=== Webhook for Bricks Forms ===
Contributors: stingray82
Tags: bricks builder, webhooks, forms, debug, integration
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds form ID and webhook URL pairs to trigger specific webhooks on Bricks form submissions, with debug options.

== Description ==

Webhook for Bricks Forms allows you to easily configure form ID and webhook URL pairs to trigger custom webhooks on Bricks form submissions. Features include:

* Add and manage form-webhook pairs via the admin panel.
* Debug mode for testing webhook responses.
* Logs form submissions for troubleshooting.

This plugin is designed for seamless integration with Bricks Builder and ensures secure and optimized performance.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/webhook-for-bricks-forms` directory or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the 'Webhook for Forms' submenu under the Bricks menu to configure settings. - Instructions here: https://github.com/stingray82/webhooks-for-bricks-forms/blob/main/README.md

== Frequently Asked Questions ==

= What is Bricks Builder? =
Bricks Builder is a powerful WordPress site builder. This plugin extends its functionality by integrating webhook triggers for forms.

= How do I enable debug mode? =
Enable debug mode in the plugin's settings page to log form submissions and webhook responses for troubleshooting.

= Is this plugin translatable? =
Yes, the plugin is fully translatable and includes a text domain `webhook-for-bricks-forms`.

== Screenshots ==

1. Plugin settings page for managing webhooks and debug options.
2. Example of configured form-webhook pairs.

== Changelog ==

= 1.3 =
* Updated Debug Options
* Wordpress.org Changes
* Added edit option rather than just the save over option
* Name confirmed to webhook-for-bricks-forms

= 1.2 =
* Warning Breaking Changes! - Migration needed to new data options
* Added Option for JSON and Formdate sending of data 

= 1.1 =
* Added support for translations using the `webhook-for-bricks-forms` text domain.
* Improved security with input sanitization and validation.
* Resolved a linter warning related to unsanitized nonce usage.
* Updated debug mode to be conditionally active and wrapped in secure logic.
* Enhanced escaping for admin page outputs to meet WordPress security standards.

= 1.0 =
* Initial release with basic functionality for managing form-webhook pairs.
* Added debug mode to log webhook responses and form submissions.

== Upgrade Notice ==

= 1.1 =
This version includes significant security and functionality improvements. It is recommended to update to ensure compatibility with WordPress standards and enhanced features like translation support.

== Notes ==
If you encounter any issues or have suggestions, feel free to reach out via the plugin support forum.
