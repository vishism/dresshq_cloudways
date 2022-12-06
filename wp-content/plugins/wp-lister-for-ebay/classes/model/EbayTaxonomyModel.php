<?php

/**
 * EbayTaxonomyModel class
 *
 * An interface for eBay's Taxonomy API that's used to get category specifics from eBay
 *
 */

require_once WPLE_PLUGIN_PATH . '/includes/ebay-rest-api/vendor/wplab/guzzle/src/functions_include.php';
require_once WPLE_PLUGIN_PATH . '/includes/ebay-rest-api/vendor/guzzlehttp/guzzle/src/functions_include.php';
require_once WPLE_PLUGIN_PATH . '/includes/ebay-rest-api/vendor/guzzlehttp/psr7/src/functions_include.php';
require_once WPLE_PLUGIN_PATH . '/includes/ebay-rest-api/vendor/guzzlehttp/promises/src/functions_include.php';
require_once WPLE_PLUGIN_PATH . '/includes/ebay-rest-api/vendor/autoload.php';

class EbayTaxonomyModel extends WPL_Model {

    private $api_url;

    private $wpl_account;

    /* @var Swagger\Client\Configuration */
    private $api_config;

    public function __construct( $wple_account_id ) {
        $account = WPLE()->accounts[ $wple_account_id ];

        $this->wpl_account  = $account;
        $this->api_url      = $account->sandbox_mode
            ? 'https://api.sandbox.ebay.com/commerce/taxonomy/v1'
            : 'https://api.ebay.com/commerce/taxonomy/v1';

        $this->api_config = Swagger\Client\Configuration::getDefaultConfiguration()
            ->setAccessToken($account->oauth_token)
            ->setHost( $this->api_url );
    }

    /**
     * @param string        $category_id
     * @param string|null   $category_tree_id
     * @return bool|\Swagger\Client\Model\Aspect[]
     */
    public function getItemAspectsForCategory( $category_id, $category_tree_id = null ) {
        WPLE()->logger->debug( 'getItemAspectsForCategory( '. $category_id .', '. $category_tree_id .')' );
        $cache_tree_id = $category_tree_id ? $category_tree_id : 0;
        $cache_key = 'wple_item_aspects_for_category_'. $category_id .'_'. $cache_tree_id;
        WPLE()->logger->debug( 'cache key: '. $cache_key );

        $aspects = get_transient( $cache_key );
        $aspects = false;

        // return cached response
        if ( $aspects ) {
            WPLE()->logger->debug( 'Returning aspects from cache:' . print_r( $aspects, 1 ) );
            return $aspects;
        }

        /**
         * Uncaught TypeError: Argument 1 passed to
         * Swagger\Client\Api\CategoryTreeApi::__construct() must be an instance of GuzzleHttp\ClientInterface or null, instance of WPLab\GuzzleHttp\Client given, called in /bitnami/wordpress/wp-content/plugins/wp-lister-ebay/classes/model/EbayTaxonomyModel.php on line 52 and defined in /bitnami/wordpress/wp-content/plugins/wp-lister-ebay/includes/ebay-rest-api/lib/Api/CategoryTreeApi.php:71
         */

        try {
            $api = new \Swagger\Client\Api\CategoryTreeApi( new WPLab\GuzzleHttp\Client(['timeout' => 600]), $this->api_config );

            if ( is_null( $category_tree_id ) ) {
                $wpl_site = WPLE_eBaySite::getSite( $this->wpl_account->site_id );
                $category_tree_id = $wpl_site->default_category_tree_id;
                WPLE()->logger->debug( 'category_tree_id from wpl_site: '. $category_tree_id );
            }

            $response = $api->getItemAspectsForCategory($category_id,$category_tree_id);

            // log request to db
            if ( get_option('wplister_log_to_db') == '1' ) {
                $dblogger = new WPL_EbatNs_Logger();
                $dblogger->updateLog( array(
                    'callname'    => 'getItemAspectsForCategory',
                    'request_url' => '',
                    'request'     => maybe_serialize( [$category_id, $category_tree_id] ),
                    'response'    => print_r($response,1),
                    'success'     => 'Success'
                ));
            }

            // $response sometimes is null as reported in #53525
            if ( $response ) {
                $aspects = $response->getAspects();
                WPLE()->logger->debug( 'Received aspects from the API: '. print_r( $aspects, 1 ) );
                set_transient( $cache_key, $aspects, 86400 );
                return $aspects;
            } else {
                WPLE()->logger->error('Error: Failed getting Category Aspects. WP-Lister could not connect to the API.');
                wple_show_message( __('Error: Failed getting Category Aspects. WP-Lister could not connect to the API.' ) );
                return false;
            }


        } catch ( Exception $e ) {
            WPLE()->logger->error('Error #'. $e->getCode() .': Failed getting Category Aspects. eBay said "'. $e->getMessage() .'".');
            wple_show_message( __('Error #'. $e->getCode() .': Failed getting Category Aspects. eBay said "'. $e->getMessage() .'".' ) );
            return false;
        }

    }

}