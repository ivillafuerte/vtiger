<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once dirname(__FILE__) . '/models/Alert.php';
include_once dirname(__FILE__) . '/models/SearchFilter.php';
include_once dirname(__FILE__) . '/models/Paging.php';

class CTMobile_WS_UserData extends CTMobile_WS_Controller {
	
	
	function getSearchFilterModel($module, $search) {
		return CTMobile_WS_SearchFilterModel::modelWithCriterias($module, Zend_JSON::decode($search));
	}
	
	function getPagingModel(CTMobile_API_Request $request) {
		$page = $request->get('page', 0);
		return CTMobile_WS_PagingModel::modelWithPageStart($page);
	}
	
	function process(CTMobile_API_Request $request) {
		global $current_user,$adb, $site_URL;
		$default_charset = VTWS_PreserveGlobal::getGlobal('default_charset');
		$userId = trim($request->get('userid'));
		
		$AttachmentQuery = $adb->pquery("select vtiger_attachments.attachmentsid, vtiger_attachments.name, vtiger_attachments.path FROM vtiger_salesmanattachmentsrel
											INNER JOIN vtiger_attachments ON vtiger_salesmanattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid 
											LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_salesmanattachmentsrel.smid 
											WHERE vtiger_salesmanattachmentsrel.smid = ?", array($userId));
											
		$AttachmentQueryCount = $adb->num_rows($AttachmentQuery);
		
		if($AttachmentQueryCount > 0) {
			$name = $adb->query_result($AttachmentQuery, 0, 'name');
			$path = $adb->query_result($AttachmentQuery, 0, 'path');
			$attachmentsId = $adb->query_result($AttachmentQuery, 0, 'attachmentsid');
			$userImage = $site_URL.$path.$attachmentsId."_".$name;
		}
		
		$userRecordModel = Vtiger_Record_Model::getInstanceById($userId, 'Users');
		$first_name = $userRecordModel->get('first_name');
		$first_name = html_entity_decode($first_name, ENT_QUOTES, $default_charset);
		$last_name = $userRecordModel->get('last_name');
		$last_name = html_entity_decode($last_name, ENT_QUOTES, $default_charset);
		$email = $userRecordModel->get('email1');
		$userData[] = array('userImage'=>$userImage, 'email' => $email, 'userName' => $first_name." ".$last_name);
		
		$response = new CTMobile_API_Response();
		$response->setResult($userData);
		
		return $response;
	}
}
