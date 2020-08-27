/* ********************************************************************************
 * The content of this file is subject to the Corrensa ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

(function ($) {

    function getQueryStringParams(sParam)
    {
        var sPageURL = window.location.search.substring(1);
        if(typeof sPageURL == 'undefined') return '';

        var sURLVariables = sPageURL.split('&');
        for (var i = 0; i < sURLVariables.length; i++)
        {
            var sParameterName = sURLVariables[i].split('=');
            if (sParameterName[0] == sParam)
            {
                return sParameterName[1];
            }
        }
    }

    var StartupDialog = function () {
        this.element = null;
    };

    StartupDialog.markup = '<div class="modal fade" id="corrensa-startup-screen" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">\
                                <div class="modal-dialog">\
                                    <div class="modal-content">\
                                        <div class="modal-header">\
                                            <h4 class="modal-title">Corrensa</h4>\
                                        </div>\
                                        <div class="modal-body">\
                                            <p><img class="startup-image" src="layouts/vlayout/modules/Corrensa/resources/images/128.png" /></p>\
                                            <p>Thanks for installing Corrensa - Plugin for Gmail, Outlook & Office 365!</p>\
                                        </div>\
                                        <div class="modal-footer">\
                                            <button type="button" class="btn btn-default btnLater" data-dismiss="modal">Later</button>\
                                            <a class="btn btn-primary btnGoToSetting" href="index.php?module=Corrensa&parent=Settings&view=Settings">Configure Corrensa</a>\
                                        </div>\
                                    </div>\
                                </div>\
                            </div>';

    $('#corrensa-startup-screen').modal({backdrop: 'static', keyboard: false})


    StartupDialog.prototype.setClosePopup = function () {
        var params = {
            module: 'Corrensa',
            action: 'SettingAjax',
            mode : 'closeStartupPopup'
        };
        AppConnector.request(params).then(function(response) {});
    };

    StartupDialog.prototype.load = function () {
        var that = this;
        var existElement = $('#corrensa-startup-screen');
        if (existElement.length == 0) {
            $(StartupDialog.markup).appendTo('body');
            this.element = $('#corrensa-startup-screen');

            $('.btnLater').click(function () {
                that.element.modal('hide');
                $.jStorage.set('startup_screen_check', 1);
                that.setClosePopup();
            });

            $('.btnGoToSetting').click(function () {
                that.element.modal('hide');
                $.jStorage.set('startup_screen_check', 1);
                that.setClosePopup();
            });
        }
    };

    StartupDialog.prototype.show = function () {
        this.element.modal('show');
    };

    var VtigerStructureHandler = function () {

    };

    VtigerStructureHandler.prototype.listenAddEditUserEvent = function () {
        var currModule = getQueryStringParams('module'),
            currView = getQueryStringParams('view'),
            currRecord = getQueryStringParams('record');

        if(currModule == 'Users') {
            if(currView == 'Detail' || currView == 'PreferenceDetail') {

                var params = {
                    module: 'Corrensa',
                    action: 'Event',
                    mode : 'resolve'
                };

                AppConnector.request(params).then(function(response) {});
            }

            else if(currView == 'Edit' || currView == 'PreferenceEdit') {
                $('.recordEditView').submit(function (event) {
                    console.log('submit handled');
                    var isUserForm = $(this).find('[name="module"]').val() == 'Users' &&
                        $(this).find('[name="action"]').val() == 'Save';

                    if (!isUserForm) return;

                    var savingMode = $(this).find('[name="record"]').val() == '' ? 'add' : 'update';

                    var params = {
                        module: 'Corrensa',
                        action: 'Event',
                        mode : 'attach',
                        name: 'user_' + savingMode,
                        uid: $('[name="record"]').val(),
                        username: $('#Users_editView_fieldName_user_name').val()
                    };

                    AppConnector.request(params).then(function(response) {});
                });
            }
        }
    };

    VtigerStructureHandler.prototype.listenFieldUpdate = function () {
        //$('body').on('click', '.saveFieldDetails', function () {
        //    var fieldId = $(this).attr('data-field-id');
        //    var params = {
        //        module: 'Corrensa',
        //        action: 'Event',
        //        mode : 'attach',
        //        name: 'field.update',
        //        fid: fieldId
        //    };
        //
        //    AppConnector.request(params).then(function(response) {});
        //});


    };

    VtigerStructureHandler.prototype.listen = function () {
        this.listenAddEditUserEvent();
        this.listenFieldUpdate();
    };

    var Corrensa_EventHandle_Js = {
        checkAndShowStartupScreen: function () {
            if ($.jStorage.get('startup_screen_check') == null) {
                $.get('index.php?module=Corrensa&action=SettingAjax&mode=getSettings').done(function (result) {
                    if (result.success) {
                        var setting = result.result;
                        if (parseInt(setting.show_startup_screen)) {
                            var startupDialog = new StartupDialog();
                            startupDialog.load();
                            startupDialog.show();
                        } else {
                            $.jStorage.set('startup_screen_check', 1);
                        }
                    }
                });
            }
        },

        insertGlobalCss: function () {
            $('head').append('<link rel="stylesheet" type="text/css" href="layouts/vlayout/modules/Corrensa/resources/css/global.css" />');
        },

        actionHandle: function () {
            (new VtigerStructureHandler()).listen();
        },

        getQueryString: function (query, param) {
            var sPageURL = query;

            if(typeof sPageURL != 'string') return '';

            var sURLVariables = sPageURL.split('&');
            for (var i = 0; i < sURLVariables.length; i++)
            {
                var sParameterName = sURLVariables[i].split('=');
                if (sParameterName[0] == param)
                {
                    return sParameterName[1];
                }
            }
        },

        queryToObject: function (query) {
            var params = query.split('&');
            var object = {};
            for(var i in params) {
                var coms = params[i].split('=');
                object[coms[0]] = coms[1];
            }
            return object;
        },

        objectToQuery: function (object) {
            var params = [];
            for(var i in object) {
                params.push(i+'='+object[i]);
            }
            return params.join('&');
        },

        ajaxHandle: function () {
            var that = this;
            $(document).ajaxSend(function (event, jqxhr, settings) {
                var module = that.getQueryString(settings.data, 'module');
                var action = that.getQueryString(settings.data, 'action');
                var mode = that.getQueryString(settings.data, 'mode');

                if(module == 'LayoutEditor' && action == 'Field' && mode == 'save') {
                    var fieldId = that.getQueryString(settings.data, 'fieldid');
                    var sourceModule = that.getQueryString(settings.data, 'sourceModule');

                    jqxhr.done(function () {
                        var params = {
                            module: 'Corrensa',
                            action: 'Event',
                            mode : 'apply',
                            eventName: 'field_update',
                            fieldId: fieldId
                        };

                        AppConnector.request(params).then(function(response) {});
                    });
                } else if(module == 'LayoutEditor' && action == 'Field' && mode == 'add') {
                    var fieldLabel = that.getQueryString(settings.data, 'fieldLabel');
                    var sourceModule = that.getQueryString(settings.data, 'sourceModule');

                    jqxhr.done(function () {
                        var params = {
                            module: 'Corrensa',
                            action: 'Event',
                            mode : 'apply',
                            eventName: 'field_add',
                            fieldLabel: fieldLabel,
                            sourceModule: sourceModule
                        };

                        AppConnector.request(params).then(function(response) {});
                    });
                } else if(module == 'LayoutEditor' && action == 'Field' && mode == 'delete') {
                    var force = that.getQueryString(settings.data, 'force');

                    if(!force) {
                        var fieldId = that.getQueryString(settings.data, 'fieldid');

                        var tempSetting = settings;
                        jqxhr.abort();

                        var progressIndicatorElement = jQuery.progressIndicator();

                        var params = {
                            module: 'Corrensa',
                            action: 'Event',
                            mode : 'apply',
                            eventName: 'field_delete',
                            fieldId: fieldId
                        };

                        AppConnector.request(params).then(function(response) {
                            var params = {
                                module: 'LayoutEditor',
                                parent: 'Settings',
                                action: 'Field',
                                mode : 'delete',
                                fieldid: fieldId,
                                force: true
                            };

                            AppConnector.request(params).then(function(response) {
                                progressIndicatorElement.hide();
                                $('.editFields[data-field-id="'+fieldId+'"]').remove();
                            });
                        });

                        return jqxhr.promise();
                    }
                }
            });
        },

        registerEvents: function () {
            this.checkAndShowStartupScreen();
            this.insertGlobalCss();
            // this.actionHandle();
            // this.ajaxHandle();
        }
    };

    var Corrensa_CustomEmailRelated_Js = {

        waitUntil: function (params) {
            var waitFor = params.for;
            var task = params.task;
            var whileWaiting = typeof params.while != 'undefined' ? params.while : false;
            var frequency = typeof params.frequency != 'undefined' ? params.frequency : 300;

            var itl = setInterval(function () {
                if(waitFor()) {
                    clearInterval(itl);
                    task();
                } else {
                    if(whileWaiting) whileWaiting();
                }
            }, frequency);

            if(params.timeout) {
                setTimeout(function () {
                    console.log("Auto clear inteval")
                    clearInterval(itl);
                }, params.timeout);
            }
        },

        fixUrl: function (module) {
            var currentUrl = window.location.href;
            var basePart = currentUrl.split('index.php')[0];
            var currentRecordId = $('#recordId').val();
            var newHref = basePart + 'index.php?module='+module+'&relatedModule=Emails&view=Detail&record='+currentRecordId+'&mode=showRelatedList&tab_label=Emails';
            window.history.pushState(null, "", newHref)
        },

        emailTabHandle: function () {
            var query = location.href.split('?');
            if(query.length <= 1) {
                return;
            }

            query = query[1];

            if(
                !(query.indexOf('module=Potentials') !== -1 &&
                query.indexOf('relatedModule=Emails') !== -1) &&

                !(query.indexOf('module=HelpDesk') !== -1 &&
                query.indexOf('relatedModule=Emails') !== -1)
            ) {
                return;
            }

            var params = Corrensa_EventHandle_Js.queryToObject(query);
            var module = params.module;
            var action = params.action;
            var view = params.view;
            var relatedModule = params.relatedModule;
            var mode = params.mode;
            var record = params.record;
            var that = this;

            if((module == 'Potentials' || module == 'HelpDesk') && view == 'Detail') {
                if(mode == 'showRelatedList' && relatedModule == 'Emails') {
                    $('#relatedListNextPageButton').addClass('checked');
                    $('#relatedListPreviousPageButton').addClass('checked');
                    var tab = $('.related li.active');
                    tab.trigger('click');
                }
            }
        },

        handleAjax: function () {
            var that = this;
            $(document).ajaxSend(function (event, jqxhr, settings) {
                if(settings.type.toLowerCase() == 'get') {
                    if(
                        !(settings.url.indexOf('module=Potentials') !== -1 &&
                        settings.url.indexOf('relatedModule=Emails') !== -1) &&

                        !(settings.url.indexOf('module=HelpDesk') !== -1 &&
                        settings.url.indexOf('relatedModule=Emails') !== -1)
                    ) {
                        return;
                    }

                    var query = settings.url.split('?');
                    if(query.length <= 1) {
                        return;
                    }
                    query = query[1];

                    var params = Corrensa_EventHandle_Js.queryToObject(query);
                    var module = params.module;
                    var action = params.action;
                    var view = params.view;
                    var mode = params.mode;
                    var relatedModule = params.relatedModule;

                    if((module == 'Potentials' || module == 'HelpDesk') && relatedModule == 'Emails' &&
                        view == 'Detail' && mode == 'showRelatedList') {
                        params['module'] = 'Corrensa';
                        params['mainModule'] = module;
                        params['relatedModule'] = 'Emails';
                        params['view'] = 'PotentialEmail';
                        settings.url = 'index.php?'+Corrensa_EventHandle_Js.objectToQuery(params);
                    }
                }

                else {
                    if(typeof settings.data != 'string') return;

                    if(
                        !(settings.data.indexOf('module=Potentials') !== -1 &&
                        settings.data.indexOf('relatedModule=Emails') !== -1) &&

                        !(settings.data.indexOf('module=HelpDesk') !== -1 &&
                        settings.data.indexOf('relatedModule=Emails') !== -1)
                    ) {
                        return;
                    }

                    var params = Corrensa_EventHandle_Js.queryToObject(settings.data);
                    var module = params.module;
                    var action = params.action;
                    var view = params.view;
                    var mode = params.mode;
                    var relatedModule = params.relatedModule;

                    if((module == 'Potentials' || module == 'HelpDesk') && action == 'RelationAjax' &&
                        relatedModule == 'Emails' && mode == 'getRelatedListPageCount') {
                        params['action'] = 'EmailAjax';
                        params['module'] = 'Corrensa';
                        params['mainModule'] = module;
                        params['mode'] = 'getEmailPageCount';
                        settings.data = Corrensa_EventHandle_Js.objectToQuery(params);
                    } else if((module == 'Potentials' || module == 'HelpDesk') && view == 'Detail' &&
                        relatedModule == 'Emails' && mode == 'showRelatedList') {
                        params['action'] = 'EmailAjax';
                        params['module'] = 'Corrensa';
                        params['mainModule'] = module;
                        params['mode'] = 'getRelatedEmail';
                        settings.data = Corrensa_EventHandle_Js.objectToQuery(params);
                    }
                }
            });
        },

        registerEvents: function () {
            this.handleAjax();
            this.emailTabHandle();
        }
    };

    $(function () {
        Corrensa_EventHandle_Js.registerEvents();
        Corrensa_CustomEmailRelated_Js.registerEvents();
    });
})(jQuery);