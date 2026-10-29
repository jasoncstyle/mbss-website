<?php
/**
 * Stripe connection settings.
 *
 * @package MBSS_Operations
 *
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$secret  = (string) get_option( 'mbss_stripe_secret_key', '' );
$webhook = (string) get_option( 'mbss_stripe_webhook_secret', '' );
$email   = (string) get_option( 'mbss_admin_notify_email', get_option( 'admin_email' ) );
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'Stripe settings', 'mbss-operations' ); ?></h1>
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<p>Checkout is hosted by Stripe. This site stores the payment reference, not the card number. Until a secret key is saved, the booking page can simulate a deposit so the flow can be reviewed.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'mbss_save_settings' ); ?>
		<input type="hidden" name="action" value="mbss_save_settings">
		<table class="form-table">
			<tr>
				<th>Secret key</th>
				<td>
					<input type="password" name="stripe_secret_key" class="regular-text" placeholder="<?php echo $secret ? esc_attr( 'Saved (' . substr( $secret, 0, 7 ) . '…)' ) : 'sk_live_ or sk_test_'; ?>" autocomplete="off">
				</td>
			</tr>
			<tr>
				<th>Webhook secret</th>
				<td>
					<input type="password" name="stripe_webhook_secret" class="regular-text" placeholder="<?php echo $webhook ? 'Saved' : 'whsec_'; ?>" autocomplete="off">
					<p class="description">Endpoint: <code><?php echo esc_html( rest_url( 'mbss/v1/stripe/webhook' ) ); ?></code>. Listen for <code>checkout.session.completed</code>.</p>
				</td>
			</tr>
			<tr>
				<th>Booking notices</th>
				<td><input type="email" name="notify_email" class="regular-text" value="<?php echo esc_attr( $email ); ?>"></td>
			</tr>
		</table>
		<?php submit_button( 'Save Stripe settings' ); ?>
	</form>
</div>
