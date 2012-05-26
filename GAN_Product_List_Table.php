<?php
class GAN_Product_List_Table extends WP_List_Table {
	var $row_actions = array();
	var $viewmode = 'add';
	var $viewid   = 0;
	var $viewitem;

	var $merchid = '';
	function __construct()
	{
		if ( method_exists('WP_List_Table','WP_List_Table')) {
			parent::WP_List_Table( array ('items') );
		} else {
			parent::__construct( array ('items') );
		}
	}
	function set_row_actions($racts) { $this->row_actions = $racts; }
	function get_columns() {
		return array (
			'cb' => '<input type="checkbox" />',
			'MerchantID' => __('Advertiser','gan'),
			'Product_Name' => __('Product Name','gan'),
			'Product_Brand' => __('Product Brand','gan'),
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
	function check_permissions() {
	  if (!current_user_can('manage_options')) {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	}
	function prepare_items() {
	  $this->check_permissions();
	  if ( isset($_REQUEST['merchid']) ) {
	    $this->merchid = $_REQUEST['merchid'];
	  } else {
	    $this->merchid = '';
	  }
	  if ( isset($_REQUEST['filter_top']) ) {
	    if ( isset($_REQUEST['merchid_top']) ) {
	      $this->merchid = $_REQUEST['merchid_top'];
	    }
	  } else if ( isset($_REQUEST['filter_bottom']) ) {
	    if ( isset($_REQUEST['merchid_bottom']) ) {
	      $this->merchid = $_REQUEST['merchid_bottom'];
	    }
	  }
	  if ( isset($_REQUEST['delmerch_top']) ) {
	    if ( isset($_REQUEST['modmerchid_top']) && 
			$_REQUEST['modmerchid_top'] != '') {
	      GAN_Database::delete_products_by_merchantID($_REQUEST['modmerchid_top']);
	    }
	  } else if ( isset($_REQUEST['delmerch_bottom']) ) {
	    if ( isset($_REQUEST['modmerchid_bottom']) && 
			$_REQUEST['modmerchid_bottom'] != '') {
	      GAN_Database::delete_products_by_merchantID($_REQUEST['modmerchid_bottom']);
	    }
	  } else if ( isset($_REQUEST['disablemerch_top']) ) {
	    	    if ( isset($_REQUEST['modmerchid_top']) && 
			$_REQUEST['modmerchid_top'] != '') {
	      GAN_Database::disable_products_by_merchantID($_REQUEST['modmerchid_top']);
	    }
	  } else if ( isset($_REQUEST['disablemerch_bottom']) ) {
	    	    if ( isset($_REQUEST['modmerchid_bottom']) && 
			$_REQUEST['modmerchid_bottom'] != '') {
	      GAN_Database::disable_products_by_merchantID($_REQUEST['modmerchid_bottom']);
	    }
	  } else if ( isset($_REQUEST['enablemerch_top']) ) {
	    	    if ( isset($_REQUEST['modmerchid_top']) && 
			$_REQUEST['modmerchid_top'] != '') {
	      GAN_Database::enable_products_by_merchantID($_REQUEST['modmerchid_top']);
	    }
	  } else if ( isset($_REQUEST['enablemerch_bottom']) ) {
	    	    if ( isset($_REQUEST['modmerchid_bottom']) && 
			$_REQUEST['modmerchid_bottom'] != '') {
	      GAN_Database::enable_products_by_merchantID($_REQUEST['modmerchid_bottom']);
	    }
	  }
	  /* Build where clause */
	  global $wpdb;
	  if ( $this->merchid != '') {
	    $wclause = ''; $and = '';
	    if ($this->merchid != '') {
	      $wclause = $wpdb->prepare(' MerchantID = %s',$this->merchid);
	      $and = ' && ';
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
	  } else {
	    $theaction = 'none';
	  }
	  switch ($theaction) {
	    case 'delete':
		if ( isset($_REQUEST['id']) ) {
		  GAN_Database::delete_product_by_id($_REQUEST['id']);
		} else {
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    GAN_Database::delete_product_by_id($theitem);
		  }
		}
		break;
	    case 'enabletoggle':
		if ( isset($_REQUEST['id']) ) {
		  GAN_Database::toggle_enabled_prod($_REQUEST['id']);
		} else {
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    GAN_Database::toggle_enabled_prod($theitem);
		  }
		}
		break;
	    case 'enableall':
		GAN_Database::enableall_products($where);
		break;
	    case 'deleteexpired':
		GAN_Database::deleteexpired($wand);
		break;
	  }
	  $orderby = isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'Product_Name';
	  if ( empty( $orderby ) ) $orderby = 'Product_Name';
	  $order = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'ASC';
	  if ( empty( $order ) ) $order = 'ASC';
	  $all_items = GAN_Database::get_GAN_Product_data($where,'OBJECT',$orderby,$order);
	  $screen = get_current_screen();
	  $option = str_replace( '-', '_', $screen->id . '_per_page' );
	  $per_page = $this->get_items_per_page( $option );

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
	function get_bulk_actions() {
	  return array ('delete' => __('Delete','gan'), 
			'enabletoggle' => __('Toggle Enabled','gan') );
	}
	function extra_tablenav( $which ) {
	  if ($which == 'top') {
	    ?><input type="hidden" name="merchid" value="<?php echo $this->merchid; ?>" /><?php
	  }
	  ?><div class="alignleft actions"><?php
	  GAN_Database::merchdropdown($this->merchid,'merchid_'.$which);
	  echo '&nbsp;';
	  submit_button(__( 'Filter','gan'), 'secondary', 'filter_'.$which, 
			false, array( 'id' => 'post-query-submit') );
	  echo ' ';
	  submit_button(__( 'Enable All','gan'), 'secondary', 
			'enableall_'.$which, false, 
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
	  $hidden = get_hidden_columns( $screen );

	  $_sortable = apply_filters( "manage_{$screen->id}_sortable_columns", $this->get_sortable_columns() );

	  $sortable = array();
	  foreach ( $_sortable as $id => $data ) {
	    if ( empty( $data ) ) continue;

            $data = (array) $data;
	    if ( !isset( $data[1] ) ) $data[1] = false;

	    $sortable[$id] = $data;
	  }

	  $this->_column_headers = array( $columns, $hidden, $sortable );

	  return $this->_column_headers;
	}
	function get_sortable_columns() {
	  return array('Product_Name' => 'Product_Name',
		       'Product_Brand' => 'Product_Brand');
	}
	function column_cb ($item) {
	  return '<input type="checkbox" name="checked[]" value="'.$item->id.'" />';
	}
	function column_MerchantID($item) {
	  return GAN_Database::get_merch_name($item->MerchantID);
	}
	function column_Product_Brand($item) {
	  return $item->Product_Brand;
	}
	function column_Product_Name($item) {
	  echo $item->Product_Name;
	  echo '<br />';
	  $paged = $this->get_pagenum();
	  $option = str_replace( '-', '_', 
				get_current_screen()->id . '_per_page' );
	  $per_page = $this->get_pagination_arg('per_page');
	  foreach ($this->row_actions as $label => $url) {
	    ?><a href="<?php echo add_query_arg( 
				array('merchid' => $this->merchid, 
				      'paged'   => $paged,
				      'screen-options-apply' => 'Apply',
				      'wp_screen_options[option]' => $option,
				      'wp_screen_options[value]' => $per_page,
				      'id' => $item->id ), $url); 
			?>"><?php echo $label; ?></a>&nbsp;<?php
	  }
	  return '';
	}
	function column_Enabled($item) {
	  if ($item->enabled) {
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
	  if ( isset($_REQUEST['merchid']) ) {
	    $this->merchid = $_REQUEST['merchid'];
	  } else {
	    $this->merchid = '';
	  }
	  $message = '';
	  if ( isset($_REQUEST['addprod']) ) {
	    $message = $this->checkiteminform(0);
	    $item    = $this->getitemfromform();
	    if ($message == '') {
	      $newid = GAN_Database::insert_GAN_Product($item->Advertiser,
							$item->Product_Name,
							$item->Product_Descr,
							$item->Tracking_URL,
							$item->Creative_URL,
							$item->Product_Category,
							$item->Product_Brand,
							$item->Product_UPC,
							$item->Price,
							$item->MerchantID,
							$item->enabled);
	      $message = '<p>'.sprintf(__('%s inserted with id %d.','gan'),
					$item->Product_Name,$newid).'</p>';
	      $this->viewmode = 'edit';
	      $this->viewid   = $newid;
	      $this->viewitem = $item;
	    } else {
	      $this->viewmode = 'add';
	      $this->viewid   = 0;
	      $this->viewitem = $item;
	    }
	  } else if ( isset($_REQUEST['updateprod']) && isset($_REQUEST['id']) ) {
	    $message = $this->checkiteminform($_REQUEST['id']);
	    $item    = $this->getitemfromform();
	    $item->id = $_REQUEST['id'];
	    if ($message == '') {
	      GAN_Database::update_GAN_Product($item->id,$item->Advertiser,
					       $item->Product_Name,
					       $item->Product_Descr,
					       $item->Tracking_URL,
					       $item->Creative_URL,
					       $item->Product_Category,
					       $item->Product_Brand,
					       $item->Product_UPC,$item->Price,
					       $item->MerchantID,
					       $item->enabled);
	      $message = '<p>'.sprintf(__('%s updated.','gan'),
					$item->Product_Name).'</p>';
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
	      $this->viewitem = GAN_Database::get_product($this->viewid,'OBJECT');
	    } else {
	      $this->viewitem = GAN_Database::get_blank_product();
	    }	    
	  }
	  return $message;
	}
	function checkiteminform($id) {
	  $result = '';
	  if ( empty($_REQUEST['Advertiser']) ) {
	    $result .= '<p>'.__('Advertiser missing.','gan').'</p>';
	  }
	  if ( empty($_REQUEST['Product_Name']) ) {
	    $result .= '<p>'.__('Product Name missing.','gan').'</p>';
	  }
	  if ( empty($_REQUEST['Tracking_URL']) ) {
	    $result .= '<p>'.__('Tracking URL missing.','gan').'</p>';
	  }
	  if ( empty($_REQUEST['Creative_URL']) ) {
	    $result .= '<p>'.__('Creative URL missing.','gan').'</p>';
	  }
	  if ( empty($_REQUEST['MerchantID']) ) {
	    $result .= '<p>'.__('Merchant ID missing.','gan').'</p>';
	  }
	  if ( empty($_REQUEST['Price']) ) {
	    $_REQUEST['Price'] = 0;
	  } else if ( !preg_match('/^[[:digit:].]*$/',$_REQUEST['Price']) ) {
	    $result .= '<p>'.__('Price should be a number.','gan').'</p>';
	  }
	  return $result;
	}
	function getitemfromform() {
	  $itemary = array();
	  foreach ( array('Advertiser','Product_Name','Product_Descr',
			  'Tracking_URL','Creative_URL','Product_Category',
			  'Product_Brand','Product_UPC','Price','MerchantID',
			  'enabled') as $field ) {
	    $itemary[$field] = $_REQUEST[$field];
	  }
	  return (object) $itemary;
	}
	function add_item_icon() {
	  switch ($this->viewmode) {
	    case 'add': return 'icon-gan-add-prod-db';
	    case 'edit': return 'icon-gan-edit-prod-db';
	    case 'view': return 'icon-gan-view-prod-db';
	  }
	}
	function add_item_h2() {
	  switch ($this->viewmode) {
	    case 'add': return __('Add Product to the GAN Database','gan');
	    case 'edit': return __('Edit Product in the GAN Database','gan');
	    case 'view': return __('View Product in the GAN Database','gan');
	  }
	}
	function display_one_item_form($returnURL) {
	  if ( isset($_REQUEST['merchid']) ) {
	    ?><input type="hidden" name="merchid" value="<?php echo $_REQUEST['merchid'] ?>" /><?php
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
	      <th scope="row"><label for="GAN-Product_Name" style="width:20%;"><?php _e('Product Name:','gan'); ?></label></th>
	      <td><input id="GAN-Product_Name"
			value="<?php echo htmlspecialchars(stripslashes($this->viewitem->Product_Name)); ?>"
			name="Product_Name"
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
		<th scope="row"><label for="GAN-Product_Descr" 
			style="width:20%;top-margin:0;"><?php _e('Product Description:','gan'); ?></label></th>
		<td><textarea id="GAN-Product_Descr" name="Product_Descr" 
			cols="50" rows="5" 
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?>><?php echo stripslashes($this->viewitem->Product_Descr); ?></textarea></td></tr>
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Tracking_URL" style="width:20%;"><?php _e('Tracking URL:','gan'); ?></label></th>
	      <td><input id="GAN-Tracking_URL"
			value="<?php echo stripslashes($this->viewitem->Tracking_URL); ?>"
			name="Tracking_URL"
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Creative_URL" style="width:20%;"><?php _e('Creative URL:','gan'); ?></label></th>
	      <td><input id="GAN-Creative_URL"
			value="<?php echo stripslashes($this->viewitem->Creative_URL); ?>"
			name="Creative_URL"
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Product_Category" style="width:20%;"><?php _e('Product Category:','gan'); ?></label></th>
	      <td><input id="GAN-Product_Category"
			value="<?php echo stripslashes($this->viewitem->Product_Category); ?>"
			name="Product_Category"
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Product_Brand" style="width:20%;"><?php _e('Product Brand:','gan'); ?></label></th>
	      <td><input id="GAN-Product_Brand"
			value="<?php echo stripslashes($this->viewitem->Product_Brand); ?>"
			name="Product_Brand"
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Product_UPC" style="width:20%;"><?php _e('Product UPC:','gan'); ?></label></th>
	      <td><input id="GAN-Product_UPC"
			value="<?php echo stripslashes($this->viewitem->Product_UPC); ?>"
			name="Product_UPC"
			style="width:75%;"<?php if ($this->viewmode == 'view') echo ' readonly="readonly"'; ?> /></td></tr>
	    <tr valign="top">
	      <th scope="row"><label for="GAN-Price" style="width:20%;"><?php _e('Price:','gan'); ?></label></th>
	      <td><input id="GAN-Price"
			value="<?php echo stripslashes($this->viewitem->Price); ?>"
			name="Price"
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
				?><input type="submit" name="addprod" class="button-primary" value="<?php _e('Add Product To Database','gan'); ?>" /><?php
				break;
			case 'edit':
				?><input type="submit" name="updateprod" class="button-primary" value="<?php _e('Update Product In Database','gan'); ?>" /><?php
				break;
		      } ?>
		<a href="<?php echo $returnURL; ?>" class="button-primary"><?php _e('Return','gan'); ?></a>
	  </p><?php
	}
	function process_bulk_upload() {
	  $this->check_permissions();
	  $message = '<p>';
	  if ( isset($_FILES['gan-prod-csv-file']) ) {
	    $fp = fopen($_FILES['gan-prod-csv-file']['tmp_name'], 'r');
	    $sep = ',';
	    $row1 = fgetcsv($fp, 0, $sep);
	    if ($row1 && count($row1) != 13) {
	      $sep = "\t";
	      fseek ( $fp, 0, SEEK_SET);
	      $row1 = fgetcsv($fp, 0, $sep);
	      if (count($row1) != 13) {
		$message .= '<p>'.__('Not a proper CSV or TSV file.','gan').'</p>';
		fclose($fp);
		return $message;
	      }
	    }
	    $count = 0;
	    while (($row = fgetcsv($fp, 0, $sep)) != FALSE) {
	      $Product_Name = $row[0];
	      $Product_Descr = $row[1];
	      $Tracking_URL = $row[2];
	      $Creative_URL = $row[3];
	      $Product_Category = $row[5];
	      $Product_Brand = $row[6];
	      if (count($row) == 13) {
		$Product_UPC = $row[7];
		$Price = preg_replace('/^\$/','',$row[8]);
		$MerchantID = $row[11];
		$Advertiser = $row[12];
	      } else if (count($row) == 12) {
		$Product_UPC = '';
		$Price = preg_replace('/^\$/','',$row[7]);
		$MerchantID = $row[10];
		$Advertiser = $row[11];
	      } else {
		$message .= __('Illformed row skipped.','gan').'<br />';
		?><!-- Bad row is <?php print_r($row); ?> --><?php
		continue;
	      }
	      GAN_Database::insert_GAN_Product($Advertiser,$Product_Name,
					       $Product_Descr,$Tracking_URL,
					       $Creative_URL,$Product_Category,
					       $Product_Brand,$Product_UPC,
					       $Price,$MerchantID,1);
	      $count++;
	    }
	    fclose($fp);
	    $message .= sprintf(__('Inserted %d products into ad database.','gan'),$count).'</p>';
	  }
	  return $message;
	}
	function display_bulk_upload_form($returnURL) {
	  ?><label for="gan-prod-csv-file"><?php _e('Select TSV or CSV Product file:','gan'); ?></label><input type='file' id="gan-prod-csv-file" name="gan-prod-csv-file" size="40" /></p>
	    <p><input type="submit" name="bulkadd" class="button-primary" value="<?php _e('Upload file','gan'); ?>" />
		<a href="<?php echo $returnURL; ?>" class="button-primary"><?php _e('Return','gan'); ?></a>
	    </p><?php
	}
	 
}
