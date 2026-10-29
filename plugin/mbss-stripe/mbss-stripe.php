<?php
/**
 * Plugin Name: MBSS Stripe Payments
 * Plugin URI: https://myrtlebeachsailingschool.com
 * Description: Sends Myrtle Beach Sailing School course deposits and balances to Stripe Checkout. Card numbers stay on Stripe.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Myrtle Beach Sailing School
 * Text Domain: mbss-stripe
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBSS_STRIPE_VERSION', '1.0.0' );
define( 'MBSS_STRIPE_FILE', __FILE__ );
define( 'MBSS_STRIPE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBSS_STRIPE_URL', plugin_dir_url( __FILE__ ) );

require_once MBSS_STRIPE_DIR . 'includes/class-quote.php';
require_once MBSS_STRIPE_DIR . 'includes/class-signature.php';
require_once MBSS_STRIPE_DIR . 'includes/class-checkout-url.php';
require_once MBSS_STRIPE_DIR . 'includes/class-installer.php';
require_once MBSS_STRIPE_DIR . 'includes/class-repository.php';
require_once MBSS_STRIPE_DIR . 'includes/class-stripe-client.php';
require_once MBSS_STRIPE_DIR . 'includes/class-checkout-service.php';
require_once MBSS_STRIPE_DIR . 'includes/class-frontend.php';
require_once MBSS_STRIPE_DIR . 'includes/class-admin.php';
require_once MBSS_STRIPE_DIR . 'includes/class-plugin.php';

register_activation_hook( MBSS_STRIPE_FILE, array( 'MBSS\\StripePay\\Installer', 'install' ) );

add_action( 'plugins_loaded', array( 'MBSS\\StripePay\\Plugin', 'boot' ) );
