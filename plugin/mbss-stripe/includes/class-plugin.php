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
		add_filter( 'plugin_row_meta', array( self::class, 'plugin_links' ), 10, 2 );
		add_action( 'rest_api_init', array( self::class, 'register_webhook' ) );
	}

	/**
	 * @param array<int, string> $links Existing plugin row links.
	 * @return array<int, string>
	 */
	public static function plugin_links( array $links, string $file ): array {
		if ( plugin_basename( MBSS_STRIPE_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="https://hundredairelabs.com" target="_blank" rel="noopener noreferrer">Hundredaire Labs</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=mbss-stripe-details' ) ) . '">Details</a>';
		return $links;
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
