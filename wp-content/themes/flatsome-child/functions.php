<?php
/* ADD custom theme functions here  */

//Google Tag Manager
add_action('wp_head', 'google_tag_manager');
function google_tag_manager() { ?>
	<?php if ( function_exists( 'gtm4wp_the_gtm_tag' ) ) { gtm4wp_the_gtm_tag(); } ?>
<?php
}