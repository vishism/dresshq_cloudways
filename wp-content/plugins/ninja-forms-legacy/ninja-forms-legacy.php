<?php
/*
Plugin Name: Ninja Forms Legacy
Plugin URI: http://ninjaforms.com/?utm_source=Ninja+Forms+Plugin&utm_medium=readme
Description: Ninja Forms is a webform builder with unparalleled ease of use and features.  This is a legacy version at 2.9.x
Version: 3.9.99
Author: Saturday Drive
Author URI: http://ninjaforms.com/?utm_source=Ninja+Forms+Plugin&utm_medium=Plugins+WP+Dashboard
Text Domain: ninja-forms
Domain Path: /lang/

Copyright 2016 WP Ninjas.
*/

/*
 * Ensure no Ninja Forms collisions occur by loading last with conditions
 */
add_action('ninja_forms_load_legacy','ninjaFormsConditionallyLoadDeprecated');

function ninjaFormsConditionallyLoadDeprecated(){

    if( class_exists('Ninja_Forms', false) ){
        // Ninja Forms core is loaded, we shouldn't be here.
        return;
    }

    //Same logic as in NF 3.0 - ensures against collision
    if (get_option('ninja_forms_load_deprecated', false) && !(isset($_POST['nf2to3']) && (defined('DOING_AJAX') && DOING_AJAX))) {
        include 'deprecated/ninja-forms.php';

        register_activation_hook(__FILE__, 'ninja_forms_activation_deprecated');

        function ninja_forms_activation_deprecated($network_wide)
        {
            include_once 'deprecated/includes/activation.php';

            ninja_forms_activation($network_wide);
        }
    }else{
        // running >3.5.2 and logic deems we run NF 3.x
    }
}
