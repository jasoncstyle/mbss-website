<?php
/**
 * Minimal Stripe API client (Checkout + webhook signatures).
 *
 * Card data never touches this site. Checkout is hosted by Stripe.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Payments;

final class Stripe_Client {

	private string $secret_key;

	public function __construct( ?string $secret_key = null ) {
		$this->secret_key = $secret_key ?? (string) get_option( 'mbss_stripe_secret_key', '' );
	}

	public function is_configured(): bool {
		return str_starts_with( $this->secret_key, 'sk_' ) || str_starts_with( $this->secret_key, 'rk_' );
	}

	/**
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>
	 */
	public function create_checkout_session( array $body ): array {
		return $this->request( 'POST', 'checkout/sessions', $body );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function retrieve_checkout_session( string $session_id ): array {
		return $this->request( 'GET', 'checkout/sessions/' . rawurlencode( $session_id ) );
	}

	/**
	 * Verify a Stripe-Signature header. Tolerance is five minutes.
	 */
	public static function verify_signature( string $payload, string $header, string $secret, ?int $now = null ): bool {
		if ( '' === $secret || '' === $header ) {
			return false;
		}

		$timestamp = '';
		$signatures = array();
		foreach ( explode( ',', $header ) as $part ) {
			$pair = explode( '=', trim( $part ), 2 );
			if ( 2 !== count( $pair ) ) {
				continue;
			}
			if ( 't' === $pair[0] ) {
				$timestamp = $pair[1];
			}
			if ( 'v1' === $pair[0] ) {
				$signatures[] = $pair[1];
			}
		}

		if ( '' === $timestamp || array() === $signatures ) {
			return false;
		}

		$now = $now ?? time();
		if ( abs( $now - (int) $timestamp ) > 300 ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		foreach ( $signatures as $signature ) {
			if ( hash_equals( $expected, $signature ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>
	 */
	private function request( string $method, string $path, array $body = array() ): array {
		if ( ! $this->is_configured() ) {
			return array( 'error' => array( 'message' => 'Stripe is not configured.' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->secret_key,
			),
		);

		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
			$args['body']                    = http_build_query( $body );
		}

		$response = wp_remote_request( 'https://api.stripe.com/v1/' . $path, $args );
		if ( is_wp_error( $response ) ) {
			return array( 'error' => array( 'message' => $response->get_error_message() ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return array( 'error' => array( 'message' => 'Stripe returned an unreadable response.' ) );
		}
		if ( $code < 200 || $code >= 300 ) {
			$message = $data['error']['message'] ?? 'Stripe request failed.';
			return array( 'error' => array( 'message' => (string) $message ) );
		}
		return $data;
	}
}
