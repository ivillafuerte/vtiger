{*<!--
/* ********************************************************************************
* The content of this file is subject to the Corrensa ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
-->*}

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css"/>

<div class="container-fluid corrensa-config">

    {if $USER_IS_ADMIN}
        <div class="corrensa_header widget_header row-fluid">
            {if $DATA.settings.panel_connected}
                <div class="header__logo pull-left">
                    <img class="logo__img" src="layouts/vlayout/modules/Corrensa/resources/images/48.png"
                         alt="Corrensa">
                    <span class="logo__text">Corrensa</span>
                </div>
                {if $SYNC_STATUS != 'in-progress'}
                    <form class="form-horizontal pull-right" method="post" action="" id="actionForm">
                        <label class="sp-switcher">
                            <input type="checkbox" id="cbxToggleSupport" {if $EN_SP}checked{/if} >
                            <div class="sp-slider round">
                                {if $EN_SP}
                                    <span class="sp-status">Support Enabled</span>
                                    {else}
                                    <span class="sp-status">Support Disabled</span>
                                {/if}
                            </div>
                        </label>

                        <a href="#" class="hover-tooltip" data-toggle="tooltip" data-placement="bottom"
                           title="Click to enable support. It will create a VTiger user account for VTExperts to login and troubleshoot the issue."><i
                                    class="fa fa-question-circle" aria-hidden="true"></i></a>

                        {if $TOTAL_ISSUES > 0}
                            <button type="button" class="btn btn-danger" id="btnShowError">Errors ({$TOTAL_ISSUES})</button>
                            <a href="#" class="hover-tooltip" data-toggle="tooltip" data-placement="bottom"
                               title="Any errors detected may impact impact plugin functionality on Outlook/Gmail. If you are having any issues - please contact us at help@vtexperts.com."><i
                                        class="fa fa-question-circle" aria-hidden="true"></i></a>
                        {/if}
                        <button type="button" class="btn btn-primary" id="btnUpdate">Update To Corrensa</button>
                        <a href="#" class="hover-tooltip" data-toggle="tooltip" data-placement="bottom"
                           title="You only need to click it if you added new users, fields, picklists or modified user permissions."><i
                                    class="fa fa-question-circle" aria-hidden="true"></i></a>
                        <button type="button" class="btn" id="btnDisconnect">Disconnect</button>
                        <a href="#" class="hover-tooltip" data-toggle="tooltip" data-placement="bottom"
                           title="If you disconnect from Corrensa - you will no longer be able to use Outlook/Gmail Extension nor recieve any automated updates and your Corrensa configuration will be cleared."><i
                                    class="fa fa-question-circle" aria-hidden="true"></i></a>
                    </form>
                {/if}
            {/if}
        </div>
        <div class="row-fluid">
            {if $DATA.settings.panel_connected && $SYNC_STATUS != 'in-progress' && $SYNC_STATUS != 'updating'}
                <iframe src="{$CORR_URL}?usr={$DATA.settings.panel_username}&token={$DATA.settings.panel_token}"
                        class="dashboard-frame"></iframe>
            {elseif $DATA.settings.panel_connected && ($SYNC_STATUS == 'in-progress' || $SYNC_STATUS == 'updating')}
                <div class="synching-status text-center">
                    <span class="status__text">Corrensa is synchronizing...</span>
                    <span class="status__button">
                    {if $SYNC_STATUS == 'in-progress'}
                        <button type="button" class="btn btn-warning btn-CancelSynching" id="btnCancelSynching">Cancel & try again</button>



                                                                                                            {elseif $SYNC_STATUS == 'updating'}



                        <button type="button" class="btn btn-warning btn-CancelUpdate" id="btnCancelUpdate">Cancel & try again</button>
                    {/if}
                </span>
                </div>
            {else}
                <div class="span4">
                    <form class="form-inline" method="post" action="" id="loginForm">
                        <h3 class="pull-left welcome-message">Welcome to Corrensa!</h3>
                        <iframe src="https://www.corrensa.com/corrensa-adapter-installed.html" style="visibility: hidden; width: 1px; height: 1px;"></iframe>
                        <div class="control-group">
                            <label class="control-label" for="inputEmail">Email</label>
                            <div class="controls">
                                <input type="text" name="email" id="inputEmail" placeholder="">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="inputPassword">Password</label>
                            <div class="controls">
                                <input type="password" name="password" id="inputPassword" placeholder="">
                            </div>
                        </div>
                        <div class="control-group actions">
                            <div class="controls">
                                <button type="submit" class="btn btn-primary btn__login">Login</button>
                                <a href="#" id="btnRegisterCorrensa" class="btn btn-warning btn__register-account">Create
                                    Corrensa Account</a>
                                <a href="#" class="hover-tooltip" data-toggle="tooltip" data-placement="bottom"
                                   title="You need to create Corrensa account to use this plugin"><i
                                            class="fa fa-question-circle" aria-hidden="true"></i></a>
                            </div>
                        </div>
                        <a href="#" id="btnLostPassword">
                            <span>Lost Password ? Click here to reset</span>
                        </a>
                    </form>
                </div>
                <div class="span8">
                    <div class="corrensa-requirements">
                        <h3 class="requirement-title">Corrensa Requirements</h3>
                        <ul>
                            {foreach from=$REQUIREMENTS item="item"}
                                <li {if $item.pass}class="is-valid" {else}class="is-invalid"{/if}>
                                    {if $item.pass}
                                        <span class="rqr-name"><img
                                                    src="layouts/vlayout/modules/Corrensa/resources/images/valid.png"> {$item.label}</span>
                                    {else}
                                        <span class="rqr-name"><img
                                                    src="layouts/vlayout/modules/Corrensa/resources/images/invalid.png"> {$item.label}</span>
                                        <span class="rqr-text">{$item.text}</span>
                                    {/if}
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                    <div class="alert alert-info live-chat-box" role="alert">
                        <h4>If you have any questions or issues installing plugin - please chat with us!</h4>
                        <br>
                        <a href="#"
                           onclick="window.open('https://v2.zopim.com/widget/livechat.html?&key=1P1qFzYLykyIVMZJPNrXdyBilLpj662a', '_blank', 'toolbar=no,scrollbars=no,resizable=no,top=150,left=500,width=500,height=500');"
                           class="btn btn-primary">
                            <span class="fa fa-comments-o"></span>
                            <span>Live Chat</span>
                        </a>
                    </div>
                    <div class="live-chat-box">

                    </div>
                </div>
            {/if}
        </div>
    {else}
        <br>
        <br>
        <h4>You must have admin permission to access Corrensa</h4>
    {/if}
</div>

{if !$DATA.settings.panel_connected}
    <div class="modal fade" id="corrensa-register-screen" tabindex="-1" role="dialog" data-backdrop="static"
         data-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Signup Corrensa</h4>
                </div>
                <div class="modal-body">
                    <iframe src="{$CORR_URL}/signup?mode=popup" frameborder="0"></iframe>
                </div>
                {*<div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>*}
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
    <div class="modal fade" id="corrensa-lost-password-screen" tabindex="-1" role="dialog" data-backdrop="static"
         data-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Recover your account</h4>
                </div>
                <div class="modal-body modal-body-lostpassword">
                    <iframe src="{$CORR_URL}/login?mode=recovery-popup" frameborder="0"></iframe>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->
{else}
    <div class="modal fade" id="corrensa-show-error" tabindex="-1" role="dialog" data-backdrop="static"
         data-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">{$TOTAL_ISSUES} Errors Found!</h4>
                </div>
                <div class="modal-body">
                    <div class="corrensa-requirements corrensa-requirements-modal">
                        <h5>Corrensa might not function properly if the issues below are not addressed.</h5>
                        <ul>
                            {foreach from=$REQUIREMENTS item="item"}
                                <li {if $item.pass}class="is-valid" {else}class="is-invalid"{/if}>
                                    {if $item.pass}
                                        <span class="rqr-name"><img
                                                    src="layouts/vlayout/modules/Corrensa/resources/images/valid.png"> {$item.label}</span>
                                    {else}
                                        <span class="rqr-name"><img
                                                    src="layouts/vlayout/modules/Corrensa/resources/images/invalid.png"> {$item.label}</span>
                                        <span class="rqr-text">{$item.text}</span>
                                    {/if}
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>
{/if}