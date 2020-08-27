<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once ('modules/Corrensa/api/BaseModule.php');
require_once ('modules/Vtiger/models/Record.php');

class API_User extends BaseModule {

    public function profileImage(Vtiger_Request $request)
    {
        $recordId = $request->get('id');
        $moduleName = 'Users';

        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
        $image = $recordModel->getImageDetails();
        if (empty($image)) return false;

        $file = realpath(dirname(__DIR__).'../../../').'/'.$image[0]['path'].'_'.$image[0]['name'];
        $file_extension = end(explode('.',$image[0]['name']));

        switch( $file_extension ) {
            case "gif": $mime="image/gif"; break;
            case "png": $mime="image/png"; break;
            case "jpeg":
            case "jpg": $mime="image/jpeg"; break;
        }

        header('Content-Type:'.$mime);
        header('Content-Length: ' . filesize($file));
        return readfile($file);
    }
}