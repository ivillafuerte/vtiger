<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once dirname(__FILE__) . '/PendingTicketsOfMine.php';

/** Idle Ticket Alert */
class CTMobile_WS_AlertModel_IdleTicketsOfMine extends CTMobile_WS_AlertModel_PendingTicketsOfMine {
	function __construct() {
		parent::__construct();
		$this->name = 'Idle Ticket Alert';
		$this->moduleName = 'HelpDesk';
		$this->refreshRate= 1 * (60 * 60); // 1 hour
		$this->description='Alert sent when ticket has not been updated in 24 hours';
	}
	
	function query() {
		$sql = parent::query();
		$sql .= " AND DATEDIFF(CURDATE(), vtiger_crmentity.modifiedtime) > 1";
		return $sql;
	}
}
