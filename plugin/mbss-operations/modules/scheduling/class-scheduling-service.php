<?php
/**
 * Scheduling and conflict detection.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Scheduling;

final class Scheduling_Service {

	public function has_conflict( int $staff_id, string $start_date, string $end_date ): bool {
		// TODO: Check staff_availability and existing session assignments.
		return false;
	}
}
