<?php
/**
 * Plugin Name: MBSS Operations
 * Plugin URI: https://myrtlebeachsailingschool.com
 * Description: Business platform for Myrtle Beach Sailing School — students, bookings, payments, scheduling, reporting.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Myrtle Beach Sailing School
 * Text Domain: mbss-operations
 * Domain Path: /languages
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBSS_OPS_VERSION', '0.1.0' );
define( 'MBSS_OPS_FILE', __FILE__ );
define( 'MBSS_OPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBSS_OPS_URL', plugin_dir_url( __FILE__ ) );

require_once MBSS_OPS_DIR . 'includes/class-autoloader.php';
require_once MBSS_OPS_DIR . 'includes/class-activator.php';
require_once MBSS_OPS_DIR . 'includes/class-deactivator.php';
require_once MBSS_OPS_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'MBSS\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MBSS\\Deactivator', 'deactivate' ) );

MBSS\Plugin::instance()->init();
