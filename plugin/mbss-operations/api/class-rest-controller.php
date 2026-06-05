<?php
/**
 * REST API endpoints (API-ready scaffold).
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Api;

final class Rest_Controller {

	private const NAMESPACE = 'mbss/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/sessions/upcoming',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_upcoming_sessions' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/course-finder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'course_finder' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_upcoming_sessions( \WP_REST_Request $request ): \WP_REST_Response {
		if ( class_exists( 'MBSS\\Modules\\Courses\\Course_Session_Repository' ) ) {
			$repo = new \MBSS\Modules\Courses\Course_Session_Repository();
			return new \WP_REST_Response( $repo->get_upcoming( (int) ( $request['limit'] ?? 10 ) ) );
		}
		return new \WP_REST_Response( array() );
	}

	public function course_finder( \WP_REST_Request $request ): \WP_REST_Response {
		if ( class_exists( 'MBSS\\Modules\\Progression\\Progression_Engine' ) ) {
			$engine = new \MBSS\Modules\Progression\Progression_Engine();
			return new \WP_REST_Response(
				$engine->recommend(
					array(
						'experience'  => sanitize_text_field( $request->get_param( 'experience' ) ?? '' ),
						'goal'        => sanitize_text_field( $request->get_param( 'goal' ) ?? '' ),
						'liveaboard'  => sanitize_text_field( $request->get_param( 'liveaboard' ) ?? '' ),
					)
				)
			);
		}
		return new \WP_REST_Response( array( 'course' => 'asa-101', 'message' => 'Scaffold default' ) );
	}
}
