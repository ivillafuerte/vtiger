<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

class CTMobileSettings_TeamTracking_View extends Settings_Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULES', $module);
        $viewer->assign('LICENSE_DATA', CTMobileSettings_Module_Model::getLicenseData());
        $users = CTMobileSettings_Module_Model::getCTRouteUser();
        $viewer->assign('ROUTE_USER', $users);
        $searchApi=$adb->pquery("SELECT * FROM `ctmobile_api_settings`",array());
		$Api = '';
		if($adb->num_rows($searchApi)>0) {
            $Api = $adb->query_result($searchApi,0,'api_key');
        }
        $viewer->assign('API_KEY', $Api);
        echo $viewer->view('CTMobileTeamTracking.tpl',$module,true); 
    }   

   

    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            "modules.CTMobileSettings.resources.CTMobileSettings",
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}
