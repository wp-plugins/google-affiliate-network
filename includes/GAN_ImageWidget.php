<?php

require_once(dirname(__FILE__) . "/GAN_Constants.php");
require_once(GAN_INCLUDES_DIR . "/GAN_Database.php");


/**
 * GAN Image Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class GAN_ImageWidget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function GAN_ImageWidget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'deepwoodsimggan', 'description' => __('Display Google Affiliate Network Image links','gan') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'deepwoodsgan-image-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'deepwoodsgan-image-widget', __('GAN Image Widget','gan'), $widget_ops, $control_ops );
	}

	/**
	 * How to display the widget on the screen. (IFrame version)
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
		
		echo '<iframe scrolling="no" frameborder="0" vspace="0" '.
			      'marginheight="0" marginwidth="0" hspace="0" '.
			      'allowtransparency="true" class="'.$instance['ulid'].'" '.
			'src="'.add_query_arg(
			array('ulid' => $instance['ulid'],
			      'maxads' => $instance['maxads'],
			      'height' => $instance['height'],
			      'width' => $instance['width'],
			      'target' => $instance['target'],
			      'merchid' => $instance['merchid']),
			GAN_PLUGIN_URL.'/GAN_Server.php').'" '.
			'frameborder="0" '.$ifwidth.$ifheight.'></iframe>';

                /* After widget (defined by themes). */
                echo $after_widget;
        }

	static function shortcode ($atts, $content=null, $code="") {
	  extract( shortcode_atts ( array(
	    'orientation' => 'vertical',
	    'maxads' => 4,
	    'width' => 120, 
	    'height' => 60,
	    'ifwidth' => '',
	    'ifheight' => '',
	    'target' => 'same',
	    'merchid' => ''), $atts ) );
	  
	  switch ($orientation) {
	    case 'horizontal': $ulid = 'GANleader'; break;
	    case 'vertical': 
	    default:           $ulid = 'GANright'; break;
	  }
	  switch ($target) {
	    case 'new' : $thetarget='_blank'; break;
	    case 'same': 
	    default:	 $thetarget='_top'; break;
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
	  $result  = '<iframe scrolling="no" frameborder="0" vspace="0" '.
			      'marginheight="0" marginwidth="0" hspace="0" '.
			      'allowtransparency="true" class="'.$ulid.'" '.
			'src="'.add_query_arg(
				array('ulid' => $ulid, 'maxads' => $maxads,
				      'height' => $height, 'width' => $width,
					'target' => $thetarget,
					'merchid' => $merchid),
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
		//echo "\n<!-- GAN_Widget::widget: \$new_instance['maxads'] = " . $new_instance['maxads'] . " -->";
		$instance['maxads'] = $new_instance['maxads'];
		$instance['height'] = $new_instance['height'];
		$instance['width']  = $new_instance['width'];
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
	    $defaults = array( 'ulid' => 'GANright', 'maxads' => 4, 
				'width' => 120, 'height' => 60,
				'target' => '_top',
				'merchid' => '' );
	    $instance = wp_parse_args( (array) $instance, $defaults ); ?>
	    <p>
		<label for="<?php echo $this->get_field_id( 'maxads' ); ?>"><?php _e('Number of ads:','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'maxads' ); ?>" 
			value="<?php echo $instance['maxads']; ?>"
			name="<?php echo $this->get_field_name( 'maxads' ); ?>"
			style="width:100%;" />
	    </p>
	    <p>
		<label for="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e('Width:','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'width' ); ?>" 
			value="<?php echo $instance['width']; ?>"
			name="<?php echo $this->get_field_name( 'width' ); ?>"
			style="width:100%;" />
	    </p>
	    <p>
		<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e('Height:','gan'); ?></label>
		<input id="<?php echo $this->get_field_id( 'height' ); ?>" 
			value="<?php echo $instance['height']; ?>"
			name="<?php echo $this->get_field_name( 'height' ); ?>"
			style="width:100%;" />
	    </p>
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

