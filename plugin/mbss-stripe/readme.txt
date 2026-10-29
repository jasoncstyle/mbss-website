=== Course Deposits for Stripe ===
Contributors: hundredairelabs
Donate link: https://hundredairelabs.com
Tags: stripe, payments, deposit, courses, checkout
Requires at least: 6.4
Tested up to: 7.1
Stable tag: 1.1.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Charge a deposit today and the remaining balance before a lesson. Stripe receives the amount. Card numbers stay on Stripe.

== Description ==

Course Deposits for Stripe is a checkout for lessons, packages, and other scheduled offerings. The site keeps the price. Stripe charges the deposit now, and the balance on or before the date you choose.

A product of [Hundredaire Labs](https://hundredairelabs.com).

The amount sent to Stripe is calculated on the server from the lesson you saved. A visitor cannot change the price in the browser and pay a different amount.

What you can set:

* The full price of each lesson
* The deposit for that lesson
* Default deposits for a lesson, a package, a docking course, and a passage
* How many days before the lesson the balance is due
* The prefix on payment references

What the customer does:

1. Choose a lesson and a start date.
2. Enter a name, email, and phone number.
3. Continue to Stripe and pay the deposit.
4. Return later, with the email and reference, and pay the balance.

Card numbers, expiration dates, and security codes are entered on Stripe Checkout. This plugin stores the payment reference, the amount, and the lesson. It does not store card data.

Courses that are sold through a separate online education system are not part of this checkout. Leave those courses out of the lesson list. They should keep their own payment link.

Shortcodes:

* `[mbss_stripe]`
* `[course_deposit]`

Both display the same checkout form.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from Plugins, Add New, Upload Plugin.
2. Activate Course Deposits for Stripe from the Plugins screen.
3. Open Course Deposits, Settings.
4. Paste the Stripe secret key. Use a key that starts with `sk_`.
5. Paste the webhook signing secret. In Stripe, add an endpoint for the URL shown on the settings screen and listen for `checkout.session.completed`.
6. Set how many days before the lesson the balance is due, and the default deposits for new lessons.
7. Open Lessons and confirm each price and deposit. Add any lesson that is missing.
8. Create the draft checkout page from Settings, or place `[mbss_stripe]` on a page yourself.
9. Publish that page when you want customers to use it.

The rest of the site stays as it is. Existing pages are not rewritten.

== Frequently Asked Questions ==

= Where do card numbers go? =

Customers type card details on Stripe Checkout. The plugin stores the Stripe payment reference and the amount. It does not store the card number.

= Can each lesson have a different deposit? =

Yes. The settings screen has default deposits for a lesson, a package, docking, and a passage. Each lesson on the Lessons screen can use a different deposit. Changing a default does not change a lesson you already saved.

= When is the balance due? =

The balance is due the number of days you set before the lesson start date. The default is 14 days. If that date has already passed, the balance is due today. The customer can pay it early from the same checkout page.

= What about online courses sold somewhere else? =

Do not add them here. If a course is paid through another education system, leave it off this lesson list and keep that system's link on your course page.

= Can I charge the full price instead of a deposit? =

Yes. Add the lesson with the type "Pay the full price today." Stripe then charges the course price in one payment.

= What if Stripe is not connected? =

The checkout page stays up and explains that the secret key is missing. It does not invent a payment.

= Can another business use the same plugin? =

Yes. Add that business's lessons, deposits, and reference prefix. The plugin does not require a sailing school. Payment references use the prefix you save, and receipts use the site name.

== Screenshots ==

1. The checkout tells the customer the deposit Stripe will charge and the date the balance is due.
2. Settings for the Stripe key, the balance timing, and the default deposits.
3. Each lesson keeps its own price and deposit.

== Changelog ==

= 1.1.0 =
* Removed online education courses from the lesson list. Those courses are paid in another system.
* Each new lesson can start from a default deposit, and every saved lesson can keep its own deposit.
* Added the WordPress.org listing sections: description, installation, FAQ, screenshots, changelog, and reviews.
* Linked the plugin to https://hundredairelabs.com
* Checkout fields stay on the screen on a phone.
* A one-word site name is used as the payment reference prefix.

= 1.0.0 =
* First checkout. Stripe charges a deposit, then the balance before the lesson.

== Other Notes ==

Shortcodes `[mbss_stripe]` and `[course_deposit]` display the same checkout form.

The plugin stores the lesson, the customer name, email, phone, the amounts, and the Stripe payment reference. Card numbers stay on Stripe.

Another business can use the same plugin. Add that business's lessons, set its deposits, and choose its reference prefix. Receipts use the site name. The product homepage is [https://hundredairelabs.com](https://hundredairelabs.com).

Courses paid through a separate online education system stay on that system. Do not add them to this lesson list.

== Upgrade Notice ==

= 1.1.0 =
Online education courses are no longer offered in this checkout. Review Lessons after updating and confirm each deposit.
