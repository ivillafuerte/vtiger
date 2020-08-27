{*<!--
 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */
-->*}

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="layouts/v7/modules/CTMobileSettings/CustomStyle.css" rel="stylesheet" type="text/css">
<label style="font:24px solid black;margin-left:20px;margin-top:10px;">{vtranslate("MODULE_LBL",$MODULE)}</label>
<hr>
<div class="row">
  <div class="container-fluid">
    <div class="row-fluid">
    
	<div class="main_div">
		<div class="box">
			<div class="box-panel">
				<div class="under_box">
					<div class="icn"><img src="layouts/v7/modules/CTMobileSettings/img/one.png"></div>
					<div class="box-title">{vtranslate("LBL_MAP_CONFIGURATION",$MODULE)}</div>
				</div>
			</div>
			
			<div class="">
				<a href="index.php?module=CTMobileSettings&parent=Settings&view=Settings">
				<p class="main_text"><center><b>{vtranslate("LBL_CTMOBILE_LIMITED_OFFER",$MODULE)}</b></center></p>
				</a>
				<center><p style="color:red;"><b>{vtranslate("Available Only for Premium",$MODULE)} Plan</b></p></center>
				
				{vtranslate("CRMTiger provides the following Map related features",$MODULE)}
				<ul>
				<li>{vtranslate("Nearby Contacts view in Mobile app",$MODULE)}</li>
				<li>{vtranslate("Live Tracking of Team(users) who enable their GPS",$MODULE)}</li>
				<li>{vtranslate("Calculate Distance between two Location",$MODULE)}</li>
				</ul>

				{vtranslate("LBL_CTMOBILE_LIMITED_OFFER",$MODULE)}
				</p>
			</div>
			
		</div>
		<div class="box">
			<div class="box-panel">
				<div class="under_box">
					<div><img src="layouts/v7/modules/CTMobileSettings/img/two.png"></div>
					<div class="box-title">{vtranslate("License",$MODULE)}</div>
				</div>
			</div>
			<a href="index.php?module=CTMobileSettings&parent=Settings&view=LicenseDetail">
			<div class="box_cont">
				<center>{vtranslate("LBL_LICENSE_CONFIGURATION",$MODULE)}</center>
			</div>
			</a>
		</div>
		<div class="box">
			<div class="box-panel">
				<div class="under_box">
					<div><img src="layouts/v7/modules/CTMobileSettings/img/three.png"></div>
					<div class="box-title">{vtranslate("LBL_ACCOUNT_SUMMARY",$MODULE)}</div>
				</div>
			</div>
			<div class="box_cont">
				<center>
						
						<div>{vtranslate("LBL_ORDER",$MODULE)} # : {$LICENSE_DATA['ORDER_ID']}</div>
						<br/>
						<div><a href="{CTMobileSettings_Module_Model::$CTMOBILE_MYACCOUNT_URL}" target="_blank">{vtranslate("LBL_MY_PLAN",$MODULE)}</a></div>
						<br/>
						<div class="uninstall">
							<button type="submit" style="background-color: lightgray" class="btn btn-danger unInstallCTMobile ">Close My Account</button>
						</div>
				</center>
			</div>
		</div>
		<div class="box">  
			<div class="box-panel">
				<div class="under_box">
					<div><img src="layouts/v7/modules/CTMobileSettings/img/two.png"></div>
					<div class="box-title">{vtranslate("LBL_APP_UPDATES",$MODULE)}</div>
				</div>
			</div>
			<div class="box_cont">
				<center>
					<div>{vtranslate("Your Version",$MODULE)} : {$VERSION}</div>
					<div>{vtranslate("LBL_LATEST_VERSION",$MODULE)} : {$ext_ver}</div>
				</center>
				{if $VERSION neq $ext_ver}
				<br/>
				<center>
					<a class="btn btn-success" href="{CTMobileSettings_Module_Model::$CTMOBILE_UPGRADEVIEW_URL}">
					<div class="">
						<center>{vtranslate("LBL_CLICK_UPDATE",$MODULE)}</center>
					</div>
					</a>
				</center>
				{else}
				<br/>
				<center>
					<div class="text text-success">
						<center>{vtranslate("LBL_UPDATED_VERSION",$MODULE)}</center>
					</div>
				</center>
				{/if}
				<br/>
				<center>
					<a class="btn btn-info" target=_blank" href="{CTMobileSettings_Module_Model::$CTMOBILE_RELEASE_NOTE_URL}">{vtranslate("View Release Note",$MODULE)}</a>	
				</center>				
			</div>
		</div>
		<div class="box">
			<div class="box-panel">
				<div class="under_box">
					<div><img src="layouts/v7/modules/CTMobileSettings/img/three.png"></div>
					<div class="box-title">{vtranslate("LBL_TEAM_TRACKING",$MODULE)}</div>
				</div>
			</div>
			<div class="box_cont">
				<center>
						{if $LICENSE_DATA['Plan'] eq 'Free1' || $LICENSE_DATA['Plan'] eq '1'}
						<div style="color:red;">{vtranslate("Available Only for Premium Plan",$MODULE)}</div>
						{else}
						<div><a href="{CTMobileSettings_Module_Model::$CTMOBILE_TEAMTRACKING_URL}">{vtranslate("LBL_CLICK_TEAM_TRACKING",$MODULE)}</a></div>
						{/if}
						<br/>
						<p style="float:left;width:50%;">{vtranslate("LBL_ACTIVE_USER",$MODULE)} : {$ACTIVE_USER}</p> <!--<p style="float:right;width:50%;">{*vtranslate("LBL_MEETING_USER",$MODULE)*} :</p>-->
						<br/>
						<br/>
						<button type="button" class="btn btn-info" id="livetrackingUser" data-url="{CTMobileSettings_Module_Model::$CTMOBILE_LIVETRACKINGUSER_URL}">{vtranslate("BTN_LIVETRACKING_USER",$MODULE)}</button>
				</center>
			</div>
		</div>
		<div class="box">
			<div class="box-panel">
				<div class="under_box">
					<div><img src="layouts/v7/modules/CTMobileSettings/img/three.png"></div>
					<div class="box-title">{vtranslate("LBL_CTMOBILEACCESS_USER",$MODULE)}</div>
				</div>
			</div>
			<div class="box_cont">
				<center>
						<button type="button" class="btn btn-info" id="ctmobileAccessUser" data-url="{CTMobileSettings_Module_Model::$CTMOBILE_ACCESSUSER_URL}">{vtranslate("BTN_CTMOBILE_ACCESS_USER",$MODULE)}</button>
				</center>
			</div>
		</div>
	</div>
	</div>
	</div>
	</div>
	
