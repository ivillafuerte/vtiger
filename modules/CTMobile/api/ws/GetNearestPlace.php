<?php
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
include_once dirname(__FILE__) . '/models/Alert.php';
include_once dirname(__FILE__) . '/models/SearchFilter.php';
include_once dirname(__FILE__) . '/models/Paging.php';

class CTMobile_WS_GetNearestPlace extends CTMobile_WS_Controller {
	
	function getSearchFilterModel($module, $search) {
		return CTMobile_WS_SearchFilterModel::modelWithCriterias($module, Zend_JSON::decode($search));
	}
	
	function getPagingModel(CTMobile_API_Request $request) {
		$page = $request->get('page', 0);
		return CTMobile_WS_PagingModel::modelWithPageStart($page);
	}
	
	function process(CTMobile_API_Request $request) {
		global $adb, $site_URL;
		
		$current_module_name = trim($request->get('module'));
		$current_latitude = trim($request->get('latitude'));
		$current_longitude = trim($request->get('longitude'));
		$radius = trim($request->get('radius'));
		$moduleWSId = CTMobile_WS_Utils::getEntityModuleWSId($current_module_name);
		
		$getModuleLatLongQuery = $adb->pquery("SELECT ct_address_lat_long.* from ct_address_lat_long
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = ct_address_lat_long.recordid
			WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.moduleid = ? AND latitude != '' AND longitude != ''", array($moduleWSId));
		$countRows = $adb->num_rows($getModuleLatLongQuery);
		$countRows;
		for($i=0;$i<$countRows;$i++) {
			$recordid = trim($adb->query_result($getModuleLatLongQuery, $i, 'recordid'));
			$latitude = trim($adb->query_result($getModuleLatLongQuery, $i, 'latitude'));
			$longitude = trim($adb->query_result($getModuleLatLongQuery, $i, 'longitude'));
			
			$getCRMEntityData = $adb->pquery("SELECT * FROM vtiger_crmentity where deleted = 0 and crmid = ?", array($recordid));
			$label = trim($adb->query_result($getCRMEntityData, 0, 'label'));
			
			$distance = $this->distance($latitude, $longitude, $current_latitude, $current_longitude);
			
			if($distance < $radius) {
				if($latitude == ''){
					$latitude = '';
				}
				if($longitude == ''){
					$longitude = '';
				}
				$nearestPlaceData[] = array('recordid'=>$recordid,'label'=>$label,'latitude'=>$latitude, 'longitude'=>$longitude);
			}	
		}
		
		$response = new CTMobile_API_Response();
		if(count($nearestPlaceData) == 0) {
			throw new WebServiceException(404,"No Records found");
		} else {
			$response->setResult(array('records'=>$nearestPlaceData, 'msg'=>''));
		}
		
		return $response;
	}
	
	function distance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles;
    }
}
