<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once 'classes/remarkety_rest_controller.php';
include_once 'classes/remarkety_cart_recover.php';

/**
 * Plugin Name: Remarkety - Email Marketing for WooCommerce
 * Plugin URI: http://www.remarkety.com
 * Description: Email Marketing automation tool for WooCommerce.
 * Version: 1.4.1
 * Author: Remarkety
 * Author URI: http://www.remarkety.com
 * License: GPL2
 * WC requires at least: 2.0.0
 * WC tested up to: 3.6.2
 */

/*  Copyright 2016  Remarkety Inc  (email : sales@remarkety.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('remarkety_for_woocommerce')) :
    /**
     * Main Remarkety for WooCommerce API Class
     *
     * @class remarkety_for_woocommerce
     * @version	1.4.1
     */
    class remarkety_for_woocommerce
    {
        const OPTION_API_KEY = 'remarkety_api_key';
        const OPTION_DEBUG_MODE = 'remarkety_api_debug';
        const OPTION_WEBTRACKING_ID = 'remarkety_webtracking_id';
        const LOG_NAME = 'remarkety-for-woocommerce.log';
        const DB_VERSION = '2.0.2';

        // email popup const variable declaration
        const OPTION_CUSTOMER_EMAIL_MODE = 'remarkety_api_customer_email';
        const OPTION_EMAIL_POPUP_TEXT = 'remarkety_api_email_popup_text';
        const OPTION_EMAIL_OPT_IN_TEXT = 'remarkety_api_email_opt_in_text';
        const OPTION_EMAIL_DISMISS_TEXT = 'remarkety_api_email_dismiss_text';
        const OPTION_EMAIL_CONTINUE_TEXT = 'remarkety_api_email_continue_text';
        const OPTION_EMAIL_CUSTOM_CSS = 'remarkety_api_email_custom_css';

        // marketing allowed checkbox
        const OPTION_ALLOW_MARKETING_CHECKBOX = 'remarkety_allow_marketing_checkbox';
        const OPTION_ALLOW_MARKETING_LABEL = 'remarkety_allow_marketing_label';
        const OPTION_ALLOW_MARKETING_DEFAULT = 'remarkety_allow_marketing_default';

        static public $logPath;
        static public $debug_mode;
        static public $debugData = null;

        static public $customer_email_mode;
        static public $marketing_allowed_checkbox;

        public function __construct()
        {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'add_menu'));
            add_action('admin_enqueue_scripts', array($this, 'load_wp_admin_styles'));
            add_action('woocommerce_cart_updated', array($this, 'wc_cart_update_event'));
            add_action('woocommerce_cart_emptied', array($this, 'wc_cart_empty_event'));
            add_filter('xmlrpc_methods', array($this, 'xml_add_methods'));
            remarkety_for_woocommerce::$debug_mode = (get_option(self::OPTION_DEBUG_MODE) == 'on');

            remarkety_for_woocommerce::$logPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::LOG_NAME;
            set_error_handler(array("remarkety_for_woocommerce", "remarketyErrorHandler"));
            if (remarkety_for_woocommerce::$debug_mode == 1) {
                error_reporting(E_ALL | E_STRICT);
                ini_set("display_errors", 1);
            }

            remarkety_for_woocommerce::$debugData = null;

            remarkety_for_woocommerce::$customer_email_mode = (get_option(self::OPTION_CUSTOMER_EMAIL_MODE) == 'on');
            remarkety_for_woocommerce::$marketing_allowed_checkbox = (get_option(self::OPTION_ALLOW_MARKETING_CHECKBOX) == 'on');

            //webtracking code embed
            add_action('wp_footer', array($this, 'webtracking_footer'));
            add_action('woocommerce_after_single_product', array($this, 'webtracking_product'));

            //guest checkout code
            add_action('wp_footer', array($this, 'footer_guest_checkout_script'));
            add_action('wp_ajax_nopriv_rm_guest_checkout', array($this, 'guest_checkout_ajax'));

            add_action('plugins_loaded', array($this, 'update_db_check'));

            //checkout and cart page
            add_action('woocommerce_after_cart', array($this, 'wc_send_cart_content'));
            add_action('woocommerce_after_checkout_form', array($this, 'wc_send_cart_content'));
            add_action('wc_ajax_get_cart_content', array($this, 'wc_get_cart_content'));

            //email popup feature
            add_action('wp_enqueue_scripts', array($this, 'remarkety_js_css'), 99);
            add_action('wp_head', array($this, 'add_email_popup_style'));
            add_action('wp_footer', array($this, 'add_email_popup'));

            //marketing allowed checkbox
            add_action('woocommerce_after_checkout_billing_form', array($this, 'add_marketing_allowed_checkbox'));
            add_action('woocommerce_checkout_update_order_meta', array($this, 'save_marketing_allowed_checkbox'));
        }

        function webtracking_footer()
        {
            $id = get_option(self::OPTION_WEBTRACKING_ID);
            if (empty($id)) { //webtracking is disabled
                return;
            }

            $current_user = wp_get_current_user();

            echo '<script>
                    var _rmData = _rmData || [];
                    _rmData.push(["setStoreKey", "' . $id . '"]);'
                . ((!empty($current_user->user_email)) ? '_rmData.push(["setCustomer", "' . $current_user->user_email . '"]);' : '') .
                '</script>
                  <script>(function(d, t) {
                      var g = d.createElement(t),
                          s = d.getElementsByTagName(t)[0];
                      g.src = "https://d3ryumxhbd2uw7.cloudfront.net/webtracking/track.js";
                      s.parentNode.insertBefore(g, s);
                    }(document, "script"));</script>';
        }

        public static function update_db_check()
        {
            if (get_site_option('rm_db_version') != self::DB_VERSION) {
                self::activate(false);
            }
        }

        public function footer_guest_checkout_script()
        {
            if (!is_user_logged_in()) {
                echo '<script type = "text/javascript">
                    var lastRmAjaxRequest = null;
                  jQuery(document).ready(function(){
                     jQuery("#billing_email, #billing_country, #shipping_country, #billing_first_name, #billing_postcode, #shipping_postcode").on("change",function() {
                        var rm_checkout_email = this . value;
                        var atpos=rm_checkout_email.indexOf("@");
                        var dotpos=rm_checkout_email.lastIndexOf(".");
                        if (atpos>=1 && dotpos >= (atpos+2) && (dotpos+2) < rm_checkout_email.length){
                            var rm_checkout_firstname = jQuery("#billing_first_name").val();
                            var rm_checkout_lastname = jQuery("#billing_last_name").val();
                            var rm_checkout_phone = jQuery("#billing_phone").val();
                            
                            var data = {
                                action: "rm_guest_checkout",
                                email: rm_checkout_email,
                                first_name: rm_checkout_firstname,
                                last_name: rm_checkout_lastname,
                                phone: rm_checkout_phone
                            };
                            var str = JSON.stringify(data);
                            if(str !== lastRmAjaxRequest){
                                lastRmAjaxRequest = str;
                                jQuery.post("' . admin_url("admin-ajax.php") . '", data);
                            }
                        }
                    });
                });
                </script>';
            }
        }

        public function guest_checkout_ajax()
        {
            global $wpdb;

            if (!is_user_logged_in()) {
                $session_cart = null;
                if (isset(WC()->session) && isset(WC()->session->cart)) {
                    $session_cart = WC()->session->cart;
                } else {
                    echo json_encode(['success' => false, 'reason' => 'no cart']);
                    remarkety_for_woocommerce::log("wc_cart_update_event() No cart in session.");
                    return;
                }
                $ts = time();
                $cart = null;

                $email = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : null;
                $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : null;
                $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : null;

                if (empty($email)) {
                    echo json_encode(array('success' => false, 'reason' => 'No email'));
                    die();
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(array('success' => false, 'reason' => 'Invalid email'));
                    die();
                }

                if (!empty($session_cart)) {
                    $cartObj = WC()->cart;
                    if ($cartObj) {
                        try {
                            $shipping_cost = $cartObj->shipping_total;
                            $shipping_tax = $cartObj->shipping_tax_total;
                        } catch (\Exception $ex) {
                            $shipping_cost = 0;
                            $shipping_tax = 0;
                        }
                    } else {
                        $shipping_cost = 0;
                        $shipping_tax = 0;
                    }

                    $cart = array(
                        'cart' => $session_cart,
                        'coupons' => WC()->session->applied_coupons,
                        'coupon_discounts' => WC()->session->coupon_discount_amounts,
                        'customer' => array(
                            'user_email' => $email,
                            'first_name' => $first_name,
                            'last_name' => $last_name
                        ),
                        'shipping' => array(
                            'total_without_tax' => $shipping_cost,
                            'tax' => $shipping_tax
                        )
                    );

                    $cart = serialize($cart);
                }

                try {
                    $cartId = isset($_COOKIE['rm_cart_id']) ? $_COOKIE['rm_cart_id'] : false;
                    if ($cartId) {
                        $q = "SELECT count(email) as cnt FROM {$wpdb->prefix}remarkety_carts_guests WHERE email = %s or id = %d";
                        $res = $wpdb->get_row($wpdb->prepare($q, $email, $cartId));
                    } else {
                        $q = "SELECT count(email) as cnt FROM {$wpdb->prefix}remarkety_carts_guests WHERE email = %s";
                        $res = $wpdb->get_row($wpdb->prepare($q, $email));
                    }
                    $count = 0;
                    if ($res && isset($res->cnt)) {
                        $count = (int)$res->cnt;
                    }
                    $update = $count >= 1;

                    if ($update) {
                        if (empty($session_cart)) {
                            //delete record
                            if ($cartId) {
                                $q = "DELETE FROM {$wpdb->prefix}remarkety_carts_guests WHERE email = %s or id = %d";
                                $wpdb->query($wpdb->prepare($q, $email, $cartId));
                            } else {
                                $q = "DELETE FROM {$wpdb->prefix}remarkety_carts_guests WHERE email = %s";
                                $wpdb->query($wpdb->prepare($q, $email));
                            }
                        } else {
                            if (empty($cartId)) {
                                $q = "UPDATE {$wpdb->prefix}remarkety_carts_guests SET email = %s, updated_on = %d, cart_data = %s WHERE (email = %s)";
                                $res = $wpdb->query($wpdb->prepare($q, $email, $ts, $cart, $email));
                            } else {
                                $q = "UPDATE {$wpdb->prefix}remarkety_carts_guests SET email = %s, updated_on = %d, cart_data = %s WHERE (email = %s or id = %d)";
                                $res = $wpdb->query($wpdb->prepare($q, $email, $ts, $cart, $email, $cartId));
                            }
                        }
                    } else {
                        if (!empty($session_cart)) {
                            $q = "INSERT INTO {$wpdb->prefix}remarkety_carts_guests (email, created_on, updated_on, cart_data) VALUES (%s, %d, %d, %s)";
                            $res = $wpdb->query($wpdb->prepare($q, $email, $ts, $ts, $cart));
                            if ($res) {
                                $id = $wpdb->insert_id;
                                setcookie("rm_cart_id", $id, time() + 3600, "/");
                            }
                        }
                    }

                    remarkety_for_woocommerce::log("wc_cart_update_event executed query : " . $q);
                } catch (Exception $e) {
                    remarkety_for_woocommerce::log($e);
                }
            }
            exit;
        }

        function webtracking_product()
        {
            global $post;
            $id = get_option(self::OPTION_WEBTRACKING_ID);
            if (empty($id)) { //webtracking is disabled
                return;
            }
            $terms = get_the_terms($post->ID, 'product_cat');
            $categoryIds = [];
            $categoryNames = [];
            if ($terms) {
                foreach ($terms as $term) {
                    $categoryIds[] = $term->term_id;
                    $categoryNames[] = $term->name;
                }
            }
            echo "<script>
                  var _rmData = _rmData || [];
                  _rmData.push(['productView', {
                    productId: " . json_encode($post->ID) . ",
                    productCategories: " . json_encode($categoryNames) . ",
                    productCategoriesIds: " . json_encode($categoryIds) . "
                  }]);
                </script>";
        }

        function xml_add_methods($methods)
        {
            $new_methods = array(
                'remarkety_wc_api.store_settings' => 'api_method_get_store_settings',
                'remarkety_wc_api.statuses' => 'api_method_get_statuses',
                'remarkety_wc_api.products' => 'api_method_get_products',
                'remarkety_wc_api.products_count' => 'api_method_get_products_count',
                'remarkety_wc_api.shoppers' => 'api_method_get_shoppers',
                'remarkety_wc_api.shoppers_count' => 'api_method_get_shoppers_count',
                'remarkety_wc_api.orders' => 'api_method_get_orders',
                'remarkety_wc_api.orders_count' => 'api_method_get_orders_count',
                'remarkety_wc_api.create_coupon' => 'api_method_create_coupon',
                'remarkety_wc_api.carts' => 'api_method_get_carts',
                'remarkety_wc_api.debug' => 'api_method_debug',
                'remarkety_wc_api.create_webhook' => 'api_method_create_webhook',
                'remarkety_wc_api.webhooks_list' => 'api_method_webhooks_list',
                'remarkety_wc_api.remove_webhook' => 'api_method_remove_webhook',
            );

            foreach ($new_methods as $k => $v) $methods[$k] = array($this, $v);
            return $methods;
        }

        public static function activate($newInstallation = true)
        {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            global $wpdb;
            $q = "
                CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}remarkety_carts` (
                    `user_id` bigint(20) NOT NULL,
                    `created_on` int(11) NOT NULL,
                    `updated_on` int(11) NOT NULL,
                    `cart_data` longtext CHARACTER SET utf8 NOT NULL,
                    PRIMARY KEY (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
            ";

            dbDelta($q);

            //upgrade the table to a new format
            $installed_ver = get_option("rm_db_version");
            if (!$installed_ver || version_compare($installed_ver, '2.0.2', '<')) {

                //table not in database. Create new table
                $charset_collate = $wpdb->get_charset_collate();

                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}remarkety_carts_guests");
                $sql = "CREATE TABLE `{$wpdb->prefix}remarkety_carts_guests` (
                                    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    `email` varchar(100) NOT NULL,
                                    `user_id` bigint(20) NULL,
                                    `created_on` int(11) NOT NULL,
                                    `updated_on` int(11) NOT NULL,
                                    `cart_data` longtext CHARACTER SET utf8 NOT NULL,
                                    PRIMARY KEY (`id`),
                                    UNIQUE KEY `email` (`email`)
                                ) $charset_collate;";
                $wpdb->query($sql);
                $q = "INSERT INTO {$wpdb->prefix}remarkety_carts_guests (email, user_id, created_on, updated_on, cart_data)
                            SELECT u.user_email as email, c.*
                            FROM `wp_remarkety_carts` as c
                            INNER JOIN wp_users as u ON (c.user_id = u.id);";
                dbDelta($q);

                update_option("rm_db_version", '2.0.2');
            }

            $apiKey = get_option(self::OPTION_API_KEY);
            if (empty($apiKey)) {
                add_option(self::OPTION_API_KEY, substr(str_replace('.', '', uniqid(uniqid('', true), true)), 0, 32));
                $newInstallation = true;
            }
            if ($newInstallation) {
                // make sure to start clean without old information remaining from after previous deactivation
                $wpdb->query("DELETE FROM {$wpdb->prefix}remarkety_carts");
                $wpdb->query("DELETE FROM {$wpdb->prefix}remarkety_carts_guests");
            }
        }

        public static function uninstall()
        {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}remarkety_carts");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}remarkety_carts_guests");
            delete_option("rm_db_version");
        }

        public function admin_init()
        {
            register_setting('remarkety_for_woocommerce', self::OPTION_DEBUG_MODE, array($this, 'debug_mode_changed'));
            register_setting('remarkety_for_woocommerce', self::OPTION_WEBTRACKING_ID, array($this, 'webtracking_changed'));

            register_setting('remarkety_for_woocommerce', self::OPTION_ALLOW_MARKETING_CHECKBOX);
            register_setting('remarkety_for_woocommerce', self::OPTION_ALLOW_MARKETING_LABEL);
            register_setting('remarkety_for_woocommerce', self::OPTION_ALLOW_MARKETING_DEFAULT);

            // email popup functions call  
            register_setting('remarkety_for_woocommerce', self::OPTION_CUSTOMER_EMAIL_MODE);
            register_setting('remarkety_for_woocommerce', self::OPTION_EMAIL_POPUP_TEXT);
            register_setting('remarkety_for_woocommerce', self::OPTION_EMAIL_DISMISS_TEXT);
            register_setting('remarkety_for_woocommerce', self::OPTION_EMAIL_CONTINUE_TEXT);
            register_setting('remarkety_for_woocommerce', self::OPTION_EMAIL_OPT_IN_TEXT);
            register_setting('remarkety_for_woocommerce', self::OPTION_EMAIL_CUSTOM_CSS);

            add_settings_section('remarkety_main', '', array($this, 'settings_section'), __FILE__);
            add_settings_field(self::OPTION_DEBUG_MODE, 'Enable debug mode', array($this, 'setting_debug_mode'), __FILE__, 'remarkety_main');
            add_settings_field(self::OPTION_WEBTRACKING_ID, 'Website tracking id', array($this, 'setting_website_tracking'), __FILE__, 'remarkety_main');

            // add setting fields for allow marketing popup
            add_settings_section('remarkety_checkbox', '', function () {
                echo '<h3>Marketing Allowed Checkbox</h3>';
                echo '<p>Enabling this feature will add a checkbox on your WooCommerce checkout page, right under the email field.<br>
                    The user’s email and SMS preferences will be sent to Remarkety based on the value of this checkbox.<br>
                    If this option is disabled, all new users are assumed to allow email marketing.</p>';
            }, __FILE__);
            add_settings_field(self::OPTION_ALLOW_MARKETING_CHECKBOX, 'Enable “Marketing Allowed” checkbox', array($this, 'setting_allow_marketing_checkbox'), __FILE__, 'remarkety_checkbox');
            add_settings_field(self::OPTION_ALLOW_MARKETING_LABEL, 'Checkbox label', array($this, 'setting_allow_marketing_label'), __FILE__, 'remarkety_checkbox');
            add_settings_field(self::OPTION_ALLOW_MARKETING_DEFAULT, 'Default state of checkbox (Checked / Unchecked)', array($this, 'setting_allow_marketing_default'), __FILE__, 'remarkety_checkbox');

            // add setting fields for email popup 
            add_settings_section('remarkety_popup', '', function () {
                echo '<h3>Email Capture Booster</h3>';
            }, __FILE__);
            add_settings_field(self::OPTION_CUSTOMER_EMAIL_MODE, 'Enable email capture booster', array($this, 'setting_customer_email_popup'), __FILE__, 'remarkety_popup');
            add_settings_field(self::OPTION_EMAIL_POPUP_TEXT, 'Popup title', array($this, 'setting_email_popup_text'), __FILE__, 'remarkety_popup');
            add_settings_field(self::OPTION_EMAIL_OPT_IN_TEXT, 'Opt-in disclaimer', array($this, 'setting_email_popup_opt_in_text'), __FILE__, 'remarkety_popup');
            add_settings_field(self::OPTION_EMAIL_DISMISS_TEXT, 'Dismiss button text', array($this, 'setting_email_popup_dismiss_text'), __FILE__, 'remarkety_popup');
            add_settings_field(self::OPTION_EMAIL_CONTINUE_TEXT, 'Continue button text', array($this, 'setting_email_popup_continue_text'), __FILE__, 'remarkety_popup');
            add_settings_field(self::OPTION_EMAIL_CUSTOM_CSS, 'Custom CSS', array($this, 'setting_email_popup_custom_css'), __FILE__, 'remarkety_popup');
        }

        public function load_wp_admin_styles()
        {
            wp_register_style('remarkety-for-woocommerce', plugins_url('remarkety-for-woocommerce/assets/css/remarkety.css'));
            wp_enqueue_style('remarkety-for-woocommerce');
        }

        public function add_menu()
        {
            add_options_page('Remarkety WC API Settings', 'Remarkety WC API', 'manage_options', 'remarkety_for_woocommerce', array($this, 'plugin_settings_page'));
        }

        public function plugin_settings_page()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('you do not have sufficient permissions to access this page.'));
            }

            echo '<div class="wrap">';
            echo '<form method="post" action="options.php">';
            settings_fields('remarkety_for_woocommerce');
            do_settings_sections(__FILE__);
            @submit_button();
            echo '</form>';
            echo '</div>';
        }

        function settings_section()
        {
            include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
        }

        function setting_debug_mode()
        {
            $d = self::OPTION_DEBUG_MODE;
            $checked = checked(get_option($d), 'on', false);
            echo "<input type='checkbox' name='{$d}' id='{$d}' {$checked} /> <span style=\"font-size:13px\">Log file path: /wp-content/plugins/remarkety-for-woocommerce/remarkety-for-woocommerce.log</span>";
        }

        function setting_website_tracking()
        {
            $d = self::OPTION_WEBTRACKING_ID;
            $id = get_option($d);
            echo "<input type='text' name='{$d}' id='{$d}' value='{$id}' /> <a href='https://support.remarkety.com/hc/en-us/articles/211066146' target='_blank'>What's this?</a>";
        }

        function setting_allow_marketing_checkbox()
        {
            $d = self::OPTION_ALLOW_MARKETING_CHECKBOX;
            $checked = checked(get_option($d), 'on', false);
            echo "<input type='checkbox' name='{$d}' id='{$d}' {$checked} /> <span style=\"font-size:13px\"> </span>";
            echo '<script type = "text/javascript">
                    jQuery(document).ready(function(){
                        jQuery("#' . $d . '").change(function(){
                            if(!this.checked) {
                                jQuery(".' . $d . '").prop("disabled", "disabled");
                            } else {
                                jQuery(".' . $d . '").prop("disabled", false);
                            }
                        });
                    });
                </script>';
        }

        function setting_allow_marketing_label()
        {
            $d = self::OPTION_ALLOW_MARKETING_LABEL;
            $p = self::OPTION_ALLOW_MARKETING_CHECKBOX;
            $id_checkbox_text = get_option($d);
            if ($id_checkbox_text  == '') {
                $id_checkbox_text  = 'I agree to receive promotional materials via email and/or SMS.';
            }
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<input type='text' name='{$d}' id='{$d}' class='{$p}' value='{$id_checkbox_text}' {$disabled}/>";
        }

        function setting_allow_marketing_default()
        {
            $d = self::OPTION_ALLOW_MARKETING_DEFAULT;
            $p = self::OPTION_ALLOW_MARKETING_CHECKBOX;
            $checked = checked(get_option($d), 'on', false);
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<input type='checkbox' name='{$d}' id='{$d}' class='{$p}' {$checked} {$disabled}/> <span style=\"font-size:13px\"> </span>";
        }

        function setting_customer_email_popup()
        {
            $d = self::OPTION_CUSTOMER_EMAIL_MODE;
            $checked = checked(get_option($d), 'on', false);
            echo "<input type='checkbox' name='{$d}' id='{$d}' {$checked} /> <span style=\"font-size:13px\"> </span>";
            echo '<script type = "text/javascript">
                    jQuery(document).ready(function(){
                        jQuery("#' . $d . '").change(function(){
                            if(!this.checked) {
                                jQuery(".' . $d . '").prop("disabled", "disabled");
                            } else {
                                jQuery(".' . $d . '").prop("disabled", false);
                            }
                        });
                    });
                </script>';
        }

        function setting_email_popup_text()
        {
            $d = self::OPTION_EMAIL_POPUP_TEXT;
            $p = self::OPTION_CUSTOMER_EMAIL_MODE;
            $id_email_text = get_option($d);
            if ($id_email_text  == '') {
                $id_email_text  = 'Reserve this item in your cart!';
            }
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<input type='text' name='{$d}' id='{$d}' class='{$p}' value='{$id_email_text}' {$disabled}/>";
        }

        function setting_email_popup_dismiss_text()
        {
            $d = self::OPTION_EMAIL_DISMISS_TEXT;
            $p = self::OPTION_CUSTOMER_EMAIL_MODE;
            $id_dismiss_text = get_option($d);
            if ($id_dismiss_text  == '') {
                $id_dismiss_text  = 'Dismiss';
            }
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<input type='text' name='{$d}' id='{$d}' class='{$p}' value='{$id_dismiss_text}' {$disabled}/>";
        }

        function setting_email_popup_continue_text()
        {
            $d = self::OPTION_EMAIL_CONTINUE_TEXT;
            $p = self::OPTION_CUSTOMER_EMAIL_MODE;
            $id_continue_text = get_option($d);
            if ($id_continue_text  == '') {
                $id_continue_text  = 'Continue';
            }
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<input type='text' name='{$d}' id='{$d}' class='{$p}' value='{$id_continue_text}' {$disabled}/>";
        }

        function setting_email_popup_opt_in_text()
        {
            $d = self::OPTION_EMAIL_OPT_IN_TEXT;
            $p = self::OPTION_CUSTOMER_EMAIL_MODE;
            $id_opt_in_text = get_option($d, 'By clicking Continue, you agree to receive newsletters and promotions to the email address above.');
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<textarea name='{$d}' id='{$d}' style=\"min-height:70px\" class='{$p}' {$disabled}>{$id_opt_in_text}</textarea>";
            echo '<div style="font-size:85%">This text is displayed in the footer of the popup to ensure subscribers know they are opting-in to email marketing</div>';
        }

        function setting_email_popup_custom_css()
        {
            $d = self::OPTION_EMAIL_CUSTOM_CSS;
            $p = self::OPTION_CUSTOMER_EMAIL_MODE;
            $id_continue_text = get_option($d);
            if ($id_continue_text  == '') {
                $id_continue_text  = '#rm-email-popup {' . PHP_EOL . '}' . PHP_EOL;
                $id_continue_text .= '#rm-email-popup-title {' . PHP_EOL . '}' . PHP_EOL;
                $id_continue_text .= '#rm-email-popup-dismiss {' . PHP_EOL . '}' . PHP_EOL;
                $id_continue_text .= '#rm-email-popup-continue {' . PHP_EOL . '}' . PHP_EOL;
            }
            $disabled = (get_option($p) !== 'on') ? 'disabled' : '';
            echo "<textarea name='{$d}' id='{$d}' style=\"min-height:100px\" class='{$p}' {$disabled}>{$id_continue_text}</textarea>";
        }

        function debug_mode_changed($input)
        {
            remarkety_for_woocommerce::$debug_mode = true;

            if ($input == 'on') {
                remarkety_for_woocommerce::log('Debug mode turned on');
            } else {
                remarkety_for_woocommerce::log('Debug mode turned off');
            }

            return $input;
        }

        function webtracking_changed($input)
        {
            if (!empty($input)) {
                remarkety_for_woocommerce::log('Webtracking turned on');
            } else {
                remarkety_for_woocommerce::log('Webtracking turned off');
            }

            return $input;
        }

        public function wc_cart_empty_event()
        {
            remarkety_for_woocommerce::log("Start wc_cart_empty_event()");
            global $wpdb;
            if (!is_user_logged_in()) return;
            $user_id = get_current_user_id();
            $cartId = isset($_COOKIE['rm_cart_id']) ? $_COOKIE['rm_cart_id'] : false;
            if ($cartId) {
                $q = "DELETE FROM {$wpdb->prefix}remarkety_carts_guests WHERE id = %d OR user_id = %d";
                $wpdb->query($wpdb->prepare($q, $cartId, $user_id));
            } else {
                $q = "DELETE FROM {$wpdb->prefix}remarkety_carts_guests WHERE user_id = %d";
                $wpdb->query($wpdb->prepare($q, $user_id));
            }
            remarkety_for_woocommerce::log("wc_cart_empty_event executed query : " . $q);
        }

        public function wc_cart_update_event()
        {
            remarkety_for_woocommerce::log("Start wc_cart_update_event()");
            global $wpdb;

            $cartId = isset($_COOKIE['rm_cart_id']) ? $_COOKIE['rm_cart_id'] : false;
            $loggedIn = is_user_logged_in();
            if (!$loggedIn && !$cartId) {
                remarkety_for_woocommerce::log("wc_cart_update_event() User is not login.");
                return;
            }
            $session_cart = null;
            if (isset(WC()->session) && isset(WC()->session->cart)) {
                $session_cart = WC()->session->cart;
            } else {
                remarkety_for_woocommerce::log("wc_cart_update_event() No cart in session.");
                return;
            }

            $email = null;
            if ($loggedIn) {
                $user_id = get_current_user_id();
                $current_user = wp_get_current_user();
                if (!empty($current_user->user_email)) {
                    $email = $current_user->user_email;
                }
            } else {
                $user_id = null;
            }

            $ts = time();
            $cart = null;

            if (!empty($session_cart)) {
                $cartObj = WC()->cart;
                if ($cartObj) {
                    try {
                        $shipping_cost = $cartObj->shipping_total;
                        $shipping_tax = $cartObj->shipping_tax_total;
                    } catch (\Exception $ex) {
                        $shipping_cost = 0;
                        $shipping_tax = 0;
                    }
                } else {
                    $shipping_cost = 0;
                    $shipping_tax = 0;
                }
                $newCartData = array(
                    'cart' => $session_cart,
                    'coupons' => WC()->session->applied_coupons,
                    'coupon_discounts' => WC()->session->coupon_discount_amounts,
                    'shipping' => array(
                        'total_without_tax' => $shipping_cost,
                        'tax' => $shipping_tax
                    )
                );
                $cart = serialize($newCartData);
            }

            try {
                $res = null;
                $count = 0;
                $q = null;

                if ($cartId) {
                    $q = "SELECT count(email) as cnt FROM {$wpdb->prefix}remarkety_carts_guests WHERE id = %d";

                    if (!empty($email)) {
                        $q .= " or email = %s";
                        $res = $wpdb->get_row($wpdb->prepare($q, $cartId, $email));
                    } else {
                        $res = $wpdb->get_row($wpdb->prepare($q, $cartId));
                    }
                } else {
                    if (!empty($email)) {
                        $q = "SELECT count(email) as cnt FROM {$wpdb->prefix}remarkety_carts_guests WHERE email = %s";
                        $res = $wpdb->get_row($wpdb->prepare($q, $email));
                    }
                }

                if ($res && isset($res->cnt)) {
                    $count = (int)$res->cnt;
                }
                $update = $count >= 1;
                if ($update) {
                    if (empty($session_cart)) {
                        //delete record
                        if ($cartId) {
                            $q = "DELETE FROM {$wpdb->prefix}remarkety_carts_guests WHERE email = %s or id = %d";
                            $wpdb->query($wpdb->prepare($q, $email, $cartId));
                        } else {
                            //logged-in user only
                            $q = "DELETE FROM {$wpdb->prefix}remarkety_carts_guests WHERE user_id = %s OR email = %s";
                            $wpdb->query($wpdb->prepare($q, $user_id, $email));
                        }
                    } else {
                        //update record
                        if (empty($cartId)) {
                            $q = "UPDATE {$wpdb->prefix}remarkety_carts_guests SET updated_on = %d, cart_data = %s WHERE user_id = %s OR email = %s";
                            $wpdb->query($wpdb->prepare($q, $ts, $cart, $user_id, $email));
                        } else {
                            //get cart customer data if exists, for guest checkouts
                            $selectQ = "SELECT * FROM {$wpdb->prefix}remarkety_carts_guests WHERE id = %d";
                            $oldCartDataRes = $wpdb->get_row($wpdb->prepare($selectQ, $cartId));
                            if ($oldCartDataRes && isset($oldCartDataRes->cart_data)) {
                                $oldCartData = unserialize($oldCartDataRes->cart_data);
                                $customer = isset($oldCartData['customer']) ? $oldCartData['customer'] : null;
                                if (!empty($customer)) {
                                    $newCartData['customer'] = $customer;
                                    $cart = serialize($newCartData);
                                }
                            }

                            if (empty($email)) {
                                $email = $oldCartDataRes->email;
                            }

                            $q = "UPDATE {$wpdb->prefix}remarkety_carts_guests SET email = %s, updated_on = %d, cart_data = %s WHERE (email = %s or id = %d)";
                            $wpdb->query($wpdb->prepare($q, $email, $ts, $cart, $email, $cartId));
                        }
                    }
                } else if ($loggedIn) {
                    //insert only when not anonymous
                    if (!empty($session_cart)) {
                        if (!empty($email)) {
                            $q = "INSERT INTO {$wpdb->prefix}remarkety_carts_guests (email, user_id, created_on, updated_on, cart_data) VALUES (%s, %d, %d, %d, %s)";
                            $wpdb->query($wpdb->prepare($q, $email, $user_id, $ts, $ts, $cart));
                        } else {
                            $q = "INSERT INTO {$wpdb->prefix}remarkety_carts_guests (user_id, created_on, updated_on, cart_data) VALUES (%d, %d, %d, %s)";
                            $wpdb->query($wpdb->prepare($q, $user_id, $ts, $ts, $cart));
                        }
                    }
                }

                remarkety_for_woocommerce::log("wc_cart_update_event executed query : " . $q);
            } catch (Exception $e) {
                remarkety_for_woocommerce::log($e);
            }
            remarkety_for_woocommerce::log("End wc_cart_update_event");
        }

        public function api_method_get_store_settings($args)
        {
            remarkety_for_woocommerce::log("Start api_method_get_store_settings()");
            remarkety_for_woocommerce::log($args);
            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $options = array(
                'blogname',
                'woocommerce_email_from_name',
                'woocommerce_email_from_address',
                'woocommerce_currency',
                'woocommerce_currency_pos',
                'woocommerce_price_thousand_sep',
                'woocommerce_price_decimal_sep',
                'woocommerce_price_num_decimals',
                'woocommerce_version',
                'woocommerce_default_country',
                'gmt_offset',
                'timezone_string',
                'home',
                'siteurl'
            );

            $res = array('settings' => array());
            foreach ($options as $option) $res['settings'][$option] = get_option($option);
            $res['settings']['is_multisite'] = is_multisite();
            global $wp_version;
            $res['settings']['wp_version'] = $wp_version;

            remarkety_for_woocommerce::log("End api_method_get_store_settings()");
            return $res;
        }

        public function api_method_get_shoppers($args)
        {
            global $wpdb;
            $ids = array();
            remarkety_for_woocommerce::log('Start api_method_get_shoppers');
            remarkety_for_woocommerce::log($args);

            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            // 			$updated_min = '';
            // 			$updated_max = '';
            $limit = '';
            $starting_id = '';

            //			if (isset($args[2]) && $args[2] > 0) $updated_min = " AND user_registered > '" . $args[2] . "'";
            //			if (isset($args[3]) && $args[3] > 0) $updated_max = " AND user_registered < '" . $args[3] . "'";

            if (isset($args[3]) && $args[3] > 0) {
                $rows = $args[3];
                $offset = (isset($args[4])) ? $rows * $args[4] : 0;
                $limit = $wpdb->prepare(" LIMIT %d, %d", $offset, $rows);
            }

            if (isset($args[5]) && $args[5] > 0) $starting_id = $wpdb->prepare(" AND user.ID >= %d", $args[5]);

            $q = "
                SELECT user.ID
                FROM {$wpdb->prefix}users as user
                WHERE true
                {$starting_id}
                {$limit}
            ";
            // 				{$updated_min}
            // 				{$updated_max}

            $res = array('shoppers' => array());
            try {
                $results = $wpdb->get_results($q);

                if ($results) {
                    foreach ($results as $result) {
                        remarkety_for_woocommerce::log('Result ID = ' . $result->ID);
                        $ids[] = $result->ID;
                    }

                    // 			$users_per_page = get_option('posts_per_page');
                    $q = array(
                        'fields'  => 'all_with_meta',
                        //						'role'    => 'customer',
                        'orderby' => 'registered',
                        'include' => join(',', $ids),
                        // 					'number'  => $users_per_page,
                    );

                    $query = new WP_User_Query($q);

                    remarkety_for_woocommerce::log('Query = ' . print_r($query, true));

                    /* @var $user WP_User */
                    if (!empty($query->results)) {
                        foreach ($query->results as $user) {
                            $res['shoppers'][] = $this->user_data_array($user);
                        }
                    }
                }
            } catch (Exception $e) {
                remarkety_for_woocommerce::log('Error in api_method_get_shoppers when calling database. Query: ' . $q);
            }

            $this->addDebugIfNeeded($res);
            return $res;
        }

        public function api_method_get_shoppers_count($args)
        {
            remarkety_for_woocommerce::log('Start api_method_get_shoppers_count');
            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $q = array(
                'fields'  => 'ID',
                //                'role'    => 'customer',  /* Commented out to be consistent with get_shoppers */
                'orderby' => 'registered',
                'posts_per_page' => -1
            );

            $query = new WP_User_Query($q);
            $res = array('count' => $query->get_total());
            remarkety_for_woocommerce::log("End api_method_get_shoppers_count.");
            return $res;
        }

        public function api_method_get_products($args)
        {
            global $wpdb;
            remarkety_for_woocommerce::log('Start api_method_get_products');
            remarkety_for_woocommerce::log($args);
            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $updated_min = '';
            $updated_max = '';
            $limit = '';
            $post_id_min = '';
            $post_date_min = '';
            $post_date_max = '';
            $post_status = '';
            $post_id = '';

            if (isset($args[1]) && $args[1] > 0) $updated_min = $wpdb->prepare(" AND post_modified_gmt > %s", $args[1]);
            if (isset($args[2]) && $args[2] > 0) $updated_max = $wpdb->prepare(" AND post_modified_gmt < %s", $args[2]);
            if (isset($args[3]) && $args[3] > 0) {
                $rows = $args[3];
                $offset = (isset($args[4])) ? $rows * $args[4] : 0;
                $limit = $wpdb->prepare("LIMIT %d, %d", $offset, $rows);
            }
            if (isset($args[5]) && $args[5] > 0) $post_id_min = $wpdb->prepare(" AND ID >= %d", $args[5]);
            if (isset($args[6]) && $args[6] > 0) $post_status = $wpdb->prepare(" AND post_status = %s", $args[6]);
            if (isset($args[7]) && $args[7] > 0) $post_id = $wpdb->prepare(" AND ID = %d", $args[7]);

            $q = "
                SELECT ID
                FROM {$wpdb->prefix}posts
                WHERE post_type = 'product'
                {$updated_min}
                {$updated_max}
                {$post_id_min}
                {$post_date_min}
                {$post_date_max}
                {$post_status}
                {$post_id}
                {$limit}
            ";


            //			remarkety_for_woocommerce::log($q);

            $res = array('products' => array());

            try {
                $results = $wpdb->get_results($q);

                if ($results) {
                    foreach ($results as $result) {
                        try {
                            if (isset($result->ID)) {
                                $res['products'][] = $this->product_data_array($result->ID, true);
                            } else {
                                remarkety_for_woocommerce::log('Error in api_method_get_products no product ID. ');
                                continue;
                            }
                        } catch (Exception $e) {
                            remarkety_for_woocommerce::log('Error in api_method_get_products when calling this->product_data_array(). Query: ' . $q);
                            continue;
                        }
                    }
                }
                $this->addDebugIfNeeded($res);
            } catch (Exception $e) {
                remarkety_for_woocommerce::log('Error in api_method_get_products when calling database. Query: ' . $q);
            }

            remarkety_for_woocommerce::log('End api_method_get_products');
            return $res;
        }

        public function api_method_get_products_count($args)
        {

            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $query = new WP_Query(array('post_type' => array('product', 'product_variation'), 'fields' => 'ids', 'post_status' => 'any', 'posts_per_page' => -1));
            $res = array('count' => count($query->posts));
            return $res;
        }

        public function api_method_create_coupon($args)
        {
            remarkety_for_woocommerce::log('Start api_method_create_coupon');
            remarkety_for_woocommerce::log($args);

            if (count($args) < 12) return self::status_auth_error();
            if (!$this->auth($args)) return self::status_params_error();

            $coupon_code =                 (string)    $args[1];
            $discount_type =             (string)    $args[2];
            $permanent =                (bool)        $args[3];        // TODO : currently : true = unlimited use ..
            $amount =                     (float)        $args[4];
            $start_date =                 (string)    $args[5];
            $expiry_date =                 (string)    $args[6];
            $minimum_spent =             (float)        $args[7];
            $free_shipping =             (bool)        $args[8];
            $apply_before_tax =            (bool)        $args[9];
            $usage_limit_per_coupon =     (int)        $args[10];
            $usage_limit_per_user =     (int)        $args[11];
            $product_ids =                 (string)    $args[12];
            $exclude_product_ids =         (string)    $args[13];
            $individual_use =             (bool)        $args[14];
            $exclude_sale_items =       (bool)        $args[15];
            $category_ids =             (string)    (isset($args[16]) ? $args[16] : '');
            $exclude_category_ids =     (string)    (isset($args[17]) ? $args[17] : '');
            $customer_email =           (string)    (isset($args[18]) ? $args[18] : '');
            $coupon_code = strtolower($coupon_code);
            if ($this->coupon_code_exists($coupon_code)) return self::status_fail();

            if ($permanent) {
                $usage_limit_per_coupon = 0;
                $usage_limit_per_user = 0;
            }

            if ($usage_limit_per_coupon == 0) $usage_limit_per_coupon = '';
            if ($usage_limit_per_user == 0) $usage_limit_per_user = '';

            $free_shipping = $free_shipping ? 'yes' : 'no';
            $apply_before_tax = $apply_before_tax ? 'yes' : 'no';
            $individual_use = $individual_use ? 'yes' : 'no';
            $exclude_sale_items = $exclude_sale_items ? 'yes' : 'no';
            $coupon = array(
                'post_title' => $coupon_code,
                'post_content' => '',
                'post_author' => 1,
                'post_type' => 'shop_coupon',
                'post_excerpt' => 'Remarkety, ' . current_time('mysql'),
                'post_status' => 'publish',
                'post_name' => $coupon_code
            );

            $post_date = new DateTime($start_date, new DateTimeZone('UTC'));
            $now = new DateTime(null, new DateTimeZone('UTC'));

            if ($post_date > $now) {
                $tz = get_option('timezone_string', 'UTC');
                try {
                    $dateTimeZone = new DateTimeZone($tz);
                } catch (Exception $ex) {
                    $dateTimeZone = new DateTimeZone('UTC');
                }

                $coupon['post_status'] = 'future';
                $coupon['post_date_gmt'] = $post_date->format('Y-m-d H:i:s');
                $post_date->setTimezone($dateTimeZone);
                $coupon['post_date'] = $post_date->format('Y-m-d H:i:s');
            }

            $new_coupon_id = wp_insert_post($coupon);

            update_post_meta($new_coupon_id, 'discount_type', $discount_type);
            update_post_meta($new_coupon_id, 'coupon_amount', $amount);
            update_post_meta($new_coupon_id, 'usage_limit', $usage_limit_per_coupon);
            update_post_meta($new_coupon_id, 'usage_limit_per_user', $usage_limit_per_user);
            update_post_meta($new_coupon_id, 'expiry_date', $expiry_date);
            if (!empty($expiry_date)) {
                $expiry_time = strtotime($expiry_date);
                if ($expiry_time) {
                    update_post_meta($new_coupon_id, 'date_expires', $expiry_time);
                }
            }
            update_post_meta($new_coupon_id, 'apply_before_tax', $apply_before_tax);
            update_post_meta($new_coupon_id, 'free_shipping', $free_shipping);
            update_post_meta($new_coupon_id, 'minimum_amount', $minimum_spent);
            update_post_meta($new_coupon_id, 'individual_use', $individual_use);
            update_post_meta($new_coupon_id, 'exclude_sale_items', $exclude_sale_items);

            if (!empty($product_ids)) {
                $verifiedProductIds = $this->verifyIdsExist($product_ids, 'product');
                update_post_meta($new_coupon_id, 'product_ids', implode(",", $verifiedProductIds));
            }
            if (!empty($exclude_product_ids)) {
                $verifiedProductIds = $this->verifyIdsExist($exclude_product_ids, 'product');
                update_post_meta($new_coupon_id, 'exclude_product_ids', implode(",", $verifiedProductIds));
            }
            if (!empty($category_ids)) {
                $product_categories = array_map('intval', array_map('trim', explode(",", $category_ids)));
                update_post_meta($new_coupon_id, 'product_categories', $product_categories);
            }
            if (!empty($exclude_category_ids)) {
                $exclude_category_ids = array_map('intval', array_map('trim', explode(",", $exclude_category_ids)));
                update_post_meta($new_coupon_id, 'exclude_product_categories', $exclude_category_ids);
            }
            if (!empty($customer_email)) {
                $customer_email_arr = array_map('trim', explode(",", $customer_email));
                update_post_meta($new_coupon_id, 'customer_email', $customer_email_arr);
            }

            remarkety_for_woocommerce::log('End api_method_create_coupon');

            return self::status_success();
        }

        public function api_method_get_orders($args)
        {
            remarkety_for_woocommerce::log('Start api_method_get_orders');
            remarkety_for_woocommerce::log($args);
            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $datetime = new DateTime("1970-01-01", new DateTimeZone('UTC'));

            if (isset($args[1])) {
                $dateTimeStr = $args[1];
                if (!empty($dateTimeStr))
                    $datetime = new DateTime($dateTimeStr, new DateTimeZone('UTC'));
            }
            $updated_at_min = $datetime->format('Y-m-d H:i:s');

            $page = (isset($args[4]) ? $args[4] : 0) + 1;   /* getOrders is the only method which users WP API instead of an SQL, and the page is 1-based instead of 0-based */
            remarkety_for_woocommerce::log('updated_at_min arg[1] = ' . $updated_at_min);
            $q = array(
                'post_type' => 'shop_order',
                'post_status' => array_keys(wc_get_order_statuses()),
                'date_query' => array(
                    'column' => 'post_modified_gmt',
                    'after'  => $updated_at_min
                ),
                'posts_per_page' => $args[3],
                'paged' => $page,
                'order' => 'ASC',
                'orderby' => 'post_modified'

            );

            $query = new WP_Query($q);

            remarkety_for_woocommerce::log('Query = ' . print_r($query, true));

            $res = array('orders' => array());
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $order_id = $query->post->ID;
                    remarkety_for_woocommerce::log('Order ID: ' . $order_id);
                    $res['orders'][] = $this->get_order($order_id); //new WC_Order($query->post->ID);
                }
            }
            $this->addDebugIfNeeded($res);
            remarkety_for_woocommerce::log('End api_method_get_orders');
            return $res;
        }

        public function api_method_get_orders_count($args)
        {
            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $query = new WP_Query(array(
                'post_type' => 'shop_order',
                'post_status' => array_keys(wc_get_order_statuses()),
                'posts_per_page' => -1
            ));
            $res = array('count' => $query->post_count);
            return $res;
        }

        public function api_method_get_statuses($args)
        {
            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $res = array('statuses' => array());

            if (version_compare(WOOCOMMERCE_VERSION, '2.2.8', '<')) {
                $taxonomies = array('shop_order_status');
                $q = array(
                    'hide_empty'    => false,
                    'fields'        => 'all',
                    'hierarchical'  => false,
                );

                foreach (get_terms($taxonomies, $q) as $s) {
                    $res['statuses'][] = array(
                        'status'    => $s->name,
                        'status_id'    => $s->slug,
                    );
                }
            } else {
                foreach (wc_get_order_statuses() as $slug => $name) {
                    $order_statuses[str_replace('wc-', '', $slug)] = $name;
                }

                foreach (wc_get_order_statuses() as $key => $value) {
                    $res['statuses'][] = array(
                        'status'    => $value,
                        'status_id'    => str_replace('wc-', '', $key),
                    );
                }
            }

            $this->addDebugIfNeeded($res);
            return $res;
        }

        public function api_method_get_carts($args)
        {
            global $wpdb;
            remarkety_for_woocommerce::log('Start api_method_get_carts');
            remarkety_for_woocommerce::log($args);

            if (count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            $updated_min = '';
            $updated_max = '';
            $limit = '';

            if (isset($args[1]) && $args[1] > 0) $updated_min = $wpdb->prepare(" AND updated_on >= %s", $args[1]);
            if (isset($args[2]) && $args[2] > 0) $updated_max = $wpdb->prepare(" AND updated_on <= %s", $args[2]);
            if (isset($args[3]) && $args[3] > 0) {
                $rows = $args[3];
                $offset = (isset($args[4])) ? $rows * $args[4] : 0;
                $limit = $wpdb->prepare("LIMIT %d, %d", $offset, $rows);
            }

            $q = "SELECT * FROM {$wpdb->prefix}remarkety_carts_guests WHERE true {$updated_min} {$updated_max} ORDER BY updated_on {$limit}";
            remarkety_for_woocommerce::log($q);

            $results = $wpdb->get_results($q);

            // TODO : need to verify that these are the correct options.
            // TODO : see how plugins that allow multi-currency work. load their correct currency if possible per order.
            $currency = get_option('woocommerce_currency');
            $currency_symbol = get_woocommerce_currency_symbol($currency);

            $res = array('carts' => array());

            if ($results) {
                remarkety_for_woocommerce::log('Carts count:' . count($results));
                foreach ($results as $result) {
                    //					remarkety_for_woocommerce::log($result);
                    //					remarkety_for_woocommerce::log($result->cart_data);
                    //					remarkety_for_woocommerce::log(unserialize($result->cart_data));
                    $userdata = null;
                    $cartData = @unserialize($result->cart_data);
                    if (!empty($result->user_id)) {
                        $userdata = $this->user_data_array_by_user_id($result->user_id);
                    } else if (isset($cartData['customer'])) {
                        $userdata = $cartData['customer'];
                    }
                    $cart = array(
                        'created_on'            => $result->created_on,
                        'updated_on'            => $result->updated_on,
                        'user_id'                => $result->user_id,
                        'shopper_block'            => $userdata,
                        'cart_data'                => $cartData,
                        'currency'                => $currency,
                        'currency_symbol'         => $currency_symbol,
                    );
                    $cart = apply_filters('remarkety_cart_data', $cart);
                    $res['carts'][] = $cart;
                }
            }

            $this->addDebugIfNeeded($res);
            remarkety_for_woocommerce::log('End api_method_get_carts');
            return $res;
        }

        private function get_order($id)
        {
            remarkety_for_woocommerce::log('Start get_order');
            remarkety_for_woocommerce::log('Order: ' . $id);

            $order = new WC_Order($id);
            $order_post = get_post($id);
            $currency = '';
            if (method_exists($order, 'get_currency'))
                $currency = $order->get_currency();
            elseif (method_exists($order, 'get_order_currency'))
                $currency = $order->get_order_currency();

            $currency = (empty($currency)) ? get_option('woocommerce_currency') : $currency;

            remarkety_for_woocommerce::log('Currency = ' . $currency);

            remarkety_for_woocommerce::log($order);

            $order_data = array(
                'id'                        => $order->id,
                'order_number'              => $order->get_order_number(),
                'created_at'                => $order_post->post_date_gmt,
                'updated_at'                => $order_post->post_modified_gmt,
                'completed_at'              => $order->completed_date,
                'status_block'              => array(
                    'status_id'             => $order->get_status(),
                    'status'                => wc_get_order_status_name($order->get_status()),
                ),
                'currency'                  => $currency,
                'currency_symbol'             => get_woocommerce_currency_symbol($currency),
                'total'                     => $order->get_total(),
                // 					'subtotal'                  => 0, // $this->format_decimal( $this->get_order_subtotal( $order ), 2 ),
                'total_tax'                 => $order->get_total_tax(),
                'total_shipping'            => $order->get_total_shipping(),
                'cart_tax'                  => $order->get_cart_tax(),
                'shipping_tax'              => $order->get_shipping_tax(),
                'order_discount'            => $order->get_total_discount(),
                'customer_id'               => $order->customer_user,
                'payment_method'            => isset($order->payment_method_title) ? $order->payment_method_title : null,

                'billing_address' => array(
                    'first_name' => $order->billing_first_name,
                    'last_name'  => $order->billing_last_name,
                    'company'    => $order->billing_company,
                    'address_1'  => $order->billing_address_1,
                    'address_2'  => $order->billing_address_2,
                    'city'       => $order->billing_city,
                    'state'      => $order->billing_state,
                    'postcode'   => $order->billing_postcode,
                    'country'    => $order->billing_country,
                    'email'      => $order->billing_email,
                    'phone'      => $order->billing_phone,
                ),
                'shipping_address' => array(
                    'first_name' => $order->shipping_first_name,
                    'last_name'  => $order->shipping_last_name,
                    'company'    => $order->shipping_company,
                    'address_1'  => $order->shipping_address_1,
                    'address_2'  => $order->shipping_address_2,
                    'city'       => $order->shipping_city,
                    'state'      => $order->shipping_state,
                    'postcode'   => $order->shipping_postcode,
                    'country'    => $order->shipping_country,
                ),
                'items'                        => array(),
                'coupon_lines'                 => array(),
                'shopper_block'                => $this->user_data_array_by_user_id($order->customer_user),
            );

            remarkety_for_woocommerce::log('Order data = ' . print_r($order_data, true));

            // add line items
            foreach ($order->get_items() as $item_id => $item) {
                $product = $order->get_product_from_item($item);
                remarkety_for_woocommerce::log('Product = ' . print_r($product, true));

                if (empty($product)) {
                    continue;
                }

                $product_id = (isset($product->variation_id)) ? $product->variation_id : $product->id;

                $order_data['items'][] = array(
                    'product_id'         => $product_id,
                    'name'               => $item['name'],
                    'quantity'           => (int) $item['qty'],
                    'sku'                => is_object($product) ? $product->get_sku() : null,
                    'price'              => $order->get_item_total($item),
                    'subtotal'           => $order->get_line_subtotal($item),
                    'total'              => $order->get_line_total($item),
                    'total_tax'          => $order->get_line_tax($item),
                    'product_block'      => $this->product_data_array($product_id, false)

                );
            }

            // TODO : if the coupon original details are needed they can be fetched from the post and it's metadata
            // the details currently retrieved are :
            // 		ITEM ID and NOT the coupon ID
            //		DISCOUNT AMOUNT (i.e. * the number of items) and NOT the coupon defined discount (%, $ etc ..)
            foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
                $order_data['coupon_lines'] = array(
                    'id'     => $coupon_item_id,
                    'code'   => $coupon_item['name'],
                    'amount' => $coupon_item['discount_amount'],
                );
            }

            $order_data = apply_filters('remarkety_order_data', $order_data);

            remarkety_for_woocommerce::log('End get_order');
            return $order_data;
        }

        private function user_data_array_by_user_id($id)
        {
            $user = new WP_User($id);
            $res = $this->user_data_array($user);
            return $res;
        }

        private function user_data_array(WP_User $user)
        {
            remarkety_for_woocommerce::log('Start user_data_array');
            if ($user->ID == 0) return array();    // unregistered user

            $fields = array(
                'ID',
                'user_email',
                'first_name',
                'last_name',
                'user_registered',
                'billing_postcode',
                'billing_country',
                'billing_state',
                'billing_city',
                'billing_first_name',
                'billing_last_name',
                '_order_count',
                '_money_spent',
                'billing_phone',
                'billing_address_1',
                'billing_address_2'
            );

            $res = array();
            foreach ($fields as $fld) $res[$fld] = $user->get($fld);
            $res['is_guest'] = false;
            if (property_exists($user, 'roles') && !empty($user->roles)) {
                //Remarkety will use the user role as a single customer group
                $res['roles'] = $user->roles;
            }
            $res['accepts_marketing'] = null;

            remarkety_for_woocommerce::log($res);
            $res = apply_filters('remarkety_customer_data', $res);
            return $res;
        }

        /**
         * @param WC_Product_Variable $product
         * @return array
         */
        private function getVariations($product)
        {
            $available_variations = array();

            foreach ($product->get_children() as $child_id) {
                $variation = wc_get_product($child_id);
                if (!$variation || !$variation->exists()) {
                    continue;
                }

                $available_variations[] = $product->get_available_variation($variation);
            }
            $available_variations = array_values(array_filter($available_variations));

            return $available_variations;
        }

        private function product_data_array($post_id, $full_details = false)
        {
            if (function_exists('wc_get_product'))
                $p = wc_get_product($post_id);
            else
                $p = get_product($post_id);
            $args = array();
            $categories = array();
            foreach (wp_get_post_terms($post_id, 'product_cat', $args) as $term_obj) {
                $categories[] = array(
                    'id' => $term_obj->term_id,
                    'name' => $term_obj->name,
                );
            }

            $tag_objects = get_the_terms($post_id, 'product_tag');
            $tags = $tag_objects;
            if (is_array($tag_objects) && !empty($tag_objects)) {
                $tag_names = array();
                foreach ($tag_objects as $tag) {
                    $tag_names[] = $tag->name;
                }
                $tags = implode(',', $tag_names);
            }

            $res = array(
                'ID' => $post_id,
                'categories' => $categories,
                'tags' => $tags,
            );

            if ($p && is_object($p)) {
                $res['sku'] = $p->get_sku();
                $res['link'] = $p->get_permalink();
                $res['image'] = $this->product_thumb($p->get_id());
                $res['image_full'] = $this->product_thumb($p->get_id(), 'full');
                $res['post_title'] = $p->get_title();
                $res['post_date_gmt'] = $p->post->post_date;
                $res['post_modified_gmt'] = $p->post->post_modified_gmt;
                $res['price'] = $p->get_regular_price();

                if ($full_details) {
                    $res['enabled'] = $p->is_visible();
                    $res['inventory_quantity'] = $p->get_stock_quantity();
                    //remarkety_for_woocommerce::log('product type = ' . $p->product_type);
                    //                 remarkety_for_woocommerce::log('product inventory qty = ' . $res['inventory_quantity']);
                    if ($p->product_type == 'variable') {
                        $product_variable = new WC_Product_Variable($p->get_id());
                        $available_variations = $this->getVariations($product_variable);
                        //remarkety_for_woocommerce::log('variations = ' . print_r($available_variations,true));
                        $inventory = null;
                        foreach ($available_variations as $variation) {
                            $variation_product = new WC_Product_Variation($variation['variation_id']);
                            $variation['image_link'] = !isset($variation['image_link']) ? $this->product_thumb($variation['variation_id']) : $variation['image_link'];
                            $variation['link'] = $variation_product->get_permalink();
                            if (empty($res['inventory_quantity']) && $res['inventory_quantity'] !== 0) {
                                $variation_inventory = $variation_product->get_stock_quantity();
                                if (!empty($variation_inventory) || $variation_inventory === 0) {
                                    // 								remarkety_for_woocommerce::log('variation product inventory qty = ' . $variation_inventory);
                                    $inventory = is_null($inventory) ? $variation_inventory : $inventory + $variation_inventory;
                                    // 								remarkety_for_woocommerce::log('inventory = ' . $inventory);
                                }
                            }
                            $res['variants'][] = $variation;
                        }
                        if (empty($res['inventory_quantity']) && $res['inventory_quantity'] !== 0) {
                            $res['inventory_quantity'] = $inventory;
                        }
                    }
                    $res['display_price'] = $p->get_display_price();
                    $res['simple_price'] = $p->get_price();
                }
            }

            return $res;
        }

        private function product_thumb($post_id, $size = 'thumbnail')
        {
            $thumbId = get_post_thumbnail_id($post_id);
            $images = wp_get_attachment_image_src($thumbId, $size);
            if (empty($images))
                return null;
            return array_shift($images);
        }

        private function coupon_code_exists($coupon_code)
        {
            global $wpdb;
            $coupon_code = $wpdb->escape($coupon_code);
            $q = "
                SELECT ID 
                FROM {$wpdb->prefix}posts 
                WHERE post_title = '{$coupon_code}' 
                AND post_status = 'publish' 
                AND post_type = 'shop_coupon'";

            $res = $wpdb->get_row($q, 'ARRAY_A');
            if (empty($res)) return false;
            return true;
        }

        private function auth($args)
        {
            if (!isset($args[0])) return false;
            if (empty($args[0])) return false;

            if ($args[0] == get_option(self::OPTION_API_KEY, time())) return true;
            return false;
        }

        private function get_status_name_by_slug($status_slug)
        {
            // TODO : cache responses ??
            $term = get_term_by('slug', sanitize_title($status_slug), 'shop_order_status');
            return $term->name;
        }

        public static function status_auth_error()
        {
            return array('error' => 'Authentication error');
        }

        public static function status_params_error()
        {
            return array('error' => 'Parameters error');
        }

        private static function status_success()
        {
            return array('success' => true);
        }

        private static function status_fail()
        {
            return array('success' => false);
        }

        public function api_method_debug($args)
        {

            if (!is_array($args) || count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            remarkety_for_woocommerce::log('Start api_method_debug');
            remarkety_for_woocommerce::log($args);
            $isDebug = $args[1] == true ? 'on' : 'off';
            update_option(self::OPTION_DEBUG_MODE, $isDebug);
            $debug_level = $args[2];
            $is_clear_log = $args[3];

            $res = array('debug' => array());

            if ($debug_level == 1) {
            } else if ($debug_level == 2) {
                $log = file_get_contents(remarkety_for_woocommerce::$logPath);
                $res['debug']['file'] = $log;
            }

            if (!empty($is_clear_log)) {
                $this->clearLog();
            }

            return $res;
        }

        public static function log($msg)
        {
            if (remarkety_for_woocommerce::$debug_mode != 1) return;
            if (is_array($msg)) $msg = "(array dump) " . print_r($msg, true);
            if (is_object($msg)) $msg = "(object dump) " . print_r($msg, true);
            $msg = date('Y-m-d H:i:s') . " : {$msg}" . PHP_EOL;
            file_put_contents(remarkety_for_woocommerce::$logPath, $msg, FILE_APPEND);
            if (remarkety_for_woocommerce::$debug_mode == 1) {
                remarkety_for_woocommerce::$debugData .= $msg;
            }
        }

        public function api_method_create_webhook($args)
        {
            if (!is_array($args) || count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();

            remarkety_for_woocommerce::log('Start api_method_create_webhook');
            remarkety_for_woocommerce::log($args);

            $topic =                 (string)    $args[1];
            $destinationUrl =         (string)    $args[2];
            if (isset($args[3])) {
                $user_id  = $args[3];
            } else {
                $user_id = 1;
            }

            if (empty($topic) || empty($destinationUrl)) {
                remarkety_for_woocommerce::log(sprintf('Topic or destination Url is missing parameters (%s, %s)', $topic, $destinationUrl));

                return self::status_fail();
            }

            $webhook = new WC_Webhook();
            $webhook->set_topic($topic);
            $webhook->set_name('Remarkety (' . str_replace('.', ' ', $topic) . ')');
            $webhook->set_status('active');
            $webhook->set_user_id($user_id);
            $webhook->set_delivery_url($destinationUrl);
            $webhook->set_secret(get_option(self::OPTION_API_KEY));

            if (version_compare(WOOCOMMERCE_VERSION, '3.0', '>=')) {
                $webhook->set_api_version('wp_api_v2');
            }
            try {
                $webhook_id = $webhook->save();
                update_post_meta($webhook_id, '_webhook_pending_delivery', true);
                remarkety_for_woocommerce::log('End api_method_create_webhook');
            } catch (Exception $e) {
                $saved = false;
                remarkety_for_woocommerce::log('Fail api_method_create_webhook: ' . $e->getMessage());
            }

            wp_update_post([
                'ID' => $webhook->id,
                'post_modified' => current_time('mysql')
            ]);
            delete_transient('woocommerce_webhook_ids');

            if ($saved === false) {
                return self::status_fail();
            } else {
                return self::status_success();
            }
        }

        public function api_method_webhooks_list($args)
        {
            if (!is_array($args) || count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();
            remarkety_for_woocommerce::log('Start api_method_webhooks_list');
            remarkety_for_woocommerce::log($args);
            $endPoint = (string)$args[1];
            if ($endPoint == 'all') {
                $endPoint = null;
            }
            $data_store = WC_Data_Store::load('webhook');
            $webhooks   = $data_store->search_webhooks();
            remarkety_for_woocommerce::log($webhooks);
            $result['webhooks'] = array();
            foreach ($webhooks as $webhook_id) {
                $webhook = new WC_Webhook($webhook_id);
                $deliveryUrl = $webhook->get_delivery_url();
                if (empty($endPoint) || $deliveryUrl == $endPoint)
                    $result['webhooks'][] = $webhook_id;
            }

            remarkety_for_woocommerce::log('End api_method_webhooks_list');
            return $result;
        }

        public function api_method_remove_webhook($args)
        {
            if (!is_array($args) || count($args) < 1) return self::status_params_error();
            if (!$this->auth($args)) return self::status_auth_error();
            remarkety_for_woocommerce::log('Start api_method_remove_webhook');
            remarkety_for_woocommerce::log($args);

            $webhook_id = $args[1];
            if (!empty($webhook_id)) {
                $webhook = new WC_Webhook($webhook_id);
                $webhook->delete(true);
            }
            remarkety_for_woocommerce::log('End api_method_remove_webhook');
            return self::status_success();
        }

        private function addDebugIfNeeded(&$result)
        {
            remarkety_for_woocommerce::log('Start addDebugIfNeeded');
            if (remarkety_for_woocommerce::$debug_mode == 1 && remarkety_for_woocommerce::$debugData != null) {
                global $wp_version;
                $woo_version = get_option('woocommerce_version');
                $result['debug'] = remarkety_for_woocommerce::$debugData . PHP_EOL . 'wp_version=' . $wp_version . ' wc_version=' . $woo_version . PHP_EOL;
            }
        }

        private function clearLog()
        {
            file_put_contents(remarkety_for_woocommerce::$logPath, '');
        }

        private function verifyIdsExist($ids, $postType)
        {
            global $wpdb;
            $ids = implode(",", array_map('intval', array_map('trim', explode(",", $ids))));
            $ids_verified = [];
            $q = "
                SELECT ID
                FROM {$wpdb->prefix}posts
                WHERE post_type = '{$postType}'
                AND ID IN ($ids)";
            $results = $wpdb->get_results($q);
            foreach ($results as $result)
                $ids_verified[] = $result->ID;
            return $ids_verified;
        }

        static public function remarketyErrorHandler($errno, $errstr, $errfile, $errline)
        {
            if (!(error_reporting() & $errno)) {
                // This error code is not included in error_reporting
                return;
            }

            switch ($errno) {
                case E_USER_ERROR:
                    $errorType = "ERROR";
                    break;
                case E_USER_WARNING:
                    $errorType = "E_USER_WARNING";
                    break;
                case E_USER_NOTICE:
                    $errorType = "E_USER_NOTICE";
                    break;
                case E_ERROR:
                    $errorType = "E_ERROR";
                    break;
                case E_WARNING:
                    $errorType = "E_WARNING";
                    break;
                case E_NOTICE:
                    $errorType = "E_NOTICE";
                    break;

                default:
                    $errorType = "Unknown error type";
                    break;
            }
            $errStr = $errorType . ": [" . $errno . "] " . $errstr . " Line " . $errline . " in file " . $errfile;
            remarkety_for_woocommerce::$debugData .= $errStr;
            remarkety_for_woocommerce::log($errStr);
            /* Don't execute PHP internal error handler */
            return true;
        }

        private function get_cart_content()
        {
            if (is_user_logged_in()) {
                if (!isset(WC()->session) && !isset(WC()->session->cart)) {
                    remarkety_for_woocommerce::log("get_cart_content() Empty cart.");
                    return;
                }

                $session_customer = WC()->session->get('customer');
                if (empty($session_customer['email'])) {
                    remarkety_for_woocommerce::log("get_cart_content() No email.");
                    return;
                }
            }

            global $woocommerce;

            $items = [];
            $url_param_products = null;
            foreach ((array)$woocommerce->cart->get_cart() as $item) {
                if ($url_param_products) {
                    $url_param_products .= ";";
                }

                $product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
                $product =  wc_get_product($product_id);
                $items[] = [
                    "product_id" => $product_id,
                    "quantity" => $item['quantity'],
                    "name" => $product->get_name(),
                    "name" => $product->get_title(),
                    "price" => $item['line_total'],
                    "tax_lines" => [
                        "price" => $item['line_tax']
                    ],
                ];
                $url_param_products .= $product_id . ":" . $item['quantity'];
            }

            $data = [
                "currency" => get_woocommerce_currency(),
                "customer" => [
                    "id" => $session_customer['id'],
                    "email" => $session_customer['email'],
                    'first_name' => isset($session_customer['first_name']) ? $session_customer['first_name'] : null,
                    'last_name' => isset($session_customer['last_name']) ? $session_customer['last_name'] : null,
                ],
                "line_items" => $items,
                "total_discounts" => $woocommerce->cart->discount_cart,
                "total_price" => $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total,
                "abandoned_checkout_url" => get_site_url() . '/?remarkety_recover_cart=' . $url_param_products,
            ];

            return $data;
        }

        public function wc_get_cart_content()
        {
            if (empty(get_option(self::OPTION_WEBTRACKING_ID))) { //webtracking is disabled
                return;
            }

            try {
                echo json_encode($this->get_cart_content());
            } catch (Exception $e) {
                remarkety_for_woocommerce::log($e);
            }
        }

        public function wc_send_cart_content()
        {
            if (empty(get_option(self::OPTION_WEBTRACKING_ID))) { //webtracking is disabled
                return;
            }

            try {
                $current_user = wp_get_current_user();
                $cart_content = $this->get_cart_content();

                if (!empty($cart_content)) {
                    echo '<script>
                    var _rmData = _rmData || [];
                    //Identify using email address
                    _rmData.push(["setCustomer", "' . $current_user->user_email . '"]);

                    //Cart events
                    data = ' . json_encode($cart_content) . ';
                    _rmData.push(["track", "carts/update", data]);
                </script>';
                }
            } catch (Exception $e) {
                remarkety_for_woocommerce::log($e);
            }
        }

        function add_email_popup()
        {
            $option_email_popup_text = get_option(self::OPTION_EMAIL_POPUP_TEXT);
            $option_email_opt_in_text = get_option(self::OPTION_EMAIL_OPT_IN_TEXT);
            $option_email_dismiss_text = get_option(self::OPTION_EMAIL_DISMISS_TEXT);
            $option_email_continue_text = get_option(self::OPTION_EMAIL_CONTINUE_TEXT);

            if (empty(wp_get_current_user()->user_email) && self::$customer_email_mode) {
                echo "<script>
                    var rm_email_popup_enabled = true;
                    var rm_email_popup_text = '$option_email_popup_text';
                    var rm_email_opt_in_text = '$option_email_opt_in_text';
                    var rm_email_dismiss_text = '$option_email_dismiss_text';
                    var rm_email_continue_text = '$option_email_continue_text';
                </script>";
            }
        }

        function add_email_popup_style()
        {
            if (empty(wp_get_current_user()->user_email) && self::$customer_email_mode) {
                echo '<style type="text/css">';
                echo get_option(self::OPTION_EMAIL_CUSTOM_CSS);
                echo '</style>';
            }
        }

        function remarkety_js_css()
        {
            if (!empty(get_option(self::OPTION_WEBTRACKING_ID))) {
                wp_enqueue_script('remarkety-for-woocommerce', plugins_url('remarkety-for-woocommerce/assets/js/remarkety-cart.js'), array('jquery'), '', true);
            }
            if (empty(wp_get_current_user()->user_email) && self::$customer_email_mode) {
                wp_enqueue_style('remarkety-email-popup', plugins_url('remarkety-for-woocommerce/assets/css/remarkety-popup.css'), false, '1.0.0', 'all');
                wp_enqueue_script('remarkety-email-popup', plugins_url('remarkety-for-woocommerce/assets/js/remarkety-popup.js'), array('jquery'), '', true);
            }
        }

        public function add_marketing_allowed_checkbox()
        {
            if (self::$marketing_allowed_checkbox) {
                $checkbox_label = get_option(self::OPTION_ALLOW_MARKETING_LABEL);
                $checked = (get_option(self::OPTION_ALLOW_MARKETING_DEFAULT) == 'on') ? "checked" : '';
                echo '<input type="checkbox" id="marketing_allowed" name="marketing_allowed" ' . $checked . '><label for="marketing_allowed">' . $checkbox_label . '</label>';
            }
        }

        public function save_marketing_allowed_checkbox($order_id)
        {
            $marketing_allowed = true;
            if (self::$marketing_allowed_checkbox && empty($_POST['marketing_allowed'])) {
                $marketing_allowed = false;
            }
            update_post_meta($order_id, 'marketing_allowed', $marketing_allowed ? '1' : '0');
        }
    }

endif;

if (class_exists('remarkety_for_woocommerce')) {

    register_activation_hook(__FILE__, array('remarkety_for_woocommerce', 'activate'));
    register_uninstall_hook(__FILE__, array('remarkety_for_woocommerce', 'uninstall'));

    $remarkety_for_woocommerce = new remarkety_for_woocommerce();

    if (class_exists('RemarketyRecoverCart')) {
        add_action('plugins_loaded', [new RemarketyRecoverCart(), 'init']);
        register_activation_hook( __FILE__, array(new RemarketyRecoverCart(), 'flush_rules'));
    }

    if (class_exists('RM_REST_controller')) {
        add_filter( 'woocommerce_rest_api_get_rest_namespaces', function ($controllers) {
            $controllers[RM_REST_controller::RM_API_NAMESPACE][RM_REST_controller::TRACING_ENDPOINT] = 'RM_REST_controller';

            return $controllers;
        });
    }
}

if (isset($remarkety_for_woocommerce)) {

    function remarkety_plugin_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=remarkety_for_woocommerce">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", 'remarkety_plugin_settings_link');


    add_filter('woocommerce_rest_orders_prepare_object_query', function (array $args, \WP_REST_Request $request) {
        $min_date_modified = $request->get_param('min_date_modified');
        $max_date_modified = $request->get_param('max_date_modified');

        if (!$min_date_modified && !$max_date_modified) {
            return $args;
        }

        if ($min_date_modified) {
            $args['date_query'][0]['column'] = 'post_modified';
            $args['date_query'][0]['after']  = $min_date_modified;
        }
        if ($max_date_modified) {
            $args['date_query'][0]['column'] = 'post_modified';
            $args['date_query'][0]['before']  = $max_date_modified;
        }

        return $args;
    }, 10, 2);

    add_filter('woocommerce_rest_product_object_query', function (array $args, \WP_REST_Request $request) {
        $min_date_modified = $request->get_param('min_date_modified');
        $max_date_modified = $request->get_param('max_date_modified');

        if (!$min_date_modified && !$max_date_modified) {
            return $args;
        }

        if ($min_date_modified) {
            $args['date_query'][0]['column'] = 'post_modified';
            $args['date_query'][0]['after']  = $min_date_modified;
        }
        if ($max_date_modified) {
            $args['date_query'][0]['column'] = 'post_modified';
            $args['date_query'][0]['before']  = $max_date_modified;
        }

        return $args;
    }, 10, 2);

    add_filter('woocommerce_rest_customer_query', function (array $args, \WP_REST_Request $request) {
        $min_date_modified = $request->get_param('min_date_modified');
        $max_date_modified = $request->get_param('max_date_modified');

        if (!$min_date_modified && !$max_date_modified) {
            return $args;
        }

        if ($min_date_modified) {
            $args['meta_query'][0]['key'] = 'last_update';
            $args['meta_query'][0]['value']  = strtotime($min_date_modified);
            $args['meta_query'][0]['compare']  = '>';
        }
        if ($max_date_modified) {
            $args['meta_query'][0]['key'] = 'last_update';
            $args['meta_query'][0]['value']  = strtotime($max_date_modified);
            $args['meta_query'][0]['compare']  = '<';
        }

        return $args;
    }, 10, 2);
}
