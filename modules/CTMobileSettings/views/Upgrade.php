<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once('vtlib/Vtiger/Unzip.php');
class CTMobileSettings_Upgrade_View extends Settings_Vtiger_Index_View {
    
    public function process(Vtiger_Request $request){
		global $adb,$root_directory;
        $viewer = $this->getViewer($request);
        $qualifiedName = $request->getModule(false);
        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        $url = CTMobileSettings_Module_Model::$CTMOBILE_VERSION_URL;
        $ch = curl_init($url);
		$data = array( "vt_version"=>'7.x');
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);
		curl_close($ch);
		$jason_result = json_decode($result);
		$zip_url = $jason_result->ext_path;
		$ext_version = $jason_result->ext_version;
		mkdir($root_directory."/test/".$ext_version, 0777);
		$destination_path = $root_directory."/test/".$ext_version."/CTMobileupgrade.zip";
		file_put_contents($destination_path, fopen($zip_url, 'r'));
		chmod($root_directory."/test/".$ext_version."/CTMobileupgrade.zip",0755);
		
		chmod($root_directory."/test/".$ext_version."/",0777);
		$unzip = new Vtiger_Unzip($root_directory."/test/".$ext_version."/CTMobileupgrade.zip");
		$unzip->unzipAllEx($root_directory."/test/".$ext_version."/");
		
		$package = new Vtiger_Package();
		$package->update(Vtiger_Module::getInstance('CTMobile'),$root_directory."/test/".$ext_version.'/CTMobile.zip');
		$package->update(Vtiger_Module::getInstance('CTMobileSettings'),$root_directory."/test/".$ext_version.'/CTMobileSettings.zip');
		$package->update(Vtiger_Module::getInstance('CTUserFilterView'),$root_directory."/test/".$ext_version.'/CTUserFilterView.zip');
        $viewer->view('CTMobileDetails.tpl',$qualifiedName);  
    }
		
   function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			"modules.$moduleName.resources.CTMobileSettings",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
    }
}
    
