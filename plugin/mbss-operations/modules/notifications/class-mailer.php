<?php
/**
 * Booking emails sent through WordPress mail.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Notifications;

final class Mailer {

	/**
	 * @param array<string, mixed> $booking
	 */
	public function send_booking_confirmation( array $booking ): void {
		$portal = add_query_arg(
			array(
				'ref'   => rawurlencode( (string) $booking['booking_ref'] ),
				'email' => rawurlencode( (string) $booking['email'] ),
			),
			home_url( '/my-booking/' )
		);
		$balance = (float) $booking['balance_due'];
		$balance_line = $balance > 0
			? '<p>Remaining balance: <strong>$' . esc_html( number_format( $balance, 2 ) ) . '</strong>'
				. ( $booking['balance_due_on'] ? ' due ' . esc_html( (string) $booking['balance_due_on'] ) : '' )
				. '. Pay it from your booking page when you are ready.</p>'
			: '<p>This booking is paid in full.</p>';

		$html = $this->wrap(
			'<p>Your seat is reserved.</p>
			<p><strong>' . esc_html( (string) $booking['course_title'] ) . '</strong><br>'
			. esc_html( (string) $booking['date_label'] ) . '</p>
			<p>Booking reference: <strong>' . esc_html( (string) $booking['booking_ref'] ) . '</strong></p>
			<p>Deposit paid: $' . esc_html( number_format( (float) $booking['deposit_amount'], 2 ) ) . '</p>'
			. $balance_line
			. '<p><a href="' . esc_url( $portal ) . '">View your booking</a></p>
			<p>Questions: <a href="tel:18432416874">843-241-6874</a> or reply to this email.</p>'
		);
		$this->send( (string) $booking['email'], 'Your Myrtle Beach Sailing School booking ' . $booking['booking_ref'], $html );
	}

	/**
	 * @param array<string, mixed> $booking
	 */
	public function send_admin_booking_notice( array $booking ): void {
		$to = (string) get_option( 'mbss_admin_notify_email', get_option( 'admin_email' ) );
		$html = $this->wrap(
			'<p>New confirmed booking <strong>' . esc_html( (string) $booking['booking_ref'] ) . '</strong>.</p>
			<p>' . esc_html( (string) $booking['first_name'] . ' ' . $booking['last_name'] ) . ' — ' . esc_html( (string) $booking['email'] ) . '<br>'
			. esc_html( (string) $booking['phone'] ) . '</p>
			<p>' . esc_html( (string) $booking['course_title'] ) . '<br>' . esc_html( (string) $booking['date_label'] ) . '</p>
			<p>Deposit $' . esc_html( number_format( (float) $booking['deposit_amount'], 2 ) )
			. ' · Balance $' . esc_html( number_format( (float) $booking['balance_due'], 2 ) ) . '</p>'
		);
		$this->send( $to, 'New booking ' . $booking['booking_ref'], $html );
	}

	/**
	 * @param array<string, mixed> $booking
	 */
	public function send_payment_receipt( array $booking, string $amount, string $label ): void {
		$html = $this->wrap(
			'<p>We received your payment of <strong>$' . esc_html( number_format( (float) $amount, 2 ) ) . '</strong> for '
			. esc_html( $label ) . '.</p>
			<p>' . esc_html( (string) $booking['course_title'] ) . ' · ' . esc_html( (string) $booking['booking_ref'] ) . '</p>
			<p>Remaining balance: $' . esc_html( number_format( (float) $booking['balance_due'], 2 ) ) . '</p>'
		);
		$this->send( (string) $booking['email'], 'Payment received for ' . $booking['booking_ref'], $html );
	}

	/**
	 * @param array<string, mixed> $person
	 * @param array<string, mixed> $session
	 */
	public function send_waitlist_opening( array $person, array $session ): void {
		$url = add_query_arg(
			array(
				'course'  => rawurlencode( (string) $session['slug'] ),
				'session' => (int) $session['id'],
			),
			home_url( '/book/' )
		);
		$html = $this->wrap(
			'<p>A seat opened for <strong>' . esc_html( (string) $session['title'] ) . '</strong>.</p>
			<p><a href="' . esc_url( $url ) . '">Reserve it now</a>. Open seats are not held for the waitlist.</p>'
		);
		$this->send( (string) $person['email'], 'A seat opened at Myrtle Beach Sailing School', $html );
	}

	private function wrap( string $body ): string {
		return '<div style="font-family:Inter,Arial,sans-serif;color:#2d2d2d;line-height:1.5">'
			. '<p style="color:#1a2f4a;font-weight:700">Myrtle Beach Sailing School</p>'
			. $body
			. '</div>';
	}

	private function send( string $to, string $subject, string $html ): void {
		if ( ! is_email( $to ) ) {
			return;
		}
		$content_type = static function (): string {
			return 'text/html';
		};
		add_filter( 'wp_mail_content_type', $content_type );
		wp_mail( $to, $subject, $html );
		remove_filter( 'wp_mail_content_type', $content_type );
	}
}
