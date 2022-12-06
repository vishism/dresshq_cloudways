<?php
if (!class_exists('MAD_TESTIMONIALS')) {

	class MAD_TESTIMONIALS extends MAD_CONTENT_TYPES {

		public $slug = 'testimonials';

		function __construct() {
			$this->init();
		}

		public function init() {

			$args = array(
				'labels' => $this->getLabels(
					__('Testimonial', 'mad_app_textdomain'),
					__('Testimonials', 'mad_app_textdomain')
				),
				'public' => true,
				'archive' => true,
				'exclude_from_search' => false,
				'publicly_queryable' => true,
				'show_ui' => true,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => true,
				'menu_position' => null,
				'taxonomies' => array('testimonials_category'),
				'supports' => array('title', 'editor', 'thumbnail'),
				'rewrite' => array('slug' => $this->slug),
				'show_in_admin_bar' => true,
				'menu_icon' => 'dashicons-edit'
			);

			register_post_type($this->slug, $args);

			register_taxonomy('testimonials_category', $this->slug, array(
				'hierarchical' => true,
				"label" => "Categories",
				'query_var' => true,
				'rewrite' => true,
				'public' => true,
				'show_admin_column' => true
			) );

			flush_rewrite_rules(false);

			add_filter("manage_". $this->slug ."_posts_columns", array(&$this, "manage_testimonials_columns"));
			add_action("manage_". $this->slug ."_posts_custom_column", array(&$this, "manage_testimonials_custom_column"));
		}

		public function manage_testimonials_columns($columns) {
			$new_columns = array(
				"cb" => "<input type=\"checkbox\" />",
				"thumb column-comments" => __('Thumb', 'mad_app_textdomain'),
				"title" => __("Title", 'mad_app_textdomain'),
				"place" => __("Place", 'mad_app_textdomain')
			);
			$columns = array_merge($new_columns, $columns);
			return $columns;
		}

		public function manage_testimonials_custom_column($column) {
			global $post;

			switch ($column) {
				case "thumb column-comments":
					if (has_post_thumbnail($post->ID)) {
						echo get_the_post_thumbnail($post->ID, array(60, 60));
					}
					break;
				case "place":
						echo get_post_meta($post->ID, 'mad_tm_place', true);
					break;
			}
		}

	}

}