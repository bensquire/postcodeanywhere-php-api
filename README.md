postcodeanywhere-php-api
========================
A PHP library to communicate with the PostCodeAnywhere Online service (http://www.postcodeanywhere.co.uk/).

Using simple OO methods, this project will connect to and parse various services
exposed by there online service. All of there services require an account, however
several functions can be called for free.

(Please note I am in no way affiliated with postcodeanywhere.)


Implemented Functionality:
--------------------------
 - Interactive Find (Free)
 - Interactive Find By Postcode (Free)
 - Interactive Retrieve By Address (Free)
 - Interactive Retrieve By ID (Paid)


Example Code:
-------------
	$oPostcode = new interactiveFindByPostcode();
	$oPostcode->setLicenceKey('9999-9999-9999-9999');
	$oPostcode->setAccountCode('TESTS99999');
	$oPostcode->setPostcode('DA1 2EN');
	$oPostcode->run();


Requirements:
-------------
This library requires no additional software beyond a functional version of PHP
5.2 (or greater), with the simpleXMLElement library include (done by default).