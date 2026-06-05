<?php
/**
 * Dashboard home view (scaffold).
 *
 * @package MBSS_Operations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'MBSS Dashboard', 'mbss-operations' ); ?></h1>
	<p><?php esc_html_e( "Today's snapshot — build widgets per docs/08-admin-dashboard.md", 'mbss-operations' ); ?></p>

	<div class="mbss-dashboard-grid">
		<div class="mbss-dashboard-card">
			<h2><?php esc_html_e( 'Upcoming Courses (30 days)', 'mbss-operations' ); ?></h2>
			<p>—</p>
		</div>
		<div class="mbss-dashboard-card">
			<h2><?php esc_html_e( 'Revenue This Month', 'mbss-operations' ); ?></h2>
			<p>—</p>
		</div>
		<div class="mbss-dashboard-card">
			<h2><?php esc_html_e( 'Action Items', 'mbss-operations' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Outstanding waivers', 'mbss-operations' ); ?>: —</li>
				<li><?php esc_html_e( 'Outstanding balances', 'mbss-operations' ); ?>: —</li>
				<li><?php esc_html_e( 'Waitlist', 'mbss-operations' ); ?>: —</li>
			</ul>
		</div>
	</div>
</div>
