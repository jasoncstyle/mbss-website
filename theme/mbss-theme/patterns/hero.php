<?php
/**
 * Hero block pattern.
 *
 * @package MBSS_Theme
 */

return array(
	'title'       => __( 'MBSS Hero', 'mbss-theme' ),
	'description' => __( 'Homepage hero with headline, CTAs, and trust bar.', 'mbss-theme' ),
	'categories'  => array( 'mbss-homepage' ),
	'content'     => '<!-- wp:group {"className":"mbss-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"ocean-navy","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group mbss-hero has-white-color has-ocean-navy-background-color has-text-color has-background">
<!-- wp:heading {"level":1,"fontSize":"hero"} -->
<h1 class="wp-block-heading has-hero-font-size">ASA Sailing Lessons &amp; Certifications</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Learn to sail through a proven certification pathway while gaining real-world coastal and ocean sailing experience.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Find Your Course</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">View Upcoming Courses</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">ASA Certified School · Beginner Friendly · Small Class Sizes · Myrtle Beach, SC · Real Coastal &amp; Ocean Sailing Experience</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
);
