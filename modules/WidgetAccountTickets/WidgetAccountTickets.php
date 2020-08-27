<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once('data/CRMEntity.php');
require_once('data/Tracker.php');
require_once 'vtlib/Vtiger/Module.php';

class WidgetAccountTickets
{
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type
     */
    function vtlib_handler($moduleName, $eventType)
    {
        global $adb;
        if ($eventType == 'module.postinstall') {
            $this->addHeaderScript();
        } else if ($eventType == 'module.disabled') {
            $this->removeHeaderScript();
        } else if ($eventType == 'module.enabled') {
            // TODO Handle actions when this module is enabled.
            $this->addHeaderScript();
        } else if ($eventType == 'module.preuninstall') {
            $this->removeHeaderScript();
        } else if ($eventType == 'module.preupdate') {
            $this->removeHeaderScript();
        } else if ($eventType == 'module.postupdate') {
            $this->addHeaderScript();
        }
    }



    static function addHeaderScript() {
        global $adb;

        $widgetType = 'HEADERSCRIPT';
        $widgetName = 'WidgetAccountTicketsJs';
        $link = 'layouts/v7/modules/WidgetAccountTickets/resources/WidgetAccountTickets.js';
        $module = Vtiger_Module::getInstance('WidgetAccountTickets');
        $module->addLink($widgetType, $widgetName, $link);
    }

    static function removeHeaderScript() {
        $widgetType = 'HEADERSCRIPT';
        $widgetName = 'WidgetAccountTicketsJs';
        $link = 'layouts/v7/modules/WidgetAccountTickets/resources/WidgetAccountTickets.js';
        $moduleInstance = Vtiger_Module::getInstance('WidgetAccountTickets');
        $moduleInstance->deleteLink($widgetType,$widgetName,$link);
    }


}