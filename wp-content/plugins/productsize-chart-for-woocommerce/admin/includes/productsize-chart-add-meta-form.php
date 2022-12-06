<?php

/**
 * Provide a admin area form view for the plugin
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

// Use get_post_meta to retrieve an existing value of chart 1 from the database.
$chart1_assets       = get_post_meta( $post->ID, 'chart-1', false );
$chart1_title        = '';
$chart1_img          = '';
$chart1_img_position = '';
$chart1_content      = '';
$chart1_table        = '';

if ( ! empty( $chart1_assets ) ) {
	$chart = $chart1_assets[0];

	$chart1_title        = $chart['chart-title'];
	$chart1_img          = $chart['chart-image'];
	$chart1_img_position = $chart['chart-position'];
	$chart1_content      = $chart['chart-content'];
	$chart1_table        = $chart['chart-table'];
}


// Use get_post_meta to retrieve an existing value of chart 2 from the database.
$chart2_assets       = get_post_meta( $post->ID, 'chart-2', false );
$chart2_title        = '';
$chart2_img          = '';
$chart2_img_position = '';
$chart2_content      = '';
$chart2_table        = '';

if ( ! empty( $chart2_assets ) ) {
	$chart2 = $chart2_assets[0];

	$chart2_title        = $chart2['chart-title'];
	$chart2_img          = $chart2['chart-image'];
	$chart2_img_position = $chart2['chart-position'];
	$chart2_content      = $chart2['chart-content'];
	$chart2_table        = $chart2['chart-table'];
}


// Display the form, using the current value.
?>
<div id="productsize-chart-meta-fields" class="chart-1"> 
	<div class="title-wrap">
		<h2><?php esc_html_e( 'Chart 1', 'productsize-chart-for-woocommerce' ); ?></h2>
	</div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Title', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart title below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"><input type="text" id="chart-title-1" name="chart-title-1" value="<?php echo esc_attr( $chart1_title ); ?>" /></div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Image', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart image below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"> 
			<input type="hidden" name="chart-image-1" id="chart-image-1" value="<?php echo absint( $chart1_img ); ?>" />
		</div>
		<?php
		$img = wp_get_attachment_image_src( $chart1_img, 'thumbnail' );
		?>
		<div id="field-image">
			<span style="display: <?php echo $img ? 'block' : 'none'; ?>;" class="_img_remove" data-placeholder="<?php echo plugins_url( 'images/chart-img-placeholder.jpg', dirname( __FILE__ ) ); ?>" data-id="primary-chart-image">×</span>
			<img class="chart_img" src="<?php echo ! empty( $img[0] ) ? esc_url( $img[0] ) : plugins_url( 'images/chart-img-placeholder.jpg', dirname( __FILE__ ) ); ?>" width="<?php echo absint( $img[1] ); ?>" height="<?php echo absint( $img[2] ); ?>" id="meta_img_1" />
		</div>
		<div class="field-item"><input type="button" id="meta-image-button-1" class="button" value="<?php esc_html_e( 'Upload', 'productsize-chart-for-woocommerce' ); ?>" /></div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Image Position', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Primary chart image position', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<select name="image-position-1" id="image-position-1">
				<option value="left" <?php selected( $chart1_img_position, 'left' ); ?>><?php esc_html_e( 'Left', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="right" <?php selected( $chart1_img_position, 'rightt' ); ?>><?php esc_html_e( 'Right', 'productsize-chart-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Content', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Chart 1 content', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<?php wp_editor( $chart1_content, 'chart-content-1', array( 'editor_height' => 200 ) ); ?>
		</div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Table', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<textarea id="chart-table-1" name="chart-table-1"><?php echo wp_kses_post( $chart1_table ); ?></textarea>
		</div>
	</div>
</div>

<div id="productsize-chart-meta-fields" class="chart-2"> 
	<div class="title-wrap"><h2><?php esc_html_e( 'Chart 2', 'productsize-chart-for-woocommerce' ); ?></h2></div>
	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Title', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart title below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"><input type="text" id="chart-title-2" name="chart-title-2" value="<?php echo esc_attr( $chart2_title ); ?>" /></div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Image', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart image below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item"> 
			<input type="hidden" name="chart-image-2" id="chart-image-2" value="<?php absint( $chart2_img ); ?>" />
		</div>
		<?php
		$img = wp_get_attachment_image_src( $chart2_img, 'thumbnail' );
		?>
		<div id="field-image">
			<span style="display: <?php echo $img ? 'block' : 'none'; ?>;" class="_img_remove" data-placeholder="<?php echo plugins_url( 'images/chart-img-placeholder.jpg', dirname( __FILE__ ) ); ?>" data-id="primary-chart-image">×</span>
			<img class="chart_img" src="<?php echo ! empty( $img[0] ) ? esc_url( $img[0] ) : plugins_url( 'images/chart-img-placeholder.jpg', dirname( __FILE__ ) ); ?>" width="<?php echo absint( $img[1] ); ?>" height="<?php echo absint( $img[2] ); ?>" id="meta_img_2" />
		</div>
		 <div class="field-item"><input type="button" id="meta-image-button-2" class="button" value="<?php esc_html_e( 'Upload', 'productsize-chart-for-woocommerce' ); ?>" /></div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Image Position', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Primary chart image position', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<select name="image-position-2" id="image-position-2">
				<option value="left" <?php selected( $chart2_img_position, 'left' ); ?>><?php esc_html_e( 'Left', 'productsize-chart-for-woocommerce' ); ?></option>
				<option value="right" <?php selected( $chart2_img_position, 'right' ); ?>><?php esc_html_e( 'Right', 'productsize-chart-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Content', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Chart 2 content', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<?php wp_editor( $chart2_content, 'chart-content-2', array( 'editor_height' => 200 ) ); ?>
		</div>
	</div>

	<div id="field">
		<div class="field-title"><h4><?php esc_html_e( 'Chart Table', 'productsize-chart-for-woocommerce' ); ?></h4></div> 
		<div class="field-description"><?php esc_html_e( 'Add/Edit chart below', 'productsize-chart-for-woocommerce' ); ?></div>
		<div class="field-item">
			<textarea id="chart-table-2" name="chart-table-2"><?php echo wp_kses_post( $chart2_table ); ?></textarea>
		</div>
	</div>
</div>
