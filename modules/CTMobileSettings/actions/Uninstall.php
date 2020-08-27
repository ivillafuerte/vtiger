<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

class CTMobileSettings_Uninstall_Action extends Vtiger_Save_Action {
    public function process(Vtiger_Request $request) {
        global $adb;
        global $site_URL;
        $Vtiger_Utils_Log = true;
		include_once('vtlib/Vtiger/Module.php');

        $array = array('CTAttendance','CTMessageTemplate','CTMobile','CTPushNotification','CTUserFilterView','CTMobileSettings');

        foreach ($array as $key => $value) {
            $module = Vtiger_Module::getInstance($value);
    		if($module) {
    		    $module->delete();
    		}
        }
        $getLicenseQuery = $adb->pquery("SELECT * FROM ctmobile_license_settings",array());
        $numOfLicenseCount = $adb->num_rows($getLicenseQuery);
		if($numOfLicenseCount > 0){
			$license_key=$adb->query_result($getLicenseQuery,0,'license_key');
			$domain=$adb->query_result($getLicenseQuery,0,'domain');
			$url = CTMobileSettings_Module_Model::$CTMOBILE_CHECKLICENSE_URL;
			$ch = curl_init($url);
			$data = array( "license_key"=>$license_key,"domain"=>$domain,"action"=>"deactivate");
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Send request.
			$result = curl_exec($ch);
			curl_close($ch);
			if($result == 'Deactivated'){
				$deleteLicense = $adb->pquery("DELETE FROM ctmobile_license_settings",array());
			}
		}
        $query ="DELETE FROM vtiger_settings_field WHERE name = 'CTMobileSettings'";
        $results = $adb->pquery($query);
        if ($results) {
            $result = $site_URL;
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array($result));
        $response->emit();
    }
}

?>
