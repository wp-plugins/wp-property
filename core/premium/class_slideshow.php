<?php
/*
Name: Slideshow
Class: class_slideshow
Version: 2.1
Description: Slideshow feature for WP-Property
*/


add_action('wpp_init', array('wpp_slideshow', 'init'));


class wpp_slideshow {

	function init() {
		global $wp_properties;
		
		if(is_array($wp_properties[image_sizes][slideshow])) {
			$defaults = $wp_properties[image_sizes][slideshow];
			$defaults[thumb_width] = '350';
		} else {
			$defaults = array(
				'width' => 800,
				'height' => 200,
				'thumb_width' => 450
			);
		}
		
		$wp_properties[images][slideshow] = apply_filters('wpp_slideshow', $defaults);
		
		add_action('wpp_admin_menu', array('wpp_slideshow', 'admin_menu'));
		
		add_filter('wpp_settings_nav', array('wpp_slideshow', 'settings_nav'));
		
		add_action('wpp_settings_content_slideshow', array('wpp_slideshow', 'settings_page')); 

	}
	
	function settings_page() {
	
		
		?>
		Slideshow settings
		<?php
	
	}
	
	
	/**
	 * Adds slideshow manu to settings page navigation
	 *
	 *
	 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
	 */	
	function settings_nav($tabs) {
	
		$tabs['slideshow'] = array(
			'slug' => 'slideshow',
			'title' => 'Slideshow'
		);
		
		return $tabs;
	
	}
	
	
	function admin_menu() {
		
		$slideshow_page = add_submenu_page('upload.php', "Slideshow", "Slideshow", 10, 'slideshow',array('wpp_slideshow', 'page')); 

		// Insert Scripts
		add_action('admin_print_scripts-' . $slideshow_page, create_function('', "wp_enqueue_script('jquery-fancybox'); wp_enqueue_style('jquery-fancybox');wp_enqueue_script( 'jquery-ui-sortable'); "));
		// Insert Styles
		add_action('admin_print_styles-' . $slideshow_page, create_function('', "wp_enqueue_style('jquery-fancybox-css');"));

	}


	
	function page() {
		// Get all images that are big enough
		global $wpdb, $wp_properties;

		// Fix values if for some reason nothing is set
		$thumb_width = (!empty($wp_properties[images][slideshow][thumb_width]) ? $wp_properties[images][slideshow][thumb_width] : '300');

		// If updating
 		if(wp_verify_nonce($_REQUEST['_wpnonce'] , 'wpp_update_slideshow') && isset($_POST['slideshow_image_array'])) {

			//fix array
			$string_array = $_POST['slideshow_image_array'];
			$string_array = str_replace('item=', '', $string_array);
			$image_array = explode('&', $string_array);

			update_option('wpp_slideshow_image_array', $image_array);
			
			$updated = "Slideshow selection and order saved.";
		}

		// Get current images
		$current = get_option('wpp_slideshow_image_array');
		$all_images = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_wp_attachment_metadata'");

 
	?>
	<style type="text/css">
	#sortable1, #sortable2 { 
background:none repeat scroll 0 0 #EDEDEF;
border:7px solid #BABABA;
list-style-type:none;
margin:0 10px 0 0;
min-height:300px;
padding:10px;

	}
	.image_block { float: left; margin-right: 20px;}
	.image_block .title {font-size: 1.4em;}
	
	#sortable1 li, #sortable2 li { 
 font-size:1.2em;
margin:0 auto;
padding:0 0 6px;
text-align:center;
width:<?php echo $thumb_width; ?>px;
	

	}	
	
	#sortable1 li img, #sortable2 li img{ 
		border: 1px solid #888888;
		cursor: move;
	}

	#sortable1 {
border:7px solid #DADADA;
min-height:300px;
min-width:<?php echo $thumb_width + 10; ?>px;
	}
	#sortable2 {
background:none repeat scroll 0 0 #F8FFC6;
border:7px solid #D5CD9C;
min-height:300px;
min-width:<?php echo $thumb_width + 10; ?>px;
	}
	
	.wpp_selected_images_title, .wpp_all_images_title {
		display: table;
		padding: 10px;
		font-size: 1.3em;
		font-weight: bold;
 	}
	
	.wpp_selected_images_title {
		background: #D5CD9C;
	}
	
	.wpp_all_images_title {
		background: #DADADA;
	
	}
	</style>
	<script type="text/javascript">
	jQuery(function() {
 		jQuery("#sortable1, #sortable2").sortable({
			connectWith: '.connectedSortable',
			update: function() {
            	var order = jQuery('#sortable2').sortable('serialize', {key: 'item'});
            	jQuery("#slideshow_image_array").val(order);
			}

			}).disableSelection();
					
	jQuery(".image_block a").click(function(e){
		    e.preventDefault();

	});
	
	jQuery(".image_block a").dblclick(function(){
		jQuery(this).fancybox({
 
			'transitionIn'	:	'elastic',
			'transitionOut'	:	'elastic',
			'speedIn'		:	200, 
			'speedOut'		:	200, 
			'overlayShow'	:	false
		
		});
		return false;
		
	});
	});
	</script>



<div class="wrap">
	<h2>Slideshow</h2>
	
	<?php if($updated): ?>
		<div class="updated fade">
			<p><?php echo $updated; ?></p>
		</div>
	
	<?php endif; ?>
	<form action="<?php admin_url('upload.php?page=slideshow'); ?>" method="post">

	<div class="wpp_box">
		<div class="wpp_box_header">
		<strong>WP-Property Slideshow</strong>
		<p>This slieshow can be integrated into your front-end pages by either using the shortcode, or pasting PHP code into your theme.</p>
		</div>
		<div class="wpp_box_content">
			<p>Drag images from selection on the left to the selection on the right, and then click save.</p>
			<p>This list gets all images from your media library that are over <?php echo $wp_properties[images][slideshow][width]; ?> pixels wide and <?php echo $wp_properties[images][slideshow][height]; ?> pixels tall.</p>
			<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpp_update_slideshow'); ?>" />		 
			<input type="hidden" name="slideshow_image_array" id="slideshow_image_array" value="" />
		</div>

		<div class="wpp_box_footer">
				<input type="submit" value="Save Selection and Order" accesskey="p" tabindex="4" id="publish" class="button-primary" name="save">

		</div>
	</div>
	</form>

			 
		 



 <?php 
	foreach($all_images as $image) {
		$sizes = unserialize($image->meta_value);
		if($sizes[width] > $wp_properties[images][slideshow][width] && $sizes[height] > $wp_properties[images][slideshow][height])
			$good_images[] = $image->post_id;
	}

 	$thumb_height = ($wp_properties[images][slideshow][height] / $wp_properties[images][slideshow][width] * $thumb_width );

?>
 <div class="image_block">
<span class="wpp_all_images_title"> All Images:</span
<ul id="sortable1" class="connectedSortable" style="width: <?php echo $thumb_width; ?>px">
<?php
 foreach($good_images as $image) {
	?>
	
 	<?php 
	$image_info = wp_get_attachment_image_src($image, 'slideshow'); 
	$image_url = $image_info[0];
	
	// skip if current
	if(is_array($current)) 
		if(in_array($image, $current))
			continue;
	?>
	
	<li id="image_<?php echo $image; ?>">
		<a href="<?php echo $image_url; ?>" "grouped_elements" rel="group1">
			<img src='<?php echo $image_url; ?>' style='width: <?php echo $thumb_width; ?>px; height: <?php echo $thumb_height; ?>px;' />
		</a>
	</li>
 	
	<?php
}
 ?>
 </ul>
 </div>
 
 
 <div class="image_block">
<span class="wpp_selected_images_title"> Slideshow Images:</span
 <ul id="sortable2" class="connectedSortable">
<?php
	if(is_array($current)):
	foreach($current as $curr_id):
	
	unset($image_info);
	unset($curr_image_url);
	$image_info = wp_get_attachment_image_src($curr_id, 'slideshow'); 
	$curr_image_url = $image_info[0];
		
?>
	<li id="image_<?php echo $curr_id; ?>">
		<a href="<?php echo $image_url; ?>" "grouped_elements" rel="group1">
			<img src='<?php echo $curr_image_url; ?>' style='width: <?php echo $thumb_width; ?>px; height: <?php echo $thumb_height; ?>px;' />
		</a>
	</li>
			
<?php
	endforeach; endif; ?>
</ul>
 
 </div>
 </div>
 
 
 <?php }
 
 
 
	function get_property_slideshow_images($id = false) {
	global $post_id;

	if(!$id)
		$id = $post_id;
 	$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC', 'orderby' => 'menu_order ID') );


	if ($attachments) {

		foreach ($attachments as $attachment) {
			$image_obj = wp_get_attachment_image_src($attachment->ID, 'slideshow');
			$return[] = $image_obj[0];
		}

		return $return;
	}

	}

	function display_property_slideshow($image_array) {



		if(!is_array($image_array))
			return;
			
		?>
        <div id="slider_wrapper">
		<div id="slideshow">
		<?php foreach($image_array as $url): ?>
			<img src="<?php echo $url; ?>" alt="" />
		<?php endforeach; ?>
		</div><!-- End of Slider -->
		<img src="<?php echo get_bloginfo('template_url'); ?>/images/slideshow_frame.png" alt="" class="slideshow_frame" />
		<div id="navigation"></div>
        </div>
		<?php

	}
	
	
}

?>