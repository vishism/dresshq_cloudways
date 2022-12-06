<?php
			$post_id=stripslashes(@$_GET['pw_id']);	
			wp_update_post( array(
			  'ID'           => @$_GET['pw_id'],
			  'post_title' => @$_POST['pw_name']
			));
			update_post_meta($post_id, 'pw_name', @$_POST['pw_name']);
			update_post_meta($post_id, 'pw_type', 'cart');
			update_post_meta($post_id, 'pw_flash_sale_image', @$_POST['pw_flash_sale_image']);
			update_post_meta($post_id, 'pw_cart_roles', @$_POST['pw_cart_roles']);
			update_post_meta($post_id, 'pw_roles', @$_POST['pw_roles']);
			update_post_meta($post_id, 'pw_capabilities', @$_POST['pw_capabilities']);
			update_post_meta($post_id, 'pw_users', @$_POST['pw_users']);
			update_post_meta($post_id, 'pw_type_conditions', @$_POST['pw_type_conditions']);
			update_post_meta($post_id, 'pw_product', @$_POST['pw_product']);
			update_post_meta($post_id, 'pw_discount', @$_POST['pw_discount']);
  
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


?>