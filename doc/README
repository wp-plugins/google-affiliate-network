=== Google Affiliate Network ===
Contributors: RobertPHeller
Donate link: http://www.deepsoft.com/GAN
Tags: gan,affiliate,widget,google,plugin,ads,shortcode
Requires at least: 2.7
Tested up to: 3.1.1
Stable tag: 4.4

A Widget / shortcode plugin to display Google Affiliate Network ads. 
Both text links and image ads can be displayed.

== Description ==

A Widget / shortcode plugin to display Google Affiliate Network ads. 
Both text links and image ads can be displayed.  The widgets are
parameterized. There is backend access to the database table of links. 
Links can be added and/or edited by hand.  A Tcl script is included to
insert E-Mailed links (link subscriptions).  The Widgets and shortcodes
are parameterized for both sidebar display (vertical layout) as well as
leader/footer display (horizontal layout).

The ads are rotated, with the least viewed ads from the lest viewed
avertisers being shown in preference to ads that have been viewed more
from avertisers than have been viewed more.  As ads are displayed, their
impression counts are incremented, which moves such ads to the back of
the list. Ads are displayed in iframes, which keeps the ads contained on
the page. Either widgets can be used to display ads in side bars or
shortcodes can be used to display ads in pages or posts (both can be
used if desired). A given ad unit can only display text or image ads,
not both.

The plugin provides an administration page to view the database of ads,
with the ability to add ads one at a time or in bulk (from a TSV file
downloaded from your Google Affiliate Network Links tab).  There are
also administration pages to view ad impression statistics and merchant
(advertiser) impression statistics.  The statistics can be downloaded as
CSV files.  The plugin also includes a help page and a printable PDF
user manual.

== Installation ==

Unpack the plugin archive under the wp-content/plugins directory and
then activate the plugin.

You can then add the widgets to your sidebar(s) and start adding in
links with the database manager. You can also use the shortcodes to
insert ad frames into posts or pages.  There is a help page and a
printable PDF user manual, that explains how to put ads in the database
and how to display ads on your blog pages or posts.

The link subscription handling script should be copied somewhere and
edited as needed (database access information).  You'll need to have Tcl
and mysqltcl installed. You will also need procmail (almost all Linux
system use procmail for their local mail delivery agent).  Read the
ganlinksToDB.tcl file carefully.

== Frequently Asked Questions ==

= Something does not work. What should I do? =

Submit a bug at [Deepwoods Software's Bugzilla][bug report].

= I have another question that is not listed here. What should I do? =

Submit one on [Deepwoods Software's Support page][support]. You can also submit
a documentation bug at [Deepwoods Software's Bugzilla][bug report] as well.

[bug report]: http://bugzilla.deepsoft.com/enter_bug.cgi?product=Google%20Affilliate%20Network%20Plugin%20for%20WordPress "Deepwoods Software Bugzilla"
[support]: http://www.deepsoft.com/support/ "Deepwoods Software's Support page"

== Screenshots ==

1. View of ad stats.
2. View of merchant stats.
3. View of ad database listing.
4. Front side view, showing both textual ads and banner ads.

== Changelog ==

= 4.4 =
* Added admin tab navigation menu.

= 4.3 =
* Added media button to insert ad unit short codes.

= 4.2 =
* Additional Y2k / date fixes.
* Minor updates to the documentation, clarifying various points.
* Change Image Width column, dropdown, etc. to Image Size.
* Change Widths to Sizes in database stats.
* Minor documentation updates. Fix spelling errors.

= 4.1.3 =
* Maintenance release -- fix problem with dates (a real live Y2K issue!)

= 4.1.2 =
* Maintenance release -- hide dashboard widgets from non-priviledged users.

= 4.1.1 =
* Maintenance release -- fix problem with add bulk upload form.

= 4.1 =
* Added in permission checks.
* Restored human readable date displays.
* Updated documentation in the readme.txt and the user manual.
* Fixed bug relating to 'lingering' edit/view state -- make sure Add New
  does in fact add a new element.

= 4.0.5 =
* Maintenance release -- fix problem with date display.
* Updated readme.txt with a couple of simple FAQs.

= 4.0.4 =
* Maintenance release -- fix problem with screen options.
* Updated internationalization files.

= 4.0.3 =

* Maintenance release -- restore lost function (options page).
* Add in contexual help.

= 4.0.2 =
* Maintenance release -- put the GAN login button back.

= 4.0.1 =
* Maintenance release -- updated pot, po, and mo files.

= 4.0 =
* Changed Help submenu page to have the same permissions as the other pages.
* Changed the listing pages to use classes derived from WP_List_Table.
* Updated help page.
* Added 'hardcopy' documentation.
* Updated screenshots

= 3.4.1 =
* Fixed readme.txt and screenshots.

= 3.4 =
* Updated readme.txt
* Added screenshots

= 3.3 =
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

= 4.4 =
Added admin tab navigation menu.

= 4.3 =
Added media button to insert ad unit short codes into pages and posts.

= 4.2 =
Assorted minor updates, including documentation cleanup.

= 4.1.3 =
Fixed a problem with dates (a real live Y2K issue!)

= 4.0 =
Many updates (see the changelog), including more useful backend 
functionallity and more complete documentation, including a downloadable and
printable manual.

= 3.4 =
Updated readme.txt, added screenshots.

= 3.2.1 =
Important bug fixes, see change log for details.

= 2.2 =
Important bug fixes, see change log for details.

= 2.1 =
Code cleaned up.  Non-critical fixes.

= 2.0 =
First release of the plugin in the WordPress plugin repository.
