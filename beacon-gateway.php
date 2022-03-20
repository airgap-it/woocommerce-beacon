<?php
/*
 * Plugin Name: WooCommerce Beacon Payment Gateway
 * Plugin URI: https://github.com/airgap-it/woocommerce-beacon
 * Description: Pay via Beacon Network
 * Author: Lukas Schönbächler
 * Author URI: https://www.papers.ch
 * Version: 1.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
*/
require_once (__DIR__ . '/functions.php');

add_filter('woocommerce_payment_gateways', 'beacon_register_gateway');
add_action('plugins_loaded', 'beacon_init_gateway');
add_action('admin_menu', 'beacon_menu');