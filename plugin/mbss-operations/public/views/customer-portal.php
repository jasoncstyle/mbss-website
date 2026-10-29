<?php
/**
 * Customer booking lookup and balance payment.
 *
 * @package MBSS_Operations
 *
 * @var string $notice
 * @var string $error
 * @var array<string, mixed>|null $booking
 * @var string $email
 * @var string $ref
 * @var bool $stripe_ready
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mbss-booking mbss-portal">
	<p class="mbss-booking__kicker">Your booking</p>
	<h2>Look up a reservation</h2>
	<p>Use the email and reference from your confirmation. Deposits and later payments are collected by Stripe.</p>

	<?php if ( $error ) : ?>
		<p class="mbss-alert" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
	<?php if ( $notice ) : ?>
		<p class="mbss-alert mbss-alert--ok" role="status"><?php echo esc_html( $notice ); ?></p>
	<?php endif; ?>

	<form class="mbss-form" method="post">
		<?php wp_nonce_field( 'mbss_portal', 'mbss_portal_nonce' ); ?>
		<input type="hidden" name="mbss_portal_action" value="lookup">
		<label>Email
			<input type="email" name="email" required value="<?php echo esc_attr( $email ); ?>">
		</label>
		<label>Booking reference
			<input type="text" name="ref" required value="<?php echo esc_attr( $ref ); ?>" placeholder="MBSS-">
		</label>
		<button class="mbss-btn" type="submit">Find booking</button>
	</form>

	<?php if ( $booking ) : ?>
		<article class="mbss-receipt">
			<h3><?php echo esc_html( (string) $booking['course_title'] ); ?></h3>
			<p><?php echo esc_html( (string) $booking['date_label'] ); ?></p>
			<p>Status: <?php echo esc_html( (string) $booking['status'] ); ?> · Reference <?php echo esc_html( (string) $booking['booking_ref'] ); ?></p>
			<dl class="mbss-summary">
				<div><dt>Total</dt><dd>$<?php echo esc_html( number_format( (float) $booking['total_amount'], 2 ) ); ?></dd></div>
				<div><dt>Balance</dt><dd>$<?php echo esc_html( number_format( (float) $booking['balance_due'], 2 ) ); ?></dd></div>
			</dl>
			<?php if ( ! empty( $booking['payments'] ) ) : ?>
				<ul class="mbss-schedule">
					<?php foreach ( $booking['payments'] as $payment ) : ?>
						<li>
							<span><?php echo esc_html( (string) ( $payment['label'] ?: $payment['payment_type'] ) ); ?></span>
							<span>$<?php echo esc_html( number_format( (float) $payment['amount'], 2 ) ); ?></span>
							<span><?php echo 'paid' === $payment['status'] ? 'Paid' : 'Due ' . esc_html( (string) $payment['due_on'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( 'confirmed' === $booking['status'] && (float) $booking['balance_due'] > 0 ) : ?>
				<form method="post">
					<?php wp_nonce_field( 'mbss_portal', 'mbss_portal_nonce' ); ?>
					<input type="hidden" name="mbss_portal_action" value="pay">
					<input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>">
					<input type="hidden" name="ref" value="<?php echo esc_attr( $ref ); ?>">
					<button class="mbss-btn" type="submit">
						<?php echo $stripe_ready ? 'Pay next amount with Stripe' : 'Record next amount (Stripe not connected)'; ?>
					</button>
				</form>
			<?php endif; ?>
		</article>
	<?php endif; ?>
</div>
