<?php

/**
 *   based on code from the following:  (example is a tabbed settings page)
 *  http://wp.tutsplus.com/series/the-complete-guide-to-the-wordpress-settings-api/   
 *    (code at    https://github.com/tommcfarlin/WordPress-Settings-Sandbox) 
 *  http://www.chipbennett.net/2011/02/17/incorporating-the-settings-api-in-wordpress-themes/?all=1 
 *  http://www.presscoders.com/2010/05/wordpress-settings-api-explained/  
 *  
 * v1.1.5 ADDED special button  
 */
class VTPRD_Setup_Plugin_Options { 
	
	public function __construct(){ 
  
    add_action( 'admin_init',            array(&$this, 'vtprd_initialize_options' ) );
    
    add_action( 'admin_menu',            array(&$this, 'vtprd_add_admin_menu_setup_items' ) );
    add_action( "admin_enqueue_scripts", array(&$this, 'vtprd_enqueue_setup_scripts') );
    
    //this picks up any taxation changes which will radically alter the saved data...
    add_action( "woocommerce_settings_saved", array(&$this, 'vtprd_destroy_session') ); //v1.0.9.3    
  } 

function vtprd_add_admin_menu_setup_items() {
 // add items to the Pricing Deals custom post type menu structure
  global $vtprd_setup_options;
  
  //V1.0.9.0 ADD WRAPPING 'IF'
  if (!isset( $vtprd_setup_options['register_under_tools_menu'] ))  {
    $vtprd_setup_options = get_option( 'vtprd_setup_options' );
  }
  
  if ( (isset( $vtprd_setup_options['register_under_tools_menu'] ))  && 
       ($vtprd_setup_options['register_under_tools_menu'] == 'yes') ) {      
      $settingsLocation = 'options-general.php';
  } else {
      $settingsLocation = 'edit.php?post_type=vtprd-rule';
  } 
  
   
	add_submenu_page(
		$settingsLocation,	// The ID of the top-level menu page to which this submenu item belongs
		__( 'Pricing Deal Settings', 'vtprd' ), // The value used to populate the browser's title bar when the menu page is active                           
		__( 'Pricing Deal Settings', 'vtprd' ),					// The label of this submenu item displayed in the menu
		'administrator',					// What roles are able to access this submenu item
		'vtprd_setup_options_page',	// The slug used to represent this submenu item
		array( &$this, 'vtprd_setup_options_cntl' ) 				// The callback function used to render the options for this submenu item
	);
  
 if(!defined('VTPRD_PRO_DIRNAME')) {  //update to pro version...
   add_submenu_page(
		'edit.php?post_type=vtprd-rule',	// The ID of the top-level menu page to which this submenu item belongs
		__( 'Upgrade to Pricing Deals Pro', 'vtprd' ), // The value used to populate the browser's title bar when the menu page is active                           
		__( 'Upgrade to Pro', 'vtprd' ),					// The label of this submenu item displayed in the menu
		'administrator',					// What roles are able to access this submenu item
		'vtprd_pro_upgrade',	// The slug used to represent this submenu item
		array( &$this, 'vtprd_pro_upgrade_cntl' ) 				// The callback function used to render the options for this submenu item
	); 
 }
    add_submenu_page(
		'edit.php?post_type=vtprd-rule',	// The ID of the top-level menu page to which this submenu item belongs
		__( 'Pricing Deals Help', 'vtprd' ), // The value used to populate the browser's title bar when the menu page is active                           
		__( 'Pricing Deals Help', 'vtprd' ),					// The label of this submenu item displayed in the menu
		'administrator',					// What roles are able to access this submenu item
		'vtprd_show_help_page',	// The slug used to represent this submenu item
		array( &$this, 'vtprd_show_help_page_cntl' ) 				// The callback function used to render the options for this submenu item
	);  
/* 
    add_submenu_page(
		'edit.php?post_type=vtprd-rule',	// The ID of the top-level menu page to which this submenu item belongs
		__( 'Pricing Deals FAQ', 'vtprd' ), // The value used to populate the browser's title bar when the menu page is active                           
		__( 'Pricing Deals FAQ', 'vtprd' ),					// The label of this submenu item displayed in the menu
		'administrator',					// What roles are able to access this submenu item
		'vtprd_show_faq_page',	// The slug used to represent this submenu item
		array( &$this, 'vtprd_show_faq_page_cntl' ) 				// The callback function used to render the options for this submenu item
	);  
 */
  //Add a DUPLICATE custom tax URL to be in the main Pricing Deals menu as well as in the PRODUCT menu
  //post_type=product => PARENT plugin post_type
    add_submenu_page(
		'edit.php?post_type=vtprd-rule',	// The ID of the top-level menu page to which this submenu item belongs
		__( 'Pricing Deals Categories', 'vtprd' ), // The value used to populate the browser's title bar when the menu page is active                           
		__( 'Pricing Deals Categories', 'vtprd' ),					// The label of this submenu item displayed in the menu
		'administrator',					// What roles are able to access this submenu item
		'edit-tags.php?taxonomy=vtprd_rule_category&post_type=product',	// The slug used to represent this submenu item
    //                                          PARENT PLUGIN POST TYPE      
		''  				// NO CALLBACK FUNCTION REQUIRED
	);

  
} 

function vtprd_pro_upgrade_cntl() {

    //PRO UPGRADE PAGE
 ?>
  <style type="text/css">
      #upgrade-title-area {
          float:left;                                                                                                                                        a
          background-image:url("/wp-content/plugins/pricing-deals-for-wp-e-commerce/admin/images/upgrade-bkgrnd-banner.png");
          background-repeat: no-repeat;
          background-size:cover;
          width: 75%;
          padding: 10px 0 10px 20px;
          border-radius: 5px 5px 5px 5px;
      }
       #upgrade-title-area a {float:left;}
       #pricing-deals-img {
           float:left;
           padding-right: 10px;
       }
      .wrap h2, .subtitle {
          color:white;
          text-shadow:none;
      }

      #upgrade-div {
                clear:left;
                float: left;
               /* width: 2.5%;     */
                border: 1px solid #CCCCCC;
                border-radius: 5px 5px 5px 5px;
                padding: 0 15px 15px 0;
                font-size:18px;
                background: linear-gradient(to top, #ECECEC, #F9F9F9) repeat scroll 0 0 #F1F1F1;
                margin: 15px 0 0 7.5%;
                width: 68%;
                line-height: 25px;
            }
      #upgrade-div h3, #upgrade-div h4 {margin-left:20px;}
      #upgrade-div ul {list-style-type: none;margin-left:50px;}
      #upgrade-div ul ul {list-style-type: circle;font-size:16px !important;}
      /*#upgrade-div ul li {font-size:16px !important;}*/
      #upgrade-div a {font-size:16px; margin-left:23%;font-weight: bold;} 
      #upgrade-blurb {
        float:left;
        margin:15px 0 0 100px;
        font-weight:bold;
        color:blue;
      }
      #upgrade-div ul#vtprd-main-attributes ul {list-style-type: none;margin-left: 20px;}
      #upgrade-div ul#vtprd-main-attributes ul li {margin-left:15px;line-height:16px;color:blue;}
      #upgrade-blurb a, #upgrade-div a {color:blue;}
      #upgrade-blurb a:hover, #upgrade-div a:hover {color:#21759B;}
      .vtprd-highlight {color:blue;font-weight:bold;}
      
      .buy-button,
      .buy-button-area,
      .buy-button-area a,
      .buy-button-area a img,
      .buy-button-label {
        float:left;
      }
      .buy-button-area {
        margin-left:10px;
        margin-top: 3px;
      }
       .buy-button {
      	margin-top:20px;
        -moz-box-shadow:inset 0px 1px 0px 0px #caefab;
      	-webkit-box-shadow:inset 0px 1px 0px 0px #caefab;
      	box-shadow:inset 0px 1px 0px 0px #caefab;
      	background-color:#77d42a;
      	-moz-border-radius:6px;
      	-webkit-border-radius:6px;
      	border-radius:6px;
      	border:1px solid #268a16;
      	display:inline-block;
      	color:#FFF;
      	font-family:arial;
      	/*font-size:15px;*/
      	font-weight:bold;
      	padding:6px 15px; /*changed*/
      	text-decoration:none;
      	text-shadow:1px 1px 0px #aade7c;
      }
      .buy-button:hover {
      	background-color:#5a8939;
      }
      .buy-button:active {
      	position:relative;
      	top:1px;
      }  
      .buy-button:hover {
      	background-color:#5a8939;
        color:white;
      }    
    </style>
   
	<div class="wrap">
		<div id="icon-themes" class="icon32"></div>
    
		<div id="upgrade-title-area">
      <a  href=" <?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT ; ?> "  title="Purchase Pro">
      <img id="pricing-deals-img" alt="help" height="40px" width="40px" src="<?php echo VTPRD_URL;?>/admin/images/sale-circle.png" />
      </a>
      <h2><?php esc_attr_e('Upgrade to Pricing Deals Pro', 'vtprd'); ?></h2>
    </div>  
      <h2 id="upgrade-blurb" ><?php _e('Pricing Deals Pro', 'vtprd'); ?> </h2>
      <span class="buy-button-area">
        <a href="<?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT; ?>" class="buy-button">
            <span class="buy-button-label">Get Pricing Deals Pro</span>
        </a>
      </span>


    <div id="upgrade-div">       
      
      <ul id="vtprd-main-attributes">
        <li> <span class="vtprd-highlight"><?php _e('Group Power &nbsp;-&nbsp; Apply rules to <em>any group you can think of!</em>', 'vtprd') ?></span>
          <ul> <strong><em><?php _e('Filter By', 'vtprd') ?></em></strong>
            <li><?php _e(' -  Wholesale / Membership / Role (Logged-in Status)', 'vtprd') ?></li>
            <li><?php _e(' -  Product Category', 'vtprd') ?></li>
            <li><?php _e(' -  Pricing Deal Custom Category', 'vtprd') ?></li>
            <li><?php _e(' -  Product', 'vtprd') ?></li>
            <li><?php _e(' -  Variations', 'vtprd') ?></li>
          </ul>             
        </li>
        <li><span class="vtprd-highlight"><?php _e('Product-level Deal Exclusion', 'vtprd') ?></span></li>
        <li><span class="vtprd-highlight"><?php _e('Maximum Deal Limits, including "One Per Customer" limit', 'vtprd') ?></span></li>
      </ul>
      
      <ul>  
        <li><?php _e('<em>Deal Types Now have Tremendous Additional Power, with full filtering capability</em>, including:', 'vtprd') ?>
          <ul>
            <li><?php _e('BOGO (Buy One, Get One) [for All]', 'vtprd') ?></li>
            <li><?php _e('Sale Pricing [for All]', 'vtprd') ?></li>
            <li><?php _e('Group Pricing [for All]', 'vtprd') ?></li>
            <li><?php _e('Special Promotions [for All]', 'vtprd') ?></li>
          </ul>         
        </li>

        <li><?php _e('Using Pricing Deal Custom Categories makes Group pricing and many other Discount Types *much more powerful*', 'vtprd') ?>
          <ul>
            <li><?php _e('Group together any products you elect seamlessly', 'vtprd') ?></li>
            <li><?php _e('Special Price for this Group', 'vtprd') ?></li>
            <li><?php _e('Grouping affects no other part of your store', 'vtprd') ?></li>
            <li><?php _e('Pricing Deal Custom Categories *do not affect* Product Category store organization and presentation *in any way*', 'vtprd') ?></li>
          </ul>
        </li>
      </ul>
      <span class="buy-button-area">                                 
        <a href="<?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT; ?>" class="buy-button">
            <span class="buy-button-label">Get Pricing Deals Pro</span>
        </a>
      </span>                 
    </div>
        
      
  </div>
 
 <?php
}

    //*******************
    //v2.0.0 re-coded
    //v2.0.2.0 re-coded  to allow for MULTIPLE GENERATIONS of data update request at the same time
    /*
    doThis=runDataUpd 
    */
function vtprd_show_help_page_cntl() {
              
       //************************
       //this is ONLY added out of the vt-pricing-deals.php file when an update is needed, OR is done Manually 
       $doThis = $_GET["doThis"];
       //************************

       //error_log( print_r(  'function vtprd_show_help_page_cntl begin,  $doThis= ' .$doThis, true ) );  

       //just a help button click, show help URL and EXIT
       if (!$doThis) {
       
           ?>
            <style type="text/css">
                .pricing-deal-button {    
                      clear: left;
                      float: left;
                      font-size: 26px;
                      margin: 50px;  }
            </style>                       
            <div class="wrap">
          		<div id="icon-themes" class="icon32"></div>        
          		<a class="pricing-deal-button" target="_blank" href="https://www.varktech.com/documentation/pricing-deals/introrule/"><?php _e('Open Documentation Page!', 'vtprd');?></a>          
            
              <?php 
              
              //ONLY DO THE MOST CURRENT CONVERSION REPORT HERE (other 'stacked' updates don't publish their reports, although they are stored.) 
              $vtprd_v2020_conversion_report = get_option( 'vtprd_v2020_conversion_report' );
              if (($vtprd_v2020_conversion_report) &&
                  (sizeof($vtprd_v2020_conversion_report) > 0)) {
                ?>
                  <a class="pricing-deal-button" href="<?php echo VTPRD_ADMIN_URL;?>edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=reportV2.0.2.0"><?php _e('Show Conversion Report for Version 2.0.2.0', 'vtprd');?></a>
                <?php 
              }

          return;         
       }

        //Write out the BANNER for all other $doThis uses    
        ?>
           <style type="text/css">
              #upgrade-title-area {    
                  margin-top: 75px;
                  width: 95%;
                  border: 10px solid #2AA4D7;
                  border-radius: 5px;   
              }
              #greenTitle {    
                  color: green;
                  margin-left: 10%;
                  margin-top: -1px;
                  border: 2px solid white;
                  padding: 10px 20px;
                  border-radius: 5px;
                  background: white;   
              }
              #outmoded_incl_excl_products_message_div,
              #outmoded_incl_excl_products_report_div,
              #includeOrExclude-report-ul,
              .includeOrExclude-report-li {
                clear:left;
                float:left; 
                font-size:16px;        
              } 
              #includeOrExclude-report-ul {list-style-type: disc;}
              #outmoded_incl_excl_products_message_div,
              #outmoded_incl_excl_products_report_div,
              .includeOrExclude-report-li {
                margin-top: 20px !important;        
              }
              #outmoded_incl_excl_products_message_div {padding-top: 40px;}
              #outmoded_incl_excl_products_report_div {margin-left: 6%;}
              .runDataUpd {float: none !important;}              
                .pricing-deal-button {    
                      clear: left;
                      float: left;
                      font-size: 26px;
                      margin: 50px;  }                                 
           </style> 
                
       		<div id="upgrade-title-area">
            <img id="pricing-deals-img" alt="help" height="40px" width="40px" src="<?php echo VTPRD_URL;?>/admin/images/sale-circle.png" />      
            <?php //v2.0.0 begin ?>
            <?php  if(defined('VTPRD_PRO_DIRNAME')) { ?>
              <h2><?php _e('Pricing Deals Pro', 'vtprd'); ?></h2>
            <?php } else { ?>
              <h2><?php _e('Pricing Deals', 'vtprd'); ?></h2>
            <?php } ?>
            <?php //v2.0.0 end ?>
            <h2 style="    
                    color: green;
                    margin-left: 10%;
                    margin-top: -1px;
                    border: 2px solid white;
                    padding: 10px 20px;
                    border-radius: 5px;
                    background: white;   
                ">
            
        <?php 
         
        global $vtprd_info;
        
        //at end of URL: forceDataUpd= any value - just says to do the $doThis action REGARDLESS
        if (isset($_GET["forceDataUpd"])) {
            $forceDataUpd = $_GET["forceDataUpd"];
        } else {
            $forceDataUpd = FALSE;
        }
             
        $doThis_array = explode("||", $doThis);   //remove || separator, make list an array
        
        
       //error_log( print_r(  '$doThis_array after explode= ', true ) );
       //error_log( var_export($doThis_array, true ) );     
        
        foreach ($doThis_array as $doThis)  {       
            switch ($doThis) {

               case  'runDataUpdV2.0.2.0' :  
    
                    //DO Data Upd
                    $this->vtprd_run_data_update($doThis,$forceDataUpd); //forceDataUpd - do it anyway!!
                  
                    echo __('Data Update Completed' , 'vtprd');
                    echo '</h2>';
                    
                    //ONLY the most recent conversion gets the automatic report shown at general conversion run time
                    $vtprd_v2020_conversion_report = get_option( 'vtprd_v2020_conversion_report' );
                    if (($vtprd_v2020_conversion_report) &&
                        (sizeof($vtprd_v2020_conversion_report) > 0)) {
                      ?>
                        <a class="pricing-deal-button" href="<?php echo VTPRD_ADMIN_URL;?>edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=reportV2.0.2.0"><?php _e('Show Conversion Report for Version 2.0.2.0', 'vtprd');?></a>
                      <?php 
                    }

                    echo '</div>';
                    //remove the 'Update Required' message div
                     ?>        
                        <script type="text/javascript">
                          jQuery(document).ready(function($) {           
                            $('.vtprd-run-data-upd').remove();
                          });  
                        </script>
                     <?php 
                 
                  //**************************************************** 
                   
                  // ALWAYS DO THIS WITH   **MOST RECENT**  UPDATE 
                  
                  //SET  required_updates TO CURRENT ALL DONE
                  
                  //**************************************************** 

                  $vtprd_data_update_options['required_updates'] = $vtprd_info['data_update_options_done_array']['required_updates'];
                  update_option('vtprd_data_update_options',$vtprd_data_update_options);                         

                     
                  //****************************************************   
                                            
                 break; 

                // Custom Report 
                //    Ex: http://[your website name]/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=reportInclExclV2.0.0                         
               case  'reportV2.0.2.0' :         
                    //Produce report on manual updates required
                    echo __('Conversion Report, Version 2.0.2.0' , 'vtprd'); 
                    echo '</h2>';
                    
                    $this->vtprd_report_v2020_conversion();
                 
                    echo '</div>';
                 break; 


               case  'runDataUpdV2.0.0' :         
    
                    //DO Data Upd
                    $this->vtprd_run_data_update($doThis,$forceDataUpd); //forceDataUpd - do it anyway!!
                    
                    //****************************************************************
                    //v2.0.2.0  ONLY DONE IN THE MOST CURRENT UPDATE, SO DELETED HERE.
                    //****************************************************************
                    /*
                    $vtprd_v2_conversion_report = get_option( 'vtprd_v2_conversion_report' );
                    if (($vtprd_v2_conversion_report) &&
                        (sizeof($vtprd_v2_conversion_report) > 0)) {
                    */
    
                                   
                 break;

               case  'reportInclExclV2.0.0' :         
                    //Produce report on manual updates required
                    echo __('Conversion Report, Version 2.0' , 'vtprd'); 
                    echo '</h2>';
                    
                    $this->vtprd_report_outmoded_incl_excl_products();
                 
                    echo '</div>';
                 break; 
                     
                //RECREATE MISSING TABLE
                // Manual Initiation Only! 
                //    Ex: http://[your website name]/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=runTableCreateV2.0.0                        
               case  'runTableCreateV2.0.0' : 
                    $this->vtprd_v2point0_create_tables();
                    echo '</h2>';
                  
                    echo '</div>';               
                 break;              
    
    
                //ALTER TABLE COLUMN
                // Manual Initiation Only! 
                //    Ex: http://[your website name]/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=alterTableColumnV2.0.0                        
               case  'alterTableColumnV2.0.0' : 
                    $this->vtprd_v2point0_alter_table_column();
                    echo '</h2>';
                 
                    echo '</div>';                
                 break;
                              
              //******************************************************************
              // From button on RULE
              //  SUMMARIZE RULE
              //  Ex: href="/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=showRuleInfo&ruleID=12345"  //where ruleID 12345 is the actual rule ID, comes from BUTTON on RULE
              //******************************************************************             
               case  'showRuleInfo' :         
                    $rule_id = $_GET["ruleID"];
                     ?> 
                        <style type="text/css">
                          #upgrade-title-area { margin-top: 20px;}
                          #show-rule-area-title, #show-rule-area, #show-rule-textarea {    
                              clear: left;
                              float: left;
                          }
                          #show-rule-area-title {
                              padding: 15px;
                              border: 1px solid grey !important;
                              border-radius: 5px;
                              background-color: #ccc;
                              margin-bottom: 40px;
                          }
                          #show-rule-textarea {
                              width: 800px;
                              height: 400px;
                              font-family: Menlo,Monaco,monospace;
                              background: 0 0;
                              white-space: pre;
                              overflow: auto;
                              display: block;
                              border: 2px solid green;
                              margin-left: 9%;
                          } 
                          h1 {margin-top: 50px;margin-bottom: 30px;]                     
                        </style>
                         
                        Rule Info - Copy for Support</h2></div>
                               
                        <h1 id="show-rule-area-title">To Copy, &nbsp;&nbsp; - CLICK in the <span style="color:green;">Box Below</span> - &nbsp;&nbsp;  then press  &nbsp;&nbsp; Ctrl + C (PC) &nbsp;&nbsp; or Cmd + C (Mac)</h1>
                        <div id="show-rule-area"> 
                           <textarea readonly="readonly" onclick="this.focus(); this.select()" id="show-rule-textarea" 
                           name="system-rule-textarea" title="To copy the RULE info to send to SUPPORT, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo $this->vtprd_return_rule_info($rule_id); ?></textarea>
                        
                        </div><!-- /#show-rule-info --> 
                     <?php                      
                    echo '</div>';                                                   
                 break;
           }  //end switch
           
        } //end foreach
                
    return;
  }


  //***************************
  //v2.0.0  new function
  //***************************
 function vtprd_run_data_update($doThis,$forceDataUpd=null){  
        
        //error_log( print_r(  'function vtprd_run_data_update begin, $doThis= ' .$doThis, true ) ); 
        
        global $vtprd_info, $wpdb; 
              
        $vtprd_data_update_options = get_option('vtprd_data_update_options');
        
        
        switch ($doThis) {
          //v2.0.2.0 begin
          case  'runDataUpdV2.0.2.0' :
                if ( (isset ($vtprd_data_update_options['required_updates'])) &&
                     (isset ($vtprd_data_update_options['required_updates']['2.0.2.0 Rule conversions'])) &&
                     ($vtprd_data_update_options['required_updates']['2.0.2.0 Rule conversions'] === true)) {
                  if (!$forceDataUpd) {
                     echo __('Data Update Completed' , 'vtprd');          
                     return;
                  }
                }
                $this->vtprd_v2020_data_conversion($forceDataUpd); 
                $this->vtprd_v2020_alter_table();         
                
              break;
          //v2.0.2.0 end    
          case  'runDataUpdV2.0.0' :
                if ( (isset ($vtprd_data_update_options['required_updates'])) &&
                     (isset ($vtprd_data_update_options['required_updates']['2.0.0 Rule conversions'])) &&
                     ($vtprd_data_update_options['required_updates']['2.0.0 Rule conversions'] === true)) {
                  if (!$forceDataUpd) {
                     //echo __('Data Update Completed' , 'vtprd');   //v2.0.2.0 - only the MOST RECENT update sends this message      
                     return;
                  }
                }
                $this->vtprd_v2point0_data_conversion($forceDataUpd);          
            
                 //set data_update_options to ALL DONE
                if (!$vtprd_data_update_options) {
                  $vtprd_data_update_options = array();
                }
            
                
              break;              
        }
   
    return;
  }
  
  //***************************
  //v2.0.2.0  new function
  //***************************
 function vtprd_v2020_data_conversion($forceDataUpd=null){ 
  //convert rule data to use new structures 'buy_group_framework', 'action_group_framework'   
      global $vtprd_edit_arrays_framework, $vtprd_setup_options, $vtprd_info;
      
              
        //error_log( print_r(  'function vtprd_v2020_data_conversion begin', true ) ); 

      //*************
      // NO GLOBAL  $vtprd_rule, $vtprd_rules_set
      //*************
      
      //GET RULES SET     
      $vtprd_rules_set = get_option( 'vtprd_rules_set' );
      
      $vtprd_v2020_conversion_report_array = array();
    
      //create backup before updating ruleset
      update_option( 'vtprd_rules_set_v2.0.2.0_bkup',$vtprd_rules_set ); 
      
      $lifetime_rule_found = false;

      $vtprd_v2020_conversion_report_array = array();
      
      //clean up ruleset iterations.
      vtprd_maybe_resync_rules_set();
      $vtprd_rules_set = get_option( 'vtprd_rules_set' ); //pick up an changes, belt and suspenders.

      if($vtprd_rules_set) {
        $sizeof_rules_set = sizeof($vtprd_rules_set);
        for($i=0; $i < $sizeof_rules_set; $i++) { 
  
          //error_log( print_r(  ' ', true ) );
          //error_log( print_r(  'RULE before update, $i = ' .$i, true ) );
          //error_log( var_export($vtprd_rules_set[$i], true ) );
            
            if ($vtprd_rules_set[$i]->rule_deal_info[0]['discount_lifetime_max_amt_type']  != 'none')  {
               $lifetime_rule_found = true;
            }  
            
                      
            if ($vtprd_rules_set[$i]->inPop == 'groups')  {    
                //PRODUCTS
                if ( ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array']) == 0 ) &&
                      ( $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_incl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_subscriptions_incl_array']) == 0 ) ) {
                  $buy_group_products_include_found = false;  
                } else {
                  $buy_group_products_include_found = true;  
                }
                if ( ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_excl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_excl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array']) == 0 ) &&
                      ( $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_excl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_excl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_subscriptions_excl_array']) == 0 ) ) {
                  $buy_group_products_exclude_found = false;  
                } else {
                  $buy_group_products_exclude_found = true;  
                }
                //values: includeOnly || excludeOnly || both (include and exclude found) || none = neither include nor exclude found 
                switch (TRUE) {
                  case ($buy_group_products_include_found & $buy_group_products_exclude_found) :  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'both'; break;
                  case ($buy_group_products_include_found) :  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'includeOnly'; break;
                  case ($buy_group_products_exclude_found) :  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'excludeOnly'; break;
                  default: $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = 'none'; break;
                } 
                          
                //CUSTOMER        
                if (( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_role_incl_array']) == 0 ) &&
                    ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_email_incl_array']) == 0 ) &&
                    ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_groups_incl_array']) == 0 ) &&
                    ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_memberships_incl_array']) == 0 )) {          
                  $buy_group_customer_include_found = false;  
                } else {
                  $buy_group_customer_include_found = true;  
                }
                if (( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_role_excl_array']) == 0 ) &&
                    ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_email_excl_array']) == 0 ) &&
                    ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_groups_excl_array']) == 0 ) &&
                    ( sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_memberships_excl_array']) == 0 )) {          
                  $buy_group_customer_exclude_found = false;  
                } else {
                  $buy_group_customer_exclude_found = true;  
                }          
                //values: includeOnly || excludeOnly || both (include and exclude found) || none = neither include nor exclude found 
                
                //PICK UP New Array STructures in the framework
                $vtprd_edit_arrays_framework['buy_group_framework']    =  $vtprd_rules_set[$i]->buy_group_population_info;
                $vtprd_rules_set[$i]->buy_group_population_info        =  $vtprd_edit_arrays_framework['buy_group_framework'];
                
                switch (TRUE) {
                  case ($buy_group_customer_include_found & $buy_group_customer_exclude_found) :  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'both'; break;
                  case ($buy_group_customer_include_found) :  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'includeOnly'; break;
                  case ($buy_group_customer_exclude_found) :  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'excludeOnly'; break;
                  default: $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = 'none'; break;
                }             
              
            } else {
              //set default for non-groups
              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'] = FALSE;
              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'] = FALSE;
            }//end if ($vtprd_rules_set[$i]->inPop == 'groups')      
  
  
            if ($vtprd_rules_set[$i]->actionPop == 'groups')  {
      
                //PRODUCTS
                if ( ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array']) == 0 ) &&
                      ( $vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_incl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_incl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_subscriptions_incl_array']) == 0 ) ) {
                  $action_group_products_include_found = false;  
                } else {
                  $action_group_products_include_found = true;  
                }
                if ( ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_excl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_excl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_product_excl_array']) == 0 ) &&
                      ( $vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_excl_array'] <= ' ' ) &&   //will be spaces if no contents!!
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_brands_excl_array']) == 0 ) &&
                      ( sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_subscriptions_excl_array']) == 0 ) ) {
                  $action_group_products_exclude_found = false;  
                } else {
                  $action_group_products_exclude_found = true;  
                }
                
                 
                //PICK UP New Array STructures in the framework
                $vtprd_edit_arrays_framework['action_group_framework']  =  $vtprd_rules_set[$i]->action_group_population_info;
                $vtprd_rules_set[$i]->action_group_population_info      =  $vtprd_edit_arrays_framework['action_group_framework'];
                             
                
                //values: includeOnly || excludeOnly || both (include and exclude found) || none = neither include nor exclude found 
                switch (TRUE) {
                  case ($action_group_products_include_found & $action_group_products_exclude_found) :  $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'both'; break;
                  case ($action_group_products_include_found) :  $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'includeOnly'; break;
                  case ($action_group_products_exclude_found) :  $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'excludeOnly'; break;
                  default: $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = 'none'; break;
                } 
                
                $action_product_set_this_value =  $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'];
  
            } else {
              if ($vtprd_rules_set[$i]->actionPop == 'sameAsInPop')  {
                $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'];              
              } else {
                //set default for non-groups
                $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none'] = FALSE;            
              }
  
            } //end if ($vtprd_rules_set[$i]->actionPop == 'groups')       
      
            
            //REPORTING
  
           //REPORT in ALL cases
           $post = get_post($vtprd_rules_set[$i]->post_id);
  
           $vtprd_v2020_conversion_report_array[] = array(
               'rule_id'                  =>  $vtprd_rules_set[$i]->post_id,
               'rule_title'               =>  $post->post_title,
               'rule_type'                =>  $vtprd_rules_set[$i]->rule_execution_type,
               'buy_group_inpop'          =>  $vtprd_rules_set[$i]->inPop,
               'buy_group_products'       =>  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_products_set_to_include_exclude_both_none'],
               'buy_group_customer'       =>  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_set_to_include_exclude_both_none'],
               'action_group_actionpop'   =>  $vtprd_rules_set[$i]->actionPop,     
               'action_group_products'    =>  $vtprd_rules_set[$i]->action_group_population_info['action_group_products_set_to_include_exclude_both_none']   
           );               
  
           
          //error_log( print_r(  ' ', true ) );
          //error_log( print_r(  'RULE AFTER update, $i = ' .$i, true ) );
          //error_log( var_export($vtprd_rules_set[$i], true ) );
          //error_log( print_r(  '$vtprd_v2020_conversion_report_array, $i = ' .$i, true ) );
          //error_log( var_export($vtprd_v2020_conversion_report_array, true ) );
          
        } //end for($i=0; $i < $sizeof_rules_set; $i++)
        
        update_option( 'vtprd_rules_set',$vtprd_rules_set );
      }
      
      if (sizeof($vtprd_v2020_conversion_report_array) > 0) {         
        update_option( 'vtprd_v2020_conversion_report' ,$vtprd_v2020_conversion_report_array );
      } else {
        delete_option('vtprd_v2020_conversion_report' );
      }
      
      $vtprd_setup_options = get_option( 'vtprd_setup_options' );
      
      //update settings to pick up new user_id and turn off ip_address by default
      if (!$lifetime_rule_found) {
        $vtprd_setup_options['max_purch_rule_lifetime_limit_by_ip'] = 'no';
      }                       
        
      $vtprd_setup_options['max_purch_rule_lifetime_limit_by_user'] = 'yes';
      
      update_option( 'vtprd_setup_options',$vtprd_setup_options );
      
        //error_log( print_r(  '$vtprd_setup_options after conversion' , true ) );
        //error_log( var_export($vtprd_setup_options, true ) );     
                                
  	return;
  }   

     
  //*********************************************
  //v2.0.2.0 new function
  //********************************************* 
  function vtprd_report_v2020_conversion() { 
      global $wpdb, $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set, $vtprd_rule_display_framework ;
      
      //error_log( print_r(  'Function begin vtprd_report_v2020_conversion', true ) );      

      echo '<h1 style="clear:left;float:left;padding-top:50px;color:grey;">V2.0.2.0 &nbsp;&nbsp; Data Conversion upgraded the internal structure of the rules Include/Exclude functions.</h1>';

      echo '<h1 style="clear:left;float:left;padding-top:20px;padding-left: 9%;color:grey;">Rule Include/Exclude Detail Summaries &nbsp; completed Successfully!</h1>';
        
      $vtprd_v2020_conversion_report = get_option( 'vtprd_v2020_conversion_report' );
      
      if ( (!is_array($vtprd_v2020_conversion_report)) 
                    ||
         (($vtprd_v2020_conversion_report) &&
          (sizeof($vtprd_v2020_conversion_report) <= 0)) ) {
        echo '<h1 style="clear:left;float:left;margin-top:50px;">No Rule Conversion was necessary for Version 2.0.2.0</h1>'; 
        return;     
      }

      //error_log( print_r(  '$vtprd_v2020_conversion_report', true ) );
      //error_log( var_export($vtprd_v2020_conversion_report, true ) );

/*
      foreach ($vtprd_v2020_conversion_report as $conversion_report) {

        if ( ($conversion_report['buy_group_products'])  ||
             ($conversion_report['buy_group_customer'])  ||
             ($conversion_report['action_group_products']) ) {
          
          echo '<br><br><br><h3 style="clear:left;float:left;color:green;">RULE: ' .$conversion_report['rule_title']. '&nbsp;&nbsp;&nbsp;&nbsp; Rule ID: ' .$conversion_report['rule_id']. '</h3>';
          
          if ($conversion_report['buy_group_products']) {
            $this->vtprd_print_v2020_report_line('buy_group_products',$conversion_report['buy_group_products']);
          }  
          if ($conversion_report['buy_group_customer']) {
            $this->vtprd_print_v2020_report_line('buy_group_customer',$conversion_report['buy_group_customer']);
          } 
          if ($conversion_report['action_group_products']) {
            $this->vtprd_print_v2020_report_line('action_group_products',$conversion_report['action_group_products']);
          }           
        }       
      } //end Prod List FOREACH
*/      


      foreach ($vtprd_v2020_conversion_report as $conversion_report) {

        //even if no action taken, still report on the RULE
        //if ( ($conversion_report['buy_group_products'])  ||
        //     ($conversion_report['buy_group_customer'])  ||
        //     ($conversion_report['action_group_products']) ) {
          if ($conversion_report['rule_type'] == 'cart') {
            $rule_type =  'Cart Discount';
          } else {
            $rule_type =  'Catalog Discount';          
          }
          echo '<br><br><br><h3 style="clear:left;float:left;color:green;">RULE: ' .$conversion_report['rule_title']. '&nbsp;&nbsp;&nbsp;&nbsp; Rule ID: ' .$conversion_report['rule_id']. '&nbsp;&nbsp;&nbsp;&nbsp; Rule Type: ' .$rule_type. '</h3>';
          
          if ($conversion_report['buy_group_inpop'] == 'groups') {
            if ($conversion_report['buy_group_products']) {
              $this->vtprd_print_v2020_report_line('buy_group_products',$conversion_report['buy_group_products']);
            }  
            if ($conversion_report['buy_group_customer']) {
              $this->vtprd_print_v2020_report_line('buy_group_customer',$conversion_report['buy_group_customer']);
            }
          } else {
            $this->vtprd_print_v2020_nothing_done_line('buy', $conversion_report['rule_type'], $conversion_report['buy_group_inpop']);
          } 
          
          if ($conversion_report['rule_type'] == 'cart') {
            if ($conversion_report['action_group_actionpop'] == 'groups') { 
              if ($conversion_report['action_group_products']) {
                $this->vtprd_print_v2020_report_line('action_group_products',$conversion_report['action_group_products']);
              } 
            } else {
              $this->vtprd_print_v2020_nothing_done_line('action', $conversion_report['rule_type'], $conversion_report['action_group_actionpop']);
            }             
          }            
        //}       
      } //end Prod List FOREACH
    return;   
  }    

     
  //*********************************************
  //v2.0.2.0 new function
  //********************************************* 
  function vtprd_print_v2020_report_line($field_name,$conversion_value) {   
      switch( $field_name ) {      
          case 'buy_group_products': 
              echo '<h4 style="clear:left;float:left;color:green;margin-top: -5px;"><span style="font-style: normal;font-size: 18px;"> Qualify Group Products Area &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span> ';
              break;                      
          case 'buy_group_customer':
              echo '<h4 style="clear:left;float:left;color:green;margin-top: -5px;"><span style="font-style: normal;font-size: 18px;"> Qualify Group Customer Area &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span> ';
              break;            
          case 'action_group_products':          
              echo '<h4 style="clear:left;float:left;color:green;margin-top: -5px;"><span style="font-style: normal;font-size: 18px;"> Discount Group Products Area &nbsp;&nbsp;&nbsp;&nbsp; </span> ';
              break; 
            break;  
      }
      
      switch( $conversion_value ) {      
          case 'both': 
              echo 'Population has been Set to BOTH INclude and EXclude &nbsp;&nbsp;&nbsp; <span style="color:grey !important;">(both include and exclude paramaters are active in the Group)</span> </h4>';
              break;                      
          case 'includeOnly':
              echo 'Population has been Set to INclude &nbsp;&nbsp;&nbsp; <span style="color:grey !important;">((only include paramaters are active in the Group)</span> </h4>';
              break;            
          case 'excludeOnly':          
              echo 'Population has been Set to EXclude &nbsp;&nbsp;&nbsp; <span style="color:grey !important;">(only exclude paramaters are active in the Group)</span> </h4>';
              break; 
            break;  
          case 'none':
          case '':           
              echo 'Population has NO Include or Exclude</h4>';
              break; 
            break; 
          default:           
              echo 'Population set to '.$conversion_value.'.  Population has no Include or Exclude </h4>';
              break; 
            break;                        
      }      
    return;          
  }


     
  //*********************************************
  //v2.0.2.0 new function
  //********************************************* 
  function vtprd_print_v2020_nothing_done_line($buyORaction, $rule_type, $group_type) {   
      
      if ($buyORaction == 'buy') {
        
        echo '<h4 style="clear:left;float:left;color:green;margin-top: -5px;"><span style="font-style: normal;font-size: 18px;"> Qualify Group Already set to &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Any Product </span> ';

        echo '&nbsp;&nbsp;&nbsp; <span style="color:grey !important;">Nothing Done</span> </h4>';
        
      } else {
        if ($rule_type == 'cart') {
          echo '<h4 style="clear:left;float:left;color:green;margin-top: -5px;"><span style="font-style: normal;font-size: 18px;"> Discount Group Already set to </span> ';
          
          switch( $group_type ) { 
              case 'sameAsInPop': 
                  echo '&nbsp;&nbsp;&nbsp;&nbsp; Discount Group Same as Buy Group &nbsp;&nbsp;&nbsp; </span> ';
                  break;                      
              case 'wholeStore':
                  echo '&nbsp;&nbsp;&nbsp;&nbsp; Any Product &nbsp;&nbsp;&nbsp; </span> ';
                  break;                       
          }
          
          echo '&nbsp;&nbsp;&nbsp; <span style="color:grey !important;">Nothing Done</span> </h4>';
        }
      } 
             
    return;          
  }


          
    //***************************
    //v2.0.2.0 new function
    // added purchaser_id bigint(20),
    // to track logged-in user ID - this now becomes the PRIMARY search criteria!! 
    // this is PRO-only, but PRO may not be active or even installed.
    // so coded to ignore all that, and just work with the table itself. 
    //***************************
   function vtprd_v2020_alter_table() { 
      //add column to VTPRD_LIFETIME_LIMITS_PURCHASER
      global $wpdb;

      
      $lifetime_table_name =  $wpdb->prefix.'vtprd_lifetime_limits_purchaser';
      
      $is_this_table_here = $wpdb->get_var( "SHOW TABLES LIKE '$lifetime_table_name'" );
      if ( $is_this_table_here  !== $lifetime_table_name) {
        return;
      } 
      
      /*
      $column_found = $wpdb->query( "SHOW COLUMNS FROM  `".VTPRD_LIFETIME_LIMITS_PURCHASER."`  LIKE `purchaser_user_id`;" );      
      if ($column_found) {
        return;      
      }
      */
      $existing_columns_array = $wpdb->get_col("DESC {$lifetime_table_name}", 0);
      if (in_array('purchaser_user_id', $existing_columns_array)) {
        return;      
      }

      //add column to existing table
	  $wpdb->query( "ALTER TABLE $lifetime_table_name  ADD `purchaser_user_id` bigint(20) ;" );
      
      //add value to new setup_option field
      $setup_options = get_option( 'vtprd_setup_options' );
      if ($setup_options['max_purch_rule_lifetime_limit_by_user'] <= ' ') {
        
        $setup_options['max_purch_rule_lifetime_limit_by_user'] = 'yes';
        update_option('vtprd_setup_options', $setup_options);
        
        //error_log( print_r(  'vtprd_setup_options after update= ' , true ) );
        //error_log( var_export($setup_options, true ) );
      }
      
      global $vtprd_setup_options;
      $vtprd_setup_options = $setup_options; 
      
      //********************************************************************************************
      //examine existing lifetime rule history, and add in purchaser_user_id based on email address
      //********************************************************************************************

      $vtprd_purchase_log = $wpdb->get_results(  "SELECT * FROM `" . $lifetime_table_name . "` ", ARRAY_A );
      
      //error_log( print_r(  'vtprd_lifetime_limits_purchaser CONTENTS = ', true ) );
      //error_log( var_export($vtprd_purchase_log, true ) );
      
      if ($vtprd_purchase_log) {
        $user_table_name =  $wpdb->prefix.'users';
             
        $sizeof_purchase_log = sizeof($vtprd_purchase_log);         
        for($i=0; $i < $sizeof_purchase_log; $i++) { 
           $row =  $vtprd_purchase_log[$i];
        
          //error_log( print_r(  'vtprd_lifetime_limits_purchaser ROW email = ' .$row['purchaser_email'], true ) );
          
          if ($row['purchaser_email']) {
             $user_email = $row['purchaser_email'];
             $user_id 	= $wpdb->get_var( "SELECT ID  FROM `".$user_table_name."`   WHERE   `user_email` = '" . $user_email . "' " ); 
        
          //error_log( print_r(  'USER ID = ' .$user_id , true ) );
                       
             if ($user_id) {
                $purchaser_table_id = $row['purchaser_table_id']; 
        
          //error_log( print_r(  '$purchaser_table_id = ' .$purchaser_table_id , true ) );
                                
                //$wpdb->query( $wpdb->prepare(  "UPDATE `".$lifetime_table_name."`  SET purchaser_user_id = `" . $user_id . "` WHERE purchaser_table_id = `" . $purchaser_table_id . "` " ) );
                $wpdb->query( $wpdb->prepare(  "UPDATE `".$lifetime_table_name."`  SET purchaser_user_id = %s WHERE purchaser_table_id = %s ", $user_id, $purchaser_table_id ) );
        
          //error_log( print_r(  'vtprd_lifetime_limits_purchaser ROW UPDATE COMPLETE ', true ) );                
             }
          }
        }
      }         
      
       
      return;
   }
  
  
  //***************************
  //v2.0.0  new function
  //***************************
 function vtprd_v2point0_data_conversion($forceDataUpd=null){ 
  //convert rule data to use new structures 'buy_group_framework', 'action_group_framework'   
      global $vtprd_edit_arrays_framework, $vtprd_setup_options, $vtprd_info;

      //*************
      // NO GLOBAL  $vtprd_rule, $vtprd_rules_set
      //*************
      
      //GET RULES SET     
      $vtprd_rules_set = get_option( 'vtprd_rules_set' );
      if ($vtprd_rules_set == FALSE) {        
        return;
      }
      
      $sizeof_rules_set = sizeof($vtprd_rules_set); 
            
      //create backup before updating ruleset
      if (!get_option( 'vtprd_rules_set_v2.0.0_bkup' )) {
        add_option( 'vtprd_rules_set_v2.0.0_bkup',$vtprd_rules_set ); 
      } 
      
      //clean up ruleset iterations.
      vtprd_maybe_resync_rules_set();
      $vtprd_rules_set = get_option( 'vtprd_rules_set' ); //pick up an changes, belt and suspenders.


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
      //v2.0.0 end M solution

/*
IF CUSTOMER has PRO version, but deletes it first, then does the data conversion, this will cause a problem.
Run the FULL conversion in all cases!!!!!!!!!!
      
      if ( (defined('VTPRD_PRO_VERSION')) ||
           ($this->vtprd_is_pro_plugin_installed()) ) {  //is the PRO version installed but not active
        $continue_with_pro_upd = true;    
      } else {
        
          //FREE version ONLY - no data updates, no includes/excludes
          //spin through the rule set, expand out each rule, then update and exit!       
          for($i=0; $i < $sizeof_rules_set; $i++) { 
            $vtprd_rules_set[$i]->buy_group_population_info     =   $vtprd_edit_arrays_framework['buy_group_framework']; 
            $vtprd_rules_set[$i]->action_group_population_info  =   $vtprd_edit_arrays_framework['action_group_framework'];          
          } 
           
          if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
             error_log( print_r(  ' ', true ) );
             error_log( print_r(  'vtprd_v2point0_data_conversion for FREE version done ', true ) );
             error_log( var_export($vtprd_rules_set, true ) );
          }
        
        update_option( 'vtprd_rules_set',$vtprd_rules_set );
        delete_option('vtprd_v2_conversion_report'); //just in case               
  	    return;       
      }
*/      

      for($i=0; $i < $sizeof_rules_set; $i++) {       
        //move data to category, product and role arrays
        //change product filter selections to 'by category' as appropriate
        //load animation??? 

      //error_log( print_r(  'RULE BEFORE conversion, $i= ' .$i, true ) );
      //error_log( var_export($vtprd_rules_set[$i], true ) );
         
        if (!$forceDataUpd) {
          //if rule udpated with version 2.0 or greater, nothing to do!!
          if ( (isset($vtprd_rule->rule_updated_with_free_version_number)) &&
               (version_compare( $vtprd_rule->rule_updated_with_free_version_number, '2.0', '>=' )) ) { //check if >= than version 2.0.0
            continue;
          }
        }
        
        $vtprd_rules_set[$i]->buy_group_population_info     =   $vtprd_edit_arrays_framework['buy_group_framework']; 
        $vtprd_rules_set[$i]->action_group_population_info  =   $vtprd_edit_arrays_framework['action_group_framework'];  
               
        //used later
        $oldInPop = $vtprd_rules_set[$i]->inPop;
        
        //convert INPOP selection
        $skip_to_next = FALSE;
        switch( $vtprd_rules_set[$i]->inPop ) {      
            case 'wholeStore':            
            case 'cart': 
                  $skip_to_next = TRUE;
              break;              
            case 'groups':                                    
                  $carry_on = true; 
               break; 
             case 'varName':                                   
                  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_incl_array'] = $vtprd_rules_set[$i]->buy_group_varName_array;
                  //all group selection now under 'groups'
                  $vtprd_rules_set[$i]->inPop = 'groups';                    
              break;

            case 'vargroup':
                  //variations now under product                  
                  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'] = $vtprd_rules_set[$i]->var_in_checked;
                  //all group selection now under 'groups'
                  $vtprd_rules_set[$i]->inPop = 'groups';
              break;
            case 'single':                  
                  $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'][] = $vtprd_rules_set[$i]->inPop_singleProdID;
                  //all group selection now under 'groups'
                  $vtprd_rules_set[$i]->inPop = 'groups';                               
              break;
        }
        
        if ($skip_to_next) {
          continue;
        }
        
        //always check categories and rules
        if (is_array($vtprd_rules_set[$i]->prodcat_in_checked)) {
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array']   = $vtprd_rules_set[$i]->prodcat_in_checked;
        }
        if (is_array($vtprd_rules_set[$i]->rulecat_in_checked)) {
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'] = $vtprd_rules_set[$i]->rulecat_in_checked;                       
        }          
        if (is_array($vtprd_rules_set[$i]->role_in_checked)) {
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_role_incl_array'] = $vtprd_rules_set[$i]->role_in_checked;        
        }

        
        //SET AND/OR and ADVANCED **if varname + cats selected**
        if ( ($oldInPop == 'varName') &&
             ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'])   > 0 ) ||
               (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array']) > 0 ) ) ) {
            
          $vtprd_rules_set[$i]->rule_type_select = 'advanced';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_show_and_or_switches'] = 'yes'; 
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_and_or'] = 'and';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] ++;

          //can't both be 'and'!!
          if ( (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'])   > 0 ) &&
               (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array']) > 0 ) ) {            
            $do_nothing = true;
          } else {
            if (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_incl_array'])   > 0 ) {
              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_and_or'] = 'and';
              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] ++;
            }             
            if (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_incl_array'])   > 0 ) {
              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_and_or'] = 'and';
              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] ++;
            } 
          }
                             
        }
        
        //and-or  $role_and_or_in
        switch( $vtprd_rules_set[$i]->role_and_or_in ) {      
            case 'and':
            case 'or':                                     
                 $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] = $vtprd_rules_set[$i]->role_and_or_in;                                   
              break; 
              
            default:  
                  
                  //if one of the named 3, ** it's an automatic 'and' ** - otherwise, a blank is an 'or'
                  switch( $oldInPop ) {      
                      case 'varName':                                   
                      case 'vargroup':
                      case 'single':                  
                            if (sizeof($vtprd_rules_set[$i]->buy_group_population_info['buy_group_role_incl_array']) > 0) {
                              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] = 'and';
                            } else {
                              $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] = 'or';
                            }                              
                        break;
                      default: 
                            $vtprd_rules_set[$i]->buy_group_population_info['buy_group_customer_and_or'] = 'or';                              
                        break;
                  } 
                                                                
              break;              
        }
        
        //used later
        $oldActionPop = $vtprd_rules_set[$i]->actionPop; 
                
        //convert actionPop selection
        switch( $vtprd_rules_set[$i]->actionPop ) {      
            case 'groups':                                    
                  $carry_on = true;                                     
              break;

             case 'varName':                                    
                  $vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_incl_array'] = $vtprd_rules_set[$i]->action_group_varName_array;
                  //all group selection now under 'groups'
                  $vtprd_rules_set[$i]->actionPop = 'groups';                    
              break;

            case 'vargroup':
                  //variations now under product
                  $vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'] = $vtprd_rules_set[$i]->var_out_checked;
                  //all group selection now under 'groups'
                  $vtprd_rules_set[$i]->actionPop = 'groups';
              break;
            case 'single':
                  $vtprd_rules_set[$i]->action_group_population_info['action_group_product_incl_array'][] = $vtprd_rules_set[$i]->actionPop_singleProdID;
                  //all group selection now under 'groups'
                  $vtprd_rules_set[$i]->actionPop = 'groups';                               
              break;
        } 

        //always check categories
        if (is_array($vtprd_rules_set[$i]->prodcat_out_checked)) {
          $vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array']   = $vtprd_rules_set[$i]->prodcat_out_checked;
        }
        
        if (is_array($vtprd_rules_set[$i]->rulecat_out_checked)) {
          $vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'] = $vtprd_rules_set[$i]->rulecat_out_checked;
        }
        
        //SET AND/OR and ADVANCED **if varname + cats selected**
        if ( ($oldActionPop == 'varName') &&
             ( (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'])   > 0 ) ||
               (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array']) > 0 ) ) ) {
            
          $vtprd_rules_set[$i]->rule_type_select = 'advanced';
          $vtprd_rules_set[$i]->action_group_population_info['action_group_show_and_or_switches'] = 'yes'; 
          $vtprd_rules_set[$i]->action_group_population_info['action_group_var_name_and_or'] = 'and';
          $vtprd_rules_set[$i]->action_group_population_info['action_group_and_switch_count'] ++;

          //can't both be 'and'!!
          if ( (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'])   > 0 ) &&
               (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array']) > 0 ) ) {            
            $do_nothing = true;
          } else {
            if (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_incl_array'])   > 0 ) {
              $vtprd_rules_set[$i]->action_group_population_info['action_group_prod_cat_and_or'] = 'and';
              $vtprd_rules_set[$i]->action_group_population_info['action_group_and_switch_count'] ++;
            }             
            if (sizeof($vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_incl_array'])   > 0 ) {
              $vtprd_rules_set[$i]->action_group_population_info['action_group_plugin_cat_and_or'] = 'and';
              $vtprd_rules_set[$i]->action_group_population_info['action_group_and_switch_count'] ++;
            } 
          }
        }
        
        //DONE with iteration.       

         if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){ 
           error_log( print_r(  ' ', true ) );
           error_log( print_r(  'vtprd_v2point0_data_conversion RULE AFTER conversion, $i= ' .$i, true ) );
           error_log( var_export($vtprd_rules_set[$i], true ) );
         } 
                        
      } 
      
      //**************************************
      //PRODUCT include/exclude updates BEGIN
      //**************************************

      global $wpdb;          
      $varsql = "SELECT posts.`ID`
            			FROM `".$wpdb->posts."` AS posts			
            			WHERE posts.`post_status` = 'publish' AND posts.`post_type`= 'product' ORDER BY posts.`ID` ASC";                    
    	$prod_id_list = $wpdb->get_col($varsql);

      //error_log( print_r(  '$prod_id_list', true ) );
      //error_log( var_export($prod_id_list, true ) );

      //Include or Exclude list
      
      $vtprd_includeOrExclude_v2_report_array = array();

      foreach ($prod_id_list as $prod_id) {
        if ( get_post_meta($prod_id, $vtprd_info['product_meta_key_includeOrExclude'], true) ) {
          $vtprd_includeOrExclude = get_post_meta($prod_id, $vtprd_info['product_meta_key_includeOrExclude'], true);
  //error_log( print_r(  'meta array FOUND,  $prod_id= ' .$prod_id, true ) );
   //error_log( var_export($vtprd_includeOrExclude, true ) );       
        } 

        if ( (is_array ($vtprd_includeOrExclude)) &&
             (isset ($vtprd_includeOrExclude['includeOrExclude_option'])) &&
             ($vtprd_includeOrExclude['includeOrExclude_option'] > ' ') ) {
          $carry_on = true;    
        } else {
     //error_log( print_r(  'meta array NOT FOUND $prod_id= ' .$prod_id, true ) );      
          continue;
        }
        
        $includeOrExclude_checked_list = $vtprd_includeOrExclude['includeOrExclude_checked_list'];
        $vtprd_includeOrExclude_v2_report = array(
            'includeOrExclude_option' => $vtprd_includeOrExclude['includeOrExclude_option'],
            'includeOrExclude_included_rules' => array (),
            'includeOrExclude_excluded_rules' => array ()                     
        );

        //update the rules with the include/excludes from this product
        switch( $vtprd_includeOrExclude['includeOrExclude_option'] ) {
          case 'includeAll':  
            break;
          case 'includeList': 
          
          //error_log( print_r(  'includeList ', true ) );                  
              //add to includes in list, exclude in all else             
              for($i=0; $i < $sizeof_rules_set; $i++) { 
                $rule_id = $vtprd_rules_set[$i]->post_id;
                $post = get_post($rule_id);
                $rule_change_msg = false;
            //error_log( print_r(  'found $rule_id = ' .$rule_id, true ) );
                if (in_array($rule_id, $includeOrExclude_checked_list)) {
                    
                    switch( TRUE ) {  
                      case ( ($vtprd_rules_set[$i]->pricing_type_select == 'all') || //whole store on sale
                             ($vtprd_rules_set[$i]->inPop == 'wholeStore') ) :
                          $rule_change_msg = 'Product selected for Product Include list, but whole store already selected.  Nothing done.';
                        break;
                      
                      case ( $vtprd_rules_set[$i]->buy_group_population_info['buy_group_set_to_exclude_only'] !== false) :
                          $rule_change_msg = 'Product selected for Product Include list, but Exclude-only population selection in force (that is, anything not excluded is automatically included).  Nothing done.';
                        break;
                      
                      default:
                          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'][] = $prod_id;
                          $rule_change_msg = 'Product added to Product Include list for Rule.';                       
                        break;
                    }  

                    $vtprd_includeOrExclude_v2_report['includeOrExclude_included_rules'][] = array (
                          'rule_id' =>   $rule_id,
                          'rule_title' =>   $post->post_title,
                          'rule_change_msg' =>  $rule_change_msg              
                        );
                        
            //error_log( print_r(  'INcluded ' .$rule_id, true ) ); 
                               
                } else { 
                    $rule_change_msg = 'Rule added to Product exclude list.';
                    if ($vtprd_rules_set[$i]->pricing_type_select == 'all') { //whole store on sale
                    
                      $vtprd_rules_set[$i]->rule_template = 'C-simpleDiscount'; //change from "C-storeWideSale"
                      $vtprd_rules_set[$i]->pricing_type_select = 'simple'; //changed to simple to allow for an exclude list
                      $vtprd_rules_set[$i]->inPop = 'groups';
                      $vtprd_rules_set[$i]->buy_group_population_info['buy_group_set_to_exclude_only'] = true; 
                      
                      $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To allow for an Exclusion list:' .
                        '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Deal Type" changed from "Whole Store" to "Simple Discount".' .
                        '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Select Group By" changed from "Any Product" to "By Category".';
                    } else {
                      if ($vtprd_rules_set[$i]->inPop == 'wholeStore')  { //whole store on sale
                        $vtprd_rules_set[$i]->inPop = 'groups';
                        $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To allow for an Exclusion list:' .
                          '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Select Group By" changed from "Any Product" to "By Category".';
                      }
                    }                    
                    
                    $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array'][] = $prod_id;
                    $vtprd_includeOrExclude_v2_report['includeOrExclude_excluded_rules'][] = array (
                          'rule_id' =>   $rule_id,
                          'rule_title' =>   $post->post_title,
                          'rule_change_msg' =>   $rule_change_msg              
                        ); 
                                           
            //error_log( print_r(  'EXcluded ' .$rule_id. ' $includeOrExclude_checked_list= ' , true ) );
            //error_log( var_export($includeOrExclude_checked_list, true ) ); 
                                 
                }
              }                                          
            break;
          case 'excludeList':  
              //error_log( print_r(  'excludeList ', true ) ); 
              //add to excludes in list
              for($i=0; $i < $sizeof_rules_set; $i++) { 
                $rule_id = $vtprd_rules_set[$i]->post_id;
                $post = get_post($rule_id);
                $rule_change_msg = false;                
            //error_log( print_r(  'found $rule_id = ' .$rule_id, true ) );
                if (in_array($rule_id, $includeOrExclude_checked_list)) {    
                                       
                    if ($vtprd_rules_set[$i]->pricing_type_select == 'all') { //whole store on sale
                    
                      $vtprd_rules_set[$i]->rule_template = 'C-simpleDiscount'; //change from "C-storeWideSale"
                      $vtprd_rules_set[$i]->pricing_type_select = 'simple'; //changed to simple to allow for an exclude list
                      $vtprd_rules_set[$i]->inPop = 'groups';
                      $vtprd_rules_set[$i]->buy_group_population_info['buy_group_set_to_exclude_only'] = true;
                      
                      $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To allow for an Exclusion list:' .
                        '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Deal Type" changed from "Whole Store" to "Simple Discount".' .
                        '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Select Group By" changed from "Any Product" to "By Category".';
                    } else {
                      if ($vtprd_rules_set[$i]->inPop == 'wholeStore')  { //whole store on sale
                        $vtprd_rules_set[$i]->inPop = 'groups';
                        $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To allow for an Exclusion list:' .
                          '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Select Group By" changed from "Any Product" to "By Category".';
                      } 
                    }                   
                    
                    $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array'][] = $prod_id;
                    
                    $vtprd_includeOrExclude_v2_report['includeOrExclude_excluded_rules'][] = array (
                          'rule_id' =>   $rule_id,
                          'rule_title' =>   $post->post_title,
                          'rule_change_msg' =>   $rule_change_msg              
                        ); 
                                             
                }
              }                                                             
            break;
          case 'excludeAll': 
              //error_log( print_r(  'excludeAll ', true ) );  
              //add to excludes in all
              for($i=0; $i < $sizeof_rules_set; $i++) { 
                    $rule_id = $vtprd_rules_set[$i]->post_id;
                    $post = get_post($rule_id);
                    $rule_change_msg = false;
                                       
                    if ($vtprd_rules_set[$i]->pricing_type_select == 'all') { //whole store on sale
                      
                      $vtprd_rules_set[$i]->rule_template = 'C-simpleDiscount'; //change from "C-storeWideSale"
                      $vtprd_rules_set[$i]->pricing_type_select = 'simple'; //changed to simple to allow for an exclude list
                      $vtprd_rules_set[$i]->inPop = 'groups';
                      $vtprd_rules_set[$i]->buy_group_population_info['buy_group_set_to_exclude_only'] = true;
                      
                      $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To allow for an Exclusion list:' .
                        '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Deal Type" changed from "Whole Store" to "Simple Discount".' .
                        '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Select Group By" changed from "Any Product" to "By Category".';
                    } else {
                      if ($vtprd_rules_set[$i]->inPop == 'wholeStore')  { //whole store on sale
                        $vtprd_rules_set[$i]->inPop = 'groups';
                        $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;To allow for an Exclusion list:' .
                          '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Select Group By" changed from "Any Product" to "By Category".';
                      }
                    }                    
                    
                    $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array'][] = $prod_id;
                    
                    $vtprd_includeOrExclude_v2_report['includeOrExclude_excluded_rules'][] = array (
                          'rule_id' =>   $rule_id,
                          'rule_title' =>   $post->post_title,
                          'rule_change_msg' =>   $rule_change_msg              
                        ); 
                          
              }              
            break;
        } 
        
        //end product 
        $vtprd_includeOrExclude_v2_report_array[] = array (
          'prod_id' => $prod_id ,
          'details' => $vtprd_includeOrExclude_v2_report
        );

        //AFTER each product spins through all rules, check all rules to set or unset "buy_group_set_to_exclude_only"
        for($i=0; $i < $sizeof_rules_set; $i++) {   
            $buy_groups_include_found = false; //used for setting the 'exclude only' later            
            if ( ($vtprd_rules_set[$i]->inPop == 'groups') &&
                 ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array'] !== true) ) {                      
              $buy_arrays_framework = $vtprd_rules_set[$i]->buy_group_population_info;         
              foreach( $buy_arrays_framework as $key => $value ) {  
                   //test search arrays (only)) for search data
                   switch ($key) {           
                     case  'buy_group_prod_cat_incl_array':   
                     case  'buy_group_plugin_cat_incl_array':
                     case  'buy_group_product_incl_array':
                     case  'buy_group_var_name_incl_array':
                     case  'buy_group_brands_incl_array':
                     case  'buy_group_subscriptions_incl_array':
                     case  'buy_group_role_incl_array':
                     case  'buy_group_email_incl_array':
                     case  'buy_group_groups_incl_array':
                     case  'buy_group_memberships_incl_array':                
                       if ( ((is_array($value)) &&
                             (sizeof($value) > 0)) 
                                  ||
                            ((!is_array($value)) &&
                             ($value > ' ')) ) { //  > ' ' test catches the vargroup list when empty 
                          $buy_groups_include_found = true; //used for setting the 'exclude only' later
                          continue 2; //done! - v2.0.1.0 changed to 'continue 2'
                       }
                       break;
                   } //end switch
              } //end foreach
              
              if (!$buy_groups_include_found) {
                $vtprd_rules_set[$i]->buy_group_population_info['buy_group_set_to_exclude_only'] = true; 
              }
              
            } //end if             
        } //end for        
        
                       
      } //end foreach product
      
      
      //If a Catalog rule, buy group and/or/each must always be set to 'or'
      for($i=0; $i < $sizeof_rules_set; $i++) {
        if ( ($vtprd_rules_set[$i]->rule_execution_type == 'display') 
                          &&
             (($vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_and_or'] != 'or') ||
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_and_or'] != 'or') || 
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_and_or'] != 'or') ||
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_and_or'] != 'or') ||
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_and_or'] != 'or') ||
              ($vtprd_rules_set[$i]->buy_group_population_info['buy_group_subscriptions_and_or'] != 'or')) ) {      
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_show_and_or_switches'] = 'no';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_prod_cat_and_or'] = 'or'; 
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_plugin_cat_and_or'] = 'or'; 
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_and_or'] = 'or';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_var_name_and_or'] = 'or';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_brands_and_or'] = 'or';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_subscriptions_and_or'] = 'or';
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_and_switch_count'] = 0;
          
          /*
          $rule_change_msg .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Catalog Rule new and/or switches must all set to "OR":';

          $rule_id = $vtprd_rules_set[$i]->post_id;
          $post = get_post($rule_id);
          $vtprd_includeOrExclude_v2_report['includeOrExclude_excluded_rules'][] = array (
                'rule_id' =>   $rule_id,
                'rule_title' =>   $post->post_title,
                'rule_change_msg' =>   $rule_change_msg              
          );
          */           
        }                
      } 
      
      
      //UNDUPLICATE the product include/exclude arrays
      if (sizeof($vtprd_includeOrExclude_v2_report_array) > 0) {
        for($i=0; $i < $sizeof_rules_set; $i++) {

          $buy_group_product_array = $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'];
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_incl_array'] = array_unique($buy_group_product_array);
     
          $buy_group_product_array = $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array'];
          $vtprd_rules_set[$i]->buy_group_population_info['buy_group_product_excl_array'] = array_unique($buy_group_product_array);                  
        } 
        
        update_option( 'vtprd_v2_conversion_report',$vtprd_includeOrExclude_v2_report_array );
        
       } else {
        delete_option('vtprd_v2_conversion_report');
       }    
      //**************************************
      //PRODUCT include/exclude updates END
      //**************************************

      update_option( 'vtprd_rules_set',$vtprd_rules_set );

        //error_log( print_r(  ' ', true ) );
        //error_log( print_r(  ' ', true ) );
        //error_log( print_r(  '**REPORT** vtprd_includeOrExclude_v2_report = ', true ) );
        //error_log( var_export($vtprd_includeOrExclude_v2_report_array, true ) );
                                   
  	return;
  }  
   

  //*********************************************
  //v2.0.0 new function
  //*********************************************
  function vtprd_return_rule_info($rule_id) { 

        //NOT GLOBAL!!!
        $vtprd_setup_options = get_option( 'vtprd_setup_options' );
        $vtprd_rules_set = get_option( 'vtprd_rules_set' );
        
        $rule_found = false;
        $sizeof_rules_set = sizeof($vtprd_rules_set);
        for($i=0; $i < $sizeof_rules_set; $i++) {  
           if ($vtprd_rules_set[$i]->post_id == $rule_id) {
              $rule_found = true;
              break;
           }
        } 
        
        if (!$rule_found) {
          return 'Rule Not Found, Rule ID= ' .$rule_id ;
        }
        
        $post = get_post($vtprd_rules_set[$i]->post_id);
    
        $send_data  = '###   Begin Rule Info    Rule ID= ' .$rule_id. '    Rule Status= ' .$post->post_status. '   ###' . "\n\n";

      	$send_data .= ' RULE TITLE:   ' . $post->post_title . "\n\n";

        if (sizeof($vtprd_rules_set[$i]->rule_error_message) > 0) {
          $send_data .= ' RULE ERROR MESSAGES: <pre>'.print_r($vtprd_rules_set[$i]->rule_error_message, true).'</pre>'  . "\n\n";   
        }
        
        $send_data .= ' BLUEPRINT                '  . "\n";
        $send_data .= 'Discount Type:            ' . $vtprd_rules_set[$i]->cart_or_catalog_select . "\n";
        $send_data .= 'Deal Type:                ' . $vtprd_rules_set[$i]->pricing_type_select . "\n";
        if ($vtprd_rules_set[$i]->minimum_purchase_select == 'next') {
          $deal_action = 'Discount Next Item';
        } else {
          $deal_action = 'Discount Item already in cart';
        }
        $send_data .= 'Deal Action:              ' . $deal_action . "\n";
        $send_data .= 'Deal Schedule:            ' . $vtprd_rules_set[$i]->rule_on_off_sw_select . "\n";
      	$send_data .= 'Basic or Advanced:        ' . $vtprd_rules_set[$i]->rule_type_select . "\n";
        $send_data .= 'Cheapest:                 ' . $vtprd_rules_set[$i]->apply_deal_to_cheapest_select . "\n \n";        
        
        $send_data .= ' BUY GROUP                '  . "\n";        
        $send_data .= 'Select Group By:          ' . $vtprd_rules_set[$i]->inPop . "\n";
        $send_data .= 'Select Group By Selections: <pre>'.print_r($vtprd_rules_set[$i]->buy_group_population_info, true).'</pre>'  . "\n\n";
        $send_data .= 'Product Applies To:       ' . $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_applies_to'] . "\n"; 
        if ($vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_type'] == 'none') {
          $buy_amt_type = 'Each Unit';
        } else {
          $buy_amt_type = $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_type'];        
        }
        $send_data .= 'Group Amount Selection:   ' . $buy_amt_type  . "\n";
        $send_data .= 'Group Amount Count:       ' . $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_count'] . "\n";  
        $send_data .= 'Min Max Selection:        ' . $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_mod'] . "\n"; 
        $send_data .= 'Min Max Count:            ' . $vtprd_rules_set[$i]->rule_deal_info[0]['buy_amt_mod_count'] . "\n"; 
        $send_data .= 'Rule Repeat Condition:    ' . $vtprd_rules_set[$i]->rule_deal_info[0]['buy_repeat_condition'] . "\n"; 
        $send_data .= 'Rule Repeat Count:        ' . $vtprd_rules_set[$i]->rule_deal_info[0]['buy_repeat_count'] . "\n \n"; 

        $send_data .= ' ACTION GROUP                '  . "\n";        
        $send_data .= 'Select Group By:          ' . $vtprd_rules_set[$i]->actionPop . "\n";
        $send_data .= 'Select Group By Selections: <pre>'.print_r($vtprd_rules_set[$i]->action_group_population_info, true).'</pre>'  . "\n\n"; 
        $send_data .= 'Product Applies To:       ' . $vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_applies_to'] . "\n"; 
        if ($vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_type'] == 'none') {
          $action_amt_type = 'Each Unit';
        } else {
          $action_amt_type = $vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_type'];        
        }        
        $send_data .= 'Group Amount Selection:   ' . $action_amt_type  . "\n";
        $send_data .= 'Group Amount Count:       ' . $vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_count'] . "\n";  
        $send_data .= 'Min Max Selection:        ' . $vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_mod'] . "\n"; 
        $send_data .= 'Min Max Count:            ' . $vtprd_rules_set[$i]->rule_deal_info[0]['action_amt_mod_count'] . "\n"; 
        $send_data .= 'Group Repeat Condition:   ' . $vtprd_rules_set[$i]->rule_deal_info[0]['action_repeat_condition'] . "\n"; 
        $send_data .= 'Group Repeat Count:       ' . $vtprd_rules_set[$i]->rule_deal_info[0]['action_repeat_count']  . "\n \n"; 
        
        //v2.0.0.3a begin
        if ($vtprd_rules_set[$i]->pricing_type_select == 'bulk') {

        $send_data .= ' Bulk Info            '  . "\n"; 
        $send_data .= 'Bulk Deal Method:          ' . $vtprd_rules_set[$i]->bulk_deal_method . "\n";
        $send_data .= 'Bulk Qty Count By:         ' . $vtprd_rules_set[$i]->bulk_deal_qty_count_by . "\n"; 
        $send_data .= 'Bulk Table Rows: <pre>'.print_r($vtprd_rules_set[$i]->bulk_deal_array, true).'</pre>'  . "\n\n";
        
        }
        //v2.0.0.3a end

        $send_data .= ' DISCOUNT GROUP           '  . "\n"; 
        $send_data .= 'Discount Amount:          ' . $vtprd_rules_set[$i]->rule_deal_info[0]['discount_amt_type'] . "\n";
        $send_data .= 'Discount Amount Count:    ' . $vtprd_rules_set[$i]->rule_deal_info[0]['discount_amt_count'] . "\n"; 
        $send_data .= 'Discount Auto Add Free:   ' . $vtprd_rules_set[$i]->rule_deal_info[0]['discount_auto_add_free_product'] . "\n";
        $send_data .= 'Discount Applies To:      ' . $vtprd_rules_set[$i]->rule_deal_info[0]['discount_applies_to'] . "\n";
        $send_data .= 'Discount Coupon Code:     ' . $vtprd_rules_set[$i]->only_for_this_coupon_name . "\n \n"; 
        
        //v2.0.0.8 begin
        $bulk_deal_array_count = sizeof($vtprd_rule->bulk_deal_array);
        if ($bulk_deal_array_count > 0) { 
          $send_data .= ' BULK DEAL        '  . "\n"; 
          $send_data .= var_export($vtprd_rule->bulk_deal_array, true );   
          $send_data .= 'end bulk deal '  . "\n \n";      
        }
        //v2.0.0.8 end

        $send_data .= ' DISCOUNT MESSAGES        '  . "\n";
        $send_data .= 'Checkout Message:         ' . $vtprd_rules_set[$i]->discount_product_short_msg . "\n";
        $send_data .= 'Advertising Message:      ' . $vtprd_rules_set[$i]->discount_product_full_msg . "\n \n";                        

        $send_data .= ' DISCOUNT LIMITS          '  . "\n";       
        $send_data .= 'Customer Rule Limit:      ' . $vtprd_rules_set[$i]->rule_deal_info[0]->discount_lifetime_max_amt_type . "\n";  //v2.0.0.8 corrected rule_deal_info[0]->
        $send_data .= 'Customer Rule Limit Count:' . $vtprd_rules_set[$i]->rule_deal_info[0]->discount_lifetime_max_amt_count . "\n";  //v2.0.0.8 corrected rule_deal_info[0]->       
        $send_data .= 'Cart Limit:               ' . $vtprd_rules_set[$i]->rule_deal_info[0]->discount_rule_max_amt_type . "\n";  //v2.0.0.8 corrected rule_deal_info[0]->
        $send_data .= 'Cart Limit Count:         ' . $vtprd_rules_set[$i]->rule_deal_info[0]->discount_rule_max_amt_count . "\n";  //v2.0.0.8 corrected rule_deal_info[0]->  
        $send_data .= 'Product Limit:            ' . $vtprd_rules_set[$i]->rule_deal_info[0]->discount_rule_cum_max_amt_type . "\n";  //v2.0.0.8 corrected rule_deal_info[0]->
        $send_data .= 'Product Limit Count:      ' . $vtprd_rules_set[$i]->rule_deal_info[0]->discount_rule_cum_max_amt_count . "\n \n";   //v2.0.0.8 corrected rule_deal_info[0]->

        $send_data .= ' DISCOUNTS WORKING TOGETHER WITH'  . "\n";        
        $send_data .= 'Other Rule Discounts:     ' . $vtprd_rules_set[$i]->cumulativeRulePricing  . "\n";
        $send_data .= 'Other Rule Priority:      ' . $vtprd_rules_set[$i]->ruleApplicationPriority_num . "\n"; 
        $send_data .= 'Other Coupon Discounts:   ' . $vtprd_rules_set[$i]->cumulativeCouponPricing  . "\n";
        $send_data .= 'Product Sale Pricing:     ' . $vtprd_rules_set[$i]->cumulativeSalePricing  . "\n \n \n";

        $send_data .= '### IMPORTANT SETTINGS'  . "\n";
        $send_data .= 'Unit Price or Coupon:     ' . $vtprd_setup_options['discount_taken_where']  . "\n";
        $send_data .= 'More or Less Discount:    ' . $vtprd_setup_options['give_more_or_less_discount']  . "\n";
        $send_data .= 'Unit Price Crossout:      ' . $vtprd_setup_options['show_unit_price_cart_discount_crossout']  . "\n";
        $send_data .= 'Test Debugging Mode:      ' . $vtprd_setup_options['debugging_mode_on']  . "\n \n \n";

        //v2.0.0.1 begin
        if (defined('VTPRD_PRO_DIRNAME')) {
          $vtprd_license_options = get_option('vtprd_license_options');        
          $send_data .= '### Begin PRO Licensing Info ###' . "\n\n";
        	$send_data .= 'Home URL:                 ' . $vtprd_license_options['url'] . "\n";
          $send_data .= 'Status:                   ' . $vtprd_license_options['status'] . "\n";
          $send_data .= 'State:                    ' . $vtprd_license_options['state'] . "\n";
          $send_data .= 'Message:                  ' . $vtprd_license_options['msg'] . "\n";
        	$send_data .= 'Key:                      ' . $vtprd_license_options['key'] . "\n";
          $send_data .= 'Email:                    ' . $vtprd_license_options['email'] . "\n";
          $send_data .= 'Activation Type:          ' . $vtprd_license_options['prod_or_test'] . "\n \n \n";        
        }
        //v2.0.0.1 end
        
   return $send_data;                          
  }
     
  //*********************************************
  //v2.0.0 new function
  //********************************************* 
  function vtprd_report_outmoded_incl_excl_products() { 
      global $wpdb, $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set, $vtprd_rule_display_framework ;
      
      //error_log( print_r(  'Function begin vtprd_report_outmoded_incl_excl_products', true ) );
      
      $selected = 'selected="selected"';        

      echo '<h1 style="clear:left;float:left;padding-top:50px;color:grey;">Version 2.0 - General Conversion completed Successfully!</h1>';

      
      if (!defined('VTPRD_PRO_VERSION')) {

        echo '<h1 style="clear:left;float:left;padding-top:50px;color:grey;">ALL RULES reported on below have includes/excludes. They will ONLY work when the <br><br>PRO Version 2.0.0 <br><br>is <em>installed</em> and <em>active</em> . </h1>';
      }
           
      $vtprd_v2_conversion_report = get_option( 'vtprd_v2_conversion_report' );
      
      if (($vtprd_v2_conversion_report) &&
          (sizeof($vtprd_v2_conversion_report) <= 0)) {
        echo '<h1 style="clear:left;float:left;margin-top:50px;">No Product Include/Exclude Conversion was necessary for Version 2.0</h1>'; 
        return;     
      }

      //error_log( print_r(  '$prod_id_list', true ) );
      //error_log( var_export($prod_id_list, true ) );

      //Include or Exclude list
      $first_time = true;
      foreach ($vtprd_v2_conversion_report as $conversion_report) {
        $prod_id = $conversion_report['prod_id'];

        if ($conversion_report['details']['includeOrExclude_option'] != 'includeAll') {

          if ($first_time) {
            $first_time = false;
            ?>
            <style type="text/css">
                   /*
                   .includeOrExclude_areaID
                   {margin: 10px 0 15px 20px;
                    padding: 10px;
                    background-color: .F9F9F9;
                    border-color: .CCCCCC;
                    border-radius: 3px 3px 3px 3px;
                    border-style: solid;
                    border-width: 1px;
                    font-size:1.1em; 
                    width:57%;   
                   }
                   */
                   .includeOrExclude_areaID {margin-top:0px;}
                   .includeOrExclude-all {margin-left:20px;}
                   .includeOrExclude-checklist 
                   {margin-left:20px;
                    width: 100%;
                   }
                   /*(inserted via js*/
                   div#vtprd-redirect {
                     background-color: .FFFFFF;
                     padding-bottom: 15px;
                     width: 100%;
                     margin-bottom: 15px; 
                   }
                   div#vtprd-redirect h3 {margin-bottom:10px;}
                   a#vtprd-redirect-anchor {font-weight:bold;padding:10px 0 10px 10px;}
                        
                  .helpImg {
                      height: 12px;
                      margin-left: 3px;
                      width: 12px;
                  }
                  .hideMe {display:none;}
                  .includeOrExclude_area,
                  .includeOrExclude-area-title,
                  .includeOrExclude_areaID,
                  .includeOrExclude-all,
                  .includeOrExclude-checklist,
                  .includeOrExclude-checklist li {
                    clear:left;
                    float:left; 
                    font-size:16px;        
                  } 
                  .includeOrExclude-checklist .inOrEx-li-details {margin-bottom: 12px;}
                  .includeOrExclude_area {
                    border: 1px solid #bbb;
                    padding-right: 3%;
                    margin-top: 15px;
                    margin-bottom: 15px;
                    width: 95%;
                  }
                  .includeOrExclude-area-title {
                    color: black;
                    margin-left: 2%;
                    font-size: 20px;
                  }
                  .includeOrExclude {border-color: green !important;}
                  .includeOrExclude_label {font-size: 18px;margin-top: 0px;margin-left: 15px;color: green;}
                  #upgrade-title-area a {float:none;}
                  .inOrEx-li-detailTitle {
                    padding: 8px 0px;
                    font-size: 18px;
                    color: black;
                  }
                  .inOrEx-li-overtitle {font-weight: 600;margin-top: 20px;}
                  .includeOrExclude_action {
                    border: 1px solid grey;
                    padding: 5px 10px;
                    margin-left: 15px;
                    background-color: white;
                    border-radius: 15px;
                </style>
                <div id="outmoded_incl_excl_products_message_div">
                  <h1 style="color: green;">REPORT: Product Include/Excludes converted</h1>
                  <h4 style="font-size: 16px !important;">These Product Include/Exclude settings were formerly in a box on each Product Page,</h4>
                  <h4 style="font-size: 16px !important;">&nbsp;&nbsp;&nbsp; and have now been transferred to the new include/exclude selections on each rule</h4>
                  <?php if ( (!defined('VTPRD_PRO_VERSION')) &&
                             ($this->vtprd_is_pro_plugin_installed()) ) { ?>
                    <h1 style="color: green;">PRO Conversion process applied to all rules</h1>
                    <h4 style="font-size: 16px !important;">&nbsp;&nbsp;&nbsp;(PRO plugin installed but not currently active)</h4>
                  <?php } ?>
                </div>
                
            <?php
          }
          
          $details = $conversion_report['details'];
          $product = get_post($prod_id);
          if (vtprd_test_for_variations($prod_id)) {
            $product_msg = '&nbsp;&nbsp;&nbsp; <span style="color:gray;font-size:14px;">( including all variations )</span>';
          } else {
            $product_msg = false;
          }
          
          ?>
          
            <div class="includeOrExclude_area">
              <h4 class="includeOrExclude-area-title"><?php echo 'Product ' .$prod_id. ' - &nbsp;&nbsp;<a class="runDataUpd" target="_blank" href="'.VTPRD_ADMIN_URL.'post.php?post= ' .$prod_id. ' &action=edit">' .$product->post_title. '</a>' .$product_msg; ?></h4>                    
              <div class="dropdown includeOrExclude_areaID clear-left">              
                 <span class="dropdown-label includeOrExclude_label"><?php _e(' Original Product Include/Exclude Selection: ', 'vtprd');?>
                  <?php
                   switch ($details['includeOrExclude_option']) {
                      case 'includeList': 
                          $action = 'Allow product   In the Checked Rules below';
                        break;
                      case 'excludeList': 
                          $action = 'Exclude product   From the Checked Rules below';
                        break;
                      case 'excludeAll': 
                          $action = 'Exclude product   From All Rules Forever';
                        break;                   
                   }                            
                  ?>            
                    <span class="includeOrExclude_action"><?php echo $action; ?></span>
                 </span>                               
              </div>
     
              <div class="includeOrExclude-all" class="tabs-panel">
                <ul class="includeOrExclude-checklist" class="categorychecklist form-no-clear">   
                      <?php  vtprd_fill_include_exclude_lists($prod_id,$details)?>  
                </ul>
              </div> 
            </div>
                          
          <?php
        
        }       
      } //end Prod List FOREACH
      

    return;   
  }  
     
  //*********************************************
  //v2.0.0 new function
  //********************************************* 
  function vtprd_print_outmoded_report($outmoded_incl_excl_products_report) {
      $message  =  '<div id="outmoded_incl_excl_products_message_div">';
      $message .=  '&nbsp;&nbsp;<br><br<strong>' .VTPRD_PLUGIN_NAME. '&nbsp;&nbsp;&nbsp;&nbsp;'. __(' PRODUCT UPDATES NEEDED ' , 'vtprd') .'</strong>';
      $message .=  '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>The following products need editing</strong>' ;
      $message .=  '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Each of the listed products have a Pricing Deals include/exclude option selected.' ;
      $message .=  '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ** &nbsp; All include/exclude options <em>are now part of each rule itself</em> &nbsp;&nbsp; **' ;
      $message .=  '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong style="font-size: 24px;">Please click each Product listed below, and follow the update instructions</strong>' ;
      $message .=  '</div>'; 
      //if manual updates still pending, produce the report
      $message .=  '<div id="outmoded_incl_excl_products_report_div">';
      $message .=  $outmoded_incl_excl_products_report;
      $message .=  '</div>'; 
      
      echo $message;
            
    return;  
  }
  
    //***************************
    //v2.0.0  new function
    //***************************
   function vtprd_v2point0_create_tables(){ 
 
      $vtprd_data_update_options = get_option('vtprd_data_update_options');
      if ( (isset ($vtprd_data_update_options['optional_updates'])) &&
           (isset ($vtprd_data_update_options['optional_updates']['2.0.0 Create Tables'])) &&
           ($vtprd_data_update_options['optional_updates']['2.0.0 Create Tables'] === true)) { 
        echo __('Table Update already done!' , 'vtprd'); 
        return;
      } 

      $vtprd_controller = new VTPRD_Controller;
      //there was an error for a couple of generations where the log tables were NOT CREATED , v1.1.7.0 - v1.1.7.1
      //if tables already there, no action is taken. 
      $vtprd_controller->vtprd_create_discount_log_tables();

      $vtprd_data_update_options['optional_updates']['2.0.0 Create Tables'] = true;
      update_option('vtprd_data_update_options',$vtprd_data_update_options);       
      echo __('Table Update Complete!' , 'vtprd');
      return;
   }  

    //***************************
    //v2.0.0  new function
    //***************************
   function vtprd_v2point0_alter_table_column(){ 
 
      $vtprd_data_update_options = get_option('vtprd_data_update_options');
      if ( (isset ($vtprd_data_update_options['optional_updates'])) &&
           (isset ($vtprd_data_update_options['optional_updates']['2.0.0 Alter Column'])) &&
           ($vtprd_data_update_options['optional_updates']['2.0.0 Alter Column'] === true)) { 
        echo __('Table Update already done!' , 'vtprd'); 
        return;
      } 

      global $wpdb;
		  $wpdb->query( "ALTER TABLE `".VTPRD_PURCHASE_LOG."`  CHANGE `ruleset_object` `ruleset_object` LONGTEXT '';" );
      $wpdb->query( "ALTER TABLE `".VTPRD_PURCHASE_LOG."`  CHANGE `cart_object` `cart_object` LONGTEXT '';" );

      $vtprd_data_update_options['optional_updates']['2.0.0 Alter Column'] = true;
      update_option('vtprd_data_update_options',$vtprd_data_update_options);       
      echo __('Table Update Complete!' , 'vtprd');
      return;
   }


    //***************************
    //v1.2.0  new function
    //***************************
	  function vtprd_is_pro_plugin_installed() {
     
    // Check if get_plugins() function exists. This is required on the front end of the
    // site, since it is in a file that is normally only loaded in the admin.
    if ( ! function_exists( 'get_plugins' ) ) {
    	require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();

    foreach ($all_plugins as $key => $data) { 
      if ($key == VTPRD_PRO_PLUGIN_FOLDER.'/'.VTPRD_PRO_PLUGIN_FILE) {    
        return true;      
      } 
    } 
    
    return false;  
 
  }

function vtprd_show_faq_page_cntl() {
}
/**
 * Renders a simple page to display for the menu item added above.
 */
function vtprd_setup_options_cntl() {
  //add help tab to this screen...
  //$vtprd_backbone->vtprd_add_help_tab ();
    $content = '<br><a  href="' . VTPRD_DOCUMENTATION_PATH . '"  title="Access Plugin Documentation">Access Plugin Documentation</a>';
    $screen = get_current_screen();
    $screen->add_help_tab( array( 
       'id' => 'vtprd-help-options',            //unique id for the tab
       'title' => 'Pricing Deals Settings Help',      //unique visible title for the tab
       'content' => $content  //actual help text
      ) );
      
    //v2.0.2.0 begin 
    $message = get_option( 'vtprd_deferred_setup_admin_notices');
    if ($message) {
      delete_option( 'vtprd_deferred_setup_admin_notices');
      $admin_notices = '<div class="fade is-dismissible vtprd-run-data-upd" 
        style="
              line-height: 19px;
              padding: 11px 15px;
              text-align: left;
              margin: 25px 20px 15px 2px;
              background-color: #FFFFBB;
              border-left: 4px solid #ffba00;
              -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
              font-size:15px;
              box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); " > ' . $message . ' </div>';
      echo $admin_notices; 
    } 
    //v2.0.2.0 end   

    if(!defined('VTPRD_PRO_DIRNAME')) {  
          // **********************************************
        // also disable and grey out options on free version
        // **********************************************
          ?>
          <style type="text/css">
               #use_lifetime_max_limits,
               #vtprd-lifetime-limit-by-user,
               #vtprd-lifetime-limit-by-ip,
               #vtprd-lifetime-limit-by-email,
               #vtprd-lifetime-limit-by-billto-name,
               #vtprd-lifetime-limit-by-billto-addr,
               #vtprd-lifetime-limit-by-shipto-name,             
               #vtprd-lifetime-limit-by-shipto-addr,
               #max_purch_checkout_forms_set,
               #show_error_before_checkout_products_selector,
               #show_error_before_checkout_address_selector,
               #lifetime_purchase_button_error_msg
               {color:#aaa;}  /*grey out unavailable choices*/
          </style>
          <script type="text/javascript">
              jQuery.noConflict();
              jQuery(document).ready(function($) {                                                        
                // To disable 
                $('#use_lifetime_max_limits').attr('disabled', 'disabled');                
                $('#vtprd-lifetime-limit-by-user').attr('disabled', 'disabled');
                $('#vtprd-lifetime-limit-by-ip').attr('disabled', 'disabled');
                $('#vtprd-lifetime-limit-by-email').attr('disabled', 'disabled');
                $('#vtprd-lifetime-limit-by-billto-name').attr('disabled', 'disabled');             
                $('#vtprd-lifetime-limit-by-billto-addr').attr('disabled', 'disabled');
                $('#vtprd-lifetime-limit-by-shipto-name').attr('disabled', 'disabled');
                $('#vtprd-lifetime-limit-by-shipto-addr').attr('disabled', 'disabled');
               /* Can't use the disable - it clears out the default value on these fields!!              
                $('#max_purch_checkout_forms_set').attr('disabled', 'disabled');
                $('#show_error_before_checkout_products_selector').attr('disabled', 'disabled');
                $('#show_error_before_checkout_address_selector').attr('disabled', 'disabled');
                $('#lifetime_purchase_button_error_msg').attr('disabled', 'disabled');   */     
              }); //end ready function 
          </script>
    <?php } ?>        
 
  
	<div class="wrap">                                                                        
		<div id="icon-themes" class="icon32"></div>
    
		<h2>
      <?php 
        if(defined('VTPRD_PRO_DIRNAME')) { 
          esc_attr_e('Pricing Deals Pro Options', 'vtprd'); 
        } else {
          esc_attr_e('Pricing Deals Settings', 'vtprd'); 
        }    
      ?>    
    </h2>
    
		<?php settings_errors(); ?>
    
    <?php 
    /*if ( isset( $_GET['settings-updated'] ) ) {
         echo "<div class='updated'><p>Theme settings updated successfully.</p></div>";
    } */
    ?>
		
		<form method="post" action="options.php">
			<?php
          //WP functions to execute the registered settings!
					settings_fields( 'vtprd_setup_options_group' );     //activates the field settings setup below
					do_settings_sections( 'vtprd_setup_options_page' );   //activates the section settings setup below 
			?>		
      			
      <div id="floating-buttons" class="show-buttons">
        <?php	submit_button(); ?>       			     
        <input name="vtprd_setup_options[options-reset]"      type="submit" class="button-secondary"  value="<?php esc_attr_e('Reset to Defaults', 'vtprd'); ?>" />
        <a class="button-secondary" target="_blank" href="https://www.varktech.com/documentation/pricing-deals/settings"><?php _e('Help!', 'vtprd');?></a> 
      </div>
      
      <span id="vtprd-system-buttons-anchor"></span>  
       <p id="system-buttons">
          <h3><?php esc_attr_e('System Repair and Delete Buttons', 'vtprd'); ?></h3>

          <h4 class="system-buttons-h4"><?php esc_attr_e('Repair reknits the Rules Custom Post Type with the Pricing Deal rules option array, if out of sync.', 'vtprd'); ?></h4>        
          <input id="repair-button"       name="vtprd_setup_options[rules-repair]"    type="submit" class="nuke_buttons button-fourth"     value="<?php esc_attr_e('Repair Rules Structures', 'vtprd'); ?>" /> 
          <h4 class="system-buttons-h4"><?php esc_attr_e('Nuke Rules deletes all Pricing Deals Rules.', 'vtprd'); ?></h4>
          <input id="nuke-rules-button"   name="vtprd_setup_options[rules-nuke]"      type="submit" class="nuke_buttons button-third"      value="<?php esc_attr_e('Nuke all Rules', 'vtprd'); ?>" />
          <h4 class="system-buttons-h4"><?php esc_attr_e('Nuke Rule Cats deletes all Pricing Deals Rule Categories', 'vtprd'); ?></h4>
          <input id="nuke-cats-button"    name="vtprd_setup_options[cats-nuke]"       type="submit" class="nuke_buttons button-fifth"      value="<?php esc_attr_e('Nuke all Rule Cats', 'vtprd'); ?>" />
          <h4 class="system-buttons-h4"><?php esc_attr_e('Nuke Contents of Customer Max Purchase History Tables', 'vtprd'); ?></h4>
          <input id="nuke-hist-button"    name="vtprd_setup_options[hist-nuke]"       type="submit" class="nuke_buttons button-fifth"      value="<?php esc_attr_e('Nuke Contents of Customer Limit (Lifetime) Max Purchase History Tables', 'vtprd'); ?>" />
          <h4 class="system-buttons-h4"><?php esc_attr_e('Nuke Audit Trail Log Tables', 'vtprd'); ?></h4>
          <input id="nuke-log-button"    name="vtprd_setup_options[log-nuke]"         type="submit" class="nuke_buttons button-seventh"    value="<?php esc_attr_e('Nuke Audit Trail Log Tables', 'vtprd'); ?>" />                    
          <h4 class="system-buttons-h4"><?php esc_attr_e('Nuke Session Variables', 'vtprd'); ?></h4>
          <input id="nuke-session-button"    name="vtprd_setup_options[session-nuke]" type="submit" class="nuke_buttons button-sixth"      value="<?php esc_attr_e('Nuke Session Variables', 'vtprd'); ?>" />
          <h4 class="system-buttons-h4"><?php esc_attr_e('Nuke Cart Contents', 'vtprd'); ?></h4>
          <input id="nuke-cart-button"    name="vtprd_setup_options[cart-nuke]"       type="submit" class="nuke_buttons button-second"     value="<?php esc_attr_e('Nuke Cart Contents', 'vtprd'); ?>" />                    
                   
          <?php //v1.1.5 New button, goes to admin_init actuated function below
                //v1.1.6 changed to a standard screen  button  
                //v1.1.6.3 changed to URL with action = force_plugin_updates_check
                global  $vtprd_license_options;  //v1.1.6.3
          ?>
          <h4 class="system-buttons-h4"><?php esc_attr_e('Check for Plugin Updates', 'vtprd'); ?></h4>
          <a  id="nuke-cart-button"  class="nuke_buttons button-second" href="<?php echo VTPRD_ADMIN_URL;?>edit.php?post_type=vtprd-rule&page=vtprd_license_options_page&action=force_plugin_updates_check">Plugin Updates Check</a> <?php //v1.1.8.2 removed home_url ?>
          
          <h4 class="system-buttons-h4"><?php esc_attr_e("Please Don't click here unless instructed!", 'vtprd') //v1.1.6.1 wording changed; ?></h4>
          <input id="nuke-cart-button"    name="vtprd_setup_options[cleanup]"       type="submit" class="nuke_buttons button-second"     value="<?php esc_attr_e("Nuke Important Stuff", 'vtprd'); ?>" />                                        
          <?php //v1.1.5 end  ?>                    
        </p>  
        
        
        <?php //v2.0.2.0 begin ?>
        
        <br><br><br><br>
        <span id="vtprd-delete-plugin-buttons-anchor"></span>  
          
        <h3 style="margin-left: 30px;"><?php esc_attr_e('Set Override to Delete ALL on Uninstall &nbsp; - &nbsp;  Delete ALL Rules and Settings when deleting Plugin', 'vtprd'); ?></h3>      
        <input id="deleteALL-button"       name="vtprd_setup_options[deleteALL_on_uninstall]"    type="submit" class="nuke_buttons button-last"   style="color:red;margin-left:50px;font-size:18px;padding:10px;background-color:white;"  
              value="<?php esc_attr_e('Set "Remove ALL" &nbsp; - override - &nbsp; I don"t want to use the Pricing Deals Plugin any more!! ', 'vtprd'); ?>" /> 

        <br><br><br>
        <h4 class="system-buttons-h4" ><?php esc_attr_e('Unset "Override to Delete ALL on Uninstall" - I didn"t mean to click on the "Remove All" button above !!', 'vtprd')?></h4>
        <input id="nuke-cart-button" style="margin-top:5px;padding:5px;"    name="vtprd_setup_options[unset_deleteALL]"       type="submit" class="nuke_buttons button-second"                                        
              value="<?php esc_attr_e('UNset "Remove ALL"  &nbsp; - resume default behavior -  &nbsp;  Keep all my existing Rules and Settings on plugin delete!! ', 'vtprd'); ?>" />         
        
        <?php //v2.0.2.0 end ?>    
		
        </form>
    
    
    <?php 
    global $vtprd_setup_options, $wp_version;
    $vtprd_setup_options = get_option( 'vtprd_setup_options' );	 
    if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ) {  
      $vtprd_functions = new VTPRD_Functions;
      $your_system_info = $vtprd_functions->vtprd_getSystemMemInfo();
    }
    ?>
    
    <span id="vtprd-plugin-info-anchor"></span>
    <h3 id="system-info-title">Plugin Info</h3>
    
    <h4 class="system-info-subtitle">System Info</h4>
    <span class="system-info">
       <span class="system-info-line"><span class="system-info-label">FREE_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_VERSION;  ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">FREE_LAST_UPDATE_DATE: </span> <span class="system-info-data"><?php echo VTPRD_LAST_UPDATE_DATE;  ?></span></span>
       <span class="system-info-line"><span class="system-info-label">FREE_DIRNAME: </span> <span class="system-info-data"><?php echo VTPRD_DIRNAME;  ?></span></span>
       <span class="system-info-line"><span class="system-info-label">URL: </span> <span class="system-info-data"><?php echo VTPRD_URL;  ?></span></span>
       <span class="system-info-line"><span class="system-info-label">EARLIEST_ALLOWED_WP_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_EARLIEST_ALLOWED_WP_VERSION;  ?></span></span>
       <span class="system-info-line"><span class="system-info-label">WP VERSION: </span> <span class="system-info-data"><?php echo $wp_version; ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">EARLIEST_ALLOWED_PHP_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_EARLIEST_ALLOWED_PHP_VERSION ;?></span> </span>
       <span class="system-info-line"><span class="system-info-label">FREE_PLUGIN_SLUG: </span> <span class="system-info-data"><?php echo VTPRD_PLUGIN_SLUG;  ?></span></span>
     </span> 
    
    <h4 class="system-info-subtitle">Parent Plugin Info</h4>
    <span class="system-info">
       <span class="system-info-line"><span class="system-info-label">PARENT_PLUGIN_NAME: </span> <span class="system-info-data"><?php echo VTPRD_PARENT_PLUGIN_NAME;  ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">EARLIEST_ALLOWED_PARENT_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_EARLIEST_ALLOWED_PARENT_VERSION;  ?></span></span>

       <?php if(defined('WOOCOMMERCE_VERSION') && (VTPRD_PARENT_PLUGIN_NAME == 'WooCommerce')) { ?>
       <span class="system-info-line"><span class="system-info-label">PARENT_VERSION (WOOCOMMERCE): </span> <span class="system-info-data"><?php echo WOOCOMMERCE_VERSION;  ?></span></span>
       <?php } ?>
       
       <?php if(defined('JIGOSHOP_VERSION') && (VTPRD_PARENT_PLUGIN_NAME == 'JigoShop')) {  ?>
       <span class="system-info-line"><span class="system-info-label">PARENT_VERSION (JIGOSHOP): </span> <span class="system-info-data"><?php echo JIGOSHOP_VERSION;  ?></span></span>
       <?php } ?>
       
       <span class="system-info-line"><span class="system-info-label">TESTED_UP_TO_PARENT_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_TESTED_UP_TO_PARENT_VERSION;  ?></span></span>
  
     </span> 

     <?php   if (defined('VTPRD_PRO_DIRNAME')) {  ?> 
      <h4 class="system-info-subtitle">Pro Info</h4>
      <span class="system-info">      
       <span class="system-info-line"><span class="system-info-label">PRO_PLUGIN_NAME: </span> <span class="system-info-data"><?php echo VTPRD_PRO_PLUGIN_NAME; ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">PRO_FREE_PLUGIN_NAME: </span> <span class="system-info-data"><?php echo VTPRD_PRO_FREE_PLUGIN_NAME; ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">PRO_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_PRO_VERSION; ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">PRO_LAST_UPDATE_DATE: </span> <span class="system-info-data"><?php echo VTPRD_PRO_LAST_UPDATE_DATE;  ?></span></span>
       <span class="system-info-line"><span class="system-info-label">PRO_DIRNAME: </span> <span class="system-info-data"><?php echo VTPRD_PRO_DIRNAME;  ?></span></span>
       <span class="system-info-line"><span class="system-info-label">PRO_MINIMUM_REQUIRED_FREE_VERSION: </span> <span class="system-info-data"><?php echo VTPRD_PRO_MINIMUM_REQUIRED_FREE_VERSION;  ?></span></span>

       <span class="system-info-line"><span class="system-info-label">PRO_PLUGIN_SLUG: </span> <span class="system-info-data"><?php echo VTPRD_PLUGIN_SLUG; ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">PRO_REMOTE_VERSION_FILE: </span> <span class="system-info-data"><?php echo VTPRD_PRO_REMOTE_VERSION_FILE; ?></span> </span>
      </span> 
     <?php   }  ?>   

        
     <?php   if ( $vtprd_setup_options['debugging_mode_on'] == 'yes' ){  ?> 
     <h4 class="system-info-subtitle">Debug Info</h4>
      <span class="system-info">                  
       <span class="system-info-line"><span class="system-info-label">PHP VERSION: </span> <span class="system-info-data"><?php echo phpversion(); ?></span> </span>
       <span class="system-info-line"><span class="system-info-label">SYSTEM MEMORY: </span> <span class="system-info-data"><?php echo '<pre>'.print_r( $your_system_info , true).'</pre>' ;  ?></span> </span>
     </span> 
     <?php   }  ?>
  
	</div><!-- /.wrap -->

<?php
} // end vtprd_display  


/* ------------------------------------------------------------------------ *
 * Setting Registration
 * ------------------------------------------------------------------------ */ 

/**
 * Initializes the theme's Discount Reporting Options page by registering the Sections,
 * Fields, and Settings.
 *
 * This function is registered with the 'admin_init' hook.
 */ 

function vtprd_initialize_options() {
  
	// If the theme options don't exist, create them.
	if( false == get_option( 'vtprd_setup_options' ) ) {
		add_option( 'vtprd_setup_options', $this->vtprd_set_default_options() );  //add the option into the table based on the default values in the function.
	} // end if

 
	// First, we register a section. This is necessary since all future options must belong to a 
	add_settings_section(
		'nav_section',			// ID used to identify this section and with which to register options
		'',	// Title to be displayed on the administration page
		array(&$this, 'vtprd_nav_callback'),	// Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);

  //v1.0.9.0 begin  
  //****************************
  //  Discount Action Taken  Area
  //****************************  
	add_settings_section(
		'taken_where_settings_section',			// ID used to identify this section and with which to register options
		__( 'Show Cart Rule Discount', 'vtprd' )	// Title to be displayed on the administration page
    .'&nbsp;&nbsp; => &nbsp;&nbsp;'.
    __( 'in **each Product Unit Price**, or as a **single Automatic Coupon**?', 'vtprd' ),
		array(&$this, 'vtprd_taken_where_options_callback'),	// Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);
   
          
    add_settings_field(	         //opt48
		'discount_taken_where',						// ID used to identify the field throughout the theme
		__( '<em>Show Discount As:</em>', 'vtprd' ) //v2.0.0.5
    .'<br>&nbsp;&nbsp;&nbsp;&nbsp;'.  //v2.0.0.5
		__( 'Unit Price Discount &nbsp;&nbsp; or', 'vtprd' ) 
    .'<br>&nbsp;&nbsp;&nbsp;&nbsp;'.  //v2.0.0.5   
    __( 'Coupon Discount', 'vtprd' ), // The label to the left of the option interface element
		array(&$this, 'vtprd_discount_taken_where_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'taken_where_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Is the discount to be taken by changing the unit price, or applied separately?', 'vtprd' )
		)
	);
 
  //v2.0.0.5 begin 
    add_settings_field(	         //opt58
		'coupon_discount_coupon_name',						// ID used to identify the field throughout the theme
        
    '<span class="unitPriceOrCoupon">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
		 __( 'Coupon Discount - single automatic Coupon', 'vtprd' ) 
     .'<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
     __( ' - Automatic Coupon Name', 'vtprd' ) // The label to the left of the option interface element     
     .'</span>',
     
		array(&$this, 'vtprd_coupon_discount_coupon_name_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'taken_where_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we show the original price with a crossout, followed by the sale price?', 'vtprd' )
		)
	);
  //v2.0.0.5 end 
           
    add_settings_field(	         //opt49
		'give_more_or_less_discount',						// ID used to identify the field throughout the theme
		//__( 'if there is a unit price rounding error, do we give more or less discount?', 'vtprd' )
    '<div class="unitPriceOnly">'.
    __( 'Give More or Less - Unit Price discount', 'vtprd' )   
    .'</div>', 
		array(&$this, 'vtprd_give_more_or_less_discount_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'taken_where_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Is the discount to be taken by changing the unit price, or applied separately?', 'vtprd' )
		)
	);  
  //v1.0.9.0 end

  //v1.0.9.3 begin 
            
    add_settings_field(	         //opt51
		'show_unit_price_cart_discount_crossout',						// ID used to identify the field throughout the theme
		//__( 'if there is a unit price rounding error, do we give more or less discount?', 'vtprd' )
    '<div class="unitPriceOnly">'.
    __( 'Show original Unit Price ', 'vtprd' )
    .'<br>'.
    __( 'Crossed Out in the Cart ', 'vtprd' )    
    .'</div>', 
		array(&$this, 'vtprd_show_unit_price_cart_discount_crossout_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'taken_where_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show the original price crossed out, when a discount is shown in the Unit Price?', 'vtprd' )
		)
	);
            
    add_settings_field(	         //opt52
		'show_unit_price_cart_discount_computation',						// ID used to identify the field throughout the theme
		//__( 'if there is a unit price rounding error, do we give more or less discount?', 'vtprd' )
    '<div class="unitPriceOnly">'.
    __( 'Show Unit Price Discount Computation', 'vtprd' )
    .'</div>', 
		array(&$this, 'vtprd_show_unit_price_cart_discount_computation_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'taken_where_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show the workings of the discount computation, use only during testing', 'vtprd' )
		)
	);
/*             
    add_settings_field(	         //opt53
		'unit_price_cart_savings_message',						// ID used to identify the field throughout the theme
		//__( 'if there is a unit price rounding error, do we give more or less discount?', 'vtprd' )
    '<div class="unitPriceOnly">'.
    __( 'Custom Checkout "Yousave" Message', 'vtprd' )
    .'</div>',  
		array(&$this, 'vtprd_unit_price_cart_savings_message_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'taken_where_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show custom savings message at Checkout', 'vtprd' )
		)
	);
*/       
  //v1.0.9.3 end
  

  //****************************
  //  Checkout Discount Reporting OptionS Area
  //****************************  
	add_settings_section(
		'checkout_settings_section',			// ID used to identify this section and with which to register options
		__( 'Checkout Discount Display (cart rules only)<span id="vtprd-checkout-reporting-anchor"></span>', 'vtprd' ),	// Title to be displayed on the administration page
		array(&$this, 'vtprd_checkout_options_callback'),	// Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);
  
          
    add_settings_field(	         //opt6
		'show_checkout_discount_detail_lines',						// ID used to identify the field throughout the theme  
		__( 'Show Product Discount Detail Lines?', 'vtprd' ), // The label to the left of the option interface element
		array(&$this, 'vtprd_show_checkout_discount_detail_lines_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show checkout discount details line', 'vtprd' )
		)
	);
    
    add_settings_field(	         //opt21
		'show_checkout_discount_details_grouped_by_what',						// ID used to identify the field throughout the theme
		'&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Product Discounts Grouped By?', 'vtprd' ),	// The label to the left of the option interface element
		array(&$this, 'vtprd_show_checkout_discount_details_grouped_by_what_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount details grouped by', 'vtprd' )
		)
	);
      
    add_settings_field(	         //opt23
		'show_checkout_discount_titles_above_details',						// ID used to identify the field throughout the theme
		'&nbsp;&nbsp;&nbsp;&nbsp;'        . __( 'Show Short Checkout Message for', 'vtprd' )
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . __( '"Grouped by Rule within Product"?', 'vtprd' ),
		array(&$this, 'vtprd_show_checkout_discount_titles_above_details_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show Titles above Checkout Discount detail lines?', 'vtprd' )
		)
	);  
   
    add_settings_field(	         //opt24
		'show_checkout_purchases_subtotal',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">'.
    __( 'Show Cart Purchases Subtotal Line?', 'vtprd' )
    .'</span>',    
		array(&$this, 'vtprd_show_checkout_purchases_subtotal_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show Subtotal of cart purchases at checkout', 'vtprd' )
		)
	);
    
    add_settings_field(	         //opt30
		'checkout_credit_subtotal_title',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon"> &nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Cart Purchases Subtotal Line', 'vtprd' )  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __( ' - Label Title', 'vtprd' )
    .'</span>',
    array(&$this, 'vtprd_checkout_credit_subtotal_title_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals title', 'vtprd' )
		)
	);
 
    add_settings_field(	         //opt5
		'show_checkout_discount_total_line',						// ID used to identify the field throughout the theme
		'<span class="unitPriceOrCoupon">'.   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    __( 'Show Discounts Grand Totals Line?', 'vtprd' )
    .'</span>',	
		array(&$this, 'vtprd_show_checkout_discount_total_line_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show Checkout separate discount totals line?', 'vtprd' )
		)
	);
  
    add_settings_field(	         //opt31
		'checkout_credit_total_title',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Discounts Grand Totals Line', 'vtprd' )  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __( ' - Label Title', 'vtprd' )
    .'</span>',		
    array(&$this, 'vtprd_checkout_credit_total_title_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals title', 'vtprd' )
		)
	);  

/*  
    add_settings_field(	         //opt45
		'show_checkout_credit_total_when_coupon_active',						// ID used to identify the field throughout the theme
		__( 'Show Discount Total at Checkout when Coupon Present', 'vtprd' ),	// The label to the left of the option interface element
		array(&$this, 'vtprd_show_checkout_credit_total_when_coupon_active_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show Discount Total at Checkout when Coupon Present', 'vtprd' )
		)
	);
*/
    add_settings_field(	         //opt10
		'checkout_credit_detail_label',						// ID used to identify the field throughout the theme
		__( 'Discount Detail Line - Credit Label', 'vtprd' ) ,	// The label to the left of the option interface element
		array(&$this, 'vtprd_checkout_credit_detail_label_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals line', 'vtprd' )
		)
	);
  
    add_settings_field(	         //opt11
		'checkout_credit_total_label',						// ID used to identify the field throughout the theme
		'<span class="unitPriceOrCoupon">'.  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    __( 'Discount Total Line - Credit Label', 'vtprd' )
    .'</span> ',
		array(&$this, 'vtprd_checkout_credit_total_label_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals line', 'vtprd' )
		)
	);

    add_settings_field(	         //opt43
		'checkout_new_subtotal_line',						// ID used to identify the field throughout the theme
		 '<span class="unitPriceOrCoupon">'.   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
     __( 'Show Products + Discounts', 'vtprd' )
    . '<br>'  . __( ' Grand Total Line', 'vtprd' )
    .'</span>',
		array(&$this, 'vtprd_checkout_new_subtotal_line_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'New checkout subtotal switch', 'vtprd' )
		)
	);  
    
    
    add_settings_field(	         //opt44
		'checkout_new_subtotal_label',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">'.  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    '&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Product + Discount', 'vtprd' )
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __( 'Grand Total Line - Label Title', 'vtprd' )
    .'</span>',		
    array(&$this, 'vtprd_checkout_new_subtotal_label_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'checkout_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'New checkout subtotal label', 'vtprd' )
		)
	);



    
  //****************************     
  //  Cart Widget Discount Reporting OptionS Area
  //****************************      
	add_settings_section(
		'cartWidget_settings_section',			// ID used to identify this section and with which to register options
		__( 'Cart Widget Discount Display (cart rules only)<span id="vtprd-cartWidget-options-anchor"></span>', 'vtprd' )
    .'<span class="unitPriceOnly">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
    __( '(... no options when "Unit Price Discount" is chosen.) </span>', 'vtprd' ),	// Title to be displayed on the administration page
		array(&$this, 'vtprd_cartWidget_options_callback'),	// Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	); 
     
    add_settings_field(	         //opt27
		'show_cartWidget_discount_detail_lines',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">'.   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide      
		__( 'Show Product Discount Detail Lines?', 'vtprd' )
    .'</span>',	// The label to the left of the option interface element
		array(&$this, 'vtprd_show_cartWidget_discount_detail_lines_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show cartWidget discount details line', 'vtprd' )
		)
	);	

    add_settings_field(	         //opt22
		'show_cartWidget_discount_details_grouped_by_what',						// ID used to identify the field throughout the theme
		'<span class="unitPriceOrCoupon">'.   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    '&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Product Discounts Grouped By?', 'vtprd' )
    .'</span>',	// The label to the left of the option interface element
		array(&$this, 'vtprd_show_cartWidget_discount_details_grouped_by_what_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount details grouped by', 'vtprd' )
		)
	);
      
    add_settings_field(	         //opt7
		'show_cartWidget_discount_titles_above_details',						// ID used to identify the field throughout the theme
		'<span class="unitPriceOrCoupon">'.   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    '&nbsp;&nbsp;&nbsp;&nbsp;'        . __( 'Show Short Checkout Message for', 'vtprd' )
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . __( '"Grouped by Rule within Product"?', 'vtprd' )
    .'</span>',   
    array(&$this, 'vtprd_show_cartWidget_discount_titles_above_details_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show Titles above Cart Widget Discount detail lines?', 'vtprd' )
		)
	);
      
    add_settings_field(	         //opt25
		'show_cartWidget_purchases_subtotal',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">' .  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    __( 'Show Cart Purchases Subtotal Line?', 'vtprd' )
    .'</span>',
    array(&$this, 'vtprd_show_cartWidget_purchases_subtotal_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show Subtotal of cart purchases in the Cart Widget', 'vtprd' )
		)
	);
    
    add_settings_field(	         //opt32
		'cartWidget_credit_subtotal_title',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">'.  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    '&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Cart Purchases Subtotal Line', 'vtprd' )
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __( ' - Label Title', 'vtprd' )
    .'</span>',
		array(&$this, 'vtprd_cartWidget_credit_subtotal_title_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals title', 'vtprd' )
		)
	);

    add_settings_field(	         //opt26
		'show_cartWidget_discount_total_line',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">'. //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    __( 'Show Discounts Grand Totals Line?', 'vtprd' )
    .'</span>',	
    array(&$this, 'vtprd_show_cartWidget_discount_total_line_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show cartWidget separate discount totals line?', 'vtprd' )
		)
	);

    add_settings_field(	         //opt33
		'cartWidget_credit_total_title',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">'.  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    '&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Discounts Grand Totals Line', 'vtprd' )
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __( ' - Label Title', 'vtprd' )
    .'</span>',	
		array(&$this, 'vtprd_cartWidget_credit_total_title_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals title', 'vtprd' )
		)
	);
 
         
    add_settings_field(	         //opt28
		'cartWidget_credit_detail_label',						// ID used to identify the field throughout the theme
		'<span class="unitPriceOrCoupon">'.   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    __( 'Discount Detail Line - Credit Label', 'vtprd' )
    .'</span>',	// The label to the left of the option interface element
		array(&$this, 'vtprd_cartWidget_credit_detail_label_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals line', 'vtprd' )
		)
	);
  
    add_settings_field(	         //opt29
		'cartWidget_credit_total_label',						// ID used to identify the field throughout the theme
		'<span class="unitPriceOrCoupon">'.  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    __( 'Discount Total Line - Credit Label', 'vtprd' )
    .'</span>',
		array(&$this, 'vtprd_cartWidget_credit_total_label_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount totals line', 'vtprd' )
		)
	);
  
  
    add_settings_field(	         //opt45
		'cartWidget_new_subtotal_line',						// ID used to identify the field throughout the theme
		 '<span class="unitPriceOrCoupon">'.  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
     __( 'Show Products + Discounts', 'vtprd' )
    . '<br>'  . __( ' Grand Total Line', 'vtprd' )
    .'</span>',
		array(&$this, 'vtprd_cartWidget_new_subtotal_line_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'New cartWidget subtotal switch', 'vtprd' )
		)
	);  
    
    
    add_settings_field(	         //opt46
		'cartWidget_new_subtotal_label',						// ID used to identify the field throughout the theme
    '<span class="unitPriceOrCoupon">&nbsp;&nbsp;&nbsp;&nbsp;'        .__( 'Product + Discount', 'vtprd' )  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
    . '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __( 'Grand Total Line - Label Title', 'vtprd' )
    .'</span>',		
    array(&$this, 'vtprd_cartWidget_new_subtotal_label_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'New cartWidget subtotal label', 'vtprd' )
		)
	);  
/*  
    add_settings_field(	         //opt12
		'cartWidget_html_colspan_value',						// ID used to identify the field throughout the theme
		__( 'HTML Colspan value for Cart Widget Display', 'vtprd' ),	// The label to the left of the option interface element
		array(&$this, 'vtprd_cartWidget_html_colspan_value_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'cartWidget_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'HTML cart widget colspan value', 'vtprd' )
		)
	);
 */ 

 
  //****************************
  //  Discount Catalog Display - Strikethrough
  //****************************

	// First, we register a section. This is necessary since all future options must belong to a 
	add_settings_section(
		'catalog_settings_section',			// ID used to identify this section and with which to register options
		__( 'Catalog Price Display<span id="vtprd-catalog-options-anchor"></span>', 'vtprd' ),	// Title to be displayed on the administration page
		array(&$this, 'vtprd_catalog_options_callback'),	// Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);

 
    add_settings_field(	         //opt47
		'show_catalog_price_crossout',						// ID used to identify the field throughout the theme
		__( 'Show Catalog Discount Price Crossout', 'vtprd' ),	// The label to the left of the option interface element  //v1.0.9.3
		array(&$this, 'vtprd_show_catalog_price_crossout_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'catalog_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we show the original price with a crossout, followed by the sale price?', 'vtprd' )
		)
	);
 
 
    add_settings_field(	         //opt50
		'show_price_suffix',						// ID used to identify the field throughout the theme
		'<br>' . __( 'Discount Price Suffix:', 'vtprd' ),	// The label to the left of the option interface element
		array(&$this, 'vtprd_show_price_suffix_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'catalog_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we show the original price with a crossout, followed by the sale price?', 'vtprd' )
		)
	);
  
  //****************************
  //  Discount Messaging for Theme Area
  //****************************

	// First, we register a section. This is necessary since all future options must belong to a 
	add_settings_section(
		'general_settings_section',			// ID used to identify this section and with which to register options
		__( 'Sell Your Deal Messages - Shown in Theme<span id="vtprd-discount-messaging-anchor"></span>', 'vtprd' ),	// Title to be displayed on the administration page
		array(&$this, 'vtprd_general_options_callback'),	// Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);

 
    add_settings_field(	         //opt34
		'show_yousave_one_some_msg',						// ID used to identify the field throughout the theme
		__( 'Show Catalog Discount Additional Message', 'vtprd' ),	// The label to the left of the option interface element
		array(&$this, 'vtprd_show_yousave_one_some_msg_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'general_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount details grouped by', 'vtprd' )
		)
	);
 
      
  //****************************
  //  PROCESSING OPTIONS Area
  //****************************
  
  	add_settings_section(
		'processing_settings_section',			// ID used to identify this section and with which to register options
		__( 'Processing Options<span id="vtprd-processing-options-anchor"></span>', 'vtprd' ),// Title to be displayed on the administration page
		array(&$this, 'vtprd_processing_options_callback'), // Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);

 	  		
	add_settings_field(	           //opt54
		'wholesale_products_display',						// ID used to identify the field throughout the theme
		//__( 'Wholesale Products Display Options', 'vtprd' ),		// The label to the left of the option interface element   
		__( 'Catalog Products Display', 'vtprd' )
    .'<br><br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em>'.
    __( 'Wholesale/Retail Product Visibility', 'vtprd' ) 
   .'<br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<span class="vtprd-pro-only-msg">( - available in the Pro Version - )</span>'
    .'</em>',			// The label to the left of the option interface element         
		array(&$this, 'vtprd_wholesale_products_display_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Wholesale Products Display Options', 'vtprd' )
		)
	);	  

 	//v1.1.1  		
	add_settings_field(	           //opt55
		'wholesale_products_price_display',						// ID used to identify the field throughout the theme
		//__( 'Wholesale Products Pricing Display Options', 'vtprd' ),		// The label to the left of the option interface element  
		__( 'Catalog Products Purchasability Display', 'vtprd' )
    .'<br><br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em>'.
    __( 'Wholesale/Retail Product Purchasability', 'vtprd' ) 
       .'<br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<span class="vtprd-pro-only-msg">( - available in the Pro Version - )</span>'
    .'</em>',			// The label to the left of the option interface element                 
		array(&$this, 'vtprd_wholesale_products_price_display_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Wholesale Products Display Options', 'vtprd' )
		)
	);

 	//v1.1.7.2		
	add_settings_field(	           //opt57
		'limit_cart_discounts',						// ID used to identify the field throughout the theme
		__( 'Cart Cross-Rule Limits', 'vtprd' )
    .'<br><br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em>'.
    __( 'Limit Cart Rule Discounting to a Single Rule', 'vtprd' ) 
    .'<br><br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em>'.
    __( ' or turn off either WOO Coupons or Cart Discounts', 'vtprd' )     
       .'<br>'.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.'<span class="vtprd-pro-only-msg">( - available in the Pro Version - )</span>'
    .'</em>',			// The label to the left of the option interface element                 
		array(&$this, 'vtprd_limit_cart_discounts_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Cart Cross-Rule Limits', 'vtprd' )
		)
	);
   
   
 /*	  	No Longer USED	
	add_settings_field(	           //opt47
		'bogo_auto_add_the_same_product_type',						// ID used to identify the field throughout the theme
		__( 'BOGO Behavior for Auto Add of Same Product', 'vtprd' ),		// The label to the left of the option interface element        
		array(&$this, 'vtprd_bogo_auto_add_the_same_product_type_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'BOGO Behavior for Auto Add of Same Product', 'vtprd' )
		)
	);	  
*/  


    //v2.0.0.5 begin
    add_settings_field(	         //opt59
		'show_woo_coupon_cart_totals_00_line',						// ID used to identify the field throughout the theme
		__( 'If rule Activated by Coupon Code', 'vtprd' ) 
    .'<br>&nbsp;&nbsp;&nbsp; - &nbsp;'.
    __( 'show the &nbsp;&nbsp; "coupon code ... 00" &nbsp;&nbsp; Cart totals line' ), // The label to the left of the option interface element
		array(&$this, 'vtprd_show_woo_coupon_cart_totals_00_line_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Show discount details grouped by', 'vtprd' )
		)
	);
    //v2.0.0.5 end
    
    add_settings_field(	         //opt3
		'discount_floor_pct_per_single_item',						// ID used to identify the field throughout the theme
		__( 'Product Discount Max % Override', 'vtprd' ),							// The label to the left of the option interface element
		array(&$this, 'vtprd_discount_floor_pct_per_single_item_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Product Discount max percentage', 'vtprd' )
		)
	);
 
    add_settings_field(	         //opt4
		'discount_floor_pct_msg',						// ID used to identify the field throughout the theme
		__( 'Product Discount Max % Override Message', 'vtprd' ),							// The label to the left of the option interface element
    array(&$this, 'vtprd_discount_floor_pct_msg_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Product Discount max percentage message', 'vtprd' )
		)
	);               
    
    add_settings_field(	        //opt19
		'use_plugin_front_end_css',						// ID used to identify the field throughout the theme
		__( 'Use the Plugin CSS file for Discount Display?', 'vtprd' ),			// The label to the left of the option interface element
		array(&$this, 'vtprd_use_plugin_front_end_css_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we use the plugin front end css at all?', 'vtprd' )
		)
	);      
     
    add_settings_field(	        //opt9
		'custom_checkout_css',						// ID used to identify the field throughout the theme
		__( 'Custom CSS overrides or additions', 'vtprd' )
    .'<br>'.
    __( 'to the end of the Plugin CSS File', 'vtprd' ),			// The label to the left of the option interface element
		array(&$this, 'vtprd_custom_checkout_css_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'processing_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we apply multiple rules to a given product?', 'vtprd' )
		)
	);     
    
  //****************************
  //  LIFETIME RULE OPTIONS Area
  //****************************    
  //mwn04142014 ==> change to msg "vtprd-lifetime-options-free-msg"
    add_settings_section(
		'lifetime_rule_settings_section',			// ID used to identify this section and with which to register options
		__( 'Customer Rule Limit - Options', 'vtprd' ) . '<span id="vtprd-lifetime-options-anchor"></span>' . '<span id="vtprd-lifetime-options-free-msg">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __( '(These options are available in the Pro Version)', 'vtprd' ) .'</span>',// Title to be displayed on the administration page
		array(&$this, 'vtprd_lifetime_rule_options_callback'), // Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);

	add_settings_field(	           //opt2
		'use_lifetime_max_limits',						// ID used to identify the field throughout the theme
		__( 'Use Customer Rule Limits', 'vtprd' ) .'<br><em>&nbsp;&nbsp;&nbsp;&nbsp;'. __( '- Store-Wide Master Switch', 'vtprd' ) .'</em>',							// The label to the left of the option interface element    
		array(&$this, 'vtprd_use_lifetime_max_limits_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Store-Wide switch for Customer Rule Limits.', 'vtprd' )
		)
	);
  
    //v2.0.2.0 begin
      add_settings_field(	        //opt64
		'max_purch_rule_lifetime_limit_by_user',						// ID used to identify the field throughout the theme
		__( 'Check Logged-in User against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by USER', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_user_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we Check Customer against Rule Purchase History, by Logged-in USER?', 'vtprd' )
		)
	); 
    //v2.0.2.0 end  
    
      add_settings_field(	        //opt14
		'max_purch_rule_lifetime_limit_by_email',						// ID used to identify the field throughout the theme
		__( 'Check Customer against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by Email', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_email_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we Check Customer against Rule Purchase History, by Email?', 'vtprd' )
		)
	);

          add_settings_field(	        //opt15
		'max_purch_rule_lifetime_limit_by_billto_name',						// ID used to identify the field throughout the theme
		__( 'Check Customer against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by BillTo Name', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_billto_name_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we Check Customer against Rule Purchase History, by BillTo Name?', 'vtprd' )
		)
	);

          add_settings_field(	        //opt16
		'max_purch_rule_lifetime_limit_by_billto_addr',						// ID u<br>&nbsp; sed to identify the field throughout the theme
		__( 'Check Customer against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by BillTo Address', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_billto_addr_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Check Customer against Rule Purchase History, by BillTo Address?', 'vtprd' )
		)
	);

          add_settings_field(	        //opt17
		'max_purch_rule_lifetime_limit_by_shipto_name',						// ID used to identify the field throughout the theme
		__( 'Check Customer against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by ShipTo Name', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_shipto_name_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we Check Customer against Rule Purchase History, by ShipTo Name?', 'vtprd' )
		)
	);

          add_settings_field(	        //opt18
		'max_purch_rule_lifetime_limit_by_shipto_addr',						// ID u<br>&nbsp; sed to identify the field throughout the theme
		__( 'Check Customer against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by ShipTo Address', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_shipto_addr_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Check Customer against Rule Purchase History, by ShipTo Address?', 'vtprd' )
		)
	);
   
   //v2.0.2.0 by IP move HERE
      add_settings_field(	        //opt13
		'max_purch_rule_lifetime_limit_by_ip',						// ID used to identify the field throughout the theme
		__( 'Check Customer against Rule Purchase History,', 'vtprd' ) .'<br>&nbsp;&nbsp;<i>'. __( 'by IP', 'vtprd' ) .'</i>',			// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_limit_by_ip_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Do we Check Customer against Rule Purchase History, by IP?', 'vtprd' )
		)
	);   
         
    
/* 
            add_settings_field(	        //opt38
		'max_purch_checkout_forms_set',						// ID used to identify the field throughout the theme
		__( 'Primary Checkout Form Set => default set to "0"', 'vtprd' ),			// The label to the left of the option interface element
		array(&$this, 'vtprd_checkout_forms_set_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Primary Checkout Form Set', 'vtprd' )
		)
	);  
*/
    add_settings_field(	         //opt39
		'show_error_before_checkout_products_selector',						// ID used to identify the field throughout the theme
		__( 'Show Error Messages Just Before Checkout', 'vtprd' ) .'<br>'. __( 'Products List - HTML Selector ', 'vtprd' ) .'<em>'. __( '(see => "more info")', 'vtprd' ) .'</em>',							// The label to the left of the option interface element
		array(&$this, 'vtprd_before_checkout_products_selector_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'For the Product area, Supplies the ID or Class HTML selector this message appears before', 'vtprd' )
		)
	);

    add_settings_field(	         //opt40
		'show_error_before_checkout_address_selector',						// ID used to identify the field throughout the theme
		__( 'Show Error Messages Just Before Checkout', 'vtprd' ) .'<br>'. __( 'Address List - HTML Selector ', 'vtprd' )  .'<em>'. __( '(see => "more info")', 'vtprd' ) .'</em>',		// The label to the left of the option interface element
		array(&$this, 'vtprd_before_checkout_address_selector_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'For the Address area, Supplies the ID or Class HTML selector this message appears before', 'vtprd' )
		)
	);

    add_settings_field(	         //opt41
		'lifetime_purchase_button_error_msg',						// ID used to identify the field throughout the theme
		__( 'Customer Rule Limit - Button Error Message', 'vtprd' ),							// The label to the left of the option interface element
		array(&$this, 'vtprd_lifetime_purchase_button_error_msg_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'lifetime_rule_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Customer Rule Limit - Checkout Button Limit Reached Error Message', 'vtprd' )
		)
	);
         
  //****************************
  //  SYSTEM AND DEBUG OPTIONS Area
  //****************************
  
  	add_settings_section(
		'internals_settings_section',			// ID used to identify this section and with which to register options
		__( 'System and Debug Options<span id="vtprd-system-options-anchor"></span>', 'vtprd' ),		// Title to be displayed on the administration page
		array(&$this, 'vtprd_internals_options_callback'), // Callback used to render the description of the section
		'vtprd_setup_options_page'		// Page on which to add this section of options
	);
	  		
	add_settings_field(	           //opt20
		'use_this_timeZone',						// ID used to identify the field throughout the theme
		__( 'Select ', 'vtprd' ) .'<em>'. __( 'Store ', 'vtprd' ) .'</em>'.  __( 'Time Zone', 'vtprd' ),		// The label to the left of the option interface element        
		array(&$this, 'vtprd_use_this_timeZone_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'internals_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Select Store Time Zone', 'vtprd' )
		)
	);
/*		
	add_settings_field(	           //opt1
		'register_under_tools_menu',						// ID used to identify the field throughout the theme
		__( 'Pricing Deals Backend Admin Menu Screens Location', 'vtprd' ),		// The label to the left of the option interface element        
		array(&$this, 'vtprd_register_under_tools_menu_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'internals_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			 __( 'Pricing Deals Admin Menu Location', 'vtprd' )
		)
	);
*/	
    add_settings_field(	        //opt8
		'debugging_mode_on',						// ID used to identify the field throughout the theme
		__( 'Test Debugging Mode Turned On', 'vtprd' ) .'<br>'. __( '(Use Only during testing)', 'vtprd' ),							// The label to the left of the option interface element
		array(&$this, 'vtprd_debugging_mode_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'internals_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Show any built-in debug info for Rule processing.', 'vtprd' )
		)
	);
    //v1.1.5   
    
    //v2.0.2.0 commented out, to be used later               
    /*
    add_settings_field(	        //opt56
		'allow_license_info_reset',						// ID used to identify the field throughout the theme
		__( 'Allow Reset', 'vtprd' ),							// The label to the left of the option interface element
		array(&$this, 'vtprd_allow_license_info_reset_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'internals_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Show any built-in debug info for Rule processing.', 'vtprd' )
		)
	);
    */ 

    //v2.0.0.8
    add_settings_field(	        //opt60
		'remove_all_extra_js_from_rule_page',						// ID used to identify the field throughout the theme
		__( 'Remove Extra JS from Rule Page', 'vtprd' ),							// The label to the left of the option interface element
		array(&$this, 'vtprd_remove_extra_js_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'internals_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Show any built-in debug info for Rule processing.', 'vtprd' )
		)
	);
  
    //v2.0.0.8
    add_settings_field(	        //opt61
		'remove_all_extra_css_from_rule_page',						// ID used to identify the field throughout the theme
		__( 'Remove Extra CSS from Rule Page', 'vtprd' ),							// The label to the left of the option interface element
		array(&$this, 'vtprd_remove_extra_css_callback'), // The name of the function responsible for rendering the option interface
		'vtprd_setup_options_page',	// The page on which this option will be displayed
		'internals_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Show any built-in debug info for Rule processing.', 'vtprd' )
		)
	);

 
	
	// Finally, we register the fields with WordPress
	register_setting(
		'vtprd_setup_options_group',
		'vtprd_setup_options' ,
    array(&$this, 'vtprd_validate_setup_input')
	);
	
} // end vtprd_initialize_options

 
  
   
  //****************************
  //  DEFAULT OPTIONS INITIALIZATION
  //****************************
function vtprd_set_default_options() {
      /*
      //v1.0.7.3 changed to always be YES!!
      if(defined('VTPRD_PRO_DIRNAME')) { 
        $use_lifetime_max_limits_default = 'yes';
      } else {
        $use_lifetime_max_limits_default = 'no';
      } 
      */     
     $options = array(           
          'register_under_tools_menu'=> 'no',  //opt1         
          'use_lifetime_max_limits' => 'yes',    //opt2           //v1.0.7.3 changed to always be YES, as we always check for PRO anyway when using the switch
          'discount_floor_pct_per_single_item' => '100', //opt3  STORE-WIDE Discount max percent  //v1.1.0.6 changed to 100% to prevent going negative!!!!!!
          'discount_floor_pct_msg' => 'System Max xx% Discount reached.',  //opt4
          'show_checkout_discount_total_line' => 'yes', //opt5  yes/no => show total of discounts AFTER products displayed
          'show_checkout_discount_detail_lines' => 'yes', //opt6  yes/no => show detail of discounts AFTER products displayed
          'show_cartWidget_discount_titles_above_details'  => 'yes',  //opt7
          'debugging_mode_on' => 'no',                    //opt8
          'custom_checkout_css'  => '',  //opt9
          
          'checkout_credit_detail_label' => '-', //opt10  TEXT field, suggest '-', 'CR', 'cr' ==>> their choice!!!!!!!!!!!!!!!
          'checkout_credit_total_label' => '-', //opt11  TEXT field, suggest '-', 'CR', 'cr' ==>> their choice!!!!!!!!!!!!!!!
          'checkout_html_colspan_value' => '3', //opt42
          'cartWidget_html_colspan_value' => '5', //opt12
          'max_purch_rule_lifetime_limit_by_ip' => 'no',  //opt13   //v2.0.2.0 changed to NO, with the addtion of USER below
          'max_purch_rule_lifetime_limit_by_email' => 'yes',  //opt14
          'max_purch_rule_lifetime_limit_by_billto_name' => 'yes',  //opt15
          'max_purch_rule_lifetime_limit_by_billto_addr' => 'yes',  //opt16
          'max_purch_rule_lifetime_limit_by_shipto_name' => 'yes',  //opt17
          'max_purch_rule_lifetime_limit_by_shipto_addr' => 'yes',   //opt18                    
          'use_plugin_front_end_css'  => 'yes',  //opt19  allows the user to shut off msg css and put their own into their own theme
          'use_this_timeZone'  => 'keep',  //opt20 set store timezone relative to gmt 
//          'nanosecond_delay_for_add_to_cart_processing' => '1000', //opt46 "1000" = 1 second
          'bogo_auto_add_the_same_product_type' => 'allAdds', //opt47  values: allAdds / fitInto
          'show_checkout_discount_details_grouped_by_what'  => 'rule',  //opt21
          'show_cartWidget_discount_details_grouped_by_what'  => 'rule',  //opt22 
          'show_checkout_discount_titles_above_details'  => 'yes',  //opt23 
          'show_checkout_purchases_subtotal'  => 'withDiscounts',  //opt24  
          'show_cartWidget_purchases_subtotal'  => 'none',  //opt25 
          'show_cartWidget_discount_total_line' => 'yes', //opt26  yes/no => show total of discounts AFTER products displayed
          'show_cartWidget_discount_detail_lines' => 'yes', //opt27  yes/no => show detail of discounts AFTER products displayed
          'cartWidget_credit_detail_label' => '-', //opt28  TEXT field, suggest '-', 'CR', 'cr' ==>> their choice!!!!!!!!!!!!!!!
          'cartWidget_credit_total_label' => '-', //opt29  TEXT field, suggest '-', 'CR', 'cr' ==>> their choice!!!!!!!!!!!!!!!      
          'checkout_credit_subtotal_title' => 'Subtotal - Cart Purchases:', //opt30 
          'checkout_credit_total_title' => 'Cart Discount Total:', //opt31
          'show_checkout_credit_total_when_coupon_active' => 'yes', //opt45
          'cartWidget_credit_subtotal_title' => 'Products:', //opt32 
          'cartWidget_credit_total_title' => 'Discounts:', //opt33 
          'show_yousave_one_some_msg' => 'yes', //opt34 
          'show_old_price' => 'docOnly', //opt35 not used as switching, just documentation!!
          'show_rule_msgs' => 'docOnly', //opt36 not used as switching, just documentation!!
          'discount_purchase_log' => 'yes', //opt37
          'max_purch_checkout_forms_set' => '0',  //opt38
          'show_error_before_checkout_products_selector' => VTPRD_CHECKOUT_PRODUCTS_SELECTOR_BY_PARENT,  //opt39
          'show_error_before_checkout_address_selector'  => VTPRD_CHECKOUT_ADDRESS_SELECTOR_BY_PARENT,  //opt40
          'lifetime_purchase_button_error_msg' => VTPRD_CHECKOUT_BUTTON_ERROR_MSG_DEFAULT,  //opt41
          'checkout_new_subtotal_line' => 'yes', //opt43  
          'checkout_new_subtotal_label' => 'Subtotal with Discount:', //opt44
          'cartWidget_new_subtotal_line' => 'yes', //opt45  
          'cartWidget_new_subtotal_label' => 'Subtotal with Discount:', //opt46
          'show_catalog_price_crossout' => 'yes', //opt47  //v1.0.9.3 changed to 'yes' for default
          'discount_taken_where'  => 'discountUnitPrice', //opt48  "discountUnitPrice" = 'apply discount directly to product unit cost'/"discountCoupon" = 'apply discount separately, show in coupon'  //v1.0.9.0
          'give_more_or_less_discount' => 'more',  //opt49  more/less
          'show_price_suffix' => '',  //opt50
          'show_unit_price_cart_discount_crossout' => 'yes',  //opt51
          'show_unit_price_cart_discount_computation' => 'no',  //opt52
          'unit_price_cart_savings_message' => __('You Saved ', 'vtprd') .'{cart_save_amount}',  //opt53  shown in cartpage, checkout and thankyou
          'wholesale_products_display' => '',  //opt54  'noAction', 'respective' = show retail to retail, wholesale to wholesale   'wholesaleAll = show retail to retail, wholesale sees all   'normal'
          'wholesale_products_price_display' => '',  //opt55  //v1.1.1
          'allow_license_info_reset' => 'no',  //opt56  //v1.1.5
          'current_pro_version' => '',  //internal, not an option  //v1.1.6.3
          'limit_cart_discounts' => '',  //opt57  //v1.1.7.2 
          'coupon_discount_coupon_name' => '',  //opt58  //v2.0.0.5
          'show_woo_coupon_cart_totals_00_line' => 'no',  //opt59  //v2.0.0.5  If you use a WOO coupon to activate a deal, and want to prevent the 'coupon... 00'  line from showing:
          'remove_all_extra_js_from_rule_page' => 'yes',  //opt60  //v2.0.0.8 - v2.0.0.9 chged to 'yes'
          'remove_all_extra_css_from_rule_page' => 'yes',  //opt61  //v2.0.0.8 - v2.0.0.9 chged to 'yes'
          'max_purch_rule_lifetime_limit_by_user' => 'yes'  //opt62  v2.0.2.0 NEW, set to 'yes'
     );
     return $options;
}

function vtprd_processing_options_callback () {
    ?>
    <h4 id="vtprd-processing-options"><?php esc_attr_e('These options apply to general discount processing.', 'vtprd'); ?></h4>
    
    <?php //v2.0.0.2 begin 
      global $wp_version;
      if ( version_compare( $wp_version, '4.8', '>=' ) ) {
    ?>
      <h4 style="color:black !important;">WordPress 5.0+ - "Product Wholesale Checkbox on Product Page" may be <strong>only available</strong> using the 
       <a target="_blank" href="https://wordpress.org/plugins/classic-editor/"><?php _e('Classic Editor Plugin', 'vtprd');?></a>
       to access the Woocommerce Product wp-admin page 
       <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
       (all other aspects of Woo Product can be accessed in the editor of your choice). 
      </h4>
    <?php 
      }  //v2.0.0.2 end ?>
    
    <?php                                                                                                                                                                                      
}

   
function vtprd_lifetime_rule_options_callback () {
    //v2.0.2.0 edited to remove comments...
    ?>
    <h4 id="vtprd-lifetime-options"><?php esc_attr_e('Customer Rule Limit Options set Store-Wide switches regarding whether and at what information level Customer Rule Limits are applied.', 'vtprd'); ?></h4>     
    <?php                                                                                                                                                                                      
}

function vtprd_nav_callback() {                                      

    ?>

    <?php //BANNER AND BUTTON AREA ?>
    <img id="pricing-deals-img-preload" alt="" src="<?php echo VTPRD_URL;?>/admin/images/upgrade-bkgrnd-banner.jpg" />
 		<div id="upgrade-title-area">
      <a  href=" <?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT ; ?> "  title="Purchase Pro">
      <img id="pricing-deals-img" alt="help" height="40px" width="40px" src="<?php echo VTPRD_URL;?>/admin/images/sale-circle.png" />
      </a>
      <h2><?php _e('Pricing Deals', 'vtprd'); ?>
          <?php
            if(defined('VTPRD_PRO_DIRNAME')) { 
               _e(' Pro', 'vtprd');
            }           
          ?> 
      </h2>
      
      <?php if(!defined('VTPRD_PRO_DIRNAME')) {  ?> 
          <span class="group-power-msg"><strong><?php _e('Get Group Power', 'vtprd'); ?></strong>
              &nbsp;-&nbsp; 
            <?php _e('Apply rules to ', 'vtprd'); ?>
            <em>
            <?php _e('any group you can think of, and More!', 'vtprd'); ?>
            </em>
          </span> 
          <span class="buy-button-area">
            <a href="<?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT; ?>" class="buy-button">
                <span class="buy-button-label" style="width: 95%;"><?php _e('Get Pricing Deals Pro', 'vtprd'); ?></span>
            </a>
          </span> 
      <?php }  ?>
          
    </div>  
           
  
    <?php 
    $options = get_option( 'vtprd_setup_options' );	
    
    /*  scaring the punters
    if ( $options['use_this_timeZone'] == 'none') {  ...
    */
    ?>

    
         <div id="vtprd-options-menu">        
              <ul>                                                           
                <li>
                  <b>JUMP TO: </b>
                </li>
                <li class="discount_display_jumps">
                  <a href="#vtprd-checkout-reporting-anchor" title="Discount Checkout Display"><?php _e('Checkout Display', 'vtprd'); ?></a>
                  <span class="discount_display_jumps">|</span>
                </li>  
                
                <li class="discount_display_jumps">
                  <a href="#vtprd-cartWidget-options-anchor" title="Discount Cart Widget Display"><?php _e('Cart Widget Display', 'vtprd'); ?></a>
                  <span class="discount_display_jumps">|</span>
                </li>  
                
                <li>
                  <a href="#vtprd-catalog-options-anchor" title="Discount Catalog Display"><?php _e('Price Display', 'vtprd'); ?></a>
                  <span>|</span>
                </li>  

                <li>
                  <a href="#vtprd-processing-options-anchor" title="Processing Options"><?php _e('Processing Options', 'vtprd'); ?></a>
                  <span>|</span>
                </li>  

                <li>
                  <a href="#vtprd-lifetime-options-anchor" title="Lifetime Discount Options"><?php _e('Customer Limit Options', 'vtprd'); ?></a>
                  <span>|</span>
                </li>  

                <li>
                  <a href="#vtprd-system-options-anchor" title="System and Debug Options"><?php _e('System Options', 'vtprd'); ?></a>
                  <span>|</span>
                </li>  

                <li>
                  <a href="#vtprd-system-buttons-anchor" title="System Buttons"><?php _e('System Buttons', 'vtprd'); ?></a>
                  <span>|</span>  <?php //v2.0.2.0 ?>
                </li>  
                 
                 <?php //v2.0.2.0 begin ?> 
                <li>
                  <a href="#vtprd-delete-plugin-buttons-anchor" title="Delete all files Button"><?php _e('Set Override to Delete ALL on Uninstall', 'vtprd'); ?></a>
                </li> 
                <?php //v2.0.2.0 begin ?>               
                <!-- last li does not have spacer at end... -->          
              </ul> 
      <?php        
      //v1.0.9.0  added warning for low memory
      echo '<br>'; 
      $this->vtprd_check_memory_limit(); 
      ?>
            </div>            
    <?php
    
    //v2.0.0 begin
    if ( $options['debugging_mode_on'] == 'yes' ){ 
          $message  =  '<h1 style="text-decoration: underline;">Pricing Deals Settings WARNING</h1>' ;
          $message .=  '<h2>The &nbsp; <a href="#vtprd-system-options-anchor" title="System and Debug Options">Test Debugging Mode Turned On</a> &nbsp; switch (below) is set to "Yes" </h2>' ;
          $message .=  '<h1  style="color: red;">SETTING this switch to "Yes"  &nbsp; will produce &nbsp; ** a VERY large error log file ** &nbsp;  and should only be used when TESTING!!! </h1>' ;               
          $message .=  '<h2> SUGGEST setting <a href="#vtprd-system-options-anchor" title="System and Debug Options">Test Debugging Mode Turned On</a> &nbsp; to the default value of "NO" !!</h2>' ;          
          $admin_notices = '<div id="message" class="error fade is-dismissible" style="background-color: #FFEBE8 !important; width:80%; margin-left:10%;"><p>' . $message . ' </p></div>'; //v2.0.0.9 added width:80%; margin-left:10%;
          echo $admin_notices;    
    }
    //v2.0.0 end
    
}

//v1.0.9.0 begin

function vtprd_taken_where_options_callback () {
                                          
    ?>                                   
    <h4 id="vtprd-discount-messaging"><?php // esc_attr_e('Cart Discount Taken Where - in the Unit Price for each product or as a single Woo Coupon?', 'vtprd'); ?> 
          <strong><?php _e('Cart Discount Display', 'vtprd'); ?></strong>
          <?php esc_attr_e(' - in the Unit Price for each product, or as a single Woo Coupon?', 'vtprd'); ?>  
    </h4> 
    <?php    
    
}
//v1.0.9.0 end

function vtprd_catalog_options_callback () {
                                          
    ?>                                   
    <h4 id="vtprd-discount-messaging"><?php esc_attr_e('These options control Catalog Discount Display in the Theme.', 'vtprd'); ?> 

    </h4> 
    <?php    
    
}

function vtprd_general_options_callback () {
                                          
    ?>                                   
    <h4 id="vtprd-discount-messaging"><?php esc_attr_e('These options control Pricing Deal messaging shown in the Theme.', 'vtprd'); ?> 

    </h4> 
    <?php    
    
}

function vtprd_checkout_options_callback () {
    ?>                                   
    <h4 id="vtprd-checkout-reporting"><?php esc_attr_e('These options control Pricing Deal checkout display.', 'vtprd'); ?>
      <a id="help-all" class="help-anchor" href="javascript:void(0);" >
      <?php esc_attr_e('Show All:', 'vtprd'); ?> 
      &nbsp; <span> <?php esc_attr_e('More Info', 'vtprd'); ?> </span></a>     
    </h4> 
    <?php 
}

function vtprd_cartWidget_options_callback () {
    if(defined('WPSC_VERSION') && (VTPRD_PARENT_PLUGIN_NAME == 'WP E-Commerce') ) {
    ?>
    <h4 id="vtprd-cartWidget-options"><?php esc_attr_e('In order to display discounts in the Cart Widget, 2-3 lines of your theme cart-widget.php code need to be added/altered.   
            Instructions for these changes are described in the plugin readme file.', 'vtprd'); ?></h4>
    <?php
    } 
}

function vtprd_internals_options_callback () {
    ?>
    <h4 id="vtprd-system-options" id="vtprd-internal-options"><?php esc_attr_e('These options control internal functions within the plugin.', 'vtprd'); ?></h4>
    <?php  
}

function vtprd_show_old_price_callback () {   //opt35  documentation only, no switches set here!   
  $html .= '<span>'; 
  $html .=  __('When a Catalog Rule Discount rule is applied, the Old Price and You Save Messages can be displayed.', 'vtprd')  .'<strong><em>'. __('See', 'vtprd') .'</em> =></strong>'; 
  $html .= '<a id="help35" class="doc-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';  
  $html .= '</span>';
  $html .= '<p id="help35-text" class="help-text doc-text" >';
  $html .= '&nbsp;&nbsp;&nbsp; <strong>' . __('In order to show Old Price and You Save Messages when a Catalog Rule Discount rule is applied', 'vtprd') .'</strong>'. 
           __(', change "wpsc_the_product_price_display()" to "vtprd_the_product_price_display()" in the single product view, grid view,list view theme files, as documented in 
           the Pricing Deals plugin files to be found in WPSC-intgration/Sample wpsc-theme 3.8.9+.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('At that point, the Old Price and You Save messages will be automatically generated across all of the edited files.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('In order to control the messaging ', 'vtprd') .'<em><strong>'. __('by file type', 'vtprd') .'</strong></em>'. __(', add one or both of the array parameters as follows:', 'vtprd'); 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' .  __(' vtprd_the_product_price_display( array( "output_old_price" => false ) );  => Turns off the Old Price messages', 'vtprd'); 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' .  __(' vtprd_the_product_price_display( array( "output_you_save" => false ) );  => Turns off the You Save message', 'vtprd');  
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' .  __(' vtprd_the_product_price_display( array( "output_old_price" => false, "output_you_save" => false ) );  => Turns off both messages', 'vtprd');      
  $html .= '</p>';
 
	echo $html;
  
}


function vtprd_show_rule_msgs_callback () {   //opt36   documentation only, no switches set here!   
  $html .= '<span>'; 
  $html .=  __('Pricing Deal Description Messages can be shown anywhere in Theme, both inside and outside the loop. ', 'vtprd')  .'<strong><em>'. __('See', 'vtprd') .'</em> =></strong>';  
  $html .= '<a id="help36" class="doc-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';  
  $html .= '</span>';
  $html .= '<p id="help36-text" class="help-text doc-text" >';
  $html .=  '&nbsp;&nbsp;&nbsp; <strong>' . __('Pricing Deal Description Messages can be shown anywhere in Theme, both inside and outside the loop.  They can be shown for
                the entire site (store-wide discounts), by product category, by pricing deal custom taxonomy, by product, by rule - 
                both inside the loop and outside, as documented in 
                the Pricing Deals plugin files to be found in WPSC-intgration/Sample wpsc-theme 3.8.9+.', 'vtprd') .'</strong>';
  $html .= '<br><br>&nbsp;' .  __('At that point, the Old Price and You Save messages will be automatically generated across all of the edited files.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('In order to control the messaging', 'vtprd') .'<em><strong>'. __('by file type', 'vtprd') .'</strong></em>'. __(', add one or both of the array parameters as follows:', 'vtprd'); 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' .  __(' vtprd_the_product_price_display( array( "output_old_price" => false ) );  => Turns off the Old Price messages', 'vtprd'); 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' .  __(' vtprd_the_product_price_display( array( "output_you_save" => false ) );  => Turns off the You Save message', 'vtprd');  
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' .  __(' vtprd_the_product_price_display( array( "output_old_price" => false, "output_you_save" => false ) );  => Turns off both messages', 'vtprd');      
  $html .= '</p>';
 
	echo $html;
}


function vtprd_show_catalog_price_crossout_callback () {   //opt47
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="show_catalog_price_crossout" name="vtprd_setup_options[show_catalog_price_crossout]">';	
  $html .= '<option value="yes"' . selected( $options['show_catalog_price_crossout'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
  $html .= '<option value="no"'  . selected( $options['show_catalog_price_crossout'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
  
  $html .= '&nbsp;&nbsp;&nbsp;<em>' . __( 'Crossout shown ONLY at Product Catalog Display time (not in the cart)', 'vtprd' )  . '</em>&nbsp;&nbsp;&nbsp;'  ; 
  
  $html .= '<a id="help47" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') ;
  //$html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __( '(the switch only applies to prices discounted by Pricing Deals) ', 'vtprd' ) .  '</a>'; //v1.0.9.3
  $html .= '<p><em>&nbsp;&nbsp;';
  $html .= __('For a Catalog Template Rule, Do we show the original price with a crossout, followed by the discounted price?', 'vtprd');
  $html .=  '</em></p>';	
  $html .= '<p id="help47-text" class = "help-text" >'; 
  $html .= __('Useful if an item or group of items are on sale, independant of wholesale pricing...', 'vtprd'); 
  $html .= '</p>';
  
	echo $html;
}

function vtprd_show_price_suffix_callback() {    //opt50
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<br><input type="text" class="" id="show_price_suffix"  rows="1" cols="100" name="vtprd_setup_options[show_price_suffix]" value="' . $options['show_price_suffix'] . '">';

  $html .= '<a id="help50" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __( 'Define text to show after your Catalog Discount Price. This could be, for example, "Save 25%", to explain your pricing. 
        You can also have info substituted here using one of the following: {price_save_percent}, {price_save_amount}.', 'vtprd' );
  $html .=  '</em></p>';  
  $html .= '<p id="help50-text" class = "help-text" >'; 
  $html .= __('Set a Price Deal suffix to show how much has been saved...', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('Define text to show after your Product Deal Prices in the Catalog. This could be, for example, "Save 25%", to explain your pricing.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('You can also have info substituted here using one of the following: {price_save_percent}, {price_save_amount}.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('So you can represent "Save xx" by putting in "Save {price_save_percent}"  and the plugin will automatically fill in the saved percentage as "25%".', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('(CSS class "pricing-suffix")', 'vtprd'); 
  $html .= '</p><br><br>';
	echo $html;
}


function vtprd_show_yousave_one_some_msg_callback () {   //opt34
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="show_yousave_one_some_msg" name="vtprd_setup_options[show_yousave_one_some_msg]">';	
  $html .= '<option value="yes"' . selected( $options['show_yousave_one_some_msg'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
  $html .= '<option value="no"'  . selected( $options['show_yousave_one_some_msg'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
  $html .= '<a id="help34" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p><em>&nbsp;&nbsp;';
  $html .= __('For a Catalog Template Rule deal where a Product with variations has only some of the variations on sale, do we show an addtional "one of these are on sale" meessage
            saying "one/some of these are on sale and rule messages are displayed"?', 'vtprd');
  $html .=  '</em></p>';	
  $html .= '<p id="help34-text" class = "help-text" >'; 
  $html .= __('"A Display (catlog) pricing deal is in force.  It acts on a product with multiple variations, but only some have a price reduction.', 'vtprd') 
              .'<br><br>&nbsp;&nbsp;'. __('Instead of a "yousave" message, show either:', 'vtprd') 
              .'<br>&nbsp;&nbsp;&nbsp;&nbsp;'. __('"One of these are on sale"', 'vtprd')  
              .'<br>&nbsp;&nbsp;&nbsp;&nbsp;'. __('"Some of these are on sale".', 'vtprd') 
              .'<br><br>&nbsp;&nbsp;'. 
              __('When messages are requested via the "vtprd_show_product_realtime_discount_full_msgs_action", the "one of these are on sale" message will display also.', 'vtprd'); 
  $html .= '</p>';
  
	echo $html;
}

function vtprd_use_this_timeZone_callback() {    //opt20                                 
	$options = get_option( 'vtprd_setup_options' );	
	/*scares the punters
  if ( $options['use_this_timeZone'] == 'none') {
      echo '<span id="gmtError">';
      echo __('Please Select the Store GMT Time Zone. Your Web Host Server can have a different date than your Store, which can throw off Pricing Deal Rules begin/end dates.', 'vtprd');
      echo '</span><br>'; 
  }
  */
  $html = '<select id="use_this_timeZone" name="vtprd_setup_options[use_this_timeZone]">';
//was scaring the punters
//	$html .= '<option value="none"'                   .  selected( $options['use_this_timeZone'], 'none', false)                    . '> &nbsp;&nbsp;' . __(' - Please Select the Store Time Zone - ', 'vtprd') . '</option>';
  $html .= '<option value="keep"'                   .  selected( $options['use_this_timeZone'], 'keep', false)                    . '>' . __('Host Server already in the correct Time Zone', 'vtprd') . '</option>';
  $html .= '<option value="Europe/London"'          .  selected( $options['use_this_timeZone'], 'Europe/London', false)           . '>GMT &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Europe/London</option>';
  $html .= '<option value="Europe/Paris"'           .  selected( $options['use_this_timeZone'], 'Europe/Paris', false)            . '>GMT+1 &nbsp;&nbsp;&nbsp; Europe/Paris</option>';
  $html .= '<option value="Europe/Athens"'          .  selected( $options['use_this_timeZone'], 'Europe/Athens', false)           . '>GMT+2 &nbsp;&nbsp;&nbsp; Europe/Athens</option>';
  $html .= '<option value="Africa/Nairobi"'         .  selected( $options['use_this_timeZone'], 'Africa/Nairobi', false)          . '>GMT+3 &nbsp;&nbsp;&nbsp; Africa/Nairobi</option>';
  $html .= '<option value="Europe/Moscow"'          .  selected( $options['use_this_timeZone'], 'Europe/Moscow', false)           . '>GMT+4 &nbsp;&nbsp;&nbsp; Europe/Moscow</option>';
  $html .= '<option value="Asia/Calcutta"'          .  selected( $options['use_this_timeZone'], 'Asia/Calcutta', false)           . '>GMT+5 &nbsp;&nbsp;&nbsp; Asia/Calcutta</option>';
  $html .= '<option value="Asia/Dhaka"'             .  selected( $options['use_this_timeZone'], 'Asia/Dhaka', false)              . '>GMT+6 &nbsp;&nbsp;&nbsp; Asia/Dhaka</option>';
  $html .= '<option value="Asia/Krasnoyarsk"'       .  selected( $options['use_this_timeZone'], 'Asia/Krasnoyarsk', false)        . '>GMT+7 &nbsp;&nbsp;&nbsp; Asia/Krasnoyarsk</option>';
  $html .= '<option value="Australia/Perth"'        .  selected( $options['use_this_timeZone'], 'Australia/Perth', false)         . '>GMT+8 &nbsp;&nbsp;&nbsp; Australia/Perth</option>';
  $html .= '<option value="Asia/Seoul"'             .  selected( $options['use_this_timeZone'], 'Asia/Seoul', false)              . '>GMT+9 &nbsp;&nbsp;&nbsp; Asia/Seoul</option>';
  $html .= '<option value="Australia/Darwin"'       .  selected( $options['use_this_timeZone'], 'Australia/Darwin', false)        . '>GMT+9.5&nbsp; Australia/Darwin</option>';
  $html .= '<option value="Australia/Sydney"'       .  selected( $options['use_this_timeZone'], 'Australia/Sydney', false)        . '>GMT+10 &nbsp; Australia/Sydney</option>';
  $html .= '<option value="Asia/Magadan"'           .  selected( $options['use_this_timeZone'], 'Asia/Magadan', false)            . '>GMT+11 &nbsp; Asia/Magadan</option>';
  $html .= '<option value="Pacific/Auckland"'       .  selected( $options['use_this_timeZone'], 'Pacific/Auckland', false)        . '>GMT+12 &nbsp; Pacific/Auckland</option>';
  $html .= '<option value="Atlantic/Azores"'        .  selected( $options['use_this_timeZone'], 'Atlantic/Azores', false)         . '>GMT-1 &nbsp;&nbsp;&nbsp;&nbsp; Atlantic/Azores</option>';
  $html .= '<option value="Atlantic/South_Georgia"' .  selected( $options['use_this_timeZone'], 'Atlantic/South_Georgia', false)  . '>GMT-2 &nbsp;&nbsp;&nbsp;&nbsp; Atlantic/South_Georgia</option>';
  $html .= '<option value="America/Sao_Paulo"'      .  selected( $options['use_this_timeZone'], 'America/Sao_Paulo', false)       . '>GMT-3 &nbsp;&nbsp;&nbsp;&nbsp; America/Sao_Paulo</option>';
  $html .= '<option value="America/St_Johns"'       .  selected( $options['use_this_timeZone'], 'America/St_Johns', false)        . '>GMT-3.5 &nbsp; America/St_Johns</option>';
  $html .= '<option value="America/Halifax"'        .  selected( $options['use_this_timeZone'], 'America/Halifax', false)         . '>GMT-4 &nbsp&nbsp;&nbsp;&nbsp; America/Halifax</option>';
  $html .= '<option value="America/Caracas"'        .  selected( $options['use_this_timeZone'], 'America/Caracas', false)         . '>GMT-4.5 &nbsp; America/Caracas</option>';
  $html .= '<option value="America/New_York"'       .  selected( $options['use_this_timeZone'], 'America/New_York', false)        . '>GMT-5 &nbsp;&nbsp;&nbsp;&nbsp; America/New_York</option>';
  $html .= '<option value="America/Chicago"'        .  selected( $options['use_this_timeZone'], 'America/Chicago', false)         . '>GMT-6 &nbsp;&nbsp;&nbsp;&nbsp; America/Chicago</option>';
  $html .= '<option value="America/Denver"'         .  selected( $options['use_this_timeZone'], 'America/Denver', false)          . '>GMT-7 &nbsp;&nbsp;&nbsp;&nbsp; America/Denver</option>';
  $html .= '<option value="America/Los_Angeles"'    .  selected( $options['use_this_timeZone'], 'America/Los_Angeles', false)     . '>GMT-8 &nbsp;&nbsp;&nbsp;&nbsp; America/Los_Angeles</option>';
  $html .= '<option value="America/Anchorage"'      .  selected( $options['use_this_timeZone'], 'America/Anchorage', false)       . '>GMT-9 &nbsp;&nbsp;&nbsp;&nbsp; America/Anchorage</option>';
  $html .= '<option value="Pacific/Honolulu"'       .  selected( $options['use_this_timeZone'], 'Pacific/Honolulu', false)        . '>GMT-10 &nbsp;&nbsp; Pacific/Honolulu</option>';
  $html .= '<option value="Pacific/Midway"'         .  selected( $options['use_this_timeZone'], 'Pacific/Midway', false)          . '>GMT-11 &nbsp;&nbsp; Pacific/Midway</option>';
  $html .= '<option value="Kwajalein"'              .  selected( $options['use_this_timeZone'], 'Kwajalein', false)               . '>GMT-12 &nbsp;&nbsp; Kwajalein, Marshall Islands</option>';  
	$html .= '</select>';
  $html .= '&nbsp;&nbsp;&nbsp;<a  href="http://wwp.greenwichmeantime.com/time-zone/"  title="' . __('Find Your GMT Time Zone', 'vtprd') . '">' . __('Find Your GMT Time Zone', 'vtprd') . '</a>';
  $html .= '<a id="help20" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br><br><em>';
  $html .= __('(Your host server can have a Different Time Zone and *Date* than your store, which can throw off Rule begin/end dates.)', 'vtprd');
  $html .=  '</em>';
	 
  $html .= '<p id="help20-text" class = "help-text" >'; 
  $html .= __('Please select the GMT value which matches the Store Location Time Zone.  This helps the date ranges in the Rule setup to be as accurate
              as possible.  They will now be anywhere from accurate to 1 hour off (because of Daylight Savings, different the world over).', 'vtprd')  
              .'<br><br><em>'.
              __('Your host server can have a different date than your store, depending on time of day!', 'vtprd')
              .'</em><br><br>'.
              __('You can find your store GMT timezone in', 'vtprd') 
              .'<a  href="http://wwp.greenwichmeantime.com/time-zone/"  title="'. 
              __('Find Your GMT Time Zone">Find Your GMT Time Zone', 'vtprd')
              .'</a><br><br>'.
              __('**If the time zone setting has no affect on the store, Check your php ini file whether timezone is set.**', 'vtprd');       
  $html .= '</p><br><br>';  
                                    
	echo $html;
}

function vtprd_wholesale_products_display_callback() {   //opt54
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="wholesale_products_display" name="vtprd_setup_options[wholesale_products_display]">';
	$html .= '<option value="noAction"'      . selected( $options['wholesale_products_display'], 'noAction', false)      . '>'   . __('Show All', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="respective"'    . selected( $options['wholesale_products_display'], 'respective', false)    . '>'   . __('Show Retail Products to Retail, Wholesale Products to Wholesale', 'vtprd') . '</option>';
  $html .= '<option value="wholesaleAll"'  . selected( $options['wholesale_products_display'], 'wholesaleAll', false)  . '>'   . __('Show Retail Products to Retail, All Products to Wholesale', 'vtprd') . '</option>';
  $html .= '<option value="retailAll"'     . selected( $options['wholesale_products_display'], 'retailAll', false)     . '>'   . __('Show All Products to Retail, Wholesale Products to Wholesale', 'vtprd') . '</option>'; //v1.1.1
	$html .= '</select>';  
  

  $html .= '<a id="help54" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br><br><em>&nbsp;&nbsp;';
  $html .= __(' - Works with: ', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' * Product Wholesale Checkbox on Product Page (in the PUBLISH box)', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' * Wholesale Role or Capability', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __('to control display of Retail/Wholesale products.', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;';
  $html .= __(' - Roles and Capabilities managed in your Role Manager Plugin - [see "More Info" above].', 'vtprd');
  $html .=  '</em>';
     
  $html .= '<p id="help54-text" class = "help-text" >'; 
  $html .= __('Catalog Products Display Options', 'vtprd') .'<br>';
  $html .= __('- Product screen now has a "wholesale product" checkbox in the PUBLISH box') .'<br>';
  $html .= __('- Combined with this switch, you have complete control over when Retail and Wholesale Products display.
              Label all of the Wholesale products in the Product screen, then select the appropriate option above.  When a Retail
              or Wholesale customer views the products, they will receive the list tailored to the selection above.') .'<br>';
  $html .= '<strong>';   
  $html .= __('NB => (Not logged in = Retail)', 'vtprd');
  $html .= '</strong>'; 
 
  $html .= '<br><br>';
  $html .= '<a class="subsection-title-smaller" target="_blank" href="https://wordpress.org/plugins/members/">Recomended Role Manager Plugin</a>'; 

  $html .= '<br><br><strong>';    
  $html .= __('Please Note: ANY Role can be a Wholesale role, IF the <em>Wholesale Capability</em> is selected for that Role (using the Role Manager)', 'vtprd');
  $html .= '</strong>'; 
            
  $html .= '</p><br><br>';   
            
	echo $html;
}



//***************************************************************
//v1.1.1 Product purchasability and Pricing Visibility

function vtprd_wholesale_products_price_display_callback() {   //opt55
	$options = get_option( 'vtprd_setup_options' );	 
	$html = '<select id="wholesale_products_price_display" name="vtprd_setup_options[wholesale_products_price_display]">';
	$html .= '<option value="noAction"'      . selected( $options['wholesale_products_price_display'], 'noAction', false)      . '>'   . __('Show All', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="onlyOnly"'      . selected( $options['wholesale_products_price_display'], 'onlyOnly', false)      . '>'   . __('Retail Products for Retail, Wholesale Products for Wholesale', 'vtprd') . '</option>';  
  $html .= '<option value="respective"'    . selected( $options['wholesale_products_price_display'], 'respective', false)    . '>'   . __('Retail Products for Retail, All Products for Wholesale', 'vtprd') . '</option>';  
	$html .= '<option value="wholesaleOnly"' . selected( $options['wholesale_products_price_display'], 'wholesaleOnly', false) . '>'   . __('No Products for Retail, Wholesale Products for Wholesale', 'vtprd') . '</option>';
	$html .= '<option value="wholesaleAll"'  . selected( $options['wholesale_products_price_display'], 'wholesaleAll', false)  . '>'   . __('No Products for Retail, All Products for Wholesale', 'vtprd') . '</option>';
  $html .= '<option value="noPrices"'      . selected( $options['wholesale_products_price_display'], 'noPrices', false)      . '>'   . __('No Products Purchasable - use WooCommerce as a Catalog only', 'vtprd') . '</option>';
	$html .= '</select>';  
  

  $html .= '<a id="help55" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br><br><em>&nbsp;&nbsp;';
  $html .= __(' - Works with: ', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' * Product Wholesale Checkbox on Product Page (in the PUBLISH box)', 'vtprd'); //v1.1.7
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' * Wholesale Role or Capability', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __('to control Retail/Wholesale products Purchasability.', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;';
  $html .= __(' - Roles and Capabilities managed in your Role Manager Plugin - [see "More Info" above].', 'vtprd');
  
  $html .= '<br>&nbsp;&nbsp;';
  $html .= __(' - For <strong>Price Replacement</strong> (with Spaces or a Message)', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __('please use the matching Filter ("vtprd_replace_price_with_message") - [see "More Info" above].', 'vtprd'); 
   
  $html .=  '</em>';
     
  $html .= '<p id="help55-text" class = "help-text" >'; 
  $html .= __('Catalog Products Purchasability Display Options', 'vtprd') .'<br>';
  $html .= __('- Product screen now has a "wholesale product" checkbox in the PUBLISH box') .'<br>'; //v1.1.7
  $html .= __('- Combined with this switch, you have complete control over when Retail and Wholesale Products ADD-to-Cart button displays.
              Label all of the Wholesale products in the Product screen, then select the appropriate option above.  When a Retail
              or Wholesale customer views the products, they will receive the list tailored to the selection above.') .'<br>';
  $html .= '<strong>';   
  $html .= __('NB => (Not logged in = Retail)', 'vtprd');
  $html .= '</strong>'; 
  
  $html .= '<br><br>';
  $html .= "// Copy Begin" .'<br>';
  $html .= "// ***************************************************************************************" .'<br>';
  $html .= "// *** TO REPLACE PRICE WITH SPACES OR MESSAGE, if desired - message may include HTML" .'<br>';
  $html .= "// *** add filter/function to the bottom of your ** Theme Functions file**" .'<br>';
  $html .= "// ***   (Copy the code from Copy Begin to Copy End)" .'<br>';
  $html .= "// ***************************************************************************************" .'<br>';
  $html .= "add_filter('vtprd_replace_price_with_message_if_product_not_purchasable', 'do_replace_price_with_message_if_product_not_purchasable', 10, 1);" .'<br>';
  $html .= "function do_replace_price_with_message_if_product_not_purchasable() {" .'<br>';
  $html .= "  return ' Message to replace Price, if Product may not be Purchased by User. May be Blank, Spaces, a Message and/or HTML '; " .'<br>';
  $html .= "}" .'<br>';
  $html .= "// Copy End";
 
  $html .= '<br><br>';
  $html .= '<a class="subsection-title-smaller" target="_blank" href="https://wordpress.org/plugins/members/">Recomended Role Manager Plugin</a>'; 

  $html .= '<br><br><strong>';    
  $html .= __('Please Note: ANY Role can be a Wholesale role, IF the <em>Wholesale Capability</em> is selected for that Role (using the Role Manager)', 'vtprd');
  $html .= '</strong>'; 
           
  $html .= '</p><br><br>';   
            
	echo $html;
}

//******************
//v1.1.7.2
//******************
function vtprd_limit_cart_discounts_callback() {   //opt57
	$options = get_option( 'vtprd_setup_options' );	 
	$html = '<select id="limit_cart_discounts" name="vtprd_setup_options[limit_cart_discounts]">';
	$html .= '<option value="none"'      . selected( $options['limit_cart_discounts'], 'none', false)      . '>'   . __('No cart rule discount limit', 'vtprd') . '</option>';  

	$html .= '<option value="allow_only_one_cart_pricing_deal_discount_per_cart"'      
      . selected( $options['limit_cart_discounts'], 'allow_only_one_cart_pricing_deal_discount_per_cart', false)      . '>'   
      . __('1. allow only one cart pricing deal discount per cart', 'vtprd') .  '&nbsp;</option>';

	$html .= '<option value="allow_no_cart_pricing_deals_if_woo_coupon_standalone_discount"'      
      . selected( $options['limit_cart_discounts'], 'allow_no_cart_pricing_deals_if_woo_coupon_standalone_discount', false)      . '>'   
      . __('2. allow no cart pricing deals if woo coupon standalone discount', 'vtprd') .  '&nbsp;</option>';

	$html .= '<option value="allow_only_one_coupon_actuated_pricing_deal_discount_per_cart"'      
      . selected( $options['limit_cart_discounts'], 'allow_only_one_coupon_actuated_pricing_deal_discount_per_cart', false)      . '>'   
      . __('3. allow only one coupon actuated pricing deal discount per cart', 'vtprd') .  '&nbsp;</option>';

	$html .= '<option value="allow_no_other_cart_pricing_deal_discounts_if_coupon_actuated_pricing_deal_presented"'      
      . selected( $options['limit_cart_discounts'], 'allow_no_other_cart_pricing_deal_discounts_if_coupon_actuated_pricing_deal_presented', false)      . '>'   
      . __('4. allow no other pricing deal discounts if coupon actuated pricing deal presented', 'vtprd') .  '&nbsp;</option>';

	$html .= '<option value="allow_no_standalone_woo_coupon_discounts_if_any_cart_pricing_deal_discount_granted"'      
      . selected( $options['limit_cart_discounts'], 'allow_no_standalone_woo_coupon_discounts_if_any_cart_pricing_deal_discount_granted', false)      . '>'   
      . __('5. allow no standalone woo coupon discounts if any cart pricing deal discounted', 'vtprd') .  '&nbsp;</option>';

	$html .= '<option value="allow_no_standalone_woo_coupon_discounts_if_coupon_actuated_pricing_deal_granted"'      
      . selected( $options['limit_cart_discounts'], 'allow_no_standalone_woo_coupon_discounts_if_coupon_actuated_pricing_deal_granted', false)      . '>'   
      . __('6. allow no standalone woo coupon discounts if coupon actuated pricing deal discounted', 'vtprd') .  '&nbsp;</option>';

	$html .= '</select>';  

  $html .= '<a id="help57" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br><br><em>&nbsp;&nbsp;';
  $html .= __(' - Applies only to CART RULES: ', 'vtprd');
  $html .= '<br><br><em>&nbsp;&nbsp;';
  $html .= __(' - ALSO REQUIRES each Rule to be updated: ', 'vtprd');  
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' * <strong>"Other Rule Discounts", the "Priority" value on EACH rule must set for RULE SORTING</strong>', 'vtprd'); 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' ->  Priority 1 is the highest priority.', 'vtprd');
  $html .= '<br><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' ->  <strong>**MUST**</strong> set an unique Priority number on each rule, <strong>to set the execution sequence</strong>', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;';
  $html .= __(' - Please read the full explanation - click on "More Info" above.', 'vtprd');
  $html .=  '</em><br><br>'; //v1.1.8.0 added a br
     
  $html .= '<p id="help57-text" class = "help-text" >'; 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;<strong>Limit Cart Discounts via Cart Cross-Rule Limits </strong><br><br>';
  $html .= ' - Limits require two components - the above limit switch setting <br>'; 
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; AND <br>';
  $html .= ' - Individual Rule Sort Priority Settings. <br><br>';
  
  $html .= __(' * "Other Rule Discounts" "Priority" value on EACH rule for RULE SORTING', 'vtprd'); 
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' ->  Priority 1 is the highest priority.', 'vtprd');
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __(' ->  <strong>**MUST**</strong> set an unique Priority number on each rule, <strong>to set the execution sequence</strong>', 'vtprd');
  $html .= '<br><br>';
  
  $html .= ' Sort Rules <br>';
  $html .= ' **MUST** set "Other Rule Discounts" "Priority" value on EACH rule for RULE SORTING <br>';
  $html .= ' &nbsp;&nbsp;&nbsp; Priority 1 is the highest priority. <br>';
  $html .= ' &nbsp;&nbsp;&nbsp; An unique Priority number on each rule should be used on each rule, to set the execution sequence. <br>';
                                                           
  $html .= ' &nbsp;&nbsp;&nbsp; In Pricing Deals, there are: <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (A) Pricing Deals discounts <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (B) Woo Coupon standalone discounts <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (C) Pricing Deals discount *actuated* by Woo Coupon <br><br>';

  $html .= ' &nbsp; New Filters which control Discount Limits (choose 1 !!): <br><br>';	
            		
  $html .= ' &nbsp;&nbsp;&nbsp; 1. ONLY 1 overall pricing deals discount (A) or (C) Per Cart (does not care about WOO coupon discounts(B) ) <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SORT Rules as desired</strong><br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SELECT</strong> "1. allow only one overall pricing deal discount per cart" <br><br>';
                 
  $html .= ' &nbsp;&nbsp;&nbsp; 2. IF standalone WOO coupon presented (B), no pricing deals (A) or (C) <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>NO Sorting required</strong><br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SELECT</strong> "2. allow no pricing deals if woo coupon standalone discount" <br><br>';
                 
  $html .= ' &nbsp;&nbsp;&nbsp; 3. ONLY 1 Pricing deal coupon-actuated rule (C)  per cart <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SORT (C) rules first, as described above</strong><br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SELECT</strong> "3. allow only one coupon actuated pricing deal discount per cart" <br><br>';
                 
  $html .= ' &nbsp;&nbsp;&nbsp; 4. IF Pricing deal coupon-actuated rule (C) discount granted, no further Pricing Deals discounts (A) or (C) <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SORT (C) rules first, as described above</strong><br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SELECT</strong> "4. allow no further pricing deal discounts if coupon actuated pricing deal presented" <br><br>';      
                 
  $html .= ' &nbsp;&nbsp;&nbsp; 5. IF Pricing deal discount (A) or (C) granted, no standalone WOO coupons allowed <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>NO Sorting required</strong><br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SELECT</strong> "5. allow no standalone woo coupon discounts if any pricing deal discount discounted" <br><br>';      
                 
  $html .= ' &nbsp;&nbsp;&nbsp; 6. IF Pricing deal coupon-actuated discount (C) granted, no standalone WOO coupons allowed <br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>NO Sorting required</strong><br>';
  $html .= ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  <strong>SELECT</strong> "6. allow no standalone woo coupon discounts if coupon actuated pricing deal discounted" <br><br>';      
       
                 
	echo $html;
}


/* no longer used!
function vtprd_bogo_auto_add_the_same_product_type_callback() {    //opt47                                
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<select id="bogo_auto_add_the_same_product_type" name="vtprd_setup_options[bogo_auto_add_the_same_product_type]">';
	$html .= '<option value="allAdds"' .  selected( $options['bogo_auto_add_the_same_product_type'], 'allAdds', false) . '>'   . __('Product Qty changes always considered to be purchases - auto adds added to the qty', 'vtprd') .  '</option>';
  $html .= '<option value="fitInto"' .  selected( $options['bogo_auto_add_the_same_product_type'], 'fitInto', false) . '>'   . __('Auto adds applied to qty 1st time. Changed quantity = combined total of both purchases and auto adds', 'vtprd') .  '</option>';
  $html .= '</select>';
  $html .= '<a id="help47" class="help-anchor" href="javascript:void(0);" >'   . __('SEE => More Info', 'vtprd') .  '</a>';
  $html .= '<p id="help47-text" class = "help-text" >'; 
  $html .= __('(1) a rule is set up to be BOGO, and', 'vtprd') 
          .'<br>'.
           __('(2) both the Buy and Action Filter groups apply to the same product, and', 'vtprd') 
           .'<br>'.
           __('(3) the auto Add Free Product switch is on:', 'vtprd')
           .'<br>'.
           __('This setting controls that Auto Add behavior.', 'vtprd')  
          .'<br><br>'.
           __('Default behavior:  any change to the quantity field for the BOGO product is treated as a purchase, and the auto adds are then added to that quantity.', 'vtprd')
           .'<br>&nbsp;&nbsp;&nbsp;&nbsp;'.
           __('For example: Initial purchase qty =2 units.  Result = 2 units purchased + 2 units free, for a total of 4 units.', 'vtprd')
            .'<br>'.
           __('AFTER THE INITIAL ADD TO CART, If customer then CHANGES THE QTY to 5 units, the AUTO ADDS ARE APPLIED TO THAT QUANTTY.  Result = 5 units purchased + 5 units free, for a total of 10 units.', 'vtprd') 
           .'<br><br>'.
           __('Optional behavior:  Only the first purchase the BOGO product is treated as a purchase, and the auto adds are then added to that quantity.', 'vtprd')
           .'<br>&nbsp;&nbsp;&nbsp;&nbsp;'.
           __('For example: Initial purchase qty =2 units.  *** Result = 2 units purchased + 2 units free, for a total of 4 units *** .', 'vtprd')
           .'<br>'.
           __('AFTER THE INITIAL ADD TO CART, If customer then CHANGES THE QTY that quantity becomes the TOTAL TARGET OF PURCHASES + AUTO ADDS.', 'vtprd')
           .'<br>'.
           __('For example a CHANGED QTY of 7 REMAINS AT 7, CONTAINING: *** 4 purchased + 3 free *** .', 'vtprd');  
                     
  $html .= '</p>';  
  
	echo $html;
}	
*/  	

/*  
function vtprd_register_under_tools_menu_callback() {   //opt1
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="register_under_tools_menu" name="vtprd_setup_options[register_under_tools_menu]">';
	$html .= '<option value="no"'  . selected( $options['register_under_tools_menu'], 'no', false) . '>'   . __('In the Main Admin Menu as its own Heading (def) ', 'vtprd') . '&nbsp;</option>';
  $html .= '<option value="yes"' . selected( $options['register_under_tools_menu'], 'yes', false) . '>'   . __('"Hide" under the Tools Menu (and Settings go under the Settings Menu) ', 'vtprd') .  '&nbsp;</option>';
	$html .= '</select>';
  $html .= '<a id="help1" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p><em>&nbsp;&nbsp;';
  $html .= __('(on update, the settings screen display will fail with "Cannot load", and that"s ok as the location will have shifted.)', 'vtprd');
  $html .=  '</em></p>';	
  $html .= '<p id="help1-text" class = "help-text" >'; 
  $html .= __('"Pricing Deals Admin Menu Location" - The Admin menu area tends to get a little overcrowded.  If that is so in your installation, you can elect
             to move the Pricing Deals menu items under the TOOLS menu.', 'vtprd'); 
  $html .= '</p>';
  
	echo $html;
}
*/

function vtprd_use_lifetime_max_limits_callback() {   //opt2
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="use_lifetime_max_limits" name="vtprd_setup_options[use_lifetime_max_limits]">';
	$html .= '<option value="yes"' . selected( $options['use_lifetime_max_limits'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['use_lifetime_max_limits'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
  
  $html .= '<a id="help2" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help2-text" class = "help-text" >'; 
  $html .= __('The "Use Customer Rule Limits" switch is a Store-Wide switch for the whole installation,
        and must be set to "Yes" in order for individual Rule-based Customer Rule Limit switches to be active.
        Switch also controls the storage of historical purchase data for future Customer Rule Limit checking', 'vtprd'); 
  $html .= '</p>'; 
  /* //v2.0.2.0 removed
  $html .= '<p><em>&nbsp;&nbsp;';
  $html .= __('This switch controls both the active testing of Customer Rule Limits, and the
        storage of historical purchase data for future Customer Rule Limit checking.  Customer Rule Limit checking data will only be stored if this switch = "yes"', 'vtprd');
  $html .=  '</em></p>';
    
  //Heading for the next section, with description      
  $html .= '<p id="lifetime-by-ip-intro" class="extra-intro">'; 
  $html .= '<strong>'. __('Checking by IP is immediately available at Shortcode time,, at Add to Cart time and at Checkout time.', 'vtprd') .'</strong>'; 
  $html .= '</p>';
  */  
            
	echo $html;
}

function vtprd_discount_floor_pct_per_single_item_callback() {    //opt3
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<input type="text" class="smallText" id="discount_floor_pct_per_single_item"  rows="1" cols="20" name="vtprd_setup_options[discount_floor_pct_per_single_item]" value="' . $options['discount_floor_pct_per_single_item'] . '">' . '%';

  $html .= '<a id="help3" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br>&nbsp;&nbsp;';
  $html .= '<i>'. __( 'Store-Wide "no more than" Product limit (Free products are exempt from this limit)', 'vtprd' )  .'</i>';
    
  $html .= '<p id="help3-text" class = "help-text" >'; 
  $html .= __('Set an absolute Product Discount max percentage, below which no discount will go - Store-Wide Setting => all accumulated discounts applied to a product may not go below this percentage', 'vtprd'); 
  $html .= '<br><br>'. __('Blank = do not use. ', 'vtprd');
  $html .= '<br><br>'. __('Default = blank', 'vtprd');
  $html .= '</p><br><br>';
	echo $html;
}


function vtprd_discount_floor_pct_msg_callback() {    //opt4
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<textarea type="text" id="discount_floor_pct_msg"  rows="1" cols="60" name="vtprd_setup_options[discount_floor_pct_msg]">' . $options['discount_floor_pct_msg'] . '</textarea>';

  $html .= '<a id="help4" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help4-text" class = "help-text" >'; 
  $html .= __('Product Discount max Percentage Message.  Message must be both filled in here, and requested via theme customization
              using the "vtprd_show_product_discount_limit_reached_short_msg_action" documented in the readme.', 'vtprd')    
              .'<br><br>'.
              __('** The message is shown in cart and checkout only **', 'vtprd') 
              .'<br><br>'.
              __('and will only appear when the Product Discount Max Percentage limit has been reached.', 'vtprd')
              .'<br><br>'.
              __('Default value = "System Max xx% Discount reached.".', 'vtprd');                
  $html .= '</p>';
  	
	echo $html;
}



function vtprd_show_checkout_discount_details_grouped_by_what_callback() {    //opt21                                 
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<select id="show_checkout_discount_details_grouped_by_what" name="vtprd_setup_options[show_checkout_discount_details_grouped_by_what]">';
	$html .= '<option value="rule"'             .  selected( $options['show_checkout_discount_details_grouped_by_what'], 'rule', false)    . '>'   . __('Grouped by Rule within Product', 'vtprd') . '&nbsp;</option>';
  $html .= '<option value="product"'          .  selected( $options['show_checkout_discount_details_grouped_by_what'], 'product', false) . '>'   . __('Grouped by Product ', 'vtprd') .  '</option>';
  $html .= '</select>';
  $html .= '<a id="help21" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p id="help21-text" class = "help-text" >'; 
  $html .= __('If Checkout Discount detail lines are to be displayed, how are they organized?  By Rule, there will be a separate line by Rule for each product which got a discount based on that rule.
              You can elect to show the relevant Rule short cart message in a line above each detail line.', 'vtprd') 
          .'<br><br>'.
           __('By product totals up all discounts accrued to that product, and produces a single detail line.', 'vtprd'); 
  $html .= '</p>';  
  
	echo $html;
}


function vtprd_show_cartWidget_discount_details_grouped_by_what_callback() {    //opt22                                 
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<div class="unitPriceOrCoupon"> <select id="show_cartWidget_discount_details_grouped_by_what" name="vtprd_setup_options[show_cartWidget_discount_details_grouped_by_what]">'; //v1.0.9.3
	$html .= '<option value="rule"'             .  selected( $options['show_cartWidget_discount_details_grouped_by_what'], 'rule', false)    . '>'   . __('Grouped by Rule within Product ', 'vtprd') .   '&nbsp;</option>';
  $html .= '<option value="product"'          .  selected( $options['show_cartWidget_discount_details_grouped_by_what'], 'product', false) . '>'   . __('Grouped by Product ', 'vtprd') .  '</option>';
  $html .= '</select>';

  $html .= '<a id="help22" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p id="help22-text" class = "help-text" >'; 
  $html .= __('If Cart Widget Discount detail lines are to be displayed, how are they organized?  By Rule, there will be a separate line by Rule for each product which got a discount based on that rule.
              You can elect to show the relevant Rule short cart message in a line above each detail line.', 'vtprd') 
              .'<br><br>'.
          __('By product totals up all discounts accrued to that product, and produces a single detail line.', 'vtprd'); 
  $html .= '</p></div>';  
  
	echo $html;
}

function vtprd_show_checkout_discount_titles_above_details_callback () {    //opt23
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="show_checkout_discount_titles_above_details" name="vtprd_setup_options[show_checkout_discount_titles_above_details]">';
	$html .= '<option value="yes"' . selected( $options['show_checkout_discount_titles_above_details'], 'yes', false) . '>'   . __('Yes', 'vtprd') . '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_checkout_discount_titles_above_details'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
	
  $html .= '<a id="help23" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help23-text" class = "help-text" >'; 
  $html .= __('When discount details display, do we show the Short Checkout Message above Rule Product Discount detail line?  Only applicable if Checkout "Grouped by Rule" chosen above.', 'vtprd'); 
  $html .= '</p>'; 
  
	echo $html;
} 

function vtprd_show_cartWidget_discount_titles_above_details_callback () {    //opt7
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<div class="unitPriceOrCoupon"><select id="show_cartWidget_discount_titles_above_details" name="vtprd_setup_options[show_cartWidget_discount_titles_above_details]">';  //v1.0.9.3
	$html .= '<option value="yes"' . selected( $options['show_cartWidget_discount_titles_above_details'], 'yes', false) . '>'   . __('Yes', 'vtprd')  .   '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_cartWidget_discount_titles_above_details'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
	
  $html .= '<a id="help7" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help7-text" class = "help-text" >'; 
  $html .= __('When discount details display, do we show the Short Checkout Message above Rule Product Discount detail line? Only applicable if Cart Widget "Grouped by Rule" chosen above.', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
}   

function vtprd_show_checkout_purchases_subtotal_callback () {    //opt24
	$options = get_option( 'vtprd_setup_options' );	  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
	$html = '<div class="unitPriceOrCoupon"> <select id="show_checkout_purchases_subtotal" name="vtprd_setup_options[show_checkout_purchases_subtotal]">';
  $html .= '<option value="withDiscounts"'  . selected( $options['show_checkout_purchases_subtotal'], 'withDiscounts', false)    . '>'   . __('Yes - Show ', 'vtprd') .  '&nbsp;'   . __('After Discounts', 'vtprd') .  '&nbsp;</option>';  
  $html .= '<option value="beforeDiscounts"' . selected( $options['show_checkout_purchases_subtotal'], 'beforeDiscounts', false) . '>'   . __('Yes - Show ', 'vtprd') .  '&nbsp;'   . __('Before Discounts ', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="none"'  . selected( $options['show_checkout_purchases_subtotal'], 'none', false)  . '>'   . __('No - No New Subtotal Line ', 'vtprd') .  '&nbsp;'   . __('for Cart Purchases ', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help24" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help24-text" class = "help-text" >'; 
  $html .= __('Do we show the purchases subtotal before discounts, with discounts or not at all?', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
} 

function vtprd_show_cartWidget_purchases_subtotal_callback () {    //opt25
	$options = get_option( 'vtprd_setup_options' );	 //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
	$html = '<div class="unitPriceOrCoupon"> <select id="show_cartWidget_purchases_subtotal" name="vtprd_setup_options[show_cartWidget_purchases_subtotal]">';
	$html .= '<option value="withDiscounts"'  . selected( $options['show_cartWidget_purchases_subtotal'], 'withDiscounts', false)    . '>'   . __('Yes - Show ', 'vtprd') .  '&nbsp;'   . __('After Discounts ', 'vtprd') .  '&nbsp;</option>';  
  $html .= '<option value="beforeDiscounts"' . selected( $options['show_cartWidget_purchases_subtotal'], 'beforeDiscounts', false) . '>'   . __('Yes - Show ', 'vtprd') .  '&nbsp;'   . __('Before Discounts ', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="none"'  . selected( $options['show_cartWidget_purchases_subtotal'], 'none', false) . '>'   . __('No - No New Subtotal Line ', 'vtprd') .  '&nbsp;'   . __('for Cart Purchases ', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help25" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help25-text" class = "help-text" >'; 
  $html .= __('Do we show the purchases subtotal before discounts, with discounts or not at all? (Default = no)', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
} 

function vtprd_show_checkout_discount_total_line_callback () {    //opt5
	$options = get_option( 'vtprd_setup_options' );	//<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
	$html = '<div class="unitPriceOrCoupon"> <select id="show_checkout_discount_total_line" name="vtprd_setup_options[show_checkout_discount_total_line]">';
	$html .= '<option value="yes"' . selected( $options['show_checkout_discount_total_line'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_checkout_discount_total_line'], 'no', false) . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help5" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help5-text" class = "help-text" >'; 
  $html .= __('When Checkout Discounts are taken, do we show a separate discount totals line?', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
} 


function vtprd_show_cartWidget_discount_total_line_callback () {    //opt26
	$options = get_option( 'vtprd_setup_options' );	 //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
	$html = '<div class="unitPriceOrCoupon"> <select id="show_cartWidget_discount_total_line" name="vtprd_setup_options[show_cartWidget_discount_total_line]">';
	$html .= '<option value="yes"' . selected( $options['show_cartWidget_discount_total_line'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_cartWidget_discount_total_line'], 'no', false) . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help26" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help26-text" class = "help-text" >'; 
  $html .= __('When Cart Widget Discounts are taken, do we show a separate discount totals line?', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
} 


function vtprd_show_checkout_discount_detail_lines_callback () {    //opt6
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="show_checkout_discount_detail_lines" name="vtprd_setup_options[show_checkout_discount_detail_lines]">';
	$html .= '<option value="yes"' . selected( $options['show_checkout_discount_detail_lines'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_checkout_discount_detail_lines'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help6" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help6-text" class = "help-text" >'; 
  $html .= __('Do we show Checkout discount detail lines, or just show the discount grand total?', 'vtprd'); 
  $html .= '</p>'; 
  
	echo $html;
} 


function vtprd_show_cartWidget_discount_detail_lines_callback () {    //opt27
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<div class="unitPriceOrCoupon"> <select id="show_cartWidget_discount_detail_lines" name="vtprd_setup_options[show_cartWidget_discount_detail_lines]">'; //v1.0.9.3
	$html .= '<option value="yes"' . selected( $options['show_cartWidget_discount_detail_lines'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_cartWidget_discount_detail_lines'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help27" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help27-text" class = "help-text" >'; 
  $html .= __('Do we show cartWidget discount detail lines?', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
}
 

function vtprd_debugging_mode_callback () {    //opt8
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="debugging_mode_on" name="vtprd_setup_options[debugging_mode_on]">';
	$html .= '<option value="yes"' . selected( $options['debugging_mode_on'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['debugging_mode_on'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help8" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help8-text" class = "help-text" >'; 
  $html .= __('"Test Debugging Mode Turned On" => 
  Set this to "yes" if you want to see the full rule structures which produce any error messages. **ONLY** should be used during testing.', 'vtprd'); 
  $html .= '<br><br>';
  $html .= __('To see the debugging output, add these lines to your wordpress install"s wp-config.php file: (output will be at wp-content/debug.log)', 'vtprd');  
 $html .= "<br>    // log php errors     ";
 $html .= "<br> define('WP_DEBUG', true);    ";
 $html .= "<br> define( 'WP_DEBUG_LOG', true ); // log to wp-content/debug.log  ";
 $html .= "<br> define( 'WP_DEBUG_DISPLAY', false ); // don't force display_errors to on ";
 $html .= "<br> ini_set( 'display_errors', 0 ); // hide errors  "; 
  $html .= '</p>';  
  
	echo $html;
}

 
    //v2.0.0.8
function vtprd_remove_extra_js_callback () {    //opt60
	$options = get_option( 'vtprd_setup_options' );	
  if ($options['remove_all_extra_js_from_rule_page'] <= ' ') {
    $options['remove_all_extra_js_from_rule_page'] = 'no';
  }
	$html = '<select id="remove_all_extra_js_from_rule_page" name="vtprd_setup_options[remove_all_extra_js_from_rule_page]">';
	$html .= '<option value="yes"' . selected( $options['remove_all_extra_js_from_rule_page'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['remove_all_extra_js_from_rule_page'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help8" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help8-text" class = "help-text" >'; 
  $html .= __('"Remove extra JS loaded on Rule page" => 
  Set this to "yes" if you want to see the full rule structures which produce any error messages. **ONLY** should be used during testing.', 'vtprd'); 
  $html .= '<br><br>';
  $html .= '</p>';  
  
	echo $html;
}

    //v2.0.0.8
function vtprd_remove_extra_css_callback () {    //opt61
	$options = get_option( 'vtprd_setup_options' );	
  if ($options['remove_all_extra_css_from_rule_page'] <= ' ') {
    $options['remove_all_extra_css_from_rule_page'] = 'no';
  }  
	$html = '<select id="remove_all_extra_css_from_rule_page" name="vtprd_setup_options[remove_all_extra_css_from_rule_page]">';
	$html .= '<option value="yes"' . selected( $options['remove_all_extra_css_from_rule_page'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['remove_all_extra_css_from_rule_page'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help8" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help8-text" class = "help-text" >'; 
  $html .= __('"Remove extra css loaded on Rule page" => 
  Set this to "yes" if you want to see the full rule structures which produce any error messages. **ONLY** should be used during testing.', 'vtprd'); 
  $html .= '<br><br>';
  $html .= '</p>';  
  
	echo $html;
}

//v1.1.5
function vtprd_allow_license_info_reset_callback () {    //opt56
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="allow_license_info_reset" name="vtprd_setup_options[allow_license_info_reset]">';
	$html .= '<option value="yes"' . selected( $options['allow_license_info_reset'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['allow_license_info_reset'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
	echo $html;
}

function vtprd_custom_checkout_css_callback() {    //opt9
  $options = get_option( 'vtprd_setup_options' );
  $html = '<textarea type="text" id="custom_checkout_css"  rows="200" cols="40" name="vtprd_setup_options[custom_checkout_css]">' . $options['custom_checkout_css'] . '</textarea>';

  $html .= '<a id="help9" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
   
  $html .= '<p id="help9-text" class = "help-text" >'; 
  $html .= __('"Custom Error Message CSS at Checkout Time" => 
          The CSS used for maximum amount error messages is supplied.  If you want to override any of the css, supply just your overrides here. ', 'vtprd')
          .'<br>'. 
          __('For Example => div.vtprd-error .red-font-italic {color: green;}', 'vtprd'); 
  $html .= '</p>';  
  
	echo $html;
}


function vtprd_use_plugin_front_end_css_callback() {    //opt19                                               Use the Plugin CSS file for Discount Display
  $options = get_option( 'vtprd_setup_options' );
	$html = '<select id="use_plugin_front_end_css" name="vtprd_setup_options[use_plugin_front_end_css]">';
	$html .= '<option value="yes"' . selected( $options['use_plugin_front_end_css'], 'yes', false) . '>'   . __('Yes - Use the Plugin CSS file ', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['use_plugin_front_end_css'], 'no', false) . '>'    . __('No - Don"t use the Plugin CSS file ', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help19" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br><br><em>&nbsp;&nbsp;';
  $html .= __('(Shutting off the Plugin CSS file allows you to create your own custom CSS and place it in your theme CSS file directly.)', 'vtprd');
  $html .=  '</em>';
     
  $html .= '<p id="help19-text" class = "help-text" >'; 
  $html .= __('An alternative to supplying custom override CSS in the options here, is to shut off the plugin front end
              CSS entirely.  This would allow you to supply all the CSS relevant to this plugin yourself,
              altered to suit, in your Theme.', 'vtprd'); 
  $html .= '</p><br><br>';  
  
	echo $html;
}

function vtprd_checkout_credit_detail_label_callback() {    //opt10
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<input type="text" class="smallText" id="checkout_credit_detail_label" name="vtprd_setup_options[checkout_credit_detail_label]" value="' . $options['checkout_credit_detail_label'] . '">';
  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help10" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= '<i>'. __( '(suggested: "-" (minus sign) or "cr" (credit) )', 'vtprd' ) .'</i>';
  
  $html .= '<p id="help10-text" class = "help-text" >'; 
  $html .= __('When showing a checkout credit detail line, this is a label which is just to the left of the currency sign, indicating that this is a credit.', 'vtprd'); 
  $html .= '</p><br><br>';
  	
	echo $html;
}

function vtprd_checkout_credit_total_label_callback() {    //opt11
	$options = get_option( 'vtprd_setup_options' );	//<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="smallText" id="checkout_credit_total_label"  name="vtprd_setup_options[checkout_credit_total_label]" value="' . $options['checkout_credit_total_label'] . '">';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help11" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= '<i>'. __( '(suggested: "-" (minus sign) or "cr" (credit) )', 'vtprd' ) .'</i>';
    
  $html .= '<p id="help11-text" class = "help-text" >'; 
  $html .= __('When showing a checkout credit total line, this is a label which is just to the left of the currency sign, indicating that this is a credit.', 'vtprd'); 
  $html .= '</p><br><br></div>';
  	
	echo $html;
}


function vtprd_cartWidget_credit_detail_label_callback() {    //opt28
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="smallText" id="cartWidget_credit_detail_label" name="vtprd_setup_options[cartWidget_credit_detail_label]" value="' . $options['cartWidget_credit_detail_label'] . '">'; //v1.0.9.3
  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help28" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= '<i>'. __( '(suggested: "-" (minus sign) or "cr" (credit) )', 'vtprd' ) .'</i>';
    
  $html .= '<p id="help28-text" class = "help-text" >'; 
  $html .= __('When showing a cartWidget credit detail line, this is a label which is just to the left of the currency sign, indicating that this is a credit.', 'vtprd'); 
  $html .= '</p><br><br></div>';
  	
	echo $html;
}

function vtprd_cartWidget_credit_total_label_callback() {    //opt29
	$options = get_option( 'vtprd_setup_options' );	 //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="smallText" id="cartWidget_credit_total_label"  name="vtprd_setup_options[cartWidget_credit_total_label]" value="' . $options['cartWidget_credit_total_label'] . '">';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help29" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= '<i>'. __( '(suggested: "-" (minus sign) or "cr" (credit) )', 'vtprd' ) .'</i>';
  
  $html .= '<p id="help29-text" class = "help-text" >'; 
  $html .= __('When showing a cartWidget credit total line, this is a label which is just to the left of the currency sign, indicating that this is a credit.', 'vtprd'); 
  $html .= '</p><br><br></div>';
  	
	echo $html;
}
function vtprd_checkout_credit_subtotal_title_callback() {    //opt30  
	$options = get_option( 'vtprd_setup_options' );	//<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="largeText" id="checkout_credit_detail_label" name="vtprd_setup_options[checkout_credit_subtotal_title]" value="' . $options['checkout_credit_subtotal_title'] . '">';
  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help30" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help30-text" class = "help-text" >'; 
  $html .= __('When showing a checkout credit detail line, this is title.', 'vtprd')  
          .'<br><br>'.
          __('Default value = "Subtotal - Cart Purchases:".', 'vtprd'); 
  $html .= '</p></div>';   
    	
	echo $html;
}

function vtprd_checkout_credit_total_title_callback() {    //opt31
	$options = get_option( 'vtprd_setup_options' );	//<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="largeText" id="checkout_credit_total_title"  name="vtprd_setup_options[checkout_credit_total_title]" value="' . $options['checkout_credit_total_title'] . '">';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help31" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help31-text" class = "help-text" >'; 
  $html .= __('When showing a checkout credit total line, this is a title.', 'vtprd') 
        .'<br><br>'.
        __('Default value = "Cart Discount Total:".', 'vtprd'); 
  $html .= '</p></div>';  
     	
	echo $html;
}

/*
function vtprd_show_checkout_credit_total_when_coupon_active_callback() {    //opt45
  $options = get_option( 'vtprd_setup_options' );
	$html = '<select id="show_checkout_credit_total_when_coupon_active" name="vtprd_setup_options[show_checkout_credit_total_when_coupon_active]">';
	$html .= '<option value="yes"' . selected( $options['show_checkout_credit_total_when_coupon_active'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['show_checkout_credit_total_when_coupon_active'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help45" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help45-text" class = "help-text" >'; 
  $html .= __('At checkout, some themes already show the discount total when a coupon is present.  This switch allows you to turn off this plugin"s credit total line.', 'vtprd'); 
  $html .= '</p>';  
  
	echo $html;
}
*/
function vtprd_checkout_new_subtotal_line_callback() {    //opt43
  $options = get_option( 'vtprd_setup_options' );  //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
	$html = '<div class="unitPriceOrCoupon"> <select id="checkout_new_subtotal_line" name="vtprd_setup_options[checkout_new_subtotal_line]">';
	$html .= '<option value="yes"' . selected( $options['checkout_new_subtotal_line'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['checkout_new_subtotal_line'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help43" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
    
  $html .= '<p id="help43-text" class = "help-text" >'; 
  $html .= __('(If you want a new subtotal line to show after the Purchased Products and Discounts have been totaled, and your theme does not already do so...)', 'vtprd'); 
  $html .= '</p></div>';
  
	echo $html;
}


function vtprd_checkout_new_subtotal_label_callback() {    //opt44
	$options = get_option( 'vtprd_setup_options' );	//<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide 
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="largeText" id="checkout_new_subtotal_label" name="vtprd_setup_options[checkout_new_subtotal_label]" value="' . $options['checkout_new_subtotal_label'] . '">';
  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help44" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help44-text" class = "help-text" >'; 
  $html .= __('If you want a new subtotal line to show after the Purchased Products and Discounts have been totaled, and your theme does not already do so, this is the label to use.', 'vtprd')  
           .'<br><br>'.
           __('Default value = "Subtotal with Discount"', 'vtprd'); 
  $html .= '</p></div>';
  	
	echo $html;
}

function vtprd_cartWidget_new_subtotal_line_callback() {    //opt45
  $options = get_option( 'vtprd_setup_options' );   //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
	$html = '<div class="unitPriceOrCoupon"> <select id="cartWidget_new_subtotal_line" name="vtprd_setup_options[cartWidget_new_subtotal_line]">';
	$html .= '<option value="yes"' . selected( $options['cartWidget_new_subtotal_line'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['cartWidget_new_subtotal_line'], 'no', false) . '>'    . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help45" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
    
  $html .= '<p id="help45-text" class = "help-text" >'; 
  $html .= __('(If you want a new subtotal line to show after the Purchased Products and Discounts have been totaled, and your theme does not already do so...)', 'vtprd'); 
  $html .= '</p></div>';
  
	echo $html;
}


function vtprd_cartWidget_new_subtotal_label_callback() {    //opt46
	$options = get_option( 'vtprd_setup_options' );	 //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="largeText" id="cartWidget_new_subtotal_label" name="vtprd_setup_options[cartWidget_new_subtotal_label]" value="' . $options['cartWidget_new_subtotal_label'] . '">';
  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help46" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help46-text" class = "help-text" >'; 
  $html .= __('If you want a new subtotal line to show after the Purchased Products and Discounts have been totaled, and your theme does not already do so, this is the label to use.', 'vtprd')  
           .'<br><br>'.
           __('Default value = "Subtotal with Discount"', 'vtprd'); 
  $html .= '</p></div>';
  	
	echo $html;
}




function vtprd_cartWidget_credit_subtotal_title_callback() {    //opt32
	$options = get_option( 'vtprd_setup_options' );	 //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="mediumText" id="cartWidget_credit_detail_label" name="vtprd_setup_options[cartWidget_credit_subtotal_title]" value="' . $options['cartWidget_credit_subtotal_title'] . '">';
  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help32" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help32-text" class = "help-text" >'; 
  $html .= __('When showing a cartWidget credit detail line, this is title.', 'vtprd') 
          .'<br><br>'.
          __('Default value = "Products:".', 'vtprd'); 
  $html .= '</p></div>';
  	
	echo $html;
}

function vtprd_cartWidget_credit_total_title_callback() {    //opt33
	$options = get_option( 'vtprd_setup_options' );	 //<span class="unitPriceOrCoupon">  added v1.0.9.0 , is accessed for show/hide
  $html = '<div class="unitPriceOrCoupon"> <input type="text" class="mediumText" id="cartWidget_credit_total_title"  name="vtprd_setup_options[cartWidget_credit_total_title]" value="' . $options['cartWidget_credit_total_title'] . '">';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help33" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help33-text" class = "help-text" >'; 
  $html .= __('When showing a cartWidget credit total line, this is a title.', 'vtprd') 
          .'<br><br>'.
          __('Default value = "Discounts:".', 'vtprd'); 
  $html .= '</p></div>';
  	
	echo $html;
}
/*
function vtprd_cartWidget_html_colspan_value_callback() {    //opt12
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<input type="text" class="smallText" id="cartWidget_html_colspan_value"  name="vtprd_setup_options[cartWidget_html_colspan_value]" value="' . $options['cartWidget_html_colspan_value'] . '">';

  $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="help12" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help12-text" class = "help-text" >'; 
  $html .= __('Controls the overall width of the Cart Widget Discount display lines.   Test extensively before releasing any changes into the wild.  Pericoloso.', 'vtprd')  
          .'<br><br>'.
          __('Default value = 5', 'vtprd'); 
  $html .= '</p>';
  	
	echo $html;
}
*/


//***************************
//v2.0.2.0 new function
//***************************
function vtprd_lifetime_limit_by_user_callback () {   //opt64
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-user" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_user]">';
	$html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_user'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_user'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help64" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help64-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by USER" => When using Customer Rule Limits, use USER to identify the customer.  Immediately when User is Logged IN.', 'vtprd'); 
  $html .= '</p>'; 

	echo $html;
}

function vtprd_lifetime_limit_by_ip_callback () {   //opt13
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-ip" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_ip]">';
	$html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_ip'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_ip'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';

  $html .= '<a id="help13" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help13-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by IP" => When using Customer Rule Limits, use IP to identify the customer.  Immediately avalable at Add to Cart time.', 'vtprd'); 
  $html .= '</p>';
    
  /* //v2.0.2.0 removed
  $html .= '<p><em>&nbsp;&nbsp;';
  $html .= __('This switch should always be set to "Yes" - check by ip is done at shortcode time, add to cart time and checkout time.', 'vtprd');
  $html .=  '</em></p>';  
  //Heading for the next section, with description      
  $html .= '<p id="lifetime-by-other-switches-intro" class="extra-intro">'; 
  $html .= __('<span>The remainder of the Customer Rule Limit checks by user-supplied info, only happen when the User clicks the "Pay" button at Checkout Time .', 'vtprd') 
        .'<br>&nbsp;&nbsp;&nbsp;'.
        __(' - The Customer Rule Limit checks are applied, and if the discount amounts have to be reduced, the User is returned to Checkout.', 'vtprd')
        .'<br>&nbsp;&nbsp;&nbsp;'.
        __(' - An error message can be displayed, highlighting the discount and total changes.  When the User accepts the discount reduction
        and hits the "Pay" button again, the transaction is then processed.</span>', 'vtprd'); 
  $html .= '</p>';
  */  
  
	echo $html;
}
  
function vtprd_lifetime_limit_by_email_callback () {   //opt14
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-email" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_email]">';	
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_email'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
  $html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_email'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';  
	$html .= '</select>';

  $html .= '<a id="help14" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help14-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by Email" => When using Customer Rule Limits, use email to identify the customer.', 'vtprd'); 
  $html .= '</p>';  
	echo $html;
}
  
function vtprd_lifetime_limit_by_billto_name_callback () {   //opt15
  $options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-billto-name" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_billto_name]">';	
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_billto_name'], 'no', false) . '>'   . __('No', 'vtprd') . '</option>';
  $html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_billto_name'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';  
	$html .= '</select>';

  $html .= '<a id="help15" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help15-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by Billto Name" => When using Customer Rule Limits, use billto name to identify the customer.', 'vtprd'); 
  $html .= '</p>'; 
	echo $html;
}  

  
function vtprd_lifetime_limit_by_billto_addr_callback () {   //opt16
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-billto-addr" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_billto_addr]">';
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_billto_addr'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
  $html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_billto_addr'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';	
	$html .= '</select>';

  $html .= '<a id="help16" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help16-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by Billto addr" => When using Customer Rule Limits, use billto addr to identify the customer.', 'vtprd'); 
  $html .= '</p>';  
	echo $html;
}
  
function vtprd_lifetime_limit_by_shipto_name_callback () {   //opt17
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-shipto-name" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_shipto_name]">';
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_shipto_name'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
  $html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_shipto_name'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';	
	$html .= '</select>';

  $html .= '<a id="help17" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help17-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by Shipto Name" => When using Customer Rule Limits, use shipto name to identify the customer.', 'vtprd'); 
  $html .= '</p>';  
	echo $html;
}
  
function vtprd_lifetime_limit_by_shipto_addr_callback () {   //opt18
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="vtprd-lifetime-limit-by-shipto-addr" name="vtprd_setup_options[max_purch_rule_lifetime_limit_by_shipto_addr]">';
	$html .= '<option value="no"'  . selected( $options['max_purch_rule_lifetime_limit_by_shipto_addr'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
  $html .= '<option value="yes"' . selected( $options['max_purch_rule_lifetime_limit_by_shipto_addr'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';	
	$html .= '</select>';

  $html .= '<a id="help18" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';

  $html .= '<p id="help18-text" class = "help-text" >'; 
  $html .= __('"Check Customer against Rule Purchase History, by Shipto addr" => When using Customer Rule Limits, use shipto addr to identify the customer.', 'vtprd'); 
  $html .= '</p>';  
	echo $html;
}
/*  
function vtprd_checkout_forms_set_callback () {   //opt38 
  $options = get_option( 'vtprd_setup_options' );
  $html = '<textarea type="text" id="max_purch_checkout_forms_set"  rows="1" cols="20" name="vtprd_setup_options[max_purch_checkout_forms_set]">' . $options['max_purch_checkout_forms_set'] . '</textarea>';
  $html .= '<a id="help38" class="help-anchor" href="javascript:void(0);" >' .  __('More Info', 'vtprd') . '</a>';

  $html .= '<p id="help38-text" class = "help-text" >'; 
  $html .= __('"Default checkout formset containing "billingemail" etc, is formset "0".  Should you wish to create a custom formset to administer the basic addressing of "billingemail" etc,
  it must duplicate all the internals of the default formset (name column can contain any value, though).', 'vtprd'); 
  $html .= '</p>'; 
  
  //Heading for the next section, with description      
  $html .= '<p id="lifetime-error-msg-intro" class="extra-intro">'; 
  $html .= '<strong>'. __('Lifetime Rule Checkout Button Error Options (See => )', 'vtprd') .'</strong>'; 
  $html .= '<a id="help41a" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';  
  $html .= '</p>'; 
   
  $html .= '<p id="help41a-text" class = "help-text" >'; 
  $html .= __('"Customer Rule Limit -  Checkout Button Error Options" => Customer Rule Limits can be based on IP, which is available at all times.  However, email address, shipto and soldto address
        are best verified at Payment Button click time.  The system rechecks any Customer Rule Limits, and if historical purchases are found, the discount amount is reduced in combination with with the purchase history
        which has now been found.  In order to alert the customer to the change in the discount amount, the screen returns to the Checkout screen, with this fields error message displayed.
        The default error message informs the user why the discount has been reduced, and invites the purchaser to accept the reduced discount, and click on the Payment button a second time, to carry on
        to the payment gateway.', 'vtprd')
        .'<br><br>'.
        __('Default = "' .VTPRD_CHECKOUT_BUTTON_ERROR_MSG_DEFAULT  . '".', 'vtprd'); 
  $html .= '</p>';    
 
	echo $html;
}
*/
function vtprd_before_checkout_products_selector_callback() {    //opt39
  $options = get_option( 'vtprd_setup_options' );
  $html = '<textarea type="text" id="show_error_before_checkout_products_selector"  rows="1" cols="20" name="vtprd_setup_options[show_error_before_checkout_products_selector]">' . $options['show_error_before_checkout_products_selector'] . '</textarea>';

  $html .= '<a id="help39" class="help-anchor" href="javascript:void(0);" >' . __('More Info', 'vtprd') . '</a>';
   
  $html .= '<p id="help39-text" class = "help-text" >'; 
  $html .= __('"Show Error Messages Just Before Checkout Products List - HTML Selector" => 
        This option controls the location of the message display.', 'vtprd') 
        .'<br><br>'. __('Blank = do not use. ', 'vtprd')
        .'<br><br>'. __('Default = "', 'vtprd') .VTPRD_CHECKOUT_PRODUCTS_SELECTOR_BY_PARENT . '".'   
        .'<br><br>'. __('If you"ve changed this value and can"t get it to work, you can use the "reset to defaults" button (just below the "save changes" button) to get the value back (snapshot your other settings first to help you quickly set the other settings back the way to what you had before.)', 'vtprd'); 
  $html .= '</p>';    
  $html .= '<br><em>&nbsp;&nbsp;';
  $html .= __('Blank = do not display error message before Checkout Products List', 'vtprd');
  $html .=  '</em><br><br>';  
  
	echo $html;
}

function vtprd_before_checkout_address_selector_callback() {    //opt40
  $options = get_option( 'vtprd_setup_options' );
  $html = '<textarea type="text" id="show_error_before_checkout_address_selector"  rows="1" cols="20" name="vtprd_setup_options[show_error_before_checkout_address_selector]">' . $options['show_error_before_checkout_address_selector'] . '</textarea>';

  $html .= '<a id="help40" class="help-anchor" href="javascript:void(0);" >' . __('More Info', 'vtprd') . '</a>';
   
  $html .= '<p id="help40-text" class = "help-text" >'; 
  $html .= __('"Show Error Messages Just Before Checkout Address  List - HTML Selector" => 
        This option controls the location of the message display.', 'vtprd') 
        .'<br><br>'. __('Blank = do not use. ', 'vtprd')
        .'<br><br>'. __('Default = "', 'vtprd') .VTPRD_CHECKOUT_ADDRESS_SELECTOR_BY_PARENT . '".'   
        .'<br><br>'. __('If you"ve changed this value and can"t get it to work, you can use the "reset to defaults" button (just below the "save changes" button) to get the value back (snapshot your other settings first to help you quickly set the other settings back the way to what you had before.)', 'vtprd');   
  $html .= '</p>';   
  $html .= '<br><em>&nbsp;&nbsp;';
  $html .= __('Blank = do not display error message before Checkout Address List', 'vtprd');
  $html .=  '</em><br><br>';
      
	echo $html;
}


function vtprd_lifetime_purchase_button_error_msg_callback() {    //opt41
  $options = get_option( 'vtprd_setup_options' );
  
  //REMOVE any line breaks, etc, which would cause a JS error !! 
  $tempMsg =    str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $options['lifetime_purchase_button_error_msg']);
  $options['lifetime_purchase_button_error_msg'] = $tempMsg;
 
  $html = '<textarea type="text" id="lifetime_purchase_button_error_msg"  rows="200" cols="40" name="vtprd_setup_options[lifetime_purchase_button_error_msg]">' . $options['lifetime_purchase_button_error_msg'] . '</textarea>';

  $html .= '<a id="help41" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
   
  $html .= '<p id="help41-text" class = "help-text" >'; 
  $html .= __('"Customer Rule Limit - Payment Button Error Message" => Customer Rule Limits can be based on IP, which is available at all times.  However, email address, shipto and soldto address
        are best verified at Payment Button click time.  The system rechecks any Customer Rule Limit rules, and if historical purchases are found, the discount amount is reduced in combination with the purchase history
        which has now been found.  In order to alert the customer to the change in the discount amount, the screen returns to the Checkout screen, with this fields error message displayed.
        The default error message informs the user why the discount has been reduced, and invites the purchaser to accept the reduced discount, and click on the Payment button a second time, to carry on
        to the payment gateway.', 'vtprd')
        .'<br><br>'.
        __('Default = "' .VTPRD_CHECKOUT_BUTTON_ERROR_MSG_DEFAULT  . '".', 'vtprd'); 
  $html .= '</p>';  
  
	echo $html;
}

//v1.0.9.0 begin

function vtprd_discount_taken_where_callback () {    //opt48
	$options = get_option( 'vtprd_setup_options' );	                                                                                                             
	$html = '<select id="discount_taken_where" name="vtprd_setup_options[discount_taken_where]">';
	$html .= '<option id="discountUnitPrice" value="discountUnitPrice"'   . selected( $options['discount_taken_where'], 'discountUnitPrice', false)  . '>'  . __('** Unit Price Discount **', 'vtprd') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __('(unit price reduced for discount)', 'vtprd')  . '&nbsp; </option>';
	$html .= '<option id="discountCoupon"    value="discountCoupon"'      . selected( $options['discount_taken_where'], 'discountCoupon', false)     . '>'  . __('** Coupon Discount **', 'vtprd') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'  . __('(Discount shown in a separate coupon [most accurate] )', 'vtprd')  . '&nbsp; </option>';
	$html .= '</select>';

  $html .= '<a id="help48" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
 
  $html .= '<br><br><p><em>';
  $html .= __( 'When **switching between **  Unit Price discount / Coupon discount, please be sure to empty your cart.
            and go back to the shop page.  Then you can carry on adding to cart and testing.', 'vtprd' );
  $html .=  '</em></p>'; 
  
  $html .= '<p id="help48-text" class = "help-text" >'; 
  //v1.1.1 message reworded
  $html .= __('Pricing Deal discounts can now be shown in one of two modes.', 'vtprd')
        .'<br><br>'.
        __('<strong>COUPON DISCOUNT</strong> - In this mode, Pricing Deals reports on the discounts *in its own area*, after the 
        <br> cart is summarized by Woo.  The actual discount total is supplied to Woo via a Pricing Deal Coupon, called "Deals".   
        <br> The discount is also reported in the mini-cart, and is shown applied *using the "Deals" Coupon,
        <br> on the Cart and Checkout pages. 
        <br> This mode is the most accurate way to apply discounts, as the total discount is always accurate.', 'vtprd')
        .'<br><br>'.
        __('<strong>UNIT COST DISCOUNT</strong> - Applies the Rule discount directly to the affected Product"s Unit Price.  
        <br> For example, if an Apple costs $100 and there is a $10 discount per apple,
        <br> the unit price is reduced to $90.', 'vtprd')
        .'<br><br>'.
        __('However, if Apples are $100 and the Rule is $10 off only the 1st Apple, 
        <br>  and 3 Apples are purchased, 
        <br>  **the discount cannot be accurately represented**.
        <br><br>
        - the discount total should be $100 * 3 = $300 - $10 discount = $290.
        <br>
        - $290 (total for 3 units) / 3 =  $96.6666667 unit price 
        <br>
        - BUT $96.66 * 3 = $289.98 
        <br> 
        - AND $96.67 * 3 = $290.01 
        <br><br>
        So in this case, neither discount is accurate.
        <br><br>
        So when Unit Price Discount is selected, you are presented with this option:
        <br>
        "Give More or Less - Unit Price discount"
        <br><br>
        In this case:
        <br>
        More Discount = $289.98
        <br>
        Less Discount = $290.01 
        ', 'vtprd');                 
  $html .= '</p>';

	echo $html;

}

function vtprd_give_more_or_less_discount_callback () {    //opt49
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<div class="unitPriceOnly"> <select id="give_more_or_less_discount" name="vtprd_setup_options[give_more_or_less_discount]">';
	$html .= '<option id="discountMore"    value="more"'      . selected( $options['give_more_or_less_discount'], 'more', false)  . '>'  . __('Give More Discount', 'vtprd') . '&nbsp; </option>';
	$html .= '<option id="discountLess"    value="less"'      . selected( $options['give_more_or_less_discount'], 'less', false)  . '>'  . __('Give Less Discount', 'vtprd') . '&nbsp; </option>';
	$html .= '</select>';
  
  $html .= '&nbsp;&nbsp;&nbsp;<em>' . __( '(if the discount does not divide out equally)', 'vtprd' )  . '</em>&nbsp;&nbsp;&nbsp;'  ; 
  
  $html .= '<a id="help49" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help49-text" class = "help-text" >'; 
  $html .=  __('If Apples are $100 and the Rule is $10 off only the 1st Apple, and 3 Apples are purchased, the discount cannot be accurately represented.
        In this case, the discount is $10 / 3 = $3.3333 and the unit price is $100 - $3.33 (or $3.34) ... = $99.99 or $100.02 .  Neither one of these multiplies out
        accurately to a $10 discount, so we have to choose whether to give more discount or less.... .', 'vtprd') 
        .'<br><br><strong>'.
        __('33.33 * 3 = 99.99 ==>> this would be the "More Discount" setting', 'vtprd') 
        .'</strong><br><strong>'.
        __('33.34 * 3 = 100.02 ==>> this would be the "Less Discount" setting', 'vtprd') .'</strong>';    
  $html .= '</p></div>'; 
  
	echo $html;
}

//****************
//v2.0.0.5 begin
//****************
function vtprd_coupon_discount_coupon_name_callback() {    //opt58
	$options = get_option( 'vtprd_setup_options' );	
  
  if ($options['coupon_discount_coupon_name'] <= ' ') {
    //accept the filter
    $coupon_title = (apply_filters('vtprd_coupon_code_discount_title','' ));
    if (!$coupon_title) { 
      $coupon_title = 'Deals';
    } 
    if ($options['discount_taken_where'] == 'discountCoupon') { //v2.0.0.8 fixed '$options'
      //always check if the manually created coupon codes are there - if not create them.
//error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS', true ) );         
      update_option( 'woocommerce_enable_coupons','yes'); 
      $auto = true;
      vtprd_woo_maybe_create_coupon_types($coupon_title,$auto);
   //error_log( print_r(  'Execute function vtprd_build_inline_front_end_css, 002', true ) );         
      vtprd_build_inline_front_end_css();
    }
    $options['coupon_discount_coupon_name'] = $coupon_title;
  }
  
  $html = '<div class="unitPriceOrCoupon"><br><input type="text" class="" id="coupon_discount_coupon_name"  rows="1" cols="100" name="vtprd_setup_options[coupon_discount_coupon_name]" value="' . $options['coupon_discount_coupon_name'] . '">';

  $html .= '<a id="help58" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p><em>';
  $html .= __( '* Coupon Discount * is displayed as a single auto-inserted Coupon in the Cart Totals, showing Total Discount value. 
        You can change the auto-inserted Coupon name here.', 'vtprd' );
  $html .=  '</em></p>';  
  $html .= '<p id="help58-text" class = "help-text" >'; 
  $html .= __('Select the Title of your choice, for the auto-inserted coupon name displayed showing the TOTAL discount.', 'vtprd');
  $html .= '</p><br><br></div>';
	echo $html;
}


function vtprd_show_woo_coupon_cart_totals_00_line_callback () {   //opt59
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<select id="show_woo_coupon_cart_totals_00_line" name="vtprd_setup_options[show_woo_coupon_cart_totals_00_line]">';	
  $html .= '<option value="yes"' . selected( $options['show_woo_coupon_cart_totals_00_line'], 'yes', false) . '>'   . __('Yes', 'vtprd') .  '&nbsp;</option>';
  $html .= '<option value="no"'  . selected( $options['show_woo_coupon_cart_totals_00_line'], 'no', false)  . '>'   . __('No', 'vtprd') . '</option>';
	$html .= '</select>';
  $html .= '<a id="help59" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') ;
  //$html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __( '(the switch only applies to prices discounted by Pricing Deals) ', 'vtprd' ) .  '</a>'; //v1.0.9.3
  $html .= '<p id="help59-text" class = "help-text" >'; 
  $html .= __('If "no" selected, do not show the cart totals line on a rule activated by a coupon code.', 'vtprd'); 
  $html .= '</p>';
  
	echo $html;
}
//v2.0.0.5 end


//v1.0.9.3 begin     
function vtprd_show_unit_price_cart_discount_crossout_callback () {    //opt51
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<div class="unitPriceOnly"> <select id="show_unit_price_cart_discount_crossout" name="vtprd_setup_options[show_unit_price_cart_discount_crossout]">';
	$html .= '<option id="discountYesCrossout"    value="yes"'      . selected( $options['show_unit_price_cart_discount_crossout'], 'yes', false)  . '>'  . __('Yes', 'vtprd') . '&nbsp; </option>';
	$html .= '<option id="discountNoCrossout"     value="no"'       . selected( $options['show_unit_price_cart_discount_crossout'], 'no', false)   . '>'  . __('No', 'vtprd') . '&nbsp; </option>';
	$html .= '</select>'; 
  $html .= '&nbsp;&nbsp;&nbsp;<em>' . __( '(Cart Discount Only)', 'vtprd' )  . '</em>&nbsp;&nbsp;&nbsp;'  ; 

  $html .= '<a id="help51" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help51-text" class = "help-text" >'; 
  $html .= __('If you select, when showing discount in the Unit Price field, you can show the current Catalog unit price crossed out, followed by the Cart rule discounted Unit Price.', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
}
     
function vtprd_show_unit_price_cart_discount_computation_callback () {    //opt52
	$options = get_option( 'vtprd_setup_options' );	
	$html = '<div class="unitPriceOnly"> <select id="give_more_or_less_discount" name="vtprd_setup_options[show_unit_price_cart_discount_computation]">';
	$html .= '<option id="discountYesShowComputation"    value="yes"'      . selected( $options['show_unit_price_cart_discount_computation'], 'yes', false)  . '>'  . __('Yes', 'vtprd') . '&nbsp; </option>';
	$html .= '<option id="discountNoShowComputation"     value="no"'       . selected( $options['show_unit_price_cart_discount_computation'], 'no', false)   . '>'  . __('No', 'vtprd') . '&nbsp; </option>';
	$html .= '</select>'; 
  
  $html .= '&nbsp;&nbsp;&nbsp;<em>' . __( '(For Testing only)', 'vtprd' )  . '</em>&nbsp;&nbsp;&nbsp;'  ; 
  
  $html .= '<a id="help52" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  
  $html .= '<p id="help52-text" class = "help-text" >'; 
  $html .= __('If there is a Cart-rule based Unit Price discount, the computations forming the basis of that discount number are shown in the detail reporting below the cart summary.', 'vtprd'); 
  $html .= '</p></div>'; 
  
	echo $html;
}

function vtprd_unit_price_cart_savings_message_callback() {    //opt53
	$options = get_option( 'vtprd_setup_options' );	
  $html = '<div class="unitPriceOnly"> <input type="text" class="" id="unit_price_cart_savings_message"  rows="1" cols="100" name="vtprd_setup_options[unit_price_cart_savings_message]" value="' . $options['unit_price_cart_savings_message'] . '">';
  $html .= '<a id="help53" class="help-anchor" href="javascript:void(0);" >'   . __('More Info', 'vtprd') .  '</a>';
  $html .= '<p><em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  $html .= __( 'Custom Message appearing on Cart Page, Checkout Page, Thankyou and Customer Email. This could be, for example, " You Saved 25% !". 
        You can also have info substituted into this message using one of the following: {cart_save_percent}, {cart_save_amount}.', 'vtprd' );
  $html .=  '</em></p>';  
  $html .= '<p id="help53-text" class = "help-text" >'; 
  $html .= __('Set a Custom Checkout Message to underscore the customer savings!', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('Define text to show on Cart Page, Checkout Page, Thankyou and Customer Email. This could be, for example, "You Saved 25% !",.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('You can also have info substituted into this message using one of the following: {cart_save_percent}, {cart_save_amount}.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('So you can represent "Save xx" by putting in "Save {price_save_percent}"  and the plugin will automatically fill in the saved percentage as "25%".', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('{cart_save_amount} is formatted with currency symbol.', 'vtprd');
  $html .= '<br><br>&nbsp;' .  __('(CSS class "cart-savings-message")', 'vtprd'); 
  $html .= '</p><br><br></div>';
	echo $html;
}

  //v1.0.9.3 end 
     

function vtprd_check_memory_limit() {    
		//from woocommerce/includes/admin/views/html-admin-page-status-report.php
    
    //******************************
    //v1.1.0.5  added function exists check ==>> pre woo 2.1 can go boom!
    //******************************
    if (function_exists('wc_let_to_num')) {
    
      $memory = wc_let_to_num( WP_MEMORY_LIMIT );
  
  		if ( $memory < 67108864 ) {
  			echo '<strong>WP Memory Limit: ' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'woocommerce' ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) .'</strong>';
        //echo '<mark class="error">WP Memory Limit: ' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'woocommerce' ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</mark>';
  		}
     
    }
    return;
}

//v2.0.2.0 leave as is
function vtprd_destroy_session() {    
    if(!isset($_SESSION)){
      session_start();
      header("Cache-Control: no-cache");
      header("Pragma: no-cache");
    }    
    session_destroy(); 
    return;
}
//v1.0.9.0 end

  function vtprd_enqueue_setup_scripts($hook_suffix) {
    switch( $hook_suffix) {        //weird but true
      case 'vtprd-rule_page_vtprd_setup_options_page':  
      case 'vtprd-rule_page_vtprd_show_help_page':  
      case 'vtprd-rule_page_vtprd_show_faq_page':              
        wp_register_style('vtprd-admin-style', VTPRD_URL.'/admin/css/vtprd-admin-style-' .VTPRD_ADMIN_CSS_FILE_VERSION. '.css' );  //v1.1.0.7
        wp_enqueue_style ('vtprd-admin-style');
        wp_register_style('vtprd-admin-settings-style', VTPRD_URL.'/admin/css/vtprd-admin-settings-style.css' );  
        wp_enqueue_style ('vtprd-admin-settings-style');
        wp_register_script('vtprd-admin-settings-script', VTPRD_URL.'/admin/js/vtprd-admin-settings-script-v003.js' );  
        wp_enqueue_script ('vtprd-admin-settings-script');
      break;
    }
  }    

  //**************************
  //v2.0.2.0 new function
  //**************************
  /*
  set uninstall to use  uninstall.php to delete everything.
  this transient lasts 3 days until standard cleanup 
  */
  function vtprd_set_to_deleteALL_on_UnInstall() {
    // set option which is read on plugin Uninstall action
    update_option( 'vtprd_deleteALL_on_UnInstall','yes');
    
    // show message that the button click set the setting
    update_option('vtprd_deferred_setup_admin_notices', '"Delete ALL on Uninstall" SET');
  }
  
  //**************************
  //v2.0.2.0 new function
  //**************************  
  function vtprd_unset_deleteALL() {
    $option_set = get_option( 'vtprd_deleteALL_on_UnInstall');
    if ($option_set) {
      // remove option which would have been read on plugin Uninstall action
      delete_option( 'vtprd_deleteALL_on_UnInstall'); 
     
      // show message that the button click UNset the setting    
      update_option('vtprd_deferred_setup_admin_notices', '"Delete ALL on Uninstall" REMOVED');
    } else {
      update_option('vtprd_deferred_setup_admin_notices', '"Delete ALL on Uninstall" button had not been clicked, so no action required');    
    }
  } 

function vtprd_validate_setup_input( $input ) {

  //did this come from on of the secondary buttons?
  $deleteALL    = ( ! empty($input['deleteALL_on_uninstall']) ? true : false );    //v2.0.2.0
  $UNdeleteALL  = ( ! empty($input['unset_deleteALL']) ? true : false );    //v2.0.2.0
  $reset        = ( ! empty($input['options-reset']) ? true : false );
  $repair       = ( ! empty($input['rules-repair']) ? true : false );
  $nuke_rules   = ( ! empty($input['rules-nuke']) ? true : false );
  $nuke_cats    = ( ! empty($input['cats-nuke']) ? true : false );
  $nuke_hist    = ( ! empty($input['hist-nuke']) ? true : false );
  $nuke_log     = ( ! empty($input['log-nuke']) ? true : false );  
  $nuke_session = ( ! empty($input['session-nuke']) ? true : false );
  $nuke_cart    = ( ! empty($input['cart-nuke']) ? true : false );
  $cleanup      = ( ! empty($input['cleanup']) ? true : false ); //v1.1.5 
 
  $output = get_option( 'vtprd_setup_options' ); //v1.1.6
  switch( true ) { 

    case $reset        === true :    //reset options
        $output = $this->vtprd_set_default_options();  //load up the defaults                
   //error_log( print_r(  'DEFAULT Options before UPDATE= ', true ) );
  //error_log( var_export($output, true ) );        
        
        //as default options are set, no further action, just return
        return apply_filters( 'vtprd_validate_setup_input', $output, $input );
      break;
    case $repair       === true :    //repair rules
        $vtprd_nuke = new vtprd_Rule_delete;            
        $vtprd_nuke->vtprd_repair_all_rules();
        //$output = get_option( 'vtprd_setup_options' );   //v1.1.6
      break;
    case $nuke_rules   === true :
        $vtprd_nuke = new vtprd_Rule_delete;            
        $vtprd_nuke->vtprd_nuke_all_rules();
        //$output = get_option( 'vtprd_setup_options' );    //v1.1.6
      break;
    case $nuke_cats    === true :    
        $vtprd_nuke = new vtprd_Rule_delete;            
        $vtprd_nuke->vtprd_nuke_all_rule_cats();
        //$output = get_option( 'vtprd_setup_options' );   //v1.1.6
      break;
    case $nuke_hist    === true :    
        $vtprd_nuke = new vtprd_Rule_delete;            
        $vtprd_nuke->vtprd_nuke_lifetime_purchase_history();
       // $output = get_option( 'vtprd_setup_options' );   //v1.1.6
      break;
    case $nuke_log    === true :    
        $vtprd_nuke = new vtprd_Rule_delete;            
        $vtprd_nuke->vtprd_nuke_audit_trail_logs();
        //$output = get_option( 'vtprd_setup_options' );   //v1.1.6
      break;      
    case $nuke_session === true :    
        //clear any session variables
        $this->vtprd_destroy_session();
        //$output = get_option( 'vtprd_setup_options' );   //v1.1.6
      break; 

     //v1.1.5 begin  
    case $cleanup === true :    
        update_option('vtprd_license_count', 0 ); //v1.1.6.1
        delete_option('vtprd_rego_clock'); //v1.1.6.1       
        delete_option( 'vtprd_license_options' );
        global $vtprd_license_options;
        $vtprd_license_options = null;
        //$output = get_option( 'vtprd_setup_options' );  //v1.1.6
  //error_log( print_r(  'deleted $vtprd_license_options', true ) );  
      break;  
     //v1.1.5 end   
      
    case $nuke_cart === true :    
        if(defined('WPSC_VERSION') && (VTPRD_PARENT_PLUGIN_NAME == 'WP E-Commerce') ) {
        	 global $wpsc_cart;	
           $wpsc_cart->empty_cart( false );
        }
        //$output = get_option( 'vtprd_setup_options' );   //v1.1.6
      break;
  
    //v2.0.2.0 begin
    case $deleteALL    === true :    //set delete all on plugin delete
        $output = $this->vtprd_set_to_deleteALL_on_UnInstall();  //set uninstall to delete all
      break;

    case $UNdeleteALL    === true :    //unset delete all
        $output = $this->vtprd_unset_deleteALL();  //set uninstall to delete all        
      break;
    //v2.0.2.0 end
          
      
    default:   //standard update button hit...                 
        
        //**************************
        //v2.0.0.5 recoded begin
        //**************************
        // $input = NEW data, $output = OLD data
        //   if "discount_taken_where" changed
        
        $inline_css_built = false;
        
        if ($output['discount_taken_where'] != $input['discount_taken_where'] ) {
            
            //if NEW selection is 'discountCoupon'
            if ($input['discount_taken_where'] == 'discountCoupon') { 
              //always check if the manually created coupon codes are there - if not create them.
          //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS CHANGE to discountCoupon', true ) );         
              update_option( 'woocommerce_enable_coupons','yes'); 
              
              //get rid of old single coupon names
              vtprd_woo_maybe_delete_coupon_types('Deals'); //get rid of OLD Deals coupon              
              vtprd_woo_maybe_delete_all_single_auto_coupons(); // get rid of any others in new format         
              
              $coupon_discount_coupon_name = $input['coupon_discount_coupon_name'];
              
              if ($coupon_discount_coupon_name <= ' ') {       
                //accept the filter
                $coupon_discount_coupon_name = (apply_filters('vtprd_coupon_code_discount_title','' ));
                if (!$coupon_discount_coupon_name) { 
                  $coupon_discount_coupon_name = 'Deals';
                }
              }
              
              $auto = true;
              if ($coupon_discount_coupon_name > ' ') {
                //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS, create supplied name', true ) );
                vtprd_woo_maybe_create_coupon_types($coupon_discount_coupon_name,$auto);
              } else {
                //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS, create default name', true ) );
                vtprd_woo_maybe_create_coupon_types('Deals',$auto);
              }

            } else {        
              
              //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS, change FROM discountCoupon, delete coupons', true ) );
              
               
              //get rid of old single coupon names
              vtprd_woo_maybe_delete_coupon_types('Deals'); //get rid of OLD Deals coupon              
              vtprd_woo_maybe_delete_all_single_auto_coupons(); // get rid of any others in new format                   
              /*
              //get rid of existing single coupon
              $coupon_name = $input['coupon_discount_coupon_name'];
              vtprd_woo_maybe_delete_coupon_types($coupon_name);        
              
              //get rid of old single coupon names
              vtprd_woo_maybe_delete_all_single_auto_coupons(); 
              */
              
              //**clear coupon name**, no longer used now...
              $input['coupon_discount_coupon_name'] = '';
            }
   //error_log( print_r(  'Execute function vtprd_build_inline_front_end_css, 003', true ) );                 
            vtprd_build_inline_front_end_css($input);
            $inline_css_built = true;
                    
        } else {
            
            if ($input['discount_taken_where'] == 'discountCoupon') {
              
              //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS, coupon name CHANGED', true ) );
              
              //can't be BLANK!
              if ($input['coupon_discount_coupon_name'] <= ' ')  { 
                //error_log( print_r(  'vtprd_woo_maybe_create_coupon_types SETUP OPTIONS, blank updated, create default name', true ) );
                $auto = true;
                vtprd_woo_maybe_create_coupon_types('Deals',$auto);
                $input['coupon_discount_coupon_name'] = 'Deals';          
              } 
              
              //if name CHANGED, delete the OLD coupon
      
              if ($output['coupon_discount_coupon_name'] != $input['coupon_discount_coupon_name']) {
                  //delete OLD coupon name!!
                  $coupon_name = $output['coupon_discount_coupon_name'];
                  vtprd_woo_maybe_delete_coupon_types($coupon_name); 
                  
                  //with auto-stored coupons, the coupon name can be stored as all lower case by default
                  $coupon_name = strtolower($coupon_name); 
                  vtprd_woo_maybe_delete_coupon_types($coupon_name);
     //error_log( print_r(  'Execute function vtprd_build_inline_front_end_css, 004', true ) );                   
                  vtprd_build_inline_front_end_css($input);
                  $inline_css_built = true;
                            
              }        
      
            } else {
              //if unneeded, get rid of the entry
              $input['coupon_discount_coupon_name'] = '';
            }
        }
        
        if ( (!$inline_css_built) &&
             ($output['show_woo_coupon_cart_totals_00_line'] != $input['show_woo_coupon_cart_totals_00_line']) ) {
          vtprd_build_inline_front_end_css($input);
        }
        
        //v2.0.0.5 end    

        //$output = get_option( 'vtprd_setup_options' ); //v1.0.7.2  changed from array initialize   //v1.1.6
      	foreach( $input as $key => $value ) {
      		if( isset( $input[$key] ) ) {
      			$output[$key] = strip_tags( stripslashes( $input[ $key ] ) );	
      		} // end if		
      	} // end foreach  
        $this->vtprd_destroy_session(); //v1.0.9.3
             
      break;
  }




     //one of these switches must be on
     if ( (isset ($input['use_lifetime_max_limits']) ) &&  //v1.1.1
          (($input['use_lifetime_max_limits'] == 'no' ) ||
          ($output['use_lifetime_max_limits'] == 'no' ) )
                                &&
        ((($input['max_purch_rule_lifetime_limit_by_ip'] == 'yes' ) ||
          ($input['max_purch_rule_lifetime_limit_by_user'] == 'yes' ) ||     //v2.0.2.0
          ($input['max_purch_rule_lifetime_limit_by_email'] == 'yes' ) ||
          ($input['max_purch_rule_lifetime_limit_by_billto_name'] == 'yes' ) ||
          ($input['max_purch_rule_lifetime_limit_by_billto_addr'] == 'yes' ) ||
          ($input['max_purch_rule_lifetime_limit_by_shipto_name'] == 'yes' ) ||
          ($input['max_purch_rule_lifetime_limit_by_shipto_addr'] == 'yes' ))
                                 ||
         (($output['max_purch_rule_lifetime_limit_by_ip'] == 'yes' ) ||
          ($output['max_purch_rule_lifetime_limit_by_user'] == 'yes' ) ||     //v2.0.2.0
          ($output['max_purch_rule_lifetime_limit_by_email'] == 'yes' ) ||
          ($output['max_purch_rule_lifetime_limit_by_billto_name'] == 'yes' ) ||
          ($output['max_purch_rule_lifetime_limit_by_billto_addr'] == 'yes' ) ||
          ($output['max_purch_rule_lifetime_limit_by_shipto_name'] == 'yes' ) ||
          ($output['max_purch_rule_lifetime_limit_by_shipto_addr'] == 'yes' )))
                                                                               ) {
                 $input['use_lifetime_max_limits'] = 'yes'; //manually set the switch //v1.0.7.9
                 $output['use_lifetime_max_limits'] = 'yes';                          //v1.0.7.9
     }
   

     /*  v2.0.2.0  REMOVED - this should not happen.
     //one of these switches must be on
     if ( ( (isset ($input['use_lifetime_max_limits']) ) &&      //v1.0.7.9
            ($input['use_lifetime_max_limits'] == 'yes' ) ) &&     
         (($input['max_purch_rule_lifetime_limit_by_ip'] == 'no' ) &&
          ($input['max_purch_rule_lifetime_limit_by_user'] == 'no' ) &&     //v2.0.2.0
          ($input['max_purch_rule_lifetime_limit_by_email'] == 'no' ) &&
          ($input['max_purch_rule_lifetime_limit_by_billto_name'] == 'no' ) &&
          ($input['max_purch_rule_lifetime_limit_by_billto_addr'] == 'no' ) &&
          ($input['max_purch_rule_lifetime_limit_by_shipto_name'] == 'no' ) &&
          ($input['max_purch_rule_lifetime_limit_by_shipto_addr'] == 'no' )) ) {
              $input['use_lifetime_max_limits'] = 'no'; //manually set the switch //v1.0.7.9
              $output['use_lifetime_max_limits'] = 'no'; //manually set the switch //v1.0.7.9
     }
     */
  
  $input['discount_floor_pct_per_single_item'] = preg_replace('/[^0-9.]+/', '', $input['discount_floor_pct_per_single_item']); //remove leading/trailing spaces, percent sign, dollar sign
   
  $tempMsg =    str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $input['lifetime_purchase_button_error_msg']);
  $input['lifetime_purchase_button_error_msg'] = $tempMsg;

/* 
    //In this situation, this 'id or class Selector' may not be blank, supply wpsc checkout default - must include '.' or '#'
  if ( $input['show_error_before_checkout_products_selector']  <= ' ' ) {
     $input['show_error_before_checkout_products_selector'] = VTPRD_CHECKOUT_PRODUCTS_SELECTOR_BY_PARENT;             
  }
    //In this situation, this 'id or class Selector' may not be blank, supply wpsc checkout default - must include '.' or '#'
  if ( $input['show_error_before_checkout_address_selector']  <= ' ' ) {
     $input['show_error_before_checkout_address_selector'] = VTPRD_CHECKOUT_ADDRESS_SELECTOR_BY_PARENT;             
  }
*/ 
  //NO Object-based code on the apply_filters statement needed or wanted!!!!!!!!!!!!!
  return apply_filters( 'vtprd_validate_setup_input', $output, $input );                       
} 


} //end class
 $vtprd_setup_plugin_options = new VTPRD_Setup_Plugin_Options;
  
  
  /*
show_checkout_discount_detail_lines		         Show Product Discount Detail Lines?		           yes/no
show_checkout_discount_titles_above_details	  Show Short Checkout Message for "Grouped "?	       yes/no
show_checkout_purchases_subtotal		          Show Cart Purchases Subtotal Line?		             withDiscounts / beforeDiscounts / none
show_checkout_discount_total_line		          Show Products + Discounts Grand Total Line	       yes/no
  */
