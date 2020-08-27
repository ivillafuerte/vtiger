<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

require_once('data/CRMEntity.php');
require_once('data/Tracker.php');
require_once 'vtlib/Vtiger/Module.php';

class CTMobileSettings extends CRMEntity {
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    function vtlib_handler($modulename, $event_type) {
        if($event_type == 'module.postinstall') {
            self::addWidgetTo();
            self::iniData();         
        } else if($event_type == 'module.disabled') {
            // TODO Handle actions when this module is disabled.
            self::removeWidgetTo();
        } else if($event_type == 'module.enabled') {
            // TODO Handle actions when this module is enabled.
            self::addWidgetTo();
        } else if($event_type == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } else if($event_type == 'module.preupdate') {
			self::deletelink(); 
            // TODO Handle actions before this module is updated.
        } else if($event_type == 'module.postupdate') {
            self::addWidgetTo(); 
            self::deletelink(); 
			self::CTmobileUserAccess();
			
        }
    }
    
     static function deletelink(){
		 global $adb;
		 $adb->pquery("DELETE FROM vtiger_links WHERE linklabel = 'CTMobileSettingsJs'",array());
	}

    static function iniData() {
        global $adb;
        $allModules = Vtiger_Module_Model::getSearchableModules();
        $searchModules=array_keys($allModules);
        foreach($searchModules as $module) {
            $adb->pquery("INSERT INTO `ctmobile_address_modules` (`module`, `active`) VALUES (?, ?)",array($module,'1'));
        }
        //Insert Contact Address Fields
        $adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Contacts","vtiger_contactaddress:mailingstreet:mailingstreet:Contacts_Mailing_Street:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Contacts","vtiger_contactaddress:mailingcity:mailingcity:Contacts_Mailing_City:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Contacts","vtiger_contactaddress:mailingstate:mailingstate:Contacts_Mailing_State:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Contacts","vtiger_contactaddress:mailingcountry:mailingcountry:Contacts_Mailing_Country:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Contacts","vtiger_contactaddress:mailingzip:mailingzip:Contacts_Mailing_Zip:V"));
		//Insert Accounts Address Fields
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Accounts","vtiger_accountshipads:ship_street:ship_street:Accounts_Shipping_Address:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Accounts","vtiger_accountshipads:ship_city:ship_city:Accounts_Shipping_City:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Accounts","vtiger_accountshipads:ship_state:ship_state:Accounts_Shipping_State:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Accounts","vtiger_accountshipads:ship_country:ship_country:Accounts_Shipping_Country:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Accounts","vtiger_accountshipads:ship_code:ship_code:Accounts_Shipping_Code:V"));
		//Insert Leads Address Fields
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Leads","vtiger_leadaddress:lane:lane:Leads_Street:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Leads","vtiger_leadaddress:city:city:Leads_City:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Leads","vtiger_leadaddress:state:state:Leads_State:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Leads","vtiger_leadaddress:country:country:Leads_Country:V"));
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Leads","vtiger_leadaddress:code:code:Leads_Postal_Code:V"));
		//Insert Calendar Address Fields
		$adb->pquery("INSERT INTO ctmobile_address_fields (module,fieldname) VALUES (?,?)",array("Calendar","vtiger_activity:location:location:Events_Location:V"));
		
    }
	
	static function CTmobileUserAccess() {
        global $adb;
		
		$ctmobile_access_Users =$adb->pquery("SELECT * FROM `ctmobile_access_users` ",array());
		if($adb->num_rows($ctmobile_access_Users) == 0 ){
			//Insert Defaullt Users in ctmobile_access_users Table
			$Users =$adb->pquery("SELECT * FROM `vtiger_users` WHERE deleted = 0 AND status = ?",array('Active'));
			$userArray = array();
			for($i=0;$i<($adb->num_rows($Users));$i++){
			$id = $adb->query_result($Users,$i,'id');
			$adb->pquery("INSERT INTO `ctmobile_access_users` (`userid`) VALUES (?)",array($id));
			}
			
		}
		
    }

 
    /**
     * Add CTMobileSettings Widgets at CRM Settings > Other Settings
     * parameters None.
     * @return None
     */
    static function addWidgetTo() {
        global $adb;
		$adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?",array('CTMobileSettings'));
		$rsBlock=$adb->pquery("SELECT blockid FROM `vtiger_settings_blocks` WHERE label='LBL_OTHER_SETTINGS'",array());
		$blockid=$adb->query_result($rsBlock, 0, 'blockid');
        $max_id=$adb->getUniqueID('vtiger_settings_field');
        $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)",array($max_id,$blockid,'CTMobileSettings', 'CTMobileSettings', 'index.php?module=CTMobileSettings&parent=Settings&view=Details',$max_id));
    }
	/**
     * remove CTMobileSettings Widgets from CRM Settings > Other Settings
     * parameters None.
     * @return None
     */
    static function removeWidgetTo() {
        global $adb;
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?",array('CTMobileSettings'));
    }   
}
