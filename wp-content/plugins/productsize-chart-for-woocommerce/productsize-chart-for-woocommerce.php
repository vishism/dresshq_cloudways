<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Product Size Chart for Woocommerce
 * Description:       This plugin allow you to use size charts to products on woocommerce.
 * Version:           1.1.3
 * Author:            Ciobanu George
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       productsize-chart-for-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function productsize_chart_activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-productsize-chart-activator.php';
	productsize_chart_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function productsize_chart_deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-productsize-chart-deactivator.php';
	productsize_chart_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'productsize_chart_activate_plugin_name' );
register_deactivation_hook( __FILE__, 'productsize_chart_deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-productsize-chart.php';

/**
 * WooCommerce fallback notice.
 *
 * @since 1.1.0
 */
function productsize_chart_for_woocommerce_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Product Size Chart for Woocommerce requires WooCommerce to be installed and active. You can download %s here.', 'productsize-chart-for-woocommerce' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'productsize_chart_for_woocommerce_init' );
function productsize_chart_for_woocommerce_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'productsize_chart_for_woocommerce_missing_wc_notice' );
		return;
	}

	run_productsize_chart_for_woocommerce();
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_productsize_chart_for_woocommerce() {

	$plugin = new productsize_chart();
	$plugin->run();

}

