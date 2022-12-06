<?php

class VTPRD_Parent_Cart_Validation {
	
	public function __construct(){
  /*
  
 +++++++++++++++++++++++++++++
 Known Addons and Calculator plugin issue:
 
 1.
 Catalog rules only
   If (discounted base unit price + addon item) = list price of base unit,
   incorrect result. Only can occur when the base product has variations.
   Example: 
      10% discount
      variations list prices: $100-$130
      addon item 1 $10
      addon item 2 $40
      
      discounted base $90 + addon item 1 $10 = $100, list price of 1st item
      discounted base $90 + addon item 1 $40 = $130, list price of last item
      
      Get_price looks for price = list price.
      Due to Addons limitiation, there's no way to distinguish
      between the original call 
        (input price $100, output discount should be $90)
      and an example already discounted
        discounted base $90 + addon item 1 $10 = $100,
        (input price $100, output should be $100!!!!!)
        
  ALL BOlt-on plugins NEED an unique product key in the get_price call!!!  
  
  2. Calculator
  - multiple taxation issues, a problem with the Calculator plugin itself, when mix/match of incl/excl tax is used  
  - the correct taxation does NOT show up in the catalog, but in the cart it's all good. 
    
 ++++++++++++++++++++++++++++
  
  
  ----------------------------------
 Bolt-on Plugins and Missing Data/Data Massaging
 ----------------------------------
 Popular 'bolt-on' plugins like 'WC_Product_Addons' + 'WC_Measurement_Price_Calculator' create sub-products WITHOUT benefit of unique product IDs.

 
 +++++++++++++++++++++++++++++
 While the Calculator plugin sends an array with each product, Addons does not.
 Currently both of these plugins will only work in Coupon Discount mode.
 Coupon Discount mode is required if either of these plugins are installed and active.
 ++++++++++++++++++++++++++++
 
 This requires that wherever we interrogate the woo cart and compare it with vtprd cart,
 that comparison must be based on Cart Item Key.
 
 Further, in order for the WC_Product_Addons to pick up a Catalog discount,
 woocommerce_get_price  is required.
 With that involved, a much heavier processing load is incurred.
  
 Unfortuately for the WC_Measurement_Price_Calculator to work,
 woocommerce_get_price  is required AT ALL TIMES.
 
 Further, woocommerce_get_price **does not pass** the arrays which are required
 to uniquely identify the new bolt-on product uniquely.  Hilarity and complexity ensues.
 
 As a result, vtprd_maybe_get_price ONLY works successfully on getting a Catalog discount
 for the product.  ALL of the other woocommerce_get_price MAY produce incorrect results.
 
 An appreciable amount of additional code is needed to massage the incorrect  amounts
 passed back by vtprd_maybe_get_price .  This massaging takes place in:
 
vtprd_maybe_before_mini_cart
vtprd_maybe_before_calculate_totals
vtprd_maybe_cart_item_price_html 

vtprd_maybe_cart_item_subtotal
vtprd_maybe_order_formatted_line_subtotal

NOT ACTIVE:
vtprd_maybe_cart_subtotal
  
 ----------------------------------  

WC_Composite_Products  
 Composite PARENT products have unique IDs and are identifiable, and composite data comes down in all get_price calls.
I'd think just enlienving maybe_get-price for "if (class_exists('WC_Composite_Products'))" would do it 
HOWEVER, if a component product has a discount, there's a 'double' discount'
- would have to disallow the component discount if parent had one ==>> complicated, as the "child" comes down first. 
  
  
*Woo Bug - If Discount applied via Woo Coupon, and taxes are Off, WOO nonetheless
		may report the coupon amount **with tax added** 
		- if Tax Rates for the "Standard" Class 
			is set to apply a tax regardless of country code
		However, when transaction is processed, 
		the **correct discount amount has been applied**.  Go figure.
  */

    //*********************************************************************************************************
    /*
        There are a number of separate functions processed here.
        
        (1) Catalog discount on a single product
            - run at catalog display time against all display rules
            - data is stored in a product_id session variable for later use 
        (2) shortcode on-demand theme marketing messages
        (3) add-to-cart realtime discount computations
            - uses any display discounts if found
            - saves the current discount computation to session variable
            - adds the discount amount to the discount bucket, with the realtime-added couone type of pricing_deal_discount 
        (4) Mini-cart discount printing routine
        (5) checkout discount printing routine
        (6) discount amount prints/computes automatically since added to discount bucket...
    */
    //*********************************************************************************************************
    
    //---------------------------- 
    //CATALOG DISPLAY Filters / Actions
    //---------------------------- 
    
    //***************************************************
    //price request processing at catalog product display time
    //***************************************************                                                                           
    //*********************************************************************************************************
 
   
    //DISPLAY RULE INITIAL Price check - Catalog pricing filters/actions => returns HTML PRICING for display
    //********************************************************************************************************************
    
    //**********======================================================================================
    //NEED both these filters and the woocommerce_get_price filter to support both 
    //  standard products (priced in woocommerce_get_price in the catalog display)
    //      and 
    //  variation products (priced in one a variaty of the _html filters in AJAX)
    //**********======================================================================================
        
//v1.0.9.1  no globals here
//v1.0.9.1    global $vtprd_info, $vtprd_setup_options;  //v1.0.9.0
    
    //Only do these if there's an active display rule

//v1.0.9.1  moved if statement to function
//v1.0.9.1    if ($vtprd_info['ruleset_has_a_display_rule'] == 'yes') {   //v1.0.9.0
 
      //???v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_grouped_price_html',          array(&$this, 'vtprd_maybe_grouped_price_html'), 10, 2);
     
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_variable_sale_price_html',    array(&$this, 'vtprd_maybe_variable_sale_price_html'), 10, 2);
    
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //    add_filter('woocommerce_variable_price_html',         array(&$this, 'vtprd_maybe_variable_price_html'), 10, 2);  //v1.0.9.0
        
//v1.0.9.0 NOW UNNECESSARY??        add_filter('woocommerce_variation_price_html',        array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
      //v1.0.9.0 covered by 'woocommerce_get_variation_price_html'
      //  add_filter('woocommerce_variation_price_html',        array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        //normal get price
     //v1.0.9.0 covered by 'woocommerce_get_variation_price_html'
     //   add_filter('woocommerce_variation_sale_price_html',   array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
            
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_sale_price_html',             array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
      //  add_filter('woocommerce_price_html',                  array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
     
      //v1.0.9.0 covered by 'woocommerce_get_price_html'
       // add_filter('woocommerce_empty_price_html',            array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);

        //v1.0.9.0   MOVED HERE  ==>>  THIS IS EXECUTED as often as "woocommerce_get_price"
        //NOT needed for CART rules, but needed for catalog
 

        //**********************************
        //CATALOG discounts supplied in these TWO calls
        //**********************************
        add_filter('woocommerce_get_price_html',              array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);
        add_filter('woocommerce_get_variation_price_html',    array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);  //v1.1.1 changed to run same routine as get_price_html
        //add_filter('woocommerce_get_variation_price_html',    array(&$this, 'vtprd_maybe_catalog_variation_price_html'), 10, 2);  //v1.0.9.3 changes to sep function
        //**********************************
        
         
//v1.0.9.1    }

    // =====================++++++++++
    //get_price is used in the line subtotal, cart subtotal and total....
    //****************
    //v1.0.9.0 begin
    //****************
    //If discount is taken for UnitPrice, no further processing, handled in "before_calculate_totals"
    
/*  REMOVE THIS, BEING RUN TOO OFTEN
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {
//NOT needed for CART rules!!!!!!!!!!!!!!
      add_filter('woocommerce_get_price',                   array(&$this, 'vtprd_maybe_get_price'), 10, 2);    
      add_filter('woocommerce_get_price_html',                   array(&$this, 'vtprd_maybe_catalog_price_html'), 10, 2);

}    */

    // =====================++++++++++
    // inline-pricing unit pricing discount updates...
    // =====================++++++++++
      //v1.0.9.3  mini cart => manually load the new unit prices/catalog pricing, as needed
      
      /* v1.1.1 vtprd_maybe_before_mini_cart REMOVED HERE, moved to vtprd_cart_updated().
         For 2 reasons:
          (1) bug in catalog discount in discountCoupon, as of v1.1.1
          (2) If mini_cart not used in Theme, whole thing won't work!!!
      */      
       add_action('woocommerce_before_mini_cart',            array(&$this, 'vtprd_maybe_before_mini_cart'), 10, 1   );

      //run it all the time!

       add_action('woocommerce_before_calculate_totals',     array(&$this, 'vtprd_maybe_before_calculate_totals'), 10, 1  );
     
      //Pick up the plugin user tax exempt flag/and/or the Role cap "buy_tax_free"   and apply it UNIVERSALLY!! 
       add_action('wp_loaded',                               array(&$this, 'vtprd_do_loaded_housekeeping'), 99  ); //v2.0.2.0 priority changed to delay, for auto_add processing
       //add_action('woocommerce_init',                        array(&$this, 'vtprd_set_woo_customer_tax_exempt'), 10  );
       
       
       //v1.0.9.3  Supply discountUnitPrice crossout ==>> in both mini-cart and checkout. //v1.1.0.8 Also coupon-initiated discount processing.

       add_action('woocommerce_cart_item_price',             array(&$this, 'vtprd_maybe_cart_item_price_html'), 99, 3  );
       
       //v1.0.9.3  Unit Price 'you save' message for whole cart
 //      add_action('woocommerce_checkout_after_order_review', array(&$this, 'vtprd_maybe_unit_price_checkout_msg'), 10 );
        
    // =====================++++++++++
    //v1.0.9.0 end
    // =====================++++++++++
   

    //-END- CATALOG DISPLAY Filters / Actions

    
    
    //---------------------------- 
    //CART AND CHECKOUT Actions
    //----------------------------  

    //'woocommerce_cart_updated' RUNS EVERY TIME THE CART OR CHECKOUT PAGE DISPLAYS!!!!!!!!!!!!!
    add_action( 'woocommerce_cart_updated',                   array(&$this, 'vtprd_cart_updated') );   //AFTER cart update completed, all totals computed
    
    //---------------------------- 
    //v2.0.2.0 begin at WP_LOGIN time, the user_ID  CANNOT be returned.  SOOOOO  these have to be SPLIT
    
    //add_action( 'wp_login',                                   array(&$this, 'vtprd_update_on_login_change'), 10 );   //v1.0.8.4   re-applies rules on login immediately! //v1.1.7 removed 10,2
    //add_action( 'wp_logout',                                  array(&$this, 'vtprd_update_on_login_change'), 10 );   //v1.0.9.4   re-applies rules on logout immediately! //v1.1.7 removed 10,2
    
    add_action( 'wp_login',                                   array(&$this, 'vtprd_update_on_login'), 10 );  
    add_action( 'wp_logout',                                  array(&$this, 'vtprd_update_on_logout'), 10 ); 
      
    //v2.0.2.0 end
    //----------------------------          

    //*************************
    //COUPON PROCESSING
    //*************************
    //add or remove Pricing Deals 'dummy' fixed_cart coupon
    //   NEED BOTH to pick up going to view cart and going directly to checkout.  Exits quickly if already done.
//v1.0.9.1  moved if statement to function
//v1.0.9.1     if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      
      //-----------------------------------
      //v2.0.0 BEGIN - 
      //removed 'woocommerce_before_cart_table' , 'woocommerce_checkout_init'
      //add_action( 'woocommerce_before_cart_table',     array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);  //v1.1.0.1 chged to action      
      //add_action( 'woocommerce_checkout_init',         array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);  //v1.1.0.1 chged to action
      // replaced with:
      
      add_action( 'woocommerce_after_calculate_totals',         array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon'), 10);  //v1.1.0.1 chged to action
  
      //v2.0.0 END
      //-----------------------------------
        
      //change the value of the Pricing Deals 'dummy' coupon instance to the Pricing Deals discount amount
      //    v1.1.0.2 change priority to fall **before** other coupon plugins
      //    - same filter gets executed in Woo points and Rewards, and at 10,2 they fight (same thing happens if Pricing Deals falls afterwards)
      //v1.1.7 - this filter only works with < woo 3.0.0
      
      //v2.0.0 BEGIN - 
      //moved if here
      if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 - if so handled below
        add_filter( 'woocommerce_get_shop_coupon_data',  array(&$this, 'vtprd_woo_maybe_load_discount_amount_to_coupon'), 5,2); //v1.1.0.2 change priority to fall **before** other coupon plugins      
      }
      //v2.0.0 END
      
      //v1.1.7 - this filter only works with >= woo 3.0.0
      // UPDATE coupon amount, if 'coupon discount' selected - WOO 3.0.0 and beyond
          
    //before woo 3.0.0, handled in function vtprd_woo_maybe_load_discount_amount_to_coupon
      
      //v2.0.0 BEGIN - 
      //moved if here
      if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) { //check if older than version 3.0.0 - if so handled below
        add_action( 'woocommerce_coupon_loaded',         array(&$this, 'vtprd_woo_new_load_discount_amount_to_coupon'), 10,1);
      }
      //v2.0.0 END  
     
     
      //created in v1.0.9.0 , now no longer necessary
      //add_action( 'woocommerce_check_cart_items',               array(&$this, 'vtprd_maybe_update_coupon_on_check_cart_items'), 10 );   //v1.0.8.9 


//v1.0.9.1     }
    //*************************                                                                               
 
   /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++    */                       
    /*
    CHECKOUT PROCESS:
      - prep the counts at checkout page entry time
      - after each checkout row print, check to see if we're on the last one
          if so, compute and print discounts: both cart and display rules are reapplied to current unit pricing
      - at before_shipping_of_shopping_cart time, add discounts into coupon totals
      - post processing, store records in db    
    */

    //*************************************************
    // Apply discount to Discount total
    //*************************************************    
   //return apply_filters( 'woocommerce_get_discounted_price', $price, $values, $this );
   //add_filter( 'woocommerce_get_discounted_price',  array(&$this, 'vtprd_maybe_add_dscount_to_coupon_totals'), 10,3);
   
    //*************************************************
    // Print Discounts in Widget (after cart subtotal!!!)
    //*************************************************
    //  in templates/cart/mini-cart.php (exists in 2.0 ...)

    //allow routine to print some detail reporting as desired  v1.0.9.0 
//    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_action( 'woocommerce_widget_shopping_cart_before_buttons', array(&$this, 'vtprd_maybe_print_widget_discount'), 10, 1 ); 
//    }  
    //*************************************************
    // Print Discounts at Checkout time
    //*************************************************        
    //In woocommerce/templates/cart/cart'        
   // add_action( 'woocommerce_cart_contents', array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
//*************************************************     

  //allow routine to print some detail reporting as desired  v1.0.9.0 
  //  if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_action( 'woocommerce_after_cart_table', array(&$this, 'vtprd_maybe_print_checkout_discount'), 10, 1 );
  //  }
//************************************************* 

    //Reapply rules only if an error occurred during processing regarding lifetime rule limits...         
    //the form validation filter executes ONLY at click-to-pay time                                                                      
 
    //v2.0.2.0 PRO verification moved to function
    add_filter( 'woocommerce_before_checkout_process', array(&$this, 'vtprd_woo_validate_order'), 10);

    
    //still in development
    //add_action( 'woocommerce_before_checkout_process', array(&$this, 'vtprd_compute_tax_values'), 10, 1  );  //v1.1.8.0

    //*************************************************
    // Post-Purchase
    //*************************************************       
    //v1.0.9.0 Now applies to all uses of the cart
    //In classes/class-wc-checkout.php  function process_checkout() =>  just before the 'thanks' Order Acknowledgement screen    
    add_action('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_save_log_info' ), 10, 2);  //v1.0.9.0

    //Order Acknowledgment Email     
    //add discount reporting to customer email USING LOG INFO...
    //  $return = apply_filters( 'woocommerce_email_order_items_table', ob_get_clean(), $this );
    //      ob_get_clean() = the whole output buffer 
    //USING THIS filter in this way, puts discounts within the existing products table, after products are shown, but before the close of the table...     
//v1.0.9.1  moved if statement to function
//v1.0.9.1    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      add_filter('woocommerce_email_order_items_table', array( &$this, 'vtprd_post_purchase_maybe_email' ), 10,2);
//v1.0.9.1     }
    
    // PRIOR to WOO version ++2.13++ - won't work - as this filter only does not have $order_info (2nd variable) in prior versions
    
    //Order Acknowledgement screen
    //add discount reporting to thankyou USING LOG INFO...
    //DON'T USE ANYMORE  add_filter('woocommerce_order_details_after_order_table', array( &$this, 'vtprd_post_purchase_maybe_thankyou' ), 10,1);
    
    //do_action( 'woocommerce_thankyou', $order->id );  IS EXECUTED in WOO to place order info on thankyou page.   Put our stuff in front of thankyou.
//v1.0.9.1  moved if statement to function
//v1.0.9.1    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   //v1.0.9.0    not needed for inline-pricing
      //v1.1.0.3 changed to 'action'
      //add_filter('woocommerce_thankyou', array( &$this, 'vtprd_post_purchase_maybe_before_thankyou' ), -1,1); //put our stuff in front of thankyou
      add_action('woocommerce_thankyou', array( &$this, 'vtprd_post_purchase_maybe_before_thankyou' ), -1,1); //put our stuff in front of thankyou, including lifetime data saving
//v1.0.9.1    }
    //last filter/hook which uses the session variables, also nukes the session vars...
//    add_filter('woocommerce_checkout_order_processed', array( &$this, 'vtprd_post_purchase_maybe_purchase_log' ), 10,2);   
     
     //v1.1.0.3 add crossouts to subtotals at **checkout**
     add_filter('woocommerce_cart_item_subtotal', array( &$this, 'vtprd_maybe_cart_item_subtotal' ), 10,3);
          
     //v1.1.0.3 add crossouts to subtotals when order placed, to order-details and emails
     add_filter('woocommerce_order_formatted_line_subtotal', array( &$this, 'vtprd_maybe_order_formatted_line_subtotal' ), 10,3);
     
    //v1.1.8.0 begin
    //CART Deal Order History Discount Reporting 
    add_action('woocommerce_admin_order_items_after_line_items', array(&$this, 'vtprd_order_history_report'), 10, 1   );
    //v1.1.8.0 end

	} //end constructor
  

  //**************************************
  //  v2.0.2.0  function refactored 
  //**************************************
  //the form validation filter executes ONLY at click-to-pay time, just to access the global variables!!!!!!!!! 
	public function vtprd_woo_validate_order(){
       //error_log( print_r(  'Function begin - vtprd_woo_validate_order', true ) );
        
       if (!defined('VTPRD_PRO_DIRNAME')) { 
         return; 
       }
 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }
  
      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
	  }

      global $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
      vtprd_debug_options();      

      $data_chain = $this->vtprd_get_data_chain();
 
      if ( ($vtprd_setup_options['use_lifetime_max_limits'] != 'yes') || 
           ($vtprd_cart->lifetime_limit_applies_to_cart     != 'yes') ) { 
        return;        
      }

      vtprd_get_purchaser_info_from_screen();
        
      if ( sizeof($vtprd_cart->error_messages) == 0 ) {   //error msg > 0 = 2nd time through HERE, customer has blessed the reduction
          //reapply rules to catch lifetime rule logic using email and address info...
  
          $total_discount_1st_runthrough = $vtprd_cart->yousave_cart_total_amt;
          $vtprd_info['checkout_validation_in_process'] = 'yes';          
          //$vtprd_apply_rules = new VTPRD_Apply_Rules; //v1.1.1.3 removed, in favor of below!!!!!
          $this->vtprd_process_discount(); 
          $vtprd_info['checkout_validation_in_process'] = 'no'; //v1.0.8.0  

          if ( $vtprd_cart->yousave_cart_total_amt < $total_discount_1st_runthrough)  {   //2ND runthrough found additional lifetime limitations, need to alert customer   
                 //$vtprd_cart->error_messages are not being loaded, so load here 
                //REMOVE any line breaks, etc, which would cause a JS error !!
              $vtprd_cart->error_messages_processed = 'yes';               
              $message = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $vtprd_setup_options['lifetime_purchase_button_error_msg']); 
              wc_add_notice( $message, $notice_type = 'error' );  //supplies an error msg and prevents payment from completing 
          } 
      } 

      vtprd_set_transient_data_chain();

    return;   
  } 	


  //**************************************
  //* v1.1.8.0 New function  *****
  //**************************************
  //compute tax values to be used in messaging, email and in order history
  //logged post-purchase.
	public function vtprd_compute_tax_values(){
   //error_log( print_r(  'Function begin - vtprd_compute_tax_values', true ) );   
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    } 
    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return;   
		}   
    global $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
    vtprd_debug_options();    
    //Open Session Variable, get rules_set and cart if not there...
    $data_chain = $this->vtprd_get_data_chain();
    
    $cart_updated = false;  
    
    $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
    for($k=0; $k < $sizeof_cart_items; $k++) {
       if ($vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {
          $cart_updated = true;
          foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              $yousave_amt_taxed = $yousave_by_rule['yousave_amt'];
              
              $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$key]['yousave_amt_taxed'] = $yousave_amt_taxed;
          } //end for
          
          $vtprd_cart->cart_items[$k]->yousave_total_amt_taxed = $vtprd_cart->cart_items[$k]->yousave_total_amt;
          
        } //end if
      
    } //end for 
    
    //recreate data_chain for logging post-purchase.
    if ($cart_updated) { 
       //v2.0.0 begin -
       /*
       if (isset($_SESSION['data_chain'])) {
         $contents = $_SESSION['data_chain'];
         unset( $_SESSION['data_chain'], $contents ); 
       }
       */
       //$_SESSION['data_chain'] = false; //v2.0.2.0
       //v2.0.0 end 
      //v2.0.2.0 begin
      /*
      $contents_total   =   $woocommerce->cart->cart_contents_total;
      $applied_coupons  =   $woocommerce->cart->get_coupons(); //v2.0.0
      $data_chain = array();
      $data_chain[] = $vtprd_rules_set;
      $data_chain[] = $vtprd_cart;
      $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
      $data_chain[] = $contents_total;
      $data_chain[] = $applied_coupons;
      $data_chain[] = time(); //v2.0.0
      */
      
      //$_SESSION['data_chain'] = serialize($data_chain);
      //error_log( print_r(  'vtprd_set_transient_data_chain 003', true ) );
      vtprd_set_transient_data_chain();
      //v2.0.2.0 end
                      
      //error_log( print_r(  'Store data_chain 003', true ) );        
    }    
    return;
	}


  /*
  //v1.1.1 REFACTORED
  Used by AJAX to get variation prices during catalog display!!!
  ONLY called for parent product of variations, or for standalone products
  */
	public function vtprd_maybe_catalog_price_html($price_html, $product_info){    
   //error_log( print_r(  'Function begin - vtprd_maybe_catalog_price_html', true ) ); 
   //error_log( print_r(  '$price_html AT TOP= ' .$price_html, true ) );
   //error_log( print_r(  '$product_info AT TOP= ', true ) );
   //error_log( var_export($product_info, true ) );

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
   
    //v2.0.0 begin K solution
     //allow wp-admin calls for Catalog pricing in back end product pages!!
     global $post, $vtprd_info, $vtprd_setup_options;
     if ( ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') || 
          (defined( 'DOING_CRON' )) ) {
        return $price_html;          
     } 
     //v2.0.0 end K solution   
      
    //v2.0.0 end
      
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
   //error_log( print_r(  'return 001 , price=  ' .$price_html, true ) );         
      return $price_html;   
		}
 
  //+-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-+
 
  //IF < VERSION 2.5, DO ******  OLD  ************** FUNCTION
  //  ELSE CONTINUE
 
  //+-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-++-+-+-+-+ 
 
 
    $single_product_discount_price  = ''; //v1.1.5
 
    
    $price_html_original = $price_html; //save for later use
	 //v1.1.1 end

    vtprd_debug_options();  //v1.0.5  
 
      
    //v1.1.8.3 begin 
    //to turn on:  add_filter( 'vtprd_disallow_product_types', function() { return array('donation'); } );
	  // to list more than 1, list using: array('xxx','yyyy','zzz')
    /* Standard product types are:
        'simple'
        'variable'
        'grouped'
        'external'    
    */
    if ( apply_filters('vtprd_disallow_product_types',FALSE) ) {
       if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
          $product_type = $product_info->get_type();
       } else {
          $product_type = $product_info->product_type;
       }
       $disallow_these_types = apply_filters('vtprd_disallow_product_types',FALSE);

  //error_log( print_r(  'vtprd_disallow_product_types 003, product_type = ' .$product_type .'disallow array= ', true ) );
  //error_log( var_export($disallow_these_types, true ) ); 
  
       if ( (is_array($disallow_these_types)) &&
            (in_array($product_type, $disallow_these_types)) ) {
  //error_log( print_r(  'vtprd_disallow_product_types 003, SKIP THIS PRODUCT ', true ) );           
          return $price_html;
       } 
    }              
    //v1.1.8.3 end 
  
 
    //v1.1.7 begin
    // As of WOO 3.0.0, can't handle the object that comes down in some calls, no way to access ID (or so it seems)
    if (( version_compare( WC_VERSION, '3.0.0', '>=' ) ) && 
        (is_object ($product_info))) {
      $product_id  =  $product_info->get_id();
    } else {      
      if ( (isset($product_info->variation_id)) &&  //v1.1.1
           ($product_info->variation_id > ' ') ) {      
        $product_id  = $product_info->variation_id;
      } else { 
        if ($product_info->id > ' ') {
          $product_id  = $product_info->id;
        } else {
          $product_id  = $product_info->product_id;
        }     
      }    
    }
    //v1.1.7 end
 
/*
    if ( (isset($product_info->variation_id)) && //v1.1.1
         ($product_info->variation_id > ' ') ) {         
      $product_id  = $product_info->variation_id;
    } else { 
      if ($product_info->id > ' ') {
        $product_id  = $product_info->id;
      } else {
        $product_id  = $product_info->product_id;
      }     
    }
*/
    //v1.1.1 BEGIN
    /* sample execution for CLIENT
     
     ***********************************************************************************************************
     ------  Product Purchasability settings switch and Pricing Visibility/Price Custom Message, via Filter  -----
     ***********************************************************************************************************
     *     
     ** The following filter **
     *     works with the "Catalog Products Purchasability Display Options" on the Pricing Deal Settings page.
     *     
     *  The "Catalog Products Purchasability Display Options" setting can control whether 
     *       the add-to-cart button is available for a given product, 
     *  based on product wholesale setting and the customer role/capabilities
     *  
     *  However, in the situation where the add-to-cart button is removed for a given product,
     *  there may also be the desire to replace the displayed product price
     *  with spaces, or a custom message.

     *  Filter "vtprd_replace_price_with_message_if_product_not_purchasable" 
     *   replaces the Product Price with a custom message where appropriate.  
     *      (This custom message may also contain HTML.) 
     *            
     ***********************************************************************************************************                                                                       
      
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // *** add filter/function to the bottom of your ** Theme Functions file**
    //  (only works when setting "Catalog Products Purchasability Display Options" set to something other than "Show All") 
    //replaces price with message, if desired - message may include HTML
    add_filter('vtprd_replace_price_with_message_if_product_not_purchasable', 'do_replace_price_with_message_if_product_not_purchasable', 10, 1); 
    function do_replace_price_with_message_if_product_not_purchasable($return_smessage) {
      return 'Message to replace Price, if Product may not be Purchased by User';
    }    
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    */ 
    
    global $vtprd_setup_options;
    
    if ( ($vtprd_setup_options['wholesale_products_price_display'] == '' ) ||
         ($vtprd_setup_options['wholesale_products_price_display'] == 'noAction') ) {
         
      $skip_this = true;  
      
    } else {
    
      $replace_price_with_message_if_not_purchasable = apply_filters('vtprd_replace_price_with_message_if_product_not_purchasable','filterNotActive');
      if ($replace_price_with_message_if_not_purchasable == 'filterNotActive') { 
       
        $skip_this = true;  
        
      } else {  
      
        $display_product_pricing = $this->vtprd_maybe_woocommerce_is_purchasable(null, $product_id);   //v1.1.1.3      
        if (!$display_product_pricing) {  
          if ($replace_price_with_message_if_not_purchasable == '') {
            $replace_price_with_message_if_not_purchasable = ' ';
          }
          return $replace_price_with_message_if_not_purchasable;       
        }
        
      }
    }
    //v1.1.1 END

    //v1.0.9.3 begin
    //moved here to store vtprd_product_old_price, used in showing cart crossouts
    /*  v1.1.1 REMOVED => need to run this
    if ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') {   //v1.0.9.1 
      if(!isset($_SESSION['vtprd_product_old_price_'.$product_id])) { 

        $_SESSION['vtprd_product_old_price_'.$product_id]['price_html'] = $price_html;
      } 
      return $price_html;
    } 
    */
    //v1.0.9.3 end


    //v1.1.7 begin
    $is_a_variable_product = false;
    if ( version_compare( WC_VERSION, '3.0.0', '>=' )) { //v1.1.7 check if this is a variable product
      $product_type = $product_info->get_type();
      if ($product_type == 'variable' ) {
        $is_a_variable_product = true;
      }
    } else {
      if ($product_info->product_type == 'variable')  {
        $is_a_variable_product = true;       
      }
    }
    //v1.1.7 end

    
    //v1.1.1 REfactored, created if structure
    //v1.0.9.0 begin
    //if we already have the html price, no need to reprocess
    
     //if ($product_info->product_type == 'variable')  { //v1.1.7
     if ($is_a_variable_product) { //v1.1.7
       if(isset($_SESSION['vtprd_product_session_price_'.$product_id])) { 
         $oldprice_session = $_SESSION['vtprd_product_session_price_'.$product_id]['price_html'];
         $price_html = stripslashes($oldprice_session); //this is FORMATTED 
         
         //v2.0.0 begin K solution
         if (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) {
            //showing on a product LIST page, needs a break between 
            $price_html = str_replace('<ins>', '<br><ins>', $price_html);
         }
         //v2.0.0 end K solution
                           
  //error_log( print_r(  'return 002 , price=  ' .$price_html, true ) );        
         return $price_html; 
       } 
     } else {
      if (isset($_SESSION['vtprd_product_old_price_'.$product_id])) {    
         $oldprice_session = $_SESSION['vtprd_product_old_price_'.$product_id]['price_html'];
         $price_html = stripslashes($oldprice_session); //this is FORMATTED 
   //error_log( print_r(  'return 003 , price=  ' .$price_html, true ) );  
         
         //v2.0.0 begin K solution
         if (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) {
            //showing on a product LIST page, needs a break between 
            $price_html = str_replace('<ins>', '<br><ins>', $price_html);
         }
         //v2.0.0 end K solution
                 
         return $price_html;
       } 
     }    
     //v1.0.9.0 end


    //****************************
    // ALL OF THIS is only ever done once - thereafter the session variables are accessed.
    //****************************

    //if ($product_info->product_type == 'variable')  { //v1.1.7 
    if ($is_a_variable_product)  {  //v1.1.7 
       
      //-------------------------------
      //Parent of a variation product
      //-------------------------------
  //error_log( print_r(  '$is_a_variable_product  ' , true ) );      



      /*  //v1.1.7 moved below
      if (sizeof($product_info->children) == 0) {  
        $product_info->get_children();
        
  //error_log( print_r(  '$product_info children AFTTER GET CHILDREN= ', true ) );
  //error_log( var_export($product_info->children, true ) );        
        
      } 
     */
      
      $variation_children_array = array();
      
      //v1.1.7 begin
      if ( version_compare( WC_VERSION, '3.0.0', '>=' )) {
          $product_children = $product_info->get_children();
          $product_visible_children = $product_info->get_visible_children();
          
          $sizeof_children = sizeof($product_visible_children); 
          
  //error_log( print_r(  'variable $sizeof_cvisible_children  ' .$sizeof_children, true ) ); 
  //error_log( print_r(  'visible_children= ', true ) );
  //error_log( var_export($product_visible_children, true ) );          
          /*
                'children' => 
              array (
                0 => 2788,
                1 => 2789,
                2 => 2790,
                3 => 2791,
              ),
               'visible_children' => 
              array (
                0 => 2788,
                1 => 2789,
                2 => 2790,
                3 => 2791,
              ),
               'variation_attributes' => 
              array (
                'Sizes' => 
                array (
                  0 => 'small',
                  1 => 'medium',
                  2 => 'large',
                  3 => 'x-large',
                ),
              ),         
          */     
      }  else {
        if (sizeof($product_info->children) == 0) {  
          $product_info->get_children();
            //error_log( print_r(  '$product_info children AFTTER GET CHILDREN= ', true ) );
            //error_log( var_export($product_info->children, true ) );                 
        }
        if (isset($product_info->children['visible'])) {
          $sizeof_children = sizeof($product_info->children['visible']);
            //children array contains product ids of all variations
            /* 'visible' new with woo 2.4
               'children' => 
              array (
                'visible' => 
                array (
                  0 => 56,
                  1 => 57,
                  2 => 58,
                  3 => 59,
                ),
                'all' => 
                array (
                  0 => 56,
                  1 => 57,
                  2 => 58,
                  3 => 59,
                ),
            */ 
        } else {
          //pre woo 2.4
          $sizeof_children = sizeof($product_info->children);
        } 
      }
      //v1.1.7 end        
        
      $there_is_a_variation_discounted = false;
      
      $varParent_current_price_low    = 9999999999999;
      $varParent_current_price_high   = 0;  
      $varParent_discount_price_low   = 9999999999999;     
      $varParent_discount_price_high  = 0;    
      $single_product_current_price   = 0;   	
      $single_product_discount_price  = '';
      
      $children_discount_count = 0; //v1.1.1.3
      
      //sort for least/most expensive, create from/to structures
      for($k=0; $k < $sizeof_children; $k++) {
        //v1.1.7 begin
        if ( version_compare( WC_VERSION, '3.0.0', '>=' )) {
          $use_this_product_id = $product_visible_children[$k];
        } else { 
          if (isset($product_info->children['visible'])) {
            $use_this_product_id = $product_info->children['visible'][$k]; 
          } else {
            //pre woo 2.4
            $use_this_product_id = $product_info->children[$k]; 
          }
        }
        //v1.1.7 end
         
        //v1.1.6.3 begin  - get_tax_class
        //FINAL FIX Fatal error: Call to a member function get_tax_class() ...
        //==>> VISIBLE <<==  product attribute (array) is (RARELY) NOT THERE after a WOOCOMMERCE update!
        if (!$use_this_product_id) {
          //v1.1.7 begin
          if ( version_compare( WC_VERSION, '3.0.0', '>=' )) {
              $use_this_product_id = $product_children[$k];
          } else {
            if (isset($product_info->children['all'][$k])) {
              $use_this_product_id = $product_info->children['all'][$k];
            }
          }
          //v1.1.7 end
        }
        //v1.1.6.3 end
               
        vtprd_get_product_session_info($use_this_product_id);
        
      
        /* removed in favor of session var info BELOW
        //------------------------
        //TURN **OFF** all of My HOOKS
        //------------------------
        $_SESSION['do_no_actions'] = true; 
        //------------------------ 
        
        //Get current non-discounted price, Correctly Taxed!!          
        $product = new WC_Product( $use_this_product_id );
        $current_price_correctly_taxed = $product->price;
        //------------------------          
        //TURN **ON** all of My HOOKS
        //------------------------ 
        $contents = $_SESSION['do_no_actions'];
        unset( $_SESSION['do_no_actions'], $contents );
        //------------------------ 
        */
        $current_price_correctly_taxed = $vtprd_info['product_session_info']['product_list_price_catalog_correctly_taxed']; 
        
        
 //error_log( print_r(  'GET PRICE LOOP ', true ) ); 
 //error_log( print_r(  '$varParent_current_price_low BEGIN ITERATION = ' .$varParent_current_price_low, true ) );
 //error_log( print_r(  '$varParent_current_price_high BEGIN ITERATION = ' .$varParent_current_price_high, true ) );
   //error_log( print_r(  '$varParent_discount_price_low BEGIN ITERATION = ' .$varParent_discount_price_low, true ) );
 //error_log( print_r(  '$varParent_discount_price_high BEGIN ITERATION = ' .$varParent_discount_price_high, true ) );
 //error_log( print_r(  '$use_this_product_id = ' .$use_this_product_id .' $k= ' .$k, true ) ); 
 //error_log( print_r(  '$current_price_correctly_taxed = ' .$current_price_correctly_taxed, true ) ); 
    //error_log( print_r(  'product_session_info= ', true ) );
  //error_log( var_export($vtprd_info['product_session_info'], true ) );
  
     
       
 /*
        if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
          $hold_session_info = $vtprd_info['product_session_info'];
          
          //spoof the session fields so that show_shop_price will work
          $vtprd_info['product_session_info']['product_discount_price'] = $product->price;
          $vtprd_info['product_session_info']['product_discount_price_incl_tax_woo']   =  vtprd_get_price_including_tax($product_id, $product->price); 
          $vtprd_info['product_session_info']['product_discount_price_excl_tax_woo']   =  vtprd_get_price_excluding_tax($product_id, $product->price);
           
          $current_price_correctly_taxed = $this->vtprd_show_shop_price();
          
          //then restore the session
          $vtprd_info['product_session_info'] = $hold_session_info; 
        } else {
          $current_price_correctly_taxed = $product->price;
        }
 */      

        //establish all low/high values
        if ($current_price_correctly_taxed < $varParent_current_price_low) {
          $varParent_current_price_low = $current_price_correctly_taxed;
        }
        if ($current_price_correctly_taxed > $varParent_current_price_high) {
          $varParent_current_price_high = $current_price_correctly_taxed;
        }
                
        if ( ( isset($vtprd_info['product_session_info']['product_yousave_total_amt']) ) &&
             ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0) ) {
          $there_is_a_variation_discounted = true;
          
          $children_discount_count++; //v1.1.1.3
          
          //current contents of $vtprd_info['product_session_info'] are CORRECT
          $discount_price_correctly_taxed = $this->vtprd_show_shop_price();
          if ($discount_price_correctly_taxed < $varParent_discount_price_low) {
            $varParent_discount_price_low = $discount_price_correctly_taxed;
          }
          if ($discount_price_correctly_taxed > $varParent_discount_price_high) {
            $varParent_discount_price_high = $discount_price_correctly_taxed;            
          } 
 //error_log( print_r(  '$discount_price_correctly_taxed = ' .$discount_price_correctly_taxed, true ) );
 //error_log( print_r(  '$varParent_discount_price_low END ITERATION = ' .$varParent_discount_price_low, true ) );
 //error_log( print_r(  '$varParent_discount_price_high END ITERATION = ' .$varParent_discount_price_high, true ) );      
  
                 
        } else {
           //************************************ 
          //v1.1.1.3 begin - whole 'else' side ==>> IF NO DISCOUNT, put CURRENT_PRICE INTO low/high discount FOR THIS ITERATION!!
          //************************************
          if ($current_price_correctly_taxed < $varParent_discount_price_low) {
            $varParent_discount_price_low = $current_price_correctly_taxed;
          }
          if ($current_price_correctly_taxed > $varParent_discount_price_high) {
            $varParent_discount_price_high = $current_price_correctly_taxed;            
          }           
          //v1.1.1.3 end 
          //************************************       
        }
 //error_log( print_r(  '$varParent_current_price_low END ITERATION = ' .$varParent_current_price_low, true ) );
 //error_log( print_r(  '$varParent_current_price_high END ITERATION = ' .$varParent_current_price_high, true ) );   
      }  //end for loop

 //error_log( print_r(  '$varParent_current_price_low END LOOP = ' .$varParent_current_price_low, true ) );
 //error_log( print_r(  '$varParent_current_price_high END LOOP = ' .$varParent_current_price_high, true ) );   

      //*********************************
      //if no discount, store low/high and refigure the price_html value, to pick up any currency conversion...
      //*********************************
      if (!$there_is_a_variation_discounted) {  
      
        //if there is NO DISCOUNT, just note the compnents in the session variable and return ORIGINAL price_html
        /*
        $varParent_current_price_low_html     =  wc_price($varParent_current_price_low);
        $varParent_current_price_high_html    =  wc_price($varParent_current_price_high);
        
        if ($varParent_current_price_low_html == $varParent_current_price_high_html) {
          $price_html                           =  $varParent_current_price_low_html;
        } else {
          $price_html                           =  $varParent_current_price_low_html   .' - '. $varParent_current_price_high_html;
        }
        */
              
        $_SESSION['vtprd_product_session_price_'.$product_id] = array(
        		//If ID is VarParent
        		//for currency exchange as needed
          'varParent_current_price_low'       => $varParent_current_price_low,
          'varParent_current_price_high'      => $varParent_current_price_high,  
          'varParent_discount_price_low'      => 0,
          'varParent_discount_price_high'     => 0,
          'single_product_current_price' 	    => 0,
          'single_product_discount_price' 	  => 0,	
          'price_html'	    		              => $price_html
        ); 

 //error_log( print_r(  '$price_html WITH NO DISCOUNT = ' .$price_html, true ) );
 //error_log( print_r(  'return 004 , price=  ' .$price_html, true ) );         
        return $price_html;     
      } 
      
      
      //Build $price_html as needed 
 
      $varParent_current_price_low_html     =  wc_price($varParent_current_price_low);
      $varParent_current_price_high_html    =  wc_price($varParent_current_price_high);
      $varParent_discount_price_low_html    =  wc_price($varParent_discount_price_low);
      $varParent_discount_price_high_html   =  wc_price($varParent_discount_price_high);
            
      if ($varParent_current_price_low_html == $varParent_current_price_high_html) {
        $varParent_current_price_range        =  $varParent_current_price_high_html;
      } else {
        $varParent_current_price_range        =  $varParent_current_price_low_html   .' - '. $varParent_current_price_high_html;
      }
      
  
      if ($varParent_discount_price_low_html == $varParent_discount_price_high_html) {
        $varParent_discount_price_range       =  $varParent_discount_price_high_html;
      } else {
        $varParent_discount_price_range       =  $varParent_discount_price_low_html  .' - '. $varParent_discount_price_high_html;
      }
      
   		//v2.0.0.7 begin      
      if ( ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes') &&
           ($varParent_current_price_range != $varParent_discount_price_range)) {  // verify that there is an actual discount first      
      //v2.0.0.7 end

          //error_log( print_r(  '<del> 001', true ) );
          $price_html  = '<del>' .$varParent_current_price_range .'</del>'; 
           
          //v2.0.0 begin K solution
          if (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) {
            //showing on a product LIST page, needs a break between 
            $price_html .= '<br>';
          }
          //v2.0.0 end K solution 
                      
          $price_html .= '<ins>' .$varParent_discount_price_range .'</ins>'; 
              
      } else {       

          $price_html =  $varParent_discount_price_range; 
      }  

        
      //add in WOO suffix 
      $price_html .= $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
      
      //add in Pricing suffix - at this point, there must be a variation discount!!
      if ($children_discount_count == $sizeof_children)  {  //v1.1.1.3 ==>> only display the suffix if ALL of the variations have a discount
        $price_html = $this->vtprd_maybe_show_pricing_suffix($price_html);
      }

      $_SESSION['vtprd_product_session_price_'.$product_id] = array(
      		//If ID is VarParent
      		//for currency exchange as needed
        'varParent_current_price_low'       => $varParent_current_price_low,
        'varParent_current_price_high'      => $varParent_current_price_high,  
        'varParent_discount_price_low'      => $varParent_discount_price_low,
        'varParent_discount_price_high'     => $varParent_discount_price_high,
        'single_product_current_price' 	    => 0,
        'single_product_discount_price' 	  => 0,	
        'price_html'	    		              => $price_html
      );


  //error_log( print_r(  '$price_html WITH DISCOUNT= ' .$price_html, true ) );
  //error_log( print_r(  '$price_html SESSION Var= ', true ) );
  //error_log( var_export($_SESSION['vtprd_product_session_price_'.$product_id], true ) );
   
             
    } else {
    
      //-------------------------------
      //Simple Product / variation item Procuct
      //-------------------------------
          
      //NOT a variation product
      //First time, $product_info contains undiscounted current price

      
      $vtprd_info['current_processing_request'] = 'display';
      
      //v1.1.8.1 begin
      //$current_price = $product_info->price;
      if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
        $current_price = $product_info->price;
      } else {
        $current_price = $product_info->get_price();
      }
      //v1.1.8.1 end       
      
    //error_log( print_r(  '$current_price= ' .$current_price, true ) );
    //error_log( print_r(  '$product_info->price= ' .$product_info->price, true ) );
           
      vtprd_get_product_session_info($product_id,$current_price);
      
      $from = strstr($price_html, 'From') !== false ? ' From ' : ' ';
       
      //ONLY change input pricing if there IS a discount
      if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
        $price_html = $this->vtprd_show_shop_price_html(); //v1.0.7.4 
        $single_product_discount_price  = $vtprd_info['product_session_info']['product_discount_price'];

        //v1.1.7.2 begin
        //fix for duplicating suffix in CATALOG pricing only 
        if (strpos($price_html,$vtprd_info['product_session_info']['product_discount_price_suffix_html_woo']) !== false) {
          $suffix_already_there = true;
          //error_log( print_r(  '002 dup suffix found', true ) ); 
        } else { 
          //add in WOO suffix 
          $price_html .= $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
        }
        //add in WOO suffix 
        //$price_html .= $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];        
        //v1.1.7.2 end 
        
        //add in Pricing suffix, if there is a discount 
        $price_html = $this->vtprd_maybe_show_pricing_suffix($price_html);
      }
        
        
      $session_array = array(
      		//If ID is VarParent
      		//for currency exchange as needed
        'varParent_current_price_low'       => 0,
        'varParent_current_price_high'      => 0,  
        'varParent_discount_price_low'      => 0,
        'varParent_discount_price_high'     => 0,
        'single_product_current_price' 	    => $current_price,
        'single_product_discount_price' 	  => $single_product_discount_price,	
        'price_html'	    		              => $price_html
      ); 
 
        $_SESSION['vtprd_product_session_price_'.$product_id] = $session_array;
        $_SESSION['vtprd_product_old_price_'.$product_id]     = $session_array; //used in showing cart crossouts 
        
          
   }
        

    return $price_html;
 } 

//v2.0.2.0 removed, UNUSED  
  //v1.0.9.0 new function
  /* ***********************************************************  
  **  Spin through the woo cart, and for inline price discounts, put discounts into unit price.
  **    so EVERY TIME the cart displays , the pricing is altered HERE, if needed  
  ************************************************************** */
 /* 
	public function vtprd_maybe_variable_price_html($price_html, $product){    
    global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
    //error_log( print_r(  'Function begin - vtprd_maybe_variable_price_html', true ) );   

     //v2.0.0 begin (also moved to begin of function!)
     //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
     //don't run in admin or ajax...
     if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' )) ||
          (defined('DOING_AJAX') && DOING_AJAX) ) { 
          //error_log( print_r(  'vtprd_maybe_variable_price_html - do not run in ADMIN or Cron or Ajax FOUND, DO NOT RUN. REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );              
        return $price_html;          
     }

     //v2.0.0 end
          		
    //v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return $price_html;   
		}
	  //v1.1.1 end
          
    vtprd_debug_options(); 

     
    //if we already have the html price, no need to reprocess
     //PARENT ID holds whole array, if there

    //v1.1.7.2 begin   grpA
    if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
      $parent_product_id = $product->get_id();
    } else { 
      $parent_product_id = $product->id;
    }
    //v1.1.7.2 end  
     
     if(isset($_SESSION['vtprd_product_session_price_'.$parent_product_id])) { 
       $oldprice_session = $_SESSION['vtprd_product_session_price_'.$product_id]['price_html'];
       $price_html = stripslashes($oldprice_session); //this is FORMATTED 
     }     
     //v1.0.9.0 end

		$variations = $product->get_available_variations();
		$varPrice_array = array();
    $varPrice_array_with_suffix = array();
		
		foreach ($variations as $variation){
      $product_id = $variation['variation_id'];          
      vtprd_maybe_get_price_single_product($product_id);
			$varPrice = $this->vtprd_show_shop_price(); 
      $varPrice_array[] = $varPrice;
      
      //store array for later suffix retrieval
      $suffix = $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
      $varPrice_array_with_suffix[] = array (
        'varPrice' => $varPrice,
        'suffix'   => $suffix
      );

		}
		   
    array_multisort($varPrice_array, SORT_ASC);
		$varPrice_min = min($varPrice_array);
		$varPrice_max = max($varPrice_array);

    //get min price suffix
    $suffix = '';
    for($s=0; $s < sizeof($varPrice_array_with_suffix); $s++) {
      if ($varPrice_min == $varPrice_array_with_suffix[$s]['varPrice']) {
        $suffix = $varPrice_array_with_suffix[$s]['suffix'];
        break;
      }
    }


		if ($varPrice_min == $varPrice_max){ 
			$price_html = wc_price($varPrice_min) . ' ' . $suffix; //v1.1.7 replace woocommerce_price with wc_price
		} else { 
			$price_html = wc_price($varPrice_min).' - '.woocommerce_price($varPrice_max) . ' ' . $suffix;  //v1.1.7 replace woocommerce_price with wc_price
		}

    //store price under PARENT ID
    //v1.1.1 removed in favor of setup below
    //$_SESSION['vtprd_product_session_price_'.$parent_product_id] = $price_html; //v1.0.9.0 
 
      $_SESSION['vtprd_product_session_price_'.$parent_product_id] = array(
      		//If ID is VarParent
      		//for currency exchange as needed
        'varParent_current_price_low'       => 0,
        'varParent_current_price_high'      => 0,  
        'varParent_discount_price_low'      => $varPrice_min,
        'varParent_discount_price_high'     => $varPrice_max,
        'single_product_current_price' 	    => 0,
        'single_product_discount_price' 	  => 0,	
        'price_html'	    		              => $price_html
      ); 
    return $price_html;
 } 
*/
   
  //v1.0.9.3 new function
  /* ***********************************************************  
  **  Refresh the mini-cart numbers as needed ==> UnitPrice AND coupon both  
  ************************************************************** */
	public function vtprd_maybe_before_mini_cart(){ 
     //error_log( print_r(  'Function begin - vtprd_maybe_before_mini_cart', true ) ); 
   
		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
     //error_log( print_r(  'before_mini_cart - do_no_actions EXIT ', true ) );            
      return;   
		}
	  //v1.1.1 end
       
    global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;

    vtprd_debug_options();  //v1.1
    
    if ( ($vtprd_cart == null) ||
         (!isset ($vtprd_cart->cart_items)) ) { //v1.1.1 was losing addressability to cart_items array...
       $data_chain = $this->vtprd_get_data_chain();
      if ($vtprd_cart == null) {  //haven't had the cart call yet...  
     //error_log( print_r(  'before_mini_cart - $vtprd_cart == null EXIT ', true ) );             
        return;
      } 
    } 
    
    $mini_cart_updated = false;
    
    $cart_object =  $woocommerce->cart->get_cart();
    
    $current_total = 0; //v1.1.1.3

    foreach ( $cart_object as $cart_item_key => $cart_item_value ) {

      /* v1.1.0.6  REMOVED
      //  If formerly free item is purchased, mini-cart update may be needed
      //price already at zero, no update needed
      if ($cart_item_value['data']->price == 0) {
        continue;
      }
      */
      
      if ($cart_item_value['variation_id'] > ' ') {      
          $woo_product_id  =  $cart_item_value['variation_id'];
      } else { 
          $woo_product_id  =  $cart_item_value['product_id'];
      }

    
      if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice')  {

        foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      

          //******************************
          //v1.1.1
          // GLOBAL CHANGE ==>> $key to $cart_item_key, $value to $cart_item_value 
          //  CHANGE to compare WOO item key rather than product ID WHICH IS NOT UNIQUE for the measurements plugin
          //******************************
          if ($vtprd_cart_item->cart_item_key == $cart_item_key ) { //v1.1.1
               
               vtprd_get_product_session_info($vtprd_cart_item->product_id);
               
              //v1.1.7 begin AS OF WC v 3.0.0, $cart_item_value['data']->price NO LONGER EXISTS IN THE WOO CART!!!!!!!!!!
              if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                $existing_price = $cart_item_value['data']->price;
              } else {
                $existing_price = $cart_item_value['data']->get_price();
              } 
              //v1.1.7 end   
          //this will now pick up BOTH inline discounts, and solo CATLOG discounts...
  
              switch( true ) {
                case ( ($vtprd_cart_item->product_inline_discount_price_woo > 0) ||  
                      (($vtprd_cart_item->product_inline_discount_price_woo == 0) &&  //price can be zero if item is free
                       ($vtprd_cart_item->product_discount_price_woo == 0) &&  //regular discount price must also be zero
                       ($vtprd_cart_item->yousave_total_amt > 0)) ):                  //there is a discount...
                    //v1.0.9.3 spec begin
                    
                    //v1.1.7 begin                    
                    $new_price = $this->vtprd_choose_mini_cart_price($vtprd_cart_item);
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 
                      $cart_item_value['data']->price = $new_price;
                    } else {                     
                      $cart_item_value['data']->set_price( $new_price );
                    } 
                    //v1.1.7 end                     
                    //$vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price;    //
                    //v1.0.9.3 spec end
                    $mini_cart_updated = true;                   
                  break;
                case ($vtprd_cart_item->product_discount_price_woo > 0)  :               
                    //v1.1.7 begin                    
                    $new_price = $this->vtprd_choose_mini_cart_price($vtprd_cart_item);
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 
                      $cart_item_value['data']->price = $new_price;
                    } else {                      
                      $cart_item_value['data']->set_price( $new_price );
                    } 
                    //v1.1.7 end                     
                    $mini_cart_updated = true;                    
                  break; 
                
                //v1.1.7 begin 
                //case ($vtprd_cart_item->unit_price < $cart_item_value['data']->price )  :    //Pick up a **solo CATALOG price reduction** 
                case ($vtprd_cart_item->unit_price < $existing_price )  :    //Pick up a **solo CATALOG price reduction**       
                    //$cart_item_value['data']->price = $vtprd_cart_item->unit_price;   /* *$this->vtprd_choose_unit_price($vtprd_cart_item); */
                    $new_price = $vtprd_cart_item->unit_price;
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                      $cart_item_value['data']->price = $new_price;
                    } else {                      
                      $cart_item_value['data']->set_price( $new_price );
                    }   
                    $mini_cart_updated = true;                                        
                  break;
                 //v1.1.7 end 

                //v1.1.7 begin 
                default  :    //Pick up a **solo CATALOG price reduction** v2.0.0 (or correct a removed discount!)
                //case ($vtprd_cart_item->unit_price != $existing_price )  :    // != allows the == stuff to be untouched...                 
                    $new_price = $vtprd_cart_item->unit_price; 
                    //v2.0.0 begin
                    //actually apply the new price!!
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                      $cart_item_value['data']->price = $new_price;
                    } else {                      
                      $cart_item_value['data']->set_price( $new_price );
                    }   
                    $mini_cart_updated = true;                     
                    //v2.0.0 end                                      
                  break;
                //v1.1.7 end 
                  
              } 
                                          
            }
            

          }//end foreach
          
        
        //v1.1.7 begin
        if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
          $current_total += ($cart_item_value['data']->price * $cart_item_value['data']->quantity); //v1.1.1.3
        } else {
          $current_total += ($new_price * $cart_item_value['quantity']); //v1.1.7 quantity is now in a different place!
        }
        //v1.1.7 end 
                
      } else { // **discountCoupon path**
/*
          foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
            if ($vtprd_cart_item->cart_item_key == $cart_item_key ) {
                //if ADDon, move the cart value in all cases => this is the CATALOG price
                $cart_item_value['data']->price = $vtprd_cart_item->unit_price;  
                $mini_cart_updated = true;
                break; //FOund it - get out of  $vtprd_cart->cart_items foreach            
            }      
          
          }
*/
        //check to be sure any **Catalog** deal prices are reflected here - *** 2nd-nth time, these numbers are not reflected in the mini-cart ***
        vtprd_maybe_get_product_session_info($woo_product_id);
        
       //v1.1.1 begin
       //***************************************************************************
       //for ADDONS product, unit price getting lost for discountCOUPON - go find it!
       // Unit Price will already reflect any Catalog discount, if available
       //***************************************************************************
       if ( (class_exists('WC_Product_Addons')) &&
            ($vtprd_info['product_session_info']['product_has_addons']) )  {         
          foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
            if ($vtprd_cart_item->cart_item_key == $cart_item_key ) {
                //if ADDon, move the cart value in all cases => this is the CATALOG price
              //  $cart_item_value['data']->price = $vtprd_cart_item->unit_price;  
                    //v1.1.7 begin                    
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 
                      $cart_item_value['data']->price = $vtprd_cart_item->unit_price;
                    } else {                     
                      $new_price = $vtprd_cart_item->unit_price;
                      $cart_item_value['data']->set_price( $new_price );
                    } 
                    //v1.1.7 end                   
                $mini_cart_updated = true;
                break; //FOund it - get out of  $vtprd_cart->cart_items foreach            
            }      
          
          }        
       } else {
       //v1.1.1 end
       
          //v1.1.7 begin AS OF WC v 3.0.0, $cart_item_value['data']->price NO LONGER EXISTS IN THE WOO CART!!!!!!!!!!
          if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
            $existing_price = $cart_item_value['data']->price;
          } else {
            $existing_price = $cart_item_value['data']->get_price();
          } 
          //v1.1.7 end 
          
          if (isset($vtprd_info['product_session_info']['product_discount_price'])) {  //v1.1.0.6 added isset test
            if ( ( ($vtprd_info['product_session_info']['product_discount_price']  > 0) ||  
                  (($vtprd_info['product_session_info']['product_discount_price'] == 0) &&  //price can be zero if item is free
                   ($vtprd_info['product_session_info']['product_yousave_total_amt']  > 0)) )
                      &&
            //v1.1.7 begin
                   //($vtprd_info['product_session_info']['product_discount_price'] < $cart_item_value['data']->price ) )  {
                   ($vtprd_info['product_session_info']['product_discount_price'] < $existing_price ) )  {
                   
              //$cart_item_value['data']->price = $vtprd_info['product_session_info']['product_discount_price']; 
              $new_price = $vtprd_info['product_session_info']['product_discount_price'];
              if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 
                $cart_item_value['data']->price = $new_price;
              } else {                     
                $cart_item_value['data']->set_price( $new_price );
              }
            //v1.1.7 end               
               
              $mini_cart_updated = true;
            }
          }
        }
       
      } //end if
    } //end foreach
     
          
    if ($mini_cart_updated) {
      $_SESSION['internal_call_for_calculate_totals'] = true;           
      $woocommerce->cart->calculate_totals(); 
     //error_log( print_r(  'before_mini_cart - calculate_totals ', true ) );       
    } 
      else {  //v1.1.1.2 begin -  totals are messed up session 1st time through  (very weird)
        if ( ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  &&
             ($vtprd_info['ruleset_contains_auto_add_free_product'] == 'yes') )  {
            $_SESSION['internal_call_for_calculate_totals'] = true;           
            $woocommerce->cart->calculate_totals();
        }
      }
      //v1.1.1.2 end

      
      //v1.1.1.3 begin
      //IF all discounts REMOVED due to item being removed, this clears up totals issues!!
      if ( ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') &&
           ($current_total != $woocommerce->cart->cart_contents_total) ) {
        $_SESSION['internal_call_for_calculate_totals'] = true;           
        $woocommerce->cart->calculate_totals();       
      }
      //v1.1.1.3 end

     //error_log( print_r(  'before_mini_cart - $woocommerce->cart at END', true ) );
     //error_log( var_export($woocommerce->cart, true ) ); 
     //error_log( print_r(  'before_mini_cart - $vtprd_cart at END', true ) );
     //error_log( var_export($vtprd_cart, true ) );   
    
           
    return;
 } 
 
 
  //v1.0.9.3 new function
  /* ***********************************************************  
  **  Unit price taxation choice for cart   
  ************************************************************** */
	public function vtprd_choose_mini_cart_price($vtprd_cart_item){ 
       //error_log( print_r(  'Function begin - vtprd_choose_mini_cart_price', true ) ); 
     global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
     
    $price = $vtprd_cart_item->product_inline_discount_price_woo;
    
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
 
                if (get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
                  $price = $vtprd_cart_item->product_inline_discount_price_incl_tax_woo;                
                }          
              }   
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   == 'incl') { //v1.0.9.3
                if (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) {
                  $price = $vtprd_cart_item->product_inline_discount_price_excl_tax_woo; //TAX WILL BE added by WOo, don't do it here!                  
                }
              }           
             break;
       }          
    } 

    return $price;
 }    
 
  //v1.0.9.0 new function
  /* ***********************************************************  
  **  Spin through the woo cart, and for inline price discounts, put discounts into unit price.
  **    so EVERY TIME the cart displays , the pricing is altered HERE, if needed 
  **  refactored in v1.0.9.3   
  ************************************************************** */
	public function vtprd_maybe_before_calculate_totals($cart_object){

    //error_log( print_r(  'Function begin - vtprd_maybe_before_calculate_totals', true ) ); 
    //error_log( print_r(  'vtprd_maybe_before_calculate_totals $cart_object BEGIN', true ) );
    //error_log( var_export($cart_object, true ) );  
    
    //v2.0.0 begin
    global $woocommerce, $vtprd_info, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;
                
    //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
    if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
         (defined( 'DOING_CRON' )) ) { 
        //error_log( print_r(  'vtprd_cart_updated - Admin or Cron FOUND, DO NOT RUN. REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );     
      return $cart_object;          
    }
    //v2.0.0 end      

    //v1.1.0.8 begin
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    //v1.1.0.8 end

   
		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) { 
 //error_log( print_r(  'return 001' , true ) );                     
      return $cart_object;   
		}
	  //v1.1.1 end


    //v1.0.9.3 begin - 
    //	This switch is set to true wherever the plugin itself calls calculate_totals
    if (isset($_SESSION['internal_call_for_calculate_totals'])) {
      if ($_SESSION['internal_call_for_calculate_totals'] == true) {    
        $_SESSION['internal_call_for_calculate_totals'] = false; 
 //error_log( print_r(  'return 002' , true ) );              
        return $cart_object;
      } 
    }
           
             
    vtprd_debug_options(); //v1.1

    //get saved vtprd_cart with discount info
    $discount_already_processed_here = false; //v1.0.9.3
    if ( ($vtprd_cart == null) ||
         (!isset ($vtprd_cart->cart_items)) ) { //v1.1.1 was losing addressability to cart_items array... 
       $data_chain = $this->vtprd_get_data_chain();

      //just in case...
      if ($vtprd_cart == null) {  //haven't had the cart call yet...
         
        //v2.0.0 begin 
        return $cart_object;        
        /* 
        if (sizeof($cart_object->cart_contents) > 0) {        
          $woocommerce_cart_contents = $woocommerce->cart->get_cart(); 
          $this->vtprd_process_discount();
          $discount_already_processed_here = true; //v1.0.9.3 
        } else { //v1.1.1 begin
 error_log( print_r(  'return 003' , true ) );         
          return $cart_object;      
        } //v1.1.1 end
        */ 
        //v2.0.0 end
      } 
    }
    

    /*
    //v1.1.0.9 begin  refactored
    if added to ==> new session var cumulativeCouponNo, and ONLY run on cart/checkout pages!!
    apply the discount NOW to pick up possible **add/remove_coupon** action...  no other way, as remove_coupon has no hook...
    2 sets of session variables:
        - on_cart_or_checkout_page is set in parent-functions.php off of the wp_head hook
        - coupon_activated_discount set in apply-rules.php
        - cumulativeCouponNo set in apply-rules.php
    */
      //v2.0.0 begin H solution 
     /*
        During a post_process auto add ,
        there can be an execution of vtprd_process_discount below.
        This situation is being tripped during the add_to_cart inadvertently, 
        causing recursive executions of vtprd_process_discount,
        when the $_SESSION['previous_auto_add_array'] does not exist.
        However, using the existing switch 'auto_add_in_progress' in 
        vtprd_maybe_before_calculate_totals will take care of this.           
     */

    
    if ( (isset($_SESSION['auto_add_in_progress'])) && 
         ($_SESSION['auto_add_in_progress'] == 'yes') ) {
      //$skip_all_this = true; 
      //Remove add coupons success msg if there, a duplicate was showing up!!        
      vtprd_remove_coupon_applied_message();   
    } else {
      if ( (isset($_SESSION['on_cart_or_checkout_page'])) && 
           ($_SESSION['on_cart_or_checkout_page'])  &&
           (!$discount_already_processed_here) &&
           
           //v2.0.2.0 begin
           //(!isset($_SESSION['previous_auto_add_array'])) ) {  //DO NOT DO if auto add free product IN PROCESS!!!!!!!!!!!!!!
           (!vtprd_get_transient_cart_data('previous_auto_add_array')) ) {  //DO NOT DO if auto add free product IN PROCESS!!!!!!!!!!!!!!
           //v2.0.2.0 end
     
        if ( ((isset($_SESSION['coupon_activated_discount'])) &&
                ($_SESSION['coupon_activated_discount'])) 
                            ||
               ((isset($_SESSION['cumulativeCouponNo'])) &&
                ($_SESSION['cumulativeCouponNo'])) ) {
                
          $woocommerce_cart_contents = $woocommerce->cart->get_cart(); 
         
          $this->vtprd_process_discount();
          $discount_already_processed_here = true; 
                  
        }
      } 
    }     
    //v2.0.0 end H solution    
    //v1.1.0.9 end
    
    foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item_value ) {
                 
       //v1.1.1 begin
       //*****************************************************
       // PLUGIN:: woocommerce-measurement-price-calculator
       //*****************************************************
       if ( (class_exists('WC_Measurement_Price_Calculator')) &&
            (isset ($cart_item_value['pricing_item_meta_data'])) ) {  //**if this is a calculator product, SKIP - pricing already all good**
          continue;   
       }                   
       //v1.1.1 end 
                 
      if ($cart_item_value['variation_id'] > ' ') {      
          $woo_product_id  =  $cart_item_value['variation_id'];
      } else { 
          $woo_product_id  =  $cart_item_value['product_id'];
      }


      foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) { 

        //******************************
        //v1.1.1  begin
        // GLOBAL CHANGE ==>> $key to $cart_item_key, $value to $cart_item_value 
        //  CHANGE to compare WOO item key rather than product ID WHICH IS NOT UNIQUE for the measurements plugin
        //******************************
        if ($vtprd_cart_item->cart_item_key == $cart_item_key ) { 
        //if ($vtprd_cart_item->product_id == $woo_product_id ) {
        //v1.1.1  end
        //this will now pick up BOTH inline discounts, and solo CATLOG discounts...
           
           if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice')  {
             

              //v1.1.7 begin AS OF WC v 3.0.0, $cart_item_value['data']->price NO LONGER EXISTS IN THE WOO CART!!!!!!!!!!
              if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                $existing_price = $cart_item_value['data']->price;
              } else {
                $existing_price = $cart_item_value['data']->get_price();
              } 
              //v1.1.7 end 
              
              switch( true ) {
                case ( ($vtprd_cart_item->product_inline_discount_price_woo > 0) ||  
                      (($vtprd_cart_item->product_inline_discount_price_woo == 0) &&  //price can be zero if item is free
                       ($vtprd_cart_item->product_discount_price_woo == 0) &&  //regular discount price must also be zero
                       ($vtprd_cart_item->yousave_total_amt > 0)) ):                  //there is a discount...
                    //v1.1.7 begin 
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                      $cart_item_value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price; 
                    } else {
                      $new_price = $vtprd_cart_item->product_inline_discount_price_woo;
                      $cart_item_value['data']->set_price( $new_price );
                    } 
                    //v1.1.7 end  
                  break;
                case ($vtprd_cart_item->product_discount_price_woo > 0)  :               
                    //$cart_item_value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price;    
                    //v1.1.7 begin                    
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                      $cart_item_value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price; 
                    } else {
                      $new_price = $vtprd_cart_item->product_inline_discount_price_woo;
                      $cart_item_value['data']->set_price( $new_price );
                    } 
                    //v1.1.7 end                  
                  break;
                //v1.1.7 begin 
                //case ($vtprd_cart_item->unit_price < $cart_item_value['data']->price )  :    //Pick up a **solo CATALOG price reduction** 
                case ($vtprd_cart_item->unit_price < $existing_price )  :    //Pick up a **solo CATALOG price reduction**       
                    //$cart_item_value['data']->price = $vtprd_cart_item->unit_price;   /* *$this->vtprd_choose_unit_price($vtprd_cart_item); */                  
                    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                      $cart_item_value['data']->price = $vtprd_cart_item->unit_price;
                    } else {
                      $new_price = $vtprd_cart_item->unit_price;
                      $cart_item_value['data']->set_price( $new_price );
                    }                                      
                  break;
                 //v1.1.7 end  
              }
           }
            else {  //discount in coupon, just show unit_price, which already includes any Catalog discount
             //v1.1.7 begin
             if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                $cart_item_price = $cart_item_value['data']->price;
             } else {
                $cart_item_price = $cart_item_value['data']->get_price();
             }
             //v1.1.7 end 
             
             if ( ($vtprd_cart_item->product_discount_price_woo > 0) ||
                  ($vtprd_cart_item->unit_price < $cart_item_price) ) { //v1.0.9.3 pick up CATALOG-only discount when discountCoupon!! , changed in //v1.1.7          
                //$cart_item_value['data']->price = $vtprd_cart_item->unit_price;   //$vtprd_cart_item->discount_price;    //
                //v1.1.7 begin
                if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
                      $cart_item_value['data']->price = $vtprd_cart_item->unit_price;
                    } else {
                      $new_price = $vtprd_cart_item->unit_price;
                      $cart_item_value['data']->set_price( $new_price );
                    }     
                //v1.1.7 end                               
             }
             
             
             
             /*
             if ($vtprd_cart_item->product_discount_price_woo > 0) {               
                $cart_item_value['data']->price = $vtprd_cart_item->product_discount_price_woo;   //$vtprd_cart_item->discount_price; 
               // $cart_item_value['data']->price = $vtprd_cart_item->product_inline_discount_price_woo;   //$vtprd_cart_item->discount_price; 
               $cart_item_value['data']->price = $vtprd_cart_item->unit_price;  
             } else {
                if ($vtprd_cart_item->unit_price > 0) {
                  $cart_item_value['data']->price = $vtprd_cart_item->unit_price;   //$vtprd_cart_item->discount_price;    //
                }
             }
             */
           }
          
           break;
        }
      }

    }
       //error_log( print_r(  'vtprd_maybe_before_calculate_totals $cart_object AT EXIT', true ) );
       //error_log( var_export($cart_object, true ) ); 
       //error_log( print_r(  'vtprd_maybe_before_calculate_totals FINAL EXIT!!!!!!!!!', true ) );
    return $cart_object;

 } 

 
  //**************************************
	//refactored v1.0.9.3
  //  Primarily used for Cart/Mini-cart unit price display ***with crossout***
  //    also used for bolt-on plugin entities
  //**************************************
  public function vtprd_maybe_cart_item_price_html($price_html, $cart_item, $cart_item_key){ 

        //error_log( print_r(  ' ', true ) );
        //error_log( print_r(  'Function begin - vtprd_maybe_cart_item_price_html, price= ' .$price_html. ' prodID= ' .$cart_item['product_id']. ' varID= ' .$cart_item['variation_id'], true ) );
        //error_log( print_r(  ' ', true ) );     
        //error_log( var_export($cart_item, true ) ); 

    global $post, $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart;
    vtprd_debug_options();  //v1.0.5

   
    //v1.1.0.3 begin
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    //v1.1.0.3 end

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
    //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit001, price= ' .$price_html, true ) );        
      return $price_html;   
		}
	  //v1.1.1 end

      
            //error_log( print_r(  'CART vtprd_maybe_cart_item_price_html', true ) );
            //error_log( var_export($woocommerce->cart, true ) ); 

         
    //v1.1.0.6 begin

    if ( (isset($cart_item['line_subtotal'])) &&
         ($cart_item['line_subtotal'] == 0)   &&
         (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE
        $price_html = __('Free', 'vtprd');         
    }    
    //v1.1.0.6 end

 
   //********************************************************
   //v1.1.1 
   //  Fix Calculator Taxation always
   //  Fix Addons taxation always
   //********************************************************
   // Calculator not doing Cart taxation - a bug in the program... 
   
    if ($cart_item['variation_id'] > ' ') {      
      $product_id  = $cart_item['variation_id'];
    } else { 
      $product_id  = $cart_item['product_id'];
    }
    
    vtprd_get_product_session_info($product_id);  
    if ( ((class_exists('WC_Measurement_Price_Calculator')) &&
          ($vtprd_info['product_session_info']['product_has_calculator']))  //product_has always exists
            ||
         ((class_exists('WC_Product_Addons')) &&
          ($vtprd_info['product_session_info']['product_has_addons']))  ) {   //product_has always exists
      $price_html = $this->vtprd_maybe_taxation_fix($price_html, $cart_item, $cart_item_key); 
    //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit002, price= ' .$price_html, true ) );       
      return $price_html;     
    }
    //v1.1.1 Fix END               
   //********************************************************
   
    
     //v1.1.1 split out 
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') {
   //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit003, price= ' .$price_html, true ) );      
       remove_action('woocommerce_cart_item_price', array(&$this, 'vtprd_maybe_cart_item_price_html'));  //v2.0.0 g solution  
       return $price_html;    
    }
    
    //v1.1.1 
    /*
    The bolt-on plugin products have the incorrect info on the current price in all situations
    For all other entity types, this is only used for crossouts
    
    BOLT-ON Discount Unit Price path.
    //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting) 
    
    //  when reactivated, add code to test for  calculator_product_in_cart/addons_product_in_cart  
    
    if ((class_exists('WC_Measurement_Price_Calculator')) ||
        (class_exists('WC_Product_Addons')) ) { 
              $carry_on = true;
    } else {    //If discount in coupon, or show no crossouts, exit stage left
    */ 
    
    if ($vtprd_setup_options['show_unit_price_cart_discount_crossout'] == 'no') {
   //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit004, price= ' .$price_html, true ) );       
      return $price_html;    
    }
  


    if ($cart_item['quantity'] <= 0) {
   //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit005, price= ' .$price_html, true ) );        
      return $price_html;
    }

    //current $price_html, if updated, has been overwritten with a new price, without the previous crossout, if any 
    /*
    if ($cart_item['data']->price == 0) {
      $newprice = __('Free!', 'vtprd');
    } else {
      $newprice = $price_html;
    }
    */

    $newprice = $price_html;
    
    foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
 
     //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart_item ROW', true ) );
     //error_log( var_export($vtprd_cart_item, true ) ); 
  
      //already free, no crossout !!!!!!
      if ( ($vtprd_cart_item->product_catalog_price_displayed == 0) ||
           ($vtprd_cart_item->product_catalog_price_displayed === __('Free', 'vtprd')) ) { //=== needed !!!
     //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart free item skip', true ) );           
         continue;  //skip to next in foreach
      }
 
  
      //******************************
      //v1.1.1 begin
      // GLOBAL CHANGE ==>> $key to $cart_item_key, $value to $cart_item_value 
      //  CHANGE to compare WOO item key rather than product ID WHICH IS NOT UNIQUE for the measurements plugin      
      if ($vtprd_cart_item->cart_item_key == $cart_item_key ) {       
 
     //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart_item KEY FOUND', true ) );

          //CROSSOUTS!!!!!!!!!!!!!!
          //pick up both Catalog and Cart discount for comparison test sake only, as the 1st test only tests if CART discount = price
          $combined_discount = $vtprd_cart_item->unit_price + $vtprd_cart_item->product_inline_discount_price_woo;
          //v1.1.7 begin AS OF WC v 3.0.0, $cart_item_value['data']->price NO LONGER EXISTS IN THE WOO CART!!!!!!!!!!
          if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
            $existing_price = $cart_item['data']->price;
          } else {
            $existing_price = $cart_item['data']->get_price();
          }  
    
 
          //*****************************
          //v2.0.0.6 begin
          // the IF below was failing in odd singular situations (when the orig product price was $9.50 and the disocount was 10%)
          // ROUNDING the participating fields in the IF took care of things
          
          $existing_price_round = round( ($existing_price) , 2);
          $product_inline_discount_price_woo_round = round( ($vtprd_cart_item->product_inline_discount_price_woo) , 2); 
          $combined_discount_round = round( ($combined_discount) , 2);   

          //error_log( print_r(  '$existing_price= ' .$existing_price, true ) );
          //error_log( print_r(  'product_inline_discount_price_woo = ' .$vtprd_cart_item->product_inline_discount_price_woo, true ) );
          //error_log( print_r(  '$combined_discount = ' .$combined_discount, true ) );          

         //TEST TEST TEST round 2 
          if ( ($product_inline_discount_price_woo_round == $existing_price_round) ||
               ($combined_discount_round                 == $existing_price_round)) {

          //v2.0.0.6 end                  
 
     //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart_item CROSSOUT in progress', true ) );
          
            $oldprice = $_SESSION['vtprd_orig_cart_price_'.$vtprd_cart_item->cart_item_key]; //v1.1.1 
     //error_log( print_r(  'OLDPRICE 001= ' .$oldprice, true ) );                  
            $oldprice =  $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $oldprice); //v1.1.1 
      //error_log( print_r(  'OLDPRICE 002= ' .$oldprice, true ) );       
            $_SESSION['vtprd_orig_cart_price_with_taxation_'.$vtprd_cart_item->cart_item_key] = $oldprice;  //v1.1.1 used in subtotal crossout display
            $oldprice = wc_price( $oldprice ); //v1.1.1  
       //error_log( print_r(  'OLDPRICE 003= ' .$oldprice, true ) );
        //error_log( print_r(  'NEWPRICE= ' .$newprice, true ) );                    
            if ($oldprice == $newprice) { //if no change, no crossout!!!!!!
              $price_html = $newprice; //may not be necessary, value should already be there 
     //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart_item CROSSOUT nothing changed', true ) );              
            } else {
              //error_log( print_r(  '<del> 002', true ) );
              $price_html = '<del>' . $oldprice  . '</del> &nbsp; <ins>' . $newprice . '</ins>';  
    //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart_item CROSSOUT SUPPLIED= ' .$price_html, true ) );              
            }
   //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit006, price= ' .$price_html, true ) );                       
            return $price_html; //v1.1.0.3 added, so that this is the only branch that stores oldprice, for use at checkout/thankyou/emails
         } 
         //v1.1.0.3 comment out this return, in order to process the session cleanout efficiently
         /*
         else {            
            return $price_html;
         }    
          */
      } 

    } 
                
     //v1.1.0.3 begin
     // Clear info in session for use in emails, etc     
    $_SESSION['vtprd_product_cart_unit_price_oldprice_'.$product_id] = ' ';             
    //v1.1.0.3 end 
    
    //error_log( print_r(  'vtprd_maybe_cart_item_price_html cart_item CROSSOUT FINAL EXIT', true ) );
   //error_log( print_r(  'vtprd_maybe_cart_item_price_html exit007, price= ' .$price_html, true ) );            
   return $price_html;

 }


  //**************************************
	//v1.1.1  new function 
  //v1.1.1 Fix Calculator Taxation
  // Calculator not doing Cart taxation - a bug in the program...
  //  FIX repairs Calculator output, whether or NOT there is a discount
  //**************************************
  public function vtprd_maybe_taxation_fix($price_html, $cart_item, $cart_item_key) {

     //error_log( print_r(  'Function begin - vtprd_maybe_taxation_fix', true ) );

    global $post, $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart;
    
    //JUST IN CASE - Bolt-ons only allowed using COUPON right now
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon') {
       return $price_html; 
    }   
           
    foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
                                         
      if ($vtprd_cart_item->product_catalog_price_displayed === __('Free', 'vtprd') ) { //=== needed !!!
         continue;  //skip to next in foreach
      }      
 
      if ($vtprd_cart_item->cart_item_key == $cart_item_key ) {       
 
        //SINCE this is allowed only in Coupon mode, this will ALways be the unit_price value, just reflecting any CATALOG pricing
        $price_html = wc_price( $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $vtprd_cart_item->unit_price) );
        
          /*
          vtprd_get_product_session_info($vtprd_cart_item->product_id);
          if ($vtprd_cart_item->yousave_total_amt > 0) {
            $price_html = wc_price( $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $vtprd_cart_item->discount_unit_price) );               
          } else {
            $price_html = wc_price( $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $vtprd_cart_item->unit_price) );
          }
          */
                    
          break;
          
        } 

    } //end foreach
                
     //v1.1.0.3 begin
     // Clear info in session for use in emails, etc     
    $_SESSION['vtprd_product_cart_unit_price_oldprice_'.$product_id] = ' ';             
    //v1.1.0.3 end        

   return $price_html;  
  
  }

  //**************************************
	//v1.1.1  new function 
  // ONLY used for Bolt-on Plugins
  //**************************************
  public function vtprd_get_taxation_price_cart($product_id, $price) {
     //error_log( print_r(  'Function begin - vtprd_choose_discount_price', true ) );
    global $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart; 

    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) { 
            
      $woocommerce_tax_display_cart = get_option( 'woocommerce_tax_display_cart' );
      $product = wc_get_product($product_id);  //v1.1.7 replace get_product with wc_get_product
       
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
            // case ( $woocommerce->customer->is_vat_exempt()):    
            case ( vtprd_maybe_customer_tax_exempt() ):             
                $price = vtprd_get_price_excluding_tax_forced($product_id, $price, $product);
              break; 
            case ( $woocommerce_tax_display_cart == 'incl'):
                //all good, nothing to do 
              break;           
            case ( $woocommerce_tax_display_cart == 'excl'):
                $price = vtprd_get_price_excluding_tax_forced($product_id, $price, $product);   
              break;
          } 
      } else {      
          switch( true ) {
            case ( vtprd_maybe_customer_tax_exempt() ):           
                //all good, nothing to do 
              break; 
            case ( $woocommerce_tax_display_cart == 'incl'):
                $price = vtprd_get_price_including_tax_forced($product_id, $price, $product);
              break;           
            case ( $woocommerce_tax_display_cart == 'excl'):
                //all good, nothing to do    
              break;
          }                 
      }    
    } 
  
    return $price;
  }


  //**************************************
	//v1.1.0.3 new function 
  //v1.1.0.6 refactored for 'free' processing
  //  add crossouts when at checkout (not on the Cart page)
  //**************************************
  public function vtprd_maybe_cart_item_subtotal($subtotal, $cart_item, $cart_item_key){  // $cart_item, $cart_item_key 
     //error_log( print_r(  'Function begin - vtprd_maybe_cart_item_subtotal', true ) );
    global $post, $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart;
    vtprd_debug_options();  //v1.0.5

    //v1.1.0.6 moved these 2 ifs to the top   
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return $subtotal;   
		}
	  //v1.1.1 end
      
    if ($cart_item['variation_id'] > '0') {      
      $product_id  = $cart_item['variation_id'];
    } else { 
      $product_id  = $cart_item['product_id'];    
    }     



       
    //v1.1.0.6 added
    if ($cart_item['line_subtotal'] == 0) {   
      
      //v2.0.2.0 begin
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0010 ', true ) );
      $get_previous_auto_add_array = vtprd_get_transient_cart_data ('previous_auto_add_array');
      if ($get_previous_auto_add_array)  {
        $previous_auto_add_array = unserialize($get_previous_auto_add_array);
      //if (isset($_SESSION['previous_auto_add_array']))  {
        //$previous_auto_add_array = unserialize($_SESSION['previous_auto_add_array']);
      //v2.0.2.0 end 
        if ( (isset($previous_auto_add_array['free_product_id'])) &&   //v2.0.2.0 added test      
             ($product_id  == $previous_auto_add_array['free_product_id']) &&
             (apply_filters('vtprd_show_zero_price_as_free',TRUE) ) ) { //if zero is preferred, send back FALSE
           $subtotal = __('Free', 'vtprd');   
        } 
      } 
    }    
    //v1.1.0.6 end
/* 
  TEST2
    //v1.1.1 calculator if added
    // Calculator product massaged in all cases
    $calculator_product = false;
    if ( (class_exists('WC_Measurement_Price_Calculator')) &&
         (isset ($cart_item['pricing_item_meta_data'])) &&
         (isset ($cart_item['pricing_item_meta_data']['_price'])) ) {  //if this is a calculator product
      $calculator_product = true;

    } else {
      if ( (!isset($vtprd_setup_options)) ||
           ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') ||
           ($vtprd_setup_options['show_unit_price_cart_discount_crossout'] == 'no') )  {
  //error_log( print_r(  'line_subtotal, $exit 001', true ) );      
        return $subtotal;    
      }     
    }

    //if ( (class_exists('WC_Measurement_Price_Calculator')) 
    // RECALCULATE the line subtotal
    if ($calculator_product) {
       foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {
          if ($vtprd_cart_item->cart_item_key == $cart_item_key ) {       
             if ($vtprd_cart_item->yousave_total_amt > 0) {  
                $discount_unit_price = $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $vtprd_cart_item->discount_unit_price);
                $subtotal = round($discount_unit_price * $cart_item['quantity'], absint( get_option( 'woocommerce_price_num_decimals' ) ) );
             } else {
                $subtotal = round($cart_item['pricing_item_meta_data']['_price'] * $cart_item['quantity'], absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
             }
             $subtotal = wc_price($subtotal);
             break;
          }
      }
    }
 */ 
    if ( (!isset($vtprd_setup_options)) ||
           ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') ||
           ($vtprd_setup_options['show_unit_price_cart_discount_crossout'] == 'no') )  {
        remove_filter('woocommerce_cart_item_subtotal', array( &$this, 'vtprd_maybe_cart_item_subtotal' )); //v2.0.0 g solution                  
        return $subtotal;       
    }

 
  
    //global $wp_query; //v1.1.0.7
    //$page_id = $wp_query->post->ID;  //v1.1.0.7
    //if ( get_the_ID () == get_option ( "woocommerce_cart_page_id" ) ) {
    //******
    //DO NOT do subtotal crossoutss on the Cart page product line, already doing the Unit Price crossouts.
    //******

    //if ( $page_id == get_option ( "woocommerce_cart_page_id" ) ) { //v1.1.0.7
    $cart_page = get_option ( "woocommerce_cart_page_id" );    //v1.1.0.7
    if ( is_page($cart_page)) { //v1.1.0.7
      return $subtotal;    
    }

    //v1.1.1 begin 
    //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting)
    /*    
    //pick up the previously stored crossout info
    if ( (isset($_SESSION['vtprd_product_cart_unit_price_oldprice_'.$product_id]) ) &&
         ($_SESSION['vtprd_product_cart_unit_price_oldprice_'.$product_id] > ' ' ) ) {
        $oldprice_subtotal = ($_SESSION['vtprd_product_cart_unit_price_oldprice_'.$product_id] * $cart_item['quantity']);
        $oldprice_subtotal_html = wc_price( $oldprice_subtotal ); 
        //v1.1.0.8 begin
        // do not display duplicate price in crossout  (on checkout page, if discount coupon removed)
        if ($oldprice_subtotal_html != $subtotal) {
          $subtotal = '<del>' . $oldprice_subtotal_html  . '</del> &nbsp; <ins>' . $subtotal . '</ins>'; 
        }
        //v1.1.0.8 end                    
    }
    */
      
      if (isset ($_SESSION['vtprd_orig_cart_price_with_taxation_'.$cart_item_key])) {

        $oldprice_subtotal = wc_price( $_SESSION['vtprd_orig_cart_price_with_taxation_'.$cart_item_key] * $cart_item['quantity'] );
        
        $_SESSION['vtprd_oldprice_subtotal_with_taxation_'.$product_id] = $oldprice_subtotal; //CANNOT be accessed by Addon and Calculator products
                        
        //v1.1.0.8 begin
        // do not display duplicate price in crossout  (on checkout page, if discount coupon removed)
        if ($oldprice_subtotal != $subtotal) {
          //error_log( print_r(  '<del> 003', true ) );
          $subtotal = '<del>' . $oldprice_subtotal  . '</del> &nbsp; <ins>' . $subtotal . '</ins>'; 
        }
      }           
     //v1.1.1 end
         
    return $subtotal;
     
  }


  //**************************************
	//v1.1.0.3 new function 
  //v1.1.0.6 refactored for 'free' processing
  //  add crossouts when order placed, to order-details and emails
  //**************************************
  public function vtprd_maybe_order_formatted_line_subtotal($subtotal, $item, $data){
 
    global $post, $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart;
    vtprd_debug_options();  //v1.0.5

     //error_log( print_r(  'Function begin - vtprd_maybe_order_formatted_line_subtotal', true ) ); 
     //error_log( print_r(  '$ITEM=', true ) );
     //error_log( var_export($item, true ) );


    //v1.1.0.6 moved these 2 ifs to the top 
    
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return $subtotal;   
		}
	  //v1.1.1 end
      
    //v1.1.7 begin ==>> ITEM has changed, post 3.0.0 use get_product_id...
    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0
      if ($item['variation_id'] > '0') {      
        $product_id  = $item['variation_id'];
      } else { 
        $product_id  = $item['product_id'];    
      } 
    } else {
       $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
    }
    //v1.1.7 end    
       
    //v1.1.0.6 added
    if ($item['line_subtotal'] == 0) { 
    
      //v2.0.2.0 begin
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0011 ', true ) );
      $get_previous_auto_add_array = vtprd_get_transient_cart_data ('previous_auto_add_array');
      if ($get_previous_auto_add_array)  {
        $previous_auto_add_array = unserialize($get_previous_auto_add_array);
      //if (isset($_SESSION['previous_auto_add_array']))  {
        //$previous_auto_add_array = unserialize($_SESSION['previous_auto_add_array']);
      //v2.0.2.0 end         

        if ( ($product_id  == $previous_auto_add_array['free_product_id']) &&
             (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE
           return  __('Free', 'vtprd');   //v1.1.1
        }
      } 
      
    }    
    //v1.1.0.6 end
         

    //If discount in coupon, or show no crossouts, exit stage left
    if ( (!isset($vtprd_setup_options)) ||
         ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') ||
         ($vtprd_setup_options['show_unit_price_cart_discount_crossout'] == 'no') )  {
      remove_filter('woocommerce_order_formatted_line_subtotal', array( &$this, 'vtprd_maybe_order_formatted_line_subtotal' )); //v2.0.0 g solution     
      return $subtotal;    
    } 

     //error_log( print_r(  'Getting the Oldprice Subtotal, for $product_id= ' .$product_id, true ) );

    //v1.1.1 refactored
    //pick up the previously stored crossout info
    if (isset($_SESSION['vtprd_oldprice_subtotal_with_taxation_'.$product_id]) ) {
      $oldprice = $_SESSION['vtprd_oldprice_subtotal_with_taxation_'.$product_id];      
      //error_log( print_r(  '<del> 004', true ) );
      //v2.0.0.7a begin
      // verify that there is an actual discount first
      if ($oldprice != $subtotal) {
        $subtotal = '<del> <span style="text-decoration: line-through;">' . $oldprice  . '</span></del> &nbsp; <ins>' . $subtotal . '</ins>';  //v1.1.0.5 added line-through span for old email clients
      }
      //v2.0.0.7 end                                  
    }

   
    return $subtotal;
     
  }

  //*****************************
  //v1.1.1 new function
  //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting)
  //  redo totals to pick up 'WC_Measurement_Price_Calculator' pricing 
  //    only if a calculator product is actually in the cart
  /*
  		public function get_cart_total() {
			if ( ! $this->prices_include_tax ) {
				$cart_contents_total = wc_price( $this->cart_contents_total );
			} else {
				$cart_contents_total = wc_price( $this->cart_contents_total + $this->tax_total );
			}

			return apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );
		}
  */
 
 
  
  //*****************************
  //v1.1.1  new function for Bolt-ons
  //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting)
  //executed from return apply_filters( 'woocommerce_cart_subtotal', $cart_subtotal, $compound, $this );
  //*****************************
  /*
  public function vtprd_maybe_cart_subtotal($cart_subtotal, $compound, $context ) {  //$context = $this from the call...
  
    //error_log( print_r(  'Function begin - vtprd_maybe_cart_subtotal', true ) );   
       
      if ($_SESSION['calculator_product_in_cart'] == 'no') {
  //error_log( print_r(  'tprd_maybe_cart_subtotal - exit with no processing ', true ) );        
        return $cart_subtotal;
      }  
      
  //Copied in its entirety from includes/class-wc-cart.php ==>> public function get_cart_subtotal( $compound = false )
  
			$cart_contents_vark = $this->vtprd_cart_contents_total();
      
      // If the cart has compound tax, we want to show the subtotal as
			// cart + shipping + non-compound taxes (after discount)
			if ( $compound ) {

		  //$cart_subtotal = wc_price( $context->cart_contents_total + $context->shipping_total + $context->get_taxes_total( false, false ) );
        $cart_subtotal = wc_price( $cart_contents_vark + $context->shipping_total + $context->get_taxes_total( false, false ) );

			// Otherwise we show cart items totals only (before discount)
			} else {

				// Display varies depending on settings
				if ( $context->tax_display_cart == 'excl' ) {

				//cart_subtotal = wc_price( $context->subtotal_ex_tax );
          $cart_subtotal = wc_price( $cart_contents_vark );

					if ( $context->tax_total > 0 && $context->prices_include_tax ) {
						$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}

				} else {
				
        //cart_subtotal = wc_price( $context->subtotal_ex_tax );
          $cart_subtotal = wc_price( $cart_contents_vark );

					if ( $context->tax_total > 0 && !$context->prices_include_tax ) {
						$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}

				}
			}
 //error_log( print_r(  'tprd_maybe_cart_subtotal - exit with result=  ' .$cart_subtotal, true ) ); 
		return $cart_subtotal;	
		  
  }
  */
    
  //*****************************
  //v1.1.1  new function for Bolt-ons
  //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting)
  //*****************************
/*
  public function vtprd_cart_contents_total() {
  
    //error_log( print_r(  'Function begin - vtprd_cart_contents_total', true ) );   
  //error_log( print_r(  '$cart_contents_total on input= ' .$cart_contents_total, true ) );
       
      if ($_SESSION['calculator_product_in_cart'] == 'no') {
  //error_log( print_r(  'vtprd_cart_contents_total - exit with no processing ', true ) );        
        return $cart_contents_total;
      }
      
      global $vtprd_cart;
      
error_log( print_r(  'vtprd_cart', true ) );
error_log( var_export($vtprd_cart, true ) ); 
              
      $cart_contents_total_with_tax = 0;
      
      foreach($vtprd_cart->cart_items as $vtprd_key => $vtprd_cart_item) {      
  
        if ($vtprd_cart_item->product_catalog_price_displayed == 0)  {
           continue;  //skip to next in foreach
        }
        
        if ($vtprd_cart_item->yousave_total_amt > 0) {
          $unit_price = $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $vtprd_cart_item->discount_unit_price);
        } else {
          $unit_price = $this->vtprd_get_taxation_price_cart($vtprd_cart_item->product_id, $vtprd_cart_item->unit_price);      
        }
        
        $subtotal = round($unit_price * $vtprd_cart_item->quantity, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
  
        $cart_contents_total_with_tax += $subtotal;
      } //end foreach
      
  //error_log( print_r(  'vtprd_cart_contents_total - exit at bottom total= ' .$cart_contents_total_with_tax, true ) ); 
       
      return $cart_contents_total_with_tax;     
  }
*/  
  
  
  //v1.0.7.4 new function
  public function vtprd_show_shop_price() {
     //error_log( print_r(  'Function begin - vtprd_show_shop_price', true ) );
    global $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart; 
   
    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {         
      $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
        
      //suffix gets added automatically, blank if no suffix provided ...
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
            // case ( $woocommerce->customer->is_vat_exempt()):    //v1.0.7.9
            case ( vtprd_maybe_customer_tax_exempt() ):            //v1.0.7.9  
                $price = $vtprd_info['product_session_info']['product_discount_price_excl_tax_woo'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price = $vtprd_info['product_session_info']['product_discount_price']; 
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price = $vtprd_info['product_session_info']['product_discount_price_excl_tax_woo'];    
              break;
          } 
      } else {      
          switch( true ) {
            // case ( $woocommerce->customer->is_vat_exempt()):   //v1.0.7.9
            case ( vtprd_maybe_customer_tax_exempt() ):           //v1.0.7.9
                $price = $vtprd_info['product_session_info']['product_discount_price'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price = $vtprd_info['product_session_info']['product_discount_price_incl_tax_woo'];
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price = $vtprd_info['product_session_info']['product_discount_price'];    
              break;
          }                 
      }    
    } else {
      $price = $vtprd_info['product_session_info']['product_discount_price']; 
    }
   
    return $price;
  }
  
  //****************************
  //v1.0.7.4 new function
  //v1.0.9.3  refactored
  //  $justThePricing = yes only when doing variation group presentation - crossouts and suffixes are introduced later
  //****************************
  public function vtprd_show_shop_price_html($justThePricing = null) {
     //error_log( print_r(  'Function begin - vtprd_show_shop_price_html', true ) );
    global $vtprd_info, $vtprd_setup_options, $woocommerce, $vtprd_cart; 

    vtprd_debug_options();  //v1.1
    
    $price_html = '';  //v1.0.8.0 
    
    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
      //suffix gets added automatically, blank if no suffix provided ...
      $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
      
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
         // case ( $woocommerce->customer->is_vat_exempt()):
            case ( vtprd_maybe_customer_tax_exempt() ):      //v1.0.7.9  
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_excl_tax_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);  
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_excl_tax_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);    
              break;
          }       
      } else {      
          switch( true ) {
        //  case ( $woocommerce->customer->is_vat_exempt()):
            case ( vtprd_maybe_customer_tax_exempt() ):      //v1.0.7.9 
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_incl_tax_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);  
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];
                //$price_html = $this->vtprd_maybe_show_crossouts($price_contents);    
              break;
          }                    
      }    
    } else { 
      $price_contents = $vtprd_info['product_session_info']['product_discount_price_html_woo'];      
    }
  
    
    if ($justThePricing == 'yes') {
      $price_html = $price_contents;
    } else {
      $price_contents .= $vtprd_info['product_session_info']['product_discount_price_suffix_html_woo'];
      $price_html = $this->vtprd_maybe_show_crossouts($price_contents);     
    }

    return $price_html;
  }


  //v1.0.7.4 new function
  //v1.0.9.3 refactored
//  public function vtprd_maybe_show_crossouts($price_contents, $justGetOldPrice = null) {
  public function vtprd_maybe_show_crossouts($price_contents) {
     //error_log( print_r(  'Function begin - vtprd_maybe_show_crossouts', true ) );  
    global $vtprd_setup_options, $vtprd_info;     
    
    if ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes')  {
      
      //v1.1.8.1 begin  
      $old_price = wc_price($vtprd_info['product_session_info']['product_list_price_catalog_correctly_taxed']);
      // $old_price = $vtprd_info['product_session_info']['product_orig_price_html_woo'];
      //v1.1.8.1 end
    
    	//v2.0.0.7 begin
      // verify that there is an actual discount first
      if ($old_price != $price_contents) {     
        //error_log( print_r(  '<del> 005', true ) );
        $price_html = '<del>' . $old_price . '</del><ins>' . $price_contents . '</ins>';
      } else { 
        $price_html = $price_contents;
      } 
      //v2.0.0.7 end 
         
    } else {
      $price_html = $price_contents;  
    }
    
    //v1.1.1 - only add in the suffix if there is a discount!!
    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  { 
      $price_html = $this->vtprd_maybe_show_pricing_suffix($price_html);
    }
    
  
    return $price_html;
  }
 
  //*************************************************************************
  //v1.0.9.3 new function
  //*************************************************************************
	public function vtprd_maybe_show_pricing_suffix($price_html){
     //error_log( print_r(  'Function begin - vtprd_maybe_show_pricing_suffix', true ) ); 
    global $vtprd_setup_options, $vtprd_info;    
   
    //v1.0.9.0  begin
    if ($vtprd_setup_options['show_price_suffix'] > ' ')  {
        $price_display_suffix = $vtprd_setup_options['show_price_suffix'];
        
        if ( (strpos($price_display_suffix,'{price_save_percent}') !== false)  ||
             (strpos($price_display_suffix,'{price_save_amount}')  !== false)   ||
             (strpos($price_display_suffix,'{sale_badge_product}') !== false) ) {   //does the suffix include these wildcards?
          //  $price_including_tax = vtprd_get_price_including_tax($product_id, $discount_price); 
          //  $price_excluding_tax = vtprd_get_price_excluding_tax($product_id, $discount_price); 
           
          $find = array(    //wildcards allowed in suffix
  				  '{price_save_percent}',
  		      '{price_save_amount}',
            '{sale_badge_product}'
  			  ); 
          $price_save_percent = $vtprd_info['product_session_info']['product_yousave_total_pct'] . '%';
          
          //show "$$ saved" with appropriate taxation
          if (strpos($price_display_suffix,'{price_save_amount}')  !== false) {
            $price_save_amount = $this->vtprd_show_price_save_amount();
          }
          //$price_save_amount = wc_price( $vtprd_info['product_session_info']['product_yousave_total_amt'] );
          
          //this span allows the user to attach a sale badge to each price, via CSS, using the background-image property. 
          $sale_badge_product = '<span class="sale_badge_product" id="sale_badge_product_' .$vtprd_info['product_session_info']['product_id']. '"> &nbsp; </span>';
          
          //replace the wildcards in the suffix!            
          $replace = array(
    			//	wc_price( $this->get_price_including_tax() ),
    			//	wc_price( $this->wc_get_price_excluding_tax() )
            $price_save_percent,  
            $price_save_amount,
            $sale_badge_product 
    			);
          
          $price_display_suffix = str_replace( $find, $replace, $price_display_suffix );
        }
                                    
        //then see if additonal suffix is needed
        if (strpos($price_html, $price_display_suffix) !== false) { //if suffix already in price, do nothing  //v1.1.1 wrong comparison fix
          $do_nothing;
        } else {
          $price_html =  $price_html . '<span class="pricing-suffix">' . $price_display_suffix . '</span>';
        }
        
    }
    //v1.0.9.0  end
        
    return $price_html;  
  }
 
  
  //*************************************************************************
  //v1.0.9.3 new function
  //*************************************************************************
	public function vtprd_show_price_save_amount(){ 
     //error_log( print_r(  'Function begin - vtprd_show_price_save_amount', true ) ); 
    global $vtprd_setup_options, $vtprd_info; 

    if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
      //suffix gets added automatically, blank if no suffix provided ...
      $woocommerce_tax_display_shop = get_option( 'woocommerce_tax_display_shop' );
      
      if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {      
          switch( true ) {
            case ( vtprd_maybe_customer_tax_exempt() ):      
                $price_contents = $vtprd_info['product_session_info']['product_catalog_yousave_total_amt_excl_tax_woo'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_catalog_yousave_total_amt_excl_tax_woo'];
              break;
          }       
      } else {      
          switch( true ) {
            case ( vtprd_maybe_customer_tax_exempt() ):      
                $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];
              break; 
            case ( $woocommerce_tax_display_shop == 'incl'):
                $price_contents = $vtprd_info['product_session_info']['product_catalog_yousave_total_amt_incl_tax_woo'];
              break;           
            case ( $woocommerce_tax_display_shop == 'excl'):
                $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];
              break;
          }                    
      }    
    } else { 
      $price_contents = $vtprd_info['product_session_info']['product_yousave_total_amt'];      
    }       
    
    $price_contents = wc_price( $price_contents );
      
    return $price_contents;  
  }
  
  
  //*************************************************************************
  //FROM 'woocommerce_get_price' => Central behind the scenes pricing
  //  NOW only used for specific additional WOO PLUGINS  BOLT-ONS
  //    PLUGIN:: woocommerce-measurement-price-calculator
  //    PLUGIN:: woocommerce-product-addons
  /*
  
  NB ==>>THIS ROUTINE is never called during an actual CATALOG pricing, only before or after.
  
 
  v1.1.1 COMMENT
      *********************
      woocommerce_get_price  NEEDS the meta data associated with ADDONS and CALCULATOR and other bolt-on plugins
      *********************
      Bolt-on plugins differentiate things at the meta-data level, not at the product level.
      So a given product id can actually be DIFFERENT products, depending on the bolt-on meta data
      For example, if a product has a list of ADDONS, then the 'parent' and each Addon have the same product ID.  UGH.
      
      Since woocommerce_get_price doesn't have the meta data, the following code is required:
      
      in woocommerce_get_price  ++ CATALOG pricing
      ==>> Bolt-on plugins pricing has to tap-dance around to find the correct value MOST of the time
      instead of using the meta data against what is in the cart to identify the precise product to get CATALOG pricing
      
      in woocommerce_before_mini_cart
      ==>> where a discountCoupon is employed, get_price constantly overwrites the CATALOG pricing, and has to be massaged
      
      in woocommerce_before_calculate_totals
      ==>> the disappeared CATALOG values must be massaged back into the cart...



    *****************
    PROCESSING
    *****************
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    Essentially, maybe_get_price is used JUST for the bolt-on plugins, to get their
    CATALOG discounts into their entities at Catalog display time.
    
    The bolt-on entities don't carry the correct pricing in the cart UNTIL
    the data is massaged.  
    
    The massage takes place in two phases - mini_cart contents is massaged in 
    * vtprd_maybe_before_mini_cart * .
        
    The CART and CHECKOUT Contents are massaged in 
    * vtprd_maybe_before_calculate_totals *
    
    The crossouts are massaged in 
    * vtprd_maybe_cart_item_price_html *
    
    Each time, the data is refreshed as needed.
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

  */
  //*************************************************************************
 //*************************************************************************
	public function vtprd_maybe_get_price($price, $product_info){    
    //error_log( print_r(  'Function begin - vtprd_maybe_get_price', true ) );

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    //IF set to execute ONLY out of the get_current_price function in parent-functions,
    //   JUST to pick up product price from OTHER plugins ($currency conversion, etc)
    //  this will ALWAYS be true for that call
    
    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return $price;   
		}
    
    
    //****************************************************************
    //  IF vtprd_do_compatability_pricing set ON ITS OWN,
    //      Get_price is ON.  But it's only used to get the current price ONCE,
    //      and further Pricing Deals processing is NOT NEEDED
    //    DO NO further processing
    $do_get_price = apply_filters('vtprd_do_compatability_pricing',false);    
    //if ONLY vtprd_do_compatability_pricing, Exit
    //  only these additional plugins (tested by class) may continue!
    If ( ($do_get_price) &&
         (!class_exists('WC_Measurement_Price_Calculator')) &&
         (!class_exists('WC_Product_Addons')) ) {
        return $price;                  
    }
    //****************************************************************
    

	  //v1.1.1 end
      

    global $post, $vtprd_info, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options;		
    vtprd_debug_options();  //v1.0.5

     //********************
     //v1.0.8.9 begin
     //  rarely at checkout screen the "return $price" was happening!!
     //  added in the 'doing_ajax' logic
     //    needed because 'is_admin' doesn't work in ajax...
     //********************
     if ( defined('DOING_AJAX') && DOING_AJAX ) {
        $carry_on = true;
     } else {
       //v2.0.0 begin
       if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
            (defined( 'DOING_CRON' )) ) {      
            //error_log( print_r(  'vtprd_maybe_get_price - Admin or Cron FOUND, DO NOT RUN. REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
          return $price;              
       }
       //v2.0.0 end
     }   
     //v1.0.8.9 end
     //********************

    //v1.1.7 begin
    // As of WOO 3.0.0, can't handle the object that comes down in some calls, no way to access ID (or so it seems)
    if (( version_compare( WC_VERSION, '3.0.0', '>=' ) ) && 
        (is_object ($product_info))) {
      $product_id  =  $product_info->get_id();
    } else {      
      if ( (isset($product_info->variation_id)) &&  //v1.1.1
           ($product_info->variation_id > ' ') ) {      
        $product_id  = $product_info->variation_id;
      } else { 
        if ($product_info->id > ' ') {
          $product_id  = $product_info->id;
        } else {
          $product_id  = $product_info->product_id;
        }     
      }    
    }
    //v1.1.7 end




/*    if (( version_compare( WC_VERSION, '3.0.0', '>=' ) ) && 
        (is_object ($product_info))) {
      return $price;
    }
    //v1.1.7 end
      
    if ( (isset($product_info->variation_id)) &&  //v1.1.1
         ($product_info->variation_id > ' ') ) {      
      $product_id  = $product_info->variation_id;
    } else { 
      if ($product_info->id > ' ') {
        $product_id  = $product_info->id;
      } else {
        $product_id  = $product_info->product_id;
      }     
    }
*/

    if ($product_id <= ' ') {     
      return $price;
    }
    
    
    $vtprd_hold_cart = $vtprd_cart; //v1.1.1
         

    vtprd_maybe_get_price_single_product($product_id, $price);


    //-------------
    //v1.1.1 BEGIN
    //*****************************************************
    // ONLY FOR PLUGIN:: woocommerce-measurement-price-calculator
    // ONLY FOR PLUGIN:: woocommerce-product-addons
    //***************************************************** 
    // 
    //if this isn't a calculator product, exit!  This array is **only** there for calculator products 
    //if this isn't an addons product, exit!
    
    // weird thing happening with the calculator data even when deactivated, so checking plugin active on each as gateway.
 

    if ( ((class_exists('WC_Measurement_Price_Calculator')) &&
          ($vtprd_info['product_session_info']['product_has_calculator']))
            ||
         ((class_exists('WC_Product_Addons')) &&
          ($vtprd_info['product_session_info']['product_has_addons']))  ) { 
        $carry_on = true;
     } else {
        return $price;         
     }

    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount      
      //v1.1.1 begin
      $discount_price = $vtprd_info['product_session_info']['product_discount_price'];    
    } else {
      $discount_price = false;
    }
 
    
    //*****************************************************
    // PLUGIN:: woocommerce-product-addons
    //*****************************************************
    // if product has addons AND
    // if incoming price is greater than list, ADDONS have been added to the 
    //    Price and **WE SHOULDN'T TOUCH IT**!!!
    //    Addons have their own variation ID, so we can uniquely identify it out of the box!
     
    if ((class_exists('WC_Product_Addons')) &&
        ($vtprd_info['product_session_info']['product_has_addons'])  ) {  
      //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting) ==>> discountUnitPrice path
      if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') {
          if ( (isset($_SESSION['vtprd_addons_added_'.$product_id])) &&
               ($_SESSION['vtprd_addons_added_'.$product_id] > 0) ) {  //addons have increased the list value of the product!      
                 
            switch( true ) {
                case ($price > $vtprd_info['product_session_info']['product_list_price']):          
                    return $price;
                  break;
                case (($discount_price) && ($discount_price < $price) && ($price == $vtprd_info['product_session_info']['product_list_price']) ):                              
                    return $discount_price;
                  break; 
                default:               
                    return $price;
                  break;                         
            } 
                  
          } else {
            if ((($discount_price) && ($discount_price < $price))) {              
              return $discount_price;
            } else {            
              return $price;
            }
          }
      } else { 
         
          if ($price == $vtprd_info['product_session_info']['product_list_price'])  {           
              if ($discount_price) {
                return $discount_price;
              } else {
                return $price;
              }
          } else {             
             return $price;         
          }          
          
            
      }
        
    }   
    //v1.1.1 end
    //-------------

    //v1.1.1 BEGIN
    //*****************************************************
    // PLUGIN:: woocommerce-measurement-price-calculator
    //*****************************************************    
    // the discount price may already be correct from previous calls, in this odd situation - so don't do anything if there is no actual discount.

    if ($vtprd_info['product_session_info']['product_has_calculator']) {
      if (($discount_price) && 
          ($discount_price < $price)) {
        $price = $discount_price;
      }
    } else { //all OTHER pricing that comes through here
      if ($discount_price) {
        $price = $discount_price;
      }    
    }   
   return $price;

 }
                                                                                                                                                                                     
 
	public function vtprd_get_product_catalog_price_do_convert($price, $product_id = null, $variation = null){ 
     //error_log( print_r(  'Function begin - vtprd_get_product_catalog_price_do_convert', true ) );  

    global $post, $vtprd_info;
	vtprd_debug_options();  //v1.0.5


    $product_id_passed_into_function = $product_id;
    
    //if we are processing a variation, always get and pass the PARENT ID
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }
    if( get_post_field( 'post_parent', $product_id ) ) {
       $product_id = get_post_field( 'post_parent', $product_id );
    }  
    

    vtprd_get_product_session_info($product_id, $price);


    //were we passed a Variation ID to start with??
    if (($product_id_passed_into_function != $product_id ) && ($product_id_passed_into_function > ' ') ) {
      
      vtprd_recompute_discount_price($product_id_passed_into_function, $price);  
    }
  
 
    if ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0)  {     //v1.0.7.2  replaced 'product_discount_price' with 'product_yousave_total_amt' to pick up a FREE discount
      //$price = $vtprd_info['product_session_info']['product_discount_price'];
      $price = $this->vtprd_show_shop_price(); //v1.0.7.4
    } 
  
    return $price;   

  }

                                    
  /* ************************************************
  **  Price Filter -  Get display info for single product at add-to_cart time and put it directly into the cart.
  *     executed out of:  do_action in => wpsc-includes/ajax.functions.php  function wpsc_add_to_cart      
  *************************************************** */

/**
 * from cart.class.php => Validate Cart Product Quantity
 * Triggered by 'wpsc_add_item' and 'wpsc_edit_item' actions when products are added to the cart.
 *
 * @since  3.8.10
 * @access private
 *
 * @param int     $product_id                    Cart product ID.
 * @param array   $parameters                    Cart item parameters.
 * @param object  $cart                          Cart object.
 *
 * @uses  wpsc_validate_product_cart_quantity    Filters and restricts the product cart quantity.
 */
  //       add_action( 'wpsc_add_item', array(&$product_info, 'vtprd_get_product_catalog_price_add_to_cart'), 99, 3 );
 //       add_action( 'wpsc_edit_item', array(&$product_info, 'vtprd_get_product_catalog_price_add_to_cart'), 99, 3); 

   
  /* ************************************************
 
  *************************************************** */
	public function vtprd_test_for_html_crossout_use(){
     //error_log( print_r(  'Function begin - vtprd_test_for_html_crossout_use', true ) );
    global $vtprd_setup_options;
    
    //replaced by using this instead:  ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes') 
    
    if ( $vtprd_setup_options['show_catalog_price_crossout'] != 'yes') {
      return false;
    }
       
    $ruleset_has_only_display_rules = get_option('vtprd_ruleset_has_only_display_rules');
    if ($ruleset_has_only_display_rules) {
      return true;
    } else {
      return false;
    }

  } 
   
   

  /* ************************************************
  ** v1.1.1 new function
  *** PURCHASABLE JUST makes things HAVE NOT ADD TO CART  
  *************************************************** */
    /* 
     ***********************************************************************************************************
     ------  Product Purchasability settings switch and Pricing Visibility/Price Custom Message, via Filter  -----
     ***********************************************************************************************************
     *     
     ** The following filter **
     *     works with the "Catalog Products Purchasability Display Options" on the Pricing Deal Settings page.
     *     
     *  The "Catalog Products Purchasability Display Options" setting can control whether 
     *       the add-to-cart button is available for a given product, 
     *  based on product wholesale setting and the customer role/capabilities
     *  
     *  However, in the situation where the add-to-cart button is removed for a given product,
     *  there may also be the desire to replace the displayed product price
     *  with spaces, or a custom message.

     *  Filter "vtprd_replace_price_with_message_if_product_not_purchasable" 
     *   replaces the Product Price with a custom message where appropriate.  
     *      (This custom message may also contain HTML.) 
     *            
     ***********************************************************************************************************                                                                       
      
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    // *** add filter/function to the bottom of your ** Theme Functions file**
     
    //replaces price with message, if desired - message may include HTML
    add_filter('vtprd_replace_price_with_message_if_product_not_purchasable', 'do_replace_price_with_message_if_product_not_purchasable', 10, 1); 
    function do_replace_price_with_message_if_product_not_purchasable($return_smessage) {
      return 'Message to replace Price, if Product may not be Purchased by User';
    }    
    // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    */  
  //CALL from WOO contains ONLY $purchasable, $product
  //CALL from vtprd_maybe_catalog_price_html contains ONLY $product_ID
 
	public function vtprd_maybe_woocommerce_is_purchasable($purchasable, $product){ //v1.1.1.3 - dropped 3rd argument, fixed below
  
 //error_log( print_r(  'Function begin - vtprd_maybe_woocommerce_is_purchasable', true ) );
  
 //error_log( print_r(  'wholesale_products_price_display= ' .$vtprd_setup_options['wholesale_products_price_display'], true ) );
  
    global $vtprd_setup_options,$current_user; 
  
    vtprd_debug_options();
   
    if ( ( $vtprd_setup_options['wholesale_products_price_display'] == '' ) ||
         ( $vtprd_setup_options['wholesale_products_price_display'] == 'noAction' ) ) {       
      return true;  
    }
 
   
    //  "noPrices"      ==>  'No Pricing Displayed, Use Woo as a Catalog Only' 
    if ( $vtprd_setup_options['wholesale_products_price_display'] == 'noPrices' ) {       
      return false;  
    }
        
    /*
    
    User Retail       = UR
    User Wholesale    = UW
    Product Retail    = PR
    Product Wholesale = PW
    
    
    	"noAction"      ==>  'Show All'
      "onlyOnly"      ==>  'Retail Products Only are Purchasable for Retail, Only Wholesale Products purchasable for Wholesale Role'
                            UR + PR , UW + PW
    	"respective"    ==>  'Retail Products Only are Purchasable for Retail, All Products purchasable for Wholesale Role'
                            UR + PR , UW
      "wholesaleOnly" ==>  'No Products are Purchasable for Retail, Only Wholesale Products purchasable for Wholesale Role'
                            (none) , UW + PW
    	"wholesaleAll"  ==>  'No Products are Purchasable for Retail , All Products Purchasable for Wholesale Role'
                            (none) , UW
    	"noPrices"      ==>  'No Pricing Displayed, Use Woo as a Catalog Only'      
 	   */   
      
    $user_role = vtprd_get_current_user_role();
    if (($user_role == 'Wholesale Buyer') ||
        ($user_role == 'Wholesale Tax Free') ||
        (current_user_can( 'wholesale')) ) {
       //$customer_is_retail_or_wholesale = 'wholesale'; 
      $customer_may_see = 'wholesale';
    } else {       
      $customer_may_see = 'retail'; 
     
    }

    //	"wholesaleAll"  ==>  'No Products are Purchasable for Retail , All Products Purchasable for Wholesale Role'
    //                        (none) , UW                            
    if ( $vtprd_setup_options['wholesale_products_price_display'] == 'wholesaleAll' ) {
       switch( $customer_may_see ) {
          case ('retail'):          
              return false;        
            break;          
       
          case ('wholesale'):            
              return true;
            break;
       }
    } 
  
  
    
     //v1.1.7.2 begin   grpA
     // CHANGED continued use of $product to $product_id
    if ( (version_compare( WC_VERSION, '3.0.0', '>=' )) &&
         (is_object ($product)) ) { 
      $product_id = $product->get_id();
    } else { 
      if (isset($product->id)) { //call from WOO
        $product_id = $product->id;
      } else {
        $product_id = $product;
      }
    } 
    /*
    //v1.1.1.3 $product can be an array or an individual product key.  This fixes things!
    if (isset($product->id)) { //call from WOO
      $product = $product->id;
    }
    */ 
    /* //v1.1.1.3
    else { //CALL from vtprd_maybe_catalog_price_html
      $product;
    }*/    
    //v1.1.7.2 end  

    $product_is_wholesale = get_post_meta( $product_id, 'vtprd_wholesale_visibility', true );  //v1.1.7.2 grpA changed $product to $product_id


    switch( $vtprd_setup_options['wholesale_products_price_display'] ) {
    
      //  "onlyOnly"      ==>  'Retail Products Only are Purchasable for Retail, Only Wholesale Products purchasable for Wholesale Role'
      //                        UR + PR , UW + PW               
      case  ('onlyOnly' ) :    
           switch( $customer_may_see ) {
              case ('retail'):
                  if ($product_is_wholesale != 'yes')  { // == retail
                    return true;
                  } else {
                    return false;
                  }       
                break;
              case ('wholesale'):
                  if ($product_is_wholesale == 'yes')  {
                    return true;
                  } else {       
                    return false;
                  }
                  
                break;
              
           }
       break; 

       //	"respective"    ==>  'Retail Products Only are Purchasable for Retail, All Products purchasable for Wholesale Role'
       //                        UR + PR , UW              
       case  ('respective' ) :  
           switch( $customer_may_see ) {
              case ('retail'):
                  if ($product_is_wholesale != 'yes')  { // == retail
                    return true;
                  } else {       
                    return false;
                  }       
                break;         
              case ('wholesale'):     
                    return true;             
                break;
            
           }
        break;

      //  "wholesaleOnly" ==>  'No Products are Purchasable for Retail, Only Wholesale Products purchasable for Wholesale Role'
      //                        (none) , UW + PW              
      case  ('wholesaleOnly' ) :
           switch( $customer_may_see ) {
              case ('retail'):   
                    return false;       
                break;         
              case ('wholesale'):
                      if ($product_is_wholesale == 'yes')  {
                        return true;
                      } else {       
                        return false;
                      }
                                
                break;
            
           }
        break;
    
    } //END switch( $vtprd_setup_options['wholesale_products_price_display'

  }
   
    
  /* ************************************************
  ** v1.1.0.7 new function
  ** visible makes things INVISIBLE  
  ** v1.1.1 Refactored for clarity, some additions  
  *************************************************** */
	public function vtprd_maybe_woocommerce_product_is_visible($visible, $id){  //v1.1.1.3 changed function name to _product_
    //error_log( print_r(  'Function begin - vtprd_maybe_woocommerce_is_visible', true ) );
    global $vtprd_setup_options,$current_user; 
  
   vtprd_debug_options(); 
   
   if ( ( $vtprd_setup_options['wholesale_products_display'] == '' ) ||
        ( $vtprd_setup_options['wholesale_products_display'] == 'noAction' ) ) {
      return true;  
   }
        
    /*
    
    User Retail       = UR
    User Wholesale    = UW
    Product Retail    = PR
    Product Wholesale = PW
    
    
    	"noAction"      ==>  'Show All'
    	"respective"    ==>  'Show Retail Products to Retail, Wholesale Products to Wholesale Role'
                            UR + PR , UW + PW
    	"wholesaleAll"  ==>  'Show Retail Products to Retail, All Products to Wholesale Role'
                            UR + PR , UW
    	"retailAll"     ==>  'Show All Products to Retail, Wholesale Products to Wholesale Role' 
                            UR , UW + PW           
 	   */   

    $user_role = vtprd_get_current_user_role();
    if (($user_role == 'Wholesale Buyer') ||
        ($user_role == 'Wholesale Tax Free') ||
        (current_user_can( 'wholesale')) ) {
       //$customer_is_retail_or_wholesale = 'wholesale'; 
      $customer_may_see = 'wholesale';
      
    } else {       
      $customer_may_see = 'retail'; 
    
    }


    $product_is_wholesale = get_post_meta( $id, 'vtprd_wholesale_visibility', true );

    switch( $vtprd_setup_options['wholesale_products_display'] ) {
    
    	//  "respective"    ==>  'Show Retail Products to Retail, Wholesale Products to Wholesale Role'
      //                        UR + PR , UW + PW              
      case  ('respective' ) :    
           switch( $customer_may_see ) {
              case ('retail'):
                  if ($product_is_wholesale != 'yes')  { // == retail
                    return true;
                  } else {
                    return false;
                  }       
                break;
              case ('wholesale'):
                  if ($product_is_wholesale == 'yes')  {
                    return true;
                  } else {       
                    return false;
                  }
                  
                break;
              
           }
       break; 

    	 //  "wholesaleAll"  ==>  'Show Retail Products to Retail, All Products to Wholesale Role'
       //                       UR + PR , UW              
       case  ('wholesaleAll' ) :  
           switch( $customer_may_see ) {
              case ('retail'):
                  if ($product_is_wholesale != 'yes')  { // == retail
                    return true;
                  } else {       
                    return false;
                  }       
                break;         
              case ('wholesale'):     
                    return true;             
                break;
            
           }
        break;

    	//  "retailAll"     ==>  'Show All Products to Retail, Wholesale Products to Wholesale Role' 
      //                        UR , UW + PW               
      case  ('retailAll' ) :
           switch( $customer_may_see ) {
              case ('retail'):   
                    return true;       
                break;         
              case ('wholesale'):
                      if ($product_is_wholesale == 'yes')  {
                        return true;
                      } else {       
                        return false;
                      }
                                
                break;
            
           }
        break;
    
    } //END switch( $vtprd_setup_options['wholesale_products_display'


  } 
  
    
  /* ************************************************
  ** v1.1.1.3 new function  
  *************************************************** */
  //return apply_filters( 'woocommerce_variation_is_visible', $visible, $this->variation_id, $this->id, $this );
	public function vtprd_maybe_woocommerce_variation_is_visible($visible, $variation_id, $parent_product_id, $product){ 
  
    //error_log( print_r(  'Function begin - vtprd_maybe_woocommerce_variation_is_visible, $parent_product_id= ' .$parent_product_id, true ) );
    
    return $this->vtprd_maybe_woocommerce_product_is_visible($visible, $parent_product_id);
    
  }
  
  
      
  /* ************************************************
  ** Template Tag / Filter -  full_msg_line   => can be accessed by both display and cart rule types    
  *************************************************** */
	public function vtprd_show_product_discount_full_msg_line($product_id=null){
     //error_log( print_r(  'Function begin - vtprd_show_product_discount_full_msg_line', true ) );
    global $post, $vtprd_info;
       
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    } 
        
    //routine has been called, but no product_id supplied or available
    if (!$product_id) {
      return;
    } 
    
    vtprd_get_product_session_info($product_id);
       
    $output  = '<p class="discount-full-msg" id="fullmsg_' .$product_id. '">' ;
    for($y=0; $y < sizeof($vtprd_info['product_session_info']['product_rule_full_msg_array']); $y++) {
      $output .= $vtprd_info['product_session_info']['product_rule_full_msg_array'][$y] . '<br>' ;
    }      
    $output .= '</p>'; 
        
    echo $output;
    
    return;
  }  

 /* v1.1.7.2 begin grpb  NO LONGER USED    
  // from woocommerce/classes/class-wc-cart.php 
  public function vtprd_woo_get_url($pageName) {
     //error_log( print_r(  'Function begin - vtprd_woo_get_url', true ) );           
     global $woocommerce;
      $checkout_page_id = $this->vtprd_woo_get_page_id($pageName);
  		if ( $checkout_page_id ) {
  			if ( is_ssl() )
  				return str_replace( 'http:', 'https:', get_permalink($checkout_page_id) );
  			else
  				return apply_filters( 'woocommerce_get_checkout_url', get_permalink($checkout_page_id) );
  		}
  }
      
  // from woocommerce/woocommerce-core-functions.php 
  public function vtprd_woo_get_page_id($pageName) {
      //error_log( print_r(  'Function begin - vtprd_woo_get_page_id', true ) ); 
     //v1.1.7.2 begin  grpB
     if ($pageName == 'cart_url') {
     		$pageName2 = 'woocommerce_wc_get_cart_url';
     } else {
     		$pageName2 = 'woocommerce_get_' . $pageName;     	
     }		
      
    $page = apply_filters($pageName2 . '_page_id', get_option('woocommerce_' . $pageName . '_page_id'));
    //$page = apply_filters('woocommerce_get_' . $pageName . '_page_id', get_option('woocommerce_' . $pageName . '_page_id'));
    //v1.1.7.2 end
     //error_log( print_r(  'vtprd_woo_get_page_id - after "get"', true ) );
		return ( $page ) ? $page : -1;
  }  
 //v1.1.7.2 end
 */    
 /*  =============+++++++++++++++++++++++++++++++++++++++++++++++++++++++++    */
    


   // do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
   public function vtprd_ajax_add_to_cart_hook($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
      //error_log( print_r(  'Function begin - vtprd_ajax_add_to_cart_hook', true ) );
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
       }
      
      //**********
      //prevents recursive processing during auto add execution of add_to_cart! 
      //**********
      if ( (defined('VTPRD_PRO_DIRNAME'))  &&
           (isset($_SESSION['auto_add_in_progress'])) && 
                 ($_SESSION['auto_add_in_progress'] == 'yes') ) {
        $current_time_in_seconds = time();
        if ( ($current_time_in_seconds - $_SESSION['auto_add_in_progress_timestamp']) > '10' ) { //session data older than 10 seconds, reset and continue! 
          $contents = $_SESSION['auto_add_in_progress'];
          unset( $_SESSION['auto_add_in_progress'], $contents );
          $contents = $_SESSION['auto_add_in_progress_timestamp'];
          unset( $_SESSION['auto_add_in_progress_timestamp'], $contents ); 
        } else {
          return;
        }          
      }

      //prevents recursive updates
     // $_SESSION['update_in_progress'] == 'discount already processed';



  /*
      //UPDATE the DATA Chain immediately with the current woocommerce totals and coupon info.  That way,
      //  when the UPDATED hook is poassibly called DURING an auto-add within the add-to-cart, the info will be current.
      global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;   
         
      $data_chain      = unserialize($_SESSION['data_chain']); 
      if ($vtprd_rules_set == '') {
        $vtprd_rules_set = $data_chain[0];
        $vtprd_cart      = $data_chain[1];
      }
      $data_chain = array();
      $data_chain[] = $vtprd_rules_set;
      $data_chain[] = $vtprd_cart;
      $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
      $data_chain[] =  $woocommerce->cart->cart_contents_total;
      $data_chain[] =  $woocommerce->cart->applied_coupons;
      $data_chain[] = time(); //v2.0.0
      $_SESSION['data_chain'] = serialize($data_chain);             
 */   


      $this->vtprd_process_discount() ;

   //   $contents = $_SESSION['update_in_progress'];
   //   unset( $_SESSION['update_in_progress'], $contents );
      
      return;
      //return $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data;
   }
     

   //*************************************
   // v1.0.8.4  new function
   //recalc the cart if user changes, to pick up user/role-based rules
   
   //-----------------------
   //v2.0.2.0 - reworked
   /*   
   WOO works as follows:
    at WP_Login, WOO sets the switch '_woocommerce_load_saved_cart_after_login' in 
            update_user_meta( $user->ID, '_woocommerce_load_saved_cart_after_login', 1 );
    THEN
    at *NEXT* WP_Loaded, woo checks the user_meta switch to see if a session-stored cart should be merged in
            and deletes the user_meta switch.


	
	
   I FOLLOW ALONG:
    at WP_Login
            - set the switch to VERIFY the WOO SAVED cart vs MY SAVED CART
          update_user_meta( $user_ID, '_vtprd_check_saved_cart_after_login', 'yes' );
            - set the action to do grab the WOO saved cart, before WOO merges a saved cart and any existing cart
          add_action( 'woocommerce_load_cart_from_session', 'vtprd_get_and_set_saved_woo_session_cart' );



            
    The Code which rolls out any freebies on_login_change below, can be 
        set to run *only* if **the cart is already current**
    OTHERWISE
        set our own user_meta switch HERE (to be checked at wp_login)
    THEN
   at *NEXT* WP_Loaded, with a very low priority, check the vtprd_saved_WOO_Cart against the current cart
    - if a match, roll out any auto_adds in the RESTORED SAVED CART
    - compare WC()->session->get_saved_cart();  with the SAVED cart at end of apply_rules
    - can't roll out saved cart FREE products UNTIL WOO gets it in wp_loaded.
    
    **
    - so with WP_Loaded -> action woocommerce_load_cart_from_session verify that saved cart matches my saved cart
     - SESION SAVED CART vs MY saved CART
     - OR
     - Current cart vs MY saved cart, to catch any saved carts from other plugins
    **

    
    - then IN a very lowp riority WP_Loaded AFTER the one above, IF the saved cart matches, and the products are still there, ROLL OUT the FREE items.
    
       WHAT ABOUT A SAVED CART FROM ANOTHER PLUGIN???
         
    
    vtprd_get_transient_cart_data ('woo_cart_contents_with_auto_add', $cart_id);


        
function wc_user_logged_in( $user_login, $user ) {
	wc_update_user_last_active( $user->ID );
	update_user_meta( $user->ID, '_woocommerce_load_saved_cart_after_login', 1 );
}
add_action( 'wp_login', 'wc_user_logged_in', 10, 2 );



woocommerce/includes/class-wc-cart-session;
	public function get_cart_from_session() {
		do_action( 'woocommerce_load_cart_from_session' );

if meta '_woocommerce_load_saved_cart_after_login' true, merge in saved cart.
Then delete meta.

SO - set a switch on login (session switch or DB?)
apply SAVED info when load_cart_from_session is run,
action executes and switch is on!        
        

           
   */
   //-----------------------
   
   //*************************************
   //v2.0.2.0 new function   
   //*************************************
   public function vtprd_update_on_login() {  //v1.1.7 removed passed info '($user_login, $user)' - problem with php7
      //error_log( print_r(  'Function begin - vtprd_update_on_login_change', true ) );
      global $woocommerce;

      
      //v1.0.9.4 begin - force the CATALOG rules to be redone
      vtprd_debug_options(); //v1.1  
      
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }  
  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        //error_log( print_r(  'Function begin - vtprd_update_on_login  return 001', true ) );
        return;   
  	   }
  	  //v1.1.1 end   
      
      /* test test - we need the user_id processing below, can't do this!
      //v2.0.2.0 begin
      //is there a cart object, and does it have contents?  If not, we don't care.
      if (is_object($woocommerce))  {       
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();
        if ( sizeof($woocommerce_cart_contents) == 0 ) {       
           return;                  
        }
      } else {
        return;
      }
      //v2.0.2.0 end
      */
  	

      /* **********************************************************************************************************
      IS NOT ACCURATE HERE!!!!!!!!!!! 
          
      is_user_logged_in() ==>> Before the plugins_loaded action it returns 0.
      get_current_user_id() ==>> Before the plugins_loaded action it returns 0.
      */
    
        
        // $_SESSION['vtprd_customer_id'] =  $user_ID; NO, this will be picked up LATER

        /*
        if AUTO ADD IN PROCESS
            WE WILL NEED TO CHECK IF A ROLL OUT IN THE RESTORED CART IS REQUIRED
        this switch is picked up in the add_action('woocommerce_load_cart_from_session' ...
         that is from the beginning of the woo action which picks up a stored cart and merges it in.
         this is in woocommerce/includes/class-wc-cart-session;
               function get_cart_from_session
          which is initiated at wp_loaded time
        */
        /*
        //if(vtprd_get_transient_cart_data('woo_cart_contents_with_auto_add')) {  SHOULD BE DONE REGARDLESS, JUST IN CASE!
        There may be an auto add in the not-logged in cart, OR the saved logged in cart.  Both need to be processed.
        */
        
        //THIS INITIATES A SERIES OF STEPS, WHICH WILL CHECK WHETHER ANY RESTORED SAVED CART HAS AN AUTO ADD WHICH NEEDS TO BE ROLLED OUT



      
      /*
      is $_SESSION['vtprd_unique_cart_id'] already there?
      If so, there is an active cart session
      If not, it's 1st time in, and we should check for a stored cart
      In either case, we navigate to transient type 'woo_cart_contents_with_auto_add', set during auto add in PRO apply_rules
      check if 'woo_cart_contents_with_auto_add' has data
      */
 
      /*
      //v2.0.2.0 - apply here if:
       - user just logged out, and we need to roll autoadds out of cart
       - user just logged in.  The current cart may have auto adds, 
            AS WELL as any abandoned cart
            SO roll out with the current cart, but ALSO with any restored abandoned cart later merged in.
      */


/*
User logout essentially clears the current cart, which then presents as EMPTY.
HOWEVER, the logged-in cart is saved for future use!
HENCE the reset of the cart_id, AND the RETURN, above
*/

      //v2.0.2.0 REWORKED



      
    //test test
    //$cart_id = $_SESSION['vtprd_unique_cart_id'];  
    //error_log( print_r(  'before vtprd_update_on_login_change rollout  $cart_id= ' .$cart_id, true ) );
    //test test
    
    
    
      
      //v1.1.7.1 begin
      //****************************************************
      //clear out any auto adds made ***before** LOGIN  (in the 'not logged in' state)
      //****************************************************
      //unfortunately does the work twice, but better result, I think...
      if (isset($_SESSION['vtprd_unique_cart_id']) ) {
        $cart_id = $_SESSION['vtprd_unique_cart_id']; 
        global $vtprd_info;
        if ( (defined('VTPRD_PRO_DIRNAME'))  &&
             (vtprd_get_transient_cart_data('woo_cart_contents_with_auto_add',$cart_id)) ) {
  
          //v1.1.8.0 begin
          // Function vtprd_get_previous_auto_add_array() only exists in PRO, 
          // and if PRO is not active due to upgrade, this gives a FATAL
          //v1.1.8.0 end
          
          //------------------------------------     
          //$vtprd_apply_rules = new VTPRD_Apply_Rules;   moved below 
          //$previous_auto_add_array = $vtprd_apply_rules->vtprd_get_previous_auto_add_array();        
          //error_log( print_r(  'before vtprd_get_transient_cart_data  0013  $cart_id= ' .$cart_id, true ) );
          
          
          $get_previous_auto_add_array = vtprd_get_transient_cart_data ('previous_auto_add_array',$cart_id);       
          //------------------------------------ 
  
          //******************************
          //prevents recursive processing during auto add execution of add_to_cart!
          //v1.1.0.6 placed at top of routine
          //******************************
          //  otherwise there would be an endless loop via both add_to_cart and set_quantity  ...
          $_SESSION['auto_add_in_progress'] = 'yes';
          //add_in_progress switch will be overriden in 10 seconds using the timestamp (also shut off at bottom of this routine)
          $_SESSION['auto_add_in_progress_timestamp'] = time();
          //******************************
  
          //only roll out previous stuff, if NO current stuff to add
          //if (sizeof($previous_auto_add_array) > 0) {  //v2.0.2.0
          if ($get_previous_auto_add_array)  {   
             $previous_auto_add_array = unserialize($get_previous_auto_add_array);  
             vtprd_maybe_roll_out_prev_auto_insert_from_woo_cart($previous_auto_add_array, 'all'); 
              /*
             //v2.0.2.0 begin
             //$this->vtprd_maybe_remove_previous_auto_add_array();
             vtprd_del_transient_cart_data_by_data_type ('previous_auto_add_array', $cart_id);
             vtprd_del_transient_cart_data_by_data_type ('woo_cart_contents_with_auto_add', $cart_id);
             $this->vtprd_cart_updated(); 
             */
             
             // WE'VE ROLLED OUT THE 'NOT LOGGED IN' AUTO ADDS.  WE NOW DELETE ALL DB RECORDS AND CLEAR ANY SESSION INFO
             vtprd_del_transient_cart_data_by_cart_id ($cart_id); //delete all transients for the existing cart ID
  
             //v2.0.2.0 end
          }                
        } else {
          //if no current auto-adds, just clean up the DB
          vtprd_del_transient_cart_data_by_cart_id ($cart_id); //delete all transients for the existing cart ID
        }
      }
      //v1.1.7.1 end

 
      //v2.0.2.0 removed.  If logout, done above.  If login, done LATER in housekeeping on next iteration of wp_loaded
  	    /*
        //v2.0.1.0 begin - wrap in is_object test
      if (is_object($woocommerce))  {       
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();
        if ( sizeof($woocommerce_cart_contents) > 0 ) {       
           //this re-does the CART rules
           error_log( print_r(  'vtprd_cart_updated 001 ', true ) );
           $this->vtprd_cart_updated(); 
                
        }
      }
      */
  	  //v2.0.1.0 end 
      
      session_destroy(); //get rid of ALL session data, nothing saved , particularly the CATALOG discounts   
      
      $_SESSION['at_login_check_saved_cart_in_housekeeping'] = TRUE;
              
      //update_user_meta( $user_ID, '_vtprd_check_saved_cart_after_login', 'yes' );
      
      //this action precedes the cart merge for any saved cart, allows us go get the 'previous' cart image for comparison
      
      add_action( 'woocommerce_load_cart_from_session', 'vtprd_get_and_set_saved_woo_session_cart' );
        
      return; 
   }

   //*************************************
   //v2.0.2.0 new function   
   //*************************************
   public function vtprd_update_on_logout() {  //v1.1.7 removed passed info '($user_login, $user)' - problem with php7
      //error_log( print_r(  'Function begin - vtprd_update_on_logout', true ) );
      global $woocommerce;

      //v1.0.9.4 begin - force the CATALOG rules to be redone
      vtprd_debug_options(); //v1.1  
      
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }  
  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
  	   }
  	  //v1.1.1 end   

      
      //$user_logged_out = true;
      //if user logged out, then we leave all the rows on the DB for a future log in.
      // we just have to change the unique key, so the existing logged-in rows are undisturbed,
      
      //   ******************************************************    
      // as WOO will generate a clean, new cart after the logout. (login = a MERGED cart)
      //   ******************************************************
      
      //create a random cart id, resets the unique session 

      session_destroy(); //get rid of ALL session data, nothing saved
      //error_log( print_r(  'Function begin - vtprd_update_on_logout  return', true ) );
      return; 


   }

 
   // do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
   public function vtprd_cart_updated() {
       //error_log( print_r(  'Function begin - vtprd_cart_updated', true ) );
       //error_log( print_r(  'vtprd_cart_updated - Admin or Cron FOUND, DO NOT RUN. REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) ); 

       //v2.0.0 begin
       global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options;
                
       //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
       if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
            (strpos($_SERVER["REQUEST_URI"],'wp-login') !== false) ||    //v2.0.2.0 if was logged in and logs out, don't destroy the logged-in cart!
            (defined( 'DOING_CRON' )) ) { 
            //error_log( print_r(  'vtprd_cart_updated - Admin or Cron FOUND, DO NOT RUN. REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) ); 
          return;          
       } 
       //v2.0.0 end     

                        
  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }
      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
  		}
  	 //v1.1.1 end

       //v2.0.2.0 begin
      if (!is_object($woocommerce)) {
        return;
      }      
      
      
      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
      
  //error_log( print_r(  'vtprd_cart_updated - $woocommerce_cart_contents= ', true ) );
  //error_log( var_export($woocommerce_cart_contents, true ) );
 
      if ( ($woocommerce->cart->cart_contents_total == 0) &&
           ($woocommerce->cart->cart_contents_count == 0) ) {       //this covers things if there is still a FREE item in the cart
        //cleanup and DONE
        //argh4 test
        if (isset($_SESSION['vtprd_unique_cart_id']) ) {
          vtprd_del_transient_cart_data_by_cart_id($_SESSION['vtprd_unique_cart_id']);

      //error_log( print_r(  'vtprd_cart_updated - DATABASE CLEARED,  $cart_id= ' .$cart_id, true ) );
        } 
  
        return;
      }
       //v2.0.2.0 end

      //v2.0.0 begin  vtprd-info array not always there!
      if (!is_array($vtprd_info)) {
        require_once ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-definitions.php');
      } 
      //v2.0.0 end


      //------------------------------------------------
      //v2.0.0 P SOLUTION
      //------------------------------------------------
      //  auto add for free + coupon activation issue on checkout page when processing new coupon initiation
      //------------------------------------------------      
      // if checkout page, does not run in ajax, so has to be done at initial load
      // if auto add free product possible and not yet done
      // get rid of existing session, which otherwise causes a hangup in processing
      //------------------------------------------------
      //  session_destroy(); in parent-functions   function vtprd_check_for_page()
      //------------------------------------------------         
      
         
    //Open Session Variable, get rules_set and cart if not there....
    
    vtprd_debug_options();  //v1.1

    $data_chain = $this->vtprd_get_data_chain();

    //v1.1.7.2 begin grpB
    if ( isset ($data_chain[1]) ) {   
      $previous_cart                     = $data_chain[1];
    } else {
      $previous_cart                     = ''; 
    }
    //v1.1.7.2 end
    
    //v1.0.8.0  begin
    if ( isset ($data_chain[2]) ) {   
      $previous_user_role                = $data_chain[2]; //v1.0.7.2  added
    } else {
      $previous_user_role                = ''; 
    }
    if ( isset ($data_chain[3]) ) {   
      $woo_cart_contents_total_previous  = $data_chain[3]; //v1.0.7.2  changed occurrence numbers
    } else {
      $woo_cart_contents_total_previous  = ''; 
    }
    if ( isset ($data_chain[4]) ) {   
      $woo_applied_coupons_previous      = $data_chain[4]; //v1.0.7.2  changed occurrence numbers 
    } else {
      $woo_applied_coupons_previous      = ''; 
    }
    //v1.0.8.0  end
    
    //**********
    //prevents recursive processing during auto add execution of add_to_cart! 
    //**********
    if ( (defined('VTPRD_PRO_DIRNAME'))  &&
         (isset($_SESSION['auto_add_in_progress'])) && 
               ($_SESSION['auto_add_in_progress'] == 'yes') ) {
      $current_time_in_seconds = time();
      if ( ($current_time_in_seconds - $_SESSION['auto_add_in_progress_timestamp']) > '10' ) { //session data older than 10 seconds, reset and continue! 
        $contents = $_SESSION['auto_add_in_progress'];
        unset( $_SESSION['auto_add_in_progress'], $contents );
        $contents = $_SESSION['auto_add_in_progress_timestamp'];
        unset( $_SESSION['auto_add_in_progress_timestamp'], $contents ); //v1.1.6.8 double semi colon removed          
      } else { 
        //error_log( print_r(  'Function - vtprd_cart_updated return001', true ) );
        return;
      }          
    }
 
    //$woocommerce_cart_contents = $woocommerce->cart->get_cart(); //v1.1.1.3 moved here from below      moved UP

     //error_log( print_r(  '$woocommerce_cart_contents', true ) );
     //error_log( var_export($woocommerce_cart_contents, true ) );
     //error_log( print_r(  ' ', true ) );
     //error_log( print_r(  '$woocommerce->cart->cart_contents_total= ' .$woocommerce->cart->cart_contents_total, true ) );
     //error_log( print_r(  '$woo_cart_contents_total_previous= ' .$woo_cart_contents_total_previous, true ) ); 
     //error_log( print_r(  ' ', true ) );
     //error_log( print_r(  '$woocommerce->cart->applied_coupons= ' .$woo_applied_coupons_previous, true ) );
     //error_log( print_r(  '$woo_applied_coupons_previous= ' .$woo_applied_coupons_previous, true ) );
     //error_log( print_r(  ' ', true ) ); 
     //error_log( print_r(  '$woocommerce->cart->cart_contents_count= ' .$woocommerce->cart->cart_contents_count, true ) ); 
     //error_log( print_r(  '$vtprd_cart->cart_contents_count= ' .$vtprd_cart->cart_contents_count, true ) );
     //error_log( print_r(  ' ', true ) );
     //error_log( print_r(  '$previous_user_role= ' .$previous_user_role, true ) );
     //error_log( print_r(  '$previous_cart', true ) ); 
     //error_log( var_export($previous_cart, true ) );  
   
    //-*******************************************************
    //IF nothing changed from last time, no need to process the discount => 
    //'woocommerce_cart_updated' RUNS EVERY TIME THE CART OR CHECKOUT PAGE DISPLAYS!!!!!!!!!!!!!
    //-*******************************************************
      
    //v1.1.7.2 begin  grpB
    // if no cart, and previously no cart, nothing to do!!
    if ( ($woocommerce->cart->cart_contents_total == 0) &&
         ($woo_cart_contents_total_previous == 0) ) {
    //if ( (sizeof($woocommerce_cart_contents) == 0) &&
         //($woocommerce_cart_contents == $previous_cart) ) {   
       //error_log( print_r(  'Function - vtprd_cart_updated return002a', true ) );   
       return;          
    }
    //what's just below handles stuff in the cart, but no change!
    //v1.1.7.2 end
    
    $applied_coupons = $woocommerce->cart->get_coupons(); //v2.0.0 
    
    if ( //removed v1.1.7.2 grpB ($woocommerce->cart->cart_contents_total  > 0) &&   //V1.0.7.1  if == 0, lost addressability to woo, rerun
         (sizeof($data_chain) > 0) &&  //v2.0.0  - if previously processed or not aged off, $data_chain will have a size
         ($woocommerce->cart->cart_contents_total  ==  $woo_cart_contents_total_previous) &&
         ($applied_coupons                         ==  $woo_applied_coupons_previous)  &&   //v2.0.0 
         ($previous_user_role                      ==  vtprd_get_current_user_role() ) )  { //v1.0.7.2  only return if user_role has not changed
       //v1.0.9.3 begin ==>>  see if a zero value item has been removed from the cart...
       if ( (isset($vtprd_cart->cart_contents_count)) &&
            ($vtprd_cart->cart_contents_count == $woocommerce->cart->cart_contents_count) ) {     
         //error_log( print_r(  'Function - vtprd_cart_updated return002b', true ) );   
         return; 
       }
       //v1.0.9.3 end  
    }

     //error_log( print_r(  '$woocommerce->cart->applied_coupons', true ) );
     //error_log( var_export($woocommerce->cart->applied_coupons, true ) );
     //error_log( print_r(  '$woo_applied_coupons_previous', true ) );
     //error_log( var_export($woo_applied_coupons_previous, true ) );

    //v1.1.6.8 begin
    if ( ($woocommerce->cart->cart_contents_total  > 0) && 
         ($applied_coupons  !=  $woo_applied_coupons_previous) ) {    //v2.0.0 
        
     //error_log( print_r(  'coupon_change_detected written', true ) ); 
       
        $_SESSION['coupon_change_detected']  =  true;
      }
    //v1.1.6.8 end

    //$woocommerce_cart_contents = $woocommerce->cart->get_cart();  //v1.1.1.3 moved above 
    if (sizeof($woocommerce_cart_contents) > 0) {   
      $this->vtprd_process_discount();
      
      /* v1.1.1 MOVED HERE, rather than
              add_action('woocommerce_before_mini_cart',            array(&$this, 'vtprd_maybe_before_mini_cart'), 10, 1   );
         For 2 reasons:
          (1) bug in catalog discount in discountCoupon, as of v1.1.1
          (2) If mini_cart not used in Theme, whole thing won't work!!!
      */
      //$this->vtprd_maybe_before_mini_cart(); //v1.1.1

    } else {       
      $this->vtprd_maybe_clear_auto_add_session_vars();
        /* argh4 test
      //v1.1.7.2 begin  grpB
      //Store the data_chain EVEN if no discounts
      // DO NOT USE vtprd_set_transient_data_chain()
        $contents_total   =   0;
        $applied_coupons  =   array();
        $vtprd_cart       =   array();
        $data_chain = array();
        $data_chain[] = $vtprd_rules_set;
        $data_chain[] = $vtprd_cart;
        $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
        $data_chain[] = $contents_total;
        $data_chain[] = $applied_coupons;
        $data_chain[] = time(); //v2.0.0

        //v2.0.2.0 begin
        //$_SESSION['data_chain'] = serialize($data_chain);
        $cart_object = serialize($data_chain);             
        vtprd_set_transient_cart_data ( 'data_chain', $cart_object );
        */
        //v2.0.2.0 end
                      
        //error_log( print_r(  'Store data_chain 004', true ) );                          
      //v1.1.7.2 end
      	
    }
    
    //v1.1.6.8 begin
    //if coupon change, and change in balance REFRESH SCREEN
//    if ( (isset($_SESSION['coupon_change_detected'])) &&
//         ($woocommerce->cart->cart_contents_total  !=  $woo_cart_contents_total_previous) ) {
   //error_log( print_r(  'coupon_change_detected read', true ) );          
      /*
      $contents = $_SESSION['coupon_change_detected'];
      unset( $_SESSION['coupon_change_detected'], $contents );
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
          || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
      header('Location: '.$protocol.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]);
    */
    /*
  wp_safe_redirect( 'http://test.varktech.com/cart/' );    
      exit;
      */
//    }
    //v1.1.6.8 end
    
     //error_log( print_r(  'Function - vtprd_cart_updated return at BOTTOM', true ) );  
      
      
     //*****************
     //v2.0.0 M solution begin - this makes sure that we only run once per wordpress_init!! 
     // but only for Unit Price discounting, as ajax add to cart causes timing issues discounting...
     // so only works on cart and checkout pages...
     //*****************     
      if ( (isset($_SESSION['on_cart_or_checkout_page'])) && 
           ($_SESSION['on_cart_or_checkout_page']) ) {
       remove_action( 'woocommerce_cart_updated', array(&$this, 'vtprd_cart_updated') );   //v2.0.0 G solution - ALL DONE!!
     }
     //v2.0.0 M solution end
     
    return;
   }
    
        
    
	public function vtprd_process_discount(){  //and print discount info...
     //error_log( print_r(  'Function begin - vtprd_process_discount', true ) ); 
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options; //v1.0.9.0   

     //v2.0.0 begin                
     //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
     if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' )) ) { 
          //error_log( print_r(  'vtprd_cart_updated - wp-admin FOUND, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) ); 
        return;          
     } 
     
      //v2.0.0 g solution begin
     //moved session start HERE
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }
      //v2.0.0 g solution end


       //v2.0.2.0 begin
        // only executed for CART processing, so if cart is empty, nothing to do
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();
        
    //error_log( print_r(  'vtprd_process_discount - $woocommerce_cart_contents= ', true ) );
    //error_log( var_export($woocommerce_cart_contents, true ) );
   
        if ( ($woocommerce->cart->cart_contents_total == 0) &&
             ($woocommerce->cart->cart_contents_count == 0) ) {       //this covers things if there is still a FREE item in the cart
          //cleanup and DONE
          //vtprd_del_transient_cart_data_by_cart_id();
          //vtprd_unset_random_unique_cart_id();
      //error_log( print_r(  'vtprd_process_discount - NOTHING DONE ', true ) );      
          return;
        }
       //v2.0.2.0 end

     
     //v2.0.0 end   

    /*
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //In order to prevent recursive executions, test for a TIMESTAMP    
    if (isset($_SESSION['process_discount_timestamp'])) {
      $previous_process_discount_timestamp = $_SESSION['process_discount_timestamp'];
      $current_process_discount_timestamp  = time();
      if ( ($current_time_in_seconds - $previous_process_discount_timestamp) > '1' ) { //session data older than 1 second
        $_SESSION['process_discount_timestamp'] = time();
      } else {
        return;
      }
    } else {
      $_SESSION['process_discount_timestamp'] = time();
    }
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++    
    */
    //calc discounts                
    $vtprd_info['current_processing_request'] = 'cart'; 

    $vtprd_apply_rules = new VTPRD_Apply_Rules;    
 
    //v1.0.9.0  begin
    //load the vtprd cart html fields, for later use - IF we are showing the discount in-line in unit price  

    if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice')  { 
      $catalog_or_inline =  'inline';
    } else {
      $catalog_or_inline =  null;
    }

    vtprd_get_cart_html_prices('process discount',$catalog_or_inline);  //v1.1.1

    //v1.0.9.0  end      
 

    /*  *************************************************
     Load this info into session variables, to begin the 
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order
     access global     - in vtprd_post_purchase_maybe_save_log_info   
    *************************************************   */
   //v2.0.0 g solution begin
   //moved session start above
   /*
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    */
    //v2.0.0 g solution end
    
    //v1.1.0.9 begin  need to clear this out
     //v2.0.0 begin - 
     /*
     if (isset($_SESSION['data_chain'])) {
       $contents = $_SESSION['data_chain'];
       unset( $_SESSION['data_chain'], $contents ); 
     }
     */
     //$_SESSION['data_chain'] = false; //v2.0.2.0
     //v2.0.0 end 
     ///v1.1.0.9 end       
    //v2.0.2.0 begin
    /*
    $contents_total   =   $woocommerce->cart->cart_contents_total;
    $applied_coupons  =   $woocommerce->cart->get_coupons(); //v2.0.0
    $data_chain = array();
    $data_chain[] = $vtprd_rules_set;
    $data_chain[] = $vtprd_cart;
    $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
    $data_chain[] =  $contents_total;
    $data_chain[] =  $applied_coupons;
    $data_chain[] = time(); //v2.0.0
    */
    
    //$_SESSION['data_chain'] = serialize($data_chain);
    //error_log( print_r(  'vtprd_set_transient_data_chain 004', true ) );
    //error_log( print_r(  'vtprd_set_transient_data_chain 004 - $woocommerce_cart_contents= ', true ) );


      // only executed for CART processing, so if cart is empty, nothing to do
      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
      
  //error_log( print_r(  'vtprd_process_discount - $woocommerce_cart_contents= ', true ) );
  //error_log( var_export($woocommerce_cart_contents, true ) );
 
      if ( ($woocommerce->cart->cart_contents_total == 0) &&
           ($woocommerce->cart->cart_contents_count == 0) ) {       //this covers things if there is still a FREE item in the cart
        //cleanup and DONE
        vtprd_del_transient_cart_data_by_cart_id();
        vtprd_unset_random_unique_cart_id();
    //error_log( print_r(  'vtprd_set_transient_data_chain 004- NOTHING DONE ', true ) );      
        return;
      }

    
    
    //error_log( var_export($woocommerce_cart_contents, true ) );    
    //error_log( print_r(  'set 0005 ', true ) );
    vtprd_set_transient_data_chain();
    //v2.0.2.0 end
    
    //error_log( print_r(  'Store data_chain 005', true ) );             
     //error_log( print_r(  'Store data_chain 005 $vtprd_cart', true ) );
     //error_log( var_export($vtprd_cart, true ) ); 
           
    return;        
} 
     
    
	public function vtprd_woo_maybe_add_remove_discount_cart_coupon(){  //and print discount info...  
     //error_log( print_r(  'Function begin - vtprd_woo_maybe_add_remove_discount_cart_coupon - from action woocommerce_before_cart_table', true ) );   

       //v2.0.0 begin L Solution
       //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
       if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
            (defined( 'DOING_CRON' )) ) { 
          return;          
       } 
       //v2.0.0 end L Solution
       
  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }

      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
  		}
  	 //v1.1.1 end
 
      //v2.0.0 BEGIN - 
      //if it's already done, don't run again!!
      if ( (isset ($_SESSION['add_remove_discount_cart_coupon_already_done'])) &&
           ($_SESSION['add_remove_discount_cart_coupon_already_done']) ) {
        $_SESSION['add_remove_discount_cart_coupon_already_done'] = false;
        return;   
  		}
      //v2.0.0 BEGIN -     

    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options; //v1.0.9.1
     
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {  
    	remove_action( 'woocommerce_after_calculate_totals', array(&$this, 'vtprd_woo_maybe_add_remove_discount_cart_coupon')); //v2.0.0 g solution
    	return false; //v1.1.0.1
    }  
    //v1.0.9.1 end  

      
    vtprd_debug_options();  //v1.0.5                 
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();

    //engenders a tr class coupon-deals, used in CSS!

    $coupon_title =  $vtprd_setup_options['coupon_discount_coupon_name']; //v2.0.0.5

    
    if ($vtprd_cart->yousave_cart_total_amt > 0) {  
       //add coupon - recalc totals done when actual coupon amount updated
       if ($woocommerce->cart->has_discount($coupon_title)) {         
          $do_nothing = true;
       } else {
       
       
          $woocommerce->cart->add_discount($coupon_title);
      
          $_SESSION['add_remove_discount_cart_coupon_already_done'] = true; //v2.0.0  
       
         //error_log( print_r(  'CART AFTER new COUPON', true ) );
         //error_log( var_export($woocommerce->cart, true ) );           
  //error_log( print_r(  'add_discount executed', true ) );
  

       }
       
       vtprd_remove_coupon_applied_message();  //v1.1.7.2 grpD

    } else {

       //remove coupon and recalculate totals
       if ($woocommerce->cart->has_discount($coupon_title) ) {
		
      		$this->vtprd_woo_maybe_remove_coupon_from_cart($coupon_title);
        
          //v1.0.9.3 - mark call as internal only - 
          //	accessed in parent-cart-validation/ function vtprd_maybe_before_calculate_totals
          $_SESSION['internal_call_for_calculate_totals'] = true;   
                    
          $woocommerce->cart->calculate_totals();
          
          //v1.1.7.2 grpD begin
          //Remove add coupons success msg if there...  otherwise it may display and confuse the customer => "Coupon code applied successfully"          
          vtprd_remove_coupon_applied_message(); 
          //v1.1.7.2 end
                      
       }
       
    }
          
    return;        
} 


  //clears coupon from cart
   public function vtprd_woo_maybe_remove_coupon_from_cart($coupon_title) {
      //error_log( print_r(  'Function begin - vtprd_woo_maybe_remove_coupon_from_cart', true ) );
 
  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }

      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
  		}
  	  //v1.1.1 end

      global $woocommerce;
			//v1.0.7.5 reworked for backwards compatability
       
      $current_version =  WOOCOMMERCE_VERSION;
      
      //v1.1.7.2 grpD begin
      /*
      No warnings coming out of this function
      remove_coupon works with the TITLE of the coupon!!
      NO CHANGES required
      */
      //v1.1.7.2 end

      //if BEFORE woo version 2.1
      if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower !!!!!!! 
        if ( $woocommerce->applied_coupons ) {
  				foreach ( $woocommerce->applied_coupons as $index => $code ) {
  					if ( $code == $coupon_title ) {
              unset( $woocommerce->applied_coupons[ $index ] );
              break;
            } 
  				}
  			}    
      } else {
        WC()->cart->remove_coupon( $coupon_title );   //v1.0.7.4 
      }      
                         
    return;                
} 


   //****************************************************************
   // V1.1.7 New Function
   // UPDATE coupon amount, if 'coupon discount' selected - WOO 3.0.0 and beyond
   // v2.0.0.5 recoded to use the new $vtprd_setup_options['coupon_discount_coupon_name'] field
   //****************************************************************
	public function vtprd_woo_new_load_discount_amount_to_coupon($coupon) {
  
  //DO NOT RUN IN ADMIN!!!!!!!! (this function is called everywhere)
    //v2.0.0 begin
     if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' )) ) {          
        //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
      return $coupon;          
     }

    //v2.0.0 end 

    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options; //v1.0.9.1
        //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon BEGIN, coupon= ', true ) );
    //error_log( var_export($coupon, true ) );
    
    //v2.0.0 BEGIN  - moved to action statement above
    /*
    //before woo 3.0.0, handled in function vtprd_woo_maybe_load_discount_amount_to_coupon
    if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 - if so handled below
       error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon return 001 ', true ) );
       return $coupon;
    }
    */
    //v2.0.0 end

       
    //v2.0.0 G solution begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon') { 
      remove_action( 'woocommerce_coupon_loaded', array(&$this, 'vtprd_woo_new_load_discount_amount_to_coupon')); 
      //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon return 002, action removed ', true ) ); 		
    	return $coupon;
    }
    
    if ($vtprd_info['yousave_cart_total_amt'] <= 0) { 
      //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon return 003 , yousave_cart_total_amt=0  ', true ) ); 		
    	return $coupon;
    }
        
    $vtprd_cart->yousave_cart_total_amt = $vtprd_info['yousave_cart_total_amt'];
    
      //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon  yousave_cart_total_amt= ' .$vtprd_info['yousave_cart_total_amt'], true ) ); 	  
    
    //v2.0.0 G solution end
    
        
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
         //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon return 003b ', true ) );
      return $coupon;   
		}

    vtprd_debug_options();
        
    //v2.0.0 G solution begin - $data_chain was only necessary for yousave_cart_total_amt
    //  which is now also stored in  $vtprd_info['yousave_cart_total_amt'] in apply-rules.php
    //$data_chain = $this->vtprd_get_data_chain();
    //v2.0.0 G solution end   

/*  
    if ($vtprd_cart->yousave_cart_total_amt == 0) {
        error_log( print_r(  'yousave_cart_total_amt is zero ', true ) );
    }  
    if ($vtprd_rules_set == '') { 
        error_log( print_r(  'vtprd_rules_set is blank ', true ) );    
    }
 */   
     
   
    //error_log( print_r(  "code = " .$coupon->get_code() , true ) );
    //error_log( print_r(  "coupon_discount_coupon_name = " .$vtprd_setup_options['coupon_discount_coupon_name'] , true ) );
    //error_log( print_r(  "amount = " .$coupon->get_amount() , true ) );
    //error_log( print_r(  "vtprd_cart->yousave_cart_total_amt = " .$vtprd_cart->yousave_cart_total_amt , true ) );      
    
    /*  v2.0.0.5 if recoded to allow use of strtolower(xxx)
                  get_code may come back all lower case!!
    if ( ($coupon->get_code() != $vtprd_setup_options['coupon_discount_coupon_name']) ||
         ($coupon->get_amount() == $vtprd_cart->yousave_cart_total_amt) ) {
     */  
    $current_coupon_code = $coupon->get_code();
    if ( ( ($current_coupon_code == $vtprd_setup_options['coupon_discount_coupon_name']) ||
           ($current_coupon_code == strtolower($vtprd_setup_options['coupon_discount_coupon_name'])) )
          and 
         ($coupon->get_amount() < $vtprd_cart->yousave_cart_total_amt) ) {
       $carry_on = true;
    } else{       
         //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon return 004 ', true ) );
       return $coupon;
    }    
    
    $coupon->set_object_read( false );
    
    //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon 002', true ) );
    
		$coupon->set_props( array(
      		'amount'                      => $vtprd_cart->yousave_cart_total_amt
		) );

    
    //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon 003c', true ) );
    

     $coupon->read_meta_data();
    
    //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon 004', true ) );   
    

     $coupon->set_object_read( true ); 
    
    //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon 005', true ) );
     
     vtprd_remove_coupon_applied_message(); //v1.1.7.2    
        
        //error_log( print_r(  'vtprd_woo_new_load_discount_amount_to_coupon END, coupon= ', true ) );
    //error_log( var_export($coupon, true ) );
           
		return $coupon;
	}

      
   //****************************************************************
   // Update the placeholder Coupon previously manually added 
   //  with the discount amount
   //****************************************************************
   public function vtprd_woo_maybe_load_discount_amount_to_coupon($status, $code) {
    //error_log( print_r(  'Function begin - vtprd_woo_maybe_load_discount_amount_to_coupon', true ) );
            
    //v2.0.0 begin
    //DON"T RUN IN ADMIN !!!!!!!!!!!
      if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
           (defined( 'DOING_CRON' )) ) {           
          //error_log( print_r(  'vtprd_woo_maybe_load_discount_amount_to_coupon - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
        return;          
     }    

    //v2.0.0 end
               
		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return;   
		}
	  //v1.1.1 end
 

    global $vtprd_rules_set, $wpdb, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
  
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	remove_filter( 'woocommerce_get_shop_coupon_data',  array(&$this, 'vtprd_woo_maybe_load_discount_amount_to_coupon')); //v2.0.0 g solution      
      return; //v1.1.0.2
    }   
    //v1.0.9.1 end  
            
      
      vtprd_debug_options();  //v1.0.5      
      
      //v2.0.0.5a begin - recoded
      if ( ($code == $vtprd_setup_options['coupon_discount_coupon_name']) ||
           ($code == strtolower($vtprd_setup_options['coupon_discount_coupon_name'])) )  {
        $carry_on = true;     
      } else {
         return;  //v1.1.0.2 
      }
      //v2.0.0.5a end

                 
      //v2.0.0 G solution begin - $data_chain was only necessary for yousave_cart_total_amt
      //  which is now stored in  $vtprd_info['yousave_cart_total_amt']
      //$data_chain = $this->vtprd_get_data_chain();

      $vtprd_cart->yousave_cart_total_amt = $vtprd_info['yousave_cart_total_amt']; 
         
      //v2.0.0 G solution end  
        
      if ($vtprd_cart->yousave_cart_total_amt <= 0) {
         return false;
      }

 
      //v1.0.7.4 begin
      //v1.0.9.3 moved      vtprd_load_cart_total_incl_excl(); 
 
      //$apply_before_tax  used to MIMIC the way regular coupons taxation!!
      //  Testing Note:  Compare how Deal discount is applied vs Regular coupon discount of same amount
      //    example: 10% cart discount vs 10% coupon, with a variety of tax switch settings...
      $apply_before_tax = vtprd_coupon_apply_before_tax();    

//      $apply_before_tax = '';      
      //v1.0.7.4 end

      //GET coupon_id of the previously inserted placeholder coupon where title = $vtprd_info['coupon_code_discount_deal_title']
      $deal_discount_title = $vtprd_setup_options['coupon_discount_coupon_name'];
      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" . $deal_discount_title. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );     	

         
      //defaults take from  class/class-wc-coupon.php    function __construct
      
      //v1.0.9.3 redone begin
      
      $current_version =  WOOCOMMERCE_VERSION;
      //AFTER Woo 2.3, coupon is always applied PRE_TAX i
      if( (version_compare(strval('2.3.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
        //pre woo 2.3
        vtprd_load_cart_total_incl_excl(); 
        $coupon_data = array(
              'id'                         => $coupon_id,
              'type'                       => 'fixed_cart',   //type = discount_type
              'amount'                     => $vtprd_cart->yousave_cart_total_amt,
              'individual_use'             => 'no',
              'product_ids'                => array(),
              'exclude_product_ids'        => array(),
              'usage_limit'                => '',
              'usage_count'                => '',
              'expiry_date'                => '',
              'apply_before_tax'           => $apply_before_tax,
              'free_shipping'              => 'no',
              'product_categories'         => array(),
              'exclude_product_categories' => array(),
              'exclude_sale_items'         => 'no',
              'minimum_amount'             => '',
              'customer_email'             => ''
        );      
      } else {
               
        if ( (get_option( 'woocommerce_calc_taxes' )  == 'yes' ) && 
             (get_option('woocommerce_prices_include_tax')  == 'yes') ) { 
          //$amount = $vtprd_cart->yousave_cart_total_amt;
          $amount = $vtprd_cart->yousave_cart_total_amt_incl_tax;
        }  else  {
          $amount = $vtprd_cart->yousave_cart_total_amt_excl_tax;
        }        
            
        $coupon_data = array(
              	'id'                         => $coupon_id,
                'discount_type'              => 'fixed_cart',
              	'coupon_amount'              => $amount, //always use untaxed, as it's added in WOO, if there...
              	'individual_use'             => 'no',
              	'product_ids'                => array(),
              	'exclude_product_ids'        => array(),
              	'usage_limit'                => '',
              	'usage_limit_per_user'       => '',
              	'limit_usage_to_x_items'     => '',
              	'usage_count'                => '',
              	'expiry_date'                => '',
              	'free_shipping'              => 'no',
              	'product_categories'         => array(),
              	'exclude_product_categories' => array(),
              	'exclude_sale_items'         => 'no',
              	'minimum_amount'             => '',
              	'maximum_amount'             => '',
              	'customer_email'             => array()
              ); 
      }     

    //error_log( print_r(  'Function end - vtprd_woo_maybe_load_discount_amount_to_coupon', true ) );
      
     vtprd_remove_coupon_applied_message();  //v1.1.7.2 grpD
                 
     return $coupon_data;
   }


  //**************************************************
  //  Maybe print discount, always update the coupon info for post-payment processing
  //**************************************************
	public function vtprd_maybe_print_checkout_discount(){  //and print discount info...
     //error_log( print_r(  'Function begin - vtprd_maybe_print_checkout_discount', true ) );
  
  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }

      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
  		}
  	  //v1.1.1 end
    
     global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons;                 
     vtprd_debug_options();  //v1.0.5    
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();

    //v1.1.1.3  REMOVED in favor of accessing the log in vtprd_save_discount_purchase_log to see if it's already done.
    //set one-time switch for use in function vtprd_post_purchase_maybe_save_log_info
    //$_SESSION['do_log_function'] = true;  //v1.1.1.3 
          
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    //**************************************************
    //Add discount totals into coupon_totals (a positive #) for payment gateway processing and checkout totals processing
    //  $wpsc_cart->coupons_amount has ALREADY been re-computed in apply-rules.php at add to cart time
    //**************************************************    

    //v1.1.0.6 begin
    if ( ($vtprd_cart->yousave_cart_total_amt > 0) || 
         ($vtprd_cart->cart_has_zero_price_auto_add_free_item == 'yes') ) {
    //v1.1.0.6 end    
    //    vtprd_print_checkout_discount();
        $msgType = 'plainText';                         //v1.0.8.0
        vtprd_checkout_cart_reporting($msgType);        //v1.0.8.0
    } 
         
    return;        
} 


  //**************************************************
  //  Maybe print Widget discount
  //**************************************************
	public function vtprd_maybe_print_widget_discount(){  //and print discount info...
     //error_log( print_r(  'Function begin - vtprd_maybe_print_widget_discount', true ) );
  
		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return;   
		}
	  //v1.1.1 end


    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $wpsc_coupons, $vtprd_setup_options;
    vtprd_debug_options();  //v1.0.5        

       //error_log( print_r(  '$vtprd_cart at maybe_print_widget begin', true ) );
       //error_log( var_export($vtprd_cart, true ) );

    //v1.0.9.3 begin
    //  NO widget print for inline pricing
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {  
      remove_action( 'woocommerce_widget_shopping_cart_before_buttons', array(&$this, 'vtprd_maybe_print_widget_discount')); //v2.0.0 g solution       		
    	return;
    }
    
    
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();
    
    //****************
    //v1.1.1.2 begin -  1st discountCoupon mini cart display can loose its data chain on auto add - check and rerun discount
    /*
    if ( ( ($vtprd_cart == null) ||
           (!isset ($vtprd_cart->cart_items)) )  &&
        ($vtprd_info['ruleset_contains_auto_add_free_product'] == 'yes')  )  {
    */
    if ( ($vtprd_cart == null) ||
         (!isset ($vtprd_cart->cart_items)) || 
         (sizeof($vtprd_cart->cart_items) == 0) ) {        
      //re-run the apply_discount
      $woocommerce_cart_contents = $woocommerce->cart->get_cart();  
      if (sizeof($woocommerce_cart_contents) > 0) {   
        $this->vtprd_process_discount();  
      }  
    }
    //v1.1.1.2 end
    //****************
  
    //v1.1.1.3  REMOVED in favor of accessing the log in vtprd_save_discount_purchase_log to see if it's already done.
    //set one-time switch for use in function vtprd_post_purchase_maybe_save_log_info
    //$_SESSION['do_log_function'] = true;  //v1.1.1.3
          
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    //**************************************************
    //Add discount totals into coupon_totals (a positive #) for payment gateway processing and checkout totals processing
    //  $wpsc_cart->coupons_amount has ALREADY been re-computed in apply-rules.php at add to cart time
    //**************************************************    

       //error_log( print_r(  '$vtprd_cart at maybe_print_widget AFTER DATa-chain get', true ) );
       //error_log( var_export($vtprd_cart, true ) );
      
      
    if ($vtprd_cart->yousave_cart_total_amt > 0) {
    //   vtprd_enqueue_front_end_css();   
        vtprd_print_widget_discount();
    } 
        
    return;        
} 


  /* ************************************************
  **   After purchase is completed, store lifetime purchase and discount log info
  *
  * This function is executed multiple times, only complete on 1st time through    
  * //				do_action( 'woocommerce_checkout_order_processed', $order_id, $this->posted );     
  *************************************************** */ 
  public function vtprd_post_purchase_maybe_save_log_info($log_id, $posted_info) {   //$log_id comes in as an argument from wpsc call...
     //error_log( print_r(  'Function begin - vtprd_post_purchase_maybe_save_log_info', true ) );

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      //error_log( print_r(  'vtprd_post_purchase_maybe_save_log_info - return 001' , true ) );
      return;   
		}
	  //v1.1.1 end

    global $woocommerce, $vtprd_setup_options, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule;
    vtprd_debug_options();  //v1.0.5           
    //while the global data is available here, it does not stay 'current' between iterations, and we loos the 'already_done' switch, so we need the data chain.
         
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();

    /*
    //v1.1.1.3  REMOVED in favor of accessing the log in vtprd_save_discount_purchase_log to see if it's already done.
    //only do this once - set in function vtprd_maybe_print_checkout_discount    
    if (!$_SESSION['do_log_function']) {   
        return;
    }
    $_SESSION['do_log_function'] = false;
    */
    
    
    //*****************
    //Save LIfetime data
    //*****************
    //v1.0.7.3 begin
    /*
    //moved to thankyou function
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  { 
      vtprd_save_lifetime_purchase_info($log_id);
    }
    */
    //v1.0.7.3 end
    
    //Save Discount Purchase Log info
    //************************************************
    //*   Purchase log is essential to customer email reporting
    //*      so it MUST be saved at all times.
    //************************************************
    vtprd_save_discount_purchase_log($log_id); 
    
    //v2.0.2.0 begin
    //clean out once the purchase is complete and purchase_log created
    //error_log( print_r(  'vtprd_post_purchase_maybe_save_log_info - above execution of vtprd_del_transient_cart_data_by_cart_id' , true ) );
    vtprd_del_transient_cart_data_by_cart_id();  
    //v2.0.2.0 end  
   
    //error_log( print_r(  'vtprd_post_purchase_maybe_save_log_info - return 002' , true ) );
    return;
  } // end  function vtprd_store_max_purchaser_info()     


   
  /* ************************************************
  USING THIS filter in this way, puts discounts within the existing products table, after products are shown, but before the close of the table...
  *************************************************** */ 
 public function vtprd_post_purchase_maybe_email($message, $order_info) { 
    //error_log( print_r(  'Function begin - vtprd_post_purchase_maybe_email', true ) );  

		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return $message;   
		}
	  //v1.1.1 end


    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 
     
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  { 
        /* v1.1.8.3 added comment
        //to TURN ON this action, add the 'add_filter...' statement to your theme/child-theme functions.php file  
        add_filter( 'vtprd_always_show_email_discount_table', function() { return TRUE; } );  
        */	      		
      //v1.1.7.2 Begin - allow override to DISPLAY the discount table on ALL emails
      if (apply_filters('vtprd_always_show_email_discount_table',FALSE)) {  
        $show_table = true;
      } else {
        return $message;
      } 
      //v1.1.7.2 end  
    }
    //v1.0.9.1 end   
    
     vtprd_debug_options();  //v1.0.5   


    //v1.1.7.2 begin   grpA
    if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
      $log_Id = $order_info->get_id();
    } else { 
      $log_Id = $order_info->id;
    }
    //v1.1.7.2 end 
   
   
    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_Id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if ($vtprd_purchase_log) { 
      $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
      $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
    }                                                                                                                          

    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
      return $message;    
    } 

      //get the Discount detail report...
    if (strpos($message, '\n\n')) {   //if '\n\n' is in the #message, it's not html!!  =>  see last line, templates/emails/plain/email-order-items.php
      $discount_reporting = vtprd_email_cart_reporting('plain'); 
    } else {
      $discount_reporting = vtprd_email_cart_reporting('html');     
    }

    $message .=  $discount_reporting;

    return $message;
  }    


   
  /* ************************************************
  v1.1.8.0  New Function
  VIEW CART RULE order history report
  *************************************************** */ 
 public function vtprd_order_history_report($log_Id) { 
    //error_log( print_r(  'Function begin - vtprd_order_history_report', true ) );  

    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 
     
    //optional shutoff
    if (apply_filters('vtprd_do_not_show_order_history_report',FALSE)) {  
      return;
    } 
    
    vtprd_debug_options();  //v1.0.5   

    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_Id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if (!$vtprd_purchase_log) {     
      return;
    }  
                                                                                                                            
    $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
    $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
      
    //NO discount found, no msg changes
    if (!($vtprd_cart->yousave_cart_total_amt > 0)) {  
      return;    
    } 

      //get the Discount detail report...
    //v1.1.8.0 email2 is still in development...
    //$discount_reporting = vtprd_email2_cart_reporting('html');  
    $discount_reporting = vtprd_email_cart_reporting('html');   

     echo $discount_reporting;
    
    return;
  }  
    
  /* ************************************************
  //  do_action( 'woocommerce_order_details_after_order_table', $order );
  *************************************************** */ 
  public function vtprd_post_purchase_maybe_before_thankyou($order_id) { 
     //error_log( print_r(  'Function begin - vtprd_post_purchase_maybe_before_thankyou', true ) ); 
     
     //test test test
     //error_log( print_r(  '$order = ', true ) );        
     //error_log( var_export($order_id, true ) );     
     
    
		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return;   
		}
	  //v1.1.1 end

    global $wpdb, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options; 
     
    //v1.1.0.3 moved below
    /*
    //v1.0.9.1 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {   		
    	return;
    }
    //v1.0.9.1 end      
    */  
     vtprd_debug_options();  //v1.0.5
    
    $message = '';  //v1.0.8.0
    $log_id = $order_id;
   
    //if there's a discount history, let's find it...
    $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $log_id . "' LIMIT 1", ARRAY_A );      	
    	    
    //if purchase log, use that info instead of current 
    if ($vtprd_purchase_log) { 
      $vtprd_cart      = unserialize($vtprd_purchase_log['cart_object']);    
      $vtprd_rules_set = unserialize($vtprd_purchase_log['ruleset_object']);
    }  else {
      return;
    }                                                                                                                        

    //v1.1.0.6 begin
    //if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
    if ( ($vtprd_cart->yousave_cart_total_amt > 0) || 
         ($vtprd_cart->cart_has_zero_price_auto_add_free_item == 'yes') ) {
      $carry_on = true;
    } else {
      return;    
    } 
    
    //*****************
    //Save LIfetime data
    //*****************
    //v1.0.7.3 begin
    //  moved HERE so that abandoned carts are avoided in lifetime info
    
    //test test test
    //global $vtprd_rules_set;
    //error_log( print_r(  'RULESET Just Before vtprd_save_lifetime_purchase_info, $log_id= ' .$log_id, true ) );
    //error_log( var_export($vtprd_rules_set, true ) );  
    
    if ( (defined('VTPRD_PRO_DIRNAME')) && 
         ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') &&
         ($vtprd_cart->lifetime_limit_applies_to_cart == 'yes') )  {       //v2.0.2.0 added
      vtprd_save_lifetime_purchase_info($log_id);
    }
    //v1.0.7.3 end

 //error_log( print_r(  'vtprd_post_purchase_maybe_before_thankyou - 005 ', true ) );

    //v1.1.0.3 begin
    if ($vtprd_setup_options['discount_taken_where'] != 'discountCoupon')  {  
    

 //error_log( print_r(  'vtprd_post_purchase_maybe_before_thankyou - 006 ', true ) );  
        
        //v1.1.1.3 begin
        // ALL DONE
        //Clear out everything Salient  (so that LIfetime discount limits are cleared...)
        //these resets allow the NEXT add to cart to launch vtprd_process_discount OUT OF vtprd_maybe_before_calculate_totals
        
         //v2.0.0 begin - 
         /*
         if (isset($_SESSION['data_chain'])) {
           $contents = $_SESSION['data_chain'];
           unset( $_SESSION['data_chain'], $contents ); 
         }
         */
         
         //v2.0.2.0 begin
         //$_SESSION['data_chain'] = false; 
         vtprd_del_transient_cart_data_by_data_type ('data_chain');
         //v2.0.2.0 end  
             
         //v2.0.0 end  
         //clean out temp storage on RULESET!
         global $vtprd_rules_set;
         $vtprd_rules_set = get_option( 'vtprd_rules_set' ); 
         
         $vtprd_cart = null;   
         //error_log( print_r(  'CLEAN OUT AT END COMPLETED 1' , true ) );
            
        //v1.1.1.3 end   
        
 //error_log( print_r(  'vtprd_post_purchase_maybe_before_thankyou - 007 ', true ) );    
    
     		
    	return;
    }
    //v1.1.0.3end      
   

 //error_log( print_r(  'vtprd_post_purchase_maybe_before_thankyou - 008 ', true ) );
    
    //get the Discount detail report...
    $discount_reporting = vtprd_thankyou_cart_reporting(); 

    //overwrite $message old message parts, new info as well...
//    $message  =  '<br>';
    
    $message .=  $discount_reporting;
//    $message .=  '<br>';

    echo  $message;
    
   
    //v1.1.1.3 begin
    // ALL DONE
    //Clear out everything Salient  (so that LIfetime discount limits are cleared...)
    //these resets allow the NEXT add to cart to launch vtprd_process_discount OUT OF vtprd_maybe_before_calculate_totals

     //v2.0.0 begin - 
     /*
     if (isset($_SESSION['data_chain'])) {
       $contents = $_SESSION['data_chain'];
       unset( $_SESSION['data_chain'], $contents ); 
     }
     */
         
     //v2.0.2.0 begin
     //$_SESSION['data_chain'] = false; 
     vtprd_del_transient_cart_data_by_data_type ('data_chain');
     //v2.0.2.0 end  
              
     //v2.0.0 end  
     //clean out temp storage on RULESET!
     global $vtprd_rules_set;
     $vtprd_rules_set = get_option( 'vtprd_rules_set' ); 
     
     $vtprd_cart = null;   
     //error_log( print_r(  'CLEAN OUT AT END COMPLETED 2' , true ) );
        
    //v1.1.1.3 end
    
    
 
    return;  
  }

 


/* ************************************************
  **   After purchase is completed, => create the html transaction results report <=
  *       ONLY at transaction time...
  *********************************************** */     
 public function vtprd_post_purchase_maybe_purchase_log($message, $notification) { 
    //error_log( print_r(  'Function begin - vtprd_post_purchase_maybe_purchase_log', true ) );   
    global $woocommerce, $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info;    
    vtprd_debug_options();  //v1.0.5             
    //Open Session Variable, get rules_set and cart if not there....
    $data_chain = $this->vtprd_get_data_chain();
   
    /*  *************************************************
     At this point the global variable contents are gone. 
     session variables are destroyed in parent plugin before post-update processing...
     load the globals with the session variable contents, so that the data will be 
     available in the globals during post-update processing!!!
      
     DATA CHAIN - global to session back to global
     global to session - in vtprd_process_discount
     session to global - in vtprd_woo_validate_order  +
                            vtprd_post_purchase_maybe_purchase_log
     access global     - in vtprd_post_purchase_maybe_save_log_info    
    *************************************************   */

    if(!isset($_SESSION['data_chain'])){
      return $message;    
    }

    
    //NO discount found, no msg changes

    //v1.1.0.6 begin
    //if (!($vtprd_cart->yousave_cart_total_amt > 0)) {
    if ( ($vtprd_cart->yousave_cart_total_amt > 0) || 
         ($vtprd_cart->cart_has_zero_price_auto_add_free_item == 'yes') ) {
      $carry_on = true;
    } else {
    //v1.1.0.6 end    
        
      $this->vtprd_nuke_session_variables();
      return $message;    
    } 
    
    //check if the discount reporting has already been applied, by looking for the header
    //  as this function may be called Twice
    $needle = '<th>' . __('Discount Quantity', 'vtprd') .'</th>';
    if (strpos($message, $needle)) {   //if $needle already in the #message
      $this->vtprd_nuke_session_variables();
      return $message;
    }
    
  
    $msgType = 'html';

    //get the Discount detail report...
    $discount_reporting = vtprd_email_cart_reporting($msgType); 
    
    //just concatenate in the discount DETAIL info into $message and return
    
    //split the message up into pieces.  We're going to insert all the Discount Reporting
    //  just before "Total Shipping:"
    $totShip_literal = __( 'Total Shipping:', 'wpsc' ); 
    $message_pieces  = explode($totShip_literal, $message); //this removes the delimiter string...
    
    //overwrite $message old message parts, new info as well...
    $message  =  $message_pieces[0]; //1st piece before the delimiter "Total Shipping:"
    $message .=  $discount_reporting;
    
    //skip a line    
    if ($msgType == 'html') {
      $message .= '<br>';
    } else {
      $message .= "\r\n";
    }
    
    //put the delimeter string BACK
    $message .=  $totShip_literal; 
    $message .=  $message_pieces[1]; //2nd piece after the delimiter "Total Shipping:"

    $this->vtprd_nuke_session_variables();
    return $message;
  } 
 
   
  /* ************************************************
  **   Post-transaction cleanup - Nuke the session variables 
  *************************************************** */ 
 public  function vtprd_nuke_session_variables() {
    
     //v2.0.0 begin - 
     /*
     if (isset($_SESSION['data_chain'])) {
       $contents = $_SESSION['data_chain'];
       unset( $_SESSION['data_chain'], $contents ); 
     }
     */
     
     //v2.0.2.0 begin
     //$_SESSION['data_chain'] = false; 
     //CLEAN OUT THE TRANSIENT TABLE FOR THIS CART!!
     vtprd_del_transient_cart_data_by_cart_id ();
     //v2.0.2.0 end  
             
     //v2.0.0 end 
    
    /*  v2.0.2.0 - these session vars were not actually used
    if (isset($_SESSION['previous_free_product_array']))  {    
      $contents = $_SESSION['previous_free_product_array'];
      unset( $_SESSION['previous_free_product_array'], $contents );
    }

    if (isset($_SESSION['current_free_product_array']))  {         
      $contents = $_SESSION['current_free_product_array'];
      unset( $_SESSION['current_free_product_array'], $contents ); 
    }
    */
    
    return;   
 }
   
  /* ************************************************
  **   Application - get current page url
  *       
  *       The code checking for 'www.' is included since
  *       some server configurations do not respond with the
  *       actual info, as to whether 'www.' is part of the 
  *       URL.  The additional code balances out the currURL,
  *       relative to the Parent Plugin's recorded URLs           
  *************************************************** */ 
 public  function vtprd_currPageURL() {
     global $vtprd_info;
     $currPageURL = $this->vtprd_get_currPageURL();
     $www = 'www.';
     
     $curr_has_www = 'no';
     if (strpos($currPageURL, $www )) {
         $curr_has_www = 'yes';
     }
     
     //use checkout URL as an example of all setup URLs
     $checkout_has_www = 'no';
     if (strpos($vtprd_info['woo_checkout_url'], $www )) {
         $checkout_has_www = 'yes';
     }     
         
     switch( true ) {
        case ( ($curr_has_www == 'yes') && ($checkout_has_www == 'yes') ):
        case ( ($curr_has_www == 'no')  && ($checkout_has_www == 'no') ): 
            //all good, no action necessary
          break;
        case ( ($curr_has_www == 'no') && ($checkout_has_www == 'yes') ):
            //reconstruct the URL with 'www.' included.
            $currPageURL = $this->vtprd_get_currPageURL($www); 
          break;
        case ( ($curr_has_www == 'yes') && ($checkout_has_www == 'no') ): 
            //all of the woo URLs have no 'www.', and curr has it, so remove the string 
            $currPageURL = str_replace($www, "", $currPageURL);
          break;
     } 
 
     return $currPageURL;
  } 
 public  function vtprd_get_currPageURL($www = null) {
     global $vtprd_info;
     $pageURL = 'http';
     //if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
     if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) { $pageURL .= "s";}
     $pageURL .= "://";
     $pageURL .= $www;   //mostly null, only active rarely, 2nd time through - see above
     
     //NEVER create the URL with the port name!!!!!!!!!!!!!!
     $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     /* 
     if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
     } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     }
     */
     return $pageURL;
  }  
    
  
  /* ************************************************
  **   Application - On Error Display Message on E-Commerce Checkout Screen  
  *************************************************** */ 
  public function vtprd_display_rule_error_msg_at_checkout(){
     //error_log( print_r(  'Function begin - vtprd_display_rule_error_msg_at_checkout', true ) );
    global $vtprd_info, $vtprd_cart, $vtprd_setup_options;
    
    vtprd_debug_options();  //v1.1 
    
    //error messages are inserted just above the checkout products, and above the checkout form
     ?>     
        <script type="text/javascript">
        jQuery(document).ready(function($) {
    <?php 
    //loop through all of the error messages 
    //          $vtprd_info['line_cnt'] is used when table formattted msgs come through.  Otherwise produces an inactive css id. 
    for($i=0; $i < sizeof($vtprd_cart->error_messages); $i++) { 
      ?>
       <?php  if ( $vtprd_setup_options['show_error_before_checkout_products_selector'] > ' ' )  {  ?> 
          $('<div class="vtprd-error"><p> <?php echo $vtprd_cart->error_messages[$i] ?> </p></div>').insertBefore('<?php echo $vtprd_setup_options['show_error_before_checkout_products_selector'] ?>') ;
       <?php  }  ?>
       <?php  if ( $vtprd_setup_options['show_error_before_checkout_address_selector'] > ' ' )  {  ?>  
          $('<div class="vtprd-error"><p> <?php echo $vtprd_cart->error_messages[$i] ?> </p></div>').insertBefore('<?php echo $vtprd_setup_options['show_error_before_checkout_address_selector'] ?>') ;
       <?php  }  ?>
      <?php 
    }  //end 'for' loop      
    ?>   
            });   
          </script>
     <?php    


     /* ***********************************
        CUSTOM ERROR MSG CSS AT CHECKOUT
        *********************************** */
     if ($vtprd_setup_options[custom_error_msg_css_at_checkout] > ' ' )  {
        echo '<style type="text/css">';
        echo $vtprd_setup_options[custom_error_msg_css_at_checkout];
        echo '</style>';
     }
     
     /*
      Turn off the messages processed switch.  As this function is only executed out
      of wp_head, the switch is only cleared when the next screenful is sent.
     */
     $vtprd_cart->error_messages_processed = 'no';
       
 } 

   //Ajax-only
   public function vtprd_ajax_empty_cart() {
      //error_log( print_r(  'Function begin - vtprd_ajax_empty_cart', true ) );
     //clears ALL the session variables, also clears out coupons
     $this->vtprd_maybe_clear_auto_add_session_vars();
     
     //Ajax needs exit
     exit;
   }


   //supply woo with ersatz pricing deals discount type
   public function vtprd_woo_add_pricing_deal_discount_type($coupon_types_array) {
      //error_log( print_r(  'Function begin - vtprd_woo_add_pricing_deal_discount_type', true ) );
      $coupon_types_array['pricing_deal_discount']	=  __( 'Pricing Deal Discount', 'woocommerce' );
     return $coupon_types_array;
   }


   public function vtprd_get_data_chain() {
      //error_log( print_r(  'Function begin - vtprd_get_data_chain', true ) );      
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }   
      /*  *************************************************
       At this point the global variable contents are gone. 
       session variables are destroyed in parent plugin before post-update processing...
       load the globals with the session variable contents, so that the data will be 
       available in the globals during post-update processing!!!
        
       DATA CHAIN - global to session back to global
       global to session - in vtprd_process_discount
       session to global - in vtprd_woo_validate_order  +
                              vtprd_post_purchase_maybe_purchase_log
       access global     - in vtprd_post_purchase_maybe_save_log_info    
      *************************************************   */
      global $vtprd_rules_set, $vtprd_cart, $vtprd_info, $vtprd_setup_options; //v2.0.0  added $vtprd_info, $vtprd_setup_options
      
      //v2.0.2.0 begin       
      /*
      //v2.0.0 begin - reworked 
      // new structure added so that the session variable can be set to false.
      $data_chain_found = false;
      if (isset($_SESSION['data_chain'])) {
            //error_log( print_r(  'vtprd_get_data_chain - data chain set', true ) );      
        if ($_SESSION['data_chain']) {
            //error_log( print_r(  'vtprd_get_data_chain - data chain found', true ) );        
          $data_chain_found = true;
        }
      }
      */
       
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0014 ', true ) );
      $data_chain = vtprd_get_transient_cart_data ('data_chain');

      if ($data_chain) {
        $data_chain      = unserialize($data_chain);
      
       //v2.0.2.0 begin
      /*      
      //test test test
      if ($data_chain) {
        $data_chain_found = true;
      */ 
       
       //v2.0.2.0 end 
        
            //error_log( print_r(  'vtprd_get_data_chain - unserialize successful', true ) );        
      } else {
            //error_log( print_r(  'vtprd_get_data_chain - No chain found, return blank array', true ) );   
        $data_chain = array();
        $vtprd_info['product_session_info'] = array();  //force all new catalog rule tests //v2.0.0
        return $data_chain; 
      }
      //v2.0.0 end
      
      //v2.0.0 begin - process $data_chain[] = time(); 
      if ( isset ($data_chain[5]) ) {
        $data_chain_timestamp = $data_chain[5];
        $current_time_in_seconds = time();
        if ( ($current_time_in_seconds - $data_chain_timestamp) > '300' ) {    //session data older than 5 minutes
          $data_chain = array();
          $vtprd_info['product_session_info'] = array();  //force all new catalog rule tests //v2.0.0        
          return $data_chain;
        }
      } else {
        $data_chain = array();
        $vtprd_info['product_session_info'] = array();  //force all new catalog rule tests //v2.0.0      
        return $data_chain;
      }
      //v2.0.0 end  
            
          
      if ($vtprd_rules_set == '') {        
        if (isset($data_chain[0])) {    //v1.0.8.0
          $vtprd_rules_set = $data_chain[0];
        }
        if (isset($data_chain[1])) {    //v1.0.8.3
          $vtprd_cart      = $data_chain[1];
        }
      }

      return $data_chain;
   }

/*
   //supply woo with ersatz pricing deals coupon data on demand
   public function vtprd_woo_add_pricing_deal_coupon_data($status, $code) {
      if ($code != 'pricing_deal_discount') {
         return false;
      } 
         
      //defaults take from  class/class-wc-coupon.php    function __construct
      $coupon_data = array(
            'id'                         => '',
            'type'                       => 'pricing_deal_discount',   //type = discount_type
            'amount'                     => 0,
            'individual_use'             => 'no',
            'product_ids'                => '',
            'exclude_product_ids'        => '',
            'usage_limit'                => '',
            'usage_count'                => '',
            'expiry_date'                => '',
            'apply_before_tax'           => 'yes',
            'free_shipping'              => 'no',
            'product_categories'         => array(),
            'exclude_product_categories' => array(),
            'exclude_sale_items'         => 'no',
            'minimum_amount'             => '',
            'customer_email'             => array()
      );            

   
     return $coupon_data;
   }
*/ //v1.0.4 fix (missing close comment...)
   
 //Clean Up Session Variables which would otherwise persist during Discount Processing       
  public function vtprd_maybe_clear_auto_add_session_vars() {
     //error_log( print_r(  'Function begin - vtprd_maybe_clear_auto_add_session_vars', true ) );
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    
    //v2.0.2.0 begin 
    /*
    if (isset($_SESSION['previous_auto_add_array']))  {
        $contents = $_SESSION['previous_auto_add_array'];
        unset( $_SESSION['previous_auto_add_array'], $contents );
        //v1.1.0.6 begin
        global $vtprd_info;
        $vtprd_info['previous_auto_add_array'] = ''; //$vtprd_info['previous_auto_add_array'] used when session variable disappears due to age
        //v1.1.0.6 end    
    }
    if (isset($_SESSION['current_auto_add_array']))  {
        $contents = $_SESSION['current_auto_add_array'];
        unset( $_SESSION['current_auto_add_array'], $contents );    
    }
    */
    
    if (isset($_SESSION['vtprd_unique_cart_id'])) {
      $cart_id = $_SESSION['vtprd_unique_cart_id'];
      vtprd_del_transient_cart_data_by_data_type ('previous_auto_add_array', $cart_id);
      vtprd_del_transient_cart_data_by_data_type ('current_auto_add_array', $cart_id);
      global $vtprd_info;
      $vtprd_info['previous_auto_add_array'] = ''; //$vtprd_info['previous_auto_add_array'] used when session variable disappears due to age
    }
    
    //v2.0.2.0 end
      
          
     //v2.0.0 begin - 
     /*
     if (isset($_SESSION['data_chain'])) {
       $contents = $_SESSION['data_chain'];
       unset( $_SESSION['data_chain'], $contents ); 
     }
     */
     //v2.0.2.0 begin
     //$_SESSION['data_chain'] = false;
     
     vtprd_del_transient_cart_data_by_data_type ( 'data_chain' ) ;
     
     //v2.0.2.0 end
     //v2.0.0 end    
    
    vtprd_debug_options();  //v1.1
    
    
    //v1.1.7.2  grpB  begin
    //re-coded
    global  $woocommerce, $vtprd_info, $vtprd_setup_options;

    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') {
          
      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
      
      //v2.0.0.5 begin
      $coupon_title = $vtprd_setup_options['coupon_discount_coupon_name'];
      if ( (sizeof($woocommerce_cart_contents) > 0 ) &&
           ($woocommerce->cart->has_discount($coupon_title)) ) {    		 
        $this->vtprd_woo_maybe_remove_coupon_from_cart($coupon_title);
      }      
      //v2.0.0.5 end
    }
    //v1.1.7.2  grpB  end
       
    return;    
  }
   
   //v1.0.7.2 begin    New function, to pick up a zero total produced by catalog discounts...
   //  really only needed if ALL products have a catalog discount which ends up with ALL products FREE ...
   public function vtprd_maybe_recalc_woo_totals() {
      //error_log( print_r(  'Function begin - vtprd_maybe_recalc_woo_totals', true ) );

  		//v1.1.1 begin - 
      // "do_no_actions" set/unset in function  vtprd_build_product_price_array
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }

      if ( (isset ($_SESSION['do_no_actions'])) &&
           ($_SESSION['do_no_actions']) ) {
        return;   
  		}
  	 //v1.1.1 end

     global $woocommerce;

     vtprd_debug_options();  //v1.1
        
     //v1.0.9.3 - mark call as internal only - 
     //	accessed in parent-cart-validation/ function vtprd_maybe_before_calculate_totals
     $_SESSION['internal_call_for_calculate_totals'] = true;   
      
               
     $woocommerce->cart->calculate_totals();        
     return;
   }
   //v1.0.7.2 end
  
   
 /*
    also:  in wpsc-includes/purchase-log-class.php  (from 3.9)
		do_action( 'wpsc_sales_log_process_bulk_action', $current_action );
  */
	public function vtprd_pro_lifetime_log_roll_out($log_id ){ 
     //error_log( print_r(  'Function begin - vtprd_pro_lifetime_log_roll_out', true ) ); 
 
		//v1.1.1 begin - 
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return;   
		}
 	 //v1.1.1 end

    //v2.0.0 begin
       //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
    if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) &&
         (!defined( 'DOING_CRON' )) &&
         (defined('VTPRD_PRO_DIRNAME')) ) {     
       vtprd_debug_options();  //v1.1
       vtprd_maybe_lifetime_roll_log_totals_out($log_id);
    }
    //v2.0.0 end
    
    return;   
  }

 /*
    also:  in wpsc-includes/purchase-log-class.php  (from 3.9)
 		do_action( 'wpsc_purchase_log_before_delete', $log_id ); 
  */
	public function vtprd_pro_lifetime_bulk_log_roll_out($current_action){  
     //error_log( print_r(  'Function begin - vtprd_pro_lifetime_bulk_log_roll_out', true ) );

       //v2.0.0 begin
       //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
    if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) &&
         (!defined( 'DOING_CRON' )) &&
         (defined('VTPRD_PRO_DIRNAME')) ) {
       vtprd_debug_options();  //v1.1
       vtprd_maybe_lifetime_bulk_roll_log_totals_out($current_action);
    }

       //v2.0.0 end    
       
    
    return;   
  }

  //********************************************************
  // v1.0.9.0  New function - do various housekeeping stuff
  //********************************************************
	public function vtprd_do_loaded_housekeeping(){
    //error_log( print_r(  'Function begin - vtprd_do_loaded_housekeeping', true ) );  

		//v1.1.1 begin - 

     //v2.0.0 begin
     //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
     if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' )) ) {  
          //error_log( print_r(  'vtprd_do_loaded_housekeeping - Admin or Cron FOUND, DO NOT RUN. REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
        return;          
     } 
     //v2.0.0 end
    
    // "do_no_actions" set/unset in function  vtprd_build_product_price_array
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['do_no_actions'])) &&
         ($_SESSION['do_no_actions']) ) {
      return;   
	}
    //v1.1.1 end

	global $woocommerce, $current_user, $vtprd_setup_options, $vtprd_info;    
     vtprd_debug_options();  //v1.1



     
    //********************
    //v2.0.2.0 begin
    // check for auto adds in existing cart right after login, OR auto adds for restored abandoned cart
    // switch set in vtprd_update_on_login_change() .  
    // This process mimics the way WOO handles things
    
    // WOO WILL ALREADY HAVE MERGED ANY EXISTING 'NOT LOGGED IN' CART WITH ANY SAVED CART FOUND
    // WE NEED TO ROLL OUT ANY AUTO ADDS GRANTED TO THE RESTORED SAVED CART!!!!!!
    if ( is_user_logged_in() ) {
        $_SESSION['vtprd_customer_id'] = $current_user->ID;
    }
   
    
    if ( (is_user_logged_in())  &&
         (defined('VTPRD_PRO_DIRNAME')) &&    //whole shebang only applies to PRO
         (isset($_SESSION['at_login_check_saved_cart_in_housekeeping'])) &&
         (is_object($woocommerce)) )  {          
      
       //clear the trigger registered at login
        $contents = $_SESSION['at_login_check_saved_cart_in_housekeeping'];
        unset( $_SESSION['at_login_check_saved_cart_in_housekeeping'], $contents );   
             
       //find any *existing* OR *abandoned* cart info
       
       $cart_id = vtprd_get_transient_cart_id_by_customer_id ( $current_user->ID, 'customer_id_for_cart_id' );
       if ($cart_id) {
       
          //is there matching saved auto adds?
           /*
           //first save off any existing cart_id  
           if ( isset($_SESSION['vtprd_unique_cart_id']) ) { 
             $vtprd_hold_unique_cart_id = $_SESSION['vtprd_unique_cart_id'];
           } else {
             $vtprd_hold_unique_cart_id = false;      
           }
           */
          
           //set unique_id = found cart id from above
           $_SESSION['vtprd_unique_cart_id'] = $cart_id;

           //There could be an auto_add in the CURRENT cart OR an abandoned cart under this user ID
           //is there an auto add? "woo_cart_contents_with_auto_add" only exists if previous cart had an auto add
           $woo_cart_contents_with_auto_add       =  vtprd_get_transient_cart_data('woo_cart_contents_with_auto_add');
           $woo_cart_contents_from_saved_session  =  vtprd_get_transient_cart_data('woo_cart_contents_from_saved_session');
           
           if ($woo_cart_contents_with_auto_add) {
                          
             /* IF woo has a saved cart session
             and that cart matches my saved auto add cart
             we're on the SAME cart as where the auto add took place
             */
             if ($woo_cart_contents_from_saved_session) {
                if  ($woo_cart_contents_from_saved_session == $woo_cart_contents_with_auto_add) {
                  $we_are_good = true;  
                } else {
                  /*
                  if ($vtprd_hold_unique_cart_id) {
                     $_SESSION['vtprd_unique_cart_id'] = $vtprd_hold_unique_cart_id;
                  }
                  */
                  //delete_user_meta( $current_user->ID, '_vtprd_check_saved_cart_after_login' );
                  return;
                }          
              }
              
              
              global $vtprd_info;

              //v1.1.8.0 begin
              // Function vtprd_get_previous_auto_add_array() only exists in PRO, 
              // and if PRO is not active due to upgrade, this gives a FATAL
              //v1.1.8.0 end
              
              //------------------------------------     
              //$vtprd_apply_rules = new VTPRD_Apply_Rules;   moved below 
              //$previous_auto_add_array = $vtprd_apply_rules->vtprd_get_previous_auto_add_array();        
              //error_log( print_r(  'before vtprd_get_transient_cart_data previous_auto_add_array 0013 ', true ) );
              $get_previous_auto_add_array = vtprd_get_transient_cart_data ('previous_auto_add_array');       
              //------------------------------------ 
      
              //******************************
              //prevents recursive processing during auto add execution of add_to_cart!
              //v1.1.0.6 placed at top of routine
              //******************************
              //  otherwise there would be an endless loop via both add_to_cart and set_quantity  ...
              $_SESSION['auto_add_in_progress'] = 'yes';
              //add_in_progress switch will be overriden in 10 seconds using the timestamp (also shut off at bottom of this routine)
              $_SESSION['auto_add_in_progress_timestamp'] = time();
              //******************************
      
              //only roll out previous stuff, if NO current stuff to add
              //if (sizeof($previous_auto_add_array) > 0) {  //v2.0.2.0
              if ($get_previous_auto_add_array)  {   
                 $previous_auto_add_array = unserialize($get_previous_auto_add_array);  
                 vtprd_maybe_roll_out_prev_auto_insert_from_woo_cart($previous_auto_add_array, 'all');    //v2.0.2.0
               
                 //v2.0.2.0 begin
                 //$this->vtprd_maybe_remove_previous_auto_add_array();
                 vtprd_del_transient_cart_data_by_data_type ('previous_auto_add_array', $cart_id);
                 
                 //error_log( print_r(  'woo_cart_contents_with_auto_add DEL 0005 ', true ) );
                 
                 vtprd_del_transient_cart_data_by_data_type ('woo_cart_contents_with_auto_add', $cart_id);
                 
                 //error_log( print_r(  'vtprd_cart_updated 002 ', true ) );
                 
                 //delete_user_meta( $current_user->ID, '_vtprd_check_saved_cart_after_login' );
                 
                 $this->vtprd_cart_updated(); 
                 
                 return;
                 //v2.0.2.0 end
              } 
      

         } //end  if($woo_cart_contents_with_auto_add)
         
       } //end if ($cart_id)
       
       //test test test
       //vtprd_set_transient_cart_data ( 'customer_id_for_cart_id', 'key to key lookup' );  // UPDATES INFO TO CURRENT - track relationship between CURRENT Unique_id AND customer_id

       //delete_user_meta( $current_user->ID, '_vtprd_check_saved_cart_after_login' );

       if (is_object($woocommerce))  {       
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();
        if ( sizeof($woocommerce_cart_contents) > 0 ) {       
           //this re-does the CART rules
           //error_log( print_r(  'vtprd_cart_updated 001 ', true ) );
           $this->vtprd_cart_updated(); 
                
        }
      }

    } //end _vtprd_check_saved_cart_after_login
    //v2.0.2.0 end                 
    //********************
    
    
    
    
    //********************
    //v1.1.0.7 begin  
    if ( ($vtprd_setup_options['wholesale_products_display'] == '' ) ||
         ($vtprd_setup_options['wholesale_products_display'] == 'noAction') ) {
      $skip_this = true;  
    } else {
      if(defined('VTPRD_PRO_DIRNAME')) {
        add_filter( 'woocommerce_product_is_visible',    array( &$this, 'vtprd_maybe_woocommerce_product_is_visible' ),  10, 2 );   //v1.1.1.3 changed function name to _product
        //in includes/class-wc-product-variation.php
        //return apply_filters( 'woocommerce_variation_is_visible', $visible, $this->variation_id, $this->id, $this );
        add_filter( 'woocommerce_variation_is_visible',  array( &$this, 'vtprd_maybe_woocommerce_variation_is_visible' ),  10, 4 ); //v1.1.1.3          
      }  
    }
    //v1.1.0.7 end
    //********************

    //********************
    //v1.1.1 begin  
  
    if ( ($vtprd_setup_options['wholesale_products_price_display'] == '' ) ||
         ($vtprd_setup_options['wholesale_products_price_display'] == 'noAction') ) {
      $skip_this = true;  
    } else {
      if(defined('VTPRD_PRO_DIRNAME')) {
        add_filter( 'woocommerce_is_purchasable',  array( &$this, 'vtprd_maybe_woocommerce_is_purchasable' ),  10, 2 ); 
        //in includes/class-wc-product-variation.php
        //return apply_filters( 'woocommerce_variation_is_purchasable', $purchasable, $this );
        add_filter( 'woocommerce_variation_is_purchasable',  array( &$this, 'vtprd_maybe_woocommerce_is_purchasable' ),  10, 2 );  //v1.1.1.3         
      }  
    }
    //v1.1.1 end
    //********************

    //------------
    //v1.1.1 begin
    //*****************************************************
    // PLUGIN:: woocommerce-measurement-price-calculator
    // PLUGIN:: woocommerce-product-addons
    //*****************************************************
    //ONLY way to get the price to the faux variations for CATALOG rules...  ADDS a lot of processing time
    //ONLY DO GET_PRICE FOR THese PLUGINs!!!!
    
    //************************************************************************************************************************************************
    //ACTIVATED externally by the client, or internally for the Addons or Calculator plugins
    //the apply_filter is done twice - once in parent-cart-validation in housekeeping, and once in parent-functions in vtprd_get_current_active_price
    //************************************************************************************************************************************************
        
    $do_get_price = apply_filters('vtprd_do_compatability_pricing',false);
    
    /* sample execution for CLIENT
      
      // *** add to bottom of Theme Functions file
      //allows Pricing Deals to pick up current product pricing from other plugins
      //  ---  more resource intensive  ---
     
    add_filter('vtprd_do_compatability_pricing', 'do_compatability_pricing', 10, 1); 
    function do_compatability_pricing ($return_status) {
      return true;
    }
    */  

    
    if ( (class_exists('WC_Measurement_Price_Calculator')) ||
         (class_exists('WC_Product_Addons')) ||
         ($do_get_price) ) { 
      //if ( !is_admin() ) {  v2.0.0 removed - duplicative!!  
          $filter_added = false;
          //only needed for CATALOG rules
          if ( (class_exists('WC_Product_Addons')) &&
               (get_option('vtprd_ruleset_has_a_display_rule') == 'yes') ) {    
    
    /*v1.1.7  WOO 3.0
    SEE woocommerce/includes/abstracts/abstract-wc-data.php
    - **********************************
    - at data access, calls the hook creator..
    - **********************************     
    protected function get_prop( $prop, $context = 'view' ) {
  		$value = null;
  
  		if ( array_key_exists( $prop, $this->data ) ) {
  			$value = isset( $this->changes[ $prop ] ) ? $this->changes[ $prop ] : $this->data[ $prop ];
  
  			if ( 'view' === $context ) {
  				$value = apply_filters( $this->get_hook_prefix() . $prop, $value, $this );
  			}
  		}
  		return $value;
  	}
    
    SEE woocommerce/includes/class-wc-product-variation.php
    - **********************************
    - creates hooks on the fly...
    - ********************************** 
    	protected function get_hook_prefix() {
    		return 'woocommerce_product_variation_get_';
    	}
    */
                    
                //v1.1.7 begin
                if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
                  add_filter('woocommerce_product_get_price',           array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                  add_filter('woocommerce_product_variation_get_price', array(&$this, 'vtprd_maybe_get_price'), 10, 2); 
                } else {
                  add_filter('woocommerce_get_price',                   array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                }                
                //v1.1.7 end
                $filter_added = true;            
          }
          //needed for ALL rules - but this logic prevents double add_filter...
          if ( (class_exists('WC_Measurement_Price_Calculator')) &&
               (!$filter_added) ) {    
                //v1.1.7 begin
                if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
                  add_filter('woocommerce_product_get_price',           array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                  add_filter('woocommerce_product_variation_get_price', array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                } else {
                  add_filter('woocommerce_get_price',                   array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                }                
                //v1.1.7 end                $filter_added = true;               
          } 
          //THIS IS TO ALLOW compatability with other plugins that need to use get_price for single_product and Cart pricing
          // (all other filters are SHUT OFF during this call, and the get_price call to Pricing Deals is just returned....)
          if ( ($do_get_price) &&
               (!$filter_added) ) {    
                //v1.1.7 begin
                if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
                  add_filter('woocommerce_product_get_price',           array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                  add_filter('woocommerce_product_variation_get_price', array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                } else {
                  add_filter('woocommerce_get_price',                   array(&$this, 'vtprd_maybe_get_price'), 10, 2);
                }                
                //v1.1.7 end                //NOT NEEDED??????????????????
                //add_filter('vtprd_get_price_always_reflects_back_input', 'vtprd_get_price_always_reflects_back_input', 10, 1);
                
                $filter_added = true;                
          } 
          /*
          //CURRENTLY INACTIVE (for now Bolt-ons only allow Coupon Discounting)
          if (class_exists('WC_Measurement_Price_Calculator')) {    
                add_filter('woocommerce_cart_subtotal',               array(&$this, 'vtprd_maybe_cart_subtotal'), 10, 3);  //only way to get the price to the faux variations...               
          }
          */                     

    }       
   
    //v1.1.1 end
    //----------

     
    if ( (!is_object($woocommerce->customer) ) ||
         (empty( $woocommerce->customer) )     ||
         ( ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) && ( $woocommerce->customer->get_is_vat_exempt() ) ) ||  //v1.1.7.2 
         ( ( version_compare( WC_VERSION, '3.0.0', '<'  ) ) && ( $woocommerce->customer->is_vat_exempt() ) ) //v1.1.7.2 
         //v1.1.7.2 ($woocommerce->customer->is_vat_exempt() ) ) { 
       ) 
    {   
      return; 
    }
    
    // check user-level tax exemption (plugin-specific checkbox on user screen)
    //USER LEVEL TAX EXEMPTION = ALL TRANSACTIONS TAX EXEMPT
    if (get_user_meta( $current_user->ID, 'vtprd_user_is_tax_exempt', true ) == 'yes') {
       //v1.1.7.2 begin
       if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 
         $woocommerce->customer->is_vat_exempt = true;
       } else {                     
         $woocommerce->customer->set_is_vat_exempt(true);
       }              
       //v1.1.7.2 $woocommerce->customer->is_vat_exempt = true;
       //v1.1.7.2 end
       return;
    }

    if ( !$current_user )  {
      $current_user = wp_get_current_user();
    }
    
    //check role-level tax exemption (plugin-specific role capability)
    if ( current_user_can( 'buy_tax_free') ) {
       //v1.1.7.2 begin
       if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { //check if older than version 3.0.0 
         $woocommerce->customer->is_vat_exempt = true;
       } else {                     
         $woocommerce->customer->set_is_vat_exempt(true);
       }              
       //v1.1.7.2 $woocommerce->customer->is_vat_exempt = true;
       //v1.1.7.2 end
    }    
    
    return;   
  }

} //end class
$vtprd_parent_cart_validation = new VTPRD_Parent_Cart_Validation;
