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

$inverterResult = json_decode(get(
	"http://".$inverterIp."/solar_api/v1/GetInverterRealtimeData.cgi?Scope=System"
), true)['Body']["Data"];

if(!$inverterResult){
	exit();
}

echo json_encode($inverterResult);
?>