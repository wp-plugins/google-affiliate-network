<?php
/**
 * Plugin Name: Google Affiliate Network widget
 * Plugin URI: http://http://www.deepsoft.com/GAN
 * Description: A Widget to display Google Affiliate Network ads
 * Version: 4.3
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
	var $database_screen_id;
	var $add_db_screen_id;
	var $add_db_bulk_screen_id;
	var $ad_stats_list_table;
	var $merch_stats_list_table;

	var $admin_tabs;
	var $admin_tablist;

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
		if (is_admin()) {
		  wp_enqueue_script('jquery-ui-sortable');
		  add_action('media_buttons', 
			     array($this,'add_media_button'), 
			     20);
		}
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
	  $this->admin_tabs = array();
	  $this->admin_tablist = array();
	  $this->database_screen_id = add_menu_page( __('GAN Database','gan'), __('GAN DB','gan'), 'manage_options', 
		 'gan-database-page', array($this,'admin_database_page'), 
		 GAN_PLUGIN_IMAGE_URL.'/GAN_menu.png');
	  $this->admin_tabs['gan-database-page'] = __('GAN DB','gan');
	  $this->admin_tablist[] = 'gan-database-page';
	  add_action("load-$this->database_screen_id", array($this,'_init_db_list_table') );
	  $this->add_contentualhelp($this->database_screen_id,'gan-database-page');

	  $this->add_db_screen_id = add_submenu_page( 'gan-database-page', __('Add new GAN DB element','gan'), 
		    __('Add new','gan'), 
		    'manage_options', 'gan-database-add-element', 
		    array($this,'admin_add_element'));
	  $this->admin_tabs['gan-database-add-element'] = __('Add new','gan');
	  $this->admin_tablist[] = 'gan-database-add-element';
	  add_action("load-$this->add_db_screen_id", array($this,'_init_add_db_list_table') );
	  $this->add_contentualhelp($this->add_db_screen_id,'gan-database-add-element');

	  $this->add_db_bulk_screen_id = add_submenu_page( 'gan-database-page', __('Add new GAN DB elements in bulk','gan'), 
		    __('Add new bulk','gan'), 
		    'manage_options', 'gan-database-add-element-bulk', 
		    array($this,'admin_add_element_bulk'));
	  $this->admin_tabs['gan-database-add-element-bulk'] = __('Add new bulk','gan');
	  $this->admin_tablist[] = 'gan-database-add-element-bulk';
	  add_action("load-$this->add_db_bulk_screen_id", array($this,'_init_add_db_list_table') );
	  $this->add_contentualhelp($this->add_db_bulk_screen_id,'gan-database-add-element-bulk');

	  $screen_id = add_submenu_page( 'gan-database-page', __('Ad Impression Statistics','gan'),
	  	    __('Ad Stats','gan'),
		    'manage_options', 'gan-database-ad-impstats',
		    array($this,'admin_ad_impstats'));
	  $this->admin_tabs['gan-database-ad-impstats'] = __('Ad Stats','gan');
	  $this->admin_tablist[] = 'gan-database-ad-impstats';
	  add_action("load-$screen_id", array($this,'_init_ad_stats_list_table') );
	  $this->add_contentualhelp($screen_id,'gan-database-ad-impstats');

	  $screen_id = add_submenu_page( 'gan-database-page', __('Merchant Impression Statistics','gan'),
	  	    __('Merchant Stats','gan'),
		    'manage_options', 'gan-database-merch-impstats',
		    array($this,'admin_merch_impstats'));
	  $this->admin_tabs['gan-database-merch-impstats'] = __('Merchant Stats','gan');
	  $this->admin_tablist[] = 'gan-database-merch-impstats';
	  add_action("load-$screen_id", array($this,'_init_merch_stats_list_table') );
	  $this->add_contentualhelp($screen_id,'gan-database-merch-impstats');

	  $screen_id = add_submenu_page( 'gan-database-page', __('Configure Options','gan'),
			    __('Configure','gan'),'manage_options', 
			    'gan-database-options',
			    array($this,'admin_configure_options'));
	  $this->add_contentualhelp($screen_id,'gan-database-options');
	  $this->admin_tabs['gan-database-options'] = __('Configure','gan');
	  $this->admin_tablist[] = 'gan-database-options';

	  add_submenu_page( 'gan-database-page', __('Help Using the Google Affliate Network Plugin','gan'),
	  		    __('Help','gan'),'manage_options',
			    'gan-database-help',
			    array($this,'admin_help')); 
	}
	function admin_tabs($current) {
	  ?><ul id="gan-admin-tabs" class="tabs"><?php
	  foreach ($this->admin_tablist as $pageslug) {
	    ?><li><a href="<?php 
		echo add_query_arg(array('page' => $pageslug),
				    admin_url('admin.php')); ?>" <?php
		if ($current == $pageslug) {echo 'class="current selected"';}
		?>><?php echo $this->admin_tabs[$pageslug]; ?></a></li><?php
	  }
	  ?></ul><?php
	}	
	function _init_db_list_table() {
	  add_screen_option('per_page',array('label' => __('Rows','gan')) );
	  $this->_init_add_db_list_table();
	}
	function _init_add_db_list_table() {
	  $this->register_List_Table('GAN_DB_List_Table');
	  if (! isset($this->gan_db_list_table) ) {
	    $this->gan_db_list_table = new GAN_DB_List_Table();
	    $this->gan_db_list_table->set_row_actions(
		array( __('Edit','gan') => add_query_arg(
					array('page' => 'gan-database-add-element',
					      'mode' => 'edit'),
					admin_url('admin.php')),
		       __('View','gan') => add_query_arg(
					array('page' => 'gan-database-add-element',
					      'mode' => 'view'),
					admin_url('admin.php')),
		       __('Delete','gan') => add_query_arg(
					array('page' => 'gan-database-page',
					      'action' => 'delete'),
					admin_url('admin.php')),
		       __('Toggle Enable','gan') => add_query_arg(
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
	  if (! isset($this->merch_stats_list_table) ) {
	    $this->merch_stats_list_table = new Merch_Stats_List_Table();
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
	  ?><p><a target="_blank" href="http://www.connectcommerce.com/global/login.html" class="button"><?php _e('Login into Google Affiliate Network','gan'); ?></a></p><?php
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
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-page'); ?><br clear="all" />
	    <div id="icon-gan-db" class="icon32"><br /></div>
	    <h2><?php _e('GAN Database','gan'); ?> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-database-add-element',
			 'mode' => 'add',
			 'id' => false)); 
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
		<?php $this->gan_db_list_table->display(); ?></form></div><?php
	}

	/* Add element to ad database */
	function admin_add_element() {
	  $message = $this->gan_db_list_table->prepare_one_item();
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-add-element'); ?><br clear="all" />
	    <div id="<?php echo $this->gan_db_list_table->add_item_icon(); ?>" class="icon32"><br />
	    </div><h2><?php echo $this->gan_db_list_table->add_item_h2(); ?><?php   
				     $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); 
		  $this->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form action="" method="get">
	    <input type="hidden" name="page" value="gan-database-add-element" />
	    <?php $this->gan_db_list_table->display_one_item_form(
			add_query_arg(array('page' => 'gan-database-page',
			'mode' => false, 
			'id' => false))); ?></form></div><?php
	}

        /* Add elements in bulk (from a TSV file) to ad database */
	function admin_add_element_bulk() {
	  $message = $this->gan_db_list_table->process_bulk_upload();
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-add-element-bulk'); ?><br clear="all" />
	    <div id="icon-gan-add-db" class="icon32"><br />
	    </div><h2><?php _e('Add Elements in bulk to the GAN Database','gan'); ?><?php   
                                     $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); 
		  $this->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form method="post" action=""  enctype="multipart/form-data" >
	    <input type="hidden" name="page" value="gan-database-add-element-bulk" />
	    <?php $this->gan_db_list_table->display_bulk_upload_form(add_query_arg(array('page' => 'gan-database-page'))); ?></form></div><?php
	}
	function admin_ad_impstats() {
	  $this->ad_stats_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-ad-impstats'); ?><br clear="all" />
	    <div id="icon-gan-ad-imp" class="icon32"><br /></div><h2><?php _e('Ad Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); ?>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
	    	<input type="hidden" name="page" value="gan-database-ad-impstats" />
		<?php $this->ad_stats_list_table->display(); ?></form></div><?php
	}

	function admin_merch_impstats() {
	  $this->merch_stats_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-merch-impstats'); ?><br clear="all" />
	    <div id="icon-gan-merch-imp" class="icon32"><br /></div><h2><?php _e('Merchant Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->PluginSponsor(); ?>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-merch-impstats" />
		<?php $this->merch_stats_list_table->display(); ?></form></div><?php
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
	    ?><div id="message"class="updated fade"><p><?php _e('Options Saved','gan'); ?></p></div><?php
	  } else if ( isset($_GET['upgradedatabase']) ) {
	    GAN_Database::upgrade_database();
	    ?><div id="message"class="updated fade"><p><?php _e('Database Upgraded','gan'); ?></p></div><?php
	  }
	  /* Head of page, filter and screen options. */
	  $autoexpire = get_option('wp_gan_autoexpire');
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-options'); ?><br clear="all" />
	    <div id="icon-gan-options" class="icon32"><br /></div><h2><?php _e('Configure Options','gan'); ?><?php $this->InsertVersion(); ?></h2>
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
	  if (current_user_can( 'manage_options' )) {
	    wp_add_dashboard_widget('gan_dashboard_widget', __('GAN Database Stats','gan'), array($this,'database_stats_widget'));
	    wp_add_dashboard_widget('ganimp_dashboard_widget', __('GAN Impression Stats','gan'), array($this,'impression_stats_widget'));
	  }
	}

	/* Database statisics dashboard widget */
	function database_stats_widget() {

	  $total_ads = GAN_Database::total_ads();
	  $disabled  = GAN_Database::disabled_count();
	  $advertisers = GAN_Database::advertiser_count();
	  $sizes    = GAN_Database::size_count();

	  ?><div class="table">
		<table class="ganstats">
		<tr><td class="ganstats_total"><?php echo $total_ads; ?></td>
		    <td class="ganstats_label"><?php _e('Total ads','gan'); ?></td>
		    <td class="ganstats_total"><?php echo $disabled; ?></td>
		    <td class="ganstats_label"><?php _e('Disabled','gan'); ?></td></tr>
		<tr><td class="ganstats_total"><?php echo $advertisers; ?></td>
		    <td class="ganstats_label"><?php _e('Advertisers','gan'); ?></td>
		    <td class="ganstats_total"><?php echo $sizes; ?></td>
		    <td class="ganstats_label"><?php _e('Sizes','gan'); ?></td></tr>
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
	function add_contentualhelp($screenid,$thepage) {
	  $helppageURL = add_query_arg(array('page' => 'gan-database-help'));
	  $help = '';
	  switch ($thepage) {
	    case 'gan-database-page':
		$help .= '<h4>'.__('Main GAN Database page','gan').'</h4>';
		$help .= '<p>'.__('This is the main ad database page. It '.
				  'contains a listing of the advertisments in '.
				  'the database.  The columns in the listing '.
				  'include the name of the advertiser, the '.
				  '(unique) Link ID, the Link Name, the '.
				  'Image Width (0 means a text ad), the '.
				  'Start and End dates, and the Enabled '.
				  'flag.  The table is sorted by End Date. '.
				  'The listing can be filtered by advertiser '.
				  'and/or the Image Width. Items in the '.
				  'database can be either deleted or have '.
				  'their Enabled flag toggled in bulk, and '.
				  'can be individually edited, views, '.
				  'deleted, or enable toggled.','gan').'</p>';
		break;
	    case 'gan-database-add-element':
		$help .= '<h4>'.__('Add/Edit/View GAN Database page','gan').
			'</h4>';
		$help .= '<p>'.__('This is the Add, Edit, and View page, '.
				  'where advertisments can be individually '.
				  'added, edited, or viewed.  The fields '.
				  'in the database are as follows:','gan');
		$help .= '<dl>';
		$help .= '<dt>'.__('Advertiser:','gan').'</dt>';
		$help .= '<dd>'.__('This is the name of the advertiser.',
					'gan').'</dd>';
		$help .= '<dt>'.__('Link ID:','gan').'</dt>';
		$help .= '<dd>'.__('This is the (unique) Link ID. It is '.
				   'generally a digit string prefixed by '.
				   'the letter J.','gan').'</dd>';
		$help .= '<dt>'.__('Link Name:','gan').'</dt>';
		$help .= '<dd>'.__('This is the Link Name and is used as '.
				   'anchor text for text ads.','gan').'</dd>';
		$help .= '<dt>'.__('Merchandising Text:','gan').'</dt>';
		$help .= '<dd>'.__('This is additional text used to sell the '.
				   'link and is included as additional text '.
				   'in text ads.','gan').'</dd>';
		$help .= '<dt>'.__('Alt Text:','gan').'</dt>';
		$help .= '<dd>'.__('This is the alternitive text used for '.
				   'image ads.','gan').'</dd>';
		$help .= '<dt>'.__('Start Date:','gan').'</dt>';
		$help .= '<dd>'.__('This is the starting date for the ad. '.
				   "The ad won't be displayed before this ".
				   'date.','gan').'</dd>';
		$help .= '<dt>'.__('End Date:','gan').'</dt>';
		$help .= '<dd>'.__('This is the ending date for the ad. The '.
				   'ad will stop being shown in this '.
				   'date.','gan').'</dd>';
		$help .= '<dt>'.__('Clickserver Link:','gan').'</dt>';
		$help .= '<dd>'.__("This is the ad's target link.",'gan').
				'</dd>';
		$help .= '<dt>'.__('Image URL:','gan').'</dt>';
		$help .= '<dd>'.__('This is the URL of the banner for an '.
				   'image ad.','gan').'</dd>'; 
		$help .= '<dt>'.__('Image Height:','gan').'</dt>';
		$help .= '<dd>'.__('This is the height of the image (enter 0 '.
				   'for text ads).','gan').'</dd>';
		$help .= '<dt>'.__('Image Width:','gan').'</dt>';
		$help .= '<dd>'.__('This is the width of the image (enter 0 '.
				   'for text ads).','gan').'</dd>';
	        $help .= '<dt>'.__('Link URL:','gan').'</dt>';
		$help .= '<dd>'.__('This is the optional auxillary link URL.',
					'gan').'</dd>';
	        $help .= '<dt>'.__('Promo Type:','gan').'</dt>';
		$help .= '<dd>'.__('This is the promotion type for the ad.',
					'gan').'</dd>';
	        $help .= '<dt>'.__('Merchant ID:','gan').'</dt>';
		$help .= '<dd>'.__('The is the Merchant ID for this '.
				   'advertiser. It is generally a string of '.
				   'digits, prefixed with the letter K.',
				   'gan').'</dd>';
	        $help .= '<dt>'.__('enabled?','gan').'</dt>';
		$help .= '<dd>'.__('This is a flag indicating if the ad is '.
				   'enabled.  Ads which are not enabled are '.
				   'not shown.','gan').'</dd>';
		$help .= '</dl>';
		$help .= '<p>'.__('Most of the fields are arbitary text. The '.
				  'URL fields should be proper URL, complete '.
				  'with the http:// prefix.  Two date '.
				  'formats are supported: the database '.
				  'format of YYYY-MM-DD and the more '.
				  'conventual human format of m/d/yyyy.  An '.
				  'end date needs to be specificed, even for '.
				  'ads with no expiration date.  For this '.
				  'case, a date far in the future (like '.
				  '12/31/2037) will do.','gan').'</p>';
		break;
	    case 'gan-database-add-element-bulk':
		$help .= '<h4>'.__('Add elements in bulk to the GAN Database '.
			 'page','gan').'</h4>';
		$help .= '<p>'.__('This is the Add elements in bulk, from a '.
				  'TSV file downloaded from your GAN account '.
				  'page, using the new (Beta) style Links '.
				  'tab. You can unload this file as-is on '.
				  'this page.','gan').'</p>';
		break;
	    case 'gan-database-ad-impstats':
		$help .= '<h4>'.__('Ad Impression Statistics page','gan').
			'</h4>';
		$help .= '<p>'.__('This is the Ad Impression Statistics page, '.
				  'where the impression statistics for '.
				  'advertisements are displayed. The columns '.
				  'displayed include the advertiser name, the '.
				  'link id, the linkname, the end date, the '.
				  'image width, the number of impressions, '.
				  'and the last view date.','gan').'</p>';
		$help .= '<p>'.__('The listing can be filtered by advertiser '.
				  'and/or image width, and the impression '.
				  'counts can be either zeroed in bulk, '.
				  'individually, or all. The statistics can '.
				  'also be downloaded as a CSV file.','gan').
				  '</p>';
		break;
	    case 'gan-database-merch-impstats':
		$help .= '<h4>'.__('Merchant Impression Statistics page',
				'gan').'</h4>';
		$help .= '<p>'.__('This is the Merchant Impression Statistics '.
				  'page, where the impression statistics for '.
				  'advertisers can be displayed. The columns '.
				  'displayed include the advertiser, the '.
				  'impression count and the last view date.',
				  'gan').'</p>';
		$help .= '<p>'.__('The impression counts can be either zeroed '.
				  'in bulk, individually, or all. The '.
				  'statistics can also be downloaded as a '.
				  'CSV file.','gan').'</p>';
		break;
	    case 'gan-database-options':
		$help .= '<h4>'.__('GAN Option Configuration page','gan').
			'</h4>';
		$help .= '<p>'.__('This is the GAN Option Configuration '.
				  'page. There is only one option at '.
				  'present, a flag enabling or disabling the '.
				  'automatic deletion of expired ads. It is '.
				  'on by default and turning it off is not '.
				  'recomended.','gan').'</p>';
		$help .= '<p>'.__('Also if you upgraded from an older '.
				  'version of the GAN plugin (pre 3.0), a '.
				  'button will be on this page to upgrade '.
				  'the database to the new version.  This is '.
				  'recomended.','gan').'</p>';
		break;
	    default: return;
	  }
	  $help .= '<p><a href="'.$helppageURL.'">GAN help screen</a></p>';
	  add_contextual_help($screenid,$help);
	}
	function add_media_button() {
	  $url = GAN_PLUGIN_URL.'/GAN_InsertAdUnit.php?TB_iframe=true&amp;height=700&amp;width=640';
	  if (is_ssl()) $url = str_replace( 'http://', 'https://',  $url );
	  echo '<a href="'.$url.'" class="thickbox" title="'.
			__('Add Ad Unit','gan').'"><img src="'.
			GAN_PLUGIN_IMAGE_URL.'/media-button.png" alt="'.
			__('Add Ad Unit','gan').'" /></a>';
	  
	}
}

/* Load widget code */
require_once(GAN_DIR . "/GAN_Widget.php");
require_once(GAN_DIR . "/GAN_ImageWidget.php");

/* Create an instanance of the plugin */
global $gan_plugin;
$gan_plugin = new GAN_Plugin;



