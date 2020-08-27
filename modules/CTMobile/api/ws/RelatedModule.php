<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
//include_once dirname(__FILE__) . '/models/Alert.php';

//include_once 'include/data/CRMEntity.php';

class CTMobile_WS_RelatedModule extends CTMobile_WS_Controller {
	
	
	function getSearchFilterModel($module, $search) {
		return CTMobile_WS_SearchFilterModel::modelWithCriterias($module, Zend_JSON::decode($search));
	}
	
	function getPagingModel(CTMobile_API_Request $request) {
		$page = $request->get('page', 0);
		return CTMobile_WS_PagingModel::modelWithPageStart($page);
	}
	
	function process(CTMobile_API_Request $request) {
		global $adb, $current_user;
		$current_user = $this->getActiveUser();
		$record = trim($request->get('record'));
		$view = $request->get('view');
		$moduleName = trim($request->get('module'));
		$recordid = explode('x', $record);
		$sql1 = "select tabid,name,tablabel from vtiger_tab where name='".$moduleName."'";
		$result1 = $adb->pquery($sql1,array()); 
		$matchtabid =$adb->query_result($result1,0,'tabid');
	
		$sql = "select related_tabid,tabid,name,label, actions from vtiger_relatedlists where tabid ='".$matchtabid."' and presence = 0 ";
		$result = $adb->pquery($sql,array());
		$numofrows = $adb->num_rows($result);
		$relatedid = array();
		$userPrivModel = Users_Privileges_Model::getInstanceById($current_user->id);
		for($i = 0; $i < $numofrows; $i++) {
			$tabid = $adb->query_result($result,$i,'tabid');
			$related_tabid = $adb->query_result($result,$i,'related_tabid');
			$relatedModuleName = getTabid($related_tabid);
			$relatedmodulelabel = $adb->query_result($result,$i,'label');
			$actions = $adb->query_result($result,$i,'actions');	
			$sql2 = "select name from  vtiger_tab where tabid ='".$related_tabid."'"; 
			$result2 = $adb->pquery($sql2,array()); 
			$relatedmoduleName =$adb->query_result($result2,0,'name');
			$getfunctionsql = "SELECT relation_id,name,label,relationfieldid FROM vtiger_relatedlists where tabid = '$matchtabid' AND related_tabid = '$related_tabid' AND name != 'get_history'";
			$getfunctionres = $adb->query($getfunctionsql);
			$relatedfunctionname = '';
			foreach($getfunctionres as $gval){
				$relatedfunctionname = $gval['name'];
				$relation_id = $gval['relation_id'];
				$relation_label =  $gval['label'];
				$relationfieldid = $gval['relationfieldid'];
			}
			$relatedfieldname = "";
			if($relationfieldid != 0){
				$relatedFieldQuery = $adb->pquery('SELECT fieldname FROM vtiger_field WHERE fieldid = ?',array($relationfieldid));
				$relatedfieldname = $adb->query_result($relatedFieldQuery,0,'fieldname');
			}
			$PresenseQuery = "SELECT * FROM  `vtiger_tab`  WHERE `tabid`='".$related_tabid."'";
			$PresenseResult = $adb->pquery($PresenseQuery,array());
			$visible = $adb->query_result($PresenseResult,0,'presence');
			if($visible != 0){
				continue;
			}
			if($relatedmoduleName && $relatedmoduleName != 'ModComments' && $relatedmodulelabel != 'Activities'){
				$moduleModel = Vtiger_Module_Model::getInstance($relatedmoduleName);
				$createAction = $userPrivModel->hasModuleActionPermission($moduleModel->getId(), 'EditView');
			}else{
				continue;
			}
			if(($userPrivModel->isAdminUser() ||
						$userPrivModel->hasGlobalReadPermission() ||
						$userPrivModel->hasModulePermission($moduleModel->getId()))){
				global $currentModule;
				$currentModule = $moduleName;
				$parentRecordModel = Vtiger_Record_Model::getInstanceById($recordid[1], $moduleName);
				$relationListView = Vtiger_RelationListView_Model::getInstance($parentRecordModel, $relatedmoduleName, $relatedmodulelabel);
				$query = $relationListView->getRelationQuery();
				
				$relatedmodulelabel = vtranslate($relatedmodulelabel, $relatedModuleName, $current_user->language);
				$moduleModel = Vtiger_Module_Model::getInstance($relatedmoduleName);
				$basetableid = $moduleModel->get('basetableid');
				$getfunctionres = $adb->pquery($query,array());
				$numofrows2 = $adb->num_rows($getfunctionres);
				$recordArray = array();
				for($j=0;$j<$numofrows2;$j++){
					$crmid = $adb->query_result($getfunctionres,$j,$basetableid);
					if(Users_Privileges_Model::isPermitted($relatedmoduleName, 'DetailView', $crmid)){
							$recordArray[] = $crmid;
					}
				}
				if ($numofrows2 == '') {
					$sql3 = "SELECT relcrmid FROM vtiger_crmentityrel INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_crmentityrel.relcrmid WHERE crmid='".$recordid[1]."' AND relmodule='".$relatedmoduleName."' AND vtiger_crmentity.deleted = 0";
					$result3 = $adb->pquery($sql3,array());
					$numofrows2 = $adb->num_rows($result3);	
					for($j=0;$j<$numofrows2;$j++){
						$crmid = $adb->query_result($result3,$j,'relcrmid');
						if(Users_Privileges_Model::isPermitted($relatedmoduleName, 'DetailView', $crmid)){
								$recordArray[] = $crmid;
						}
					}
				}
				$numofrows2 = count($recordArray);
				$relatedid[] =  array('moduleName' => ($moduleName)?$moduleName:'','record' => $recordid[1],'related_tabid' => ($related_tabid)?$related_tabid:'','relatedmoduleName' => ($relatedmoduleName)?$relatedmoduleName:'','tabid' => ($tabid)?$tabid:'','tablabel'=>($relatedmodulelabel)?$relatedmodulelabel:'','numofrows'=>$numofrows2, 'actions'=>$actions,'createAction'=>$createAction,'relatedfieldname'=>$relatedfieldname);
			}
		}
	   	
   		$response = new CTMobile_API_Response();
		$response->setResult($relatedid);
		return $response;
   		
	}		
}
