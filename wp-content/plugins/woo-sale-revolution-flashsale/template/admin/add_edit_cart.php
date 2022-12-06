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
					?><strong><?php _e('Add New Cart Discount', 'pw_wc_flash_sale') ?></strong>
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
			<tr class="tr-applie-to">
				<td>
					<?php _e('Applies To Customer:','pw_wc_flash_sale');?>
				</td>
				<td>
					<select name="pw_cart_roles" class="tr-type-roles" data-placeholder="Choose...">
						<option value="everyone" <?php selected("everyone",$pw_cart_roles,1);?>><?php _e('Everyone','pw_wc_flash_sale');?></option>
						<option value="roles" <?php selected("roles",$pw_cart_roles,1);?>><?php _e('Specific Roles in','pw_wc_flash_sale');?></option>
						<option value="capabilities" <?php selected("capabilities",$pw_cart_roles,1);?>><?php _e('Specific Capabilities in','pw_wc_flash_sale');?></option>
						<option value="users" <?php selected("users",$pw_cart_roles,1);?>><?php _e('Specific Users in','pw_wc_flash_sale');?></option>
					</select>
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
						$selected="";
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
		<!--			<?php foreach ($chunks as $chunk) : ?>
						<ul class="list-column">        
							<?php foreach ($chunk as $role_id => $role) : ?>
								<?php $role_checked = (isset($condition['args']['roles']) && is_array($condition['args']['roles']) && in_array($role_id, $condition['args']['roles'])) ? 'checked="checked"' : ''; ?>
								<li>
									<label for="<?php echo $name; ?>_role_<?php echo $role_id; ?>" class="selectit">
										<input <?php echo $role_checked; ?> type="checkbox" id="<?php echo $name; ?>_role_<?php echo $role_id; ?>" name="pricing_rules[<?php echo $name; ?>][conditions][<?php echo $condition_index; ?>][args][roles][]" value="<?php echo $role_id; ?>" /><?php echo $role['name']; ?>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
					-->
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
			
			<tr>
				<td>
					<?php _e('Conditions', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<select name="pw_type_conditions" class="tr-type-conditions">
						<option value="total" <?php selected("total",$pw_type_conditions,1);?>><?php _e('Order Total Pricing','pw_wc_flash_sale');?></option>
						<option value="products" <?php selected("products",$pw_type_conditions,1);?>><?php _e('If this products in cart','pw_wc_flash_sale');?></option>
					</select>
				</td>
			</tr>
			
			<tr class="tr-total">
				<td>
					<?php _e('Order Total Pricing', 'pw_wc_flash_sale') ?>
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
                            <input type="text" name="pw_discount_qty[0][min]" placeholder="<?php _e('Minimum Order Total', 'pw_wc_flash_sale') ?>"></br>
                            <input type="text" name="pw_discount_qty[0][max]" placeholder="<?php _e('Max Order Total', 'pw_wc_flash_sale') ?>"></br>
                            <input type="text" name="pw_discount_qty[0][discount]" placeholder="<?php _e('Amount', 'pw_wc_flash_sale') ?>"></br>
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
			
			<tr class="tr-product">
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
			
			<tr class="tr-discount">
				<td>
					<?php _e('Discount', 'pw_wc_flash_sale') ?>
				</td>
				<td>
					<input type="text" name="pw_discount" value="<?php echo $pw_discount;?>">
					<br/>
					<div style="font-size: 13px;font-style: italic;">
						<?php _e('Example:','pw_wc_flash_sale');?>
						<br/>
						<?php _e('1- Enter number with "%" symbol such as 10%, 20% or other percent','pw_wc_flash_sale');?>
						<br/>
						<?php _e('2- Enter just number such as 1000,2000,2300 or other number','pw_wc_flash_sale');?>
					</div>
				</td>
			</tr>
			
			<tr>
				<td>
					
				</td>
				<td>	
                	<input type="hidden" name="pw_type" value="cart">
                	<input type="submit" value="<?php _e('Submit', 'pw_wc_flash_sale') ?>">
                    <input type="hidden" name="pw_action_type" id="pw_action_type" value="<?php echo $pw_action_type;?>" />				
				</td>
			</tr>
		</table>
	</form>
<script language="javascript">  
jQuery(document).ready(function() {
	
		jQuery('.chosen-select').chosen();
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
	
	jQuery('.tr-total').dependsOn({
		'.tr-type-conditions': {
			values: ['total']
		}
	});
	
	jQuery('.tr-product').dependsOn({
		'.tr-type-conditions': {
			values: ['products']
		}
	});
	
	jQuery('.tr-discount').dependsOn({
		'.tr-type-conditions': {
			values: ['products']
		}
	});

});
</script>