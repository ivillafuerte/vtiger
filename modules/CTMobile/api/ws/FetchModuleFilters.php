<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
class CTMobile_WS_FetchModuleFilters extends CTMobile_WS_Controller {
	
	function process(CTMobile_API_Request $request) {
		$response = new CTMobile_API_Response();

		$module = $request->get('module');
		if($module == 'Events'){
			$module = 'Calendar';
		}
		$current_user = $this->getActiveUser();
		
		$result = array();
		
		$filters = $this->getModuleFilters($module, $current_user);
		
		$yours = array();
		$others= array();
		if(!empty($filters)) {
			foreach($filters as $filter) {
				if($filter['userName'] == $current_user->column_fields['user_name']) {
					$yours[] = $filter;
				} else {
					$others[]= $filter;
				}
			}
		}
		$filters = array_merge($yours,$others);
		$filter = $filters;
		foreach($filter as $key => $value){
			$filters[$key]['isDefault'] = 0;
			global $adb;
			$tabId = getTabid($module); 
			$result = $adb->pquery('SELECT default_cvid FROM vtiger_user_module_preferences WHERE userid = ? AND tabid = ?',
				array($current_user->id, $tabId));
			if($adb->num_rows($result) > 0) {
				$cvId = $adb->query_result($result, 0, 'default_cvid');
				if($cvId === $value['cvid']) {
					$filters[$key]['isDefault'] = 1;
				} else {
					$filters[$key]['isDefault'] = 0;
				}
			}
		}
		$results['filters'] = array('yours' => $filters);
		$response->setResult($results);

		return $response;
	}

	protected function getModuleFilters($moduleName, $user) {
		
		$filters = array();
		
		global $adb;
			$sql = "SELECT vtiger_customview.*, vtiger_users.user_name FROM vtiger_customview 
				INNER JOIN vtiger_users ON vtiger_customview.userid = vtiger_users.id WHERE vtiger_customview.entitytype=?";
		$parameters = array($moduleName);

		if(!is_admin($user)) {
			require('user_privileges/user_privileges_'.$user->id.'.php');
			
			$sql .= " AND (vtiger_customview.status=0 or vtiger_customview.userid = ? or vtiger_customview.status = 3 or vtiger_customview.userid IN
			(SELECT vtiger_user2role.userid FROM vtiger_user2role INNER JOIN vtiger_users on vtiger_users.id=vtiger_user2role.userid 
			INNER JOIN vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid WHERE vtiger_role.parentrole LIKE '".$current_user_parent_role_seq."::%'))";
			
			array_push($parameters, $user->id);
		}
		
		$result = $adb->pquery($sql, $parameters);
		if($result && $adb->num_rows($result)) {
			while($resultrow = $adb->fetch_array($result)) {
				$filters[] = $this->prepareFilterDetailUsingResultRow($resultrow, $moduleName, $user);
			}
		}	
		return $filters;
	}
	
	protected function prepareFilterDetailUsingResultRow($resultrow, $moduleName, $user) {
		$filter = array();
		$filter['cvid'] = $resultrow['cvid'];
		$filter['viewname'] = vtranslate(decode_html($resultrow['viewname']), $moduleName, $user->language);
		$filter['setdefault'] = $resultrow['setdefault'];
		$filter['setmetrics'] = $resultrow['setmetrics'];
		$filter['moduleName'] = decode_html($resultrow['entitytype']);
		$filter['status']     = decode_html($resultrow['status']);
		$filter['userName']   = decode_html($resultrow['user_name']);
		return $filter;
	}
}
