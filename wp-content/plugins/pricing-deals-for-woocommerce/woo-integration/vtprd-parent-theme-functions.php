<?php
  //v2.0.1.1  introduced a bug in 2.0.1.0 .  This file replaced with valid  file from v2.0.0.9 . 12/6/2019   
  //====================================
  //SHORTCODE: pricing_deal_msgs_by_rule
  //====================================
  
  //shortcode documentation here - wholestore
  //WHOLESTORE MESSAGES SHORTCODE     'vtprd_pricing_deal_store_msgs'
  /* ================================================================================= 
  => "rules" parameter is Required - Show msgs only for these rules => if not supplied, all msgs will be produced
  
   A list can be a single code [ example: rules => '123' }, or a group of codes [ example: rules => '123,456,789' }  with no spaces in the list

  As a shortcode:
    [pricing_deal_msgs_by_rule  rules="10,15,30"]
  
  As a template code with a passed variable containing the list:
    $rules="10,15,30"; //or it is a generated list 
    echo do_shortcode('[pricing_deal_msgs_by_rule rules=' .$rules. ']');
        OR
    echo do_shortcode('[pricing_deal_msgs_by_rule  rules="10,15,30"]');
   

  ====================================
  PARAMETER DEFAULTS and VALID VALUES
  ==================================== 
        rules => '123,',            //'123,456,789'     
  ================================================================================= */
  //
  add_shortcode('pricing_deal_msgs_by_rule','vtprd_pricing_deal_msgs_by_rule');   
  function vtprd_pricing_deal_msgs_by_rule($atts) {
    global $vtprd_rules_set, $post, $vtprd_setup_options, $vtprd_info;
    extract(shortcode_atts (
      array (
        rules => '',            //'123,456,789'                                      
      ), $atts));  //override default value with supplied parameters...

    //if no lists are present, then the skip tests are all there is.  Print the msg and exit.
    if ($rules <= ' ' ){ 
      return;      
    }
    
    vtprd_set_selected_timezone();


    $output = '<div class="vtprd-rule-msg-area">';
    $msg_counter = 0;
    
    $vtprd_rules_set = get_option( 'vtprd_rules_set' );
    
    $rules_array = explode(",", $rules);   //remove comma separator, make list an array  
      
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) { 

      //BEGIN skip tests      
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {
        continue;
      }      

      $rule_is_date_valid = vtprd_rule_date_validity_test($i);
      if (!$rule_is_date_valid) {
         continue;
      }  
      //IP is immediately available, check against Lifetime limits
      if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {  
        $rule_has_reached_lifetime_limit = vtprd_rule_lifetime_validity_test($i,'shortcode');
        if ($rule_has_reached_lifetime_limit) {
           continue;
        } 
        
      }
      
      //v1.0.9.3 new skipt test
      if ($vtprd_rules_set[$i]->discount_product_full_msg == $vtprd_info['default_full_msg'] ) { //v1.1.0.5
         continue;      
      }
       
      //END skip tests
      
      //INclusion test begin  -  all are implicit 'or' functions     
    
      if (in_array($vtprd_rules_set[$i]->post_id, $rules_array)) {
        $msg_counter++;
        $output .= vtprd_store_deal_msg($i);  //Print
        continue;
      }
 
       
    } //end 'for' loop
    
    if ($msg_counter == 0) {
      return;
    }

    //close owning div 
    $output .= '</div>'; 
        
    return $output;  
  }



  //====================================
  //SHORTCODE: pricing_deal_msgs_standard
  //====================================
  /* ************************************************************
  pricing_deal_msgs_standard DEFAULTS TO CART MESSAGES ONLY
  *************************************************************** */  
  //shortcode documentation here - wholestore
  //WHOLESTORE MESSAGES SHORTCODE     'vtprd_pricing_deal_msgs_standard'
  /* ================================================================================= 
  => rules is OPTIONAL - Show msgs only for these rules => if not supplied, all msgs will be produced
  
   A list can be a single code [ example: rules => '123' }, or a group of codes [ example: rules => '123,456,789' }  with no spaces in the list
  A switch can be sent to just display the whole store messages
  
  As a shortcode:
    [pricing_deal_whole_store_msgs  rules="10,15,30"]
  
  As a template code with a passed variable containing the list:
    $rules="10,15,30"; //or it is a generated list 
    echo do_shortcode('[pricing_deal_msgs_standard rules=' .$rules. ']');
        OR
    echo do_shortcode('[pricing_deal_msgs_standard  rules="10,15,30"]');
    echo do_shortcode('[pricing_deal_msgs_standard  wholestore_msgs_only="yes"  rules="10,15,30" ]');     

  ====================================
  PARAMETER DEFAULTS and VALID VALUES
  ==================================== 
        type => 'cart',            //'cart' (default) / 'catalog' / 'all' ==> "cart" msgs = cart rules type, "catalog" msgs = realtime catalog rules type        
                                       // AND (implicit)
        wholestore_msgs_only => 'no',  //'yes' / 'no' (default) 
                                       // AND [implicit]
                                       //   (  
        roles => '',          //'Administrator,Customer,Not logged in (just visiting),Member'  
                                       // OR  [implicit]
        rules => '',            //'123,456,789'     
                                       // OR  [implicit]
        product_category => '',  //'123,456,789'      only active if in this list / 'any' - if on a category page, show any msg for that category
                                      // OR  [implicit]
        plugin_category => ''   //'123,456,789'      only active if in this list 
                                      // OR  [implicit]                                              
         products => ''          //'123,456,789'    (ONLY WORKS in the LOOP, or if the Post-id is available as a passed variable ) / 'any' - if on a product page, show any msg for that product
                                       //   )       
  ================================================================================= */
  
  //******************************
  //v1.0.8.9  refactored
  //******************************
  
  //
  add_shortcode('pricing_deal_msgs_standard','vtprd_pricing_deal_msgs_standard');
  add_shortcode('pricing_deal_store_msgs','vtprd_pricing_deal_msgs_standard'); //for backwards compatability   
  function vtprd_pricing_deal_msgs_standard($atts) {
    global $vtprd_rules_set, $post, $vtprd_setup_options, $vtprd_info, $wpdb;
    
  //error_log( print_r(  'BEGIN vtprd_pricing_deal_msgs_standard, Shortcode $atts:', true ) );
  //error_log( var_export($atts, true ) );

    extract(shortcode_atts (
      array (
        'type' => 'cart',            //'cart' (default) / 'catalog' / 'all' ==> "cart" msgs = cart rules type, "catalog" msgs = realtime catalog rules type        
                                       // AND (implicit)
        'wholestore_msgs_only' => 'no',  //'yes' / 'no' (default) 
                                       // AND [implicit]
                                       //   (  
        'roles' => '',          //'Administrator,Customer,Not logged in (just visiting),Member'  
                                       // OR  [implicit]
        'rules' => '',            //'123,456,789'     
                                       // OR  [implicit]
        'product_category' => '',  //'123,456,789'    / 'any' - if on a category page, show any msg for that category   
                                        // OR  [implicit]
        'plugin_category' => '',   //'123,456,789'       
                                                // OR  [implicit]   
                                                // OR  [implicit]                                   
        'force_in_the_loop' => '',     //   //'yes' / 'no' (default)  //v1.1.0.5
        'force_in_the_loop_product' => '',     //must be a single product  //v1.1.0.5        
        // MUST BE USED WITH 'products' which MUST have a SINGLE VALUE ==> emulating being in the loop  //v1.1.0.5
                                    // and (implicit)  //v1.1.0.5                                                                                  
        'products' => '',          //'123,456,789'    (ONLY WORKS in the LOOP, or if the Post is available )   / 'any' - if on a product page, show any msg for that product
                                       //   )  
    
                                  //***********************
                                  //v2.0.0.7 begin
                                  //***********************
                                  /*
                                  ACTUALLY, this is the SAME AS:
                                                                 ***************************************
                                  'type' => 'cart',            //'cart' (default) / 'catalog' / 'all' 
                                                                 ***************************************
                                  */
        'show_all_rules_msgs' => ''                          
                                  /* allow all messages to displayed anywhere, for use in the theme on other pages
                                   *** SHOW all RULES messages by rule type ***
                                  "show_all_rules_msgs" - overrides in the loop, outside the loop, etc - ignores product, etc => just focuses on msgs
                                  Options:
                                    1. 'all_catalog_msgs_only'
                                    2. 'all_cart_msgs_only'
                                    3. 'all_msgs'
                                          
                                  Shortcode EXample:  [pricing_deal_msgs_standard  show_all_rules_msgs='all_cart_msgs_only' ] 
                                  
                                  */                                  
                                  //v2.0.0.7 end
                                  //***********************
                                                                           
      ), $atts));  //override default value with supplied parameters...

    
    
    /* //v1.1.0.5 new sample shortcode
    
        On the product page, you'd use the shortcode.
        
        On any other page, you'd use the following as an example in your theme files, to show messages by PRODUCT:
        
        $product_id = get_the_ID();
        echo do_shortcode( '[pricing_deal_msgs_standard force_in_the_loop="yes" force_in_the_loop_product="'.$product_id.'"]');

    */
    
    
 //error_log( print_r(  '*** SHORTCODE *** ' , true ) );
 //error_log( print_r(  '$type= ' .$type, true ) );
 //error_log( print_r(  '$wholestore_msgs_only= ' .$wholestore_msgs_only, true ) );
 //error_log( print_r(  '$roles= ' .$roles, true ) );
 //error_log( print_r(  '$rules= ' .$rules, true ) );
 //error_log( print_r(  '$product_category= ' .$product_category, true ) );
 //error_log( print_r(  '$plugin_category= ' .$plugin_category, true ) );
 //error_log( print_r(  '$force_in_the_loop= ' .$force_in_the_loop, true ) );
 //error_log( print_r(  '$force_in_the_loop_product= ' .$force_in_the_loop_product, true ) );

    
    vtprd_set_selected_timezone();


    $output = '<div class="vtprd-store-deal-msg-area">';
    $msg_counter = 0;


/*
$userRole = vtprd_get_current_user_role();
$userRole_name = translate_user_role( $userRole );
if ($userRole_name = "Administrator") {
    $output .= '*** STANDARD SHORTCODE Passed values, shows for ADMINISTRATOR only ' .'<br>';
    $output .= '$type= ' .$type .'<br>';
    $output .= '$wholestore_msgs_only= ' .$wholestore_msgs_only .'<br>';
    $output .= '$roles= ' .$roles .'<br>';
    $output .= '$rules= ' .$rules .'<br>';
    $output .= '$product_category= ' .$product_category .'<br>';
    $output .= '$plugin_category= ' .$plugin_category .'<br>';
    $output .= '$force_in_the_loop= ' .$force_in_the_loop .'<br>';
    $output .= '$force_in_the_loop_product= ' .$force_in_the_loop_product.'<br>';
    
    $output .= '</div>';
        
    return $output;  
}
*/


    
    $vtprd_rules_set = get_option( 'vtprd_rules_set' );
    
    //v1.1.0.5 begin
    if ($force_in_the_loop_product > '') {
      $post->ID = $force_in_the_loop_product;
 //error_log( print_r(  '$post->ID = ' .$post->ID , true ) );      
    }

            
    //***********************
    //v2.0.0 begin
    //***********************
    //v2.0.0.7 added $show_all_rules_msgs
    if ( ( ( (in_the_loop() ) || 
         ($force_in_the_loop_product > '') ) 
              &&
         ($product_category <= ' ') &&
         ($plugin_category  <= ' ') &&
         ($wholestore_msgs_only != 'yes') &&
         ($roles <= ' ') ) 
              ||
         ($show_all_rules_msgs > '') )     { 

      
      global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info;
      
      //new "class VTPRD_Rule_Messaging extends VTPRD_Apply_Rules" at bottom of apply-rules file
      //------------------------------------------------------------
      $vtprd_apply_rule_messaging = new VTPRD_Apply_Rule_Messaging;
      
      //load the fake cart with the current product
      vtprd_rule_messaging_load_dummy_cart($post->ID);
      $k = 0; //always only test 1the product passed in the loop
      
      $loop_msgs_array = array();
      
      if ($rules > ' ') {
        $rules_array = explode(",", $rules);   //remove comma separator, make list an array
      }

             //error_log( print_r(  'BEgin SHORTCODE main rule loop ', true ) ); 

      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) {
       
       //error_log( print_r(  'For ruleID= ' .$vtprd_rules_set[$i]->post_id.  ' discount_product_full_msg= ' .$vtprd_rules_set[$i]->discount_product_full_msg, true ) ); 
           
          //is there a message to show?
          if ($vtprd_rules_set[$i]->discount_product_full_msg <= ' ') {
             continue;          
          }
          
          
          //v2.0.0.2 begin
          if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {
     //error_log( print_r(  'shortcode skip 001 ', true ) );        
            continue;
          }      
    
          $rule_is_date_valid = vtprd_rule_date_validity_test($i);
          if (!$rule_is_date_valid) {
     //error_log( print_r(  'shortcode skip 002 ', true ) );
             continue;
          }  
          //IP is immediately available, check against Lifetime limits
          if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {  
            $rule_has_reached_lifetime_limit = vtprd_rule_lifetime_validity_test($i,'shortcode');
            if ($rule_has_reached_lifetime_limit) {
     //error_log( print_r(  'shortcode skip 003 ', true ) );        
               continue;
            }
          }         
          //v2.0.0.2 end
          
          //v2.0.0.7 begin - reformatted to accommodate $show_all_rules_msgs                   
          //are we on a correct rule type
          $exit_stage_left = 'no';
          
          if ($show_all_rules_msgs > '') {
          
            if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
              if ($show_all_rules_msgs == 'all_catalog_msgs_only') {
                $exit_stage_left = 'yes';
              }
            } else {
              if ($show_all_rules_msgs == 'all_cart_msgs_only') {
                $exit_stage_left = 'yes';
              }            
            }
            
          } else {
          
            switch( $type ) {
              case 'cart':
                if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
                  $exit_stage_left = 'yes';
                   //error_log( print_r(  'shortcode skip 004 ', true ) );            
                }
                break;
              case 'catalog':                                                                                   
                if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
                  $exit_stage_left = 'yes';
                   //error_log( print_r(  'shortcode skip 005 ', true ) );
                }  
                break;
              default:
                break; 
            }
             
          }
             
          if ($exit_stage_left == 'yes') {
              //error_log( print_r(  'exit stage left EXECUTED ', true ) );
             continue;
          }
          
          //if rules specified in the loop, and this is not one of them, skip
          if ($rules > ' ') {
            if (!in_array($vtprd_rules_set[$i]->post_id, $rules_array)) {
               //error_log( print_r(  'shortcode skip for rule array not found ', true ) );
              continue;
            }
          }
          
          //****************       //error_log( print_r(  'For ruleID= ' .$vtprd_rules_set[$i]->post_id.  ' discount_product_full_msg= ' .$vtprd_rules_set[$i]->discount_product_full_msg, true ) );
          //v2.0.0.7 begin
          //v2.0.2.01 beg - RECODED
          //error_log( print_r(  'above loop to load msgs into array For ruleID= ' .$vtprd_rules_set[$i]->post_id ,  true ) );
          if ($show_all_rules_msgs > '') {
            //error_log( print_r(  'SHOW ALL MSGS, msg loaded ', true ) );
            $loop_msgs_array[] = $i;
          } else {
                                
            $vtprd_apply_rule_messaging->vtprd_manage_customer_rule_tests($i); //v2.0.2.01
            
            if ($vtprd_apply_rule_messaging->vtprd_test_if_inPop_product($i, $k)) {
               //error_log( print_r(  'If inpop found, msg loaded ', true ) );
               $loop_msgs_array[] = $i;                
            } else {
            $inPop_found = FALSE;
            if ($vtprd_apply_rule_messaging->vtprd_test_if_actionPop_product($i, $k, $inPop_found)) {
               //error_log( print_r(  'If actionnpop found, msg loaded ', true ) );
               $loop_msgs_array[] = $i;                
            }              
            
            /*
            //TEST TEST TEST
            else {
                if (!$vtprd_apply_rule_messaging->vtprd_is_product_in_inPop_group($i, $k)) {
                  //error_log( print_r(  'IS NOT IN INPOP For ruleID= ' .$vtprd_rules_set[$i]->post_id  , true ) );
                }
                if (!$vtprd_apply_rule_messaging->vtprd_is_product_in_actionPop_group($i, $k)) {
                  //error_log( print_r(  'IS NOT IN ACTIONPOP For ruleID= ' .$vtprd_rules_set[$i]->post_id  , true ) );
                }
            }
            //TEST TEST TEST 
            */ 
            }      
          }
          //v2.0.2.01 end
          //v2.0.0.7 end                          
      } //end rule set processing
      
      $sizeof_loop_msgs_array = sizeof($loop_msgs_array);
      if ($sizeof_loop_msgs_array > 0) {
               //error_log( print_r(  'Message found, outputting ', true ) );      
        $output = '<div class="vtprd-rule-msg-area">';
        $lineSkip = '';
        for($p=0; $p < $sizeof_loop_msgs_array ; $p++) {
          $rule_ind_val = $loop_msgs_array[$p]; 
          //v2.0.0.7 begin
          if ($p > 0) {
            $lineSkip = '<br>';
          }
          $output .= $lineSkip. vtprd_category_deal_msg($rule_ind_val);
          //v2.0.0.7 end
        } 
        $output .= '</div>';
        return $output; 
      }

               //error_log( print_r(  'Message NOT found ', true ) ); 
               //error_log( print_r(  '  ', true ) );
               //error_log( print_r(  '  ', true ) );
      
      return;     
    } //end IF
    //v2.0.0 end
    
    
    //Only do this once!!!
    if ( (in_the_loop() ) ||
        // ($force_in_the_loop == 'yes') ) { 
         ($force_in_the_loop_product > '') ) {
     $loop_msgs_array = array();
     //v1.1.0.5 end  
       
      $prod_cat_list = wp_get_object_terms( $post->ID, $vtprd_info['parent_plugin_taxonomy'], $args = array('fields' => 'ids') );
      $rule_cat_list = wp_get_object_terms( $post->ID, $vtprd_info['rulecat_taxonomy'], $args = array('fields' => 'ids') ); 
 //error_log( print_r(  '$prod_cat_list= ' , true ) );
 //error_log( var_export($prod_cat_list, true ) );
 //error_log( print_r(  '$rule_cat_list= ' , true ) );
 //error_log( var_export($rule_cat_list, true ) );
    }
 
    if ($product_category > ' ') {
      $product_category_array = explode(",", $product_category); 
      $product_category_msgs_array = array();
    } else {
      $product_category_array = array();
    }
    if ($rules > ' ') {
      $rules_array = explode(",", $rules);   //remove comma separator, make list an array
      $rules_msgs_array = array();
    } else {
      $rules_array = array();
    }
    if ($plugin_category > ' ') {
      $plugin_category_array = explode(",", $plugin_category);   //remove comma separator, make list an array
      $plugin_category_msgs_array = array();    
     } else {
      $plugin_category_array = array();
    }
    if ( $products > ' ' ) {                                                                                   
      $products_array = explode(",", $products);   //remove comma separator, make list an array
      $products_msgs_array = array();
     } else {
      $products_array = array();
    }
    
                    
       
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) { 
 //error_log( print_r(  '$i= ' . $i . ' rule_id= ' .$vtprd_rules_set[$i]->post_id, true ) );
      //BEGIN skip tests 
      
      //v2.0.0 begin    
      //is there a message to show?
      if ($vtprd_rules_set[$i]->discount_product_full_msg <= ' ') {
         continue;          
      }
      //v2.0.0 end
                 
           
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {
 //error_log( print_r(  'shortcode skip 001 ', true ) );        
        continue;
      }      

      $rule_is_date_valid = vtprd_rule_date_validity_test($i);
      if (!$rule_is_date_valid) {
 //error_log( print_r(  'shortcode skip 002 ', true ) );
         continue;
      }  
      //IP is immediately available, check against Lifetime limits
      if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {  
        $rule_has_reached_lifetime_limit = vtprd_rule_lifetime_validity_test($i,'shortcode');
        if ($rule_has_reached_lifetime_limit) {
 //error_log( print_r(  'shortcode skip 003 ', true ) );        
           continue;
        }
      }

      $exit_stage_left = 'no';
      switch( $type ) {
        case 'cart':
          if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
            $exit_stage_left = 'yes';
 //error_log( print_r(  'shortcode skip 004 ', true ) );            
          }
          break;
        case 'catalog':                                                                                   
          if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
            $exit_stage_left = 'yes';
 //error_log( print_r(  'shortcode skip 005 ', true ) );
          }  
          break;
        default:
          break; 
      }     
      if ($exit_stage_left == 'yes') {
         continue;
      }      
    
      if ($wholestore_msgs_only == 'yes') {
        if ( ($vtprd_rules_set[$i]->inPop != 'wholeStore') && ($vtprd_rules_set[$i]->actionPop != 'wholeStore' ) ) {
 //error_log( print_r(  'shortcode skip 006 ', true ) );          
          continue;
        }
      } 
      
      if ($roles > ' ') {
        $userRole = vtprd_get_current_user_role();
        //mwn04142014
        if ($userRole =  'notLoggedIn') { 
          $userRole_name = 'Not logged in (just visiting)'; 
        } else {
          $userRole_name = translate_user_role( $userRole );
        }
 //error_log( print_r(  'current user role=  ' .$userRole_name, true ) );        
        $roles_array = explode(",", $roles);   //remove comma separator, make list an array
        if (!in_array($userRole_name, $roles_array)) {
 //error_log( print_r(  'shortcode skip 007 ', true ) );        
          continue;
        }
      }

      //v1.0.9.3 new skipt test
      if ($vtprd_rules_set[$i]->discount_product_full_msg == $vtprd_info['default_full_msg']   ) { //v1.1.0.5
 //error_log( print_r(  'shortcode skip 008 ', true ) );
         continue;      
      }
            
      //END skip tests
      
      //*************************
      //INclusion test begin  -  all are implicit 'or' functions  
      //*************************   
 //error_log( print_r(  'shortcode ABove001 ', true ) );       
      //if no lists are present, then the skip tests are all there is.  Print the msg and exit.
      if (($rules <= ' ' ) && 
          ($roles <= ' ' ) &&  //v1.1.0.5      
          ($products <= ' ') &&
          ($product_category <= ' ')  &&
          ($plugin_category <= ' ')  &&
          ($force_in_the_loop != 'yes') &&
          (!in_the_loop() ) ) {  //v1.1.1
        $msg_counter++;
        $output .= vtprd_store_deal_msg($i);  //Print
 //error_log( print_r(  'shortcode PRINT Msg 001 ', true ) );        
        continue;      
      }
      
      if ($rules > ' ') {
        if (in_array($vtprd_rules_set[$i]->post_id, $rules_array)) {
          $msg_counter++;
          $rules_msgs_array[] =$i;
 //error_log( print_r(  'shortcode PRINT Msg 002 ', true ) ); 
          continue;
        }
      } 
      
      //*******************************
      //one set of tests for in_the_loop, one for outside
      //*******************************
      //v1.1.0.5 begin
 //error_log( print_r(  'shortcode ABove002 ', true ) ); 
    if ( (in_the_loop() ) ||
         ($force_in_the_loop == 'yes') ) {
 //error_log( print_r(  'IN THE LOOP ', true ) );       
              /*
              $product_category_array                   =  categories passed in with shortcode
              $product_cat_list                         =  categories selected in product
              $vtprd_rules_set[$i]->prodcat_XX_checked  =  categories selected in RULE
              */  
       //v1.1.0.5 end 
               
          if ($product_category > ' ') {
            if ( ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'],  $product_category_array ) ) ||  //v2.0.0
                   ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'], $product_category_array ) ) )   //v2.0.0
                    && 
                   ( array_intersect($prod_cat_list,  $product_category_array ) ) ) {                     
                $msg_counter++;
                $product_category_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 003 ', true ) );                
                continue; //only output the msg once 
            }
          } else {  //v1.1.0.5 begin
            //if RULE list intersects with PRODUCT participation list
            if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'],  $prod_cat_list ) ) || //v2.0.0
                 ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'], $prod_cat_list ) ) ) { //v2.0.0
                $msg_counter++;
                $loop_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 003a ', true ) ); 
                continue; //only output the msg once  
            } 
          }
           //v1.1.0.5 end
          
          if ($plugin_category > ' ') {
            if ( ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'],  $plugin_category_array ) ) || //v2.0.0
                   ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'], $plugin_category_array ) ) )  //v2.0.0
                    &&
                   ( array_intersect($rule_cat_list,  $plugin_category_array ) ) ) {                      
                $msg_counter++;
                $plugin_category_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 004 ', true ) );                
                continue; //only output the msg once 
            }
          } else {  //v1.1.0.5 begin
            //if RULE list intersects with PRODUCT participation list
            if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'],  $rule_cat_list ) ) || //v2.0.0
                 ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'], $rule_cat_list ) ) ) { //v2.0.0
                $msg_counter++;
                $loop_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 004a ', true ) );
                continue; //only output the msg once  
            } 
          } 
            //v1.1.0.5 end    
          
          //v1.1.1 begin
          
          //**  replicated the if below.  here, it's just the force
          if ( ( $products > ' ' ) && ($force_in_the_loop == 'yes') ) { 
            if ( (in_array($post->ID, $products_array))
                    &&              
                 ((in_array($post->ID, $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'])) || //v2.0.0
                  (in_array($post->ID, $vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'])))  ) { //v2.0.0
              $msg_counter++;
              $products_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 005 ', true ) );              
              continue; //only output the msg once 
            }      
          }
        
          
 //error_log( print_r(  'above test for single prod id =  $post->ID= ' .$post->ID, true ) );

  
          //**  replicatd from above - if only in_the_loop, no other test other than agreement.
          if   ( (in_the_loop() ) &&
                 ((in_array($post->ID, $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'])) || //v2.0.0
                  (in_array($post->ID, $vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'])))  ) { //v2.0.0
            $msg_counter++;
            $loop_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 005 ', true ) );              
            continue; //only output the msg once 
          }
          //**  replicatd from above - if only in_the_loop, no other test other than agreement.
          if   ( (in_the_loop() ) &&
                (($vtprd_rules_set[$i]->inPop     ==  'wholeStore' ) ||
                 ($vtprd_rules_set[$i]->actionPop ==  'wholeStore' )) ) {
            $msg_counter++;
            $loop_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 005 ', true ) );              
            continue; //only output the msg once 
          }          
          //v1.1.1 end      
         
 //error_log( print_r(  'shortcode NO MESSAGE 001 ', true ) );          
      } else {  //************************************************
                //*****  NOT IN THE LOOP from here on **********
                //************************************************
 //error_log( print_r(  'shortcode NOT IN THE LOOP ', true ) ); 
          if ($product_category > ' ') {
            if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'],  $product_category_array ) ) || //v2.0.0
                 ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'], $product_category_array ) ) ) {   //v2.0.0
               $msg_counter++;
               $product_category_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 006 ', true ) );               
                continue; //only output the msg once 
            }
          } 
    
          if ($plugin_category > ' ') {
            if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'],  $plugin_category_array ) ) || //v2.0.0
                 ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'], $plugin_category_array ) ) ) {   //v2.0.0
               $msg_counter++;
               $plugin_category_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 007 ', true ) );               
                continue; //only output the msg once 
            }
          }  
          
          if ( $products > ' ' ) { 
            if   ( (in_array($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'], $products_array)) || //v2.0.0
                   (in_array($vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'] , $products_array)) ) { //v2.0.0
              $msg_counter++;
              $products_msgs_array[] = $i;
 //error_log( print_r(  'shortcode PRINT Msg 008 ', true ) );              
              continue; //only output the msg once 
            }             
          }
 //error_log( print_r(  'shortcode NO MESSAGE 002 ', true ) ); 
              
      } //if (in_the_loop()  end
    

 //error_log( print_r(  '$vtprd_rules_set[$i], $i= ' . $i, true ) );
 //error_log( var_export($vtprd_rules_set[$i], true ) );
      
  
      //PRINT test end     
       
    } //end 'for' loop


    
    if ($msg_counter == 0) {
 //error_log( print_r(  'shortcode NO MESSAGEs by counter ', true ) );     
      return;
    }

 //error_log( print_r(  '$product_category_array', true ) );
 //error_log( var_export($product_category_array, true ) );
 //error_log( print_r(  '$product_category_msgs_array', true ) );
 //error_log( var_export($product_category_msgs_array, true ) );

      /*
      *******************************
      OUTPUT MESSAGES IN SORT ORDER
      *******************************
     msgs_array holds the list of rules matching the criteria
     - spin through the input criteria list (cat, prods)
     - spin through msgs array, looking for **individual** input criteria, in order
     
      msg sort hierarchy;
      -by rule ID list
      -by product_category list
      -by plugin_category list
      -by product list   
      */
      switch( true ) {
        case ($rules > ' '):
 //error_log( print_r(  'printing based on $rules', true ) );         
            //*  Rules list already access the rules directly, no 2nd lookiup required
            $sizeof_rules_array = sizeof($rules_array);
            $sizeof_rules_msgs_array = sizeof($rules_msgs_array);
            for($p=0; $p < $sizeof_rules_array; $p++) {
                $rules_array_ruleID = $rules_array[$p];
                for($a=0; $a < $sizeof_rules_msgs_array; $a++) {
                    $rule_ind_val = $rules_msgs_array [$a] ;
                    if ( $rules_array_ruleID == $vtprd_rules_set[$rule_ind_val]->post_id ) {                          
                        $output .= vtprd_category_deal_msg($rule_ind_val);
                    }
                }                
            }
          break;
          
        case ($product_category > ' '): 
 //error_log( print_r(  'printing based on $product_category', true ) );                                                                                          
            $sizeof_product_category_array = sizeof($product_category_array);
            $sizeof_product_category_msgs_array = sizeof($product_category_msgs_array);
            for($p=0; $p < $sizeof_product_category_array; $p++) {
                $cat = $product_category_array[$p];
                for($a=0; $a < $sizeof_product_category_msgs_array; $a++) {
                    $rule_ind_val = $product_category_msgs_array[$a];
 //error_log( print_r(  '$cat= ' .$cat, true ) ); 
 //error_log( print_r(  '$rule_ind_val= ' .$rule_ind_val, true ) );                    
                    
                    if ( ( in_array($cat, $vtprd_rules_set[$rule_ind_val]->buy_group_population_info['buy_group_prod_cat_incl_array']) ) || //v2.0.0
                         ( in_array($cat, $vtprd_rules_set[$rule_ind_val]->action_group_population_info['action_group_prod_cat_incl_array']) ) ) {    //v2.0.0                       
                        $output .= vtprd_category_deal_msg($rule_ind_val);
                    }
                }
            }   
          break;
          
        case ($plugin_category > ' '):  
 //error_log( print_r(  'printing based on $plugin_category', true ) );                                                                                         
            $sizeof_plugin_category_array = sizeof($plugin_category_array);
            $sizeof_plugin_category_msgs_array = sizeof($plugin_category_msgs_array);
            for($p=0; $p < $sizeof_plugin_category_array; $p++) {
                $cat = $plugin_category_array[$p];
                for($a=0; $a < $sizeof_plugin_category_msgs_array; $a++) {
                    $rule_ind_val = $plugin_category_msgs_array[$a];
                    if ( ( in_array($cat, $vtprd_rules_set[$rule_ind_val]->buy_group_population_info['buy_group_plugin_cat_incl_array']) ) || //v2.0.0
                         ( in_array($cat, $vtprd_rules_set[$rule_ind_val]->action_group_population_info['action_group_plugin_cat_incl_array']) ) ) {    //v2.0.0                      
                        $output .= vtprd_category_deal_msg($rule_ind_val);
                    }
                }
            } 
          break;
          
        case ( $products > ' ' ): 
 //error_log( print_r(  'printing based on $products', true ) );                                                                                            
            $sizeof_products_array = sizeof($products_array); //v1.1.5 
            $sizeof_products_msgs_array = sizeof($products_msgs_array);
            for($p=0; $p < $sizeof_products_array; $p++) {
                $prod = $products_array[$p];
                for($a=0; $a < $sizeof_products_msgs_array; $a++) {
                    $rule_ind_val = $products_msgs_array[$a];
                    if ( ((in_array($prod, $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'])) || //v2.0.0
                          (in_array($prod, $vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'])))  ) {   //v2.0.0
                        $output .= vtprd_category_deal_msg($rule_ind_val);
                    }
                }
            }     
          break;  
          
        //v1.1.0.5 begin 
        default:  
 //error_log( print_r(  'printing based on default path', true ) ); 
            if ( (in_the_loop() ) ||
               ($force_in_the_loop == 'yes') ) {                                                                                         
              $sizeof_loop_msgs_array = sizeof($loop_msgs_array);
              for($p=0; $p < $sizeof_loop_msgs_array ; $p++) {
                $rule_ind_val = $loop_msgs_array[$p]; 
                $output .= vtprd_category_deal_msg($rule_ind_val);
              } 
             }    
          break;                    
        //v1.1.0.5 end  
            
      }


    //close owning div 
    $output .= '</div>';

 //error_log( print_r(  '$output = ' .$output, true ) );
        
    return $output;  
  }
  
  function vtprd_store_deal_msg($i) {
    global $vtprd_rules_set;
    $output  = '<span class="vtprd-store-deal-msg" id="vtprd-store-deal-msg-' . $vtprd_rules_set[$i]->post_id . '">';
    $output .= stripslashes($vtprd_rules_set[$i]->discount_product_full_msg);
    $output .= '</span>';
    $output .= '<span class="vtprd-line-skip-with-display-block"></span>';
    return $output;
  }
 //====================================
 //SHORTCODE: pricing_deal_msgs_by_category
 //==================================== 
 
 //shortcode documentation here - category
 //STORE CATEGORY MESSAGES SHORTCODE    vtprd_pricing_deal_msgs_by_category
  /* ================================================================================= 
  => either prodcat_id_list or rulecat_id_list or rules is REQUIRED
  => if both lists supplied, the shortcode will find rule msgs in EITHER prodcat_id_list OR rulecat_id_list OR rules.
  
        A list can be a single code [ example: rules => '123' }, or a group of codes [ example: rules => '123,456,789' }  with no spaces in the list 
        
        REQUIRED => Data MUST be sent in ONE of the list parameters, or nothing is returned.
        
  As a shortcode:
    [pricing_deal_msgs_by_category  prodcat_id_list="10,15,30"  rulecat_id_list="12,17,32"]
  
  As a template code with a passed variable containing the list:
    to show only the current category messages, for example:
    GET CURRENT CATEGORY 

    if (is_category()) {
      $prodcat_id_list = get_query_var('cat');
      echo do_shortcode('[pricing_deal_msgs_by_category  prodcat_id_list=' .$prodcat_id_list. ']');
    }
        OR 
    USING A HARDCODED CAT LIST   
    echo do_shortcode('[pricing_deal_msgs_by_category  prodcat_id_list="10,15,30" ]');

  ====================================
  PARAMETER DEFAULTS and VALID VALUES
  ====================================
          type => 'cart',     //'cart' (default) / 'catalog' / 'all' ==> "cart" msgs = cart rules type, "catalog" msgs = realtime catalog rules type 
                                // AND [implicit]                                               
                                //   ( 
        product_category => '',  //'123,456,789'      only active if in this list
                                // OR  [implicit]
        plugin_category => ''   //'123,456,789'      only active if in this list
                                //   )                      
  ================================================================================= */
  add_shortcode('pricing_deal_msgs_by_category','vtprd_pricing_deal_msgs_by_category');   
  add_shortcode('pricing_deal_category_msgs','vtprd_pricing_deal_msgs_by_category');  
  function vtprd_pricing_deal_msgs_by_category($atts) {
    global $vtprd_rules_set, $vtprd_setup_options, $vtprd_info;
    extract(shortcode_atts (
      array (
        'type' => 'cart',     //'cart' (default) / 'catalog' / 'all' ==> "cart" msgs = cart rules type, "catalog" msgs = realtime catalog rules type 
                                // AND [implicit]                                               
                                //   ( 
        'product_category' => '',  //'123,456,789'      only active if in this list
                                // OR  [implicit]
        'plugin_category' => ''   //'123,456,789'      only active if in this list
                                //   ) 
      ), $atts));               
    
    vtprd_set_selected_timezone();
    
    if ( ($product_category <= ' ') && ($plugin_category <= ' ') && ($rules <= ' ') ) {   //MUST supply one or the other
       return;
    }
    
    $vtprd_rules_set = get_option( 'vtprd_rules_set' );

    $output = '<div class="vtprd-category-deal-msg-area">';
    $msg_counter = 0;
    
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) { 

      //v2.0.0 begin    
      //is there a message to show?
      if ($vtprd_rules_set[$i]->discount_product_full_msg <= ' ') {
         continue;          
      }
      //v2.0.0 end

      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {
        continue;
      }      
      
      $rule_is_date_valid = vtprd_rule_date_validity_test($i);
      if (!$rule_is_date_valid) {
         continue;
      }  
      if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {
      //IP is immediately available, check against Lifetime limits
        $rule_has_reached_lifetime_limit = vtprd_rule_lifetime_validity_test($i,'shortcode');
        if ($rule_has_reached_lifetime_limit) {
           continue;
        }
      }
      
      //v1.0.9.3 new skipt test
      if ($vtprd_rules_set[$i]->discount_product_full_msg == $vtprd_info['default_full_msg']  ) { //v1.1.0.5
         continue;      
      }
       
      $exit_stage_left = 'no';
      switch( $type ) {
        case 'cart':
          if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
            $exit_stage_left = 'yes';
          }
          break;
        case 'catalog':                                                                                   
          if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
            $exit_stage_left = 'yes';
          }  
          break;
        case 'all':
          break; 
      }     
      if ($exit_stage_left == 'yes') {
         continue;
      }      

      //the rest are implied 'or' relationships


      if ($product_category > ' ') {
        $product_category_array = explode(",", $product_category);   //remove comma separator, make list an array
        if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'],  $product_category_array ) ) || //v2.0.0
             ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'], $product_category_array ) ) ) {   //v2.0.0
           $msg_counter++;
           $output .= vtprd_category_deal_msg($i);
            continue; //only output the msg once 
        }
      } 

      if ($plugin_category > ' ') {
        $plugin_category_array = explode(",", $plugin_category);   //remove comma separator, make list an array
        if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'],  $plugin_category_array ) ) || //v2.0.0
             ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'], $plugin_category_array ) ) ) {   //v2.0.0
           $msg_counter++;
           $output .= vtprd_category_deal_msg($i);
            continue; //only output the msg once 
        }
      }
      
    }
    
    if ($msg_counter == 0) {
      return;
    }

    //close owning div 
    $output .= '</div>';  
 //   vtprd_enqueue_front_end_css();
    
    return $output;  
  }
  
  function vtprd_category_deal_msg($i) {
    global $vtprd_rules_set;
    $output  = '<span class="vtprd-category-deal-msg" id="vtprd-category-deal-msg-' . $vtprd_rules_set[$i]->post_id . '">';
    $output .= stripslashes($vtprd_rules_set[$i]->discount_product_full_msg);
    $output .= '</span>';
    $output .= '<span class="vtprd-line-skip-with-display-block"></span>';
    return $output;
  }
//====================================
 //SHORTCODE: pricing_deal_msgs_advanced
 //==================================== 
 
 //shortcode documentation here - advanced
 //ADVANCED MESSAGES SHORTCODE    vtprd_pricing_deal_msgs_advanced
  /* ================================================================================= 
   
        A list can be a single code [ example: rules => '123' }, or a group of codes [ example: rules => '123,456,789' }  with no spaces in the list 
        
        NB - please be careful to follow the comma use exactly as described!!!  
        
  As a shortcode:
    [pricing_deal_msgs_advanced  
        group1_type => 'cart'
        group1_and_or_wholestore_msgs_only => 'and'
        group1_wholestore_msgs_only => 'no'
          and_or_group1_to_group2 => 'and'
        group2_rules => ''
        group2_and_or_roles => 'and'
        group2_roles => ''
          and_or_group2_to_group3 => 'and'
        group3_product_category => ''
        group3_and_or_plugin_category => 'or'
        group3_plugin_category => ''   
    ]
  
  As a template code with passed variablea
    echo do_shortcode('[pricing_deal_msgs_advanced  
        group1_type => 'cart'
        group1_and_or_wholestore_msgs_only => 'and'
        group1_wholestore_msgs_only => 'no'
          and_or_group1_to_group2 => 'and'
        group2_rules => ''
        group2_and_or_roles => 'and'
        group2_roles => ''
          and_or_group2_to_group3 => 'and'
        group3_product_category => ''
        group3_and_or_plugin_category => 'or'
        group3_plugin_category => '' 
    ]');
  
  ====================================
  PARAMETER DEFAULTS and VALID VALUES
  ====================================
                                                    //   (  group 1
        group1_type => 'cart',                   //'cart' (default) / 'catalog' / 'all' ==> "cart" msgs = cart rules type, "catalog" msgs = realtime catalog rules type  
        group1_and_or_wholestore_msgs_only => 'and', //'and'(default) / 'or' 
        group1_wholestore_msgs_only => 'no',         //'yes' / 'no' (default)   only active if rule active for whole store
                                                   //   )
        and_or_group1_to_group2 => 'and',              //'and'(default) / 'or' 
                                                   //   (  group 2
        group2_rules => '',                   //'123,456,789'          only active if in this list
        group2_and_or_roles => 'and',       //'and'(default) / 'or' 
        group2_roles => '',                 //'Administrator,Customer,Not logged in (just visiting),Member'         Only active if in this list 
                                                   //   )
        and_or_group2_to_group3 => 'and',              //'and'(default) / 'or' 
                                                   //   (  group 3
        group3_product_category => '',                //'123,456,789'      only active if in this list
        group3_and_or_plugin_category => 'or',       //'and' / 'or'(default) 
        group3_plugin_category => ''                 //'123,456,789'      only active if in this list
                                                   //   )   
  ================================================================================= */
  add_shortcode('pricing_deal_msgs_advanced','vtprd_pricing_deal_msgs_advanced'); 
  add_shortcode('pricing_deal_advanced_msgs','vtprd_pricing_deal_msgs_advanced');  //for backwards compatability  
  function vtprd_pricing_deal_msgs_advanced($atts) {
    global $vtprd_rules_set, $vtprd_setup_options, $vtprd_info;
    extract(shortcode_atts (
      array (
                                                   //   (  group 1
        'group1_type' => 'cart',                   //'cart' (default) / 'catalog' / 'all' ==> "cart" msgs = cart rules type, "catalog" msgs = realtime catalog rules type  
        'group1_and_or_wholestore_msgs_only' => 'and', //'and'(default) / 'or' 
        'group1_wholestore_msgs_only' => 'no',         //'yes' / 'no' (default)   only active if rule active for whole store
                                                   //   )
        'and_or_group1_to_group2' => 'and',              //'and'(default) / 'or' 
                                                   //   (  group 2
        'group2_rules' => '',                   //'123,456,789'          only active if in this list
        'group2_and_or_roles' => 'and',       //'and'(default) / 'or' 
        'group2_roles' => '',                 //'Administrator,Customer,Not logged in (just visiting),Member'         Only active if in this list 
                                                   //   )
        'and_or_group2_to_group3' => 'and',              //'and'(default) / 'or' 
                                                   //   (  group 3
        'group3_product_category' => '',                //'123,456,789'      only active if in this list
        'group3_and_or_plugin_category' => 'or',       //'and' / 'or'(default) 
        'group3_plugin_category' => ''                 //'123,456,789'      only active if in this list
                                                   //   )
      ), $atts));  //override default value with supplied parameters...
    
    vtprd_set_selected_timezone();

    $vtprd_rules_set = get_option( 'vtprd_rules_set' );

    $output = '<div class="vtprd-advanced-deal-msg-area">';
    $msg_counter = 0;
//echo 'incoming attributes= ' .$atts. '<br>'; //mwnt 
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) { 
      
      //v2.0.0 begin    
      //is there a message to show?
      if ($vtprd_rules_set[$i]->discount_product_full_msg <= ' ') {
         continue;          
      }
      //v2.0.0 end
            
      if ( $vtprd_rules_set[$i]->rule_status != 'publish' ) {
        continue;
      }      
            
      $rule_is_date_valid = vtprd_rule_date_validity_test($i);
      if (!$rule_is_date_valid) {
         continue;
      }  
      if ( (defined('VTPRD_PRO_DIRNAME')) && ($vtprd_setup_options['use_lifetime_max_limits'] == 'yes') )  {
      //IP is immediately available, check against Lifetime limits
        $rule_has_reached_lifetime_limit = vtprd_rule_lifetime_validity_test($i,'shortcode');
        if ($rule_has_reached_lifetime_limit) {
           continue;
        }
      }
      
      //v1.0.9.3 new skipt test
      if ($vtprd_rules_set[$i]->discount_product_full_msg == $vtprd_info['default_full_msg'] ) { //v1.1.0.5
         continue;      
      }
             
      $status =       array (
        'group1_type' => '',                  
        'group1_wholestore_msgs_only' => '',           
        'group2_rules' => '',                        
        'group2_roles' => '',                       
        'group3_product_category' => '',                
        'group3_plugin_category' => '',
        'group1' => '',
        'group2' => '',
        'group3' => '',
        'total' => ''                 
      );
      
      //SET Status success/failed for each parameter
      switch( $group1_type ) {
        case 'cart':      
          if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
            $status['group1_type'] = 'failed';      
          } else {
            $status['group1_type'] = 'success';      
          }
          break;
        case 'catalog':                                                                                          
          if ($vtprd_rules_set[$i]->rule_execution_type == 'cart') {
            $status['group1_type'] = 'failed';          
          } else {
            $status['group1_type'] = 'success';         
          } 
          break;
        case 'all':
          $status['group1_type'] = 'success';
          break;
        default:
          $status['group1_type'] = 'failed';
          break; 
      }     

      if ($group1_wholestore_msgs_only == 'yes') {
        if ( ($vtprd_rules_set[$i]->inPop == 'wholeStore') || ($vtprd_rules_set[$i]->actionPop == 'wholeStore' ) ) {
          $status['group1_wholestore_msgs_only'] = 'success';
        } else {
          $status['group1_wholestore_msgs_only'] = 'failed';
        }
      } else {
        $status['group1_wholestore_msgs_only'] = 'success';
      }
            
      if ($group2_roles > ' ') {
        $userRole = vtprd_get_current_user_role();
        //mwn04142014
        if ($userRole =  'notLoggedIn') { 
          $userRole_name = 'Not logged in (just visiting)'; 
        } else {
          $userRole_name = translate_user_role( $userRole );
        }

        $group2_roles_array = explode(",", $group2_roles);   //remove comma separator, make list an array
        if (in_array($userRole_name, $group2_roles_array)) {
          $status['group2_roles'] = 'success';
        } else {
          $status['group2_roles'] = 'failed';
        }
      } else {
        $status['group2_roles'] = 'success';
      }

      if ($group2_rules > ' ') {
        $group2_rules_array = explode(",", $group2_rules);   //remove comma separator, make list an array
        if (in_array($vtprd_rules_set[$i]->post_id, $group2_rules_array)) {
          $status['group2_rules'] = 'success';
        } else {
          $status['group2_rules'] = 'failed';
        }
      } else {
        $status['group2_rules'] = 'success';
      }

      if ($group3_product_category > ' ') {
        $group3_product_category_array = explode(",", $group3_product_category);   //remove comma separator, make list an array
        if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'],  $group3_product_category_array ) ) || //v2.0.0
             ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'], $group3_product_category_array ) ) ) {   //v2.0.0
           $status['group3_product_category'] = 'success'; 
        } else {
           $status['group3_product_category'] = 'failed'; 
        }
      } else {
        $status['group3_product_category'] = 'success';
      }

      if ($group3_plugin_category > ' ') {
        $group3_plugin_category_array = explode(",", $group3_plugin_category);   //remove comma separator, make list an array
        if ( ( array_intersect($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'],  $group3_plugin_category_array ) ) || //v2.0.0
             ( array_intersect($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'], $group3_plugin_category_array ) ) ) {   //v2.0.0
           $status['group3_plugin_category'] = 'success'; 
        } else {
           $status['group3_plugin_category'] = 'failed'; 
        }
      } else {
        $status['group3_plugin_category'] = 'success';
      }
      
      //Evaluate status settings

      //evaluate group1
      switch( $group1_and_or_wholestore_msgs_only ) {
        case 'and':        
            if (($status['group1_type'] == 'success') &&
                ($status['group1_wholestore_msgs_only'] == 'success')) {
              $status['group1'] = 'success';
            } else {
              $status['group1'] = 'failed';
            }            
          break;
        case 'or':
            if (($status['group1_type'] == 'success') ||
                ($status['group1_wholestore_msgs_only'] == 'success')) {
              $status['group1'] = 'success';  
            } else {
              $status['group1'] = 'failed';
            }          
          break;
        default:
            $status['group1'] = 'failed';         
          break;
      } 
      
      //evaluate group2
      switch( $group2_and_or_roles ) {
        case 'and': 
            if (($status['group2_rules'] == 'success') &&
                ($status['group2_roles'] == 'success')) {
              $status['group2'] = 'success';  
            } else {
              $status['group2'] = 'failed';
            }            
          break;
        case 'or':
            if (($status['group2_rules'] == 'success') ||
                ($status['group2_roles'] == 'success')) {
              $status['group2'] = 'success';  
            } else {
              $status['group2'] = 'failed';
            }          
          break;
        default:
            $status['group2'] = 'failed';         
          break;
      } 

      //evaluate group3
      switch( $group3_and_or_plugin_category ) {
        case 'and': 
            if (($status['group3_product_category'] == 'success') &&
                ($status['group3_plugin_category'] == 'success')) {
              $status['group3'] = 'success';  
            } else {
              $status['group3'] = 'failed';
            }            
          break;
        case 'or':
            if (($status['group3_product_category'] == 'success') ||
                ($status['group3_plugin_category'] == 'success')) {
              $status['group3'] = 'success';  
            } else {
              $status['group3'] = 'failed';
            }          
          break;
        default:
            $status['group3'] = 'failed';         
          break;          
      } 

      //evaluate all 3 groups together
      switch( true ) {
        case ( ($and_or_group1_to_group2 == 'and') &&
               ($and_or_group2_to_group3 == 'and') ) : 
            if ( ($status['group1'] == 'success') &&
                 ($status['group2'] == 'success') &&
                 ($status['group3'] == 'success') ) {
              $status['total'] = 'success';  
            } else {
              $status['total'] = 'failed';
            }            
          break;
        case ( ($and_or_group1_to_group2 == 'and') &&
               ($and_or_group2_to_group3 == 'or') ) : 
            if ( (($status['group1'] == 'success')  &&
                  ($status['group2'] == 'success')) ||
                  ($status['group3'] == 'success') ) {
              $status['total'] = 'success';  
            } else {
              $status['total'] = 'failed';
            }            
          break;
        case ( ($and_or_group1_to_group2 == 'or') &&
               ($and_or_group2_to_group3 == 'and') ) : 
            if ( (($status['group1'] == 'success')  ||
                  ($status['group2'] == 'success')) &&
                  ($status['group3'] == 'success') ) {
              $status['total'] = 'success';  
            } else {
              $status['total'] = 'failed';
            }            
          break;
        case ( ($and_or_group1_to_group2 == 'or') &&
               ($and_or_group2_to_group3 == 'or') ) : 
            if ( ($status['group1'] == 'success') ||
                 ($status['group2'] == 'success') ||
                 ($status['group3'] == 'success') ) {
              $status['total'] = 'success';  
            } else {
              $status['total'] = 'failed';
            }            
          break;                    
      } 

      if ($status['total'] == 'success') {
        $msg_counter++;
        $output .= '<span class="vtprd-advanced-deal-msg" id="vtprd-advanced-deal-msg-' . $vtprd_rules_set[$i]->post_id . '">';
        $output .= stripslashes($vtprd_rules_set[$i]->discount_product_full_msg);
        $output .= '</span>';
        $output .= '<span class="vtprd-line-skip-with-display-block"></span>';      
      }
      
    } //end 'for' loop
    
    
    if ($msg_counter == 0) {
      return;
    }

    //close owning div 
    $output .= '</div>'; 
    
    return $output;  
  }


  
  add_shortcode('pricing_deal_product_msgs','vtprd_pricing_deal_product_msgs');
	function vtprd_pricing_deal_product_msgs(){
    global $post, $vtprd_info;


    $product_id = the_ID(); 

        
    //routine has been called, but no product_id supplied or available
    if (!$product_id) {
      return;
    }
   
    vtprd_get_product_session_info($product_id);
 
    //CUSTOM function created by CUSTOMER
    if (function_exists('custom_show_product_realtime_discount_full_msgs')) {
      custom_show_product_realtime_discount_full_msgs($product_id, $vtprd_info['product_session_info']['product_rule_full_msg_array']);
      return;
    } 

    $sizeof_msg_array = sizeof($vtprd_info['product_session_info']['product_rule_full_msg_array']);
    for($y=0; $y < $sizeof_msg_array; $y++) {
      ?>
				<p class="pricedisplay <?php echo wpsc_the_product_id(); ?> vtprd-single-product-msgs"><?php echo stripslashes($vtprd_info['product_session_info']['product_rule_full_msg_array'][$y]); ?> </p>
      <?php
    } 
         
    return;
  } 



 
function vtprd_the_product_price_display( $args = array() ) {
   global $vtprd_info,  $vtprd_setup_options;
  if ( empty( $args['id'] ) )
		$id = get_the_ID();
	else
		$id = (int) $args['id'];

       
 //-+--+-+-+-+-+-+-+-+-+-+-++--+-+-+-+-+-+-+-+-+-+-++--+-+-+-+-+-+-+-+-+-+-+
 //  if $id is a variation and has a parent, sent the PARENT!!!

  //gets all of the info we need and puts it into 'product_session_info'
  vtprd_get_product_session_info($id);
  
   
 //-+--+-+-+-+-+-+-+-+-+-+-++--+-+-+-+-+-+-+-+-+-+-++--+-+-+-+-+-+-+-+-+-+-+
 //  if $id is a variation, refigure product_yousave_total_amt!!


 
  
  //if we have no yousave amt, do the default routine and exit
  if ($vtprd_info['product_session_info']['product_yousave_total_amt'] == 0) {
     wpsc_the_product_price_display($args);
     return;
  }

	$defaults = array(
		'id' => $id,
		'old_price_text'   => __( 'Old Price: %s', 'wpsc' ),
		'price_text'       => __( 'Price: %s', 'wpsc' ),
		/* translators     : %1$s is the saved amount text, %2$s is the saved percentage text, %% is the percentage sign */
		'you_save_text'    => __( 'You save: %s', 'wpsc' ),
		'old_price_class'  => 'pricedisplay wpsc-product-old-price ' . $id,
		'old_price_before' => '<p %s>',
		'old_price_after'  => '</p>',
		'old_price_amount_id'     => 'old_product_price_' . $id,
		'old_price_amount_class' => 'oldprice',
		'old_price_amount_before' => '<span class="%1$s" id="%2$s">',
		'old_price_amount_after' => '</span>',
		'price_amount_id'     => 'product_price_' . $id,
		'price_class'  => 'pricedisplay wpsc-product-price ' . $id,
		'price_before' => '<p %s>',
		'price_after' => '</p>',
		'price_amount_class' => 'currentprice pricedisplay ' . $id,
		'price_amount_before' => '<span class="%1$s" id="%2$s">',
		'price_amount_after' => '</span>',
		'you_save_class' => 'pricedisplay wpsc-product-you-save product_' . $id,
		'you_save_before' => '<p %s>',
		'you_save_after' => '</p>',
		'you_save_amount_id'     => 'yousave_' . $id,
		'you_save_amount_class' => 'yousave',
		'you_save_amount_before' => '<span class="%1$s" id="%2$s">',
		'you_save_amount_after'  => '</span>',
		'output_price'     => true,
		'output_old_price' => true,
		'output_you_save'  => true,
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );


  $amt = $vtprd_info['product_session_info']['product_list_price'];
  $amt = vtprd_format_money_element($amt);
  $old_price  =  $amt;

  $amt = $vtprd_info['product_session_info']['product_yousave_total_amt'];
  $amt = vtprd_format_money_element($amt);  
  $you_save            = $amt . '! (' . $vtprd_info['product_session_info']['product_yousave_total_pct'] . '%)';
  
  $you_save_percentage = $vtprd_info['product_session_info']['product_yousave_total_pct'];

	// if the product has no variations, these amounts are straight forward...
//	$old_price           = wpsc_product_normal_price( $id );
	$current_price       = wpsc_the_product_price( false, false, $id );
//	$you_save            = wpsc_you_save( 'type=amount' ) . '! (' . wpsc_you_save() . '%)';
//	$you_save_percentage = wpsc_you_save();

//	$show_old_price = $show_you_save = wpsc_product_on_special( $id );
  
  /*
	// but if the product has variations and at least one of the variations is on special, we have
	// a few edge cases...
	if ( wpsc_product_has_variations( $id ) && wpsc_product_on_special( $id ) ) {
		// generally it doesn't make sense to display "you save" amount unless the user has selected
		// a specific variation
		$show_you_save = false;

		$old_price_number = wpsc_product_variation_price_from( $id, array( 'only_normal_price' => true ) );
		$current_price_number = wpsc_product_variation_price_from( $id );

		// if coincidentally, one of the variations are not on special, but its price is equal to
		// or lower than the lowest variation sale price, old price should be hidden, and current
		// price should reflect the "normal" price, not the sales price, to avoid confusion
		if ( $old_price_number == $current_price_number ) {
			$show_old_price = false;
			$current_price = wpsc_product_normal_price( $id );
		}
	}
  */
	// replace placeholders in arguments with correct values
	$old_price_class = apply_filters( 'wpsc_the_product_price_display_old_price_class', $old_price_class, $id );
	$old_price_amount_class = apply_filters( 'wpsc_the_product_price_display_old_price_amount_class', $old_price_amount_class, $id );
	$attributes = 'class="' . esc_attr( $old_price_class ) . '"';
//	if ( ! $show_old_price )
//		$attributes .= ' style="display:none;"';
	$old_price_before = sprintf( $old_price_before, $attributes );
	$old_price_amount_before = sprintf( $old_price_amount_before, esc_attr( $old_price_amount_class ), esc_attr( $old_price_amount_id ) );

	$price_class = 'class="' . esc_attr( apply_filters( 'wpsc_the_product_price_display_price_class', esc_attr( $price_class ), $id )  ) . '"';
	$price_amount_class = apply_filters( 'wpsc_the_product_price_display_price_amount_class', esc_attr( $price_amount_class ), $id );
	$price_before = sprintf( $price_before, $price_class );
	$price_amount_before = sprintf( $price_amount_before, esc_attr( $price_amount_class ), esc_attr( $price_amount_id ) );

	$you_save_class = apply_filters( 'wpsc_the_product_price_display_you_save_class', $you_save_class, $id );
	$you_save_amount_class = apply_filters( 'wpsc_the_product_price_display_you_save_amount_class', $you_save_amount_class, $id );
	$attributes = 'class="' . esc_attr( $you_save_class ) . '"';
//	if ( ! $show_you_save )
//		$attributes .= ' style="display:none;"';
	$you_save_before = sprintf( $you_save_before, $attributes );
	$you_save_amount_before = sprintf( $you_save_amount_before, esc_attr( $you_save_amount_class ), esc_attr( $you_save_amount_id ) );
//	$you_save = wpsc_currency_display ( $you_save );

	$old_price     = $old_price_amount_before . $old_price . $old_price_amount_after;
	$current_price = $price_amount_before . $current_price . $price_amount_after;
	$you_save      = $you_save_amount_before . $you_save . $you_save_amount_after;

	$old_price_text = sprintf( $old_price_text, $old_price );
	$price_text     = sprintf( $price_text, $current_price );
	$you_save_text  = sprintf( $you_save_text, $you_save );

 // if ( $vtprd_setup_options['show_old_price'] == 'yes' ) {
	if (($output_old_price) && ($old_price_text > ' ')) {
		echo $old_price_before . $old_price_text . $old_price_after . "\n";
  }
	if ( $output_price )
		echo $price_before . $price_text . $price_after . "\n";

 // if ( $vtprd_setup_options['show_you_save'] == 'yes' ) {
	if ($output_you_save) {
  	if ($you_save_text > ' ') {
      echo $you_save_before . $you_save_text . $you_save_after . "\n";
    } else  
    if ($vtprd_info['product_session_info']['show_yousave_one_some_msg'] > ' ') {
      echo $vtprd_info['product_session_info']['show_yousave_one_some_msg'] . "\n";
    }
  }  

  return;   
}

/* ************************************************
  **   Template Tag / Filter  -  Get display info for single product   & return list price amt
  *************************************************** */  
  function vtprd_show_product_list_price($product_id=null) {
    global $post, $vtprd_info, $vtprd_setup_options;    
      
    
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }
    if (!$product_id) {
      return;
    }    
    $amt = vtprd_get_product_list_price_amt($product_id);

    //CUSTOM function created by CUSTOMER
    if (function_exists('custom_show_product_list_price_amt')) {
      custom_show_product_list_price_amt($product_id, $amt);
      return;
    }

    if ($amt) {
      ?>
				<p class="pricedisplay <?php echo wpsc_the_product_id(); ?>"><?php _e('Old Price', 'wpsc'); ?>: <span class="oldprice" id="old_product_price_<?php echo wpsc_the_product_id(); ?>"><?php echo $amt; ?></span></p>
      <?php
    } else {
      //original code from wpsc-single_product.php
      ?>
      
      <?php if(wpsc_product_on_special()) : ?>
				<p class="pricedisplay <?php echo wpsc_the_product_id(); ?>"><?php _e('Old Price', 'wpsc'); ?>: <span class="oldprice" id="old_product_price_<?php echo wpsc_the_product_id(); ?>"><?php echo wpsc_product_normal_price(); ?></span></p>
			<?php endif; ?>
      
      <?php
    }        
    return;
  }   

    function vtprd_get_product_list_price_amt($product_id=null) {
    global $post, $vtprd_info, $vtprd_setup_options;
        
   //  only applies if one rule set to $rule_execution_type_selected == 'display'.  Carried in an option, set into info...     
    if ($vtprd_info['ruleset_has_a_display_rule'] == 'no') {
      return;
    }
    
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }   
    
    //routine has been called, but no product_id supplied or available
    if (!$product_id) {
      return;
    }
   
    vtprd_get_product_session_info($product_id);
    
    //if the product does not participate in any rule which allows use at display time, only messages are available - send back nothing
    if ( !$vtprd_info['product_session_info']['product_in_rule_allowing_display']  == 'yes') {
       return;
    }

    
    //list price
    $amt = $vtprd_info['product_session_info']['product_list_price'];
    $amt = vtprd_format_money_element($amt);        
    return $amt;

  }   

  

  /* ************************************************
  ** Template Tag / Filter -  Get display info for single product   & return you save line - amt and pct
  *************************************************** */
	function vtprd_show_product_you_save($product_id=null){
    global $post, $vtprd_setup_options, $vtprd_info;
      
    $pct = vtprd_get_single_product_you_save_pct($product_id); 
    $amt = $vtprd_info['product_session_info']['product_yousave_total_amt'];
    $amt = vtprd_format_money_element($amt);
    
    //CUSTOM function created by CUSTOMER
    if (function_exists('custom_show_single_product_you_save')) {
      custom_show_single_product_you_save($product_id, $pct, $amt);
      return;
    }    

    if ($pct) {
      ?>
				<p class="pricedisplay product_<?php echo wpsc_the_product_id(); ?>"><?php _e('You save', 'wpsc'); ?>: <span class="yousave" id="yousave_<?php echo wpsc_the_product_id(); ?>"><?php echo $amt; ?>! (<?php echo $pct; ?>%)</span></p>
			<?php
    } else {
      //original code from wpsc-single_product.php
      ?>
      
        <?php if(wpsc_product_on_special()) : ?>
					<p class="pricedisplay product_<?php echo wpsc_the_product_id(); ?>"><?php _e('You save', 'wpsc'); ?>: <span class="yousave" id="yousave_<?php echo wpsc_the_product_id(); ?>"><?php echo wpsc_currency_display(wpsc_you_save('type=amount'), array('html' => false)); ?>! (<?php echo wpsc_you_save(); ?>%)</span></p>
				<?php endif; ?>
      
      <?php
     }
    return;
  } 
	
  function vtprd_get_single_product_you_save_pct($product_id=null){
    global $post, $vtprd_setup_options, $vtprd_info;
    
   //  only applies if one rule set to $rule_execution_type_selected == 'display'.  Carried in an option, set into info...     
    if ($vtprd_info['ruleset_has_a_display_rule'] == 'no') {
      return;
    }
    
    if ($post->ID > ' ' ) {
      $product_id = $post->ID;
    }
            
    //routine has been called, but no product_id supplied or available
    if (!$product_id) {
      return;
    }
        
    vtprd_get_product_session_info($product_id);
    
    //if the product does not participate in any rule which allows use at display time, only messages are available - send back nothing
    if ( !$vtprd_info['product_session_info']['product_in_rule_allowing_display']  == 'yes') {
       return;
    }

    
    if ( $vtprd_info['product_session_info']['product_yousave_total_pct']  > 0) {
       return $vtprd_info['product_session_info']['product_yousave_total_pct'];
    }
     
    return;
  } 

    
    function vtprd_rule_messaging_load_dummy_cart($product_id) {
        global $post, $wpdb, $woocommerce, $vtprd_cart, $vtprd_cart_item, $vtprd_setup_options, $vtprd_info;
        $vtprd_cart = new vtprd_Cart;
        $vtprd_cart_item = new vtprd_Cart_Item;
        $vtprd_cart_item->product_id           = $product_id;
        $vtprd_cart_item->prod_cat_list = wp_get_object_terms( $product_id, $vtprd_info['parent_plugin_taxonomy'], $args = array('fields' => 'ids') );
        $vtprd_cart_item->rule_cat_list = wp_get_object_terms( $product_id, $vtprd_info['rulecat_taxonomy'], $args = array('fields' => 'ids') );  
        $vtprd_cart->cart_items[]  = $vtprd_cart_item;
        $vtprd_cart->purchaser_ip_address = $vtprd_info['purchaser_ip_address'];  
        return;          
    }
