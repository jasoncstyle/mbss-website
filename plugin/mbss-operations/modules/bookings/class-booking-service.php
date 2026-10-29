<?php
/**
 * Booking workflow: holds, confirmation, cancellation, receipts.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Bookings;

use MBSS\Modules\Crm\Lifecycle_Service;
use MBSS\Modules\Notifications\Mailer;
use MBSS\Modules\Students\Student_Repository;
use MBSS\Modules\Waivers\Waiver_Service;

final class Booking_Service {

	private const HOLD_MINUTES = 2880;

	private static bool $holds_expired = false;

	public function expire_holds(): int {
		if ( self::$holds_expired ) {
			return 0;
		}
		self::$holds_expired = true;
		global $wpdb;
		$table = $wpdb->prefix . 'mbss_bookings';
		return (int) $wpdb->query(
			"UPDATE {$table}
			 SET status = 'expired', updated_at = UTC_TIMESTAMP()
			 WHERE status = 'pending_payment'
			 AND hold_until IS NOT NULL
			 AND hold_until <= UTC_TIMESTAMP()"
		);
	}

	public function seats_remaining( int $session_id, int $capacity, int $seats_booked ): int {
		global $wpdb;
		$this->expire_holds();
		$table = $wpdb->prefix . 'mbss_bookings';
		$held  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(seat_count), 0) FROM {$table}
				 WHERE session_id = %d AND status = 'pending_payment' AND hold_until > UTC_TIMESTAMP()",
				$session_id
			)
		);
		return max( 0, $capacity - $seats_booked - $held );
	}

	/**
	 * @param array<string, mixed> $selection
	 * @return array<string, mixed>
	 */
	public function quote_session( int $session_id, array $selection ): array {
		$session = $this->session_with_course( $session_id );
		if ( ! $session ) {
			return array( 'ok' => false, 'error' => 'That date is no longer listed.' );
		}
		$quote = Quote::build( $session, $selection, (string) $session['start_date'], current_time( 'Y-m-d' ) );
		if ( empty( $quote['ok'] ) ) {
			return $quote;
		}
		$remaining = $this->seats_remaining( (int) $session['id'], (int) $session['capacity'], (int) $session['seats_booked'] );
		$needed    = ! empty( $quote['takes_all_seats'] ) ? (int) $session['capacity'] : 1;
		$quote['session_id']      = (int) $session['id'];
		$quote['course_title']    = (string) $session['title'];
		$quote['date_label']      = Quote::format_range( (string) $session['start_date'], (string) $session['end_date'], (int) $session['pay_in_full'] === 1 );
		$quote['seats_remaining'] = $remaining;
		$quote['seat_count']      = $needed;
		$quote['available']       = $remaining >= $needed;
		if ( ! $quote['available'] ) {
			$quote['unavailable_reason'] = $remaining <= 0 ? 'This date is full.' : 'A private boat needs every seat still open.';
		}
		return $quote;
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public function create_hold( array $input ): array {
		$quote = $this->quote_session( (int) ( $input['session_id'] ?? 0 ), $input );
		if ( empty( $quote['ok'] ) ) {
			return $quote;
		}
		if ( empty( $quote['available'] ) ) {
			return array( 'ok' => false, 'error' => (string) ( $quote['unavailable_reason'] ?? 'This date is full.' ) );
		}

		$student = $this->sanitize_student( $input['student'] ?? array() );
		if ( isset( $student['error'] ) ) {
			return array( 'ok' => false, 'error' => $student['error'] );
		}

		$signature_name = sanitize_text_field( (string) ( $input['signature_name'] ?? '' ) );
		$signature_data = (string) ( $input['signature_data'] ?? '' );
		if ( strlen( $signature_name ) < 2 ) {
			return array( 'ok' => false, 'error' => 'Sign the waiver with your full name.' );
		}
		if ( ! preg_match( '#^data:image/png;base64,[A-Za-z0-9+/=\r\n]+$#', $signature_data ) || strlen( $signature_data ) > 500000 ) {
			return array( 'ok' => false, 'error' => 'Draw your signature before payment.' );
		}
		if ( empty( $input['policy_accepted'] ) ) {
			return array( 'ok' => false, 'error' => 'Accept the payment and rescheduling policy to reserve your seat.' );
		}

		global $wpdb;
		$sessions = $wpdb->prefix . 'mbss_course_sessions';
		$bookings = $wpdb->prefix . 'mbss_bookings';

		$wpdb->query( 'START TRANSACTION' );
		$session = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, capacity, seats_booked FROM {$sessions} WHERE id = %d FOR UPDATE", (int) $quote['session_id'] ),
			ARRAY_A
		);
		if ( ! $session ) {
			$wpdb->query( 'ROLLBACK' );
			return array( 'ok' => false, 'error' => 'That date is no longer listed.' );
		}

		$held = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(seat_count), 0) FROM {$bookings}
				 WHERE session_id = %d AND status = 'pending_payment' AND hold_until > UTC_TIMESTAMP()",
				(int) $session['id']
			)
		);
		$remaining = (int) $session['capacity'] - (int) $session['seats_booked'] - $held;
		$needed    = (int) $quote['seat_count'];
		if ( $remaining < $needed ) {
			$wpdb->query( 'ROLLBACK' );
			return array( 'ok' => false, 'error' => 'Those seats were just taken. Choose another date.' );
		}

		$students   = new Student_Repository();
		$existing   = $students->find_by_email( $student['email'] );
		$student_id = $students->upsert( $student );
		if ( $student_id <= 0 ) {
			$wpdb->query( 'ROLLBACK' );
			return array( 'ok' => false, 'error' => 'We could not save your student record.' );
		}

		$ref = $this->unique_ref();
		$inserted = $wpdb->insert(
			$bookings,
			array(
				'booking_ref'      => $ref,
				'student_id'       => $student_id,
				'session_id'       => (int) $session['id'],
				'status'           => 'pending_payment',
				'seat_count'       => $needed,
				'occupant_count'   => (int) $quote['occupant_count'],
				'occupancy_type'   => (string) $quote['occupancy'],
				'private_upgrade'  => ! empty( $quote['private_upgrade'] ) ? 1 : 0,
				'addon_selected'   => ! empty( $quote['addon'] ) ? 1 : 0,
				'addon_104'        => ! empty( $quote['addon_104'] ) ? 1 : 0,
				'extra_students'   => (int) $quote['extra_students'],
				'total_amount'     => $quote['total'],
				'deposit_amount'   => $quote['deposit'],
				'balance_due'      => $quote['balance'],
				'balance_due_on'   => '' !== $quote['balance_due_on'] ? $quote['balance_due_on'] : null,
				'waiver_signed'    => 1,
				'signature_name'   => $signature_name,
				'experience_level' => $student['experience_level'],
				'special_notes'    => $student['notes'],
				'schedule_json'    => wp_json_encode( $quote['schedule'] ),
				'hold_until'       => gmdate( 'Y-m-d H:i:s', time() + ( self::HOLD_MINUTES * 60 ) ),
			)
		);
		$booking_id = (int) $wpdb->insert_id;
		if ( ! $inserted || $booking_id <= 0 ) {
			$wpdb->query( 'ROLLBACK' );
			return array( 'ok' => false, 'error' => 'We could not start the reservation.' );
		}

		( new Waiver_Service() )->store_signature( $booking_id, $signature_data );
		$wpdb->query( 'COMMIT' );

		$stage = (string) ( $existing['lifecycle_stage'] ?? 'lead' );
		if ( in_array( $stage, array( 'lead', '' ), true ) ) {
			( new Lifecycle_Service() )->update_stage( $student_id, 'inquiry' );
		}
		$this->audit( 'booking_hold', 'booking', $booking_id, $ref );

		return array(
			'ok'            => true,
			'booking_id'    => $booking_id,
			'booking_ref'   => $ref,
			'deposit'       => $quote['deposit'],
			'total'         => $quote['total'],
			'balance'       => $quote['balance'],
			'course_title'  => $quote['course_title'],
			'date_label'    => $quote['date_label'],
			'student_email' => $student['email'],
			'hold_minutes'  => self::HOLD_MINUTES,
			'hold_until'    => gmdate( 'c', time() + ( self::HOLD_MINUTES * 60 ) ),
		);
	}

	/**
	 * @param array<string, mixed> $student
	 * @return array<string, mixed>
	 */
	private function sanitize_student( array $student ): array {
		$first = sanitize_text_field( (string) ( $student['first_name'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $student['last_name'] ?? '' ) );
		$email = sanitize_email( (string) ( $student['email'] ?? '' ) );
		$phone = sanitize_text_field( (string) ( $student['phone'] ?? '' ) );
		$digits = preg_replace( '/\D+/', '', $phone ) ?? '';

		if ( strlen( $first ) < 1 || strlen( $last ) < 1 ) {
			return array( 'error' => 'Enter your first and last name.' );
		}
		if ( ! is_email( $email ) ) {
			return array( 'error' => 'Enter a valid email address.' );
		}
		if ( strlen( $digits ) < 10 ) {
			return array( 'error' => 'Enter a phone number with at least 10 digits.' );
		}

		$experience = sanitize_text_field( (string) ( $student['experience_level'] ?? '' ) );
		$allowed    = array( 'none', 'some', 'asa-101', 'asa-103', 'asa-104', 'offshore' );
		if ( ! in_array( $experience, $allowed, true ) ) {
			$experience = 'none';
		}

		return array(
			'first_name'         => $first,
			'last_name'          => $last,
			'email'              => $email,
			'phone'              => $phone,
			'address'            => sanitize_textarea_field( (string) ( $student['address'] ?? '' ) ),
			'emergency_contact'  => sanitize_text_field( (string) ( $student['emergency_contact'] ?? '' ) ),
			'emergency_phone'    => sanitize_text_field( (string) ( $student['emergency_phone'] ?? '' ) ),
			'asa_number'         => sanitize_text_field( (string) ( $student['asa_number'] ?? '' ) ),
			'experience_level'   => $experience,
			'notes'              => sanitize_textarea_field( (string) ( $student['notes'] ?? '' ) ),
		);
	}

	/**
	 * Mark a checkout session paid and confirm or apply an installment.
	 *
	 * @param array<string, mixed> $session Stripe Checkout Session payload.
	 */
	public function apply_paid_session( array $session ): bool {
		if ( ( $session['payment_status'] ?? '' ) !== 'paid' ) {
			return false;
		}

		global $wpdb;
		$payments = $wpdb->prefix . 'mbss_payments';
		$session_id = (string) ( $session['id'] ?? '' );
		if ( '' === $session_id ) {
			return false;
		}

		$already = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$payments} WHERE external_id = %s", $session_id )
		);
		if ( $already > 0 ) {
			return true;
		}

		$metadata = $session['metadata'] ?? array();
		if ( is_object( $metadata ) ) {
			$metadata = (array) $metadata;
		}
		$booking_id   = (int) ( $metadata['booking_id'] ?? 0 );
		$payment_type = (string) ( $metadata['payment_type'] ?? 'deposit' );
		$payment_row  = (int) ( $metadata['payment_id'] ?? 0 );
		$amount       = number_format( ( (int) ( $session['amount_total'] ?? 0 ) ) / 100, 2, '.', '' );
		$intent       = (string) ( $session['payment_intent'] ?? '' );

		$booking = $this->get_booking( $booking_id );
		if ( ! $booking ) {
			return false;
		}

		if ( 'deposit' === $payment_type ) {
			return $this->confirm_deposit( $booking, $session_id, $intent, $amount );
		}

		return $this->apply_installment( $booking, $payment_row, $session_id, $intent, $amount );
	}

	/**
	 * @param array<string, mixed> $booking
	 */
	private function confirm_deposit( array $booking, string $session_id, string $intent, string $amount ): bool {
		if ( ! in_array( $booking['status'], array( 'pending_payment', 'expired' ), true ) ) {
			return 'confirmed' === $booking['status'];
		}

		global $wpdb;
		$bookings = $wpdb->prefix . 'mbss_bookings';
		$sessions = $wpdb->prefix . 'mbss_course_sessions';
		$payments = $wpdb->prefix . 'mbss_payments';

		$decoded  = json_decode( (string) ( $booking['schedule_json'] ?? '' ), true );
		$schedule = is_array( $decoded ) ? $decoded : array();

		$wpdb->query( 'START TRANSACTION' );
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$bookings} SET status = 'confirmed', updated_at = %s WHERE id = %d AND status IN ('pending_payment', 'expired')",
				current_time( 'mysql' ),
				(int) $booking['id']
			)
		);
		if ( ! $updated ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$sessions} SET seats_booked = seats_booked + %d WHERE id = %d",
				(int) $booking['seat_count'],
				(int) $booking['session_id']
			)
		);

		$paid_cents = Quote::cents( (float) $amount );
		$balance_cents = 0;
		if ( array() === $schedule ) {
			$balance_cents = max( 0, Quote::cents( (float) $booking['total_amount'] ) - $paid_cents );
			$wpdb->insert(
				$payments,
				array(
					'booking_id'         => (int) $booking['id'],
					'amount'             => $amount,
					'payment_type'       => 'deposit',
					'payment_method'     => str_starts_with( $session_id, 'demo_' ) ? 'demo' : 'stripe',
					'status'             => 'paid',
					'label'              => 'Deposit',
					'due_on'             => current_time( 'Y-m-d' ),
					'installment_number' => 1,
					'external_id'        => $session_id,
					'paid_at'            => current_time( 'mysql' ),
					'notes'              => $intent,
				)
			);
			if ( $balance_cents > 0 ) {
				$wpdb->insert(
					$payments,
					array(
						'booking_id'         => (int) $booking['id'],
						'amount'             => Quote::dollars( $balance_cents ),
						'payment_type'       => 'balance',
						'status'             => 'scheduled',
						'label'              => 'Final balance',
						'due_on'             => $booking['balance_due_on'] ?: current_time( 'Y-m-d' ),
						'installment_number' => 2,
					)
				);
			}
		} else {
			foreach ( $schedule as $index => $line ) {
				$is_deposit = 'deposit' === $line['payment_type'];
				if ( ! $is_deposit ) {
					$balance_cents += Quote::cents( (float) $line['amount'] );
				}
				$wpdb->insert(
					$payments,
					array(
						'booking_id'         => (int) $booking['id'],
						'amount'             => $is_deposit ? $amount : $line['amount'],
						'payment_type'       => $line['payment_type'],
						'payment_method'     => $is_deposit ? ( str_starts_with( $session_id, 'demo_' ) ? 'demo' : 'stripe' ) : null,
						'status'             => $is_deposit ? 'paid' : 'scheduled',
						'label'              => $line['label'],
						'due_on'             => $line['due_on'],
						'installment_number' => (int) $line['installment_number'],
						'external_id'        => $is_deposit ? $session_id : null,
						'paid_at'            => $is_deposit ? current_time( 'mysql' ) : null,
						'notes'              => $is_deposit ? $intent : null,
					)
				);
			}
		}

		$next_due = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(due_on) FROM {$payments} WHERE booking_id = %d AND status = 'scheduled'",
				(int) $booking['id']
			)
		);
		$wpdb->update(
			$bookings,
			array(
				'deposit_amount' => $amount,
				'balance_due'    => Quote::dollars( $balance_cents ),
				'balance_due_on' => $next_due ? (string) $next_due : null,
			),
			array( 'id' => (int) $booking['id'] )
		);
		$wpdb->query( 'COMMIT' );

		$confirmed = $this->get_booking( (int) $booking['id'] );
		if ( $confirmed ) {
			$this->refresh_lifecycle( (int) $confirmed['student_id'] );
			( new Mailer() )->send_booking_confirmation( $confirmed );
			( new Mailer() )->send_admin_booking_notice( $confirmed );
		}
		$this->audit( 'booking_confirmed', 'booking', (int) $booking['id'], $session_id );
		return true;
	}

	/**
	 * @param array<string, mixed> $booking
	 */
	private function apply_installment( array $booking, int $payment_id, string $session_id, string $intent, string $amount ): bool {
		global $wpdb;
		$payments = $wpdb->prefix . 'mbss_payments';
		$bookings = $wpdb->prefix . 'mbss_bookings';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$payments} WHERE id = %d AND booking_id = %d AND status = 'scheduled'",
				$payment_id,
				(int) $booking['id']
			),
			ARRAY_A
		);
		if ( ! $row ) {
			return false;
		}

		$wpdb->update(
			$payments,
			array(
				'status'         => 'paid',
				'payment_method' => str_starts_with( $session_id, 'demo_' ) ? 'demo' : 'stripe',
				'external_id'    => $session_id,
				'paid_at'        => current_time( 'mysql' ),
				'amount'         => $amount,
				'notes'          => $intent,
			),
			array( 'id' => (int) $row['id'] )
		);

		$this->recalculate_balance( (int) $booking['id'] );
		$fresh = $this->get_booking( (int) $booking['id'] );
		if ( $fresh ) {
			( new Mailer() )->send_payment_receipt( $fresh, $amount, (string) $row['label'] );
		}
		$this->audit( 'installment_paid', 'booking', (int) $booking['id'], $session_id );
		unset( $bookings );
		return true;
	}

	public function recalculate_balance( int $booking_id ): void {
		global $wpdb;
		$payments = $wpdb->prefix . 'mbss_payments';
		$bookings = $wpdb->prefix . 'mbss_bookings';
		$balance  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount), 0) FROM {$payments} WHERE booking_id = %d AND status = 'scheduled'",
				$booking_id
			)
		);
		$next_due = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(due_on) FROM {$payments} WHERE booking_id = %d AND status = 'scheduled'",
				$booking_id
			)
		);
		$wpdb->update(
			$bookings,
			array(
				'balance_due'    => number_format( (float) $balance, 2, '.', '' ),
				'balance_due_on' => $next_due ? (string) $next_due : null,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id )
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function record_manual_payment( int $booking_id, float $amount, string $method, string $note ): ?array {
		$allowed = array( 'square', 'cash', 'check', 'other', 'stripe' );
		if ( ! in_array( $method, $allowed, true ) ) {
			$method = 'other';
		}
		$booking = $this->get_booking( $booking_id );
		if ( ! $booking || 'confirmed' !== $booking['status'] ) {
			return null;
		}
		$cents = Quote::cents( $amount );
		if ( $cents <= 0 || $cents > Quote::cents( (float) $booking['balance_due'] ) ) {
			return null;
		}

		global $wpdb;
		$payments = $wpdb->prefix . 'mbss_payments';
		$row      = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$payments} WHERE booking_id = %d AND status = 'scheduled' ORDER BY installment_number ASC, due_on ASC LIMIT 1",
				$booking_id
			),
			ARRAY_A
		);

		if ( $row && Quote::cents( (float) $row['amount'] ) === $cents ) {
			$wpdb->update(
				$payments,
				array(
					'status'         => 'paid',
					'payment_method' => $method,
					'paid_at'        => current_time( 'mysql' ),
					'notes'          => $note,
				),
				array( 'id' => (int) $row['id'] )
			);
		} else {
			$wpdb->insert(
				$payments,
				array(
					'booking_id'         => $booking_id,
					'amount'             => Quote::dollars( $cents ),
					'payment_type'       => 'balance',
					'payment_method'     => $method,
					'status'             => 'paid',
					'label'              => 'Manual payment',
					'due_on'             => current_time( 'Y-m-d' ),
					'installment_number' => 90,
					'paid_at'            => current_time( 'mysql' ),
					'notes'              => $note,
				)
			);
			if ( $row ) {
				$reduced = Quote::cents( (float) $row['amount'] ) - $cents;
				if ( $reduced <= 0 ) {
					$wpdb->delete( $payments, array( 'id' => (int) $row['id'] ) );
				} else {
					$wpdb->update( $payments, array( 'amount' => Quote::dollars( $reduced ) ), array( 'id' => (int) $row['id'] ) );
				}
			}
		}

		$this->recalculate_balance( $booking_id );
		$this->audit( 'manual_payment', 'booking', $booking_id, $method . ' ' . Quote::dollars( $cents ) );
		return $this->get_booking( $booking_id );
	}

	public function cancel_booking( int $booking_id, string $reason ): bool {
		$booking = $this->get_booking( $booking_id );
		if ( ! $booking || ! in_array( $booking['status'], array( 'confirmed', 'pending_payment' ), true ) ) {
			return false;
		}
		global $wpdb;
		$bookings = $wpdb->prefix . 'mbss_bookings';
		$sessions = $wpdb->prefix . 'mbss_course_sessions';
		$wpdb->update(
			$bookings,
			array(
				'status'     => 'cancelled',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id )
		);
		if ( 'confirmed' === $booking['status'] ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$sessions} SET seats_booked = GREATEST(0, seats_booked - %d) WHERE id = %d",
					(int) $booking['seat_count'],
					(int) $booking['session_id']
				)
			);
			$this->notify_waitlist( (int) $booking['session_id'] );
		}
		$this->audit( 'booking_cancelled', 'booking', $booking_id, $reason );
		return true;
	}

	public function notify_waitlist( int $session_id ): void {
		global $wpdb;
		$table   = $wpdb->prefix . 'mbss_waitlist';
		$people  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session_id = %d AND notified_at IS NULL", $session_id ),
			ARRAY_A
		);
		if ( ! $people ) {
			return;
		}
		$session = $this->session_with_course( $session_id );
		if ( ! $session ) {
			return;
		}
		$mailer = new Mailer();
		foreach ( $people as $person ) {
			$mailer->send_waitlist_opening( $person, $session );
			$wpdb->update( $table, array( 'notified_at' => current_time( 'mysql' ) ), array( 'id' => (int) $person['id'] ) );
		}
	}

	/**
	 * @param array<string, string> $person
	 */
	public function join_waitlist( int $session_id, array $person ): bool {
		$remaining = 0;
		$session   = $this->session_with_course( $session_id );
		if ( ! $session ) {
			return false;
		}
		$remaining = $this->seats_remaining( $session_id, (int) $session['capacity'], (int) $session['seats_booked'] );
		if ( $remaining > 0 ) {
			return false;
		}
		$email = sanitize_email( $person['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			return false;
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mbss_waitlist',
			array(
				'session_id' => $session_id,
				'first_name' => sanitize_text_field( $person['first_name'] ?? '' ),
				'last_name'  => sanitize_text_field( $person['last_name'] ?? '' ),
				'email'      => $email,
				'phone'      => sanitize_text_field( $person['phone'] ?? '' ),
			)
		);
		return $wpdb->insert_id > 0;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_booking( int $booking_id ): ?array {
		global $wpdb;
		$sql = $this->receipt_sql() . ' WHERE b.id = %d';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $booking_id ), ARRAY_A );
		return $row ? $this->with_payments( $row ) : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_by_ref( string $ref ): ?array {
		global $wpdb;
		$sql = $this->receipt_sql() . ' WHERE b.booking_ref = %s';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $ref ), ARRAY_A );
		return $row ? $this->with_payments( $row ) : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function portal_lookup( string $email, string $ref ): ?array {
		$booking = $this->get_by_ref( $ref );
		if ( ! $booking ) {
			return null;
		}
		if ( strtolower( (string) $booking['email'] ) !== strtolower( sanitize_email( $email ) ) ) {
			return null;
		}
		return $booking;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function next_payable( int $booking_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mbss_payments WHERE booking_id = %d AND status = 'scheduled' ORDER BY installment_number ASC, due_on ASC LIMIT 1",
				$booking_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function catalog(): array {
		global $wpdb;
		$courses = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}mbss_courses WHERE is_active = 1 ORDER BY title ASC",
			ARRAY_A
		) ?: array();
		$out = array();
		foreach ( $courses as $course ) {
			$sessions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mbss_course_sessions
					 WHERE course_id = %d AND status = 'confirmed'
					 AND ( ( %d = 1 AND end_date >= CURDATE() ) OR ( %d = 0 AND start_date >= CURDATE() ) )
					 ORDER BY start_date ASC",
					(int) $course['id'],
					(int) $course['pay_in_full'],
					(int) $course['pay_in_full']
				),
				ARRAY_A
			) ?: array();
			$public_sessions = array();
			foreach ( $sessions as $session ) {
				$remaining = $this->seats_remaining( (int) $session['id'], (int) $session['capacity'], (int) $session['seats_booked'] );
				$public_sessions[] = array(
					'id'              => (int) $session['id'],
					'start'           => (string) $session['start_date'],
					'end'             => (string) $session['end_date'],
					'capacity'        => (int) $session['capacity'],
					'remaining'       => $remaining,
					'label'           => Quote::format_range( (string) $session['start_date'], (string) $session['end_date'], (int) $course['pay_in_full'] === 1 ),
					'open_enrollment' => (int) $course['pay_in_full'] === 1,
					'notes'           => (string) ( $session['notes'] ?? '' ),
				);
			}
			$out[] = array(
				'id'                   => (int) $course['id'],
				'slug'                 => (string) $course['slug'],
				'title'                => (string) $course['title'],
				'path'                 => (string) $course['path'],
				'summary'              => (string) ( $course['summary'] ?? '' ),
				'duration'             => (string) ( $course['duration_label'] ?? '' ),
				'price'                => (float) $course['base_price'],
				'shared_price'         => null === $course['shared_price'] ? null : (float) $course['shared_price'],
				'deposit'              => (float) $course['deposit_amount'],
				'deposit_percent'      => (float) $course['deposit_percent'],
				'pricing_model'        => (string) $course['pricing_model'],
				'private_upgrade'      => (float) $course['private_upgrade_price'],
				'extra_student_price'  => (float) $course['extra_student_price'],
				'addon_price'          => (float) $course['addon_price'],
				'addon_label'          => (string) ( $course['addon_label'] ?? '' ),
				'allows_asa104'        => (int) ( $course['allows_asa104'] ?? 0 ) === 1,
				'allows_asa106'        => (int) ( $course['allows_asa106'] ?? 0 ) === 1 || (float) ( $course['addon_price'] ?? 0 ) > 0,
				'asa104_price'         => (float) ( $course['asa104_price'] ?? 600 ),
				'asa106_price'         => (float) ( $course['asa106_price'] ?? ( $course['addon_price'] ?? 800 ) ),
				'pay_in_full'          => (int) $course['pay_in_full'] === 1,
				'sessions'             => $public_sessions,
			);
		}
		return $out;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function session_with_course( int $session_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, c.title, c.slug, c.base_price, c.shared_price, c.deposit_amount, c.deposit_percent,
				        c.private_upgrade_price, c.extra_student_price, c.addon_price, c.addon_label,
				        c.allows_asa104, c.allows_asa106, c.asa104_price, c.asa106_price,
				        c.pricing_model, c.pay_in_full, c.balance_due_days, c.second_payment_days, c.path
				 FROM {$wpdb->prefix}mbss_course_sessions s
				 JOIN {$wpdb->prefix}mbss_courses c ON c.id = s.course_id
				 WHERE s.id = %d AND s.status = 'confirmed' AND c.is_active = 1",
				$session_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	private function receipt_sql(): string {
		global $wpdb;
		$b = $wpdb->prefix . 'mbss_bookings';
		$s = $wpdb->prefix . 'mbss_course_sessions';
		$c = $wpdb->prefix . 'mbss_courses';
		$st = $wpdb->prefix . 'mbss_students';
		return "SELECT b.*, s.start_date, s.end_date, s.capacity, c.title AS course_title, c.slug AS course_slug,
		               c.pay_in_full, c.duration_label, c.pricing_model, c.path,
		               st.first_name, st.last_name, st.email, st.phone
		        FROM {$b} b
		        JOIN {$s} s ON s.id = b.session_id
		        JOIN {$c} c ON c.id = s.course_id
		        JOIN {$st} st ON st.id = b.student_id";
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function with_payments( array $row ): array {
		global $wpdb;
		$row['payments'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, amount, payment_type, payment_method, status, label, due_on, installment_number, paid_at
				 FROM {$wpdb->prefix}mbss_payments WHERE booking_id = %d ORDER BY installment_number ASC, id ASC",
				(int) $row['id']
			),
			ARRAY_A
		) ?: array();
		$row['date_label'] = Quote::format_range(
			(string) $row['start_date'],
			(string) $row['end_date'],
			(int) $row['pay_in_full'] === 1
		);
		return $row;
	}

	private function refresh_lifecycle( int $student_id ): void {
		global $wpdb;
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}mbss_bookings WHERE student_id = %d AND status = 'confirmed'",
				$student_id
			)
		);
		$stage = $count > 1 ? 'returning' : 'booked';
		( new Lifecycle_Service() )->update_stage( $student_id, $stage );
	}

	private function unique_ref(): string {
		global $wpdb;
		$table = $wpdb->prefix . 'mbss_bookings';
		do {
			$ref = 'MBSS-' . strtoupper( substr( bin2hex( random_bytes( 4 ) ), 0, 6 ) );
			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE booking_ref = %s", $ref ) );
		} while ( $exists > 0 );
		return $ref;
	}

	private function audit( string $action, string $entity, int $entity_id, string $details ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mbss_audit_log',
			array(
				'user_id'     => get_current_user_id() ?: null,
				'action'      => $action,
				'entity_type' => $entity,
				'entity_id'   => $entity_id,
				'details'     => $details,
			)
		);
	}
}
