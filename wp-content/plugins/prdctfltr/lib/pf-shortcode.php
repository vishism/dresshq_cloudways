<?php

	/*
	 * Product Filter Shortcodes
	 */
	class WC_Prdctfltr_Shortcodes {

		public static function init() {
			$class = __CLASS__;
			new $class;
		}

		function __construct() {

			add_shortcode( 'prdctfltr_sc_products', __CLASS__ . '::prdctfltr_sc_products' );
			add_shortcode( 'prdctfltr_sc_get_filter', __CLASS__ . '::prdctfltr_sc_get_filter' );

			add_action( 'woocommerce_before_subcategory', __CLASS__. '::add_category_support', 10, 1 );

			add_action( 'wp_ajax_nopriv_prdctfltr_respond', __CLASS__ . '::prdctfltr_respond' );
			add_action( 'wp_ajax_prdctfltr_respond', __CLASS__ . '::prdctfltr_respond' );

		}

		public static function add_category_support( $category ) {

			echo '<span class="prdctfltr_cat_support" style="display:none!important;" data-slug="' . $category->slug . '"></span>';

		}

		public static function get_categories() {

			global $wp_query;

			$defaults = array(
				'before'        => '',
				'after'         => '',
				'force_display' => false
			);

			$args = array();

			$args = wp_parse_args( $args, $defaults );

			extract( $args );

			$term = get_queried_object();
			$parent_id = empty( $term->term_id ) ? 0 : $term->term_id;
			if ( $parent_id == 0 && isset( $_GET['product_cat'] ) ) {
				$term = get_term_by( 'slug', $_GET['product_cat'], 'product_cat' );
				$parent_id = $term->term_id;
			}

/*			if ( is_product_category() ) {
				$display_type = get_woocommerce_term_meta( $term->term_id, 'display_type', true );

				switch ( $display_type ) {
					case 'products' :
						return;
					break;
					case '' :
						if ( get_option( 'woocommerce_category_archive_display' ) == '' ) {
							return;
						}
					break;
				}
			}*/

			$product_categories = get_categories( apply_filters( 'woocommerce_product_subcategories_args', array(
				'parent'       => $parent_id,
				'menu_order'   => 'ASC',
				'hide_empty'   => 0,
				'hierarchical' => 1,
				'taxonomy'     => 'product_cat',
				'pad_counts'   => 1
			) ) );

/*			if ( ! apply_filters( 'woocommerce_product_subcategories_hide_empty', false ) ) {
				$product_categories = wp_list_filter( $product_categories, array( 'count' => 0 ), 'NOT' );
			}*/

			if ( $product_categories ) {
				echo $before;

				foreach ( $product_categories as $category ) {
					wc_get_template( 'content-product_cat.php', array(
						'category' => $category
					) );
				}

/*				if ( is_product_category() ) {
					$display_type = get_woocommerce_term_meta( $term->term_id, 'display_type', true );

					switch ( $display_type ) {
						case 'subcategories' :
							$wp_query->post_count    = 0;
							$wp_query->max_num_pages = 0;
						break;
						case '' :
							if ( get_option( 'woocommerce_category_archive_display' ) == 'subcategories' ) {
								$wp_query->post_count    = 0;
								$wp_query->max_num_pages = 0;
							}
						break;
					}
				}

				if ( is_shop() && get_option( 'woocommerce_shop_page_display' ) == 'subcategories' ) {
					$wp_query->post_count    = 0;
					$wp_query->max_num_pages = 0;
				}*/

				echo $after;

				return true;
			}

		}

		/*
		 * [prdctfltr_sc_products]
		 */
		public static function prdctfltr_sc_products( $atts, $content = null ) {
			extract( shortcode_atts( array(
				'preset' => '',
				'rows' => 4,
				'columns' => 4,
				'cat_columns' => 4,
				'ajax' => 'no',
				'pagination' => 'yes',
				'use_filter' => 'yes',
				'no_products' => 'no',
				'show_categories' => 'no',
				'show_products' => 'yes',
				'min_price' => '',
				'max_price' => '',
				'orderby' => '',
				'order' => '',
				'product_cat'=> '',
				'product_tag'=> '',
				'product_characteristics'=> '',
				'product_attributes'=> '',
				'sale_products' => '',
				'instock_products' => '',
				'http_query' => '',
				'disable_overrides' => 'yes',
				'action' => '',
				'bot_margin' => 36,
				'class' => '',
				'shortcode_id' => ''
			), $atts ) );


			$args = array();

			global $paged;
			$paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;

			if ( $no_products == 'no' ) {
				$args = $args + array (
					'prdctfltr' => 'active'
				);
			}
			else {
				$use_filter = 'no';
				$pagination = 'no';
				$orderby = 'rand';
			}

			global $prdctfltr_global;

			if ( $action !== '' ) {
				$prdctfltr_global['action'] = $action;
			}
			else {
				$prdctfltr_global['action'] = '';
			}
			if ( $preset !== '' ) {
				$prdctfltr_global['preset'] = $preset;
			}
			else {
				$prdctfltr_global['preset'] = '';
			}

			if ( $disable_overrides !== '' ) {
				$prdctfltr_global['disable_overrides'] = $disable_overrides;
			}
			else {
				$prdctfltr_global['disable_overrides'] = '';
			}

			$args = $args + array (
				'post_type' 			=> 'product',
				'post_status' 			=> 'publish',
				'posts_per_page' 		=> $columns*$rows,
				'paged' 				=> $paged,
				'wc_query' 				=> 'product_query',
				'meta_query' 			=> array(
					array(
						'key' 			=> '_visibility',
						'value' 		=> array('catalog', 'visible'),
						'compare' 		=> 'IN'
					)
				)
			);

			if ( $orderby !== '' ) {
				$args['orderby'] = $orderby;
			}
			if ( $order !== '' ) {
				$args['order'] = $order;
			}
			if ( $min_price !== '' ) {
				$args['min_price'] = $min_price;
			}
			if ( $max_price !== '' ) {
				$args['max_price'] = $max_price;
			}
			if ( $product_cat !== '' ) {
				$args['product_cat'] = $product_cat;
			}
			if ( $product_tag !== '' ) {
				$args['product_tag'] = $product_tag;
			}
			if ( $product_characteristics !== '' ) {
				$args['product_characteristics'] = $product_characteristics;
			}
			if ( $product_attributes !== '' ) {
				$args['product_attributes'] = $product_attributes;
			}
			if ( $instock_products !== '' ) {
				$args['instock_products'] = $instock_products;
			}
			if ( $sale_products !== '' ) {
				$args['sale_products'] = $sale_products;
			}
			if ( $http_query !== '' ) {
				$args['http_query'] = $http_query;
			}

			if ( $ajax == 'yes' ) {

				$ajax_params =  array(
					( $preset !== '' ? $preset : 'false' ),
					( $columns !== '' ? $columns : 'false' ),
					( $rows !== '' ? $rows : 'false' ),
					( $pagination !== '' ? $pagination : 'false' ),
					( $no_products !== '' ? $no_products : 'false' ),
					( $show_products !== '' ? $show_products : 'false' ),
					( $use_filter !== '' ? $use_filter : 'false' ),
					( $action !== '' ? $action : 'false' ),
					( $bot_margin !== '' ? $bot_margin : 'false' ),
					( $class !== '' ? $class : 'false' ),
					( $shortcode_id !== '' ? $shortcode_id : 'false' ),
					( $disable_overrides !== '' ? $disable_overrides : 'false' ),
					( $show_categories !== '' ? $show_categories : 'false' ),
					( $cat_columns !== '' ? $cat_columns : 'false' )
				);
				$pf_params = implode( '|', $ajax_params );

				$add_ajax = ' data-query="' . http_build_query( $args ) . '" data-page="' . $paged . '" data-shortcode="' . $pf_params . '"';

				$prdctfltr_global['sc_ajax'] = true;

			}

			$prdctfltr_global['sc_query'] = $args;

			$bot_margin = (int)$bot_margin;
			$margin = " style='margin-bottom:".$bot_margin."px'";

			$out = '';

			global $woocommerce, $woocommerce_loop, $wp_query, $wp_the_query;
			
			$woocommerce_loop['columns'] = apply_filters( 'loop_shop_columns', 4 );

			$products = new WP_Query( $args );

			$wp_query_old = $wp_query;
			$wp_the_query_old = $wp_the_query;

			$wp_query = $products;
			$wp_the_query = $products;

			ob_start();

			if ( $use_filter == 'yes' ) {
				include( WC_Prdctfltr::$dir . 'woocommerce/loop/product-filter.php' );
			}

			if ( $products->have_posts() ) :

				if ( $show_products == 'yes' ) {

					woocommerce_product_loop_start();

					if ( $show_categories == 'yes' ) {
						if ( isset( $atts['cat_columns'] ) ) {
							$woocommerce_loop['columns'] = $cat_columns;
						}
						self::get_categories();
					}

					while ( $products->have_posts() ) : $products->the_post();

						if ( isset( $atts['columns'] ) ) {
							$woocommerce_loop['columns'] = $columns;
						}

						wc_get_template_part( 'content', 'product' );

					endwhile;

					woocommerce_product_loop_end();

					}
					else {
						$pagination = 'no';
					}

			else :
				include( WC_Prdctfltr::$dir . 'woocommerce/loop/product-filter-no-products-found.php' );
			endif;

			$shortcode = ob_get_clean();

			$out .= '<div' . ( $shortcode_id != '' ? ' id="'.$shortcode_id.'"' : '' ) . ' class="prdctfltr_sc_products woocommerce'.($ajax=='yes'? ' prdctfltr_ajax' : '' ).'' . ( $class != '' ? ' '.$class.'' : '' ) . '"'.$margin.($ajax=='yes' ? $add_ajax : '' ).'>';
			$out .= do_shortcode($shortcode);

			if ( $pagination == 'yes' ) {

				ob_start();

				$wp_query->max_num_pages = $products->max_num_pages;

				add_filter( 'woocommerce_pagination_args', 'WC_Prdctfltr::prdctfltr_pagination_filter', 999, 1 );
				wc_get_template( 'loop/pagination.php' );
				remove_filter( 'woocommerce_pagination_args', 'WC_Prdctfltr::prdctfltr_pagination_filter' );

				$pagination = ob_get_clean();

				$out .= $pagination;
			}

			$out .= '</div>';

			$wp_query = $wp_query_old;
			$wp_the_query = $wp_the_query_old;

			wp_reset_postdata();
			wp_reset_query();

			return $out;

		}

		/**
		 * Shortcode AJAX Respond
		 */
		public static function prdctfltr_respond() {

			global $prdctfltr_global;

			$shortcode_params = explode('|', $_POST['pf_shortcode']);

			$preset = ( $shortcode_params[0] !== 'false' ? $shortcode_params[0] : '' );
			$columns = ( $shortcode_params[1] !== 'false' ? $shortcode_params[1] : 4 );
			$rows = ( $shortcode_params[2] !== 'false' ? $shortcode_params[2] : 4 );
			$pagination = ( $shortcode_params[3] !== 'false' ? $shortcode_params[3] : '' );
			$no_products = ( $shortcode_params[4] !== 'false' ? $shortcode_params[4] : '' );
			$show_products = ( $shortcode_params[5] !== 'false' ? $shortcode_params[5] : '' );
			$use_filter = ( $shortcode_params[6] !== 'false' ? $shortcode_params[6] : '' );
			$action = ( $shortcode_params[7] !== 'false' ? $shortcode_params[7] : '' );
			$bot_margin = ( $shortcode_params[8] !== 'false' ? $shortcode_params[8] : '' );
			$class = ( $shortcode_params[9] !== 'false' ? $shortcode_params[9] : '' );
			$shortcode_id = ( $shortcode_params[10] !== 'false' ? $shortcode_params[10] : '' );
			$disable_overrides = ( $shortcode_params[11] !== 'false' ? $shortcode_params[11] : '' );
			$show_categories = ( $shortcode_params[12] !== 'false' ? $shortcode_params[12] : '' );
			$cat_columns = ( $shortcode_params[13] !== 'false' ? $shortcode_params[13] : '' );

			$res_paged = ( isset( $_POST['pf_paged'] ) ? $_POST['pf_paged'] : $_POST['pf_page'] );

			parse_str( $_POST['pf_query'], $qargs );

			$qargs = array_merge( $qargs, array (
				'post_type' => 'product',
				'post_status' => 'publish'
			) );

			$ajax_query = http_build_query( $qargs );

			$current_page = WC_Prdctfltr::prdctfltr_get_between( $ajax_query, 'paged=', '&' );
			$page = $res_paged;

			$args = str_replace( 'paged=' . $current_page . '&', 'paged=' . $page . '&', $ajax_query );

			if ( $no_products == 'yes' ) {
				$use_filter = 'no';
				$pagination = 'no';
				$orderby = 'rand';
			}

			$add_ajax = ' data-query="' . $args . '" data-page="' . $res_paged . '" data-shortcode="' . $_POST['pf_shortcode'] . '"';

			$bot_margin = (int)$bot_margin;
			$margin = " style='margin-bottom:" . $bot_margin . "px'";

			if ( isset($_POST['pf_filters']) ) {
				$curr_filters = $_POST['pf_filters'];
			}
			else {
				$curr_filters = array();
			}

			$filter_args = '';
			foreach ( $curr_filters as $k => $v ) {

				if ( strpos($v, ',') ) {
					$new_v = str_replace(',', '%2C', $v);
				}
				else if ( strpos($v, '+') ) {
					$new_v = str_replace('+', '%2B', $v);
				}
				else {
					$new_v = $v;
				}

				$filter_args .= '&' . $k . '=' . $new_v;
			}

			$prdctfltr_global['ajax_query'] = $args;

			$args = $args . $filter_args . '&prdctfltr=active';

			$prdctfltr_global['ajax_paged'] = $res_paged;
			$prdctfltr_global['active_filters'] = $curr_filters;

			if ( $action !== '' ) {
				$prdctfltr_global['action'] = $action;
			}
			if ( $preset !== '' ) {
				$prdctfltr_global['preset'] = $preset;
			}
			if ( $disable_overrides !== '' ) {
				$prdctfltr_global['disable_overrides'] = $disable_overrides;
			}

			$out = '';

			global $woocommerce, $woocommerce_loop, $wp_the_query, $wp_query;

			$woocommerce_loop['columns'] = apply_filters( 'loop_shop_columns', $columns );

			$prdctfltr_global['ajax'] = true;
			$prdctfltr_global['sc_ajax'] = $_POST['pf_mode'] == 'no' ? 'no' : null;

			$products = new WP_Query( $args );

			$products->is_search = false;

			$wp_query = $products;
			$wp_the_query = $products;

			ob_start();

			if ( $use_filter == 'yes' ) {
				include( WC_Prdctfltr::$dir . 'woocommerce/loop/product-filter.php' );
			}

			if ( $products->have_posts() ) :

				if ( $show_products == 'yes' ) {

					woocommerce_product_loop_start();

					if ( isset( $prdctfltr_global['categories_active'] ) && $prdctfltr_global['categories_active'] === true ) {

						if ( $show_categories == 'archive' ) {
							if ( isset( $cat_columns ) ) {
								$woocommerce_loop['columns'] = $cat_columns;
							}
							woocommerce_product_subcategories();
						}
						else if ( $show_categories == 'yes' ) {
							if ( isset( $cat_columns ) ) {
								$woocommerce_loop['columns'] = $cat_columns;
							}
							self::get_categories();
						}

					}

					while ( $products->have_posts() ) : $products->the_post();

						if ( isset( $columns ) ) {
							$woocommerce_loop['columns'] = $columns;
						}

						wc_get_template_part( 'content', 'product' );

					endwhile;

					woocommerce_product_loop_end();

					}
					else {
						$pagination = 'no';
					}

			else :
				include( WC_Prdctfltr::$dir . 'woocommerce/loop/product-filter-no-products-found.php' );
			endif;

			$prdctfltr_global['ajax'] = null;

			$shortcode = str_replace( 'type-product', 'product type-product', ob_get_clean() );

			$out .= '<div' . ( $shortcode_id != '' ? ' id="'.$shortcode_id.'"' : '' ) . ' class="prdctfltr_sc_products woocommerce prdctfltr_ajax' . ( $class != '' ? ' '.$class.'' : '' ) . '"'.$margin.$add_ajax.'>';
			$out .= do_shortcode($shortcode);

			if ( $pagination == 'yes' ) {
				$wp_query = $products;
				ob_start();

				add_filter( 'woocommerce_pagination_args', 'WC_Prdctfltr::prdctfltr_pagination_filter', 999, 1 );
				wc_get_template( 'loop/pagination.php' );
				remove_filter( 'woocommerce_pagination_args', 'WC_Prdctfltr::prdctfltr_pagination_filter' );

				$pagination = ob_get_clean();

				$out .= $pagination;

			}

			if ( $_POST['pf_widget'] == 'yes' ) {

				if ( isset($_POST['pf_widget_title']) ) {
					$curr_title = explode('%%%', $_POST['pf_widget_title']);
				}

				ob_start();

				the_widget('prdctfltr', 'preset=' . $_POST['pf_preset'] . '&template=' . $_POST['pf_template'], array('before_title'=>stripslashes($curr_title[0]),'after_title'=>stripslashes($curr_title[1])) );

				$out .= ob_get_clean();

			}

			$out .= '</div>';

			die($out);
			exit;
		}


		/*
		 * [prdctfltr_sc_get_filter]
		 */
		public static function prdctfltr_sc_get_filter( $atts, $content = null ) {
			return include( WC_Prdctfltr::$dir . 'woocommerce/loop/orderby.php' );
		}

	}

	add_action( 'init', array( 'WC_Prdctfltr_Shortcodes', 'init' ), 999 );

?>