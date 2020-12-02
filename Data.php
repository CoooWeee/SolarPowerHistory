<?php
header('Content-Type: application/json');
error_reporting(0);

// settings
$accountNumber = "<energy australia costumer id>";
$username = "<energy australia email>";
$password = "<energy australia password>";
$inverterIp = "<fronius ip/name>";

// http init
require_once 'HttpRequester.php';
$browser = new HttpRequester;
$browser->postfollowredirs = true;

function login($browser, $username, $password) {
	return $browser->post(
		'https://www.energyaustralia.com.au/myaccount/login',
		"username=".$username."&password=".$password
	);
}

function getPeriod($browser, $accountNumber) {
	$response = $browser->get(
		"https://www.energyaustralia.com.au/myaccount/api/usage/".$accountNumber."/summary?meterType=SMART_MANUAL"
	);

	$dates = [$response["earliestDataDate"] , $response["latestDataDate"] ];

	$endDate = new DateTime(implode("-", $dates[1]));
	$endDate->modify('+1 day');

	return new DatePeriod(
		new DateTime(implode("-", $dates[0])),
		new DateInterval('P1D'),
		$endDate
	);
}

function saveInverterData($browser, $inverterIp, $period) {
	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');
	
		$filepath = "./data/inverter/".$date.'.json';
		$json_string = file_get_contents($filepath);
	
		if ($json_string === false) {
			$result = $browser->get(
				"http://".$inverterIp."/solar_api/v1/GetArchiveData.cgi?Scope=Device&DeviceClass=Inverter&StartDate=".$date."&EndDate=".$date."&DeviceId=1&Channel=TimeSpanInSec&Channel=EnergyReal_WAC_Sum_Produced&Channel=EnergyReal_WAC_Sum_Consumed&Channel=InverterEvents&Channel=InverterErrors&Channel=Current_DC_String_1&Channel=Current_DC_String_2&Channel=Voltage_DC_String_1&Channel=Voltage_DC_String_2&Channel=Temperature_Powerstage&Channel=Voltage_AC_Phase_1&Channel=Voltage_AC_Phase_2&Channel=Voltage_AC_Phase_3&Channel=Current_AC_Phase_1&Channel=Current_AC_Phase_2&Channel=Current_AC_Phase_3&Channel=PowerReal_PAC_Sum&Channel=EnergyReal_WAC_Minus_Absolute&Channel=EnergyReal_WAC_Plus_Absolute&Channel=Meter_Location_Current&Channel=Temperature_Channel_1&Channel=Temperature_Channel_2&Channel=Digital_Channel_1&Channel=Digital_Channel_2&Channel=Radiation&Channel=Digital_PowerManagementRelay_Out_1&Channel=Digital_PowerManagementRelay_Out_2&Channel=Digital_PowerManagementRelay_Out_3&Channel=Digital_PowerManagementRelay_Out_4"
			);
	
			if($result !== null) {
				$json_string = json_encode($result);
				file_put_contents($filepath, $json_string);
			}
		} 
	}
}

function convertInverterDataToHourly($period) {
	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');
	
		$filepath = "./data/inverter/".$date.".json";
		$hourlyfilepath = "./data/hourly/".$date.".json";
		$json_string = file_get_contents($hourlyfilepath);
	
		if ($json_string === false) {
			$json = null;
			$json_string = file_get_contents($filepath);

			$json = json_decode($json_string, true)['Body']["Data"]["inverter/1"]["Data"]["PowerReal_PAC_Sum"]["Values"];
	
			if($json) {
				$hourlyData = array(
					0  => 0, 1  => 0, 2  => 0, 3  => 0, 4  => 0, 5  => 0, 6  => 0, 7  => 0, 8  => 0, 9  => 0, 10 => 0, 11 => 0, 
					12 => 0, 13 => 0, 14 => 0, 15 => 0, 16 => 0, 17 => 0, 18 => 0, 19 => 0, 20 => 0, 21 => 0, 22 => 0, 23 => 0
				);
	
				foreach ((array) $json as $k => $v) {
					if($v > 0) {
						$hour = floor($k/60/60);
						$hourlyData[$hour] = $hourlyData[$hour] + ($v/12/1000);
					}
				}
	
				$json_string = json_encode($hourlyData);
				file_put_contents($hourlyfilepath , $json_string);
			}
		} 
	
	}
}

function saveEAData($browser, $accountNumber, $period) {
	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');
	
		$filepath = "./data/ea/".$date.'.json';
		$json_string = file_get_contents($filepath);
	
		if ($json_string === false) {
			$result = $browser->get(
				"https://www.energyaustralia.com.au/myaccount/api/usage/".$accountNumber."/hourly?forDay=".$date."&ts=" . time()
			)[0];
	
			if($result !== null) {
				$json_string = json_encode($result);
				file_put_contents($filepath, $json_string);
			}
		} 
	}
}

function MergeData($period) {
	$result = array();

	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');

		$hourlyfilepath = "./data/hourly/".$date.".json";
		$eafilepath = "./data/ea/".$date.'.json';

		$solarKw = json_decode(file_get_contents($hourlyfilepath), true);

		if($solarKw) {
			$ea = json_decode(file_get_contents($eafilepath), true);

			$day = array();

			foreach ($ea as $i) {
				$hour = $i['interval'];
				$solar = $solarKw[$hour];
				$day[$hour] = array(
						"interval" => $hour,
						"bought" =>  $i['consumption'],
						"sold" => - $i['soldToGrid'],
						"produced" => $solar,
						"consumed" => - $i['consumption'] - ( $solar - $i['soldToGrid'])
				);
			}

			array_push($result, array( "date"=> $date, "data"=> $day  ));
		}
	}
	return $result;
}

login($browser, $username, $password);
$period = getPeriod($browser, $accountNumber);
saveEAData($browser, $accountNumber, $period);
saveInverterData($browser, $inverterIp, $period);
convertInverterDataToHourly($period);
$result = MergeData($period);

echo json_encode($result);

?>