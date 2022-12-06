<?php
			$post_id=stripslashes(@$_GET['pw_id']);	
			wp_update_post( array(
			  'ID'           => @$_GET['pw_id'],
			  'post_title' => @$_POST['pw_name']
			));
			update_post_meta($post_id, 'pw_name', @$_POST['pw_name']);
			update_post_meta($post_id, 'pw_type', @$_POST['pw_type']);
			update_post_meta($post_id, 'pw_flash_sale_image', @$_POST['pw_flash_sale_image']);
			update_post_meta($post_id, 'pw_apply_to', @$_POST['pw_apply_to']);
			update_post_meta($post_id, 'pw_discount', @$_POST['pw_discount']);
			update_post_meta($post_id, 'amount_to_purchase', @$_POST['amount_to_purchase']);
			update_post_meta($post_id, 'amount_to_adjust', @$_POST['amount_to_adjust']);
			update_post_meta($post_id, 'adjustment_type', @$_POST['adjustment_type']);
			update_post_meta($post_id, 'pw_type_discount', @$_POST['pw_type_discount']);
			update_post_meta($post_id, 'adjustment_value', @$_POST['adjustment_value']);
			update_post_meta($post_id, 'pw_roles', @$_POST['pw_roles']);
			update_post_meta($post_id, 'pw_capabilities', @$_POST['pw_capabilities']);
			update_post_meta($post_id, 'pw_cart_roles', @$_POST['pw_cart_roles']);
			update_post_meta($post_id, 'pw_users', @$_POST['pw_users']);
			update_post_meta($post_id, 'pw_matched', @$_POST['pw_matched']);
			update_post_meta($post_id, 'products_to_adjust', @$_POST['products_to_adjust']);
			update_post_meta($post_id, 'pw_products_to_adjust', @$_POST['pw_products_to_adjust']);
			update_post_meta($post_id, 'pw_products_to_adjust_products', @$_POST['pw_products_to_adjust_products']);
			update_post_meta($post_id, 'pw_products_to_adjust_category', @$_POST['pw_products_to_adjust_category']);
			update_post_meta($post_id, 'quantity_base', @$_POST['quantity_base']);

  
	/*		$p=wp_update_post(
				array (
					'ID'=> 579,
					'post_content'=> 'a'
					)
			);
			*/
			$pw_discount_qty=$matched_products=$except_product=$arr="";
			if(isset($_POST['pw_discount_qty']) && is_array($_POST['pw_discount_qty']))
			{
				$pw_discount_qty = array_filter(array_map('array_filter', @$_POST['pw_discount_qty'])); 
			}
			
			update_post_meta($post_id, 'pw_discount_qty', @$pw_discount_qty);
			update_post_meta($post_id, 'pw_from', @$_POST['pw_from']);
			update_post_meta($post_id, 'pw_to', @$_POST['pw_to']);
				// For Create Array
				$pw_apply_to=(get_post_meta($post_id,'pw_apply_to',true)==""?array():get_post_meta($post_id,'pw_apply_to',true));
				if((is_array(@$_POST['pw_product_category']) && count(@$_POST['pw_product_category'])>0) || (is_array(@$_POST['pw_except_product']) && count(@$_POST['pw_except_product'])>0)|| (is_array(@$_POST['pw_except_product_category']) && count(@$_POST['pw_except_product_category'])>0) || (is_array(@$_POST['pw_product_tag']) && count(@$_POST['pw_product_tag'])>0) || (is_array(@$_POST['pw_except_product_tag']) && count(@$_POST['pw_except_product_tag'])>0))
				{
						$arr=array('relation' => 'AND');
				}
				if($pw_apply_to=="pw_except_product")
				{
					$except_product=@$_POST['pw_except_product'];
					update_post_meta($post_id, 'pw_except_product', @$_POST['pw_except_product']);
					update_post_meta($post_id, 'pw_except_product_category', '');
					update_post_meta($post_id, 'pw_product_tag', '');
					update_post_meta($post_id, 'pw_product_category', '');
					update_post_meta($post_id, 'pw_except_product_tag', '');
					update_post_meta($post_id, 'pw_product', '');					
				}
				elseif($pw_apply_to=="pw_product_category")
				{
					update_post_meta($post_id, 'pw_product_category', @$_POST['pw_product_category']);
					update_post_meta($post_id, 'pw_except_product', '');
					update_post_meta($post_id, 'pw_except_product_category', '');
					update_post_meta($post_id, 'pw_product_tag', '');
					update_post_meta($post_id, 'pw_except_product_tag', '');
					update_post_meta($post_id, 'pw_product', '');
					if(is_array(@$_POST['pw_product_category']) && count(@$_POST['pw_product_category'])>0)
					{
						$arr[]=array(
									'taxonomy' => 'product_cat',
									'field'    => 'id',
									'terms'    => @$_POST['pw_product_category'],
								);
					}
				}
				elseif($pw_apply_to=="pw_except_product_category")
				{
					update_post_meta($post_id, 'pw_except_product_category', @$_POST['pw_except_product_category']);
					update_post_meta($post_id, 'pw_except_product', '');
					update_post_meta($post_id, 'pw_product_tag', '');
					update_post_meta($post_id, 'pw_product_category', '');
					update_post_meta($post_id, 'pw_except_product_tag', '');
					update_post_meta($post_id, 'pw_product', '');					
					if(is_array(@$_POST['pw_except_product_category']) && count(@$_POST['pw_except_product_category'])>0)
					{
						$arr[]=array(
									'taxonomy' => 'product_cat',
									'field'    => 'id',
									'terms'    => @$_POST['pw_except_product_category'],
									'operator' => 'NOT IN',
								);
					}
				}
				elseif($pw_apply_to=="pw_product_tag")
				{
					update_post_meta($post_id, 'pw_except_product', '');
					update_post_meta($post_id, 'pw_except_product_category', '');
					update_post_meta($post_id, 'pw_product_category', '');
					update_post_meta($post_id, 'pw_except_product_tag', '');
					update_post_meta($post_id, 'pw_product', '');
					update_post_meta($post_id, 'pw_product_tag', @$_POST['pw_product_tag']);
					if(is_array(@$_POST['pw_product_tag']) && count(@$_POST['pw_product_tag'])>0)
					{
						$arr[]=array(
							'taxonomy' => 'product_tag',
							'field'    => 'id',
							'terms'    => @$_POST['pw_product_tag'],
						);
					}
				}
				elseif($pw_apply_to=="pw_except_product_tag")
				{
					update_post_meta($post_id, 'pw_except_product_tag', @$_POST['pw_except_product_tag']);
					update_post_meta($post_id, 'pw_except_product', '');
					update_post_meta($post_id, 'pw_except_product_category', '');
					update_post_meta($post_id, 'pw_product_tag', '');
					update_post_meta($post_id, 'pw_product_category', '');
					update_post_meta($post_id, 'pw_product', '');					
					if(is_array(@$_POST['pw_except_product_tag']) && count(@$_POST['pw_except_product_tag'])>0)
					{				
						$arr[]=array(
							'taxonomy' => 'product_tag',
							'field'    => 'id',
							'terms'    => @$_POST['pw_except_product_tag'],
							'operator' => 'NOT IN',
						);
					}
				}
				if($pw_apply_to=="pw_product")
				{
					update_post_meta($post_id, 'pw_product', @$_POST['pw_product']);
					update_post_meta($post_id, 'pw_except_product', '');
					update_post_meta($post_id, 'pw_except_product_category', '');
					update_post_meta($post_id, 'pw_product_tag', '');
					update_post_meta($post_id, 'pw_product_category', '');
					update_post_meta($post_id, 'pw_except_product_tag', '');
//					foreach(@$_POST['pw_product'] as $pr)			
					$matched_products=@$_POST['pw_product'];
				}				
				else
				{
					$matched_products = get_posts(
						array(
							'post_type' 	=> 'product',
							'numberposts' 	=> -1,
							'post_status' 	=> 'publish',
							'fields' 		=> 'ids',
						//	'post__in'		=> $pw_product,
							'post__not_in'		=>$except_product,
							'no_found_rows' => true,
							'tax_query' => $arr,
						)
					);						
				}	
				update_post_meta($post_id, 'pw_array', $matched_products);		


?>