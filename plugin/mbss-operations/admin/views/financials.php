<?php
/**
 * Balances and manual payments.
 *
 * @package MBSS_Operations
 *
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MBSS\Modules\Reporting\Reporting_Service;

$rows = ( new Reporting_Service() )->outstanding();
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'Financials', 'mbss-operations' ); ?></h1>
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<p>Stripe collects the deposit and later installments. Record a check, cash, or other payment here when it does not go through checkout.</p>
	<table class="widefat striped">
		<thead><tr><th>Reference</th><th>Student</th><th>Trip</th><th>Balance</th><th>Due</th><th>Record payment</th><th>Cancel</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $row['booking_ref'] ); ?></td>
				<td><?php echo esc_html( $row['first_name'] . ' ' . $row['last_name'] ); ?><br><?php echo esc_html( (string) $row['email'] ); ?></td>
				<td><?php echo esc_html( (string) $row['title'] ); ?></td>
				<td>$<?php echo esc_html( number_format( (float) $row['balance_due'], 2 ) ); ?></td>
				<td><?php echo esc_html( (string) $row['balance_due_on'] ); ?></td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'mbss_record_payment' ); ?>
						<input type="hidden" name="action" value="mbss_record_payment">
						<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $row['id'] ); ?>">
						<input type="number" name="amount" step="0.01" min="0.01" max="<?php echo esc_attr( (string) $row['balance_due'] ); ?>" value="<?php echo esc_attr( (string) $row['balance_due'] ); ?>">
						<select name="method">
							<option value="check">Check</option>
							<option value="cash">Cash</option>
							<option value="square">Square</option>
							<option value="other">Other</option>
						</select>
						<input type="text" name="note" placeholder="Note">
						<?php submit_button( 'Record', 'secondary', '', false ); ?>
					</form>
				</td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'mbss_cancel_booking' ); ?>
						<input type="hidden" name="action" value="mbss_cancel_booking">
						<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $row['id'] ); ?>">
						<input type="text" name="reason" placeholder="Reason" required>
						<?php submit_button( 'Cancel', 'delete', '', false ); ?>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
