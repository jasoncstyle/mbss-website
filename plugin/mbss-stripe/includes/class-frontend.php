<?php
/**
 * Public deposit form and Stripe return receipt.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	/** @var array<string, mixed>|null */
	private static ?array $receipt = null;

	private static bool $notice_shown = false;

	public function register(): void {
		add_shortcode( 'mbss_stripe', array( $this, 'render' ) );
		add_action( 'admin_post_nopriv_mbss_stripe_start', array( $this, 'start' ) );
		add_action( 'admin_post_mbss_stripe_start', array( $this, 'start' ) );
		add_action( 'admin_post_nopriv_mbss_stripe_balance', array( $this, 'balance' ) );
		add_action( 'admin_post_mbss_stripe_balance', array( $this, 'balance' ) );
		add_action( 'template_redirect', array( $this, 'maybe_fulfill' ) );
		add_filter( 'the_content', array( $this, 'prepend_status' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		if ( ! $this->page_uses_shortcode() ) {
			return;
		}
		wp_enqueue_style( 'mbss-stripe', MBSS_STRIPE_URL . 'assets/pay.css', array(), MBSS_STRIPE_VERSION );
		wp_enqueue_script( 'mbss-stripe', MBSS_STRIPE_URL . 'assets/pay.js', array(), MBSS_STRIPE_VERSION, true );
		wp_localize_script(
			'mbss-stripe',
			'mbssStripe',
			array(
				'balanceDays' => max( 1, min( 90, (int) get_option( 'mbss_stripe_balance_days', 14 ) ) ),
			)
		);
	}

	public function maybe_fulfill(): void {
		if ( empty( $_GET['mbss_stripe_session'] ) ) {
			return;
		}
		$session_id = sanitize_text_field( wp_unslash( $_GET['mbss_stripe_session'] ) );
		self::$receipt = ( new Checkout_Service() )->fulfill( $session_id );
	}

	public function start(): void {
		$this->guard_post( 'mbss_stripe_start' );
		$date = isset( $_POST['lesson_date'] ) ? sanitize_text_field( wp_unslash( $_POST['lesson_date'] ) ) : '';
		$result = ( new Checkout_Service() )->start_deposit(
			isset( $_POST['course'] ) ? sanitize_title( wp_unslash( $_POST['course'] ) ) : '',
			'' === $date ? null : $date,
			array(
				'name'  => isset( $_POST['customer_name'] ) ? wp_unslash( $_POST['customer_name'] ) : '',
				'email' => isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '',
				'phone' => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
			)
		);
		$this->finish( $result );
	}

	public function balance(): void {
		$this->guard_post( 'mbss_stripe_balance' );
		$result = ( new Checkout_Service() )->start_balance(
			isset( $_POST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['order_ref'] ) ) : '',
			isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : ''
		);
		$this->finish( $result );
	}

	public function render(): string {
		$this->enqueue();
		$repository = new Repository();
		$products   = $repository->products( true );
		$ref        = isset( $_GET['mbss_ref'] ) ? sanitize_text_field( wp_unslash( $_GET['mbss_ref'] ) ) : '';
		$email      = isset( $_GET['mbss_email'] ) ? sanitize_email( wp_unslash( $_GET['mbss_email'] ) ) : '';
		$days       = max( 1, min( 90, (int) get_option( 'mbss_stripe_balance_days', 14 ) ) );

		ob_start();
		echo '<div class="mbss-stripe">';
		if ( self::$receipt ) {
			$this->render_receipt( self::$receipt );
		}
		echo '<form class="mbss-stripe__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mbss_stripe_start', 'mbss_stripe_nonce' );
		echo '<input type="hidden" name="action" value="mbss_stripe_start">';
		echo '<p class="mbss-stripe__hp"><label>Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label></p>';
		echo '<label>Course<select name="course" id="mbss-stripe-course" required>';
		echo '<option value="">Select a course</option>';
		foreach ( $products as $product ) {
			$online = 'online' === $product['kind'];
			echo '<option value="' . esc_attr( (string) $product['slug'] ) . '"';
			echo ' data-price="' . esc_attr( (string) $product['price_cents'] ) . '"';
			echo ' data-deposit="' . esc_attr( (string) $product['deposit_cents'] ) . '"';
			echo ' data-online="' . ( $online ? '1' : '0' ) . '">';
			echo esc_html( (string) $product['title'] . ' — ' . Quote::dollars( (int) $product['price_cents'] ) );
			echo '</option>';
		}
		echo '</select></label>';
		echo '<label id="mbss-stripe-date-label">Lesson start date<input type="date" name="lesson_date" id="mbss-stripe-date" required min="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></label>';
		echo '<label>Name<input type="text" name="customer_name" required autocomplete="name"></label>';
		echo '<label>Email<input type="email" name="email" required autocomplete="email"></label>';
		echo '<label>Phone<input type="tel" name="phone" required autocomplete="tel"></label>';
		echo '<div class="mbss-stripe__summary" id="mbss-stripe-summary" hidden>';
		echo '<p id="mbss-stripe-due"></p>';
		echo '<p id="mbss-stripe-later"></p>';
		echo '</div>';
		echo '<button type="submit" class="mbss-stripe__button">Continue to Stripe</button>';
		echo '<p class="mbss-stripe__fine">The amount shown above is what Stripe charges. Card numbers stay on Stripe. A lesson balance is due ' . esc_html( (string) $days ) . ' days before the start date. ASA 118 docking uses a $50 deposit.</p>';
		echo '</form>';

		echo '<form class="mbss-stripe__form mbss-stripe__balance" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<h2>Pay a balance</h2>';
		wp_nonce_field( 'mbss_stripe_balance', 'mbss_stripe_nonce' );
		echo '<input type="hidden" name="action" value="mbss_stripe_balance">';
		echo '<p class="mbss-stripe__hp"><label>Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label></p>';
		echo '<label>Email<input type="email" name="email" required value="' . esc_attr( $email ) . '"></label>';
		echo '<label>Reference<input type="text" name="order_ref" required value="' . esc_attr( $ref ) . '" placeholder="MBSS-"></label>';
		echo '<button type="submit" class="mbss-stripe__button">Pay balance with Stripe</button>';
		echo '</form>';
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $result
	 */
	private function render_receipt( array $result ): void {
		if ( empty( $result['ok'] ) || empty( $result['order'] ) || ! is_array( $result['order'] ) ) {
			echo '<p class="mbss-stripe__notice" role="alert">' . esc_html( (string) ( $result['error'] ?? 'The payment could not be confirmed.' ) ) . '</p>';
			return;
		}
		$order   = $result['order'];
		$product = ( new Repository() )->product( (int) $order['product_id'] );
		$title   = $product['title'] ?? 'Course';
		echo '<section class="mbss-stripe__receipt">';
		echo '<h2>Payment received</h2>';
		echo '<p>' . esc_html( (string) $title ) . '</p>';
		echo '<p>Reference <strong>' . esc_html( (string) $order['order_ref'] ) . '</strong></p>';
		echo '<p>Course price ' . esc_html( Quote::dollars( (int) $order['price_cents'] ) ) . '</p>';
		echo '<p>Deposit ' . esc_html( Quote::dollars( (int) $order['deposit_cents'] ) ) . '</p>';
		if ( (int) $order['balance_cents'] > 0 && 'paid' !== $order['status'] ) {
			echo '<p>Balance ' . esc_html( Quote::dollars( (int) $order['balance_cents'] ) );
			if ( ! empty( $order['balance_due'] ) ) {
				echo ' due ' . esc_html( (string) $order['balance_due'] );
			}
			echo '</p>';
		} else {
			echo '<p>Paid in full.</p>';
		}
		echo '</section>';
	}

	public function prepend_status( string $content ): string {
		$extra = '';
		if ( ! self::$notice_shown ) {
			$notice = $this->notice_text();
			if ( $notice ) {
				$extra            .= '<p class="mbss-stripe__notice" role="alert">' . esc_html( $notice ) . '</p>';
				self::$notice_shown = true;
			}
		}
		if ( self::$receipt && ! $this->page_uses_shortcode() ) {
			ob_start();
			echo '<div class="mbss-stripe">';
			$this->render_receipt( self::$receipt );
			echo '</div>';
			$extra .= (string) ob_get_clean();
			self::$receipt = null;
		}
		return $extra . $content;
	}

	private function page_uses_shortcode(): bool {
		$post = get_post();
		return $post instanceof \WP_Post && has_shortcode( (string) $post->post_content, 'mbss_stripe' );
	}

	private function notice_text(): string {
		if ( empty( $_GET['mbss_stripe_notice'] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( $_GET['mbss_stripe_notice'] ) );
	}

	private function guard_post( string $action ): void {
		$nonce = isset( $_POST['mbss_stripe_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mbss_stripe_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			$this->back( 'The form expired. Reload the page and try again.' );
		}
		if ( ! empty( $_POST['company'] ) ) {
			$this->back( 'The payment could not be started.' );
		}
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		$key   = 'mbss_stripe_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count > 30 ) {
			$this->back( 'Too many attempts. Please wait and try again.' );
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	/**
	 * @param array{ok:bool,error?:string,url?:string} $result
	 */
	private function finish( array $result ): void {
		if ( ! empty( $result['ok'] ) && ! empty( $result['url'] ) ) {
			wp_redirect( $result['url'] );
			exit;
		}
		$this->back( (string) ( $result['error'] ?? 'The payment could not be started.' ) );
	}

	private function back( string $message ): void {
		$target = wp_get_referer();
		if ( ! is_string( $target ) || '' === $target ) {
			$target = ( new Checkout_Service() )->return_base();
		}
		wp_safe_redirect( add_query_arg( 'mbss_stripe_notice', rawurlencode( $message ), $target ) );
		exit;
	}
}
