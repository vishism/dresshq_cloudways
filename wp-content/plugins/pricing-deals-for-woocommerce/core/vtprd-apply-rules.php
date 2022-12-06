<?php

//EDITED * + * +  * + * +  * + * +  * + * + 
       /*
       *******************************
       *  v1.1.0.6 CHANGES       
       *******************************
       
       *******
       PRE_Process
       *******
       *       
       Logic paths ==>>
       autoadds are now added REALTIME as needed, rather than in advance  
       
       always delete old autoadds in pre-process
       
       Internal:
          free product same as purchased product
       External:
          free product based on purchases of different product(s)
       Mixed:
          free product based on BOTH purchases of same and/or different product(s)
          
       **************   
       Deleting previous auto inserts:
        if previous auto-added product current qty = 0,
          all done!
        if previous auto-added product current qty >= previous total,
          roll out previous auto-added product qty
          if nothing left
            remove auto-added product from vtprd-cart
          all done!
        if previous auto-added product current qty < previous total
          If previous auto-added product had **any purchases**
            roll back to that previous purchase total
          else
            roll to zero
            remove auto-added product from vtprd-cart
        
        Log current purchased qty, if any, in current session variable
        
        Delete previous session variables
          
        
        ** $vtprd_setup_options['bogo_auto_add_the_same_product_type'] ** no longer needed, I think    
       
        
       *******
       PROCESS
       *******
       * 
       at top of function vtprd_process_actionPop_and_discount    
        - is rule an auto add
        - if so, check to see how many of auto add product should be added in this iteration
        - add auto add qty to:
          vtprd-cart
          action array (end of, or add to qty)
          action exploded array
            - if product not in array, add at end
            - if product in array, add in the middle of the array, just after last occurrence of product
        - ADD auto-add qty to Current Session Variable
        - check 'sizeof' statements to make sure they're local, not global 
        
        - Process as Normal!!          
       
       
       *******
       POST_Process
       *******
       * 
       Cleanup =>
        
       Add/Modify?delete in Woo Cart, 
        based on inserted qty
       
       
       - At end, make set Previous session variable = current
       - Delete current session variable
       
        *******************************
       *  v1.1.0.6 end CHANGES       
       *******************************     
 */       
class VTPRD_Apply_Rules{
	
	public function __construct(){
		global $woocommerce, $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule;

      //error_log( print_r(  ' ', true ) );       
      //error_log( print_r(  'TOP OF CLASS VTPRD_Apply_Rules ', true ) );   

    
     //v2.0.0 begin                
     //no cart rules in admin (only catalog)!!
     //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
     if ( ($vtprd_info['current_processing_request'] == 'cart') 
            &&
         ((strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' ))) ) {           
          //error_log( print_r(  'VTPRD_Apply_Rules - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
        return;          
     }     
     //v2.0.0 end 
        
    //*********************
    //v1.1.5 begin
    global $vtprd_license_options;    
    if (!$vtprd_license_options) {
      $vtprd_license_options = get_option( 'vtprd_license_options' );
    }

    $vtprd_info['yousave_cart_total_amt'] = 0; //v2.0.0 G solution  - used to load the discount coupon
    
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
      error_log( print_r(  '$vtprd_license_options at APPLY-RULES BEGIN', true ) );
      error_log( var_export($vtprd_license_options, true ) ); 
      if ($vtprd_info['current_processing_request'] == 'cart') { //v2.0.0
        global $woocommerce;
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();      
        $wooSize = sizeof($woocommerce_cart_contents);
        //error_log( print_r(  '$woocommerce->cart SIZE at APPLY-RULES BEGIN = ' .$wooSize, true ) );        
        //error_log( print_r(  '$woocommerce->cart at APPLY-RULES BEGIN', true ) );        
        //error_log( var_export($woocommerce->cart, true ) );
      } 
    }  
    
    if (defined('VTPRD_PRO_VERSION')) {  //v2.0.0.1 added this test.
      if ( ($vtprd_license_options['status'] == 'valid') &&  
           ($vtprd_license_options['pro_plugin_version_status'] == 'valid')  )  {  
        $all_good = true;
      } else {
       //v2.0.0 begin 
       if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
          error_log( print_r(  'VTPRD_Apply_Rules INVALID LICENSE FOUND, EXIT', true ) );
       } 
       //v2.0.0 end    
        return;
      }
    } //v2.0.0.1 added this test.
    
    
    //v1.1.5 end 
    //*********************  
    
    //GET RULES SET     
    $vtprd_rules_set = get_option( 'vtprd_rules_set' );  //v2.0.0  - 'vtprd_rules_set' load **001**

    if ($vtprd_rules_set == FALSE) {  
       //v2.0.0 begin 
       if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
          error_log( print_r(  'VTPRD_Apply_Rules NO RULES FOUND, EXIT', true ) );
       } 
       //v2.0.0 end
              
      return;
    }
    
    //v2.0.1.0 begin
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ) { 
      error_log( print_r(  ' ', true ) );       
      error_log( print_r(  'ABOVE Cart/catalog test ', true ) );    
      error_log( print_r(  ' ', true ) );  
      error_log( print_r(  '$vtprd_rules_set at APPLY-RULES BEGIN', true ) );
      error_log( var_export($vtprd_rules_set, true ) );
      error_log( print_r(  '$vtprd_info at APPLY-RULES BEGIN', true ) );
      error_log( var_export($vtprd_info, true ) ); 
    }
    //v2.0.1.0 end

    //v2.0.1.0 begin
    // after getting rules set, update rule status for those rules which don't meet customer-level criteria
    $sizeof_rules_set = sizeof($vtprd_rules_set);         
    for($i=0; $i < $sizeof_rules_set; $i++) { 
    
      //skip **existing** invalid rules
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
        continue;  //skip out of this for loop iteration
      }
      
      $this->vtprd_manage_shared_rule_tests($i);

    } 
    //v2.0.1.0 end     
      
    if ($vtprd_info['current_processing_request'] == 'cart') {
      
        //CART RULES ****
  
         //v1.1.7.2 grpD begin
        //clear the variable
        if(!isset($_SESSION)){
            session_start();
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
        }      
        //$_SESSION['coupon_activated_discount'] = false;  //v2.0.0 e solution - moved below  
        //v1.1.7.2 end
        
        
             
       //v1.0.9.4  moved here to cover 
       //  when JUST a catalog discount was processed, CART still needs loading               
       //Move parent cart contents to vtprd_cart 
        vtprd_load_vtprd_cart_for_processing(); 
  
  
        //v2.0.0 begin
        //GET RULES SET  again, in case they were stepped on in cart load, when processing the Catalog rules   
        $vtprd_rules_set = get_option( 'vtprd_rules_set' );  //v2.0.0 - CART rules 'vtprd_rules_set' load **002**
  
  
        //v2.0.1.0 begin
        // after getting rules set, update rule status for those rules which don't meet customer-level criteria
        $sizeof_rules_set = sizeof($vtprd_rules_set);         
        for($i=0; $i < $sizeof_rules_set; $i++) { 
        
          //skip **existing** invalid rules
          if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
            continue;  //skip out of this for loop iteration
          }
          
          $this->vtprd_manage_shared_rule_tests($i);
  
       } 
       //v2.0.1.0 end 
  
        //in some situations, the vtprd_cart will not load correctly first time through the apply process.  It will be correct in the 2nd...
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();
        $wooSize = sizeof($woocommerce_cart_contents);
        $vtprdSize = sizeof($vtprd_cart->cart_items);
  
        if ($wooSize != $vtprdSize) {           
          if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
             error_log( print_r(  ' ' , true ) );
             error_log( print_r(  'APPLY-RULES BEGIN CART SIZE Mismatch error, end of apply-rules processing', true ) );
             error_log( print_r(  '$woocommerce->cart SIZE at APPLY-RULES BEGIN = ' .$wooSize, true ) );
             error_log( print_r(  '$vtprd_cart cart_items SIZE at APPLY-RULES BEGIN  = ' .$vtprdSize, true ) );
          }
          return;        
        } 
        //V2.0.0 End
        
        //*****************************************
        //v2.0.0 begin - Reworked.  Moved here  So - auto add for free - works - below...
        // ALSO, removed the WC()->cart->get_coupons(); in favor of WC()->cart->has_discount('code') after 3.0;
        //  WC()->cart->get_coupons(); sometimes doesn't work during AJAX
        
        //only needed for older versions
        if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) { 
          $coupon_array = array();
          $applied_coupons = WC()->cart->get_coupons();
          //build coupon array
          
          foreach ( $applied_coupons as $code => $coupon ) {
            $coupon_array[]  = strtolower($code);        
          } 
         }  
        
        //error_log( print_r(  '$coupon_array', true ) );
        //error_log( var_export($coupon_array, true ) ); 
        
        $sizeof_rules_set = sizeof($vtprd_rules_set);         
        for($i=0; $i < $sizeof_rules_set; $i++) { 
                                                                        
          //only activate if coupon presented
          //in wp-admin, new coupon should be created as a 'cart discount' with a 'coupon amount'.  (just used to activate the rule)
          if ($vtprd_rules_set[$i]->only_for_this_coupon_name > ' ') {
            $only_for_this_coupon_name = strtolower($vtprd_rules_set[$i]->only_for_this_coupon_name); //v1.1.7.1a  compare both in lower case
            if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) { 
              if  (!WC()->cart->has_discount($only_for_this_coupon_name)) {  
                $vtprd_rules_set[$i]->rule_status = 'noCouponFound';
               //error_log( var_export('noCouponFound set', true ) );         
              }          
            } else {
              if (!in_array($only_for_this_coupon_name, $coupon_array)) { 
                $vtprd_rules_set[$i]->rule_status = 'noCouponFound';
               //error_log( var_export('noCouponFound set', true ) );         
              }
            }
          }  
        }               
        //v2.0.0 End 
        //************   
  
        //creates an exploded Product ID rule, can only work in PRO
        if (defined('VTPRD_PRO_VERSION')) {
          $this->vtprd_explode_applies_to_each_rules();
        }
        //v1.1.7.1a end
        //*********************
        
        
        //sort for "cart" rules and delete "display" rules
        $this->vtprd_sort_rules_set_for_cart();
  
              
        //after sort for cart/remove display rows, are there rows left?
        if ( sizeof($vtprd_rules_set) == 0) {
          if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ) {   
            error_log( print_r(  'APPLY-RULES BEGIN at APPLY-RULES BEGIN, NO valid CART RULES found ', true ) ); //v2.0.0
          }      
          
          //*********************************************************
          //v2.0.0 begin  E Solution  
          //if previous run had a coupon-activated auto-add for frree discount, 
          // but the coupon has now been REMOVED (invalidating the rule)
          // run pre-process to see if the discount should be rolled out of the woo cart!
          //if ( (isset($_SESSION['previous_auto_add_array'])) &&    //v2.0.2.0
          //error_log( print_r(  'before vtprd_get_transient_cart_data  0015 ', true ) );
          if ( (vtprd_get_transient_cart_data ('previous_auto_add_array')) &&   //v2.0.2.0
               (isset($_SESSION['coupon_activated_discount'])) &&
               ($_SESSION['coupon_activated_discount']) ) {
            $this->vtprd_pre_process_cart_for_autoAdds();
            $this->vtprd_post_process_cart_for_autoAdds();    
          }      
          //v2.0.0 end 
          //*********************************************************
           
          return;
        }
        
        $_SESSION['coupon_activated_discount'] = false; //v2.0.0 moved here to allow the above test.
      
        //**********************
        /*  At top of routine to set a coupon discount baseline as relevant
          (b) if we're on the checkout page, and a coupon has been added/removed
          (c) if an auto-add is in the cart (which should really be skipped), it doesn't matter, it'll get picked up and corrected in the  maybe_update_parent_cart_for_autoAdds function
          (d) new coupon behavior:  With an auto add, "apply with coupons" is required
              and the Coupon will ALWAYS be skipped instead of the rule.  this is accomplished by re-running the vtprd_maybe_compute_coupon_discount function again
              (i) after the previous auto adds have been rolled out and
              (ii) before any new auto adds are rolled in 
        */
        //v1.0.9.4 added if
        //v1.1.0.9  IF removed ==>> prevented the 'other coupoon = no' from working
        /*
        if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  { 
          vtprd_count_other_coupons();
        }
        */
        vtprd_count_other_coupons();
        //**********************
                 
       //v1.0.9.4  moved above
       //Move parent cart contents to vtprd_cart 
       // vtprd_load_vtprd_cart_for_processing(); 
  
  
        //autoAdd into internal arrays, as needed 
        //EDITED * + * +  * + * +  * + * +  * + * +      
  
     
        $this->vtprd_process_cart(); 
     
        
        //Update the parent cart for any auto add free products...
        //EDITED * + * +  * + * +  * + * +  * + * + 
         
        $vtprd_info['yousave_cart_total_amt'] = $vtprd_cart->yousave_cart_total_amt;  //v2.0.0 G solution  - used to load the discount coupon      

    } else {
    //CATALOG RULES ****
    
       //v2.0.0 begin
       if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
          error_log( print_r('VTPRD_Apply_Rules Current processing request = CATALOG', true ) );
       }
       //v2.0.0 end      
 
          
      //sort for "display" rules and delete "cart" rules
      $this->vtprd_sort_rules_set_for_display();        
      //after sort for display/remove cart rows, are there rows left?
      if ( sizeof($vtprd_rules_set) == 0) {
        //v2.0.0 begin
        if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   //v1.1.5
          error_log( print_r(  'APPLY-RULES BEGIN at APPLY-RULES BEGIN, NO valid CATALOG RULES found ', true ) );
        } 
        //v2.0.0 end      
        return;
      } 
            
      // **********************************************************  
      //  This path is for display rules only, where a SINGLE product
      //     has been loaded into the cart to test for a Display discount
      // **********************************************************       
      $this->vtprd_process_cart();                
    }  

    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
      error_log( print_r(  '$woocommerce->cart at APPLY-RULES END', true ) );
      error_log( var_export($woocommerce->cart, true ) );
      error_log( print_r(  '$vtprd_info at APPLY-RULES END', true ) );
      error_log( var_export($vtprd_info, true ) );
      session_start();    //mwntest
      error_log( print_r(  '$_SESSION at APPLY-RULES END', true ) );
      error_log( var_export($_SESSION, true ) );
      error_log( print_r(  '$vtprd_rules_set at APPLY-RULES END', true ) );
      error_log( var_export($vtprd_rules_set, true ) );
      error_log( print_r(  '$vtprd_cart at APPLY-RULES END', true ) );
      error_log( var_export($vtprd_cart, true ) );
      error_log( print_r(  '$vtprd_setup_options at APPLY-RULES END', true ) );
      error_log( var_export($vtprd_setup_options, true ) );
      //$message = ' Backtrace: ' . wp_debug_backtrace_summary(); //v1.1.7.2 grpb
      //error_log( var_export($message, true ) ); //v1.1.7.2 grpb        
    }


       //v1.1.7.2 grpD begin
       //catch single rule execution
       if ( ($vtprd_info['current_processing_request'] == 'cart') && 
            (isset($vtprd_setup_options['limit_cart_discounts'])) ) {            
           switch( $vtprd_setup_options['limit_cart_discounts'] ) {
            //ALSO break this out of here, put after rule execution, in case there's only 1 rule
            case 'allow_no_standalone_woo_coupon_discounts_if_any_cart_pricing_deal_discount_granted':  //5
                if ($vtprd_cart->yousave_cart_total_amt > 0) { 
                   vtprd_remove_any_other_woo_coupons();  
                } 
               break; 
            case 'allow_no_standalone_woo_coupon_discounts_if_coupon_actuated_pricing_deal_granted':  //6 
                if ($_SESSION['coupon_activated_discount']) { //coupon_activated_discount = PREVIOUS rule was coupon activated and discounted
                   vtprd_remove_any_other_woo_coupons();  
                } 
               break;              
           }
       }
       //v1.1.7.2 end                                                       

 
    return;      
	}
 

  public function vtprd_process_cart() { 
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;	
    //error_log( print_r(  'vtprd_process_cart ', true ) ); 

     //v2.0.0.9 removed
    //cart may be empty...
    /*
    if (sizeof($vtprd_cart) == 0) {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'No Products in the Cart.';
      return;
    }
    */
    
    // v1.1.7.2 Added filter override
    // to turn on:  add_filter( 'vtprd_allow_multiple_coupons_in_coupon_mode', function() { return TRUE; } );
    //v1.0.7.4 begin
    if ( apply_filters('vtprd_allow_multiple_coupons_in_coupon_mode',TRUE) ) {  //v1.1.8.1 CHANGED to TRUE to allow by default
      $allow_multiples = true;
    } else {
      //test to prevent multiples
      if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  { //v1.0.9.4
        if ( ($vtprd_info['current_processing_request'] == 'cart') &&
             ($vtprd_info['skip_cart_processing_due_to_coupon_individual_use']) )  {
          $vtprd_cart->cart_level_status = 'rejected';
          $vtprd_cart->cart_level_auditTrail_msg = 'Another Coupon with Individual_use = "yes" has been activated.  Cart processing may not continue.';          
          return;
        }
      }
    }
    //v1.0.7.4 end

    
      //v2.0.0 begin - reload vtprd_cart if still has the catalog discount loads!
      if ($vtprd_info['current_processing_request'] == 'cart') {
        global $woocommerce;
        $woocommerce_cart_contents = $woocommerce->cart->get_cart();
        $wooSize = sizeof($woocommerce_cart_contents);
        $vtprdSize = sizeof($vtprd_cart->cart_items);     
        
        //this will be true if a Catalog rule was applied during cart processing
        //if ($wooSize != $vtprdSize) {
        if ($vtprdSize < $wooSize)  { //can be =, which is standard, and if vtprd is greater, there's an auto-add in progress        
          if ($vtprd_setup_options['debugging_mode_on'] == 'yes'){ 
             error_log( print_r(  ' ' , true ) );
             error_log( print_r(  'APPLY-RULES BEGIN CART SIZE Mismatch error, rebuild vtprd_cart', true ) );
             error_log( print_r(  '$woocommerce->cart SIZE at APPLY-RULES BEGIN = ' .$wooSize, true ) );
             error_log( print_r(  '$vtprd_cart cart_items SIZE at APPLY-RULES BEGIN  = ' .$vtprdSize, true ) ); //v2.0.0 
          }
          
          //RELOAD vtprd_cart in this situation - it'll be because Catalog rules were just processed...
          vtprd_load_vtprd_cart_for_processing(); 
          
        }        
      }       
      //v2.0.0 End

        
    //test all rules for inPop and actionPop participation 
    $vtprd_cart->at_least_one_rule_actionPop_product_found = 'no';
    //
    $this->vtprd_test_cart_for_rules_populations();
    //        

   if ($vtprd_cart->at_least_one_rule_actionPop_product_found != 'yes') {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'No actionPop Products found.  Processing ended.';     
      return;
   } 
    
    /* if price or template code request (display), there's only one product in the cart for the call
       if either of these conditions exist:
          no display rules found
          or product does not participate in a display rule
            product_in_rule_allowing_display will be 'no'      
    */
    if ( ($vtprd_info['current_processing_request'] == 'display') &&
         ($vtprd_cart->cart_items[0]->product_in_rule_allowing_display == 'no') )  {
      $vtprd_cart->cart_level_status = 'rejected';
      $vtprd_cart->cart_level_auditTrail_msg = 'A single product "Display" request sent, product not in any Display rule.  Processing ended.';          
      return;
    }

    //v1.0.9.3 begin
    if ($vtprd_info['current_processing_request'] == 'cart') {
      $vtprd_cart->cart_contents_orig_subtotal = vtprd_get_Woo_cartSubtotal(); 
    }
    //v1.0.9.3 end

    //test all rules whether in and out counts satisfied    
    $this->vtprd_process_cart_for_rules_discounts();


    return;
 }   

  //************************************************
  //Load inpop found list and actionopop found list
  //************************************************
  public function vtprd_test_cart_for_rules_populations() { 
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;
    
    //error_log( print_r(  'vtprd_test_cart_for_rules_populations ', true ) );     
     
    //************************************************
    //BEGIN processing to mark product as participating in the rule or not...
    //************************************************
    
    /*  Analyze each rule, and load up any cart products found into the relevant rule
        fill rule array with product cart data :: load inPop info 
    */  

    //************************************************
    //FIRST PASS:
    //    - does the product participate in either inPop or actionPop 
    //************************************************
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) {                                                               

  //error_log( print_r(  '$sizeof_rules_set= ' .$sizeof_rules_set . '(2)= ' . sizeof($vtprd_rules_set). ' $i= ' .$i, true ) );

      //v2.0.0 test for $vtprd_rules_set[$i]->only_for_this_coupon_name moved above

      //pick up existing invalid rules
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
        continue;  //skip out of this for loop iteration
      } 

      /* v2.0.1.0 moved around to initial load of vtprd_rules_set
      $this->vtprd_manage_shared_rule_tests($i);           

            // test whether the product participates in either inPop or actionPop
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
          continue;  //skip out of this for loop iteration
      } 
      */

      
      
      //****************************************************
      // ONLY FOR AUTO ADD - overwrite actionPop and discountAppliesWhere
      //******************
      //  - timing of this overwrite is different for auto adds...
      //  - NON auto adds are done below
      //**************************************************** 
      //EDITED * + * +  * + * +  * + * +  * + * +

       
      //Cart Main Processing
      
      //v2.0.0 begin - 'each' tabulation, general efficiency across cart
      $inPop_prod_cats_found_across_cart_array   = array();
      $inPop_plugin_cats_found_across_cart_array = array();
      $inPop_products_found_across_cart_array    = array();
      $inPop_products_found_across_cart       = FALSE;
      $actionPop_products_found_across_cart   = FALSE;
      //v2.0.0 end
                  
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {                 
        //only do this check if the product is on special!!
        if ($vtprd_cart->cart_items[$k]->product_is_on_special == 'yes')  { 
          $do_continue = '';  //v1.0.4 set = to ''
          switch( $vtprd_rules_set[$i]->cumulativeSalePricing) {
            case 'no':              
                //product already on sale, can't apply further discount
                //v1.1.0.4 
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
                $product_name = $vtprd_cart->cart_items[$k]->product_name;
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'For product= ' .$product_name. '  No Discount - product already on sale, can"t apply further discount - discount in addition to sale pricing not allowed';
                //v1.1.0.4 end
                
                $do_continue = 'yes';                  
              break;
            case 'addToSalePrice':               
               //just act naturally, apply the discount to the price we find, which is already the Sale Price...
              break;
            case 'replaceSalePrice':     //ONLY applies if discount is greater than sale price!!!!!!!!
                /*  **********************************************************
                  At this point in time, unit and db_unit both contain the Sale Price,
                  Overwrite the sale price with the list price, process as normal, then check at the bottom...
                  if the discount is <= the existing sale price, DO NOT APPLY AS DISCOUNT!
                  ********************************************************** */ 
                $vtprd_cart->cart_items[$k]->unit_price     = $vtprd_cart->cart_items[$k]->db_unit_price_list;
                $vtprd_cart->cart_items[$k]->db_unit_price  = $vtprd_cart->cart_items[$k]->db_unit_price_list;               
              break;
          } //end cumulativeSalePricing check
                   
          if ($do_continue) {            
            continue; //skip further processing for this iteration of the "for" loop
          }
        }  //end product is on special check
        //set up cart audit trail info, keyed to rule prod_id
        $this->vtprd_init_cartAuditTrail($i,$k);


        //does product participate in inPop
        $inPop_found = $this->vtprd_test_if_inPop_product($i, $k);  //v2.0.0  added  $inPop_found   


        //v2.0.0 begin - 'each' tabulation and greater efficiency all round
        if ($inPop_found) {  
          $inPop_products_found_across_cart = TRUE;
          if (($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_and_or']    == 'each') ||
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_and_or']  == 'each') ||
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_and_or']     == 'each')) {
            $inPop_prod_cats_found_across_cart_array[]   = $vtprd_cart->cart_items[$k]->prod_cat_list;
            $inPop_plugin_cats_found_across_cart_array[] = $vtprd_cart->cart_items[$k]->rule_cat_list;
            if ($vtprd_cart->cart_items[$k]->parent_product_id_found_in_search) { //allows for PARENT ID found when child is actually in the cart
              $inPop_products_found_across_cart_array[]    = $vtprd_cart->cart_items[$k]->parent_product_id;
            } else {
              $inPop_products_found_across_cart_array[]    = $vtprd_cart->cart_items[$k]->product_id;
            }
            
          }
        } else {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product rejected - inpop';
        }
        //v2.0.0 end

         
        //does product participate in actionPop
        $actionPop_found = $this->vtprd_test_if_actionPop_product($i, $k, $inPop_found);   //v2.0.0  added   $actionPop_found     //v2.0.2.0 added $inPop_found
        
        
        //v2.0.0 begin
        if ($actionPop_found) {
          $actionPop_products_found_across_cart = TRUE;
        } else {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product rejected - actionpop';
        }
        //v2.0.0 end                                                         


      } //end cart-items 'for' loop


      //**********************************
      //v2.0.0 begin 
      //**********************************
      
      //test this rule for ongoing validity
      if (!$inPop_products_found_across_cart) {
         $vtprd_rules_set[$i]->rule_status = 'noInPopProducts';  //temp chg of rule_status for this execution only
         $vtprd_rules_set[$i]->rule_processing_status = 'All inPop products failed inclusion tests.';
         continue; //skip to next in rules rules 'for' loop
      } else {
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'inPop Products Found';
      }
      if (!$actionPop_products_found_across_cart) {
         $vtprd_rules_set[$i]->rule_status = 'noActionPopProducts';  //temp chg of rule_status for this execution only
         $vtprd_rules_set[$i]->rule_processing_status = 'All actionPop products failed inclusion tests.';
         continue; //skip to next in rules rules 'for' loop
      } else {
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'actionPop Products Found';
      }  
          
      //- buy group' each' testing
      //Test WHOLE inpop to see if it matches 'each' rqeuirements
      if (($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_and_or']    == 'each') ||
          ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_and_or']  == 'each') ||
          ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_and_or']     == 'each'))  {
        $each_conditions_met = TRUE;
        switch( TRUE ) { 
           case ( ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_and_or'] == 'each') &&
                  (sizeof($inPop_products_found_across_cart_array) == $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_count'] ) ):
                 $each_conditions_met = TRUE;
              break;        
           default: 
                 if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_and_or'] == 'each') {
                    if (sizeof($inPop_prod_cats_found_across_cart_array) <= 0) {
                      //each array should have data, so error
                      $each_conditions_met = FALSE;
                      break;
                    } else {
                      //flatten array
                      $inPop_prod_cats_found_across_cart_array = $this->vtprd_flatten_multi_array($inPop_prod_cats_found_across_cart_array);
                      //compare flattened array with select array
                      $stuff_not_found = array_diff($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'],$inPop_prod_cats_found_across_cart_array);  //array_diff($a1,$a2);  shows $a1 stuff not in $a2
                      if (sizeof($stuff_not_found) > 0) {
                        //size > 0 = entries from incl array not found, so error 
                        $each_conditions_met = FALSE;                       
                        break;
                      }                    
                    }
                    
                 }
                 if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_and_or'] == 'each') {
                    if (sizeof($inPop_plugin_cats_found_across_cart_array) <= 0) {
                      //each array should have data, so error
                      $each_conditions_met = FALSE;
                      break;
                    } else {
                      //flatten array
                      $inPop_plugin_cats_found_across_cart_array = $this->vtprd_flatten_multi_array($inPop_plugin_cats_found_across_cart_array);
                      //compare flattened array with select array
                      $stuff_not_found = array_diff($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'],$inPop_plugin_cats_found_across_cart_array);  //array_diff($a1,$a2);  shows $a1 stuff not in $a2
                      if (sizeof($stuff_not_found) > 0) {
                        //size > 0 = entries from incl array not found, so error 
                        $each_conditions_met = FALSE;                       
                        break;
                      }                    
                    }
                    
                 }     
                 if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_and_or'] == 'each') {
                    if (sizeof($inPop_products_found_across_cart_array) <= 0) {
                      //each array should have data, so error
                      $each_conditions_met = FALSE;
                      break;
                    } else {
                      //compare flattened array with select array
                      $stuff_not_found = array_diff($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'],$inPop_products_found_across_cart_array);  //array_diff($a1,$a2);  shows $a1 stuff not in $a2
                      if (sizeof($stuff_not_found) > 0) {
                        //size > 0 = entries from incl array not found, so error 
                        $each_conditions_met = FALSE;                       
                        break;
                      }                    
                    }
                    
                 }                             
              break; 
        }             
        if (!$each_conditions_met) {
           $vtprd_rules_set[$i]->rule_status = 'InPopFailedEach';  //temp chg of rule_status for this execution only
           $vtprd_rules_set[$i]->rule_processing_status = 'Inpop failed across-cart Each test(s).';
           continue; //skip to next in rules rules 'for' loop        
        }
      }
      //v2.0.0 end 
      //**********************************     
      
      //**************************************************************
      //v1.1.6.7 BEGIN sort actionPop_exploded_found_list for cheapest
      //v2.0.0 ADDED 'equal-or-less', changed to 'case' structure
      //**************************************************************
      switch( $vtprd_rules_set[$i]->apply_deal_to_cheapest_select ) { 
       
             case 'cheapest':                          
                 $sizeof_exploded_array = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
                 //create candidate array
                 /*
                 for( $s=0; $s < $sizeof_exploded_array; $s++) {
                    $cheapest_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];           
                 }
                 */
                 $cheapest_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list;
                 //http://stackoverflow.com/questions/7839198/array-multisort-with-natural-sort
                 //http://isambard.com.au/blog/2009/07/03/sorting-a-php-multi-column-array/
                 //sort group by prod_unit_price (relative column3), cheapest 1stt
                 $prod_unit_price = array();
                 foreach ($cheapest_array as $key => $row) {
                    $prod_unit_price[$key] = $row['prod_unit_price'];
                 } 
                 array_multisort($prod_unit_price, SORT_ASC, SORT_NUMERIC, $cheapest_array); 
        
                 
                 //load sorted array back into rule
                 $vtprd_rules_set[$i]->actionPop_exploded_found_list = $cheapest_array;
                 
                 // walk the array and store occurrence
                 for( $s=0; $s < $sizeof_exploded_array; $s++) {
                    $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
                 }
                  
               break; 
                     
             case 'most-expensive':                                         
                 $sizeof_exploded_array = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
                 //create candidate array
                 /*
                 for( $s=0; $s < $sizeof_exploded_array; $s++) {
                    $cheapest_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];           
                 }
                 */
                 $cheapest_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list;
                 //http://stackoverflow.com/questions/7839198/array-multisort-with-natural-sort
                 //http://isambard.com.au/blog/2009/07/03/sorting-a-php-multi-column-array/
                 //sort group by prod_unit_price (relative column3), cheapest 1stt
                 $prod_unit_price = array();
                 foreach ($cheapest_array as $key => $row) {
                    $prod_unit_price[$key] = $row['prod_unit_price'];
                 } 
                 array_multisort($prod_unit_price, SORT_DESC, SORT_NUMERIC, $cheapest_array); 
        
                 
                 //load sorted array back into rule
                 $vtprd_rules_set[$i]->actionPop_exploded_found_list = $cheapest_array;
                 
                 // walk the array and store occurrence
                 for( $s=0; $s < $sizeof_exploded_array; $s++) {
                    $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
                 }
               break; 
                     
             case 'equal-or-less': 
                          
                  //--------------------------------------------------------------------                
                  //Discount Equal or Lesser Value Item first
                  //- Discount the item(s) in the GET Group of equal or lesser value to the most expensive item in the BUY Group    
                  //--------------------------------------------------------------------  
                           
                 //***************************
                 // SORT inPOP for MOST EXPENSIVE
                 //***************************
                 
                 $sizeof_exploded_array = sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list);
                 //create candidate array
                 /*
                 for( $s=0; $s < $sizeof_exploded_array; $s++) {
                    $most_expensive_array [] = $vtprd_rules_set[$i]->inPop_exploded_found_list[$s];           
                 }
                 */
                 $most_expensive_array = $vtprd_rules_set[$i]->inPop_exploded_found_list;
                 //http://stackoverflow.com/questions/7839198/array-multisort-with-natural-sort
                 //http://isambard.com.au/blog/2009/07/03/sorting-a-php-multi-column-array/
                 //sort group by prod_unit_price (relative column3), cheapest 1stt
                 $prod_unit_price = array();
                 foreach ($most_expensive_array as $key => $row) {
                    $prod_unit_price[$key] = $row['prod_unit_price'];
                 } 
                 array_multisort($prod_unit_price, SORT_DESC, SORT_NUMERIC, $most_expensive_array); 
        
                 
                 //load sorted array back into rule
                 $vtprd_rules_set[$i]->inPop_exploded_found_list = $most_expensive_array;

                 $most_expensive_inPop_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[0]['prod_id'];
                 $most_expensive_inPop_unit_price = $vtprd_rules_set[$i]->inPop_exploded_found_list[0]['prod_unit_price'];

                 //END inPop processing
                 //***************************
                 
                
                 //***************************
                 //BEGIN action processing - SORT and DIMINISH actionPOP
                 //***************************                                   
                 $sizeof_exploded_array = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);

                 $work_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list;
                 
                 //FIND if most expensive inPOP is in actionPOP
                 $most_expensive_found_in_actionPop = false;
                 for($z=0; $z < $sizeof_exploded_array; $z++) { 
                    if ($work_array[$z]['prod_id'] == $most_expensive_inPop_prod_id) {
                      //mark name 
                      $work_array[$z]['prod_name'] = 'AAAAA'.$work_array[$z]['prod_name'];
                      $most_expensive_found_in_actionPop = $work_array[$z]['prod_name'];
                      continue;
                    }
                 } 

                 //syntax requires PHP 5.5 +
                 // from https://stackoverflow.com/questions/3232965/sort-multidimensional-array-by-multiple-keys
                 array_multisort(array_column($work_array, 'prod_unit_price'), SORT_DESC, SORT_NUMERIC, 
                                 array_column($work_array, 'prod_name'), SORT_ASC,
                                  $work_array);
                                  
                 //if  ($most_expensive_found_in_actionPop) , throw away everything until AAAAA found
                 // then start copying with the NEXT one found  
                 $most_expensive_array = array();
                 if  ($most_expensive_found_in_actionPop) {                    
                    $do_copy_action = false;
                    foreach ($work_array as $key => $row) {
                      if ($do_copy_action) {
                        $most_expensive_array[] = $row;
                      }                      
                      if ((!$do_copy_action) && ($row['prod_name'] == $most_expensive_found_in_actionPop)) {
                        $do_copy_action = true;
                      }                      
                    }
                 } else {
                  // start copying when price <= most expensive price
                    foreach ($work_array as $key => $row) {
                      if ($row['prod_unit_price'] <= $most_expensive_inPop_unit_price) {
                        $most_expensive_array[] = $row;
                      }
                    }                 
                 }             
                                                                                            
                 //load sorted AND DIMINISHED array back into rule
                 $vtprd_rules_set[$i]->actionPop_exploded_found_list = $most_expensive_array;
                 
                 $sizeof_exploded_array = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
                 
                 // walk the array and store occurrence
                 for( $s=0; $s < $sizeof_exploded_array; $s++) {
                    $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
                 }
                 //END actionPop processing
                 //***************************                 
                 
               break;
                               
             default: 
               break;
                                          
      }
      //********************
      //v2.0.0 end
      //********************
      
      
      //******************** 
      //v1.1.8.1 begin 
      //handle applying a $$ discount across all of a group, ONLY 
      // if action count is 5 9's , which are the signal to spread the $$ discount across all of the "GET" group in the cart

  //TEST TEST TEST CHANGED TO '99999'
  //error_log( print_r(  '$vtprd_rules_set $i= ' .$i, true ) );
  //error_log( var_export($vtprd_rules_set[$i], true ) ); 


      if ($vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_count'] == '99999') {
        $pricing_type_select = $vtprd_rules_set[$i]->pricing_type_select;
        if ( (in_array($pricing_type_select, array('simple', 'bogo', 'all'))) &&
             ($vtprd_rules_set[$i]->rule_deal_info[0]['discount_applies_to']  == 'all') &&
             ($vtprd_rules_set[$i]->rule_deal_info[0]['discount_amt_type']   == 'currency') &&
             ($vtprd_rules_set[$i]->rule_deal_info[0]['action_repeat_condition'] == 'none')  ) {
          $vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_count'] = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list); 
          //error_log( print_r(  '$action_amt_count= ' .$vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_count'], true ) );   
        }
      }
      //v1.1.8.1 end 
      //********************  
      
    
    }  //end rules 'for' loop



      return;   
   }                              
 
        
   public function vtprd_manage_shared_rule_tests($i) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      
    //error_log( print_r(  'vtprd_manage_shared_rule_tests ', true ) ); 

      $rule_is_date_valid = vtprd_rule_date_validity_test($i);
      if (!$rule_is_date_valid) {
         $vtprd_rules_set[$i]->rule_status = 'dateInvalid';  //temp chg of rule_status for this execution only
         $vtprd_rules_set[$i]->rule_processing_status = 'Cart Transaction does not fall within date boundaries set for the rule.';
         return;
      }
      
      //v2.0.1.0  - begin
      $this->vtprd_manage_customer_rule_tests($i);
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
          return; 
      }
      //v2.0.1.0  - end

      //EDITED * + * +  * + * +  * + * +  * + * +  
          
     //v1.1.0.9 begin - coupon_activated_discount for later use in parent-cart-validation during remove_coupon, as needed
     //  if coupon removed, this promotes the re-run of the discount at the same time. (re-use of session var, similar situation from v1.1.0.8)    
      //don't run if 'no'
      if ($vtprd_rules_set[$i]->cumulativeCouponPricing == 'no') {
         
         //cumulativeCouponNo for later use in parent-cart-validation during add/remove_coupon, as needed
         //  if coupon removed, this promotes the re-run of the discount at the same time.
         //  This session var set to true will cause a re-process of the cart on Cart and checkout pages ONLY        
        if(!isset($_SESSION)){
          session_start();
          header("Cache-Control: no-cache");
          header("Pragma: no-cache");
        }
        $_SESSION['cumulativeCouponNo'] = true;
     
        $sizeof_coupon_codes_array = (sizeof($vtprd_info['coupon_codes_array'])) ; 
        if ( ($vtprd_rules_set[$i]->only_for_this_coupon_name > ' ')  &&
             ($sizeof_coupon_codes_array == 1) &&
             (in_array($vtprd_rules_set[$i]->only_for_this_coupon_name, $vtprd_info['coupon_codes_array'] )) ) {
          //activated by coupon and 1coupon found, so all ok
          $all_good = true;
        } else {
           //coupons array is **without** deals coupon
           if ($sizeof_coupon_codes_array > 0) {
             $vtprd_rules_set[$i]->rule_status = 'cumulativeCouponPricingNo';  //temp chg of rule_status for this execution only
             $vtprd_rules_set[$i]->rule_processing_status = 'Coupon presented, rule switch says do not run.'; 
             return;       
            }        
        }                     
      }
      //v1.1.0.9 end  
      
      return;   
   
   } 
   
   
  // ****************  
  // v2.0.1.0 - new function
  //   ALL rule customer testing should be done only once
  //   New rule switch showing results passed to inpop test - buy_group_population_info['buy_group_customer_found'] 
  // ****************     
        
   public function vtprd_manage_customer_rule_tests($i) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;

      $k = 0; //dummy number since it's required by the inpop_list_check functions - we don't have a product to process at this point.
      
      $cust_exclusion_listed_in_rule_but_not_matched = false;


        //EDITED * + * +  * + * +  * + * +  * + * +
        
                
         if ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_groups_excl_array']) > 0) &&
              (function_exists('_groups_get_tablename') ) ) {
            if ($this->vtprd_are_groups_in_inPop_list_check($i, $k, 'excl', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_groups_excl_array']) ) {
              $vtprd_rules_set[$i]->rule_status = 'custInvalid';  
              $vtprd_rules_set[$i]->rule_processing_status = 'Groups exclusion found';
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Groups exclusion found - Customer + Rule Invalid'; 
     //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 009 ', true ) );
              return;
            }  else {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Groups list did NOT match exclusion list';
              $cust_exclusion_listed_in_rule_but_not_matched = true;        
            }
         }
         
         if ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_memberships_excl_array']) > 0) &&
              (function_exists('wc_memberships') ) ) {
            if ($this->vtprd_are_memberships_in_inPop_list_check($i, $k, 'excl', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_memberships_excl_array']) ) {
              $vtprd_rules_set[$i]->rule_status = 'custInvalid';  
              $vtprd_rules_set[$i]->rule_processing_status = 'Memberships exclusion found';
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Memberships exclusion found - Customer + Rule Invalid'; 
     //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 010 ', true ) );
              return;
            }  else {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Memberships list did NOT match exclusion list';
              $cust_exclusion_listed_in_rule_but_not_matched = true;        
            }
         } 
            
         //v2.0.2.0 begin
         if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] == 'excludeOnly') { 
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Groups inclusion OK, no exclusions found for an exclude-only INPOP rule';
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Groups inclusion OK, no exclusions found for an exclude-only INPOP rule';
            $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']  = true;
            return;
         }
         //v2.0.2.0 end         
    

     /*
     //***********************
     //CUSTOMER INCLUSION Attributes Begin
     //***********************
     1. only 1 customer attribute needs finding.
     2. if found, 'and' or 'or', we're done.
     */
     $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']  =  false;
     $customer_inclusion_criteria_present_but_not_found = false; 

     
     //EDITED * + * +  * + * +  * + * +  * + * +

         //if customer not found, test groups
         if (!$vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']) {     
           if ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_groups_incl_array']) > 0)  &&
                (function_exists('_groups_get_tablename') ) ) {
              if ($this->vtprd_are_groups_in_inPop_list_check($i, $k, 'incl', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_groups_incl_array']) ) {
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Groups inclusion found';
                $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']  = true;
                return;   
              } else {
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Listed inclusion Groups list NOT found for customer'; 
                $customer_inclusion_criteria_present_but_not_found = true;             
                if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] == 'and') {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'NO Customer Groups inclusion found, "AND" on rule';
                }        
              }
           }
         }
         //if customer not found, test memberships
         if (!$vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']) {     
           if ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_memberships_incl_array']) > 0)  &&
                (function_exists('wc_memberships') ) ) {
              if ($this->vtprd_are_memberships_in_inPop_list_check($i, $k, 'incl', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_memberships_incl_array']) ) {
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Memberships inclusion found';
                $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']  = true;
                return;    
              } else {
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Listed inclusion Memberships list NOT found for customer'; 
                $customer_inclusion_criteria_present_but_not_found = true;             
                if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] == 'and') {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'NO Customer Memberships inclusion found, "AND" on rule';
                }        
              }
           }


         //Customer required for AND , but NOT FOUND
         if (!$vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found'])  {
         
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'NO Customer exclusion or inclusion found';  
            if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] == 'and') {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'NO Customer inclusion found on an "AND" rule!';
              $vtprd_rules_set[$i]->rule_status = 'custInvalid';  
              $vtprd_rules_set[$i]->rule_processing_status = 'NO Customer inclusion found on an "AND" rule!'; 
       //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 011 ', true ) );
            } 
            //v2.0.2.0 begin
            /*
            if ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array']) > 0) ||
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_excl_array']) > 0) || 
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array']) > 0) ||              
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_excl_array']) > 0) ||
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array']) > 0) || 
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array']) > 0) ||              
                        ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array'] > '') ||               
                        ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array'] > '') || 
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_incl_array']) > 0) ||              
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_excl_array']) > 0) ||
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_subscriptions_incl_array']) > 0) ||              
                 (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_subscriptions_excl_array']) > 0) ) { 
             */    
            if ( ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] == 'includeOnly') ||
                 ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] == 'excludeOnly') ||
                 ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] == 'both') ) {
            //v2.0.2.0 end
              $carryOn_Rule_process = true;
            } else {
              //CUSTOMER is only criteria at this point
              //if cust exclusion in rule and no other criteria, but exclusion not matched, then ok
              if ($cust_exclusion_listed_in_rule_but_not_matched) { 
                //this is a 'virtual' setting - by proving a negative, a positive result is achieved
                $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found'] = true;          
              } else {
              //if cust inclusion in rule and no other criteria, but inclusion not matched, then fail
                if($customer_inclusion_criteria_present_but_not_found) { 
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'NO Customer inclusion found, and no further criteria on rule!';
                  $vtprd_rules_set[$i]->rule_status = 'custInvalid';  
                  $vtprd_rules_set[$i]->rule_processing_status = 'NO Customer inclusion found, and no further criteria on rule'; 
                } 
              }                 
            } 
                     
         } 
         
     } 

     //v2.0.2.0 end
     //***********************
     //CUSTOMER INCLUSION Attributes End
     //***********************  
     
     return;        
         
   }
       
   
  // ****************  
  // inPop TESTS
  // ****************     
        
   public function vtprd_test_if_inPop_product($i, $k) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      
    //error_log( print_r(  'vtprd_test_if_inPop_product ', true ) ); 
       
      /*  v1.0.5 
      ADDTIONAL RULE CRITERIA FILTER - optional, default = TRUE   (useful to add additional checks on a specific rule)
      - vtprd_additional_inpop_include_criteria -
      
      all data needed accessible through global statement, eg global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
        Rule ID = $vtprd_rules_set[$i]->post_id
       filter can check for specific rule_id, and apply criteria.
         if failed additional criteria check, return FALSE, so that the rule is not executed 
      To Execute, sample:
        add_filter('vtprd_additional_inpop_include_criteria', 'your function name', 10, 3);
        $i = ruleset occurrence ($vtprd_rules_set[$i])
        $k = cart occurence  ($vtprd_cart->cart_items[$k])
        
      LOOK FOR 'process_additional_inpop_include_criteria'  example at the bottom of the document... 
      */
      
      $inPop_found = false; //v2.0.0
      
      switch( $vtprd_rules_set[$i]->inPop ) {  
           case 'wholeStore':                                                                                                                     
                $additional_include_criteria = apply_filters('vtprd_additional_inpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product rejected by additional criteria filter';
                } else {
                  //load whole cart into inPop
                  $this->vtprd_load_inPop_found_list($i, $k);
                  $inPop_found = true; //v2.0.0                  
                }           
            break;
          case 'cart':                
                $additional_include_criteria = apply_filters('vtprd_additional_inpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product rejected by additional criteria filter';
                } else {
                  //load whole cart into inPop               
                  $this->vtprd_load_inPop_found_list($i, $k);
                  $inPop_found = true; //v2.0.0                    
                }          
            break;
          //v2.0.0 begin
          default:               
                $additional_include_criteria = apply_filters('vtprd_additional_inpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product rejected by additional criteria filter';
                } else {
                  //test if product belongs in rule inPop 
                  if ( $this->vtprd_is_product_in_inPop_group($i, $k) ) {
                    $this->vtprd_load_inPop_found_list($i, $k);
                    $inPop_found = true; //v2.0.0                         
                  } else {
                    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product not found in group';
                  }                
                }
            break;
            //v2.0.0 end
        } 
        
      return $inPop_found; //v2.0.0 ;
    } 


  // **************** 
  // actionPop TESTS        
  // **************** 
           
   public function vtprd_test_if_actionPop_product($i, $k, $inPop_found) {      //v2.0.2.0 added $inPop_found)
   
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      
    //error_log( print_r(  'vtprd_test_if_actionPop_product ', true ) );      
      /*  v1.0.5 
      ADDTIONAL RULE CRITERIA FILTER - optional, default = TRUE   (useful to add additional checks on a specific rule)
      - vtprd_additional_actionpop_include_criteria -
      
      all data needed accessible through global statement, eg global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
        Rule ID = $vtprd_rules_set[$i]->post_id
       filter can check for specific rule_id, and apply criteria.
         if failed additional criteria check, return FALSE, so that the rule is not executed 
      To Execute, sample:
        add_filter('vtprd_additional_actionpop_include_criteria', 'your function name', 10, 3);
        $i = ruleset occurrence ($vtprd_rules_set[$i])
        $k = cart occurence  ($vtprd_cart->cart_items[$k])
      */
      
      $actionPop_found = FALSE; //v2.0.0
      
      switch( $vtprd_rules_set[$i]->actionPop ) {  
          case 'sameAsInPop':                
                $additional_include_criteria = apply_filters('vtprd_additional_actionpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Actionpop Product rejected by additional criteria filter';
                } else {
                  //if current product in inpop products array...
                  //v2.0.2.0 begin - changed to $inPop_found
                  if ($inPop_found) {
                  //if ( in_array($vtprd_cart->cart_items[$k]->product_id, $vtprd_rules_set[$i]->inPop_prodIds_array) ) {
                  //v2.0.2.0 end
                    $this->vtprd_load_actionPop_found_list($i, $k);
                    $actionPop_found = TRUE; //v2.0.0
                  } else {
                    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Product not found in inpop list, so not included on actionPop';
                  }                
                }
            break;
          case 'wholeStore':               
                $additional_include_criteria = apply_filters('vtprd_additional_actionpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Actionpop Product rejected by additional criteria filter';
                } else {
                  $this->vtprd_load_actionPop_found_list($i, $k);
                  $actionPop_found = TRUE; //v2.0.0                
                }
            break;            
          case 'cart':                                                                                                     
                $additional_include_criteria = apply_filters('vtprd_additional_actionpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Actionpop Product rejected by additional criteria filter';
                } else {
                  //load whole cart into actionPop
                  $this->vtprd_load_actionPop_found_list($i, $k);
                  $actionPop_found = TRUE; //v2.0.0                
                }
            break;
          default:             
                $additional_include_criteria = apply_filters('vtprd_additional_actionpop_include_criteria',TRUE,$i, $k );
                if ($additional_include_criteria == FALSE) {
                   $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Actionpop Product rejected by additional criteria filter';
                } else {
                  //test if product belongs in rule actionPop
                  if ( $this->vtprd_is_product_in_actionPop_group($i, $k) ) {
                    $this->vtprd_load_actionPop_found_list($i, $k);
                    $actionPop_found = TRUE; //v2.0.0                        
                  } else {
                    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Actionpop Product not found in group';
                  }                
                }
            break;
 
        } 
        
      return $actionPop_found; //v2.0.0
    } 



  public function vtprd_process_cart_for_rules_discounts() {
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info;  
      
    //error_log( print_r(  'vtprd_process_cart_for_rules_discounts ', true ) ); 
        
    //************************************************
    //SECOND PASS - have the inPop, output and rule conditions been met
    //************************************************
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) {         
 
        //v2.0.1.0 begin
        //skip invalid rules
        if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
          continue;  //skip out of this for loop iteration
        }
        //v2.0.1.0 end
       
       // v1.1.7.3 
       /*
       track - was previous cart discounted, if so, rule list
       track - previous cart standalone woo coupon discount
       
       - using the tracked values, when previous Pricing Deal discount is disallowed
        OR
       - a previous WOO COUPON standalone was disallowed
       put up a MESSAGE to let the customer know.
       
       */
 
            
      //************************************
      // v1.1.7.2 begin grpD
      //************************************
      /*
          * Enhancement - CART Discounting Single Discount Controls
                    		(to TURN ON these new actions, use the Matching Filter statement below.)
              
              Sort Rules
              **MUST** set "Other Rule Discounts" "Priority" value on EACH rule for RULE SORTING
                    Priority 1 is the highest priority.
                    An unique Priority number on each rule should be used on each rule, to set the execution sequence.
                                                           
          		In Pricing Deals, there are:
          		  (A) Pricing Deals discounts
          		  (B) Woo Coupon standalone discounts
          		  (C) Pricing Deals discount *actuated* by Woo Coupon
          		
              New Filters which control Discount Limits (choose 1 !!):	
            		
                1. ONLY 1 overall pricing deals discount (A) or (C) Per Cart (doesn't care about WOO coupon discounts(B) )
            			// SORT Rules as desired, as described above
                  //to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file
            			add_filter( 'vtprd_limit_discounts', function() { return 'allow_only_one_overall_pricing_deal_discount_per_cart'; } ); 
                  
            		 - OR -
                 
                2. IF separate WOO coupon presented (B), no pricing deals (A) or (C)
            			// NO Sorting required
                  //to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file
            			add_filter( 'vtprd_limit_discounts', function() { return 'allow_no_pricing_deals_if_woo_coupon_standalone_discount'; } ); 
                  
            		 - OR -
                 
                3. ONLY 1 Pricing deal coupon-actuated rule (C)  per cart
                  // SORT (C) rules first, as described above 
            			//to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file
            			add_filter( 'vtprd_limit_discounts', function() { return 'allow_only_one_coupon_actuated_pricing_deal_discount_per_cart'; } ); 
                  
            		 - OR -
                 
                4. IF Pricing deal coupon-actuated rule (C) presented, no further Pricing Deals discounts (A) or (C)
            			// SORT (C) rules first, as described above
                  //to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file
            			add_filter( 'vtprd_limit_discounts', function() { return 'allow_no_more_pricing_deal_discounts_if_coupon_actuated_pricing_deal_presented'; } );
      
      */                  
       if ( ($vtprd_info['current_processing_request'] == 'cart') && 
            (isset($vtprd_setup_options['limit_cart_discounts']) ) ) {
           $set_continue = false; 
           switch( $vtprd_setup_options['limit_cart_discounts'] ) {
           
            case FALSE:
            case 'none':
              break;
              
            case 'allow_only_one_cart_pricing_deal_discount_per_cart': //1
                //1. ONLY 1 overall pricing deals discount (A or C) Per Cart (doesn't care about WOO coupon discounts(B) )
                if ( $vtprd_cart->yousave_cart_total_amt > 0)  {  //yousave already has a value, we're done.
                   $vtprd_rules_set[$i]->rule_status = 'noFurtherDiscounts 1';  //temp chg of rule_status for this execution only
                   $vtprd_rules_set[$i]->rule_processing_status = 'Cart filter set to a single overall discount - already applied in previous rule.';        
                   $set_continue = true; 
                }
               break; 
                        
            case 'allow_no_cart_pricing_deals_if_woo_coupon_standalone_discount':  //2 
                //2. IF separate WOO coupon presented (B), no pricing deals (A) or (C)
                if ($vtprd_info['skip_cart_processing_due_to_coupon_individual_use']) { //set in vtprd_count_other_coupons(), only sets when WOO standalone coupon presented
                   $vtprd_rules_set[$i]->rule_status = 'noFurtherDiscounts 2';  //temp chg of rule_status for this execution only
                   $vtprd_rules_set[$i]->rule_processing_status = 'Cart filter set no other discounts if WOO standalone coupon applied.';        
                   $set_continue = true; 
                }
                break;                     
            case 'allow_only_one_coupon_actuated_pricing_deal_discount_per_cart': //3 
               //3. ONLY 1 Pricing deal coupon-actuated rule (C)  per cart
                if ( ($_SESSION['coupon_activated_discount']) && //coupon_activated_discount = PREVIOUS rule was coupon activated and discounted
                     ($vtprd_rules_set[$i]->only_for_this_coupon_name > ' ') ) { //this rule is coupon-activated
                   $vtprd_rules_set[$i]->rule_status = 'noFurtherDiscounts 3';  //temp chg of rule_status for this execution only
                   $vtprd_rules_set[$i]->rule_processing_status = 'Cart ONLY 1 Pricing deal coupon-actuated rule per cart, coupon actuation already applied in previous rule.';        
                   $set_continue = true;  
                }  
               break;                                     

            case 'allow_no_other_cart_pricing_deal_discounts_if_coupon_actuated_pricing_deal_presented':  //4 
               //4. IF Pricing deal coupon-actuated rule (C) presented, no further Pricing Deals discounts (A or C) 
                //NEEDS a sort for coupon actuated rules first
                if ($_SESSION['coupon_activated_discount']) { //coupon_activated_discount = PREVIOUS rule was coupon activated and discounted
                   $vtprd_rules_set[$i]->rule_status = 'noFurtherDiscounts 4';  //temp chg of rule_status for this execution only
                   $vtprd_rules_set[$i]->rule_processing_status = 'Cart Pricing deal coupon-actuated rule already presented, no further discounting.';        
                   $set_continue = true;  
                } 
               break;              



            //ALSO break this out of here, put after rule execution, in case there's only 1 rule
            case 'allow_no_standalone_woo_coupon_discounts_if_any_cart_pricing_deal_discount_granted':  //5
               //5. if woo coupon standalone, disallow if cart discount
                if ($vtprd_cart->yousave_cart_total_amt > 0) { //coupon_activated_discount = PREVIOUS rule was coupon activated and discounted       
                   vtprd_remove_any_other_woo_coupons(); 
                } 
               break;              

            //ALSO put after rule execution, in case there's only 1 rule
            case 'allow_no_standalone_woo_coupon_discounts_if_coupon_actuated_pricing_deal_granted':  //6
               //6. if woo coupon standalone, disallow if cart discount actuated by coupon 
                if ($_SESSION['coupon_activated_discount']) { //coupon_activated_discount = PREVIOUS rule was coupon activated and discounted
                   vtprd_remove_any_other_woo_coupons();  
                } 
               break;              



           }
           if ($set_continue) {
              continue;
           }
       }                                                       
      // v1.1.7.2 end
      //************************************
      
      
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {          
        continue;  //skip the rest of this iteration, but keep the "for" loop going
      }


      //THIS WOULD ONLY BE A MESSAGE REQUEST AT DISPLAY TIME for a single product on a Cart rule      
      if ($vtprd_info['current_processing_request'] == 'display') {  
          if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
            $vtprd_info['product_session_info']['product_rule_short_msg_array'][] = $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_short_msg'];
            $vtprd_info['product_session_info']['product_rule_full_msg_array'][]  = $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_full_msg'];
            $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'MessageRequestCompleted';
            $vtprd_cart->cart_items[0]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'Display Message for Cart rule successfully sent back.';           
            continue;  //skip the rest of this iteration, but keep the "for" loop going
          }
      } 

      //no point in continuing of no actionpop to discount for this rule...
      if ( sizeof($vtprd_rules_set[$i]->actionPop_found_list) == 0 ) {
       // $vtprd_rules_set[$i]->rule_requires_cart_action = 'no';
        $vtprd_rules_set[$i]->rule_processing_status = 'No action population products found for this rule.';
        continue;   
      }      
      //reset inPop running totals for each rule iteration
      $vtprd_rules_set[$i]->inPop_group_begin_pointer     = 1; //begin with 1st iteration
      $vtprd_rules_set[$i]->inPop_exploded_group_begin   = 0;
      $vtprd_rules_set[$i]->inPop_exploded_group_end     = 0;

      //reset actionPop running totals => they will aways reflect the inPop, unless using different actionPop
      $vtprd_rules_set[$i]->actionPop_group_begin_pointer     = 1;  //begin with 1st iteration
      $vtprd_rules_set[$i]->actionPop_exploded_group_begin   = 0;  
      $vtprd_rules_set[$i]->actionPop_exploded_group_end     = 0; 

    /* ******************
     PROCESS CART FOR DISCOUNT: group within rule until: info lines done / processing completed / inpop ended
     ********************* */       
      
      //Overriding Control Status Switch Setup
      $vtprd_rules_set[$i]->discount_processing_status = 'inProcess'; // inProcess / completed /  InPopEnd
     // $vtprd_rules_set[$i]->end_of_actionPop_reached = 'no';   
 
         
        //********************************
        //v1.1.8.0  Begin
        //******************************** 
        if ($vtprd_rules_set[$i]->pricing_type_select == 'bulk') {
        
            //find last occurrence in bulk tiers array
            $sizeof_bulk_deal_array = sizeof($vtprd_rules_set[$i]->bulk_deal_array);
            //$last_bulk_row_occurs = $sizeof_bulk_deal_array - 1;
            $last_bulk_row_occurs = sizeof($vtprd_rules_set[$i]->bulk_deal_array) - 1;
                            
            //**********************************
            //inpop quantities = actionpop quantites, as we're dealing with the same set.
            //**********************************
            $b=$last_bulk_row_occurs;
           
            //loop through rows to find active row, from LAST to FIRST
            $occurrence_found = false;
            $actionPop_end = false;
            //**************
            $d = 0; //only processes first occurrence of the rule_deal_info array!!!  $d is actually set in the NEXT for loop!!
            $sizeof_actionPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
            $actionPop_exploded_group_end = false;
            
            //**************
            $b=$last_bulk_row_occurs;
            //*******************************************************************
            //first test if total units or price greater than max limit in rows, otherwise do a for loop.
            switch ( true ) {

                //Test last max for units
                case ( ($vtprd_rules_set[$i]->bulk_deal_method == 'units') &&
                       ($vtprd_rules_set[$i]->inPop_qty_total >= $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) ) :                     
                     $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type']    = 'quantity';
                     $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';                                    
                     $occurrence_found = true;
                     $actionPop_exploded_group_end = $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'];
                     $actionPop_end = 'count';
                     $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Bulk Row Found, occurrence= ' .$b. '  path001' ;
                     
                     if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to']  == 'each') {
                       $msg_suffix = __(' for each purchase ', 'vtprd');
                     } else {
                       $msg_suffix = __(' across all purchases ', 'vtprd');
                     }
                     
                     $vtprd_rules_set[$i]->bulk_deal_processing_array = array (
                        'actionPop_exploded_group_end'      =>  $actionPop_exploded_group_end,
                        'currency_last_iteration_remainder' =>  0,
                        'prod_id' =>  '', 
                        'orig_prod_unit_price' =>  '',
                        'bulk_array_occurrence' =>  $b,
                        'msg_suffix' =>  $msg_suffix          
                     );                     
                   break;
                
                //Test last max for currency                                 
                case ( ($vtprd_rules_set[$i]->bulk_deal_method == 'currency') && 
                       ($vtprd_rules_set[$i]->inPop_total_price >= $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) ) :                       
                     $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type']    = 'currency';
                     
                     //TEST $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'currency'; 
                     $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';              
                     $occurrence_found = true; 
                     $actionPop_exploded_group_end = $this->vtprd_bulk_find_currency_actionpop_end($i, $b, $sizeof_actionPop_exploded_found_list);
                     $actionPop_end = 'count'; 
                     $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Bulk Row Found, occurrence= ' .$b. '  path002' ;
                                       
                   break;
                                                                            
                default:
                    /*
                    spin through the bulk array, find actual bulk row and $actionPop_exploded_group_end.
                    start from the last, work back to the first (o)
                    
                    -----------------------------
                    If qty total (units or $$) 
                      > than previous Max
                      < current Min
                      USE the previous Max ***
                    -----------------------------
                    */
                    for($b=$last_bulk_row_occurs; $b > -1; $b--) {              
                      switch ( $vtprd_rules_set[$i]->bulk_deal_method ) { 
                          case ('units'):
                               if ( ( ($vtprd_rules_set[$i]->inPop_qty_total >= $vtprd_rules_set[$i]->bulk_deal_array[$b]['min_value']) && 
                                      ($vtprd_rules_set[$i]->inPop_qty_total <= $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) ) 
                                          ||
                                         //total falls between previous min and current max, so use *this* max 
                                      ($vtprd_rules_set[$i]->inPop_qty_total > $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) ) {
                                 //set QUANTITY in the rule processing
                                 $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type']    = 'quantity';
                                 $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';                                    
                                 $occurrence_found = true;                                
                                 //in this case, qty total = sizeof list...  for UNITS, all we need is the endpoint
                                 
                                 if ($vtprd_rules_set[$i]->inPop_qty_total > $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) {
                                    $actionPop_exploded_group_end = $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'];
                                 } else {
                                    $actionPop_exploded_group_end = $sizeof_actionPop_exploded_found_list;
                                 }
                                 //$actionPop_exploded_group_end = $sizeof_actionPop_exploded_found_list;
                                                                  
                                 $actionPop_end = 'count';
                                  
                                 if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to']  == 'each') {
                                   $msg_suffix = __(' for  each unit purchased, up to a total of ', 'vtprd') .$vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'] . __(' units', 'vtprd');
                                 } else {
                                   $msg_suffix = __(' across all purchases up to a total of ', 'vtprd') .$vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'] . __(' units', 'vtprd');
                                 } 
                                                                                  
                                 $vtprd_rules_set[$i]->bulk_deal_processing_array = array (
                                    'actionPop_exploded_group_end'      =>  $actionPop_exploded_group_end,
                                    'currency_last_iteration_remainder' =>  0,
                                    'prod_id' =>  '', 
                                    'orig_prod_unit_price' =>  '',
                                    'bulk_array_occurrence' =>  $b,
                                    'msg_suffix' =>  $msg_suffix          
                                 );
                                 $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Bulk Row Found, occurrence= ' .$b. '  path003' ;                                                                
                                 break 3; //get out of switch, for loop, switch -  all done
                               } 
                            break;
                            
                          case ('currency'):    
                               if ( ( ($vtprd_rules_set[$i]->inPop_total_price >= $vtprd_rules_set[$i]->bulk_deal_array[$b]['min_value']) && 
                                      ($vtprd_rules_set[$i]->inPop_total_price <= $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) ) 
                                           ||
                                         //total falls between previous min and current max, so use *this* max 
                                      ($vtprd_rules_set[$i]->inPop_total_price > $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) ) {                                                                                               
                                 //set CURRENCY in the rule processing
                                  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type']    = 'currency';
                     
                                 //TEST $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'currency'; 
                                  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';
                                    
                                  $occurrence_found = true;
                                  $actionPop_exploded_group_end = $this->vtprd_bulk_find_currency_actionpop_end($i, $b, $sizeof_actionPop_exploded_found_list);
                                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Bulk Row Found, occurrence= ' .$b. '  path004' ;
          
                                  $actionPop_end = 'count';
                                  break 3; //get out of switch, for loop, switch -  all done
                               }  //end if for currency case 
       
                            break;
                        } //end switch 
                           
                    } //end for loop
                    
                   break;                    
            }  //end switch 
            
            if (!$occurrence_found) {
              $vtprd_rules_set[$i]->rule_status = 'bulkNotFound';
              $vtprd_rules_set[$i]->rule_processing_status = 'Bulk Rows - No occurrence found to match criteria';
              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Total Unit Qty (= '.$vtprd_rules_set[$i]->inPop_qty_total.') or Total Pricing Qty (= '.$vtprd_rules_set[$i]->inPop_total_price.') insufficient for Bulk Pricing.';
              continue; //skip this rule
            }
            
            /*
            $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']          = $vtprd_rules_set[$i]->bulk_deal_array[$b]['min_value'];
            $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']       = $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'];  
            */

            $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']     = $vtprd_rules_set[$i]->bulk_deal_qty_count_by; //each or all
            $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']  = $vtprd_rules_set[$i]->bulk_deal_qty_count_by; //each or all
            $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']      = $vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_type']; //percent, etc
            $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count']     = $vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_value']; //percentage, etc

            //*****************

            $currency_last_iteration_remainder = 0;

           //*********************
           //Set up ACTION area 
           // is the discount applied to each item, or across all items?
           //*********************
            switch (TRUE) {
               
                //if applies to each, count is always 1 and applied to individually - currency total handled later with remainder, as needed
                case ($vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_type'] == 'fixedPrice') : 
                     // ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') is always true in this case  

                    //by this time we know the max number to be discounted - no reason not be to set that as the buy group value. (amt_types set above)
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']           = $actionPop_exploded_group_end;
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']        = 1;
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_condition'] = 'count'; //always 'count' currently
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_count']     = $actionPop_exploded_group_end; //++; //this is 'last occurs, starts at 0.'                   
                    //if currency and there's a remainder, then special fractional processing when discounting, ***but ONLY when applying a PERCENTAGE***!!!                     
                  break;
                
                //if applies to each, count is always 1 and applied to individually - currency total handled later with remainder, as needed
                case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') 
                          ||
                       ($vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_type'] == 'percent') ) :  //with  percent, all = each, so it applies equally to currency

                    //by this time we know the max number to be discounted - no reason not be to set that as the buy group value. (amt_types set above)
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']          = $actionPop_exploded_group_end;
                    
                    //override for the case where percent, all = each, so it applies equally to currency
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to']  = 'all';  

                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] = 1;
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_condition'] = 'count'; //always 'count' currently
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_count']     = $actionPop_exploded_group_end; //++; //this is 'last occurs, starts at 0.'                   
                    //if currency and there's a remainder, then special fractional processing when discounting, ***but ONLY when applying a PERCENTAGE***!!!                     
                  break;

                //discount spread across all
                case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'all'):                  
            
                    //by this time we know the max number to be discounted - no reason not be to set that as the buy group value. (amt_types set above)
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']          = $actionPop_exploded_group_end;
                    
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_condition'] = 'count';  //always 'count' currently
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] = $actionPop_exploded_group_end;
                    $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_condition'] = 'none';                     
                     
                    //if currency and there's a remainder, then special fractional processing when discounting, ***but ONLY when applying a PERCENTAGE***!!!                    
                  break; 
            }
            
            //****************************
            //modify $vtprd_rules_set[$i]->discount_product_short_msg if wildcard found!!
            //****************************
            // BUT allow for both curly brackets {show_discount_val} and without
            if (strpos($vtprd_rules_set[$i]->discount_product_short_msg,'show_discount_val') !== false) {
              //format discount
              switch ( $vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_type'] ) { 
                  case ('percent'):
                        $discount_value = $vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_value'] . '%';
                     break;   
                  case ('currency'):
                        $currency_symbol = get_woocommerce_currency_symbol();
                        $discount_value = $currency_symbol . $vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_value'];
                      break;
                  case ('fixedPrice'):
                        $currency_symbol = get_woocommerce_currency_symbol();
                        $msg = __('Fixed Price of ', 'vtprd');
                        $discount_value = $msg . $currency_symbol . $vtprd_rules_set[$i]->bulk_deal_array[$b]['discount_value'];  
                     break;                                        
              }
              
              $discount_product_msg = $vtprd_rules_set[$i]->discount_product_short_msg;
              
              if (strpos($vtprd_rules_set[$i]->discount_product_short_msg,'show_discount_val_more') !== false) {
                
                $discount_value .= $vtprd_rules_set[$i]->bulk_deal_processing_array['msg_suffix'];

                if (strpos($vtprd_rules_set[$i]->discount_product_short_msg,'{show_discount_val_more}') !== false) {
                  $vtprd_rules_set[$i]->discount_product_short_msg = str_replace( '{show_discount_val_more}', $discount_value, $discount_product_msg );
                } else {
                  $vtprd_rules_set[$i]->discount_product_short_msg = str_replace( 'show_discount_val_more', $discount_value, $discount_product_msg );              
                }
  
              } else {
                // allow for either with/without curly brackets
                
                if (strpos($vtprd_rules_set[$i]->discount_product_short_msg,'{show_discount_val}') !== false) {
                  $vtprd_rules_set[$i]->discount_product_short_msg = str_replace( '{show_discount_val}', $discount_value, $discount_product_msg );
                } else {
                  $vtprd_rules_set[$i]->discount_product_short_msg = str_replace( 'show_discount_val', $discount_value, $discount_product_msg );              
                }              
              }
              

            }
                                 
            /*
            
            
            ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'all') ) {
            
            FOR BULK
            set exploded action END in HOLDING FIELD
            If we already know it here, set it here
            else 
            set it as we go - 
                  if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'quantity') || ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'currency') ) {
      
                  where "$vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt Qty test completed';" is set
                  
            when processing last discount and using currency, apply FRACTIONAL discount as needed      
                  
                  
            Fill in everything needed for the Exploder, as that runs before NOW      
                  
            */ 
              
        } //end of for loop       
        //****************
        //v1.1.8.0  End
        //****************

        // ends with sizeof being reached, OR  $vtprd_rules_set[$i]->discount_processing_status == 'yes'
        $sizeof_rule_deal_info = sizeof($vtprd_rules_set[$i]->rule_deal_info);
        for($d=0; $d < $sizeof_rule_deal_info; $d++) {
          switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_repeat_condition'] ) {
              case 'none':     //only applies to 1st rule deal line
                   /* 
                   There can be multiple conditions which are covered by inserting a repeat count = 1.
                   Most often, the rule applies to the entire actionPop.  If that is the case, the 
                   actionPop Loop will run through the whole actionPop in one go, to process all of the 
                   discounts.  This is a hack, as it really should be governed here.                 
                   */
                  $buy_repeat_count = 1;
                break;
              case 'unlimited':   //only applies to 1st rule deal line
                  $buy_repeat_count = 99999999;
                break;
              case 'count':     //can only occur when there's only one rule deal line
                  $buy_repeat_count = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_repeat_count'];
                break;  
        }        

        //REPEAT count only augments IF a discount successfully processes...
        for($br=0; $br < $buy_repeat_count; $br++) {
           $this->vtprd_repeating_group_discount_cntl($i, $d, $br );             
           
           if ($vtprd_rules_set[$i]->discount_processing_status != 'inProcess') { 
             break; // exit repeat for loop
           }                     
        } // $buy_repeat_count for loop        
    
        //v1.0.4 begin => lifetime counted by group (= 'all') up count here, once per rule/cart
        //EDITED * + * +  * + * +  * + * +  * + * +
        //v1.0.4 end 
                  
      }  //rule_deal_info for loop
       
      
     /*  THIS IS ONLY NECESSARY IN WPEC, NOT WOO,
         as in woo the adds haven't happened yet - nothing to roll out in this situation...
      
      //***********************************************************
      // If a product was auto inserted for a free discount, but does *not*
      //     receive that discount,
      //   Roll the auto-added product 'UNfree' qty out of the all of the rules actionPop array
      //      AND out of vtprd_cart, removing the product entirely if necessary.
      //***********************************************************
      if ( ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') &&
           (sizeof($vtprd_rules_set[$i]->auto_add_inserted_array) > 0) )  {        
        $this->vtprd_maybe_roll_out_auto_inserted_products($i); 
      }     
      
      */
      
      //***********************************************************
      // If a product has been given a 'Free' discount, it can't get
      //     any further discounts.
      //   Roll the product 'free' qty out of the rest of the rules actionPop array
      //***********************************************************
      if (sizeof($vtprd_rules_set[$i]->free_product_array) > 0) {
        $this->vtprd_roll_free_products_out_of_other_rules($i); 
      }
      
      //v1.0.8.4 begin
      //  used in following rule processing iterations, if cumulativeRulePricing == 'no'
      //v1.0.9.3 added if isset
      if ( (isset ($vtprd_info['applied_value_of_discount_applies_to']) ) &&
         ( ($vtprd_info['applied_value_of_discount_applies_to']  == 'cheapest') ||
           ($vtprd_info['applied_value_of_discount_applies_to']  == 'most_expensive') ||
           ($vtprd_info['applied_value_of_discount_applies_to']  == 'all') ) ) {
         $this->vtprd_mark_products_in_an_all_rule($i);
      } 
   
      //v1.0.8.4 begin  

      //v1.1.0.8 begin
      //only activate if coupon presented
      // coupon_activated_discount for later use in parent-cart-validation during remove_coupon, as needed
      if ( ($vtprd_rules_set[$i]->only_for_this_coupon_name > ' ')  &&
           ($vtprd_rules_set[$i]->discount_total_amt_for_rule > 0) ) {
        /* v1.1.7.2 grpD moved above
        if(!isset($_SESSION)){
            session_start();
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
        }
        */
          $_SESSION['coupon_activated_discount'] = true;
      }
      //v1.1.0.8 end
         
    }  //ruleset for loop
    return;    
  }

  //***********************************************************
  //v1.1.8.0  New Function
  //***********************************************************
  public function vtprd_bulk_find_currency_actionpop_end($i, $b, $sizeof_actionPop_exploded_found_list) {
		global $woocommerce, $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule;
       //error_log( print_r(  'function $vtprd_bulk_find_currency_actionpop_end  $i= ' .$i. ' $b= ' .$b. ' $sizeof_actionPop_exploded_found_list= ' .$sizeof_actionPop_exploded_found_list , true ) );

      //***********************
      //roll through actionpop array until total >= found bulk max
      // find actual actionpop end WHERE running total just greater than max, 
      // then use the occurrence as the actionpop count.  note the remainder for use on the last itertation during processing.
      //***********************
      //find alast product and fraction of the last unit to have a discount.                      
      $actionPop_exploded_group_end = 0;
      $actionPop_exploded_currency_total = 0;
      $d = 0;
      for($z=0; $z < $sizeof_actionPop_exploded_found_list; $z++) {
        $actionPop_exploded_group_end++;  
        $actionPop_exploded_currency_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$z]['prod_unit_price'];
        if ($actionPop_exploded_currency_total >= $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value']) {  //MAX is already found above...               
        
            $currency_symbol = get_woocommerce_currency_symbol();
            if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to']  == 'each') {
              $msg_suffix = __(' across each purchase up to ', 'vtprd') .$currency_symbol. $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'];
            } else {
              $msg_suffix = __(' across all purchases up to ', 'vtprd') .$currency_symbol. $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'];
            } 
                                 
          $vtprd_rules_set[$i]->bulk_deal_processing_array = array (
            'actionPop_exploded_group_end'      =>  $actionPop_exploded_group_end,
            'currency_last_iteration_remainder' =>  ( $actionPop_exploded_currency_total - $vtprd_rules_set[$i]->bulk_deal_array[$b]['max_value'] ), 
            'prod_id' =>  $vtprd_rules_set[$i]->actionPop_exploded_found_list[$z]['prod_id'] , 
            'orig_prod_unit_price' => $vtprd_rules_set[$i]->actionPop_exploded_found_list[$z]['prod_unit_price'],
            'bulk_array_occurrence' =>  $b,
            'msg_suffix' =>  $msg_suffix                        
          ); 
         
          //+-+-+-+-+-
          // IF bulk, currency discount and there's a remainder, subtract the remainder from the LAST actionpop unit price!!
          //last doesnt work, let's try 1st!! doesn't matter which one, really...
          //$vtprd_rules_set[$i]->actionPop_exploded_found_list[$z]['prod_unit_price'] -= 
          /*
                $vtprd_rules_set[$i]->actionPop_exploded_found_list[0]['prod_unit_price'] -=
                $vtprd_rules_set[$i]->bulk_deal_processing_array['currency_last_iteration_remainder']; 
           */ 
            
                $vtprd_rules_set[$i]->actionPop_exploded_found_list[$z]['prod_unit_price'] -= 
                $vtprd_rules_set[$i]->bulk_deal_processing_array['currency_last_iteration_remainder'];
                                                          
          //+-+-+-+-+-
                                             
          break;  //found the correct end, exit for loop!
        }
      }
     
      return $actionPop_exploded_group_end;    
  }
  
  
  
  //$i = rule index, $d = deal index, $br = repeat index
  //***********************************************************
  // Take a Single BUY group all the way through the discount process,
  //     Performed by  REPEAT NUM  within DEAL LINE within RULE
  //***********************************************************
  public function vtprd_repeating_group_discount_cntl($i, $d, $br) {
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework, $vtprd_deal_structure_framework;        

     //error_log( print_r(  'Top Of vtprd_repeating_group_discount_cntl, $br= ' . $br, true ) ); 
            
    //initialize rule_processing_trail(
    $vtprd_rules_set[$i]->rule_processing_trail[] = $vtprd_deal_structure_framework;
    $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupBeingTested';

    // previously determined template key
    $templateKey = $vtprd_rules_set[$i]->rule_template; 
   
    //if buy_amt_type is active and there is a buy_amt count...
    //***********************************************************
    //THIS SETS THE SIZE OF THE BUY exploded GROUP "WINDOW"
    //***********************************************************
    // Initialize the amt qty as needed
    if ($vtprd_template_structures_framework[$templateKey]['buy_amt_type'] > ' ' ) { 
      if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'none' ) ||  
           ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'one' ) ) {
         $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] = 'quantity';
         $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] = 1;
         if ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']  <= ' ') {
           $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']  = 'all';
         }
      }
    } else {
       $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] = 'quantity';
       $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] = 1;
       $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']  = 'all';
    }    
    
    // INPOP_EXPLODED_GROUP_BEGIN setup
    if ($br == 0) { //is this the 1st time through the buy repeat?  
      $vtprd_rules_set[$i]->inPop_exploded_group_begin = 0;
    } else {    //if 2nd-nth time      
       switch ($vtprd_rules_set[$i]->discountAppliesWhere) {
        case 'allActionPop':        //process all actionPop in one go , 'allActionPop'
        case 'inCurrentInPopOnly':  //treats inpop group as a unit, so we get the next inpop group unit                    
        case 'nextInActionPop':     //FOR all 3 values, add 1 to end            
            $vtprd_rules_set[$i]->inPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end;// + 1;
          break;
        case 'nextInInPop':   //we're bouncing between inpop and actionpop, so use actionPop end + 1 here:
            $vtprd_rules_set[$i]->inPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;// + 1;
          break;     
      }
 
    }

    //*************************************************************
    //1st pass through data, set the begin/end pointers, 
    // verify 'buy' conditions met
    //*************************************************************
    $this->vtprd_set_buy_group_end($i, $d, $br );     //vtprd_buy_amt_process   
    
    //if buy amt process failed, exit
    if ($vtprd_rules_set[$i]->rule_processing_status == 'cartGroupFailedTest') {
      //if buy criteria not met, discount processing for rule is done
      $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
      return;
    } 

    //***************
    //ACTION area
    //***************
    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_condition'] ) {
      case 'none':     //only one rule deal line
          $action_repeat_count = 1;
        break;
      case 'unlimited':   //only one rule deal line
          $action_repeat_count = 99999999;
        break;
      case 'count':     //only one rule deal line
          $action_repeat_count = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_repeat_count'];
        break;  
    } 
    
    for($ar=0; $ar < $action_repeat_count; $ar++) {
       $this->vtprd_process_actionPop_and_discount($i, $d, $br, $ar );                 
       if ($vtprd_rules_set[$i]->discount_processing_status != 'inProcess')  {         
         break; //break out of  for loop
       }                             
    } // end $action_repeat_count for loop  
                                                                
  }
 
  public function vtprd_process_actionPop_and_discount($i, $d, $br, $ar ) {      
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;        
     //error_log( print_r(  ' ', true ) ); 
     //error_log( print_r(  'vtprd_process_actionPop_and_discount  $i= ' .$i. ' $d= ' .$d.  ' $br= ' .$br. ' $ar= ' .$ar, true ) ); 

                         
    //v1.1.0.6 begin
    //If 2nd to nth repeat where INPOP has been blessed, then add more free candidates!
    if ( ($br > 0) ||
         ($ar > 0) ) {
     
      //2nd => nth
      if ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') {    
        $this->vtprd_add_free_item_candidate($i); //v1.1.0.6 if $br or $ar > 0, add to exploded!
      }
    }
    //v1.1.0.6 end
    
    $templateKey = $vtprd_rules_set[$i]->rule_template;

    if ($vtprd_template_structures_framework[$templateKey]['action_amt_type'] > ' ' ) { 
      if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'none' ) ||  
          ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'one' ) ) {
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] = 1;
        if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] <= ' ') {
           $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']  = 'all';
        }
      }
    } else {
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] = 'quantity';
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] = 1;
        $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']  = 'all';
    }

   //ACTIONPOP_EXPLODED_GROUP BEGN AND END  SETUP
   switch( $vtprd_rules_set[$i]->discountAppliesWhere  ) {     // 'allActionPop' / 'inCurrentInPopOnly'  / 'nextInInPop' / 'nextInActionPop' / 'inActionPop' /
      case 'allActionPop':
          //process all actionPop in one go
          $vtprd_rules_set[$i]->actionPop_exploded_group_begin = 0;
          $vtprd_rules_set[$i]->actionPop_exploded_group_end   = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
        break;
      case 'inCurrentInPopOnly':
         //v1.0.8.1 begin  -  refactored
          /*
          if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'zero' ) {  //means we are acting on the already-found 'buy' unit
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end - 1;   //end - 1 gets the nth, as well as the direct hit...       
          } else {          
            //always the same as inPop pointers
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_begin;
          }
        //$vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;   //v1.0.3 
         $vtprd_rules_set[$i]->actionPop_exploded_group_end   = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);    //v1.0.3
          */

          if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'zero' ) {  //means we are acting on the already-found 'buy' unit
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end - 1;   //end - 1 gets the nth, as well as the direct hit...
            $vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;
          } else {
            if ($ar > 0) { //if 2nd - nth actionPop repeat, use the previous actionPop group end to begin the next group
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;
            } else {
              //always the same as inPop pointers at beginning
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_begin;
            } 
   
            //SETS action amt "window" for the actionPop_exploded_group
            $this->vtprd_set_action_group_end($i, $d, $ar );  //vtprd_action_amt_process 
          }
          //v1.0.8.1 end              
        
        break;  
      case 'nextInInPop': 
         
          if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'zero' ) {  //means we are acting on the already-found 'buy' unit
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end - 1;   //end - 1 gets the nth, as well as the direct hit...
            $vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;
          } else {
            if ($ar > 0) { //if 2nd - nth actionPop repeat, use the previous actionPop group end to begin the next group
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;
            } else {
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end;// + 1;
            } 
   
            //SETS action amt "window" for the actionPop_exploded_group
            $this->vtprd_set_action_group_end($i, $d, $ar );  //vtprd_action_amt_process 
          }
           
          
          //v1.0.8.7 begin
          // capture overflow...  >= since we're comparing occurrence with size
          $sizeOf_actionPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
          if ($vtprd_rules_set[$i]->actionPop_exploded_group_begin >= $sizeOf_actionPop_exploded_found_list ) {
             $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';  
             $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Gone beyond available actionpop data[1] (actionpop begin > size of actionpop list)'; //v2.0.0 E solution          
             break;
          }
      
          //v1.0.8.7 end
         
          
        break;  
      case 'nextInActionPop':   
         
          //first time actionPop_exploded_group_end arrives here = 0...
          if (($br > 0) ||    //if 2nd to nth buy repeat or actionpop repeat, , use the previous actionPop group end to begin the next group
              ($ar > 0)) { 
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end;// + 1;
          } 

        
          //v1.1.8.1 begin
          //issue with 1 in the cart....
          if (($br == 0) &&    
              ($ar == 0) &&
              ($vtprd_rules_set[$i]->inPop_exploded_group_end == 1) &&
              ($vtprd_rules_set[$i]->inPop_exploded_found_list[0] == $vtprd_rules_set[$i]->actionPop_exploded_found_list[0]) ) {              
            $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_end;
          }
          $sizeOf_actionPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
          if ($vtprd_rules_set[$i]->actionPop_exploded_group_begin >= $sizeOf_actionPop_exploded_found_list ) {
             $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
             $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Gone beyond available actionpop data[2] (actionpop begin > size of actionpop list)'; //v2.0.0 E solution
             break;
          }
           //v1.1.8.1 end
                    
          //SETS action amt "window" for the actionPop_exploded_group
          $this->vtprd_set_action_group_end($i, $d, $ar );  //vtprd_action_amt_process  
        break;   
    } 
    
    //only possible if  vtprd_set_action_group_end  executed
    if ($vtprd_rules_set[$i]->rule_processing_status == 'cartGroupFailedTest') {
      //THIS PATH can either end processing for the rule, or just this iteration of actionPop processing, based on settings in set_action_group...         
      $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
      return;
    }         

    //************************************************
    //************************************************
    //     PROCESS DISCOUNTS                             
    //************************************************
    //************************************************
    /*
     Do we treat the actionPop as a group or as individuals ?
        Requires group analysis:
          *least expensive
          *most expensive
          *forThePriceOf units
          *forThePriceOf currency        
        Can be applied to the group or individually (each/all)
          *currency discount
          *percentage discount
        Can only be applied to individual products
          *free
          *fixed price                        
    */
    
    switch( true ) {
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Units') :
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Currency') :
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'cheapest') :    //can only be 'each'
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'most_expensive') :   //can only be 'each'
        //v1.0.7.2 begin
          //reset the action group pointers to be = to buy group pointers, so actionpop doesn't count whole group at once...  so whatever group count the buy group as set, we do here.
          if ($vtprd_rules_set[$i]->discountAppliesWhere == 'inCurrentInPopOnly') {  //'inCurrentInPopOnly' = 'discount this one'
              $vtprd_rules_set[$i]->actionPop_exploded_group_begin = $vtprd_rules_set[$i]->inPop_exploded_group_begin; 
              $vtprd_rules_set[$i]->actionPop_exploded_group_end   = $vtprd_rules_set[$i]->inPop_exploded_group_end;
          }
          $this->vtprd_apply_discount_as_a_group($i, $d, $ar );       
        break;
        //v1.0.7.2 end  
              
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'all') && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'currency') ):
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'all') && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'percent') ):  //v1.0.7.4   floating point fix...
          $this->vtprd_apply_discount_as_a_group($i, $d, $ar );       
        break;
      
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'free')       && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):   //can only be 'each'
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'fixedPrice') && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):   //can only be 'each'  
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'currency')   && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):
      case ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'percent')    && ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'each') ):    //v1.0.7.4
          $this->vtprd_apply_discount_to_each_product($i, $d, $ar );       
        break;
    } 

    $vtprd_info['applied_value_of_discount_applies_to']  =  $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'];   //v1.0.8.4  store value for processing

    $sizeof_actionpop_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list); //v 1.0.3
    
    //v1.1.0.6  begin
    // revamped the fallthrough logic to handle 'infitely repeatable'
    if ( ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') &&
         ($vtprd_rules_set[$i]->actionPop_exploded_group_end >= $sizeof_actionpop_list) )  {
      //emulate having the future additonal free item which will be added next iteration, if $br or $ar have "room" left...
      $sizeof_actionpop_list++;
    }
    //v1.1.0.6 END       
    
    if ( ($vtprd_rules_set[$i]->actionPop_exploded_group_end >= $sizeof_actionpop_list ) || 
         ($ar >= ($sizeof_actionpop_list) ) ||  //v1.0.3 exit if infinite repeat  
         ($vtprd_rules_set[$i]->end_of_actionPop_reached == 'yes') ) { 
       $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
    } else {
      switch ($vtprd_rules_set[$i]->discountAppliesWhere)  {
        case 'allActionPop':
           $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd'; //all done - process all actionPop in one go 
          break;
        case 'inCurrentInPopOnly':              
        case 'nextInInPop':       
        case 'nextInActionPop':
            $vtprd_rules_set[$i]->actionPop_repeat_activity_completed = 'yes';  //action completed, then allow the repeat to control the discount action
          break;          
      }    
    }
             
  } // end  vtprd_process_actionPop_and_discount

 
  public function vtprd_apply_discount_to_each_product($i, $d, $ar ) {  
     global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;        
      
    //error_log( print_r(  'vtprd_apply_discount_to_each_product $i= ' .$i. ' $d= ' .$d. ' $ar= ' .$ar, true ) ); 
 
     //if we're doing action nth processing, only the LAST product in the list gets the discount.
     if ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'nthQuantity') {
       $each_product_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_end - 1;
     } else {
       $each_product_group_begin = $vtprd_rules_set[$i]->actionPop_exploded_group_begin;
     }
          
     for( $s=$each_product_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
        $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $this->vtprd_compute_each_discount($i, $d, $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price']);      
        $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
        $curr_prod_array['exploded_group_occurrence'] = $s; 
        $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);           
        
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Single product discount (possibly) applied for $i= ' .$i. ' $d= ' .$d. ' $ar= ' .$ar;
        //just in case...
        if ($s >= sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list)) {
          $vtprd_rules_set[$i]->discount_processing_status = 'InPopEnd';
          return;
        }  
     } 
    //at this point we may have processed all of actionPop in one go, so we set the end switch
     
     return; 
  }
 
  public function vtprd_apply_discount_as_a_group($i, $d, $ar ) {   
     global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework; 
      
    //error_log( print_r(  'vtprd_apply_discount_as_a_group Begin $i= ' .$i. ' $d= ' .$d. ' $ar= ' .$ar, true ) ); 
             
    $prod_discount = 0;    
    switch( true ) {
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Units') :
         // buy 5 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] ) 
         // get 5   ( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']; )
         // FOR THE PRICE OF           
         // 4 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_for_the_price_of_count'] )
         
         //add unit prices together
         $cart_group_total_price = 0;
         for ( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $cart_group_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
         }      
       if ($vtprd_rules_set[$i]->rule_template == 'C-forThePriceOf-inCart') {  //buy-x-action-forThePriceOf-same-group-discount
           $forThePriceOf_Divisor = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'];
        } else {
           $forThePriceOf_Divisor = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'];
        }

        //divide by total by number of units = average price
        $cart_group_avg_price = $cart_group_total_price / $forThePriceOf_Divisor;

        //multiply average price * # of forthepriceof units = new group price
        $new_total_price = $cart_group_avg_price * $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];

        $total_savings = $cart_group_total_price - $new_total_price;

        //per unit savings = new total / group unit count => by Buy group or Action Group
        //$per_unit_savings = $total_savings / $forThePriceOf_Divisor;

        //compute remainder
        //$per_unit_savings_2decimals = bcdiv($total_savings , $forThePriceOf_Divisor , 2);
        $per_unit_savings_2decimals = round( ($total_savings / $forThePriceOf_Divisor) , 2); 
            
        $running_total =  $per_unit_savings_2decimals * $forThePriceOf_Divisor;

        //$remainder = $total_savings - $running_total;
        $remainder = round($total_savings - $running_total, 2); //v1.0.7.4   floating point...
        
        if ($remainder <> 0) {      //v1.0.5.1 changed > 0 to <>0 ==>> pick up positive or negative rounding error
          $add_a_penny_to_first = $remainder;
        } else {
          $add_a_penny_to_first = 0;
        }

       
        //apply the per unit savings to each unit       
        for ( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $per_unit_savings_2decimals;
            
            //if first occurrence, add in penny if remainder calc produced one
            if ($s == $vtprd_rules_set[$i]->actionPop_exploded_group_begin) {
               $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] += $add_a_penny_to_first;
            }
            
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         } 

        break;
      
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Currency') :

         // buy 5 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] ) 
         // get 5   ( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']; )
         // FOR THE PRICE OF           
         // 4 ( $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_for_the_price_of_count'] )
         
         //**********************
         /* v2.0.0 RECODED
         //**********************
             DEAL is actually - buy X number for a total fixed price of $$Y
             
             This routine not working when discount applies across multiple products with different prices.
             forThePriceOf_Currency:
             future unit price = discount_amt_count (total new $$ for the group) / 
                                  $forThePriceOf_Divisor (number of units this new group price applies to)
         */
         
         //**********************
         /* v2.0.0.2 BEGIN -Completely RECODED
         //**********************
             # units in the deal = $forThePriceOf_Divisor
             New deal total = $cart_group_new_fixed_price
             Old deal total = $cart_group_old_fixed_price
             
             Total Group Discount =  $cart_group_old_fixed_price -  $cart_group_new_fixed_price
             Avg Unit Price discount =   Total Group Discount /  # units in the deal
                   
             1. loop through the Action group, total up the Old deal total.             
             2. Compute Total Group Discount  and  Avg Unit Price discount
             3. SORT the Action Group, low to high unit cost
             4. Loop through Action Group
                A. if Old Unit Price >= Avg Unit Price discount,
                    apply discount to get New Unit Price and go to next unit Action Group (if not the last one)
                B. If Old Unit Price < Avg Unit Price discount
                    1. Unit Discount Remainder =  Avg Unit Price discount - Old Unit Price
                    2. zero out the New Unit Price
                    3. Compute new value for Avg Unit Price discount:
                        a. Additional discount per unit = Unit Discount Remainder / # of units remaining in discount group
                        b. Avg Unit Price discount += Additional discount per unit.
                C. If LAST unit ($discounted_units_running_count == # units in the deal)
                    1. Compute  Total Discount Remainder =  $cart_group_new_fixed_price - $discounted_units_running_total
                    2. Subtract  Total Discount Remainder from FINAL unit price.                        
         */ 
             
         $cart_group_old_fixed_price = 0;
         $cheapest_array = array();
         $forThePriceOf_Divisor = 0; 
         // loop through the Action group, total up the Old deal total.  
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
            $cheapest_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $cart_group_old_fixed_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
            $forThePriceOf_Divisor++;           
         }
         //http://stackoverflow.com/questions/7839198/array-multisort-with-natural-sort
         //http://isambard.com.au/blog/2009/07/03/sorting-a-php-multi-column-array/
         //sort group by prod_unit_price (relative column3), cheapest 1stt
         $prod_unit_price = array();
         foreach ($cheapest_array as $key => $row) {
            $prod_unit_price[$key] = $row['prod_unit_price'];
         } 
         //SORT the Action Group, low to high unit cost
         array_multisort($prod_unit_price, SORT_ASC, SORT_NUMERIC, $cheapest_array);

        $cart_group_new_fixed_price = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];

        $total_group_discount = $cart_group_old_fixed_price - $cart_group_new_fixed_price; 

        //compute remainder
        //$per_unit_savings_2decimals = bcdiv($total_savings , $forThePriceOf_Divisor , 2);
        //$per_unit_discount_2decimals = round( ($total_group_discount / $forThePriceOf_Divisor) , 2); 
        $per_unit_discount_2decimals = $total_group_discount / $forThePriceOf_Divisor; 

        $discounted_units_running_count = 0; 
        $discounted_units_running_total = 0;

        //apply the per unit savings to each unit              
        foreach ($cheapest_array as $key => $cheapest_array_row) {            
            $discounted_units_running_count++;
            
            $unused_unit_discount = 0;
            
            if ($cheapest_array_row['prod_unit_price'] >= $per_unit_discount_2decimals) {
              $cheapest_array_row['prod_discount_amt'] = $per_unit_discount_2decimals;
                           
            } else {              
              $unused_unit_discount = $per_unit_discount_2decimals - $cheapest_array_row['prod_unit_price'];
              $cheapest_array_row['prod_discount_amt'] = $cheapest_array_row['prod_unit_price'];
              
              //distribute uniused discount into $per_unit_discount_2decimals
              $discountable_units_remaining = $forThePriceOf_Divisor - $discounted_units_running_count;
              if ($discountable_units_remaining > 0) {
                $per_unit_unused_unit_discount = round( ($unused_unit_discount / $discountable_units_remaining) , 2);
                $per_unit_discount_2decimals += $per_unit_unused_unit_discount;                
              }               
            }

            //round AFTER computation
            $cheapest_array_row['prod_discount_amt']  = round( (0 + $cheapest_array_row['prod_discount_amt']) , 2); 

            $discounted_units_running_total += ($cheapest_array_row['prod_unit_price'] - $cheapest_array_row['prod_discount_amt']);
            
            //add remainder to last in group
            if ($discounted_units_running_count == $forThePriceOf_Divisor) { //if we're on the last one in the group      
               switch(TRUE) {
                //if positive remainder, add remainder
                case ($cart_group_new_fixed_price < $discounted_units_running_total):  //Not Enough discount already applied
                    $remainder = $discounted_units_running_total - $cart_group_new_fixed_price;
                    if ($remainder > '.005') {
                      $cheapest_array_row['prod_discount_amt'] += $remainder;
                    }              
                  break;
                //if negative remainder, subtract remainder
                case ($cart_group_new_fixed_price > $discounted_units_running_total):    //TOO much discount already applied
                    $remainder = $cart_group_new_fixed_price - $discounted_units_running_total;
                    if ($remainder > '.005') {                    
                      $cheapest_array_row['prod_discount_amt'] -= $remainder;
                    }               
                  break; 
                default:
                    //all done!
                  break;             
               }
            }

            $curr_prod_array = $cheapest_array_row;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         } 

        //v2.0.0.2 end
      
        break;
                
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'cheapest') :
         $cheapest_array = array();
         //create candidate array
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
            $cheapest_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];           
         }
         //http://stackoverflow.com/questions/7839198/array-multisort-with-natural-sort
         //http://isambard.com.au/blog/2009/07/03/sorting-a-php-multi-column-array/
         //sort group by prod_unit_price (relative column3), cheapest 1st
         $prod_unit_price = array();
         foreach ($cheapest_array as $key => $row) {
            $prod_unit_price[$key] = $row['prod_unit_price'];
         } 
         array_multisort($prod_unit_price, SORT_ASC, SORT_NUMERIC, $cheapest_array);
         
         //apply discount        
         $curr_prod_array = $cheapest_array[0];
         $curr_prod_array['prod_discount_amt'] = $this->vtprd_compute_each_discount($i, $d, $cheapest_array[0]['prod_unit_price'] );
         $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
 
        break;
      
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] == 'most_expensive') :
         $mostExpensive_array = array();
         
         //create candidate array
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence'] = $s;
            $mostExpensive_array [] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
         }
         
         //sort group by prod_unit_price , most expensive 1st
         $prod_unit_price = array();
         foreach ($mostExpensive_array as $key => $row) {
            $prod_unit_price[$key] = $row['prod_unit_price'];
         } 
         array_multisort($prod_unit_price, SORT_DESC, SORT_NUMERIC, $mostExpensive_array);
         
         //apply discount
         $curr_prod_array = $mostExpensive_array[0];
         $curr_prod_array['prod_discount_amt'] = $this->vtprd_compute_each_discount($i, $d, $mostExpensive_array[0]['prod_unit_price'] );
         $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         
        break;
        
      //$$ value off of a group
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'currency') :  //only 'ALL'
         
         //add unit prices together
         $cart_group_total_price = 0;
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $cart_group_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
         }      
        $unit_count = $vtprd_rules_set[$i]->actionPop_exploded_group_end - $vtprd_rules_set[$i]->actionPop_exploded_group_begin;
       
        //per unit savings = new total / group unit count
        

        //compute remainder
        //$per_unit_savings_2decimals = bcdiv($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] , $unit_count , 2);
        $per_unit_savings_2decimals = round( ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] / $unit_count ) , 2);     
     
        $running_total =  $per_unit_savings_2decimals * $unit_count;

        //$remainder = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] - $running_total;
        $remainder = round($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] - $running_total, 2);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
        
        //if ($remainder > 0) {
        if ($remainder != 0) {      //v1.0.8.1  allow for negative remainder!
          $add_a_penny_to_first = $remainder;
        } else {
          $add_a_penny_to_first = 0;
        }
    
        //apply the per unit savings to each unit
        for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $per_unit_savings_2decimals;
            
            //if first occurrence, add in penny if remainder calc produced one
            if ($s == $vtprd_rules_set[$i]->actionPop_exploded_group_begin) {
               $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] += $add_a_penny_to_first;
            }
                      
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);
         } 

        break;
         
      //v1.0.7.4 begin
      //*******************************************
      //% value off of a group
      //  added to handle a price decimal ending in 5, which otherwise will produce a rounding-based error.
      //  rounding errors are now handled within each product sub-group, so that the fix will be reflected in the appropriate bucket.
      //*******************************************
      //--------------------------------------------
      //v1.0.7.6 entire case structure reworked
      //--------------------------------------------
      case ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'percent') :     
         //Applying a % discount to a group is often different from applying it singly, due to rounding issues.  This routine repairs that
         //  by comparing the total of the individually discounted items against the discount total of the same group, and adding any remainder to the last item discounted

         //*******************************************
         //add unit prices together, per product (so any group-level remainder goes with the correct product!)
         //******************************************* 
         
         $cart_group_total = array();
  
         $s = $vtprd_rules_set[$i]->actionPop_exploded_group_begin;
         $current_product_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'];
         
         $current_unit_count = 0;
         $current_total_price = 0;
         $current_unit_price = 0; 
         $grand_total_exploded_group = 0;
         $running_grand_total = 0;
     
         //pre-process action group for remainder info
         for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) {
            
            if ($current_product_id == $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'])  {
               
               //add to current totals
               $current_unit_count++;
               $current_total_price += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
               $current_unit_price = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
          
            } else {
               //insert the totals of the previous product id     
               $cart_group_total[] = array(
                   'product_id'  => $current_product_id,
                   'unit_count'  => $current_unit_count,
                   'unit_price'  => $current_unit_price,
                   'total_price' => $current_total_price,
                   'total_discount' => 0,
                   'total_remainder' => 0,
                   'product_discount' => 0,
                   'product_discount_remainder' => 0  
                    ); 
               
               //initialize the current group     
               $current_product_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'];
               $current_unit_count = 1;
               $current_total_price = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'];
               $current_unit_price = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price']; 
                                                     
            }

            //handle last in list
            if (  ($s + 1) == $vtprd_rules_set[$i]->actionPop_exploded_group_end ) {
               $cart_group_total[] = array(
                   'product_id'  => $current_product_id,
                   'unit_count'  => $current_unit_count,
                   'unit_price'  => $current_unit_price,
                   'total_price' => $current_total_price,
                   'total_discount' => 0,
                   'total_remainder' => 0,
                   'product_discount' => 0,
                   'product_discount_remainder' => 0   
                    );            
            }

            $grand_total_exploded_group += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price']; 
            
           
         }

         $percent_off = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] / 100;

         //*******************
         //compute group discounts, by product
         //*******************
         $sizeof_cart_group_total = sizeof ($cart_group_total);
         
         for( $c=0; $c < $sizeof_cart_group_total; $c++) {
            //****************************************************
            //Get the total discount amount for the whole group, used to calculate final remainder later
            //****************************************************
        //  $discount_applied_to_total = bcmul($cart_group_total[$c]['total_price'], $percent_off , 2);
            $discount_applied_to_total = round($cart_group_total[$c]['total_price'] * $percent_off , 2); //v1.0.7.6

            $cart_group_total[$c]['total_discount']  =  $discount_applied_to_total; 
            $cart_group_total[$c]['total_remainder'] =  0;
            
            
            //****************************************************
            //Get the Unit Price discount
            //****************************************************            
        //  $discounted_per_unit = (bcmul($cart_group_total[$c]['unit_price'], $percent_off , 2));
            $discounted_per_unit = (round($cart_group_total[$c]['unit_price'] * $percent_off , 2));  //v1.0.7.6
            
            $discount_applied_to_unit_times_count = $discounted_per_unit * $cart_group_total[$c]['unit_count'];
            
            $unit_price_discount_difference = $discount_applied_to_total - $discount_applied_to_unit_times_count;

            $discounted_per_unit_round = (round ($cart_group_total[$c]['unit_price'] * $percent_off , 2));


            $cart_group_total[$c]['product_discount'] = $discounted_per_unit;
            $cart_group_total[$c]['product_discount_remainder'] = $unit_price_discount_difference;
            
            //keep track of grand total
          //  $running_grand_total += ($temp_product_total_discount + $unit_price_discount_difference);           
             
         }
         
         //See if there is remainder AFTER all of the product groups are computed
       //$grand_total_exploded_group_discount = bcmul($grand_total_exploded_group, $percent_off , 2); 
         $grand_total_exploded_group_discount = round($grand_total_exploded_group * $percent_off , 2);
         $grand_total_remainder = round($grand_total_exploded_group_discount - $running_grand_total, 2);

        $current_product_id = '';
        $current_unit_count = 0;
        $current_total_discount = 0;
        
        //*******************  
        //apply discount to each item - add in **group** remainder to last
        //*******************
        for( $s=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $s < $vtprd_rules_set[$i]->actionPop_exploded_group_end; $s++) { 

            //*******************
            // track unit count for this product
            //*******************  
            if ($current_product_id == $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'])  {
              $current_unit_count++; 
            } else {
              $current_product_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_id'];
              $current_unit_count     = 1;
            }   
            
            //*******************
            // add in group remainder, as needed
            //*******************                      
            for( $c=0; $c < $sizeof_cart_group_total; $c++) {               
               
               if ($cart_group_total[$c]['product_id']  ==  $current_product_id ) {
                  
                  $this_prod_discount_amt = $cart_group_total[$c]['product_discount'];
                  
                  if ($cart_group_total[$c]['unit_count']  ==  $current_unit_count ){  //are we on last unit in product group?                  
                      $this_prod_discount_amt += $cart_group_total[$c]['product_discount_remainder']; 
                      /*  only do this by product!!
                      //if we're on the last product, last unit - add in grand_total_remainder
                      if ( ($c + 1) == $sizeof_cart_group_total) {
                         $this_prod_discount_amt += $grand_total_remainder;
                      }
                     */                   
                  }

                  $c = $sizeof_cart_group_total; //exit stage left
               }
            }
            
            //*******************
            // update discount
            //*******************                
            $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_discount_amt'] = $this_prod_discount_amt;
            $curr_prod_array = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s];
            $curr_prod_array['exploded_group_occurrence'] = $s;
            $this->vtprd_upd_cart_discount($i, $d, $curr_prod_array);                 
        }

      break;
      //v1.0.7.4 end
        
    }
    
    return;           
  }
 
 /*  --------------------------
 This routine creates a single exploded product's discount.  It also checks that discount against
 individual limits.  It also checks if this exploded product discount 
 exceeds the product's cumulative quantity discount.
    -------------------------- */
  public function vtprd_upd_cart_discount($i, $d, $curr_prod_array) {   
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;  
      
     //error_log( print_r(  'vtprd_upd_cart_discount AT TOP, $i= ' .$i. ' $d= ' .$d, true ) ); 
     //error_log( print_r(  '$curr_prod_array AT TOP= ', true ) );
     //error_log( var_export($curr_prod_array, true ) );
 
    $k = $curr_prod_array['prod_id_cart_occurrence'];
    $rule_id = $vtprd_rules_set[$i]->post_id; 
    
    $product_id = $vtprd_cart->cart_items[$k]->product_id; //v1.1.1.2 
    
    $cart_id = $_SESSION['vtprd_unique_cart_id']; //v2.0.2.0

    if ($curr_prod_array['prod_discount_amt'] == 0) {
      //v1.1.0.6 begin -  don't skip this if there is a zero priced product on an auto insert rule...
            
      //------------------------------------
      //v2.0.2.0 begin - code reworked
      //$current_auto_add_array = $this->vtprd_get_current_auto_add_array();        
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0016 ', true ) );
      $get_current_auto_add_array = vtprd_get_transient_cart_data ('current_auto_add_array', $cart_id);
      if ($get_current_auto_add_array)  {
          $current_auto_add_array = unserialize($get_current_auto_add_array);
      } else {
          $current_auto_add_array = array();
      }        
      //v2.0.2.0 end
        
      if ( ($vtprd_info['current_processing_request']  ==  'cart') &&
           ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product  ==  'yes') &&
           //v1.1.1.2 begin
           //($vtprd_cart->cart_items[$k]->product_id  ==  $current_auto_add_array['free_product_id']) &&
           ($vtprd_cart->cart_items[$k]->product_auto_insert_rule_id  ==  $vtprd_rules_set[$i]->post_id) &&
           (isset ($current_auto_add_array[$product_id]) ) &&
           //v1.1.1.2 end
           //v2.0.0 g solution begin
           // if all items are free, unit price is zero!
           //($vtprd_cart->cart_items[$k]->unit_price  ==  0) ) {
           ($vtprd_cart->cart_items[$k]->quantity  == $current_auto_add_array[$free_product_id]['free_qty']) ) {          
           //v2.0.0 g solution end
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'Zero price auto add product';
        $vtprd_cart->cart_items[$k]->zero_price_auto_add_free_item = 'yes'; //MARK FOR PRINTING FUNCTIONS
        $vtprd_cart->cart_has_zero_price_auto_add_free_item = 'yes'; //MARK FOR PRINTING FUNCTIONS
      } else {
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No discount for this rule';
        return;
      }
      //v1.1.0.6 end
    }
      
    //just in case discount for this rule already applied to this product iteration....
    //mark exploded list product as already processed for this rule
    $occurrence = $curr_prod_array['exploded_group_occurrence'];       
    if (($curr_prod_array['prod_discount_applied'] == 'yes') ||
        ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$occurrence]['prod_discount_applied'] == 'yes')) {
      $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'Discount already applied, can"t reapply';
      //exit stage left, can't apply discount for same rule to same product....
      return;
    }
 

    //*********************************************************************
    //CHECK THE MANY DIFFERENT MAX LIMITS BEFORE UPDATING THE DISCOUNT TO THE ARRAY
    //********************************************************************* 
 
    //v1.0.8.4 begin
    if ( ($vtprd_rules_set[$i]->cumulativeRulePricing == 'no') &&
         ($vtprd_cart->cart_items[$k]->product_already_in_an_all_rule == 'yes') ) {
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - counted as part of an "all" rule group from previous discount, no more allowed';
        return;     
    }
    //v1.0.8.4 begin

    //v1.1.7.2 begin                                
    $additional_include_criteria = apply_filters('vtprd_cumulativeRulePricing_custom_criteria',TRUE,$i, $k );
    if ($additional_include_criteria !== TRUE) {
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - vtprd_cumulativeRulePricing_custom_criteria override ';
       return;
    }
    //v1.1.7.2 end
      
    if ( isset( $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id] ) ) {
      if ( (sizeof ($vtprd_cart->cart_items[$k]->yousave_by_rule_info) > 1 ) &&   //only 1 allowed in this case...
           ($vtprd_rules_set[$i]->cumulativeRulePricing == 'no') ) {
         //1 discount rule already applied discount, no more allowed;
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - 1 discount rule already applied discount, no more allowed';
         return;  
      }
      if ( $vtprd_setup_options['discount_floor_pct_per_single_item'] > 0 ) {
        if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   != 'free') {
           //v2.0.0.2 begin
           if (($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Units') ||
               ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']   == 'forThePriceOf_Currency')) {
             $no_floor_percent_applies = true;  
           } else {           
             if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_pct'] >= $vtprd_setup_options['discount_floor_pct_per_single_item']) ||
                  ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {
                //yousave percent max alread reached in a previous discount!!!!!!  Do Nothing
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount floor percentage max reached, ' .$vtprd_setup_options['discount_floor_pct_msg']; //floor percentage maxed out;            
                return;
             }
           }
           //v2.0.0.2 end           
        }
      } 
  
      switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_type'] ) {
        case 'none':
            $do_nothing;
          break;
        case 'percent':
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_pct'] >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Max Percent Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;
        case 'quantity':       
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['discount_applies_to_qty'] >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Max Qty Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;        
        case 'currency': 
           if ( ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_amt'] >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) ||
                ($vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] > ' ') ) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Max $$ Value Previously Reached.'; //floor percentage maxed out;                      
              return;
            }      
          break;
      }
      
       
      switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_type'] ) {
        case 'none':
            $do_nothing;
          break;
        case 'percent':
           if ( $vtprd_rules_set[$i]->discount_total_pct >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Cumulative Max Percent Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;
        case 'quantity':  
          //if ( $vtprd_rules_set[$i]->discount_total_qty >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {  //v1.1.6.8
           if ( $vtprd_rules_set[$i]->discount_total_qty_for_rule >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {    //v1.1.6.8        
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Cumulative Max Qty Previously Reached.'; //floor percentage maxed out;                      
              return;
            }
          break;        
        case 'currency':    
           //if ( $vtprd_rules_set[$i]->discount_total_amt >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count'])  {  //v1.1.6.8
           if ( $vtprd_rules_set[$i]->discount_total_amt_for_rule >= $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count'])  {  //v1.1.6.8          
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Rule Cumulative Max $$ Value Previously Reached.'; //floor percentage maxed out;                      
              return;
            }      
          break;
      }      
 
      $yousave_for_this_rule_id_already_exists = 'yes';

    } else {      
      if ( (sizeof($vtprd_cart->cart_items[$k]->yousave_by_rule_info) > 0 ) &&
           ($vtprd_rules_set[$i]->cumulativeRulePricing == 'no') ) {
         //1 discount rule already applied discount, no more allowed
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount for this another rule already applied to this Product, multiple rule discounts not allowed.';         
         return;  
      }
      
      $yousave_for_this_rule_id_already_exists = 'no';
      
    }
    
    
    //*****************************************
    //find current product's yousave percent, altered as needed below
    //*****************************************
    
    if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']  == 'percent') {
      $yousave_pct = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count']; 
    } else {
      //compute yousave_pct_at_upd_begin
      $computed_pct =  $curr_prod_array['prod_discount_amt'] /  $curr_prod_array['prod_unit_price'] ;
      //$computed_pct_2decimals = bcdiv($curr_prod_array['prod_discount_amt'] , $curr_prod_array['prod_unit_price'] , 2);
      $computed_pct_2decimals = round( ($curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'] ) , 2);
                
      //$remainder = $computed_pct - $computed_pct_2decimals;
      $remainder = round($computed_pct - $computed_pct_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                  
      if ($remainder > 0.005) {
        //v1.0.7.4 begin
        $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
        if ($increment < .01) {
          $increment = .01;
        }
        ////v1.0.7.4 end
        $yousave_pct = ($computed_pct_2decimals + $increment) * 100;  //v1.0.7.4   floating point
      } else {
        $yousave_pct = $computed_pct_2decimals * 100;
      }
    }

 
    $max_msg = '';
    $discount_status = '';
    
    //compute current discount_totals for limits testing
    $discount_total_qty_for_rule = $vtprd_rules_set[$i]->discount_total_qty_for_rule + 1;
    $discount_total_amt_for_rule = $vtprd_rules_set[$i]->discount_total_amt_for_rule + $curr_prod_array['prod_discount_amt'] ;
    //$discount_total_unit_price_for_rule will be the unit qty * db_unit_price already, as this routine is done 1 by 1...
    $discount_total_unit_price_for_rule =  $vtprd_rules_set[$i]->discount_total_unit_price_for_rule + $curr_prod_array['prod_unit_price'] ;
    //yousave pct whole number  = total discount amount / (orig unit price * number of units discounted)
    $discount_total_pct_for_rule = ($discount_total_amt_for_rule / $discount_total_unit_price_for_rule) * 100 ;  //in round #s
     
    //adjust yousave_amt and yousave_pct as needed based on limits
    switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_type']  ) {  //var on the 1st iteration only
      case 'none':
          $do_nothing;
        break;
      case 'percent':           
          if ($discount_total_pct_for_rule > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) {
            
             // % = floor minus rule % totaled in previous iteration
            $yousave_pct = $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count'] - $vtprd_rules_set[$i]->discount_total_pct_for_rule; 
            
            //*********************************************************************
            //reduce discount amount to max allowed by rule percentage
            //*********************************************************************
          //$discount_2decimals = bcmul(($yousave_pct / 100) , $curr_prod_array['prod_unit_price'] , 2);
            $discount_2decimals = round(($yousave_pct / 100) * $curr_prod_array['prod_unit_price'] , 2); //v1.0.7.6
          
            //compute rounding
            $temp_discount = ($yousave_pct / 100) * $curr_prod_array['prod_unit_price'];
         
            //$rounding = $temp_discount - $discount_2decimals;
            $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                     
            if ($rounding > 0.005) {
              //v1.0.7.4 begin
              $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end              
              $discount = $discount_2decimals + $increment;   //v1.0.7.4  floating point
            }  else {
              $discount = $discount_2decimals;
            } 
                     
            $curr_prod_array['prod_discount_amt']  = $discount;
            $max_msg = $vtprd_rules_set[$i]->discount_rule_max_amt_msg;
 
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 
            'Discount reduced due to max rule percent overrun.';         
                        
          }
 
        break;      
      case 'quantity':
          if ($discount_total_qty_for_rule > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) {
             //we've reached the max allowed by this rule, as we only process 1 at a time, exit
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount Rule Max Qty already reached, discount skipped';               
             return;
          }
        break;
      case 'currency':
          if ($discount_total_amt_for_rule > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count']) {
            //reduce discount to max...
            $reduction_amt = $discount_total_amt_for_rule - $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count'];

            $curr_prod_array['prod_discount_amt']  = $curr_prod_array['prod_discount_amt'] - $reduction_amt;
            
            //v1.0.9.3 begin
            if ($curr_prod_array['prod_discount_amt'] > $curr_prod_array['prod_unit_price']) {
              $curr_prod_array['prod_discount_amt'] = $curr_prod_array['prod_unit_price'];
            }
            //v1.0.9.3 end
            
            $max_msg = $vtprd_rules_set[$i]->discount_rule_max_amt_msg;
             
            $yousave_pct_temp = $curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'];
            
            // $yousave_pct = $yousave_amt / $curr_prod_array['prod_unit_price'] * 100;        
            //compute remainder
            //$yousave_pct_2decimals = bcdiv($curr_prod_array['prod_discount_amt'] , $curr_prod_array['prod_unit_price'] , 2);
            
            $yousave_pct_2decimals = round( ($curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'] ) , 2);
                              
          //$remainder = $yousave_pct_temp - $yousave_pct_2decimals;
            $remainder = round($yousave_pct_temp - $yousave_pct_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                        
            if ($remainder > 0.005) {
              //v1.0.7.4 begin
              $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end              
              $yousave_pct = ($yousave_pct_2decimals + $increment) * 100;  //v1.0.7.4  PHP floating point 
            } else {
              $yousave_pct = $yousave_pct_2decimals * 100;
            }
          }
        break;
    }

    //Test yousave for product across All Rules applied to the Product
    $yousave_product_total_amt = $vtprd_cart->cart_items[$k]->yousave_total_amt +  $curr_prod_array['prod_discount_amt'] ;
    $yousave_product_total_qty = $vtprd_cart->cart_items[$k]->yousave_total_qty + 1;
    //  yousave_total_unit_price is a rolling full total of unit price already
    $yousave_total_unit_price = $vtprd_cart->cart_items[$k]->yousave_total_unit_price + $curr_prod_array['prod_unit_price'];  
    //yousave pct whole number = (total discount amount / (orig unit price * number of units discounted))
    $yousave_pct_prod_temp = $yousave_product_total_amt / $yousave_total_unit_price;
    //$yousave_pct_prod_2decimals = bcdiv($yousave_product_total_amt , $yousave_total_unit_price , 2);
    $yousave_pct_prod_2decimals = round( ($yousave_product_total_amt / $yousave_total_unit_price ) , 2);
       
  //$remainder = $yousave_pct_prod_temp - $yousave_pct_prod_2decimals;
    $remainder = round($yousave_pct_prod_temp - $yousave_pct_prod_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                    
    if ($remainder > 0.005) {
      //v1.0.7.4 begin
      $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
      if ($increment < .01) {
        $increment = .01;
      }
      ////v1.0.7.4 end
      $yousave_product_total_pct = ($yousave_pct_prod_2decimals + $increment) * 100;   //v1.0.7.4  PHP floating point 
    } else {
      $yousave_product_total_pct = $yousave_pct_prod_2decimals * 100;
    }
    $refigure_yousave_product_totals = 'no';

    //if amts have been massaged, recheck vs discount_floor_percentage
    if ($max_msg > ' ') {
      if ( $vtprd_setup_options['discount_floor_pct_per_single_item'] > 0 ) {

        if ( $yousave_product_total_pct > $vtprd_setup_options['discount_floor_pct_per_single_item']) {
          //reduce discount amount to max allowed by max floor discount percentage
          //    compute the allowed remainder percentage
          // % = floor minus product % totaled *before now*
          $yousave_pct = $vtprd_setup_options['discount_floor_pct_per_single_item'] - $vtprd_cart->cart_items[$k]->yousave_total_pct;
          
          $percent_off = $yousave_pct / 100;         
          //compute rounding
        //$discount_2decimals = bcmul($curr_prod_array['prod_unit_price'] , $percent_off , 2);
          $discount_2decimals = round($curr_prod_array['prod_unit_price'] * $percent_off , 2);  //v1.0.7.6
          
          $temp_discount = $curr_prod_array['prod_unit_price'] * $percent_off;
          
          //$rounding = $temp_discount - $discount_2decimals;
          $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                             
          if ($rounding > 0.005) {
            //v1.0.7.4 begin
            $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
            if ($increment < .01) {
              $increment = .01;
            }
            ////v1.0.7.4 end            
            $curr_prod_array['prod_discount_amt'] = $discount_2decimals + $increment;   //v1.0.7.4  PHP floating point
          }  else {
            $curr_prod_array['prod_discount_amt'] = $discount_2decimals;
          }                   
          $refigure_yousave_product_totals = 'yes';
          //$curr_prod_array['prod_discount_amt']  = ($yousave_pct / 100) * $curr_prod_array['prod_unit_price'];
        } 
      }         
    }
    
        
    //adjust yousave_amt and yousave_pct as needed based on limits
    switch( $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_type']  ) {  //var on the 1st iteration only
      case 'none':
          $do_nothing;
        break;
      case 'percent':           
          if ($yousave_product_total_pct > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {
            
             // % = floor minus rule % totaled *before now*
            $yousave_pct = $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_max_amt_count'] - $vtprd_cart->cart_items[$k]->yousave_total_pct;
            
            //*********************************************************************
            //reduce discount amount to max allowed by rule percentage
            //*********************************************************************
          //$discount_2decimals = bcmul(($yousave_pct / 100) , $curr_prod_array['prod_unit_price'] , 2);
            $discount_2decimals = round(($yousave_pct / 100) * $curr_prod_array['prod_unit_price'] , 2); //v1.0.7.6
         
            //compute rounding
            $temp_discount = ($yousave_pct / 100) * $curr_prod_array['prod_unit_price'];

          //$rounding = $temp_discount - $discount_2decimals;
            $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
         
            if ($rounding > 0.005) {
               //v1.0.7.4 begin
              $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end             
              $discount = $discount_2decimals + $increment;    //v1.0.7.4  PHP floating point 
            }  else {
              $discount = $discount_2decimals;
            } 
          } 
 
        break;       
      case 'quantity':
          if ($yousave_product_total_qty > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {
             //we've reached the max allowed by this rule, as we only process 1 at a time, exit
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = 'No Discount - Discount Rule Max Qty already reached, discount skipped';               
             return;
          }
        break;
      case 'currency':
          if ($yousave_product_total_amt > $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count']) {
            //reduce discount to max...
            $reduction_amt = $yousave_product_total_amt - $vtprd_rules_set[$i]->rule_deal_info[0]['discount_rule_cum_max_amt_count'];

            $curr_prod_array['prod_discount_amt']  = $curr_prod_array['prod_discount_amt'] - $reduction_amt;
            
            //v1.0.9.3 begin
            if ($curr_prod_array['prod_discount_amt'] > $curr_prod_array['prod_unit_price']) {
              $curr_prod_array['prod_discount_amt'] = $curr_prod_array['prod_unit_price'];
            }
            //v1.0.9.3 end
                        
            $max_msg = $vtprd_rules_set[$i]->discount_rule_cum_max_amt_msg;
             
            $yousave_pct_temp = $curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'];
            
            // $yousave_pct = $yousave_amt / $curr_prod_array['prod_unit_price'] * 100;        
            //compute remainder
            //$yousave_pct_2decimals = bcdiv($curr_prod_array['prod_discount_amt'] , $curr_prod_array['prod_unit_price'] , 2);
            $yousave_pct_2decimals = round( ($curr_prod_array['prod_discount_amt'] / $curr_prod_array['prod_unit_price'] ) , 2);
                           
          //$remainder = $yousave_pct_temp - $yousave_pct_2decimals;
            $remainder = round($yousave_pct_temp - $yousave_pct_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
             
            if ($remainder > 0.005) {
              //v1.0.7.4 begin
              $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
              if ($increment < .01) {
                $increment = .01;
              }
              ////v1.0.7.4 end
              $yousave_pct = ($yousave_pct_2decimals + $increment) * 100;     //v1.0.7.4  PHP floating point 
            } else {
              $yousave_pct = $yousave_pct_2decimals * 100;
            }
            $refigure_yousave_product_totals = 'yes';
          }
        break;
    }

    //*************************************
    // PURCHASE HISTORY LIFETIME LIMIT
    //*************************************   
        
    //EDITED * + * +  * + * +  * + * +  * + * +
    
    //EXIT if Sale Price already lower than Discount
    if ( ($vtprd_cart->cart_items[$k]->product_is_on_special == 'yes') &&
         ($vtprd_rules_set[$i]->cumulativeSalePricing == 'replaceSalePrice' ) )  {
      //Replacement of Sale Price is requested, but only happens if Discount is GREATER THAN sale price
      $discounted_price = ($curr_prod_array['prod_unit_price'] - $curr_prod_array['prod_discount_amt'] ) ;
      If ($vtprd_cart->cart_items[$k]->db_unit_price_special < $discounted_price ) {
        $vtprd_cart->cart_items[$k]->unit_price     = $vtprd_cart->cart_items[$k]->db_unit_price_special;
        $vtprd_cart->cart_items[$k]->db_unit_price  = $vtprd_cart->cart_items[$k]->db_unit_price_special;
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'No Discount - Sale Price Less than Discounted price';
        return;      
      }
   }
  
    //*********************************************************************
    //eND MAX LIMITS CHECKING
    //*********************************************************************
           
      
    //*************************************
    // Add Discount Totals into the Array
    //*************************************       
    if ($yousave_for_this_rule_id_already_exists == 'yes') { 
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['yousave_amt']     +=  $curr_prod_array['prod_discount_amt'] ;
    //cumulative percentage

       //v1.1.8.1 begin
       //$vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['discount_applies_to_qty']++; 
       //  don't count this row if it's a currency exploder.  only the original row gets counted.
       //  (only filled on the 2nd-nth row, not the original)
       if (!$curr_prod_array['this_is_a_currency_exploder_row']) {
          $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['discount_applies_to_qty']++;
       } 
       //v1.1.8.1 end
       
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['rule_max_amt_msg'] =  $max_msg;
    }  else {
       $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id] =  array(
           'ruleset_occurrence'    => $i, 
           'discount_amt_type'   => '',
           'discount_amt_count'   => 0,
           'discount_for_the_price_of_count'  => '', 
           'discount_applies_to_qty'  => 1,         
           'yousave_amt'       => $curr_prod_array['prod_discount_amt'] ,
           'yousave_pct'       => $yousave_pct ,
           'rule_max_amt_msg'  => $max_msg,
           'rule_execution_type' =>  $vtprd_rules_set[$i]->rule_execution_type, //used when sending purchase EMAIL!!       
           'rule_short_msg'    => $vtprd_rules_set[$i]->discount_product_short_msg,
           'rule_full_msg'     => $vtprd_rules_set[$i]->discount_product_full_msg,
           
           //v1.1.8.0 begin used for BULK reporting, when only SOME of a product get discounted
           'bulk_fully_discounted_product_count'        => 0, //how many fully discounted
           'bulk_partial_discount_product_value'        => 0, //how much of the $$ value of the unit was discounted
           'bulk_partial_discount_product_yousave_amt'  => 0,  //$$ value of the discount itself
           'yousave_amt_taxed'                          => 0 
           //v1.1.8.0 end
           
           //used at cart discount display time => if coupon used, does this discount apply?
           //  ---> pick this up directly from the ruleset occurrence at application time
           //'cumulativeCouponPricingAllowed' => $vtprd_rules_set[$i]->cumulativeCouponPricingAllowed  
          );
        
        //******************************************
        //for later ajaxVariations pricing    - BEGIN
        //******************************************        
        if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type'] == 'percent') {
          $pricing_rule_percent_discount  = $yousave_pct;
          $pricing_rule_currency_discount = 0;
        } else {
          $pricing_rule_percent_discount  = 0;
          $pricing_rule_currency_discount = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];        
        }
        $vtprd_cart->cart_items[$k]->pricing_by_rule_array[] =  array(  
            'pricing_rule_id' => $rule_id, 
            'pricing_rule_applies_to_variations_array' => $vtprd_rules_set[$i]->var_in_checked , //' ' or var list array
            'pricing_rule_percent_discount'  => $pricing_rule_percent_discount,
            'pricing_rule_currency_discount' => $pricing_rule_currency_discount 
          );
        //  ajaxVariations pricing - END
           
    }
    //recompute the discount totals for use in next iteration
    $vtprd_rules_set[$i]->discount_total_qty_for_rule = $vtprd_rules_set[$i]->discount_total_qty_for_rule + 1;
    $vtprd_rules_set[$i]->discount_total_amt_for_rule = $vtprd_rules_set[$i]->discount_total_amt_for_rule + $curr_prod_array['prod_discount_amt'] ;
    //$discount_total_unit_price_for_rule will be the unit qty * db_unit_price already, as this routine is done 1 by 1...
    $vtprd_rules_set[$i]->discount_total_unit_price_for_rule =  $vtprd_rules_set[$i]->discount_total_unit_price_for_rule + $curr_prod_array['prod_db_unit_price'] ;
    //yousave pct whole number  = total discount amount / (orig unit price * number of units discounted)
    $vtprd_rules_set[$i]->discount_total_pct_for_rule = ($discount_total_amt_for_rule / $discount_total_unit_price_for_rule) * 100 ;

    //REFIGURE the product totals, if there was a reduction above...
    if ($refigure_yousave_product_totals == 'yes') {
      $yousave_product_total_amt = $vtprd_cart->cart_items[$k]->yousave_total_amt +  $curr_prod_array['prod_discount_amt'] ;
      $yousave_product_total_qty = $vtprd_cart->cart_items[$k]->yousave_total_qty + 1;
      //  yousave_total_unit_price is a rolling full total of unit price already
      $yousave_total_unit_price = $vtprd_cart->cart_items[$k]->yousave_total_unit_price + $curr_prod_array['prod_unit_price'];  
      //yousave pct whole number = (total discount amount / (orig unit price * number of units discounted))
      $yousave_pct_prod_temp = $yousave_product_total_amt / $yousave_total_unit_price;
      //$yousave_pct_prod_2decimals = bcdiv($yousave_product_total_amt , $yousave_total_unit_price , 2);
      $yousave_pct_prod_2decimals = round( ($yousave_product_total_amt / $yousave_total_unit_price ) , 2);  
         
      //$remainder = $yousave_pct_prod_temp - $yousave_pct_prod_2decimals;
      $remainder = round($yousave_pct_prod_temp - $yousave_pct_prod_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!

      if ($remainder > 0.005) {
        //v1.0.7.4 begin
        $increment = round($remainder, 2); //round the rounding error to 2 decimal points!  floating point
        if ($increment < .01) {
          $increment = .01;
        }
        //v1.0.7.4 end     
        $yousave_product_total_pct = ($yousave_pct_prod_2decimals + $increment) * 100;  //v1.0.7.4   floating point
      } else {
        $yousave_product_total_pct = $yousave_pct_prod_2decimals * 100;
      } 
    }      
    $vtprd_cart->cart_items[$k]->yousave_total_amt = $yousave_product_total_amt; 
    $vtprd_cart->cart_items[$k]->yousave_total_qty = $yousave_product_total_qty; 
    $vtprd_cart->cart_items[$k]->yousave_total_pct = $yousave_product_total_pct ;
    $vtprd_cart->cart_items[$k]->yousave_total_unit_price = $yousave_total_unit_price;
    
    //keep track of historical discount totals 
     //instead of $yousave_product_total_qty;, we're actually counting home many times the RULE was used, not the total qty it was applied to... 
    
    //v1.0.4 begin => lifetime counted by group (= 'all') added only after group processing for rule is complete
    if ($vtprd_rules_set[$i]->rule_deal_info[$d]['discount_applies_to'] != 'all') {
      $vtprd_rules_set[$i]->purch_hist_rule_row_qty_total_plus_discounts    +=  1; // +1 for each RULE OCCURRENCE usage...
    }
    //v1.0.4 end    
        
    $vtprd_rules_set[$i]->purch_hist_rule_row_price_total_plus_discounts  +=  $curr_prod_array['prod_discount_amt'];
    
    //used in lifetime limits
    $vtprd_rules_set[$i]->actionPop_rule_yousave_amt  +=  $curr_prod_array['prod_discount_amt'];
    $vtprd_rules_set[$i]->actionPop_rule_yousave_qty  +=  1;  //$yousave_product_total_qty;  not qty, but iterations of USAGE!


      //**************
      //v1.1.8.0 Begin
      /*
       IF bulk, currency discount and there's a **remainder**, LAST actionpop item has had its unit price overwritten, possibly.
       if so, restore the original value **BEFORE** the next computation
      */

    /*

     $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id] =  array(
         'ruleset_occurrence'    => $i, 
         'discount_amt_type'   => '',
         'discount_amt_count'   => 0,
         'discount_for_the_price_of_count'  => '', 
         'discount_applies_to_qty'  => 1,         
         'yousave_amt'       => $curr_prod_array['prod_discount_amt'] ,
         'yousave_pct'       => $yousave_pct ,
         'rule_max_amt_msg'  => $max_msg,
         'rule_execution_type' =>  $vtprd_rules_set[$i]->rule_execution_type, //used when sending purchase EMAIL!!       
         'rule_short_msg'    => $vtprd_rules_set[$i]->discount_product_short_msg,
         'rule_full_msg'     => $vtprd_rules_set[$i]->discount_product_full_msg,
         
         //v1.1.8.0 begin used for BULK reporting, when only SOME of a product get discounted
         'bulk_fully_discounted_product_count'   => '', //how many fully discounted
         'bulk_partial_discount_product_value'   => '', //how much of the $$ value of the unit was discounted
         'bulk_partial_discount_product_yousave_amt'   => '' //$$ value of the discount itself
         //v1.1.8.0 end
         
         //used at cart discount display time => if coupon used, does this discount apply?
         //  ---> pick this up directly from the ruleset occurrence at application time
         //'cumulativeCouponPricingAllowed' => $vtprd_rules_set[$i]->cumulativeCouponPricingAllowed  
        );
        
        ---------------------------
        New reporting functions.
        ---------------------------
        I. order history - always produce discount report in order history
        Add a WooCommerce custom order action · GitHub
        https://gist.github.com/bekarice/5233ed58c3a836064123b290463241c0
        https://stackoverflow.com/questions/37772912/woocommerce-add-custom-metabox-to-admin-order-page
        - using existing custom history to do this will make this work RETROactively!!
        
            ADD as Meta Box:
            in: woocommerce\includes\admin\meta-boxes\views\class-wc-meta-box-order-actions.php 
            do_action( 'woocommerce_order_action_' . sanitize_title( $action ), $order );
            
            Add at end of Line Items, before totals:
            in: woocommerce\includes\admin\meta-boxes\views\html-order-items.php
            do_action( 'woocommerce_admin_order_items_after_line_items', $order->get_id() );
            
            
            IF currency limits, then this is the only report available for clarity sake...  
            
              [ Report and each column can be shut off via filter ]
              
              Report columns: Item / Price (orig crossed out, discounted) / discount amt per unit / Quantity Discounted  / product discount total
              
              If partial discount, PRICE shows the Partial discount, qty discounted = 1 / product discount total = partial 
        
        II. if partial discount given for a unit purchase
        1. print a line with full discount reporting - product, orig price, #units purchased fully, disc/unit, total discount
        2. print line with partial discount reporting, same as above
        3. below partial line, explain why partial unit discount.
        
        III. Full reporting on customer email, thankyou page.
        
        IV. Better control of full reporting in both coupon and unit pricing modes.
        
        V. rethink.  If currency and partial discount, always have full reporting on email, thankyou and order history.
          a. if partial report columns are:
            1. item name / unit list price(crossout)/$ value discounted/unit / discount units / subtotal discounted / total discount amount
          b. if NOT partial report columns are:
            2. item name / unit list price / discount units / subtotal discounted / total discount amount
            - alway show a total line - total $$ value discounted, total $$discount
        
         
        ---------------------------
        Document all HOOKS !!!!!!!!
        ---------------------------              
*/
                    
      if ( ($vtprd_rules_set[$i]->pricing_type_select == 'bulk') && 
           ($vtprd_rules_set[$i]->bulk_deal_method    == 'currency') &&
           ($vtprd_rules_set[$i]->bulk_deal_processing_array['currency_last_iteration_remainder'] > 0) &&
           ($vtprd_rules_set[$i]->bulk_deal_processing_array['prod_id'] == $curr_prod_array['prod_id'])  &&
           ($vtprd_rules_set[$i]->bulk_deal_processing_array['orig_prod_unit_price'] != $curr_prod_array['prod_unit_price']) ) {  //this last one tells us we're on the PARTIAL!            

        //for report tracking 
            /*
             'bulk_fully_discounted_product_count'   => '', //how many fully discounted
             'bulk_partial_discount_product_value'   => '', //how much of the $$ value of the unit was discounted
             'bulk_partial_discount_product_yousave_amt'   => '' //$$ value of the discount itself
            */
        $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['bulk_fully_discounted_product_count'] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence']; //occurrence = count - 1, so correct 
        $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['bulk_partial_discount_product_value'] = $curr_prod_array['prod_unit_price'];
        $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['bulk_partial_discount_product_yousave_amt'] = $curr_prod_array['prod_discount_amt'];

        //*************************************************
        //processing - overwrite the PARTIAL unit price with the original full unit price, for later processing
        //*************************************************
        $curr_prod_array['prod_unit_price'] = $vtprd_rules_set[$i]->bulk_deal_processing_array['orig_prod_unit_price'];
        $s = ($vtprd_rules_set[$i]->actionPop_exploded_group_end - 1);          
        $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'] = $vtprd_rules_set[$i]->bulk_deal_processing_array['orig_prod_unit_price'];

      }
    
      //v1.1.8.0 end                             
      //************** 

      //**************
      //v1.1.8.1 begin 
      // reset to the original price if its a 'currency exploder' row (NOT BULK)                       

      if ( ($vtprd_rules_set[$i]->rule_execution_type == 'cart') &&
           ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'currency' ) &&
           ($curr_prod_array['prod_unit_price_hold'] > 0) &&  // *** prod_unit_price_hold only available in actionpop, and set when only there is 'currency exploder' processing ***
           ($curr_prod_array['prod_unit_price_hold'] != $curr_prod_array['prod_unit_price']) ) {  //prod_unit_price_hold only set                 
        $s = ($vtprd_rules_set[$i]->actionPop_exploded_group_end - 1);
        $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['currency_fully_discounted_product_count'] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['exploded_group_occurrence']; //occurrence = count - 1, so correct 
        $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['currency_partial_discount_product_value'] = $curr_prod_array['prod_unit_price'];
        $vtprd_cart->cart_items[$k]->yousave_by_rule_info[$rule_id]['currency_partial_discount_product_yousave_amt'] = $curr_prod_array['prod_discount_amt'];
        //*************************************************
        //processing - overwrite the PARTIAL unit price with the original full unit price, for later processing
        //*************************************************
        $curr_prod_array['prod_unit_price'] = $curr_prod_array['prod_unit_price_hold'];
       // $s = ($vtprd_rules_set[$i]->actionPop_exploded_group_end - 1);          
        $vtprd_rules_set[$i]->actionPop_exploded_found_list[$s]['prod_unit_price'] = $curr_prod_array['prod_unit_price_hold'];                          
      }
      //v1.1.8.1 end                             
      //**************
      
      
    //$vtprd_cart->cart_items[$k]->discount_price    = ($vtprd_cart->cart_items[$k]->db_unit_price * $vtprd_cart->cart_items[$k]->quantity) - $yousave_product_total_amt ;  
    $vtprd_cart->cart_items[$k]->discount_price    = ( $curr_prod_array['prod_unit_price'] * $vtprd_cart->cart_items[$k]->quantity) - $yousave_product_total_amt ; 
    
    //v1.1.1 begin
    if ($vtprd_cart->cart_items[$k]->discount_price > 0) {
      $vtprd_cart->cart_items[$k]->discount_unit_price  =  round( $vtprd_cart->cart_items[$k]->discount_price / $vtprd_cart->cart_items[$k]->quantity , 2); 
    } else {
      $vtprd_cart->cart_items[$k]->discount_unit_price  =  '';    
    }  
    //v1.1.1 end
        
    $vtprd_rules_set[$i]->discount_applied == 'yes';
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'applied';
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] = __('Discount Applied', 'vtprd');
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_amt'] = $curr_prod_array['prod_discount_amt'];
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_pct'] = $yousave_pct;
    
    //                         *******************
    //if discount has applied, update rule totals after recalc to pick up most current total price info 
    //                         *******************  
    
    
    //add in total saved to yousave_total_amt for PRODUCT
   
    if ($curr_prod_array['prod_discount_amt'] > 0) {  

      //v1.0.5.3 begin 
      //  the vtprd_cart unit price and discounts all reflect the TAX STATE of 'woocommerce_prices_include_tax'      
		   global $woocommerce;
       $product_id = $vtprd_cart->cart_items[$k]->product_id;
       switch( true ) {
          case ( get_option( 'woocommerce_calc_taxes' ) == 'no' ):
          //v1.1.7.2 begin
          case ( ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) && (is_object($woocommerce->customer)) && (! empty( $woocommerce->customer ) )  &&  ( $woocommerce->customer->get_is_vat_exempt() ) ): 
          case ( ( version_compare( WC_VERSION, '3.0.0', '<'  ) ) && (is_object($woocommerce->customer)) && (! empty( $woocommerce->customer ) )  &&  ( $woocommerce->customer->is_vat_exempt() ) ): 
          //case ( $woocommerce->customer->is_vat_exempt() ): 
          //v1.1.7.2 end  
             $prod_discount_amt_excl_tax  =  $curr_prod_array['prod_discount_amt'];
             $prod_discount_amt_incl_tax  =  $curr_prod_array['prod_discount_amt'];
            break; 
          case ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ): 
             $prod_discount_amt_excl_tax  =  vtprd_get_price_excluding_tax($product_id, $curr_prod_array['prod_discount_amt']);
             $prod_discount_amt_incl_tax  =  $curr_prod_array['prod_discount_amt'];
            break; 
          case ( get_option( 'woocommerce_prices_include_tax' ) == 'no' ): 
             $prod_discount_amt_excl_tax  =  $curr_prod_array['prod_discount_amt'];
             $prod_discount_amt_incl_tax  =  vtprd_get_price_including_tax($product_id, $curr_prod_array['prod_discount_amt']);
            break;              
		   }
       //THIS is where the cart SAVE TOTALS are stored!!             
       $vtprd_cart->yousave_cart_total_amt_excl_tax      += $prod_discount_amt_excl_tax;
       $vtprd_cart->yousave_cart_total_amt_incl_tax      += $prod_discount_amt_incl_tax;
        //v1.0.5.3 end    
         
                                
      //v1.1.6.8 begin
      //$vtprd_rules_set[$i]->discount_total_qty += 1;     
      //$vtprd_rules_set[$i]->discount_total_amt += $curr_prod_array['prod_discount_amt'];
      $vtprd_rules_set[$i]->discount_total_qty_for_rule += 1;     
      $vtprd_rules_set[$i]->discount_total_amt_for_rule += $curr_prod_array['prod_discount_amt'];
      //v1.1.6.8 end
      $vtprd_cart->yousave_cart_total_qty      += 1;
      $vtprd_cart->yousave_cart_total_amt      += $curr_prod_array['prod_discount_amt'];        
    }    

    //mark exploded list product as already processed for this rule
    $vtprd_rules_set[$i]->actionPop_exploded_found_list[$occurrence]['prod_discount_applied'] = 'yes';


    //**********************************************
    //  if this product is free, add the product qty to the tracking bucket
    //**********************************************    
    if ($curr_prod_array['prod_discount_amt'] == $vtprd_cart->cart_items[$k]->unit_price) {  
      $key =  $vtprd_cart->cart_items[$k]->product_id;
      if (isset($vtprd_rules_set[$i]->free_product_array[$key])) {
         $vtprd_rules_set[$i]->free_product_array[$key]++;
      } else {
         $vtprd_rules_set[$i]->free_product_array[$key] = 1;
      }
    }
    
    //********************
    //v1.1.1.2 begin - reworked for multiple free 
    //v1.1.0.6 begin
    //  SINGLE UNIT - mark free candidate as free, increment/decrement counters
    
    //------------------------------------
    //v2.0.2.0 begin - code reworked    
    //$current_auto_add_array = $this->vtprd_get_current_auto_add_array();    
    
    //error_log( print_r(  'before vtprd_get_transient_cart_data  0017 ', true ) );
    $get_current_auto_add_array = vtprd_get_transient_cart_data ('current_auto_add_array',$cart_id);
    if ($get_current_auto_add_array)  {
        $current_auto_add_array = unserialize($get_current_auto_add_array);
    } else {
        $current_auto_add_array = array();
    }    
    //v2.0.2.0 end 
    
    $free_product_id = $vtprd_cart->cart_items[$k]->product_id; //v1.1.1.2 

    if ( ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') &&
         ($vtprd_cart->cart_items[$k]->product_auto_insert_rule_id  ==  $vtprd_rules_set[$i]->post_id) &&
         //($vtprd_cart->cart_items[$k]->product_id == $current_auto_add_array['free_product_id']) ){
         (isset($current_auto_add_array[$free_product_id])) ) {
       
     
      $vtprd_cart->cart_items[$k]->product_auto_insert_state = 'free';
      $current_auto_add_array[$free_product_id]['free_qty'] ++;
      $current_auto_add_array[$free_product_id]['candidate_qty'] --;
      
      $current_auto_add_array[$free_product_id]['current_qty'] = 
        ($current_auto_add_array[$free_product_id]['purchased_qty'] + $current_auto_add_array[$free_product_id]['free_qty']); //v1.1.1.2

      $set_current_auto_add_array = serialize($current_auto_add_array);
      //error_log( print_r(  'set 0006 ', true ) );
      vtprd_set_transient_cart_data ( 'current_auto_add_array', $set_current_auto_add_array, $cart_id );
    }
    //v1.1.0.6 end
    //v1.1.1.2 end
    //********************
    
    return;
 }
 
 
  public function vtprd_compute_each_discount($i, $d, $prod_unit_price ) {   
    global $post, $vtprd_setup_options, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;    
       
     //error_log( print_r(  'vtprd_compute_each_discount ', true ) ); 
        
     //$vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price']
    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_type']  ) {            
      case 'free':
          $discount = $prod_unit_price;
        break;
      case 'fixedPrice':
          $discount = $prod_unit_price - $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];                               
        break;
      case 'percent':
          $percent_off = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'] / 100;   

          
          //*********************
          //v1.1.8.3 Begin
        
          /*
          * The previous method was to compute the discount amount by straight multiplication -
          * discount amount =  %off x unit price
          * HOWEVER, that created an occasional $.01 discrepancy
          * SO the method was altered.
          * 1 - $off = %remaining after discount  
          * %remaining after discount x unit price = unit price after discount, with correct rounding
          *  discount amount =  unit price -  unit price after discount !!!!!                                                       
          */
          
          $percentForUnitPriceAfterDiscount = 1 - $percent_off;
          $unitPriceAfterDiscount_2decimals = round($prod_unit_price * $percentForUnitPriceAfterDiscount , 2);
          $discount = $prod_unit_price - $unitPriceAfterDiscount_2decimals; 
          
          /*  REMOVED v1.1.8.3
        //$discount_2decimals = bcmul($prod_unit_price , $percent_off , 2);
          $discount_2decimals = round($prod_unit_price * $percent_off , 2); //v1.0.7.6                          
          //compute rounding
          $temp_discount = $prod_unit_price * $percent_off;
          
          //$rounding = $temp_discount - $discount_2decimals;
          $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
                  
          if ($rounding > 0.005) {
            //v1.0.7.4 begin
            $increment = round($rounding, 2); //round the rounding error to 2 decimal points!  floating point
            if ($increment < .01) {
              $increment = .01;
            }
            ////v1.0.7.4 end          
            $discount = $discount_2decimals + $increment;   //v1.0.7.4  PHP floating
          }  else {
            $discount = $discount_2decimals;
          }
          */
               
          //v1.1.8.3 End
          //******************
                      
        break;              
      case 'currency': 
          $discount = $vtprd_rules_set[$i]->rule_deal_info[$d]['discount_amt_count'];
                      
          //v1.0.9.3 begin
          if ($discount > $prod_unit_price) {
            $discount = $prod_unit_price;
          }
          //v1.0.9.3 end 
              
        break;
    }

    return $discount;
  }
 
  public function vtprd_set_buy_group_end($i, $d, $r ) { 
    global $post, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;  
     //error_log( print_r(  '  '  , true ) );  
     //error_log( print_r(  'vtprd_set_buy_group_end i= ' .$i.  ' $d= ' .$d.  ' $r= ' .$r , true ) ); 
    

    $templateKey = $vtprd_rules_set[$i]->rule_template;    
      
    $for_loop_current_prod_id = '';
    $for_loop_unit_count = 0;
    $for_loop_price_total = 0;
    $for_loop_elapsed_count = 0;

    if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'quantity') || 
         ($vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] == 'currency') ) {
 
      $sizeof_inPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list);  
      
      for($e=$vtprd_rules_set[$i]->inPop_exploded_group_begin; $e < $sizeof_inPop_exploded_found_list; $e++) {
        $for_loop_elapsed_count++;        
        switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] ) {
          
          case 'quantity':
                $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;
               // $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_end + $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] ;  
               // if ( $temp_end > sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) {  //v1.1.0.6 
                if ( $temp_end > $sizeof_inPop_exploded_found_list ) {  //v1.1.0.6 
                   $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                   $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining qty in cart to fulfill buy amt qty';
                   return;
                }              
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price']; 
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']                
               if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
                  //Set group_end here.  use $e + 1 since we may have reset the for_loop_unit_count during processing
                  
                  $vtprd_rules_set[$i]->inPop_exploded_group_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count; 
                                                      
                  if ($vtprd_template_structures_framework[$templateKey]['buy_amt_mod'] > ' ' ) {
                     switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                         case 'none':
                           break;  
                         case 'minCurrency':                           
                              if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // < is an error, value should be >= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum buy amt qty';
                                return; //v1.1.0.6                                                               
                              }
                           break;
                         case 'maxCurrency':
                              if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // > is an error, value should be <= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum buy amt qty';
                                return; //v1.1.0.6    
                              }                              
                           break;                                            
                     } //end switch 
                   }                                   
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Buy amt Qty test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if         
            break;
         
          case 'currency':
                $for_loop_price_total_previous = $for_loop_price_total; //v1.1.8.1  save for future reference
                
                $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;  
                //if ( $temp_end > sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) {    //v1.1.0.6
                if ( $temp_end > $sizeof_inPop_exploded_found_list ) {    //v1.1.0.6  
                   $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                    $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill buy amt qty';
                   return;
                }             
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price']; 
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']
          
               if ($for_loop_price_total >= $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
                  //Set group_end here.  use $e + 1 since we may have reset the for_loop_unit_count during processing                 

                  $vtprd_rules_set[$i]->inPop_exploded_group_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;

                    //********************
                  //v1.1.8.1a begin
                  //********************

                     /* **************************************************************************************************
                       * Fix - Currency-based rule repeat bug fixed 
                                	When a currency value is greater than what's needed to satisfy a rule iteration, 
                                	The remaining value is used in succeeding iterations (until exhausted).                     
                                            
                       - this only occurs if  ( $for_loop_price_total ** == ** $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] )
                       - in this branch, we Cycle through the iterations in inPop_exploded_group_currency_array 
                       - if the total < 'buy_amt_count', we are by definition on the LAST ONE and so this does not get executed...
                       
                       say buy_amt_count = $100, Prod_unit_price = $1000, total to this point is $50
                       0 prod_unit_price_current = $50  , prod_unit_price_remaining = 950
                       1 prod_unit_price_current = $100 , prod_unit_price_remaining = 850
                       2 prod_unit_price_current = $100 , prod_unit_price_remaining = 750
                       3 prod_unit_price_current = $100 , prod_unit_price_remaining = 650
                       4 prod_unit_price_current = $100 , prod_unit_price_remaining = 550
                       TO LAST
                       9 prod_unit_price_current = $50  , prod_unit_price_remaining = 0
                       
                       ************************************************************************************************** */

                    
                  // test for > only.   If inPop_exploded_group_currency_array IN PROGRESS, this test will FAIL, as it will be <= only, so all good
                  if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
                    
                    //**********************
                    // if extra value, parcel it across multiple iterations
                    //**********************
                                   
                    //amount of unit price used, ONLY what is needed to equal 'buy_amt_count' and NO MORE
                    $amount_used = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'] - $for_loop_price_total_previous;
                    
                    //unused portion of unit price
                    $prod_unit_price_remaining = ($vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'] - $amount_used);

                    //load 1st iteration
                    $price_divided_array = array(
                       array (                       
                        'prod_unit_price_current' =>  $amount_used,
                        'prod_unit_price_remaining' =>  $prod_unit_price_remaining
                       )                 
                      );
 
                    if ($prod_unit_price_remaining > 0) {                
                         //load all 2nd-Nth iterations until orig unit price is exhausted 
                         for( ; $prod_unit_price_remaining > 0; ) { //exit is internal to loop
    
                              if ($prod_unit_price_remaining <= 0) {
                                break;
                              }
                              
                              if ($prod_unit_price_remaining >= $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
                                $amount_used = $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count'];
                              } else {
                                $amount_used = $prod_unit_price_remaining;
                              }
                              
                              //so may be ZERO...
                              $prod_unit_price_remaining = ($prod_unit_price_remaining - $amount_used);
    
                              $price_divided_array[] = 
                                  array (                       
                                    'prod_unit_price_current' =>  $amount_used,
                                    'prod_unit_price_remaining' =>  $prod_unit_price_remaining
                                 );                                                  

                          }             
                      }

                      //OVERWRITES any existing value in the ruleset 
                      //  - will only occur on the 1st time seeing a specific inPop_exploded_array occurrence
                      $vtprd_rules_set[$i]->inPop_exploded_group_currency_array =  
                          array (
                            'inPop_exploded_group_end'      =>  $vtprd_rules_set[$i]->inPop_exploded_group_end,
                            'price_divided_array'    =>  $price_divided_array,
                            /*
                              array (                       
                                'prod_unit_price_current' =>  $amount_used,
                                'prod_unit_price_remaining' =>  $prod_unit_price_remaining
                             ); 
                            */
                            'price_divided_array_occurrence'    => 0,                              
                            'prod_id' =>  $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] , 
                            'orig_prod_unit_price' => $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'],
                            'inPop_exploded_array_current_occurrence' =>  $e         
                          ); 
      
                      //save unit price on the exploded row
                      $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price_hold'] = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'];
                      
                      //set unit price to 1st_time_prod_price NEXT iteration
                      $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'] = 
                          $vtprd_rules_set[$i]->inPop_exploded_group_currency_array['price_divided_array'][0]['prod_unit_price_current'];
 
                      //**  Explode OUT the single inPop row, resorting the array as needed.
                      //**    append to END OF LIST for later $ar processing

                      //create dummy for new exploder adds
                      $inPop_exploded_found_list_row = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]; 
                                           
                      $sizeof_original_inPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list);
                      //got to include the current occurrence as well...
                      $limit_to_here = $vtprd_rules_set[$i]->inPop_exploded_group_currency_array['inPop_exploded_array_current_occurrence'] + 1; 
                      $dummy_counter = 0;                                                                  
                      $sizeof_price_divided_array = sizeof($price_divided_array);
                      //new exploder adds - if we're already on the last occurrence, just add the new 'exploders' to the end!
                      if ( $sizeof_original_inPop_exploded_found_list == $limit_to_here) {
                                             
                        for($z=1; $z < $sizeof_price_divided_array; $z++) {                       

                          //zero out value
                          $inPop_exploded_found_list_row['prod_unit_price'] = $price_divided_array[$z]['prod_unit_price_current'];
                          
                          //set dummy name
                          $dummy_counter++;
                          $inPop_exploded_found_list_row['prod_name'] = 'dummy for currency processing, dummy number= ' .$dummy_counter;
                          
                          $vtprd_rules_set[$i]->inPop_exploded_found_list[] = $inPop_exploded_found_list_row;
                        }

                      } else {
                      
                        //create new temp array. copy forwards until reaching current iteration.  add in new 'exploders'. copy the rest of the original array. overwrite the original array. 
                        $temp_array = array();
                        
                        //copy forwards to current location, index value = $c for copy
                        for($c=0; $c < $limit_to_here; $c++) {  
                           $temp_array[] = $vtprd_rules_set[$i]->inPop_exploded_found_list[$c];
                        } 
                        
                        //add new exploders 
                        for($z=1; $z < $sizeof_price_divided_array; $z++) {                       

                          //zero out value
                          $inPop_exploded_found_list_row['prod_unit_price'] = $price_divided_array[$z]['prod_unit_price_current'];
                          
                          //set dummy name
                          $dummy_counter++;
                          $inPop_exploded_found_list_row['prod_name'] = 'dummy for currency processing, dummy number= ' .$dummy_counter;
                          
                          $temp_array[] = $inPop_exploded_found_list_row;
                        }
                          
                        //now that the new exploders have been added, copy in the rest of the original array (from where we left $c to end of original_inPop_exploded_found_list)                        
                        //  don't need $c++, as it is done coming out of the bottom of the previous for loop
                        for( ; $c < $sizeof_original_inPop_exploded_found_list; $c++) {  
                           $temp_array[] = $vtprd_rules_set[$i]->inPop_exploded_found_list[$c];
                        } 
                        
                        //overwrite the inPop_exploded_found_list with newly enlarged array
                        $vtprd_rules_set[$i]->inPop_exploded_found_list = $temp_array;
                                                                                           
                      } //end of if for new exploder adds

                  } 
                  
                  //v1.1.8.1 end
                  //***************
                                                  
                  if ($vtprd_template_structures_framework[$templateKey]['buy_amt_mod'] > ' ' ) {
                    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                       case 'none':
                         break;
                       case 'minCurrency':                           
                            if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // < is an error, value should be >= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum buy amt mod count';
                              return; //v1.1.0.6
                            }
                         break;
                       case 'maxCurrency':
                            if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // > is an error, value should be <= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum buy amt mod count';
                              return; //v1.1.0.6
                            }                              
                         break;                                              
                    } //end switch                                    
                  }
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Buy amt $$ test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if  
           break;
                       
        }  //end switch  vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_type'] 
      } //end for loop
      
      //if loop reached end of list... 
      //if ($e >= sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) { //v1.1.0.6 
      if ($e >= $sizeof_inPop_exploded_found_list ) { //v1.1.0.6 
        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'reached end of inPop_exploded_found_list';
        return;
      }
    } else {//end if 'quantity' or 'currency'
      
      //'nthQuantity' path
      $end_of_nth_test = 'no';         
      //Must do 'for' loop, as exploded list may cross product boundaries and if 'each' the count must be reset...
      for($e=$vtprd_rules_set[$i]->inPop_exploded_group_begin; $end_of_nth_test == 'no'; $e++) {
          $for_loop_elapsed_count++;       
          $temp_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;  
          //if ( $temp_end > sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) { //v1.1.0.6 
          if ( $temp_end > $sizeof_inPop_exploded_found_list ) { //v1.1.0.6 
             $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
             $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining buy qty for nth';
             return;
          }
          
          switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] ) {             
            case 'each':
                 //check if new product in list...
                 if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'] ) {
                    //if new product, reset all tracking fields
                    $for_loop_current_prod_id = $vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_id'];
                    $for_loop_unit_count = 1;                
                 } else {
                    $for_loop_unit_count++;                  
                 }
              break;               
            case 'all':
                $for_loop_unit_count++;               
              break;           
          } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to']        
               
          if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_count']) {
            //Set group_end here.  use $e + 1 since we may have reset the for_loop_unit_count during processing
            $vtprd_rules_set[$i]->inPop_exploded_group_end = $vtprd_rules_set[$i]->inPop_exploded_group_begin + $for_loop_elapsed_count;                               
            if ($vtprd_template_structures_framework[$templateKey]['buy_amt_mod'] > ' ' ) {
              switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                 case 'none':
                    break;
                 case 'minCurrency':                           
                      if ($vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'] < $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // < is an error, value should be >= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining minimum buy $$ for nth';
                      }
                   break;
                 case 'maxCurrency':
                      if ($vtprd_rules_set[$i]->inPop_exploded_found_list[$e]['prod_unit_price'] > $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']) { // > is an error, value should be <= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining maximum buy $$ for nth';
                      }                              
                   break;                                              
              } //end switch                                    
            }
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Buy amt Qty Nth test completed';
            return; // done, passed the test, both begin and end set...            
          }  //end if
          //if ($e >= sizeof($vtprd_rules_set[$i]->inPop_exploded_found_list) ) { //v1.1.0.6 
          if ( $e >= $sizeof_inPop_exploded_found_list ) {  //v1.1.0.6      
            $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'End of inPop reached during Nth processing';
            $end_of_nth_test = 'yes';
            return;
          }         
      } // end for loop $end_of_nth_test
        
    } //end if
 
    return;
 }
 
     //if action_amt_type is active and there is a action_amt count...
    //***********************************************************
    //THIS SETS THE SIZE OF THE BUY exploded GROUP "WINDOW"
    //***********************************************************
 
  public function vtprd_set_action_group_end($i, $d, $ar ) { 
    global $post, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;     
      //error_log( print_r(  '  '  , true ) );
      //error_log( print_r(  'vtprd_set_action_group_end i= ' .$i.  ' $d= ' .$d.  ' $ar= ' .$ar.  ' exploded_group_begin= '  .$vtprd_rules_set[$i]->actionPop_exploded_group_begin, true ) );  
    /*
    DETERMINE THE BEGIN AND END OF ACTIONPOP PROCESSING "WINDOW"
    
    1st time, group_end set to 0, end may be set but will be overwritten here
              group_begin remains at 0, since its an OCCURRENCE begin
    2nd-Nth,  group_begin set to previous end + 1
              group_end set to a computed value.  If the required action group size is not reached or end of actionPop reached, 
                  the setup/edit fails.     
    */


    $templateKey = $vtprd_rules_set[$i]->rule_template;
    
    $for_loop_current_prod_id = ''; //v1.0.8.7
    $for_loop_unit_count = 0;
    $for_loop_price_total = 0;
    $for_loop_elapsed_count = 0;

    if ( ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'quantity') || 
         ($vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] == 'currency') ) {
    
      $sizeof_actionPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
      for($e=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $e < $sizeof_actionPop_exploded_found_list; $e++) {
        $for_loop_elapsed_count++;
        switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] ) {
          
          case 'quantity':         
                $temp_end = $vtprd_rules_set[$i]->actionPop_exploded_group_end + $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] ;  
                if ( $temp_end > sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list) ) {
                   $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                   $vtprd_rules_set[$i]->end_of_actionPop_reached = 'yes';
                   $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining qty in cart to fulfill action amt qty';
                   return;
                }               
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];                     
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];                   
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']            
               if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
                  $vtprd_rules_set[$i]->actionPop_exploded_group_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;                     
                  if ($vtprd_template_structures_framework[$templateKey]['action_amt_mod'] > ' ' ) {
                     switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod'] ) {
                         case 'none':
                           break;  
                         case 'minCurrency':                           
                              if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // < is an error, value should be >= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum action amt qty'; 
                              }
                           break;
                         case 'maxCurrency':
                              if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // > is an error, value should be <= 
                                $failed_test_total++;
                                $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                                $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum action amt qty'; 
                              }                              
                           break;                                            
                     } //end switch 
                   }                                                   
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt Qty test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if         
          break;
         
          case 'currency':          
               $for_loop_price_total_previous = $for_loop_price_total; //v1.1.8.1  save for future reference
               
               switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] ) {             
                  case 'each':
                       //check if new product in list...
                       if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] ) {
                          //if new product, reset all tracking fields
                          $for_loop_current_prod_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'];
                          $for_loop_unit_count = 1;
                          $for_loop_price_total = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                       } else {
                          $for_loop_unit_count++;
                          $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                       }
                    break;               
                  case 'all':
                      $for_loop_unit_count++;
                      $for_loop_price_total += $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price']; 
                    break;           
               } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']
               
               if ($for_loop_price_total >= $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {

                  $vtprd_rules_set[$i]->actionPop_exploded_group_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;                     
                       
                  //********************
                  //v1.1.8.1a begin
                  //********************
                  
                  //**SET up for the NEXT iteration
                  //if "unused" remainder, replace the LAST unit price with that remainder
                  //so if the value is $200 and we only need $100 for this iteration, leave $100 behind and begin here at next iteration.

                     /* **************************************************************************************************
                       - this only occurs if  ( $for_loop_price_total ** == ** $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] )
                       - in this branch, we Cycle through the iterations in actionPop_exploded_group_currency_array 
                       - if the total < 'action_amt_count', we are by definition on the LAST ONE and so this does not get executed...
                       
                       say action_amt_count = $100, Prod_unit_price = $1000, total to this point is $50
                       0 prod_unit_price_current = $50  , prod_unit_price_remaining = 950
                       1 prod_unit_price_current = $100 , prod_unit_price_remaining = 850
                       2 prod_unit_price_current = $100 , prod_unit_price_remaining = 750
                       3 prod_unit_price_current = $100 , prod_unit_price_remaining = 650
                       4 prod_unit_price_current = $100 , prod_unit_price_remaining = 550
                       TO LAST
                       9 prod_unit_price_current = $50  , prod_unit_price_remaining = 0
                       
                       ************************************************************************************************** */

                    
                  // test for > only.   If actionPop_exploded_group_currency_array IN PROGRESS, this test will FAIL, as it will be <= only, so all good
                  if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
                    
                    //**********************
                    // if extra value, parcel it across multiple iterations
                    //**********************
                                   
                    //amount of unit price used, ONLY what is needed to equal 'action_amt_count' and NO MORE
                    $amount_used = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'] - $for_loop_price_total_previous;
                    
                    //unused portion of unit price
                    $prod_unit_price_remaining = ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'] - $amount_used);

                    //load 1st iteration
                    $price_divided_array = array(
                       array (                       
                        'prod_unit_price_current' =>  $amount_used,
                        'prod_unit_price_remaining' =>  $prod_unit_price_remaining
                       )                 
                      );

                    if ($prod_unit_price_remaining > 0) {                
                         //load all 2nd-Nth iterations until orig unit price is exhausted 
                         for( ; $prod_unit_price_remaining > 0; ) { //exit is internal to loop
    
                              if ($prod_unit_price_remaining <= 0) {
                                break;
                              }
                              
                              if ($prod_unit_price_remaining >= $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
                                $amount_used = $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count'];
                              } else {
                                $amount_used = $prod_unit_price_remaining;
                              }
                              
                              //so may be ZERO...
                              $prod_unit_price_remaining = ($prod_unit_price_remaining - $amount_used);
    
                              $price_divided_array[] = 
                                  array (                       
                                    'prod_unit_price_current' =>  $amount_used,
                                    'prod_unit_price_remaining' =>  $prod_unit_price_remaining
                                 );                                                  

                          }             
                      }

                      //OVERWRITES any existing value in the ruleset 
                      //  - will only occur on the 1st time seeing a specific actionPop_exploded_array occurrence
                      $vtprd_rules_set[$i]->actionPop_exploded_group_currency_array =  
                          array (
                            'actionPop_exploded_group_end'      =>  $vtprd_rules_set[$i]->actionPop_exploded_group_end,
                            'price_divided_array'    =>  $price_divided_array,
                            /*
                              array (                       
                                'prod_unit_price_current' =>  $amount_used,
                                'prod_unit_price_remaining' =>  $prod_unit_price_remaining
                             ); 
                            */
                            'price_divided_array_occurrence'    => 0,                              
                            'prod_id' =>  $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] , 
                            'orig_prod_unit_price' => $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'],
                            'actionPop_exploded_array_current_occurrence' =>  $e         
                          ); 
      
                      //save unit price on the exploded row
                      $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price_hold'] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'];
                      
                      //set unit price to 1st_time_prod_price NEXT iteration
                      $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'] = 
                          $vtprd_rules_set[$i]->actionPop_exploded_group_currency_array['price_divided_array'][0]['prod_unit_price_current'];

                      //**  Explode OUT the single actionpop row, resorting the array as needed.
                      //**    append to END OF LIST for later $ar processing

                      //create dummy for new exploder adds
                      $actionPop_exploded_found_list_row = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e];                                          
                      $actionPop_exploded_found_list_row['this_is_a_currency_exploder_row'] = TRUE;
                                          
                      $sizeof_original_actionPop_exploded_found_list = sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list);
                      //got to include the current occurrence as well...
                      $limit_to_here = $vtprd_rules_set[$i]->actionPop_exploded_group_currency_array['actionPop_exploded_array_current_occurrence'] + 1; 
                      $dummy_counter = 0;                                                                  
                      $sizeof_price_divided_array = sizeof($price_divided_array);
                                        
                      //new exploder adds - if we're already on the last occurrence, just add the new 'exploders' to the end!
                      if ( $sizeof_original_actionPop_exploded_found_list == $limit_to_here) {
                      
                        for($z=1; $z < $sizeof_price_divided_array; $z++) {                       

                          //zero out value
                          $actionPop_exploded_found_list_row['prod_unit_price'] = $price_divided_array[$z]['prod_unit_price_current'];
                          
                          //set dummy name
                          $dummy_counter++;
                          $actionPop_exploded_found_list_row['prod_name'] = 'dummy for currency processing, dummy number= ' .$dummy_counter;
                          
                          $vtprd_rules_set[$i]->actionPop_exploded_found_list[] = $actionPop_exploded_found_list_row;
                        }

                      } else {
                      
                        //create new temp array. copy forwards until reaching current iteration.  add in new 'exploders'. copy the rest of the original array. overwrite the original array. 
                        $temp_array = array();
                        
                        //copy forwards to current location, index value = $c for copy
                        for($c=0; $c < $limit_to_here; $c++) {  
                           $temp_array[] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$c];
                        } 
                        
                        //add new exploders 
                        for($z=1; $z < $sizeof_price_divided_array; $z++) {                       

                          //zero out value
                          $actionPop_exploded_found_list_row['prod_unit_price'] = $price_divided_array[$z]['prod_unit_price_current'];
                          
                          //set dummy name
                          $dummy_counter++;
                          $actionPop_exploded_found_list_row['prod_name'] = 'dummy for currency processing, dummy number= ' .$dummy_counter;
                          
                          $temp_array[] = $actionPop_exploded_found_list_row;
                        }
                          
                        //now that the new exploders have been added, copy in the rest of the original array (from where we left $c to end of original_actionPop_exploded_found_list)                        
                        //  don't need $c++, as it is done coming out of the bottom of the previous for loop
                        for( ; $c < $sizeof_original_actionPop_exploded_found_list; $c++) {  
                           $temp_array[] = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$c];
                        } 
                        
                        //overwrite the actionPop_exploded_found_list with newly enlarged array
                        $vtprd_rules_set[$i]->actionPop_exploded_found_list = $temp_array;
                                                                                           
                      } //end of if for new exploder adds

                  } 
                  
                  //v1.1.8.1 end
                  //***************

                  if ($vtprd_template_structures_framework[$templateKey]['action_amt_mod'] > ' ' ) {
                    switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod'] ) {
                       case 'none':
                         break;
                       case 'minCurrency':                           
                            if ($for_loop_price_total < $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // < is an error, value should be >= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill minimum action amt mod count';
                            }
                         break;
                       case 'maxCurrency':
                            if ($for_loop_price_total > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // > is an error, value should be <= 
                              $failed_test_total++;
                              $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                              $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining $$ in cart to fulfill maximum action amt mod count';
                            }                              
                         break;                                              
                    } //end switch                                    
                  }
                  $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt $$ test completed';
                  return; // done, passed the test, both begin and end set...
               }  //end if  
          break;
                       
        }  //end switch  vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_type'] 
      } //end for loop
            
      //if loop dropout + reached end of list...
      if ($e >= sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list) ) {
        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'reached end of actionPop_exploded_found_list';
        return;
      }
    } else {//end if 'quanity' or 'currency'
      
      //'nthQuantity' path
      $end_of_nth_test = 'no';
      for($e=$vtprd_rules_set[$i]->actionPop_exploded_group_begin; $end_of_nth_test == 'no'; $e++) {
         $for_loop_elapsed_count++;
         $temp_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count;  
          if ( $temp_end > sizeof($vtprd_rules_set[$i]->actionPop_exploded_found_list) ) {
             $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
             $vtprd_rules_set[$i]->end_of_actionPop_reached = 'yes';
             $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining action qty for nth';
             return;
          }
          
          switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to'] ) {             
            case 'each':
                 //check if new product in list...
                 if ($for_loop_current_prod_id != $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'] ) {
                    //if new product, reset all tracking fields
                    $for_loop_current_prod_id = $vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_id'];
                    $for_loop_unit_count = 1;
                 } else {
                    $for_loop_unit_count++;
                 }
              break;               
            case 'all':
                $for_loop_unit_count++;
              break;           
          } //end switch  $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_applies_to']        
               
          if ($for_loop_unit_count == $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_count']) {
            $vtprd_rules_set[$i]->actionPop_exploded_group_end = $vtprd_rules_set[$i]->actionPop_exploded_group_begin + $for_loop_elapsed_count; 
            if ($vtprd_template_structures_framework[$templateKey]['action_amt_mod'] > ' ' ) {
              switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod'] ) {
                 case 'none':
                    break;
                 case 'minCurrency':                           
                      if ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'] < $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // < is an error, value should be >= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining minimum action $$ for nth';
                      }
                   break;
                 case 'maxCurrency':
                      if ($vtprd_rules_set[$i]->actionPop_exploded_found_list[$e]['prod_unit_price'] > $vtprd_rules_set[$i]->rule_deal_info[$d]['action_amt_mod_count']) { // > is an error, value should be <= 
                        $failed_test_total++;
                        $vtprd_rules_set[$i]->rule_processing_status = 'cartGroupFailedTest';
                        $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Insufficient remaining maximum action $$ for nth';
                      }                              
                   break;                                              
              } //end switch                                    
            }
            $vtprd_rules_set[$i]->rule_processing_msgs[] = 'Action amt Qty Nth test completed';
            return; // done, passed the test, both begin and end set...
          }  //end if        
      } // end for loop $end_of_nth_test
        
    } //end if
    
   return;
 }
 
 /*
 This process treats all of the products/quantities in the cart as a running total.  For each sub-group in the cart, derived from applying the buy_amt_count,
 the group valuation is computed.  if it doesn't fulfill the buy_amt_mod requirements, that part of the cart fails this test (for this rule). 
 */ 
  public function vtprd_buy_amt_mod_all_process($i,$d, $failed_test_total) { 
    global $post, $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_template_structures_framework;    
       
     //error_log( print_r(  'vtprd_buy_amt_mod_all_process ', true ) ); 
     
    //walk through the cart imits 1 by 1 until inPop_running_unit_group_begin_pointer reached

    //preset to 'fail', on success it is switched to 'pass' in the routine
    //$vtprd_rules_set[$i]->buy_amt_process_status = 'fail';
    $current_group_pointer = 0;
    $buy_amt_mod_count_elapsed = 0;
    $buy_amt_mod_count_currency_total = 0;

    $sizeof_inPop_found_list = sizeof($vtprd_rules_set[$i]->inPop_found_list);
    for($k=0; $k < $sizeof_inPop_found_list; $k++) {      
    //add this product's unit count to the current_group_pointer
    //   until unit_counter_begin reached or end of unit count 
      for($z=0; $z < $vtprd_rules_set[$i]->inPop_found_list[$k]['prod_qty']; $z++) {
         //this augments the $current_group_pointer until it equals the begin pointer, then stops
         //  from this point on, it's the gateway to the rest of the routine.
         if ($current_group_pointer < $vtprd_rules_set[$i]->inPop_group_begin_pointer) { 
            $current_group_pointer++;
         }         
         if ($current_group_pointer == $vtprd_rules_set[$i]->inPop_group_begin_pointer) {
            //used to track the correct starting point
            $buy_amt_mod_count_elapsed++;
            //total up the unit costs until ...
            $buy_amt_mod_count_currency_total +=  $vtprd_rules_set[$i]->inPop_found_list[$k]['prod_unit_price'];  
            
            //if currency threshhold reached...., test and exit
            if ($buy_amt_mod_count_currency_total >= $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod_count']  ) {            
              switch( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_mod'] ) {
                case 'minCurrency':
                    
                  break;
                case 'maxCurrency':
                  break;
              }
              
              //increment the begin pointer to the end of current group +1 
              $vtprd_rules_set[$i]->inPop_group_begin_pointer = $vtprd_rules_set[$i]->inPop_group_begin_pointer + $buy_amt_mod_count_elapsed + 1 ;
              break 2;  //break out of both for loops and return...
            }
         }
      }
    }                        
     
    return;
 }
    
   //***********************
   //v2.0.0 recoded
   //***********************
   public function vtprd_is_product_in_inPop_group($i, $k) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      
     //error_log( print_r(  'vtprd_is_product_in_inPop_group ', true ) ); 
 
     //Check all EXCLUDED selections first
     /* ALL of this now in **vtprd_edit_arrays_framework in vtprd-rules-ui-framework.php** 
         'buy_group_prod_cat_incl_array'            => array(),
         'buy_group_prod_cat_excl_array'            => array(), 
         'buy_group_plugin_cat_incl_array'          => array(),
         'buy_group_plugin_cat_excl_array'          => array(), 
         'buy_group_product_incl_array'             => array(),
         'buy_group_product_excl_array'             => array(),		 
         'buy_group_var_name_incl_array'            => array(), 
         'buy_group_var_name_excl_array'            => array(),
         'buy_group_brands_incl_array'              => array(), //woo brands plugin / other brands plugins by filter
         'buy_group_brands_excl_array'              => array(), //woo brands plugin / other brands plugins by filter 
                              
         //Roles / Customers / Groups / Brands / Other
              **************************************
              * 'and' here means ONE of the customer identifiers must be matched, for the rule to go forwards
              * ************************************              
         'buy_group_customer_and_or'                => 'and',  //'and' = 1 of the customer identifiers is required, 'or' = optional
         'buy_group_role_incl_array'                => array(),
         'buy_group_role_excl_array'                => array(),  
         'buy_group_email_incl_array'               => array(), 
         'buy_group_email_excl_array'               => array(), 
         'buy_group_groups_incl_array'              => array(), //groups plugin / woo groups plugin
         'buy_group_groups_excl_array'              => array() //groups plugin / woo groups plugin
         'buy_group_memberships_incl_array'             => array(), //official Woo membership plugin
         'buy_group_memberships_excl_array'             => array(), //official Woo membership plugin           
     */
          
     //----------------------------------------
     //EXCLUDES FIRST, descending order is fine.
     //----------------------------------------
     
     //v2.0.2.0 end

     //v2.0.2.0 wrap in general test
     if ( ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] == 'excludeOnly') ||
          ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] == 'both') ) {
         
         //EDITED * + * +  * + * +  * + * +  * + * +
    
         if ( (is_array($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array'])) && //will be spaces if no contents!!
              (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array']) > 0) ) {
    
          //$array_size = sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array']);
          //error_log( print_r(  'sizeof buy_group_var_name_excl_array= ' .sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array']), true ) );
          //error_log( print_r(  'buy_group_var_name_excl_array=', true ) );
          //error_log( var_export($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array'], true ) );
         
            if ($this->vtprd_is_var_name_in_list_check($i, $k, 'excl-inPop', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array']) ) {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Variation Name exclusion found';
     //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 005 ', true ) );
              return false;
            }
         }
         
    
         if (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_excl_array']) > 0) {
            if ($this->vtprd_are_brands_in_list_check($i, $k, 'excl', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_excl_array']) ) {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Variation Name exclusion found';
     //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 006 ', true ) );
              return false;
            }
         }
   
         //v2.0.2.0 begin
         if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] == 'excludeOnly') { 
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Groups inclusion OK, no exclusions found for an exclude-only INPOP rule';
            return true;
         }
         //v2.0.2.0 end
         
     } //v2.0.2.0 end     
     
     //****************
     //v2.0.1.0 begin -   Customer inclusion/exclusion tests moved to vtprd_manage_customer_rule_tests
     // Customer EXcludes already applied
     //****************
     
     //-----------
     //INCLUDES 
     //-----------
     /* v2.0.2.0 superceded
     //if set to exclude only, we've passed all the tests!! 
     if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_set_to_exclude_only']) {
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Groups inclusion OK, no exclusions found for an exclude-only INPOP rule';
        return true;
     }
     */
     

     if ( ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or']  == 'or') &&
          ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] == 0) &&
          ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_found']) ) {   // set earlier in vtprd_manage_customer_rule_tests
        return true; 
     } 


     //***********************
     //CUSTOMER INCLUSION Attributes End
     //***********************
     
     //v2.0.1.0 end

     $and_tracking = 0;
     
     //EDITED * + * +  * + * +  * + * +  * + * +

     if ( (is_array($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array'])) && //will be spaces if no contents!!
          (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array']) > 0) ) {
 //error_log( print_r(  'above vtprd_is_var_name_in_list_check', true ) );        
        if ($this->vtprd_is_var_name_in_list_check($i, $k, 'incl-inPop', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array']) ) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Variation Name inclusion found';
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_and_or'] == 'and') {
              $and_tracking++;
           }
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] == $and_tracking) {
              return true;
           }                    
        } else {
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_and_or'] == 'and') {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Variation Name Not found (inpop and 1)';
 //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 018 ', true ) );
              return false;
           }        
        }
     } else {
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_and_or'] == 'and') {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Variation Name Not found (inpop and 2)';
 //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 019 ', true ) );
              return false;
           }     
     }
     

     if (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_incl_array']) > 0) {
        if ($this->vtprd_are_brands_in_list_check($i, $k, 'incl', $vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_incl_array']) ) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Brands inclusion found';
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_and_or'] == 'and') {
              $and_tracking++;
           }
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] == $and_tracking) {
              return true;
           }                    
        } else {
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_and_or'] == 'and') {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Brands Not found';
 //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 020 ', true ) );
              return false;
           }        
        }
     } else {
           if ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_and_or'] == 'and') {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Brands Not found';
 //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 021 ', true ) );
              return false;
           }      
     }
    
    //error_log( print_r(  'Product Not in any Inclusion list ', true ) );
    //error_log( print_r(  'Cart product id= ' .$vtprd_cart->cart_items[$k]->product_id, true ) );
    //error_log( print_r(  'buy_group_var_name_incl_array= ' , true ) ); 
    //error_log( var_export($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array'], true ) );    
       

      //FALLTHROUGH - not found
      $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product Not in any Inclusion list';
 //error_log( print_r(  'vtprd_is_product_in_inPop_group return false 022 ', true ) );
      return false;
      
   } 
  

    //*************************
    //v2.0.0 recoded
    //*************************
    public function vtprd_is_role_in_inPop_list_check($i,$k, $incl_or_excl, $test_this_array) {
    //EDITED * + * +  * + * +  * + * +  * + * +
    }

  
    //*************************
    //v2.0.0 New Function
    //*************************
    public function vtprd_is_email_in_inPop_list_check($i,$k, $incl_or_excl, $test_this_array) {
    //EDITED * + * +  * + * +  * + * +  * + * +
    }

    //*************************
    //v2.0.0 New Function
    //*************************
    public function vtprd_is_var_name_in_list_check($i,$k, $incl_or_excl, $test_this_array) {
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options; 
      
      //error_log( print_r(  'vtprd_is_var_name_in_list_check, $i= ' .$i. ' $k= ' .$k. ' $incl_or_excl= ' .$incl_or_excl. ' $test_this_array= ', true ) );
      //error_log( var_export($test_this_array, true ) );              
        
        //if on-the fly variation id, 2nd test applies
        if ( ($vtprd_cart->cart_items[$k]->variation_id > ' ') ||
             ( (isset($vtprd_cart->cart_items[$k]->variation_array)) && 
               (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0) ) ) { 
            $carry_on = true;             
        } else {
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Not a variation product, no discount with a varName rule for '.$incl_or_excl;                  
            return false;                 
        }
                        
        //Text comparisons should be done in lowercase on both sides...
   
        //just use the value => key is variaton set name
        // for 'on the fly' variations...

        //***********************************************************************************
        //**  Using this logic covers BOTH straight variation, and on-the-fly variations
        //***********************************************************************************


        $varName_found = false;

        //apply lowercase to Product Variation array names
        $product_varName_array = array();
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $product_varName) { 
          $product_varName_array[] = strtolower($product_varName);
        }
        //$vtprd_rules_set[$i]->buy_group_varName is already in lower case at rule update time.
        // need the following foreach, as buy_group_varName_array is a multidimensional array

      
      //error_log( print_r(  '$product_varName_array= ', true ) );
      //error_log( var_export($product_varName_array, true ) );
        
        foreach ($test_this_array as $varName_combo) {
      
      //error_log( print_r(  '$varName_combo= ', true ) );
      //error_log( var_export($varName_combo, true ) );              
        
          $result = array_intersect($varName_combo, $product_varName_array );
          //if we've found all of the $varName_combo, then found.
          if ( (sizeof($result)) == (sizeof($varName_combo)) ) {
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Rule var names found in Product var name for '.$incl_or_excl; 
      //error_log( var_export('Rule var names found in Product var name for '.$incl_or_excl, true ) );      
            return true; 
          }
        }

        if ($varName_found) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Rule var names found in Product var name for '.$incl_or_excl; 
       //error_log( var_export('Rule var names found in Product var name for '.$incl_or_excl, true ) ); 
          return true;
        } else {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Rule var names not found in Product var name for '.$incl_or_excl;                  
      //error_log( var_export('Rule var names NOT found in Product var name for '.$incl_or_excl, true ) ); 
          return false;         
        }
                       
    }

    //*************************
    //v2.0.0 New Function
    //*************************
    public function vtprd_are_brands_in_list_check($i,$k, $incl_or_excl, $test_this_array) {
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;      
      if (!$vtprd_cart->cart_items[$k]->brands_tax_found) {
        //find active brands taxonomy
        $tax_array = $vtprd_info['brands_taxonomy_array'];                
        $filter_tax = apply_filters('vtprd_add_brands_taxonomy',FALSE );
        if ($filter_tax) {
          $tax_array[] = $filter_tax;
        } 

        foreach ( $tax_array as $tax ) {
          if (taxonomy_exists($tax)) { 
            $vtprd_cart->cart_items[$k]->brands_tax_found = $tax;
            break;
          }
        } 
        
        if (!$vtprd_cart->cart_items[$k]->brands_tax_found) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Brand taxonomy not found for '.$incl_or_excl;                  
          return false;
        }
      }
      
      //v2.0.0.5 begin - changed to check brand against parent, as needed
      if (sizeof($vtprd_cart->cart_items[$k]->brands_list) == 0) {
        if ($vtprd_cart->cart_items[$k]->parent_product_id) {
          $use_this_id = $vtprd_cart->cart_items[$k]->parent_product_id;
        } else {
          $use_this_id = $vtprd_cart->cart_items[$k]->product_id;        
        }
        $vtprd_cart->cart_items[$k]->brands_list = wp_get_object_terms( $use_this_id, $vtprd_cart->cart_items[$k]->brands_tax_found, $args = array('fields' => 'ids') );
      }
      //v2.0.0.5 end
      
      if (sizeof($vtprd_cart->cart_items[$k]->brands_list) == 0) {
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product is not in a brand taxonomy for '.$incl_or_excl;                  
        return false;
      }
      
      if (array_intersect($vtprd_cart->cart_items[$k]->brands_list, $test_this_array )) {
        $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product is in a selected brand taxonomy for '.$incl_or_excl;                  
        return true;
      }

      $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product is not in a brand taxonomy for '.$incl_or_excl;                  
      return false;
                                          
    }              

    //*************************
    //v2.0.0 New Function
    //v2.0.0.8 reworked
    //*************************
    public function vtprd_are_groups_in_inPop_list_check($i,$k, $incl_or_excl, $test_this_array) {
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options, $wpdb;      
       if (sizeof($vtprd_cart->cart_items[$k]->groups_list) == 0) {
          $vtprd_cart->cart_items[$k]->groups_list = do_shortcode('[groups_user_groups]'); //gets current user_id if null, get groups associated with this user
       }
       if (sizeof($vtprd_cart->cart_items[$k]->groups_list) == 0) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'User is not in a Group for '.$incl_or_excl;                  
          return false;
       }
       
       /*
       At this point, 'groups_list' will contain a SINGLE FIELD of HTML, stringing all the group NAMEs together with intervening markup.
       **However, $test_this_array will have group IDs, not names.**
       So we first have to get the names.
       */              
       $user_found = false;
       $group_names = array(); 
       foreach ($test_this_array as $group_ID) {
         $name = $wpdb->get_var( "SELECT `name` FROM `" . $wpdb->prefix . "groups_group` WHERE `group_id`='" . $group_ID . "' LIMIT 1" ); 
         if ($name) {
            $group_names[] = $name;
            if (strpos($vtprd_cart->cart_items[$k]->groups_list,$name) !== false) {
               $user_found = true;
               continue;
            }
         }
       }
       // save into cart_items for documentation (added on the fly)
       if ($incl_or_excl = 'incl') {
          $vtprd_cart->cart_items[$k]->groups_list_incl_names = $group_names;
       } else {
          $vtprd_cart->cart_items[$k]->groups_list_excl_names = $group_names;       
       }


       if ( $user_found ) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'User is in a selected Group for '.$incl_or_excl;                  
          return true;       
       }
       $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'User is not in a selected Group for '.$incl_or_excl;                  
       return false;
       
        /* groups_list
        IN \groups.2.3.0\groups\lib\access\class-groups-access-shortcodes.php':
        
        	public static function groups_user_groups( $atts, $content = null ) {
        		$output = '';
        		$options = shortcode_atts(
        			array(
        				'user_id' => null, //gets current user_id if null
        				'user_login' => null,
        				'user_email' => null,
        				'format' => 'list', //no formatting, just an array!!
        				'list_class' => 'groups',
        				'item_class' => 'name',
        				'order_by' => 'name',
        				'order' => 'ASC',
        				'group' => null,
        				'exclude_group' => null
        			),
        			$atts
        		);
      */
                                          
    }               


    //*************************
    //v2.0.0 New Function
    //v2.0.0.9 refactored
    //*************************
    public function vtprd_are_memberships_in_inPop_list_check($i,$k, $incl_or_excl, $test_this_array) {
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;      
       if (sizeof($vtprd_cart->cart_items[$k]->memberships_list) == 0) {
         /*       
         from woocommerce-memberships/includes/admin/class-wc-memberships-admin-users.php
          function user_column_values( $output, $column_name, $user_id ) 
         */ 
          $memberships_plan_list = array();
          if (function_exists('wc_memberships'))  {
            $user_id = get_current_user_id();
       			$memberships = wc_memberships()->get_user_memberships_instance()->get_user_memberships( $user_id );     
      			if ( ! empty( $memberships ) ) {           
      				foreach ( $memberships as $membership ) {      
      					$plan = $membership->get_plan();
      					if ( $plan && wc_memberships_is_user_active_member( $user_id, $plan ) ) {
      						$memberships_plan_list[] = $plan->id;
      					}                                
      				}
      			}
          }              
          $vtprd_cart->cart_items[$k]->memberships_plan_list = $memberships_plan_list;           
       }
       if (sizeof($vtprd_cart->cart_items[$k]->memberships_plan_list) == 0) {
          $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Users Membership Plan not found in list for '.$incl_or_excl;                  
          return false;
       }
       
       foreach ( $vtprd_cart->cart_items[$k]->memberships_plan_list as $key => $membership_plan ) {
          if (in_array($membership_plan, $test_this_array)) {
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Users Membership Plan found in list for '.$incl_or_excl;                  
            return true;              
          }
       }
       
       $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Users Membership Plan not found in list for '.$incl_or_excl;                  
       return false;
                                       
    }  
 
    
    
    //*************************
    //v2.0.0 New Function
    //*************************
    public function vtprd_flatten_multi_array($array_to_flatten) {                    
       //each $array_to_flatten level is itself an array              
       $flattened_array = array();        
       foreach ($array_to_flatten as $sub_array) {
          foreach ($sub_array as $array_entry) { 
            $flattened_array[] = $array_entry;
          }  
       }     
       return $flattened_array;                                       
    }  
    
    
         
   public function vtprd_is_product_in_actionPop_group($i,$k) { 
      global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      
     //error_log( print_r(  'vtprd_is_product_in_actionPop_group ', true ) ); 
 
      
     //error_log( print_r(  'vtprd_is_product_in_actionPop_group ', true ) ); 
 
     /* ALL of this now in **vtprd_edit_arrays_framework in vtprd-rules-ui-framework.php** 
         'action_group_prod_cat_incl_array'            => array(),
         'action_group_prod_cat_excl_array'            => array(), 
         'action_group_plugin_cat_incl_array'          => array(),
         'action_group_plugin_cat_excl_array'          => array(), 
         'action_group_product_incl_array'             => array(),
         'action_group_product_excl_array'             => array(),		 
         'action_group_var_name_incl_array'            => array(), 
         'action_group_var_name_excl_array'            => array(),
         'action_group_brands_incl_array'              => array(), //woo brands plugin / other brands plugins by filter
         'action_group_brands_excl_array'              => array(), //woo brands plugin / other brands plugins by filter 
                              
     */
          
     //----------------------------------------
     //EXCLUDES FIRST, descending order is fine.
     //----------------------------------------

     //v2.0.2.0 wrap in general test
     if ( ($vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] == 'excludeOnly') ||
          ($vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] == 'both') ) {
         
         //EDITED * + * +  * + * +  * + * +  * + * +
    
         if ( (is_array($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_excl_array'])) && //will be spaces if no contents!!
              (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_excl_array']) > 0) ) {
            if ($this->vtprd_is_var_name_in_list_check($i, $k, 'excl-actionPop', $vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_excl_array']) ) {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Variation Name exclusion found';
              return false;
            }
         }
         
    
         if (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_excl_array']) > 0) {
            if ($this->vtprd_are_brands_in_list_check($i, $k, 'excl', $vtprd_rules_set[$i]->action_group_population_info['action_group_brands_excl_array']) ) {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Variation Name exclusion found';
              return false;
            }
         }
         
         //v2.0.2.0 begin
         //if set to exclude only, we've passed all the tests!! 
         if ($vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] == 'excludeOnly') { 
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Groups inclusion OK, no exclusions found for an exclude-only ACTIONPOP rule';
            return true;
         }
         //v2.0.2.0 end
         
     } //v2.0.2.0 end

     //-----------
     //INCLUDES 
     //-----------
        
     //v2.0.2.0 wrap in general test
     if ( ($vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] == 'includeOnly') ||   
          ($vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] == 'both') ) {           
                       
         /* //v2.0.2.0  superceded
         //if set to exclude only, we've passed all the tests!! 
         if ($vtprd_rules_set[$i]->action_group_population_info['action_group_set_to_exclude_only']) {
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Groups inclusion OK, no exclusions found for an exclude-only ACTIONPOP rule';
            return true;
         } 
         */
         
         $and_tracking = 0;
     
         //EDITED * + * +  * + * +  * + * +  * + * +
    
         if ( (is_array($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_incl_array'])) && //will be spaces if no contents!!
              (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_incl_array']) > 0) ) {
            if ($this->vtprd_is_var_name_in_list_check($i, $k, 'incl-actionPop', $vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_incl_array']) ) {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Variation Name inclusion found';
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_and_or'] == 'and') {
                  $and_tracking++;
               }
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_and_switch_count'] == $and_tracking) {
                  return true;
               }                    
            } else {
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_and_or'] == 'and') {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Variation Name Not found (actionpop and 1)';
                  return false;
               }        
            }
         } else {
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_and_or'] == 'and') {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Variation Name Not found (actionpop and 2)';
                  return false;
               }     
         }
         
         if (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_incl_array']) > 0) {
            if ($this->vtprd_are_brands_in_list_check($i, $k, 'incl', $vtprd_rules_set[$i]->action_group_population_info['action_group_brands_incl_array']) ) {
              $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Brands inclusion found';
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_and_or'] == 'and') {
                  $and_tracking++;
               }
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_and_switch_count'] == $and_tracking) {
                  return true;
               }                    
            } else {
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_and_or'] == 'and') {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Brands Not found';
                  return false;
               }        
            }
         } else {
               if ($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_and_or'] == 'and') {
                  $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Reguired Brands Not found';
                  return false;
               }      
         }
      } //v2.0.2.0 end

      
      //FALLTHROUGH - not found
      $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Product Not in combined Categories or Role list';
      return false;
      
   } 
    
   public function vtprd_load_inPop_found_list($i,$k) {
    	global $vtprd_cart, $vtprd_rules_set, $vtprd_info;
      
     //error_log( print_r(  'vtprd_load_inPop_found_list ', true ) ); 
       
      //***********************
      //v1.1.0.6 begin
      //v1.1.1.2 reworked for multiples
      //***********************
      //reduce qty of candidate/free items to just what was purchased, if free items in cart
      
      //v2.0.0 begin
      if ($vtprd_info['current_processing_request'] == 'cart') {   
        //------------------------------------
        //v2.0.2.0 begin - code reworked   
        //$current_auto_add_array = $this->vtprd_get_current_auto_add_array();    
        //error_log( print_r(  'before vtprd_get_transient_cart_data  0018 ', true ) );
        $get_current_auto_add_array = vtprd_get_transient_cart_data ('current_auto_add_array');
        if ($get_current_auto_add_array)  {
            $current_auto_add_array = unserialize($get_current_auto_add_array);
        } else {
            $current_auto_add_array = array();
        }   
        //v2.0.2.0 end
      } else {
        $current_auto_add_array = array();
      }
      //v2.0.0 end

      $computed_qty          =  $vtprd_cart->cart_items[$k]->quantity; //initialize the value for fallthrough
      $computed_total_price  =  $vtprd_cart->cart_items[$k]->total_price; //initialize the value for fallthrough

      //v1.1.1.2 begin - reworked for multiple free
      //EDITED * + * +  * + * +  * + * +  * + * + 
      //v1.1.1.2 end
      
      //v1.1.0.6 end
      //***********************
       
       
     // $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['at_least_one_inPop_product_found_in_rule']  = 'yes';

      $vtprd_rules_set[$i]->inPop_found_list[] = array('prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                     //'prod_qty' => $vtprd_cart->cart_items[$k]->quantity,  //v1.1.0.6
                                                     //'prod_running_qty' => $vtprd_cart->cart_items[$k]->quantity,  //v1.1.0.6 
                                                       'prod_qty' => $computed_qty, //v1.1.0.6                                                                                                         
                                                       'prod_running_qty' => $computed_qty, //v1.1.0.6
                                                       'prod_unit_price' => $vtprd_cart->cart_items[$k]->unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price, 
                                                       'prod_total_price' => $computed_total_price,  //v1.1.1.2
                                                       'prod_running_total_price' => $computed_total_price,  //v1.1.1.2
                                                       'prod_cat_list' => $vtprd_cart->cart_items[$k]->prod_cat_list,
                                                       'rule_cat_list' => $vtprd_cart->cart_items[$k]->rule_cat_list,
                                                       'prod_id_cart_occurrence' => $k, //used to mark product in cart if failed a rule 
                                                       'product_variation_key' =>  $vtprd_cart->cart_items[$k]->product_variation_key //v1.0.8.6                                   
                                                      );
     $vtprd_rules_set[$i]->inPop_qty_total   += $computed_qty; //v1.1.0.6 
     $vtprd_rules_set[$i]->inPop_total_price += ($computed_qty *  $vtprd_cart->cart_items[$k]->unit_price); //v1.1.0.6
     $vtprd_rules_set[$i]->inPop_running_qty_total   += $computed_qty; //v1.1.0.6 
     $vtprd_rules_set[$i]->inPop_running_total_price += ($computed_qty *  $vtprd_cart->cart_items[$k]->unit_price); //v1.1.0.6

     if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
        $vtprd_cart->cart_items[$k]->product_in_rule_allowing_display = 'yes';     
     }
     
    //*****************************************************************************
    //EXPLODE out the cart into individual unit quantity lines for DISCOUNT processing
    //*****************************************************************************
    //for($e=0; $e < $vtprd_cart->cart_items[$k]->quantity; $e++) { //v1.1.0.6 
    for($e=0; $e < $computed_qty; $e++) { //v1.1.0.6             
      $vtprd_rules_set[$i]->inPop_exploded_found_list[] = array(
                                                       'prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                       'prod_qty' => 1,
                                                       'prod_unit_price' => $vtprd_cart->cart_items[$k]->unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price, 
                                                       'prod_db_unit_price_list' => $vtprd_cart->cart_items[$k]->db_unit_price_list,
                                                       'prod_db_unit_price_special' => $vtprd_cart->cart_items[$k]->db_unit_price_special,
                                                       'prod_id_cart_occurrence' => $k, //used to mark product in cart if failed a rule
                                                       'exploded_group_occurrence' => $e,
                                                       'prod_discount_amt'  => 0,
                                                       'prod_discount_applied'  => '',
                                                       'product_variation_key' =>  $vtprd_cart->cart_items[$k]->product_variation_key, //v1.0.8.6
                                                       'prod_unit_price_hold' =>  0 //v1.1.8.1  only filled on a 'currency exploder' row
                                                      );          
  //    $vtprd_rules_set[$i]->inPop_exploded_group_occurrence++;
      $vtprd_rules_set[$i]->inPop_exploded_group_occurrence = $e;
    } //end explode
    
    $vtprd_rules_set[$i]->inPop_prodIds_array [] = $vtprd_cart->cart_items[$k]->product_id; //used only when searching for sameAsInpop
      
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['inPop_participation_msgs'][] = 'Product participates in buy population';              
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_short_msg'] = $vtprd_rules_set[$i]->discount_product_short_msg;
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_full_msg']  = $vtprd_rules_set[$i]->discount_product_full_msg;    
  }
    
        
   public function vtprd_load_actionPop_found_list($i,$k) {
    	global $vtprd_cart, $vtprd_rules_set, $vtprd_info;
      
     //error_log( print_r(  'vtprd_load_actionPop_found_list ', true ) ); 

      
      //***********************
      //v1.1.0.6 begin
      //v1.1.1.2 reworked for multiples
      //***********************
      //reduce qty of candidate/free items to just what was purchased **IF NOT ON --matching-- AUTO ADD RULE
      
      //v2.0.0 begin
      if ($vtprd_info['current_processing_request'] == 'cart') {  
          //------------------------------------
          //v2.0.2.0 begin - code reworked          
          //$current_auto_add_array = $this->vtprd_get_current_auto_add_array();          
          //error_log( print_r(  'before vtprd_get_transient_cart_data  0019 ', true ) );
          $get_current_auto_add_array = vtprd_get_transient_cart_data ('current_auto_add_array');
          if ($get_current_auto_add_array)  {
              $current_auto_add_array = unserialize($get_current_auto_add_array);
          } else {
              $current_auto_add_array = array();
          }          
          //v2.0.2.0 end
      } else {
        $current_auto_add_array = array();
      }
      //v2.0.0 end
      
      
      $computed_qty          =  $vtprd_cart->cart_items[$k]->quantity; //initialize the value for fallthrough
      $computed_total_price  =  $vtprd_cart->cart_items[$k]->total_price; //initialize the value for fallthrough      


      //EDITED * + * +  * + * +  * + * +  * + * +
      
      //v1.1.1.2 end 
      
      //v1.1.0.6 end
      //***********************
                               
      //END product exclusions check
      
      $prod_unit_price = $vtprd_cart->cart_items[$k]->unit_price;
      //Skip if item already on sale and switch = no
      if ($vtprd_cart->cart_items[$k]->product_is_on_special == 'yes')  {
          if ( $vtprd_rules_set[$i]->cumulativeSalePricing == 'no') { 
            //product already on sale, can't apply further discount
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
            $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'No Discount - product already on sale, can"t apply further discount - discount in addition to sale pricing not allowed';
            return;
          } else {
            //overwrite the sale price with the original unit price when applying IN PLACE OF the sale price
            $prod_unit_price = $vtprd_cart->cart_items[$k]->db_unit_price;
          }         
     }
     
     //v1.1.8.1 BEGIN - BOGO buy group/get group remove dups
     //only works with comma separated list of RULE_IDs
    if ( apply_filters('vtprd_remove_buy_group_dups_from_get_group',FALSE) ) {
      $comma_separated_IDs = apply_filters('vtprd_remove_buy_group_members_from_get_group',FALSE);
      $ID_array = explode( ',', $comma_separated_IDs );
      $rule_id = $vtprd_rules_set[$i]->post_id;
      if (in_array($rule_id, $ID_array)) {
          $sizeof_inPop_found_list = sizeof($vtprd_rules_set[$i]->inPop_found_list);
          for($skip=0; $skip < $sizeof_inPop_found_list; $skip++) {            
              if ($vtprd_rules_set[$i]->inPop_found_list[$k]['prod_id'] ==
                  $vtprd_cart->cart_items[$k]->product_id ) {
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_status'] = 'rejected';
                $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['discount_msgs'][] =  'No Discount - product is in BUY group, filter instruction to remove dup received.';
                return;
              }                 
          }        
      }
    }          
     //v1.1.8.1 end

      $vtprd_cart->at_least_one_rule_actionPop_product_found = 'yes'; //mark rule for further processing
  
      $vtprd_rules_set[$i]->actionPop_found_list[] = array('prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                     //'prod_qty' => $vtprd_cart->cart_items[$k]->quantity,  //v1.1.0.6 
                                                     //'prod_running_qty' => $vtprd_cart->cart_items[$k]->quantity,  //v1.1.0.6  
                                                       'prod_qty' => $computed_qty,  //v1.1.0.6  
                                                       'prod_running_qty' => $computed_qty,  //v1.1.0.6 
                                                       'prod_unit_price' => $prod_unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price,
                                                       'prod_total_price' => $computed_total_price, //v1.1.1.2
                                                       'prod_running_total_price' => $computed_total_price, //v1.1.1.2
                                                       'prod_cat_list' => $vtprd_cart->cart_items[$k]->prod_cat_list,
                                                       'rule_cat_list' => $vtprd_cart->cart_items[$k]->rule_cat_list,
                                                       'prod_id_cart_occurrence' => $k, //used to access product in later processing
                                                       'product_variation_key' =>  $vtprd_cart->cart_items[$k]->product_variation_key //v1.0.8.6
                                                      );

     $vtprd_rules_set[$i]->actionPop_qty_total   += $computed_qty; //v1.1.0.6 
     $vtprd_rules_set[$i]->actionPop_total_price += ($computed_qty *  $vtprd_cart->cart_items[$k]->unit_price); //v1.1.0.6
     $vtprd_rules_set[$i]->actionPop_running_qty_total   += $computed_qty; //v1.1.0.6 
     $vtprd_rules_set[$i]->actionPop_running_total_price += ($computed_qty *  $vtprd_cart->cart_items[$k]->unit_price); //v1.1.0.6
          
     $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_short_msg'] = $vtprd_rules_set[$i]->discount_product_short_msg;
     $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['rule_full_msg']  = $vtprd_rules_set[$i]->discount_product_full_msg; 
          
     if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
        $vtprd_cart->cart_items[$k]->product_in_rule_allowing_display = 'yes';     
     }

              
    //*****************************************************************************
    //EXPLODE out the cart into individual unit quantity lines for DISCOUNT processing
    //*****************************************************************************
    //for($e=0; $e < $vtprd_cart->cart_items[$k]->quantity; $e++) {  //v1.1.0.6 
    for($e=0; $e < $computed_qty; $e++) {  //v1.1.0.6 
          
      /*  Marking no longer used.  Now use purchased_qty + free_qty
      //v1.1.0.6  begin
      // mark the latter part of the qty group, if some have been purchased
      if ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') {
        if (($mark_auto_insert_after_occurrence == 'all') ||
             ($e > $mark_auto_insert_after_occurrence))  {
          $product_free_auto_insert_candidate = 'yes';
        } else {
          $product_free_auto_insert_candidate = 'no';
        }
      } else {
        $product_free_auto_insert_candidate = ' ';
      }
      //v1.1.0.6 end
      */
      
      $vtprd_rules_set[$i]->actionPop_exploded_found_list[] = array('prod_id' => $vtprd_cart->cart_items[$k]->product_id,
                                                       'prod_name' => $vtprd_cart->cart_items[$k]->product_name,
                                                       'prod_qty' => 1,
                                                       'prod_unit_price' => $vtprd_cart->cart_items[$k]->unit_price,
                                                       'prod_db_unit_price' => $vtprd_cart->cart_items[$k]->db_unit_price, 
                                                       'prod_db_unit_price_list' => $vtprd_cart->cart_items[$k]->db_unit_price_list,
                                                       'prod_db_unit_price_special' => $vtprd_cart->cart_items[$k]->db_unit_price_special,
                                                       'prod_id_cart_occurrence' => $k, //used to mark product in cart if failed a rule
                                                       'exploded_group_occurrence' => $e,
                                                       'prod_discount_amt'  => 0,
                                                       'prod_discount_applied'  => '',
                                                       'product_variation_key' =>  $vtprd_cart->cart_items[$k]->product_variation_key, //v1.0.8.6
                                                       'prod_unit_price_hold' =>  0, //v1.1.8.1  only filled on a 'currency exploder' row
                                                       'this_is_a_currency_exploder_row' =>  false //v1.1.8.1  only on an actionpop row, only filled on the 2nd-nth 'currency exploder' row
                              //no longer used                         'product_free_auto_insert_candidate' =>  $product_free_auto_insert_candidate //v1.1.0.6
                                                      );          
                                                      
   //   $vtprd_rules_set[$i]->actionPop_exploded_group_occurrence++;
      $vtprd_rules_set[$i]->actionPop_exploded_group_occurrence = $e;
    } //end explode
  
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id]['actionPop_participation_msgs'][] = 'Product participates in action population';
  
   }
 
      
  public function vtprd_init_recursive_work_elements($i){ 
    global $vtprd_rules_set;
    $vtprd_rules_set[$i]->errProds_qty = 0 ;
    $vtprd_rules_set[$i]->errProds_total_price = 0 ;
    $vtprd_rules_set[$i]->errProds_ids = array() ;
    $vtprd_rules_set[$i]->errProds_names = array() ;    
  }
  public function vtprd_init_cat_work_elements($i){ 
    global $vtprd_rules_set;
    $vtprd_rules_set[$i]->errProds_cat_names = array() ;             
  }     


   public  function vtprd_sort_rules_set_for_cart() {
    global $vtprd_cart, $vtprd_rules_set;
     //error_log( print_r(  ' ', true ) );  
     //error_log( print_r(  'Function begin - vtprd_sort_rules_set_for_cart ', true ) ); 
 
    //v2.0.0 D Solution - NO GET - there could be an exploder, which would then no longer be there!!!!
    /*
     //ET RULES SET     
    $vtprd_rules_set = get_option( 'vtprd_rules_set' ); //v2.0.0 ADDED here to cover when
    */ 
 
    //***********************************************************
    //DELETE ALL "DISPLAY" RULES from the array for this iteration, leaving only the 'cart' rules
    //***********************************************************
     if ( sizeof($vtprd_rules_set) > 0) {    
        foreach ($vtprd_rules_set as $key => $rule )  {
           //v2.0.0 begin (changed to positive testing)
           if ( (isset($rule->rule_status)) && 
                ($rule->rule_status == 'publish') &&
                (isset($rule->rule_execution_type)) &&
                ($rule->rule_execution_type == 'cart') ) {
              $rule_is_ok = true; 
              //error_log( print_r(  'rule stays in set, ruleID=  ' .$rule->post_id. ' $key = ' .$key, true ) );     
           } else {
              unset( $vtprd_rules_set[$key]); 
              //error_log( print_r(  'rule deleted set, ruleID=  ' .$rule->post_id. ' $key = ' .$key, true ) );  
           }
           //v2.0.0 end                                          
        } 
              
        //reknit the array to get rid of any holes
        $vtprd_rules_set = array_values($vtprd_rules_set);  
     }    

      //error_log( print_r(  '$vtprd_rules_set Before sort', true ) );
      //error_log( var_export($vtprd_rules_set, true ) );  

     //****
     //SORT  if any rules are left...
     //****
     if ( sizeof($vtprd_rules_set) > 1) {
        $this->vtprd_sort_rules_set(); 
     } 

      //error_log( print_r(  '$vtprd_rules_set after sort', true ) );
      //error_log( var_export($vtprd_rules_set, true ) ); 
           
     return;
  }


   public  function vtprd_sort_rules_set_for_display() {
     global $vtprd_cart, $vtprd_rules_set;
      //error_log( print_r(  'Function begin - vtprd_sort_rules_set_for_display', true ) ); 
 
      //GET RULES SET     
     $vtprd_rules_set = get_option( 'vtprd_rules_set' ); //v2.0.0 ADDED  **CATALOG** rules during a CART run. - 'vtprd_rules_set' load **003**  


      //v2.0.1.0 begin
      $sizeof_rules_set = sizeof($vtprd_rules_set);         
      for($i=0; $i < $sizeof_rules_set; $i++) { 

        //skip **existing** invalid rules
        if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
          continue;  //skip out of this for loop iteration
        }
        
        $this->vtprd_manage_shared_rule_tests($i);
         
     } 
     //v2.0.1.0 end  
 
      //***********************************************************
      //DELETE ALL "CART" RULES from the array for this iteration, leaving only the 'display' rules
      //***********************************************************     
     if ( sizeof($vtprd_rules_set) > 0) {  
        foreach ($vtprd_rules_set as $key => $rule )  {
           //v2.0.0 begin (changed to positive testing)
           if ( (isset($rule->rule_status)) && 
                ($rule->rule_status == 'publish') &&
                (isset($rule->rule_execution_type)) &&
                ($rule->rule_execution_type == 'display') ) {
              $rule_is_ok = true;     
           } else {
              unset( $vtprd_rules_set[$key]); 
           }     
           //v2.0.0 end             
        } 
                        
        //reknit the array to get rid of any holes
        $vtprd_rules_set = array_values($vtprd_rules_set);  
     }
     
     //****
     //SORT   if any rules are left...
     //****
     if ( sizeof($vtprd_rules_set) > 1) {
        $this->vtprd_sort_rules_set(); 
     }
    
    return;
  }

   public  function vtprd_sort_rules_set() {
     global $vtprd_cart, $vtprd_rules_set;

      //http://stackoverflow.com/questions/3232965/sort-multidimensional-array-by-multiple-keys
      // excellent example here:   http://cybernet-computing.com/news/blog/php-sort-array-multiple-fields
      $rule_execution_type = array();
      $rule_contains_free_product = array();
      $ruleApplicationPriority_num = array();
      
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) { 
        
        //v2.0.1.0 begin
        //skip invalid rules
        if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) { 
          continue;  //skip out of this for loop iteration
        }
        //v2.0.1.0 end  
            
      //  $rule_execution_type[]          =  $vtprd_rules_set[$i]->rule_execution_type;
        $rule_contains_free_product[]   =  $vtprd_rules_set[$i]->rule_contains_free_product;
        $ruleApplicationPriority_num[]  =  $vtprd_rules_set[$i]->ruleApplicationPriority_num;
      }
      array_multisort(
      //  $rule_execution_type, SORT_DESC, //display / cart  
               
        $rule_contains_free_product, SORT_DESC,   // yes / no / [blank]]
        $ruleApplicationPriority_num, SORT_ASC,     // 0 => on up
        
			  $vtprd_rules_set  //applies all the sort parameters to the object in question
      );
      
    return;
  }
  
  
   public  function vtprd_init_cartAuditTrail($i,$k) {
    global $vtprd_cart, $vtprd_rules_set;  
    $vtprd_cart->cart_items[$k]->cartAuditTrail[$vtprd_rules_set[$i]->post_id] = array(  
          'ruleset_occurrence'          => $i,
          'inPop'                       => $vtprd_rules_set[$i]->inPop, 
          'inPop_prod_cat_found'        => '' ,   
          'inPop_rule_cat_found'        => '' ,
          'inPop_and_required'          => '' ,  
          'userRole'            				=> '' ,
          'inPop_role_found'            => '' ,  
          'inPop_single_found'          => '' , 
          'inPop_variation_found'       => '' ,
          'at_least_one_inPop_product_found_in_rule' => '' ,          
          'product_in_inPop'            => '' ,  
          
          'actionPop'                   => $vtprd_rules_set[$i]->actionPop,   
          'actionPop_prod_cat_found'    => '' ,  
          'actionPop_rule_cat_found'    => '' ,
          'actionPop_and_required'      => '' ,  
          'actionPop_role_found'        => '' , 
          'actionPop_single_found'      => '' ,  
          'actionPop_variation_found'   => '' ,
          'product_in_actionPop'        => '' ,
                      
          'rule_priority'               => '',    // y/n
          
          'discount_status'             => '',
          'discount_msgs'               => array(),
          'discount_amt'                => '',
          'discount_pct'                => '',
          
          // if 'product_in_actionPop' == yes, messages are filled in
          'rule_short_msg'              => '' ,
          'rule_full_msg'               => ''       
    ); 
 
    return;   
  }
                                       

 
  //***********************************************************
/*
  // v1.1.0.6  REFACTORED 
NO LONGER NECESSARY - ACTIONPOP LOAD IGNORES NON-PURCHASED STUFF IF ***NOT*** IN TEH AUTO-ADD RULE!!!!!!
  // If a product was auto inserted for a free discount, but does *not*
  //     receive that discount,
  //   Roll the auto-added product 'UNfree' qty out of the all of the rules actionPop array
  //      AND out of vtprd_cart, removing the product entirely if necessary.
  //***********************************************************    
   public  function vtprd_maybe_roll_out_auto_inserted_products($i) {
		global $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule;     

    
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    } 
    
    
     //if no array, nothing done!
     if (isset($_SESSION['current_auto_add_array'])) {
       $current_auto_add_array = unserialize($_SESSION['current_auto_add_array']);
     } else {
       return:
     }
     
     //no rollouts if all candidates became free!
     if ($current_auto_add_array['current_qty'] == ($current_auto_add_array['purchased_qty'] == 0) {
       return;
     }

    //compute qty to be removed, if any: subtract free qty from auto added qty
    $remove_auto_add_qty = $current_auto_add_array['candidate_qty'] - $current_auto_add_array['free_qty'];
    
    //***************************************************************
    //remove the remainder $remove_auto_add_qty from ALL **actionpop lists **
    //***************************************************************
    $sizeof_ruleset = sizeof($vtprd_rules_set);
    for($rule=0; $rule < $sizeof_ruleset; $rule++) {

      $delete_qty = $remove_auto_add_qty;
      foreach ($vtprd_rules_set[$rule]->actionPop_exploded_found_list as $actionPop_key => $actionPop_exploded_found_list )  {
         if ($actionPop_exploded_found_list['prod_id'] == $current_auto_add_array['free_product_id']) {            
            //as each row has a quantity of 1, unset is the way to go....
            //from  http://stackoverflow.com/questions/2304570/how-to-delete-object-from-array-inside-foreach-loop
            unset( $vtprd_rules_set[$rule]->actionPop_exploded_found_list[$actionPop_key]);                       
            $delete_qty -= 1;
         }         
         if ($delete_qty == 0) {
           break;
         }
      } //end "for" loop unsetting the free product
      
      //if any unsets were done, need to re-knit the array so that there are no gaps...
      //    from    http://stackoverflow.com/questions/1748006/what-is-the-best-way-to-delete-array-item-in-php/1748132#1748132
      //            $a = array_values($a);
      if ($delete_qty != $remove_auto_add_qty) {          
        $vtprd_rules_set[$rule]->actionPop_exploded_found_list = array_values($vtprd_rules_set[$rule]->actionPop_exploded_found_list);
      }
    
    } //end "for"  rule loop
    
    //***************************************************************
    //remove the $remove_auto_add_qty from **$vtprd_cart** !! 
    //***************************************************************
    $removed_row_qty = 0;
    foreach($vtprd_cart->cart_items as $key => $cart_item) {      
      if ($cart_item->product_id != $current_auto_add_array['free_product_id']) {
        continue;
      }
            
      $cart_item->quantity -= $remove_auto_add_qty;
      
      if ($cart_item->quantity <= 0) {
        unset($vtprd_cart->cart_items[$key]);
        $removed_row_qty++;
        //**************************************************
      }  else  {
        $cart_item->total_price = $cart_item->quantity * $cart_item->unit_price;      
      }
      
      //once the product_id has been processed, we're all done...
      break;
      
    }  //end foreach
        
    if ($removed_row_qty > 0) {          
      //re-knit the array as needed
      $vtprd_cart->cart_items = array_values($vtprd_cart->cart_items);
    } 

       
    return;
  }  
*/
                                       
  
  //***********************************************************
  // If a product(s) has been given a 'Free' discount, it can't get
  //     any further discounts.
  //   Roll the product 'free' qty out of the rest of the rules actionPop arrays
  //      so that they can't be found when searching for other discounts
  //***********************************************************     
   public  function vtprd_roll_free_products_out_of_other_rules($i) {
		global $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule;     

    $sizeof_ruleset = sizeof($vtprd_rules_set);
    
    //for this rule's free_product_array, roll out these products from all other rules...
    foreach($vtprd_rules_set[$i]->free_product_array as $free_product_key => $free_qty) {  
      
      for($rule=0; $rule < $sizeof_ruleset; $rule++) {

        //skip if we're on the rule initiating the free product array logic
        if ( ($vtprd_rules_set[$rule]->post_id == $vtprd_rules_set[$i]->post_id) ||      //1.0.5.1
             ($vtprd_rules_set[$rule]->rule_status != 'publish') ) {                     //1.0.5.1 added in != 'publish' test
          continue; 
        }
        
        //delete as many of the product from the actionpop array as there are free qty
        $delete_qty = $free_qty;
        foreach ($vtprd_rules_set[$rule]->actionPop_exploded_found_list as $actionPop_key => $actionPop_exploded_found_list )  {
           if ($actionPop_exploded_found_list['prod_id'] == $free_product_key) {
              
              //as each row has a quantity of 1, unset is the way to go....
              //from  http://stackoverflow.com/questions/2304570/how-to-delete-object-from-array-inside-foreach-loop
              unset( $vtprd_rules_set[$rule]->actionPop_exploded_found_list[$actionPop_key]);           
              
              $delete_qty -= 1;
           }
           
           if ($delete_qty == 0) {
             break;
           }
           
        } //end "for" loop unsetting the free product
        
        //if any unsets were done, need to re-knit the array so that there are no gaps...
        //    from    http://stackoverflow.com/questions/1748006/what-is-the-best-way-to-delete-array-item-in-php/1748132#1748132
        //            $a = array_values($a);
        if ($delete_qty < $free_qty) {          
          $vtprd_rules_set[$rule]->actionPop_exploded_found_list = array_values($vtprd_rules_set[$rule]->actionPop_exploded_found_list);
        }
      
      } //end "for"  rule loop
      
    } //end foreach free product
    
    return;
  }  



  /*******************************************************  
  //v1.1.0.6 REFACTORED!!!  
  //v1.1.1.2 reworked to handle multiple autoadds  
  ******************************************************** */
	public function vtprd_pre_process_cart_for_autoAdds(){
  
    //EDITED * + * +  * + * +  * + * +  * + * +
    
  } //end  vtprd_pre_process_cart_for_autoAdds


  
  //++++++++++++++++++++++++++++++
  //v1.1.0.6 Commented out
  //++++++++++++++++++++++++++++++
  /*  ***************************************************************************************
   AUTO add - test here if inpop criteria reached for auto add rule
    InPop has JUST been loaded, and THIS rule is an auto-add rule,
            (a) is the product already in cart somewhere - 
                if not ADD it right now...
            (b) If SO, will its quantity suffice or should it be increased
        (if the auto add switch is on, free products are always auto-added, 
                regardless of whether that product is already in the cart...)
       ***************************************************************************************                    
  */
  //$i = rule index, $d = deal index, $k = product index
/*
  public function vtprd_maybe_auto_add_to_vtprd_cart($i, $d, $free_product_id) {
  
error_log( print_r(  'Entry vtprd_maybe_auto_add_to_vtprd_cart', true ) );
error_log( print_r(  '$i = ' .$i, true ) );
error_log( print_r(  '$d = ' .$d, true ) );


    $purchased_qty = 0;
    $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
    for($c=0; $c < $sizeof_cart_items; $c++) {  
       if ($vtprd_cart->cart_items[$c]->product_id == $free_product_id) {
         $free_product_status = 'found';
         break; //breaks out of this for loop
       }
    }  
   
    //UPD existing inpop qty  or  ADD to Cart and ActionPop
error_log( print_r(  'FROM apply-rules.php  function vtprd_maybe_auto_add_to_vtprd_cart', true ) );
error_log( print_r(  '$free_product_id= ' .$free_product_id, true ) );
error_log( print_r(  '$free_product_status=  ' .$free_product_status, true ) );


    //free_product_qty is ALWAYS 1!!!!
    if ($free_product_status == 'found') {
      //updates to both inpop and inpop exploded...
      $purchased_qty = $vtprd_cart->cart_items[$c]->quantity;
      //ADD to $vtprd_cart
      $vtprd_cart->cart_items[$c]->quantity++; 
      $vtprd_cart->cart_items[$c]->total_price  +=  $vtprd_cart->cart_items[$c]->unit_price
      $vtprd_cart->cart_items[$c]->product_auto_insert_state = 'candidate';
      
error_log( print_r(  'vtprd_maybe_auto_add_to_vtprd_cart  001', true ) );
    } else { 
      $price_add_to_total = $this->vtprd_auto_add_to_vtprd_cart($free_product_id, 1, $i);    
error_log( print_r(  'vtprd_maybe_auto_add_to_vtprd_cart  002', true ) );
    }


  
    return $purchased_qty;
     
  }
*/

  //******************************
  //insert/delete free stuff as warranted...
  //v1.1.0.6  refactored
  //v1.1.1.2 refactored for multiple auto adds 
  //******************************	
	public function vtprd_post_process_cart_for_autoAdds(){ 

    //EDITED * + * +  * + * +  * + * +  * + * +
               
  }  //end vtprd_post_process_cart_for_autoAdds  
  
     
        
   //**********************************
   //v1.1.0.6 new function 
   //  Only 1 ITERATION of auto add free candidate COUNT is added during pre_processing
   //   all others are added HERE, as needed, based on the $br and $ar REPEATS
   //v1.1.1.2 begin - reworked for multiple free 
   //  $current_auto_add_array for this product created in pre_processing
   //**********************************       
   public function vtprd_add_free_item_candidate($i) {
   
    //EDITED * + * +  * + * +  * + * +  * + * + 
    
  }                                                       
     
   //v2.0.2.0  FUNCTION MOVED to parent-functions.php     
   //**********************************
   //v1.1.0.6 new function
   //v1.1.1.2 begin - reworked for multiple free  
   /*
    now acceepts  $previous_auto_add_array,$all_or_single,$single_key
    if 'all', process whole $previous_auto_add_array
    if 'single' only process supplied $single_key
   */
   //**********************************                                 
   
   /*
   public function vtprd_maybe_roll_out_prev_auto_insert_from_woo_cart($previous_auto_add_array, $all_or_single, $single_key=none) {      
      global $woocommerce;
      
     //error_log( print_r(  'vtprd_maybe_roll_out_prev_auto_insert_from_woo_cart ', true ) ); 
 

      
      //v2.0.1.0 begin
      //if auto add done prior to login, and then login occurs, can throw fatal if object not there.
      if (!is_object($woocommerce))  { 
        return;
      }
      //v2.0.1.0 end
      
      $cart_updated = false; //v1.1.1.2
      $woocommerce_cart_contents = $woocommerce->cart->get_cart(); 
      foreach($woocommerce_cart_contents as $key => $cart_item) {

        if ($cart_item['variation_id'] > ' ') {      
            $cart_product_id    = $cart_item['variation_id'];
        } else { 
            $cart_product_id    = $cart_item['product_id']; 
        } 
        
        if ($all_or_single == 'single') { 
            if ($single_key != $cart_product_id) {
              continue;  //skip if not = to supplied single key
            }
        }      
             
        //if ($previous_auto_add_array['free_product_id'] == $cart_product_id) { 
        if (isset($previous_auto_add_array[$cart_product_id] )) { 

         $previous_auto_add_array_row = $previous_auto_add_array[$cart_product_id];
 
         //SKIP this product if no free qty
         if ($previous_auto_add_array_row['free_qty']  <= 0) {
            continue;
         }
                  
         $current_total_quantity =  ($cart_item['quantity'] - $previous_auto_add_array_row['free_qty']); 

         if ($current_total_quantity <= 0) {

            $woocommerce->cart->set_quantity($key,0,false); //set_quantity = 0 ==> delete the product

         } else {

            $woocommerce->cart->set_quantity($key,$current_total_quantity,false); //false = don't refresh totals

         }
         
         $cart_updated = true; //v1.1.1.2
         
        //v1.1.1.2  need to keep running for multiples
        //break; //break out of for each

       } 
        
      } //end foreach  
      
        //v1.1.1.2 new
        if ($cart_updated) {
              //v1.0.9.3 - mark call as internal only - 
          //	accessed in parent-cart-validation/ function vtprd_maybe_before_calculate_totals
          $_SESSION['internal_call_for_calculate_totals'] = true;   
            
          $woocommerce->cart->calculate_totals();
          
         //v2.0.2.0 begin
         if ( ($woocommerce->cart->cart_contents_total == 0) &&
              ($woocommerce->cart->cart_contents_count == 0) ) { 
           error_log( print_r(  'woo_cart_contents_with_auto_add DEL 0004 ', true ) );
           vtprd_del_transient_cart_data_by_data_type ( 'woo_cart_contents_with_auto_add' );  //v2.0.2.0  remove existing data
         }
         //v2.0.2.0 end
      }  

      return;
   } 
   */
       
              
   //**********************************
   //v1.1.0.6 new function 
   //**********************************
   public function vtprd_turn_off_auto_add_in_progress() { 
     //EDITED * + * +  * + * +  * + * +  * + * +
   } 

  /* v2.0.2.0 removed        
   //**********************************
   //v1.1.0.6 new function
   //v1.1.1.2 begin - reworked for multiple free 
   //**********************************
   public function vtprd_maybe_remove_previous_auto_add_array() { 
     global $vtprd_info;
     //clear out the previous variable for use in next iteration, as needed
     $previous_auto_add_array = array();
     $_SESSION['previous_auto_add_array'] = serialize($previous_auto_add_array);
   } 
    */
            
  /* v2.0.2.0 removed
   //**********************************
   //v1.1.0.6 new function 
   //**********************************
   public function vtprd_get_previous_auto_add_array() { 
      global $vtprd_info;
      
     //error_log( print_r(  'vtprd_get_previous_auto_add_array ', true ) ); 
       
      if (isset($_SESSION['previous_auto_add_array']))  {
         $previous_auto_add_array = unserialize($_SESSION['previous_auto_add_array']);
      } else {
         //session var may have expired due to age.  If in vtprd_info, use that version!
         //****************************************
         // IF BOTH are gone, Free stuff DISAPPEARS and becomes a purchase.
         //****************************************
         if ( is_array($vtprd_info['previous_auto_add_array']) ) {
            $previous_auto_add_array = $vtprd_info['previous_auto_add_array'];
         } else {
            $previous_auto_add_array = array(); //v1.1.1.2
         }         
      }
      
      return $previous_auto_add_array; 
   }  
   */
 
 /* v1.1.1.2  removed, no longer in use          
   //**********************************
   //v1.1.0.6 new function 
   //**********************************
   public function vtprd_init_previous_auto_add_array() { 
       $previous_auto_add_array = array (       
          'free_product_id' => '',
          'free_product_add_action_cnt' => '', 
          'free_product_in_inPop' => '',
          'free_rule_actionPop' => '',      
          'rule_id' => '',
          'current_qty' => '', 
          'purchased_qty' => '',
          'candidate_qty' => '',
          'free_qty' => '',
          'variations_parameter' => ''                  
      );
      
      return $previous_auto_add_array; 
   }     
 */   
               
   /*  v2.0.2.0 removed
   //**********************************
   //v1.1.0.6 new function 
   //**********************************
   public function vtprd_get_current_auto_add_array() { 
      
     //error_log( print_r(  'vtprd_get_current_auto_add_array ', true ) ); 
    
      if (isset($_SESSION['current_auto_add_array']))  {
         $current_auto_add_array = unserialize($_SESSION['current_auto_add_array']);
      } else {
         //v1.1.1.2 begin  -  handle multiples!
         //$current_auto_add_array = $this->vtprd_init_auto_add_array_row();
         $current_auto_add_array = array();
         //v1.1.1.2 end
      }
      
      return $current_auto_add_array; 
   } 
   */   
           
   //**********************************
   //v1.1.1.2 begin - reworked for multiple free 
   //v1.1.0.6 new function 
   //**********************************
   public function vtprd_init_auto_add_array_row() { 

      //EDITED * + * +  * + * +  * + * +  * + * + 
   }     
    
   /* v2.0.2.0 removed
   //**********************************
   //v1.1.0.6 new function 
   //v1.1.1.2 begin - reworked for multiple free 
   //**********************************
   public function vtprd_maybe_remove_current_auto_add_array() { 
     //clear out the current variable for use in next iteration, as needed
     $current_auto_add_array = array();
     $_SESSION['current_auto_add_array'] = serialize($current_auto_add_array);
   }
   */    

   //**********************************
   //v1.1.0.6 new function
   //put potentially free product at end of cart!!
   //**********************************
   public function vtprd_sort_vtprd_cart_autoAdd_last() { 
     //EDITED * + * +  * + * +  * + * +  * + * + 
   }
 
  //************
  //AUTO ADD to vtprd-cart, only used for free items... 
  //************
	public function vtprd_auto_add_to_vtprd_cart($free_product_id, $free_product_to_be_added_qty, $i) {
     //EDITED * + * +  * + * +  * + * +  * + * +       
  }

 
 
   //*******************************************************
   //v1.0.8.4 new function
   //  used in following rule processing iterations, if cumulativeRulePricing == 'no'
   //*******************************************************
   public  function vtprd_mark_products_in_an_all_rule($i) {
		  global $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule; 
      
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      $sizeof_actionPop_found_list = sizeof($vtprd_rules_set[$i]->actionPop_found_list);
      
      for($a=0; $a < $sizeof_actionPop_found_list; $a++) {            
          for($k=0; $k < $sizeof_cart_items; $k++) { 
             //v1.1.7.1a begin - 'if' altered, added last 2 lines
             // check *not only* actionpop found list, but ALSO verify that the rule actually granted a discount to the product!
             if ( ($vtprd_cart->cart_items[$k]->product_id == $vtprd_rules_set[$i]->actionPop_found_list[$a]['prod_id']) &&
                  (sizeof($vtprd_cart->cart_items[$k]->yousave_by_rule_info) > 0 ) &&
                  (in_array($vtprd_rules_set[$i]->post_id, $vtprd_cart->cart_items[$k]->yousave_by_rule_info )) ) { 
             //v1.1.7.1a end
                $vtprd_cart->cart_items[$k]->product_already_in_an_all_rule = 'yes'; 
             }
          }
      }
   }      
 
 
 
   //*******************************************************
   //v1.1.7.1a new function - RULE EXPLODER - create exploded single rules
      /* if ( $vtprd_rules_set[$i]->rule_deal_info[$d]['buy_amt_applies_to'] == 'each') 
        Explode buy group out to INDIVIDUAL RULES per PRODUCT in the CART if part of ORIGINAL RULE POP
        TEMPORARILY DELETING the original rule BY changing the status to TRASH
        
        In the exploded rule, allow the post_id to DUPLICATe - this will be OK in processing, and ALSO allow the filters to work correctly.
      */
    //v2.0.0 reconfigured for new rule structures  
   //*******************************************************
   public  function vtprd_explode_applies_to_each_rules() {
		  global $vtprd_cart, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $vtprd_rule, $vtprd_edit_arrays_framework; //v2.0.0 added $vtprd_edit_arrays_framework 
      
      if ( sizeof($vtprd_rules_set) <= 0) { 
        return;           
      } 
      
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }
      
      
      //v2.0.2.0 begin
      $cart_id = $_SESSION['vtprd_unique_cart_id'];
      $vtprd_rules_set2 = array();
      //$_SESSION['rules_set2'] = serialize($vtprd_rules_set2); 
      $set_rules_set2 = serialize($vtprd_rules_set2);
      //error_log( print_r(  'set 0014 ', true ) );
      vtprd_set_transient_cart_data ( 'vtprd_rules_set2', $set_rules_set2, $cart_id );
      //v2.0.2.0 end 
                  
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) {  
         if ( ( $vtprd_rules_set[$i]->rule_execution_type == 'cart') &&
              ( $vtprd_rules_set[$i]->rule_status == 'publish' ) && 
              ( $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_applies_to'] == 'each' ) &&
              ( $vtprd_rules_set[$i]->actionPop == 'sameAsInPop') ) {  //discount group same as buy group
            //$vtprd_rule_post_id = $vtprd_rules_set[$i]->post_id;
            //v2.0.0 begin
            //SAve so we can RESET the rule object!, changing $vtprd_rule changes the ruleset!!
            $save_post_id                      =  $vtprd_rules_set[$i]->post_id;
            $save_buy_group_population_info    =  $vtprd_rules_set[$i]->buy_group_population_info;
            $save_action_group_population_info =  $vtprd_rules_set[$i]->action_group_population_info;
            $save_inPop                        =  $vtprd_rules_set[$i]->inPop;
            $vtprd_rule = $vtprd_rules_set[$i];   
            //v2.0.0 end         
         } else {
            continue;           
         } 
         
    /* 
    error_log( print_r(  'Top of exploder list, $i= ' .$i. ' post_id= ' .$vtprd_rules_set[$i]->post_id, true ) );
    error_log( print_r(  'buy_group_product_incl_array= ' , true ) ); 
    error_log( var_export($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'], true ) ); 
    */        
         /* v2.0.0 removed
         if ($vtprd_rules_set[$i]->inPop == 'single') {
            //already single, done with this rule
            continue;         
         } 
         */ 
         
         $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
         
         $exploded_cnt = 1;
         $rule_exploded = false;

         switch( $vtprd_rules_set[$i]->inPop ) {  //against the FOREACH value
              case 'wholeStore':                                                                                      
              case 'cart':  
                  
                  
                  for($k=0; $k < $sizeof_cart_items; $k++) {                                    
                      //**************
                      //reset all select criteria
                      //**************
                      $vtprd_rule->buy_group_population_info    = $vtprd_edit_arrays_framework['buy_group_framework']; //v2.0.0 
                      $vtprd_rule->action_group_population_info = $vtprd_edit_arrays_framework['action_group_framework']; //v2.0.0 
                      
                      //load prod_id into select
                      $vtprd_rule->buy_group_population_info['buy_group_product_incl_array'][] = $vtprd_cart->cart_items[$k]->product_id; //v2.0.0
                      $vtprd_rule->inPop = 'groups'; //v2.0.0
                                   
                      $vtprd_rule->rule_processing_msgs[] =  'EXPLODED RULE for product id= ' .$vtprd_cart->cart_items[$k]->product_id; //v2.0.0             
                      $vtprd_rule->post_id .= '-expl-' .$exploded_cnt;
        
                      
                       //v2.0.2.0 begin
                      //error_log( print_r(  'before vtprd_get_transient_cart_data  0026 ', true ) );
                      $get_rules_set2 = vtprd_get_transient_cart_data ( 'vtprd_rules_set2', $cart_id );
                      //$vtprd_rules_set2 = unserialize($_SESSION['rules_set2']); 
                      $vtprd_rules_set2 = unserialize($get_rules_set2);                      
                      //v2.0.2.0 end                      
                                            
                      $vtprd_rules_set2[] = $vtprd_rule;
                      
                      //v2.0.2.0 begin
                      //$_SESSION['rules_set2'] = serialize($vtprd_rules_set2); 
                      $set_rules_set2 = serialize($vtprd_rules_set2);
                      //error_log( print_r(  'set 0015 ', true ) );
                      vtprd_set_transient_cart_data ( 'vtprd_rules_set2', $set_rules_set2, $cart_id );
                      //v2.0.2.0 end                       
        
                      //v2.0.0 begin
                      //RESET the rule object!, changing $vtprd_rule changes the ruleset!!
                      $vtprd_rule = $vtprd_rules_set[$i]; 
                           
                      $vtprd_rule->post_id = $save_post_id;
                      $vtprd_rule->buy_group_population_info    = $save_buy_group_population_info;
                      $vtprd_rule->action_group_population_info = $save_action_group_population_info;
                      $vtprd_rule->inPop                        = $save_inPop;                                 
                      $vtprd_rule->rule_processing_msgs = array();
                      //v2.0.0 end

        
                      $exploded_cnt++; 
                      $rule_exploded = true; 
       //error_log( print_r(  'Exploded rule created1, product= ' .$vtprd_cart->cart_items[$k]->product_id, true ) );                                                    
                  }      
                break;
              default: //groups path
                  for($k=0; $k < $sizeof_cart_items; $k++) {                                    
     /*                 
    error_log( print_r(  'vtprd_rule set in cart loop, buy_group_product_incl_array= ' , true ) ); 
    error_log( var_export($vtprd_rule->buy_group_population_info['buy_group_product_incl_array'], true ) );
    error_log( print_r(  'CALLING vtprd_is_product_in_inPop_group FROM vtprd_explode_applies_to_each_rules', true ) );
    */                   
                    if ( $this->vtprd_is_product_in_inPop_group($i, $k) ) {

                      //reset all select criteria
                      $vtprd_rule->buy_group_population_info    = $vtprd_edit_arrays_framework['buy_group_framework']; //v2.0.0 
                      $vtprd_rule->action_group_population_info = $vtprd_edit_arrays_framework['action_group_framework']; //v2.0.0 
                      
                      //load prod_id into select
                      $vtprd_rule->buy_group_population_info['buy_group_product_incl_array'][] = $vtprd_cart->cart_items[$k]->product_id;
                                   
                      $vtprd_rule->rule_processing_msgs[] =  'EXPLODED RULE for product id= '  .$vtprd_cart->cart_items[$k]->product_id; //v2.0.0              
                      $vtprd_rule->post_id .= '-expl-' .$exploded_cnt;

                       //v2.0.2.0 begin
                      //error_log( print_r(  'before vtprd_get_transient_cart_data  0027 ', true ) );
                      $get_rules_set2 = vtprd_get_transient_cart_data ( 'vtprd_rules_set2', $cart_id );
                      //$vtprd_rules_set2 = unserialize($_SESSION['rules_set2']); 
                      $vtprd_rules_set2 = unserialize($get_rules_set2);                      
                      //v2.0.2.0 end 
                      
                      $vtprd_rules_set2[] = $vtprd_rule;
                      
                      //v2.0.2.0 begin
                      //$_SESSION['rules_set2'] = serialize($vtprd_rules_set2); 
                      $set_rules_set2 = serialize($vtprd_rules_set2);
                      //error_log( print_r(  'set 0016 ', true ) );
                      vtprd_set_transient_cart_data ( 'vtprd_rules_set2', $set_rules_set2, $cart_id );
                      //v2.0.2.0 end 
                              
                      //v2.0.0 begin
                      //RESET the rule object!, changing $vtprd_rule changes the ruleset!!
                      $vtprd_rule = $vtprd_rules_set[$i];      
                      $vtprd_rule->post_id = $save_post_id;
                      $vtprd_rule->buy_group_population_info    = $save_buy_group_population_info;
                      $vtprd_rule->action_group_population_info = $save_action_group_population_info;          
                      $vtprd_rule->rule_processing_msgs = array();
                      //v2.0.0 end
        
                      $exploded_cnt++; 
                      $rule_exploded = true;    
       //error_log( print_r(  'Exploded rule created2, product= ' .$vtprd_cart->cart_items[$k]->product_id, true ) );                                                          
                    }
                  }  
                break;

            } //end switch 

       
          //end this for loop iteration
          if ($rule_exploded) {
            $vtprd_rules_set[$i]->rule_status = 'trash';  //REMOVE "PARENT" RULE for the duration of this iteration of apply-rules, only allow exploded single rules
          }
      
      } //end  'for' loop

       //v2.0.2.0 begin
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0028 ', true ) );
      $get_rules_set2 = vtprd_get_transient_cart_data ( 'vtprd_rules_set2', $cart_id );
      //$vtprd_rules_set2 = unserialize($_SESSION['rules_set2']); 
      $vtprd_rules_set2 = unserialize($get_rules_set2);                      
      //v2.0.2.0 end                      

      //ADD generated RULES to ruleset
      if ( sizeof($vtprd_rules_set2) > 0) {         
        foreach ($vtprd_rules_set2 as $key => $rule )  {     
           $vtprd_rules_set[] = $rule;      
        }  
      }

      vtprd_del_transient_cart_data_by_data_type ('vtprd_rules_set2', $cart_id);   //v2.0.2.0     
      
      //v2.0.1.0 begin
      if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ) {  
         error_log( print_r(  '$vtprd_rules_set at END OF vtprd_explode_applies_to_each_rules', true ) );
         error_log( var_export($vtprd_rules_set, true ) );
      }
      //v2.0.1.0 end
      
      return;
      
   } //end EACH EXPLODE function      
  
   
  
/*
ADDTIONAL RULE CRITERIA FILTER - Execution example

add_filter('vtprd_additional_inpop_include_criteria', 'process_additional_inpop_include_criteria', 10, 3);

function process_additional_inpop_include_criteria ($return_status, $i, $k) {
  global $vtprd_cart, $vtprd_rules_set, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
  $return_status = TRUE;
  
  //$vtprd_rules_set[$i]->post_id = Rule ID
  //$vtprd_cart is the cart contents ==> look at  core/vtprd-cart-classes.php  for cart contents structure
  //   and check this document for examples of how to access the cart data items.

                
    //v1.1.7.1a begin
    // Rule becomes an EXPLODED RULE, if 'buy amount applies to: each'  AND  'discount group same as buy group'
    // if a given rule is EXPLODED, test ONLY that rule differently
    // switch( TRUE ) { 
    //  case (strpos($vtprd_rules_set[$i]->post_id,'001') !== false):    //rule id 001
         //  **do add-on-criteria test [keep as comment]
         //  *if failed test, [keep as comment]
    //      $return_status = FALSE;                      
    //  break;
    // }
    //v1.1.7.1a end
                  
  
  switch( $vtprd_rules_set[$i]->post_id ) { 
     //ONLY test those ids for which additional criteria is needed
     case '001':    //rule id 001
         //  **do add-on-criteria test
         //  *if failed test,
             $return_status = FALSE;                      
        break;
     case '002':    etc
                 
        break;        
  }


  return $return_status;
}

*/

  //*************************
  // v1.1.7.2 new function
  // v2.0.1.0 revamp
  //*************************
  public  function vtprd_get_current_user_roles() {
    global $current_user; 
    
    if( is_user_logged_in() ) {
 
      if ( !$current_user )  {
        $current_user = wp_get_current_user();
      }   
      
      // from https://pluginrepublic.com/get-current-user-role-in-wordpress/
      $user_roles = ( array ) $current_user->roles;
    } else {
      $user_roles = array();
      $user_roles[0] = 'notLoggedIn';
    }  
    
    return $user_roles;
  }        
   
} //end VTPRD_Apply_Rules class

//v2.0.0 begin
  /*
      The following CLASS extension would shut off the automatic application of rules.
      Used for SHORTCODE validation
      
      Then we can use these  functions directly in that extended class:
          vtprd_is_product_in_inPop_group
          vtprd_is_product_in_actionPop_group
  */

  class VTPRD_Apply_Rule_Messaging extends VTPRD_Apply_Rules {
  	public function __construct(){
        //this empty construct overrides the automatic application of rules in the original class
  	}

  } //end VTPRD_Apply_Rule_Messaging class
//v2.0.0 end
