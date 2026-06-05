<?php
/**
 * Course Finder block render (scaffold).
 *
 * @package MBSS_Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="mbss-course-finder" aria-label="<?php esc_attr_e( 'Find your course', 'mbss-theme' ); ?>">
	<h2><?php esc_html_e( 'Find the Right Course', 'mbss-theme' ); ?></h2>
	<p><?php esc_html_e( 'Scaffold placeholder — connect to MBSS Operations progression engine.', 'mbss-theme' ); ?></p>
	<form class="mbss-course-finder__form" data-mbss-course-finder>
		<fieldset>
			<legend><?php esc_html_e( 'Have you sailed before?', 'mbss-theme' ); ?></legend>
			<label><input type="radio" name="experience" value="no" /> <?php esc_html_e( 'No', 'mbss-theme' ); ?></label>
			<label><input type="radio" name="experience" value="yes" /> <?php esc_html_e( 'Yes', 'mbss-theme' ); ?></label>
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e( 'What is your goal?', 'mbss-theme' ); ?></legend>
			<select name="goal">
				<option value="learn"><?php esc_html_e( 'Learn to sail', 'mbss-theme' ); ?></option>
				<option value="cruise"><?php esc_html_e( 'Learn to cruise', 'mbss-theme' ); ?></option>
				<option value="charter"><?php esc_html_e( 'Charter a boat', 'mbss-theme' ); ?></option>
				<option value="catamaran"><?php esc_html_e( 'Learn catamarans', 'mbss-theme' ); ?></option>
			</select>
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e( 'Want a liveaboard experience?', 'mbss-theme' ); ?></legend>
			<label><input type="radio" name="liveaboard" value="yes" /> <?php esc_html_e( 'Yes', 'mbss-theme' ); ?></label>
			<label><input type="radio" name="liveaboard" value="no" /> <?php esc_html_e( 'No', 'mbss-theme' ); ?></label>
		</fieldset>
		<button type="submit" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Find Your Course', 'mbss-theme' ); ?></button>
	</form>
	<div class="mbss-course-finder__result" hidden></div>
</section>
