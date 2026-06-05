<?php
/**
 * PSR-4 style autoloader for MBSS Operations.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'MBSS\\';
		if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$relative = str_replace( '\\', '/', $relative );
		$parts    = explode( '/', $relative );

		// MBSS\Modules\Students\Student_Repository → modules/students/class-student-repository.php
		if ( count( $parts ) >= 2 && 'Modules' === $parts[0] ) {
			$module = strtolower( $parts[1] );
			$class_file = 'class-' . strtolower( str_replace( '_', '-', $parts[2] ) ) . '.php';
			$file = MBSS_OPS_DIR . 'modules/' . $module . '/' . $class_file;
			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// MBSS\Admin\Admin_Menu → admin/class-admin-menu.php
		if ( 'Admin' === $parts[0] && isset( $parts[1] ) ) {
			$file = MBSS_OPS_DIR . 'admin/class-' . strtolower( str_replace( '_', '-', $parts[1] ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// MBSS\Api\Rest_Controller → api/class-rest-controller.php
		if ( 'Api' === $parts[0] && isset( $parts[1] ) ) {
			$file = MBSS_OPS_DIR . 'api/class-' . strtolower( str_replace( '_', '-', $parts[1] ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// MBSS\Database\Schema → database/class-schema.php
		if ( 'Database' === $parts[0] && isset( $parts[1] ) ) {
			$file = MBSS_OPS_DIR . 'database/class-' . strtolower( str_replace( '_', '-', $parts[1] ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// MBSS\Plugin → includes/class-plugin.php
		$file = MBSS_OPS_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $parts[0] ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
