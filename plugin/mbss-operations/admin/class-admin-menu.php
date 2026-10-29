<?php
/**
 * Admin menu and dashboard pages.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Admin;

final class Admin_Menu {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menus(): void {
		add_menu_page(
			__( 'MBSS Operations', 'mbss-operations' ),
			__( 'MBSS', 'mbss-operations' ),
			'manage_options',
			'mbss-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-palmtree',
			3
		);

		$submenus = array(
			'mbss-dashboard'     => __( 'Dashboard', 'mbss-operations' ),
			'mbss-courses'       => __( 'Course Operations', 'mbss-operations' ),
			'mbss-students'      => __( 'Students', 'mbss-operations' ),
			'mbss-financials'    => __( 'Financials', 'mbss-operations' ),
			'mbss-reports'       => __( 'Reports', 'mbss-operations' ),
		);

		foreach ( $submenus as $slug => $title ) {
			add_submenu_page(
				'mbss-dashboard',
				$title,
				$title,
				'manage_options',
				$slug,
				array( $this, 'render_placeholder' )
			);
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'mbss-' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'mbss-admin',
			MBSS_OPS_URL . 'assets/css/admin.css',
			array(),
			MBSS_OPS_VERSION
		);
	}

	public function render_dashboard(): void {
		include MBSS_OPS_DIR . 'admin/views/dashboard-home.php';
	}

	public function render_placeholder(): void {
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? 'mbss-dashboard' ) );
		echo '<div class="wrap mbss-admin">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<p class="mbss-scaffold-notice">' . esc_html__( 'Scaffold placeholder — build per docs/08-admin-dashboard.md', 'mbss-operations' ) . '</p>';
		echo '<p><code>' . esc_html( $page ) . '</code></p>';
		echo '</div>';
	}
}
