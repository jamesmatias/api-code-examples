<?php
// When passed the API Item URL (for example from a checkout response)
// returns the title of the item using the Bib API.
function getTitle($item, &$token, &$tstamp)
{
	include "apiconstants.php";
	
	// Check the life of the token and renew if necessary.
	$token = getToken($token, $tstamp);
	
	// Get Bib ID using Item API
	$ch = curl_init($item);
	curl_setopt_array($ch,array(
			CURLOPT_HTTPGET => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array(
			'Host: '.$hosturl,
			'Authorization: Bearer '.$token,
			'User-Agent: '.$appname,
			'X-Forwarded-For: '.$webserver
			)
		));
				
	$response = curl_exec($ch);
				
	if($response === FALSE){
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}			
				
	$itemData = json_decode($response, true);
	if(is_null($itemData)){
		echo date("Y-m-d H:i:s")." Item response is null.\n";
		echo date("Y-m-d H:i:s")." ".$response."\n";
		return false;
	}
	
	// Get Title using Bib API, checks first bib only (if multiple are listed)
	$bibIDs = (array)$itemData["bibIds"];
	$bibURL = $apiurl."bibs/".$bibIDs[0];
		
	$ch = curl_init($bibURL);
	curl_setopt_array($ch,array(
			CURLOPT_HTTPGET => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array(
			'Host: '.$hosturl,
			'Authorization: Bearer '.$token,
			'User-Agent: '.$appname,
			'X-Forwarded-For: '.$webserver
			)
		));
				
	$response = curl_exec($ch);
				
	if($response === FALSE){
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}			
				
	$bibData = json_decode($response, true);
	if(is_null($bibData)){
		echo date("Y-m-d H:i:s")." Bib response is null.\n";
		echo date("Y-m-d H:i:s")." ".$response."\n";
		return false;
	}
	
	// Return title from bib
	return $bibData["title"];
}
?>