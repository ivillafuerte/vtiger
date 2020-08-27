<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

require_once 'modules/Emails/class.phpmailer.php';
require_once 'modules/Emails/mail.php';   
class CTMobile_WS_SendEmail extends CTMobile_WS_Controller {
	
	function getFromEmailAddress() {
		$db = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$fromEmail = false;
		$result = $db->pquery('SELECT email1 FROM vtiger_users WHERE is_admin=?', array('on'));
		if ($db->num_rows($result)) {
			$fromEmail = decode_html($db->query_result($result, 0, 'email1'));
		}
		if (empty($fromEmail)) $fromEmail = $currentUserModel->get('email1');
		return $fromEmail;
	}
	

	function process(CTMobile_API_Request $request) {
		//image code
		$moduleName = trim($request->get('module'));
		$recordid = trim($request->get('record'));
		$record = explode('x', $recordid);
		$toEmailInfo = $request->get('to');
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$first_name = $currentUserModel->get('first_name');
		$last_name = $currentUserModel->get('last_name');
		$fromName = $first_name.' '.$last_name;
		$valuesJSONString =  $request->get('values');
		
		if ($valuesJSONString) {
			$values = Zend_Json::decode($valuesJSONString);
			$data=$values['imagebase64'];
			$data = str_replace(' ', '+', $data);
			$imagename = $values['imagename'];
			$imagetypeexplode = explode('.', $imagename);
			$imagetype = $imagetypeexplode[1];
		}


		$body = $request->get('body');
		$subject = trim($request->get('subject'));
		$fromEmail = $this->getFromEmailAddress();

		global $root_directory, $adb, $current_user;
		$current_user = $this->getActiveUser();
		$current_user_id = $current_user->id;
		$crm_id = $adb->getUniqueID("vtiger_crmentity");
	    $lastInsertedId = $adb->pquery("select crmid from vtiger_crmentity order by crmid DESC limit 0,1");
     	$Unique_id = $adb->query_result($lastInsertedId, 0, 'crmid');

		if(isset($imagename) && !empty($imagename)) {
			$filepath = 'storage/';
			$year  = date('Y');
			$month = date('F');
			$day   = date('j');
			$week  = '';
			
			if (!is_dir($root_directory.$filepath . $year)) {
				 mkdir($root_directory.$filepath . $year);
				 chmod($root_directory.$filepath . $year, 0777);
			} 
			
			if (!is_dir($root_directory.$filepath . $year . "/" . $month)) {
				  mkdir($root_directory.$filepath . "$year/$month");
				  chmod($root_directory.$filepath . "$year/$month", 0777);
			}
		
			if ($day > 0 && $day <= 7)
				$week = 'week1';
			elseif ($day > 7 && $day <= 14)
				$week = 'week2';
			elseif ($day > 14 && $day <= 21)
				$week = 'week3';
			elseif ($day > 21 && $day <= 28)
				$week = 'week4';
			else
				$week = 'week5'; 
			 
			if (!is_dir($root_directory.$filepath . $year . "/" . $month . "/" . $week)) {
				  mkdir($root_directory.$filepath . "$year/$month/$week");
				  chmod($root_directory.$filepath . "$year/$month/$week", 0777);
			}
			
			$path = $filepath. "$year/$month/$week/";
			if(basename($imagename != '')) {
				$createdtime =  date('Y-m-d h:i:s');
				$modifiedtime = date('Y-m-d h:i:s');
				$uniquecrmid = $Unique_id+1;

				$crmentity_query = "insert into vtiger_crmentity(crmid,smcreatorid,smownerid,modifiedby,setype,description,presence,createdtime,modifiedtime,label)values ('$uniquecrmid','$current_user_id','$current_user_id','$current_user_id','Emails','$body','1', '$createdtime','$modifiedtime','$body')";
				$crmentity_query_result = $adb->pquery($crmentity_query,array());

				$emaildetails = "INSERT into vtiger_emaildetails (emailid,from_email,to_email,cc_email,bcc_email,assigned_user_email,idlists) values ('$uniquecrmid','$fromEmail','$toEmailInfo','','','','$record[1]@$current_user_id|')";
				$emaildetail_result = $adb->pquery($emaildetails,array());

				$activitysql = "insert into vtiger_activity (activityid,subject,activitytype,visibility) values ('$uniquecrmid','$subject','Emails','all')";
				$activity_result = $adb->pquery($activitysql,array());

				$seactivityrel = "insert into vtiger_seactivityrel (crmid,activityid) values ('$record[1]','$uniquecrmid')";
				$seactivity_result = $adb->pquery($seactivityrel,array());

				$mailtrackid = "INSERT INTO vtiger_email_track(crmid, mailid,  access_count) VALUES('$record[1]','$uniquecrmid','0')";
				$mailtrack_result = $adb->pquery($mailtrackid,array());

				$updatecrmid_seq = "update vtiger_crmentity_seq set id='$uniquecrmid'";
				$updateresult_seq =  $adb->pquery($updatecrmid_seq,array());
				
				$updatedcrmid = $uniquecrmid+1;
				$attachment_crmentity_query = "INSERT INTO vtiger_crmentity(crmid, smcreatorid, smownerid, modifiedby, setype,description, presence, createdtime, modifiedtime,label) values('$updatedcrmid','$current_user_id','$current_user_id','$current_user_id','Emails Attachments','$body','1','$createdtime','$modifiedtime','$body')";
				$attachment_crmentity_query_result = $adb->pquery($attachment_crmentity_query,array());

				$updatecrmid_seqafter_attachment = "update vtiger_crmentity_seq set id='$updatedcrmid'";
				$updateresultafter_attachment_seq =  $adb->pquery($updatecrmid_seqafter_attachment,array());

				$attachment_query = "INSERT INTO vtiger_attachments(attachmentsid,name,description,type, path) values ('$updatedcrmid','$imagename','$body','image/$imagetype[1]','$path')";
				$attachment_query_result = $adb->pquery($attachment_query,array());

				$target_file = $root_directory.  $filepath. "$year/$month/$week/" .$updatedcrmid."_".basename($imagename);
				if (file_put_contents($target_file, base64_decode($data))) {
					chmod($target_file, 0777);
					$seattachment_query = "INSERT INTO vtiger_seattachmentsrel(crmid, attachmentsid) values('$uniquecrmid','$updatedcrmid')";
					$seattachment_query_result = $adb->pquery($seattachment_query,array());
					$status = send_mail($moduleName, $toEmailInfo, $fromName, $fromEmail, $subject, $body,'','','all',$uniquecrmid,'',true);
					 if($status != 1) {
						$result = array('code' => 0,'message' => 'Mail not sent to Client');
					 }else{
						$result = array('code' => 1,'message' => 'Email  Sent Successfully');
						$updatecrmid = "update vtiger_emaildetails set email_flag='SENT' where emailid='".$uniquecrmid."' ";
						$updateresult =  $adb->pquery($updatecrmid,array());
					 }
				}
			}
		}
		else{
			$createdtime =  date('Y-m-d h:i:s');
			$modifiedtime = date('Y-m-d h:i:s');
			$uniquecrmid = $Unique_id+1;

			$crmentity_query = "insert into vtiger_crmentity(crmid,smcreatorid,smownerid,modifiedby,setype,description,presence,createdtime,modifiedtime,label)values ('$uniquecrmid','$current_user_id','$current_user_id','$current_user_id','Emails','$body','1', '$createdtime','$modifiedtime','$body')";
			$crmentity_query_result = $adb->pquery($crmentity_query,array());

			$emaildetails = "INSERT into vtiger_emaildetails (emailid,from_email,to_email,cc_email,bcc_email,assigned_user_email,idlists) values ('$uniquecrmid','$fromEmail','$toEmailInfo','','','','$record[1]@$current_user_id|')";
			$emaildetail_result = $adb->pquery($emaildetails,array());

			$activitysql = "insert into vtiger_activity (activityid,subject,activitytype,visibility) values ('$uniquecrmid','$subject','Emails','all')";
			$activity_result = $adb->pquery($activitysql,array());

			$seactivityrel = "insert into vtiger_seactivityrel (crmid,activityid) values ('$record[1]','$uniquecrmid')";
			$seactivity_result = $adb->pquery($seactivityrel,array());

			$status = send_mail($moduleName, $toEmailInfo, $fromName, $fromEmail,$subject, $body,'','','all',$uniquecrmid,'',true);
			 if($status != 1) {
				$result = array('code' => 0,'message' => 'Mail not sent to Client');
			 }else{
				$result = array('code' => 1,'message' => 'Email  Sent Successfully');
				$updatecrmid = "update vtiger_emaildetails set email_flag='SENT' where emailid='".$uniquecrmid."' ";
				$updateresult =  $adb->pquery($updatecrmid,array());
			 }
		}
		$response = new CTMobile_API_Response();
		$response->setResult($result);
		return $response;
	}
}
		
