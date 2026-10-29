<?php
/**
 * Operations dashboard.
 *
 * @package MBSS_Operations
 *
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MBSS\Modules\Courses\Course_Session_Repository;
use MBSS\Modules\Reporting\Reporting_Service;

$reports  = new Reporting_Service();
$snapshot = $reports->month_snapshot();
$actions  = $reports->action_counts();
$recent   = $reports->recent_bookings();
$upcoming = ( new Course_Session_Repository() )->get_upcoming( 8 );
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'MBSS Dashboard', 'mbss-operations' ); ?></h1>
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<div class="mbss-dashboard-grid">
		<div class="mbss-dashboard-card">
			<h2><?php esc_html_e( 'Collected this month', 'mbss-operations' ); ?></h2>
			<p>$<?php echo esc_html( number_format( (float) $snapshot['collected'], 2 ) ); ?></p>
			<p><?php esc_html_e( 'Booked value', 'mbss-operations' ); ?>: $<?php echo esc_html( number_format( (float) $snapshot['booked'], 2 ) ); ?></p>
		</div>
		<div class="mbss-dashboard-card">
			<h2><?php esc_html_e( 'Balances outstanding', 'mbss-operations' ); ?></h2>
			<p>$<?php echo esc_html( number_format( (float) $snapshot['outstanding'], 2 ) ); ?></p>
			<p><?php echo esc_html( (string) $actions['overdue'] ); ?> overdue · <?php echo esc_html( (string) $actions['due_soon'] ); ?> due within 14 days</p>
		</div>
		<div class="mbss-dashboard-card">
			<h2><?php esc_html_e( 'Action items', 'mbss-operations' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Open balances', 'mbss-operations' ); ?>: <?php echo esc_html( (string) $actions['balances'] ); ?></li>
				<li><?php esc_html_e( 'Waitlist', 'mbss-operations' ); ?>: <?php echo esc_html( (string) $actions['waitlist'] ); ?></li>
				<li><?php esc_html_e( 'Unpaid holds', 'mbss-operations' ); ?>: <?php echo esc_html( (string) $actions['holds'] ); ?></li>
			</ul>
		</div>
	</div>

	<h2><?php esc_html_e( 'Upcoming dates', 'mbss-operations' ); ?></h2>
	<table class="widefat striped">
		<thead><tr><th>Trip</th><th>Dates</th><th>Open</th></tr></thead>
		<tbody>
		<?php foreach ( $upcoming as $session ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $session['title'] ); ?></td>
				<td><?php echo esc_html( (string) $session['start_date'] ); ?> – <?php echo esc_html( (string) $session['end_date'] ); ?></td>
				<td><?php echo esc_html( (string) $session['seats_remaining'] ); ?> / <?php echo esc_html( (string) $session['capacity'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Recent reservations', 'mbss-operations' ); ?></h2>
	<table class="widefat striped">
		<thead><tr><th>Reference</th><th>Student</th><th>Trip</th><th>Status</th><th>Balance</th></tr></thead>
		<tbody>
		<?php foreach ( $recent as $booking ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $booking['booking_ref'] ); ?></td>
				<td><?php echo esc_html( $booking['first_name'] . ' ' . $booking['last_name'] ); ?></td>
				<td><?php echo esc_html( (string) $booking['title'] ); ?></td>
				<td><?php echo esc_html( (string) $booking['status'] ); ?></td>
				<td>$<?php echo esc_html( number_format( (float) $booking['balance_due'], 2 ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
