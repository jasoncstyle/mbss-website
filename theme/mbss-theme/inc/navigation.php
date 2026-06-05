<?php
/**
 * Primary navigation fallback aligned to sitemap.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

/**
 * Default primary nav items when no menu is assigned.
 *
 * @return array<int, array{label: string, url: string}>
 */
function mbss_default_nav_items(): array {
	return array(
		array( 'label' => __( 'Home', 'mbss-theme' ), 'url' => home_url( '/' ) ),
		array( 'label' => __( 'Learn to Sail', 'mbss-theme' ), 'url' => home_url( '/learn-to-sail/' ) ),
		array( 'label' => __( 'Advance Your Skills', 'mbss-theme' ), 'url' => home_url( '/advance-your-skills/' ) ),
		array( 'label' => __( 'Liveaboard Packages', 'mbss-theme' ), 'url' => home_url( '/liveaboard-packages/' ) ),
		array( 'label' => __( 'Online Certifications', 'mbss-theme' ), 'url' => home_url( '/online-certifications/' ) ),
		array( 'label' => __( 'About', 'mbss-theme' ), 'url' => home_url( '/about/' ) ),
		array( 'label' => __( 'Resources', 'mbss-theme' ), 'url' => home_url( '/resources/' ) ),
		array( 'label' => __( 'Reviews', 'mbss-theme' ), 'url' => home_url( '/reviews/' ) ),
		array( 'label' => __( 'Book Now', 'mbss-theme' ), 'url' => home_url( '/book/' ) ),
	);
}
