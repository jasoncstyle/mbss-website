<?php
/**
 * Stripe Checkout over HTTP. Card data stays at Stripe.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stripe_Client {

	public function secret(): string {
		$key = (string) get_option( 'mbss_stripe_secret_key', '' );
		return trim( $key );
	}

	public function webhook_secret(): string {
		return trim( (string) get_option( 'mbss_stripe_webhook_secret', '' ) );
	}

	public function is_configured(): bool {
		$secret = $this->secret();
		return str_starts_with( $secret, 'sk_' ) || str_starts_with( $secret, 'rk_' );
	}

	/**
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>
	 */
	public function create_session( array $body ): array {
		$success = (string) ( $body['success_url'] ?? '' );
		unset( $body['success_url'] );
		$encoded = http_build_query( $body );
		if ( '' !== $success ) {
			$encoded .= '&success_url=' . Checkout_Url::encode_success( $success );
		}
		return $this->request( 'POST', 'checkout/sessions', $encoded );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function retrieve_session( string $session_id ): array {
		return $this->request( 'GET', 'checkout/sessions/' . rawurlencode( $session_id ), '' );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function request( string $method, string $path, string $body ): array {
		$secret = $this->secret();
		if ( ! $this->is_configured() ) {
			return array( 'error' => array( 'message' => 'Stripe is not connected.' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $secret . ':' ),
			),
		);
		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
			$args['body']                    = $body;
		}

		$response = wp_remote_request( 'https://api.stripe.com/v1/' . $path, $args );
		if ( is_wp_error( $response ) ) {
			return array( 'error' => array( 'message' => $response->get_error_message() ) );
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $decoded ) ? $decoded : array( 'error' => array( 'message' => 'Stripe returned an unreadable response.' ) );
	}
}
