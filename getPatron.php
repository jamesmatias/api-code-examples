<?php
function getPatron($barcode){
	include "apiconstants.php";
	include_once "getToken.php";

	// Retrieve an access token
	$tstamp = 0;
	$token = getToken($token,$tstamp);

	// Begin the GET request to the API

	// Define the URL to retrieve patron data from the API. 
	// $apiurl is defined in apiconstants.php
	// We're using varFieldTag=b to search, so we pass the patron's barcode to the API as varFieldContent
	// fields= is a comma delimited list of fields that we want returned. Check the documentation to see fields available and their format
	// Because commas can't be passed in the URL, they must each converted to Hex ASCII code '%2C'
	// In this case we want name(s) and email(s) returned. Record number (id) is always returned.
	$getPatronURL = $apiurl."patrons/find?varFieldTag=b&varFieldContent=".$barcode."&fields=names%2Cemails";

	// $hosturl, $appname, and $webserver are defined in apiconstants.php
	// $hosturl is the URL of the Sierra App Server
	// $token is the authentication token that we just retrieved
	// $appname is the name of our webapp
	// $webserver is the IP or Hostname of the server executing this code
	$ch = curl_init($getPatronURL);
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

	// Check to make sure there was a response, if not print the error to std out and return false.
	if($response === FALSE){
		echo curl_error($ch);
		return false;
	}

	// Parse the JSON response so that PHP can access the data
	$patronData = json_decode($response, true);

	// Check to make sure data was retrieved and parsed, if not print error to std out with timestamp and return false.
	if(is_null($patronData)){
		echo date("Y-m-d H:i:s")." Patron Data response is null.\n";
		return false;
	}
	
	// Data would be accessed as:
	// $patronID = $patronData["id"];	
	// A patron can have multiple names in their record, so the data is always
	// passed as an array. We can retrieve the first full name returned by using:
	// $patronNames = (array)$patronData["names"];
	// $patronName = $patronNames[0];
	// Emails work the same as names:
	// $patronEmails = (array)$patronData["emails"];
	// $patronEmail = $patronEmails[0];
	// In both cases it is a good idea to check that element 0 exists before trying to read it.
	
	
	
	// Return the parsed array of patron data for use in calling function.
	return $patronData;

}

?>