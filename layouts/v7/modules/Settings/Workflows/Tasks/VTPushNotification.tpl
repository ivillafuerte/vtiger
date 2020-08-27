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
<div class="row form-group">
    <div class="col-lg-2">{vtranslate('LBL_RECEPIENTS',$QUALIFIED_MODULE)}<span class="redColor">*</span></div>
    <div class="col-lg-8">
        <div class="row">
            <div class="col-lg-5">
                <input type="text" class="inputElement fields" data-rule-required="true" name="sms_recepient" value="{$TASK_OBJECT->sms_recepient}" />
            </div>
            <div class="col-lg-6">
                <select class="select2 task-fields" style="min-width: 150px;" data-placeholder="{vtranslate('LBL_SELECT_FIELDS', $QUALIFIED_MODULE)}">
                    <option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
					{foreach from=$ASSIGNED_TO key=LABEL item=ASSIGNED_USERS_LIST}
						{if $LABEL neq 'Groups'}
							<optgroup label="{vtranslate($LABEL,$QUALIFIED_MODULE)}">
								{foreach from=$ASSIGNED_USERS_LIST item=ASSIGNED_USER key=ASSIGNED_USER_KEY}
									<option value=",{$ASSIGNED_USER_KEY}" {if $ASSIGNED_USER_KEY eq $TASK_OBJECT->assigned_user_id} selected="" {/if}>{$ASSIGNED_USER}</option>
								{/foreach}
							</optgroup>
						{/if}	
					{/foreach}
                    <optgroup label="{vtranslate('LBL_SPECIAL_OPTIONS')}">
                            <option value=",record_owner" {if $TASK_OBJECT->assigned_user_id eq 'copyParentOwner'} selected="" {/if}>{vtranslate('LBL_PARENT_OWNER')}</option>
                    </optgroup>
                </select>	
            </div>
        </div>
    </div>
</div>
<div class="row form-group">
    <div class="col-lg-2">{vtranslate('LBL_ADD_FIELDS',$QUALIFIED_MODULE)}</div>
    <div class="col-lg-10">
        <select class="select2 task-fields" style="min-width: 150px;">
            {$ALL_FIELD_OPTIONS}
        </select>	
    </div>
    <div class="col-lg-2"> &nbsp; </div>
    <div class="col-lg-10"> &nbsp; </div>
    <div class="col-lg-2">{vtranslate('LBL_SMS_TEXT',$QUALIFIED_MODULE)}</div>
    <div class="col-lg-6">
        <textarea name="content" class="inputElement fields" style="height: inherit;">{$TASK_OBJECT->content}</textarea>
    </div>
</div>
{/strip}	
