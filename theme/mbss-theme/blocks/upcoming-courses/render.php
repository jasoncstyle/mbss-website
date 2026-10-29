<?php
/**
 * Upcoming Courses block render (scaffold).
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sessions = array();

if ( class_exists( 'MBSS\\Modules\\Courses\\Course_Session_Repository' ) ) {
	$repo     = new MBSS\Modules\Courses\Course_Session_Repository();
	$sessions = $repo->get_upcoming( 10 );
}
?>
<section class="mbss-upcoming-courses" aria-label="<?php esc_attr_e( 'Upcoming courses', 'mbss-theme' ); ?>">
	<h2><?php esc_html_e( 'Upcoming Courses', 'mbss-theme' ); ?></h2>
	<?php if ( empty( $sessions ) ) : ?>
		<p><?php esc_html_e( 'No upcoming sessions yet. Connect MBSS Operations plugin.', 'mbss-theme' ); ?></p>
	<?php else : ?>
		<ul class="mbss-upcoming-courses__list">
			<?php foreach ( $sessions as $session ) : ?>
				<li class="mbss-upcoming-courses__card">
					<!-- Session cards rendered by plugin data -->
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
