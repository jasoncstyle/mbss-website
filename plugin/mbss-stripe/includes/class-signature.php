<?php
/**
 * Stripe webhook signature check. No WordPress calls.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Signature {

	public static function verify( string $payload, string $header, string $secret, int $now, int $tolerance = 300 ): bool {
		if ( '' === $secret || '' === $header ) {
			return false;
		}

		$timestamp = '';
		$given     = '';
		foreach ( explode( ',', $header ) as $piece ) {
			$pair = explode( '=', trim( $piece ), 2 );
			if ( 2 !== count( $pair ) ) {
				continue;
			}
			if ( 't' === $pair[0] ) {
				$timestamp = $pair[1];
			}
			if ( 'v1' === $pair[0] ) {
				$given = $pair[1];
			}
		}

		if ( '' === $timestamp || '' === $given || ! ctype_digit( $timestamp ) ) {
			return false;
		}

		if ( abs( $now - (int) $timestamp ) > $tolerance ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		return hash_equals( $expected, $given );
	}
}
