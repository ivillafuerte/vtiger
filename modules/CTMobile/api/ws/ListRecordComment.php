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

class CTMobile_WS_ListRecordComment extends CTMobile_WS_Controller {
	
	
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
		$record = trim($request->get('record'));
		$index = trim($request->get('index'));
		$size = $request->get('size');
		$limit = ($index*$size) - $size;
		
		$query = "SELECT vtiger_modcomments.*, vtiger_crmentity.createdtime, vtiger_crmentity.smownerid from vtiger_modcomments INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_modcomments.modcommentsid where vtiger_crmentity.deleted = 0 and vtiger_modcomments.related_to = ? ORDER BY vtiger_modcomments.modcommentsid DESC";
		if(!empty($index) && !empty($size)){
			$query .= sprintf(" LIMIT %s, %s", $limit, $size);
		}
		$getCommentQuery = $adb->pquery($query, array($record));
		$countComment = $adb->num_rows($getCommentQuery);
		
		$modcommentsData = array();
		for($i=0;$i<$countComment;$i++) {
			$modcommentId = $adb->query_result($getCommentQuery, $i, 'modcommentsid');
			$commentcontent = $adb->query_result($getCommentQuery, $i, 'commentcontent');
			$relatedTo = $adb->query_result($getCommentQuery, $i, 'related_to');
			
			$userId = $adb->query_result($getCommentQuery, $i, 'smownerid');
			$createdtime = $adb->query_result($getCommentQuery, $i, 'createdtime');
			$commentedtime = Vtiger_Util_Helper::formatDateDiffInStrings($createdtime);
			
			if($userId) {
				$userRecordModel = Vtiger_Record_Model::getInstanceById($userId, 'Users');
				$firstname = $userRecordModel->get('first_name');
				$firstname = html_entity_decode($firstname, ENT_QUOTES, $default_charset);
				$lastname = $userRecordModel->get('last_name');
				$lastname = html_entity_decode($lastname, ENT_QUOTES, $default_charset);
			}
			$modcommentsData[] = array('modcommentId'=>'31x'.$modcommentId, 'commentcontent'=>$commentcontent, 'relatedTo' => $relatedTo, 'userid'=>$userId, 'userName'=>$firstname." ".$lastname, 'createdtime'=>$createdtime,'ModifiedTime'=>$commentedtime);
		}
		$response = new CTMobile_API_Response();
		$response->setResult($modcommentsData);
		
		return $response;
	}
	
	public function getDisplayValue($value) {
		$dateValue = '--';

		if ($value != '') {
			$date = new DateTimeField($value);
			$dateTimeValue = $date->getDisplayDateTimeValue();;
			list($startDate, $startTime) = explode(' ', $dateTimeValue);

			$currentUser = Users_Record_Model::getCurrentUserModel();
			if ($currentUser->get('hour_format') == '12') {
				$startTime = Vtiger_Time_UIType::getTimeValueInAMorPM($startTime);
			}

			$dateValue = "$startDate $startTime";
		}
		return $dateValue;
	}
}
