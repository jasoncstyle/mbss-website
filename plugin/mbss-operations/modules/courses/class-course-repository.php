<?php
/**
 * Course catalog repository.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Courses;

final class Course_Repository {

	public function get_all_active(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mbss_courses';
		return $wpdb->get_results(
			"SELECT * FROM {$table} WHERE is_active = 1 ORDER BY title ASC",
			ARRAY_A
		) ?: array();
	}

	public function find_by_slug( string $slug ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'mbss_courses';
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ),
			ARRAY_A
		);
		return $row ?: null;
	}
}
