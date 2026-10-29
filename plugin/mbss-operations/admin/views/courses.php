<?php
/**
 * Trip and course editor.
 *
 * @package MBSS_Operations
 *
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MBSS\Modules\Courses\Course_Repository;
use MBSS\Modules\Courses\Course_Session_Repository;

$repo     = new Course_Repository();
$courses  = $repo->get_all();
$selected = isset( $_GET['course_id'] ) ? $repo->find( absint( $_GET['course_id'] ) ) : null;
$sessions = ( new Course_Session_Repository() )->get_upcoming( 40 );
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'Trips and courses', 'mbss-operations' ); ?></h1>
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<table class="widefat striped">
		<thead><tr><th>Name</th><th>Path</th><th>Private / price</th><th>Shared</th><th>Deposit</th><th></th></tr></thead>
		<tbody>
		<?php foreach ( $courses as $course ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $course['title'] ); ?></td>
				<td><?php echo esc_html( (string) $course['path'] ); ?></td>
				<td>$<?php echo esc_html( number_format( (float) $course['base_price'], 2 ) ); ?></td>
				<td><?php echo null === $course['shared_price'] ? '—' : '$' . esc_html( number_format( (float) $course['shared_price'], 2 ) ); ?></td>
				<td><?php echo (float) $course['deposit_percent'] > 0 ? esc_html( (string) $course['deposit_percent'] ) . '%' : '$' . esc_html( number_format( (float) $course['deposit_amount'], 2 ) ); ?></td>
				<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mbss-courses', 'course_id' => (int) $course['id'] ), admin_url( 'admin.php' ) ) ); ?>">Edit</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $selected ) : ?>
		<h2><?php echo esc_html( (string) $selected['title'] ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'mbss_save_course' ); ?>
			<input type="hidden" name="action" value="mbss_save_course">
			<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected['id'] ); ?>">
			<table class="form-table">
				<tr><th>Price / private cabin</th><td><input name="base_price" type="number" step="0.01" value="<?php echo esc_attr( (string) $selected['base_price'] ); ?>"></td></tr>
				<tr><th>Shared cabin total</th><td><input name="shared_price" type="number" step="0.01" value="<?php echo esc_attr( (string) ( $selected['shared_price'] ?? '' ) ); ?>"><p class="description">Leave blank if this trip has one price.</p></td></tr>
				<tr><th>Fixed deposit</th><td><input name="deposit_amount" type="number" step="0.01" value="<?php echo esc_attr( (string) $selected['deposit_amount'] ); ?>"></td></tr>
				<tr><th>Deposit percent</th><td><input name="deposit_percent" type="number" step="0.01" value="<?php echo esc_attr( (string) $selected['deposit_percent'] ); ?>"><p class="description">Passages use 10. Leave 0 to use the fixed deposit.</p></td></tr>
				<tr><th>Private upgrade</th><td><input name="private_upgrade_price" type="number" step="0.01" value="<?php echo esc_attr( (string) $selected['private_upgrade_price'] ); ?>"></td></tr>
				<tr><th>Extra student</th><td><input name="extra_student_price" type="number" step="0.01" value="<?php echo esc_attr( (string) $selected['extra_student_price'] ); ?>"></td></tr>
				<tr><th>Listed</th><td><label><input type="checkbox" name="is_active" value="1" <?php checked( (int) $selected['is_active'], 1 ); ?>> Active</label></td></tr>
			</table>
			<?php submit_button( 'Save trip' ); ?>
		</form>

		<h3>Add a date</h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'mbss_add_session' ); ?>
			<input type="hidden" name="action" value="mbss_add_session">
			<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected['id'] ); ?>">
			<p>
				<label>Start <input type="date" name="start_date" required></label>
				<label>End <input type="date" name="end_date" required></label>
				<label>Cabins or seats <input type="number" name="capacity" min="1" value="3"></label>
				<label>Note <input type="text" name="notes"></label>
			</p>
			<?php submit_button( 'Add date' ); ?>
		</form>
	<?php endif; ?>

	<h2>Open dates</h2>
	<table class="widefat striped">
		<thead><tr><th>Trip</th><th>Dates</th><th>Open</th></tr></thead>
		<tbody>
		<?php foreach ( $sessions as $session ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $session['title'] ); ?></td>
				<td><?php echo esc_html( (string) $session['start_date'] ); ?> – <?php echo esc_html( (string) $session['end_date'] ); ?></td>
				<td><?php echo esc_html( (string) $session['seats_remaining'] ); ?> / <?php echo esc_html( (string) $session['capacity'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
