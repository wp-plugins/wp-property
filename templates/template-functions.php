<?php
/**
 * Functions to be used in templates.  Overrided by anything in template functions.php
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.4
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

	
	
if(!function_exists('prepare_property_for_display')):
	/**
	 * Runs all filters through property variables
	 *
	 * @since 1.4
	 *
 	 */
	 function prepare_property_for_display($property) {
 
		if(empty($property))
			return;
			
		foreach($property as $meta_key => $attribute)
 			$property[$meta_key] = apply_filters("wpp_stat_filter_$meta_key",$attribute);

		
		// Go through children properties
		if(is_array($property[children]))
			foreach($property[children] as $child => $child_data)  
				$property[children][$child] = prepare_property_for_display($child_data);
		
		
	
		return $property;	
	}
	
endif;
	
if(!function_exists('property_slideshow')):
	/**
	 * Returns property slideshow images, or single image if plugin not installed
	 *
	 * @since 1.0
	 *
 	 */
	 function property_slideshow($args = "") {
		global $wp_properties, $post;
		
		
		
		
		$defaults = array('force_single' => false, 'return' => false);
		$args = wp_parse_args( $args, $defaults );
		
		if($wp_properties[configuration][property_overview][display_slideshow] == 'false')
			return;
			
			
		ob_start();
			
			// Display slideshow if premium plugin exists and the property isn't set to hide slideshow
			if($wp_properties[plugins][slideshow][status] == 'enabled' && !$post->disable_slideshow) {
				wpp_slideshow::display_property_slideshow(wpp_slideshow::get_property_slideshow_images($post->ID));
			} else {
				// Get slideshow image type for featured image
				
				if(!empty($post->slideshow)) {
					echo "<a href='{$post->featured_image_url}' class='fancybox_image'>";
					echo "<img src='{$post->slideshow}' alt='{$post->featured_image_title}' />";
					echo "</a>";
				}
			}
				
			
			
			
		$content = ob_get_contents();
		ob_end_clean();
		
		if(empty($content))
			return false;
		
		if($return)
			return $content;
			
		echo $content;
		
		
	}
endif; // property_slideshow


/*
	Extends get_post by dumping all metadata into array
*/
if(!function_exists('get_property')):
	function get_property($id, $args = "") {
		if($id && is_numeric($id))
			return WPP_F::get_property($id, $args);
	}
endif;

if(!function_exists('the_tagline')):
 	function the_tagline($before = '', $after = '', $echo = true) {
		global $post;

		$content = $post->tagline;


		if ( strlen($content) == 0 )
			return;

		$content = $before . $content . $after;

		if ( $echo )
			echo $content;
		else
			return $content;

	}
endif;

if(!function_exists('get_features')):
	function get_features($args = '') {
		global $post;

		$defaults = array('type' => 'property_feature', 'format' => 'comma', 'links' => true);
		$args = wp_parse_args( $args, $defaults );



		$features = get_the_terms($post->ID, $args[type]);

		$features_html = array();

		if($features) {
		foreach ($features as $feature)

			if($links)
				array_push($features_html, '<a href="' . get_term_link($feature->slug, $args[type]) . '">' . $feature->name . '</a>');
			else
				array_push($features_html, $feature->name);

			if($args[format] == 'comma')
				echo implode($features_html, ", ");

			if($args[format] == 'array')
				return $features_html;

			if($args[format] == 'count')
				return (count($features) > 0 ? count($features) : false);

			if($args[format] == 'list')
				echo "<li>" . implode($features_html, "</li><li>") . "</li>";

		}


	}
endif;

if(!function_exists('draw_stats')):
	function draw_stats($args = false){
		global $wp_properties, $post;
		$defaults = array( );
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

 
		$stats = WPP_F::get_stat_values_and_labels($post, $args);


		if(!$stats)
			return;

 		?>
	 
			<?php foreach($stats as $label => $value):

			$labels_to_keys = array_flip($wp_properties['property_stats']);

			if(empty($value))
				return;
			
			$tag = $labels_to_keys[$label];
			
			
			?>
			<dt class="wpp_stat_dt_<?php echo $tag; ?>"><?php echo $label; ?></dt>
			<dd class="wpp_stat_dd_<?php echo $tag; ?>"><?php echo apply_filters("wpp_stat_filter_$tag", $value, $post); ?>&nbsp;</dd>
 			<?php endforeach; ?>
 
		<?php
	}
endif;


if(!function_exists('draw_featured_properties')):
	function draw_featured_properties() {
	?>


	    <?php
		$featured_array = WPP_F::get_properties("featured=true&property_type=all");

	    if(is_array($featured_array)):
			foreach($featured_array as $featured):

				unset($this_property);
				$this_property = WPP_F::get_property($featured->ID);

				?>
				<div class="apartment_entry clearfix" style="clear:both;margin-bottom:15px;">

					<a href="<?php echo $this_property[permalink]; ?>">
						<img src="<?php echo $this_property[sidebar_gallery_thumb];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property[post_title], $this_property[location], $this_property[price]); ?>" />
					</a>

					<ul class="sidebar_properties">
						<li><span><?php _e('Price:','wpp'); ?></span> $ <?php echo  $this_property[price]; ?></li>
						<li><span><?php _e('Bed(s):','wpp'); ?></span> <?php echo  $this_property[bedrooms]; ?></li>
						<li><span><?php _e('Bath(s):','wpp'); ?></span> <?php echo  $this_property[bathrooms]; ?></li>
						<li><span><?php _e('Square Ft:','wpp'); ?></span> <?php echo  $this_property[area]; ?></li>
					</ul>
			   </div>
			  <?php
			endforeach;
		endif;


	}
endif;

	/**
	 * Draws search form
	 *
	 *
	 * @return array|$wp_properties
	 * @since 0.57
	 *
 	 */
if(!function_exists('draw_property_search_form')):
	function draw_property_search_form($search_attributes = false, $searchable_property_types = false, $per_page = false) {
        global $wp_properties;
  
        if(!$search_attributes)
            return;

        $search_values = WPP_F::get_search_values();
        ?>
 
        <form action="<?php echo  UD_F::base_url($wp_properties['configuration']['base_slug']); ?>" method="post">
            
		<?php
		if(is_array($searchable_property_types)) foreach($searchable_property_types as $this_property) {
            echo '<input type="hidden" name="wpp_search[property_type][]" value="'. $this_property .'" />';
		} ?>
				
            <ul class="wpp_search_elements">
                <?php if(is_array($search_attributes)) foreach($search_attributes as $attrib) {
                    // Don't display search attributes that have no values
                    if(!isset($search_values[$attrib]))
                        continue;
                    ?>
                    <li class="seach_attribute_<?php echo $attrib; ?>">
                        <label class="wpp_search_label wpp_search_label_<?php echo $attrib; ?>" for="wpp_search_input_field_<?php echo $attrib; ?>"><?php echo (empty($wp_properties['property_stats'][$attrib]) ? ucwords($attrib) : $wp_properties['property_stats'][$attrib]) ?>:</label>
                        <?php
                        // Determine if attribute is a numeric range
                        if(WPP_F::is_numeric_range($search_values[$attrib])) {
                        ?>
                            <input  id="wpp_search_input_field_<?php echo $attrib; ?>"  class="wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?>" type="text" name="wpp_search[<?php  echo $attrib; ?>][min]" value="<?php echo $_REQUEST['wpp_search'][$attrib]['min']; ?>" /> -
                            <input class="wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?>"  type="text" name="wpp_search[<?php echo $attrib; ?>][max]" value="<?php echo $_REQUEST['wpp_search'][$attrib]['max']; ?>" />
                        <?php
                        }  else { /* Not a numeric range */ ?>
                            <select id="wpp_search_input_field_<?php echo $attrib; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?>" name="wpp_search[<?php echo $attrib; ?>]" >
                                <option value="-1"><?php _e('Select an item','wpp') ?></option>
                                <?php foreach($search_values[$attrib] as $value) { ?>
                                    <option value='<?php echo $value; ?>' <?php if($_REQUEST['wpp_search'][$attrib] == $value) echo " selected='true' "; ?>>
                                        <?php echo apply_filters("wpp_stat_filter_$attrib", $value); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </li>
                <?php } ?>
                <li><input type="submit" class="wpp_search_button submit" value="<?php _e('Search','wpp') ?>" /></li>
            </ul>
            <?php if($per_page) echo '<input type="hidden" name="wpp_search[pagi]" value="0--'. $per_page .'" />'; ?>
        </form>
    <?php }
		
endif;

?>