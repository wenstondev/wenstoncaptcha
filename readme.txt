=== wenstonCaptcha ===
Contributors: wenstondev
Tags: captcha, spam, security, gdpr
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted image CAPTCHA for WordPress. No third-party services or external requests. Privacy-first CAPTCHA. GDPR compliant.

== Description ==

* Image CAPTCHA rendered with PHP GD (same character set and drawing approach as common self-hosted implementations).
* Stores the challenge using WordPress transients and a form token; does not rely on PHP sessions.
* Shortcode `[wenstoncaptcha]` for pages and custom forms.
* Contact Form 7: add a `wenstoncaptcha` form tag (for example `[wenstoncaptcha your-captcha]`).
* Translations included: Bulgarian, Czech, Danish, German, Spanish, Finnish, French, Greek, Italian, Polish, Russian, Swedish, Ukrainian (see `languages/`).

== Installation ==

1. Upload the `wenstoncaptcha` folder to `/wp-content/plugins/`, or install the ZIP from the Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. The PHP **GD** extension must be enabled on the server.

== Frequently Asked Questions ==

= Does this plugin call an external CAPTCHA service? =

No. Everything runs on your own server.

= How do I use it with Contact Form 7? =

Add a tag to your form template, for example:

`[wenstoncaptcha your-captcha]`

Validation runs automatically when the form is submitted.

== Changelog ==

= 1.0.1 =
* Security: verify WordPress nonce on CAPTCHA image AJAX requests (`admin-ajax.php` action `wenstoncaptcha_image`)
* Plugin package no longer contains WordPress.org directory-only icon PNGs
* Added uninstall.php, security.md, and LICENSE.txt

= 1.0.0 =
* Initial release.
