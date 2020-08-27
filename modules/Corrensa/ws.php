<?php
/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
chdir('../../');

if(isset($_REQUEST['query'])) {
	$_REQUEST['query'] = base64_decode($_REQUEST['query']);
}

if(isset($_POST['query'])) {
	$_POST['query'] = base64_decode($_POST['query']);
}

if(isset($_GET['query'])) {
	$_GET['query'] = base64_decode($_GET['query']);
}

if(isset($_POST['elementType']) && $_POST['elementType'] == 'Emails') {
    $element = json_decode($_POST['element'], 1);
    $element['description'] = base64_decode($element['description']);
    $element = json_encode($element);
    $_POST['element'] = $element;
}
//
//echo "<pre>";
//print_r($_POST);
//echo "<pre>";exit();

include_once('webservice.php');