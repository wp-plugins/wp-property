<?php

 
	// Add post-thumbnails support
	add_action("after_setup_theme", array('WPP_Core', "after_setup_theme"));	

	// Add some default actions
	add_filter("wpp_stat_filter_price", 'add_dollar_sign');
	add_filter("wpp_stat_filter_deposit", 'add_dollar_sign');
	add_filter("wpp_stat_filter_area", 'add_square_foot');
	add_filter('wpp_get_property', 'add_display_address');
	add_filter('wpp_searchable_attributes', 'add_city_to_searchable');
	add_action("wpp_ui_after_attribute_{$wp_properties['configuration']['address_attribute']}", 'wpp_show_coords');
	add_action('wpp_ui_after_attribute_price', 'wpp_show_week_month_selection');

	function add_square_foot($area) {
	
		return $area . " sq.ft";
	}
	function add_city_to_searchable($array) {
	
		array_push($array, 'city');
		
		return $array;
	}
	
	
	/**	
	 * Demonstrates how to add a new attribute to the property class
	 *
	 * @since 1.0
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */
	function add_display_address($property) {
	
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
	 * @since 1.0
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */	 
	function add_dollar_sign($content) {
		return "$" . $content;
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