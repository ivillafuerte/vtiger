<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class BaseModule {

    public function displayJson($data) {
        header('Content-type: text/json; charset=UTF-8');
        $output = array(
            'success'   => 1,
            'data'      => $data,
            'msg'       => ''
        );
        echo Zend_Json::encode($output);
        exit();
    }

    public function displayError($msg, $code = '') {
        header('Content-type: text/json; charset=UTF-8');
        $output = array(
            'success'   => 0,
            'data'      => array(),
            'msg'       => $msg,
            'code'      => $code
        );
        echo Zend_Json::encode($output);
        exit();
    }

}