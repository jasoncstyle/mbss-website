<?php
/**
 * Plugin Name: Course Deposits for Stripe
 * Plugin URI: https://hundredairelabs.com
 * Description: Charge a deposit today and the remaining balance before a lesson through Stripe Checkout. Deposit amounts and the due date are set per site.
 * Version: 1.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Hundredaire Labs
 * Author URI: https://hundredairelabs.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mbss-stripe
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBSS_STRIPE_VERSION', '1.1.0' );
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
require_once MBSS_STRIPE_DIR . 'includes/class-directory-tabs.php';
require_once MBSS_STRIPE_DIR . 'includes/class-plugin.php';

register_activation_hook( MBSS_STRIPE_FILE, array( 'MBSS\\StripePay\\Installer', 'install' ) );

add_action( 'plugins_loaded', array( 'MBSS\\StripePay\\Plugin', 'boot' ) );
