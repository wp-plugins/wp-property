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


	// Load settings out of database to overwrite defaults from action_hooks.
	$wp_properties_db = get_option('wpp_settings');


	

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
	
	// Default setings for [property_overview] shortcode
	$wp_properties['configuration']['address_attribute'] = 'location';
	
	
 	// Default setings for admin UI
	$wp_properties['configuration']['admin_ui'] = array(
		'overview_table_thumbnail_size' => 'tiny_thumb');
			
 
	// Setup property types to be used.
	if(!is_array($wp_properties_db['property_types']))
		$wp_properties['property_types'] =  array(
			'building' => "Building",
			'floorplan' => "Floorplan"
		);
	
 
	// Setup property types to be used.
	if(!is_array($wp_properties_db['property_inheritance']))
 	$wp_properties['property_inheritance'] =  array(
		'floorplan' => array("street_number", "route", "city", 'state', 'postal_code', 'location', 'display_address', 'address_is_formatted'));
	

	// Property stats. Can be searchable, displayed as input boxes on editing page.
	if(!is_array($wp_properties_db['property_stats']))
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
	if(!is_array($wp_properties_db['property_meta']))
	$wp_properties['property_meta'] =  array(
		'lease_terms' => 'Lease Terms',
		'pet_policy' => 'Pet Policy',
		'school' => 'School',
 		'tagline' => 'Tagline'
	);
	
	// On property editing page - determines which fields to hide for a particular property type 
	if(!is_array($wp_properties_db['hidden_attributes']))
	$wp_properties['hidden_attributes'] = array(
		'floorplan' => array('location', 'parking', 'school'), /*  Floorplans inherit location. Parking and school are generally same for all floorplans in a building */
		'building' => array('price', 'bedrooms', 'bathrooms', 'area', 'deposit'
	));
	
	
 	// Determines property types that have addresses. 
	if(!is_array($wp_properties_db['location_matters']))
		$wp_properties['location_matters'] = array('building');
	
 	
	/**
	 *
	 * Searching and Filtering
	 *
	 */

	// Determine which property types should actually be searchable. 
	if(!is_array($wp_properties_db['searchable_property_types']))
	$wp_properties['searchable_property_types'] =  array(
		'floorplan'
	);


	// Attributes to use in searching.
	if(!is_array($wp_properties_db['searchable_attributes']))
	$wp_properties['searchable_attributes'] =  array(
 		'area',
 		'deposit',
 		'bedrooms',
 		'bathrooms',
		'city',
		'price'
	);

	
	// Convert phrases to searchabe values.  Converts string stats into numeric values for searching and filtering.
	if(!is_array($wp_properties_db['search_conversions']))
	$wp_properties['search_conversions'] =array(
		'bedrooms' => array(
			'Studio' => '0.5'
	));


 
	
	/**
	 *
	 * Display and UI related filters
	 *
	 */
	
	// Don't load defaults if settings exist in db
	if(!is_array($wp_properties_db['image_sizes']))
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
 

	// Overwrite $wp_properties with database setting
	$wp_properties = UD_F::array_merge_recursive_distinct($wp_properties, $wp_properties_db);
 
 
?>