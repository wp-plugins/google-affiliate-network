=== Google Affiliate Network ===
Contributors: RobertPHeller
Donate link: http://www.deepsoft.com/GAN
Tags: gan,affiliate,widget,google,plugin,ads,shortcode
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 6.1.4

A Widget / shortcode plugin to display Google Affiliate Network ads. 
Both text links and image ads can be displayed.

== Description ==

A Widget / shortcode plugin to display Google Affiliate Network ads. 
Both text links and image ads can be displayed.  The widgets are
parameterized. There is backend access to the database table of links. 
Links can be added and/or edited by hand or can be added in bulk from a
Tab Separate Value file, as downloaded from Links (Beta) tab from the
Google Affiliate Network page.  A Tcl script is included to insert
E-Mailed links (link subscriptions).  The Widgets and shortcodes are
parameterized for both sidebar display (vertical layout) as well as
leader/footer display (horizontal layout).

The ads are rotated, with the least viewed ads from the lest viewed
avertisers being shown in preference to ads that have been viewed more
from avertisers than have been viewed more.  As ads are displayed,
their impression counts are incremented, which moves such ads to the
back of the list. Ads are displayed in iframes, which keeps the ads
contained on the page. Either widgets can be used to display ads in
side bars or shortcodes can be used to display ads in pages or posts
(both can be used if desired). A given ad unit can only display text or
image ads, not both. As a convience, a media insert button is available
on the post and page editor to generate and insert short codes to
display ad units on posts and pages.

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
insert ad frames into posts or pages (you can use the Insert Ad Unit
media button to generate and insert these short codes).  There is a help
page and a printable PDF user manual, that explains how to put ads in
the database and how to display ads on your blog pages or posts.

The link subscription handling script should be copied somewhere and
edited as needed (database access information).  You'll need to have Tcl
and mysqltcl installed. You will also need procmail (almost all Linux
system use procmail for their local mail delivery agent).  Read the
ganlinksToDB.tcl file carefully.

A downloadable PDF user manual is available at [gan_manual.pdf][pdfdownload].
This manual provides detailed documentation on how to use this plugin.

[pdfdownload]: http://www.deepsoft.com/downloadfile/gan_manual.pdf

== Frequently Asked Questions ==

Support is handled either with [Deepwoods Software's
Bugzilla][bugreport] (submit any bugs and feature requests to the
Bugzilla) or though the [Deepwoods Software's Support page][support]
(use this for  comments or for general questions).

= Something does not work. What should I do? =

Submit a bug at [Deepwoods Software's Bugzilla][bugreport].

= I have another question that is not listed here. What should I do? =

Submit one on [Deepwoods Software's Support page][support]. You can also submit
a documentation bug at [Deepwoods Software's Bugzilla][bugreport] as well.

[bugreport]: http://bugzilla.deepsoft.com/enter_bug.cgi?product=Google%20Affilliate%20Network%20Plugin%20for%20WordPress "Deepwoods Software Bugzilla"
[support]: http://www.deepsoft.com/support/ "Deepwoods Software's Support page"

== Screenshots ==

1. View of ad stats.
2. View of merchant stats.
3. View of ad database listing.
4. Front side view, showing both textual ads and banner ads.

== Changelog ==

= 6.1.4 =
* Update ganlinksToDB.tcl to support Google's V2 e-mailed gan links.

= 6.1.3 =
* Include force update Database button.

= 6.1.2 =
* Fix missing database field.

= 6.1.1 =
* Deal with (stupid) Utf-8 characters in Google's csv files.

= 6.1 =
* Minor bug in search/filtering fixed.

= 6.0 =
* Major re-write of the code.
  All of the helper files moved to an includes directory.
  All of the WP_List_Table classes re-written correctly.
  The manual has been heavily revised and the help page has also been revised.
  Things should generally work lots better and the admin pages should be
  better behaved, with sorting and searching and working items per page
  screen options.
  A fresh set of screen shots has also been created.
  

= 5.2.3.1 =
* Security and minor bug fixes.

= 5.2.3 =
* Google changed the link format file, again.

= 5.2.2 =
* Added Deepwoods Software's Amazon wish list to the ways to support this project.

= 5.2.1 =
* Minor change to product ad searching -- give more presidence to the 
  description pattern.

= 5.2 =
* Added matching in the description for product ad insertions.

= 5.1 =
* Google changed the format of the Export Links file! Code updated to match.
* Links can now be uploaded from either CSV or TSV files (code now uses fgetcsv()).
* Various small changes.

= 5.0.5 =
* Fixed typo in GAN.php

= 5.0.4 =
* Assorted minor fixes, including database field size change and minor link
  context updates.

= 5.0.3 =
* Fixed anoying bugs in GAN_InsertAdUnit (copy-and-paste error) and 
  GAN_Server (backwards loop exit condition).

= 5.0.2 =
* Fixed minor problem with dashboard widget.

= 5.0.1 =
* Updated localization.
* Fixed minor issue with the product ad server code.

= 5.0 =
* Added product ad unit support.
* Removed product feed => ShopperPress / Blog (This code will re-appear as 
  a separate plugin shortly.)

= 4.12 =
* Minor change: added merchant ID selection to the post and page Ad Insert 
  media button.

= 4.11.1 =
* Mantainence release.  Plugin Sponsors is closed, disable it, and always 
  display the PayPal Donate Button.

= 4.11 =
* Add support for product feeds *This code is experimental*.  Includes support
  for ShopperPress.
* Various minor bug fixes.

= 4.10 =
* Add selected merchant to widgets and shortcodes.
* Changed admin forms to use POST to avoid endlessly growing URLs.

= 4.9.4 =
* Fix bulk upload to handle unquoted image size strings

= 4.9.3 =
* Fix bulk upload to handle unquoted header strings

= 4.9.2 =
* Database creation and update changes.

= 4.9.1 =
* Documentation updates.

= 4.9 =
* Various changes to the database creation / update code (try to make things
  work on WIMP/WAMP servers).
* Added filter code to the brief ad stats.
* Added PluginSponser code.

= 4.8 =
* Update localization
* Add merchant-level enable/disable
* Minor typo fixed
* Update Google Affiliate Network link
* Comment out debug code 

= 4.7.1 =
* Fix ganlinksToDB.tcl to skip broken entries and to skip dupliicate link ids
* Add function to delete a merchant

= 4.7 =
* Add summary statistics to the top of the ad and merchant statistics pages.
* Update WP_List_Table classes to check for constructor name change.

= 4.6 =
* Improve the look of the dashboard impression stats widget.
* Various fixes to the readme file.
* Updated the internationalization.

= 4.5.1 =
* Major updates to the user manual, including the insertion of helpful 
figures.

= 4.5 =
* Added convience link buttons to admin pages on the dashboard widgets.
* Additional date fix.
* Updates to the readme file.
* Minor updates to the dashboard widgets.

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

= 6.1.1 =
Deal with (stupid) Utf-8 characters in Google's csv/tsv files.

= 6.0 =
Major rewrite of the code.

= 5.2.3 =
Google changed the link CSV/TSV file format, again.

= 5.2.2 =
Added Deepwoods Software's Amazon wish list to the ways to support this project.

= 5.2.1 =
Minor change in product ad searching -- give more presidence to the description pattern.

= 5.2 =
Added matching in the description for product ad insertions.

= 5.1 =
Code fixed to handle new GAN Links CSV/TSV format.

= 5.0.5 =
Maintainence release. Fix typo in GAN.php

= 5.0.4 =
Maintainence release. Minor bug fixes.

= 5.0.3 =
Maintainence release. Fixed bugs in GAN_InsertAdUnit and GAN_Server.

= 5.0.2 =
Maintainence release. Fixed minor problem with dashboard widget.

= 5.0.1 =
Maintainence release.  Updated localization.  Minor update in the
product ad server code.

= 5.0 =
Added product ad units, removed product feed code (see changelog for more 
details)

= 4.12 =
Minor change: added merchant ID selection to the post and page Ad
Insert media button.

= 4.11.1 =
Maintaince release. Plugin Sponsors is closed, disable it, and always display
the PayPal Donate Button.

= 4.11 =
Add support for product feeds. Note: the product feed code is still beta
test and  experimental.

= 4.10 =
Add selected merchant to widgets and shortcodes. 
Changed admin forms to use POST to avoid endlessly growing URLs.

= 4.9.4 =
Maintaince release: Important! Fixed bulk upload to handle unquoted image sizes

= 4.9.3 =
Maintaince release: Important! Fixed bulk upload to handle unquoted headers

= 4.9.2 =
Maintaince release: Important! Database creation and update changes.

= 4.9.1 =
Maintaince release: documentation updated.

= 4.8 =
Assorted minor fixes.  Minor feature enhancement. See the changelog for details.

= 4.7.1 =
Maintaince release: minor changes, see the changelog for details.

= 4.7 =
Critical WP 3.2 update: Update WP_List_Table classes to check for constructor name change.

= 4.5 =
Added convience link buttons to admin pages on the dashboard widgets. Merchant
stats date format fix. Updates to the readme file.

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
