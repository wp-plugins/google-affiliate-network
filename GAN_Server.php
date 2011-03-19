<?php

/* GAN_Server.php -- Serves ads for GAN iframes */

/* Minimal WP set up -- we are called directly, not through the normal WP
 * process.  We won't be displaying full fledged WP pages either.
 */
$wp_root = dirname(__FILE__) .'/../../../';
if(file_exists($wp_root . 'wp-load.php')) {
      require_once($wp_root . "wp-load.php");
} else if(file_exists($wp_root . 'wp-config.php')) {
      require_once($wp_root . "wp-config.php");
} else {
      exit;
}

@error_reporting(0);
  
global $wp_db_version;
if ($wp_db_version < 8201) {
	// Pre 2.6 compatibility (BY Stephen Rider)
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
		else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
	}
	if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

/* Load support files: constants and database */
require_once(dirname(__FILE__) . "/GAN_Constants.php");
require_once(dirname(__FILE__) . "/GAN_Database.php");

/* Make sure we are first and only program */
if (headers_sent()) {
  @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
  wp_die(__('Headers Sent', 'The headers have been sent by another plugin - there may be a plugin conflict.'));
}
  
/* http Headers */
@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
@header("Pragma: no-cache");
@header("Expires: 0");
@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
@header("Robots: none");
echo " ";	/* End of headers */

/* Start serving content (ads) */
?><html><HEAD><TITLE>Google Affiliate Network</TITLE><?php
$path = GAN_PLUGIN_CSS_URL . '/GAN.css';
echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
?></head><body style="margin:0px;padding:0px"><?php

/* Make sure we have the parameters we need. */
if (!isset($_GET['ulid']) && !isset($_GET['maxads']) ) {
  wp_die(__('Missing parameters!','The needed parameters are missing. Probably because this file was not called from an iframe.'));
}

/* Load parameters */
$instance = array( 'ulid' => $_GET['ulid'], 'maxads' => $_GET['maxads'] );

?><div style="margin:0px;padding:0px"><?php

/* Text or image ads? If height and width are not set (or are 0), serve text 
 * ads */

if ((!isset($_GET['height']) || $_GET['height'] == "0") && 
    (!isset($_GET['width'])  || $_GET['width'] == "0") ) {
	if ( $instance['ulid'] == 'GANright' ) {
		$theside = 1;
	} else {
		$theside = 0;
	}
	$maxads = $instance['maxads'];
	//echo "\n<!-- GAN_Widget::widget: \$maxads (1) = " . $maxads . " -->";
	if (empty($maxads)) $maxads = 4;
	//echo "\n<!-- GAN_Widget::widget: \$maxads (2) = " . $maxads . " -->";
	$merchlist = GAN_Database::ordered_merchants(0,0);
	//echo "\n<!-- GAN_Widget::widget: merchlist = ".print_r($merchlist,true)." -->\n";
	/* Display the ads, if any. Display maxads at most. */
	$numads = 0;
	if (! empty($merchlist) ) {
	      echo '<ul id="' . $instance['ulid'] . '" style="margin:0px;padding:0px">';
	      $loopcount = $maxads;
	      while ($numads < $maxads && $loopcount > 0) {
		//file_put_contents("php://stderr","*** GAN_Widget::widget: loopcount = ".$loopcount."\n");
		foreach ($merchlist as $merchid) {
		  //echo "\n<!-- GAN_Widget::widget: \$numads = " . $numads . " -->";
		  //echo "\n<!-- GAN_Widget::widget: GANAd is ".print_r($GANAd,true)." -->\n";
		  $adlist = GAN_Database::ordered_ads(0,0,$merchid);
		  //echo "\n<!-- GAN_Widget::widget: adlist = ".print_r($adlist,true)." -->\n";
		  if (empty($adlist)) {continue;}
		  //echo "\n<!-- GAN_Widget::widget: adlist[0] = ".$adlist[0]." -->\n";
		  $GANAd = GAN_Database::get_ad($adlist[0]);
		  GAN_Database::bump_counts($adlist[0]);
		  ?><li style="margin:0px;padding:0px"><a href="<?php echo $GANAd['ClickserverLink']; 
		  ?>"><?php echo $GANAd['LinkName']; 
		  ?></a> <?php echo $GANAd['MerchandisingText']; 
		  ?></li><?php
		  $numads++;
		  if ($numads >= $maxads) break;
		}
		$loopcount--;
	      }
	      ?></ul><?php
	}
} else {	/* Serve image ads of the specificed size */
	if (isset($_GET['height'])) {$instance['height'] = $_GET['height'];}
	if (isset($_GET['width'])) {$instance['width'] = $_GET['width'];}
	if ( $instance['ulid'] == 'GANright' ) {
		$theside = 1;
	} else {
		$theside = 0;
	}
	$maxads = $instance['maxads'];
	//echo "\n<!-- GAN_Widget::widget: \$maxads (1) = " . $maxads . " -->";
	if (empty($maxads)) $maxads = 4;
	//echo "\n<!-- GAN_Widget::widget: \$maxads (2) = " . $maxads . " -->";
	$merchlist = GAN_Database::ordered_merchants($instance['height'],$instance['width']);
	/* Display the ads, if any. Display maxads at most. */
	$numads = 0;
	if (! empty($merchlist) ) {
	      echo '<ul id="' . $instance['ulid'] . '" style="margin:0px;padding:0px">';
	      $loopcount = $maxads;
	      while ($numads < $maxads && $loopcount > 0) {
		foreach ($merchlist as $merchid) {
		  //echo "\n<!-- GAN_Widget::widget: \$numads = " . $numads . " -->";
		  $adlist = GAN_Database::ordered_ads($instance['height'],$instance['width'],$merchid);
		  if (empty($adlist)) {continue;}
		  $GANAd = GAN_Database::get_ad($adlist[0]);
		  GAN_Database::bump_counts($adlist[0]);
		  ?><li style="margin:0px;padding:0px"><a href="<?php echo $GANAd['ClickserverLink']; 
		  ?>"><img src="<?php echo $GANAd['ImageURL']; ?>"
		      width="<?php echo $GANAd['ImageWidth']; ?>"
		      height="<?php echo $GANAd['ImageHeight']; ?>"
		      alt="<?php echo $GANAd['AltText']; ?>">
		  </a></li><?php
		  $numads++;
		  if ($numads >= $maxads) break;
		}
		$loopcount--;
	      }
	      ?></ul><?php
	}
}

?></div></body></html>


