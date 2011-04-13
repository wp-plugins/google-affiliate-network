<?php

require_once(dirname(__FILE__) . "/GAN_Constants.php");


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
		
		echo '<iframe scrolling="auto" class="'.$instance['ulid'].'" '.
			'src="'.add_query_arg(
			array('ulid' => $instance['ulid'],
			      'maxads' => $instance['maxads'],
			      'height' => $instance['height'],
			      'width' => $instance['width']),
			GAN_PLUGIN_URL.'/GAN_Server.php').'" '.
			'frameborder="0" '.$ifwidth.$ifheight.'></iframe>';

                /* After widget (defined by themes). */
                echo $after_widget;
        }

	
	/**
	 * How to display the widget on the screen. (Non IFrame version)
	 */
	function XXXwidget( $args, $instance ) {
		extract( $args );

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Extract ads from the database. */
		global $wpdb;
		$maxads = $instance['maxads'];
		//echo "\n<!-- GAN_Widget::widget: \$maxads (1) = " . $maxads . " -->";
		if (empty($maxads)) $maxads = 4;
		//echo "\n<!-- GAN_Widget::widget: \$maxads (2) = " . $maxads . " -->";
		$merchlist = GAN_Database::ordered_merchants($instance['height'],$instance['width']);
		/* Display the ads, if any. Display maxads at most. */
		$numads = 0;
		if (! empty($merchlist) ) {
		      echo '<ul id="' . $instance['ulid'] . '">';
		      $loopcount = $maxads;
		      while ($numads < $maxads && $loopcount > 0) {
			foreach ($merchlist as $merchid) {
		          //echo "\n<!-- GAN_Widget::widget: \$numads = " . $numads . " -->";
			  $adlist = GAN_Database::ordered_ads($instance['height'],$instance['width'],$merchid);
			  if (empty($adlist)) {continue;}
			  $GANAd = GAN_Database::get_ad($adlist[0]);
			  GAN_Database::bump_counts($adlist[0]);
			  ?><li><a href="<?php echo $GANAd['ClickserverLink']; 
			  ?>"><img src="<?php echo $GANAd['ImageURL']; ?>"
			      width="<?php echo $GANAd['ImageWidth']; ?>"
			      height="<?php echo $GANAd['ImageHeight']; ?>"
			      alt="<?php echo $GANAd['AltText']; ?>" border="0">
			  </a></li><?php
			  $numads++;
			  if ($numads >= $maxads) break;
		        }
			$loopcount--;
		      }
		      ?></ul><?php
		}

		/* After widget (defined by themes). */
		echo $after_widget;
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
				'width' => 120, 'height' => 60 );
	    $instance = wp_parse_args( (array) $instance, $defaults ); ?>
	    <p>
		<label for="<?php echo $this->get_field_id( 'maxads' ); ?>"><?php _e('Max ads:','gan'); ?></label>
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

               
?>
