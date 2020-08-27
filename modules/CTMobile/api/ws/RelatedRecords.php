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
include_once 'modules/Vtiger/views/Index.php';

class CTMobile_WS_RelatedRecords extends CTMobile_WS_Controller {
		protected $record = false;
		function process(CTMobile_API_Request $request) {
			global $site_URL;
			$db = PearDatabase::getInstance();
			$query = "SELECT vtiger_tab.name FROM  `vtiger_relatedlists` INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_relatedlists.tabid WHERE vtiger_relatedlists.label =  'Documents'";
			$result = $db->pquery($query,array());
			$num_rows = $db->num_rows($result);
			$documentModules = array();
			for($i=0;$i<$num_rows;$i++){
				$documentModules[] = $db->query_result($result,$i,'name');
			}
			$parentId = trim($request->get('record'));
			$module = trim($request->get('module'));
			$relatedRecordList = array();
			$query = $db->pquery('SELECT `tabid` FROM `vtiger_tab` WHERE `name`= ?',array($module));
			$tabid = $db->query_result($query,0,'tabid');
			if($module == 'Potentials' ){
				//Documents
				$limitQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_notes.title, vtiger_notes.folderid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime, vtiger_notes.filename, vtiger_notes.filelocationtype, vtiger_notes.filestatus, vtiger_attachments.path, vtiger_attachments.attachmentsid FROM vtiger_notes inner join vtiger_senotesrel on vtiger_senotesrel.notesid= vtiger_notes.notesid left join vtiger_notescf ON vtiger_notescf.notesid= vtiger_notes.notesid inner join vtiger_crmentity on vtiger_crmentity.crmid= vtiger_notes.notesid and vtiger_crmentity.deleted=0 inner join vtiger_crmentity crm2 on crm2.crmid=vtiger_senotesrel.crmid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid left join vtiger_seattachmentsrel on vtiger_seattachmentsrel.crmid =vtiger_notes.notesid left join vtiger_attachments on vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid left join vtiger_users on vtiger_crmentity.smownerid= vtiger_users.id where crm2.crmid=? AND vtiger_notes.filestatus = 1 ';
				$params = array($parentId);
				$result = $db->pquery($limitQuery, $params);
				$relatedRecordList['Documents']['fields'] = array('title'=>vtranslate('Title',$module),'filename'=>vtranslate('File Name',$module));
				if($db->num_rows($result)>0){
					for($i=0; $i< $db->num_rows($result); $i++ ) {
						$relatedRecordList['Documents']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($result,$i,'crmid');
						$relatedRecordList['Documents']['records'][$i]['title'] = $db->query_result($result,$i,'title');
						$relatedRecordList['Documents']['records'][$i]['filename'] = $db->query_result($result,$i,'filename');
						$relatedRecordList['Documents']['records'][$i]['filelocationtype'] = $db->query_result($result,$i,'filelocationtype');
						$attachmentsid = $db->query_result($result,$i,'attachmentsid');
						$path = $db->query_result($result,$i,'path');
						$relatedRecordList['Documents']['records'][$i]['path'] = $site_URL.$path.$attachmentsid.'_'.$relatedRecordList['Documents']['records'][$i]['filename'];
					}
				}else {
					$relatedRecordList['Documents']['records']=array();
				}

				//Products
				$productQuery = "SELECT DISTINCT vtiger_crmentity.crmid,vtiger_products.productname, vtiger_products.product_no, vtiger_products.discontinued, vtiger_products.productcategory, vtiger_products.qtyinstock, vtiger_products.productcode, vtiger_products.unit_price, vtiger_products.commissionrate, vtiger_products.qty_per_unit FROM vtiger_products INNER JOIN vtiger_seproductsrel ON vtiger_products.productid = vtiger_seproductsrel.productid and vtiger_seproductsrel.setype = 'Potentials' INNER JOIN vtiger_productcf ON vtiger_products.productid = vtiger_productcf.productid INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid INNER JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_seproductsrel.crmid LEFT JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE vtiger_crmentity.deleted = 0 AND vtiger_potential.potentialid = ? ";
				$params = array($parentId);
				$productresult = $db->pquery($productQuery, $params);
				$relatedRecordList['Products']['fields'] = array('productname'=>vtranslate('Product Name',$module),'unit_price'=>vtranslate('Unit Price',$module));
				if($db->num_rows($result)>0){
					for($i=0; $i< $db->num_rows($productresult); $i++ ) {
						$relatedRecordList['Products']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($productresult,$i,'crmid');
						$relatedRecordList['Products']['records'][$i]['productname'] = $db->query_result($productresult,$i,'productname');
						$relatedRecordList['Products']['records'][$i]['unit_price'] = $db->query_result($productresult,$i,'unit_price');
					}
			   }else {
			   		$relatedRecordList['Products']['records'] = array();
			   }

			   //Contacts
				$contactQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, vtiger_contactdetails.phone, vtiger_contactdetails.email, vtiger_contactdetails.accountid, vtiger_contactdetails.title, vtiger_crmentity.smownerid, vtiger_contactaddress.mailingcity, vtiger_contactaddress.mailingcountry FROM vtiger_potential left join vtiger_contpotentialrel on vtiger_contpotentialrel.potentialid = vtiger_potential.potentialid inner join vtiger_contactdetails on ((vtiger_contactdetails.contactid = vtiger_contpotentialrel.contactid) or (vtiger_contactdetails.contactid = vtiger_potential.contact_id)) INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid INNER JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid left join vtiger_account on vtiger_account.accountid = vtiger_contactdetails.accountid left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid left join vtiger_users on vtiger_crmentity.smownerid=vtiger_users.id where vtiger_potential.potentialid = ? and vtiger_crmentity.deleted=0 ';
				$params = array($parentId);
				$contactresult = $db->pquery($contactQuery, $params);
				$relatedRecordList['Contacts']['fields'] = array('firstname'=>vtranslate('First Name',$module),'lastname'=>vtranslate('Last Name',$module),'phone'=>vtranslate('Phone',$module),'email'=>vtranslate('Primary Email',$module),'mailingcity'=>vtranslate('City',$module),'mailingcountry'=>vtranslate('Country',$module));
				if($db->num_rows($contactresult)>0){
					for($i=0; $i< $db->num_rows($contactresult); $i++ ) {
						$relatedRecordList['Contacts']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($contactresult,$i,'crmid');
						$relatedRecordList['Contacts']['records'][$i]['firstname'] = $db->query_result($contactresult,$i,'firstname');
						$relatedRecordList['Contacts']['records'][$i]['lastname'] = $db->query_result($contactresult,$i,'lastname');
						$relatedRecordList['Contacts']['records'][$i]['phone'] = $db->query_result($contactresult,$i,'phone');
						$relatedRecordList['Contacts']['records'][$i]['email'] = $db->query_result($contactresult,$i,'email');
						$relatedRecordList['Contacts']['records'][$i]['mailingcity'] = $db->query_result($contactresult,$i,'mailingcity');
						$relatedRecordList['Contacts']['records'][$i]['mailingcountry'] = $db->query_result($contactresult,$i,'mailingcountry');
					}
				}else{
					$relatedRecordList['Contacts']['records'] =  array();
				}

			}else if($module == 'Project'){
				//Project Key Metrics
				$summaryinfo = $this->getSummaryInfo($parentId);
				$relatedRecordList['Project Key Metrics'] = $summaryinfo;

				//Documents 
				 $limitQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_notes.title, vtiger_notes.folderid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime, vtiger_notes.filename, vtiger_notes.filelocationtype, vtiger_notes.filestatus, vtiger_attachments.path, vtiger_attachments.attachmentsid FROM vtiger_notes inner join vtiger_senotesrel on vtiger_senotesrel.notesid= vtiger_notes.notesid left join vtiger_notescf ON vtiger_notescf.notesid= vtiger_notes.notesid inner join vtiger_crmentity on vtiger_crmentity.crmid= vtiger_notes.notesid and vtiger_crmentity.deleted=0 inner join vtiger_crmentity crm2 on crm2.crmid=vtiger_senotesrel.crmid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid left join vtiger_seattachmentsrel on vtiger_seattachmentsrel.crmid =vtiger_notes.notesid left join vtiger_attachments on vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid left join vtiger_users on vtiger_crmentity.smownerid= vtiger_users.id where crm2.crmid=? AND vtiger_notes.filestatus = 1';
				$params = array($parentId);
				$result = $db->pquery($limitQuery, $params);
				$relatedRecordList['Documents']['fields'] = array('title'=>vtranslate('Title',$module),'filename'=>vtranslate('File Name',$module));
				if($db->num_rows($result)>0){
					for($i=0; $i< $db->num_rows($result); $i++ ) {
						$relatedRecordList['Documents']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($result,$i,'crmid');
						$relatedRecordList['Documents']['records'][$i]['title'] = $db->query_result($result,$i,'title');
						$relatedRecordList['Documents']['records'][$i]['filename'] = $db->query_result($result,$i,'filename');
						$relatedRecordList['Documents']['records'][$i]['filelocationtype'] = $db->query_result($result,$i,'filelocationtype');
						$attachmentsid = $db->query_result($result,$i,'attachmentsid');
						$path = $db->query_result($result,$i,'path');
						$relatedRecordList['Documents']['records'][$i]['path'] = $site_URL.$path.$attachmentsid.'_'.$relatedRecordList['Documents']['records'][$i]['filename'];
					}
				}else{
					$relatedRecordList['Documents']['records']=array();
				}

				//Tickets 
				 $ticketsQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_troubletickets.priority, vtiger_troubletickets.title, vtiger_troubletickets.parent_id, vtiger_troubletickets.contact_id, vtiger_crmentity.smownerid, vtiger_troubletickets.status, vtiger_troubletickets.severity, vtiger_troubletickets.ticket_no, vtiger_crmentity.description FROM vtiger_troubletickets INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_troubletickets.ticketid INNER JOIN vtiger_crmentityrel ON (vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid) LEFT JOIN vtiger_ticketcf ON vtiger_ticketcf.ticketid = vtiger_troubletickets.ticketid LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE vtiger_crmentity.deleted = 0 AND (vtiger_crmentityrel.crmid = ? OR vtiger_crmentityrel.relcrmid = ?) ORDER BY vtiger_crmentity.modifiedtime DESC';
				 $params = array($parentId,$parentId);
				$ticketsresult = $db->pquery($ticketsQuery, $params);
				$relatedRecordList['Tickets']['fields'] = array('title'=>vtranslate('Title',$module),'priority'=>vtranslate('Priority',$module));
				if($db->num_rows($ticketsresult)>0){
					for($i=0; $i< $db->num_rows($ticketsresult); $i++ ) {
						$relatedRecordList['Tickets']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($ticketsresult,$i,'crmid');
						$relatedRecordList['Tickets']['records'][$i]['title'] = $db->query_result($ticketsresult,$i,'title');
						$relatedRecordList['Tickets']['records'][$i]['priority'] = $db->query_result($ticketsresult,$i,'priority');
					}
				}else{
					$relatedRecordList['Tickets']['records'] = array();
				}

				//Milestone 
				 $milestoneQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_projectmilestone.projectmilestonename, vtiger_projectmilestone.projectmilestonedate, vtiger_projectmilestone.projectmilestonetype FROM vtiger_projectmilestone INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_projectmilestone.projectmilestoneid LEFT JOIN vtiger_projectmilestonecf ON vtiger_projectmilestonecf.projectmilestoneid = vtiger_projectmilestone.projectmilestoneid INNER JOIN vtiger_project AS vtiger_projectProject ON vtiger_projectProject.projectid = vtiger_projectmilestone.projectid LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE vtiger_crmentity.deleted = 0 AND vtiger_projectProject.projectid = ? ';
				 $params = array($parentId);
				$milestoneresult = $db->pquery($milestoneQuery, $params);
				$relatedRecordList['ProjectMilestone']['fields'] = array('projectmilestonename'=>vtranslate('Project Milestone Name',$module),'projectmilestonedate'=>vtranslate('Milestone Date',$module));
				if($db->num_rows($milestoneresult)>0){
					for($i=0; $i< $db->num_rows($milestoneresult); $i++ ) {
						$relatedRecordList['ProjectMilestone']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($milestoneresult,$i,'crmid');
						$relatedRecordList['ProjectMilestone']['records'][$i]['projectmilestonename'] = $db->query_result($milestoneresult,$i,'projectmilestonename');
						$relatedRecordList['ProjectMilestone']['records'][$i]['projectmilestonedate'] = $db->query_result($milestoneresult,$i,'projectmilestonedate');
					}
				}else{
		 	 		$relatedRecordList['ProjectMilestone']['records'] = array();
				}

		 	 	//ProjectTask 
				 $taskQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_projecttask.projecttaskname, vtiger_projecttask.projecttasktype, vtiger_crmentity.smownerid, vtiger_projecttask.projecttaskprogress, vtiger_projecttask.startdate, vtiger_projecttask.enddate, vtiger_projecttask.projecttaskstatus FROM vtiger_projecttask INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_projecttask.projecttaskid LEFT JOIN vtiger_projecttaskcf ON vtiger_projecttaskcf.projecttaskid = vtiger_projecttask.projecttaskid INNER JOIN vtiger_project AS vtiger_projectProject ON vtiger_projectProject.projectid = vtiger_projecttask.projectid LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid WHERE vtiger_crmentity.deleted = 0 AND vtiger_projectProject.projectid = ? ';
				 $params = array($parentId);
				$taskresult = $db->pquery($taskQuery, $params);
				$relatedRecordList['ProjectTask']['fields'] = array('projecttaskname'=>vtranslate('Project Task Name',$module),'projecttaskprogress'=>vtranslate('Progress',$module),'projecttaskstatus'=>vtranslate('Status',$module));
				if($db->num_rows($taskresult)>0){
					for($i=0; $i< $db->num_rows($taskresult); $i++ ) {
						$relatedRecordList['ProjectTask']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($taskresult,$i,'crmid');
						$relatedRecordList['ProjectTask']['records'][$i]['projecttaskname'] = $db->query_result($taskresult,$i,'projecttaskname');
						$relatedRecordList['ProjectTask']['records'][$i]['projecttaskprogress'] = $db->query_result($taskresult,$i,'projecttaskprogress');
						$relatedRecordList['ProjectTask']['records'][$i]['projecttaskstatus'] = $db->query_result($taskresult,$i,'projecttaskstatus');
					}
				}else{
						$relatedRecordList['ProjectTask']['records'] = array();
				}

			}else{
				if(in_array($module,$documentModules)){
					//Documents
					$limitQuery = 'SELECT DISTINCT vtiger_crmentity.crmid,vtiger_notes.title, vtiger_notes.folderid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime, vtiger_notes.filename, vtiger_notes.filelocationtype, vtiger_notes.filestatus, vtiger_attachments.path, vtiger_attachments.attachmentsid FROM vtiger_notes inner join vtiger_senotesrel on vtiger_senotesrel.notesid= vtiger_notes.notesid left join vtiger_notescf ON vtiger_notescf.notesid= vtiger_notes.notesid inner join vtiger_crmentity on vtiger_crmentity.crmid= vtiger_notes.notesid and vtiger_crmentity.deleted=0 inner join vtiger_crmentity crm2 on crm2.crmid=vtiger_senotesrel.crmid LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid left join vtiger_seattachmentsrel on vtiger_seattachmentsrel.crmid =vtiger_notes.notesid left join vtiger_attachments on vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid left join vtiger_users on vtiger_crmentity.smownerid= vtiger_users.id where crm2.crmid=? AND vtiger_notes.filestatus = 1 ';
					$params = array($parentId);
					$result = $db->pquery($limitQuery, $params);
					$relatedRecordList['Documents']['fields'] = array('title'=>vtranslate('Title',$module),'filename'=>vtranslate('File Name',$module));
					if($db->num_rows($result)>0){
						for($i=0; $i< $db->num_rows($result); $i++ ) {
							$relatedRecordList['Documents']['records'][$i]['crmid'] = $tabid.'x'.$db->query_result($result,$i,'crmid');
							$relatedRecordList['Documents']['records'][$i]['title'] = $db->query_result($result,$i,'title');
							$relatedRecordList['Documents']['records'][$i]['filename'] = $db->query_result($result,$i,'filename');
							$relatedRecordList['Documents']['records'][$i]['filelocationtype'] = $db->query_result($result,$i,'filelocationtype');
							$attachmentsid = $db->query_result($result,$i,'attachmentsid');
							$path = $db->query_result($result,$i,'path');
							$relatedRecordList['Documents']['records'][$i]['path'] = $site_URL.$path.$attachmentsid.'_'.$relatedRecordList['Documents']['records'][$i]['filename'];
						}
					}else{
						$relatedRecordList['Documents']['records']=array();
					}
				}else{
					$relatedRecordList = (object)array();
				}
			}
			
			$response = new CTMobile_API_Response();
			$response->setResult($relatedRecordList);
			return $response;
			
			
		}

		public function getSummaryInfo($id) {
			
				$adb = PearDatabase::getInstance();

				$query ='SELECT smownerid,enddate,projecttaskstatus,projecttaskpriority
						FROM vtiger_projecttask
								INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_projecttask.projecttaskid
									AND vtiger_crmentity.deleted=0
								WHERE vtiger_projecttask.projectid = ? ';

				$result = $adb->pquery($query, array($id));

				$tasksOpen = $taskProgress= $taskCompleted = $taskDue = $taskDeferred = $numOfPeople = 0;
				$highTasks = $lowTasks = $normalTasks = $otherTasks = 0;
				$currentDate = date('Y-m-d');
				$inProgressStatus = array('Open', 'In Progress');
				$usersList = array();

				while($row = $adb->fetchByAssoc($result)) {
					$projectTaskStatus = $row['projecttaskstatus'];
					switch($projectTaskStatus){
						case 'Open'		: $tasksOpen++;		break;
						case 'In Progress' : $taskProgress++;break;
						case 'Deferred'	: $taskDeferred++;	break;
						case 'Completed': $taskCompleted++;	break;
					}
					$projectTaskPriority = $row['projecttaskpriority'];
					switch($projectTaskPriority){
						case 'high' : $highTasks++;break;
						case 'low' : $lowTasks++;break;
						case 'normal' : $normalTasks++;break;
						default : $otherTasks++;break;
					}

					if(!empty($row['enddate']) && (strtotime($row['enddate']) < strtotime($currentDate)) &&
							(in_array($row['projecttaskstatus'], $inProgressStatus))) {
						$taskDue++;
					}
					$usersList[] = $row['smownerid'];
				}

				$usersList = array_unique($usersList);
				$numOfPeople = count($usersList);

				$summaryInfo['projecttaskstatus'] =  array(array('Label'=>vtranslate('LBL_TASKS_OPEN','Project'),'value'=>$tasksOpen),
					array('Label'=>vtranslate('Progress','Project'),'value'=>$taskProgress),
					array('Label'=>vtranslate('LBL_TASKS_DUE','Project'),'value'=>$taskDue),
					array('Label'=>vtranslate('LBL_TASKS_COMPLETED','Project'),'value'=>$taskCompleted));

				$summaryInfo['projecttaskpriority'] =  array(array('Label'=>vtranslate('LBL_TASKS_HIGH','Project'),'value'=>$highTasks),
					array('Label'=>vtranslate('LBL_TASKS_NORMAL','Project'),'value'=>$normalTasks),
					array('Label'=>vtranslate('LBL_TASKS_LOW','Project'),'value'=>$lowTasks),
					array('Label'=>vtranslate('LBL_TASKS_OTHER','Project'),'value'=>$otherTasks));

			return $summaryInfo;
	}
	
}
