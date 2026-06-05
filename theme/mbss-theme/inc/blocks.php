<?php
/**
 * Register custom Gutenberg blocks.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

add_action(
	'init',
	function (): void {
		if ( function_exists( 'register_block_category' ) ) {
			register_block_category(
				'mbss',
				array(
					'title' => __( 'MBSS', 'mbss-theme' ),
				)
			);
		}

		$blocks = array(
			'course-finder',
			'upcoming-courses',
			'pathway-graphic',
			'pricing-card',
		);

		foreach ( $blocks as $block ) {
			$block_dir = MBSS_THEME_DIR . '/blocks/' . $block;
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type( $block_dir );
			}
		}
	}
);
