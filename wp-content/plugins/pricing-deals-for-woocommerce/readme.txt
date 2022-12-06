=== Pricing Deals for WooCommerce ===
Contributors: vark
Donate link: https://www.varktech.com/woocommerce/woocommerce-dynamic-pricing-discounts-pro/
Tags: woocommerce bulk pricing, woocommerce discounts, woocommerce dynamic discounts, woocommerce dynamic pricing, woocommerce wholesale pricing, woocommerce cart discount, bulk pricing, cart discount, category discount, customer role discount, user role discount, woocommerce prices, woocommerce pricing
Requires at least: 3.3
Tested up to: 5.5
Stable tag: 2.0.2.01
Requires PHP: 5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Pricing Deals for Woocommerce - Dynamic Pricing, BOGO Deals, Bulk Discounts, Wholesale Discounts, Catalog discounts, Role-based pricing and more.  

== Description ==

A great plugin to offer discount pricing and marketing deals on your Woocommerce website!  
Create a rule tailored to the deal you want. Pricing Deals for Woocommerce is a powerful discounting tool that helps you create both Dyanmic Cart Pricing discounts and Catalog Price (wholesale) discounts. 

*   Streamlined Processing
*   NEW Discount Rule Screen Layout
*   NEW Select Group options
*   NEW BOGO Function - "Discount Equal or Lesser Value Item"
*   NEW Clone Rule Function

==  FULLY TESTED with  ==
*   WordPress 5.5+
*   Gutenberg
*   WooCommerce 4.7+
*   PHP 7.2+


= OVERVIEW =

*   Bogo Deals (buy one get one) 
*   Category Pricing
*   Bulk Pricing in a single rule!  Use the Bulk option for simple deals as well!
*   Catalog Pricing
*   Schedulable
*   Advertise the deal on your site (shortcodes )
*   Multilanguage support through [qTranslateX](https://wordpress.org/plugins/qtranslate-x/) or [GTranslate](https://wordpress.org/plugins/gtranslate/)
*   Order History CART discount reporting  


= DISCOUNT TYPES =

*   Percent Discount
*   Fixed Amount Discounts  *(applied across a group or individually)*
*   Package Pricing
*   Discount Cheapest / Most Expensive / Equal or Lesser Value Item


= Pricing Deals FREE =  

*   NEW Select (Include or Exclude) by:
     - *Whole Store*
     - *Variation name Across Products*
     - *Brands*
     - *Groups*
     - *Members*
*   Bulk Discounts
     - *Buy 5 get a discount, buy 10 get a larger discount*
     - *Buy $10 get a discount, buy $100 get a larger discount*
*   BOGO Deals (Buy one get one)
     - *Buy 1, get next 1 at a discount* 
*   Cart Deal activation by Woo Coupon
*   Catalog Pricing
     - *Show the discount directly in the catalog pricing display.* 
*   Marketing
     - *Theme Sales and Promotions Marketing by displaying the Rule message via shortcode (One Day Sale!)*
*   Show a Cart discounts directly in the Unit Price, or as an automatically-inserted Woo Coupon


= Pricing Deals PRO = 

*   Expands functionality by adding many additional features
*   NEW Select (Include or Exclude) by:
     - *Whole Store*
     - *Category, Custom Pricing Deals Category*
     - *Product, Variation*
     - *Variation name Across Products*
     - *Brands*
     - *User Role*
     - *Email or Customer Name*
     - *Groups*
     - *Members*
*   NEW Advanced Select Group options:
     - *"And"  option  - Require One entry in a list to be in the cart to complete the deal*
     - *"Or"   option  - Any entry in a list can complete the deal*
     - *"Each" option  - Require Each entry in a list to be in the cart to complete the deal* 
*   Automatically insert a Free item:
*   Set Customer Limits:
     - *Example: "One per customer"*
*   Product-level Deal Participation selection
*   Retail / Wholesale Product Visibility and Salability control
*   Wholesale Tax Free/Buy tax free purchasing
*   Add a message next to all Catalog discounts

[GET Pricing Deals Pro](https://www.varktech.com/woocommerce/woocommerce-dynamic-pricing-discounts-pro/)


= UNPARALLELED CUSTOMER SERVICE =

*   Customer Service is as important as the plugin functionality itself
*   [Support](https://www.varktech.com/support/) is open 7 days for questions and 1-on-1 assistance.
*   [Documentation](https://www.varktech.com/documentation/pricing-deals/introrule/)
*   [Deal Examples](https://www.varktech.com/documentation/pricing-deals/examples/)


= Additional Plugins by VarkTech.com =
1. [Wholesale Pricing for WooCommerce](https://wordpress.org/plugins/wholesale-pricing-for-woocommerce) .. (skinnier pricing deals plugin)
1. [Cart Deals for WooCommerce](https://wordpress.org/plugins/cart-deals-for-woocommerce) ..  (skinnier pricing deals plugin)
1. [Minimum Purchase for WooCommerce](https://wordpress.org/plugins/minimum-purchase-for-woocommerce)
1. [Maximum Purchase for WooCommerce](https://wordpress.org/plugins/maximum-purchase-for-woocommerce) 
1. [Min or Max Purchase for WooCommerce](https://wordpress.org/plugins/min-or-max-purchase-for-woocommerce)   


= Minimum Requirements =

*   WooCommerce 2.0.14+
*   WordPress 3.3+
*   PHP 5+

== Install Instructions ==

1. Upload the folder `pricing-deals-for-woocommerce` to the `/wp-content/plugins/` directory of your site
1. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Where can I find plugin Documentation and Deal Examples? =
[Documentation](https://www.varktech.com/documentation/pricing-deals/introrule/)
[Deal Examples](https://www.varktech.com/documentation/pricing-deals/examples/)


= How to set up BULK PRICING / PRICING TABLES =

PRICING DEAL RULE SETUP:
Blueprint Section
Discount Type : Cart Deal
Deal Type : Bulk Purchasing   ( Pricing Table )
Deal Action : Discount item in cart already
Show Me : Advanced
 
Discount Group Section
Discount Group Select Group By : [your choice]
Discount Group Pricing Table 
- Count by Units or Currency: 
- - [What are we counting? select by units or by $$]
- Begin / End Ranges Apply To: 
- - [Counting the whole subtotal, or by individual cart line item total?]
- Pricing Table Rows : [fill in as desired!]

Discount Section
Discount Applies to : [your choice]

Checkout Message : WILDCARD message options for BULK rules
  - {show_discount_val}   - wildcard shows discount val: 
- - discount percent or discount amount applied from Pricing Table 
  - {show_discount_val_more}   - wildcard shows: 
- - discount val and more in msg



= Buy 1 get next 1 at a Discount =

PRICING DEAL RULE SETUP:
Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
Deal Action : Discount Next item added to cart 
Show Me : Advanced
 
Qualify Group Section
Qualify Group Select Group By : [your choice]
Qualify Group Amount Type : Buy Unit Quantity
Qualify Group Amount Count : 1
Qualify Group Amount Applies To : All Products
Qualify Group  Rule Usage Count  : [your choice]
 
Discount Group Section

Discount Group Select Group By : "Discount Group same as Qualify Group" - [Where the Buy and the Discount Group are the SAME ]
- OR -
Discount Group Select Group By : [some other choice] - [Where the Buy and the Discount Group are DIFFERENT ]

Discount Group Amount Type : Discount Next One
Discount Group Amount Applies To : All Products
Discount Group Repeat : None
 
Discount Section
Discount Amount Type : [your choice]
Discount Applies To : Each Product
Checkout Message : [your choice]

Buy 1 fruit get next fruit at a discount  (Where the Buy and the Discount Group are the SAME)
Buy 1 apple get next orange at a discount (Where the Buy and the Discount Group are DIFFERENT)


= Buy 2 get 1 of them at a discount =

Buy 2 get the FIRST of those 2 at a discount!

PRICING DEAL RULE SETUP:
Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
Deal Action : Discount item in cart already
Show Me : Advanced
 
Qualify Group Section
Qualify Group Select Group By : [your choice]
Qualify Group Amount Type : Buy Unit Quantity
Qualify Group Amount Count : 2
Qualify Group Amount Applies To : All Products
Qualify Group  Rule Usage Count  : [your choice]
 
Discount Group Section
Discount Group Select Group By : Discount Group same as Qualify Group
Discount Group Amount Type : Discount Unit Quantity
Discount Group Amount Count : 1
Discount Group Amount Applies To : All Products
Discount Group Repeat : None
 
Discount Section
Discount Amount Type : [your choice]
Discount Applies To : Each Product
Checkout Message : [your choice]


= BUY 2 or more GET 10% OFF ALL  =

PRICING DEAL RULE SETUP:
Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
Deal Action : Discount item in cart already
Show Me : Advanced
 
Qualify Group Section
Qualify Group Select Group By : [your choice]
Qualify Group Amount Type : Buy Unit Quantity
Qualify Group Amount Count : 2
Qualify Group Amount Applies To : All Products
Qualify Group  Rule Usage Count : Apply Rule Once per Cart
 
Discount Group Section
Discount Group Select Group By : Discount Group same as Qualify Group
Discount Group Amount Type : Discount Unit Quantity
Discount Group Amount Count : 1
Discount Group Amount Applies To : All Products
Discount Group Repeat : Unlimited Group Repeats
 
Discount Section
Discount Amount Type : % Off
Discount Amount Count : 10
Discount Applies To : Each Product
Checkout Message : [your choice]


= How to set up a BOGO DEAL =
[Deal Examples](https://www.varktech.com/documentation/pricing-deals/examples/)
Look for "Buy a Laptop, get a Second Laptop $100 off"

PRICING DEAL RULE SETUP:
Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
Deal Action : Discount Next item added to cart
Show Me : Basic
 
Qualify Group Section
Qualify Group Amount Type : Buy Each unit
Qualify Group Filter : Category / Logged-in Role / Plugin Category
Buy Filter Product Categories : Laptops
 
Discount Group Section
Discount Group Amount Type : Discount Next One (single unit)
Discount Group Select Group By : Discount Group same as Qualify Group
 
Discount Section
Discount Amount Type : $ Off
Discount Amount Count : 100
Checkout Message  (sample) : 2nd Laptop 20% off
Sell the Deal Message  (sample) : One Day Sale, buy a Laptop, get a 2nd at 20% off! 

= How to set up a CATALOG (Wholesale Pricing) DEAL =
[Deal Examples](https://www.varktech.com/documentation/pricing-deals/examples/)
Look for "10% Off Entire Catalog"

Blueprint Section
Discount Type : Catalog Discount
Deal Type : Whole Catalog on Sale
Deal Action : Apply Discount to Catalog Item
Show Me : Basic
 
Qualify Group Section
Qualify Group Amount Type : Buy Each unit
Qualify Group Filter : Any Product
 
Discount Section
Discount Amount Type : % Off
Discount Amount Count : 10
Checkout Message  (sample) : none
Sell the Deal Message  (sample) : Introductory Sale, 10% Off the Entire Store! 


= BUY 2 Discount the Cheapest  =

 Buy 2 Get cheapest (of those 2) at a discount 

PRICING DEAL RULE SETUP:
Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
Deal Action : Discount Next item added to cart
Show Me : Advanced 
Apply Discount to - which - Cart Item First : Apply to Cheapest
 
Qualify Group Section
Qualify Group Select Group By : [your choice]
Qualify Group Amount Type :  Buy Unit Quantity 
Qualify Group Amount Type Count : 2
Qualify Group Amount Applies To : All Products 
Qualify Group  Rule Usage Count : [your choice]
 
Discount Group Section
Discount Group Select Group By : [usually the *same group* as the Qualify Group Filter, selected in the same way:] 
	[your choice of **any option for the filter, BUT NOT 'discount group same as Qualify Group'  **]
Discount Group Amount Type : Discount Next One (single unit)
Discount Group Repeat : none
 
Discount Section
Discount Amount Type : Free
Discount Applies To : Each Product 
etc ...

PS 
(the Qualify Group count may look strange combined with a 'next' deal, but it's correct...)
JUST CHANGE:
- Qualify Group Select Group By
- Discount Group Select Group By
- Qualify Group  Rule Usage Count


= For Each $100 Purchased, get a $20 discount =

Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
Deal Action : Discount item in cart already
Show Me : Advanced

Qualify Group Section
Qualify Group Select Group By : Any Product
Qualify Group Amount Type : Buy $$ Value
Qualify Group Amount Count : 100
Qualify Group Amount Applies To : All Products
Qualify Group  Rule Usage Count : Unlimited

Discount Group Section
Discount Group Select Group By : Discount Group same as Qualify Group
Discount Group Amount Type : Discount $$ value
Discount Group Amount Count : 100
Discount Group Amount Applies To : All Products
Discount Group Repeat : None

Discount Section
Discount Amount Type : $$ Off
Discount Amount Count : 20
Discount Applies To : All Products
Checkout Message : [your choice]


= BUY 1 of Anything, get a $20 discount CART Discount =

Blueprint Section
Discount Type : Cart Deal
Deal Type : Buy one Get one (Bogo)
 Deal Action : Discount Next item added to cart 
Show Me : Advanced
 
Qualify Group Section
Qualify Group Select Group By : Any Product
Qualify Group Amount Type : Buy Each Unit
Qualify Group Amount Count : 1 
Qualify Group Amount Applies To : All Products
Qualify Group  Rule Usage Count : Apply Rule Once per Cart
 
Discount Group Section
Discount Group Select Group By : Any Product [applies to whole cart]
- [usually the *same group* as the Qualify Group Filter, selected in the same way:] 
- [your choice of **any option for the filter, **BUT NOT** 'Discount group same as Qualify Group'  **]
Discount Group Amount Type : Discount Next Unit Quantity
Discount Group Amount Count : 99999 [ 5 9's (**which triggers the spread across the Discount Group**) ]
Discount Group Amount Applies To : All Products
Discount Group Repeat : None
 
Discount Section
Discount Amount Type : $$ off
Discount Amount Count : 20
Discount Applies To : All Products
Checkout Message : [your choice]
[When spreading a fixed discount across multiple products, best to use 
 - Pricing Deals Setting/Unit Price Discount or Coupon Discount: Coupon Discount ] 


= Where to get Pricing Deals PRO =
[GET Pricing Deals Pro](https://www.varktech.com/woocommerce/woocommerce-dynamic-pricing-discounts-pro/)

== Screenshots ==

1. Pricing Deals - Add New Rule Basic Lower Area
2. Pricing Deals - Add New Rule Top Area
3. Pricing Deals - Cart Widget with Discount
4. Pricing Deals - Checkout with Discount


== Changelog ==

= 2.0.2.01 - 2020-08-17 =
* Fix - Fix to Advertising message shortcode display error
* Fix - Fix to Lifetime shortcode error function


= 2.0.2.0 - 2020-01-15 =
* Fix - auto-add when cart accessed in 2nd session
* Enhancement  - Vast reduction in memory needed for plugin function
* Enhancement  - Streamline discount group include/exclude processing
* Enhancement  - Prevent external notices produced by other plugins, from displaying on wp-admin Pricing Deals Rule screen 
* Enhancement  - Add USER selection to Customer Rule Limit on Pricing Deals Settings page
          	 Make logged-in user selection a default.
        	 Add message to Pricing Deals Rule screen UI pointing towards Pricing Deals Settings page, when Customer Rule Limit selected.
        	 Make 'by IP' setting 'No' by default.  Conversion will set to 'no' if no Customer Rule Limits in use. 
* Enhancement  - added option to delete all plugin resources on uninstall
          	 When plugin active, click on "Remove All" on plugins page
          	 OR go to Pricing Deals Settings page, click on 'Set "Remove All"'
          	 - when plugin is deleted, all traces of Pricing Deals will be removed - Settings, Options, Tables etc.
* Fix - Customer IP was not always being correctly tracked in Lifetime rules.
* Note         - Updating to this generation requires a DB update.  Suggest full backup first.  

= 2.0.1.1 - 2019-12-05 =
* Fix - OPTIONAL - Fix to advertising message display inadvertently introduced in v2.0.1.0 
        	IF advertising message is not used, this update is optional.
        	(For V2.0.1.1 there is no matching PRO update, none required)

= 2.0.1.0 - 2019-12-02 =
* Fix - Wp-admin logout error repaired
* Fix - In Cart processing, if auto add done prior to login, and then login occurs, can throw fatal if object not there.
* Fix - Rule Email/Name search criteria issue fixed
* Fix - Rule Role search criteria issue fixed
* Fix - Timezone fix
* Enhancement  - At discount execution time, apply rule customer-level testing at the rule level, not the product level.

= 2.0.0.9 - 2019-08-13 =
* Fix - For WC_Product_Addons and WC_Measurement_Price_Calculator plugin compatibility
* Fix - Membership access in apply rules module.
* Enhancement  - 'Remove Extra JS from Rule Page' + 'Remove Extra CSS from Rule Page'
        	now set to 'yes' by default - both in old pages and in new.
        	Setting these to 'yes' universally heads off future issues with other modules.
        	Update to settings also made universally to existing installations.

= 2.0.0.8 - 2019-04-16 =
* Enhancement - If you are experiencing an issue in wp-admin when Editing 
        	a Pricing Deal Rule, this is likely due to another module
        	mishandling Wordpress resources.  Suggest you
        	go to wp-admin/pricing deals rules/pricing deals settings page.
        	On the horizontal JUMP TO menu, click on 'System Options'.
        	- at 'Remove Extra JS from Rule Page', select 'Yes'
        	- at 'Remove Extra CSS from Rule Page', select 'Yes' 
        	and click on 'Save Changes.
                Then close your browser completely, and test in a fresh browser session.
                Using 'Add New Rule', create a new rule for testing. 
                Then try editing an existing rule. 
        	Any existing rules which are still not displaying correctly will need to be 
                deleted and recreated, but FIRST take a screenprint of the existing rule.

* Enhancement - Updates to accomodate Woocommerce 3.6.0
* Fix - Groups selection function repaired.
* Fix - Memberships selection function repaired. 
        (NOTE: Older versions of woocomerce memberships plugin cause WC_VERSION tests to FAIL)
* Fix - When using a comma as a decimal separator, the Bulk rule had an issue displaying decimal values in the rule table. Resolved.
* Fix - If Woocommerce plugin not installed and active, error message sent.

= 2.0.0.7 - 2019-01-30 =
* Fix - Rare purchase email crossout display issue resolved.
* Fix - Data conversion for "Deals" coupon issue resolved.
* Enhancement - Added options for "vtprd_pricing_deal_msgs_standard" shortcode allowing
           	display of all rule advertising msgs in the theme 

= 2.0.0.6 - 2019-01-28 =
* Fix - Rule product search issue resolved.
* Fix - Rare cart Unit pricing crossout display issue resolved.

= 2.0.0.5 - 2019-01-22 =
* Fix - On the Pricing Deals Settings page, in Coupon Discount mode, the "Cart Cross-Rule Limits" settings were having an issue, now repaired.
* Enhancement - For Pricing Deals Settings page -- Show Discount as Unit Price Discount or Coupon Discount 
           	If "Coupon Discount" selected, the 
           	** single automatic coupon name showing the discounting **  
           	can be custom named in the new "Automatic Coupon Name" field directly below 
* Enhancement - If rule discount is activated by a WOO coupon ("Discount Coupon Code"), the WOO coupon is now
           	automatically created as well. 
* Enhancement - In the front end, if rule discount is activated by a WOO coupon , the WOO coupon code shows
           	in a "coupon code ... 00" cart totals line.
           	There is now a switch to prevent the "...00" line showing in the front end.
           	On the Pricing Deals Settings page, look for:
		-- If rule Activated by Coupon Code - show the "coupon code ... 00"    Cart totals line"
		--- To prevent this line from showing in the front end, set to 'No'
* Enhancement - Allow additional product types from additional plugins

         	If added PRODUCT type from additional Plugins needed
                   Find all the Product types needed in your additional plugins, by searching for: "register_post_type".
                   In the "return" statement below, string them together as the example suggests
           
        	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
        	add_filter( 'vtprd_use_additional_product_type', function() { return (array('product-type1','product-type2')); } ); 
          
                   THIS FILTER will add your added PRODUCT type to BOTH the PRODUCT selector AND the Pricing Deal Category selector
                   - so if you want a group of products to be included in a rule, you can either list them in the PRODUCT selector,
                   or make sure they participate in a Pricing Deal Category, which is then selected in your desired rule.

= 2.0.0.3 - 2018-12-05 =
* Fix - Javascript issue in Rules screen, where Groups and Memberships were not displaying correctly, now repaired
* Enhancement - Pricing Deals Rule screen, remove additional excess Javascripts.
* Fix - Copy to Support button now supplies Bulk rule info correctly

= 2.0.0.2 - 2018-11-11 =
* Fully compatible with: WordPress 5.0+, Gutenberg, WooCommerce 3.5+, PHP 7.2
* Fix - Code changes to support PHP 7.2
* Enhancement - Updates to accomodate Wordpress 5.0
* Enhancement - Pricing Deals now removes a lot of excess Javascripts.
           	If you are editing a rule and having trouble with the Pricing Deals RULE 'Date Picker':
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	add_filter( 'vtprd_trouble_with_date_picker', function() { return TRUE; } ); 
* Fix - Package Pricing - for the Price of $$ amount error fixed
* Fix - Package Pricing - allow 'Each Product' selection on Qualify Group, Group Amount Applies to
          	allowing each product to process separately as its own qualify group...
* Fix - Shortcodes now all react appropriately to begin/end dates

= 2.0.0.1 - 2018-07-05 =
* Fix - In some sites, rules were not executed when the free version was installed solely.  Issue repaired.

= 2.0.0.0 - 2018-07-01 =
* Enhancement - RULE SCREEN REWRITE - Look and feel revamp.
          	Rewrite of the way to choose a Category, Product, etc in a rule - now using Select2. 
          	Added Group Choice by Customer (email and name), Brands and Groups. 
          	Each group choice will have an include/exclude option.

          	If using a BRANDS plugin not in the supported list, add support by doing the following:
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	//change [brands plugin taxonomy] to the brands taxonomy of your brands plugin 
          	//(*** to find your plugin's taxonomy, search the plugin for 'register_taxonomy' ***)  
          	add_filter( 'vtprd_add_brands_taxonomy', function() { return  'brands plugin taxonomy'; } );
* Enhancement - New "Apply Discount to Equal or Lesser Value Item"
                      //--------------------------------------------------------------------                
                      // Discount Equal or Lesser Value Item first
                      //- Discount the item(s) in the Discount Group of equal or lesser value to the most expensive item in the Qualify Group    
                      //--------------------------------------------------------------------   
* Enhancement - New 'Copy to Support'button, for 1-click copying of rule for support emails 
* Enhancement - Removed Include/Exclude Box on Product screen.  All existing include/exclude converted into new rule structures.
          	Report documeting all original include/exclude settings available:
          	Ex: http://[your website name]/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=reportInclExclV2.0.0  
* Enhancement - Deal activation by Woo coupon and auto add for free function now works together! 
* Fix - Compatible again with Gravity Forms plugin.  
		Gravity Forms plugin (v2.3.2+)  interferes with the wordpress is-admin() conditional, 
			making Gravity Forms incompatible with Pricing Deals.  
		Replaced is-admin() conditional with REQUEST_URI test, where required for compatibility.  
* Note        - DB update required, suggest full backup first.  

= 1.1.8.3 - 2018-01-27 =
* Enhancement - Specialized filter to disallow discounts by Product Type / post_type.  Do not discount 'donation','gift-card', etc.
          	// ( wordpress.org/plugins/donations-for-woocommerce/ YITH Woocommerce Gift Card, ... )
          	// Identify the custom post_type created by your conflicting plugin.
          	//(search through your plugin for this statement: register_post_type ...)
          	// Put that custom post type into the following and install as per directions.
          	// Then turn the conflicting plugin back on and test.
          	// ***to TURN ON this new action***, add the 'add_filter...' statement to your theme/child-theme functions.php file  
          	add_filter( 'vtprd_disallow_product_types', function() { return array('donation','gift-card'); } ); 
          	//( to list more than 1, list using: array('xxx','yyyy','zzz') ) 
* Enhancement - Copy to Support button - one button copy for support emails
* Fix - intermittent rounding error (trailing 5 rounding down) resolved

= 1.1.8.2 - 2018-01-27 =
* Enhancement - Updates to accomodate Woocommerce Version 3.3
* Enhancement - TEST license Improvements (for more info, see comments at top in admin/vtprd-license-options.php file) 
    
= 1.1.8.1 - 2017-12-29 =
* Enhancement - Clone Rule Button now available on each rule.  Clicking on the button makes a rule copy, and the copy is placed in pending status.
* Enhancement - Specialized filter to help BOGO deals.  When the Qualify Group and the Discount Group share members, but the Discount Group
          	should not receive discounts on those products in the Qualify Group. 
          	   - You must supply a RuleID / list of RuleIDs to the function .
          	   - RuleID is the number in your rule URL: "...com/wp-admin/post.php?post=1066&action=edit"
          	//to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file  
          	add_filter( 'vtprd_remove_buy_group_members_from_get_group', function() { return  '1066,1918,1945' ; } ); 
* Fix - Currency-based rule repeat bug fixed 
          	When a currency value is greater than what's needed to satisfy a rule iteration, 
          	The remaining value is used in succeeding iterations (until exhausted). 
* Fix - Catalog rule discount display now shows original list price as old price crossed out, even if a woo sale is price has bee overridden.
* Fix - Spread a $$ discount across the whole cart/Discount Group. USE '99999' in Discount Group amount: 
          	   - SEE FAQ Example "BUY 1 of Anything, get a $20 discount CART Discount"
* Fix - custom table definitions autofix

= 1.1.8.0 - 2017-10-21 =
* Enhancement - Bulk Processing in a single rule!  Use the Bulk option for simple deals as well! 
* Enhancement - CART Discount reporting in Order History (very basic formatting, will improve soon)
          	//to TURN OFF this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file  
          	add_filter( 'vtprd_do_not_show_order_history_report', function() { return  TRUE; } );  
* Fix - at update time, had a rare issue when an active rule had auto add for free.
* Fix - for the 'get variations' button in Rule UI product filter
* Fix - custom table definitions 

= 1.1.7.2 - 2017-10-10 =
* Enhancement - Prevent Pricing Deals and Pricing Deals Pro from background auto-updating.  These plugins must always
		These plugins must be updated *only* by and an admin click in response to an update nag ! 
* Enhancement - Show WOO sales badge if shop product discounted by CATALOG rule discount
          	//to TURN OFF this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file  
          	add_filter( 'vtprd_show_catalog_deal_sale_badge', function() { return  FALSE; } );  
* Fix - by Variation Name across Products now also applies to CATALOG rules
* Enhancement - Allow multiple coupons in coupon mode
          	//to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file
          	add_filter( 'vtprd_allow_multiple_coupons_in_coupon_mode', function() { return TRUE; } ); 
* Enhancement - By Role now tests against all roles user participates in (primary and secondary) 
* Fix - by Variation Name across Products in the Discount Group now saving name correctly.
* Enhancement - New Filter to Allow full discount reporting on customer emails for Units discounting
          	//to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	add_filter( 'vtprd_always_show_email_discount_table', function() { return TRUE; } );
* Fix - VTPRD_PURCHASE_LOG definition changed, 2 columns now Longtext.
* Fix - Various Woocommerce 3.0, 3.12 and 3.2 log warnings resolved. 
* Enhancement - Limit Cart Rule Discounting to a Single Rule or Rule type
          	new setting: - Pricing Deal Settings Page => 'Cart Cross-Rule Limits'
* Enhancement - New Filter vtprd_cumulativeRulePricing_custom_criteria.  Allows custom control of rule interaction.
          	Using this filter, create your own custom function to manage Rule interaction
          	(folow the example for using the 'vtprd_additional_inpop_include_criteria' in the PRO version apply-rules.php)

= 1.1.7.1 - 2017-05-26 =
* Enhancement - In the Group Product Filter, now select 
		by Variation Name across Products ! 
		Example: Apply a discount across all 'large' size shirts
* Enhancement - FOR Cart rule with 'Buy amount applies to' = EAch, and discount group same as Qualify Group,
		process EACH product matching the choice criteria INDIVIDUALLY.
		  *NOTE* if 'Qualify Group RULE USAGE COUNT' = apply rule once per cart, the rule will be applied
		  **once per product**
* Enhancement - Filter to allow page Refresh of the CART page after an AJAX update 
   		Valid Values for FILTER:
  		    CouponOnly - only send JS on Cart Page when an existing rule is actuated by a Coupon
  		    Never - never send the JS on Cart Page [DEFAULT] 
 		    Always - always on Cart Page      
            	//Be sure to clear the cache and start a fresh browser session when testing this...
            	function js_trigger_cart_page_reload() {
           		 return 'Never';  //valid values: 'CouponOnly' / 'Never' / 'Always'
          	}
          	add_filter('vtprd_js_trigger_cart_page_reload', 'js_trigger_cart_page_reload', 10);

          	//Alternative: same solution with less code, no additional function:
          	add_filter( 'vtprd_js_trigger_cart_page_reload', function() { return  'Never'; } );  //valid values: 'CouponOnly' / 'Never' / 'Always'
* Fix - Remove warnings on coupon use
* Fix - IF auto add to cart granted and user logs in, correct auto added product count will be maintained.

= 1.1.7 - 2017-04-03 =
* Enhancement - Updates to accomodate Woocommerce 3.0
* Enhancement - New 'ex. VAT' filter - 'vtprd_replace_ex_vat_label'
* Change -  (due to change in Woocommerce 3.0)
    	    If you choose to show the Pricing Deals discount via an auto-inserted Coupon, and
    	    you want  translate/change the name of  the 'Deals' title of the auto-inserted "Coupon: Deals", 
		1. ADD the following wordpress filter:
      		// Sample filter execution ==>>  put into your theme's functions.php file (at the BOTTOM is best), so it's not affected by plugin updates
         	 function coupon_code_discount_title() {
           		 return 'different coupon title';  //new coupon title
          	}
          	add_filter('vtprd_coupon_code_discount_title', 'coupon_code_discount_title', 10); 

		**New**
		2. ALSO ADD a new Woocommerce Coupon in wp-admin/woocommerce/coupons
		  Required Coupon Attributes:
   			Coupon Code => coupon title from (1) above
   			Coupon Type => Fixed Cart Discount
   			Coupon Amount => 0

= 1.1.6.8 - 2016-08-08 =
* Fix - added warning about using Deal Type cheapest/most expensive, pointing instead to new "Apply to cheapest item first".
* Enhancement - Added "/stage" etc list to valid Pro license test names
* Enhancement - Function added to prevent Pricing Deals + Pro from automatically updating 
			(and possibly causing version mismatch issues)

= 1.1.6.7 - 2016-07-18 =
* Enhancement - Improved "Cheapest" Deals: 
		Cheapest option now in the Rule's Blueprint area - **please hover** to read the how-to.
* Enhancement - Pro plugin custom "update available" messaging now included on the plugins page, 
		just under the listing forPricing Deals Pro.
* Enhancement - Only check for Pro plugin update if required.

= 1.1.6.6 - 2016-07-09 =
SVN Update issue, final resolution!

= 1.1.6.5 - 2016-07-09 =
SVN Update issue

= 1.1.6.4 - 2016-07-09 =
SVN Update issue

= 1.1.6.3 - 2016-07-09 =
* Enhancement - Pro plugin updater now active - Pro plugin updates now delivered directly to the plugins.php page
* Fix - If Woocommerce deactivated, slide through with no warning
* Fix - Warn if PHP version less than 5.3.1
* Fix - repair rare Fatal error: Call to a member function get_tax_class() ...
* Fix - repair auto add for free bug

= 1.1.6.2 - 2016-06-19 =
* Fix - Auto update was accidentally forcing re-registration

= 1.1.6.1 - 2016-06-19 =
* Fix - URL fixes in anchors
* Fix - Registration fixes (pro only):
	 - fix to rego clock (pro activation)
	 - fix cron scheduling
	 - Localhost and IP warnings suspended
	 - Phone Home frequency reduced (pro activation check)
	 - document that Licensing and PHone Home functions are PRO-only, and run
		only if the PRO version is installed and active

= 1.1.6 - 2016-06-15 =
* Fix - Added warning for invalid Client website IP address
* Fix - delete 'Deals' coupon when not needed for plugin setting
* Fix - removed bloginfo from admin pages
* Fix - minor registration issues
* Enhancement - Now allow ANY staging.wpengine site as part of a test site registration

= 1.1.5 - 2016-06-05 =
* Fix - Added code to handle the rare "Fatal error: Call to a member function get_tax_class() ..."
* Enhancement - At store page refresh, catch any price changes and refigure discount
* Enhancement - Now with FREE full PRO demo available, 3-Day PRO licensing included.

= 1.1.1.3 - 2016-01-22 =
* Fix - Date range end date issue resolved
* Fix - Price Range of Variable Products with a partial Catalog Discount (where only some of the variations have discounts) resolved
* Enhancement - Now Compatible with WooCommerce Currency Switcher  (by realmag777).

= 1.1.1.2 - 2015-11-07 =
* Fix - Coupon discount mini-cart intermittent display issue on 1st time for auto adds
* Enhancement - Formerly, only a single "auto add for free" rule was allowed.
		Now multiple "auto add for free" rules is fully supported. 

= 1.1.1 - 2015-09-26 =
* Enhancement - Now Compatible with Woocommerce Measurement Price Calculator (Woocommerce + Skyverge). 
* Enhancement - Now Compatible with Woocommerce Product Addons (Woocommerce). 
* Enhancement - 'Cheapest in the cart' - see 'cheapest in cart filter' txt file in pro .
* Fix - Other rule discounts = no
* Fix - improve efficiency for Rule Discounts activated by Coupon
* Fix - variation discount pricing display
* Fix - shortcode in-the-loop product messaging
* Fix - discount and sale price scheduling
* Fix - fix for variation pricing for variation groups larger than 20, Catalog rules discount
* Fix - on Users screen
		Pricing Deals User Tax Free (box) User Transactions are Tax-Free
		- is now recognized by the system correctly
		- NB - if the switch is set on, then toggled off, to clear same browser sesion of the setting:
			- Pricing Deal Settings 'nuke session variables'
			- log out/log back in to Uswer
		
* Enhancement - New Filter to enable Pricing Deals to pick up pricing from other plugins 
    
		     // *** add to bottom of Theme Functions file (before the closing ? line, if there is one)
 		     //allows Pricing Deals to pick up current product pricing from other plugins
 		     //  ---  more resource intensive  ---
     		    add_filter( 'vtprd_do_compatability_pricing', function() { return TRUE; } );
* Enhancement - Catalog Products Purchasability Display (pro):
		- ** Gives you the ability to control Product Purchasability
		- ** You can even turn your Woocommerce Store into a Catalog-only Installation!
		- Product screen now has a 'wholesale product' checkbox in the PUBLISH box
			- Label all wholesale products as wholesale
		- Settings Page now has "Catalog Products Purchasability Display"
			- Choose the Retail/Wholesale display option you want
		- Then as each Retail or Wholesale Capability user logs in, they will see
			- a tailored list (Not logged in = Retail)
* Enhancement - Wholesale Product Visibility (pro):
		- new option - Show All Products to Retail, Wholesale Products to Wholesale	
* Note - Now recommend "Members" plugin by Justin Tadlock, rather than User Role Editor

= 1.1.0.9 - 2015-08-12 = 
* Fix - Variation product discount pricing display due to woo 2.4 changes

= 1.1.0.8 - 2015-07-25 =
* Fix - Wp-admin Rule editing - if advanced field in error and basic rule showing, 
	switch to advanced rule in update process to expose errored field. 
* Fix - fix to user tax exempt status on User Screen - save to user updated, not user making the update!
* Enhancement - New Rule Option => Rule Discounts activated by Coupon
		- https://www.varktech.com/documentation/pricing-deals/introrule/#discount.discountcoupon
		- A Woocommerce Coupon code may be included on a Pricing Deals Rule 
		- if the rule has a Woocommerce coupon code included, that rule's discount will only be applied 
			once the same coupon code is redeemed in the cart.
		- May only be used in a Cart Rule.
		Directions:
		- Create a Woocommerce coupon => set to 'Cart Discount' and 'coupon amount' = 0.
		- In the Pricing Deals rule screen, select 'Advanced Rule' in the Blueprint Area
		- Coupon code (Coupon Title) may be entered in the Discount box area at "Discount Coupon Code"
		- With a Coupon code in the rule, the rule discount will only apply 
			when the matching Coupon Code is presented in the Cart


= 1.1.0.7 - 2015-07-21 =
* Fix - User screen tax exempt flag. 
* Fix - "Discount applied to list price, taken if it is less than sale price" now works with Catalog rules also. 
* Fix - buy_tax_free capability applied globally...
* Enhancement - Wholesale Product Visibility (pro):
		- https://www.varktech.com/documentation/pricing-deals/introrule/#rolesetup.productvisibility
		- Product screen now has a 'wholesale product' checkbox in the PUBLISH box
			- Label all wholesale products as wholesale
		- Settings Page now has "Wholesale Products Display Options"
			- Choose the Retail/Wholesale display option you want
		- Then as each Retail or Wholesale Capability user logs in, they will see
			- a tailored list (Not logged in = Retail)
		- NOTE when testing, use the Members plugin to control the new 'wholesale' capability!			
* Note - Now recommend Members plugin by Justin Tadlock, rather than User Role Editor

= 1.1.0.6 - 2015-07-07 =
* Fix - Auto add free item function. 
* Enhancement - Auto add free item function:
		- Can now add multiple free items using the Discount Group Amount count.
		- New Filter ==> $0 Price shown as 'Free' unless overridden by filter:
			add_filter('vtprd_show_zero_price_as_free',FALSE); 
			(in your theme's functions.php file)

= 1.1.0.5 - 2015-05-22 =
* Fix - Older email clients dropping strikethrough, added css strikethrough
* Fix - Obscure PHP computation issue (floating point comparison)
* Enhancement - New Template Tag
		vtprd_the_discount() ==> Show the formatted total discount
		Template code: if ( vtprd_the_discount() ) { echo vtprd_the_discount();}
* Enhancement - Shortcode ==> pricing_deal_msgs_standard
		new functionality
		Sample template code:
      			$product_id = get_the_ID();
      			echo do_shortcode( '[pricing_deal_msgs_standard  
						force_in_the_loop="yes"  
						force_in_the_loop_product="'.$product_id.'"]');
* Enhancement - Cleanup if last rule deleted (admin/..rules-delete...)

= 1.1.0.4 - 2015-05-01 =
* Fix - Sale Price Discount exclusion switch issue resolved

= 1.1.0.3 - 2015-04-28 =
* Enhancement - Unit Price Discount subtotal crossouts now on Checkout and Thankyou pages,
	and also on Customer Email.

= 1.1.0.2 - 2015-04-25 =
* Fix - Woo Points and Rewards + regular coupons

= 1.1.0.1 - 2015-04-23 =
* Fix - Compatability issue with other Coupon-based plugins resolved,
	in particular Woo Points and Rewards
* Enhancement - New notification of mismatch between Free and Pro versions

= 1.1 - 2015-04-19 =
* Enhancement - In the Qualify Group Filter, added Logged-in Role to Single product and single product with variations:
	By Single Product with Variations   (+ Logged-in Role) 
	By Single Product    (+ Logged-in Role)          

= 1.0.9.7 - 2015-04-19 =
* Fix - Catalog rule variation discounts (from-to) *crossout* value had a rare issue

= 1.0.9.6 - 2015-04-14 =
* Fix - Catalog rule variation discounts (from-to) changed to only show a single price, when all
	variation prices are the same.

= 1.0.9.5 - 2015-04-11 =
* Fix - Widget Catalog discount pricing for variations had an issue.
* Fix - Variation Catalog Discount pricing showing least-to-most expensive had an issue 
	when the actual variations were not in ascending sequence by price.  
	Now sorted for least/most expensive.
* Fix - Different decimal separator for Unit Price discount crossout value in cart/mini-cart.

= 1.0.9.4 - 2015-04-10 =
* Fix - Cart issue if only Catalog discount used.

= 1.0.9.3 - 2015-04-09 =
* Enhancement - Redux - Added **Settings Switches** to SHOW DISCOUNT AS:
		**UNIT COST DISCOUNT** or **COUPON DISCOUNT**
		- "Unit Cost Discount" discounts the unit price in the cart immediately
			- Old price crossed out, followed by discounted price is the default
			- can show discount computation for testing purposes
		- "Coupon Discount" places the discount in a Plugin-specific Woo coupon
		- "Unit Cost Discount" is the new default
		
* Enhancement - Added Settings Switch to show *Catalog Price discount suffix*, with wildcards.
		So you can represent "Save xx" 
		by putting in "Save {price_save_percent} or {price_save_amount}" 
		and the plugin will automatically fill in the saved percentage as "25%".

* Fix - For Catalog Rules, price crossout for variable products now fully controlled
		using Settings switch

= 1.0.9.2 - 2015-01-23 =
* Fix - Release Rollback - A small but significant number of clients continue to have
		issues with release v 1.0.9.0 and fix release 1.0.9.1 . Rather than
		leaving users with issues while a fix is being identified,  
		Release 1.0.9.2 rolls all the code back to v1.0.8.9, 
		prior to the code changes and the issues
		these customers are experiencing.

= 1.0.9.1 - 2015-01-23 =
* Fix - pricing issue - for some installations, no discounts made it to checkout. Fixed.

= 1.0.9.0 - 2015-01-22 =
* Enhancement - Added Settings Switch to SHOW DISCOUNT AS:
		**COUPON DISCOUNT** or 
		**UNIT COST DISCOUNT**
* Enhancement - Added Settings Switch to show *Catalog Price discount suffix*, with wildcards.
		So you can represent "Save xx" by putting in "Save {price_save_percent} {price_save_amount}" 
		and the plugin will automatically fill in the saved percentage as "25%".
 

= 1.0.8.9 - 2014-11-11 =
* Fix - pricing issue - doing_ajax
* Fix - pricing issue - login on the fly at checkout
* Fix - is_taxable Issue
* Fix - Product-level rule include/exclude list
* Enhancement - Shortcode Standard version now produces messages 'in the loop' only 
		when matching the product information 
* Enhancement - Shortcode Standard version now sorts msgs based on request
* Fix - 'excluding taxable' option in subtotal reporting.
* Fix - 'cheapest/most expensive' discount type sometimes would not remain selected - JS.
 

= 1.0.8.8 - 2014-10-19 =
* Enhancement - Added "Wholesale Tax Free" Role.  Added "buy_tax_free" Role Capability.
		Now **Any** User logged in with a role with the "buy_tax_free" Role Capability 
		will have 0 tax applied
		And the tax-free status will apply to the **Role**, regardless of whether a deal is currently active!!

    		**************************************** 
    		**Setup needed - Requires the addition of a  "Zero Rate Rates" tax class in the wp-admin back end 
    		*****************************************     
    		*(1) go to Woocommerce/Settings
    		*(2) Select (click on) the 'Tax' tab at the top of the page
    		*(3) You will then see, just below the tabs, the line     
    		    "Tax Options | Standard Rates | Reduced Rate Rates | Zero Rate Rates (or Exempt from Vat)" 
    		*(4) Select (click on) "Zero Rate Rates (or Exempt from Vat) " 
    		*(5) Then at the bottom left, click on 'insert row' .  
    		* Done.
    		* 
* Fix - Crossout original value in Catalog discount, in a rare situation


= 1.0.8.7 - 2014-09-04 =
* Fix - Rare Discount by each counting issue
* Fix - Onsale Switch for Catalog Rules

= 1.0.8.6 - 2014-08-16 =
* Fix - Rare variation categories list issue
* Enhancement - Variation Attributes

= 1.0.8.5 - 2014-08-13 =
* Enhancement - Coupon Title 'deals' translated via filter - see languages/translation directions.txt 
* Fix - Variation taxable status

= 1.0.8.4 - 2014-08-6 =
* Enhancement - Pick up User Login and apply to Cart realtime 
* Enhancement - Upgraded discount exclusion for pricing tiers, when "Discount Applies to ALL" 
* Enhancement - Pick up admin changes to Catalog rules realtime for all customers
* Fix - JS and/or initialization on Group

= 1.0.8.3 - 2014-08-3 =
* Fix - "Apply to All" rare issue 

= 1.0.8.2 - 2014-07-30 =
* Fix - Auto Insert free product name in discount reporting
* Fix - Fine-tune Admin resources

= 1.0.8.1 - 2014-07-27 =
* Fix - Refactored "Discount This" limits
	If 'Buy Something, Discount This Item' is selected,
	Discount Group Amount is now *an absolute amount* of units/$$ applied to
	working with the Discount Group Repeat amount 

= 1.0.8.0 - 2014-07-25 =
* Fix - Customer Limits
* Enhancement - Settings System Buttons

= 1.0.7.9 - 2014-07-21 =
* Enhancement - Custom Variation Usage
* Enhancement - Variation Reporting in receipts
* Enhancement - Woo Customer tax exempt

= 1.0.7.8 - 2014-07-15 =
* Fix - variation usage  ...

= 1.0.7.7 - 2014-07-03 =
* Fix - backwards compatability:: if pre woo 2.1 ...

= 1.0.7.6 - 2014-06-30 =
* Enhancement - Group Pricing math
* Enhancement - Percentage discount now defaults to 'all in group'
* Enhancement - Package Pricing now defaults to currency

= 1.0.7.5 - 2014-06-27 =
* Enhancement - backwards compatability
* Fix - mini-cart discount subtotal excluding tax
* Enhancement - rule schedule default - "on always"

= 1.0.7.4 - 2014-06-19 =
* Enhancement - use WC  coupon routine
* Enhancement - VAT pricing - include Woo wildcard in suffix text
* Enhancement - Taxation messaging as needed in checkout
* Enhancement - Auto add 'Wholesale Buyer' role on install
* Enhancement - Coupon Individual_use lockout
* Fix - PHP floating point rounding

= 1.0.7.3 - 2014-06-05 =
* Fix - post-purchase processing
* Fix - intermittent issue with variable product name 
* Fix - use_lifetime_max_limits defaults to 'yes'

= 1.0.7.2 - 2014-05-29 =
* Fix - Package Pricing in same group 
* Fix - Settings update repair
* Fix - update show help functions
* Fix - user role change in cart discount
* Fix - apply rule free catalog product issue repaired
* Fix - group pricing rounding issue

= 1.0.7.1 - 2014-5-23 =
* Enhancement - Admin improvements
* Fix - Include/Exclude box on Product wp-admin screen
* Fix - Cart Updated  woocommerce addressability issue

= 1.0.7 - 2014-5-14 =
* Fix - Include 'price display suffix' in Catalog pricing, as needed
* Enhancement - Pro version check from Free version

= 1.0.6 - 2014-5-10 =
* Enhancement - VAT pricing uses regular_price first, but if empty, looks at _price.

= 1.0.5 - 2014-5-08 =
* Fix -VAT inclusive for Cart pricing
* Fix -Warnings and move vtprd_debug_options to functions
* Enhancement - hook added for additional population logic
* Fix -$product_variations_list

= 1.0.4 - 2014-5-01 =
* Fix - if BCMATH not installed with PHP by host, replacement functions
* Fix - add in missing close comment above function in parent-cart-validation.php
* Fix - framework, removed (future) upcharge... , fix pricing-type-simple for catalog
* Fix - framework, pricing-type discount by catalog Option renamed
* Fix - js for cart simple discount was disallowing discount limits in error

= 1.0.3 - 2014-04-26 =
* Fix - warnings on apply
* Fix - cartWidget print option corrected
* Fix - Discount Group repeat logic
* Enhancement - e_notices made switchable, based on 'Test Debugging Mode Turned On' settings switch
* Enhancement - debugging_mode output to error log
* Change - cumulativeSalePricing switch default now = 'Yes - Apply Discount to Product Price, even if On Sale' - UI + JS chg

= 1.0.2 - 2014-04-14 =
* Fix - warnings on UI update error
* Enhancement - improved edit error msgs in UI
* Fix - Change to collation syntax on install
* Fix - shortcode role 'notLoggedIn'

= 1.0.1 - 2014-04-10 =
* Fix - warning on install in front end if no rule
* Fix - removed red notices to change host timezone on install
* Fix - removed deprecated WOO hook
* Fix - BOGO 'discount this' fix
* Fix - replace bcdiv with round
* Fix - BOGO missing method in free apply
* Enhancement - reformatted the rule screen, hover help now applies to Label, rather than data field 

= 1.0 - 2014-03-15 =
* Initial Public Release


== Upgrade Notice ==

= 2.0.0.5 - 2019-01-22 =
* Fix - On the Pricing Deals Settings page, in Coupon Discount mode, the "Cart Cross-Rule Limits" settings were having an issue, now repaired.
* Enhancement - For Pricing Deals Settings page -- Show Discount as Unit Price Discount or Coupon Discount 
           	If "Coupon Discount" selected, the 
           	** single automatic coupon name showing the discounting **  
           	can be custom named in the new "Automatic Coupon Name" field directly below 
* Enhancement - If rule discount is activated by a WOO coupon ("Discount Coupon Code"), the WOO coupon is now
           	automatically created as well. 
* Enhancement - In the front end, if rule discount is activated by a WOO coupon , the WOO coupon code shows
           	in a "coupon code ... 00" cart totals line.
           	There is now a switch to prevent the "...00" line showing in the front end.
           	On the Pricing Deals Settings page, look for:
		-- If rule Activated by Coupon Code - show the "coupon code ... 00"    Cart totals line"
		--- To prevent this line from showing in the front end, set to 'No'
* Enhancement - Allow additional product types from additional plugins

         	If added PRODUCT type from additional Plugins needed
                   Find all the Product types needed in your additional plugins, by searching for: "register_post_type".
                   In the "return" statement below, string them together as the example suggests
           
        	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
        	add_filter( 'vtprd_use_additional_product_type', function() { return (array('product-type1','product-type2')); } ); 
          
                   THIS FILTER will add your added PRODUCT type to BOTH the PRODUCT selector AND the Pricing Deal Category selector
                   - so if you want a group of products to be included in a rule, you can either list them in the PRODUCT selector,
                   or make sure they participate in a Pricing Deal Category, which is then selected in your desired rule.

= 2.0.0.3 - 2018-12-05 =
* Fix - Javascript issue in Rules screen, where Groups and Memberships were not displaying correctly, now repaired
* Enhancement - Pricing Deals Rule screen, remove additional excess Javascripts.
* Fix - Copy to Support button now supplies Bulk rule info correctly

= 2.0.0.2 - 2018-11-11 =
* Fully compatible with: WordPress 5.0+, Gutenberg, WooCommerce 3.5+, PHP 7.2
* Fix - Code changes to support PHP 7.2
* Enhancement - Updates to accomodate Wordpress 5.0
* Enhancement - Pricing Deals now removes a lot of excess Javascripts.
           	If you are editing a rule and having trouble with the Pricing Deals RULE 'Date Picker':
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	add_filter( 'vtprd_trouble_with_date_picker', function() { return TRUE; } ); 
* Fix - Package Pricing - for the Price of $$ amount error fixed
* Fix - Package Pricing - allow 'Each Product' selection on Qualify Group, Group Amount Applies to
          	allowing each product to process separately as its own qualify group...
* Fix - Shortcodes now all react appropriately to begin/end dates

= 2.0.0.1 - 2018-07-05 =
* Fix - In some sites, rules were not executed when the free version was installed solely.  Issue repaired.

= 2.0.0 - 2018-07-01 =
* Enhancement - RULE SCREEN REWRITE - Rewrite of the way to choose a Category, Product, etc in a rule - now using Select2.
          	Added Group Choice by Customer (email and name), Brands and Groups. 
          	Each group choice will have an include/exclude option.

          	If using a BRANDS plugin not in the supported list, add support by doing the following:
          	//add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	//change [brands plugin taxonomy] to the brands taxonomy of your brands plugin   
          	add_filter( 'vtprd_add_brands_taxonomy', function() { return  'brands plugin taxonomy'; } );
* Enhancement - New "Apply Discount to Equal or Lesser Value Item"
                      //--------------------------------------------------------------------                
                      // Discount Equal or Lesser Value Item first
                      //- Discount the item(s) in the Discount Group of equal or lesser value to the most expensive item in the Qualify Group    
                      //--------------------------------------------------------------------   
* Enhancement - New 'Copy to Support'button, for 1-click copying of rule for support emails 
* Enhancement - Removed Include/Exclude Box on Product screen.  All existing include/exclude converted into new rule structures.
          	Report documeting all original include/exclude settings available:
          	Ex: http://[your website name]/wp-admin/edit.php?post_type=vtprd-rule&page=vtprd_show_help_page&doThis=reportInclExclV2.0.0  
* Enhancement - Deal activation by Woo coupon and auto add for free function now works together! 
* Fix - Compatible again with Gravity Forms plugin.  
		Gravity Forms plugin (v2.3.2+)  interferes with the wordpress is-admin() conditional, 
			making Gravity Forms incompatible with Pricing Deals.  
		Replaced is-admin() conditional with REQUEST_URI test, where required for compatibility.  

= 1.1.8.0 - 2017-10-21 =
* Enhancement - Bulk Processing in a single rule!  Use the Bulk option for simple deals as well! 
* Enhancement - CART Discount reporting in Order History (very basic formatting, will improve soon)
* Fix - at update time, had a rare issue when an active rule had auto add for free.
* Fix - for the 'get variations' button in Rule UI product filter 

= 1.1.7.2 - 2017-10-10 =
* Enhancement - Prevent Pricing Deals and Pricing Deals Pro from background auto-updating.  These plugins must always
		These plugins must be updated *only* by and an admin click in response to an update nag ! 
* Enhancement - Show WOO sales badge if shop product discounted by CATALOG rule discount
          	//to TURN OFF this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file  
          	add_filter( 'vtprd_show_catalog_deal_sale_badge', function() { return  FALSE; } );  
* Fix - by Variation Name across Products now also applies to CATALOG rules
* Enhancement - Allow multiple coupons in coupon mode
          	//to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file
          	add_filter( 'vtprd_allow_multiple_coupons_in_coupon_mode', function() { return TRUE; } ); 
* Enhancement - By Role now tests against all roles user participates in (primary and secondary) 
* Fix - by Variation Name across Products in the Discount Group now saving name correctly.
* Enhancement - New Filter to Allow full discount reporting on customer emails for Units discounting
          	//to TURN ON this new action, add the 'add_filter...' statement to your theme/child-theme functions.php file 
          	add_filter( 'vtprd_always_show_email_discount_table', function() { return TRUE; } );
* Fix - VTPRD_PURCHASE_LOG definition changed, 2 columns now Longtext.
* Fix - Various Woocommerce 3.0, 3.12 and 3.2 log warnings resolved. 
* Enhancement - Limit Cart Rule Discounting to a Single Rule or Rule type
          	new setting: - Pricing Deal Settings Page => 'Cart Cross-Rule Limits'
* Enhancement - New Filter vtprd_cumulativeRulePricing_custom_criteria.  Allows custom control of rule interaction.
          	Using this filter, create your own custom function to manage Rule interaction
          	(folow the example for using the 'vtprd_additional_inpop_include_criteria' in the PRO version apply-rules.php)

= 1.1.7.1 - 2017-05-26 =
* Enhancement - In the Group Product Filter, now select 
		by Variation Name across Products ! 
		Example: Apply a discount across all 'large' size shirts
* Enhancement - FOR Cart rule with 'Buy amount applies to' = EAch, and discount group same as Qualify Group,
		process EACH product matching the choice criteria INDIVIDUALLY.
		  *NOTE* if 'Qualify Group RULE USAGE COUNT' = apply rule once per cart, the rule will be applied
		  **once per product**
* Enhancement - Filter to allow page Refresh of the CART page after an AJAX update 
   		Valid Values for FILTER:
  		    CouponOnly - only send JS on Cart Page when an existing rule is actuated by a Coupon
  		    Never - never send the JS on Cart Page [DEFAULT] 
 		    Always - always on Cart Page      
            	//Be sure to clear the cache and start a fresh browser session when testing this...
            	function js_trigger_cart_page_reload() {
           		 return 'Never';  //valid values: 'CouponOnly' / 'Never' / 'Always'
          	}
          	add_filter('vtprd_js_trigger_cart_page_reload', 'js_trigger_cart_page_reload', 10);

          	//Alternative: same solution with less code, no additional function:
          	add_filter( 'vtprd_js_trigger_cart_page_reload', function() { return  'Never'; } );  //valid values: 'CouponOnly' / 'Never' / 'Always'
* Fix - Remove warnings on coupon use
* Fix - IF auto add to cart granted and user logs in, correct auto added product count will be maintained.

= 1.1.7 - 2017-04-03 =
* Enhancement - Updates to accomodate Woocommerce 3.0
* Enhancement - New 'ex. VAT' filter - 'vtprd_replace_ex_vat_label'
* Change -  (due to change in Woocommerce 3.0)
    	    If you choose to show the Pricing Deals discount via an auto-inserted Coupon, and
    	    you want  translate/change the name of  the 'Deals' title of the auto-inserted "Coupon: Deals", 
		1. ADD the following wordpress filter:
      		// Sample filter execution ==>>  put into your theme's functions.php file (at the BOTTOM is best), so it's not affected by plugin updates
         	 function coupon_code_discount_title() {
           		 return 'different coupon title';  //new coupon title
          	}
          	add_filter('vtprd_coupon_code_discount_title', 'coupon_code_discount_title', 10); 

		**New**
		2. ALSO ADD a new Woocommerce Coupon in wp-admin/woocommerce/coupons
		  Required Coupon Attributes:
   			Coupon Code => coupon title from (1) above
   			Coupon Type => Fixed Cart Discount
   			Coupon Amount => 0

= 1.1.6 - 2016-06-15 =
* Fix - Added warning for invalid Client website IP address
* Fix - minor registration issues
* Enhancement - Now allow ANY staging.wpengine site as part of a test site registration

= 1.1.5 - 2016-06-05 =
* Fix - Added code to handle the rare "Fatal error: Call to a member function get_tax_class() ..."
* Enhancement - At store page refresh, catch any price changes and refigure discount
* Enhancement - Now with FREE PRO demo available, PRO licensing included.

= 1.1.1.2 - 2015-11-07 =
* Fix - Coupon discount mini-cart intermittent display issue on 1st time 
* Enhancement - Formerly, only a single "auto add for free" rule was allowed.
		Now multiple "auto add for free" rules is fully supported. 

= 1.1.1 - 2015-09-26 =
* Enhancement - Now Compatible with Woocommerce Measurement Price Calculator (Woocommerce + Skyverge). 
* Enhancement - Now Compatible with Woocommerce Product Addons (Woocommerce). 
* Enhancement - 'Cheapest in the cart' - see 'cheapest in cart filter' txt file in pro .
* Fix - Other rule discounts = no
* Fix - improve efficiency for Rule Discounts activated by Coupon
* Fix - variation discount pricing display
* Fix - shortcode in-the-loop product messaging
* Fix - discount and sale price scheduling
* Fix - fix for variation pricing for variation groups larger than 20, Catalog rules discount
* Enhancement - New Filter to enable Pricing Deals to pick up pricing from other plugins 
    
		     // *** add to bottom of Theme Functions file
 		     //allows Pricing Deals to pick up current product pricing from other plugins
 		     //  ---  more resource intensive  ---
     
		    add_filter('vtprd_do_compatability_pricing', 'do_compatability_pricing', 10, 1); 
 		    function do_compatability_pricing ($return_status) {
 		     return true;
		    }
* Enhancement - Catalog Products Purchasability Display (pro):
		- ** Gives you the ability to control Product Purchasability
		- ** You can even turn your Woocommerce Store into a Catalog-only Installation!
		- Product screen now has a 'wholesale product' checkbox in the PUBLISH box
			- Label all wholesale products as wholesale
		- Settings Page now has "Catalog Products Purchasability Display"
			- Choose the Retail/Wholesale display option you want
		- Then as each Retail or Wholesale Capability user logs in, they will see
			- a tailored list (Not logged in = Retail)
* Enhancement - Wholesale Product Visibility (pro):
		- new option - Show All Products to Retail, Wholesale Products to Wholesale	
* Note - Now recommend "Members" plugin by Justin Tadlock, rather than User Role Editor

= 1.1.0.9 - 2015-07-31 =
* Fix - Other rule discounts = no
* Fix - improve efficiency for Rule Discounts activated by Coupon

= 1.1.0.8 - 2015-07-25 =
* Fix - Wp-admin Rule editing - if advanced field in error and basic rule showing, 
	switch to advanced to expose errored field. 
* Fix - fix to user tax exempt status - saved to user updated, not user making the update!
* Enhancement - New Advanced Rule Option - Rule Discount applies only 
			when a specific Coupon Code is redeemed for the cart:
		- Coupon code is entered in the Pricing Deals Rule in the Discount box area (opotional!)
		- The rule discount will not activate in the Cart for a client purchase, 
			until the correct coupon code is presented.
		- Best to use a coupon set to 'Cart Discount' and 'coupon amount' = 0.

= 1.1.0.7 - 2015-07-21 =
* Fix - User screen tax exempt flag. 
* Fix - "Discount applied to list price, taken if it is less than sale price" now works with Catalog rules also. 
* Fix - buy_tax_free capability applied globally...
* Enhancement - Wholesale Product Visibility (pro):
		- Product screen now has a 'wholesale product' checkbox in the PUBLISH box
			- Label all wholesale products as wholesale
		- Settings Page now has "Wholesale Products Display Options"
			- Choose the Retail/Wholesale display option you want
		- Then as each Retail or Wholesale Capability user logs in, they will see
			- a tailored list (Not logged in = Retail)
		- NOTE when testing, use the Members plugin to control the new 'wholesale' capability!			
* Note - Now recommend Members plugin by Justin Tadlock, rather than User Role Editor

= 1.1.0.6 - 2015-07-07 =
* Fix - Auto add free item function. 
* Enhancement - Auto add free item function:
		- Can now add multiple free items using the Discount Group Amount count.
		- New Filter ==> $0 Price shown as 'Free' unless overridden by filter:
			add_filter('vtprd_show_zero_price_as_free',FALSE); 
			(in your theme's functions.php file)

= 1.1.0.5 - 2015-05-22 =
* Fix - Older email clients dropping strikethrough, added css strikethrough
* Fix - Obscure PHP computation issue (floating point comparison)
* Enhancement - New Template Tag
		vtprd_the_discount() ==> Show the formatted total discount
		Template code: if ( vtprd_the_discount() ) { echo vtprd_the_discount();}
* Enhancement - Shortcode ==> pricing_deal_msgs_standard
		new functionality
		Sample template code:
      			$product_id = get_the_ID();
      			echo do_shortcode( '[pricing_deal_msgs_standard  
						force_in_the_loop="yes"  
						force_in_the_loop_product="'.$product_id.'"]');
* Enhancement - Cleanup if last rule deleted (admin/..rules-delete...)

= 1.1.0.4 - 2015-05-01 =
* Fix - Sale Price Discount exclusion switch issue resolved

= 1.1.0.3 - 2015-04-28 =
* Enhancement - Unit Price Discount subtotal crossouts now on Checkout and Thankyou pages,
	and also on Customer Email.

= 1.1.0.2 - 2015-04-25 =
* Fix - Woo Points and Rewards + regular coupons

= 1.1.0.1 - 2015-04-23 =
* Fix - Compatability issue with other Coupon-based plugins resolved,
	in particular Woo Points and Rewards
* Enhancement - New notification of mismatch between Free and Pro versions

= 1.1 - 2015-04-19 =
* Enhancement - In the Qualify Group Filter, added Logged-in Role to Single product and single product with variations:
	By Single Product with Variations   (+ Logged-in Role) 
	By Single Product    (+ Logged-in Role)          

= 1.0.9.6 - 2015-04-14 =
* Fix - Catalog rule variation discounts (from-to) changed to only show a single price, when all
	variation prices are the same.

= 1.0.9.5 - 2015-04-11 =
* Fix - Widget Catalog discount pricing for variations had an issue.
* Fix - Variation Catalog Discount pricing showing least-to-most expensive had an issue 
	when the actual variations were not in ascending sequence by price.  
	Now sorted for least/most expensive.
* Fix - Different decimal separator for Unit Price discount crossout value in cart/mini-cart.

= 1.0.9.4 - 2015-04-10 =
* Fix - Cart issue if only Catalog discount used, now fixed.

= 1.0.9.3 - 2015-04-09 =
* Enhancement - Redux - Added **Settings Switches** to SHOW DISCOUNT AS:
		**UNIT COST DISCOUNT** or **COUPON DISCOUNT**
		- "Unit Cost Discount" discounts the unit price in the cart immediately
			- Old price crossed out, followed by discounted price is the default
			- can show discount computation for testing purposes
		- "Coupon Discount" places the discount in a Plugin-specific Woo coupon
		- "Unit Cost Discount" is the new default
		
* Enhancement - Added Settings Switch to show *Catalog Price discount suffix*, with wildcards.
		So you can represent "Save xx" 
		by putting in "Save {price_save_percent} or {price_save_amount}" 
		and the plugin will automatically fill in the saved percentage as "25%".

* Fix - For Catalog Rules, price crossout for variable products now fully controlled
		using Settings switch

= 1.0.9.2 - 2015-01-23 =
* Fix - Release Rollback - A small but significant number of clients continue to have
		issues with release v 1.0.9.0 and fix release 1.0.9.1 . Rather than
		leaving users with issues while a fix is being identified,  
		Release 1.0.9.2 rolls all the code back to v1.0.8.9, 
		prior to the code changes and the issues
		these customers are experiencing.

= 1.0.9.1 - 2015-01-23 =
* Fix - pricing issue - for some installations, no discounts made it to checkout. Fixed.

= 1.0.9.0 - 2015-01-22 =
* Enhancement - Added Settings Switch to SHOW DISCOUNT AS:
		**COUPON DISCOUNT** or 
		**UNIT COST DISCOUNT**
* Enhancement - Added Settings Switch to show *Catalog Price discount suffix*, with wildcards.
		So you can represent "Save xx" by putting in "Save {price_save_percent} {price_save_amount}" 
		and the plugin will automatically fill in the saved percentage as "25%".
	and the plugin will automatically fill in the saved percentage as "25%".
 
= 1.0.8.9 - 2014-11-11 =
* Fix - pricing issue - doing_ajax
* Fix - pricing issue - login on the fly at checkout
* Fix - is_taxable Issue
* Fix - Product-level rule include/exclude list
* Enhancement - Shortcode Standard version now produces messages 'in the loop' only 
		when matching the product information 
* Enhancement - Shortcode Standard version now sorts msgs based on request
* Fix - 'excluding taxable' option in subtotal reporting.
* Fix - 'cheapest/most expensive' discount type sometimes would not remain selected - JS.


= 1.0.8.8 - 2014-10-19 =
* Enhancement - Added "Wholesale Tax Free" Role.  Added "buy_tax_free" Role Capability.
		Now **Any** User logged in with a role with the "buy_tax_free" Role Capability 
		will have 0 tax applied
		And the tax-free status will apply to the **Role**, regardless of whether a deal is currently active!!

    		**************************************** 
    		**Setup needed - Requires the addition of a  "Zero Rate Rates" tax class in the wp-admin back end 
    		*****************************************     
    		*(1) go to Woocommerce/Settings
    		*(2) Select (click on) the 'Tax' tab at the top of the page
    		*(3) You will then see, just below the tabs, the line     
    		    "Tax Options | Standard Rates | Reduced Rate Rates | Zero Rate Rates (or Exempt from Vat)" 
    		*(4) Select (click on) "Zero Rate Rates (or Exempt from Vat) " 
    		*(5) Then at the bottom left, click on 'insert row' .  
    		* Done.
    		* 
* Fix - Crossout original value in Catalog discount, in a rare situation

= 1.0.8.7 - 2014-09-04 =
* Fix - Rare Discount by each counting issue
* Fix - Onsale Switch for Catalog Rules

= 1.0.8.6 - 2014-08-16 =
* Fix - Rare variation categories list issue
* Enhancement - Variation Attributes

= 1.0.8.5 - 2014-08-13 =
* Enhancement - Coupon Title 'deals' translated via filter - see languages/translation directions.txt 
* Fix - Variation taxable status

= 1.0.8.4 - 2014-08-6 =
* Enhancement - Pick up User Login and apply to Cart realtime 
* Enhancement - Upgraded discount exclusion for pricing tiers, when "Discount Applies to ALL" 
* Enhancement - Pick up admin changes to Catalog rules realtime for all customers
* Fix - JS and/or initialization on Group

= 1.0.8.3 - 2014-08-3 =
* Fix - "Apply to All" rare issue 

= 1.0.8.2 - 2014-07-30 =
* Fix - Auto Insert free product name in discount reporting
* Fix - Fine-tune Admin resources

= 1.0.8.1 - 2014-07-27 =
* Fix - Refactored "Discount This" limits
	If 'Buy Something, Discount This Item' is selected,
	Discount Group Amount is now *an absolute amount* of units/$$ applied to
	working with the Discount Group Repeat amount 

= 1.0.8.0 - 2014-07-25 =
* Fix - Customer Limits
* Enhancement - Settings System Buttons

= 1.0.7.9 - 2014-07-21 =
* Enhancement - Custom Variation Usage
* Enhancement - Variation Reporting in receipts
* Enhancement - Woo Customer tax exempt

= 1.0.7.8 - 2014-07-15 =
* Fix - variation usage  ...

= 1.0.7.7 - 2014-07-03 =
* Fix - backwards compatability:: if pre woo 2.1 ...

= 1.0.7.6 - 2014-06-30 =
* Enhancement - Group Pricing math
* Enhancement - Percentage discount now defaults to 'all in group'
* Enhancement - Package Pricing now defaults to currency

= 1.0.7.5 - 2014-06-27 =
* Enhancement - backwards compatability
* Fix - mini-cart discount subtotal excluding tax
* Enhancement - rule schedule default - "on always"

= 1.0.7.4 - 2014-06-19 =
* Enhancement - use WC  coupon routine
* Enhancement - VAT pricing - include Woo wildcard in suffix text
* Enhancement - Taxation messaging as needed in checkout
* Enhancement - Auto add 'Wholesale Buyer' role on install
* Enhancement - Coupon Individual_use lockout
* Fix - PHP floating point rounding

= 1.0.7.3 - 2014-06-05 =
* Fix - post-purchase processing
* Fix - intermittent issue with variable product name 
* Fix - use_lifetime_max_limits defaults to 'yes'

= 1.0.7.2 - 2014-05-27 =
* Fix - Package Pricing in same group 
* Fix - Settings update repair
* Fix - update show help functions
* Fix - user role change in cart discount
* Fix - apply rule free catalog product issue repaired

= 1.0.7.1 - 2014-5-23 =
* Fix - Include/Exclude box on Product wp-admin screen
* Fix - Cart Updated woocommerce addressability issue

= 1.0.7 - 2014-5-14 =
* Fix - Include price display suffix in Catalog pricing, as needed
* Enhancement - Pro version check from Free version

= 1.0.6 - 2014-5-10 =
* Fix -VAT pricing uses regular_price first, but if empty, looks at _price.

= 1.0.5 - 2014-5-08 =
* Fix -VAT inclusive for Cart pricing
* Fix -Warnings fix
* Enhancement - hook added for additional population logic
* Fix -$product_variations_list fix

= 1.0.4 - 2014-05-01 =
* Fix - if BCMATH not installed with PHP by host, replacement functions
* Fix - add in missing close comment above function in parent-cart-validation.php
* Fix - framework, removed (future) upcharge... , fix pricing-type-simple for catalog
* Fix - framework, pricing-type discount by catalog Option renamed
* Fix - js for cart simple discount was disallowing discount limiits in error

= 1.0.3 - 2014-04-26 =
* Fix - warnings on apply
* Fix - cartWidget print option corrected
* Fix - Discount Group repeat logic
* Enhancement - e_notices made switchable, based on 'Test Debugging Mode Turned On' settings switch
* Enhancement - debugging_mode output to error log
* Change - cumulativeSalePricing switch default now = 'Yes - Apply Discount to Product Price, even if On Sale' - UI + JS chg

= 1.0.2 - 2014-04-14 =
* Fix - warnings on UI update error
* Enhancement - improved edit error msgs in UI
* Fix - Change to collation syntax on install
* Fix - shortcode role 'notLoggedIn'

= 1.0.1 - 2014-04-10 =
* Fix - warning on install in front end if no rule
* Fix - removed red notices to change host timezone on install
* Fix - removed deprecated WOO hook
* Fix - BOGO 'discount this' fix
* Fix - replace bcdiv with round
* Fix - BOGO missing method in free apply
* Enhancement - reformatted the rule screen, hover help now applies to Label, rather than data field 

= 1.0 - 2014-03-15 =
* Initial Public Release