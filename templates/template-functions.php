<?php
/**
 * Functions to be used in templates.  Overrided by anything in template functions.php
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.3
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

	
	
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
		if($id)
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
		global $wp_properties;
		$defaults = array( );
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if(!isset($property)) {
			global $post;
			$property = $post->ID;
		}


		$stats = WPP_F::get_stat_values_and_labels($property, $args);


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

			<?php if(WPP_F::isURL($value)): ?>
			<dd><a href="<?php echo $value; ?>"><?php echo $label; ?> Link</a></dd>
			<?php else: ?>
			<dd class="wpp_stat_dd_<?php echo $tag; ?>">
			<?php
			if($label == 'Deposit' || $label == 'Price')
				echo "$";

			echo $value;
			?></dd>
			<?php endif; ?>

			<?php endforeach; ?>
 
		<?php
	}
endif;


if(!function_exists('draw_featured_properties')):
	function draw_featured_properties() {
	?>


	    <?php
		$featured_array = WPP_F::get_featured();

	    if(is_array($featured_array)):
			foreach($featured_array as $featured):

				unset($this_property);
				$this_property = WPP_F::get_property($featured->ID);

				?>
				<div class="apartment_entry clearfix" style="clear:both;margin-bottom:15px;">

					<a href="<?php echo $this_property[permalink]; ?>">
						<img src="<?php echo $this_property[sidebar_gallery_thumb];?>" alt="<?php echo $this_property[post_title]; ?> at <?php echo $this_property[location]; ?> for <?php echo  $this_property[price]; ?>" />
					</a>

					<ul class="sidebar_properties">
						<li><span>Price:</span> $ <?php echo  $this_property[price]; ?></li>
						<li><span>Bed(s):</span> <?php echo  $this_property[bedrooms]; ?></li>
						<li><span>Bath(s):</span> <?php echo  $this_property[bathrooms]; ?></li>
						<li><span>Square Ft:</span> <?php echo  $this_property[area]; ?></li>
					</ul>
			   </div>
			  <?php
			endforeach;
		endif;


	}
endif;

if(!function_exists('draw_property_search_form')):
	function draw_property_search_form($search_attributes = "") {
		global $wp_properties;
  
	?>

	<?php
 		$search_values = WPP_F::get_search_values();

		?>
            <form action="<?php echo  get_bloginfo('url') . "/". $wp_properties['configuration']['base_slug']; ?>" method="post">
            	<ul class="wpp_search_elements">

				<?php if(in_array('bedrooms', $search_attributes)): ?>
				<li>
					<label for="wpp_search[bedrooms][value1]">Bedrooms</label>

					<select class="selection" name="wpp_search[bedrooms][value1]">
						<option></option>

						<?php foreach($search_values[bedrooms] as $value): ?>
							<option <?php if($_REQUEST['wpp_search']['bedrooms']['value1'] == $value): ?>SELECTED<?php endif; ?> value="<?php echo $value; ?>">
								<?php echo WPP_F::do_search_conversion('bedrooms', $value, true); ?>

								<?php /* if($value != max($search_values[bedrooms])): ?>+<?php endif; */ ?>

							</option>
						<?php endforeach; ?>

                    </select>
				</li>
				<?php endif; ?>
 
				<?php if(in_array('city', $search_attributes)): ?>
				<li>
					<label for="wpp_search[city][value1]">City</label>

					<select class="selection" name="wpp_search[city][value1]">
						<option></option>

						<?php foreach($search_values[city] as $value): ?>
							<option
							<?php if($_REQUEST['wpp_search']['city']['value1'] == $value): ?>
							SELECTED
							<?php endif; ?>

							value="<?php echo $value; ?>">

							<?php echo WPP_F::do_search_conversion('city', $value, true); ?>

							</option>
						<?php endforeach; ?>
                    </select>
				</li>
				<?php endif; ?>

				<?php if(in_array('price', $search_attributes)): ?>
				<li>
					<label for="wpp_search[price][value1]">Price</label>
					<div class="search_input_block">
					<input type="text"  class="text" name="wpp_search[price][value1]" value="<?php echo $_REQUEST['wpp_search']['price']['value1']; ?>" /> to
					<input name="wpp_search[price][value2]" type="text"   class="text" value="<?php echo $_REQUEST['wpp_search']['price']['value2']; ?>"  />
					</div>
				</li>
				<?php endif; ?>
				<li>
                    <input type="submit" class="wpp_search_button" value="Search" class="submit" />
				</li>
			</ul>

            </form>

		<?php }
		
endif;

?>