<?php

/**
 * @author Ben Squire
 * @copyright 2012
 * @name Postcode Anywhere PHP API
 * @example $sPostcodeanywhere->setFilter('OnlyResidential')->setLanguage('English')->addressFromPostcode('LE18 2RF');
 */
abstract class postcodeAnywhere {

	protected $aPossibleLanguages = array('english', 'welsh');
	protected $aPossibleFilters = array('None', 'OnlyResidential', 'OnlyCommercial');
	public $sLicenceKey = '';
	public $sAccountCode = '';
	public $sLanguage = 'English';
	public $sUsername = null;
	public $aData = array();
	public $iErrorID = null;
	public $sErrorMessage = null;
	public $sPostcode = '';
	
	
	protected $iTop;  //The maximum number of rows to return.
	protected $sOrderBy; //A list of columns to order the results by.
	protected $sFilter = 'None';  //A SQL-style WHERE filter to apply to the result. Name LIKE 'a%'
	protected $iPageNumber; //Integer	Returns the relevant page from the results, 1 being the first. Must be used in conjunction with $PageSize.			5
	protected $iPageSize; //Selects the appropriate results per page size to use.
	protected $bUseHTTPs = false;
	protected $sUrl = null;
	protected $aUrl = array();

	public function __construct() {
		
	}

	protected abstract function run();

	/**
	 * Set the required language of the postcode request
	 *
	 * @param string $sLanguage
	 */
	public function setLanguage($sLanguage) {
		$sLanguage = strtolower($sLanguage);

		if (!in_array($sLanguage, $this->aPossibleLanguages)) {
			throw new Exception('Invalid Requested Language');
		}

		$this->sLanguage = $sLanguage;
		return $this;
	}

	/**
	 * Set the username of the postcodeanywhere account
	 *
	 * @param string $sUsername
	 * @return postcodeanywhere
	 */
	public function setUsername($sUsername = null) {
		if (!is_string($sUsername) || strlen($sUsername) === 0) {
			throw new Exception('Invalid username, string or null');
		}

		$this->sUsername = $sUsername;
		return $this;
	}

	/**
	 * Set the filter for our returned results.
	 *
	 * @param string $sFilter
	 * @return postcodeanywhere
	 */
	public function setFilter($sFilter = null) {
		if (!in_array($sFilter, $this->aPossibleFilters)) {
			throw new Exception('Invalid requestd filter');
		}

		$this->sFilter = $sFilter;
		return $this;
	}

	/**
	 * Sets the key for the request
	 * TODO:	Needs better validation
	 *
	 * @param string $sKey
	 * @return postcodeanywhere
	 */
	public function setLicenceKey($sKey) {
		if (strlen($sKey) === 0) {
			throw new Exception('Invalid api key');
		}

		$this->sLicenceKey = $sKey;
		return $this;
	}

	/**
	 * Set the postcodenywhere account code.
	 *
	 * @param string $sAccountCode
	 * @return postcodeanywhere
	 */
	public function setAccountCode($sAccountCode) {
		if (strlen($sAccountCode) === 0) {
			throw new Exception('Invalid Account Code');
		}

		$this->sAccountCode = $sAccountCode;
		return $this;
	}

	/**
	 * Set the house name and number we'll be searching on
	 *
	 * @param string $sPostcode The postcode to search for
	 *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setPostcode($sPostcode = null) {
		$oPostcode = new Postcode();

		if (!$oPostcode->isValid($sPostcode)) {
			throw new Exception('Invalid Postcode');
		}

		$this->sPostcode = $oPostcode->cleanPostcode($sPostcode);
		return $this;
	}

	/**
	 * Set the house name/number we'll be searching for
	 *
	 * @param string $sHouseNameNumber The house name/number to search for
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setHouseNameNumber($sHouseNameNumber) {
		if (strlen($sHouseNameNumber) === 0) {
			throw new Exception('Invalid House Name/Number');
		}

		$this->sHouseNameNumber = $sHouseNameNumber;
		return $this;
	}

	/**
	 * Sets the address ID we'll be looking up.
	 *
	 * @param int $iAddressID The address ID to look up
	 *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setAddressID($iAddressID) {
		if (!is_int($iAddressID)) {
			throw new Exception('Invalid Address ID');
		}
		$this->iAddressID = (int) $iAddressID;
		return $this;
	}

	/**
	 * Determines whether we access the service using SSL or not
	 *
	 * @param boolean $bUseHTTPs True or False
	 *
	 * @return \postcodeanywhere
	 * @throws Exception
	 */
	public function setUseHTTPs($bUseHTTPs = false) {
		if (!is_bool($bUseHTTPs)) {
			throw new Exception('Invalid Use HTTPs value supplied');
		}

		$this->bUseHTTPs = (bool) $bUseHTTPs;
		return $this;
	}

	/**
	 * Sets the object error id and message
	 *
	 * @param int $iID
	 * @param string $sMessage
	 * @return postcodeanywhere
	 */
	protected function setError($sMessage) {
		$this->sErrorMessage = $sMessage;
		return $this;
	}

	/**
	 * Sets the currently returned data
	 * @param array $aData
	 * @return postcodeanywhere
	 */
	protected function setData($aData) {
		$this->aData = $aData;
		return $this;
	}

	/**
	 * Get the current objects error information
	 *
	 * @return array
	 */
	public function getError() {
		return $this->sErrorMessage;
	}

	/**
	 * Returns the current array of postcode data.
	 *
	 * @return array
	 */
	public function getData() {
		return $this->aData;
	}

	/**
	 * Match the address ID with that stored in the object data variable
	 *
	 * @param int $iMatchID
	 * @return mixed boolean|array
	 */
	public function matchAddressID($iMatchID) {
		foreach ($this->aData as $aAddressItem) {
			if ((isset($aAddressItem['id']) && $aAddressItem['id'] == $iMatchID)) {
				return $aAddressItem;
			}
		}

		return false;
	}

	/**
	 * Sends the GET request to PostcodeAnywhere
	 * 
	 * @param string $sUrl
	 * @return boolean
	 * @throws Exception
	 */
	protected function fetchXML($sUrl) {
		if (strlen($sUrl) === 0) {
			throw new Exception('Invalid URL');
		}

		libxml_clear_errors();
		libxml_use_internal_errors(true);

		$oXML = @simplexml_load_file($sUrl);
		$aErrors = libxml_get_errors();
		libxml_use_internal_errors(false);

		if (count($aErrors) > 0) {
			$aError = array_shift($aErrors);
			$this->sErrorMessage = $aError->message;
			return false;
		}

		return $oXML;
	}

	/**
	 * Build the URL to send the address request
	 * 
	 * @return string
	 */
	protected function buildUrl() {
		$this->aUrl['Key'] = $this->sLicenceKey;
		$this->aUrl['UserName'] = $this->sUsername;
		$this->aUrl['PreferredLanguage'] = $this->sLanguage;

		//$this->aUrl['$Top']
		//$this->aUrl['$Orderby']
		//$this->aUrl['$Filter']
		//$this->aUrl['$PageNumber']
		//$this->aUrl['$PageSize']
		//Make the request to Postcode Anywhere and parse the XML returned
		$aUrl = '';
		foreach ($this->aUrl AS $sKey => $sValue) {
			$sValue = trim($sValue);
			
			if (strlen($sValue) > 0) {
				$aUrl[] = $sKey . '=' . urlencode($sValue);
			}
		}

		return 'http' . ($this->bUseHTTPs ? 's' : '') . '://' . $this->sUrl . '?' . implode('&', $aUrl);
	}

}

/**
 * Interactive Find v1.10
 * Lists address records matching the specified search term. This general search method can search by postcode, company or street.
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/Find/v1.1/default.aspx
 */
class interactiveFind extends postcodeanywhere {

	protected $sSearchTerm = null;
	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/Find/v1.10/xmla.ws';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Sets the searchterm the interactive find will look for
	 * 
	 * @param string $sSearchTerm
	 * 
	 * @return \interactiveFind
	 */
	public function setSearchTerm($sSearchTerm) {
		$this->sSearchTerm = $sSearchTerm;
		return $this;
	}

	/**
	 * Fetches an address based on a ad-hoc string (Free?)
	 * http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/Find/v1.1/default.aspx
	 *
	 * @return boolean
	 */
	public function run() {
		//Standard URL Parameters
		$this->aUrl = array();

		//Specific
		$this->aUrl['Filter'] = $this->sFilter;
		$this->aUrl['SearchTerm'] = $this->sSearchTerm;
		
		//Make the request
		$oXML = $this->fetchXML($this->buildUrl());

		if (!$oXML) {
			return false;
		}

		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		$aData = array();
		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array('id' => (float) $item->attributes()->Id, 'street' => (string) $item->attributes()->StreetAddress, 'place' => (string) $item->attributes()->Place);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 * Find an address using a UK postcode
 * 
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/FindByPostcode/v1/default.aspx
 */
class interactiveFindByPostcode extends postcodeanywhere {

	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/FindByPostcode/v1.00/xmla.ws';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Fetch possible address based on the postcode (Free?)
	 * 
	 * @return boolean
	 */
	public function run() {
		if (strlen($this->sPostcode) === 0) {
			throw new Exception('Invalid Postcode.');
		}

		//Build URL
		$this->aUrl = array();
		$this->aUrl['Postcode'] = $this->sPostcode;

		//Make the request
		//Make the request to Postcode Anywhere and parse the XML returned
		$oXML = $this->fetchXML($this->buildUrl());

		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		$aData = array();
		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array('id' => (float) $item->attributes()->Id, 'street' => (string) $item->attributes()->StreetAddress, 'place' => (string) $item->attributes()->Place);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 * Interactive Retrieve By Id v1.30
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/RetrieveById/v1.3/default.aspx
 */
class interactiveRetrieveByID extends postcodeanywhere {

	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/RetrieveById/v1.20/xmla.ws';
	protected $iAddressID = null;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieves address information based on the postcodeanywhere id (Not Free)
	 *
	 * @return boolean
	 */
	public function run() {

		if (strlen($this->iAddressID) === 0) {
			throw new Exception('No Address ID set');
		}

		//Required.
		$this->aUrl = array();
		$this->aUrl['Id'] = (int) $this->iAddressID;

		$oXML = $this->fetchXML($this->buildUrl());
		
		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}
	
		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array(
				'udprn' => (int) $item->attributes()->Udprn,
				'company' => (string) $item->attributes()->Company,
				'department' => (string) $item->attributes()->Department,
				'line1' => (string) $item->attributes()->Line1,
				'line2' => (string) $item->attributes()->Line2,
				'line3' => (string) $item->attributes()->Line3,
				'line4' => (string) $item->attributes()->Line4,
				'line5' => (string) $item->attributes()->Line5,
				'posttown' => (string) $item->attributes()->PostTown,
				'county' => (string) $item->attributes()->County,
				'postcode' => (string) $item->attributes()->Postcode,
				'mailsort' => (int) $item->attributes()->Mailsort,
				'barcode' => (string) $item->attributes()->Barcode,
				'type' => (string) $item->attributes()->Type,
				'delivery_point_suffix' => (string) $item->attributes()->DeliveryPointSuffix,
				'sub_building' => (string) $item->attributes()->SubBuilding,
				'building_name' => (string) $item->attributes()->BuildingName,
				'building_number' => (string) $item->attributes()->BuildingNumber,
				'primary_street' => (string) $item->attributes()->PrimaryStreet,
				'secondary_street' => (string) $item->attributes()->SecondaryStreet,
				'double_dependent_locality' => (string) $item->attributes()->DoubleDependentLocality,
				'dependent_locality' => (string) $item->attributes()->DependentLocality,
				'pobox' => (string) $item->attributes()->PoBox,
				'primary_street_name' => (string) $item->attributes()->PrimaryStreetName,
				'primary_street_type' => (string) $item->attributes()->PrimaryStreetType,
				'secondary_street_name' => (string) $item->attributes()->SecondaryStreetName,
				'secondary_street_type' => (string) $item->attributes()->SecondaryStreetType,
				'country_name' => (string) $item->attributes()->CountryName,
				'country_iso2' => (string) $item->attributes()->CountryISO2,
				'country_iso3' => (string) $item->attributes()->CountryISO3
			);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 *
 * @see http://www.postcodeanywhere.co.uk/support/webservices/PostcodeAnywhere/Interactive/RetrieveByAddress/v1.2/default.aspx
 * (Not free)
 */
class interactiveRetrieveByAddress extends postcodeanywhere {

	protected $sAddress = null;
	protected $sCompany = null;
	protected $sUrl = 'services.postcodeanywhere.co.uk/PostcodeAnywhere/Interactive/RetrieveByAddress/v1.20/xmla.ws';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Sets the address of the address were looking for
	 * 
	 * @param string $sAddress
	 * @throws Exception
	 */
	public function setAddress($sAddress = '') {
		if (strlen($sAddress) === 0) {
			throw new Exception('Invalid Address String');
		}
		
		$this->sAddress = $sAddress;
	}
	
	/**
	 * Sets the companny name of the address were looking for
	 * 
	 * @param string $sCompany
	 * @throws Exception
	 */
	public function setCompany($sCompany) {
		if (strlen($sCompany) === 0) {
			throw new Exception('Invalid Company String');
		}
		
		$this->sCompany = $sCompany;
	}
	
	
	/**
	 * Perform search for address using company name and/or address
	 * 
	 * @return boolean
	 */
	public function run() {
		//Standard
		$this->aUrl = array();


		//Specific
		$this->aUrl['Address'] = $this->sAddress;
		$this->aUrl['Company'] = $this->sCompany;

		//Make the request
		$oXML = $this->fetchXML($this->buildUrl());

		//Check for an error
		if ($oXML->Columns->attributes()->Items == 4 && $oXML->Columns->Column->attributes()->Name == 'Error') {
			$this->setError((string) $oXML->Rows->Row['Description'] . ' - ' . $oXML->Rows->Row['Cause']);
			return false;
		}

		//Create the response
		if (empty($oXML->Rows)) {
			return false;
		}

		foreach ($oXML->Rows->Row as $item) {
			$aData[] = array(
				'udprn' => (int) $item->attributes()->Udprn,
				'company' => (string) $item->attributes()->Company,
				'department' => (string) $item->attributes()->Department,
				'line1' => (string) $item->attributes()->Line1,
				'line2' => (string) $item->attributes()->Line2,
				'line3' => (string) $item->attributes()->Line3,
				'line4' => (string) $item->attributes()->Line4,
				'line5' => (string) $item->attributes()->Line5,
				'posttown' => (string) $item->attributes()->PostTown,
				'county' => (string) $item->attributes()->County,
				'postcode' => (string) $item->attributes()->Postcode,
				'mailsort' => (int) $item->attributes()->Mailsort,
				'barcode' => (string) $item->attributes()->Barcode,
				'type' => (string) $item->attributes()->Type,
				'delivery_point_suffix' => (string) $item->attributes()->DeliveryPointSuffix,
				'sub_building' => (string) $item->attributes()->SubBuilding,
				'building_name' => (string) $item->attributes()->BuildingName,
				'building_number' => (string) $item->attributes()->BuildingNumber,
				'primary_street' => (string) $item->attributes()->PrimaryStreet,
				'secondary_street' => (string) $item->attributes()->SecondaryStreet,
				'double_dependent_locality' => (string) $item->attributes()->DoubleDependentLocality,
				'dependent_locality' => (string) $item->attributes()->DependentLocality,
				'pobox' => (string) $item->attributes()->PoBox,
				'primary_street_name' => (string) $item->attributes()->PrimaryStreetName,
				'primary_street_type' => (string) $item->attributes()->PrimaryStreetType,
				'secondary_street_name' => (string) $item->attributes()->SecondaryStreetName,
				'country_name' => (string) $item->attributes()->CountryName,
				'confidence' => (string) $item->attributes()->Confidence
			);
		}

		$this->setData($aData);
		return true;
	}

}

/**
 * Utility class for the validation and cleansing of Postcodes
 *
 */
class Postcode {

	public $sPostCodeRegex = '/^([A-PR-UWYZ0-9][A-HK-Y0-9][AEHMNPRTVXY0-9]?[ABEHMNPRVWXY0-9]? {1,2}[0-9][ABD-HJLN-UW-Z]{2}|GIR 0AA)$/';

	public function __construct() {
		
	}

	/**
	 * Is the postcode valid?
	 *
	 * @param type $sPostcode
	 *
	 * @return boolean
	 */
	public function isValid($sPostcode = '') {
		return (bool) preg_match($this->sPostCodeRegex, $this->cleanPostcode($sPostcode));
	}

	/**
	 * Clean a postcode string
	 *
	 * @param string $sPostcode
	 * @return string
	 */
	public function cleanPostcode($sPostcode = '') {
		$sPostcode = strtoupper(str_replace(' ', '', $sPostcode));
		$sPostcode = wordwrap($sPostcode, strlen($sPostcode) - 3, ' ', true);
		return trim($sPostcode);
	}

}

?>