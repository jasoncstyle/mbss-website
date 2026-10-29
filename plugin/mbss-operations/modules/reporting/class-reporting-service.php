<?php
/**
 * Dashboard figures for courses, bookings, and payments.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Reporting;

final class Reporting_Service {

	/**
	 * @return array<string, float|int>
	 */
	public function month_snapshot(): array {
		global $wpdb;
		$payments = $wpdb->prefix . 'mbss_payments';
		$bookings = $wpdb->prefix . 'mbss_bookings';
		$month    = current_time( 'Y-m' );

		$collected = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount), 0) FROM {$payments}
				 WHERE status = 'paid' AND paid_at LIKE %s",
				$month . '%'
			)
		);
		$outstanding = (float) $wpdb->get_var(
			"SELECT COALESCE(SUM(balance_due), 0) FROM {$bookings} WHERE status = 'confirmed'"
		);
		$booked_value = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(total_amount), 0) FROM {$bookings}
				 WHERE status = 'confirmed' AND created_at LIKE %s",
				$month . '%'
			)
		);
		$paid_in_full = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$bookings} WHERE status = 'confirmed' AND balance_due <= 0"
		);

		return array(
			'collected'    => $collected,
			'outstanding'  => $outstanding,
			'booked'       => $booked_value,
			'paid_in_full' => $paid_in_full,
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function recent_bookings( int $limit = 10 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.booking_ref, b.status, b.total_amount, b.balance_due, b.created_at,
				        st.first_name, st.last_name, c.title
				 FROM {$wpdb->prefix}mbss_bookings b
				 JOIN {$wpdb->prefix}mbss_students st ON st.id = b.student_id
				 JOIN {$wpdb->prefix}mbss_course_sessions s ON s.id = b.session_id
				 JOIN {$wpdb->prefix}mbss_courses c ON c.id = s.course_id
				 ORDER BY b.created_at DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: array();
	}

	/**
	 * @return array<string, int>
	 */
	public function action_counts(): array {
		global $wpdb;
		$bookings = $wpdb->prefix . 'mbss_bookings';
		$wait     = $wpdb->prefix . 'mbss_waitlist';
		$today    = current_time( 'Y-m-d' );
		$soon     = gmdate( 'Y-m-d', strtotime( $today . ' +14 days' ) );

		return array(
			'balances' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings} WHERE status = 'confirmed' AND balance_due > 0" ),
			'due_soon' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$bookings}
					 WHERE status = 'confirmed' AND balance_due > 0 AND balance_due_on IS NOT NULL AND balance_due_on <= %s",
					$soon
				)
			),
			'overdue'  => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$bookings}
					 WHERE status = 'confirmed' AND balance_due > 0 AND balance_due_on IS NOT NULL AND balance_due_on < %s",
					$today
				)
			),
			'waitlist' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wait} WHERE notified_at IS NULL" ),
			'holds'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings} WHERE status = 'pending_payment'" ),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function revenue_by_course(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT c.title, c.path,
			        COUNT(DISTINCT b.id) AS bookings,
			        COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) AS collected
			 FROM {$wpdb->prefix}mbss_courses c
			 LEFT JOIN {$wpdb->prefix}mbss_course_sessions s ON s.course_id = c.id
			 LEFT JOIN {$wpdb->prefix}mbss_bookings b ON b.session_id = s.id AND b.status = 'confirmed'
			 LEFT JOIN {$wpdb->prefix}mbss_payments p ON p.booking_id = b.id
			 GROUP BY c.id
			 ORDER BY collected DESC, c.title ASC",
			ARRAY_A
		) ?: array();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function outstanding(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT b.id, b.booking_ref, b.balance_due, b.balance_due_on, c.title,
			        st.first_name, st.last_name, st.email
			 FROM {$wpdb->prefix}mbss_bookings b
			 JOIN {$wpdb->prefix}mbss_students st ON st.id = b.student_id
			 JOIN {$wpdb->prefix}mbss_course_sessions s ON s.id = b.session_id
			 JOIN {$wpdb->prefix}mbss_courses c ON c.id = s.course_id
			 WHERE b.status = 'confirmed' AND b.balance_due > 0
			 ORDER BY b.balance_due_on ASC",
			ARRAY_A
		) ?: array();
	}
}
