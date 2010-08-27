<?php
/**
 * Property Default Template for Single Property View
 *
 * Overwrite by creating your own in the theme directory called either:
 * property.php
 * or add the property type to the end to customize further, example:
 * property-building.php or property-floorplan.php, etc.
 *
 * By default the system will look for file with property type suffix first, 
 * if none found, will default to: property.php
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.3
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

global $wp_properties;
$map_image_type = $wp_properties[configuration][single_property_view][map_image_type];

?>

<?php get_header(); ?>
<?php the_post(); ?>

   <script type="text/javascript">
		jQuery(document).ready(function() {
 
			jQuery("a.fancybox_image, .gallery-item a").fancybox({
				'transitionIn'	:	'elastic',
				'transitionOut'	:	'elastic',
				'speedIn'		:	600, 
				'speedOut'		:	200, 
				'overlayShow'	:	false
			});

			initialize();
		});
	
	 
		
	function initialize() {
		<?php if($coords = WPP_F::get_coordinates()): ?>
		var myLatlng = new google.maps.LatLng(<?php echo $coords[latitude]; ?>,<?php echo $coords[longitude]; ?>);
		var myOptions = {
		  zoom: <?php echo (!empty($wp_properties[configuration][gm_zoom_level]) ? $wp_properties[configuration][gm_zoom_level] : 13); ?>,
		  center: myLatlng,
		  mapTypeId: google.maps.MapTypeId.ROADMAP
		}

		var map = new google.maps.Map(document.getElementById("property_map"), myOptions);
		
		var infowindow = new google.maps.InfoWindow({
			content: '<table cellpadding=0 cellspacing=0><tr><td width="110"><img style="margin:0;padding:0;" src="<?php echo $post->images[$map_image_type];?>" alt="<?php echo $post->post_title;?>" /></td><td width="200" valign="top"><b><?php echo $post->{$wp_properties['configuration']['address_attribute']}; ?></b><br /><br /><a target="_blank" href="http://maps.google.com/maps?gl=us&daddr=<?php echo str_replace(' ','+', $post->{$wp_properties['configuration']['address_attribute']}); ?>">Get Directions</a></td></tr></table>'
		});

	
	   var marker = new google.maps.Marker({
			position: myLatlng,
			map: map,
			title: '<?php echo $post->post_title; ?>'
		});
 			infowindow.open(map,marker);
 
		<?php endif; ?>
	}

  </script>
  	
	
	<div id="container" class="<?php echo (!empty($post->property_type) ? $post->property_type . "_container" : "");?>">
		<div id="content" role="main" class="property_content">
			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		
			
			<div class="building_title_wrapper">
				<h1 class="property-title entry-title"><?php the_title(); ?></h1>
				<h3 class="entry-subtitle"><?php the_tagline(); ?></h3>
			</div>
		
			<?php property_slideshow(); ?>
   
	
			<div class="entry-content">
				<?php the_content(); ?>
				
				<div id="property_stats" class="overview_stats">
				
				<dt class="wpp_stat_dt_location">Address</dt>
				<dd class="wpp_stat_dd_location"><?php echo $post->display_address; ?></dd>

			
				<?php draw_stats("exclude={$wp_properties['configuration']['address_attribute']}"); ?>
				</div>
				
				
				<?php if(get_features('type=property_feature&format=count')):  ?>
				<div class="features_list">
				<h2>Features</h2>
				<ul class="clearfix">
				<?php get_features('type=property_feature&format=list&links=false'); ?>
				</ul>
				</div>
				<?php endif; ?>

				<?php if(get_features('type=community_feature&format=count')):  ?>
				<div class="features_list">
				<h2>Community Features</h2>
				<ul class="clearfix">
				<?php get_features('type=community_feature&format=list&links=false'); ?>
				</ul>
				</div>
				<?php endif; ?>

				<?php if(is_array($wp_properties[property_meta])): ?>
				<?php foreach($wp_properties[property_meta] as $meta_slug => $meta_title): 
					if(empty($post->$meta_slug) || $meta_slug == 'tagline')
						continue;
				?>
					<h2><?php echo $meta_title; ?></h2>
					<p><?php echo $post->$meta_slug; ?></p>
				<?php endforeach; ?>
				<?php endif; ?>

				<?php if(WPP_F::get_coordinates()): ?>
					<div id="property_map" style="width:100%; height:450px"></div>
				<?php endif; ?>
				
				<?php if(class_exists('WPP_Inquiry')): ?>
					<h2>Interested?</h2>
					<?php WPP_Inquiry::contact_form(); ?>
				<?php endif; ?>
					
				 
				<?php if($post->post_parent): ?>
					<a href="<?php echo $post->parent_link; ?>">Return to building page.</a>
				<?php endif; ?>
					 
			</div><!-- .entry-content -->
		</div><!-- #post-## -->

		</div><!-- #content -->
	</div><!-- #container -->	


<?php
	// Primary property-type sidebar.
	if ( is_active_sidebar( "wpp_sidebar_" . $post->property_type ) ) : ?>

		<div id="primary" class="widget-area <?php echo "wpp_sidebar_" . $post->property_type; ?>" role="complementary">
			<ul class="xoxo">
				<?php dynamic_sidebar( "wpp_sidebar_" . $post->property_type ); ?>
			</ul>
		</div><!-- #primary .widget-area -->

<?php endif; ?>

 
 <?php get_footer(); ?>
