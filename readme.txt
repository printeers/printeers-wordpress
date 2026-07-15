=== Printeers ===
Contributors: printeers
Tags: print-on-demand, fulfillment, woocommerce, dropshipping
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 10.0

Connect your WooCommerce store to Printeers for print-on-demand fulfillment.

== Description ==

Printeers is a print-on-demand platform for phone cases and accessories. This plugin connects your WooCommerce store to Printeers, enabling:

* Automatic product sync from your Printeers blueprints to WooCommerce
* Order import from WooCommerce to Printeers for fulfillment
* Shipment tracking synced back to your customers
* Stock quantity updates

== External services ==

This plugin connects your store to the Printeers platform and communicates with the following Printeers services:

* **Printeers Dashboard (dashboard.printeers.com)**: when you click "Connect to Printeers", you are redirected to the Printeers Dashboard to log in and complete the connection. Your store URL and a one-time connect nonce are included in the redirect.
* **Printeers callback API (woocommerce-callback-api.printeers.com)**: after you approve the connection, the plugin sends your store URL and the WooCommerce REST API credentials it generated to this API, so Printeers can sync products, orders, shipments, and stock with your store. When you disconnect, the plugin notifies this API so the store is released on the Printeers side.

Data is only sent when you connect or disconnect the plugin. Both services are operated by Printeers. See the [Printeers privacy statement](https://printeers.com/privacy/) for how Printeers handles your data.

== Installation ==

1. Install and activate the plugin from the WordPress Plugin Directory.
2. Go to the Printeers menu in your WordPress admin.
3. Click "Connect to Printeers" and log in to your Printeers account.
4. Create a store in the Printeers Dashboard to complete the setup.

== Changelog ==

= 1.0.0 =
* Initial release.
