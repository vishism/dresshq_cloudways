<?php
                     
	/*******************************************************  
 	     The session variable for this product will already have been
 	     stored during the catalog display of the product price 
          (similar pricing done in vtprd-auto-add.php...)       
  ******************************************************** */
	function vtprd_load_vtprd_cart_for_processing(){
 
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
      
  //error_log( print_r(  'BEGIN vtprd_load_vtprd_cart_for_processing', true ) );

     // from Woocommerce/templates/cart/mini-cart.php  and  Woocommerce/templates/checkout/review-order.php

      $woocommerce_cart_contents = $woocommerce->cart->get_cart();
      
      $calculator_product_in_cart = false; //v1.1.1
      $addons_product_in_cart     = false; //v1.1.1

      if (sizeof($woocommerce_cart_contents) > 0) { 

          //-----------------------
          //v2.0.2.0 begin
          //-----------------------
          /*
          
          LOGIC
          *****
          by this time, we're loading a cart for discount testing
           - any previous auto_adds have NOT been rolled out of the cart
          
           - Logged-in?
           - 1st time through?
                yes - check if old rows hanging about for user?
           
          /-----------------------*******  
             
                    FOR MORE INFO, look at the comment above function vtprd_update_on_login_change 

          /-----------------------*******
          
          $cookie[0] = customer_id
          Woo creates an unique session ID for the not logged in customer, creates a cookie.
          When the user logs in, that cookie is copied over to a NEW ID, and the old is saved off.
          For the logged-in user, $customer_id is a combination of actual user_id + a hash In function generate_customer_id()
          */
          if (!isset($_SESSION['vtprd_unique_cart_id']) ) {
            vtprd_find_or_set_unique_cart_id();
          }  
          
                              
      //error_log( print_r(  'vtprd_unique_cart_id 001 = ' .$_SESSION['vtprd_unique_cart_id'], true ) );    
              
            
          if ( is_user_logged_in() &&
             (!isset($_SESSION['vtprd_customer_id']) ) )  {
            $_SESSION['vtprd_customer_id'] = get_current_user_id();            
          }
           
          //set/upd for any new cart
          //vtprd_set_transient_cart_data ( 'customer_id_for_cart_id', 'key to key lookup' );  //- track relationship between CURRENT Unique_id AND customer_id
          if ( is_user_logged_in() ) {
            //error_log( print_r(  'set 0001 ', true ) );
            vtprd_set_unqiue_transient_customer_id_for_cart_id ('customer_id_for_cart_id', 'key to key lookup');
          }
          

          //v2.0.2.0 end
          //-----------------------
          
          
          //v1.0.8.0  save previous cart before creating new cart image, for lifetime confirmation processing only
          $vtprd_previous_cart = $vtprd_cart;     //v1.0.8.0 
          
          $vtprd_cart = new vtprd_Cart; 
           
          $cart_has_catalog_discounts = 'no'; //v1.1.1
           
          foreach ( $woocommerce_cart_contents as $cart_item_key => $cart_item ) {
						$_product = $cart_item['data'];
						if ($_product->exists() && $cart_item['quantity']>0) {
							$vtprd_cart_item                = new vtprd_Cart_Item;
             
              //v1.1.1 begin
              //unique identifier in place of prod id, as some products DO NOT have an unique one
              $vtprd_cart_item->cart_item_key = $cart_item_key; 
              //v1.1.1 end
              
              //the product id does not change in woo if variation purchased.  
              //  Load expected variation id, if there, along with constructed product title.
              $varLabels = ' ';

              if ($cart_item['variation_id'] > ' ') {      
                 
                  // get parent title
                  $parent_post = get_post($cart_item['product_id']);
                  
                  // get variation names to string onto parent title
                  foreach($cart_item['variation'] as $key => $value) {          
                    $varLabels .= $value . '&nbsp;';           
                  }
                  
                  $vtprd_cart_item->product_id           = $cart_item['variation_id'];
                  $vtprd_cart_item->variation_array      = $cart_item['variation'];                  
                  $vtprd_cart_item->product_name         = $parent_post->post_title . '&nbsp;' . $varLabels ;
                  $vtprd_cart_item->parent_product_name  = $parent_post->post_title;
                  $vtprd_cart_item->parent_product_id    = $cart_item['product_id']; //v2.0.0
                  //v1.1.8.2 begin
                  if ( version_compare( WC_VERSION, '3.3.0', '>=' ) ) {
                    $vtprd_cart_item->variation_name_html  = wc_get_formatted_cart_item_data($cart_item);   //v1.1.8.2
                  } else {
                    $vtprd_cart_item->variation_name_html  = $woocommerce->cart->get_item_data($cart_item);   //v1.0.7.9
                  }
                  //v1.1.8.2 end
                  
                // added for v1.0.9.0 but unused, so commented ... 
                //  $variation_product = wc_get_product( $vtprd_cart_item->product_id );  //v1.0.9.0   //v1.1.7 replace get_product with wc_get_product                

              } else { 
                  $vtprd_cart_item->product_id           = $cart_item['product_id'];
                  //v1.1.8.2 begin
                  if ( version_compare( WC_VERSION, '3.3.0', '>=' ) ) {
                    $vtprd_cart_item->product_name         = $_product->get_title().wc_get_formatted_cart_item_data($cart_item);   //v1.1.8.2  
                  } else {
                    $vtprd_cart_item->product_name         = $_product->get_title().$woocommerce->cart->get_item_data($cart_item);
                  }
                  //v1.1.8.2 end 
              }

              //v1.0.8.6  begin
              //for Variation Products with Attributes only, **there is NO product_ID difference** - the only difference is in the variation array.
              //   this info is used later to uniquely identify the product to which a discount should be added.
              $vtprd_cart_item->product_variation_key  = array (
                 'product_id'    => $cart_item['product_id'], 
                 'variation_id'  => $cart_item['variation_id'],
                 'variation'     => $cart_item['variation']
              );   
              //v1.0.8.6  end
              
              $product = wc_get_product( $vtprd_cart_item->product_id ); //v1.0.7.4 //v1.1.7 replace get_product with wc_get_product
              
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
                    $product_type = $product->get_type();
                 } else {
                    $product_type = $product->product_type;
                 }
                 $disallow_these_types = apply_filters('vtprd_disallow_product_types',FALSE);
  
  //error_log( print_r(  'vtprd_disallow_product_types 001, product_type = ' .$product_type .'disallow array= ', true ) );
  //error_log( var_export($disallow_these_types, true ) ); 
  
                 if ( (is_array($disallow_these_types)) &&
                      (in_array($product_type, $disallow_these_types)) ) {
  //error_log( print_r(  'vtprd_disallow_product_types 001, SKIP THIS PRODUCT ', true ) );                       
                    continue; // skip this cart item!!
                 } 
              }              
              //v1.1.8.3 end
 
              
              //v1.0.8.5 begin
              $varID  = $cart_item['variation_id'];
              $prodID = $cart_item['product_id'];
              //v1.0.8.5 end
               

              $vtprd_cart_item->quantity      = $cart_item['quantity'];                                                  
                  
              $product_id = $vtprd_cart_item->product_id;
               
               
              //***always*** will be a session found

              vtprd_maybe_get_product_session_info($product_id);
              

              //************************************************************************
              /*
              v1.0.9.0 begin     this part of the routine reworked.
               WITH V1.0.9.0 WE IGNORE THE UNIT PRICE IN THE CART,
                as that could reflect both a CATALOG discount AND an **in-line** CART discount 
                not a problem with coupone-based discount)
                rather, we either go back to the ORIGINAL unit price for processing
                OR we use the CATALOG discounted unit price.
                (1) Original price, as taken from the DB
                (2) Catalog pricing, taken from session variable
                (3) Cart pricing, from running cart discount

              */
              //************************************************************************
              
              //By this time, there may  be a 'display' session variable for this product, if a discount was displayed in the catalog           
              //  so 2nd - nth iteration picks up the discounted current price AND the original price for comparison
              if ( ( isset($vtprd_info['product_session_info']['product_yousave_total_amt']) ) &&
                   ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0) ) {   //v1.0.9.0 changed to pick up FREE items...
                 //  $vtprd_cart_item->unit_price             =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart);     //v1.0.8.0 
                  $vtprd_cart_item->unit_price             =  $vtprd_info['product_session_info']['product_discount_price'];
                  
                  
                  
                  
                  $vtprd_cart_item->save_orig_unit_price   =  $vtprd_info['product_session_info']['product_unit_price'];   //v1.0.7.4  save for later comparison
                  
                  //v1.1.6.8 begin load in SALE PRICE as orig price, as needed
                  if ( ($vtprd_info['product_session_info']['product_special_price']) &&
                       ($vtprd_info['product_session_info']['product_special_price'] < $vtprd_info['product_session_info']['product_list_price'] ) ) {
                    $vtprd_cart_item->save_orig_unit_price   =  $vtprd_info['product_session_info']['product_special_price'];
                  } else {
                    $vtprd_cart_item->save_orig_unit_price   =  $vtprd_info['product_session_info']['product_list_price'];
                  }                  
                  //$vtprd_cart_item->db_unit_price          =  $vtprd_info['product_session_info']['product_unit_price'];
                  //v1.1.6.8  end                   
                  
    
                  $vtprd_cart_item->db_unit_price_special  =  $vtprd_info['product_session_info']['product_special_price']; 
                  $vtprd_cart_item->db_unit_price_list     =  $vtprd_info['product_session_info']['product_list_price'];
                  $vtprd_cart_item->product_list_price_catalog_correctly_taxed      =  $vtprd_info['product_session_info']['product_list_price_catalog_correctly_taxed']; //v1.1.1                  
                  $cart_has_catalog_discounts = 'yes'; //v1.1.1              
              } else { 
                  
                  $price = vtprd_get_current_active_price($product_id,$product);  //v1.1.1 added $product to call
                 
                  $vtprd_previous_cart = '';
                  $price  =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart, $price); 
                 
                  $vtprd_cart_item->unit_price             =  $price;

                  
                  //v1.1.6.8 begin load in SALE PRICE as orig price, as needed
                  if ( ($vtprd_cart_item->db_unit_price_special) &&
                       ($vtprd_cart_item->db_unit_price_special < $vtprd_cart_item->db_unit_price_list ) ) {
                    $vtprd_cart_item->save_orig_unit_price   =  $vtprd_cart_item->db_unit_price_special;
                  } else {
                    $vtprd_cart_item->save_orig_unit_price   =  $vtprd_cart_item->db_unit_price_list;
                  }                  
                  //$vtprd_cart_item->save_orig_unit_price   =  $vtprd_cart_item->db_unit_price_list;
                  //v1.1.6.8  end 
                  
                  /*  now loaded in vtprd_get_current_active_price
                  $vtprd_cart_item->db_unit_price         = $price;
                  $vtprd_cart_item->db_unit_price_list    = $regular_price;
                  $vtprd_cart_item->db_unit_price_special = $sale_price;                  
                  */
              }
              
              //v1.0.9.0 end


                 //v1.1.1 begin
                 //*****************************************************
                 // PLUGIN:: woocommerce-product-addons
                 //*****************************************************
                 /*
                 With addons plugin, Catalog rules *do not affect the pricing of addons* - only the primary product.
                 If there is a CART rule discount, both the Primary product and the addons will be discounted together.
                 */
                 if ( (class_exists('WC_Product_Addons')) && 
                      (isset ($cart_item['addons'])) ) {  //if this is an ADDONS product

                    $vtprd_cart_item->other_plugins_info_array = $cart_item['addons'];
                    
                    if (  (isset($vtprd_info['product_session_info']['product_has_addons']))  &&  //v2.0.0.9 added isset
                          ($vtprd_info['product_session_info']['product_has_addons']) ) {
                      $addons_total_price = 0;
                      foreach ( $cart_item['addons'] as $key => $addon ) {
                        $addons_total_price += $addon['price'];
                      }  
                                         
                      $vtprd_cart_item->unit_price             +=  $addons_total_price;
                      $vtprd_cart_item->save_orig_unit_price   +=  $addons_total_price; 
                      $vtprd_cart_item->db_unit_price          +=  $addons_total_price;  
                      $vtprd_cart_item->db_unit_price_special  +=  $addons_total_price;  
                      $vtprd_cart_item->db_unit_price_list     +=  $addons_total_price; 
                      $_SESSION['vtprd_addons_added_'.$product_id] = $addons_total_price;  //used in vtprd_maybe_get_price
                      $addons_product_in_cart = true;
                    }
         
                 }                   
                 //v1.1.1 end
                 
                 //v1.1.1 begin
                 //*****************************************************
                 // PLUGIN:: woocommerce-measurement-price-calculator
                 //*****************************************************
                 /* Overrides any other pricing from upstream pricing plugins, if there.
                 woocommerce-measurement-price-calculator provides the ability to price by weights, measures etc
                 sort of like fake variations.  However, it is all under the **original** product, with no actual
                 variation product ids.  So the data that is unique to the added product is in the
                 'pricing_item_meta_data' array, which is appended to the cart item.  It contains the 
                 current price of the item, undisturbed.  So it can always act as the original source of the pricing.
                 CATALOG rules are processed using the "woocommerce_get_price" hook, also using a calculator-specific
                 array to process successfully.                               
                 */
                 if ( (class_exists('WC_Measurement_Price_Calculator')) &&
                      ($vtprd_info['product_session_info']['product_has_calculator']) &&
                      (isset ($cart_item['pricing_item_meta_data'])) &&
                      (isset ($cart_item['pricing_item_meta_data']['_price'])) ) {  //if this is a calculator product
                    $vtprd_cart_item->unit_price             =  $cart_item['pricing_item_meta_data']['_price'];
                    $vtprd_cart_item->save_orig_unit_price   =  $cart_item['pricing_item_meta_data']['_price'];  
                    $vtprd_cart_item->db_unit_price          =  $cart_item['pricing_item_meta_data']['_price'];  
                    $vtprd_cart_item->db_unit_price_special  =  $cart_item['pricing_item_meta_data']['_price'];  
                    $vtprd_cart_item->db_unit_price_list     =  $cart_item['pricing_item_meta_data']['_price']; 
                    $_SESSION['vtprd_calculator_added_'.$product_id] = $cart_item['pricing_item_meta_data']['_price'];  //used in vtprd_maybe_get_price 
                    $calculator_product_in_cart = true; 
                 }                   
                 //v1.1.1 end
              
              //v1.1.1 begin
              //update old_price for later crossout use ==>> need ***cart_item_key***, can't use product_id for calculator/addons items!
              $_SESSION['vtprd_orig_cart_price_'.$vtprd_cart_item->cart_item_key] = $vtprd_cart_item->unit_price;
              
              //******************
              //v1.1.8.0 begin
              //allows tracking of correctly taxed orig unit price for later reporting
               if ( get_option('woocommerce_calc_taxes')  == 'yes' ) {
                  switch( true ) {
                    case ( ( get_option('woocommerce_prices_include_tax') == 'no') &&
                           ( get_option('woocommerce_tax_display_cart')   == 'incl') ):             
                        $vtprd_cart_item->product_orig_cart_unit_price  =  vtprd_get_price_including_tax($vtprd_cart_item->product_id, $vtprd_cart_item->unit_price);
                      break; 

                    case ( ( get_option('woocommerce_prices_include_tax') == 'yes') &&
                           ( get_option('woocommerce_tax_display_cart')   == 'excl') ) :                   
                        $vtprd_cart_item->product_orig_cart_unit_price  =  vtprd_get_price_excluding_tax($vtprd_cart_item->product_id, $vtprd_cart_item->unit_price);
                      break; 
                    default :                   
                        $vtprd_cart_item->product_orig_cart_unit_price  =  $vtprd_cart_item->unit_price;
                      break;                      
                  }                        
               } else {
                  $vtprd_cart_item->product_orig_cart_unit_price  =  $vtprd_cart_item->unit_price;
               }               
              //v1.1.8.0 end
              //******************
                  
                  //error_log( print_r(  'vtprd_load_vtprd_cart_for_processing FUTURE oldprice loaded, value = ' . $vtprd_cart_item->unit_price . ' for key= ' .$vtprd_cart_item->cart_item_key, true ) ); 
                  //v1.1.1 end

              // db_unit_price_special CAN be zero if item is FREE!!
              //if ($vtprd_cart_item->unit_price < $vtprd_cart_item->db_unit_price_list )  {
              //v1.1.0.4 begin  added >= 0 and != null test
              
              if ( ($vtprd_cart_item->db_unit_price_special != null) && 
                   ($vtprd_cart_item->db_unit_price_special >= 0) &&
                   ($vtprd_cart_item->db_unit_price_special < $vtprd_cart_item->db_unit_price_list ) ) {
              //v1.1.0.4 end
                  $vtprd_cart_item->product_is_on_special = 'yes';
              
              } 
/*
if  ($vtprd_cart_item->db_unit_price_special == null) {
 $vtprd_cart_item->product_is_on_special = 'It"s null, dummy!!!';
} else 
if  ($vtprd_cart_item->db_unit_price_special <= 0 ) {
 $vtprd_cart_item->product_is_on_special = 'It"s not numeric, dummy!!!';
} 
 */                           

              $vtprd_cart_item->total_price   = $vtprd_cart_item->quantity * $vtprd_cart_item->unit_price;
              
              /*  *********************************
              ***  JUST the cat *ids* please...
              ************************************ */
                       
              $vtprd_cart_item->prod_cat_list = wp_get_object_terms( $cart_item['product_id'], $vtprd_info['parent_plugin_taxonomy'], $args = array('fields' => 'ids') );
              $vtprd_cart_item->rule_cat_list = wp_get_object_terms( $cart_item['product_id'], $vtprd_info['rulecat_taxonomy'], $args = array('fields' => 'ids') );              

              //initialize the arrays
              $vtprd_cart_item->prod_rule_include_only_list = array();  
              $vtprd_cart_item->prod_rule_exclusion_list = array();


              //v1.0.9.3 added if isset
              if (isset($cart_item['line_subtotal'])) {
                $vtprd_cart_item->lifetime_line_subtotal = $cart_item['line_subtotal'];     //v1.0.8.0  for future lifetime processing only...  
              }
               
              //add cart_item to cart array
              $vtprd_cart->cart_items[]       = $vtprd_cart_item;
				    }
        } //	endforeach;
        
        
		} //end  if (sizeof($woocommerce->cart->get_cart())>0) 
     
    //v1.1.1 begin
    if ($calculator_product_in_cart) {      
      $_SESSION['calculator_product_in_cart'] = 'yes';
    } else {
      $_SESSION['calculator_product_in_cart'] = 'no';    
    }
    if ($addons_product_in_cart) {      
      $_SESSION['addons_product_in_cart'] = 'yes';
    } else {
      $_SESSION['addons_product_in_cart'] = 'no';    
    }    
    //v1.1.1 end
    
     
    if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {
      //v1.1.5 begin
      global $vtprd_license_options;
      if ( ($vtprd_license_options['status'] == 'valid') && 
           ($vtprd_license_options['state']  == 'active') && //if license is deactivated, pro is not loaded!!
           ($vtprd_license_options['pro_plugin_version_status'] == 'valid')  )  {
        vtprd_get_purchaser_info_from_screen(); 
      }
      //v1.1.5 end
    }
    $vtprd_cart->purchaser_ip_address = $vtprd_info['purchaser_ip_address']; 
    
    $vtprd_cart->cart_contents_count = $woocommerce->cart->cart_contents_count; //v1.0.9.3  used to check if cart contents have changed...
        
    $vtprd_info['cart_has_catalog_discounts'] = $cart_has_catalog_discounts;  //v1.1.1                         

  }


  
//************************************* 
   //v1.0.7.4  New Function
   //
   //  the vtprd_cart unit price and discounts all reflect the TAX STATE of 'woocommerce_prices_include_tax'
   //
   //************************************* 
	function vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart, $price=null){ //v1.0.9.0 added $price=null ==> $price only comes from one place... 
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info;  
     // $product = wc_get_product( $product_id ); v1.0.8.9 - value is passed already... //v1.1.7 replace get_product with wc_get_product

		  //v1.0.9.0 begin  moved here
      if ($price) {
        $unit_price  =  $price;
      } else {
        if (isset($cart_item['line_subtotal'])) {
          $unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];
        } else {
          $cart_item_line_subtotal = vtprd_get_line_subtotal_for_lifetime_only($product_id, $vtprd_previous_cart);
          $unit_price  =  $cart_item_line_subtotal / $cart_item['quantity'];
        }
        $price       =  $unit_price;
      }
      //v1.0.9.0 end

        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
             ( get_option( 'woocommerce_prices_include_tax' ) == 'no' )  || 
             ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9  
           //NO VAT included in price
           // $unit_price  =  $cart_item['line_subtotal'] / $cart_item['quantity'];  //v1.0.9.0
           $do_nothing = true;  //v2.0.0.2                                                            //v1.0.9.0
        } else {
           
           //v1.0.7.4 begin
           //TAX included in price in DB, and Woo $cart_item pricing **has already subtracted out the TAX **, so restore the TAX
           //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
       //$price now loaded above //v1.0.9.0
       //$price           =  $cart_item['line_subtotal'] / $cart_item['quantity'];  //v1.0.9.0
          // $unit_price  =  vtprd_get_price_including_tax($product_id, $price);
           $qty = 1;     
  //error_log( print_r(  'get_tax_class 001', true ) );       
           $_tax  = new WC_Tax();                
           //$product = wc_get_product( $product_id ); //not necessary //v1.1.7 replace get_product with wc_get_product
           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price  * $qty, $tax_rates, false );
  				 $tax_amount = $_tax->get_tax_total( $taxes );
  				 $unit_price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
                     
        } 

     return $unit_price;     
     
  }  
  
   
   //************************************* 
   //new function v1.0.8.0 
   // Lifetime only, at checkout confirmation time the line_subtotal in the cart has gone away...
   //   get the line_subtotal from previous cart image at last add-to-cart or cart screen
   //************************************* 
	function vtprd_get_line_subtotal_for_lifetime_only($product_id, $vtprd_previous_cart) {
      $lifetime_line_subtotal = 0;
      $sizeof_cart_items = sizeof($vtprd_previous_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {
        if ($vtprd_previous_cart->cart_items[$k]->product_id == $product_id) {
           $lifetime_line_subtotal = $vtprd_previous_cart->cart_items[$k]->lifetime_line_subtotal;
           $k = $sizeof_cart_items;
        }
      } 
   
      return $lifetime_line_subtotal;     
  }


   //************************************* 

   //************************************* 
	function vtprd_count_other_coupons(){
      global $woocommerce, $vtprd_info, $vtprd_rules_set, $vtprd_setup_options; //v2.0.0.5 added $vtprd_setup_options; 
      
      //error_log( print_r(  'function vtprd_count_other_coupons begin', true ) );   

        
      //v2.0.0.5a begin
      /*
        *********************************************************************************************
        In order to prevent looping iterations of "vtprd_woo_new_load_discount_amount_to_coupon" (vtprd-parent-cart-validation.php)
        after WOO 3.5.3, use the existing check against  $_SESSION['do_no_actions']
        to turn off processing in "vtprd_woo_new_load_discount_amount_to_coupon"
        while "vtprd_count_other_coupons" is running.  
        *********************************************************************************************    
      */
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }
      $_SESSION['do_no_actions'] = true;
      //v2.0.0.5a end
               
      //v1.0.7.4 begin   routine rewritten!                     
      $coupon_cnt = 0;
      $vtprd_info['skip_cart_processing_due_to_coupon_individual_use'] = false;

      //v1.0.7.7 begin - backwards compatability
      $current_version =  WOOCOMMERCE_VERSION;
      if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower
        $applied_coupons = $woocommerce->cart->get_applied_coupons();
      } else {
        $applied_coupons = WC()->cart->get_coupons();
      }          
      //v1.0.7.7 end
      
      //v1.1.7.1  Begin
      $vtprd_rule_coupon_activations = get_option( 'vtprd_rule_coupon_activations', array() );
      //v1.1.7.1  End
      
            
       //v1.1.0.9  $coupon_codes_array  DOES NOT INCLUDE the 'DEALS' code
      $coupon_codes_array = array();   //v1.1.0.9

      foreach ( $applied_coupons as $code => $name ) {	
      
        //error_log( print_r(  '$code= ' .$code, true ) );
        //error_log( print_r(  '$name= ' .$name, true ) );   
        //error_log( print_r(  '$vtprd_setup_options  coupon_discount_coupon_name= ' .$vtprd_setup_options['coupon_discount_coupon_name'] , true ) ); 
        //v2.0.0.5a begin
        if (!is_array($vtprd_setup_options)) {
          $vtprd_setup_options = get_option( 'vtprd_setup_options' );
          //error_log( print_r(  '$vtprd_setup_options  coupon_discount_coupon_name AGAIN= ' .$vtprd_setup_options['coupon_discount_coupon_name'] , true ) );           
        }
        //v2.0.0.5a end
               
      
      if ( ($code == $vtprd_setup_options['coupon_discount_coupon_name']) ||  //v2.0.0.5
           ($code == strtolower($vtprd_setup_options['coupon_discount_coupon_name'])) )  {  //v2.0.0.5
      //error_log( print_r(  '$code continued= ' .$code, true ) );             
          continue;  //if the coupon is a Pricing Deal discount, skip
        } else {
        
        //v1.1.7.1 Begin
        /*
        v1.1.7.1
        assuming that the other 7.1 logic works, we'll now have an array of existing
        pricing deals-only coupon actuators.  So that can be used here, 
        INSTEAD
        of looking at each coupn code the way it's being done.
        If the coupon code in the applied list is the current, it skips out
        otherwise, ONLY add to the array if it's NOT in the list.
        
          //$coupon_cnt++;  //v1.1.0.9 
          $coupon_codes_array[] = $code;   //v1.1.0.9        
         	 // from woocommerce/includes/class-wc-cart.php
           // Set a switch to skip Cart processing if a coupon with individual_use = "yes" detected
    			$the_coupon = new WC_Coupon( $code );           
    			if ( $the_coupon->id ) {            
    				// If it's individual use then flag to skip all plugin discount processing!!!!!!!!!!!       				
            if ( $the_coupon->individual_use == 'yes' ) {
    					$vtprd_info['skip_cart_processing_due_to_coupon_individual_use'] = true;
    				}           
          }
         */ 
            $coupon_codes_array[] = $code;   //v1.1.0.9   
            //if presented code is NOT used to activate a rule, set skip cart...
            if (!in_array($code, $vtprd_rule_coupon_activations)) {   //if variation is in previously checked_list   
               $vtprd_info['skip_cart_processing_due_to_coupon_individual_use'] = true;
            } 
                
         //v1.1.7.1 END                                    
        }
			}
      $vtprd_info['coupon_codes_array'] = $coupon_codes_array;   //v1.1.0.9
     // $vtprd_rules_set[0]->coupons_amount_without_rule_discounts = $coupon_cnt;   //v1.1.0.9
      //v1.0.7.4 end
      
      //error_log( print_r(  'function vtprd_count_other_coupons END', true ) ); 
      
      //v2.0.0.5a begin - reset 'do_no_actions'
      //*********************************************************************************************
      $contents = $_SESSION['do_no_actions'];
      unset( $_SESSION['do_no_actions'], $contents );
      //*********************************************************************************************
      //v2.0.0.5a end 
              
     return;     
  } 
  
     

   /* ************************************************
   **  v1.1.7.2 grpD NEW FUNCTION
   ************************************************** */  
    function vtprd_remove_any_other_woo_coupons() {
      //error_log( print_r(  'function vtprd_remove_any_other_woo_coupons begin', true ) );
      global $woocommerce, $vtprd_info, $vtprd_rules_set, $vtprd_setup_options; //v2.0.0.5 $vtprd_setup_options
      //v2.0.0 begin
      /*
      if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
        $applied_coupons = $woocommerce->cart->get_applied_coupons();
      } else {
        $applied_coupons = $woocommerce->cart->get_coupons();
      }
      */
      $applied_coupons = $woocommerce->cart->get_coupons(); 
      //v2.0.0 end 

      //v2.0.0.5 begin
      if (sizeof($applied_coupons) == 0) {        
        //error_log( print_r(  'Return from vtprd_remove_any_other_woo_coupons NOTHING FOUND', true ) );
        return;
      }
      //v2.0.0.5 end 
            
      //error_log( print_r(  '$applied_coupons= ', true ) );
      //error_log( var_export($applied_coupons, true ) );      

      //v2.0.0.5a begin - recoded
      
      $coupon_removed = false; 
      foreach ( $applied_coupons as $code => $name ) {
    //error_log( print_r(  '$code= ' .$code, true ) );
    //error_log( print_r(  '$name= ' .$name, true ) );      
      if ( ($code == $vtprd_setup_options['coupon_discount_coupon_name']) ||  //v2.0.0.5
           ($code == strtolower($vtprd_setup_options['coupon_discount_coupon_name'])) )  {  //v2.0.0.5
      //error_log( print_r(  '$code continued= ' .$code, true ) );         
          continue;  //if the coupon is a Pricing Deal discount, skip
        } else {
          //$woocommerce->cart->remove_coupon( $code );
    //error_log( print_r(  '$code before remove= ' .$code, true ) ); 
          $woocommerce->cart->remove_coupon( $code );
    //error_log( print_r(  '$name REMOVED= ' .$code, true ) );          
          //direct filter override for disallowed message!
          $msg = apply_filters('vtprd_coupon_disallowed_message',FALSE); 
          if ($msg) {
            $carryOn = true;
          } else {
            $msg  = __( 'Coupon discount disallowed, may not be used in combination with other discounts.', 'vtprd' );
          }
          wc_add_notice( $msg, 'error' );
          $coupon_removed = true;        
        }
           
      }
      //v2.0.0.5a end
            
      if ($coupon_removed) {   
    //error_log( print_r(  'above vtprd_remove_coupon_applied_message ', true ) ); 
        //Remove add coupons success msg if there...  otherwise it may display and confuse the customer => "Coupon code applied successfully"
        vtprd_remove_coupon_applied_message();          
      }
        
      //error_log( print_r(  'Return from vtprd_remove_any_other_woo_coupons AT END', true ) );   
            
      return;         
    }
  

   /* ************************************************
   **  v1.1.7.2 grpD NEW FUNCTION
   ************************************************** */  
    function vtprd_remove_coupon_applied_message() {
      //error_log( print_r(  'vtprd_remove_coupon_applied_message', true ) );
      global $woocommerce; 
      if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) { 
          
          /*$notices = WC()->session->get('wc_notices', array());          
          foreach( $notices['notices'] as $key => $notice){
              if( $notice == $coupon_succss_msg){
                  $remove_this_key = $key;
                  break;
              }
          }
          unset( $notices['notices'][$remove_this_key] );          
          WC()->session->set('wc_notices', $notices);
          */ 
          if(!isset($_SESSION)){
            session_start();
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
          }
          $_SESSION['remove_coupon_message'] = true; 
          //add_filter('woocommerce_coupon_message', 'vtprd_maybe_remove_coupon_message' , 10,3);                           
      } else {
        $coupon_succss_msg = __( 'Coupon code applied successfully.', 'vtprd' );
        $sizeof_messages = sizeof($woocommerce->messages);
        for($y=0; $y < $sizeof_messages; $y++) { 
           if ($woocommerce->messages[$y] == $coupon_succss_msg ) {
              unset ( $woocommerce->messages[$y] );
              break;
           }
        }
      } 
      
      return;   
    }

  //**************************************
  //* v1.1.7.2  New function
  //  Get rid of any coupon msg woo 3.0 and beyond.
  //**************************************
	add_filter('woocommerce_coupon_message', 'vtprd_maybe_remove_coupon_message' , 10,3); 
  function vtprd_maybe_remove_coupon_message($msg, $msg_code, $var){
    //error_log( print_r(  'Function begin - vtprd_maybe_remove_coupon_message', true ) );   

    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }

    if ( (isset ($_SESSION['remove_coupon_message'])) &&
         ($_SESSION['remove_coupon_message']) ) {
      $_SESSION['remove_coupon_message'] = false;
      $msg = '';  
		}
    return $msg;
     
  }    
   
	//v2.0.0 Reworked - J Solution
  /* just save off the incoming post object as flattened SESSION var
  Then get the post for the incoming $product_id
  Alter to use the post info readily available
  Then at the end, restore the post object from the flattened SESSION var   
  */
  function vtprd_load_vtprd_cart_for_single_product_price($product_id, $price){
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info; 
  //error_log( print_r(  'function vtprd_load_vtprd_cart_for_single_product_price begin, $product_id= ' .$product_id. ' $price= ' .$price, true ) );

      $flattened_post = serialize($post);
      //v2.0.2.0 begin
      // $_SESSION['flattened_post_cart_for_single_product_price'] = $flattened_post;             
      $cart_id = $_SESSION['vtprd_unique_cart_id'];

      //need to escape any single quotes in the data, so we don't get SQL errors
      $flattened_post = str_replace("'", "%27", $flattened_post);      
      
      //error_log( print_r(  'set 0002 ', true ) );
      vtprd_set_transient_cart_data ( 'flattened_post_cart_for_single_product_price', $flattened_post, $cart_id );  
      //v2.0.2.0 end

      
      $post = get_post($product_id); 
      
      $vtprd_cart = new VTPRD_Cart; 
       
      $vtprd_cart_item                = new VTPRD_Cart_Item;    

      $vtprd_cart_item->product_id            = $product_id;
      $vtprd_cart_item->product_name          = $post->post_name;
      $vtprd_cart_item->quantity              = 1;
      $vtprd_cart_item->parent_product_id     = $post->post_parent; //v2.0.0
      
      $product = wc_get_product( $product_id );  //v1.1.1 moved here //v1.1.7 replace get_product with wc_get_product
      $price = vtprd_get_current_active_price($product_id,$product);  //v1.1.1 added $product to call
  //error_log( print_r(  'vtprd_load_vtprd_cart_for_single_product_price - price after vtprd_get_current_active_price= ' .$price, true ) );
       
      //v1.1.7.2 begin
      //needed for "by varname" for catalog rules
      if ($post->post_parent > 0) {  //if this has a post_perant value, it is a variation
        $vtprd_cart_item->variation_id = $product_id;
        $vtprd_cart_item->variation_array = $product->get_attributes();    
        //if we're on a variation, gotta use the Parent to get the taxonomies!!                   
        $use_this_id    = $post->post_parent;
        $post_parent_ID = $post->post_parent;  //v1.0.9.0
      } else {
        $use_this_id = $product_id;
      }
      //v1.1.7.2 end

      //init $cart_item for call
      $cart_item = array();
      $vtprd_previous_cart = '';
      $price  =  vtprd_compute_current_unit_price($product_id, $cart_item, $vtprd_cart_item, $product, $vtprd_previous_cart, $price);            
      $vtprd_cart_item->save_orig_unit_price  = $price;
      $vtprd_cart_item->unit_price            = $price;  
      $vtprd_cart_item->total_price           = $price;

      $vtprd_cart_item->prod_cat_list = wp_get_object_terms( $use_this_id, $vtprd_info['parent_plugin_taxonomy'], $args = array('fields' => 'ids') );
      $vtprd_cart_item->rule_cat_list = wp_get_object_terms( $use_this_id, $vtprd_info['rulecat_taxonomy'], $args = array('fields' => 'ids') );
        //*************************************                    
      //v1.0.7.8  end                    

       //v1.0.7.4 begin 
      //initialize the arrays
      $vtprd_cart_item->prod_rule_include_only_list = array();  
      $vtprd_cart_item->prod_rule_exclusion_list = array();

      //v1.1.0.7 begin        
      if ( ($vtprd_cart_item->db_unit_price_special != null) && 
           ($vtprd_cart_item->db_unit_price_special >= 0) &&
           ($vtprd_cart_item->db_unit_price_special < $vtprd_cart_item->db_unit_price_list ) ) {
      //v1.1.0.4 end
          $vtprd_cart_item->product_is_on_special = 'yes';
      
      }
      //v1.1.0.7 end 
  
      //add cart_item to cart array
      $vtprd_cart->cart_items[]       = $vtprd_cart_item;  

      //restore parent $post as needed, for WOO's sanity
      
      //v2.0.2.0 begin
      //$flattened_post = $_SESSION['flattened_post_cart_for_single_product_price']; //v2.0.0 begin - j solution
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0001 ', true ) );
      $flattened_post = vtprd_get_transient_cart_data ( 'flattened_post_cart_for_single_product_price', $cart_id);
      vtprd_del_transient_cart_data_by_data_type ('flattened_post_cart_for_single_product_price', $cart_id);
      
      //need to un-escape any single quotes in the data, so we don't get SQL errors
      $flattened_post = str_replace("%27", "'",  $flattened_post);        
      
      //v2.0.2.0 end     
      $post = unserialize($flattened_post);
      
      return;           
  }

	//*****************************************
  //v1.1.1 Refactored to use get_price!!
  //*****************************************
  function vtprd_get_current_active_price($product_id,$product) {	
      global $post, $vtprd_cart_item;
   //error_log( print_r(  'vtprd_get_current_active_price, product_id= ' .$product_id, true ) );       
      //************************************************************************************************************************************************
      //ACTIVATED externally by the client, or internally for the Addons or Calculator plugins
      //the apply_filter is done twice - once in parent-cart-validation in housekeeping, and once in parent-functions in vtprd_get_current_active_price
      //************************************************************************************************************************************************
      
      //vtprd_do_compatability_pricing is a function below, and returns true if active
      $do_get_price = apply_filters('vtprd_do_compatability_pricing',false);
          
      //v1.1.5 begin
      //FIX Fatal error: Call to a member function get_tax_class() ...
      if  ( (!$product) ||
            (!is_object($product)) ) {
         $product = wc_get_product( $product_id ); //v1.1.7 replace get_product with wc_get_product
      }      
      //v1.1.5 end
          
        
      /*  sample execution for CLIENT
      
      // *** add to bottom of Theme Functions file
      //allows Pricing Deals to pick up current product pricing from other plugins
      //  ---  more resource intensive  ---
     
      add_filter('vtprd_do_compatability_pricing', 'do_compatability_pricing', 10, 1); 
      function do_compatability_pricing ($return_status) {
        return true;
      }
      */  
      
      If ( ($do_get_price) ||
           (class_exists('WC_Measurement_Price_Calculator')) ||
           (class_exists('WC_Product_Addons')) ) {
  //error_log( print_r(  'do get price', true ) );  
        //*********************************************************        
        //USE GET_PRICE  to pick up other plugins pricing, but only ON DEMAND
        //*********************************************************
        
        //------------------------
        //TURN **OFF** all of My HOOKS
        //------------------------
        $_SESSION['do_no_actions'] = true; 
        //------------------------ 

      
        //Get current non-discounted price, Correctly Taxed!!          
        $price = $product->get_price();
 
   
        //might be a problem later, not having the differentiated list price and sale price!!!!!!!
        //load into global
        $vtprd_cart_item->db_unit_price         = $price;
        $vtprd_cart_item->db_unit_price_list    = $price;
        $vtprd_cart_item->db_unit_price_special = $price;         
         
        
        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) &&
             ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) ) {           
  //error_log( print_r(  'get_tax_class 002', true ) );          
           $_tax  = new WC_Tax();                
          // $product = wc_get_product( $product_id );  already there v1.1.1 //v1.1.7 replace get_product with wc_get_product
  
           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price , $tax_rates, false );
  				 //back out taxes!!!
           $tax_amount = $_tax->get_tax_total( $taxes );
  
           $tax_percent    = $tax_amount / $price  ;
          
           $divisor    = 1 + $tax_percent;
          
           $price = $price / $divisor;

        }      

        //------------------------          
        //TURN **ON** all of My HOOKS
        //------------------------ 
        $contents = $_SESSION['do_no_actions'];
        unset( $_SESSION['do_no_actions'], $contents );
        //------------------------ 

       
      } else {
  //error_log( print_r(  'old method', true ) );      
        //****************************
        // v1.0.9.0  begin
        //  New price logic - ignore passed-in price, get the price from the DB and process anew           
        //$price
   
        $regular_price = get_post_meta( $product_id, '_regular_price', true );
        if ($regular_price <= 0) {
           $regular_price  =  get_post_meta( $product_id, '_price', true );
        }
   
        $sale_price  =  vtprd_maybe_get_sale_price($product_id);                  
   //error_log( print_r(  '$sale_price= ' .$sale_price, true ) );  
        if ( ($sale_price > 0) &&
             ($sale_price < $regular_price) ) {
          $price  =  $sale_price;    
        } else {
          $price  =  $regular_price;
        }
   
  
        //load into global
        $vtprd_cart_item->db_unit_price         = $price;
        $vtprd_cart_item->db_unit_price_list    = $regular_price;
        $vtprd_cart_item->db_unit_price_special = $sale_price;         
         
        //v1.0.9.3 begin
        // Prices from the DB include taxation in the following situation
        //   Woo carries unit pricing without taxation, so back it out here.
        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) &&
             ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) ) {           
  //error_log( print_r(  'get_tax_class 003', true ) );           
           $_tax  = new WC_Tax();                
          //$product = wc_get_product( $product_id ); already there v1.1.1 //v1.1.7 replace get_product with wc_get_product

           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price , $tax_rates, false );
  				 //back out taxes!!!
           $tax_amount = $_tax->get_tax_total( $taxes );
  
           $tax_percent    = $tax_amount / $price  ;
          
           $divisor    = 1 + $tax_percent;
          
           $price = $price / $divisor;
          
           //$price = round( $price / $divisor, absint( get_option( 'woocommerce_price_num_decimals' ) ) );  
        }
        //v1.0.9.3 end  
                  
      } 

    //error_log( print_r(  '$price= ' .$price, true ) );

      return $price;
  }

	//******************************
  //v1.1.1  new function
  //******************************
	function vtprd_maybe_get_sale_price($product_id){
      
      $sale_price  =  get_post_meta( $product_id, '_sale_price', true );
      If (!$sale_price) {
        return $sale_price;
      }
      
			$date_from = get_post_meta( $product_id, '_sale_price_dates_from', true );
			$date_to   = get_post_meta( $product_id, '_sale_price_dates_to', true );
 			
      //If no dates, return sale_price
      if ( (empty( $date_from )) &&
           (empty( $date_to )) ) {
          return $sale_price; 
      }
      
      $date_from = ! empty( $date_from ) ? date( 'Y-m-d', $date_from ) : '';
	  $date_to   = ! empty( $date_to ) ? date( 'Y-m-d', $date_to ) : '';


       //v2.0.2.0 begin  old date function returned UTC time, not set timezone.
       //$today = date("Y-m-d");
       global  $vtprd_info;
       if (isset($vtprd_info['timezoneOffset'])) {
          $today =  vtprd_get_current_time( "Y-m-d" ); //picks up plugin timezone !!!!!!  
       }  else {
          $today =  current_time( "Y-m-d" ); //picks up wp_timezone !!!!!!
       }
       //v2.0.2.0 end
      
      //If out of date range, no sale price
       
      if ( ($date_from ) &&
           ($today < $date_from) ) {
          $sale_price = '';
          return $sale_price;            
      }
       
      if ( ($date_to ) &&
           ($today > $date_to) ) {
          $sale_price = '';
          return $sale_price;            
      }
            
                        
      return $sale_price;     

  }

	
	function vtprd_move_vtprd_single_product_to_session($product_id){
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_setup_options, $vtprd_rules_set;  
      
      vtprd_debug_options();  //v1.0.9.4
      
      $short_msg_array = array();
      $full_msg_array = array();
      $msg_already_done = 'no';
      $show_yousave_one_some_msg = ''; //v1.0.8.0
    

      //v2.0.0 begin K solution
      if ($vtprd_info['ruleset_has_a_display_rule'] == 'yes') {      
        //auditTrail keyed to rule_id, so foreach is necessary
        foreach ($vtprd_cart->cart_items[0]->cartAuditTrail as $key => $row) {                 
          //parent product vargroup on sale, individual product variation may not be on sale.
          // send an additional sale msg for the varProd parent group...
          $show_yousave_one_some_msg = '';
          if ($vtprd_setup_options['show_yousave_one_some_msg'] == 'yes' ) {
            if (!$show_yousave_one_some_msg) {
              $rulesetKey = $row['ruleset_occurrence'];
              switch( $vtprd_rules_set[$rulesetKey]->inPop_varProdID_parentLit) {  
                case 'one':
                   $show_yousave_one_some_msg = __('One of these are on Sale', 'vtprd');
                  break;
                case 'some':
                   $show_yousave_one_some_msg = __('Some of these are on Sale', 'vtprd');
                  break;         
                case 'all':  //all are on sale, handled as normal.
                  break; 
                default:  //handled as normal.
                  break;       
              }
            }
          }
           
          if ($row['rule_short_msg'] > ' ' ) {       
            $short_msg_array [] = $row['rule_short_msg'];
            $full_msg_array  [] = $row['rule_full_msg'];
          }
  
        }
      }     

      /*
       if  $vtprd_cart->cart_level_status == 'rejected' no discounts found
       how to handle yousave display, etc.... If no yousave, return 'false'
      */
      if ( ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') ||
           ($vtprd_cart->cart_level_status == 'rejected') ) {
        $vtprd_cart->cart_items[0]->discount_price = 0;
        $vtprd_cart->cart_items[0]->yousave_total_amt = 0;
        $vtprd_cart->cart_items[0]->yousave_total_pct = 0;
      } 
     //v2.0.0 end K solution
      
      //needed for wp-e-commerce!!!!!!!!!!!
      //  if = 'yes', display of 'yousave' becomes 'save FROM' and doesn't change!!!!!!!
//      $product_variations_sw = vtprd_test_for_variations($product_id);
      $product_variations_sw = '';

      //v1.0.9.0 begin
      vtprd_get_cart_html_prices('single','catalog');



      //*************************
      //v1.0.9.0 begin  refactored
      //*************************      
     if ($vtprd_cart->cart_items[0]->yousave_total_amt > 0) {
         $list_price                    =   $vtprd_cart->cart_items[0]->db_unit_price_list;
         
         //v1.0.8.8 begin
         //if taxation should be applied to list price, do so here
         if ( ( get_option('woocommerce_calc_taxes')  == 'yes' ) &&
              ( get_option('woocommerce_prices_include_tax') == 'no') &&
              ( get_option('woocommerce_tax_display_cart')   == 'incl') ) {
             
            $list_price                 =   vtprd_get_price_including_tax($product_id, $list_price); 
         }
         //v1.0.8.8 end
         
        //v1.1.1 begin ADDED to include opposite side 
         if ( ( get_option('woocommerce_calc_taxes')  == 'yes' ) &&
              ( get_option('woocommerce_prices_include_tax') == 'yes') &&
              ( get_option('woocommerce_tax_display_cart')   == 'excl') ) {
             
            $list_price                 =   vtprd_get_price_excluding_tax($product_id, $list_price); 
         }  
         $vtprd_cart->cart_items[0]->product_list_price_catalog_correctly_taxed   =   $list_price;       
         //v1.1.1 end
                
         
         $vtprd_cart->cart_items[0]->product_list_price_html_woo   =   wc_price($list_price);  //v1.1.7 replace woocommerce_price with wc_price
      } else {
      //************************************
      //v1.1.1.3 begin
      //************************************
      //STILL needs doing for NON-DISCOUNT
      
         $list_price                    =   $vtprd_cart->cart_items[0]->db_unit_price_list;
         
         //v1.0.8.8 begin
         //if taxation should be applied to list price, do so here
         if ( ( get_option('woocommerce_calc_taxes')  == 'yes' ) &&
              ( get_option('woocommerce_prices_include_tax') == 'no') &&
              ( get_option('woocommerce_tax_display_cart')   == 'incl') ) {
             
            $list_price                 =   vtprd_get_price_including_tax($product_id, $list_price); 
         }
         //v1.0.8.8 end
         
        //v1.1.1 begin ADDED to include opposite side 
         if ( ( get_option('woocommerce_calc_taxes')  == 'yes' ) &&
              ( get_option('woocommerce_prices_include_tax') == 'yes') &&
              ( get_option('woocommerce_tax_display_cart')   == 'excl') ) {
             
            $list_price                 =   vtprd_get_price_excluding_tax($product_id, $list_price); 
         }  
         $vtprd_cart->cart_items[0]->product_list_price_catalog_correctly_taxed   =   $list_price;  
         
         //v1.1.1.3 end 
         //************************************
      }
      //v1.0.9.0 end
      
      $product_orig_price_html_woo = ''; //v1.1.0.9
      
      //v1.0.9.3 begin
      //load info for old_price used later
      if ( ($vtprd_cart->cart_items[0]->yousave_total_amt > 0) &&
           ($vtprd_setup_options['show_catalog_price_crossout'] == 'yes') )  {
        switch( true ) {
          //v1.1.0.4 begin 
          case ($vtprd_cart->cart_items[0]->product_is_on_special == 'yes') :
          /*
          case ( ($vtprd_cart->cart_items[0]->db_unit_price_special > 0 ) &&
                 ($vtprd_cart->cart_items[0]->db_unit_price_special < $vtprd_cart->cart_items[0]->db_unit_price_list ) ) :                  //there is a discount...
          */ 
          //v1.1.0.4 end      
              $product_orig_price = $vtprd_cart->cart_items[0]->db_unit_price_special; //special_price needs formatting ...
              
            break;
          default :               
              $product_orig_price = $vtprd_cart->cart_items[0]->db_unit_price_list;
            break; 
        } 
        
        $product_orig_price_html_woo = wc_price($product_orig_price);

        $product = false; //v1.1.1  needed below!
        
        if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
           if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' )  {
              If (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) {
                $product = wc_get_product( $product_id ); //v1.1.7 replace get_product with wc_get_product                
                $product_orig_price_excl_tax =  vtprd_get_price_excluding_tax_forced($product_id, $product_orig_price, $product); 
                $product_orig_price_html_woo =  wc_price($product_orig_price_excl_tax);    
              }           
           } else {
              If (get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
                $product = wc_get_product( $product_id );  //v1.1.7 replace get_product with wc_get_product               
                $product_orig_price_incl_tax =  vtprd_get_price_including_tax_forced($product_id, $product_orig_price, $product); 
                $product_orig_price_html_woo =  wc_price($product_orig_price_incl_tax); 
              }           
           }
        }           
            /*
           switch (true) {
              case ( (get_option( 'woocommerce_tax_display_shop' ) == 'excl' ) :
              
                     $oldprice = $vtprd_cart_item->product_catalog_price_displayed_incl_tax_woo;
                    
                 break;         
              case ( (get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) &&
                     (get_option( 'woocommerce_tax_display_cart' ) == 'excl') ) :
              
                     $oldprice = $vtprd_cart_item->product_catalog_price_displayed_excl_tax_woo;
                             
                 break;
              default:
                     $oldprice = $vtprd_cart_item->product_catalog_price_displayed;                     
                 break;                  
           }

                $product = wc_get_product( $product_id );   //v1.1.7 replace get_product with wc_get_product             
                $product_orig_price_incl_tax =  vtprd_get_price_including_tax_forced($product_id, $product_orig_price, $product); 
                $product_orig_price_html_woo =  wc_price($product_orig_price_incl_tax);   

        $product = wc_get_product( $product_id );                
        $product_orig_price_incl_tax =  vtprd_get_price_including_tax_forced($product_id, $product_orig_price, $product); 
        $product_orig_price_incl_tax_html_woo =  wc_price($product_orig_price_incl_tax); 
         
        $product_orig_price_excl_tax =  vtprd_get_price_excluding_tax_forced($product_id, $product_orig_price, $product);        
        $product_orig_price_excl_tax_html_woo =  wc_price($product_orig_price_excl_tax);
                     
        */
        
        
      }
      //v1.0.9.3 end
     
      //------------------
      //v1.1.1 begin
      //*****************************************************
      // PLUGIN:: woocommerce-product-addons
      //***************************************************** 
      // Test for and Mark all addon-related products
      $product_has_addons = false;
      if (class_exists('WC_Product_Addons')) { 
         //*********
         //if we are on a variation, get the parent id.  product_addons are attributes of the parent product only - but mark all products!
         //*********

         $post_parent = wp_get_post_parent_id( $product_id );
         if ($post_parent > 0) { 
            $use_this_product_id = $post_parent;
         } else {
            $use_this_product_id = $product_id;
         }
    
         $product_addons = array_filter( (array) get_post_meta( $use_this_product_id, '_product_addons', true ) );
          
         if ( is_array( $product_addons ) && sizeof( $product_addons ) > 0 ) {
            $product_has_addons = true;
         }
         
               
        //used in vtprd_maybe_get_price  
        if ( (isset ($_SESSION['single_product_first_time_'.$product_id])) &&
             ($_SESSION['single_product_first_time_'.$product_id] == 'yes') ) {
           $_SESSION['single_product_first_time_'.$product_id] = 'no';  
        } else {
           $_SESSION['single_product_first_time_'.$product_id] = 'yes';       
        }
      
      }
      
       //*****************************************************
       // PLUGIN:: woocommerce-measurement-price-calculator
       //***************************************************** 
       // Test for and Mark all calculator-related products     
      $product_has_calculator = false;
      if (class_exists('WC_Measurement_Price_Calculator')) { 
         //*********
         //if we are on a variation, get the parent id.  product_calculator are attributes of the parent product only - but mark all products!
         //*********
         
         $post_parent = wp_get_post_parent_id( $product_id );
         if ($post_parent > 0) { 
            $use_this_product_id = $post_parent;
         } else {
            $use_this_product_id = $product_id;
         }
         
         $price_calculator        = get_post_meta( $use_this_product_id, '_wc_price_calculator', true );
         $price_calculator_rules  = get_post_meta( $use_this_product_id, '_wc_price_calculator_pricing_rules', true );
         
         if ( ( is_array( $price_calculator ) && sizeof( $price_calculator ) > 0 ) ||
              ( is_array( $price_calculator_rules ) && sizeof( $price_calculator_rules ) > 0 ) ) {
            $product_has_calculator = true;
         }
      }

      //v1.1.1 end
      //------------------
      
      
      $vtprd_info['product_session_info']  =     array (
            'product_list_price'           => $vtprd_cart->cart_items[0]->db_unit_price_list,
            'product_list_price_html_woo'  => $vtprd_cart->cart_items[0]->product_list_price_html_woo,
            'product_unit_price'           => $vtprd_cart->cart_items[0]->db_unit_price,
            'product_special_price'        => $vtprd_cart->cart_items[0]->db_unit_price_special,
            'product_discount_price'       => $vtprd_cart->cart_items[0]->product_discount_price_woo,   //mwntest
                                                  //$vtprd_cart->cart_items[0]->discount_price,
            //v1.0.7.4 - field now contains **just** the discount - suffix is added later.  
            //  this price reflects the tax situation of the ORIGINAL price - so if the price was originally entered with tax, this will reflect tax
         //   'product_discount_price_woo'   =>                                                           //v1.0.9.0
        //                                      $vtprd_cart->cart_items[0]->product_discount_unit_price_woo,   //mwntest
            'product_discount_price_html_woo'  => 
                                              $vtprd_cart->cart_items[0]->product_discount_price_html_woo,            
            //v1.0.7.4 begin
            'product_discount_price_incl_tax_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_incl_tax_woo,
            'product_discount_price_excl_tax_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_excl_tax_woo,
            'product_discount_price_incl_tax_html_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_incl_tax_html_woo,
            'product_discount_price_excl_tax_html_woo'      =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_excl_tax_html_woo,                                              
            'product_discount_price_suffix_html_woo'   =>
                                              $vtprd_cart->cart_items[0]->product_discount_price_suffix_html_woo, 
            //v1.0.7.4 end
            //v1.0.9.3 begin
            'product_catalog_yousave_total_amt_incl_tax_woo'   =>
                                              $vtprd_cart->cart_items[0]->product_catalog_yousave_total_amt_incl_tax_woo,
            'product_catalog_yousave_total_amt_excl_tax_woo'   =>
                                              $vtprd_cart->cart_items[0]->product_catalog_yousave_total_amt_excl_tax_woo, 
            'product_orig_price_html_woo'   =>
                                              $product_orig_price_html_woo,
         /*   'product_orig_price_incl_tax_html_woo'   =>
                                              $product_orig_price_incl_tax_html_woo,                                              
            'product_orig_price_excl_tax_html_woo'   =>
                                              $product_orig_price_excl_tax_html_woo, */                                             
            //v1.0.9.3 end            
                                                        
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
            'product_has_calculator'       => $product_has_calculator,    //v1.1.1  
            'product_has_addons'           => $product_has_addons,    //v1.1.1
            'product_list_price_catalog_correctly_taxed'  => $vtprd_cart->cart_items[0]->product_list_price_catalog_correctly_taxed     //v1.1.1            
      ) ;          
    //v1.0.9.0 end           

      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      } 
      //store session id 'vtprd_product_session_info_[$product_id]'
      $_SESSION['vtprd_product_session_info_'.$product_id] = $vtprd_info['product_session_info'];
      
      //initialize vtprd_cart to clear all discount values...  //v1.0.7.8
      $vtprd_cart = new vtprd_Cart;                            //v1.0.7.8      
  }



  
    // *** ------------------------------------------------------------------------------------------------------- ***
    // v1.0.9.0  new function
    // *** ------------------------------------------------------------------------------------------------------- ***
    function vtprd_get_cart_html_prices($called_from, $catalog_or_inline=null) {   //v1.1.1 changed to parameter name
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_setup_options, $vtprd_rules_set;
 //error_log( print_r(  ' ' , true ) );
 //error_log( print_r(  'vtprd_get_cart_html_prices, $called_from= ' .$called_from. ' $catalog_or_inline= ' .$catalog_or_inline, true ) );    
 
      $price_display_suffix = ''; //v1.0.9.3
      //v1.1.1 begin
      if ($called_from == 'single') {
        $number_of_times = 1;
      } else {
        $number_of_times = sizeof($vtprd_cart->cart_items);
      }

      //error_log( print_r(  'vtprd_get_cart_html_prices, CART = ', true ) );
      //error_log( var_export($vtprd_cart, true ) ); 
      //v1.1.1 end
      for($z=0; $z < $number_of_times; $z++) { 
       
        $product_id                    =   $vtprd_cart->cart_items[$z]->product_id;   //need this on both sides of the if

        if ($vtprd_cart->cart_items[$z]->yousave_total_amt > 0) { 
         //  $product_id                    =   $vtprd_cart->cart_items[$z]->product_id;
           $list_price                    =   $vtprd_cart->cart_items[$z]->db_unit_price_list;
           $db_unit_price_list_html_woo   =   wc_price($list_price); //v1.1.7 replace woocommerce_price with wc_price
           $product_list_price_catalog_correctly_taxed   =   $list_price;  //v1.1.1
                      
           //NEW UNIT PRICE  ( $vtprd_cart->cart_items[$z]->discount_price = units subtotal including discount )
           //$discount_price                =   round($vtprd_cart->cart_items[$z]->discount_price / $vtprd_cart->cart_items[$z]->quantity , 2); 
           $discount_price                =  $vtprd_cart->cart_items[$z]->discount_unit_price; //v1.1.1 

           //v1.0.9.3  begin
            //v1.1.1 begin - reconfigured
            if ($called_from == 'single')  {         
              if(isset($_SESSION['vtprd_product_old_price_'.$product_id])) {
                  $oldprice = $_SESSION['vtprd_product_old_price_'.$product_id]['single_product_current_price'];
                  $oldprice_formatted = wc_price($oldprice);   
              } else {              
                //v1.1.0.4 begin - replace with status test
                if ($vtprd_cart->cart_items[$z]->product_is_on_special == 'yes') {
                /*
                if ( ($vtprd_cart->cart_items[$z]->db_unit_price_special > 0 ) &&
                     ($vtprd_cart->cart_items[$z]->db_unit_price_special < $vtprd_cart_item->db_unit_price_list) ) {
                */
                //v1.1.0.4 end
                  $oldprice_formatted = wc_price( $vtprd_cart->cart_items[$z]->db_unit_price_special ) ;
                  $oldprice = $vtprd_cart->cart_items[$z]->db_unit_price_special;
                } else {
                  $oldprice_formatted = wc_price( $vtprd_cart->cart_items[$z]->db_unit_price_list );
                  $oldprice = $vtprd_cart->cart_items[$z]->db_unit_price_list;
                }
                $oldprice_formatted = wc_price($oldprice); //v1.1.1
              } 
              
              //error_log( print_r(  'vtprd_get_cart_html_prices Oldprice SINGLE path' , true ) ); 
                            
           } else {

              $oldprice = $_SESSION['vtprd_orig_cart_price_'.$vtprd_cart->cart_items[$z]->cart_item_key];
              $oldprice_formatted = wc_price($oldprice); 
              
              //error_log( print_r(  'vtprd_get_cart_html_prices Oldprice Found loaded, value = ' . $oldprice . ' for key= ' .$vtprd_cart->cart_items[$z]->cart_item_key , true ) ); 
          
           }
                     
           $vtprd_cart->cart_items[$z]->product_catalog_price_displayed =  $oldprice;
           $product = wc_get_product( $product_id );   //v1.1.7 replace get_product with wc_get_product             
    //       $vtprd_cart->cart_items[$z]->product_catalog_price_displayed_incl_tax_woo =  vtprd_get_price_including_tax($product_id, $oldprice); 
           $vtprd_cart->cart_items[$z]->product_catalog_price_displayed_incl_tax_woo =  vtprd_get_price_including_tax_forced($product_id, $oldprice, $product);  
           $vtprd_cart->cart_items[$z]->product_catalog_price_displayed_excl_tax_woo =  vtprd_get_price_excluding_tax_forced($product_id, $oldprice, $product); 
           
           $yousave_total_amt = $vtprd_cart->cart_items[$z]->yousave_total_amt;
           $vtprd_cart->cart_items[$z]->product_catalog_yousave_total_amt_incl_tax_woo =  vtprd_get_price_including_tax_forced($product_id, $yousave_total_amt, $product);  
           $vtprd_cart->cart_items[$z]->product_catalog_yousave_total_amt_excl_tax_woo =  vtprd_get_price_excluding_tax_forced($product_id, $yousave_total_amt, $product);              
                   
           $vtprd_cart->cart_items[$z]->computation_summary  = $vtprd_cart->cart_items[$z]->product_name .'<br>';  //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '++ Computation Summary ++' .'&nbsp;&nbsp;&nbsp;'.  '(may exclude tax until end...)' .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Total Product Discount = ' .$vtprd_cart->cart_items[$z]->yousave_total_amt .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Pre-Discount Unit Price = ' .$oldprice_formatted .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Unit Quantity = ' .$vtprd_cart->cart_items[$z]->quantity .'<br>'; //v1.0.9.3
           //next one's kinda faked...
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Subtotal = Quantity * Pre-Discount Unit Price = ' . ($vtprd_cart->cart_items[$z]->quantity * $vtprd_cart->cart_items[$z]->product_catalog_price_displayed).'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Discounted subtotal = subtotal - discount = ' .$vtprd_cart->cart_items[$z]->discount_price .'<br>'; //v1.0.9.3
           
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Initial discounted Unit Price = Discounted subtotal / quantity = ' .$discount_price .'<br>'; //v1.0.9.3
           //v1.0.9.3  end
           
           
           //Test New Unit price for rounding error and fix
           if ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') { 
              
              //v1.1.0.5 begin  FIX floating point error  ==>> value comparison was not working due to floating point number precision
              //$test_total_discount_price  = $discount_price * $vtprd_cart->cart_items[$z]->quantity;
              $test_total_discount_price                   = round($discount_price * $vtprd_cart->cart_items[$z]->quantity , 2);
              $vtprd_cart->cart_items[$z]->discount_price  = round($vtprd_cart->cart_items[$z]->discount_price + 0 , 2); //this fixes floating point issue...
              //v1.1.0.5 end 
              
              $vtprd_cart->cart_items[$z]->computation_summary .= '- Test new subtotal = Initial discounted Unit Price * quantity = ' .$test_total_discount_price .'<br>'; //v1.0.9.3
                         
              switch( true ) {
 
                case ($test_total_discount_price == $vtprd_cart->cart_items[$z]->discount_price):  
                    $all_good;
                  break;

                case ($test_total_discount_price > $vtprd_cart->cart_items[$z]->discount_price):  //not enough discount                
                    if ($vtprd_setup_options['give_more_or_less_discount'] == 'more')  {
                      $discount_price = $discount_price - .01; //smaller unit price = MORE discount
                      
                      $vtprd_cart->cart_items[$z]->computation_summary .= 
                        '- Test new subtotal > Discounted subtotal, and'  . '<br>'.
                        '&nbsp;&nbsp;&nbsp;' . '"give more or less discount" = more,'  . '<br>'. 
                        '&nbsp;&nbsp;&nbsp;' . 'so .01 subtracted from Initial discounted Unit Price = ' .$discount_price. '<br>'; //v1.0.9.3
                      $test_total_discount_price  = $discount_price * $vtprd_cart->cart_items[$z]->quantity; //v1.0.9.3
                      $vtprd_cart->cart_items[$z]->computation_summary .= '- Test new subtotal = New discounted Unit Price * quantity = ' .$test_total_discount_price .'<br>'; //v1.0.9.3
               
                    } /*else {
                      //subtract a penny from $discount_price until $test_total_discount_price <= $vtprd_cart->cart_items[$z]->discount_price
                      $discount_price = $discount_price + .01; //larger discount_price = larger unit price = LESS discount                      
                    }*/
                  break;

                case ($test_total_discount_price < $vtprd_cart->cart_items[$z]->discount_price):  //too much discount
                    if ($vtprd_setup_options['give_more_or_less_discount'] == 'more')  {
                       $all_good; 
                    } else {
                       $discount_price = $discount_price + .01; //slarger unit price = LESS discount 
 
                      $vtprd_cart->cart_items[$z]->computation_summary .= 
                        '- Test new subtotal < Discounted subtotal, and'  . '<br>'.
                        '&nbsp;&nbsp;&nbsp;' . '"give more or less discount" = less,'  . '<br>'. 
                        '&nbsp;&nbsp;&nbsp;' . 'so .01 added to Initial discounted Unit Price = ' .$discount_price. '<br>'; //v1.0.9.3 
                      $test_total_discount_price  = $discount_price * $vtprd_cart->cart_items[$z]->quantity; //v1.0.9.3
                      $vtprd_cart->cart_items[$z]->computation_summary .= '- Test new subtotal = New discounted Unit Price * quantity = ' .$test_total_discount_price .'<br>'; //v1.0.9.3                                                                   
                    }                    
                  break; 
                                   
              }
           }
           
           $discount_price_html_woo       =   wc_price($discount_price); //v1.1.7 replace woocommerce_price with wc_price

           //v1.0.7.4 begin
           $price_including_tax           =   vtprd_get_price_including_tax($product_id, $discount_price); 

           $price_excluding_tax           =   vtprd_get_price_excluding_tax($product_id, $discount_price);
           $price_including_tax_html      =   wc_price( $price_including_tax );
           $price_excluding_tax_html      =   wc_price( $price_excluding_tax );
                      
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Final discounted Unit Price = ' .$discount_price_html_woo .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Final Unit Price including Tax = ' .$price_including_tax_html .'<br>'; //v1.0.9.3
           $vtprd_cart->cart_items[$z]->computation_summary .= '- Final Unit Price excluding Tax = ' .$price_excluding_tax_html; //v1.0.9.3
                      
           //v1.0.7.4 end
  
           //v1.0.7 begin
           //from woocommerce/includes/abstracts/abstract-wc-product.php
           // Check for Price Suffix
           
           //v1.0.7.4 begin
           //  no suffix processing if taxes not turned on!!
           global $woocommerce; 
           if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
                ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9   
              $price_display_suffix  = false; 
    
           } else {
              $price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );
           }
           //v1.0.7.4 end
         
        	 if ( ( $price_display_suffix ) &&                              //v1.0.7.2
                ( $vtprd_cart->cart_items[$z]->yousave_total_amt > 0 ) ) {   //v1.0.7.2  don't do suffix for zero amount...
        			
              //***************
              //v1.0.7.4 begin
              //***************
  
              if ( (strpos($price_display_suffix,'{price_including_tax}') !== false)  ||
                   (strpos($price_display_suffix,'{price_excluding_tax}') !== false) ) {   //does the suffix include these wildcards?
                //  $price_including_tax = vtprd_get_price_including_tax($product_id, $discount_price); 
                //  $price_excluding_tax = vtprd_get_price_excluding_tax($product_id, $discount_price); 
     
                $find = array(    //wildcards allowed in suffix
        				  '{price_including_tax}',
        		      '{price_excluding_tax}'
        			  );              
                //replace the wildcards in the suffix!            
                $replace = array(
          			//	wc_price( $this->get_price_including_tax() ),
          			//	wc_price( $this->wc_get_price_excluding_tax() )
                  $price_including_tax_html,  
                  $price_excluding_tax_html 
          			);
  
          			$price_display_suffix = str_replace( $find, $replace, $price_display_suffix ); 
              }
              //v1.0.7.4 end
                                          
              //then see if additonal suffix is needed
              if (strpos($discount_price_html_woo, $price_display_suffix) !== false) { //if suffix already in price, do nothing
                $do_nothing = true; //v2.0.0.2 
              } else {
                //$discount_price_html_woo = $discount_price_html_woo . ' <small class="woocommerce-price-suffix ">' . $price_display_suffix . '</small>';
                $price_display_suffix  = '<small class="woocommerce-price-suffix "> ' . $price_display_suffix . ' </small>'; //v1.1.7.5 added spaces before and after
              }
           }
           
           //v1.0.7 end
     //      $vtprd_cart->cart_items[$z]->product_catalog_discount_price_html_woo = 
     //         '<del>' . $db_unit_price_list_html_woo . '</del><ins>' . $discount_price_html_woo . '</ins>'; 
         } else {
           $db_unit_price_list_html_woo = '';
           $discount_price = 0;          
           $discount_price_html_woo = '';            
           $price_including_tax = 0;            
           $price_excluding_tax = 0;            
           $price_including_tax_html = '';            
           $price_excluding_tax_html = '';
           $product_list_price_catalog_correctly_taxed = 0; //v1.1.1
               
         }

         $vtprd_cart->cart_items[$z]->product_list_price_html_woo                          =  $db_unit_price_list_html_woo; 
         $vtprd_cart->cart_items[$z]->product_list_price_catalog_correctly_taxed           =  $product_list_price_catalog_correctly_taxed;  //v1.1.1
         
         
         if ($catalog_or_inline == 'inline') {
           //load the price fields used for inline 
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_woo                  =  $discount_price;          
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_html_woo             =  $discount_price_html_woo;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_incl_tax_woo         =  $price_including_tax;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_excl_tax_woo         =  $price_excluding_tax;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_incl_tax_html_woo    =  $price_including_tax_html;            
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_excl_tax_html_woo    =  $price_excluding_tax_html;                   
           $vtprd_cart->cart_items[$z]->product_inline_discount_price_suffix_html_woo      =  $price_display_suffix; 
         } else {
           $vtprd_cart->cart_items[$z]->product_discount_price_woo                         =  $discount_price;          
           $vtprd_cart->cart_items[$z]->product_discount_price_html_woo                    =  $discount_price_html_woo;            
           $vtprd_cart->cart_items[$z]->product_discount_price_incl_tax_woo                =  $price_including_tax;            
           $vtprd_cart->cart_items[$z]->product_discount_price_excl_tax_woo                =  $price_excluding_tax;            
           $vtprd_cart->cart_items[$z]->product_discount_price_incl_tax_html_woo           =  $price_including_tax_html;            
           $vtprd_cart->cart_items[$z]->product_discount_price_excl_tax_html_woo           =  $price_excluding_tax_html;                     
           $vtprd_cart->cart_items[$z]->product_discount_price_suffix_html_woo             =  $price_display_suffix;                   
         }

       }  //end foreach     
       
       return; 
  }

    //v1.0.9.3 new function
    function vtprd_maybe_price_incl_tax ($product_id, $price) { 
       global $woocommerce;    
       $product = wc_get_product( $product_id ); //v1.0.7.4  //v1.1.7 replace get_product with wc_get_product
       
 
        if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
             ( get_option( 'woocommerce_prices_include_tax' ) == 'no' )  || 
             ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9  
           $do_nothing = true; //v2.0.0.2                                                               //v1.0.9.0
        } else {
  //error_log( print_r(  'get_tax_class 004', true ) );
           $qty = 1;           
           $_tax  = new WC_Tax();                
          // $product = wc_get_product( $product_id );  //v1.1.7 replace get_product with wc_get_product
           $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
  			 	 $taxes      = $_tax->calc_tax( $price  * $qty, $tax_rates, false );
  				 $tax_amount = $_tax->get_tax_total( $taxes );
  				 $price = round( $price  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
                     
        }        
       
       
       return $price; 
  }
      

  
  function vtprd_test_for_variations($prod_ID) { 
      
    //v2.0.0 simplified
     /* Commented => DB access method uses more IO/CPU cycles than array processing below...
     //sql from woocommerce/classes/class-wc-product.php
     $variations = get_posts( array(
    			'post_parent' 	=> $prod_ID,
    			'posts_per_page'=> -1,
    			'post_type' 	=> 'product_variation',
    			'fields' 		=> 'ids',
    			'post_status'	=> 'publish'
    		));
     if ($variations)  {
        $vartest_response = 'yes';
     }  */
     
     // code from:  woocommerce/admin/post-types/writepanels/writepanel-product-type-variable.php
     $attributes = (array) maybe_unserialize( get_post_meta($prod_ID, '_product_attributes', true) );
     foreach ($attributes as $attribute) {
       if ( (isset( $attribute['is_variation'] ) )  &&   //v1.0.8.6
            ($attribute['is_variation']) )  {
          return TRUE; //v2.0.0 changed to return true
          break;
       }
     }
     
     return false;  //v2.0.0 changed to return false   
  }  
  
    
   function vtprd_format_money_element($price) { 
      //from woocommerce/woocommerce-core-function.php   function woocommerce_price
    	$return          = '';
    	$num_decimals    = (int) get_option( 'woocommerce_price_num_decimals' );
    	$currency_pos    = get_option( 'woocommerce_currency_pos' );
    	$currency_symbol = get_woocommerce_currency_symbol();
    	$decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
    	$thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
    
    	$price           = apply_filters( 'raw_woocommerce_price', (double) $price );
    	$price           = number_format( $price, $num_decimals, $decimal_sep, $thousands_sep );
    
    	if ( get_option( 'woocommerce_price_trim_zeros' ) == 'yes' && $num_decimals > 0 )
    		$price = woocommerce_trim_zeros( $price );
    
    	//$return = '<span class="amount">' . sprintf( get_woocommerce_price_format(), $currency_symbol, $price ) . '</span>'; 

     $formatted = sprintf( get_woocommerce_price_format(), $currency_symbol, $price );
     
     return $formatted;
   }
   
   //****************************
   // Gets Currency Symbol from PARENT plugin   - only used in backend UI during rules update
   //****************************   
  function vtprd_get_currency_symbol() {    
    return get_woocommerce_currency_symbol();  
  } 


  //ALSO DEFINE IF tax_exempt
  //v2.0.1.0 revamped
  function vtprd_get_current_user_role() {
    global $current_user; 
     
    $user_role = null;
    if( is_user_logged_in() ) {

      if ( !$current_user )  {
        $current_user = wp_get_current_user();
      }
      
      $user_roles = $current_user->roles;
      $user_role = array_shift($user_roles);
    } 
     
    if  ($user_role <= ' ') {
      $user_role = 'notLoggedIn';
    }

    /*   NO LONGER NECESSARY - handled in v1.0.8.8
    //v1.0.9.0  begin --  load user tax exempt status
    
    global $vtprd_info;
    
    //if already loaded, we're done
    if ($vtprd_info['user_is_tax_exempt']) {
       return $user_role;
    }
    
    $vtprd_info['user_is_tax_exempt']  =  false;
          
    // check user-level tax exemption (plugin-specific checkbox on user screen)
    if (get_user_meta( $current_user->ID, 'vtprd_user_is_tax_exempt', true ) == 'yes') {
       $vtprd_info['user_is_tax_exempt']  =  true;
       
       return $user_role;
    }
    
    //check role-level tax exemption (plugin-specific role capability)
    if ( current_user_can( 'buy_tax_free', $current_user->ID, ) ) {
       $vtprd_info['user_is_tax_exempt']  =  true;
    }

    //v1.0.9.0  end
    */      
    return $user_role;
  } 


  //****************************************
  //v1.0.8.8 new function
  //**************************************** 
   //Make all logged-in roles with "buy_tax_free" capability tax-free
   /*
    * Enhancement - Added "Wholesale Tax Free" Role.  Added "buy_tax_free" Role Capability.
		Now **Any** User logged in with a role with the "buy_tax_free" Role Capability 
		will have 0 tax applied
		And the tax-free status will apply to the **Role**, regardless of whether a deal is currently active!!

    		**************************************** 
    		**Setup needed - Requires the addition of a  "Zero Rate Rates" tax class in the wp-admin back end 
    		*****************************************     
    		*(1) go to Woocommerce/Settings
    		*(2) Select (click on) the 'Tax' tab at the top of the page
    		*(3) You will then see, just below the tabs, the line     
    		    "Tax Options | Standard Rates | Reduced Rate Rates | Zero Rate Rates " 
    		*(4) Select (click on) "Zero Rate Rates " 
    		*(5) Then at the bottom left, click on 'insert row' .  
    		* Done.
    		* 
    * 
    **Now  any role with the capability 'buy_tax_free' will have 0 taxes applied!                               
   */

    //add_filter( 'woocommerce_product_tax_class', 'vtprd_maybe_tax_free_tax_class', 1, 2 );
    add_filter( 'woocommerce_product_get_tax_class', 'vtprd_maybe_tax_free_tax_class', 1, 2 );  //filter replaced v1.1.7 for woo 3.0
 
    function vtprd_maybe_tax_free_tax_class( $tax_class, $product ) {

    if  ( current_user_can('buy_tax_free') ) {
        $tax_class = 'Zero Rate';
    }
    return $tax_class;
  }


  //*******************************************************************************

  //*******************************************************************************
  function vtprd_print_widget_discount() {
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_setup_options;
      
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4      
      
    //*****************************
    //PRINT DISCOUNT ROWS + total line
    //*****************************

    $vtprd_cart->cart_discount_subtotal = $vtprd_cart->yousave_cart_total_amt; 
     
    if ($vtprd_setup_options['show_cartWidget_discount_detail_lines'] == 'yes') {
      $output  = '<h3 class="widget-title vtprd-widget-title">'; //v1.1.7.2 added class
      $output .=  __('Discounts', 'vtprd');
      $output .= '</h3>';
      echo $output;

      //do we repeat the purchases subtotal after the discount details?
      if ($vtprd_setup_options['show_cartWidget_purchases_subtotal'] == 'beforeDiscounts') {
         vtprd_print_widget_purchases_subtotal();
      } 

      if ($vtprd_setup_options['show_cartWidget_discount_detail_lines'] == 'yes') {
         vtprd_print_widget_discount_rows();
      } 
            
    } 

    //do we repeat the purchases subtotal after the discount details?
    if ($vtprd_setup_options['show_cartWidget_purchases_subtotal'] == 'withDiscounts') {
       vtprd_print_widget_purchases_subtotal();
       echo '<br>'; //additional break needed here
    } 

    if ($vtprd_setup_options['show_cartWidget_discount_total_line'] == 'yes') {
       vtprd_print_widget_discount_total();
    }     

    if ($vtprd_setup_options['cartWidget_new_subtotal_line'] == 'yes') {
       vtprd_print_widget_new_combined_total();
    }         
            

    return;
  }
 
  /* ************************************************
  **   print discount amount by product, and print total              
  *************************************************** */
	function vtprd_print_widget_discount_rows() {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;

      echo '<ul class="cart_list product_list_widget vtprd_product_list_widget">' ;

  
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {            
            echo '<li>';
            $msg_cnt = 0;  

            if ($vtprd_setup_options['show_cartWidget_discount_details_grouped_by_what'] == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
                $msg_cnt++;
                if ($msg_cnt > 1) {
                  echo '</li><li>';
                }

                $i = $yousave_by_rule['ruleset_occurrence'];
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                $output  = '<span class="vtprd-discount-msg-widget" >';                  
                $output .= stripslashes($yousave_by_rule['rule_short_msg']);
                $output .= '</span><br>';
                echo  $output;
                
                //if a max was reached and msg supplied, print here 
                if ($yousave_by_rule['rule_max_amt_msg'] > ' ') {    
                  $output  = '<span class="vtprd-discount-max-msg-widget" >';                  
                  $output .= stripslashes($yousave_by_rule['rule_max_amt_msg']);
                  $output .= '</span><br>';
                  echo  $output;                  
                }
                
                $amt = $yousave_by_rule['yousave_amt']; 
                $units = $yousave_by_rule['discount_applies_to_qty'];                  
                vtprd_print_discount_detail_line_widget($amt, $units, $k);
              }
            } else {   //show discounts by product
              $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
              $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
              vtprd_print_discount_detail_line_widget($amt, $units, $k);
           }
           
           echo '</li>';
        }
      }

      echo '</ul>' ;

    return;
    
  }
     
	function vtprd_print_discount_detail_line_widget($amt, $units, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
    $output = ''; //v1.0.7.9

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<span class="vtprd-product-name-widget">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';	
      //v1.0.7.9 begin
      if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
        $output .= $vtprd_cart->cart_items[$k]->variation_name_html;
      } else {
        $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin               
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
          $name  = ucwords($name);
          $current_version =  WOOCOMMERCE_VERSION;
          if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
            //pre 2.1
            $name  = sanitize_title($name);
            $value = sanitize_title($value);            
            $output .= '<dt class="variation-'.$name.'">'. $name . ': </dt>';  //added class
            $output .= '<dd class="variation-'.$name.'">'. $value .'</dd>';    //added class
          } else {
            //post 2.1
            //$name2 = sanitize_text_field( $name );
            $name2 = sanitize_title( $name );
            $name2_san = sanitize_html_class( $name2 );
            $output .= '<dt class="variation-'. $name2_san.'">'. wp_kses_post( $name ) . ': </dt>';  //added class
            $output .= '<dd class="variation-'. $name2_san.'">'. wp_kses_post( wpautop( $value )) .'</dd>';    //added class
          }
          //v1.0.7.8  end              
        }
        $output .= '</dl>';
      }
      //v1.0.7.9 end    
    } else {
      $output .= '<span class="vtprd-product-name-widget">' . $vtprd_cart->cart_items[$k]->product_name  .'</span>';
      $output .= '<br>';
    }    
            
    //*************************************
    //division creates a per-unit discount
    //*************************************
    $amt = $amt / $units;
    
    //v1.0.7.4 begin      
    $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
    // $amt = vtprd_format_money_element($amt);
    //v1.0.7.4 end
    		
    $output .= '<span class="quantity vtprd-quantity-widget">' . $units  .' &times; ';	
    $output .= '<span class="amount vtprd-amount-widget">' . $vtprd_setup_options['cartWidget_credit_detail_label'] .$amt  .'</span>';
    $output .= '</span>';	    

 
    echo  $output;
    return;  
 }

    
	function vtprd_print_widget_discount_total() {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;

    if ($vtprd_setup_options['show_cartWidget_discount_total_line'] == 'no') { 
      return;
    }
                                                    
    $output = ''; //v1.0.7.9
    $output .= '<span class="total vtprd-discount-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_credit_total_title']. '&nbsp;</strong>';     

    //v1.0.9.3 begin
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
      if (get_option('woocommerce_tax_display_cart')   == 'incl') {
        $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);
      } else {
        
        $amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt_excl_tax);
        //v1.1.7 begin
        $ex_vat  = __( '(ex. VAT)', 'vtprd' );
        $ex_vat  = apply_filters('vtprd_replace_ex_vat_label',$ex_vat );
        $amt .= ' ' . $ex_vat;
        //v1.1.7 end
      }
    } else {
      $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);
    }
    //v1.0.9.3 END

    $output .= '<span class="amount  vtprd-discount-total-amount-widget">' . $vtprd_setup_options['cartWidget_credit_total_label'] .$amt . '</span>';

    $output .= '</span>';
    echo  $output;
       
    return;
    
  }

    
	function vtprd_print_widget_new_combined_total() {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;

    if ($vtprd_setup_options['cartWidget_new_subtotal_line'] == 'no') {
       return;
    }

    $output = ''; //v1.0.7.9
    $output .= '<p class="total vtprd-combined-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_new_subtotal_label'] .'&nbsp;</strong>';     

    
   //v1.0.7.5 - changed to "get_option('woocommerce_tax_display_cart')" - $woocommerce didn't have the info yet...
   if ( get_option('woocommerce_tax_display_cart') == 'excl' ) {  //v1.0.7.5
			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
		} else {
			$subtotal = $woocommerce->cart->subtotal;
    }

    //v1.0.8.9a begin               
    // pick up included, excluded or yousave_cart_total_amt Total       
    //$subTotal -= $vtprd_cart->cart_discount_subtotal;

    //v1.0.9.3 begin
    //$subtotal -= vtprd_load_cart_total_incl_excl();
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   		
    	$subtotal -= vtprd_load_cart_total_incl_excl();
    }
    //v1.0.9.3 end      
    
    //v1.0.8.9a end  
    
    $amt = vtprd_format_money_element($subtotal);

    //$amt = $woocommerce->cart->subtotal .' - ' . $vtprd_cart->cart_discount_subtotal .' = '. $subtotal; 
    
    $output .= '<span class="amount  vtprd-discount-total-amount-widget">' .$amt . '</span>';

    $output .= '</p>';
    echo  $output;
       
    return;
    
  }
  
  
  /* ************************************************
  **   print cart widget purchase subtotal             
  *************************************************** */
   
	function vtprd_print_widget_purchases_subtotal() {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
                                               
    $output = ''; //v1.0.7.9
    $output .= '<span class="total vtprd-product-total-label-widget" >';
    $output .= '<strong>'.$vtprd_setup_options['cartWidget_credit_subtotal_title']. '&nbsp;</strong>';     

    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    $output .= '<span class="amount  vtprd-discount-total-amount-widget">' .$amt . '</span>';

    $output .= '</span>';
    echo  $output;
       
    return;
    
  }
 
  /* ************************************************
  **   print discount amount by product, and print total AND MOVE DISCOUNT INTO TOTAL...             
  *************************************************** */

	function vtprd_print_checkout_discount() {
    global $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
   //when executing from here, the table rows created by the print routines need a <table>
    //  when executed from the cart_widget, the TR lines appear in the midst of an existing <table>

    vtprd_load_cart_total_incl_excl(); //v1.0.7.4
          
    $execType = 'checkout';
    
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'beforeDiscounts') {
      vtprd_print_cart_purchases_subtotal($execType);
    }
    
    $output = ''; //v1.0.7.9

    $output .=  '<table class="vtprd-discount-table"> ';
    
    
    if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {    
      $output .= '<tr id="vtprd-discount-title-checkout" >';
            /* COLSPAN no longer used here, has no affect
      $output .= '<td colspan="' .$vtprd_setup_options['checkout_html_colspan_value']. '" id="vtprd-discount-title-above-checkout">';
      */
      $output .= '<td id="vtprd-discount-title-above-checkout">';
      $output .= '<div class="vtprd-discount-prodLine-checkout" >';
      
      $output .= '<span class="vtprd-discount-prodCol-checkout">' .  __('Product', 'vtprd') . '</span>';
      
      $output .= '<span class="vtprd-discount-unitCol-checkout">' .  __('Discount Qty', 'vtprd') . '</span>';
  
      //v1.0.9.0 added new title
      if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
        $output .= '<span class="vtprd-discount-amtCol-checkout">' .  __('Discount Amount', 'vtprd') . '</span>';
      } else {
        $output .= '<span class="vtprd-discount-amtCol-checkout">' .  __('Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd') . '</span>';
      }
      
      $output .= '</div'; //end prodline
      $output .= '</td>';
      $output .= '</tr>';
         
     }
     echo  $output;
    
    $vtprd_cart->cart_discount_subtotal = $vtprd_cart->yousave_cart_total_amt;
    
    /*
    if ($vtprd_rules_set[0]->coupons_amount_without_rule_discounts > 0) {
       $vtprd_cart->cart_discount_subtotal += $vtprd_rules_set[0]->coupons_amount_without_rule_discounts;
       //print a separate discount line if price discounts taken, PRN
       vtprd_print_coupon_discount_row($execType);
    }
    */ 
                                                 
    //print discount detail rows 
    vtprd_print_cart_discount_rows($execType);
 
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'withDiscounts') {
      vtprd_print_cart_purchases_subtotal($execType);
    } 
 
 /*
    if ($vtprd_rules_set[0]->coupons_amount_without_rule_discounts > 0) {
       //print totals using the coupon amount  
       if ($vtprd_setup_options['show_checkout_credit_total_when_coupon_active'] == 'yes')  {          
          vtprd_print_cart_discount_total($execType); 
       }    
    } else {
      //if there's no coupon being presented, no coupon totals will be printed, so discount total line is needed     
      vtprd_print_cart_discount_total($execType);   
    }
    */
    vtprd_print_cart_discount_total($execType); 

    if ($vtprd_setup_options['checkout_new_subtotal_line'] == 'yes') {
      vtprd_print_new_cart_checkout_subtotal_line($execType);
    }    

    echo   '</table>  <!-- vtprd discounts table close -->  '; 
        
 } 
 
  /* ************************************************
  **   print discount amount by product, and print total              
  *************************************************** */
	function vtprd_print_cart_discount_rows($execType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $printRowsCheck = 'show_' .$execType. '_discount_detail_lines';
      if ($vtprd_setup_options[$printRowsCheck] == 'no') {
        return;
      }
  
      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  

       	if ( ($vtprd_cart->cart_items[$k]->yousave_total_amt > 0) ||  //v1.1.0.6
             ($vtprd_cart->cart_items[$k]->zero_price_auto_add_free_item == 'yes') ) {    //v1.1.0.6        
            if ((($execType == 'checkout')   && ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule')) ||
                (($execType == 'cartWidget') && ($vtprd_setup_options['show_cartWidget_discount_details_grouped_by_what'] == 'rule'))) {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
                $i = $yousave_by_rule['ruleset_occurrence'];
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
                  $output  = '<tr class="vtprd-discount-title-row" >';                  
                  $output .= '<td  class="vtprd-ruleNameCol-' .$execType. ' vtprd-border-cntl vtprd-deal-msg" >' . stripslashes($yousave_by_rule['rule_short_msg']) . '</td>';
                  $output .= '</tr>';
                  echo  $output;
                  
                  //if a max was reached and msg supplied, print here 
                  if ($yousave_by_rule['rule_max_amt_msg'] > ' ') {    
                    $output  = '<tr class="vtprd-discount-title-row" >';                  
                    $output .= '<td  class="vtprd-ruleNameCol-' .$execType. ' vtprd-border-cntl vtprd-deal-msg" >' . stripslashes($yousave_by_rule['rule_max_amt_msg']) . '</td>';
                    $output .= '</tr>';
                  echo  $output;                  
                  }
                  
                  $amt = $yousave_by_rule['yousave_amt'];
                  $units = $yousave_by_rule['discount_applies_to_qty'];                  
                  vtprd_print_discount_detail_line($amt, $units, $execType, $k);
                }
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  vtprd_print_discount_detail_line($amt, $units, $execType, $k);
           }
        }
      }

    return;
    
  }

  function vtprd_print_cart_widget_title() {     
    global $vtprd_setup_options;
    if ($vtprd_setup_options['show_cartWidget_discount_titles_above_details'] == 'yes') {    
      $output = ''; //v1.0.7.9  
      $output .= '<tr id="vtprd-discount-title-cartWidget" >';
      $output .= '<td colspan="' .$vtprd_setup_options['cartWidget_html_colspan_value']. '" id="vtprd-discount-title-cartWidget-line">';
      $output .= '<div class="vtprd-discount-prodLine-cartWidget" >';
      
      $output .= '<span class="vtprd-discount-prodCol-cartWidget">&nbsp;</span>';
      
      $output .= '<span class="vtprd-discount-unitCol-cartWidget">&nbsp;</span>';
  
      $output .= '<span class="vtprd-discount-amtCol-cartWidget">' .  __('Discount', 'vtprd') . '</span>';
      
      $output .= '</div>'; //end prodline
      $output .= '</td>';
      $output .= '</tr>';

      echo  $output;
    }
    return;   
  }
 
     
	function vtprd_print_discount_detail_line($amt, $units, $execType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
    $output = ''; //v1.0.7.9
    $output .= '<tr class="vtprd-discount-total-for-product-rule-row-' .$execType. '  bottomLine-' .$execType. '" >';
    $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value']. '">';
    $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
    
    $output .= '<span class="vtprd-discount-prodCol-' .$execType. '" id="vtprd-discount-product-id-' . $vtprd_cart->cart_items[$k]->product_id . '">';
    $output .= $vtprd_cart->cart_items[$k]->product_name;
    $output .= '</span>';
    
    $output .= '<span class="vtprd-discount-unitCol-' .$execType. '">' . $units . '</span>';

     //v1.1.0.6 begin      
    if ( ($amt == 0) &&
         (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE
      $amt = __('Free', 'vtprd');
      $output .= '<span class="vtprd-discount-amtCol-' .$execType. '">' .$amt . '</span>';
    } else {
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn  //v1.0.7.4 begin
      $output .= '<span class="vtprd-discount-amtCol-' .$execType. '">' . $vtprd_setup_options['' .$execType. '_credit_detail_label'] . ' ' .$amt . '</span>';      
    }
    // $amt = vtprd_format_money_element($amt);
    //v1.1.0.6 end   
    

    
    $output .= '</div>'; //end prodline
    $output .= '</td>';
    $output .= '</tr>';
    echo  $output;  
 }

/*  
  //coupon discount only shows at Checkout 
	function vtprd_print_coupon_discount_row($execType) {
    global $woocommerce, $vtprd_setup_options, $vtprd_rules_set;

    $output;
    $output .= '<tr class="vtprd-discount-total-for-product-rule-row-' .$execType. '  bottomLine-' .$execType. '  vtprd-coupon_discount-' .$execType. '" >';
    $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value']. '">';
    $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
    
    $output .= '<span class="vtprd-discount-prodCol-' .$execType. ' vtprd-coupon_discount-literal-' .$execType. '">';
    $output .= __('Coupon Discount: ', 'vtprd'); 
    $output .= '</span>';
    
    $output .= '<span class="vtprd-discount-unitCol-' .$execType. '">&nbsp;</span>';
    
    $labelType = $execType . '_credit_detail_label';
    
    $amt = vtprd_format_money_element($vtprd_rules_set[0]->coupons_amount_without_rule_discounts);  //show original coupon amt as credit
    $output .= '<span class="vtprd-discount-amtCol-' .$execType. '  vtprd-coupon_discount-amt-' .$execType. '">' . $vtprd_setup_options['' .$execType. '_credit_detail_label'] . ' ' .$amt . '</span>';
    
    $output .= '</div>'; //end prodline
    $output .= '</td>';
    $output .= '</tr>';
    echo  $output; 
       
    return;
    
  }
 */  
   
	//***************************************
  // Subtotal - Cart Purchases:
  //***************************************
  function vtprd_print_cart_purchases_subtotal($execType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   
      $subTotalCheck = 'show_' .$execType. '_purchases_subtotal';
      if ($vtprd_setup_options[$subTotalCheck] == 'none') {     
        return;
      }

      $output = ''; //v1.0.7.9
      if ($vtprd_setup_options[$subTotalCheck] == 'beforeDiscounts') {
          $output .= '<tr class="vtprd-discount-total-' .$execType. '" >';
          $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
          $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
          
          $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
          $output .= $vtprd_setup_options['' .$execType. '_credit_subtotal_title'];
          $output .= '</span>';
      
 //       due to a WPEC problem,  $vtprd_cart->cart_original_total_amt  may be inaccurate - use wpec's own subtotaling....
 //         $subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit 
          $amt = vtprd_get_Woo_cartSubtotal();
  
          $labelType = $execType . '_credit_detail_label';  
          $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. '"> &nbsp;&nbsp;' .$amt . '</span>';
          
          $output .= '</div>'; //end prodline
          $output .= '</td>';
          $output .= '</tr>'; 
      } else {
          $output .= '<tr class="vtprd-discount-total-' .$execType. '" >';
          $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
          $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
          
          $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
          $output .= $vtprd_setup_options['' .$execType. '_credit_subtotal_title'];
          $output .= '</span>';
      
      
 //         $subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
          $amt = vtprd_get_Woo_cartSubtotal();
          
          $labelType = $execType . '_credit_detail_label';  
          $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. '"> &nbsp;&nbsp;' .$amt . '</span>';
          
          $output .= '</div>'; //end prodline
          $output .= '</td>';
          $output .= '</tr>'; 
      }
      echo  $output;
   
    return;
    
  }

  //***************************************
  // Subtotal with Discount:  (print)
  //***************************************
	function vtprd_print_new_cart_checkout_subtotal_line($execType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

      $output = ''; //v1.0.7.9
 
      $output .= '<tr class="vtprd-discount-total-' .$execType. ' vtprd-new-subtotal-line" >';
      $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value'].'" class="vtprd-discount-total-' .$execType. '-line">';
      $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
      
      $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
      $output .= $vtprd_setup_options['' .$execType. '_new_subtotal_label'];
      $output .= '</span>';
  
  
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      //v1.0.8.9a begin  
      //$subTotal  = $woocommerce->cart->subtotal;
       
      if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
    		$subtotal = $woocommerce->cart->subtotal_ex_tax ;
    	} else {
    		$subtotal = $woocommerce->cart->subtotal;
      }  
             
      // pick up included, excluded or yousave_cart_total_amt Total       
      //$subTotal -= $vtprd_cart->cart_discount_subtotal;
      $subtotal -= vtprd_load_cart_total_incl_excl();
      $amt = vtprd_format_money_element($subtotal); 
      //v1.0.8.9a end  
            
      
      $labelType = $execType . '_credit_detail_label';  
      $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. ' vtprd-new-subtotal-amt"> &nbsp;&nbsp;' .$amt . '</span>';
      
      $output .= '</div>'; //end prodline
      $output .= '</td>';
      $output .= '</tr>'; 

      echo  $output;
   
    return;  
  }
    
     
	function vtprd_print_cart_discount_total($execType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
    
    $printRowsCheck = 'show_' .$execType. '_discount_total_line';
    
    if ($vtprd_setup_options[$printRowsCheck] == 'no') {
      return;
    }
    $output = ''; //v1.0.7.9
    $output .= '<tr class="vtprd-discount-total-' .$execType. ' vtprd-discount-line" >';    
    $output .= '<td colspan="' .$vtprd_setup_options['' .$execType. '_html_colspan_value']. '" class="vtprd-discount-total-' .$execType. '-line ">';
    $output .= '<div class="vtprd-discount-prodLine-' .$execType. '" >';
    
    $output .= '<span class="vtprd-discount-totCol-' .$execType. '">';
    $output .= $vtprd_setup_options['' .$execType. '_credit_total_title'];
    $output .= '</span>';

    $amt = vtprd_format_money_element($vtprd_cart->cart_discount_subtotal);
    
    $output .= '<span class="vtprd-discount-totAmtCol-' .$execType. ' vtprd-discount-amt">' . $vtprd_setup_options['' .$execType. '_credit_detail_label'] . ' ' .$amt . '</span>';
     
    $output .= '</div>'; //end prodline
    $output .= '</td>';
    $output .= '</tr>';
    echo  $output;
       
    return;
    
  }
   
    
     /*
    \n = CR (Carriage Return) // Used as a new line character in Unix
    \r = LF (Line Feed) // Used as a new line character in Mac OS
    \n\r = CR + LF // Used as a new line character in Windows
    (char)13 = \n = CR // Same as \n
    http://en.wikipedia.org/wiki/Newline
    */
  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_email_cart_reporting($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options;
    $output = ''; //v1.0.7.9
    
    if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {
      if ($msgType == 'html') {
        //Skip a line between products and discounts      		
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>'; 
        
        //v1.1.8.0 begin 
        if ( apply_filters('vtprd_show_discount_reporting_title',TRUE) ) {
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;"  colspan="3">' . __('Discount Reporting', 'vtprd') .'</td>';				
        $output .= '</tr>';
        }  
        //v1.1.8.0 end
                
        //New headers, but printed as TD instead, to keep the original structure going...                    
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;">' . __('Discount Product', 'vtprd') .'</td>';			
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;">' . __('Quantity', 'vtprd') .'</td>';			
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;">' . __('Amount', 'vtprd') .'</td>';		
        $output .= '</tr>';    
      
      } else {
        //first a couple of page ejects
        $output .= "\r\n \r\n";
        $output .= __( 'Discounts ', 'vtprd' );
        $output .= "\r\n";
      }
    }
 
    if ($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') { //v1.0.9.0
      //get the discount details    
      $output .= vtprd_email_cart_discount_rows($msgType);
    }
     
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
        
        //v1.0.8.9a begin               
        //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);        
        $amt = vtprd_load_cart_total_incl_excl();
        $amt = vtprd_format_money_element($amt); 
        $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4         
        //v1.0.8.9a end  
                  
      if ($msgType == 'html') {        
        //v1.0.8.9a begin               
        //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt); 
        //$amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4             
        //v1.0.8.9a end       
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold"  colspan="2">'. $vtprd_setup_options['checkout_credit_total_title'] .'</td>';						
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</td>';		
        $output .= '</tr>';   
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>';
      } else {
        $output .= "\r\n";
        $output .= "\n" .$vtprd_setup_options['checkout_credit_total_title'];
        $output .= "\n" .$vtprd_setup_options['checkout_credit_total_label'] .$amt ;
      }
    }      

      //v1.1.7.2 Begin
      if ($msgType == 'html') {
        //Skip a line at end      		
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>';   
      
      } else {
        $output .= "\r\n";
      }
      //v1.1.7.2 end

                   
    return $output;
    
  }
  
  //coupon discount only shows at Checkout 
	function vtprd_email_cart_coupon_discount_row($msgType) {
    global $vtprd_cart, $vtprd_rules_set, $vtprd_setup_options;

    $output = ''; //v1.0.7.9
    $amt = vtprd_format_money_element($vtprd_cart->wpsc_orig_coupon_amount);  //show original coupon amt as credit
    
    vtprd_format_money_element($vtprd_cart->wpsc_orig_coupon_amount);  //show original coupon amt as credit
       
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . __('Coupon Discount', 'vtprd') .'</td>';
        $output .= '<td>' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';
      $output .= '</tr>';    
    } else {
      $output .= __('Coupon Discount: ', 'vtprd'); 
      
      $output .= $amt;
      $output .= "\r\n \r\n";
    }

    return $output; 
    
  }      
    
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_email_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	//if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) { 
        //v1.1.0.6 end
        if ( ($vtprd_cart->cart_items[$k]->yousave_total_amt > 0) || 
             ($vtprd_cart->cart_items[$k]->zero_price_auto_add_free_item == 'yes') ) {
        //v1.1.0.6 end           
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                    if ($msgType == 'html')  {
                        $output .= '<tr  class="vtprd-rule-msg-checkout"  >';
                        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;" colspan="3">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';				
                        $output .= '</tr>';                       
                    } else {
                      $output .= "\n" .  stripslashes($yousave_by_rule['rule_short_msg']) . "\r\n"; 
                    }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_email_discount_detail_line($amt, $units, $msgType, $k); 
              
                 // } 
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_email_discount_detail_line($amt, $units, $msgType, $k);
           }
        }
      }

    return $output;
    
  }

    
	function vtprd_email_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9
          
      //v1.0.7.4 begin      
      //$amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
      // $amt = vtprd_format_money_element($amt); //mwn
      //v1.0.7.4 end 
         
     
     //v1.1.0.6 begin      
    if ( ($amt == 0) && 
         (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE
      $amt = __('Free', 'vtprd');
    } else {
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn  //v1.0.7.4 begin
    }
    // $amt = vtprd_format_money_element($amt);
    //v1.1.0.6 end   
        
         
         
    if ($msgType == 'html')  {
      $output .= '<tr>';

      if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
        $output .= '<small>';
        //v1.0.7.9 begin
        if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
          $variation_name_html = $vtprd_cart->cart_items[$k]->variation_name_html;
          //remove wrapping paragraph on variation name...
          $variation_name_html = str_replace( '<p>',  '', $variation_name_html  );
          $variation_name_html = str_replace( '</p>', '', $variation_name_html  );                                                                               
           $output .= $variation_name_html;
        } else {
        //v1.0.7.9 end        
           // $output .= '<dl class="variation">';
          foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
            //v1.0.7.8  begin                
            $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
            $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
            $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
            $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
            $current_version =  WOOCOMMERCE_VERSION;
            if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
              //pre 2.1
              $name  = sanitize_title($name);
              $value = sanitize_title($value);
              $name  = ucwords($name);  
              $output .= '<br class="variation-'.$name.'">'. $name . ': ' .$value ;  //added class
            } else {
              //post 2.1
              $name2 = sanitize_text_field( $name );
              $output .= '<br class="variation-'.sanitize_html_class( $name2 ).'">'. wp_kses_post( $name ) . ': ' .wp_kses_post( wpautop( $value ));  //added class
            }
            //v1.0.7.8  end                       
          }
          //$output .= '</dl></small>'; 
        }  //v1.0.7.9 
        $output .= '</small>';      			
        $output .= '</td>';     
      } else {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word">' . $vtprd_cart->cart_items[$k]->product_name .'</td>';
      }
			
      $output .= '<td class="vtprd-quantity-email" style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $units .'</td>';			
      $output .= '<td class="vtprd-amount-email"  style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';		
      $output .= '</tr>';        
    } else {
      if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {  //v1.0.9.0 
        $output .= "\n" . __( 'Product: ', 'vtprd' ); 
        $output .= "\n" . $vtprd_cart->cart_items[$k]->product_name;
        $output .= "\n" . __( ' Discount Units: ', 'vtprd' );
        $output .= "\n" . $units ;
        
        
        //v1.0.9.0 added new title
        if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
          $output .= "\n" . __( ' Discount Amount: ', 'vtprd' );
        } else {
          $output .= "\n" . __('  Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd');
        } 
        
        $output .= "\n" . $amt;
        $output .= "\r\n";
      }
    }
    
    return  $output;  
 }
   
	function vtprd_email_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td  class="vtprd-subtotal-email" colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }
 
     
	function vtprd_email_cart_discount_total($msgType) {
    global $vtprd_cart, $vtprd_rules_set, $vtprd_setup_options;

    $output = ''; //v1.0.7.9
      
    //v1.0.8.9a begin               
    // pick up included, excluded or yousave_cart_total_amt Total       
    $amt = vtprd_load_cart_total_incl_excl();
    //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
    $amt = vtprd_format_money_element($amt);
    $amt .= vtprd_maybe_load_incl_excl_vat_lit(); 
    //v1.0.8.9a end  

    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_total_title'] .'</td>';
        $output .= '<td>' . $vtprd_setup_options['checkout_credit_total_label'] . ' ' .$amt .'</td>';
      $output .= '</tr>';   
    } else {      
      $output .= $vtprd_setup_options['checkout_credit_total_title'];          //Discount Total
      $output .= $amt ;
      $output .= "\r\n";        
    }
    
    return $output;  
    
  }
   
	
  //***************************************
  // Subtotal with Discount:  (email)
  //***************************************
  function vtprd_email_new_cart_checkout_subtotal_line($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

      $output = ''; //v1.0.7.9
   
      // for wpec $vtprd_cart->cart_original_total_amt is not accurate - use wpec's own routine
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      
     
      //v1.0.8.9a begin  
      //$subTotal  = $woocommerce->cart->subtotal;
       
      if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
    		$subtotal = $woocommerce->cart->subtotal_ex_tax ;
    	} else {
    		$subtotal = $woocommerce->cart->subtotal;
      }  
      //v1.0.8.9a end   
            
      //v1.0.8.9a no longer needed  vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    

      //*****************************
      //No longer used - $subTotal -= $vtprd_cart->yousave_cart_total_amt;
      //*****************************
      //v1.0.8.9a begin               
      // pick up included, excluded or yousave_cart_total_amt Total       
      //$subTotal -= $vtprd_cart->cart_discount_subtotal;  /may or may not contain the coupon amount, depending on passed value calling function
      $subtotal -= vtprd_load_cart_total_incl_excl();
      $amt = vtprd_format_money_element($subtotal);
      //v1.0.8.9a end              
 
      $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4
      
      if ($msgType == 'html')  {
        $output .= '<tr>';
          $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_new_subtotal_label'] .'</td>';
          $output .= '<td>' . $amt .'</td>';
        $output .= '</tr>';
      } else {
        $output .= $vtprd_setup_options['checkout_new_subtotal_label'];
        $output .= '  '; 
        $output .= $amt;
        $output .= "\r\n";        
      }
    
    return $output; 
  }  

   

  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_thankyou_cart_reporting() {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $woocommerce;
    $output = ''; //v1.0.7.9
   	
    $output .=  '<h2 id="vtprd-thankyou-title">' . __('Cart Discount Details', 'vtprd') .'</h2>'; //v1.0.9.5  closing '</h2>' fixed
     	
    $output .= '<table class="shop_table order_details vtprd-thankyou-table">';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th class="product-name">' . __('Discount Product', 'vtprd') .'</th>';
    
    //v1.0.9.0 added new title
    if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
      $output .= '<th class="product-name">' . __('Discount Amount', 'vtprd') .'</th>';	
    } else {
      $output .= '<th class="product-name">' . __('Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd') .'</th>';	
    }
    
    $output .= '</tr>';    
    $output .= '</thead>';
    
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if (($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') || 
        ($vtprd_setup_options['checkout_new_subtotal_line']        == 'yes')) {
        $output .= '<tfoot>';
        if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {

            //v1.0.8.9a begin               
            // pick up included, excluded or yousave_cart_total_amt Total       
            $amt = vtprd_load_cart_total_incl_excl();
            //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
            $amt = vtprd_format_money_element($amt);
            //v1.0.8.9a end            
            
            $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4  
            $output .= '<tr class="checkout_credit_total">';
            $output .= '<th scope="row">'. $vtprd_setup_options['checkout_credit_total_title'] .'</th>';						
            $output .= '<td><span class="amount">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</span></td>';		
            $output .= '</tr>';
        }
        /*
        if ($vtprd_setup_options['checkout_new_subtotal_line'] == 'yes') {
            //can't use the regular routine ($subtotal = vtprd_get_Woo_cartSubtotal(); ), as it returns a formatted result
           if ( $woocommerce->tax_display_cart == 'excl' ) {
        			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
        		} else {
        			$subtotal = $woocommerce->cart->subtotal;
            }   
            $subtotal -= $vtprd_cart->yousave_cart_total_amt;
            $amt = vtprd_format_money_element($subtotal);              
            $output .= '<tr class="checkout_new_subtotal">';
            $output .= '<th scope="row">'. $vtprd_setup_options['checkout_new_subtotal_label'] .'</th>';						
            $output .= '<td><span class="amount">'  . $vtprd_setup_options['checkout_credit_detail_label'] .$amt .'</span></td>';		
            $output .= '</tr>';
        }  
        */      
        $output .= '</tfoot>';   
    }   
 
    $output .= '<tbody>';
 
    //get the discount details    
    $output .= vtprd_thankyou_cart_discount_rows($msgType);
  
    $output .= '</tbody>';
    $output .= '</table>';
           
    return $output;
    
  }
    
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_thankyou_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	//if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) {  
        //v1.1.0.6 end
        if ( ($vtprd_cart->cart_items[$k]->yousave_total_amt > 0) || 
             ($vtprd_cart->cart_items[$k]->zero_price_auto_add_free_item == 'yes') ) {
        //v1.1.0.6 end          
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                      $output .= '<tr class = "order_table_item">';
                      $output .= '<td  class="product-name">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';
                      //td with blank needed to complete the border line in the finished product
                      $output .= '<td  class="product-name">&nbsp;</td>';				
                      $output .= '</tr>';                       
                 // }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_thankyou_discount_detail_line($amt, $units, $msgType, $k); 
              
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_thankyou_discount_detail_line($amt, $units, $msgType, $k);
           }
        }
      }

    return $output;
    
  }
     
	function vtprd_thankyou_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9
    
    //v1.0.7.4 begin      
    //$amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
    // $amt = vtprd_format_money_element($amt); //mwn
    //v1.0.7.4 end

    //v1.1.0.6 begin      
    if ( ($amt == 0) && 
         (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE
      $amt = __('Free', 'vtprd');
    } else {
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn  //v1.0.7.4 begin
    }
    // $amt = vtprd_format_money_element($amt);
    //v1.1.0.6end
    
    
    
    $output .= '<tr class = "order_table_item">';
    /*
    $output .= '<td  class="product-name">' . $vtprd_cart->cart_items[$k]->product_name ;
    $output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';				
    $output .= '</td>';
    */

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<td  class="product-name vtprd-product-name" ><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
      $output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';	
           
      //v1.0.7.9 begin
      if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
        $output .= $vtprd_cart->cart_items[$k]->variation_name_html;
      } else {
        $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin              
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
          $name  = ucwords($name);
          $current_version =  WOOCOMMERCE_VERSION;
          if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
            //pre 2.1
            $name  = sanitize_title($name);
            $value = sanitize_title($value);            
            $output .= '<dt class="variation-'.$name.'">'. $name . ': </dt>';  //added class
            $output .= '<dd class="variation-'.$name.'">'. $value .'</dd>';    //added class
          } else {
            //post 2.1
            //$name2 = sanitize_text_field( $name );
            $name2 = sanitize_title( $name );
            $name2_san = sanitize_html_class( $name2 );
            $output .= '<dt class="variation-'. $name2_san.'">'. wp_kses_post( $name ) . ': </dt>';  //added class
            $output .= '<dd class="variation-'. $name2_san.'">'. wp_kses_post( wpautop( $value )) .'</dd>';    //added class
          }
          //v1.0.7.8  end              
        }
        $output .= '</dl>';
      }
      //v1.0.7.9 end
      $output .= '</td>';     
    } else {
      $output .= '<td  class="product-name" >' . $vtprd_cart->cart_items[$k]->product_name ;
			$output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';	
      $output .= '</td>';
    }

    
    $output .= '<td  class="product-total">';
    $output .= '<span class="amount">' . $vtprd_setup_options['checkout_credit_detail_label'] .$amt .'</span>';				
    $output .= '</td>';

    
                          
    $output .= '</tr>'; 
    
    return  $output;  
 }
   
	function vtprd_thankyou_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }


  

  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_checkout_cart_reporting($msgType) {      //v1.0.8.0
    //error_log( print_r(  'function vtprd_checkout_cart_reporting begin ', true ) ); //v2.0.0 test 
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options, $woocommerce;
    $output = ''; //v1.0.7.9        
   	if (($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') ||  
        ($vtprd_setup_options['show_checkout_discount_total_line']   == 'yes') ||  
        ($vtprd_setup_options['checkout_new_subtotal_line']          == 'yes') ) {	
            $output .= '<table class="shop_table cart vtprd_shop_table" cellspacing="0">';
            $output .= '<thead>';
            $output .= '<tr class="checkout_discount_headings">';
            $output .= '<th  class="product-name" >' . __('Discount Product', 'vtprd') .'</th>';
            $output .= '<th  class="product-quantity">' . __('Quantity', 'vtprd') .'</th>';
            	            
            //v1.0.9.0 added new title
            if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
              $output .= '<th  class="product-subtotal" >' . __('Discount Amount', 'vtprd') .'</th>';
            } else {
              $output .= '<th  class="product-subtotal" >' . __('Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd') .'</th>';
            }
            
            $output .= '</tr>';    
            $output .= '</thead>';
    } 
   
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if (($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') || 
        ($vtprd_setup_options['checkout_new_subtotal_line']        == 'yes')) { 
        $output .= '<tfoot>';
         if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
      
            //v1.0.8.9a begin               
            // pick up included, excluded or yousave_cart_total_amt Total       
            $amt = vtprd_load_cart_total_incl_excl();
            //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
            $amt = vtprd_format_money_element($amt);
            //v1.0.8.9a end
      
            $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4  
            $output .= '<tr class="checkout_discount_total_line">';
            $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_credit_total_title'] .'</th>';						
            $output .= '<td ><span class="amount">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</span></td>';		
            $output .= '</tr>';
        }
         
        if ($vtprd_setup_options['checkout_new_subtotal_line'] == 'yes') {
            //can't use the regular routine ($subtotal = vtprd_get_Woo_cartSubtotal(); ), as it returns a formatted result
           if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
        			$subtotal = $woocommerce->cart->subtotal_ex_tax ;
        		} else {
        			$subtotal = $woocommerce->cart->subtotal;
            }   

            //v1.0.8.9a begin               
            // pick up included, excluded or yousave_cart_total_amt Total       
            //$subtotal -= $vtprd_cart->yousave_cart_total_amt;
            $subtotal -= vtprd_load_cart_total_incl_excl();
            $amt = vtprd_format_money_element($subtotal);
            //v1.0.8.9a end  

            $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4
                               
            $output .= '<tr class="checkout_new_subtotal">';
            $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_new_subtotal_label'] .'</th>';						
            $output .= '<td ><span class="amount">'  . $amt .'</span></td>';		
            $output .= '</tr>'; 
        }        
        $output .= '</tfoot>';   
    }   
 
    $output .= '<tbody>';

    //new    
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'beforeDiscounts') {
      $amt = vtprd_get_Woo_cartSubtotal();
      $output .= '<tr class="checkout_purchases_subtotal">';
      $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_credit_subtotal_title'] .'</th>';						
      $output .= '<td ><span class="amount">'   .$amt .'</span></td>';		
      $output .= '</tr>';
    }
 
    if ($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') {
      //get the discount details    
      $output .= vtprd_checkout_cart_discount_rows($msgType);
     }
     
    if ($vtprd_setup_options['show_checkout_purchases_subtotal'] == 'withDiscounts') {
      $amt = vtprd_get_Woo_cartSubtotal();
      $output .= '<tr class="checkout_purchases_subtotal">';
      $output .= '<th scope="row" colspan="2">'. $vtprd_setup_options['checkout_credit_subtotal_title'] .'</th>';						
      $output .= '<td ><span class="amount">'   .$amt .'</span></td>';		
      $output .= '</tr>';
    }


    $output .= '</tbody>';
    $output .= '</table>';
    
    echo $output;
           
    return;
    
  }
    
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_checkout_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	//if (  $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) { 
       	if ( ($vtprd_cart->cart_items[$k]->yousave_total_amt > 0) ||  //v1.1.0.6
             ($vtprd_cart->cart_items[$k]->zero_price_auto_add_free_item == 'yes') ) {    //v1.1.0.6                        
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                      $output .= '<tr class = "order_table_item">';
                      $output .= '<td  class="product-name vtprd-rule_msg" colspan="3">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';			
                      $output .= '</tr>';                       
                 // }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k); 
              
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k);
           }
           
           //v1.0.9.3 begin
           if ( ($vtprd_setup_options['discount_taken_where'] == 'discountUnitPrice') &&
                ($vtprd_setup_options['show_unit_price_cart_discount_computation'] == 'yes') )  {
              $computation_summary = $vtprd_cart->cart_items[$k]->computation_summary; 
              $output .= '<tr class = "order_table_item">';
              $output .= '<td  class="unit-price-computation" colspan="3">' . $computation_summary  .'</td>';			
              $output .= '</tr>';             
           }
           //v1.0.9.3 end
        }
      }

    return $output;
    
  }
     
	function vtprd_checkout_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9

      //v1.1.0.6 begin      
      if ( ($amt == 0) &&
           (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE 
        $amt = __('Free', 'vtprd');
      } else {
        $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn  //v1.0.7.4 begin
      }
      // $amt = vtprd_format_money_element($amt);
      //v1.1.0.6end
    
    $output .= '<tr class = "order_table_item">';

    if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
      $output .= '<td  class="product-name vtprd-product-name" ><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
      
      //v1.0.7.9 begin
      if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
        $output .= $vtprd_cart->cart_items[$k]->variation_name_html;
      } else {
        $output .= '<dl class="variation">';
        foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
          //v1.0.7.8  begin               
          $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
          $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
          $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
          $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
          $name  = ucwords($name);
          $current_version =  WOOCOMMERCE_VERSION;
          if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
            //pre 2.1
            $name  = sanitize_title($name);
            $value = sanitize_title($value);            
            $output .= '<dt class="variation-'.$name.'">'. $name . ': </dt>';  //added class
            $output .= '<dd class="variation-'.$name.'">'. $value .'</dd>';    //added class
          } else {
            //post 2.1
            //$name2 = sanitize_text_field( $name );
            $name2 = sanitize_title( $name );
            $name2_san = sanitize_html_class( $name2 );
            $output .= '<dt class="variation-'. $name2_san.'">'. wp_kses_post( $name ) . ': </dt>';  //added class
            $output .= '<dd class="variation-'. $name2_san.'">'. wp_kses_post( wpautop( $value )) .'</dd>';    //added class
          }
          //v1.0.7.8  end              
        }
        $output .= '</dl>';
      }
      //v1.0.7.9 end
 
            			
      $output .= '</td>';
      //$output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';	     
    } else {
      $output .= '<td  class="product-name" >' . $vtprd_cart->cart_items[$k]->product_name ;
     // $output .= '<strong class="product-quantity"> &times; ' . $units  .'</strong>';				
      $output .= '</td>';
    }

    $output .= '<td  class="product-quantity" style="text-align:middle;">' . $units .'</td>';
    
    $output .= '<td  class="product-total">';
    
    //v1.1.0.6 begin
    if ( $amt == __('Free', 'vtprd') ) {
      $output .= '<span class="amount">'  .$amt .'</span>';
    } else {
      $output .= '<span class="amount">' . $vtprd_setup_options['checkout_credit_detail_label'] .$amt .'</span>';	
    }
    //v1.1.0.6 end
          			
    $output .= '</td>';
                          
    $output .= '</tr>'; 
    
    return  $output;  
 }
   
	function vtprd_checkout_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }


  
  
  function vtprd_numberOfDecimals($value) {
      if ((int)$value == $value) {
          return 0;
      }
      else if (! is_numeric($value)) {
          // throw new Exception('numberOfDecimals: ' . $value . ' is not a number!');
          return false;
      }
  
      return strlen($value) - strrpos($value, '.') - 1;  
  }

   function vtprd_print_rule_full_msg($i) { 
    global $vtprd_rules_set;
    $output  = '<span  class="vtprd-full-messages" id="vtprd-category-deal-msg' . $vtprd_rules_set[$i]->post_id . '">';
    $output .= stripslashes($vtprd_rules_set[$i]->discount_product_full_msg);
    $output .= '</span>'; 
    return $output;    
   }  


  // ****************  
  // Date Validity Rule Test
  // ****************             
   function vtprd_rule_date_validity_test($i) {  
       global $vtprd_rules_set, $vtprd_setup_options; //v2.0.0.9 added $vtprd_setup_options
          
      //v2.0.0.9 begin
      if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
        error_log( print_r(  'Begin vtprd_rule_date_validity_test DATE TESTING , $i = ' .$i. ' Rule ID = ' .$vtprd_rules_set[$i]->post_id, true ) );
      } 
      //v2.0.0.9 end 
                  
       switch( $vtprd_rules_set[$i]->rule_on_off_sw_select ) {
          case 'on':  //continue, use scheduling dates
            break;
          case 'off': //rule is always off!!!
                  
              //v2.0.0.9 begin
              if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
                error_log( print_r(  'Rule is set to "OFF" ', true ) );    
              } 
              //v2.0.0.9 end 
      
              return false;
            break;
          case 'onForever': //rule is always on!!
                  
              //v2.0.0.9 begin
              if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
                error_log( print_r(  'Rule is set to "ON FOREVER" ', true ) );    
              } 
              //v2.0.0.9 end 
      
              return true;
            break;
        }

       //v1.1.1.3 Begin
       //CHange to use DateTime functions
/*
       $today = date("Y-m-d");
       
       for($t=0; $t < sizeof($vtprd_rules_set[$i]->periodicByDateRange); $t++) {
          if ( ($today >= $vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeBeginDate']) &&
               ($today <= $vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeEndDate']) ) {
             return true;  
          }
       }
 */
       //v2.0.2.0 begin  old date function returned UTC time, not set timezone.
       //$today = date("Y-m-d");
       global  $vtprd_info;
       if (isset($vtprd_info['timezoneOffset'])) {
          $today =  vtprd_get_current_time( "Y-m-d" ); //picks up plugin timezone !!!!!!  
       }  else {
          $today =  current_time( "Y-m-d" ); //picks up wp_timezone !!!!!!
       }
       //v2.0.2.0 end


       for($t=0; $t < sizeof($vtprd_rules_set[$i]->periodicByDateRange); $t++) {
          $rangeBeginDate = $vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeBeginDate'];
          $rangeEndDate   = $vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeEndDate'];
          
          //v2.0.0.9 begin
          if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
            error_log( print_r(  'Dates as Stored: Begin= ' .$vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeBeginDate'] .' End= ' .$vtprd_rules_set[$i]->periodicByDateRange[$t]['rangeEndDate'], true ) );
            error_log( print_r(  'Date Today= ' .$today , true ) );       
          } 
          //v2.0.0.9 end         

          if ( ($today >= $rangeBeginDate) &&
               ($today <= $rangeEndDate) ) {
          
              //v2.0.0.9 begin
              if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
                error_log( print_r(  'Today IS within the specified range for rule=  ' .$vtprd_rules_set[$i]->post_id, true ) );       
              } 
              //v2.0.0.9 end 
              
             return true;  
          }
       }       
       
       //v1.1.1.3 End 
          
      //v2.0.0.9 begin
      if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
        error_log( print_r(  'Today IS NOT within the specified range ', true ) );       
      } 
      //v2.0.0.9 end 
                      
       return false; //marks test as invalid
   }   


   //v2.0.0  This function KEPT for conversion report display ONLY!! 
  /* ************************************************
  *    PRODUCT META INCLUDE/EXCLUDE RULE ID LISTS
  *       Meta box added to PRODUCT in rules-ui.php 
  *             updated in pricing-deals.php    
  * ************************************************               
  **   Products can be individually added to two lists:
  *       Include only list - **includes** the product in a rule population 
  *         *only" if:
  *           (1) The product already participates in the rule
  *           (2) The product is in the include only rule list 
*       Exclude list - excludes the product in a rule population 
  *         *only" if:
  *           (1) The product already participates in the rule
  *           (2) The product is in the exclude rule list        
  *************************************************** */  

  //depending on the switch setting, this will be either include or exclude - but from the function's
  //  point of view, it doesn't matter...
  function vtprd_fill_include_exclude_lists($prod_id,$details) { 
      global $wpdb, $vtprd_setup_options;
      
      //error_log( print_r(  'function begin vtprd_fill_include_exclude_lists, $includeOrExclude_option= ' .$includeOrExclude_option. ' $checked_list= ' , true ) );
      //error_log( var_export($checked_list, true ) );
      

      /*
         'details' => 
        array (
          'includeOrExclude_option' => 'includeAll',
          'includeOrExclude_included_rules' => 
          array (
          ),
          'includeOrExclude_excluded_rules' => 
          array (
          ),
        ),     
      
     'excludeOrExclude_excluded_rules' => 
      array (
        0 => 
        array (
          'rule_id' => 2362,
          'rule_title' => '7. Cart Rule - Bogo Next - BUY Group: 1349 AND author - GET Group: Varname=seven, and shoes or test cat',
          'rule_change_msg' => 'Product added to Product Exclude list for Rule.',
        )
      */

      $output = false;
      //Include or Exclude list
     
      //*includes* exist only on this single path...
      if (sizeof($details['includeOrExclude_included_rules']) > 0) {
        $output  .= '<li class="inOrEx-li-overtitle">Add Product ' .$prod_id.  ' to Product Includes for listed Rules, Exclude Product in all other Rules</li>' ;
        $output  .= '<li class="inOrEx-li-detailTitle">&nbsp;&nbsp;-- Product INCLUDED in these Pricing Deals Rules --</li>' ;      
        foreach ($details['includeOrExclude_included_rules'] as  $includeOrExclude_included_rules) {                   
          $array_rule_id = $includeOrExclude_included_rules['rule_id'];
          $array_rule_title = $includeOrExclude_included_rules['rule_title'];
          $temp = '<li class="inOrEx-li-details">' ;
          $temp  .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a class="" target="_blank" href="/wp-admin/post.php?post=' .$array_rule_id. '&action=edit">' .$array_rule_title. '</a>';             
          $temp  .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em style="color:gray;">' . $includeOrExclude_included_rules['rule_change_msg'] .'</em>';
          $temp  .= '</li>'; 
          $output  .= $temp;                                                   
        }
      }
              
      if (sizeof($details['includeOrExclude_excluded_rules']) > 0) {
        switch ($details['includeOrExclude_option']) {
            case 'includeList': 
                $output  .= '<li class="inOrEx-li-overtitle">Add Product ' .$prod_id.  ' to Product Exclude List for all other Rules</li>' ;
              break;
            case 'excludeList': 
                $output  .= '<li class="inOrEx-li-overtitle">Add Product ' .$prod_id.  ' to Product Exclude List for these Rules</li>' ;
              break;
            case 'excludeAll': 
                $output  .= '<li class="inOrEx-li-overtitle">Add Product ' .$prod_id.  ' to Product Excludes for All Rules</li>' ;
              break;
        } 
        $output  .= '<li class="inOrEx-li-detailTitle">&nbsp;&nbsp;-- Product EXCLUDED in these Pricing Deals Rules --</li>' ;
        foreach  ($details['includeOrExclude_excluded_rules'] as  $includeOrExclude_excluded_rules) {                              
          if ($includeOrExclude_excluded_rules['rule_change_msg'] <= ' ') {
            $includeOrExclude_excluded_rules['rule_change_msg'] = 'Product added to Product Exclude list for Rule';
          }
          $array_rule_id = $includeOrExclude_excluded_rules['rule_id'];
          $array_rule_title = $includeOrExclude_excluded_rules['rule_title'];
          $temp = '<li class="inOrEx-li-details">' ;
          $temp  .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a class="" target="_blank" href="/wp-admin/post.php?post=' .$array_rule_id. '&action=edit">' .$array_rule_title. '</a>';             
          $temp  .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em style="color:gray;">' . $includeOrExclude_excluded_rules['rule_change_msg'] .'</em>';
          $temp  .= '</li>'; 
          $output  .= $temp;                                                   
        }

      }      

       
      //error_log( print_r(  '$includeOrExclude_option= ' .$includeOrExclude_option. ' Fall-Through ' , true ) );     
 
      //error_log( print_r(  '$include_array= ' , true ) );
      //error_log( var_export($include_array, true ) );
      //error_log( print_r(  '$exclude_array= ' , true ) );
      //error_log( var_export($exclude_array, true ) );
      //error_log( print_r(  '$output= ' , true ) );
      //error_log( var_export($output, true ) );
 
        echo  $output;
 
      return;   
  }
  
  //*********************
  //v2.0.2.0 reworked
  //*********************
  function vtprd_set_selected_timezone() {
   global $vtprd_setup_options, $vtprd_info;
     
     if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' )) ) {  
        return;          
     } 
    
    switch( $vtprd_setup_options['use_this_timeZone'] ) {
      case 'none':
      case 'keep':
        break;
      default:

            $vtprd_info['timezoneOffset'] = $vtprd_setup_options['use_this_timeZone'];
            
            if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
              error_log( print_r(  'vtprd_set_selected_timezone ==> timezone Offset= ' .$vtprd_info['timezoneOffset'], true ) );       
            }         
          
        break;
    }
    
    return;
  }
  
  //*********************
  //v2.0.2.0 new function
  //*********************
  function vtprd_get_current_time($type, $gmt = 0) {  
    global $vtprd_setup_options, $vtprd_info;

    $vtprd_offset = new DateTimeZone( $vtprd_info['timezoneOffset'] ); 

    $timezone = $gmt ? new DateTimeZone( 'UTC' ) : $vtprd_offset;
	$datetime = new DateTime( 'now', $timezone );
    
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
      $datetimeSTR = date_format($datetime,"Y/m/d H:i:s");
      error_log( print_r(  'vtprd_get_current_time ==> timezone Offset= ' .$vtprd_info['timezoneOffset'].  ' today before formatting= ' .$datetimeSTR, true ) );      
    }
    
	return $datetime->format( $type );     

  }
  
   

	//this routine only gets previouosly-stored session info
  function vtprd_maybe_get_product_session_info($product_id) {
    global $vtprd_info;   

    //v2.0.0.9  begin - 'product_session_info' needed for Addons and Measurement Price Calculator plugins
    if ( (class_exists('WC_Product_Addons')) ||
         (class_exists('WC_Measurement_Price_Calculator')) ) {
      $carry_on = true;     
    } else {     
      //v2.0.0 begin M solution  
      if ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') {
            $vtprd_info['product_session_info'] = array();
                //error_log( print_r(  'vtprd_maybe_get_product_session_info 000, no display rule, SKIP THIS PRODUCT ', true ) );                    
            return;
      }
      //v2.0.0 end M solution
    }
    //v2.0.0.9 end

    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  
    
     
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
        $product = wc_get_product($product_id);
       if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
          $product_type = $product->get_type();
       } else {
          $product_type = $product->product_type;
       }
       $disallow_these_types = apply_filters('vtprd_disallow_product_types',FALSE);
  
  //error_log( print_r(  'vtprd_disallow_product_types 002, product_type = ' .$product_type .'disallow array= ', true ) );
  //error_log( var_export($disallow_these_types, true ) );         

       if ( (is_array($disallow_these_types)) &&
            (in_array($product_type, $disallow_these_types)) ) {
          $vtprd_info['product_session_info'] = array();
  //error_log( print_r(  'vtprd_disallow_product_types 002, SKIP THIS PRODUCT ', true ) );                    
          return;
       } 
    }              
    //v1.1.8.3 end    
       
     
    
    // ********************************************************
    //this routine is also called during cart processing.             
    //  if so, get the session info if there, MOVE it to VTPRD_INFO and exit
    // ********************************************************
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {      
      $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];
      //v2.0.0 begin
      //If there but AGED OUT, get it again!
      $current_time_in_seconds = time(); 
      if ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '600' ) {   //session data older than 10 minutes //v2.0.0      
         vtprd_get_product_session_info($product_id);
      }   
      //v2.0.0 end
    } else {
      //v1.0.9.0 begin
      // we'll want to pick up the Catalog discount, if any...
      global $vtprd_info;  
      
      //v2.0.0.9  begin - 'product_session_info' needed for Addons and Measurement Price Calculator plugins
      if ( ($vtprd_info['ruleset_has_a_display_rule'] == 'yes') || 
           (class_exists('WC_Product_Addons')) ||
           (class_exists('WC_Measurement_Price_Calculator')) ) {
          vtprd_get_product_session_info($product_id);
      //v2.0.0.9  end
      } else {
          $vtprd_info['product_session_info'] = array();
      }
      //v1.0.9.0 end
    }

           
  }  
          
   /* ************************************************
  **  get display session info and MOVE to $vtprd_info['product_session_info']
  *  First time go to the DB.
  *  2nd thru nth go to session variable...
  *    If the ID is a Variation (only comes realtime from AJAX), the recompute is run to refigure price.
  *    
  * //$cart_processing_sw: 'yes' => only get the session info
  *                        'no'  => only get the session info
  *             
  *************************************************** */
	//PRICE only comes from  parent-cart-validation function vtprd_show_product_catalog_price
  // $product_info comes from catalog calls...
  function vtprd_get_product_session_info($product_id, $price=null){   
    global $post, $vtprd_info;

    //v2.0.0.9  begin - 'product_session_info' needed for Addons and Measurement Price Calculator plugins
    if ( (class_exists('WC_Product_Addons')) ||
         (class_exists('WC_Measurement_Price_Calculator')) ) {
      $carry_on = true;     
    } else {   
      //v2.0.0 begin M solution  
      if ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') {
            $vtprd_info['product_session_info'] = array();
              //error_log( print_r(  'vtprd_get_product_session_info 000, no display rule, SKIP THIS PRODUCT ', true ) );                    
            return;
      }
      //v2.0.0 end M solution
    }
    //v2.0.0.9 end
    
    //store product-specific session info
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  
       
    //v2.0.0 e Solution begin
    //if a CART is in progress, and session info (Catalog) pricing is called
    //In obtaining any Catalog pricing, vtprd_cart_item/cart/ruleset can be overwritten, losing working addressability.
    // save until  after Catalog pricing session processing complete.
    //   https://blog.nexcess.net/2013/10/31/php-sessions-and-objects/
    //     - don't serialize in the statment that stores the session variable!!!!!!
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set;

    $flattened_cart = serialize($vtprd_cart);
    
    //v2.0.2.0 begin
    //$_SESSION['flattened_cart_get_product_session_info'] = $flattened_cart;
                 
    if (isset($_SESSION['vtprd_unique_cart_id'])) {
      $temp_random_cart_id_set = FALSE;
    } else {  
      //random number, to make sure this is unique across a single DB with multiple simultaneous store customers
      // resets the unique session 
      //error_log( print_r(  'set random cart ID 003', true ) );
      vtprd_set_random_unique_cart_id($product_id);
      $temp_random_cart_id_set = TRUE;          
    }
    $cart_id = $_SESSION['vtprd_unique_cart_id'];
    
    //error_log( print_r(  'set 0003 ', true ) );
    vtprd_set_transient_cart_data ( 'flattened_cart_get_product_session_info', $flattened_cart, $cart_id );
    //v2.0.2.0 end
          
    $flattened_cart_item = serialize($vtprd_cart_item);
    //v2.0.2.0 begin
    //$_SESSION['flattened_cart_item_get_product_session_info'] = $flattened_cart_item;
    //error_log( print_r(  'set 0004 ', true ) );
    vtprd_set_transient_cart_data ( 'flattened_cart_item_get_product_session_info', $flattened_cart_item, $cart_id );
    //v2.0.2.0 end
     
    
    $flattened_ruleset = serialize($vtprd_rules_set);        
    //v2.0.2.0 begin
    //$_SESSION['flattened_ruleset_get_product_session_info'] = $flattened_ruleset;
    //error_log( print_r(  'set 0005 ', true ) );
    vtprd_set_transient_cart_data ( 'flattened_ruleset_get_product_session_info', $flattened_ruleset, $cart_id );
    //v2.0.2.0 end
        
    
    $save_current_processing_request = $vtprd_info['current_processing_request']; //v2.0.0 F Solution
    
    //v2.0.0 E Solution end


    //if already in the session variable... => this routine can be called multiple times in displaying a single catalog price.  check first if already done.
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {
       $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];   
      //will be a problem in Ajax...
      $current_time_in_seconds = time();          
      $user_role = vtprd_get_current_user_role();      
      
      //v2.0.0.9  begin - 'product_session_info' needed for Addons and Measurement Price Calculator plugins
      if ( (class_exists('WC_Product_Addons')) ||
           (class_exists('WC_Measurement_Price_Calculator')) ) {
        //just in case
        $vtprd_info['product_session_info']['user_role'] = $user_role;  
      } else { 
        //v2.0.0 begin K solution
        // if no display rule and already have session info, we're DONE!!
        if ($vtprd_info['ruleset_has_a_display_rule'] != 'yes') {
          //*****************************
          //v1.0.8.4 timestamp  begin
          $vtprd_ruleset_timestamp = get_option( 'vtprd_ruleset_timestamp' );  
          if (!$vtprd_ruleset_timestamp) {
            $vtprd_ruleset_timestamp = 0; 
          }     
          //v1.0.8.4 timestamp  end 
          //*****************************      
    
          if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '600' ) ||    //session data older than 10 minutes //v2.0.0
               (  $user_role != $vtprd_info['product_session_info']['user_role']) ||                                      //user role CHANGED via user login
               (  $vtprd_ruleset_timestamp > $vtprd_info['product_session_info']['session_timestamp_in_seconds'] ) ) {   //v1.0.8.4 timestamp - GET *more recent* ADMIN updates to ruleset NOW    
            vtprd_apply_rules_to_single_product($product_id, $price);
            //reset user role info, in case it changed
            $vtprd_info['product_session_info']['user_role'] = $user_role;
          }
        } else {
          //just in case
          $vtprd_info['product_session_info']['user_role'] = $user_role;
        }
        //v2.0.0 end K solution 
      } 
      //v2.0.0.9 end        
    } else { 
       //First time obtaining the info, also moves the data to $vtprd_info       
      vtprd_apply_rules_to_single_product($product_id, $price);
      // vtprd_apply_rules_to_vargroup_or_single($product_id, $price);        
    } 

             
    //v2.0.0 j Solution begin

    //v2.0.2.0 begin
    //$flattened_cart = $_SESSION['flattened_cart_get_product_session_info'];
    //error_log( print_r(  'before vtprd_get_transient_cart_data  0002 ', true ) );
    $flattened_cart = vtprd_get_transient_cart_data ( 'flattened_cart_get_product_session_info', $cart_id);
    vtprd_del_transient_cart_data_by_data_type ('flattened_cart_get_product_session_info', $cart_id);
    //v2.0.2.0 end            
    $vtprd_cart = unserialize($flattened_cart);
    
        
    //v2.0.2.0 begin
    //$flattened_cart_item = $_SESSION['flattened_cart_item_get_product_session_info'];
    //error_log( print_r(  'before vtprd_get_transient_cart_data  0003 ', true ) );
    $flattened_cart_item = vtprd_get_transient_cart_data ( 'flattened_cart_item_get_product_session_info', $cart_id);
    vtprd_del_transient_cart_data_by_data_type ('flattened_cart_item_get_product_session_info', $cart_id);
    //v2.0.2.0 end         
    $vtprd_cart_item = unserialize($flattened_cart_item);
    
   
    //v2.0.2.0 begin
    //$flattened_ruleset = $_SESSION['flattened_ruleset_get_product_session_info'];
    //error_log( print_r(  'before vtprd_get_transient_cart_data  0004 ', true ) );
    $flattened_ruleset = vtprd_get_transient_cart_data ( 'flattened_ruleset_get_product_session_info', $cart_id);
    vtprd_del_transient_cart_data_by_data_type ('flattened_ruleset_get_product_session_info', $cart_id);
    //v2.0.2.0 end     
    $vtprd_rules_set = unserialize($flattened_ruleset);
    
    $vtprd_info['current_processing_request'] = $save_current_processing_request; //v2.0.0 F Solution              
    //v2.0.0 j Solution end 
    
    //clean up the table
    if ($temp_random_cart_id_set) {
        vtprd_unset_random_unique_cart_id();
    }  
                 
    return;
  }
  

  /* ************************************************
  **   Apply Rules to single product + store as session info
  *************************************************** */
	function vtprd_apply_rules_to_single_product($product_id, $price=null){    
 
    global $post, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule;
    
    //v2.0.2.0 begin
    if ( isset($_SESSION['vtprd_unique_cart_id']) ) { 
       $vtprd_saved_unique_cart_id = $_SESSION['vtprd_unique_cart_id'];
    }  else {
       $vtprd_saved_unique_cart_id = false;
    } 
    

      //error_log( print_r(  'vtprd_unique_cart_id 002 = ' .$_SESSION['vtprd_unique_cart_id'], true ) ); 
    
    //create temp cart_id for CATALOG product processing  
    //random number, to make sure this is unique across a single DB with multiple simultaneous store customers
    // resets the unique session 
    //error_log( print_r(  'set random cart ID 004', true ) );
    vtprd_set_random_unique_cart_id($product_id);
    
    //v2.0.2.0 end

    vtprd_set_selected_timezone();
    vtprd_load_vtprd_cart_for_single_product_price($product_id, $price);
 
    //v2.0.0.9  begin - 'product_session_info' needed for Addons and Measurement Price Calculator plugins
    //v2.0.0 begin K solution
    if ( ($vtprd_info['ruleset_has_a_display_rule'] == 'yes') ||
         (class_exists('WC_Product_Addons')) ||
         (class_exists('WC_Measurement_Price_Calculator')) ) {    
      $vtprd_info['current_processing_request'] = 'display';
      $vtprd_apply_rules = new VTPRD_Apply_Rules;
    }
    //v2.0.0 end K solution
    //v2.0.0.9 end

    //also moves the data to $vtprd_info
    vtprd_move_vtprd_single_product_to_session($product_id);
    //return formatted price; if discounted, store price, orig price and you_save in session id
    //  if no discount, formatted DB price returned, no session variable stored
        
    $vtprd_cart = new VTPRD_Cart; //v1.1.8.0  need to clear this after being used here!!!

    //v2.0.2.0 begin
    
    //delete any transient rows created for this Product's ***CATALOG*** processing
    vtprd_del_transient_cart_data_by_cart_id();
    
    //restore original CART unique ID 
    if ($vtprd_saved_unique_cart_id) { 
       //overwrite value used for CATALOG processing, returning to CART value
       $_SESSION['vtprd_unique_cart_id'] = $vtprd_saved_unique_cart_id;

  //error_log( print_r(  'vtprd_unique_cart_id 003 = ' .$_SESSION['vtprd_unique_cart_id'], true ) ); 
    }  else {
       //remove value used for CATALOG processing
      vtprd_unset_random_unique_cart_id();
    }   
    //v2.0.2.0 end
       
    //price result stored in $vtprd_info['product_session_info'] 
    return; 
      
  }
  
  //*********************************
  //NEW GET PRICE FUNCTION
  //FROM 'woocommerce_get_price' => Central behind the scenes pricing  
  //*********************************
  function vtprd_maybe_get_price_single_product($product_id, $price=null){   
    global $post, $vtprd_info;
   
    //store product-specific session info
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }  

    //if already in the session variable... => this routine can be called multiple times in displaying a single catalog price.  check first if already done.
     
//      echo 'IN THE ROUTINE, $product_id= ' .$product_id.'<br>' ;
//            echo 'SESSION data <pre>'.print_r($_SESSION, true).'</pre>' ; 
      
    if(isset($_SESSION['vtprd_product_session_info_'.$product_id])) {
       $vtprd_info['product_session_info'] = $_SESSION['vtprd_product_session_info_'.$product_id];   
      //will be a problem in Ajax...
      $current_time_in_seconds = time();          
      $user_role = vtprd_get_current_user_role();
      
      //*****************************
      //v1.0.8.4 timestamp  begin
      $vtprd_ruleset_timestamp = get_option( 'vtprd_ruleset_timestamp' );  
      if (!$vtprd_ruleset_timestamp) {
        $vtprd_ruleset_timestamp = 0;
      }     
      //v1.0.8.4 timestamp  end
      //*****************************      
            
      if ( ( ($current_time_in_seconds - $vtprd_info['product_session_info']['session_timestamp_in_seconds']) > '600' ) ||    //session data older than 10 minutes //v2.0.0 
           (  $user_role != $vtprd_info['product_session_info']['user_role']) ||                                      //user role CHANGED via user login
           (  $vtprd_ruleset_timestamp > $vtprd_info['product_session_info']['session_timestamp_in_seconds'] ) ) {   //v1.0.8.4 timestamp - GET *more recent* ADMIN updates to ruleset NOW                  
        vtprd_apply_rules_to_single_product($product_id, $price);
        //reset stored role to current
        $vtprd_info['product_session_info']['user_role'] = $user_role;        
      }        
    } else { 
       //First time obtaining the info, also moves the data to $vtprd_info       
      vtprd_apply_rules_to_single_product($product_id, $price);
      // vtprd_apply_rules_to_vargroup_or_single($product_id, $price);        
    } 

    //$vtprd_info['product_session_info'] is loaded by this time...
    return;
  }
    
  
  
  
   
  
  /* ************************************************
  **   Post-purchase discount logging
  *************************************************** */	
	function vtprd_save_discount_purchase_log($cart_parent_purchase_log_id) {   
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set;  

      if ($vtprd_cart->yousave_cart_total_amt == 0) {
        return;
      }

      //**************************
      //v1.1.1.3  begin
      //THIS FUNCTION is executed multiple times
      //  purchase_log is stored the 1st time through, then we're done with this!!
      
      //if there's a discount history, let's find it...
      $vtprd_purchase_log = $wpdb->get_row( "SELECT * FROM `" . VTPRD_PURCHASE_LOG . "` WHERE `cart_parent_purchase_log_id`='" . $cart_parent_purchase_log_id . "' LIMIT 1", ARRAY_A );      	
      	    
      //if purchase log already there, the inserts below have already taken place!!  Exit, stage left
      if ($vtprd_purchase_log) { 
        return;
      }                                                                                                                          
      //v1.1.1.3  end
      //**************************

      //Create PURCHASE LOG row - 1 per cart
      $purchaser_ip_address = $vtprd_info['purchaser_ip_address']; 
      $next_id = '';             //v1.0.8.0 //supply null value for use with autoincrement table key
      $date = date("Y-m-d");     //v1.0.8.0 
 
      $ruleset_object = serialize($vtprd_rules_set); 
      $cart_object    = serialize($vtprd_cart);
      
      $wpdb->query("INSERT INTO `".VTPRD_PURCHASE_LOG."` (`id`,`cart_parent_purchase_log_id`,`purchaser_name`,`purchaser_ip_address`,`purchase_date`,`cart_total_discount_currency`,`ruleset_object`,`cart_object`) 
        VALUES ('{$next_id}','{$cart_parent_purchase_log_id}','{$vtprd_cart->billto_name}','{$purchaser_ip_address}','{$date}','{$vtprd_cart->yousave_cart_total_amt}','{$ruleset_object}','{$cart_object}' );");

      $purchase_log_row_id = $wpdb->get_var("SELECT LAST_INSERT_ID() AS `id` FROM `".VTPRD_PURCHASE_LOG."` LIMIT 1");

      foreach($vtprd_cart->cart_items as $key => $cart_item) {  
        if ($cart_item->yousave_total_amt > 0 ) { 
          //Create PURCHASE LOG PRODUCT row - 1 per product
          $wpdb->query("INSERT INTO `".VTPRD_PURCHASE_LOG_PRODUCT."` (`id`,`purchase_log_row_id`,`product_id`,`product_title`,`cart_parent_purchase_log_id`,
                `product_orig_unit_price`,`product_total_discount_units`,`product_total_discount_currency`,`product_total_discount_percent`) 
            VALUES ('{$next_id}','{$purchase_log_row_id}','{$cart_item->product_id}','{$cart_item->product_name}','{$cart_parent_purchase_log_id}',
                '{$cart_item->db_unit_price}','{$cart_item->yousave_total_qty}','{$cart_item->yousave_total_amt}','{$cart_item->yousave_total_pct}' );");
      
          $purchase_log_product_row_id = $wpdb->get_var("SELECT LAST_INSERT_ID() AS `id` FROM `".VTPRD_PURCHASE_LOG_PRODUCT."` LIMIT 1"); 
          foreach($cart_item->yousave_by_rule_info as $key => $yousave_by_rule) {
            $ruleset_occurrence = $yousave_by_rule['ruleset_occurrence'] ;
            $rule_id = $vtprd_rules_set[$ruleset_occurrence]->post_id;
            $discount_applies_to_qty = $yousave_by_rule['discount_applies_to_qty'];
            $yousave_amt = $yousave_by_rule['yousave_amt'];
            $yousave_pct = $yousave_by_rule['yousave_pct'];        
            //Create PURCHASE LOG PRODUCT RULE row  -  1 per product/rule combo
            $wpdb->query("INSERT INTO `".VTPRD_PURCHASE_LOG_PRODUCT_RULE."` (`id`,`purchase_log_product_row_id`,`product_id`,`rule_id`,`cart_parent_purchase_log_id`,
                  `product_rule_discount_units`,`product_rule_discount_dollars`,`product_rule_discount_percent`) 
              VALUES ('{$next_id}','{$purchase_log_product_row_id}','{$cart_item->product_id}','{$rule_id}','{$cart_parent_purchase_log_id}',
                  '{$discount_applies_to_qty}','{$yousave_amt}','{$yousave_pct}' );");              
          }    
        }
      }
      
           
  }
    
     
  /* ************************************************
  **   Recompute Discount for VARIATION Display rule AJAX  
  *************************************************** */
  function vtprd_recompute_discount_price($variation_id, $price){
      global $vtprd_info;  
      
      $yousave_amt = 0;
      $sizeof_pricing_array = sizeof($vtprd_info['product_session_info']['pricing_by_rule_array']);
      for($y=0; $y < $sizeof_pricing_array; $y++) {
        
        $apply_this = 'yes';
        
        $pricing_rule_applies_to_variations_array = $vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_applies_to_variations_array'];
        
        if (sizeof($pricing_rule_applies_to_variations_array) > 0) {
           if (in_array($variation_id, $pricing_rule_applies_to_variations_array )) {
             $apply_this = 'yes';
           } else {
             $apply_this = 'no';  //this rule is variation-specific, and the passed id is not!! in the group - skip
           }
        }
        
        if ($apply_this == 'yes') {
          if ($vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_currency_discount'] > 0) {
            $yousave_amt +=  $vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_currency_discount'];
          } else {
            $PercentValue =  $vtprd_info['product_session_info']['pricing_by_rule_array'][$y]['pricing_rule_percent_discount'];
            $yousave_amt +=  vtprd_compute_percent_discount($PercentValue, $price);
          }
        }
        
      }  //end for loop
      
      $vtprd_info['product_session_info']['product_discount_price'] = $price - $yousave_amt;
      //                                  ************************
       
     return;
  }
  
   
  /* ************************************************
  **   Compute percent discount for VARIATION realtime
  *************************************************** */
  function vtprd_compute_percent_discount($PercentValue, $price){
    //from apply-rules.php   function vtprd_compute_each_discount
      $percent_off = $PercentValue / 100;          
      
   // $discount_2decimals = bcmul($price , $percent_off , 2);
      $discount_2decimals = round($price * $percent_off , 2); //v1.0.7.6
      
      //compute rounding
      $temp_discount = $price * $percent_off;
          
    //$rounding = $temp_discount - $discount_2decimals;
      $rounding = round($temp_discount - $discount_2decimals, 4);   //v1.0.7.4  PHP floating point error fix - limit to 4 places right of the decimal!!
           
      if ($rounding > 0.005) {
        $discount = $discount_2decimals + .01;
      }  else {
        $discount = $discount_2decimals;
      }
           
     return $discount;
  }


   //********************************
   //v1.1.6 new function
   //v2.0.0.5 recoded
   // now handles BOTH Coupon discount auto coupon, and rule activating coupon
   //********************************   
   function vtprd_woo_maybe_create_coupon_types($coupon_name,$auto=null) { 
      global $wpdb, $vtprd_info, $vtprd_setup_options; 
   
   //error_log( print_r(  'BEGIN vtprd_woo_maybe_create_coupon_types, $coupon_name= ' .$coupon_name. ' $auto= ' .$auto, true ) ); 

      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" . $coupon_name. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );     	
      if (!$coupon_id) {
        //$coupon_code = 'UNIQUECODE'; // Code
        if ($auto) {
          $content = 'please do not delete - single Automatic Coupon';
        } else {
          $content = 'delete only if no longer in use - rule discount activating Coupon';
        }
        $amount = '0'; // Amount
        $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
        $coupon = array(
        'post_title' => $coupon_name, 
        'post_content' => 'Pricing Deal Plugin Inserted Coupon, ' .$content,
        'post_excerpt' => 'Pricing Deal Plugin Inserted Coupon, ' .$content,        
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
        );
        $new_coupon_id = wp_insert_post( $coupon );
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', '' );
        update_post_meta( $new_coupon_id, 'expiry_date', '' );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
      }
   
    /*  FUTURE code for free shipping with discount...
      $deal_free_shipping_title = __('Free Shipping Deal', 'vtprd'); 

      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" .$deal_free_shipping_title. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );
      if (!$coupon_id) {
        //$coupon_code = 'UNIQUECODE'; // Code
        
        $amount = '0'; // Amount
        $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
        $coupon = array(
        'post_title' => $deal_free_shipping_title, //$coupon_code,
        'post_content' => 'Pricing Deal Plugin Inserted Coupon, please do not delete',
        'post_excerpt' => 'Pricing Deal Plugin Inserted Coupon, please do not delete',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
        );
        $new_coupon_id = wp_insert_post( $coupon );
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', '' );
        update_post_meta( $new_coupon_id, 'expiry_date', '' );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'yes' ); //YES!!!
      }
      */    
      
     return;
   } 

  
   //********************************
   //v2.0.0.5  new function
   //********************************
   function vtprd_woo_maybe_delete_all_single_auto_coupons() { 
      
   //error_log( print_r(  'BEGIN vtprd_woo_maybe_delete_all_single_auto_coupons= ', true ) ); 
   
      global $wpdb;
      //$sql = "SELECT `post_title`  FROM `" . $wpdb->prefix . "posts`   WHERE   `post_type` = 'shop_coupon' AND `post_status` = 'publish' AND  `post_content` LIKE '%single Automatic Coupon%' "; 
            $sql = "SELECT post_title  FROM " . $wpdb->prefix . "posts   WHERE   post_type = 'shop_coupon' AND post_status = 'publish' AND  post_content LIKE '%single Automatic Coupon%' "; 
            $coupons_array = $wpdb->get_results($sql,ARRAY_A) ;

      foreach ($coupons_array as $coupon) {
        $coupon_name = $coupon['post_title'];
      
   //error_log( print_r(  'BEGIN vtprd_woo_maybe_delete_all_single_auto_coupons, delete this= ' .$coupon_name , true ) );         
        vtprd_woo_maybe_delete_coupon_types($coupon_name);
      }    
                 
      return;
   } 
     
   //********************************
   //v1.1.6 new function
   //v2.0.0.5 recoded
   // now handles BOTH Coupon discount auto coupon, and rule activating coupon   
   //********************************
   function vtprd_woo_maybe_delete_coupon_types($coupon_name) { //v2.0.0.5  $coupon added allow any coupon to be processed
      global $wpdb, $vtprd_info, $vtprd_setup_options;   //v2.0.0.5 $vtprd_setup_options 
 //error_log( print_r(  'BEGIN  vtprd_woo_maybe_delete_coupon_types', true ) );

      $coupon_id 	= $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title ='" . $coupon_name. "'  AND post_type = 'shop_coupon' AND post_status = 'publish'  LIMIT 1" );     	
      if (!$coupon_id) {     
        return;
      }
      //$coupon_code = 'UNIQUECODE'; // Code
      
      $amount = '0'; // Amount
      $discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

      // Add meta
      delete_post_meta( $coupon_id, 'discount_type', $discount_type );
      delete_post_meta( $coupon_id, 'coupon_amount', $amount );
      delete_post_meta( $coupon_id, 'individual_use', 'no' );
      delete_post_meta( $coupon_id, 'product_ids', '' );
      delete_post_meta( $coupon_id, 'exclude_product_ids', '' );
      delete_post_meta( $coupon_id, 'usage_limit', '' );
      delete_post_meta( $coupon_id, 'expiry_date', '' );
      delete_post_meta( $coupon_id, 'apply_before_tax', 'yes' );
      delete_post_meta( $coupon_id, 'free_shipping', 'no' );
      
      wp_delete_post($coupon_id); 

     return;
   }
  
  function vtprd_woo_ensure_coupons_are_allowed() {     

    if ( ($_REQUEST['page'] == 'woocommerce_settings' ) ) {
      $coupons_enabled = get_option( 'woocommerce_enable_coupons' );
      if ($coupons_enabled == 'no') {
          $message  =  '<strong>' . __('Message from Pricing Deals Plugin => WooCommerce setting "Enable the use of coupons" checkbox must be checked, as Pricing Deal cart discounts are applied using the coupon system.' , 'vtprd') . '</strong>' ;
          $message .=  '<br><br>';
          $message .=  '<strong>' . __('"Enable the use of coupons" reset to Checked.' , 'vtprd') . '</strong>' ;
          $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
          //v2.0.0.2 begin
          //add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
          add_action( 'admin_notices', function() use ( $admin_notices ) {echo $admin_notices;}, 10 );
          //v2.0.0.2 end
          update_option( 'woocommerce_enable_coupons','yes');    
      }
    }
  }

  
  function vtprd_checkDateTime($date) {
    if (date('Y-m-d', strtotime($date)) == $date) {
        return true;
    } else {
        return false;
    }
  }
      
  /* ************************************************
  **   Amount comes back Formatted!
  *************************************************** */  
  function vtprd_get_Woo_cartSubtotal() {

      global $woocommerce;
      $amt = $woocommerce->cart->get_cart_subtotal();
      
      return $amt;
  }


  /* ************************************************
  **   Change the default title in the rule custom post type
  *************************************************** */
  function vtprd_change_default_title( $title ){
     $screen = get_current_screen();
     if  ( 'vtprd-rule' == $screen->post_type ) {
          $title = 'Enter Rule Title';
     }
     return $title;
  }
  add_filter( 'enter_title_here', 'vtprd_change_default_title' ); 


  //***** v1.0.4 begin
  /* ************************************************
  **  if BCMATH not installed with PHP by host, this will replace it.
  *************************************************** */
 /*  v1.0.7.6 removed!
  if (!function_exists('bcmul')) {
    function bcmul($_ro, $_lo, $_scale=0) {
      return round($_ro*$_lo, $_scale);
    }
  }
  if (!function_exists('bcdiv')) {
    function bcdiv($_ro, $_lo, $_scale=0) {
      return round($_ro/$_lo, $_scale);
    }
  }
  */
  //***** v1.0.4 end 
  
  //v1.0.7 change
  function vtprd_debug_options(){     

    global $vtprd_setup_options;
    if ( ( isset( $vtprd_setup_options['debugging_mode_on'] )) &&
         ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ) ) {  
      error_reporting(E_ALL);  
    }  else {
      error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);    //only allow FATAL error types  //v1.0.7.7       
    } 
    
    //v1.0.7.8 begin
    //refresh $woocommerce addressablility
    $current_version =  WOOCOMMERCE_VERSION;
    if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
      $do_nothing_for_older_version = '';
    } else {
      //from woocommerce.php
      global $woocommerce;
      $woocommerce = WC();
    }
    //v1.0.7.8 end  
   
  }
  
  //****************************************
  //v1.0.7.4 new function
  // Format $amt with VAT suffix, as needed 
  //****************************************
  function vtprd_format_amt_and_adjust_for_taxes($amt, $k=null){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
//error_log( print_r(  'vtprd_format_amt_and_adjust_for_taxes, Begin price= ' .$amt, true ) ); 
    //at a minimum, format $amt
    if ( ( get_option( 'woocommerce_calc_taxes' ) == 'no' ) ||
         ( vtprd_maybe_customer_tax_exempt() ) ) {      //v1.0.7.9      
       return vtprd_format_money_element($amt);
    }
    
    $woocommerce_prices_include_tax   =   get_option('woocommerce_prices_include_tax');
    $woocommerce_tax_display_cart     =   get_option('woocommerce_tax_display_cart');

    if ($woocommerce_prices_include_tax == 'yes') {
        switch(true) {
          case ($woocommerce_tax_display_cart   == 'incl') :
              $amt = vtprd_format_money_element($amt); 
            break; 
          case ($woocommerce_tax_display_cart   == 'excl') :
              $product_id = $vtprd_cart->cart_items[$k]->product_id;
              $amt = vtprd_get_price_excluding_tax($product_id, $amt);
              $amt = vtprd_format_money_element($amt);
            break;
        }           
    } else {  // price does NOT include tax
        switch(true) {
          case ($woocommerce_tax_display_cart   == 'excl') :         
              $amt = vtprd_format_money_element($amt); 
            break; 
          case ($woocommerce_tax_display_cart   == 'incl') :
  //error_log( print_r(  'get_tax_class 005', true ) );          
             $qty = 1;           
             $_tax  = new WC_Tax();
             $product = wc_get_product( $vtprd_cart->cart_items[$k]->product_id ); //v1.1.7 replace get_product with wc_get_product
             $tax_rates  = $_tax->get_rates( $product->get_tax_class() );
    			 	 $taxes      = $_tax->calc_tax( $amt  * $qty, $tax_rates, false );
    				 $tax_amount = $_tax->get_tax_total( $taxes );
    				 $amt        = round( $amt  * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) ); 
             $amt        = vtprd_format_money_element($amt); 
            break;
        }    
    }

    $amt .= vtprd_maybe_load_incl_excl_vat_lit(); 
    return $amt;
  }
  
  
  //****************************************
  //v1.0.7.4 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************
  function vtprd_get_price_including_tax($product_id, $discount_price){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
//error_log( print_r(  'vtprd_get_price_including_tax, Begin price= ' .$discount_price .' Product= ' .$product_id , true ) );
    //changed $this->  to  $product->
    //use $discount_price as basi

    $qty = 1; 
    $product = wc_get_product( $product_id );  //v1.1.7 replace get_product with wc_get_product
    
    //v1.1.8.3 begin
    if  ( (!$product) ||
          (!is_object($product)) ) {

       if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){  
          error_log( print_r(  'vtprd_get_price_including_tax - product object NOT FOUND, $product_id= ' .$product_id, true ) );  
       }
       return $discount_price;
    }     
    //v1.1.8.3 end
        
    $price = $discount_price;
    
		$_tax  = new WC_Tax();

		if ( $product->is_taxable() ) {

			if ( get_option('woocommerce_prices_include_tax') === 'no' ) {
  //error_log( print_r(  'get_tax_class 006', true ) );
				$tax_rates  = $_tax->get_rates( $product->get_tax_class() );
				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = $_tax->get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
			} else {
  //error_log( print_r(  'get_tax_class 007', true ) );
				$tax_rates      = $_tax->get_rates( $product->get_tax_class() );
				$base_tax_rates = $_tax->get_shop_base_rate( $product->get_tax_class() ); //v1.1.7.2  $product->tax_class 

			//	if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {   //v1.0.7.5
        if ( vtprd_maybe_customer_tax_exempt() )  {      //v1.0.7.9
					$base_taxes 		= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount	= array_sum( $base_taxes );
					$price      		= round( $price * $qty - $base_tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );

				} elseif ( $tax_rates !== $base_tax_rates ) {

					$base_taxes			= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$modded_taxes		= $_tax->calc_tax( ( $price * $qty ) - array_sum( $base_taxes ), $tax_rates, false );
					$price      		= round( ( $price * $qty ) - array_sum( $base_taxes ) + array_sum( $modded_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );

				} else {

					$price = $price * $qty;

				}

			}

		} else {
			$price = $price * $qty;     
		}
    return $price;
  }
 
  //****************************************
  //v1.0.7.4 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************

  function vtprd_get_price_excluding_tax($product_id, $discount_price){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 

    //changed $this->  to  $product->
    //use $discount_price as basis
    
    $qty = 1;
    $product = wc_get_product( $product_id ); //v1.1.7 replace get_product with wc_get_product
     
    //v1.1.8.3 begin
    if  ( (!$product) ||
          (!is_object($product)) ) {
       
       if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){  
          error_log( print_r(  'vtprd_get_price_excluding_tax - product object NOT FOUND, $product_id= ' .$product_id, true ) );  
       }
       return $discount_price;
    }     
    //v1.1.8.3 end
       
    $price = $discount_price;
    
		if ( $product->is_taxable() && get_option('woocommerce_prices_include_tax') === 'yes' ) {
  //error_log( print_r(  'get_tax_class 010', true ) );
			$_tax       = new WC_Tax();
      $tax_rates  = $_tax->get_shop_base_rate( $product->get_tax_class() ); //v1.1.7.2  $product->tax_class 
			$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, true );
			$price      = $_tax->round( $price * $qty - array_sum( $taxes ) );
		} else {
			$price = $price * $qty;     
		}
 //error_log( print_r(  'get_tax_class 010 PRICE= ' .$price, true ) );       
    return $price;
  }

 
  //****************************************
  //v1.0.9.3 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************
  function vtprd_get_price_including_tax_forced ($product_id, $price, $product){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 
//error_log( print_r(  'vtprd_get_price_including_tax_forced, Begin price= ' .$price .' Product= ' .$product_id , true ) );
    //changed $this->  to  $product->
    //use $discount_price as basi

    $qty = 1; 
   
    
		$_tax  = new WC_Tax();

		if ( $product->is_taxable() ) {

			if ( get_option('woocommerce_prices_include_tax') === 'no' ) {
  //error_log( print_r(  'get_tax_class 008', true ) );
				$tax_rates  = $_tax->get_rates( $product->get_tax_class() );
   //error_log( print_r(  'get_tax_class 008 001', true ) );       
				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );
    //error_log( print_r(  'get_tax_class 008 002', true ) );       
				$tax_amount = $_tax->get_tax_total( $taxes );
     //error_log( print_r(  'get_tax_class 008 003', true ) );      
				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
     //error_log( print_r(  'get_tax_class 008 004', true ) );
			} else {
  //error_log( print_r(  'get_tax_class 009', true ) );
				$tax_rates      = $_tax->get_rates( $product->get_tax_class() );
				$base_tax_rates = $_tax->get_shop_base_rate( $product->get_tax_class() ); //v1.1.7.2

			//	if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {   //v1.0.7.5
        if ( vtprd_maybe_customer_tax_exempt() )  {      //v1.0.7.9
					$base_taxes 		= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount	= array_sum( $base_taxes );
					$price      		= round( $price * $qty - $base_tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
				} elseif ( $tax_rates !== $base_tax_rates ) {

					$base_taxes			= $_tax->calc_tax( $price * $qty, $base_tax_rates, true );
					$modded_taxes		= $_tax->calc_tax( ( $price * $qty ) - array_sum( $base_taxes ), $tax_rates, false );
					$price      		= round( ( $price * $qty ) - array_sum( $base_taxes ) + array_sum( $modded_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );
				} else {

   				$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, false );      
  				$tax_amount = $_tax->get_tax_total( $taxes );      
  				$price      = round( $price * $qty + $tax_amount, absint( get_option( 'woocommerce_price_num_decimals' ) ) );
				}

			}

		} else {
			$price = $price * $qty;  
		}
  //error_log( print_r(  'get_tax_class RETURN PRICE= ' .$price, true ) );  
    return $price;
  }
 
  //****************************************
  //v1.0.9.3 new function
  //from woocommerce/includes/abstracts/abstract-wc-product.php
  //****************************************

  function vtprd_get_price_excluding_tax_forced ($product_id, $price, $product){ 
    global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info; 

    //changed $this->  to  $product->
    //use $discount_price as basis

    
    $qty = 1;
    
		if ( $product->is_taxable() ) {
    //if ( $product->is_taxable() && get_option('woocommerce_prices_include_tax') === 'yes' ) {
 //error_log( print_r(  'get_tax_class 011', true ) );
			$_tax       = new WC_Tax();
		//v1.1.7 begin
    //	$tax_rates  = $_tax->get_shop_base_rate( $product->tax_class );
      $tax_rates  = $_tax->get_shop_base_rate( $product->get_tax_class() );
		//v1.1.7 end      
			$taxes      = $_tax->calc_tax( $price * $qty, $tax_rates, true );
			$price      = $_tax->round( $price * $qty - array_sum( $taxes ) );
		} 
 //error_log( print_r(  'get_tax_class 011 end', true ) );        
    return $price;
  }



  //****************************************
  //v1.0.7.4 new function
  //****************************************
  function vtprd_load_cart_total_incl_excl(){ 
	  global $vtprd_cart, $woocommerce;
   
    //v1.0.8.9a  initialize the return base amt
    $return_amt = $vtprd_cart->yousave_cart_total_amt; //v1.0.8.9a
    
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
                 //v1.0.8.9a begin  re-fix!!!
                 //$excl_vat_lit .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';  //v1.0.7.5
                 $return_amt =  $vtprd_cart->yousave_cart_total_amt_excl_tax; //The return value is only accessed in a very few executions if this function!
                 //v1.0.8.9a end
              }   
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   != 'excl') { //v1.0.9.3
              //if (get_option('woocommerce_tax_display_cart')   == 'incl') {              
                 $vtprd_cart->yousave_cart_total_amt =  $vtprd_cart->yousave_cart_total_amt_incl_tax; 
                 $return_amt = $vtprd_cart->yousave_cart_total_amt;  //v1.0.8.9a  The return value is only accessed in a very few executions if this function!
              }           
             break;
       }          
    }    

 
    //v1.0.8.9a  The return_amt is ONLY accessed when reporting on CART DISCOUNT (sub)TOTAL in the detail area
    return $return_amt; //v1.0.8.9a
  }

  
  //****************************************
  //v1.0.7.9 new function
  //****************************************
  function vtprd_maybe_customer_tax_exempt(){ 
		global $vtprd_cart, $woocommerce, $vtprd_info;

    //save is_tax_exempt status
    //handles addressability for emails!
    //defaults to false.
    if ( (isset($vtprd_cart->customer_is_tax_exempt)) &&  //v1.0.8.0
         ($vtprd_cart->customer_is_tax_exempt) ) {           
      return true;
    }
    if (!is_object($woocommerce->customer)) {   
      return false; 
    }
    //v1.1.7.2 begin
    if ( ( ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) && (! empty( $woocommerce->customer ) )  &&  ( $woocommerce->customer->get_is_vat_exempt() ) ) || 
         ( ( version_compare( WC_VERSION, '3.0.0', '<'  ) ) && (! empty( $woocommerce->customer ) )  &&  ( $woocommerce->customer->is_vat_exempt() ) ) ) {
    //if ( ! empty( $woocommerce->customer ) && $woocommerce->customer->is_vat_exempt() ) {  
    //v1.1.7.2 end       
   
      $vtprd_cart->customer_is_tax_exempt = true;      
      return true;
    } 
    
    //v1.0.9.0 begin
    //pick up setting from NEW user-level wp-admin screen field!!
    if ($vtprd_info['user_is_tax_exempt'])  {
       return true;
    } 
    //v1.0.9.0 end
       
    return false;
  }  
   
  
  //****************************************
  //v1.0.7.4 new function
  //****************************************
  function vtprd_maybe_load_incl_excl_vat_lit(){ 
		global $vtprd_cart, $woocommerce;
    //inc_tax_or_vat()

    $excl_vat_lit = '';

     if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       switch (get_option('woocommerce_prices_include_tax')) {
          case 'yes':
              if (get_option('woocommerce_tax_display_cart')   == 'excl') {
                 $excl_vat_lit .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';     //v1.0.7.5
              } 
             break;         
          case 'no':
              if (get_option('woocommerce_tax_display_cart')   == 'incl') {
                 $excl_vat_lit .= ' <small>' . $woocommerce->countries->inc_tax_or_vat() . '</small>';     //v1.0.7.5
              }           
             break;
       }          
    } 


    return $excl_vat_lit;
  } 
   
  //****************************************
  //v1.0.7.4 new function
  //  Testing Note:  Compare how Deal discount is applied vs Regular coupon discount of same amount
  //    example: 10% cart discount vs 10% coupon, with a variety of tax switch settings...
  //****************************************
  function vtprd_coupon_apply_before_tax(){ 

    $apply_before_tax = 'yes';  //supply DEFAULT
    if ( get_option( 'woocommerce_calc_taxes' )  == 'yes' ) {
       if (get_option('woocommerce_prices_include_tax') == 'yes') {
         $do_nothing = true; //v2.0.0.2 
          if (get_option('woocommerce_tax_display_cart')   == 'excl') {
             $apply_before_tax = 'no'; 
          } 
       } else {
          if (get_option('woocommerce_tax_display_cart')   == 'incl') {
            $apply_before_tax = 'no'; 
          }               
       }
    } 
 
    return $apply_before_tax;
  }
        
    //****************************************  
    //v1.0.9.0  begin
    // new functions
    //****************************************
    add_action( 'show_user_profile', 'vtprd_my_show_extra_profile_fields' );
    add_action( 'edit_user_profile', 'vtprd_my_show_extra_profile_fields' );
    
    function vtprd_my_show_extra_profile_fields( $user ) { 
       		
        if ( current_user_can( 'edit_user', $user->ID ) ) {
    			//v1.1.0.7 begin
          //global $current_user; //v1.1.0.8 unnecessary
          if (get_user_meta( $user->ID, 'vtprd_user_is_tax_exempt', true ) == 'yes') {  //v1.1.0.8 changed to $user->ID
            $checked =  ' checked="checked" '; 
          } else {
            $checked =  '';
          }
          //v1.1.0.7 end
          ?>
    				<table class="form-table">
    					<tbody>
    						<tr>
    							<th><label for="vtprd_user_is_tax_exempt"><?php _e( 'Pricing Deals User Tax Free', 'vtprd' ); ?></label></th>
    							<td>
    								<?php //if ( empty( $user->woocommerce_api_consumer_key ) ) : ?>
    									<input name="vtprd_user_is_tax_exempt" type="checkbox" <?php echo $checked; //v1.1.0.7  ?> id="vtprd_user_is_tax_exempt" value="yes" />
    									<span class="description"><?php _e( 'User Transactions are Tax-Free', 'vtprd' ); ?></span>
    							</td>
    						</tr>
    					</tbody>
    				</table>
    			<?php
    		}
        return;
    }
      
      add_action( 'personal_options_update',  'vtprd_my_save_extra_profile_fields' );
      add_action( 'edit_user_profile_update', 'vtprd_my_save_extra_profile_fields' );
      
      function vtprd_my_save_extra_profile_fields( $user_id ) {
      
      	if ( !current_user_can( 'edit_user', $user_id ) )
      		return false;
      
      	update_usermeta( $user_id, 'vtprd_user_is_tax_exempt', $_REQUEST['vtprd_user_is_tax_exempt'] );
      }
      //v1.0.9.0  end
          
    
    //v1.0.7.9 new function
    //from::includes/api/class-wc-api-products.php
  	function vtprd_get_attributes( $product ) {

		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {

			// variation attributes
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {

				// taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
				$attributes[] = array(
					'name'   => ucwords( str_replace( 'attribute_', '', str_replace( 'pa_', '', $attribute_name ) ) ),
					'option' => $attribute,
				);
			}

		} else {

			foreach ( $product->get_attributes() as $attribute ) {

				// taxonomy-based attributes are comma-separated, others are pipe (|) separated
				if ( $attribute['is_taxonomy'] )
					$options = explode( ',', $product->get_attribute( $attribute['name'] ) );
				else
					$options = explode( '|', $product->get_attribute( $attribute['name'] ) );

				$attributes[] = array(
					'name'      => ucwords( str_replace( 'pa_', '', $attribute['name'] ) ),
					'position'  => $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => array_map( 'trim', $options ),
				);
			}
		}

		return $attributes;
	}
  //v1.0.7.9  end
  
  //v1.0.9.3 begin
  //Need to use this deferred admin structure for ACTIVATION messages
  /*
        $notices= get_option('vtprd_deferred_admin_notices', array());
        $notices[]= $admin_notices;
        update_option('vtprd_deferred_admin_notices', $notices);
  */
  
  /*
  
  //v1.1.0.5 new function ==>> template tag TO SHOW THE TOTAL DISCOUNT!!
  
  
  
  HOW TO USE:
  

  < ?php  //<<==remove space between '<' and '?'   -wrapped in php tags, *******ONLY AS NEEDED -  
  
  if ( vtprd_the_discount() ) { echo vtprd_the_discount(); }
  
 // remove space between '?' and '>' in next line ==>>
   ? > 
   
   
  */

    function vtprd_the_discount() {
           
      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }   
      global $vtprd_cart; 
      if (empty($vtprd_cart)) {      
  
        //v2.0.2.0 begin
        /*
        if (isset($_SESSION['data_chain'])) {
          $data_chain      = unserialize($_SESSION['data_chain']);
          if (isset($data_chain[1])) {  
            $vtprd_cart      = $data_chain[1];
          }        
        }
        */            
        //error_log( print_r(  'vtprd_get_data_chain 001', true ) );
        //error_log( print_r(  'before vtprd_get_transient_cart_data  0005 ', true ) );
        $data_chain = vtprd_get_transient_cart_data ( 'data_chain' );
        if ($data_chain) {
          $data_chain      = unserialize($data_chain);
          if (isset($data_chain[1])) {  
            $vtprd_cart      = $data_chain[1];
          }        
        } 
        //v2.0.2.0 end
            
      }
  
      if ( (isset($vtprd_cart->yousave_cart_total_amt)) &&
           ($vtprd_cart->yousave_cart_total_amt > 0) ) {
        $the_discount = wc_price($vtprd_cart->yousave_cart_total_amt); 
      } else {
        $the_discount = false;
      }  
         
    return $the_discount;  
  }
  //v1.1.0.5 end   

 
  /* ************************************************
  **   //v1.1.0.6 new function
  *     repair the ruleset if rapid updates have put things OUT OF SYNC 
  *     run after **every PRO rule screen update only**  
  *************************************************** */

  //v2.0.0 M solution - function reworked

  function vtprd_maybe_resync_rules_set() {
    global $wpdb, $post, $vtprd_info, $vtprd_rule; //v2.0.0 M solution - removed global $vtprd_rules_set !!!!!!!!!!

    $vtprd_rules_set = get_option('vtprd_rules_set');  
          
    if ( sizeof($vtprd_rules_set) <= 0) { 
      return;           
    } 

    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    
    $update_required = false; 
           
    //$update_required = false; 
 
    foreach ($vtprd_rules_set as $key => $rule )  { 
       
       if ( (isset($rule ->post_id)) &&
            (is_numeric($rule ->post_id)) ) {
          $post = get_post($rule ->post_id);
       } else {
          $post = false;
       } 

       if ($post) {              
          //v2.0.0 begin
          switch (TRUE) {
              case ($rule->rule_status == 'pending'):
                  //if pending, do nothing
                break;
              case (sizeof($rule->rule_error_message) > 0):
                  $rule->rule_status = 'pending';
                  $update_required = true;
                break;
              case ($rule->rule_status != $post->post_status):
                  $rule->rule_status = $post->post_status;
                  $update_required = true;
                break;                  
          }  
          $vtprd_rules_set[$key] = $rule;
       } else {
          unset( $vtprd_rules_set[$key]);
          $update_required = true;
       }
    }
    
    if ($update_required) {
      //reknit the array to get rid of any holes
      $vtprd_rules_set = array_values($vtprd_rules_set);       
      update_option( 'vtprd_rules_set', $vtprd_rules_set ); 
    }   

      //error_log( print_r(  '$vtprd_rules_set vtprd_maybe_resync_rules_set at bottom', true ) );
      //error_log( var_export($vtprd_rules_set, true ) ); 
  
    return;
 }      


  add_action('admin_notices', 'vtprd_admin_notices');
  function vtprd_admin_notices() {
    if ($notices= get_option('vtprd_deferred_admin_notices')) {
      foreach ($notices as $notice) {
        echo $notice;
      }
      delete_option('vtprd_deferred_admin_notices');
    }   
  } 
    
  add_action('admin_init', 'vtprd_check_for_deactivation_action');
  function vtprd_check_for_deactivation_action() {
    //moved here from vt-pricing-deals.php, so it can run at admin init time
    if( !is_plugin_active(VTPRD_PLUGIN_SLUG) ) { 
      return;
    }  
    
    global $wp_version, $vtprd_setup_options;
		$earliest_allowed_wp_version = 3.3;
    if( (version_compare(strval($earliest_allowed_wp_version), strval($wp_version), '>') == 1) ) {   //'==1' = 2nd value is lower  
        $message  =  '<strong>' . __('Looks like you\'re running an older version of WordPress, you need to be running at least WordPress 3.3 to use the Varktech Pricing Deals plugin.' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('Current Wordpress Version = ' , 'vtprd')  . $wp_version ;
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        //v2.0.0.2 begin
        //add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        add_action( 'admin_notices', function() use ( $admin_notices ) {echo $admin_notices;}, 10 );
        //v2.0.0.2 end
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
        
        //v2.0.0 begin - M solution - only need to do this once
        remove_action('admin_init', 'vtprd_check_for_deactivation_action');
        //v2.0.0 end - M solution
        
        return;
		}
   
            
   if (version_compare(PHP_VERSION, VTPRD_EARLIEST_ALLOWED_PHP_VERSION) < 0) {    //'<0' = 1st value is lower  
        $message  =  '<strong>' . __('Looks like you\'re running an older version of PHP.   - your PHP version = ' , 'vtprd') .PHP_VERSION. '</strong>' ;
        $message .=  '<br>' . __('You need to be running **at least PHP version 5** to use this plugin. Please contact your host and request an upgrade to PHP 5+ .  Once that has been installed, you can activate this plugin.' , 'vtprd');
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        //v2.0.0.2 begin
        //add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        add_action( 'admin_notices', function() use ( $admin_notices ) {echo $admin_notices;}, 10 );
        //v2.0.0.2 end
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
        
        //v2.0.0 begin - M solution - only need to do this once
        remove_action('admin_init', 'vtprd_check_for_deactivation_action');
        //v2.0.0 end - M solution
                
        return;      
      
		}

    
    if(defined('WOOCOMMERCE_VERSION') && (VTPRD_PARENT_PLUGIN_NAME == 'WooCommerce')) { 
      $new_version =      VTPRD_EARLIEST_ALLOWED_PARENT_VERSION;
      $current_version =  WOOCOMMERCE_VERSION;
      if( (version_compare(strval($new_version), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower 
        $message  =  '<strong>' . __('Looks like you\'re running an older version of WooCommerce. You need to be running at least ** WooCommerce 2.0 **, to use the Varktech Pricing Deals plugin' , 'vtprd') . '</strong>' ;
        $message .=  '<br>' . __('Your current WooCommerce version = ' , 'vtprd') .WOOCOMMERCE_VERSION;
        $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
        //v2.0.0.2 begin
        //add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
        add_action( 'admin_notices', function() use ( $admin_notices ) {echo $admin_notices;}, 10 );
        //v2.0.0.2 end
        if (get_option('vtprd_deferred_admin_notices')) {
          delete_option('vtprd_deferred_admin_notices');
        }        
        deactivate_plugins( VTPRD_PLUGIN_SLUG );
        
        //v2.0.0 begin - M solution - only need to do this once
        remove_action('admin_init', 'vtprd_check_for_deactivation_action');
        //v2.0.0 end - M solution
        
        return;         
  		}
    } 

      //error_log( print_r(  ' PARENT FUNCTIONS $vtprd_setup_options= ', true ) ); 
      //error_log( var_export($vtprd_setup_options, true ) ); 
      
      
      //**************************
      //v2.0.0.5 begin - rewrite
      //**************************
        if ( ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') ||
             ($vtprd_setup_options['discount_taken_where'] <= ' ') ) { //v1.0.9.3  doesn't apply if 'discountUnitPrice'
        //v1.0.7.4 begin  
          //****************************************
          //INSIST that coupons be enabled in woo, in order for this plugin to work!!
          //****************************************
          //always check if the manually created coupon codes are there - if not create them.
          $coupon_discount_coupon_name = $vtprd_setup_options['coupon_discount_coupon_name'];
          
          //only check if there IS a coupon name - otherwise, this is in process and will be handled in the settings page.
          if ($coupon_discount_coupon_name > ' ') {                
            
            $auto = true;          
            vtprd_woo_maybe_create_coupon_types($coupon_discount_coupon_name,$auto); 
            //v2.0.0.5 end
              
    //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types PARENT FUNCTIONS', true ) );                
            $coupons_enabled = get_option( 'woocommerce_enable_coupons' ) == 'no' ? false : true;
            if (!$coupons_enabled) {  
              $message  =  '<strong>' . __('In order for the "Pricing Deals" plugin to function successfully when the "Coupon Discount" setting is selected, the Woo Coupons Setting must be on, and it is currently off.' , 'vtprd') . '</strong>' ;
              $message .=  '<br><br>' . __('Woo Coupons Setting is now set to "on"'  , 'vtprd'); //v2.0.0.5
              $admin_notices = '<div id="message" class="error fade" style="background-color: #FFEBE8 !important;"><p>' . $message . ' </p></div>';
              //v2.0.0.2 begin
              //add_action( 'admin_notices', create_function( '', "echo '$admin_notices';" ) );
              add_action( 'admin_notices', function() use ( $admin_notices ) {echo $admin_notices;}, 10 );
              //v2.0.0.2 end
              update_option( 'woocommerce_enable_coupons','yes'); //v2.0.0.5            
            }
          } 
        }  
        /*  should always be done in the settings page!! don't do here.
        else {
    //error_log( print_r(  'vtprd_woo_maybe_DELETE_coupon_types PARENT FUNCTIONS', true ) );       
        
            //get rid of old single coupon names //v2.0.0.5
            vtprd_woo_maybe_delete_all_single_auto_coupons();  //v2.0.0.5 
        } 
        */
          
      //v2.0.0 begin - M solution - only need to do this once
      remove_action('admin_init', 'vtprd_check_for_deactivation_action');
      return;
      //v2.0.0 end - M solution
      
      //v2.0.0.5 end - rewrite
              
  }
  //v1.0.9.3 end

 //******************************
 //v1.1.0.9 New Function 
 //  done here because the page is needed later during an ajax call, and is_page is NOT available in ajax
 //****************************** 
   
  //v2.0.0.2 begin
   //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
   if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
        (defined( 'DOING_CRON' )) ) {          
        //error_log( print_r(  'vtprd_maybe_store_admin_price_change - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
      $do_nothing = true;          
   } else {
      add_action('wp_head','vtprd_check_for_page'); 
   }
   //v2.0.0.2 end  

  function vtprd_check_for_page() {            
    //error_log( print_r(  'Function begin - vtprd_check_for_page', true ) ); 
       //v2.0.0.2 moved above
       /*
       //v2.0.0 begin - i Solution
       //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
       if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
            (defined( 'DOING_CRON' )) ) { 
          //error_log( print_r(  'vtprd_process_discount - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );     
          return;          
       }   
      //v2.0.0 end - i solution
      */

      if(!isset($_SESSION)){
        session_start();
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
      }  
         
       vtprd_debug_options();  //v1.1 
       
      //v1.1.6.7 begin
      //this is unnecessary in ADMIN!
      /*  BUT this adds ballast to the Execution of discounts, a BAD THING - so don't do it.
      if ( (function_exists( 'get_current_screen_vtprd' ) ) ||    // get_current_screen_vtprd ONLY exists in ADMIN!!!  
          ( is_admin() ) ) { 
         return;              
      }
      */
      //v1.1.6.7 end
    
      global $wp_query;
      
      //********************
      //v2.0.0.5a begin
      //$wp_query object not found rarely...
      
      //v2.0.0.8 begin 
      /*
      is_object is passing wp_query, but we still get Trying to get property of non-object
      so added an 'isset' after the is_object
      */
      if ( (is_object($wp_query)) &&
           (isset($wp_query->post->ID)) )  {
        $postID = $wp_query->post->ID;      
      } else {
        global $post;
        if ( (is_object($post)) && 
             (isset($post->ID)) ) {
          $postID = $post->ID;
        } else {        
          return;
        }
      }  
       //v2.0.0.8 end  
       
      //$postID = $wp_query->post->ID;
       
      //v2.0.0.5a end
      //********************
             
       //v2.0.0 begin - p solution
             
      global $vtprd_info; 
      if (!is_array($vtprd_info)) {
        require_once ( VTPRD_DIRNAME . '/woo-integration/vtprd-parent-definitions.php');
      }
        
      $woocommerce_cart_page_id = get_option ("woocommerce_cart_page_id");
      $woocommerce_checkout_page_id = get_option ("woocommerce_checkout_page_id");
         
      //have to set these at init time - accessed in ajax, and is_page not available there
      if ( ($postID == $woocommerce_cart_page_id) ||
           ($postID == $woocommerce_checkout_page_id) ) {
        $_SESSION['on_cart_or_checkout_page'] = true; 
      } else {
        $_SESSION['on_cart_or_checkout_page'] = false;
      }


      //v2.0.0
      //------------------------------------------------
      //  auto add for free + coupon activation issue on checkout page when processing new coupon initiation
      //------------------------------------------------      
      // if checkout page, does not run in ajax, so has to be done at initial load
      // if auto add free product possible and not yet done
      // get rid of existing session, which otherwise causes a hangup in processing
      // 
      if ($postID == $woocommerce_checkout_page_id) { 
        if (get_option ('vtprd_ruleset_contains_auto_add_free_coupon_initiated_deal') == 'yes')  {
        //v2.0.2.0 begin
          //error_log( print_r(  'before vtprd_get_transient_cart_data  0006 ', true ) );
          $get_previous_auto_add_array = vtprd_get_transient_cart_data ('previous_auto_add_array');
          if ($get_previous_auto_add_array) {
             $previous_auto_add_array = unserialize($get_previous_auto_add_array);
          }  else {
             $previous_auto_add_array = array();
          }
          if (sizeof($previous_auto_add_array) > 0)  {
               
               $auto_add_already_exists = true; //do nothing  
                          
          } else {

              //session_destroy(); 
              vtprd_destroy_session_save_keys();   

          }
        } 
/*
        if (get_option ('vtprd_ruleset_contains_auto_add_free_coupon_initiated_deal') == 'yes')  {
        //v2.0.2.0 begin
          $get_previous_auto_add_array = vtprd_get_transient_cart_data ('previous_auto_add_array');
          if ($get_previous_auto_add_array) {
             $previous_auto_add_array = unserialize($get_previous_auto_add_array);
          }  else {
             $previous_auto_add_array = array();
          }
          if ($get_previous_auto_add_array) {
             //$previous_auto_add_array = unserialize($_SESSION['previous_auto_add_array']);
             if (sizeof($previous_auto_add_array) > 0)  {
               
               $auto_add_already_exists = true; //do nothing  
                 
             } else {

              //session_destroy(); 
              vtprd_destroy_session_save_keys();   

             }          
          } else {

              //session_destroy(); 
              vtprd_destroy_session_save_keys();   

          }
        } 
*/           
        //v2.0.2.0 end    
        $_SESSION['on_checkout_page'] = true; 
        return;              
      } else {
        $_SESSION['on_checkout_page'] = false;
      }      
      

      //v1.1.7.1 begin
      if ($postID == $woocommerce_cart_page_id) {        
        $_SESSION['on_cart_page'] = true;         
        vtprd_send_cart_js_trigger_page_reload();
        return;
        //add_filter('woocommerce_coupon_message','vtprd_add_trigger_to_coupon_message', 10, 3);        
      } else {
        $_SESSION['on_cart_page'] = false;
      }      
      //v1.1.7.1 end
    
    return;
          
    //v2.0.0 end - p solution

     
  }  
  
 //******************************
 //v1.1.7.1 New Function 
 // The JS will look for the string '+++', and if found, 
 // and trigger a forced page reload to pick up any Pricing Deals discount
 //******************************  
	function vtprd_send_cart_js_trigger_page_reload() {
  
 //error_log( print_r(  'Function begin - vtprd_send_cart_js_trigger_page_reload', true ) ); 
    //the JS triggers the text search in the class at ajaxComplete time.
    //  http://api.jquery.com/ajaxcomplete/
    //  if trigger '+++' from ME is found, 
    //     then do the window reload (true = force reload from server) to pick up the Pricing Deals discount
    
    //OVERRIDE if JS not desired
   /*
   Valid Values for FILTER:
      CouponOnly - only send JS on Cart Page when an existing rule is actuated by a Coupon
      Never - never send the JS on Cart Page [DEFAULT] 
      Always - always on Cart Page 
      
            //Be sure to clear the cache and start a fresh browser session when testing this...
            function js_trigger_cart_page_reload() {
           		 return 'Never';  //valid values: 'CouponOnly' / 'Never' / 'Always'
          	}
          	add_filter('vtprd_js_trigger_cart_page_reload', 'js_trigger_cart_page_reload', 10);
      
   */
    
    //***********************************
    //currently turned off by default!
    //***********************************
    $vtprd_js_trigger_cart_page_reload = apply_filters('vtprd_js_trigger_cart_page_reload','Never' );
    //***********************************
    
    if ($vtprd_js_trigger_cart_page_reload == 'Never' ) {
   //error_log( print_r(  'vtprd_send_cart_js_trigger_page_reload - NEVER', true ) );
      return;
    }
    
    if ($vtprd_js_trigger_cart_page_reload == 'CouponOnly' ) {
      $vtprd_rule_coupon_activations = get_option( 'vtprd_rule_coupon_activations', array() );
      //IF not coupon activations, DO NOT SEND!!
      if (sizeof($vtprd_rule_coupon_activations) == 0) {
         //error_log( print_r(  'vtprd_send_cart_js_trigger_page_reload - NO COUPONS FOUND', true ) );
        return;
      }
    }
    
   //error_log( print_r(  'vtprd_send_cart_js_trigger_page_reload - SEND JS', true ) );
    
    //filter = 'Always' is a fallthrough.    

    ?>
      <script type="text/javascript">
          jQuery(document).ready(function($) {
             $( document ).ajaxComplete(function() { 
                //alert ('about to reload');
                location.reload(true);
                  /*
                  NO LONGER USED WITH TRIGGER searchValue...
                  var searchValue = "+++";
                  
                  $(".woocommerce-message").each(function(){
                    if($(this).html().indexOf(searchValue) > -1){
                       //match has been made
                       alert ('about to reload');
                       location.reload(true);
                    }
                  });
                  */
              });
          });   
      </script>
    <?php
  }
  

/*
 //******************************
 //v1.1.7.1 New Function 
 //******************************  
	function vtprd_add_trigger_to_coupon_message($msg,$msg_code,$coupon) {
    if ($coupon->WC_COUPON_SUCCESS) {
      $vtprd_rule_coupon_activations = get_option( 'vtprd_rule_coupon_activations', array() );
      if (sizeof($vtprd_rule_coupon_activations) == 0) {
        return $msg;
      }
      //if a coupon code has been presented which JUST activates an active Pricing Deals rule
      if (in_array($msg_code, $vtprd_rule_coupon_activations)) {
        $msg .= ' +++'; //THIS is the string that the JS will use to trigger the FORCED PAGE REFRESH
      }    
    }
    
    return $msg;

  }  
 */
 
  
 //******************************
 //v1.1.1 New Function 
 //******************************  
	function vtprd_strip_out_price_format($price_formatted, $product) {

    $currency_symbol = get_woocommerce_currency_symbol();
    
    //strip out currency symbol
    $price =  str_replace($currency_symbol,'',$price_formatted);
    
    $price_suffix = $product->get_price_suffix();
    
    //strip out suffix, if there
    $price =  str_replace($price_suffix,'',$price);
   
    //********************************* 
    //v1.0.9.5 begin - crossout price fix for using different decimal/thousands separators
    
    //strip out thousands separator ==>>(getting it this way covers pre-2.3 versions...)  
    $thousands_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
    $price =  str_replace($thousands_sep,'',$price);
    
    //reformat into decimal as needed if decimal separator not "." AND turn decimal into floatval
    $price = wc_format_decimal($price, 2);

  return $price;
  }
  

   /* ************************************************
  **   Admin - v1.1.5 new actions and functions
  *   +++++++++++++++++++++++++++++++++++++++++++++
  *     
      *******************************
      Woocommerce PRICE UPDATE hooks, to trigger new price update TS:::
      
      at Store access time, using ** vtprd_maybe_store_admin_price_change() ** ,
      check TS against recent accss.  If updated, clear all sessions to redo pricing.
      ******************************* 
      *       
  *************************************************** */  
  add_action('woocommerce_product_quick_edit_save',    'vtprd_update_session_ts_on_price_change') ;
  add_action('woocommerce_product_bulk_edit_save',     'vtprd_update_session_ts_on_price_change') ;
  add_action('woocommerce_process_product_meta',       'vtprd_update_session_ts_on_price_change') ;
  //v1.1.7.2 begin grpd
  if ( version_compare( WC_VERSION, '3.1.2', '>=' ) ) {   
    add_action('woocommerce_new_product_variation',   'vtprd_update_session_ts_on_price_change') ;
  } else {
    add_action('woocommerce_create_product_variation',   'vtprd_update_session_ts_on_price_change') ;  
  }
  //v1.1.7.2 end
  add_action('woocommerce_update_product_variation',   'vtprd_update_session_ts_on_price_change') ;
  add_action('woocommerce_api_process_product_meta_',  'vtprd_update_session_ts_on_price_change') ;
  add_action('woocommerce_api_save_product_variation', 'vtprd_update_session_ts_on_price_change') ;

  function  vtprd_update_session_ts_on_price_change() { 

    update_option( 'vtprd_last_admin_update_ts', time() );
  }


  add_action('save_post',   'vtprd_update_session_ts_on_save' );  
 
  function  vtprd_update_session_ts_on_save() { 
  
    global $post, $vtprd_info;
    if( !isset( $post ) ) {    
      return;
    }
    if ($post->post_type == $vtprd_info['parent_plugin_cpt']) {
      update_option( 'vtprd_last_admin_update_ts', time() );
    }
    
    return;

  }

  
  //v2.0.0.2 begin
   //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:
   if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
        (defined( 'DOING_CRON' )) ) {          
        //error_log( print_r(  'vtprd_maybe_store_admin_price_change - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
      $do_nothing = true;          
   } else {
      add_action('wp_head', 'vtprd_maybe_store_admin_price_change'); 
   }
   //v2.0.0.2 end 

  function  vtprd_maybe_store_admin_price_change() { 
   //error_log( print_r(  'vtprd_maybe_store_admin_price_change', true ) ); 
  /*
    allow front end to recognize back end price changes ON SCREEN REFRESH
    if Site Admin has made a backend change, all Pricing Deals save prices are deleted
    (Only vulnerability is if an auto add free item is in process, the auto add system may loose track)
    
    (backend pricing changes option is update here: vtprd_update_ts_on_price_change() )
  */
  
    //if is_admin, this frontend test is invalid.  exit stage left.
    
      //v2.0.0.2 moved above
     /*
     //v2.0.0 begin
       //wp-admin calls doing ajax can be confused with other calls - best to test the ACTIVE PAGE:

     if ( (strpos($_SERVER["REQUEST_URI"],'wp-admin') !== false) ||
          (defined( 'DOING_CRON' )) ) {          
          //error_log( print_r(  'vtprd_maybe_store_admin_price_change - do not run in ADMIN, exiting function, REQUEST_URI= ' .$_SERVER["REQUEST_URI"], true ) );
        return;          
     } 
     */
    /*
    if ( (function_exists( 'get_current_screen' ) ) ||    // get_current_screen ONLY exists in ADMIN!!!  
        ( is_admin() ) ) { 
       return;              
    }
    */
    //v2.0.0 end
    
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }
    
    //first time through
    if (!isset($_SESSION['last_frontend_ts'])) {
      $_SESSION['last_frontend_ts'] = time();
      return;    
    }
    
    if (get_option( 'vtprd_last_admin_update_ts' ) !== false) {
      $carry_on = true;
    } else {
      return;
    }
    
    $vtprd_last_admin_update_ts = get_option( 'vtprd_last_admin_update_ts' );

    //------------------------------
    //v2.0.2.0 reworked - begin
    /*
    if ($vtprd_last_admin_update_ts > $_SESSION['last_frontend_ts']) {
      //grab AUTO-ADD info
      if (isset($_SESSION['current_auto_add_array']))  {
         $current_auto_add_array = unserialize($_SESSION['current_auto_add_array']);
      } else {
        $current_auto_add_array = false;
      }
      if (isset($_SESSION['previous_auto_add_array']))  {
         $previous_auto_add_array = unserialize($_SESSION['previous_auto_add_array']);
      } else {
        $previous_auto_add_array = false;
      }
            
      //this forces Pricing Deals to get all new prices and redo all discounts for the CLIENT, to pick up the new SITE ADMIN pricing changes
      //v2.0.2.0 begin
      //session_destroy(); 
      vtprd_destroy_session_save_keys();   
      //v2.0.2.0 end
      
      //new frontend timestamp
      $_SESSION['last_frontend_ts'] = time();
      
      //restore auto-add tracking
      if ($current_auto_add_array) {
        $_SESSION['current_auto_add_array'] = serialize($current_auto_add_array);
      }      
      if ($previous_auto_add_array) {
        $_SESSION['previous_auto_add_array'] = serialize($previous_auto_add_array); 
      }
      
    }
    */
    if ($vtprd_last_admin_update_ts > $_SESSION['last_frontend_ts']) {
      //grab AUTO-ADD info

      $cart_id = $_SESSION['vtprd_unique_cart_id'];
      
      //the 'get' fields contained either 'false' or the already searilaized arrays
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0007 ', true ) );
      $get_previous_auto_add_array  =  vtprd_get_transient_cart_data ('previous_auto_add_array',$cart_id);
      //error_log( print_r(  'before vtprd_get_transient_cart_data  0008 ', true ) );
      $get_current_auto_add_array   =  vtprd_get_transient_cart_data ('current_auto_add_array',$cart_id);

            
      //this forces Pricing Deals to get all new prices and redo all discounts for the CLIENT, to pick up the new SITE ADMIN pricing changes
      //session_destroy(); 
      vtprd_destroy_session_save_keys();   
      
      //new frontend timestamp
      $_SESSION['last_frontend_ts'] = time();
            

      if ($get_previous_auto_add_array)  {
        //error_log( print_r(  'set previous_auto_add_array  0001 ', true ) );
        vtprd_set_transient_cart_data ( 'previous_auto_add_array', $get_previous_auto_add_array, $cart_id );
      }       
      if ($get_current_auto_add_array)  {
        vtprd_set_transient_cart_data ( 'current_auto_add_array', $get_current_auto_add_array, $cart_id );
      }
      
    }
    //v2.0.2.0 end
    //------------------------------
    
    return;
  
  }


  
//v1.1.5 END


     //******************
     //v1.1.7.2 begin
     //******************
     //v1.1.7.2 - function re-coded
    
    /*
        ITEM Object
        (
            [id] => 10696
            [slug] => phpbb-single-sign-on
            [plugin] => phpbb-single-sign-on/connect-phpbb.php
            [new_version] => 0.9
            [url] => https://wordpress.org/plugins/phpbb-single-sign-on/
            [package] => https://downloads.wordpress.org/plugin/phpbb-single-sign-on.zip
        )  
        
        auto_update_plugin runs every 12 hours via cron job.  for testing, initate using WP Crontrol plugin
        add a CRON event labeled "wp_maybe_auto_update"  and run it!
    */
    
    //***************************************************************************
    //auto_update_plugin tested using function below: vtprd_test_auto_update
    //***************************************************************************
    
    function vtprd_exclude_plugins_from_auto_update( $update, $item ) {
       /* 
       error_log( print_r(  'vtprd_exclude_plugins_from_auto_update BEGIN ' , true ) );
       error_log( print_r(  '$update= ' , true ) ); 
       error_log( var_export($update, true ) );
       error_log( print_r(  '$item= ' , true ) ); 
       error_log( var_export($item, true ) );       
       */ 
        if ( in_array( trim($item->slug), array(
                'pricing-deals-for-woocommerce',
                'pricing-deals-pro-for-woocommerce'
            ) ) ) {
      //error_log( print_r(  'vtprd_exclude_plugins_from_auto_update FALSE 1 Exit ' , true ) );       
           return false; 
        }
        
        if ( in_array( trim($item->plugin), array(
                'pricing-deals-for-woocommerce/vt-pricing-deals.php',
                'pricing-deals-pro-for-woocommerce/vt-pricing-deals-pro.php'
            ) ) ) {
       //error_log( print_r(  'vtprd_exclude_plugins_from_auto_update FALSE 2 Exit ' , true ) );     
           return false; 
        }            
                     
        //from https://codex.wordpress.org/Configuring_Automatic_Background_Updates
        return $update; // Else, use the normal API response to decide whether to update or not
        
    }
    add_filter( 'auto_update_plugin', 'vtprd_exclude_plugins_from_auto_update', 99, 2 ); //make sure we're last in line


    /*  FOR TESTING - TO IDENTIFY NAME AND SLUG FOR ABOVE
    function vtprd_display_plugins( ) {
      $plugins = get_plugins();  
      error_log( print_r(  'PLUGINS LIST= ' , true ) ); 
      error_log( var_export($plugins, true ) );
    }    
    add_filter( 'admin_init', 'vtprd_display_plugins', 99, 2 );
    */
    /*
    //from https://gist.github.com/nacin/7047909
    function vtprd_test_auto_update() {
            error_log( print_r(  'vtprd_test_auto_update BEGIN ' , true ) );
        		delete_site_option( 'auto_core_update_failed' );
        		delete_option( 'auto_upgrader.lock' );
        		delete_site_transient( 'update_core' );
        		wp_maybe_auto_update();
            //wp_mail( 'me@example.net', 'The subject', 'The message' ); 
            error_log( print_r(  'vtprd_test_auto_update COMPLETED ' , true ) );
    }    
    add_filter( 'admin_init', 'vtprd_test_auto_update', 99);
    */
   //******************
   //v1.1.7.2 end
   //******************





 //******************************
 //v1.1.1 New Function 
 //  Sales Flash for Catalog Rule Price Discount
 //     BUT>>>  how to differentiate between SALE rule and Wholesale rule?
 //           --->>>  need another flag on the RULE, and then pass it in vtprd_info!!
 //******************************  
/*
//v1.1.7.2 reformatted and implemented
ADD FILTER TO TURN THIS OFF AT WILL, SURROUNDING THE ADD ACTION PERHAPS
GO AFTER THE SESSION INFO RATHER THAN THE REGULAR INFO....  IF THERE'S A DISCOUNT, SAY TRUE!
*/
  if (apply_filters('vtprd_show_catalog_deal_sale_badge',TRUE ) ) {
    add_action('woocommerce_product_is_on_sale','vtprd_maybe_on_sale', 10, 2) ;
  }
	function vtprd_maybe_on_sale($is_on_sale, $product_info) {
  //return true only if discount by Pricing Deals 
    global $vtprd_info;
    
    //if already on_sale, we're done
    if ($is_on_sale) {
      return $is_on_sale;
    }
 
    
    //if it's a variable product, we're still getting JUST the parent.
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


    if ($is_a_variable_product)  { 
      if ( version_compare( WC_VERSION, '3.0.0', '>=' )) {
          $product_children = $product_info->get_children();
          $product_visible_children = $product_info->get_visible_children();
          $sizeof_children = sizeof($product_visible_children);     
      }  else {
        if (sizeof($product_info->children) == 0) {  
          $product_info->get_children();                
        }
        if (isset($product_info->children['visible'])) {
          $sizeof_children = sizeof($product_info->children['visible']); 
        } else {
          //pre woo 2.4
          $sizeof_children = sizeof($product_info->children);
        } 
      }

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

            
            //error_log( print_r(  '$product_id= ' .$product_id, true ) );
            //error_log( print_r(  'vtprd_maybe_on_sale,  $product= ', true ) );
            //error_log( var_export( $product_info, true ) );       
            //error_log( print_r(  'product_session_info= ', true ) );
            //error_log( var_export( $vtprd_info['product_session_info'], true ) );
          
        //v2.0.0.5a  added 'isset' to IF
        if ( ( isset($vtprd_info['product_session_info']['product_yousave_total_amt']) ) &&        
             ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0) ) {
          return true;
        }
      } //end FOR
    } else {    //NOT a variable product
      vtprd_maybe_get_product_session_info($product_id);
      //v2.0.0.5a  added 'isset' to IF
      if ( ( isset($vtprd_info['product_session_info']['product_yousave_total_amt']) ) &&        
           ($vtprd_info['product_session_info']['product_yousave_total_amt'] > 0) ) {
        return true;
      }   
    }
    /*
    if ( $product->has_child() ) {
			foreach ( $product->get_children( true ) as $child_id ) {
				$price      = get_post_meta( $child_id, '_price', true );
				$sale_price = get_post_meta( $child_id, '_sale_price', true );
				if ( $sale_price !== "" && $sale_price >= 0 && $sale_price == $price ) {
					$is_on_sale = true;
				}
			}
		}
    */
    
    
		return $is_on_sale;
	}



     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************
     /*
    \n = CR (Carriage Return) // Used as a new line character in Unix
    \r = LF (Line Feed) // Used as a new line character in Mac OS
    \n\r = CR + LF // Used as a new line character in Windows
    (char)13 = \n = CR // Same as \n
    http://en.wikipedia.org/wiki/Newline
    */
  /* ************************************************
  **   Assemble all of the cart discount row info FOR email/transaction results messaging  
  *        $msgType = 'html' or 'plainText'            
  *************************************************** */
	function vtprd_email2_cart_reporting($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_rules_set, $vtprd_info, $vtprd_setup_options;
    $output = ''; //v1.0.7.9
    
    if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {
      if ($msgType == 'html') {
        //Skip a line between products and discounts      		
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="5"> &nbsp;</td>';	//v1.1.8.0  colspan="3"	to colspan="5"		
        $output .= '</tr>'; 
        
        //v1.1.8.0 begin 
        if ( apply_filters('vtprd_show_discount_reporting_title',TRUE) ) {
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;"  colspan="5">' . __('Discount Reporting', 'vtprd') .'</td>';			//v1.1.8.0  colspan="3"	to colspan="5"			
        $output .= '</tr>';
        }  
        //v1.1.8.0 end
                
        //v1.1.8.0 changed TD to TH in headeing row!!
        //New headers, but printed as TD instead, to keep the original structure going...                    
        $output .= '<tr>';
        $output .= '<th style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;width:40%">' . __('Item', 'vtprd') .'</td>';			
        $output .= '<th style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;width:15%">' . __('Orig Price', 'vtprd') .'</td>';			
        $output .= '<th style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;width:15%">' . __('Qty', 'vtprd') .'</td>';		
        $output .= '<th style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;width:15%">' . __('Subtotal', 'vtprd') .'</td>';        
        $output .= '<th style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold;width:15%">' . __('Discount Subtotal', 'vtprd') .'</td>';        
        $output .= '</tr>';    
      
      } else {
        //first a couple of page ejects
        $output .= "\r\n \r\n";
        $output .= __( 'Discounts ', 'vtprd' );
        $output .= "\r\n";
      }
    }
 
    if ($vtprd_setup_options['show_checkout_discount_detail_lines'] == 'yes') { //v1.0.9.0
      //get the discount details    
      $output .= vtprd_email2_cart_discount_rows($msgType);
    }
     
    vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    
    if ($vtprd_setup_options['show_checkout_discount_total_line'] == 'yes') {
        
        //v1.0.8.9a begin               
        //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);        
        $amt = vtprd_load_cart_total_incl_excl();
        $amt = vtprd_format_money_element($amt); 
        $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4         
        //v1.0.8.9a end  
                  
      if ($msgType == 'html') {        
        //v1.0.8.9a begin               
        //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt); 
        //$amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4             
        //v1.0.8.9a end       
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;font-weight:bold"  colspan="2">'. $vtprd_setup_options['checkout_credit_total_title'] .'</td>';						
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee">'  . $vtprd_setup_options['checkout_credit_total_label'] .$amt .'</td>';		
        $output .= '</tr>';   
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>';
      } else {
        $output .= "\r\n";
        $output .= "\n" .$vtprd_setup_options['checkout_credit_total_title'];
        $output .= "\n" .$vtprd_setup_options['checkout_credit_total_label'] .$amt ;
      }
    }      

      //v1.1.7.2 Begin
      if ($msgType == 'html') {
        //Skip a line at end      		
        $output .= '<tr>';
        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"  colspan="3"> &nbsp;</td>';				
        $output .= '</tr>';   
      
      } else {
        $output .= "\r\n";
      }
      //v1.1.7.2 end

                   
    return $output;
    
  }


     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************
  
  //coupon discount only shows at Checkout 
	function vtprd_email2_cart_coupon_discount_row($msgType) {
    global $vtprd_cart, $vtprd_rules_set, $vtprd_setup_options;

    $output = ''; //v1.0.7.9
    $amt = vtprd_format_money_element($vtprd_cart->wpsc_orig_coupon_amount);  //show original coupon amt as credit
    
    vtprd_format_money_element($vtprd_cart->wpsc_orig_coupon_amount);  //show original coupon amt as credit
       
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . __('Coupon Discount', 'vtprd') .'</td>';
        $output .= '<td>' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';
      $output .= '</tr>';    
    } else {
      $output .= __('Coupon Discount: ', 'vtprd'); 
      
      $output .= $amt;
      $output .= "\r\n \r\n";
    }

    return $output; 
    
  }      

     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************
      
  /* ************************************************
  **   Assemble all of the cart discount row info              
  *************************************************** */
	function vtprd_email2_cart_discount_rows($msgType) {
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
       
      $output = ''; //v1.0.7.9

      $sizeof_cart_items = sizeof($vtprd_cart->cart_items);
      for($k=0; $k < $sizeof_cart_items; $k++) {  
       	//if ( $vtprd_cart->cart_items[$k]->yousave_total_amt > 0) { 
        //v1.1.0.6 end
        if ( ($vtprd_cart->cart_items[$k]->yousave_total_amt > 0) || 
             ($vtprd_cart->cart_items[$k]->zero_price_auto_add_free_item == 'yes') ) {
        //v1.1.0.6 end           
            if ($vtprd_setup_options['show_checkout_discount_details_grouped_by_what']   == 'rule') {
              //these rows are indexed by ruleID, so a foreach is needed...
              foreach($vtprd_cart->cart_items[$k]->yousave_by_rule_info as $key => $yousave_by_rule) {
              
                //display info is tabulated for cumulative rule processing, but the Price Reduction has already taken place!!
                if ($yousave_by_rule['rule_execution_type'] == 'cart') {
                  //CREATE NEW SWITCH
                 // if ($vtprd_setup_options['show_checkout_discount_each_msg'] == 'yes') {
                    if ($msgType == 'html')  {
                        $output .= '<tr  class="vtprd-rule-msg-checkout"  >';
                        $output .= '<td style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word;" colspan="5">' . stripslashes($yousave_by_rule['rule_short_msg'])  .'</td>';				
                        $output .= '</tr>';                       
                    } else {
                      $output .= "\n" .  stripslashes($yousave_by_rule['rule_short_msg']) . "\r\n"; 
                    }                                 
                    $amt   = $yousave_by_rule['yousave_amt']; 
                    $units = $yousave_by_rule['discount_applies_to_qty'];                  
                    $output .= vtprd_email2_discount_detail_line($amt, $units, $msgType, $k); 
              
                 // } 
                }                
              }
            } else {   //show discounts by product
                  $amt = $vtprd_cart->cart_items[$k]->yousave_total_amt; 
                  $units = $vtprd_cart->cart_items[$k]->yousave_total_qty;                  
                  $output .= vtprd_email2_discount_detail_line($amt, $units, $msgType, $k);
           }
        }
      }

    return $output;
    
  }

 
     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************
     
	function vtprd_email2_discount_detail_line($amt, $units, $msgType, $k) {  
    global $vtprd_cart, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;
      $output = ''; //v1.0.7.9
          
      //v1.0.7.4 begin      
      //$amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn
      // $amt = vtprd_format_money_element($amt); //mwn
      //v1.0.7.4 end 
         
     
     //v1.1.0.6 begin      
    if ( ($amt == 0) && 
         (apply_filters('vtprd_show_zero_price_as_free',TRUE)) ) { //if zero is preferred, send back FALSE
      $amt = __('Free', 'vtprd');
    } else {
      $amt = vtprd_format_amt_and_adjust_for_taxes($amt, $k);  //has both formatted amount and suffix, prn  //v1.0.7.4 begin
    }
    // $amt = vtprd_format_money_element($amt);
    //v1.1.0.6 end   
        
         
         
    if ($msgType == 'html')  {
      $output .= '<tr>';

      if (sizeof($vtprd_cart->cart_items[$k]->variation_array) > 0   ) {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word"><span class="vtprd-product-name-span">' . $vtprd_cart->cart_items[$k]->parent_product_name .'</span>';
        $output .= '<small>';
        //v1.0.7.9 begin
        if ($vtprd_cart->cart_items[$k]->variation_name_html > '')  {
          $variation_name_html = $vtprd_cart->cart_items[$k]->variation_name_html;
          //remove wrapping paragraph on variation name...
          $variation_name_html = str_replace( '<p>',  '', $variation_name_html  );
          $variation_name_html = str_replace( '</p>', '', $variation_name_html  );                                                                               
           $output .= $variation_name_html;
        } else {
        //v1.0.7.9 end        
           // $output .= '<dl class="variation">';
          foreach($vtprd_cart->cart_items[$k]->variation_array as $key => $value) {          
            //v1.0.7.8  begin                
            $name  = str_replace( 'attribute_pa_', '', $key  );  //post v 2.1
            $name  = str_replace( 'attribute_', '', $key  );     //post v 2.1   for on-the-fly variations
            $value = str_replace( 'attribute_', '', $value  );   //post v 2.1   for on-the-fly variations
            $name  = str_replace( 'pa_', '', $name  );   //pre v 2.1
            $current_version =  WOOCOMMERCE_VERSION;
            if( (version_compare(strval('2.0.2.0'), strval($current_version), '>') == 1) ) {   //'==1' = 2nd value is lower     
              //pre 2.1
              $name  = sanitize_title($name);
              $value = sanitize_title($value);
              $name  = ucwords($name);  
              $output .= '<br class="variation-'.$name.'">'. $name . ': ' .$value ;  //added class
            } else {
              //post 2.1
              $name2 = sanitize_text_field( $name );
              $output .= '<br class="variation-'.sanitize_html_class( $name2 ).'">'. wp_kses_post( $name ) . ': ' .wp_kses_post( wpautop( $value ));  //added class
            }
            //v1.0.7.8  end                       
          }
          //$output .= '</dl></small>'; 
        }  //v1.0.7.9 
        $output .= '</small>';      			
        $output .= '</td>';     
      } else {
        $output .= '<td  class="vtprd-product-name-email" style="text-align:left;vertical-align:middle;border:1px solid #eee;word-wrap:break-word">' . $vtprd_cart->cart_items[$k]->product_name .'</td>';
      }
      
      	//v1.1.8.0 bergin
        // HERE's where the PARTIAL discount would be acknowledged on a currency discount!
      if ( (isset($vtprd_cart->cart_items[$k]->product_orig_cart_unit_price)) &&
           ($vtprd_cart->cart_items[$k]->product_orig_cart_unit_price > '') ) { 
        $orig_amt = vtprd_format_money_element($vtprd_cart->cart_items[$k]->product_orig_cart_unit_price);
        $subtotal = vtprd_format_money_element($vtprd_cart->cart_items[$k]->product_orig_cart_unit_price * $units);
			} else {
        $orig_amt = __('not avail in older orders', 'vtprd');
        $subtotal = $orig_amt;
      }
      $output .= '<td class="vtprd-orig-price" style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $orig_amt .'</td>'; //v1.1.8.0 
      //v1.1.8.0 end    
      
      $output .= '<td class="vtprd-quantity-email" style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $units .'</td>';			
      
      $output .= '<td class="vtprd-subtotal" style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $subtotal .'</td>'; //v1.1.8.0 
      
      $output .= '<td class="vtprd-amount-email"  style="text-align:left;vertical-align:middle;border:1px solid #eee">' . $vtprd_setup_options['checkout_credit_detail_label'] . ' ' .$amt .'</td>';		
      $output .= '</tr>';        
    } else {
      if ($vtprd_setup_options['show_checkout_discount_titles_above_details'] == 'yes') {  //v1.0.9.0 
        $output .= "\n" . __( 'Product: ', 'vtprd' ); 
        $output .= "\n" . $vtprd_cart->cart_items[$k]->product_name;
        $output .= "\n" . __( ' Discount Units: ', 'vtprd' );
        $output .= "\n" . $units ;
        
        
        //v1.0.9.0 added new title
        if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon')  {   
          $output .= "\n" . __( ' Discount Amount: ', 'vtprd' );
        } else {
          $output .= "\n" . __('  Discount', 'vtprd') .'<br>'.  __('( in Unit Price )', 'vtprd');
        } 
        
        $output .= "\n" . $amt;
        $output .= "\r\n";
      }
    }
    
    return  $output;  
 }
   

     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************

  	function vtprd_email2_cart_purchases_subtotal($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

    $output = ''; //v1.0.7.9
    //$subTotal = $vtprd_cart->cart_original_total_amt;    //show as a credit
    $amt = vtprd_get_Woo_cartSubtotal(); 
    
    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td  class="vtprd-subtotal-email" colspan="2">' . $vtprd_setup_options['checkout_credit_subtotal_title'] .'</td>';
        $output .= '<td>' . $amt .'</td>';
      $output .= '</tr>';   
    } else {
      $output .= $vtprd_setup_options['checkout_credit_subtotal_title'];
      $output .= '  ';
      $output .= $amt;
      $output .= "\r\n";        
    }
    return $output;  
  }
 

     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************
       
	function vtprd_email2_cart_discount_total($msgType) {
    global $vtprd_cart, $vtprd_rules_set, $vtprd_setup_options;

    $output = ''; //v1.0.7.9
      
    //v1.0.8.9a begin               
    // pick up included, excluded or yousave_cart_total_amt Total       
    $amt = vtprd_load_cart_total_incl_excl();
    //$amt = vtprd_format_money_element($vtprd_cart->yousave_cart_total_amt);
    $amt = vtprd_format_money_element($amt);
    $amt .= vtprd_maybe_load_incl_excl_vat_lit(); 
    //v1.0.8.9a end  

    if ($msgType == 'html')  {
      $output .= '<tr>';
        $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_credit_total_title'] .'</td>';
        $output .= '<td>' . $vtprd_setup_options['checkout_credit_total_label'] . ' ' .$amt .'</td>';
      $output .= '</tr>';   
    } else {      
      $output .= $vtprd_setup_options['checkout_credit_total_title'];          //Discount Total
      $output .= $amt ;
      $output .= "\r\n";        
    }
    
    return $output;  
    
  }
   

     //***************************************  
     //V1.1.8.0  NEW function vtprd_email2
     //***************************************
  	
  //***************************************
  // Subtotal with Discount:  (email)
  //***************************************
  function vtprd_email2_new_cart_checkout_subtotal_line($msgType) {
    global $vtprd_cart, $woocommerce, $vtprd_cart_item, $vtprd_info, $vtprd_rules_set, $vtprd_rule, $vtprd_setup_options;   

      $output = ''; //v1.0.7.9
   
      // for wpec $vtprd_cart->cart_original_total_amt is not accurate - use wpec's own routine
      //$subTotal = $vtprd_cart->cart_original_total_amt - $vtprd_cart->yousave_cart_total_amt;    //show as a credit
      
     
      //v1.0.8.9a begin  
      //$subTotal  = $woocommerce->cart->subtotal;
       
      if ( $woocommerce->cart->tax_display_cart == 'excl' ) {
    		$subtotal = $woocommerce->cart->subtotal_ex_tax ;
    	} else {
    		$subtotal = $woocommerce->cart->subtotal;
      }  
      //v1.0.8.9a end   
            
      //v1.0.8.9a no longer needed  vtprd_load_cart_total_incl_excl(); //v1.0.7.4 
    

      //*****************************
      //No longer used - $subTotal -= $vtprd_cart->yousave_cart_total_amt;
      //*****************************
      //v1.0.8.9a begin               
      // pick up included, excluded or yousave_cart_total_amt Total       
      //$subTotal -= $vtprd_cart->cart_discount_subtotal;  /may or may not contain the coupon amount, depending on passed value calling function
      $subtotal -= vtprd_load_cart_total_incl_excl();
      $amt = vtprd_format_money_element($subtotal);
      //v1.0.8.9a end              
 
      $amt .= vtprd_maybe_load_incl_excl_vat_lit();  //v1.0.7.4
      
      if ($msgType == 'html')  {
        $output .= '<tr>';
          $output .= '<td colspan="2">' . $vtprd_setup_options['checkout_new_subtotal_label'] .'</td>';
          $output .= '<td>' . $amt .'</td>';
        $output .= '</tr>';
      } else {
        $output .= $vtprd_setup_options['checkout_new_subtotal_label'];
        $output .= '  '; 
        $output .= $amt;
        $output .= "\r\n";        
      }
    
    return $output; 
  } 

   
   //***************************************  
   //V2.0.0.5  NEW function
   //***************************************       
	//$setup_options are passed if options screen in process of update *ONLY
  function vtprd_build_inline_front_end_css($setup_options = null) { 
      //global $vtprd_setup_options, $vtprd_rules_set; NO GLOBALS - get the options!
      
      $vtprd_rules_set = get_option('vtprd_rules_set');
      
      if (!$setup_options) {
        $vtprd_setup_options = get_option( 'vtprd_setup_options' ); 
      } else {
        //$setup_options are passed if options screen in process of update *ONLY
        $vtprd_setup_options = $setup_options;
      }

      
      
    //error_log( print_r(  'Function begin - vtprd_build_inline_front_end_css', true ) );
    
      $inline_front_end_css = '';
     
      //build css to prevent the 'remove' from showing for 'discountCoupon'
      if ($vtprd_setup_options['discount_taken_where'] == 'discountCoupon') {
        $coupon_title = strtolower($vtprd_setup_options['coupon_discount_coupon_name']);
        $coupon_title_hyphenated = str_replace( ' ', '-', $coupon_title ); 
        $inline_front_end_css .= 'tr.coupon-' .$coupon_title_hyphenated. ' a {display:none;} ';        
      }
 
     //error_log( print_r(  'show the 00 line: ' .$vtprd_setup_options['show_woo_coupon_cart_totals_00_line'], true ) ); 
      if ($vtprd_setup_options['show_woo_coupon_cart_totals_00_line'] == 'no') {
        //spin through rules_set looking for coupon activation, build CSS
        $sizeof_rules_set = sizeof($vtprd_rules_set);
     //error_log( print_r(  '$sizeof_rules_set: ' .$sizeof_rules_set, true ) ); 
        for($i=0; $i < $sizeof_rules_set; $i++) { 
          if ( ($vtprd_rules_set[$i]->only_for_this_coupon_name > ' ') &&
               ($vtprd_rules_set[$i]->rule_status == 'publish') ) {
            //error_log( print_r(  'in the if, only_for_this_coupon_name at top ' .$vtprd_rules_set[$i]->only_for_this_coupon_name, true ) );
            $only_for_this_coupon_name_lower = strtolower($vtprd_rules_set[$i]->only_for_this_coupon_name);
            $only_for_this_coupon_name_final = str_replace( ' ', '-', $only_for_this_coupon_name_lower );
            //error_log( print_r(  'in the if, only_for_this_coupon_name at middle ' .$only_for_this_coupon_name_final, true ) );
            $inline_front_end_css .= '.coupon-' .$only_for_this_coupon_name_final. '{display:none !important;}'; 
            //error_log( print_r(  'in the if, $inline_front_end_css ' .$inline_front_end_css, true ) );                
		      }
        }      
      }
      
      update_option( 'vtprd_inline_front_end_css',$inline_front_end_css );

    //error_log( print_r(  'vtprd_build_inline_front_end_css at end, $inline_front_end_css= ' .$inline_front_end_css, true ) ); 
       
    return($inline_front_end_css);  //return value needed for setup_options processing
   
  }

  
  //*************************
  //v2.0.0.9 NEW FUNCTION
  //*************************
  function vtprd_build_rule_deal_info() {
      global $vtprd_deal_structure_framework;
      
      
      $rule_deal_info = $vtprd_deal_structure_framework;  

      $rule_deal_info['buy_repeat_condition'] = 'none'; 
      $rule_deal_info['buy_amt_type'] = 'none';
      $rule_deal_info['buy_amt_mod'] = 'none';
      $rule_deal_info['buy_amt_applies_to'] = 'all';
      $rule_deal_info['action_repeat_condition'] = 'none'; 
      $rule_deal_info['action_amt_type'] = 'none';  
      $rule_deal_info['action_amt_mod'] = 'none';
      $rule_deal_info['action_amt_applies_to'] = 'all';
      $rule_deal_info['discount_amt_type'] = '0';
      $rule_deal_info['discount_applies_to'] = 'each';
      $rule_deal_info['discount_rule_max_amt_type'] = 'none';
      $rule_deal_info['discount_lifetime_max_amt_type'] = 'none';
      $rule_deal_info['discount_rule_cum_max_amt_type'] = 'none';   
      
    return $rule_deal_info;  
  }


  //*************************
  //v2.0.2.0 New Function
  //*************************  
	function vtprd_get_transient_cart_data ( $transient_data_type, $cart_id=null ) {
     
       //error_log( print_r(  'Function begin - vtprd_get_transient_cart_data_by_cart_id ', true ) );
       
      global $wpdb;
      
      // $cart_id can be supplied
      if (!$cart_id) {
        $cart_id = $_SESSION['vtprd_unique_cart_id']; 
      }      
    
        
      $transient_object 	= $wpdb->get_var( "SELECT transient_object  FROM `".VTPRD_TRANSIENT_CART_DATA."`   WHERE   `cart_id` = '" . $cart_id . "'  AND   `transient_data_type` = '" . $transient_data_type . "'  " );     	

      //need to un-escape the single quote changes made on the way in (see below)
      $transient_object = str_replace("%27", "'",  $transient_object);        
                  
      return $transient_object; 
   } 
   
   
  //*************************
  //v2.0.2.0 New Function
  //*************************  
	function vtprd_get_transient_cart_id_by_customer_id ( $customer_id, $transient_data_type  ) {
       // customer_id is ONLY ever stored on data_chain, for cross-reference purposes!! 
       //error_log( print_r(  'Function begin - vtprd_get_transient_cart_data_by_customer ', true ) );
       
      global $wpdb;

      $cart_id 	= $wpdb->get_var( "SELECT cart_id  FROM `".VTPRD_TRANSIENT_CART_DATA."`   WHERE   `customer_id` = '" . $customer_id . "'  AND   `transient_data_type` = '" . $transient_data_type . "'  " );     	
                
      return $cart_id; 
   } 
     
     
  //*************************
  //v2.0.2.0 New Function
  //*************************  
	function vtprd_set_transient_cart_data ( $transient_data_type, $transient_object, $cart_id=null ) {
       
      global $wpdb;
               
       //error_log( print_r(  'Function begin - vtprd_set_transient_cart_data', true ) );       
      
      // $cart_id can be supplied
      if (!$cart_id) {
        $cart_id = $_SESSION['vtprd_unique_cart_id']; 
      } 
      //rare case
      if (!$cart_id) {
         $cart_id = vtprd_set_random_unique_cart_id();
      }
      
      if (isset($_SESSION['vtprd_customer_id'])) {
        $customer_id = $_SESSION['vtprd_customer_id'];
      } else {
        $customer_id = null;      
      } 


      //need to escape any single quotes in the data, so we don't get SQL errors
      $transient_object = str_replace("'", "%27",   $transient_object); 
     
          
      $date = date("Y-m-d H:i:s");   
      
      $wpdb->query("INSERT INTO `".VTPRD_TRANSIENT_CART_DATA."` (`cart_id`,`transient_data_type`,`customer_id`,`cart_date`,
      `transient_object`) 
      VALUES ('{$cart_id}','{$transient_data_type}','{$customer_id}', 
      '{$date}','{$transient_object}' )
      ON DUPLICATE KEY UPDATE `customer_id` = '".$customer_id."', `cart_date` = '".$date."', `transient_object` = '".$transient_object."' ;");
      
        //test test
        //$transient_cart = $wpdb->get_row( "SELECT * FROM `" . VTPRD_TRANSIENT_CART_DATA . "` WHERE   `cart_id` = '" . $cart_id . "'  AND   `transient_data_type` = '" . $transient_data_type . "'  LIMIT 1", ARRAY_A );   
        //error_log( print_r(  'vtprd_set_transient_cart_data - transient_cart at SET', true ) );        
        //error_log( var_export($transient_cart, true ) );

      return; 
   } 
      
     
  //*************************
  //v2.0.2.0 New Function
  //************************* 
  //only allow ONE 'customer_id_for_cart_id' to exist per customer !!!!!!!!!! 
	function vtprd_set_unqiue_transient_customer_id_for_cart_id ($transient_data_type, $transient_object) {
      
      //error_log( print_r(  'Function begin - vtprd_set_unqiue_transient_customer_id_for_cart_id', true ) );  
      
      global $wpdb;
               

       $cart_id = $_SESSION['vtprd_unique_cart_id']; 

      
      if (isset($_SESSION['vtprd_customer_id'])) {
        $customer_id = $_SESSION['vtprd_customer_id'];
      } else {
        $customer_id = null;      
      } 


      //only allow ONE 'customer_id_for_cart_id' row to exist per customer.
      // if you are storing this row, and an older one exists with a DIFFERENT CART_ID, DELETE IT!!
      $older_cart_id = vtprd_get_transient_cart_id_by_customer_id ( $customer_id, $transient_data_type  );
      if ($older_cart_id != $cart_id) {
        vtprd_del_transient_cart_data_by_data_type ( $transient_data_type, $older_cart_id );        
      } 

      $date = date("Y-m-d H:i:s");   
      
      $wpdb->query("INSERT INTO `".VTPRD_TRANSIENT_CART_DATA."` (`cart_id`,`transient_data_type`,`customer_id`,`cart_date`,
      `transient_object`) 
      VALUES ('{$cart_id}','{$transient_data_type}','{$customer_id}', 
      '{$date}','{$transient_object}' )
      ON DUPLICATE KEY UPDATE `customer_id` = '".$customer_id."', `cart_date` = '".$date."', `transient_object` = '".$transient_object."' ;");
 
        //test test
        //$transient_cart = $wpdb->get_row( "SELECT * FROM `" . VTPRD_TRANSIENT_CART_DATA . "` WHERE   `cart_id` = '" . $cart_id . "'  AND   `transient_data_type` = '" . $transient_data_type . "'  LIMIT 1", ARRAY_A );   
        //error_log( print_r(  'vtprd_set_unqiue_transient_customer_id_for_cart_id - transient_cart at SET - ', true ) );        
        //error_log( var_export($transient_cart, true ) );

      return; 
   } 
     

              
  //*************************
  //v2.0.2.0 New Function
  //*************************
  // cleanup of a specific data type  
	function vtprd_del_transient_cart_data_by_data_type ( $transient_data_type, $cart_id=null ) {
    
    //error_log( print_r(  'Function begin - vtprd_del_transient_cart_data_by_data_type', true ) );
       
      global $wpdb;
             
      // $cart_id can be supplied
      if (!$cart_id) {
        $cart_id = $_SESSION['vtprd_unique_cart_id']; 
      } 
                    
       //error_log( print_r(  'Function begin - vtprd_del_transient_cart_data_by_type', true ) );
      $sql = "DELETE FROM `".VTPRD_TRANSIENT_CART_DATA."` WHERE   `cart_id` = '" . $cart_id . "'  AND   `transient_data_type` = '" . $transient_data_type . "'  ";  
      $wpdb->query($sql);
       
      return; 
   } 
      
      
  //*************************
  //v2.0.2.0 New Function
  //*************************
  //general cleanup for a cart  
	function vtprd_del_transient_cart_data_by_cart_id ( $cart_id=null  ) {
    
       global $wpdb;

      
      // $cart_id can be supplied
      if (!$cart_id) {
        $cart_id = $_SESSION['vtprd_unique_cart_id']; 
      }      

               
       //error_log( print_r(  'Function begin - vtprd_del_transient_cart_data FOR CART_ID= ' .$cart_id, true ) );    
    
    //no index on cart_id due to max length error of 1000 bytes
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `".VTPRD_TRANSIENT_CART_DATA."` WHERE cart_id = %s",
				$cart_id
			)
		);
           
                
      return; 
   } 
         
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
  //cronjob cleanup run once a day 
	function vtprd_del_transient_cart_data_older_than_3_days() {
     
       //error_log( print_r(  'Function begin - vtprd_del_transient_cart_data_older_than_3_days', true ) );
       
        global $wpdb;

        //from https://thisinterestsme.com/php-subtract-days-date/
         //New DateTime object representing today's date.
        $currentDate = new DateTime();
         
        //Use the sub function to subtract a DateInterval
        $delete_before = $currentDate->sub(new DateInterval('P3D'));    //subtract 3 days from todays date - woo session expiry is 48 hours
         
        //Get yesterday's date in a YYYY-MM-DD format.
        $delete_before_date = $delete_before->format('Y-m-d H:i:s');        
        
        
        //error_log( print_r(  '$delete_before_date= ' .$delete_before_date, true ) );
 
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `".VTPRD_TRANSIENT_CART_DATA."` WHERE cart_date < %s",
				$delete_before_date
			)
		);
                
      return; 
   }   
            
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
	function vtprd_destroy_session_save_keys() {
   //error_log( print_r(  'vtprd_destroy_session_save_keys ' , true ) ); 
        
      if(!isset($_SESSION)){
        return;
      } 

      //error_log( print_r(  'vtprd_unique_cart_id 004 = ' .$_SESSION['vtprd_unique_cart_id'], true ) ); 
            
      if ( isset($_SESSION['vtprd_unique_cart_id']) ) { 
        $vtprd_unique_cart_id = $_SESSION['vtprd_unique_cart_id'];
      } else {
        $vtprd_unique_cart_id = false;      
      }
      

      if ( is_user_logged_in() &&  
         (isset($_SESSION['vtprd_customer_id']) ) )  {
        $vtprd_customer_id = $_SESSION['vtprd_customer_id'];
      } else {
        $vtprd_customer_id = false;
      }     
      
      session_destroy(); 
      
      //delete all transient database rows for unique cart ID
      if ($vtprd_unique_cart_id) {
        vtprd_del_transient_cart_data_by_cart_id($vtprd_unique_cart_id);
        $_SESSION['vtprd_unique_cart_id'] =  $vtprd_unique_cart_id;
      }
      
      if ($vtprd_customer_id) {
         $_SESSION['vtprd_customer_id'] = $vtprd_customer_id;
      }

      //error_log( print_r(  'vtprd_unique_cart_id 005 = ' .$_SESSION['vtprd_unique_cart_id'], true ) );
              
      return; 
   }
                
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
  //this action precedes the cart merge for any saved cart, allows us go get the 'previous' cart image for comparison
  // add_action( 'woocommerce_load_cart_from_session', 'vtprd_get_and_set_saved_woo_session_cart' );
	function vtprd_get_and_set_saved_woo_session_cart() {
    global $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;

      $saved_cart = WC()->session->get_saved_cart();
      if ($saved_cart) {
        $cart_contents =  serialize ($saved_cart);
        vtprd_set_transient_cart_data ('woo_cart_contents_from_saved_session', $cart_contents, $cart_id); 
      }

      return; 
   }              
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
	function vtprd_set_transient_data_chain() {
    global $vtprd_rules_set, $vtprd_cart, $vtprd_setup_options, $vtprd_info, $woocommerce;
  //error_log( print_r(  'vtprd_set_transient_data_chain ' , true ) );
  
      $contents_total   =   $woocommerce->cart->cart_contents_total;
      $applied_coupons  =   $woocommerce->cart->get_coupons(); //v2.0.0  
      $data_chain = array();
      $data_chain[] = $vtprd_rules_set;
      $data_chain[] = $vtprd_cart;
      $data_chain[] = vtprd_get_current_user_role();  //v1.0.7.2
      $data_chain[] = $contents_total;
      $data_chain[] = $applied_coupons;
      $data_chain[] = time(); //v2.0.0

      $transient_object = serialize($data_chain);             
      vtprd_set_transient_cart_data ( 'data_chain', $transient_object );  
       
      return; 
   }
                  
     
  //*************************
  //v2.0.2.0 New Function
  //************************* 
  // resets the unique session as well
	function vtprd_set_random_unique_cart_id($product_id=null) {
      //random number, to make sure this is unique across a single DB with multiple simultaneous store customers

      if (!$product_id) {
        $product_id = rand(10000,10000000); 
      }
      
      $random_number = rand(10000000,10000000000);
            
      $vtprd_unique_cart_id = time() .'||'. $product_id .'||'. $random_number;
      
      $_SESSION['vtprd_unique_cart_id'] =  $vtprd_unique_cart_id;

      //error_log( print_r(  'set randome cart ID vtprd_unique_cart_id = ' .$_SESSION['vtprd_unique_cart_id'], true ) );
              
      return $vtprd_unique_cart_id; 
   } 
                  
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
	function vtprd_unset_random_unique_cart_id() {
      $contents = $_SESSION['vtprd_unique_cart_id'];
      unset( $_SESSION['vtprd_unique_cart_id'], $contents ); 
       
      return; 
   } 
                     
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
	function vtprd_set_unique_cart_id_from_cookie() {  
        //error_log( print_r(  'BEGIN vtprd_set_unique_cart_id_from_cookie', true ) );
       //v2.0.2.01 beg  added is_object test
       if (is_object(WC()->session)) {
         $cookie = WC()->session-> get_session_cookie(); 
         
         //error_log( print_r(  'session object found', true ) );
          
       } else {
         $cookie = FALSE;
         
         //error_log( print_r(  'session object NOT found', true ) );
                  
       }
       //v2.0.2.01 end           

       //error_log( print_r(  'get cookie= ', true ) );
       //error_log( var_export($cookie, true ) );
       if ($cookie) {
         // from: woocommerce/includes/class-wc-session-handler.php ,  function set_customer_session_cookie
         // $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
         $cookie_value = $cookie[0] . '||' . $cookie[1] . '||' . $cookie[2] . '||' . $cookie[3] ;
         $_SESSION['vtprd_unique_cart_id'] = $cookie_value;  
       }  else  {
         //create a random cart id just in case, resets the unique session 
         //error_log( print_r(  'set random cart ID 001', true ) );
         vtprd_set_random_unique_cart_id();                 
       } 
       
      return; 
   }  
                     
         
  //*************************
  //v2.0.2.0 New Function
  //************************* 
	function vtprd_find_or_set_unique_cart_id() {
    
        //IF NO UNIQUE CART ID Session, CHECK IF EXISTING SESSION FOUND
        if (!isset($_SESSION['vtprd_unique_cart_id']) ) {           
            //at login IN A FRESH BROWSER SESSION, the session may not yet be set, and $cookie may not be there.  if so, look for existing session for user_id 
            if ( is_user_logged_in() ) {
               $_SESSION['vtprd_customer_id'] = get_current_user_id();
               $cart_id = vtprd_get_transient_cart_id_by_customer_id ( $_SESSION['vtprd_customer_id'], 'customer_id_for_cart_id' );
               if ($cart_id) { 
                  $_SESSION['vtprd_unique_cart_id'] = $cart_id;
               } else {
                  vtprd_set_unique_cart_id_from_cookie(); 
               }              
            } else {
               vtprd_set_unique_cart_id_from_cookie();                            
            }            
        } 
       
      return; 
   }
   
              
   //**********************************
   //V2.0.2.0  MOVED HERE from apply-rules.php
   //**********************************                                 
   function vtprd_maybe_roll_out_prev_auto_insert_from_woo_cart($previous_auto_add_array, $all_or_single, $single_key=none) {      
      global $woocommerce;
      
     //error_log( print_r(  'vtprd_maybe_roll_out_prev_auto_insert_from_woo_cart ', true ) ); 
 
      /*
      if ($previous_auto_add_array['free_qty']  <= 0) {
        return;
      }
      */
      
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
           //error_log( print_r(  'woo_cart_contents_with_auto_add DEL 0004 ', true ) );
           vtprd_del_transient_cart_data_by_data_type ( 'woo_cart_contents_with_auto_add' );  //v2.0.2.0  remove existing data
         }
         //v2.0.2.0 end
      }  

      return;
   }   
              
   //**********************************
   //V2.0.2.0  New Function
   //**********************************     
  function  vtprd_get_admin_site_ip_address() {
    
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

     if (apply_filters('vtprd_force_new_ip_address',FALSE) ) {
      $skip_this = true;
    } else {
      $vtprd_ip_address = get_option( 'vtprd_ip_address' );
      if ($vtprd_ip_address) {
        return $vtprd_ip_address;
      }    
    }

    
    //THIS ONLY OCCURS WHEN THE PLUGIN IS FIRST INSTALLED!
    // from http://stackoverflow.com/questions/4305604/get-ip-from-dns-without-using-gethostbyname
    
    //v1.1.6.3 refactored, put in test for php version
    $php_version = phpversion();
    if ( version_compare( $php_version, '5.3.1', '<' ) ) {
      $vtprd_ip_address = $_SERVER['SERVER_ADDR'];
    } else {    
      $host = gethostname();
      $query = `nslookup -timeout=$timeout -retry=1 $host`;
      if(preg_match('/\nAddress: (.*)\n/', $query, $matches)) {
        $vtprd_ip_address =  trim($matches[1]);
      } else {
        $vtprd_ip_address = gethostbyname($host);
      } 
    }	

    
    update_option( 'vtprd_ip_address', $vtprd_ip_address );
    
    return $vtprd_ip_address;

  }
  //v1.1.5 END   
  
  
   //*************************
   // v1.09.91.2 new function
   //*************************
  function vtprd_auto_update_setting_html1 ( $html, $plugin_file, $plugin_data ) {
    if ( 'pricing-deals-for-woocommerce/vt-pricing-deals.php' 	   ===  $plugin_file ) {
        $html = __( 'Auto-updates are not available for this plugin.', 'pricing-deals-for-woocommerce' );
    }
 
    return $html;
  }
  add_filter( 'plugin_auto_update_setting_html', 'vtprd_auto_update_setting_html1', 10, 3 );

    
   //*************************
   // v1.09.91.2 new function
   //*************************
  function vtprd_auto_update_setting_html2 ( $html, $plugin_file, $plugin_data ) {
    if ( 'pricing-deals-pro-for-woocommerce/vt-pricing-deals-pro.php' 	   ===  $plugin_file ) {
        $html = __( 'Auto-updates are not available for this plugin.', 'pricing-deals-pro-for-woocommerce' );
    }
 
    return $html;
  }
  add_filter( 'plugin_auto_update_setting_html', 'vtprd_auto_update_setting_html2', 10, 3 );  

   