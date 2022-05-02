=== Maintenance Redirect ===
Contributors: petervandoorn,jfinch3
Tags: maintenance,503,redirect,developer,coming soon,launch page,under construction
Requires at least: 4.6
Tested up to: 5.6
Requires PHP: 5.2.4
Stable tag: 1.7
Text Domain: jf3-maintenance-mode
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to specify a maintenance mode message or HTML page for your site as well as configure settings to allow specific users to bypass the maintenance mode functionality in order to preview the site prior to public launch, etc.

== Description ==
This plugin is intended primarily for designers / developers that need to allow clients to preview sites before being available to the general public or to temporarily hide your WordPress site while undergoing major updates.

Any logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings in the plugin. The exact privilege can be set using a filter hook - see FAQs.

The behaviour of this can be enabled or disabled at any time without losing any of settings configured in its settings pane. However, deactivating the plugin is recommended versus having it activated while disabled.

When redirect is enabled it can send 2 different header types. “200 OK” is best used for when the site is under development and “503 Service Temporarily Unavailable” is best for when the site is temporarily taken offline for small amendments. If used for a long period of time, 503 can damage your Google ranking.

A list of IP addresses can be set up to completely bypass maintenance mode. This option is useful when needing to allow a client’s entire office to access the site while in maintenance mode without needing to maintain individual access keys.

Access keys work by creating a key on the user’s computer that will be checked against when maintenance mode is active. When a new key is created, a link to create the access key cookie will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key.

This plugin allows three methods of notifying users that a site is undergoing maintenance:

  1. They can be presented with a message on a page created by information entered into the plugin settings pane.

  2. They can be presented with a custom HMTL page.

  3. They can be redirected to a static page. This static page will need to be uploaded to the server via FTP or some other method. This plugin DOES NOT include any way to upload the static page file.


== Installation ==
1. Upload the `jf3-maintenance-mode` folder to your plugins directory (usually `/wp-content/plugins/`).

2. Activate the plugin through the `Plugins` menu in WordPress.

3. Configure the settings through the `Maintenance Redirect` Settings panel.


== Frequently Asked Questions ==
= How can I bypass the redirect programatically? =

There is a filter which allows you to programatically bypass the redirection block:

**`wpjf3_matches`**

This allows you to run pretty much any test you like, although be aware that the whole redirection thing runs *before* the `$post` global is set up, so WordPress conditionals such as `is_post()` and `is_tax()` are not available. 

This example looks in the `$_SERVER` global to see if any part of the URL contains "demo"

	function my_wpjf3_matches( $wpjf3_matches ) {
		if ( stristr( $_SERVER['REQUEST_URI'], 'demo' ) ) 
			$wpjf3_matches[] = "<!-- Demo -->";
		return $wpjf3_matches;
	}
	add_filter( "wpjf3_matches", "my_wpjf3_matches" );

*Props to @brianhenryie for this!*

= How can I let my logged-in user see the front end? =

By default, Maintenance Redirect uses the `manage_options` capability, but that is normally only applied to administrators. As it stands, a user with a lesser permissions level, such as editor, is able to view the admin side of the site, but not the front end. You can change this using this filter:

**`wpjf3_user_can`**

This filter is used to pass a different WordPress capability to check if the logged-in user has permission to view the site and thus bypass the redirection, such as `edit_posts`. Note that this is run before `$post` is set up, so WordPress conditionals such as `is_post()` and `is_tax()` are not available. However, it's not really meant for programatically determining whether a user should have access, but rather just changing the default capability to be tested, so you don't really need to do anything other than the example below.

	function my_wpjf3_user_can( $capability ) {
		return "edit_posts";
	}
	add_filter( "wpjf3_user_can", "my_wpjf3_user_can" );

== Screenshots ==
1. Settings

== Changelog ==
= 1.7 =
* Added links to plugin screen and the dashboard notification to go to the Settings page.
* Added information to the Site Health screen.

= 1.6 =
* Added `wpjf3_matches` filter to allow programatical bypasses. Thanks to @brianhenryie for this.
* Added `wpjf3_user_can` filter to allow the WordPress capability check to be changed so logged-in users can be allowed to bypass the redirect.

= 1.5.3 =
* Fixed ability to set IP address using Class C wildcard (eg, 192.168.0.*) - thanks to @tsouts for bringing that to my attention.

= 1.5.2 =
* Tiny translation tweak

= 1.5.1 =
* Phooey! Found a couple of translation strings that I missed on the previous commit!

= 1.5 =
* Now translatable! I’m a typical Englishman who doesn’t speak any other language, so at time of release there aren’t any translation packs available. However, if you’re interested in contributing, just pop over to https://translate.wordpress.org/ and get translating!
* Minimum WordPress requirement now 4.6 due to usage of certain translation bits & bobs.

= 1.4 =
* Plugin taken over by @petervandoorn due to being unmaintained for over 4 years. 
* Changed name to Maintenance Redirect
* Setting added to choose whether to return 200 or 503 header
* Added nonces and other, required, security checks
* General code modernisation

= 1.3 =
* Updated to return 503 header when enabled to prevent indexing of maintenance page. 
* Also, wildcards are allowed to enable entire class C ranges. Ex: 10.10.10.*
* A fix affecting some installations using a static page has been added. Thanks to Dorthe Luebbert.

= 1.2 =
* Fixed bug in Unrestricted IP table creation.

= 1.1 =
* Updated settings panel issue in WordPress 3.0 and moved folder structure to work properly with WordPress auto install.

= 1.0 =
* First release. No Changes Yet.

== Upgrade Notice ==
Now translatable!