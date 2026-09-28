=== Printeers: Print on demand ===
Contributors: printeers
Tags: print-on-demand, fulfillment, woocommerce, dropshipping
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
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
* **Printeers callback API (woocommerce-callback-api.printeers.com)**: after you approve the connection, the plugin sends your store URL and the WooCommerce REST API credentials it generated to this API, so Printeers can sync products, orders, shipments, and stock with your store. When you disconnect, the plugin sends your store URL, the address of the site it runs on, and the API credential to this API so the store is paused on the Printeers side; a copied site cannot disconnect the original store.

Data is only sent when you connect or disconnect the plugin. Both services are operated by Printeers. See the [Printeers privacy statement](https://printeers.com/privacy/) for how Printeers handles your data.

== Installation ==

1. Install and activate the plugin from the WordPress Plugin Directory.
2. Go to the Printeers menu in your WordPress admin.
3. Click "Connect to Printeers" and log in to your Printeers account.
4. Create a store in the Printeers Dashboard to complete the setup.

== Screenshots ==

1. Create and sell custom phone cases and wearables: 800+ products, same day shipping, custom branding options.
2. A case type for every lifestyle: tough, MagSafe-compatible, wallet, crossbody, clear soft and many more styles.
3. Photorealistic mockups display your products from various angles and close ups.
4. Fully branded customer experience: build your brand by adding custom items to your orders.
5. Track your orders in the Printeers Dashboard: set up your shop, add products, track orders and more.
6. Shipping in 24 hours: all orders placed before 16:00 CET are printed and shipped same day.

== Changelog ==

= 1.1.0 =
* Disconnect is now safe on a copied site: the plugin sends the address of the site it runs on, and Printeers refuses to disconnect a store from a different site.
* The Printeers page recognizes a connection that belongs to another site (for example after copying the site to a staging or new domain) and offers to remove it from this site only, without touching the original store.
* Disconnecting pauses the store on the Printeers side instead of removing it; connecting again from the plugin resumes syncing.
* When a disconnect fails, the reason given by Printeers is shown.

= 1.0.0 =
* Initial release.
