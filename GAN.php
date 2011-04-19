<?php
/**
 * Plugin Name: Google Affiliate Network widget
 * Plugin URI: http://http://www.deepsoft.com/GAN
 * Description: A Widget to display Google Affiliate Network ads
 * Version: 3.1
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
		load_plugin_textdomain('gan',GAN_PLUGIN_URL.'/languages/',
					  basename(GAN_DIR).'/languages/');
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
	  add_menu_page( __('GAN Database','gan'), __('GAN DB','gan'), 'manage_options', 
		 'gan-database-page', array($this,'admin_database_page'), 
		 GAN_PLUGIN_IMAGE_URL.'/GAN_menu.png');

	  add_submenu_page( 'gan-database-page', __('Add new GAN DB element','gan'), 
		    __('Add new','gan'), 
		    'manage_options', 'gan-database-add-element', 
		    array($this,'admin_add_element'));
	  add_submenu_page( 'gan-database-page', __('Add new GAN DB elements in bulk','gan'), 
		    __('Add new bulk','gan'), 
		    'manage_options', 'gan-database-add-element-bulk', 
		    array($this,'admin_add_element_bulk'));
	  add_submenu_page( 'gan-database-page', __('Ad Impression Statistics','gan'),
	  	    __('Ad Stats','gan'),
		    'manage_options', 'gan-database-ad-impstats',
		    array($this,'admin_ad_impstats'));
	  add_submenu_page( 'gan-database-page', __('Merchant Impression Statistics','gan'),
	  	    __('Merchant Stats','gan'),
		    'manage_options', 'gan-database-merch-impstats',
		    array($this,'admin_merch_impstats'));
	  add_submenu_page( 'gan-database-page', __('Configure Options','gan'),
			    __('Configure','gan'),'manage_options', 
			    'gan-database-options',
			    array($this,'admin_configure_options'));
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
	  if( isset($_GET['Update']) && isset($_GET['id']) ) { 
	    if ( !$this->admin_database_editrow ($_GET['id']) ) return;
	  }
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
	    if( $action == 'delete' ) {
	      GAN_Database::delete_ad_by_id($id);
	    } else if ( $action == 'enabledtoggle' ) {
	      GAN_Database::toggle_enabled($id);
	    } else if ( $action == 'editrow' ) {
	      $this->admin_database_editrow ( $id );
	      return;
	    }
	  /* Global actions: */
	  } else if ( isset($_GET['enableall']) ) {
	    GAN_Database::enableall($where);
	  } else if ( isset($_GET['deleteexpired']) ) {
	    GAN_Database::deleteexpired($wand);
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
	  ?><div class="wrap"><div id="icon-gan-db" class="icon32"><br /></div><h2><?php _e('GAN Database','gan'); ?> <a href="<?php echo admin_url('admin.php') . "?page=gan-database-add-element"; ?>" class="button add-new-h2"><?php _e('Add New','gan'); ?></a></h2>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-page" />
		<?php $this->merchdropdown($merchid) ?>&nbsp;<?php $this->imwidthdropdown($imwidth); ?>
		<input type="submit" name="filter" class="button" value="<?php _e('Filter','gan'); ?>" />
		<label for="gan-rows-per-page"><?php _e('Rows per page','gan'); ?></label><input type="text" class="screen-per-page" name="GAN_rows_per_page" id="rows-per-page" maxlength="3" value="<?php echo $per_page; ?>" />
	        <input type="submit" name="screenopts" class="button" value="<?php _e('Apply','gan'); ?>" /></form><?php
	  /* Get database rows */
	  $GANData = GAN_Database::get_GAN_data($where);
	  if ( ! empty($GANData) ) { 
	     /* Non empty results.  Get row count. */
	     $GANDataRowCount = GAN_Database::get_GAN_row_count($where);
	     /* Figure page count. */
	     $num_pages = ceil($GANDataRowCount / $per_page);
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
	<div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s','gan' ) . '</span>%s',
	        number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
	        number_format_i18n( min( $pagenum * $per_page, $GANDataRowCount ) ),
	        number_format_i18n( $GANDataRowCount ),
	        $page_links
	); echo $page_links_text; ?></div>
	<form method="get" action="<?php echo admin_url('admin.php'); ?>">
	<input type="hidden" name="page" value="gan-database-page" />
	<?php $this->hidden_filter_fields(); ?>
	<div class="alignleft actions">
	<input type="submit" name="enableall" class="button" value="<?php _e('Enable All','gan'); ?>" />
	<input type="submit" name="deleteexpired" class="button" value="<?php _e('Delete Expired','gan'); ?>" /></div>
	<br class="clear" /></div>
	     <table class="widefat page fixed" cellspacing="2">
		<thead>
		<tr><th align="left" width="10%" scope="col" class="manage-column"><?php _e('Advertiser','gan'); ?></th>
		    <th align="left" width="10%" scope="col" class="manage-column"><?php _e('Link ID','gan'); ?></th>
		    <th align="left" width="40%" scope="col" class="manage-column"><?php _e('Link Name','gan'); ?></th>
		    <th align="left" width="10%"  scope="col" class="manage-column"><?php _e('Image Width','gan'); ?></th>
		    <th align="left" width="10%" scope="col" class="manage-column"><?php _e('Start Date','gan'); ?></th>
		    <th align="left" width="10%" scope="col" class="manage-column"><?php _e('End Date','gan'); ?></th>
		    <th align="left" width="10%"  scope="col" class="manage-column"><?php _e('Enabled?','gan'); ?></th></tr>
		</thead>
		<tfoot>
		<tr><th align="left" width="10%" scope="col" class="manage-column"><?php _e('Advertiser','gan'); ?></th>
		    <th align="left" width="10%" scope="col" class="manage-column"><?php _e('Link ID','gan'); ?></th>
		    <th align="left" width="40%" scope="col" class="manage-column"><?php _e('Link Name','gan'); ?></th>
		    <th align="left" width="10%"  scope="col" class="manage-column"><?php _e('Image Width','gan'); ?></th>
		    <th align="left" width="10%" scope="col" class="manage-column"><?php _e('Start Date','gan'); ?></th>
		    <th align="left" width="10%" scope="col" class="manage-column"><?php _e('End Date','gan'); ?></th>
		    <th align="left" width="10%"  scope="col" class="manage-column"><?php _e('En?','gan'); ?></th></tr>
		</tfoot>
	        <tbody>
		<?php /* Display each row. */
		      $index = 0; $alt = 'alternate';
		      foreach ((array)$GANData as $GANRow) {
		        $index++;	/* count record. */
		        if ($index <= $skiprecs) {continue;}	/* Previous pages. */
		        if ($index >  ($skiprecs+$per_page)) {break;} /* Next pages. */
		        $id = $GANRow['id'];	/* Id for row actions. */
			//echo "<!-- GANRow: ";print_r($GANRow);echo " -->\n";
		        ?><tr class="<?php echo $alt; ?> iedit"><td valign="top" width="10%" align="left"><?php 
			  echo GAN_Database::get_merch_name($GANRow['MerchantID']); /* Advertiser name */
		        ?></td><td valign="top" width="10%" align="left" ><?php 
			  echo $GANRow['LinkID'];     /* Link ID */
		        ?></td><td valign="top" width="40%" align="left" ><?php 
			  echo $GANRow['LinkName'];   /* Link name */
		        ?><br /><a href="<?php 
			  $this->make_page_query('gan-database-page',$id, 'editrow' );	/* Edit link */
			?>">Edit</a> <a href="<?php 
			  $this->make_page_query('gan-database-page',$id, 'delete' );  /* Delete link */
			?>"><?php _e('Delete','gan'); ?></a> <a href="<?php
			  $this->make_page_query('gan-database-page',$id, 'enabledtoggle' ); /* Enable toggle link */
			?>"><?php _e('Toggle&nbsp;Enabled','gan'); ?></a></td><td valign="top" width="10%" align="left" ><?php 
			  echo $GANRow['ImageWidth'];	/* Image width (0 = text) */
		        ?></td><td valign="top" width="10%" align="left" ><?php 
			  echo $GANRow['StartDate'];	/* Start date */
		        ?></td><td valign="top" width="10%" align="left" ><?php 
			  echo $GANRow['EndDate'];	/* End date */
		        ?></td><td valign="top" width="10%" align="left" ><?php 
			  /* Enabled */
			  if ($GANRow['Enabled'] == 1) {echo 'yes';} 
			  else {echo 'no';} ?></td></tr>
			<?php
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
	        number_format_i18n( min( $pagenum * $per_page, $GANDataRowCount ) ),
	        number_format_i18n( $GANDataRowCount ),
	        $page_links
	); echo $page_links_text; ?></div>
	<div class="alignleft actions">
	<input type="submit" name="enableall" class="button" value="Enable All" />
	<input type="submit" name="deleteexpired" class="button" value="Delete Expired" /></div>
	<br class="clear" /></div></form>
	<?php
	  } else {
	  ?><h4><?php _e('No matching entries found.','gan'); ?></h4><?php
	  }
	  ?><form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-page" />
		<?php $this->merchdropdown($merchid) ?>&nbsp;<?php $this->imwidthdropdown($imwidth); ?>
		<input type="submit" name="filter" class="button" value="<?php _e('Filter','gan'); ?>" />
		<label for="gan-rows-per-page"><?php _e('Rows per page','gan'); ?></label><input type="text" class="screen-per-page" name="GAN_rows_per_page" id="rows-per-page" maxlength="3" value="<?php echo $per_page; ?>" />
		<input type="submit" name="screenopts" class="button" value="<?php _e('Apply','gan'); ?>" /></form></div><?php 
	}

	/* Add element to ad database */
	function admin_add_element() {
	  if ( isset($_GET['Cancel']) ) {
	    $this->admin_database_page();
	    return;
	  }
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	  ?><div class="wrap"><div id="icon-gan-add-db" class="icon32"><br /></div><h2><?php _e('Add Element to GAN Database','gan'); ?></h2><?php
	  $defaults = array( 'Advertiser' => '', 'LinkID' => '', 'LinkName' => '' ,
			     'MerchandisingText' => '', 'AltText' => '', 
			     'StartDate' => '', 'EndDate' => '', 
			     'ClickserverLink' => '', 'ImageURL' => '', 
			     'ImageHeight' => 0, 'ImageWidth' => 0, 'LinkURL' => '',
			     'PromoType' => '', 'MerchantID' => '' , 
			     'enabled' => 0 );
	  if (isset($_GET['Advertiser']) ) {  $defaults['Advertiser'] = $_GET['Advertiser']; }
	  if (isset($_GET['LinkID']) ) {  $defaults['LinkID'] =     $_GET['LinkID']; }
	  if (isset($_GET['LinkName']) ) {  $defaults['LinkName'] =   $_GET['LinkName']; }
	  if (isset($_GET['MerchandisingText']) ) {  $defaults['MerchandisingText'] = $_GET['MerchandisingText']; }
	  if (isset($_GET['AltText']) ) {  $defaults['AltText'] =    $_GET['AltText']; }
	  if (isset($_GET['StartDate']) ) {  $defaults['StartDate'] =  $_GET['StartDate']; }
	  if (isset($_GET['EndDate']) ) {  $defaults['EndDate'] =    $_GET['EndDate']; }
	  if (isset($_GET['ClickserverLink']) ) {  $defaults['ClickserverLink'] = $_GET['ClickserverLink']; }
	  if (isset($_GET['ImageURL']) ) {  $defaults['ImageURL'] =   $_GET['ImageURL']; }
	  if (isset($_GET['ImageHeight']) && !empty($_GET['ImageHeight'])) {  $defaults['ImageHeight'] = $_GET['ImageHeight']; }
	  if (isset($_GET['ImageWidth']) && !empty($_GET['ImageWidth'])) {  $defaults['ImageWidth'] = $_GET['ImageWidth']; }
	  if (isset($_GET['LinkURL']) ) {  $defaults['LinkURL'] =    $_GET['LinkURL']; }
	  if (isset($_GET['PromoType']) ) {  $defaults['PromoType'] =  $_GET['PromoType']; }
	  if (isset($_GET['MerchantID']) ) {  $defaults['MerchantID'] = $_GET['MerchantID']; }
	  if (isset($_GET['enabled']) ) {  $defaults['enabled'] =    $_GET['enabled']; }
	  if( isset($_GET['Add']) &&
	      $this->add_element_checkvalid() ) {
	    GAN_Database::insert_GAN($_GET['Advertiser'],
				     $_GET['LinkID'],
				     $_GET['LinkName'],
				     $_GET['MerchandisingText'],
				     $_GET['AltText'],
				     $_GET['StartDate'],
				     $_GET['EndDate'],
				     $_GET['ClickserverLink'],
				     $_GET['ImageURL'],
				     $_GET['ImageHeight'],
				     $_GET['ImageWidth'],
				     $_GET['LinkURL'],
				     $_GET['PromoType'],
				     $_GET['MerchantID'],
				     $_GET['enabled'] );
	  }
	  ?><form name="add-GAN-element" method="GET" action="<?php echo admin_url('admin.php'); ?>">
	    <input type="hidden" name="page" value="gan-database-add-element">
	    <table class="form-table">
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Advertiser" style="width:20%;"><?php _e('Advertiser:','gan'); ?></label></th>
	      <td><input id="GAN-Advertiser" 
			value="<?php echo $defaults['Advertiser']; ?>" 
			name="Advertiser" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-LinkID" style="width:20%;"><?php _e('Link ID:','gan'); ?></label></th>
		<td><input id="GAN-LinkID" 
			value="<?php echo $defaults['LinkID']; ?>" name="LinkID" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-LinkName" style="width:20%;"><?php _e('Link Name:','gan'); ?></label></th>
		<td><input id="GAN-LinkName" 
			value="<?php echo $defaults['LinkName']; ?>" name="LinkName" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-MerchandisingText" 
			style="width:20%;top-margin:0;"><?php _e('Merchandising Text:','gan'); ?></label></th>
		<td><textarea id="GAN-MerchandisingText" name="MerchandisingText" 
			cols="50" rows="5" 
			style="width:75%;"><?php echo $defaults['MerchandisingText']; ?></textarea></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-AltText" style="width:20%;"><?php _e('Alt Text:','gan'); ?></label></th>
		<td><input id="GAN-AltText" 
			value="<?php echo $defaults['AltText']; ?>" name="AltText" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-StartDate" style="width:20%;"><?php _e('Start Date:','gan'); ?></label></th>
		<td><input id="GAN-StartDate" 
			value="<?php echo $defaults['StartDate']; ?>" name="StartDate" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-EndDate" style="width:20%;"><?php _e('End Date:','gan'); ?></label></th>
		<td><input id="GAN-EndDate" 
			value="<?php echo $defaults['EndDate']; ?>" name="EndDate" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-ClickserverLink" 
			style="width:20%;"><?php _e('Clickserver Link:','gan'); ?></label></th>
		<td><input id="GAN-ClickserverLink" 
			value="<?php echo $defaults['ClickserverLink']; ?>"
			name="ClickserverLink" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-ImageURL" style="width:20%;"><?php _e('ImageURL:','gan'); ?></label></th>
		<td><input id="GAN-ImageURL" 
			value="<?php echo $defaults['ImageURL']; ?>" name="ImageURL" 
			style="width:75%;" />
	    </tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-ImageHeight" style="width:20%;"><?php _e('ImageHeight:','gan'); ?></label></th>
		<td><input id="GAN-ImageHeight" 
			value="<?php echo $defaults['ImageHeight']; ?>" 
			name="ImageHeight" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-ImageWidth" style="width:20%;"><?php _e('ImageWidth:','gan'); ?></label></th>
		<td><input id="GAN-ImageWidth" 
			value="<?php echo $defaults['ImageWidth']; ?>" 
			name="ImageWidth" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-LinkURL" style="width:20%;"><?php _e('LinkURL:','gan'); ?></label></th>
		<td><input id="GAN-LinkURL" 
			value="<?php echo $defaults['LinkURL']; ?>" name="LinkURL" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-PromoType" style="width:20%;"><?php _e('PromoType:','gan'); ?></label></th>
		<td><input id="GAN-PromoType" 
			value="<?php echo $defaults['PromoType']; ?>" name="PromoType" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-MerchantID" style="width:20%;"><?php _e('MerchantID:','gan'); ?></label></th>
		<td><input id="GAN-MerchantID" 
			value="<?php echo $defaults['MerchantID']; ?>" 
			name="MerchantID" 
			style="width:75%;" /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-enabled"><?php _e('enabled?','gan'); ?></label></th>
		<td><input class="checkbox" type="checkbox"
			<?php checked( $defaults['enabled'], true ); ?>
			id="GAN-enabled" name="enabled" value="1"
			/></td></tr>
	  </table>
	  <p>
		<input type="submit" name="Add" class="button-primary" value="<?php _e('Add Element','gan'); ?>">
		<a href="<?php $this->cancel_page_query(); ?>"><?php _e('Cancel','gan'); ?></a>
	  </p>
	  <?php $this->hidden_filter_fields(); ?>
	  </form></div><?php
	}

        /* Add elements in bulk (from a TSV file) to ad database */
	function admin_add_element_bulk() {
	  if ( isset($_GET['Cancel']) ) {
	    $this->admin_database_page();
	    return;
	  }
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	  if ( isset($_FILES['gan-tsv-file']) ) {
	    $fp = fopen($_FILES['gan-tsv-file']['tmp_name'], 'r');
	    $count = 0;
	    ?><div id="message"class="updated fade"><p><?php
	    while ($line = fgets($fp)) {
	      $line = trim($line,"\n");
	      if (preg_match('/^"Id"[[:space:]]*"Name"/',$line)) {break;}
	    }
            while ($line = fgets($fp)) {
	      $line = trim($line,"\n");
	      $rawelts = explode("\t",$line);
	      if (count($rawelts) < 16) {continue;}
	      $Advertizer = $this->tsv_unquote($rawelts[3]);
	      $LinkID     = $this->tsv_unquote($rawelts[0]);
	      $LinkName   = $this->tsv_unquote($rawelts[1]);
	      $MerchandisingText = $this->tsv_unquote($rawelts[15]);
	      $AltText    = '';
	      $StartDate = $this->fixdate(trim($this->tsv_unquote($rawelts[8])));
	      $EndDate = $this->fixdate(trim($this->tsv_unquote($rawelts[9])));
	      if ($EndDate == 'none' || $EndDate == '') {$EndDate = "2099-12-31";}
	      $ClickserverURL = $this->tsv_unquote($rawelts[4]);
	      $ImageURL = $this->tsv_unquote($rawelts[5]);
	      $width = 0;
	      $height = 0;
	      if (preg_match('/^"([[:digit:]]*)x([[:digit:]]*)"$/',$rawelts[6],$matches) ) {
		$width = $matches[1];
		$height = $matches[2];
	      }
	      $LinkURL = '';
	      $PromoType = $this->tsv_unquote($rawelts[12]);
	      $MerchantID = $this->tsv_unquote($rawelts[2]);
	      if (preg_match("/^[[:digit:]]/",$LinkID)) {$LinkID = 'J'.$LinkID;}
	      if (preg_match("/^[[:digit:]]/",$MerchantID)) {$MerchantID = 'K'.$MerchantID;}
	      GAN_Database::insert_GAN($Advertizer,$LinkID,$LinkName,
					$MerchandisingText,$AltText,$StartDate,
					$EndDate,$ClickserverURL,$ImageURL,
					$height,$width,$LinkURL,$PromoType,
					$MerchantID,1);
	      $count++;
	      printf(__('Inserted %s (%s) into ad database.','gan'),$LinkID,$LinkName); ?><br /><?php
	    }
	    fclose($fp);
	    printf(__('Inserted %d ads into ad database.','gan'),$count); ?></p></div><?php
	  }
	  ?><div class="wrap"><div id="icon-gan-add-db" class="icon32"><br /></div><h2><?php _e('Add Elements in bulk to GAN Database','gan'); ?></h2>
          <form method="post" action="<?php echo admin_url('admin.php').'?page=gan-database-add-element-bulk'; ?>" enctype="multipart/form-data" >
	    <p><label for="gan-tsv-file"><?php _e('Select TSV File:','gan'); ?></label><input type='file' name="gan-tsv-file" size="40" /></p>
	    <p><input type="submit" name="bulkadd" class="button-primary" value="<?php _e('Upload file','gan'); ?>" /></p>
	  </form></div><?php
	}


	/* Check for a validly filled in add or edit ad form */
	function add_element_checkvalid() {
	  $result = true;
	  if ( empty($_GET['Advertiser']) ) {
	    ?><p><?php _e('Advertiser missing.','gan'); ?></p><?php
	    $result = false;
	  }
	  if ( empty($_GET['LinkID']) ) {
	    ?><p><?php _e('Link ID missing.','gan'); ?></p><?php
	    $result = false;
	  }
	  if ( empty($_GET['LinkName']) ) {
	    ?><p><?php _e('Link Name missing.','gan'); ?></p><?php
	    $result = false;
	  }
	  if ( empty($_GET['StartDate']) ) {
	    ?><p><?php _e('Start Date missing.','gan'); ?></p><?php
	    $result = false;
	  } else if ( !$this->checkdate('Start Date', $_GET['StartDate']) ) {
	    $result = false;
	  }
	  if ( empty($_GET['EndDate']) ) {
	    ?><p><?php _e('End Date missing.','gan'); ?></p><?php
	    $result = false;
	  } else if ( !$this->checkdate('End Date', $_GET['EndDate']) ) {
	    $result = false;
	  }
	  if ( empty($_GET['ClickserverLink']) ) {
	    ?><p><?php _e('Clickserver Link missing.','gan'); ?></p><?php
	    $result = false;
	  }
	  if ( empty($_GET['MerchantID']) ) {
	    ?><p><?php _e('Merchant ID  missing.','gan'); ?></p><?php
	    $result = false;
	  }
	  if ( empty($_GET['ImageHeight']) ) {
	    $_GET['ImageHeight'] = 0;
	  } else if ( !ereg("^[0-9]+$", $_GET['ImageHeight'], $trashed) ) {
	    ?><p><?php _e('Image Height should be a number.','gan'); ?></p><?php
	    $result = false;
	  }
	  if ( empty($_GET['ImageWidth']) ) {
	    $_GET['ImageWidth'] = 0;
	  } else if ( !ereg("^[0-9]+$", $_GET['ImageWidth'], $trashed) ) {
	    ?><p><?php _e('Image Width should be a number.','gan'); ?></p><?php
	    $result = false;
	  }
	  return $result;
	}

	function fixdate ($date) {
	  if (preg_match('@^([[:digit:]]*)/([[:digit:]]*)/([[:digit:]]*)$@',$date,$matches)) {
	    $m = $matches[1];
	    $d = $matches[2];
	    $y = $matches[3];
	    if (strlen($y) < 4) {$y = '20'.$y;}
	    return sprintf('%04d-%02d-%02d',$y,$m,$d);
	  } else {
	    return $date;
	  }
	}

	function tsv_unquote ($s) {
	  if (preg_match('/^"(.*)"$/',$s,$matches)) {
	    $q = $matches[1];
	    return preg_replace('/""/','"',$q);
	  } else {
	    return $s;
	  }
	}
	/*
	 * Validate dates
	 */

	function checkdate( $label, $datestring ) {
	  // Basic format check
	  if (!ereg("^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$", $datestring, $trashed)) {
	    ?><p><?php printf(__('The date %s is invalid: should be YYYY-MM-DD.','gan'),$label); ?> </p><?php
	    return false;
	  }
	  $result = true;
	  $pos=strpos($datestring,'-');	// find first dash
	  $year=substr($datestring,0,$pos); // extract year
	  $month=substr($datestring,$pos+1,2); // month
	  $day=substr($datestring,$pos+4,2); // day
	  if ($month < 1 || $month > 12) {		// month range check
	    ?><p><?php printf(__('The date %s is invalid: month out of range, should be 1 to 12.','gan'),$label); ?></p><?php
	    $result = false;
	  }
	  if ($day < 1 || $day > 31) {			// date range check
	    ?><p><?php printf(__('The date %s is invalid: date out of range, should be 1 to 31.','gan'),$label); ?> </p><?php
	    $result = false;
	  }
	  return $result;  
	}

	/*
	 * Edit a row
	 */

	function admin_database_editrow ( $id) {
	  //echo "<!-- \$_GET is: \n";
	  //foreach ($_GET as $key => $value) {
	  //  echo "$key => $value\n";
	  //}
	  //echo " -->";
	  if ( isset($_GET['Cancel']) ) {
	    $this->admin_database_page();
	    return;
	  }
	  $id = $_GET['id'];
	  //must check that the user has the required capability 
	  if (!current_user_can('manage_options'))
	  {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	  $defaults = GAN_Database::get_ad($id);
	//  echo "<!-- \$defaults (before $_GET check) is: \n";
	//  foreach ($defaults as $key => $value) {
	//    echo "$key => $value\n";
	//  }
	//  echo " -->";
	  if (isset($_GET['Advertiser']) ) {  $defaults['Advertiser'] = $_GET['Advertiser']; }
	  if (isset($_GET['LinkID']) ) {  $defaults['LinkID'] =     $_GET['LinkID']; }
	  if (isset($_GET['LinkName']) ) {  $defaults['LinkName'] =   $_GET['LinkName']; }
	  if (isset($_GET['MerchandisingText']) ) {  $defaults['MerchandisingText'] = $_GET['MerchandisingText']; }
	  if (isset($_GET['AltText']) ) {  $defaults['AltText'] =    $_GET['AltText']; }
	  if (isset($_GET['StartDate']) ) {  $defaults['StartDate'] =  $_GET['StartDate']; }
	  if (isset($_GET['EndDate']) ) {  $defaults['EndDate'] =    $_GET['EndDate']; }
	  if (isset($_GET['ClickserverLink']) ) {  $defaults['ClickserverLink'] = $_GET['ClickserverLink']; }
	  if (isset($_GET['ImageURL']) ) {  $defaults['ImageURL'] =   $_GET['ImageURL']; }
	  if (isset($_GET['ImageHeight']) ) {  $defaults['ImageHeight'] = $_GET['ImageHeight']; }
	  if (isset($_GET['ImageWidth']) ) {  $defaults['ImageWidth'] = $_GET['ImageWidth']; }
	  if (isset($_GET['LinkURL']) ) {  $defaults['LinkURL'] =    $_GET['LinkURL']; }
	  if (isset($_GET['PromoType']) ) {  $defaults['PromoType'] =  $_GET['PromoType']; }
	  if (isset($_GET['MerchantID']) ) {  $defaults['MerchantID'] = $_GET['MerchantID']; }
	  if (isset($_GET['enabled']) ) {  $defaults['enabled'] =    $_GET['enabled']; }
	  if( isset($_GET['Update']) &&
	      $this->add_element_checkvalid() ) {
	    GAN_Database::update_GAN($id,$_GET['Advertiser'],
				      $_GET['LinkID'],
				      $_GET['LinkName'],
				      $_GET['MerchandisingText'],
				      $_GET['AltText'],
				      $_GET['StartDate'],
				      $_GET['EndDate'],
				      $_GET['ClickserverLink'],
				      $_GET['ImageURL'],
				      $_GET['ImageHeight'],
				      $_GET['ImageWidth'],
				      $_GET['LinkURL'],
				      $_GET['PromoType'],
				      $_GET['MerchantID'],
				      $_GET['enabled'] );
	    return true;
	  }
	  ?><div class="wrap"><div id="icon-gan-edit-db" class="icon32"><br /></div><h2><?php _e('Edit Element to GAN Database','gan'); ?></h2>
	  <form name="edit-GAN-element" method="GET" action="<?php echo admin_url('admin.php'); ?>">
	  <input type="hidden" name="page" value="gan-database-page">
	  <input type="hidden" value="<?php echo $id; ?>" name="id">
	  <table class="form-table">
	  <tr valign="top">
		<th scope="row"><label for="GAN-Advertiser" style="width:20%;"><?php _e('Advertiser:','gan'); ?></label></th>
		<td><input id="GAN-Advertiser" 
			value="<?php echo $defaults['Advertiser']; ?>" 
			name="Advertiser" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-LinkID" style="width:20%;"><?php _e('Link ID:','gan'); ?></label></th>
		<td><input id="GAN-LinkID" 
			value="<?php echo $defaults['LinkID']; ?>" name="LinkID" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-LinkName" style="width:20%;"><?php _e('Link Name:','gan'); ?></label></th>
		<td><input id="GAN-LinkName" 
			value="<?php echo $defaults['LinkName']; ?>" name="LinkName" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-MerchandisingText" 
			style="width:20%;top-margin:0;"><?php _e('Merchandising Text:','gan'); ?></label></th>
		<td><textarea id="GAN-MerchandisingText" name="MerchandisingText" 
			cols="50" rows="5" 
			style="width:75%;"><?php echo $defaults['MerchandisingText']; ?></textarea></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-AltText" style="width:20%;"><?php _e('Alt Text:','gan'); ?></label></th>
		<td><input id="GAN-AltText" 
			value="<?php echo $defaults['AltText']; ?>" name="AltText" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-StartDate" style="width:20%;"><?php _e('Start Date:','gan'); ?></label></th>
		<td><input id="GAN-StartDate" 
			value="<?php echo $defaults['StartDate']; ?>" name="StartDate" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-EndDate" style="width:20%;"><?php _e('End Date:','gan'); ?></label></th>
		<td><input id="GAN-EndDate" 
			value="<?php echo $defaults['EndDate']; ?>" name="EndDate" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-ClickserverLink" 
			style="width:20%;"><?php _e('Clickserver Link:','gan'); ?></label></th>
		<td><input id="GAN-ClickserverLink" 
			value="<?php echo $defaults['ClickserverLink']; ?>"
			name="ClickserverLink" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-ImageURL" style="width:20%;"><?php _e('ImageURL:','gan'); ?></label></th>
		<td><input id="GAN-ImageURL" 
			value="<?php echo $defaults['ImageURL']; ?>" name="ImageURL" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-ImageHeight" style="width:20%;"><?php _e('ImageHeight:','gan'); ?></label></th>
		<td><input id="GAN-ImageHeight" 
			value="<?php echo $defaults['ImageHeight']; ?>" 
			name="ImageHeight" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-ImageWidth" style="width:20%;"><?php _e('ImageWidth:','gan'); ?></label></th>
		<td><input id="GAN-ImageWidth" 
			value="<?php echo $defaults['ImageWidth']; ?>" 
			name="ImageWidth" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-LinkURL" style="width:20%;"><?php _e('LinkURL:','gan'); ?></label></th>
		<td><input id="GAN-LinkURL" 
			value="<?php echo $defaults['LinkURL']; ?>" name="LinkURL" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-PromoType" style="width:20%;"><?php _e('PromoType:','gan'); ?></label></th>
		<td><input id="GAN-PromoType" 
			value="<?php echo $defaults['PromoType']; ?>" name="PromoType" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-MerchantID" style="width:20%;"><?php _e('MerchantID:','gan'); ?></label></th>
		<td><input id="GAN-MerchantID" 
			value="<?php echo $defaults['MerchantID']; ?>" 
			name="MerchantID" 
			style="width:75%;" /></td></tr>
	  <tr valign="top">
		<th scope="row"><label for="GAN-enabled"><?php _e('enabled?','gan'); ?></label></th>
		<td><input class="checkbox" type="checkbox"
			<?php checked( $defaults['enabled'], true ); ?>
			id="GAN-enabled" name="enabled" value="1"
			/></td></tr>
	  </table>
	  <p>
		<input type="submit" name="Update" class="button-primary" value="<?php _e('Update Element','gan'); ?>">
		<a href="<?php $this->cancel_page_query(); ?>"><?php _e('Cancel','gan'); ?></a>
	  </p>
	  <?php $this->hidden_filter_fields(); ?>
	  </form></div><?php
	  return false;
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
	  ?><div class="wrap"><div id="icon-gan-ad-imp" class="icon32"><br /></div><h2><?php _e('Ad Impression Statistics','gan'); ?></h2>
	    <form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-ad-impstats" />
		<?php $this->merchdropdown($merchid) ?>&nbsp;<?php $this->imwidthdropdown($imwidth); ?>
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
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" /></div>
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
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" /></div>
	<br class="clear" /></div></form>
	<?php
	  } else {
	  ?><h4><?php _e('No matching entries found.','gan'); ?></h4><?php
	  }
	  ?><form method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="gan-database-ad-impstats" />
		<?php $this->merchdropdown($merchid) ?>&nbsp;<?php $this->imwidthdropdown($imwidth); ?>
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
	  ?><div class="wrap"><div id="icon-gan-merch-imp" class="icon32"><br /></div><h2><?php _e('Merchant Impression Statistics','gan'); ?></h2>
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
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" /></div>
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
	<input type="submit" name="zerostats" class="button" value="<?php _e('Zero Stats','gan'); ?>" /></div>
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
	    ?><div id="message"class="updated fade"><p><?php _e('Options Saved','gan'); ?></p></div><?php
	  } else if ( isset($_GET['upgradedatabase']) ) {
	    GAN_Database::upgrade_database();
	    ?><div id="message"class="updated fade"><p><?php _e('Database Upgraded','gan'); ?></p></div><?php
	  }
	  /* Head of page, filter and screen options. */
	  $autoexpire = get_option('wp_gan_autoexpire');
	  ?><div class="wrap"><div id="icon-gan-options" class="icon32"><br /></div><h2><?php _e('Configure Options','gan'); ?></h2>
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

	/*
	 * Create merchant dropdown list
	 */

	function merchdropdown ($merchid) {
	  $GANMerchants = GAN_Database::get_merchants();
	
	  ?><label for="gan-merchid"><?php _e('Advertisers:','gan'); ?></label>
	    <select name="merchid" id="gan-merchid" maxlength="20">
	    <option value="" <?php 
		if ( $merchid == "" ) echo 'selected="selected"'; 
		?>><?php _e('All','gan'); ?></option><?php
	  foreach ((array)$GANMerchants as $GANMerchant) {
	    $shortadvert = substr($GANMerchant['Advertiser'],0,25);
	    ?><option value="<?php echo $GANMerchant['MerchantID']; ?>" <?php 
		if ( $merchid == $GANMerchant['MerchantID'] ) 
			echo 'selected="selected"'; 
		?> label="<?php echo $GANMerchant['Advertiser']; 
		?>"><?php echo $shortadvert; ?></option><?php 
	  }
	  ?></select><?php
	}

	/*
	 * Make a image width dropdown list
	 */

	function imwidthdropdown ($imwidth) {
	  $GANImageWidths = GAN_Database::get_imagewidths();
	
	  ?><label for="gan-imwidth"><?php _e('Image Width:','gan'); ?></label>
	    <select name="imwidth" id="gan-imwidth" maxlength="4">
	    <option value="-1" <?php 
		if ( $imwidth == "-1" ) echo 'selected="selected"'; 
		?>><?php _e('All','gan'); ?></option><?php
	  foreach ((array)$GANImageWidths as $GANImageWidth) {
	    ?><option value="<?php echo $GANImageWidth['ImageWidth']; ?>" <?php 
		if ( $imwidth == $GANImageWidth['ImageWidth'] ) 
			echo 'selected="selected"'; 
		?>><?php echo $GANImageWidth['ImageWidth']; ?></option><?php 
	  }
	  ?></select><?php
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



