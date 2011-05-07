=== Google Affiliate Network ===
Contributors: Robert Heller
Donate link: http://www.deepsoft.com/GAN
Tags: gan,affiliate,widget,google,plugin,ads,shortcode
Requires at least: 2.7
Tested up to: 3.1.1
Stable tag: 3.3

A Widget / shortcode plugin to display Google Affiliate Network ads. 
Both text links and image ads can be displayed.  The widgets and
shortcodes are parameterized.

== Description ==

A Widget / shortcode plugin to display Google Affiliate Network ads. 
Both text links and image ads can be displayed.  The widgets are
parameterized. There is backend access to the database table of links. 
Links can be added and/or edited by hand.  A Tcl script is included to
insert E-Mailed links (link subscriptions).  The Widgets and shortcodes
are parameterized for both sidebar display (vertical layout) as well as
leader/footer display (horizontal layout).

== Installation ==

Unpack the plugin archive under the wp-content/plugins directory and
then activate the plugin.

You can then add the widgets to your sidebar(s) and start adding in
links with the database manager. You can also use the shortcodes to
insert ad frames into posts or pages.

The link subscription handling script should be copied somewhere and
edited as needed (database access information).  You'll need to have Tcl
and mysqltcl installed. You will also need procmail (almost all Linux
system use procmail for their local mail delivery agent).  Read the
ganlinksToDB.tcl file carefully.

== Frequently Asked Questions ==

There are no Frequently Asked Questions yet.

== Screenshots ==

There are no Screenshots yet.

== Changelog ==

= 3.3
* Added target option to widgets and shortcodes.
* Added button to log into your Google Affiliate Network page

= 3.2.1 =
* Add target="_top" to links.

= 3.2 =
* Added donate button
* Added stats download (as CSV)

= 3.1 =
* Add new in bulk (from TSV file) 
* Removed extrainous (obsolute) button 
* Added short codes 
* Added help page

= 3.0 =
* New database structure.
* Minor changes to the stylesheets.

= 2.4 =
* Added in internationalization.
* Improved the ad stats display (include additional database fields).

= 2.3 =
* Changed the statistics reseting from 'flush' (delete) to 'zero', since 
  deleting rows from the stats tables is counter-indicated.  Reseting the
  statistics to a zero state (0 impressions, last date to the beginning of 
  the epoch) is what should be done.
* Fixed small database error (failing to delete empty advertisers).
* Fixed the delete expired function to properly clean up the stats tables.
* Added daily auto-expire function.
* Removed expired deletion code from ganlinksToDB.tcl

= 2.2 =
* Added stats view to admin pages.
* Fixed admin icon problem.
* Fixed a problem with the stats tables -- they need to be populated, 
  partitularly for new ads!
* Updated tags in the readme.txt file.

= 2.1 =
* Moved ALL database operations to GAN_Database.php
* Added code to update the stats tables when ads are deleted.
* Added code to clean orphan ads and merchants from stats tables.

= 2.0 =
* Major changes:
  * Ad are now served in iframes, which helps isolate the ad content from the
      page content (avoids confusing search engines).
  * Code broken up into multiple files (more modular).
  * Impression counting added.  Used to spread the impressions out across 
      merchants and ads.  Also use for statistical reporting.
* Tested under 3.1

= 1.2 =
* Fixed an endless loop issue, when there are too few ads available.
* Tested under 3.0.

= 1.1 =
* Minor update: pass along filter variables to edit and add row forms.
* Properly handle flow control in the case of failed edit row updates 
  (invalid entries).

= 1.0 =
* First official release.

== Upgrade Notice ==

= 2.2 =
Important bug fixes, see change log for details.

= 2.1 =
Code cleaned up.  Non-critical fixes.

= 2.0 =
First release of the plugin in the WordPress plugin repository.
