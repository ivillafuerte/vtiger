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

/** Upcoming Opportunity */
class CTMobile_WS_AlertModel_PotentialsDueIn5Days extends CTMobile_WS_AlertModel {
	function __construct() {
		parent::__construct();
		$this->name = 'Upcoming Opportunity';
		$this->moduleName = 'Potentials';
		$this->refreshRate= 1 * (24 * 60 * 60); // 1 day
		$this->description='Alert sent when Potential Close Date is due before 5 days or less';
	}
	
	function query() {
		$sql = CTMobile_WS_Utils::getModuleListQuery('Potentials', 
					"vtiger_potential.sales_stage not like 'Closed%' AND 
					DATEDIFF(vtiger_potential.closingdate, CURDATE()) <= 5"
				);
		return preg_replace("/^SELECT count\(\*\) as count(.*)/i", "SELECT crmid $1", Vtiger_Functions::mkCountQuery($sql));
	}
	
	function queryParameters() {
		return array();
	}
}
