/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

/**
 * UChannel library
 * */
(function ($) {
    "use strict";

    window.UServer = function (id) {
        this.id = id;
        this.methods = {};
        this.listen();
    };

    UServer.prototype.listen = function () {
        var server = this;
        window.addEventListener("message", function (event) {
            if(typeof event.data.id != 'undefined' && event.data.id == server.id && event.data.from != 'server') {
                var methodName = event.data.method;
                var params = event.data.params;
                var callId = event.data.callId;

                var task = server.methods[methodName](params);
                task.done(function (result) {
                    server.return(methodName, callId, result);
                    console.log('task '+ methodName + ' has return = ', result);
                }).fail(function () {
                    console.log('task '+ methodName + ' failed to process');
                }).always(function () {
                    console.log('task '+ methodName + ' has called ');
                });
            }
        }, false);
    };

    UServer.prototype.return = function (methodName, callId, data) {
        var server = this;
        var msgBody = {
            id: server.id,
            method: methodName,
            from: 'server',
            result: data,
            callId: callId
        };

        window.postMessage(msgBody, '*');
    };

    UServer.prototype.notifyEvent = function (eventName, eventData) {
        var server = this;
        window.postMessage({
            id: server.id,
            type: 'event',
            eventName: eventName,
            eventData: eventData,
            from: 'server'
        }, '*');
    };

    UServer.prototype.bind = function (methodName, func) {
        this.methods[methodName] = func;
    };


    /* Client */

    window.UClient = function (id) {
        this.id = id;
        this.listen();
        this.processingCall = {};
        this.eventHandlers = {};
    };

    UClient.prototype.getCallId = function () {
        var length = 30;
        return Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1);
    };

    UClient.prototype.call = function (methodName, params) {
        var client = this;
        var d = $.Deferred();

        var callId = this.getCallId();
        this.processingCall[callId] = '|---empty---|';

        var callBody = {
            id: this.id,
            method: methodName,
            from: 'client',
            params: params,
            callId: callId
        };
        window.postMessage(callBody, '*');

        var times = 0;

        var waitForResult = setInterval(function () {
            if(client.processingCall[callId] != '|---empty---|') {
                var result = client.processingCall[callId];
                delete client.processingCall[callId];
                d.resolve(result);
                clearInterval(waitForResult);
            } else if(times == 12) {
                d.reject();
                clearInterval(waitForResult);
            } else {
                times++;
            }
        }, 250);

        return d.promise();
    };

    UClient.prototype.on = function (eventName, func) {
        if(!Array.isArray(this.eventHandlers[eventName])) {
            this.eventHandlers[eventName] = [];
        }

        this.eventHandlers[eventName].push(func);
    };

    UClient.prototype.executeHandlers = function (eventName, eventData) {
        this.eventHandlers[eventName].forEach(function (handle, idx) {
            handle(eventData);
        });
    };


    UClient.prototype.listen = function () {
        var client = this;
        window.addEventListener("message", function (event) {
            if(typeof event.data.id != 'undefined' && event.data.id == client.id && event.data.from != 'client') {
                if(typeof event.data.type != 'undefined' && event.data.type == 'event') {
                    var eventName = event.data.eventName;
                    var eventData = event.data.eventData;
                    client.executeHandlers(eventName, eventData);
                } else {
                    var callId = event.data.callId;
                    var result = event.data.result;
                    client.processingCall[callId] = result;
                }
            }
        }, false);
    };
})(jQuery);

(function ($) {

    var ConnectionDialog = function () {
        this.element = null;
        this.loaded = false;
    };

    ConnectionDialog.markup = '<div class="modal fade" id="corrensa-connection-screen" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">\
                                <div class="modal-dialog">\
                                    <div class="modal-content noselect">\
                                        <div class="modal-header clearfix">\
                                            <img class="top-icon" src="layouts/vlayout/modules/Corrensa/resources/images/48.png" />\
                                            <h4 class="modal-title">Connecting To Corrensa</span></h4>\
                                        </div>\
                                        <div class="modal-body">\
                                            <p class="step clearfix" id="step-1">\
                                                <span class="status-icon"></span>\
                                                <span class="process-name">Connect to Corrensa</span>\
                                            </p>\
                                            <p class="step clearfix" id="step-2">\
                                                <span class="status-icon"></span>\
                                                <span class="process-name">Setting up Corrensa for first use <i class="sync-progress"></i>\
                                                    <br><span class="take-up-alert">This might take up to 2-3 minutes</span>\
                                                </span>\
                                            </p>\
                                            <div class="step clearfix text-center" id="step-3">\
                                                <div class="area done-area">\
                                                    <p class="confirm-success">You\'ve successfully connected your VTiger to Corrensa!</p>\
                                                    <button type="button" class="btn btn-success btn-lg btnComplete">\
                                                        Click here to continue\
                                                    </button>\
                                                </div>\
                                                <div class="area fail-area">\
                                                    <span class="area__text">There was an issue connecting VTiger and Corrensa. \
                                                    Our development team will take a look at the log and will get back to you within 24-48 hours.</span>\
                                                    <button type="button" class="btn btn-default btn-lg btnSendReportFirstConnect">\
                                                        Send Error log to Corrensa Support\
                                                    </button>\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>\
                                </div>\
                            </div>';

    ConnectionDialog.prototype.load = function () {
        var that = this;
        var existElement = $('#corrensa-connection-screen');
        if (existElement.length == 0) {
            $(ConnectionDialog.markup).appendTo('body');
            this.element = $('#corrensa-connection-screen');
        }
    };

    $('#corrensa-connection-screen').modal({backdrop: 'static', keyboard: false});

    ConnectionDialog.prototype.show = function () {
        if(!this.loaded) this.load();
        this.element.modal('show');
        return this;
    };

    ConnectionDialog.prototype.hide = function () {
        this.element.modal('hide');
        return this;
    };

    ConnectionDialog.prototype.reset = function () {
        $('#step-1').removeClass('processing').removeClass('done');
        $('#step-2').removeClass('processing').removeClass('done');
        return this;
    };

    ConnectionDialog.prototype.activeStep = function (number, result) {
        if(number == 1) {
            $('#step-3').hide();
            $('#step-1').addClass('processing');
        } else if(number == 2) {
            $('#step-1').removeClass('processing').addClass('done');
            $('#step-2').addClass('processing');
        } else if(number == 3) {
            $('#step-3').show();

            if(result) {
                $('#step-1').hide();
                $('#step-2').hide();
                $('#step-3').addClass('done');
                $('#step-2').removeClass('processing').addClass('done');
                var trackerIframe = '<iframe id="ifm-track-error" src="https://www.corrensa.com/corrensa-loggedin-dashboard.html" style="visibility: hidden; width: 1px; height: 1px"></iframe>';
                $('#step-3').append(trackerIframe);
            } else {
                $('#step-3').addClass('fail');
                $('#step-2').removeClass('processing').addClass('fail');

                var trackerIframe = '<iframe id="ifm-track-error" src="https://www.corrensa.com/corrensa-sync-error.html" style="visibility: hidden; width: 1px; height: 1px"></iframe>';
                $('#step-3').append(trackerIframe);

                // Send report to corrensa's staffs
                AppConnector.request({
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode :  'reportIssue',
                    while:  'login',
                    username: $('#inputEmail').val()
                });
            }

            $('.btnComplete').click(function(){
                location.reload();
            });

            $('.btnSendReportFirstConnect').click(function () {
                var params = {
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode : 'cancelSyncData'
                };

                AppConnector.request(params).then(function(response) {
                    location.reload();
                });
            });
            // location.reload();
        }
    };

    ConnectionDialog.prototype.updateStatus = function (x, z) {
        this.element.find('.sync-progress').text('('+x+'/'+z+')');
    };

    /*
     * Update Structure to corrensa
     * */
    var UpdateDialog = function () {
        this.element = null;
        this.loaded = false;
    };

    UpdateDialog.markup = '<div class="modal fade" id="corrensa-update-screen" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">\
                                <div class="modal-dialog">\
                                    <div class="modal-content noselect">\
                                        <div class="modal-header clearfix">\
                                            <img class="top-icon" src="layouts/vlayout/modules/Corrensa/resources/images/48.png" />\
                                            <h4 class="modal-title">Connecting to Corrensa</span></h4>\
                                        </div>\
                                        <div class="modal-body">\
                                            <div class="step processing clearfix" id="step-1">\
                                                <span class="status-icon"></span>\
                                                <span class="process-name">Updating Corrensa Toolbar with latest VTiger database changes <i class="sync-progress"></i></span>\
                                            </div>\
                                            <div class="step clearfix text-center" id="step-2">\
                                                <div class="area done-area">\
                                                    <button type="button" class="btn btn-success btn-lg btnComplete">\
                                                        Click here to continue\
                                                    </button>\
                                                </div>\
                                                <div class="area fail-area">\
                                                    <span class="area__text">There was an issue connecting VTiger and Corrensa. \
                                                    Our development team will take a look at the log and will get back to you within 24-48 hours.</span>\
                                                    <button type="button" class="btn btn-default btn-lg btnSendReport">\
                                                        Send Error log to Corrensa Support\
                                                    </button>\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>\
                                </div>\
                            </div>';

    $('#corrensa-update-screen').modal({backdrop: 'static', keyboard: false});


    UpdateDialog.prototype.load = function () {
        var that = this;
        var existElement = $('#corrensa-update-screen');
        if (existElement.length == 0) {
            $(UpdateDialog.markup).appendTo('body');
            this.element = $('#corrensa-update-screen');
        }
    };

    UpdateDialog.prototype.show = function () {
        if(!this.loaded) this.load();
        this.element.modal('show');
        return this;
    };

    UpdateDialog.prototype.hide = function () {
        this.element.modal('hide');
        return this;
    };

    UpdateDialog.prototype.triggerDone = function () {
        var that = this;
        var step1 = $('#step-1');
        step1.removeClass('processing').addClass('done');
        $('#step-2 .btnComplete').click(function () {
            location.reload();
        });
        $('#step-2').show().addClass('done');
    };

    UpdateDialog.prototype.triggerFail = function () {
        var _this = this;
        var step1 = $('#step-1');
        var step2 = $('#step-2');
        step1.removeClass('processing').addClass('fail');

        $('#step-2').show().addClass('fail');

        // Send report to corrensa's staffs
        AppConnector.request({
            module: 'Corrensa',
            action: 'SettingAjax',
            mode :  'reportIssue',
            while:  'update'
        });

        $('#step-2 .btnSendReport').click(function () {
            var params = {
                module: 'Corrensa',
                action: 'SettingAjax',
                mode : 'cancelUpdate'
            };

            AppConnector.request(params).then(function(response) {
                location.reload();
            });
        });
    };

    UpdateDialog.prototype.updateStatus = function (x, z) {
        this.element.find('.sync-progress').text('('+x+'/'+z+')');
    };

    var Corrensa_Settings_JS = {
        connectingDialog: new ConnectionDialog(),
        updateDialog: new UpdateDialog(),

        registerLoginFormEvents: function () {
            $('#loginForm').submit(function (e) {
                e.preventDefault();

                var username = $("#inputEmail").val(),
                    password = $("#inputPassword").val();

                Corrensa_Settings_JS.connectingDialog.show().activeStep(1);

                var funcSyncPermission = function(userId, userIds) {
                    var uid = userId || '';

                    AppConnector.request(params = {
                        module: 'Corrensa',
                        action: 'SettingAjax',
                        mode : 'syncPermissionData',
                        user_id: userId,
                        user_ids: userIds
                    }).then(function(response) {
                        if(typeof response.result !== 'undefined') {
                            response = response.result;
                        }
                        if(response && response.success) {
                            if(response.finish) {
                                Corrensa_Settings_JS.connectingDialog.updateStatus(userIds.length, userIds.length);
                                Corrensa_Settings_JS.connectingDialog.activeStep(3, true);
                            }else{
                                Corrensa_Settings_JS.connectingDialog.updateStatus(response.status, userIds.length);
                                funcSyncPermission(response.next_id, userIds);
                            }
                        }
                    });
                };

                var params = {
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode : 'loginToCorrensa',
                    username : username,
                    password : password
                };

                // Post login
                AppConnector.request(params).then(function(response) {
                    if(typeof response.result !== 'undefined') {
                        response = response.result;
                    }
                    if(response.success) {
                        Corrensa_Settings_JS.connectingDialog.activeStep(2);
                        // Sync data
                        var params = {
                            module: 'Corrensa',
                            action: 'SettingAjax',
                            mode : 'syncData'
                        };

                        AppConnector.request(params).then(function(response) {
                            if(typeof response.result !== 'undefined') {
                                response = response.result;
                            }
                            if(response && response.success) {
                                Corrensa_Settings_JS.connectingDialog.updateStatus(1, response.users.length);
                                funcSyncPermission('', response.users);
                            } else {
                                Corrensa_Settings_JS.connectingDialog.activeStep(3, false);
                            }
                        });
                    }
                }, function(error) {
                    alert(error);
                    Corrensa_Settings_JS.connectingDialog.hide();
                    Corrensa_Settings_JS.connectingDialog.reset();
                });
            });

            $('#btnLostPassword').click(function () {
                $('#corrensa-lost-password-screen').modal('show');
            });

            $('#btnForgetPasswordCorrensa').click(function(){
                var email = $('.modal-body-lostpassword input#inputEmail').val();
                var params = {
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode : 'forgetPassword'
                };

                AppConnector.request(params).then(function(response) {
                    // if(response.success) {
                    alert('A new password has been sent to your email address.');
                    $('#corrensa-lost-password-screen').modal('hide');
                    // }else {
                    //     alert('Your Email is not valid');
                    // }
                    // } else {
                    //     Corrensa_Settings_JS.updateDialog.hide();
                    // }
                });
                // }
            });

            $('#corrensa-lost-password-screen').on('shown.bs.modal', function () {
                var hBo = $(window).height();
                var wBo = $(window).width();

                var width = wBo / 100 * 95;
                var height = hBo / 100 * 95;
                var top = ((hBo - height) / 2) + 'px';
                var left = ((wBo - width) / 2) + 'px';

                if(width > 1200) {
                    width = wBo / 100 * 35;
                    height = 330;
                    top = ((hBo - height) / 2) -  100 + 'px';
                    left = ((wBo - width) / 2) + 'px';
                } else if(width > 1024 && width < 1200) {
                    width = wBo / 100 * 45;
                    height = 330;
                    top = ((hBo - height) / 2) + 'px';
                    left = ((wBo - width) / 2) + 'px';
                } else if(width > 800 && width < 1024) {
                    width = wBo / 100 * 70;
                    height = 330;
                    top = ((hBo - height) / 2) + 'px';
                    left = ((wBo - width) / 2) + 'px';
                } else {
                    width = wBo / 100 * 90;
                    height = 330;
                    top = ((hBo - height) / 2) + 'px';
                    left = ((wBo - width) / 2) + 'px';
                }

                $('#corrensa-lost-password-screen').css({
                    'top': top,
                    'left': left,
                    'width': width,
                    'max-width': width,
                    'height': height,
                    'max-height': height,
                });

                $('#corrensa-lost-password-screen .modal-body').height($('#corrensa-lost-password-screen').height() - 50);
            });

            $('#btnDisconnect').click(function () {
                if(confirm('Are you sure?')) {
                    var params = {
                        module: 'Corrensa',
                        action: 'SettingAjax',
                        mode : 'disconnect'
                    };

                    // Post login
                    AppConnector.request(params).then(function(response) {
                        // if(typeof response.success != 'undefined' && response.success) {
                        location.reload();
                        // } else {
                        //     alert("There are an issue while disconnecting. Please contact to support@vtexperts.com to resolve this issue.");
                        // }
                    });
                }
            });

            $('#btnUpdate').click(function () {
                Corrensa_Settings_JS.updateDialog.show();

                var funcSyncPermission = function(userId, userIds) {
                    var uid = userId || '';

                    AppConnector.request({
                        module: 'Corrensa',
                        action: 'SettingAjax',
                        mode : 'syncPermissionData',
                        user_id: userId,
                        user_ids: userIds
                    }).then(function(response) {
                        if(typeof response !== 'undefined') {
                            response = response.result;
                        }
                        if(response && response.success) {
                            if (response.finish){
                                Corrensa_Settings_JS.updateDialog.updateStatus(userIds.length, userIds.length);
                                Corrensa_Settings_JS.updateDialog.triggerDone();
                            }else{
                                Corrensa_Settings_JS.updateDialog.updateStatus(response.status, userIds.length);
                                funcSyncPermission(response.next_id, userIds);
                            }
                        }
                    });
                };

                var params = {
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode : 'reSyncData'
                };

                AppConnector.request(params).then(function(response) {
                    if(typeof response.result !== 'undefined') {
                        response = response.result;
                    }
                    if(response && response.success) {
                        Corrensa_Settings_JS.updateDialog.updateStatus(1, response.users.length);
                        funcSyncPermission('', response.users);
                    }
                }, function() {
                    Corrensa_Settings_JS.updateDialog.triggerFail();
                });
            });

            $('#btnCancelSynching').click(function () {
                var params = {
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode : 'cancelSyncData'
                };

                AppConnector.request(params).then(function(response) {
                    location.reload();
                });
            });

            $('#btnCancelUpdate').click(function () {
                var params = {
                    module: 'Corrensa',
                    action: 'SettingAjax',
                    mode : 'cancelUpdate'
                };

                AppConnector.request(params).then(function(response) {
                    location.reload();
                });
            });

            $('.setting-nav li a').click(function () {
                var tabName = $(this).data('tab');

                $('.setting-pane').removeClass('active');
                $('.'+tabName+'-pane').addClass('active');
                $('.setting-nav li').removeClass('active');
                $(this).parent().addClass('active');
            });

            $('#btnShowError').click(function () {
                $('#corrensa-show-error').modal('show');
            });
        },

        registerSignupEvents: function () {

            $('#corrensa-register-screen').on('shown.bs.modal', function () {
                var hBo = $(window).height();
                var wBo = $(window).width();

                var width = wBo / 100 * 95;
                var height = hBo / 100 * 95;

                var top = ((hBo - height) / 2) + 'px';
                var left = ((wBo - width) / 2) + 'px';

                if(width > 1200) {
                    width = wBo / 100 * 60;
                    height = 500;
                    top = ((hBo - height) / 2) - 100 + 'px';
                    left = ((wBo - width) / 2)+ 'px';
                } else if(width > 1024 && width < 1200) {
                    width = wBo / 100 * 80;
                    height = 500;
                    top = ((hBo - height) / 2) - 60 + 'px';
                    left = ((wBo - width) / 2) + 'px';
                } else if(width > 800 && width < 1024) {
                    width = wBo / 100 * 90;
                    height = 500;
                    top = ((hBo - height) / 2) - 40 + 'px';
                    left = ((wBo - width) / 2) + 'px';
                } else {
                    width = wBo / 100 * 90;
                    height = 500;
                    top = ((hBo - height) / 2) + 'px';
                    left = ((wBo - width) / 2) + 'px';
                }

                $('#corrensa-register-screen').css({
                    'top': top,
                    'left': left,
                    'width': width,
                    'max-width': width,
                    'height': height,
                    // 'max-height': height,
                });

                $('#corrensa-register-screen .modal-body').height($('#corrensa-register-screen').height() - 50);
            });

            $('body').on('click', '#btnRegisterCorrensa', function () {
                $('#corrensa-register-screen').modal('show');
            });

            $('#cbxToggleSupport').change(function () {
                var checked = $(this).prop('checked');

                if(checked) {
                    AppConnector.request({
                        module: 'Corrensa',
                        action: 'SettingAjax',
                        mode : 'enableSupport'
                    });
                    $('.sp-status').text('Support Enabled');
                } else {
                    AppConnector.request({
                        module: 'Corrensa',
                        action: 'SettingAjax',
                        mode : 'disableSupport'
                    });
                    $('.sp-status').text('Support Disabled');
                }
            });
        },

        registerDashboardEvents: function () {
            var wh = $(window).height();
            var ww = $(window).width();
            var topBarHeight = $('.navbar.navbar-fixed-top').height();
            var headerBarHeight = $('.corrensa_header').height();

            $('.dashboard-frame').height(wh - topBarHeight - headerBarHeight - 20);
            $('.hover-tooltip').tooltip();

            var uServer = new UServer('19021991lowa123');
            uServer.bind('closeRegisterModal', function () {
                $('#corrensa-register-screen').modal('hide');
                $('#inputEmail').focus();
            });
        },

        registerEvents: function () {
            Corrensa_Settings_JS.registerLoginFormEvents();
            Corrensa_Settings_JS.registerSignupEvents();
            Corrensa_Settings_JS.registerDashboardEvents();
        }
    };

    $(function () {
        Corrensa_Settings_JS.registerEvents();

        $(window).resize(function () {
            Corrensa_Settings_JS.registerDashboardEvents();
        });
    });
})(jQuery);