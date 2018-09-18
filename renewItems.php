<?php
function renewItems($pid, $token, $tstamp){
	include "apiconstants.php";

	// Check the life of the token and renew if necessary.
	$token = getToken($token, $tstamp);

	// Begin the GET request to the API

	// Define the URL to retrieve checkout data from the API. $pid is the patron record number.
	$getCheckoutsURL = $apiurl."patrons/{$pid}/checkouts";

	// $hosturl, $appname, and $webserver are defined in apiconstants.php
	// $hosturl is the URL of the Sierra App Server
	// $token is the authentication token that we just retrieved
	// $appname is the name of our webapp
	// $webserver is the IP or Hostname of the server executing this code
	$ch = curl_init($getCheckoutsURL);
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
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}

	// Parse the JSON response so that PHP can access the data
	$coData = json_decode($response, true);
	
	// Check to make sure data was retrieved and parsed, if not print error to std out with timestamp and return false.
	if(is_null($coData)){
		echo date("Y-m-d H:i:s")." Checkout response is null.\n";
		return false;
	}
	
	// Retrieve Checkout data
	// It will include total, which is the total number of checkouts
	// It will also return an array of checkouts called 'entries'. Each element provides the following data for each checkout:
	// 		id - the checkout id
	// 		patron - the patron with the checkout
	// 		item - the item that is checked out
	//		dueDate - the item dueDate
	//		numberOfRenewals - the number of renewals of this checkout
	//		outDate - the original date the item was checked out
	$numCheckouts = (int)$coData["total"];	
	$entries = (array)$coData["entries"];

	// Iterate and parse checkouts and attempt renewals
	$success = 0;
	$failed = 0;
	
	// Enumerate the entries array and attempt to renew each checkout
	foreach($entries as $temp)
	{
		// We could use the dueDate element to determine whether or not to try to renew
		// the checkout, but for simplicity we do not do that here. See autorenewdb.php on
		// Github for an example. 
		// "2018-09-18T08:00:00Z"
		$dueDate = $temp["dueDate"];
		
		// Check the life of the token and renew if necessary.
		$token = getToken($token, $tstamp);
		
		// Begin the POST request to API
		
		// API returns a URL as the ID, append /renewal for the API URL to renew that checkout
		// $temp[id] is "https://<sierra app server>/iii/sierra-api/v5/patrons/checkouts/3004430"
		$renewCOURL = $temp["id"]."/renewal";
			
		// $hosturl, $appname, and $webserver are defined in apiconstants.php
		// $hosturl is the URL of the Sierra App Server
		// $token is the authentication token that we just retrieved
		// $appname is the name of our webapp
		// $webserver is the IP or Hostname of the server executing this code
		$ch = curl_init($renewCOURL);
		curl_setopt_array($ch,array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HTTPHEADER => array(
						'Host: '.$hosturl,
						'Authorization: Bearer '.$token,
						'User-Agent: '.$appname,
						'X-Forwarded-For: '.$webserver
				)
		));
			
		// Execute the POST to the API and retrieve the response
		$response = curl_exec($ch);
			
		// Check to make sure there was a response, if not print the error with timestamp to std out and return false.
		if($response === FALSE){
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
		}
		
		// Parse the JSON response so that PHP can access the data
		$renewData = json_decode($response, true);
		
		// Check to make sure data was retrieved and parsed, if not print error to std out with timestamp and return false.
		if(is_null($renewData)){
				echo date("Y-m-d H:i:s")." Renewal response is null.\n";
				return false;
		}
		
		// Parse response - printed to std out for debugging
		// HTTP Code 200 is OK.
		if(curl_getinfo($ch,CURLINFO_HTTP_CODE) == 200)
		{
			
			// Get the new due date
			$newDue = $renewData["dueDate"];
			
			// Previous versions of the API would sometimes say a renewal was successful, but not change the due date.
			// Check to see if this is the case
			if(strcmp($newDue,$dueDate)== 0)
			{
				// Due date didn't change
				// Failure Response - print debugging data to std out
				echo date("Y-m-d H:i:s")." Code: ".$renewData["code"].".".$renewData["specificCode"]."\n";
				echo date("Y-m-d H:i:s")." HTTP Status: ".$renewData["httpStatus"]."\n";
				echo date("Y-m-d H:i:s")." Description: ".$renewData["description"]."\n";
				echo "Renewal URL: ".$renewCOURL."\n";
				echo "Server Response: ".$response."\n";
				echo "JSON Response: \n";
				echo var_dump($renewData)."\n";
				
				// Increment failure counter
				$failed = $failed + 1;
				
			}
			else
			{
				// The due date was changed, so we increment our success counter			
				$success = $success + 1;
			}
		}
		else 
		{
			// Non-200 code is a failure - print debugging data to std out	
			// One example of failure would be "Webpac - Too Many Renewals"
			echo date("Y-m-d H:i:s")." Code: ".$renewData["code"].".".$renewData["specificCode"]."\n";
			echo date("Y-m-d H:i:s")." HTTP Status: ".$renewData["httpStatus"]."\n";
			echo date("Y-m-d H:i:s")." Description: ".$renewData["description"]."\n";
			echo "Renewal URL: ".$renewCOURL."\n";
			echo "Server Response: ".$response."\n";
			echo "JSON Response: \n";
			echo var_dump($renewData)."\n";
			
			// Increment failure counter
			$failed = $failed + 1;
		}			
	} // end of for loop

	// print results to std out
	echo $success." items were renewed.\n";
	echo $failed." items were unable to be renewed.\n";
	
	// if any items were renewed, return that number.
	if ($success)
		return $success;
	else
		return -1;
}

?>