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

class CTMobile_WS_ListModuleRecords extends CTMobile_WS_Controller {
	function isCalendarModule($module) {
		return ($module == 'Events' || $module == 'Calendar');
	}
	
	function getSearchFilterModel($module, $search) {
		return CTMobile_WS_SearchFilterModel::modelWithCriterias($module, Zend_JSON::decode($search));
	}
	
	function getPagingModel(CTMobile_API_Request $request) {
		$page = $request->get('page', 0);
		return CTMobile_WS_PagingModel::modelWithPageStart($page);
	}
	
	function process(CTMobile_API_Request $request) {
		
		return $this->processSearchRecordLabel($request);
	}

	function GroupDetails($pagingModel,$paging){
		global $adb;
		$index = $paging['index'];
		$size = $paging['size'];
		$limit = ($index*$size) - $size;
		$query = "SELECT * FROM vtiger_groups";
		if($index != '' && $size != '') {
			$query .= sprintf(" LIMIT %s, %s", $limit, $size);
		}
		$prequeryResult = $adb->pquery($query,array());
		$result = new SqlResultIterator($adb, $prequeryResult);
		$i = 0;
		foreach($result as $record) {
			if ($record instanceof SqlResultIteratorRow) {
				$record = $record->data;
				$modifiedRecord[$i]['assigned_user_id'] = $modifiedRecord[$i]['modifiedtime'] = $modifiedRecord[$i]['label'] = $modifiedRecord[$i]['id'] = null;
				foreach($record as $key => $values){
					$modifiedRecord[$i]['modifiedtime']= null;
					$modifiedRecord[$i]['assigned_user_id'] = null;
					if($key == 'groupid'){
						$modifiedRecord[$i]['id'] = '20x'.$values;
					}else if($key == 'groupname'){
						$modifiedRecord[$i]['label'] = $values;
					}
				}
			}
			$i =$i+1;
		}
		return $modifiedRecord;
	}
	
	function processSearchRecordLabel(CTMobile_API_Request $request) {
		$default_charset = VTWS_PreserveGlobal::getGlobal('default_charset');
		global $current_user, $adb, $site_URL; // Few core API assumes this variable availability
		
		$current_user = $this->getActiveUser();
		
		$module = trim($request->get('module'));
		$alertid = $request->get('alertid');
		$filterid = $request->get('filterid');
		$search = trim($request->get('search'));
		$index = $request->get('index');
		$size = $request->get('size');
		$type = $request->get('type');
		$field_name = trim($request->get('field_name'));
		$field_value = trim($request->get('field_value'));
		$order_by = $request->get('order_field');
		$orderby = $request->get('orderby');
		$display_params = $request->get('display_params');
		$params = Zend_Json::decode($display_params);
		//echo "<pre>";print_r($params);exit;
		$user_type = $request->get('user_type');
		$related = $request->get('related');
		
		if($module == 'Groups'){
			$pagingModel = $this->getPagingModel($request);
			$paging = array('index'=>$index, 'size'=>$size);
			$modifiedRecords = array();
			$modifiedRecords = $this->GroupDetails($pagingModel,$paging);
			$response = new CTMobile_API_Response();
			if(count($modifiedRecords) == 0) {
				$response->setResult(array('records'=>$modifiedRecords, 'module'=>$module, 'msg'=>'No more records','module_record_status'=>false));
			} else {
				$response->setResult(array('records'=>$modifiedRecords, 'module'=>$module, 'msg'=>'','module_record_status'=>true));
			}
			return $response;
		}
		
		
		$userId = array();
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$fieldModels = $moduleModel->getFields();
		if($field_name){
			$uitype = $fieldModels[$field_name]->get('uitype');
		}
		$refrenceUitypes = array(10,51,57,58,59,66,73,75,76,78,80,81,101);
		if(in_array($uitype,$refrenceUitypes)){
			$field_value = trim($field_value);
			$result = $adb->pquery("SELECT crmid FROM vtiger_crmentity WHERE label LIKE '%".$field_value."%' ");
			$numofrows = $adb->num_rows($result);
			for($i=0;$i<$numofrows;$i++){
				 $otherId[] = $adb->query_result($result,$i,'crmid');
			}
			$relation_name = $field_name;
			$field_value = implode(",",$otherId);
		}
		
		if($field_name == 'assigned_user_id'){
			$result = $adb->pquery("SELECT id FROM vtiger_users WHERE first_name LIKE '%".$field_value."%' OR last_name LIKE '%".$field_value."%' ");
			$numofrows = $adb->num_rows($result);
			for($i=0;$i<$numofrows;$i++){
				 $userId[] = $adb->query_result($result,$i,'id');
			}
			$field_name = '';
			$field_value = '';
		}
		$WithoutFilterModules = array('Users','CTUserFilterView');
		if(!$filterid && $module != ''  && !in_array($module, $WithoutFilterModules)) {
			$customView = new CustomView();
			$filterid = $customView->getViewId($module);
		}
		$filterOrAlertInstance = false;
		if(!empty($alertid)) {
			$filterOrAlertInstance = CTMobile_WS_AlertModel::modelWithId($alertid);
		}
		else if(!empty($filterid)) {
			$filterOrAlertInstance = CTMobile_WS_FilterModel::modelWithId($module, $filterid);
		}
		else if(!empty($search)) {
			
			$filterOrAlertInstance = $this->getSearchFilterModel($module, $search);
		}
		
		if($filterOrAlertInstance && strcmp($module, $filterOrAlertInstance->moduleName)) {
			$response = new CTMobile_API_Response();
			$response->setError(1001, 'Mismatched module information.');
			return $response;
		}
		

		// Initialize with more information
		if($filterOrAlertInstance) {
			$filterOrAlertInstance->setUser($current_user);
		}
		
		// Paging model
		$pagingModel = $this->getPagingModel($request);
		$paging = array('index'=>$index, 'size'=>$size);
		if($user_type == 'free'){
			$maxLimit = $index * $size;
			//~ if($maxLimit > 30){
				//~ $result = array();
				//~ $response = new CTMobile_API_Response();
				//~ $msg = html_entity_decode('You do not have permission to view more records. Please subcscribe for Premium version.');
				//~ $response->setResult(array('records'=>$result,'msg'=>$msg,'module_record_status'=>false,'user_type'=>$user_type));
				//~ return $response;
			//~ }
		}
		/* Start: Added by Vijay Bhavsar */
		if($module == 'Leads') {
			$morefields = array('firstname', 'lastname', 'phone', 'company', 'designation', 'email', 'createdtime', 'modifiedtime','assigned_user_id');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else if($module == "Contacts"){
			$morefields = array('firstname', 'lastname', 'title', 'phone', 'email', 'createdtime', 'modifiedtime','assigned_user_id');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else if($module == "Products"){
			$morefields = array('productname','unit_price', 'createdtime', 'modifiedtime','assigned_user_id','discontinued');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else if($module == "Services"){
			$morefields = array('servicename','unit_price', 'createdtime', 'modifiedtime','assigned_user_id','discontinued');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else if($module == "CTUserFilterView"){
			$morefields = array('module_name','filter_id', 'filter_name','createdtime', 'modifiedtime', 'assigned_user_id');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else if($module == "Documents"){
			$morefields = array('notes_title', 'createdtime', 'modifiedtime','assigned_user_id');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else if($module == "CTCalllog"){
			$morefields = array('calllog_no', 'calllog_name', 'modifiedtime','assigned_user_id');
			foreach ($params as $p) {
				if(!in_array($p, $morefields)){
					$morefields[]=$p;
				}
			}
		}else{
			$morefields = array();
			foreach ($params as $p) {
				$morefields[]=$p;
			}
			if($module == 'Users'){

			}else{
				$morefields[]= 'assigned_user_id';
			}
		}
		
		
		/* End: Added by Vijay Bhavsar */
		if($this->isCalendarModule($module)) {
			
			return $this->processSearchRecordLabelForCalendar($request, $filterOrAlertInstance, $pagingModel, $paging,$field_name, $field_value,$order_by,$orderby,$related);
		}
		
		$records = $this->fetchRecordLabelsForModule($module, $current_user, $morefields, $filterOrAlertInstance, $pagingModel, $paging, $field_name, $field_value,$order_by,$orderby,$related);

		$modifiedRecords = array();
		foreach($records as $record) {
			if ($record instanceof SqlResultIteratorRow) {
				$record = $record->data;
				// Remove all integer indexed mappings
				for($index = count($record); $index > -1; --$index) {
					if(isset($record[$index])) {
						unset($record[$index]);
					}
				}
			}
			
			if($module == 'CTUserFilterView'){
				$total_records_count = 0;
				$user_id = '19x'.$current_user->id;
				if($user_id!=$record['assigned_user_id']){
					continue;
				}elseif($record['filter_id']!='' && $record['module_name']!='' && ctype_digit($record['filter_id'])){
					$listViewModel = Vtiger_ListView_Model::getInstance($record['module_name'], $record['filter_id']);
					$total_records_count = $listViewModel->getListViewCount();
			
				}
			}
			
			$recordid = $record['id'];
			unset($record['id']);
			
			$eventstart = '';
			if($this->isCalendarModule($module)) {
				$eventstart = $record['date_start'];
				unset($record['date_start']);
			}

			$values = array_values($record);
			if($module == 'Users') {
				$label = implode(' ', $values);
			} else {
				$label = $values[1];
				$fieldnames = CTMobile_WS_Utils::getEntityFieldnames($module);
				$label = $record[$fieldnames[0]];
			}
			
			
			$record_id = explode('x', $recordid);
			$moduleModel = Vtiger_Module_Model::getInstance($module);
			$fieldModels = $moduleModel->getFields();
			$recordModel = Vtiger_Record_Model::getInstanceById($record_id[1],$module);
			foreach($record as $key => $value){
				if($value){
					$record[$key] = html_entity_decode($value, ENT_QUOTES, $default_charset);
					if(isset($record[$key])){
						$fieldModel = $fieldModels[$key];
						$uitype = $fieldModel->get('uitype');
						$allowedUitypes = array('5','6','23','70');
						if(in_array($uitype,$allowedUitypes)){
							$record[$key] = $fieldModel->getDisplayValue($record[$key], $record_id[1], $recordModel);
						}
					}
				}else{
					$record[$key] = "";
				}
				
			}
			/* Start: Added by Vijay Bhavsar */
			$query = "SELECT * FROM vtiger_smsnotifier_servers WHERE isactive='1'";
			$result = $adb->pquery($query,array());
			$totalRecords = $adb->num_rows($result);
			
			if($module == "Leads") {
				$modifiedRecord = array('id' => $recordid, 'label'=>$record['firstname']." ".$record['lastname'], 'firstname'=>$record['firstname'], 'lastname' => $record['lastname'], 'phone' => $record['phone'], 'company' => $record['company'], 'designation' => $record['designation'], 'email' => $record['email'], 'createdtime' => $record['createdtime'], 'modifiedtime'=>$record['modifiedtime'],'assigned_user_id'=>$record['assigned_user_id']); 
				foreach ($params as $p) {
					$modifiedRecord[$p]=$record[$p];
				}
				if($totalRecords > 0){
					$modifiedRecord['sms_notifier'] = true;
				}else{
					$modifiedRecord['sms_notifier'] = false;
				}
			} else if($module == "Contacts"){
				$record_id = explode('x', $recordid);
				$AttachmentQuery =$adb->pquery("select vtiger_attachments.attachmentsid, vtiger_attachments.name, vtiger_attachments.subject, vtiger_attachments.path FROM vtiger_seattachmentsrel
											INNER JOIN vtiger_attachments ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid 
											LEFT JOIN vtiger_notes ON vtiger_notes.notesid = vtiger_seattachmentsrel.crmid 
											WHERE vtiger_seattachmentsrel.crmid = ?", array($record_id[1]));
											
				$AttachmentQueryCount = $adb->num_rows($AttachmentQuery);
				$document_path = array();
				
				if($AttachmentQueryCount > 0) {
					$name = $adb->query_result($AttachmentQuery, 0, 'name');
					$Path = $adb->query_result($AttachmentQuery, 0, 'path');
					$attachmentsId = $adb->query_result($AttachmentQuery, 0, 'attachmentsid');
					$contactImageUrl = $site_URL.$Path.$attachmentsId."_".$name;
				} else {
					$contactImageUrl = '';
				}
				$modifiedRecord = array('id' => $recordid, 'label'=>$record['firstname']." ".$record['lastname'], 'contactname' => $record['firstname']." ".$record['lastname'], 'title' => $record['title'], 'phoneNo'=> $record['phone'], 'email'=> $record['email'], 'contactimage' => $contactImageUrl, 'modifiedtime'=>$record['modifiedtime'], 'createdtime'=>$record['createdtime'],'assigned_user_id'=>$record['assigned_user_id']); 
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				}
				if($totalRecords > 0){
					$modifiedRecord['sms_notifier'] = true;
				}else{
					$modifiedRecord['sms_notifier'] = false;
				}
			}else if($module == "Products") {
				$record_id = explode('x', $recordid);
				$AttachmentQuery =$adb->pquery("select vtiger_attachments.attachmentsid, vtiger_attachments.name, vtiger_attachments.subject, vtiger_attachments.path FROM vtiger_seattachmentsrel
											INNER JOIN vtiger_attachments ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid 
											LEFT JOIN vtiger_notes ON vtiger_notes.notesid = vtiger_seattachmentsrel.crmid 
											WHERE vtiger_seattachmentsrel.crmid = ?", array($record_id[1]));
											
				$AttachmentQueryCount = $adb->num_rows($AttachmentQuery);
				$document_path = array();
				
				if($AttachmentQueryCount > 0) {
					$name = $adb->query_result($AttachmentQuery, 0, 'name');
					$Path = $adb->query_result($AttachmentQuery, 0, 'path');
					$attachmentsId = $adb->query_result($AttachmentQuery, 0, 'attachmentsid');
					$productImageUrl = $site_URL.$Path.$attachmentsId."_".$name;
				} else {
					$productImageUrl = '';
				}
				$modifiedRecord = array('id' => $recordid, 'label'=>$record['productname'], 'productimage' => $productImageUrl,'unit_price' => number_format((float)$record['unit_price'], 2, '.', ''), 'createdtime' => $record['createdtime'], 'modifiedtime'=>$record['modifiedtime'],'assigned_user_id'=>$record['assigned_user_id'],'isActive'=>$record['discontinued']);
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				} 
			}else if($module == "Services") {
				$modifiedRecord = array('id' => $recordid, 'label'=>$record['servicename'], 'unit_price' => number_format((float)$record['unit_price'], 2, '.', ''), 'createdtime' => $record['createdtime'], 'modifiedtime'=>$record['modifiedtime'],'assigned_user_id'=>$record['assigned_user_id'],'isActive'=>$record['discontinued']); 
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				}
			}else if($module == "CTUserFilterView") {
				$modifiedRecord = array('id' => $recordid, 'label'=>$label, 'module_name'=>$record['module_name'], 'filter_id'=>$record['filter_id'], 'filter_name'=>$record['filter_name'],'modifiedtime'=>$record['modifiedtime'],'total_records_count'=>$total_records_count,'assigned_user_id'=>$record['assigned_user_id']); 
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				}
			}else if($module == "Documents") {
				$modifiedRecord = array('id' => $recordid, 'label'=>$record['notes_title'], 'modifiedtime'=>$record['modifiedtime'],'assigned_user_id'=>$record['assigned_user_id']); 
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				}
			}else if($module == "CTCalllog"){
				$modifiedRecord = array('id' => $recordid, 'label'=>$record['calllog_name'], 'modifiedtime'=>$record['modifiedtime'],'assigned_user_id'=>$record['assigned_user_id']); 
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				}
			}else {
				$modifiedRecord = array('id' => $recordid,'label'=>$label);
				foreach ($params as $p) {
					$uitype = $fieldModels[$p]->get('uitype');
					if(in_array($uitype,$refrenceUitypes)){
						if($record[$p] == 0){
							$modifiedRecord[$p]="";
						}else{
							$labelresult = $adb->pquery("SELECT label FROM vtiger_crmentity WHERE crmid = ?",array($record[$p]));
							$new = $adb->query_result($labelresult,0,'label');
							$modifiedRecord[$p]=$new;
						}
					}else{
						$modifiedRecord[$p]=$record[$p];
				    }
				}
				 $modifiedRecord['modifiedtime'] = $record['modifiedtime'];
				 $modifiedRecord['assigned_user_id'] = $record['assigned_user_id'];
			}
			
			/* End: Added by Vijay Bhavsar */
			
			//get Username Form userid
			if(!empty($modifiedRecord['assigned_user_id'])){
				if($module == 'CTUserFilterView'){
					$modifiedRecord['assigned_user_id'] = explode('x',$modifiedRecord['assigned_user_id']);
					$modifiedRecord['assigned_user_id'] = $modifiedRecord['assigned_user_id'][1];
				}
				$userRecordModel = Vtiger_Record_Model::getInstanceById($modifiedRecord['assigned_user_id'],'Users');
				$modifiedRecord['assigned_user_id'] = $userRecordModel->get('first_name').' '.$userRecordModel->get('last_name');
			}
			
			if(!empty($eventstart)) {
				$modifiedRecord['eventstart'] = $eventstart;
			}
			if(!empty($userId)){
				if(in_array($modifiedRecord['assigned_user_id'],$userId)){
					$modifiedRecords[] = $modifiedRecord;
				}else{
					continue;
				}
			}else{
				$modifiedRecords[] = $modifiedRecord;
			}	
			
		}
		
		
		foreach ($modifiedRecords as $key => $part) {
			$sort[$key] = strtotime($part['modifiedtime']);	
		}
		
		if($module == 'Users' && $type == 'owner'){
			$users = $modifiedRecords;
			$pagingModel = $this->getPagingModel($request);
			$paging = array('index'=>$index, 'size'=>$size);
			$modifiedRecords = array();
			$modifiedRecords = $this->GroupDetails($pagingModel,$paging);
			$groups = $modifiedRecords;
			$modifiedRecord = array();
			$modifiedRecord['Users'] = $users;
			$modifiedRecord['Groups'] = $groups;
			$moduleLabel = vtranslate($module,$module);
			$response = new CTMobile_API_Response();
			$response->setResult(array('records'=>$modifiedRecord, 'module'=>$module,'moduleLabel'=>$moduleLabel, 'msg'=>'','module_record_status'=>true));
			return $response;
		}
		//for create action 
		$userPrivModel = Users_Privileges_Model::getInstanceById($current_user->id);
		$createAction = $userPrivModel->hasModuleActionPermission($moduleModel->getId(), 'CreateView');
		
		$moduleLabel = vtranslate($module,$module);
		$response = new CTMobile_API_Response();
		if(count($modifiedRecords) == 0) {
			$response->setResult(array('records'=>$modifiedRecords, 'module'=>$module,'moduleLabel'=>$moduleLabel, 'msg'=>'No more records','module_record_status'=>false,'createAction'=>$createAction));
		} else {
			$response->setResult(array('records'=>$modifiedRecords, 'module'=>$module,'moduleLabel'=>$moduleLabel, 'msg'=>'','module_record_status'=>true,'createAction'=>$createAction));
		}
		
		return $response;
	}
	
	function processSearchRecordLabelForCalendar(CTMobile_API_Request $request,$filterOrAlertInstance = false, $pagingModel = false, $paging = array(),$field_name, $field_value,$order_by,$orderby,$related) {
		$current_user = $this->getActiveUser();
		$module = $request->get('module');
		// Fetch both Calendar (Todo) and Event information
		$moreMetaFields = array('date_start', 'time_start', 'activitytype', 'location', 'subject', 'createdtime');
		$records=$this->fetchRecordLabelsForModule($module, $current_user, $moreMetaFields, $filterOrAlertInstance, $pagingModel, $paging,$field_name, $field_value,$order_by,$orderby,$related);

		$modifiedRecords = array();
		foreach($records as $record) {
			if ($record instanceof SqlResultIteratorRow) {
				$record = $record->data;
				// Remove all integer indexed mappings
				for($index = count($record); $index > -1; --$index) {
					if(isset($record[$index])) {
						unset($record[$index]);
					}
				}
			}
		    $recordId = explode('x',$record['id']);
		    global $adb;
		    $modifiedRecord = array();
		    $EventTaskQuery = $adb->pquery("SELECT * FROM  `vtiger_activity` WHERE activitytype = ? AND activityid = ?",array('Task',$recordId[1])); 
		    if($adb->num_rows($EventTaskQuery) > 0){
				$wsid = CTMobile_WS_Utils::getEntityModuleWSId('Calendar');
				$record['id'] = $wsid.'x'.$recordId[1];
				$modifiedRecord['module'] = 'Calendar';
			}else{
				$wsid = CTMobile_WS_Utils::getEntityModuleWSId('Events');
				$record['id'] = $wsid.'x'.$recordId[1];
				$modifiedRecord['module'] = 'Events';
			}
			
			$modifiedRecord['id'] = $record['id'];                      unset($record['id']);
			$modifiedRecord['eventstartdate'] = $record['date_start'];  unset($record['date_start']);
			$modifiedRecord['eventstarttime'] = $record['time_start'];  unset($record['time_start']);
			$modifiedRecord['eventtype'] = $record['activitytype'];     unset($record['activitytype']);
			$modifiedRecord['eventlocation'] = $record['location'];     unset($record['location']);
			$modifiedRecord['subject'] = $record['subject'];     unset($record['subject']);
			$modifiedRecord['createdtime'] = $record['createdtime'];     unset($record['createdtime']);
			
			$modifiedRecord['label'] =  $modifiedRecord['subject'];
			$modifiedRecord['startDateTime'] = $modifiedRecord['eventstartdate']." ".$modifiedRecord['eventstarttime'];
			if(Users_Privileges_Model::isPermitted($module, 'DetailView', $recordId[1])){
				$modifiedRecords[] = $modifiedRecord;
			}
		}
		
		foreach ($modifiedRecords as $key => $part) {
			$sort[$key] = strtotime($part['startDateTime']);
		}
		array_multisort($sort, SORT_DESC, $modifiedRecords);
		$moduleLabel = vtranslate($module,$module);
		$response = new CTMobile_API_Response();
		if(count($modifiedRecords) == 0) {
			$response->setResult(array('records'=>$modifiedRecords, 'module'=>$module,'moduleLabel'=>$moduleLabel, 'msg'=>'No more records','module_record_status'=>false));
		} else {
			$response->setResult(array('records'=>$modifiedRecords, 'module'=>$module,'moduleLabel'=>$moduleLabel, 'msg'=>'','module_record_status'=>true));
		}
		
		return $response;
	}
	
	function fetchRecordLabelsForModule($module, $user, $morefields=array(), $filterOrAlertInstance=false, $pagingModel = false, $paging=array(), $field_name, $field_value,$order_by,$orderby,$related) {
		
		if($module != 'Users') {
			$morefields[]='modifiedtime';
		}
		
		if($this->isCalendarModule($module)) {
			$fieldnames = CTMobile_WS_Utils::getEntityFieldnames('Calendar');
		} else {
			$fieldnames = CTMobile_WS_Utils::getEntityFieldnames($module);
		}
		
		if(!empty($morefields)) {
			foreach($morefields as $fieldname) $fieldnames[] = $fieldname;
		}

		if($filterOrAlertInstance === false) {
			$filterOrAlertInstance = CTMobile_WS_SearchFilterModel::modelWithCriterias($module);
			$filterOrAlertInstance->setUser($user);
		}
			
		return $this->queryToSelectFilteredRecords($module, $fieldnames, $filterOrAlertInstance, $pagingModel, $paging, $field_name, $field_value,$order_by,$orderby,$user,$related);
	}
	
	function queryToSelectFilteredRecords($module, $fieldnames, $filterOrAlertInstance, $pagingModel, $paging = array(), $field_name, $field_value,$order_by,$orderby,$user,$related) {
		
		if (($filterOrAlertInstance instanceof CTMobile_WS_SearchFilterModel) && !$this->isCalendarModule($module)) {
			if(!empty($order_by) && !empty($orderby)){
				$orderClause = " ORDER BY ".$order_by." ".$orderby;
			}else{
				$orderClause = '';
			}
			return $filterOrAlertInstance->execute($fieldnames, $pagingModel, $paging, $orderClause);
			
		}
		
		global $adb;
		$moduleWSId = CTMobile_WS_Utils::getEntityModuleWSId($module);
		$columnByFieldNames = CTMobile_WS_Utils::getModuleColumnTableByFieldNames($module, $fieldnames);
		// Build select clause similar to Webservice query
		$selectColumnClause = "CONCAT('{$moduleWSId}','x',vtiger_crmentity.crmid) as id,";
		foreach($columnByFieldNames as $fieldname=>$fieldinfo) {
			$selectColumnClause .= sprintf("%s.%s as %s,", $fieldinfo['table'],$fieldinfo['column'],$fieldname);
		}
		
		$selectColumnClause = rtrim($selectColumnClause, ',');
		$var =array();
		for($i=0;$i<count($fieldnames);$i++){
			$var[]= $fieldnames[$i];
		}
		$var[]='id';
		$generator = new QueryGenerator($module, $user);
		if($related != 1){
		 $generator->initForCustomViewById($filterOrAlertInstance->filterid);
	    }
		$generator->setFields($var);
		$query = $generator->getQuery();
		$query = preg_replace("/SELECT.*FROM(.*)/i", "SELECT $selectColumnClause FROM $1", $query);
		if($module == 'Events' || $module == 'Calendar'){
			$Eventsquery = explode('WHERE',$query);
			$query = $Eventsquery[0]." WHERE vtiger_crmentity.setype = 'Calendar' AND ".$Eventsquery[1];
		}
			
		if ($pagingModel !== false) {
			$index = $paging['index'];
			$size = $paging['size'];
			$limit = ($index*$size) - $size;
			if($index != '' && $size != '') {
				if($field_name != '' && $field_value != ''){
					$tablename = $columnByFieldNames[$field_name]['table'];
					$moduleModel = Vtiger_Module_Model::getInstance($module);
					$fieldModels = $moduleModel->getFields();
					if($field_name){
						$uitype = $fieldModels[$field_name]->get('uitype');
					}
					$refrenceUitypes = array(10,51,57,58,59,66,73,75,76,78,80,81,101);
					if(in_array($uitype,$refrenceUitypes)){
						$tablename =  $fieldModels[$field_name]->get('table');
						$column =  $fieldModels[$field_name]->get('column');
						$query .= " AND ".$tablename.".".$column." IN (".$field_value.")";
					}else{
						$tablename =  $fieldModels[$field_name]->get('table');
						$column =  $fieldModels[$field_name]->get('column');
						$query .= " AND ".$tablename.".".$column." LIKE '%".$field_value."%'";
					}
					
				}
				if($order_by){
				   if($orderby){
				   	$query .= " ORDER BY ".$order_by." ".$orderby;
				   }else{
					$query .= " ORDER BY ".$order_by." ASC";
				   }	
				}else{
				   $query .= " ORDER BY modifiedtime DESC";
				}
				$query .= sprintf(" LIMIT %s, %s", $limit, $size);
			}
		}
		if($module == 'Leads'){
			if (strpos($query, 'INNER JOIN vtiger_leadaddress') == false) {
				$query_explode = explode('WHERE',$query);
				$query = $query_explode[0]." INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_crmentity.crmid WHERE ".$query_explode[1]." AND ".$field_name." LIKE %".$field_value."%";
			}	
		}
		
		$prequeryResult = $adb->pquery($query, $filterOrAlertInstance->queryParameters());
		return new SqlResultIterator($adb, $prequeryResult);
	}
	
}
