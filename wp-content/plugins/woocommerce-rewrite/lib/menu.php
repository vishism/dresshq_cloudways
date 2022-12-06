<?php

class wcAdmin
{

	protected $options = array(); 

	public function __construct()
	{
		if(is_admin())
        {
            $this->register_admin();
            $this->options = get_option( 'ns_rewrite' );
        }
	}

	function custom_css() {
	       echo '<style type="text/css">
	                h2:before {
	                    content: \'\f324\';
	                    display: inline-block;
	                    -webkit-font-smoothing: antialiased;
	                    font: normal 29px/1 \'dashicons\';
	                    vertical-align: middle;
	                    margin-right: 0.3em;}
	                 </style>';
	    }

	    public function register_admin()
	    {
	        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
	        add_action( 'admin_init', array( $this, 'page_init' ) );
	        add_action( 'admin_head', array( $this, 'custom_css'));    
	    }

	    function admin_print_styles() {
	        wp_enqueue_style( 'admin-css', plugin_dir_url( __FILE__ ) . 'inc/css/admin.css' );
	    }

	/**
	     * Options page callback
	     */
	    public function create_admin_page()
	    {
	        // Set class property
	        ?>
	        <div class="wrap">
	            <h2 class="nav-tab-wrapper"><?php _e('Rewrite settings', 'woocommerce'); ?>
	              <a href="#plugin" class="nav-tab nav-tab-active">Plugin</a>
    			</h2>
				<div id="plugin">
				<form method="post" action="options.php">
	            <?php
	                settings_fields( 'WooCommerce_Rewrite' );   
	                do_settings_sections( 'ns-rewrite-admin' );
	                submit_button(); 
	            ?>
	            </form>
				</div>     
	        </div>
	        <?php
	    }

	    /**
	     * Add options page
	     */
	    public function add_plugin_page()
	    {
	        // This page will be under "Settings"
	        add_options_page(
	            'Rewrite settings (WooCommerce)', 
	            'Rewrite settings (WooCommerce)', 
	            'manage_options', 
	            'woocommerce-rewrite', 
	            array( $this, 'create_admin_page' )
	        );
	    }

	    public function sanitize( $input )
	    {
	        $new_input = array();
	        if( isset( $input['rewrite_enabled'] ) )
	            $new_input['rewrite_enabled'] = $input['rewrite_enabled'];

	        if( isset( $input['redirect_enabled'] ) )
	            $new_input['redirect_enabled'] = $input['redirect_enabled'];

	        return $new_input;
	    }


	    /**
	     * Register and add settings
	     */
	    public function page_init()
	    {
	        register_setting(
	            'WooCommerce_Rewrite', // Option group
	            'ns_rewrite', // Option name
	            array( $this, 'sanitize' ) // Sanitize
	        );

	        add_settings_section(
	            'setting_section_id', // ID
	            '', // Title
	            array( $this, 'print_section_info' ), // Callback
	            'ns-rewrite-admin' // Page
	        );  

	        add_settings_field(
	            'rewrite_enabled',
	            'Rewrite the URL\'s', 
	            array( $this, 'rewrite_enabled_callback' ),
	            'ns-rewrite-admin',
	            'setting_section_id'           
	        );      

	        add_settings_field(
	            'redirect_enabled', 
	            'Redirect ' . _x('product', 'slug', 'woocommerce') . ' & ' . _x('product-category', 'slug', 'woocommerce'), 
	            array( $this, 'redirect_enabled_callback' ), 
	            'ns-rewrite-admin', 
	            'setting_section_id'
	        );  
	    }

	    /** 
	     * Get the settings option array and print one of its values
	     */
	    public function rewrite_enabled_callback()
	    {
	        printf(
	            '<input type="checkbox" id="rewrite_enabled" name="ns_rewrite[rewrite_enabled]" %s />',
	            isset( $this->options['rewrite_enabled'] ) ? 'checked="checked"' : ''
	        );
	    }

	    /** 
	     * Get the settings option array and print one of its values
	     */
	    public function redirect_enabled_callback()
	    {
	        printf(
	            '<input type="checkbox" id="redirect_enabled" name="ns_rewrite[redirect_enabled]" %s />',
	            isset($this->options['redirect_enabled']) ? 'checked="checked"' : ''
	        );
	    }

	    /** 
	     * Print the Section text
	     */
	    public function print_section_info()
	    {
	        _e('WooCommerce uses /' . _x('product', 'slug', 'woocommerce') . '/ & /'. _x('product-category', 'slug', 'woocommerce') .'/ (or whatever language you use) in the URL.<br>- Enable Rewrite to remove /' . _x('product', 'slug', 'woocommerce') . '/ & /'. _x('product-category', 'slug', 'woocommerce') .'/ from the URL.<br>- Enable Redirect to redirect the old URL to the new URL. This is important for SEO. It is extremely important when your website already has been indexed by searchengines; The gathered SEO value will be redirected to the new URL so you will not loose your ranking.<p>Enable both options for the best result.</p>');
	    }
}