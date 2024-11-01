=== Smooth Locations ===
Tags: geolocation, locations, google maps
Requires at least: 3.5.1
Tested up to: 3.5.1
Stable tag: 1.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin to smoothly list locations in a table and on a map with an automatically updating search field.

== Description ==

This plugin can be used to list locations. It adds a custom post type for locations, which can be manipulated
via the admin panel. The locations can be displayed on a map and in a table. In addition, a search field can
be added that allows users to search for relevant locations.

The functionality is added to a page or post via shortcodes:

 * [smooth-location-map]
 * [smooth-location-search]
 * [smooth-location-table]

The settings can be configured by passing attributes to the shortcodes, like so:

[smooth-location-map width="500px" height="300px" ...more attributes]

None of the attributes are required. The following attributes are available:

 * width: defaults to "500px"
 * height: defaults to "300px"
 * center: defaults to "37.752530,-122.447777"
 * geolocation: defaults to "false"
 * maptype: defaults to "HYBRID"
 * zoom: defaults to 7
 * icon_url: url for different icon image
 * shadow_url: url for shadow of different icon image

The same functionality is available via do_action() calls with the same name as the shortcode.

In addition, the following filters are available to modify the HTML output, if necessary:

 * smooth_location_map_filter
 * smooth_location_search_filter
 * smooth_location_table_filter

== Installation ==

1. Upload the `smooth-locations` directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the relevant shortcodes on your posts/pages:
 * [smooth-location-search]
 * [smooth-location-map] 
 * [smooth-location-table]
4. Add your locations in the admin panel under 'Locations'

Alternatively, place the relevant do_action() calls in your theme/plugin.

== Frequently Asked Questions ==

= How do I change the center of the map? =

Set the "center" attribute for the smooth-location-map shortcode:

[smooth-location-map center="37.752530,-122.447777"]

= How do I enable geolocation? =

Set the "geolocation" attribute for the smooth-location-map shortcode:

[smooth-location-map geolocation="true"]

= How do I change the width and height? =

Set the "width" and "height" attributes for the smooth-location-map shortcode.

= How do I change the map type? =

Set the "maptype" attribute for the smooth-location-map shortcode. You can choose from
the following types:

 * HYBRID
 * ROADMAP
 * SATELLITE
 * TERRAIN

= How do I change the placeholder for the search field? =

Set the "placeholder" attribute for the smooth-location-search shortcode.

= Can you extend this plugin to do X? =

Yes we can, send us an e-mail.

== Changelog ==

= 1.1 =
* Find geocodes by clicking on a integrated map
* Support for custom markers
* Add map type select
* Adjust initial zoom level of the embedded map

= 1.0 =
* Initial release