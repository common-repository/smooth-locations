<?php
/*
Plugin Name: SmoothLocations
Plugin URI: http://demo.brainstormit.nl/smooth-locations
Description: A plugin that allows you to list locations smoothly in a table and on a map
Version: 1.1
Author: Brainstorm IT
Author URI: http://www.brainstormit.nl
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
/*
Copyright 2013 Brainstorm IT <info@brainstormit.nl>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('BIT_Smooth_Locations')) {
	define('BIT_SL_VERSION', '1.1');
	define('BIT_SL_TDOMAIN', 'bit_smooth_locations_plugin');

	load_plugin_textdomain(BIT_SL_TDOMAIN, false, basename(dirname(__FILE__)) . '/languages');

	class BIT_Smooth_Locations {
		public function __construct() {
			add_action('wp_ajax_nopriv_do_ajax', array(&$this, 'get_smooth_locations'));
			add_action('wp_ajax_do_ajax', array(&$this, 'get_smooth_locations'));

			add_action('init', array(&$this, 'create_smooth_location_post_type'));
			add_action('admin_init', array(&$this, 'add_smooth_meta_box'));
			add_action('save_post', array(&$this, 'save_smooth_meta_box'));

			add_shortcode('smooth-location-map', array(&$this, 'shortcode_render_smooth_location_map'));
			add_shortcode('smooth-location-table', array(&$this, 'shortcode_render_smooth_location_table'));
			add_shortcode('smooth-location-search', array(&$this, 'shortcode_render_smooth_location_search'));

			add_action('smooth_location_map', array(&$this, 'render_smooth_location_map'));
			add_action('smooth_location_table', array(&$this, 'render_smooth_location_table'));
			add_action('smooth_location_search', array(&$this, 'render_smooth_location_search'));

			// we enqueue scripts from render_smooth_location_map in order to make it conditional,
			// but otherwise we would do:
			// 	add_action('wp_enqueue_scripts', array(&$this, 'enqueue_smooth_location_scripts'));
			// for styles, however, there is no smooth way around this if we want to maintain HTML compliancy
			// and load the stylesheet in <head>, so:
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_smooth_location_stylesheets'));

			$version_field = 'bit_smooth_locations_version';
			$version = get_option($version_field);
			if (!$version) {
				update_option($version_field, BIT_SL_VERSION);
			} else if ($version !== BIT_SL_VERSION) {
				update_option($version_field, BIT_SL_VERSION);
				// update things, if necessary
			}

			// default settings
			define('SL_MAP_WIDTH', '500px');
			define('SL_MAP_HEIGHT', '300px');
			define('SL_MAP_CENTER', '37.752530,-122.447777');
			define('SL_MAP_GEOLOCATION', 'false');
			define('SL_MAP_TYPE', 'HYBRID');
			define('SL_MAP_ZOOM', '7');
			define('SL_MAP_MARKER', 'http://www.google.com/mapfiles/marker.png');
			define('SL_MAP_MARKER_SHADOW', 'http://www.google.com/mapfiles/shadow50.png');
		}

		// Shortcodes
		function shortcode_render_smooth_location_map($atts) {
			// EXAMPLE: [smooth-location-map width="500px" height="300px" center="37.752530,-122.447777" geolocation="true"]
			extract(shortcode_atts(array(	'width' => SL_MAP_WIDTH,
							'height' => SL_MAP_HEIGHT,
							'center' => SL_MAP_CENTER,
							'geolocation' => SL_MAP_GEOLOCATION,
							'maptype' => SL_MAP_TYPE,
							'zoom' => SL_MAP_ZOOM,
							'icon_url' => SL_MAP_MARKER,
							'shadow_url' => SL_MAP_MARKER_SHADOW), $atts));

			return $this->render_smooth_location_map($width, $height, $center, $geolocation, $maptype, $zoom, $icon_url, $shadow_url);
		}
		function shortcode_render_smooth_location_search($atts) {
			// EXAMPLE: [smooth-location-search placeholder="Search..."]
			extract(shortcode_atts(array('placeholder' => 'Search...'), $atts));
			return $this->render_smooth_location_search($placeholder);
		}
		function shortcode_render_smooth_location_table() {
			return $this->render_smooth_location_table();
		}

		// Actions
		function render_smooth_location_map($width = SL_MAP_WIDTH,
							$height = SL_MAP_HEIGHT,
							$center = SL_MAP_CENTER,
							$geolocation = SL_MAP_GEOLOCATION,
							$maptype = SL_MAP_TYPE,
							$zoom = SL_MAP_ZOOM,
							$icon_url = '',
							$shadow_url = '') {

			$jssettings = array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'map_center' => $center,
				'map_geolocation' => $geolocation,
				'map_type' => $maptype,
				'map_zoom' => $zoom,
				'icon_url' => $icon_url,
				'shadow_url' => $shadow_url,
			);

			$this->enqueue_smooth_location_scripts($jssettings);
			$html = '<div id="smooth-location-map-canvas" style="width: '.$width.'; height: '.$height.';"></div>';
			return apply_filters('smooth_location_map_filter', $html);
		}
		function render_smooth_location_search($placeholder = "Search...") {
			$html = '<input type="text" id="smooth-location-search-field" placeholder="'.$placeholder.'">';
			return apply_filters('smooth_location_search_filter', $html);
		}
		function render_smooth_location_table() {
			$html = '<table id="smooth-location-table"><tr></tr></table>';
			return apply_filters('smooth_location_table_filter', $html);
		}

		function enqueue_smooth_location_stylesheets() {
			wp_register_style('smooth-locations-style', plugins_url('/css/smooth-locations.css', __FILE__));
			wp_enqueue_style('smooth-locations-style');
		}
		function enqueue_smooth_location_scripts($settings) {
			wp_enqueue_script('googlemaps', 'http://maps.google.com/maps/api/js?sensor=true', array(), NULL, true);
			wp_enqueue_script('smooth_locations', plugins_url('/js/smooth_locations.min.js', __FILE__), array('jquery', 'googlemaps'));
			wp_localize_script('smooth_locations', 'smooth_location_settings', $settings);
		}

		function create_smooth_location_post_type() {
			register_post_type('smooth_location',
				array(
					'labels' => array(
						'name' => __('Locations', BIT_SL_TDOMAIN),
						'singular_name' => __('Location', BIT_SL_TDOMAIN),
						'add_new_item' => __('Add New Location', BIT_SL_TDOMAIN),
					),
				'public' => true,
				'supports' => array(false), // we use metabox instead, is better for searching
				)
			);
		}

		function add_smooth_meta_box() {
			add_meta_box(
				'smooth_location_name',
				__('Smooth Location', BIT_SL_TDOMAIN),
				array(&$this, 'render_meta_box_content'),
				'smooth_location',
				'normal',
				'high'
			);
		}

		function render_meta_box_content($post) {
			$this->enqueue_smooth_location_stylesheets();

			wp_nonce_field(plugin_basename(__FILE__), 'smooth_locations_plugin_noncename');

			$name = get_post_meta($post->ID, $key = 'smooth_location_name', $single = true);
			$geocode = get_post_meta($post->ID, $key = 'smooth_location_geocode', $single = true);
			$address = get_post_meta($post->ID, $key = 'smooth_location_address', $single = true);
			$city = get_post_meta($post->ID, $key = 'smooth_location_city', $single = true );
			$country = get_post_meta($post->ID, $key = 'smooth_location_country', $single = true);
			?>
			<div style="overflow:hidden;">
				<div class="options_group" style="width: 260px; float: left; margin-right: 1%;">
					<p class="form-field bit_location_name_field">
						<label for="smooth_location_name_field"><?php _e('Name', BIT_SL_TDOMAIN); ?></label>
						<input type="text" size=10 name="smooth_location_name_field" id="smooth_location_name_field" value="<?php echo esc_attr($name); ?>" placeholder="">
					</p>
					<p class="form-field bit_location_geocode_field">
						<label for="smooth_location_geocode_field"><?php _e('Geocode', BIT_SL_TDOMAIN); ?></label>
						<input type="text" size=10 name="smooth_location_geocode_field" id="smooth_location_geocode_field" value="<?php echo esc_attr($geocode); ?>" placeholder="Select by clicking on the map">
					</p>
					<p class="form-field bit_location_address_field">
						<label for="smooth_location_address_field"><?php _e('Address', BIT_SL_TDOMAIN); ?></label>
						<input type="text" size=10 name="smooth_location_address_field" id="smooth_location_address_field" value="<?php echo esc_attr($address); ?>" placeholder="">
					</p>
					<p class="form-field bit_location_city_field">
						<label for="smooth_location_city_field"><?php _e('City', BIT_SL_TDOMAIN); ?></label>
						<input type="text" size=10 name="smooth_location_city_field" id="smooth_location_city_field" value="<?php echo esc_attr($city); ?>" placeholder="">
					</p>
					<p class="form-field bit_location_country_field">
						<label for="smooth_location_country_field"><?php _e('Country', BIT_SL_TDOMAIN); ?></label>
						<input type="text" size=10 name="smooth_location_country_field" id="smooth_location_country_field" value="<?php echo esc_attr($country); ?>" placeholder="">
					</p>
				</div>
				<div id="map-canvas" style="width: 400px; height: 300px; float: left;"></div>
			</div>
			<?php
			wp_enqueue_script('googlemaps', 'http://maps.google.com/maps/api/js?sensor=true', array(), NULL, true);
			wp_enqueue_script('smooth_locations_admin', plugins_url('/js/smooth_locations_admin.min.js', __FILE__), array('jquery', 'googlemaps'));
			$settings = array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'map_center' => empty($geocode) ? SL_MAP_CENTER : $geocode,
				'map_marker' => $geocode,
				'map_zoom' => SL_MAP_ZOOM
			);
			wp_localize_script('smooth_locations_admin', 'smooth_location_admin_settings', $settings);
		}

		function save_smooth_meta_box($post_id) {
			if (!current_user_can('edit_post', $post_id)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}

			if ($_POST) {
				if (!isset($_POST['smooth_locations_plugin_noncename']) || !wp_verify_nonce($_POST['smooth_locations_plugin_noncename'], plugin_basename(__FILE__))) {
					return;
				}

				update_post_meta($post_id, 'smooth_location_name', sanitize_text_field($_POST['smooth_location_name_field']));
				update_post_meta($post_id, 'smooth_location_geocode', sanitize_text_field($_POST['smooth_location_geocode_field']));
				update_post_meta($post_id, 'smooth_location_address', sanitize_text_field($_POST['smooth_location_address_field']));
				update_post_meta($post_id, 'smooth_location_city', sanitize_text_field($_POST['smooth_location_city_field']));
				update_post_meta($post_id, 'smooth_location_country', sanitize_text_field($_POST['smooth_location_country_field']));

				// change the post's post_title to our metabox name, remove action temporarily to avoid infinite loop
				remove_action('save_post', array(&$this, 'save_smooth_meta_box'));
				$updated_post = array();
				$updated_post['ID'] = $post_id;
				$updated_post['post_title'] = sanitize_text_field($_POST['smooth_location_name_field']);
				wp_update_post($updated_post);
				add_action('save_post', array(&$this, 'save_smooth_meta_box'));
			}
		}

		function get_smooth_locations() {
			switch ($_REQUEST['fn']){
				case 'get_smooth_locations':
					global $wpdb;

					$search_keys = array(
						"'smooth_location_name'",
						"'smooth_location_address'",
						"'smooth_location_city'",
						"'smooth_location_country'"
						// we do not want to search for smooth_location_geocode
					);
					$search_str = '%' . mysql_escape_string($_REQUEST['search']) . '%';

					$query = $wpdb->prepare('SELECT post_id, GROUP_CONCAT(meta_value ORDER BY meta_key ASC) AS meta_location '
						. 'FROM '.$wpdb->postmeta.' WHERE meta_key IN (' . implode($search_keys, ',') . ') '
						. 'GROUP BY post_id HAVING (meta_location LIKE %s) ORDER BY meta_location ASC',
						$search_str);

					$locations_obj = array('locations' => array());
					$locations = $wpdb->get_results($query) or die(mysql_error());

					foreach ($locations as $location) {
						$meta = explode(',', $location->meta_location);
						$meta[4] = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'smooth_location_geocode' AND post_id = " . $location->post_id);
						$location_obj = array('name' => $meta[3], 'address' => $meta[0], 'city' => $meta[1], 'country' => $meta[2], 'geocode' => $meta[4]);
						$locations_obj['locations'][] = $location_obj;
					}

					echo json_encode($locations_obj);
				break;
				default:
				break;
			}

			die;
		}

		function log_me($message) {
			if (WP_DEBUG === true) {
				if (is_array($message) || is_object($message)) {
					error_log(print_r($message, true));
				} else {
					error_log($message);
				}
			}
		}
	}

	$GLOBALS['bit_smooth_locations'] = new BIT_Smooth_Locations();
}
?>
