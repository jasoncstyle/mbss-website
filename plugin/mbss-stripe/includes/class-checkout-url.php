<?php
/**
 * Keeps Stripe's {CHECKOUT_SESSION_ID} placeholder literal.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Checkout_Url {

	public static function encode_success( string $url ): string {
		$token   = 'CHECKOUT_SESSION_ID_TOKEN';
		$plain   = str_replace( '{CHECKOUT_SESSION_ID}', $token, $url );
		$encoded = rawurlencode( $plain );
		return str_replace( $token, '{CHECKOUT_SESSION_ID}', $encoded );
	}
}
