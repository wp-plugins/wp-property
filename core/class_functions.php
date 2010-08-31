<?php
/**
 * WP-Property General Functions
 *
 * Contains all the general functions used by the plugin.
 *
 * @version 0.54
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
 * @subpackage Functions
 */

class WPP_F {


	

	/**
	 * Determines most common property type (used for defaults when needed)
	 *
 	 *
 	 * @since 0.55
	 *
 	 */
	function get_most_common_property_type($array = false) {
		global $wpdb;
	
		$top_property_type = $wpdb->get_row("
		SELECT meta_value as property_type, count(meta_value) as count
		FROM {$wpdb->prefix}postmeta WHERE meta_key = 'property_type'
		GROUP BY meta_value
		ORDER BY count DESC
		LIMIT 0,1");
		
 		
		return $top_property_type->property_type;
	}
	
	

	/**
	 * Determines if all of the arrays values are numeric
	 *
 	 *
 	 * @since 0.55
	 *
 	 */
	function is_numeric_range($array = false) {
		if(!is_array($array))
			return;
			
		foreach($array as $value) {
			if(!is_numeric($value))
				return false;	
		}
		
		return true;
	
	}

	function draw_property_type_dropdown($args = '') {
		global $wp_properties;
		
		$defaults = array('id' => 'wpp_property_type',  'name' => 'wpp_property_type',  'selected' => '');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		
		if(!is_array($wp_properties['property_types']))
			return;
		
		$return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
		foreach($wp_properties['property_types'] as $slug => $label) 
			$return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . "'>$label</option>";
		$return .= "</select>";
	
		return $return;
	
	
	}
	
	function draw_property_dropdown($args = '') {
		global $wp_properties, $wpdb;
		
		$defaults = array('id' => 'wpp_properties',  'name' => 'wpp_properties',  'selected' => '');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$all_properties = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'property' AND post_status = 'publish'");
 		
		if(!is_array($all_properties))
			return;
		
		$return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
		foreach($all_properties as $p_data) 
			$return .= "<option value='$p_data->id' " . ($selected == $p_data->id ? " selected='true' " : "") . "'>{$p_data->post_title}</option>";
		$return .= "</select>";
	
		return $return;
	
	
	}
	
	function draw_attribute_dropdown($args = '') {
		global $wp_properties, $wpdb;
		
		$defaults = array('id' => 'wpp_attribute',  'name' => 'wpp_attribute',  'selected' => '');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$attributes = $wp_properties['property_stats'];
 		
		if(!is_array($attributes))
			return;
		
		$return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
		foreach($attributes as $slug => $label) 
			$return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . "'>$label ($slug)</option>";
		$return .= "</select>";
	
		return $return;
	
	
	}
	
	

	/**
	 * Makes a given property featured, usuall called via ajax
	 *
 	 *
 	 * @since 0.55
	 *
 	 */
	function feature_check($return = false) {
		$blogname = get_bloginfo('url');
		$blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
		$system = 'wpp';
		
		$check_url = "http://updates.twincitiestech.com/?system=$system&site=$blogname";
		$response = @wp_remote_get($check_url);
	
		if(!$response)
			return;
			
 
					
		// Quite if failture
		if($response[response][code] != '200')
			return;
	

 		$response = @json_decode($response[body]);
	
		if(is_object($response->available_features)): 
			
			$response->available_features = UD_F::objectToArray($response->available_features);
			
		
			// Updata database
			$wpp_settings = get_option('wpp_settings');
			$wpp_settings[available_features] =  UD_F::objectToArray($response->available_features);
 			update_option('wpp_settings', $wpp_settings);


		endif;// available_features
		
		
		if($response->features == 'eligible') {
		
			// Try to create directory if it doesn't exist
			if(!is_dir(WPP_Premium)) {
				@mkdir(WPP_Premium, 0755);
			}
				
			// If didn't work, we quit
			if(!is_dir(WPP_Premium))
				continue;
				
				
			// Save code
			if(is_object($response->code)) {
				foreach($response->code as $code) {
 					$filename = $code->filename;
					$php_code = $code->code;
					$version = $code->version;
					
					// Check version
				
					$default_headers = array(
					'Name' => 'Feature Name',
					'Version' => 'Version',
					'Description' => 'Description',
					);

					$current_file = @get_file_data( WPP_Premium . "/" . $filename, $default_headers, 'plugin' );
					//echo "$filename - new version: $version , old version:$current_file[Version] |  " .  @version_compare($current_file[Version], $version) . "<br />"; 
					
					if(@version_compare($current_file[Version], $version) == '-1') {
						$this_file = WPP_Premium . "/" . $filename;
						$fh = @fopen($this_file, 'w');
						fwrite($fh, $php_code);
						fclose($fh);
						UD_F::log("WP-Property Premium Feature: {$code->name} updated to version $version from {$current_file[Version]}.");
					} else {
 					
					}
					
						
				}			
			}
		}
		
		// Update settings
		WPP_F::settings_action(true);
		
		if($return)
			return $response;
	}
	 
	 
	/**
	 * Makes a given property featured, usuall called via ajax
	 *
 	 *
 	 * @since 0.54
	 *
 	 */
	 function toggle_featured($post_id = false) {

		if(!$post_id)
			return;
			
		$featured = get_post_meta($post_id, 'featured', true);

		// Check if already featured
		if($featured == 'true') {
			update_post_meta($post_id, 'featured', 'false');
			$status = 'not_featured';
		} else {  
			update_post_meta($post_id, 'featured', 'true');
			$status = 'featured';
		}

		echo json_encode(array('success' => 'true', 'status' => $status, 'post_id' => $post_id));

	 }

	/**
	 * Displays dropdown of available property size images
	 *
 	 *
 	 * @since 0.54
	 *
 	 */
	function image_sizes_dropdown($args = "") {
		global $wp_properties;

		$defaults = array('name' => 'wpp_image_sizes',  'selected' => 'none');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if(empty($id) && !empty($name)) {
			$id = $name;
		}


		$image_array = get_intermediate_image_sizes();


		?>
			<select id="<?php echo $id ?>" name="<?php echo $name ?>" >
					<?php
						foreach($image_array as $name) {
						$sizes = WPP_F::image_sizes($name);

						if(!$sizes)
							continue;

					?>
						<option value='<?php echo $name; ?>' <?php if($selected == $name) echo 'SELECTED'; ?>>
							 <?php echo $name; ?>: <?php echo $sizes[width]; ?>px by <?php echo $sizes[height]; ?>px
						</option>
					<?php } ?>
			</select>

		<?php
	}

	function image_sizes($type = false, $args = "") {
		global $_wp_additional_image_sizes;

		$defaults = array('return_all' => false);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


		if(!$type)
			return false;

		if(is_array($_wp_additional_image_sizes[$type])) {
			$return = $_wp_additional_image_sizes[$type];

		} else {

			if($type == 'thumbnail' || $type == 'thumb') {
				$return = array('width' => intval(get_option('thumbnail_size_w')), 'height' => intval(get_option('thumbnail_size_h')));
			}

			if($type == 'medium') {
				$return = array('width' => intval(get_option('medium_size_w')), 'height' => intval(get_option('medium_size_h')));
			}

			if($type == 'large') {
				$return = array('width' => intval(get_option('large_size_w')), 'height' => intval(get_option('large_size_h')));
			}

		}

		if(!is_array($return))
			return;

		if(!$return_all) {

			// Zeroed out dimensions means they are deleted
			if(empty($return[width]) || empty($return[height]))
				return;

			// Zeroed out dimensions means they are deleted
			if($return[width] == '0' || $return[height] == '0')
				return;

		}

		// Return dimensions
		return $return;

	}


	/**
	 * Saves settings, applies filters, and loads settings into global variable
	 *
	 * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
	 *
	 * @return array|$wp_properties
	 * @since 0.54
	 *
 	 */
	function settings_action($force_db = false) {
		global $wp_properties, $wp_rewrite;

		// Process saving settings
		if(isset($_REQUEST['wpp_settings']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_setting_save') ) {
 			update_option('wpp_settings', $_REQUEST['wpp_settings']);
			$wp_rewrite->flush_rules();

			// Load settings out of database to overwrite defaults from action_hooks.
			$wp_properties_db = get_option('wpp_settings');

			// Overwrite $wp_properties with database setting
			$wp_properties = array_merge($wp_properties, $wp_properties_db);

		}
		
		if($force_db) {
		
			// Load settings out of database to overwrite defaults from action_hooks.
			$wp_properties_db = get_option('wpp_settings');

			// Overwrite $wp_properties with database setting
			$wp_properties = array_merge($wp_properties, $wp_properties_db);

		}

		add_filter('wpp_image_sizes', array('WPP_F','remove_deleted_image_sizes'));

		// Filers are applied
		$wp_properties['configuration'] 						= apply_filters('wpp_configuration', $wp_properties['configuration']);
		$wp_properties['location_matters'] 					= apply_filters('wpp_location_matters', $wp_properties['location_matters']);
		$wp_properties['hidden_attributes'] 				= apply_filters('wpp_hidden_attributes', $wp_properties['hidden_attributes']);
		$wp_properties['descriptions'] 						= apply_filters('wpp_label_descriptions' , $wp_properties['descriptions']);
		$wp_properties['image_sizes'] 						= apply_filters('wpp_image_sizes' , $wp_properties['image_sizes']);
		$wp_properties['search_conversions'] 				= apply_filters('wpp_search_conversions' , $wp_properties['search_conversions']);
		$wp_properties['searchable_attributes'] 			= apply_filters('wpp_searchable_attributes' , $wp_properties['searchable_attributes']);
		$wp_properties['searchable_property_types'] 	= apply_filters('wpp_searchable_property_types' , $wp_properties['searchable_property_types']);
		$wp_properties['property_inheritance'] 			= apply_filters('wpp_property_inheritance' , $wp_properties['property_inheritance']);
		$wp_properties['property_meta'] 					= apply_filters('wpp_property_meta' , $wp_properties['property_meta']);
		$wp_properties['property_stats'] 					= apply_filters('wpp_property_stats' , $wp_properties['property_stats']);
		$wp_properties['property_types'] 					= apply_filters('wpp_property_types' , $wp_properties['property_types']);

		return $wp_properties;

	}

	function remove_deleted_image_sizes($sizes) {
		global $wp_properties;

		foreach($sizes as $slug => $size) {
			if($size[width] == '0' || $size[height] == '0')
				unset($sizes[$slug]);

		}


		return $sizes;

	}


	/**
	 * Loads property values into global $post variables
	 *
	 * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
	 *
	 * @todo There may be a better place to load property variables
	 * @since 0.54
	 *
 	 */
	function the_post($post) {
		global $post;

		if($post->post_type == 'property') {
			$post = WPP_F::get_property($post->ID, "return_object=true");
		}

 	}


	/**
	 * Check for premium features and load them
	 *
	 * @since 0.624
	 *
 	 */
	function load_premium() {
		global $wp_properties;

		$default_headers = array(
			'Name' => 'Name',
			'Version' => 'Version',
			'Description' => 'Description',
		);
		
					
		if(!is_dir(WPP_Premium))
			return;

		if ($premium_dir = opendir(WPP_Premium)) {

			include_once(WPP_Premium . "/index.php");

			while (false !== ($file = readdir($premium_dir))) {

				if($file == 'index.php')
					continue;

				if(end(explode(".", $file)) == 'php') {

					$plugin_slug = str_replace(array('.php'), '', $file);
					
					
					
					$plugin_data = @get_file_data( WPP_Premium . "/" . $file, $default_headers, 'plugin' );
					$wp_properties[installed_features][$plugin_slug][name] = $plugin_data['Name'];
					$wp_properties[installed_features][$plugin_slug][version] = $plugin_data['Version'];
					$wp_properties[installed_features][$plugin_slug][description] = $plugin_data['Description'];

					// Check if the plugin is disabled
					if($wp_properties[installed_features][$plugin_slug][disabled] != 'true') {
						include_once(WPP_Premium . "/" . $file);

 						// Disable plugin if class does not exists - file is empty
						if(!class_exists($plugin_slug))
							unset($wp_properties[installed_features][$plugin_slug]);
							
						$wp_properties[installed_features][$plugin_slug][disabled] = 'false';
					}

				}
			}
		}

	}

	function check_plugin_updates() {
		global $wp_properties;

		WPP_F::feature_check();
		
		


	}
	

	/**
	 * Schedule premium feature check
	 *
	 * @since 0.55
	 *
 	 */
	function activation() {
		global $wpdb;

		$installed_ver = get_option( "wpp_version" );
		$wpp_version = "0.62";

 
		wp_schedule_event(time(), 'daily', 'wpp_premium_feature_check');
		update_option( "wpp_version", $wpp_version );

	}

	function deactivation() {
		global $wp_rewrite;
		$timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
		wp_unschedule_event($timestamp, 'wpp_premium_feature_check' );
		wp_clear_scheduled_hook('wpp_premium_feature_check');

		$wp_rewrite->flush_rules();

	}

	/**
	 * Returns array of searchable property IDs
	 *
	 *
	 * @return array|$wp_properties
	 * @since 0.621
	 *
 	 */
	function get_searchable_properties() {
		global $wp_properties;

		$searchable_properties = array();

		if(!is_array($wp_properties['searchable_property_types']))
			return;
		
		// Get IDs of all property types
		foreach($wp_properties['searchable_property_types'] as $property_type) {
		
			$this_type_properties = WPP_F::get_properties("property_type=$property_type");
			
			if(is_array($this_type_properties) && is_array($searchable_properties))
				$searchable_properties = array_merge($searchable_properties, $this_type_properties);
		}

		if(is_array($searchable_properties))
			return $searchable_properties;

		return false;

	}

	/**
	 * Returns array of searchable attributes and their ranges
	 *
	 *
	 * @todo Should cache values
	 * @return array|$wp_properties
	 * @since 0.57
	 *
 	 */
	function get_search_values() {
		global $wpdb, $wp_properties;

		$searchable_properties = WPP_F::get_searchable_properties();

		// Return fail if no searchable properties found
		if(!$searchable_properties)
			return false;

 		// Cycle through all searchable properties all searchable data into one array
		foreach($searchable_properties as $property_id) {

			$property = WPP_F::get_property($property_id, "get_children=false&load_gallery=false");

			foreach($wp_properties['searchable_attributes'] as $searchable_attribute) {

 				// Clean up values if a conversion exists
				$search_value = WPP_F::do_search_conversion($searchable_attribute, $property[$searchable_attribute]);

				// Remove dollay signs
				$search_value = str_replace(array(",", "$"), '', $search_value);

				// Fix ranges
				if(strpos($search_value, '-')) {
					$split = explode('-', $search_value);

					foreach($split as $new_search_value)
						if(!empty($new_search_value))
							$range[$searchable_attribute][] = trim($new_search_value);

					continue;

				}

				if(empty($search_value))
					continue;

				$range[$searchable_attribute][]	= $search_value;

				$range[$searchable_attribute] = array_unique($range[$searchable_attribute]);
				sort($range[$searchable_attribute], SORT_NUMERIC);
			}


		}

		return ($range);

	}

/*
	check if a search converstion exists for a attributes value
*/
	function do_search_conversion($attribute, $value, $reverse = false)  {
		global $wp_properties;

		// First, check if any conversions exists for this attribute, if not, return value
		if(count($wp_properties['search_conversions'][$attribute]) < 1)
			return $value;


		// If reverse is set to true, means we are trying to convert a value to integerer (most likely),
		// For isntance: in "bedrooms", $value = 0 would be converted to "Studio"
		if($reverse) {


			$flipped_conversion = array_flip($wp_properties['search_conversions'][$attribute]);

			// Debug:
			//echo "reverse conversion: $attribute - $value; -" .$flipped_conversion['search_conversions'][$attribute][$value]. "<br />";


			if(!empty($flipped_conversion[$value]))
				return $flipped_conversion[$value];


		}



		// Debug:
		//echo "doing conversion: $attribute - $value; -" .$wp_properties['search_conversions'][$attribute][$value]. "<br />";


		// Search conversion does exist, make sure its not an empty value.
		// Need to $conversion == '0' or else studios will not work, since they have 0 bedrooms
		$conversion = $wp_properties['search_conversions'][$attribute][$value];
		if($conversion == '0' || !empty($conversion))
			return $conversion;

		// Return value in case something messed up
		return $value;


	}

 	
	/**
	 * Primary function for queries properties  based on type and attributes
	 *
 	 *
 	 * @since 0.55
	 *
 	 */
	function get_properties($args = "") {
		global $wpdb;

		$defaults = array('property_type' => 'building');
		
		if(is_array($maybe_array = unserialize($args)))
			$query = $maybe_array;
		else
			$query = wp_parse_args( $args, $defaults );

		//echo "<pre>" . print_r($query, true) . "</pre>";
				
		// Go down the array list narrowing down matching properties
		
		foreach($query as $meta_key => $criteria) {

			if(isset($matching_ids) && empty($matching_ids)) {
				UD_F::log("Stop filtering because no IDs left, count: " . count($matching_ids));
				break;
			}
			
			if(is_array($criteria)) {
				$min = $criteria[min];
				$max = $criteria[max];
				$specific = $criteria[specific];
			} else {
				$specific = $criteria;			
			}

			
			switch ($meta_key) {
			
				case 'property_type':
				
					if(empty($criteria) || $criteria == 'all')
						break;
					
					if(!is_array($criteria))
						$criteria = array($criteria);

					UD_F::log("Filtering property_type");
						
					$where_string = implode("' OR meta_value ='", $criteria);
					
					// See if mathinc_ids have already been filtered down
					if(isset($matching_ids)) {
						$matching_id_filter = implode("' OR post_id ='", $matching_ids);
						
						$matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (post_id ='$matching_id_filter') AND (meta_key = 'property_type' AND (meta_value ='$where_string'))");
					} else {
						$matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = 'property_type' AND (meta_value ='$where_string'))");
					}
					
					UD_F::log($wpdb->last_query. " " . print_r($matching_ids, true));

				
				break;
			
			
				default:
				
					if(empty($min) && empty($max) && empty($specific)) {
						UD_F::log("Skipping $meta_key search because no criteria passed.");
						continue;
					}
						
					if(WPP_F::is_numeric_range($criteria)) {
										
						UD_F::log("Filtering $meta_key which is numeric");
						// See if mathinc_ids have already been filtered down
						if(isset($matching_ids)) {
							$matching_id_filter = implode("' OR post_id ='", $matching_ids);						
							$matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '$meta_key' AND (post_id ='$matching_id_filter') AND (meta_value BETWEEN  $min AND $max)");
						} else {
							$matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = '$meta_key' AND (meta_value BETWEEN  '$min' AND $max))");
						}
						UD_F::log($wpdb->last_query. " " . print_r($matching_ids, true));
					} else {

						UD_F::log("Filtering $meta_key which is not numeric");
						
						if(isset($matching_ids)) {
							$matching_id_filter = implode("' OR post_id ='", $matching_ids);						
							$matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '$meta_key' AND (post_id ='$matching_id_filter') AND meta_value = '$specific'");
						} else {
							$matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '$meta_key' AND meta_value = '$specific'");
						}
						UD_F::log($wpdb->last_query. " " . print_r($matching_ids, true));
					
					}
				
				break;
			
			
			}
		
		}
		
		if(!empty($matching_ids)) {
			UD_F::log("Search complete, returning: " . implode(" ,", $matching_ids));
			return $matching_ids;
		}
		
		
		return false;
	}

	
 
/*
	Returns array of all values for a particular attribute/meta_key
*/
	function get_all_attribute_values($slug) {
		global $wpdb;


		$prefill_meta = $wpdb->get_col("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$slug'");

		if(empty($prefill_meta[0]))
			unset($prefill_meta);

		$prefill_meta = apply_filters('wpp_prefill_meta', $prefill_meta, $slug);

		if(count($prefill_meta) < 1)
			return false;

		// Clean up values
		foreach($prefill_meta as $meta) {

			if(empty($meta))
				continue;

			$return[] = $meta;

		}

		// Remove duplicates
		$return = array_unique($return);

		sort($return);

		return $return;


	}

	/**
	 * Load property information into an array or an object
	 *
 	 *
 	 * @since 0.55
	 *
 	 */
	function get_property($id, $args = false) {
		global $wp_properties, $wpdb, $wpp_cache;


 		$defaults = array("get_children" => 'true', 'return_object' => "false", 'load_gallery' => 'true', 'load_thumbnail' => 'true', 'load_parent' => 'true');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
 
 
		//UD_F::log("Loading property id: $id");

		$post = get_post($id, ARRAY_A);

		if($post[post_type] != 'property')
			return false;



		if ( $keys = get_post_custom( $id )) {

  			foreach ( $keys as $key => $value ) {

				$keyt = trim($key);

				if ( '_' == $keyt{0} )
					continue;

				// Fix for boolean values
				switch($value[0]) {

					case 'true':
					$real_value = true;
					break;

					case 'false':
					$real_value = false;
					break;

					default:
					$real_value = $value[0];
					break;

				}
 				// if a property_meta value, we do a nl2br since it will most likely have line breaks
				if(array_key_exists($key, $wp_properties['property_meta']))
					$real_value = nl2br($real_value);

				$return[$key] = $real_value;
			}
 		}


		if(is_array($return))
			$return = array_merge($return, $post);



		/*
			Figure out what the thumbnail is, and load all sizes
		*/
		if($load_thumbnail == 'true') {
			$wp_image_sizes = get_intermediate_image_sizes();

			$thumbnail_id = get_post_meta( $id, '_thumbnail_id', true );
			$attachments = get_children( array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image',  'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );


			if ($thumbnail_id) {
				foreach($wp_image_sizes as $image_name) {
					$this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name , true );
					$return[images][$image_name] = $this_url[0];
					}

				$featured_image_id = $thumbnail_id;

			} elseif ($attachments) {
				foreach ( $attachments as $attachment_id => $attachment ) {

					foreach($wp_image_sizes as $image_name) {
						$this_url =  wp_get_attachment_image_src( $attachment_id, $image_name , true );
						$return[images][$image_name] = $this_url[0];
					}

					$featured_image_id = $attachment_id;
					break;
				}
			}


			if($featured_image_id) {
				$return[featured_image] = $featured_image_id;

				$image_title = $wpdb->get_var("SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '$featured_image_id' ");

				$return[featured_image_title] = $image_title;
				$return[featured_image_url] = wp_get_attachment_url($featured_image_id);

			}
		} /* end load_thumbnail */


		/*
			Load all attached images and their sizes
		*/
		if($load_gallery == 'true') {
			// Get gallery images
			if($attachments) {
				foreach ( $attachments as $attachment_id => $attachment ) {
					$return[gallery][$attachment->post_name][post_title] = $attachment->post_title;
					foreach($wp_image_sizes as $image_name) {
						$this_url =  wp_get_attachment_image_src( $attachment_id, $image_name , true );
						$return[gallery][$attachment->post_name][$image_name] = $this_url[0];
					}


				}

			} else {
				$return[gallery] = false;
			}
		}
		// end load_gallery




 
		/*
			Load parent if exists.
			Inherit Parent's Properties
		*/
		if($load_parent == 'true' && $post[post_parent]) {

			$return[is_child] = true;

			$parent_object = WPP_F::get_property($post[post_parent], "get_children=false");


			
 			$return[parent_id] = $post[post_parent];
			$return[parent_link] = $parent_object[permalink];
			$return[parent_title] = $parent_object[post_title];

 
			// Inherit things
			if(is_array($wp_properties['property_inheritance'][$return[property_type]])) {
				foreach($wp_properties['property_inheritance'][$return[property_type]] as $inherit_attrib) {
					if(!empty($parent_object[$inherit_attrib]) && empty($return[$inherit_attrib])) {
						$return[$inherit_attrib] = $parent_object[$inherit_attrib];
					}
				}
			}
		}

		/*
			Load Children and their attributes
		*/
		if($get_children == 'true') {
		
			// Calculate variables if based off children if children exist
			$children = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE  post_type = 'property' AND post_status = 'publish' AND post_parent = '$id' ORDER BY menu_order ASC ");

			//print_r($children);
			if(count($children) > 0) {


					// Cycle through children and get necessary variables
					foreach($children as $child_id) {

						$child_object = WPP_F::get_property($child_id, "load_parent=false");

						$return[children][$child_id] = $child_object;


						foreach($wp_properties['searchable_attributes'] as $searchable_attribute)
 								if(!empty($child_object[$searchable_attribute]))
									$range[$searchable_attribute][]	= $child_object[$searchable_attribute];


					}



				// Cycle through every type of range (i.e. price, deposit, bathroom, etc) and fix-up the respective data arrays
				foreach($range as $range_attribute => $range_values) {

					// Cycle through all values of this range (attribute), and fix any ranges that use dashes
					foreach($range_values as $key => $single_value) {

						// Remove dollay signs
						$single_value = str_replace("$" , '', $single_value);

						// Fix ranges
						if(strpos($single_value, '-')) {
							$split = explode('-', $single_value);


							foreach($split as $new_single_value)

								if(!empty($new_single_value)) {
									array_push($range_values, trim($new_single_value));

								}

							// Unset original value with dash
							unset($range_values[$key]);


						}
					}

					// Remove duplicate values from this range
					$range[$range_attribute] =  array_unique($range_values);

					// Sort the values in this particular range
 					sort($range[$range_attribute]);

 					if(count($range[$range_attribute] ) < 2) {
						$return[$range_attribute] = $range[$range_attribute][0];
					}

					if(count($range[$range_attribute]) > 1) {
						$return[$range_attribute] = min($range[$range_attribute]) . " - " .  max($range[$range_attribute]);
					}

				}


			}
		} /* end get_children */



		// Another name for location
        $return[address] = $return[location];


		$return[permalink] = get_permalink($id);

		if(empty($return[phone_number]) && !empty($wp_properties[configuration][phone_number]))
			$return[phone_number] = $wp_properties[configuration][phone_number];



		if(is_array($return))
			ksort($return);

		$return = apply_filters('wpp_get_property', $return);

		// Get rid of all empty values
		foreach($return as $key => $item) {
			if(empty($item))
				unset($return[$key]);
		}



		// Convert to object
		if($return_object == 'true') {
			$return = WPP_F::array_to_object($return);

		}

		// Save to cache
		if(is_object($return))
			$wpp_cache[$id]['object'] = $return;
		if(is_array($return))
			$wpp_cache[$id]['array'] = $return;
		
		return $return;


	}
/*
	Gets prefix to an attribute
*/
	function get_attrib_prefix($attrib) {

		if($attrib == 'price')
			return "$";

		if($attrib == 'deposit')
			return "$";

	}

	/*
	Gets annex to an attribute
*/
	function get_attrib_annex($attrib) {
		if($attrib == 'area')
			return " sq ft.";

	}

 
/*
	Get coordinates for property out of database
*/
	function get_coordinates($listing_id = false) {
		global $post;

		if(!$listing_id)
			$listing_id = $post->ID;

		$latitude = get_post_meta($listing_id, 'latitude', true);
		$longitude = get_post_meta($listing_id, 'longitude', true);

		if(empty($latitude) || empty($longitude)) {

			// Try parent
			if($post->parent_id)  {
				$latitude = get_post_meta($post->parent_id, 'latitude', true);
				$longitude = get_post_meta($post->parent_id, 'longitude', true);

			}

			// Still nothing
			if(empty($latitude) || empty($longitude))
				return false;


		}

		return array('latitude' => $latitude, 'longitude' => $longitude);

	}


/*
	Validate if a URL is valid.
*/
	function isURL($url) {
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
	}

/*
	Returns an array of a property's stats and their values
	Query is array of variables to use
*/
	function get_stat_values_and_labels($property_object, $args = false) {
		global $wp_properties;
		$defaults = array( );
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if($exclude)
			$exclude = explode(',', $exclude);

		if($include)
			$include = explode(',', $include);



		$property_stats = $wp_properties['property_stats'];

		foreach($property_stats as $slug => $label) {
			$value = $property_object->$slug;



			// Exclude passed variables
			if(is_array($exclude) && in_array($slug, $exclude))
				continue;

			// Include only passed variables
			if(is_array($include) && in_array($slug, $include)) {
				if(!empty($value))
				$return[$label] = $value;
				continue;
			}


			if(!is_array($include)) {
			if(!empty($value))
				$return[$label] = $value;
			}

		}

		if(count($return) > 0)
			return $return;

		return false;


	}


	
	function array_to_object($array = array()) {
    if (!empty($array)) {
        $data = false;

        foreach ($array as $akey => $aval) {
            $data -> {$akey} = $aval;
        }

        return $data;
    }

    return false;
}



}



/**
* XMLToArray Generator Class
* @author  :  MA Razzaque Rupom <rupom_315@yahoo.com>, <rupom.bd@gmail.com>
*             Moderator, phpResource (LINK1http://groups.yahoo.com/group/phpresource/LINK1)
*             URL: LINK2http://www.rupom.infoLINK2
* @version :  1.0
* @date       06/05/2006
* Purpose  : Creating Hierarchical Array from XML Data
* Released : Under GPL
*/

class XmlToArray
{

    var $xml='';

    /**
    * Default Constructor
    * @param $xml = xml data
    * @return none
    */

    function XmlToArray($xml)
    {
       $this->xml = $xml;
    }

    /**
    * _struct_to_array($values, &$i)
    *
    * This is adds the contents of the return xml into the array for easier processing.
    * Recursive, Static
    *
    * @access    private
    * @param    array  $values this is the xml data in an array
    * @param    int    $i  this is the current location in the array
    * @return    Array
    */

    function _struct_to_array($values, &$i)
    {
        $child = array();
        if (isset($values[$i]['value'])) array_push($child, $values[$i]['value']);

        while ($i++ < count($values)) {
            switch ($values[$i]['type']) {
                case 'cdata':
                array_push($child, $values[$i]['value']);
                break;

                case 'complete':
                    $name = $values[$i]['tag'];
                    if(!empty($name)){
                    $child[$name]= ($values[$i]['value'])?($values[$i]['value']):'';
                    if(isset($values[$i]['attributes'])) {
                        $child[$name] = $values[$i]['attributes'];
                    }
                }
              break;

                case 'open':
                    $name = $values[$i]['tag'];
                    $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
                    $child[$name][$size] = $this->_struct_to_array($values, $i);
                break;

                case 'close':
                return $child;
                break;
            }
        }
        return $child;
    }//_struct_to_array

    /**
    * createArray($data)
    *
    * This is adds the contents of the return xml into the array for easier processing.
    *
    * @access    public
    * @param    string    $data this is the string of the xml data
    * @return    Array
    */
    function createArray()
    {
        $xml    = $this->xml;
        $values = array();
        $index  = array();
        $array  = array();
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $xml, $values, $index);
        xml_parser_free($parser);
        $i = 0;
        $name = $values[$i]['tag'];
        $array[$name] = isset($values[$i]['attributes']) ? $values[$i]['attributes'] : '';
        $array[$name] = $this->_struct_to_array($values, $i);
        return $array;
    }//createArray


}//XmlToArray
?>