<?php
/**
 * Plugin deactivation.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS;

final class Deactivator {

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'mbss_expire_holds' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'mbss_expire_holds' );
		}
		flush_rewrite_rules();
	}
}
