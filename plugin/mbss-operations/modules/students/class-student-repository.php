<?php
/**
 * Student repository.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Students;

final class Student_Repository {

	public function find( int $student_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mbss_students WHERE id = %d", $student_id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function find_by_email( string $email ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mbss_students WHERE email = %s", $email ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function create( array $data ): int {
		return $this->upsert( $data );
	}

	/**
	 * Insert or refresh a student. Lifecycle stage is left as-is on update.
	 *
	 * @param array<string, mixed> $data
	 */
	public function upsert( array $data ): int {
		global $wpdb;
		$table    = $wpdb->prefix . 'mbss_students';
		$existing = $this->find_by_email( (string) ( $data['email'] ?? '' ) );
		$fields   = array(
			'first_name'        => (string) ( $data['first_name'] ?? '' ),
			'last_name'         => (string) ( $data['last_name'] ?? '' ),
			'phone'             => (string) ( $data['phone'] ?? '' ),
			'address'           => (string) ( $data['address'] ?? '' ),
			'emergency_contact' => (string) ( $data['emergency_contact'] ?? '' ),
			'emergency_phone'   => (string) ( $data['emergency_phone'] ?? '' ),
			'asa_number'        => (string) ( $data['asa_number'] ?? '' ),
			'experience_level'  => (string) ( $data['experience_level'] ?? '' ),
			'notes'             => (string) ( $data['notes'] ?? '' ),
		);

		if ( $existing ) {
			$fields['updated_at'] = current_time( 'mysql' );
			$wpdb->update( $table, $fields, array( 'id' => (int) $existing['id'] ) );
			return (int) $existing['id'];
		}

		$fields['email']           = (string) $data['email'];
		$fields['lifecycle_stage'] = 'lead';
		$wpdb->insert( $table, $fields );
		return (int) $wpdb->insert_id;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function search( string $term, int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mbss_students';
		$limit = max( 1, min( 200, $limit ) );
		if ( '' === $term ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT %d", $limit ),
				ARRAY_A
			) ?: array();
		}
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s
				 ORDER BY updated_at DESC LIMIT %d",
				$like,
				$like,
				$like,
				$like,
				$limit
			),
			ARRAY_A
		) ?: array();
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_with_history( int $student_id ): ?array {
		$student = $this->find( $student_id );
		if ( ! $student ) {
			return null;
		}
		global $wpdb;
		$student['bookings'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, c.title AS course_title, s.start_date, s.end_date
				 FROM {$wpdb->prefix}mbss_bookings b
				 JOIN {$wpdb->prefix}mbss_course_sessions s ON s.id = b.session_id
				 JOIN {$wpdb->prefix}mbss_courses c ON c.id = s.course_id
				 WHERE b.student_id = %d
				 ORDER BY b.created_at DESC",
				$student_id
			),
			ARRAY_A
		) ?: array();
		$student['payments'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.* FROM {$wpdb->prefix}mbss_payments p
				 JOIN {$wpdb->prefix}mbss_bookings b ON b.id = p.booking_id
				 WHERE b.student_id = %d
				 ORDER BY p.created_at DESC",
				$student_id
			),
			ARRAY_A
		) ?: array();
		return $student;
	}
}
