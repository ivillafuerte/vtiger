<?php
/* ********************************************************************************
 * The content of this file is subject to the Global Search ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once('modules/Corrensa/Corrensa.php');
require_once('modules/Corrensa/libs/CorrensaHttp.php');

class Corrensa_Settings_View extends Settings_Vtiger_Index_View
{

    function __construct()
    {
        parent::__construct();
    }

    function canConnectCorrensa()
    {
        $connected = fopen("https://dashboard.vtexperts.com/", "r");
        if ($connected) {
            return true;
        } else {
            return false;
        }
    }

    function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);

        $currentUser = Users_Record_Model::getCurrentUserModel();
        $connected = Corrensa_ModuleSettings_Model::get('panel_connected');
        $buildUrl = vglobal('site_URL');

        $settingData = Corrensa_ModuleSettings_Model::getAll();

        $viewData = array(
            'settings' => $settingData
        );

        $synchingStatus = Corrensa_ModuleSettings_Model::get('synching-status');

        // Check requirements
        $errors = array();
        $issueCount = 0;
        # Curl
        if(!extension_loaded('curl')) {
            $errors['curl'] = array(
                'label' => 'php_curl',
                'text'  => 'This php library is required to properly run Corrensa. Please click <a target="_blank" href="https://www.corrensa.com/how-to-enable-php-curl/">here</a> for instructions & more information.',
                'pass'  => false
            );
            $issueCount += 1;
        } else {
            $errors['curl'] = array(
                'label' => 'php_curl',
                'pass'  => true
            );
        }

        # Mcrypt
        if(!extension_loaded('mcrypt')) {
            $errors['mcrypt'] = array(
                'label' => 'php_mcrypt',
                'text'  => 'This php library is required to properly run Corrensa. Please click <a target="_blank" href="https://www.corrensa.com/how-to-enable-php-mcrypt">here</a> for instructions & more information.',
                'pass'  => false
            );
            $issueCount += 1;
        } else {
            $errors['mcrypt'] = array(
                'label' => 'php_mcrypt',
                'pass'  => true
            );
        }

        # Outgoing traffic
        if($errors['curl']['pass']) {
            if(!$this->canConnectCorrensa()) {
                $crmName = Corrensa::$CODE == 'sa' ? "CRM" : "VTiger";
                $errors['out_traffic'] = array(
                    'label' => 'Outgoing connection',
                    'text'  => 'Your '.$crmName.' webaddress is a local/internal IP address. In order to use Corrensa - your '.$crmName.' must have an outside domain or IP address that it can communicate with Corrensa servers. Please click <a target="_blank" href="https://www.corrensa.com/how-to-setup-corrensa-on-local-setup/">here</a> for instructions & more information.',
                    'pass'  => false
                );
                $issueCount += 1;
            } else {
                $errors['out_traffic'] = array(
                    'label' => 'Outgoing connection',
                    'pass'  => true
                );
            }
        }

        # Incoming traffic
        if($errors['curl']['pass']) {
            $pingResult = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/test-in-traffic', array('url' => $buildUrl));
        }

        if(!$errors['curl']['pass'] || empty($pingResult) || !$pingResult['success']) {
            $errors['in_traffic'] = array(
                'label' => 'Incoming connection',
                'text'  => 'It appears that your VTiger is behind the firewall. In order to use Corrensa - your VTiger must be able to communicate with Corrensa servers. Please click <a target="_blank" href="https://www.corrensa.com/how-to-setup-corrensa-behind-firewall/">here</a> for instructions & more information.',
                'pass'  => false
            );
            $issueCount += 1;
        } else {
            $errors['in_traffic'] = array(
                'label' => 'Incoming connection',
                'pass'  => true
            );
        }

        # Error reporting
        $currReportingLevel = error_reporting();
        if($currReportingLevel > 1) {
            $errors['error_report'] = array(
                'label' => 'Error reporting level',
                'text'  => 'Current error_reporting level is not recommended. Please click <a target="_blank" href="https://www.corrensa.com/proper-error-reporting-level/">here</a> for instructions & more information.',
                'pass'  => false
            );
            $issueCount += 1;
        } else {
            $errors['error_report'] = array(
                'label' => 'Error reporting level',
                'pass'  => true
            );
        }

        # Check version
        $currentModuleVersion = file_get_contents(Corrensa::$SERVER."/module-version.txt");
        $isOld = version_compare(Corrensa::$VERSION, $currentModuleVersion) == -1;

        if($isOld) {
            $errors['module_version'] = array(
                'label' => 'Module version',
                'text'  => "Please update to the latest version of the software <a href='https://www.corrensa.com/corrins/vtigercorrensa.zip' target='_blank'>here</a>",
                'pass'  => false
            );
        } else {
            $errors['module_version'] = array(
                'label' => 'Module version',
                'pass'  => true
            );
        }


        // If corrensa connected, then check token
        if (!$errors['curl']['pass']) {
            if($connected) {
                $pong = $this->ping();

                // If token not available then auto logout current user
                if (!$pong) {
                    Corrensa_ModuleSettings_Model::set('panel_username', '');
                    Corrensa_ModuleSettings_Model::set('panel_password', '');
                    Corrensa_ModuleSettings_Model::set('panel_token', '');
                    Corrensa_ModuleSettings_Model::set('panel_connected', '0');
                }
            }
        }

        $enableSupport = Corrensa_ModuleSettings_Model::get('enable_support');

        $viewer->assign('EN_SP', $enableSupport);
        $viewer->assign('REQUIREMENTS', $errors);
        $viewer->assign('TOTAL_ISSUES', $issueCount);
        $viewer->assign('SYNC_STATUS', $synchingStatus);
        $viewer->assign('USER_IS_ADMIN', ($currentUser->is_admin == 'on'));
        $viewer->assign('DATA', $viewData);
        $viewer->assign('CORR_URL', Corrensa::$SERVER);

        echo $viewer->view('Settings.tpl', $module, true);
    }

    function ping()
    {
        $token = Corrensa_ModuleSettings_Model::get('panel_token');
        $postData = array(
            'tk' => $token,
//            'XDEBUG_SESSION_START' => 'PHPSTORM'
        );
        $result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/ping', $postData);

        if ($result) {
            if ($result['success'] == 0 || $result['msg'] == 'Invalid Token') {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            "modules.$moduleName.resources.js.Settings",
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);

        if(Vtiger_Version::check('7.0.0', '>=')) {
            $cssFileNames = array(
                '~layouts/v7/modules/Corrensa/resources/css/settings.css'
            );
        } else {
            $cssFileNames = array(
                '~layouts/vlayout/modules/Corrensa/resources/css/settings.css'
            );
        }

        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }
}