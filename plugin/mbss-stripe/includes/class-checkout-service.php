<?php
/**
 * Creates Stripe Checkout sessions for the deposit and the balance.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Checkout_Service {

	private Repository $repository;

	private Stripe_Client $stripe;

	public function __construct( ?Repository $repository = null, ?Stripe_Client $stripe = null ) {
		$this->repository = $repository ?? new Repository();
		$this->stripe     = $stripe ?? new Stripe_Client();
	}

	/**
	 * @param array<string, string> $customer
	 * @return array{ok:bool,error?:string,url?:string,order_ref?:string}
	 */
	public function start_deposit( string $slug, ?string $lesson_date, array $customer ): array {
		$product = $this->repository->product_by_slug( $slug );
		if ( ! $product || (int) $product['active'] !== 1 ) {
			return array( 'ok' => false, 'error' => 'Choose a course from the list.' );
		}

		$person = $this->person( $customer );
		if ( isset( $person['error'] ) ) {
			return array( 'ok' => false, 'error' => $person['error'] );
		}

		$quote = $this->quote_for( $product, $lesson_date );
		if ( ! $quote['ok'] ) {
			return array( 'ok' => false, 'error' => $quote['error'] );
		}

		$kind = $quote['pay_in_full'] ? 'full' : 'deposit';
		return $this->open_checkout( $product, $quote, $person, $kind, $quote['charge_cents'] );
	}

	/**
	 * @return array{ok:bool,error?:string,url?:string,order_ref?:string}
	 */
	public function start_balance( string $ref, string $email ): array {
		$order = $this->repository->order_by_ref( strtoupper( trim( $ref ) ) );
		$email = sanitize_email( $email );
		if ( ! $order || strtolower( (string) $order['email'] ) !== strtolower( $email ) ) {
			return array( 'ok' => false, 'error' => 'No payment matches that email and reference.' );
		}
		if ( 'paid' === $order['status'] || (int) $order['balance_cents'] <= 0 ) {
			return array( 'ok' => false, 'error' => 'This course is already paid in full.' );
		}
		if ( 'deposit_paid' !== $order['status'] ) {
			return array( 'ok' => false, 'error' => 'Pay the deposit before the balance.' );
		}

		$product = $this->repository->product( (int) $order['product_id'] );
		if ( ! $product ) {
			return array( 'ok' => false, 'error' => 'That course is no longer available.' );
		}

		$quote = array(
			'pay_in_full'   => false,
			'price_cents'   => (int) $order['price_cents'],
			'deposit_cents' => (int) $order['deposit_cents'],
			'balance_cents' => (int) $order['balance_cents'],
			'balance_due'   => $order['balance_due'] ? (string) $order['balance_due'] : null,
			'lesson_date'   => $order['lesson_date'] ? (string) $order['lesson_date'] : null,
		);

		return $this->open_checkout(
			$product,
			$quote,
			array(
				'name'  => (string) $order['customer_name'],
				'email' => (string) $order['email'],
				'phone' => (string) $order['phone'],
			),
			'balance',
			(int) $order['balance_cents'],
			(int) $order['id'],
			(string) $order['order_ref']
		);
	}

	/**
	 * @return array{ok:bool,error?:string,order?:array<string,mixed>,duplicate?:bool}
	 */
	public function fulfill( string $session_id ): array {
		if ( ! preg_match( '/^cs_(test|live)_/', $session_id ) ) {
			return array( 'ok' => false, 'error' => 'That payment reference is not valid.' );
		}

		$already = $this->repository->payment_by_session( $session_id );
		if ( $already && 'paid' === $already['status'] ) {
			$order = $this->repository->order( (int) $already['order_id'] );
			return array( 'ok' => true, 'order' => $order, 'duplicate' => true );
		}

		$session = $this->stripe->retrieve_session( $session_id );
		if ( ! empty( $session['error'] ) || ( $session['payment_status'] ?? '' ) !== 'paid' ) {
			return array( 'ok' => false, 'error' => 'The payment is not complete yet.' );
		}

		$payment_id = (int) ( $session['metadata']['payment_id'] ?? 0 );
		$payment    = $payment_id > 0 ? $this->repository->payment( $payment_id ) : $already;
		if ( ! $payment ) {
			return array( 'ok' => false, 'error' => 'This payment is not on file.' );
		}
		if ( (int) ( $session['amount_total'] ?? -1 ) !== (int) $payment['amount_cents'] ) {
			return array( 'ok' => false, 'error' => 'The paid amount does not match this course.' );
		}
		if ( 'paid' === $payment['status'] ) {
			return array(
				'ok'        => true,
				'order'     => $this->repository->order( (int) $payment['order_id'] ),
				'duplicate' => true,
			);
		}

		$order = $this->repository->order( (int) $payment['order_id'] );
		if ( ! $order ) {
			return array( 'ok' => false, 'error' => 'This payment is not on file.' );
		}

		$next = 'deposit_paid';
		if ( 'deposit' !== $payment['kind'] || (int) $order['balance_cents'] === 0 ) {
			$next = 'paid';
		}

		$this->repository->attach_session( (int) $payment['id'], $session_id );
		$this->repository->mark_paid(
			(int) $payment['id'],
			(int) $order['id'],
			(string) ( $session['payment_intent'] ?? '' ),
			$next
		);

		$fresh = $this->repository->order( (int) $order['id'] );
		if ( $fresh ) {
			$this->notify( $fresh, (string) $payment['kind'] );
		}
		return array( 'ok' => true, 'order' => $fresh, 'duplicate' => false );
	}

	/**
	 * @param \WP_REST_Request $request Incoming Stripe event.
	 */
	public function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$payload = $request->get_body();
		$header  = (string) $request->get_header( 'stripe_signature' );
		if ( '' === $header ) {
			$header = (string) $request->get_header( 'Stripe-Signature' );
		}

		if ( ! Signature::verify( $payload, $header, $this->stripe->webhook_secret(), time() ) ) {
			return new \WP_REST_Response( array( 'ok' => false ), 400 );
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) || ( $event['type'] ?? '' ) !== 'checkout.session.completed' ) {
			return new \WP_REST_Response( array( 'received' => true ) );
		}

		$session_id = (string) ( $event['data']['object']['id'] ?? '' );
		$result     = $this->fulfill( $session_id );
		$code       = empty( $result['ok'] ) ? 422 : 200;
		return new \WP_REST_Response( array( 'ok' => ! empty( $result['ok'] ) ), $code );
	}

	/**
	 * @param array<string, mixed> $product
	 * @return array{ok:bool,error:string,charge_cents:int,price_cents:int,deposit_cents:int,balance_cents:int,balance_due:?string,pay_in_full:bool,lesson_date:?string}
	 */
	public function quote_for( array $product, ?string $lesson_date ): array {
		$pay_in_full = 'online' === ( $product['kind'] ?? '' );
		$days        = (int) get_option( 'mbss_stripe_balance_days', 14 );
		$days        = max( 1, min( 90, $days > 0 ? $days : 14 ) );
		$quote       = Quote::build(
			(int) $product['price_cents'],
			(int) $product['deposit_cents'],
			$pay_in_full,
			$lesson_date,
			wp_date( 'Y-m-d' ),
			$days
		);
		$quote['lesson_date'] = $pay_in_full ? null : $lesson_date;
		return $quote;
	}

	/**
	 * @param array<string, mixed>  $product
	 * @param array<string, mixed>  $quote
	 * @param array<string, string> $person
	 * @return array{ok:bool,error?:string,url?:string,order_ref?:string}
	 */
	private function open_checkout( array $product, array $quote, array $person, string $kind, int $amount, int $order_id = 0, string $ref = '' ): array {
		if ( ! $this->stripe->is_configured() ) {
			return array(
				'ok'    => false,
				'error' => 'Stripe is not connected yet. Add the secret key under MBSS Stripe before taking a payment.',
			);
		}

		$created = false;
		if ( $order_id <= 0 ) {
			$ref      = $this->reference();
			$order_id = $this->repository->insert_order(
				array(
					'order_ref'     => $ref,
					'product_id'    => (int) $product['id'],
					'lesson_date'   => $quote['lesson_date'],
					'customer_name' => $person['name'],
					'email'         => $person['email'],
					'phone'         => $person['phone'],
					'price_cents'   => (int) $quote['price_cents'],
					'deposit_cents' => (int) $quote['deposit_cents'],
					'balance_cents' => (int) $quote['balance_cents'],
					'balance_due'   => $quote['balance_due'],
				)
			);
			$created = true;
			if ( $order_id <= 0 ) {
				return array( 'ok' => false, 'error' => 'The payment could not be saved. Please try again.' );
			}
		}

		if ( ! $created ) {
			$this->repository->delete_pending_payment( $order_id, $kind );
		}
		$payment_id = $this->repository->insert_payment( $order_id, $kind, $amount );
		if ( $payment_id <= 0 ) {
			if ( $created ) {
				$this->repository->delete_pending_order( $order_id );
			}
			return array( 'ok' => false, 'error' => 'The payment could not be saved. Please try again.' );
		}

		$label = (string) $product['title'];
		if ( 'deposit' === $kind ) {
			$label .= ' deposit';
		} elseif ( 'balance' === $kind ) {
			$label .= ' balance';
		}

		$description = 'Myrtle Beach Sailing School';
		if ( ! empty( $quote['balance_cents'] ) && 'balance' !== $kind ) {
			$description = 'Balance of ' . Quote::dollars( (int) $quote['balance_cents'] );
			if ( ! empty( $quote['balance_due'] ) ) {
				$description .= ' is due ' . (string) $quote['balance_due'];
			}
		}

		$page    = $this->return_base();
		$success = $page . ( str_contains( $page, '?' ) ? '&' : '?' ) . 'mbss_stripe_session={CHECKOUT_SESSION_ID}';
		$cancel  = add_query_arg(
			'mbss_stripe_notice',
			rawurlencode( 'Payment was canceled. The course is not reserved until Stripe accepts the payment.' ),
			$page
		);

		$session = $this->stripe->create_session(
			array(
				'mode'                 => 'payment',
				'customer_email'       => $person['email'],
				'client_reference_id'  => $ref,
				'success_url'          => $success,
				'cancel_url'           => $cancel,
				'metadata'             => array(
					'order_ref'  => $ref,
					'payment_id' => (string) $payment_id,
					'kind'       => $kind,
				),
				'line_items'           => array(
					array(
						'quantity'   => 1,
						'price_data' => array(
							'currency'     => 'usd',
							'unit_amount'  => $amount,
							'product_data' => array(
								'name'        => $label,
								'description' => $description,
							),
						),
					),
				),
			)
		);

		if ( empty( $session['url'] ) ) {
			if ( $created ) {
				$this->repository->delete_pending_order( $order_id );
			}
			$message = (string) ( $session['error']['message'] ?? 'Stripe could not start the payment.' );
			return array( 'ok' => false, 'error' => $message );
		}

		$this->repository->attach_session( $payment_id, (string) $session['id'] );
		return array(
			'ok'        => true,
			'url'       => (string) $session['url'],
			'order_ref' => $ref,
		);
	}

	/**
	 * @param array<string, string> $customer
	 * @return array{name:string,email:string,phone:string}|array{error:string}
	 */
	private function person( array $customer ): array {
		$name  = sanitize_text_field( $customer['name'] ?? '' );
		$email = sanitize_email( $customer['email'] ?? '' );
		$phone = preg_replace( '/\D+/', '', (string) ( $customer['phone'] ?? '' ) );
		if ( strlen( $name ) < 2 || ! is_email( $email ) || strlen( (string) $phone ) < 10 ) {
			return array( 'error' => 'Enter your name, a valid email, and a phone number with at least 10 digits.' );
		}
		return array(
			'name'  => $name,
			'email' => $email,
			'phone' => (string) $phone,
		);
	}

	private function reference(): string {
		return 'MBSS-' . strtoupper( bin2hex( random_bytes( 3 ) ) );
	}

	public function return_base(): string {
		$page_id = (int) get_option( 'mbss_stripe_page_id', 0 );
		if ( $page_id > 0 ) {
			$link = get_permalink( $page_id );
			if ( is_string( $link ) && '' !== $link ) {
				return $link;
			}
		}
		return home_url( '/' );
	}

	/**
	 * @param array<string, mixed> $order
	 */
	private function notify( array $order, string $kind ): void {
		$product = $this->repository->product( (int) $order['product_id'] );
		$title   = $product['title'] ?? 'your course';
		$ref     = (string) $order['order_ref'];
		$pay     = add_query_arg(
			array(
				'mbss_ref'   => rawurlencode( $ref ),
				'mbss_email' => rawurlencode( (string) $order['email'] ),
			),
			$this->return_base()
		);

		if ( 'balance' === $kind || 'full' === $kind || 'paid' === $order['status'] ) {
			$subject = 'Payment received for ' . $title;
			$body    = '<p>Reference ' . esc_html( $ref ) . ' is paid.</p>';
		} else {
			$subject = 'Deposit received for ' . $title;
			$body    = '<p>Reference ' . esc_html( $ref ) . '.</p>';
			$body   .= '<p>Deposit paid: ' . esc_html( Quote::dollars( (int) $order['deposit_cents'] ) ) . '.</p>';
			if ( (int) $order['balance_cents'] > 0 ) {
				$body .= '<p>Balance of ' . esc_html( Quote::dollars( (int) $order['balance_cents'] ) );
				if ( ! empty( $order['balance_due'] ) ) {
					$body .= ' is due ' . esc_html( (string) $order['balance_due'] );
				}
				$body .= '.</p><p><a href="' . esc_url( $pay ) . '">Pay the balance with Stripe</a></p>';
			}
		}

		wp_mail(
			(string) $order['email'],
			$subject,
			$body,
			array( 'Content-Type: text/html; charset=UTF-8' )
		);
	}
}
