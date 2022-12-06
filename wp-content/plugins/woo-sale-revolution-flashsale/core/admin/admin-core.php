<?php
global $wpdb;
if(@$_GET['tab']=="cart Discounts")
{
	if(@$_GET['pw_action_type']=="add" || @$_GET['pw_action_type']=="edit" || @$_GET['pw_action_type']=="delete" || @$_GET['pw_action_type']=="status")
	{
		$pw_name=$pw_flash_sale_image=$pw_flashsale_image_id=$pw_product_category=$pw_type_conditions=$pw_users=$pw_cart_roles=$pw_capabilities=$pw_roles=$pw_except_product_category=$pw_product_tag=$pw_except_product_tag=$pw_product=$pw_except_product=$pw_discount=$pw_from=$pw_to="";
		if(@$_POST['pw_action_type']=='add' || @$_POST['pw_action_type']=='' && isset($_POST['pw_id']))
		{
			include_once (PW_flash_sale_URL.'/core/admin/add_cart.php') ;
			?>
			<script type="text/javascript">
				window.location="<?php echo admin_url( 'admin.php?page=rule_list&tab=cart Discounts');?>";
			</script>';	
			<?php
		}
		else if(@$_POST['pw_action_type']=='edit' && isset($_POST['pw_name']))
		{
			include_once (PW_flash_sale_URL.'/core/admin/edit_cart.php') ;
		}
		else if(@$_GET['pw_action_type']=='delete' && isset($_GET['pw_id']))
		{
			wp_delete_post($_GET['pw_id']);
			?>
			<script type="text/javascript">
				window.location="<?php echo admin_url( 'admin.php?page=rule_list&tab=cart Discounts');?>";
			</script>';	
			<?php
		//	header('Location:'.admin_url( 'admin.php?page=rule_list'));
		}
		else if(@$_GET['pw_action_type']=='status' && isset($_GET['pw_id']))
		{
			update_post_meta($_GET['pw_id'], 'status', @$_GET['status_type']);
			?>
			<script type="text/javascript">
				window.location="<?php echo admin_url( 'admin.php?page=rule_list&tab=cart Discounts');?>";
			</script>';	
			<?php
		//	header('Location:'.admin_url( 'admin.php?page=rule_list'));
		}		
		$pw_action_type='add';
		if(@$_GET['pw_action_type']=="edit"){
			$pw_action_type='edit';
			if(isset($_GET['pw_id']))
			{
				$pw_name=get_post_meta($_GET['pw_id'],'pw_name',true);
				$pw_users=get_post_meta($_GET['pw_id'],'pw_users',true);
				$pw_cart_roles=get_post_meta($_GET['pw_id'],'pw_cart_roles',true);
				$pw_type_conditions=get_post_meta($_GET['pw_id'],'pw_type_conditions',true);
				$pw_capabilities=get_post_meta($_GET['pw_id'],'pw_capabilities',true);
				$pw_type=get_post_meta($_GET['pw_id'],'pw_type',true);
				$thumbnail_id = get_post_meta($_GET['pw_id'], 'pw_flash_sale_image', true);
				$pw_roles = get_post_meta($_GET['pw_id'], 'pw_roles', true);
				$pw_flashsale_image_id =$thumbnail_id;
				if ($thumbnail_id)
					$pw_flash_sale_image = wp_get_attachment_thumb_url( $thumbnail_id );
				$pw_flash_sale_image = str_replace( ' ', '%20', $pw_flash_sale_image );
				
				$pw_product_category=get_post_meta($_GET['pw_id'],'pw_product_category',true);
				$pw_except_product_category=get_post_meta($_GET['pw_id'],'pw_except_product_category',true);
				$pw_apply_to=get_post_meta($_GET['pw_id'],'pw_apply_to',true);
				$pw_product_tag=get_post_meta($_GET['pw_id'],'pw_product_tag',true);
				$pw_except_product_tag=get_post_meta($_GET['pw_id'],'pw_except_product_tag',true);
				$pw_product=get_post_meta($_GET['pw_id'],'pw_product',true);
				$pw_except_product=get_post_meta($_GET['pw_id'],'pw_except_product',true);
				$pw_discount=get_post_meta($_GET['pw_id'],'pw_discount',true);
				$pw_from=get_post_meta($_GET['pw_id'],'pw_from',true);
				$pw_to=get_post_meta($_GET['pw_id'],'pw_to',true);
				$pw_discount_qty=get_post_meta($_GET['pw_id'],'pw_discount_qty',true);
				$adjustment_type=get_post_meta($_GET['pw_id'],'adjustment_type',true);
				$adjustment_value=get_post_meta($_GET['pw_id'],'adjustment_value',true);
				$amount_to_adjust=get_post_meta($_GET['pw_id'],'amount_to_adjust',true);
				$amount_to_purchase=get_post_meta($_GET['pw_id'],'amount_to_purchase',true);		
			}	
		}		

		include_once (PW_flash_sale_URL.'/template/admin/add_edit_cart.php') ;
	}
	//list_rule
	elseif(!isset($_GET['pw_action_type']))
	{
		include_once (PW_flash_sale_URL.'/core/admin/list_cart.php') ;
	}
}
else
{
	$pw_flash_sale_image=$pw_type_discount=$quantity_base=$pw_matched=$pw_users=$pw_cart_roles=$pw_roles=$adjustment_type=$pw_capabilities=
	$pw_flashsale_image_id=$pw_name=$pw_type=$pw_apply_to=$adjustment_value=$amount_to_adjust=
	$amount_to_purchase=$pw_products_to_adjust=$arr=$pw_products_to_adjust_products=$pw_products_to_adjust_category="";	
	if(@$_GET['pw_action_type']=="add" || @$_GET['pw_action_type']=="edit" || @$_GET['pw_action_type']=="delete" || @$_GET['pw_action_type']=="status")
	{
		$pw_product_category=$pw_except_product_category=$pw_product_tag=$pw_except_product_tag=$pw_product=
		$pw_except_product=$pw_discount=$pw_from=$pw_to=$pw_products_to_adjust_products=$pw_products_to_adjust_category="";
		if(@$_POST['pw_action_type']=='add' || @$_POST['pw_action_type']=='' && isset($_POST['pw_id']))
		{
			include_once (PW_flash_sale_URL.'/core/admin/add_rule.php') ;
			?>
			<script type="text/javascript">
				window.location="<?php echo admin_url( 'admin.php?page=rule_list');?>";
			</script>';	
			<?php			
		}
		else if(@$_POST['pw_action_type']=='edit' && isset($_POST['pw_name']))
		{
			include_once (PW_flash_sale_URL.'/core/admin/edit_rule.php') ;
		}
		else if(@$_GET['pw_action_type']=='delete' && isset($_GET['pw_id']))
		{
			wp_delete_post($_GET['pw_id']);
			?>
			<script type="text/javascript">
				window.location="<?php echo admin_url( 'admin.php?page=rule_list');?>";
			</script>';	
			<?php
		//	header('Location:'.admin_url( 'admin.php?page=rule_list'));
		}
		else if(@$_GET['pw_action_type']=='status' && isset($_GET['pw_id']))
		{
			update_post_meta($_GET['pw_id'], 'status', @$_GET['status_type']);
			?>
			<script type="text/javascript">
				window.location="<?php echo admin_url( 'admin.php?page=rule_list');?>";
			</script>';	
			<?php
		//	header('Location:'.admin_url( 'admin.php?page=rule_list'));
		}		
		$pw_action_type='add';
		if(@$_GET['pw_action_type']=="edit"){
			$pw_action_type='edit';
			if(isset($_GET['pw_id']))
			{
				$pw_name=get_post_meta($_GET['pw_id'],'pw_name',true);
				$pw_matched=get_post_meta($_GET['pw_id'],'pw_matched',true);
				$pw_users=get_post_meta($_GET['pw_id'],'pw_users',true);
				$pw_type=get_post_meta($_GET['pw_id'],'pw_type',true);
				$pw_cart_roles=get_post_meta($_GET['pw_id'],'pw_cart_roles',true);
				$pw_capabilities=get_post_meta($_GET['pw_id'],'pw_capabilities',true);
				$pw_roles=get_post_meta($_GET['pw_id'],'pw_roles',true);
				$quantity_base=get_post_meta($_GET['pw_id'],'quantity_base',true);
				$thumbnail_id = get_post_meta($_GET['pw_id'], 'pw_flash_sale_image', true);
				$pw_flashsale_image_id =$thumbnail_id;
				if ($thumbnail_id)
					$pw_flash_sale_image = wp_get_attachment_thumb_url( $thumbnail_id );
				$pw_flash_sale_image = str_replace( ' ', '%20', $pw_flash_sale_image );
				
				$pw_product_category=get_post_meta($_GET['pw_id'],'pw_product_category',true);
				$pw_except_product_category=get_post_meta($_GET['pw_id'],'pw_except_product_category',true);
				$pw_apply_to=get_post_meta($_GET['pw_id'],'pw_apply_to',true);
				$pw_product_tag=get_post_meta($_GET['pw_id'],'pw_product_tag',true);
				$pw_except_product_tag=get_post_meta($_GET['pw_id'],'pw_except_product_tag',true);
				$pw_product=get_post_meta($_GET['pw_id'],'pw_product',true);
				$pw_except_product=get_post_meta($_GET['pw_id'],'pw_except_product',true);
				$pw_discount=get_post_meta($_GET['pw_id'],'pw_discount',true);
				$pw_from=get_post_meta($_GET['pw_id'],'pw_from',true);
				$pw_to=get_post_meta($_GET['pw_id'],'pw_to',true);
				$pw_discount_qty=get_post_meta($_GET['pw_id'],'pw_discount_qty',true);
				$pw_type_discount=get_post_meta($_GET['pw_id'],'pw_type_discount',true);
				$adjustment_type=get_post_meta($_GET['pw_id'],'adjustment_type',true);
				$adjustment_value=get_post_meta($_GET['pw_id'],'adjustment_value',true);
				$amount_to_adjust=get_post_meta($_GET['pw_id'],'amount_to_adjust',true);
				$amount_to_purchase=get_post_meta($_GET['pw_id'],'amount_to_purchase',true);		
				$pw_products_to_adjust=get_post_meta($_GET['pw_id'],'pw_products_to_adjust',true);		
				$pw_products_to_adjust_products=get_post_meta($_GET['pw_id'],'pw_products_to_adjust_products',true);		
				$pw_products_to_adjust_category=get_post_meta($_GET['pw_id'],'pw_products_to_adjust_category',true);		
				
			}	
		}		

		include_once (PW_flash_sale_URL.'/template/admin/add_edit_rule.php') ;
	}
	//list_rule
	elseif(!isset($_GET['pw_action_type']))
	{
		include_once (PW_flash_sale_URL.'/core/admin/list_rule.php') ;
	}
}
?>