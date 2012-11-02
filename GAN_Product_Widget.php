<?php
/* Product widget: display product ads */

/* Load our constants */
require_once(dirname(__FILE__) . "/GAN_Constants.php");

class GAN_Product_Widget extends WP_Widget {
    /* Initialize ourselves */
    function GAN_Product_Widget() {
	$widget_ops = array( 'classname' => 'deepwoodsganprods',
			     'description' =>
				 __('Display Google Affiliate Network products','gan') );
	/* Widget control settings. */
	$control_ops = array( 'width' => 300, 'height' => 350, 
			      'id_base' => 'deepwoodsganprod-widget' );
	/* Create the widget. */
	$this->WP_Widget( 'deepwoodsganprod-widget', __('GAN Product Widget','gan'), $widget_ops, $control_ops );
     }

       /**
	 * How to display the widget on the screen. (Iframe version)
	 */

     function widget( $args, $instance ) {
	extract( $args );

	/* Before widget (defined by themes). */
	echo $before_widget;

	$ifwidth='';
	$ifheight='';

	if ( $instance['ulid'] == 'GANleader' ) {
	     $ifwidth=' width="100%" ';
	}
	if ( isset($instance['ifwidth']) && $instance['ifwidth'] > 0) {
	     $ifwidth=' width="'.$instance['ifwidth'].'" ';
	}
	if ( isset($instance['ifheight']) && $instance['ifheight'] > 0) {
	     $ifheight=' height="'.$instance['ifheight'].'" ';
	}

	/* Create the iframe.  The ads will be served by GAN_Server. */
	echo '<iframe scrolling="auto" frameborder="0" vspace="0" '.
			'marginheight="0" marginwidth="0" hspace="0" '.
			'allowtransparency="true" class="'.
			$instance['ulid'].'" '.
		'src="'.add_query_arg(
			array('ulid' => $instance['ulid'],
			      'target' => $instance['target'],
			      'merchid' => $instance['merchid'],
			      'namepat' => $instance['namepat'],
			      'catpat' => $instance['catpat'],
			      'brandpat' => $instance['brandpat'],
			      'descrpat' => $instance['descrpat'],
			      'products' => 'yes'),
			GAN_PLUGIN_URL.'/GAN_Server.php').'" '.
		'frameborder="0" '.$ifwidth.$ifheight.'></iframe>';

	/* After widget (defined by themes). */
	echo $after_widget;
    }

    static function shortcode ($atts, $content=null, $code="") {
      extract( shortcode_atts ( array(
	'orientation' => 'horizontal',
	'ifwidth' => '',
	'ifheight' => '',
	'target' => 'same',
	'merchid' => '',
	'namepat' => '',
	'catpat' => '',
	'descrpat' => '',
	'brandpat' => ''), $atts ) );

	  switch ($orientation) {
	    case 'horizontal': $ulid = 'GANleader'; break;
	    case 'vertical': 
	    default:	   $ulid = 'GANright'; break;
	  }
	  switch ($target) {
	    case 'new' : $thetarget='_blank'; break;
	    case 'same': 
	    default:     $thetarget='_top'; break;
	  }
	  $framew=''; $frameh='';
	  if ( $ulid == 'GANleader' ) {
	    $framew=' width="100%" ';
	  }
	  if ($ifwidth > 0) {
	    $framew=' width="'.$ifwidth.'" ';
	  }
	  if ($ifheight > 0) {
	    $frameh=' height="'.$ifheight.'" ';
	  }
	  $frameattrs=$framew.$frameh;
	  $frameattrs=$framew.$frameh;
	  $result  = '<iframe scrolling="auto" frameborder="0" vspace="0" '.
			      'marginheight="0" marginwidth="0" hspace="0" '.
			      'allowtransparency="true" class="'.$ulid.'" '.
			'src="'.add_query_arg(
				array('ulid' => $ulid, 
					'target' => $thetarget,
					'merchid' => $merchid,
					'namepat' => $namepat,
					'catpat' => $catpat,
					'brandpat' => $brandpat,
					'descrpat' => $descrpat,
					'products' => 'yes'),
				GAN_PLUGIN_URL.'/GAN_Server.php').'" '.
			'frameborder="0" '.$frameattrs.'></iframe>';
	  return $result;
	}
        /**
         * Update the widget settings.
         */
        function update( $new_instance, $old_instance ) {
                $instance = $old_instance;

                $instance['ulid'] = $new_instance['ulid'];
		$instance['namepat'] = $new_instance['namepat'];
		$instance['catpat'] = $new_instance['catpat'];
		$instance['brandpat'] = $new_instance['brandpat'];
		$instance['descrpat'] = $new_instance['descrpat'];
                $instance['ifwidth'] = $new_instance['ifwidth'];
                $instance['ifheight'] = $new_instance['ifheight'];
		$instance['target'] = $new_instance['target'];
		$instance['merchid'] = $new_instance['merchid'];

                return $instance;
        }

        /**
         * Displays the widget settings controls on the widget panel.
         * Make use of the get_field_id() and get_field_name() function
         * when creating your form elements. This handles the confusing stuff.
         */
        function form( $instance ) {

            /* Set up some default widget settings. */
            $defaults = array( 'ulid' => 'GANleader',
				'ifwidth' => 468, 'ifheight' => 150,
				'target' => '_top',
				'namepat' => '',
				'catpat' => '',
				'brandpat' => '',
				'descrpat' => '',
				'merchid' => '' );
            $instance = wp_parse_args( (array) $instance, $defaults ); ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'ulid' ); ?>"><?php _e('Orientation:','gan'); ?></label>
                <select id="<?php echo $this->get_field_id( 'ulid' ); ?>" 
                        name="<?php echo $this->get_field_name( 'ulid' ); ?>" 
                        class="widefat" style="width:100%;">
                    <option value="GANright"  <?php if ( 'GANright' == $instance['ulid'] ) echo 'selected="selected"'; ?>><?php _e('vertical','gan'); ?></option>
                    <option value="GANleader" <?php if ( 'GANleader' == $instance['ulid'] ) echo 'selected="selected"'; ?>><?php _e('horizontal','gan'); ?></option>
                </select>
            </p>
	    <p>
		<?php GAN_Database::merchdropdown($instance['merchid'],$this->get_field_name( 'merchid' ), $this->get_field_id( 'merchid' ) ); ?>
	    </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php _e('Target:','gan'); ?></label>
                <select id="<?php echo $this->get_field_id( 'target' ); ?>" 
                        name="<?php echo $this->get_field_name( 'target' ); ?>" 
                        class="widefat" style="width:100%;">
                    <option value="_top"  <?php if ( '_top' == $instance['target'] ) echo 'selected="selected"'; ?>><?php _e('Same Window','gan'); ?></option>
                    <option value="_blank" <?php if ( '_blank' == $instance['target'] ) echo 'selected="selected"'; ?>><?php _e('New Window or Tab','gan'); ?></option>
                </select>
            </p>
	    <p>
		<label for="<?php echo $this->get_field_id( 'namepat' ); ?>"><?php _e('Name Pattern','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'namepat' ); ?>"
			value="<?php echo $instance['namepat']; ?>"
			name="<?php echo $this->get_field_name( 'namepat' ); ?>"
			style="width:100%;" />
	    </p>
	    <p>
		<label for="<?php echo $this->get_field_id( 'catpat' ); ?>"><?php _e('Category Pattern','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'catpat' ); ?>"
			value="<?php echo $instance['catpat']; ?>"
			name="<?php echo $this->get_field_name( 'catpat' ); ?>"
			style="width:100%;" />
	    </p>
	    <p>
		<label for="<?php echo $this->get_field_id( 'brandpat' ); ?>"><?php _e('Brand Pattern','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'brandpat' ); ?>"
			value="<?php echo $instance['brandpat']; ?>"
			name="<?php echo $this->get_field_name( 'brandpat' ); ?>"
			style="width:100%;" />
	    </p>
	    <p>
		<label for="<?php echo $this->get_field_id( 'descrpat' ); ?>"><?php _e('Description Pattern','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'descrpat' ); ?>"
			value="<?php echo $instance['descrpat']; ?>"
			name="<?php echo $this->get_field_name( 'descrpat' ); ?>"
			style="width:100%;" />
	    </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'ifwidth' ); ?>"><?php _e('Ad frame width:','gan'); ?></label>
                <input id="<?php echo $this->get_field_id( 'ifwidth' ); ?>" 
                        value="<?php echo $instance['ifwidth']; ?>"
                        name="<?php echo $this->get_field_name( 'ifwidth' ); ?>"
                        style="width:100%;" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'ifheight' ); ?>"><?php _e('Ad frame height:','gan'); ?></label>
                <input id="<?php echo $this->get_field_id( 'ifheight' ); ?>" 
                        value="<?php echo $instance['ifheight']; ?>"
                        name="<?php echo $this->get_field_name( 'ifheight' ); ?>"
                        style="width:100%;" />
            </p>
            <?php
        }
}






