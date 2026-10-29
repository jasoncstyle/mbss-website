<?php
/**
 * Stripe keys, course prices, and payment list.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_mbss_stripe_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_mbss_stripe_save_product', array( $this, 'save_product' ) );
		add_action( 'admin_post_mbss_stripe_create_page', array( $this, 'create_page' ) );
	}

	public function menu(): void {
		add_menu_page(
			'MBSS Stripe',
			'MBSS Stripe',
			'manage_options',
			'mbss-stripe',
			array( $this, 'render_settings' ),
			'dashicons-money-alt',
			58
		);
		add_submenu_page( 'mbss-stripe', 'Lessons', 'Lessons', 'manage_options', 'mbss-stripe-lessons', array( $this, 'render_lessons' ) );
		add_submenu_page( 'mbss-stripe', 'Payments', 'Payments', 'manage_options', 'mbss-stripe-payments', array( $this, 'render_payments' ) );
	}

	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$days    = (int) get_option( 'mbss_stripe_balance_days', 14 );
		$page_id = (int) get_option( 'mbss_stripe_page_id', 0 );
		$ready   = ( new Stripe_Client() )->is_configured();
		$notice  = isset( $_GET['mbss_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['mbss_notice'] ) ) : '';
		echo '<div class="wrap">';
		echo '<h1>Stripe payments</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}
		echo '<p>The course price stays in WordPress. Stripe charges the deposit today, then the balance. ASA 118 docking uses its own deposit. Card numbers are not stored here.</p>';
		echo $ready ? '<p><strong>Stripe is connected.</strong></p>' : '<p><strong>Stripe is not connected.</strong> Paste a secret key that starts with sk_.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mbss_stripe_save_settings' );
		echo '<input type="hidden" name="action" value="mbss_stripe_save_settings">';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th scope="row"><label for="mbss-stripe-key">Secret key</label></th><td>';
		echo '<input id="mbss-stripe-key" class="regular-text" type="password" name="secret_key" value="" autocomplete="off" placeholder="' . ( $ready ? esc_attr( 'Saved — leave blank to keep' ) : '' ) . '">';
		echo '</td></tr>';
		echo '<tr><th scope="row"><label for="mbss-stripe-wh">Webhook secret</label></th><td>';
		echo '<input id="mbss-stripe-wh" class="regular-text" type="password" name="webhook_secret" value="" autocomplete="off" placeholder="' . ( get_option( 'mbss_stripe_webhook_secret' ) ? esc_attr( 'Saved — leave blank to keep' ) : '' ) . '">';
		echo '<p class="description">Endpoint: <code>' . esc_html( rest_url( 'mbss-stripe/v1/webhook' ) ) . '</code>. Listen for <code>checkout.session.completed</code>.</p>';
		echo '</td></tr>';
		echo '<tr><th scope="row"><label for="mbss-stripe-days">Days before the lesson</label></th><td>';
		echo '<input id="mbss-stripe-days" type="number" min="1" max="90" name="balance_days" value="' . esc_attr( (string) $days ) . '">';
		echo '<p class="description">The balance is due this many days before the lesson start date.</p>';
		echo '</td></tr>';
		echo '<tr><th scope="row"><label for="mbss-stripe-page">Checkout page</label></th><td>';
		wp_dropdown_pages(
			array(
				'name'              => 'page_id',
				'id'                => 'mbss-stripe-page',
				'show_option_none'  => 'Home page',
				'option_none_value' => '0',
				'selected'          => $page_id,
			)
		);
		echo '<p class="description">Put the shortcode <code>[mbss_stripe]</code> on that page. Stripe sends the customer back there after payment.</p>';
		echo '</td></tr>';
		echo '</tbody></table>';
		submit_button( 'Save Stripe settings' );
		echo '</form>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mbss_stripe_create_page' );
		echo '<input type="hidden" name="action" value="mbss_stripe_create_page">';
		submit_button( 'Create a draft checkout page', 'secondary' );
		echo '</form>';
		echo '</div>';
	}

	public function render_lessons(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$repository = new Repository();
		$edit_slug  = isset( $_GET['course'] ) ? sanitize_title( wp_unslash( $_GET['course'] ) ) : '';
		$editing    = $edit_slug ? $repository->product_by_slug( $edit_slug ) : null;
		$notice     = isset( $_GET['mbss_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['mbss_notice'] ) ) : '';
		echo '<div class="wrap"><h1>Lessons</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}
		echo '<p>Individual lessons use a $250 deposit. Liveaboard packages use $500, except ASA 102 + 104, which stays at the published $250. ASA 118 docking is $50. Online courses charge the full price.</p>';
		if ( $editing ) {
			echo '<h2>Edit ' . esc_html( (string) $editing['title'] ) . '</h2>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mbss_stripe_save_product' );
			echo '<input type="hidden" name="action" value="mbss_stripe_save_product">';
			echo '<input type="hidden" name="product_id" value="' . esc_attr( (string) $editing['id'] ) . '">';
			echo '<table class="form-table"><tbody>';
			echo '<tr><th scope="row">Course price</th><td><input name="price" value="' . esc_attr( number_format( (int) $editing['price_cents'] / 100, 2, '.', '' ) ) . '"></td></tr>';
			echo '<tr><th scope="row">Deposit sent to Stripe</th><td><input name="deposit" value="' . esc_attr( number_format( (int) $editing['deposit_cents'] / 100, 2, '.', '' ) ) . '"></td></tr>';
			echo '<tr><th scope="row">Available</th><td><label><input type="checkbox" name="active" value="1" ' . checked( (int) $editing['active'], 1, false ) . '> Show on the checkout page</label></td></tr>';
			echo '</tbody></table>';
			submit_button( 'Save lesson' );
			echo '</form>';
		}
		echo '<table class="widefat striped"><thead><tr><th>Course</th><th>Price</th><th>Deposit</th><th>Type</th><th></th></tr></thead><tbody>';
		foreach ( $repository->products( false ) as $product ) {
			$link = add_query_arg(
				array(
					'page'   => 'mbss-stripe-lessons',
					'course' => (string) $product['slug'],
				),
				admin_url( 'admin.php' )
			);
			echo '<tr>';
			echo '<td>' . esc_html( (string) $product['title'] ) . '</td>';
			echo '<td>' . esc_html( Quote::dollars( (int) $product['price_cents'] ) ) . '</td>';
			echo '<td>' . esc_html( Quote::dollars( (int) $product['deposit_cents'] ) ) . '</td>';
			echo '<td>' . esc_html( (string) $product['kind'] ) . '</td>';
			echo '<td><a href="' . esc_url( $link ) . '">Edit</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	public function render_payments(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>Payments</h1>';
		echo '<table class="widefat striped"><thead><tr><th>Reference</th><th>Course</th><th>Customer</th><th>Lesson</th><th>Deposit</th><th>Balance</th><th>Due</th><th>Status</th></tr></thead><tbody>';
		$rows = ( new Repository() )->recent_orders();
		if ( ! $rows ) {
			echo '<tr><td colspan="8">No payments yet.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr>';
			echo '<td>' . esc_html( (string) $row['order_ref'] ) . '</td>';
			echo '<td>' . esc_html( (string) ( $row['title'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) $row['customer_name'] ) . '<br>' . esc_html( (string) $row['email'] ) . '</td>';
			echo '<td>' . esc_html( (string) ( $row['lesson_date'] ?: '—' ) ) . '</td>';
			echo '<td>' . esc_html( Quote::dollars( (int) $row['deposit_cents'] ) ) . '</td>';
			echo '<td>' . esc_html( Quote::dollars( (int) $row['balance_cents'] ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $row['balance_due'] ?: '—' ) ) . '</td>';
			echo '<td>' . esc_html( (string) $row['status'] ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	public function save_settings(): void {
		$this->guard( 'mbss_stripe_save_settings' );
		$key = isset( $_POST['secret_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) ) : '';
		$wh  = isset( $_POST['webhook_secret'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ) ) ) : '';
		if ( '' !== $key ) {
			update_option( 'mbss_stripe_secret_key', $key );
		}
		if ( '' !== $wh ) {
			update_option( 'mbss_stripe_webhook_secret', $wh );
		}
		$days = isset( $_POST['balance_days'] ) ? (int) $_POST['balance_days'] : 14;
		update_option( 'mbss_stripe_balance_days', max( 1, min( 90, $days ) ) );
		update_option( 'mbss_stripe_page_id', isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0 );
		$this->back( 'mbss-stripe', 'Stripe settings saved.' );
	}

	public function save_product(): void {
		$this->guard( 'mbss_stripe_save_product' );
		$id      = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$price   = Quote::cents( isset( $_POST['price'] ) ? sanitize_text_field( wp_unslash( $_POST['price'] ) ) : '' );
		$deposit = Quote::cents( isset( $_POST['deposit'] ) ? sanitize_text_field( wp_unslash( $_POST['deposit'] ) ) : '' );
		if ( $id <= 0 || $price < 0 || $deposit < 0 ) {
			$this->back( 'mbss-stripe-lessons', 'Enter the price and deposit in dollars.' );
		}
		( new Repository() )->save_product( $id, $price, $deposit, empty( $_POST['active'] ) ? 0 : 1 );
		$this->back( 'mbss-stripe-lessons', 'Lesson saved.' );
	}

	public function create_page(): void {
		$this->guard( 'mbss_stripe_create_page' );
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Pay your course deposit',
				'post_name'    => 'pay-deposit',
				'post_content' => '[mbss_stripe]',
				'post_status'  => 'draft',
				'post_type'    => 'page',
			)
		);
		if ( is_int( $page_id ) && $page_id > 0 ) {
			update_option( 'mbss_stripe_page_id', $page_id );
		}
		$this->back( 'mbss-stripe', 'A draft page named Pay your course deposit is ready. Publish it when you want students to use it.' );
	}

	private function guard( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( 'You cannot change payment settings.' ) );
		}
		check_admin_referer( $action );
	}

	private function back( string $page, string $notice ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => $page,
					'mbss_notice' => rawurlencode( $notice ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
