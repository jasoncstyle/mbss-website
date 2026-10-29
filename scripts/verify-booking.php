<?php
/**
 * Checks deposit math and Stripe webhook signatures without WordPress.
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/plugin/mbss-operations/modules/bookings/class-quote.php';
require dirname( __DIR__ ) . '/plugin/mbss-operations/modules/payments/class-stripe-client.php';

use MBSS\Modules\Bookings\Quote;
use MBSS\Modules\Payments\Stripe_Client;

$failures = 0;

function check( bool $condition, string $message ): void {
	global $failures;
	if ( $condition ) {
		echo "ok  {$message}\n";
		return;
	}
	$failures++;
	echo "FAIL {$message}\n";
}

$passage = array(
	'pricing_model'       => 'passage',
	'base_price'          => 2250,
	'shared_price'        => 3250,
	'deposit_amount'      => 0,
	'deposit_percent'     => 10,
	'private_upgrade_price' => 0,
	'extra_student_price' => 0,
	'addon_price'         => 800,
	'allows_asa104'       => 1,
	'allows_asa106'       => 1,
	'asa104_price'        => 600,
	'asa106_price'        => 800,
	'pay_in_full'         => 0,
	'balance_due_days'    => 20,
	'second_payment_days' => 60,
);

$private = Quote::build( $passage, array( 'occupancy' => 'private' ), '2027-04-25', '2026-10-28' );
check( ! empty( $private['ok'] ), 'private passage quotes' );
check( '2250.00' === $private['total'], 'private total 2250, got ' . ( $private['total'] ?? '' ) );
check( '225.00' === $private['deposit'], '10% deposit is 225, got ' . ( $private['deposit'] ?? '' ) );
check( 3 === count( $private['schedule'] ), 'three payment lines' );
check( '2027-02-24' === $private['schedule'][1]['due_on'], 'payment 2 is 60 days prior, got ' . ( $private['schedule'][1]['due_on'] ?? '' ) );
check( '2027-04-05' === $private['schedule'][2]['due_on'], 'final balance is 20 days prior, got ' . ( $private['schedule'][2]['due_on'] ?? '' ) );
$sum = 0;
foreach ( $private['schedule'] as $line ) {
	$sum += (int) round( (float) $line['amount'] * 100 );
}
check( 225000 === $sum, 'schedule sums to the private total' );

$shared = Quote::build(
	$passage,
	array( 'occupancy' => 'shared', 'addon' => true, 'addon_104' => true ),
	'2027-04-25',
	'2026-10-28'
);
check( '6050.00' === $shared['total'], 'shared cabin plus both certifications is 6050, got ' . ( $shared['total'] ?? '' ) );
check( 2 === $shared['occupant_count'], 'shared cabin counts two sailors' );

$course = array(
	'pricing_model'         => 'standard',
	'base_price'            => 900,
	'deposit_amount'        => 250,
	'deposit_percent'       => 0,
	'private_upgrade_price' => 500,
	'extra_student_price'   => 0,
	'pay_in_full'           => 0,
	'balance_due_days'      => 21,
	'second_payment_days'   => 0,
);
$basic = Quote::build( $course, array(), '2026-11-06', '2026-10-28' );
check( '900.00' === $basic['total'] && '250.00' === $basic['deposit'] && '650.00' === $basic['balance'], 'ASA 101 deposit stays 250' );
check( '2026-10-28' === $basic['schedule'][1]['due_on'], 'balance inside 21 days is due today' );

$online = $course;
$online['pay_in_full'] = 1;
$online['base_price']  = 419;
$full = Quote::build( $online, array(), '2099-12-31', '2026-10-28' );
check( '419.00' === $full['deposit'] && '0.00' === $full['balance'], 'online courses are paid in full' );

$payload = '{"id":"evt_test"}';
$secret  = 'whsec_test_secret';
$stamp   = 1700000000;
$sig     = hash_hmac( 'sha256', $stamp . '.' . $payload, $secret );
check(
	Stripe_Client::verify_signature( $payload, 't=' . $stamp . ',v1=' . $sig, $secret, $stamp ),
	'valid Stripe signature is accepted'
);
check(
	! Stripe_Client::verify_signature( $payload, 't=' . $stamp . ',v1=deadbeef', $secret, $stamp ),
	'rejected signature is refused'
);
check(
	! Stripe_Client::verify_signature( $payload, 't=' . ( $stamp - 1000 ) . ',v1=' . $sig, $secret, $stamp ),
	'stale signature is refused'
);

exit( $failures > 0 ? 1 : 0 );
