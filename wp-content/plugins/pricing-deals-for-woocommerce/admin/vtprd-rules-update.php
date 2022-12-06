<?php
 
class VTPRD_Rule_update {
	

	public function __construct(){  
  
    //error_log( print_r(  ' ', true ) );
    //error_log( print_r(  'VTPRD_Rule_update begin', true ) ); 
    
    //********     
    //v2.0.0 begin - moved here to clean up the set FIRST, before anything
    //********
    global $post;
    $save_this_post = $post;
    
    vtprd_maybe_resync_rules_set(); //v1.1.0.6  if multiple updates done rapidly in list screen, these can get out of sync.
    
    //the resync gets other posts, this brings back the correct one.
    $post = $save_this_post;
    
    //v2.0.0 end
    //********
    
    $this->vtprd_edit_rule();

    //apply rule scehduling
    $this->vtprd_validate_rule_scheduling();

    //clear out irrelevant/conflicting data (if no errors)
    $this->vtprd_maybe_clear_extraneous_data();
       
    //translate rule into text...
    //$this->vtprd_build_ruleInWords();      No Longer Used
        
    //update rule...
    $this->vtprd_update_rules_info();

    //v1.1.8.0 begin
    global $vtprd_setup_options, $vtprd_rules_set;
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
      error_log( print_r(  ' ', true ) );
      error_log( print_r(  '$vtprd_rules_set at UPDATE-RULES END', true ) );
      error_log( var_export($vtprd_rules_set, true ) );
    }
    //v1.1.8.0 end

      
  }
  
  /**************************************************************************************** 
  ERROR MESSAGES SHOULD GO ABOVE THE FIELDS IN ERROR, WHERE POSSIBLE, WITH A GENERAL ERROR MSG AT TOP.
  ****************************************************************************************/ 
            
  public  function vtprd_edit_rule() {
      global $post, $wpdb, $vtprd_rule, $vtprd_info, $vtprd_rule_template_framework, $vtprd_deal_edits_framework, $vtprd_deal_structure_framework; //v2.0.0 M solution - removed global $vtprd_rules_set
                                                                                                                                                         
      $vtprd_rule_new = new VTPRD_Rule();   //  always  start with fresh copy
      $selected = 's';

      $vtprd_rule = $vtprd_rule_new;  //otherwise vtprd_rule is not addressable!
      
      // NOT NEEDED now that the edits are going through successfully
      //for new rule, put in 1st iteration of deal info
      //$vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;   mwnt
       
     //*****************************************
     //  FILL / upd VTPRD_RULE...
     //*****************************************
     //   Candidate Population
     
     $vtprd_rule->post_id = $post->ID;

     if ( ($_REQUEST['post_title'] > ' ' ) ) {
       //do nothing
     } else {     
       $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' => '#vtprd-deal-selection',
              'error_msg'  => __('The Rule needs to have a Title, but Title is empty.', 'vtprd')  );   
     }

/*

//specialty edits list:

**FOR THE PRICE OF**
=>for the price of within the group:
buy condition must be an amt
buy amt count must be > 1
buy amt must be = to discount amount count

action group condition must be 'applies to entire'
action group must be same as buy pool group only
discount applies to must be = 'all'

=> for the price of next
buy condition can be anything
action amt condition must be an amt
action amt count must be > 1
action amt must be = to discount amount count 

**CHEAPEST/MOST EXPENSIVE**
*
*NEW "Apply Discount to Equal or Lesser Value Item" 
*
*=> in buy group
buy condition must be an amt
buy amt count must be > 1

*=> in action group
buy condition can be anything
action amt condition can be an amt or $$

*/
      //Upper Selects

      $vtprd_rule->cart_or_catalog_select   = $_REQUEST['cart-or-catalog-select'];  
      $vtprd_rule->pricing_type_select      = $_REQUEST['pricing-type-select'];  
      $vtprd_rule->minimum_purchase_select  = $_REQUEST['minimum-purchase-select'];  
 
      $vtprd_rule->rule_on_off_sw_select    = $_REQUEST['rule-on-off-sw-select'];
      $vtprd_rule->rule_type_select         = $_REQUEST['rule-type-select'];
      //$vtprd_rule->wizard_on_off_sw_select  = $_REQUEST['wizard-on-off-sw-select']; //v2.0.0 removed!!
      
      $vtprd_rule->apply_deal_to_cheapest_select  = $_REQUEST['apply-deal-to-cheapest-select']; //v.1.6.7
              
      $upperSelectsDoneSw                   = $_REQUEST['upperSelectsDoneSw']; 
      
      if ($upperSelectsDoneSw != 'yes') {       
          $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '.top-box',  
                'error_msg'  => __('Blueprint choices not yet completed', 'vtprd') );   //mwn20140414       
          $vtprd_rule->rule_error_red_fields[] = '#blue-area-title' ;    //mwn20140414             
      } 
      

      if (($vtprd_rule->pricing_type_select == 'choose') || ($vtprd_rule->pricing_type_select <= ' ')) {
          $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '.top-box',  
                'error_msg'  => __('Deal Type choice not yet made', 'vtprd') );   //mwn20140414       
          $vtprd_rule->rule_error_red_fields[] = '#pricing-type-select-label' ; 
      } 
   

      //#RULEtEMPLATE IS NOW A HIDDEN FIELD which carries the rule template SET WITHIN THE JS
      //   in response to the inital dropdowns being selected. 
     $vtprd_rule->rule_template = $_REQUEST['rule_template_framework']; 
     
     if ($vtprd_rule->rule_template <= '0') {   //mwn20140414
          /*  mwn20140414 
          $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '.template-area',  
                'error_msg'  => __('Pricing Deal Template choice is required.', 'vtprd') );
          $vtprd_rule->rule_error_red_fields[] = '#deal-type-title' ; 
          */
          $this->vtprd_dump_deal_lines_to_rule();
        //  $this->vtprd_update_rules_info();   mwn20140414           
          return; //fatal exit....           
      } else {    
        for($i=0; $i < sizeof($vtprd_rule_template_framework['option']); $i++) {
          //get template title to make that name available on the Rule
          if ( $vtprd_rule_template_framework['option'][$i]['value'] == $vtprd_rule->rule_template )  {
            $vtprd_rule->rule_template_name = $vtprd_rule_template_framework['option'][$i]['title'];
            $i = sizeof($vtprd_rule_template_framework['option']);
          } 
        }
      }

     //DISCOUNT TEMPLATE
     $display_or_cart = substr($vtprd_rule->rule_template ,0 , 1);
     if ($display_or_cart == 'D') {
       $vtprd_rule->rule_execution_type = 'display';
     } else {
       $vtprd_rule->rule_execution_type = 'cart';
     }

     //using the selected Template, build the $vtprd_deal_edits_framework, used for all DEAL edits following
     $this->vtprd_build_deal_edits_framework();
  
     //********************************************************************************
     //EDIT DEAL LINES
     //***LOOP*** through all of the deal line iterations, edit lines 
     //********************************************************************************        
     $deal_iterations_done = 'no'; //initialize variable
     $active_line_count = 0; //initialize variable
     $active_field_count = 0;     

     for($k=0; $deal_iterations_done == 'no'; $k++) {      
       
       if ( (isset( $_REQUEST['buy_repeat_condition_' . $k] )) && (!empty( $_REQUEST['buy_repeat_condition_' . $k] )) ) {    //is a deal line there? always 1 at least...
         foreach( $vtprd_deal_structure_framework as $key => $value ) {   //spin through all of the screen fields=>  $key = field name, so has multiple uses...  
            //load up the deal structure with incoming fields
            //v1.1.8.1 new isset
            if (isset($_REQUEST[$key . '_' .$k])) {
              $vtprd_deal_structure_framework[$key] = $_REQUEST[$key . '_' .$k];
            }             
         } 
          
            //Edit deal line
         $this->vtprd_edit_deal_info_line($active_field_count, $active_line_count, $k);
            //add deal line to rule
         $vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;   //add each line to rule, regardless if empty              
       } else {     
         $deal_iterations_done = 'yes';
       }
     }
     
     /*v2.0.0.9 begin
     There is a bug cascade brought on by Gutenburg and the resulting programming in other plugins.
     If the JS for these other plugins bleed into the rule screen display and cause a JS conflict, 
     then $vtprd_rule->rule_deal_info may have **no** iterations following update.
     So in that case, two things have to happen.
     1. at this point, the damage has been done to $vtprd_rule->rule_deal_info, so put in the default array
     2. send an error message explaining the JS conflict, and send the user to the Settings screen to turn on the switches, 
     and then try the udpate again.     
     */
      if (sizeof($vtprd_rule->rule_deal_info) == 0) {       
        $vtprd_rule->rule_deal_info[] = vtprd_build_rule_deal_info(); 
        $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#vtprd-deal-selection',  //blue-area-title-line
            'error_msg'  => __('******* <br> Due to another module mishandling Javascript resources, a Javascript conflict has caused a fatal error on this rule. <br><br> please go to wp-admin/pricing deals rules/pricing deals settings page. <br><br> On the horizontal JUMP TO menu, click on "System Options". <br> at "Remove Extra JS from Rule Page", select "Yes"	<br> at "Remove Extra CSS from Rule Page", select "Yes" <br> and click on "Save Changes." <br><br> Any rules which are not displaying correctly will need to deleted and recreated. <br><br>Test in a fresh browser session.  <br>******* ', 'vtprd') );   
      }
      //v2.0.0.9 end 

 
     //v2.0.0 move these 2 here    
     //inPop        
     $vtprd_rule->inPop = $_REQUEST['popChoiceIn'];
     /* v2.0.0
        popChoiceIn:
          wholeStore
          groups     
     */

     //actionPop        

     $vtprd_rule->actionPop = $_REQUEST['popChoiceOut'];
     /* v2.0.0
      popChoiceOut: 
        sameAsInPop
        wholeStore
        groups     
     */
     
     //**********************************************
     $groups_found_count_array = $this->vtprd_get_and_store_selection_arrays(); //v2.0.0
      //$groups_found_count_array = ('buy_groups_found' => $buy_groups_found,'action_groups_found' => $action_groups_found);
     //**********************************************
     
     //****************
     //v1.1.8.0 begin - BULK deal edits
     //****************
     if ($vtprd_rule->pricing_type_select == 'bulk') {
     
        //  load up defaults which don't come across because they're hidden... 
        // most get ++OVERRIDDEN++ during rule execution.    
        $vtprd_rule->rule_deal_info[0]['buy_repeat_condition']    = 'none';
        $vtprd_rule->rule_deal_info[0]['buy_amt_type']            = 'quantity';
        $vtprd_rule->rule_deal_info[0]['buy_amt_count']           = '1';
        $vtprd_rule->rule_deal_info[0]['buy_amt_applies_to']      = 'all';
        $vtprd_rule->rule_deal_info[0]['buy_amt_mod']             = 'none';
        $vtprd_rule->rule_deal_info[0]['action_repeat_condition'] = 'none';
        $vtprd_rule->rule_deal_info[0]['action_amt_type']         = 'one';
        $vtprd_rule->rule_deal_info[0]['action_amt_applies_to']   = 'all';
        $vtprd_rule->rule_deal_info[0]['action_amt_mod']          = 'none';
        $vtprd_rule->rule_deal_info[0]['discount_amt_type']       = 'percent';
        $vtprd_rule->rule_deal_info[0]['discount_amt_count']      = '10'; 
        //**************    
     
        $vtprd_rule->bulk_deal_method = $_REQUEST['bulkMethodIn'];
        $vtprd_rule->bulk_deal_qty_count_by = $_REQUEST['bulkCountByIn'];
        
        //set buy_amt_applies_to so that the EXPLODER in the apply can check it! (discount same as buy set later for bulk...)
        $vtprd_rule->rule_deal_info[0]['buy_amt_applies_to'] = $vtprd_rule->bulk_deal_qty_count_by;
        
        if(!empty($_REQUEST['minVal'])) { 
          $minVal = $_REQUEST['minVal'];
          $maxVal = $_REQUEST['maxVal'];
          $discountType = $_REQUEST['discountType'];
          $discountVal = $_REQUEST['discountVal'];
          $vtprd_rule->bulk_deal_array = array();
          $row_count = sizeof($minVal);
          $final_row_count = 0;
          if ($row_count > 0) {
            for($b=0; $b < $row_count; $b++) {
                //don't copy empty rows
                if ( ($b > 0) &&
                     ($minVal[$b] <= ' ')  &&
                     ($maxVal[$b] <= ' ')  &&
                     ($discountVal[$b] <= ' ')  ) {
                  continue;
                }
                $vtprd_rule->bulk_deal_array[] = array (
                  'min_value'      =>  $minVal[$b], 
                  'max_value'      =>  $maxVal[$b],
                  'discount_type'  =>  $discountType[$b],
                  'discount_value' =>  $discountVal[$b]
                );
                $final_row_count++;
            }
            $this->vtprd_edit_pricing_table($final_row_count);
          } else {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#pricing-table-headings-line',            
                  'error_msg'  => __('When Bulk Purchasing Deal Type is selected, a Pricing Table row must be filled in', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#pricing-type-select-label' ;
            $vtprd_rule->rule_error_red_fields[] = '#pricing_table_group_title_active' ;
            $vtprd_rule->rule_error_box_fields[] = '#minVal_row_1';           
          }
        }        
     }
     //v1.1.8.0 end
     
     
    //if max_amt_type is active, may have a max_amt_msg
    $vtprd_rule->discount_rule_max_amt_msg = $_REQUEST['discount_rule_max_amt_msg'];

    //if max_lifetime_amt_type is active, may have a max_amt_msg
    $vtprd_rule->discount_lifetime_max_amt_msg = $_REQUEST['discount_lifetime_max_amt_msg'];

    //if max_cum_amt_type is active, may have a max_amt_msg
    $vtprd_rule->discount_rule_cum_max_amt_msg = $_REQUEST['discount_rule_cum_max_amt_msg'];
    
               
    $vtprd_rule->discount_product_short_msg = $_REQUEST['discount_product_short_msg'];
    if ( ($vtprd_rule->discount_product_short_msg <= ' ') || 
         ($vtprd_rule->discount_product_short_msg == $_REQUEST['shortMsg']) ) {
        $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' => '#messages-box',            
              'error_msg'  => __('Checkout Short Message is required.', 'vtprd') );
        $vtprd_rule->rule_error_red_fields[] = '#discount_product_short_msg_label' ;
        $vtprd_rule->rule_error_box_fields[] = '#discount_product_short_msg';       
    } else {
        $vtprd_rule->discount_product_short_msg = stripslashes($vtprd_rule->discount_product_short_msg); //v1.0.9.0
    }   

    $vtprd_rule->discount_product_full_msg = $_REQUEST['discount_product_full_msg']; 
    //if default msg, get rid of it!!!!!!!!!!!!!!  //v1.1.0.8 reworked the IF
    $vtprd_rule->discount_product_full_msg = stripslashes($vtprd_rule->discount_product_full_msg); //v1.0.9.0
    if ( $vtprd_rule->discount_product_full_msg == $vtprd_info['default_full_msg'] ) {
       $vtprd_rule->discount_product_full_msg = ' ';
    }        

              
    $vtprd_rule->cumulativeRulePricing = $_REQUEST['cumulativeRulePricing']; 
    if ($vtprd_rule->cumulativeRulePricing == 'yes') {
       if ($vtprd_rule->cumulativeRulePricingAllowed == 'yes') {
         $vtprd_rule->ruleApplicationPriority_num = $_REQUEST['ruleApplicationPriority_num'];
         $vtprd_rule->ruleApplicationPriority_num = preg_replace('/[^0-9.]+/', '', $vtprd_rule->ruleApplicationPriority_num); //remove leading/trailing spaces, percent sign, dollar sign
         if ( is_numeric($vtprd_rule->ruleApplicationPriority_num) === false ) { 
            $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable 
         }
       } else {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#cumulativePricing_box',  
                  'error_msg'  => __('With this Rule Template chosen, "Apply this Rule Discount in Addition to Other Rule Discounts" must = "No".', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#ruleApplicationPriority_num_label' ;
            $vtprd_rule->rule_error_box_fields[] = '#ruleApplicationPriority_num'; 
            $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable     
       }
    } else {
    //v1.0.5.3 begin
    //  $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable  
         $vtprd_rule->ruleApplicationPriority_num = $_REQUEST['ruleApplicationPriority_num'];
         $vtprd_rule->ruleApplicationPriority_num = preg_replace('/[^0-9.]+/', '', $vtprd_rule->ruleApplicationPriority_num); //remove leading/trailing spaces, percent sign, dollar sign
         if ( is_numeric($vtprd_rule->ruleApplicationPriority_num) === false ) { 
            $vtprd_rule->ruleApplicationPriority_num = '10'; //init variable 
         }
     //v1.0.5.3 end        
    }
                 
    $vtprd_rule->cumulativeSalePricing   = $_REQUEST['cumulativeSalePricing'];
    if ( ($vtprd_rule->cumulativeSalePricing != 'no') && ($vtprd_rule->cumulativeSalePricingAllowed == 'no') ) {
      $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#cumulativePricing_box',  
            'error_msg'  => __('With this Rule Template chosen, "Rule Discount in addition to Product Sale Pricing" must = "Does not apply when Product Sale Priced".', 'vtprd') );
      $vtprd_rule->rule_error_red_fields[] = '#cumulativePricing_box';
      $vtprd_rule->rule_error_box_fields[] = '#cumulativePricing';  
    }
               
    $vtprd_rule->cumulativeCouponPricing = $_REQUEST['cumulativeCouponPricing'];            
    if ( ($vtprd_rule->cumulativeCouponPricing == 'yes') && ($vtprd_rule->cumulativeCouponPricingAllowed == 'no') ) {
      $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#cumulativePricing_box',  
            'error_msg'  => __('With this Rule Template chosen, " Apply Rule Discount in addition to Coupon Discount?" must = "No".', 'vtprd') );
      $vtprd_rule->rule_error_red_fields[] = '#cumulativePricing_box' ;
      $vtprd_rule->rule_error_box_fields[] = '#cumulativePricing'; 
    } 
 

      
     //v1.1.8.0 begin
     if ($vtprd_rule->pricing_type_select == 'bulk') { 
        //set 'discount same as buy'
        $vtprd_rule->actionPop = 'sameAsInPop';    
     }
     //v1.1.8.0 end

      //********************************************************************************************************************
      //Specialty Complex edits... 
      //********************************************************************************************************************
                                                  
       //FOR THE PRICE OF requirements...
       if ($vtprd_rule->rule_deal_info[0]['discount_amt_type'] =='forThePriceOf_Units') {
          switch ($vtprd_rule->rule_template) {
           case 'C-forThePriceOf-inCart':  //buy-x-action-forThePriceOf-same-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" required for Discount Type "For the Price of (Units) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';                
                 } 
                 elseif ( (is_numeric( $vtprd_rule->rule_deal_info[0]['buy_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < '2' )) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" must be > 1 for Discount Type "For the Price of (Units) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';                    
                 }
                 elseif ( (is_numeric( $vtprd_rule->rule_deal_info[0]['buy_amt_count'])) && 
                          ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] <= $vtprd_rule->rule_deal_info[0]['discount_amt_count'])  ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" must be greater than Discount Type "Discount For the Price of Units", when "For the Price of (Units) Discount" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_count_literal_forThePriceOf_0';
                    $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';                    
                 }      
              break;
           case 'C-forThePriceOf-Next':  //buy-x-action-forThePriceOf-other-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" required for Discount Type "For the Price of (Units) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                
                 } 
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['action_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['action_amt_count'] < '2') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" must be > 1 for Discount Type "For the Price of (Units) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                    
                 }
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['action_amt_count'])) &&
                        ($vtprd_rule->rule_deal_info[0]['action_amt_count'] <= $vtprd_rule->rule_deal_info[0]['discount_amt_count']) ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" must be greater than Discount Type "Discount For the Price of Units", when "For the Price of (Units) Discount" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_count_literal_forThePriceOf_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                    
                 }     
              break;
           default:
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#discount_amt_box_0',  
                      'error_msg'  => __('To use Discount Type "For the Price of (Units) Discount", choose a "For the Price Of" template type.', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0'; 
                $vtprd_rule->rule_error_red_fields[] = '#deal-type-title';                   
              break;
         } //end switch   
       } //end if forThePriceOf_Units

       if ($vtprd_rule->rule_deal_info[0]['discount_amt_type'] =='forThePriceOf_Currency') {
          switch ($vtprd_rule->rule_template) {
           case 'C-forThePriceOf-inCart':  //buy-x-action-forThePriceOf-same-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" required for Discount Type "For the Price of (Currency) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';                
                 } 
                 elseif ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < '2' ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('"Buy Unit Quantity" must be > 1 for Discount Type "For the Price of (Currency) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';                    
                 }     
              break;
           case 'C-forThePriceOf-Next':  //buy-x-action-forThePriceOf-other-group-discount
                 if ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'quantity') {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" required for Discount Type "For the Price of (Currency) Discount"', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#action_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';                
                 } 
                 elseif ($vtprd_rule->rule_deal_info[0]['action_amt_count'] < '2' ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('"Get Unit Quantity" must be > 1 for Discount Type "For the Price of (Currency) Discount".', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0';
                    $vtprd_rule->rule_error_box_fields[] = '#action_amt_count_0';                    
                 }     
              break;
           default:
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#discount_amt_box_0',  
                      'error_msg'  => __('To use Discount Type "For the Price of (Currency) Discount", choose a "For the Price Of" template type.', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#discount_amt_type_label_0'; 
                $vtprd_rule->rule_error_red_fields[] = '#deal-type-title';                   
              break;
         } //end switch   
       } //end if forThePriceOf_Currency
                                                    
       //DISCOUNT APPLIES TO requirements...
       if ( ($vtprd_rule->rule_deal_info[0]['discount_applies_to'] == 'cheapest') || 
            ($vtprd_rule->rule_deal_info[0]['discount_applies_to'] == 'most_expensive') ){
          switch ($vtprd_rule->rule_template) {
           case 'C-cheapest-inCart':  //buy-x-action-most-expensive-same-group-discount
                 if ( ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') && 
                      ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'currency') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('Buy Amount type must be Quantity or Currency, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }                     
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['buy_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < '2') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#buy_amt_box_0',  
                          'error_msg'  => __('Buy Amount Count must be greater than 1, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#buy_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }                                                
              break;           
           case 'C-cheapest-Next':  //buy-x-action-most-expensive-other-group-discount
                 if ( ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'quantity') && 
                      ($vtprd_rule->rule_deal_info[0]['action_amt_type'] != 'currency') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('Get Amount type must be Quantity or Currency, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#action_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }                     
                 elseif ( (is_numeric($vtprd_rule->rule_deal_info[0]['action_amt_count'])) && ($vtprd_rule->rule_deal_info[0]['action_amt_count'] < '2') ) {
                    $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '#action_amt_box_0',  
                          'error_msg'  => __('Get Amount Count must be greater than 1, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                    $vtprd_rule->rule_error_red_fields[] = '#action_amt_type_label_0';
                    $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';                 
                 }
              break;
           default:
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#discount_applies_to_box_0',  
                      'error_msg'  => __('Please choose a "Cheapest/Most Expensive" template type, when Discount "Applies To Cheapest/Most Expensive" chosen.', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_label_0';
                $vtprd_rule->rule_error_red_fields[] = '#deal-type-title';
              break;           
         } //end switch        
       } //end if discountAppliesTo
                                            
       //v1.1.0.8 begin
       //only_for_this_coupon_name
       $vtprd_rule->only_for_this_coupon_name   = $_REQUEST['only_for_this_coupon_name'];

       if ( $vtprd_rule->only_for_this_coupon_name == $vtprd_info['default_coupon_msg'] ) {
         $vtprd_rule->only_for_this_coupon_name = ' ';     
       }
       
       /* v2.0.0.5
 in rules_update.php
only_for_this_coupon_name processing
- if only_for_this_coupon_name > ' '
1. if auto add for free, send error msg
2. if coupon not found, add it
3. if coupons-allowed not on, turn on
- else 
 if coupon name REMOVED, run  vtprd_build_inline_front_end_css()        
       */
       
       if ($vtprd_rule->only_for_this_coupon_name > ' ') {
          vtprd_woo_ensure_coupons_are_allowed();
          if ($vtprd_rule->cart_or_catalog_select == 'catalog') {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#only_for_this_coupon_box_0',  
                  'error_msg'  => __('Discount Coupon Code option not valid for Catalog rule type - please remove coupon code.', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#only_for_this_coupon_anchor';          
          } else {
            $coupon = $vtprd_rule->only_for_this_coupon_name;        
            vtprd_woo_maybe_create_coupon_types($coupon);  //v2.0.0.5
          }                        
       }
       //v1.1.0.8 end


    //error_log( print_r(  'buy_group_population_info after load 003 = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) );      
      
               

                                                    
       //v1.1.6.7 begin
       //v2.0.0 reworked to include 'equal-or-less'
       //CHEAPEST selector EDITS
       
       if  ( ($vtprd_rule->apply_deal_to_cheapest_select == 'cheapest') ||
             ($vtprd_rule->apply_deal_to_cheapest_select == 'most-expensive') ||
             ($vtprd_rule->apply_deal_to_cheapest_select == 'equal-or-less') ) {
 
          switch( $vtprd_rule->apply_deal_to_cheapest_select ) {
            case 'cheapest':  
                  $apply_to_msg = 'Apply Discount to Cheapest';
                break;
            case 'most-expensive':  
                  $apply_to_msg = 'Apply Discount to Most Expensive';
                break;          
            case 'equal-or-less':  
                  $apply_to_msg = 'Apply Discount to Equal or Lesser Value Item';
                break;
          }
                
                          
          //cheapest rule should always used advanced mode!
          $vtprd_rule->rule_type_select = 'advanced';
          
          //v2.0.0 edit changed
          if  ( (($vtprd_rule->apply_deal_to_cheapest_select == 'cheapest') ||
                 ($vtprd_rule->apply_deal_to_cheapest_select == 'most-expensive')) 
                      &&
                 ($vtprd_rule->pricing_type_select == 'cheapest') ) {
                   $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '.top-box',  
                        'error_msg'  => __('Deal Type  &nbsp; "Discount Cheapest / Most Expensive"  &nbsp; is the older version of  &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area. <br><br>They may not be selected together. ', 'vtprd') );         
                   $vtprd_rule->rule_error_red_fields[] = '#pricing-type-select-label';
                   $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label';
                   $vtprd_rule->rule_error_box_fields[] = '#pricing-type-select';
                   $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';                      
          }
          
          //BOGO AND NEXT Blueprint edits are applied solo first.  If PASSED, then the other edits happen.
          if ($vtprd_rule->pricing_type_select != 'bogo') {
                   $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#pricing_type_select_box',  
                        'error_msg'  => __('Deal Type must be &nbsp; "BOGO" &nbsp; when &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area ', 'vtprd') );        
                   $vtprd_rule->rule_error_red_fields[] = '#pricing-type-select-label';
                   $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label';
                   $vtprd_rule->rule_error_box_fields[] = '#pricing-type-select';
                   $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';                      
          } else {
            if ($vtprd_rule->minimum_purchase_select != 'next') {
                     $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => '.top-box',  
                          'error_msg'  => __('Deal Action must be &nbsp; "NEXT" &nbsp; when &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area ', 'vtprd') );         
                     $vtprd_rule->rule_error_red_fields[] = '#minimum-purchase-select-label';
                     $vtprd_rule->rule_error_red_fields[] = '#minimum-purchase-Next-label';                   
                     $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                     $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';                      
            }
            if ( ($vtprd_rule->pricing_type_select == 'bogo') && 
                 ($vtprd_rule->minimum_purchase_select == 'next') &&
                 ($vtprd_rule->actionPop == 'sameAsInPop') ) {
                     $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#action_group_box_0',  
                      'error_msg'  => __('In  &nbsp; "Get Group Product Filter" &nbsp; ,  &nbsp; "Discount Group Same as Buy Group" &nbsp;  was selected. <br><br>This is NOT ALLOWED when &nbsp; "', 'vtprd') 
                              .$apply_to_msg. __('" &nbsp; selected in Blueprint Area ', 'vtprd') .
                              __('"<br><br>If you want the Get Group "Select Group By" to be the same as the Buy Group "Select Group By",', 'vtprd') . 
                              __('"<br><br><strong>just make the same actual selections in the Buy Group "Select Group By"</strong>,', 'vtprd')          
                              );
                     $vtprd_rule->rule_error_red_fields[] = '#action_group_title_anchor';
                     $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                     $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut';
                     $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';   
            }
            //v2.0.0 edit changed
            if ( (($vtprd_rule->apply_deal_to_cheapest_select == 'cheapest') ||
                  ($vtprd_rule->apply_deal_to_cheapest_select == 'most-expensive')) && 
                  ($vtprd_rule->pricing_type_select == 'bogo') && 
                  ($vtprd_rule->minimum_purchase_select == 'next') &&
                  ($groups_found_count_array['action_groups_found'] == 1) &&
                  (sizeof($vtprd_rule->action_group_population_info['action_group_product_incl_array']) == 1) ) {
                     //test single product to see if it's a parent.  If so, allow.
                     if (vtprd_test_for_variations($vtprd_rule->action_group_population_info['action_group_product_incl_array'][0])) {
                       $this_is_a_group_item = true; 
                     } else {
                       $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#action_group_box_0',  
                        'error_msg'  => __('In  &nbsp; "Get Group Product Filter" &nbsp; , a single product was chosen.  <br><br>This is NOT ALLOWED when &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area. ', 'vtprd') );
                       $vtprd_rule->rule_error_red_fields[] = '#action_group_title_anchor';
                       $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                       $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut';
                       $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select'; 
                     }  
            }
            //v2.0.0 edit changed
            if ( (($vtprd_rule->apply_deal_to_cheapest_select == 'cheapest') ||
                  ($vtprd_rule->apply_deal_to_cheapest_select == 'most-expensive')) && 
                  ($vtprd_rule->pricing_type_select == 'bogo') && 
                  ($vtprd_rule->minimum_purchase_select == 'next') ) { 
                                 
                if ( ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'quantity') ||
                     ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'currency') ) {
                        $carry_on = true;
                } else {
                        $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => '#buy_amt_box_0',  
                              'error_msg'  => __('"Buy Unit Quantity" &nbsp;  or &nbsp;  "Buy $$ Value" &nbsp;  <strong> is required</strong> when &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area ', 'vtprd') );
                        $vtprd_rule->rule_error_red_fields[] = '#buy_amt_title_anchor_0';
                        $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                        $vtprd_rule->rule_error_box_fields[] = '#buy_amt_type_0';
                        $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';                                          
                }
            }
            //v2.0.0 edit changed          
            if ( (($vtprd_rule->apply_deal_to_cheapest_select == 'cheapest') ||
                  ($vtprd_rule->apply_deal_to_cheapest_select == 'most-expensive')) && 
                  ($vtprd_rule->pricing_type_select == 'bogo') && 
                  ($vtprd_rule->minimum_purchase_select == 'next') ) {
                              
                if ( ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'quantity') &&
                     ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] == '1') )  {
                        $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => '#buy_repeat_box_0',  
                              'error_msg'  => __(' &nbsp; "Buy Unit Quantity"  &nbsp; must be > 1 when &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area ', 'vtprd') );
                        $vtprd_rule->rule_error_red_fields[] = '#buy_amt_title_anchor_0';
                        $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';     
                        $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                        $vtprd_rule->rule_error_box_fields[] = '#buy_amt_count_0';
                        $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';                                         
                } 
            }
            
            //v2.0.0 new edit
            if  ( ($vtprd_rule->pricing_type_select == 'bogo') && 
                  ($vtprd_rule->minimum_purchase_select == 'next') &&
                  ($vtprd_rule->apply_deal_to_cheapest_select == 'equal-or-less') &&
                  ($vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] != 'none') ) {
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#buy_repeat_box_0',  
                      'error_msg'  => __('Buy  &nbsp; "Rule Usage Count" &nbsp;  * must * be  &nbsp; "Apply Rule Once per Cart" &nbsp;  when &nbsp; "', 'vtprd') .$apply_to_msg. __('" &nbsp; selected in Blueprint Area ', 'vtprd') );
                $vtprd_rule->rule_error_red_fields[] = '#buy_repeat_title_anchor_0';
                $vtprd_rule->rule_error_box_fields[] = '#buy_repeat_condition_0';     
                $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';  
            }  
            //v2.0.0 new edit
            if ($vtprd_rule->apply_deal_to_cheapest_select == 'equal-or-less') { 
               $php_version = phpversion();
               if ( version_compare( $php_version, '5.5', '<' ) ) {	 //new SORT syntax in apply-rules.php requires PHP 5.5+           	 
                  $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#vtprd-deal-selection',  
                        'error_msg'  => __('Your PHP version must be  &nbsp; 5.5 &nbsp; or greater, to use the function &nbsp; "', 'vtprd') .$apply_to_msg. __('"  &nbsp; selected in Blueprint Area ', 'vtprd')
                                    .'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . 'Your PHP version is currently &nbsp;' .  $php_version  .'&nbsp;&nbsp;&nbsp;&nbsp;' . '- Contact your host to upgrade!!!.  '                            
                        );    
                  $vtprd_rule->rule_error_red_fields[] = '#apply-to-cheapest-label'; 
                  $vtprd_rule->rule_error_box_fields[] = '#apply-deal-to-cheapest-select';  
                  
               }
            }
            
          } 
                       
        }
       //v2.0.0 recode end

      //********************************************************************************************************************      
      //********************************************************************************************************************
//to here
      //*************************
      //AUTO ADD switching (+ sort field switching as well)
      //*************************
      $vtprd_rule->rule_contains_auto_add_free_product = 'no';
      //if ($vtprd_rule->rule_deal_info[$d]['discount_amt_type'] == 'free') {
      if ($vtprd_rule->rule_deal_info[0]['discount_amt_type'] == 'free') {
        $vtprd_rule->rule_contains_free_product = 'yes'; 
      } else {
        $vtprd_rule->rule_contains_free_product = 'no'; //used for sort in apply-rules.php      
      }     

      $vtprd_rule->var_out_product_variations_parameter = array(); 
      $sizeof_rule_deal_info = sizeof($vtprd_rule->rule_deal_info);
      for($d=0; $d < $sizeof_rule_deal_info; $d++) {                  
         
         //auto-add editing!!
         if ($vtprd_rule->rule_deal_info[$d]['discount_auto_add_free_product'] == 'yes') {                           
           //verify auto add selected single
           $auto_add_error = false;        
           
           //v2.0.0 begin
           
           //if $vtprd_rule->actionPop  == 'sameAsInPop', edit the BUY group, otherwise edit the ACTION group
           if ($vtprd_rule->actionPop  == 'sameAsInPop' ) {

             //must be groups
             if ($vtprd_rule->inPop != 'groups') {
                 $auto_add_error = true; 
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#buy_group_box_0', 
                        'error_msg'  => __('"Buy Select Group" must be "by Category / Product..." and a *Single Product* selected, When "Automatically Add Free Product to Cart" is Selected and the GET Group Product Filter is "Discount same as Buy Group".', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_box_fields[] = '#popChoiceIn'; 
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';   
                 return;          
             }
             
             
             $sizeof_product_array = sizeof($vtprd_rule->buy_group_population_info['buy_group_product_incl_array']);
             
             //must be a single product
             if ($sizeof_product_array != 1) {
                 $auto_add_error = true; 
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#buy_group_box_0', 
                        'error_msg'  => __('"Get (Discount) Select Group" must be a single product selection, When "Automatically Add Free Product to Cart" is Selected.', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_box_fields[] = '#popChoiceIn';
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0'; 
                 return;           
             }
             
             //no other selections other than a single product allowed
             if ( (sizeof($vtprd_rule->buy_group_population_info['buy_group_prod_cat_incl_array']) > 0) or
                  (sizeof($vtprd_rule->buy_group_population_info['buy_group_prod_cat_excl_array']) > 0) or
                  (sizeof($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_incl_array']) > 0) or
                  (sizeof($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_excl_array']) > 0) or 
                  ($vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array']  > ' ') or
                  ($vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array']  > ' ') or                           
                  (sizeof($vtprd_rule->buy_group_population_info['buy_group_brands_incl_array']) > 0) or
                  (sizeof($vtprd_rule->buy_group_population_info['buy_group_brands_excl_array']) > 0) ) {
                 $auto_add_error = true; 
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#buy_group_box_0', 
                        'error_msg'  => __('"BUY Select Group" must be a single product selection, When "Automatically Add Free Product to Cart" is Selected and Get group product filter is "Discount Same as Buy Group".', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_box_fields[] = '#popChoiceIn';
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                 $vtprd_rule->rule_error_red_fields[] = '#action_group_title_anchor'; 
                 return;                 
              } 
              
              //test single product to see if it's a parent.  If so, disallow.
              if (vtprd_test_for_variations($vtprd_rule->buy_group_population_info['buy_group_product_incl_array'][0])) {
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '.buy-group-product-incl-excl-group', 
                        'error_msg'  => __('Product item is a Variation *Parent* Product - which can allow multiple variations to be found.', 'vtprd') .'<br><br>'. __('Product item must be a single, unique product selection, When "Automatically Add Free Product to Cart" is Selected and Get group product filter is "Discount Same as Buy Group".', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_red_fields[] = '.buy-product-incl-label';                  
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                 $vtprd_rule->rule_error_box_fields[] = '.buy-product-incl-select .select2-container--default .select2-selection--multiple'; 
                 $vtprd_rule->rule_error_red_fields[] = '#action_group_title_anchor';                 
                 return;               
              }
                        
           } else {
     
             //must be groups
             if ($vtprd_rule->actionPop != 'groups') {
                 $auto_add_error = true; 
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#action_group_box_0', 
                        'error_msg'  => __('"Get (Discount) Select Group" must be "by Category / Product..." and a *Single Product* selected, When "Automatically Add Free Product to Cart" is Selected.', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut'; 
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';   
                 return;          
             }
             
             
             $sizeof_product_array = sizeof($vtprd_rule->action_group_population_info['action_group_product_incl_array']);
             
             //must be a single product
             if ($sizeof_product_array != 1) {
                 $auto_add_error = true; 
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#action_group_box_0', 
                        'error_msg'  => __('"Get (Discount) Select Group" must be a single product selection, When "Automatically Add Free Product to Cart" is Selected.', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut';
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0'; 
                 return;           
             }
             
             //no other selections other than a single product allowed
             if ( (sizeof($vtprd_rule->action_group_population_info['action_group_prod_cat_incl_array']) > 0) or
                  (sizeof($vtprd_rule->action_group_population_info['action_group_prod_cat_excl_array']) > 0) or
                  (sizeof($vtprd_rule->action_group_population_info['action_group_plugin_cat_incl_array']) > 0) or
                  (sizeof($vtprd_rule->action_group_population_info['action_group_plugin_cat_excl_array']) > 0) or 
                  ($vtprd_rule->action_group_population_info['action_group_var_name_incl_array']  > ' ') or
                  ($vtprd_rule->action_group_population_info['action_group_var_name_excl_array']  > ' ') or                           
                  (sizeof($vtprd_rule->action_group_population_info['action_group_brands_incl_array']) > 0) or
                  (sizeof($vtprd_rule->action_group_population_info['action_group_brands_excl_array']) > 0) ) {
                 $auto_add_error = true; 
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#action_group_box_0', 
                        'error_msg'  => __('"Get (Discount) Select Group" must be a single product selection, When "Automatically Add Free Product to Cart" is Selected.', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut';
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0'; 
                 return;                 
              }
              
              //test single product to see if it's a parent.  If so, disallow.
              if (vtprd_test_for_variations($vtprd_rule->action_group_population_info['action_group_product_incl_array'][0])) {
                 $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '.action-group-product-incl-excl-group', 
                        'error_msg'  => __('Product item is a Variation *Parent* Product - which can allow multiple variations to be found.', 'vtprd') .'<br><br>'. __('Product item must be a single, unique product selection, When "Automatically Add Free Product to Cart" is Selected', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                        );                                                       
                 $vtprd_rule->rule_error_red_fields[] = '.action-product-incl-label';                  
                 $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                 $vtprd_rule->rule_error_box_fields[] = '.action-product-incl-select .select2-container--default .select2-selection--multiple';                 
                 return;                  
                              
              }              
              
                            
            }

            //carry on with auto add setup
            if (!$auto_add_error) {      
                $vtprd_rule->rule_contains_auto_add_free_product = 'yes';
                $vtprd_rule->rule_contains_free_product = 'yes';                             
                if ($vtprd_rule->actionPop  == 'sameAsInPop' ) {               
                   $vtprd_rule->auto_add_free_trigger_rule_type = 'same_product';                     
                   $vtprd_rule->var_out_product_variations_parameter  = $this->vtprd_get_variations_parameter('inPop');
                   $test_post = get_post($vtprd_rule->buy_group_population_info['buy_group_product_incl_array'][0]); //v2.0.0 
                   $vtprd_rule->inPop_singleProdID_name = sanitize_title($test_post->post_title); //v2.0.0   . ' (Variations)'                             
                } else {
                   $vtprd_rule->auto_add_free_trigger_rule_type = 'external';
                   $vtprd_rule->var_out_product_variations_parameter  = $this->vtprd_get_variations_parameter('actionPop');
                   $test_post = get_post($vtprd_rule->action_group_population_info['action_group_product_incl_array'][0]); //v2.0.0 
                   $vtprd_rule->actionPop_singleProdID_name = sanitize_title($test_post->post_title); //v2.0.0     . ' (Variations)'               
                }
                if ( ($groups_found_count_array['buy_groups_found'] == 1) &&
                     ($vtprd_rule->buy_group_population_info['buy_group_product_incl_array'] ==
                      $vtprd_rule->action_group_population_info['action_group_product_incl_array']) ) {
                  $vtprd_rule->auto_add_free_trigger_rule_type = 'same_product';                      
                }              
             }

          } //end for loop                  
      }

    //error_log( print_r(  'buy_group_population_info after load 004 = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) );      
      
      
      //*************************
      //Pop Filter Agreement Check (switch used in apply...)
      //*************************
      $this->vtprd_maybe_pop_filter_agreement();

      

    //error_log( print_r(  'buy_group_population_info after load 005 = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) ); 
                 
      //*************************
      //check against all other rules acting on the free product
      //*************************
      if ($vtprd_rule->rule_contains_auto_add_free_product == 'yes') {

        $vtprd_rules_set = get_option('vtprd_rules_set');

        //v2.0.0  fix sizeof error if array is NULL
        if (!$vtprd_rules_set) {
          $sizeof_rules_set = 0;
        } else {
          $sizeof_rules_set = sizeof($vtprd_rules_set);
        }
        
       
        for($i=0; $i < $sizeof_rules_set; $i++) { 
                     
          if ( ($vtprd_rules_set[$i]->rule_status != 'publish') ||
               ($vtprd_rules_set[$i]->rule_on_off_sw_select == 'off') ) {             
             continue;
          }

            
          if ($vtprd_rules_set[$i]->post_id == $vtprd_rule->post_id) {                   
             continue;
          } 
                        
          //if another rule has the exact same FREE product, that's an ERROR
          if ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product == 'yes') {  

              /* v2.0.0 removed - now valid
              //v1.1.1.2 begin ADDED ==>> can't do auto adds when activated by coupon
              //v1.1.0.9 begin - 
              // If CURRENT rule activated by coupon, ***no AUTO ADD rules may exist*** ==>> the switches which handle add/remove for coupons go nuts. 
              if ($vtprd_rule->only_for_this_coupon_name > ' ') {
                $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#only_for_this_coupon_box_0',  
                      'error_msg'  => __('Discount Coupon Code not allowed when *any rule* has Auto Add for Free function selected. CONFLICTING RULE NAME is: ', 'vtprd') .$conflictPost->post_title 
                            );                
                $vtprd_rule->rule_error_red_fields[] = '#only_for_this_coupon_anchor';
              } 
              */

              //current rule vs other rule actionPop vs actionPop
              if ( (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array']) > 0) &&
                   (in_array($vtprd_rule->action_group_population_info['action_group_product_incl_array'][0],   
                             $vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'])) ) {
                $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                $vtprd_rule->rule_error_message[] = array( 
                    'insert_error_before_selector' => '#discount_amt_box_0',  
                    'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') .$conflictPost->post_title 
                    );
                $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0'; 
                return; 
              }   
              
              //current rule actionPop vs other rule inPop
              if ($vtprd_rules_set[$i]->actionPop  == 'sameAsInPop' ) { 
                  if ( (sizeof($vtprd_rules_set[$i]->get_group_population_info['get_group_product_incl_array']) > 0) &&
                       (in_array($vtprd_rule->action_group_population_info['action_group_product_incl_array'][0],   
                                 $vtprd_rules_set[$i]->get_group_population_info['get_group_product_incl_array'])) ) {
                    $conflictPost = get_post($vtprd_rules_set[$i]->post_id);
                    $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#discount_amt_box_0',  
                        'error_msg'  => __('When "Automatically Add Free Product to Cart" is Selected, no other Auto Add Rule may have the same product as the Discount Group.  CONFLICTING RULE NAME is: ', 'vtprd') .$conflictPost->post_title 
                        );
                    $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';
                    return; 
                  }                      
              }             

          } //end if
          
        } //end 'for' loop
      } //end if auto product 
      //*************************
      

    //error_log( print_r(  'buy_group_population_info after load 006 = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) );      
      
            
      //v1.0.7.9a  begin
      //additional lifetime custom edit
      global $vtprd_setup_options;
      if ( ( ($vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] == 'quantity') ||
             ($vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] == 'currency') ) 
              &&
             ($vtprd_setup_options['use_lifetime_max_limits'] != 'yes') ) {
           $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '#discount_lifetime_max_dropdown',  
                'error_msg'  => __('In order to use a Customer Rule Limit, please <b>FIRST</b> go to the <b>Pricing Deals Settings Page</b> and turn on the "Use Customer Rule Limits" switch', 'vtprd') 
                ); 
           $vtprd_rule->rule_error_red_fields[] = '#discount_lifetime_max_title_anchor';                  
      } 
      //v1.0.7.9a  end
      


  } //end vtprd_edit_rule
  
  
  //****************************
  //v1.1.8.0  New Function
  //****************************
     /*
     bulk row edit rules:
     must have all 4 values per row  [max may be blank only on last row]
     min must be less than max for each row. [max may be blank only on last row]
     min must be greater than previous row max
     units may not be decimalized     
     */  
  public function vtprd_edit_pricing_table($row_count) {
    global $post, $vtprd_rule, $vtprd_setup_options; //v2.0.0 M solution - removed global $vtprd_rules_set
        $current_row = 0;

				$thousand_separator = get_option( 'woocommerce_price_thousand_sep' );
				$decimal_separator  = get_option( 'woocommerce_price_decimal_sep' );

        for($b=0; $b < $row_count; $b++) {
          
          $current_row ++;                      

          //**********************
          //currency pre-process - decimals required for CURRENCY!!
          //**********************
          /*
            remove thousands separator on all
            remove ' ' used in french format as thousands separatory...
            carry all rows as true decimal
            accept whatever decimal separater is current, change internally to true decimal
            on DISPLAY, change back to selected decimal separator
          */  
          if ($vtprd_rule->bulk_deal_method == 'currency') { //currency pre-process            
            //remove thousands separator
            $vtprd_rule->bulk_deal_array[$b]['min_value'] = str_replace($thousand_separator, '', $vtprd_rule->bulk_deal_array[$b]['min_value']);
            $vtprd_rule->bulk_deal_array[$b]['max_value'] = str_replace($thousand_separator, '', $vtprd_rule->bulk_deal_array[$b]['max_value']);
            //v2.0.0.8 begin
            //remove spaces which might be used as an optional thousands separator
            $vtprd_rule->bulk_deal_array[$b]['min_value'] = str_replace(' ', '', $vtprd_rule->bulk_deal_array[$b]['min_value']);
            $vtprd_rule->bulk_deal_array[$b]['max_value'] = str_replace(' ', '', $vtprd_rule->bulk_deal_array[$b]['max_value']);
            //v2.0.0.8 end
            
            if ($decimal_separator == ',') {
              $vtprd_rule->bulk_deal_array[$b]['min_value'] = str_replace($decimal_separator, '.', $vtprd_rule->bulk_deal_array[$b]['min_value']);
              $vtprd_rule->bulk_deal_array[$b]['max_value'] = str_replace($decimal_separator, '.', $vtprd_rule->bulk_deal_array[$b]['max_value']);
            }
            if ( ($vtprd_rule->bulk_deal_array[$b]['min_value'] > ' ') && //min_value blank replaced by 0.00 later
                 (is_numeric($vtprd_rule->bulk_deal_array[$b]['min_value'] )) ) {
               
               //error_log( print_r(  '$current_row= ' .$current_row , true ) );
               //error_log( print_r(  'min_value= ' .$vtprd_rule->bulk_deal_array[$b]['min_value'] , true ) );
               
               if ( ($current_row == 1) &&
                    ($vtprd_rule->bulk_deal_array[$b]['min_value'] == 0) ) {
                  $vtprd_rule->bulk_deal_array[$b]['min_value'] = '0.00';
                  
                //DOES NOT WORK!!!!!!!!  
                  
                //error_log( print_r(  'min value row 1 now 0.00, $b= ' .$b. ' min value= '.$vtprd_rule->bulk_deal_array[$b]['min_value'] , true ) );  
               }  
               $num_decimals = strlen(preg_replace("/.*\./", "", $vtprd_rule->bulk_deal_array[$b]['min_value']));
               if ( ($num_decimals !== 2) ||
                    (strpos( $vtprd_rule->bulk_deal_array[$b]['min_value'], "." ) === false ) ) {
                  //error_log( print_r(  'error msg begin qty not 2 decimals $b= ' .$b. ' min value= '.$vtprd_rule->bulk_deal_array[$b]['min_value'], true ) );
                  $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#pricing-table-headings-line',            
                        'error_msg'  => __('Begin Quantity on row ', 'vtprd')
                        .$current_row
                        . __(' must have 2 decimal places, when counting by "Currency" ', 'vtprd') );
                  $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row;                     
               }
            }
            
            if ( ($vtprd_rule->bulk_deal_array[$b]['max_value'] > ' ') && //may be blank, tested if appropriate later...
                 (is_numeric($vtprd_rule->bulk_deal_array[$b]['max_value'] )) ) {
               $num_decimals = strlen(preg_replace("/.*\./", "", $vtprd_rule->bulk_deal_array[$b]['max_value']));
               if ( ($num_decimals !== 2) ||
                    (strpos( $vtprd_rule->bulk_deal_array[$b]['max_value'], "." ) === false ) ) {
                  $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#pricing-table-headings-line',            
                        'error_msg'  => __('End Quantity on row ', 'vtprd')
                        .$current_row
                        . __(' must have 2 decimal places, when counting by "Currency" ', 'vtprd') );
                  $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .$current_row;                     
               } 
            }
                           
          } //end currency pre-process
          
          //v2.0.0.8 begin - moved here as the value can be decimal any time
          //change decimal separator to ''
          $vtprd_rule->bulk_deal_array[$b]['discount_value'] = str_replace($thousand_separator, '', $vtprd_rule->bulk_deal_array[$b]['discount_value']); //ALWAYS do this!
          //remove spaces which might be used as an optional thousands separator
          $vtprd_rule->bulk_deal_array[$b]['discount_value'] = str_replace(' ', '', $vtprd_rule->bulk_deal_array[$b]['discount_value']); //ALWAYS do this!          
          
          if ($decimal_separator == ',') {
            $vtprd_rule->bulk_deal_array[$b]['discount_value'] = str_replace($decimal_separator, '.', $vtprd_rule->bulk_deal_array[$b]['discount_value']);            
          }
          //v2.0.0.8 end


          if ($current_row == 1) {
            if ( is_numeric($vtprd_rule->bulk_deal_array[$b]['min_value'] ) ) {
                if ($vtprd_rule->bulk_deal_array[$b]['min_value'] < 0) {
                  $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#pricing-table-headings-line',            
                        'error_msg'  => __('Begin Quantity on First Row must be zero or greater', 'vtprd') );
                  $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row;            
                } 
                //row 1
                if ($vtprd_rule->bulk_deal_method == 'units') {
                    if ( (strpos( $vtprd_rule->bulk_deal_array[$b]['min_value'], "." ) !== false ) ||
                         (strpos( $vtprd_rule->bulk_deal_array[$b]['min_value'], "," ) !== false ) ) {
                      $vtprd_rule->rule_error_message[] = array( 
                            'insert_error_before_selector' => '#pricing-table-headings-line',            
                            'error_msg'  => __('Begin Quantity on row 1 must be a whole number - may not be a decimal, when counting by "Units" ', 'vtprd') );
                      $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row ;
                    }
                    if ( (strpos( $vtprd_rule->bulk_deal_array[$b]['max_value'], "." ) !== false ) ||
                         (strpos( $vtprd_rule->bulk_deal_array[$b]['max_value'], "," ) !== false ) ) {
                      $vtprd_rule->rule_error_message[] = array( 
                            'insert_error_before_selector' => '#pricing-table-headings-line',            
                            'error_msg'  => __('End Quantity on row 1 must be a whole number - may not be a decimal, when counting by "Units" ', 'vtprd') );
                      $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .$current_row;
                    }                
               }    
                                               
            } else {
                if ($vtprd_rule->bulk_deal_method == 'units') {
                  $vtprd_rule->bulk_deal_array[$b]['min_value'] = '0';
                } else {
                  $vtprd_rule->bulk_deal_array[$b]['min_value'] = '0.00';
                }
            }           
          } else { //rows 2 => N 
              if  ( ( !is_numeric($vtprd_rule->bulk_deal_array[$b]['min_value'] ) ) || 
                    ($vtprd_rule->bulk_deal_array[$b]['min_value'] <= 0) ) {
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#pricing-table-headings-line',            
                      'error_msg'  => __('Begin Quantity  on row ', 'vtprd') 
                                     .$current_row
                                     .__(' must be a number greater than zero', 'vtprd') );
                $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row;            
              } else {
                                           
                if ($vtprd_rule->bulk_deal_array[$b]['min_value'] <= $vtprd_rule->bulk_deal_array[$b-1]['max_value']) {
                  $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#pricing-table-headings-line',            
                        'error_msg'  => __('Begin Quantity on row ', 'vtprd')
                        .$current_row
                        . __(' must be greater than the End Quantity on the previous row ', 'vtprd') 
                        .($current_row-1) );
                  $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row;
                  $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .($current_row-1);            
                } 
                //rows 2 => N
                if ( ($vtprd_rule->bulk_deal_method == 'units') &&
                     (strpos( $vtprd_rule->bulk_deal_array[$b]['min_value'], "." ) !== false ) ||
                     (strpos( $vtprd_rule->bulk_deal_array[$b]['min_value'], "," ) !== false ) ) {
                  $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => '#pricing-table-headings-line',            
                        'error_msg'  => __('Begin Quantity on row ', 'vtprd')
                        .$current_row
                        . __(' must be a whole number - may not be a decimal, when counting by "Units" ', 'vtprd') );
                  $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row;                
                }
                             
              }    
          }
          
          if (is_numeric($vtprd_rule->bulk_deal_array[$b]['max_value'] ) ) { 
             if ($vtprd_rule->bulk_deal_array[$b]['max_value'] <= 0) {
              $vtprd_rule->rule_error_message[] = array( 
                    'insert_error_before_selector' => '#pricing-table-headings-line',            
                    'error_msg'  => __('End Quantity must be greater than zero on row ', 'vtprd') 
                                    .$current_row );
              $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .$current_row;            
            } else {
              if ( $vtprd_rule->bulk_deal_array[$b]['min_value'] > $vtprd_rule->bulk_deal_array[$b]['max_value'] ) {
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#pricing-table-headings-line',            
                      'error_msg'  => __('The End Quantity on row ', 'vtprd')
                                      .$current_row  
                                      . __(' must be greater than or equal to Begin Quantity', 'vtprd') );
                $vtprd_rule->rule_error_box_fields[] = '#minVal_row_' .$current_row; 
                $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .$current_row;           
              }
              if ( ($vtprd_rule->bulk_deal_method == 'units') &&
                   (strpos( $vtprd_rule->bulk_deal_array[$b]['max_value'], "." ) !== false ) ||
                   (strpos( $vtprd_rule->bulk_deal_array[$b]['max_value'], "," ) !== false ) ) {
                $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => '#pricing-table-headings-line',            
                      'error_msg'  => __('End Quantity on row ', 'vtprd')
                      .$current_row
                      . __(' must be a whole number - may not be a decimal, when counting by "Units" ', 'vtprd') );
                $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .$current_row;                
              }                            
            }                   
          } else { //end set to 'no limit', row is not last row
            if ($current_row < $row_count) { //if we are not on last row...
              $vtprd_rule->rule_error_message[] = array( 
                    'insert_error_before_selector' => '#pricing-table-headings-line',            
                    'error_msg'  => __('The End Quantity on Row ', 'vtprd')
                                    .$current_row  
                                    . __(' is set to "No Limit".', 'vtprd') 
                                    . __(' Only The End Quantity on the last row in the table may be set to "No Limit"', 'vtprd') );
              $vtprd_rule->rule_error_box_fields[] = '#maxVal_row_' .$current_row;            
            } else {
              //place a max val for processing.  remove, however, in the UI display!!!
              $vtprd_rule->bulk_deal_array[$b]['max_value'] = 999999999999;
            }                 
          }
                    
          if ( is_numeric($vtprd_rule->bulk_deal_array[$b]['discount_value'] ) ) {
            if ($vtprd_rule->bulk_deal_array[$b]['discount_value'] <= 0) {
              $vtprd_rule->rule_error_message[] = array( 
                    'insert_error_before_selector' => '#pricing-table-headings-line', 
                    'error_msg'  => __('The Discount Value on row ', 'vtprd')
                                    .$current_row 
                                    .__(' must be greater than zero', 'vtprd') );
              $vtprd_rule->rule_error_box_fields[] = '#discountVal_row_' .$current_row;            
            }          
          } else {
            //no discount supplied - discount must always be there.
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#pricing-table-headings-line',            
                  'error_msg'  => __('The Discount Value on row ', 'vtprd')
                                    .$current_row 
                                    .__(' must be a number greater than zero', 'vtprd') );
            $vtprd_rule->rule_error_box_fields[] = '#discountVal_row_' .$current_row;
          } 

          if ( ($vtprd_rule->bulk_deal_array[$b]['discount_type'] == 'fixedPrice') &&
               ($vtprd_rule->rule_deal_info[0]['discount_applies_to'] == 'all') ) {
              $vtprd_rule->rule_error_message[] = array( 
                    'insert_error_before_selector' => '#pricing-table-headings-line', 
                    'error_msg'  => __('For Bulk row Discount Type of Fixed Price (on row ', 'vtprd')
                                    .$current_row 
                                    .__('), Discount Applies To must be "Each Product"', 'vtprd') );               
             $vtprd_rule->rule_error_box_fields[] = '#discount_amt_type_row_' .$current_row;
             $vtprd_rule->rule_error_box_fields[] = '#discount_applies_to_0';
             $vtprd_rule->rule_error_red_fields[] = '#discount_applies_to_title_anchor_0';
             $vtprd_rule->rule_type_select = 'advanced'; //in case they've set to basic - in that case, discount applies to wouldn't be visible!
          }
          
        } //end for loop
        
        return;
  } //end vtprd_edit_pricing_table
  

  public function vtprd_update_rules_info() {
    global $post, $vtprd_rule, $vtprd_rules_set, $vtprd_setup_options; //v2.0.0 M solution - KEEP global $vtprd_rules_set

    //v1.0.4
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){   
      error_log( print_r(  '$vtprd_rule at update time, vtprd-rules-update.php', true ) );
      error_log( var_export($vtprd_rule, true ) );   
    }
/*      
    //set the switch used on the screen for JS data check
    switch( true ) {
      case (!$vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] == 'none'):
      case ( $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'] > 0) :
      case (!$vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] == 'none'):
      case ( $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'] > 0) :
      case (!$vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] == 'none'):
      case ( $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'] > 0) :
          $vtprd_rule->advancedSettingsDiscountLimits = 'yes';
        break;
    }
  */
    //*****************************************
    //  If errors were found, the error message array will be displayed by the UI on next screen send.
    //*****************************************

    if  ( sizeof($vtprd_rule->rule_error_message) > 0 ) {
      $vtprd_rule->rule_status = 'pending';    
    } else {
      $vtprd_rule->rule_status = 'publish';      
    }
           
    
    $vtprd_rule->rule_updated_with_free_version_number =  VTPRD_VERSION; //v2.0.0
    
    
    //v1.1.0.8 begin
    if ( (sizeof($vtprd_rule->rule_error_message) > 0 ) &&
         ($vtprd_rule->rule_type_select == 'basic') ) {        
      if ( (in_array('#discount_applies_to_box_0',   $vtprd_rule->rule_error_red_fields)) ||
           (in_array('#only_for_this_coupon_anchor', $vtprd_rule->rule_error_red_fields)) ) {
          //can't see the Discount error fields in basic!
          $vtprd_rule->rule_type_select = 'advanced';
      }   
    }
    //v1.1.0.8 end

    $rules_set_found = false;
    $vtprd_rules_set = get_option( 'vtprd_rules_set' ); 
    if ($vtprd_rules_set) {
      $rules_set_found = true;
    }
          
    if ($rules_set_found) {
      $rule_found = false;
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) {       
         if ($vtprd_rules_set[$i]->post_id == $post->ID) {
            $vtprd_rules_set[$i] = $vtprd_rule;
            $i =  $sizeof_rules_set;
            $rule_found = true;
         }
      }
      if (!$rule_found) {
         $vtprd_rules_set[] = $vtprd_rule;        
      } 
    } else {
      $vtprd_rules_set = array();
      $vtprd_rules_set[] = $vtprd_rule;
    }

    //v2.0.0 begin
    /*
    if ($rules_set_found) {
      update_option( 'vtprd_rules_set',$vtprd_rules_set );
    } else {
      add_option( 'vtprd_rules_set',$vtprd_rules_set );
    }
    */
    update_option( 'vtprd_rules_set',$vtprd_rules_set ); //v2.0.0 will do an add if not found
        
    //v2.0.0 end   
     
    //v2.0.0.5 begin
    if ( ($vtprd_rule->only_for_this_coupon_name > ' ') && 
         ($vtprd_rule->rule_status == 'publish') &&
         ($vtprd_rule->cart_or_catalog_select == 'cart') ) {
   //error_log( print_r(  'Execute function vtprd_build_inline_front_end_css, 001', true ) );      
       vtprd_build_inline_front_end_css();                       
    }
    //v2.0.0.5 end

    
    //v1.1.7.1a begin
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){  
      error_log( print_r(  '$vtprd_rules_set at END OF RULE UPDATE', true ) );
      error_log( var_export($vtprd_rules_set, true ) );
    }
    //v1.1.7.1a end


    //error_log( print_r(  'buy_group_population_info  at END OF RULE UPDATE = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) ); 


    //v2.0.0 begin M solution
    //**************
    //keep a running track of ruleset_has_a_display_rule   ==> used in apply-rules processing
    //*************    
    // added in test for rule_status == 'publish'
    $ruleset_has_a_display_rule = 'no';
    $ruleset_contains_auto_add_free_product = 'no';
    $ruleset_contains_auto_add_free_coupon_initiated_deal = 'no';
    $sizeof_rules_set = sizeof($vtprd_rules_set);
    for($i=0; $i < $sizeof_rules_set; $i++) { 
       if ( ($vtprd_rules_set[$i]->rule_status == 'publish') &&
            ($vtprd_rules_set[$i]->rule_on_off_sw_select != 'off') ) {
         if ($vtprd_rules_set[$i]->rule_execution_type == 'display') {
            $ruleset_has_a_display_rule = 'yes'; 
         } 
         if ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product  == 'yes') { 
            $ruleset_contains_auto_add_free_product = 'yes';         
         }
         if ( ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product  == 'yes') &&
              ($vtprd_rules_set[$i]->only_for_this_coupon_name > ' ') ) { 
            $ruleset_contains_auto_add_free_coupon_initiated_deal = 'yes';         
         } 
      }
    }
    update_option( 'vtprd_ruleset_has_a_display_rule',$ruleset_has_a_display_rule );    
    update_option( 'vtprd_ruleset_contains_auto_add_free_product',$ruleset_contains_auto_add_free_product );
    update_option( 'vtprd_ruleset_contains_auto_add_free_coupon_initiated_deal',$ruleset_contains_auto_add_free_coupon_initiated_deal );

    $current_time = time(); 
    update_option( 'vtprd_ruleset_timestamp',$current_time );  
    //v2.0.0 end M solution

/*   //v2.0.0 M solution  replaced with the above code 
    //********************
    //v1.1.0.9 begin keep track of auto adds as well   
    if ($vtprd_rule->rule_contains_auto_add_free_product == 'yes') {
      $ruleset_contains_auto_add_free_product = 'yes';
    } else { 
      $ruleset_contains_auto_add_free_product = 'no';
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) { 
         if ( ($vtprd_rules_set[$i]->rule_status == 'publish') && 
              ($vtprd_rules_set[$i]->rule_contains_auto_add_free_product  == 'yes') ) {
            $i =  $sizeof_rules_set;
            $ruleset_contains_auto_add_free_product = 'yes'; 
         }
      }
    } 
    $option = (get_option('vtprd_ruleset_contains_auto_add_free_product'));
    if ($option > '') {  
      update_option( 'vtprd_ruleset_contains_auto_add_free_product',$ruleset_contains_auto_add_free_product );
    } else {
      add_option( 'vtprd_ruleset_contains_auto_add_free_product',$ruleset_contains_auto_add_free_product );
    }
    //v1.1.0.9 end 
    //****************  
*/


    //nuke the browser session variables in this case - allows clean retest ...
    // mwn20140414 begin => added inline session_start().  allow potential dup session start, as it's only a Notice, not a warning....
    if (session_id() == "") {
      session_start();    
    } 
    $_SESSION = array();
    $_SESSION['session_started'] = 'Yes!';  // need to initialize the session prior to destroy 
    session_destroy();   
    session_write_close();
    // mwn20140414 end        

    return;
  } 
  
  public function vtprd_validate_rule_scheduling() {
    global $vtprd_rule, $vtprd_setup_options;  
    
    $date_valid = true;     
    $loop_ended = 'no';
    $today = date("Y-m-d");

    if ( $vtprd_setup_options['use_this_timeZone'] == 'none') {
        $vtprd_rule->rule_error_message[] = array( 
          'insert_error_before_selector' => '#date-line-0',  
          'error_msg'  => __('Scheduling requires setup', 'vtprd') );
        $date_valid = false; 
    }

    for($t=0; $loop_ended  == 'no'; $t++) {

      if ( (isset($_REQUEST['date-begin-' .$t])) ||
           (isset($_REQUEST['date-end-' .$t])) ) {  
       
        $date = $_REQUEST['date-begin-' .$t];
        
        $vtprd_rule->periodicByDateRange[$t]['rangeBeginDate'] = $date;

        if (!vtprd_checkDateTime($date)) {
           $vtprd_rule->rule_error_red_fields[] = '#date-begin-' .$t. '-error';
           $date_valid = false;
        }

        $date = $_REQUEST['date-end-' .$t];
        $vtprd_rule->periodicByDateRange[$t]['rangeEndDate'] = $date;
        if (!vtprd_checkDateTime($date)) {
           $vtprd_rule->rule_error_red_fields[] = '#date-end-' .$t. '-error';
           $date_valid = false;
        }
      
      } else {
        $loop_ended = true;
        break;        
      }

      if ($vtprd_rule->periodicByDateRange[$t]['rangeBeginDate'] >  $vtprd_rule->periodicByDateRange[$t]['rangeEndDate']) {
          $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#date-line-0',  
            'error_msg'  => __('End Date must be Greater than or equal to Begin Date', 'vtprd') );
          $vtprd_rule->rule_error_red_fields[] = '#end-date-label-' .$t;
          $date_valid = false;
      }    
      //emergency exit
      if ($t > 9) {
        break; //exit the for loop
      }
    } 
    
    if (!$date_valid) {
      $vtprd_rule->rule_error_message[] = array( 
            'insert_error_before_selector' => '#vtprd-rule-scheduling',  
            'error_msg'  => __('Please repair date error', 'vtprd') );                   
    }
    
  } 

  
  //**********************
  // DEAL Line Edits
  //**********************
  public function vtprd_edit_deal_info_line($active_field_count, $active_line_count, $k ) {
    global $vtprd_rule, $vtprd_deal_structure_framework, $vtprd_deal_edits_framework;
   
    $skip_amt_edit_dropdown_values  =  array('once', 'none' , 'zero', 'one' , 'unlimited', 'each', 'all', 'cheapest', 'most_expensive');
   
   //FIX THIS LATER!!!!!!!!!!!!!!!
   /* if ($active_field_count == 0) { 
      if ( !isset( $_REQUEST['dealInfoLine_' . ($k + 1) ] ) ) {  //if we're on the last line onscreen
         if ($k == 0) { //if the 1st line is the only line 
            $vtprd_rule->rule_error_message[] = array( 'insert_error_before_selector' => '#rule_deal_info_line.0',  //errmsg goes before the 1st line onscreen
                                                        'error_msg'  => __('Deal Info Line must be filled in, for the rule to be valid', 'vtprd')  );
          }  else {
            $vtprd_rule->rule_error_message[] = array( 'insert_error_before_selector' => '#rule_deal_info_line' .$k,  //errmsg goes before current onscreen line
                                                        'error_msg'  => __('At least one Deal Info Line must be filled in, for the rule to be valid', 'vtprd')  );        
          }
        
      } else {    //this empty line is not the last...
            $vtprd_rule->rule_error_message[] = array( 'insert_error_before_selector' => '#rule_deal_info_line' .$k,  //errmsg goes before current onscreen line
                                                       'error_msg'  => __('Deal Info Line is not filled in.  Please delete the line', 'vtprd')  );      
      }
      return;
    }    */

  
    //Go through all of the possible deal structure fields            
    foreach( $vtprd_deal_edits_framework as $fieldName => $fieldAttributes ) {      
       /* ***********************
       special handling for  discount_rule_max_amt_type, discount_lifetime_max_amt_type.  Even though they appear iteratively in deal info,
       they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
       onscreen... This is actually a kluge, done to utilize the complete editing already available here for a  dropdown and an associated amt field.
       The ui-php points to the '0' iteration of the deal data, when displaying these fields.
       *********************** */
       if ( ($fieldName == 'discount_rule_max_amt_type' )     || ($fieldName == 'discount_rule_max_amt_count' ) ||
            ($fieldName == 'discount_rule_cum_max_amt_type' ) || ($fieldName == 'discount_rule_cum_max_amt_count' ) ||
            ($fieldName == 'discount_lifetime_max_amt_type' ) || ($fieldName == 'discount_lifetime_max_amt_count' ) ) {
          //only process these combos on the 1st iteration only!!           
          if ($k > 0) {
             break;
          }
       }

      $field_has_an_error = 'no'; 
      //if the DEAL STRUCTURE KEY field name is in the RULE EDITS array
      if ( $fieldAttributes['edit_is_active'] ) {   //if field active for this template selection
        $dropdown_status; //init variable
        $dropdown_value;  //init variable  
        switch( $fieldAttributes['field_type'] ) {
          case 'dropdown':                   
                if ( ( $vtprd_deal_structure_framework[$fieldName] == '0' ) || ($vtprd_deal_structure_framework[$fieldName] == ' ' ) || ($vtprd_deal_structure_framework[$fieldName] == ''  ) ) {   //dropdown value not selected
                    if ( $fieldAttributes['required_or_optional'] == 'required' ) {                          
                      $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                        'error_msg'  => $fieldAttributes['field_label'] . __(' is required. Please select an option', 'vtprd') );
                      $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ; 
                      $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;        
                      $dropdown_status = 'error';
                      $field_has_an_error = 'yes';
                    }  else {
                       $dropdown_status = 'notSelected'; //optional, still at title, Nothing selected
                    }
                } else {  //something selected
                  //standard 'selected' path              
                  $dropdown_status = 'selected';
                  $dropdown_value  =  $vtprd_deal_structure_framework[$fieldName];
                } 
             break;

          case 'amt':   //amt is ALWAYS preceeded by a dropdown of some sort...
              //clear the amt field if the matching dropdown is not selected
              if ($dropdown_status == 'notSelected') {
                 $vtprd_deal_structure_framework[$fieldName] = ''; //initialize the amt field
                 break;
              }
              //clear the amt field if the matching dropdown is selected, but has a value of  'none', etc.. [values not requiring matching amt]
              $dropdown_values_with_no_amt = array('none', 'unlimited', 'zero', 'one', 'no', 'free', 'each', 'all', 'cheapest', 'most_expensive');
              if ( ($dropdown_status == 'selected') && (in_array($dropdown_value, $dropdown_values_with_no_amt)) ) {
                 $vtprd_deal_structure_framework[$fieldName] = ''; //initialize the amt field
                 break;              
              }                           
             
              // if 'once', 'none' , 'unlimited' on dropdown , then amt field not relevant.
              if ( ($dropdown_status == 'selected') &&  ( in_array($dropdown_value, $skip_amt_edit_dropdown_values) )  ) {                                      
                break;
              }                         
              
              $vtprd_deal_structure_framework[$fieldName] =  preg_replace('/[^0-9.]+/', '', $vtprd_deal_structure_framework[$fieldName]); //remove leading/trailing spaces, percent sign, dollar sign
              if ( !is_numeric($vtprd_deal_structure_framework[$fieldName]) ) {  // not numeric covers it all....
                 if ($dropdown_status == 'selected') { //only produce err msg if previous dropdown status=selected [otherwise amt field cannot be entered]              
                    if  ($vtprd_deal_structure_framework[$fieldName] <= ' ') {  //if blank, use 'required' msg
                        if ( $fieldAttributes['required_or_optional'] == 'required' ) {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' is required. Please enter a value', 'vtprd'); 
                        } else {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' must have a value when a count option chosen in ', 'vtprd') .
                                        $fieldAttributes['matching_dropdown_label'];
                                                       
                        }
                     } else { //something entered but not numeric...
                        if ( $fieldAttributes['required_or_optional'] == 'required' ) {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' is required and not numeric. Please enter a numeric value <em>only</em>', 'vtprd');
                        } else {
                           $error_msg = $fieldAttributes['field_label'] . 
                                        __(' is not numeric, and must have a value value when a count option chosen in ', 'vtprd') .
                                        $fieldAttributes['matching_dropdown_label'];                             
                        }                         
                     }
                     
                     $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                        'error_msg'  => $error_msg ); 
                     //$vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                     $vtprd_rule->rule_error_red_fields[] = $fieldAttributes['matching_dropdown_label_id'] . '_' .$k ;
                     $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;   
                     $field_has_an_error = 'yes';                            
                 } //end  if 'selected' 
                 //THIS path exits here  
              } else {  
                //SPECIAL NUMERIC EDITS, PRN                  
                 switch( $dropdown_value ) {
                    case 'quantity':
                    case 'forThePriceOf_Units':                                           //only allow whole numbers
                        if ($vtprd_deal_structure_framework[$fieldName] <= 0) {
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Units are selected, the number must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;                         
                           $field_has_an_error = 'yes';
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 0 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Units are selected, no decimals are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          }                        
                        }                            
                      break;
                    case 'forThePriceOf_Currency':  // (only on discount_amt_type)
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when For the Price of (Currency) is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 2 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when For the Price of (Currency) is selected, up to 2 decimal places <em>only</em>  are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                          }
                        }                           
                      break;  
                    case 'currency':
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Currency is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                           $field_has_an_error = 'yes';
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 2 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Currency is selected, up to 2 decimal places <em>only</em>  are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          }
                        }                             
                      break;
                    case 'fixedPrice':   // (only on discount_amt_type)
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Fixed Price is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                           $field_has_an_error = 'yes';
                        } else {
                          $number_of_decimal_places = vtprd_numberOfDecimals( $vtprd_deal_structure_framework[$fieldName] ) ;
                          if ( $number_of_decimal_places > 2 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector']. '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Fixed Price is selected, up to 2 decimal places <em>only</em>  are allowed. ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          }                        
                        }                             
                      break;
                    case 'percent':
                        if ( $vtprd_deal_structure_framework[$fieldName] <= 0 ) {           
                           $vtprd_rule->rule_error_message[] = array( 
                              'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                              'error_msg'  => $fieldAttributes['field_label'] .  __(' - when Percent is selected, the amount must be greater than zero. ', 'vtprd') );
                           $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                           $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                           $field_has_an_error = 'yes';
                        } else {
                          if ( $vtprd_deal_structure_framework[$fieldName] < 1 ) {           
                             $vtprd_rule->rule_error_message[] = array( 
                                'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                                'error_msg'  => $fieldAttributes['field_label'] .  __(' - the Percent value must be greater than 1.  For example 10% would be "10", not ".10" . ', 'vtprd') );
                             $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                             $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ; 
                             $field_has_an_error = 'yes';
                          } 
                         }                                  
                      break;
                    case '':
                      break;
                } //end switch
              } //end amount numeric testing       
            break;
         
         case 'text':
              if ( ($vtprd_deal_structure_framework[$fieldName] <= ' ') && ( $fieldAttributes['required_or_optional'] == 'required' ) ) {  //error possible only if blank                        
                        $vtprd_rule->rule_error_message[] = array( 
                          'insert_error_before_selector' => $fieldAttributes['insert_error_before_selector'] . '_' . $k,  //errmsg goes before current onscreen line
                          'error_msg'  => $fieldAttributes['field_label'] . __(' is required. Please enter a description', 'vtprd') );
                        $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;
                        $vtprd_rule->rule_error_box_fields[] = '#' . $fieldName . '_' .$k ;  
                        $field_has_an_error = 'yes';
              }
            break;
        } //end switch
      }  else {
        //if this field doesn't have an active edit and hence is not allowed, clear it out in the DEAL STRUCTURE.
        $vtprd_deal_structure_framework[$fieldName] = '';
      }

      //*******************************
      //Template-Level and Cross-field edits
      //*******************************      
      //This picks up the template_profile_error_msg if appropriate, 
      //  and if no other error messages already created
      if ($field_has_an_error == 'no') {
        switch( $fieldAttributes['allowed_values'] ) {
            case 'all':    //all values are allowed
              break;
            case '':       //no values are allowed
                if ( ($vtprd_deal_structure_framework[$fieldName] > ' ') && ($fieldAttributes['template_profile_error_msg'] > ' ' ) ) {
       //error_log( print_r(  'Error comes from 001 = ' .$fieldAttributes['template_profile_error_msg'] , true ) );              
                  $field_has_an_error = 'yes';
                  $display_this_msg = $fieldAttributes['template_profile_error_msg'];
                  $insertBefore = $fieldAttributes['insert_error_before_selector'];
                  $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);
                }                      
              break;              
            default:  //$fieldAttributes['allowed_values'] is an array!
                //check for valid values
                if ( !in_array($vtprd_deal_structure_framework[$fieldName], $fieldAttributes['allowed_values']) ) { 
       //error_log( print_r(  'Error comes from 002 = ' .$fieldAttributes['template_profile_error_msg'], true ) );                 
                  $field_has_an_error = 'yes';
                  $display_this_msg = $fieldAttributes['template_profile_error_msg'];
                  $insertBefore = $fieldAttributes['insert_error_before_selector'];
                  $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);
                }
              break;
        }

     //error_log( print_r(  '$vtprd_deal_structure_framework= ' , true ) );
     //error_log( var_export($vtprd_deal_structure_framework, true ) );  

        //Cross-field edits
        $sizeof_cross_field_edits = sizeof($fieldAttributes['cross_field_edits']);
        if ( ($field_has_an_error == 'no') && ($sizeof_cross_field_edits > 0) ) {
          for ( $c=0; $c < $sizeof_cross_field_edits; $c++) {
              //if current field values fall within value array that the cross-edit applies to
              if ( in_array($vtprd_deal_structure_framework[$fieldName], $fieldAttributes['cross_field_edits'][$c]['applies_to_this_field_values']) ) {               
                 $cross_field_name = $fieldAttributes['cross_field_edits'][$c]['cross_field_name'];
      /*
      error_log( print_r(  '$cross_field_name= ' .$cross_field_name, true ) );
      error_log( print_r(  '$vtprd_deal_structure_framework value= ' .$vtprd_deal_structure_framework[$cross_field_name], true ) );
      error_log( print_r(  'cross_allowed_values= ' , true ) );
      error_log( var_export($fieldAttributes['cross_field_edits'][$c]['cross_allowed_values'], true ) ); 
       */              
                 //v2.0.0 added isset
                 if ( (isset($vtprd_deal_structure_framework[$cross_field_name])) && 
                      (!in_array($vtprd_deal_structure_framework[$cross_field_name], $fieldAttributes['cross_field_edits'][$c]['cross_allowed_values'])) ) {  
                    //special handling for these 2, as they're not in the standard edit framwork, and we don't have the values yet
                    if ( ($fieldName = 'discount_auto_add_free_product') &&
                        (($cross_field_name == 'popChoiceOut') ||
                         ($cross_field_name == 'cumulativeCouponPricing')) ) {
                        
                        if ($cross_field_name == 'popChoiceOut') {
                          $field_value_temp = $_REQUEST['popChoiceOut'];
                        } else {
                          $field_value_temp = $_REQUEST['cumulativeCouponPricing'];
                        }
                        
                        if ( !in_array($field_value_temp, $fieldAttributes['cross_field_edits'][$c]['cross_allowed_values']) ) { 
                          $field_has_an_error = 'yes';
                          $display_this_msg = $fieldAttributes['cross_field_edits'][$c]['cross_error_msg'];
                          $insertBefore = $fieldAttributes['cross_field_edits'][$c]['cross_field_insertBefore'];
       //error_log( print_r(  'Error comes from 003 = ' .$fieldAttributes['cross_field_edits'][$c]['cross_field_insertBefore'], true ) ); 
                          $vtprd_rule->rule_error_red_fields[] = '#' . $cross_field_name . '_label_' .$k ;
                          //custom error name
                          //this cross-edit name wasn't being picked up correctly...                    
                          $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);	 
                        } 
                    } else {
                      //Normal error processing
                      $field_has_an_error = 'yes';
                      $display_this_msg = $fieldAttributes['cross_field_edits'][$c]['cross_error_msg'];
       //error_log( print_r(  'Error comes from 004 = ' .$fieldAttributes['cross_field_edits'][$c]['cross_error_msg'], true ) ); 
                      $insertBefore = $fieldAttributes['cross_field_edits'][$c]['cross_field_insertBefore'];
                      $vtprd_rule->rule_error_red_fields[] = '#' . $cross_field_name . '_label_' .$k ;
                      //custom error name
                      //this cross-edit name wasn't being picked up correctly...                    
                      $this->vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName);	 
                      
                    }

              } 
            }
          } //end for cross-edit loop       
        } //END Template-Level and Cross-field edits

      } //end if no-error 
      
       
    }  //end foreach

    
    return;
  }

  public function vtprd_add_cross_field_error_message($insertBefore, $k, $display_this_msg, $fieldName) { 
    global $vtprd_rule, $vtprd_deal_structure_framework;
    $vtprd_rule->rule_error_message[] = array( 
      'insert_error_before_selector' =>  $insertBefore . '_' . $k,  //errmsg goes before current onscreen line
      'error_msg'  => $display_this_msg 
      );
    //  'error_msg'  => $fieldAttributes['field_label'] . ' ' .$display_this_msg );
    $vtprd_rule->rule_error_red_fields[] = '#' . $fieldName . '_label_' .$k ;  
  }
  
    
  public function vtprd_dump_deal_lines_to_rule() {  
     global $vtprd_rule, $vtprd_deal_structure_framework;
     $deal_iterations_done = 'no'; //initialize variable

     for($k=0; $deal_iterations_done == 'no'; $k++) {      
       if ( (isset( $_REQUEST['buy_repeat_condition_' . $k] )) && (!empty( $_REQUEST['buy_repeat_condition_' . $k] )) ) {    //is a deal line there? always 1 at least...
         //INITIALIZE was introducing an iteration error!!!!!!!!          
         //$this->vtprd_initialize_deal_structure_framework();       
         foreach( $vtprd_deal_structure_framework as $key => $value ) {   //spin through all of the screen fields  
            //v1.1.8.1 new isset
            if (isset($_REQUEST[$key . '_' .$k])) {            
              $vtprd_deal_structure_framework[$key] = $_REQUEST[$key . '_' .$k];  
            }      
         }                 
         $vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;   //add each line to rule, regardless if empty              
       } else {     
         $deal_iterations_done = 'yes';
       }
     }		  
  }
  
  public function vtprd_build_deal_edits_framework() {
    global $vtprd_rule, $vtprd_template_structures_framework, $vtprd_deal_edits_framework;
    
    //mwn20140414
    if ($vtprd_rule->rule_template <= '0') {
        return; 
    }
        
    // previously determined template key
    $templateKey = $vtprd_rule->rule_template; 
    $additional_template_rule_switches = array ( 'discountAppliesWhere' ,  'inPopAllowed' , 'actionPopAllowed'  , 'cumulativeRulePricingAllowed', 'cumulativeSalePricingAllowed', 'replaceSalePricingAllowed', 'cumulativeCouponPricingAllowed') ;
    $nextInActionPop_templates = array ( 'C-discount-Next', 'C-forThePriceOf-Next', 'C-cheapest-Next', 'C-nth-Next' );
 
    foreach( $vtprd_template_structures_framework[$templateKey] as $key => $value ) {            
      //check for addtional template switches first ==> they are stored in this framework for convenience only.
      if ( in_array($key, $additional_template_rule_switches) ) {
        switch( $key ) {
            case 'discountAppliesWhere':               // 'allActionPop' / 'inCurrentInPopOnly'  / 'nextInInPop' / 'nextInActionPop' / 'inActionPop' /
              // if template set to nextInActionPop, check if it should be overwritten...
              //this is a duplicate field load, done here in advance PRN 
              //OVERWRITE discountAppliesWhere TO GUIDE THE APPLY LOGIC AS TO WHICH GROUP WILL BE ACTED UPON 
              $vtprd_rule->actionPop = $_REQUEST['popChoiceOut'];
              if ( (in_array($templateKey, $nextInActionPop_templates))  &&
                   ($vtprd_rule->actionPop == 'sameAsInPop') ) {
                $vtprd_rule->discountAppliesWhere =  'nextInInPop';
              } else {
                $vtprd_rule->discountAppliesWhere = $value;
              }             
            break;
          case 'inPopAllowed':
              $vtprd_rule->inPopAllowed = $value;
            break; 
          case 'actionPopAllowed':
              $vtprd_rule->actionPopAllowed = $value;
            break;            
          case 'cumulativeRulePricingAllowed':
              $vtprd_rule->cumulativeRulePricingAllowed = $value; 
            break;
          case 'cumulativeSalePricingAllowed':
              $vtprd_rule->cumulativeSalePricingAllowed = $value; 
            break;
          case 'replaceSalePricingAllowed':
              $vtprd_rule->replaceSalePricingAllowed = $value; 
            break;            
          case 'cumulativeCouponPricingAllowed':
              $vtprd_rule->cumulativeCouponPricingAllowed = $value; 
            break;
        }
      } else {      
        if ( ($value['required_or_optional'] == 'required') || ($value['required_or_optional'] == 'optional') ) {
          //update required/optional, $key = field name, same relative value across both frameworks...
          $vtprd_deal_edits_framework[$key]['edit_is_active']       = 'yes';
          $vtprd_deal_edits_framework[$key]['required_or_optional'] = $value['required_or_optional'];         
        } else {
          $vtprd_deal_edits_framework[$key]['edit_is_active']       = '';
        }
        
        $vtprd_deal_edits_framework[$key]['allowed_values']  =  $value['allowed_values'];
        $vtprd_deal_edits_framework[$key]['template_profile_error_msg']  =  $value['template_profile_error_msg'];
        
        //cross_field_edits is an array which ***will only exist where required ****
        if (isset($value['cross_field_edits'])) {  //v1.1.8.0 changed to isset
           $vtprd_deal_edits_framework[$key]['cross_field_edits']  =  $value['cross_field_edits'];
        }
      }            
    } 
   
    return;
  }  

  /* **********************************
   If no edit errors are present,
      clear out irrelevant/conflicting data 
      left over from setting up the rule
        where conditions were changed
      *************************************** */
  public function vtprd_maybe_clear_extraneous_data() { 
    global $post, $vtprd_rule, $vtprd_rule_template_framework, $vtprd_deal_edits_framework, $vtprd_deal_structure_framework, $vtprd_edit_arrays_framework;   //v2.0.0  
    
    //IF there are edit errors, leave everything as is, exit stage left...
    if ( sizeof($vtprd_rule->rule_error_message ) > 0 ) {  
      return;
    }

    //*************
    //Clear BUY area
    //*************
    if (($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'none') ||
        ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'one')) {
       $vtprd_rule->rule_deal_info[0]['buy_amt_count'] = null; 
    }
    
    if ($vtprd_rule->rule_deal_info[0]['buy_amt_mod'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['buy_amt_mod_count'] = null; 
    }  
  
    /* //v2.0.0 begin
    switch( $vtprd_rule->inPop ) {
      case 'wholeStore':
 
          $vtprd_rule->buy_group_population_info    = $vtprd_edit_arrays_framework['buy_group_framework']; //v2.0.0 
          $vtprd_rule->action_group_population_info = $vtprd_edit_arrays_framework['action_group_framework']; //v2.0.0                 
        break;
      case 'groups':
  
        break;
    } 
    */
    if( $vtprd_rule->inPop == 'wholeStore' ) {
      $vtprd_rule->buy_group_population_info    = $vtprd_edit_arrays_framework['buy_group_framework']; 
    }
    //v2.0.0 end

        
    if ($vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['buy_repeat_count'] = null; 
    }      
    //End BUY area clear

    //*************
    //Clear GET area
    //*************
    if (($vtprd_rule->rule_deal_info[0]['action_amt_type'] == 'none') ||
        ($vtprd_rule->rule_deal_info[0]['action_amt_type'] == 'zero') ||
        ($vtprd_rule->rule_deal_info[0]['action_amt_type'] == 'one')) {
       $vtprd_rule->rule_deal_info[0]['action_amt_count'] = null; 
    }
    
    if ($vtprd_rule->rule_deal_info[0]['action_amt_mod'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['action_amt_mod_count'] = null; 
    }  
  
    //v2.0.0 begin
    switch( $vtprd_rule->actionPop ) {
      case 'sameAsInPop':
      case 'wholeStore':
          $vtprd_rule->action_group_population_info = $vtprd_edit_arrays_framework['action_group_framework']; //v2.0.0        
        break;
    }
    //v2.0.0 end 
    
    if ($vtprd_rule->rule_deal_info[0]['action_repeat_condition'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['action_repeat_count'] = null; 
    }      
    //End GET area clear


    //*************
    //Clear DISCOUNT area        
    //*************
    switch( $vtprd_rule->rule_deal_info[0]['discount_amt_type'] ) {
      case 'percent':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null;  
        break;
      case 'currency':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
      case 'fixedPrice':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
      case 'free':
          $vtprd_rule->rule_deal_info[0]['discount_amt_count'] = null;
        break;
      case 'forThePriceOf_Units':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
      case 'forThePriceOf_Currency':
          $vtprd_rule->rule_deal_info[0]['discount_auto_add_free_product'] = null; 
        break;
    }
    //End Discount clear


    //*************
    //Clear MAXIMUM LIMITS area        
    //*************    
    
    if ($vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count'] = null; 
    } 
    if ($vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count'] = null; 
    }     
    if ($vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] == 'none') {
       $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count'] = null; 
    } 
    //End Maximum Limits clear        

    return;  
  }


  //*************************
  //Pop Filter Agreement Check (switch used in apply...)
  //*************************
  public function vtprd_maybe_pop_filter_agreement() { 
    global $vtprd_rule;     
  
    if ($vtprd_rule->actionPop  ==  'sameAsInPop' ) {
      $vtprd_rule->set_actionPop_same_as_inPop = 'yes';
      return;
    }
    
    
    if (($vtprd_rule->inPop      ==  'wholeStore') &&
        ($vtprd_rule->actionPop  ==  'wholeStore') ) {
      $vtprd_rule->set_actionPop_same_as_inPop = 'yes';
      return;
    }

    //v2.0.0 GROUPS is all that's left            
    if ( ($vtprd_rule->buy_group_population_info['buy_group_prod_cat_incl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_prod_cat_incl_array']) &&
         ($vtprd_rule->buy_group_population_info['buy_group_prod_cat_excl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_prod_cat_excl_array']) &&         
 
         ($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_incl_array'] ==
          $vtprd_rule->action_group_population_info['action_group_plugin_cat_incl_array']) &&
         ($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_excl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_plugin_cat_excl_array']) && 
          
         ($vtprd_rule->buy_group_population_info['buy_group_product_incl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_product_incl_array']) &&
         ($vtprd_rule->buy_group_population_info['buy_group_product_excl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_product_excl_array']) &&                   
          
         ($vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_var_name_incl_array']) &&
         ($vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_var_name_excl_array']) &&           
          
         ($vtprd_rule->buy_group_population_info['buy_group_brands_incl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_brands_incl_array']) &&
         ($vtprd_rule->buy_group_population_info['buy_group_brands_excl_array'] == 
          $vtprd_rule->action_group_population_info['action_group_brands_excl_array']) ) {
      $vtprd_rule->set_actionPop_same_as_inPop = 'yes';
      return;          
    }         
    
    $vtprd_rule->set_actionPop_same_as_inPop = 'no';
    return;
  }
  


  /* ************************************************
  **   Get single variation data to support discount_auto_add_free_product, Pro Only
  *
  *++++++++++++++++++++++++++++++++
  *       v2.0.0 Recoded 
  *++++++++++++++++++++++++++++++++  
  * (if we do a straight return, the expected returned value is never accessed so not relevant)          
  *************************************************** */
  public function vtprd_get_variations_parameter($which_vargroup) {
    //error_log( print_r(  'function vtprd_get_variations_parameter BEGIN ' , true ) );
    global $wpdb, $post, $vtprd_rule, $woocommerce;

    if ($which_vargroup == 'inPop') {
       $product_ID    =  $vtprd_rule->buy_group_population_info['buy_group_product_incl_array'][0];    
    } else {
       $product_ID    =  $vtprd_rule->action_group_population_info['action_group_product_incl_array'][0];    
    }
 /*   
error_log( print_r(  ' action_group_product_incl_array occurrence 0= ' .$vtprd_rule->action_group_population_info['action_group_product_incl_array'][0] , true ) );        
error_log( print_r(  '$which_vargroup= ' .$which_vargroup. ' $product_ID= ' .$product_ID , true ) ); 
error_log( print_r(  'action_group_population_info ARRAY= ' , true ) ); 
error_log( var_export($vtprd_rule->action_group_population_info['action_group_product_incl_array'], true ) );
*/
/* v2.0.0 no longer necessary - variation parents are removed from offered product selections.
    //test if selected product is a variation parent - if so, ERROR
    $product_has_variations = vtprd_test_for_variations($product_id);
    if ($product_has_variations) { //v2.0.0 changed from a 'yes' test
      if ($which_vargroup == 'inPop') {  
         $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '#buy_group_box', 
                'error_msg'  => __('When "Discount Same as Buy Group" is selected, "Buy Select Group" must be either a single product, or a single product variation - it may NOT be a single product which has many variations, When "Automatically Add Free Product to Cart" is Selected.', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                );                                                       
         $vtprd_rule->rule_error_box_fields[] = '#popChoiceIn';
         $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';       
      } else {
         $vtprd_rule->rule_error_message[] = array( 
                'insert_error_before_selector' => '#action_group_box_0', 
                'error_msg'  => __('"Get (Discount) Select Group" must be either a single product, or a single product variation - it may NOT be a single product which has many variations, When "Automatically Add Free Product to Cart" is Selected.', 'vtprd') .'<br><br>'. __('Otherwise the Auto add does not know which product to add. ', 'vtprd')
                );                                                      
         $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut';
         $vtprd_rule->rule_error_red_fields[] = '#discount_auto_add_free_product_label_0';        
      }  
      return;
    }
*/    
    //test if product is a VARIATION   
    //sql from woocommerce/classes/class-wc-product.php
    $variation_product = get_posts( array(
			'post_parent' 	=> $product_ID,
			'post_type' 	  => 'product_variation',
			'post_status'	  => 'publish',
      'order'         => 'ASC'
	  ));
    
    //if not a variation, no further processing
    if (!$variation_product) {
      return;
    }
/*
error_log( print_r(  '$which_vargroup= ' .$which_vargroup. ' $product_ID= ' .$product_ID. ' Should not be a variation product... return= ' , true ) ); 
error_log( var_export($variation_product, true ) );        
error_log( print_r(  '$vtprd_rule= ' , true ) ); 
error_log( var_export($vtprd_rule, true ) );
*/           
    $variation_id  =  $product_ID;
    $product_id    =  wp_get_post_parent_id($variation_id);
    
    //************************
    //FROM woocommerce/woocommerce-functions.php  function woocommerce_add_to_cart_action
    //************************
    
	  $adding_to_cart      = wc_get_product( $product_id ); //v1.1.7 replace get_product with wc_get_product

  	$all_variations_set = true;
  	$variations         = array();

		$attributes = $adding_to_cart->get_attributes();
		$variation  = wc_get_product( $variation_id );  //v1.1.7 replace get_product with wc_get_product

		// Verify all attributes
		foreach ( $attributes as $attribute ) {
      if ( ! $attribute['is_variation'] )
      	continue;

      $taxonomy = 'attribute_' . sanitize_title( $attribute['name'] );


          // Get value from post data
          // Don't use woocommerce_clean as it destroys sanitized characters
         // $value = sanitize_title( trim( stripslashes( $_REQUEST[ $taxonomy ] ) ) );
          $value = $variation->variation_data[ $taxonomy ];

          // Get valid value from variation
          $valid_value = $variation->variation_data[ $taxonomy ];
          // Allow if valid
          if ( $valid_value == '' || $valid_value == $value ) {
            if ( $attribute['is_taxonomy'] )
            	$variations[ esc_html( $attribute['name'] ) ] = $value;
            else {
              // For custom attributes, get the name from the slug
              $options = array_map( 'trim', explode( '|', $attribute['value'] ) );
              foreach ( $options as $option ) {
              	if ( sanitize_title( $option ) == $value ) {
              		$value = $option;
              		break;
              	}
              }
               $variations[ esc_html( $attribute['name'] ) ] = $value;
            }
            continue;
        }

    }

    if (sizeof($variations) > 0) {
      $product_variations_array = array(
         'parent_product_id'    => $product_id,
         'variation_product_id' => $variation_id,
         'variations_array'     => $variations
        ); 
     } 
    

    return ($product_variations_array);
  } 


 

  /* ************************************************
  **   v2.0.0 NEW FUNCTION
  *************************************************** */
  public function vtprd_get_and_store_selection_arrays() {
    global $post, $wpdb, $vtprd_rule, $vtprd_info, $vtprd_rule_template_framework, $vtprd_deal_edits_framework, $vtprd_deal_structure_framework, $vtprd_edit_arrays_framework;     //v2.0.0 M solution - removed global $vtprd_rules_set
    //error_log( print_r(  'function vtprd_get_and_store_selection_arrays begin in rules-update', true ) );
       //-----------------------------
       //LOAD DATA FROM screen to rule  ****************
       //-----------------------------

      //INPOP
      
      $buy_groups_found = 0;
      $buy_groups_product_selectors_found = 0;
      $and_switch_count = 0;
      $and_switch_first_key = null;
      
      $buy_groups_include_test_found = false; //used for setting the 'exclude only' later          
      
      if ($vtprd_rule->inPop == 'groups') {        
        $buy_arrays_framework = $vtprd_edit_arrays_framework['buy_group_framework'];
        foreach( $buy_arrays_framework as $key => $value ) {  
           if (isset($_REQUEST[$key])) {
             $vtprd_edit_arrays_framework['buy_group_framework'][$key] = $_REQUEST[$key];
 
             //test search arrays (only)) for search data
             switch ($key) {           
               case  'buy_group_prod_cat_incl_array': 
               case  'buy_group_prod_cat_excl_array':   
               case  'buy_group_plugin_cat_incl_array':
               case  'buy_group_plugin_cat_excl_array':
               case  'buy_group_product_incl_array':
               case  'buy_group_product_excl_array':
               case  'buy_group_var_name_incl_array':
               case  'buy_group_var_name_excl_array':
               case  'buy_group_brands_incl_array':
               case  'buy_group_brands_excl_array':
               case  'buy_group_subscriptions_incl_array': 
               case  'buy_group_subscriptions_excl_array': 
               case  'buy_group_role_incl_array':
               case  'buy_group_role_excl_array': 
               case  'buy_group_email_incl_array': 
               case  'buy_group_email_excl_array':
               case  'buy_group_groups_incl_array':
               case  'buy_group_groups_excl_array':
               case  'buy_group_memberships_incl_array':
               case  'buy_group_memberships_excl_array':         
                 if ( ((is_array($vtprd_edit_arrays_framework['buy_group_framework'][$key])) &&
                       (sizeof($vtprd_edit_arrays_framework['buy_group_framework'][$key]) > 0)) 
                              ||
                      ((!is_array($vtprd_edit_arrays_framework['buy_group_framework'][$key])) &&
                       ($vtprd_edit_arrays_framework['buy_group_framework'][$key] > ' ')) ) { //  > ' ' test catches the vargroup list when empty 
                    $buy_groups_found ++;
                    if (strpos($key,'incl') !== false) {
                      $buy_groups_include_test_found = true; //used for setting the 'exclude only' later
                    }
                 }
                 break;
             }

             //test product search arrays (only)) for search data
             switch ($key) {           
               case  'buy_group_prod_cat_incl_array': 
               case  'buy_group_prod_cat_excl_array':   
               case  'buy_group_plugin_cat_incl_array':
               case  'buy_group_plugin_cat_excl_array':
               case  'buy_group_product_incl_array':
               case  'buy_group_product_excl_array':
               case  'buy_group_var_name_incl_array':
               case  'buy_group_var_name_excl_array':
               case  'buy_group_brands_incl_array':
               case  'buy_group_brands_excl_array':
               case  'buy_group_subscriptions_incl_array': 
               case  'buy_group_subscriptions_excl_array':                    
                 if ( ((is_array($vtprd_edit_arrays_framework['buy_group_framework'][$key])) &&
                       (sizeof($vtprd_edit_arrays_framework['buy_group_framework'][$key]) > 0)) 
                          ||
                      ((!is_array($vtprd_edit_arrays_framework['buy_group_framework'][$key])) &&   
                       ($vtprd_edit_arrays_framework['buy_group_framework'][$key] > ' ')) ) { //  > ' ' test catches the vargroup list when empty 
                    $buy_groups_product_selectors_found ++;
             //error_log( print_r(  'Buy Group Selector Found, $key= ' .$key, true ) );
                 }
                 break;
             }
             //test and/or switches, see if they should be turned off or counted
             switch ($key) {           
               case  'buy_group_show_and_or_switches':  //if not advanced, can't show the and/or switches!
                   if ($vtprd_rule->rule_type_select == 'basic') {
                      $vtprd_edit_arrays_framework['buy_group_framework']['buy_group_show_and_or_switches'] = 'no';                               
                   }
                 break;
               case  'buy_group_prod_cat_and_or':
               case  'buy_group_plugin_cat_and_or':
               case  'buy_group_product_and_or':
               case  'buy_group_var_name_and_or':
               case  'buy_group_brands_and_or':
               case  'buy_group_subscriptions_and_or':             
                   if ($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_show_and_or_switches'] == 'no') {
                      $vtprd_edit_arrays_framework['buy_group_framework'][$key] = 'or';
                   } else {          
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') ||
                           ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'each') )  { //'each' is also 'and'!!
                        $and_switch_count++;
                        $and_switch_first_key = $key;
                      }                                
                   }
                 break;
             }

             //test if and/or selected, but data is BLANK
             $and_or_selected_but_no_data = false;
             $and_selected_but_only_excl_data = false;  //v2.0.1.0
             $each_selected_but_insufficient_data = false;
             $and_or_selector_in_error_box2 = false;
             switch ($key) {           
               case  'buy_group_prod_cat_and_or':          
                    if (defined('VTPRD_PRO_VERSION')) { // 'and' / 'each' only allowed in PRO version
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') &&
                           (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_prod_cat_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                          $and_or_selected_but_no_data = true;
                          $and_or_selector_in_error = 'Category';
                          $and_or_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
                          $and_or_selector_in_error_field         = '.buy-prod-category-incl-label'; 
                          $and_or_selector_in_error_box           = '#buy-and-or-selector-prod-cat';
                          $and_or_selector_in_error_field2        = '#buy_group_prod_cat_and_or-AndSelect-label';
                          $and_or_selector_in_error_box2          = '.buy_group_prod_cat_incl'; 
                          //v2.0.1.0 begin
                          if (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_prod_cat_excl_array']) > 0)  {
                            $and_selected_but_only_excl_data = true;                                                                         
                          } 
                          //v2.0.1.0 end                                                                         
                      }
                                           
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'each') &&
                           (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_prod_cat_incl_array']) < 2) )  {
                          $each_selected_but_insufficient_data = true;
                          $and_or_selector_in_error = 'Category';
                          $and_or_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
                          $and_or_selector_in_error_field         = '.buy-prod-category-incl-label';
                          $and_or_selector_in_error_box           = '#buy-and-or-selector-prod-cat';
                          $and_or_selector_in_error_field2        = '#buy_group_prod_cat_each-EachSelect-label';
                          $and_or_selector_in_error_box2          = '.buy_group_prod_cat_incl';                                                 
                      } 
                    }                                                    
                 break;               
               case  'buy_group_plugin_cat_and_or':
                    if (defined('VTPRD_PRO_VERSION')) { // 'and' / 'each' only allowed in PRO version
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') &&
                           (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_plugin_cat_incl_array']) == 0) ) {      //v2.0.1.0 removed excl_array from test
                          $and_or_selected_but_no_data = true;
                          $and_or_selector_in_error = 'Pricing Deals Category';
                          $and_or_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
                          $and_or_selector_in_error_field         = '.buy-plugin-category-incl-label';
                          $and_or_selector_in_error_box           = '#buy-and-or-selector-plugin-cat';
                          $and_or_selector_in_error_field2        = '#buy_group_plugin_cat_and_or-AndSelect-label';
                          $and_or_selector_in_error_box2          = '.buy_group_plugin_cat_incl';                                                         
                          //v2.0.1.0 begin
                          if (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_plugin_cat_excl_array']) > 0)  {
                              $and_selected_but_only_excl_data = true;                                                      
                          }
                          //v2.0.1.0 end
                      } 
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'each') &&
                           (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_plugin_cat_incl_array']) < 2) )  {
                          $each_selected_but_insufficient_data = true;
                          $and_or_selector_in_error = 'Pricing Deals Category';
                          $and_or_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
                          $and_or_selector_in_error_field         = '.buy-plugin-category-incl-label'; 
                          $and_or_selector_in_error_box           = '#buy-and-or-selector-plugin-cat';
                          $and_or_selector_in_error_field2        = '#buy_group_plugin_cat_each-EachSelect-label';
                          $and_or_selector_in_error_box2          = '.buy_group_plugin_cat_incl';                                                  
                      }
                    }                                                     
                 break;               
               case  'buy_group_product_and_or':
                    if (defined('VTPRD_PRO_VERSION')) { // 'and' / 'each' only allowed in PRO version
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') &&
                           (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_product_incl_array']) == 0) ) { //v2.0.1.0 removed excl_array from test
                          $and_or_selected_but_no_data = true;
                          $and_or_selector_in_error = 'Product';
                          $and_or_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
                          $and_or_selector_in_error_field         = '.buy-product-incl-label';
                          $and_or_selector_in_error_box           = '#buy-and-or-selector-product';
                          $and_or_selector_in_error_field2        = '#buy_group_product_and_or-AndSelect-label';
                          $and_or_selector_in_error_box2          = '.buy_group_product_incl';                                                         
                          //v2.0.1.0 begin
                          if (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_product_excl_array']) > 0) {
                              $and_selected_but_only_excl_data = true;                                                        
                          }
                          //v2.0.1.0 end 
                      } 
                      if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'each') &&
                           (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_product_incl_array']) < 2) )  {
                          $each_selected_but_insufficient_data = true;
                          $and_or_selector_in_error = 'Product';
                          $and_or_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
                          $and_or_selector_in_error_field         = '.buy-product-incl-label';
                          $and_or_selector_in_error_box           = '#buy-and-or-selector-product';
                          $and_or_selector_in_error_field2        = '#buy_group_product_each-EachSelect-label';
                          $and_or_selector_in_error_box2          = '.buy_group_product_incl';                                                      
                      } 
                    }                                                   
                 break;               
               case  'buy_group_var_name_and_or':
                    if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') &&
                         ($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_var_name_incl_array'] <= ' ') ) {    //v2.0.1.0 removed excl_array from test
                        $and_or_selected_but_no_data = true;
                        $and_or_selector_in_error = 'Variation Name';
                        $and_or_selector_in_error_insert_before = '.buy-group-var-name-incl-excl-group';
                        $and_or_selector_in_error_field         = '.buy-var-name-incl-label';
                        $and_or_selector_in_error_box           = '#buy-and-or-selector-var-name';
                        $and_or_selector_in_error_field2        = '#buy_group_var_name_and_or-AndSelect-label';
                        $and_or_selector_in_error_box2          = '.buy_group_var_name_incl';                                                      
                        //v2.0.1.0 begin
                        if ($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_var_name_excl_array'] > ' ')  {
                            $and_selected_but_only_excl_data = true;                                                    
                        }
                        //v2.0.1.0 end 
                    }                                
                 break;               
               case  'buy_group_brands_and_or':
                    if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') &&
                         (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_brands_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                        $and_or_selected_but_no_data = true;
                        $and_or_selector_in_error = 'Brands';
                        $and_or_selector_in_error_insert_before = '.buy-group-brands-incl-excl-group';
                        $and_or_selector_in_error_field         = '.buy-brands-incl-label';
                        $and_or_selector_in_error_box           = '#buy-and-or-selector-brands';
                        $and_or_selector_in_error_field2        = '#buy_group_brands_and_or-AndSelect-label';
                        $and_or_selector_in_error_box2          = '.buy_group_brands_incl';                                                      
                        //v2.0.1.0 begin
                        if (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_brands_excl_array']) > 0) { 
                            $and_selected_but_only_excl_data = true;                                                     
                        }
                        //v2.0.1.0 end
                    }                                
                 break;                 
               case  'buy_group_subscriptions_and_or':             
                    if ( ($vtprd_edit_arrays_framework['buy_group_framework'][$key] == 'and') &&
                         (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_subscriptions_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                        $and_or_selected_but_no_data = true;
                        $and_or_selector_in_error = 'Subscriptions';
                        $and_or_selector_in_error_insert_before = '.buy-group-subscriptions-incl-excl-group';
                        $and_or_selector_in_error_field         = '.buy-subscriptions-incl-label';
                        $and_or_selector_in_error_box           = '#buy-and-or-selector-subscriptions'; 
                        $and_or_selector_in_error_field2        = '#buy_group_subscriptions_and_or-AndSelect-label';
                        $and_or_selector_in_error_box2          = '.buy_group_subscriptions_incl'; 
                        //v2.0.1.0 begin
                        if (sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_subscriptions_excl_array']) > 0) {
                            $and_selected_but_only_excl_data = true;                                                    
                        } 
                        //v2.0.1.0 end                                                                           
                    }                                
                 break;                   
             } //end switch
             
             if ($and_or_selected_but_no_data) {
                //v2.0.1.0 begin
                if ($and_selected_but_only_excl_data) {
                    $and_selected_but_only_excl_data_msg = '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em> Only &nbsp; "OR" &nbsp; may be used when &nbsp;  *only*  &nbsp; "EXCLUDE"s &nbsp; selected </em>';
                } else {
                    $and_selected_but_only_excl_data_msg = null;
                }             
                $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                  'error_msg'  => 'The "' .$and_or_selector_in_error. '" &nbsp; "AND/OR" &nbsp; selection is set to "AND". &nbsp;&nbsp;&nbsp; <br><br> When "AND" is selected, the matching "' .$and_or_selector_in_error. '" <br><br>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;    *Include* &nbsp; selector list <br><br>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  **must** &nbsp;&nbsp; have  at least &nbsp; * 1 * &nbsp;  item selected. <br><br>( default &nbsp; "and/or" &nbsp; selection is  "Or" )'  .$and_selected_but_only_excl_data_msg
                  );
                //v2.0.1.0 end 
                $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field;
                $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-product-area';
                $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;
                $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
                $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box2. ' .select2-container--default .select2-selection--multiple';                                  
             } 
           
             if ( ($each_selected_but_insufficient_data) &&
                  ($vtprd_rule->cart_or_catalog_select == 'cart') ) {
                $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                  'error_msg'  => 'The "' .$and_or_selector_in_error. '" &nbsp; "AND/OR/EACH" &nbsp; selection is set to "EACH". &nbsp;&nbsp;&nbsp; <br><br> When "EACH" is selected, the matching "' .$and_or_selector_in_error. '" selector list <br><br>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  **must** &nbsp;&nbsp; have  at least &nbsp; * 2 * &nbsp;  items selected.  <br><br>  Example - &nbsp;&nbsp; Category: &nbsp; [x Hats] &nbsp; [x Shoes] &nbsp; <br><br>( default &nbsp; "and/or/each" &nbsp; selection is  "Or" )'
                  ); 
                $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field;
                $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-product-area';
                $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;
                $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
                $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box2. ' .select2-container--default .select2-selection--multiple';                
             }                           
              
             //IF FREE VERSION, These are in error by definition!!                 
             if (!defined('VTPRD_PRO_VERSION')) {
                $free_selector_in_error = FALSE;
                $free_and_each_selected_in_error = FALSE;
                switch ($key) {
                   case  'buy_group_prod_cat_incl_array'   :
                        $free_selector_in_error = 'Category';
                        $free_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-prod-category-incl-label';
                        $free_selector_in_error_box           = '.buy_group_prod_cat_incl';                                                
                      break;
                   case  'buy_group_prod_cat_excl_array'   : 
                        $free_selector_in_error = 'Exclude Category';
                        $free_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-prod-category-excl-label';
                        $free_selector_in_error_box           = '.buy_group_prod_cat_excl';                        
                      break;                   
                   case  'buy_group_plugin_cat_incl_array' :
                        $free_selector_in_error = 'Pricing Deal Category';
                        $free_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-plugin-category-incl-label';
                        $free_selector_in_error_box           = '.buy_group_plugin_cat_incl';                        
                      break;                   
                   case  'buy_group_plugin_cat_excl_array' :
                        $free_selector_in_error = 'Exclude Pricing Deal Category';
                        $free_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-plugin-category-excl-label';
                        $free_selector_in_error_box           = '.buy_group_plugin_cat_excl';                        
                      break; 
                   case  'buy_group_product_incl_array'    :
                        $free_selector_in_error = 'Product';
                        $free_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-product-incl-label';
                        $free_selector_in_error_box           = '.buy_group_product_incl';                       
                      break;                   
                   case  'buy_group_product_excl_array'    :
                        $free_selector_in_error = 'Exclude Product';
                        $free_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-product-excl-label';
                        $free_selector_in_error_box           = '.buy_group_product_excl';                         
                      break;
                   case  'buy_group_role_incl_array'       :
                        $free_selector_in_error = 'Role';
                        $free_selector_in_error_insert_before = '.buy-group-role-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-role-incl-label';
                        $free_selector_in_error_box           = '.buy_group_role_incl'; 
                      break;                                           
                   case  'buy_group_role_excl_array'       :
                        $free_selector_in_error = 'Exclude Role';
                        $free_selector_in_error_insert_before = '.buy-group-role-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-role-excl-label';
                        $free_selector_in_error_box           = '.buy_group_role_excl';                        
                      break;                        
                   case  'buy_group_email_incl_array'      :
                        $free_selector_in_error = 'Email';
                        $free_selector_in_error_insert_before = '.buy-group-email-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-email-incl-label';
                        $free_selector_in_error_box           = '.buy_group_email_incl';                        
                      break;                    
                   case  'buy_group_email_excl_array'      :
                        $free_selector_in_error = 'Exclude Email';
                        $free_selector_in_error_insert_before = '.buy-group-email-incl-excl-group';
                        $free_selector_in_error_field         = '.buy-email-excl-label';
                        $free_selector_in_error_box           = '.buy_group_email_excl';                        
                      break;
                   case  'buy_group_prod_cat_and_or'      :
                        if ($vtprd_edit_arrays_framework['buy_group_framework'][$key] != 'or') {
                          $free_selector_in_error = 'Category And/Or/Each';
                          $free_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
                          $free_selector_in_error_field         = '#buy_group_prod_cat_and_or-OrSelect-label';
                          $free_selector_in_error_box           = '#buy_group_prod_cat_and_or-OrSelect-label';
                          $free_and_each_selected_in_error      = TRUE;                          
                        }                        
                      break;  
                   case  'buy_group_plugin_cat_and_or'      :
                        if ($vtprd_edit_arrays_framework['buy_group_framework'][$key] != 'or') {
                          $free_selector_in_error = 'Pricing Deal Category And/Or/Each';
                          $free_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';                                                          
                          $free_selector_in_error_field         = '#buy_group_plugin_cat_and_or-OrSelect-label';
                          $free_selector_in_error_box           = '#buy_group_plugin_cat_and_or-OrSelect-label';
                          $free_and_each_selected_in_error      = TRUE;  
                        }                       
                      break; 
                   case  'buy_group_product_and_or'      :
                        if ($vtprd_edit_arrays_framework['buy_group_framework'][$key] != 'or') {
                          $free_selector_in_error = 'Product And/Or/Each';
                          $free_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
                          $free_selector_in_error_field         = '#buy_group_product_and_or-OrSelect-label';
                          $free_selector_in_error_box           = '#buy_group_product_and_or-OrSelect-label';
                          $free_and_each_selected_in_error      = TRUE;                           
                        }                        
                      break;                                                                
                }
                if ($free_selector_in_error) {
                  if ($free_and_each_selected_in_error) {
                    $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => $free_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                      'error_msg'  => '"' .$free_selector_in_error. '" may only be "Or" in the FREE version'
                      ); 
                  } else {
                    $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => $free_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                      'error_msg'  => 'The "' .$free_selector_in_error. '" selector is only available in the PRO version'
                      );                 
                  }
                  $vtprd_rule->rule_error_red_fields[] = $free_selector_in_error_field;
                  $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-product-area';                  
                  if ($free_selector_in_error_box) {
                    if ($free_and_each_selected_in_error) {
                      $vtprd_rule->rule_error_box_fields[] = $free_selector_in_error_box;
                    } else {
                      $vtprd_rule->rule_error_box_fields[] = $free_selector_in_error_box.' .select2-container--default .select2-selection--multiple';
                    }
                  }                                      
                }                 
             } //end if pro  
                  
    //error_log( print_r(  '$key= ' .$key, true ) );
    //error_log( var_export($vtprd_edit_arrays_framework['buy_group_framework'][$key], true ) );
                         
          }  //end if isset  
  
        } //end foreach

        if ($buy_groups_found == 0) {
            $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' =>  '.buy-group-select-product-area',  //errmsg goes before current onscreen line
              'error_msg'  => '"By Category / Role / Product" chosen above, <br><br>but <strong>no selections made in the boxes below</strong>.&nbsp;&nbsp;&nbsp; <br><br>Please make a selection in either the "Select Products" box or "Select Customers" box below. &nbsp;&nbsp;&nbsp;'
              ); 
            $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-product-area';
            $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-customer-area';
            $vtprd_rule->rule_error_box_fields[] = '#popChoiceIn';                 
        }

    //error_log( print_r(  '$buy_groups_product_selectors_found= ' .$buy_groups_product_selectors_found, true ) );
    //error_log( print_r(  '$and_switch_count= ' .$and_switch_count, true ) );
    
        /*  
        //AND is disallowed if only 1 product selector found
        if ( (($buy_groups_product_selectors_found == 1) && ($and_switch_count == 1)) ||
              ($and_switch_count == 1) ) {
             $and_or_selector_in_error = false;
             switch ($and_switch_first_key) {           
               case  'buy_group_prod_cat_and_or':          
                        $and_or_selector_in_error = 'Category';
                        $and_or_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
                        $and_or_selector_in_error_field         = '#buy-and-or-selector-prod-cat';                                                                              
                 break;               
               case  'buy_group_plugin_cat_and_or':
                        $and_or_selector_in_error = 'Pricing Deals Category';
                        $and_or_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
                        $and_or_selector_in_error_field         = '#buy-and-or-selector-plugin-cat';                                                             
                 break;               
               case  'buy_group_product_and_or':
                        $and_or_selector_in_error = 'Product';
                        $and_or_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
                        $and_or_selector_in_error_field         = '#buy-and-or-selector-product';                                                           
                 break;               
               case  'buy_group_var_name_and_or':
                        $and_or_selector_in_error = 'Variation Name';
                        $and_or_selector_in_error_insert_before = '.buy-group-var-name-incl-excl-group';
                        $and_or_selector_in_error_field         = '#buy-and-or-selector-var-name';                                                            
                 break;               
               case  'buy_group_brands_and_or':
                        $and_or_selector_in_error = 'Brands';
                        $and_or_selector_in_error_insert_before = '.buy-group-brands-incl-excl-group';
                        $and_or_selector_in_error_field         = '#buy-and-or-selector-brands';                                                           
                 break;                 
               case  'buy_group_subscriptions_and_or':             
                        $and_or_selector_in_error = 'Subscriptions';
                        $and_or_selector_in_error_insert_before = '.buy-group-subscriptions-incl-excl-group';
                        $and_or_selector_in_error_field         = '#buy-and-or-selector-subscriptions';                                                             
                 break;                   
             } //end switch
             if ($and_or_selector_in_error) {
                 if ($buy_groups_product_selectors_found == 1)  {
                      $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                        'error_msg'  => 'The AND/OR is set to "AND" <br><br>but it should be "OR", <br><br>when the **only** search criteria is "' .$and_or_selector_in_error. '" .'
                        ); 
                 } else { //path for selectors >1, but only one AND
                      $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                        'error_msg'  => 'Within the Select Products area, there is Only 1 AND/OR is set to "AND". <br><br> When "AND" is selected, there must be a minimum of 2 AND/OR set to "AND".'
                        );                  
                 }
                 $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field;
                 $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-product-area'; 
             }               
        } //end disallowed 'and' test  
        */     
        
      } //end groups
      
      $vtprd_edit_arrays_framework['buy_group_framework']['buy_group_and_switch_count'] = $and_switch_count;
      
      if (!$buy_groups_include_test_found) {
        $vtprd_edit_arrays_framework['buy_group_framework']['buy_group_set_to_exclude_only'] = true;
      }
  
      //Either moves the collected data, or if no data, then this re-initalizes the arrays.
      $vtprd_rule->buy_group_population_info = $vtprd_edit_arrays_framework['buy_group_framework'];

      //********************
      //EACH EDITS Round 2
      //********************
      if ( (defined('VTPRD_PRO_VERSION')) //each not allowed in free version...
                &&
         (($vtprd_rule->buy_group_population_info['buy_group_prod_cat_and_or']    == 'each') ||
          ($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_and_or']  == 'each') ||
          ($vtprd_rule->buy_group_population_info['buy_group_product_and_or']     == 'each')) ) {
          
         $each_total_selector_count = 0;
         if ($vtprd_rule->buy_group_population_info['buy_group_prod_cat_and_or']    == 'each') {
            $and_or_error_msg = '"Each" selected in Category And/Or/EACH (above)';
            $and_or_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
            $and_or_selector_in_error_field1         = '.buy-prod-category-incl-label'; 
            $and_or_selector_in_error_field2         = '#buy_group_prod_cat_each-EachSelect-label';
            $and_or_selector_include_count = sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_prod_cat_incl_array']);
            $each_total_selector_count += $and_or_selector_include_count;                   
         } 
         if ($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_and_or']  == 'each') {
            $and_or_error_msg = '"Each" selected in Pricing Deal Category And/Or/EACH (above)';
            $and_or_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
            $and_or_selector_in_error_field1         = '.buy-plugin-category-incl-label'; 
            $and_or_selector_in_error_field2         = '#buy_group_plugin_cat_each-EachSelect-label';
            $and_or_selector_include_count = sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_plugin_cat_incl_array']);
            $each_total_selector_count += $and_or_selector_include_count;                                    
         }                 
         if ($vtprd_rule->buy_group_population_info['buy_group_product_and_or']     == 'each') {
            $and_or_error_msg = '"Each" selected in Product And/Or/EACH (above)';
            $and_or_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
            $and_or_selector_in_error_field1         = '.buy-product-incl-label'; 
            $and_or_selector_in_error_field2         = '#buy_group_product_each-EachSelect-label';
            $and_or_selector_include_count = sizeof($vtprd_edit_arrays_framework['buy_group_framework']['buy_group_product_incl_array']);                                   
            $each_total_selector_count += $and_or_selector_include_count;                                    
         }
         
         //When Each, MUST be 'advanced' and 'applies to ALL'
         if ($vtprd_rule->rule_deal_info[0]['buy_amt_applies_to'] != 'all') {
            $vtprd_rule->rule_type_select = 'advanced'; //make sure the errored field is visible
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#buy_amt_box_appliesto_0',  
                  'error_msg'  => __('Buy  &nbsp; "Group Amount Applies to" &nbsp;  * must * be  &nbsp; "All Products" &nbsp;  when &nbsp; "', 'vtprd') .$and_or_error_msg. __('" &nbsp; selected', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#buy_amt_appliesto_anchor_0';
            $vtprd_rule->rule_error_box_fields[] = '#buy_amt_applies_to_0';
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
            $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;             
         }
         
         if ($vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] != 'none') {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#buy_repeat_box_0',  
                  'error_msg'  => __('Buy  &nbsp; "Rule Usage Count" &nbsp;  * must * be  &nbsp; "Apply Rule Once per Cart" &nbsp;  when &nbsp; "', 'vtprd') .$and_or_error_msg. __('" &nbsp; selected', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#buy_repeat_title_anchor_0';
            $vtprd_rule->rule_error_box_fields[] = '#buy_repeat_condition_0';              
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
            $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;                 
         }
         if ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] != 'quantity') {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#buy_amt_box_0',  
                  'error_msg'  => __('When "', 'vtprd') .$and_or_error_msg
                    .'<br><br>'. __('you are essentially saying "Buy 1 of EACH of the listed items".', 'vtprd') 
                    .'<br><br>'. __('** SO ** at "Group Amount" you must select "Buy Unit Quantity".', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#buy_amt_title_anchor_0';
            $vtprd_rule->rule_error_box_fields[] = '#buy_amt_type_0';
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
            $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;            
         } 
          
         if ( ($vtprd_rule->rule_deal_info[0]['buy_amt_type'] == 'quantity') &&          
               ($vtprd_rule->rule_deal_info[0]['buy_amt_count'] < $each_total_selector_count ) ) {
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '#buy_amt_box_0',  
                  'error_msg'  => __('When "', 'vtprd') .$and_or_error_msg . __('you are essentially saying "Buy 1 of EACH of the listed items".', 'vtprd')  
                    .'<br><br>'. __('SO at "Group Amount" you must select "Buy Unit Quantity".', 'vtprd')
                    .'<br>'. __('And the quantity amount must greater than or equal to the TOTAL number of items selected for EACH selectors together.', 'vtprd')                      
                    .'<br><br>'. __('For Example:', 'vtprd')
                    .'<br>&nbsp;&nbsp;&nbsp;'. __('"Buy 1 of Category A and 1 of Category B, then get a discount".', 'vtprd')
                    .'<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'. __('THEN', 'vtprd')
                    .'<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'. __('the "Buy Unit Quantity" be 2, AND ', 'vtprd')                      
                    .'<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'. __('the Categories select must be ONLY Categories A and B.', 'vtprd') );
            $vtprd_rule->rule_error_red_fields[] = '#buy_amt_title_anchor_0';
            $vtprd_rule->rule_error_box_fields[] = '#buy_amt_type_0';
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
            $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;                           
         } 
      } //end EACH EDITS Round 2
      
      //EACH EDITS Round 3
      //CATALOG rule type may not have EACH selected!
      if ( ($vtprd_rule->cart_or_catalog_select == 'catalog') &&
           (defined('VTPRD_PRO_VERSION')) ) { //other error msg applies in FREE version
         if ($vtprd_rule->buy_group_population_info['buy_group_prod_cat_and_or']    == 'each') {
            $and_or_error_msg = '"Each" selected in Category And/Or/EACH';
            $and_or_selector_in_error_insert_before = '.buy-group-prod-cat-incl-excl-group';
            $and_or_selector_in_error_field1         = '.buy-prod-category-incl-label'; 
            $and_or_selector_in_error_field2         = '#buy_group_prod_cat_each-EachSelect-label';
            
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  
                  'error_msg'  => $and_or_error_msg. ', <br><br>but may only be "And" / "Or" for a CATALOG Deal');
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;                             
         } 
         if ($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_and_or']  == 'each') {
            $and_or_error_msg = '"Each" selected in Pricing Deal Category And/Or/EACH';
            $and_or_selector_in_error_insert_before = '.buy-group-plugin-cat-incl-excl-group';
            $and_or_selector_in_error_field1         = '.buy-plugin-category-incl-label'; 
            $and_or_selector_in_error_field2         = '#buy_group_plugin_cat_each-EachSelect-label';  
            
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  
                  'error_msg'  => $and_or_error_msg. ', <br><br>but may only be "And" / "Or" for a CATALOG Deal');
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;                                              
         }                 
         if ($vtprd_rule->buy_group_population_info['buy_group_product_and_or']     == 'each') {
            $and_or_error_msg = '"Each" selected in Product And/Or/EACH';
            $and_or_selector_in_error_insert_before = '.buy-group-product-incl-excl-group';
            $and_or_selector_in_error_field1         = '.buy-product-incl-label'; 
            $and_or_selector_in_error_field2         = '#buy_group_product_each-EachSelect-label';
            
            $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  
                  'error_msg'  => $and_or_error_msg. ', <br><br>but may only be "And" / "Or" for a CATALOG Deal');
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field1;
            $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
         }                  
      } //end EACH EDITS Round 3
                


    //error_log( print_r(  'buy_group_population_info after load 001 = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) );      

      if ($vtprd_rule->inPop == 'groups') {     
        //EDIT FOR DUPLICATES amongst the include/exclude lists                                       
        foreach ($vtprd_edit_arrays_framework['buy_group_array_duplicate_edits'] as $dup_edit_row) {   
          $compareA = $dup_edit_row['compareA'];
          $compareB = $dup_edit_row['compareB'];
  
          $result = array_intersect($vtprd_edit_arrays_framework['buy_group_framework'][$compareA],$vtprd_edit_arrays_framework['buy_group_framework'][$compareB]);   
  
          if ( sizeof($result) > 0 ) {
            $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' =>  $dup_edit_row['insert_error_before_selector'],  //errmsg goes before current onscreen line
              'error_msg'  => $dup_edit_row['error_msg']
              );
            foreach ($dup_edit_row['error_fields'] as $error_field) {
              $vtprd_rule->rule_error_red_fields[] = $error_field;
            }  
          }                            
        }
      } //end if 
      
      if ($vtprd_rule->buy_group_population_info['buy_group_customer_and_or'] == 'and') {
         if ( ( sizeof($vtprd_rule->buy_group_population_info['buy_group_role_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_role_excl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_email_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_email_excl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_groups_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_groups_excl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_memberships_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_memberships_excl_array']) == 0 ) ) {
           $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.buy-group-and-or', 
                  'error_msg'  => __('If "AND" selected, <br><br>&nbsp;&nbsp;&nbsp;You must fill in a  "Role (Wholesale) / Email / Customer Name / Group / Membership"&nbsp; selection &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br><br>(or select "OR")', 'vtprd') 
                  );                                                      
           $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-customer-area';
           $vtprd_rule->rule_error_red_fields[] = '#and-select-field';
           $vtprd_rule->rule_error_red_fields[] = '#and-message-field';               
         }     
      }
      
      /* v2.0.2.0  no longer valid  -  full combination of includes and excludes now supported.
      if ($vtprd_rule->buy_group_population_info['buy_group_customer_and_or'] == 'and') {
         if ( (( sizeof($vtprd_rule->buy_group_population_info['buy_group_role_incl_array']) == 0 ) &&
               ( sizeof($vtprd_rule->buy_group_population_info['buy_group_email_incl_array']) == 0 ) &&
               ( sizeof($vtprd_rule->buy_group_population_info['buy_group_groups_incl_array']) == 0 ) &&
               ( sizeof($vtprd_rule->buy_group_population_info['buy_group_memberships_incl_array']) == 0 ))
                      &&
              (( sizeof($vtprd_rule->buy_group_population_info['buy_group_role_excl_array']) > 0 ) ||
               ( sizeof($vtprd_rule->buy_group_population_info['buy_group_email_excl_array']) > 0 ) ||
               ( sizeof($vtprd_rule->buy_group_population_info['buy_group_groups_excl_array']) > 0 ) ||
               ( sizeof($vtprd_rule->buy_group_population_info['buy_group_memberships_excl_array']) > 0 )) ) {
           $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.buy-group-and-or', 
                  'error_msg'  => __('If "AND" selected and only EXclude selectors filled in <br><br>&nbsp;&nbsp;&nbsp;you must select "OR"', 'vtprd') 
                  );                                                      
           $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-customer-area';
           $vtprd_rule->rule_error_red_fields[] = '#and-select-field';
           $vtprd_rule->rule_error_red_fields[] = '#and-message-field';               
         }     
      }


      if ($vtprd_rule->buy_group_population_info['buy_group_customer_and_or'] == 'and') {
         if ( ( sizeof($vtprd_rule->buy_group_population_info['buy_group_prod_cat_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_product_incl_array']) == 0 ) &&
              ( $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'] <= ' ' ) &&   //will be spaces if no contents!!
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_brands_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_subscriptions_incl_array']) == 0 ) ) {
           $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => '.buy-group-and-or', 
                  'error_msg'  => __('If "AND" selected, <br><br>&nbsp;&nbsp;&nbsp;You must fill in a <br><br> "Select Products:   by Category / Product / Variation Name across Products / Brands" <br><br> ** inclusion **  selection in the box above &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br><br>(or select "OR")', 'vtprd') 
                  );                                                      
           $vtprd_rule->rule_error_box_fields[] = '.buy-group-select-customer-area';
           $vtprd_rule->rule_error_red_fields[] = '#and-select-field';
           $vtprd_rule->rule_error_red_fields[] = '#and-message-field';               
         }     
      }
      */      
      
      //v2.0.2.0 begin
      /* 1. Support **pre-assesment** of any combination of product group and customer group found/include/exclude
      PLUS  And/or, except, of course, for no selections
         2. make the include/exclude processing in apply-rules.php faster by assessing the sections above as a total entity 
      */ 
      if ( (sizeof($vtprd_rule->rule_error_message) == 0) &&
           ($vtprd_rule->inPop == 'groups') ) {

          //PRODUCTS
          if ( ( sizeof($vtprd_rule->buy_group_population_info['buy_group_prod_cat_incl_array']) == 0 ) &&
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_incl_array']) == 0 ) &&
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_product_incl_array']) == 0 ) &&
                ( $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_brands_incl_array']) == 0 ) &&
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_subscriptions_incl_array']) == 0 ) ) {
            $buy_group_products_include_found = false;  
          } else {
            $buy_group_products_include_found = true;  
          }
          if ( ( sizeof($vtprd_rule->buy_group_population_info['buy_group_prod_cat_excl_array']) == 0 ) &&
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_plugin_cat_excl_array']) == 0 ) &&
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_product_excl_array']) == 0 ) &&
                ( $vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_brands_excl_array']) == 0 ) &&
                ( sizeof($vtprd_rule->buy_group_population_info['buy_group_subscriptions_excl_array']) == 0 ) ) {
            $buy_group_products_exclude_found = false;  
          } else {
            $buy_group_products_exclude_found = true;  
          }
          //values: includeOnly || excludeOnly || both (include and exclude found) || none = neither include nor exclude found 
          switch (TRUE) {
            case ($buy_group_products_include_found & $buy_group_products_exclude_found) :  $vtprd_rule->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'both'; break;
            case ($buy_group_products_include_found) :  $vtprd_rule->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'includeOnly'; break;
            case ($buy_group_products_exclude_found) :  $vtprd_rule->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'excludeOnly'; break;
            default: $vtprd_rule->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'none'; break;
          } 
                    
          //CUSTOMER        
          if (( sizeof($vtprd_rule->buy_group_population_info['buy_group_role_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_email_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_groups_incl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_memberships_incl_array']) == 0 )) {          
            $buy_group_customer_include_found = false;  
          } else {
            $buy_group_customer_include_found = true;  
          }
          if (( sizeof($vtprd_rule->buy_group_population_info['buy_group_role_excl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_email_excl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_groups_excl_array']) == 0 ) &&
              ( sizeof($vtprd_rule->buy_group_population_info['buy_group_memberships_excl_array']) == 0 )) {          
            $buy_group_customer_exclude_found = false;  
          } else {
            $buy_group_customer_exclude_found = true;  
          }          
          //values: includeOnly || excludeOnly || both (include and exclude found) || none = neither include nor exclude found 
          switch (TRUE) {
            case ($buy_group_customer_include_found & $buy_group_customer_exclude_found) :  $vtprd_rule->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'both'; break;
            case ($buy_group_customer_include_found) :  $vtprd_rule->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'includeOnly'; break;
            case ($buy_group_customer_exclude_found) :  $vtprd_rule->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'excludeOnly'; break;
            default: $vtprd_rule->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'none'; break;
          }             

                   //error_log( print_r(  '$vtprd_rule->inPop = ' .$vtprd_rule->inPop , true ) );
                   //error_log( print_r(  '$buy_group_products_include_found = ' .$buy_group_products_include_found , true ) );
                   //error_log( print_r(  '$buy_group_products_exclude_found = ' .$buy_group_products_exclude_found , true ) );
                   //error_log( print_r(  'buy_group_products_set_to_include_exclude_both_none = ' .$vtprd_rule->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] , true ) );
                   //error_log( print_r(  '$buy_group_customer_include_found = ' .$buy_group_customer_include_found , true ) );
                   //error_log( print_r(  '$buy_group_customer_exclude_found = ' .$buy_group_customer_exclude_found , true ) );
                   //error_log( print_r(  'buy_group_customer_set_to_include_exclude_both_none = ' .$vtprd_rule->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] , true ) );            
      }       

      //v2.0.2.0 end 
      
      //ACTIONPOP
      $action_groups_found = 0;
      $and_switch_count = 0; 
      
      $action_groups_include_test_found = false; //used for setting the 'exclude only' later
            
      if ($vtprd_rule->actionPop == 'groups') {     
        $action_arrays_framework = $vtprd_edit_arrays_framework['action_group_framework'];
        foreach( $action_arrays_framework as $key => $value ) {  
           
           if (isset($_REQUEST[$key])) {
              $vtprd_edit_arrays_framework['action_group_framework'][$key] = $_REQUEST[$key];
              
              //test product search arrays (only)) for search data
             switch ($key) {           
               case  'action_group_prod_cat_incl_array': 
               case  'action_group_prod_cat_excl_array':   
               case  'action_group_plugin_cat_incl_array':
               case  'action_group_plugin_cat_excl_array':
               case  'action_group_product_incl_array':
               case  'action_group_product_excl_array':
               case  'action_group_var_name_incl_array':
               case  'action_group_var_name_excl_array':
               case  'action_group_brands_incl_array':
               case  'action_group_brands_excl_array':
               case  'action_group_subscriptions_incl_array': 
               case  'action_group_subscriptions_excl_array':         
                 if ( ((is_array($vtprd_edit_arrays_framework['action_group_framework'][$key])) &&
                       (sizeof($vtprd_edit_arrays_framework['action_group_framework'][$key]) > 0))    
                          ||
                      ((!is_array($vtprd_edit_arrays_framework['action_group_framework'][$key])) &&   
                       ($vtprd_edit_arrays_framework['action_group_framework'][$key] > ' ')) ) { //  > ' ' test catches the vargroup list when empty 
                    $action_groups_found ++;
                    if (strpos($key,'incl') !== false) {
                      $action_groups_include_test_found = true; //used for setting the 'exclude only' later
                    }                    
                 }
                 break;
             }   

              //test and/or switches, see if they should be turned off or counted
              switch ($key) {
                 case  'action_group_show_and_or_switches':  //if not advanced, can't show the and/or switches!
                       if ($vtprd_rule->rule_type_select == 'basic') {
                          $vtprd_edit_arrays_framework['action_group_framework']['action_group_show_and_or_switches'] = 'no';                               
                       }
                     break;                         
                 case  'action_group_prod_cat_and_or':
                 case  'action_group_plugin_cat_and_or':
                 case  'action_group_product_and_or':
                 case  'action_group_var_name_and_or':
                 case  'action_group_brands_and_or':
                 case  'action_group_subscriptions_and_or':             
                     if ($vtprd_edit_arrays_framework['action_group_framework']['action_group_show_and_or_switches'] == 'no') {
                        $vtprd_edit_arrays_framework['action_group_framework'][$key] = 'or';
                     } else {          
                        if ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') {
                          $and_switch_count++;
                          $and_switch_first_key = $key;
                        }                                
                     }
                   break;
              }


              //test if and/or selected, but data is BLANK
              $and_or_selected_but_no_data = false;
              $and_selected_but_only_excl_data = false;  //v2.0.1.0 
              switch ($key) {           
               case  'action_group_prod_cat_and_or':          
                    if (defined('VTPRD_PRO_VERSION')) { //and only allowed in PRO version
                      if ( ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') &&
                           (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_prod_cat_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                          $and_or_selected_but_no_data = true;
                          $and_or_selector_in_error = 'Category';
                          $and_or_selector_in_error_insert_before = '.action-group-prod-cat-incl-excl-group';
                          $and_or_selector_in_error_field         = '.action-prod-category-incl-label';
                          $and_or_selector_in_error_box           = '#action-and-or-selector-prod-cat';
                          $and_or_selector_in_error_field2        = '#action_group_prod_cat_and_or-AndSelect-label';
                          $and_or_selector_in_error_box2          = '.action_group_prod_cat_incl';                                                        
                          //v2.0.1.0 begin
                          if (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_prod_cat_excl_array']) > 0)  {
                              $and_selected_but_only_excl_data = true;                                                       
                          }
                          //v2.0.1.0 end
                      }
                     }                                
                 break;               
               case  'action_group_plugin_cat_and_or':
                    if (defined('VTPRD_PRO_VERSION')) { //and  only allowed in PRO version
                      if ( ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') &&
                           (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_plugin_cat_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                          $and_or_selected_but_no_data = true;
                          $and_or_selector_in_error = 'Pricing Deals Category';
                          $and_or_selector_in_error_insert_before = '.action-group-plugin-cat-incl-excl-group';
                          $and_or_selector_in_error_field         = '.action-plugin-category-incl-label';
                          $and_or_selector_in_error_box           = '#action-and-or-selector-plugin-cat';
                          $and_or_selector_in_error_field2        = '#action_group_plugin_cat_and_or-AndSelect-label';
                          $and_or_selector_in_error_box2          = '.action_group_plugin_cat_incl';                                                          
                          //v2.0.1.0 begin
                          if (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_plugin_cat_excl_array']) > 0) {
                              $and_selected_but_only_excl_data = true;                                                         
                          }
                          //v2.0.1.0 end
                      } 
                    }                               
                 break;               
               case  'action_group_product_and_or':
                    if (defined('VTPRD_PRO_VERSION')) { //and or each only allowed in PRO version
                      if ( ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') &&
                           (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_product_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                          $and_or_selected_but_no_data = true;
                          $and_or_selector_in_error = 'Product';
                          $and_or_selector_in_error_insert_before = '.action-group-product-incl-excl-group';
                          $and_or_selector_in_error_field         = '.action-product-incl-label';
                          $and_or_selector_in_error_box           = '#action-and-or-selector-product';
                          $and_or_selector_in_error_field2        = '#action_group_product_and_or-AndSelect-label';
                          $and_or_selector_in_error_box2          = '.action_group_product_incl';                                                        
                          //v2.0.1.0 begin
                          if (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_product_excl_array']) > 0) {
                              $and_selected_but_only_excl_data = true;                                                       
                          } 
                          //v2.0.1.0 end
                      } 
                    }                               
                 break;               
               case  'action_group_var_name_and_or':
                    if ( ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') &&
                         ($vtprd_edit_arrays_framework['action_group_framework']['action_group_var_name_incl_array'] <= ' ') ) {    //v2.0.1.0 removed excl_array from test
                        $and_or_selected_but_no_data = true;
                        $and_or_selector_in_error = 'Variation Name';
                        $and_or_selector_in_error_insert_before = '.action-group-var-name-incl-excl-group';
                        $and_or_selector_in_error_field         = '.action-var-name-incl-label';
                        $and_or_selector_in_error_box           = '#action-and-or-selector-var-name';
                        $and_or_selector_in_error_field2        = '#action_group_var_name_and_or-AndSelect-label';
                        $and_or_selector_in_error_box2          = '.action_group_var_name_incl';                                                      
                        //v2.0.1.0 begin
                        if ($vtprd_edit_arrays_framework['action_group_framework']['action_group_var_name_excl_array'] > ' ')  {
                            $and_selected_but_only_excl_data = true;                                                     
                        }
                        //v2.0.1.0 end
                    }                                
                 break;               
               case  'action_group_brands_and_or':
                    if ( ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') &&
                         (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_brands_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                        $and_or_selected_but_no_data = true;
                        $and_or_selector_in_error = 'Brands';
                        $and_or_selector_in_error_insert_before = '.action-group-brands-incl-excl-group';
                        $and_or_selector_in_error_field         = '.action-brands-incl-label';
                        $and_or_selector_in_error_box           = '#action-and-or-selector-brands';
                        $and_or_selector_in_error_field2        = '#action_group_brands_and_or-AndSelect-label';
                        $and_or_selector_in_error_box2          = '.action_group_brands_incl';                                                       
                        //v2.0.1.0 begin
                        if (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_brands_excl_array']) > 0)  {
                            $and_selected_but_only_excl_data = true;                                                     
                        }
                        //v2.0.1.0 end
                    }                                
                 break;                 
               case  'action_group_subscriptions_and_or':             
                    if ( ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') &&
                         (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_subscriptions_incl_array']) == 0) ) {    //v2.0.1.0 removed excl_array from test
                        $and_or_selected_but_no_data = true;
                        $and_or_selector_in_error = 'Subscriptions';
                        $and_or_selector_in_error_insert_before = '.action-group-subscriptions-incl-excl-group';
                        $and_or_selector_in_error_field         = '.action-subscriptions-incl-label';
                        $and_or_selector_in_error_box           = '#action-and-or-selector-subscriptions';
                        $and_or_selector_in_error_field2        = '#action_group_subscriptions_and_or-AndSelect-label';
                        $and_or_selector_in_error_box2          = '.action_group_subscriptions_incl';                                                      
                        //v2.0.1.0 begin
                        if (sizeof($vtprd_edit_arrays_framework['action_group_framework']['action_group_subscriptions_excl_array']) > 0) {
                            $and_selected_but_only_excl_data = true;                                                     
                        }
                        //v2.0.1.0 end
                    }                                
                 break;                   
              } //end switch
             if ($and_or_selected_but_no_data) {
                //v2.0.1.0 begin
                if ($and_selected_but_only_excl_data) {
                    $and_selected_but_only_excl_data_msg = '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em> Only &nbsp; "OR" &nbsp; may be used when  &nbsp;  *only* &nbsp; "EXCLUDE"s &nbsp; selected </em>';
                } else {
                    $and_selected_but_only_excl_data_msg = null;
                }
                $vtprd_rule->rule_error_message[] = array( 
                  'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                  'error_msg'  => 'The "' .$and_or_selector_in_error. '" &nbsp; "AND/OR" &nbsp; selection is set to "AND". &nbsp;&nbsp;&nbsp; <br><br> When "AND" is selected, the matching "' .$and_or_selector_in_error. '" <br><br>   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   *Include* &nbsp; selector list<br><br>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  **must** &nbsp;&nbsp; have  at least &nbsp; * 1 * &nbsp;  item selected. <br><br>( default &nbsp; "and/or" &nbsp; selection is  "Or" )'  .$and_selected_but_only_excl_data_msg
                  ); 
                //v2.0.1.0 end                  
                $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field;
                $vtprd_rule->rule_error_box_fields[] = '.action-group-select-product-area';
                $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box;
                $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field2;
                $vtprd_rule->rule_error_box_fields[] = $and_or_selector_in_error_box2. ' .select2-container--default .select2-selection--multiple';                                  
             }  
                          
             //IF FREE VERSION, These are in error by definition!! 
             if (!defined('VTPRD_PRO_VERSION')) {
                $free_selector_in_error = FALSE;
                $free_and_each_selected_in_error = FALSE;
                switch ($key) {
                   case  'action_group_prod_cat_incl_array'   :
                        $free_selector_in_error = 'Category';
                        $free_selector_in_error_insert_before = '.action-group-prod-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.action-prod-category-incl-label';
                        $free_selector_in_error_box           = '.action_group_prod_cat_incl';                        
                      break;
                   case  'action_group_prod_cat_excl_array'   : 
                        $free_selector_in_error = 'Exclude Category';
                        $free_selector_in_error_insert_before = '.action-group-prod-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.action-prod-category-excl-label'; 
                        $free_selector_in_error_box           = '.action_group_prod_cat_excl';                       
                      break;                   
                   case  'action_group_plugin_cat_incl_array' :
                        $free_selector_in_error = 'Pricing Deal Category';
                        $free_selector_in_error_insert_before = '.action-group-plugin-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.action-plugin-category-incl-label';
                        $free_selector_in_error_box           = '.action_group_plugin_cat_incl';                        
                      break;                   
                   case  'action_group_plugin_cat_excl_array' :
                        $free_selector_in_error = 'Exclude Pricing Deal Category';
                        $free_selector_in_error_insert_before = '.action-group-plugin-cat-incl-excl-group';
                        $free_selector_in_error_field         = '.action-plugin-category-excl-label'; 
                        $free_selector_in_error_box           = '.action_group_plugin_cat_excl';                       
                      break; 
                   case  'action_group_product_incl_array'    :
                        $free_selector_in_error = 'Product';
                        $free_selector_in_error_insert_before = '.action-group-product-incl-excl-group';
                        $free_selector_in_error_field         = '.action-product-incl-label';
                        $free_selector_in_error_box           = '.action_group_product_cat_incl';                       
                      break;                   
                   case  'action_group_product_excl_array'    :
                        $free_selector_in_error = 'Exclude Product';
                        $free_selector_in_error_insert_before = '.action-group-product-incl-excl-group';
                        $free_selector_in_error_field         = '.action-product-excl-label';
                        $free_selector_in_error_box           = '.action_group_product_cat_excl';                         
                      break;
                   case  'action_group_prod_cat_and_or'      :
                        if ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') {
                          $free_selector_in_error = 'Category And/Or';
                          $free_selector_in_error_insert_before = '.action-group-prod-cat-incl-excl-group';
                          $free_selector_in_error_field         = '#action_group_prod_cat_and_or-OrSelect-label';
                          $free_selector_in_error_box           = '#action_group_prod_cat_and_or-OrSelect-label';
                          $free_and_each_selected_in_error      = TRUE;  
                        }                       
                      break;  
                   case  'action_group_plugin_cat_and_or'      :
                        if ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') {
                          $free_selector_in_error = 'Pricing Deal Category And/Or';
                          $free_selector_in_error_insert_before = '.action-group-plugin-cat-incl-excl-group';
                          $free_selector_in_error_field         = '#action_group_plugin_cat_and_or-OrSelect-label';
                          $free_selector_in_error_box           = '#action_group_plugin_cat_and_or-OrSelect-label';
                          $free_and_each_selected_in_error      = TRUE;                           
                        }                        
                      break; 
                   case  'action_group_product_and_or'      :
                        if ($vtprd_edit_arrays_framework['action_group_framework'][$key] == 'and') {
                          $free_selector_in_error = 'Product And/Or';
                          $free_selector_in_error_insert_before = '.action-group-product-incl-excl-group';
                          $free_selector_in_error_field         = '#action_group_product_and_or-OrSelect-label';
                          $free_selector_in_error_box           = '#action_group_product_and_or-OrSelect-label';
                          $free_and_each_selected_in_error      = TRUE;                           
                        }                        
                      break;                         
                }
                
                if ($free_selector_in_error) {
                 if ($free_and_each_selected_in_error) {
                    $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => $free_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                      'error_msg'  => '"' .$free_selector_in_error. '" may only be "Or" in the FREE version'
                      ); 
                  } else {
                    $vtprd_rule->rule_error_message[] = array( 
                      'insert_error_before_selector' => $free_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                      'error_msg'  => 'The "' .$free_selector_in_error. '" selector is only available in the PRO version'
                      );                 
                  } 
                  $vtprd_rule->rule_error_red_fields[] = $free_selector_in_error_field;
                  $vtprd_rule->rule_error_box_fields[] = '.action-group-select-product-area';
                  if ($free_selector_in_error_box) {
                    if ($free_and_each_selected_in_error) {
                      $vtprd_rule->rule_error_box_fields[] = $free_selector_in_error_box;
                    } else {
                      $vtprd_rule->rule_error_box_fields[] = $free_selector_in_error_box.' .select2-container--default .select2-selection--multiple';
                    }
                  }                                                      
                }

              }  //end if pro                
            } //end if isset                              
        } //end foreach
               

        if ($action_groups_found == 0) {
            $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' =>  '.action-group-select-product-area',  //errmsg goes before current onscreen line
              'error_msg'  => '"By Category / Product / Variation / Variation Name" chosen above, <br><br>but <strong>no selections made in the box below</strong>.&nbsp;&nbsp;&nbsp;  <br><br>Please make a selection.'
              );
            $vtprd_rule->rule_error_box_fields[] = '.action-group-select-product-area';
            $vtprd_rule->rule_error_box_fields[] = '#popChoiceOut';   
        }
    
        /*
        //AND is disallowed if only 1 product selector found
        if (($action_groups_found == 1) && ($and_switch_count == 1)){
             $and_or_selector_in_error = false;
             switch ($and_switch_first_key) {           
               case  'action_group_prod_cat_and_or':          
                        $and_or_selector_in_error = 'Category';
                        $and_or_selector_in_error_insert_before = '.action-group-prod-cat-incl-excl-group';
                        $and_or_selector_in_error_field         = '#action-and-or-selector-prod-cat';                                                                              
                 break;               
               case  'action_group_plugin_cat_and_or':
                        $and_or_selector_in_error = 'Pricing Deals Category';
                        $and_or_selector_in_error_insert_before = '.action-group-plugin-cat-incl-excl-group';
                        $and_or_selector_in_error_field         = '#action-and-or-selector-plugin-cat';                                                             
                 break;               
               case  'action_group_product_and_or':
                        $and_or_selector_in_error = 'Product';
                        $and_or_selector_in_error_insert_before = '.action-group-product-incl-excl-group';
                        $and_or_selector_in_error_field         = '#action-and-or-selector-product';                                                           
                 break;               
               case  'action_group_var_name_and_or':
                        $and_or_selector_in_error = 'Variation Name';
                        $and_or_selector_in_error_insert_before = '.action-group-var-name-incl-excl-group';
                        $and_or_selector_in_error_field         = '#action-and-or-selector-var-name';                                                            
                 break;               
               case  'action_group_brands_and_or':
                        $and_or_selector_in_error = 'Brands';
                        $and_or_selector_in_error_insert_before = '.action-group-brands-incl-excl-group';
                        $and_or_selector_in_error_field         = '#action-and-or-selector-brands';                                                           
                 break;                 
               case  'action_group_subscriptions_and_or':             
                        $and_or_selector_in_error = 'Subscriptions';
                        $and_or_selector_in_error_insert_before = '.action-group-subscriptions-incl-excl-group';
                        $and_or_selector_in_error_field         = '#action-and-or-selector-subscriptions';                                                             
                 break;                   
             } //end switch
             if ($and_or_selector_in_error) {
                 if ($action_groups_found== 1)  {
                      $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                        'error_msg'  => 'The AND/OR is set to "AND" <br><br>but it should be "OR", <br><br>when the **only** search criteria is "' .$and_or_selector_in_error. '" .'
                        ); 
                 } else { //path for selectors >1, but only one AND
                      $vtprd_rule->rule_error_message[] = array( 
                        'insert_error_before_selector' => $and_or_selector_in_error_insert_before,  //errmsg goes before current onscreen line
                        'error_msg'  => 'Within the Select Products area, there is Only 1 AND/OR is set to "AND". <br><br> When "AND" is selected, there must be a minimum of 2 AND/OR set to "AND".'
                        );                  
                 }
                 $vtprd_rule->rule_error_red_fields[] = $and_or_selector_in_error_field;
                 $vtprd_rule->rule_error_box_fields[] = '.action-group-select-product-area'; 
             }               
         } //end disallowed 'and' test
         */       
                            
      } //end groups
  
  
      $vtprd_edit_arrays_framework['action_group_framework']['action_group_and_switch_count'] = $and_switch_count;
      
      if (!$action_groups_include_test_found) {
        $vtprd_edit_arrays_framework['action_group_framework']['action_group_set_to_exclude_only'] = true;
      }
             
      //Either moves the collected data, or if no data, then this re-initalizes the arrays.
      $vtprd_rule->action_group_population_info = $vtprd_edit_arrays_framework['action_group_framework'];
 
    //error_log( print_r(  'action_group_population_info after load 002 = ', true ) );
    //error_log( var_export($vtprd_rule->action_group_population_info, true ) );      

      if ($vtprd_rule->actionPop == 'groups') {
        //EDIT FOR DUPLICATES                                         
        foreach ($vtprd_edit_arrays_framework['action_group_array_duplicate_edits'] as $dup_edit_row) {   
          $compareA = $dup_edit_row['compareA'];
          $compareB = $dup_edit_row['compareB'];
  
          $result = array_intersect($vtprd_edit_arrays_framework['action_group_framework'][$compareA],$vtprd_edit_arrays_framework['action_group_framework'][$compareB]);   
  
          if ( sizeof($result) > 0 ) {
            
            $vtprd_rule->rule_error_message[] = array( 
              'insert_error_before_selector' =>  $dup_edit_row['insert_error_before_selector'],  //errmsg goes before current onscreen line
              'error_msg'  => $dup_edit_row['error_msg']
              );
            foreach ($dup_edit_row['error_fields'] as $error_field) {
              $vtprd_rule->rule_error_red_fields[] = $error_field;
            }  
          }                            
        }

        //v2.0.2.0 begin
        /* 1. Support **pre-assesment** of any combination of product group and customer group found/include/exclude
        PLUS  And/or, except, of course, for no selections
           2. make the include/exclude processing in apply-rules.php faster by assessing the sections above as a total entity 
        */ 
        //error_log( print_r(  '$vtprd_rule->actionPop = ' .$vtprd_rule->actionPop , true ) );
        //$errMsgCnt = sizeof($vtprd_rule->rule_error_message);
        //error_log( print_r(  '$errMsgCnt = ' .$errMsgCnt , true ) );
        //error_log( print_r(  'action_group_population_info ARRAY = ', true ) );
        //error_log( var_export($vtprd_rule->action_group_population_info, true ) );
        
        //'sameAsInPop' does not need processing, as apply-rules.php follows the inpop processing result, prior to these switches being reviewed.
        if ( (sizeof($vtprd_rule->rule_error_message) == 0) &&
             ($vtprd_rule->actionPop == 'groups') ) {
  
            //PRODUCTS
            if ( ( sizeof($vtprd_rule->action_group_population_info['action_group_prod_cat_incl_array']) == 0 ) &&
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_plugin_cat_incl_array']) == 0 ) &&
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_product_incl_array']) == 0 ) &&
                  ( $vtprd_rule->action_group_population_info['action_group_var_name_incl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_brands_incl_array']) == 0 ) &&
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_subscriptions_incl_array']) == 0 ) ) {
              $action_group_products_include_found = false;  
            } else {
              $action_group_products_include_found = true;  
            }
            if ( ( sizeof($vtprd_rule->action_group_population_info['action_group_prod_cat_excl_array']) == 0 ) &&
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_plugin_cat_excl_array']) == 0 ) &&
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_product_excl_array']) == 0 ) &&
                  ( $vtprd_rule->action_group_population_info['action_group_var_name_excl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_brands_excl_array']) == 0 ) &&
                  ( sizeof($vtprd_rule->action_group_population_info['action_group_subscriptions_excl_array']) == 0 ) ) {
              $action_group_products_exclude_found = false;  
            } else {
              $action_group_products_exclude_found = true;  
            }
            //values: includeOnly || excludeOnly || both (include and exclude found) || none = neither include nor exclude found 
            switch (TRUE) {
              case ($action_group_products_include_found & $action_group_products_exclude_found) :  $vtprd_rule->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'both'; break;
              case ($action_group_products_include_found) :  $vtprd_rule->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'includeOnly'; break;
              case ($action_group_products_exclude_found) :  $vtprd_rule->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'excludeOnly'; break;
              default: $vtprd_rule->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'none'; break;
            } 
 
         //error_log( print_r(  '$vtprd_rule->actionPop = ' .$vtprd_rule->actionPop , true ) );
         //error_log( print_r(  '$action_group_products_include_found = ' .$action_group_products_include_found , true ) );
         //error_log( print_r(  '$action_group_products_exclude_found = ' .$action_group_products_exclude_found , true ) );
         //error_log( print_r(  'action_group_products_set_to_include_exclude_both_none = ' .$vtprd_rule->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] , true ) );

        }              
        //v2.0.2.0 end

      } //end if     
   
      //*********************************************
      //translate varName strings into arrays.
      //*********************************************

      if ($vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array']) {
         if (strpos($vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'],$vtprd_info['default_by_varname_msg_warning']) !== false) {
           $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'] = ' ';     
         } else {
            $varName_string = $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'];
            $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'] = $this->vtprd_build_varName_array($varName_string);             
         }
      }
      if ($vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array']) {
         if (strpos($vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'],$vtprd_info['default_by_varname_msg_warning']) !== false) {
           $vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'] = ' ';     
         } else {
            $varName_string = $vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'];
            $vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'] = $this->vtprd_build_varName_array($varName_string);             
         }
      }
      if ($vtprd_rule->action_group_population_info['action_group_var_name_incl_array']) {
         if (strpos($vtprd_rule->action_group_population_info['action_group_var_name_incl_array'],$vtprd_info['default_by_varname_msg_warning']) !== false) {
           $vtprd_rule->action_group_population_info['action_group_var_name_incl_array'] = ' ';     
         } else {
            $varName_string = $vtprd_rule->action_group_population_info['action_group_var_name_incl_array'];
            $vtprd_rule->action_group_population_info['action_group_var_name_incl_array'] = $this->vtprd_build_varName_array($varName_string);             
         }
      }
      if ($vtprd_rule->action_group_population_info['action_group_var_name_excl_array']) {
         if (strpos($vtprd_rule->action_group_population_info['action_group_var_name_excl_array'],$vtprd_info['default_by_varname_msg_warning']) !== false) {
           $vtprd_rule->action_group_population_info['action_group_var_name_excl_array'] = ' ';     
         } else {
            $varName_string = $vtprd_rule->action_group_population_info['action_group_var_name_excl_array'];
            $vtprd_rule->action_group_population_info['action_group_var_name_excl_array'] = $this->vtprd_build_varName_array($varName_string);             
         }
      }      
      //-----------------------------  
  

    //error_log( print_r(  'buy_group_population_info after load 002 = ', true ) );
    //error_log( var_export($vtprd_rule->buy_group_population_info, true ) );      
      
               
  //error_log( print_r(  '$vtprd_rule at ARRAY LOAD TIME', true ) );
  //error_log( var_export($vtprd_rule, true ) );
      
  //TEST TEST TEST   
  //return count for later use in editing
  return array('buy_groups_found' => $buy_groups_found,'action_groups_found' => $action_groups_found);
  
  }

  /* ************************************************
  **   v2.0.0 NEW FUNCTION
  *************************************************** */
  //change formatted varName string into a varName array
  public function vtprd_build_varName_array($varName_string) {
    $varName_exploded = explode( '|', $varName_string );
    $varName_array = array();
    //remove PLUS separator, create a sub-array for rule array field
    foreach ($varName_exploded as $varName) {
      $varName_exploded_combo = explode( '+', $varName );
      $varName_array_combo = array();
      foreach ($varName_exploded_combo as $varName_combo) {
        $varName_array_combo[] = strtolower(trim($varName_combo));  //remove leading or trailing spaces, make lower case...
      }              
      $varName_array[] = $varName_array_combo;
    }             
    return($varName_array);
  }

   
} //end class
