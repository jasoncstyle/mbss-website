<?php
/**
 * Staff management service.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Staff;

final class Staff_Service {

	public function assign_to_session( int $session_id, int $staff_id, string $role ): bool {
		// TODO: Conflict detection + email notification.
		return false;
	}
}
