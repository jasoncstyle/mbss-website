<?php
/**
 * Digital waiver service.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Waivers;

final class Waiver_Service {

	public function store_signature( int $booking_id, string $signature_data ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mbss_waivers',
			array(
				'booking_id'     => $booking_id,
				'signed_at'      => current_time( 'mysql' ),
				'signature_data' => $signature_data,
			)
		);
		return (int) $wpdb->insert_id;
	}
}
