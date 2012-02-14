<?php

class Prod_Stats_List_Table extends WP_List_Table {
	var $merchid = '';
	var $stats_where = '';
	function __construct() {
		if ( method_exists('WP_List_Table','WP_List_Table')) {
			parent::WP_List_Table( array ('items') );
		} else {
			parent::__construct( array ('items') );
		}
	}
	function get_columns() {
		return array (
			'cb' => '<input type="checkbox" />',
			'MerchantID' => __('Advertiser','gan'),
			'Product_Name' => __('Product Name','gan'),
			'Product_Brand' => __('Product Brand','gan'),
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
	  /* Build where clause */
	  global $wpdb;
	  if ( $this->merchid != '' ) {
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
	  } else if (isset($_REQUEST['zeroall_top']) || 
		     isset($_REQUEST['zeroall_bottom'])) {
	    $theaction = 'zeroall';
	  } else {
	    $theaction = 'none';
	  }
	  switch ($theaction) {
	    case 'zero':
		if ( isset($_REQUEST['id']) ) {
		  GAN_Database::zero_GAN_PRODUCTS_STAT($_REQUEST['id']);
		} else {
		  foreach ( $_REQUEST['checked'] as $theitem ) {
		    GAN_Database::zero_GAN_PRODUCTS_STAT($theitem);
		  }
		}
		break;
	    case 'zeroall':
		GAN_Database::zero_GAN_PRODUCTS_STATS($where);
		break;
	  }
	  $orderby = isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'Product_Name';
	  if ( empty( $orderby ) ) $orderby = 'Impressions';
	  $order = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'ASC';
	  if ( empty( $order ) ) $order = 'ASC';
	  $all_items = GAN_Database::get_GAN_Product_Stats_data($where,'OBJECT',$orderby,$order);
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
	  $this->stats_where = $where;
	}
	function check_permissions() {
	  if (!current_user_can('manage_options')) {
	    wp_die( __('You do not have sufficient permissions to access this page.','gan') );
	  }
	}
	function get_bulk_actions() {
	  return array ('zero' => 'Zero');
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
		       'Product_Brand' => 'Product_Brand',
		       'Impressions' => 'Impressions');
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
	  ?><br /><a href="<?php echo add_query_arg(array('action' => 'zero',
						    'id' => $item->id )); 
		?>"><?php _e('Zero','gan'); ?></a><?php
	  return '';
	}
	function column_Impressions($item) {
	  return $item->Impressions;
	}
	function column_LastRunDate($item) {
	  return mysql2date('F j, Y',$item->LastRunDate);
	  //return $item->LastRunDate;
	}
	function column_default($item, $column_name) {
	  return apply_filters( 'manage_items_custom_column','',$column_name,$item->id);
	}
	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
			$this->top_statistics();
		}
?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<div class="alignleft actions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div><?php
	}
	function top_statistics() {
		$product_statistics = GAN_Database::product_statistics($this->stats_where);
?><table class="ganstats_table" width="100%">
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
<?php
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
	  submit_button(__( 'Zero All','gan'), 'secondary', 
			'zeroall_'.$which, false, 
			array( 'id' => 'post-query-submit') );
	  ?>&nbsp;<a href="<?php echo add_query_arg(array('mode' => 'product',
							  'merchid' => $this->merchid),
					GAN_PLUGIN_URL.'/GAN_ExportStats.php');
			?>" class="button-primary" ><?php 
		_e('Download CSV','gan'); ?></a></div><?php
	}
}

