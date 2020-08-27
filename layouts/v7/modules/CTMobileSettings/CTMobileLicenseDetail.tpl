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
			<div class="span8"><h3>{vtranslate('CTMobile License Configuration', $MODULE)}</h3></div>
			<div class="span4"><div class="pull-right">
			{if $LICENCE_KEY neq ''}<button class="btn btn-danger" id="deactivateLicense" type="button" title="{vtranslate('LBL_DEACTIVATE', $MODULE)}"><strong>{vtranslate('LBL_DEACTIVATE', $MODULE)}</strong></button>&nbsp;{/if}
			<button class="btn btn-success editButton" data-url='?module=CTMobileSettings&parent=Settings&view=LicenseEdit' type="button" title="{vtranslate('LBL_EDIT', $MODULE)}"><strong>{vtranslate('LBL_EDIT', $MODULE)}</strong></button>
			<a class="cancelLink" type="reset" onclick="javascript:window.history.back();">Cancel</a>
			</div>
			</div>	
		</div>
		<br/>
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
										{vtranslate('License Key',{$MODULE})}</label>
									</td>
									<td  class="{$WIDTHTYPE} fieldValue medium">
										<label class="muted marginRight10px">{$LICENCE_KEY}</label>
									</td>
								</tr>
						</tbody>
					</table>
				   
		</div>
	</div>
{/strip}
