=== WP 404 Auto Redirect to Similar Post ===
Contributors: hwk-fr
Donate link: http://hwk.fr/
Tags: 404, Redirect, 301, Similar Post, SEO, Broken Link, Webmaster Tools
Requires at least: 4.0
Tested up to: 4.7.4
Stable tag: 0.4.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically Redirect any 404 to a Similar Post based on the Title, Post Type, Category & Taxonomy using 301 Redirects!

== Description ==

Welcome to WP 404 Auto Redirect to Similar Post!

This plugin will automatically redirect all your 404 to a similar post, based on the Title, Post Type, Category & Taxonomy. If nothing similar is found, the plugin will redirect to your homepage. All redirects are done via 301 HTTP Status Code.

= Features: =

* Easy to Install / Uninstall. No data saved in DB
* Automatically detect any 404
* Automatically search a similar post based on Title, Post Type, Category & Taxonomy
* Redirect to homepage if nothing found
* All redirects are using 301 status code

= Requirements: =

* PHP 5

== Installation ==

= Wordpress Install =

1. Upload the plugin files to the `/wp-content/plugins/wp-404-auto-redirect-similar-post` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Everything is ready! Modify an url in your browser to try to trigger a 404.

== Frequently Asked Questions ==

= What are the redirect priorities? =

1. Post Title + Post Type
2. Category / Taxonomy
4. Homepage

== Changelog ==

= 0.4.0.2 =
* Fixed sanitization bug
* Fixed debug typo

= 0.4 =
* Revamped Code
* Improved Speed
* Better Post Type / Category / Taxonomy matching

= 0.3.2 =
* Added Debug monitoring
* Better management of paged requests

= 0.3 =
* Initial Release

== Upgrade Notice ==

None