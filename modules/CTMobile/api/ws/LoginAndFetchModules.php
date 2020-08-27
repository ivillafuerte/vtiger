<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once dirname(__FILE__) . '/Login.php';
class CTMobile_WS_LoginAndFetchModules extends CTMobile_WS_Login {
	
	function postProcess(CTMobile_API_Response $response) {
		global $current_user,$adb, $site_URL;
		$current_user = $this->getActiveUser();
		
		if ($current_user) {
			$results = $response->getResult();
			$user_type = $_REQUEST['user_type'];
			$results['modules'] = $this->getAllVisibleModules($user_type);
			
			$query = "SELECT vtiger_tab.name, vtiger_tab.tabid FROM vtiger_relatedlists INNER JOIN vtiger_tab ON vtiger_relatedlists.tabid = vtiger_tab.tabid where vtiger_relatedlists.presence = 0 AND vtiger_relatedlists.label=?";
			$params = array("ModComments");
			$result = $adb->pquery($query , $params);
			$numrows = $adb->num_rows($result);
			$CommentsModule = array();
			for($i=0;$i<$numrows;$i++){
				$CommentsModule[] = $adb->query_result($result,$i,'name');
			}
			
			$query2 = "SELECT vtiger_tab.name, vtiger_tab.tabid FROM vtiger_relatedlists INNER JOIN vtiger_tab ON vtiger_relatedlists.tabid = vtiger_tab.tabid where vtiger_relatedlists.presence = 0 AND vtiger_relatedlists.label=?";
			$params2 = array("Activities");
			$result2 = $adb->pquery($query2 , $params2);
			$numrows = $adb->num_rows($result2);
			$ActivitiesModule = array();
			for($i=0;$i<$numrows;$i++){
				$ActivitiesModule[] = $adb->query_result($result2,$i,'name');
			}
			
			$query2 = "SELECT vtiger_tab.name, vtiger_tab.tabid FROM vtiger_relatedlists INNER JOIN vtiger_tab ON vtiger_relatedlists.tabid = vtiger_tab.tabid where vtiger_relatedlists.presence = 0 AND vtiger_relatedlists.label=?";
			$params2 = array("Activities");
			$result2 = $adb->pquery($query2 , $params2);
			$numrows = $adb->num_rows($result2);
			$ActivitiesModule = array();
			for($i=0;$i<$numrows;$i++){
				$ActivitiesModule[] = $adb->query_result($result2,$i,'name');
			}
			
			$query3 = "SELECT * FROM  `vtiger_tab` WHERE  `isentitytype` =1 AND  `presence` =0";
			$result3 = $adb->pquery($query3 ,array());
			$numrows = $adb->num_rows($result3);
			$SummaryModule = array();
			for($i=0;$i<$numrows;$i++){
				$Module = $adb->query_result($result3,$i,'name');
				$moduleModel = Vtiger_Module_Model::getInstance($Module); 
				if($moduleModel->isSummaryViewSupported()) {
					$SummaryModule[] = $Module;
				}else{
					continue;
				}
			}
			$results['AccessModule'] = array('CommentsModule'=>$CommentsModule,'ActivitiesModule'=>$ActivitiesModule,'SummaryModule'=>$SummaryModule);
			$response->setResult($results);
		}
	}
	
	function getImageURL($modulename) {
		global $adb,$site_URL;
		$img_url = '';	
		$filename = 'modules/CTMobile/MobileIcon/'.$modulename.'.png';
		
		if (file_exists($filename)) {
			$img_url = $site_URL.'modules/CTMobile/MobileIcon/'.$modulename.'.png';
		}	
		return $img_url;	
	}

	public function getAllVisibleModules($user_type) {
		$current_user = $this->getActiveUser();
		$listresult = vtws_listtypes(null,$current_user);
		$menuModelsList = Vtiger_Menu_Model::getAll(true);
		$modules[vtranslate('Other Modules','Vtiger')]=$modules[vtranslate('LBL_TOOLS','Vtiger')]=$modules[vtranslate('LBL_PROJECT','Vtiger')]=$modules[vtranslate('LBL_SUPPORT','Vtiger')]=$modules[vtranslate('LBL_INVENTORY','Vtiger')]=$modules[vtranslate('LBL_SALES','Vtiger')]=$modules[vtranslate('LBL_MARKETING','Vtiger')]= array();
		$presence = array('0', '2');
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_app2tab WHERE visible = ? ORDER BY appname,sequence', array(1));
		$count = $db->num_rows($result);
		$userPrivModel = Users_Privileges_Model::getInstanceById($current_user->id);
		
		if ($count > 0) {
			for ($i = 0; $i < $count; $i++) {
				$appname = $db->query_result($result, $i, 'appname');
				$tabid = $db->query_result($result, $i, 'tabid');
				$sequence = $db->query_result($result, $i, 'sequence');
				$moduleName = getTabModuleName($tabid);
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
				$restrictedModule = array('CTMobile','Rss','Portal','RecycleBin','ExtensionStore','CTPushNotification','EmailTemplates','CTAttendance');
				if (empty($moduleModel))
					continue;
				if (in_array($moduleModel->get('name'),$restrictedModule))
					continue;
				$moduleModel->set('app2tab_sequence', $sequence);
				if (($userPrivModel->isAdminUser() ||
						$userPrivModel->hasGlobalReadPermission() ||
						$userPrivModel->hasModulePermission($moduleModel->getId())) && in_array($moduleModel->get('presence'), $presence)) {
					
					$view = 'List';
					$module = $moduleModel->get('name');
					$ModulesArray = array('SMSNotifier','PBXManager','CTPushNotification','CTCalllog','CTAttendance');
					if(in_array($module,$ModulesArray)){
						$QuickCreateAction = false;
						$editAction = false;
						$createAction = false;
					}else{
						$QuickCreateAction = $moduleModel->isQuickCreateSupported();
						$editAction = $userPrivModel->hasModuleActionPermission($moduleModel->getId(), 'EditView');
						$createAction = $userPrivModel->hasModuleActionPermission($moduleModel->getId(), 'CreateView');
					}
					
					
					
					$singular = vtranslate($moduleModel->get('name'),$module);
					if($appname == ''){
						$appname ='Other Modules';
					}
					$appname = vtranslate('LBL_'.$appname,'Vtiger');
					//allow access false when user type Free
					$restrictedModules = array('SMSNotifier','CTUserFilterView','CTPushNotification','CTAttendance','CTCalllog');
					if(in_array($module,$restrictedModules) && $user_type == 'free'){     
						$module_access = false;
					}else{
						$module_access = true;
					}
					$modules[$appname][] = array(
						'id'=> $moduleModel->get('id'),
						'name' => trim($moduleModel->get('name')),
						'isEntity' => $moduleModel->get('isentitytype'),
						'label' => vtranslate($moduleModel->get('label'),$module),
						'singular' => $singular,
						'parent' => $appname,
						'view' => $view,
						'img_url' => $this->getImageURL($moduleModel->get('name')),
						'module_access' => $module_access,
						'createAction' => $createAction,
						'editAction' => $editAction,
						'QuickCreateAction'=>$QuickCreateAction
						);
				}	
			}
			$moduleModel = Vtiger_Module_Model::getInstance('MailManager');
			$QuickCreateAction = false;
			if(array_key_exists("MailManager",$menuModelsList) && ($userPrivModel->isAdminUser() ||
						$userPrivModel->hasGlobalReadPermission() ||
						$userPrivModel->hasModulePermission($menuModelsList['MailManager']->get('id'))) && in_array($menuModelsList['MailManager']->get('presence'), $presence) ){
				$editAction = $userPrivModel->hasModuleActionPermission($menuModelsList['MailManager']->get('id'), 'EditView');
				$createAction = $userPrivModel->hasModuleActionPermission($menuModelsList['MailManager']->get('id'), 'CreateView');
				$modules['Other Modules'][] = array(
				'id'=> $menuModelsList['MailManager']->get('id'),
				'name' => $menuModelsList['MailManager']->get('name'),
				'isEntity' => $menuModelsList['MailManager']->get('isentitytype'),
				'label' => vtranslate($menuModelsList['MailManager']->get('label'),'MailManager'),
				'singular' => $moduleModel->get('label'),
				'parent' => 'Other Modules',
				'view' => 'List',
				'img_url' => $this->getImageURL('MailManager'),
				'module_access' => true,
				'createAction' => false,
				'editAction' => false,
				'QuickCreateAction'=>$QuickCreateAction
				);
			}
			if($user_type != 'free'){
				$moduleModel = Vtiger_Module_Model::getInstance('CTUserFilterView');
				$QuickCreateAction = false;
				if(($userPrivModel->isAdminUser() ||
							$userPrivModel->hasGlobalReadPermission() ||
							$userPrivModel->hasModulePermission($moduleModel->get('id'))) && $moduleModel->get('presence') == 0){
					$editAction = $userPrivModel->hasModuleActionPermission($moduleModel->get('id'), 'EditView');
					$createAction = $userPrivModel->hasModuleActionPermission($moduleModel->get('id'), 'CreateView');
					$modules['Other Modules'][] = array(
					'id'=> $moduleModel->get('id'),
					'name' => $moduleModel->get('name'),
					'isEntity' => $moduleModel->get('isentitytype'),
					'label' => vtranslate($moduleModel->get('label'),'CTUserFilterView'),
					'singular' => $moduleModel->get('label'),
					'parent' => 'Other Modules',
					'view' => 'List',
					'img_url' => $this->getImageURL('CTUserFilterView'),
					'module_access' => true,
					'createAction' => false,
					'editAction' => false,
					'QuickCreateAction'=>$QuickCreateAction
					);
				}
			}
			$moduleModel = Vtiger_Module_Model::getInstance('Documents');
			$QuickCreateAction = $moduleModel->isQuickCreateSupported();
			if(array_key_exists("Documents",$menuModelsList) && ($userPrivModel->isAdminUser() ||
						$userPrivModel->hasGlobalReadPermission() ||
						$userPrivModel->hasModulePermission($menuModelsList['Documents']->get('id'))) && in_array($menuModelsList['Documents']->get('presence'), $presence)){
				$editAction = $userPrivModel->hasModuleActionPermission($menuModelsList['Documents']->get('id'), 'EditView');
				$createAction = $userPrivModel->hasModuleActionPermission($menuModelsList['Documents']->get('id'), 'CreateView');
				$modules['Other Modules'][] = array(
				'id'=> $menuModelsList['Documents']->get('id'),
				'name' => $menuModelsList['Documents']->get('name'),
				'isEntity' => $menuModelsList['Documents']->get('isentitytype'),
				'label' => vtranslate($menuModelsList['Documents']->get('label'),'Documents'),
				'singular' => $moduleModel->get('label'),
				'parent' => 'Other Modules',
				'view' => 'List',
				'img_url' => $this->getImageURL('Documents'),
				'module_access' => true,
				'createAction' => $createAction,
				'editAction' => $editAction,
				'QuickCreateAction'=>$QuickCreateAction
				);
			}
			$moduleModel = Vtiger_Module_Model::getInstance('Calendar');
			$QuickCreateAction = $moduleModel->isQuickCreateSupported();
			if(array_key_exists("Calendar",$menuModelsList) && ($userPrivModel->isAdminUser() ||
						$userPrivModel->hasGlobalReadPermission() ||
						$userPrivModel->hasModulePermission($menuModelsList['Calendar']->get('id'))) && in_array($menuModelsList['Calendar']->get('presence'), $presence)){
				$editAction = $userPrivModel->hasModuleActionPermission($menuModelsList['Calendar']->get('id'), 'EditView');
				$createAction = $userPrivModel->hasModuleActionPermission($menuModelsList['Calendar']->get('id'), 'CreateView');
				$modules['Other Modules'][] = array(
				'id'=> $menuModelsList['Calendar']->get('id'),
				'name' => $menuModelsList['Calendar']->get('name'),
				'isEntity' => $menuModelsList['Calendar']->get('isentitytype'),
				'label' => vtranslate($menuModelsList['Calendar']->get('label'),'Calendar'),
				'singular' => 'Task',
				'parent' => 'Other Modules',
				'view' => 'Calendar',
				'img_url' => $this->getImageURL('Calendar'),
				'module_access' => true,
				'createAction' => $createAction,
				'editAction' => $editAction,
				'QuickCreateAction'=>$QuickCreateAction
				);
			}

		}
		foreach($modules as $key => $value){
			if(count($value) > 0){
			}else{
				unset($modules[$key]);
			}
		}
		return $modules;
	}	
		
	

	
}
