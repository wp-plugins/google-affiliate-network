<?php
/** Load WordPress Administration Bootstrap */
if(file_exists('../../../wp-load.php')) {
	require_once("../../../wp-load.php");
} else if(file_exists('../../wp-load.php')) {
	require_once("../../wp-load.php");
} else if(file_exists('../wp-load.php')) {
	require_once("../wp-load.php");
} else if(file_exists('wp-load.php')) {
	require_once("wp-load.php");
} else if(file_exists('../../../../wp-load.php')) {
	require_once("../../../../wp-load.php");
} else if(file_exists('../../../../wp-load.php')) {
	require_once("../../../../wp-load.php");
} else {

	if(file_exists('../../../wp-config.php')) {
		require_once("../../../wp-config.php");
	} else if(file_exists('../../wp-config.php')) {
		require_once("../../wp-config.php");
	} else if(file_exists('../wp-config.php')) {
		require_once("../wp-config.php");
	} else if(file_exists('wp-config.php')) {
		require_once("wp-config.php");
	} else if(file_exists('../../../../wp-config.php')) {
		require_once("../../../../wp-config.php");
	} else if(file_exists('../../../../wp-config.php')) {
		require_once("../../../../wp-config.php");
	} else {
		echo '<p>Failed to load bootstrap.</p>';
		exit;
	}

}

global $wp_db_version;
if ($wp_db_version < 8201) {
	// Pre 2.6 compatibility (BY Stephen Rider)
	if ( ! defined( 'WP_CONTENT_URL' ) ) {
		if ( defined( 'WP_SITEURL' ) ) define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
		else define( 'WP_CONTENT_URL', get_option( 'url' ) . '/wp-content' );
	}
	if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
	if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

require_once(ABSPATH.'wp-admin/admin.php');

/* Load constants and database */
require_once(dirname(__FILE__) . "/GAN_Constants.php");
require_once(dirname(__FILE__) . "/GAN_Database.php");

load_plugin_textdomain('gan',GAN_PLUGIN_URL.'/languages/',
		       basename(GAN_DIR).'/languages/');

################################################################################
// REPLACE ADMIN URL
################################################################################

if (function_exists('admin_url')) {
	wp_admin_css_color('classic', __('Blue'), admin_url("css/colors-classic.css"), array('#073447', '#21759B', '#EAF3FA', '#BBD8E7'));
	wp_admin_css_color('fresh', __('Gray'), admin_url("css/colors-fresh.css"), array('#464646', '#6D6D6D', '#F1F1F1', '#DFDFDF'));
} else {
	wp_admin_css_color('classic', __('Blue'), get_bloginfo('wpurl').'/wp-admin/css/colors-classic.css', array('#073447', '#21759B', '#EAF3FA', '#BBD8E7'));
	wp_admin_css_color('fresh', __('Gray'), get_bloginfo('wpurl').'/wp-admin/css/colors-fresh.css', array('#464646', '#6D6D6D', '#F1F1F1', '#DFDFDF'));
}

wp_enqueue_script( 'common' );
wp_enqueue_script( 'jquery-color' );

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<title><?php bloginfo('name') ?> &rsaquo; <?php _e('Uploads'); ?> &#8212; <?php _e('WordPress'); ?></title>
	<?php
		wp_enqueue_style( 'global' );
		wp_enqueue_style( 'wp-admin' );
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'media' );
	?>
	<script type="text/javascript">
	//<![CDATA[
		function addLoadEvent(func) {if ( typeof wpOnload!='function'){wpOnload=func;}else{ var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}}
	//]]>
	</script>
	<?php
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
	if ( isset($content_func) && is_string($content_func) )
		do_action( "admin_head_{$content_func}" );
	?>
</head>
<body id="media-upload">
<form style="clear:both" class="media-upload-form">
	<p><label for="maxads"><?php _e('Max ads:','gan'); ?></label>
	   <input id="maxads" value="4" name="maxads" style="width:75%;" />
	</p>
	<p><?php GAN_Database::imsizedropdown('0x0'); ?></p>
	<p><label for="orientation"><?php _e('Orientation:','gan'); ?></label>
	   <select id="orientation" name="orientation" class="widefat" style="width:75%;">
		<option value="vertical" selected="selected"><?php _e('vertical','gan'); ?></option>
		<option value="horizontal"><?php _e('horizontal','gan'); ?></option>
	   </select>
	</p><label for="target"><?php _e('Target:','gan'); ?></label>
	    <select id="target" name="target" class="widefat" style="width:75%;">
		<option value="same" selected="selected"><?php _e('Same Window','gan'); ?></option>
		<option value="new"><?php _e('New Window or Tab','gan'); ?></option>
	   </select>
	<p><label for="ifwidth"><?php _e('Ad frame width:','gan'); ?></label>
	   <input id="ifwidth" name="ifwidth" value="" style="width:75%;" />
	</p>
	<p><label for="ifheight"><?php _e('Ad frame height:','gan'); ?></label>
	   <input id="ifheight" name="ifheight" value="" style="width:75%;" />
	</p>
	<p>
	<a href="#" class="button insertad"><?php _e('Insert Ad Unit','gan'); ?></a>
	</p>
</form>
<script type="text/javascript">
	/* <![CDATA[ */
	function changeupdate() {
	  var maxads = parseInt(document.getElementById('maxads').value);
	  var imsize = document.getElementById('gan-imsize').value.split('x');
	  var imwidth = parseInt(imsize[0]); var imheight = parseInt(imsize[1]);
	  var orientation = document.getElementById('orientation').value;
	  var ifwidth;
	  var ifheight;
	  switch (orientation) {
	    case "vertical":
	      if (imwidth == 0) ifwidth = 120;
	      else ifwidth = imwidth;
	      document.getElementById('ifwidth').value = ifwidth;
	      if (imheight == 0) ifheight = 60*maxads;
	      else ifheight = (imheight+3)*maxads;
	      document.getElementById('ifheight').value = ifheight;
	      break;
	    case "horizontal":
	      if (imwidth == 0) ifwidth = 120*maxads;
	      else ifwidth = imwidth*maxads;
	      document.getElementById('ifwidth').value = ifwidth;
	      if (imheight == 0) ifheight = 60;
	      else ifheight = imheight+3;
	      document.getElementById('ifheight').value = ifheight;
	      break;
	  }
	  return false;
	}
	document.getElementById('maxads').onchange = changeupdate;
	document.getElementById('gan-imsize').onchange = changeupdate;
	document.getElementById('orientation').onchange = changeupdate;
	jQuery('.insertad').click(function(){
		var win = window.dialogArguments || opener || parent || top;
		var maxads = parseInt(jQuery('#maxads').val());
		var imsize = jQuery('#gan-imsize').val().split('x');
		var imwidth = parseInt(imsize[0]); var imheight = parseInt(imsize[1]);
		var orientation = jQuery('#orientation').val();
		var target = jQuery('#target').val();
		var ifwidth = jQuery('#ifwidth').val();
		var ifheight = jQuery('#ifheight').val();
		if (ifwidth == '' && ifheight == '' && 
			imwidth != 0 && imheight != 0) {
		  switch (orientation) {
		    case "vertical":
		      ifwidth = imwidth;
		      ifheight = (imheight+3)*maxads;
		      break;
		    case "horizontal":
		      ifwidth = imwidth*maxads;
		      ifheight = imheight+3;
		      break;
		  }
		}
		if (imwidth == 0 && imheight == 0) {
		   win.send_to_editor('[GAN_Text orientation="'+orientation+
					'" maxads="'+maxads+
					'" ifwidth="'+ifwidth+
					'" ifheight="'+ifheight+
					'" target="'+target+'"]');
		} else {
		   win.send_to_editor('[GAN_Image orientation="'+orientation+
					'" maxads="'+maxads+
					'" width="'+imwidth+
					'" height="'+imheight+
					'" ifwidth="'+ifwidth+
					'" ifheight="'+ifheight+
					'" target="'+target+'"]');
		}
	});
	/* ]]> */
</script>
</body>
</html>

