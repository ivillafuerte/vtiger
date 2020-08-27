<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

class CTMobileSettings_Details_View extends Settings_Vtiger_Index_View {

    public function process(Vtiger_Request $request) {
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULES', $module);
        $viewer->assign('LICENSE_DATA', CTMobileSettings_Module_Model::getLicenseData());
        $users = CTMobileSettings_Module_Model::getCTRouteUser();
        $activeuser = CTMobileSettings_Module_Model::getActiveUser();
        $viewer->assign('ROUTE_USER', $users);
        $viewer->assign('ACTIVE_USER', $activeuser);
        $version=$adb->pquery("SELECT * FROM vtiger_tab where name='CTMobileSettings'",array());
        $ver = $adb->query_result($version,0,'version');
        $url = CTMobileSettings_Module_Model::$CTMOBILE_VERSION_URL;
		if(extension_loaded('Curl')){
			
			$ch = curl_init($url);
			$data = array( "vt_version"=>'7.x');
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($ch);
			curl_close($ch);
			$jason_result = json_decode($result);
			$ext_ver = $jason_result->ext_version;
			$viewer->assign('VERSION', $ver);
			 $viewer->assign('ext_ver', $ext_ver);
			echo $viewer->view('CTMobileDetails.tpl',$module,true); 
			
		}else{
			echo "Please enable curl on your server";
			
		}
        
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
