<?php
/**
 * Public booking shortcodes.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Bookings;

use MBSS\Modules\Payments\Payment_Service;
use MBSS\Modules\Payments\Stripe_Client;

final class Booking_Shortcodes {

	public function register(): void {
		add_shortcode( 'mbss_booking_flow', array( $this, 'render_booking_flow' ) );
		add_shortcode( 'mbss_customer_portal', array( $this, 'render_portal' ) );
	}

	public function render_booking_flow(): string {
		$this->enqueue_style();

		$ref = isset( $_GET['mbss_booking'] ) ? sanitize_text_field( wp_unslash( $_GET['mbss_booking'] ) ) : '';
		if ( '' !== $ref ) {
			$session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';
			if ( '' !== $session_id ) {
				( new Payment_Service() )->fulfill_session( $session_id );
			}
			$booking = ( new Booking_Service() )->get_by_ref( $ref );
			$cancelled = isset( $_GET['paid'] ) && '0' === (string) wp_unslash( $_GET['paid'] );
			ob_start();
			include MBSS_OPS_DIR . 'public/views/confirmation.php';
			return (string) ob_get_clean();
		}

		$this->enqueue_script();
		ob_start();
		include MBSS_OPS_DIR . 'public/views/booking-flow.php';
		return (string) ob_get_clean();
	}

	public function render_portal(): string {
		$this->enqueue_style();
		$notice  = '';
		$error   = '';
		$booking = null;
		$email   = isset( $_REQUEST['email'] ) ? sanitize_email( wp_unslash( $_REQUEST['email'] ) ) : '';
		$ref     = isset( $_REQUEST['ref'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ref'] ) ) : '';

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['mbss_portal_action'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mbss_portal_nonce'] ?? '' ) ), 'mbss_portal' ) ) {
				$error = 'The form expired. Reload the page and try again.';
			} else {
				$action = sanitize_text_field( wp_unslash( $_POST['mbss_portal_action'] ) );
				$service = new Booking_Service();
				$booking = $service->portal_lookup( $email, $ref );
				if ( ! $booking ) {
					$error = 'No booking matches that email and reference.';
				} elseif ( 'pay' === $action ) {
					$result = ( new Payment_Service() )->start_installment_checkout( (int) $booking['id'], (string) $booking['email'] );
					if ( ! empty( $result['checkout_url'] ) ) {
						wp_safe_redirect( $result['checkout_url'] );
						exit;
					}
					if ( ! empty( $result['demo'] ) ) {
						$notice  = 'Stripe is not connected, so this installment was recorded as a simulated payment.';
						$booking = $service->portal_lookup( $email, $ref );
					} else {
						$error = (string) ( $result['error'] ?? 'Payment could not be started.' );
					}
				}
			}
		} elseif ( '' !== $email && '' !== $ref ) {
			$booking = ( new Booking_Service() )->portal_lookup( $email, $ref );
			if ( ! $booking ) {
				$error = 'No booking matches that email and reference.';
			}
		}

		$stripe_ready = ( new Stripe_Client() )->is_configured();
		ob_start();
		include MBSS_OPS_DIR . 'public/views/customer-portal.php';
		return (string) ob_get_clean();
	}

	private function enqueue_style(): void {
		wp_enqueue_style(
			'mbss-booking',
			MBSS_OPS_URL . 'assets/css/public.css',
			array(),
			MBSS_OPS_VERSION
		);
	}

	private function enqueue_script(): void {
		wp_enqueue_script(
			'mbss-booking',
			MBSS_OPS_URL . 'assets/js/booking.js',
			array(),
			MBSS_OPS_VERSION,
			true
		);

		$course  = isset( $_GET['course'] ) ? sanitize_title( wp_unslash( $_GET['course'] ) ) : '';
		$session = isset( $_GET['session'] ) ? absint( $_GET['session'] ) : 0;
		$data    = array(
			'restUrl'     => esc_url_raw( rest_url( 'mbss/v1/' ) ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'stripeReady' => ( new Stripe_Client() )->is_configured(),
			'portalUrl'   => esc_url_raw( home_url( '/my-booking/' ) ),
			'catalog'     => ( new Booking_Service() )->catalog(),
			'preselect'   => array(
				'course'  => $course,
				'session' => $session,
			),
			'paths'       => array(
				array( 'id' => 'learn', 'title' => 'Learn to Sail', 'text' => 'ASA 101 and ASA 103. Start on the ocean with no experience required.' ),
				array( 'id' => 'advance', 'title' => 'Advance Your Skills', 'text' => 'ASA 102, 104, 114, and docking. Build the skills you will use on a cruise.' ),
				array( 'id' => 'liveaboard', 'title' => 'Liveaboard Packages', 'text' => 'Private weeks for a couple or family. $500 deposit, balance due 21 days before arrival.' ),
				array( 'id' => 'online', 'title' => 'Online Certifications', 'text' => 'Self-paced ASA courses. Pay in full and the materials are mailed to you.' ),
				array( 'id' => 'passage', 'title' => 'Coastal Passages', 'text' => 'The Ocean Sailing Adventures schedule: private or shared cabin, 10% deposit, then payments 60 and 20 days before departure.' ),
			),
		);

		wp_add_inline_script(
			'mbss-booking',
			'window.mbssBooking = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}
}
