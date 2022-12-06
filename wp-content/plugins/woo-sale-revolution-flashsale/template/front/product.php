<?php
class WC_flash_sale_Product {

	public function __construct()
	{
			add_filter( 'woocommerce_grouped_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
//			add_filter( 'woocommerce_variable_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
			add_filter( 'woocommerce_sale_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
			add_filter( 'woocommerce_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
			add_filter( 'woocommerce_empty_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
			add_filter( 'woocommerce_variation_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
			add_filter( 'woocommerce_variation_sale_price_html', array(&$this, 'on_price_htmla'), 10, 2 );
	}

	public function on_price_htmla( $html, $_product ) {
				$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
				$base_price = $tax_display_mode == 'incl' ? $_product->get_price_including_tax() : $_product->get_price_excluding_tax();
				$html =wc_price($base_price);
				$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );

				$arr=$pw_discount=$result=$timer="";
				$query_meta_query=array('relation' => 'AND');
				$query_meta_query[] = array(
					'key' =>'status',
					'value' => "active",
					'compare' => '=',
				);
				$matched_products = get_posts(
					array(
						'post_type' 	=> 'flash_sale',
						'numberposts' 	=> -1,
						'post_status' 	=> 'publish',
						'fields' 		=> 'ids',
						'orderby'	=>'modified',
						'no_found_rows' => true,
						'meta_query' => $query_meta_query,
					)
				);
				$id=$_product->id;
				foreach($matched_products as $pr)
				{
					$arr=$type="";
					$resultr=0;
					$pw_type = get_post_meta($pr,'pw_type',true);
					$pw_cart_roles = get_post_meta($pr,'pw_cart_roles',true);
					if(($pw_cart_roles == 'roles' && empty($pw_roles )) || ($pw_cart_roles == 'capabilities' && empty($pw_capabilities )) || ($pw_cart_roles == 'users' && empty($pw_users )))
						$resultr = 1;
					//For Check Roles
					if ($pw_cart_roles == 'roles' && isset($pw_roles) && is_array($pw_roles)) {
						if (is_user_logged_in()) {
							foreach ($pw_roles as $role) {
								if (current_user_can($role)) {
									$resultr = 1;
									break;
								}
							}
						}
					}//End check Roles

					//For Check capabilities
					if ($pw_cart_roles == 'capabilities' && isset($pw_capabilities) && is_array($pw_capabilities)) {
						if (is_user_logged_in()) {
							foreach ($pw_capabilities as $capabilities) {
								if (current_user_can($capabilities)) {
									$resultr = 1;
									break;
								}
							}
						}
					}//End check capabilities

					//For Check User's
					if ($pw_cart_roles == 'users' && isset($pw_users) && is_array($pw_users)) {
						if (is_user_logged_in()) {
							if (in_array(get_current_user_id(), $pw_users)){
								$resultr = 1;
							}
						}
					}//End Check Users
					//echo $resultr;
					if($resultr==1 || $pw_cart_roles == 'everyone')
					{
						$pw_to=strtotime(get_post_meta($pr,'pw_to',true));
						$pw_from=strtotime(get_post_meta($pr,'pw_from',true));
						$arr= get_post_meta($pr,'pw_array',true);
						$blogtime = strtotime(current_time( 'mysql' ));
						if($pw_to=="" && ($pw_type=="quantity" || $pw_type=="special"))
						{
							$pw_from=$blogtime-1000;
							$pw_to=$blogtime+1000;
						}
						if($blogtime<$pw_to && $blogtime>$pw_from)
						{
							if (is_array($arr) && in_array($id, $arr))
							{
								$pw_matched= get_post_meta($pr,'pw_matched',true);
								if($pw_type=="flashsale")
								{
									$pw_type_discount= get_post_meta($pr,'pw_type_discount',true);
									if($pw_matched=="only")
									{
										$pw_dis= get_post_meta($pr,'pw_discount',true);
										if ( $pw_type_discount=="percent")
											$pw_discount += calculate_modifiera( $pw_dis, $base_price );
										else
											$pw_discount +=$pw_dis;
										$timer=get_post_meta($pr,'pw_to',true);
										goto break_line;
									}
									elseif($pw_matched=="all")
									{
										$pw_dis= get_post_meta($pr,'pw_discount',true);
										if ( $pw_type_discount=="percent" )
											$pw_discount += calculate_modifiera( $pw_dis, $base_price );
										else
											$pw_discount +=$pw_dis;
										$timer=get_post_meta($pr,'pw_to',true);
									}
								}
								else
								{
									if($pw_matched=="only")
										goto break_line;
								}
							}
						}
					}
				}

			break_line:
			if($pw_discount!="")
			{
				if ( false !== strpos( $pw_discount, '%' ) )
				{
					$max_discount = calculate_discount_modifiera( $pw_discount, $base_price );
					$result = round( floatval( $base_price ) - ( floatval( $max_discount  )), (int) $num_decimals );
				}
				else
					$result=$base_price-$pw_discount;
			}

			$discount_price=$result;
			$display_price="20";

			if ( $discount_price && $discount_price != $base_price ) {
				if ( apply_filters( 'wc_dynamic_pricing_use_discount_format', true ) ) {

					if ( $_product->is_type( 'variable' ) ) {
						$from = '<span class="from">' . _x( 'From:', 'min_price', 'pw_wc_flash_sale' ) . ' </span>';
					}

					$html = '<del>' .   wc_price($base_price). '</del><ins> ' .  wc_price($discount_price). '</ins>';
				} else {

					if ( $_product->is_type( 'variable' ) ) {
						$from = '<span class="from">' . _x( 'From:', 'min_price', 'pw_wc_flash_sale' ) . ' </span>';
					}

					$html = $from . $discount_price ;
				}
			}
			elseif ( $discount_price === 0 || $discount_price === 0.00 ) {
				$html = $_product->get_price_html_from_to( $_product->regular_price, __( 'Free!', 'pw_wc_flash_sale' ) );
			}

			if ( is_shop()) {
				if(get_option('pw_woocommerce_flashsale_archive_countdown')!="yes")
					goto break_ret;
			}
			if ( is_singular( 'product' ) ) {
				if(get_option('pw_woocommerce_flashsale_single_countdown')!="yes")
					goto break_ret;
			}
			if($timer!="")
			{
				$id=rand(0,1000);
				$countdown=get_option( 'pw_woocommerce_flashsale_countdown');
				$fontsize=get_option( 'pw_woocommerce_flashsale_fontsize_countdown');
				if(is_admin())
				{
					$countdown="style1";
					$fontsize="medium";
				}
				$html.='
					<div class="fl-pcountdown-cnt">
						<ul class="fl-'.$countdown.' fl-'.$fontsize.' fl-countdown fl-countdown-pub countdown_'.$id.'">
						  <li><span class="days">00</span><p class="days_text">Days</p></li>
							<li class="seperator">:</li>
							<li><span class="hours">00</span><p class="hours_text">'.__('Hours','pw_wc_flash_sale').'</p></li>
							<li class="seperator">:</li>
							<li><span class="minutes">00</span><p class="minutes_text">'.__('Minutes','pw_wc_flash_sale').'</p></li>
							<li class="seperator">:</li>
							<li><span class="seconds">00</span><p class="seconds_text">'.__('Seconds','pw_wc_flash_sale').'</p></li>
						</ul>
					</div>
					<script type="text/javascript">
						jQuery(".countdown_'.$id.'").countdown({
							date: "'.$timer.'",
							offset: -8,
							day: "Day",
							days: "Days"
						}, function () {
						//	alert("Done!");
						});
					</script>';
			}
			break_ret:
			return apply_filters( 'wc_dynamic_pricing_price_html', $html, $_product );
	}
}
new WC_flash_sale_Product();
?>