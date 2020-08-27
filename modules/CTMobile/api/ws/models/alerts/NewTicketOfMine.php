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

/** New Ticket */
class CTMobile_WS_AlertModel_NewTicketOfMine extends CTMobile_WS_AlertModel_PendingTicketsOfMine {
	function __construct() {
		parent::__construct();
		$this->name = 'New Ticket Alert';
		$this->moduleName = 'HelpDesk';
		$this->refreshRate= 1 * (60 * 60); // 1 hour
		$this->description='Alert sent when a ticket is assigned to you';
	}
	
	function query() {
		$sql = parent::query();
		$sql .= " ORDER BY crmid DESC";
		return $sql;
	}
	
	function countQuery() {
		return str_replace("ORDER BY crmid DESC", "", $this->query());
	}
	
	function executeCount() {
		global $adb;
		$result = $adb->pquery($this->countQuery(), $this->queryParameters());
		return $adb->num_rows($result);
	}
}
