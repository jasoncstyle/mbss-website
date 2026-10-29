<?php
/**
 * Database schema installer.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Database;

final class Schema {

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix . 'mbss_';

		$sql = array(
			"CREATE TABLE {$prefix}students (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				wp_user_id bigint(20) unsigned DEFAULT NULL,
				first_name varchar(100) NOT NULL DEFAULT '',
				last_name varchar(100) NOT NULL DEFAULT '',
				email varchar(255) NOT NULL DEFAULT '',
				phone varchar(50) DEFAULT NULL,
				address text NULL,
				emergency_contact varchar(255) DEFAULT NULL,
				emergency_phone varchar(50) DEFAULT NULL,
				asa_number varchar(50) DEFAULT NULL,
				experience_level varchar(50) DEFAULT NULL,
				lifecycle_stage varchar(50) NOT NULL DEFAULT 'lead',
				notes text NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY email (email),
				KEY lifecycle_stage (lifecycle_stage)
			) {$charset};",

			"CREATE TABLE {$prefix}courses (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				slug varchar(100) NOT NULL DEFAULT '',
				title varchar(255) NOT NULL DEFAULT '',
				course_type varchar(50) NOT NULL DEFAULT 'course',
				asa_code varchar(20) DEFAULT NULL,
				path varchar(50) NOT NULL DEFAULT 'learn',
				summary text NULL,
				duration_label varchar(120) DEFAULT NULL,
				base_price decimal(10,2) NOT NULL DEFAULT 0.00,
				shared_price decimal(10,2) DEFAULT NULL,
				deposit_amount decimal(10,2) NOT NULL DEFAULT 250.00,
				deposit_percent decimal(5,2) NOT NULL DEFAULT 0.00,
				private_upgrade_price decimal(10,2) NOT NULL DEFAULT 0.00,
				extra_student_price decimal(10,2) NOT NULL DEFAULT 0.00,
				addon_price decimal(10,2) NOT NULL DEFAULT 0.00,
				addon_label varchar(255) DEFAULT NULL,
				allows_asa104 tinyint(1) NOT NULL DEFAULT 0,
				allows_asa106 tinyint(1) NOT NULL DEFAULT 0,
				asa104_price decimal(10,2) NOT NULL DEFAULT 600.00,
				asa106_price decimal(10,2) NOT NULL DEFAULT 800.00,
				pricing_model varchar(20) NOT NULL DEFAULT 'standard',
				pay_in_full tinyint(1) NOT NULL DEFAULT 0,
				balance_due_days int(11) NOT NULL DEFAULT 21,
				second_payment_days int(11) NOT NULL DEFAULT 0,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY slug (slug)
			) {$charset};",

			"CREATE TABLE {$prefix}course_sessions (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				course_id bigint(20) unsigned NOT NULL,
				start_date date NOT NULL,
				end_date date NOT NULL,
				capacity int(11) NOT NULL DEFAULT 4,
				seats_booked int(11) NOT NULL DEFAULT 0,
				instructor_id bigint(20) unsigned DEFAULT NULL,
				captain_id bigint(20) unsigned DEFAULT NULL,
				mate_id bigint(20) unsigned DEFAULT NULL,
				status varchar(50) NOT NULL DEFAULT 'confirmed',
				notes varchar(255) DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY course_id (course_id),
				KEY start_date (start_date)
			) {$charset};",

			"CREATE TABLE {$prefix}bookings (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				booking_ref varchar(24) DEFAULT NULL,
				student_id bigint(20) unsigned NOT NULL,
				session_id bigint(20) unsigned NOT NULL,
				status varchar(50) NOT NULL DEFAULT 'pending_payment',
				seat_count int(11) NOT NULL DEFAULT 1,
				occupant_count int(11) NOT NULL DEFAULT 1,
				occupancy_type varchar(20) NOT NULL DEFAULT 'standard',
				private_upgrade tinyint(1) NOT NULL DEFAULT 0,
				addon_selected tinyint(1) NOT NULL DEFAULT 0,
				addon_104 tinyint(1) NOT NULL DEFAULT 0,
				extra_students int(11) NOT NULL DEFAULT 0,
				total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
				deposit_amount decimal(10,2) NOT NULL DEFAULT 0.00,
				balance_due decimal(10,2) NOT NULL DEFAULT 0.00,
				balance_due_on date DEFAULT NULL,
				waiver_signed tinyint(1) NOT NULL DEFAULT 0,
				signature_name varchar(255) DEFAULT NULL,
				experience_level varchar(50) DEFAULT NULL,
				special_notes text NULL,
				schedule_json longtext NULL,
				hold_until datetime DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY booking_ref (booking_ref),
				KEY student_id (student_id),
				KEY session_id (session_id),
				KEY status (status)
			) {$charset};",

			"CREATE TABLE {$prefix}payments (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				booking_id bigint(20) unsigned NOT NULL,
				amount decimal(10,2) NOT NULL DEFAULT 0.00,
				payment_type varchar(50) NOT NULL DEFAULT '',
				payment_method varchar(50) DEFAULT NULL,
				status varchar(50) NOT NULL DEFAULT 'pending',
				label varchar(255) DEFAULT NULL,
				due_on date DEFAULT NULL,
				installment_number int(11) NOT NULL DEFAULT 1,
				external_id varchar(255) DEFAULT NULL,
				invoice_sent_at datetime DEFAULT NULL,
				paid_at datetime DEFAULT NULL,
				notes text NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY external_id (external_id),
				KEY booking_id (booking_id),
				KEY status (status)
			) {$charset};",

			"CREATE TABLE {$prefix}staff (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				wp_user_id bigint(20) unsigned DEFAULT NULL,
				role varchar(50) NOT NULL DEFAULT '',
				display_name varchar(255) NOT NULL DEFAULT '',
				email varchar(255) NOT NULL DEFAULT '',
				is_active tinyint(1) NOT NULL DEFAULT 1,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id)
			) {$charset};",

			"CREATE TABLE {$prefix}staff_availability (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				staff_id bigint(20) unsigned NOT NULL,
				date date NOT NULL,
				status varchar(50) NOT NULL DEFAULT 'available',
				PRIMARY KEY  (id),
				KEY staff_date (staff_id, date)
			) {$charset};",

			"CREATE TABLE {$prefix}waivers (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				booking_id bigint(20) unsigned NOT NULL,
				signed_at datetime DEFAULT NULL,
				signature_data longtext NULL,
				pdf_path varchar(500) DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY booking_id (booking_id)
			) {$charset};",

			"CREATE TABLE {$prefix}waitlist (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_id bigint(20) unsigned NOT NULL,
				first_name varchar(100) NOT NULL DEFAULT '',
				last_name varchar(100) NOT NULL DEFAULT '',
				email varchar(255) NOT NULL DEFAULT '',
				phone varchar(50) DEFAULT NULL,
				notified_at datetime DEFAULT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY session_id (session_id),
				KEY email (email)
			) {$charset};",

			"CREATE TABLE {$prefix}audit_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned DEFAULT NULL,
				action varchar(100) NOT NULL DEFAULT '',
				entity_type varchar(50) DEFAULT NULL,
				entity_id bigint(20) unsigned DEFAULT NULL,
				details text NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY action (action)
			) {$charset};",
		);

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mbss_ops_db_version', MBSS_OPS_VERSION );
	}
}
