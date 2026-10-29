<?php
/**
 * Orders, payments, and course rows.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Repository {

	public function products( bool $active_only = true ): array {
		global $wpdb;
		$table = $this->table( 'products' );
		$sql   = "SELECT * FROM {$table}";
		if ( $active_only ) {
			$sql .= ' WHERE active = 1';
		}
		$sql .= ' ORDER BY title ASC';
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	public function product_by_slug( string $slug ): ?array {
		global $wpdb;
		$table = $this->table( 'products' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function product( int $id ): ?array {
		global $wpdb;
		$table = $this->table( 'products' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function save_product( int $id, int $price_cents, int $deposit_cents, int $active ): bool {
		global $wpdb;
		$updated = $wpdb->update(
			$this->table( 'products' ),
			array(
				'price_cents'   => $price_cents,
				'deposit_cents' => $deposit_cents,
				'active'        => $active ? 1 : 0,
			),
			array( 'id' => $id ),
			array( '%d', '%d', '%d' ),
			array( '%d' )
		);
		return false !== $updated;
	}

	public function insert_order( array $row ): int {
		global $wpdb;
		$ok = $wpdb->insert(
			$this->table( 'orders' ),
			array(
				'order_ref'     => $row['order_ref'],
				'product_id'    => $row['product_id'],
				'lesson_date'   => $row['lesson_date'],
				'customer_name' => $row['customer_name'],
				'email'         => $row['email'],
				'phone'         => $row['phone'],
				'price_cents'   => $row['price_cents'],
				'deposit_cents' => $row['deposit_cents'],
				'balance_cents' => $row['balance_cents'],
				'balance_due'   => $row['balance_due'],
				'status'        => 'pending',
				'created_at'    => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public function insert_payment( int $order_id, string $kind, int $amount_cents ): int {
		global $wpdb;
		$ok = $wpdb->insert(
			$this->table( 'payments' ),
			array(
				'order_id'     => $order_id,
				'kind'         => $kind,
				'amount_cents' => $amount_cents,
				'status'       => 'pending',
			),
			array( '%d', '%s', '%d', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public function attach_session( int $payment_id, string $session_id ): void {
		global $wpdb;
		$wpdb->update(
			$this->table( 'payments' ),
			array( 'stripe_session' => $session_id ),
			array( 'id' => $payment_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function delete_pending_payment( int $order_id, string $kind ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $this->table( 'payments' ) . " WHERE order_id = %d AND kind = %s AND status = 'pending'",
				$order_id,
				$kind
			)
		);
	}

	public function delete_pending_order( int $order_id ): void {
		global $wpdb;
		$orders   = $this->table( 'orders' );
		$payments = $this->table( 'payments' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$payments} WHERE order_id = %d AND status = 'pending'", $order_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$orders} WHERE id = %d AND status = 'pending'", $order_id ) );
	}

	public function order( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $this->table( 'orders' ) . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function order_by_ref( string $ref ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $this->table( 'orders' ) . ' WHERE order_ref = %s', $ref ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function payment( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $this->table( 'payments' ) . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function payment_by_session( string $session_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $this->table( 'payments' ) . ' WHERE stripe_session = %s', $session_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function mark_paid( int $payment_id, int $order_id, string $intent, string $next_status ): void {
		global $wpdb;
		$wpdb->update(
			$this->table( 'payments' ),
			array(
				'status'         => 'paid',
				'stripe_intent'  => $intent,
				'paid_at'        => current_time( 'mysql', true ),
			),
			array( 'id' => $payment_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		$wpdb->update(
			$this->table( 'orders' ),
			array( 'status' => $next_status ),
			array( 'id' => $order_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function recent_orders( int $limit = 40 ): array {
		global $wpdb;
		$orders   = $this->table( 'orders' );
		$products = $this->table( 'products' );
		$limit    = max( 1, min( 100, $limit ) );
		$sql      = "SELECT o.*, p.title FROM {$orders} o LEFT JOIN {$products} p ON p.id = o.product_id ORDER BY o.id DESC LIMIT {$limit}";
		$rows     = $wpdb->get_results( $sql, ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	private function table( string $name ): string {
		global $wpdb;
		$map = array(
			'products' => $wpdb->prefix . 'mbss_stripe_products',
			'orders'   => $wpdb->prefix . 'mbss_stripe_orders',
			'payments' => $wpdb->prefix . 'mbss_stripe_payments',
		);
		return $map[ $name ];
	}
}
