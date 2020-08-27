<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class Corrensa_ModuleSettings_Model {

	public static function getAll() {
        $adb = new PearDatabase();

		$query = "SELECT * FROM corrensa_settings";
		$settings = array();

		$rs = $adb->pquery($query, array());

		if($adb->num_rows($rs) > 0) {
			while($row = $adb->fetchByAssoc($rs)) {
				$settings[$row['key']] = $row['value'];
			}
		}

		$adb->disconnect();

		return $settings;
	}

	public static function get($key) {
        $adb = new PearDatabase();

		$query = "SELECT * FROM corrensa_settings WHERE `key` = ?";

		$rs = $adb->pquery($query, array($key));

		if($adb->num_rows($rs) == 0) {
			return false;
		}

		$firstRow = $adb->fetchByAssoc($rs);

        $adb->disconnect();

        return $firstRow['value'];
	}

	public static function set($key, $value) {
        $adb = new PearDatabase();

        $query = "INSERT INTO corrensa_settings(`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?";
        $adb->pquery($query, array($key, $value, $value));
        $adb->disconnect();
	}

}