<?php
/**
 * WP-Property Actions and Hooks File
 *
 * Do not modify arrays found in these files, use the filters to modify them in your functions.php file
 * Sets up default settings and loads a few actions.
 *
 * Documentation: http://twincitiestech.com/plugins/wp-property/api-documentation/
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @link http://twincitiestech.com/plugins/wp-property/api-documentation/
 * @version 1.1
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

	// Add some default actions
	add_filter("wpp_stat_filter_price", 'add_dollar_sign');
	add_filter("wpp_stat_filter_deposit", 'add_dollar_sign');
	add_filter('wpp_get_property', 'add_display_address');
	add_action('wpp_ui_after_attribute_location', 'wpp_show_coords');
	add_action('wpp_ui_after_attribute_price', 'wpp_show_week_month_selection');

		
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


	/**
	 *
	 * System-wide Filters and Settings
	 *
	 */

	// This slug will be used to display properties on the front-end.  Most likely overwriten by get_option('wpp_settings');
	$wp_properties['configuration'] = array(
		'autoload_css' => 'true',
		'automatically_insert_overview' => 'true',
		'base_slug' => 'property',
		'gm_zoom_level' => '13');
			
	// Default setings for [property_overview] shortcode
	$wp_properties['configuration']['property_overview'] = array(
		'thumbnail_size' => 'tiny_thumb',
		'fancybox_preview' => 'true',
		'display_slideshow' => 'false',
		'show_children' => 'true');
						
	// Default setings for [property_overview] shortcode
	$wp_properties['configuration']['single_property_view'] = array(
		'map_image_type' => 'tiny_thumb',
		'gm_zoom_level' => '13');
			
 	// Default setings for admin UI
	$wp_properties['configuration']['admin_ui'] = array(
		'overview_table_thumbnail_size' => 'tiny_thumb');
			
 
	// Setup property types to be used.
 	$wp_properties['property_types'] =  array(
		'building' => "Building",
		'floorplan' => "Floorplan"
	);
 
	// Setup property types to be used.
 	$wp_properties['property_inheritance'] =  array(
		'floorplan' => array("street_number", "route", "city", 'state', 'postal_code', 'location', 'display_address', 'address_is_formatted'));

	// Property stats. Can be searchable, displayed as input boxes on editing page.
	$wp_properties['property_stats'] =  array(
		'location' => 'Address',
		'price' => 'Price',
		'bedrooms' => 'Bedrooms',
		'bathrooms' => 'Bathrooms',
		'deposit' => 'Deposit',
		'area' => 'Area',
		'phone_number' => 'Phone Number',
		'deposit' => 'Deposit'
	);

	// Property meta.  Typically not searchable, displayed as textarea on editing page.
	$wp_properties['property_meta'] =  array(
		'lease_terms' => 'Lease Terms',
		'pet_policy' => 'Pet Policy',
		'school' => 'School',
 		'tagline' => 'Tagline'
	);


	
	/**
	 *
	 * Searching and Filtering
	 *
	 */

	// Determine which property types should actually be searchable. 
	$wp_properties['searchable_property_types'] =  array(
		'floorplan'
	);

	// Attributes to use in searching.
	$wp_properties['searchable_attributes'] =  array(
 		'area',
 		'deposit',
 		'bedrooms',
 		'bathrooms',
		'city',
		'price'
	);
	
	// Convert phrases to searchabe values.  Converts string stats into numeric values for searching and filtering.
	$wp_properties['search_conversions'] =array(
		'bedrooms' => array(
			'Studio' => '0.5'
	));


 
	
	/**
	 *
	 * Display and UI related filters
	 *
	 */
	
	// Setup image sizes that will be used in your theme..
	$wp_properties['image_sizes'] = array(
		'map_thumb' => array('width'=> '75', 'height' => '75'),
 		'tiny_thumb' => array('width'=> '100', 'height' => '100'),
		'sidebar_wide' => array('width'=> '195', 'height' => '130'),
 		'slideshow' => array('width'=> '640', 'height' => '235')
	);

	// Image URLs.
	$wp_properties['images']['map_icon_shadow'] = WPP_URL . "images/map_icon_shadow.png";
	
	// Default attribute label descriptions for the back-end
	$wp_properties['descriptions'] = array(
		'descriptions' => array(
			'property_type' => 'The property type will determine the layout.',
			'custom_attribute_overview' => 'Customize what appears in search results in the attribute section.  For example: 1bed, 2baths, area varies slightly.',
			'tagline' => 'Will appear on overview pages and on top of every listing page.')
	);
 
	// On property editing page - determines which fields to hide for a particular property type 
	$wp_properties['hidden_attributes'] = array(
		'floorplan' => array('location', 'parking', 'school'), /*  Floorplans inherit location. Parking and school are generally same for all floorplans in a building */
		'building' => array('price', 'bedrooms', 'bathrooms', 'area', 'deposit'
	));
 
 	// Determines property types that have addresses. 
	$wp_properties['location_matters'] = array('building');
	
	// Load settings out of database to overwrite defaults from action_hooks.
	$wp_properties_db = get_option('wpp_settings');

	// Overwrite $wp_properties with database setting
	$wp_properties = UD_F::array_merge_recursive_distinct($wp_properties, $wp_properties_db);
 
?>