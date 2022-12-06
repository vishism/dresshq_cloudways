=== Remarkety - eCommerce Marketing Automation Platform for WooCommerce ===

Contributors: remarkety
Tags: WordPress.com, marketing, woocommerce, woocommerce emails, abandoned cart, email automation, ecommerce, emails, email marketing, campaigns, drip, email builder, email marketing, manage subscribers, newsletter, subscribers, subscription, targeted emails,
Requires at least: 3.0.1
Tested up to: 5.7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Send intelligent emails based on customer purchase history. Recover abandoned carts, send targeted newsletters and more. Free Trial!

== Description ==

About Remarkety ‑ Email, SMS, Social

= Marketing Automation That Works For You = 

An easy-to-use platform for eCommerce Email Marketing Automation. Purpose-built for eCommerce, Remarkety draws on shopping behavior to segment customers and apply predictive algorithms. It gives you the tools you need to create & deliver highly captivating, data-driven campaigns that propel customers to action and drive revenue. Your revenue growth is a click away with Remarkety. Integration is quick and easy.

Do you want to completely automate and personalize your Email and SMS marketing without dedicated analysts or expensive software? Meet Remarkety!

Using our integrations, rich customer data, predictive product recommendations and dynamic coupon capabilities, you can craft hyper-personalized messages that reach your shoppers at the right time with the right content.


= Easily create top performing campaigns =

*   Cart Recovery: Send beautiful, detailed cart recovery emails & SMS with product images, upsell recommendations and time-limited coupons to create a sense of urgency.
*   Browse Recovery: Send emails & SMS based on customers who browsed your products, but haven't added them to a cart yet
*   Customer Reactivation: Re-engage your one-off and repeat customers which haven't shown up in a while
*   Add our “email capture booster” to your site and collect more email addresses very early on in the customer journey, without popups!
*   Product Replenishment: Remind your customers to restock with repeat purchases before they start shopping someplace else
*   Order followup: Develop deeper relationships with your customers by following up with relevant content based on what they actually bought
*   And more !


= Key Features =

*   Get started in minutes with our built-in, proven segments and templates
*   Tailored marketing recommendations to help you effectively manage your priorities
*   Send newsletters and SMS blasts to hyper segmented, targeted audiences
*   Use our intuitive segment builder to fine-tune your audiences based on their purchase history (incl. product categories), using historic and real-time data
*   Create more effective Facebook & Instagram campaigns by syncing Remarkety segments with FB Custom Audiences
*   Automatically create WooCommerce  coupons with unique, single-use discount codes
*   Completely customizable, AI-based predictive product recommendations
*   Detailed reporting on every campaign
*   Reliable inbox deliverability - Ensure maximum exposure for your content and know your message will reach the recipient’s inbox with our 99% delivery rate.


= Integrates with =

*   yotpo
*   smile.io
*   zapier
*   privy
*   facebook
*   Wheel.io


== Installation ==

1. Install Remarkety either via the WordPress.org plugin directory, or by uploading the files to your server.
2. After activating Remarkety, [register](https://app.remarkety.com) for free at https://app.remarkety.com
3. Copy/paste the security API key from your Remarkety plugin to your Remarkety new account and save it.
4. That's it.  You're ready to go!


== Frequently Asked Questions ==

 = How does Remarkety recommend email campaigns I should send to my customers? =

We analyze your store’s data and purchase history to find opportunities, such as inactive customers, or customers who have spent more than a certain amount, etc.

 = Will Remarkety send messages to my customers automatically? =

You have full control over what is sent and when. But once you set the marketing activity, we will do all the work for you and send messages to the right customer at the right time, automatically.

 = I am not a marketing expert. How can I know how to write a message or when to send it? =

With Remarkety you don’t have to be an expert. We recommend the best timing, the best wording, the best templates based on best practices from thousands of online stores just like yours.


 = How would I get Remarkety’s recommendations? =

There are two ways to get the recommendation:

Simply follow Remarkety recommendation on the main dashboard.
Once a week you will receive an email from us with a recommendation that was generated for your store.

 = What types of emails will you send to my customers? =

We analyze your online store’s products, sales, and customers’ purchase history. Based on this analysis we recommend a variety of marketing activities, such as: Personal product recommendations, cart abandonment recovery, personal coupons, wake up call to inactive customers, order follow-up emails, feedback request and more.


 = Will my customers open these emails? =

You bet! Researches shows, and our internal statistics confirms it, that stores that took advantage of automation and personalization improved conversions by up to 50%.


 = I want to make sure that the emails looks fine before sending them to my customers. What are my options? =

Remarkety offers three ways to control your emails:

1. After setting the email text, click on "Preview" to see how the email will look like with real data from your store.
1. From the email editing page you can send a test email to a different email address to see how the various email clients will render it.
1. Start your activity in test mode. All emails will be sent normally, only they will be sent to your email address and not your customers' email address.


 = How will Remarkety help me comply with regulation?  =

Remarkety will not allow you to run a marketing activity if the email text does not contain an {unsubscribe} tag. In addition, Remarkety will never send an email to a customer who asked not to receive emails from your store.



 = How can I be sure that I'm not bugging my customers with too many emails?  =

Remarkety carefully monitors the amount of emails that your customers receive from you. So if a customer has received an email in the past number of days we will not send another email unless it is extremely “important”.


 = How much does Remarkety cost?  =

Remarkety offers a free 14-day trial. After the trial, pricing is based on the size of your contact list. [See pricing here](http://www.remarkety.com/pricing).

== Screenshots ==

1. Drag & Drop email template editor
2. Personal email example
3. Remarkety dashboard
4. Abandoned cart email example
5. Email campaign recommendation
6. Marketing reports
7. Track results
8. Product recommendation in emails
9. Easy customer segmentation

== Changelog ==

1.4.2
Security fix: More validation added to data stored in the remarkety_carts_guests table. 
Thank you Joshua and Dylan from beyondthebrandmedia.com for reporting this!

1.4.1
Allow Remarkety to remotely update the public store id for configuring the client-side tracking.

1.4.0
New feature: Cross device cart recovery links

1.3.1
New feature: Ability to add “marketing allowed” checkbox automatically to checkout pages, and allow users to explicitly opt-in to email and SMS marketing.

1.3.0
Email popup feature and cart sending on ajax added_to_cart request

1.2.20
WooCommerce REST API - added filtering on last modification date

1.2.19
Remove unused parameter from products url

1.2.18
Auth fix

1.2.17
Fix webhooks issue (requires user id to create the webhook payload)

1.2.16
Products API should include inactive variations so Remarkety could disable them

1.2.15
Webhooks list and delete api use WC api

1.2.14
Update webhook registration

1.2.12
Client-side tracking script loaded from CDN

1.2.11
Bugfix - remove email field from table if user not logged in

1.2.10
Added filters for modifying the customer data before sending to Remarkety

1.2.9
Bugfix - coupon creation date timezone

1.2.8
Bugfix - skip product info for deleted products

1.2.7
Release date December 10th 2017
Fix shipping amounts on carts with older WC versions

1.2.5
Release date December 7th 2017
Support shipping amount on carts

1.2.0
Release date October 4th 2017
* Support cart recovery from checkout page

1.1.28
Release date September 5 2017
* Use user role as a customer group in Remarkety

1.1.27
Release date August 22 2017
* Personalized coupons - Add support for excluding/including specific categories
* Personalized coupons - Add support for excluding items on sale

1.1.26
Release date July 2 2017
* Support "product added" dates

1.1.24
Release date May 23 2017
* support webhooks for wc versions below 3.0

1.1.23
Release date May 22 2017
* Fix bug in getting specific product
* Add support for webhooks

1.1.22
Release date Apr. 12 2017
* Fix more issues getting orders in WC 3

1.1.21
Release date Apr. 12 2017
* Fix issues getting orders in WC 3

1.1.20
Release date Apr. 5th 2017

* More customer fields sent to Remarkety

1.1.19
Release date Mar. 15 2017

* Bugfix - XML RPC methods overwrite fixed

1.1.18
Release date Dec. 22, 2016

* Provide Remarkety the full size product image

1.1.17
Release date Sep. 6, 2016

* Bugfix - set individual_use when creating coupon

1.1.16
Release date August 31, 2016

* Bugfix - Wrong ordering of order posts caused some orders not to sync

1.1.15
Release date July 31, 2016

* Support link for website tracking

1.1.14
Release date July 31, 2016

* Website tracking embedded into the plugin
* Send the payment method used for orders

1.1.13
Release date Jone 16, 2016

* Enrich product pricing options

1.1.12
Release date April 11, 2016

* Handle connection errors

= 1.1.11 =
Release date February 25, 2016

* Support individual_use parameter for new unique coupons

= 1.1.10 =
Release date December 21, 2015

* Better collecting variants stock level

= 1.1.9 =
Release date December 15, 2015

* Bug fix in count methods and get_orders paging

= 1.1.8 =
Release date December 10th, 2015

* Improve Unique Dynamic Coupons in emails. Support specific product list (include/exclude) in coupons.

= 1.1.7 =
Release date December 3nd, 2015

* Bug fixes

= 1.1.6 =
Release date December 3nd, 2015

* Fix product id bug

= 1.1.5 =
Release date December 2nd, 2015

* Better support variable products

= 1.1.4 =
Release date November 30st, 2015

* Bug fixes - get carts

= 1.1.3 =
Release date November 25th, 2015

* Bug fixes - order sync paging

= 1.1.2 =
Release date September 25th, 2015

* Improve orders data sync

= 1.1.1 =
Release date September 18th, 2015

* Fix log file path

= 1.1.0 =
Release date September 18th, 2015

* Fix order items synchronization - V1.1.0

= 1.0.9 =
Release date August 24th, 2015

* Add support in product tags, inventory and visibility for product recommendations in emails

= 1.0.7 =
Release Date: July 13th, 2015

* Fix plugin settings link

= 1.0.6 =
Release Date: May 22nd, 2015

* Improved functionality - better reports and logging

= 1.0.5 =

* Initial version

== Upgrade Notice ==

= 1.1.16 =
Important upgrade - fixes an issue with missing orders

= 1.1.3 =
Upgrade to fix a problem with syncing orders into Remarkety
