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

load_plugin_textdomain('gan',GAN_PLUGIN_URL.'/languages/',basename(GAN_DIR).'/languages/');

/* Make sure we are first and only program */
if (headers_sent()) {
  @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
  wp_die(__('The headers have been sent by another plugin - there may be a plugin conflict.','gan'));
}
  
/* http Headers */
@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
@header("Pragma: no-cache");
@header("Expires: 0");
@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
@header("Robots: none");
@header("X-Robots-Tag: noindex, nofollow");
echo " ";	/* End of headers */

/* Start serving content (ads) */
?><html><HEAD><TITLE>Google Affiliate Network</TITLE><?php
$path = GAN_PLUGIN_CSS_URL . '/GAN.css';
echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
?></head><body style="margin:0px;padding:0px"><?php

/* Make sure we have the parameters we need. */
if (!isset($_REQUEST['ulid']) && !isset($_REQUEST['maxads']) ) {
  wp_die(__('The needed parameters are missing. Probably because this file was not called from an iframe.','gan'));
}

/* Load parameters */
$instance = array( 'ulid' => $_REQUEST['ulid'] );
if (isset($_REQUEST['products'])) {
  $instance['namepat'] = $_REQUEST['namepat'];
  $instance['catpat'] = $_REQUEST['catpat'];
  $instance['brandpat'] = $_REQUEST['brandpat'];
} else {
  $instance['maxads'] = $_REQUEST['maxads'];
}
if ( isset($_REQUEST['target']) ) {
  $instance['target'] = $_REQUEST['target'];
} else {
  $instance['target'] = '_top';
}
if ( isset($_REQUEST['merchid']) ) {
  $instance['merchid'] = $_REQUEST['merchid'];
} else {
  $instance['merchid'] = '';
}

?><div style="margin:0px;padding:0px"><?php

/* Product, text or image ads? If height and width are not set (or are 0), serve text 
 * ads */

if (isset($_REQUEST['products'])) {
  if ($instance['merchid'] != '') {
    $merchlist[] = $instance['merchid'];
  } else {
    $merchlist = GAN_Database::ordered_merchants_prods();
  }
  echo "\n"; ?><!-- merchlist is <?php print_r($merchlist); ?> --><?php
  if (! empty($merchlist) ) {
    $numads = 0;
    while ($numads < 1) {
      foreach ($merchlist as $merchid) {
        $prods = GAN_Database::ordered_prod_ads($merchid,$instance['namepat'],
						$instance['catpat'],
						$instance['brandpat']);
        if (count($prods) == 0) continue;
      }
      if (count($prods) == 0) {
	if ($instance['brandpat'] != '') {
	  $instance['brandpat'] = '';
	} else if ($instance['catpat'] != '') {
	  $instance['catpat'] = '';
	} else if ($instance['namepat'] != '') {
	  $instance['namepat'] = '';
	} else {
	  break;
	}
      } else {
	echo "\n"; ?><!-- prods is <?php print_r($prods); ?> --><?php
	$GANProd = GAN_Database::get_product($prods[0]);
	echo "\n"; ?><!-- GANProd  is <?php print_r($GANProd); ?> --><?php
	GAN_Database::bump_product_counts($prods[0]);
	$numads++;
	?><p class="<?php echo $instance['ulid']; 
		?>"><a href="<?php echo $GANProd['Tracking_URL']; 
		?>" target="<?php echo $instance['target']; 
		?>"><img class="<?php echo $instance['ulid'];
		?>" src="<?php echo $GANProd['Creative_URL'];
		?>" alt="<?php echo $GANProd['Product_Name']; 
		?>" /></a><?php 
		   if ($instance['ulid'] == 'GANright') 
		      echo '<br clear="all" />'; 
		?><a href="<?php echo $GANProd['Tracking_URL'];
		?>" target="<?php echo $instance['target'];
		?>"><?php echo $GANProd['Product_Name']; ?></a> <?php
		echo $GANProd['Product_Descr']; 
		?> Price: $<?php printf("%.2f",$GANProd['Price']); ?></p><?php
      }
    }
  }
} else if ((!isset($_REQUEST['height']) || $_REQUEST['height'] == "0") && 
    (!isset($_REQUEST['width'])  || $_REQUEST['width'] == "0") ) {
	$maxads = $instance['maxads'];
	//echo "\n<!-- GAN_Widget::widget: \$maxads (1) = " . $maxads . " -->";
	if (empty($maxads)) $maxads = 4;
	//echo "\n<!-- GAN_Widget::widget: \$maxads (2) = " . $maxads . " -->";
	if ($instance['merchid'] != '') {
	  $merchlist[] = $instance['merchid'];
	} else {
	  $merchlist = GAN_Database::ordered_merchants(0,0);
	}
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
		  ?>" target="<?php echo $instance['target']; ?>"><?php echo $GANAd['LinkName']; 
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
	if (isset($_REQUEST['height'])) {$instance['height'] = $_REQUEST['height'];}
	if (isset($_REQUEST['width'])) {$instance['width'] = $_REQUEST['width'];}
	$maxads = $instance['maxads'];
	//echo "\n<!-- GAN_Widget::widget: \$maxads (1) = " . $maxads . " -->";
	if (empty($maxads)) $maxads = 4;
	//echo "\n<!-- GAN_Widget::widget: \$maxads (2) = " . $maxads . " -->";
	if ($instance['merchid'] != '') {
	  $merchlist[] = $instance['merchid'];
	} else {
	  $merchlist = GAN_Database::ordered_merchants($instance['height'],$instance['width']);
	}
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
		  ?>" target="<?php echo $instance['target']; ?>"><img src="<?php echo $GANAd['ImageURL']; ?>"
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


