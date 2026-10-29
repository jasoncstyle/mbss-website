<?php
/**
 * Plugin activation.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS;

use MBSS\Database\Catalog_Seeder;
use MBSS\Database\Schema;

final class Activator {

	public static function activate(): void {
		Schema::install();
		Catalog_Seeder::seed();
		self::ensure_pages();
		if ( ! wp_next_scheduled( 'mbss_expire_holds' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'mbss_expire_holds' );
		}
		flush_rewrite_rules();
	}

	public static function ensure_pages(): void {
		$pages = array(
			'book'       => array(
				'title'    => 'Find Your Course',
				'content'  => '[mbss_booking_flow]',
				'template' => 'booking',
			),
			'my-booking' => array(
				'title'    => 'My Booking',
				'content'  => '[mbss_customer_portal]',
				'template' => '',
			),
		);

		foreach ( $pages as $slug => $page ) {
			$existing = get_posts(
				array(
					'name'        => $slug,
					'post_type'   => 'page',
					'post_status' => array( 'publish', 'draft', 'private', 'pending' ),
					'numberposts' => 1,
				)
			);

			if ( $existing ) {
				$current = $existing[0];
				$update  = array( 'ID' => $current->ID );
				if ( 'publish' !== $current->post_status ) {
					$update['post_status'] = 'publish';
				}
				if ( ! str_contains( (string) $current->post_content, $page['content'] ) ) {
					$update['post_content'] = trim( (string) $current->post_content . "\n\n" . $page['content'] );
				}
				if ( count( $update ) > 1 ) {
					wp_update_post( $update );
				}
				if ( '' !== $page['template'] ) {
					update_post_meta( $current->ID, '_wp_page_template', $page['template'] );
				}
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => $page['content'],
				)
			);
			if ( $page_id && '' !== $page['template'] ) {
				update_post_meta( (int) $page_id, '_wp_page_template', $page['template'] );
			}
		}
	}
}
