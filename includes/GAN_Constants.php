<?php

/* GAN_Constants.php
 * Assorted global constants.  These constants are used in various places in
 * the code.
 */

define('GAN_INCLUDES_DIR', plugin_dir_path(__FILE__));	    /* The Plugin includes directory */
define('GAN_DIR', dirname(GAN_INCLUDES_DIR));	    /* The Plugin directory */
define('GAN_MAINFILE', GAN_DIR . '/GAN.php');	    /* Root file of the plugin */
function _gan_get_header_value($file,$headerkey) {
  $fp = fopen($file,'r');
  if (!$fp) return '';
  while ($line = fgets($fp)) {
    if (preg_match("/^\s*\*\s*$/",$line) > 0) {
      $result = '';
      break;
    }
    if (preg_match('/^\s*\*\s*'.$headerkey.':\s*(.*)$/',$line,$matches) > 0) {
      $result = $matches[1];
      break;
    }
  }
  fclose($fp);
  return $result;
}

define('GAN_PLUGIN_NAME', 'GAN_Plugin'); /* Name of the plugin */
define('GAN_DISPLAY_NAME', _gan_get_header_value(GAN_MAINFILE,'Plugin Name'));
define('GAN_VERSION', _gan_get_header_value(GAN_MAINFILE,'Version'));
define('GAN_PLUGIN_URL',plugin_dir_url(GAN_MAINFILE));
define('GAN_PLUGIN_CSS_URL', GAN_PLUGIN_URL . '/css');
define('GAN_PLUGIN_IMAGE_URL', GAN_PLUGIN_URL . '/images');
global $wpdb;
/* Database table names */
$result = $wpdb->get_row("show variables where Variable_name = 'lower_case_file_system'",ARRAY_A);
$lower_case_file_system = $result['Value'];
$result = $wpdb->get_row("show variables where Variable_name = 'lower_case_table_names'",ARRAY_A);
$lower_case_table_names = $result['Value'];
if ($lower_case_table_names > 0 || $lower_case_file_system == 'ON') {
define('GAN_AD_TABLE',$wpdb->prefix . "dws_gan");	/* Base ad table */
define('GAN_MERCH_TABLE',$wpdb->prefix . "dws_gan_merch");       /* Merchant Table */
/* Old (pre V3) tables and views */
/* Ad statistics */
define('GAN_AD_STATS_TABLE',$wpdb->prefix . "dws_gan_ad_stats");
/* Merchant statistics */
define('GAN_MERCH_STATS_TABLE',$wpdb->prefix . "dws_gan_merch_stats");
/* Views (combinations of Ad database and statistics tables) */
define('GAN_AD_STATS_TABLE_VIEW',$wpdb->prefix . "dws_gan_ad_stats_view");
define('GAN_MERCH_STATS_TABLE_VIEW',$wpdb->prefix . "dws_gan_merch_stats_view");
} else {
define('GAN_AD_TABLE',$wpdb->prefix . "DWS_GAN");	/* Base ad table */
define('GAN_MERCH_TABLE',$wpdb->prefix . "DWS_GAN_MERCH");       /* Merchant Table */
/* Old (pre V3) tables and views */
/* Ad statistics */
define('GAN_AD_STATS_TABLE',$wpdb->prefix . "DWS_GAN_AD_STATS");
/* Merchant statistics */
define('GAN_MERCH_STATS_TABLE',$wpdb->prefix . "DWS_GAN_MERCH_STATS");
/* Views (combinations of Ad database and statistics tables) */
define('GAN_AD_STATS_TABLE_VIEW',$wpdb->prefix . "DWS_GAN_AD_STATS_VIEW");
define('GAN_MERCH_STATS_TABLE_VIEW',$wpdb->prefix . "DWS_GAN_MERCH_STATS_VIEW");
/* 3.1 Tables: GAN_ProdAdTable */
define('GAN_PRODUCTS_AD_TABLE',$wpdb->prefix . "dws_gan_products_ad_table");
}
