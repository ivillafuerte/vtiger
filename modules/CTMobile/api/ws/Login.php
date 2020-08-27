<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
class CTMobile_WS_Login extends CTMobile_WS_Controller {

	function requireLogin() {
		return false;
	}

	function process(CTMobile_API_Request $request) {
		$response = new CTMobile_API_Response();

		$username = trim($request->get('username'));
		$password = trim($request->get('password'));

		$current_user = CRMEntity::getInstance('Users');
		$current_user->column_fields['user_name'] = $username;

		

		if(!$current_user->doLogin($password)) {

			$response->setError(1210, 'Authentication Failed');

		} else {
			
			// Start session now
			$sessionid = CTMobile_API_Session::init();

			if($sessionid === false) {
				echo "Session init failed $sessionid\n";
			}

			$current_user->id = $current_user->retrieve_user_id($username);
			$current_user->retrieveCurrentUserInfoFromFile($current_user->id);
			$this->setActiveUser($current_user);
			$theme = $current_user->theme_config;
			
			if($theme == 'RTL'){	
				$theme = true;
			} else if($theme == 'LTR') {
				$theme = false;
			}else{
				$theme = $current_user->theme;
				$explode_theme = explode('_',$theme);
			
				if(isset($explode_theme[1]) && $explode_theme[1] == 'rtl') {
					$theme = true;
				}else if(isset($explode_theme[1]) && $explode_theme[1] == 'ltr'){
					$theme = false;
				}else{
					$theme = false;
				}
			}

			$device_key = $request->get('device_key');
			if($device_key!='' && $current_user->id != ''){
				global $adb;
				$userId = $current_user->id;
				$selectQuery = $adb->pquery("SELECT * FROM ctmobile_userdevicetoken where userid = ?", array($userId));								
				$selectQueryCount = $adb->num_rows($selectQuery);
				
				if($selectQueryCount > 0) {
					 $query = $adb->pquery("UPDATE ctmobile_userdevicetoken SET devicetoken = ? WHERE userid = ?", array($device_key, $userId));
					
				} else {
					$query = $adb->pquery("INSERT INTO ctmobile_userdevicetoken (userid, devicetoken, longitude, latitude) VALUES (?,?,?,?)", array($userId, $device_key, '0', '0'));
				}
			}
		
			$userId = $current_user->id;
			if($userId!=''){
				global $adb, $site_URL;
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
					
					$first_name = $current_user->first_name;
					$last_name = $current_user->last_name;
					
			}
			
			$moduleModel = Vtiger_Module_Model::getInstance('CTMobile');
			if($moduleModel->get('presence') != 0){
				$response->setError('', 'Please Enable CTMobile Module');
				return $response;	
			}
			
			$version=$adb->pquery("SELECT * FROM vtiger_tab where name='CTMobileSettings'",array());
			$mobile_web_version = $adb->query_result($version,0,'version');
			
			//for livetracking access to user
			$liveuserQuery = $adb->pquery("SELECT 1 FROM ctmobile_livetracking_users WHERE userid = ?",array($current_user->id));
			if($adb->num_rows($liveuserQuery) > 0){
				$livetracking = true;
			}else{
				$livetracking = false;
			}
			//for ctmobile access to user
			$ctmobileAccessQuery = $adb->pquery("SELECT 1 FROM ctmobile_access_users WHERE userid = ?",array($current_user->id));
			if($adb->num_rows($ctmobileAccessQuery) > 0){
				$ctmobileAccess = true;
			}else{
				$ctmobileAccess = false;
			}
			
			$user_type = '';
			$expirydate = '';
			//for ctmobile usertype and expirydate
			$ctlicenseQuery = $adb->pquery("SELECT expirydate,user_type FROM ctmobile_license_settings",array());
			if($adb->num_rows($ctlicenseQuery) > 0){
				$user_type = $adb->query_result($ctlicenseQuery,0,'expirydate');
				$expirydate = $adb->query_result($ctlicenseQuery,0,'user_type');
			}
			
			$resultApi = $adb->pquery("SELECT * FROM ctmobile_api_settings",array());
			$api_key = $adb->query_result($resultApi,0,'api_key');
			global $default_module;
			$result = array();
			$result['login'] = array(
				'userImage'=>$userImage,
				'userName' => $first_name." ".$last_name,
				'userid' => $current_user->id,
				'is_admin'=>$current_user->is_owner,
				'crm_tz' => DateTimeField::getDBTimeZone(),
				'user_tz' => $current_user->time_zone,
                'user_currency' => $current_user->currency_code,
                'rtl_theme' => $theme,
                'language' => $current_user->language,
				'session'=> $sessionid,
				'due_date' => $due_date,
				'vtiger_version' => CTMobile_WS_Utils::getVtigerVersion(),
                'date_format' => $current_user->date_format, 
				'mobile_module_version' => CTMobile_WS_Utils::getVersion(),
				'hour_format'=>$current_user->hour_format,
				'default_module'=>$default_module,
				'default_module_label'=>vtranslate($default_module,$default_module),
				'mobile_web_version'=>$mobile_web_version,
				'api_key'=>$api_key,
				'livetracking'=>$livetracking,
				'ctmobile_access_user' => $ctmobileAccess,
				'user_type'=>$user_type,
				'expirydate'=>$expirydate
			);
			$response->setResult($result);

			$this->postProcess($response);
		
		}
		return $response;
	}

	function postProcess(CTMobile_API_Response $response) {
		return $response;
	}
}
