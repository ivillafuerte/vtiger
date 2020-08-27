<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once 'include/Webservices/Delete.php';

class CTMobile_WS_DeleteRecords extends CTMobile_WS_Controller {
	
	function process(CTMobile_API_Request $request) {
		global $current_user;
		$current_user = $this->getActiveUser();
		$records = trim($request->get('records'));
		if (empty($records)) {
			$records = array($request->get('record'));
		} else {
			$records = Zend_Json::decode($records);
		}
		$deleted = array();
		foreach($records as $record) {
			try {
				vtws_delete($record, $current_user);
				$result = true;
			} catch(Exception $e) {
				$result = false;
			}
			$deleted[$record] = $result;
		}
		$response = new CTMobile_API_Response();
		$response->setResult(array('deleted' => $deleted));
		
		return $response;
	}
}
