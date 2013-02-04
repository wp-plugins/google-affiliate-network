<?php

/*
 * GAN Merchant Stats List_Table: list of merchant stats in the database.
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
 * Class to display and manage GAN Merchant Stats
 */

class GAN_Merch_Stats_List_Table extends WP_List_Table {

  var $per_page_screen_option;

  static function my_screen_option() {return 'gan_merch_stats_per_page';}

  function __construct($screen_id) {
    /* Add screen option: links per page. */
    $this->per_page_screen_option =
       new GAN_Per_Page_Screen_Option($screen_id,
		GAN_Merch_Stats_List_Table::my_screen_option(),'Merchant Statistics');

    //Set parent defaults
    parent::__construct( array ('singular' => 'Merchant Statistic',	// One thing
				'plural'   => 'Merchant Statistics',  // Multiple things
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
  function column_MerchantID_wide($item) {
    $actions = array(   
	'zero' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'],
						  'action' => 'zero',
						  'id' => $item->id ),
						admin_url('admin.php')).'">'.
			__('Zero','gan')."</a>"
	);
    return GAN_Database::get_merch_name($item->MerchantID).
		$this->row_actions($actions);
  }
  function column_Impressions($item) {
    return $item->Impressions;
  }
  function column_LastRunDate($item) {
    return mysql2date('F j, Y',$item->LastRunDate);
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
    $merch_statistics = GAN_Database::merch_statistics();
?><table class="ganstats_table" width="100%">
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
<?php
  }
  function get_columns() {
    return array (
	'cb' => '<input type="checkbox" />',
	'MerchantID_wide' => __('Advertiser','gan'),
	'Impressions' => __('Imp.','gan'),
	'LastRunDate' => __('Last View','gan'));
  }			
  function get_sortable_columns() {
    $sortable =  array(
      'Impressions' => array('Impressions',false),
      'LastRunDate' => array('LastRunDate',false) );
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
    ?><div class="alignleft actions"><?php
    submit_button(__( 'Zero All','gan'), 'secondary', 
			'zeroall_'.$which, false, 
			array( 'id' => 'post-query-submit') );
    ?>&nbsp;<a href="<?php echo add_query_arg(array('mode' => 'merch',
						    'merchid' => $this->merchid),
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
  function check_permissions() {
    if (!current_user_can('manage_options')) {
      wp_die( __('You do not have sufficient permissions to access this page.','gan') );
    }
  }
  function process_bulk_action() {
    $action = $this->current_action();
    switch ($action) {
      case 'zero':
	     if ( isset($_REQUEST['checked']) && !empty($_REQUEST['checked'])) {
	       foreach ( $_REQUEST['checked'] as $theitem ) {
		 GAN_Database::zero_GAN_MERCH_STAT($theitem);
	       }
	     } else if ( isset($_REQUEST['id']) ) {
	       GAN_Database::zero_GAN_MERCH_STAT($_REQUEST['id']);
	     }
	     break;
      case 'zeroall':
	     GAN_Database::zero_GAN_MERCH_STATS($where);
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

    // Process bulk action, if any
    $this->process_bulk_action();

    $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'Impressions';
    $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';

    $all_items = GAN_Database::get_GAN_MERCH_STATS_data('','OBJECT',$orderby,$order);
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

