<?php

/**
 * Provide a admin area form view for meta fields
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    productsize-chart-for-woocommerce
 * @subpackage productsize-chart-for-woocommerce/admin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

// Add an nonce field so we can check for it later.
wp_nonce_field( 'productsize_chart_inner_custom_box', 'productsize_chart_inner_custom_box' );

// Use get_post_meta to retrieve an existing value from the database.e
$chart_label        = get_post_meta( $post->ID, 'label', true );
$chart_img          = get_post_meta( $post->ID, 'primary-chart-image', true );
$chart_img_position = get_post_meta( $post->ID, 'primary-image-position', true );
$title_color        = get_post_meta( $post->ID, 'title-color', true );
$text_color         = get_post_meta( $post->ID, 'text-color', true );
$overlay_color      = get_post_meta( $post->ID, 'overlay-color', true );
$table_style        = get_post_meta( $post->ID, 'table-style', true );
$chart_padding      = get_post_meta( $post->ID, 'chart-padding', true );
$chart_position     = get_post_meta( $post->ID, 'position', true );
$button_position    = get_post_meta( $post->ID, 'button-position', true );
$chart_categories   = (array) get_post_meta( $post->ID, 'chart-categories', true );
$chart_table        = get_post_meta( $post->ID, 'chart-table', true );
$img                = wp_get_attachment_image_src( $chart_img, 'thumbnail' );
$img                = wp_parse_args( $img, array( '', 150, 100 ) );

?>
<div id="productsize-chart-meta-fields" class="chart-0"> 
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Label', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Chart Label', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"><input type="text" id="label" name="label" value="<?php echo esc_attr( $chart_label ); ?>" /></div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Primary Chart Image', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit primary chart image below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"> 
			<input type="hidden" name="primary-chart-image" id="primary-chart-image" value="<?php echo absint( $chart_img ); ?>" />
		</div>
		<?php  ?>
		<div id="field-image">
			<span style="display: <?php echo $img ? 'block' : 'none'; ?>;" class="_img_remove" data-placeholder="<?php echo plugins_url( 'images/chart-img-placeholder.jpg', dirname( __FILE__ ) ); ?>" data-id="primary-chart-image">×</span>
			<img class="chart_img" src="<?php echo ! empty( $img[0] ) ? esc_url( $img[0] ) : plugins_url( 'images/chart-img-placeholder.jpg', dirname( __FILE__ ) ); ?>" width="<?php echo absint( $img[1] ); ?>" height="<?php echo absint( $img[2] ); ?>"  id="meta_img" />
		</div>
		<div class="field-item"><input type="button" id="meta-image-button" class="button" value="<?php esc_html_e( 'Upload', 'productsize-chart-for-woocommerce' ); ?>" /></div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Image Position', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Primary chart mage position', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<select name="primary-image-position" id="primary-image-position">
				<option value="left" <?php selected( $chart_img_position, 'left' ); ?>><?php esc_html_e( 'Left', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="right" <?php selected( $chart_img_position, 'right' ); ?>><?php esc_html_e( 'Right', 'productsize-chart-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Title Color', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Pick text color for the chart text', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"><input type="text" id="title-color" name="title-color" value="<?php echo esc_attr( $title_color ); ?>" /></div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Text Color', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Pick text color for the chart text', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"><input type="text" id="text-color" name="text-color" value="<?php echo esc_attr( $text_color ); ?>" /></div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Overlay Color', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Pick overlay background color for modal', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<input type="text" id="overlay-color" name="overlay-color" value="<?php echo esc_attr( $overlay_color ); ?>" />
		</div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Table Style', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Chart Table Styles (Default Style 1)', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<select name="table-style" id="table-style">
				<option value="style-1" <?php selected( $table_style, 'style-1' ); ?>><?php esc_html_e( 'Style 1', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="style-2" <?php selected( $table_style, 'style-2' ); ?>><?php esc_html_e( 'Style 2', 'productsize-chart-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Padding (e.g. 10px)', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Pick overlay background color for modal', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<input type="text" id="chart-padding" name="chart-padding" value="<?php echo esc_attr( $chart_padding ); ?>" />
		</div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Position', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Select if the chart will display as a popup or as a additional tab', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<select name="position" id="position">
				<option value="tab" <?php selected( $chart_position, 'tab' ); ?>><?php esc_html_e( 'Additional Tab', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="popup" <?php selected( $chart_position, 'popup' ); ?>><?php esc_html_e( 'Modal Pop Up', 'productsize-chart-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>
	<div style="clear:both"></div>
	<div id="field" class="tab-or-modal" <?php echo $chart_position == 'tab' ? "style='display:none;'" : ''; ?>>
		<div class="field-title"><h4><?php esc_html_e( 'Chart Popup Button Position', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Select where the pop up button displays', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<select name="button-position" id="button-position">
				<option value="before-summary-text" <?php selected( $button_position, 'before-summary-text' ); ?>><?php esc_html_e( 'Before Summary Text', 'productsize-chart-for-woocommerce' ); ?></option>
				<option  value="after-add-to-cart" <?php selected( $button_position, 'after-add-to-cart' ); ?>><?php esc_html_e( 'After Add to Cart', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="before-add-to-cart" <?php selected( $button_position, 'before-add-to-cart' ); ?>><?php esc_html_e( 'Before Add to Cart', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="after-product-meta" <?php selected( $button_position, 'after-product-meta' ); ?>><?php esc_html_e( 'After Product Meta', 'productsize-chart-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Table', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<textarea id="chart-table" name="chart-table"><?php echo wp_kses_post( $chart_table ); ?></textarea>
		</div>
	</div>
</div>
