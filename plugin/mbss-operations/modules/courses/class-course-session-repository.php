<?php
/**
 * Course session (scheduled dates) repository.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Courses;

final class Course_Session_Repository {

	public function get_upcoming( int $limit = 10 ): array {
		global $wpdb;
		$sessions = $wpdb->prefix . 'mbss_course_sessions';
		$courses  = $wpdb->prefix . 'mbss_courses';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, c.title, c.slug, c.deposit_amount,
				        (s.capacity - s.seats_booked) AS seats_remaining
				 FROM {$sessions} s
				 JOIN {$courses} c ON c.id = s.course_id
				 WHERE s.start_date >= CURDATE()
				 ORDER BY s.start_date ASC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: array();
	}
}
