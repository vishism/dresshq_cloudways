<?php 
wp_enqueue_media();
?>
    <form id="pw_create_level_form" class="pw_create_level_form" method="POST">	
        <table class="form-table pw-fs-table">
        	<tr class="title-row">
            	<td colspan="2" >
					<?php
					if($_GET['pw_action_type']=="edit"){
					?>
						<strong><?php _e('Edit Rule', 'pw_wc_flash_sale') ?></strong>
					<?php }
					else{
						?><strong><?php _e('Add New Rule', 'pw_wc_flash_sale') ?></strong>
					<?php
					}
					?>
                </td>
                
            </tr>
			<tr>
            	<td>
					<?php _e('Rule Name', 'pw_wc_flash_sale') ?>:
                </td>
                <td>
                	<input type="text" name="pw_name" id="pw_name" value="<?php echo $pw_name;?>" class="require">
                    <br />
                    <span class="description"><?php _e('Enter Rule name', 'pw_wc_flash_sale') ?></span>
                </td>
           	</tr>
			<tr>
				<td>
					<?php _e('Rule Image', 'pw_wc_flash_sale') ?>:
				</td>
				<td>
					<div id="property_type_thumbnail" style="float:left;margin-right:10px;">
					<img src="<?php echo $pw_flash_sale_image;?>" width="60px" height="60px" /></div>
					
					<input type="hidden" id="property_type_thumbnail_id" name="pw_flash_sale_image" value="<?php echo $pw_flashsale_image_id;?>" />
					<button type="button" class="upload_image_button button"><?php _e( 'Upload/Add image', 'pw_wc_flash_sale' ); ?></button>
					<button type="button" class="remove_image_button button"><?php _e( 'Remove image', 'pw_wc_flash_sale' ); ?></button>

					<script type="text/javascript">
						 if ( ! jQuery('#property_type_thumbnail_id').val() )
						var file_frame;
						jQuery(document).on( 'click', '.upload_image_button', function( event ){
						
							event.preventDefault();

							// If the media frame already exists, reopen it.
							if ( file_frame ) {
								file_frame.open();
								return;
							}
							// Create the media frame.
							file_frame = wp.media.frames.downloadable_file = wp.media({
								title: '<?php _e( 'Choose an image', 'pw_wc_flash_sale' ); ?>',
								button: {
									text: '<?php _e( 'Use image', 'pw_wc_flash_sale' ); ?>',
								},
								multiple: false
							});
		
							// When an image is selected, run a callback.
							file_frame.on( 'select', function() {
								attachment = file_frame.state().get('selection').first().toJSON();
		
								jQuery('#property_type_thumbnail_id').val( attachment.id );
								jQuery('#property_type_thumbnail img').attr('src', attachment.url );
								jQuery('.remove_image_button').show();
							});
		
							// Finally, open the modal.
							file_frame.open();
						});
		
						jQuery(document).on( 'click', '.remove_image_button', function( event ){
							jQuery('#property_type_thumbnail img').attr('src', '<?php echo ''; ?>');
							jQuery('#property_type_thumbnail_id').val('');
						//	jQuery('.remove_image_button').hide();
							return false;
						});
						
						
					</script>
				</td>
			</tr>
			<tr class="tr_apply_to">
				<td>
					<?php _e('Apply to', 'pw_wc_flash_sale');?>
				</td>
				<td>
					<select name="pw_apply_to" id="pw_apply_to">
                    	<optgroup label="Products">
							<option value="pw_all_product" <?php selected("pw_all_product",$pw_apply_to,1)?>><?php _e('All Product', 'pw_wc_flash_sale');?></option>
                            <option value="pw_product" <?php selected("pw_product",$pw_apply_to,1)?>><?php _e('Product in List', 'pw_wc_flash_sale');?></option>
                            <option value="pw_except_product" <?php selected("pw_except_product",$pw_apply_to,1)?>><?php _e('Product not in List', 'pw_wc_flash_sale');?></option>
                        </optgroup>
                        
                        <optgroup label="Category">
							<option value="pw_product_category" <?php selected("pw_product_category",$pw_apply_to,1)?> ><?php _e('Category in List', 'pw_wc_flash_sale');?></option>
							<option value="pw_except_product_category" <?php selected("pw_except_product_category",$pw_apply_to,1)?> ><?php _e('Category not in List', 'pw_wc_flash_sale');?></option>
                        </optgroup>
                        
                        <optgroup label="Tag">
							<option value="pw_product_tag" <?php selected("pw_product_tag",$pw_apply_to,1)?> ><?php _e('Tag in List', 'pw_wc_flash_sale');?></option>
							<option value="pw_except_product_tag" <?php selected("pw_except_product_tag",$pw_apply_to,1)?> ><?php _e('Tag not in List', 'pw_wc_flash_sale');?></option>
                        </optgroup>
					</select>
				</td>
			</tr>
            <tr id="pw_product_category" class="discount_apply_to">
            	<td>
					<?php _e('category', 'pw_wc_flash_sale') ?>
                </td>
				<td>
                   <div>
                    <?php
                    	$param_line = '<select name="pw_product_category[]" class="chosen-select" multiple="multiple" data-placeholder="'.__('Choose Category','pw_wc_flash_sale').' ..." >';
						$args = array(
							'taxonomy'       		   =>  'product_cat',
							'name'          		   =>  'pw_product_category',
							'id'                       =>  'pw_product_category',
							'orderby'                  => 'name',
							'order'                    => 'ASC',
							'hide_empty'               => 0,
							'hierarchical'             => 1,
							'exclude'                  => '',
							'include'                  => '',
							'child_of'          		 => 0,
							'number'                   => '',
							'pad_counts'               => false 
						
						); 

						$categories = get_categories($args); 
						foreach ($categories as $category) {
							$selected='';
							//$meta=($pw_level_discount_type=='pw_level_product_category' ? $pw_level_discount_applyto:"");
							//$meta=get_post_meta($category->cat_ID,'pw_product_category',true);
							$meta=$pw_product_category;
                            if(is_array($meta))
                            {
                                $selected=(in_array($category->cat_ID,$meta) ? "SELECTED":"");
                            }
							
							$option = '<option value="'.$category->cat_ID.'" '.$selected.'>';
							$option .= $category->cat_name;
							$option .= ' ('.$category->category_count.')';
							$option .= '</option>';
							$param_line .= $option;
						}
						$param_line .= '</select>';
						echo $param_line; 
					?>	
					</div>					
				</td>
			</tr>
            <tr id="pw_except_product_category" class="discount_apply_to">
            	<td>
					<?php _e('Except category', 'pw_wc_flash_sale') ?>
                </td>
				<td>
                   <div>
                    <?php
                    	$param_line = '<select name="pw_except_product_category[]" class="chosen-select" multiple="multiple" data-placeholder="'.__('Choose Category','pw_wc_flash_sale').' ..." >';
						$args = array(
							'taxonomy'       		   =>  'product_cat',
							'name'          		   =>  'pw_except_product_category',
							'id'                       =>  'pw_except_product_category',
							'orderby'                  => 'name',
							'order'                    => 'ASC',
							'hide_empty'               => 0,
							'hierarchical'             => 1,
							'exclude'                  => '',
							'include'                  => '',
							'child_of'          		 => 0,
							'number'                   => '',
							'pad_counts'               => false 
						
						); 

						$categories = get_categories($args); 
						foreach ($categories as $category) {
							$selected='';
							//$meta=($pw_level_discount_type=='pw_level_product_category' ? $pw_level_discount_applyto:"");
							//$meta=get_post_meta(get_the_ID(),'pw_except_product_category',true);
							$meta=$pw_except_product_category;
                            if(is_array($meta))
                            {
                                $selected=(in_array($category->cat_ID,$meta) ? "SELECTED":"");
                            }
							
							$option = '<option value="'.$category->cat_ID.'" '.$selected.'>';
							$option .= $category->cat_name;
							$option .= ' ('.$category->category_count.')';
							$option .= '</option>';
							$param_line .= $option;
						}
						$param_line .= '</select>';
						echo $param_line; 
					?>	
					</div>					
				</td>
			</tr>			
			<tr id="pw_product_tag" class="discount_apply_to">
				<td>
					<?php _e('Tag', 'pw_wc_flash_sale') ?>
				</td>
				<td>
                    <div>    
					<?php	
                        $param_line = '<select name="pw_product_tag[]" class="chosen-select" multiple="multiple" data-placeholder="'.__('Choose Tag','pw_wc_flash_sale').' ...">';
						$args = array(
							'taxonomy'       		   =>  'product_tag',
							'name'          		   =>  'pw_product_tag',
							'id'                       =>  'pw_product_tag',
							'orderby'                  => 'name',
							'order'                    => 'ASC',
							'hide_empty'               => 0,
							'hierarchical'             => 1,
							'exclude'                  => '',
							'include'                  => '',
							'child_of'          		 => 0,
							'number'                   => '',
							'pad_counts'               => false 
						
						); 

						$categories = get_categories($args); 
						foreach ($categories as $category) {
							$selected='';
							//$meta=get_post_meta(get_the_ID(),'pw_product_tag',true);	
							$meta=$pw_product_tag;
						//	$meta=($pw_level_discount_type=='pw_level_product_tag' ? $pw_level_discount_applyto:"");
                            if(is_array($meta))
                            {
                                $selected=(in_array($category->cat_ID,$meta) ? "SELECTED":"");
                            }
							
							$option = '<option value="'.$category->cat_ID.'" '.$selected.'>';
							$option .= $category->cat_name;
							$option .= ' ('.$category->category_count.')';
							$option .= '</option>';
							$param_line .= $option;
						}
						$param_line .= '</select>';
						echo $param_line;
					?>
                    </div>				
				</td>
			</tr>
			<tr id="pw_except_product_tag"  class="discount_apply_to">
				<td>
					<?php _e('Except Tag', 'pw_wc_flash_sale') ?>
				</td>
				<td>
                    <div>    
					<?php	
             /**/           $param_line = '<select name="pw_except_product_tag[]" class="chosen-select" multiple="multiple" data-placeholder="'.__('Choose Tag','pw_wc_flash_sale').' ...">';
						$args = array(
							'taxonomy'       		   =>  'product_tag',
							'name'          		   =>  'pw_except_product_tag',
							'id'                       =>  'pw_except_product_tag',
							'orderby'                  => 'name',
							'order'                    => 'ASC',
							'hide_empty'               => 0,
							'hierarchical'             => 1,
							'exclude'                  => '',
							'include'                  => '',
							'child_of'          		 => 0,
							'number'                   => '',
							'pad_counts'               => false 
						
						); 

						$categories = get_categories($args); 
						foreach ($categories as $category) {
							$selected='';
							//$meta=get_post_meta(get_the_ID(),'pw_except_product_tag',true);	
						//	$meta=($pw_level_discount_type=='pw_level_product_tag' ? $pw_level_discount_applyto:"");
							$meta=$pw_except_product_tag;
                            if(is_array($meta))
                            {
                                $selected=(in_array($category->cat_ID,$meta) ? "SELECTED":"");
                            }
							
							$option = '<option value="'.$category->cat_ID.'" '.$selected.'>';
							$option .= $category->cat_name;
							$option .= ' ('.$category->category_count.')';
							$option .= '</option>';
							$param_line .= $option;
						}
						$param_line .= '</select>';
						echo $param_line;
						
					?>
                    </div>				
				</td>
			</tr>			
			<tr id="pw_product" class="discount_apply_to">
				<td>
					<?php _e('Product', 'pw_wc_flash_sale') ?>
				</td>
				<td>
                    <div>
                    <select name="pw_product[]" class="chosen-select" multiple="multiple" data-placeholder="<?php _e('Choose Product', 'pw_wc_flash_sale') ?> ..." >
						<?php
               /* */        $args_post = array('post_type' => 'product','posts_per_page'=>-1);
                        $loop_post = new WP_Query( $args_post );
                        $option_data='';
                        while ( $loop_post->have_posts() ) : $loop_post->the_post();
                            $selected='';
							//$meta=get_post_meta(get_the_ID(),'pw_product',true);								
							$meta=$pw_product;
                            //$meta=($pw_level_discount_type=='pw_level_product' ? $pw_level_discount_applyto:"");
                            if(is_array($meta))
                            {
                                $selected=(in_array(get_the_ID(),$meta) ? "SELECTED":"");
                            }
                            $option_data.='<option '.$selected.' value="'.get_the_ID().'">'.get_the_title().'</option>';
                        endwhile;
                        echo $option_data;
						
                        ?>
                    </select>
                    </div>					
				</td>
			</tr>
			<tr id="pw_except_product" class="discount_apply_to" >
				<td>
					<?php _e('Except Product', 'pw_wc_flash_sale') ?>
				</td>
				<td>
                    <div>
                    <select name="pw_except_product[]" class="chosen-select" multiple="multiple" data-placeholder="<?php _e('Choose Product', 'pw_wc_flash_sale') ?> ..." >
						<?php
              /* */         $args_post = array('post_type' => 'product','posts_per_page'=>-1);
                        $loop_post = new WP_Query( $args_post );
                        $option_data='';
                        while ( $loop_post->have_posts() ) : $loop_post->the_post();
                            $selected='';
							//$meta=get_post_meta(get_the_ID(),'pw_except_product',true);
							$meta=$pw_except_product;
                            //$meta=($pw_level_discount_type=='pw_level_product' ? $pw_level_discount_applyto:"");
                            if(is_array($meta))
                            {
                                $selected=(in_array(get_the_ID(),$meta) ? "SELECTED":"");
                            }
                            $option_data.='<option '.$selected.' value="'.get_the_ID().'">'.get_the_title().'</option>';
                        endwhile;
                        echo $option_data;
						
                        ?>
                    </select>
                    </div>					
				</td>
			</tr>
			<tr class="tr-applie-to-roles">
				<td>
					<?php _e('Customer:','pw_wc_flash_sale');?>
				</td>
				<td>
					<select name="pw_cart_roles" class="tr-type-roles" data-placeholder="Choose...">
						<option value="everyone" <?php selected("everyone",$pw_cart_roles,1);?>><?php _e('Everyone','pw_wc_flash_sale');?></option>
						<option value="roles" <?php selected("roles",$pw_cart_roles,1);?>><?php _e('Specific Roles in','pw_wc_flash_sale');?></option>
						<option value="capabilities" <?php selected("capabilities",$pw_cart_roles,1);?>><?php _e('Specific Capabilities in','pw_wc_flash_sale');?></option>
						<option value="users" <?php selected("users",$pw_cart_roles,1);?>><?php _e('Specific users in','pw_wc_flash_sale');?></option>
					</select>
				</td>
			</tr>
			<tr class="tr-roles">
				<td>
					<?php _e('Roles','pw_wc_flash_sale');?>
				</td>
				<td>
					<?php
					//For Create
					if (!isset($wp_roles)) {
						$wp_roles = new WP_Roles();
					}					
					$all_roles = $wp_roles->roles;
					$chunks = array_chunk($all_roles, ceil(count($all_roles) / 3), true);
					echo '<select name="pw_roles[]" class="chosen-select" multiple="multiple" data-placeholder="Choose Roles">';
					foreach ($chunks as $chunk) :					
						foreach ($chunk as $role_id => $role) :
							$meta=$pw_roles;
                            if(is_array($meta))
                            {
                                $selected=(in_array($role_id ,$meta) ? "SELECTED":"");
                            }						
							echo '<option '.$selected.' value="'. $role_id.'">'.$role['name'].'</option>';
						endforeach;
					endforeach;
					echo '</select>';
					?>
				</td>
			</tr>
			<tr class="tr-roles-capabilities">
				<td>
					<?php _e('capabilities','pw_wc_flash_sale');?>
				</td>
				<td>
					<?php
					echo '<select name="pw_capabilities[]" class="chosen-select" multiple="multiple" data-placeholder="Choose capabilities">';
					foreach ( pw_list_capabilities() as $cap ) { 
						$meta=$pw_capabilities;
						if(is_array($meta))
						{
							$selected=(in_array($cap ,$meta) ? "SELECTED":"");
						}						
						echo '<option '.$selected.' value="'. $cap.'">'.$cap.'</option>';
					}
					echo '</select>';
					?>
				</td>
			</tr>
			<tr class="tr-roles-users">
				<td>
					<?php _e('Users','pw_wc_flash_sale');?>
				</td>
				<td>
					<?php
					echo '<select name="pw_users[]" class="chosen-select" multiple="multiple" data-placeholder="Choose Users">';
					foreach(get_users() as $user) {
						$meta=$pw_users;
						if(is_array($meta))
						{
							$selected=(in_array($cap ,$meta) ? "SELECTED":"");
						}
						echo '<option '.$selected.' value="'. $user->ID .'">ID:'.$user->ID.' '.$user->user_email.'</option>';
					}
					echo '</select>';
					?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e('schedule', 'pw_wc_flash_sale') ?>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e('From', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<input type="text" id="date_timepicker_from" name="pw_from" value="<?php echo $pw_from;?>">
					<?php
/*					$res=strtotime($pw_to)-strtotime($pw_from);
					$days= floor(($res)/86400);
					$hours   =floor(($res-($days*86400))/3600);
					echo 'Days: '.$days.' H : '.$hours;
*/
					?>
					<script type="text/javascript">
						jQuery(function(){
							jQuery('#date_timepicker_from').datetimepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e('To', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<input type="text" id="date_timepicker_to" name="pw_to" value="<?php echo $pw_to;?>">
					<script type="text/javascript">
						jQuery(function(){
							jQuery('#date_timepicker_to').datetimepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e('If conditions are matched with other Rule', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<select name="pw_matched" class="tr-type-roles">
						<option value="all" <?php selected("all",$pw_matched,1);?>><?php _e('Apply this and other matched rules','pw_wc_flash_sale');?></option>
						<option value="only" <?php selected("only",$pw_matched,1);?>><?php _e('Apply only This rule(disregard other rules)','pw_wc_flash_sale');?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e('Type Of Discount', 'pw_wc_flash_sale')?>
				</td>
				<td>
					<select name="pw_type" class="tr-type">
						<option value="flashsale" <?php selected("flashsale",$pw_type,1)?>><?php _e('FlashSale', 'pw_wc_flash_sale')?></option>
						<option value="quantity" <?php selected("quantity",$pw_type,1)?> ><?php _e('Quantity', 'pw_wc_flash_sale')?></option>
						<option value="special" <?php selected("special",$pw_type,1)?> ><?php _e('Special Offer', 'pw_wc_flash_sale')?></option>
					</select>
				</td>
			</tr>
			<tr class="tr-special">
				<td><?php _e('Quantities based on','');?></td>
				<td>
					<select name="quantity_base" class="tr-type">
						<option value="one" <?php selected("one",$quantity_base,1)?>><?php _e('Quantities of each product individually', 'pw_wc_flash_sale')?></option>
						<option value="all" <?php selected("all",$quantity_base,1)?> ><?php _e('Quantities of all selected products split by category', 'pw_wc_flash_sale')?></option>
					</select>
				</td>
			</tr>
			<tr class="tr-adjust">
				<td>
					<?php _e('Products to adjust', 'pw_wc_flash_sale')?>
				</td>
				<td>
					<select name="pw_products_to_adjust" class="td-adjust">
						<option value="matched" <?php selected("matched",$pw_products_to_adjust,1)?>><?php _e('Same products (selected above)', 'pw_wc_flash_sale')?></option>
						<option value="other_categories" <?php selected("other_categories",$pw_products_to_adjust,1)?> ><?php _e('Specific categories', 'pw_wc_flash_sale')?></option>
						<option value="other_products" <?php selected("other_products",$pw_products_to_adjust,1)?> ><?php _e('Specific products', 'pw_wc_flash_sale')?></option>
					</select>
				</td>
			</tr>

			<tr id="pw_products_to_adjust_products" class="products-to-adjust-products">
				<td>
					<?php _e('Product List', 'pw_wc_flash_sale') ?>
				</td>
				<td>
                    <div>
                    <select name="pw_products_to_adjust_products[]" class="chosen-select" multiple="multiple" data-placeholder="<?php _e('Choose Product', 'pw_wc_flash_sale') ?> ..." >
						<?php
               /* */        $args_post = array('post_type' => 'product','posts_per_page'=>-1);
                        $loop_post = new WP_Query( $args_post );
                        $option_data='';
                        while ( $loop_post->have_posts() ) : $loop_post->the_post();
                            $selected='';
							//$meta=get_post_meta(get_the_ID(),'pw_product',true);								
							$meta=$pw_products_to_adjust_products;
                            //$meta=($pw_level_discount_type=='pw_level_product' ? $pw_level_discount_applyto:"");
                            if(is_array($meta))
                            {
                                $selected=(in_array(get_the_ID(),$meta) ? "SELECTED":"");
                            }
                            $option_data.='<option '.$selected.' value="'.get_the_ID().'">'.get_the_title().'</option>';
                        endwhile;
                        echo $option_data;
						
                        ?>
                    </select>
                    </div>					
				</td>
			</tr>
            <tr id="pw_products_to_adjust_category" class="pw-products-to-adjust-category">
            	<td>
					<?php _e('Category List', 'pw_wc_flash_sale') ?>
                </td>
				<td>
                   <div>
                    <?php
                    	$param_line = '<select name="pw_products_to_adjust_category[]" class="chosen-select" multiple="multiple" data-placeholder="'.__('Choose Category','pw_wc_flash_sale').' ..." >';
						$args = array(
							'taxonomy'       		   =>  'product_cat',
							'name'          		   =>  'pw_product_category',
							'id'                       =>  'pw_product_category',
							'orderby'                  => 'name',
							'order'                    => 'ASC',
							'hide_empty'               => 0,
							'hierarchical'             => 1,
							'exclude'                  => '',
							'include'                  => '',
							'child_of'          		 => 0,
							'number'                   => '',
							'pad_counts'               => false 
						
						); 

						$categories = get_categories($args); 
						foreach ($categories as $category) {
							$selected='';
							//$meta=($pw_level_discount_type=='pw_level_product_category' ? $pw_level_discount_applyto:"");
							//$meta=get_post_meta($category->cat_ID,'pw_product_category',true);
							$meta=$pw_products_to_adjust_category;
                            if(is_array($meta))
                            {
                                $selected=(in_array($category->cat_ID,$meta) ? "SELECTED":"");
                            }
							
							$option = '<option value="'.$category->cat_ID.'" '.$selected.'>';
							$option .= $category->cat_name;
							$option .= ' ('.$category->category_count.')';
							$option .= '</option>';
							$param_line .= $option;
						}
						$param_line .= '</select>';
						echo $param_line; 
					?>	
					</div>					
				</td>
			</tr>

			<tr class="tr-flashsale">
				<td>
					<?php _e('Discount', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<select name="pw_type_discount" class="td-adjust">
						<option value="percent" <?php selected("percent",$pw_type_discount,1)?>><?php _e('Percentage discount', 'pw_wc_flash_sale')?></option>
						<option value="price" <?php selected("price",$pw_type_discount,1)?> ><?php _e('Price discount', 'pw_wc_flash_sale')?></option>
					</select>
					<input type="text" id="datepicker" name="pw_discount" value="<?php echo $pw_discount;?>">					
				</td>
			</tr>
			<tr class="tr-special">
				<td>
					<?php _e('Amount to purchase', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<input type="text" id="datepicker" name="amount_to_purchase" value="<?php echo $amount_to_purchase;?>">
				</td>
			</tr>
			<tr class="tr-special">
				<td>
					<?php _e('Amount to adjust', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<input type="text" id="datepicker" name="amount_to_adjust" value="<?php echo $amount_to_adjust;?>">
				</td>
			</tr>
			<tr class="tr-special">
				<td>
					<?php _e('Adjustment Type/value', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<select name="adjustment_type" class="td-adjust">
						<option value="percent" <?php selected("percent",$adjustment_type,1)?>><?php _e('Percentage discount', 'pw_wc_flash_sale')?></option>
						<option value="price" <?php selected("price",$adjustment_type,1)?> ><?php _e('Price discount', 'pw_wc_flash_sale')?></option>
					</select>
					<input type="text" id="datepicker" name="adjustment_value" value="<?php echo $adjustment_value;?>">
				</td>
			</tr>
			<tr class="tr-quantity">
				<td>
					<?php _e('Quantity Discount', 'pw_wc_flash_sale') ?>
				</td>
				<td id="pw_discount_qty_repeatable">
                	<div>
                    	
                        <?php
                        	if(isset($pw_discount_qty) && is_array($pw_discount_qty))
							{
								$row_i=0;
								foreach($pw_discount_qty as $discount_qty){
									$remove_btn='';
									if($row_i>0)
									{
										$remove_btn='<input type="button" class="pw_discount_remove_btn" value="Remove">';
									}
									echo '
                                	<div class="pw_discount_qty">
                                        <input type="text" name="pw_discount_qty['.$row_i.'][min]" placeholder="Min Quantity" value="'.@$discount_qty['min'].'"></br>
                                        <input type="text" name="pw_discount_qty['.$row_i.'][max]" placeholder="Max Quantity" value="'.@$discount_qty['max'].'"></br>
                                        <input type="text" name="pw_discount_qty['.$row_i.'][discount]" placeholder="Discount" value="'.@$discount_qty['discount'].'">'.$remove_btn.'</br>
                                    </div>
									';	
									$row_i++;
								}
							}else
							{
						?>
                        <div class="pw_discount_qty">
                            <input type="text" name="pw_discount_qty[0][min]" placeholder="<?php _e('Min Quantity', 'pw_wc_flash_sale') ?>"></br>
                            <input type="text" name="pw_discount_qty[0][max]" placeholder="<?php _e('Max Quantity', 'pw_wc_flash_sale') ?>"></br>
                            <input type="text" name="pw_discount_qty[0][discount]" placeholder="<?php _e('Discount', 'pw_wc_flash_sale') ?>"></br>
                        </div>
                        <?php
							}
						?>
                        <div id="pw_discount_add">
                        	<input type="button" id="pw_discount_add_btn" value="Add">
                        </div>
                    </div>    
				</td>				
			</tr>			
			<tr>
				<td>
					
				</td>
				<td>	
                	<input type="submit" value="<?php _e('Submit', 'pw_wc_flash_sale') ?>">
                    <input type="hidden" name="pw_action_type" id="pw_action_type" value="<?php echo $pw_action_type;?>" />				
				</td>
			</tr>
		</table>
	</form>
<script language="javascript">  

jQuery(document).ready(function() {
	
	
	/////////ADD DISCOUNT REPEATABLE//////////
	var row_count=<?php echo isset($row_i) ? $row_i:1 ?>;
	jQuery('#pw_discount_add_btn').click(function(){
		jQuery('<div class="pw_discount_qty"><input type="text" name="pw_discount_qty['+row_count+'][min]" placeholder="Min Quantity"></br><input type="text" name="pw_discount_qty['+row_count+'][max]" placeholder="Max Quantity"></br><input type="text" name="pw_discount_qty['+row_count+'][discount]" placeholder="Discount"><input type="button" class="pw_discount_remove_btn" value="Remove"></br></div>').insertBefore(this);
		jQuery('.pw_discount_remove_btn').click(function(){
			jQuery(this).parent().remove();
		});
		row_count++;
	});
	
	jQuery('.pw_discount_remove_btn').click(function(){
		jQuery(this).parent().remove();
	});
	/////////END ADD DISCOUNT REPEATABLE//////////
	
	
	//////////APPLY TO/////////////
	jQuery('#pw_apply_to').change(function(){
		var $id=jQuery(this).val();		
		if($id!='pw_all_product')
		{
			jQuery('.discount_apply_to').each(function(){
				jQuery(this).hide();
			});

			jQuery("#"+$id).show();
		}else{
			jQuery('.discount_apply_to').each(function(){
				jQuery(this).hide();
			});
		}
	});
	jQuery('#pw_apply_to').val("<?php echo isset($pw_apply_to) ? ($pw_apply_to=='' ? 'pw_all_product':$pw_apply_to):'pw_all_product';?>").change();
	//////////END APPLY TO//////////////
	
	
	jQuery('.chosen-select').chosen();
	
	jQuery('.tr-quantity').dependsOn({
		'.tr-type': {
			values: ['quantity']
		}
	});
	jQuery('.tr-special').dependsOn({
		'.tr-type': {
			values: ['special']
		}
	});	
	
	jQuery('.tr-flashsale').dependsOn({
		'.tr-type': {
			values: ['flashsale','cart']
		}	
	});
	jQuery('.tr_apply_to').dependsOn({
		'.tr-type': {
			values: ['special','flashsale','quantity']
		}	
	});
	jQuery('.tr-adjust').dependsOn({
		'.tr-type': {
			values: ['special','quantity']
		}	
	});
	jQuery('.products-to-adjust-products').dependsOn({
		'.tr-type': {
			values: ['special','quantity']
		}	
	});
	jQuery('.pw-products-to-adjust-category').dependsOn({
		'.tr-type': {
			values: ['special','quantity']
		}	
	});
	jQuery('.products-to-adjust-products').dependsOn({
		'.td-adjust': {
			values: ['other_products']
		}	
	});
	jQuery('.pw-products-to-adjust-category').dependsOn({
		'.td-adjust': {
			values: ['other_categories']
		}	
	});
	jQuery('.tr-roles').dependsOn({
		'.tr-type-roles': {
			values: ['roles']
		}
	});
	
	jQuery('.tr-roles-capabilities').dependsOn({
		'.tr-type-roles': {
			values: ['capabilities']
		}
	});
	jQuery('.tr-roles-users').dependsOn({
		'.tr-type-roles': {
			values: ['users']
		}
	});		
});
</script>