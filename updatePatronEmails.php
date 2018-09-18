<?php
function updatePatronEmails($pid,$emails){
	include "apiconstants.php";
	include_once "getToken.php";

	// Retrieve an access token
	$tstamp = 0;
	$token = getToken(null,$tstamp);
	
	
	// "emails" is the name of the field that we are updating
	// $emails is the array of email addresses that is passed to the function.
	// encode the data array with the field name into JSON for PUT	
	$body = json_encode(array("emails"=>$emails));
	
	// Begin the PUT request to the API

	// Define the URL to retrieve patron data from the API. 
	// $apiurl is defined in apiconstants.php
	// $pid is the patron record number for the patron that we're updating
	$updatePatronURL = $apiurl."patrons/".$pid;

	// The following two lines are necessary because we are passing JSON data in the PUT, instead of passing data in the URL
	// 'Content-Type:application/json',
	// 'Content-Length: ' . strlen($body),
	// We'll also add a new option in CURL to let it know to expect data in the PUT:
	// CURLOPT_POSTFIELDS => $body,
	// $hosturl, $appname, and $webserver are defined in apiconstants.php
	// $hosturl is the URL of the Sierra App Server
	// $token is the authentication token that we just retrieved
	// $appname is the name of our webapp
	// $webserver is the IP or Hostname of the server executing this code
	$ch = curl_init($updatePatronURL);
	curl_setopt_array($ch,array(
			CURLOPT_CUSTOMREQUEST => "PUT",
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'Content-Length: '.strlen($body),
					'Host: '.$hosturl,
					'Authorization: Bearer '.$token,
					'User-Agent: '.$appname,
					'X-Forwarded-For: '.$webserver
			)
	));

	// Execute the POST to the API and retrieve the response
	$response = curl_exec($ch);
	
	// check for a 204 response - success, but no return content
	if(curl_getinfo($ch,CURLINFO_HTTP_CODE) == 204)
		return true;
	else
	{
		//echo var_dump(curl_getinfo($ch));
		echo var_dump($response);
		return false;
	}

}

?>