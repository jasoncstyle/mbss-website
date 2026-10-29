<?php
/**
 * Public booking REST API.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Api;

use MBSS\Modules\Bookings\Booking_Service;
use MBSS\Modules\Payments\Payment_Service;
use MBSS\Modules\Progression\Progression_Engine;

final class Rest_Controller {

	private const NAMESPACE = 'mbss/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/sessions/upcoming',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_upcoming_sessions' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/course-finder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'course_finder' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quote',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'quote' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/bookings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/demo-confirm',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'demo_confirm' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/waitlist',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'waitlist' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/portal/lookup',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'portal_lookup' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/portal/pay',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'portal_pay' ),
				'permission_callback' => array( $this, 'check_nonce' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/stripe/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function check_nonce( \WP_REST_Request $request ): bool {
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	public function get_upcoming_sessions( \WP_REST_Request $request ): \WP_REST_Response {
		$repo = new \MBSS\Modules\Courses\Course_Session_Repository();
		return new \WP_REST_Response( $repo->get_upcoming( (int) ( $request->get_param( 'limit' ) ?? 10 ) ) );
	}

	public function course_finder( \WP_REST_Request $request ): \WP_REST_Response {
		$engine = new Progression_Engine();
		$result = $engine->recommend(
			array(
				'experience' => sanitize_text_field( (string) ( $request->get_param( 'experience' ) ?? '' ) ),
				'goal'       => sanitize_text_field( (string) ( $request->get_param( 'goal' ) ?? '' ) ),
				'liveaboard' => sanitize_text_field( (string) ( $request->get_param( 'liveaboard' ) ?? '' ) ),
			)
		);
		if ( ! empty( $result['slug'] ) ) {
			$result['book_url'] = add_query_arg( 'course', rawurlencode( (string) $result['slug'] ), home_url( '/book/' ) );
		}
		return new \WP_REST_Response( $result );
	}

	public function quote( \WP_REST_Request $request ): \WP_REST_Response {
		$result = ( new Booking_Service() )->quote_session( (int) $request->get_param( 'session_id' ), $this->selection( $request ) );
		$status = empty( $result['ok'] ) ? 400 : 200;
		return new \WP_REST_Response( $result, $status );
	}

	public function create_booking( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $this->limited( 'book', 12 ) ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'Too many reservation attempts. Wait a few minutes and try again.' ), 429 );
		}

		$payload = array_merge(
			$this->selection( $request ),
			array(
				'session_id'      => (int) $request->get_param( 'session_id' ),
				'student'         => $request->get_param( 'student' ),
				'signature_name'  => $request->get_param( 'signature_name' ),
				'signature_data'  => $request->get_param( 'signature_data' ),
				'policy_accepted' => (bool) $request->get_param( 'policy_accepted' ),
			)
		);

		$hold = ( new Booking_Service() )->create_hold( $payload );
		if ( empty( $hold['ok'] ) ) {
			return new \WP_REST_Response( $hold, 400 );
		}

		$checkout = ( new Payment_Service() )->start_deposit_checkout( $hold );
		if ( empty( $checkout['ok'] ) ) {
			return new \WP_REST_Response( $checkout, 502 );
		}
		$checkout['deposit']     = $hold['deposit'];
		$checkout['total']       = $hold['total'];
		$checkout['date_label']  = $hold['date_label'];
		$checkout['hold_until']  = $hold['hold_until'];
		return new \WP_REST_Response( $checkout );
	}

	public function demo_confirm( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ( new \MBSS\Modules\Payments\Stripe_Client() )->is_configured() ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'Demo payments are off because Stripe is connected.' ), 403 );
		}
		$ref     = sanitize_text_field( (string) $request->get_param( 'booking_ref' ) );
		$booking = ( new Booking_Service() )->get_by_ref( $ref );
		if ( ! $booking || 'pending_payment' !== $booking['status'] ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'That reservation is not waiting for a deposit.' ), 404 );
		}
		$ok = ( new Booking_Service() )->apply_paid_session(
			array(
				'id'             => 'demo_' . $ref,
				'payment_status' => 'paid',
				'amount_total'   => (int) round( (float) $booking['deposit_amount'] * 100 ),
				'payment_intent' => 'demo_pi_' . $ref,
				'metadata'       => array(
					'booking_id'   => (string) $booking['id'],
					'payment_type' => 'deposit',
					'payment_id'   => '0',
				),
			)
		);
		return new \WP_REST_Response(
			array(
				'ok'          => $ok,
				'booking_ref' => $ref,
			),
			$ok ? 200 : 400
		);
	}

	public function waitlist( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $this->limited( 'wait', 10 ) ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'Too many requests.' ), 429 );
		}
		$ok = ( new Booking_Service() )->join_waitlist(
			(int) $request->get_param( 'session_id' ),
			array(
				'first_name' => (string) $request->get_param( 'first_name' ),
				'last_name'  => (string) $request->get_param( 'last_name' ),
				'email'      => (string) $request->get_param( 'email' ),
				'phone'      => (string) $request->get_param( 'phone' ),
			)
		);
		if ( ! $ok ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'The waitlist is only open when a date is full, and a valid email is required.' ), 400 );
		}
		return new \WP_REST_Response( array( 'ok' => true ) );
	}

	public function portal_lookup( \WP_REST_Request $request ): \WP_REST_Response {
		if ( $this->limited( 'portal', 20 ) ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'Too many lookups. Try again shortly.' ), 429 );
		}
		$booking = ( new Booking_Service() )->portal_lookup(
			(string) $request->get_param( 'email' ),
			sanitize_text_field( (string) $request->get_param( 'booking_ref' ) )
		);
		if ( ! $booking ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'No booking matches that email and reference.' ), 404 );
		}
		return new \WP_REST_Response( array( 'ok' => true, 'booking' => $this->public_booking( $booking ) ) );
	}

	public function portal_pay( \WP_REST_Request $request ): \WP_REST_Response {
		$booking = ( new Booking_Service() )->portal_lookup(
			(string) $request->get_param( 'email' ),
			sanitize_text_field( (string) $request->get_param( 'booking_ref' ) )
		);
		if ( ! $booking ) {
			return new \WP_REST_Response( array( 'ok' => false, 'error' => 'No booking matches that email and reference.' ), 404 );
		}
		$result = ( new Payment_Service() )->start_installment_checkout( (int) $booking['id'], (string) $booking['email'] );
		$status = empty( $result['ok'] ) ? 400 : 200;
		return new \WP_REST_Response( $result, $status );
	}

	public function webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$payload   = $request->get_body();
		$signature = (string) $request->get_header( 'stripe_signature' );
		if ( '' === $signature ) {
			$signature = (string) $request->get_header( 'Stripe-Signature' );
		}
		$ok        = ( new Payment_Service() )->handle_webhook( $payload, $signature );
		return new \WP_REST_Response( array( 'received' => $ok ), $ok ? 200 : 400 );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function selection( \WP_REST_Request $request ): array {
		return array(
			'private_upgrade' => (bool) $request->get_param( 'private_upgrade' ),
			'extra_students'  => (int) $request->get_param( 'extra_students' ),
			'addon'           => (bool) $request->get_param( 'addon' ),
			'addon_104'       => (bool) $request->get_param( 'addon_104' ),
			'occupancy'       => sanitize_text_field( (string) ( $request->get_param( 'occupancy' ) ?? 'standard' ) ),
		);
	}

	/**
	 * @param array<string, mixed> $booking
	 * @return array<string, mixed>
	 */
	private function public_booking( array $booking ): array {
		$payments = array();
		foreach ( $booking['payments'] as $payment ) {
			$payments[] = array(
				'id'      => (int) $payment['id'],
				'label'   => (string) $payment['label'],
				'amount'  => (string) $payment['amount'],
				'status'  => (string) $payment['status'],
				'due_on'  => (string) ( $payment['due_on'] ?? '' ),
				'paid_at' => (string) ( $payment['paid_at'] ?? '' ),
			);
		}
		return array(
			'booking_ref'    => (string) $booking['booking_ref'],
			'status'         => (string) $booking['status'],
			'course_title'   => (string) $booking['course_title'],
			'date_label'     => (string) $booking['date_label'],
			'total'          => (string) $booking['total_amount'],
			'deposit'        => (string) $booking['deposit_amount'],
			'balance'        => (string) $booking['balance_due'],
			'balance_due_on' => (string) ( $booking['balance_due_on'] ?? '' ),
			'payments'       => $payments,
		);
	}

	private function limited( string $bucket, int $max ): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'mbss_rl_' . md5( $bucket . '|' . $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= $max ) {
			return true;
		}
		set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
		return false;
	}
}
