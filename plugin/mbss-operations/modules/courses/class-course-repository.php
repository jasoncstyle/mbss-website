<?php
/**
 * Course catalog repository.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Courses;

final class Course_Repository {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_active(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}mbss_courses WHERE is_active = 1 ORDER BY title ASC",
			ARRAY_A
		) ?: array();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}mbss_courses ORDER BY path ASC, title ASC",
			ARRAY_A
		) ?: array();
	}

	public function find( int $course_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mbss_courses WHERE id = %d", $course_id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function find_by_slug( string $slug ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mbss_courses WHERE slug = %s", $slug ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * @param array<string, mixed> $fields
	 */
	public function update( int $course_id, array $fields ): bool {
		global $wpdb;
		$allowed = array(
			'title',
			'summary',
			'duration_label',
			'base_price',
			'shared_price',
			'deposit_amount',
			'deposit_percent',
			'balance_due_days',
			'private_upgrade_price',
			'extra_student_price',
			'addon_price',
			'addon_label',
			'is_active',
		);
		$data = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $fields ) ) {
				$data[ $key ] = $fields[ $key ];
			}
		}
		if ( array() === $data ) {
			return false;
		}
		return false !== $wpdb->update( $wpdb->prefix . 'mbss_courses', $data, array( 'id' => $course_id ) );
	}
}
