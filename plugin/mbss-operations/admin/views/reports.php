<?php
/**
 * Revenue by trip.
 *
 * @package MBSS_Operations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MBSS\Modules\Reporting\Reporting_Service;

$rows = ( new Reporting_Service() )->revenue_by_course();
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'Reports', 'mbss-operations' ); ?></h1>
	<table class="widefat striped">
		<thead><tr><th>Trip</th><th>Path</th><th>Confirmed bookings</th><th>Collected</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $row['title'] ); ?></td>
				<td><?php echo esc_html( (string) $row['path'] ); ?></td>
				<td><?php echo esc_html( (string) $row['bookings'] ); ?></td>
				<td>$<?php echo esc_html( number_format( (float) $row['collected'], 2 ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
