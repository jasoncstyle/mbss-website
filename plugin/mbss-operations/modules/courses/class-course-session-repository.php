<?php
/**
 * Course session (scheduled dates) repository.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Courses;

use MBSS\Modules\Bookings\Booking_Service;

final class Course_Session_Repository {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_upcoming( int $limit = 10 ): array {
		global $wpdb;
		$sessions = $wpdb->prefix . 'mbss_course_sessions';
		$courses  = $wpdb->prefix . 'mbss_courses';
		$rows     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, c.title, c.slug, c.deposit_amount, c.base_price, c.pay_in_full, c.deposit_percent,
				        (s.capacity - s.seats_booked) AS seats_remaining
				 FROM {$sessions} s
				 JOIN {$courses} c ON c.id = s.course_id
				 WHERE s.status = 'confirmed' AND c.is_active = 1
				 AND (
				   (c.pay_in_full = 1 AND s.end_date >= CURDATE())
				   OR (c.pay_in_full = 0 AND s.start_date >= CURDATE())
				 )
				 ORDER BY c.pay_in_full ASC, s.start_date ASC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: array();

		$bookings = new Booking_Service();
		foreach ( $rows as &$row ) {
			$row['seats_remaining'] = $bookings->seats_remaining(
				(int) $row['id'],
				(int) $row['capacity'],
				(int) $row['seats_booked']
			);
		}
		unset( $row );
		return $rows;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function insert( array $data ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mbss_course_sessions',
			array(
				'course_id'  => (int) $data['course_id'],
				'start_date' => (string) $data['start_date'],
				'end_date'   => (string) $data['end_date'],
				'capacity'   => (int) $data['capacity'],
				'status'     => 'confirmed',
				'notes'      => (string) ( $data['notes'] ?? '' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function upcoming_admin( int $limit = 80 ): array {
		return $this->get_upcoming( $limit );
	}
}
