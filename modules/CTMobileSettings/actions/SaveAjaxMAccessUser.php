<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
class CTMobileSettings_SaveAjaxMAccessUser_Action extends Vtiger_Save_Action {
    public function process(Vtiger_Request $request) {
        global $adb;
        $fields=$request->get("fields");
        // Clear data
        $adb->pquery("DELETE FROM `ctmobile_access_users`",array());
        // Save selected fields
        if(is_array($fields)) {
            foreach($fields as $field) {
                $adb->pquery("INSERT INTO `ctmobile_access_users` (`userid`) VALUES (?)",array($field));
            }
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array('msg'=>vtranslate('CTMobile Access User Saved Successfully','CTMobileSettings')));
        $response->emit();
    }
}
