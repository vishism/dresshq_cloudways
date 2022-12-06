<?php

	/**
	 * Product Filter Get Variable Product
	 */
	$curr_variable = get_option( 'wc_settings_prdctfltr_use_variable_images', 'no' );

	if ( $curr_variable == 'yes' ) {

		if ( function_exists('runkit_function_rename') && function_exists( 'woocommerce_get_product_thumbnail' ) ) :
			runkit_function_rename ( 'woocommerce_get_product_thumbnail', 'old_woocommerce_get_product_thumbnail' );
		endif;

		if ( !function_exists( 'woocommerce_get_product_thumbnail' ) ) :
		function woocommerce_get_product_thumbnail( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0  ) {

			global $product;

			if ( $product->is_type( 'variable' ) ) {

				$attrs = array();
				foreach( $_GET as $k => $v ){
					if ( substr( $k, 0, 3 ) == 'pa_' ) {

						if ( strpos($v, ',') ) {
							$v_val = explode(',', $v);
							$v_val = $v_val[0];
						}
						else if ( strpos($v, '+') ) {
							$v_val = explode('+', $v);
							$v_val = $v_val[0];
						}
						else {
							$v_val = $v;
						}

						$attrs = $attrs + array(
							$k => $v_val
						);
					}
				}

				if ( count( $attrs ) == 0 ) {
					global $wp_the_query;
					if ( isset( $wp_the_query->query ) ) {
						foreach( $wp_the_query->query as $k => $v ){
							if ( substr( $k, 0, 3 ) == 'pa_' ) {

								if ( strpos($v, ',') ) {
									$v_val = explode( ',', $v );
									$v_val = $v_val[0];
								}
								else if ( strpos( $v, '+' ) ) {
									$v_val = explode( '+', $v );
									$v_val = $v_val[0];
								}
								else {
									$v_val = $v;
								}

								$attrs = $attrs + array(
									$k => $v_val
								);
							}
						}
					}
				}

				if ( count($attrs) == 0 ) {
					global $prdctfltr_global;

					if ( isset( $prdctfltr_global['active_filters'] ) ) {
						foreach( $prdctfltr_global['active_filters'] as $k => $v ){
							if ( substr( $k, 0, 3 ) == 'pa_' ) {

								if ( strpos( $v, ',' ) ) {
									$v_val = explode( ',', $v );
									$v_val = $v_val[0];
								}
								else if ( strpos( $v, '+' ) ) {
									$v_val = explode( '+', $v );
									$v_val = $v_val[0];
								}
								else {
									$v_val = $v;
								}

								$attrs = $attrs + array(
									$k => $v_val
								);
							}
						}
					}
				}

				if ( is_product_taxonomy() ) {
					$attrs = array_merge( $attrs, array( get_query_var('taxonomy') => get_query_var('term') ) );
				}

				if ( count($attrs) > 0 ) {
					$curr_var = $product->get_available_variations();
					foreach( $curr_var as $key => $var ) {
						$curr_var_set[$key]['attributes'] = $var['attributes'];
						$curr_var_set[$key]['variation_id'] = $var['variation_id'];
					}
					$found = WC_Prdctfltr::prdctrfltr_search_array( $curr_var_set, $attrs );
				}
			}

			if ( isset( $found[0] ) && $found[0]['variation_id'] && has_post_thumbnail( $found[0]['variation_id'] ) ) {
				$image = get_the_post_thumbnail( $found[0]['variation_id'], $size );
			} elseif ( has_post_thumbnail( $product->id ) ) {
				$image = get_the_post_thumbnail( $product->id, $size );
			} elseif ( ( $parent_id = wp_get_post_parent_id( $product->id ) ) && has_post_thumbnail( $parent_id ) ) {
				$image = get_the_post_thumbnail( $product, $size );
			} else {
				$image = wc_placeholder_img( $size );
			}

			return $image;

		}
		endif;
	}

?>