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
	<script type="text/javascript" src="layouts/v7/modules/CTMobileSettings/resources/moment.min.js"></script>
	<script type="text/javascript" src="layouts/v7/modules/CTMobileSettings/resources/daterangepicker.min.js"></script>
	<link rel="stylesheet" type="text/css" href="layouts/v7/modules/CTMobileSettings/daterangepicker.css" />
	{if $API_KEY neq ''}
			 <script async defer
			src="https://maps.googleapis.com/maps/api/js?key={$API_KEY}&callback=initMap">
			</script>
			{literal}
					<script type='text/javascript'>
					var flightPath;
					var map;
					function initMap() {
						map = new google.maps.Map(document.getElementById('map'), {
						  zoom: 3,
						  center: {lat: 0, lng: -180},
						  mapTypeId: 'terrain'
						});

						var flightPlanCoordinates = [
						  {lat: 37.772, lng: -122.214},
						  {lat: 21.291, lng: -157.821},
						  {lat: -18.142, lng: 178.431},
						  {lat: -27.467, lng: 153.027}
						];
						flightPath = new google.maps.Polyline({
						  path: flightPlanCoordinates,
						  geodesic: true,
						  strokeColor: '#FF0000',
						  strokeOpacity: 1.0,
						  strokeWeight: 2
						});

						flightPath.setMap(null);
					}
					
					jQuery(document).ready(function () {
					 jQuery('#routeUser').on('change',function(){
						var params = {};
						var daterange = jQuery('input[name="daterange"]').val();
						params['module'] = app.getModuleName();
						params['parent'] = app.getParentModuleName();
						params['action'] = 'getUserLatLong';
						params['user_id'] = jQuery(this).val();
						params['daterange'] = daterange;
						params['mode'] = 'poliline';
						AppConnector.request(params).then(
						function(data) {
						
							var result = data.result.poliLine;
							var flightPlanCoordinates = [];
							jQuery.each(result, function(index, item) {
								flightPlanCoordinates.push(item);
							});
							var result2 = data.result.marker;
							var flightPlanCoordinates2 = [];
							jQuery.each(result2, function(index, item) {
								flightPlanCoordinates2.push([item.label,item.lat,item.lng]);
							});
							initMap();
							map.setCenter({lat:result[0].lat, lng:result[0].lng});
							map.setZoom(10);
							flightPath = new google.maps.Polyline({
							  path: flightPlanCoordinates,
							  geodesic: true,
							  strokeColor: '#FF0000',
							  strokeOpacity: 1.0,
							  strokeWeight: 2
							});
							
							var infowindow = new google.maps.InfoWindow();

							var marker, i;

							for (i = 0; i < flightPlanCoordinates2.length; i++) {  
							  marker = new google.maps.Marker({
								position: new google.maps.LatLng(flightPlanCoordinates2[i][1], flightPlanCoordinates2[i][2]),
								map: map,
							  });
							
							  google.maps.event.addListener(marker, 'mouseover', (function(marker, i) {
								return function() {
								  infowindow.setContent(flightPlanCoordinates2[i][0]);
								  infowindow.open(map, marker);
								}
							  })(marker, i));
							  google.maps.event.addListener(marker, 'click', (function(marker, i) {
								return function() {
									 if (marker.getAnimation() !== null) {
									  marker.setAnimation(null);
									} else {
									  marker.setAnimation(google.maps.Animation.BOUNCE);
									}
								}
							  })(marker, i));
							}
							flightPath.setMap(map);
					});
				});
				jQuery('#daterange').on('change',function(){
						var params = {};
						var daterange = jQuery(this).val();
						params['module'] = app.getModuleName();
						params['parent'] = app.getParentModuleName();
						params['action'] = 'getUserLatLong';
						params['user_id'] = jQuery('#routeUser').val();
						params['daterange'] = daterange;
						params['mode'] = 'poliline';
						AppConnector.request(params).then(
						function(data) {
							var result = data.result.poliLine;
							var flightPlanCoordinates = [];
							jQuery.each(result, function(index, item) {
								flightPlanCoordinates.push(item);
							});
							var result2 = data.result.marker;
							var flightPlanCoordinates2 = [];
							jQuery.each(result2, function(index, item) {
								flightPlanCoordinates2.push([item.label,item.lat,item.lng]);
							});
							initMap();
							map.setCenter({lat:result[0].lat, lng:result[0].lng});
							map.setZoom(10);
							flightPath = new google.maps.Polyline({
							  path: flightPlanCoordinates,
							  geodesic: true,
							  strokeColor: '#FF0000',
							  strokeOpacity: 1.0,
							  strokeWeight: 2
							});
							
							var infowindow = new google.maps.InfoWindow();

							var marker, i;

							for (i = 0; i < flightPlanCoordinates2.length; i++) {  
							  marker = new google.maps.Marker({
								position: new google.maps.LatLng(flightPlanCoordinates2[i][1], flightPlanCoordinates2[i][2]),
								icon:{strokeColor:"green"},
								map: map,
							  });
							  marker.setAnimation(google.maps.Animation.DROP);
							  google.maps.event.addListener(marker, 'mouseover', (function(marker, i) {
								return function() {
								  infowindow.setContent(flightPlanCoordinates2[i][0]);
								  infowindow.open(map, marker);
								}
							  })(marker, i));
							  google.maps.event.addListener(marker, 'click', (function(marker, i) {
								return function() {
									 if (marker.getAnimation() !== null) {
									  marker.setAnimation(null);
									} else {
									  marker.setAnimation(google.maps.Animation.BOUNCE);
									}
								}
							  })(marker, i));
							}
							flightPath.setMap(map);
						});
					});
			});
			</script>
		   {/literal}
   {else}
		 <script src="https://api-maps.yandex.ru/2.1/?lang=en_US&amp;" type="text/javascript"></script>
		{literal}
				<script type='text/javascript'>
							ymaps.ready(init);
							var myMap = '';
							function init() {
							// Creating the map.
								myMap = new ymaps.Map("map", {
										center: [0,-180],
										zoom: 2,
										controls: ['zoomControl','typeSelector']
									}
								);
							}
					jQuery(document).ready(function () { 
						jQuery('#routeUser').on('change',function(){
							var params = {};
							var daterange = jQuery('input[name="daterange"]').val();
							params['module'] = app.getModuleName();
							params['parent'] = app.getParentModuleName();
							params['action'] = 'getUserLatLong';
							params['user_id'] = jQuery(this).val();
							params['daterange'] = daterange;
							AppConnector.request(params).then(
							function(data) {
								var result1 = data.result.poliLine;
								var polyLine4 = [];
								jQuery.each(result1, function(index, item) {
									polyLine4.push([item.lat,item.lng]);
								});
								var result2 = data.result.marker;
									var flightPlanCoordinates2 = [];
									jQuery.each(result2, function(index, item) {
										flightPlanCoordinates2.push([item.label,item.lat,item.lng]);
								});
								myMap.geoObjects.removeAll();
								myMap.setCenter([result1[0].lat,result1[0].lng]);
								myMap.setZoom(12);
								 var myPolyline = new ymaps.Polyline(polyLine4, {
								}, {
									balloonCloseButton: false,
									strokeColor: "#FF0000",
									strokeWidth: 4,
									strokeOpacity: 0.5
								});
							    myMap.geoObjects.add(myPolyline);
							    for (i = 0; i < flightPlanCoordinates2.length; i++) {
									myMap.geoObjects.add(new ymaps.Placemark([flightPlanCoordinates2[i][1],flightPlanCoordinates2[i][2]], {
										balloonContent: flightPlanCoordinates2[i][0]
									}, {
										preset: 'islands#icon',
										iconColor: '#0095b6'
									}));
								}
							});
						});
						jQuery('#daterange').on('change',function(){
								var params = {};
								var daterange = jQuery(this).val();
								params['module'] = app.getModuleName();
								params['parent'] = app.getParentModuleName();
								params['action'] = 'getUserLatLong';
								params['user_id'] = jQuery('#routeUser').val();
								params['daterange'] = daterange;
								AppConnector.request(params).then(
								function(data) {
											var result1 = data.result.poliLine;
											var polyLine4 = [];
											jQuery.each(result1, function(index, item) {
												polyLine4.push([item.lat,item.lng]);
											});
											var result2 = data.result.marker;
												var flightPlanCoordinates2 = [];
												jQuery.each(result2, function(index, item) {
													flightPlanCoordinates2.push([item.label,item.lat,item.lng]);
											});
											myMap.geoObjects.removeAll();
											myMap.setCenter([result1[0].lat,result1[0].lng]);
											myMap.setZoom(12);
											 var myPolyline = new ymaps.Polyline(polyLine4, {
											}, {
												balloonCloseButton: false,
												strokeColor: "#FF0000",
												strokeWidth: 4,
												strokeOpacity: 0.5
											});
											myMap.geoObjects.add(myPolyline);
											for (i = 0; i < flightPlanCoordinates2.length; i++) {
												myMap.geoObjects.add(new ymaps.Placemark([flightPlanCoordinates2[i][1],flightPlanCoordinates2[i][2]], {
													balloonContent: flightPlanCoordinates2[i][0]
												}, {
													preset: 'islands#icon',
													iconColor: '#0095b6'
												}));
											}
								});
							});
						});
				</script>
	   {/literal}
   {/if}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="layouts/v7/modules/CTMobileSettings/CustomStyle.css" rel="stylesheet" type="text/css">
<label style="font:24px solid black;margin-left:20px;margin-top:10px;">{vtranslate("MODULE_LBL",$MODULE)}</label>
<hr>
<div class="row">
  <div class="container-fluid">
    <div class="row-fluid">
    
	<div class="main_div">
		<div class="box2">
			<div class="row">
			<select data-fieldname="routeUser" id="routeUser" data-fieldtype="picklist" class="inputElement select2" style="width:60%;float:left;margin-left:1%;">
			<option value="">Select an user</option>
			{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$ROUTE_USER}
			<option value="{$PICKLIST_VALUE['id']}">{$PICKLIST_VALUE['name']}</option>
			{/foreach}
			</select>
			<input type="text" class="inputElement" id="daterange" name="daterange" value="" style="width:30%;display: inline!important;float:right;margin-right:1%;"/>
			</div>
			<div id='map' style="width:100%;height:420px; margin: 0; padding: 0;">
  			</div>
		</div>
	</div>
	</div>
	</div>
	</div>
	
