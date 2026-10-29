<?php
/**
 * Price and payment-schedule quotes.
 *
 * Amounts are calculated in cents so deposits and installment splits
 * always add back to the course total.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Bookings;

final class Quote {

	/**
	 * @param array<string, mixed> $course
	 * @param array<string, mixed> $selection
	 * @return array<string, mixed>
	 */
	public static function build( array $course, array $selection, string $start_date, string $today ): array {
		$model = (string) ( $course['pricing_model'] ?? 'standard' );

		$private_upgrade = ! empty( $selection['private_upgrade'] );
		$addon           = ! empty( $selection['addon'] );
		$addon_104       = ! empty( $selection['addon_104'] );
		$extra           = max( 0, (int) ( $selection['extra_students'] ?? 0 ) );
		$occupancy       = (string) ( $selection['occupancy'] ?? 'standard' );

		if ( 'passage' === $model ) {
			if ( ! in_array( $occupancy, array( 'private', 'shared' ), true ) ) {
				$occupancy = 'private';
			}
			if ( 'shared' === $occupancy && (float) ( $course['shared_price'] ?? 0 ) <= 0 ) {
				return self::fail( 'Shared berth pricing is not available for this trip.' );
			}
			$private_upgrade = false;
			$extra           = 0;
			if ( (int) ( $course['allows_asa106'] ?? 0 ) !== 1 && (float) ( $course['addon_price'] ?? 0 ) <= 0 ) {
				$addon = false;
			}
			if ( (int) ( $course['allows_asa104'] ?? 0 ) !== 1 ) {
				$addon_104 = false;
			}
		} elseif ( 'package' === $model ) {
			$occupancy       = 'private';
			$private_upgrade = false;
			$addon           = false;
			$addon_104       = false;
			if ( (float) ( $course['extra_student_price'] ?? 0 ) <= 0 ) {
				$extra = 0;
			}
			if ( $extra > 2 ) {
				return self::fail( 'A private package can add up to two additional students (four aboard).' );
			}
		} else {
			$occupancy = 'standard';
			$extra     = 0;
			$addon     = false;
			$addon_104 = false;
			if ( $private_upgrade && (float) ( $course['private_upgrade_price'] ?? 0 ) <= 0 ) {
				return self::fail( 'A private upgrade is not offered for this course.' );
			}
		}

		$guests = 'package' === $model ? 2 : 1;
		if ( 'passage' === $model ) {
			$base   = 'shared' === $occupancy ? (float) $course['shared_price'] : (float) $course['base_price'];
			$guests = 'shared' === $occupancy ? 2 : 1;
		} else {
			$base = (float) $course['base_price'];
		}

		$total_cents = self::cents( $base );
		if ( $private_upgrade ) {
			$total_cents += self::cents( (float) $course['private_upgrade_price'] );
		}
		if ( $extra > 0 ) {
			$total_cents += self::cents( (float) $course['extra_student_price'] ) * $extra;
		}
		if ( $addon ) {
			$addon_price = (float) ( $course['asa106_price'] ?? 0 );
			if ( $addon_price <= 0 ) {
				$addon_price = (float) ( $course['addon_price'] ?? 0 );
			}
			$total_cents += self::cents( $addon_price ) * $guests;
		}
		if ( $addon_104 ) {
			$total_cents += self::cents( (float) ( $course['asa104_price'] ?? 0 ) ) * $guests;
		}

		if ( $total_cents <= 0 ) {
			return self::fail( 'This course does not have a price yet.' );
		}

		$lines = self::schedule( $course, $total_cents, $start_date, $today );
		$deposit = 0;
		$balance = 0;
		foreach ( $lines as $line ) {
			$cents = self::cents( (float) $line['amount'] );
			if ( 'deposit' === $line['payment_type'] ) {
				$deposit += $cents;
			} else {
				$balance += $cents;
			}
		}

		$next_due = '';
		foreach ( $lines as $line ) {
			if ( 'deposit' !== $line['payment_type'] ) {
				$next_due = (string) $line['due_on'];
				break;
			}
		}

		return array(
			'ok'               => true,
			'total'            => self::dollars( $total_cents ),
			'deposit'          => self::dollars( $deposit ),
			'balance'          => self::dollars( $balance ),
			'balance_due_on'   => $next_due,
			'occupancy'        => $occupancy,
			'private_upgrade'  => $private_upgrade,
			'extra_students'   => $extra,
			'addon'            => $addon,
			'addon_104'        => $addon_104,
			'occupant_count'   => $guests + $extra,
			'takes_all_seats'  => $private_upgrade,
			'schedule'         => $lines,
		);
	}

	/**
	 * @param array<string, mixed> $course
	 * @return array<int, array<string, mixed>>
	 */
	private static function schedule( array $course, int $total_cents, string $start_date, string $today ): array {
		$pay_in_full = (int) ( $course['pay_in_full'] ?? 0 ) === 1;
		$percent     = (float) ( $course['deposit_percent'] ?? 0 );

		if ( $pay_in_full || $percent <= 0 && (float) ( $course['deposit_amount'] ?? 0 ) >= ( $total_cents / 100 ) ) {
			if ( $pay_in_full ) {
				return array(
					self::line( 1, 'deposit', 'Payment in full', $total_cents, $today ),
				);
			}
		}

		if ( $percent > 0 ) {
			$deposit = (int) round( $total_cents * ( $percent / 100 ) );
			$deposit = max( 1, min( $total_cents, $deposit ) );
			$lines   = array(
				self::line( 1, 'deposit', 'Payment 1 — ' . self::percent_label( $percent ) . ' deposit', $deposit, $today ),
			);
			$remaining = $total_cents - $deposit;
			$second_days = (int) ( $course['second_payment_days'] ?? 0 );
			$final_days  = (int) ( $course['balance_due_days'] ?? 21 );

			if ( $second_days > 0 && $remaining > 0 ) {
				$second = (int) round( $total_cents * 0.45 );
				if ( $second >= $remaining ) {
					$second = $remaining;
				}
				$final = $remaining - $second;
				if ( $second > 0 ) {
					$lines[] = self::line( 2, 'installment', 'Payment 2', $second, self::due( $start_date, $second_days, $today ) );
				}
				if ( $final > 0 ) {
					$lines[] = self::line( 3, 'balance', 'Payment 3 — final balance', $final, self::due( $start_date, $final_days, $today ) );
				}
				return $lines;
			}

			if ( $remaining > 0 ) {
				$lines[] = self::line( 2, 'balance', 'Final balance', $remaining, self::due( $start_date, (int) ( $course['balance_due_days'] ?? 21 ), $today ) );
			}
			return $lines;
		}

		$deposit = self::cents( (float) ( $course['deposit_amount'] ?? 0 ) );
		if ( $deposit <= 0 || $deposit > $total_cents ) {
			$deposit = $total_cents;
		}
		$lines = array(
			self::line( 1, 'deposit', 'Deposit due today', $deposit, $today ),
		);
		$remaining = $total_cents - $deposit;
		if ( $remaining > 0 ) {
			$lines[] = self::line(
				2,
				'balance',
				'Final balance',
				$remaining,
				self::due( $start_date, (int) ( $course['balance_due_days'] ?? 21 ), $today )
			);
		}
		return $lines;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function line( int $number, string $type, string $label, int $cents, string $due_on ): array {
		return array(
			'installment_number' => $number,
			'payment_type'       => $type,
			'label'              => $label,
			'amount'             => self::dollars( $cents ),
			'due_on'             => $due_on,
		);
	}

	private static function due( string $start_date, int $days_before, string $today ): string {
		if ( '' === $start_date ) {
			return $today;
		}
		try {
			$due = ( new \DateTimeImmutable( $start_date ) )->modify( '-' . max( 0, $days_before ) . ' days' );
		} catch ( \Exception $e ) {
			return $today;
		}
		$due_day = $due->format( 'Y-m-d' );
		return $due_day < $today ? $today : $due_day;
	}

	private static function percent_label( float $percent ): string {
		$rounded = abs( $percent - round( $percent ) ) < 0.001 ? (string) (int) round( $percent ) : rtrim( rtrim( number_format( $percent, 2, '.', '' ), '0' ), '.' );
		return $rounded . '%';
	}

	public static function cents( float $amount ): int {
		return (int) round( $amount * 100 );
	}

	public static function dollars( int $cents ): string {
		return number_format( $cents / 100, 2, '.', '' );
	}

	public static function format_range( string $start, string $end, bool $open_enrollment = false ): string {
		if ( $open_enrollment || str_starts_with( $end, '2099' ) ) {
			return 'Start anytime';
		}
		try {
			$s = new \DateTimeImmutable( $start );
			$e = new \DateTimeImmutable( $end );
		} catch ( \Exception $ex ) {
			return $start;
		}
		if ( $s->format( 'Y-m-d' ) === $e->format( 'Y-m-d' ) ) {
			return $s->format( 'M j, Y' );
		}
		if ( $s->format( 'Y-m' ) === $e->format( 'Y-m' ) ) {
			return $s->format( 'M j' ) . '–' . $e->format( 'j, Y' );
		}
		if ( $s->format( 'Y' ) === $e->format( 'Y' ) ) {
			return $s->format( 'M j' ) . ' – ' . $e->format( 'M j, Y' );
		}
		return $s->format( 'M j, Y' ) . ' – ' . $e->format( 'M j, Y' );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function fail( string $message ): array {
		return array(
			'ok'    => false,
			'error' => $message,
		);
	}
}
