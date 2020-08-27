<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
class CTMobileSettings_getUserLatLong_Action extends Vtiger_Save_Action {
    
public function process(Vtiger_Request $request) {
	global $adb;
	$user_id = $request->get('user_id');
	$daterange = $request->get('daterange');
	
	$date = explode(' - ',$daterange);
	$date1 = date("Y-m-d", strtotime($date[0]));
	$date2 = date("Y-m-d", strtotime($date[1]));
	$poliquery = "SELECT * FROM ctmobile_userderoute WHERE userid = ? AND DATE(createdtime) BETWEEN '".$date1."' AND '".$date2."' ";
	$result1 = $adb->pquery($poliquery,array($user_id));
	$last_latitude = '';
	$last_longitude = '';
	$data = array('poliLine'=>'','marker'=>'');
	for($i=0;$i<$adb->num_rows($result1);$i++){
		$latitude = $adb->query_result($result1,$i,'latitude');
		$longitude = $adb->query_result($result1,$i,'longitude');
		
		if(empty($last_latitude) && empty($last_longitude)){
			$last_latitude = $latitude;
			$last_longitude = $longitude;
			$data['poliLine'][] = array('lat'=>(double)$latitude,'lng'=>(double)$longitude);
		}else if($last_latitude == $latitude && $last_longitude == $longitude){
			continue;
		}else{
			$last_latitude = $latitude;
			$last_longitude = $longitude;
			$data['poliLine'][] = array('lat'=>(double)$latitude,'lng'=>(double)$longitude);
		}
		
	}
	
	$markquery = "SELECT * FROM ctmobile_userderoute WHERE userid = ? AND record != '' AND DATE(createdtime) BETWEEN '".$date1."' AND '".$date2."' ";
	$result2 = $adb->pquery($markquery,array($user_id));
	$last_latitude = '';
	$last_longitude = '';
	for($i=0;$i<$adb->num_rows($result2);$i++){
		$latitude = $adb->query_result($result2,$i,'latitude');
		$longitude = $adb->query_result($result2,$i,'longitude');
		$recordid = $adb->query_result($result2,$i,'record');
		$action = $adb->query_result($result2,$i,'action');
		$createdtime = $adb->query_result($result2,$i,'createdtime');
		
		$recordResult = $adb->pquery("SELECT * FROM vtiger_crmentity WHERE crmid = ?",array($recordid));
		$module = $adb->query_result($recordResult,0,'setype');
		$label = $adb->query_result($recordResult,0,'label');
		
		if($action == 'edit'){
			$action = 'Updated';
			$DetaiViewurl = 'index.php?module='.$module.'&view=Detail&record='.$recordid;
		}else{
			if($module == 'ModComments'){
				$CommentQuery = "SELECT * FROM vtiger_modcomments INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_modcomments.related_to WHERE modcommentsid = ?";
				$commentResult = $adb->pquery($CommentQuery,array($recordid));
				$setype = $adb->query_result($commentResult,0,'setype');
				$crmid = $adb->query_result($commentResult,0,'crmid');
				$relatedLabel = $adb->query_result($commentResult,0,'label');
				$DetaiViewurl = 'index.php?module='.$setype.'&relatedModule='.$module.'&view=Detail&record='.$crmid.'&mode=showRelatedList';
				
				$action = 'Commented On '.$relatedLabel;
				$module = '';
			}else{
				$action = 'Created';
				$DetaiViewurl = 'index.php?module='.$module.'&view=Detail&record='.$recordid;
			}
			
		}
		$createdtime = Vtiger_Util_Helper::formatDateDiffInStrings($createdtime);
		$entitylabel = '<div id="bodyContent"><p>'.$action.' '.$module.' : </p><p><a href="'.$DetaiViewurl.'" target="_blank"><b>'.$label.' </b></a><p>'.$createdtime.'</p></div>';
		
		$data['marker'][] = array('lat'=>(double)$latitude,'lng'=>(double)$longitude,'label'=>$entitylabel);
		
		
	}
	
	$response = new Vtiger_Response();
	$response->setEmitType(Vtiger_Response::$EMIT_JSON);
	$response->setResult($data);
	$response->emit();
}
}
?>
