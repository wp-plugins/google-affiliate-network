<?php

/*
 * GAN Link Stats List_Table: list of ad link stats in the database.
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
 * Class to display and manage GAN Link Ad Stats
 */

class GAN_Link_Stats_List_Table extends WP_List_Table {
  var $merchid = '';
  var $imsize = -1;
  var $stats_where = '';

  var $per_page_screen_option;

  static function my_screen_option() {return 'gan_link_stats_per_page';}

  function __construct($screen_id) {
    /* Add screen option: links per page. */
    $this->per_page_screen_option =
       new GAN_Per_Page_Screen_Option($screen_id,
		GAN_Link_Stats_List_Table::my_screen_option(),'Link Statistics');

    //Set parent defaults
    parent::__construct( array ('singular' => 'Link Statistic',	// One thing
				'plural'   => 'Link Statistics',  // Multiple things
				'ajax'     => false     // AJAX?
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
    return GAN_Database::get_link_id($item->adid);
  }
  /* Link name column -- this is where the links live, 
   * so it is handled special. */
  function column_LinkName($item) {
    // Build row actions
    $actions = array(
	'zero' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'],
						  'action' => 'zero',
						  'merchid' => $this->merchid,
						  'imsize' => $this->imsize,
						  'id' => $item->id),
						admin_url('admin.php')).'">'.
			__('Zero','gan')."</a>"
	);
    return GAN_Database::get_link_name($item->adid).$this->row_actions($actions);
  }
  function column_ImageSize($item) {
    if ($item->ImageWidth == 0 && $item->ImageHeight == 0) {
      return 'text';
    } else {
      return $item->ImageWidth.'x'.$item->ImageHeight;
    }
  }
  function column_EndDate($item) {
    return mysql2date('F j, Y',$item->EndDate);
    //return $item->EndDate;
  }
  function column_Impressions($item) {
    return $item->Impressions;
  }
  function column_LastRunDate($item) {
    return mysql2date('F j, Y',$item->LastRunDate);
    //return $item->LastRunDate;
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
	</div>
<?php
  }
  function top_statistics() {
    $ad_statistics = GAN_Database::ad_statistics($this->stats_where);
?><br clear="all" /><table class="ganstats_table" width="100%">
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
<?php
  }
  function get_columns() {
    return array (
	'cb' => '<input type="checkbox" />',
	'MerchantID' => __('Advertiser','gan'),
	'LinkID' => __('Link ID','gan'),
	'LinkName' => __('Link Name','gan'),
	'EndDate' => __('End Date','gan'),
	'ImageSize' => __('Image Size','gan'),
	'Impressions' => __('Imp.','gan'),
	'LastRunDate' => __('Last View','gan'));
  }
  function get_sortable_columns() {
    $sortable =  array(
      'EndDate' => array('EndDate',false),
      'Impressions' => array('Impressions',false),
      'LinkID' => array('LinkID',false),
      'ImageSize' => array('ImageWidth, ImageHeight',false),
      'LastRunDate' => array('LastRunDate',false) );
    if (GAN_Database::database_version() >= 3.0) {
      $sortable['LinkName'] = array('LinkName',false);
    }
    return $sortable;
  }
  function get_column_info() {
    if ( isset($this->_column_headers) ) {return $this->_column_headers;}
    $columns = $this->get_columns( );
    $hidden = array();

    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array( $columns, $hidden, $sortable );

    return $this->_column_headers;
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
    submit_button(__( 'Zero All','gan'), 'secondary', 
			'zeroall_'.$which, false, 
			array( 'id' => 'post-query-submit') );
    ?>&nbsp;<a href="<?php echo add_query_arg(array('mode' => 'ad',
						    'merchid' => $this->merchid,
						    'imsize' => $this->imsize),
					GAN_PLUGIN_URL.'/GAN_ExportStats.php');
			?>" class="button-primary" ><?php 
		_e('Download CSV','gan'); ?></a></div><?php
  }
  function get_bulk_actions() {
    return array ('zero' => __('Zero','gan'));
  }
  function current_action() {
    if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
	return $_REQUEST['action'];

    if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
	return $_REQUEST['action2'];
    // Extra nav actions
    if (isset($_REQUEST['zeroall_top']) || 
	isset($_REQUEST['zeroall_bottom'])) {
      return 'zeroall';
    }
    return false;
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
    if ( $this->merchid != '' || $this->imsize != -1 || 
	(isset($_REQUEST['s']) && GAN_Database::database_version() >= 3.0) ) {
      $wclause = ''; $and = '';
      if ( isset($_REQUEST['s']) && GAN_Database::database_version() >= 3.0) {
        $wclause = $wpdb->prepare(' LinkName LIKE %s ','%'.$_REQUEST['s'].'%');
        $and = ' && ';
      }
      if ($this->merchid != '') {
	$wclause = $wpdb->prepare($wclause . $and .' MerchantID = %s',$this->merchid);
	$and = ' && ';
      }
      if ($this->imsize != -1) {
	$size = explode('x',$this->imsize);
	$wclause = $wpdb->prepare($wclause . $and . 
				  ' ImageWidth = %d && ImageHeight = %d',
				  $size[0],$size[1]);
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
  function process_bulk_action($where,$wand) {
    $action = $this->current_action();
    switch ($action) {
      case 'zero':
	     if ( isset($_REQUEST['checked']) && !empty($_REQUEST['checked'])) {
	       foreach ( $_REQUEST['checked'] as $theitem ) {
		 GAN_Database::zero_GAN_AD_STAT($theitem);
	       }
	     } else if ( isset($_REQUEST['id']) ) {
	       GAN_Database::zero_GAN_AD_STAT($_REQUEST['id']);
	     }
	     break;
      case 'zeroall':
	     GAN_Database::zero_GAN_AD_STATS($where);
	     break;
    }
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
    $this->stats_where = $this->process_filters_and_bulk_action();

    $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'Impressions, EndDate';
    $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';

    $all_items = GAN_Database::get_GAN_AD_VIEW_data($this->stats_where,'OBJECT',$orderby,$order);
    $current_page = $this->get_pagenum();
    $total_items = count($all_items);
    $data = array_slice($all_items,(($current_page-1)*$per_page),$per_page);
    $this->items = $data;
    $this->set_pagination_args( array (
		'total_items' => $total_items,
		'per_page'    => $per_page,
		'total_pages' => ceil($total_items/$per_page) ));
  }
}    
