<?php

class GAN_DB_List_Table extends WP_List_Table {
	var $row_actions = array();
	var $viewmode = 'add';
	var $viewid   = 0;
	var $viewitem;

	var $merchid = '';
	var $imwidth = -1;
	function GAN_DB_List_Table() {
		parent::WP_List_Table( array ('items') );
	}
	function set_row_actions($racts) { $this->row_actions = $racts; }
	function get_columns() {
		return array (
			'cb' => '<input type="checkbox" />',
			'MerchantID' => __('Advertiser','gan'),
			'LinkID' => __('Link ID','gan'),
			'LinkName' => __('Link Name','gan'),
			'ImageWidth' => __('Image Width','gan'),
			'StartDate' => __('Start Date','gan'),
			'EndDate' => __('End Date','gan'),
			'Enabled' => __('Enabled?','gan'));
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
	  $this->check_permissions();
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::prepare_items _REQUEST is ".print_r($_REQUEST,true)."\n");
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
	  } else if (isset($_REQUEST['enableall_top']) || 
		     isset($_REQUEST['enableall_bottom'])) {
	    $theaction = 'enableall';
	  } else if (isset($_REQUEST['deleteexpired_top']) || 
		     isset($_REQUEST['deleteexpired_bottom'])) {
	    $theaction = 'deleteexpired';
	  } else {
	    $theaction = 'none';
	  }
	  switch ($theaction) {
	    case 'delete':
		if ( isset($_REQUEST['id']) ) {
		  GAN_Database::delete_ad_by_id($_REQUEST['id']);
		} else {
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    GAN_Database::delete_ad_by_id($theitem);
		  }
		}
		break;
	    case 'enabletoggle':
		if ( isset($_REQUEST['id']) ) {
		  GAN_Database::toggle_enabled($_REQUEST['id']);
		} else {
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    GAN_Database::toggle_enabled($theitem);
		  }
		}
		break;
	    case 'enableall':
		GAN_Database::enableall($where);
		break;
	    case 'deleteexpired':
		GAN_Database::deleteexpired($wand);
		break;
	  }
	  $all_items = GAN_Database::get_GAN_data($where,'OBJECT');
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
	  return array ('delete' => __('Delete','gan'), 
			'enabletoggle' => __('Toggle Enabled','gan') );
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
	  submit_button(__( 'Enable All','gan'), 'secondary', 
			'enableall_'.$which, false, 
			array( 'id' => 'post-query-submit') );
	  echo ' ';
	  submit_button(__( 'Delete Expired','gan'), 'secondary', 
			'deleteexpired_'.$which, false, 
			array( 'id' => 'post-query-submit') );

	  echo '</div>';
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
	  return $item->LinkID;
	}
	function column_LinkName($item) {
	  echo $item->LinkName;
	  echo '<br />';
	  $paged = $this->get_pagenum();
	  $option = str_replace( '-', '_', 
				get_current_screen()->id . '_per_page' );
	  $per_page = $this->get_pagination_arg('per_page');
	  foreach ($this->row_actions as $label => $url) {
	    ?><a href="<?php echo add_query_arg( 
				array('merchid' => $this->merchid, 
				      'imwidth' => $this->imwidth, 
				      'paged'   => $paged,
				      'screen-options-apply' => 'Apply',
				      'wp_screen_options[option]' => $option,
				      'wp_screen_options[value]' => $per_page,
				      'id' => $item->id ), $url); 
			?>"><?php echo $label; ?></a>&nbsp;<?php
	  }
	  return '';
	}
	function column_ImageWidth($item) {
	  return $item->ImageWidth;
	}
	function column_StartDate($item) {
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::column_StartDate(".print_r($item,true).")\n");
	  /*return mysql2date('F j, Y',$item->StartDate.' 00:00:00');*/
	  return $item->StartDate;
	}
	function column_EndDate($item) {
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::column_EndDate(".print_r($item,true).")\n");
	  /*return mysql2date('F j, Y',$item->EndDate.' 00:00:00');*/
	  return $item->EndDate;
	}
	function column_Enabled($item) {
	  if ($item->Enabled) {
	    return __('Yes', 'gan');
	  } else {
	    return __('No', 'gan');
	  }
	}
	function column_default($item, $column_name) {
	  return apply_filters( 'manage_items_custom_column','',$column_name,$item->id);
	}
	/* Add/View/Edit page */
	function prepare_one_item() {
	  $this->check_permissions();
	  //file_put_contents("php://stderr","*** GAN_DB_List_Table::prepare_items _REQUEST is ".print_r($_REQUEST,true)."\n");
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
	    case 'add': return __('Add Element to the GAN Database','gan');
	    case 'edit': return __('Edit Element in the GAN Database','gan');
	    case 'view': return __('View Element in the GAN Database','gan');
	  }
	}
	function display_one_item_form($returnURL) {
	  if ( isset($_REQUEST['merchid']) ) {
	    ?><input type="hidden" name="merchid" value="<?php echo $_REQUEST['merchid'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['imwidth']) ) {
	    ?><input type="hidden" name="imwidth" value="<?php echo $_REQUEST['imwidth'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['paged']) ) {
	    ?><input type="hidden" name="paged" value="<?php echo $_REQUEST['paged'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['screen-options-apply']) ) {
	    ?><input type="hidden" name="screen-options-apply" value="<?php echo $_REQUEST['screen-options-apply'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['wp_screen_options']['option']) ) {
	    ?><input type="hidden" name="wp_screen_options[option]" value="<?php echo $_REQUEST['wp_screen_options']['option'] ?>" /><?php
	  }
	  if ( isset($_REQUEST['wp_screen_options']['value']) ) {
	    ?><input type="hidden" name="wp_screen_options[value]" value="<?php echo $_REQUEST['wp_screen_options']['value'] ?>" /><?php
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
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> />
	    </tr>
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
	  if ( isset($_FILES['gan-tsv-file']) ) {
	    $fp = fopen($_FILES['gan-tsv-file']['tmp_name'], 'r');
	    $count = 0;
	    $message .= '<p>';
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
	      if (GAN_Database::find_ad_by_LinkID($LinkID) != 0) {
		$message .= sprintf(__('Duplicate Link ID $s (%s). Ad not inserted into database.','gan'),$LinkID,$LinkName);
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

	function display_bulk_upload_form($returnURL) {
	  ?><label for="gan-tsv-file"><?php _e('Select TSV File:','gan'); ?></label><input type='file' name="gan-tsv-file" size="40" /></p>
	    <p><input type="submit" name="bulkadd" class="button-primary" value="<?php _e('Upload file','gan'); ?>" />
		<a href="<?php echo $returnURL; ?>" class="button-primary"><?php _e('Return','gan'); ?></a>
	    </p><?php
	}
}
