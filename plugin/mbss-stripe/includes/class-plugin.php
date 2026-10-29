<?php
/**
 * Hooks the Stripe plugin into WordPress.
 *
 * @package MBSS_Stripe
 */

declare(strict_types=1);

namespace MBSS\StripePay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	public static function boot(): void {
		try {
			Installer::maybe_upgrade();
		} catch ( \Throwable $error ) {
			error_log( 'MBSS Stripe upgrade failed: ' . $error->getMessage() );
			add_action(
				'admin_notices',
				static function (): void {
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p>MBSS Stripe could not finish its database update. The rest of the site is unchanged.</p></div>';
				}
			);
			return;
		}

		( new Frontend() )->register();
		if ( is_admin() ) {
			( new Admin() )->register();
		}
		add_action( 'rest_api_init', array( self::class, 'register_webhook' ) );
	}

	public static function register_webhook(): void {
		register_rest_route(
			'mbss-stripe/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => static function ( \WP_REST_Request $request ) {
					return ( new Checkout_Service() )->handle_webhook( $request );
				},
				'permission_callback' => '__return_true',
			)
		);
	}
}
