<?php

/*
 * GAN Link List_Table: list of ad links in the database.  Includes code to 
 * add/edit/view single link ads and code to add link ads in bulk.
 *
 * (Based on "Custom List Table Example" plugin by Matt Van Andel)
 * (Re-written, from scratch, from the original hack for the GAN plugin)
 *
 */

/* Load our constants */
require_once(dirname(__FILE__) . "/GAN_Constants.php");

/* Load Database code */
require_once(GAN_INCLUDES_DIR . "/GAN_Database.php");
/* Load Per_Page_Screen_Opt class */
require_once(GAN_INCLUDES_DIR . "/Per_Page_Screen_Opt.php");

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/*
 * Class to display and manage GAN Link Ads
 */

class GAN_Link_List_Table extends WP_List_Table {
  var $viewmode = 'add';
  var $viewid   = 0;
  var $viewitem;

  var $merchid = '';
  var $imsize = -1;

  var $per_page_screen_option;

  static function my_screen_option() {return 'gan_links_per_page';}

  function __construct($screen_id) {
    //file_put_contents("php://stderr","*** GAN_Link_List_Table::__construct()\n");
    /* Add screen option: links per page. */
    $this->per_page_screen_option = 
	new GAN_Per_Page_Screen_Option($screen_id,
		GAN_Link_List_Table::my_screen_option(),'Links');

    //Set parent defaults
    parent::__construct( array ('singular' => 'Link',	// One thing
				'plural'   => 'Links',  // Multiple things
				'ajax'	   => false	// AJAX?
				) );

  }
  function create_screen_opts($screen_id) {
    $this->per_page_screen_option->create_screen_opts($screen_id);
  }
  /* Default column (nothing really here, since every displayed column gets 
   * its own function).
   */
  function column_default($item, $column_name) {
    return apply_filters( 'manage_items_custom_column','',
				$column_name,$item->id);
  }
  /* Check box column. */
  function column_cb ($item) {
    return '<input type="checkbox" name="checked[]" value="'.$item->id.'" />';
  }
  /* Merchant ID column. */
  function column_MerchantID($item) {
    return GAN_Database::get_merch_name($item->MerchantID);
  }
  /* Link ID column. */
  function column_LinkID($item) {
    return $item->LinkID;
  }
  /* Link name column -- this is where the edit, etc. links live, 
   * so it is handled special. */
  function column_LinkName($item) {
    // Build row actions
    $actions = array(
	'edit' => '<a href="'.add_query_arg(array('page' => 'gan-add-link-ad',
						  'mode' => 'edit',
						  'merchid' => $this->merchid,
						  'imsize' => $this->imsize,
						  'id' => $item->id),
						admin_url('admin.php')).'">'.
			__('Edit','gan')."</a>",
	'view' => '<a href="'.add_query_arg(array('page' => 'gan-add-link-ad',
						  'mode' => 'view',
						  'merchid' => $this->merchid,
						  'imsize' => $this->imsize,
						  'id' => $item->id),
						admin_url('admin.php')).'">'.
			__('View','gan')."</a>",
	'delete' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'],
						    'action' => 'delete',
						    'merchid' => $this->merchid,
						    'imsize' => $this->imsize,
						    'id' => $item->id),
						 admin_url('admin.php')).'">'.
			__('Delete','gan')."</a>",
	'enabletoggle' => '<a href="'.add_query_arg(
					     array('page' => $_REQUEST['page'],
						   'action' => 'enabletoggle',
						   'merchid' => $this->merchid,
						   'imsize' => $this->imsize,
						   'id' => $item->id),
					admin_url('admin.php')).'">'.
			__('Toggle Enable','gan')."</a>"
	);
    return $item->LinkName.$this->row_actions($actions);
  }
  function column_ImageSize($item) {
    if ($item->ImageWidth == 0 && $item->ImageHeight == 0) {
      return 'text';
    } else {
      return $item->ImageWidth.'x'.$item->ImageHeight;
    }
  }
  function column_StartDate($item) {
    //file_put_contents("php://stderr","*** GAN_DB_List_Table::column_StartDate(".print_r($item,true).")\n");
    return mysql2date('F j, Y',$item->StartDate.' 00:00:00');
    //return $item->StartDate;
  }
  function column_EndDate($item) {
    //file_put_contents("php://stderr","*** GAN_DB_List_Table::column_EndDate(".print_r($item,true).")\n");
    return mysql2date('F j, Y',$item->EndDate.' 00:00:00');
    //return $item->EndDate;
  }
  function column_Enabled($item) {
    if ($item->Enabled) {
      return __('Yes', 'gan');
    } else {
      return __('No', 'gan');
    }
  }
  function get_columns() {
    return array (
	'cb' => '<input type="checkbox" />',
	'MerchantID' => __('Advertiser','gan'),
	'LinkID' => __('Link ID','gan'),
	'LinkName' => __('Link Name','gan'),
	'ImageSize' => __('Image Size','gan'),
	'StartDate' => __('Start Date','gan'),
	'EndDate' => __('End Date','gan'),
	'Enabled' => __('Enabled?','gan'));
  }
  function get_sortable_columns() {
    return array(
	'EndDate' => array('EndDate',false),
	'StartDate' => array('StartDate',false),
	'LinkName' => array('LinkName',false),
	'LinkID' => array('LinkID',false),
	'ImageSize' => array('ImageWidth, ImageHeight',false)
	);
  }
  function get_bulk_actions() {
    return array ('delete' => __('Delete','gan'),
		    'enabletoggle' => __('Toggle Enabled','gan') );
  }
  function current_action() {
    //file_put_contents("php://stderr","*** GAN_Link_List_Table::current_action(): _REQUEST is ".print_r($_REQUEST,true)."\n");
    // Extra nav actions
    if ( isset($_REQUEST['delmerch_top']) &&
	 (isset($_REQUEST['modmerchid_top']) &&
	  $_REQUEST['modmerchid_top'] != '') ) 
      return 'delmerch_top';
    
    if ( isset($_REQUEST['delmerch_bottom']) &&
         ( isset($_REQUEST['modmerchid_bottom']) && 
		   $_REQUEST['modmerchid_bottom'] != '') ) 
      return 'delmerch_bottom';
    
    if ( isset($_REQUEST['disablemerch_top']) &&
         ( isset($_REQUEST['modmerchid_top']) && 
		 $_REQUEST['modmerchid_top'] != '') ) 
      return 'disablemerch_top';
    
    if ( isset($_REQUEST['disablemerch_bottom']) &&
         ( isset($_REQUEST['modmerchid_bottom']) && 
	   $_REQUEST['modmerchid_bottom'] != '') ) 
      return 'disablemerch_bottom';
    
    if ( isset($_REQUEST['enablemerch_top']) &&
         ( isset($_REQUEST['modmerchid_top']) && 
		   $_REQUEST['modmerchid_top'] != '') ) 
      return 'enablemerch_top';
    
    if ( isset($_REQUEST['enablemerch_bottom']) &&
         ( isset($_REQUEST['modmerchid_bottom']) && 
		   $_REQUEST['modmerchid_bottom'] != '') ) 
      return 'enablemerch_bottom';
    
    if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
	return $_REQUEST['action'];

    if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
	return $_REQUEST['action2'];
    if (isset($_REQUEST['enableall_top']) || 
	isset($_REQUEST['enableall_bottom'])) 
	return 'enableall';
    
    if (isset($_REQUEST['deleteexpired_top']) ||
	isset($_REQUEST['deleteexpired_bottom'])) 
	return 'deleteexpired';
    
    return false;
  }
  function process_bulk_action($where,$wand) {
    $action = $this->current_action();
    file_put_contents("php://stderr","*** GAN_Link_List_Table::process_bulk_action: action is $action (from current_action())\n");
    switch ($action) {
	case 'delmerch_top': 
		GAN_Database::delete_ads_by_merchantID($_REQUEST['modmerchid_top']);
		break;
	case 'delmerch_bottom':
		GAN_Database::delete_ads_by_merchantID($_REQUEST['modmerchid_bottom']);
		break;
	case 'disablemerch_top':
		GAN_Database::disable_ads_by_merchantID($_REQUEST['modmerchid_top']);
		break;
	case 'disablemerch_bottom':
		GAN_Database::disable_ads_by_merchantID($_REQUEST['modmerchid_bottom']);
		break;
	case 'enablemerch_top':
		GAN_Database::enable_ads_by_merchantID($_REQUEST['modmerchid_top']);
		break;
	case 'enablemerch_bottom':
		GAN_Database::enable_ads_by_merchantID($_REQUEST['modmerchid_bottom']);
		break;
	case 'delete':
	     if ( isset($_REQUEST['checked']) && !empty($_REQUEST['checked'])) {
	       foreach ( $_REQUEST['checked'] as $theitem ) {
		 GAN_Database::delete_ad_by_id($theitem);
	       }
	     } else if ( isset($_REQUEST['id']) ) {
	       GAN_Database::delete_ad_by_id($_REQUEST['id']);
	     }
	     break;
	case 'enabletoggle':
	     if ( isset($_REQUEST['checked']) && !empty($_REQUEST['checked'])) {
	       foreach ( $_REQUEST['checked'] as $theitem ) {
		 GAN_Database::toggle_enabled($theitem);
	       }
	     } else if ( isset($_REQUEST['id']) ) {
	       GAN_Database::toggle_enabled($_REQUEST['id']);
	     }
	     break;
	case 'enableall':
	     GAN_Database::enableall($where);
	     break;
	case 'deleteexpired':
	     GAN_Database::deleteexpired($wand);
	     break;
    }
  }
  function process_filters_and_bulk_action() {
    if ( isset($_REQUEST['merchid']) ) {
      $this->merchid = $_REQUEST['merchid'];
    } else {
      $this->merchid = '';
    }
    if ( isset($_REQUEST['imsize']) ) {
      $this->imsize = $_REQUEST['imsize'];
    } else {
      $this->imsize = -1;
    }
    if ( isset($_REQUEST['filter_top']) ) {
      if ( isset($_REQUEST['merchid_top']) ) {
	$this->merchid = $_REQUEST['merchid_top'];
      }
      if ( isset($_REQUEST['imsize_top']) ) {
	$this->imsize = $_REQUEST['imsize_top'];
      }
    } else if ( isset($_REQUEST['filter_bottom']) ) {
      if ( isset($_REQUEST['merchid_bottom']) ) {
	$this->merchid = $_REQUEST['merchid_bottom'];
      }
      if ( isset($_REQUEST['imsize_bottom']) ) {
	$this->imsize = $_REQUEST['imsize_bottom'];
      }
    }
    /* Build where clause */
    global $wpdb;
    if ( $this->merchid != '' || $this->imsize != -1 || isset($_REQUEST['s']) ) {
      $wclause = ''; $and = '';
      if ( isset($_REQUEST['s']) ) {
        $wclause = $wpdb->prepare(' LinkName LIKE %s ','%'.$_REQUEST['s'].'%');
        $and = ' && ';
      }
      if ($this->merchid != '') {
	$wclause .= $and .$wpdb->prepare(' MerchantID = %s',$this->merchid);
	$and = ' && ';
      }
      if ($this->imsize != -1) {
	$size = explode('x',$this->imsize);
	$wclause .= $and . $wpdb->prepare(
				  ' ImageWidth = %d && ImageHeight = %d',
				  $size[0],$size[1]);
	//file_put_contents("php://stderr","*** GAN_Link_List_Table::process_filters_and_bulk_action: this->imsize is '".$this->imsize."', size is ".print_r($size,true).", wclause = '".$wclause."'\n");
      }
      $where = ' where ' . $wclause . ' ';
      $wand  = ' && ' . $wclause . ' ';
    } else {
      $where = ' '; $wand  = ' ';
    }
    
    $this->process_bulk_action($where,$wand);
    return $where;      
  }
  function check_permissions() {
    if (!current_user_can('manage_options')) {
      wp_die( __('You do not have sufficient permissions to access this page.','gan') );
    }
  }
  function extra_tablenav( $which ) {
    if ($which == 'top') {
      ?><input type="hidden" name="merchid" value="<?php echo $this->merchid; ?>" />
	<input type="hidden" name="imsize" value="<?php echo $this->imsize; ?>" /><?php
    }
    ?><br clear="all" /><div class="alignleft actions"><?php
    GAN_Database::merchdropdown($this->merchid,'merchid_'.$which);
    echo '&nbsp;';
    GAN_Database::imsizedropdown($this->imsize,'imsize_'.$which);
    echo '&nbsp;';
    submit_button(__( 'Filter','gan'), 'secondary', 'filter_'.$which, 
			false, array( 'id' => 'post-query-submit') );
    echo ' ';
    submit_button(__( 'Enable All','gan'), 'secondary', 
			'enableall_'.$which, false, 
			array( 'id' => 'post-query-submit') );
    echo ' ';
    submit_button(__( 'Delete Expired','gan'), 'secondary', 
			'deleteexpired_'.$which, false, 
			array( 'id' => 'post-query-submit') );
    echo '<br />';
    GAN_Database::merchdropdown($this->merchid,'modmerchid_'.$which);
    echo '&nbsp;';
    submit_button(__( 'Delete Merchant','gan'), 'primary', 'delmerch_'.$which,
			false, array( 'id' => 'post-query-submit') );
    echo '&nbsp;';
    submit_button(__( 'Disable Merchant','gan'), 'secondary', 'disablemerch_'.$which,
			false, array( 'id' => 'post-query-submit') );
    echo '&nbsp;';
    submit_button(__( 'Enable Merchant','gan'), 'secondary', 'enablemerch_'.$which,
			false, array( 'id' => 'post-query-submit') );
    echo '</div>';
  }
  function get_column_info() {
    if ( isset($this->_column_headers) ) {return $this->_column_headers;}
    $columns = $this->get_columns( );
    $hidden = array();

    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array( $columns, $hidden, $sortable );

    return $this->_column_headers;
  }
  
  function prepare_items() {
    // Check permissions
    $this->check_permissions();
    // Get per page (screen option)
    $per_page = $this->per_page_screen_option->get();
    // Deal with columns
    $columns = $this->get_columns();	// All of our columns
    $hidden  = array();		// Hidden columns [none]
    $sortable = $this->get_sortable_columns(); // Sortable columns
    $this->_column_headers = array($columns,$hidden,$sortable); // Set up columns

    // Process filters and bulk action, if any
    $whereclause = $this->process_filters_and_bulk_action();

    $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'EndDate';
    $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';

    $all_items = GAN_Database::get_GAN_data($whereclause,'OBJECT',$orderby,$order);
    $current_page = $this->get_pagenum();
    $total_items = count($all_items);
    $data = array_slice($all_items,(($current_page-1)*$per_page),$per_page);
    $this->items = $data;
    $this->set_pagination_args( array (
		'total_items' => $total_items,
		'per_page'    => $per_page,
		'total_pages' => ceil($total_items/$per_page) ));
  }
  /* Add/View/Edit page */
  function prepare_one_item() {
    $this->check_permissions();
    if ( isset($_REQUEST['merchid']) ) {
      $this->merchid = $_REQUEST['merchid'];
    } else {
      $this->merchid = '';
    }
    if ( isset($_REQUEST['imsize']) ) {
      $this->imsize = $_REQUEST['imsize'];
    } else {
      $this->imsize = -1;
    }
    $message = '';
    if ( isset($_REQUEST['addad']) ) {
      $message = $this->checkiteminform(0);
      $item    = $this->getitemfromform();
      if ($message == '') {
	$newid = GAN_Database::insert_GAN($item->Advertiser,
					  $item->LinkID,
					  $item->LinkName,
					  $item->MerchandisingText,
					  $item->AltText,
					  $item->StartDate,
					  $item->EndDate,
					  $item->ClickserverLink,
					  $item->ImageURL,
					  $item->ImageHeight,
					  $item->ImageWidth,
					  $item->LinkURL,
					  $item->PromoType,
					  $item->MerchantID,
					  $item->enabled);
	$message = '<p>'.sprintf(__('%s inserted with id %d.','gan'), 
				  $item->LinkName, $newid).'</p>';
	$this->viewmode = 'edit';
	$this->viewid   = $newid;
	$this->viewitem = $item;
      } else {
	$this->viewmode = 'add';
	$this->viewid   = 0;
	$this->viewitem = $item;
      }
    } else if ( isset($_REQUEST['updatead']) && isset($_REQUEST['id']) ) {
      $message = $this->checkiteminform($_REQUEST['id']);
      $item    = $this->getitemfromform();
      $item->id = $_REQUEST['id'];
      if ($message == '') {
	GAN_Database::update_GAN($item->id,
				 $item->Advertiser,
				 $item->LinkID,
				 $item->LinkName,
				 $item->MerchandisingText,
				 $item->AltText,
				 $item->StartDate,
				 $item->EndDate,
				 $item->ClickserverLink,
				 $item->ImageURL,
				 $item->ImageHeight,
				 $item->ImageWidth,
				 $item->LinkURL,
				 $item->PromoType,
				 $item->MerchantID,
				 $item->enabled);
	$message = '<p>'.sprintf(__('%s updated.','gan'), 
				$item->LinkName).'</p>';
      }
      $this->viewmode = 'edit';
      $this->viewid   = $item->id;
      $this->viewitem = $item;
    } else {
      $this->viewmode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'add';
      $this->viewid   = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
      switch ($this->viewmode) {
	case 'edit':
	case 'view': 
	     if ($this->viewid == 0) {$this->viewmode = 'add';}
	     break;
	case 'add':
	     $this->viewid   = 0;
	     break;
	default:
	     $this->viewmode = 'add';
	     $this->viewid   = 0;
	     break;
      }
      if ($this->viewid != 0) {
        $this->viewitem = GAN_Database::get_ad($this->viewid,'OBJECT');
      } else {
        $this->viewitem = GAN_Database::get_blank_ad();
      }	    
    }
    return $message;
  }
  function checkiteminform($id) {
    $result = '';
    if ( empty($_REQUEST['Advertiser']) ) {
      $result .= '<p>'.__('Advertiser missing.','gan').'</p>';
    }
    if ( empty($_REQUEST['LinkID']) ) {
      $result .= '<p>'.__('Link ID missing.','gan').'</p>';
    } else if ($id != GAN_Database::find_ad_by_LinkID($_REQUEST['LinkID'])) {
      $result .= '<p>'.__('Duplicate Link ID.','gan').'</p>';
    }
    if ( empty($_REQUEST['LinkName']) ) {
      $result .= '<p>'.__('Link Name missing.','gan').'</p>';
    }
    if ( empty($_REQUEST['StartDate']) ) {
      $result .= '<p>'.__('Start Date missing.','gan').'</p>';
    } else {
      $result .= $this->checkdate(__('Start Date','gan'), 
					$_REQUEST['StartDate']);
    }
    if ( empty($_REQUEST['EndDate']) ) {
      $result .= '<p>'.__('End Date missing.','gan').'</p>';
    } else {
      $result .= $this->checkdate(__('End Date','gan'), 
				  $_REQUEST['EndDate']);
    }
    if ( empty($_REQUEST['ClickserverLink']) ) {
      $result .= '<p>'.__('Clickserver Link missing.','gan').'</p>';
    }
    if ( empty($_REQUEST['MerchantID']) ) {
      $result .= '<p>'.__('Merchant ID missing.','gan').'</p>';
    }
    if ( empty($_REQUEST['ImageHeight']) ) {
      $_REQUEST['ImageHeight'] = 0;
    } else if ( !preg_match('/^[[:digit:]]*$/',$_REQUEST['ImageHeight']) ) {
      $result .= '<p>'.__('Image Height should be a whole number.','gan').'</p>';
    }
    if ( empty($_REQUEST['ImageWidth']) ) {
      $_REQUEST['ImageWidth'] = 0;
    } else if ( !preg_match('/^[[:digit:]]*$/',$_REQUEST['ImageWidth']) ) {
      $result .= '<p>'.__('Image Width should be a whole number.','gan').'</p>';
    }
    return $result;
  }
  function checkdate($label,$datestring) {
    $matches = array();
    if (preg_match('/^([0-9][0-9][0-9][0-9])-([0-9][0-9]*)-([0-9][0-9]*)$/',
		   $datestring,$matches)) {
      $year = $matches[1];
      $month = $matches[2];
      $day = $matches[3];
    } else if (preg_match('|^([0-9][0-9]*)/([0-9][0-9]*)/([0-9][0-9][0-9][0-9])$|',
			$datestring,$matches)) {
      $month = $matches[1];
      $day = $matches[2];
      $year = $matches[3];
    } else {
      return '<p>'.sprintf(__('%s invalid: should be YYYY-MM-DD or MM/DD/YYYY.','gan'),$label).'</p>';
    }
    if ($month < 1 || $month > 12) {
      return '<p>'.sprintf(__('%s month out of range, should be 1 to 12.','gan'),$label).'</p>';
    }
    if ($day < 1 || $day > 31) {
      return '<p>'.sprintf(__('%s date out of range, should be 1 to 31.','gan'),$label).'</p>';
    }
    return '';
  }
  function getitemfromform() {
    $itemary = array();
    foreach ( array('Advertiser', 'LinkID', 'LinkName', 'StartDate', 
		    'EndDate', 'ClickserverLink', 'MerchantID', 
		    'ImageHeight', 'ImageWidth', 'enabled') as $field ) {
      $itemary[$field] = $_REQUEST[$field];
    }
    $itemary['StartDate'] = $this->normalizedate($itemary['StartDate']);
    $itemary['EndDate'] = $this->normalizedate($itemary['EndDate']);
    return (object) $itemary;
  }
  function normalizedate($datestring) {
    $matches = array();
    if (preg_match('/^([0-9][0-9][0-9][0-9])-([0-9][0-9])-([0-9][0-9])$/',
		   $datestring,$matches)) {
      $year = $matches[1];
      $month = $matches[2];
      $day = $matches[3];
    } else if (preg_match('|^([0-9][0-9]*)/([0-9][0-9]*)/([0-9][0-9][0-9][0-9])$|',
			  $datestring,$matches)) {
      $month = $matches[1];
      $day = $matches[2];
      $year = $matches[3];
    }
    return sprintf('%04d-%02d-%02d',$year,$month,$day);
  }
  function add_item_icon() {
    switch ($this->viewmode) {
      case 'add': return 'icon-gan-add-db';
      case 'edit': return 'icon-gan-edit-db';
      case 'view': return 'icon-gan-view-db';
    }
  }
  function add_item_h2() {
    switch ($this->viewmode) {
      case 'add': return __('Add Link to the GAN Database','gan');
      case 'edit': return __('Edit Link in the GAN Database','gan');
      case 'view': return __('View Link in the GAN Database','gan');
    }
  }
  function display_one_item_form($returnURL) {
    if ( isset($_REQUEST['merchid']) ) {
      ?><input type="hidden" name="merchid" value="<?php echo $_REQUEST['merchid'] ?>" /><?php
    }
    if ( isset($_REQUEST['imsize']) ) {
      ?><input type="hidden" name="imsize" value="<?php echo $_REQUEST['imsize'] ?>" /><?php
    }
    if ($this->viewmode != 'add') {
      ?><input type="hidden" name="id" value="<?php echo $this->viewid; ?>" /><?php
    }
    ?><table class="form-table">
      <tr valign="top">
	<th scope="row"><label for="GAN-Advertiser" style="width:20%;"><?php _e('Advertiser:','gan'); ?></label></th>
	<td><input id="GAN-Advertiser" 
		   value="<?php echo $this->viewitem->Advertiser; ?>" 
		   name="Advertiser" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-LinkID" style="width:20%;"><?php _e('Link ID:','gan'); ?></label></th>
	<td><input id="GAN-LinkID" 
		   value="<?php echo $this->viewitem->LinkID; ?>" name="LinkID" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-LinkName" style="width:20%;"><?php _e('Link Name:','gan'); ?></label></th>
	<td><input id="GAN-LinkName" 
		   value="<?php echo $this->viewitem->LinkName; ?>" name="LinkName" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-MerchandisingText" 
	    style="width:20%;top-margin:0;"><?php _e('Merchandising Text:','gan'); ?></label></th>
	<td><textarea id="GAN-MerchandisingText" name="MerchandisingText" 
		      cols="50" rows="5" 
		      style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?>><?php echo $this->viewitem->MerchandisingText; ?></textarea></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-AltText" style="width:20%;"><?php _e('Alt Text:','gan'); ?></label></th>
	<td><input id="GAN-AltText" 
		   value="<?php echo $this->viewitem->AltText; ?>" name="AltText" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-StartDate" style="width:20%;"><?php _e('Start Date:','gan'); ?></label></th>
	<td><input id="GAN-StartDate" 
		   value="<?php echo $this->viewitem->StartDate; ?>" name="StartDate" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-EndDate" style="width:20%;"><?php _e('End Date:','gan'); ?></label></th>
	<td><input id="GAN-EndDate" 
		   value="<?php echo $this->viewitem->EndDate; ?>" name="EndDate" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-ClickserverLink" 
	    style="width:20%;"><?php _e('Clickserver Link:','gan'); ?></label></th>
	<td><input id="GAN-ClickserverLink" 
		   value="<?php echo $this->viewitem->ClickserverLink; ?>"
		   name="ClickserverLink" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-ImageURL" style="width:20%;"><?php _e('Image URL:','gan'); ?></label></th>
	<td><input id="GAN-ImageURL" 
		   value="<?php echo $this->viewitem->ImageURL; ?>" name="ImageURL" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-ImageHeight" style="width:20%;"><?php _e('Image Height:','gan'); ?></label></th>
	<td><input id="GAN-ImageHeight" 
		   value="<?php echo $this->viewitem->ImageHeight; ?>" 
		   name="ImageHeight" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-ImageWidth" style="width:20%;"><?php _e('Image Width:','gan'); ?></label></th>
	<td><input id="GAN-ImageWidth" 
		   value="<?php echo $this->viewitem->ImageWidth; ?>" 
		   name="ImageWidth" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-LinkURL" style="width:20%;"><?php _e('Link URL:','gan'); ?></label></th>
	<td><input id="GAN-LinkURL" 
		   value="<?php echo $this->viewitem->LinkURL; ?>" name="LinkURL" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-PromoType" style="width:20%;"><?php _e('Promo Type:','gan'); ?></label></th>
	<td><input id="GAN-PromoType" 
		   value="<?php echo $this->viewitem->PromoType; ?>" name="PromoType" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-MerchantID" style="width:20%;"><?php _e('Merchant ID:','gan'); ?></label></th>
	<td><input id="GAN-MerchantID" 
		   value="<?php echo $this->viewitem->MerchantID; ?>" 
		   name="MerchantID" 
		   style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
      <tr valign="top">
	<th scope="row"><label for="GAN-enabled"><?php _e('enabled?','gan'); ?></label></th>
	<td><input class="checkbox" type="checkbox"
		   <?php checked( $this->viewitem->enabled, true ); ?>
		   id="GAN-enabled" name="enabled" value="1"
		   <?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
    </table>
    <p>
      <?php switch($this->viewmode) {
		case 'add':
			?><input type="submit" name="addad" class="button-primary" value="<?php _e('Add Ad To Database','gan'); ?>" /><?php
			break;
		case 'edit':
			?><input type="submit" name="updatead" class="button-primary" value="<?php _e('Update Ad In Database','gan'); ?>" /><?php
			break;
	    } ?>
	    <a href="<?php echo $returnURL; ?>" class="button-primary"><?php _e('Return','gan'); ?></a>
    </p><?php
  }
  function process_bulk_upload() {
    $this->check_permissions();
    $message = '';
    //file_put_contents("php://stderr","*** GAN_DB_List_Table::process_bulk_upload: _FILES is ".print_r($_FILES,true)."\n");
    if ( isset($_FILES['gan-tsv-file']) ) {
      $fp = fopen($_FILES['gan-tsv-file']['tmp_name'], 'r');
      $sep = ',';
      $row1 = fgetcsv($fp, 0, $sep);
      if ($row1 && count($row1) < 10) {
	$sep = "\t";
	fseek ( $fp, 0, SEEK_SET);
	$row1 = fgetcsv($fp, 0, $sep);
	if (count($row1) < 10) {
	  $message .= '<p>'.__('Not a proper CSV or TSV file.','gan').'</p>';
	  fclose($fp);
	  return $message;
	}
      }
      $columns = count($row1);
      $indexes = array();
      foreach (array("Id","Name","Advertiser Id","Advertiser name",
		     "Tracking URL","Creative URL","Image Size",
		     "Start Date","End Date","Promotion Type",
		     "Merchandising Text") as $colname) {
	$found = array_search($colname,$row1);
	if ($found === FALSE) {
	  $indexes[$colname] = -1;
	} else {
	  $indexes[$colname] = $found;
	}
      }
	    
      $count = 0;
      $message .= '<p>';
      while (($rawelts = fgetcsv($fp, 0, $sep)) != FALSE) {
	//file_put_contents("php://stderr","*** GAN_DB_List_Table::process_bulk_upload: rawelts is ".print_r($rawelts,true)."\n");
	if (count($rawelts) < $columns) {continue;}
	$Advertizer = $rawelts[$indexes["Advertiser name"]];
	$LinkID     = $rawelts[$indexes["Id"]];
	$LinkName   = $rawelts[$indexes["Name"]];
	$MerchandisingText = $rawelts[$indexes["Merchandising Text"]];
	$AltText    = '';
	if ($indexes["Start Date"] < 0) $StartDate = "1970-01-01";
	else $StartDate = $this->fixdate(trim($rawelts[$indexes["Start Date"]]));
	if ($indexes["End Date"] < 0) $EndDate = 'none';
	else $EndDate = $this->fixdate(trim($rawelts[$indexes["End Date"]]));
	if ($EndDate == 'none' || $EndDate == '') {$EndDate = "2037-12-31";}
	$ClickserverURL = $rawelts[$indexes["Tracking URL"]];
	$ImageURL = $rawelts[$indexes["Creative URL"]];
	$width = 0;
	$height = 0;
	$imsize = $rawelts[$indexes["Image Size"]];
	if (preg_match('/^([[:digit:]]*)x([[:digit:]]*)$/',$imsize,$matches) ) {
	  $width = $matches[1];
	  $height = $matches[2];
	} else if (preg_match('/^([[:digit:]]*)[[:space:]]*\?[[:space:]]*([[:digit:]]*)$/',$imsize,$matches) ) {
	  $width = $matches[1];
	  $height = $matches[2];
	}
	$LinkURL = '';
	if ($indexes["Promotion Type"] >= 0) $PromoType = $rawelts[$indexes["Promotion Type"]];
	else $PromoType = '';
	$MerchantID = $rawelts[$indexes["Advertiser Id"]];
	if (GAN_Database::find_ad_by_LinkID($LinkID) != 0) {
	  $message .= sprintf(__('Duplicate Link ID %s (%s). Ad not inserted into database.','gan'),$LinkID,$LinkName);
	  $message .= "<br />\n";
	  continue;
	}
	GAN_Database::insert_GAN($Advertizer,$LinkID,$LinkName,
				 $MerchandisingText,$AltText,$StartDate,
				 $EndDate,$ClickserverURL,$ImageURL,
				 $height,$width,$LinkURL,$PromoType,
				 $MerchantID,1);
	$count++;
	$message .= sprintf(__('Inserted %s (%s) into ad database.','gan'),$LinkID,$LinkName);
	$message .= "<br />\n";
      }
      fclose($fp);
      $message .= sprintf(__('Inserted %d ads into ad database.','gan'),$count);
      $message .= "</p>\n";
    }
    return $message;
  }
  function fixdate ($date) {
    if (preg_match('@^([[:digit:]]*)/([[:digit:]]*)/([[:digit:]]*)$@',$date,$matches)) {
      $m = $matches[1];
      $d = $matches[2];
      $y = $matches[3];
      if (strlen($y) < 4) {
        if ($y <= 37) {
	  $y += 2000;
	} else if ($y > 69) {
	  $y += 1900;
	} 
      }
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

  function display_bulk_upload_form($returnURL) {
    ?><label for="gan-tsv-file"><?php _e('Select CSV or TSV File:','gan'); ?></label><input type='file' name="gan-tsv-file" size="40" /></p>
      <p><input type="submit" name="bulkadd" class="button-primary" value="<?php _e('Upload file','gan'); ?>" />
	  <a href="<?php echo $returnURL; ?>" class="button-primary"><?php _e('Return','gan'); ?></a>
      </p><?php
  }

}

