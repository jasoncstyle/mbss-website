<?php
/**
 * Primary navigation — classic menu (Appearance → Menus) with correct order.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

/**
 * Sitemap order for primary navigation.
 *
 * @return array<int, array{label: string, url: string}>
 */
function mbss_default_nav_items(): array {
	return array(
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

/**
 * Create and assign primary menu on first theme activation.
 */
function mbss_setup_primary_menu(): void {
	$menu_name = 'Primary Navigation';
	$location  = 'primary';
	$locations = get_theme_mod( 'nav_menu_locations', array() );

	if ( ! empty( $locations[ $location ] ) ) {
		return;
	}

	$menu = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
	} else {
		$menu_id = (int) $menu->term_id;
	}

	$existing_items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $existing_items ) ) {
		$position = 0;
		foreach ( mbss_default_nav_items() as $item ) {
			++$position;
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $item['label'],
					'menu-item-url'    => $item['url'],
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
					'menu-item-position' => $position,
				)
			);
		}
	}

	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Render primary nav markup (used by block filter and fallback).
 */
function mbss_render_primary_nav(): string {
	ob_start();

	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => 'nav',
				'container_class'=> 'mbss-primary-nav',
				'menu_class'     => 'mbss-primary-nav__list',
				'fallback_cb'    => false,
				'depth'          => 1,
			)
		);
	} else {
		echo '<nav class="mbss-primary-nav" aria-label="' . esc_attr__( 'Primary', 'mbss-theme' ) . '">';
		echo '<ul class="mbss-primary-nav__list">';
		foreach ( mbss_default_nav_items() as $item ) {
			printf(
				'<li class="mbss-primary-nav__item"><a href="%s">%s</a></li>',
				esc_url( $item['url'] ),
				esc_html( $item['label'] )
			);
		}
		echo '</ul></nav>';
	}

	return (string) ob_get_clean();
}

add_action(
	'after_switch_theme',
	function (): void {
		mbss_setup_primary_menu();
	}
);

/**
 * Replace header Navigation block with classic menu (respects Appearance → Menus order).
 */
add_filter(
	'render_block',
	function ( string $block_content, array $block ): string {
		if ( 'core/navigation' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		$class = $block['attrs']['className'] ?? '';
		if ( ! str_contains( $class, 'mbss-primary-nav-block' ) ) {
			return $block_content;
		}

		return mbss_render_primary_nav();
	},
	10,
	2
);
