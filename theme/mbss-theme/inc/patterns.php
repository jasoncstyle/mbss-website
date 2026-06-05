<?php
/**
 * Register block patterns for page sections.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

add_action(
	'init',
	function (): void {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		register_block_pattern_category(
			'mbss-homepage',
			array(
				'label' => __( 'MBSS Homepage', 'mbss-theme' ),
			)
		);

		register_block_pattern_category(
			'mbss-courses',
			array(
				'label' => __( 'MBSS Courses', 'mbss-theme' ),
			)
		);

		register_block_pattern_category(
			'mbss-packages',
			array(
				'label' => __( 'MBSS Liveaboard Packages', 'mbss-theme' ),
			)
		);

		$patterns = array(
			'hero',
			'choose-your-path',
			'sailing-pathway',
			'why-mbss',
			'final-cta',
		);

		foreach ( $patterns as $pattern ) {
			$file = MBSS_THEME_DIR . '/patterns/' . $pattern . '.php';
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$pattern_data = include $file;
			if ( is_array( $pattern_data ) ) {
				register_block_pattern(
					'mbss-theme/' . $pattern,
					$pattern_data
				);
			}
		}
	}
);
