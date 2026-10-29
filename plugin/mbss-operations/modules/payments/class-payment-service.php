<?php
/**
 * Stripe Checkout for deposits and later installments.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Payments;

use MBSS\Modules\Bookings\Booking_Service;

final class Payment_Service {

	/**
	 * @param array<string, mixed> $hold Result of Booking_Service::create_hold().
	 * @return array<string, mixed>
	 */
	public function start_deposit_checkout( array $hold ): array {
		$stripe = new Stripe_Client();
		$amount = (string) $hold['deposit'];
		$name   = (string) $hold['course_title'] . ' — ' . (string) $hold['date_label'];

		if ( ! $stripe->is_configured() ) {
			return array(
				'ok'          => true,
				'demo'        => true,
				'booking_ref' => $hold['booking_ref'],
				'deposit'     => $amount,
			);
		}

		return $this->checkout(
			(int) $hold['booking_id'],
			0,
			'deposit',
			$amount,
			$name . ' deposit',
			(string) $hold['student_email'],
			(string) $hold['booking_ref']
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function start_installment_checkout( int $booking_id, string $email ): array {
		$bookings = new Booking_Service();
		$booking  = $bookings->get_booking( $booking_id );
		if ( ! $booking || strtolower( (string) $booking['email'] ) !== strtolower( $email ) ) {
			return array( 'ok' => false, 'error' => 'Booking not found.' );
		}
		if ( 'confirmed' !== $booking['status'] ) {
			return array( 'ok' => false, 'error' => 'This reservation is not confirmed yet.' );
		}
		$next = $bookings->next_payable( $booking_id );
		if ( ! $next ) {
			return array( 'ok' => false, 'error' => 'Nothing is due on this booking.' );
		}

		$stripe = new Stripe_Client();
		if ( ! $stripe->is_configured() ) {
			$ok = $bookings->apply_paid_session(
				array(
					'id'             => 'demo_balance_' . $booking['booking_ref'] . '_' . $next['id'],
					'payment_status' => 'paid',
					'amount_total'   => (int) round( (float) $next['amount'] * 100 ),
					'payment_intent' => 'demo_pi_' . $next['id'],
					'metadata'       => array(
						'booking_id'   => (string) $booking_id,
						'payment_type' => 'installment',
						'payment_id'   => (string) $next['id'],
					),
				)
			);
			return array(
				'ok'          => $ok,
				'demo'        => true,
				'booking_ref' => $booking['booking_ref'],
			);
		}

		return $this->checkout(
			$booking_id,
			(int) $next['id'],
			'installment',
			(string) $next['amount'],
			(string) $booking['course_title'] . ' — ' . (string) $next['label'],
			(string) $booking['email'],
			(string) $booking['booking_ref']
		);
	}

	public function fulfill_session( string $session_id ): bool {
		if ( str_starts_with( $session_id, 'demo_' ) || str_contains( $session_id, '{' ) ) {
			return false;
		}
		$stripe = new Stripe_Client();
		if ( ! $stripe->is_configured() ) {
			return false;
		}
		$session = $stripe->retrieve_checkout_session( $session_id );
		if ( isset( $session['error'] ) ) {
			return false;
		}
		return ( new Booking_Service() )->apply_paid_session( $session );
	}

	public function handle_webhook( string $payload, string $signature ): bool {
		$secret = (string) get_option( 'mbss_stripe_webhook_secret', '' );
		if ( ! Stripe_Client::verify_signature( $payload, $signature, $secret ) ) {
			return false;
		}
		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) || ( $event['type'] ?? '' ) !== 'checkout.session.completed' ) {
			return true;
		}
		$session = $event['data']['object'] ?? null;
		if ( ! is_array( $session ) ) {
			return false;
		}
		( new Booking_Service() )->apply_paid_session( $session );
		return true;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function checkout( int $booking_id, int $payment_id, string $type, string $amount, string $name, string $email, string $ref ): array {
		$cents = (int) round( (float) $amount * 100 );
		if ( $cents < 50 ) {
			return array( 'ok' => false, 'error' => 'Stripe requires a charge of at least $0.50.' );
		}

		$success = home_url( '/book/' );
		$success .= ( str_contains( $success, '?' ) ? '&' : '?' )
			. 'mbss_booking=' . rawurlencode( $ref )
			. '&session_id={CHECKOUT_SESSION_ID}';
		$cancel = add_query_arg(
			array(
				'mbss_booking' => rawurlencode( $ref ),
				'paid'         => '0',
			),
			home_url( '/book/' )
		);

		$body = array(
			'mode'                                   => 'payment',
			'success_url'                            => $success,
			'cancel_url'                             => $cancel,
			'customer_email'                         => $email,
			'client_reference_id'                    => $ref,
			'metadata[booking_id]'                   => (string) $booking_id,
			'metadata[payment_type]'                 => $type,
			'metadata[payment_id]'                   => (string) $payment_id,
			'metadata[booking_ref]'                  => $ref,
			'line_items[0][quantity]'                => 1,
			'line_items[0][price_data][currency]'    => 'usd',
			'line_items[0][price_data][unit_amount]' => $cents,
			'line_items[0][price_data][product_data][name]' => $name,
		);

		$session = ( new Stripe_Client() )->create_checkout_session( $body );
		if ( isset( $session['error'] ) ) {
			$message = $session['error']['message'] ?? 'Stripe could not start checkout.';
			return array( 'ok' => false, 'error' => (string) $message );
		}
		if ( empty( $session['url'] ) ) {
			return array( 'ok' => false, 'error' => 'Stripe did not return a checkout link.' );
		}

		return array(
			'ok'           => true,
			'demo'         => false,
			'checkout_url' => (string) $session['url'],
			'booking_ref'  => $ref,
		);
	}
}
