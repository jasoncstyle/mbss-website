<?php
/**
 * Tables and the published course catalog.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset  = $wpdb->get_charset_collate();
		$products = $wpdb->prefix . 'mbss_stripe_products';
		$orders   = $wpdb->prefix . 'mbss_stripe_orders';
		$payments = $wpdb->prefix . 'mbss_stripe_payments';

		dbDelta(
			"CREATE TABLE {$products} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				slug varchar(80) NOT NULL,
				title varchar(191) NOT NULL,
				price_cents int(11) NOT NULL DEFAULT 0,
				deposit_cents int(11) NOT NULL DEFAULT 0,
				kind varchar(20) NOT NULL DEFAULT 'lesson',
				active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (id),
				UNIQUE KEY slug (slug)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$orders} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				order_ref varchar(20) NOT NULL,
				product_id bigint(20) unsigned NOT NULL,
				lesson_date date NULL,
				customer_name varchar(120) NOT NULL,
				email varchar(190) NOT NULL,
				phone varchar(40) NOT NULL DEFAULT '',
				price_cents int(11) NOT NULL DEFAULT 0,
				deposit_cents int(11) NOT NULL DEFAULT 0,
				balance_cents int(11) NOT NULL DEFAULT 0,
				balance_due date NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY order_ref (order_ref),
				KEY email (email)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$payments} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				order_id bigint(20) unsigned NOT NULL,
				kind varchar(20) NOT NULL DEFAULT 'deposit',
				amount_cents int(11) NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'pending',
				stripe_session varchar(255) NULL,
				stripe_intent varchar(255) NULL,
				paid_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY stripe_session (stripe_session),
				KEY order_id (order_id)
			) {$charset};"
		);

		add_option( 'mbss_stripe_balance_days', 14 );
		self::ensure_catalog();
		update_option( 'mbss_stripe_db_version', MBSS_STRIPE_VERSION );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( 'mbss_stripe_db_version' ) === MBSS_STRIPE_VERSION ) {
			return;
		}
		self::install();
	}

	public static function ensure_catalog(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'mbss_stripe_products';
		foreach ( self::catalog() as $product ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $product['slug'] )
			);
			if ( $exists > 0 ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array(
					'slug'          => $product['slug'],
					'title'         => $product['title'],
					'price_cents'   => $product['price'],
					'deposit_cents' => $product['deposit'],
					'kind'          => $product['kind'],
					'active'        => 1,
				),
				array( '%s', '%s', '%d', '%d', '%s', '%d' )
			);
		}
	}

	/**
	 * Published Myrtle Beach prices. Deposits follow the current course pages.
	 * ASA 118 docking is the $50 exception. Existing rows are left unchanged.
	 *
	 * @return array<int, array{slug:string,title:string,price:int,deposit:int,kind:string}>
	 */
	public static function catalog(): array {
		return array(
			array( 'slug' => 'asa-101', 'title' => 'ASA 101 Keelboat', 'price' => 90000, 'deposit' => 25000, 'kind' => 'lesson' ),
			array( 'slug' => 'asa-102', 'title' => 'ASA 102 Keelboat', 'price' => 90000, 'deposit' => 25000, 'kind' => 'lesson' ),
			array( 'slug' => 'asa-103', 'title' => 'ASA 103 Coastal Cruising', 'price' => 105000, 'deposit' => 25000, 'kind' => 'lesson' ),
			array( 'slug' => 'asa-104', 'title' => 'ASA 104 Bareboat Cruising', 'price' => 160000, 'deposit' => 25000, 'kind' => 'lesson' ),
			array( 'slug' => 'asa-114', 'title' => 'ASA 114 Cruising Catamaran', 'price' => 140000, 'deposit' => 25000, 'kind' => 'lesson' ),
			array( 'slug' => 'asa-118', 'title' => 'ASA 118 Docking Endorsement', 'price' => 32500, 'deposit' => 5000, 'kind' => 'docking' ),
			array( 'slug' => 'asa-challenge', 'title' => 'ASA 101 or 103 Course Challenge', 'price' => 45000, 'deposit' => 25000, 'kind' => 'lesson' ),
			array( 'slug' => 'asa-101-103-104', 'title' => 'ASA 101 + 103 + 104 Liveaboard', 'price' => 365000, 'deposit' => 50000, 'kind' => 'package' ),
			array( 'slug' => 'asa-101-103', 'title' => 'ASA 101 + 103 Liveaboard', 'price' => 210000, 'deposit' => 50000, 'kind' => 'package' ),
			array( 'slug' => 'asa-102-104', 'title' => 'ASA 102 + 104 Liveaboard', 'price' => 237500, 'deposit' => 25000, 'kind' => 'package' ),
			array( 'slug' => 'asa-103-104', 'title' => 'ASA 103 + 104 Liveaboard', 'price' => 510000, 'deposit' => 50000, 'kind' => 'package' ),
			array( 'slug' => 'asa-104-114', 'title' => 'ASA 104 + 114 Liveaboard', 'price' => 260000, 'deposit' => 50000, 'kind' => 'package' ),
			array( 'slug' => 'hilton-head-1', 'title' => 'Hilton Head Passage — 1 guest', 'price' => 225000, 'deposit' => 25000, 'kind' => 'passage' ),
			array( 'slug' => 'hilton-head-2', 'title' => 'Hilton Head Passage — 2 guests', 'price' => 325000, 'deposit' => 50000, 'kind' => 'passage' ),
			array( 'slug' => 'myrtle-beach-new-york-1', 'title' => 'Myrtle Beach to New York — 1 guest', 'price' => 320000, 'deposit' => 25000, 'kind' => 'passage' ),
			array( 'slug' => 'myrtle-beach-new-york-2', 'title' => 'Myrtle Beach to New York — 2 guests', 'price' => 480000, 'deposit' => 50000, 'kind' => 'passage' ),
			array( 'slug' => 'asa-105', 'title' => 'ASA 105 Coastal Navigation', 'price' => 41900, 'deposit' => 41900, 'kind' => 'online' ),
			array( 'slug' => 'asa-107', 'title' => 'ASA 107 Celestial Navigation', 'price' => 41900, 'deposit' => 41900, 'kind' => 'online' ),
			array( 'slug' => 'asa-117', 'title' => 'ASA 117 Basic Celestial Endorsement', 'price' => 41900, 'deposit' => 41900, 'kind' => 'online' ),
			array( 'slug' => 'asa-119', 'title' => 'ASA 119 Marine Weather', 'price' => 41900, 'deposit' => 41900, 'kind' => 'online' ),
			array( 'slug' => 'asa-120', 'title' => 'ASA 120 Radar Endorsement', 'price' => 41900, 'deposit' => 41900, 'kind' => 'online' ),
		);
	}
}
