<?php
/**
 * Flatastic Child Theme functions and definitions
 *
 */

if (!function_exists('mad_flatastic_child_css')) {

	add_action('wp_print_styles', 'mad_flatastic_child_css');

	function mad_flatastic_child_css() {

		if (!is_admin()) {

			wp_enqueue_style( 'flatastic-child-styles', get_stylesheet_directory_uri() . '/style.css' );

			if (is_rtl()) {
				wp_enqueue_style( 'flatastic-child-styles-rtl', get_stylesheet_directory_uri() . '/rtl.css' );
			}

		}

	}

}

//Google Tag Manager
add_action('wp_head', 'google_tag_manager');
function google_tag_manager() { ?>
	<?php if ( function_exists( 'gtm4wp_the_gtm_tag' ) ) { gtm4wp_the_gtm_tag(); } ?>
<?php
}