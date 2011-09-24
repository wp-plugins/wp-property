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

global $wp_properties, $post, $wp_query;
$map_image_type = $wp_properties['configuration']['single_property_view']['map_image_type'];

// Uncomment to disable fancybox script being loaded on this page
//wp_deregister_script('jquery-fancybox');
//wp_deregister_script('jquery-fancybox-css');
?>

<?php
  $wp_query = apply_filters( 'wpp_pending_template_query', $wp_query, $post );
?>
<?php get_header(); ?>

   <script type="text/javascript">
    var map;
    var marker;
    var infowindow;
    
    jQuery(document).ready(function() {

      initialize();
      
    });
  
   
    
  function initialize() {
    <?php if($coords = WPP_F::get_coordinates()): ?>
    var myLatlng = new google.maps.LatLng(<?php echo $coords['latitude']; ?>,<?php echo $coords['longitude']; ?>);
    var myOptions = {
      zoom: <?php echo (!empty($wp_properties['configuration']['gm_zoom_level']) ? $wp_properties['configuration']['gm_zoom_level'] : 13); ?>,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }

    map = new google.maps.Map(document.getElementById("property_map"), myOptions);
    
    infowindow = new google.maps.InfoWindow({
      content: '<?php echo WPP_F::google_maps_infobox($post); ?>',
      maxWidth: 500
    });
  
     marker = new google.maps.Marker({
      position: myLatlng,
      map: map,
      title: '<?php echo addslashes($post->post_title); ?>'
    });         

    google.maps.event.addListener(infowindow, 'domready', function() {
    document.getElementById('infowindow').parentNode.style.overflow='';
    document.getElementById('infowindow').parentNode.parentNode.style.overflow='';
   });
   
   setTimeout("infowindow.open(map,marker);",1000);
 
    <?php endif; ?>
  }

  </script>
    
  
  <div id="container" class="<?php echo (!empty($post->property_type) ? $post->property_type . "_container" : "");?>">
    <div id="content" role="main" class="property_content">
      <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    
      
      <div class="building_title_wrapper">
        <h1 class="property-title entry-title"><?php the_title(); ?></h1>
        <h3 class="entry-subtitle"><?php the_tagline(); ?> (<?php _e('Pending'); ?>)</h3>
      </div>
 
    
      <div class="entry-content">
        <?php @the_content(); ?>
        
        
        <dl id="property_stats" class="overview_stats">
          <?php if(!empty($post->display_address)): ?>
          <dt class="wpp_stat_dt_location"><?php echo $wp_properties['property_stats'][$wp_properties['configuration']['address_attribute']]; ?></dt>
          <dd class="wpp_stat_dd_location alt"><?php echo $post->display_address; ?>&nbsp;</dd>
          <?php endif; ?>
          <?php @draw_stats("make_link=true&exclude={$wp_properties['configuration']['address_attribute']}"); ?>
        </dl>
        
      <?php if(!empty($wp_properties['taxonomies'])) foreach($wp_properties['taxonomies'] as $tax_slug => $tax_data): ?>
        <?php if(get_features("type=$tax_slug&format=count")):  ?>
        <div class="<?php echo $tax_slug; ?>_list">
        <h2><?php echo $tax_data['label']; ?></h2>
        <ul class="clearfix">
        <?php get_features("type=$tax_slug&format=list&links=true"); ?>
        </ul>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>

 

        <?php if(is_array($wp_properties['property_meta'])): ?>
        <?php foreach($wp_properties['property_meta'] as $meta_slug => $meta_title): 
          if(empty($post->$meta_slug) || $meta_slug == 'tagline')
            continue;
        ?>
          <h2><?php echo $meta_title; ?></h2>
          <p><?php echo  do_shortcode(html_entity_decode($post->$meta_slug)); ?></p>
        <?php endforeach; ?>
        <?php endif; ?>
        

        <?php if(WPP_F::get_coordinates()): ?>
          <div id="property_map" style="width:100%; height:450px"></div>
        <?php endif; ?>
        
        <?php if(class_exists('WPP_Inquiry')): ?>
          <h2><?php _e('Interested?','wpp') ?></h2>
          <?php WPP_Inquiry::contact_form(); ?>
        <?php endif; ?>
          
         
        <?php if($post->post_parent): ?>
          <a href="<?php echo $post->parent_link; ?>"><?php _e('Return to building page.','wpp') ?></a>
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