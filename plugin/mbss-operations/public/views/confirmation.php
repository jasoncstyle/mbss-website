<?php
/**
 * Booking receipt after checkout.
 *
 * @package MBSS_Operations
 *
 * @var array<string, mixed>|null $booking
 * @var bool $cancelled
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portal = home_url( '/my-booking/' );
if ( $booking ) {
	$portal = add_query_arg(
		array(
			'ref'   => rawurlencode( (string) $booking['booking_ref'] ),
			'email' => rawurlencode( (string) $booking['email'] ),
		),
		$portal
	);
}
?>
<div class="mbss-booking mbss-receipt">
	<?php if ( ! $booking ) : ?>
		<h2>We could not find that booking</h2>
		<p>Check the reference in your email, or look it up on the <a href="<?php echo esc_url( home_url( '/my-booking/' ) ); ?>">booking page</a>.</p>
	<?php elseif ( 'confirmed' === $booking['status'] ) : ?>
		<p class="mbss-booking__kicker">Reservation confirmed</p>
		<h2>Your seat is reserved</h2>
		<p class="mbss-receipt__ref"><?php echo esc_html( (string) $booking['booking_ref'] ); ?></p>
		<dl class="mbss-summary">
			<div><dt>Course</dt><dd><?php echo esc_html( (string) $booking['course_title'] ); ?></dd></div>
			<div><dt>Dates</dt><dd><?php echo esc_html( (string) $booking['date_label'] ); ?></dd></div>
			<div><dt>Deposit paid</dt><dd>$<?php echo esc_html( number_format( (float) $booking['deposit_amount'], 2 ) ); ?></dd></div>
			<div><dt>Balance</dt><dd>$<?php echo esc_html( number_format( (float) $booking['balance_due'], 2 ) ); ?>
				<?php if ( $booking['balance_due_on'] ) : ?>
					<span>due <?php echo esc_html( (string) $booking['balance_due_on'] ); ?></span>
				<?php endif; ?>
			</dd></div>
		</dl>
		<?php if ( ! empty( $booking['payments'] ) ) : ?>
			<h3>Payment schedule</h3>
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
		<p>A confirmation was sent to <?php echo esc_html( (string) $booking['email'] ); ?>.</p>
		<p><a class="mbss-btn" href="<?php echo esc_url( $portal ); ?>">View or pay your balance</a></p>
	<?php elseif ( ! empty( $cancelled ) ) : ?>
		<h2>Payment was not completed</h2>
		<p>Reference <?php echo esc_html( (string) $booking['booking_ref'] ); ?> is still holding your seat for a few minutes. Return to checkout from the booking page, or start again.</p>
		<p><a class="mbss-btn" href="<?php echo esc_url( home_url( '/book/' ) ); ?>">Back to dates</a></p>
	<?php else : ?>
		<h2>Reservation <?php echo esc_html( (string) $booking['booking_ref'] ); ?></h2>
		<p>Status: <?php echo esc_html( (string) $booking['status'] ); ?>. If you just paid, refresh this page in a moment while the payment is confirmed.</p>
		<p><a class="mbss-btn" href="<?php echo esc_url( $portal ); ?>">Open your booking</a></p>
	<?php endif; ?>
</div>
