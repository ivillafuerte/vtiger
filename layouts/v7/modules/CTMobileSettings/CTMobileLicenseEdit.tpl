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
{strip}
	<div class="container-fluid" id="EditConfigEditor">
		<div class="widget_header row-fluid">
			<div class="span6"><h3>{vtranslate('CTMobile License Configuration',{$MODULE})}</h3></div>
		</div>
		<div class="contents">
					<table class="table table-bordered table-condensed themeTableColor">
						<tbody>
								<tr>
									<th colspan="2">
										{vtranslate('License Key Configuration',{$MODULE})}
									</th>
								</tr>
								<tr class="fieldLabel medium">
									<td width="30%" class="{$WIDTHTYPE}">
										<label class="muted pull-right marginRight10px"> 
										<span class="redColor">*</span>{vtranslate('Enter License Key',{$MODULE})}</label>
									</td>
									<td  class="{$WIDTHTYPE} fieldValue medium">
										<input class="inputElement" type="text" name="License_Key" id="License_Key" value="{$LICENCE_KEY}"/>
									</td>
								</tr>
						</tbody>
					</table>
				   <br>
					<div class="row-fluid">
						<div class="pull-right">
							<button type="button" class="btn btn-success saveButton" name="save_license_settings" id="save_license_settings"><strong>{vtranslate('Save',{$MODULE})}</strong></button>
							<a class="cancelLink" type="reset" onclick="javascript:window.history.back();">Cancel</a>
						</div>
					</div>
		</div>
	</div>
{/strip}
