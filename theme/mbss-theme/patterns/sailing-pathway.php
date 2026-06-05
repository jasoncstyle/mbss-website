<?php
/**
 * Sailing pathway block pattern.
 *
 * @package MBSS_Theme
 */

return array(
	'title'       => __( 'Sailing Pathway', 'mbss-theme' ),
	'description' => __( 'ASA certification progression graphic placeholder.', 'mbss-theme' ),
	'categories'  => array( 'mbss-homepage', 'mbss-courses' ),
	'content'     => '<!-- wp:group {"className":"mbss-pathway","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"warm-sand","layout":{"type":"constrained"}} -->
<div class="wp-block-group mbss-pathway has-warm-sand-background-color has-background">
<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Your Sailing Pathway</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">START HERE → ASA 101 → ASA 103 → Choose Your Direction → ASA 102 / ASA 104 → Advanced Training → ASA 106 / ASA 114 → Online Endorsements</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">View Full Certification Path</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
);
