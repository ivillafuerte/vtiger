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
include_once dirname(__FILE__) . '/FetchAllAlerts.php';

class CTMobile_WS_AlertDetailsWithMessage extends CTMobile_WS_FetchAllAlerts {
	
	function process(CTMobile_API_Request $request) {
		global $current_user;

		$response = new CTMobile_API_Response();

		$alertid = $request->get('alertid');
		$current_user = $this->getActiveUser();

		$alert = $this->getAlertDetails($alertid);
		if(empty($alert)) {
			$response->setError(1401, 'Alert not found');
		} else {
			$result = array();
			$result['alert'] = $this->getAlertDetails($alertid);
			$response->setResult($result);			
		}

		return $response;
	}
	
	function getAlertDetails($alertid) {
		
		$alertModel = CTMobile_WS_AlertModel::modelWithId($alertid);
		
		$alert = false;
		if($alertModel) {
			$alert = $alertModel->serializeToSend();
			
			$alertModel->setUser($this->getActiveUser());
			$alert['message'] = $alertModel->message();
		}
		
		return $alert;
	}
	
}
