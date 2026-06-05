<?php
/**
 * Page template helpers and scaffold page creation on theme activation.
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

/**
 * Pages to scaffold on first theme activation.
 *
 * @return array<string, array{title: string, parent?: string, template?: string}>
 */
function mbss_scaffold_pages(): array {
	return array(
		'learn-to-sail'              => array( 'title' => 'Learn to Sail' ),
		'learn-to-sail/asa-101'        => array( 'title' => 'ASA 101', 'parent' => 'learn-to-sail', 'template' => 'course' ),
		'learn-to-sail/asa-103'        => array( 'title' => 'ASA 103', 'parent' => 'learn-to-sail', 'template' => 'course' ),
		'advance-your-skills'          => array( 'title' => 'Advance Your Skills' ),
		'advance-your-skills/asa-102'  => array( 'title' => 'ASA 102', 'parent' => 'advance-your-skills', 'template' => 'course' ),
		'advance-your-skills/asa-104'  => array( 'title' => 'ASA 104', 'parent' => 'advance-your-skills', 'template' => 'course' ),
		'advance-your-skills/asa-106'  => array( 'title' => 'ASA 106', 'parent' => 'advance-your-skills', 'template' => 'course' ),
		'advance-your-skills/asa-114'  => array( 'title' => 'ASA 114', 'parent' => 'advance-your-skills', 'template' => 'course' ),
		'liveaboard-packages'          => array( 'title' => 'Liveaboard Packages' ),
		'liveaboard-packages/asa-101-103'       => array( 'title' => 'ASA 101 + 103 Package', 'parent' => 'liveaboard-packages', 'template' => 'liveaboard-package' ),
		'liveaboard-packages/asa-101-103-104'   => array( 'title' => 'ASA 101 + 103 + 104 Package', 'parent' => 'liveaboard-packages', 'template' => 'liveaboard-package' ),
		'liveaboard-packages/asa-103-104'       => array( 'title' => 'ASA 103 + 104 Package', 'parent' => 'liveaboard-packages', 'template' => 'liveaboard-package' ),
		'liveaboard-packages/asa-102-104'       => array( 'title' => 'ASA 102 + 104 Package', 'parent' => 'liveaboard-packages', 'template' => 'liveaboard-package' ),
		'liveaboard-packages/asa-104-114'       => array( 'title' => 'ASA 104 + 114 Package', 'parent' => 'liveaboard-packages', 'template' => 'liveaboard-package' ),
		'online-certifications'        => array( 'title' => 'Online Certifications' ),
		'about'                        => array( 'title' => 'About' ),
		'about/instructors'            => array( 'title' => 'Meet the Instructors', 'parent' => 'about' ),
		'about/fleet'                  => array( 'title' => 'Fleet', 'parent' => 'about' ),
		'about/safety'                 => array( 'title' => 'Safety & Training Philosophy', 'parent' => 'about' ),
		'why-mbss'                     => array( 'title' => 'Why Myrtle Beach Sailing School' ),
		'reviews'                      => array( 'title' => 'Reviews' ),
		'compare-courses'              => array( 'title' => 'Compare Courses' ),
		'book'                         => array( 'title' => 'Find Your Course', 'template' => 'booking' ),
		'resources'                    => array( 'title' => 'Resources' ),
		'resources/faq'                => array( 'title' => 'FAQ', 'parent' => 'resources' ),
	);

}

/**
 * Create scaffold pages if they do not exist.
 */
function mbss_create_scaffold_pages(): void {
	$pages   = mbss_scaffold_pages();
	$created = array();

	foreach ( $pages as $path => $config ) {
		$existing = get_page_by_path( $path );
		if ( $existing ) {
			$created[ $path ] = (int) $existing->ID;
			continue;
		}

		$parent_id = 0;
		if ( ! empty( $config['parent'] ) && isset( $created[ $config['parent'] ] ) ) {
			$parent_id = $created[ $config['parent'] ];
		}

		$post_id = wp_insert_post(
			array(
				'post_title'  => $config['title'],
				'post_name'   => basename( $path ),
				'post_status' => 'draft',
				'post_type'   => 'page',
				'post_parent' => $parent_id,
			),
			true
		);

		if ( ! is_wp_error( $post_id ) && ! empty( $config['template'] ) ) {
			update_post_meta( $post_id, '_wp_page_template', $config['template'] );
		}

		if ( ! is_wp_error( $post_id ) ) {
			$created[ $path ] = (int) $post_id;
		}
	}
}

add_action(
	'after_switch_theme',
	function (): void {
		mbss_create_scaffold_pages();
	}
);
