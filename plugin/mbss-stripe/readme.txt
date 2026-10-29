=== MBSS Stripe Payments ===
Contributors: myrtlebeachsailingschool
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Sends a course deposit, and later the balance, to Stripe Checkout.

== Description ==

The current site stays in place. Add the shortcode `[mbss_stripe]` to a page when you are ready for students to pay there.

Stripe charges the deposit today:

* Individual lessons: $250
* Liveaboard packages: $500
* ASA 102 + 104 Liveaboard: $250, matching the current course page
* ASA 118 Docking Endorsement: $50
* Online courses: the full $419

The balance is due 14 days before the lesson. That second amount is also sent to Stripe. Card numbers are not stored on the site.

== Installation ==

1. Copy the `mbss-stripe` folder into `wp-content/plugins`.
2. Activate MBSS Stripe Payments.
3. Open MBSS Stripe and save the Stripe secret key and webhook secret.
4. Point Stripe at the webhook printed on that screen and listen for `checkout.session.completed`.
5. Create the draft checkout page, add `[mbss_stripe]` if it is not already there, and publish the page when you want it live.

Existing course pages and FareHarbor buttons are left as they are.
