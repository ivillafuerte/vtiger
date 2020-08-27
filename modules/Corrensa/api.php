<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
chdir('../../');


include_once("config.inc.php");
require_once 'include/utils/utils.php';
require_once('includes/runtime/BaseModel.php');
require_once('includes/Loader.php');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';
require_once('modules/Corrensa/api/BaseModule.php');

if(Vtiger_Version::check('6.4.0', '>')) {
    require_once("libraries/HTTP_Session2/HTTP/Session2.php");
} else {
    require_once("libraries/HTTP_Session/Session.php");
}

require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/State.php");
require_once("include/Webservices/OperationManager.php");
require_once("include/Webservices/SessionManager.php");

error_reporting(E_ERROR);
ini_set('display_errors', 1);

$baseModule = new BaseModule();
$request    = new Vtiger_Request($_REQUEST);
$resource   = $request->get('module');
$method     = $request->get('action');
$sessionId  = $request->get('sessionName');

if (empty($resource)) {
    $baseModule->displayError('Invalid module');
}

if (empty($method)) {
    $method = 'index';
}

global $adb, $current_user;

$sessionManager = new SessionManager();

if(!(($resource == 'Documents' && $method == 'Download') || $resource == 'Version')) {
    try {
        $adoptSession = true;
        $sid = $sessionManager->startSession($sessionId, $adoptSession);

        $userid = $sessionManager->get("authenticatedUserId");

        if ($userid) {

            $seed_user = new Users();
            $current_user = $seed_user->retrieveCurrentUserInfoFromFile($userid);
            vglobal('current_user', $current_user);

        } else {
            $current_user = null;
            $baseModule->displayError("Error while authenticate: invalid session", 'INVALID_SESSIONID');
        }
    } catch (WebServiceException $e) {
        $baseModule->displayError("Error while authenticate: " . $e->getMessage(), 'AUTHENTICATION_REQUIRED');
    } catch (Exception $e) {
        $baseModule->displayError("Error while authenticate: " . $e->getMessage(), 'AUTHENTICATION_REQUIRED');
    }
}

$className = "API_" . ucwords($resource);

$className = str_replace(array('\\', '/'), '', $className);

$fileClass = "modules/Corrensa/api/$className.php";
checkFileAccessForInclusion($fileClass);
require_once($fileClass);
$classInstance = new $className();

if (!method_exists($classInstance, $method)) {
    $baseModule->displayError('Invalid action ' . $method);
}

$classInstance->$method($request);