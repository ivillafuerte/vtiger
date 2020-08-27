<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once dirname(__FILE__) . '/../Alert.php';

/** Server time sample alert */
class CTMobile_WS_AlertModel_ServerTimeSampleAlert extends CTMobile_WS_AlertModel {
	function __construct() {
		// Mandatory call to parent constructor
		parent::__construct();
		
		$this->name = 'Server Time Alert';
		$this->description='Alert to get server time information';
		$this->refreshRate= 1; // 1 second
		$this->recordsLinked = FALSE; 
		// There is no module records linked with message.
		// If set to true $this->moduleName needs to be set.
	}
	
	function message() {
		return date('Y-m-d H:i:s');
	}
	
	/** Override base class methods */
	function query() {
		return false;
	}

	function queryParameters() {
		return false;
	}
}
