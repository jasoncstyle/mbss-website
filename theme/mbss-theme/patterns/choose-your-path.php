<?php
/**
 * Choose Your Path block pattern.
 *
 * @package MBSS_Theme
 */

return array(
	'title'       => __( 'Choose Your Path', 'mbss-theme' ),
	'description' => __( 'Four pathway cards for homepage.', 'mbss-theme' ),
	'categories'  => array( 'mbss-homepage' ),
	'content'     => '<!-- wp:group {"className":"mbss-choose-path","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group mbss-choose-path">
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose Your Path</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Learn to Sail</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>ASA 101 &amp; 103 — Start your sailing journey.</p>
<!-- /wp:paragraph -->
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Explore Courses</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Advance Your Skills</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>ASA 102, 104, 106 &amp; 114 — Build cruising confidence.</p>
<!-- /wp:paragraph -->
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">View Courses</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Liveaboard Packages</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Earn multiple certifications while living aboard.</p>
<!-- /wp:paragraph -->
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Explore Packages</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Online Certifications</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Flexible online learning.</p>
<!-- /wp:paragraph -->
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">View Online Courses</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
);
