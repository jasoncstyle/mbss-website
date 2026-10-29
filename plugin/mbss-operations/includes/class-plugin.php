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
use MBSS\Database\Catalog_Seeder;
use MBSS\Database\Schema;
use MBSS\Modules\Bookings\Booking_Service;
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
		add_action( 'init', array( $this, 'boot' ) );
		add_action( 'mbss_expire_holds', array( $this, 'expire_holds' ) );

		if ( is_admin() ) {
			( new Admin_Menu() )->register();
		}

		( new Rest_Controller() )->register();
	}

	public function boot(): void {
		$this->maybe_upgrade();
		( new Booking_Shortcodes() )->register();
		if ( ! wp_next_scheduled( 'mbss_expire_holds' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'mbss_expire_holds' );
		}
	}

	public function expire_holds(): void {
		( new Booking_Service() )->expire_holds();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'mbss-operations',
			false,
			dirname( plugin_basename( MBSS_OPS_FILE ) ) . '/languages'
		);
	}

	private function maybe_upgrade(): void {
		if ( get_option( 'mbss_ops_db_version' ) === MBSS_OPS_VERSION ) {
			return;
		}
		Schema::install();
		Catalog_Seeder::seed();
		Activator::ensure_pages();
	}
}
