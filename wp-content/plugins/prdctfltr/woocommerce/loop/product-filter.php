<?php

	/**
	 * Product Filter Template
	 */

	if ( ! defined( 'ABSPATH' ) ) exit;

	if ( WC_Prdctfltr::prdctfltr_check_appearance() === false ) {
		return;
	}

	global $wp, $prdctfltr_global;

	$prdctfltr_global['active'] = 'true';

	if ( isset( $prdctfltr_global['active_filters'] ) ) {
		foreach( $prdctfltr_global['active_filters'] as $k => $v ) {
			$_GET[$k] = $v;
		}
	}

	if ( isset( $prdctfltr_global['sc_query'] ) ) {
		foreach( $prdctfltr_global['sc_query'] as $k => $v ) {
			$_GET[$k] = $v;
		}
	}

	$curr_options = WC_Prdctfltr::prdctfltr_get_settings();

	if ( !isset($prdctfltr_global['widget_style']) ) {
		if ( isset($curr_options['wc_settings_prdctfltr_style_mode']) ) {
			if ( !in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array( 'pf_select', 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right' ) ) ) {
				$curr_mod = $curr_options['wc_settings_prdctfltr_style_mode'];
			}
			else {
				$curr_mod = 'pf_mod_multirow';
			}
		}
		else {
			$curr_mod = 'pf_mod_multirow';
		}
		$curr_widget_add = '';
	}
	else {
		$curr_options['wc_settings_prdctfltr_style_preset'] = $prdctfltr_global['widget_style'];
		$curr_mod = 'pf_mod_multirow';
		$curr_widget_add = ' data-preset="' . $prdctfltr_global['widget_style'].'" data-template="' . $prdctfltr_global['preset'] . '"';
	}

	if ( in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array('pf_arrow','pf_arrow_inline') ) !== false ) {
		$curr_options['wc_settings_prdctfltr_always_visible'] = 'no';
		$curr_options['wc_settings_prdctfltr_disable_bar'] = 'no';
	}

	$curr_elements = ( $curr_options['wc_settings_prdctfltr_active_filters'] !== NULL ? $curr_options['wc_settings_prdctfltr_active_filters'] : array() );

	$pf_order_default = array(
		''              => apply_filters( 'prdctfltr_none_text', __( 'None', 'prdctfltr' ) ),
		'menu_order'    => __( 'Default', 'prdctfltr' ),
		'comment_count' => __( 'Review Count', 'prdctfltr' ),
		'popularity'    => __( 'Popularity', 'prdctfltr' ),
		'rating'        => __( 'Average rating', 'prdctfltr' ),
		'date'          => __( 'Newness', 'prdctfltr' ),
		'price'         => __( 'Price: low to high', 'prdctfltr' ),
		'price-desc'    => __( 'Price: high to low', 'prdctfltr' ),
		'rand'          => __( 'Random Products', 'prdctfltr' ),
		'title'         => __( 'Product Name', 'prdctfltr' )
	);

	if ( !empty( $curr_options['wc_settings_prdctfltr_include_orderby'] ) ) {
		foreach ( $pf_order_default as $u => $i ) {
			if ( !in_array( $u, $curr_options['wc_settings_prdctfltr_include_orderby'] ) ) {
				unset( $pf_order_default[$u] );
			}
		}
		$pf_order_default = array_merge( array( '' => apply_filters( 'prdctfltr_none_text', __( 'None', 'prdctfltr' ) ) ), $pf_order_default );
	}

	$catalog_orderby = apply_filters( 'prdctfltr_catalog_orderby', $pf_order_default );

	$catalog_instock = apply_filters( 'prdctfltr_catalog_instock', array(
		'both'    => __( 'All Products', 'prdctfltr' ),
		'in'  => __( 'In Stock', 'prdctfltr' ),
		'out' => __( 'Out Of Stock', 'prdctfltr' )
	) );

	global $wp_the_query;

	if ( $wp_the_query->is_tax( get_object_taxonomies( 'product' ) ) || $wp_the_query->is_post_type_archive( 'product' ) || $wp_the_query->is_page( WC_Prdctfltr::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) ) ) {

		$paged = max( 1, $wp_the_query->get( 'paged' ) );
		$per_page = $wp_the_query->get( 'posts_per_page' );
		$total = $wp_the_query->found_posts;
		$first = ( $per_page * $paged ) - $per_page + 1;
		$last = min( $total, $wp_the_query->get( 'posts_per_page' ) * $paged );

	}
	else {
		if ( !isset( $products ) ) {
			if ( isset( $prdctfltr_global['sc_query'] ) ) {
				$r_args = $prdctfltr_global['sc_query'];
			}
			else {
				$r_args = array();

				$r_args = $r_args + array(
					'prdctfltr'				=> 'active',
					'post_type'				=> 'product',
					'post_status' 			=> 'publish',
					'posts_per_page' 		=> get_option('posts_per_page'),
					'meta_query' 			=> array(
						array(
							'key' 			=> '_visibility',
							'value' 		=> array('catalog', 'visible'),
							'compare' 		=> 'IN'
						)
					)
				);
			}

			$res_products = new WP_Query( $r_args );
		}
		else { 
			$res_products = $products;
		}

		$paged = ( isset($prdctfltr_global['ajax_paged']) ? $prdctfltr_global['ajax_paged'] : max( 1, $res_products->get( 'paged' ) ) );
		$per_page = $res_products->get( 'posts_per_page' );
		$total = $res_products->found_posts;
		$first = ( $per_page * $paged ) - $per_page + 1;
		$last = min( $total, $res_products->get( 'posts_per_page' ) * $paged );

	}

	$pf_query = ( isset($res_products) ? $res_products : $wp_the_query );

	$curr_styles = WC_Prdctfltr::prdctfltr_get_styles( $curr_options, $curr_mod );

	$curr_maxheight = ( $curr_options['wc_settings_prdctfltr_limit_max_height'] == 'yes' ? ' style="max-height:' . $curr_options['wc_settings_prdctfltr_max_height'] . 'px;"' : '' );

	if ( get_option( 'wc_settings_prdctfltr_use_ajax', 'no' ) == 'yes' ) {
		$curr_ajax_params = array(
			'false',
			$pf_columns = floatval( WC_Prdctfltr::$settings['wc_settings_prdctfltr_ajax_columns'] ) > 0 ? floatval( WC_Prdctfltr::$settings['wc_settings_prdctfltr_ajax_columns'] ) : apply_filters( 'loop_shop_columns', 4 ),
			$pf_rows = floatval( WC_Prdctfltr::$settings['wc_settings_prdctfltr_ajax_rows'] ) > 0 ? floatval( WC_Prdctfltr::$settings['wc_settings_prdctfltr_ajax_rows'] ) : 4,
			'yes',
			'false',
			'yes',
			( isset( $prdctfltr_global['widget_search'] ) ? 'no' : 'yes' ),
			'false',
			'false',
			'false',
			'false',
			'false',
			'archive',
			'false'
		);
		$pf_params = implode( '|', $curr_ajax_params );

/*		if ( $pf_query->query_vars['paged'] == 0 ) {
			$pf_query->set( 'paged', 1 );
		}*/

		$curr_query = ( isset( $prdctfltr_global['ajax_query'] ) ? $prdctfltr_global['ajax_query'] : http_build_query( $pf_query->query_vars ) );

		$curr_add_query = ' data-query="' . $curr_query . '" data-page="' . $paged . '" data-shortcode="' . $pf_params .'"';
	}

	if ( isset( $_GET ) ) {

		$supress = array( 'post_type', 'widget_search' );
		$allowed = array( 'orderby', 'min_price', 'max_price', 'instock_products', 'sale_products', 'products_per_page' );

		$rng_terms = array();
		$pf_activated = array();

		foreach( $_GET as $k => $v ){
			if ( !in_array( $k, $supress+$allowed ) ) {
				if ( substr($k, 0, 4) == 'rng_' && $v !== '' ) {
					if ( substr($k, 0, 8) == 'rng_min_' ) {
						$rng_terms[str_replace('rng_min_', '', $k)]['min'] = $v;
					}
					else {
						$rng_terms[str_replace('rng_max_', '', $k)]['max'] = $v;
					}
				}
			}
			if ( !in_array( $k, $supress ) ) {
				if ( in_array( $k, $allowed ) ) {
					if ( $k =='orderby' && $v == 'date' ) {
						continue;
					}
					$pf_activated = $pf_activated + array( $k => $v );
				}
				else if ( taxonomy_exists( $k ) ) {
					$pf_activated = $pf_activated + array( $k => $v );
				}
			}
		}

		$pf_activated = $pf_activated + $rng_terms;
		
		if ( empty( $pf_activated ) ) {
			$pf_activated = null;
		}
	}

	if ( !isset( $pf_activated ) && get_query_var( 'taxonomy' ) !== '' ) {
		$pf_activated[get_query_var( 'taxonomy' )] = get_query_var( 'term' );
	}
	if ( !isset( $pf_activated['product_cat'] ) && get_query_var( 'product_cat' ) !== '' ) {
		$pf_activated['product_cat'] = get_query_var( 'product_cat' );
	}

	$pf_activated = isset( $pf_activated ) ? $pf_activated : array();

	do_action( 'prdctfltr_filter_before', $curr_options, $pf_activated );

	?>
	<div <?php echo ( !isset( $prdctfltr_global['sc_ajax'] ) ? 'id="prdctfltr_woocommerce" ' : '' ); ?>class="prdctfltr_wc prdctfltr_woocommerce woocommerce <?php echo isset( $prdctfltr_global['widget_search'] ) ? 'prdctfltr_wc_widget' : 'prdctfltr_wc_regular' ; ?> <?php echo preg_replace( '/\s+/', ' ', implode( $curr_styles, ' ' ) ); ?>"<?php echo $curr_widget_add; ?><?php echo ( isset( $curr_add_query ) ? $curr_add_query : '' ); ?> data-loader="<?php echo ( $curr_options['wc_settings_prdctfltr_loader'] !== '' ? $curr_options['wc_settings_prdctfltr_loader'] : 'oval' ); ?>"<?php echo ( WC_Prdctfltr::prdctfltr_wpml_language() !== false ? ' data-lang="' . ICL_LANGUAGE_CODE . '"' : '' ); ?> data-nonce="<?php echo $nonce = wp_create_nonce( 'prdctfltr_analytics' ); ?>">
	<?php

		if ( !isset($prdctfltr_global['widget_style']) && $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' ) {

			$prdctfltr_icon = $curr_options['wc_settings_prdctfltr_icon'];
		?>
			<span class="prdctfltr_filter_title">
				<a <?php ( isset( $prdctfltr_global['active'] ) ? '' : 'id="prdctfltr_woocommerce_filter" ' ); ?>class="prdctfltr_woocommerce_filter<?php echo ' pf_ajax_' . ( $curr_options['wc_settings_prdctfltr_loader'] !== '' ? $curr_options['wc_settings_prdctfltr_loader'] : 'oval' ); ?>" href="#"><i class="<?php echo ( $prdctfltr_icon == '' ? 'prdctfltr-bars' : $prdctfltr_icon ); ?>"></i></a>
		<?php

			if ( $curr_options['wc_settings_prdctfltr_title'] !== '' ) {
				echo $curr_options['wc_settings_prdctfltr_title'];
			}
			else {
				_e( 'Filter products', 'prdctfltr' );
			}

			if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' ) {

				if ( isset( $pf_activated ) ) {
					foreach( $pf_activated as $k => $v ) {
						if ( substr( $k, 0, 10 ) == 'rng_order_' || substr( $k, 0, 12 ) == 'rng_orderby_' ) {
							continue;
						}

						switch( $k ) {
							case 'products_per_page' :
								if ( isset( $_GET['products_per_page'] ) ) {
									echo ' / <span>' . $_GET['products_per_page'] . ' ' . __( 'Products per page', 'prdctfltr' ) . '</span> <a href="#" class="prdctfltr_title_remove" data-key="products_per_page"><i class="prdctfltr-delete"></i></a>';
								}
							break;
							case 'sale_products' :
								if ( isset( $_GET['sale_products'] ) ) {
									echo ' / <span>' . __( 'Products on sale', 'prdctfltr' ) . '</span> <a href="#" class="prdctfltr_title_remove" data-key="sale_products"><i class="prdctfltr-delete"></i></a>';
								}
							break;
							case 'instock_products' :
								if ( isset( $_GET['instock_products'] ) ) {
									echo ' / <span>' . $catalog_instock[$_GET['instock_products']] . '</span> <a href="#" class="prdctfltr_title_remove" data-key="instock_products"><i class="prdctfltr-delete"></i></a>';
								}
							break;
							case 'orderby' :
								if ( isset( $_GET['orderby'] ) ) {
									if ( !array_key_exists($_GET['orderby'], $catalog_orderby ) ) continue;
									echo ' / <span>' . $catalog_orderby[$_GET['orderby']] . '</span> <a href="#" class="prdctfltr_title_remove" data-key="orderby"><i class="prdctfltr-delete"></i></a>';
								}
							break;
							case 'min_price' :
								if ( isset( $_GET['min_price'] ) && $_GET['min_price'] !== '' ) {

									$min_price = wc_price( $_GET['min_price'] );

									if ( isset( $_GET['max_price'] ) && $_GET['max_price'] !== '' ) {
										$curr_max_price = $_GET['max_price'];
										$max_price = wc_price( $_GET['max_price'] );
									}
									else {
										$max_price = '+';
									}

									echo ' / <span>' . $min_price . ' - ' . $max_price . '</span> <a href="#" class="prdctfltr_title_remove" data-key="byprice"><i class="prdctfltr-delete"></i></a>';
								}
							break;
							case 'max_price' :
							break;
							case 'price' :
								if ( isset( $_GET['rng_min_price'] ) && $_GET['rng_min_price'] !== '' ) {

									$min_price = wc_price($_GET['rng_min_price']);

									if ( isset( $_GET['rng_max_price'] ) && $_GET['rng_max_price'] !== '' ) {
										$curr_max_price = $_GET['rng_max_price'];
										$max_price = wc_price($_GET['rng_max_price']);
									}
									else {
										$max_price = '+';
									}

									echo ' / <span>' . __('Price range', 'prdctfltr') . ' ' . $min_price . ' &rarr; ' . $max_price . '</span> <a href="#" class="prdctfltr_title_remove" data-key="byprice"><i class="prdctfltr-delete"></i></a>';
								}
							break;
							default :
								if ( $k == 'cat' || $k == 'tag' ) {
									$k = 'product_' . $k;
								}

								$curr_filter = isset( $_GET[$k] ) ? $_GET[$k] : isset( $pf_query->query_vars[$k] ) ? $pf_query->query_vars[$k] : false;

								if ( $curr_filter === false ) {
									continue;
								}

								if ( strpos( $curr_filter, ',' ) ) {
									$curr_selected = explode( ',', $curr_filter );
								}
								else if ( strpos( $curr_filter, '+' ) ) {
									$curr_selected = explode( '+', $curr_filter );
								}
								else {
									$curr_selected = array( $curr_filter );
								}

								if ( substr( $k, 0, 3 ) == 'pa_' && $v !== '' ) {
									echo ' / <span>' . wc_attribute_label( $k ) . ' - ';
								}
								else {
									echo ' / <span>';
								}
								$i=0;
								foreach( $curr_selected as $selected ) {
									$curr_term = get_term_by( 'slug', $selected, $k );
									echo ( $i !== 0 ? ', ' : '' ) . $curr_term->name;
									$i++;
								}
								echo '</span> <a href="#" class="prdctfltr_title_remove" data-key="' . $k . '"><i class="prdctfltr-delete"></i></a>';
							break;
						}
					}
				}

				if ( $curr_options['wc_settings_prdctfltr_noproducts'] !=='' && $total == 0 ) {
					
				} elseif ( $total == 0 ) {
					echo ' / ' . __( 'No products found!', 'prdctfltr' );
				} elseif ( $total == 1 ) {
					echo ' / ' . __( 'Showing the single result', 'prdctfltr' );
				} elseif ( $total <= $per_page || -1 == $per_page ) {
					echo ' / ' . __( 'Showing all', 'prdctfltr') . ' ' . $total . ' ' . __( 'results', 'prdctfltr' );
				} else {
					echo ' / ' . __( 'Showing', 'prdctfltr' ) . ' ' . $first . ' - ' . $last . ' ' . __( 'of', 'prdctfltr' ) . ' ' . $total . ' ' . __( 'results', 'prdctfltr' );
				}
			}
		?>
			</span>
		<?php
		}

		if ( in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right' ) ) ) {
			$curr_columns = 1;
		}
		else {
			$curr_mix_count = ( count($curr_elements) );
			$curr_columns = ( $curr_mix_count < $curr_options['wc_settings_prdctfltr_max_columns'] ? $curr_mix_count : $curr_options['wc_settings_prdctfltr_max_columns'] );
		}

		$curr_columns_class = ' prdctfltr_columns_' . $curr_columns;

		if ( $curr_options['wc_settings_prdctfltr_adoptive'] == 'yes' || ( defined('DOING_AJAX') && DOING_AJAX ) ) {

			if ( $pf_query->have_posts() ) {

				$output_terms = array();

				$request = $pf_query->request;

				if ( !empty( $request ) ) {

					$t_pos = strpos( $request, 'LIMIT' );
					if ( $t_pos !== false ) {
						$t_str = substr( $request, 0, $t_pos );
					}
					else {
						$t_str = $request;
					}

					$t_str .= ' LIMIT 0,10000000';

					global $wpdb;
					$pf_products = $wpdb->get_results( $t_str );

					$curr_in = array();
					foreach ( $pf_products as $p ) {
						$curr_in[] = $p->ID;
					}

					if ( !empty( $curr_in ) ) {
						$curr_ins = implode(',',$curr_in);
						$curr_tax = implode(',',$curr_elements);

						$pf_product_terms = $wpdb->get_results( $wpdb->prepare( '
							SELECT slug, taxonomy FROM %1$s
							INNER JOIN %2$s ON (%1$s.ID = %2$s.object_id)
							INNER JOIN %3$s ON (%2$s.term_taxonomy_id = %3$s.term_taxonomy_id )
							INNER JOIN %4$s ON (%3$s.term_id = %4$s.term_id )
							WHERE %1$s.ID IN (' . $curr_ins . ')
							ORDER BY %4$s.name ASC
						', $wpdb->posts, $wpdb->term_relationships, $wpdb->term_taxonomy, $wpdb->terms ) );

						foreach ( $pf_product_terms as $p ) {
							if ( !isset($output_terms[$p->taxonomy]) ) {
								$output_terms[$p->taxonomy] = array();
							}
							if ( !array_key_exists( $p->slug, $output_terms[$p->taxonomy] ) ) {
								$output_terms[$p->taxonomy][$p->slug] = 1;
							}
							else if ( array_key_exists( $p->slug, $output_terms[$p->taxonomy] ) ) {
								$output_terms[$p->taxonomy][$p->slug] = $output_terms[$p->taxonomy][$p->slug] + 1;
							}
						}
					}

				}

			}

		}

		$pf_structure = get_option( 'permalink_structure' );
		$curr_cat_query = get_option( 'wc_settings_prdctfltr_force_categories', 'no' );

		if ( is_product_taxonomy() || is_product() ) {

			if ( is_product() ) {
				$curr_action = get_permalink( WC_Prdctfltr::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) );
			}
			else if ( $curr_cat_query == 'no' ) {
				$curr_action = get_permalink( WC_Prdctfltr::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) );
			}
			else {
				if ( $pf_structure == '' ) {
					$curr_action = esc_url( remove_query_arg( array( 'page', 'paged' ), esc_url( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) ) ) );
				} else {
					$curr_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );
				}
			}
		}
		else if ( !isset($prdctfltr_global['action']) || $prdctfltr_global['action'] == '' ) {
			if ( $pf_structure == '' ) {
				$curr_action = esc_url( remove_query_arg( array( 'page', 'paged' ), esc_url( add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) ) ) );
			} else {
				$curr_action = preg_replace( '%\/page/[0-9]+%', '', home_url( $wp->request ) );
			}
		}
		else {
			$curr_action = $prdctfltr_global['action'];
		}

	?>
	<form action="<?php echo $curr_action; ?>" class="prdctfltr_woocommerce_ordering" method="get">

		<?php do_action( 'prdctfltr_filter_form_before', $curr_options, $pf_activated ); ?>

		<div class="prdctfltr_filter_wrapper<?php echo $curr_columns_class; ?>" data-columns="<?php echo $curr_columns; ?>">
			<div class="prdctfltr_filter_inner">
			<?php

				$q = 0;
				$n = 0;
				$p = 0;

				$active_filters = array();

				$pf_adv_check = array(
					'pfa_title' => '',
					'pfa_taxonomy' => '',
					'pfa_include' => array(),
					'pfa_orderby' => 'name',
					'pfa_order' => 'ASC',
					'pfa_multi' => 'no',
					'pfa_relation' => 'IN',
					'pfa_adoptive' => 'no',
					'pfa_none' => 'no',
					'pfa_hierarchy' => 'no',
					'pfa_hierarchy_mode' => 'no',
					'pfa_mode' => 'showall',
					'pfa_style' => 'pf_attr_text',
					'pfa_limit' => 0
				);

				$pf_rng_check = array(
					'pfr_title' => '',
					'pfr_taxonomy' => '',
					'pfr_include' => array(),
					'pfr_orderby' => 'name',
					'pfr_order' => 'ASC',
					'pfr_style' => 'no',
					'pfr_grid' => 'no',
					'pfr_custom' => ''
				);

				foreach ( $curr_elements as $k => $v ) :

					if ( !isset( $prdctfltr_global['widget_search'] ) && $q == $curr_columns && ( $curr_options['wc_settings_prdctfltr_style_mode'] == 'pf_mod_multirow' || $curr_options['wc_settings_prdctfltr_style_preset'] == 'pf_select' ) ) {
						$q = 0;
						echo '<div class="prdctfltr_clear"></div>';
					}

					switch ( $v ) :

					case 'per_page' :

						if ( $curr_options['wc_settings_prdctfltr_perpage_term_customization'] !== '' ) {
							$language = WC_Prdctfltr::prdctfltr_wpml_language();

							if ( isset( $language ) && $language !== false ) {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_perpage_term_customization'] . '_' . $language, '' );
								if ( $get_customization == '' ) {
									$get_customization = get_option( $curr_options['wc_settings_prdctfltr_perpage_term_customization'], '' );
								}
							}
							else {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_perpage_term_customization'], '' );
							}
							

							if ( $get_customization !== '' && isset( $get_customization['style'] ) ) {
								$ctcid = $curr_options['wc_settings_prdctfltr_perpage_term_customization'];
								$curr_term_customization = ' prdctfltr_terms_customized  prdctfltr_terms_customized_' . $get_customization['style'] . ' ' . $ctcid;

								$customization = $get_customization;
								if ( $customization['style'] == 'text' ) {
									WC_Prdctfltr::add_customized_terms_css( $ctcid, $customization );
								}
							}
						}
						else {
							$customization = array();
							$curr_term_customization = '';
						}

					?>
						<div class="prdctfltr_filter prdctfltr_per_page<?php echo $curr_term_customization; ?>" data-filter="pf_per_page">
							<input name="products_per_page" type="hidden"<?php echo ( isset($_GET['products_per_page'] ) ? ' value="'.$_GET['products_per_page'].'"' : '' );?>>
							<?php
								if ( isset( $prdctfltr_global['widget_style'] ) ) {
									$pf_before_title = $before_title . '<span class="prdctfltr_widget_title">';
									$pf_after_title = '</span>' . $after_title;
								}
								else {
									$pf_before_title = '<span class="prdctfltr_regular_title">';
									$pf_after_title = '</span>';
								}

								echo $pf_before_title;

								if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' && ( $curr_styles[5] == 'prdctfltr_disable_bar' || isset( $prdctfltr_global['widget_style'] ) ) && isset($_GET['products_per_page'] ) ) {
									echo '<a href="#" data-key="products_per_page"><i class="prdctfltr-delete"></i></a> <span>' . $_GET['products_per_page'] . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_perpage_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_perpage_title'];
								}
								else {
									_e( 'Products Per Page', 'prdctfltr' );
								}
							?>
							<i class="prdctfltr-down"></i>
							<?php echo $pf_after_title; ?>
							<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
							<?php

								$filter_customization = WC_Prdctfltr::get_filter_customization( 'per_page', $curr_options['wc_settings_prdctfltr_perpage_filter_customization'] );

								if ( !empty( $filter_customization ) && isset( $filter_customization['settings'] ) && is_array( $filter_customization['settings'] ) ) {

									foreach( $filter_customization['settings'] as $v ) {
										$curr_perpage[$v['value']] = $v['text'];
									}

								}
								else {

									$curr_perpage_set = $curr_options['wc_settings_prdctfltr_perpage_range'];
									$curr_perpage_limit = $curr_options['wc_settings_prdctfltr_perpage_range_limit'];

									$curr_perpage = array();

									for ($i = 1; $i <= $curr_perpage_limit; $i++) {

										$curr_perpage[$curr_perpage_set*$i] = $curr_perpage_set*$i . ' ' . ( $curr_options['wc_settings_prdctfltr_perpage_label'] == '' ? __( 'Products', 'prdctfltr' ) : $curr_options['wc_settings_prdctfltr_perpage_label'] );

									}

								}

								foreach ( $curr_perpage as $id => $name ) {

									$checked = ( isset($_GET['products_per_page']) && $_GET['products_per_page'] == $id ? ' checked' : ' ' );

									if ( $curr_options['wc_settings_prdctfltr_perpage_term_customization'] !== '' ) {
										$curr_insert = WC_Prdctfltr::get_customized_term( $id, $name, false, $customization, $checked );
									}
									else {
										$curr_insert = sprintf( '<input type="checkbox" value="%1$s"%2$s/><span>%3$s</span>', esc_attr( $id ), $checked, $name );
									}

									printf( '<label%1$s>%2$s</label>', ( isset($_GET['products_per_page']) && $_GET['products_per_page'] == $id ? ' class="prdctfltr_active prdctfltr_ft_' . sanitize_title( $id ) .'"' : ' class="prdctfltr_ft_' . sanitize_title( $id ) .'"' ), $curr_insert );
								}
							?>
							</div>
						</div>

					<?php break;

					case 'instock' :

						if ( $curr_options['wc_settings_prdctfltr_instock_term_customization'] !== '' ) {
							$language = WC_Prdctfltr::prdctfltr_wpml_language();

							if ( isset( $language ) && $language !== false ) {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_instock_term_customization'] . '_' . $language, '' );
								if ( $get_customization == '' ) {
									$get_customization = get_option( $curr_options['wc_settings_prdctfltr_instock_term_customization'], '' );
								}
							}
							else {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_instock_term_customization'], '' );
							}
							

							if ( $get_customization !== '' && isset( $get_customization['style'] ) ) {
								$ctcid = $curr_options['wc_settings_prdctfltr_instock_term_customization'];
								$curr_term_customization = ' prdctfltr_terms_customized  prdctfltr_terms_customized_' . $get_customization['style'] . ' ' . $ctcid;

								$customization = $get_customization;
								if ( $customization['style'] == 'text' ) {
									WC_Prdctfltr::add_customized_terms_css( $ctcid, $customization );
								}
							}
						}
						else {
							$customization = array();
							$curr_term_customization = '';
						}
					?>
						<div class="prdctfltr_filter prdctfltr_instock<?php echo $curr_term_customization; ?>" data-filter="pf_instock">
							<input name="instock_products" type="hidden"<?php echo ( isset($_GET['instock_products'] ) ? ' value="'.$_GET['instock_products'].'"' : '' );?>>
							<?php
								if ( isset( $prdctfltr_global['widget_style'] ) ) {
									$pf_before_title = $before_title . '<span class="prdctfltr_widget_title">';
									$pf_after_title = '</span>' . $after_title;
								}
								else {
									$pf_before_title = '<span class="prdctfltr_regular_title">';
									$pf_after_title = '</span>';
								}

								echo $pf_before_title;

								if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' && ( $curr_styles[5] == 'prdctfltr_disable_bar' || isset( $prdctfltr_global['widget_style'] ) ) && isset($_GET['instock_products'] ) ) {
									echo '<a href="#" data-key="instock_products"><i class="prdctfltr-delete"></i></a> <span>'.$catalog_instock[$_GET['instock_products']] . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_instock_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_instock_title'];
								}
								else {
									_e( 'Product Availability', 'prdctfltr' );
								}
							?>
							<i class="prdctfltr-down"></i>
							<?php echo $pf_after_title; ?>
							<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
							<?php

								foreach ( $catalog_instock as $id => $name ) {

									$checked = ( isset($_GET['instock_products']) && $_GET['instock_products'] == $id ? ' checked' : ' ' );

									if ( $curr_options['wc_settings_prdctfltr_instock_term_customization'] !== '' ) {
										$curr_insert = WC_Prdctfltr::get_customized_term( $id, $name, false, $customization, $checked );
									}
									else {
										$curr_insert = sprintf( '<input type="checkbox" value="%1$s"%2$s/><span>%3$s</span>', esc_attr( $id ), $checked, $name );
									}

									printf( '<label%1$s>%2$s</label>', ( isset($_GET['instock_products']) && $_GET['instock_products'] == $id ? ' class="prdctfltr_active prdctfltr_ft_' . sanitize_title( $id ) .'"' : ' class="prdctfltr_ft_' . sanitize_title( $id ) .'"' ), $curr_insert );

								}
							?>
							</div>
						</div>

					<?php break;

					case 'sort' :

						if ( $curr_options['wc_settings_prdctfltr_orderby_term_customization'] !== '' ) {
							$language = WC_Prdctfltr::prdctfltr_wpml_language();

							if ( isset( $language ) && $language !== false ) {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_orderby_term_customization'] . '_' . $language, '' );
								if ( $get_customization == '' ) {
									$get_customization = get_option( $curr_options['wc_settings_prdctfltr_orderby_term_customization'], '' );
								}
							}
							else {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_orderby_term_customization'], '' );
							}
							

							if ( $get_customization !== '' && isset( $get_customization['style'] ) ) {
								$ctcid = $curr_options['wc_settings_prdctfltr_orderby_term_customization'];
								$curr_term_customization = ' prdctfltr_terms_customized  prdctfltr_terms_customized_' . $get_customization['style'] . ' ' . $ctcid;

								$customization = $get_customization;
								if ( $customization['style'] == 'text' ) {
									WC_Prdctfltr::add_customized_terms_css( $ctcid, $customization );
								}
							}
						}
						else {
							$customization = array();
							$curr_term_customization = '';
						}
					?>
						<div class="prdctfltr_filter prdctfltr_orderby<?php echo $curr_term_customization; ?>" data-filter="orderby">
							<input name="orderby" type="hidden"<?php echo ( isset($_GET['orderby'] ) ? ' value="'.$_GET['orderby'].'"' : '' );?>>
							<?php
								if ( isset($prdctfltr_global['widget_style']) ) {
									$pf_before_title = $before_title . '<span class="prdctfltr_widget_title">';
									$pf_after_title = '</span>' . $after_title;
								}
								else {
									$pf_before_title = '<span class="prdctfltr_regular_title">';
									$pf_after_title = '</span>';
								}

								echo $pf_before_title;

								if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' && ( $curr_styles[5] == 'prdctfltr_disable_bar' || isset( $prdctfltr_global['widget_style'] ) ) && isset( $_GET['orderby'] ) && isset( $catalog_orderby[$_GET['orderby']] ) ) {
									echo '<a href="#" data-key="orderby"><i class="prdctfltr-delete"></i></a> <span>' . $catalog_orderby[$_GET['orderby']] . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_orderby_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_orderby_title'];
								}
								else {
									_e('Sort by', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
							<?php echo $pf_after_title; ?>
							<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
							<?php
								if ( get_option( 'woocommerce_enable_review_rating' ) === 'no' ) {
									unset( $catalog_orderby['rating'] );
								}
								if ( $curr_options['wc_settings_prdctfltr_orderby_none'] == 'yes' ) {
									unset( $catalog_orderby[''] );
								}

								foreach ( $catalog_orderby as $id => $name ) {

									$checked = ( isset($_GET['orderby']) && $_GET['orderby'] == $id ? ' checked' : ' ' );

									if ( $curr_options['wc_settings_prdctfltr_orderby_term_customization'] !== '' ) {
										$curr_insert = WC_Prdctfltr::get_customized_term( $id, $name, false, $customization, $checked );
									}
									else {
										$curr_insert = sprintf( '<input type="checkbox" value="%1$s"%2$s/><span>%3$s</span>', esc_attr( $id ), $checked, $name );
									}

									printf( '<label%1$s>%2$s</label>', ( isset($_GET['orderby']) && $_GET['orderby'] == $id ? ' class="prdctfltr_active prdctfltr_ft_' . sanitize_title( $id ) .'"' : ' class="prdctfltr_ft_' . sanitize_title( $id ) .'"' ), $curr_insert );

								}
							?>
							</div>
						</div>

					<?php break;

					case 'price' :

						if ( $curr_options['wc_settings_prdctfltr_price_term_customization'] !== '' ) {
							$language = WC_Prdctfltr::prdctfltr_wpml_language();

							if ( isset( $language ) && $language !== false ) {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_price_term_customization'] . '_' . $language, '' );
								if ( $get_customization == '' ) {
									$get_customization = get_option( $curr_options['wc_settings_prdctfltr_price_term_customization'], '' );
								}
							}
							else {
								$get_customization = get_option( $curr_options['wc_settings_prdctfltr_price_term_customization'], '' );
							}
							

							if ( $get_customization !== '' && isset( $get_customization['style'] ) ) {
								$ctcid = $curr_options['wc_settings_prdctfltr_price_term_customization'];
								$curr_term_customization = ' prdctfltr_terms_customized  prdctfltr_terms_customized_' . $get_customization['style'] . ' ' . $ctcid;

								$customization = $get_customization;
								if ( $customization['style'] == 'text' ) {
									WC_Prdctfltr::add_customized_terms_css( $ctcid, $customization );
								}
							}
						}
						else {
							$customization = array();
							$curr_term_customization = '';
						}

					?>
						<div class="prdctfltr_filter prdctfltr_byprice<?php echo $curr_term_customization; ?>"  data-filter="pf_byprice">
							<input name="min_price" type="hidden"<?php echo ( isset( $_GET['min_price'] ) ? ' value="' . $_GET['min_price'] . '"' : '' );?>>
							<input name="max_price" type="hidden"<?php echo ( isset( $_GET['max_price'] ) ? ' value="' . $_GET['max_price'] . '"' : '' );?>>
							<?php
								if ( isset($prdctfltr_global['widget_style']) ) {
									$pf_before_title = $before_title . '<span class="prdctfltr_widget_title">';
									$pf_after_title = '</span>' . $after_title;
								}
								else {
									$pf_before_title = '<span class="prdctfltr_regular_title">';
									$pf_after_title = '</span>';
								}

								echo $pf_before_title;

								if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' && ( $curr_styles[5] == 'prdctfltr_disable_bar' || isset( $prdctfltr_global['widget_style'] ) ) && isset($_GET['min_price']) && $_GET['min_price'] !== '' ) {
									$min_price = wc_price( $_GET['min_price'] );
									if ( isset( $_GET['max_price'] ) && $_GET['max_price'] !== '' ) {
										$curr_max_price = $_GET['max_price'];
										$max_price = wc_price( $_GET['max_price'] );
									}
									else {
										$max_price = ' +';
									}
									echo '<a href="#" data-key="byprice"><i class="prdctfltr-delete"></i></a> <span>' . $min_price . ' - ' . $max_price . '</span> / ';
								}

								if ( $curr_options['wc_settings_prdctfltr_price_title'] != '' ) {
									echo $curr_options['wc_settings_prdctfltr_price_title'];
								}
								else {
									_e('Price range', 'prdctfltr');
								}
							?>
							<i class="prdctfltr-down"></i>
							<?php

							echo $pf_after_title;

							$filter_customization = WC_Prdctfltr::get_filter_customization( 'price', $curr_options['wc_settings_prdctfltr_price_filter_customization'] );

							$catalog_ready_price = array();
							$curr_price = ( isset($_GET['min_price']) ? $_GET['min_price'].'-'.( isset($_GET['max_price']) ? $_GET['max_price'] : '' ) : '' );

							if ( !empty( $filter_customization ) && isset( $filter_customization['settings'] ) && is_array( $filter_customization['settings'] ) ) {

								if ( $curr_options['wc_settings_prdctfltr_price_none'] == 'no' ) {
									$catalog_ready_price = array(
										'-' => apply_filters( 'prdctfltr_none_text', __( 'None', 'prdctfltr' ) )
									);
								}

								foreach( $filter_customization['settings'] as $k => $v ) {
									$catalog_ready_price[$k] = $v;
								}

							}
							else {

								$curr_prices = array();
								$curr_prices_currency = array();

								$curr_price_set = $curr_options['wc_settings_prdctfltr_price_range'];
								$curr_price_add = $curr_options['wc_settings_prdctfltr_price_range_add'];
								$curr_price_limit = $curr_options['wc_settings_prdctfltr_price_range_limit'];

								global $wpdb;
								$min = floor( $wpdb->get_var(
									$wpdb->prepare('
										SELECT min(meta_value + 0)
										FROM %1$s
										LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
										WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
										AND meta_value != ""
										', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price' )
									)
								);

								if ( $curr_options['wc_settings_prdctfltr_price_none'] == 'no' ) {
									$catalog_ready_price = array(
										'-' => apply_filters( 'prdctfltr_none_text', __( 'None', 'prdctfltr' ) )
									);
								}

								for ($i = 0; $i < $curr_price_limit; $i++) {

									if ( $i == 0 ) {
										$min_price = $min;
										$max_price = $curr_price_set;
									}
									else {
										$min_price = $curr_price_set+($i-1)*$curr_price_add;
										$max_price = $curr_price_set+$i*$curr_price_add;
									}

									$curr_prices[$i] = $min_price . '-' . ( ($i+1) == $curr_price_limit ? '' : $max_price );

									$curr_prices_currency[$i] = wc_price( $min_price ) . ( $i+1 == $curr_price_limit ? '+' : ' - ' . wc_price( $max_price ) );

									$catalog_ready_price = $catalog_ready_price + array(
										$curr_prices[$i] => $curr_prices_currency[$i]
									);

								}

							}

							$catalog_price = apply_filters( 'prdctfltr_catalog_price', $catalog_ready_price );

						?>
						<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
							<?php
								foreach ( $catalog_price as $id => $name ) {
									$checked = ( $curr_price == $id ? ' checked' : ' ' );

									if ( $curr_options['wc_settings_prdctfltr_price_term_customization'] !== '' ) {
										$curr_insert = WC_Prdctfltr::get_customized_term( $id, $name, false, $customization, $checked );
									}
									else {
										$curr_insert = sprintf( '<input type="checkbox" value="%1$s"2$s/><span>%3$s</span>', esc_attr( $id ), $checked, $name );
									}

									printf( '<label%1$s>%2$s</label>', ( $curr_price == $id ? ' class="prdctfltr_active prdctfltr_ft_' . sanitize_title( $id ) .'"' : ' class="prdctfltr_ft_' . sanitize_title( $id ) .'"' ), $curr_insert );
								}
							?>
							</div>
						</div>

					<?php break;


					case 'range' :

						foreach ( $pf_rng_check as $k => $v ) {
							if ( !isset($curr_options['wc_settings_prdctfltr_range_filters'][$k][$p]) ) {
								$curr_options['wc_settings_prdctfltr_range_filters'][$k][$p] = $v;
							}
						}

						$attr = $curr_options['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][$p];

			?>
						<div class="prdctfltr_filter prdctfltr_range prdctfltr_<?php echo $attr; ?> <?php echo 'pf_rngstyle_' . $curr_options['wc_settings_prdctfltr_range_filters']['pfr_style'][$p]; ?>">
							<input name="rng_min_<?php echo $attr; ?>" type="hidden"<?php echo ( isset( $_GET['rng_min_' . $attr] ) ? ' value="'.$_GET['rng_min_' . $attr].'"' : '' );?>>
							<input name="rng_max_<?php echo $attr; ?>" type="hidden"<?php echo ( isset( $_GET['rng_max_' . $attr] ) ? ' value="'.$_GET['rng_max_' . $attr].'"' : '' );?>>
							<input name="rng_orderby_<?php echo $attr; ?>" type="hidden" value="<?php echo $curr_options['wc_settings_prdctfltr_range_filters']['pfr_orderby'][$p]; ?>">
							<input name="rng_order_<?php echo $attr; ?>" type="hidden" value="<?php echo $curr_options['wc_settings_prdctfltr_range_filters']['pfr_order'][$p]; ?>">
							<?php
								if ( isset($prdctfltr_global['widget_style']) ) {
									$pf_before_title = $before_title . '<span class="prdctfltr_widget_title">';
									$pf_after_title = '</span>' . $after_title;
								}
								else {
									$pf_before_title = '<span class="prdctfltr_regular_title">';
									$pf_after_title = '</span>';
								}

								echo $pf_before_title;

								if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' && ( $curr_styles[5] == 'prdctfltr_disable_bar' || isset( $prdctfltr_global['widget_style'] ) ) ) {
									if ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) {
										echo '<a href="#" data-key="rng_' . $attr . '"><i class="prdctfltr-delete"></i></a> <span>';
										if ( $attr == 'price' ) {
											echo wc_price($_GET['rng_min_' . $attr]) . ' - ' . wc_price($_GET['rng_max_' . $attr]);
										}
										else {
											$pf_f_term = get_term_by('slug', $_GET['rng_min_' . $attr], $attr);
											$pf_s_term = get_term_by('slug', $_GET['rng_max_' . $attr], $attr);
											echo $pf_f_term->name . ' - ' . $pf_s_term->name;
										}
										echo '</span> / ';
									}
								}

								if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_title'][$p] !== '' ) {
									echo $curr_options['wc_settings_prdctfltr_range_filters']['pfr_title'][$p];
								}
								else {
									if ( !in_array($curr_options['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][$p], array('price') ) ) {
										echo wc_attribute_label( $attr );
									}
									else {
										_e( 'Price range', 'prdctfltr' );
									}

								}
							?>
							<i class="prdctfltr-down"></i>
							<?php echo $pf_after_title; ?>
							<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
							<?php
								$pf_add_settings = '';
								$curr_include = $curr_options['wc_settings_prdctfltr_range_filters']['pfr_include'][$p];

								$curr_include = WC_Prdctfltr::prdctfltr_wpml_translate_terms( $curr_include, $attr );

								if ( !in_array($curr_options['wc_settings_prdctfltr_range_filters']['pfr_taxonomy'][$p], array('price') ) ) {

									if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_orderby'][$p] == 'number' ) {
										$attr_args = array(
											'hide_empty' => 1,
											'orderby' => 'slug'
										);
										$curr_attributes = WC_Prdctfltr::prdctfltr_get_terms( $attr, $attr_args );
										$pf_sort_args = array(
											'order' => ( isset( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_order'][$p] ) ? $curr_options['wc_settings_prdctfltr_range_filters']['pfr_order'][$p] : 'ASC' )
										);
										$curr_attributes = WC_Prdctfltr::prdctfltr_sort_terms_naturally( $curr_attributes, $pf_sort_args );
									}
									else {
										$attr_args = array(
											'hide_empty' => 1,
											'orderby' => ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_orderby'][$p] !== '' ? $curr_options['wc_settings_prdctfltr_range_filters']['pfr_orderby'][$p] : 'name' ),
											'order' => ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_order'][$p] !== '' ? $curr_options['wc_settings_prdctfltr_range_filters']['pfr_order'][$p] : 'ASC' )
										);
										$curr_attributes = WC_Prdctfltr::prdctfltr_get_terms( $attr, $attr_args );
									}

									
									$pf_add_settings .= 'values:[';

									$c=0;

									foreach ( $curr_attributes as $attribute ) {
										if ( !empty($curr_include) && !in_array($attribute->slug, $curr_include) ) {
											continue;
										}
										if ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) {
											if ( $_GET['rng_min_' . $attr] == $attribute->slug ) {
												$pf_curr_min = $c;
											}
											if ( $_GET['rng_max_' . $attr] == $attribute->slug ) {
												$pf_curr_max = $c;
											}
										}
										$pf_add_settings .= ( $c !== 0 ? ', ' : '' ) . '"<span class=\'pf_range_val\'>' . $attribute->slug . '</span>' . $attribute->name . '"';
										$c++;
									}

									$pf_add_settings .= '], decorate_both: false,values_separator: " &rarr; ", min_interval: 1, ';

									if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_custom'][$p] !== '' ) {
										$pf_add_settings .= $curr_options['wc_settings_prdctfltr_range_filters']['pfr_custom'][$p] . ', ';
									}

								}
								else {
									global $wpdb;
									$pf_curr_min = floor( $wpdb->get_var(
										$wpdb->prepare('
											SELECT min(meta_value + 0)
											FROM %1$s
											LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
											WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
											AND meta_value != ""
											', $wpdb->posts, $wpdb->postmeta, '_price', '_min_variation_price' )
										)
									);
									$pf_curr_max = ceil( $wpdb->get_var(
										$wpdb->prepare('
											SELECT max(meta_value + 0)
											FROM %1$s
											LEFT JOIN %2$s ON %1$s.ID = %2$s.post_id
											WHERE ( meta_key = \'%3$s\' OR meta_key = \'%4$s\' )
											AND meta_value != ""
										', $wpdb->posts, $wpdb->postmeta, '_price', '_max_variation_price' )
									) );

									$pf_add_settings .= 'min:' . $pf_curr_min . ', max:' . $pf_curr_max . ', min_interval: 1, ';

									if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_custom'][$p] !== '' ) {
										$pf_add_settings .= $curr_options['wc_settings_prdctfltr_range_filters']['pfr_custom'][$p] . ', ';
									}

									$currency_pos = get_option( 'woocommerce_currency_pos' );
									$currency = get_woocommerce_currency_symbol();

									switch ( $currency_pos ) {
										case 'left' :
											$pf_add_settings .= 'prefix: "' . $currency . '", ';
										break;
										case 'right' :
											$pf_add_settings .= 'postfix: "' . $currency . '", ';
										break;
										case 'left_space' :
											$pf_add_settings .= 'prefix: "' . $currency . ' ", ';
										break;
										case 'right_space' :
											$pf_add_settings .= 'postfix: " ' . $currency . '", ';
										break;
									}
									if ( ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) !== false ) {
										$pf_curr_min = ( isset($_GET['rng_min_' . $attr]) ? $_GET['rng_min_' . $attr] : $_GET['min_' . $attr] );
										$pf_curr_max = ( isset($_GET['rng_max_' . $attr]) ? $_GET['rng_max_' . $attr] : $_GET['max_' . $attr] );
									}
								}

								if ( $curr_options['wc_settings_prdctfltr_range_filters']['pfr_grid'][$p] == 'yes' ) {
									$pf_add_settings .= 'grid: true, ';
								}

								if ( ( isset($_GET['rng_min_' . $attr]) && isset($_GET['rng_max_' . $attr]) ) !== false ) {
									$pf_add_settings .= 'from:'.$pf_curr_min.',to:'.$pf_curr_max.', ';
								}

								$curr_rng_id = 'prdctfltr_rng_' . uniqid() . '_' . $p;

								$pf_add_settings .= '
									onFinish: function (data) {
										if ( data.min == data.from && data.max == data.to ) {
											$(\'#' . $curr_rng_id . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_min_"]:first\').val( \'\' );
											$(\'#' . $curr_rng_id . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_max_"]:first\').val( \'\' ).trigger(\'change\');
										}
										else {
											$(\'#' . $curr_rng_id . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_min_"]:first\').val( ( data.from_value == null ? data.from : $(data.from_value).text() ) );
											$(\'#' . $curr_rng_id . '\').closest(\'.prdctfltr_filter\').find(\'input[name^="rng_max_"]:first\').val( ( data.to_value == null ? data.to : $(data.to_value).text() ) ).trigger(\'change\');
										}
									}';

								printf( '<input id="%1$s" />', $curr_rng_id );
	?>
								<script type="text/javascript">
	(function($){
	"use strict";
		$('#<?php echo $curr_rng_id; ?>').ionRangeSlider({
			type: 'double',
			<?php echo $pf_add_settings; ?>
		});
	})(jQuery);
								</script>
	<?php
							?>
							</div>
						</div>
						<?php

						$p++;
					break;

					default :

						$curr_fo = array();

						if ( $v == 'cat' ) {

							$curr_fo['filter'] = 'product_cat';
							$mod = 'regular';
						}
						else if ( $v == 'tag' ) {

							$curr_fo['filter'] = 'product_tag';
							$mod = 'regular';
						}
						else if ( $v == 'char' ) {

							$curr_fo['filter'] = 'characteristics';
							$mod = 'regular';
						}
						else if ( substr( $v, 0, 3) == 'pa_' ) {

							$curr_fo['filter'] = $v;
							$mod = 'attribute';
						}
						else if ( $v == 'advanced' ) {

							$curr_fo['filter'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_taxonomy'][$n];
							$mod = 'advanced';
						}

						if ( in_array( $mod, array( 'regular', 'attribute' ) ) ) {
							$curr_fo['settings']['title'] = $curr_options['wc_settings_prdctfltr_' . $v . '_title'];

							if ( $mod == 'attribute' ) {
								$curr_fo['settings']['include'] = $curr_options['wc_settings_prdctfltr_include_' . $v];
							}
							else {
								$curr_fo['settings']['include'] = $curr_options['wc_settings_prdctfltr_include_' . $v . 's'];
							}

							$checked_customization = isset( $curr_options['wc_settings_prdctfltr_' . $v .'_term_customization'] ) && !empty( $curr_options['wc_settings_prdctfltr_' . $v .'_term_customization'] ) ? $curr_options['wc_settings_prdctfltr_' . $v .'_term_customization'] : '' ;

							$curr_fo['settings']['orderby'] = $curr_options['wc_settings_prdctfltr_' . $v . '_orderby'];
							$curr_fo['settings']['order'] = $curr_options['wc_settings_prdctfltr_' . $v . '_order'];
							$curr_fo['settings']['limit'] = $curr_options['wc_settings_prdctfltr_' . $v . '_limit'];
							$curr_fo['settings']['multi'] = $curr_options['wc_settings_prdctfltr_' . $v . '_multi'];
							$curr_fo['settings']['relation'] = $curr_options['wc_settings_prdctfltr_' . $v . '_relation'];
							$curr_fo['settings']['adoptive'] = $curr_options['wc_settings_prdctfltr_' . ( $v == 'char' ? $v . 's' : $v ) . '_adoptive'];
							$curr_fo['settings']['none'] = $curr_options['wc_settings_prdctfltr_' . $v . '_none'];
							$curr_fo['settings']['customization'] = $checked_customization;

							if ( $mod == 'attribute' || $v == 'cat' ) {
								$curr_fo['settings']['hierarchy'] = $curr_options['wc_settings_prdctfltr_' . $v . '_hierarchy'];
								$curr_fo['settings']['hierarchy_mode'] = $curr_options['wc_settings_prdctfltr_' . $v . '_hierarchy_mode'];
								$curr_fo['settings']['mode'] = $curr_options['wc_settings_prdctfltr_' . $v . '_mode'];
							}
							if ( $mod == 'attribute' ) {
								$curr_fo['settings']['style'] = $curr_options['wc_settings_prdctfltr_' . $v];
							}
						}
						else {


							foreach ( $pf_adv_check as $ck => $cv ) {
								if ( !isset($curr_options['wc_settings_prdctfltr_advanced_filters'][$ck][$n]) ) {
									$curr_options['wc_settings_prdctfltr_advanced_filters'][$ck][$n] = $cv;
								}
							}

							$checked_customization = isset( $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'][$n] ) && !empty( $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'][$n] ) ? $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_term_customization'][$n] : '' ;

							$curr_fo['settings']['title'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_title'][$n];
							$curr_fo['settings']['include'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_include'][$n];
							$curr_fo['settings']['orderby'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_orderby'][$n];
							$curr_fo['settings']['order'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_order'][$n];
							$curr_fo['settings']['limit'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_limit'][$n];
							$curr_fo['settings']['multi'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_multiselect'][$n];
							$curr_fo['settings']['relation'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_relation'][$n];
							$curr_fo['settings']['adoptive'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_adoptive'][$n];
							$curr_fo['settings']['none'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_none'][$n];

							$curr_fo['settings']['hierarchy'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy'][$n];
							$curr_fo['settings']['hierarchy_mode'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_hierarchy_mode'][$n];
							$curr_fo['settings']['mode'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_mode'][$n];
							$curr_fo['settings']['style'] = $curr_options['wc_settings_prdctfltr_advanced_filters']['pfa_style'][$n];
							$curr_fo['settings']['customization'] = $checked_customization;

						}

						if ( $total !== 0 && $curr_fo['settings']['adoptive'] == 'yes' && $curr_options['wc_settings_prdctfltr_adoptive_style'] == 'pf_adptv_default' && ( isset( $output_terms ) && ( !isset( $output_terms[$curr_fo['filter']] ) || isset( $output_terms[$curr_fo['filter']] ) && empty( $output_terms[$curr_fo['filter']]) ) === true ) ) {
							continue;
						}

						$curr_limit = intval( $curr_fo['settings']['limit'] );
						if ( $curr_limit !== 0 ) {
							$catalog_categories = WC_Prdctfltr::prdctfltr_get_terms( $curr_fo['filter'], array('hide_empty' => 1, 'orderby' => 'count', 'order' => 'DESC', 'number' => $curr_limit ) );
						}
						else {

							if ( $curr_fo['settings']['orderby'] == 'number' ) {
								$curr_term_args = array(
									'hide_empty' => 1,
									'orderby' => 'slug'
								);
								$catalog_categories = WC_Prdctfltr::prdctfltr_get_terms( $curr_fo['filter'], $curr_term_args );
								$pf_sort_args = array(
									'order' => ( isset( $curr_fo['settings']['order'] ) ? $curr_fo['settings']['order'] : 'ASC' )
								);
								$catalog_categories = WC_Prdctfltr::prdctfltr_sort_terms_naturally( $catalog_categories, $pf_sort_args );
							}
							else {
								$curr_term_args = array(
									'hide_empty' => 1,
									'orderby' => ( $curr_fo['settings']['orderby'] !== '' ? $curr_fo['settings']['orderby'] : 'name' ),
									'order' => ( $curr_fo['settings']['order']!== '' ? $curr_fo['settings']['order'] : 'ASC' )
								);
								$catalog_categories = WC_Prdctfltr::prdctfltr_get_terms( $curr_fo['filter'], $curr_term_args );
							}

							if ( isset( $curr_fo['settings']['hierarchy'] ) && $curr_fo['settings']['hierarchy'] == 'yes' ) {
								$catalog_categories_sorted = array();
								WC_Prdctfltr::prdctfltr_sort_terms_hierarchicaly( $catalog_categories, $catalog_categories_sorted );
								$catalog_categories = $catalog_categories_sorted;
							}
						}

					if ( !empty( $catalog_categories ) && !is_wp_error( $catalog_categories ) ) {

						$curr_cat_selected = array();

						if ( isset( $pf_activated[$curr_fo['filter']] ) ) {
							$curr_cat = $pf_activated[$curr_fo['filter']];
							if ( is_string( $curr_cat ) ) {
								if ( strpos($curr_cat, ',') ) {
									$curr_cat_selected = explode( ',', $curr_cat);
								}
								else if ( strpos($curr_cat, '+') ) {
									$curr_cat_selected = explode( '+', $curr_cat);
								}
								else {
									$curr_cat_selected = array( $curr_cat );
								}
							}
						}

						if ( !empty( $curr_cat_selected ) ) {
							$curr_cat_selected = array_map( 'strtolower', $curr_cat_selected );
						}

						if ( isset($curr_fo['settings']['style'] ) ) {
							$curr_term_style = $curr_fo['settings']['style'];
						}
						else {
							$curr_term_style = 'pf_attr_text';
						}

						if ( $curr_fo['settings']['customization'] !== '' ) {
							$language = WC_Prdctfltr::prdctfltr_wpml_language();

							if ( isset( $language ) && $language !== false ) {
								$get_customization = get_option( $curr_fo['settings']['customization'] . '_' . $language, '' );
								if ( $get_customization == '' ) {
									$get_customization = get_option( $curr_fo['settings']['customization'], '' );
								}
							}
							else {
								$get_customization = get_option( $curr_fo['settings']['customization'], '' );
							}
							

							if ( $get_customization !== '' && isset( $get_customization['style'] ) ) {
								$ctcid = $curr_fo['settings']['customization'];
								$curr_term_customization = ' prdctfltr_terms_customized  prdctfltr_terms_customized_' . $get_customization['style'] . ' ' . $ctcid;

								$customization = $get_customization;
								if ( $customization['style'] == 'text' ) {
									WC_Prdctfltr::add_customized_terms_css( $ctcid, $customization );
								}
								$curr_term_style = '';
							}
						}
						else {
							$customization = array();
							$curr_term_customization = '';
						}

						if ( isset( $curr_fo['settings']['hierarchy'] ) && $curr_fo['settings']['hierarchy'] == 'yes' ) {
							$curr_term_style = 'pf_attr_text';
							if ( isset( $customization['style'] ) && $customization['style'] !== 'select' ) {
								$curr_fo['settings']['customization'] = '';
								$curr_term_customization = '';
								$customization = array();
							}
						}

						$curr_term_multi = $curr_fo['settings']['multi'] == 'yes' ? ' prdctfltr_multi' : ' prdctfltr_single';
						$curr_term_adoptive = $curr_fo['settings']['adoptive'] == 'yes' ? ' prdctfltr_adoptive' : '';
						$curr_term_relation = $curr_fo['settings']['relation'] == 'AND' ? ' prdctfltr_merge_terms' : '';
						$curr_term_expand = isset( $curr_fo['settings']['hierarchy_mode'] ) && $curr_fo['settings']['hierarchy_mode'] == 'yes' ? ' prdctfltr_expand_parents' : '';
						$curr_include = array_map( 'strtolower', $curr_fo['settings']['include'] );

						if ( !empty( $curr_include ) ) {
							$curr_include = array_map( 'strtolower', $curr_include );
						}
						else {
							foreach ( $catalog_categories as $term ) {
								$curr_include[] = strtolower( $term->slug );
							}
						}

						$curr_include = WC_Prdctfltr::prdctfltr_wpml_translate_terms( $curr_include, $curr_fo['filter'] );

					?>
						<div class="prdctfltr_filter prdctfltr_attributes prdctfltr_<?php echo $v; ?> <?php echo $curr_term_style; ?><?php echo $curr_term_multi; ?><?php echo $curr_term_customization; ?><?php echo $curr_term_adoptive; ?><?php echo $curr_term_relation; ?><?php echo $curr_term_expand; ?>" data-filter="<?php echo $curr_fo['filter']; ?>">
							<input name="<?php echo $curr_fo['filter']; ?>" type="hidden"<?php echo ( !empty( $curr_cat_selected ) ? isset( $pf_activated[$curr_fo['filter']] ) ? ' value="' . $pf_activated[$curr_fo['filter']] . '"' : '' : '' ); ?> />
							<?php
								if ( isset($prdctfltr_global['widget_style']) ) {
									$pf_before_title = $before_title . '<span class="prdctfltr_widget_title">';
									$pf_after_title = '</span>' . $after_title;
								}
								else {
									$pf_before_title = '<span class="prdctfltr_regular_title">';
									$pf_after_title = '</span>';
								}

								echo $pf_before_title;

								if ( $curr_options['wc_settings_prdctfltr_disable_showresults'] == 'no' && ( $curr_styles[5] == 'prdctfltr_disable_bar' || isset( $prdctfltr_global['widget_style'] ) ) ) {
									if ( !empty( $curr_cat_selected ) && array_intersect( $curr_cat_selected, $curr_include ) ) {
										echo '<a href="#" data-key="' . $curr_fo['filter'] . '"><i class="prdctfltr-delete"></i></a> <span>';
										$i=0;
										foreach( $curr_cat_selected as $selected ) {
											if ( in_array( $selected, $curr_include ) ) {
												$curr_term = get_term_by('slug', $selected, $curr_fo['filter']);
												echo ( $i !== 0 ? ', ' : '' ) . $curr_term->name;
												$i++;
											}
										}
										echo '</span> / ';
									}
								}

								if ( $curr_fo['settings']['title'] != '' ) {
									echo $curr_fo['settings']['title'];
								}
								else {
									if ( substr( $curr_fo['filter'], 0, 3 ) == 'pa_' ) {
										echo wc_attribute_label( $curr_fo['filter'] );
									}
									else {
										if ( $curr_fo['filter'] == 'product_cat' ) {
											_e( 'Categories', 'prdctfltr' );
										}
										else if ( $curr_fo['filter'] == 'product_tag') {
											_e( 'Tags', 'prdctfltr' );
										}
										else if ( $curr_fo['filter'] == 'characteristics' ) {
											_e( 'Characteristics', 'prdctfltr' );
										}
										else {
											$curr_term = get_taxonomy( $curr_fo['filter'] );
											echo $curr_term->name;
										}
									}
								}

							?>
							<i class="prdctfltr-down"></i>
							<?php echo $pf_after_title; ?>
							<div class="prdctfltr_checkboxes"<?php echo $curr_maxheight; ?>>
							<?php
								if ( $curr_fo['settings']['none'] == 'no' ) {
									if ( $curr_fo['settings']['customization'] == '' ) {
										switch ( $curr_term_style ) {
											case 'pf_attr_text':
												$curr_blank_element = __('None' , 'prdctfltr');
											break;
											case 'pf_attr_imgtext':
												$curr_blank_element = '<img src="' . WC_Prdctfltr::$url_path . '/lib/images/pf-transparent.gif" />';
												$curr_blank_element .= __('None' , 'prdctfltr');
											break;
											case 'pf_attr_img':
												$curr_blank_element = '<img src="' . WC_Prdctfltr::$url_path . '/lib/images/pf-transparent.gif" />';
												$curr_blank_element .= '<span class="prdctfltr_tooltip"><span>' . __('None' , 'prdctfltr') . '</span></span>';
											break;
											default :
												$curr_blank_element = __('None' , 'prdctfltr');
											break;
										}
									}
									else {
										$curr_blank_element = WC_Prdctfltr::get_customized_term( '', apply_filters( 'prdctfltr_none_text', __( 'None', 'prdctfltr' ) ), false, $customization );
									}

									printf('<label><input type="checkbox" value="" /><span>%1$s</span></label>', $curr_blank_element );
								}

								if ( isset( $curr_fo['settings']['mode'] ) && $curr_fo['settings']['mode'] == 'subcategories' ) {
									$curr_cat_ids_selected = array();
									foreach( $curr_cat_selected as $catalog_category ) {
										$catalog_category_term = get_term_by('slug', $catalog_category, $curr_fo['filter']);
										$curr_cat_ids_selected[] = $catalog_category_term->term_id;
									}
								}

								foreach ( $catalog_categories as $term ) {

									$decode_slug = $term->slug;

									if ( !empty($curr_include) && !in_array($decode_slug, $curr_include) ) {
										continue;
									}

									if ( isset($term->children) ) {
										$pf_children = $term->children;
									}
									else {
										$pf_children = array();
									}

									$curr_id_found = false;

									if ( isset( $curr_cat_ids_selected ) && !empty( $pf_children ) ) {
										foreach( $curr_cat_ids_selected as $curr_cat_id_selected ) {
											if ( term_is_ancestor_of( (int)$term->term_id, (int)$curr_cat_id_selected, $curr_fo['filter'] ) ) {
												$curr_id_found = true;
											}
										}
									}

									if ( isset( $curr_fo['settings']['mode'] ) && $curr_fo['settings']['mode'] == 'subcategories' && !empty( $curr_cat_selected ) &&  $curr_id_found == false && !in_array( $term->term_id, $curr_cat_ids_selected ) ) {
										continue;
									}

									if ( $curr_fo['settings']['customization'] == '' ) {

										$term_count = ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' || $term->count == '0' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$curr_fo['filter']]) && isset($output_terms[$curr_fo['filter']][$term->slug]) && $output_terms[$curr_fo['filter']][$term->slug] != $term->count ? $output_terms[$curr_fo['filter']][$term->slug] . '/' . $term->count : $term->count ) . '</span>' );

										switch ( $curr_term_style ) {
											case 'pf_attr_text':
												$curr_insert = $term->name . $term_count;
											break;
											case 'pf_attr_imgtext':
												$curr_img = wp_get_attachment_image( get_woocommerce_term_meta($term->term_id, $curr_fo['filter'] . '_thumbnail_id_photo', true), 'shop_thumbnail' );

												$curr_insert = ( $curr_img !== '' ? $curr_img : '<img src="' . WC_Prdctfltr::$url_path . '/lib/images/pf-placeholder.gif" />');
												$curr_insert .= $term->name . $term_count;
											break;
											case 'pf_attr_img':
												$curr_img = wp_get_attachment_image( get_woocommerce_term_meta($term->term_id, $curr_fo['filter'] . '_thumbnail_id_photo', true), 'shop_thumbnail' );

												$curr_insert = ( $curr_img !== '' ? $curr_img : '<img src="' . WC_Prdctfltr::$url_path . '/lib/images/pf-placeholder.gif" />');
												$curr_insert .= '<span class="prdctfltr_tooltip"><span>' . $term->name . $term_count . '</span></span>';
											break;
											default :
												$curr_insert = $term->name;
											break;
										}
									}
									else {

										$term_count = ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' || $term->count == '0' ? false : ( isset($output_terms[$curr_fo['filter']]) && isset($output_terms[$curr_fo['filter']][$term->slug]) && $output_terms[$curr_fo['filter']][$term->slug] != $term->count ? $output_terms[$curr_fo['filter']][$term->slug] . '/' . $term->count : $term->count ) );

										$curr_insert = WC_Prdctfltr::get_customized_term( $term->slug, $term->name, $term_count, $customization );
									}

									$pf_adoptive_class = '';

									if ( $curr_fo['settings']['adoptive'] == 'yes' && isset( $output_terms[$curr_fo['filter']] ) && !empty( $output_terms[$curr_fo['filter']] ) && !array_key_exists( $term->slug, $output_terms[$curr_fo['filter']] ) ) {
										$pf_adoptive_class = ' pf_adoptive_hide';
									}

									printf('<label class="%6$s%4$s%7$s%8$s"><input type="checkbox" value="%1$s"%3$s /><span>%2$s</span>%5$s</label>', $decode_slug, $curr_insert, ( in_array( $decode_slug, $curr_cat_selected ) ? ' checked' : '' ), ( in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_active' : '' ), ( !empty($pf_children) ? '<i class="prdctfltr-plus"></i>' : '' ), $pf_adoptive_class, ( !empty($pf_children) && in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_clicked' : '' ), ' prdctfltr_ft_' . sanitize_title( $term->slug ) );

									if ( isset( $curr_fo['settings']['hierarchy'] ) && $curr_fo['settings']['hierarchy'] == 'yes' && !empty( $pf_children ) ) {

										printf( '<div class="prdctfltr_sub" data-sub="%1$s">', $term->slug );

										foreach( $pf_children as $sub ) {

											$pf_adoptive_class = '';
											if ( $curr_fo['settings']['adoptive'] == 'yes' && isset($output_terms[$curr_fo['filter']]) && !empty($output_terms[$curr_fo['filter']]) && !array_key_exists($sub->slug, $output_terms[$curr_fo['filter']]) ) {
												$pf_adoptive_class = ' pf_adoptive_hide';
											}

											$decode_slug = $sub->slug;

											$curr_insert = $sub->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' || $sub->count == '0' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$curr_fo['filter']]) && isset($output_terms[$curr_fo['filter']][$sub->slug]) && $output_terms[$curr_fo['filter']][$sub->slug] != $sub->count ? $output_terms[$curr_fo['filter']][$sub->slug] . '/' . $sub->count : $sub->count ) . '</span>' );

											printf('<label class="%6$s%4$s%7$s%8$s"><input type="checkbox" value="%1$s"%3$s /><span>%2$s</span>%5$s</label>', $decode_slug, $curr_insert, ( in_array( $decode_slug, $curr_cat_selected ) ? ' checked' : '' ), ( in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_active' : '' ), ( !empty($sub->children) ? '<i class="prdctfltr-plus"></i>' : '' ), $pf_adoptive_class, ( !empty($sub->children) && in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_clicked' : '' ), ' prdctfltr_ft_' . sanitize_title( $sub->slug ) );

											if ( !empty($sub->children) ) {

												printf( '<div class="prdctfltr_sub" data-sub="%1$s">', $sub->slug );

												foreach( $sub->children as $subsub ) {

													$pf_adoptive_class = '';
													if ( $curr_fo['settings']['adoptive'] == 'yes' && isset($output_terms[$curr_fo['filter']]) && !empty($output_terms[$curr_fo['filter']]) && !array_key_exists($subsub->slug, $output_terms[$curr_fo['filter']]) ) {
														$pf_adoptive_class = ' pf_adoptive_hide';
													}

													$decode_slug = $subsub->slug;

													$curr_insert = $subsub->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' || $subsub->count == '0' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$curr_fo['filter']]) && isset($output_terms[$curr_fo['filter']][$subsub->slug]) && $output_terms[$curr_fo['filter']][$subsub->slug] != $subsub->count ? $output_terms[$curr_fo['filter']][$subsub->slug] . '/' . $subsub->count : $subsub->count ) . '</span>' );

													printf('<label class="%6$s%4$s%7$s%8$s"><input type="checkbox" value="%1$s" %3$s /><span>%2$s</span>%5$s</label>', $decode_slug, $curr_insert, ( in_array( $decode_slug, $curr_cat_selected ) ? 'checked' : '' ), ( in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_active' : '' ), ( !empty($subsub->children) ? '<i class="prdctfltr-plus"></i>' : '' ), $pf_adoptive_class, ( !empty($subsub->children) && in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_clicked' : '' ), ' prdctfltr_ft_' . sanitize_title( $subsub->slug ) );

													if ( !empty($subsub->children) ) {

														printf( '<div class="prdctfltr_sub" data-sub="%1$s">', $subsub->slug );

														foreach( $subsub->children as $subsubsub ) {

															$pf_adoptive_class = '';
															if ( $curr_fo['settings']['adoptive'] == 'yes' && isset($output_terms[$curr_fo['filter']]) && !empty($output_terms[$curr_fo['filter']]) && !array_key_exists($subsubsub->slug, $output_terms[$curr_fo['filter']]) ) {
																$pf_adoptive_class = ' pf_adoptive_hide';
															}

															$decode_slug = $subsubsub->slug;

															$curr_insert = $subsubsub->name . ( $curr_options['wc_settings_prdctfltr_show_counts'] == 'no' || $subsubsub->count == '0' ? '' : ' <span class="prdctfltr_count">' . ( isset($output_terms[$curr_fo['filter']]) && isset($output_terms[$curr_fo['filter']][$subsubsub->slug]) && $output_terms[$curr_fo['filter']][$subsubsub->slug] != $subsubsub->count ? $output_terms[$curr_fo['filter']][$subsubsub->slug] . '/' . $subsubsub->count : $subsubsub->count ) . '</span>' );

															printf('<label class="%5$s%4$s%6$s%7$s"><input type="checkbox" value="%1$s"%3$s /><span>%2$s</span></label>', $decode_slug, $curr_insert, ( in_array( $decode_slug, $curr_cat_selected ) ? ' checked' : '' ), ( in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_active' : '' ), $pf_adoptive_class, ( !empty($subsubsub->children) && in_array( $decode_slug, $curr_cat_selected ) ? ' prdctfltr_clicked' : '' ), ' prdctfltr_ft_' . sanitize_title( $subsubsub->slug ) );

														}

													echo '</div>';

													}

												}

												echo '</div>';

											}

										}

										echo '</div>';
									}
								}
							?>
							</div>
						</div>
						<?php
						}

						if ( $v == 'advanced' ) {
							$n++;
						}

					break;

					endswitch;

				$q++;

				endforeach;

				if ( !isset( $prdctfltr_global['widget_search'] ) ) {
					echo '<div class="prdctfltr_clear"></div>';
				}
			?>
			</div>
			<div class="prdctfltr_clear"></div>
		</div>
		<?php do_action( 'prdctfltr_filter_form_after', $curr_options, $pf_activated ); ?>
		<div class="prdctfltr_add_inputs">
		<?php
			if ( isset($prdctfltr_global['widget_style']) ) {
				echo '<input type="hidden" name="widget_search" value="yes" />';
			}
			if ( isset($_GET['s']) ) {
				echo '<input type="hidden" name="s" value="' . $_GET['s'] . '" />';
				echo '<input type="hidden" name="post_type" value="product" />';
			}
			if ( isset($_GET['page_id']) ) {
				echo '<input type="hidden" name="page_id" value="' . $_GET['page_id'] . '" />';
			}
			if ( isset($_GET['lang']) ) {
				echo '<input type="hidden" name="lang" value="' . $_GET['lang'] . '" />';
			}
			$curr_posttype = get_option( 'wc_settings_prdctfltr_force_product', 'no' );
			if ( $curr_posttype == 'no' ) {
				if ( !isset($_GET['s']) && $pf_structure == '' && ( is_shop() || is_product_taxonomy() ) ) {
					echo '<input type="hidden" name="post_type" value="product" />';
				}
			}
			else {
				echo '<input type="hidden" name="post_type" value="product" />';
			}
			if ( is_product_category() && $curr_cat_query !== 'yes' ) {
				$pf_queried_term = get_queried_object();
				echo '<input type="hidden" name="' . $pf_queried_term->taxonomy . '" value="' . $pf_queried_term->slug . '" />';
			}
		?>
		</div>
	</form>
	</div>
<?php

	do_action( 'prdctfltr_filter_after', $curr_options, $pf_activated );

	if ( isset( $prdctfltr_global['categories_active'] ) && $prdctfltr_global['categories_active'] === false ) {
		add_filter( 'woocommerce_is_filtered', create_function('', 'return true;') );
	}

	wp_reset_query();
	wp_reset_postdata();

	if ( isset( $prdctfltr_global['woo_template'] ) ) {
		unset($prdctfltr_global['woo_template']);
	}

?>