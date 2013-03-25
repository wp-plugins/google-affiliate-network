<?php
/**
 * Plugin Name: Google Affiliate Network widget
 * Plugin URI: http://http://www.deepsoft.com/GAN
 * Description: A Widget to display Google Affiliate Network ads
 * Version: 6.1.2
 * Author: Robert Heller
 * Author URI: http://www.deepsoft.com/
 * License: GPL2
 * 
 * ------------------------------------------------------------------
 * GAN.php - Google Affiliate Network plugin / widgets
 * Created by Robert Heller on Sun Jan 27 13:46:15 2013
 * ------------------------------------------------------------------
 * Modification History: $Log: headerfile.text,v $
 * Modification History: Revision 1.1  2002/07/28 14:03:50  heller
 * Modification History: Add it copyright notice headers
 * Modification History:
 * ------------------------------------------------------------------
 * Contents:
 * ------------------------------------------------------------------
 *  
 *     Generic Project
 *     Copyright (C) 2010-2013  Robert Heller D/B/A Deepwoods Software
 * 			51 Locke Hill Road
 * 			Wendell, MA 01379-9728
 * 
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 * 
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 * 
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 * 
 *  
 */ 

/* Load constants */
require_once(dirname(__FILE__) . "/includes/GAN_Constants.php");

/* Additional file-specific constants */
define('GAN_FILE', basename(__FILE__));
define('GAN_PATH', GAN_DIR . '/' . GAN_FILE);

/* Load Database code */
require_once(GAN_INCLUDES_DIR . "/GAN_Database.php");

/* Load widget code */
require_once(GAN_INCLUDES_DIR . "/GAN_Widget.php");
require_once(GAN_INCLUDES_DIR . "/GAN_ImageWidget.php");
require_once(GAN_INCLUDES_DIR . "/GAN_Product_Widget.php");

/* Load Table code */
require_once(GAN_INCLUDES_DIR . "/Link_List_Table.php");
require_once(GAN_INCLUDES_DIR . "/Product_List_Table.php");
require_once(GAN_INCLUDES_DIR . "/Link_Stats_List_Table.php");
require_once(GAN_INCLUDES_DIR . "/Prod_Stats_List_Table.php");
require_once(GAN_INCLUDES_DIR . "/Merch_Stats_List_Table.php");

/* Main plugin class. Implements the basic admin functions of the plugin. */
class GAN_Plugin {

	var $link_list_table;
	var $prod_list_table;
	var $link_stats_list_table;
	var $prod_stats_list_table;
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
		add_action('gan_daily_event',array($this,'daily_work'));
		add_option('wp_gan_autoexpire','yes');
		add_option('wp_gan_extra_css','');
		add_filter('set-screen-option', array($this,'set_screen_options'), 10, 3);

		load_plugin_textdomain('gan',GAN_PLUGIN_URL.'/languages/',
					  basename(GAN_DIR).'/languages/');
		wp_enqueue_style('gan-css', GAN_PLUGIN_CSS_URL . '/GAN.css',
				 null,GAN_VERSION);
		if (is_admin()) {
		  wp_enqueue_script('jquery-ui-sortable');
		  wp_enqueue_style('gan-admin-css', 
				   GAN_PLUGIN_CSS_URL . '/GAN_admin.css', 
				   array('gan-css','wp-admin'),GAN_VERSION);
		  add_action('media_buttons', 
			     array($this,'add_media_button'), 
			     20);
		}
	}
	function set_screen_options($status, $option, $value) {
	  file_put_contents("php://stderr","*** GAN_Plugin::set_screen_options($status,$option, $value)\n");
	  if (class_exists('GAN_Link_List_Table') &&
	      $option == GAN_Link_List_Table::my_screen_option())
		return $value;
	  if (class_exists('GAN_Product_List_Table') &&
	      $option == GAN_Product_List_Table::my_screen_option())
		return $value;
	  if (class_exists('GAN_Link_Stats_List_Table') &&
	      $option == GAN_Link_Stats_List_Table::my_screen_option())
		return $value;
	  if (class_exists('GAN_Prod_Stats_List_Table') &&
	      $option == GAN_Prod_Stats_List_Table::my_screen_option())
		return $value;
	  if (class_exists('GAN_Merch_Stats_List_Table') &&
	      $option == GAN_Merch_Stats_List_Table::my_screen_option())
		return $value;
	}
	/* Activation hook: create database tables. */
	function install() {
		$dbvers = GAN_Database::database_version();
		if ($dbvers == 0.0) {
		  GAN_Database::make_ad_table();
		  GAN_Database::make_merchs_table();
		  GAN_Database::make_products_ad_table();
		} else if ($dbvers < 3.2) {
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
	  register_widget( 'GAN_Product_Widget' );        /* Products */
	  add_shortcode('GAN_Product', array('GAN_Product_Widget','shortcode'));
	}

	/* Add in our Admin Menu */
	function admin_menu() {
	  /* Admin tabs */
	  $this->admin_tabs = array();
	  $this->admin_tablist = array();

	  /*
	   * Main Admin page: Links (Click Ads) 
	   */
	  $screen_id = add_menu_page( __('GAN Link Ads','gan'), 
					    __('GAN Links','gan'), 
					    'manage_options',
					    'gan-link-ads-table',
					    array($this,
						  'admin_link_list_table'),
					    GAN_PLUGIN_IMAGE_URL.
						'/GAN_menu.png');
	  $this->admin_tabs['gan-link-ads-table'] = __('GAN Link Ads','gan');
	  $this->admin_tablist[] = 'gan-link-ads-table';
	  $this->add_contentualhelp($screen_id,'gan-link-ads-table');
	  $this->link_list_table = new GAN_Link_List_Table($screen_id);

	  /* Add / Edit / View single link (ad) page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
						__('Add new GAN Link','gan'),
						__('Add new link','gan'),
						'manage_options', 
						'gan-add-link-ad',
						array($this,
							'admin_add_one_link'));
	  //$this->admin_tabs['gan-add-link-ad'] = __('Add new link','gan');
	  //$this->admin_tablist[] = 'gan-add-link-ad';
	  $this->add_contentualhelp($screen_id,
	  			    'gan-add-link-ad');

	  /* Add links (ads) in bulk page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
					__('Add new GAN Links in bulk','gan'),
					__('Add new bulk links','gan'),
					'manage_options', 
					'gan-add-bulk-link-ads',
					array($this,'admin_add_bulk_links'));
	  //$this->admin_tabs['gan-add-bulk-link-ads'] = __('Add new bulk links','gan');
	  //$this->admin_tablist[] = 'gan-add-bulk-link-ads';
	  $this->add_contentualhelp($screen_id,'gan-add-bulk-link-ads');

	  /* Product Links List Table */
	  $screen_id = add_submenu_page('gan-link-ads-table',
					__('GAN Products List', 'gan'),
					__('GAN Products', 'gan'),
					'manage_options',
					'gan-products-table',
					array($this,
						'admin_product_list_table'));
	  $this->admin_tabs['gan-products-table'] = __('GAN Products', 'gan');
	  $this->admin_tablist[] = 'gan-products-table';
	  $this->add_contentualhelp($screen_id,'gan-products-table');
	  $this->prod_list_table = new GAN_Product_List_Table($screen_id);

	  /* Add / Edit / View single product page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
						__('Add new GAN Product','gan'),
						__('Add new product','gan'),
						'manage_options',
						'gan-add-product',
						array($this,
						    'admin_add_one_product'));
	  //$this->admin_tabs['gan-add-product'] = __('Add new product','gan');
	  //$this->admin_tablist[] = 'gan-add-product';
	  $this->add_contentualhelp($screen_id,
					'gan-add-product');

	  /* Add products in bulk page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
				__('Add new GAN Products in bulk','gan'), 
				__('Add new products in bulk','gan'), 
				'manage_options',
				'gan-add-product-bulk',
				 array($this,'admin_add_bulk_products'));
	  //$this->admin_tabs['gan-add-product-bulk'] = 
	  //		__('Add new products in bulk','gan');
	  //$this->admin_tablist[] = 'gan-add-product-bulk';
	  $this->add_contentualhelp($screen_id,
				    'gan-add-product-bulk');

	  /* Link Stats page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
					__('Link Impression Statistics','gan'),
					__('Link Stats','gan'),
					'manage_options', 
					'gan-link-impstats',
					array($this,'admin_link_impstats'));
	  $this->admin_tabs['gan-link-impstats'] = __('Link Stats','gan');
	  $this->admin_tablist[] = 'gan-link-impstats';
	  $this->add_contentualhelp($screen_id,'gan-link-impstats');
	  $this->link_stats_list_table = new GAN_Link_Stats_List_Table($screen_id);
						
	  /* Product Stats page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
					__('Product Impression Statistics','gan'),
					__('Product Stats','gan'),
					'manage_options', 
					'gan-product-impstats',
					array($this,'admin_product_impstats'));
	  $this->admin_tabs['gan-product-impstats'] = __('Product Stats','gan');
	  $this->admin_tablist[] = 'gan-product-impstats';
	  $this->add_contentualhelp($screen_id,'gan-product-impstats');
	  $this->prod_stats_list_table = new GAN_Prod_Stats_List_Table($screen_id);
						
	  /* Merch Stats page */
	  $screen_id = add_submenu_page( 'gan-link-ads-table',
					__('Merchant Impression Statistics','gan'),
					__('Merchant Stats','gan'),
					'manage_options', 
					'gan-merch-impstats',
					array($this,'admin_merch_impstats'));
	  $this->admin_tabs['gan-merch-impstats'] = __('Merchant Stats','gan');
	  $this->admin_tablist[] = 'gan-merch-impstats';
	  $this->add_contentualhelp($screen_id,'gan-merch-impstats');
	  $this->merch_stats_list_table = new GAN_Merch_Stats_List_Table($screen_id);
						

	  $screen_id = add_submenu_page( 'gan-link-ads-table', __('Configure Options','gan'),
			    __('Configure','gan'),'manage_options', 
			    'gan-options',
			    array($this,'admin_configure_options'));
	  $this->add_contentualhelp($screen_id,'gan-options');
	  $this->admin_tabs['gan-options'] = __('Configure','gan');
	  $this->admin_tablist[] = 'gan-options';

	  add_submenu_page( 'gan-link-ads-table', __('Help Using the Google Affliate Network Plugin','gan'),
	  		    __('Help','gan'),'manage_options',
			    'gan-help',
			    array($this,'admin_help')); 
	  $this->admin_tabs['gan-help'] = __('Help','gan');
	  $this->admin_tablist[] = 'gan-help';
	}

	function admin_tabs($current) {
	  ?><ul id="gan-admin-tabs" class="tabs">
	  <li><img alt="<?php _e('GAN Database','gan'); ?>" src="<?php echo GAN_PLUGIN_IMAGE_URL.'/GAN_menu.png'; ?>" /></li><?php
	  foreach ($this->admin_tablist as $pageslug) {
	    ?><li><a href="<?php 
		echo add_query_arg(array('page' => $pageslug),
				    admin_url('admin.php')); ?>" <?php
		if ($current == $pageslug) {echo 'class="current selected"';}
		?>><?php echo $this->admin_tabs[$pageslug]; ?></a></li><?php
	  }
	  ?></ul><?php
	}	
	function InsertPayPalDonateButton() {
	  ?><div id="gan_donate"><form action="https://www.paypal.com/cgi-bin/webscr" method="post"><?php _e('Donate to Google Affiliate Network plugin software effort.','gan'); ?><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="B34MW48SVGBYE"><input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/i/scr/pixel.gif" width="1" height="1"></form></div><br clear="all" /><?php
	  $helppageURL = add_query_arg(array('page' => 'gan-help'))
	  ?><div id="gan_supportSmall"><a href="<?php echo $helppageURL.'#SupportGAN'; ?>"><?php _e('More ways to support the GAN project.','gan'); ?></a></div><br clear="all" /><?php
	}

	function InsertH2AffiliateLoginButton() {
	  ?><p><a target="_blank" href="http://www.google.com/ads/affiliatenetwork/" class="button"><?php _e('Login into Google Affiliate Network','gan'); ?></a></p><?php
	}
	/* Front side head action: load our style sheet */
	function wp_head() {
	  $extra_css = stripslashes(get_option('wp_gan_extra_css'));
	  if ($extra_css != '') {
	    ?><style type="text/css" media="all"><?php echo $extra_css; ?></style><?php
	  }
	}
	/* Admin side head action: load our admin style sheet */
	function admin_head() {
	}

	/* Main admin page.  List the ads in the database */
	function admin_link_list_table() {
	  $this->link_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-link-ads-table'); ?><br clear="all" />
	    <div id="icon-gan-db" class="icon32"><br /></div>
	    <h2><?php _e('GAN Link Ads','gan'); ?> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-add-link-ad',
			 'mode' => 'add',
			 'id' => false),admin_url('admin.php')); 
		?>" class="button add-new-h2"><?php _e('Add New','gan'); 
		?></a> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-add-bulk-link-ads'),admin_url('admin.php')); 
		?>" class="button add-new-h2"><?php 
			_e('Add New in Bulk','gan'); ?></a><?php 
					$this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); ?>
	    <form method="post" action="">
		<input type="hidden" name="page" value="gan-link-ads-table" />
		<?php $this->link_list_table->search_box( 'search', 'search_id' ); ?>
		<?php $this->link_list_table->display(); ?></form></div><?php
	}
	/* Add element to ad database */
	function admin_add_one_link() {
	  $message = $this->link_list_table->prepare_one_item();
	  ?><div class="wrap"><?php $this->admin_tabs('gan-add-link-ad'); ?><br clear="all" />
	    <div id="<?php echo $this->link_list_table->add_item_icon(); ?>" class="icon32"><br />
	    </div><h2><?php echo $this->link_list_table->add_item_h2(); ?><?php   
				     $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); 
		  $this->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form action="<?php echo admin_url('admin.php'); ?>" method="get">
	    <input type="hidden" name="page" value="gan-add-link-ad" />
	    <?php $this->link_list_table->display_one_item_form(
			add_query_arg(array('page' => 'gan-link-ads-table',
			'mode' => false, 
			'id' => false))); ?></form></div><?php
	}

        /* Add elements in bulk (from a TSV file) to ad database */
	function admin_add_bulk_links() {
	  $message = $this->link_list_table->process_bulk_upload();
	  ?><div class="wrap"><?php $this->admin_tabs('gan-add-bulk-link-ads'); ?><br clear="all" />
	    <div id="icon-gan-add-db" class="icon32"><br />
	    </div><h2><?php _e('Add Links in bulk','gan'); ?><?php   
                                     $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); 
		  $this->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form method="post" action=""  enctype="multipart/form-data" >
	    <input type="hidden" name="page" value="gan-add-bulk-link-ads" />
	    <?php $this->link_list_table->display_bulk_upload_form(add_query_arg(array('page' => 'gan-link-ads-table'))); ?></form></div><?php
	}
	function admin_product_list_table() {
	  $this->prod_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-products-table'); ?><br clear="all" />
	    <div id="icon-gan-prod-db" class="icon32"><br /></div>
	    <h2><?php _e('GAN Product Database','gan'); ?> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-add-product',
			 'mode' => 'add',
			 'id' => false),admin_url('admin.php')); 
		?>" class="button add-new-h2"><?php _e('Add New','gan'); 
		?></a> <a href="<?php 
		echo add_query_arg(
		   array('page' => 'gan-add-product-bulk'),admin_url('admin.php')); 
		?>" class="button add-new-h2"><?php 
			_e('Add New in Bulk','gan'); ?></a><?php 
					$this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); ?>
	    <form method="post" action="">
		<input type="hidden" name="page" value="gan-products-table" />
		<?php $this->prod_list_table->search_box( 'search', 'search_id' ); ?>
		<?php $this->prod_list_table->display(); ?></form></div><?php
	}
	function admin_add_one_product() {
	  $message = $this->prod_list_table->prepare_one_item();
	  ?><div class="wrap"><?php $this->admin_tabs('gan-add-product'); ?><br clear="all" />
	    <div id="<?php echo $this->prod_list_table->add_item_icon(); ?>" class="icon32"><br />
	    </div><h2><?php echo $this->prod_list_table->add_item_h2(); ?><?php   
				     $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); 
		  $this->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form action="<?php echo admin_url('admin.php'); ?>" method="get">
	    <input type="hidden" name="page" value="gan-add-product" />
	    <?php $this->prod_list_table->display_one_item_form(
			add_query_arg(array('page' => 'gan-products-table',
			'mode' => false, 
			'id' => false))); ?></form></div><?php
	}

	function admin_add_bulk_products() {
	  $message = $this->prod_list_table->process_bulk_upload();
	  ?><div class="wrap"><?php $this->admin_tabs('gan-add-product-bulk'); ?><br clear="all" />
	    <div id="icon-gan-add-prod-db" class="icon32"><br />
	    </div><h2><?php _e('Add Products in bulk to the GAN Database','gan'); ?><?php   
                                     $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); 
		  $this->InsertH2AffiliateLoginButton(); ?>
	    <?php if ($message != '') {
		?><div id="message" class="update fade"><?php echo $message; ?></div><?php
		} ?>
	    <form method="post" action=""  enctype="multipart/form-data" >
	    <input type="hidden" name="page" value="gan-add-product-bulk" />
	    <?php $this->prod_list_table->display_bulk_upload_form(add_query_arg(array('page' => 'gan-products-table'))); ?></form></div><?php
	}


	function admin_link_impstats() {
	  $this->link_stats_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-link-impstats'); ?><br clear="all" />
	    <div id="icon-gan-ad-imp" class="icon32"><br /></div><h2><?php _e('Link Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); ?>
	    <form method="post" action="">
	    	<input type="hidden" name="page" value="gan-link-impstats" />
		<?php if (GAN_Database::database_version() >= 3.0) $this->link_stats_list_table->search_box( 'search', 'search_id' ); ?>
		<?php $this->link_stats_list_table->display(); ?></form></div><?php
	}

	function admin_product_impstats() {
	  $this->prod_stats_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-product-impstats'); ?><br clear="all" />
	    <div id="icon-gan-prod-imp" class="icon32"><br /></div><h2><?php _e('Product Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); ?>
	    <form method="post" action="">
	    	<input type="hidden" name="page" value="gan-product-impstats" />
		<?php $this->prod_stats_list_table->search_box( 'search', 'search_id' ); ?>
		<?php $this->prod_stats_list_table->display(); ?></form></div><?php
	}

	function admin_merch_impstats() {
	  $this->merch_stats_list_table->prepare_items();
	  /* Head of page, filter and screen options. */
	  ?><div class="wrap"><?php $this->admin_tabs('gan-merch-impstats'); ?><br clear="all" />
	    <div id="icon-gan-merch-imp" class="icon32"><br /></div><h2><?php _e('Merchant Impression Statistics','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); ?>
	    <form method="post" action="">
		<input type="hidden" name="page" value="gan-merch-impstats" />
		<?php $this->merch_stats_list_table->display(); ?></form></div><?php
	}

	function admin_configure_options() {
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.', 'gan') );
	  }
	  if ( isset($_REQUEST['saveoptions']) ) {
	    $autoexpire = $_REQUEST['gan_autoexpire'];
	    update_option('wp_gan_autoexpire',$autoexpire);
	    //$disablesponsor = $_REQUEST['gan_disablesponsor'];
	    //update_option('wp_gan_disablesponsor',$disablesponsor);
	    $extra_css = $_REQUEST['gan_extra_css'];
	    update_option('wp_gan_extra_css',$extra_css);
	    ?><div id="message"class="updated fade"><p><?php _e('Options Saved','gan'); ?></p></div><?php
	  } else if ( isset($_REQUEST['upgradedatabase']) ) {
	    GAN_Database::upgrade_database();
	    ?><div id="message"class="updated fade"><p><?php _e('Database Upgraded','gan'); ?></p></div><?php
	  } else if ( isset($_REQUEST['displaydatabasetab']) ) {
	    GAN_Database::display_database();
	    ?><div id="message"class="updated fade"><p><?php _e('Database Displayed','gan'); ?></p></div><?php
	  }
	  /* Head of page, filter and screen options. */
	  $autoexpire = get_option('wp_gan_autoexpire');
	  $extra_css = get_option('wp_gan_extra_css');
	  //$disablesponsor = get_option('wp_gan_disablesponsor');
	  ?><div class="wrap"><?php $this->admin_tabs('gan-database-options'); ?><br clear="all" />
	    <div id="icon-gan-options" class="icon32"><br /></div><h2><?php _e('Configure Options','gan'); ?><?php $this->InsertVersion(); ?></h2>
	    <?php $this->InsertPayPalDonateButton(); ?>
	    <form method="post" action="">
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
		  <tr valign="top">
		    <th scope="row"><label for="gan_extra_css" style="width:20%;"><?php _e('Extra style (CSS) settings for ad and product units','gan'); ?></label></th>
		    <td><textarea rows="10" cols="40" id="gan_extra_css" name="gan_extra_css"><?php echo stripslashes($extra_css); ?></textarea></td></tr>
		</table>
		<p>
			<input type="submit" name="saveoptions" class="button-primary" value="<?php _e('Save Options','gan'); ?>" />
		</p><?php 
		?><span id="gan_dash_version"><?php printf(__('Database Version: %3.1f','gan'),GAN_Database::database_version()) ?></span><br /><?php
		if (GAN_Database::database_version() < 3.2) {
		  ?><p><?php _e('Your database needs to be upgraded.','gan'); ?>&nbsp;<input type="submit" name="upgradedatabase" class="button-primary" value="<?php _e('Upgrade Database','gan'); ?>"></p><?php
		} ?><p><input type="submit" name="displaydatabasetab" class="button-primary" value="<?php _e('Display Database','gan'); ?>"></p></form></div><?php
	}
	function admin_help() {
	  require_once(GAN_INCLUDES_DIR.'/GAN_Help.php');
	}
	function InsertVersion() {
	  ?><span id="gan_version"><?php printf(__('Version: %s','gan'),GAN_VERSION) ?></span><?php
	}
	function InsertDashVersion() {
	  ?><span id="gan_dash_version"><?php printf(__('Version: %s','gan'),GAN_VERSION) ?></span><?php
	}
	/* Set up dashboard widgets */
	function wp_dashboard_setup() {
	  if (current_user_can( 'manage_options' )) {
	    wp_add_dashboard_widget('gan_dashboard_widget', __('GAN Database Stats','gan'), array($this,'database_stats_widget'));
	    if (GAN_Database::database_version() >= 3.1) wp_add_dashboard_widget('gan_proddashboard_widget', __('GAN Product Database Stats','gan'), array($this,'product_database_stats_widget'));
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
		<a href="<?php 
			echo add_query_arg(array('page' => 'gan-link-ads-table'),
					   admin_url('admin.php')); 
			?>" style="float:right;" class="button"><?php _e('Manage the GAN ad DB','gan'); 
		?></a><br clear="all" />
		<?php $this->InsertDashVersion(); ?><br clear="all" />
		</div><?php 
	}
	/* Product Database statisics dashboard widget */
	function product_database_stats_widget() {

	  $total_products = GAN_Database::total_products();
	  $disabled  = GAN_Database::disabled_product_count();

	  ?><div class="table">
		<table class="ganstats">
		<tr><td class="ganstats_total"><?php echo $total_products; ?></td>
		    <td class="ganstats_label"><?php _e('Total products','gan'); ?></td>
		    <td class="ganstats_total"><?php echo $disabled; ?></td>
		    <td class="ganstats_label"><?php _e('Disabled','gan'); ?></td></tr>
		</table>
		<a href="<?php 
			echo add_query_arg(array('page' => 'gan-products-table'),
					   admin_url('admin.php')); 
			?>" style="float:right;" class="button"><?php _e('Manage the GAN product DB','gan'); 
		?></a><br clear="all" />
		<?php $this->InsertDashVersion(); ?><br clear="all" />
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
	  <table class="ganstats_table" width="100%">
	    <th>
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
	  <table class="gantops_table" width="100%">
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
	  <a href="<?php 
			echo add_query_arg(array('page' => 'gan-merch-impstats'),
					   admin_url('admin.php')); 
			?>" style="float:right;" class="button"><?php _e('Detailed Merchant Stats','gan'); 
		?></a><br clear="all" /><?php
	  $top_ads = GAN_Database::top_ads();
	  $ad_statistics = GAN_Database::ad_statistics();
	  $max_ad_impressions = GAN_Database::max_ad_impressions();
	  ?>
	  <h4 style="width: 100%;text-align: center;">Ads</h4><br />
	  <table class="ganstats_table" width="100%">
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
	  <table class="gantops_table" width="100%">
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
	  <a href="<?php 
			echo add_query_arg(array('page' => 'gan-link-impstats'),
					   admin_url('admin.php')); 
			?>" style="float:right;" class="button"><?php _e('Detailed Ad Stats','gan'); 
		?></a><br clear="all" />
	  <?php
	  $top_products = GAN_Database::top_products();
	  $product_statistics = GAN_Database::product_statistics();
	  $max_product_impressions = GAN_Database::max_product_impressions();
	  ?>
	  <h4 style="width: 100%;text-align: center;">Products</h4><br />
	  <table class="ganstats_table" width="100%">
	    <thead>
		<tr>
		   <th scope="col"><?php _e('Maximum','gan'); ?></th><th scope="col"><?php _e('Minimum','gan'); ?></th>
		   <th scope="col"><?php _e('Average','gan'); ?></th><th scope="col"><?php _e('Std. Deviation','gan'); ?></th>
		   <th scope="col"><?php _e('Variance','gan'); ?></th></tr>
	    <tbody>
		<tr>
		   <td width="20%" style="text-align: center;"><?php echo $product_statistics['maximum']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $product_statistics['minimum']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $product_statistics['average']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $product_statistics['std_deviation']; ?></td>
		   <td width="20%" style="text-align: center;"><?php echo $product_statistics['variance']; ?></td></tr>
	    </tbody>
	  </table>
	  <table class="gantops_table" width="100%">
	  <tbody>
		<tr>
		   <th scope="col"><span class="auraltext"><?php _e('Product','gan'); ?></span> </th>
		   <th scope="col"><span class="auraltext"><?php _e('Number of Impressions','gan'); ?></span> </th>
		</tr>
		<?php
		   $loop = 1;
		   $first = 'first';
		   $num_products = count($top_products);
		   $last = "";
		   echo "\n<!-- GAN_Plugin::impression_stats_widget: num_products = ".$num_products." -->\n";
		   if ($num_products > 0 && $max_product_impressions > 0) {
		     foreach ((array)$top_products as $product) {
			$imps = $product['Impressions'];
			$id = $product['id'];
			$width = ($imps / $max_product_impressions * 90);
			if ($loop == $num_products) $last = 'last';
			echo '
			<tr>
				<td class="'.$first.'" style="width:25%;">'.GAN_Database::get_product_name($id).'</td>
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
	  <a href="<?php 
			echo add_query_arg(array('page' => 'gan-product-impstats'),
					   admin_url('admin.php')); 
			?>" style="float:right;" class="button"><?php _e('Detailed Product Stats','gan'); 
		?></a><br clear="all" />
	  <?php
	}
	function daily_work() {
	  if (get_option('wp_gan_autoexpire') == 'yes') {
	    GAN_Database::deleteexpired('');
	  }
	}
	function add_contentualhelp($screenid,$thepage) {
	  $helppageURL = add_query_arg(array('page' => 'gan-help'));
	  $help = '';
	  switch ($thepage) {
	    case 'gan-link-ads-table':
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
	    case 'gan-add-link-ad':
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
	    case 'gan-add-bulk-link-ads':
		$help .= '<h4>'.__('Add elements in bulk to the GAN Database '.
			 'page','gan').'</h4>';
		$help .= '<p>'.__('This is the Add elements in bulk, from a '.
				  'TSV file downloaded from your GAN account '.
				  'page, using the new (Beta) style Links '.
				  'tab. You can unload this file as-is on '.
				  'this page.','gan').'</p>';
		break;
	    case 'gan-products-table':
		break;
	    case 'gan-add-product':
		break;
	    case 'gan-add-product-bulk':
		break;

	    case 'gan-link-impstats':
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
	    case 'gan-product-impstats':
		break;
	    case 'gan-merch-impstats':
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
	    case 'gan-options':
		$help .= '<h4>'.__('GAN Option Configuration page','gan').
			'</h4>';
		$help .= '<p>'.__('This is the GAN Option Configuration '.
				  'page. There are two options at '.
				  'present, a flag enabling or disabling the '.
				  'automatic deletion of expired ads.'.
				  'gan').'</p>';
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
	  $url = GAN_PLUGIN_URL.'/GAN_InsertAdUnit.php?tab=links&amp;TB_iframe=true&amp;height=750&amp;width=640';
	  if (is_ssl()) $url = str_replace( 'http://', 'https://',  $url );
	  echo '<a href="'.$url.'" class="thickbox" title="'.
			__('Add Ad Unit','gan').'"><img src="'.
			GAN_PLUGIN_IMAGE_URL.'/media-button.png" alt="'.
			__('Add Ad Unit','gan').'" /></a>';
	  
	}

	
}

/* Create an instanance of the plugin */
global $gan_plugin;
$gan_plugin = new GAN_Plugin;

