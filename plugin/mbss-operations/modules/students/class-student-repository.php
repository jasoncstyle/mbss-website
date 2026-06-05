<?php
/**
 * Student repository.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Students;

final class Student_Repository {

	public function find_by_email( string $email ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'mbss_students';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", $email ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function create( array $data ): int {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'mbss_students', $data );
		return (int) $wpdb->insert_id;
	}

	public function get_with_history( int $student_id ): ?array {
		// TODO: Join bookings, certifications, payments, recommendations.
		return null;
	}
}
