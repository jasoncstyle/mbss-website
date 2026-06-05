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
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				wp_user_id BIGINT UNSIGNED NULL,
				first_name VARCHAR(100) NOT NULL,
				last_name VARCHAR(100) NOT NULL,
				email VARCHAR(255) NOT NULL,
				phone VARCHAR(50) NULL,
				address TEXT NULL,
				emergency_contact VARCHAR(255) NULL,
				asa_number VARCHAR(50) NULL,
				experience_level VARCHAR(50) NULL,
				lifecycle_stage VARCHAR(50) NOT NULL DEFAULT 'lead',
				notes TEXT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY email (email),
				KEY lifecycle_stage (lifecycle_stage)
			) {$charset};",

			"CREATE TABLE {$prefix}courses (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				slug VARCHAR(100) NOT NULL,
				title VARCHAR(255) NOT NULL,
				course_type VARCHAR(50) NOT NULL,
				asa_code VARCHAR(20) NULL,
				base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
				deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 250,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY slug (slug)
			) {$charset};",

			"CREATE TABLE {$prefix}course_sessions (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				course_id BIGINT UNSIGNED NOT NULL,
				start_date DATE NOT NULL,
				end_date DATE NOT NULL,
				capacity INT NOT NULL DEFAULT 4,
				seats_booked INT NOT NULL DEFAULT 0,
				instructor_id BIGINT UNSIGNED NULL,
				captain_id BIGINT UNSIGNED NULL,
				mate_id BIGINT UNSIGNED NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'confirmed',
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY course_id (course_id),
				KEY start_date (start_date)
			) {$charset};",

			"CREATE TABLE {$prefix}bookings (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				student_id BIGINT UNSIGNED NOT NULL,
				session_id BIGINT UNSIGNED NOT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'pending',
				private_upgrade TINYINT(1) NOT NULL DEFAULT 0,
				waiver_signed TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY student_id (student_id),
				KEY session_id (session_id)
			) {$charset};",

			"CREATE TABLE {$prefix}payments (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				booking_id BIGINT UNSIGNED NOT NULL,
				amount DECIMAL(10,2) NOT NULL,
				payment_type VARCHAR(50) NOT NULL,
				payment_method VARCHAR(50) NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'pending',
				external_id VARCHAR(255) NULL,
				invoice_sent_at DATETIME NULL,
				paid_at DATETIME NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY booking_id (booking_id)
			) {$charset};",

			"CREATE TABLE {$prefix}staff (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				wp_user_id BIGINT UNSIGNED NULL,
				role VARCHAR(50) NOT NULL,
				display_name VARCHAR(255) NOT NULL,
				email VARCHAR(255) NOT NULL,
				is_active TINYINT(1) NOT NULL DEFAULT 1,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) {$charset};",

			"CREATE TABLE {$prefix}staff_availability (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				staff_id BIGINT UNSIGNED NOT NULL,
				date DATE NOT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'available',
				PRIMARY KEY (id),
				KEY staff_date (staff_id, date)
			) {$charset};",

			"CREATE TABLE {$prefix}waivers (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				booking_id BIGINT UNSIGNED NOT NULL,
				signed_at DATETIME NULL,
				signature_data LONGTEXT NULL,
				pdf_path VARCHAR(500) NULL,
				PRIMARY KEY (id),
				KEY booking_id (booking_id)
			) {$charset};",

			"CREATE TABLE {$prefix}audit_log (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NULL,
				action VARCHAR(100) NOT NULL,
				entity_type VARCHAR(50) NULL,
				entity_id BIGINT UNSIGNED NULL,
				details TEXT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY action (action)
			) {$charset};",
		);

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mbss_ops_db_version', MBSS_OPS_VERSION );
	}
}
