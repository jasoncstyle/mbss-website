<?php
/**
 * Business intelligence reporting.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Reporting;

final class Reporting_Service {

	public function get_monthly_revenue(): array {
		// TODO: Aggregate payments by category.
		return array(
			'booked'       => 0,
			'deposits'     => 0,
			'outstanding'  => 0,
			'paid_in_full' => 0,
		);
	}

	public function get_progression_rate(): array {
		// TODO: 101 → 103 → 104 retention funnel.
		return array();
	}
}
