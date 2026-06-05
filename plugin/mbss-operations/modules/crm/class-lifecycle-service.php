<?php
/**
 * CRM-lite lifecycle stages.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Crm;

final class Lifecycle_Service {

	public const STAGES = array(
		'lead',
		'inquiry',
		'booked',
		'completed',
		'returning',
		'inactive',
	);

	public function update_stage( int $student_id, string $stage ): bool {
		if ( ! in_array( $stage, self::STAGES, true ) ) {
			return false;
		}
		global $wpdb;
		return (bool) $wpdb->update(
			$wpdb->prefix . 'mbss_students',
			array( 'lifecycle_stage' => $stage ),
			array( 'id' => $student_id )
		);
	}
}
