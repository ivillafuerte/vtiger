<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
class API_Version {

	function index() {
		require 'modules/Corrensa/Corrensa.php';
		echo Corrensa::$CODE . " | " . Corrensa::$VERSION;
	}

	function config() {
        require_once 'config.inc.php';
    }

}