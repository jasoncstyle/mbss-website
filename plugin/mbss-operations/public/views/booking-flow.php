<?php
/**
 * Booking flow view (8-step scaffold).
 *
 * @package MBSS_Operations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$steps = array(
	__( 'Choose Your Path', 'mbss-operations' ),
	__( 'Choose Course', 'mbss-operations' ),
	__( 'Choose Date', 'mbss-operations' ),
	__( 'Summary', 'mbss-operations' ),
	__( 'Your Information', 'mbss-operations' ),
	__( 'Upgrades', 'mbss-operations' ),
	__( 'Waiver', 'mbss-operations' ),
	__( 'Deposit Payment', 'mbss-operations' ),
);
?>
<div class="mbss-booking-flow" data-mbss-booking-flow>
	<ol class="mbss-booking-flow__steps">
		<?php foreach ( $steps as $i => $label ) : ?>
			<li class="mbss-booking-flow__step <?php echo 0 === $i ? 'is-active' : ''; ?>">
				<span class="mbss-booking-flow__step-num"><?php echo esc_html( (string) ( $i + 1 ) ); ?></span>
				<?php echo esc_html( $label ); ?>
			</li>
		<?php endforeach; ?>
	</ol>

	<div class="mbss-booking-flow__panel">
		<p><?php esc_html_e( 'Booking flow scaffold — implement 8 steps per docs/07-booking-system.md', 'mbss-operations' ); ?></p>

		<div class="mbss-booking-flow__paths">
			<button type="button" class="mbss-path-card"><?php esc_html_e( 'Learn to Sail', 'mbss-operations' ); ?></button>
			<button type="button" class="mbss-path-card"><?php esc_html_e( 'Advance Your Skills', 'mbss-operations' ); ?></button>
			<button type="button" class="mbss-path-card"><?php esc_html_e( 'Liveaboard Packages', 'mbss-operations' ); ?></button>
			<button type="button" class="mbss-path-card"><?php esc_html_e( 'Online Certifications', 'mbss-operations' ); ?></button>
		</div>
	</div>
</div>
