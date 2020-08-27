<?php
/* ********************************************************************************
 * The content of this file is subject to the Global Search ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class Corrensa_ShowInfo_View extends Settings_Vtiger_Index_View
{

    function __construct()
    {
        parent::__construct();
    }


    function process(Vtiger_Request $request)
    {
        global $adb;
        $data = array();
        $sql = "SELECT * FROM corrensa_settings";
        $results = $adb->pquery($sql);
        if ($adb->num_rows($results)>0){
            while($item = $adb->fetch_row($results)){
                $data[$item['key']] = $item['value'];
            }
        }

        $tableShowInfo = "<table width=\"50%\" style=\"margin: 0 auto; margin-top: 50px; border: solid 1px #000 \">";
        foreach ($data as $key => $value){
            $tableShowInfo.= "<tr>";
            $tableShowInfo.= "<td style=\"border: solid 1px #000 \">{$key}</td>";
            $tableShowInfo.= "<td style=\"border: solid 1px #000 \">{$value}</td>";
            $tableShowInfo.= "</tr>";
        }

        $tableShowInfo.= "</table>";

        echo $tableShowInfo;
    }
}