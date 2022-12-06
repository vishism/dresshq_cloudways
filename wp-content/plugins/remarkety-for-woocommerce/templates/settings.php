<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="remarkety_wrap">
    <div class="remarkety_logo"></div>
    <h1 class="">Remarkety for WooCommerce</h1>
    <h3>Your API Connection Code is:</h3>
    <p><input type="text" size="40" readonly="readonly" value="<?php echo get_option(remarkety_for_woocommerce::OPTION_API_KEY); ?>"></p>
	<h3>How to use the API Connection Code?</h3>
	<p>This is a private generated token which allows the Remarkety service to securely connect to your WooCommerce store.
		<br>You need to enter this code only once in order to setup your Remarkety account.
		<br>You can use the following link in order to set your API Connection Code now: <a target="_blank" href="https://app.remarkety.com/account/storesettings">Your Remarkety account</a></p>
	<h3>Need support?</h3>
    <p>We're always here to help - visit our <a target="_blank" href="https://support.remarkety.com/">Help Center</a>.</p>
</div>