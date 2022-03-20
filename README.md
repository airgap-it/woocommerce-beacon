=== Beacon Payment Gateway ===
Contributors: lukeisontheroad
Tags: woocommerce, payment, gateway, tezos, beacon, airgap
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.1.0
Requires PHP: 7.0
License: MIT
License URI: https://opensource.org/licenses/MIT
 
Enable Tezos crypto payments in your WooCommerce Webshop with the help of the beacon network.

=== Description ===

Install this plugin in your WooCommerce store, set a receiver address and start collecting crypto payment such with uUSD or any other FA2 compatible Tezos token.

=== Download ===

Directly from this GitHub or via https://wordpress.org/plugins/ .

woocommerce.com

=== Installation & configuration ===

Install the plugin directly through the wordpress plugin directory. Afterwards follow these steps:

1. In the admin, enable the payment gateway by going to WooCommerce -> Payments -> Beacon
2. Click on manage
3. Enable the payment gateway, set a title, description, recipient address (your Tezos Wallet address), min Confirmations (you can leave it as default), set a store name and define the payment button description
4. Navigate to Plugins -> Beacon configuration
5. Enable the currencies you want to accept and the conversion rate to your main store currency

=== Tests ===

Just run
```
composer install
php vendor/bin/phpunit 
```

=== Features ===
- Support for native Tez
- Support for FA2 compatible tokens

=== Contributing ===

Before integrating a new feature, please quickly reach out to us in an issue so we can discuss and coordinate the change.

- If you find any bugs, submit an [issue](../../issues) or open [pull-request](../../pulls).

=== Related Projects ===

- [beacon-sdk](hhttps://github.com/airgap-it/beacon-sdk)
- [AirGap Wallet](https://github.com/airgap-it/airgap-wallet)
