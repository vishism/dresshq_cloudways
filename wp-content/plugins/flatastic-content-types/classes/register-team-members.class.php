<?php

if (!class_exists('MAD_TEAM_MEMBERS')) {

	class MAD_TEAM_MEMBERS extends MAD_CONTENT_TYPES {

		public $slug = 'team-members';

		function __construct() {
			$this->init();
		}

		public function init() {

			$args = array(
				'labels' => $this->getLabels(
					__('Team Member', 'mad_app_textdomain'),
					__('Team Members', 'mad_app_textdomain')
				),
				'public' => false,
				'archive' => true,
				'exclude_from_search' => false,
				'publicly_queryable' => true,
				'show_ui' => true,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => true,
				'menu_position' => null,
				'taxonomies' => array('team_category'),
				'supports' => array('title', 'editor', 'thumbnail'),
				'rewrite' => array('slug' => $this->slug),
				'show_in_admin_bar' => true,
				'menu_icon' => 'dashicons-businessman'
			);

			register_post_type($this->slug, $args);

			register_taxonomy('team_category', $this->slug, array(
				'hierarchical' => true,
				"label" => "Categories",
				'query_var' => true,
				'rewrite' => true,
				'public' => true,
				'show_admin_column' => true
			) );

			flush_rewrite_rules(false);

			add_action("manage_" . $this->slug . "_posts_columns", array(&$this, "show_edit_columns"));
			add_action("manage_" . $this->slug . "_posts_custom_column", array(&$this, "show_edit_columns_content"));
		}

		public function show_edit_columns($columns) {
			$new_columns = array(
				'cb'    => '<input type="checkbox" />',
				"thumb column-comments" => __('Thumb', 'mad_app_textdomain'),
				"title" => __("Name", 'mad_app_textdomain'),
				"position" => __("Position", 'mad_app_textdomain'),
				"facebook" => __("Facebook", 'mad_app_textdomain'),
				"twitter" => __("Twitter", 'mad_app_textdomain'),
				"gplus" => __("Google Plus", 'mad_app_textdomain'),
				"pinterest" => __("Pinterest", 'mad_app_textdomain'),
				"instagram" => __("Instagram", 'mad_app_textdomain')
			);
			$columns = array_merge($new_columns, $columns);
			return $columns;
		}

		public function show_edit_columns_content($column) {
			global $post;

			switch ($column) {
				case "thumb column-comments":
					if (has_post_thumbnail($post->ID)) {
						echo get_the_post_thumbnail($post->ID, array(60, 60));
					}
					break;
				case "position":
					echo get_post_meta($post->ID, 'mad_tm_position', true);
					break;
				case "facebook":
					echo get_post_meta($post->ID, 'mad_tm_facebook', true);
					break;
				case "twitter":
					echo get_post_meta($post->ID, 'mad_tm_twitter', true);
					break;
				case "gplus":
					echo get_post_meta($post->ID, 'mad_tm_gplus', true);
					break;
				case "pinterest":
					echo get_post_meta($post->ID, 'mad_tm_pinterest', true);
					break;
				case "instagram":
					echo get_post_meta($post->ID, 'mad_tm_instagram', true);
					break;
			}
		}

	}

}