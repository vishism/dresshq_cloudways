<?php
/*
Plugin Name: Flatastic Content Types
Description: Content Types for Flatastic eCommerce Theme.
Version: 1.0
Author: mad_velikorodnov
Author URI: inthe7heaven.com
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if (!class_exists('MAD_CONTENT_TYPES')) {

	class MAD_CONTENT_TYPES {

		public $paths = array();
		public static $view_path;

		public $content_types_classes = array(
			'MAD_PORTFOLIO', 'MAD_TESTIMONIALS', 'MAD_TEAM_MEMBERS'
		);

		function __construct() {

			// Load text domain
			add_action('plugins_loaded', array( &$this, 'load_textdomain' ) );

			$dir = dirname(__FILE__);

			$this->paths = array(
				'APP_ROOT' => $dir,
				'APP_DIR' => basename( $dir ),
				'CLASSES_PATH' => $dir . '/classes/',
				'MAD_VIEWS_PATH' => trailingslashit($dir) . trailingslashit('view')
			);

			self::$view_path = $this->paths['MAD_VIEWS_PATH'];

			$this->include_post_types_classes();

			// Register content types
			add_action('init', array( &$this, 'init_post_types_classes' ) );
		}

		// include post types classes
		function include_post_types_classes() {
			foreach (glob($this->paths['CLASSES_PATH'] . '*.php') as $file) {
				include_once($file);
			}
		}

		// init post types classes
		function init_post_types_classes() {
			foreach ($this->content_types_classes as $content_type_class) {
				new $content_type_class;
			}
		}

		// load plugin text domain
		function load_textdomain() {
			load_plugin_textdomain( 'mad_app_textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		// Get content type labels
		function getLabels($singular_name, $name, $title = FALSE) {
			if ( !$title )
				$title = $name;

			return array(
				"name" => $title,
				"singular_name" => $singular_name,
				"add_new" => __("Add New", 'mad_app_textdomain'),
				"add_new_item" => sprintf( __("Add New %s", 'mad_app_textdomain'), $singular_name),
				"edit_item" => sprintf( __("Edit %s", 'mad_app_textdomain'), $singular_name),
				"new_item" => sprintf( __("New %s", 'mad_app_textdomain'), $singular_name),
				"view_item" => sprintf( __("View %s", 'mad_app_textdomain'), $singular_name),
				"search_items" => sprintf( __("Search %s", 'mad_app_textdomain'), $name),
				"not_found" => sprintf( __("No %s found", 'mad_app_textdomain'), $name),
				"not_found_in_trash" => sprintf( __("No %s found in Trash", 'mad_app_textdomain'), $name),
				"parent_item_colon" => ""
			);
		}

		// Get content type taxonomy labels
		function getTaxonomyLabels($singular_name, $name) {
			return array(
				"name" => $name,
				"singular_name" => $singular_name,
				"search_items" => sprintf( __("Search %s", 'mad_app_textdomain'), $name),
				"all_items" => sprintf( __("All %s", 'mad_app_textdomain'), $name),
				"parent_item" => sprintf( __("Parent %s", 'mad_app_textdomain'), $singular_name),
				"parent_item_colon" => sprintf( __("Parent %s:", 'mad_app_textdomain'), $singular_name),
				"edit_item" => sprintf( __("Edit %", 'mad_app_textdomain'), $singular_name),
				"update_item" => sprintf( __("Update %s", 'mad_app_textdomain'), $singular_name),
				"add_new_item" => sprintf( __("Add New %s", 'mad_app_textdomain'), $singular_name),
				"new_item_name" => sprintf( __("New %s Name", 'mad_app_textdomain'), $singular_name),
				'not_found' => sprintf(__('No %s found', 'mad_app_textdomain'), $singular_name),
				'not_found_in_trash' => sprintf(__('No %s found in Trash', 'mad_app_textdomain'), $singular_name),
				"menu_name" => $name,
			);
		}

		function output_html($view, $data = array()) {
			@extract($data);
			ob_start();
			include(self::$view_path . $view . '.php');
			return ob_get_clean();
		}

	}

	new MAD_CONTENT_TYPES();

}
