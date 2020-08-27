 /*+*******************************************************************************
 * The content of this file is subject to the CRMTiger Pro license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is vTiger
 * The Modified Code of the Original Code owned by https://crmtiger.com/
 * Portions created by CRMTiger.com are Copyright(C) CRMTiger.com
 * All Rights Reserved.
  ***************************************************************************** */


jQuery.Class('CTMobileSettings_CTMobileSettings_Js', {}, {
	
	registerAppTriggerEvent : function() {
        jQuery('.app-menu').removeClass('hide');
        var toggleAppMenu = function(type) {
            var appMenu = jQuery('.app-menu');
            var appNav = jQuery('.app-nav');
            appMenu.appendTo('#page');
            appMenu.css({
                'top' : appNav.offset().top + appNav.height(),
                'left' : 0
            });
            if(typeof type === 'undefined') {
                type = appMenu.is(':hidden') ? 'show' : 'hide';
            }
            if(type == 'show') {
                appMenu.show(200, function() {});
            } else {
                appMenu.hide(200, function() {});
            }
        };

        jQuery('.app-trigger, .app-icon, .app-navigator').on('click',function(e){
            e.stopPropagation();
            toggleAppMenu();
        });

        jQuery('html').on('click', function() {
            toggleAppMenu('hide');
        });

        jQuery(document).keyup(function (e) {
            if (e.keyCode == 27) {
                if(!jQuery('.app-menu').is(':hidden')) {
                    toggleAppMenu('hide');
                }
            }
        });

        jQuery('.app-modules-dropdown-container').hover(function(e) {
            var dropdownContainer = jQuery(e.currentTarget);
            jQuery('.dropdown').removeClass('open');
            if(dropdownContainer.length) {
                if(dropdownContainer.hasClass('dropdown-compact')) {
                    dropdownContainer.find('.app-modules-dropdown').css('top', dropdownContainer.position().top - 8);
                } else {
                    dropdownContainer.find('.app-modules-dropdown').css('top', '');
                }
                dropdownContainer.addClass('open').find('.app-item').addClass('active-app-item');
            }
        }, function(e) {
            var dropdownContainer = jQuery(e.currentTarget);
            dropdownContainer.find('.app-item').removeClass('active-app-item');
            setTimeout(function() {
                if(dropdownContainer.find('.app-modules-dropdown').length && !dropdownContainer.find('.app-modules-dropdown').is(':hover') && !dropdownContainer.is(':hover')) {
                    dropdownContainer.removeClass('open');
                }
            }, 500);

        });

        jQuery('.app-item').on('click', function() {
            var url = jQuery(this).data('defaultUrl');
            if(url) {
                window.location.href = url;
            }
        });

        jQuery(window).resize(function() {
            jQuery(".app-modules-dropdown").mCustomScrollbar("destroy");
            app.helper.showVerticalScroll(jQuery(".app-modules-dropdown").not('.dropdown-modules-compact'), {
                setHeight: $(window).height(),
                autoExpandScrollbar: true
            });
            jQuery('.dropdown-modules-compact').each(function() {
                var element = jQuery(this);
                var heightPer = parseFloat(element.data('height'));
                app.helper.showVerticalScroll(element, {
                    setHeight: $(window).height()*heightPer - 3,
                    autoExpandScrollbar: true,
                    scrollbarPosition: 'outside'
                });
            });
        });
        app.helper.showVerticalScroll(jQuery(".app-modules-dropdown").not('.dropdown-modules-compact'), {
            setHeight: $(window).height(),
            autoExpandScrollbar: true,
            scrollbarPosition: 'outside'
        });
        jQuery('.dropdown-modules-compact').each(function() {
            var element = jQuery(this);
            var heightPer = parseFloat(element.data('height'));
            app.helper.showVerticalScroll(element, {
                setHeight: $(window).height()*heightPer - 3,
                autoExpandScrollbar: true,
                scrollbarPosition: 'outside'
            });
        });
    },
		
    UninstallCTMobile : function(){
		jQuery('.unInstallCTMobile').live('click',function(){
			var params = {};
			params['module'] = app.getModuleName();
			params['parent'] = app.getParentModuleName();
			params['action'] = 'Uninstall';
			var message = app.vtranslate('MSG_CTMOBILE_POPUP1')+'<br/>'+app.vtranslate('MSG_CTMOBILE_POPUP1_2');
			var message1 = app.vtranslate('MSG_CTMOBILE_POPUP2');
			app.helper.showConfirmationBox({'message' : message}).then(function(data) {
				app.helper.showConfirmationBox({'message' : message1}).then(function(data) {
					app.request.post({data: params}).then(function(err, response) {
						if(response){
							var params = {
								title : app.vtranslate('MSG_CTMOBILE_UNISTALL'),
								text: app.vtranslate('MSG_CTMOBILE_UNISTALL'),
								animation: 'show',
								type: 'success'
							};
							Vtiger_Helper_Js.showPnotify(params);
							window.location.href = response;
						}	
					});
				});
			});
		});
	},
	DeactivateCTMobileLicense : function(){
		jQuery('#deactivateLicense').live('click',function(){
			var params = {};
			params['module'] = app.getModuleName();
			params['parent'] = app.getParentModuleName();
			params['action'] = 'Deactivate';
			var message1 = app.vtranslate('MSG_CTMOBILE_DEACTIVATE_POPUP');
			app.helper.showConfirmationBox({'message' : message1}).then(function(data) {
				app.request.post({data: params}).then(function(err, response) {
					if(response){
						var params = {
							title : response.message,
							text: response.message,
							animation: 'show',
							type: 'success'
						};
						Vtiger_Helper_Js.showPnotify(params);
						location.reload();
					}	
				});
			});
		});
	},
/**
* Function to register events
*/
	registerEvents: function () {
		var thisInstance = this;
		this.registerAppTriggerEvent();
		this.UninstallCTMobile();
		this.DeactivateCTMobileLicense();
		
	}
})



	
jQuery(document).ready(function () {
	var view =app.view();
	if(view == 'TeamTracking'){
	 var date = new Date();
	   var month = date.getMonth()+1;
	   var day = date.getDate();
	   var year = date.getFullYear();
	   var newdate = year + '/' + month + '/' + day;
	    if(day > 7){
		   day = day -7;
		   if(day < 10 ){
			   day = '0' + day;
		   }
		   if(month < 10 ){
			   month = '0' + month;
		   }
		   var lastDate = year + '/' + month + '/' + day;
	   }else{
		   var month31 = [1,3,5,7,8,10,12];
		   if(month > 1){
				day = 7 - day;
				month = month - 1;
				if(jQuery.inArray(month, month31) !== -1){
					day = 31 - day;
				}else{
					day = 30 - day;
				}
				year = year - 1;
				if(day < 10 ){
				   day = '0' + day;
				}
				if(month < 10 ){
				   month = '0' + month;
				}
				var lastDate = year + '/' + month + '/' + day;
		   }else{
				day = day -7;
				month = month - 1;
				if(jQuery.inArray(month, month31) !== -1){
					day = 31 - day;
				}else{
					day = 30 - day;
				}
				if(day < 10 ){
				   day = '0' + day;
				}
				if(month < 10 ){
				   month = '0' + month;
				}
				var lastDate = year + '/' + month + '/' + day;
		   }
	   }

	   var dateValue = lastDate + ' - ' + newdate;
	   jQuery('input[name="daterange"]').val(dateValue);
	   jQuery('input[name="daterange"]').daterangepicker({    locale: {
		  format: 'YYYY/MM/DD'
		}});
	}
    var CTMobileSettingsInstance = new CTMobileSettings_CTMobileSettings_Js();
    CTMobileSettingsInstance.registerEvents();
	
    jQuery('.editButton').on('click',function(){
		var url = jQuery(this).attr('data-url');
		location.href = url;
	});
	jQuery('#livetrackingUser').on('click',function(){
		var url = jQuery(this).attr('data-url');
		location.href = url;
	});
	jQuery('#ctmobileAccessUser').on('click',function(){
		var url = jQuery(this).attr('data-url');
		location.href = url;
	});
	 
	jQuery('#save_license_settings').on('click',function(){
		var License_Key = jQuery('input[name="License_Key"]').val();
		if(License_Key == ''){

			var params = {
				title : app.vtranslate('Please Enter License Key'),
				text: app.vtranslate('Please Enter License Key'),
				animation: 'show',
				type: 'error'
			};
			Vtiger_Helper_Js.showPnotify(params);
		}else{
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});

			var params = {};
			params['module'] = app.getModuleName();
			params['parent'] = app.getParentModuleName();
			params['action'] = 'SaveLicense';
			params['license_key'] = License_Key;
			
			AppConnector.request(params).then(function(data) {
					progressIndicatorElement.progressIndicator({'mode' : 'hide'});
					var msg=data.result['msg'];
					var code =data.result['code'];
					if(code == 100){
						var params = {
							title : app.vtranslate(msg),
							text: msg,
							animation: 'show',
							type: 'error'
						};
						Vtiger_Helper_Js.showPnotify(params);	
					}else if(code == 101){
						var params = {
							title : app.vtranslate(msg),
							text: msg,
							animation: 'show',
							type: 'error'
						};
						Vtiger_Helper_Js.showPnotify(params);	
					}else{
						var params = {
							title : app.vtranslate(msg),
							text: msg,
							animation: 'show',
							type: 'info'
						};
						Vtiger_Helper_Js.showMessage(params);
						
						location.href = "index.php?module=CTMobileSettings&parent=Settings&view=LicenseDetail";
					}
			});
		}
	 
		
	});
 
});

		
