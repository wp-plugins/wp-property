<?php

	// Widget address format
	add_filter("wpp_stat_filter_{$wp_properties[configuration][address_attribute]}", "wpp_format_address_attribute", 0,3);

	// Add additional Google Maps localizations
	add_filter("wpp_google_maps_localizations", "wpp_add_additional_google_maps_localizations");

	// Add post-thumbnails support
	add_action("after_setup_theme", array('WPP_Core', "after_setup_theme"));

	// Add some default actions
	add_filter("wpp_stat_filter_price", 'add_dollar_sign');
	add_filter("wpp_stat_filter_deposit", 'add_dollar_sign');
	add_filter("wpp_stat_filter_area", 'add_square_foot');
	add_filter("wpp_stat_filter_phone_number", 'format_phone_number');

	add_filter('wpp_get_property', 'add_display_address');

	add_filter('wpp_property_inheritance', 'add_city_to_inheritance');
	add_filter('wpp_searchable_attributes', 'add_city_to_searchable');

	// Add sold/rented options
	add_filter('wpp_property_stats', 'wpp_property_stats_add_sold_or_rented');
	add_filter('wpp_property_stats_input_for_rent', 'wpp_property_stats_input_for_rent_make_checkbox', 0, 3);
	add_filter('wpp_property_stats_input_for_sale', 'wpp_property_stats_input_for_sale_make_checkbox', 0, 3);
	add_filter('wpp_stat_filter_for_rent', 'wpp_stat_filter_for_rent_fix');
	add_filter('wpp_stat_filter_for_sale', 'wpp_stat_filter_for_sale_fix');

	add_filter('the_password_form', 'wpp_password_protected_property_form');

	// Coordinate manual override
	add_filter('wpp_property_stats_input_'. $wp_properties[configuration][address_attribute], 'wpp_property_stats_input_address', 0, 3);
	
	
	add_action('save_property', 'save_property_coordinate_override', 0, 3);

	//add_action("wpp_ui_after_attribute_{$wp_properties['configuration']['address_attribute']}", 'wpp_show_coords');
	add_action('wpp_ui_after_attribute_price', 'wpp_show_week_month_selection');

	//  Adds additional settings for Property Page
	add_action('wpp_settings_page_property_page', 'add_format_phone_number_checkbox');

	function wpp_password_protected_property_form($output) {
		global $post;

		if($post->post_type != 'property')
			return $output;

		return str_replace("This post is password protected", "This property is password protected", $output);
	}

	/**
	 * Example of how to add a new language to Google Maps localization
	 *
	 * @since 1.04
	 */
	function wpp_add_additional_google_maps_localizations($attributes) {
		$attributes['fi'] = "Finnish";
		return $attributes;
	}

	function wpp_format_address_attribute($data, $property = false, $format = "[street_number] [street_name], [city], [state]") {
		global $wp_properties;

		if(!is_object($property))
			return $data;



		$street_number  = $property->street_number;
		$route  = $property->route;
		$city  = $property->city;
		$state  = $property->state;
		$state_code  = $property->state_code;
		$county  = $property->county;
		$country  = $property->country;
		$postal_code  = $property->postal_code;

		$display_address = $format;


		$display_address = 	str_replace("[street_number]", $street_number,$display_address);
		$display_address = 	str_replace("[street_name]", $route, $display_address);
		$display_address = 	str_replace("[city]", "$city",$display_address);
		$display_address = 	str_replace("[state]", "$state",$display_address);
		$display_address = 	str_replace("[state_code]", "$state_code",$display_address);
		$display_address = 	str_replace("[county]", "$county",$display_address);
		$display_address = 	str_replace("[country]", "$country",$display_address);
		$display_address = 	str_replace("[zip_code]", "$postal_code",$display_address);
		$display_address = 	str_replace("[zip]", "$postal_code",$display_address);
		$display_address =	str_replace("[postal_code]", "$postal_code",$display_address);
		$display_address = 	preg_replace('/^\n+|^[\t\s]*\n+/m', "", $display_address);


		// Remove empty lines
		foreach(explode("\n", $display_address) as $line) {

			$line = trim($line);

			// Remove line if comma is first character
			if(strlen($line) < 3 && (strpos($line, ',') === 1 || strpos($line, ',') === 0))
				continue;

			$return[] = $line;

		}

		//$display_address = 	nl2br($display_address);

		if(is_array($return))
			return implode("\n", $return);

	}


	function wpp_property_stats_add_sold_or_rented($property_stats) {

		$property_stats[for_sale]= __("For Sale",'wpp');
		$property_stats[for_rent]= __("For Rent",'wpp');

		return $property_stats;
	}

	function wpp_property_stats_input_for_rent_make_checkbox($content, $slug, $object) {
		$checked = (get_post_meta($object->ID, $slug, true) == 'true' ? ' checked="true" ': false);
		return "<input type='hidden' name='wpp_data[meta][{$slug}]'  value='false'  /><input type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'  value='true' $checked /> <label for='wpp_meta_{$slug}'>".__('This is a rental property.','wpp')."</label>";
	}


	function wpp_property_stats_input_for_sale_make_checkbox($content, $slug, $object) {
		$checked = (get_post_meta($object->ID, $slug, true) == 'true' ? ' checked="true" ': false);
		return "<input type='hidden'  name='wpp_data[meta][{$slug}]'  value='false' /><input type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'  value='true' $checked /> <label for='wpp_meta_{$slug}'>".__('This property is for sale.','wpp')."</label>";
	}

	/**
	 * Add UI to set custom coordinates on property editing page
 	 *
	 * @since 1.04
	 */
	function wpp_property_stats_input_address($content, $slug, $object) {
		$checked = ((get_post_meta($object->ID, 'manual_coordinates', true) == 'true') ? 'checked="checked"' : false);

		ob_start();

        ?>
        <div class="wpp_attribute_row_address">
        	<?php echo $content; ?>
			<div class="wpp_attribute_row_address_options">
    			<input type="hidden" name="wpp_data[meta][manual_coordinates]" value="false" />
    			<input type="checkbox" id="wpp_manual_coordinates" name="wpp_data[meta][manual_coordinates]" value="true" <?php echo $checked; ?> />
    			<label for="wpp_manual_coordinates"><?php echo __('Set Coordinates Manually (When unchecked the coordinates will be set (and validated) by Google Maps)','wpp'); ?></label>
    			<div id="wpp_coordinates" style="display:none;">
    				<ul>
    					<li>
    			    		<input type="text" id="wpp_meta_latitude" name="wpp_data[meta][latitude]" value="<?php echo get_post_meta($object->ID, 'latitude', true); ?>" />
    			    		<label><?php echo __('Latitude','wpp') ?></label>
    			    		<div class="wpp_clear"></div>
    			    	</li>
    			    	<li>
    			    		<input type="text" id="wpp_meta_longitude" name="wpp_data[meta][longitude]" value="<?php echo get_post_meta($object->ID, 'longitude', true); ?>" />
    			    		<label><?php echo __('Longitude','wpp') ?></label>
    			    		<div class="wpp_clear"></div>
    			    	</li>
    			    </ul>
    			</div>
			</div>
		</div>
		<script>
			if(jQuery('input#wpp_manual_coordinates').attr('checked') == true){
				jQuery('#wpp_coordinates').show();
			}
			jQuery('input#wpp_manual_coordinates').change(function(){
				if(jQuery(this).attr('checked') == true){
					jQuery('#wpp_coordinates').show();
				}else{
					jQuery('#wpp_coordinates').hide();
				}
			});
		</script>
		<?php

		$content = ob_get_contents();
        ob_end_clean();

        return $content;
	}


	/**
	 * Save manually entered coordinates if setting exists
 	 *
	 * @since 1.08
	 */
	function save_property_coordinate_override($post_id, $post_data, $geo_data) {
		global $wp_properties;
		
		if (get_post_meta($post_id, 'manual_coordinates', true) != 'true' &&
			get_post_meta($post_id, 'manual_coordinates', true) != '1') {


			update_post_meta($post_id, 'latitude', $geo_data->latitude);
			update_post_meta($post_id, 'longitude', $geo_data->longitude);
		} else {

			update_post_meta($post_id, 'location', $post_data['wpp_data']['meta'][$wp_properties['configuration']['address_attribute']]);
			update_post_meta($post_id, 'display_address', $post_data['wpp_data']['meta'][$wp_properties['configuration']['address_attribute']]);
			
			update_post_meta($post_id, 'latitude', $post_data['wpp_data']['meta']['latitude']);
			update_post_meta($post_id, 'longitude',$post_data['wpp_data']['meta']['longitude']);
		}

	}

	function wpp_stat_filter_for_rent_fix($value) {
		if($value == '1')
			return __('Yes','wpp');
	}

	function wpp_stat_filter_for_sale_fix($value) {
		if($value == '1')
			return __('Yes','wpp');
	}



	/**
	 * Formats phone number for display
	 *
 	 *
	 * @since 1.0
 	 * @param string $phone_number
 	 * @return string $phone_number
	 */
	function format_phone_number($phone_number) {
		global $wp_properties;

		if($wp_properties['configuration']['property_overview']['format_phone_number'] == 'true') {
            $phone_number = preg_replace("[^0-9]",'',$phone_number);
            if(strlen($phone_number) != 10) return(False);
            $sArea = substr($phone_number,0,3);
            $sPrefix = substr($phone_number,3,3);
            $sNumber = substr($phone_number,6,4);
            $phone_number = "(".$sArea.") ".$sPrefix."-".$sNumber;
		}

		return $phone_number;
	}

	/**
	 * Adds option 'format phone number' to settings of property page
	 */
	function add_format_phone_number_checkbox() {
	    global $wp_properties;

	    $format_phone_number_text = __('Format phone number.','wpp');
        echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][format_phone_number]&label=$format_phone_number_text", $wp_properties['configuration']['property_overview']['format_phone_number']);
	}

	/**
	 * Add "city" as an inheritable attribute for city property_type
	 *
	 * Modifies $wp_properties['property_inheritance'] in WPP_F::settings_action(), overriding database settings
	 *
	 * @since 1.0
 	 * @param array $property_inheritance
 	 * @return array $property_inheritance
	 */
	function add_city_to_inheritance($property_inheritance) {

		$property_inheritance[floorplan][] = 'city';

		return $property_inheritance;
	}

	/**
	 * Adds city to searchable
	 *
 	 * Modifies $wp_properties['searchable_attributes'] in WPP_F::settings_action(), overriding database settings
 	 *
	 * @since 1.0
 	 * @param string $area
 	 * @return string $area
	 */
	function add_city_to_searchable($array) {

		array_push($array, 'city');

		return $array;
	}


	/**
	 * Adds "sq. ft." to the end of all area attributes
	 *
 	 *
	 * @since 1.0
 	 * @param string $area
 	 * @return string $area
	 */
	function add_square_foot($area) {

		return $area . __(" sq ft.",'wpp');
	}




	/**
	 * Demonstrates how to add a new attribute to the property class
	 *
	 * @since 1.08
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */
	function add_display_address($property) {
		global $wp_properties;

		// Don't execute function if coordinates are set to manual
		if($property['manual_coordinates'] == 'true')
			return $property;

		$display_address = $wp_properties['configuration']['display_address_format'];

		if(empty($display_address))
			$display_address =  "[street_number] [street_name], [city], [state]";

		$display_address_code = $display_address;

		// Check if property is supposed to inehrit the address
		if(isset($property['parent_id'])
			&& is_array($wp_properties['property_inheritance'][$property['property_type']])
				&& in_array($wp_properties['configuration']['address_attribute'], $wp_properties['property_inheritance'][$property[property_type]])) {

			if(get_post_meta($property[parent_id], 'address_is_formatted', true)) {
				$street_number = get_post_meta($property[parent_id],'street_number', true);
				$route = get_post_meta($property[parent_id],'route', true);
				$city = get_post_meta($property[parent_id],'city', true);
				$state = get_post_meta($property[parent_id],'state', true);
				$state_code = get_post_meta($property[parent_id],'state_code', true);
				$postal_code = get_post_meta($property[parent_id],'postal_code', true);
				$county = get_post_meta($property[parent_id],'county', true);
				$country = get_post_meta($property[parent_id],'country', true);

				$display_address = str_replace("[street_number]", $street_number,$display_address);
				$display_address = str_replace("[street_name]", $route, $display_address);
				$display_address = str_replace("[city]", "$city",$display_address);
				$display_address = str_replace("[state]", "$state",$display_address);
				$display_address = str_replace("[state_code]", "$state_code",$display_address);
				$display_address = str_replace("[country]", "$country",$display_address);
				$display_address = str_replace("[county]", "$county",$display_address);
				$display_address = str_replace("[zip_code]", "$postal_code",$display_address);
				$display_address = str_replace("[zip]", "$postal_code",$display_address);
				$display_address = str_replace("[postal_code]", "$postal_code",$display_address);
				$display_address =	preg_replace('/^\n+|^[\t\s]*\n+/m', "", $display_address);
				$display_address = nl2br($display_address);

			}
		} else {

			// Verify that address has been converted via Google Maps API
			if($property[address_is_formatted]) {

					$street_number  = $property[street_number];
					$route  = $property[route];
					$city  = $property[city];
					$state  = $property[state];
					$state_code  = $property[state_code];
					$country  = $property[country];
					$postal_code  = $property[postal_code];
					$county  = $property[county];

					$display_address = str_replace("[street_number]", $street_number,$display_address);
					$display_address = str_replace("[street_name]", $route, $display_address);
					$display_address = str_replace("[city]", "$city",$display_address);
					$display_address = str_replace("[state]", "$state",$display_address);
					$display_address = str_replace("[state_code]", "$state_code",$display_address);
					$display_address = str_replace("[country]", "$country",$display_address);
					$display_address = str_replace("[county]", "$county",$display_address);
					$display_address = str_replace("[zip_code]", "$postal_code",$display_address);
					$display_address = str_replace("[zip]", "$postal_code",$display_address);
					$display_address = str_replace("[postal_code]", "$postal_code",$display_address);
					$display_address =	preg_replace('/^\n+|^[\t\s]*\n+/m', "", $display_address);
					$display_address = nl2br($display_address);

			}

		}


		// If somebody is smart enough to do the following with regular expressions, let us know!

		$comma_killer = explode(",", $display_address);

		if(is_array($comma_killer))
			foreach($comma_killer as $key => $addy_line)
				if(isset($addy_line))
					if(trim($addy_line) == "")
						unset($comma_killer[$key]);

		$display_address  = implode(", ", $comma_killer);

		$empty_line_killer = explode("<br />", $display_address);

		if(is_array($empty_line_killer))
			foreach($empty_line_killer as $key => $addy_line)
				if(isset($addy_line))
					if(trim($addy_line) == "")
						unset($empty_line_killer[$key]);


		if(is_array($empty_line_killer))
			$display_address  = implode("<br />", $empty_line_killer);


		$property['display_address'] = apply_filters('wpp_display_address', $display_address, $property);


		// Don't return if result matches the
		if(str_replace(array(" ", "," , "\n"), "", $display_address_code) == str_replace(array(" ", "," , "\n"), "", $display_address))
			$property['display_address'] = "";


		return $property;
	}


	/**
	 * Demonstrates how to add dollar signs before all prices and deposits
	 *
	 * @since 1.1
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */
	function add_dollar_sign($content) {
		global $wp_properties;

		$currency_symbol = (!empty($wp_properties['configuration']['currency_symbol']) ? $wp_properties['configuration']['currency_symbol'] : "$");

		$content = str_replace(",", "", $content);

		if ( !is_numeric($content) && substr_count($content, '-')){
			$hyphen_between = explode('-', $content);
			return $currency_symbol . number_format($hyphen_between[0]) . ' - ' . $currency_symbol . number_format($hyphen_between[1]);
				} elseif (!is_numeric($content)) {

					return $content;

				} else {

			return $currency_symbol . number_format($content);
			}
	}

	/**
	 * Display latitude and longitude on listing edit page below address field
	 *
	 * Echos html content to be displayed after location attribute on property edit page
	 *
	 * @since 1.0
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */
		function wpp_show_coords($listing_id = false) {

			if(!$listing_id)
				return;

			// If latitude and logitude meta isn't set, returns false
			$coords = WPP_F::get_coordinates($listing_id);

			if($coords) {
				_e("<span class='description'>Address was validated by Google Maps.</span>",'wpp');
			} else {
				_e("<span class='description'>Address has not yet been validated, should be formatted as: street, city, state, postal code, country. Locations are validated through Google Maps.</span>",'wpp');
			}

		}


	/**
	 * Add week/month dropdown after price
	 *
	 * Displays a hidden field on property edit page setting the property price frequency
	 *
	 * @since 1.0
 	 * @param string $listing_id Listing ID must be passed
	 */
	function wpp_show_week_month_selection($listing_id = false) {
		if(!$listing_id)
			return;

		echo '<input type="hidden" name="wpp_data[meta][price_per]" value="month" />';

		/*

		Uncomment the following to allow the editor to select if price is monthly and weekly.
		Or add your own frequencies.

			<select id="wpp_meta_price_per" name="wpp_data[meta][price_per]">
				<option value=""></option>
				<option <?php if(get_post_meta($listing_id, 'price_per', true) == 'week') echo "SELECTED"; ?> value="week">week</option>
				<option <?php if(get_post_meta($listing_id, 'price_per', true) == 'month') echo "SELECTED"; ?> value="month">month</option>
			</select>.
		*/
	}


	/**
	 *
	 * Group search values
	 *
	 */
	function group_search_values($values) {
		$result = array();

		if(!is_array($values)) {
		    return $values;
		}

		$min = 0;
		$max = 0;
		$control = false;

		for($i=0; $i<count($values); $i++) {
		    $value = (int)$values[$i];
		    if(!$control && $min == 0 && $value != 0) {
		        $control = true;
		        $min = $value;
		    } elseif($value < $min) {
		        $min = $value;
		    } elseif($value > $max) {
		        $max = $value;
		    }
		}

		$range = $max-$min;

		if($range == 0) {
		    return $values;
		}

		$s = round($range/10);
		$stepup = ($s > 1)?$s:1;

		$result[] = $min;
	    for($i= ($min + $stepup); $i<$max; $i) {
	        $result[] = $i;
		    $i = $i + $stepup;
		}
		$result[] = $max;

	    return $result;
	}


	?>
