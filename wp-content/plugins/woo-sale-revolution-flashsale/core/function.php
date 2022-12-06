<?php


/*add_action('wp_ajax_pw_fetch_rule', 'pw_fetch_rule');
add_action('wp_ajax_nopriv_pw_fetch_rule', 'pw_fetch_rule');
function pw_fetch_rule() {
	$query_meta_query=array('relation' => 'AND');
	$query_meta_query[] = array(
		'key' =>'status',
		'value' => "active",
		'compare' => '=',
	);
	$args=array(
		'post_type'=>'flash_sale',
		'posts_per_page'=>-1,
		'order'=>'data',
		'orderby'=>'DESC',
		'meta_query' => $query_meta_query,		
	);
	$loop = new WP_Query( $args );		

		while ( $loop->have_posts() ) : 
			$loop->the_post();
			echo '<option value="'.get_the_ID().'">
					'.get_post_meta(get_the_ID(),'pw_name',true).'
				</option>';
		endwhile;	

	exit(0);
}

add_action('wp_ajax_pw_fetch_product', 'pw_fetch_product');
add_action('wp_ajax_nopriv_pw_fetch_product', 'pw_fetch_product');
function pw_fetch_product() {

	$args=array(
		'post_type'=>'product',
		'posts_per_page'=>-1,
		'order'=>'data',
		'orderby'=>'DESC',
	);
	$loop = new WP_Query( $args );		

		while ( $loop->have_posts() ) : 
			$loop->the_post();
			echo '<option value='.get_the_ID().'>
					'.get_the_title().'
				</option>';
		endwhile;	

	exit(0);
}

function calculate_discount_modifiera( $percentage, $price ) {
	$percentage = str_replace( '%', '', $percentage ) / 100;

	return $percentage * $price;
}	

function fl_product_rule_custom_style($rand_id , $text_colour , $countdown_backcolour , $countdown_area_backcolour , $description_area_backcolour) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.countdown-'.$rand_id.'{
			background-color: '.$countdown_area_backcolour.'
		}
		.countdown-'.$rand_id.' ul.fl-countdown li span , .countdown-'.$rand_id.' ul.fl-countdown li p ,.countdown-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.countdown-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.countdown-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		 }
		.car-'.$rand_id.' .fs-itemdesc{
			background:'.$description_area_backcolour.';
		}
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}
function fl_rule_list_custom_style($rand_id , $text_colour , $countdown_backcolour  , $overlay_backcolour) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.rulelist-'.$rand_id.' .fl-rulcnt-overlay{
			background: '.$overlay_backcolour.'
		}
		.rulelist-'.$rand_id.' ul.fl-countdown li span , .rulelist-'.$rand_id.' ul.fl-countdown li p ,.rulelist-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.rulelist-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.rulelist-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		}
		 
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}

function fl_rule_slider_custom_style($rand_id , $text_colour , $countdown_backcolour  , $overlay_backcolour) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.ruleslider-'.$rand_id.' .fl-rulcnt-overlay{
			background: '.$overlay_backcolour.'
		}
		.ruleslider-'.$rand_id.' ul.fl-countdown li span , .ruleslider-'.$rand_id.' ul.fl-countdown li p ,.ruleslider-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.ruleslider-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.ruleslider-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		}
		 
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}

function fl_top_product_grid_custom_style($rand_id , $text_colour , $countdown_backcolour , $description_area_backcolour ) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.countdown-'.$rand_id.' ul.fl-countdown li span , .countdown-'.$rand_id.' ul.fl-countdown li p ,.countdown-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.countdown-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.countdown-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		 }
		.col-'.$rand_id.' .fs-itemdesc{
			background:'.$description_area_backcolour.';
		}
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}
function fl_top_product_carousel_custom_style($rand_id , $text_colour , $countdown_backcolour , $description_area_backcolour ) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.countdown-'.$rand_id.' ul.fl-countdown li span , .countdown-'.$rand_id.' ul.fl-countdown li p ,.countdown-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.countdown-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.countdown-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		 }
		.car-'.$rand_id.' .fs-itemdesc{
			background:'.$description_area_backcolour.';
		}
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}
*/
?>


