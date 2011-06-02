<div class="wrap"><div id="icon-gan-help" class="icon32"><br />
</div><h2><?php _e('Help Using the Google Affliate Network Plugin','gan'); ?><?php $this->InsertVersion(); ?></h2>
<?php $this->PluginSponsor(); ?>
<ul>
<li><a href="#Installation">Installation</a></li>
<li><a href="#Configuring">Configuring</a></li>
<li><a href="#InsertingAds">Inserting Ads</a></li>
<li><a href="#ShowingAds">Showing Ads</a></li>
<li><a href="#EditingAds">Editing Ads</a></li>
<li><a href="#AdSubsriptions">Ad Subsriptions</a></li>
<li><a href="#Statisics">Statisics</a></li>
<li><a href="<?php echo GAN_PLUGIN_URL . '/doc/gan_manual.pdf'; ?>">Download printable manual</a></li>
</ul>
<a name="Installation"></a><h3>Installation</h3> 
<p>Installation is just a matter of installing from the new plugin
page.  Once installed and activated, the plugin is ready to start
displaying affilate ads.</p> 
<a name="Configuring"></a><h3>Configuring</h3> 
<p>There is only one configuration option: Autoexpire. The option is
true by default.  It enables the automatic removal of ads that have
expired. While it is possible to disable this option it is not
recomended</p>
<p>If you have upgraded from an older version of the plugin, the
configure page will display a button to upgrade the database to the new
version.</p>
<a name="InsertingAds"></a><h3>Inserting Ads</h3> 
<p>In order to display ads, you need to have some ads in your database.
There are two ways to insert ads: manually, one by one or in bulk from
a TSV (Tab Separated Value) file. Manual insertion is done on the
<strong>Add new</strong> admin page and bulk insertion is done on the
<strong>Add new bulk</strong> admin page.</p>
<h4>Add new admin page (manual insertion)</h4>
<p>This page has a form for adding a single ad.  The fields include: 
<ul>
<li><strong>Advertiser:</strong> This is the advertiser's name.</li>
<li><strong>Link ID:</strong> This is the (unique) Link Id code.</li>
<li><strong>Link Name:</strong> This is the name of the link.  It is
used as the anchor text for text ads.</li> 
<li><strong>Merchandising Text:</strong> This is some ad copy for the
link and is displayed with the ad link.</li>
<li><strong>Alt Text:</strong> This is the alterniitive text for image
ads.</li>
<li><strong>Start Date:</strong> The is the starting date, in the
format yyyy-mm-dd.</li> 
<li><strong>End Date:</strong> This is the ending date, in the format
yyyy-mm-dd.</li> 
<li><strong>Clickserver Link:</strong> This is the tracking URL for the
ad.</li> 
<li><strong>ImageURL:</strong> This is the URL of the ad image for
image ads.</li> 
<li><strong>ImageHeight:</strong> This is the height of the image (0
for text ads).</li> 
<li><strong>ImageWidth:</strong> This is the width of the image (0 for
text ads).</li> 
<li><strong>LinkURL:</strong> This is the Link URL.</li>
<li><strong>PromoType:</strong> This is the type of promotion.</li>
<li><strong>MerchantID:</strong> This is the (unique) merchant id.</li>
<li><strong>enabled?</strong> This indicates of the ad is enabled or
not.</li> 
</ul> </p>
<h4>Add new bulk admin page (bulk insertion)</h4>
<p>This page uploads a TSV file of ads previously downloaded from your
Google Affiliate Network management page. You get this file by visiting
your Google Affiliate Network management page and clicking the Links
tab. On this page you can select the sorts of ads you would like by
selecting one or more of your approved advertisers and selecting the
type of ads (text and/or banner), and other criteria such as size, etc.
It is then possible to export these ads as a TSV file, which can then
be downloaded. This same file can in turn be uploaded to the GAN plugin
and the ads in this file will be added to your ad database.</p> 
<a name="ShowingAds"></a><h3>Showing Ads</h3> 
<p>There are two ways to show ads on your pages and/or posts.  You can
use one of the two widgets (GAN Image Widget or GAN Widget) or one of
the two shortcodes (GAN_Text or GAN_Image).  The widgets of course need
to go into a 'sidebar' that supports widgets.  The shortcodes can go
into any post or page.</p> 
<h4>GAN Widget</h4> 
<p>The GAN Widget has five parameters:
<ul>
<li><strong>Max ads:</strong> The maximum number of ads to display.</li>
<li><strong>Orientation:</strong> The orientation of the ads. Horizontal
means the ads are arranged side by side like one row of a table and
vertical means the ads are arranged in a vertical list. Typically the
horizontal orientation is suitable for a wide but short ad frame and the
vertical orientation is suitable for skyscrapper type ad unit.</li>
<li><strong>Target:</strong> The link target to use. Can be either Same 
Window or New Window or Tab.</li>
<li><strong>Ad frame width:</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>Ad frame height:</strong> The height of the ad frame.</li>
</ul></p>
<h4>GAN Image Widget</h4>
<p>The GAN Image Widget has seven parameters:
<ul>
<li><strong>Max ads:</strong> The maximum number of ads to display.</li>
<li><strong>Width:</strong> The image width of the image ads.</li>
<li><strong>Height:</strong> The image height of the image ads.</li>
<li><strong>Orientation:</strong> The orientation of the ads. Horizontal
means the ads are arranged side by side like one row of a table and
vertical means the ads are arranged in a vertical list. Typically the
horizontal orientation is suitable for a wide but short ad frame and the
vertical orientation is suitable for skyscrapper type ad unit.</li>
<li><strong>Target:</strong> The link target to use. Can be either Same 
Window or New Window or Tab.</li>
<li><strong>Ad frame width:</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>Ad frame height:</strong> The height of the ad frame.</li>
</ul></p>
<h4>GAN_Text shortcode</h4>
<p>The GAN_Text shortcode has same five parameters as the GAN
Widget:
<ul><li><strong>maxads</strong> An integer, with the default being 4.
The maximum number of ads to display.</li>
<li><strong>orientation</strong> The orientation of the ads, one of
'vertical' (the default) or 'horizontal'. Horizontal means the ads are
arranged side by side like one row of a table and      vertical means
the ads are arranged in a vertical list. Typically the horizontal
orientation is suitable for a wide but short ad frame and the vertical
orientation is suitable for skyscrapper type ad unit.</li>
<li><strong>target</strong> The link target to use, one of 'same' (the
default) or 'new'.</li>
<li><strong>ifwidth</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>ifheight</strong> The height of the ad frame.</li>
</ul></p>
<p>Here is an example -- 5 text ads arranged horizontally in a 798x70 frame:<br />
<pre>
[GAN_Text maxads=5 orientation='horizontal' ifwidth=798 ifheight=70]
</pre></p>
<h4>GAN_Image shortcode</h4>
<p>The GAN_Image shortcode has same seven parameters as the GAN Image
Widget.
<ul><li><strong>maxads</strong> An integer, with the default being 4.
The maximum number of ads to display.</li>
<li><strong>orientation</strong> The orientation of the ads, one of
'vertical' (the default) or 'horizontal'. Horizontal means the ads are
arranged side by side like one row of a table and      vertical means
the ads are arranged in a vertical list. Typically the horizontal
orientation is suitable for a wide but short ad frame and the vertical
orientation is suitable for skyscrapper type ad unit.</li>
<li><strong>target</strong> The link target to use, one of 'same' (the
default) or 'new'.</li>
<li><strong>width</strong> The image width of the image ads. The
default is 120.</li>
<li><strong>height</strong> The image height of the image ads. The
default is 60.</li>
<li><strong>ifwidth</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>ifheight</strong> The height of the ad frame.</li>
</ul></p>
<p>Here is an example -- 2 468x60 banners arranged vertically in a 473x65 frame:<br />
<pre>
[GAN_Image maxads=2 orientation='vertical' ifwidth=473 ifheight=65 width=468 height=60]
</pre></p>
<a name="EditingAds"></a><h3>Editing Ads</h3>
<p>When displaying the data on the main admin page, links are provided
to edit, delete, or toggle the enabled flag for each ad. The ads are
displayed ordered by expiration date, with the soonest to expire
displayed first. It is possible to select only a single merchant's ads
to be displayed and/or a single width of ad (a width of zero implies
text ads).</p>
<a name="AdSubsriptions"></a><h3>Ad Subsriptions</h3> 
<p>A Tcl script is included to process E-Mailed Ad Subsriptions and
insert them into the database.  This requires the ability to receive
E-Mail on the server running the database server and requires that Tcl
and the MySQLTcl package be installed as well as the use of procmail as
a mail delivery agent.</p>
<a name="Statisics"></a><h3>Statisics</h3>
<p>Both ad and merchant statisics are available for display.  The
statisics are ordered from fewest impressions to most impressions. A
summary of the statisics is also displayed on the dashboard.</p>
</div>
