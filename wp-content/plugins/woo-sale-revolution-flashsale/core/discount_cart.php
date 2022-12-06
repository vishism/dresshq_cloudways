<?php

add_action( 'woocommerce_loaded', 'pw_cashback_woocommerce_loaded' );
function pw_cashback_woocommerce_loaded() {
	add_action('woocommerce_cart_loaded_from_session', 'apply_coupons', 100);
	if ( version_compare( WOOCOMMERCE_VERSION, "2.1.0" ) >= 0 ) {
		add_filter( 'woocommerce_cart_item_price','pw_cashback_display_cart_item_price_html', 10, 3 );
	} else {
		add_filter( 'woocommerce_cart_item_price_html', 'pw_cashback_display_cart_item_price_html' , 10, 3 );
	}
}
 
function apply_coupons($cart) {
	global $woocommerce;
//	if ( ! is_cart())
//		return;
//	if ( $woocommerce->cart->has_discount($woocommerce->session->wc_fals_sale_discount_code ) )
//		return;		
	//$amount = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() ) );
	$amount=0;
	foreach ($woocommerce->cart->cart_contents as $cart_item_key => $cart_item)
	{
		
		$quantity = (isset($cart_item['quantity']) && $cart_item['quantity']) ? $cart_item['quantity'] : 1;
	//	echo $cart_item['data']->get_price();
		$amount += $cart_item['data']->get_price() * $quantity;		
	}

	if($amount>0)
	{
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
				'no_found_rows' => true,
				'orderby'	=>'modified',
				'meta_query' => $query_meta_query,
			)
		);
		$pw_discount=$price_adjusted=false;
		foreach($matched_products as $pr)
		{
			$result=0;
			$pw_type = get_post_meta($pr,'pw_type',true);
			if($pw_type=="cart")
			{
				$pw_to=strtotime(get_post_meta($pr,'pw_to',true));
				$pw_from=strtotime(get_post_meta($pr,'pw_from',true));
				$blogtime = strtotime(current_time( 'mysql' ));
				if($pw_to=="")
				{
					$pw_from=$blogtime-1000;
					$pw_to=$blogtime+1000;
				}
				if($blogtime<$pw_to && $blogtime>$pw_from)
				{
					$pw_cart_roles = get_post_meta($pr,'pw_cart_roles',true);
					$pw_roles = get_post_meta($pr,'pw_roles',true);
					$pw_capabilities = get_post_meta($pr,'pw_capabilities',true);
					$pw_users = get_post_meta($pr,'pw_users',true);
				//	print_r ($pw_roles);
					if(($pw_cart_roles == 'roles' && empty($pw_roles )) || ($pw_cart_roles == 'capabilities' && empty($pw_capabilities )) || ($pw_cart_roles == 'users' && empty($pw_users )))
						$result = 1;
					//For Check
					if ($pw_cart_roles == 'roles' && isset($pw_roles) && is_array($pw_roles)) {
						if (is_user_logged_in()) {
							foreach ($pw_roles as $role) {
								if (current_user_can($role)) {
								
									$result = 1;
									break;
								}
							}
						}
					}//End check
					//For Check capabilities
					if ($pw_cart_roles == 'capabilities' && isset($pw_capabilities) && is_array($pw_capabilities)) {
						if (is_user_logged_in()) {
							foreach ($pw_capabilities as $capabilities) {
								if (current_user_can($capabilities)) {
									$result = 1;
									break;
								}
							}
						}
					}//End check capabilities
					
					//For Check User's
					if ($pw_cart_roles == 'users' && isset($pw_users) && is_array($pw_users)) {
						if (is_user_logged_in()) {
							if (in_array(get_current_user_id(), $pw_users)){
								$result = 1;
								goto br_cart;
							}
						}
					}//End Check Users
					
					//echo '<br/><br/>';
					if($result==1 || $pw_cart_roles == 'everyone')
					{
						$pw_matched_cart= get_option('pw_matched_cart');
						$pw_type_conditions= get_post_meta($pr,'pw_type_conditions',true);
						//echo $pw_type_conditions;
						if($pw_type_conditions=="total")
						{
							$pw_discount_qty= get_post_meta($pr,'pw_discount_qty',true);									
							if(is_array($pw_discount_qty))
							{
								foreach($pw_discount_qty as $discount_qty)
								{
									$min=$max=$discount="";
									$min=$discount_qty['min'];
									$max=$discount_qty['max'];
									//echo '<br><br><br>';
									//echo  $woocommerce->cart->get_cart_total();
									if($amount>=$min && $amount<=$max)
									{
										//echo $min .'-'.$max.'-'.@$discount_qty['discount'].'-'.$amount;
										if($pw_matched_cart=="all")
										{
											if ( false !== strpos( @$discount_qty['discount'], '%' ))										
												$pw_discount +=calculate_discount_modifiera(@$discount_qty['discount'],$amount);
											else
												$pw_discount+=@$discount_qty['discount'];
											goto br_cart;	
										}
										elseif($pw_matched_cart=="only")
										{
											if ( false !== strpos( @$discount_qty['discount'], '%' ))
												$pw_discount +=calculate_discount_modifiera(@$discount_qty['discount'],$amount);
											else
												$pw_discount+=@$discount_qty['discount'];
											//$pw_discount=@$discount_qty['discount'];
											goto br_cart;
										}
									}
								}
							}
						}
						elseif($pw_type_conditions=="products")
						{
							$pw_product= get_post_meta($pr,'pw_product',true);
							foreach ($woocommerce->cart->cart_contents as $cart_item_key => $cart_item)
							{
								if (is_array($pw_product) && in_array($cart_item['product_id'], $pw_product))
								{
									if($pw_matched_cart=="all")
									{
										$pw_disc = get_post_meta($pr,'pw_discount',true);
										if ( false !== strpos( $pw_disc, '%' ))
											$pw_discount += calculate_discount_modifiera( $pw_disc, $amount );
										else
											$pw_discount +=$pw_disc;
										goto br_cart;
									}	
									elseif($pw_matched_cart=="only")
									{
										$pw_disc = get_post_meta($pr,'pw_discount',true);
										if ( false !== strpos( $pw_disc, '%' ))
											$pw_discount += calculate_discount_modifiera( $pw_disc, $amount );
										else
											$pw_discount +=$pw_disc;
										goto br_cart;
									}
								}
							}
							
						}
						//echo '<br/><br/>';
					}
				}
			}
		}//end foreach
		//echo $pw_discount;
		br_cart:
		if($pw_discount!=false)
		{
			if ( false !== strpos( $pw_discount, '%' ))
			{
				$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );			
				$max_discount = calculate_discount_modifiera( $pw_discount, $amount );
				$price_adjusted = round(floatval($max_discount), (int) $num_decimals );
			}
			else
				$price_adjusted=$pw_discount;

			if($price_adjusted!=false)
			{
				$woocommerce->session->wc_fals_sale_discount_code =$price_adjusted;
				add_filter('woocommerce_get_shop_coupon_data','add_coupon_v', 10, 2);
                add_action('woocommerce_before_calculate_totals','apply_coupon');
			}
		}
	}//end if 
	
}

function apply_coupon()
{
	global $woocommerce;
//	print_r($this->opt['settings']['cart_discount_title']);
	$the_coupon = new WC_Coupon(apply_filters('woocommerce_coupon_code', 'Cart redemption'));

	if ($the_coupon->id && $the_coupon->is_valid() && !$woocommerce->cart->has_discount('Cart redemption')) {

		// Do not apply coupon with individual use coupon already applied
		if ($woocommerce->cart->applied_coupons) {
			foreach ($woocommerce->cart->applied_coupons as $code) {
				$coupon = new WC_Coupon($code);

				if ($coupon->individual_use == 'yes') {
					return false;
				}
			}
		}
		$woocommerce->cart->applied_coupons[] = apply_filters('woocommerce_coupon_code', 'Cart redemption');

		return true;
	}
}

function add_coupon_v($param, $code)
{
	global $woocommerce;
	if ($code == apply_filters('woocommerce_coupon_code', 'Cart redemption')) {
		$coupons = array(
			'id' => 887712,
			'type' => 'fixed_cart',
			'amount' => $woocommerce->session->wc_fals_sale_discount_code,
			'individual_use' => 'no',
			'product_ids' => array(),
			'exclude_product_ids' => array(),
			'usage_limit' => '',
			'usage_limit_per_user' => '',
			'limit_usage_to_x_items' => '',
			'usage_count' => '',
			'expiry_date' => '',
			'apply_before_tax' => 'yes',
			'free_shipping' => 'no',
			'product_categories' => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items' => 'no',
			'minimum_amount' => '',
			'customer_email' => array(),
		);
		return $coupons;
	}
}


function pw_cashback_display_cart_item_price_html( $html, $cart_item ) {
		if (isset($cart_item['discounts'])) {
			$_product = $cart_item['data'];
//		print_r($cart_item['discounts']);
		if (function_exists('get_product')) {
			$price_adjusted = get_option('woocommerce_tax_display_cart') == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();
			$price_base = $cart_item['discounts']['display_price'];
		} else {
			if (get_option('woocommerce_display_cart_prices_excluding_tax') == 'yes') :
				$price_adjusted = $cart_item['data']->get_price_excluding_tax();
				$price_base = $cart_item['discounts']['display_price'];
			else :
				$price_adjusted = $cart_item['data']->get_price();
				$price_base = $cart_item['discounts']['display_price'];
			endif;
		}
			//$price_adjusted ="10";
			if (!empty($price_adjusted) || $price_adjusted === 0) {
				if (apply_filters('wc_dynamic_pricing_use_discount_format', true)) {
					$html = '<del>' . woocommerce_price($price_base) . '</del><ins> ' . woocommerce_price($price_adjusted) . '</ins>';
				} else {
					$html = '<span class="amount">' . woocommerce_price($price_adjusted). '</span>';
				}
			}
		}
	//	return 'asdsad';
		return $html;
}


function sort_by_price($cart_item_a, $cart_item_b) {
	return $cart_item_a['data']->get_price() > $cart_item_b['data']->get_price();
}

function on_cart_loaded_from_session( $cart ) {
	global $woocommerce;

	$sorted_cart = array();
	if ( sizeof( $cart->cart_contents ) > 0 ) {
		foreach ( $cart->cart_contents as $cart_item_key => $values ) {
			//echo 'a';
			$sorted_cart[$cart_item_key] = $values;
		}
	}

	//Sort the cart so that the lowest priced item is discounted when using block rules.
	@uasort( $sorted_cart, 'sort_by_price' );
	//print_r($sorted_cart);
	//adjust_cart( $sorted_cart );
	adjust_cart_rule( $sorted_cart );

/*	$query_meta_query=array('relation' => 'AND');
	$query_meta_query[] = array(
		'key' =>'pw_type',
		'value' => "cart",
		'compare' => '=',
	);
	$matched_products = get_posts(
		array(
			'post_type' 	=> 'flash_sale',
			'numberposts' 	=> -1,
			'post_status' 	=> 'publish',
			'fields' 		=> 'ids',
			'no_found_rows' => true,
			'orderby'	=>'modified',
			'meta_query' => $query_meta_query,
		)
	);
	print_r($matched_products);
	$amount = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() ) );
*/
}
add_action( 'woocommerce_cart_loaded_from_session', 'on_cart_loaded_from_session', 99, 1 );


function get_cart_item_categories($cart_item)
{
	$categories = array();
	$current = wp_get_post_terms($cart_item['data']->id, 'product_cat');
	foreach ($current as $category) {
		$categories[] = $category->term_id;
	}
	return $categories;
}
function get_adjact_item_categories($category)
{
	$categories = array();
	$current = wp_get_post_terms($cart_item['data']->id, 'product_cat');
	foreach ($current as $category) {
		$categories[] = $category->term_id;
	}
	return $categories;
}
function get_cart_item_tags($cart_item)
{
	$tags = array();
	$current = wp_get_post_terms($cart_item['data']->id, 'product_tag');
	foreach ($current as $tag) {
		$tags[] = $tag->term_id;
	}
	return $tags;
}

function adjust_cart_rule( $cart ) {
	global $woocommerce,$wpdb;
	$arr_cart= array();
	foreach ( $cart as $cart_item_key => $cart_item )
	{
		$arr_cart[$cart_item['data']->id]=array(
			"id"=>$cart_item['data']->id,
			"orginal_price" =>$cart_item['data']->price,
			"price_adjusted"=>0,
			"quantity"=>$cart_item['quantity'],
			'lock'=>'no',
			'lock_sp'=>'no',
		);
	}

	$arr = array();
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
			'no_found_rows' => true,
			'orderby'	=>'modified',
			'meta_query' => $query_meta_query,
		)
	);
	foreach($matched_products as $pr)
	{
		$result=0;
		$pw_type = get_post_meta($pr,'pw_type',true);
		if($pw_type=="flashsale" || $pw_type=="special" || $pw_type=="quantity")
		{
			$pw_to=strtotime(get_post_meta($pr,'pw_to',true));
			$pw_from=strtotime(get_post_meta($pr,'pw_from',true));
			$blogtime = strtotime(current_time( 'mysql' ));
			$pw_apply_to=get_post_meta($pr,'pw_apply_to',true);
		//Check For User Role
			$pw_cart_roles = get_post_meta($pr,'pw_cart_roles',true);
			$pw_roles = get_post_meta($pr,'pw_roles',true);
			$pw_capabilities = get_post_meta($pr,'pw_capabilities',true);
			$pw_users = get_post_meta($pr,'pw_users',true);
			$pw_products_to_adjust = get_post_meta($pr,'pw_products_to_adjust',true);
			$quantity_base = get_post_meta($pr,'quantity_base',true);
			
			if(($pw_cart_roles=="everyone") || ($pw_cart_roles == 'roles' && empty($pw_roles )) || ($pw_cart_roles == 'capabilities' && empty($pw_capabilities )) || ($pw_cart_roles == 'users' && empty($pw_users )))
				$result = 1;
			//For Check Roles
			if ($pw_cart_roles == 'roles' && isset($pw_roles) && is_array($pw_roles)) {
				if (is_user_logged_in()) {
					foreach ($pw_roles as $role) {
						if (current_user_can($role)) {
							$result = 1;
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
							$result = 1;
							break;
						}
					}
				}
			}//End check capabilities
			//For Check User's
			if ($pw_cart_roles == 'users' && isset($pw_users) && is_array($pw_users)) {
				if (is_user_logged_in()) {
					if (in_array(get_current_user_id(), $pw_users)){
						$result = 1;
					}
				}
			}//End Check Users
			
			//Check Time Rules
			if($pw_to=="" && ($pw_type=="quantity" || $pw_type=="special"))
			{
				$pw_from=$blogtime-1000;
				$pw_to=$blogtime+1000;
			}
			if($blogtime>$pw_to && $blogtime<$pw_from)
				$result=0;

			//Check If Customer can give discount in this rule
			if($result==0)
				goto break_one_rule;
			//Foreach Cart Item
			foreach ( $cart as $cart_item_key => $cart_item )
			{
				$is_ok=false;$pw_discount="";
				if($pw_apply_to=="pw_product" && !in_array($cart_item['data']->id, get_post_meta($pr,'pw_product',true)))
					goto break_one_cart;
				if($pw_apply_to=="pw_except_product" && !in_array($cart_item['data']->id, get_post_meta($pr,'pw_except_product',true)))
					goto break_one_cart;
				if($pw_apply_to=="pw_product_category" && count(array_intersect(get_cart_item_categories($cart_item), get_post_meta($pr,'pw_product_category',true))) <= 0)
					goto break_one_cart;
				if($pw_apply_to=="pw_except_product_category" && count(array_intersect(get_cart_item_categories($cart_item), get_post_meta($pr,'pw_except_product_category',true))) > 0)
					goto break_one_cart;
				if($pw_apply_to=="pw_product_tag" && count(array_intersect(get_cart_item_tags($cart_item), get_post_meta($pr,'pw_product_tag',true))) <= 0)
					goto break_one_cart;
				if($pw_apply_to=="pw_except_product_tag" && count(array_intersect(get_cart_item_tags($cart_item), get_post_meta($pr,'pw_except_product_tag',true))) > 0)
					goto break_one_cart;
				
				//echo $cart_item['data']->id.'-';
			//echo count(array_intersect(get_cart_item_categories($cart_item), get_post_meta($pr,'pw_product_category',true)));
				//echo $pw_apply_to;
				
				//Get Price Orginal
				$original_price=get_price_to_discount( $cart_item, $cart_item_key );								
				
				//Pw_type=Flashsale
				if($pw_type=="flashsale")
				{
					$pw_discount=0;
					$pw_matched= get_post_meta($pr,'pw_matched',true);
					$pw_type_discount= get_post_meta($pr,'pw_type_discount',true);
					
					$pw_dis = get_post_meta($pr,'pw_discount',true);
					if ( $pw_type_discount=="percent")
						$pw_discount = calculate_modifiera( $pw_dis, $original_price );
					else
						$pw_discount =$pw_dis;

					//Check If matched Rules
					if($pw_matched=="only" && $arr_cart[$cart_item['data']->id]['lock']=="no")
					{
						$arr_cart[$cart_item['data']->id]['lock']='yes';
						$arr_cart[$cart_item['data']->id]['price_adjusted']=$pw_discount;
					}
					elseif($pw_matched=="all" && $arr_cart[$cart_item['data']->id]['lock']=="no")
						$arr_cart[$cart_item['data']->id]['price_adjusted']+=$pw_discount;

				}//endif pw_type=Flashsale
				
				//Else Pw_type=special || Quentity
				elseif($pw_type=="special" || $pw_type=="quantity")
				{
					//Check Products To Adjust
					//in_array($cart_item['data']->id, get_post_meta($pr,'pw_products_to_adjust_category',true)))
					/*
					if($pw_products_to_adjust=="other_categories" && count(array_intersect(get_cart_item_categories($cart_item), get_post_meta($pr,'pw_products_to_adjust_category',true))) > 0)
						$is_ok=true;
					elseif($pw_products_to_adjust=="other_products" && in_array($cart_item['data']->id, get_post_meta($pr,'pw_products_to_adjust_products',true)))
						$is_ok=true;
					elseif($pw_products_to_adjust=="matched")
						$is_ok=true;
					*/
					//elseif($pw_products_to_adjust=="other_products" && in_array($cart_item['data']->id, get_post_meta($pr,'pw_products_to_adjust_products',true)))
					//	$is_ok=true;
					//elseif($pw_products_to_adjust=="matched")
					//	$is_ok=true;
					//if product_to_adjust is set and ok
				//	echo $cart_item['data']->id.'-';
					//echo $is_ok.'-';
					//if($is_ok!=true)
					//	goto break_one_cart;
					//print_r(get_post_meta($pr,'pw_products_to_adjust_products',true));
					
					//pw_products_to_adjust_products
					// Check $pw_type=="special"
					if($pw_type=="special")
					{
						$loop= get_post_meta($pr,'amount_to_adjust',true);
						$is_true=false;
						$discount_calc=0;$pricetotall=$pricetotalla=0;$pw_products_to_adjust_category="";$price_adjusted_p=false;
						$pw_matched= get_post_meta($pr,'pw_matched',true);
						$amount_to_purchase = get_post_meta($pr,'amount_to_purchase',true);
						$amount_to_adjust =get_post_meta($pr,'amount_to_adjust',true);
						$adjustment_type=get_post_meta($pr,'adjustment_type',true);
						$adjustment_value=get_post_meta($pr,'adjustment_value',true);
						$pricetotalla=$cart_item['quantity']*$original_price;
						$pw_products_to_adjust_category=get_post_meta($pr,'pw_products_to_adjust_category',true);
						//echo $cart_item['quantity'].'<br/>';
						if($quantity_base=="all")
						{
							if($pw_apply_to=="pw_product_category")
							{
								$counter_cat = array();
								foreach ( $cart as $cart_key => $item) {
									$cat = get_cart_item_categories($item);
									foreach ($cat as $id) {
										if (isset($counter_cat[$id]))
											$counter_cat[$id] += $item['quantity'];
										else 
											$counter_cat[$id] = $item['quantity'];
									}
								}
								$pw_product_category=get_post_meta($pr,'pw_product_category',true);
								foreach((array)$pw_product_category as $cat)
								{
									if(isset($counter_cat[$cat]) && $counter_cat[$cat]>=$amount_to_purchase)
									{
										$is_true=true;
										//break;
									}
								}
							}
							elseif($pw_apply_to=="pw_product_tag")
							{
								$counter_tag = array();
								foreach ( $cart as $cart_key => $item) {
									$tag = get_cart_item_tags($item);
									foreach ($tag as $id) {
										if (isset($counter_tag[$id]))
											$counter_tag[$id] += $item['quantity'];
										else 
											$counter_tag[$id] = $item['quantity'];
									}
								}
								$pw_product_tag=get_post_meta($pr,'pw_product_tag',true);
								foreach((array)$pw_product_tag as $tag)
								{
									if(isset($counter_tag[$tag]) && $counter_tag[$tag]>=$amount_to_purchase)
										$is_true=true;
								}							
							}
							elseif($pw_apply_to=="pw_all_product")
							{
								$couner=0;
								foreach ( $cart as $cart_key => $item)
									$couner += $item['quantity'];

								if($couner>=$amount_to_purchase)
									$is_true=true;
							}
						}
						elseif($cart_item['quantity']>=$amount_to_purchase)
							$is_true=true;

						if($pw_products_to_adjust=="other_products")
						{
							//Foreach Cart For adjusted other_products
							if($is_true==true)
							{
								foreach ( $cart as $cart_key => $cart_i )
								{
									//echo $cart_i['data']->id;
									//Check If item in product_to_adjust
									$loop= get_post_meta($pr,'amount_to_adjust',true);
									if(in_array($cart_i['data']->id, get_post_meta($pr,'pw_products_to_adjust_products',true)) && $arr_cart[$cart_i['data']->id]['lock']=="no")
									{
										$original_p_s=get_price_to_discount( $cart_i, $cart_key );
										$cart_quantity= $cart_i['quantity'];
										if ( $adjustment_type=="percent" )
										{
											while($loop>0 && $cart_quantity>0)
											{
												$discount_calc += calculate_modifiera( $adjustment_value, $original_p_s );
												$loop--;
												$cart_quantity--;
											}
										}
										else
											$discount_calc = $amount_to_adjust*$adjustment_value;
										
										$discount_calc=$discount_calc/$cart_i['quantity'];
											//Foreach Amount_to_Adjust 		
										//Check If matched Rules
										if($pw_matched=="only" && $arr_cart[$cart_i['data']->id]['lock']=="no")
										{
											$arr_cart[$cart_i['data']->id]['lock']='yes';
											$arr_cart[$cart_i['data']->id]['price_adjusted']=$discount_calc;
											//break;
										}
										elseif($pw_matched=="all" && $arr_cart[$cart_i['data']->id]['lock']=="no")
										{
											$arr_cart[$cart_i['data']->id]['price_adjusted']+=$discount_calc;
											//break;
										}
									}//End if Check If item in product_to_adjust				
								}//End If $cart_i['quantity']>=$amount_to_purchase
							}//End Foreach adjusted other_products
						//count(array_intersect(get_cart_item_categories($cart_item), get_post_meta($pr,'pw_products_to_adjust_products',true))) > 0
						//	$is_ok=true;
						}
						//Else IF Adjust other_categories
						elseif($pw_products_to_adjust=="other_categories")
						{
							if($is_true==true)
							{
								//Foreach Cart For adjusted other_products
								foreach ( $cart as $cart_key => $cart_i )
								{
									//echo (count(array_intersect(get_cart_item_categories($cart_i), get_post_meta($pr,'pw_products_to_adjust_category',true))) > 0);
									
									//print_r(get_cart_item_categories($cart_i));
									//echo '@';
									//print_r(get_post_meta($pr,'pw_products_to_adjust_category',true));
									//echo '@';
									
									if((count(array_intersect(get_cart_item_categories($cart_i), get_post_meta($pr,'pw_products_to_adjust_category',true))) > 0) && $arr_cart[$cart_i['data']->id]['lock']=="no" )
									{
										$original_p_s=get_price_to_discount( $cart_i, $cart_key );
										$discount_calc=0;
										$cart_quantity= $cart_i['quantity'];
										//echo $original_p_s.'-';
										//print_r(get_cart_item_categories($cart_i));
										if ( $adjustment_type=="percent" )
										{
											while($loop>0  && $cart_quantity>0)
											{
												$discount_calc = calculate_modifiera( $adjustment_value, $original_p_s );
												$loop--;
												$cart_quantity--;
											}
										}
										else
										{
											while($loop>0  && $cart_quantity>0)
											{
												//echo $adjustment_value.'<br/>';
												$discount_calc += $adjustment_value;
												$loop--;
												$cart_quantity--;
											}
										}
										
										//echo $discount_calc;
										//print_r();
										$discount_calc=$discount_calc/$cart_i['quantity'];
										
										//echo $discount_calc.'<br/>';
										//Check If matched Rules
										if($pw_matched=="only" && $arr_cart[$cart_i['data']->id]['lock']=="no" && $arr_cart[$cart_i['data']->id]['lock_sp']=="no")
										{
											$arr_cart[$cart_i['data']->id]['lock']='yes';
											$arr_cart[$cart_i['data']->id]['lock_sp']='yes';
											$arr_cart[$cart_i['data']->id]['price_adjusted']=$discount_calc;
											//break;
										}
										elseif($pw_matched=="all" && $arr_cart[$cart_i['data']->id]['lock']=="no" && $arr_cart[$cart_i['data']->id]['lock_sp']=="no")
										{
											//echo 'dd';
											$arr_cart[$cart_i['data']->id]['lock_sp']='yes';
											$arr_cart[$cart_i['data']->id]['price_adjusted']+=$discount_calc;
											//break;
										}
									}
								}							
							//	print_r(get_cart_item_categories($cart_i));
							}//end if($cart_item['quantity']>=$amount_to_purchase)
						}//End Foreach Cart For adjusted other_products
						//Else IF Adjust matched
						elseif($pw_products_to_adjust=="matched")
						{
							if($is_true==true)
							{
								if ( $adjustment_type=="percent" )
								{
									$cart_quantity= $cart_i['quantity'];
									while($loop>0 && $cart_quantity>0)
									{
										$discount_calc += calculate_modifiera( $adjustment_value, $original_price );
										$loop--;
										$cart_quantity--;
									}
									//echo $discount_calc;
									//$temp=$cart_item['quantity']*$original_price;
									//$temp-=$discount_calc;
									//$temp=$temp/$cart_item['quantity'];
									//echo '@'.$temp.'@';
								}
								else
									$discount_calc = $amount_to_adjust*$adjustment_value;

								$discount_calc=$discount_calc/$cart_item['quantity'];									
								
								//Check If matched Rules
								if($pw_matched=="only" && $arr_cart[$cart_item['data']->id]['lock']=="no")
								{
									$arr_cart[$cart_item['data']->id]['lock']='yes';
									$arr_cart[$cart_item['data']->id]['price_adjusted']=$discount_calc;
								}
								elseif($pw_matched=="all" && $arr_cart[$cart_item['data']->id]['lock']=="no")
									$arr_cart[$cart_item['data']->id]['price_adjusted']+=$discount_calc;								
							}
						}//End Else IF Adjust matched

						//$arr_cart[$cart_item['data']->id]['price_adjusted']=$discount_calc;
						
					}//End if pw_type="special"

					// Check $pw_type=="quantity"
					elseif($pw_type=="quantity")
					{
						$pw_discount=0;$discount_calc=0;
						$pw_discount_qty= get_post_meta($pr,'pw_discount_qty',true);
						$pw_matched= get_post_meta($pr,'pw_matched',true);
						//Check if Qty is set
						if(is_array($pw_discount_qty))
						{
							if($pw_products_to_adjust=="other_products")
							{
									//Foreach Cart For adjusted other_products
									foreach ( $cart as $cart_key => $cart_i )
									{
										$original_p_s=get_price_to_discount( $cart_i, $cart_key );
										//Check If item in product_to_adjust
										if(in_array($cart_i['data']->id, get_post_meta($pr,'pw_products_to_adjust_products',true)) && $arr_cart[$cart_i['data']->id]['lock']=="no")
										{
											foreach($pw_discount_qty as $discount_qty)
											{
												$min=$max=$discount="";
												$min=$discount_qty['min'];
												$max=$discount_qty['max'];
												if($cart_item['quantity']>=$min && $cart_item['quantity']<=$max)
												{
													//if(get_option('pw_matched_rule','all')=="all"){}
													if ( false !== strpos( @$discount_qty['discount'], '%' ))
														$pw_discount = calculate_discount_modifiera( @$discount_qty['discount'], $original_p_s );
													else
														$pw_discount =@$discount_qty['discount'];
													//$pw_discount=$pw_discount/$cart_i['quantity'];			
													//Check If matched Rules
													if($pw_matched=="only" && $arr_cart[$cart_i['data']->id]['lock']=="no")
													{
														$arr_cart[$cart_i['data']->id]['lock']='yes';
														$arr_cart[$cart_i['data']->id]['price_adjusted']=$pw_discount;
														//break;
													}
													elseif($pw_matched=="all" && $arr_cart[$cart_i['data']->id]['lock']=="no")
													{
														$arr_cart[$cart_i['data']->id]['price_adjusted']+=$pw_discount;
														//break;
													}
												}
											}//End Foreach $pw_discount_qty
										}
									}
							}
							//Else IF Adjust other_categories
							elseif($pw_products_to_adjust=="other_categories")
							{
									//Foreach Cart For adjusted other_products
									foreach ( $cart as $cart_key => $cart_i )
									{
										$original_p_s=get_price_to_discount( $cart_i, $cart_key );
										//Check If item in product_to_adjust
										if((count(array_intersect(get_cart_item_categories($cart_i), get_post_meta($pr,'pw_products_to_adjust_category',true))) > 0) && $arr_cart[$cart_i['data']->id]['lock']=="no" )
										{
											foreach($pw_discount_qty as $discount_qty)
											{
												$min=$max=$discount="";
												$min=$discount_qty['min'];
												$max=$discount_qty['max'];
												if($cart_item['quantity']>=$min && $cart_item['quantity']<=$max)
												{
													//if(get_option('pw_matched_rule','all')=="all"){}
													if ( false !== strpos( @$discount_qty['discount'], '%' ))
														$pw_discount = calculate_discount_modifiera( @$discount_qty['discount'], $original_p_s );
													else
														$pw_discount =@$discount_qty['discount'];
														
													//$pw_discount=$pw_discount/$cart_i['quantity'];				
													//Check If matched Rules
													if($pw_matched=="only" && $arr_cart[$cart_i['data']->id]['lock']=="no")
													{
														$arr_cart[$cart_i['data']->id]['lock']='yes';
														$arr_cart[$cart_i['data']->id]['price_adjusted']=$pw_discount;
														//break;
													}
													elseif($pw_matched=="all" && $arr_cart[$cart_i['data']->id]['lock']=="no")
													{
														$arr_cart[$cart_i['data']->id]['price_adjusted']+=$pw_discount;
														//break;
													}
												}
											}//End Foreach $pw_discount_qty
										}
									}
							}
							//Else IF Adjust matched
							elseif($pw_products_to_adjust=="matched")
							{
								foreach($pw_discount_qty as $discount_qty)
								{
									$min=$max=$discount="";
									$min=$discount_qty['min'];
									$max=$discount_qty['max'];
									if($cart_item['quantity']>=$min && $cart_item['quantity']<=$max)
									{
										$pw_matched= get_post_meta($pr,'pw_matched',true);
										//if(get_option('pw_matched_rule','all')=="all"){}
										if ( false !== strpos( @$discount_qty['discount'], '%' ))
											$pw_discount = calculate_discount_modifiera( @$discount_qty['discount'], $original_price );
										else
											$pw_discount =@$discount_qty['discount'];
											
										//$pw_discount=$pw_discount/$cart_item['quantity'];				
										//echo $pw_discount.'-'.$discount_qty['discount'].'-'.$cart_i['quantity'];
										//Check If matched Rules
										if($pw_matched=="only" && $arr_cart[$cart_item['data']->id]['lock']=="no")
										{
											$arr_cart[$cart_item['data']->id]['lock']='yes';
											$arr_cart[$cart_item['data']->id]['price_adjusted']=$pw_discount;
										}
										elseif($pw_matched=="all" && $arr_cart[$cart_item['data']->id]['lock']=="no")
											$arr_cart[$cart_item['data']->id]['price_adjusted']+=$pw_discount;
									}
								}//End Foreach $pw_discount_qty
							}//End Else IF Adjust matched
						}//End If Check Qty					
						
					}//End if pw_type="quantity"
					
				}//End if pw_type=="special","quantity"
				break_one_cart:
			}//End Foreach Cart
		}//End If pw_type=="Quentity","flash_sale","special"
		break_one_rule:
	}// end Foreach Rules
	//print_r($arr_cart);
	
	foreach ( $cart as $cart_item_key => $cart_item )
	{
		$module="simple_category";
		$applied_rule_set_id="set_12";
		$price_adjusted=false;
		$price_adjusted_arr=$arr_cart[$cart_item['data']->id]['price_adjusted'];
		$original_price=$cart_item['data']->price;
		//Check If cart_item[id] and $arr_cart is equal		
		if($arr_cart[$cart_item['data']->id]['id']==$cart_item['data']->id && $price_adjusted_arr != 0 )
		{
			
			if ( $price_adjusted_arr != 0 &&  $price_adjusted_arr > 0 )
				$price_adjusted=$original_price-$price_adjusted_arr;
				//$price_adjusted=$price_adjusted_arr;
			//echo '**'.$original_price.'**';
			if($price_adjusted < 0)
				$price_adjusted=0;

			if( $price_adjusted!==false && floatval( $original_price ) != floatval( $price_adjusted ))
				apply_cart_item_adjustment( $cart_item_key, $original_price, $price_adjusted, $module, $applied_rule_set_id );		
		}//End Check If cart_item[id] and $arr_cart is equal
	}
	//foreach($arr_cart as $a)
	//	echo 'id:'.$a['id'].'/p:'.$a['price_adjusted'].'<br/>';
}

function adjust_cart( $cart ) {
	global $woocommerce,$wpdb;
//	if ( ! is_user_logged_in())
//	return;
/*	$arr_cart="";
	foreach ( $cart as $cart_item_key => $cart_item )
	{
		$arr_cart[$cart_item['data']->id]=array(
			"id"=>$cart_item['data']->id,
			"orginal_price" =>$cart_item['data']->price,
			"price_adjusted","",
		);
	}
	 */
	//print_r($arr_cart);

	//print_r($arr_cart);
	foreach ( $cart as $cart_item_key => $cart_item ) {
		
		//echo $arr_cart[$cart_item['data']->id];
		
		//print_r($cart_item);
		$original_price=get_price_to_discount( $cart_item, $cart_item_key );
		$result=false;
		$price_adjusted=$result;
		$module="simple_category";
		$applied_rule_set_id="set_12";
		$pw_discount="";
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
				'no_found_rows' => true,
				'orderby'	=>'modified',
				'meta_query' => $query_meta_query,
			)
		);
		//print_r($matched_products);
		foreach($matched_products as $pr)
		{
			$arr="";
			$result = 0;
			$pw_to=strtotime(get_post_meta($pr,'pw_to',true));
			$pw_from=strtotime(get_post_meta($pr,'pw_from',true));
			$blogtime = strtotime(current_time( 'mysql' ));
			$pw_type = get_post_meta($pr,'pw_type',true);
			//Check For User Role
				$pw_cart_roles = get_post_meta($pr,'pw_cart_roles',true);
				$pw_roles = get_post_meta($pr,'pw_roles',true);
				$pw_capabilities = get_post_meta($pr,'pw_capabilities',true);
				$pw_users = get_post_meta($pr,'pw_users',true);
			//	print_r ($pw_roles);
				if(($pw_cart_roles == 'roles' && empty($pw_roles )) || ($pw_cart_roles == 'capabilities' && empty($pw_capabilities )) || ($pw_cart_roles == 'users' && empty($pw_users )))
					$result = 1;
				//For Check Roles
				if ($pw_cart_roles == 'roles' && isset($pw_roles) && is_array($pw_roles)) {
					if (is_user_logged_in()) {
						foreach ($pw_roles as $role) {
							if (current_user_can($role)) {
								$result = 1;
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
								$result = 1;
								break;
							}
						}
					}
				}//End check capabilities

				//For Check User's
				if ($pw_cart_roles == 'users' && isset($pw_users) && is_array($pw_users)) {
					if (is_user_logged_in()) {
						if (in_array(get_current_user_id(), $pw_users)){
							$result = 1;
						}
					}
				}//End Check Users
				
			if($result==1 || $pw_cart_roles == 'everyone')
			{	
				if($pw_to=="" && ($pw_type=="quantity" || $pw_type=="special"))
				{
					//echo $pr;
					$pw_from=$blogtime-1000;
					$pw_to=$blogtime+1000;
				}
				if($blogtime<$pw_to && $blogtime>$pw_from)
				{
					$arr= get_post_meta($pr,'pw_array',true);
					if($pw_type=="flashsale")
					{
					
						if (is_array($arr) && in_array($cart_item['product_id'], $arr))
						{
							$pw_matched= get_post_meta($pr,'pw_matched',true);
							if($pw_matched=="only")
							{
								$pw_dis = get_post_meta($pr,'pw_discount',true);
								if ( false !== strpos( $pw_dis, '%' ))
									$pw_discount += calculate_discount_modifiera( $pw_dis, $original_price );
								else
									$pw_discount +=$pw_dis;
								//$pw_discount+= get_post_meta($pr,'pw_discount',true);
								//echo '<br/><br/>'.$pr.'-'.$pw_discount;
								goto break_line;
							}
							elseif($pw_matched=="all")
							{
								$pw_dis = get_post_meta($pr,'pw_discount',true);
								if ( false !== strpos( $pw_dis, '%' ))
									$pw_discount += calculate_discount_modifiera( $pw_dis, $original_price );
								else
									$pw_discount +=$pw_dis;
								//$pw_discount+= get_post_meta($pr,'pw_discount',true);
								//echo '<br/><br/>'.$pr.'-'.$pw_discount;	
							}
						}
					}
					elseif($pw_type=="quantity")
					{
						$pw_discount_qty= get_post_meta($pr,'pw_discount_qty',true);
						//print_r($pw_discount_qty);
					//	echo '</br></br>';
						$arr= get_post_meta($pr,'pw_array',true);					
						if(is_array($pw_discount_qty))
						{
	//						echo $pr;
	//						print_r($pw_discount_qty);
							foreach($pw_discount_qty as $discount_qty)
							{
								$min=$max=$discount="";
								$min=$discount_qty['min'];
								$max=$discount_qty['max'];
								//$discount=@$discount_qty['discount'].'-';
								//echo '-'.$pr.'-'.$min.'-'.$max.'<br/>';
								if($cart_item['quantity']>=$min && $cart_item['quantity']<=$max)
								{
									if (is_array($arr) && in_array($cart_item['product_id'], $arr))
									{
										$pw_matched= get_post_meta($pr,'pw_matched',true);
										if($pw_matched=="only")
										{	
											if ( false !== strpos( @$discount_qty['discount'], '%' ))
												$pw_discount += calculate_discount_modifiera( @$discount_qty['discount'], $original_price );
											else
												$pw_discount +=@$discount_qty['discount'];
											goto break_line;
										}
										elseif($pw_matched=="all")
											if ( false !== strpos( @$discount_qty['discount'], '%' ))
												$pw_discount += calculate_discount_modifiera( @$discount_qty['discount'], $original_price );
											else
												$pw_discount +=@$discount_qty['discount'];
										$pw_discount+=@$discount_qty['discount'];
									}
									//echo '<br/><br/>'.$min.'-'.$max.'-'.$pw_discount;
									//break;
								}
							}
						}
					}
					elseif($pw_type=="special")
					{
					//	echo '<br><br>';
						$discount_calc=0;
						$pw_matched= get_post_meta($pr,'pw_matched',true);
						$amount_to_purchase = get_post_meta($pr,'amount_to_purchase',true);
						$amount_to_adjust =$loop= get_post_meta($pr,'amount_to_adjust',true);
						$price_adjusted_p=false;
						$pricetotall=$pricetotalla=0;
						$adjustment_value=get_post_meta($pr,'adjustment_value',true);
						$pricetotalla=$cart_item['quantity']*$original_price;
						$arr= get_post_meta($pr,'pw_array',true);								

						if (is_array($arr) && in_array($cart_item['product_id'], $arr))
						{						
							if($cart_item['quantity']>$amount_to_purchase)
							{
								if ( false !== strpos( $adjustment_value, '%' ) )
								{
									while($loop>0)
									{
										$discount_calc += calculate_discount_modifiera( $adjustment_value, $original_price );
										$loop--;
									}
									
								}
								else
								{
									$discount_calc = $amount_to_adjust*$adjustment_value;
									//echo '-'.$discount_calc.'-';
								}
								//echo '-'.$discount_calc.'-';
								if($pw_matched=="only")
								{
									$discount_calc=$discount_calc/$cart_item['quantity'];
									$pw_discount =$discount_calc;
									goto break_line;
								}
								else{
								//	echo $discount_calc .'-';
									$discount_calca=$discount_calc/$cart_item['quantity'];
									$pw_discount +=$discount_calca;
									//echo $discount_calca ;	
									}
								//echo $pw_discount ;
								//echo $original_price.'-';
						/*		while($loop>0)
								{
									if ( false !== strpos( $adjustment_value, '%' ) )
									{
										//$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );			
										$max_discount += calculate_discount_modifiera( $adjustment_value, $original_price );
										//$price_adjusted_p+= round( floatval( $original_price ) - ( floatval( $max_discount  )), (int) $num_decimals );
									}
									else
									{
										$max_discount +=$adjustment_value;
										//$temp=$adjustment_value*$amount_to_adjust;
										//$pricetotall=$pricetotalla-$temp;
									}
									$loop--;
								}
								$pw_discount=$max_discount;
								
								$temp=$adjustment_value*$amount_to_adjust;
								$pricetotall=$pricetotalla-$temp;
								
								$temp=$amount_to_adjust*$original_price;
								//echo $amount_to_adjust;
								goto break_line;
								//if($price_adjusted_p!=false)
								//{
								//	if($price_adjusted_p==0)
								//	{
								///		$temp=$amount_to_adjust*$original_price;
								//		$pricetotall=$pricetotalla-$temp;
								//	}
								//	else
								//		$pricetotall=$pricetotalla-$price_adjusted_p;									
								//}
								//if($pricetotall<0)
								//	$pricetotall=0;
									*/
							/*	if($pw_matched=="only")
									$price_adjusted=$pricetotall/$cart_item['quantity'];
								elseif($pw_matched=="all")
									$price_adjusted +=$pricetotall/$cart_item['quantity'];
								*/
							}
						}
						//if($pw_matched=="only")
							//goto break_line_sp;							
					}
				}
			}
		}
		break_line:
	//	echo '<br/>'.$pw_discount;
		if($pw_discount!="")
		{
			if ( false !== strpos( $pw_discount, '%' ) )
			{
				$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );			
				$max_discount = calculate_discount_modifiera( $pw_discount, $original_price );
				$price_adjusted = round( floatval( $original_price ) - ( floatval( $max_discount  )), (int) $num_decimals );
			}
			else
				$price_adjusted=$original_price-$pw_discount;
		//	echo $original_price;
		}
	//	echo $price_adjusted;
//$cart_item['product_id']
		break_line_sp:
		if ( $price_adjusted !== false && floatval( $original_price ) != floatval( $price_adjusted ) ) {
			apply_cart_item_adjustment( $cart_item_key, $original_price, $price_adjusted, $module, $applied_rule_set_id );
		}
	}
}

	function get_price_to_discount($cart_item, $cart_item_key) {
		global $woocommerce;

		$result = false;
		
		$filter_cart_item = $cart_item;
		if (isset($woocommerce->cart->cart_contents[$cart_item_key])) {
			$filter_cart_item  = $woocommerce->cart->cart_contents[$cart_item_key];
			if (isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts'])) {
				//if (is_cumulative($cart_item, $cart_item_key)) {
				//	$result = $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['price_adjusted'];
				//} else {
					$result = $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['price_base'];
				//}
			} else {
				$result = $woocommerce->cart->cart_contents[$cart_item_key]['data']->get_price();
			}
		}

		return apply_filters('woocommerce_dynamic_pricing_get_price_to_discount', $result, $filter_cart_item, $cart_item_key);
	}

/*	function is_cumulative($cart_item, $cart_item_key, $default = false) {
		global $woocommerce;
		//Check to make sure the item has not already been discounted by this module.  This could happen if update_totals is called more than once in the cart. 
		if (isset($woocommerce->cart->cart_contents[$cart_item_key]['discounts'])) {
			if (in_array($this->module_id, $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'])) {
				return false;
			} elseif (count(array_intersect(array('simple_category', 'simple_membership'), $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'])) > 0) {
				return true;
			}
		} else {
			return apply_filters('woocommerce_dynamic_pricing_is_cumulative', $default, $this->module_id, $cart_item, $cart_item_key);
		}
	}
*/
	function apply_cart_item_adjustment( $cart_item_key, $original_price, $adjusted_price, $module, $set_id ) {
		global $woocommerce;
		$adjusted_price = apply_filters( 'wc_dynamic_pricing_apply_cart_item_adjustment', $adjusted_price, $cart_item_key, $original_price, $module );

		if ( isset( $woocommerce->cart->cart_contents[$cart_item_key] ) ) {
			$_product = $woocommerce->cart->cart_contents[$cart_item_key]['data'];
			$display_price = get_option( 'woocommerce_tax_display_cart' ) == 'excl' ? $_product->get_price_excluding_tax() : $_product->get_price_including_tax();

			$woocommerce->cart->cart_contents[$cart_item_key]['data']->price = $adjusted_price;

			if ( !isset( $woocommerce->cart->cart_contents[$cart_item_key]['discounts'] ) ) {

				$discount_data = array(
				    'by' => array($module),
				    'set_id' => $set_id,
				    'price_base' => $original_price,
				    'display_price' => $display_price,
				    'price_adjusted' => $adjusted_price,
				    'applied_discounts' => array(array('by' => $module, 'set_id' => $set_id, 'price_base' => $original_price, 'price_adjusted' => $adjusted_price))
				);
				$woocommerce->cart->cart_contents[$cart_item_key]['discounts'] = $discount_data;
			} else {

				$existing = $woocommerce->cart->cart_contents[$cart_item_key]['discounts'];

				$discount_data = array(
				    'by' => $existing['by'],
				    'set_id' => $set_id,
				    'price_base' => $original_price,
				    'display_price' => $existing['display_price'],
				    'price_adjusted' => $adjusted_price
				);

				$woocommerce->cart->cart_contents[$cart_item_key]['discounts'] = $discount_data;

				$history = array('by' => $existing['by'], 'set_id' => $existing['set_id'], 'price_base' => $existing['price_base'], 'price_adjusted' => $existing['price_adjusted']);
				array_push( $woocommerce->cart->cart_contents[$cart_item_key]['discounts']['by'], $module );
				$woocommerce->cart->cart_contents[$cart_item_key]['discounts']['applied_discounts'][] = $history;
			}
		}

		do_action( 'woocommerce_dynamic_pricing_apply_cartitem_adjustment', $cart_item_key, $original_price, $adjusted_price, $module, $set_id );
	}



?>