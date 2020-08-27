<?php
/* ********************************************************************************
 * The content of this file is subject to the Global Search ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once('modules/Corrensa/Corrensa.php');

class Corrensa_Uninstall_View extends Settings_Vtiger_Index_View
{

	function __construct()
	{
		parent::__construct();
	}

	function process(Vtiger_Request $request)
	{
		$module = $request->getModule();
		$viewer = $this->getViewer($request);

		echo $viewer->view('Uninstall.tpl', $module, true);
	}

	public function getHeaderCss(Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);

		$cssFileNames = array(
			'~layouts/vlayout/modules/Corrensa/resources/css/settings.css'
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}