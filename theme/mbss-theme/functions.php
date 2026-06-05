<?php
/**
 * MBSS Theme bootstrap.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBSS_THEME_VERSION', '0.1.0' );
define( 'MBSS_THEME_DIR', get_template_directory() );
define( 'MBSS_THEME_URI', get_template_directory_uri() );

require_once MBSS_THEME_DIR . '/inc/setup.php';
require_once MBSS_THEME_DIR . '/inc/assets.php';
require_once MBSS_THEME_DIR . '/inc/patterns.php';
require_once MBSS_THEME_DIR . '/inc/blocks.php';
require_once MBSS_THEME_DIR . '/inc/navigation.php';
require_once MBSS_THEME_DIR . '/inc/page-templates.php';
