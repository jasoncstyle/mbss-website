<?php
/**
 * Deposit math and Stripe signature checks. No WordPress and no network.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

require dirname( __DIR__ ) . '/plugin/mbss-stripe/includes/class-quote.php';
require dirname( __DIR__ ) . '/plugin/mbss-stripe/includes/class-signature.php';
require dirname( __DIR__ ) . '/plugin/mbss-stripe/includes/class-checkout-url.php';
require dirname( __DIR__ ) . '/plugin/mbss-stripe/includes/class-installer.php';

use MBSS\StripePay\Checkout_Url;
use MBSS\StripePay\Installer;
use MBSS\StripePay\Quote;
use MBSS\StripePay\Signature;

function check( bool $ok, string $label ): void {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL {$label}\n" );
		exit( 1 );
	}
	echo "ok  {$label}\n";
}

$asa101 = Quote::build( 90000, 25000, false, '2026-12-01', '2026-10-29', 14 );
check( $asa101['ok'], 'ASA 101 quote' );
check( 25000 === $asa101['charge_cents'], 'ASA 101 deposit is 250' );
check( 65000 === $asa101['balance_cents'], 'ASA 101 balance is 650' );
check( '2026-11-17' === $asa101['balance_due'], 'balance is 14 days before December 1, got ' . (string) $asa101['balance_due'] );

$soon = Quote::build( 90000, 25000, false, '2026-11-06', '2026-10-29', 14 );
check( '2026-10-29' === $soon['balance_due'], 'a balance already inside 14 days is due today' );

$docking = Quote::build( 32500, 5000, false, '2027-03-01', '2026-10-29', 14 );
check( 5000 === $docking['charge_cents'], 'ASA 118 deposit is 50' );
check( 27500 === $docking['balance_cents'], 'ASA 118 balance is 275' );
check( '2027-02-15' === $docking['balance_due'], 'ASA 118 balance date' );

$package = Quote::build( 365000, 50000, false, '2027-03-14', '2026-10-29', 14 );
check( 50000 === $package['charge_cents'], 'liveaboard deposit is 500' );
check( 315000 === $package['balance_cents'], 'liveaboard balance' );

$online = Quote::build( 41900, 41900, true, null, '2026-10-29', 14 );
check( $online['ok'] && 41900 === $online['charge_cents'] && 0 === $online['balance_cents'], 'online course charges the full price' );

$past = Quote::build( 90000, 25000, false, '2026-10-01', '2026-10-29', 14 );
check( ! $past['ok'], 'a past lesson date is refused' );

$clamped = Quote::build( 4000, 5000, false, '2027-01-10', '2026-10-29', 14 );
check( 4000 === $clamped['charge_cents'] && 0 === $clamped['balance_cents'], 'deposit cannot exceed the course price' );

$secret  = 'whsec_test';
$payload = '{"id":"evt_test"}';
$time    = 1700000000;
$sig     = hash_hmac( 'sha256', $time . '.' . $payload, $secret );
check( Signature::verify( $payload, 't=' . $time . ',v1=' . $sig, $secret, $time ), 'valid signature' );
check( ! Signature::verify( $payload, 't=' . $time . ',v1=deadbeef', $secret, $time ), 'bad signature' );
check( ! Signature::verify( $payload, 't=' . $time . ',v1=' . $sig, $secret, $time + 1000 ), 'stale signature' );

$by_slug = array();
foreach ( Installer::catalog() as $product ) {
	$by_slug[ $product['slug'] ] = $product;
}
check( 5000 === $by_slug['asa-118']['deposit'], 'catalog ASA 118 deposit is $50' );
check( 25000 === $by_slug['asa-101']['deposit'], 'catalog ASA 101 deposit is $250' );
check( 50000 === $by_slug['asa-101-103']['deposit'], 'catalog liveaboard deposit is $500' );
check( 25000 === $by_slug['asa-102-104']['deposit'], 'catalog 102+104 keeps the published $250 deposit' );
check( ! isset( $by_slug['asa-105'] ), 'online education courses are not in the catalog' );
check( ! isset( $by_slug['asa-120'] ), 'radar endorsement is not sold in this checkout' );

$encoded = Checkout_Url::encode_success( 'https://example.com/pay/?mbss_stripe_session={CHECKOUT_SESSION_ID}' );
check( str_contains( $encoded, '{CHECKOUT_SESSION_ID}' ), 'success url keeps the Stripe placeholder' );
check( ! str_contains( $encoded, '%7B' ), 'success url does not encode the placeholder' );

echo "all deposit checks passed\n";
