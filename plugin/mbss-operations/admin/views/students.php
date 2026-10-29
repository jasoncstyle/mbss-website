<?php
/**
 * Student records.
 *
 * @package MBSS_Operations
 *
 * @var string $notice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MBSS\Modules\Students\Student_Repository;

$repo    = new Student_Repository();
$term    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$detail  = isset( $_GET['student_id'] ) ? $repo->get_with_history( absint( $_GET['student_id'] ) ) : null;
$students = $repo->search( $term );
?>
<div class="wrap mbss-admin">
	<h1><?php esc_html_e( 'Students', 'mbss-operations' ); ?></h1>
	<form method="get">
		<input type="hidden" name="page" value="mbss-students">
		<input type="search" name="s" value="<?php echo esc_attr( $term ); ?>" placeholder="Name, email, or phone">
		<?php submit_button( 'Search', 'secondary', '', false ); ?>
	</form>
	<table class="widefat striped">
		<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Stage</th></tr></thead>
		<tbody>
		<?php foreach ( $students as $student ) : ?>
			<tr>
				<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mbss-students', 'student_id' => (int) $student['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $student['first_name'] . ' ' . $student['last_name'] ); ?></a></td>
				<td><?php echo esc_html( (string) $student['email'] ); ?></td>
				<td><?php echo esc_html( (string) $student['phone'] ); ?></td>
				<td><?php echo esc_html( (string) $student['lifecycle_stage'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ( $detail ) : ?>
		<h2><?php echo esc_html( $detail['first_name'] . ' ' . $detail['last_name'] ); ?></h2>
		<p><?php echo esc_html( (string) $detail['email'] ); ?> · <?php echo esc_html( (string) $detail['phone'] ); ?></p>
		<p>Emergency: <?php echo esc_html( (string) $detail['emergency_contact'] ); ?> <?php echo esc_html( (string) $detail['emergency_phone'] ); ?></p>
		<h3>Reservations</h3>
		<ul>
			<?php foreach ( $detail['bookings'] as $booking ) : ?>
				<li><?php echo esc_html( (string) $booking['booking_ref'] ); ?> — <?php echo esc_html( (string) $booking['course_title'] ); ?> (<?php echo esc_html( (string) $booking['status'] ); ?>)</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
