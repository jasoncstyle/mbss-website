<?php
/**
 * Booking workflow service.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Bookings;

use MBSS\Modules\Students\Student_Repository;

final class Booking_Service {

	public function create_booking( array $booking_data, array $student_data ): int {
		// TODO: Validate session availability, create/find student, create booking + deposit payment.
		$students = new Student_Repository();
		$existing = $students->find_by_email( $student_data['email'] ?? '' );

		if ( ! $existing ) {
			$students->create( $student_data );
		}

		return 0;
	}
}
