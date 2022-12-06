<?php if ( !defined('ABSPATH') ) die('No direct access (c) WooCommerce.binpress.com');
/*
Plugin Name: WooCommerce Remove /product & /product-category
Plugin URI: http://www.binpress.com/app/woocommerce-url-cleaner/2366
Description: Does what <strong>WooCommerce</strong> needs for <strong>SEO</strong>. Removes <strong>/product/</strong> and <strong>/product-category/</strong> (language independent) from the url in a clean way. <strong>Redirects</strong> the old URL to the new URL to maintain the grown SEO value.

Version: 2.0.1
Requires at least: 3.0
Tested up to: 4.1

Author: Tim
Author URI: http://www.binpress.com/app/woocommerce-url-cleaner/2366
*/

foreach ( glob( plugin_dir_path( __FILE__ ) . "lib/*.php" ) as $file ) {
    include_once $file;
}

defined('PLUGIN_CAN_EXECUTE') and new init or new wcAdmin;

?>
