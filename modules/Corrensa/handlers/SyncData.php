<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
require_once('modules/Corrensa/Corrensa.php');
require_once 'modules/Corrensa/models/VtigerData.php';
require_once 'modules/Corrensa/libs/CorrensaHttp.php';

class SyncData {

	public static function when_user_add($username) {
		$vtigerData = new Corrensa_VtigerData_Model();
		$user = $vtigerData->getUserByUsername($username);
		$permission = $vtigerData->getUserPermissionByUser($user['id']);

		$token  = Corrensa_ModuleSettings_Model::get('panel_token');

        $postData = array(
            'tk'    => $token,
            'uid'   => $user['id'],
            'ud'    => json_encode($user),
            'p'     => json_encode($permission),
//            'XDEBUG_SESSION_START' => 'PHPSTORM'
        );

		$result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/au', $postData);
	}

	public static function when_user_update($userId) {
		$vtigerData = new Corrensa_VtigerData_Model();
		$user = $vtigerData->getUserById($userId);
		$token  = Corrensa_ModuleSettings_Model::get('panel_token');

        $postData = array(
            'tk'    => $token,
            'uid'   => $userId,
            'ud'    => json_encode($user),
//            'XDEBUG_SESSION_START' => 'PHPSTORM'
        );
		$result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/uui', $postData);
	}

	public static function when_field_update($fieldId) {
		$vtigerData = new Corrensa_VtigerData_Model();
		$token  = Corrensa_ModuleSettings_Model::get('panel_token');
		$fieldInstance = Vtiger_Field_Model::getInstance($fieldId);
		$moduleName = $fieldInstance->getModuleName();

		$fieldDataAndPermission = $vtigerData->getFieldInfoAndPermission($moduleName, $fieldInstance->get('name'));
		$fieldPermission = $fieldDataAndPermission['permission'];
		$fieldData       = $fieldDataAndPermission['field'];

        $postData = array(
            'tk'    => $token,
            'fd'    => json_encode($fieldData),
            'p'     => json_encode($fieldPermission),
            'mdl'   => $moduleName,
//            'XDEBUG_SESSION_START' => 'PHPSTORM'
        );

		$result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/ufd', $postData);
	}

	public static function when_field_add($fieldLabel, $sourceModule) {
		$vtigerData = new Corrensa_VtigerData_Model();
		$token  = Corrensa_ModuleSettings_Model::get('panel_token');
		$fieldRecord = $vtigerData->getFieldByLabel($fieldLabel, $sourceModule);

		if(!$fieldRecord) {
			return false;
		}

		$fieldDataAndPermission = $vtigerData->getFieldInfoAndPermission($sourceModule, $fieldRecord['fieldname']);
		$fieldPermission = $fieldDataAndPermission['permission'];
		$fieldData       = $fieldDataAndPermission['field'];

        $postData = array(
            'tk'    => $token,
            'fd'    => json_encode($fieldData),
            'p'     => json_encode($fieldPermission),
            'mdl'   => $sourceModule,
//            'XDEBUG_SESSION_START' => 'PHPSTORM'
        );
		$result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/afd', $postData);
	}

	public static function when_field_delete($fieldId) {
		$vtigerData = new Corrensa_VtigerData_Model();
		$token  = Corrensa_ModuleSettings_Model::get('panel_token');
		$fieldInstance = Vtiger_Field_Model::getInstance($fieldId);
		$moduleName = $fieldInstance->getModuleName();

        $postData = array(
            'tk'    => $token,
            'mdl'   => $moduleName,
            'fdl'   => $fieldInstance->get('name'),
//            'XDEBUG_SESSION_START' => 'PHPSTORM'
        );

		$result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/dfd', $postData);
	}
}