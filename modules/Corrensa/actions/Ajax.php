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
require_once('modules/Corrensa/handlers/SyncData.php');

class Corrensa_Ajax_Action extends Vtiger_BasicAjax_Action
{

	function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getFieldNameById');
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

	private function displayJson($data) {
		header('Content-type: text/json; charset=UTF-8');
		echo Zend_Json::encode($data);
	}

	function getFieldNameById($request) {
		$fieldId = $request->get('id');

		$fieldInstance = Vtiger_Field_Model::getInstance($fieldId);
		$fieldName = "";
		if($fieldInstance) {
			$fieldName = $fieldInstance->get('name');
		}

		$this->displayJson(array(
			'success'   => !empty($fieldName),
			'fieldName' => $fieldName
		));
	}
}
