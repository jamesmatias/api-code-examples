<?php
include "getPatron.php";
include "renewItems.php";
include "updatePatronEmails.php";

// Retrieve patron information using barcode
$patron = getPatron("1234567890123");

$pid = $patron["id"];
$pnames = $patron["names"];
$pemails = $patron["emails"];

if (count($pnames) > 0)
{
	// retrieve the first name in the list
	echo "Patron's name is ".$pnames[0]."\n";
}
else
{
	// data had no names
}
if (count($pemails) > 0)
{
	// retrieve the first email in the list
	echo "Patron's email address is ".$pemails[0]."\n";
}
else
{
	// data had no names
}

// Renew patron's items
$token = null;
$tstamp = 0;
$result = renewItems($pid, $token, $tstamp);

// Update patron's emails
$emails[] = "test@mylibrary.org";
$emails[] = "sierra@iii.com";

$result = updatePatronEmails($pid,$emails);
if($result)
	echo "Patron's email addresses were updated.\n";
else
	echo "Patron's email addresses were not updated.\n";

?>