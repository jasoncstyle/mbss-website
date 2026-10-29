<?php
/**
 * Seeds the published MBSS course catalog and upcoming dates.
 *
 * Existing slugs are left untouched so later admin price edits stick.
 *
 * @package MBSS_Operations
 */

declare(strict_types=1);

namespace MBSS\Database;

final class Catalog_Seeder {

	public static function seed(): void {
		global $wpdb;

		$courses_table  = $wpdb->prefix . 'mbss_courses';
		$sessions_table = $wpdb->prefix . 'mbss_course_sessions';

		foreach ( self::courses() as $course ) {
			$existing_id = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$courses_table} WHERE slug = %s", $course['slug'] )
			);
			if ( $existing_id > 0 ) {
				$course_id = $existing_id;
			} else {
				$wpdb->insert( $courses_table, $course );
				$course_id = (int) $wpdb->insert_id;
			}

			if ( $course_id <= 0 ) {
				continue;
			}

			$has_sessions = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$sessions_table} WHERE course_id = %d", $course_id )
			);
			if ( $has_sessions > 0 ) {
				continue;
			}

			foreach ( self::sessions_for( $course['slug'] ) as $session ) {
				$wpdb->insert(
					$sessions_table,
					array(
						'course_id'    => $course_id,
						'start_date'   => $session['start'],
						'end_date'     => $session['end'],
						'capacity'     => $session['capacity'],
						'seats_booked' => 0,
						'status'       => 'confirmed',
						'notes'        => $session['notes'] ?? null,
					)
				);
			}
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function courses(): array {
		$standard = array(
			'course_type'           => 'course',
			'path'                  => 'learn',
			'shared_price'          => null,
			'deposit_amount'        => 250,
			'deposit_percent'       => 0,
			'private_upgrade_price' => 500,
			'extra_student_price'   => 0,
			'addon_price'           => 0,
			'addon_label'           => '',
			'pricing_model'         => 'standard',
			'pay_in_full'           => 0,
			'balance_due_days'      => 21,
			'second_payment_days'   => 0,
			'is_active'             => 1,
		);

		$rows = array(
			array(
				'slug'           => 'asa-101',
				'title'          => 'ASA 101 Basic Keelboat',
				'asa_code'       => '101',
				'path'           => 'learn',
				'summary'        => 'Three days on the Atlantic for new sailors. Optional nights aboard. Materials are mailed after the deposit.',
				'duration_label' => '3 days',
				'base_price'     => 900,
			),
			array(
				'slug'           => 'asa-103',
				'title'          => 'ASA 103 Basic Coastal Cruising',
				'asa_code'       => '103',
				'path'           => 'learn',
				'summary'        => 'Coastal cruising with a night at anchor behind Bird Island. Prerequisite: ASA 101.',
				'duration_label' => '3 days / 2 nights',
				'base_price'     => 1050,
			),
			array(
				'slug'           => 'asa-102',
				'title'          => 'ASA 102 Basic Keelboat Sailing',
				'asa_code'       => '102',
				'path'           => 'advance',
				'summary'        => 'Sail trim and boat handling so the boat is faster and more comfortable. Prerequisite: ASA 101.',
				'duration_label' => '3 days',
				'base_price'     => 900,
			),
			array(
				'slug'           => 'asa-104',
				'title'          => 'ASA 104 Bareboat Cruising',
				'asa_code'       => '104',
				'path'           => 'advance',
				'summary'        => 'A four-day coastal cruise with anchoring at Bird Island and an overnight at Bald Head Island. Prerequisite: ASA 103.',
				'duration_label' => '4 days / 3 nights',
				'base_price'     => 1600,
			),
			array(
				'slug'           => 'asa-114',
				'title'          => 'ASA 114 Cruising Catamaran',
				'asa_code'       => '114',
				'path'           => 'advance',
				'summary'        => 'Catamaran cruising with overnight anchoring. Meals aboard are included.',
				'duration_label' => '3 days / 2 nights',
				'base_price'     => 1400,
			),
			array(
				'slug'                   => 'asa-118',
				'title'                  => 'ASA 118 Docking Endorsement',
				'asa_code'               => '118',
				'path'                   => 'advance',
				'summary'                => 'Two days of docking and close-quarters boat handling.',
				'duration_label'         => '2 days',
				'base_price'             => 325,
				'private_upgrade_price'  => 0,
			),
		);

		$online = array(
			array( 'asa-105', '105', 'ASA 105 Coastal Navigation', 'Chart plotting, tides, and coastal navigation. Self-paced, with instructor support.' ),
			array( 'asa-107', '107', 'ASA 107 Celestial Navigation', 'Sun, moon, and star sights for ocean passages. Prerequisite: ASA 105.' ),
			array( 'asa-117', '117', 'ASA 117 Basic Celestial Endorsement', 'Core celestial navigation concepts you can study on your own schedule.' ),
			array( 'asa-119', '119', 'ASA 119 Marine Weather', 'Practical marine weather for passage planning.' ),
			array( 'asa-120', '120', 'ASA 120 Radar Endorsement', 'Small-craft radar for coastal and offshore navigation.' ),
		);

		$packages = array(
			array(
				'slug'                => 'asa-101-103-118',
				'title'               => 'ASA 101 + 103 + 118 Private Liveaboard',
				'asa_code'            => '101+103+118',
				'summary'             => 'Six days and five nights for a couple or family, including two nights at Bird Island and a docking day.',
				'duration_label'      => '6 days / 5 nights',
				'base_price'          => 4900,
			),
			array(
				'slug'                => 'asa-102-104',
				'title'               => 'ASA 102 + 104 Private Liveaboard',
				'asa_code'            => '102+104',
				'summary'             => 'Five days for a couple: performance sailing plus the Bald Head Island bareboat cruise.',
				'duration_label'      => '5 days / 4 nights',
				'base_price'          => 4750,
			),
			array(
				'slug'                => 'asa-101-103',
				'title'               => 'ASA 101 + 103 Private Liveaboard',
				'asa_code'            => '101+103',
				'summary'             => 'Five days that launch a couple from first sail into coastal cruising.',
				'duration_label'      => '5 days / 4 nights',
				'base_price'          => 4200,
			),
			array(
				'slug'                => 'asa-104-114-118',
				'title'               => 'ASA 104 + 114 + 118 Private Liveaboard',
				'asa_code'            => '104+114+118',
				'summary'             => 'Six days of catamaran cruising, a destination overnight, and a docking day.',
				'duration_label'      => '6 days / 5 nights',
				'base_price'          => 5400,
			),
		);

		$passages = array(
			array(
				'slug'            => 'hilton-head-passage',
				'title'           => 'Hilton Head Coastal Passage',
				'asa_code'        => '106',
				'summary'         => 'Seven-day round trip to Hilton Head with night sailing, the ICW, and anchoring. ASA 106 can be added if you qualify.',
				'duration_label'  => '7 days / 6 nights',
				'base_price'      => 2250,
				'shared_price'    => 3250,
			),
			array(
				'slug'            => 'myrtle-beach-new-york-passage',
				'title'           => 'Myrtle Beach to New York Offshore Passage',
				'asa_code'        => '106',
				'summary'         => 'Offshore passage from Myrtle Beach toward New York. ASA 106 can be added per sailor if you qualify.',
				'duration_label'  => 'About 4–5 travel days',
				'base_price'      => 3200,
				'shared_price'    => 4800,
			),
		);

		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array_merge( $standard, $row );
		}
		foreach ( $online as $item ) {
			$out[] = array_merge(
				$standard,
				array(
					'slug'                  => $item[0],
					'title'                 => $item[2],
					'asa_code'              => $item[1],
					'course_type'           => 'online',
					'path'                  => 'online',
					'summary'               => $item[3],
					'duration_label'        => 'Self-paced online',
					'base_price'            => 419,
					'deposit_amount'        => 419,
					'private_upgrade_price' => 0,
					'pricing_model'         => 'standard',
					'pay_in_full'           => 1,
				)
			);
		}
		foreach ( $packages as $row ) {
			$out[] = array_merge(
				$standard,
				$row,
				array(
					'course_type'           => 'package',
					'path'                  => 'liveaboard',
					'deposit_amount'        => 500,
					'private_upgrade_price' => 0,
					'extra_student_price'   => 1500,
					'pricing_model'         => 'package',
				)
			);
		}
		foreach ( $passages as $row ) {
			$out[] = array_merge(
				$standard,
				$row,
				array(
					'course_type'           => 'passage',
					'path'                  => 'passage',
					'deposit_amount'        => 0,
					'deposit_percent'       => 10,
					'private_upgrade_price' => 0,
					'addon_price'           => 800,
					'addon_label'           => 'ASA 106 Advanced Coastal Cruising certification',
					'allows_asa104'         => 1,
					'allows_asa106'         => 1,
					'asa104_price'          => 600,
					'asa106_price'          => 800,
					'pricing_model'         => 'passage',
					'balance_due_days'      => 20,
					'second_payment_days'   => 60,
				)
			);
		}

		return $out;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function sessions_for( string $slug ): array {
		$map = array(
			'asa-101' => array(
				array( '2026-11-06', '2026-11-08', 3 ),
				array( '2027-03-19', '2027-03-21', 3 ),
				array( '2027-05-07', '2027-05-09', 3 ),
				array( '2027-07-09', '2027-07-11', 3 ),
				array( '2027-09-03', '2027-09-05', 3 ),
				array( '2027-11-05', '2027-11-07', 3 ),
			),
			'asa-102' => array(
				array( '2027-05-28', '2027-05-30', 3 ),
				array( '2027-09-24', '2027-09-26', 3 ),
			),
			'asa-103' => array(
				array( '2027-04-02', '2027-04-04', 3 ),
				array( '2027-06-04', '2027-06-06', 3 ),
				array( '2027-08-06', '2027-08-08', 3 ),
				array( '2027-10-01', '2027-10-03', 3 ),
			),
			'asa-104' => array(
				array( '2027-04-23', '2027-04-26', 3 ),
				array( '2027-05-21', '2027-05-24', 3 ),
				array( '2027-06-11', '2027-06-14', 3 ),
				array( '2027-09-17', '2027-09-20', 3 ),
				array( '2027-10-22', '2027-10-25', 3 ),
			),
			'asa-114' => array(
				array( '2027-05-14', '2027-05-16', 3 ),
				array( '2027-09-10', '2027-09-12', 3 ),
			),
			'asa-118' => array(
				array( '2027-03-20', '2027-03-21', 4 ),
				array( '2027-05-22', '2027-05-23', 4 ),
				array( '2027-09-18', '2027-09-19', 4 ),
			),
			'asa-101-103-118' => array(
				array( '2027-03-14', '2027-03-19', 1, 'Private boat — exact daily plan confirmed after booking' ),
				array( '2027-06-06', '2027-06-11', 1, 'Private boat — exact daily plan confirmed after booking' ),
			),
			'asa-102-104' => array(
				array( '2027-04-18', '2027-04-22', 1, 'Private boat' ),
				array( '2027-10-03', '2027-10-07', 1, 'Private boat' ),
			),
			'asa-101-103' => array(
				array( '2027-05-02', '2027-05-06', 1, 'Private boat' ),
				array( '2027-09-12', '2027-09-16', 1, 'Private boat' ),
			),
			'asa-104-114-118' => array(
				array( '2027-05-16', '2027-05-21', 1, 'Private catamaran' ),
				array( '2027-10-10', '2027-10-15', 1, 'Private catamaran' ),
			),
			'hilton-head-passage' => array(
				array( '2027-04-25', '2027-05-02', 3, '3 cabins' ),
			),
			'myrtle-beach-new-york-passage' => array(
				array( '2027-06-04', '2027-06-13', 3, '3 cabins' ),
			),
		);

		if ( isset( $map[ $slug ] ) ) {
			$sessions = array();
			foreach ( $map[ $slug ] as $row ) {
				$sessions[] = array(
					'start'    => $row[0],
					'end'      => $row[1],
					'capacity' => $row[2],
					'notes'    => $row[3] ?? null,
				);
			}
			return $sessions;
		}

		if ( str_starts_with( $slug, 'asa-10' ) || str_starts_with( $slug, 'asa-11' ) || str_starts_with( $slug, 'asa-12' ) ) {
			return array(
				array(
					'start'    => '2026-01-01',
					'end'      => '2099-12-31',
					'capacity' => 500,
					'notes'    => 'Open enrollment',
				),
			);
		}

		return array();
	}
}
