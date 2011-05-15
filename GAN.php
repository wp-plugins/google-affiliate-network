<?php
/**
 * Plugin Name: Google Affiliate Network widget
 * Plugin URI: http://http://www.deepsoft.com/GAN
 * Description: A Widget to display Google Affiliate Network ads
 * Version: 4.0
 * Author: Robert Heller
 * Author URI: http://www.deepsoft.com/
 * License: GPL2
 *
 *  Google Affiliate Network plugin / widgets
 *  Copyright (C) 2010,2011  Robert Heller D/B/A Deepwoods Software
 *			51 Locke Hill Road
 *			Wendell, MA 01379-9728
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *
 */

/* Load constants */
require_once(dirname(__FILE__) . "/GAN_Constants.php");

/* Additional file-specific constants */
define('GAN_FILE', basename(__FILE__));
define('GAN_PATH', GAN_DIR . '/' . GAN_FILE);

/* Load Database code */
require_once(GAN_DIR . "/GAN_Database.php");

/* Main plugin class. Implements the basic admin functions of the plugin. */
class GAN_Plugin {

	var $gan_db_list_table;
	var $ad_stats_list_table;
	var $merch_stats_list_table;

	/* Constructor: register our activation and deactivation hooks and then
	 * add in our actions.
         */
	function GAN_Plugin() {
		// Add the installation and uninstallation hooks
		register_activation_hook(GAN_PATH, array($this,'install'));
		register_deactivation_hook(GAN_PATH, array($this,'deinstall'));
		// Actions: widgets, admin menu, headings (CSS), and dashboard.
		add_action('widgets_init', array($this,'widgets_init'));
		add_action('admin_menu', array($this,'admin_menu'));
		add_action('wp_head', array($this,'wp_head'));
		add_action('admin_head', array($this,'admin_head'));
		add_action('wp_dashboard_setup', array($this,'wp_dashboard_setup'));
		add_action('gan_daily_event',array($this,'check_autoexpire'));
		add_option('wp_gan_autoexpire','yes');
	        /* add_option('wp_gan_disablesponsor','no'); */
		load_plugin_textdomain('gan',GAN_PLUGIN_URL.'/languages/',
					  basename(GAN_DIR).'/languages/');
		//$fp = fopen(GAN_FILE,'r');
		//while ($line = fgets($fp)) {
		//  $line = trim($line,"\n");
		//  if (preg_match('/Version:[[:space:]]*(.*)$/',$line,$matches) ) {
		//    $version = trim($matches[1]);
		//    break;
		//  }
		//}
		//fclose($fp);
	}
	/* Activation hook: create database tables. */
	function install() {
		$dbvers = GAN_Database::database_version();
		if ($dbvers == 0.0) {
		  GAN_Database::make_ad_table();
		  GAN_Database::make_merchs_table();
		} else if ($dbvers < 3.0) {
		  GAN_Database::upgrade_database();
		}
		wp_schedule_event(mktime(2,0,0), 'daily', 'gan_daily_event');
	}
        /* Deactivation hook: nothing at present. */
	function deinstall() {
		wp_clear_scheduled_hook('gan_daily_event');
	}

	/* Initialize our widgets */
	function widgets_init() {
	  register_widget( 'GAN_Widget' );        /* Text links */
	  add_shortcode('GAN_Text', array('GAN_Widget','shortcode'));
	  register_widget( 'GAN_ImageWidget' );   /* Image links */
	  add_shortcode('GAN_Image', array('GAN_ImageWidget','shortcode'));
	}

	/* Add in our Admin Menu */
	function admin_menu() {
	  $screen_id = add_menu_page( __('GAN Database','gan'), __('GAN DB','gan'), 'manage_options', 
		 'gan-database-page', array($this,'admin_database_page'), 
		 GAN_PLUGIN_IMAGE_URL.'/GAN_menu.png');
	  add_action("load-$screen_id", array($this,'_init_db_list_table') );
	  $screen_id = add_submenu_page( 'gan-database-page', __('Add new GAN DB element','gan'), 
		    __('Add new','gan'), 
		    'manage_options', 'gan-database-add-element', 
		    array($this,'admin_add_element'));
	  add_action("load-$screen_id", array($this,'_init_db_list_table') );
	  $screen_id = add_submenu_page( 'gan-database-page', __('Add new GAN DB elements in bulk','gan'), 
		    __('Add new bulk','gan'), 
		    'manage_options', 'gan-database-add-element-bulk', 
		    array($this,'admin_add_element_bulk'));
	  add_action("load-$screen_id", array($this,'_init_db_list_table') );
	  $screen_id = add_submenu_page( 'gan-database-page', __('Ad Impression Statistics','gan'),
	  	    __('Ad Stats','gan'),
		    'manage_options', 'gan-database-ad-impstats',
		    array($this,'admin_ad_impstats'));
	  add_action("load-$screen_id", array($this,'_init_ad_stats_list_table') );
	  $screen_id = add_submenu_page( 'gan-database-page', __('Merchant Impression Statistics','gan'),
	  	    __('Merchant Stats','gan'),
		    'manage_options', 'gan-database-merch-impstats',
		    array($this,'admin_merch_impstats'));
	  add_action("load-$screen_id", array($this,'_init_merch_stats_list_table') );
	  add_submenu_page( 'gan-database-page', __('Configure Options','gan'),
			    __('Configure','gan'),'manage_options', 
			    'gan-database-options',
			    array($this,'admin_configure_options'));
	  add_submenu_page( 'gan-database-page', __('Help Using the Google Affliate Network Plugin','gan'),
	  		    __('Help','gan'),'manage_options',
			    'gan-database-help',
			    array($this,'admin_help')); 
	}
	function _init_db_list_table() {
	  add_screen_option('per_page',array('label' => __('Rows','gan')) );
	  $this->register_List_Table('GAN_DB_List_Table');
	  if (! isset($this->gan_db_list_table) ) {
	    $this->gan_db_list_table = new GAN_DB_List_Table();
	    $this->gan_db_list_table->set_row_actions(
		array( 'Edit' => add_query_arg(
					array('page' => 'gan-database-add-element',
					      'mode' => 'edit'),
					admin_url('admin.php')),
		       'View' => add_query_arg(
					array('page' => 'gan-database-add-element',
					      'mode' => 'view'),
					admin_url('admin.php')),
		       'Delete' => add_query_arg(
					array('page' => 'gan-database-page',
					      'action' => 'delete'),
					admin_url('admin.php')),
		       'Toggle Enable' => add_query_arg(
					array('page' => 'gan-database-page',
					      'action' => 'enabletoggle'),
					admin_url('admin.php')) ));
	  }					      
	}
	function _init_ad_stats_list_table() {
	  add_screen_option('per_page',array('label' => __('Rows','gan')));
	  $this->register_List_Table('Ad_Stats_List_Table');
	  if (! isset($this->ad_stats_list_table) ) {
	    $this->ad_stats_list_table = new Ad_Stats_List_Table();
	  }
	}
	function _init_merch_stats_list_table() {
	  add_screen_option('per_page',array('label' => __('Rows','gan') ));
	  $this->register_List_Table('Merch_Stats_List_Table');
	  if (! isset($this->ad_stats_list_table) ) {
	    $this->ad_stats_list_table = new Merch_Stats_List_Table();
	  }
	}
        function register_List_Table($class) {
	  switch ($class) {
	    case 'GAN_DB_List_Table':
		require_once GAN_DIR . '/GAN_DB_List_Table.php';
		return $class;
	    case 'Ad_Stats_List_Table':
		require_once GAN_DIR . '/Ad_Stats_List_Table.php';
		return $class;
	    case 'Merch_Stats_List_Table':
		require_once GAN_DIR . '/Merch_Stats_List_Table.php';
		return $class;
	  }
	}	
	function InsertPayPalDonateButton() {
	  ?><div id="gan_donate"><form action="https://www.paypal.com/cgi-bin/webscr" method="post"><?php _e('Donate to Google Affiliate Network plugin software effort.','gan'); ?><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="B34MW48SVGBYE"><input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/scr/pixel.gif" width="1" height="1"></form></div><br clear="all" /><?php
	}

	function PluginSponsor() {
	  if (/*get_option('wp_gan_disablesponsor') == 'yes'*/ true) {
	    $this->InsertPayPalDonateButton();
	  } else {
	    ?><script></script><?php
	  }
	}
	function InsertH2AffiliateLoginButton() {
	  ?><a target="_blank" href="http://www.connectcommerce.com/global/login.html" class="button add-new-h2"><?php _e('Login into Google Affiliate Network','gan'); ?></a><?php
	}
	/* Front side head action: load our style sheet */
	function wp_head() {
	  $path = GAN_PLUGIN_CSS_URL . '/GAN.css';

	  echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
	}

	/* Admin side head action: load our admin style sheet */
	function admin_head() {
	  $this->wp_head();
	  $path = GAN_PLUGIN_CSS_URL . '/GAN_admin.css';
	  echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
	}

	/* Main admin page.  List the ads in the database */
	function admin_database_page() {
	  $this->gan_db_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><div id="icon-gan-db" class="icon32"><br /></div>
	    <h2><?php _e('GAN Database','gan'); ?> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-database-add-element')); 
		?>" class="button add-new-h2"><?php _e('Add New','gan'); 
		?></a> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-database-add-element-bulk')); 
		?>" class="button add-new-h2"><?php 
			_e('Add New in Bulk','gan'); ?></a><?php 
					$this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); ?>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-page" />
		<?php $this->gan_db_list_table->views();
		      $this->gan_db_list_table->display(); ?></form></div><?php
	}

	/* Add element to ad database */
	function admin_add_element() {
	  $message = $this->gan_db_list_table->prepare_one_item();
	  ?><div class="wrap"><div id="<?php echo $this->gan_db_list_table->add_item_icon(); ?>" class="icon32"><br />
	    </div><h2><?php echo $this->gan_db_list_table->add_item_h2(); ?></h2>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form action="" method="get">
	    <input type="hidden" name="page" value="gan-database-add-element" />
	    <?php $this->gan_db_list_table->display_one_item_form(add_query_arg(array('page' => 'gan-database-page'))); ?></form></div><?php
	}

        /* Add elements in bulk (from a TSV file) to ad database */
	function admin_add_element_bulk() {
	  $message = $this->gan_db_list_table->process_bulk_upload();
	  ?><div class="wrap"><div id="icon-gan-add-db" class="icon32"><br />
	    </div><h2><?php _e('Add Elements in bulk to the GAN Database','gan'); ?></h2>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form action="post" method="get"  enctype="multipart/form-data" >
	    <input type="hidden" name="page" value="gan-database-add-element-bulk" />
	    <?php $this->gan_db_list_table->display_bulk_upload_form(add_query_arg(array('page' => 'gan-database-page'))); ?></form></div><?php
	}
	function admin_ad_impstats() {
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	  global $wpdb;
	  /* Filters: merchant id and image width (0 == text ad). */
	  if ( isset($_GET['merchid']) ) {
	    $merchid = $_GET['merchid'];
	  } else {
	    $merchid = "";
	  }
	  if ( isset($_GET['imwidth']) ) {
	    $imwidth = $_GET['imwidth'];
	  } else {
	    $imwidth = -1;
	  }
	  /* Build where clause. */
	  if ( $merchid != "" || $imwidth != -1 ) {
	    $wclause = ""; $and = "";
	    if ($merchid != "") {
		$wclause = $wpdb->prepare($wclause . " MerchantID = %s",
					  $merchid);
		$and = " && ";
	    }
	    if ($imwidth != -1) $wclause = $wpdb->prepare($wclause . $and . 
							  " ImageWidth = %d",
							  $imwidth);
	    $where = " where " . $wclause . " ";
	    $wand  = " && " . $wclause . " ";
	  } else {
	    $wand = " ";
	    $where = " ";
	  }
	  /* Handle row action links: */
	  if ( isset($_GET['id']) && isset($_GET['action']) ) {
	    $id     = $_GET['id'];
	    $action = $_GET['action'];
	    if( $action == 'zero' ) {
	      GAN_Database::zero_GAN_AD_STAT($id);
	    }
	  /* Global actions: */
	  } else if ( isset($_GET['zerostats']) ) {
	    GAN_Database::zero_GAN_AD_STATS($where);
	  }
	  /* Handle pagenation. */
	  if ( isset($_GET['pagenum']) ) {
	    $pagenum = $_GET['pagenum'];
	  } else {
	    $pagenum = 1;
	  }
	  if ( isset($_GET['GAN_rows_per_page']) ) {
	    $per_page = $_GET['GAN_rows_per_page'];
	  } else {
	    $per_page = 20;
	  }
	  $skiprecs = ($pagenum - 1) * $per_page;
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><div id="icon-gan-ad-imp" class="icon32"><br /></div><h2><?php _e('Ad Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); ?>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-ad-impstats" />
		<?php GAN_Database::merchdropdown($merchid) ?>&nbsp;<?php GAN_Database::imwidthdropdown($imwidth); ?>
		<input type="submit" name="filter" class="button" value="<?php _e('Filter','gan'); ?>" />
		<label for="gan-rows-per-page"><?php _e('Rows per page','gan'); ?></label><input type="text" class="screen-per-page" name="GAN_rows_per_page" id="rows-per-page" maxlength="3" value="<?php echo $per_page; ?>" />
	        <input type="submit" name="screenopts" class="button" value="<?php _e('Apply','gan'); ?>" /></form><?php
	  /* Get database rows */
	  $ADStatsData = GAN_Database::get_GAN_AD_VIEW_data($where);
	  if ( ! empty($ADStatsData) ) {
	    /* Non empty results.  Get row count. */
	    $ADStatsDataRowCount = GAN_Database::get_GAN_AD_VIEW_row_count($where);
	    $num_pages = ceil($ADStatsDataRowCount / $per_page);
	    /* Build page links. */
	     $page_links = paginate_links( array(
	        'base' => add_query_arg( array ('pagenum' => '%#%', 'GAN_rows_per_page' => $per_page ) ),
	        'format' => '',
	        'prev_text' => __('&laquo;','gan'),
	        'next_text' => __('&raquo;','gan'),
	        'total' => $num_pages,
	        'current' => $pagenum
	     )); ?>
	<div class="tablenav">
	<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'gan' ) . '</span>%s',
	        number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
	        number_format_i18n( min( $pagenum * $per_page, $ADStatsDataRowCount ) ),
	        number_format_i18n( $ADStatsDataRowCount ),
	        $page_links
	); echo $page_links_text; ?></div>
	<form method="get" action="<?php echo admin_url('admin.php'); ?>">
	<input type="hidden" name="page" value="gan-database-ad-impstats" />
	<?php $this->hidden_filter_fields(); ?>
	<div class="alignleft actions">
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" />
	<a href="<?php echo add_query_arg(array('mode' => 'ad','where' => $where),
				     GAN_PLUGIN_URL.'/GAN_ExportStats.php'); ?>"
	   class="button-primary"><?php _e('Download CSV','gan'); ?></a></div>
	<br class="clear" /></div>
	     	     <table class="widefat page fixed" cellspacing="2">
		<thead>
		<tr><th align="left" width="10%" scope="col" class="manage-column"><?php _e('Advertiser','gan'); ?></th>
		    <th align="left" width="10%"  scope="col" class="manage-column"><?php _e('Link ID','gan'); ?></th>
		    <th align="left" width="40%" scope="col" class="manage-column"><?php _e('Link Name','gan'); ?></th>
		    <th align="right" width="10%" scope="col" class="manage-column"><?php _e('End Date','gan'); ?></th>
		    <th align="right" width="8%"  scope="col" class="manage-column"><?php _e('Image Width','gan'); ?></th>
		    <th align="right" width="12%"  scope="col" class="manage-column"><?php _e('Impressions','gan'); ?></th>
		    <th align="right" width="10%"  scope="col" class="manage-column"><?php _e('Last View','gan'); ?></th></tr>
		</thead>
		<tfoot>
		<tr><th align="left" width="10%" scope="col" class="manage-column"><?php _e('Advertiser','gan'); ?></th>
		    <th align="left" width="10%"  scope="col" class="manage-column"><?php _e('Link ID','gan'); ?></th>
		    <th align="left" width="40%" scope="col" class="manage-column"><?php _e('Link Name','gan'); ?></th>
		    <th align="right" width="10%" scope="col" class="manage-column"><?php _e('End Date','gan'); ?></th>
		    <th align="right" width="8%"  scope="col" class="manage-column"><?php _e('Image Width','gan'); ?></th>
		    <th align="right" width="12%"  scope="col" class="manage-column"><?php _e('Impressions','gan'); ?></th>
		    <th align="right" width="10%"  scope="col" class="manage-column"><?php _e('Last View','gan'); ?></th></tr>
		</tfoot>
		<tbody>
		<?php /* Display each row. */
		      $index = 0; $alt = 'alternate';
		      foreach ((array)$ADStatsData as $ADStatRow) {
			$index++;       /* count record. */ 
			if ($index <= $skiprecs) {continue;}	/* Previous pages. */
		        if ($index >  ($skiprecs+$per_page)) {break;} /* Next pages. */
			$id = $ADStatRow['id'];    /* Id for row actions. */
			?><tr class="<?php echo $alt; ?> iedit"><td valign="top" width="10%" align="left"><?php
			  echo GAN_Database::get_merch_name($ADStatRow['MerchantID']);  /* Advertiser name */
			?></td><td valign="top" width="10%" align="left"><?php
			  echo GAN_Database::get_link_id($ADStatRow['adid']);   /* Link ID */
			?></td><td valign="top" width="40%" align="left" ><?php 
			  echo GAN_Database::get_link_name($ADStatRow['adid']);   /* Link name */
		        ?><br /><a href="<?php 
			  $this->make_page_query('gan-database-ad-impstats',$id,'zero');  /* zero stat */
			?>"><?php _e('Zero','gan'); ?></a></td><td valign="top" width="10%" align="right" ><?php
			  echo $ADStatRow['EndDate']; /* End Date */
			?></a></td><td valign="top" width="8%" align="right" ><?php 
			  echo $ADStatRow['ImageWidth']; /* Image Width */
		        ?></td><td valign="top" width="12%" align="right" ><?php 
			  echo $ADStatRow['Impressions']; /* Impressions */
		        ?></td><td valign="top" width="10%" align="right" ><?php 
			  echo $ADStatRow['LastRunDate']; /* Last Run Data */
			?></td></tr><?php
			/* Toggle row backgrounds */
			if ($alt == '') {
			  $alt = 'alternate';
			} else {
			  $alt = '';
			}
		      } ?></tbody>
		</table><div class="tablenav">
	<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'gan' ) . '</span>%s',
	        number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
	        number_format_i18n( min( $pagenum * $per_page, $ADStatsDataRowCount ) ),
	        number_format_i18n( $ADStatsDataRowCount ),
	        $page_links
	); echo $page_links_text; ?></div>
	<form method="get" action="<?php echo admin_url('admin.php'); ?>">
	<input type="hidden" name="page" value="gan-database-ad-impstats" />
	<?php $this->hidden_filter_fields(); ?>
	<div class="alignleft actions">
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" />
	<a href="<?php echo add_query_arg(array('mode' => 'ad','where' => $where),
				     GAN_PLUGIN_URL.'/GAN_ExportStats.php'); ?>"
	   class="button-primary"><?php _e('Download CSV','gan'); ?></a></div>
	<br class="clear" /></div></form>
	<?php
	  } else {
	  ?><h4><?php _e('No matching entries found.','gan'); ?></h4><?php
	  }
	  ?><form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-ad-impstats" />
		<?php GAN_Database::merchdropdown($merchid) ?>&nbsp;<?php GAN_Database::imwidthdropdown($imwidth); ?>
		<input type="submit" name="filter" class="button" value="<?php _e('Filter','gan'); ?>" />
		<label for="gan-rows-per-page"><?php _e('Rows per page','gan'); ?></label><input type="text" class="screen-per-page" name="GAN_rows_per_page" id="rows-per-page" maxlength="3" value="<?php echo $per_page; ?>" />
	        <input type="submit" name="screenopts" class="button" value="<?php _e('Apply','gan'); ?>" /></form><?php
	}

	function admin_merch_impstats() {
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.', 'gan') );
	  }
	  /* Handle row action links: */
	  if ( isset($_GET['id']) && isset($_GET['action']) ) {
	    $id     = $_GET['id'];
	    $action = $_GET['action'];
	    if( $action == 'zero' ) {
	      GAN_Database::zero_GAN_MERCH_STAT($id);
	    }
	  /* Global actions: */
	  } else if ( isset($_GET['zerostats']) ) {
	    GAN_Database::zero_GAN_MERCH_STATS('');
	  }
	  /* Handle pagenation. */
	  if ( isset($_GET['pagenum']) ) {
	    $pagenum = $_GET['pagenum'];
	  } else {
	    $pagenum = 1;
	  }
	  if ( isset($_GET['GAN_rows_per_page']) ) {
	    $per_page = $_GET['GAN_rows_per_page'];
	  } else {
	    $per_page = 20;
	  }
	  $skiprecs = ($pagenum - 1) * $per_page;
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><div id="icon-gan-merch-imp" class="icon32"><br /></div><h2><?php _e('Merchant Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); ?>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<label for="gan-rows-per-page"><?php _e('Rows per page','gan'); ?></label><input type="text" class="screen-per-page" name="GAN_rows_per_page" id="rows-per-page" maxlength="3" value="<?php echo $per_page; ?>" />
	        <input type="submit" name="screenopts" class="button" value="<?php _e('Apply','gan'); ?>" /></form><?php
	  /* Get database rows */
	  $MerchStatsData = GAN_Database::get_GAN_MERCH_STATS_data('');
	  if ( ! empty($MerchStatsData) ) {
	    /* Non empty results.  Get row count. */
	    $MerchStatsDataRowCount = GAN_Database::get_GAN_MERCH_STATS_row_count('');
	    $num_pages = ceil($MerchStatsDataRowCount / $per_page);
	    /* Build page links. */
	     $page_links = paginate_links( array(
	        'base' => add_query_arg( array ('pagenum' => '%#%', 'GAN_rows_per_page' => $per_page ) ),
	        'format' => '',
	        'prev_text' => __('&laquo;', 'gan'),
	        'next_text' => __('&raquo;', 'gan'),
	        'total' => $num_pages,
	        'current' => $pagenum
	     )); ?>
	<div class="tablenav">
	<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s','gan' ) . '</span>%s',
	        number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
	        number_format_i18n( min( $pagenum * $per_page, $MerchStatsDataRowCount ) ),
	        number_format_i18n( $MerchStatsDataRowCount ),
	        $page_links
	); echo $page_links_text; ?></div>
	<form method="get" action="<?php echo admin_url('admin.php'); ?>">
	<input type="hidden" name="page" value="gan-database-merch-impstats" />
	<?php $this->hidden_filter_fields(); ?>
	<div class="alignleft actions">
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" />
	<a href="<?php echo add_query_arg(array('mode' => 'merch'),
				     GAN_PLUGIN_URL.'/GAN_ExportStats.php'); ?>"
	   class="button-primary"><?php _e('Download CSV','gan'); ?></a></div>
	<br class="clear" /></div>
	     	     <table class="widefat page fixed" cellspacing="2">
		<thead>
		<tr><th align="left" width="75%" scope="col" class="manage-column"><?php _e('Advertiser','gan'); ?></th>
		    <th align="right" width="15%"  scope="col" class="manage-column"><?php _e('Impressions','gan'); ?></th>
		    <th align="right" width="10%"  scope="col" class="manage-column"><?php _e('Last View','gan'); ?></th></tr>
		</thead>
		<tfoot>
		<tr><th align="left" width="75%" scope="col" class="manage-column"><?php _e('Advertiser','gan'); ?></th>
		    <th align="right" width="15%"  scope="col" class="manage-column"><?php _e('Impressions','gan'); ?></th>
		    <th align="right" width="10%"  scope="col" class="manage-column"><?php _e('Last View','gan'); ?></th></tr>
		</tfoot>
		<tbody>
		<?php /* Display each row. */
		      $index = 0; $alt = 'alternate';
		      foreach ((array)$MerchStatsData as $MerchStatRow) {
			$index++;       /* count record. */ 
			if ($index <= $skiprecs) {continue;}	/* Previous pages. */
		        if ($index >  ($skiprecs+$per_page)) {break;} /* Next pages. */
			$id = $MerchStatRow['id'];    /* Id for row actions. */
			?><tr class="<?php echo $alt; ?> iedit"><td valign="top" width="75%" align="left"><?php
			  echo GAN_Database::get_merch_name($MerchStatRow['MerchantID']);  /* Advertiser name */
		        ?><br /><a href="<?php 
			  $this->make_page_query('gan-database-merch-impstats',$id,'zero');  /* Zero stat */
			?>"><?php _e('Zero','gan'); ?></a></td><td valign="top" width="15%" align="right" ><?php 
			  echo $MerchStatRow['Impressions']; /* Impressions */
		        ?></td><td valign="top" width="10%" align="right" ><?php 
			  echo $MerchStatRow['LastRunDate']; /* Last Run Data */
			?></td></tr><?php
			/* Toggle row backgrounds */
			if ($alt == '') {
			  $alt = 'alternate';
			} else {
			  $alt = '';
			}
		      } ?></tbody>
		</table><div class="tablenav">
	<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'gan' ) . '</span>%s',
	        number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
	        number_format_i18n( min( $pagenum * $per_page, $MerchStatsDataRowCount ) ),
	        number_format_i18n( $MerchStatsDataRowCount ),
	        $page_links
	); echo $page_links_text; ?></div>
	<form method="get" action="<?php echo admin_url('admin.php'); ?>">
	<input type="hidden" name="page" value="gan-database-merch-impstats" />
	<?php $this->hidden_filter_fields(); ?>
	<div class="alignleft actions">
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" />
	<a href="<?php echo add_query_arg(array('mode' => 'merch'),
				     GAN_PLUGIN_URL.'/GAN_ExportStats.php'); ?>"
	   class="button-primary"><?php _e('Download CSV','gan'); ?></a></div>
	<br class="clear" /></div></form>
	<?php
	  } else {
	  ?><h4><?php _e('No matching entries found.','gan'); ?></h4><?php
	  }
	  ?><form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<label for="gan-rows-per-page"><?php _e('Rows per page','gan'); ?></label><input type="text" class="screen-per-page" name="GAN_rows_per_page" id="rows-per-page" maxlength="3" value="<?php echo $per_page; ?>" />
	        <input type="submit" name="screenopts" class="button" value="<?php _e('Apply','gan'); ?>" /></form><?php
	}

	function admin_configure_options() {
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.', 'gan') );
	  }
	  if ( isset($_GET['saveoptions']) ) {
	    $autoexpire = $_GET['gan_autoexpire'];
	    update_option('wp_gan_autoexpire',$autoexpire);
	    /* $disablesponsor = $_GET['gan_disablesponsor'];
	       update_option('wp_gan_disablesponsor',$disablesponsor); */
	    ?><div id="message"class="updated fade"><p><?php _e('Options Saved','gan'); ?></p></div><?php
	  } else if ( isset($_GET['upgradedatabase']) ) {
	    GAN_Database::upgrade_database();
	    ?><div id="message"class="updated fade"><p><?php _e('Database Upgraded','gan'); ?></p></div><?php
	  }
	  /* Head of page, filter and screen options. */
	  $autoexpire = get_option('wp_gan_autoexpire');
	  /* $disablesponsor = get_option('wp_gan_disablesponsor'); */
	  ?><div class="wrap"><div id="icon-gan-options" class="icon32"><br /></div><h2><?php _e('Configure Options','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); ?>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
	    	<input type="hidden" name="page" value="gan-database-options" />
		<table class="form-table">
		  <tr valign="top">
		    <th scope="row"><label for="gan_autoexpire" style="width:20%;"><?php _e('Enable Autoexpire?','gan'); ?></label></th>
		    <td><input type="radio" name="gan_autoexpire" value="yes"<?php
				if ($autoexpire == 'yes') {
				  echo ' checked="checked" ';
				} 
			?> /><?php _e('Yes','gan'); ?>&nbsp;<input type="radio" name="gan_autoexpire" value="no"<?php
				if ($autoexpire == 'no') {
				  echo ' checked="checked" ';
				}
			?> /><?php _e('No','gan'); ?></td></tr>
		  <!-- <tr valign="top">
		    <th scope="row"><label for="gan_disablesponsor" style="width:20%;"><?php _e('Disable sponsor messages?','gan'); ?></label></th>
		    <td><input type="radio" name="gan_disablesponsor" value="yes"<?php
				if ($disablesponsor == 'yes') {
				  echo ' checked="checked" ';
				} 
			?> /><?php _e('Yes','gan'); ?>&nbsp;<input type="radio" name="gan_disablesponsor" value="no"<?php
				if ($disablesponsor == 'no') {
				  echo ' checked="checked" ';
				}
			?> /><?php _e('No','gan'); ?></td></tr> -->
		</table>
		<p>
			<input type="submit" name="saveoptions" class="button-primary" value="<?php _e('Save Options','gan'); ?>" />
		</p><?php 
		if (GAN_Database::database_version() < 3.0) {
		  ?><p><?php _e('Your database needs to be upgraded.','gan'); ?>&nbsp;<input type="submit" name="upgradedatabase" class="button-primary" value="<?php _e('Upgrade Database','gan'); ?>"></p><?php
		} ?></form></div><?php
	}

	function admin_help () {
	  require_once(GAN_DIR.'/GAN_Help.php');
	}

	function InsertVersion() {
	  ?><span id="gan_version"><?php printf(__('Version: %s','gan'),GAN_VERSION) ?></span><?php
	}
	/*
	 * Build an action link
	 */

	function make_page_query($page, $id, $what)
	{
		if ( isset($_GET['GAN_rows_per_page']) ) {
		  $per_page = $_GET['GAN_rows_per_page'];
		} else {
		  $per_page = 20;
		}
		if ( isset($_GET['pagenum']) ) {
		  $pagenum = $_GET['pagenum'];
		} else {
		  $pagenum = 1;
		}
		if ( isset($_GET['merchid']) ) {
		  $merchid = $_GET['merchid'];
		} else {
		  $merchid = "";
		}
	        if ( isset($_GET['imwidth']) ) {
		  $imwidth = $_GET['imwidth'];
		} else {
		  $imwidth = -1;
	 	}
		echo admin_url('admin.php') . "?page=".$page."&"
			. "GAN_rows_per_page=$per_page&"
			. "pagenum=$pagenum&"
			. "merchid=$merchid&"
			. "imwidth=$imwidth&"
			. "id=$id&"
			. "action=$what ";
	}

	/*
	 * Build a cancel link
	 */

	function cancel_page_query()
	{
		
		if ( isset($_GET['GAN_rows_per_page']) ) {
		  $per_page = $_GET['GAN_rows_per_page'];
		} else {
		  $per_page = 20;
		}
		if ( isset($_GET['pagenum']) ) {
		  $pagenum = $_GET['pagenum'];
		} else {
		  $pagenum = 1;
		}
		if ( isset($_GET['merchid']) ) {
		  $merchid = $_GET['merchid'];
		} else {
		  $merchid = "";
		}
	        if ( isset($_GET['imwidth']) ) {
		  $imwidth = $_GET['imwidth'];
		} else {
		  $imwidth = -1;
	 	}
		echo admin_url('admin.php') . "?page=gan-database-page&"
			. "GAN_rows_per_page=$per_page&"
			. "pagenum=$pagenum&"
			. "merchid=$merchid&"
			. "imwidth=$imwidth";
	}

	/*
	 * Create hidden fields for filter options.
	 */

	function hidden_filter_fields ()
	{
		if ( isset($_GET['GAN_rows_per_page']) ) {
		  $per_page = $_GET['GAN_rows_per_page'];
		} else {
		  $per_page = 20;
		}
		if ( isset($_GET['pagenum']) ) {
		  $pagenum = $_GET['pagenum'];
		} else {
		  $pagenum = 1;
		}
		if ( isset($_GET['merchid']) ) {
		  $merchid = $_GET['merchid'];
		} else {
		  $merchid = "";
		}
	        if ( isset($_GET['imwidth']) ) {
		  $imwidth = $_GET['imwidth'];
		} else {
		  $imwidth = -1;
	 	}
		?><input type="hidden" name="GAN_rows_per_page" value="<?php echo $per_page; ?>" />
		  <input type="hidden" name="pagenum" value="<?php echo $pagenum; ?>" />
		  <input type="hidden" name="merchid" value="<?php echo $merchid; ?>" />
		  <input type="hidden" name="imwidth" value="<?php echo $imwidth; ?>" /><?php
	}

	/* Set up dashboard widgets */
	function wp_dashboard_setup() {
	  wp_add_dashboard_widget('gan_dashboard_widget', __('GAN Database Stats','gan'), array($this,'database_stats_widget'));
	  wp_add_dashboard_widget('ganimp_dashboard_widget', __('GAN Impression Stats','gan'), array($this,'impression_stats_widget'));
	}

	/* Database statisics dashboard widget */
	function database_stats_widget() {

	  $total_ads = GAN_Database::total_ads();
	  $disabled  = GAN_Database::disabled_count();
	  $advertisers = GAN_Database::advertiser_count();
	  $widths    = GAN_Database::width_count();

	  ?><div class="table">
		<table class="ganstats">
		<tr><td class="ganstats_total"><?php echo $total_ads; ?></td>
		    <td class="ganstats_label"><?php _e('Total ads','gan'); ?></td>
		    <td class="ganstats_total"><?php echo $disabled; ?></td>
		    <td class="ganstats_label"><?php _e('Disabled','gan'); ?></td></tr>
		<tr><td class="ganstats_total"><?php echo $advertisers; ?></td>
		    <td class="ganstats_label"><?php _e('Advertisers','gan'); ?></td>
		    <td class="ganstats_total"><?php echo $widths; ?></td>
		    <td class="ganstats_label"><?php _e('Widths','gan'); ?></td></tr>
		</table>
		</div><?php 
	}
	/* Impression statisics dashboard widget */
	function impression_stats_widget() {
	  $top_merch = GAN_Database::top_merch();
	  $max_merch_impressions = GAN_Database::max_merch_impressions();
	  $merch_statistics = GAN_Database::merch_statistics();
	  //echo "\n<!-- GAN_Plugin::impression_stats_widget: top_merch is ".print_r($top_merch,true)." -->\n";
	  //echo "\n<!-- GAN_Plugin::impression_stats_widget: max_merch_impressions = ".$max_merch_impressions." -->\n";
	  ?>
	  <h4 style="width: 100%;text-align: center;"><?php _e('Merchants'); ?></h4><br />
	  <table class="ganmerchimpstats" width="100%">
	    <thead>
		<tr>
		   <th scope="col"><?php _e('Maximum','gan'); ?></th><th scope="col"><?php _e('Minimum','gan'); ?></th>
		   <th scope="col"><?php _e('Average','gan'); ?></th><th scope="col"><?php _e('Std. Deviation','gan'); ?></th>
		   <th scope="col"><?php _e('Variance','gan'); ?></th></tr>
	    <tbody>
		<tr>
		   <td width="20%" style="text-align: center;"><?php echo $merch_statistics['maximum']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $merch_statistics['minimum']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $merch_statistics['average']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $merch_statistics['std_deviation']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $merch_statistics['variance']; ?></td></tr>
	    </tbody>
	  </table>
	  <table class="ganmerchtopimps" width="100%">
	  <tbody>
		<tr>
		   <th scope="col"><span class="auraltext"><?php _e('Merchant','gan'); ?></span> </th>
		   <th scope="col"><span class="auraltext"><?php _e('Number of Impressions','gan'); ?></span> </th>
		</tr>
		<?php
		   $loop = 1;
		   $first = 'first';
		   $num_merch = count($top_merch);
		   $last = "";
		   echo "\n<!-- GAN_Plugin::impression_stats_widget: num_merch = ".$num_merch." -->\n";
		   if ($num_merch > 0 && $max_merch_impressions > 0) {
		     foreach ((array)$top_merch as $merch) {
			$imps = $merch['Impressions'];
			$merchid = $merch['MerchantID'];
			$width = ($imps / $max_merch_impressions * 90);
			if ($loop == $num_merch) $last = 'last';
			echo '
			<tr>
				<td class="'.$first.'" style="width:25%;">'.GAN_Database::get_merch_name($merchid).'</td>
				<td class="value '.$first.' '.$last.'"><img src="'.GAN_PLUGIN_IMAGE_URL.'/bar.png" alt="" height="16" width="'.$width.'%" />'.$imps.'</td>
			</tr>
			';
			$first = "";
			$loop++;
		     }
		   } else {
		     echo '<tr><td class="first last" style="border-right:1px solid #e5e5e5" colspan="2">No stats yet</td></tr>';
		   }
		?>
	  </tbody>
	  </table>
	  <?php
	  $top_ads = GAN_Database::top_ads();
	  $ad_statistics = GAN_Database::ad_statistics();
	  $max_ad_impressions = GAN_Database::max_ad_impressions();
	  ?>
	  <h4 style="width: 100%;text-align: center;">Ads</h4><br />
	  <table class="ganadimpstats" width="100%">
	    <thead>
		<tr>
		   <th scope="col"><?php _e('Maximum','gan'); ?></th><th scope="col"><?php _e('Minimum','gan'); ?></th>
		   <th scope="col"><?php _e('Average','gan'); ?></th><th scope="col"><?php _e('Std. Deviation','gan'); ?></th>
		   <th scope="col"><?php _e('Variance','gan'); ?></th></tr>
	    <tbody>
		<tr>
		   <td width="20%" style="text-align: center;"><?php echo $ad_statistics['maximum']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $ad_statistics['minimum']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $ad_statistics['average']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $ad_statistics['std_deviation']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $ad_statistics['variance']; ?></td></tr>
	    </tbody>
	  </table>
	  <table class="ganadtopimps" width="100%">
	  <tbody>
		<tr>
		   <th scope="col"><span class="auraltext"><?php _e('Ad','gan'); ?></span> </th>
		   <th scope="col"><span class="auraltext"><?php _e('Number of Impressions','gan'); ?></span> </th>
		</tr>
		<?php
		   $loop = 1;
		   $first = 'first';
		   $num_ads = count($top_ads);
		   $last = "";
		   echo "\n<!-- GAN_Plugin::impression_stats_widget: num_ads = ".$num_ads." -->\n";
		   if ($num_ads > 0 && $max_ad_impressions > 0) {
		     foreach ((array)$top_ads as $ad) {
			$imps = $ad['Impressions'];
			$id = $ad['adid'];
			$width = ($imps / $max_ad_impressions * 90);
			if ($loop == $num_ads) $last = 'last';
			echo '
			<tr>
				<td class="'.$first.'" style="width:25%;">'.GAN_Database::get_link_name($id).'</td>
				<td class="value '.$first.' '.$last.'"><img src="'.GAN_PLUGIN_IMAGE_URL.'/bar.png" alt="" height="16" width="'.$width.'%" />'.$imps.'</td>
			</tr>
			';
			$first = "";
			$loop++;
		     }
		   } else {
		     echo '<tr><td class="first last" style="border-right:1px solid #e5e5e5" colspan="2">No stats yet</td></tr>';
		   }
		?>
	  </tbody>
	  </table>
	  <?php
	}
	function check_autoexpire() {
	  if (get_option('wp_gan_autoexpire') == 'yes') {
	    GAN_Database::deleteexpired('');
	  }
	}
}

/* Load widget code */
require_once(GAN_DIR . "/GAN_Widget.php");
require_once(GAN_DIR . "/GAN_ImageWidget.php");

/* Create an instanance of the plugin */
global $gan_plugin;
$gan_plugin = new GAN_Plugin;



