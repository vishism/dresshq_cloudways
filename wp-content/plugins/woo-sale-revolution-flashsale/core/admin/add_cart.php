<?php
			$defaults = array('post_title'=>stripslashes($_POST['pw_name']), 'post_type'=>'flash_sale', 'post_content'=>'demo text', 'post_status'=>'publish');				
			
			if($post_id=wp_insert_post( $defaults ))
			{
				add_post_meta($post_id, 'pw_name', @$_POST['pw_name']);
				add_post_meta($post_id, 'pw_type', 'cart');
				add_post_meta($post_id, 'pw_flash_sale_image', @$_POST['pw_flash_sale_image']);
				add_post_meta($post_id, 'status','active');
				add_post_meta($post_id, 'pw_discount',@$_POST['pw_discount']);
				add_post_meta($post_id, 'pw_cart_roles',@$_POST['pw_cart_roles']);
				add_post_meta($post_id, 'pw_product',@$_POST['pw_product']);
				add_post_meta($post_id, 'pw_roles',@$_POST['pw_roles']);
				add_post_meta($post_id, 'pw_capabilities',@$_POST['pw_capabilities']);
				add_post_meta($post_id, 'pw_users',@$_POST['pw_users']);
				add_post_meta($post_id, 'pw_type_conditions',@$_POST['pw_type_conditions']);
				$pw_discount_qty=$matched_products=$except_product=$arr='';
				if(isset($_POST['pw_discount_qty']) && is_array($_POST['pw_discount_qty']))
				{
					$pw_discount_qty = array_filter(array_map('array_filter', @$_POST['pw_discount_qty'])); 
				}
				add_post_meta($post_id, 'pw_discount_qty', $pw_discount_qty);
				add_post_meta($post_id, 'pw_from', @$_POST['pw_from']);
				add_post_meta($post_id, 'pw_to', @$_POST['pw_to']);
			}


?>