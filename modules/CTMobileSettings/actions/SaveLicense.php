<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
class CTMobileSettings_SaveLicense_Action extends Settings_Vtiger_Basic_Action {
    
public function process(Vtiger_Request $request) {
	global $adb,$site_URL;
	$getLicenseQuery=$adb->pquery("SELECT * FROM ctmobile_license_settings");
	$numOfLicenseCount = $adb->num_rows($getLicenseQuery);
	$License_Key = trim($request->get('license_key'));

	$url = CTMobileSettings_Module_Model::$CTMOBILE_CHECKLICENSE_URL;
	$ch = curl_init($url);
	$data = array( "license_key"=>$License_Key,"domain"=>$site_URL,"action"=>"activate");
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);
	if($result == 'validated'){
		
		$url = CTMobileSettings_Module_Model::$CTMOBILE_CHECKLICENSE_URL;
		$ch = curl_init($url);
		// Setup request to send json via POST.
		$data2 = array( "license_key"=>$License_Key,"domain"=>$site_URL,"action"=>"get_licence_data");
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data2 );
		// Return response instead of printing.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// Send request.
		$result2 = curl_exec($ch);
		curl_close($ch);
		$licencedata = json_decode($result2);
		$premiumUserType = array('Monthly','One Time','Yearly');
		$user_type = $licencedata->user_type;
		$expirydate = $licencedata->expirydate;
		if(in_array($user_type,$premiumUserType)){
			$user_type = 'premium';
		}else{
			$user_type = 'free';
		}
		if($numOfLicenseCount > 0){
			$record=$adb->query_result($getLicenseQuery,0,'id');
			$query=$adb->pquery("UPDATE ctmobile_license_settings SET license_key=?, domain=?, expirydate=?, user_type=? WHERE id=?",array($License_Key, $site_URL, $expirydate, $user_type, $record));
			if($query){
				$result = array('code'=>2, 'msg'=>vtranslate('License Key Updated Successfully','CTMobileSettings'));
			}
		}else{
			$query=$adb->pquery("INSERT INTO ctmobile_license_settings (license_key,status,domain,expirydate,user_type) values(?,?,?,?,?)",array($License_Key,1,$site_URL,$expirydate,$user_type));
			if($query){
				$result = array('code'=>1, 'msg'=>vtranslate('License Key Inserted Successfully','CTMobileSettings'));
			}
		}
	}else if($result == 'Already activated'){
		$result = array('code'=>101, 'msg'=>vtranslate('You Enetered License is Already Registered','CTMobileSettings'));
	}else{
		$result = array('code'=>100, 'msg'=>vtranslate('You Enetered License is Invalid','CTMobileSettings'));
	}
	$response = new Vtiger_Response();
	$response->setEmitType(Vtiger_Response::$EMIT_JSON);
	$response->setResult($result);
	$response->emit();
}
}
?>
