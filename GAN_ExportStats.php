<?php

/* GAN_ExportStats -- Exports ad stats as a CSV file */

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

function gan_csv_quote($string) {
  return preg_replace('/"/','""', $string);
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
  
if (isset($_REQUEST['mode']) && in_array(strtolower($_REQUEST['mode']),array('ad','merch')) ) {
  $mode = strtolower($_REQUEST['mode']);
} else {
  $mode = 'ad';
}

if ( isset($_REQUEST['merchid']) ) {
  $merchid = $_REQUEST['merchid'];
} else {
  $merchid = '';
}
if ( isset($_REQUEST['imwidth']) ) {
  $imwidth = $_REQUEST['imwidth'];
} else {
  $imwidth = -1;
}
/* Build where clause */
global $wpdb;
if ( $merchid != '' || $imwidth != -1 ) {
  $wclause = ''; $and = '';
  if ($merchid != '') {
    $wclause = $wpdb->prepare(' MerchantID = %s',$merchid);
    $and = ' && ';
  }
  if ($imwidth != -1) {
    $wclause = $wpdb->prepare($wclause . $and . 
				' ImageWidth = %d',$imwidth);
  }
  $where = ' where ' . $wclause . ' ';
} else {
  $where = ' ';
}

$csv = '';

//file_put_contents("php://stderr","*** GAN_ExportStats.php: mode = ".$mode."\n");
//file_put_contents("php://stderr","*** GAN_ExportStats.php: where = ".$where."\n");


switch ($mode) {
  case 'ad':
	$ADStatsData = GAN_Database::get_GAN_AD_VIEW_data($where);
	//file_put_contents("php://stderr","*** GAN_ExportStats.php: ".count($ADStatsData)." rows of data in ADStatsData\n");
	$csv .= '"Advertiser","Link ID","Link Name","End Date","Image Width","Impressions","Last View"'."\n";
	foreach ((array)$ADStatsData as $ADStatRow) {
	  $csv .= '"'.gan_csv_quote(GAN_Database::get_merch_name($ADStatRow['MerchantID'])).'",';
	  $csv .= '"'.gan_csv_quote(GAN_Database::get_link_id($ADStatRow['adid'])).'",';
	  $csv .= '"'.gan_csv_quote(GAN_Database::get_link_name($ADStatRow['adid'])).'",';
	  $csv .= '"'.gan_csv_quote($ADStatRow['EndDate']).'",';
	  $csv .= $ADStatRow['ImageWidth'].',';
	  $csv .= $ADStatRow['Impressions'].',';
	  $csv .= '"'.gan_csv_quote($ADStatRow['LastRunDate']).'"';
	  $csv .= "\n";
	}
	break;
  case 'merch':
	$MerchStatsData = GAN_Database::get_GAN_MERCH_STATS_data('');
	//file_put_contents("php://stderr","*** GAN_ExportStats.php: ".count($MerchStatsData)." rows of data in MerchStatsData\n");
	$csv .= '"Advertiser","Impressions","Last View"'."\n";
	foreach ((array)$MerchStatsData as $MerchStatRow) {
	  $csv .= '"'.gan_csv_quote(GAN_Database::get_merch_name($MerchStatRow['MerchantID'])).'",';
	  $csv .= $MerchStatRow['Impressions'].',';
	  $csv .= '"'.gan_csv_quote($MerchStatRow['LastRunDate']).'"'."\n";
	}
	break;
}

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=gan_stats.csv");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Length: " . strlen($csv));

echo $csv;
exit;

