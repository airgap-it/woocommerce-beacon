<?php
/*
 * Plugin Name: WooCommerce Beacon Payment Gateway
 * Plugin URI: https://github.com/airgap-it/woocommerce-beacon
 * Description: Pay via Beacon Network
 * Author: Lukas Schönbächler
 * Author URI: https://www.papers.ch
 * Version: 1.0.0
*/
require __DIR__ . '/functions.php';

add_filter('woocommerce_currencies', 'register_currencies');
add_filter('woocommerce_currency_symbol', 'register_symbols', 10, 2);
add_filter('woocommerce_payment_gateways', 'register_gateway');
add_action('plugins_loaded', 'init_gateway');