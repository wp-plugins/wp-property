<?php
/*
Name: Supermap
Class: class_supermap
Version: 1.2
Description: A big map for property overview.
*/

add_action('wpp_init', array('wpp_supermap', 'init'));


if(!class_exists('wpp_supermap')) :
class wpp_supermap {

	function init() {
	
			add_shortcode('supermap', array('wpp_supermap', 'shortcode_supermap'));

	}
	
	
	function shortcode_supermap($atts)  {
		global $wp_properties;

		extract(shortcode_atts(array(
			'type' => 'all'), 
			$atts));

 
			ob_start();	

			// 1. Try template in theme folder
			if(file_exists(TEMPLATEPATH . "/supermap.php")) 
				include TEMPLATEPATH . "/supermap.php";
	 
			
			// 3. Try template in plugin folder
			if(file_exists(WPP_Templates . "/supermap.php")) 
				include WPP_Templates . "/supermap.php";
 		
			$result .= ob_get_contents();
			ob_end_clean();
			
		return $result;
	}

	
	function draw_supermap() { ?>
	
	<div class="wpp_supermap clearfix">
<?php $properties = WPP_F::get_supermap_properties(); ?>

<?php // print_r($properties); ?>
<script>

var locations = [

<?php $count = 0; foreach($properties as  $property): $count++; ?>
	{
		title: "<?php echo $property['post_title']; ?>",
		lat: <?php echo $property['latitude']; ?>,
		lng: <?php echo $property['longitude']; ?>,
		location: "<?php echo $property['location']; ?>",
		bathrooms: "<?php echo $property['bathrooms']; ?>",
		bedrooms: "<?php echo $property['bedrooms']; ?>",
		price: "<?php echo $property['price']; ?>",
		area: "<?php echo $property['area']; ?>",
		link: "<?php echo get_permalink($property['ID']); ?>",
		popup_thumb: '<?php echo $property['sidebar_gallery_thumb']; ?>',
		map_thumb: '<?php echo $property['map_thumb']; ?>'
	}
	<?php if(count($properties) != $count): ?>,<?php endif; ?>
<?php endforeach; ?>
];


jQuery(document).ready(function() {

	init();
	
	jQuery(".wpp_map_link").click(function() {
	
		jQuery(this).parents('ul').children().removeClass('current_page_item');
		jQuery(this).addClass('current_page_item');
		
		var element_id = jQuery(this).attr('id');

		var point_id = parseInt(element_id.replace("wpp_map_property_", ""));

		
		go_to_point(point_id, locations[point_id]);
		return false;
	});
	
});



	
var map, cloud;
var counter = 0;
var markers = [];

function init() {
	var options = {
		zoom: <?php echo (!empty($wp_properties['settings']['google_maps_zoom']) ? $wp_properties['settings']['google_maps_zoom'] : "2"); ?>,
 		center: new google.maps.LatLng(0, 0),
		minZoom: 4,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	}
	
	map = new google.maps.Map(document.getElementById('map_canvas'), options);

	// V3 version of GLatLngBounds
	var bounds = new google.maps.LatLngBounds();

	// Create an empty objects

	
	for (var i in locations) {
		var myLatLng = new google.maps.LatLng(locations[i].lat, locations[i].lng);


		bounds.extend(myLatLng);

		makeMarker(locations[i]);
		map.fitBounds(bounds)
	    

	}
 
 
	 
}

function go_to_point(i, location) {

  var content = '<div style="text-align: center; font-size:14px;">' +
    '<center><b>' + location.title + '</b></center>' +
	'<a href="'+location.link+'"><img width="240" height="180" src="' + location.popup_thumb + '"/></a>' +
	'<div style="margin: auto; text-align: left; width: 240px;" class="linkbutton">' +
	'<b>' + location.location + '</b><br />' +
	'Price: ' + location.price + ' <br />' +
	'' + location.bedrooms + ' Bedrooms <br />' +
	'' + location.bathrooms + ' Bathrooms <br />' +
	'</div>' +
	'<br/></div>';
	
	
	var myLatLng = new google.maps.LatLng(locations[i].lat, locations[i].lng);
	map.setZoom(10);
	//map.zoomIn(myLatLng, 8);
	map.panTo(myLatLng);
	
	
	     var infowindow = new google.maps.InfoWindow({
        content: content,
        maxWidth: 250
    });
	
  
  var marker = new google.maps.Marker({
	map: map, 
	position: new google.maps.LatLng(location.lat, location.lng),
	title: location.title
	});
	
      infowindow.open(map,marker);

	jQuery(".wpp_map_link").click(function() {
		infowindow.close(map,marker);
	});
     
   
	
}
 
 

function makeMarker(location) {

  var content = '<div style="text-align: center; font-size:14px;">' +
    '<center><b>' + location.title + '</b></center>' +
	'<img width="240" height="180" src="' + location.popup_thumb + '"/>' +
	'<div style="margin: auto; text-align: left; width: 240px;" class="linkbutton">' +
	'<b>' + location.location + '</b><br />' +
	'Price: ' + location.price + ' <br />' +
	'' + location.bedrooms + ' Bedrooms <br />' +
	'' + location.bathrooms + ' Bathrooms <br />' +
	'</div>' +
	'<br/></div>';

     
     var infowindow = new google.maps.InfoWindow({
        content: content,
        maxWidth: 250
    });

  
  var marker = new google.maps.Marker({
	map: map, 
	position: new google.maps.LatLng(location.lat, location.lng),
	title: location.title
	});
 
 
	 



}

</script>

<div id="content" style="page type-page hentry">
  <div id="main_content" style="width: 620px; padding-bottom: 5px;">
  <div class="entry-content">
 <div id="map_canvas" style="width:100%; height:600px; float: left;"></div>
 </div>
 </div>
 
 <div style="width: 234px; background: none repeat scroll 0% 0% transparent;" id="right_sidebar">
			
			<div id="primary" class="widget-area">
				<ul class="xoxo">
				<li class="widget-container widget_pages">

 
				<ul>
				<?php $counter = 0; foreach($properties as $property): ?>
				<li id="wpp_map_property_<?php echo $counter; ?>" class="wpp_map_link page_item"><a title="<?php echo $property[post_title]; ?>" href="#"><?php echo $property[post_title]; ?></a></li>
				<?php  $counter++; endforeach; ?>

				</ul>         
 

				</li>			
				</ul>
			</div>
		
		</div>
		
</div>
</div>

<?php } 

}
endif; // Class Exists
?>