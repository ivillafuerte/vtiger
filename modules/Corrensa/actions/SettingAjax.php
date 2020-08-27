<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once('modules/Corrensa/Corrensa.php');
require_once('modules/Corrensa/libs/CorrensaHttp.php');
require_once('modules/Corrensa/libs/utils.php');

class Corrensa_SettingAjax_Action extends Vtiger_BasicAjax_Action
{

    private $supportUsername = "corrensasp1";

    function __construct()
    {
        parent::__construct();
        $this->exposeMethod('getSettings');
        $this->exposeMethod('loginToCorrensa');
        $this->exposeMethod('syncData');
        $this->exposeMethod('reSyncData');
        $this->exposeMethod('cancelSyncData');
        $this->exposeMethod('cancelUpdate');
        $this->exposeMethod('syncPermissionsData');
        $this->exposeMethod('disconnect');
        $this->exposeMethod('login');
        $this->exposeMethod('forgetPassword');
        $this->exposeMethod('closeStartupPopup');
        $this->exposeMethod('getImportProgress');
        $this->exposeMethod('reportIssue');
        $this->exposeMethod('syncPermissionData');
        $this->exposeMethod('enableSupport');
        $this->exposeMethod('disableSupport');
    }

    private function displayJson($data)
    {
        header('Content-type: text/json; charset=UTF-8');
        echo Zend_Json::encode($data);
    }

    function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
            return;
        }
    }

    function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    function getSettings()
    {
        $settings = Corrensa_ModuleSettings_Model::getAll();
        $response = new Vtiger_Response();
        $response->setResult($settings);
        $response->emit();
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    function loginToCorrensa($request)
    {
        $username = $request->get('username');
        $password = $request->get('password');
        $buildUrl = vglobal('site_URL');
        $vtigerData = new Corrensa_VtigerData_Model();

        $postData = array('username' => $username, 'password' => $password, 'build' => $buildUrl);
        $loginResult = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/lg', $postData);

        if ($loginResult['success']) {
            Corrensa_ModuleSettings_Model::set('panel_username', $username);
            Corrensa_ModuleSettings_Model::set('panel_password', $password);
            Corrensa_ModuleSettings_Model::set('panel_token', $loginResult['token']);
            Corrensa_ModuleSettings_Model::set('panel_connected', 1);
            Corrensa::getInstance()->subscribe();
            $this->displayJson($loginResult);
        } else {
            $this->displayJson(array('success' => 0, 'error' => array('message' => $loginResult['msg'])));
        }
    }

    function syncData($request)
    {
//        ignore_user_abort(true);
//        set_time_limit(420);

        $vtigerData = new Corrensa_VtigerData_Model();
//        $userCount = $vtigerData->getUserCount();
//        $baseMem = 80;
//        if($userCount > 0) {
//            $baseMem += ceil($userCount * 4);
//        }
//
        ini_set('memory_limit', '128M');

        $buildUrl = vglobal('site_URL');
        $synchingStatus = Corrensa_ModuleSettings_Model::get('synching-status');
        if ($synchingStatus != Corrensa::$SYNC_INPROGRESS) {
            Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_INPROGRESS);

            $currentUser = Users_Record_Model::getCurrentUserModel();

            $token = Corrensa_ModuleSettings_Model::get('panel_token');
            $modules = $vtigerData->getEntityModules();
            $fields = $vtigerData->getAllFields();
            $currencies = $vtigerData->getCurrencies();
            $taxes = $vtigerData->getTaxes();
            $users = $vtigerData->getUsers();

            $allFields = array();
            foreach ($fields as $moduleName => $moduleMeta) {
                foreach ($moduleMeta as $key => $value) {
                    $allFields[] = $value;
                }
            }

            // Remove signature from user data
            foreach ($users as $key => $user) {
                unset($users[$key]['signature']);
                unset($users[$key]['description']);
				$users[$key]['system_timezone'] = vglobal('default_timezone');
            }

            $adminData = array(
                'username' => $currentUser->get('user_name'),
                'uid' => '19x' . $currentUser->get('id'),
//                'userkey' => $currentUser->get('accesskey')
            );

            global $vtiger_current_version;

            $postData = array(
                'tk' => $token,
                'adm' => json_encode($adminData),
                'u' => $buildUrl,
                'm' => json_encode($modules),
                'f' => json_encode($fields),
                'c' => json_encode($currencies),
                't' => json_encode($taxes),
                'urs' => json_encode($users),
                'version' => $vtiger_current_version
            );


            $result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/pad', $postData);

            if ($result['success']) {
                Corrensa_ModuleSettings_Model::set('panel_connected', 1);
//                $this->syncPermissionsData($users, $token);
                Corrensa::getInstance()->subscribe();
                Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_COMPLETED);
                Corrensa_ModuleSettings_Model::set('synching-progress', '');

                $jsonOutput = array('users' => array());
                // Get user list
                $users = $vtigerData->getUsers();
                foreach ($users as $user) {
                    $jsonOutput['users'][] = $user['id'];
                }
                $jsonOutput['success'] = 1;
                $this->displayJson($jsonOutput);
            } else {
                Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_COMPLETED);
                Corrensa_ModuleSettings_Model::set('synching-progress', '');
                $this->displayJson(array('success' => 0, 'error' => array('message' => $result['msg'])));
            }
        } else {
            $this->displayJson(array('success' => 1));
        }
    }

    function reSyncData($request)
    {
//        ignore_user_abort(true);
//        set_time_limit(420);

        $vtigerData = new Corrensa_VtigerData_Model();
//        $userCount = $vtigerData->getUserCount();
//        $baseMem = 80;
//        if($userCount > 0) {
//            $baseMem += ceil($userCount * 4);
//        }
//
        ini_set('memory_limit', '128M');

        $buildUrl = vglobal('site_URL');
        $synchingStatus = Corrensa_ModuleSettings_Model::get('synching-status');
        if ($synchingStatus != Corrensa::$SYNC_INPROGRESS && $synchingStatus != Corrensa::$SYNC_UPDATING) {
            Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_UPDATING);

            $currentUser = Users_Record_Model::getCurrentUserModel();

            $token = Corrensa_ModuleSettings_Model::get('panel_token');
            $modules = $vtigerData->getEntityModules();
            $fields = $vtigerData->getAllFields();
            $currencies = $vtigerData->getCurrencies();
            $taxes = $vtigerData->getTaxes();
            $users = $vtigerData->getUsers();

            $allFields = array();
            foreach ($fields as $moduleName => $moduleMeta) {
                foreach ($moduleMeta as $key => $value) {
                    $allFields[] = $value;
                }
            }

            // Remove signature from user data
            foreach ($users as $key => $user) {
                unset($users[$key]['signature']);
                unset($users[$key]['description']);
				$users[$key]['system_timezone'] = vglobal('default_timezone');
			}

            $adminData = array(
                'username' => $currentUser->get('user_name'),
                'uid' => '19x' . $currentUser->get('id'),
                'userkey' => $currentUser->get('accesskey')
            );

            $postData = array(
                'tk' => $token,
                'adm' => json_encode($adminData),
                'u' => $buildUrl,
                'm' => json_encode($modules),
                'f' => json_encode($fields),
                'c' => json_encode($currencies),
                't' => json_encode($taxes),
                'urs' => json_encode($users)
//                'XDEBUG_SESSION_START' => 'PHPSTORM'
            );
            $result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/uad', $postData);

            if ($result['success']) {
                // Do processing here
                Corrensa_ModuleSettings_Model::set('panel_connected', 1);
//                $this->syncPermissionsData($users, $token);
                Corrensa::getInstance()->subscribe();
//                exit();
                Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_COMPLETED);
                Corrensa_ModuleSettings_Model::set('synching-progress', '');

                $jsonOutput = array('users' => array());
                // Get user list
                $users = $vtigerData->getUsers();
                foreach ($users as $user) {
                    $jsonOutput['users'][] = $user['id'];
                }
                $jsonOutput['success'] = 1;
                $this->displayJson($jsonOutput);
            } else {
                Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_COMPLETED);
                Corrensa_ModuleSettings_Model::set('synching-progress', '');
                $this->displayJson(array('success' => 0, 'error' => array('message' => $result['msg'])));
            }
        } else {
            $this->displayJson(array('success' => 1));
        }
    }

    public function syncPermissionData($request) {
//        set_time_limit(420);

        $vtigerData = new Corrensa_VtigerData_Model();

        $userId = $request->get('user_id');
        $userIds = $request->get('user_ids');
        $testDir = 'test/Corrensa';
        $cacheDir = $testDir . '/cache/';
        $token = Corrensa_ModuleSettings_Model::get('panel_token');

        if(empty($userId)) $userId = $userIds[0];

//        $hasCache = file_exists($cacheDir . 'modules');
//        if(!$hasCache) {
        $modules = $vtigerData->getEntityModules();
        $allFields = $vtigerData->getAllFields();

//            if(!file_exists($testDir)) {
//                mkdir($testDir);
//                mkdir($cacheDir);
//            }

//            file_put_contents($cacheDir . 'modules', json_encode($modules));
//            file_put_contents($cacheDir . 'all_fields', json_encode($allFields));
//        } else {
//            $modulesJson    = file_get_contents($cacheDir . 'modules');
//            $allFieldsJson  = file_get_contents($cacheDir . 'all_fields');
//            $modules        = json_decode($modulesJson, true);
//            $allFields      = json_decode($allFieldsJson, true);
//        }

        $userPermission = $vtigerData->getUserPermission($userId, $modules, $allFields);
        $postData = array(
            'tk' => $token,
            'p' => json_encode($userPermission)
        );
        CorrensaHttp::post(Corrensa::$SERVER . '/cnt/pap', $postData);

        $jsonOutput = array();

        $currIdx = array_search($userId, $userIds);
        if($currIdx + 1 == count($userIds)) {
            $jsonOutput['finish'] = 1;
            unlink($cacheDir . 'modules');
            unlink($cacheDir . 'all_fields');
        } else {
            $jsonOutput['finish'] = 0;
            $jsonOutput['next_id'] = $userIds[$currIdx+1];
            $jsonOutput['status'] = $currIdx+1;
        }

        $jsonOutput['success'] = 1;

        $this->displayJson($jsonOutput);
    }

    public function cancelSyncData()
    {
        Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_COMPLETED);
        Corrensa_ModuleSettings_Model::set('synching-progress', '');
        Corrensa_ModuleSettings_Model::set('panel_connected', 0);
        $this->displayJson(array('success' => 1));
    }

    public function cancelUpdate()
    {
        Corrensa_ModuleSettings_Model::set('synching-status', Corrensa::$SYNC_COMPLETED);
        Corrensa_ModuleSettings_Model::set('synching-progress', '');
        $this->displayJson(array('success' => 1));
    }

//    public function syncPermissionsData($users, $token)
//    {
//        $vtigerData = new Corrensa_VtigerData_Model();
//
//        $modules = $vtigerData->getEntityModules();
//        $allFields = $vtigerData->getAllFields();
//
//        $userCount = count($users);
//        for ($i = 0; $i < $userCount; $i++) {
//            $user = $users[$i];
//            $userPermission = $vtigerData->getUserPermission($user['id'], $modules, $allFields);
//            $postData = array(
//                'tk' => $token,
//                'p' => json_encode($userPermission)
//            );
//
//            if($userCount == $i+1) {
//                $postData['is-last'] = true;
//            }
//
//            CorrensaHttp::post(Corrensa::$SERVER . '/cnt/pap', $postData);
//        }
//    }

    public function getImportProgress()
    {
        $progress = Corrensa_ModuleSettings_Model::get('synching-progress');
        $this->displayJson(array('progress' => $progress));
    }

    function disconnect()
    {
        $token = Corrensa_ModuleSettings_Model::get('panel_token');
        $postData = array(
            'tk' => $token
        );
        $result = CorrensaHttp::post(Corrensa::$SERVER . '/cnt/out', $postData);

        if ($result && ($result['success'] || $result['msg'] == 'Invalid Token')) {
//            Corrensa_ModuleSettings_Model::set('panel_username', '');
//            Corrensa_ModuleSettings_Model::set('panel_password', '');
            Corrensa_ModuleSettings_Model::set('panel_token', '');
            Corrensa_ModuleSettings_Model::set('panel_connected', '0');
//            Corrensa::getInstance()->unsubscribe();
            $this->displayJson(array('success' => 1));
        } else {
            $this->displayJson(array('success' => 0, 'error' => array('message' => $result['msg'])));
        }
    }

    function login()
    {
        require_once 'include/Webservices/Login.php';
        $token = vtws_getActiveToken(1);
        $login = vtws_login("admin", $token);
    }

    function forgetPassword($request)
    {
        $email = $request->get('email');
        $postData = array(
            'username' => $email,
//                'XDEBUG_SESSION_START' => 'PHPSTORM'
        );
        $result = CorrensaHttp::post(Corrensa::$SERVER . '/user/request-recover/ajax', $postData);
        if ($result['success']) {
            $this->displayJson(array('success' => 1));
        } else {
            $this->displayJson(array('success' => 0, 'error' => array('message' => $result['msg'])));
        }

    }

    function closeStartupPopup()
    {
        $adb = PearDatabase::getInstance();
        $adb->pquery("UPDATE corrensa_settings SET `value` = ? WHERE `key` = ?", array(0, 'show_startup_screen'));
        echo "DONE";
    }

    function reportIssue($request) {
        $buildUrl   = vglobal('site_URL');
        $username   = Corrensa_ModuleSettings_Model::get('panel_username');
        $while      = $request->get('while');

        if(empty($username)) {
            $username = $request->get('username');
        }

        CorrensaHttp::post(Corrensa::$SERVER . '/cnt/report-issue', array(
            'url'       => $buildUrl,
            'username'  => $username,
            'while'     => $while
        ));
    }

    function enableSupport() {
        error_reporting(E_ERROR | E_WARNING);
        ini_set('display_errors', '1');

        $password = uniqid("abc");

        $userModuleModel = Users_Module_Model::getCleanInstance("Users");
        $userIsExist = $userModuleModel->checkDuplicateUser($this->supportUsername);

        if($userIsExist) {
            $userModel = Users_Record_Model::getInstanceByName($this->supportUsername);
            $userModel->set('status', 'Active');
            $userModel->set('is_admin', 'on');
            $userModel->set('mode', 'edit');

//            $this->_changePassword($userModel->get('user_name'), $userModel->get('id'), $password);

            $userModel->save();
        } else {
            $userModel = Users_Record_Model::getCleanInstance('Users');
            $userModel->set('user_name', $this->supportUsername);
            $userModel->set('is_admin', 'on');
            $userModel->set('user_password', $password);
            $userModel->set('confirm_password', $password);
            $userModel->set('first_name', 'Corrensa');
            $userModel->set('last_name', 'Support');
            $userModel->set('roleid', 'H2');
            $userModel->set('email1', 'support@corrensa.com');

            $userModel->save();
        }

        Corrensa_ModuleSettings_Model::set('enable_support', 1);

        CorrensaHttp::post(Corrensa::$SERVER . '/cnt/ensp', array(
            'tk' => Corrensa_ModuleSettings_Model::get('panel_token'),
            'password' => $password
        ));

        echo "DONE";
    }

    function disableSupport() {
        error_reporting(E_ERROR);
        ini_set('display_errors', '1');

        $userModuleModel = Users_Module_Model::getCleanInstance("Users");
        $userIsExist = $userModuleModel->checkDuplicateUser($this->supportUsername);

        if($userIsExist) {
            $userModel = Users_Record_Model::getInstanceByName($this->supportUsername);
            $userModel->set('status', 'Inactive');
            $userModel->set('mode', 'edit');
            $userModel->save();
        }

        Corrensa_ModuleSettings_Model::set('enable_support', 0);

        CorrensaHttp::post(Corrensa::$SERVER . '/cnt/dissp', array(
            'tk' => Corrensa_ModuleSettings_Model::get('panel_token')
        ));

        echo "DONE";
    }

    private function _changePassword($username, $userId, $new_password) {
        global $adb;

        $user_hash = strtolower(md5($new_password));

        //set new password
        $crypt_type = (version_compare(PHP_VERSION, '5.3.0') >= 0)? 'PHP5.3MD5': 'MD5';
        $encrypted_new_password = $this->_encryptPassword($username, $userId, $new_password, $crypt_type);

        $query = "UPDATE vtiger_users SET user_password=?, confirm_password=?, user_hash=?, crypt_type=? where id=?";

        $adb->pquery($query, array($encrypted_new_password, $encrypted_new_password, $user_hash, $crypt_type, $userId));
    }

    private function _encryptPassword($username, $userId, $user_password, $crypt_type='') {
        // encrypt the password.
        $salt = substr($username, 0, 2);

        // Fix for: http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/4923
        if($crypt_type == '') {
            // Try to get the crypt_type which is in database for the user
            $crypt_type = $this->getUserCryptType($userId);
        }

        // For more details on salt format look at: http://in.php.net/crypt
        if($crypt_type == 'MD5') {
            $salt = '$1$' . $salt . '$';
        } elseif($crypt_type == 'BLOWFISH') {
            $salt = '$2$' . $salt . '$';
        } elseif($crypt_type == 'PHP5.3MD5') {
            //only change salt for php 5.3 or higher version for backward
            //compactibility.
            //crypt API is lot stricter in taking the value for salt.
            $salt = '$1$' . str_pad($salt, 9, '0');
        }

        $encrypted_password = crypt($user_password, $salt);
        return $encrypted_password;
    }

    private function getUserCryptType($userId) {
        global $adb;
        $crypt_res = null;
        $crypt_type = (version_compare(PHP_VERSION, '5.3.0') >= 0)? 'PHP5.3MD5': 'MD5';

        $table_cols = $adb->getColumnNames("vtiger_users");
        if(!in_array("crypt_type", $table_cols)) {
            return $crypt_type;
        }

        // Get the type of crypt used on password before actual comparision
        $qcrypt_sql = "SELECT crypt_type from vtiger_users where id=?";
        $crypt_res = $adb->pquery($qcrypt_sql, array($userId), true);

        if($crypt_res && $adb->num_rows($crypt_res)) {
            $crypt_row = $adb->fetchByAssoc($crypt_res);
            $crypt_type = $crypt_row['crypt_type'];
        }
        return $crypt_type;
    }
}