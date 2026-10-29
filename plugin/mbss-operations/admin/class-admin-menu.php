<?php
/**
 * Admin menu and booking operations.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Admin;

use MBSS\Modules\Bookings\Booking_Service;
use MBSS\Modules\Courses\Course_Repository;
use MBSS\Modules\Courses\Course_Session_Repository;
use MBSS\Modules\Reporting\Reporting_Service;
use MBSS\Modules\Students\Student_Repository;

final class Admin_Menu {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_mbss_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_mbss_save_course', array( $this, 'save_course' ) );
		add_action( 'admin_post_mbss_add_session', array( $this, 'add_session' ) );
		add_action( 'admin_post_mbss_cancel_booking', array( $this, 'cancel_booking' ) );
		add_action( 'admin_post_mbss_record_payment', array( $this, 'record_payment' ) );
	}

	public function add_menus(): void {
		add_menu_page(
			__( 'MBSS Operations', 'mbss-operations' ),
			__( 'MBSS', 'mbss-operations' ),
			'manage_options',
			'mbss-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-palmtree',
			3
		);

		$submenus = array(
			'mbss-dashboard'  => __( 'Dashboard', 'mbss-operations' ),
			'mbss-courses'    => __( 'Trips and Courses', 'mbss-operations' ),
			'mbss-students'   => __( 'Students', 'mbss-operations' ),
			'mbss-financials' => __( 'Financials', 'mbss-operations' ),
			'mbss-reports'    => __( 'Reports', 'mbss-operations' ),
			'mbss-settings'   => __( 'Stripe Settings', 'mbss-operations' ),
		);

		foreach ( $submenus as $slug => $title ) {
			add_submenu_page(
				'mbss-dashboard',
				$title,
				$title,
				'manage_options',
				$slug,
				array( $this, 'render_page' )
			);
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'mbss-' ) ) {
			return;
		}
		wp_enqueue_style( 'mbss-admin', MBSS_OPS_URL . 'assets/css/admin.css', array(), MBSS_OPS_VERSION );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? 'mbss-dashboard' ) );
		$map  = array(
			'mbss-dashboard'  => 'dashboard-home.php',
			'mbss-courses'    => 'courses.php',
			'mbss-students'   => 'students.php',
			'mbss-financials' => 'financials.php',
			'mbss-reports'    => 'reports.php',
			'mbss-settings'   => 'settings.php',
		);
		$file = MBSS_OPS_DIR . 'admin/views/' . ( $map[ $page ] ?? 'dashboard-home.php' );
		$notice = isset( $_GET['mbss_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['mbss_notice'] ) ) : '';
		include $file;
	}

	public function render_dashboard(): void {
		$this->render_page();
	}

	public function save_settings(): void {
		$this->guard( 'mbss_save_settings' );
		$secret = trim( (string) wp_unslash( $_POST['stripe_secret_key'] ?? '' ) );
		$webhook = trim( (string) wp_unslash( $_POST['stripe_webhook_secret'] ?? '' ) );
		if ( '' !== $secret ) {
			update_option( 'mbss_stripe_secret_key', sanitize_text_field( $secret ) );
		}
		if ( '' !== $webhook ) {
			update_option( 'mbss_stripe_webhook_secret', sanitize_text_field( $webhook ) );
		}
		update_option( 'mbss_admin_notify_email', sanitize_email( wp_unslash( $_POST['notify_email'] ?? '' ) ) );
		$this->back( 'mbss-settings', 'Stripe settings saved.' );
	}

	public function save_course(): void {
		$this->guard( 'mbss_save_course' );
		$id     = absint( $_POST['course_id'] ?? 0 );
		$shared = trim( (string) wp_unslash( $_POST['shared_price'] ?? '' ) );
		$fields = array(
			'base_price'            => $this->money_post( 'base_price' ),
			'deposit_amount'        => $this->money_post( 'deposit_amount' ),
			'deposit_percent'       => $this->money_post( 'deposit_percent' ),
			'private_upgrade_price' => $this->money_post( 'private_upgrade_price' ),
			'extra_student_price'   => $this->money_post( 'extra_student_price' ),
			'is_active'             => empty( $_POST['is_active'] ) ? 0 : 1,
		);
		if ( '' !== $shared ) {
			$fields['shared_price'] = $this->money_post( 'shared_price' );
		}
		( new Course_Repository() )->update( $id, $fields );
		global $wpdb;
		if ( '' === trim( (string) wp_unslash( $_POST['shared_price'] ?? '' ) ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}mbss_courses SET shared_price = NULL WHERE id = %d", $id ) );
		}
		$this->back( 'mbss-courses', 'Course saved.', array( 'course_id' => $id ) );
	}

	public function add_session(): void {
		$this->guard( 'mbss_add_session' );
		$start = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
		$end   = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );
		$course_id = absint( $_POST['course_id'] ?? 0 );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) || $end < $start ) {
			$this->back( 'mbss-courses', 'Enter a valid date range.', array( 'course_id' => $course_id ) );
		}
		( new Course_Session_Repository() )->insert(
			array(
				'course_id'  => $course_id,
				'start_date' => $start,
				'end_date'   => $end,
				'capacity'   => max( 1, absint( $_POST['capacity'] ?? 1 ) ),
				'notes'      => sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) ),
			)
		);
		$this->back( 'mbss-courses', 'Date added.', array( 'course_id' => $course_id ) );
	}

	public function cancel_booking(): void {
		$this->guard( 'mbss_cancel_booking' );
		$id = absint( $_POST['booking_id'] ?? 0 );
		$ok = ( new Booking_Service() )->cancel_booking( $id, sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Cancelled by admin' ) ) );
		$this->back( 'mbss-financials', $ok ? 'Booking cancelled and the cabin was released.' : 'That booking could not be cancelled.' );
	}

	public function record_payment(): void {
		$this->guard( 'mbss_record_payment' );
		$result = ( new Booking_Service() )->record_manual_payment(
			absint( $_POST['booking_id'] ?? 0 ),
			(float) wp_unslash( $_POST['amount'] ?? 0 ),
			sanitize_text_field( wp_unslash( $_POST['method'] ?? 'other' ) ),
			sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) )
		);
		$this->back( 'mbss-financials', $result ? 'Payment recorded.' : 'That payment could not be recorded.' );
	}

	private function guard( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot manage bookings.', 'mbss-operations' ) );
		}
		check_admin_referer( $action );
	}

	private function money_post( string $key ): string {
		return number_format( (float) wp_unslash( $_POST[ $key ] ?? 0 ), 2, '.', '' );
	}

	/**
	 * @param array<string, int|string> $args
	 */
	private function back( string $page, string $notice, array $args = array() ): void {
		wp_safe_redirect(
			add_query_arg(
				array_merge( array( 'page' => $page, 'mbss_notice' => $notice ), $args ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
