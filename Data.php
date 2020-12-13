<?php
header('Content-Type: application/json');
error_reporting(0);

// settings
$accountNumber = "<energy australia costumer id>";
$username = "<energy australia email>";
$password = "<energy australia password>";
$inverterIp = "<fronius ip/name>";
$fallbackStartDate = "<first date to fetch data>";

// http init
require_once 'HttpRequester.php';
$browser = new HttpRequester;
$browser->postfollowredirs = true;

function login($username, $password) {
	return $GLOBALS['browser']->post(
		'https://www.energyaustralia.com.au/myaccount/login',
		"username=".$username."&password=".$password
	);
}

function getPeriod() {
	$response = $GLOBALS['browser']->get(
		"https://www.energyaustralia.com.au/myaccount/api/usage/".$GLOBALS['accountNumber']."/summary?meterType=SMART_MANUAL"
	);

	if($response == null) {
		$startDate = new DateTime($GLOBALS['fallbackStartDate']);
		$endDate = new DateTime(date("Y-m-d"));
		$endDate->modify('-2 day');
	} else {
		$dates = [$response["earliestDataDate"] , $response["latestDataDate"] ];
		$startDate = new DateTime(implode("-", $dates[0]));
		$endDate = new DateTime(implode("-", $dates[1]));
		$endDate->modify('+1 day');
	}


	return new DatePeriod(
		$startDate,
		new DateInterval('P1D'),
		$endDate
	);
}

function saveInverterData($period) {
	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');
	
		$filepath = "./data/inverter/".$date.'.json';
		$json_string = file_get_contents($filepath);
	
		if ($json_string === false) {
			$result = $GLOBALS['browser']->get(
				"http://".$GLOBALS['inverterIp']."/solar_api/v1/GetArchiveData.cgi?Scope=Device&DeviceClass=Inverter&StartDate=".$date."&EndDate=".$date."&DeviceId=1&Channel=TimeSpanInSec&Channel=EnergyReal_WAC_Sum_Produced&Channel=EnergyReal_WAC_Sum_Consumed&Channel=InverterEvents&Channel=InverterErrors&Channel=Current_DC_String_1&Channel=Current_DC_String_2&Channel=Voltage_DC_String_1&Channel=Voltage_DC_String_2&Channel=Temperature_Powerstage&Channel=Voltage_AC_Phase_1&Channel=Voltage_AC_Phase_2&Channel=Voltage_AC_Phase_3&Channel=Current_AC_Phase_1&Channel=Current_AC_Phase_2&Channel=Current_AC_Phase_3&Channel=PowerReal_PAC_Sum&Channel=EnergyReal_WAC_Minus_Absolute&Channel=EnergyReal_WAC_Plus_Absolute&Channel=Meter_Location_Current&Channel=Temperature_Channel_1&Channel=Temperature_Channel_2&Channel=Digital_Channel_1&Channel=Digital_Channel_2&Channel=Radiation&Channel=Digital_PowerManagementRelay_Out_1&Channel=Digital_PowerManagementRelay_Out_2&Channel=Digital_PowerManagementRelay_Out_3&Channel=Digital_PowerManagementRelay_Out_4"
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

function saveEAData( $period) {
	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');
	
		$filepath = "./data/ea/".$date.'.json';
		$json_string = file_get_contents($filepath);
	
		if ($json_string === false) {
			$result = $GLOBALS['browser']->get(
				"https://www.energyaustralia.com.au/myaccount/api/usage/".$GLOBALS['accountNumber']."/hourly?forDay=".$date."&ts=" . time()
			)[0];
	
			if($result !== null) {
				$json_string = json_encode($result);
				file_put_contents($filepath, $json_string);
			}
		} 
	}
}

function mergeData($period) {
	$result = array();

	foreach ($period as $key => $value) {
		$date = $value->format('Y-m-d');


		$mergedpath = "./data/merged/".$date.'.json';
		$json = json_decode(file_get_contents($mergedpath), true);;

		if($json ) {
			array_push($result, $json );

		} else {
			$hourlyfilepath = "./data/hourly/".$date.".json";
			$eafilepath = "./data/ea/".$date.'.json';

			$solarKw = json_decode(file_get_contents($hourlyfilepath), true);
			$ea = json_decode(file_get_contents($eafilepath), true);

			if($solarKw && $ea) {
				$day = array();

				$sums =  array(
					"interval" => 0,
					"bought" =>  0,
					"sold" => 0,
					"produced" => 0,
					"consumed" => 0
				);

				foreach ($ea as $i) {
					$hour = $i['interval'];
					$solar = $solarKw[$hour];
					$sold = - $i['soldToGrid'];
					$bought = $i['consumption'];
					$consumed = - $bought - ($solar + $sold);

					// EA does not return null for missing values
					// it is quite unlikely that I will not buy or sell anything.
					$hasData = $bought != 0 || $sold != 0;

					$sums = array(
						"interval" => $sums["interval"] + $hour,
						"bought" =>  $sums["bought"] + $bought,
						"sold" => $sums["sold"] + $sold,
						"produced" => $sums["produced"] + $solar,
						"consumed" => $sums["consumed"] + ($hasData ? $consumed : 0)
					);

					$day[$hour] = array(
							"interval" => $hour,
							"bought" =>  $hasData ? $bought : null,
							"sold" => $hasData ?  $sold : null,
							"produced" => $solar,
							"consumed" => $hasData ? $consumed : null
					);
				}
				$data = array( "date"=> $date, "data"=> $day, "sum" => $sums  );
				file_put_contents($mergedpath, json_encode($data));
				array_push($result, $data);

			}
		}
	}
	return $result;
}

login( $username, $password);
$period = getPeriod();
saveEAData( $period);
saveInverterData( $period);
convertInverterDataToHourly($period);
$result = mergeData($period);

echo json_encode($result);


?>