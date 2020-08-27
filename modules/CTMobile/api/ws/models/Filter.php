<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once 'modules/CustomView/CustomView.php';

class CTMobile_WS_FilterModel {
	
	var $filterid, $moduleName;
	var $user;
	protected $customView;
	
	function __construct($moduleName) {
		$this->moduleName = $moduleName;
		$this->customView = new CustomView($moduleName);
	}
	
	function setUser($userInstance) {
		$this->user = $userInstance;
	}
	
	function getUser() {
		return $this->user;
	}
	
	function query() {
		// $listquery = getListQuery($this->moduleName);
		// $query = $this->customView->getModifiedCvListQuery($this->filterid,$listquery,$this->moduleName);
		
		$listViewModel = Vtiger_ListView_Model::getInstance($this->moduleName, $this->filterid);
		$query = $listViewModel->getQuery();
		return $query;
	}
	
	function queryParameters() {
		return false;
	}
	
	static function modelWithId($moduleName, $filterid) {
		$model = new CTMobile_WS_FilterModel($moduleName);
		$model->filterid = $filterid;
		return $model;
	}
	
}
