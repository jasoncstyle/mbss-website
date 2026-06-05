<?php
/**
 * Enqueue theme assets.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

add_action(
	'wp_enqueue_scripts',
	function (): void {
		wp_enqueue_style(
			'mbss-google-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'mbss-main',
			MBSS_THEME_URI . '/assets/css/main.css',
			array( 'mbss-google-fonts' ),
			MBSS_THEME_VERSION
		);

		wp_enqueue_script(
			'mbss-main',
			MBSS_THEME_URI . '/assets/js/main.js',
			array(),
			MBSS_THEME_VERSION,
			true
		);
	}
);

add_action(
	'enqueue_block_editor_assets',
	function (): void {
		wp_enqueue_style(
			'mbss-editor',
			MBSS_THEME_URI . '/assets/css/editor.css',
			array(),
			MBSS_THEME_VERSION
		);
	}
);
