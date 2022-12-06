<?php
/**
 * globally available functions
 */


/**
 * get instance of WP-Lister object
 * @return WPL_WPLister
 */
function WPLE() {
    return WPL_WPLister::get_instance();
}


// custom tooltips
function wplister_tooltip( $desc ) {
	if ( defined('WPLISTER_RESELLER_VERSION') ) {
	    $desc = apply_filters_deprecated( 'wplister_tooltip_text', array($desc), '2.8.4', 'wple_tooltip_text' );
	    $desc = apply_filters( 'wple_tooltip_text', $desc );
    }
	if ( defined('WPLISTER_RESELLER_VERSION') && apply_filters_deprecated( 'wplister_reseller_disable_tooltips', array(false), '2.8.4', 'wple_reseller_disable_tooltips' ) ) return;
	if ( defined('WPLISTER_RESELLER_VERSION') && apply_filters( 'wple_reseller_disable_tooltips', false ) ) return;
    echo '<img class="help_tip" data-tip="' . esc_attr( $desc ) . '" src="' . WPLE_PLUGIN_URL . 'img/help.png" height="16" width="16" />';
}

// fetch eBay ItemID for a specific product_id / variation_id
// Note: this function does not return archived listings
function wplister_get_ebay_id_from_post_id( $post_id ) {
	$ebay_id = WPLE_ListingQueryHelper::getEbayIDFromPostID( $post_id );
	return $ebay_id;
}

// fetch fetch eBay items by column
// example: wple_get_listings_where( 'status', 'changed', 'auction_title', SORT_ASC|SORT_DESC );
function wple_get_listings_where( $column, $value, $sort_by = null, $sort_direction = null ) {
	return WPLE_ListingQueryHelper::getWhere( $column, $value, $sort_by, $sort_direction );
}


/**
 * Show admin message
 * @param $message
 * @param string $type info, warn or error
 * @param array $params accepts persistent and dismissible keys
 *
 * $params keys:
 * bool params[persistent]  Set to TRUE to store message as a transient to be shown on the next page load
 * bool params[dismissible] Set to TRUE to make the notification dismissible
 */
function wple_show_message( $message, $type = 'info', $params = [] ) {
    if ( is_bool( $params ) ) {
        $persistent = $params;
        $params['persistent'] = $persistent;
    }

    $params = wp_parse_args( $params, array(
        'persistent'    => false,
        'dismissible'   => false,
    ));

	WPLE()->messages->add_message( $message, $type, $params );
}

// Return TRUE if the current request is done via AJAX
function wple_request_is_ajax() {
    return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) || ( isset($_POST['action']) && ( $_POST['action'] == 'editpost' ) ) ;
}

// Return TRUE if the current request is done via the REST API
function wple_request_is_rest() {
    return ( (defined( 'WC_API_REQUEST' ) && WC_API_REQUEST) || (defined( 'REST_REQUEST' ) && REST_REQUEST) );
}

// Shorthand way to access a product's property
function wple_get_product_meta( $product_id, $key ) {
    //return WPL_WooProductDataStore::getProperty( $product_id, $key );
    if ( is_object( $product_id ) ) {
        $product_id = is_callable( array( $product_id, 'get_id' ) ) ? $product_id->get_id() : $product_id->id;
    }

    $product = ProductWrapper::getProduct( $product_id );

    // Check for a valid product object
    if ( ! $product || ! $product->exists() ) {
        return false;
    }

    if ( $key == 'product_type' && is_callable( array( $product, 'get_type' ) ) ) {
        return call_user_func( array( $product, 'get_type' ) );
    }

    // custom WPLE postmeta
    if ( substr( $key, 0, 5 ) == 'ebay_' ) {
        return get_post_meta( $product_id, '_'. $key, true );
    }

    if ( is_callable( array( $product, 'get_'. $key ) ) ) {
        return call_user_func( array( $product, 'get_'. $key ) );
    } else {
        return $product->$key;
    }
}


function wple_get_order_meta( $order_id, $key ) {
    $order = $order_id;
    if ( ! is_object( $order ) ) {
        $order = wc_get_order( $order_id );
    }

    if ( is_callable( array( $order, 'get_'. $key ) ) ) {
        return call_user_func( array( $order, 'get_'. $key ) );
    } else {
        return $order->$key;
    }
}

/**
 * Our own version of wc_clean to prevent errors in case WC gets deactivated
 * @param  array|string $var
 * @return array|string
 */
function wple_clean( $var ) {
    if ( is_callable( 'wc_clean' ) ) {
        return wc_clean( $var );
    } else {
        if ( is_array( $var ) ) {
            return array_map( 'wple_clean', $var );
        } else {
            return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
        }
    }
}


//
// Template API functions
//

function wplister_register_custom_fields( $type, $id, $default, $label, $config = array() ) {
    global $wpl_tpl_fields;
    if ( ! $wpl_tpl_fields ) $wpl_tpl_fields = array();

    if ( ! $type || ! $id ) return;

    // create field
    $field = new stdClass();
    $field->id      = $id;
    $field->type    = $type;
    $field->label   = $label;
    $field->default = $default;
    $field->value   = $default;
    $field->slug    = isset($config['slug']) ? $config['slug'] : $id;
    $field->options = isset($config['options']) ? $config['options'] : array();

    // add to template fields
    $wpl_tpl_fields[$id] = $field;

}

//
// Scheduler functions
//

/**
 * Schedule a ReviseItem call so it runs in the background
 * @param int $id
 * @param int $account_id
 */
function wple_schedule_revise_items( $id, $account_id ) {
    WPLE()->logger->info( 'wple_schedule_revise_items for #'. $id );
    as_schedule_single_action( null, 'wple_do_background_revise_items', array( $id, $account_id ), 'wple' );
}

/**
 * Schedule a ReviseInventoryStatus call so it runs in the background
 * @param int $id
 * @param int $account_id
 * @param int $order_id
 */
function wple_schedule_revise_inventory( $id, $account_id = null, $order_id = null ) {
    WPLE()->logger->info( 'wple_schedule_revise_inventory for #'. $id );

    // Use async background action instead of single_action
    //as_schedule_single_action( null, 'wple_do_background_revise_items', array( $id, $account_id, true, $order_id ), 'wple' );
    wple_enqueue_async_action( 'wple_do_background_revise_items', array( $id, $account_id, true, $order_id, true ), 'wple' );
}

/**
 * Customize the parameters in Action-Scheduler to get larger queues processed quicker
 */
function wple_action_scheduler_settings() {
    if ( !function_exists( 'as_get_scheduled_actions' ) ) {
        return;
    }

    if ( ListingsModel::countQueuedChangedListings() > 50 ) {
        // Set time limit to the max_execution_time. Fallback to 60 seconds if unable to get the max_execution_time value
        add_filter( 'action_scheduler_queue_runner_time_limit', function() {
            $max_time_limit = @ini_get('max_execution_time');
            $max_time_limit = ( $max_time_limit ) ? $max_time_limit : 60;
            return $max_time_limit;
        }, 100 );

        // Increase the batch size
        add_filter( 'action_scheduler_queue_runner_batch_size', function() {
            return 50;
        }, 100 );

        add_filter( 'action_scheduler_queue_runner_concurrent_batches', function() {
            return 5;
        });
    }

}

/**
 * Revises a listing. This is usually ran in the background through ActionScheduler.
 * @param int       $id The WP-Lister listing ID to revise
 * @param int|null  $account_id The account ID of the listing to revise
 * @param bool      $reviseInventoryOnly Pass TRUE to only run a ReviseInventoryStatus call instead of the default ReviseItem
 * @param int|null  $order_id If provided, an order note will be added to the order with the status of the revision
 * @param bool      $force Set to TRUE to push revision regardless of the Background Revisions setting
 */
function wple_do_background_revise_items( $id, $account_id = null, $reviseInventoryOnly = false, $order_id = null, $force = false ) {
    WPLE()->logger->info( 'wple_do_background_revise_items for #'. $id );
    WPLE()->logger->info( 'id: '. $id .' / account: '. $account_id .' / order: '. $order_id );
    WPLE()->logger->info( 'reviseInventoryOnly: '. ($reviseInventoryOnly) ? 'true' : 'false' );

    if ( !$force && ! get_option( 'wplister_background_revisions', 0 ) ) {
        WPLE()->logger->info( 'Skipping bg revisions because it is disabled in the settings' );
        return;
    }

    $listing = ListingsModel::getItem( $id );

    if ( !$account_id ) {
        $account_id = $listing['account_id'];
    }

    WPLE()->initEC( $account_id );
    $ec = WPLE()->EC;
    $sm = new ListingsModel();

    if ( $reviseInventoryOnly ) {
        //$results = $sm->reviseInventoryStatus( $id, WPLE()->EC->sesssion );
        $ec->reviseInventoryForListing( $id, true );
        $ec->closeEbay();
        $results = $ec->lastResults;
    } else {
        $results = $ec->reviseItems( $id );
    }

    $ec->closeEbay();

    WPLE()->logger->info( 'wple_do_background_revise_items #'. $id .' complete' );
    WPLE()->logger->info( print_r( $results, 1 ) );

    if ( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( $ec->isSuccess ) {
            $order->add_order_note( sprintf(__( 'eBay inventory was updated successfully for <em>%s</em>.', 'wp-lister-for-ebay' ), $listing['auction_title'] ) );
        } else {
            // isSuccess is false when WPLE fails to revise a listing because it had already been ended so check for the ended status first
            $listing = ListingsModel::getItem( $id );

            if ( $listing['status'] == 'ended' || $listing['status'] == 'sold' ) {
                // No need to revise because the listing has already been ended
                $order->add_order_note( sprintf(__( 'eBay listing has been marked as ended for <em>%s</em>.', 'wp-lister-for-ebay' ), $listing['auction_title'] ) );
            } else {
                WPLE()->logger->error('EC::lastResults:' . print_r($ec->lastResults,1) );
                // set a max of 10 revision retries per order
                $revision_retries = get_post_meta( $order_id, '_wple_stock_revision_retries', true );

                if ( ! $revision_retries ) $revision_retries = 0;

                if ( $revision_retries <= 10 ) {
                    $order->add_order_note( sprintf( __( 'There was a problem revising the inventory on eBay for <em>%s</em>! Revision will be retried in 5 minutes. Please check the database log and contact support.', 'wp-lister-for-ebay' ), $listing['auction_title'] ) );

                    // Schedule a retry of the inventory sync in 5 minutes
                    as_schedule_single_action( time() + 300, 'wple_do_background_revise_items', array( $id, $account_id, true, $order_id, true ), 'wple' );

                    $revision_retries++;
                    update_post_meta( $order_id, '_wple_stock_revision_retries', $revision_retries );
                } else {
                    $order->add_order_note( sprintf( __( 'There was a problem revising the inventory on eBay for <em>%s</em> and the revision retry limit has been reached. Please check the database log and contact support.', 'wp-lister-for-ebay' ), $listing['auction_title'] ) );
                }
            }
        }
    }
} // wple_do_background_revise_items()

// Schedule an as-soon-as-possible task to revise $listing_id
function wple_async_revise_listing( $listing_id ) {
    WPLE()->logger->info("Async revise listing #{$listing_id}");

    if ( WPLE()->isStagingSite() ) {
        WPLE()->logger->info( 'Staging site pattern match! Skipping.' );
        return;
    }

    if ( ! get_option( 'wplister_background_revisions', 0 ) ) {
        WPLE()->logger->info('Background revisions disabled. Skipping.');
        return;
    }

    if ( as_next_scheduled_action( 'wple_revise_item', array( $listing_id ), 'WPLE' ) ) {
        WPLE()->logger->info('Revise schedule found for listing. Skipping.');
        return;
    }

    wple_enqueue_async_action( 'wple_revise_item', array( $listing_id ), 'WPLE' );
    WPLE()->logger->info('Revision scheduled');
}

/**
 * Wrapper function for as_enqueue_async_action since it is not available in some old WC installations. If the async
 * function is not available, as_schedule_single_action() is called instead and passing in the current time so it is triggered
 * on the next cron run.
 *
 * @param string $hook The hook to trigger.
 * @param array  $args Arguments to pass when the hook triggers.
 * @param string $group The group to assign this job to.
 * @return int The action ID.
 */
function wple_enqueue_async_action( $hook, $args = array(), $group = '' ) {
    if ( function_exists( 'as_enqueue_async_action' ) ) {
        return as_enqueue_async_action( $hook, $args, $group );
    } else {
        return as_schedule_single_action( time(), $hook, $args, $group );
    }
}

/**
 * Get all registered custom attributes.
 *
 * @return stdClass[]
 */
function wple_get_custom_attributes() {
    $wpl_custom_attributes = array();
    $custom_attributes = apply_filters_deprecated( 'wplister_custom_attributes', array(array()), '2.8.4', 'wple_custom_attributes' );
    $custom_attributes = apply_filters( 'wple_custom_attributes', $custom_attributes );
    if ( is_array( $custom_attributes ) )
        foreach ( $custom_attributes as $attrib ) {
            $new_attribute = new stdClass();
            $new_attribute->name  = $attrib['id'];
            $new_attribute->label = $attrib['label'];
            $wpl_custom_attributes[] = $new_attribute;
        }

    return $wpl_custom_attributes;
}