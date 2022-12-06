<?php

if (!class_exists('MAD_PORTFOLIO')) {

	class MAD_PORTFOLIO extends MAD_CONTENT_TYPES {

		public $slug = 'portfolio';

		function __construct() {
			$this->init();
		}

		public function init() {
			$args = array(
				'labels' => $this->getLabels(
					__('Portfolio', 'mad_app_textdomain'),
					__('Portfolios', 'mad_app_textdomain')
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
				'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'tags', 'comments'),
				'rewrite' => array('slug' => $this->slug),
				'show_in_admin_bar' => true,
				'taxonomies' => array('post_tag'),
				'menu_icon' => 'dashicons-portfolio'
			);

			register_taxonomy("skills", array($this->slug), array(
				"hierarchical" => true,
				"labels" => $this->getTaxonomyLabels(
					__('Skill', 'mad_app_textdomain'),
					__('Skills', 'mad_app_textdomain')
				),
				"singular_label" => __("skill", 'mad_app_textdomain'),
				"show_tagcloud" => true,
				'query_var' => true,
				'rewrite' => true,
				'show_in_nav_menus' => false,
				'capabilities' => array('manage_terms'),
				'show_ui' => true
			));

			register_taxonomy("portfolio_categories", array($this->slug), array(
				"hierarchical" => true,
				"labels" => $this->getTaxonomyLabels(
					__('Portfolio Category', 'mad_app_textdomain'),
					__('Portfolio Categories', 'mad_app_textdomain')
				),
				"singular_label" => __("category", 'mad_app_textdomain'),
				"show_tagcloud" => true,
				'query_var' => true,
				'rewrite' => true,
				'show_in_nav_menus' => false,
				'capabilities' => array('manage_terms'),
				'show_ui' => true
			));

			register_post_type($this->slug, $args);

			add_filter("manage_". $this->slug ."_posts_columns", array(&$this, "show_edit_columns"));
			add_action("manage_". $this->slug ."_posts_custom_column", array(&$this, "show_edit_columns_content"));
		}

		public function fullwidthSlider() {
			$images = rwmb_meta('mad_portfolio_images', 'type=file');
			$slideshow = rwmb_meta('mad_flex_slideshow');
			$slideshowSpeed = rwmb_meta('mad_flex_slideshow_speed');

			$portfolio_images = array();
			$params = array();

			foreach ($images as $info) {
				$portfolio_images[]= array(
					'id' => $info['ID'],
					'title' => $info['title'],
					'url' => $info['url']
				);
			}

			$params[] = array(
				'slideshow' => $slideshow,
				'slideshowSpeed' => $slideshowSpeed
			);

			$this->render_slider($portfolio_images, $params);
		}

		public function render_slider($portfolio_images, $params) {
			$data = array();
			$data['portfolio_images'] = $portfolio_images;
			$data['params'] = $params;

			echo $this->output_html('render-slider', $data);
		}

		public function show_edit_columns_content($column) {
			global $post;
			switch ($column) {
				case "thumb column-comments":
					if (has_post_thumbnail($post->ID)) {
						echo get_the_post_thumbnail($post->ID, array(60, 60));
					}
					break;
				case "portfolio_entries":
					echo get_the_term_list($post->ID, 'portfolio_categories', '', ', ','');
					break;
				case "masonry_size":
					$output = "";
					switch(get_post_meta($post->ID, 'mad_masonry_size', true)) {
						case 'masonry-big':
							$output .= __('Big (440x345)', 'mad_app_textdomain');
							break;
						case 'masonry-medium':
							$output .= __('Medium (340x150)', 'mad_app_textdomain');
							break;
						case 'masonry-small':
							$output .= __('Small (245x150)', 'mad_app_textdomain');
							break;
						case 'masonry-long':
							$output .= __('Long (245x345)', 'mad_app_textdomain');
							break;
					}
					echo $output;
					break;
				case "skills":
					echo get_the_term_list($post->ID, 'skills', '', ', ','');
					break;
			}
		}

		public function show_edit_columns($columns) {
			$newcolumns = array(
				"cb" => "<input type=\"checkbox\" />",
				"thumb column-comments" => "Image",
				"title" => "Title",
				"portfolio_entries" => "Categories",
				"masonry_size" => "Masonry Size",
				"skills" => "Skills"
			);

			$columns = array_merge($newcolumns, $columns);
			return $columns;
		}

	}
}
