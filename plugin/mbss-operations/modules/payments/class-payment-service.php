<?php
/**
 * Payment tracking (Stripe deposits + Square final invoices).
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Payments;

final class Payment_Service {

	public function record_deposit( int $booking_id, float $amount, string $stripe_id ): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mbss_payments',
			array(
				'booking_id'     => $booking_id,
				'amount'         => $amount,
				'payment_type'   => 'deposit',
				'payment_method' => 'stripe',
				'status'         => 'paid',
				'external_id'    => $stripe_id,
				'paid_at'        => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public function mark_invoice_sent( int $payment_id, string $square_invoice_number ): bool {
		global $wpdb;
		return (bool) $wpdb->update(
			$wpdb->prefix . 'mbss_payments',
			array(
				'invoice_sent_at' => current_time( 'mysql' ),
				'external_id'     => $square_invoice_number,
				'status'          => 'invoice_sent',
			),
			array( 'id' => $payment_id )
		);
	}
}
