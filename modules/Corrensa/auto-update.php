<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
chdir('../../');

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("config.inc.php");
require_once 'include/utils/utils.php';
require_once('includes/runtime/BaseModel.php');
require_once('includes/Loader.php');
include_once 'vtlib/Vtiger/Module.php';
include_once 'includes/main/WebUI.php';

require_once('includes/http/Response.php');
require_once('modules/Corrensa/Corrensa.php');
require_once('vtlib/Vtiger/Package.php');

global $current_user;

$seed_user = new Users();
$current_user = $seed_user->retrieveCurrentUserInfoFromFile(1);
$current_user->moduleName = "Users";
vglobal('current_user', $current_user);

$version = $_REQUEST['v'];

if(empty($version)) {
    exit("Invalid version");
}

require_once 'Corrensa.php';

$fileName = Corrensa::$SERVER . "/modules/corrensa-$version.zip";
if(Corrensa::$CODE != 'pri') {
    $fileName = Corrensa::$SERVER . "/modules/corrensa-$version-".Corrensa::$CODE.".zip";
}

$fileUrl = Corrensa::$SERVER . "/modules/corrensa-$version.zip";
$moduleName = 'Corrensa';

$data = file_get_contents($fileUrl);
$linkUrl = __DIR__ . '/../../test/vtlib/' . $moduleName . '.zip';

$inputFileZip = file_put_contents($linkUrl, $data);
if ($inputFileZip) {
	$package = new Vtiger_Package();
	$package->update(Vtiger_Module::getInstance($moduleName), $linkUrl);
	checkFileAccessForDeletion($linkUrl);
	unlink($linkUrl);
	echo 'done';
} else {
	echo 'false';
}