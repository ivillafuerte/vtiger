<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
require_once('modules/Corrensa/actions/SettingAjax.php');

class Corrensa_Uninstall_Action extends Settings_Vtiger_Index_Action {


	/**
	 * Settings_LayoutEditor_Field_Action constructor.
	 */
	public function __construct()
	{
		$this->exposeMethod('approve');
	}

	function process(Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	function checkPermission(Vtiger_Request $request)
	{
		return true;
	}

	public function approve() {
		global $adb;

		// ajax disconnect first unistall
		$ajaxAction = new Corrensa_SettingAjax_Action();
		$ajaxAction -> disconnect();

		$module = Vtiger_Module::getInstance('Corrensa');

		// remove directory
		$this->deleteDir('layouts/vlayout/modules/Corrensa');

		$this->deleteDir('modules/Corrensa');

		// Remove link
		$module->deleteLink('HEADERSCRIPT', 'CorrensaGlobalJS', 'layouts/vlayout/modules/Corrensa/resources/js/Corrensa.js');

		// Remove setting menu
		$adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array('Corrensa'));

		// drop tables
		$sql = "DROP TABLE `corrensa_settings`";

		$adb->pquery($sql,array());

		// Uninstall module
		if ($module) $module->delete();

		header("Location: index.php?module=ModuleManager&parent=Settings&view=List");
	}

	public function deleteDir($dirPath) {
		if (! is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				$this->deleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
	}
}