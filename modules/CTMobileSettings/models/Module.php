<?php
/*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */

class CTMobileSettings_Module_Model extends Vtiger_Module_Model {
    
    public static $CTMOBILE_VERSION_URL = 'https://crmtiger.com/m/checkversion.php';
    public static $CTMOBILE_CHECKLICENSE_URL = 'https://crmtiger.com/m/checklicense.php';
    public static $GOOGLE_ADDRESSAPI_URL = 'https://maps.googleapis.com/maps/api/geocode/json?address=';
    public static $CTMOBILE_UPGRADEVIEW_URL = 'index.php?module=CTMobileSettings&parent=Settings&view=Upgrade';
    public static $CTMOBILE_TEAMTRACKING_URL = 'index.php?module=CTMobileSettings&parent=Settings&view=TeamTracking';
    public static $CTMOBILE_LIVETRACKINGUSER_URL = 'index.php?module=CTMobileSettings&parent=Settings&view=LiveTrackingUser';
    public static $CTMOBILE_MYACCOUNT_URL = 'https://crmtiger.com/m/my-account/';
    public static $CTMOBILE_ACCESSUSER_URL = 'index.php?module=CTMobileSettings&parent=Settings&view=CTMobileAccessUser';
	public static $CTMOBILE_RELEASE_NOTE_URL = 'http://kb.crmtiger.com/knowledge-base/release-notes/';
    
    
    function getLicenseData(){
		global $adb;
		$result = $adb->pquery("SELECT * FROM ctmobile_license_settings",array());
		$num_rows = $adb->num_rows($result);
		if($num_rows > 0){
			$license_key = $adb->query_result($result,0,'license_key');
			$domain = $adb->query_result($result,0,'domain');
			$url = self::$CTMOBILE_CHECKLICENSE_URL;
			$ch = curl_init($url);
			// Setup request to send json via POST.
			$data = array( "license_key"=>$license_key,"domain"=>$domain,"action"=>"get_licence_data");
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			// Return response instead of printing.
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Send request.
			$result = curl_exec($ch);
			curl_close($ch);
			$licencedata = json_decode($result);
			$LastPaymentDate = $licencedata->PrevPaymentDate;
			$user_type = $licencedata->user_type;
			$NextPaymentdate = $licencedata->NextPaymentdate;
			$ORDER_ID = $licencedata->order_id;
			$message = $licencedata->NextPaymentdate;
			if($user_type != ''){
				if($user_type == 'One Time'){
					$NextPaymentdate = "None";
				}
				$date = strtotime($LastPaymentDate);
				$LastPaymentDate = date('d-m-Y',$date);
				$data = array("Plan"=>$user_type,"LastPaymentDate"=>$LastPaymentDate,"NextPaymentDate"=>$NextPaymentdate,'ORDER_ID'=>$ORDER_ID);
			}else{
				$data = array("Plan"=>$user_type,"LastPaymentDate"=>"01-02-2019","NextPaymentDate"=>"01-03-2019","message"=>"Invalid License Key");
			}
		}else{
			$data = array("Plan"=>"","LastPaymentDate"=>"01-02-2019","NextPaymentDate"=>"01-03-2019","message"=>"No Licence Key");
		}
		return $data;
	}
	function getCTRouteUser(){
		global $adb;
		$query = "SELECT DISTINCT(userid) FROM ctmobile_userderoute  INNER JOIN vtiger_users ON vtiger_users.id = ctmobile_userderoute.userid WHERE vtiger_users.deleted = 0";
		$result = $adb->pquery($query,array());
		for($i=0;$i<$adb->num_rows($result);$i++){
			$id = $adb->query_result($result,$i,'userid');
			$usersRecordModel =Users_Record_Model::getInstanceById($id,'Users');
			$name = $usersRecordModel->get('first_name').' '. $usersRecordModel->get('last_name');
			$user[] = array('id'=>$id,'name'=>$name);
		}
		return $user;
	}
	function getActiveUser(){
		global $adb;
		$query = "SELECT DISTINCT(userid) FROM ctmobile_userderoute  INNER JOIN vtiger_users ON vtiger_users.id = ctmobile_userderoute.userid WHERE vtiger_users.deleted = 0 AND createdtime > (NOW()-interval 30 minute)";
		$result = $adb->pquery($query,array());
		$activeuser = $adb->num_rows($result);
		return $activeuser;
	}
	
	function getGeocodingReport(){
		global $adb;
		//Contacts
		$contotalquery = "SELECT * FROM vtiger_contactdetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid WHERE vtiger_crmentity.deleted = 0";
		$contotalresult  = $adb->pquery($contotalquery,array());
		$contotal = $adb->num_rows($contotalresult);
		$congeocodedquery = "SELECT * FROM vtiger_contactdetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_contactdetails.contactid 
						  WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NOT NULL AND ct_address_lat_long.longitude IS NOT NULL";
		$congeocodedresult  = $adb->pquery($congeocodedquery,array());
		$congeocoded = $adb->num_rows($congeocodedresult);
		$connongeocodedquery = "SELECT * FROM vtiger_contactdetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_contactdetails.contactid 
						  INNER JOIN vtiger_contactaddress ON vtiger_contactaddress.contactaddressid = vtiger_contactdetails.contactid WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NULL AND ct_address_lat_long.longitude IS NULL";
		$conaddressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Contacts'));
		for($i=0;$i<$adb->num_rows($conaddressQuery);$i++){
			$fields = $adb->query_result($conaddressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			if($i == 0){
				$connongeocodedquery .= " AND ( vtiger_contactaddress.".$field." != ''";
			}else if($i == $adb->num_rows($conaddressQuery)-1){
				$connongeocodedquery .= " OR vtiger_contactaddress.".$field." != '' ) ";
			}else{
				$connongeocodedquery .= " OR vtiger_contactaddress.".$field." != '' ";
			}
		}
		
		$connongeocodedresult  = $adb->pquery($connongeocodedquery,array());
		$connongeocoded = $adb->num_rows($connongeocodedresult);
		
		$conaddressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Contacts'));
		$connonAddressQuery = "SELECT * FROM vtiger_contactdetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid INNER JOIN vtiger_contactaddress ON vtiger_contactaddress.contactaddressid = vtiger_contactdetails.contactid WHERE vtiger_crmentity.deleted = 0";
		for($i=0;$i<$adb->num_rows($conaddressQuery);$i++){
			$fields = $adb->query_result($conaddressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			$connonAddressQuery .= " AND vtiger_contactaddress.".$field."= ''";
		}
		$connonAddressQuery = $adb->pquery($connonAddressQuery,array());
		$connonaddress = $adb->num_rows($connonAddressQuery);
		$conpending = $contotal - ($congeocoded + $connonaddress + $connongeocoded);
		$data['Contacts'] = array('total'=>$contotal,'geocoded'=>$congeocoded,'nongeocoded'=>$connongeocoded,'pending'=>$conpending,'nonAddress'=>$connonaddress);
		
		//Leads
		$ledtotalquery = "SELECT * FROM vtiger_leaddetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid WHERE vtiger_crmentity.deleted = 0";
		$ledtotalresult  = $adb->pquery($ledtotalquery,array());
		$ledtotal = $adb->num_rows($ledtotalresult);
		$ledgeocodedquery = "SELECT * FROM vtiger_leaddetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_leaddetails.leadid 
						  WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NOT NULL AND ct_address_lat_long.longitude IS NOT NULL";
		$ledgeocodedresult  = $adb->pquery($ledgeocodedquery,array());
		$ledgeocoded = $adb->num_rows($ledgeocodedresult);
		$lednongeocodedquery = "SELECT * FROM vtiger_leaddetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_leaddetails.leadid 
						   INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NULL AND ct_address_lat_long.longitude IS NULL";
		$ledaddressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Leads'));
		for($i=0;$i<$adb->num_rows($ledaddressQuery);$i++){
			$fields = $adb->query_result($ledaddressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			if($i == 0){
				$lednongeocodedquery .= " AND ( vtiger_leadaddress.".$field." != ''";
			}else if($i == $adb->num_rows($ledaddressQuery)-1){
				$lednongeocodedquery .= " OR vtiger_leadaddress.".$field." != '' ) ";
			}else{
				$lednongeocodedquery .= " OR vtiger_leadaddress.".$field." != '' ";
			}
		}
		
		$lednongeocodedresult  = $adb->pquery($lednongeocodedquery,array());
		$lednongeocoded = $adb->num_rows($lednongeocodedresult);
		$ledaddressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Leads'));
		$lednonAddressQuery = "SELECT * FROM vtiger_leaddetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid WHERE vtiger_crmentity.deleted = 0";
		for($i=0;$i<$adb->num_rows($ledaddressQuery);$i++){
			$fields = $adb->query_result($ledaddressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			$lednonAddressQuery .= " AND vtiger_leadaddress.".$field."= ''";
		}
		$lednonAddressQuery = $adb->pquery($lednonAddressQuery,array());
		$lednonaddress = $adb->num_rows($lednonAddressQuery);
		$ledpending = $ledtotal - ($ledgeocoded + $lednonaddress + $lednongeocoded);
		$data['Leads'] = array('total'=>$ledtotal,'geocoded'=>$ledgeocoded,'nongeocoded'=>$lednongeocoded,'pending'=>$ledpending,'nonAddress'=>$lednonaddress);
		
		//Accounts
		$acctotalquery = "SELECT * FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid WHERE vtiger_crmentity.deleted = 0";
		$acctotalresult  = $adb->pquery($acctotalquery,array());
		$acctotal = $adb->num_rows($acctotalresult);
		$accgeocodedquery = "SELECT * FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_account.accountid 
						  WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NOT NULL AND ct_address_lat_long.longitude IS NOT NULL";
		$accgeocodedresult  = $adb->pquery($accgeocodedquery,array());
		$accgeocoded = $adb->num_rows($accgeocodedresult);
		$accnongeocodedquery = "SELECT * FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_account.accountid 
						  INNER JOIN vtiger_accountbillads ON vtiger_accountbillads.accountaddressid = vtiger_account.accountid INNER JOIN vtiger_accountshipads ON vtiger_accountshipads.accountaddressid = vtiger_account.accountid 
						  WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NULL AND ct_address_lat_long.longitude IS NULL";
		$accaddressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Accounts'));
		for($i=0;$i<$adb->num_rows($accaddressQuery);$i++){
			$fields = $adb->query_result($accaddressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			if($i == 0){
				$accnongeocodedquery .= " AND ( ".$field." != ''";
			}else if($i == $adb->num_rows($accaddressQuery)-1){
				$accnongeocodedquery .= " OR ".$field." != '' ) ";
			}else{
				$accnongeocodedquery .= " OR ".$field." != '' ";
			}
		}
		$accnongeocodedresult  = $adb->pquery($accnongeocodedquery,array());
		$accnongeocoded = $adb->num_rows($accnongeocodedresult);
		$accpending = $acctotal - ($accgeocoded + $accnongeocoded);
		$accaddressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Accounts'));
		$accnonAddressQuery = "SELECT * FROM vtiger_account INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid INNER JOIN vtiger_accountbillads ON vtiger_accountbillads.accountaddressid = vtiger_account.accountid INNER JOIN vtiger_accountshipads ON vtiger_accountshipads.accountaddressid = vtiger_account.accountid WHERE vtiger_crmentity.deleted = 0";
		for($i=0;$i<$adb->num_rows($accaddressQuery);$i++){
			$fields = $adb->query_result($accaddressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			$accnonAddressQuery .= " AND ".$field."= ''";
		}
		$accnonAddressQuery = $adb->pquery($accnonAddressQuery,array());
		$accnonaddress = $adb->num_rows($accnonAddressQuery);
		$accpending = $acctotal - ($accgeocoded + $accnonaddress + $accnongeocoded);
		$data['Accounts'] = array('total'=>$acctotal,'geocoded'=>$accgeocoded,'nongeocoded'=>$accnongeocoded,'pending'=>$accpending,'nonAddress'=>$accnonaddress);
		
		//Calendar
		$caltotalquery = "SELECT * FROM vtiger_activity INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid WHERE vtiger_crmentity.deleted = 0";
		$caltotalresult  = $adb->pquery($caltotalquery,array());
		$caltotal = $adb->num_rows($caltotalresult);
		$calgeocodedquery = "SELECT * FROM vtiger_activity INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_activity.activityid 
						  WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NOT NULL AND ct_address_lat_long.longitude IS NOT NULL";
		$calgeocodedresult  = $adb->pquery($calgeocodedquery,array());
		$calgeocoded = $adb->num_rows($calgeocodedresult);
		$calnongeocodedquery = "SELECT * FROM vtiger_activity INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid INNER JOIN ct_address_lat_long ON ct_address_lat_long.recordid = vtiger_activity.activityid 
						  WHERE vtiger_crmentity.deleted = 0 AND ct_address_lat_long.latitude IS NULL AND ct_address_lat_long.longitude IS NULL";
		$caladdressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Calendar'));
		for($i=0;$i<$adb->num_rows($caladdressQuery);$i++){
			$fields = $adb->query_result($caladdressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			if($i == 0){
				$calnongeocodedquery .= " AND ( ".$field." != ''";
			}else if($i == $adb->num_rows($caladdressQuery)-1){
				$calnongeocodedquery .= " OR ".$field." != '' ) ";
			}else{
				$calnongeocodedquery .= " OR ".$field." != '' ";
			}
		}
		$calnongeocodedresult  = $adb->pquery($calnongeocodedquery,array());
		$calnongeocoded = $adb->num_rows($calnongeocodedresult);
		$caladdressQuery = $adb->pquery("SELECT * FROM ctmobile_address_fields WHERE module = ?",array('Calendar'));
		$calnonAddressQuery = "SELECT * FROM vtiger_activity INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid WHERE vtiger_crmentity.deleted = 0";
		for($i=0;$i<$adb->num_rows($caladdressQuery);$i++){
			$fields = $adb->query_result($caladdressQuery,$i,'fieldname');
			$test = explode(":",$fields);
			$field = $test[1];
			$calnonAddressQuery .= " AND ".$field."= ''";
		}
		$calnonAddressQuery = $adb->pquery($calnonAddressQuery,array());
		$calnonaddress = $adb->num_rows($calnonAddressQuery);
		$calpending = $caltotal - ($calgeocoded + $calnonaddress + $calnongeocoded);
		$data['Calendar'] = array('total'=>$caltotal,'geocoded'=>$calgeocoded,'nongeocoded'=>$calnongeocoded,'pending'=>$calpending,'nonAddress'=>$calnonaddress);
		
		return $data;	
	}
}
