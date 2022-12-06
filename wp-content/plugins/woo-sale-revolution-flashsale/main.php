<?php 
/*
Plugin Name: Woo Sale Revolution:Flash Sale + Dynamic Discounts
Plugin URI: http://proword.net/woo_sale_revolution/
Description: Create advanced sale & flash sale with different scenario for your woocommerce shop
Author: Proword
Version: 2.1
Author URI: http://proword.net/
Text Domain: pw_wc_flash_sale
Domain Path: /languages/ 
 */

 
define('plugin_dir_url_flash_sale', plugin_dir_url( __FILE__ ));
define ('PW_flash_sale_URL',plugin_dir_path( __FILE__ ));

if ( ! defined( 'RC_TC_BASE_FILE' ) )
    define( 'RC_TC_BASE_FILE', __FILE__ );
if ( ! defined( 'RC_TC_BASE_DIR' ) )
    define( 'RC_TC_BASE_DIR', dirname( RC_TC_BASE_FILE ) );
if ( ! defined( 'RC_TC_PLUGIN_URL' ) )
    define( 'RC_TC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'pw_wc_flash_sale', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
add_filter( 'template_include', 'rc_tc_template_chooser');
function rc_tc_template_chooser( $template ) {
 
    // Post ID
    $post_id = get_the_ID();
 
    // For all other CPT
    if ( get_post_type( $post_id ) != 'flash_sale' ) {
        return $template;
    }
 
    // Else use custom template
    if ( is_single() ) {
        return rc_tc_get_template_hierarchy( 'single' );
    }
 
}

function rc_tc_get_template_hierarchy( $template ) {
 
    // Get the template slug
    $template_slug = rtrim( $template, '.php' );
    $template = $template_slug . '.php';
 
    // Check if a custom template exists in the theme folder, if not, load the plugin template file
    if ( $theme_file = locate_template( array( 'plugin_template/' . $template ) ) ) {
        $file = $theme_file;
    }
    else {
        $file = RC_TC_BASE_DIR . '/includes/templates/' . $template;
    }
 
    return apply_filters( 'rc_repl_template_' . $template, $file );
}
add_filter( 'template_include', 'rc_tc_template_chooser' );
 add_action( 'init', 'create_post_type_flashsale' );
function create_post_type_flashsale() {
  register_post_type( 'flash_sale',
    array(
      'labels' => array(
        'name' => __( 'flash_sale' ),
        'singular_name' => __( 'flash_sale' )
      ),
	'public' => true,
	'has_archive' => true,
	'show_in_menu'=>false, 
    )
  );
}

/* Filter the single_template with our custom function*/
//add_filter('single_template', 'my_custom_template');

function my_custom_template($single) {
    global $wp_query, $post;
	/* Checks for single template by post type */
	//echo dirname( __FILE__ );
	if ($post->post_type == "flash_sale"){
	//	if(file_exists(plugin_dir_url_flash_sale. 'a.php'))
			return dirname( __FILE__ ) . '/template/a.php';
	}
		return $single;
}

class woocommerce_flashsale {

	private $page_id;
	
	public function __construct() 
	{
		$this->includes();
		//add_action( 'widgets_init', array( $this, 'include_widgets' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );
		add_action('admin_head', array( $this, 'admin_js'));		
		add_action( 'init' , array( $this, 'kv_time_js' ) );
		add_action( 'wp_head', array( $this, 'dynamic_custom_css' ));		
		//Shortcode Ui
		add_filter('init', array( $this,'flash_sale_shortcodes_add_scripts'));
		add_action('admin_head', array( $this,'flash_sale_shortcodes_addbuttons'));		
		register_activation_hook( __FILE__ , array( $this,'woo_flashsale_install' ));
	}
	
	public function woo_flashsale_install()
	{
		update_option( 'pw_woocommerce_flashsale_countdown', 'style1' );	
		update_option( 'pw_woocommerce_flashsale_single_countdown', 'yes' );	
		update_option( 'pw_woocommerce_flashsale_archive_countdown', 'yes' );	
		update_option( 'pw_woocommerce_flashsale_color_countdown', '#6bb667' );	
		update_option( 'pw_woocommerce_flashsale_fontsize_countdown', 'medium' );	
		update_option( 'pw_matched_cart', 'only' );
		update_option( 'pw_matched_rule', 'all' );
	}
	//
	function flash_sale_shortcodes_add_scripts() {
		if(!is_admin()) {
			wp_enqueue_style('flash_sale_shortcodes', plugin_dir_url_flash_sale.'/includes/shortcodes.css');
			
			wp_enqueue_script('jquery');
			wp_register_script('flash_sale_shortcodes_js', plugin_dir_url_flash_sale.'/includes/shortcodes.js', 'jquery');
			wp_enqueue_script('flash_sale_shortcodes_js');
		} else {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}		
	}


	function flash_sale_shortcodes_addbuttons() {
		global $typenow;
		// check user permissions
		if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
		return;
		}
		// check if WYSIWYG is enabled
		if ( get_user_option('rich_editing') == 'true') {
			add_filter("mce_external_plugins", array( $this, "add_flash_sale_shortcodes_tinymce_plugin"));
			add_filter('mce_buttons', array( $this, 'register_flash_sale_shortcodes_button'));
		}
	}
	
	function add_flash_sale_shortcodes_tinymce_plugin($plugin_array) {
		$plugin_array['flash_sale_shortcodes_button'] = plugins_url( '/includes/tinymce_button.js', __FILE__ );
		return $plugin_array;
	}
	function register_flash_sale_shortcodes_button($buttons) {
	   array_push($buttons, "flash_sale_shortcodes_button");
	   return $buttons;
	}	
	
	public function includes()
	{
		//require( 'core/function.php' );
		require( 'core/discount_cart.php' );		
		require( 'template/front/product.php' );
		require( 'core/shortcode.php' );
		require( 'core/admin/setting-tab.php' );
	}
	
	public function include_widgets() {
		include_once( 'core/widget.php' );
	}	
	
	public function admin_js() {
		if(is_admin())
		{
			wp_register_style('kv_js_time_style' , plugin_dir_url_flash_sale. 'css/jquery.datetimepicker.css');
			wp_enqueue_style('kv_js_time_style');
			
			wp_enqueue_script('jquery-time-picker' ,  plugin_dir_url_flash_sale. 'js/jquery.datetimepicker.js',  array('jquery' ));
			wp_enqueue_style('flipclock-master-cssss', plugin_dir_url_flash_sale.'css/jquery.countdown.css');		
			wp_enqueue_script('flipclocksdsd-master-jsaaaa',  plugin_dir_url_flash_sale.'js/jquery.countdown.min.js',array( 'jquery' ));	
			////////////////ADMIN STYLE///////////////////
		    wp_enqueue_style('pw-fs-main-style',plugin_dir_url_flash_sale.'/css/admin-css.css', array() , null);
		  
		    /////////////////////////CSS CHOSEN///////////////////////
		    wp_enqueue_style('pw-fs-chosen-style',plugin_dir_url_flash_sale.'/css/chosen/chosen.css', array() , null);
		    wp_enqueue_script( 'pw-fs-chosen-script', plugin_dir_url_flash_sale.'/js/chosen/chosen.jquery.min.js', array( 'jquery' ));			
			
			wp_enqueue_script('jquery');
		    wp_enqueue_script( 'pw-dependsOn', plugin_dir_url_flash_sale.'/js/dependsOn-1.0.1.min.js', array( 'jquery' ));				
		}
	}
	public function kv_time_js() {

		wp_enqueue_style('public-style', plugin_dir_url_flash_sale.'css/public-style.css');
		wp_enqueue_style('fontawesome-style', plugin_dir_url_flash_sale.'css/fonts/font-awesome.css');
		
		wp_enqueue_style('flipclock-master-cssss', plugin_dir_url_flash_sale.'css/jquery.countdown.css');		
		
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
		//wp_register_style( 'flipclock-master-css', plugin_dir_url_flash_sale.'css/flipclock.css' );		
		
		wp_enqueue_style('flipclock-master-css', plugin_dir_url_flash_sale.'css/flipclock.css');
		wp_enqueue_style( 'flipclock-master-css' );
		
		//slider
		wp_enqueue_style('fl-slider-style', plugin_dir_url_flash_sale.'css/bx-slider/jquery.bxslider.css');
		
		//grid
		wp_enqueue_style('fl-grid-style', plugin_dir_url_flash_sale.'css/grid/grid.css');
		
		wp_enqueue_script('jquery');			
		wp_enqueue_script('flipclock-master-jsaa',  plugin_dir_url_flash_sale.'js/flipclock.js',array( 'jquery' ));	
		
		wp_enqueue_script('flipclocksdsd-master-jsaaaa',  plugin_dir_url_flash_sale.'js/jquery.countdown.min.js',array( 'jquery' ));
		//wp_register_script('flipclock-master-js', plugin_dir_url_flash_sale.'js/flipclock.js',array( 'jquery' ));		
		wp_enqueue_script('flipclock-master-js');		
		
		//slider
		wp_enqueue_script('fl-slider-jquery',  plugin_dir_url_flash_sale.'js/bx-slider/jquery.bxslider.js',array( 'jquery' ));	
	}	
	public function add_menu_link() {

		$this->page_id = add_submenu_page(
			'woocommerce',
			__( 'Woo Sale Rev', 'pw_wc_flash_sale' ),
			__( 'Woo Sale Rev', 'pw_wc_flash_sale' ),
			'manage_woocommerce',
			'rule_list',
			array( $this, 'show_sub_menu_page' )
		);

		// add the Manage Points/Points log list table Screen Options
	//add_action( 'load-' . $this->page_id, array( $this, 'add_list_table_options' ) );
	}


	public function add_list_table_options() {

		if ( isset( $_GET['tab'] ) && 'log' === $_GET['tab'] ) {
			$args = array(
				'label' => __( 'Points Log', 'pw_wc_flash_sale' ),
				'default' => 20,
				'option' => 'wc_points_rewards_points_log_per_page',
			);
		} else {
			$args = array(
				'label' => __( 'Manage Points', 'pw_wc_flash_sale' ),
				'default' => 20,
				'option' => 'wc_points_rewards_manage_points_customers_per_page',
			);
		}

		add_screen_option( 'per_page', $args );
	}
	
	public function show_sub_menu_page() {

		$current_tab = ( empty( $_GET['page'] ) ) ? 'rule_list' : urldecode( $_GET['page'] );
		if( 'rule_list' === $current_tab)
			$this->show_level_tab();
	}	

	private function show_level_tab() {
		$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'Pricing Rules';
		$tabs=array('Pricing Rules','cart Discounts');
		echo '<h2>';
		foreach ($tabs as $name) {
			echo '<a href="' . admin_url('admin.php?page=rule_list&tab=' . $name) . '" class="nav-tab ';
			if ($current_tab == $name)
				echo 'nav-tab-active';
			echo '">' .$name . '</a>';
		}
		echo '</h2>';
		if(@$_GET['tab']=="cart Discounts")
		{
		
		}
		else
		{
			if(@$_GET['pw_action_type']=="list_product")
			{
				$pw_name=(get_post_meta($_GET['pw_id'],'pw_name',true)==""? "..." :get_post_meta($_GET['pw_id'],'pw_name',true));
				echo '<h2>' . __( 'Rule', 'wc_advanced_points' ) .' '.$pw_name;
				echo '<a href="'. wp_nonce_url( remove_query_arg( 'points_balance', add_query_arg( array( 'pw_action_type' => 'edit') ) ), 'wc_points_rewards_update' )
			.'">Edit Rule</a></h2>';
				include_once (PW_flash_sale_URL.'/core/admin/list_product.php') ;		
			}
			echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
		}
		require( 'core/admin/admin-core.php' );
	}		
	public function dynamic_custom_css() {
		echo '<style>
        	.fl-countdown-pub.fl-countdown.fl-style2 li ,.fl-countdown-pub.fl-countdown.fl-style3 li span { background:'.get_option( 'pw_woocommerce_flashsale_color_countdown').'
		</style>';
	}
}
new woocommerce_flashsale();


add_action('wp_ajax_pw_fetch_rule', 'pw_fetch_rule');
add_action('wp_ajax_nopriv_pw_fetch_rule', 'pw_fetch_rule');
function pw_fetch_rule() {
/*	echo '<option>dsd</option>';*/
	$query_meta_query=array('relation' => 'AND');
	$query_meta_query[] = array(
		'key' =>'status',
		'value' => "active",
		'compare' => '=',
	);
	$args=array(
		'post_type'=>'flash_sale',
		'posts_per_page'=>-1,
		'order'=>'data',
		'orderby'=>'modified',
		'meta_query' => $query_meta_query,		
	);
	$loop = new WP_Query( $args );		

		while ( $loop->have_posts() ) : 
			$loop->the_post();
			$pw_type=get_post_meta(get_the_ID(),'pw_type',true);
			if($pw_type=="flashsale")
			{
				echo '<option value="'.get_the_ID().'">
						'.get_post_meta(get_the_ID(),'pw_name',true).'
					</option>';
			}
		endwhile;	

	exit(0);
}

add_action('wp_ajax_pw_fetch_product', 'pw_fetch_product');
add_action('wp_ajax_nopriv_pw_fetch_product', 'pw_fetch_product');
function pw_fetch_product() {

	$args=array(
		'post_type'=>'product',
		'posts_per_page'=>-1,
		'order'=>'data',
		'orderby'=>'DESC',
	);
	$loop = new WP_Query( $args );		

		while ( $loop->have_posts() ) : 
			$loop->the_post();
			echo '<option value='.get_the_ID().'>
					'.get_the_title().'
				</option>';
		endwhile;	

	exit(0);
}

add_action('wp_ajax_pw_save_cart_matched', 'pw_save_cart_matched');
add_action('wp_ajax_nopriv_pw_save_cart_matched', 'pw_save_cart_matched');
function pw_save_cart_matched() {

	update_option( 'pw_matched_cart', @$_POST['pw_matched_cart'] );	
}

add_action('wp_ajax_pw_save_rule_matched', 'pw_save_rule_matched');
add_action('wp_ajax_nopriv_pw_save_rule_matched', 'pw_save_rule_matched');
function pw_save_rule_matched() {

	update_option( 'pw_matched_rule', @$_POST['pw_matched_rule'] );	
}

function calculate_modifiera( $percentage, $price )
{
	$percentage = $percentage / 100;
	return $percentage * $price;
}
function calculate_discount_modifiera( $percentage, $price ) {
	$percentage = str_replace( '%', '', $percentage ) / 100;
	return $percentage * $price;
}	

function fl_product_rule_custom_style($rand_id , $text_colour , $countdown_backcolour , $countdown_area_backcolour , $description_area_backcolour) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.countdown-'.$rand_id.'{
			background-color: '.$countdown_area_backcolour.'
		}
		.countdown-'.$rand_id.' ul.fl-countdown li span , .countdown-'.$rand_id.' ul.fl-countdown li p ,.countdown-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.countdown-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.countdown-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		 }
		.car-'.$rand_id.' .fs-itemdesc{
			background:'.$description_area_backcolour.';
		}
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}
function fl_rule_list_custom_style($rand_id , $text_colour , $countdown_backcolour  , $overlay_backcolour) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.rulelist-'.$rand_id.' .fl-rulcnt-overlay{
			background: '.$overlay_backcolour.'
		}
		.rulelist-'.$rand_id.' ul.fl-countdown li span , .rulelist-'.$rand_id.' ul.fl-countdown li p ,.rulelist-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.rulelist-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.rulelist-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		}
		 
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}

function fl_rule_slider_custom_style($rand_id , $text_colour , $countdown_backcolour  , $overlay_backcolour) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.ruleslider-'.$rand_id.' .fl-rulcnt-overlay{
			background: '.$overlay_backcolour.'
		}
		.ruleslider-'.$rand_id.' ul.fl-countdown li span , .ruleslider-'.$rand_id.' ul.fl-countdown li p ,.ruleslider-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.ruleslider-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.ruleslider-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		}
		 
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}

function fl_top_product_grid_custom_style($rand_id , $text_colour , $countdown_backcolour , $description_area_backcolour ) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.countdown-'.$rand_id.' ul.fl-countdown li span , .countdown-'.$rand_id.' ul.fl-countdown li p ,.countdown-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.countdown-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.countdown-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		 }
		.col-'.$rand_id.' .fs-itemdesc{
			background:'.$description_area_backcolour.';
		}
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}
function fl_top_product_carousel_custom_style($rand_id , $text_colour , $countdown_backcolour , $description_area_backcolour ) {
			
	wp_enqueue_style('pw-pl-custom-style', plugin_dir_url_flash_sale . '/css/custom.css', array() , null); 
	$custom_css = '
		.countdown-'.$rand_id.' ul.fl-countdown li span , .countdown-'.$rand_id.' ul.fl-countdown li p ,.countdown-'.$rand_id.' ul.fl-countdown li.seperator{ 
			color:'.$text_colour.';
		}
		.countdown-'.$rand_id.' ul.fl-countdown.fl-style2 li ,.countdown-'.$rand_id.' ul.fl-countdown.fl-style3 li span { 
			background: '.$countdown_backcolour.'
		 }
		.car-'.$rand_id.' .fs-itemdesc{
			background:'.$description_area_backcolour.';
		}
		';
	wp_add_inline_style( 'pw-pl-custom-style', $custom_css );
}
function pw_list_capabilities(){
	$capabilities = array();
	$default_caps = pw_get_capabilities_def();
	$role_caps = pw_get_role_capabilities();
	
	//$capabilities = array_merge( $default_caps, $role_caps, $plugin_caps );	
	$capabilities = array_merge( $default_caps, $role_caps);	
	sort( $capabilities );
	return array_unique( $capabilities );	
}
function pw_get_role_capabilities() {
	global $wp_roles;

	/* Set up an empty capabilities array. */
	$capabilities = array();

	/* Loop through each role object because we need to get the caps. */
	foreach ( $wp_roles->role_objects as $key => $role ) {

		/* Roles without capabilities will cause an error, so we need to check if $role->capabilities is an array. */
		if ( is_array( $role->capabilities ) ) {

			/* Loop through the role's capabilities and add them to the $capabilities array. */
			foreach ( $role->capabilities as $cap => $grant )
				$capabilities[$cap] = $cap;
		}
	}

	/* Return the capabilities array, making sure there are no duplicates. */
	return array_unique( $capabilities );
}
function pw_get_capabilities_def() {
	$ret = array(
		'activate_plugins','add_users','create_users','delete_others_pages','delete_others_posts','delete_pages','delete_plugins','delete_posts','delete_private_pages','delete_private_posts','delete_published_pages','delete_published_posts','delete_users','edit_dashboard','edit_files','edit_others_pages','edit_others_posts','edit_pages','edit_plugins','edit_posts','edit_private_pages','edit_private_posts','edit_published_pages','edit_published_posts','edit_theme_options','edit_themes','edit_users','import','install_plugins','install_themes','list_users','manage_categories','manage_links','manage_options','moderate_comments','promote_users','publish_pages','publish_posts','read',
		'read_private_pages',
		'read_private_posts',
		'remove_users',
		'switch_themes',
		'unfiltered_html',
		'unfiltered_upload',
		'update_core',
		'update_plugins',
		'update_themes',
		'upload_files'
	);
	return $ret;					
}	
?>
