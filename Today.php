<?php
header('Content-Type: application/json');
error_reporting(0);

function get($url) {
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
	curl_setopt($ch,CURLOPT_HTTPHEADER, array(
					"cache-control: no-cache"
	));
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch); 
	curl_close($ch); 
	return $output;
}


// settings
$inverterIp = "<fronius ip/name>";
date_default_timezone_set("Australia/Brisbane");

$date = date("Y-m-d");

$inverterResult = json_decode(get(
	"http://".$inverterIp."/solar_api/v1/GetArchiveData.cgi?Scope=Device&DeviceClass=Inverter&StartDate=".$date."&EndDate=".$date."&DeviceId=1&Channel=PowerReal_PAC_Sum"
), true)['Body']["Data"]["inverter/1"]["Data"]["PowerReal_PAC_Sum"]["Values"];

if(!$inverterResult){
	exit();
}

// $hourlyData = array(
// 	0  => 0, 1  => 0, 2  => 0, 3  => 0, 4  => 0, 5  => 0, 6  => 0, 7  => 0, 8  => 0, 9  => 0, 10 => 0, 11 => 0, 
// 	12 => 0, 13 => 0, 14 => 0, 15 => 0, 16 => 0, 17 => 0, 18 => 0, 19 => 0, 20 => 0, 21 => 0, 22 => 0, 23 => 0
// );

// foreach ((array) $inverterResult as $k => $v) {
// 	if($v > 0) {
// 		$hour = floor($k/60/60);
// 		$hourlyData[$hour] = $hourlyData[$hour] + ($v/12/1000);
// 	}
// }


echo json_encode($inverterResult);
?>