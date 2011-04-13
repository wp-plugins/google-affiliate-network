<?php

/* Text widget: display textual affiliate ads. */


/* Load our constants */
require_once(dirname(__FILE__) . "/GAN_Constants.php");

/* This widget displays text based affiliate ads. */
class GAN_Widget extends WP_Widget {

	/* Initialize ourselves */
        function GAN_Widget() {
                $widget_ops = array( 'classname' => 'deepwoodsgan', 'description' => __('Display Google Affiliate Network links','gan') );

                /* Widget control settings. */
                $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'deepwoodsgan-widget' );

                /* Create the widget. */
                $this->WP_Widget( 'deepwoodsgan-widget', __('GAN Widget','gan'), $widget_ops, $control_ops );
        }

        /**
         * How to display the widget on the screen. (Non iframe version.)
         */
        function XX_widget( $args, $instance ) {
                extract( $args );

                /* Before widget (defined by themes). */
                echo $before_widget;

                /* Extract ads from the database. */
                global $wpdb;
                $maxads = $instance['maxads'];
                //echo "\n<!-- GAN_Widget::widget: \$maxads (1) = " . $maxads . " -->";
                if (empty($maxads)) $maxads = 4;
                //echo "\n<!-- GAN_Widget::widget: \$maxads (2) = " . $maxads . " -->";
		$merchlist = GAN_Database::ordered_merchants(0,0);
		//echo "\n<!-- GAN_Widget::widget: merchlist = ".print_r($merchlist,true)." -->\n";
                /* Display the ads, if any. Display maxads at most. */
                $numads = 0;
                if (! empty($merchlist) ) {
                      echo '<ul id="' . $instance['ulid'] . '">';
                      $loopcount = $maxads;
                      while ($numads < $maxads && $loopcount > 0) {
			//file_put_contents("php://stderr","*** GAN_Widget::widget: loopcount = ".$loopcount."\n");
                        foreach ($merchlist as $merchid) {
                          //echo "\n<!-- GAN_Widget::widget: \$numads = " . $numads . " -->";
			  //echo "\n<!-- GAN_Widget::widget: GANAd is ".print_r($GANAd,true)." -->\n";
			  $adlist = GAN_Database::ordered_ads(0,0,$merchid);
			  //echo "\n<!-- GAN_Widget::widget: adlist = ".print_r($adlist,true)." -->\n";
			  if (empty($adlist)) {continue;}
			  //echo "\n<!-- GAN_Widget::widget: adlist[0] = ".$adlist[0]." -->\n";
			  $GANAd = GAN_Database::get_ad($adlist[0]);
			  GAN_Database::bump_counts($adlist[0]);
                          ?><li><a href="<?php echo $GANAd['ClickserverLink']; 
                          ?>"><?php echo $GANAd['LinkName']; 
                          ?></a> <?php echo $GANAd['MerchandisingText']; 
                          ?></li><?php
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
		echo '<iframe scrolling="auto" class="'.$instance['ulid'].'" '.
			'src="'.add_query_arg(
				array('ulid' => $instance['ulid'],
				      'maxads' => $instance['maxads']),
				GAN_PLUGIN_URL.'/GAN_Server.php').'" '.
			'frameborder="0" '.$ifwidth.$ifheight.'></iframe>';

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
				'ifwidth' => 728, 'ifheight' => 90 );
            $instance = wp_parse_args( (array) $instance, $defaults ); ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'maxads' ); ?>"><?php _e('Max ads:','gan'); ?></label>
                <input id="<?php echo $this->get_field_id( 'maxads' ); ?>" 
                        value="<?php echo $instance['maxads']; ?>"
                        name="<?php echo $this->get_field_name( 'maxads' ); ?>"
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
