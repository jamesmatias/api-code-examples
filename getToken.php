<?php 
/*	 This function attempts to retrieve an API access token using variables defined in the apiconstants.php file
	It takes two parameters:
	$token can be null or a valid token to be checked/renewed
	$tstamp is the expiration timestamp of the token
*/
function getToken($token, &$tstamp){
	
	// Include global API constants that we've defined in apiconstants.php
	include "apiconstants.php";
	
	// If token is expired, soon expiring, or does not exist, request a new token
	if (is_null($token) || $tstamp <= (time() - $token_expire_interval))
	{
		// Print to standard out that the token is expired, with timestamp. 
		echo date("Y-m-d H:i:s")." Token expired or null. Requesting new token.\n";
		
		// URL for token request, with $apiurl from apiconstants.php
		$tokenurl = $apiurl."token";

		// Begin the POST request to API
		$postBody="grant_type=client_credentials";
		
		// $hosturl and $encauth are defined in apiconstants.php
		// $hosturl is the URL of the Sierra App Server
		// $encauth is the Base64 encoded authentication key and secret pair.
		$ch = curl_init($tokenurl);
		curl_setopt_array($ch,array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HTTPHEADER => array(
						'Host: '.$hosturl,
						'Authorization: Basic '.$encauth,
						'Content-Type: application/x-www-form-urlencoded'
				),
				CURLOPT_POSTFIELDS => $postBody
		));
		
		// Execute the POST to the API and retrieve the response
		$response = curl_exec($ch);
		
		// Check to make sure there was a response, if not print the error with timestamp to std out and return false.
		if($response === FALSE){
			echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
			return false;
		}
		
		// Parse the JSON response so that PHP can access the data
		$tokenData = json_decode($response, true);
		
		// Check to make sure data was retrieved and parsed, if not print the error with timestamp to std out and return false.
		if(is_null($tokenData)){
			echo date("Y-m-d H:i:s")." Could not retrieve token from server.\n";
			return false;
		}
		
		// Retrieve the token from the response.
		$token = $tokenData["access_token"];
		
		// Use the info endpoint to get time remaining for token and update $tstamp variable
		// Begin the GET request to the API
		$tokenurl = $apiurl."info/token";
		
		// $hosturl, $appname, and $webserver are defined in apiconstants.php
		// $hosturl is the URL of the Sierra App Server
		// $token is the authentication token that we just retrieved
		// $appname is the name of our webapp
		// $webserver is the IP or Hostname of the server executing this code
		$ch = curl_init($tokenurl);
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
	
	// Execute the GET to the API and retrieve the response
	$response = curl_exec($ch);

	// Check to make sure there was a response, if not print the error with timestamp to std out and return false.
	if($response === FALSE){
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}

	// Parse the JSON response so that PHP can access the data
	$tiData = json_decode($response, true);
	
	// Check to make sure data was retrieved and parsed, if not print error to std out with timestamp and return false.
	if(is_null($tiData)){
		echo date("Y-m-d H:i:s")." Token info response is null.\n";
		return false;
	}
	
	// Set the new value of the token's expiry timestamp
	$tstamp = $tiData["expiresIn"] + time();
	
	// Return the new token
	return $token;
		
	}
	// If the existing token is valid - do nothing, return the original token
	else 
		return $token;
	
}

?>