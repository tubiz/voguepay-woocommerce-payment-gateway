=== Voguepay Woocommerce Payment Gateway ===
Contributors: tubiz
Donate link: http://bosun.me/donate
Tags: woocommerce, payment gateway, payment gateways, voguepay, interswitch, verve cards, tubiz plugins, verve, nigeria
Requires at least: 3.5
Tested up to: 4.2
Stable tag: 3.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Voguepay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store via Visa Cards, Mastercards, Verve Cards and eTranzact.




== Description ==

> Before you start accepting payment on your site, login to your [Voguepay Account](https://voguepay.com/) go to <strong>Settings</strong>, <strong>Account Settings</strong> then <strong>API/Payment Settings</strong>, then set <strong>Notification Method to All</strong> and <strong>Enable Notification API? to Yes</strong>. Then click on <strong>Save Changes</strong> for the changes to be effected. <br /><strong>It is very important that you do this before you start accepting payment on your site.</strong>


This is a Voguepay payment gateway for Woocommerce.

Voguepay is a Nigerian Payment Gateway

Voguepay is a unique online payment processor whose vision is to offer buyers and sellers a secure and easy-to-use means of transacting business online.

VoguePay allows site owners to recieve payment for their goods and services on their website without any setup fee.

To signup for Voguepay visit their website by clicking [here](https://voguepay.com)

Voguepay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store using Nigeria issued Visa Card, Mastercard and Verve Cards

With this Voguepay Woocommerce Payment Gateway plugin, you will be able to accept the following payment methods in your shop:

* __MasterCards__
* __Visa Card__
* __Verve Cards__
* __eTranzact__

= Note =

This plugin is meant to be used by merchants in Nigeria.

= Plugin Features =

*   __Accept payment__ via Visa Cards, Mastercards, Verve Cards and eTranzact.
* 	__Seamless integration__ into the WooCommerce checkout page.
* 	__Add Naira__ currency symbol. To select it go to go to __WooCommerce > Settings__ from the left hand menu, then click __General__ from the top tab. From __Currency__ select Naira, then click on __Save Changes__ for your changes to be effected.

= Premium Addons =

**Voguepay WooCommerce Payment Gateway Transaction Log**

[Voguepay WooCommerce Payment Gateway Transaction Log](https://tunspress.com/plugins/voguepay-woocommerce-payment-gateway-transaction-log/) plugin log and save the full details of every payment notification that happens on your site when using the Voguepay Woocommerce Payment Gateway Plugin.

*Some Features Include*

*	This plugin logs each payment transaction that is made via the Voguepay Woocommerce Payment Gateway plugin in your WordPress website.
*	It also allows you to view the full details of the each transaction without visiting Voguepay website.
*	You can also search for transaction via it's transaction id.
* 	Plus much more. <br />
To get the plugin click [here](https://tunspress.com/plugins/voguepay-woocommerce-payment-gateway-transaction-log/)


= Suggestions / Feature Request =

If you have suggestions or a new feature request, feel free to get in touch with me via the contact form on my website [here](http://bosun.me/get-in-touch/)

You can also follow me on Twitter! **[@tubiz](http://twitter.com/tubiz)**


= Contribute =
To contribute to this plugin feel free to fork it on GitHub [Voguepay Woocommerce Payment Gateway on GitHub](https://github.com/tubiz/voguepay-woocommerce-payment-gateway)


== Installation ==

= Automatic Installation =
* 	Login to your WordPress Admin area
* 	Go to "Plugins > Add New" from the left hand menu
* 	In the search box type "Voguepay Woocommerce Payment Gateway"
*	From the search result you will see "Voguepay Woocommerce Payment Gateway" click on "Install Now" to install the plugin
*	A popup window will ask you to confirm your wish to install the Plugin.

= Note: =
If this is the first time you've installed a WordPress Plugin, you may need to enter the FTP login credential information. If you've installed a Plugin before, it will still have the login information. This information is available through your web server host.

* Click "Proceed" to continue the installation. The resulting installation screen will list the installation as successful or note any problems during the install.
* If successful, click "Activate Plugin" to activate it, or "Return to Plugin Installer" for further actions.

= Manual Installation =
1. 	Download the plugin zip file
2. 	Login to your WordPress Admin. Click on "Plugins > Add New" from the left hand menu.
3.  Click on the "Upload" option, then click "Choose File" to select the zip file from your computer. Once selected, press "OK" and press the "Install Now" button.
4.  Activate the plugin.
5. 	Open the settings page for WooCommerce and click the "Payment Gateways," tab.
6. 	Click on the sub tab for "Voguepay Payment Gateway".
7.	Configure your "Voguepay Payment Gateway" settings. See below for details.



= Configure the plugin =
To configure the plugin, go to __WooCommerce > Settings__ from the left hand menu, then click "Payment Gateways" from the top tab. You should see __"Voguepay Payment Gateway"__ as an option at the top of the screen. Click on it to configure the payment gateway.

__*You can select the radio button next to the Voguepay Payment Gateway from the list of payment gateways available to make it the default gateway.*__

* __Enable/Disable__ - check the box to enable Voguepay Payment Gateway.
* __Title__ - allows you to determine what your customers will see this payment option as on the checkout page.
* __Description__ - controls the message that appears under the payment fields on the checkout page. Here you can list the types of cards you accept.
* __VoguePay Merchant ID__  - enter your Voguepay Merchant ID, this is gotten from your account page on [Voguepay website](https://voguepay.com).
* Click on __Save Changes__ for the changes you made to be effected.





== Frequently Asked Questions ==

= What Do I Need To Use The Plugin =

1.	You need to have Woocommerce plugin installed and activated on your WordPress site.
2.	You need to open an account on [Voguepay](https://voguepay.com)




== Changelog ==

= 3.1.0 =
*	Fix: Use wc_get_order instead or declaring a new WC_Order class
*	Fix: Removed all global $woocommerce variable

= 3.0.0 =
*	New: Add support for Voguepay Multiple Stores
*	New: Added verification check to ensure the payment is sent to the right Voguepay account.
* 	Fix: Change payment icon

= 2.0.4 =
* 	Fix: Fix an error on my part that sent the wrong notify url to Voguepay

= 2.0.3 =
* 	Fix: Disable SSL certificate check when calling Voguepay Notification/Order processing API

= 2.0.2 =
* 	New: Automatically redirect the customer to Voguepay to make payment
*	New: Add support for Woocommerce 2.2

= 2.0.1 =
* Fix: This fix the errors that display on the order received page if another payment method is selected

= 2.0.0 =
* 	New: Check if NGN is set as store currency. As Voguepay only process transactions in Naira
*	Fix: Fine tuned the IPN (Instant Payment Notification). Immediately a payment transaction occurs a notification is sent by Voguepay to your site. This allows an order payment status to be updated realtime as soon as a payment transaction occurs and before a user is redirected back to the site.

= 1.3.0 =
*	New: Better support for digital product stores
*	New: Set correct order status for orders that contains downloadable products
*	Fix: Changed deprecated Woocommerce functions

= 1.2.0 =
*	Fix: Fixed the settings page link not working in Woocommerce 2.1
*	Fix: Failed transaction displaying an error message


= 1.1.0 =
*	New: Added support for Woocommerce 2.1
* 	New: Only load the functions (tbz_add_my_currency & tbz_add_my_currency_symbol) which add the Naira currency and symbol on WordPress sites running Woocommerce version that are less that 2.1, as it has been added to Woocommerce from version 2.1.
* 	New: Added verification checks to ensure the right amount is paid by the customer
*	Fix: Fixed Naira currency not displaying properly.
*	Fix: Check if tbz_add_my_currency & tbz_add_my_currency_symbol functions exist before declaring it.


= 1.0.0 =
*   First release





== Upgrade Notice ==

= 3.1.0 =
* Make plugin compatible with latest WordPress version






== Screenshots ==

1. WooCommerce payment gateway setting page

2. Voguepay Wooocommerce Payment Gateway Setting Page

3. Voguepay Wooocommerce Payment Gateway method on the checkout page

4. Successful Payment Transaction Message

5. Failed Payment Transaction Declined Message

