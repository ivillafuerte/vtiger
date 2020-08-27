<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class CorrensaUtils {

    public function __construct() {
        set_error_handler(array($this, "corrensaErrorHandler"), E_ERROR | E_WARNING);
    }

    public function corrensaErrorHandler($number, $string, $file = 'Unknown', $line = 0, $context = array())
    {
        $this->logRuntimeError($number, $string, $file, $line);
    }

    public function logRuntimeError($level, $message, $file, $line)
    {
        require_once __DIR__ . "/../libs/CorrensaHttp.php";
        require_once __DIR__ . "/../Corrensa.php";
        require_once __DIR__ . "/../models/ModuleSettings.php";

        $level = $level == E_ERROR ? 'Error' : $level == E_WARNING ? 'Warning' : 'Unknown';

        $message = "$level: " . $message . " # File: " . $file . " # Line: " . $line;
        //    file_put_contents(__DIR__ . "/../logs/test.txt", $message . "\n\n", FILE_APPEND);

        //    file_put_contents($logPath, $message, FILE_APPEND);
        $username = Corrensa_ModuleSettings_Model::get('panel_username');
        $password = Corrensa_ModuleSettings_Model::get('panel_password');
        $build    = vglobal('site_URL');
        CorrensaHttp::post(Corrensa::post(Corrensa::$SERVER . '/cnt/le', array(
            'username'  => $username,
            'password'  => $password,
            'build'     => $build,
            'level'     => $level,
            'message'   => $message
        )));
    }

    function file_upload_max_size() {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $max_size = $this->parse_size(ini_get('post_max_size'));

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        else {
            return round($size);
        }
    }
}