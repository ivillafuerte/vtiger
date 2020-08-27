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

class Corrensa_Event_Action extends Vtiger_BasicAjax_Action
{

	function __construct()
	{
		parent::__construct();
		$this->exposeMethod('attach');
		$this->exposeMethod('resolve');
		$this->exposeMethod('apply');
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

	function attach($request) {
		$currentUser    = Users_Record_Model::getCurrentUserModel();
		$eventName = $request->get('name');

		$value = "";
		if($eventName == Corrensa::$EVENT_USER_ADD) $value = $request->get('username');
		else if($eventName == Corrensa::$EVENT_USER_UPDATE) $value = $request->get('uid');
		else if($eventName == Corrensa::$EVENT_FIELD_UPDATE) $value = $request->get('fid');

		$key = $eventName . "_" . $currentUser->get('id');
		$_SESSION[$key] = $value;
	}

	function apply($request) {
		$eventName  = $request->get('eventName');

		if($eventName == Corrensa::$EVENT_FIELD_UPDATE) {
			$fieldId = $request->get('fieldId');
			SyncData::when_field_update($fieldId);
		} else if($eventName == Corrensa::$EVENT_FIELD_ADD) {
			$fieldLabel = $request->get('fieldLabel');
			$sourceModule = $request->get('sourceModule');
			$fieldLabel = urldecode($fieldLabel);
			SyncData::when_field_add($fieldLabel, $sourceModule);
		} else if($eventName == Corrensa::$EVENT_FIELD_DELETE) {
			$fieldId = $request->get('fieldId');
			SyncData::when_field_delete($fieldId);
		}
	}

	function resolve($request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$events = array();
		$events[] = 'user_add_'.$currentUser->get('id');
		$events[] = 'user_update_'.$currentUser->get('id');
		$events[] = 'field_update_'.$currentUser->get('id');

		foreach ($events as $event) {
			if(isset($_SESSION[$event]) && $_SESSION[$event] != '@#NOTUSED#@') {
				$value = $_SESSION[$event];

				if(strpos($event, Corrensa::$EVENT_USER_ADD) !== false) {
					SyncData::when_user_add($value);
				} else if(strpos($event, Corrensa::$EVENT_USER_UPDATE) !== false) {
					SyncData::when_user_update($value);
				} else if(strpos($event, Corrensa::$EVENT_FIELD_UPDATE) !== false) {
					SyncData::when_field_update($value);

				}

                $_SESSION[$event] = '@#NOTUSED#@';
				unset($_SESSION[$event]);
			}
		}
	}
}
