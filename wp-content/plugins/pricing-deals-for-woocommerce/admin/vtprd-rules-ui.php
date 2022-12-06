<?php
/*
   Rule CPT rows are stored.  At rule store/update
   time, a master rule option array is (re)created, to allow speedier access to rule information at
   product/cart processing time.
 */
   
class VTPRD_Rules_UI{ 
	
	public function __construct(){       
    global $post, $vtprd_info;
    
    //ACTION TO ALLOW THEME TO OFFER ALL PRODUCTS AT A DISCOUNT.....
    
        
    add_action( 'add_meta_boxes_vtprd-rule', array(&$this, 'vtprd_remove_meta_boxes') );   
    add_action( 'add_meta_boxes_vtprd-rule', array(&$this, 'vtprd_add_metaboxes') );
    
    //v2.0.0.8 begin
    // ** BOTH executions NEEDED **
    // Gutenberg caused multiple themes to add conflicting JS, need BOTH executions to catch various themes...
    //  this double execution works in tandem with "add_filter( 'vtprd_remove_all_extra_js_from_rule_page', function() { return TRUE; } );"
    add_action( "admin_enqueue_scripts",     array(&$this, 'vtprd_enqueue_admin_scripts'),999  );//v2.0.0.3a 999 so the dequeue is LAST
    add_action( "admin_print_scripts",       array(&$this, 'vtprd_enqueue_admin_scripts'),999  );//v2.0.0.3a 999 so the dequeue is LAST
    //v2.0.0.8 end
        
        

    add_action( 'add_meta_boxes_vtprd-rule', array($this, 'vtprd_remove_all_in_one_seo_aiosp') ); 

    
    
    //AJAX actions
         
    // v1.1.8.1 begin - clone rule button
    add_action( 'wp_ajax_vtprd_ajax_clone_rule',                      array(&$this, 'vtprd_ajax_clone_rule') ); 
    add_action( 'wp_ajax_nopriv_vtprd_ajax_clone_rule',               array(&$this, 'vtprd_ajax_clone_rule') );
    // v1.1.8.1 end
              
    //Adds Wholesale flag on the Product Page
    add_action( 'post_submitbox_misc_actions', array( $this, 'vtprd_product_data_visibility' ) ); //v1.1.0.7 

    
    //*******************************************
    //v2.0.0 begin NEW ajax actions for SELECT2 
    //*******************************************
    //Product Search
    add_action( 'wp_ajax_vtprd_product_search_ajax',         array(&$this, 'vtprd_ajax_do_product_selector') ); 
    add_action( 'wp_ajax_nopriv_vtprd_product_search_ajax',  array(&$this, 'vtprd_ajax_do_product_selector') ); 
    //Category Search - no longer ajax, just select2...
    //add_action( 'wp_ajax_vtprd_category_search_ajax',        array(&$this, 'vtprd_ajax_do_category_selector') ); 
    //add_action( 'wp_ajax_nopriv_vtprd_category_search_ajax', array(&$this, 'vtprd_ajax_do_category_selector') );   
    //Customer Search
    add_action( 'wp_ajax_vtprd_customer_search_ajax',        array(&$this, 'vtprd_ajax_do_customer_selector') ); 
    add_action( 'wp_ajax_nopriv_vtprd_customer_search_ajax', array(&$this, 'vtprd_ajax_do_customer_selector') );
 
    //v2.0.0 end
    
	}
  
  public function vtprd_enqueue_admin_scripts() {
    //v2.0.0.1 begin
    global $vtprd_info;  //V2.0.0.1 removed $post_type from global statement
    $post_type = get_post_type();
    if( $post_type == 'vtprd-rule' ){         //v1.0.8.2   can't just test $post_type here, not always accurate!
    //v2.0.0.1 end      
    
          //v2.0.0.9 begin - turns off gutenberg for post type
          // Enable Gutenberg for WP < 5.0 beta
          add_filter('gutenberg_can_edit_post_type', '__return_false', 10);
      
          // Enable Gutenberg for WordPress >= 5.0
          add_filter('use_block_editor_for_post_type', '__return_false', 10);
          //v2.0.0.9 end


          //*****************
          //v2.0.0.8 begin
          //*****************
          global $vtprd_setup_options; 
          if (!isset( $vtprd_setup_options['register_under_tools_menu'] ))  { //register_under... is the 1st option
            $vtprd_setup_options = get_option( 'vtprd_setup_options' );
          }
          
          //TEST for AVADA theme - if avada, set switch
          if (($vtprd_setup_options['remove_all_extra_js_from_rule_page'] == 'no') ||
              ($vtprd_setup_options['remove_all_extra_js_from_rule_page'] <= ' ') ) {
            //get current theme name
            $theme_name = wp_get_theme()->get( 'Name' );
            /*
              https://codex.wordpress.org/Function_Reference/wp_get_theme
              Template 
              (Optional — used in a child theme) The folder name of the parent theme            
            */
            $parent_theme_template_name = wp_get_theme()->get( 'Template' ); 
            $theme_name = strtolower($theme_name);
            $parent_theme_template_name = strtolower($parent_theme_template_name);    
            
            if ( (strpos($theme_name, 'avada' ) !== FALSE) ||  //v2.0.0.9  switched needle and haystack
                 (strpos($parent_theme_template_name, 'avada' ) !== FALSE) ) {     //v2.0.0.9  switched needle and haystack              
               //if theme name found, set switch to 'yes'!
               $vtprd_setup_options['remove_all_extra_js_from_rule_page'] = 'yes';
               update_option( 'vtprd_setup_options',$vtprd_setup_options); 
            }
           
             
          }
          
          
          
        // from https://wordpress.stackexchange.com/questions/61635/how-to-remove-all-javascript-in-a-theme-wordpress
        /*
          If you are editing a rule and having trouble with the lower Rule screen display:
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	add_filter( 'vtprd_remove_all_extra_js_from_rule_page', function() { return TRUE; } ); 
        */
        if ( (apply_filters('vtprd_remove_all_extra_js_from_rule_page',FALSE )) ||
             ($vtprd_setup_options['remove_all_extra_js_from_rule_page'] == 'yes') ) { //if the theme has a terminal wp-admin conflict...
        
            //error_log( print_r(  'vtprd_remove_all_extra_JS_from_rule_page EXECUTED ' , true ) );

            //this is a single CSS statement, which overrides the publish box 'floating' attribute
           // wp_register_style ('vtprd-admin-override', VTPRD_URL.'/admin/css/vtprd-admin-style-override.css' );  
           // wp_enqueue_style  ('vtprd-admin-override');
                        
            global $wp_scripts;
            /*
            $leave_alone = array(
                // Put the scripts you don't want to remove in here.
            );
            */
            /*
        
            foreach ( $wp_scripts->queue as $handle )
            {
                // Here we skip/leave-alone those, that we added above ?
                //if ( in_array( $handle, $leave_alone ) )
                //    continue;
        
                $wp_scripts->remove( $handle );
            } 
            */ 
            //from https://stackoverflow.com/questions/22561094/how-do-i-remove-all-scripts-from-wordpress-using-wp-dequeue-script-or-wp-deregis
            $scripts = $wp_scripts->registered;
            foreach ( $scripts as $script ){
                wp_dequeue_script($script->handle);
            } 
            
                    
            /* duplicating!!
            //**********************************
            //error messages get deleted above, so redo them
            global $vtprd_rule;
            $sizeTest = sizeof($vtprd_rule->rule_error_message);
            if ( sizeof($vtprd_rule->rule_error_message ) > 0 ) {    //these error messages are from the last upd action attempt, coming from vtprd-rules-update.php
               $this->vtprd_error_messages();
            }
            */
        
                                     
        } else {
          $this->vtprd_remove_excess_scripts(); //v2.0.0.5a
        }
        /*
        remove excess CSS
          https://gelwp.com/articles/removing-all-enqueued-and-default-css-scripts-in-wordpress/

            1. dump all wordpress handles current for my test installation
            2. delete all except that list (include 'vtprd-admin-override')
            3. use filter to add to that list as needed
        */  
        if ( (apply_filters('vtprd_remove_all_extra_css_from_rule_page',FALSE )) ||
             ($vtprd_setup_options['remove_all_extra_css_from_rule_page'] == 'yes') ) { //if the theme has a terminal wp-admin conflict...      
              /*
            	// get all styles data
            	global $wp_styles;    
              // loop over all of the registered scripts
              	foreach ($wp_styles->registered as $handle => $data)
              	{
                //error_log( print_r(  ' ' , true ) );
                error_log( print_r(  'active $handle= "' .$handle. '"' , true ) );
                //error_log( print_r(  'styles Date for this handle = ', true ) );
                //error_log( var_export($data, true ) ); 
              	} 
                */  
        
            //error_log( print_r(  'vtprd_remove_all_extra_CSS_from_rule_page EXECUTED ' , true ) );
                                       
            // get all styles data
          	global $wp_styles;
          
          	// create an array of stylesheet "handles" to allow to remain
          	// e.g. these styles will keep the admin bar styled
          	$styles_to_keep = array(
                "colors",
                "common",
                "forms",
                "admin-menu",
                "dashboard",
                "list-tables",
                "edit",
                "revisions",
                "media",
                "themes",
                "about",
                "nav-menus",
                "widgets",
                "site-icon",
                "l10n",
                "code-editor",
                "wp-admin",
                "login",
                "install",
                "wp-color-picker",
                "customize-controls",
                "customize-widgets",
                "customize-nav-menus",
                "ie",
                "buttons",
                "dashicons",
                "admin-bar",
                "wp-auth-check",
                "editor-buttons",
                "media-views",
                "wp-pointer",
                "customize-preview",
                "wp-embed-template-ie",
                "imgareaselect",
                "wp-jquery-ui-dialog",
                "mediaelement",
                "wp-mediaelement",
                "thickbox",
                "wp-codemirror",
                "deprecated-media",
                "farbtastic",
                "jcrop",
                "colors-fresh",
                "open-sans",
                "wp-editor-font",
                "wp-block-library-theme",
                "wp-edit-blocks",
                "wp-block-library",
                "wp-components",
                "wp-edit-post",
                "wp-editor",
                "wp-format-library",
                "wp-list-reusable-blocks",
                "wp-nux",
                "wordfence-font-awesome-style",
                "wordfence-global-style",
                "vtprd-pro-admin-style",
                "wordfenceAJAXcss",
                "wf-adminbar",
                "storefront-plugin-install",
                "woocommerce_admin_menu_styles",
                "woocommerce_admin_styles",
                "jquery-ui-style",
                "woocommerce_admin_dashboard_styles",
                "woocommerce_admin_print_reports_styles",
                "vtprd-qtip-style",
                "vtprd-admin-style",
                "vtprd-jquery-datepicker-style",
                "vtprd-admin-style2",
                "selectWoo-style"                   
            );
          
          	// loop over all of the registered scripts
          	foreach ($wp_styles->registered as $handle => $data) {
          		// if we want to keep it, skip it
          		if ( in_array($handle, $styles_to_keep) ) {               
                continue;
              } else {
            		// otherwise remove it
            		wp_deregister_style($handle);
            		wp_dequeue_style($handle);
              }
          	}            
        }
        //v2.0.0.8 end

        //v1.1.8.1 end  
      
        //Datepicker resources, some part of WP
        wp_register_style ('vtprd-jquery-datepicker-style', VTPRD_URL.'/admin/css/smoothness/jquery-ui-1.10.2.custom.css' );  
        wp_enqueue_style  ('vtprd-jquery-datepicker-style');
        wp_enqueue_script ('jquery-ui-core', array('jquery'), false, true );
        wp_enqueue_script ('jquery-ui-datepicker', array('jquery'), false, true );

        if(defined('VTPRD_PRO_DIRNAME')) {
            wp_register_style ('vtprd-admin-style2', VTPRD_PRO_URL.'/admin/css/vtprd-admin-style2.css' );  
            wp_enqueue_style  ('vtprd-admin-style2');
        }
             
        //v2.0.0 begin

        wp_register_script('selectWoo', VTPRD_URL.'/admin/js/selectWoo.full.min.js' );  
        wp_enqueue_script ('selectWoo', array('jquery'), false, true);        
        wp_register_style ('selectWoo-style', VTPRD_URL.'/admin/css/selectWoo-style.css' );  
        wp_enqueue_style  ('selectWoo-style');   

        wp_register_script('vtprd-enhanced-select', VTPRD_URL.'/admin/js/vtprd-enhanced-select.min.js' );  //v2.0.0.1 changed to .min
        wp_enqueue_script ('vtprd-enhanced-select', array('jquery'), false, true); 

        //create ajax resource for EACH SEARCH BOX
        //wp_localize_script('vtprd-enhanced-select', 'vtprdProductSelect', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )  ));                                 
        //v2.0.0 end
        
                
        //*********************
        //v2.0.0.8 begin - moved these statements to bottom, to accomodate new 'extra js' wipeout actions
        //*********************
        
        //QTip Resources
        wp_register_style ('vtprd-qtip-style', VTPRD_URL.'/admin/css/vtprd.qtip.min.css' );  
        wp_enqueue_style  ('vtprd-qtip-style'); 
       
       //qtip resources named jquery-qtip, to agree with same name used in wordpress-seo from yoast!
        wp_register_script('jquery-qtip', VTPRD_URL.'/admin/js/vtprd.qtip.min.js' );  
        wp_enqueue_script ('jquery-qtip', array('jquery'), false, true);


        wp_register_style ('vtprd-admin-style', VTPRD_URL.'/admin/css/vtprd-admin-style-' .VTPRD_ADMIN_CSS_FILE_VERSION. '.css' );  //v1.1.0.7
        wp_enqueue_style  ('vtprd-admin-style');
        
        wp_register_script('vtprd-admin-script', VTPRD_URL.'/admin/js/vtprd-admin-script-' .VTPRD_ADMIN_JS_FILE_VERSION. '.js' );  //v1.1
        //create ajax resource
               
        wp_enqueue_script ('vtprd-admin-script', array('jquery', 'vtprd-qtip-js'), false, true);
        
        //v1.1.8.1 begin
        //create ajax resource
        wp_localize_script('vtprd-admin-script', 'cloneRuleAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )  ));

        //v2.0.0.8 end
        //*************
                       
      } //end 'vtprd-rule' section

      //v2.0.0.1 BEGIN
      //keep this for css for vtprd-wholesale 
      if( $post_type == $vtprd_info['parent_plugin_cpt']){
        wp_register_style('vtprd-admin-product-metabox-style', VTPRD_URL.'/admin/css/vtprd-admin-product-metabox-style.css' );  
        wp_enqueue_style( 'vtprd-admin-product-metabox-style');    
      }
      //v2.0.0.1 end

  }                               
 
  //**************************
  //v2.0.0.5a New Function
  //**************************
  public function vtprd_remove_excess_scripts() {
    //v2.0.0.8 begin
    /*
    $post_type = get_post_type();
      //error_log( print_r(  'Function begin - vtprd_remove_excess_scripts, post_type= ' .$post_type, true ) );
    if( $post_type == 'vtprd-rule' ){         //v1.0.8.2   can't just test $post_type here, not always accurate!
    //v2.0.0.1 end
    */
    //v2.0.0.8 end

        //v2.0.0.2 begin
        /*
           	If you are editing a rule and having trouble with the Date Picker:
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	add_filter( 'vtprd_trouble_with_date_picker', function() { return TRUE; } ); 
         */      
        if (apply_filters('vtprd_trouble_with_date_picker',FALSE )) { //v2.0.0.3a  'false' was in parenthesis!!
            //error_log( print_r(  'Function begin - MINI DEQUEUE LOADED', true ) ); 
            $dq_list = array (
            //Woocommmerce verison of selectwoo is REMOVED, I supply my own copy.
            'selectWoo'
           );
        } else {
          //*********************************
          //REMOVE UNNECCESSARY JS from PAGE!!
          //*********************************
            $dq_list = array (
            //Woocommmerce verison of selectwoo is REMOVED, I supply my own copy.
            'selectWoo',  
            
            //Perfect WooCommerce Brands 
            'pwb-functions-admin',     
            
            //Woocommerce Memberships
            'wc-memberships-admin', 
            
            //Product Brands For WooCommerce  
            'jquery-ui-accordion',  
           // 'wp-color-picker',   //v2.0.0.8 removed, causing problems with some themes.
            'wpsf-plugins', 
            'wpsf-fields',  
            'wpsf-framework', 
            
            //YITH WooCommerce Brands Add-On
            'yith-enhanced-select', 
            
            //Ultimate WooCommerce Brands
            'mgwb-script-admin',
            
            //Members plugin 
            'members-edit-post',  
                 
            'moxiejs', //file upload
            'plupload', //file upload
            
            //Wordpress media
            'media-editor',              
            'media-views',
            'media-audiovideo',       
            'thickbox',      
            'media-upload',
            'imgAreaSelect',
            'image-edit',
            'editor',
            'wp-embed',
            'TinyMCE',
            'quicktags',
            'wplink',
            'wp-emoji-release',
            'print_emoji_detection_script',
             
            //v2.0.0.5 begin Wordpress 5.0 additions
            // list from https://make.wordpress.org/core/2018/12/06/javascript-packages-and-interoperability-in-5-0-and-beyond/
            'wp-a11y',
            'wp-annotations',
            'wp-api-fetch',
            'wp-autop',
            'wp-blob',
            'wp-block-library',
            'wp-block-serialization-default-parser',
            'wp-blocks',
            'wp-components',
            'wp-compose',
            'wp-core-data',
            'wp-data',
            'wp-date',
            'wp-deprecated',
            'wp-dom-ready',
            'wp-dom',
            'wp-edit-post',
            'wp-editor',
            'wp-element',
            'wp-escape-html',
            'wp-format-library',
            'wp-hooks',
            'wp-html-entities',
            'wp-i18n',
            'wp-is-shallow-equal',
            'wp-keycodes',
            'wp-list-reusable-blocks',
            'wp-notices',
            'wp-nux',
            'wp-plugins',
            'wp-polyfill-element-closest',
            'wp-polyfill-fetch',
            'wp-polyfill-formdata',
            'wp-polyfill-node-contains',
            'wp-polyfill',
            'wp-redux-routine',
            'wp-rich-text',
            'wp-shortcode',
            'wp-token-list',
            'wp-url',
            'wp-viewport',
            'wp-wordcount'           
            //v2.0.0.5 end          
          );
          //error_log( print_r(  'Function begin - DEQUEUE LOADED', true ) ); 
        }
        //v2.0.0.2 end
        
        
        foreach ( $dq_list as $dq_this) { 
          wp_dequeue_script($dq_this);
          wp_deregister_script($dq_this);
        } 
     // } v2.0.0.8
      return;  
  }
  
  public function vtprd_remove_meta_boxes() {
     if(!current_user_can('administrator')) {  
      	remove_meta_box( 'revisionsdiv', 'post', 'normal' ); // Revisions meta box
        remove_meta_box( 'commentsdiv', 'vtprd-rule', 'normal' ); // Comments meta box
      	remove_meta_box( 'authordiv', 'vtprd-rule', 'normal' ); // Author meta box
      	remove_meta_box( 'slugdiv', 'vtprd-rule', 'normal' );	// Slug meta box        	
      	remove_meta_box( 'postexcerpt', 'vtprd-rule', 'normal' ); // Excerpt meta box
      	remove_meta_box( 'formatdiv', 'vtprd-rule', 'normal' ); // Post format meta box
      	remove_meta_box( 'trackbacksdiv', 'vtprd-rule', 'normal' ); // Trackbacks meta box
      	remove_meta_box( 'postcustom', 'vtprd-rule', 'normal' ); // Custom fields meta box
      	remove_meta_box( 'commentstatusdiv', 'vtprd-rule', 'normal' ); // Comment status meta box
      	remove_meta_box( 'postimagediv', 'vtprd-rule', 'side' ); // Featured image meta box
      	remove_meta_box( 'pageparentdiv', 'vtprd-rule', 'side' ); // Page attributes meta box
        remove_meta_box( 'categorydiv', 'vtprd-rule', 'side' ); // Category meta box
        remove_meta_box( 'tagsdiv-post_tag', 'vtprd-rule', 'side' ); // Post tags meta box
        remove_meta_box( 'tagsdiv-vtprd_rule_category', 'vtprd-rule', 'side' ); // vtprd_rule_category tags  
        remove_meta_box( 'relateddiv', 'vtprd-rule', 'side');                  
      } 
 
  }
         
  //v1.1.0.7  New Function - 
  //    add wholesale Product tickbox in PUBLISH metabox for Parent Product
  public  function vtprd_product_data_visibility() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set;        

      //error_log( print_r(  'FUNCTION vtprd_product_data_visibility begin' , true ) ); 

      //only do this for PRODUCT
      if( get_post_type() != $vtprd_info['parent_plugin_cpt'] ){  
        return;
      } 
      
      $current_visibility = get_post_meta( $post->ID, 'vtprd_wholesale_visibility', true );
      
      ?> 
      &nbsp; &nbsp; 
      <span id="vtprd-wholesale">
      <label class="selectit vtprd-wholesale-visibility-label">
        <input id="vtprd-wholesale-visibility" class="vtprd-wholesale-visibility-class" name="vtprd-wholesale-visibility" value="yes" <?php if ($current_visibility == 'yes'){echo ' checked="checked" ';} ?>  type="checkbox">
        <strong>&nbsp; <?php _e('Wholesale Product', 'vtprd') ?></strong>
      </label>
      </span>
      <?php 
      
      return;
  }
          
  public  function vtprd_add_metaboxes() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set;        

      $found_rule = false;                            
      if ($post->ID > ' ' ) {
        $post_id =  $post->ID;
        $vtprd_rules_set   = get_option( 'vtprd_rules_set' ) ;

        $sizeof_rules_set = sizeof($vtprd_rules_set);
        for($i=0; $i < $sizeof_rules_set; $i++) {  
           if ($vtprd_rules_set[$i]->post_id == $post_id) {
              $vtprd_rule = $vtprd_rules_set[$i];  //load vtprd-rule               
              $found_rule = true;
              $found_rule_index = $i; 
              $i = $sizeof_rules_set;  
       
              //***************
              //v2.0.0.9 begin
              //***************
              /*
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
                //'insert_error_before_selector' => '#vtprd-deal-selection',  //blue-area-title-line
                $vtprd_rule->rule_error_message[] = array( 
                    'insert_error_before_selector' => '#vtprd-deal-selection',  //blue-area-title-line
                    'error_msg'  => __('******* <br> Due to another module mishandling Javascript resources, a Javascript conflict has caused a fatal error on this rule. <br><br> please go to wp-admin/pricing deals rules/pricing deals settings page. <br><br> On the horizontal JUMP TO menu, click on "System Options". <br> at "Remove Extra JS from Rule Page", select "Yes"	<br> at "Remove Extra CSS from Rule Page", select "Yes" <br> and click on "Save Changes." <br><br> Any rules which are not displaying correctly will need to deleted and recreated. <br><br>Test in a fresh browser session.  <br>******* ', 'vtprd') );  
              }               
              //v2.0.0.9 end                           
           }
        }
      } 

      if (!$found_rule) {
        $this->vtprd_build_new_rule();        
      } 

      add_meta_box('vtprd-deal-selection',  __('Pricing Deals', 'vtprd') , array(&$this, 'vtprd_deal'), 'vtprd-rule', 'normal', 'high');

      //side boxes
//      add_meta_box('vtprd-rule-id', __('Rule In Words', 'vtprd'), array(&$this, 'vtprd_rule_id'), 'vtprd-rule', 'side', 'low'); //low = below Publish box
//      add_meta_box('vtprd-rule-resources', __('Resources', 'vtprd'), array(&$this, 'vtprd_rule_resources'), 'vtprd-rule', 'side', 'low'); //low = below Publish box 

      //create help tab...                                                                                                                                                                                                          
      /*
      $content = '';
      $content .= '<br><a id="pricing-deal-title-more2" class="more-anchor" href="javascript:void(0);"><img class="pricing-deal-title-helpPng" alt="help"  width="14" height="14" src="' . VTPRD_URL .  '/admin/images/help.png" />' .    __(' Help! ', 'vtprd')  .'&nbsp;'.   __('Tell me about Pricing Deals ', 'vtprd') . '<img class="plus-button" alt="help" height="10px" width="10px" src="' . VTPRD_URL . '/admin/images/plus-toggle2.png" /></a>';            
      $content .= '    <a id="pricing-deal-title-less2" class="more-anchor less-anchor" href="javascript:void(0);"><img class="pricing-deal-title-helpPng" alt="help" width="14" height="14" src="' . VTPRD_URL . '/admin/images/help.png" />' . __('   Less Pricing Deals Help ... ', 'vtprd') . '<img class="minus-button" alt="help" height="12px" width="12px" src="' . VTPRD_URL . '/admin/images/minus-toggle2.png" /></a>';   
      
      $screen = get_current_screen();
      $screen->add_help_tab( array( 
         'id' => 'vtprd-help',            //unique id for the tab
         'title' => 'Pricing Deals Help',      //unique visible title for the tab
         'content' => $content  //actual help text
        ) ); 
        */ 
  }                   
   
                                                    
  public function vtprd_error_messages() {     
      global $post, $vtprd_rule;
  //error_log( print_r(  'vtprd_error_messages BEGIN' , true ) );
      $error_msg_count = sizeof($vtprd_rule->rule_error_message);
       ?>        
          <script type="text/javascript">
          jQuery(document).ready(function($) {           
          $('<div class="vtprd-error" id="vtprd-error-announcement"><?php _e("Please Repair Errors below", "vtprd"); ?></div>').insertBefore('#vtprd-deal-selection');  
      <?php 
     //error_log( print_r(  'javascript output' , true ) );   
      //loop through all of the error messages 
      //          $vtmax_info['line_cnt'] is used when table formattted msgs come through.  Otherwise produces an inactive css id. 
     for($i=0; $i < $error_msg_count; $i++) { 
       ?>
             $('<div class="vtprd-error"><?php echo $vtprd_rule->rule_error_message[$i]['error_msg'];?></div>').insertBefore('<?php echo $vtprd_rule->rule_error_message[$i]['insert_error_before_selector']; ?>');
      <?php 
     //error_log( print_r(  'message loaded into script= ' .$vtprd_rule->rule_error_message[$i]['error_msg'] , true ) );   
      }  //end 'for' loop      
      ?>   
            });   
          </script>
     <?php 

     //v1.1.8.0 begin - reformulated
     
     //Change the label color to red for fields in error
     if ( (sizeof($vtprd_rule->rule_error_red_fields) > 0 ) || 
          (sizeof($vtprd_rule->rule_error_box_fields) > 0 ) )  {  //v1.1.8.0 added error_box test
      
       echo '<style>' ;   // echo '<style type="text/css">' ;
       
       if (sizeof($vtprd_rule->rule_error_red_fields) > 0 ) {  //v1.1.8.0 added if
         for($i=0; $i < sizeof($vtprd_rule->rule_error_red_fields); $i++) { 
            if ($i > 0) { // if 2nd to n field name, put comma before the name...
              echo ', ';
            }
            echo $vtprd_rule->rule_error_red_fields[$i];
         }
         //echo '{color:red !important; display:block;}' ;         // display:block added for hidden date err msg fields
         echo '{color:red !important;} ' ;         //v2.0.0  display:block REMOVED - when error applied to CUSTOMER and/or, block causes weird results.
       }
       
       if (sizeof($vtprd_rule->rule_error_box_fields) > 0 ) {  //v1.1.8.0 added if
         for($i=0; $i < sizeof($vtprd_rule->rule_error_box_fields); $i++) { 
            if ($vtprd_rule->rule_error_box_fields[$i] > ' ')
              if ($i > 0) { // if 2nd to n field name, put comma before the name...
                echo ', ';
              }
              echo $vtprd_rule->rule_error_box_fields[$i];
            }
         }
         echo '{border-color:red !important; display:block;}' ;         // display:block added for hidden date err msg fields 
        
       //v1.1.8.0 end
              
       echo '</style>' ;
     }

      
      if( $post->post_status == 'publish') { //if post status not = pending, make it so  
          $post_id = $post->ID;
          global $wpdb;
          $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $post_id ) );
      } 

  }   

/* **************************************************************
    Deal Selection Metabox
                                                                                     
    Includes: 
    - Rule type info
    - Rule deal info
    - applies-to max info
    - rule catalog/cart display msgs
    - cumulative logic rule switches
************************************************************** */                                                   
  public function vtprd_deal() {     
      global $vtprd_rule_template_framework, $vtprd_deal_structure_framework, $vtprd_deal_screen_framework, $vtprd_rule_display_framework, $vtprd_rule, $vtprd_info, $vtprd_setup_options;
      $selected = 'selected="selected"';
      $checked = 'checked="checked"';
      $disabled = 'disabled="disabled"' ; 
      $vtprdNonce = wp_create_nonce("vtprd-rule-nonce"); //nonce verified in vt-pricing-deals.php


   //error_log( print_r(  'RULE BEGIN OF RULE DISPLAY = ', true ) );
   //error_log( var_export($vtprd_rule, true ) ); 
                
      $sizeTest = sizeof($vtprd_rule->rule_error_message);
       //error_log( print_r(  'sizeof rule_error_message = ' .$sizeTest, true ) ); 
      
      if ( sizeof($vtprd_rule->rule_error_message ) > 0 ) {    //these error messages are from the last upd action attempt, coming from vtprd-rules-update.php
            //error_log( print_r(  'go to vtprd_error_messages',  true ) );
           $this->vtprd_error_messages();
      }  else { //v2.0.0 begin ***********
        if(!defined('VTPRD_PRO_DIRNAME')) {
          //grey out the selection labels in buy group and get group not available in FREE
          echo '<style>  
          .buy-prod-category-incl-label, .buy-prod-category-excl-label, 
          .buy-plugin-category-incl-label, .buy-plugin-category-excl-label,
          .buy-product-incl-label, .buy-product-excl-label,
          .buy-role-incl-label, .buy-role-excl-label,
          .buy-email-incl-label, .buy-email-excl-label,
          .action-prod-category-incl-label, .action-prod-category-excl-label, 
          .action-plugin-category-incl-label, .action-plugin-category-excl-label,
          .action-product-incl-label, .action-product-excl-label
            {color:#888;} 
          
          #buy-and-or-selector-prod-cat,
          #buy-and-or-selector-plugin-cat,
          #buy-and-or-selector-product,
          #action-and-or-selector-prod-cat,
          #action-and-or-selector-plugin-cat,
          #action-and-or-selector-product {         
            color: #BB8500 !important;
            border: 1px solid #BB8500;
          }                
           </style>' ;  
           //most of the yellow stuff is in JS, except the above        
        }     
      } //v2.0.0 end
      
      //v2.0.0 begin
      //THESE blocks are also in JS, but are causing a BLINK due to slow load.  
      //SO also here.
      if ( $vtprd_rule->cart_or_catalog_select  ==  'catalog') {
          echo '<style>  
              #bulk-checkout-msg-comment, 
              #deal-action-line,
              #apply-to-cheapest-select-area,
              #buy_amt_box_0,
              #buy_repeat_box_0,
              #pricing-type-Bulk, 
              #pricing-type-Bogo,
              #pricing-type-Group,
              #pricing-type-Cheapest,
              .cumulativeCouponPricing_area,
              #deal-action-horiz-line
                {display:none;}          
             </style>' ;       
      }
      if (in_array( $vtprd_rule->pricing_type_select, array('choose', 'all', 'simple', 'bulk') )) {
          echo '<style>  
              #deal-action-line, 
              #deal-action-horiz-line
                {display:none;}          
             </style>' ;       
      }      
      //v2.0.0 end
    
      $currency_symbol = vtprd_get_currency_symbol();
      
      //v1.1.0.8 begin ==>>  init messages with default value, if blank (cleared out in rules_update )
      if ($vtprd_rule->discount_product_short_msg <= ' ') {
        $vtprd_rule->discount_product_short_msg = $vtprd_info['default_short_msg']; 
      }
      if ($vtprd_rule->discount_product_full_msg <= ' ') {
        $vtprd_rule->discount_product_full_msg = $vtprd_info['default_full_msg']; 
      }
      if ($vtprd_rule->only_for_this_coupon_name <= ' ') {
        $vtprd_rule->only_for_this_coupon_name = $vtprd_info['default_coupon_msg']; 
      }
      //v1.1.0.8 end
      
      //v2.0.0 begin
      /*
      //v1.1.7.1a begin
      if ($vtprd_rule->buy_group_varName_array <= ' ') {
        $vtprd_rule->buy_group_varName_array = $vtprd_info['default_by_varname_msg']; 
      }
      if ($vtprd_rule->action_group_varName_array <= ' ') {
        $vtprd_rule->action_group_varName_array = $vtprd_info['default_by_varname_msg']; 
      }
      //v1.1.7.1a end      
      */
      /*v2.0.0  NO longer necessary, this is now taken care of via the PLACEHOLDER
      if ($vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array']  <= ' ') {
          $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'] = $vtprd_info['default_by_varname_msg'];             
      }
      if ($vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array']  <= ' ') {
          $vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'] = $vtprd_info['default_by_varname_msg'];             
      }      
      if ($vtprd_rule->action_group_population_info['action_group_var_name_incl_array']  <= ' ') {
          $vtprd_rule->action_group_population_info['action_group_var_name_incl_array'] = $vtprd_info['default_by_varname_msg'];             
      }
      if ($vtprd_rule->action_group_population_info['action_group_var_name_excl_array']  <= ' ') {
          $vtprd_rule->action_group_population_info['action_group_var_name_excl_array'] = $vtprd_info['default_by_varname_msg'];             
      }  
      */  
      //v2.0.0 end
         
      //**********************************************************************
      //IE CSS OVERRIDES, done here to ensure they're last in line...
      //**********************************************************************
      echo '<!--[if IE]>';
	    echo '<link rel="stylesheet" type="text/css"  media="all" href="' .VTPRD_URL.'/admin/css/vtprd-admin-style-ie.css" />';
      echo '<![endif]-->';
      // end override
       
      //This Div only shows if there is a JS error in the customer implementation of the plugin, as the JS hides this div, if the JS is active
      //vtprd_show_help_if_js_is_broken();  
      
      ?>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
        
        //Spinner gif wasn't working... 
         $('.spinner').append('<img src="<?php echo VTPRD_URL;?>/admin/images/indicator.gif" />');
       
          });   
        </script>
     
       <?php /*
       <div class="hide-by-jquery">
        <span class="">< ?php _e('If you can see this, there is a JavaScript Error on the Page. Hover over this &rarr;', 'vtprd'); ? > </span>
            < ?php vtprd_show_help_tooltip($context = 'onlyShowsIfJSerror', $location = 'title'); ? >
       </div>
       */
       ?>

    <?php //BANNER AND BUTTON AREA ?>
                         

    
    <img id="pricing-deals-img-preload" alt="" src="<?php echo VTPRD_URL;?>/admin/images/upgrade-bkgrnd-banner.jpg" />
 		<div id="upgrade-title-area">
      <a  href=" <?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT ; ?> "  title="Purchase Pro">
      <img id="pricing-deals-img" alt="help" height="40px" width="40px" src="<?php echo VTPRD_URL;?>/admin/images/sale-circle.png" />
      </a>      
      <h2>
        <?php _e('Pricing Deals', 'vtprd'); ?>
        <?php if(defined('VTPRD_PRO_DIRNAME')) {  
                _e(' Pro', 'vtprd');
              }
        ?>    
        
        </h2>  
      
      <?php if(!defined('VTPRD_PRO_DIRNAME')) {  ?> 
          <span class="group-power-msg">
            <strong><em><?php _e('Create rules for Any Group you can think of, and More!', 'vtprd'); ?></em></strong>
            <?php /* 
              - Product Category
              - Pricing Deal Custom Category
              - Logged-in Status
              - Product
              - Variations!
                */ ?> 
          </span> 
          <span class="buy-button-area">
            <a href="<?php echo VTPRD_PURCHASE_PRO_VERSION_BY_PARENT; ?>" class="help tooltip tooltipWide buy-button">
                <span class="buy-button-label"><?php _e('Get Pricing Deals Pro', 'vtprd'); ?></span>
                <b> <?php vtprd_show_help_tooltip_text('upgradeToPro'); ?> </b>
            </a>
          </span> 
      <?php }  ?>
          
    </div>  

            
      <?php //RULE EXECUTION TYPE ?> 
      <div class="display-virtual_box  top-box">                           
        
        <?php //************************ ?>
        <?php //HIDDEN FIELDS BEGIN ?>
        <?php //************************ ?>
        <?php //RULE EXECUTION blue-dropdownS - only one actually displays at a time, depending on ?>
        <input type="hidden" id="vtprd_nonce" name="vtprd_nonce" value="<?php echo $vtprdNonce; ?>" />
        <?php //Hidden switch to communicate with the JS that the data is 1st time screenful ?>
        <input type="hidden" id="firstTimeBackFromServer" name="firstTimeBackFromServer" value="yes" />        
        <input type="hidden" id="upperSelectsFirstTime" name="upperSelectsFirstTime" value="yes" />
        <input type="hidden" id="upperSelectsDoneSw" name="upperSelectsDoneSw" value="" />
        <input type="hidden" id="catalogCheckoutMsg" name="catalogCheckoutMsg" value="<?php echo __('Message unused for Catalog Discount', 'vtprd');?>" />
        <input type="hidden" id="vtprd-moreInfo" name="vtprd-docTitle" value="<?php _e('More Info', 'vtprd');?>" /> <?php //v1.0.5 added 2nd button ?>
        <input type="hidden" id="vtprd-docTitle" name="vtprd-docTitle" value="<?php _e('- Help! -', 'vtprd');?>" /> 
        <input type="hidden" id="currencySymbol" name="currencySymbol" value="<?php echo get_woocommerce_currency_symbol();?>" /> <?php //v1.1.8.0 ?>        
        <?php
        //v2.0.0.8 begin
        $decimal_separator  = get_option( 'woocommerce_price_decimal_sep' );
        if ($decimal_separator == ',') {
          $stepValue = '0,01';
          $placeholderValue = '0,00';
          $typeValue = 'text'; //allows comma to be input, but deactivates JS auto number checking
        } else {
          $stepValue = '0.01';
          $placeholderValue = '0.00';
          $typeValue = 'number';
        }                           
        ?>
        <input type="hidden" id="stepValue" name="stepValue" value="<?php echo $stepValue; ?>" />
        <input type="hidden" id="placeholderValue" name="placeholderValue" value="<?php echo $placeholderValue; ?>" />
        <input type="hidden" id="typeValue" name="typeValue" value="<?php echo $typeValue; ?>" />
        <?php //v2.0.0.8 end ?>
                
        <?php //v1.1.8.1 begin  
        global $post; 
        // ajax-ruleID value is sent down with the button click
        ?> 
        <input type="hidden" id="vtprd-cloneRule" name="vtprd-cloneRule" value="<?php _e('Clone This Rule', 'vtprd');?>" />
        <input type="hidden" id="vtprd-url" name="vtprd-url" value="<?php  echo VTPRD_URL; ?>" />
        <input type="hidden" id="ajaxRuleID" name="ajaxRuleID" value="<?php echo $post->ID; ?>" /> 
        <?php //v1.1.8.1 end  ?>
        <input type="hidden" id="vtprd-copyForSupport" name="vtprd-copyForSupport" value="<?php _e('Copy to Support', 'vtprd');?>" />
        
        <?php //v2.0.0.2 begin added to handle non-standard installations in copy for support ?>
        <input type="hidden" id="vtprd-admin-url" name="vtprd-admin-url" value="<?php  echo VTPRD_ADMIN_URL ?>" />
        <?php //v2.0.0.2 end ?>

        <?php 
           /*
            Assign a numeric value to the switch
              showing HOW MANY selects have data
                on 1st return from server...
           */           
           $data_sw = '0';
           
           //test the Various group filter selects and set a value...
           switch( true) {
              case ( ($vtprd_rule->get_group_filter_select > ' ') &&
                     ($vtprd_rule->get_group_filter_select != 'choose') ):
                  $data_sw = '5';
                break;
              case ( ($vtprd_rule->buy_group_filter_select > ' ') &&
                     ($vtprd_rule->buy_group_filter_select != 'choose') ):
                  $data_sw = '4';
                break;  
              case ( ($vtprd_rule->minimum_purchase_select > ' ') &&
                     ($vtprd_rule->minimum_purchase_select != 'choose') ):              
                  $data_sw = '3';
                break;   
              case ( ($vtprd_rule->pricing_type_select > ' ') &&
                     ($vtprd_rule->pricing_type_select != 'choose') ):
                  $data_sw = '2';
                break;   
              case ( ($vtprd_rule->cart_or_catalog_select > ' ') &&
                     ($vtprd_rule->cart_or_catalog_select != 'choose') ):              
                  $data_sw = '1';
                break;                    
             } 
             
             /*  upperSelectsHaveDataFirstTime has values from 0 => 5
             value = 0  no previous data saved 
             value = 1  last run got to:  cart_or_catalog_select
             value = 2  last run got to:  pricing_type_select
             value = 3  last run got to:  minimum_purchase_select
             value = 4  last run got to:  buy_group_filter_select
             value = 5  last run got to:  get_group_filter_select
             */
        ?>
        <input type="hidden" id="upperSelectsHaveDataFirstTime" name="upperSelectsHaveDataFirstTime" value="<?php echo $data_sw; ?>" />
        
        <input type="hidden" id="templateChanged" name="templateChanged" value="no" /> 
        
        <?php //Statuses used for switching of the upper dropdowns ?>
        <input type="hidden" id="select_status_sw"  name="select_status_sw"  value="no" />
        <input type="hidden" id="chg_detected_sw"  name="chg_detected_sw"    value="no" />   <?php //v1.0.7.6 ?>
        
        <?php //pass these two messages up to JS, translated here if necessary ?>
        <input type="hidden" id="fullMsg" name="fullMsg" value="<?php echo $vtprd_info['default_full_msg'];?>" />    
        <input type="hidden" id="shortMsg" name="shortMsg" value="<?php echo $vtprd_info['default_short_msg'];?>" />
        <input type="hidden" id="couponMsg" name="couponMsg" value="<?php echo $vtprd_info['default_coupon_msg'];?>" />   <?php //v1.1.0.8  ?>
  
        <input id="pluginVersion" type="hidden" value="<?php if(defined('VTPRD_PRO_DIRNAME')) { echo "proVersion"; } else { echo "freeVersion"; } ?>" name="pluginVersion" />  
        <input id="rule_template_framework" type="hidden" value="<?php echo $vtprd_rule->rule_template;  ?>" name="rule_template_framework" />
              
           
        <?php //************************ ?>
        <?php //HIDDEN FIELDS END ?>
        <?php //************************ ?>

        <div class="template-area clear-left">  
          
          <div class="clear-left" id="blue-area-title-line"> 
              <img id="blue-area-title-icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />
              <span class="section-headings column-width2" id="blue-area-title">  <?php _e('Blueprint', 'vtprd');?></span>             
          </div> <?php //blue-area-title-line ?>
          
          <div class="clear-left" id="first-blue-line">                          
                                                                             
              <div class="left-column"  style="margin-top: 14px;" >                              
                 <?php //mwn20140414 added id ?>
                 <label id="cart-or-catalog-select-label" class="hasWizardHelpRight"  for="<?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['label']['for'];?>"><?php echo $vtprd_rule_display_framework['cart_or_catalog_select']['label']['title'];?></label>  
                 <?php vtprd_show_object_hover_help ('cart_or_catalog_select', 'wizard'); ?> 
              </div>
              <div class="blue-dropdown  cart-or-catalog" id="cart-or-catalog-select-area"> 
                  <label class="cart-or-catalog-label">&nbsp;<?php _e('Purchase Discount &nbsp;&nbsp;&nbsp;/&nbsp;&nbsp; Store Display Discount', 'vtprd');?></label> 
                  <?php //vtprd_show_object_hover_help ('cart-or-catalog-select', 'wizard'); ?>
                  <div class="switch-field-blueprint" id="" class="clear-left" >                    
                        
                        <input id="cart-or-catalog-Cart" class="cart-or-catalog" name="cart-or-catalog-select" value="cart" type="radio"  
                        <?php if ( 'cart' == $vtprd_rule->cart_or_catalog_select) { echo $checked; } ?> >
                        <label for="cart-or-catalog-Cart" id="cart-or-catalog-Cart-label">&nbsp;&nbsp;CART Deal&nbsp;&nbsp;</label> 
                     
                        <input id="cart-or-catalog-Catalog" class="cart-or-catalog" name="cart-or-catalog-select" value="catalog" type="radio"  
                        <?php if ( 'catalog' == $vtprd_rule->cart_or_catalog_select) { echo $checked; } ?> >
                        <label for="cart-or-catalog-Catalog" id="cart-or-catalog-Catalog-label"> CATALOG Discount</label> 
                                          
                  </div>  
                  <?php //v2.0.0.1 img old style below style="margin-top:24px;margin-left:15px;"  ?> 
                <img class="hasHoverHelp2" width="18px" style="float:left;margin-left:15px;" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />
                <?php vtprd_show_object_hover_help ('cart_or_catalog_select', 'small'); ?> 
              </div>               

          </div> <?php //end first blue-line ?> 
          
          <div class="horiz-line-div">&nbsp;</div> 

          <div class="blue-line  clear-left" id="pricing_type_select_box">                                  
               <span class="left-column" style="padding-top: 5px;">                              
                 <?php //mwn20140414 added id ?>
                 <label id="pricing-type-select-label" class="hasWizardHelpRight"   for="<?php echo $vtprd_rule_display_framework['pricing_type_select']['label']['for'];?>"><?php echo $vtprd_rule_display_framework['pricing_type_select']['label']['title'];?></label>
                 <?php vtprd_show_object_hover_help ('pricing_type_select', 'wizard'); ?> 
               </span>
               <span class="blue-dropdown  right-column" id="pricing-type-select-area">   
                 <select id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['pricing_type_select']['select']['class']; ?>  " name="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['tabindex']; ?>" >          
                   <?php
                   for($i=0; $i < sizeof($vtprd_rule_display_framework['pricing_type_select']['option']); $i++) { 
                   ?>                             
                      <option id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value'] == $vtprd_rule->pricing_type_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['title']; ?> </option>
                   <?php } ?> 
                 </select>  
                  <img  class="hasHoverHelp2" width="18px" style="margin-top:5px;margin-left:13px;" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />  
                  <?php vtprd_show_object_hover_help ('pricing_type_select', 'small'); ?>                                                        
               </span> 
          </div> <?php //end blue-line ?>
          
          <div class="horiz-line-div" id="deal-action-horiz-line">&nbsp;</div> 

          <div class="blue-line  clear-left" id="deal-action-line" style="margin-top: -10px;">  
               <div class="left-column" style="margin-top: 14px;" >                                            
                 <?php //mwn20140414 added id ?>
                 <label id="minimum-purchase-select-label" class="hasWizardHelpRight" for="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['label']['for'];?>"><?php echo $vtprd_rule_display_framework['minimum_purchase_select']['label']['title'];?></label>
                 <?php vtprd_show_object_hover_help ('minimum_purchase_select', 'wizard'); ?> 
               </div>
               <div class="blue-dropdown  blue-dropdown-minimum  right-column" id="minimum-purchase-select-area">  
                  <label class="minimum-purchase-label"><?php _e('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Buy 3 discount the *next* item" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; "Buy 3 discount 1 of them" ', 'vtprd');?></label> 
                  <?php //vtprd_show_object_hover_help ('minimum-purchase-select', 'wizard'); ?>
                  <div class="switch-field-blueprint" id="" class="clear-left">                    
                                                                         
                        <input id="minimum-purchase-Next" class="minimum-purchase" name="minimum-purchase-select" value="next" type="radio" 
                        <?php if ( 'next' == $vtprd_rule->minimum_purchase_select) { echo $checked; } ?> >
                        
                        <label for="minimum-purchase-Next" id="minimum-purchase-Next-label"> Discount Next item added to cart</label> 
                                                   
                        <input id="minimum-purchase-None" class="minimum-purchase" name="minimum-purchase-select" value="none" type="radio"
                        <?php if ( 'none' == $vtprd_rule->minimum_purchase_select) { echo $checked; } ?> >
                        
                        <label for="minimum-purchase-None" id=""> Discount item in cart already</label> 
                                                              
                  </div>
                <img  class="hasHoverHelp2" width="18px" style="margin-top:22px;margin-left:15px;" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />                
                <?php vtprd_show_object_hover_help ('minimum_purchase_select', 'small'); ?>                                          
              </div>         
          </div> <?php //end blue-line ?>  
          
          <div class="horiz-line-div">&nbsp;</div> 
                                        
          <div class="blue-line  blue-line-less-top  clear-left">
              <span class="left-column">                                                      
                <label class="scheduling-label hasWizardHelpRight" id="scheduling-label-item"><?php _e('Deal Schedule', 'vtprd');?></label>   
                <?php vtprd_show_object_hover_help ('scheduling', 'wizard'); ?>
              </span>
              <span class="blue-dropdown  scheduling-group  right-column" id="scheduling-area">   
                <span class="date-line" id='date-line-0'>                               
                <?php //   <label class="scheduling-label">Scheduling</label> ?>                                              
                    <span class="date-line-area">  
                      <?php  $this->vtprd_rule_scheduling(); ?> 
                    </span> 
                    <span class="on-off-switch">                              
                    <?php //     <label for="rule-state-select">On/Off Switch</label>  ?> 
                       <select id="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['rule_on_off_sw_select']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['select']['tabindex']; ?>" >          
                         <?php
                         for($i=0; $i < sizeof($vtprd_rule_display_framework['rule_on_off_sw_select']['option']); $i++) { 
                         ?>                             
                            <option id="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['value'] == $vtprd_rule->rule_on_off_sw_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['rule_on_off_sw_select']['option'][$i]['title']; ?> </option>
                         <?php } ?> 
                       </select>                        
                    </span>                                
                </span> 
                    <img  class="hasHoverHelp2" width="18px" alt="" style="margin-top:30px;margin-left:9px;"  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                    <?php vtprd_show_object_hover_help ('scheduling', 'small'); ?>                                                    
              </span>      
          </div> <?php //end blue-line ?>
          
          <div class="horiz-line-div">&nbsp;</div> 

          <div class="blue-line  clear-left" id="schedule-box" style="margin-top: -10px;">
              <div class="left-column">                                                      
                &nbsp;
              </div>
              <div class="right-column">       
                  <div class="blue-dropdown  rule-type" id="rule-type-select-area" style="margin-top: -2px;"> 
                      <label id="show-me-label" class="rule-type-label">&nbsp;<?php _e('Show Me', 'vtprd');?></label> 
                      <div class="switch-field-blueprint-small"  class="clear-left"> 
                                         
                          <input id="basicSelected" class="basic-advancedClass" name="rule-type-select" value="basic" type="radio"
                          <?php if ( 'basic' == $vtprd_rule->rule_type_select) { echo $checked; } ?>   >
                          <label for="basicSelected"> Basic Rule</label> 
                                                     
                          <input id="advancedSelected" class="basic-advancedClass" name="rule-type-select" value="advanced" type="radio"
                          <?php if ( 'advanced' == $vtprd_rule->rule_type_select) { echo $checked; } ?>   >
                          <label for="advancedSelected"> Advanced Rule</label>                    
                      
                      </div> 
                        <?php //v2.0.0.1 img old style below style="margin-top:24px;margin-left:15px;"  ?>
                      <img class="hasHoverHelp2" width="18px" style="float:left;margin-left:15px;" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />
                      <?php vtprd_show_object_hover_help ('rule-type-select', 'small'); ?> 
                  </div>

                   <?php //v1.1.6.7 begin 
                      //v2.0.0
                      //--------------------------------------------------------------------                
                      //Discount Equal or Lesser Value Item first
                      //- Discount the item(s) in the GET Group of equal or lesser value to the most expensive item in the BUY Group    
                      //--------------------------------------------------------------------  
                   ?> 
                   <div class="blue-dropdown cheapest-type" id="apply-to-cheapest-select-area"> 
                      <div style="float:left;">
                        <label class="wizard-type-label" id="apply-to-cheapest-label">&nbsp;<?php _e('Apply Discount to &nbsp;&nbsp; - which - &nbsp;&nbsp; Cart Item First', 'vtprd');?></label> 
                        <select id="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['select']['id'];?>" class="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['select']['name'];?>" tabindex="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['select']['tabindex']; ?>" >          
                           <?php
                           for($i=0; $i < sizeof($vtprd_rule_display_framework['apply_deal_to_cheapest_select']['option']); $i++) { 
                           ?>                             
                              <option id="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['apply_deal_to_cheapest_select']['option'][$i]['value'] == $vtprd_rule->apply_deal_to_cheapest_select )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['apply_deal_to_cheapest_select']['option'][$i]['title']; ?> </option>
                           <?php } ?> 
                        </select> 
                      </div>
                      <img class="hasHoverHelp2" width="18px" style="margin-top:24px;margin-left:13px;" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" />
                      <?php vtprd_show_object_hover_help ('apply_deal_to_cheapest', 'small'); ?> 
                   </div>                   
                   <?php //v1.1.6.7 end ?>                    
                                               
                     
              </div>
          </div> <?php //end blue-line ?>
    
          <?php //v1.0.9.0 begin  
           $memory = wc_let_to_num( WP_MEMORY_LIMIT );
      
      		 //v1.1.1 begin - REMOVED MEMORY LIMIT TEST
           //if ( $memory < 67108864 ) {     //test for 64mb             
           if ( $memory < 1 ) {     //test for 64mb 
           //v1.1.1 end   
          ?>
          <div class="blue-line  clear-left"> 
              <span class="left-column">                                                      
                &nbsp;
              </span>
              <span class="right-column"> 
    			     <?php
               echo 'WP Memory Limit: ' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'vtprd' ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) ;
    		       ?> 
              </span>                 
          </div> <?php //end blue-line ?>
          <?php } //v1.0.9.0 end ?>
          
          
      </div> <?php //end template-area ?>                       

     </div> <?php //end top-box ?>                
     
  <div class="display-virtual_box hideMe" id="lower-screen-wrapper" >

  
      <?php //****************  
            //DEAL INFO GROUP  
            //**************** ?> 
 
     <div class="display-virtual_box  clear-left" id="rule_deal_info_group">  
                       
      <?php // for($k=0; $k < sizeof($vtprd_rule->rule_deal_info[$k]); $k++) {  ?> 
      <?php  for($k=0; $k < sizeof($vtprd_rule->rule_deal_info); $k++) {  ?>         
      <div class="display-virtual_box rule_deal_info" id="rule_deal_info_line<?php echo '_' .$k; ?>">   
        <div class="display-virtual_box" id="buy_info<?php echo '_' .$k; ?>">  
         
           <input id="hiddenDealInfoLine<?php echo '_' .$k; ?>" type="hidden" value="lineActive" name="dealInfoLine<?php echo '_' .$k; ?>" />

           <?php 
              //*****************************************************
              //set the switch used on the screen for JS data check 
              //*****************************************************  ?>
           <?php //end switch ************************************** ?> 

         <div class="screen-box buy_group_title_box">
            <span class="buy_group_title-area">
              <img class="buy_amt_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />              
              
              <?php //EITHER / OR TITLE BASED ON DISCOUNT PRICING TYPE ?>
              <span class="section-headings first-level-title showBuyAsDiscount" id="buy_group_title_asDiscount">
                <?php _e('Discount Group ', 'vtprd');?>
              </span>
              <span class="section-headings first-level-title showBuyAsBuy" id="buy_group_title_asBuy">
                <?php _e('Qualify Group', 'vtprd');?>
                <span class="label-no-cap" style="color:#888;font-style:italic;font-family: Arial,Helvetica,sans-serif;">&nbsp;&nbsp;&nbsp;( Buy Group )</span>                
              </span>          
            </span>

         </div><!-- //buy_group_title_box --> 
 

      <div class="screen-box buy_group_box" id="buy_group_box<?php echo '_' .$k; ?>" >
            
          <div class="group-product-filter-box clear-left" id="buy-group-product-filter-box">            
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="buy_group_title">
                  <a id="buy_group_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Select Group By', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Select Group By', 'vtprd');?></span> </a>                    
                  <span class="required-asterisk">* </span>                    
                </span>
                <?php vtprd_show_object_hover_help ('inPop', 'wizard'); ?> 
                 
            </span>
            
            <span class="dropdown  buy_group  right-column" id="buy_group_dropdown">              
               <select id="<?php echo $vtprd_rule_display_framework['inPop']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['inPop']['select']['class']; ?> " name="<?php echo $vtprd_rule_display_framework['inPop']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['inPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['inPop']['option']); $i++) { 
                      
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['inPop']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title3'];                        
                      }               
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['inPop']['option'][$i]['value'] == $vtprd_rule->inPop )  { echo $selected; } ?> >  <?php echo $title; ?> </option>
                 <?php } ?> 
               </select> 
            
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('inPop', 'small');?>
               </span>                

              <div class="show-and-or-switches" id="buy-show-and-or-switches">                          
                <label class="show-and-or-switches-label"><?php _e('Show And/Or"s', 'vtprd');?></label>
                <div class="switch-field">               
                  <span class="hasWizardHelpRight">
                    <input id="buy-group-show-and-or-switches-YesSelect" class="buy-group-show-and-or-switches" name="buy_group_show_and_or_switches" value="yes" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_show_and_or_switches'] == 'yes') { echo $checked; } ?> >
                    <label for="buy-group-show-and-or-switches-YesSelect">Yes</label>
                  </span> 
                  <?php vtprd_show_object_hover_help ('buy_group_show_and_or_switches_YesSelect', 'wizard'); ?> 
                  <span class="hasWizardHelpRight">                                                       
                    <input id="buy-group-show-and-or-switches-NoSelect"  class="buy-group-show-and-or-switches" name="buy_group_show_and_or_switches" value="no"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_show_and_or_switches'] == 'no' ) { echo $checked; } ?> > 
                    <label for="buy-group-show-and-or-switches-NoSelect" id="buy-group-show-and-or-switches-NoSelect-label">No</label>
                  </span>
                  <?php vtprd_show_object_hover_help ('buy_group_show_and_or_switches_NoSelect', 'wizard'); ?> 
                </div> 
              </div>
                           
               <span class="buy_group_line_remainder_class" id="buy_group_line_remainder">   
                  <?php $this->vtprd_buy_group_cntl(); ?> 
               </span>                
               
               <?php  /* v1.1 "Product must be in the Filter Group" messaging removed!  */ ?>                          
            </span>  
          </div> <!-- //buy-group-product-filter-box -->                                                                       

       
         
         <?php  
         //*****************************
         //v1.1.8.0 begin
         //*****************************
         ?>
         
        <div id="bulk-box">   <?php //Bulk Buying ?>
            <div class="display-virtual_box pricing_table_info" id="pricing_table_info<?php echo '_' .$k; ?>"> 
             <div class="screen-box pricing_table_group_title_box">
                <span class="title  hasWizardHelpRight" id="pricing_table_title">
                  <a id="pricing_table_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Bulk Pricing Table', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Bulk Pricing Table', 'vtprd');?></span> </a>                    
                  <span class="required-asterisk">* </span>                    
                </span>
                <?php vtprd_show_object_hover_help ('pricingTable', 'wizard'); ?> 
             </div><!-- //pricing_table_group_title_box -->


            <?php 
              $bulk_deal_method_units = false;   
              $bulk_deal_method_currency = false;
              $bulk_deal_CountBy_each = false;
              $bulk_deal_CountBy_all = false;
              $bulk_deal_method_units_hideme = false;
              $bulk_deal_method_currency_hideme = 'hideMe';
              switch( $vtprd_rule->bulk_deal_method ) {
                case 'units':
                    $bulk_deal_method_units = $selected;
                  break;
                case 'currency':
                    $bulk_deal_method_currency = $selected;
                    $bulk_deal_method_units_hideme = 'hideMe';
                    $bulk_deal_method_currency_hideme = false;                 
                  break; 
                default:  
                    $bulk_deal_method_units = $selected;
                  break;                                                                    
              }
              switch( $vtprd_rule->bulk_deal_qty_count_by ) {
                case 'each':
                    $bulk_deal_CountBy_each = $selected;
                  break;
                case 'all':
                    $bulk_deal_CountBy_all = $selected;
                  break;
                default:  
                    $bulk_deal_CountBy_all = $selected;
                  break;                                                                          
              }              
            ?> 
             
             <div class="screen-box pricing_table_body_box">
               
                <div class="screen-box pricing_table_method_count_box  clear-left"> 
                  <span class="clear-left" id="pricing_table_method_box"> 
                       <span class="pricing_table_method_type">
                          <label class="pricing_table-method_label" ><?php _e('Count by Units or Currency', 'vtprd');?></label>
                          <select id="bulkMethodIn" class="clear-left" name="bulkMethodIn" tabindex="">                                                                
                              <option id="bulkMethodUnits" class="bulkMethodInOptions" <?php echo $bulk_deal_method_units;?> value="units" ><?php _e('Units &nbsp;&nbsp;- &nbsp; count by product units  ', 'vtprd');?></option>
                              <option id="bulkMethodCurrency" class="bulkMethodInOptions"  <?php echo $bulk_deal_method_currency;?> value="currency" ><?php echo __('Currency &nbsp;&nbsp;- &nbsp; count by ', 'vtprd') .$currency_symbol. __(' value  ', 'vtprd');?></option>                                                                                  
                          </select>
                       </span>
                  </span> 
   
                   <span class="" id="pricing_table_CountBy_box"> 
                       <span class="pricing_table_CountBy_type">
                          <label class="pricing_table_CountBy-label" ><?php _e('Begin / End Ranges Apply To', 'vtprd');?></label>
                          <select id="bulkCountByIn" class="clear-left" name="bulkCountByIn" tabindex="">                                                                                            
                              <option id="bulkCountByAll" class="bulkCountByInOptions"  <?php echo $bulk_deal_CountBy_all;?> value="all" ><?php _e('All &nbsp;&nbsp;- &nbsp; count together as a group ', 'vtprd'); //  &nbsp;&nbsp; ex: "Buy 5 shirts get a discount" '?></option> 
                              <option id="bulkCountByEach" class="bulkCountByInOptions" <?php echo $bulk_deal_CountBy_each;?> value="each" ><?php _e('Each &nbsp;&nbsp;-&nbsp; count each individual cart line item total ', 'vtprd');  //  &nbsp;&nbsp;  ex: "Buy 5 units of any one shirt..." ?></option>                                                                                 
                          </select>
                       </span>
                  </span>    
                </div>
                
                <div class="screen-box pricing_table_rows_box  clear-left">
                    <div class="pricing_table_line  pricing_table_line_top  clear-left" id="pricing-table-headings-line">              
                        <span class="pricing-table-headings pricing_table_column1 pricing-table-heading1"> 
                          <span><?php _e('Begin', 'vtprd');?></span>
                          <span class="bulk-heading-dollars <?php echo $bulk_deal_method_currency_hideme; ?>"><?php echo $currency_symbol;?> <?php _e('Value', 'vtprd');?> </span>
                          <span class="bulk-heading-units <?php echo $bulk_deal_method_units_hideme; ?> "><?php _e('Unit Quantity', 'vtprd');?></span>
                        </span>            
                        <span class="pricing-table-headings pricing_table_column2 pricing-table-heading2">
                          <span><?php _e('End', 'vtprd');?></span>
                          <span class="bulk-heading-dollars <?php echo $bulk_deal_method_currency_hideme; ?> "><?php echo $currency_symbol;?> <?php _e('Value', 'vtprd');?> </span>
                          <span class="bulk-heading-units <?php echo $bulk_deal_method_units_hideme; ?> "><?php _e('Unit Quantity', 'vtprd');?></span>                    
                        </span>               
                        <span class="pricing-table-headings pricing_table_column3 pricing-table-heading3"><?php _e('Discount Type', 'vtprd');  //php _e('Adjustment Type', 'vtprd');?></span>           
                        <span class="pricing-table-headings pricing_table_column4 pricing-table-heading4"><?php _e('Discount Value', 'vtprd');  //php _e('Adjustment Value', 'vtprd');?></span>                                
                    </div> 
                    
    
                     <div class="InputsWrapper" id="InputsWrapper">
    
    
                        <?php 
                        
                        /*
                        When generating new line, show:: Min: 5 (sample)  Max: 10 (sample)
    
                            ==>> start with 2 sample lines:
                            (1) Min: 5 (sample),  Max: 10 (sample)
                            (2) Min: (min = 0),  Max: (max = unlimited)
                        */
                         
                        $bulk_deal_array_count = sizeof($vtprd_rule->bulk_deal_array);
                        $RowCount = 0;
                        if ($bulk_deal_array_count > 0) {  //send existing rows
                            ?>
                            <input type="hidden" id="rowCountFirstTime" name="rowCountFirstTime" value="<?php echo $bulk_deal_array_count; ?>" />                        
                            <?php
                            $decimal_separator  = get_option( 'woocommerce_price_decimal_sep' );
                            
                            //v2.0.0.8 begin
                            if ($decimal_separator == ',') {
                              $stepValue = '0,01';
                              $placeholderValue = '0,00';
                              $typeValue = 'text'; //allows comma to be input, but deactivates JS auto number checking
                            } else {
                              $stepValue = '0.01';
                              $placeholderValue = '0.00';
                              $typeValue = 'number';
                            }                         
                            //v2.0.0.8 end
                            
                            $currency_symbol = get_woocommerce_currency_symbol();
                            for($b=0; $b < $bulk_deal_array_count; $b++) {
                              
                              //change decimal separator for display purposes, as needed - it's always carried internally as '.'
                              if ($decimal_separator == ',') {
                                $vtprd_rule->bulk_deal_array[$b]['min_value'] = str_replace('.', $decimal_separator, $vtprd_rule->bulk_deal_array[$b]['min_value']);
                                $vtprd_rule->bulk_deal_array[$b]['max_value'] = str_replace('.', $decimal_separator, $vtprd_rule->bulk_deal_array[$b]['max_value']);
                                $vtprd_rule->bulk_deal_array[$b]['discount_value'] = str_replace('.', $decimal_separator, $vtprd_rule->bulk_deal_array[$b]['discount_value']);            
                              }                              
                              
                              $RowCount++;
                              $min_value = $vtprd_rule->bulk_deal_array[$b]['min_value'];
                              $max_value = $vtprd_rule->bulk_deal_array[$b]['max_value'];
                              if ($max_value == 999999999999) {
                                $max_value = false;
                              }
                              $discount_type_percent = false;
                              $discount_type_currency = false;
                              $discount_type_fixedPrice = false;
                              switch( $vtprd_rule->bulk_deal_array[$b]['discount_type'] ) {
                                case 'percent':
                                  $discount_type_percent = $selected;
                                  break;
                                case 'currency':
                                  $discount_type_currency = $selected;
                                  break;                            
                                case 'fixedPrice':
                                  $discount_type_fixedPrice = $selected;
                                  break;                            
                              } 
                              $discount_value = $vtprd_rule->bulk_deal_array[$b]['discount_value'];
                                                        
                              $newHtml  =  '<div class="pricing_tier_row" id="pricing_tier_row_'. $RowCount.'">';
                              
                              $newHtml .=  '<span class="hideMe"><input  type="hidden" name="rowCount[]" id="rowCount_'.$RowCount.'" value="'.$RowCount.'"/></span>';
                              
                              $newHtml .=  '<span class="pricing_table_column1" id="minVal_'.$RowCount.'" ><input  type="text"   placeholder="From" name="minVal[]" id="minVal_row_'. $RowCount.'" value="'.$min_value.'"/></span>';
                              $newHtml .=  '<span class="pricing_table_column2" id="maxVal_'.$RowCount.'" ><input  type="text"   placeholder="To - No limit"  name="maxVal[]" id="maxVal_row_'. $RowCount.'" value="'.$max_value.'"/></span>';
                              
                              $newHtml .=  '<span class="pricing_table_column3" id="discountType_'.$RowCount.'" >';
                              $newHtml .=  '<select id="discount_amt_type_row_'.$RowCount.'"  class="pricing_table_discount_amt_type" name="discountType[]" tabindex="">';          
                              $newHtml .=  '<option id="pricing_table_discount_amt_type_percent_'.$RowCount.'" class="pricing_table_discount_amt_type_percent" value="percent" '.$discount_type_percent.' > % Off </option>';
                              $newHtml .=  '<option id="pricing_table_discount_amt_type_currency_'.$RowCount.'" class="pricing_table_discount_amt_type_currency" value="currency" '.$discount_type_currency.' > '.$currency_symbol.' Off </option>';                                                      
                              $newHtml .=  '<option id="pricing_table_discount_amt_type_fixedPrice_'.$RowCount.'" class="pricing_table_discount_amt_type_fixedPrice" value="fixedPrice" '.$discount_type_fixedPrice.' >  Fixed Unit Price '.$currency_symbol.'  </option>'; 
                              $newHtml .=  '</select>';                    
                              $newHtml .=  '</span>';
           
                              $newHtml .=  '<span class="pricing_table_column4" id="discountVal_'.$RowCount.'" ><input  type="'.$typeValue.'"  step="'.$stepValue.'" placeholder="'.$placeholderValue.'"  name="discountVal[]" id="discountVal_row_'. $RowCount.'" value="'.$discount_value.'"/></span>';
                              $newHtml .=  '<a href="#" class="removeclass">X</a>';
                              $newHtml .=  '</div>'; 
                              echo $newHtml;
                            }
                        } else {  //send a default row
                              $RowCount = 1;
                              ?>
                              <input type="hidden" id="rowCountFirstTime" name="rowCountFirstTime" value="<?php echo $RowCount; ?>" />                        
                              <?php
                              $newHtml  =  '<div class="pricing_tier_row" id="pricing_tier_row_'. $RowCount.'">';
                              
                              $newHtml .=  '<span class="hideMe"><input  type="hidden" name="rowCount[]" id="rowCount_'.$RowCount.'" value="'.$RowCount.'"/></span>';
                              
                              $newHtml .=  '<span class="pricing_table_column1" id="minVal_'.$RowCount.'" ><input  type="text"  placeholder="From" name="minVal[]" id="minVal_row_'. $RowCount.'" value=""/></span>';
                              $newHtml .=  '<span class="pricing_table_column2" id="maxVal_'.$RowCount.'" ><input  type="text"  placeholder="To - No limit" name="maxVal[]" id="maxVal_row_'. $RowCount.'" value=""/></span>';
                              
                              $newHtml .=  '<span class="pricing_table_column3" id="discountType_'.$RowCount.'" >';
                              $newHtml .=  '<select id="discount_amt_type_row_'.$RowCount.'"  class="pricing_table_discount_amt_type" name="discountType[]" tabindex="">';          
                              $newHtml .=  '<option id="pricing_table_discount_amt_type_percent_'.$RowCount.'" class="pricing_table_discount_amt_type_percent" value="percent" '.$selected.' > % Off </option>';
                              $newHtml .=  '<option id="pricing_table_discount_amt_type_currency_'.$RowCount.'" class="pricing_table_discount_amt_type_currency" value="currency" > '.$currency_symbol.' Off </option>';                                                      
                              $newHtml .=  '<option id="pricing_table_discount_amt_type_fixedPrice_'.$RowCount.'" class="pricing_table_discount_amt_type_fixedPrice" value="fixedPrice" >  Fixed Unit Price '.$currency_symbol.' </option>'; 
                              $newHtml .=  '</select>';                    
                              $newHtml .=  '</span>';
           
                              $newHtml .=  '<span class="pricing_table_column4" id="discountVal_'.$RowCount.'" ><input  type="'.$typeValue.'"  step="'.$stepValue.'" placeholder="'.$placeholderValue.'" name="discountVal[]" id="discountVal_row_'. $RowCount.'" value=""/></span>';
                              $newHtml .=  '<a href="#" class="removeclass">X</a>';
                              $newHtml .=  '</div>'; 
                              echo $newHtml;                    
                        }                
                      ?> 
                      
                     </div>
                    
                    <span class="pricing_table_add_row clear-left"><a href="#" id="AddMoreFileBox" class="btn btn-info"><span class="plus-sign">+</span>&nbsp;<?php _e('Add Row', 'vtprd');?></a></span>  
                           
                </div><!-- //pricing_table_rows_box -->                   
             </div><!-- //pricing_table_body_box -->
             
             <div class="bulk_box_currency_warning_class<?php echo '_' .$k; ?>" id="bulk_box_currency_warning<?php echo '_' .$k; ?>" >
               <span class="warning-line warning-line0"><strong><?php _e('COUNT BY CURRENCY &nbsp; - &nbsp; Examples and Recommendations', 'vtprd') ?></strong></span> 
               <a href="#" class="hideWarning">X</a>
               <p>
                  <span class="warning-line warning-line1"><strong><?php _e('When "Count by Currency" selected, please be sure to use the decimal place correctly.', 'vtprd') ?></strong></span>
                  <span class="warning-line warning-line2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Begin: 10.00 &nbsp;&nbsp; End: 19.99', 'vtprd') ?></span>
                  <span class="warning-line warning-line3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Begin: 20.00 &nbsp;&nbsp; End: 29.99', 'vtprd') ?></span>
                  <span class="warning-line warning-line4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Begin: 30.00 &nbsp;&nbsp; End: No Limit', 'vtprd') ?></span>
               </p>

               <p>
                  <span class="warning-line warning-line1"><strong><?php _e('EXAMPLE: the following discount rows, with a MAX discount of $400:', 'vtprd') ?></strong></span>
                  <span class="warning-line warning-line2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Begin: 100.00 &nbsp;&nbsp; End: 200.00 &nbsp;&nbsp; Discount: 10%', 'vtprd') ?></span>
                  <span class="warning-line warning-line3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Begin: 200.01 &nbsp;&nbsp; End: 300.00 &nbsp;&nbsp; Discount: 15%', 'vtprd') ?></span>
                  <span class="warning-line warning-line4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Begin: 300.01 &nbsp;&nbsp; End: 400.00 &nbsp;&nbsp; Discount: 20%', 'vtprd') ?></span>
               </p>
               
               <p>                  
                  <span class="warning-line warning-line5">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('Test 1: the following is in the cart', 'vtprd') ?></strong></span>
                  <span class="warning-line warning-line6">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Product: A &nbsp;&nbsp; Price: $50.00 &nbsp;&nbsp; Quantity: 7 &nbsp;&nbsp; Total: $350.00', 'vtprd') ?></span>
                  <span class="warning-line warning-line7">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Product: B &nbsp;&nbsp; Price: $50.00 &nbsp;&nbsp; Quantity: 2 &nbsp;&nbsp; Total: $100.00', 'vtprd') ?></span>
               </p> 
                              
               <p> 
                  <span class="warning-line warning-line8">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - All 7 units of Product A will be discounted by 20% - Total $350', 'vtprd') ?></span>
                  <span class="warning-line warning-line9">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - 1 full unit of Product B will be discounted by 20% - Total $50', 'vtprd') ?></span>
                  <span class="warning-line warning-line10">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - <strong>Discounting is straightforward</strong>', 'vtprd') ?></span>
               </p> 
               

               <p>                  
                  <span class="warning-line warning-line5">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('Test 2: the following is in the cart', 'vtprd') ?></strong></span>
                  <span class="warning-line warning-line6">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Product: A &nbsp;&nbsp; Price: $50.00 &nbsp;&nbsp; Quantity: 7 &nbsp;&nbsp; Total: $350.00', 'vtprd') ?></span>
                  <span class="warning-line warning-line7">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Product: B &nbsp;&nbsp; Price: $15.00 &nbsp;&nbsp; Quantity: 5 &nbsp;&nbsp; Total: $75.00', 'vtprd') ?></span>
                </p> 
                              
               <p>                 
                  <span class="warning-line warning-line8">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - All 7 units of Product A will be discounted by 20% - Total $350', 'vtprd') ?></span>
                  <span class="warning-line warning-line9">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - 3 units of Product B will be discounted by 20% - Total $45', 'vtprd') ?></span>
                  <span class="warning-line warning-line10">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - <strong>1 unit of Product B will be <em>Partially</em> discounted by 20%</strong>', 'vtprd') ?></span>
                  <span class="warning-line warning-line11">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' - <strong>$350 + $45 = $395. Only $5 of Product B unit 4 price will be discounted</strong>', 'vtprd') ?></span>                 
               </p>                         
             </div><!-- //bulk_box_warning -->

          </div><!-- //pricing_table_info -->
           
         </div>   <?php //end bulk-box ?>  

         
         <!-- //v1.1.8.0 end -->
        
                     
         <div class="screen-box buy_amt_box buy_amt_box_class<?php echo '_' .$k; ?>" id="buy_amt_box<?php echo '_' .$k; ?>" >
            
            <span class="left-column">
                <span class="title hasWizardHelpRight" id="buy_amt_title<?php echo '_' .$k; ?> ">
                  <a id="buy_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Group Amount', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Group Amount', 'vtprd');?></span>
                  </a>
                  <span class="required-asterisk">*</span>                      
                </span> 
                <?php vtprd_show_object_hover_help ('buy_amt_type', 'wizard'); ?>                                             
            </span>                
 
            <span class="dropdown  buy_amt  right-column" id="buy_amt_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class']; ?>  " name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('buy_amt_type', $i, $currency_symbol);
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] . '_'  .$k; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_amt_type'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?>                   
                </select>  
                
                            
                 <span class="buy_amt_line_remainder  buy_amt_line_remainder_class<?php echo '_' .$k; ?>" id="buy_amt_line_remainder<?php echo '_' .$k; ?>">   
                     <span class="amt-field buy_amt_count" id="buy_amt_count_area<?php echo '_' .$k; ?>">
                       <input id="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['buy_amt_count']; ?>" />
                     </span>

                 </span> 
           
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('buy_amt_type', 'small');?>
               </span>                               
                                            
            </span>
            
         </div><!-- //buy_amt_box -->


                  
         <div class="screen-box  buy_amt_box_appliesto_class<?php echo '_' .$k; ?>  buy_amt_line_remainder  clear-left" id="buy_amt_box_appliesto<?php echo '_' .$k; ?>" > 
            <span class="show-in-adanced-mode-only">
                <span class="left-column  left-column-less-padding-top3">  
                    <span class="title  hasWizardHelpRight" id="buy_amt_type_title<?php echo '_' .$k; ?>" >            
                      <a id="buy_amt_appliesto_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Group Amount', 'vtprd'); echo '<br>'; _e('Applies to', 'vtprd');?></a>
                    </span> 
                    <?php vtprd_show_object_hover_help ('buy_amt_applies_to', 'wizard'); ?>           
                </span> 
                

                <span class="dropdown  right-column">                           
                     <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_applies_to']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_applies_to']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_amt_applies_to'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                    
                               
                   <span class="shortIntro" >
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('buy_amt_applies_to', 'small');?>
                   </span>                               
                                  
                </span>
                                         
           </span>
        </div><!-- //buy_amt_box_appliesto -->


                    
         <div class="screen-box buy_amt_mod_box  buy_amt_mod_box_class<?php echo '_' .$k; ?>" id="buy_amt_mod_box<?php echo '_' .$k; ?>" > 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="buy_amt_mod_title<?php echo '_' .$k; ?>" >
                  <a id="buy_amt_mod_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php _e('Min / Max', 'vtprd');?></span><span class="showBuyAsDiscount"><?php _e('Min / Max', 'vtprd');?></span></a> 
                </span>
                <?php vtprd_show_object_hover_help ('buy_amt_mod', 'wizard');?>
            </span>
            <span class="dropdown  buy_amt_mod  right-column" id="buy_amt_mod_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_mod']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_mod']['option']); $i++) {
                          $this->vtprd_change_title_currency_symbol('buy_amt_mod', $i, $currency_symbol);                  
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_amt_mod'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
               
               <span class="amt-field  buy_amt_mod_count_area  buy_amt_mod_count_area_class<?php echo '_' .$k; ?>" id="buy_amt_mod_count_area<?php echo '_' .$k; ?>">
                 <input id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['buy_amt_mod_count']; ?>" />
               </span>   
            
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('buy_amt_mod', 'small');?>
               </span>                               
             
            </span>
                          
         </div><!-- //buy_amt_mod_box -->


                    
          <div class="screen-box buy_repeat_box  buy_repeat_box_class<?php echo '_' .$k; ?>" id="buy_repeat_box<?php echo '_' .$k; ?>" >     <?php //Rule repeat shifted to end of action area, although processed first ?> 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="buy_repeat_title<?php echo '_' .$k; ?> ">
                   <a id="buy_repeat_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showBuyAsBuy"><?php echo __('Rule Usage Count', 'vtprd');?></span><span class="showBuyAsDiscount"><?php echo __('Rule Usage Count', 'vtprd');?></span></a>
                   <span class="required-asterisk">* </span>
                </span>
                <?php vtprd_show_object_hover_help ('buy_repeat_condition', 'wizard');?>
            </span>
            
            <span class="dropdown buy_repeat right-column" id="buy_repeat_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['buy_repeat_condition'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                             
               <span class="amt-field  buy_repeat_count_area  buy_repeat_count_area_class<?php echo '_' .$k; ?>" id="buy_repeat_count_area<?php echo '_' .$k; ?>">              
                 <input id="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['buy_repeat_count']; ?>" />                
               </span>
                        
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_repeat_condition', 'small');?>
               </span>                               
                       
            </span>
                     
         </div><!-- //buy_repeat_box --> 
          
        </div><!-- //buy_info -->
           
        <?php //ACtion INFO  ?>        
        
        <div class="display-virtual_box action_info" id="action_info<?php echo '_' .$k; ?>"> 
         <div class="screen-box get_group_title_box">
            <span class="get_group_title-area">
              <img class="get_amt_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />              
              <span class="section-headings first-level-title showGetAsDiscount" id="get_group_title_active">
                <?php _e('Discount Group ', 'vtprd');?>
                <span class="label-no-cap"  style="color:#888;font-style:italic;font-family: Arial,Helvetica,sans-serif;">&nbsp;&nbsp;&nbsp;<?php _e('( Get Group )', 'vtprd');?></span>
              
            </span>
         </div><!-- //get_group_title_box --> 



        <div class="screen-box action_group_box" id="action_group_box<?php echo '_' .$k; ?>" >           
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="action_group_title">
                  <a id="action_group_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Select Group By', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Select Group By', 'vtprd');?></span></a>
                  <span class="required-asterisk">*</span>
                </span> 
                <?php vtprd_show_object_hover_help ('actionPop', 'wizard'); ?>      
            </span>
             
            <span class="dropdown action_group right-column" id="action_group_dropdown_0">              
               <select id="<?php echo $vtprd_rule_display_framework['actionPop']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['actionPop']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['actionPop']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['actionPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['actionPop']['option']); $i++) { 
                       
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['actionPop']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['actionPop']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['actionPop']['option'][$i]['title3'];                        
                      }                 
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['actionPop']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['actionPop']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['actionPop']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['actionPop']['option'][$i]['value'] == $vtprd_rule->actionPop )  { echo $selected; } ?> >  <?php echo $title; ?> </option>
                 <?php } ?> 
               </select> 
                          
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('actionPop', 'small');?>
               </span>  

              <div class="show-and-or-switches" id="action-show-and-or-switches">                          
                <label class="show-and-or-switches-label"><?php _e('Show "And/Or"s', 'vtprd');?></label>
                <div class="switch-field">               
                  <span class="hasWizardHelpRight">
                    <input id="action-group-show-and-or-switches-YesSelect" class="action-group-show-and-or-switches" name="action_group_show_and_or_switches" value="yes" type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_show_and_or_switches'] == 'yes') { echo $checked; } ?> >
                    <label for="action-group-show-and-or-switches-YesSelect"  class="show-and-or-switches-yes show-and-or-switches-action">Yes</label>
                  </span> 
                  <?php vtprd_show_object_hover_help ('action_group_show_and_or_switches_YesSelect', 'wizard'); ?> 
                  <span class="hasWizardHelpRight">                                                       
                    <input id="action-group-show-and-or-switches-NoSelect"  class="action-group-show-and-or-switches" name="action_group_show_and_or_switches" value="no"  type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_show_and_or_switches'] == 'no' ) { echo $checked; } ?> > 
                    <label for="action-group-show-and-or-switches-NoSelect"  class="show-and-or-switches-no show-and-or-switches-action" id="action-group-show-and-or-switches-NoSelect-label">No</label> 
                  </span>
                  <?php vtprd_show_object_hover_help ('action_group_show_and_or_switches_NoSelect', 'wizard'); ?> 
                </div> 
              </div>
                                         
               <span class="action_group_line_remainder_class" id="action_group_line_remainder">   
                <?php $this->vtprd_action_group_cntl(); ?> 
               </span>
               
               <?php  /* v1.1 "Product must be in the Filter Group" messaging removed!  */ ?>                               
                    
            </span>

         </div><!-- //action_group_box -->

                   
         <div class="screen-box action_amt_box  action_amt_box_class<?php echo '_' .$k; ?>" id="action_amt_box<?php echo '_' .$k; ?>" > 
            <span class="left-column">  
                <span class="title  hasWizardHelpRight" id="action_amt_type_title<?php echo '_' .$k; ?>" >            
                  <a id="action_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Amount', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Amount', 'vtprd');?></span></a>
                  <span class="required-asterisk">*</span>
                </span>
                <?php vtprd_show_object_hover_help ('action_amt_type', 'wizard'); ?>                                
            </span> 
            <span class="dropdown action_amt right-column" id="action_amt_dropdown<?php echo '_' .$k; ?>">              
               <select id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['name'] . '_' .$k ; ?>" tabindex="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_type']['option']); $i++) {
                          $this->vtprd_change_title_currency_symbol('action_amt_type', $i, $currency_symbol);                  
                 ?>                            
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_amt_type'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>              
               
              
               <span class="action_amt_line_remainder  action_amt_line_remainder_class<?php echo '_' .$k; ?>" id="action_amt_line_remainder<?php echo '_' .$k; ?>">
                   <span class="amt-field action_amt_count" id="action_amt_count_pair<?php echo '_' .$k; ?>">
                     <input id="<?php echo $vtprd_deal_screen_framework['action_amt_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['action_amt_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['action_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['action_amt_count']; ?>" />
                   </span>                                                    
               </span>  
           
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                 <?php vtprd_show_object_hover_help ('action_amt_type', 'small');?>
               </span>                               
                                          
            </span>

        </div><!-- //action_amt_box -->

                  
         <div class="screen-box action_amt_box_appliesto_class<?php echo '_' .$k; ?>  action_amt_line_remainder clear-left  " id="action_amt_box_appliesto<?php echo '_' .$k; ?>" > 
            <span class="show-in-adanced-mode-only" id="action_amt_box_appliesto_span<?php echo '_' .$k; ?>">
                <span class="left-column  left-column-less-padding-top3">  
                    <span class="title  hasWizardHelpRight" id="action_amt_type_title<?php echo '_' .$k; ?>" >            
                      <a id="action_amt_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Amount', 'vtprd'); echo '<br>'; _e('Applies to', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Amount', 'vtprd'); echo '<br>'; _e('Applies to', 'vtprd');?></span></a>
                    </span>
                    <?php vtprd_show_object_hover_help ('action_amt_applies_to', 'wizard'); ?>            
                </span> 

                <span class="dropdown    right-column">                           
                     <select id="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_applies_to']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_applies_to']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_applies_to']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_amt_applies_to'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_amt_applies_to']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                     
                               
                   <span class="shortIntro" >                      
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('action_amt_applies_to', 'small');?>
                   </span>                               
    
                </span>

            </span>
        </div><!-- //action_amt_box_appliesto -->


 
                    
        <div class="screen-box action_amt_mod_box  action_amt_mod_box_class<?php echo '_' .$k; ?>" id="action_amt_mod_box<?php echo '_' .$k; ?>" >
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="action_amt_mod_title<?php echo '_' .$k; ?>" >
                   <a id="action_amt_mod_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Min / Max', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Min / Max', 'vtprd');?></span></a>
                </span>
                <?php vtprd_show_object_hover_help ('action_amt_mod', 'wizard');?>
            </span>
            
            <span class="dropdown  right-column" id="action_amt_mod_dropdown<?php echo '_' .$k; ?>">
               <select id="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['select']['id'] . '_' .$k ; ?>" class="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_mod']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('action_amt_mod', $i, $currency_symbol);                  
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_amt_mod'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_amt_mod']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                            
               <span class="amt-field  action_amt_mod_count_area  action_amt_mod_count_area_class<?php echo '_' .$k; ?>" id="action_amt_mod_count_area<?php echo '_' .$k; ?>">
                 <input id="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_amt_mod_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['action_amt_mod_count']; ?>" />
               </span>  
            
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('action_amt_mod', 'small');?>
               </span>                                  
            </span>
         </div><!-- //action_amt_mod_box -->  


         
         <div class="screen-box action_repeat_condition_box  action_repeat_condition_box_class<?php echo '_' .$k; ?>" id="action_repeat_condition_box<?php echo '_' .$k; ?>" >      <?php //Action repeat shifted to end of action area, although processed first ?> 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="action_repeat_condition_title<?php echo '_' .$k; ?>" >
                   <a id="action_repeat_condition_title_anchor<?php echo '_' .$k; ?>" class="title-anchors third-level-title" href="javascript:void(0);"><span class="showGetAsGet"><?php _e('Group Repeat', 'vtprd');?></span><span class="showGetAsDiscount"><?php _e('Group Repeat', 'vtprd');?></span></a>
                </span>
                <?php vtprd_show_object_hover_help ('action_repeat_condition', 'wizard');?>
            </span>
            <span class="dropdown action_repeat_condition right-column"  id="action_repeat_condition_dropdown<?php echo '_' .$k; ?>">              
               
               <select id="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['action_repeat_condition']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['action_repeat_condition'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['action_repeat_condition']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select> 
               
                            
               <span class="amt-field action_repeat_count_area  action_repeat_count_area_class<?php echo '_' .$k; ?>" id="action_repeat_count_area<?php echo '_' .$k; ?>">
                 <input id="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['action_repeat_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['action_repeat_count']; ?>" />                 
               </span>
                        
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('action_repeat_condition', 'small');?>
               </span>                                                   
           </span>
         </div><!-- //action_repeat_condition_box -->  
         
      </div><!-- //action_info -->  
        
      <div class="display-virtual_box" id="discount_info">
                 
          <div class="screen-box discount_amt_box  discount_amt_box_class<?php echo '_' .$k; ?>" id="discount_amt_box<?php echo '_' .$k; ?>" >  
            <span class="title" id="discount_amt_title<?php echo '_' .$k; ?>" >
              <img class="discount_amt_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                            
              <a id="discount_amt_title_anchor<?php echo '_' .$k; ?>" class="section-headings first-level-title" href="javascript:void(0);"><?php _e('Discount ', 'vtprd'); echo $currency_symbol; ?></a>
            </span>
            
            <div  class="screen-box discount_amt_row clear-both" id="discount_amt_row<?php echo '_' .$k; ?>">
              <span class="clear-left left-column">
                  <span class="title  discount_action_type  hasWizardHelpRight" id="discount_action_type_title<?php echo '_' .$k; ?>" >            
                    <a id="discount_action_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Discount Type', 'vtprd'); //v2.0.0 changed from Discount Amount?></a>
                    <span class="required-asterisk">*</span>
                  </span>
                  <?php vtprd_show_object_hover_help ('discount_amt_type', 'wizard');?>
              </span>
  
              <span class="dropdown discount_amt_type right-column" id="discount_amt_type_dropdown<?php echo '_' .$k; ?>">              
                
                 <select id="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['discount_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_amt_type']['select']['tabindex']; ?>" style="width: 61.5%;">          
                   <?php
                   for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_amt_type']['option']); $i++) { 
                            $this->vtprd_change_title_currency_symbol('discount_amt_type', $i, $currency_symbol);                 
                    ?>                                                
                      <option id="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['discount_amt_type'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['discount_amt_type']['option'][$i]['title']; ?> </option>
                   <?php } ?> 
                 </select>
                 
                  
                 <span class="discount_amt_count_area  discount_amt_count_area_class<?php echo '_' .$k; ?>  amt-field" id="discount_amt_count_area<?php echo '_' .$k; ?>">    
                   <span class="discount_amt_count_label" id="discount_amt_count_label<?php echo '_' .$k; ?>"> 
                      <span class="forThePriceOf-amt-literal-inserted  discount_amt_count_literal  discount_amt_count_literal<?php echo '_' .$k;?> " id="discount_amt_count_literal_forThePriceOf_buyAmt<?php echo '_' .$k; ?>"><?php $this->vtprd_load_forThePriceOf_literal($k); ?> </span>
                      <span class="discount_amt_count_literal  discount_amt_count_literal_forThePriceOf  discount_amt_count_literal<?php echo '_' .$k;?> " id="discount_amt_count_literal_forThePriceOf<?php echo '_' .$k; ?>"><?php _e('units ', 'vtprd'); echo  '&nbsp;';  _e(' For the Price of ', 'vtprd');?> </span>
                      <span class="discount_amt_count_literal  discount_amt_count_literal_forThePriceOf_Currency  discount_amt_count_literal<?php echo '_' .$k;?> " id="discount_amt_count_literal_forThePriceOf_Currency<?php echo '_' .$k; ?>"><?php echo $currency_symbol; ?></span>
                   </span>                 
                   <input id="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['id'] . '_'  .$k; ?>" class="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['class']; ?>" type="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_amt_count']['name'] . '_' .$k ; ?>" value="<?php echo $vtprd_rule->rule_deal_info[$k]['discount_amt_count']; ?>" />                 
                   <span class="discount_amt_count_literal_units_area  discount_amt_count_literal<?php echo '_' .$k;?>  discount_amt_count_literal_units_area_class<?php echo '_' .$k; ?>" id="discount_amt_count_literal_units_area<?php echo '_' .$k; ?>">
                     <span class="discount_amt_count_literal" id="discount_amt_count_literal_units<?php echo '_' .$k; ?>"><?php _e(' units', 'vtprd');?> </span>
                     <?php vtprd_show_help_tooltip($context = 'discount_amt_count_forThePriceOf'); ?>
                   </span>                
                 </span>
                  <label id="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['label']['id'] . '_'  .$k; ?>"   class="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['label']['class'] ?>"> 
                      
                      <input id="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['id'] . '_'  .$k; ?>" 
                            class="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['class']; ?>  hasWizardHelpBelow"
                            type="checkbox" 
                            value="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['value']; ?>" 
                             <?php if ($vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['value'] == $vtprd_rule->rule_deal_info[$k]['discount_auto_add_free_product'] )  { echo $checked; } ?>
                            name="<?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['checkbox']['name'] . '_'  .$k; ?>" />
                      <?php vtprd_show_object_hover_help ('discount_free', 'wizard'); ?> 
                            
                      <?php echo $vtprd_deal_screen_framework['discount_auto_add_free_product']['label']['title']; ?>  
                      <?php vtprd_show_help_tooltip($context = 'discount_auto_add_free_product', $location = 'title'); ?> 
                  </label>
                          
                 <span class="shortIntro  shortIntro2" >
                    <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                    <?php vtprd_show_object_hover_help ('discount_amt_type', 'small');?>
                 </span>                                     
              </span>
            </div> <!-- //discount_amt_row -->
          </div> <!-- //discount_amt_box -->
                  
          <div class="screen-box discount_applies_to_box  discount_applies_to_box_class<?php echo '_' .$k; ?>" id="discount_applies_to_box<?php echo '_' .$k; ?>" >
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="discount_applies_to_title<?php echo '_' .$k; ?>" >
                  <a id="discount_applies_to_title_anchor<?php echo '_' .$k; ?>" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Discount Applies To', 'vtprd');?></a>
                </span>
                <?php vtprd_show_object_hover_help ('discount_applies_to', 'wizard');?>
            </span>
            
            <span class="dropdown discount_applies_to right-column"  id="discount_applies_to_dropdown<?php echo '_' .$k; ?>">              
               
               <select id="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['select']['id'] . '_' .$k ; ?>" class="<?php echo$vtprd_deal_screen_framework['discount_applies_to']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['select']['name'] . '_' .$k ; ?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_applies_to']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_applies_to']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['id'] . '_'  .$k  ?>"  class="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['value'] == $vtprd_rule->rule_deal_info[$k]['discount_applies_to'] )  { echo $selected; } ?> >  <?php echo $vtprd_deal_screen_framework['discount_applies_to']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
               </select>
               
                               
                   <span class="shortIntro" >
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('discount_applies_to', 'small');?>
                   </span>                                                                               
              </span>
              
             
               <div class="discount_applies_to_box_warning_class<?php echo '_' .$k; ?> hideMe" id="bulk_box_currency_warning_<?php echo '_' .$k; ?>" >
                 <p>
                    <span class="warning-line warning-line0"><?php _e('Discount Applies to Setup', 'vtprd') ?></span>
                    <span class="warning-line warning-line2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('When "All Products" selected, Unit Price discount can result in a "partial unit discount", which would be correct but confusing to the customer.', 'vtprd') ?></span>
                    <span class="warning-line warning-line1"><?php _e('On Pricing Deals Settings page, at "Unit Price Discount or Coupon Discount", suggest selecting <strong>"Coupon Discount"</strong>', 'vtprd') ?></span> 
                 </p>             
               </div><!-- //discount_applies_to_box_warning -->              
              
              
          </div><!-- //discount_applies_to_box -->


          <?php //v1.1.0.8 New  BOX - only by coupon ;?>  
          <div class="screen-box only_for_this_coupon_box only_for_this_coupon_box_class<?php echo '_' .$k; ?>" id="only_for_this_coupon_box<?php echo '_' .$k; ?>" >     <?php //Rule repeat shifted to end of action area, although processed first ?> 
            <span class="left-column">
                <span class="title  third-level-title  hasWizardHelpRight" id="only_for_this_coupon_title">
                   <a id="only_for_this_coupon_anchor" class="title-anchors third-level-title" href="javascript:void(0);"><?php _e('Discount Coupon Code', 'vtprd');  //_e('Apply Discount only with', 'vtprd'); echo '<br>'; _e('This Coupon Code', 'vtprd');  // _e('Discount Only with Coupon Code (optional)', 'vtprd'); ?> </a>
                </span>
                <?php vtprd_show_object_hover_help ('only_for_this_coupon_name', 'wizard');?>
            </span>
            
            <span class="dropdown buy_repeat right-column only_for_this_coupon_name-column" id="only_for_this_coupon_name_dropdown">              
                     <span class="column-width50">
                         <textarea rows="1" cols="50" id="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['class']; ?>  right-column" type="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['only_for_this_coupon_name']['name']; ?>" ><?php echo $vtprd_rule->only_for_this_coupon_name; ?></textarea>
                         
                     </span>              
                     <span class="shortIntro" >            
                        <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                        <?php vtprd_show_object_hover_help ('only_for_this_coupon_name', 'small');?>
                     </span>                               
                               
                       
            </span>
                     
         </div><!-- //only_for_this_coupon_box-->  
                            
                  
        </div> <!-- //discount_info -->
  
        
        </div> <!-- //end DEAL INFO line in "for" loop --><?php //end DEAL INFO line in "for" loop ?>   
      <?php } //end $k'for' LOOP ?>
      </div> <!-- //rule_deal_info_group --> <?php //end rule_deal_info_group ?>  
      
      <div id="messages-outer-box">           
         <div class="screen-box  messages-box_class" id="messages-box">
           <span class="title" id="discount_msgs_title" >
              <img class="theme_msgs_title_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                                          
              <a id="discount_msgs_title_anchor" class="section-headings first-level-title" href="javascript:void(0);"><?php _e('Discount Messages:', 'vtprd');?></a>            
           </span>
           <span class="dropdown messages-box-area clear-left"  id="discount_msgs_dropdown">
             <span class="discount_product_short_msg_area  clear-left">

                 <span class="left-column">
                     <span class="title  hasHoverHelp  hasWizardHelpRight">                
                         <span class="title-anchors" id="discount_product_short_msg_label"><?php _e('Checkout Message', 'vtprd'); ?></span> 
                         <span class="required-asterisk">*</span>
                     </span>
                     <?php vtprd_show_object_hover_help ('discount_product_short_msg', 'wizard');?>
                 </span>

                 <span class="right-column">
                     <span class="column-width50">
                         <textarea rows="1" cols="50" id="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['class']; ?>  right-column" type="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_product_short_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_product_short_msg; ?></textarea>
                         
                     </span>              
                     <span class="shortIntro" style="margin-top:3px;">                        
                        <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                        <?php vtprd_show_object_hover_help ('discount_product_short_msg', 'small');?>
                     </span>                               

                  </span>                      
             </span>
             
                               
             <?php //<span class="bulk-checkout-msg  clear-both" id="bulk-checkout-msg-comment1"> ('Ex: " Your bulk purchase was discounted by <strong>{show_discount_val}</strong> "', 'vtprd') </span>   ?>    
             <span class="bulk-checkout-msg  clear-both" id="bulk-checkout-msg-comment1"> <?php _e('<strong>{show_discount_val}</strong> &nbsp; - wildcard shows discount percent or discount amount applied from Pricing Table', 'vtprd');?> </span>             
             <span class="bulk-checkout-msg  clear-both" id="bulk-checkout-msg-comment3"> <?php _e('<strong>{show_discount_val_more}</strong> &nbsp; -  wildcard shows discount val and more in msg ', 'vtprd');?> </span>
             
                    
             <span class="discount_product_full_msg_area clear-both">

                 <span class="left-column">
                     <span class="title  hasWizardHelpRight">                
                         <span class="title-anchors" id="discount_product_full_msg_label"> <?php _e('Advertising Message', 'vtprd');?> </span> 
                     </span>
                     <?php vtprd_show_object_hover_help ('discount_product_full_msg', 'wizard');?>
                 </span>
                                    
                 <span class="right-column">                
                     <span class="column-width50">
                         <textarea rows="2" cols="35" id="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['class']; ?>  right-column" type="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_product_full_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_product_full_msg; ?></textarea>                                                                                              
                         
                     </span>                               
                     <span class="shortIntro"  style="margin-top:3px;">
                        <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                       <?php vtprd_show_object_hover_help ('discount_product_full_msg', 'small');?>
                     </span> 
                                            
                  </span> 
            
             </span>

           </span>
         </div>    
      </div>
       
    <div id="advanced-data-area"> 

      <div class="screen-box" id="maximums_box">   
          <span class="title" id="cumulativePricing_title" >
            <img class="maximums_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                                                        
            <a id="cumulativePricing_title_anchor" class="section-headings first-level-title" href="javascript:void(0);">
                <?php _e('Discount Limits:', 'vtprd');?>
                <?php if (!defined('VTPRD_PRO_DIRNAME'))  {  ?>
                    <span id="max-limits-subtitle"><?php _e('(pro only)', 'vtprd');?></span>
                <?php }  ?>
            </a>
          </span>
 
           
        
          <div class="screen-box  screen-box2 discount_lifetime_max_amt_type_box  clear-left" id="discount_lifetime_max_amt_type_box_0">  
             <?php
                 /* ***********************
                 special handling for  discount_lifetime_max_amt_type, discount_lifetime_max_amt_type.  Even though they appear iteratively in deal info,
                 they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
                 onscreen... This is actually a kluge, done to utilize the complete editing already available in the deal info loop for a  dropdown and an associated amt field.
                 *********************** */
             
               //Both _label fields have trailing '_0', as edits are actually handled in the discount info loop ?>          
            <span class="left-column  left-column-less-padding-top2">
                <span class="title  hasWizardHelpRight" id="discount_lifetime_max_title_0" >
                  <a id="discount_lifetime_max_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Customer', 'vtprd'); echo '<br>'; _e('Rule Limit', 'vtprd');?></a>
                </span>
                <?php vtprd_show_object_hover_help ('discount_lifetime_max_amt_type', 'wizard'); ?> 
            </span>
            
            <span class="dropdown  right-column" id="discount_lifetime_max_dropdown">
               
               <select id="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['id'] .'_0' ;?>" class="<?php echo$vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['name'] .'_0' ;?>" tabindex="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['select']['tabindex'] .'_0' ; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option']); $i++) { 
                          $this->vtprd_change_title_currency_symbol('discount_lifetime_max_amt_type', $i, $currency_symbol);
                      
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['title3'];                        
                      }         
                                                            
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['id'] .'_0' ;?>"  class="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_lifetime_max_amt_type']['option'][$i]['value']  == $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type']  )  { echo $selected; } // use '0' deal_info_line...?> >  <?php echo $title; ?> </option>
                 <?php } ?> 
               </select>
               
                           
               <span class="amt-field" id="discount_lifetime_max_amt_count_area">
 
                 <input id="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['id'] .'_0' ?>" class="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['class']; ?>  limit-count" type="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_lifetime_max_amt_count']['name'] .'_0' ;?>" value="<?php echo $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_count']; // use '0' deal_info_line...?>" />
               </span>
            
                        
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('discount_lifetime_max_amt_type', 'small');?>
               </span>                               

            </span>
            <span class="text-field  clear-left" id="discount_lifetime_max_amt_msg">
               <span class="data-line-indent">&nbsp;</span>
               <span class="text-field-label" id="discount_lifetime_max_amt_msg_label"> <?php _e('Short Message When Max Applied (opt) ', 'vtprd');?> </span>
                <?php vtprd_show_help_tooltip($context = 'discount_lifetime_max_amt_msg'); ?>
               <textarea rows="1" cols="100" id="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_lifetime_max_amt_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_lifetime_max_amt_msg; ?></textarea>
            </span>
            
            <?php //v2.0.2.0 begin ?>
            <span class='custLimit_addl_info  clear-left hideMe'>               
              <span class='custLimit_addl_info-line1  clear-left'>
                <?php _e('<em>Customer Rule Limit settings</em> - go to the ==>> ', 'vtprd'); ?>
                <a id="custLimit_addl_inf_anchor" class=" " target="_blank" href="<?php echo VTPRD_ADMIN_URL;?>/edit.php?post_type=vtprd-rule&page=vtprd_setup_options_page#vtprd-lifetime-options-anchor"><?php _e('Pricing Deals Settings page', 'vtprd');?> </a> 
              </span>                        
            </span>
            <?php //v2.0.2.0 end ?>            
                       
          </div> 
                   
                    
          
 
           
        <div class="screen-box  screen-box2  dropdown discount_rule_max_amt_type discount_rule_max_amt_type_box clear-left" id="discount_rule_max_amt_type_box_0">  
             <?php
                 /* ***********************
                 special handling for  discount_rule_max_amt_type, discount_rule_max_amt_type.  Even though they appear iteratively in deal info,
                 they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
                 onscreen... This is actually a kluge, done to utilize the complete editing already available in the deal info loop for a  dropdown and an associated amt field.
                 *********************** */
             
               //Both _label fields have trailing '_0', as edits are actually handled in the discount info loop ?>          
            <span class="left-column">
                <span class="title  hasWizardHelpRight" id="discount_rule_max_title_0" >
                  <a id="discount_rule_max_title_anchor" class="title-anchors second-level-title" href="javascript:void(0);"><?php _e('Cart Limit', 'vtprd');?></a>
                </span>
                <?php vtprd_show_object_hover_help ('discount_rule_max_amt_type', 'wizard'); ?>                
            </span>   
                    
            <span class="dropdown right-column" id="discount_rule_max_dropdown">
                
                <select id="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['id'] .'_0' ;?>" class="<?php echo$vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['name'] .'_0' ;?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['select']['tabindex'] .'_0' ; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_rule_max_amt_type']['option']); $i++) {
                          $this->vtprd_change_title_currency_symbol('discount_rule_max_amt_type', $i, $currency_symbol); 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['id'] .'_0' ;?>"  class="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['value']  == $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type']  )  { echo $selected; } // use '0' deal_info_line...?> >  <?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_type']['option'][$i]['title']; ?> </option>
                 <?php } ?> 
                </select> 
                
                
                <span class="amt-field  " id="discount_rule_max_amt_count_area">
                 <input id="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['id'] .'_0' ?>" class="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['class']; ?>  limit-count" type="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_max_amt_count']['name'] .'_0' ;?>" value="<?php echo $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_count']; // use '0' deal_info_line...?>" />
                </span>
                        
               <span class="shortIntro  shortIntro2" >
                  <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                  <?php vtprd_show_object_hover_help ('discount_rule_max_amt_type', 'small');?>
               </span>                                  
            </span>

           <?php //while the 2 max_amt fields above are kluged onto the deal_screen_framework, the msg field is on the rule proper ?>
           <span class="text-field  clear-left" id="discount_rule_max_amt_msg">
             <span class="data-line-indent">&nbsp;</span>
             <span class="left-column">
                 <span class="text-field-label" id="discount_rule_max_amt_msg_label"> <?php _e('Short Message When Max Applied (opt) ', 'vtprd');?> </span>
                  <?php vtprd_show_help_tooltip($context = 'discount_rule_max_amt_msg'); ?>
             </span>
             <textarea rows="1" cols="100" id="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['class']; ?> right-column" type="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_rule_max_amt_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_rule_max_amt_msg; ?></textarea>
           </span>           
        </div>     
  
            <div class="screen-box  screen-box2  dropdown discount_rule_cum_max_amt_type discount_rule_cum_max_amt_type_box clear-left" id="discount_rule_cum_max_amt_type_box_0">  
                 <?php
                     /* ***********************
                     special handling for  discount_rule_cum_max_amt_type, discount_rule_cum_max_amt_type.  Even though they appear iteratively in deal info,
                     they are only active on the '0' occurrence line.  further, they are displayed only AFTER all of the deal lines are displayed
                     onscreen... This is actually a kluge, done to utilize the complete editing already available in the deal info loop for a  dropdown and an associated amt field.
                     *********************** */
                 
                   //Both _label fields have trailing '_0', as edits are actually handled in the discount info loop ?>          
                <span class="left-column">
                    <span class="title  hasWizardHelpRight" >
                      <span class="title-anchors" id="discount_rule_cum_max_title_0" ><?php _e('Product Limit', 'vtprd');?></span>
                    </span> 
                    <?php vtprd_show_object_hover_help ('discount_rule_cum_max_amt_type', 'wizard'); ?>      
                </span>
                
                <span class="dropdown right-column" id="discount_rule_cum_max_dropdown">                                                         
                   
                   <select id="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['id'] .'_0' ;?>" class="<?php echo$vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['class']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['name'] .'_0' ;?>" tabindex="<?php //echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['select']['tabindex'] .'_0' ; ?>" >          
                     <?php
                     for($i=0; $i < sizeof($vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option']); $i++) { 
                              $this->vtprd_change_title_currency_symbol('discount_rule_cum_max_amt_type', $i, $currency_symbol);             
                     ?>                             
                        <option id="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['id'] .'_0' ;?>"  class="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['value']; ?>"   <?php if ($vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['value']  == $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type']  )  { echo $selected; } // use '0' deal_info_line...?> >  <?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_type']['option'][$i]['title']; ?> </option>
                     <?php } ?> 
                   </select>
                   
                    
                   <span class="amt-field" id="discount_rule_cum_max_amt_count_area">
              
                     <input id="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['id'] .'_0' ?>" class="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['class']; ?>  limit-count" type="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['type']; ?>" name="<?php echo $vtprd_deal_screen_framework['discount_rule_cum_max_amt_count']['name'] .'_0' ;?>" value="<?php echo $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_count']; // use '0' deal_info_line...?>" />
                   </span>
                        
                   <span class="shortIntro  shortIntro2" >
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                      <?php vtprd_show_object_hover_help ('discount_rule_max_amt_type', 'small');?>
                   </span>                                
                </span>
               <span class="text-field  clear-left" id="discount_rule_cum_max_amt_msg">
                 <span class="data-line-indent">&nbsp;</span>
                 <span class="text-field-label" id="discount_rule_cum_max_amt_msg_label"> <?php _e('Short Message When Max Applied (opt) ', 'vtprd');?> </span>
                  <?php vtprd_show_help_tooltip($context = 'discount_rule_cum_max_amt_msg'); ?>
                 <textarea rows="1" cols="100" id="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['discount_rule_cum_max_amt_msg']['name']; ?>" ><?php echo $vtprd_rule->discount_rule_cum_max_amt_msg; ?></textarea>
               </span>
            </div>                
          
      </div> <?php //end maximums_box box ?>                      

      <div class="screen-box" id="cumulativePricing_box">     
          <span class="title" id="cumulativePricing_title" >
            <img class="working_together_icon" src="<?php echo VTPRD_URL;?>/admin/images/tab-icons.png" width="1" height="1" />                                                        
            <a id="cumulativePricing_title_anchor" class="section-headings first-level-title" href="javascript:void(0);"><?php _e('Discount Works Together With:', 'vtprd');?></a>
          </span>
          
          <div class="clear-left" id="cumulativePricing_dropdown">       
            <div class="screen-box dropdown cumulativeRulePricing_area clear-left" id="cumulativeRulePricing_areaID"> 
               
               <span class="left-column  left-column-less-padding-top">
                  <span class="title  hasWizardHelpRight" >
                    <span class="cumulativeRulePricing_lit" id="cumulativeRulePricing_label"><?php _e('Other', 'vtprd'); echo '&nbsp;<br>';  _e('Rule Discounts', 'vtprd');?></span>
                  </span> 
                  <?php vtprd_show_object_hover_help ('cumulativeRulePricing', 'wizard'); ?>    
               </span>
               
               <span class="right-column">
                   <span class="column-width50"> 
                     <select id="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cumulativeRulePricing']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['cumulativeRulePricing']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_rule_display_framework['cumulativeRulePricing']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['value'] == $vtprd_rule->cumulativeRulePricing )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cumulativeRulePricing']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                     
                     
                     <span class="" id="priority_num">   <?php //only display if multiple rule discounts  ?>
                       <span class="text-field" id="ruleApplicationPriority_num">
                         <span class="text-field-label" id="ruleApplicationPriority_num_label"> <?php _e('Priority', 'vtprd');//_e('Rule Priority Sort Number:', 'vtprd');?> </span>
                         <input id="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['id']; ?>" class="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['class']; ?>" type="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['type']; ?>" name="<?php echo $vtprd_rule_display_framework['ruleApplicationPriority_num']['name']; ?>" value="<?php echo $vtprd_rule->ruleApplicationPriority_num; ?>" />
                       </span>
                     </span>
                   </span>           
                   <span class="shortIntro  shortIntro2" >
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                      <?php vtprd_show_object_hover_help ('cumulativeRulePricing', 'small');?>
                   </span>                                   
               </span> 
                            
            </div>
    
            <div class="screen-box dropdown cumulativeCouponPricing_area clear-left" id="cumulativeCouponPricing_0">              
               <span class="left-column  left-column-less-padding-top">
                  <span class="title  hasWizardHelpRight" >
                    <span class="cumulativeRulePricing_lit" id="cumulativeCouponPricing_label"><?php _e('Other <br>Coupon Discounts', 'vtprd');//_e('Apply this Rule Discount ', 'vtprd'); echo '&nbsp;&nbsp;';  _e('in Addition to Coupon Discount : &nbsp;', 'vtprd');?></span>
                  </span> 
                  <?php vtprd_show_object_hover_help ('cumulativeCouponPricing', 'wizard'); ?>  
               </span>
               <span class="right-column">
                   <span class="column-width50"> 
                     <select id="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cumulativeCouponPricing']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['cumulativeCouponPricing']['select']['tabindex']; ?>" >          
                       <?php
                       for($i=0; $i < sizeof($vtprd_rule_display_framework['cumulativeCouponPricing']['option']); $i++) { 
                       ?>                             
                          <option id="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['value'] == $vtprd_rule->cumulativeCouponPricing )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cumulativeCouponPricing']['option'][$i]['title']; ?> </option>
                       <?php } ?> 
                     </select>
                     
                   </span>           
                   <span class="shortIntro  shortIntro2" >
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                     <?php vtprd_show_object_hover_help ('cumulativeCouponPricing', 'small');?>
                   </span>                               

               </span> 
            </div>
                 
            <div class="screen-box dropdown cumulativeSalePricing_area clear-left" id="cumulativeSalePricing_areaID">              
               <span class="left-column  left-column-less-padding-top">
                   <span class="title  hasWizardHelpRight" >
                     <span class="cumulativeRulePricing_lit" id="cumulativeSalePricing_label"><?php _e('Product', 'vtprd'); echo '&nbsp;<br>'; _e('Sale Pricing', 'vtprd');?></span>
                   </span> 
                   <?php vtprd_show_object_hover_help ('cumulativeSalePricing', 'wizard'); ?>                
               </span>
               <span class="right-column">
                   
                   <select id="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['select']['id'];?>" class="<?php echo$vtprd_rule_display_framework['cumulativeSalePricing']['select']['class']; ?>" name="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['select']['name'];?>" tabindex="<?php //echo $vtprd_rule_display_framework['cumulativeSalePricing']['select']['tabindex']; ?>" >          
                     <?php
                     for($i=0; $i < sizeof($vtprd_rule_display_framework['cumulativeSalePricing']['option']); $i++) { 
                     ?>                             
                        <option id="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['id']; ?>"  class="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['class']; ?>"  value="<?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['value']; ?>"   <?php if ($vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['value'] == $vtprd_rule->cumulativeSalePricing )  { echo $selected; } ?> >  <?php echo $vtprd_rule_display_framework['cumulativeSalePricing']['option'][$i]['title']; ?> </option>
                     <?php } ?> 
                   </select> 
                   
                        
                   <span class="shortIntro  shortIntro2" >
                      <img  class="hasHoverHelp2" width="18px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help.png" /> 
                      <?php vtprd_show_object_hover_help ('cumulativeSalePricing', 'small'); ?>
                   </span>                                                 
               </span>
               <?php if (VTPRD_PARENT_PLUGIN_NAME == 'WP E-Commerce') { vtprd_show_help_tooltip($context = 'cumulativeSalePricingLimitation');  } ?> 
            </div>
          </div>  <?php //end cumulativeRulePricing_dropdown ?>  
       </div> <?php //end cumulativePricing box ?>  

      </div> <?php //end advanced-data-area ?>
            
      </div> <?php //lower-screen-wrapper ?>
      
      <?php 
          
    //lots of selects change their values between standard and 'discounted' titles.
    //This is where we supply the HIDEME alternative titles
    $this->vtprd_print_alternative_title_selects();  
    
//echo '$vtprd_rule= <pre>'.print_r($vtprd_rule, true).'</pre>' ; 
         
  }  //end vtprd_deal

      
  
    public    function vtprd_buy_group_cntl() {   
       global $post, $vtprd_info, $vtprd_rule, $vtprd_rule_display_framework, $vtprd_rules_set;
       $selected = 'selected="selected"';
       $checked = 'checked="checked"';  
 
       //*****************************
       //v2.0.0 begin
       //*****************************
       
       if(defined('VTPRD_PRO_DIRNAME')) { 
          $prodcat_msg    =  __( 'Search Product Cat &hellip;', 'vtprd' ); 
          $plugincat_msg  =  __( 'Search Pricing Deal Cat&hellip;', 'vtprd' );
          $product_msg    =  __( 'Search Product &hellip;', 'vtprd' );
          $role_msg       =  __( 'Search Role &hellip;', 'vtprd' );
          $email_msg      =  __( 'Search Email or Name &hellip;', 'vtprd' );
          $selector_msg   = null;      
       } else {
          $pro_only_msg   =  __( '* Pro - only * Search &hellip;', 'vtprd' );
          $prodcat_msg    =  $pro_only_msg; 
          $plugincat_msg  =  $pro_only_msg; 
          $product_msg    =  $pro_only_msg; 
          $role_msg       =  $pro_only_msg; 
          $email_msg      =  $pro_only_msg;
          $selector_msg   = '<h4 class="clear-left free-warning">- Yellow selectors not available in Free version -  </h4>'; 
       } ?> 

      <div class="buy-group-select-product-area  clear-left"> <?php  /* box around the product selections  */ ?>
      
        <?php  /* Select by Category / Product / Variation Products / Variation Name across Products / Brands  */ ?>
        <h4 class="select-sub-heading clear-left">Select Products: &nbsp; by Category / Product / Variation Name across Products / Brands</h4>
        <?php echo $selector_msg  //FREE Version blue message, yellow selectors not available; ?>        
        
         <?php //PROD CATEGORIES ?> 
        <div class="incl-excl-group top-horiz-line bottom-horiz-line buy-group-prod-cat-incl-excl-group clear-left">

          <div class="and-or-selector buy-and-or-selector" id="buy-and-or-selector-prod-cat">                                        
            <div class="switch-field">   
              <span class="hasWizardHelpRight">
                <input id="buy_group_prod_cat_and_or-AndSelect" class="and-or-selector-AndSelect" name="buy_group_prod_cat_and_or" value="and" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_prod_cat_and_or'] == 'and') { echo $checked; } ?> >
                <label id="buy_group_prod_cat_and_or-AndSelect-label" for="buy_group_prod_cat_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('buy_group_prod_cat_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_prod_cat_and_or-OrSelect"  class="" name="buy_group_prod_cat_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_prod_cat_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label id="buy_group_prod_cat_and_or-OrSelect-label" for="buy_group_prod_cat_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_prod_cat_OrSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_prod_cat_each-EachSelect"  class="" name="buy_group_prod_cat_and_or" value="each"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_prod_cat_and_or'] == 'each' ) { echo $checked; } ?> > 
                <label for="buy_group_prod_cat_each-EachSelect" id="buy_group_prod_cat_each-EachSelect-label"  class="and-or-selector-each">Each</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_prod_cat_EachSelect', 'wizard'); ?>               
            </div> 
          </div>
                                
          <div class="form-group2 clear-both  buy_group_prod_cat_incl">        
      				<div class="form-field"><label class="buy-prod-category-incl-label right-col-label"><?php _e( 'Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_prod_cat_incl_array[]" data-catid="prod_cat" data-placeholder="<?php echo $prodcat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['parent_plugin_taxonomy'];
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_prod_cat_incl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_prod_cat_incl', 'small');?>
                 </span>              
              </div>
          </div>
         
          <div class="form-group2 pad-the-top clear-left  buy_group_prod_cat_excl">     
      				<div class="form-field"><label class="buy-prod-category-excl-label  right-col-label"><?php _e( 'Exclude Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search  left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_prod_cat_excl_array[]" data-catid="prod_cat" data-placeholder="<?php  echo $prodcat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['parent_plugin_taxonomy'];
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_prod_cat_excl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_prod_cat_excl', 'small');?>
                 </span>              
              </div>
          </div>
          
        </div>        

         
         <?php //PRICING DEAL CATEGORIES ?> 
        <div class="incl-excl-group bottom-horiz-line  buy-group-plugin-cat-incl-excl-group clear-left">


          <div class="and-or-selector  buy-and-or-selector" id="buy-and-or-selector-plugin-cat">                                        
            <div class="switch-field"> 
              <span class="hasWizardHelpRight">
                <input id="buy_group_plugin_cat_and_or-AndSelect" class="and-or-selector-AndSelect" name="buy_group_plugin_cat_and_or" value="and" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_plugin_cat_and_or'] == 'and') { echo $checked; } ?> >
                <label id="buy_group_plugin_cat_and_or-AndSelect-label" for="buy_group_plugin_cat_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_plugin_cat_and_or-OrSelect"  class="" name="buy_group_plugin_cat_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_plugin_cat_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label id="buy_group_plugin_cat_and_or-OrSelect-label" for="buy_group_plugin_cat_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_OrSelect', 'wizard'); ?>
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_plugin_cat_each-EachSelect"  class="" name="buy_group_plugin_cat_and_or" value="each"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_plugin_cat_and_or'] == 'each' ) { echo $checked; } ?> > 
                <label id="buy_group_plugin_cat_each-EachSelect-label"  for="buy_group_plugin_cat_each-EachSelect" class="and-or-selector-each">Each</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_EachSelect', 'wizard'); ?>                   
            </div>              
          </div>
         
          <div class="form-group2  clear-both  buy_group_plugin_cat_incl">       
      				<div class="form-field"><label class="buy-plugin-category-incl-label  right-col-label"><?php _e( 'Pricing Deal <br>Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search  left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_plugin_cat_incl_array[]" data-catid="rule_cat" data-placeholder="<?php echo $plugincat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['rulecat_taxonomy'];
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_plugin_cat_incl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_incl', 'small');?>
                 </span>              
              </div>
          </div>
          
          <div class="form-group2 pad-the-top clear-left  buy_group_plugin_cat_excl">        
      				<div class="form-field"><label class="buy-plugin-category-excl-label right-col-label"><?php _e( 'Exclude <br>Pricing Deal <br>Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_plugin_cat_excl_array[]" data-catid="rule_cat" data-placeholder="<?php echo $plugincat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['rulecat_taxonomy'];
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_plugin_cat_excl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_excl', 'small');?>
                 </span>              
              </div>
          </div> 
                 
        </div>
                 
         <?php //PRODUCTS ?>               
         <div class="incl-excl-group bottom-horiz-line buy-group-product-incl-excl-group clear-left"> 


          <div class="and-or-selector  buy-and-or-selector" id="buy-and-or-selector-product">                                        
            <div class="switch-field">                
              <span class="hasWizardHelpRight">
                <input id="buy_group_product_and_or-AndSelect" class="and-or-selector-AndSelect" name="buy_group_product_and_or" value="and" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_product_and_or'] == 'and') { echo $checked; } ?> >
                <label id="buy_group_product_and_or-AndSelect-label" for="buy_group_product_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('buy_group_product_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_product_and_or-OrSelect"  class="" name="buy_group_product_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_product_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label id="buy_group_product_and_or-OrSelect-label" for="buy_group_product_and_or-OrSelect" id="buy_group_product_and_or-OrSelect-label" class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_product_OrSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_product_each-EachSelect"  class="" name="buy_group_product_and_or" value="each"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_product_and_or'] == 'each' ) { echo $checked; } ?> > 
                <label for="buy_group_product_each-EachSelect" id="buy_group_product_each-EachSelect-label" class="and-or-selector-each">Each</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_product_EachSelect', 'wizard'); ?>                
            </div> 
          </div>
              
         
           <div class="form-group2 clear-both  buy_group_product_incl">
              <div class="form-field"><label class="buy-product-incl-label  right-col-label" style="padding-top:8px;"><?php _e( 'Product', 'vtprd' ); ?></label>                                          
      				    <select class="vtprd-product-search left-col-data buy-product-incl-select" multiple="multiple" style="width: 500px;" name="buy_group_product_incl_array[]" data-placeholder="<?php echo $product_msg; ?>" data-action="vtprd_product_search_ajax">
        					<?php
                    $product_ids = $vtprd_rule->buy_group_population_info['buy_group_product_incl_array'];
        
        						//v2.0.0.9a begin
                    foreach ( $product_ids as $product_id ) {
        							if ($product_id > ' ') {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                          $product_name = $product->get_formatted_name(); 
                          if (vtprd_test_for_variations($product_id)) {
                            $product_name .= '&nbsp; [all variations] ';
                          }
          								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product_name ) . '</option>';
          							}
                      }
        						}
                    //v2.0.0.9a end
        					?>
                  </select>                                                   
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_product_incl', 'small');?>
                 </span>                                                                 
              </div>                                                    
          </div>
          
           <div class="form-group2 pad-the-top clear-left  buy_group_product_excl">
              <div class="form-field"><label class="buy-product-excl-label  right-col-label" style="padding-top:8px;"><?php _e( 'Exclude Product', 'vtprd' ); ?></label>                           
      				    <select class="vtprd-product-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_product_excl_array[]" data-placeholder="<?php echo $product_msg; ?>" data-action="vtprd_product_search_ajax">
        					<?php
                    $product_ids = $vtprd_rule->buy_group_population_info['buy_group_product_excl_array'];

        						//v2.0.0.9a begin
                    foreach ( $product_ids as $product_id ) {
        							if ($product_id > ' ') {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                          $product_name = $product->get_formatted_name(); 
                          if (vtprd_test_for_variations($product_id)) {
                            $product_name .= '&nbsp; [all variations] ';
                          }
          								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product_name ) . '</option>';
          							}
                      }
        						}
                    //v2.0.0.9a end                   
                    
        					?>
                  </select>                                                   
                 <span class="shortIntro-b-and-w shortIntro-select2" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_product_excl', 'small');?>
                 </span>
              </div>                                                                                                         
          </div>
          
        </div>


         <?php //VARNAME ?> 
         <div class="incl-excl-group bottom-horiz-line buy-group-var-name-incl-excl-group clear-left">


          <div class="and-or-selector  buy-and-or-selector" id="buy-and-or-selector-var-name">                                        
            <div class="switch-field">
              <span class="hasWizardHelpRight">
                <input id="buy_group_var_name_and_or-AndSelect" class="and-or-selector-AndSelect" name="buy_group_var_name_and_or" value="and" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_var_name_and_or'] == 'and') { echo $checked; } ?> >
                <label id="buy_group_var_name_and_or-AndSelect-label" for="buy_group_var_name_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('buy_group_var_name_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_var_name_and_or-OrSelect"  class="" name="buy_group_var_name_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_var_name_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label for="buy_group_var_name_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_var_name_OrSelect', 'wizard'); ?> 
            </div>          
          </div>
           
           <div class="form-group2 clear-both">        
      				<div class="form-field"><label class="buy_group_var_name_incl-label right-col-label" style="margin-top: 30px;"><?php _e( 'Variation Name <br>Across Products', 'vtprd' ); ?></label>

               <?php
                   // large|red+extra large|blue (*full* variation name[s], separated by: | AND combined by: + )
                   $varName_array = $vtprd_rule->buy_group_population_info['buy_group_var_name_incl_array'];
                   $varName_string = $this->vtprd_stringify_var_name_array($varName_array);
              ?>
              <span class='varName-example'>Example:&nbsp;&nbsp;<?php echo $vtprd_info['default_by_varname_example']; ?> </span>
                         
               <span class="varName-area">
                   <textarea rows="1" cols="50" id="buy_group_var_name_incl_array" class="buy_group_var_name_incl_array_class" name="buy_group_var_name_incl_array" placeholder="<?php _e( 'Enter attribute names &hellip;', 'vtprd' ); ?>"><?php echo $varName_string; ?></textarea>                 
               </span>              
               <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area">
                  <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  style="margin-left: -1.5% !important;" src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_group_var_name_incl', 'small');?>
               </span>                               
           </div>     
         </div>    


           <div class="form-group2 clear-left"  style="padding-top:15px;">        
      				
              <div class="form-field"><label class="buy_group_var_name_excl-label right-col-label"><?php _e( 'Exclude <br>Variation Name <br>Across Products', 'vtprd' ); ?></label>

               <?php
                   // large|red+extra large|blue (*full* variation name[s], separated by: | AND combined by: + )
                   $varName_array = $vtprd_rule->buy_group_population_info['buy_group_var_name_excl_array'];
                   $varName_string = $this->vtprd_stringify_var_name_array($varName_array);
              ?>
                         
               <span class="varName-area">
                   <textarea rows="1" cols="50" id="buy_group_var_name_excl_array" class="buy_group_var_name_excl_array_class"  name="buy_group_var_name_excl_array" placeholder="<?php _e( 'Enter attribute names &hellip;', 'vtprd' ); ?>"><?php echo $varName_string; ?></textarea>                 
               </span>              
               <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" style="margin-left: -1% !important;">
                  <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  style="margin-left: -1.5% !important;"  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_group_var_name_excl', 'small');?>
               </span>
                                              
          <style> 
          .varName_catalog_info {
            float: left;
            color:#666;font-size: 13px;margin-left: 15%;font-weight: normal;width: 80%;} 
          .varName_addl_info-line1,
          .varName_addl_info-line2,
          .varName_addl_info-line3,
          .varName_addl_info-line4,
          .varName_addl_info-line5 
            {float:left;} 
          .varName_addl_info-line2
            {margin-top:8px;}          
          .varName_addl_info-line3,
          .varName_addl_info-line4,
          .varName_addl_info-line5 
            {margin-top:5px;}                               
           </style>
           <?php //v2.0.0.1 REMOVED ERRANT '}' in style above ?> 
            
                <?php //v1.1.8.0 begin ?>
                <span class='varName_addl_info  clear-left'> 
                  <span class='varName_addl_info-line1  clear-left'><?php _e('<span style="color:#666;font-size: 13px;margin-left: -4%;font-weight: normal;">' .$vtprd_info['default_by_varname_msg']. '</span>', 'vtprd'); ?></span>
                  <span class='varName_addl_info-line2  clear-left'><?php _e('( <em>Changes To &nbsp; lowercase &nbsp; , &nbsp; removes leading and trailing spaces </em>)', 'vtprd'); ?></span>                  
                  <span class='varName_addl_info-line3  clear-left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('<em>If an Attribute Name has a space in the name between words, </em>', 'vtprd'); ?></span>
                  <span class='varName_addl_info-line4  clear-left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' <em>and the name is not found in testing, </em>', 'vtprd'); ?></span>                  
                  <span class='varName_addl_info-line5  clear-left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' <em>try replacing the space in the name with a dash "-" </em>', 'vtprd'); ?></span>          
                </span>
                <?php //v1.1.8.0 end ?>
            
                <?php //v2.0 begin ?>
                <span class='varName_catalog_info  clear-left'>
                  <span class='varName_addl_info-line1  clear-left' style="margin-top: 8px;"><?php _e('Catalog Attributes Note', 'vtprd'); ?></span>
                  <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  style="margin-top: 3px; float: left;"  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_group_varName_catalog_info', 'small');?>                
                </span>  
                <?php //v2.0 end ?>                
             </div>     
           </div>   
                         
         </div>  <?php //end buy_group_varName_exclude_area ?>   


         
         <?php //BRANDS         
          /* ********************************
          Pricing Deals Pro has built-in support for the following list of BRANDS Plugins .
          There's also the 'vtprd_add_brands_taxonomy' filter, which allows you to use ANY
          nominated custom taxonomy at the BRANDS selector 

          	If using a BRANDS plugin not in the supported list, add support by doing the following:
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	//change [brands plugin taxonomy] to the taxonomy of your brands plugin   
          	add_filter( 'vtprd_add_brands_taxonomy', function() { return  'brands plugin taxonomy'; } ); 
          
          Here's what we're prepared for: 
            
            Product Brands For WooCommerce
            https://wordpress.org/plugins/product-brands-for-woocommerce/
            taxonomy = 'product_brands'
            <a href="https://wordpress.org/plugins/product-brands-for-woocommerce/">Product Brands For WooCommerce</a>
            
            Perfect WooCommerce Brands
            https://wordpress.org/plugins/perfect-woocommerce-brands/
            taxonomy = 'pwb-brand'
            <a href="https://wordpress.org/plugins/perfect-woocommerce-brands/">Perfect WooCommerce Brands</a>
            
            Brands for WooCommerce
            https://wordpress.org/plugins/brands-for-woocommerce/
            taxonomy = 'berocket_brand'
            <a href="https://wordpress.org/plugins/brands-for-woocommerce/">Brands for WooCommerce</a>

            YITH WooCommerce Brands Add-On
            https://wordpress.org/plugins/yith-woocommerce-brands-add-on/
            taxonomy = 'yith_product_brand';
            <a href="https://wordpress.org/plugins/yith-woocommerce-brands-add-on/">YITH WooCommerce Brands Add-On</a>
            
            Ultimate WooCommerce Brands
            https://wordpress.org/plugins/ultimate-woocommerce-brands/
            taxonomy = "product_brand"
            <a href="https://wordpress.org/plugins/ultimate-woocommerce-brands/">Ultimate WooCommerce Brands</a>
            
            Woocommerce Brand
            https://wordpress.org/plugins/wc-brand/
            taxonomy = 'product_brand' 
            <a href="https://wordpress.org/plugins/wc-brand/">Woocommerce Brand</a> 
            
          */ 
          $tax_array = $vtprd_info['brands_taxonomy_array']; 

          //add_filter( 'vtprd_add_brands_taxonomy', function() { return  'YOUR brands plugin taxonomy'; } );               
          $filter_tax = apply_filters('vtprd_add_brands_taxonomy',FALSE );
          if ($filter_tax) {
            $tax_array[] = $filter_tax;
          } 
          $taxonomy = FALSE;
          foreach ( $tax_array as $tax ) {
            if (taxonomy_exists($tax)) { 
              $taxonomy = $tax;
              break;
            } else {
            }
          }                             
         ?> 
         <div class="incl-excl-group bottom-horiz-line buy-group-brands-incl-excl-group clear-left"> 

         <?php        
          if ($taxonomy) {   //only show and/or if there's something to SEARCH!!        
         ?>
          <div class="and-or-selector  buy-and-or-selector" id="buy-and-or-selector-brands">                                        
            <div class="switch-field"> 
              <span class="hasWizardHelpRight">
                <input id="buy_group_brands_and_or-AndSelect" class="and-or-selector-AndSelect" name="buy_group_brands_and_or" value="and" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_brands_and_or'] == 'and') { echo $checked; } ?> >
                <label id="buy_group_brands_and_or-AndSelect-label" for="buy_group_brands_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('buy_group_brands_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="buy_group_brands_and_or-OrSelect"  class="" name="buy_group_brands_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_brands_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label for="buy_group_brands_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('buy_group_brands_OrSelect', 'wizard'); ?> 
            </div>          
          </div>
         <?php        
          }           
         ?>              
                  
          <div class="form-group2 clear-both">      
      				<div class="form-field"><label class="buy-brands-incl-label right-col-label"><?php _e( 'Brand', 'vtprd' ); if (!$taxonomy) { _e( '<br><br>Exclude Brand', 'vtprd' ); } ?></label>
                 <?php        
                  if ($taxonomy) {           
                 ?> 
        				    <select class="vtprd-brand-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_brands_incl_array[]" data-placeholder="<?php esc_attr_e( 'Search Brand &hellip;', 'vtprd' ); ?>" data-action="vtprd_brand_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->buy_group_population_info['buy_group_brands_incl_array'];
          						$this->vtprd_build_cat_selects($taxonomy,$checked_list); //ALSO USED FOR BRANDS!!
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required hasWizardHelpRight">( <span class="brand-lit">Brands</span> - free Brands plugin needed &nbsp;&nbsp;<em>[ hover for plugin list ]</em> )</span>', 'vtprd' );
                      vtprd_show_object_hover_help ('buy_group_brands_incl', 'wizard'); 
                  } ?> 
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_brands_incl', 'wizard'); ?>
                 </span>                 
              </div>   
          </div>

         <?php        
          if ($taxonomy) {           
         ?>           
          <div class="form-group2   pad-the-top clear-left">      
      				<div class="form-field"><label class="buy-brands-excl-label right-col-label"><?php _e( 'Exclude Brand', 'vtprd' ); ?></label>
                 <?php        
                  if ($taxonomy) {          
                 ?>				    
                    <select class="vtprd-brand-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_brands_excl_array[]" data-placeholder="<?php esc_attr_e( 'Search Brand &hellip;', 'vtprd' ); ?>" data-action="vtprd_brand_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->buy_group_population_info['buy_group_brands_excl_array'];
          						$this->vtprd_build_cat_selects($taxonomy,$checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required hasWizardHelpRight">( Brands - free Brands plugin needed )</span>', 'vtprd' );
                      vtprd_show_object_hover_help ('buy_group_brands_incl', 'wizard'); 
                  } ?> 
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_brands_excl', 'wizard');?>
                 </span> 
              </div>               
          </div>
         <?php        
          }           
         ?>           
          
        </div>    


         
         <?php //SUBSCRIPTIONS        
          /* ********************************

            Look for:
            register_post_type
            register_taxonomy
            
            
            YITH WooCommerce Subscription
            https://wordpress.org/plugins/yith-woocommerce-subscription/
            yith zip
            register_post_type( 'ywsbs_subscription', $args );
            
            
            HF WooCommerce Subscriptions
            https://wordpress.org/plugins/xa-woocommerce-subscriptions/
            za zip
            wc_register_order_type('hf_shop_subscription', apply_filters('woocommerce_register_post_type_hf_subscription', array(
            public function get_order_count( $status ) {
            		global $wpdb;
            		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'hf_shop_subscription' AND post_status = %s", $status ) ) );
            	}
            
            
            Woocommerce subscriptions
            https://github.com/wp-premium/woocommerce-subscriptions
            woocommerce-subscriptions-master
            register_post_type 'shop_subscription'
            
            function wcs_do_subscriptions_exist() {
            	global $wpdb;
            	$sql = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1;", 'shop_subscription' );

          */           
          ?>
      </div>  <?php //end buy-group-select-product-area box around the product selections  ?> 

                                          
      <div class="buy-group-select-customer-area  clear-left"> <?php  /* box around the customer selections  */ ?>
        <h4 class="select-sub-heading clear-left" id="buy-group-by-customer-title">Select Customers: &nbsp; by Role (Wholesale) / Email / Customer Name / Group / Membership</h4>     
          
        <div id="" class="buy-group-and-or clear-left" >                                                                
          <span class="hasWizardHelpRight">
            <input id="buy-group-and-or-AndSelect" class="and-orClass" name="buy_group_customer_and_or" value="and" type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_customer_and_or'] == 'and') { echo $checked; } ?> >
            <span id="andSelect-label"><span id="and-select-field">AND</span> <span class="and-or-message-field" id="and-message-field">&nbsp;&nbsp;<span style="text-decoration:underline;">One</span> Customer entry from lists below is <span style="text-decoration:underline;">required</span></span>  </span>
          </span> 
          <?php vtprd_show_object_hover_help ('buy-group-and-or-AndSelect', 'wizard'); ?> 
          <br><br>
          <span class="hasWizardHelpRight">                                                       
            <input id="buy-group-and-or-OrSelect"  class="and-orClass" name="buy_group_customer_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->buy_group_population_info['buy_group_customer_and_or'] == 'or' ) { echo $checked; } ?> > 
            <span id="orSelect-label" ><span id="or-select-field">OR</span> <span class="and-or-message-field">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="text-decoration:underline;">Any</span> Customer entry can "activate" the deal</span>  </span> 
          </span>
          <?php vtprd_show_object_hover_help ('buy-group-and-or-OrSelect', 'wizard'); ?>                                                      
        </div>
        
        <?php echo $selector_msg;  //FREE Version blue message, yellow selectors not available; ?>
           
         <?php //Roles ?> 
        <div class="incl-excl-group top-horiz-line bottom-horiz-line buy-group-role-incl-excl-group clear-left">
          <div class="form-group2 clear-left  buy_group_role_incl">       
      				<div class="form-field"><label class="buy-role-incl-label right-col-label"><?php _e( 'Role', 'vtprd' ); ?></label>
    				    <select class="vtprd-role-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_role_incl_array[]" data-placeholder="<?php echo $role_msg; ?>" data-action="vtprd_role_search_ajax">
      					<?php
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_role_incl_array'];
      						$this->vtprd_build_role_selects($checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_role_incl', 'wizard');?>                 
                 </span>               
              </div>
          </div>
          
          <div class="form-group2   pad-the-top  clear-left  buy_group_role_excl">        
      				<div class="form-field"><label class="buy-role-excl-label right-col-label"><?php _e( 'Exclude Role', 'vtprd' ); ?></label>
    				    <select class="vtprd-role-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_role_excl_array[]" data-placeholder="<?php echo $role_msg; ?>" data-action="vtprd_role_search_ajax">
      					<?php
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_role_excl_array'];
      						$this->vtprd_build_role_selects($checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_role_excl', 'wizard');?> 
                 </span> 
              </div>
          </div> 
        </div>      

                        
         
         <?php //Customers ?> 
        <div class="incl-excl-group bottom-horiz-line buy-group-email-incl-excl-group clear-left">
          <div class="form-group2  clear-left  buy_group_email_incl">        
      				<div class="form-field"><label class="buy-email-incl-label right-col-label"><?php _e( 'Email &nbsp;or <br>Customer Name', 'vtprd' ); ?></label>
    				    <select class="vtprd-customer-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_email_incl_array[]" data-placeholder="<?php echo $email_msg; ?>" data-action="vtprd_customer_search_ajax">
      					<?php
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_email_incl_array'];
      						$this->vtprd_build_customer_selects($checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_email_incl', 'wizard');?> 
                 </span>               
              </div>
          </div>
          
          <div class="form-group2 clear-left  buy_group_email_excl">         
      				<div class="form-field"><label class="buy-email-excl-label right-col-label"><?php _e( 'Exclude <br>Email or <br>Customer Name', 'vtprd' ); ?></label>
    				    <select class="vtprd-customer-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_email_excl_array[]" data-placeholder="<?php echo $email_msg; ?>" data-action="vtprd_customer_search_ajax">
      					<?php
                  $checked_list = $vtprd_rule->buy_group_population_info['buy_group_email_excl_array'];
      						$this->vtprd_build_customer_selects($checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_email_excl', 'wizard');?> 
                 </span>               
              </div>
          </div>
        </div>


         
         <?php //GROUPS   based on the free version of Woocommerce Groups: https://wordpress.org/plugins/groups/  ?>    
 
        <div class="incl-excl-group bottom-horiz-line buy-group-groups-incl-excl-group clear-left">
          <div class="form-group2  clear-left">       
      				<div class="form-field"><label class="buy-groups-incl-label right-col-label"><?php _e( 'Group', 'vtprd' ); if ( !function_exists('_groups_get_tablename') ) { _e( '<br><br>Exclude Group', 'vtprd' ); } ?></label>
                 <?php        
                  if ( function_exists('_groups_get_tablename') ) {            
                 ?> 
        				    <select class="vtprd-group-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_groups_incl_array[]" data-placeholder="<?php esc_attr_e( 'Search Group &hellip;', 'vtprd' ); ?>" data-action="vtprd_group_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->buy_group_population_info['buy_group_groups_incl_array'];
          						$this->vtprd_build_group_selects($checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required hasWizardHelpRight">( <a id="" class="" href="https://wordpress.org/plugins/groups/">Groups</a> - free Groups plugin needed )</span>', 'vtprd' );
                      vtprd_show_object_hover_help ('buy_group_groups_needed', 'wizard');
                  } ?> 
                  <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                    <?php vtprd_show_object_hover_help ('buy_group_groups_incl', 'wizard');?> 
                  </span>                 
              </div>   
          </div>
          
         <?php        
          if ( function_exists('_groups_get_tablename') ) {            
         ?>          
          <div class="form-group2   pad-the-top  clear-left">        
      				<div class="form-field"><label class="buy-groups-excl-label right-col-label"><?php _e( 'Exclude Group', 'vtprd' ); ?></label>
                 <?php        
                  if ( function_exists('_groups_get_tablename') ) {          
                 ?>				    
                    <select class="vtprd-group-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_groups_excl_array[]" data-placeholder="<?php esc_attr_e( 'Search Group &hellip;', 'vtprd' ); ?>" data-action="vtprd_group_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->buy_group_population_info['buy_group_groups_excl_array'];
          						$this->vtprd_build_group_selects($checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required">( <a id="" class="" href="https://wordpress.org/plugins/groups/">Groups</a> - free Groups plugin needed )</span>', 'vtprd' );
                  } ?>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_groups_excl', 'wizard');?> 
                 </span>                 
              </div>               
          </div>
         <?php        
          }            
         ?>           
          
        </div> 

         
         <?php //MEMBERSHIPS  all the functions needed: https://docs.woocommerce.com/document/woocommerce-memberships-function-reference/  ?>    
 
         
         <?php //memberships   based on the PAY Woocommerce members: https://woocommerce.com/products/woocommerce-memberships/  ?>    
 
        <div class="incl-excl-group bottom-horiz-line buy-group-memberships-incl-excl-group clear-left">
          <div class="form-group2  clear-left">        
      				<div class="form-field"><label class="buy-members-incl-label right-col-label"><?php _e( 'Membership', 'vtprd' ); if ( !function_exists('wc_memberships') ) { _e( '<br><br>Exclude Membership', 'vtprd' ); } ?></label>
                 <?php        
                  if ( function_exists('wc_memberships') ) {            
                 ?> 
        				    <select class="vtprd-group-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_memberships_incl_array[]" data-placeholder="<?php esc_attr_e( 'Search Membership &hellip;', 'vtprd' ); ?>" data-action="vtprd_membership_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->buy_group_population_info['buy_group_memberships_incl_array'];
          						$this->vtprd_build_memberships_selects($checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required hasWizardHelpRight">( <a id="" class="" href="https://woocommerce.com/products/woocommerce-memberships/">Memberships</a> plugin needed )</span>', 'vtprd' );
                      vtprd_show_object_hover_help ('buy_group_memberships_needed', 'wizard');
                  } ?> 
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_memberships_incl', 'wizard');?> 
                 </span>                 
              </div>   
          </div>

         <?php        
          if ( function_exists('wc_memberships') ) {            
         ?>           
          <div class="form-group2   pad-the-top  clear-left">        
      				<div class="form-field"><label class="buy-members-excl-label right-col-label"><?php _e( 'Exclude Membership', 'vtprd' ); ?></label>
                 <?php        
                  if ( function_exists('wc_memberships') ) {          
                 ?>				    
                    <select class="vtprd-group-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="buy_group_memberships_excl_array[]" data-placeholder="<?php esc_attr_e( 'Search Membership &hellip;', 'vtprd' ); ?>" data-action="vtprd_membership_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->buy_group_population_info['buy_group_memberships_excl_array'];
          						$this->vtprd_build_memberships_selects($checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required">( <a id="" class="" href="https://woocommerce.com/products/woocommerce-memberships/">Memberships</a> plugin needed )</span>', 'vtprd' );
                  } ?>  
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                   <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_memberships_excl', 'wizard');?>
                 </span> 
              </div>               
          </div>
         <?php        
          }           
         ?>            
          
        </div>
        
         
      </div>  <?php //end buy-group-select-customer-area box around the customer selections  ?> 

     </div><!-- //buy_group_box -->
       
     <?php 
    //***************   
    //v2.0.0 end 
    //***************   
 
}
      

                                                                            
    public    function vtprd_action_group_cntl() { 
       global $post, $vtprd_info, $vtprd_rule, $vtprd_rule_display_framework, $vtprd_rules_set;
       $selected = 'selected="selected"';
       $checked = 'checked="checked"';                                                  

         //*****************************
         //v2.0.0 begin
         //*****************************
       if(defined('VTPRD_PRO_DIRNAME')) { 
          $prodcat_msg    =  __( 'Search Product Cat &hellip;', 'vtprd' ); 
          $plugincat_msg  =  __( 'Search Pricing Deal Cat&hellip;', 'vtprd' );
          $product_msg    =  __( 'Search Product &hellip;', 'vtprd' );
          $role_msg       =  __( 'Search Role &hellip;', 'vtprd' );
          $email_msg      =  __( 'Search Email or Name &hellip;', 'vtprd' );
          $selector_msg   = null;                
       } else {
          $pro_only_msg   =  __( '* Pro - only * Search &hellip;', 'vtprd' );
          $prodcat_msg    =  $pro_only_msg; 
          $plugincat_msg  =  $pro_only_msg; 
          $product_msg    =  $pro_only_msg; 
          $role_msg       =  $pro_only_msg; 
          $email_msg      =  $pro_only_msg; 
          $selector_msg   = '<h4 class="clear-left free-warning">Free version - yellow selectors not available </h4>';                   
       } ?>
      
      
      <div class="action-group-select-product-area  clear-left"> <?php  /* box around the product selections  */ ?>
      
        <h4 class="select-sub-heading action-group-select-sub-heading clear-left">Select Products: &nbsp; by Category / Product / Variation Name across Products / Brands</h4>
                
        <?php echo $selector_msg  //FREE Version blue message, yellow selectors not available; ?> 
        
         <?php //PROD CATEGORIES ?> 
        <div class="incl-excl-group top-horiz-line bottom-horiz-line action-group-prod-cat-incl-excl-group clear-left">

          <div class="and-or-selector action-and-or-selector" id="action-and-or-selector-prod-cat">                                        
            <div class="switch-field"> 
              <span class="hasWizardHelpRight">
                <input id="action_group_prod_cat_and_or-AndSelect" class="and-or-selector-AndSelect" name="action_group_prod_cat_and_or" value="and" type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_prod_cat_and_or'] == 'and') { echo $checked; } ?> >
                <label id="action_group_prod_cat_and_or-AndSelect-label" for="action_group_prod_cat_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('action_group_prod_cat_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="action_group_prod_cat_and_or-OrSelect"  class="" name="action_group_prod_cat_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_prod_cat_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label id="action_group_prod_cat_and_or-OrSelect-label"  for="action_group_prod_cat_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('action_group_prod_cat_OrSelect', 'wizard'); ?> 
            </div>               
          </div>
                            
          <div class="form-group2 clear-both  action_group_prod_cat_incl">        
      				<div class="form-field"><label class="action-prod-category-incl-label right-col-label"><?php _e( 'Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="action_group_prod_cat_incl_array[]" data-catid="prod_cat" data-placeholder="<?php echo $prodcat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['parent_plugin_taxonomy'];
                  $checked_list = $vtprd_rule->action_group_population_info['action_group_prod_cat_incl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_prod_cat_incl', 'small');?>
                 </span>              
              </div>
          </div>
         
          <div class="form-group2 pad-the-top clear-left    action_group_prod_cat_excl">     
      				<div class="form-field"><label class="action-prod-category-excl-label  right-col-label"><?php _e( 'Exclude Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search  left-col-data" multiple="multiple" style="width: 500px;" name="action_group_prod_cat_excl_array[]" data-catid="prod_cat" data-placeholder="<?php echo $prodcat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['parent_plugin_taxonomy'];
                  $checked_list = $vtprd_rule->action_group_population_info['action_group_prod_cat_excl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_prod_cat_excl', 'small');?>
                 </span>              
              </div>
          </div>
          
        </div>        

         
         <?php //PRICING DEAL CATEGORIES ?> 
        <div class="incl-excl-group bottom-horiz-line action-group-plugin-cat-incl-excl-group clear-left">

          <div class="and-or-selector action-and-or-selector" id="action-and-or-selector-plugin-cat">                                        
            <div class="switch-field">  
              <span class="hasWizardHelpRight">
                <input id="action_group_plugin_cat_and_or-AndSelect" class="and-or-selector-AndSelect" name="action_group_plugin_cat_and_or" value="and" type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_plugin_cat_and_or'] == 'and') { echo $checked; } ?> >
                <label id="action_group_plugin_cat_and_or-AndSelect-label" for="action_group_plugin_cat_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('action_group_plugin_cat_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="action_group_plugin_cat_and_or-OrSelect"  class="" name="action_group_plugin_cat_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_plugin_cat_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label id="action_group_plugin_cat_and_or-OrSelect-label" for="action_group_plugin_cat_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('action_group_plugin_cat_OrSelect', 'wizard'); ?> 
            </div>
          </div>
                   
          <div class="form-group2  clear-both   action_group_plugin_cat_incl">       
      				<div class="form-field"><label class="action-plugin-category-incl-label  right-col-label"><?php _e( 'Pricing Deal <br>Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search  left-col-data" multiple="multiple" style="width: 500px;" name="action_group_plugin_cat_incl_array[]" data-catid="rule_cat" data-placeholder="<?php echo $plugincat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['rulecat_taxonomy'];
                  $checked_list = $vtprd_rule->action_group_population_info['action_group_plugin_cat_incl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_incl', 'small');?>
                 </span>              
              </div>
          </div>
          
          <div class="form-group2 pad-the-top clear-left  action_group_plugin_cat_excl">        
      				<div class="form-field"><label class="action-plugin-category-excl-label right-col-label"><?php _e( 'Exclude <br>Pricing Deal <br>Category', 'vtprd' ); ?></label>
    				    <select class="vtprd-category-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="action_group_plugin_cat_excl_array[]" data-catid="rule_cat" data-placeholder="<?php echo $plugincat_msg; ?>" data-action="vtprd_category_search_ajax">
      					<?php
                  $taxonomy = $vtprd_info['rulecat_taxonomy'];
                  $checked_list = $vtprd_rule->action_group_population_info['action_group_plugin_cat_excl_array'];
      						$this->vtprd_build_cat_selects($taxonomy, $checked_list); 
      					?>
                </select>
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_plugin_cat_excl', 'small');?>
                 </span>              
              </div>
          </div> 
                 
        </div>
                 
         <?php //PRODUCTS ?>               
         <div class="incl-excl-group bottom-horiz-line action-group-product-incl-excl-group clear-left"> 

          <div class="and-or-selector action-and-or-selector" id="action-and-or-selector-product">                                        
            <div class="switch-field"> 
              <span class="hasWizardHelpRight">
                <input id="action_group_product_and_or-AndSelect" class="and-or-selector-AndSelect" name="action_group_product_and_or" value="and" type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_product_and_or'] == 'and') { echo $checked; } ?> >
                <label id="action_group_product_and_or-AndSelect-label" for="action_group_product_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('action_group_product_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="action_group_product_and_or-OrSelect"  class="" name="action_group_product_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_product_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label id="action_group_product_and_or-OrSelect-label"  for="action_group_product_and_or-OrSelect" class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('action_group_product_OrSelect', 'wizard'); ?> 
            </div>
          </div>
                     
                              
           <div class="form-group2 clear-both  action_group_product_incl">
              <div class="form-field"><label class="action-product-incl-label  right-col-label"><?php _e( 'Product', 'vtprd' ); ?></label>                                          
      				    <select class="vtprd-product-search left-col-data action-product-incl-select" multiple="multiple" style="width: 500px;" name="action_group_product_incl_array[]" data-placeholder="<?php echo $product_msg; ?>" data-action="vtprd_product_search_ajax">
        					<?php
                    $product_ids = $vtprd_rule->action_group_population_info['action_group_product_incl_array'];        

        						//v2.0.0.9a begin
                    foreach ( $product_ids as $product_id ) {
        							if ($product_id > ' ') {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                          $product_name = $product->get_formatted_name(); 
                          if (vtprd_test_for_variations($product_id)) {
                            $product_name .= '&nbsp; [all variations] ';
                          }
          								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product_name ) . '</option>';
          							}
                      }
        						}
                    //v2.0.0.9a end
                    
        					?>
                  </select>                                                   
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_product_incl', 'small');?>
                 </span>                                                                 
              </div>                                                    
          </div>
          
           <div class="form-group2 pad-the-top clear-left  action_group_product_excl">
              <div class="form-field"><label class="action-product-excl-label  right-col-label"><?php _e( 'Exclude Product', 'vtprd' ); ?></label>                           
      				    <select class="vtprd-product-search left-col-data" multiple="multiple" style="width: 500px;" name="action_group_product_excl_array[]" data-placeholder="<?php echo $product_msg; ?>" data-action="vtprd_product_search_ajax">
        					<?php
                    $product_ids = $vtprd_rule->action_group_population_info['action_group_product_excl_array'];

        						//v2.0.0.9a begin
                    foreach ( $product_ids as $product_id ) {
        							if ($product_id > ' ') {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                          $product_name = $product->get_formatted_name(); 
                          if (vtprd_test_for_variations($product_id)) {
                            $product_name .= '&nbsp; [all variations] ';
                          }
          								echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product_name ) . '</option>';
          							}
                      }
        						}
                    //v2.0.0.9a end                    
                    
        					?>
                  </select>                                                   
                 <span class="shortIntro-b-and-w shortIntro-select2" >
                    <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_product_excl', 'small');?>
                 </span>
              </div>                                                                                                         
          </div>
          
        </div>


         <?php //VARNAME ?> 
         <div class="incl-excl-group bottom-horiz-line action-group-var-name-incl-excl-group clear-left">

          <div class="and-or-selector action-and-or-selector" id="action-and-or-selector-var-name">                                        
            <div class="switch-field">
              <span class="hasWizardHelpRight">
                <input id="action_group_var_name_and_or-AndSelect" class="and-or-selector-AndSelect" name="action_group_var_name_and_or" value="and" type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_var_name_and_or'] == 'and') { echo $checked; } ?> >
                <label id="action_group_var_name_and_or-AndSelect-label" for="action_group_var_name_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('action_group_var_name_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="action_group_var_name_and_or-OrSelect"  class="" name="action_group_var_name_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_var_name_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label for="action_group_var_name_and_or-OrSelect" class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('action_group_var_name_OrSelect', 'wizard'); ?> 
            </div>
          </div>
                            
           <div class="form-group2 clear-both">        
      				<div class="form-field"><label class="action_group_var_name_incl-label right-col-label" style="margin-top: 30px;"><?php _e( 'Variation Name <br>Across Products', 'vtprd' ); ?></label>

               <?php
                   // large|red+extra large|blue (*full* variation name[s], separated by: | AND combined by: + )
                   $varName_array = $vtprd_rule->action_group_population_info['action_group_var_name_incl_array'];
                   $varName_string = $this->vtprd_stringify_var_name_array($varName_array);
              ?>
              <span class='varName-example'>Example:&nbsp;&nbsp;<?php echo $vtprd_info['default_by_varname_example']; ?> </span>
                         
               <span class="varName-area">
                   <textarea rows="1" cols="50" id="action_group_var_name_incl_array" class="action_group_var_name_incl_array_class" name="action_group_var_name_incl_array" placeholder="<?php _e( 'Enter attribute names &hellip;', 'vtprd' ); ?>"><?php echo $varName_string; ?></textarea>                 
               </span>              
               <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area">
                  <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  style="margin-left: -1.5% !important;" src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_group_var_name_incl', 'small');?>
               </span>                               
           </div>     
         </div>    


           <div class="form-group2 clear-left"  style="padding-top:15px;">        
      				
              <div class="form-field"><label class="action_group_var_name_excl-label right-col-label"><?php _e( 'Exclude <br>Variation Name <br>Across Products', 'vtprd' ); ?></label>

               <?php
                   // large|red+extra large|blue (*full* variation name[s], separated by: | AND combined by: + )
                   $varName_array = $vtprd_rule->action_group_population_info['action_group_var_name_excl_array'];
                   $varName_string = $this->vtprd_stringify_var_name_array($varName_array);
              ?>
                         
               <span class="varName-area">
                   <textarea rows="1" cols="50" id="action_group_var_name_excl_array" class="action_group_var_name_excl_array_class" name="action_group_var_name_excl_array" placeholder="<?php _e( 'Enter attribute names &hellip;', 'vtprd' ); ?>"><?php echo $varName_string; ?></textarea>                 
               </span>              
               <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" style="margin-left: -1% !important;">
                  <img  class="hasHoverHelp2 help-b-and-w" width="15px" alt=""  style="margin-left: -1.5% !important;"  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                  <?php vtprd_show_object_hover_help ('buy_group_var_name_excl', 'small');?>
               </span>                               

            
                <?php //v1.1.8.0 begin ?>
                <span class='varName_addl_info  clear-left'> 
                  <span class='varName_addl_info-line1  clear-left'><?php _e('<span style="color:#666;font-size: 13px;margin-left: -4%;font-weight: normal;">' .$vtprd_info['default_by_varname_msg']. '</span>', 'vtprd'); ?></span>
                  <span class='varName_addl_info-line2  clear-left'><?php _e('( <em>Changes To &nbsp; lowercase &nbsp; , &nbsp; removes leading and trailing spaces </em>)', 'vtprd'); ?></span>                  
                  <span class='varName_addl_info-line3  clear-left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('<em>If an Attribute Name has a space in the name between words, </em>', 'vtprd'); ?></span>
                  <span class='varName_addl_info-line4  clear-left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' <em>and the name is not found in testing, </em>', 'vtprd'); ?></span>                  
                  <span class='varName_addl_info-line5  clear-left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e(' <em>try replacing the space in the name with a dash "-" </em>', 'vtprd'); ?></span>          
                </span>
                <?php //v1.1.8.0 end ?>
                
             </div>     
           </div>   
                         
         </div>  <?php //end action_group_varName_exclude_area ?>   


         
         <?php //BRANDS         
          /* ********************************
          Pricing Deals Pro has built-in support for the following list of BRANDS Plugins .
          There's also the 'vtprd_add_brands_taxonomy' filter, which allows you to use ANY
          nominated custom taxonomy at the BRANDS selector 

          	If using a BRANDS plugin not in the supported list, add support by doing the following:
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	//change [brands plugin taxonomy] to the taxonomy of your brands plugin   
          	add_filter( 'vtprd_add_brands_taxonomy', function() { return  'brands plugin taxonomy'; } ); 
          
          Here's what we're prepared for: 
            
            Product Brands For WooCommerce
            https://wordpress.org/plugins/product-brands-for-woocommerce/
            taxonomy = 'product_brands'
            <a href="https://wordpress.org/plugins/product-brands-for-woocommerce/">Product Brands For WooCommerce</a>
            
            Perfect WooCommerce Brands
            https://wordpress.org/plugins/perfect-woocommerce-brands/
            taxonomy = 'pwb-brand'
            <a href="https://wordpress.org/plugins/perfect-woocommerce-brands/">Perfect WooCommerce Brands</a>
            
            Brands for WooCommerce
            https://wordpress.org/plugins/brands-for-woocommerce/
            taxonomy = 'berocket_brand'
            <a href="https://wordpress.org/plugins/brands-for-woocommerce/">Brands for WooCommerce</a>

            YITH WooCommerce Brands Add-On
            https://wordpress.org/plugins/yith-woocommerce-brands-add-on/
            taxonomy = 'yith_product_brand';
            <a href="https://wordpress.org/plugins/yith-woocommerce-brands-add-on/">YITH WooCommerce Brands Add-On</a>
            
            Ultimate WooCommerce Brands
            https://wordpress.org/plugins/ultimate-woocommerce-brands/
            taxonomy = "product_brand"
            <a href="https://wordpress.org/plugins/ultimate-woocommerce-brands/">Ultimate WooCommerce Brands</a>
            
            Woocommerce Brand
            https://wordpress.org/plugins/wc-brand/
            taxonomy = 'product_brand' 
            <a href="https://wordpress.org/plugins/wc-brand/">Woocommerce Brand</a> 
            
          */ 
          $tax_array = $vtprd_info['brands_taxonomy_array']; 
          //add_filter( 'vtprd_add_brands_taxonomy', function() { return  'brands plugin taxonomy'; } );               
          $filter_tax = apply_filters('vtprd_add_brands_taxonomy',FALSE );
          if ($filter_tax) {
            $tax_array[] = $filter_tax;
          } 
          $taxonomy = FALSE;
          foreach ( $tax_array as $tax ) {
            if (taxonomy_exists($tax)) { 
              $taxonomy = $tax;
              break;
            }
          }                             
         ?> 
         <div class="incl-excl-group bottom-horiz-line action-group-brands-incl-excl-group clear-left"> 
         
         <?php        
          if ($taxonomy) {           
         ?> 
          <div class="and-or-selector action-and-or-selector" id="action-and-or-selector-brands">                                        
            <div class="switch-field"> 
              <span class="hasWizardHelpRight">
                <input id="action_group_brands_and_or-AndSelect" class="and-or-selector-AndSelect" name="action_group_brands_and_or" value="and" type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_brands_and_or'] == 'and') { echo $checked; } ?> >
                <label id="action_group_brands_and_or-AndSelect-label" for="action_group_brands_and_or-AndSelect" class="and-or-selector-yes">And</label>
              </span> 
              <?php vtprd_show_object_hover_help ('action_group_brands_AndSelect', 'wizard'); ?> 
              <span class="hasWizardHelpRight">                                                       
                <input id="action_group_brands_and_or-OrSelect"  class="" name="action_group_brands_and_or" value="or"  type="radio" <?php if ( $vtprd_rule->action_group_population_info['action_group_brands_and_or'] == 'or' ) { echo $checked; } ?> > 
                <label for="action_group_brands_and_or-OrSelect"  class="and-or-selector-no">Or</label> 
              </span>
              <?php vtprd_show_object_hover_help ('action_group_brands_OrSelect', 'wizard'); ?> 
            </div> 
          </div>
         <?php        
          }          
         ?>  
                  
          <div class="form-group2 clear-both">      
      				<div class="form-field"><label class="action-brands-incl-label right-col-label"><?php _e( 'Brand', 'vtprd' ); if (!$taxonomy) { _e( '<br><br>Exclude Brand', 'vtprd' ); } ?></label>
                 <?php        
                  if ($taxonomy) {           
                 ?> 
        				    <select class="vtprd-brand-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="action_group_brands_incl_array[]" data-placeholder="<?php esc_attr_e( 'Search Brand &hellip;', 'vtprd' ); ?>" data-action="vtprd_brand_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->action_group_population_info['action_group_brands_incl_array'];
          						$this->vtprd_build_cat_selects($taxonomy,$checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required hasWizardHelpRight">( <span class="brand-lit">Brands</span> - free Brands plugin needed &nbsp;&nbsp;<em>[ hover for plugin list ]</em> )</span>', 'vtprd' );
                      vtprd_show_object_hover_help ('buy_group_brands_incl', 'wizard'); 
                  } ?> 
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_brands_incl', 'wizard'); ?>
                 </span>                 
              </div>   
          </div>

         <?php        
          if ($taxonomy) {           
         ?>           
          <div class="form-group2   pad-the-top clear-left">      
      				<div class="form-field"><label class="action-brands-excl-label right-col-label"><?php _e( 'Exclude Brand', 'vtprd' ); ?></label>
                 <?php        
                  if ($taxonomy) {          
                 ?>				    
                    <select class="vtprd-brand-search vtprd-noajax-search left-col-data" multiple="multiple" style="width: 500px;" name="action_group_brands_excl_array[]" data-placeholder="<?php esc_attr_e( 'Search Brand &hellip;', 'vtprd' ); ?>" data-action="vtprd_brand_search_ajax">
          					<?php
                      $checked_list = $vtprd_rule->action_group_population_info['action_group_brands_excl_array'];
          						$this->vtprd_build_cat_selects($taxonomy,$checked_list); 
          					?>
                    </select>
                  <?php } else {
                      _e( '<span class="plugin-required hasWizardHelpRight">( Brands - free Brands plugin needed )</span>', 'vtprd' );
                      vtprd_show_object_hover_help ('buy_group_brands_incl', 'wizard'); 
                  } ?> 
                 <span class="shortIntro-b-and-w shortIntro-select2 question-mark-area" >
                    <img  class="hasHoverHelp2 help-b-and-w hasWizardHelpRight" width="15px" alt=""  src="<?php echo VTPRD_URL;?>/admin/images/help-b-and-w.png" /> 
                   <?php vtprd_show_object_hover_help ('buy_group_brands_excl', 'wizard');?>
                 </span> 
              </div>               
          </div>
         <?php        
          }           
         ?>                     
        </div>  

      </div> <?php  /* END action-group-select-product-area  */ ?>
          
    <?php 
    //*************
    //v2.0.0 end   
    //*************
 
    }  
      
  
    public    function vtprd_pop_in_specifics( ) {                     
       global $post, $vtprd_info, $vtprd_rule; $vtprd_rules_set;
       $checked = 'checked="checked"';  
  ?>
        
       <div class="column1" id="specDescrip">
          <h4><?php _e('How is the Rule applied to the search results?', 'vtprd');?></h4>
          <p><?php _e("Once we've figured out the population we're working on (cart only or specified groups),
          how do we apply the rule?  Do we look at each product individually and apply the rule to
          each product we find?  Or do we look at the population as a group, and apply the rule to the
          group as a tabulated whole?  Or do we apply the rule to any we find, and limit the application 
          of the rule to a certain number of products?", 'vtprd');?>           
          </p>
       </div>
       <div class="column2" id="specChoiceIn">
          <h3><?php _e('Select Rule Application Method', 'vtprd');?></h3>
          <div id="specRadio">
            <span id="Choice-input-span">
                <?php
               for($i=0; $i < sizeof($vtprd_rule->specChoice_in); $i++) { 
               ?>                 

                  <input id="<?php echo $vtprd_rule->specChoice_in[$i]['id']; ?>" class="<?php echo $vtprd_rule->specChoice_in[$i]['class']; ?>" type="<?php echo $vtprd_rule->specChoice_in[$i]['type']; ?>" name="<?php echo $vtprd_rule->specChoice_in[$i]['name']; ?>" value="<?php echo $vtprd_rule->specChoice_in[$i]['value']; ?>" <?php if ( $vtprd_rule->specChoice_in[$i]['user_input'] > ' ' ) { echo $checked; } ?> /><?php echo $vtprd_rule->specChoice_in[$i]['label']; ?><br />

               <?php
                }
               ?>  
            </span>
            <span class="" id="anyChoiceIn-span">
                <span><?php _e('*Any* applies to a *required*', 'vtprd');?></span><br />
                 <?php _e('Maximum of:', 'vtprd');?>                      
                 <input id="<?php echo $vtprd_rule->anyChoiceIn_max['id']; ?>" class="<?php echo $vtprd_rule->anyChoiceIn_max['class']; ?>" type="<?php echo $vtprd_rule->anyChoiceIn_max['type']; ?>" name="<?php echo $vtprd_rule->anyChoiceIn_max['name']; ?>" value="<?php echo $vtprd_rule->anyChoiceIn_max['value']; ?>" />
                 <?php _e('Products', 'vtprd');?>
            </span>           
          </div>                
       </div>                                                
       <div class="column3 specExplanation" id="allChoiceIn-chosen">
          <h4><?php _e('Treat the Selected Group as a Single Entity', 'vtprd');?><span> - <?php _e('explained', 'vtprd');?></span></h4>
          <p><?php _e("Using *All* as your method, you choose to look at all the products from your cart search results.  That means we add
          all the quantities and/or price across all relevant products in the cart, to test against the rule's requirements.", 'vtprd');?>           
          </p>
       </div>
       <div class="column3 specExplanation" id="eachChoiceIn-chosen">
          <h4><?php _e('Each in the Selected Group', 'vtprd');?><span> - <?php _e('explained', 'vtprd');?></span></h4>
          <p><?php _e("Using *Each* as your method, we apply the rule to each product from your cart search results.
          So if any of these products fail to meet the rule's requirements, the cart as a whole receives an error message.", 'vtprd');?>           
          </p>
       </div>
       <div class="column3 specExplanation" id="anyChoiceIn-chosen">
          <h4><?php _e('Apply the rule to any Individual Product in the Cart', 'vtprd');?><span> - <?php _e('explained', 'vtprd');?></span></h4>
          <p><?php _e("Using *Any*, we can apply the rule to any product in the cart from your cart search results, similar to *Each*.  However, there is a
          maximum number of products to which the rule is applied. The product group is checked to see if any of the group fail to reach the maximum amount
          threshhold.  If so, the error will be applied to products in the cart based on cart order, up to the maximum limit supplied.", 'vtprd');?>
          <br /> <br /> 
          <?php _e('For example, the rule might be something like:', 'vtprd');?>
          <br /> <br /> &nbsp;&nbsp;
          <?php _e('"You may buy a maximum of $10 for each of any of 2 products from this group."', 'vtprd');?>              
          </p>               
       </div> 
      
    <?php
  }  
                                                                           
    public    function vtprd_rule_id() {          
        global $post, $vtprd_rule;           
       
        if ($vtprd_rule->ruleInWords > ' ') { ?>
            <span class="ruleInWords" >              
               <span class="clear-left">  <?php echo $vtprd_rule->ruleInWords; ?></span><!-- /clear-left -->                              
            </span><!-- /ruleInWords -->              
        <?php } //end ruleInWords 
  } 
  
    public    function vtprd_rule_resources() {          
        echo '<a id="vtprd-rr-doc"  href="' . VTPRD_DOCUMENTATION_PATH . '"  title="Access Plugin Documentation">' . __('Plugin', 'vtprd'). '<br>' . __('Documentation', 'vtprd'). '</a>';
        //Back to the Top box, fixed at lower right corner!!!!!!!!!!
        echo '<a href="#" id="back-to-top-tab" class="show-tab">' . __('Back to Top', 'vtprd'). ' <strong>&uarr;</strong></a>';
  }   

      
    public    function vtprd_rule_scheduling() {             //periodicByDateRange
        global $vtprd_rule;
        
        //**********************************************************************************
        //script goes here, rather than in enqueued resources, due to timing issues 
        //**********************************************************************************
       ?>     
          <script type="text/javascript">
          jQuery.noConflict();
          jQuery(document).ready(function($) {
             //DatePicker                       
             // from  http://jquerybyexample.blogspot.com/2012/01/end-date-should-not-be-greater-than.html
                $("#date-begin-0").datepicker({
                  dateFormat : 'yy-mm-dd', 
                  minDate: 0,
                 // maxDate: "+60D",
                  numberOfMonths: 2,
                  onSelect: function(selected) {
                    $("#date-end-0").datepicker("option","minDate", selected)
                  }
              });
              $("#date-end-0").datepicker({ 
                  dateFormat : 'yy-mm-dd', 
                  minDate: 0,
                 // maxDate:"+60D",
                  numberOfMonths: 2,
                  onSelect: function(selected) {
                     $("#date-begin-0").datepicker("option","maxDate", selected)
                  }                             
              });

            });   
          </script>                            
     <?php       
     //load up default if no date range
     if ( sizeof($vtprd_rule->periodicByDateRange) == 0 ) {     
        $vtprd_rule->periodicByDateRange[0]['rangeBeginDate'] = date('Y-m-d');
        $vtprd_rule->periodicByDateRange[0]['rangeEndDate']   = (date('Y')+1) . date('-m-d') ;
     } 
     ?> 
        <span class="basic-begin-date-area blue-dropdown"> 
            <label class="begin-date first-in-line-label">&nbsp;<?php _e('Begin Date', 'vtprd');?></label> 
            <input type='text' id='date-begin-0' class='pickdate  clear-left' size='7' value="<?php echo $vtprd_rule->periodicByDateRange[0]['rangeBeginDate']; ?>" name='date-begin-0' readonly="readonly" />				
        </span>        
        <span class="basic-end-date-area blue-dropdown">          
          <label class="end-date first-in-line-label">&nbsp;<?php _e('End Date', 'vtprd');?></label>                      
          <input type='text' id='date-end-0'   class='pickdate   clear-left' size='7' value="<?php echo $vtprd_rule->periodicByDateRange[0]['rangeEndDate']; ?>"   name='date-end-0' readonly="readonly"  />          
        </span>        
        
    <?php      
       global $vtprd_setup_options;
       /* scaring the punters
       if ( $vtprd_setup_options['use_this_timeZone'] == 'none') {
          echo __('<span id="options-setup-error" style="color:red !important;">Scheduling requires setup: <a  href="'.VTPRD_ADMIN_URL.'edit.php?post_type=vtprd-rule&page=vtprd_setup_options_page"  title="select">Please - Click Here - to Select the Store GMT Time Zone</a></span>', 'vtprd'); 
        }          
       */
  }   

  public  function vtprd_change_title_currency_symbol( $variable_name, $i, $currency_symbol ) {
     global $vtprd_deal_screen_framework;
      //replace $$ with setup currency!!                        
      $vtprd_deal_screen_framework[$variable_name]['option'][$i]['title'] = 
                str_replace('$$', $currency_symbol, $vtprd_deal_screen_framework[$variable_name]['option'][$i]['title'] );
  }    
  

    function vtprd_load_forThePriceOf_literal($k) {
      global $vtprd_rule;
     if (($vtprd_rule->rule_deal_info[$k]['discount_amt_type'] =='forThePriceOf_Units') ||
         ($vtprd_rule->rule_deal_info[$k]['discount_amt_type'] =='forThePriceOf_Currency')) {
        switch ($vtprd_rule->rule_template) {
          case 'C-forThePriceOf-inCart':    //buy-x-action-forThePriceOf-same-group-discount              
              echo ' Buy ';
              echo $vtprd_rule->rule_deal_info[$k]['buy_amt_count'];
            break;
          case 'C-forThePriceOf-Next':  //buy-x-action-forThePriceOf-other-group-discount
              echo ' Get ';
              echo $vtprd_rule->rule_deal_info[$k]['action_amt_count'];
            break;
        }
      }
    }


    //remove conflict with all-in-one seo pack!!  
    //  from http://wordpress.stackexchange.com/questions/55088/disable-all-in-one-seo-pack-for-some-custom-post-types
    function vtprd_remove_all_in_one_seo_aiosp() {
        $cpts = array( 'vtprd-rule' );
        foreach( $cpts as $cpt ) {
            remove_meta_box( 'aiosp', $cpt, 'advanced' );
        }
    }


    
  /*
    *  taxonomy (r) - registered name of taxonomy
    *  tax_class (r) - name options => 'prodcat-in' 'prodcat-out' 'rulecat-in' 'rulecat-out'
    *             refers to product taxonomy on the candidate or action categories,
    *                       rulecat taxonomy on the candidate or action categories
    *                         :: as there are only these 4, they are unique   
    *  checked_list (o) - selection list from previous iteration of rule selection                              
    *                          
   */

  //*******************
  //v2.0.0 recoded and renamed for select2
  //*******************
  public function vtprd_build_cat_selects ($taxonomy, $checked_list) { //v2.0.0.9 removed " = NULL "
        //error_log( print_r(  'function vtprd_build_cat_selects begin in rules-ui, $taxonomy= ' .$taxonomy, true ) );
        //error_log( print_r(  '$checked_list = ', true ) );
        //error_log( var_export($checked_list, true ) );
        
        global $wpdb, $vtprd_info;         

              $sql = "SELECT terms.`term_id`, terms.`name`  FROM `" . $wpdb->prefix . "terms` as terms, `" . $wpdb->prefix . "term_taxonomy` as term_taxonomy  WHERE   terms.`term_id` = term_taxonomy.`term_id` AND term_taxonomy.`taxonomy` = '" . $taxonomy . "' ORDER BY terms.`name` ASC";  
              $categories = $wpdb->get_results($sql,ARRAY_A) ;
        
                
        if (sizeof($categories) == 0) {
      		echo '<option value="0"' 
      		. 'selected="selected">No Entries Established</option>';
          return;            
        } 
        
       //error_log( print_r(  '$categories', true ) );
       //error_log( var_export($categories, true ) ); 
               
        foreach ($categories as $category) {
            $term_id = $category['term_id']; 
            if (in_array( $term_id, $checked_list )) {
          		echo '<option value="' . esc_attr( $term_id ) . '"' 
          		. 'selected="selected">' 
          		. esc_html( $category['name'] ) . '</option>';
            }  else {
          		echo '<option value="' . esc_attr( $term_id ) . '"' 
          		. '>' 
          		. esc_html( $category['name'] ) . '</option>'; 
             
             //error_log( print_r(  'option created cat = ' .$category['name'], true ) );           
            }        
         }    
         return;
   
    }


  //*******************
  //v2.0.0 new function
  //*******************
  public function vtprd_build_role_selects ($checked_list) {  //v2.0.0.9 removed " = NULL "
        //error_log( print_r(  'function vtprd_build_role_selects begin', true ) );
        
        global $wpdb, $vtprd_info;         

        $roles = get_editable_roles();
        
        $roles['notLoggedIn'] = array( 'name' => 'Not logged in (just visiting)' );

        foreach ($roles as $role => $info) {
            $name_translated = translate_user_role( $info['name'] );
            
            if (in_array( $role, $checked_list )) {
          		echo '<option value="' . $role . '"' 
          		. 'selected="selected">' 
          		. $name_translated . '</option>';
            }  else {
          		echo '<option value="' . $role . '"' 
          		. '>' 
          		. $name_translated . '</option>';            
            }        
         }
   
         return;
    }


  //*******************
  //v2.0.0 new function
  //*******************
  public function vtprd_build_customer_selects ($checked_list = NULL ) {
//redo to go directly after key, rather than do array check!!
        //error_log( print_r(  'function vtprd_build_customer_selects begin ', true ) ); 
        global $wpdb, $vtprd_info;         
  
        foreach ($checked_list as $user_id) {                                     
            $sql = "SELECT *  FROM `" . $wpdb->prefix . "users`   WHERE   `ID` = '" . $user_id . "' ";  
                                        
		        $user = $wpdb->get_results($sql,ARRAY_A) ; 
            if ($user) { 
              //result is a single iteration array, with an occurrence!! 
              $email_and_name = $user[0]['user_email'] .' ('. $user[0]['display_name'] .')';

              echo '<option value="' . esc_attr( $user_id ) . '"' 
          		. 'selected="selected">' 
          		. esc_html( $email_and_name ) . '</option>'; 
              /*
              $option = '<option value="' . esc_attr( $user_id ) . '"' 
          		. 'selected="selected">' 
          		. esc_html( $email_and_name ) . '</option>';
              echo $option;
              */              
            }          
         }    
         return;
    }

/*
Groups By itthinx
https://wordpress.org/plugins/groups/


$user_id = isset( $user->ID ) ? $user->ID : isset( $args[1] ) ? $args[1] : 0;

		global $wpdb;

		$group_table = _groups_get_tablename( 'group' );
		$user_group_table = _groups_get_tablename( 'user_group' );
		// We can end up here while a blog is being deleted, in that case, 
		// the tables have already been deleted.
		if ( ( $wpdb->get_var( "SHOW TABLES LIKE '" . $group_table . "'" ) == $group_table ) &&
			( $wpdb->get_var( "SHOW TABLES LIKE '" . $user_group_table . "'" ) == $user_group_table )
		) {

			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $user_group_table
				LEFT JOIN $group_table ON $user_group_table.group_id = $group_table.group_id
				WHERE $user_group_table.user_id = %d
				",
				Groups_Utility::id( $user_id )
			) );
			if ( $rows ) {
				foreach( $rows as $row ) {
					// don't optimize that, favour standard deletion
					self::delete( $row->user_id, $row->group_id );
				}
*/
  //*******************
  //v2.0.0 new function
  //*******************
  public function vtprd_build_group_selects($checked_list = NULL ) {
        //error_log( print_r(  'function vtprd_build_group_selects begin', true ) );
        
        global $wpdb, $vtprd_info;         

              $sql = "SELECT *  FROM `" . $wpdb->prefix . "groups_group`  ORDER BY `name` ASC";   
              $groups = $wpdb->get_results($sql,ARRAY_A) ;
        
       //error_log( print_r(  '$groups', true ) );
      //error_log( var_export($groups, true ) ); 

        
        if (sizeof($groups) == 0) {
      		echo '<option value="0"' 
      		. 'selected="selected">No Groups Established</option>';
          return;            
        }
               
        foreach ($groups as $group) {
            $group_id = $group['group_id']; 
            if (in_array( $group_id, $checked_list )) {
          		echo '<option value="' . esc_attr( $group_id ) . '"' 
          		. 'selected="selected">' 
          		. esc_html( $group['name'] ) . '</option>';
            }  else {
          		echo '<option value="' . esc_attr( $group_id ) . '"' 
          		. '>' 
          		. esc_html( $group['name'] ) . '</option>'; 
             
             //error_log( print_r(  'option created cat = ' .$group['name'], true ) );           
            }        
         }    
         return;                  
    }

  //*******************
  //v2.0.0 new function
  //*******************
  public function vtprd_build_memberships_selects($checked_list = NULL ) {
        //error_log( print_r(  'function vtprd_build_memberships_selects begin', true ) );
        
        global $wpdb, $vtprd_info;         

        //this comes back as POSTS with post_type of 'wc_membership_plan'
        $all_membership_plans = wc_memberships_get_membership_plans();
        
       //error_log( print_r(  '$all_membership_plans', true ) );
       //error_log( var_export($all_membership_plans, true ) ); 
        
        if (sizeof($all_membership_plans) == 0) {
      		echo '<option value="0"' 
      		. 'selected="selected">No Memberships Established</option>';
          return;            
        }       
        
        
        foreach ($all_membership_plans as $membership_plan) {
        
            $membership_plan_id = $membership_plan->id; 
            if (in_array( $membership_plan_id, $checked_list )) {
          		echo '<option value="' . esc_attr( $membership_plan_id ) . '"' 
          		. 'selected="selected">' 
          		. esc_html( $membership_plan->name ) . '</option>';
            }  else {
          		echo '<option value="' . esc_attr( $membership_plan_id ) . '"' 
          		. '>' 
          		. esc_html( $membership_plan->name ) . '</option>'; 
             
             //error_log( print_r(  'option created cat = ' .$membership_plan->name, true ) );           
            }        
         }    
         return;                  
    }

  
 
  //BUILD A DEFAULT RULE       
  public  function vtprd_build_new_rule() {
      global $post, $vtprd_info, $vtprd_rule, $vtprd_rules_set, $vtprd_deal_structure_framework, $vtprd_edit_arrays_framework; //v2.0.0 added $vtprd_edit_arrays_framework
                    
        //initialize rule
        $vtprd_rule = new VTPRD_Rule;
 
         //fill in standard default values not already supplied
         
        //load the 1st iteration of deal info by default    => internal defaults set in vtprd_deal_structure_framework


        //***************
        //v2.0.0.9 begin
        //***************
        $vtprd_rule->rule_deal_info[] = vtprd_build_rule_deal_info();
        
        /*
        $vtprd_rule->rule_deal_info[] = $vtprd_deal_structure_framework;  

        $vtprd_rule->rule_deal_info[0]['buy_repeat_condition'] = 'none'; 
        $vtprd_rule->rule_deal_info[0]['buy_amt_type'] = 'none';
        $vtprd_rule->rule_deal_info[0]['buy_amt_mod'] = 'none';
        $vtprd_rule->rule_deal_info[0]['buy_amt_applies_to'] = 'all';
        $vtprd_rule->rule_deal_info[0]['action_repeat_condition'] = 'none'; 
        $vtprd_rule->rule_deal_info[0]['action_amt_type'] = 'none';  
        $vtprd_rule->rule_deal_info[0]['action_amt_mod'] = 'none';
        $vtprd_rule->rule_deal_info[0]['action_amt_applies_to'] = 'all';
        $vtprd_rule->rule_deal_info[0]['discount_amt_type'] = '0';
        $vtprd_rule->rule_deal_info[0]['discount_applies_to'] = 'each';
        $vtprd_rule->rule_deal_info[0]['discount_rule_max_amt_type'] = 'none';
        $vtprd_rule->rule_deal_info[0]['discount_lifetime_max_amt_type'] = 'none';
        $vtprd_rule->rule_deal_info[0]['discount_rule_cum_max_amt_type'] = 'none'; 
        */
        //v2.0.0.9 end
        $vtprd_rule->cumulativeRulePricing = 'yes';   
        $vtprd_rule->cumulativeSalePricing = 'addToSalePrice';   //v1.0.4 
        $vtprd_rule->cumulativeCouponPricing = 'yes';
               //discount occurs 5 times
        $vtprd_rule->ruleApplicationPriority_num = '10';         
        $vtprd_rule->rule_type_selected_framework_key =  'Title01'; //default 1st title for BOTH dropdowns
        
        $vtprd_rule->inPop = 'wholeStore';  //apply to all products
        //$vtprd_rule->role_and_or_in = 'or'; //v2.0.0
        $vtprd_rule->actionPop = 'sameAsInPop' ; 
        //$vtprd_rule->role_and_or_out = 'or'; //v2.0.0
        
        //new upper selects 

        $vtprd_rule->cart_or_catalog_select =  'cart'; //v2.0.0 New Defaults
        $vtprd_rule->pricing_type_select = 'choose';
        $vtprd_rule->minimum_purchase_select =  'next'; //v2.0.0 New Defaults
        $vtprd_rule->buy_group_filter_select = 'choose';
        $vtprd_rule->get_group_filter_select = 'choose';
        $vtprd_rule->rule_on_off_sw_select = 'onForever'; //v1.0.7.5 changed from 'on' 
        $vtprd_rule->wizard_on_off_sw_select = 'on';
        $vtprd_rule->rule_type_select = 'advanced'; //v2.0.0 was 'basic'    
              
        $vtprd_rule->buy_group_population_info = $vtprd_edit_arrays_framework['buy_group_framework']; //v2.0.0 
        $vtprd_rule->action_group_population_info = $vtprd_edit_arrays_framework['action_group_framework']; //v2.0.0 
                 
    return;
  }        
     //lots of selects change their values between standard and 'discounted' titles.
    //This is where we supply the HIDEME alternative titles
  public  function vtprd_print_alternative_title_selects() {
      global $vtprd_rule_display_framework, $vtprd_deal_screen_framework;
      ?>          
             
           <?php 
           /* +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
             Hidden Selects containing various versions of the Select Option texts.
             
                #1  = the default version of the titles
                #2  = the altenate (Discount) version of the titles
              
              Both are supplied, so the JS can toggle between these two sets,
              as needed by the Upper select choices
              +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
           */ ?>  
             <?php //Upper  pricint_type_select?>  
              <select id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['pricing_type_select']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['pricing_type_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['pricing_type_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['title'];
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_rule_display_framework['pricing_type_select']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['pricing_type_select']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_rule_display_framework['pricing_type_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['pricing_type_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      //v2.0.0 removed   $title = $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_rule_display_framework['pricing_type_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>   
                          
             
             <?php //Upper  minimum_purchase_select?>  
              <select id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['minimum_purchase_select']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['minimum_purchase_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title => in this case, title and title3
                      //v2.0.0 removed   $title = $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['title'];
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_rule_display_framework['minimum_purchase_select']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_rule_display_framework['minimum_purchase_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['minimum_purchase_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      //v2.0.0 removed   $title = $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_rule_display_framework['minimum_purchase_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>   
             
             <?php //Upper  buy_group_filter_select?>  
              <select id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['buy_group_filter_select']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['buy_group_filter_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title => in this case, title and title3
                      $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title3'];                        
                      }
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['id'] .'2';?>" class="<?php echo$vtprd_rule_display_framework['buy_group_filter_select']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['buy_group_filter_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title2'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title4'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title4'];                        
                      }                                     
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>
              <select id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_rule_display_framework['buy_group_filter_select']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_rule_display_framework['buy_group_filter_select']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['buy_group_filter_select']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      //v2.0.0 removed   $title = $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_rule_display_framework['buy_group_filter_select']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                  
      
             <?php //buy_amt_type ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"    ><?php //v2.0.0 removed   echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_type']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_type']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      //v2.0.0 removed   $title = $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_type']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                   
               
             <?php //buy_amt_applies_to ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_applies_to']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_applies_to']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_applies_to']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_applies_to']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_applies_to']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['value']; ?>"    ><?php //v2.0.0 removed    echo $vtprd_deal_screen_framework['buy_amt_applies_to']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
               
             <?php //buy_amt_mod ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_mod']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_mod']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_amt_mod']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_amt_mod']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_amt_mod']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['value']; ?>"    ><?php //v2.0.0 removed    echo $vtprd_deal_screen_framework['buy_amt_mod']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
             
            <?php //buy_repeat_condition ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"    ><?php //v2.0.0 removed   echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
              <select id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['id'] .'-catalog';?>" class="<?php echo$vtprd_deal_screen_framework['buy_repeat_condition']['select']['class'] .'-catalog'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['name'] .'-catalog';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['buy_repeat_condition']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['buy_repeat_condition']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      //v2.0.0 removed   $title = $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['title-catalog'];
                
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['id'] .'-catalog'; ?>"  class="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['class'] .'-catalog'; ?>"  value="<?php echo $vtprd_deal_screen_framework['buy_repeat_condition']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>
      
             <?php //action_amt_type ?>  
              <select id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['id'] .'1';?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_type']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value']; ?>"    ><?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['id'] .'2';?>" class="<?php echo$vtprd_deal_screen_framework['action_amt_type']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_deal_screen_framework['action_amt_type']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_deal_screen_framework['action_amt_type']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_deal_screen_framework['action_amt_type']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['value']; ?>"    ><?php //v2.0.0 removed   echo $vtprd_deal_screen_framework['action_amt_type']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select> 
               
            <?php //inPop ?>  
              <select id="<?php echo $vtprd_rule_display_framework['inPop']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['inPop']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['inPop']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['inPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['inPop']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['inPop']['option'][$i]['title3'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title3'];                        
                      }                  
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['inPop']['select']['id'] .'2';?>" class="<?php echo$vtprd_rule_display_framework['inPop']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['inPop']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_rule_display_framework['inPop']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['inPop']['option']); $i++) { 
                                             
                      //pick up the free/pro version of the title 
                      $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title2'];
                      if ( ( defined('VTPRD_PRO_DIRNAME') ) &&
                           ( $vtprd_rule_display_framework['inPop']['option'][$i]['title4'] > ' ' ) ) {
                        $title = $vtprd_rule_display_framework['inPop']['option'][$i]['title4'];                        
                      }                   
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_rule_display_framework['inPop']['option'][$i]['value']; ?>"    ><?php echo $title; ?></option>
                 <?php } ?> 
               </select>  
                 
             <?php //specChoice_in ?>  
              <select id="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['id'] .'1';?>" class="<?php echo$vtprd_rule_display_framework['specChoice_in']['select']['class'] .'1'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['name'] .'1';?>" tabindex="<?php //echo $vtprd_rule_display_framework['specChoice_in']['select']['tabindex']; ?>" >          
                 <?php
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['specChoice_in']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['id'] .'1'; ?>"  class="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['class'] .'1'; ?>"  value="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['value']; ?>"    ><?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['title']; ?></option>
                 <?php } ?> 
               </select>                                        
              <select id="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['id'] .'2';?>" class="<?php echo$vtprd_rule_display_framework['specChoice_in']['select']['class'] .'2'; ?> hideMe" name="<?php echo $vtprd_rule_display_framework['specChoice_in']['select']['name'] .'2';?>" tabindex="<?php //echo $vtprd_rule_display_framework['specChoice_in']['select']['tabindex']; ?>" >          
                 <?php                                               
                 for($i=0; $i < sizeof($vtprd_rule_display_framework['specChoice_in']['option']); $i++) { 
                 ?>                             
                    <option id="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['id'] .'2'; ?>"  class="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['class'] .'2'; ?>"  value="<?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['value']; ?>"    ><?php echo $vtprd_rule_display_framework['specChoice_in']['option'][$i]['title2']; ?></option>
                 <?php } ?> 
               </select>  
                          
   <?php         
  } 


/*
    
    //v1.1.8.1 begin
     v1.1.8.1
    IF clone rule action, do clone, then send admin message in screen return.
    Save as draft ONLY, set status to pending.
    Then EXIT
     
    
    if ( isset( $_POST['cloneRule'] ) ) { 
      $this->vtprd_clone_post();
      return;
    }
    //v1.1.8.1 end
*/

    //******************************
    //v1.1.8.1  New Function
    //******************************
    public  function vtprd_ajax_clone_rule() {
      //global $post, $vtprd_rule, $vtprd_rules_set, $vtprd_setup_options; 
      global $post;
      
      //error_log( print_r(  'Function begin - vtprd_ajax_clone_rule', true ) ); 
      
      $clone_from_ruleID  = $_POST['ajaxRuleID'];
      if (!$clone_from_ruleID) {    
         echo '<div id="ajaxCloneRuleMsg">';
         echo __('Pricing Deal Rule *Clone Action* Failed. The clone-from rule ID not supplied.', 'vtprd');
         echo '</div>';
         exit; 
      }
            
      $post = get_post($clone_from_ruleID);

      //'clone XX of rule ID YYYY'  
      if ( ($post) &&
           ($post->post_title > ' ' ) &&
           ($post->post_status == 'publish') ) {
        $carry_on = true;
      } else {     
         echo '<div id="ajaxCloneRuleMsg">';
         echo __('Pricing Deal Rule *Clone Action* Failed. The clone-from rule must be Published, in order to use the Clone button.', 'vtprd');
         echo '</div>';
         exit; 
      }

      //get ruleset
      $vtprd_rules_set = get_option( 'vtprd_rules_set' );
      
      //NEED to STASH the current ruleset in the OPTION table
      //since when we store the new cloned rule, the ruleset gets clobbered by the normal Pricing Deals update catcher...
      if (get_option( 'vtprd_clone_rules_set' )) {
        update_option( 'vtprd_clone_rules_set',$vtprd_rules_set );
      } else {
        add_option( 'vtprd_clone_rules_set',$vtprd_rules_set );
      }

      //Find rule
      $rule_found = false;
      $sizeof_rules_set = sizeof($vtprd_rules_set);
      for($i=0; $i < $sizeof_rules_set; $i++) {       
         if ($vtprd_rules_set[$i]->post_id == $post->ID) {
            $hold_vtprd_rule = $vtprd_rules_set[$i];
            $rule_found = true;
            break;
         }
      }
      
      //clone-from rule NOT FOUND! 
      if (!$rule_found) {;
         echo '<div id="ajaxCloneRuleMsg">';
         echo __('Pricing Deal Rule *Clone Action* Failed. The clone-from rule no longer exists.', 'vtprd');
         echo '</div>';
         exit;     
      }
      
      //set an option to be read during the Pricing Deals update catcher
      //removed at end of this function (can't use session var, for some reason...)
      add_option('vtprd_clone_in_process_skip_upd_rule', 'yes');  

      //cloned rules are pending only   
      //add 'clone XX of rule ID YYYY'  to title 
      $cloneNum = maybe_unserialize(get_post_meta($post->ID, '_cloneNum'));      
      
      if ($cloneNum) {
        if (is_array($cloneNum)) {
          $cloneNum = $cloneNum[0]; //unfortunately, the number is often stored as an array!!!!!!
        }
        $cloneNum++;
        update_post_meta($post->ID, '_cloneNum', $cloneNum );         
      } else {
        $cloneNum = 1;
        add_post_meta($post->ID, '_cloneNum', $cloneNum, true );      
      }
      
      $my_post = array(
           'post_title' => $post->post_title . ' - Clone ' .$cloneNum. ' of Rule ID ' .$post->ID ,
           'post_date' => $_SESSION['cal_startdate'],
           'post_content' => 'cloned rule.',
           'post_status' => 'pending',
           'post_type' => 'vtprd-rule' 
        );

      $new_post_id = wp_insert_post(wp_slash($my_post));

      $vtprd_rule      = $hold_vtprd_rule;
      $vtprd_rule->post_id = $new_post_id;
      $vtprd_rule->rule_status = 'pending';
      $vtprd_rule->rule_updated_with_free_version_number =  VTPRD_VERSION; //v2.0.0
       
      //get previously saved option copy - from the  STASH of the current ruleset in the OPTION table
      //since when we store the new cloned rule, the ruleset gets clobbered by the normal Pricing Deals update catcher...
      $vtprd_rules_set = get_option('vtprd_clone_rules_set'); 

      $vtprd_rules_set[] = $vtprd_rule;
            
      update_option( 'vtprd_rules_set',$vtprd_rules_set );
      
      //clean up 
      delete_option('vtprd_clone_in_process_skip_upd_rule'); 
      delete_option('vtprd_clone_rules_set');
      
      echo '<div id="ajaxCloneRuleMsg">';
      echo __('Pricing Deal Rule Clone Completed.' , 'vtprd');
      echo '</div>';
      
      $post = get_post($clone_from_ruleID);
      
      //error_log( print_r(  '$vtprd_rules_set AT BOTTOM', true ) );
      //error_log( var_export($vtprd_rules_set, true ) );  
 
  	exit;
  }

    //********************************
    //* v2.0.0  NEW Function
    //********************************
    public function vtprd_stringify_var_name_array($varName_array) {
       global $vtprd_info; 
       
       if ($varName_array <= ' ') {                    
          return;
       }
 
 
       
       // large|red+extra large|blue (*full* variation name[s], separated by: | AND combined by: + )
       $varName_string = null;
       $varName_count = 0;
       foreach ($varName_array as $varName) {
        
        if ($varName_count > 0) {
          $varName_string .= '|';
        }
        $varName_combo_count = 0;
        foreach ($varName as $varName_combo) {
          if ($varName_combo_count > 0) {
            $varName_string .= '+';
          }
          $varName_string .= $varName_combo;
          $varName_combo_count++;
        }

        $varName_count++;
      }
      
      if ($varName_count == 0) {
        return;
      }
      
      return $varName_string;
  }

    //********************************
    //* v2.0.0  NEW Function
    //********************************
    public function vtprd_ajax_do_product_selector() {
      global $wpdb, $post, $vtprd_rule;
      //error_log( print_r(  'Function vtprd_ajax_do_product_selector BEGIN', true ) );   
      $data = array();
      $search = wc_clean( empty( $term ) ? stripslashes( $_GET['term'] ) : $term );     
      //error_log( print_r(  'SEARCH term 002 = ' .$search, true ) );            
      $search = '%'.$search.'%';     
      $post_status_publish = 'publish';
      
      //v2.0.0.5 begin
      // only works with PRO
      //similar code ALSO in vtprd-parent-definitions.php , so that the new post_type is also added to Pricing Deal Category
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
      $product_type_array = array('product', 'product_variation');    
      if (apply_filters('vtprd_use_additional_product_type',FALSE )) { 
        $additional_product_types = apply_filters('vtprd_use_additional_product_type',FALSE );
        /*
        foreach ($additional_product_types as $key => $additional_product_type) {
           $product_type_array[] = $additional_product_type;
        }
        */
        $product_type_array = array_merge($product_type_array,$additional_product_types);
      }     
    //v2.0.0.6 begin doesn't work with array!!!!  must be a comma separated list with quotes around each item
    $sql = "SELECT `ID`, `post_title`, `post_type`  FROM `" . $wpdb->prefix . "posts`  WHERE `post_title` LIKE '" . $search . "' AND `post_status` =  '" . $post_status_publish . "'    AND `post_type` IN ( 'product', 'product_variation' )  ORDER BY `post_title` ASC";                         	      
    //$sql = "SELECT `ID`, `post_title`, `post_type`  FROM `" . $wpdb->prefix . "posts`  WHERE `post_title` LIKE '" . $search . "' AND `post_status` =  '" . $post_status_publish . "'    AND `post_type` IN  '" . $product_type_array . "'   ORDER BY `post_title` ASC";
    //v2.0.0.6 end
      //v2.0.0.5 end
      
      $products_array = $wpdb->get_results($sql,ARRAY_A) ;              
    	if(sizeof($products_array) > 0){
        foreach ($products_array as $key => $product_row) {
           $product = wc_get_product($product_row['ID']);
           $product_name = wp_kses_post( $product->get_formatted_name() );
           if (vtprd_test_for_variations($product_row['ID'])) {
            $product_name .= '&nbsp; [all variations] ';
           }            
	         $prodID = $product_row['ID'];
           $data[$prodID] = $product_name;			 	
        } 
      } else {
           $data[1] = 'No Products Found';              
      }      
       //error_log( print_r(  '$data after PRODUCT search', true ) );
       //error_log( var_export($data, true ) );  
      wp_send_json( $data );
      
      die();
  }


    //********************************
    //* v2.0.0  NEW Function
    //  RETIRED in favor of inline loading with NO AJAX
    //********************************
    public function vtprd_ajax_do_category_selector() {
      global $wpdb, $post, $vtprd_rule, $vtprd_info;
      //copied from woocommerce class-wc-ajax.php
      //error_log( print_r(  'Function vtprd_ajax_do_category_selector BEGIN', true ) );

  
  		if ( ! $search = wc_clean( stripslashes( $_GET['term'] ) ) ) {
  			wp_die();
  		}

      //error_log( print_r(  '$search_text= ' .$search_text, true ) );
      
      //data-catID="prod_cat"
      $cat_id = ( $_GET['catid'] );
            
      //error_log( print_r(  '$cat_id= ' .$cat_id, true ) ); 
           
      if ($cat_id == 'prod_cat') {
        $taxonomy = 'product_cat';
        //error_log( print_r(  '$taxonomy001= ' .$taxonomy, true ) ); 
      } else {
        $taxonomy = 'vtprd_rule_category';;  
        //error_log( print_r(  '$taxonomy002= ' .$taxonomy, true ) );     
      }
      
  
  		$found_categories = array();
      
      $search = '%'.$search.'%';   //add these for 'LIKE'
     
      //manual access, as 'get_terms' changed over history, AND has a problem with the custom taxonomy
      $sql = "SELECT terms.`term_id`, terms.`name`  FROM `" . $wpdb->prefix . "terms` as terms, `" . $wpdb->prefix . "term_taxonomy` as term_taxonomy  WHERE  terms.`name`  LIKE '" . $search . "'  AND terms.`term_id` = term_taxonomy.`term_id` AND term_taxonomy.`taxonomy` = '" . $taxonomy . "' ORDER BY terms.`name` ASC";                         

      $terms = $wpdb->get_results($sql,ARRAY_A) ;
      
       //error_log( print_r(  '$terms after category search', true ) );
       //error_log( var_export($terms, true ) );       
      
      if (sizeof($terms) > 0){
  			foreach ( $terms as $term ) {
  				$found_categories[ $term['term_id'] ] = $term['name'];
  			}
  		} else {
          $found_categories[1] = 'No Categories Found';              
      }  
          
       //error_log( print_r(  '$found_categories', true ) );
       //error_log( var_export($found_categories, true ) );  
       
      wp_send_json( $found_categories);
      
      die();
   
  }



    //********************************
    //* v2.0.0  NEW Function
    //********************************
    public function vtprd_ajax_do_customer_selector() {
      global $wpdb, $post, $vtprd_rule, $vtprd_info;
      //copied from woocommerce class-wc-ajax.php
      //error_log( print_r(  'Function vtprd_ajax_do_customer_selector BEGIN', true ) );

  
  		if ( ! $search = wc_clean( stripslashes( $_GET['term'] ) ) ) {
  			wp_die();
  		}
      
      //error_log( print_r(  'Search term= ' .$search, true ) );
            
  		$found_customers = array();
      
      $search = '%'.$search.'%';   //add these for 'LIKE'
            
     
      //manual access, as 'get_terms' changed over history, AND has a problem with the custom taxonomy

      $sql = "SELECT *  FROM `" . $wpdb->prefix . "users` as users  WHERE  users.`user_email`  LIKE '" . $search . "'  OR users.`display_name`  LIKE '" . $search . "' ORDER BY users.`user_email` ASC";                         
 
      $terms = $wpdb->get_results($sql,ARRAY_A) ;
      
       //error_log( print_r(  '$terms after customer search', true ) );
       //error_log( var_export($terms, true ) );       
      
      if (sizeof($terms) > 0){
  			foreach ( $terms as $term ) {
  				$email_and_name = $term['user_email'] .' ('. $term['display_name'] .')';
          $found_customers[ $term['ID'] ] = $email_and_name;
  			}
  		} else {
          $found_customers[1] = 'No Customers Found';              
      }  
          
       //error_log( print_r(  '$found_customers', true ) );
       //error_log( var_export($found_customers, true ) );  
       
      wp_send_json( $found_customers);
      
      die();
  } 
      
      
} //end class
