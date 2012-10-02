<?php

//Include Library
include('../postcodeanywhere.php');

//Set Licence and Account Code
$oPostcode = new interactiveFindByPostcode();
$oPostcode->setLicenceKey('9999-9999-9999-9999');
$oPostcode->setAccountCode('TESTS99999');

//Set Language (Not needed, english is the default)
$oPostcode->setLanguage('English');

//Set the company were looking for and address
$oPostcode->setPostcode('DA1 2EN');

if (!$oPostcode->run()) {
	//Ensure there isn't any errors
	var_dump($oPostcode->sErrorMessage);
} else {
	//Output results
	var_dump($oPostcode->aData);
}

?>
