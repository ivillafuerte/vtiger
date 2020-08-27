<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
require_once('include/utils/utils.php');

class ActionHandler extends VTEventHandler
{
    public function handleEvent($handlerType, $entityData)
    {
        global $log, $adb, $current_user;

//        if ($handlerType == 'vtiger.entity.aftersave') {
//            echo "<pre>";
//            print_r($entityData);
//            echo "</pre>";
//            exit();
//        }
    }

}