<?php
/**
 * Pricing Deals Uninstall
 *
 * @version 2.0.2.0
 */


    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	   //error_log( print_r(  'vtprd UNINSTALL - exit001', true ) );
	   return;
	}


	global $wpdb, $wp_version;

    //done on deactivation by default, but just in case
	wp_clear_scheduled_hook( 'vtprd_once_daily_scheduled_events' ); //v2.0.2.0
    wp_clear_scheduled_hook( 'vtprd_twice_daily_scheduled_events' );
    wp_clear_scheduled_hook( 'vtprd_thrice_daily_scheduled_events' ); //v2.0.0.2, just in case
	

	//test test test   COMMENT TEMPORARILY
    if ((get_option('vtprd_deleteALL_on_UnInstall')) != 'yes') {
	   //error_log( print_r(  'vtprd UNINSTALL - exit002', true ) );
	   return;
	}



	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'vtprd\_%';" );
	
	// Delete posts + data.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'vtprd-rule';" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" ); 

	// Delete usermeta.
	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'vtprd\_%';" );
	
    //drop all tables
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_purchase_log ");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_purchase_log_product ");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_purchase_log_product_rule ");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_transient_cart_data ");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_lifetime_limits_purchaser ");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_lifetime_limits_purchaser_logid_rule ");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}vtprd_lifetime_limits_purchaser_rule ");

    //delete all custom Taxonomy entries
   //DELETE all rule category entries
   $terms = get_terms('vtprd_rule_category', 'hide_empty=0&parent=0' );
   if ( (is_array($terms)) &&
        (sizeof($terms) > 0) ) {
       foreach ( $terms as $term ) {
          wp_delete_term( $term->term_id, 'vtprd_rule_category' );
       }
   } 


    //delete custom Taxonomy
	$wpdb->delete(
		$wpdb->term_taxonomy,
		array(
			'taxonomy' => 'vtprd_rule_category',
		)
	);		


	// Delete orphan relationships.
	$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;" );

	// Delete orphan terms.
	$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;" );

	// Delete orphan term meta.
	if ( ! empty( $wpdb->termmeta ) ) {
		$wpdb->query( "DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;" );
	}


	// Clear any cached data that has been removed.
	wp_cache_flush();
    return;