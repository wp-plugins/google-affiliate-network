<div class="wrap"><?php $this->admin_tabs('gan-database-help'); ?><br clear="all" />
<div id="icon-gan-help" class="icon32"><br />
</div><h2><?php _e('Help Using the Google Affiliate Network Plugin','gan'); ?><?php $this->InsertVersion(); ?></h2>
<?php $this->InsertPayPalDonateButton(); ?>
<ul>
<li><a href="#Introduction">Introduction</a></li>
<li><a href="#Installation">Installation</a></li>
<li><a href="#Configuring">Configuring</a></li>
<li><a href="#InsertingAds">Inserting Links</a></li>
<li><a href="#EditingAds">Editing Links </a></li>
<li><a href="#InsertingProducts">Inserting Products</a></li>
<li><a href="#EditingProducts">Editing Products </a></li>
<li><a href="#ShowingAds">Showing Links and Products</a></li>
<li><a href="#AdSubsriptions">Link Subscriptions</a></li>
<li><a href="#Statistics">Statistics</a></li>
<li><a href="<?php echo GAN_PLUGIN_URL . '/doc/gan_manual.pdf'; ?>">Download printable manual</a></li>
<li><a href="#SupportGAN">Ways to support the Google Affiliate Network Plugin</a></li>
</ul>
<a name="Introduction"></a><h3>Introduction</h3>
<p>I wrote this plugin to display ads from the Google Affiliate Network
on Deepwoods Software's WordPress powered website. This plugin uses a
database of ad links and products to display.  The ad links and
products are displayed in rotation, using the simple method of counting
ad impressions and giving priority to the advertisers with the least
impressions and display ads with the least impressions first that are
expiring soonest.  As ads and advertisers are displayed, their
impression counts are incremented, which moves them down the list. 
This means that all ads are displayed fairly, with preference given to
new ad links and products and to ad links which are expiring soonest.
After using &quot;in house&quot; for a while, I have made this plugin
available to other WordPress users who also using the Google Affiliate
Network as a source of advertising revenue.</p>
<a name="Installation"></a><h3>Installation</h3> 
<p>Installation is just a matter of installing from the new plugin
page.  Once installed and activated, the plugin is ready to start
displaying affiliate ads.</p> 
<a name="Configuring"></a><h3>Configuring</h3> 
<p>There are two configuration options: one for automatically deleting
expired ad links and for adding additional CSS code to fine tune the
styling of ad units.  The option to automatically delete expired ads is
on by default. While it is possible to disable automatically deleting
expired ads it is not recommended.</p>
<p>If you have upgraded from an older version of the plugin, the
configure page will display a button to upgrade the database to the new
version.</p>
<a name="InsertingAds"></a><h3>Inserting Ad Links</h3> 
<p>In order to display ad links, you need to have some ad links in your
database. There are two ways to insert ads: manually, one by one or in
bulk from a TSV (Tab Separated Value) or CSV (Comma Separated Value) file. Manual insertion is done on
the <strong>Add new</strong> admin page and bulk insertion is done on
the <strong>Add new bulk</strong> admin page.</p>
<h4>Add new admin page (manual insertion)</h4> <p>This page has a form
for adding a single ad.  The fields include:  <ul>
<li><strong>Advertiser:</strong> This is the advertiser's name.</li>
<li><strong>Link ID:</strong> This is the (unique) Link Id code.</li>
<li><strong>Link Name:</strong> This is the name of the link.  It is
used as the anchor text for text ads.</li>  <li><strong>Merchandising
Text:</strong> This is some ad copy for the link and is displayed with
the ad link.</li> <li><strong>Alt Text:</strong> This is the
alternative text for image ads.</li> <li><strong>Start Date:</strong>
The is the starting date, in the format yyyy-mm-dd.</li> 
<li><strong>End Date:</strong> This is the ending date, in the format
yyyy-mm-dd.</li>  <li><strong>Clickserver Link:</strong> This is the
tracking URL for the ad.</li>  <li><strong>ImageURL:</strong> This is
the URL of the ad image for image ads.</li> 
<li><strong>ImageHeight:</strong> This is the height of the image (0
for text ads).</li>  <li><strong>ImageWidth:</strong> This is the width
of the image (0 for text ads).</li>  <li><strong>LinkURL:</strong> This
is the Link URL.</li> <li><strong>PromoType:</strong> This is the type
of promotion.</li> <li><strong>MerchantID:</strong> This is the
(unique) merchant id.</li> <li><strong>enabled?</strong> This indicates
of the ad is enabled or not.</li>  </ul> </p> <a
name="EditingAds"></a><h3>Editing Ads</h3> <p>When displaying the data
on the main admin page, links are provided to edit, delete, or toggle
the enabled flag for each ad. The ads are displayed ordered by
expiration date, with the soonest to expire displayed first. It is
possible to select only a single merchant's ads to be displayed and/or
a single width of ad (a width of zero implies text ads).</p>
<h4>Add new bulk admin page (bulk insertion)</h4>
<p>This page uploads a TSV or CSV file of ads previously downloaded from your
Google Affiliate Network management page. You get this file by visiting
your Google Affiliate Network management page and clicking the Links
tab. On this page you can select the sorts of ads you would like by
selecting one or more of your approved advertisers and selecting the
type of ads (text and/or banner), and other criteria such as size, etc.
It is then possible to export these ads as a TSV file, which can then
be downloaded. This same file can in turn be uploaded to the GAN plugin
and the ads in this file will be added to your ad database.</p> 
<a name="InsertingProducts"></a><h3>Inserting Products</h3>
<p>Products are managed from GAN Product Database page. The advertiser,
product name, product brand, and enabled flag are displayed in this
table. Products are intially sorted by product name, but can be sorted
by product brand instead. It is possible to filter the displayed ad by
advertiser. You can also search by Product Name. There is a button to
enable all products. Products can be deleted or have their enable flag 
toggled in bulk.  Ads can be individually edited, viewed, deleted or  
have their enable flags toggled.  You can also delete, disable, and    
enable by merchant.</p>
<h4>Inserting Products</h4>
<p>In order to display products, you need to have some products in your
database. There are two ways to insert products: manually, one by one
or in bulk from a TSV (Tab Separated Value) or CSV (Comma Separated
Value) file. Manual insertion is done on the <strong>Add new
(Product)</strong> admin page and bulk insertion is done on the
<strong>Add new (Products in) bulk</strong> admin page.</p>
<h5>Inserting one product</h5>
<p>The Add new Product page has a form for adding (and editing and
viewing) a single product. Generally, this page is not usually used,
the Add new (Products in) bulk is used for adding products in bulk. The
fields include:</p>
<ul>
<li><strong>Advertiser:</strong> This is the advertiser's name.</li>
<li><strong>Product Name:</strong> This is the name of the product.</li>
<li><strong>Product Description:</strong> This is the description of the product.</li>
<li><strong>Tracking URL:</strong> This is the &quot;buy&quot; link for the product.</li>
<li><strong>Creative URL:</strong> This is the URL of the product's image.</li>
<li><strong>Product Category:</strong> This is the product's category.</li>
<li><strong>Product Brand:</strong> This is the product's brand.</li>
<li><strong>Product UPC:</strong> This is the product's UPC.</li>
<li><strong>Price:</strong> This is the product's price.</li>
<li><strong>Merchant ID:</strong> This is the advertiser id.</li>
<li><strong>enabled?</strong> This indicates whether the product is enabled or not.</li>
</ul>
<h5>Add new products in bulk admin page (bulk product insertion)</h5>
<p>The Add new Products in bulk page uploads a TSV or CSV file of
products previously downloaded from your Google Affiliate Network
management page. You get this file by visiting your Google Affiliate
Network management page and clicking the Products tab. On this page you
can search for products that match a set of search terms. It is then
possible to export these ads as a TSV or CSV file, using the Export As
button and selecting &quot;Tab Separated Values&quot; option, which can
then be downloaded. This same file can in turn be uploaded to the GAN
plugin and the products in this file will be added to your product
database.</p>
<a name="ShowingAds"></a><h3>Showing Ad Links and Products</h3> 
<p>There are two ways to show ads on your pages and/or posts.  You can
use one of the three widgets (GAN Image Widget, GAN Widget, or GAN
Product Widget) or one of the two shortcodes (GAN_Text,  GAN_Image,
GAN_Product).  The widgets of course need to go into a 'sidebar' that
supports widgets.  The shortcodes can go into any post or page.</p> 
<h4>GAN Widget</h4> 
<p>The GAN Widget has six parameters:
<ul>
<li><strong>Number of As:</strong> The number of ads to display.</li>
<li><strong>Orientation:</strong> The orientation of the ads. Horizontal
means the ads are arranged side by side like one row of a table and
vertical means the ads are arranged in a vertical list. Typically the
horizontal orientation is suitable for a wide but short ad frame and the
vertical orientation is suitable for sky scrapper type ad unit.</li>
<li><strong>Advertisers:</strong> This parameter can be used to limit the ad 
links to a single advertiser.</li>
<li><strong>Target:</strong> The link target to use. Can be either Same 
Window or New Window or Tab.</li>
<li><strong>Ad frame width:</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>Ad frame height:</strong> The height of the ad frame.</li>
</ul></p>
<h4>GAN Image Widget</h4>
<p>The GAN Image Widget has eight parameters:
<ul>
<li><strong>Number of  ads:</strong> The number of ads to display.</li>
<li><strong>Width:</strong> The image width of the image ads.</li>
<li><strong>Height:</strong> The image height of the image ads.</li>
<li><strong>Orientation:</strong> The orientation of the ads. Horizontal
means the ads are arranged side by side like one row of a table and
vertical means the ads are arranged in a vertical list. Typically the
horizontal orientation is suitable for a wide but short ad frame and the
vertical orientation is suitable for sky scrapper type ad unit.</li>
<li><strong>Advertisers:</strong> This parameter can be used to limit the ad 
links to a single advertiser.</li>
<li><strong>Target:</strong> The link target to use. Can be either Same 
Window or New Window or Tab.</li>
<li><strong>Ad frame width:</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>Ad frame height:</strong> The height of the ad frame.</li>
</ul></p>
<h4>GAN Product Widget</h4>
<p>The GAN Product Widget has nine parameters:
<ul>
<li><strong>Orientation:</strong> The orientation of the ads. Horizontal means the
product has the textual description to the right of the image and
vertical means the product has the textual description below the image.
Typically the horizontal orientation is suitable for a wide but short
ad frame and the vertical orientation is suitable for sky scrapper type
ad unit.  That is the vertical orientation is best in a narrow sidebar
and the horizontal orientation would be suitable for a `side bar' in the
wide part of the page (such as a side bar that is between posts or above
or below posts or page content).</li>
<li><strong>Advertisers:</strong> The parameter can be used to limit the product ads to
a single advertiser.  The default is All, which is to use products from all
advertisers.</li>
<li><strong>Target:</strong> The link target to use. Can be either Same 
Window or New Window or Tab.</li>
<li><strong>Name Pattern:</strong> This is a pattern that is matched to the product
name and if not empty will limit products to those that match this name.</li>
<li><strong>Category Pattern:</strong> This is a pattern that is matched to the
product category and if not empty will limit products to those that
match this category.</li>
<li><strong>Brand Pattern:</strong> This is a pattern that is matched to the product
brand and if not empty will limit products to those that match this brand.</li>
<li><strong>Description Pattern:</strong> This is a pattern that is matched to the
product description and if not empty will limit products to those that
match this description.</li>
<li><strong>Ad frame width:</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>Ad frame height:</strong> The height of the ad frame.</li>
</ul></p>
<h4>GAN_Text shortcode</h4>
<p>The GAN_Text shortcode has same six parameters as the GAN
Widget:
<ul><li><strong>maxads</strong> An integer, with the default being 4.
The maximum number of ads to display.</li>
<li><strong>orientation</strong> The orientation of the ads, one of
'vertical' (the default) or 'horizontal'. Horizontal means the ads are
arranged side by side like one row of a table and      vertical means
the ads are arranged in a vertical list. Typically the horizontal
orientation is suitable for a wide but short ad frame and the vertical
orientation is suitable for sky scrapper type ad unit.</li>
<li><strong>merchid</strong> This parameter can be used to limit the ad 
links to a single advertiser.</li>
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
<p>The GAN_Image shortcode has same eight parameters as the GAN Image
Widget.
<ul><li><strong>maxads</strong> An integer, with the default being 4.
The maximum number of ads to display.</li>
<li><strong>orientation</strong> The orientation of the ads, one of
'vertical' (the default) or 'horizontal'. Horizontal means the ads are
arranged side by side like one row of a table and      vertical means
the ads are arranged in a vertical list. Typically the horizontal
orientation is suitable for a wide but short ad frame and the vertical
orientation is suitable for sky scrapper type ad unit.</li>
<li><strong>merchid</strong> This parameter can be used to limit the ad 
links to a single advertiser.</li>
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
<h4>GAN_Product shortcode</h4>
<p>The GAN_Product shortcode has same nine parameters as the GAN Product
Widget: 
<ul>
<li><strong>orientation</strong> The orientation of the ads, one of &quot;vertical&quot; or
&quot;horizontal&quot; (the default). The orientation of the ads. Horizontal
means the product has the textual description to the right of the image
and vertical means the product has the textual description below the
image. Typically the horizontal orientation is suitable for a wide but
short ad frame and the vertical orientation is suitable for sky
scrapper type ad unit.  That is the vertical orientation is best in a
narrow sidebar and the horizontal orientation would be suitable for a
`side bar' in the wide part of the page (such as a side bar that is
between posts or above or below posts or page content).</li>
<li><strong>merchid</strong> The parameter can be used to limit the product ads to
a single advertiser.  The default is &quot;, which is to use products from all
advertisers.</li>
<li><strong>target</strong> The link target to use one of &quot;same&quot; (the
default) or &quot;new&quot;.</li>
<li><strong>namepat</strong> This is a pattern that is matched to the product
name and if not empty will limit products to those that match this name.</li>
<li><strong>catpat</strong> This is a pattern that is matched to the
product category and if not empty will limit products to those that
match this category.</li>
<li><strong>brandpat</strong> This is a pattern that is matched to the product
brand and if not empty will limit products to those that match this brand.</li>
<li><strong>descrpat</strong> This is a pattern that is matched to the
product description and if not empty will limit products to those that
match this description.</li>
<li><strong>ifwidth</strong> The width of the ad frame. A value
of zero will cause the frame to use all of the available space.</li>
<li><strong>ifheight</strong> The height of the ad frame.</li>
</ul></p>
<p>Here is an example -- a horizontal product ad the full with of the post
content column and 200 pixels high, limited to products with linux in
their description:</p>
<pre>
[GAN_Product orientation='horizontal' ifwidth='100%' ifheight='200' target='new' merchid='' namepat='' catpat='' brandpat='' descrpat='linux']
</pre>
<a name="AdSubsriptions"></a><h3>Link Subscriptions</h3> 
<p>A Tcl script is included to process E-Mailed Ad Subscriptions and
insert them into the database.  This requires the ability to receive
E-Mail on the server running the database server and requires that Tcl
and the MySQLTcl package be installed as well as the use of procmail as
a mail delivery agent.</p>
<a name="Statistics"></a><h3>Statistics</h3>
<p>Both ad and merchant statistics are available for display.  The
statistics are ordered from fewest impressions to most impressions. A
summary of the statistics is also displayed on the dashboard.</p>
<a name="SupportGAN"></a><h3>Ways to support the Google Affiliate Network Plugin</h3>
<div id="gan_donateHelp"><form action="https://www.paypal.com/cgi-bin/webscr" method="post"><?php _e('Donate to Google Affiliate Network plugin software effort.','gan'); ?><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="B34MW48SVGBYE"><input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/scr/pixel.gif" width="1" height="1"></form></div><br clear="all" />
<div id="gan_donateHelp">Buy some Deepwoods Software <a href="http://www.deepsoft.com/home/products/dwsmerch/" target="_blank">merchandise</a></div><br clear="all" />
<div id="gan_donateHelp">Deepwoods Software's <a href="http://amzn.com/w/3679UKP8RZRI9">Amazon Wish List</a></div><br clear="all" />
</div>
