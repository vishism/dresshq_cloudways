<?php
/*

*/


class VTPRD_Parent_Definitions {
	
	public function __construct(){
    
    define('VTPRD_PARENT_PLUGIN_NAME',                      'WooCommerce');
    define('VTPRD_EARLIEST_ALLOWED_PARENT_VERSION',         '2.0.14');  //all due to support for hook 'woocommerce_email_order_items_table' - requires the 2nd order_info variable...
    define('VTPRD_TESTED_UP_TO_PARENT_VERSION',             '2.1.9');
    //v1.1.5 begin ==>> replaced 'http//' with 'https//'
    define('VTPRD_DOCUMENTATION_PATH',                      'https://www.varktech.com/documentation/pricing-deals/introrule/');                                                                                                     //***
    define('VTPRD_INSTALLATION_INSTRUCTIONS_BY_PARENT',     'https://www.varktech.com/woocommerce/pricing-deals-for-woocommerce/?active_tab=instructions');
    define('VTPRD_PRO_INSTALLATION_INSTRUCTIONS_BY_PARENT', 'https://www.varktech.com/woocommerce/woocommerce-dynamic-pricing-discounts-pro/?active_tab=instructions');
    define('VTPRD_PURCHASE_PRO_VERSION_BY_PARENT',          'https://www.varktech.com/woocommerce/woocommerce-dynamic-pricing-discounts-pro/');
    define('VTPRD_DOWNLOAD_FREE_VERSION_BY_PARENT',         'wordpress.org/extend/plugins/pricing-deals-for-woocommerce/');
    define('VTPRD_SUPPORT_URL',                             'https://www.varktech.com/support/'); //v1.1.5 NEW
    //v1.1.5 end
    
    //html default selector locations in checkout where error message will display before.
    define('VTPRD_CHECKOUT_PRODUCTS_SELECTOR_BY_PARENT',    '.shop_table');        // PRODUCTS TABLE on BOTH cart page and checkout page
    define('VTPRD_CHECKOUT_ADDRESS_SELECTOR_BY_PARENT',     '#customer_details');      //  address area on checkout page    default = on

    define('VTPRD_CHECKOUT_BUTTON_ERROR_MSG_DEFAULT',        
         __('Based on previous discounted order(s), a Discount Limit has been exceeded. The total Discount for this order has been reduced. Please return to the Cart page to see the change in the discount, or hit the "Purchase" button a second time to complete the transaction.', 'vtprd')
    );
    
    global $vtprd_info, $vtprd_rule_type_framework, $wpdb;      

  
    define('VTPRD_TRANSIENT_CART_DATA',                   $wpdb->prefix.'vtprd_transient_cart_data');    //v2.0.2.0 new data table
    define('VTPRD_PURCHASE_LOG',                          $wpdb->prefix.'vtprd_purchase_log');      
    define('VTPRD_PURCHASE_LOG_PRODUCT',                  $wpdb->prefix.'vtprd_purchase_log_product');   
    define('VTPRD_PURCHASE_LOG_PRODUCT_RULE',             $wpdb->prefix.'vtprd_purchase_log_product_rule'); 
    

    //option set during update rule process
    if (get_option('vtprd_ruleset_has_a_display_rule') == true) {
      $ruleset_has_a_display_rule = get_option('vtprd_ruleset_has_a_display_rule');
    } else {
      $ruleset_has_a_display_rule = 'no';
    }

    //v1.1.1.2 begin
    //option set during update rule process
    if (get_option('vtprd_ruleset_contains_auto_add_free_product') == true) {
      $ruleset_contains_auto_add_free_product = get_option('vtprd_ruleset_contains_auto_add_free_product');
    } else {
      $ruleset_contains_auto_add_free_product = 'no';
    }
    //v1.1.1.2 end
    
    
    $default_short_msg  =  __('Short checkout message required', 'vtprd');
    $default_full_msg   =  __('Get 10% off Laptops Today! (sample)', 'vtprd');
    $default_coupon_msg =  __('Optional - Discount applied *only* with Coupon Code', 'vtprd'); //v1.1.0.8

    $default_by_varname_example =  "<span class=\"varname_ex_larger\"> \"large | red + extra large | blue\" </span>"; //v1.1.8.0
    $default_by_varname_msg =  'Enter attribute names:  &nbsp; use &nbsp; " | "  &nbsp; to separate values ,  &nbsp; " + "  &nbsp; to combine values'; //v2.0.0 msg changed
        //v1.1.7.1a warning used for edit comparison ONLY - NO HTML CHARACTERS
    $default_by_varname_msg_warning =  __('combining values', 'vtprd'); //v1.1.7.1a - used for comparison during update process

      //v2.0.0.5 begin
      // only works with PRO
      //similar code ALSO in vtprd-rules-ui.php , so that the new post_type is also added to the PRODUCT search
      /*
         	If added PRODUCT type from additional Plugins needed
          Find all the Product types needed in your additional plugins, by searching for: "register_post_type".
          In the "return" statement below, string them together as the example suggests
           - return ('product-type1'); for 1
           - return ('product-type1','product-type2' etc ); for more than 1
           
        	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
        	add_filter( 'vtprd_use_additional_product_type', function() { return (array('product-type1','product-type2')); } );
           
          THIS FILTER will add your added PRODUCT type to BOTH the PRODUCT selector AND the Pricing Deal Category selector
          - so if you want a group of products to be included in a rule, you can either list them in the PRODUCT selector,
          or make sure they participate in a Pricing Deal Category, which is then selected in your desired rule.
       */  
      $product_type_array = array('product');  
        
      if ( (apply_filters('vtprd_use_additional_product_type',FALSE )) &&
           (defined('VTPRD_PRO_VERSION')) ) {  
        $additional_product_types = apply_filters('vtprd_use_additional_product_type',FALSE );
        /*
         	If added PRODUCT type from additional Plugins needed
                   Find all the Product types needed in your additional plugins, by searching for: "register_post_type".
                   In the "return" statement below, string them together as the example suggests
           
        	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
        	add_filter( 'vtprd_use_additional_product_type', function() { return (array('product-type1','product-type2')); } ); 
          
                  
        foreach ($additional_product_types as $key => $additional_product_type) {
           $product_type_array[] = $additional_product_type;
        }
        */
        $product_type_array = array_merge($product_type_array,$additional_product_types);
      } 
    
    //v2.0.0.5 end
    
    $vtprd_info = array(                                                                    
      	'parent_plugin' => 'woo',
      	'parent_plugin_taxonomy' => 'product_cat',
        'parent_plugin_taxonomy_name' => 'Product Categories',
        'parent_plugin_cpt' => 'product', //v2.0.0.6 doesn't work with array
        'applies_to_post_types' => 'product', //v2.0.0.6 doesn't work with array       
        //'parent_plugin_cpt' => $product_type_array, //v2.0.0.5
        //'applies_to_post_types' => $product_type_array, //rule cat only needs to be registered to product, not rule as well...  //v2.0.0.5
        'rulecat_taxonomy' => 'vtprd_rule_category',
        'rulecat_taxonomy_name' => 'Pricing Deals Rules',
        'cart_discount_processing_save_unit_price' => 0,  //v1.0.7.4  used to store unit price if changed, by rule, to work with previous catalog discount
        
        //element set at filter entry time, to differentiate cart processing from price request/template tag processing
        'current_processing_request' => 'cart',  //'cart'(def) / 'display'
        
        //v1.0.7.4  If a coupon has been presented where individual_use is restricted, Our Coupon (cart discount) MAY NOT RUN
        'skip_cart_processing_due_to_coupon_individual_use' => false, //v1.0.7.4 

        'product_session_info' => '',
        /*
        array (
            'product_list_price'           => $vtprd_cart->cart_items[0]->db_unit_price_list,
            'product_list_price_html_woo'  => $db_unit_price_list_html_woo,
            'product_unit_price'           => $vtprd_cart->cart_items[0]->db_unit_price,
            'product_special_price'        => $vtprd_cart->cart_items[0]->db_unit_price_special,
            'product_discount_price'       => $vtprd_cart->cart_items[0]->discount_price,
            'product_discount_price_html_woo'  => 
                                              $discount_price_html_woo,
            
            //v1.0.7.4 begin
            'product_discount_price_incl_tax_woo'      =>
                                              $price_including_tax,
            'product_discount_price_excl_tax_woo'      =>
                                              $price_excluding_tax,
            'product_discount_price_incl_tax_html_woo'      =>
                                              $price_including_tax_html,
            'product_discount_price_excl_tax_html_woo'      =>
                                              $price_excluding_tax_html,                                              
            'product_discount_price_suffix_html_woo'   =>
                                              $price_display_suffix, 
            //v1.0.7.4 end
                                                        
            'product_is_on_special'        => $vtprd_cart->cart_items[0]->product_is_on_special,
            'product_yousave_total_amt'    => $vtprd_cart->cart_items[0]->yousave_total_amt,     
            'product_yousave_total_pct'    => $vtprd_cart->cart_items[0]->yousave_total_pct,    
            'product_rule_short_msg_array' => $short_msg_array,        
            'product_rule_full_msg_array'  => $full_msg_array,
            'product_has_variations'       => $product_variations_sw,
            'session_timestamp_in_seconds' => time(),
            'user_role'                    => vtprd_get_current_user_role(),
            'product_in_rule_allowing_display'  => $vtprd_cart->cart_items[0]->product_in_rule_allowing_display, //if not= 'yes', only msgs are returned 
            'show_yousave_one_some_msg'    => $show_yousave_one_some_msg, 
            //for later ajaxVariations pricing
            'this_is_a_parent_product_with_variations' => $vtprd_cart->cart_items[0]->this_is_a_parent_product_with_variations,            
            'pricing_by_rule_array'        => $vtprd_cart->cart_items[0]->pricing_by_rule_array,
            'product_id'                   => $product_id,    //v1.0.9.0  
            'product_has_addons'           => $product_has_addons    //v1.1.1 
                                                 
          ) ;
         */
         'ruleset_has_a_display_rule'     => $ruleset_has_a_display_rule,
         'ruleset_contains_auto_add_free_product' => $ruleset_contains_auto_add_free_product,  //v1.1.1.2 
        
        //elements used in vtprd-apply-rules.php at the ruleset processing level
        //'at_least_one_rule_condition_satisfied' => 'no',
        'inPop_conditions_met' => 'no',
        'actionPop_conditions_met' => 'no',
        'maybe_auto_add_free_product_count' => 0,
        
        //computed discount total used in display
 //       'cart_discount_total'  => 0.00,
        'cart_rows_at_checkout_count' => 0,
        'after_checkout_cart_row_execution_count' => 0,
        'product_meta_key_includeOrExclude' => '_vtprd_includeOrExclude',
        /*
          array (
            'includeOrExclude_option'    => '',
            'includeOrExclude_checked_list'    => array( ) //this is the checked list...
          )
         */
		    'inpop_variation_checkbox_total' => 0,
        'on_checkout_page' => '', //are we on the checkout page?
        'coupon_num' => '',
        'checkout_validation_in_process' => 'no', //are we in checkout_form_validation?
        'ajax_test_value' => '',
       // 'coupon_code_discount_deal_title' => $coupon_code_discount_deal_title, //v2.0.0.5 removed
        
        'cart_color_cnt' => '',
        'rule_id_list' => '',
        'line_cnt' => 0,
        'action_cnt'  => 0,
        'bold_the_error_amt_on_detail_line'  => 'no',
        'currPageURL'  => '',
        'woo_cart_url'  => '',
        'woo_checkout_url'  => '',
        'woo_pay_url'  => '',
    //    'woo_single_product_name'  => '',     //used in auto add function ONLY, if single product chosen for autoadd
    //    'woo_variation_name_list_by_id'  => '',     //used in auto add function ONLY
        /*
          array (     //KEYED to variation_id, from the original checkbox load...
            'variation_product_name_attributes'    => array( ) 
          )
         */                
        
        //elements used at the ruleset/product level 
        'purch_hist_product_row_id'  => '',              
        'purch_hist_product_price_total'  => '',      
        'purch_hist_product_qty_total'  => '',          
        'get_purchaser_info' => '',          
        'purch_hist_done' => '',
        'purchaser_ip_address'  => vtprd_get_purchaser_ip_address(), //v1.0.7.4    >>> must be here!!, //v1.1.5 changed below   //v2.0.2.0
        'default_short_msg' => $default_short_msg,
        'default_full_msg'  => $default_full_msg,
        'user_is_tax_exempt'  => '',  //v1.0.9.0
        'product_catalog_price_array' => array (),
        'previous_auto_add_array' => '',  //v1.1.0.6  added - init to spaces so is_array test can be used
        'default_coupon_msg'  => $default_coupon_msg, //v1.1.0.8
        'coupon_codes_array' => array(),   //v1.1.0.9
        'cart_has_catalog_discounts' => false,   //v1.1.1  reset each time the cart is processed
        'default_by_varname_msg'  => $default_by_varname_msg, //v1.1.7.1a
        'default_by_varname_msg_warning'  => $default_by_varname_msg_warning, //v1.1.7.1a
        'default_by_varname_example'  => $default_by_varname_example, //v1.1.7.1a
        //v2.0.0 begin
        'data_update_options_done_array'  => array ( 
            'required_updates'  => array (
                '2.0.0 Rule conversions'      => true,
                '2.0.0.7 Auto Coupon Label'   => true,   //v2.0.0.7 
                '2.0.0.9 Remove Extra JS'     => true,    //v2.0.0.9 
                '2.0.2.0 Rule conversions'    => true    //2.0.2.0             
            ),
            'optional_updates'  => array (
                '2.0.0 Create Tables'         => true, //fixes a problem from v1.1.8.1
                '2.0.0 Alter Column'          => true  //changes the column def pre v1.1.8.1 
            )
        ),

        //BRANDS         
        /* ********************************
        Pricing Deals Pro has built-in support for the following list of BRANDS Plugins .
        There's also the 'vtprd_brands_taxonomy_filter', which allows you to use ANY
        nominated custom taxonomy at the BRANDS selector 
        
        Here's what we're prepared for: 
          
          Product Brands For WooCommerce
          https://wordpress.org/plugins/product-brands-for-woocommerce/
          taxonomy = 'product_brands'
          
          Perfect WooCommerce Brands
          https://wordpress.org/plugins/perfect-woocommerce-brands/
          taxonomy = 'pwb-brand'
          
          Brands for WooCommerce
          https://wordpress.org/plugins/brands-for-woocommerce/
          taxonomy = 'berocket_brand'
    
          YITH WooCommerce Brands Add-On
          https://wordpress.org/plugins/yith-woocommerce-brands-add-on/
          taxonomy = 'yith_product_brand';
          
          Ultimate WooCommerce Brands
          https://wordpress.org/plugins/ultimate-woocommerce-brands/
          taxonomy = "product_brand"
          
          Woocommerce Brand
          https://wordpress.org/plugins/wc-brand/
          taxonomy = 'product_brand'  
          
        */   
        'brands_taxonomy_array'  => array("product_brand","pwb-brand","berocket_brand","product_brands","yith_product_brand"),
        'yousave_cart_total_amt'  => 0    //v2.0.0 G solution , contains $vtprd_cart->yousave_cart_total_amt , used in COUPON discounting   
          //v2.0.0 end             
             
      ); //end vtprd_info      
      
    if ($vtprd_info['purchaser_ip_address'] <= ' ' ) {
      $vtprd_info['purchaser_ip_address'] = vtprd_get_purchaser_ip_address();  //v1.1.5 changed below    //v2.0.2.0
    } 
 
    //load up 'user_is_tax_exempt'   //v1.0.9.0 
    //vtprd_get_current_user_role();   //v1.0.9.0 

                                                                                            
	}

	 
} //end class
$vtprd_parent_definitions = new VTPRD_Parent_Definitions;

  //v1.1.5 BEGIN 
  //NEEDS TO BE HERE

  function  vtprd_get_purchaser_ip_address() {    //v2.0.2.0 renamed
    
    /* 
        //IF YOU MUST OVERRIDE THE IP ADDRESS ON A PERMANENT BASIS
        //USE SOMETHING LIKE https://www.site24x7.com/find-ip-address-of-web-site.html to find your website IP address (**NOT** your CLIENT ip address)
        //copy code begin
        add_filter('vtprd_override_with_supplied_ip_address', 'override_with_supplied_ip_address', 10 );        
        function override_with_supplied_ip_address() {  return 'YOUR IP ADDRESS HERE'; }
        //copy code end                
    */
    //v2.0.0.9 begin
    $supplied_IP = apply_filters('vtprd_override_with_supplied_ip_address',FALSE);
    if ($supplied_IP) {
      //error_log( print_r(  '$supplied_IP = ' .$supplied_IP, true ) );
      return $supplied_IP;
    }
    //v2.0.0.9 end
    
    
    /*  // IP address license check can fail if you have copied your whole site with options table from one IP address to another
        // ==>>>>> only ever do this with a SINGLE RULE SCREEN ACCESS, 
        // then remove from your theme functions.php file ==>>>>> heavy server resource cost if executed constantly!!!!!!!
        //copy code begin
        add_filter('vtprd_force_new_ip_address', 'force_new_ip_address', 10 );        
        function force_new_ip_address() {  return 'yes'; } 
        //copy code end
    */
    
    /*
    //v2.0.2.0 removed
    if (apply_filters('vtprd_force_new_ip_address',FALSE) ) {
      $skip_this = true;
    } else {
      $vtprd_ip_address = get_option( 'vtprd_ip_address' );
      if ($vtprd_ip_address) {
        return $vtprd_ip_address;
      }    
    }
    */

    
    //THIS ONLY OCCURS WHEN THE PLUGIN IS FIRST INSTALLED!
    // from http://stackoverflow.com/questions/4305604/get-ip-from-dns-without-using-gethostbyname
    
    //v1.1.6.3 refactored, put in test for php version
    $php_version = phpversion();
    if ( version_compare( $php_version, '5.3.1', '<' ) ) {
      $vtprd_ip_address = $_SERVER['SERVER_ADDR'];
    } else {    
      //v2.0.2.0 begin
      /*
      $host = gethostname();
      $query = `nslookup -timeout=$timeout -retry=1 $host`;
      if(preg_match('/\nAddress: (.*)\n/', $query, $matches)) {
        $vtprd_ip_address =  trim($matches[1]);
      } else {
        $vtprd_ip_address = gethostbyname($host);
      } 
      */
      // from https://www.w3resource.com/php-exercises/php-basic-exercise-5.php
      //whether ip is from share internet
      if (!empty($_SERVER['HTTP_CLIENT_IP']))   
        {
          $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
      //whether ip is from proxy
      elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  
        {
          $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
      //whether ip is from remote address
      else
        {
          $ip_address = $_SERVER['REMOTE_ADDR'];
        }

      $vtprd_ip_address = $ip_address;
      //v2.0.2.0 end  
    }	

    
    //v2.0.2.0 removed   update_option( 'vtprd_ip_address', $vtprd_ip_address );
    
    return $vtprd_ip_address;

  }
  //v1.1.5 END
  