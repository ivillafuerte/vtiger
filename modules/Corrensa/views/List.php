<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

class Corrensa_List_View extends Vtiger_List_View {
	function __construct() {
		parent::__construct();
	}

	function preProcess (Vtiger_Request $request) {
		header("Location: index.php?module=Corrensa&parent=Settings&view=Settings");
	}
}