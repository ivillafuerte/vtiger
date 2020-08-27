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
class CTMobile_WS_AlertModel_ProjectTasksOfMine extends CTMobile_WS_AlertModel {
	function __construct() {
		parent::__construct();
		$this->name = 'My Project Task';
		$this->moduleName = 'ProjectTask';
		$this->refreshRate= 1 * (24* 60 * 60); // 1 day
		$this->description='Project Task Assigned To Me';
	}

	function query() {
		$sql = "SELECT crmid FROM vtiger_crmentity INNER JOIN vtiger_projecttask ON 
                    vtiger_projecttask.projecttaskid=vtiger_crmentity.crmid WHERE vtiger_crmentity.deleted=0 AND vtiger_crmentity.smownerid=? AND
                    vtiger_projecttask.projecttaskprogress <> '100%';";
		return $sql;
	}
        function queryParameters() {
		return array($this->getUser()->id);
	}

	
}

