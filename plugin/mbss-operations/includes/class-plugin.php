<?php
/**
 * Main plugin bootstrap.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS;

use MBSS\Admin\Admin_Menu;
use MBSS\Api\Rest_Controller;
use MBSS\Modules\Bookings\Booking_Shortcodes;

final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

		if ( is_admin() ) {
			( new Admin_Menu() )->register();
		}

		( new Rest_Controller() )->register();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'mbss-operations',
			false,
			dirname( plugin_basename( MBSS_OPS_FILE ) ) . '/languages'
		);
	}

	public function register_shortcodes(): void {
		( new Booking_Shortcodes() )->register();
	}

	public function enqueue_public_assets(): void {
		wp_enqueue_style(
			'mbss-public',
			MBSS_OPS_URL . 'assets/css/admin.css',
			array(),
			MBSS_OPS_VERSION
		);
	}
}
