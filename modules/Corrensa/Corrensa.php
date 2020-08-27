<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class Corrensa
{
	public static $VERSION = '3.7.0';
	public static $SERVER = 'https://dashboard.vtexperts.com';
	public static $CODE = 'pri'; // pri | sa
	public static $ENABLE_LOG = false;

	public static $EVENT_USER_ADD = 'user_add';
	public static $EVENT_USER_UPDATE = 'user_update';
	public static $EVENT_FIELD_UPDATE = 'field_update';
	public static $EVENT_FIELD_ADD = 'field_add';
	public static $EVENT_FIELD_DELETE = 'field_delete';
    public static $SYNC_INPROGRESS = 'in-progress';
    public static $SYNC_UPDATING = 'updating';
    public static $SYNC_COMPLETED = 'completed';
	private static $ins;

	public static function getInstance() {
		if(self::$ins == null) self::$ins = new Corrensa();
		return self::$ins;
	}

	function vtlib_handler($moduleName, $eventType)
	{
		global $adb;

		$buildUrl = vglobal('site_URL');
		if ($eventType == 'module.postinstall') {
            $this->makeFileConfig();
            $this->addSettingMenu();
            $this->addGlobalJavascript();
            $this->makeModuleIcon();
            $this->createSettingTable();
            $this->createEmailTabs();
//            $this->subscribe();

        } else if ($eventType == 'module.disabled') {
			$this->removeSettingMenu();
			$this->removeGlobalJavascript();
			$this->disconnectCorrensa();

		} else if ($eventType == 'module.enabled') {
			$this->addSettingMenu();
			$this->addGlobalJavascript();
//			$this->subscribe();
		} else if ($eventType == 'module.preuninstall') {
            $this->disconnectCorrensa();
            $this->removeSettingMenu();
            $this->removeGlobalJavascript();
            $this->dropSettingTable();
            $this->unsubscribe();

        } else if ($eventType == 'module.preupdate') {
		} else if ($eventType == 'module.postupdate') {
            $this->makeFileConfig();
            $this->addSettingMenu();
            $this->addGlobalJavascript();
            $this->makeModuleIcon();
            $this->createSettingTable();
            $this->createEmailTabs();
            $this->cleanupCode();
//            $this->subscribe();
        }
	}

	function dropSettingTable() {
	    global $adb;

        $adb->pquery("DROP TABLE corrensa_settings", array());
    }

    function makeFileConfig() {
        $CORRENSA_ENCRYPT_KEY = md5(time());

        $filePath = 'modules/Corrensa/Config.php';
        $fileContent = "<?php
        interface Corrensa_Config {
            const CORRENSA_ENCRYPT_KEY = '$CORRENSA_ENCRYPT_KEY';
        }";

        $fileExist = file_exists($filePath);

        if(!$fileExist) {
            file_put_contents($filePath, $fileContent);
        }
    }

    function createEmailTabs() {
	    global $adb;

	    $values = array(
            array('32', 'Potentials'),
            array('32', 'HelpDesk')
        );

        foreach ($values as $value) {
            $check = "SELECT * FROM vtiger_ws_referencetype WHERE fieldtypeid = ? AND type = ?";
            $rs = $adb->pquery($check, $value);
            if($adb->num_rows($rs) == 0) {
                $adb->pquery("INSERT INTO `vtiger_ws_referencetype` (`fieldtypeid`, `type`) VALUES (?, ?)", $value);
            }
        }

        $values = array(
            array(13, 10),
            array(2, 10)
        );

        foreach ($values as $value) {
            $maxId = $adb->getUniqueID('vtiger_relatedlists');
            $check = "SELECT * FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ?";
            $rs = $adb->pquery($check, $value);
            if($adb->num_rows($rs) == 0) {
                $adb->pquery("INSERT INTO `vtiger_relatedlists` (`relation_id`, `tabid`, `related_tabid`, `name`, `sequence`, `label`, `presence`, `actions`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    array($maxId, $value[0], $value[1], 'get_dependents_list', 5, 'Emails', 0, '')
                );
            }
        }
    }

	function createSettingTable() {
		global $adb;

        $checkTableExisting = "SHOW TABLES LIKE 'corrensa_settings'";

        $checkResult = $adb->pquery($checkTableExisting, array());

        if($adb->num_rows($checkResult) == 0) {
            $query = "CREATE TABLE `corrensa_settings` (
                        `key` VARCHAR(50) NOT NULL,
                        `value` VARCHAR(128) NOT NULL,
                        PRIMARY KEY (`key`)
                    )
                    COLLATE='utf8_general_ci'
                    ENGINE=InnoDB;";

            $adb->pquery($query, array());

            $insert = "INSERT INTO `corrensa_settings` (`key`, `value`) VALUES
					('show_startup_screen', '1'),
					('panel_username', ''),
					('panel_password', ''),
					('panel_token', ''),
					('panel_connected', '0'),
					('synching-status', ''),
					('synching-progress', '0');";
            $adb->pquery($insert, array());
        }
	}

	function addSettingMenu()
	{
		$adb = PearDatabase::getInstance();

		$checkRs = $adb->pquery("SELECT * FROM vtiger_settings_field WHERE `name` = 'Corrensa'");

		if ($adb->num_rows($checkRs) > 0) {
			return false;
		}

		/* ADD SETTING MENU */
		$maxId = $adb->getUniqueID('vtiger_settings_field');
		$query = "INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`)
						  VALUES (?, ?, ?, ?, ?, ?)";
		$adb->pquery($query, array($maxId, '4', 'Corrensa', 'Corrensa Settings', 'index.php?module=Corrensa&parent=Settings&view=Settings', $maxId));
		/* ADD SETTING MENU */
	}

	function removeSettingMenu()
	{
		$adb = PearDatabase::getInstance();

		/*  REMOVE SETTING MENU */
		$adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array('Corrensa'));
		/*  REMOVE SETTING MENU */
	}

	function addGlobalJavascript()
	{
		global $adb;

		$widgetType = 'HEADERSCRIPT';
		$widgetName = 'CorrensaGlobalJS';
		$link = 'layouts/vlayout/modules/Corrensa/resources/js/Corrensa.js';
		include_once 'vtlib/Vtiger/Module.php';

		$checkRs = $adb->pquery("SELECT * FROM vtiger_links WHERE `linklabel` = '$widgetName';");

		if ($adb->num_rows($checkRs) > 0) return false;

		$module = Vtiger_Module::getInstance('Corrensa');
		if ($module) {
			$module->addLink($widgetType, $widgetName, $link);
		}
	}

	function removeGlobalJavascript()
	{
		global $adb;

		$widgetType = 'HEADERSCRIPT';
		$widgetName = 'CorrensaGlobalJS';
		$link = 'layouts/vlayout/modules/Corrensa/resources/js/Corrensa.js';
		include_once 'vtlib/Vtiger/Module.php';

		$checkRs = $adb->pquery("SELECT * FROM vtiger_links WHERE `linklabel` = '$widgetName';");

		if ($adb->num_rows($checkRs) == 0) return false;

		$module = Vtiger_Module::getInstance('Corrensa');
		if ($module) {
			$module->deleteLink($widgetType, $widgetName, $link);
		}
	}

	function subscribe() {
		$buildUrl = vglobal('site_URL');
		$buildUrl = trim($buildUrl, '/');
		$vtigerVersion = Vtiger_Version::current();

		$result = self::post(self::$SERVER . '/cnt/subs', array('url' => $buildUrl, 'vtiger' => $vtigerVersion, 'corrensa' => self::$VERSION));
		return $result;
	}

	function unsubscribe() {
		$buildUrl = vglobal('site_URL');
		$buildUrl = trim($buildUrl, '/');
		$vtigerVersion = Vtiger_Version::current();

		$result = self::post(self::$SERVER . '/cnt/unsubs', array('url' => $buildUrl, 'vtiger' => $vtigerVersion, 'corrensa' => self::$VERSION));
		return $result;
	}

	function cleanupCode() {
        @unlink('modules/Corrensa/libs/corrensaf.php');
        @unlink('modules/Corrensa/libs/corrensas.php');
        @unlink('modules/Corrensa/libs/cu.php');
    }

	function disconnectCorrensa() {
		$token = Corrensa_ModuleSettings_Model::get('panel_token');
		$result = $this->post(self::$SERVER . '/cnt/out',
			array(
				'tk'    => $token,
//				'XDEBUG_SESSION_START' => 'PHPSTORM'
			)
		);

		if($result && ($result['success'] || $result['msg'] == 'Invalid Token')) {
			Corrensa_ModuleSettings_Model::set('panel_username', '');
			Corrensa_ModuleSettings_Model::set('panel_password', '');
			Corrensa_ModuleSettings_Model::set('panel_token', '');
			Corrensa_ModuleSettings_Model::set('panel_connected', '0');

			$this->displayJson(array('success' => 1));
		} else {
			$this->displayJson(array('success' => 0));
		}
	}

	function makeModuleIcon() {
		$iconImageBase64 = 'data:image/png;base64,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';

		$img = str_replace('data:image/png;base64,', '', $iconImageBase64);
		$img = str_replace(' ', '+', $img);
		$data = base64_decode($img);
		$file = 'layouts/vlayout/skins/images/Corrensa.png';

        if(!file_exists($file)) @file_put_contents($file, $data);
	}

	public static function post($url, $params = array())
	{
		$stream = curl_init();
		curl_setopt($stream, CURLOPT_URL, $url);
		curl_setopt($stream, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($stream, CURLOPT_POST, 1);
		curl_setopt($stream, CURLOPT_POSTFIELDS, $params);
		curl_setopt($stream, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($stream, CURLOPT_SSL_VERIFYHOST, false);
		$output = curl_exec($stream);

		curl_close($stream);

		try {
			$outputData = json_decode($output, true);

			return $outputData;
		} catch (Exception $ex) {
			throw new Exception("Can not parse json | " . $ex->getMessage());
		}
	}

}
