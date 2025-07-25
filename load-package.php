<?php
/**
 * This file is designed to be used to load as package NOT a WP plugin!
 *
 * @version 4.6.2
 * @package CoCart
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COCART_FILE' ) ) {
	define( 'COCART_FILE', __FILE__ );
}

if ( ! defined( 'COCART_SLUG' ) ) {
	define( 'COCART_SLUG', 'cart-rest-api-for-woocommerce' );
}

// Include the main CoCart class.
if ( ! class_exists( 'CoCart', false ) ) {
	include_once untrailingslashit( plugin_dir_path( COCART_FILE ) ) . '/includes/class-cocart.php';
}

// Initialize CoCart.
add_action( 'plugins_loaded', array( 'CoCart', 'init' ), -1 );
