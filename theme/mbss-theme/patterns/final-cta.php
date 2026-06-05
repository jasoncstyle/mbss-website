<?php
/**
 * Final CTA block pattern.
 *
 * @package MBSS_Theme
 */

return array(
	'title'       => __( 'Final CTA', 'mbss-theme' ),
	'description' => __( 'Homepage closing call to action.', 'mbss-theme' ),
	'categories'  => array( 'mbss-homepage' ),
	'content'     => '<!-- wp:group {"className":"mbss-final-cta","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"ocean-navy","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group mbss-final-cta has-white-color has-ocean-navy-background-color has-text-color has-background">
<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Where Your Sailing Journey Begins</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Whether you\'re learning to sail for the first time or preparing for your next cruising adventure, Myrtle Beach Sailing School provides the training and experience to help you reach your goals.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Find Your Course</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
);
