 /*
 $.browser.chrome = $.browser.webkit && !!window.chrome;
$.browser.safari = $.browser.webkit && !window.chrome;

if ($.browser.chrome) alert("You are using Chrome!");
if ($.browser.safari) alert("You are using Safari!");

Need to alert Safari that JS might not work...
DITTO ie10!!
 */

      
                       jQuery.noConflict();

                        jQuery(document).ready(function($) {                                                        

                          //v1.0.5 changed to 2 buttons
                          //Include/Exclude Redirect , inserted into the PUBLISH box
                          newHtml  =  '<span class="box-border-line2" id="">&nbsp;</span>'            
                          newHtml +=  '<div class="vtprd-redirect">';
                          newHtml +=  '<a href="http://www.varktech.com/documentation/pricing-deals/introrule"  target="_blank" class="vtprd-redirect-anchor">';
                          newHtml +=  $("#vtprd-moreInfo").val();        //pick up the literals passed up in the html...  
                          newHtml +=  '</a></div>';           
                          newHtml +=  '<div class="vtprd-redirect vtprd-redirect2">';
                          newHtml +=  '<a href="http://www.varktech.com/support/"  target="_blank" class="vtprd-redirect-anchor">';
                          newHtml +=  $("#vtprd-docTitle").val();        //pick up the literals passed up in the html...  
                          newHtml +=  '</a></div>';
                          
                          //v1.1.8.1 begin
                          newHtml +=  '<div class="vtprd-redirect vtprd-clone">';
                          newHtml +=  '<a id="ajaxCloneRule" href="javascript:void(0);">';  //
                          newHtml +=  $("#vtprd-cloneRule").val();        //pick up the literals passed up in the html... 
                          newHtml +=  '</a></div>';
                          
                          newHtml +=  '<div class="ajaxCloneRule-loading-animation">';
                          newHtml +=  '<img title="Loading" alt="Loading" src="';
                          newHtml +=  $("#vtprd-url").val();        //pick up the literals passed up in the html...
                          newHtml +=  '/admin/images/indicator.gif" />';
                          newHtml +=  '</div>'; 
                                                  
                          newHtml +=  '<div id="vtprd-clone-msg" class="vtprd-redirect">';
                          newHtml +=  '</div>';                         
                          //v1.1.8.1 end

                          //v2.0.0 begin
                          newHtml +=  '<div class="vtprd-redirect vtprd-copyForSupport">';
                          newHtml +=  '<a href="'; //v2.0.0.2 altered to pick up adminURL
                          newHtml +=  $("#vtprd-admin-url").val();        //pick up the literals passed up in the html... v2.0.0.2 altered to pick up adminURL
                          newHtml +=  'edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=showRuleInfo&ruleID='; //v2.0.0.2 altered to pick up adminURL
                          newHtml +=  $("#ajaxRuleID").val(); 
                          newHtml +=  '"  target="_blank" class="vtprd-redirect-anchor">';
                          newHtml +=  $("#vtprd-copyForSupport").val();        //pick up the literals passed up in the html... 
                          newHtml +=  '</a></div>';
                          //v2.0.0 end


                          $(newHtml).insertAfter('div#publishing-action');                                     	                        						

                          //*************************
                          //v1.1.8.0 begin
                          //*************************
                           $("#bulkMethodIn").change(function(){
                              bulkMethodInTest();
                          });
                                                    
                          add_Bulk_line_cntl(); 
                          //v1.1.8.0 end

                                                                                                                
            //*************************************
            // At init time, these routines run to present the data loaded in php
            //*************************************  

                           //First time through, test for existing data.  If none, set up new rule setup.                           
                           firstTimeThroughControl(); 

                        
            //*************************************
            // NEW DROPDOWNS test routine 
            //*************************************                             
                          $(".cart-or-catalog").change(function(){ //v2.0.0 change for radio buttons, now testing the CLASS
                              cartOrCatalogChange(); //v2.0.0
                              cartOrCatalogTest();
                          });
                          $("#pricing-type-select").change(function(){
                              pricingTypeTest();
                              //v2.0.0 begin - if changed to 'bogo', set 'NEXT'
                              if ( $("#pricing-type-select").val() == 'bogo') {
                                $("#minimum-purchase-Next").prop("checked", true); 
                                minimumTest(); //reset other stuff as though 'next' were selected onscreen
                              }
                              //v2.0.0 end                              
                          });
                          $(".minimum-purchase").change(function(){ //v2.0.0 change for radio buttons, now testing the CLASS
                              minimumTest();
                          
  //v2.0.0 do we need these 3 lines??                          //mwnnew                             
      /*
                            activateDates_and_lowerArea();                         
                            setRuleTemplate();                       
                            ruleTemplateChanged();  
      */                                 
                          });
                                    
                          //basic/advanced radio buttons
                          $("#basicSelected").change(function(){
                              basicSelected();
                          });                          
                          $("#advancedSelected").change(function(){
                              advancedSelected();
                          });                                                  
                            
                          //***************************************
                          //   Control upper selects!!
                          //***************************************
                          function firstTimeThroughControl() {                     
                             //VERSION TEST free vs pro, dropdown option labels, etc...
                              switch( $("#pluginVersion").val() ) {
                               case 'freeVersion':
                                  $(".freeVersion-labels").show();
                                  $(".proVersion-labels").hide();                                 
                                  disableAllDiscountLimits();
                                  //v2.0.0 begin - mark selectors yellow, not avail in free version
                                  jQuery('.buy-prod-category-incl-label').css('color', '#BB8500');
                                  jQuery('.buy-prod-category-excl-label').css('color', '#BB8500');
                                  jQuery('.buy-plugin-category-incl-label').css('color', '#BB8500');
                                  jQuery('.buy-plugin-category-excl-label').css('color', '#BB8500');
                                  jQuery('.buy-product-incl-label').css('color', '#BB8500');
                                  jQuery('.buy-product-excl-label').css('color', '#BB8500');
                                  jQuery('.buy-role-incl-label').css('color', '#BB8500');
                                  jQuery('.buy-role-excl-label').css('color', '#BB8500');
                                  jQuery('.buy-email-incl-label').css('color', '#BB8500');
                                  jQuery('.buy-email-excl-label').css('color', '#BB8500');                                  
                                                                    
                                  jQuery('.action-prod-category-incl-label').css('color', '#BB8500');
                                  jQuery('.action-prod-category-excl-label').css('color', '#BB8500');
                                  jQuery('.action-plugin-category-incl-label').css('color', '#BB8500');
                                  jQuery('.action-plugin-category-excl-label').css('color', '#BB8500');
                                  jQuery('.action-product-incl-label').css('color', '#BB8500');
                                  jQuery('.action-product-excl-label').css('color', '#BB8500'); 
                                  $('#bulkCountByEach').attr('disabled', true); //each uses product-based exploder, doesn't work in free                                               
                                  //v2.0.0 end
                                 break; 
                               default:
                                  $(".freeVersion-labels").hide();
                                  $(".proVersion-labels").show();
                                  enableAllDiscountLimits();
                                 break;                                                                  
                              }; 

                             /* 
                             ****************************************************
                             TWO (hidden) Upper Selects first time switches in use:
                             ****************************************************
                             
                             *upperSelectsHaveDataFirstTime has values from 0 => 5                                                                                             
                                 value = 0  no previous data saved / not a first time run
                                 value = 1  last run got to:  cart_or_catalog_select
                                 value = 2  last run got to:  pricing_type_select
                                 value = 3  last run got to:  minimum_purchase_select
                                 value = 4  last run got to:  buy_group_filter_select
                                 value = 5  last run got to:  get_group_filter_select
                                  $("#upperSelectsHaveDataFirstTime").val()
                                  
                              *upperSelectsFirstTime - values of 'yes' or 'no'
                              *                                
                             */

                             if ($("#upperSelectsHaveDataFirstTime").val() > 0 ) {
                                ruleTemplateTest();  //master set if data came back from the server
                                changeCumulativeSwitches();                              
                             } else {
                                jQuery('.top-box select #cart-or-catalog-select').css('font-style', 'italic');
                                jQuery('.top-box select #cart-or-catalog-select').css('color', 'grey');
                                //Set the cursor for the 3 LIMIT switches
                             };

                             //If the saved msg = the default, user hasn't entered a msg, make it italic!                             
                             if ($("#discount_product_full_msg").val() == $("#fullMsg").val() ) {
                               jQuery('#discount_product_full_msg').css('color', 'rgb(152, 152, 152) !important').css("font-style","italic");                                                     
                             };                                                          
                             if ($("#discount_product_short_msg").val() == $("#shortMsg").val() ) {
                               jQuery('#discount_product_short_msg').css('color', 'rgb(152, 152, 152) !important').css("font-style","italic");                                                     
                             };                                
                              //v1.1.0.8
                             if ($("#only_for_this_coupon_name").val() == $("#couponMsg").val() ) {
                               jQuery('#only_for_this_coupon_name').css('color', 'rgb(152, 152, 152) !important').css("font-style","italic");                                                     
                             };  

                             //HIDE the FUTURE enhancement UPCHARGE option
                             $("#pricing-type-Upcharge").hide();
                            
                             $("#select_status_sw").val('no');
                             cartOrCatalogTest();                   
                             if ( $("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();                            
                               return;        
                             }; 
                            
                             $("#select_status_sw").val('no');
                             pricingTypeTest(); 
                             if ($("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();
                               return;
                             }; 
                             
                             $("#select_status_sw").val('no');
                             minimumTest(); 
                             if ($("#select_status_sw").val() == 'no') {
                               resetUpperFirstTimeSwitches();
                               return;
                             }; 
                             //mwnnew
                             //release the rest of the screen EXCEPT the get group 
                             activateDates_and_lowerArea(); 
                             
                             
                                                        
                             //all good, expose the lower screen!
                             $("#lower-screen-wrapper").show("slow");                              
                            
                             //test the on-off switch settings
                             //  done here so the lower opacity setting will take, if sw is off...
                             rule_on_off_sw_select_test();
                           //  wizard_on_off_sw_select_test();

                             //reset upper level server switches prior to exit
                             resetUpperFirstTimeSwitches(); 
                             
                             //used in rules-update.php
                             $("#upperSelectsDoneSw").val('yes'); 
                             
                                                                                         
                          };


                          //v1.1.8.0 New Function
                          function add_Bulk_line_cntl() {
                       
                            var MaxInputs       = 8; //maximum input boxes allowed
                            var InputsWrapper   = $("#InputsWrapper"); //Input boxes wrapper ID
                            var AddButton       = $("#AddMoreFileBox"); //Add button ID
                            
                            var x = InputsWrapper.length; //initlal text box count
                             
                            if ($("#rowCountFirstTime").val() > 0 ) {
                              var RowCount = $("#rowCountFirstTime").val(); 
                              $("#rowCountFirstTime").val('0'); //reset to zero until next update                             
                            } else {
                              var RowCount = 0; //to keep track of text box added
                            };
                            
                            var currencySymbol = $("#currencySymbol").val();        //pick up the literals passed up in the html...
                            var stepValue = $("#stepValue").val();        //pick up the literals passed up in the html... //v2.0.0.8
                            var placeholderValue = $("#placeholderValue").val();        //pick up the literals passed up in the html... //v2.0.0.8
                            var typeValue = $("#typeValue").val();        //pick up the literals passed up in the html... //v2.0.0.8
                                       
                            $(AddButton).click(function (e)  //on add input button click
                            {
                                RowCount++; //text box added increment
                                
                                bulkHtml  =  '<div class="pricing_tier_row" id="pricing_tier_row_'+ RowCount +'">';
                                bulkHtml +=  '<span class="hideMe"><input  type="hidden" name="rowCount[]" id="rowCount'+ RowCount +'" value="'+ RowCount +'"/></span>';
                                bulkHtml +=  '<span class="pricing_table_column1"><input type="text" placeholder="From" name="minVal[]" id="minVal_row_'+ RowCount +'" value=""/></span>';
                                bulkHtml +=  '<span class="pricing_table_column2"><input type="text" placeholder="To - No limit" name="maxVal[]" id="maxVal_row_'+ RowCount +'" value=""/></span>';
                                
                                bulkHtml +=  '<span class="pricing_table_column3">';
                                bulkHtml +=  '<select id="discount_amt_type_row_'+ RowCount +'"  class="pricing_table_discount_amt_type" name="discountType[]" tabindex="">';          
                                bulkHtml +=  '<option id="pricing_table_discount_amt_type_percent_'+ RowCount +'" class="pricing_table_discount_amt_type_percent" value="percent" selected="selected"> % Off </option>';
                                bulkHtml +=  '<option id="pricing_table_discount_amt_type_currency_'+ RowCount +'" class="pricing_table_discount_amt_type_currency" value="currency">  '+ currencySymbol +' Off  </option>';                                                      
                                bulkHtml +=  '<option id="pricing_table_discount_amt_type_fixedPrice_'+ RowCount +'" class="pricing_table_discount_amt_type_fixedPrice" value="fixedPrice"> Fixed Unit Price '+ currencySymbol +' </option>';                                                     
                                bulkHtml +=  '</select>';                    
                                bulkHtml +=  '</span>';

                                bulkHtml +=  '<span class="pricing_table_column4"><input type="'+ typeValue +'" step="'+ stepValue +'" placeholder="'+ placeholderValue +'"  name="discountVal[]" id="discountVal_row_'+ RowCount +'" value=""/></span>';
                                bulkHtml +=  '<a href="#" class="removeclass">X</a>';
                                bulkHtml +=  '</div>';                   
                                
                                //add input box
                                $(InputsWrapper).append(bulkHtml);
                                return false;
                            });
                            
                            $("body").on("click",".removeclass", function(e){ //user click on remove text
                                 $(this).parent('div').remove(); //remove text box
                                 
                                 //CAN't decrease row count - two rows end up with same identifier, as row count part of ID
                                 // .removeclass now used for OTHER rows as well!
                                 //RowCount --; //decrement RowCount
                                 
                                return false;
                            });
                            $("body").on("click",".hideWarning", function(e){ //user click on hide warning box
                                 $(this).parent('div').hide("slow"); //hide warning box
                                return false;
                            });
                                                                                         
                          };
                          
                          //v1.1.8.0 New Function
                          function bulkMethodInTest() {
                             switch( $("#bulkMethodIn").val() ) {
                               case "currency":
                                    $(".bulk_box_currency_warning_class_0").show("slow");
                                    $(".bulk-heading-units").hide();
                                    $(".bulk-heading-dollars").show();
                                 break;
                               default:
                                    $(".bulk_box_currency_warning_class_0").hide("slow");                                   
                                    $(".bulk-heading-dollars").hide();
                                    $(".bulk-heading-units").show();                                    
                                 break;                                  
                             };                           
                          };
                          
                          function resetUpperFirstTimeSwitches() {
                            //reset upper level server switches - only valid for 1st run
                             $("#upperSelectsHaveDataFirstTime").val('0');
                             $("#upperSelectsFirstTime").val('no'); 
                             //reset the switch, only used 1st time in from server, in hidden html field...
                             $("#firstTimeBackFromServer").val('no');                            
                          };
                          
                          //v2.0.0 New Function
                          function cartOrCatalogChange() {
                            if($('#cart-or-catalog-Cart').prop('checked')) {
                              $("#advancedSelected").prop("checked", true);
                            } else { //'catalog' branch
                              $("#basicSelected").prop("checked", true);   
                            }
                          }
                          
                          function cartOrCatalogTest() {     
                             $("#upperSelectsDoneSw").val('no');
                             
                             //$(".select-subTitle").hide("slow");
                             
                             $("#select_status_sw").val('ok');
                                                          
                             //reset these options to 'choose'
                             if ($("#upperSelectsFirstTime").val() == 'yes') {
                                reset_Min_to_Choose();
                                //if buy set as discount, reset...
                                setBuyGroupAsBuy();
                             } else {
                                $("#minimum-purchase-None").prop("checked", true);     //v2.0.0 set 'discount the item'
                             }; 
                                                          
                             //v2.0.0 changed to check radio button                             
                             if($('#cart-or-catalog-Cart').prop('checked')) {
                                  //'Cart' branch
                                  //reload_PricingType_Titles1(); v2.0.0
                                  //reload_BuyGroupFilterSelect_Titles1(); v2.0.0
                                  reload_buy_amt_type_Titles1();
                                  reload_buy_repeat_condition_Titles1();                                   
                                 
                                  if ($("#upperSelectsFirstTime").val() == 'yes') {
                                    $('#pricing-type-select').removeAttr('disabled');
                                    showAllPricingTypeOptions();
                                  } else {
                                    resetPricingType_and_DisableBelow();
                                    showAllPricingTypeOptions();
                                  } ;
                                                                                                                        
                                   //reset the Minimum Select titles to default
                                   //reload_MinimumSelect_Titles1(); v2.0.0
                                   //restore 'next' title
                                   // v2.0.0 $('#minimum-purchase-Next').removeAttr('disabled');
                                   
                                  //v2.0.0 begin 
                                  if ( ( $("#pricing-type-select").val() == 'all') ||
                                       ( $("#pricing-type-select").val() == 'simple') || 
                                       ( $("#pricing-type-select").val() == 'bulk') ||
                                       ( $("#pricing-type-select").val() == 'choose') ) {
                                     $("#deal-action-line").hide();  
                                     $("#deal-action-horiz-line").hide();
                                     $("#buy_amt_box_0").hide();  
                                   } else {
                                     $("#deal-action-line").show("slow");  
                                     $("#deal-action-horiz-line").show("slow");
                                     $("#buy_amt_box_0").show("slow");  
                                   }
                                   //v2.0.0 end
                                   $("#apply-to-cheapest-select-area").show("slow"); //v2.0.0
                                   //$("#schedule-box").show("slow"); //v2.0.0

                                   $("#buy_repeat_box_0").show("slow"); //v2.0.0
                                   $(".discount_product_short_msg_area").show(); //v2.0.0  
                                   $("#pricing-type-Bulk").show(); //v2.0.0
                                   $("#pricing-type-Bogo").show(); //v2.0.0
                                   $("#pricing-type-Group").show(); //v2.0.0
                                   $("#pricing-type-Cheapest").show(); //v2.0.0
                                   $(".cumulativeCouponPricing_area").show(); //v2.0.0                                   
                                   $("#discount_applies_to_box_0").show(); //v2.0.0
                                   $("#only_for_this_coupon_box_0").show(); //v2.0.0                                   
                                   $(".varName_catalog_info").hide(); //v2.0.0
                                       
                               } else { //'catalog' branch                                 
                                  
                                  //reload_PricingType_Titles_catalog(); v2.0.0
                                  //reload_BuyGroupFilterSelect_Titles_catalog();  v2.0.0
                                  reload_buy_amt_type_Titles_catalog();
                                  reload_buy_repeat_condition_Titles_catalog();                                   
                                 
                                  if ($("#upperSelectsFirstTime").val() == 'yes') {
                                    $('#pricing-type-select').removeAttr('disabled');
                                    restrictPricingTypeOptions();
                                  } else {
                                    resetPricingType_and_DisableBelow();
                                    restrictPricingTypeOptions();
                                  }; 
                                                         
                                   //reset the Minimum Select titles to 'catalog'
                                   //reload_MinimumSelect_Titles_catalog(); v2.0.0
                                   
                                   //v2.0.0  JS is slow and causes a BLINK
                                   //so these Hides are ALSO IN THE UI FILE
                                   
                                   hideBulk(); //v1.1.8.0 don't show pricing table in Coupon processing
                                   $("#bulk-checkout-msg-comment").hide(); //v1.1.8.0 don't show pricing table in Coupon processing
                                   $("#deal-action-line").hide("slow"); //v2.0.0
                                   $("#deal-action-horiz-line").hide("slow"); //v2.0.0
                                   $("#apply-to-cheapest-select-area").hide("slow"); //v2.0.0
                                   //$("#schedule-box").hide("slow"); //v2.0.0
                                   //$("#basicSelected").prop("checked", true); //v2.0.0 removed
                                   $(".discount_product_short_msg_area").hide(); //v2.0.0 
                                   $("#buy_amt_box_0").hide(); //v2.0.0
                                   $("#buy_repeat_box_0").hide(); //v2.0.0
                                   $("#pricing-type-Bulk").hide(); //v2.0.0
                                   $("#pricing-type-Bogo").hide(); //v2.0.0
                                   $("#pricing-type-Group").hide(); //v2.0.0
                                   $("#pricing-type-Cheapest").hide(); //v2.0.0
                                   $(".cumulativeCouponPricing_area").hide(); //v2.0.0
                                   $("#discount_applies_to_box_0").hide(); //v2.0.0 
                                   $("#only_for_this_coupon_box_0").hide(); //v2.0.0
                                   $(".varName_catalog_info").show(); //v2.0.0
                                   $("#buy-show-and-or-switches").hide("slow"); //v2.0.0	
                                   $("#action-show-and-or-switches").hide("slow"); //v2.0.0
                                   $(".action-and-or-selector").hide("slow"); //v2.0.0
                                   $(".buy-and-or-selector").hide("slow"); //v2.0.0                                 
                             } 
                                                         
                                                        
                          };
                          
                          function pricingTypeTest() {                              
                             $("#upperSelectsDoneSw").val('no');
                             
                             //$(".select-subTitle").hide("slow");
                             
                             //reset these options to 'choose'
                             if ($("#upperSelectsFirstTime").val() == 'yes') {
                               if ( $("#pricing-type-select").val() == 'choose' ) {
                                     $("#select_status_sw").val('no');
                                     return;  //exit stage left!
                               };
                             };
                             
                             $('#pricing-type-Choose').attr("disabled", "disabled");
                             jQuery('#pricing-type-select').css('font-style', 'normal');
                             jQuery('#pricing-type-select').css('color', '#0077BB');                             
                             
                             $(".bogo-titles").hide(); //v2.0.0
                             
                             //set to 'no' only if 'choose' still set
                             $("#select_status_sw").val('yes');
                             
                             switch( $("#pricing-type-select").val() ) {
                               case "all":
                                     setWholeStore(); //resets
                                     //$(".select-subTitle-all").show("slow"); 
                                     hideBulk();  //v1.1.8.0 don't show pricing table                                 
                                 break;
                                 //v1.1.8.0 new case
                                case "bulk":
                                     setBulkDiscount(); 
                                 break;                                
                               case "simple":
                                     setSimpleDiscount();
                                     //$(".select-subTitle-simple").show("slow"); 
                                     hideBulk();  //v1.1.8.0 don't show pricing tabl
                                 break; 
                               case "bogo":
                                     setComplexDiscount();
                                     //$(".select-subTitle-bogo").show("slow");
                                     $(".bogo-titles").show("slow"); //v2.0.0 
                                     
                                     
                                     hideBulk();  //v1.1.8.0 don't show pricing tabl                               
                                 break;
                               case "group":
                                     setComplexDiscount();
                                     //$(".select-subTitle-group").show("slow");
                                     hideBulk();  //v1.1.8.0 don't show pricing tabl 
                                 break;                                 
                               case "cheapest":
                                     setComplexDiscount();
                                     //$(".select-subTitle-cheapest").show("slow");
                                     hideBulk();  //v1.1.8.0 don't show pricing tabl 
                                 break;                                                                  
                               //v2.0.0 BEGIN
                               default: //still set to 'choose'!!
                                   //enable min, disable the rest of the upper selects
                                   $('#rule-on-off-sw-select').attr("disabled", "disabled");
                                   
                                   //IF we're in the 1st time thru and there upper selects had data coming in,
                                   //   let the pre-loaded switch setting carry on.  
                                   //     "== 0" means neither of these conditions is true.
                                   if ( $("#upperSelectsHaveDataFirstTime").val() == 0) {
                                      $("#rule-on-off-sw-select").val('onForever')               //v1.0.7.5 
                                      rule_on_off_sw_select_test();                              //v1.0.7.5 
                                   }                             
                                   
                                   jQuery('#date-begin-0').css('text-decoration', 'none');
                                   jQuery('#date-end-0').css('text-decoration', 'none');
                                  
                                   disableDates_lowerScreen();
                                 break; 
                             };                         
 
                            activateDates_and_lowerArea();
                            setRuleTemplate();                             
                            ruleTemplateChanged();
                            return;
                                                                                         
                             //v2.0.0 END
                                                                                     
                          };
                          
                          //v1.1.8.0 new function
                          function hideBulk() {
                             $("#bulk-box").hide(); 
                             $(".bulk-checkout-msg").hide();
                             //v2.0.0 begin - only show in CART mode                           
                             if($('#cart-or-catalog-Cart').prop('checked')) {
                               // $("#buy_amt_box_0").show("slow"); //when switching from 'bulk' in basic mode, this doesn't show 
                               show_amt_box_test();
                             }
                             //v2.0.0 end                         
                          };

                                                
                                                    
                          function minimumTest() {
                             $("#upperSelectsDoneSw").val('no');
                             
                             $("#select_status_sw").val('yes');

                            //v2.0.0 begin
                            activateDates_and_lowerArea();                         
                            setRuleTemplate();                         
                            ruleTemplateChanged();    
                            //v2.0.0 end                          
                          };

                       
                          function activateDates_and_lowerArea() {     
                             //activate dates
                             $('#date-begin-0').removeAttr('disabled');
                             $('#date-end-0').removeAttr('disabled');
                             $('#rule-on-off-sw-select').removeAttr('disabled');                             
                                                          
                             //IF we're in the 1st time thru and there upper selects had data coming in,
                             //   let the pre-loaded switch setting carry on.  
                             //     "== 0" means neither of these conditions is true.
                             if ( $("#upperSelectsHaveDataFirstTime").val() == 0) {                            
                                $("#rule-on-off-sw-select").val('onForever')               //v1.0.7.5 
                                rule_on_off_sw_select_test();                              //v1.0.7.5 
                             } 
                       
                             //activate lower screen
                             $("#lower-screen-wrapper").show("slow");
                             
                             //used in rules-update.php
                             $("#upperSelectsDoneSw").val('yes');
                             
                             
                                                          
                             //ENable and lighten basic/advanced ...
                             jQuery('#rule-type-info').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                             $('#basicSelected').attr('disabled', false);        
                             $('#advancedSelected').attr('disabled', false);
                             jQuery('#basicSelected-label').css('color', '#444'); 
                             jQuery('#advancedSelected-label').css('color', '#444'); 
                            
                             
                              //basic/advanced radio buttons, hide or show basic/advanced
                              if($('#basicSelected').prop('checked')) {
                                  basicSelected();
                              } else {                         
                                  advancedSelected();
                              };                             
                                                              
                          };                          
                                                                              
                          function resetPricingType_and_DisableBelow() {     
                             //enable pricing type select
                             $('#pricing-type-select').removeAttr('disabled');
                             $('#pricing-type-Choose').attr('selected', true);
                             jQuery('#pricing-type-select').css('font-style', 'italic');
                             jQuery('#pricing-type-select').css('color', 'grey');
                             
                             //disable the rest of the upper selects
                             //$('#minimum-purchase-select').attr("disabled", "disabled");
                                                         
                             //hide the buy/get group subtitles
                             //$(".select-subTitle").hide("slow");
                             
                             disableDates_lowerScreen();

                          };                            
                          
                              
                          function disableDates_lowerScreen() {          
                             
                             //disable begin/end 
                             $('#date-begin-0').attr('disabled', true);
                             $('#date-end-0').attr('disabled', true);
                             $('#rule-on-off-sw-select').attr("disabled", "disabled");
                                                          
                             //IF we're in the 1st time thru and there upper selects had data coming in,
                             //   let the pre-loaded switch setting carry on.  
                             //     "== 0" means neither of these conditions is true.
                             if ( $("#upperSelectsHaveDataFirstTime").val() == 0) {
                                 $("#rule-on-off-sw-select").val('onForever')               //v1.0.7.5 
                                 rule_on_off_sw_select_test();                              //v1.0.7.5 
                             }                                 
                             
                             //Disable and darken basic/advanced ...
                             jQuery('#rule-type-info').css('opacity', '0.7').css('filter', 'alpha(opacity=70)');
                             $('#basicSelected').attr('disabled', true);        
                             $('#advancedSelected').attr('disabled', true);
                             jQuery('#basicSelected-label').css('color', '#AAAAAA'); 
                             jQuery('#advancedSelected-label').css('color', '#AAAAAA'); 
                             
                             //hide the lower screen 
                             $("#lower-screen-wrapper").hide("slow");                                                 
                          };

                          function showAllPricingTypeOptions() {    //'cart' chosen
                             //Pricing Type
                             $('#pricing-type-All').attr('disabled', false);
                             $('#pricing-type-Simple').attr('disabled', false);
                             $('#pricing-type-Bogo').attr('disabled', false);
                             $('#pricing-type-Group').attr('disabled', false);
                             $('#pricing-type-Cheapest').attr('disabled', false);
                             $('#pricing-type-Nth').attr('disabled', false);
                             $('#pricing-type-Bulk').attr('disabled', false); //v1.1.8.0
                          };
                          function restrictPricingTypeOptions() {    //'cart' chosen
                             //Pricing Type
                             $('#pricing-type-All').attr('disabled', false);
                             $('#pricing-type-Simple').attr('disabled', false);
                             $('#pricing-type-Bogo').attr('disabled', true);
                             $('#pricing-type-Group').attr('disabled', true);
                             $('#pricing-type-Cheapest').attr('disabled', true);
                             $('#pricing-type-Nth').attr('disabled', true);
                             $('#pricing-type-Bulk').attr('disabled', true); //v1.1.8.0
                             //min options remain protected until Pricing Type chosen 
                             $('#minimum-purchase-None').attr('disabled', false);
                             $('#minimum-purchase-Minimum').attr('disabled', true);                                             
                          };                          


                          function setWholeStore() { 
                                                         
                             //don't allow 'Next' title to be selected
                             $('#minimum-purchase-Next').attr("disabled", "disabled");
                             $('#minimum-purchase-None').removeAttr('disabled');
                             

                             //opaque the lower screen (to mimic disabled)
                             //$("#lower-screen-wrapper").hide("slow");  v2.0.0
                              
                             //Buy group title
                             setBuyGroupAsDiscount();
                             $(".buyShortIntro").hide();

                             //reset these options to 'choose'
                             reset_Min_to_Choose();

                             $("#buy_amt_box_0").hide(); //v2.0.0
                             $("#buy_amt_mod_box_0").hide(); //v2.0.0
                             $("#deal-action-line").hide("slow"); //v2.0.0 
                             $("#deal-action-horiz-line").hide("slow"); //v2.0.0                            
                          };

                          function setSimpleDiscount() { 
                                                         
                             //don't allow 'Next' title to be selected
                             $('#minimum-purchase-Next').attr("disabled", "disabled");
                             $('#minimum-purchase-None').removeAttr('disabled');;
                                                          
                             //Buy group title
                             setBuyGroupAsDiscount();
                             $(".buyShortIntro").hide("slow");

                             //reset these options to 'choose'
                             reset_Min_to_Choose();

                             $("#buy_amt_box_0").hide(); //v2.0.0
                             $("#buy_amt_mod_box_0").hide(); //v2.0.0
                             $("#deal-action-line").hide("slow"); //v2.0.0
                             $("#deal-action-horiz-line").hide("slow"); //v2.0.0
                          };                          

                          //**************************
                          //v1.1.8.0 new function
                          //**************************
                          function setBulkDiscount() { 
                                                         
                             //don't allow 'Next' title to be selected
                             $('#minimum-purchase-Next').attr("disabled", "disabled");
                             $('#minimum-purchase-None').removeAttr('disabled');;
                                                          
                             //Buy group title
                             setBuyGroupAsDiscount();
                             $(".buyShortIntro").hide("slow");

                             //reset these options to 'choose'
                             reset_Min_to_Choose();
                             
                             $("#bulk-box").show("slow"); //show pricing table
                             $(".bulk-checkout-msg").show("slow"); //show comment for short checkout msg with wildcard
                             
                             //don't show stuff not relevant to bulk purchasing 
                             $("#deal-action-line").hide("slow"); //v2.0.0
                             $("#deal-action-horiz-line").hide("slow"); //v2.0.0
                             $("#buy_amt_box_0").hide();  //Buy Group Amount
                             $("#buy_amt_mod_box_0").hide();  //Buy Min/Max
                             $("#buy_repeat_box_0").hide(); //Rule Usage Count
                             $("#discount_amt_row_0").hide(); //Discount Amount Type disable 
                                                      
                          };

                          
                          function setComplexDiscount() { 
                                                                                      
                             //restore 'next' title
                             $('#minimum-purchase-Next').removeAttr('disabled');
                                                          
                             //min option
                             $('#minimum-purchase-None').attr('disabled', false);
                             $('#minimum-purchase-Minimum').attr('disabled', false);
                             //reset these options to 'choose'
                             reset_Min_to_Choose();

                             //Buy group title
                             setBuyGroupAsBuy();
                             $(".buyShortIntro").show("slow");
                             
                             enableAllDiscountLimits();
                             
                             $("#deal-action-line").show("slow"); //v2.0.0 
                             $("#deal-action-horiz-line").show("slow"); //v2.0.0
                          };                         
 
                          function setBuyGroupAsBuy() {                              
 
                             //Show the Buy literals as Buy
                             $(".showBuyAsBuy").show();
                             $(".showBuyAsDiscount").hide();
                             //show Get literals as Discount
                            // $(".showGetAsGet").hide(); //v2.0.0
                            // $(".showGetAsDiscount").show(); //v2.0.0
                             //UNhide whole get area and line above, in case it was previously hidden...   
                             $("#action_info_0").show();   
                          };   
 
                          function setBuyGroupAsDiscount() {                              
  
                             //show the Buy literals as Discount
                             $(".showBuyAsBuy").hide();
                             $(".showBuyAsDiscount").show();
                             //show Get literals as Inactive/Get
                            // $(".showGetAsGet").show();   //v2.0.0
                            // $(".showGetAsDiscount").hide();  //v2.0.0
                             //hide whole get area and line above...   
                             $("#action_info_0").hide();
                          }; 
                          
                          function reset_Min_to_Choose() {                              
                             //Act on next selects based on whether the upper selects had data coming in
                             switch( $("#upperSelectsHaveDataFirstTime").val() ) {
                               case '5' :  //data up to  get_group_filter_select
                               case '4' :  //data up to  buy_group_filter_select
                               case '3' :  //data up to  minimum_purchase_select;
                                 break; 
                               default:
                                    $("#minimum-purchase-None").prop("checked", true);     //v2.0.0 set 'discount the item'
                                 break;                                                              
                             }
                            
                          }; 
                        
                          
                         $("#rule-on-off-sw-select").click(function(){
                             rule_on_off_sw_select_test();                            
                         });
                                                           
                         function rule_on_off_sw_select_test() {                                                         
                          switch( $("#rule-on-off-sw-select").val() ) {                                
                            case "on": 
                                //set to standard blue
                                jQuery('#rule-on-off-sw-select').css('color', '#0077BB !important');
                                jQuery('#date-begin-0').css('color', '#0077BB !important').css('text-decoration', 'none');
                                jQuery('#date-end-0').css('color', '#0077BB !important').css('text-decoration', 'none');
                                $('#date-begin-0').removeAttr('disabled');
                                $('#date-end-0').removeAttr('disabled');                               
                                jQuery('#lower-screen-wrapper').css('opacity', '1').css('filter', 'alpha(opacity=100)');           
                                if ($("#firstTimeBackFromServer").val() != 'yes') {   
                                   jQuery('#cart-or-catalog-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#pricing-type-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#minimum-purchase-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');                                  
                                 };
                              break;
                            case "onForever":    
                                //set to green and strikethrough
                                jQuery('#rule-on-off-sw-select').css('color', '#1F861F !important');
                                jQuery('#date-begin-0').css('color', '#1F861F !important').css('text-decoration', 'line-through');
                                jQuery('#date-end-0').css('color', '#1F861F !important').css('text-decoration', 'line-through');
                                $('#date-begin-0').attr("disabled", "disabled");
                                $('#date-end-0').attr("disabled", "disabled");                          
                                jQuery('#lower-screen-wrapper').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                                if ($("#firstTimeBackFromServer").val() != 'yes') {
                                   jQuery('#cart-or-catalog-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#pricing-type-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');
                                   jQuery('#minimum-purchase-select').css('color', '#0077BB !important').css('opacity', '1.0').css('filter', 'alpha(opacity=100)');                                   
                                };
                              break;
                            case "off": 
                                //set to red, strikethrough and protect everything else!
                                jQuery('#rule-on-off-sw-select').css('color', '#E32525 !important');
                                jQuery('#date-begin-0').css('color', '#E32525 !important').css('text-decoration', 'line-through');
                                jQuery('#date-end-0').css('color', '#E32525 !important').css('text-decoration', 'line-through');
                                $('#date-begin-0').attr("disabled", "disabled");
                                $('#date-end-0').attr("disabled", "disabled");                            
                                jQuery('#lower-screen-wrapper').css('opacity', '0.5').css('filter', 'alpha(opacity=50)');
                               
                                   //do this EVERY time...
                                   jQuery('#cart-or-catalog-select').css('color', '#999999 !important').css('opacity', '0.65').css('filter', 'alpha(opacity=65)');
                                   jQuery('#pricing-type-select').css('color', '#999999 !important').css('opacity', '0.65').css('filter', 'alpha(opacity=65)');
                                   jQuery('#minimum-purchase-select').css('color', '#999999 !important').css('opacity', '0.65').css('filter', 'alpha(opacity=65)');                                
                              break;                                                         
                          };
                        }
                           
             //*************************************
            // NEW DROPDOWNS End
            //*************************************                           
                          
                          
                           
                          
            //***********************************************************************
            //Evaluate the Upper Dropdowns and set the Rule Template Type
            //***********************************************************************
                          function setRuleTemplate() { 
                             //v2.0.0 redone for radio buttons
                             //cartOrCatalogSelect_sw = $("#cart-or-catalog-select").val();
                             if($('#cart-or-catalog-Cart').prop('checked')) { 
                                cartOrCatalogSelect_sw   = 'cart';
                             } else {
                                cartOrCatalogSelect_sw   = 'catalog';
                             }
                             //v2.0.0 redone for radio buttons
                             //minPurchaseSelect_sw   = $("#minimum-purchase-select").val();
                             if($('#minimum-purchase-None').prop('checked')) {
                                minPurchaseSelect_sw   = 'none';
                             } else {
                                minPurchaseSelect_sw   = 'next';
                             } 

                             switch( $("#pricing-type-select").val() ) {
                                case "all":                       
                                     if (cartOrCatalogSelect_sw == 'cart') {
                                       $("#rule_template_framework").val('C-storeWideSale');
                                     } else {
                                       $("#rule_template_framework").val('D-storeWideSale');
                                     };
                                  break;
                                case "simple":                       
                                     if (cartOrCatalogSelect_sw == 'cart') {
                                       $("#rule_template_framework").val('C-simpleDiscount');
                                     } else {
                                       $("#rule_template_framework").val('D-simpleDiscount');
                                     };
                                  break;
                                case "bulk":  //v1.1.8.0  - CART processing only                   
                                      $("#rule_template_framework").val('C-bulkDiscount');
                                  break;                                  
                                case "bogo":                       
                                     if (minPurchaseSelect_sw == 'none') {
                                       $("#rule_template_framework").val('C-discount-inCart');   
                                     } else {
                                       $("#rule_template_framework").val('C-discount-Next');   
                                     };
                                  break;
                                case "group":                       
                                     if (minPurchaseSelect_sw == 'none') {
                                       $("#rule_template_framework").val('C-forThePriceOf-inCart');
                                     } else {
                                       $("#rule_template_framework").val('C-forThePriceOf-Next');
                                     };
                                  break; 
                                case "cheapest":                       
                                     if (minPurchaseSelect_sw == 'none') {
                                       $("#rule_template_framework").val('C-cheapest-inCart');
                                     } else {
                                       $("#rule_template_framework").val('C-cheapest-Next');
                                     };
                                  break;
                                case "nth":                       
                                       $("#rule_template_framework").val('C-nth-Next');
                                  break;                                                                                                                                                                         
                             };

                          };                          

                          //basic click function
                          function basicSelected() {   
                              //$(".box-border-line").hide("slow"); //v2.0.0
                              $("#buy_amt_mod_box_0").hide("slow"); 
                              $("#action_amt_mod_box_0").hide("slow"); 
                              $("#action_repeat_condition_box_0").hide("slow"); 
                              //$("#discount_applies_to_box_0").hide("slow");  //v2.0.0
                              $("#discount_lifetime_max_amt_type_box_0").hide("slow");    
                              $(".advanced-area").hide("slow"); 
                              $("#advanced-data-area").hide("slow");  
                              $(".show-in-adanced-mode-only").hide("slow");
                              //$("#discount_msgs_title").hide("slow");  //v2.0.0
                              /* v2.0.0 removed
                              jQuery('.buy_group_title-area').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');
                              jQuery('.get_group_title-area').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');
                              jQuery('#discount_amt_title_0').css('opacity', '0.4').css('filter', 'alpha(opacity=40)');
                              */
                              $("#only_for_this_coupon_box_0").hide("slow"); //v1.1.0.8
                              $("#buy-show-and-or-switches").hide("slow"); //v2.0.0	
                              $("#action-show-and-or-switches").hide("slow"); //v2.0.0	
                              $(".buy-and-or-selector").hide("slow"); //v2.0.0
                              $(".action-and-or-selector").hide("slow"); //v2.0.0	
                              if ( $("#pricing-type-select").val() != 'bulk') {
                                $("#discount_amt_row_0").show("slow"); //Discount Amount Type enable  //v2.0.0	
                              }
                          };
                                                                           
                          //advanced click function
                          function advancedSelected() {   
                              //$(".box-border-line").show("slow"); //v2.0.0
                              //v2.0.0 begin
                              if($('#cart-or-catalog-Cart').prop('checked')) { 
                                $("#action_repeat_condition_box_0").show("slow"); 
                                //$("#discount_applies_to_box_0").show("slow");   
                                $("#only_for_this_coupon_box_0").show();  
                              } else {
                                //dont show "show and/or" or and/or switching for CATALOG rules!
                                $("#buy-show-and-or-switches").hide("slow"); 
                                $("#action-show-and-or-switches").hide("slow"); 
                                $(".action-and-or-selector").hide("slow"); 
                                $(".buy-and-or-selector").hide("slow");                              
                              } 
                              //v2.0.0 end 
                              $("#discount_lifetime_max_amt_type_box_0").show("slow"); 
                              $(".advanced-area").show("slow"); 
                              $("#advanced-data-area").show("slow");
                              $(".show-in-adanced-mode-only").show("slow");

                                                            
                              //$("#discount_msgs_title").show("slow");  //v2.0.0 
                              jQuery('.buy_group_title-area').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                              jQuery('.get_group_title-area').css('opacity', '1').css('filter', 'alpha(opacity=100)');
                              jQuery('#discount_amt_title_0').css('opacity', '1').css('filter', 'alpha(opacity=100)');                              
                              
                              //v1.1.8.0 don't show for bulk
                              if ( $("#pricing-type-select").val() == 'bulk') {
                                 //don't show stuff not relevant to bulk purchasing  
                                 $("#buy_amt_box_0").hide();  //Buy Group Amount
                                 $("#buy_amt_mod_box_0").hide();  //Buy Min/Max
                                 $("#buy_repeat_box_0").hide(); //Rule Usage Count
                                 $("#discount_amt_row_0").hide(); //Discount Amount Type disable                                 
                              } else { 
                                  //in case bulk was chosen previously...
                                 if($('#cart-or-catalog-Cart').prop('checked')) { //v2.0.0
                                    //$("#buy_amt_box_0").show("slow");  //Buy Group Amount
                                    //$("#buy_amt_mod_box_0").show("slow");  //Buy Min/Max
                                    $("#buy_repeat_box_0").show("slow"); //Rule Usage Count
                                    //v2.0.0 begin
                                    show_amt_box_test();
                                    /*
                                    if ( ( $("#pricing-type-select").val() == 'all') ||
                                         ( $("#pricing-type-select").val() == 'simple') || 
                                         ( $("#pricing-type-select").val() == 'bulk')) {
                                       $("#buy_amt_box_0").hide(); //v2.0.0
                                       $("#buy_amt_mod_box_0").hide(); //v2.0.0                                    
                                    } else {
                                       $("#buy_amt_box_0").show("slow");  //Buy Group Amount
                                       $("#buy_amt_mod_box_0").show("slow");  //Buy Min/Max
                                    }
                                    */
                                    //v2.0.0 end
                                 }
                                 $("#discount_amt_row_0").show("slow"); //Discount Amount Type enable                              
                              }; 
                              
                              //v1.1.8.1 begin fix
                              //works fine with switch to advanced rule.                              
                              if ( $("#action_amt_type_0").val() == 'one') {
                                $("#action_amt_box_appliesto_0").hide();
                                $(".action_amt_mod_box_class_0").hide();                               
                              } else {
                                $("#action_amt_box_appliesto_0").show("slow"); 
                              }
                              if ( $("#popChoiceOut").val() != "sameAsInPop") {
                                $("#action_amt_mod_box_0").show("slow");
                              } else {
                                $("#action_amt_mod_box_0").hide();
                              }
                              //v1.1.8.1 end
                              
                              //v2.0.0 begin
                              if($('#cart-or-catalog-Cart').prop('checked')) {
                                if( $("#popChoiceIn").val() == "groups" ) {
                                  $("#buy-show-and-or-switches").show("slow"); 
                                  buy_group_show_and_or_switches_test();
                                }
                                if( $("#popChoiceOut").val() == "groups" ) {
                                  $("#action-show-and-or-switches").show("slow"); 	
                                  action_group_show_and_or_switches_test();
                                } 
                               }                             
                              //v2.0.0 end
                                
                          };
                          
                          //v2.0.0 new function
                          function show_amt_box_test() {  
                          /*
                              if ( ( $("#pricing-type-select").val() == 'all') ||
                                 ( $("#pricing-type-select").val() == 'simple') || 
                                 ( $("#pricing-type-select").val() == 'bulk') ||
                                 ( $("#pricing-type-select").val() == 'choose') ) {
                          */ 
                            if ( ( $("#pricing-type-select").val() == 'all') ||
                                 ( $("#pricing-type-select").val() == 'simple') ) {
                               $("#buy_amt_box_0").hide(); 
                               $("#buy_amt_mod_box_0").hide(); 
                               $("#buy_repeat_box_0").show("slow");                                   
                            } else {
                               $("#buy_amt_box_0").show("slow");  //Buy Group Amount
                               if($('#basicSelected').prop('checked')) {
                                  $("#buy_amt_mod_box_0").hide(); 
                               } else {
                                  $("#buy_amt_mod_box_0").show("slow");  //Buy Min/Max
                               }
                            }
                          };
                                                                             
                         //***********************************************
                         //  Reload Select Option Titles area
                         //***********************************************
                         
                         /*
                         Control the replacement of the text literals associated with selects
                         throughout the whole screen
                         
                         Set1 = default set
                         Set2 = change titles to be 'Discount' rather than 'Buy' or 'Get'
                         
                         Working off of hidden selects which contain these alternate titles
                          - Framework.php has the alternate titles labeled as 'title2'
                          - rules-ui.ph creates these hidden selects on the fly, based on the 
                            existence of these 'title2' entries.
                        
                        (Both title sets are needed, as we toggle between them...)
                         */ 
                         /* v2.0.0
                          function reload_PricingType_Titles1() {   
                              var selectobject=document.getElementById("buy-group-filter-select1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=pricing-type-select1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=pricing-type-select1]').find('option').eq(i).text();
                                     $('select[name=pricing-type-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_PricingType_Titles_catalog() {   
                              var selectobject=document.getElementById("pricing-type-select-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=pricing-type-select-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=pricing-type-select-catalog]').find('option').eq(i).text();
                                     $('select[name=pricing-type-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          
                          function reload_MinimumSelect_Titles1() {   
                              var selectobject=document.getElementById("buy-group-filter-select1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=minimum-purchase-select1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=minimum-purchase-select1]').find('option').eq(i).text();
                                     $('select[name=minimum-purchase-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          function reload_MinimumSelect_Titles_catalog() {   
                              var selectobject=document.getElementById("minimum-purchase-select-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=minimum-purchase-select-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=minimum-purchase-select-catalog]').find('option').eq(i).text();
                                     $('select[name=minimum-purchase-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          
                          function reload_BuyGroupFilterSelect_Titles1() {   
                              var selectobject=document.getElementById("buy-group-filter-select1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy-group-filter-select1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy-group-filter-select1]').find('option').eq(i).text();
                                     $('select[name=buy-group-filter-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_BuyGroupFilterSelect_Titles2() {    
                              var selectobject=document.getElementById("buy-group-filter-select2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy-group-filter-select2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy-group-filter-select2]').find('option').eq(i).text();
                                     $('select[name=buy-group-filter-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };
                          
                          function reload_BuyGroupFilterSelect_Titles_catalog() {    
                              var selectobject=document.getElementById("buy-group-filter-select-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy-group-filter-select-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy-group-filter-select-catalog]').find('option').eq(i).text();
                                     $('select[name=buy-group-filter-select]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          }; 
                          */                         
                          function reload_buy_amt_type_Titles1() {   
                              var selectobject=document.getElementById("buy_amt_type1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_type1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_type1]').find('option').eq(i).text();
                                     $('select[name=buy_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          

                          function reload_buy_amt_type_Titles_catalog() {    
                              var selectobject=document.getElementById("buy_amt_type-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_amt_type-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_amt_type-catalog]').find('option').eq(i).text();
                                     $('select[name=buy_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                                                    
                          function reload_buy_repeat_condition_Titles1() {   
                              var selectobject=document.getElementById("buy_repeat_condition1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_repeat_condition1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_repeat_condition1]').find('option').eq(i).text();
                                     $('select[name=buy_repeat_condition_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_buy_repeat_condition_Titles_catalog() {    
                              var selectobject=document.getElementById("buy_repeat_condition-catalog");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=buy_repeat_condition-catalog]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=buy_repeat_condition-catalog]').find('option').eq(i).text();
                                     $('select[name=buy_repeat_condition_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_action_amt_type_Titles1() {   
                              var selectobject=document.getElementById("action_amt_type1");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=action_amt_type1]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=action_amt_type1]').find('option').eq(i).text();
                                     $('select[name=action_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };                          
                          function reload_action_amt_type_Titles2() {    
                              var selectobject=document.getElementById("action_amt_type2");
                              for (var i = 0; i < selectobject.length; i++) {
                                  if ($('select[name=action_amt_type2]').find('option').eq(i).text() > ' ') {
                                     var newtitle = $('select[name=action_amt_type2]').find('option').eq(i).text();
                                     $('select[name=action_amt_type_0]').find('option').eq(i).text(newtitle)
                                  };
                              };
                          };

                         //END  Reload Select Option Titles area                               
                                                       
                                                        
            //*************************************
            // MASTER TEMPLATE choice routine
            //*************************************  
                            
                            // React to Template choices in the upper DROPDOWNs
                            function ruleTemplateChanged() {                           
                                //SKIP if 1st time through!!  
                                if ($("#firstTimeBackFromServer").val() == 'yes') {  
                                	return;
                                } 
                                
                                //get rid of all values on template change only!!
                                $("#templateChanged").val('yes');
                                resetAllDropdowns();
                                resetAllSelections();
                                initAllValues();
                     //don't do this twice...           hideRemainderOfAllBoxes();
                                ruleTemplateTest();
                                changeCumulativeSwitches();                                  
                             //   $("#templateChanged").val('no');
                            };                                                           
                                                                       
                            function ruleTemplateTest() { 
                              hideRemainderOfAllBoxes();
                              switch( $("#rule_template_framework").val() ) {
                                case "0":                       noTemplateChoiceYet();              break;
                                case "D-storeWideSale":         storeWideSale();                    break;
                                case "D-simpleDiscount":        simpleMembershipDiscount();
                                                                disableAllDiscountLimits();   //v1.0.4  moved here to prevent interference with cart simple discount usage...          
                                                                                                    break;
                                case "C-storeWideSale":         process_C_storeWideSale();          break;
                                case "C-simpleDiscount":        process_C_simpleDiscount();         break;
                                case "C-bulkDiscount":          process_C_simpleDiscount();         break; //v1.1.8.0
                                case "C-discount-inCart":       process_C_discount_inCart();        break;
                                case "C-forThePriceOf-inCart":  process_C_forThePriceOf_inCart();   break;
                                case "C-cheapest-inCart":       process_C_cheapest_inCart();        break;
                                case "C-discount-Next":         process_C_discount_Next();          break;
                                case "C-forThePriceOf-Next":    process_C_forThePriceOf_Next();     break;
                                case "C-cheapest-Next":         process_C_cheapest_Next();          break;
                                case "C-nth-Next":              process_C_nth_Next();               break;                              
                              };
                            }; 
                              
                            function noTemplateChoiceYet() { 
                              //keep all these for 1st time through
                              resetAllDropdowns();
                              resetAllSelections();
                              initAllValues();
                              hideRemainderOfAllBoxes();  
                            };
                               
                            function process_C_storeWideSale() { 
                              storeWideSale(); 
                              //  override  discount_appliesTo_protect1() done in storeWideSale() 
                              discount_appliesTo_protect2(); //allow 'each' or 'all'
                              enableAllDiscountLimits();
                              buyAmtType_repeat_reset(); //mwntest  
                            };
                            function process_C_simpleDiscount() { 
                              simpleMembershipDiscount(); 
                            //  buyAmtType_qualifier_reset();
                              buyAmtType_qualifier_protect1();
                              buyAmtType_repeat_reset();
                              //  override  discount_appliesTo_protect1() done in simpleMembershipDiscount() 
                              discount_appliesTo_protect2(); //allow 'each' or 'all'
                              enableAllDiscountLimits();
                               
                            };                                                     
                            function storeWideSale() {    //Store-Wide Sale  -Display
                              setDropdownsToInitalDefaults();
                              actionAmtType_change_text_to_remove_Next();
                              setWholeStoreOrCartContentsIn();//only on whole store template                           
                              discount_appliesTo_protect1();// set 'each' only 
                              buyAmtType_appliesTo_protect2(); //set each only
                         //     blockAllButSimpleOptions();                       
                              disableAllDiscountLimits();
                              testForExistingData();
                     //         $("#buy_group_box").show("slow");
                     //         $("#action_group_box").show("slow");  
                            };
                            function simpleMembershipDiscount() {    //Membership Discount   -Display
                              setDropdownsToInitalDefaults();                     
                            //  disableAllDiscountLimits();   //v1.0.4  moved to a different location, interfered with cart simple discount usage...  
                              actionAmtType_change_text_to_remove_Next();
                              discountAmtType_protect1();                  
                              setSameAsBuyGroupOnly();                                                            
                              discount_appliesTo_protect1();// set 'each' only
                              buyAmtType_appliesTo_protect2(); //set each only
                              testForExistingData(); 
                            };
                             
                            function process_C_discount_inCart() {    //Buy 5/$500, get a discount for Some/All 5      -Cart
                              enableAllDiscountLimits();
                              actionAmtType_change_text_to_remove_Next();                              
                              setSameAsBuyGroupOnly();
                              actionAmtType_protect4();
                              discountAmtType_protect1();                                                            
                              discount_appliesTo_protect2(); //set 'each' and 'all' as valid only
                              testForExistingData();
                              buy_amt_line_remainder_chg(); //expose rest of buy line... 
                              action_amt_line_remainder_chg(); //expose rest of action line... 
                            };
                            function process_C_forThePriceOf_inCart() {    //Buy 5, get them for the price of 4/$400       -Cart
                              enableAllDiscountLimits();
                              actionAmtType_change_text_to_remove_Next();
                              setAttribsFor_ForThePriceOf();
                              testForExistingData();                              
                              setSameAsBuyGroupOnly();
                            };
                            function process_C_cheapest_inCart() {    //Buy 5/$500, get the cheapest/most expensive at a discount     -Cart
                              enableAllDiscountLimits();
                              actionAmtType_change_text_to_remove_Next();                                                             
                              setSameAsBuyGroupOnly();                              
                              setAttribsFor_Cheapest();
                              testForExistingData(); 
                            };
                            //This option has the most breadth....
                            function process_C_discount_Next() {    // Buy 5/$500, get a discount on Next 4/$400 - Cart                            
                              enableAllDiscountLimits();
                              setAttribsFor_nextNumOrCurrency(); 
                              actionAmtType_change_text_to_include_Next();                             
                              testForExistingData();
                              
                            };
                            function process_C_forThePriceOf_Next() {    // Buy 5/$500, get next 3 for the price of 2/$200 - Cart
                              enableAllDiscountLimits();
                              setAttribsFor_nextForThePriceOf();
                              actionAmtType_change_text_to_include_Next();
                              testForExistingData();
                              
                            };
                            function process_C_cheapest_Next() {    // Buy 5/$500, get a discount on the cheapest/most expensive when next 5/$500 purchased - Cart
                              enableAllDiscountLimits();
                              setAttribsFor_NextCheapest();
                              actionAmtType_change_text_to_include_Next();                             
                              testForExistingData();
                              
                            };
                            function process_C_nth_Next() {    // Buy 5/$500, get the following Nth at a discount - Cart                           
                              enableAllDiscountLimits();
                              setAttribsFor_NextNth();
                              actionAmtType_change_text_to_include_Next();                             
                              testForExistingData();
                              
                            };
                                                
                            function resetAllDropdowns() {
                              buyAmtType_reset();
                              buyAmtType_appliesTo_reset();
                              buyAmtType_qualifier_reset();
                              buyAmtType_repeat_reset();                              
                              actionAmtType_reset();
                              actionAmtType_appliesTo_reset();
                              actionAmtType_qualifier_reset();
                              actionAmtType_repeat_reset();                               
                              discountAmtType_reset();
                              discount_appliesTo_reset();
                              popChoiceIn_reset();
                              popChoiceOut_reset();
                              hideChoiceIn(); 
                              hideChoiceOut();                              
                            };                      
                            
                            function initAllValues() {      
                              $('.buy_amt_count').val(' ');
                              $('.buy_amt_mod_count').val(' ');
                              $('.buy_repeat_count').val(' ');
                              $('.action_amt_count').val(' ');
                              $('.action_amt_mod_count').val(' ');
                              $('.action_repeat_count').val(' ');
                              $('.discount_amt_count').val(' ');
                              $('.forThePriceOf-amt-literal-inserted').val(' '); 
                              $('.discount_auto_add_free_product').removeAttr('checked');                               
                              $('#discount_rule_max_amt_count_0').val(' ');
                              $('#discount_rule_max_amt_msg').val(' ');
                              $('#discount_lifetime_max_amt_count_0').val(' ');
                              $('#discount_lifetime_max_amt_msg').val(' ');
                              $('#discount_rule_cum_max_amt_count_0').val(' ');
                              $('#discount_rule_cum_max_amt_msg').val(' ');
                              $(".vtprd-error").hide("slow");  //hide all previous errors
                              $(".vtprd-error2").hide("slow");  //hide all previous errors
                              /*Group fields*/
                              $("#ruleApplicationPriority_num").val('10'); 
                              
                              var elem = document.getElementById("discount_product_full_msg");
                              elem.value = $("#fullMsg").val();//hidden field with lit
                              jQuery('#discount_product_full_msg').css('color', 'rgb(152, 152, 152) !important').css("font-style","italic");
                              
                              var elem = document.getElementById("discount_product_short_msg");
                              if($('#cart-or-catalog-Cart').prop('checked')) { //v2.0.0                                 
                                elem.value = $("#shortMsg").val();//hidden field with lit
                                jQuery('#discount_product_short_msg').css('color', 'rgb(152, 152, 152) !important').css("font-style","italic");
                              } else {
                                //set Checkout Message  =  "Unused for Catalog Discount"
                                elem.value = $("#catalogCheckoutMsg").val();//hidden field with lit
                              }
                              
                              //v1.1.0.8
                              var elem = document.getElementById("only_for_this_coupon_name");
                              elem.value = $("#couponMsg").val();//hidden field with lit
                              jQuery('#only_for_this_coupon_name').css('color', 'rgb(152, 152, 152) !important').css("font-style","italic");
                                
                            };                                                      

                            function blockAllButSimpleOptions() { 
                              //disable Discount Limits Options
                              $('#discount_rule_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_currency_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('disabled', true);

                              // force cum_max switches change to 'no'
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('disabled', true);

                              //disable Discount Limits Options
                              jQuery('#discount_rule_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_quantity_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_currency_0').css('color', 'black');                              
                            }; 

                            function disableAllDiscountLimits() { 
                              //disable Discount Limits Options
                              $('#discount_rule_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_max_amt_type_currency_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('disabled', true);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('disabled', true);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('disabled', true);
                              
                              //disable Discount Limits Options
                              jQuery('#discount_rule_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_quantity_0').css('color', 'black');
                              jQuery('#discount_lifetime_max_amt_type_currency_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_percent_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_qty_0').css('color', 'black');
                              jQuery('#discount_rule_cum_max_amt_type_currency_0').css('color', 'black'); 
                              
                              //disable selected values:  discount_lifetime_max_amt_type_none_0
                              $('#discount_rule_max_amt_type_percent_0').attr('selected', false);
                              $('#discount_rule_max_amt_type_qty_0').attr('selected', false);
                              $('#discount_rule_max_amt_type_currency_0').attr('selected', false);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('selected', false);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('selected', false);
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('selected', false);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('selected', false);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('selected', false); 
                              
                              //set selected values:  
                              $('#discount_lifetime_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_cum_max_amt_type_none_0').attr('selected', true);
                                                         
                            };                                                                                                            
                            
                            function enableAllDiscountLimits() {
                              
                              //only do this in the PRO version...
                              switch( $("#pluginVersion").val() ) {
                               case 'freeVersion':
                                  return;
                                 break; 
                               default:
                                 break;                                                                  
                              };
                              
                              //enable Discount Limits Options
                              $('#discount_rule_max_amt_type_percent_0').attr('disabled', false);
                              $('#discount_rule_max_amt_type_qty_0').attr('disabled', false);
                              $('#discount_rule_max_amt_type_currency_0').attr('disabled', false);
                              $('#discount_lifetime_max_amt_type_quantity_0').attr('disabled', false);
                              $('#discount_lifetime_max_amt_type_currency_0').attr('disabled', false);
                              $('#discount_rule_cum_max_amt_type_percent_0').attr('disabled', false);
                              $('#discount_rule_cum_max_amt_type_qty_0').attr('disabled', false);
                              $('#discount_rule_cum_max_amt_type_currency_0').attr('disabled', false);

                              jQuery('#discount_rule_max_amt_type_percent_0').css('color', '#0077BB');
                              jQuery('#discount_rule_max_amt_type_qty_0').css('color', '#0077BB');
                              jQuery('#discount_rule_max_amt_type_currency_0').css('color', '#0077BB');
                              jQuery('#discount_lifetime_max_amt_type_quantity_0').css('color', '#0077BB');
                              jQuery('#discount_lifetime_max_amt_type_currency_0').css('color', '#0077BB');
                              jQuery('#discount_rule_cum_max_amt_type_percent_0').css('color', '#0077BB');
                              jQuery('#discount_rule_cum_max_amt_type_qty_0').css('color', '#0077BB');
                              jQuery('#discount_rule_cum_max_amt_type_currency_0').css('color', '#0077BB');                              
                            };

                            //RESET ALL VALUES TO DEFAULT  
                            function resetAllSelections() {
                              $('#buy_amt_type_none_0').attr('selected', true);
                              $('#cartChoiceIn').attr('selected', true); 
                              $('#buy_amt_applies_to_all_0').attr('selected', true); /*v1.1.8.0 changed to ALL*/
                              $('#buy_amt_mod_none_0').attr('selected', true);
                              //$('#buy_repeat_condition_none_0').attr('selected', true);
                              $('#buy_repeat_condition_unlimited_0').attr('selected', true);
                              $('#action_amt_type_none_0').attr('selected', true);
                              $('#sameChoiceOut').attr('selected', true);
                              $('#action_amt_mod_none_0').attr('selected', true);
                              $('#action_amt_applies_to_all_0').attr('selected', true);
                              $('#action_repeat_condition_none_0').attr('selected', true);
                              $('.discount_amt_type_heading').attr('disabled', true);
                              $('#discount_amt_type_heading_0').attr('selected', true); 
                              $('#discount_applies_to_each_0').attr('selected', true);
                              //$('#').attr('selected', true);
                              $('#discount_lifetime_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_max_amt_type_none_0').attr('selected', true);
                              $('#discount_rule_cum_max_amt_type_none_0').attr('selected', true);
                              $('#cumulativeRulePricingNo').attr('selected', true);
                              $('#cumulativeCouponPricingNo').attr('selected', true);
                              $('#cumulativeSalePricingAddTo').attr('selected', true);        
                              $('.forThePriceOf-amt-literal-inserted').remove();
                            };       
                            
                            
                            function setDropdownsToInitalDefaults() {
                              buyAmtType_protect1();
                              buyAmtType_appliesTo_protect1();
                              buyAmtType_qualifier_protect1();
                              buyAmtType_repeat_protect1();                              
                              actionAmtType_protect1();
                              actionAmtType_appliesTo_protect1();
                              actionAmtType_qualifier_protect1();
                              actionAmtType_repeat_protect1();                               
                              discountAmtType_protect1();  //also does the reset..
                              discount_appliesTo_protect1();
                            };
                                                                                   
                            //testForExistingData, only done on return from server
                            function testForExistingData() {  
                              if ($("#firstTimeBackFromServer").val() != 'yes') {
                                 return;
                              };
                              
                              //MWN moved to "firstTimeThroughControl()" ==> "resetUpperFirstTimeSwitches()"
                              //reset the switch, only used 1st time in from server, in hidden html field...
                              //$("#firstTimeBackFromServer").val('no');
                              
                              //don't do this if template yet to be chosen, on data from server
                              if ($("#rule_template_framework").val() != "0") {
                                  buy_amt_line_remainder_chg();
                                  buy_amt_mod_count_area_chg();
                                  buy_repeat_count_area_chg();
                                  action_amt_line_remainder_chg();
                                  action_amt_mod_count_area_chg();
                                  action_repeat_count_area_chg();
                                  discount_amt_line_remainder_chg();
                                  discount_rule_max_amt_count_area_chg();
                                  discount_lifetime_max_amt_count_area_chg();
                                  cumulativeRulePricing_chg();
                                  discount_rule_cum_max_amt_count_area_chg();
                                  popChoiceInTest();   // Tests for 'selected...' 
                                  popChoiceOutTest();  // Tests for 'selected...' 
                              };
                              
                               
                            }; 
                            
                            function hideRemainderOfAllBoxes() { 
                              $(".buy_amt_line_remainder").hide();
                              $("#action_amt_line_remainder_0").hide();
                              /*$(".discount_amt_line_remainder").hide();*/ 
                              $("#buy_amt_mod_count_area_0").hide();
                              $("#buy_repeat_count_area_0").hide();
                              $("#action_amt_mod_count_area_0").hide();
                              $("#action_repeat_count_area_0").hide();
                              $("#discount_amt_count_area_0").hide();  
                              $("#discount_for_the_price_of_area_0").hide();
                              $("#discount_auto_add_free_product_label_0").hide();                 
                          //    $("#priority_num").hide();   Handled elsewhere
                              $("#advanced-settings-anchor-plus").hide();    
                              $("#advanced-settings-anchor-minus").hide();
                              $("#advanced-settings-info").hide();  
                              $("#discount_rule_max_amt_msg").hide();
                              $("#discount_rule_max_amt_count_area").hide();
                              $("#discount_lifetime_max_amt_msg").hide();
                              $("#discount_lifetime_max_amt_count_area").hide();
                              $("#discount_rule_cum_max_amt_msg").hide();
                              $("#discount_rule_cum_max_amt_count_area").hide();
                            }; 

                            
                            /*  Buy Amount Type   */                                                         
                            //    protect all except *'none'*      
                            function buyAmtType_protect1() { 
                              $('.buy_amt_type_none').attr('disabled', false);
                              $('.buy_amt_type_none').attr('selected', true);
                              $('.buy_amt_type_one').attr('disabled', true);
                              $('.buy_amt_type_qty').attr('disabled', true);
                              $('.buy_amt_type_currency').attr('disabled', true);
                              $('.buy_amt_type_nthQty').attr('disabled', true);
                            }; 
                            //    protect all except *'nthQty'*      
                            function buyAmtType_protect2() { 
                              $('.buy_amt_type_none').attr('disabled', true);
                              $('.buy_amt_type_one').attr('disabled', true);
                              $('.buy_amt_type_qty').attr('disabled', true);
                              $('.buy_amt_type_currency').attr('disabled', true);
                              $('.buy_amt_type_nthQty').attr('disabled', false);
                              $('.buy_amt_type_nthQty').attr('selected', true);
                            };                                 
                            //     reset the attributs and selections
                            function buyAmtType_reset() { 
                              $('.buy_amt_type_none').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_type_none').attr('selected', true);
                              };    
                              $('.buy_amt_type_one').attr('disabled', false);
                              $('.buy_amt_type_qty').attr('disabled', false);
                              $('.buy_amt_type_currency').attr('disabled', false);
                              $('.buy_amt_type_nthQty').attr('disabled', false);                              
                            };
                             
                             //buyAmtType_appliesTo
                            function buyAmtType_appliesTo_protect1() { 
                              $('.buy_amt_applies_to_all').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_applies_to_all').attr('selected', true);
                              };                               
                              //v2.0.0.2  begin - if package deal, allow 'applies to each'
                              if ( $("#pricing-type-select").val() == 'group') {  
                                $('.buy_amt_applies_to_each').attr('disabled', false);
                              } else {                          
                                $('.buy_amt_applies_to_each').attr('disabled', true);
                              };
                              //v2.0.0.2  end                               
                            };
                            function buyAmtType_appliesTo_protect2() { 
                              $('.buy_amt_applies_to_all').attr('disabled', true);
                              $('.buy_amt_applies_to_each').attr('disabled', false);
                              $('.buy_amt_applies_to_each').attr('selected', true);                              
                              
                            };
                             function buyAmtType_appliesTo_reset() { 
                              $('.buy_amt_applies_to_all').attr('disabled', false);    
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_applies_to_all').attr('selected', true);
                              }; 
                              $('.buy_amt_applies_to_each').attr('disabled', false);
                            };
                             
                            function buyAmtType_qualifier_protect1() { 
                              $('.buy_amt_mod_none').attr('disabled', false);
                              $('.buy_amt_mod_none').attr('selected', true);
                              $('.buy_amt_mod_minCurrency').attr('disabled', true);
                              $('.buy_amt_mod_maxCurrency').attr('disabled', true);
                            };
                            function buyAmtType_qualifier_reset() { 
                              $('.buy_amt_mod_none').attr('disabled', false)        
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_amt_mod_none').attr('selected', true);
                              };
                              $('.buy_amt_mod_minCurrency').attr('disabled', false);
                              $('.buy_amt_mod_maxCurrency').attr('disabled', false);
                            };
                            
                            function buyAmtType_repeat_protect1() { 
                              
                              switch( $("#rule_template_framework").val() ) {
                                case "0":                    
                                case "D-storeWideSale":   
                                case "D-simpleDiscount":                                                            
                                    $('.buy_repeat_condition_none').attr('disabled', false);
                                    
                                    //MWNTEST
                                    //$('.buy_repeat_condition_none').attr('selected', true);
                                    if ($("#templateChanged").val() == 'yes') {
                                      $('.buy_repeat_condition_none').attr('selected', true);
                                    };
                                    
                                    $('.buy_repeat_condition_unlimited').attr('disabled', true);
                                    $('.buy_repeat_condition_count').attr('disabled', true);
                                  break;
                                default:
                                  break;
                              };
                            };
                            function buyAmtType_repeat_reset() { 
                              $('.buy_repeat_condition_none').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.buy_repeat_condition_unlimited').attr('selected', true);
                              };
                              $('.buy_repeat_condition_unlimited').attr('disabled', false);
                              $('.buy_repeat_condition_count').attr('disabled', false);
                            };
                            function buyAmtType_repeat_reset2() { 
                              $('.buy_repeat_condition_none').attr('disabled', false);
                              $('.buy_repeat_condition_unlimited').attr('disabled', false);
                              $('.buy_repeat_condition_count').attr('disabled', false);
                            };                            
                                                         
                             /*  Action Amount Type   */                                                         
                            //    protect all except *'zero'*      
                            function actionAmtType_protect1() { 
                              $('.action_amt_type_none').attr('disabled', false);
                              $('.action_amt_type_none').attr('selected', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', true);
                              $('.action_amt_type_currency').attr('disabled', true);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                            }; 
                            //    protect all except *'nthQty'*      
                            function actionAmtType_protect2() { 
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', true);
                              $('.action_amt_type_currency').attr('disabled', true);
                              $('.action_amt_type_nthQty').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_nthQty').attr('selected', true);
                              };
                            };
                            function actionAmtType_protect3() { 
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_qty').attr('selected', true);
                              };                              
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                            };  
                            function actionAmtType_protect4() { 
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                               // $('.action_amt_type_zero').attr('selected', true);
                                $('.action_amt_type_qty').attr('selected', true);
                              };
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', false);                              
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                            };
                            function actionAmtType_protect5() {  //standard 'next' behavior  
                              $('.action_amt_type_none').attr('disabled', true);
                            //  $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', false);
                              $('.action_amt_type_qty').attr('disabled', false);                              
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_one').attr('selected', true);                               
                              };
                            };                                
                            //     reset the attributs and selections
                            function actionAmtType_reset() { 
                              $('.action_amt_type_none').attr('disabled', false);     
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_type_none').attr('selected', true);
                              };
                           //   $('.action_amt_type_zero').attr('disabled', false);
                              $('.action_amt_type_one').attr('disabled', false);
                              $('.action_amt_type_qty').attr('disabled', false);
                              $('.action_amt_type_currency').attr('disabled', false);
                              $('.action_amt_type_nthQty').attr('disabled', false);
                            };
                            
                            //********************************************************************************************************
                            //  If template type is 'next', change the action type dropdown wording to include 'Next on the fly'
                            //********************************************************************************************************
                            function actionAmtType_change_text_to_include_Next() { 
                              reload_action_amt_type_Titles2(); 
                            };
                            function actionAmtType_change_text_to_remove_Next() {  
                              reload_action_amt_type_Titles1();  
                            };

                             
                             //actionAmtType_appliesTo
                             function actionAmtType_appliesTo_protect1() { 
                              $('.action_amt_applies_to_all').attr('disabled', false);
                              $('.action_amt_applies_to_all').attr('selected', true);
                              $('.action_amt_applies_to_each').attr('disabled', true);
                            };
                             function actionAmtType_appliesTo_reset() { 
                              $('.action_amt_applies_to_all').attr('disabled', false);  
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_applies_to_all').attr('selected', true);
                              };
                              $('.action_amt_applies_to_each').attr('disabled', false);
                            };                            
                                                         
                            //actionAmtType_qualifier_protect1
                            function actionAmtType_qualifier_protect1() { 
                              $('.action_amt_mod_none').attr('disabled', false);
                              $('.action_amt_mod_none').attr('selected', true);
                              $('.action_amt_mod_minCurrency').attr('disabled', true);
                              $('.action_amt_mod_maxCurrency').attr('disabled', true);
                            };
                            function actionAmtType_qualifier_reset() { 
                              $('.action_amt_mod_none').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_amt_mod_none').attr('selected', true);
                              };
                              $('.action_amt_mod_minCurrency').attr('disabled', false);
                              $('.action_amt_mod_maxCurrency').attr('disabled', false);
                            };                            
  
                            //actionAmtType_qualifier_protect1
                            function actionAmtType_repeat_protect1() { 
                              $('.action_repeat_condition_none').attr('disabled', false);
                              $('.action_repeat_condition_none').attr('selected', true); 
                              $('.action_repeat_condition_unlimited').attr('disabled', true);
                              $('.action_repeat_condition_count').attr('disabled', true);
                            };                             
                            function actionAmtType_repeat_reset() { 
                              $('.action_repeat_condition_none').attr('disabled', false); 
                              if ($("#templateChanged").val() == 'yes') {
                                $('.action_repeat_condition_none').attr('selected', true);
                              };
                              $('.action_repeat_condition_unlimited').attr('disabled', false);
                              $('.action_repeat_condition_count').attr('disabled', false);
                            };  
                            
                             /*  Discount Amount Type   */                                                         
                            //    protect none except *'discount_amt_type_forThePriceOf_Units'*      
                            function discountAmtType_protect1() {
                              $('.discount_amt_type_heading').attr('disabled', true); 
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_amt_type_heading').attr('selected', true);
                              };
                              $('.discount_amt_type_percent').attr('disabled', false);
                              $('.discount_amt_type_currency').attr('disabled', false);
                              $('.discount_amt_type_fixedPrice').attr('disabled', false);
                              $('.discount_amt_type_free').attr('disabled', false);    
                              $('.discount_amt_type_forThePriceOf_Units').attr('disabled', true);
                              $('.discount_amt_type_forThePriceOf_Currency').attr('disabled', true);
                            }; 
                            function discountAmtType_protect2() {
                              $('.discount_amt_type_heading').attr('disabled', true);
                              $('.discount_amt_type_percent').attr('disabled', true);
                              $('.discount_amt_type_currency').attr('disabled', true);
                              $('.discount_amt_type_fixedPrice').attr('disabled', true);
                              $('.discount_amt_type_free').attr('disabled', true);    
                              $('.discount_amt_type_forThePriceOf_Units').attr('disabled', false);
                              $('.discount_amt_type_forThePriceOf_Currency').attr('disabled', false);
                              switch( $("#discount_amt_type_0").val() ) {     //v1.0.7.6 switched defaults                                
                                  case "forThePriceOf_Units":
                                     $('.discount_amt_type_forThePriceOf_Units').attr('selected', true); break;                                                                                   
                                  default:  //case "forThePriceOf_Units":  set as default in case no choice as yet for discount_amt_type        
                                     $('.discount_amt_type_forThePriceOf_Currency').attr('selected', true);  break;
                              };
                              // //v1.0.7.6 end                              
                            }; 
                            function discountAmtType_reset() { 
                              $('.discount_amt_type_heading').attr('disabled', true);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_amt_type_heading').attr('selected', true);
                              };
                              $('.discount_amt_type_percent').attr('disabled', false);
                              $('.discount_amt_type_currency').attr('disabled', false);
                              $('.discount_amt_type_fixedPrice').attr('disabled', false);
                              $('.discount_amt_type_free').attr('disabled', false);
                              $('.discount_amt_type_forThePriceOf_Units').attr('disabled', false);
                              $('.discount_amt_type_forThePriceOf_Currency').attr('disabled', false);
                            };

                             
                             //discount_appliesTo
                             function discount_appliesTo_protect1() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', false);
                              //$('.discount_applies_to_each').attr('selected', true);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_each').attr('selected', true);
                              };                             
                              $('.discount_applies_to_all').attr('disabled', true);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                             function discount_appliesTo_protect2() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', false); 
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_each').attr('selected', true);
                              };
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                             function discount_appliesTo_protect3() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', true);
                              $('.discount_applies_to_all').attr('disabled', true);
                              $('.discount_applies_to_cheapest').attr('disabled', false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_cheapest').attr('selected', true);
                              };
                              $('.discount_applies_to_most_expensive').attr('disabled', false);
                            };
                             function discount_appliesTo_protect4() { //used only for 'forthepriceof'
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', true);  
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_all').attr('selected', true);
                              };
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                            //v1.0.7.6 function added
                            function discount_appliesTo_protect5() { 
                              $('.discount_applies_to_title').attr('disabled', true);
                              $('.discount_applies_to_each').attr('disabled', false);  
                              $('.discount_applies_to_all').attr('selected', true);
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', true);
                              $('.discount_applies_to_most_expensive').attr('disabled', true);
                            };
                             function discount_appliesTo_reset() { 
                              $('.discount_applies_to_title').attr('disabled',false);
                              if ($("#templateChanged").val() == 'yes') {
                                $('.discount_applies_to_title').attr('selected', true);
                              };
                              $('.discount_applies_to_each').attr('disabled', false);
                              $('.discount_applies_to_all').attr('disabled', false);
                              $('.discount_applies_to_cheapest').attr('disabled', false);
                              $('.discount_applies_to_most_expensive').attr('disabled', false);
                            };
                            
                            //  setAttribsFor_ForThePriceOf
                             function setAttribsFor_ForThePriceOf() {
                                switch( $("#discount_amt_type_0").val() ) {                                    
                                  case "forThePriceOf_Units":
                                     setAttribsFor_ForThePriceOf_Units();     
                                     break;                                                                                   
                                  default:  //set currency as default in case no choice as yet for discount_amt_type                                      
                                     setAttribsFor_ForThePriceOf_Currency();
                                     break;
                                };
                                //v1.0.7.6 end                                
                             };
                              
                             function setAttribsFor_ForThePriceOf_Currency() {  
                              $('#buy_amt_type_none_0').attr('disabled', true);              
                              $('#buy_amt_type_one_0').attr('disabled', true);
                              $('#buy_amt_type_qty_0').attr('disabled', false);
                              $('#buy_amt_type_qty_0').attr('selected', true);
                              $('#buy_amt_type_currency_0').attr('disabled', true);
                              $('#buy_amt_type_nthQty_0').attr('disabled', true);                               
                              buy_amt_line_remainder_chg();
                              buyAmtType_appliesTo_protect1();
                              actionAmtType_protect1();
                              discountAmtType_protect2();  
                              $("#discount_amt_count_area_0").show("slow");
                              $(".discount_amt_count_literal_0").hide("slow");
                              $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow"); 
                              $("#discount_amt_count_literal_forThePriceOf_0").show("slow");
                              $("#discount_amt_count_literal_forThePriceOf_Currency_0").show("slow"); 
                              discount_appliesTo_protect4(); 
                              $("#discount_amt_count_literal_units_area_0").hide("slow");  
                            };
                            
                             function setAttribsFor_ForThePriceOf_Units() {  
                              $('#buy_amt_type_none_0').attr('disabled', true);              
                              $('#buy_amt_type_one_0').attr('disabled', true);
                              $('#buy_amt_type_qty_0').attr('disabled', false);
                              $('#buy_amt_type_qty_0').attr('selected', true);
                              $('#buy_amt_type_currency_0').attr('disabled', true);
                              $('#buy_amt_type_nthQty_0').attr('disabled', true);                               
                              buy_amt_line_remainder_chg();
                              buyAmtType_appliesTo_protect1();                              
                              actionAmtType_protect1();
                              discountAmtType_protect2(); 
                              $("#discount_amt_count_area_0").show("slow");
                              $(".discount_amt_count_literal_0").hide("slow");
                              $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow"); 
                              $("#discount_amt_count_literal_forThePriceOf_0").show("slow");
                              $("#discount_amt_count_literal_forThePriceOf_Currency_0").hide("slow");   
                              $("#discount_amt_count_literal_units_area_0").show("slow");
                              discount_appliesTo_protect4();  
                            };
                            
                            //  setAttribsFor_Cheapest
                             function setAttribsFor_Cheapest() { 
                              $('#buy_amt_type_one_0').attr('disabled', true);
                              $('#buy_amt_type_nthQty_0').attr('disabled', true);                               
                              buyAmtType_appliesTo_protect1();                             
                              actionAmtType_protect1();
                              discountAmtType_protect1();   
                              discount_appliesTo_protect3();  
                            };
                            
                            //  setAttribsFor_nextNumOrCurrency
                             function setAttribsFor_nextNumOrCurrency() { 
                              discountAmtType_protect1();   
                              discount_appliesTo_protect2();
                              actionAmtType_protect5();  
                            };
                            
                            //  setAttribsFor_nextForThePriceOf
                             function setAttribsFor_nextForThePriceOf() { 
                              $('.action_amt_type_none').attr('disabled', true);
                          //    $('.action_amt_type_zero').attr('disabled', true);
                              $('.action_amt_type_one').attr('disabled', true);
                              $('.action_amt_type_qty').attr('disabled', false);
                              $('.action_amt_type_qty').attr('selected', true);
                              $('.action_amt_type_currency').attr('disabled', true);
                              $('.action_amt_type_nthQty').attr('disabled', true);
                              action_amt_line_remainder_chg();
                              discountAmtType_protect2();
                              $("#discount_amt_count_area_0").show("slow"); 
                              $(".discount_amt_count_literal_0").hide("slow"); 
                              $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow");
                              $("#discount_amt_count_literal_forThePriceOf_0").show("slow");   
                              $("#discount_amt_count_literal_units_area_0").show("slow");  
                              discount_appliesTo_protect4(); 
                            };

                            //  setAttribsFor_NextCheapest
                             function setAttribsFor_NextCheapest() {                              
                              actionAmtType_protect3();
                              action_amt_line_remainder_chg();
                              actionAmtType_appliesTo_protect1()
                              discountAmtType_protect1();   
                              discount_appliesTo_protect3(); 
                            };

                            //  setAttribsFor_NextNth
                             function setAttribsFor_NextNth() {                               
                              actionAmtType_protect2();
                              action_amt_line_remainder_chg();
                              discountAmtType_protect1();   
                              discount_appliesTo_protect2(); 
                            };
                                                 
                            /*  PopChoiceIn/Out   */           
                            //    protect protect all except All Store ('cartChoiceIn')
                            function setWholeStoreOrCartContentsIn() {
                              //inPopRadio                             
                              $('#cartChoiceIn').attr('selected', true);
                              $('#groupChoiceIn').attr('disabled', true);
                            };   
                            function setSameAsBuyGroupOnly() {
                              //outPopRadio                              
                              $('#sameChoiceOut').attr('selected', true);
                              $('#cartChoiceOut').attr('disabled', true);
                              $('#groupChoiceOut').attr('disabled', true);                            
                            };                                                    
                            function popChoiceIn_reset() {
                              //inPopRadio
                              $('#cartChoiceIn').attr('disabled', false);
                              $('#groupChoiceIn').attr('disabled', false);                           
                            };
                            function popChoiceOut_reset() {
                              //inPopRadio
                              $('#sameChoiceOut').attr('disabled', false);
                              $('#cartChoiceOut').attr('disabled', false);
                              $('#groupChoiceOut').attr('disabled', false); //v2.0.0                           
                            };
                            //KEEP this
                            function changeCumulativeSwitches() {
                              /* always SET THESE TO 'YES' BY DEFAULT!!!!
                              switch( $("#rule_template_framework").val() ) {
                                case "0":                   
                                case "D-storeWideSale":     
                                case "D-simpleDiscount":    setCumulativeSwitchesNo();     break;
                                default:                    setCumulativeSwitchesYes();    break;                              
                              };
                              */
                              setCumulativeSwitchesYes();
                              
                            };                              
                            function setCumulativeSwitchesYes() {
                              //only do this if NOT 1st time and 1st time data present
                              if ($("#upperSelectsHaveDataFirstTime").val() == 0 ) {
                                $("#cumulativeRulePricing").val('yes');
                                $("#cumulativeCouponPricing").val('yes');
                                $("#cumulativeSalePricing").val('addToSalePrice');  //v1.0.3
                                $("#ruleApplicationPriority_num").val('10');
                              }

                              $('#cumulativeRulePricingYes').attr('disabled', false);
                              $('#cumulativeCouponPricingYes').attr('disabled', false);
                              $('#cumulativeSalePricingAddTo').attr('disabled', false);
                              $('#cumulativeSalePricingReplace').attr('disabled', false);
                              cumulativeRulePricing_chg();
                            };                                                     
                                                        
            // MASTER TEMPLATE choice routine END
            
           
            //Prompt messages for required fields        
                      	// input on focus  FUNCTION - REMOVE msg so they can type
                    		//v1.1.0.8 update list in jquery stmt
                        jQuery("#discount_product_full_msg[type=text], #discount_product_short_msg[type=text], #only_for_this_coupon_name[type=text], #only_for_this_coupon_name[type=text]").focus(function() {   //v1.1.7.1a 
                    			
                     //     var default_value = this.value;			
                    //			if(this.value === default_value) {
                    //				this.value = '';
                    //			}
                           
                          var id = jQuery(this).attr('id'); 
                          if (id == 'discount_product_full_msg') {
                    				if (this.value === $("#fullMsg").val()) {
                              this.value = '';
                            }
                    			}
                          if (id == 'discount_product_short_msg') {
                    				if (this.value === $("#shortMsg").val()) {
                              this.value = '';
                            }
                    			} 
                          //v1.1.0.8
                          if (id == 'only_for_this_coupon_name') {
                    				if (this.value === $("#couponMsg").val()) {
                              this.value = '';
                            }
                    			} 

                    			//jQuery(this).removeClass('blur');
                    			//return css to normal!!
                          jQuery(this).css("color","#000").css("font-style","normal");
                    		});
                       
                        
                        //FUNCTION - put msg back if nothing is there!!!
                    		jQuery("#discount_product_full_msg[type=text], #discount_product_short_msg[type=text], #only_for_this_coupon_name[type=text]").blur(function() {   //v1.1.7.1a                 				
                          var id = jQuery(this).attr('id');
                    			if (id == 'discount_product_full_msg') {
                    				var default_value = $("#fullMsg").val();
                    			} 
                          if (id == 'discount_product_short_msg') {
                    				var default_value = $("#shortMsg").val();
                    			}
                          //v1.1.0.8 
                          if (id == 'only_for_this_coupon_name') {
                    				var default_value = $("#couponMsg").val();
                    			} 
                                                   
                    			if(this.value === '') {
                    				this.value = default_value;
                    			//return css to light grey!!
                          jQuery(this).css("color","rgb(152, 152, 152)").css("font-style","italic");
                    			}                    			
                    		});          
             
            //****************************
            // LINE CONTROLS   Begin
            //****************************                                                                
                            //Buy Pool Amount Condition Type                            
                            $('#buy_amt_type_0').change(function(){
                                buy_amt_line_remainder_chg();                                                                                   
                            });                                     
                             function buy_amt_line_remainder_chg() {     
                              switch( $("#buy_amt_type_0").val() ) {                                
                                case "none":         $(".buy_amt_line_remainder").hide("slow");                                                   
                                case "one":          $(".buy_amt_line_remainder").hide("slow");  
                                                     break;
                                case "quantity":     $(".buy_amt_line_remainder").show("slow");
                                                     $(".buy_amt_count_literal_0").hide("slow"); 
                                                     $("#buy_amt_count_literal_quantity_0").show("slow");   
                                                     break;
                                case "currency":     $(".buy_amt_line_remainder").show("slow");
                                                     $(".buy_amt_count_literal_0").hide("slow"); 
                                                     $("#buy_amt_count_literal_currency_0").show("slow");   
                                                     break;
                                case "nthQuantity":  $(".buy_amt_line_remainder").show("slow");
                                                     $(".buy_amt_count_literal_0").hide("slow"); 
                                                     $("#buy_amt_count_literal_nthQuantity_0").show("slow");   
                                                     break;                                                               
                              };
                            }; 

                            //Buy Pool Amount Mod Type
                            $('#buy_amt_mod_0').change(function(){
                                buy_amt_mod_count_area_chg();                                                   
                            });                                     
                             function buy_amt_mod_count_area_chg() {     
                              switch( $("#buy_amt_mod_0").val() ) {                                
                                case "none":         $("#buy_amt_mod_count_area_0").hide("slow");  break;                                                               
                                case "minCurrency":  $("#buy_amt_mod_count_area_0").show("slow");  break;
                                case "maxCurrency":  $("#buy_amt_mod_count_area_0").show("slow");  break;                                                         
                              };
                            };
                            
                             //Buy Repeat Condition Type
                            $('#buy_repeat_condition_0').change(function(){
                                buy_repeat_count_area_chg();                                                   
                            });                                     
                             function buy_repeat_count_area_chg() {  
                              switch( $("#buy_repeat_condition_0").val() ) {                                
                                case "none":       $("#buy_repeat_count_area_0").hide("slow");  break;                                                               
                                case "unlimited":  $("#buy_repeat_count_area_0").hide("slow");  break;
                                case "count":      $("#buy_repeat_count_area_0").show("slow");  break;                                                         
                              };
                            };
                            
                            //Action Pool Amount Condition Type
                            $('#action_amt_type_0').change(function(){
                                action_amt_line_remainder_chg();                                                                                   
                            });                                     
                             function action_amt_line_remainder_chg() { 
                              switch( $("#action_amt_type_0").val() ) {                                
                                case "none":         $(".action_amt_line_remainder").hide("slow");  
                                                      action_amt_subBoxes_test();  break;
                                case "zero":         $(".action_amt_line_remainder").hide("slow");  
                                                     action_amt_subBoxes_test();  break;                                                               
                                case "one":          $(".action_amt_line_remainder").hide("slow");  
                                                     action_amt_subBoxes_test();  break;
                                case "quantity":     $(".action_amt_line_remainder").show("slow");
                                                     $(".action_amt_count_literal_0").hide("slow"); 
                                                     $("#action_amt_count_literal_quantity_0").show("slow");   
                                                     action_amt_subBoxes_test();  break;
                                case "currency":     $(".action_amt_line_remainder").show("slow");
                                                     $(".action_amt_count_literal_0").hide("slow"); 
                                                     $("#action_amt_count_literal_currency_0").show("slow");   
                                                     action_amt_subBoxes_test();  break;
                                case "nthQuantity":  $(".action_amt_line_remainder").show("slow");
                                                     $(".action_amt_count_literal_0").hide("slow"); 
                                                     $("#action_amt_count_literal_nthQuantity_0").show("slow");   
                                                     action_amt_subBoxes_test();  break;                               
                              };
                              //v1.1.8.1 begin fix
                              if ( $("#action_amt_type_0").val() == 'one') {
                                $("#action_amt_box_appliesto_span_0").hide();
                                $(".action_amt_mod_box_class_0").hide();                               
                              } else {
                                $("#action_amt_box_appliesto_span_0").show("slow");
                              }
                              if ( $("#popChoiceOut").val() != "sameAsInPop") {
                                $("#action_amt_mod_box_0").show("slow");
                              } else {
                                $("#action_amt_mod_box_0").hide();
                              }
                              //v1.1.8.1 end                              
                            };
                             function action_amt_subBoxes_test() {     
                              switch( $("#action_amt_type_0").val() ) {                                
                                case "none":         $('.action_amt_mod').attr("disabled", "disabled");
                                                     $('.action_repeat_condition').attr("disabled", "disabled");                                                               
                                default:             $('.action_amt_mod').removeAttr('disabled');
                                                     $('.action_repeat_condition').removeAttr('disabled')                                                              
                              };
                            };
                                                        
                            //Action Pool Amount Mod Type
                            $('#action_amt_mod_0').change(function(){
                                action_amt_mod_count_area_chg();                                                   
                            });                                     
                             function action_amt_mod_count_area_chg() {     
                              switch( $("#action_amt_mod_0").val() ) {                                
                                case "none":         $("#action_amt_mod_count_area_0").hide("slow");  break;                                                               
                                case "minCurrency":  $("#action_amt_mod_count_area_0").show("slow");  break;
                                case "maxCurrency":  $("#action_amt_mod_count_area_0").show("slow");  break;                                                         
                              };
                            };
                            
                             //Action Repeat Condition Type
                            $('#action_repeat_condition_0').change(function(){
                                action_repeat_count_area_chg();                                                   
                            });                                     
                             function action_repeat_count_area_chg() {     
                              switch( $("#action_repeat_condition_0").val() ) {                                
                                case "none":       $("#action_repeat_count_area_0").hide("slow");  break;                                                               
                                case "unlimited":  $("#action_repeat_count_area_0").hide("slow");  break;
                                case "count":      $("#action_repeat_count_area_0").show("slow");  break;                                                         
                              };
                            };
                                                        
                            //Discount Amount Condition Type
                            $('#discount_amt_type_0').change(function(){
                                $("#chg_detected_sw").val('yes');  //v1.0.7.6
                                discount_amt_line_remainder_chg();                                                                                                                                                   
                            });                                     
                             
                             function discount_amt_line_remainder_chg() {     
                                                          
                              switch( $("#discount_amt_type_0").val() ) {                                
                                case "0":            $("#discount_amt_count_area_0").hide("slow"); 
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");                               
                                                      //catch the two 'cheapest' and process differently...
                                                      switch( $("#rule_template_framework").val() ) {
                                                        case "C-cheapest-inCart":       
                                                        case "C-cheapest-Next":  
                                                            discount_appliesTo_protect3();
                                                          break;
                                                        default: 
                                                            discount_appliesTo_protect1();
                                                          break;                            
                                                      };
                                                     $('.discount_auto_add_free_product').removeAttr('checked');                                                                                                      
                                                     break; 
                                                                                                                   
                                case "percent":      $("#discount_amt_count_area_0").show("slow"); 
                                                     $(".discount_amt_count_literal_0").hide("slow"); 
                                                     $("#discount_amt_count_literal_percent_0").show("slow");                                  
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //turn back on in case previous selection was fixedPrice 
                                                     
                                                     //v1.0.7.7  begin
                                                     //$('.discount_applies_to_all').attr('disabled', false);
                                                     if ($("#chg_detected_sw").val() == 'yes') {
                                                        if($('#cart-or-catalog-Cart').prop('checked')) { //v2.0.0
                                                          discount_appliesTo_protect5(); //  set 'all' as default
                                                        } else {   //catalog requires each!!
                                                          discount_appliesTo_protect1(); //  set 'each' as default
                                                        }
                                                        
                                                        $("#chg_detected_sw").val('no');
                                                     }
                                                     //v1.0.7.7  end
                                                      
                                                     break;
                                                                                     
                                case "currency":     $("#discount_amt_count_area_0").show("slow"); 
                                                     $(".discount_amt_count_literal_0").hide("slow"); 
                                                     $("#discount_amt_count_literal_currency_0").show("slow");                                  
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //turn back on in case previous selection was fixedPrice 
                                                     $('.discount_applies_to_all').attr('disabled', false);
                                                     break;
                                                      
                                case "fixedPrice":   $("#discount_amt_count_area_0").show("slow");   
                                                     $(".discount_amt_count_literal_0").hide("slow"); 
                                                     $("#discount_amt_count_literal_currency_0").show("slow");                                  
                                                     $("#discount_for_the_price_of_area_0").hide("slow"); 
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //disallow here!!  only allow 'applies to each' 
                                                     $('.discount_applies_to_all').attr('disabled', true);
                                                     break;
                                                     
                                case "free":         $("#discount_amt_count_area_0").hide("slow");   
                                                     $("#discount_for_the_price_of_area_0").hide("slow");
                                                     switch( $("#rule_template_framework").val() ) {
                                                        case "D-storeWideSale":
                                                        case "C-storeWideSale":                    
                                                        case "D-simpleDiscount":
                                                        case "C-storeWideSale":
                                                            $("#discount_auto_add_free_product_label_0").hide("slow");
                                                          break;               
                                                        default:  //rest of the templates...;
                                                            $("#discount_auto_add_free_product_label_0").show("slow");                                                              
                                                          break;                               
                                                     };
                                                     //disallow here!!  only allow 'applies to each' 
                                                     $('.discount_applies_to_all').attr('disabled', true); 
                                                                                                          
                                                     //v1.0.8.9 begin
                                                     // for cheapest, do NOT force select applies_to_each !!!!!!!!!!!!!!
                                                      switch( $("#rule_template_framework").val() ) {
                                                        case "C-cheapest-inCart":       break;
                                                        case "C-cheapest-Next":         break;
                                                        default:              
                                                           $('.discount_applies_to_each').attr('disabled', false);
                                                           $('#discount_applies_to_each_0').attr('selected', true);                                                        
                                                         break;                              
                                                      };                                                     
                                                     //v1.0.8.9 end
                                                     
                                                     break;
                                                                                                          
                                case "forThePriceOf_Units":   
                                                     $("#discount_amt_count_area_0").show("slow");  
                                                     $(".discount_amt_count_literal_0").hide("slow");    
                                                     $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow");
                                                     $("#discount_amt_count_literal_forThePriceOf_0").show("slow");   
                                                     $("#discount_amt_count_literal_units_area_0").show("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //allready removes discount_applies_to_all 
                                                     break;
                                                      
                                case "forThePriceOf_Currency":   
                                                     $("#discount_amt_count_area_0").show("slow");  
                                                     $(".discount_amt_count_literal_0").hide("slow");    
                                                     $("#discount_amt_count_literal_forThePriceOf_buyAmt_0").show("slow");
                                                     $("#discount_amt_count_literal_forThePriceOf_0").show("slow");
                                                     $("#discount_amt_count_literal_forThePriceOf_Currency_0").show("slow");   
                                                     $("#discount_amt_count_literal_units_area_0").hide("slow");
                                                     $("#discount_auto_add_free_product_label_0").hide("slow");
                                                     $('.discount_auto_add_free_product').removeAttr('checked');
                                                     //allready removes discount_applies_to_all
                                                     break;                                                                                   
                              };
                            }; 

                            //Discount Maximum for Rule across the Cart Type
                            $('#discount_rule_max_amt_type_0').change(function(){
                                discount_rule_max_amt_count_area_chg();
                            });                                     
                             function discount_rule_max_amt_count_area_chg() {                                  
                              switch( $("#discount_rule_max_amt_type_0").val() ) {                                
                                case "none":      $("#discount_rule_max_amt_count_area").hide("slow");  
                                                  //ruleMaxMsgTest();  
                                                  break;                                                               
                                case "quantity":  $("#discount_rule_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_max_amt_count_literal_quantity").show("slow"); 
                                                  //ruleMaxMsgTest();  
                                                  break;
                                case "currency":  $("#discount_rule_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_max_amt_count_literal_currency").show("slow"); 
                                                  //ruleMaxMsgTest();  
                                                  break; 
                                case "percent":   $("#discount_rule_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_max_amt_count_literal_percent").show("slow"); 
                                                  //ruleMaxMsgTest();  
                                                  break;                                                                                                                                            
                              };
                            };
                            function ruleMaxMsgTest () {     
                              /*switch( $("#discount_rule_max_amt_type_0").val() ) {
                                case "none":    $("#discount_rule_max_amt_msg").hide("slow");    break;
                                default:        $("#discount_rule_max_amt_msg").delay(1500).show("slow");    break;                             
                              };
                              */
                            };
                                                          
                            //Customer Discount Maximum Lifetime Limit for Rule Type
                            $('#discount_lifetime_max_amt_type_0').change(function(){
                                discount_lifetime_max_amt_count_area_chg();                                                      
                            });                                     
                             function discount_lifetime_max_amt_count_area_chg() {     
                              switch( $("#discount_lifetime_max_amt_type_0").val() ) {                                
                                case "none":      $("#discount_lifetime_max_amt_count_area").hide("slow");
                                                  $(".custLimit_addl_info").hide("slow");  /*v2.0.2.0*/
                                              //    lifetimeMaxMsgTest();  
                                                  break;                                                               
                                case "quantity":  $("#discount_lifetime_max_amt_count_area").show("slow");                   
                                                  $(".discount_lifetime_max_amt_count_literal").hide("slow");  
                                                  $("#discount_lifetime_max_amt_count_literal_quantity").show("slow"); 
                                                  $(".custLimit_addl_info").show("slow");  /*v2.0.2.0*/
                                               //   lifetimeMaxMsgTest();  
                                                  break;
                                case "currency":  $("#discount_lifetime_max_amt_count_area").show("slow");                   
                                                  $(".discount_lifetime_max_amt_count_literal").hide("slow");  
                                                  $("#discount_lifetime_max_amt_count_literal_currency").show("slow");
                                                  $(".custLimit_addl_info").show("slow");  /*v2.0.2.0*/ 
                                               //   lifetimeMaxMsgTest();  
                                                  break;                                                                                                                                      
                              };
                            };
                            function lifetimeMaxMsgTest () {     
                              switch( $("#discount_lifetime_max_amt_type_0").val() ) {
                                case "none":    $("#discount_lifetime_max_amt_msg").hide("slow");    break;
                                default:        $("#discount_lifetime_max_amt_msg").delay(1500).show("slow");     break;                             
                              };
                            };
                                                          
                             // Cumulative Pricing Settings:  Apply this Rule Discount in Addition to Other Rule Discounts
                            $('#cumulativeRulePricing').change(function(){
                                cumulativeRulePricing_chg();                                                   
                            });                                     
                             function cumulativeRulePricing_chg() {                                   
                              switch( $("#cumulativeRulePricing").val() ) {                                
                                /*  v1.0.7.4  allow priority to show at all times
                                case "no":   $("#priority_num").hide("slow");  
                                             $("#ruleApplicationPriority_num").val('10');  //clear out the priority numbreak;                                                               
                                   break; 
                                */
                                case "yes":  $("#priority_num").show("slow");
                                /*
                                small bug - if the delete or back key was used by the user to clear out the priority num field,
                                            that key value sticks around and overrides the display of the '10' value in FF
                                            however the value is still there, and will be processed correctly... 
                                */
                                             if ( $("#ruleApplicationPriority_num").val() <= 0 ) {                          
                                                $("#ruleApplicationPriority_num").val('10');  //init the priority num to '10'                      
                                             }; 
                                  break;                                                                                                                                 
                              };
                            };
                                                        
                            //Discount Cumulative Product Maximum for all Discounts
                            $('#discount_rule_cum_max_amt_type_0').change(function(){
                                discount_rule_cum_max_amt_count_area_chg();
                            });                                     
                             function discount_rule_cum_max_amt_count_area_chg() {     
                              switch( $("#discount_rule_cum_max_amt_type_0").val() ) {                                
                                case "none":      $("#discount_rule_cum_max_amt_count_area").hide("slow");  
                                                  cumMaxMsgTest();  break;                                                               
                                case "quantity":  $("#discount_rule_cum_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_cum_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_cum_max_amt_count_literal_quantity").show("slow"); 
                                                  cumMaxMsgTest();  break;
                                case "currency":  $("#discount_rule_cum_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_cum_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_cum_max_amt_count_literal_currency").show("slow"); 
                                                  cumMaxMsgTest();  break; 
                                case "percent":   $("#discount_rule_cum_max_amt_count_area").show("slow");                   
                                                  $(".discount_rule_cum_max_amt_count_literal").hide("slow");  
                                                  $("#discount_rule_cum_max_amt_count_literal_percent").show("slow"); 
                                                  cumMaxMsgTest();  break;                                                                                                                                            
                              };
                            };
                            function cumMaxMsgTest() {     
                             /* switch( $("#discount_rule_cum_max_amt_type_0").val() ) {
                                case "none":    $("#discount_rule_cum_max_amt_msg").hide("slow");    break;
                                default:        $("#discount_rule_cum_max_amt_msg").delay(1500).show("slow");     break;                             
                              };*/
                            };                                                                             
                            
                                                                                  
                            $('#buy_amt_count_0').change(function(){
                                frameWork = $("#rule_template_framework").val();
                                if (frameWork == "C-forThePriceOf-inCart") {    //forThePriceOf-same-group-discount
                                  insertBuyAmt(); 
                                };
                            });
                            function insertBuyAmt() { 
                              $('.forThePriceOf-amt-literal-inserted').remove();
                              insertVal  = '<span class="forThePriceOf-amt-literal-inserted discount_amt_count_literal  discount_amt_count_literal_0 " id="discount_amt_count_literal_forThePriceOf_buyAmt_0">';                              
                              insertVal += ' Buy ';
                              insertVal += $('#buy_amt_count_0').val();
                              insertVal += ' </span>';
                              $(insertVal).insertBefore('#discount_amt_count_literal_forThePriceOf_0');
                            };
                            
                            $('#action_amt_count_0').change(function(){
                                frameWork = $("#rule_template_framework").val();
                                if (frameWork == "C-forThePriceOf-Next") {    //forThePriceOf-other-group-discount
                                  insertGetAmt(); 
                                };
                            });
                            function insertGetAmt() { 
                              $('.forThePriceOf-amt-literal-inserted').remove();
                              insertVal  = '<span class="forThePriceOf-amt-literal-inserted discount_amt_count_literal  discount_amt_count_literal_0 " id="discount_amt_count_literal_forThePriceOf_buyAmt_0">';
                              insertVal += ' Get ';
                              insertVal += $('#action_amt_count_0').val();
                              insertVal += ' </span>';
                              $(insertVal).insertBefore('#discount_amt_count_literal_forThePriceOf_0');
                            };

            
            // LINE CONTROLS   eND
             
 

                           //OLD STUFF, NEEDS TO BE REPLACED...
                            //Population Handling Specifics
                            $("#allChoiceIn").click(function(){
                                $("#allChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-span").hide("slow"); 
                                $("#eachChoiceIn-chosen").hide("slow");                                         
                            });
                            if($('#allChoiceIn').prop('checked')) {
                                $("#allChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-chosen").hide();
                                $("#anyChoiceIn-span").hide(); 
                                $("#eachChoiceIn-chosen").hide();                              
                                };
                                
                            $("#anyChoiceIn").click(function(){
                                $("#allChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-span").show("slow"); 
                                $("#eachChoiceIn-chosen").hide("slow");
                            });
                            if($('#anyChoiceIn').prop('checked')) {
                                $("#allChoiceIn-chosen").hide();
                                $("#anyChoiceIn-chosen").show("slow");
                                $("#anyChoiceIn-span").show("slow"); 
                                $("#eachChoiceIn-chosen").hide();                                
                                };
                                 
                            $("#eachChoiceIn").click(function(){
                                $("#allChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-chosen").hide("slow");
                                $("#anyChoiceIn-span").hide("slow"); 
                                $("#eachChoiceIn-chosen").show("slow");
                            });
                            if($('#eachChoiceIn').prop('checked')) {
                                $("#allChoiceIn-chosen").hide();
                                $("#anyChoiceIn-chosen").hide();
                                $("#anyChoiceIn-span").hide(); 
                                $("#eachChoiceIn-chosen").show("slow");                                
                                };
                                
                            $("#qtySelectedIn").click(function(){
                                $("#qtyChoice-chosen").show("slow");
                                $("#amtChoice-chosen").hide("slow");                              
                            });
                            if($('#qtySelectedIn').prop('checked')) {
                                $("#qtyChoice-chosen").show("slow");
                                $("#amtChoice-chosen").hide();                             
                                };
                               
                            $("#amtSelectedIn").click(function(){
                                $("#amtChoice-chosen").show("slow");
                                $("#qtyChoice-chosen").hide("slow");                           
                            });
                            if($('#amtSelectedIn').prop('checked')) {
                                $("#amtChoice-chosen").show("slow");
                                $("#qtyChoice-chosen").hide();
                                };
     //end old stuff


                                                        
                            //toggle "more info" areas
                            $("#pop-in-more").click(function(){
                                $("#pop-in-descrip").toggle("slow");                           
                            });
                            $("#inPopDescrip-more").click(function(){
                                $("#inPopDescrip-descrip").toggle("slow");                           
                            });
  
           
            //********************************************
            // POP Controls - Buy/Action Population handling   Begin
            //********************************************                                                                  
                        // popChoiceIn
                             
                        $('#popChoiceIn').change(function(){
                            popChoiceInTest();
                           //  mirrorPopChoiceInChange();       //v1.0.8.4
                        });

                        function popChoiceInTest() {
                           switch( $("#rule_template_framework").val() ) {
                            //Don't show PopChoice for the WholeStore versions
                            case "D-storeWideSale": 
                            case "C-storeWideSale": 
                                hideChoiceIn();                                 
                              break; 
                            default:             
                                popChoiceInProcess();
                              break;                               
                           };                             

                        }; 
                        function popChoiceInProcess() {
                          switch( $("#popChoiceIn").val() ) {
                            case "wholeStore": hideChoiceIn();    break;
                            case "groups":     groupChoiceIn();   break;
                          };
                        };                       
                        function cartChoiceIn() {
                          //$("#vtprd-pop-in-cntl").hide("slow");          
                          //$("#vtprd-pop-in-groups-cntl").hide();  
                          $("#buy_group_line_remainder").hide();
                          $("#buy-show-and-or-switches").hide(); //v2.0.0
                          $(".buy-and-or-selector").hide(); //v2.0.0				  
                        };						
                         //v2.0.0 begin
                        function groupChoiceIn() {
                          $("#buy_group_line_remainder").show("slow");
                          if($('#advancedSelected').prop('checked')) { 
                            if($('#cart-or-catalog-Cart').prop('checked')) {
                              $("#buy-show-and-or-switches").show("slow"); 
                              buy_group_show_and_or_switches_test();  
                            }                         
                          }
                        };
                        //v2.0.0 end
                        function hideChoiceIn() {
                          //$("#vtprd-pop-in-cntl").hide("slow");
                          //$("#vtprd-pop-in-groups-cntl").hide("slow"); //mwntest
                          $("#buy_group_line_remainder").hide("slow"); //mwntest 
                          $("#buy-show-and-or-switches").hide("slow"); //v2.0.0	
                          $(".buy-and-or-selector").hide("slow"); //v2.0.0					  
                        };

                        //v2.0.0 begin
                        $(".buy-group-show-and-or-switches").click(function(){
                             buy_group_show_and_or_switches_test();                            
                         });                          
                        function buy_group_show_and_or_switches_test() {     
                           if($('#buy-group-show-and-or-switches-YesSelect').prop('checked')) { 
                              $(".buy-and-or-selector").show("slow"); //v2.0.0                           
                           } else {
                              $(".buy-and-or-selector").hide("slow"); //v2.0.0
                           }
                        };                          
                        //v2.0.0 end 
                                                                               
                        // popChoiceOut
                           
                        $('#popChoiceOut').change(function(){
                            popChoiceOutTest();
                           //  mirrorPopChoiceOutChange();   //v1.0.8.4
                        });                                

                        function popChoiceOutTest() {     
                           switch( $("#rule_template_framework").val() ) {
                            //Don't show PopChoice for the WholeStore versions
                            case "D-storeWideSale": 
                            case "C-storeWideSale": 
                                hideChoiceOut();                                
                              break; 
                            default:             
                                popChoiceOutProcess();
                              break;                               
                           }; 
                        }; 
                        function popChoiceOutProcess() {                        
                          switch( $("#popChoiceOut").val() ) {
                            case "appliesToBuy": hideChoiceOut();    break;
                            case "sameAsInPop":  hideChoiceOut();    break;
                            case "wholeStore":   hideChoiceOut();    break;
                            case "cart":         cartChoiceOut();    break;
                            case "groups":       groupChoiceOut();   break;
                          }; 
                          //v1.1.8.1 begin
                          if ( $("#popChoiceOut").val() != "sameAsInPop"){
                            $("#action_amt_mod_box_0").show("slow");
                          } else {
                            $("#action_amt_mod_box_0").hide();
                          }
                          //v1.1.8.1 end                                                 
                        }                       
                        function cartChoiceOut() {                            
                          //$("#vtprd-pop-out-cntl").hide("slow"); 
                          $("#action_group_line_remainder").hide("slow");
                          $("#action-show-and-or-switches").hide(); //v2.0.0
                          $(".action-and-or-selector").hide(); //v2.0.0	                          						  
                        };						
                        function groupChoiceOut() {
                          $("#action_group_line_remainder").show("slow");
                          if($('#advancedSelected').prop('checked')) { //v2.0.0 
                            $("#action-show-and-or-switches").show("slow"); //v2.0.0
                            action_group_show_and_or_switches_test();   //v2.0.0                          
                          }                                                      
                          //$("#vtprd-pop-out-cntl").show("slow");		  
                        };                       
                        function hideChoiceOut() {
                         // $("#vtprd-pop-out-cntl").hide("slow");
                          $("#action_group_line_remainder").hide("slow");	
                          $("#action-show-and-or-switches").hide("slow"); //v2.0.0	
                          $(".action-and-or-selector").hide("slow"); //v2.0.0                          					  
                        };

                        //v2.0.0 begin
                        $(".action-group-show-and-or-switches").click(function(){
                             action_group_show_and_or_switches_test();                            
                         });                          
                        function action_group_show_and_or_switches_test() {     
                           if($('#action-group-show-and-or-switches-YesSelect').prop('checked')) { 
                              $(".action-and-or-selector").show("slow"); //v2.0.0                           
                           } else {
                              $(".action-and-or-selector").hide("slow"); //v2.0.0
                           }
                        };                          
                        //v2.0.0 end 
                                             
            // POP CONTROLS   eND
             
             
            //****************************
            // SCROLL UP
            //****************************  
                                         
                       $(window).scroll(function() {
                          if ($(this).scrollTop()) {
                              $('#back-to-top-tab').fadeIn();
                          } else {
                              $('#back-to-top-tab').fadeOut();
                          };
                      });
                      
                      $("#back-to-top-tab").click(function() {
                          $("html, body").animate({scrollTop: 0}, 1000);
                       });
            
            // SCROLL UP End

            
            // Buy/Action Population handling END


                        //v1.1.8.1 begin
                        $("#ajaxCloneRule").click(function(){
                            //turn on loader animation
                            jQuery('div.ajaxCloneRule-loading-animation').css('visibility', 'visible');

                            $('div#vtprd-clone-msg').hide("slow");
                            
                            var VarAjaxRuleID = $('#ajaxRuleID').val();  //parent product ID from screen
                                                                                     
                            jQuery.ajax({
                               type : "post",
                               dataType : "html",
                               url : cloneRuleAjax.ajaxurl,  
                               data :  {action: "vtprd_ajax_clone_rule", ajaxRuleID: VarAjaxRuleID } ,
                               //                                             ajaxRuleID = name referenced in PHP => refers to this variable declaration, not the original html element.
                               success: function(response) {                                        
                                    //load the html output into #variations and show slowly
                                    $('div#vtprd-clone-msg').html(response).show("slow");
                                    //turn off loader animation
                                    jQuery('div.ajaxCloneRule-loading-animation').css('visibility', 'hidden');
                                }                              
                            }) ;  
    
                         });
                        //v1.1.8.1 end                                                   
                        //**************     
                        //  end Ajax
                        //**************                      

           
                //********************************************
                // ToolTip  QTip2   BEGIN  +  Wizard sw
                //********************************************                              

                     
                                              
                        $('.hasHoverHelp').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content

                                }
                                ,position: {
                                    my: 'left center',  //location of pointer on tooltip
                                    at: 'right center' //points to where on the object
                                    
                                }
                                ,show: 'click'
                                ,hide: {
                                      fixed: true,
                                      delay: 300
                                  }

                            });              
                        });                          
                        
                      
                                              
                        $('.hasHoverHelp2').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content

                                }
                                ,position: {
                                    my: 'top right',  //location of pointer on tooltip
                                    at: 'bottom center' //points to where on the object
                                    
                                }
                              //  ,show: 'click'
                                ,hide: {
                                      fixed: true,
                                      delay: 300
                                  }

                            });              
                        });                          
                        

                        $('.hasWizardHelpBelow').each(function() { // Notice the .each() loop, discussed below                         

                            $(this).qtip({
                                                        
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content
                                   
                                    ,title: {
                              					button: true    //close button on box
                              				}
                                }
                          			/*
                                hide: { 
                          				event: 'click'   //only hides when close button is clicked!!
                          			}
                                */
  
                                //,hide: 'unfocus'   //keep displaying until next click elsewhere
                                ,hide: {
                                      fixed: true,
                                      delay: 300
                                  }
                                ,position: {
                                    //where the arrow pointing to the clicked-on object is located
                                    /*
                                    corner: {
                                        target: 'topLeft',
                                        tooltip: 'bottomLeft'
                                        },
                                        */
                                    //adjusts the location of the whole tooltip relative to the clicked-on object
                                   /* adjust: {
                                        x: 10,
                                        y: 150
                                        }
                                    ,  */
                                    my: 'top center',  //location of pointer on tooltip
                                    at: 'bottom center' //points to where on the object
                                    
                                }
                                    
                                
                                ,style: {
                              			classes: 'wideWizard' //activates this class for the tooltip, which changes the max-width and max-height, etc....
                              	}  
                                                                    
                            });  
                                                                                            
                        }); 
                        
 
                      
                        $('.hasWizardHelpRight').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content
                                    ,title: {
                              					button: true    //close button on box
                                        }                                   
                                }
                                
                                ,hide: {       //brief time delay, then fade
                                      fixed: true,
                                      delay: 300
                                  }
                                  
                                //,hide: 'unfocus'   //keep displaying until next click elsewhere
                                      
                                ,position: {
                                    //where the arrow pointing to the clicked-on object is located
                                    //adjusts the location of the whole tooltip relative to the clicked-on object

                                //    my: 'left center',  //location of pointer on tooltip
                                //    at: 'bottom right' //points to where on the object
                                    
 
                                    my: 'top left',  //location of pointer on tooltip
                                    at: 'bottom right' //points to where on the object
                                                                        
                                }                                    
                                
                                ,style: {
                              			classes: 'wideWizard' //activates this class for the tooltip, which changes the max-width and max-height, etc....
                              	}
                                                                    
                            });              
                        }); 
                       
                        // Grab all elements with the class "hasTooltip"
                        $('.hasTooltip').each(function() { // Notice the .each() loop, discussed below
                            $(this).qtip({
                                content: {
                                    text: $(this).next('div') // Use the "div" element next to this for the content
                                    ,title: {
                              					button: true    //close button on box
                              				}
                                }
                                
                                ,show: {
                                    modal: {
                                        on: true
                                    }
                                }                                
                                
                            });              
                        });     
                        

                
                        //****************************************************************
                        //Pick up the WIZARD switch state 1st time, AFTER qtip has run!
                        //****************************************************************
                        
                        wizard_on_off_sw_select_test();
                        
                        //****************************************************************
                        
                        $('#wizard-on-off-sw-select').change(function(){
                            wizard_on_off_sw_select_test();
                        });

                        function wizard_on_off_sw_select_test() {
                           switch( $("#wizard-on-off-sw-select").val() ) {
                            case "on":                                 
                                  $('.hasWizardHelpBelow').qtip('enable');
                                  $('.hasWizardHelpRight').qtip('enable'); 
                              break;                              
                            case "off":                                                           
                                  $('.hasWizardHelpBelow').qtip('hide').qtip('disable');
                                  $('.hasWizardHelpRight').qtip('hide').qtip('disable');                                 
                              break;                               
                           };                             

                        }; 
                        
                        //switch on the tooltips themselves...
                        $('.wizard-turn-hover-help-off').click(function(){
                            //$("#wizard-on-off-sw-select").val('off');
                            $('#wizard-on-off-sw-on').attr('selected', false);
                            $('#wizard-on-off-sw-off').attr('selected', true); 
                            wizard_on_off_sw_select_test();
                        });               
                
                //********************************************
                // ToolTip  QTip2   END
                //********************************************  

                    }); //end ready function 
                   
