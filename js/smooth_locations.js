jQuery(document).ready(function($) {
	jQuery('#smooth-location-search-field').keyup(function(e){
		var search_str = $(this).val();
		populateTable(search_str);
	});
});

var markersArray = [];
var map;
var infowindow;

function getLatLng(geocode) {
	var latlng = geocode.split(',');
	var lat = parseFloat(latlng[0]);
	var lng = parseFloat(latlng[1]);
	return new google.maps.LatLng(lat, lng);
}

function getMapType(maptype) {
	switch(maptype) {
		case "HYBRID":
			type = google.maps.MapTypeId.HYBRID;
		break;
		case "TERRAIN":
			type = google.maps.MapTypeId.TERRAIN;
		break;
		case "SATELLITE":
			type = google.maps.MapTypeId.SATELLITE;
		break;
		case "ROADMAP":
			type = google.maps.MapTypeId.ROADMAP;
		break;
		default:
			type = google.maps.MapTypeId.HYBRID;
		break;
	}

	return type;
}

function initialize() {
	var mapOptions = {
		center: getLatLng(smooth_location_settings.map_center),
		zoom: parseInt(smooth_location_settings.map_zoom),
		mapTypeId: getMapType(smooth_location_settings.map_type),
		mapTypeControl: false
	};

	map = new google.maps.Map(document.getElementById("smooth-location-map-canvas"), mapOptions);

	if (smooth_location_settings.map_geolocation === "true" && navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			currentLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
			map.setCenter(currentLocation);
		});
	}

	infowindow = new google.maps.InfoWindow({
		content: ""
	});

	populateTable('');
}

google.maps.event.addDomListener(window, 'load', initialize);

function deleteOverlays() {
	if (markersArray) {
		for (i in markersArray) {
			markersArray[i].setMap(null);
		}

		markersArray.length = 0;
	}
}

function clearOverlays() {
	if (markersArray) {
		for (i in markersArray) {
			markersArray[i].setMap(null);
		}
	}
}

function showOverlays() {
	if (markersArray) {
		for (i in markersArray) {
			markersArray[i].setMap(map);
		}
	}
}

function addMarker(location, title, address) {
	marker = new google.maps.Marker({
		position: location,
		map: map,
		title: title,
		icon: smooth_location_settings.icon_url,
		shadow: {
			url: smooth_location_settings.shadow_url,
//			anchor: new google.maps.Point(16, 34) // modify if necessary
		}
	});

	markersArray.push(marker);

	google.maps.event.addListener(marker, 'click', function () {
		infowindow.setContent(title + ', ' + address);
		infowindow.open(map, this);
	});
}

function populateTable(search_str) {
	jQuery.ajax({
		url: smooth_location_settings.ajaxurl,
		data: {
			'action':'do_ajax',
			'fn':'get_smooth_locations',
			'search': search_str
		},
		dataType: 'JSON',
		success: function(data) {
			jQuery('#smooth-location-table').find("tr").remove();
			deleteOverlays();

			if (!data) {
				return;
			}

			for (i = 0; i < data.locations.length; i++) {
				var name = data.locations[i]['name'];
				var city = data.locations[i]['city'];
				var geocode = data.locations[i]['geocode'];
				var country = data.locations[i]['country'];
				jQuery('#smooth-location-table > tbody:last').append('<tr><td>'+name+'</td><td>'+city+'</td><td>'+geocode+'</td><td>'+country+'</td></tr>');

				// update geocodes
				addMarker(getLatLng(geocode), name, city + ', ' + country);
			}

			showOverlays();
		},
		error: function(errorThrown) {
			jQuery('#smooth-location-table').find("tr").remove();
		}
	});
}
