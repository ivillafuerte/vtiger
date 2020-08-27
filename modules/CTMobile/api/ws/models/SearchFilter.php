<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once 'include/Webservices/Query.php';
include_once dirname(__FILE__) . '/Filter.php';

class CTMobile_WS_SearchFilterModel extends CTMobile_WS_FilterModel {
	
	protected $criterias;
	
	function __construct($moduleName) {
		$this->moduleName = $moduleName;
	}
	
	function query() {
		return false;
	}
	
	function queryParameters() {
		return false;
	}
	
	function setCriterias($criterias) {
		$this->criterias = $criterias;
	}
	
	function execute($fieldnames, $pagingModel = false, $paging = array(),$orderclause='') {
		$selectClause = sprintf("SELECT %s", implode(',', $fieldnames));
		$fromClause = sprintf("FROM %s", $this->moduleName);
		$whereClause = "";
		if($orderclause){
			$orderClause = $orderclause;
		}else{
			if($this->moduleName == 'Users'){
				if (!empty($this->criterias)) {
					$_sortCriteria = $this->criterias['_sort'];
					if(!empty($_sortCriteria)) {
						$orderClause = $_sortCriteria;
					}
				}
			}else{
				$orderClause = " ORDER BY modifiedtime DESC";
			}
		}
		$groupClause = "";
		//$limitClause = $pagingModel? " LIMIT {$pagingModel->currentCount()},{$pagingModel->limit()}" : "" ;
		
		$index = $paging['index'];
		$size = $paging['size'];
		
		$limit = ($index*$size) - $size;
		$limitClause = " LIMIT $limit, $size"; 
		

		if($index == '' && $size == '') {
			$query = sprintf("%s %s %s %s %s;", $selectClause, $fromClause, $whereClause, $orderClause, $groupClause);
		} else {
			//$orderClause = " ORDER BY modifiedtime DESC"; 
			$query = sprintf("%s %s %s %s %s %s;", $selectClause, $fromClause, $whereClause, $orderClause, $groupClause, $limitClause);
		}
		
		//$query = sprintf("%s %s %s %s %s %s;", $selectClause, $fromClause, $whereClause, $orderClause, $groupClause, $limitClause);
			
		if( $this->moduleName == 'Users')	{
			$userid = 1;
			$this->activeUser = CRMEntity::getInstance('Users');
			$this->activeUser->retrieveCurrentUserInfoFromFile($userid);
			return vtws_query($query, $this->activeUser); 
		}else{
			return vtws_query($query, $this->getUser());
		}	
		 
	}
	
	static function modelWithCriterias($moduleName, $criterias = false) {
		
		$model = new CTMobile_WS_SearchFilterModel($moduleName);
		$model->setCriterias($criterias);
		return $model;
	}
}
