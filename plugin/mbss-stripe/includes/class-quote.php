<?php
/**
 * Deposit and balance math. No WordPress calls.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quote {

	/**
	 * @return array{ok:bool,error:string,charge_cents:int,price_cents:int,deposit_cents:int,balance_cents:int,balance_due:?string,pay_in_full:bool}
	 */
	public static function build( int $price_cents, int $deposit_cents, bool $pay_in_full, ?string $lesson_date, string $today, int $balance_days = 14 ): array {
		$empty = array(
			'ok'            => false,
			'error'         => '',
			'charge_cents'  => 0,
			'price_cents'   => max( 0, $price_cents ),
			'deposit_cents' => 0,
			'balance_cents' => 0,
			'balance_due'   => null,
			'pay_in_full'   => $pay_in_full,
		);

		if ( $price_cents <= 0 ) {
			$empty['error'] = 'This course does not have a price yet.';
			return $empty;
		}

		if ( $pay_in_full ) {
			$empty['ok']            = true;
			$empty['charge_cents']  = $price_cents;
			$empty['deposit_cents'] = $price_cents;
			$empty['balance_cents'] = 0;
			$empty['balance_due']   = null;
			return $empty;
		}

		if ( ! self::is_date( $lesson_date ) || ! self::is_date( $today ) ) {
			$empty['error'] = 'Choose the lesson start date.';
			return $empty;
		}

		if ( $lesson_date < $today ) {
			$empty['error'] = 'Choose a lesson date that is today or later.';
			return $empty;
		}

		$deposit = min( max( 0, $deposit_cents ), $price_cents );
		if ( $deposit <= 0 ) {
			$empty['error'] = 'This course does not have a deposit yet.';
			return $empty;
		}

		$due = self::shift_days( $lesson_date, -1 * max( 1, $balance_days ) );
		if ( $due < $today ) {
			$due = $today;
		}

		$empty['ok']            = true;
		$empty['charge_cents']  = $deposit;
		$empty['deposit_cents'] = $deposit;
		$empty['balance_cents'] = $price_cents - $deposit;
		$empty['balance_due']   = $due;
		return $empty;
	}

	public static function dollars( int $cents ): string {
		return '$' . number_format( $cents / 100, 2 );
	}

	public static function cents( string $amount ): int {
		$amount = trim( str_replace( array( '$', ',', ' ' ), '', $amount ) );
		if ( ! preg_match( '/^\d+(\.\d{1,2})?$/', $amount ) ) {
			return -1;
		}
		return (int) round( ( (float) $amount ) * 100 );
	}

	public static function is_date( ?string $value ): bool {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}

	public static function shift_days( string $date, int $days ): string {
		$base = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		if ( ! $base ) {
			return $date;
		}
		return $base->modify( ( $days >= 0 ? '+' : '' ) . $days . ' days' )->format( 'Y-m-d' );
	}
}
