<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
header('Content-Type: text/json');

chdir (dirname(__FILE__) . '/../../');

/**
 * URL Verfication - Required to overcome Apache mis-configuration and leading to shared setup mode.
 */
require_once 'config.php';
if (file_exists('config_override.php')) {
    include_once 'config_override.php';
}

// Define GetRelatedList API before including the core files
// NOTE: Make sure GetRelatedList function_exists check is made in include/utils/RelatedListView.php
include_once dirname(__FILE__) . '/api/Relation.php';

include_once dirname(__FILE__) . '/api/Request.php';
include_once dirname(__FILE__) . '/api/Response.php';
include_once dirname(__FILE__) . '/api/Session.php';

include_once dirname(__FILE__) . '/api/ws/Controller.php';
require_once 'includes/main/WebUI.php';

class CTMobile_API_Controller {

	static $opControllers = array(
		'login'                   => array('file' => '/api/ws/Login.php', 'class' => 'CTMobile_WS_Login'),
		'loginAndFetchModules'    => array('file' => '/api/ws/LoginAndFetchModules.php', 'class' => 'CTMobile_WS_LoginAndFetchModules'),
		'fetchModuleFilters'      => array('file' => '/api/ws/FetchModuleFilters.php'  , 'class' => 'CTMobile_WS_FetchModuleFilters'),
		'filterDetailsWithCount'  => array('file' => '/api/ws/FilterDetailsWithCount.php', 'class' => 'CTMobile_WS_FilterDetailsWithCount'),
		'fetchAllAlerts'          => array('file' => '/api/ws/FetchAllAlerts.php', 'class' => 'CTMobile_WS_FetchAllAlerts'),
		'alertDetailsWithMessage' => array('file' => '/api/ws/AlertDetailsWithMessage.php', 'class' => 'CTMobile_WS_AlertDetailsWithMessage'),
		'listModuleRecords'       => array('file' => '/api/ws/ListModuleRecords.php', 'class' => 'CTMobile_WS_ListModuleRecords'),
		'fetchRecord'             => array('file' => '/api/ws/FetchRecord.php', 'class' => 'CTMobile_WS_FetchRecord'),
		'fetchRecordWithGrouping' => array('file' => '/api/ws/FetchRecordWithGrouping.php', 'class' => 'CTMobile_WS_FetchRecordWithGrouping'),
		'fetchRecordsWithGrouping' => array('file' => '/api/ws/FetchRecordsWithGrouping.php', 'class' => 'CTMobile_WS_FetchRecordsWithGrouping'),
		'describe'                => array('file' => '/api/ws/Describe.php', 'class' => 'CTMobile_WS_Describe'),
		'saveRecord'              => array('file' => '/api/ws/SaveRecord.php', 'class' => 'CTMobile_WS_SaveRecord'),
		'syncModuleRecords'       => array('file' => '/api/ws/SyncModuleRecords.php', 'class' => 'CTMobile_WS_SyncModuleRecords'),

		'query'                   => array('file' => '/api/ws/Query.php', 'class' => 'CTMobile_WS_Query'),
		'queryWithGrouping'       => array('file' => '/api/ws/QueryWithGrouping.php', 'class' => 'CTMobile_WS_QueryWithGrouping'),

		'relatedRecordsWithGrouping' => array('file' => '/api/ws/RelatedRecordsWithGrouping.php', 'class' => 'CTMobile_WS_RelatedRecordsWithGrouping'),
		'deleteRecords'              => array('file' => '/api/ws/DeleteRecords.php', 'class' => 'CTMobile_WS_DeleteRecords'),

		'addRecordComment'           => array('file' => '/api/ws/AddRecordComment.php', 'class' => 'CTMobile_WS_AddRecordComment'),
		'history'                    => array('file' => '/api/ws/History.php', 'class' => 'CTMobile_WS_History'),
                'taxByType'                  => array('file'=>'/api/ws/TaxByType.php','class'=>'CTMobile_WS_TaxByType'),
                'fetchModuleOwners'          => array('file' => '/api/ws/FetchModuleOwners.php', 'class'=>'CTMobile_WS_FetchModuleOwners'),
                'relatedModule'          => array('file' => '/api/ws/RelatedModule.php', 'class'=>'CTMobile_WS_RelatedModule'),
                'relatedModuleList'          => array('file' => '/api/ws/RelatedModuleList.php', 'class'=>'CTMobile_WS_RelatedModuleList'),
                'recentEvent' => array('file' => '/api/ws/RecentEvent.php', 'class'=>'CTMobile_WS_RecentEvent'),
                'userData'          => array('file' => '/api/ws/UserData.php', 'class'=>'CTMobile_WS_UserData'),
                'listRecordComment' => array('file' => '/api/ws/ListRecordComment.php', 'class' => 'CTMobile_WS_ListRecordComment'),
                'getNearestPlace' => array('file' => '/api/ws/GetNearestPlace.php', 'class'=>'CTMobile_WS_GetNearestPlace'),
                'searchRecord' => array('file' => '/api/ws/SearchRecord.php', 'class'=>'CTMobile_WS_SearchRecord'),
                'updateLocation' => array('file'=>'/api/ws/UpdateLocation.php', 'class'=>'CTMobile_WS_UpdateLocation'),
				'attendance' => array('file'=>'/api/ws/Attendance.php', 'class'=>'CTMobile_WS_Attendance'),
				'attendanceUserStatus' => array('file'=>'/api/ws/AttendanceUserStatus.php', 'class'=>'CTMobile_WS_AttendanceUserStatus'),
				'attendanceUserHours' => array('file'=>'/api/ws/AttendanceUserHours.php', 'class'=>'CTMobile_WS_AttendanceUserHours'),
                'getAllUserLocation' => array('file'=>'/api/ws/GetAllUserLocation.php', 'class'=>'CTMobile_WS_GetAllUserLocation'),
				'GetUserRoute' => array('file'=>'/api/ws/GetUserRoute.php', 'class'=>'CTMobile_WS_GetUserRoute'),
				'GetRouteUserList' => array('file'=>'/api/ws/GetRouteUserList.php', 'class'=>'CTMobile_WS_GetRouteUserList'),
				'GetEventList' => array('file'=>'/api/ws/GetEventList.php', 'class'=>'CTMobile_WS_GetEventList'),
				'GetDateBaseEventList' => array('file'=>'/api/ws/GetDateBaseEventList.php', 'class'=>'CTMobile_WS_GetDateBaseEventList'),
				'GetMonthBaseEventCount' => array('file'=>'/api/ws/GetMonthBaseEventCount.php', 'class'=>'CTMobile_WS_GetMonthBaseEventCount'),
				
				'GetFolderList'          => array('file' => '/api/ws/GetFolderList.php',          'class' => 'CTMobile_WS_GetFolderList'),
				'FetchFolderEmails'          => array('file' => '/api/ws/FetchFolderEmails.php',          'class' => 'CTMobile_WS_FetchFolderEmails'),
				'FetchEmailById'				=> array('file' => '/api/ws/FetchEmailById.php',             'class' => 'CTMobile_WS_FetchEmailById'),
				'AttachedEmail'			=> array('file' => '/api/ws/AttachedEmail.php',		'class' => 'CTMobile_WS_AttachedEmail'),
				'SearchEmail'			=> array('file' => '/api/ws/SearchEmail.php',		'class' => 'CTMobile_WS_SearchEmail'),
				'MarkAsUnreadEmail'			=> array('file' => '/api/ws/MarkAsUnreadEmail.php',		'class' => 'CTMobile_WS_MarkAsUnreadEmail'),
				'DeleteEmail'			=> array('file' => '/api/ws/DeleteEmail.php',		'class' => 'CTMobile_WS_DeleteEmail'),
				'MoveEmail'			=> array('file' => '/api/ws/MoveEmail.php',		'class' => 'CTMobile_WS_MoveEmail'),
				'EmailAction'			=> array('file' => '/api/ws/EmailAction.php',		'class' => 'CTMobile_WS_EmailAction'),
				'AddAttachmentByid'			=> array('file' => '/api/ws/AddAttachmentByid.php',		'class' => 'CTMobile_WS_AddAttachmentByid'),
				'FetchCommentModules'			=> array('file' => '/api/ws/FetchCommentModules.php',		'class' => 'CTMobile_WS_FetchCommentModules'),
				'SendFeedback'			=> array('file' => '/api/ws/SendFeedback.php',		'class' => 'CTMobile_WS_SendFeedback'),
				
				 'getMessageTemplate' => array('file'=>'/api/ws/GetMessageTemplate.php', 'class'=>'CTMobile_WS_GetMessageTemplate'),
                'sendEmail' => array('file' =>'/api/ws/SendEmail.php', 'class' =>'CTMobile_WS_SendEmail'),
                'ReplyEmail' => array('file' =>'/api/ws/ReplyEmail.php', 'class' =>'CTMobile_WS_ReplyEmail'),
				'sendSMS' => array('file' => '/api/ws/SendSMS.php', 'class' => 'CTMobile_WS_SendSMS'),
				'saveMultipleRecord'              => array('file' => '/api/ws/SaveMultipleRecord.php', 'class' => 'CTMobile_WS_SaveMultipleRecord'),
			    'RelatedRecords'              => array('file' => '/api/ws/RelatedRecords.php', 'class' => 'CTMobile_WS_RelatedRecords'),
				'DependencyAddress'              => array('file' => '/api/ws/DependencyAddress.php', 'class' => 'CTMobile_WS_DependencyAddress'),
				'forgotPassword' => array('file' => '/api/ws/ForgotPassword.php', 'class' => 'CTMobile_WS_ForgotPassword'),
				'updatePendingShift' => array('file' => '/api/ws/UpdatePendingShift.php', 'class' => 'CTMobile_WS_UpdatePendingShift'),
				'Upgrade' => array('file' => '/api/ws/Upgrade.php', 'class' => 'CTMobile_WS_Upgrade'),
				'SaveUserImage' => array('file' => '/api/ws/SaveUserImage.php', 'class' => 'CTMobile_WS_SaveUserImage'),
				'NearbyStatus'=> array('file' => '/api/ws/NearbyStatus.php', 'class' => 'CTMobile_WS_NearbyStatus'),
				'CheckOutgoingServer'=> array('file' => '/api/ws/CheckOutgoingServer.php', 'class' => 'CTMobile_WS_CheckOutgoingServer'),
				'logout'=> array('file' => '/api/ws/Logout.php', 'class' => 'CTMobile_WS_Logout'),
				'globalSearch'=> array('file' => '/api/ws/GlobalSearch.php', 'class' => 'CTMobile_WS_GlobalSearch'),
	);

	static function process(CTMobile_API_Request $request) {
		$operation = $request->getOperation();
		$sessionid = $request->getSession();

		$response = false;
		if(isset(self::$opControllers[$operation])) {

			$operationFile = self::$opControllers[$operation]['file'];
			$operationClass= self::$opControllers[$operation]['class'];

			include_once dirname(__FILE__) . $operationFile;
			$operationController = new $operationClass;

			$operationSession = false;
			if($operationController->requireLogin()) {
				$operationSession = CTMobile_API_Session::init($sessionid);
				if($operationController->hasActiveUser() === false) {
					$operationSession = false;
				}
				//CTMobile_WS_Utils::initAppGlobals();
			} else {
				// By-pass login
				$operationSession = true;
			}

			if($operationSession === false && $operation != 'forgotPassword') {
				$response = new CTMobile_API_Response();
				$response->setError(1501, 'Login required');
			} else {

				try {
					$response = $operationController->process($request);
				} catch(Exception $e) {
					$response = new CTMobile_API_Response();
					$response->setError($e->getCode(), $e->getMessage());
				}
			}

		} else {
			$response = new CTMobile_API_Response();
			$response->setError(1404, 'Operation not found: ' . $operation);
		}

		if($response !== false) {
			echo $response->emitJSON();
		}
	}
}

/** Take care of stripping the slashes */
function stripslashes_recursive($value) {
       $value = is_array($value) ? array_map('stripslashes_recursive', $value) : stripslashes($value);
       return $value;
}
/** END **/

if(!defined('MOBILE_API_CONTROLLER_AVOID_TRIGGER')) {
	$clientRequestValues = $_POST; // $_REQUEST or $_GET

	$clientRequestValuesRaw = array();

	// Set of request key few controllers are interested in raw values (example, SaveRecord)
	/*$rawValueHeaders = array('values');
	foreach($rawValueHeaders as $rawValueHeader) {
		if(isset($clientRequestValues[$rawValueHeader])) {
			$clientRequestValuesRaw[$rawValueHeader] = $clientRequestValues[$rawValueHeader];
		}
	}*/
	// END

	if (get_magic_quotes_gpc()) {
	    $clientRequestValues = stripslashes_recursive($clientRequestValues);
	}
	CTMobile_API_Controller::process(new CTMobile_API_Request($clientRequestValues, $clientRequestValuesRaw));
}
