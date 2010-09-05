<?php

 
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
	
	add_action("wpp_ui_after_attribute_{$wp_properties['configuration']['address_attribute']}", 'wpp_show_coords');
	add_action('wpp_ui_after_attribute_price', 'wpp_show_week_month_selection');

	/**	
	 * Formats phone number for display
	 *
 	 *
	 * @since 1.0
 	 * @param string $phone_number 
 	 * @return string $phone_number 
	 */
	function format_phone_number($phone_number) {
		
   $phone_number = ereg_replace("[^0-9]",'',$phone_number);
    if(strlen($phone_number) != 10) return(False);
    $sArea = substr($phone_number,0,3);
    $sPrefix = substr($phone_number,3,3);
    $sNumber = substr($phone_number,6,4);
    $phone_number = "(".$sArea.")".$sPrefix."-".$sNumber; 
	
		return $phone_number;
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
	
		return $area . " sq.ft";
	}


	
	
	/**	
	 * Demonstrates how to add a new attribute to the property class
	 *
	 * @since 1.0
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */
	function add_display_address($property) {
		global $wp_properties;
		
		// Check if property is supposed to inehrit the address		
		if(isset($property[parent_id]) 
			&& is_array($wp_properties['property_inheritance'][$property[property_type]]) 
				&& in_array($wp_properties['configuration']['address_attribute'], $wp_properties['property_inheritance'][$property[property_type]])) {
		
			if(get_post_meta($property[parent_id], 'address_is_formatted', true)) {
				$street_number = get_post_meta($property[parent_id],'street_number', true);
				$route = get_post_meta($property[parent_id],'route', true);
				$city = get_post_meta($property[parent_id],'city', true);
				$state = get_post_meta($property[parent_id],'state', true);
				$postal_code = get_post_meta($property[parent_id],'postal_code', true);
				
				$display_address = "{$street_number} {$route}<br />{$city}, {$state}, {$postal_code}";
				$property[display_address] = $display_address;
				return $property;
			}
		}
		
		// Verify that address has been converted via Google Maps API
		if($property[address_is_formatted]) {
		
			$display_address = "{$property[street_number]} {$property[route]}<br />{$property[city]}, {$property[state]}, {$property[postal_code]}";
		
		}
		$property[display_address] = $display_address;
	
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
		return $currency_symbol . $content;
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
				echo "<span class='description'>Address was validated by Google Maps.</span>";
			} else {
				echo "<span class='description'>Address has not yet been validated, should be formatted as: street, city, state, postal code, country. Locations are validated through Google Maps.</span>";
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

	
	?>