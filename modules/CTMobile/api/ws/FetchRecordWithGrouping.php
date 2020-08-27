<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once 'include/Webservices/Retrieve.php';
include_once dirname(__FILE__) . '/FetchRecord.php';
include_once 'include/Webservices/DescribeObject.php';

class CTMobile_WS_FetchRecordWithGrouping extends CTMobile_WS_FetchRecord {
	
	private $_cachedDescribeInfo = false;
	private $_cachedDescribeFieldInfo = false;
	
	protected function cacheDescribeInfo($describeInfo) {
		$this->_cachedDescribeInfo = $describeInfo;
		$this->_cachedDescribeFieldInfo = array();
		if(!empty($describeInfo['fields'])) {
			foreach($describeInfo['fields'] as $describeFieldInfo) {
				$this->_cachedDescribeFieldInfo[$describeFieldInfo['name']] = $describeFieldInfo;
			}
		}
	}
	
	protected function cachedDescribeInfo() {
		return $this->_cachedDescribeInfo;
	}
	
	protected function cachedDescribeFieldInfo($fieldname) {
		if ($this->_cachedDescribeFieldInfo !== false) {
			if(isset($this->_cachedDescribeFieldInfo[$fieldname])) {
				return $this->_cachedDescribeFieldInfo[$fieldname];
			}
		}
		return false;
	}
	
	protected function cachedEntityFieldnames($module) {
		$describeInfo = $this->cachedDescribeInfo();
		$labelFields = $describeInfo['labelFields'];
		switch($module) {
			case 'HelpDesk': $labelFields = 'ticket_title'; break;
			case 'Documents': $labelFields = 'notes_title'; break;
		}
		return explode(',', $labelFields);
	}
	
	protected function isTemplateRecordRequest(CTMobile_API_Request $request) {
		$recordid = $request->get('record');
		return (preg_match("/([0-9]+)x0/", $recordid));
	}
	
	protected function processRetrieve(CTMobile_API_Request $request) {
		$recordid = $request->get('record');

		// Create a template record for use 
		if ($this->isTemplateRecordRequest($request)) {
			global $current_user;
			$current_user = $this->getActiveUser();
			
			$module = $this->detectModuleName($recordid);
		 	$describeInfo = vtws_describe($module, $current_user);
		 	CTMobile_WS_Utils::fixDescribeFieldInfo($module, $describeInfo);

		 	$this->cacheDescribeInfo($describeInfo);

			$templateRecord = array();
			foreach($describeInfo['fields'] as $describeField) {
				$templateFieldValue = '';
				if (isset($describeField['type']) && isset($describeField['type']['defaultValue'])) {
					$templateFieldValue = trim($describeField['type']['defaultValue']);
				} else if (isset($describeField['default'])) {
					$templateFieldValue = trim($describeField['default']);
				}
				$templateRecord[$describeField['name']] = $templateFieldValue;
			}
			if (isset($templateRecord['assigned_user_id'])) {
				$templateRecord['assigned_user_id'] = sprintf("%sx%s", CTMobile_WS_Utils::getEntityModuleWSId('Users'), $current_user->id);
			} 
			// Reset the record id
			$templateRecord['id'] = $recordid;
			
			return $templateRecord;
		}
		
		// Or else delgate the action to parent
		return parent::processRetrieve($request);
	}
	
	function process(CTMobile_API_Request $request) {
		$recordid = trim($request->get('record'));
		$module = $this->detectModuleName($recordid);
		global $adb;
		if($module == 'Calendar' || $module == 'Events'){
			$calendarmodule = explode('x', $request->get('record'));
			$activityid = $calendarmodule[1];
			$EventTaskQuery = $adb->pquery("SELECT * FROM  `vtiger_activity` WHERE activitytype = ? AND activityid = ?",array('Task',$activityid)); 
		    if($adb->num_rows($EventTaskQuery) > 0){
				$wsid = CTMobile_WS_Utils::getEntityModuleWSId('Calendar');
				$recordid = $wsid.'x'.$activityid;
				$recordModule = 'Calendar';
			}else{
				$wsid = CTMobile_WS_Utils::getEntityModuleWSId('Events');
				$recordid = $wsid.'x'.$activityid;
				$recordModule = 'Events';
			}
			$request->set('record',$recordid);
		}
		$response = parent::process($request);
		
		return $this->processWithGrouping($request, $response);
	}
	
	protected function processWithGrouping(CTMobile_API_Request $request, $response) {
		$isTemplateRecord = $this->isTemplateRecordRequest($request);
		$result = $response->getResult();
		
		$resultRecord = $result['record'];
		$module = $this->detectModuleName($resultRecord['id']);
		if($module == 'Emails'){
			$resultRecord['recordLabel'] = trim($resultRecord['subject']);
		}
		
		$modifiedRecord = $this->transformRecordWithGrouping($resultRecord, $module, $isTemplateRecord);
		$response->setResult(array('record' => $modifiedRecord));
		
		return $response;
	}
	
	protected function transformRecordWithGrouping($resultRecord, $module, $isTemplateRecord=false) {
		$default_charset = VTWS_PreserveGlobal::getGlobal('default_charset');
		global $adb,$current_user,$site_URL;
		$current_user = $this->getActiveUser();
		$moduleFieldGroups = CTMobile_WS_Utils::gatherModuleFieldGroupInfo($module);
		$recordid = explode("x",$resultRecord['id']);
		$modifiedResult = array();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$duplicateAction = $moduleModel->isDuplicateOptionAllowed('CreateView', $recordid[1]);
		//$userPrivModel = Users_Privileges_Model::getInstanceById($current_user->id);
		$editAction = Users_Privileges_Model::isPermitted($module, 'EditView', $recordid[1]);
		
		$deleteAction = Users_Privileges_Model::isPermitted($module, 'Delete', $recordid[1]);
		$ModulesArray = array('SMSNotifier','PBXManager','CTPushNotification','CTCalllog','CTAttendance','Users');
		if(in_array($module,$ModulesArray)){
			$editAction = false;
			$deleteAction = false;
		}
		
		if($module == 'Documents' || $module == 'Emails'){
			$editAction = false;
		}
		if($module == 'Emails'){
			$deleteAction = false;
		}
		$modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
		$commentModuleAccess = $modCommentsModel->isPermitted('CreateView');
		$ActivityModuleModel = Vtiger_Module_Model::getInstance('Calendar');
		$ActivityModuleAccess = $ActivityModuleModel->isPermitted('CreateView');
		
		$fieldModels = $moduleModel->getFields();
		$blocks = array(); $labelFields = false;
		if(array_key_exists('filename',$resultRecord)){
		}else{
			$query = "SELECT * FROM  `vtiger_notes` WHERE notesid = ?";
			$result = $adb->pquery($query,array($recordid[1]));
			$filename = $adb->query_result($result,0,'filename');
			$resultRecord['filename'] = $filename;
		}
		foreach($moduleFieldGroups as $blocklabel => $fieldgroups) {
			$fields = array();
			/* Start: Added by Vijay Bhavsar */
			$query = "SELECT * FROM vtiger_smsnotifier_servers WHERE isactive='1'";
			$result = $adb->pquery($query,array());
			$totalRecords = $adb->num_rows($result);

			foreach($fieldgroups as $fieldname => $fieldinfo) {
				
				// Pickup field if its part of the result
				if(isset($resultRecord[$fieldname])) {
					$fieldModel = $fieldModels[$fieldname];
					$displayType = $fieldModel->get('displaytype');
					$uitypes = $fieldModel->get('uitype');
					$allowedFields = array('productid','time_start','time_end');
					$restrictedDisplayTypes = array(1,2);
					
					if(!in_array($displayType,$restrictedDisplayTypes) && !in_array($fieldname,$allowedFields)){
						continue;
					}
					$typeofdataArray = array('N~O','N~M','NN~O','NN~M');
					if(($fieldinfo['uitype'] == 72 || $fieldinfo['uitype'] == 1) && in_array($fieldinfo['typeofdata'],$typeofdataArray)) {
						$recordModel = Vtiger_Record_Model::getInstanceById($recordid[1],$module);
						$value = $fieldModel->getDisplayValue($resultRecord[$fieldname], $recordid[1], $recordModel);
						$field = array(
							'name'  => $fieldname,
							'value' => $value,
							'label' => $fieldinfo['label'],
							'uitype'=> $fieldinfo['uitype'],
							'summaryfield' => $fieldinfo['summaryfield'],
							'typeofdata' => $fieldinfo['typeofdata']
						);
					} else {
						if($fieldinfo['uitype'] == 33){
							$value = explode(' |##| ', $resultRecord[$fieldname]);
							$values = '';
							foreach($value as $key => $v){
								if($key+1 == count($value)){
									$values.= $v;
								}else{
									$values.= $v.',';
								}
							}
							$multipicklistvalue = array();
							foreach($value as $v){
								$multipicklistvalue[] = array('label'=>vtranslate($v,$module),'value'=>$v);
							}
							$field = array(
							'name'  => $fieldname,
							'value' => $values,
							$fieldname.'_value'=>$multipicklistvalue,
							'label' => $fieldinfo['label'],
							'uitype'=> $fieldinfo['uitype'],
							'summaryfield' => $fieldinfo['summaryfield'],
							'typeofdata' => $fieldinfo['typeofdata']
							);
						}else if($fieldname =='time_start' || $fieldname =='time_end'){
							$date = new DateTime();
							$dateTime = new DateTimeField($date->format('Y-m-d').' '.$resultRecord[$fieldname]);
							$value = Vtiger_Time_UIType::getDisplayValue($dateTime->getDisplayTime());
							$field['value'] = $value;
							$field = array(
							'name'  => $fieldname,
							'value' => $value,
							'label' => $fieldinfo['label'],
							'uitype'=> (string)$fieldinfo['uitype'],
							'summaryfield' => $fieldinfo['summaryfield'],
							'typeofdata' => $fieldinfo['typeofdata']
						   );
						}else if($fieldinfo['uitype'] == 71 || $fieldinfo['uitype'] == 30){
							$recordModel = Vtiger_Record_Model::getInstanceById($recordid[1],$module);
							$value = $fieldModel->getDisplayValue($resultRecord[$fieldname], $recordid[1], $recordModel);
							$field = array(
							'name'  => $fieldname,
							'value' => $value,
							'label' => $fieldinfo['label'],
							'uitype'=> (string)$fieldinfo['uitype'],
							'summaryfield' => $fieldinfo['summaryfield'],
							'typeofdata' => $fieldinfo['typeofdata']
						   );
						  
						   if($fieldname =='reminder_time' && $resultRecord['reminder_time'] != ''){
							   $reminder = $resultRecord['reminder_time'];
							   $minutes = (int)($reminder)%60;
							   $hours = (int)($reminder/(60))%24;
							   $days =  (int)($reminder/(60*24));
							   $field['reminder_value'] = array('days'=>$days,'hours'=>$hours,'minutes'=>$minutes);
						   }
						}else if($fieldinfo['uitype'] == 69){
							$AttachmentQuery =$adb->pquery("select vtiger_attachments.attachmentsid, vtiger_attachments.name, vtiger_attachments.subject, vtiger_attachments.path FROM vtiger_seattachmentsrel
											INNER JOIN vtiger_attachments ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid  
											WHERE vtiger_seattachmentsrel.crmid = ?", array($recordid[1]));
											
							$AttachmentQueryCount = $adb->num_rows($AttachmentQuery);
							$document_path = array();
							
							if($AttachmentQueryCount > 0) {
								$name = $adb->query_result($AttachmentQuery, 0, 'name');
								$Path = $adb->query_result($AttachmentQuery, 0, 'path');
								$attachmentsId = $adb->query_result($AttachmentQuery, 0, 'attachmentsid');
								$ImageUrl = $site_URL.$Path.$attachmentsId."_".$name;
							} else {
								$ImageUrl = '';
							}
							$field = array(
							'name'  => $fieldname,
							'value' => $resultRecord[$fieldname],
							'ImageUrl'=>$ImageUrl,
							'label' => $fieldinfo['label'],
							'uitype'=> (string)$fieldinfo['uitype'],
							'summaryfield' => $fieldinfo['summaryfield'],
							'typeofdata' => $fieldinfo['typeofdata']
						   );
						}else{
							$refrenceUitypes = array(10,51,57,58,59,66,73,75,76,78,80,81,101);
							$field = array(
							'name'  => $fieldname,
							'value' => $resultRecord[$fieldname],
							'label' => $fieldinfo['label'],
							'uitype'=> $fieldinfo['uitype'],
							'summaryfield' => $fieldinfo['summaryfield'],
							'typeofdata' => $fieldinfo['typeofdata']
						   );
						   if(in_array($fieldinfo['uitype'],$refrenceUitypes)){
							   if($resultRecord[$fieldname]['value']){
									$refrerenceModule = CTMobile_WS_Utils::detectModulenameFromRecordId($resultRecord[$fieldname]['value']);
									$field['refrerenceModule'] = $refrerenceModule;
							   }else{
								   $field['refrerenceModule'] = "";
							   }
						   }
						   
						}
						
					}
					if($fieldname == 'recurringtype'){
						$field['value'] = $this->RecurringDetails($recordid[1],$module);
						$recordModel = Vtiger_Record_Model::getInstanceById($recordid[1],$module);
						$recurringInfo = $recordModel->getRecurringDetails();
						if(!empty($recurringInfo['repeat_str'])){
							$recurringType = '';
							if($recurringInfo['recurringtype'] == 'Monthly'){
								if($recurringInfo['repeatMonth_daytype'] != ''){
									$repeat_str =explode(' ',$recurringInfo['repeat_str']);
									$recurringType .= ' '.$repeat_str[0].' ';
									$recurringType .= $repeat_str[1].' ';
									$recurringType .= vtranslate(trim($repeat_str[2]),$module);
								}else{
									$repeat_str =explode('  ',$recurringInfo['repeat_str']);
									$recurringType .= ' '.$repeat_str[0].' ';
									$recurringType .= vtranslate(trim($repeat_str[1]),$module);
								}
							}else{
								$repeat_str =explode(' ',$recurringInfo['repeat_str']);
								$recurringType .= ' '.$repeat_str[0].' ';
								$repeat_str1 = explode(',',$repeat_str[1]);
								foreach($repeat_str1 as $key => $r){
									if($key == count($repeat_str1)-1){
										$recurringType .= vtranslate(trim($r),$module);
									}else{
										$recurringType .= vtranslate(trim($r),$module).',';
									}
								}
							}
							$recurringInfo['repeat_str'] = $recurringType;
						}
						$field['recurring_value'] = $recurringInfo;
					}
					if($fieldname == 'filename'){
						global $adb,$site_URL;
						$query = "SELECT * FROM vtiger_attachments INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid=vtiger_attachments.attachmentsid WHERE vtiger_seattachmentsrel.crmid=?";
						$result = $adb->pquery($query,array($recordid[1]));
						$filename = $adb->query_result($result,0,'name');
						$attachmentsid = $adb->query_result($result,0,'attachmentsid');
						$path = $adb->query_result($result,0,'path');
						$filepath = $site_URL.$path.$attachmentsid.'_'.$filename;
						$field['filepath'] = $filepath;
					}
					
					
					// Template record requested send more details if available
					if ($isTemplateRecord) {
						$describeFieldInfo = $this->cachedDescribeFieldInfo($fieldname);
						if ($describeFieldInfo) {
							foreach($describeFieldInfo as $k=>$v) {
								if (isset($field[$k])) continue;
								$field[$k] = $v;
							}
						}
						// Entity fieldnames
						$labelFields = $this->cachedEntityFieldnames($module);
					}
					// Fix the assigned to uitype
					if ($field['uitype'] == '53') {
						$field['type']['defaultValue'] = array('value' => "19x{$current_user->id}", 'label' => $current_user->column_fields['last_name']);
					} else if($field['uitype'] == '117') {
						$field['type']['defaultValue'] = trim($field['value']);
					}
               		// Special case handling to pull configured Terms & Conditions given through webservices.
					else if($field['name'] == 'terms_conditions' && in_array($module, array('Quotes','Invoice', 'SalesOrder', 'PurchaseOrder'))){ 
   						$field['type']['defaultValue'] = trim($field['value']); 
                    }else if($field['uitype'] == '70' ) {
						if($field['value']!=''){
							$recordModel = Vtiger_Record_Model::getInstanceById($recordid[1],$module);
							$userDateTimeString = $fieldModel->getDisplayValue($field['value'], $recordid[1], $recordModel);
							$field['value'] = $userDateTimeString;
							
						}
						
					}else if($field['uitype'] == '5'  ) {
						if($field['value']!=''){
							$field['value'] = Vtiger_Date_UIType::getDisplayDateValue($field['value']);
							
						}
						
					}else if( $field['uitype'] == '6' ) {
						if($field['value']!=''){
							$field['value'] = Vtiger_Date_UIType::getDisplayDateValue($field['value']);
							
						}
						
					}else if($field['uitype'] == '23' ) {
						if($field['value']!=''){
							$field['value'] = Vtiger_Date_UIType::getDisplayDateValue($field['value']);
							
						}
						
					}
					if(array_key_exists('label',$field['value'])){
						if($field['value']['label']){
							$field['value']['label'] = html_entity_decode($field['value']['label'], ENT_QUOTES, $default_charset);
						}
					}else{
						$field['value'] = html_entity_decode($field['value'], ENT_QUOTES, $default_charset);
					}
					$fields[] = $field;
				}
				
				
			}
			$blocklabel = html_entity_decode($blocklabel, ENT_QUOTES, $default_charset);
			$blocks[] = array( 'label' => $blocklabel, 'fields' => $fields );
		}
		
		$sections = array();
		$moduleFieldGroupKeys = array_keys($moduleFieldGroups);
		foreach($moduleFieldGroupKeys as $blocklabel) {
			// Eliminate empty blocks
			if(isset($groups[$blocklabel]) && !empty($groups[$blocklabel])) {
				$sections[] = array( 'label' => $blocklabel, 'count' => count($groups[$blocklabel]) );
			}
		}
		
		$recordLabel = html_entity_decode($resultRecord['recordLabel'], ENT_QUOTES, $default_charset);
		
		if($module == 'Events') {
			global $adb;
			$recordId = explode('x',$resultRecord['id']);
			
			$getInvites = $adb->pquery("SELECT * FROM vtiger_invitees where activityid = ?", array($recordId[1]));
			$countInvities = $adb->num_rows($getInvites);
			$id = ''; // for Detailview
			$invite_user_value = array(); //for Editview
			for($i=0;$i<$countInvities;$i++){
				$inviteId = $adb->query_result($getInvites, $i, 'inviteeid');
				$userRecordModel = Vtiger_Record_Model::getInstanceById($inviteId, 'Users');
				$firstname = $userRecordModel->get('first_name');
				$firstname = html_entity_decode($firstname, ENT_QUOTES, $default_charset);
				$lastname = $userRecordModel->get('last_name');
				$lastname = html_entity_decode($lastname, ENT_QUOTES, $default_charset);
				if($i == 0) {
					$id .= $firstname." ".$lastname;
				} else {
					$id .= ", ".$firstname." ".$lastname;
				}
				$invite_user_value[] = array('value'=>$inviteId,'label'=>$firstname." ".$lastname);
			}
			
			$invitefields[] = array('name'=>'invite_user', 'value'=>$id,'invite_user_value'=>$invite_user_value, 'label' => 'Invite User', 'uitype' => '33', 'summaryfield' => '0', 'typeofdata' => 'V~O');
			$blocks[] = array('label' => vtranslate("Invite User",$module), 'fields'=> $invitefields);
		}
		
		if($module == 'Leads' || $module == 'Contacts'){
			if($totalRecords > 0){
				$sms_notifier = true;
				$sms_status_message = '';
			}else{
				$sms_notifier = false;
				$sms_status_message = 'You do not configure SMS Notifier in CRM. Please configure SMS Notifier in your CRM to use this feature.';
			}	
			$modifiedResult = array('blocks' => $blocks, 'id' => $resultRecord['id'], 'recordLabel' => $recordLabel,'sms_notifier'=>$sms_notifier,'sms_status_message'=>$sms_status_message,'editAction'=>$editAction,'deleteAction'=>$deleteAction,'duplicateAction'=>$duplicateAction,'commentModuleAccess'=>$commentModuleAccess,'ActivityModuleAccess'=>$ActivityModuleAccess);
		}else{
			$modifiedResult = array('blocks' => $blocks, 'id' => $resultRecord['id'], 'recordLabel' => $recordLabel,'editAction'=>$editAction,'deleteAction'=>$deleteAction,'duplicateAction'=>$duplicateAction,'commentModuleAccess'=>$commentModuleAccess,'ActivityModuleAccess'=>$ActivityModuleAccess);
		}
		if($labelFields) $modifiedResult['labelFields'] = $labelFields;
		
		if (isset($resultRecord['LineItems'])) {
			foreach($resultRecord['LineItems'] as $key => $value) {
				$productIds = $value['productid'];
				$productid = explode('x',$productIds);
				
				if($productid[1]) {
					$productRecordModel = Vtiger_Record_Model::getInstanceById($productid[1]);
						$servicename = $productRecordModel->get('servicename');
						$productname = $productRecordModel->get('productname');
						if($productname!=''){
							$productname = html_entity_decode($productname, ENT_QUOTES, $default_charset);
							$resultRecord['LineItems'][$key]['productname'] = $productname;	
						}elseif($servicename!=''){
							$servicename = html_entity_decode($servicename, ENT_QUOTES, $default_charset);
							$resultRecord['LineItems'][$key]['productname'] = $servicename;
						}else{
							$resultRecord['LineItems'][$key]['productname'] = '';
						}
				} else {
					$resultRecord['LineItems'][$key]['productname'] = ''; 
				}
			}
			$modifiedResult['LineItems'] = $resultRecord['LineItems'];
		}
		if(isset($modifiedResult['LineItems'])) {
			
			$recordId = explode('x',$resultRecord['id']);
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId[1]);
			$modifiedResult['Items Total'] = round($recordModel->get('hdnSubTotal'),2);
			$modifiedResult['Pre Tax Total'] = round($recordModel->get('pre_tax_total'),2);
			$modifiedResult['Discount Percent'] = round($recordModel->get('hdnDiscountPercent'),2);
			$modifiedResult['Discount Amount'] = round($recordModel->get('hdnDiscountAmount'),2);
			$modifiedResult['total'] = round($recordModel->get('hdnGrandTotal'),2);
			foreach($modifiedResult['LineItems'] as $key => $value){
				$modifiedResult['LineItems'][$key]['unitprice'] = round(($value['quantity']*$value['listprice']),2);
				$listPrice = $value['listprice'] - (($value['listprice'] * $value['discount_percent'])/100);
				$modifiedResult['LineItems'][$key]['netprice'] = round(($value['quantity']*$listPrice),2);
				foreach($value as $fieldname => $fieldvalue) {
					$LineItemsFields = array('quantity','listprice','discount_percent','discount_amount','tax1','tax2','tax3');
					if(in_array($fieldname,$LineItemsFields)) {
						$modifiedResult['LineItems'][$key][$fieldname] = number_format(round($fieldvalue, 2),2);
					}
				}
			}
		}
		return $modifiedResult;
	}
	
	function RecurringDetails($recordid,$module){
		$recordModel = Vtiger_Record_Model::getInstanceById($recordid,$module);
		$recurringInfo = $recordModel->getRecurringDetails();
		if($recurringInfo['recurringcheck'] == 'Yes'){
			$recurringType = vtranslate('LBL_REPEATEVENT', $module).'  '.$recurringInfo['repeat_frequency'].' '.vtranslate($recurringInfo['recurringtype'], $module);
			if(!empty($recurringInfo['repeat_str'])){
				if($recurringInfo['recurringtype'] == 'Monthly'){
					if($recurringInfo['repeatMonth_daytype'] != ''){
						$repeat_str =explode(' ',$recurringInfo['repeat_str']);
						$recurringType .= ' '.$repeat_str[0].' ';
						$recurringType .= $repeat_str[1].' ';
						$recurringType .= vtranslate(trim($repeat_str[2]),$module);
					}else{
						$repeat_str =explode('  ',$recurringInfo['repeat_str']);
						$recurringType .= ' '.$repeat_str[0].' ';
						$recurringType .= vtranslate(trim($repeat_str[1]),$module);
					}
				}else{
					$repeat_str =explode(' ',$recurringInfo['repeat_str']);
					$recurringType .= ' '.$repeat_str[0].' ';
					$repeat_str1 = explode(',',$repeat_str[1]);
					foreach($repeat_str1 as $key => $r){
						if($key == count($repeat_str1)-1){
							$recurringType .= vtranslate(trim($r),$module);
						}else{
							$recurringType .= vtranslate(trim($r),$module).',';
						}
					}
				}
			}
			$recurringType .= ' '.vtranslate('LBL_UNTIL', $module).' '.$recurringInfo['recurringenddate'];
		}else{
			$recurringType = $recurringInfo['recurringcheck'];
		}
		return $recurringType;
	}
}
