<?php
/**
 * WP-Property General Functions
 *
 * Contains all the general functions used by the plugin.
 *
 * @version 1.1
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
 * @subpackage Functions
 */

class WPP_F {

	
	/**
	 * Displays dropdown of available property size images
	 *
 	 *
 	 * @since 1.1
	 *
 	 */	 
	function image_sizes_dropdown($args = "") {	
		global $wp_properties, $_wp_additional_image_sizes;
	
		$defaults = array('name' => 'wpp_image_sizes', 'wpp_only' => false, 'selected' => 'none');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		if(empty($id) && !empty($name)) {
			$id = $name;
		}
		
		if($wpp_only) {
			$image_array = $wp_properties['image_sizes'];
		} else {
			$image_array = $_wp_additional_image_sizes;		
		}
		
		?>
			<select id="<?php echo $id ?>" name="<?php echo $name ?>" >
					<?php foreach($image_array as $name => $sizes) { ?>
						<option value='<?php echo $name; ?>' <?php if($selected == $name) echo 'SELECTED'; ?>>
							<?php echo $sizes[width]; ?>px by <?php echo $sizes[height]; ?>px
						</option>
					<?php } ?>
			</select>
	
		<?php
	}
	
	function image_sizes($type) {
		global $_wp_additional_image_sizes;
		
		if(is_array($_wp_additional_image_sizes[$type]))
			return $_wp_additional_image_sizes[$type];
			
		return false;
	
	}
	
	
	/**
	 * Saves settings, applies filters, and loads settings into global variable
	 *
	 * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
	 *
	 * @return array|$wp_properties
	 * @since 1.1
	 *
 	 */	 
	function settings_action() {
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
			
		// Filers are applied
		$wp_properties['configuration'] 						= apply_filters('wpp_configuration', $wp_properties['configuration']);
		$wp_properties['location_matters'] 					= apply_filters('wpp_location_matters', $wp_properties['location_matters']);
		$wp_properties['hidden_attributes'] 				= apply_filters('wpp_hidden_attributes', $wp_properties['hidden_attributes']);
		$wp_properties['descriptions'] 						= apply_filters('wpp_label_descriptions' , $wp_properties['descriptions']);
		$wp_properties['image_sizes'] 						= apply_filters('wpp_image_sizes' , $wp_properties['image_sizes']);
		$wp_properties['search_conversions'] 				= apply_filters('wpp_search_conversions' , $wp_properties['search_conversions']);
		$wp_properties['searchable_attributes'] 			= apply_filters('wpp_searchable_attributes' , $wp_properties['searchable_attributes']);
		$wp_properties['searchable_property_types'] 	= apply_filters('wpp_searchable_property_types' , $wp_properties['searchable_property_types']);
		$wp_properties['property_meta'] 					= apply_filters('wpp_property_meta' , $wp_properties['property_meta']);
		$wp_properties['property_stats'] 					= apply_filters('wpp_property_stats' , $wp_properties['property_stats']);
		$wp_properties['property_types'] 					= apply_filters('wpp_property_types' , $wp_properties['property_types']);
 		
		return $wp_properties;
	
	}
	
	
	
	
	/**
	 * Loads property values into global $post variables
	 *
	 * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
	 *
	 * @todo There may be a better place to load property variables
	 * @since 1.0
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
	 * @since 1.0
	 *
 	 */
	function load_premium() {
		global $wp_properties;
		
		
		if(!is_dir(WPP_Premium))
			return;
	
		if ($premium_dir = opendir(WPP_Premium)) {
		
			include_once(WPP_Premium . "/index.php");
			
			while (false !== ($file = readdir($premium_dir))) {

				if($file == 'index.php')
					continue;
				
				if(end(explode(".", $file)) == 'php') {
					
					$plugin_slug = str_replace(array('class_','.php'), '', $file);
					
					// Check if the plugin is disabled
					if($wp_properties[plugins][$plugin_slug][status] != 'disabled') {
						include_once(WPP_Premium . "/" . $file);
						
						$wp_properties[plugins][$plugin_slug][status] = 'enabled';
					}
					
				}
			}
		}

	}


/*
	Create tables and stuff
*/
	function activation() {
		global $wpdb;

		$installed_ver = get_option( "wpp_version" );
		$wpp_version = "0.52";
	 

		update_option( "wpp_version", $wpp_version );

	}

	function deactivation() {
		global $wp_rewrite;
		
		$wp_rewrite->flush_rules();

	}

/*
	Returns array of searchable properties
*/

	function get_searchable_properties() {
		global $wp_properties;

		$searchable_properties = array();

		// Get IDs of all property types
		foreach($wp_properties['searchable_property_types'] as $property_type) {
			$searchable_properties = array_merge($searchable_properties, WPP_F::get_properties("property_type=$property_type"));

		}

		// Remove any properties specifically marked as non-searchable (later)

		if(is_array($searchable_properties))
			return $searchable_properties;

		return false;


	}
/*
	Returns values for search and filtering
*/

	function get_search_values() {

		global $wpdb, $wp_properties;

		$searchable_properties = WPP_F::get_searchable_properties();

		// Return fail if no searchable properties found
		if(!$searchable_properties)
			return false;
			
 		// Cycle through all property types and gather all searchable data into one array
		foreach($searchable_properties as $property_id) {


			$property = WPP_F::get_property($property_id);
 
			foreach($wp_properties['searchable_attributes'] as $searchable_attribute) {
	
 				// Clean up values if a conversion exists
				$search_value = WPP_F::do_search_conversion($searchable_attribute, $property[$searchable_attribute]);
				
 
				if(empty($search_value))
					continue;
			
				$range[$searchable_attribute][]	= $search_value;

				$range[$searchable_attribute] = array_unique($range[$searchable_attribute]);
				sort($range[$searchable_attribute], SORT_NUMERIC);
			}


		}


		// Clean up values - remove any dollar signs, dashes, words, etc.



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

/*
	Get properties
*/

	function get_properties($args) {
		global $wpdb;

		$defaults = array('property_type' => 'building');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$all = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_type = 'property'");
  		foreach($all as $id) {
		

			$property_obj = WPP_F::get_property($id);			
			if(!empty($bedrooms)) {			
				if(WPP_F::do_search_conversion('bedrooms',$property_obj[bedrooms], true) != $bedrooms) {
 					continue;
				}
								

			}
							
			if(!empty($bathroms)) {
				if(WPP_F::do_search_conversion('bathrooms',$property_obj[bathrooms], true) != $bathroms)
					continue;
									
			}
			
							
			if(!empty($city)) {
				if($property_obj[city] != $city)
					continue;				
			}
							
			if(!empty($price_low)) {
				if($property_obj[price] < $price_low)
					continue;	 				
			}
											
			if(!empty($price_high)) {
				if($property_obj[price] > $price_high)
					continue;	 				
			}
			
			if(!empty($property_type) && $property_type != 'all') {
				if($property_type != get_post_meta($id, 'property_type', true))
					continue;
			}

			
			$return[] = $id;

		}	


		return $return;


	}
	
/*
	Gets items that are to be displayed on super man
*/
	function get_supermap_properties() {
		global $wpdb;

		// Get all property listings
		$properties = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_type = 'property'");

 		// Keep only ones that have coordinates
		foreach($properties as $id) {

			$property = WPP_F::get_property($id);


			// Skip if no coordinates
			if(!$property['coordinates_set'])
				continue;

			// Skip if marked as to be excluded
			if($property['exclude_from_supermap'])
				continue;



			$return[$id] = $property;



		}

		return $return;


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

/*
	Loads property settings into a class
*/
	function get_property($id, $args = false) {
		global $wp_properties, $wpdb;
 
 		$defaults = array("get_children" => true, 'return_object' => false);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
 
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



		

 		$thumbnail_id = get_post_meta( $id, '_thumbnail_id', true );
		$attachments = get_children( array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image',  'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );

		
		// Get Primary Image.  If a thumbnail is set, use that image, if not, get the first attachment
		// Load all the image specified by the 'image_sizes' hook

		if ($thumbnail_id) {

			foreach($wp_properties['image_sizes'] as $image_name => $image_sizes) {
				$this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name , true );
				$return[$image_name] = $this_url[0];
				}
				
			$featured_image_id = $thumbnail_id;

		} elseif ($attachments) {
			foreach ( $attachments as $attachment_id => $attachment ) {

				foreach($wp_properties['image_sizes'] as $image_name => $image_sizes) {
					$this_url =  wp_get_attachment_image_src( $attachment_id, $image_name , true );
					$return[$image_name] = $this_url[0];
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





		if(!empty($return['latitude']) && !empty($return['longitude']))
			$return['coordinates_set'] = true;



		//$return[$searchable_attribute] = WPP_F::format_attrib(min($range[$searchable_attribute]), $searchable_attribute) . " - " . WPP_F::format_attrib(max($range[$searchable_attribute]), $searchable_attribute);

		
		// Setup parent settings
		if($post[post_parent]) {
		

			$return[post_parent] = $post[post_parent];
			$return[parent_id] = $post[post_parent];
			$return[parent_link] = get_permalink($post[post_parent]);
			$return[parent_title] = get_the_title($post[post_parent]);
			
			// Inherit city for search
 		
				
			// Inherit parents address
			if(empty($return[location])) {
				
				$return[location] = get_post_meta($post[post_parent], 'location', true);

				if(get_post_meta($post[post_parent], 'address_is_formatted', true)) {
					 $return[display_address] = get_post_meta($post[post_parent], 'street_number', true) . " " . get_post_meta($post[post_parent], 'route', true) . "<br />" . get_post_meta($post[post_parent], 'city', true) . ", " . get_post_meta($post[post_parent], 'state', true);
				} else {
					$return[display_address] = $return[location];
				}

				if(empty($return[location]) && get_post_meta($post[post_parent], 'location', true) != '') {
					$return[location] = get_post_meta($post[post_parent], 'location', true) ;
				}
			}
						
		
		}   
		
		
		if($return[address_is_formatted])
			$return[display_address] = $return[street_number] . " " . $return[route] . "<br />" . $return[city] . ", " . $return[state];

			
		// Another name for location
        $return[address] = $return[location];


		// Calculate variables if based off children if children exist
		$children = get_posts("post_parent=$id&post_type=property&orderby=menu_order&order=ASC");

		//print_r($children);
		if(count($children) > 0) {

			if($get_children) {
				// Cycle through children and get necessary variables
				foreach($children as $child) {

					$child_object = WPP_F::get_property($child->ID);

					$return[children][$child->ID] = $child_object;


					foreach($wp_properties['searchable_attributes'] as $searchable_attribute)
						if(is_numeric(str_replace("$", '', $child_object[$searchable_attribute])))
							$range[$searchable_attribute][]	= str_replace("$" , '', $child_object[$searchable_attribute]);

				}
			}



			// Enter children ranges into object variables, overwriting them
			foreach($wp_properties['searchable_attributes'] as $searchable_attribute) {
			
				if($searchable_attribute == 'city')
					continue;

				// Do not format values here because it'll mess up counts
				if(count($range[$searchable_attribute]) < 2)
					$return[$searchable_attribute] = $range[$searchable_attribute][0];
				elseif(count($range[$searchable_attribute]) > 1)
					$return[$searchable_attribute] = min($range[$searchable_attribute]) . " - " . max($range[$searchable_attribute]);
				else
					$return[$searchable_attribute] = $range[$searchable_attribute];
			}



		}

		$return[is_child] = (is_numeric($post[post_parent]) && $post[post_parent] > 0 ? true : false);

		$return[permalink] = get_permalink($id);

		if(empty($return[phone_number])) {
			$return[phone_number] = $wp_properties[configuration][phone_number];
			
				
		}
		
		// Fix currency
		$return[price] = money_format("%i",$return[price]);

		if(is_array($return))
			ksort($return);

		$return = apply_filters('wpp_get_property', $return);

		// Get rid of all empty values
		foreach($return as $key => $item) {
			if(empty($item))
				unset($return[$key]);
		}
		
		
		
		// Convert to object
		if($return_object) {
			$return = WPP_F::array_to_object($return);
		
		}
		
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
	Format attribute
*/
	function format_attrib($value, $attribute = false) {

		return WPP_F::get_attrib_prefix($attribute) . $value . WPP_F::get_attrib_annex($attribute);
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

		if(empty($latitude) || empty($longitude))
			return false;

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
	function get_stat_values_and_labels($property_id = false, $args = false) {
		global $wp_properties;

		$defaults = array( );
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if($exclude)
			$exclude = explode(',', $exclude);

		if($include)
			$include = explode(',', $include);


		$property_stats = $wp_properties['property_stats'];

		foreach($property_stats as $slug => $label) {
			$value = get_post_meta($property_id, $slug, true);

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


/*
	Returns array of featured properties
*/

	function get_featured($args = "") {
		global $wpdb;

		$defaults = array('return' => 'ID');
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		
		$by_meta = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'featured' AND meta_value = 'true'");

		
		if(empty($by_meta))
			return false;
 
		
		
		if($return == "ID") {
			return $by_meta;
		}

		foreach($by_meta as $post_id) {
			$return[$post_id] = WPP_F::geat($post_id);
		}


		return $return;




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