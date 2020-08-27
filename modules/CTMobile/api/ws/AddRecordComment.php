<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once dirname(__FILE__) . '/SaveRecord.php';

class CTMobile_WS_AddRecordComment extends CTMobile_WS_SaveRecord {
	
	function process(CTMobile_API_Request $request) {

		$values = Zend_Json::decode($request->get('values'));
		$relatedTo = $values['related_to'];
		$commentContent = $values['commentcontent'];
		$user = $this->getActiveUser();
		$targetModule = 'ModComments';
		$response = false;
		if (vtlib_isModuleActive($targetModule)) {
			$request->set('module', $targetModule);
			$values['assigned_user_id'] = sprintf('%sx%s', CTMobile_WS_Utils::getEntityModuleWSId('Users'), $user->id);
			
			$request->set('values', Zend_Json::encode($values) );
			
			$response = parent::process($request);
		}
		return $response;
	}
}
