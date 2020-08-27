<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

class PUSHNotifier {
	/**
	 * Send SMS (Creates SMS Entity record, links it with related CRM record and triggers provider to send sms)
	 *
	 * @param String $message
	 * @param Array $tonumbers
	 * @param Integer $ownerid User id to assign the SMS record
	 * @param mixed $linktoids List of CRM record id to link SMS record
	 * @param String $linktoModule Modulename of CRM record to link with (if not provided lookup it will be calculated)
	 */
	static function sendnotification($message, $tonumbers, $ownerid = false, $linktoids = false, $linktoModule = false, $title = '', $ws_id = '') {
		global $current_user, $adb;

		if($ownerid === false) {
			if(isset($current_user) && !empty($current_user)) {
				$ownerid = $current_user->id;
			} else {
				$ownerid = 1;
			}
		}
		
		foreach ($tonumbers as $userid){
			if($userid!=''){
				$devicekey = self::getdevicekey($userid);
				$moduleName = 'CTPushNotification';
				$focus = CRMEntity::getInstance($moduleName);
				$focus->column_fields['description'] = $message;
				$focus->column_fields['assigned_user_id'] = $userid;
				$focus->column_fields['pn_related'] = $linktoids;
				$focus->column_fields['pushnotificationstatus'] = 'Draft';
				$focus->column_fields['devicekey'] = $devicekey;
				$focus->column_fields['pn_title'] =  $title;
				$focus->save($moduleName);
				if($focus->id != ''){
					$record_id = $focus->id;
					
					$result = self::sendpushnotification($message,$devicekey, $ws_id, $linktoModule, $title);
					
					if($result){
						
						$recordModel = Vtiger_Record_Model::getInstanceById($record_id, $moduleName);
						$modelData = $recordModel->getData();
						$recordModel->set('mode', 'edit');
						$recordModel->set('pushnotification_response', $result);
						$recordModel->set('pushnotificationstatus', 'Send');
						$recordModel->save();
						
						
					}
				}
				
			}
		}	
	}
	
	static function getdevicekey($userid) {
		global $log, $adb;
		
		$perm_qry = "SELECT devicetoken FROM ctmobile_userdevicetoken  WHERE userid = ? ;";
		$perm_result = $adb->pquery($perm_qry, array($userid));
		$perm_rows = $adb->num_rows($perm_result);
		if($perm_rows > 0){
			return  $adb->query_result($perm_result, 0, "devicetoken");
		}
		return '';
	}
	
	static function sendpushnotification($message,$devicekey, $ws_id, $linktoModule, $title) {
		
		 define( 'API_ACCESS_KEY', 'AAAA_kGRtQ8:APA91bEWdbKg2fAycMdQGfhh6wWgdorH8D4J7lmcKq6tLE8RTKFg6_BKOQLNa_-agDsJugMCM3BrhFIPbvNq6EqW2PKO5E6SN-KwFs4RWRNcfl7TWrbNCkFhuaLtVg9F_FTrHal1tn7t' );
       
		
        $fcmMsg = array(
			 'content_available'=> 'true',
        );
        $dataPayload = array('message' => $message, 'title' => $title, 'recordId' => $ws_id , 'moduleName' => $linktoModule);
		
        $fcmFields = array(
            'to' => $devicekey ,
            'priority' => 'high',
            'notification' => $fcmMsg,
            'data' => $dataPayload

        );
		
        $headers = array(
            'Authorization: key=' .API_ACCESS_KEY ,
            'Content-Type: application/json'
        );
         
        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fcmFields ) );
		
        $result = curl_exec($ch );
        curl_close( $ch );
       
		return $result;
	}

	
}
?>
