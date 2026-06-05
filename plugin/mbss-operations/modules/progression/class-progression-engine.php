<?php
/**
 * Student Progression Engine — recommends next course/package.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Modules\Progression;

final class Progression_Engine {

	private const LADDER = array(
		'asa-101' => 'asa-103',
		'asa-103' => 'asa-104',
		'asa-104' => array( 'asa-114', 'asa-106' ),
	);

	public function recommend( array $answers ): array {
		$experience = $answers['experience'] ?? 'no';
		$goal       = $answers['goal'] ?? 'learn';
		$liveaboard = $answers['liveaboard'] ?? 'no';

		if ( 'yes' === $liveaboard && 'no' === $experience ) {
			return array(
				'type'    => 'package',
				'slug'    => 'asa-101-103-104',
				'title'   => 'ASA 101 + 103 + 104 Liveaboard Package',
				'url'     => home_url( '/liveaboard-packages/asa-101-103-104/' ),
				'book_url' => home_url( '/book/' ),
			);
		}

		if ( 'charter' === $goal ) {
			return $this->course_result( 'asa-104', 'Learn to Charter a Sailboat' );
		}

		if ( 'catamaran' === $goal ) {
			return $this->course_result( 'asa-114', 'Learn Catamaran Cruising' );
		}

		if ( 'cruise' === $goal || 'yes' === $experience ) {
			return $this->course_result( 'asa-103', 'Learn to Cruise' );
		}

		return $this->course_result( 'asa-101', 'Learn to Sail' );
	}

	public function next_after( string $completed_slug ): array {
		$next = self::LADDER[ $completed_slug ] ?? null;
		if ( is_array( $next ) ) {
			return array_map( fn( $s ) => $this->course_result( $s, '' ), $next );
		}
		if ( is_string( $next ) ) {
			return array( $this->course_result( $next, '' ) );
		}
		return array();
	}

	private function course_result( string $slug, string $title ): array {
		$path = in_array( $slug, array( 'asa-101', 'asa-103' ), true )
			? 'learn-to-sail'
			: 'advance-your-skills';

		return array(
			'type'     => 'course',
			'slug'     => $slug,
			'title'    => $title ?: strtoupper( str_replace( '-', ' ', $slug ) ),
			'url'      => home_url( '/' . $path . '/' . $slug . '/' ),
			'book_url' => home_url( '/book/' ),
		);
	}
}
