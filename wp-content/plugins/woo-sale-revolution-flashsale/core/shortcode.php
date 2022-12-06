<?php
add_shortcode( 'flash_sale_product_rule_auto', 'pw_flashsale_shortcode_auto' );

function pw_flashsale_shortcode_auto( $atts, $content = null ) {
	$brands_attr = shortcode_atts( array(
		'rule'=>'',
		'show_countdown'=>'',
		'show_discunt'=>'',
		'countdown_style'=>'',
		'countdown_size'=>'',
		'text_colour'=>'#ffffff',
		'countdown_backcolour'=>'#333333',
		'description_area_backcolour'=>'#f5f5f5',
		'column'=>'fr_col_1_of_3',
	), $atts );
	
	$rule='';
	if($brands_attr['rule']!="")
		$rule=array($brands_attr['rule']);
	
	$query_meta_query=array('relation' => 'AND');
		
	$matched_products_rule = get_posts(
		array(
			'post_type' 	=> 'flash_sale',
			'numberposts' 	=> -1,
			'post_status' 	=> 'publish',
			'fields' 		=> 'ids',
			'post__in'=>$rule,
			'no_found_rows' => true,
			'orderby'	=>'modified',
		)
	);
	$matched_products=$ret="";
//	print_r($matched_products_rule);
	if(!is_array($matched_products_rule) || count($matched_products_rule)<=0)
		return;
	foreach($matched_products_rule as $p)
	{
		$status=get_post_meta($p,'status',true);
		$pw_from=strtotime(get_post_meta($p,'pw_from',true));
		$pw_to=get_post_meta($p,'pw_to',true);
		if($status=="active")
		{
			$blogtime = strtotime(current_time( 'mysql' ));
			
			if($blogtime<strtotime($pw_to) && $blogtime>$pw_from)
			{
				$pw_discount= get_post_meta($p,'pw_discount',true);
				$pw_type_discount= get_post_meta($p,'pw_type_discount',true);
				$pw_apply_to=get_post_meta($p,'pw_apply_to',true);
				$pw_product_category=get_post_meta($p,'pw_product_category',true);
				$pw_except_product_category=get_post_meta($p,'pw_except_product_category',true);
				$pw_product_tag=get_post_meta($p,'pw_product_tag',true);
				$pw_except_product_tag=get_post_meta($p,'pw_except_product_tag',true);
				$pw_product=get_post_meta($p,'pw_product',true);
				$pw_except_product=get_post_meta($p,'pw_except_product',true);
				$arr=$except_product='';
				if((is_array($pw_product_category) && count($pw_product_category)>0) || (is_array($pw_except_product) && count($pw_except_product)>0)|| (is_array($pw_except_product_category) && count($pw_except_product_category)>0) || (is_array($pw_product_tag) && count($pw_product_tag)>0) || (is_array($pw_except_product_tag) && count($pw_except_product_tag)>0)){
					$arr=array('relation' => 'AND');
					
				}
				
				if($pw_apply_to=="pw_except_product")
					$except_product=$pw_except_product;
				elseif($pw_apply_to=="pw_product_category")
				{
					if(is_array($pw_product_category) && count($pw_product_category)>0)
					{
						$arr[]=array(
									'taxonomy' => 'product_cat',
									'field'    => 'id',
									'terms'    => $pw_product_category,
								);
					}
				}
				elseif($pw_apply_to=="pw_except_product_category")
				{
					if(is_array($pw_except_product_category) && count($pw_except_product_category)>0)
					{
						$arr[]=array(
									'taxonomy' => 'product_cat',
									'field'    => 'id',
									'terms'    => $pw_except_product_category,
									'operator' => 'NOT IN',
								);			
					}
				}
				elseif($pw_apply_to=="pw_product_tag")
				{
					if(is_array($pw_product_tag) && count($pw_product_tag)>0)
					{
						$arr[]=array(
							'taxonomy' => 'product_tag',
							'field'    => 'id',
							'terms'    => $pw_product_tag,
						);
					}
				}
				elseif($pw_apply_to=="pw_except_product_tag")
				{
					add_post_meta($post_id, 'pw_except_product_tag', @$_POST['pw_except_product_tag']);
					if(is_array($pw_except_product_tag) && count($pw_except_product_tag)>0)
					{				
						$arr[]=array(
							'taxonomy' => 'product_tag',
							'field'    => 'id',
							'terms'    => $pw_except_product_tag,
							'operator' => 'NOT IN',
						);
						
					}
				}
				if($pw_apply_to=="pw_product")
					$matched_products =$pw_product;
				else
				{
					$matched_products = get_posts(
						array(
							'post_type' 	=> 'product',
							'numberposts' 	=> -1,
							'post_status' 	=> 'publish',
							'fields' 		=> 'ids',
							'post__not_in'		=>$except_product,
							'no_found_rows' => true,
							'tax_query' => $arr,
						)
					);
				}
				break;
			}
		}
	}
	if($matched_products=="" || !is_array($matched_products) || count($matched_products)<=0)
		return;	

	$ret.='<div class="fr_section fr_group">';
	foreach($matched_products as $r)
	{
		$rand_id=rand(0,1000);		
		$title=$result=$countdown="";$result="";
		$title=get_the_title( $r,true );
		$permalink=get_page_link($r);
		$base_price = get_post_meta( $r, '_regular_price',true);
		$image = get_the_post_thumbnail( $r, 'medium' );
		$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );
		if($pw_discount!="")
		{
			if ( $pw_type_discount=="percent" )
			{
				$max_discount = calculate_modifiera( $pw_discount, $base_price );
				$result = round( floatval( $base_price ) - ( floatval( $max_discount  )), (int) $num_decimals );
			}
			else
				$result=$base_price-$pw_discount;

			$result='<del>' .   woocommerce_price($base_price). '</del><ins> ' .  woocommerce_price($result). '</ins>';
		}
		if($brands_attr['show_countdown']=="yes" && $pw_discount!="" && $pw_to!="")
		{
			$id=rand(0,1000);
			$countdown ='
				<div class="fl-rule-coundown countdown-'.$rand_id.'	">
					<ul class="fl-'.$brands_attr['countdown_style'].' fl-'.$brands_attr['countdown_size'].' fl-countdown countdown_'.$id.'">
					  <li><span class="days">--</span><p class="days_text">Days</p></li>
						<li class="seperator">:</li>
						<li><span class="hours">--</span><p class="hours_text">'.__('Hours','pw_wc_flash_sale').'</p></li>
						<li class="seperator">:</li>
						<li><span class="minutes">--</span><p class="minutes_text">'.__('Minutes','pw_wc_flash_sale').'</p></li>
						<li class="seperator">:</li>
						<li><span class="seconds">--</span><p class="seconds_text">'.__('Seconds','pw_wc_flash_sale').'</p></li>
					</ul>
				</div>
				<script type="text/javascript">
					jQuery(".countdown_'.$id.'").countdown({
						date: "'.$pw_to.'",
						offset: -8,
						day: "Day",
						days: "Days"
					}, function () {
					//	alert("Done!");
					});
				</script>';
		}

		$ret.= '<div class="fr_col '.$brands_attr['column'].' col-'.$rand_id.'" >';							
			$ret.= '<a href="'.$permalink.'">'.$image.'</a>';
			$ret .='<div class="fs-itemdesc">';
				$ret.= '<h3><a href="'.$permalink.'">'.$title.'</a></h3>';
				if($result!='')
					$ret.= $result;
				else
					$ret.= woocommerce_price($base_price);
				$ret.= $countdown;
			$ret .='</div>';
		$ret.= '</div>';
	}
	$ret .='</div>';
	fl_top_product_grid_custom_style( $rand_id , $brands_attr['text_colour'] , $brands_attr['countdown_backcolour'] , $brands_attr['description_area_backcolour']);
	return $ret;
}

add_shortcode( 'flash_sale_product_rule', 'pw_flashsale_shortcode' );

function pw_flashsale_shortcode( $atts, $content = null ) {
		$brands_attr = shortcode_atts( array(
			'rule'=>'',			
			'count_items'=>'-1',
			'show_countdown'=>'yes',
			'show_discount'=>'yes',
			'countdown_style'=>'style1',
			'countdown_size'=>'medium',
			'text_colour'=>'#ffffff',
			'countdown_backcolour'=>'#333333',
			'countdown_area_backcolour'=>'#ffffff',
			'item_width'=>'300',
			'item_marrgin'=>'10',
			'slide_direction' => 'vertical',			
			'show_paginatin' => 'true',			
			'show_control' => 'true',			
			'item_per_view' => '1',			
			'item_per_slide' => '1',			
			'slide_speed' => '3000',			
			'auto_play' => 'true',			
			'description_area_backcolour' => '#f5f5f5',			
			
		), $atts );
		if(esc_attr($brands_attr['slide_direction']=="ver-carousel")  || esc_attr($brands_attr['slide_direction']=="hor-carousel"))
		{
			//	wp_enqueue_script('woob-carousel-script');
		}
		$ret="";
		$did=rand(0,1000);
		
		$cunt=$brands_attr['count_items'];
		if($brands_attr['count_items']=="")
			$cunt=-1;

		$product='';
		if($product!="")
			$product=$product;
		$rule='';
		if($brands_attr['rule']!="")
			$rule=array($brands_attr['rule']);
		$args=array(
			'post_type'=>'flash_sale',
			'posts_per_page'=>$cunt,
			'post__in'=>$rule,
			'orderby'	=>'modified',
		);
		
		$loop = new WP_Query( $args );
		$arr="";
		if ( $loop->have_posts() ) 
		{							
			$show_countdown="";
			$flag=false;
			while ( $loop->have_posts() ) : 
				$loop->the_post();
				$blogtime = current_time( 'mysql' );
				$pw_to=get_post_meta(get_the_ID(),'pw_to',true);
				$pw_from=get_post_meta(get_the_ID(),'pw_from',true);
				$pw_type_discount=get_post_meta(get_the_ID(),'pw_type_discount',true);
				$res=strtotime($pw_to)- strtotime($blogtime);
				$rand_id = rand(0,1000);
				if (trim($brands_attr['show_discount']) == "yes" || trim($brands_attr['show_countdown']) == "yes")
					$ret .= '<div class="fl-countdown-cnt countdown-'.$rand_id.'">';

				if(trim($brands_attr['show_discount']) == "yes" )
					$ret .= '<div class="fl-flashsale-discount">' . get_post_meta(get_the_ID(),'pw_discount',true).'</div>';

				if(trim($brands_attr['show_countdown']) == "yes" )
				{
					if(strtotime($blogtime)<strtotime($pw_to))
					{
						$id=rand(0,1000);			
						$ret.='<ul class="fl-'.$brands_attr['countdown_style'].' fl-'.$brands_attr['countdown_size'].' fl-countdown countdown_'.$id.'">
								  <li><span class="days">00</span><p class="days_text">'.__('Days','pw_wc_flash_sale').'</p></li>
									<li class="seperator">:</li>
									<li><span class="hours">00</span><p class="hours_text">'.__('Hours','pw_wc_flash_sale').'</p></li>
									<li class="seperator">:</li>
									<li><span class="minutes">00</span><p class="minutes_text">'.__('Minutes','pw_wc_flash_sale').'</p></li>
									<li class="seperator">:</li>
									<li><span class="seconds">00</span><p class="seconds_text">'.__('Seconds','pw_wc_flash_sale').'</p></li>
								</ul>
							<script type="text/javascript">
								jQuery(".countdown_'.$id.'").countdown({
									date: "'.$pw_to.'",
									offset: -8,
									day: "Day",
									days: "Days"
								}, function () {
								//	alert("Done!");
								});
							</script>';
					}
					
				}	
				if (trim($brands_attr['show_discount']) == "yes" || trim($brands_attr['show_countdown']) == "yes")
					$ret .= '</div>';
				
				
				if(strtotime($blogtime)<strtotime($pw_to) && strtotime($blogtime)>strtotime($pw_from)){
					$pw_type= get_post_meta(get_the_ID(),'pw_type',true);
					if($pw_type=="flashsale")
						$flag=true;
				}
					
				$arr= get_post_meta(get_the_ID(),'pw_array',true);
				
				echo $show_countdown;
				
				
				$ret .= '<ul id="sidecar_'.$rand_id.'" class="fs-bxslider fs-single-car  fs-carousel-layout car-'.$rand_id.'">';
		
				foreach($arr as $a)
				{
					$title= get_the_title( $a );
					$price = get_post_meta( $a, '_regular_price',true);							
					$size = 'shop_catalog';
					$image = get_the_post_thumbnail( $a, 'medium' );
					$result = "";
					$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );
					if($flag==true)
					{
						$pw_discount=get_post_meta(get_the_ID(),'pw_discount',true);
						if ( $pw_type_discount=="percent" )
						{
							$max_discount = calculate_modifiera( $pw_discount, $price );
							$result = round( floatval( $price ) - ( floatval( $max_discount  )), (int) $num_decimals );
						}
						else
							$result=$price-$pw_discount;
					}
											
					$ret.= '<li >';							
						$ret.= '<a href="'.get_page_link($a).'">'.$image.'</a>';
						$ret .='<div class="fs-itemdesc">';
							$ret.= '<h3><a href="'.get_page_link($a).'">'.$title.'</a></h3>';
							if($result!="")
								$ret.= '<span><del>'.woocommerce_price($price).'</del> '.woocommerce_price($result).'</span>';
							else
								$ret.=woocommerce_price($price);
						$ret .='</div>';
					$ret.= '</li>';
				}//end foreach
				$ret .='</ul>';
			endwhile;
		}
		
		if ( ($brands_attr['slide_direction']=='vertical') || ($brands_attr['slide_direction']=='horizontal'))	{
			$item_width=$brands_attr['item_width'];
			if($item_width=="")
				$item_width="1000";
			$ret .= "<script type='text/javascript'>
					/* <![CDATA[  */
					jQuery(document).ready(function() {
						sidecar_" . $rand_id ." =
						 jQuery('#sidecar_" . $rand_id ."').bxSlider({ 
							  
							  mode : '".($brands_attr['slide_direction']=='vertical' ? 'vertical' : 'horizontal' )."' ,
							  touchEnabled : true ,
							  adaptiveHeight : true ,
							  slideMargin : ".($brands_attr['item_marrgin']!='' ? $brands_attr['item_marrgin'] : '10').", 
							  wrapperClass : 'fs-bx-wrapper fs-sidebar-car ' ,
							  infiniteLoop: true,
							  pager: ".$brands_attr['show_paginatin'] .",
							  controls: ".$brands_attr['show_control'] .",
							  ".($brands_attr['slide_direction']=='horizontal' ? 'slideWidth:'.$item_width.',' : 'slideWidth:5000,' )."
							  minSlides:".($brands_attr['item_per_view']!="" ? $brands_attr['item_per_view'] : "1").",
							  maxSlides: ".($brands_attr['item_per_view']!="" ? $brands_attr['item_per_view'] : "1").",
							  moveSlides: ".($brands_attr['item_per_slide']!='' ? $brands_attr['item_per_slide'] : '1').",
							  auto: true,
							  pause : ".$brands_attr['slide_speed']."	,
							  autoHover  : true , 
							  autoStart: ".$brands_attr['auto_play']."
						 });";
						 
						if ($brands_attr['auto_play']=='true'){
							$ret.="
							 jQuery('.fs-bx-wrapper .fs-bx-controls-direction a').click(function(){
								  sidecar_" . $rand_id .".startAuto();
							 });";
						}
						$ret.="});	
					/* ]]> */
				</script>";
		}
		
		fl_product_rule_custom_style( $rand_id , $brands_attr['text_colour'] , $brands_attr['countdown_backcolour'] , $brands_attr['countdown_area_backcolour'] , $brands_attr['description_area_backcolour']);
			
			
		
		return $ret;
	}	

add_shortcode( 'flash_sale_top_products_carosel', 'pw_flashsalerule_product_shortcode' );
function pw_flashsalerule_product_shortcode( $atts, $content = null )
{
	$brands_attr = shortcode_atts( array(
		'products' => '',
		'show_discunt'=>'',
		'show_countdown'=>'',
		'countdown_style'=>'',
		'countdown_size'=>'',
		'text_colour'=>'#ffffff',
		'countdown_backcolour'=>'#333333',
		'item_width'=>'',
		'item_marrgin'=>'0',
		'slide_direction'=>'',
		'show_pagination'=>'',
		'show_control'=>'',
		'item_per_view'=>'',
		'item_per_slide'=>'',
		'slide_speed'=>'',
		'auto_play'=>'',
		'description_area_backcolour'=>'',
	), $atts );
	$ret ='';
	$blogtime="";
	$products=explode(",",$brands_attr['products']);
	
	$query_meta_query=array('relation' => 'AND');
	$query_meta_query[] = array(
		'key' =>'pw_type',
		'value' => "flashsale",
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
	$rand_id= rand(0,1000);
	$ret .= '<ul id="sidecar_'.$rand_id.'" class="fs-bxslider fs-single-car  fs-carousel-layout car-'.$rand_id.'">';	
	$i=1;
	foreach($products as $p)
	{
		$pw_discount=$pw_to=$title=$result=$countdown=$result="";
		$base_price = get_post_meta( $p, '_regular_price',true);
		$image = get_the_post_thumbnail( $p, 'medium' );
		foreach($matched_products as $r)
		{
			$arr="";
			$pw_to=get_post_meta($r,'pw_to',true);				
			$pw_from=get_post_meta($r,'pw_from',true);				
			$pw_type_discount=get_post_meta($r,'pw_type_discount',true);				
			$blogtime = current_time( 'mysql' );
				if(strtotime($blogtime)<strtotime($pw_to) && strtotime($blogtime)>strtotime($pw_from))
				{
					$arr= get_post_meta($r,'pw_array',true);
					
					if (is_array($arr) && in_array($p, $arr))
					{
						$pw_discount= get_post_meta($r,'pw_discount',true);
						
						$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );
						if($pw_discount!="")
						{
							if ( $pw_type_discount=="percent" )
							{
								$max_discount = calculate_modifiera( $pw_discount, $base_price );
								$result = round( floatval( $base_price ) - ( floatval( $max_discount  )), (int) $num_decimals );
							}
							else
								$result=$base_price-$pw_discount;
							$result='<del>' . woocommerce_price($base_price). '</del><ins> ' .  woocommerce_price($result). '</ins>';							
						}
						break;
					}
				
				}
		}
		$title=get_the_title( $p,true );
		$permalink=get_page_link($p);
		if($brands_attr['show_countdown']=="yes" && $pw_discount!="")
		{
			$id=rand(0,1000);
			$countdown ='
				<div class="fl-rule-coundown countdown-'.$rand_id.'">
					<ul class="fl-'.$brands_attr['countdown_style'].' fl-'.$brands_attr['countdown_size'].' fl-countdown countdown_'.$id.'">
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
						date: "'.$pw_to.'",
						offset: -8,
						day: "Day",
						days: "Days"
					}, function () {
					});
				</script>';
		}
		
		$ret.= '<li >';							
			$ret.= '<a href="'.$permalink.'">'.$image.'</a>';
			$ret .='<div class="fs-itemdesc">';
				$ret.= '<h3><a href="'.$permalink.'">'.$title.'</a></h3>';
				if($result!="")
					$ret.= $result;
				else
					$ret.= woocommerce_price($base_price);
				$ret.= $countdown;
			$ret .='</div>';
		$ret.= '</li>';
				
	}
	$ret.='</ul>';
	$item_width=$brands_attr['item_width'];
	if($item_width=="")
		$item_width="1000";	
	$ret.= "<script type='text/javascript'>
			jQuery(document).ready(function() {
				sidecar_" . $rand_id ." =
				 jQuery('#sidecar_" . $rand_id ."').bxSlider({ 
					  mode : '".($brands_attr['slide_direction']=='vertical' ? 'vertical' : 'horizontal' )."' ,
					  touchEnabled : true ,
					  adaptiveHeight : true ,
					  slideMargin : ".($brands_attr['item_marrgin']!='' ? $brands_attr['item_marrgin'] : '10').", 
					  wrapperClass : 'fs-bx-wrapper fs-sidebar-car ' ,
					  infiniteLoop: true,
					  pager: ".$brands_attr['show_pagination'] .",
					  controls: ".$brands_attr['show_control'] .",
					  ".($brands_attr['slide_direction']=='horizontal' ? 'slideWidth:'.$item_width.',' : 'slideWidth:5000,' )."
					  minSlides:".($brands_attr['item_per_view'] !='' ? $brands_attr['item_per_view'] : '1') .",
					  maxSlides: ".($brands_attr['item_per_view']!='' ? $brands_attr['item_per_view'] : '1').",
					  moveSlides: ".($brands_attr['item_per_slide'] !='' ? $brands_attr['item_per_slide'] : '1').",
					  auto: true,
					  pause : ".($brands_attr['slide_speed']!='' ? $brands_attr['slide_speed'] : '2000')."	,
					  autoHover  : true , 
					  autoStart: ".$brands_attr['auto_play']."
				 });";
				 
				if ($brands_attr['auto_play']=='true'){
					$ret.="
					 jQuery('.fs-bx-wrapper .fs-bx-controls-direction a').click(function(){
						  sidecar_" . $rand_id .".startAuto();
					 });";
				}
				$ret.="
				});	
		</script>";
	fl_top_product_carousel_custom_style( $rand_id , $brands_attr['text_colour'] , $brands_attr['countdown_backcolour'] , $brands_attr['description_area_backcolour']);	
	return $ret;
}

add_shortcode( 'flash_sale_rule_list', 'pw_flashsalerule_shortcode' );
function pw_flashsalerule_shortcode( $atts, $content = null ) {
		$brands_attr = shortcode_atts( array(
			'rule'=>'',
			'show_discunt'=>'',
			'show_countdown' => '',
			'countdown_style' => '',
			'countdown_size' => '',
			'text_colour' => '#ffffff',
			'countdown_backcolour' => '#333333',
			'structure' => '',
			'overlay_backcolour' => '#ffffff',

		), $atts );
		$ret ='';
		//$rule="";
		if($brands_attr['rule']!="")
			$rule=explode(",",$brands_attr['rule']);

		$args=array(
			'post_type'=>'flash_sale',
			'post__in'=>$rule,
			'orderby'	=>'modified',
		);
		$loop = new WP_Query( $args );
		$rand_id = rand(1,1000);
		$ret .= '<div class="fl-rule-wrapper">';
		while ( $loop->have_posts() ) : 
			$loop->the_post();
			$pw_flash_sale_image=get_post_meta(get_the_ID(),'pw_flash_sale_image',true);
			
			$ret .='<div class="fl-rulecnt '.$brands_attr['structure'].'-col rulelist-'.$rand_id.'">
						<a class="fl-imglink" href="'.get_page_link(get_the_ID()).'">'.wp_get_attachment_image( $pw_flash_sale_image, 'full' ).'</a>';
				$ret .='<div class="fl-rulcnt-overlay">';		
					
					$ret .='<h3><a href="'.get_page_link(get_the_ID()).'">'.get_the_title().'</a></h3>';	
					
					if($brands_attr['show_discunt']=="yes")
					{
						$ret .= '<div class="fl-rulcnt-discount">' . get_post_meta(get_the_ID(),'pw_discount',true).'</div>';
					}	
					
					if($brands_attr['show_countdown']=="yes")
					{
						$pw_to=get_post_meta(get_the_ID(),'pw_to',true);
						$id=rand(0,1000);
						$ret .='
							<div class="fl-rule-coundown">
								<ul class="fl-'.$brands_attr['countdown_style'].' fl-'.$brands_attr['countdown_size'].' fl-countdown countdown_'.$id.'">
								  <li><span class="days">00</span><p class="days_text">'.__('Days','pw_wc_flash_sale').'</p></li>
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
									date: "'.$pw_to.'",
									offset: -8,
									day: "Day",
									days: "Days"
								}, function () {
								//	alert("Done!");
								});
							</script>';							
					}
					
				$ret .='</div>';		
			$ret .=	'</div>';
			
			//echo ;
		endwhile;	
		$ret.='</div>';
	
			
					
		fl_rule_list_custom_style( $rand_id , $brands_attr['text_colour'] , $brands_attr['countdown_backcolour'] , $brands_attr['overlay_backcolour']);
		return $ret;
		
}


add_shortcode( 'flash_sale_rule_slider', 'pw_flashsalerule_slider_shortcode' );

function pw_flashsalerule_slider_shortcode( $atts, $content = null ) {
		$brands_attr = shortcode_atts( array(
			'rule'=>'',		
			'show_discunt'=>'yes',			
			'show_countdown' => 'no',
			'countdown_style'=>'style1',
			'countdown_size'=>'style1',
			'text_colour'=>'style1',
			'countdown_backcolour'=>'',
			'slider_mod'=>'',
			'pagination'=>'',
			'show_control'=>'',
			'slider_speed'=>'2000',
			'auto_play'=>'',
			'overlay_backcolour'=>'#ffffff',
			'carousel_item_per_view'=>'1',
			'slider_width'=>'2000',
		), $atts );
		$ret ='';
		
		if($brands_attr['rule']!="")
			$rule=explode(",",$brands_attr['rule']);

		$args=array(
			'post_type'=>'flash_sale',
			'post__in'=>$rule,
			'orderby'	=>'modified',
		);
		$loop = new WP_Query( $args );
		
		$rand_id = rand(0,1000);
		$ret .= '<ul id="sidecar_'.$rand_id.'" class="fs-bxslider fs-single-car  fs-carousel-layout ruleslider-'.$rand_id.'">';
		
		while ( $loop->have_posts() ) : 
			$loop->the_post();
			
			$pw_flash_sale_image=get_post_meta(get_the_ID(),'pw_flash_sale_image',true);
			
			$ret .='<li>
					<div class="fl-rulecnt">
						<a class="fl-imglink" href="'.get_page_link(get_the_ID()).'">'.wp_get_attachment_image( $pw_flash_sale_image, 'full' ).'</a>';
				$ret .='<div class="fl-rulcnt-overlay">';		
					
					$ret .='<h3><a href="'.get_page_link(get_the_ID()).'">'.get_the_title().'</a></h3>';	
					
					if($brands_attr['show_discunt']=="yes")
					{
						$ret .= '<div class="fl-rulcnt-discount">' . get_post_meta(get_the_ID(),'pw_discount',true).'</div>';
					}	
					
					if($brands_attr['show_countdown']=="yes")
					{
						$pw_to=get_post_meta(get_the_ID(),'pw_to',true);
						$id=rand(0,1000);
						$ret .='
							<div class="fl-rule-coundown">
								<ul class="fl-'.$brands_attr['countdown_style'].' fl-'.$brands_attr['countdown_size'].' fl-countdown countdown_'.$id.'">
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
									date: "'.$pw_to.'",
									offset: -8,
									day: "Day",
									days: "Days"
								}, function () {
								//	alert("Done!");
								});
							</script>';							
					}
					
				$ret .='</div>';		
			$ret .=	'</div>
					</li>';
		endwhile;			
	  $ret.= '</ul>';	
	$slider_width=$brands_attr['slider_width'];
	if($slider_width=="")
		$slider_width="1000";	
	  $ret .= "<script type='text/javascript'>
			jQuery(document).ready(function() {
				sidecar_" . $rand_id ." =
				 jQuery('#sidecar_" . $rand_id ."').bxSlider({ 
					  mode : '".($brands_attr['slider_mod']=='fade' ? 'fade' : 'horizontal' )."',
					  touchEnabled : true ,
					  adaptiveHeight : true ,
					  slideMargin : 0 , 
					  wrapperClass : 'fs-bx-wrapper fs-sidebar-car ' ,
					  infiniteLoop: true,
					  pager: ".$brands_attr['pagination'].",
					  controls: ".$brands_attr['show_control'].",
					  slideWidth:".$slider_width.",
					  minSlides:1,
					  maxSlides: 1,
					  moveSlides: 1,
					  auto:  ".$brands_attr['auto_play'].",
					  pause :  ".($brands_attr['slider_speed']!="" ? $brands_attr['slider_speed'] : '2000' )."	,
					  autoHover  : true , 
					  autoStart: ".$brands_attr['auto_play']."
				 });
				 ";
				 if ($brands_attr['auto_play']=="true"){
					 $ret .="
						 jQuery('.fs-bx-wrapper .fs-bx-controls-direction a').click(function(){
							  sidecar_" . $rand_id .".startAuto();
						 });
					 ";
				 }
			$ret.="
			});	
		</script>";
		
		fl_rule_slider_custom_style( $rand_id , $brands_attr['text_colour'] , $brands_attr['countdown_backcolour'] , $brands_attr['overlay_backcolour']);
		return $ret;
}

add_shortcode( 'flash_sale_top_products_grid', 'pw_flashsalerule_product_shortcode_grid' );
function pw_flashsalerule_product_shortcode_grid( $atts, $content = null )
{
	$brands_attr = shortcode_atts( array(
		'products' => '',
		'show_countdown'=>'',
		'show_discunt'=>'',
		'countdown_style'=>'',
		'countdown_size'=>'',
		'text_colour'=>'#ffffff',
		'countdown_backcolour'=>'#333333',
		'description_area_backcolour'=>'#f5f5f5',
		'column'=>'fr_col_1_of_3',
	), $atts );
	$ret ='';
	$blogtime="";
	$products=explode(",",$brands_attr['products']);
	$matched_products = get_posts(
		array(
			'post_type' 	=> 'flash_sale',
			'numberposts' 	=> -1,
			'post_status' 	=> 'publish',
			'fields' 		=> 'ids',
			'orderby'	=>'modified',
			'no_found_rows' => true,
		)
	);
	$ret.='<div class="fr_section fr_group">';
	$rand_id=rand(0,1000);
	foreach($products as $p)
	{
		$pw_discount=$pw_to=$title=$result=$countdown="";
		$base_price = get_post_meta( $p, '_regular_price',true);
		$result="";
		$image = get_the_post_thumbnail( $p, 'medium' );
		foreach($matched_products as $r)
		{
			$arr="";
			$pw_to=get_post_meta($r,'pw_to',true);				
			$pw_from=get_post_meta($r,'pw_from',true);				
			$blogtime = current_time( 'mysql' );
			$pw_type=get_post_meta($r,'pw_type',true);
			$pw_type_discount=get_post_meta($r,'pw_type_discount',true);
			if(strtotime($blogtime)<strtotime($pw_to) && strtotime($blogtime)>strtotime($pw_from))
			{
				$arr= get_post_meta($r,'pw_array',true);
				if($pw_type=="flashsale")
				{
					if (is_array($arr) && in_array($p, $arr))
					{
						$pw_discount= get_post_meta($r,'pw_discount',true);
						
						$num_decimals = apply_filters( 'woocommerce_wc_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );
						if($pw_discount!="")
						{
							if ( $pw_type_discount=="percent" )
							{
								$max_discount = calculate_modifiera( $pw_discount, $base_price );
								$result = round( floatval( $base_price ) - ( floatval( $max_discount  )), (int) $num_decimals );
							}
							else
								$result=$base_price-$pw_discount;
						$result='<del>' .   woocommerce_price($base_price). '</del><ins> ' .  woocommerce_price($result). '</ins>';							
						}
						break;
					}
				}
			}
		}
		$title=get_the_title( $p,true );
		$permalink=get_page_link($p);
		if($brands_attr['show_countdown']=="yes" && $pw_discount!="" && $pw_to!="")
		{
			$id=rand(0,1000);
			$countdown ='
				<div class="fl-rule-coundown countdown-'.$rand_id.'	">
					<ul class="fl-'.$brands_attr['countdown_style'].' fl-'.$brands_attr['countdown_size'].' fl-countdown countdown_'.$id.'">
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
						date: "'.$pw_to.'",
						offset: -8,
						day: "Day",
						days: "Days"
					}, function () {
					//	alert("Done!");
					});
				</script>';
		}
		
		$ret.= '<div class="fr_col '.$brands_attr['column'].' col-'.$rand_id.'" >';							
			$ret.= '<a href="'.$permalink.'">'.$image.'</a>';
			$ret .='<div class="fs-itemdesc">';
				$ret.= '<h3><a href="'.$permalink.'">'.$title.'</a></h3>';
				if($result!='')
					$ret.= $result;
				else
					$ret.= woocommerce_price($base_price);
				$ret.= $countdown;
			$ret .='</div>';
		$ret.= '</div>';
		
				
	}
	$ret .='</div>';
	fl_top_product_grid_custom_style( $rand_id , $brands_attr['text_colour'] , $brands_attr['countdown_backcolour'] , $brands_attr['description_area_backcolour']);
	return $ret;
	
}


?>