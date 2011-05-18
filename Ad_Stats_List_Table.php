<?php

class Ad_Stats_List_Table extends WP_List_Table {

	var $merchid = '';
	var $imwidth = -1;
	function Ad_Stats_List_Table() {
		parent::WP_List_Table( array ('items') );
	}
	function get_columns() {
		return array (
			'cb' => '<input type="checkbox" />',
			'MerchantID' => __('Advertiser','gan'),
			'LinkID' => __('Link ID','gan'),
			'LinkName' => __('Link Name','gan'),
			'EndDate' => __('End Date','gan'),
			'ImageWidth' => __('Image Width','gan'),
			'Impressions' => __('Imp.','gan'),
			'LastRunDate' => __('Last View','gan'));
	}
	function get_items_per_page ($option, $default = 20) {
	  if ( isset($_REQUEST['screen-options-apply']) &&
	       $_REQUEST['wp_screen_options']['option'] == $option ) {
	    $per_page = (int) $_REQUEST['wp_screen_options']['value'];
	  } else {
	    $per_page = $default;
	  }
	  return (int) apply_filters( $option, $per_page );
	}	
	function prepare_items() {
	  file_put_contents("php://stderr","*** Ad_Stats_List_Table::prepare_items _REQUEST is ".print_r($_REQUEST,true)."\n");
	  if ( isset($_REQUEST['merchid']) ) {
	    $this->merchid = $_REQUEST['merchid'];
	  } else {
	    $this->merchid = '';
	  }
	  if ( isset($_REQUEST['imwidth']) ) {
	    $this->imwidth = $_REQUEST['imwidth'];
	  } else {
	    $this->imwidth = -1;
	  }
	  if ( isset($_REQUEST['filter_top']) ) {
	    if ( isset($_REQUEST['merchid_top']) ) {
	      $this->merchid = $_REQUEST['merchid_top'];
	    }
	    if ( isset($_REQUEST['imwidth_top']) ) {
	      $this->imwidth = $_REQUEST['imwidth_top'];
	    }
	  } else if ( isset($_REQUEST['filter_bottom']) ) {
	    if ( isset($_REQUEST['merchid_bottom']) ) {
	      $this->merchid = $_REQUEST['merchid_bottom'];
	    }
	    if ( isset($_REQUEST['imwidth_bottom']) ) {
	      $this->imwidth = $_REQUEST['imwidth_bottom'];
	    }
	  }
	  /* Build where clause */
	  global $wpdb;
	  if ( $this->merchid != '' || $this->imwidth != -1 ) {
	    $wclause = ''; $and = '';
	    if ($this->merchid != '') {
	      $wclause = $wpdb->prepare(' MerchantID = %s',$this->merchid);
	      $and = ' && ';
	    }
	    if ($this->imwidth != -1) {
	      $wclause = $wpdb->prepare($wclause . $and . 
					' ImageWidth = %d',$this->imwidth);
	    }
	    $where = ' where ' . $wclause . ' ';
	    $wand  = ' && ' . $wclause . ' ';
	  } else {
	    $where = ' ';
	    $wand  = ' ';
	  }
	  if ( isset($_REQUEST['action']) && $_REQUEST['action'] != -1 ) {
	    $theaction = $_REQUEST['action'];
	  } else if ( isset($_REQUEST['action2']) && $_REQUEST['action2'] != -1 ) {
	    $theaction = $_REQUEST['action2'];
	  } else if (isset($_REQUEST['zeroall_top']) || 
		     isset($_REQUEST['zeroall_bottom'])) {
	    $theaction = 'zeroall';
	  } else {
	    $theaction = 'none';
	  }
	  switch ($theaction) {
	    case 'zero':
		if ( isset($_REQUEST['id']) ) {
		  GAN_Database::zero_GAN_AD_STAT($_REQUEST['id']);
		} else {
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    GAN_Database::zero_GAN_AD_STAT($theitem);
		  }
		}
		break;
	    case 'zeroall':
		GAN_Database::zero_GAN_AD_STATS($where);
		break;
	  }
	  $all_items = GAN_Database::get_GAN_AD_VIEW_data($where,'OBJECT');
	  $screen = get_current_screen();
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::prepare_items: screen = ".print_r($screen,true)."\n");
	  $option = str_replace( '-', '_', $screen->id . '_per_page' );
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::prepare_items: option = $option\n");
	  $per_page = $this->get_items_per_page( $option );
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::prepare_items: per_page = $per_page\n");

	  $total_items = count($all_items);
	  $this->set_pagination_args( array (
		'total_items' => $total_items,
		'per_page'    => $per_page ));
	  $total_pages = $this->get_pagination_arg( 'total_pages' );
	  $pagenum = $this->get_pagenum();
	  if ($pagenum < 1) {
	    $pagenum = 1;
	  } else if ($pagenum > $total_pages && $total_pages > 0) {
	    $pagenum = $total_pages;
	  }
	  $start = ($pagenum-1)*$per_page;
  	  $this->items = array_slice( $all_items,$start,$per_page );
	}
	function check_permissions() {
	  if (!current_user_can('manage_options')) {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	}
	function get_bulk_actions() {
	  return array ('zero' => 'Zero');
	}
	function extra_tablenav( $which ) {
	  if ($which == 'top') {
	    ?><input type="hidden" name="merchid" value="<?php echo $this->merchid; ?>" />
	      <input type="hidden" name="imwidth" value="<?php echo $this->imwidth; ?>" /><?php
	  }
	  ?><div class="alignleft actions"><?php
	  GAN_Database::merchdropdown($this->merchid,'merchid_'.$which);
	  echo '&nbsp;';
	  GAN_Database::imwidthdropdown($this->imwidth,'imwidth_'.$which);
	  echo '&nbsp;';
	  submit_button(__( 'Filter','gan'), 'secondary', 'filter_'.$which, 
			false, array( 'id' => 'post-query-submit') );
	  echo ' ';
	  submit_button(__( 'Zero All','gan'), 'secondary', 
			'zeroall_'.$which, false, 
			array( 'id' => 'post-query-submit') );
	  ?>&nbsp;<a href="<?php echo add_query_arg(array('mode' => 'ad',
							  'merchid' => $this->merchid,
							  'imwidth' => $this->imwidth),
					GAN_PLUGIN_URL.'/GAN_ExportStats.php');
			?>" class="button-primary" ><?php 
		_e('Download CSV','gan'); ?></a></div><?php
	}
	function get_column_info() {
	  if ( isset($this->_column_headers) ) {return $this->_column_headers;}
	  $this->_column_headers = 
		array( $this->get_columns(), 
		       array(), 
		       $this->get_sortable_columns() );
	  return $this->_column_headers;
	}
	function column_cb ($item) {
	  return '<input type="checkbox" name="checked[]" value="'.$item->id.'" />';
	}
	function column_MerchantID($item) {
	  return GAN_Database::get_merch_name($item->MerchantID);
	}
	function column_LinkID($item) {
	  return GAN_Database::get_link_id($item->adid);
	}
	function column_LinkName($item) {
	  echo GAN_Database::get_link_name($item->adid);
	  ?><br /><a href="<?php echo add_query_arg(array('action' => 'zero',
						    'id' => $item->id )); 
		?>"><?php _e('Zero','gan'); ?></a><?php
	  return '';
	}
	function column_ImageWidth($item) {
	  return $item->ImageWidth;
	}
	function column_EndDate($item) {
	  return mysql2date('F j, Y',$item->EndDate);
	}
	function column_Impressions($item) {
	  return $item->Impressions;
	}
	function column_LastRunDate($item) {
	  return mysql2date('F j, Y',$item->LastRunDate);
	}
	function column_default($item, $column_name) {
	  return apply_filters( 'manage_items_custom_column','',$column_name,$item->id);
	}
}
