<?php
/**
 * Public booking shortcodes.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Bookings;

final class Booking_Shortcodes {

	public function register(): void {
		add_shortcode( 'mbss_booking_flow', array( $this, 'render_booking_flow' ) );
	}

	public function render_booking_flow(): string {
		ob_start();
		include MBSS_OPS_DIR . 'public/views/booking-flow.php';
		return (string) ob_get_clean();
	}
}
