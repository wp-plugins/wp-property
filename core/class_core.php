<?php
/**
 * WP-Property Core Framework
 *
 * @version 0.60
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

/**
 * WP-Property Core Framework Class
 *
 * Contains primary functions for setting up the framework of the plugin.
 *
 * @version 0.60
 * @package WP-Property
 * @subpackage Main
 */
class WPP_Core {
	/**
	 * Primary function of WPP_Core, gets called by init.
	 *
	 * Creates and sets up the 'listing' post type.
	 * Creates taxonomies: skills, geo_tag, and industry
	 *
	 * @todo  Find a way of not having to call $wp_rewrite->flush_rules(); on every load.
	 * @since 0.60
	 * @uses $wp_properties WP-Property configuration array
	 * @uses $wp_rewrite WordPress rewrite object
 	 * @access public
	 *
	 */
	function WPP_Core(){
		global $wp_properties, $wp_rewrite;

		WPP_F::settings_action();

		// Load early so plugins can use them as well
		wp_register_style('jquery-fancybox-css', WPP_URL. '/third-party/fancybox/jquery.fancybox-1.3.4.css');
        wp_register_style('jquery-ui', WPP_URL. '/css/jquery-ui.css');
		wp_register_script('jquery-fancybox', WPP_URL. '/third-party/fancybox/jquery.fancybox-1.3.4.pack.js', array('jquery'), '1.7.3' );
		wp_register_script('jquery-easing', WPP_URL. '/third-party/fancybox/jquery.easing-1.3.pack.js', array('jquery'), '1.7.3' );
		wp_register_script('jquery-slider', WPP_URL. '/js/jquery.ui.slider.min.js', array('jquery'), '1.7.3' );
		wp_register_script('jquery-cookie', WPP_URL. '/js/jquery.cookie.js', array('jquery'), '1.7.3' );
		wp_register_script('wp-property-admin-overview', WPP_URL. '/js/wp-property-admin-overview.js', array('jquery'), '0.63' );
		wp_register_script('wp-property-global', WPP_URL. '/js/wp-property-global.js', array('jquery'), '0.63' );
 		wp_register_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=true');
		wp_register_script('jquery-quicksand', WPP_URL. '/third-party/jquery.quicksand.js', array('jquery'));
		wp_register_script('jquery-nivo-slider', WPP_URL. '/third-party/jquery.nivo.slider.pack.js', array('jquery'));

			
		// Find and register stylesheet
		if ( file_exists( TEMPLATEPATH . '/wp_properties.css') ) {
			wp_register_style('wp-property-frontend', get_bloginfo('template_url') . '/wp_properties.css',   array(),'0.63' );
		} elseif (file_exists( WPP_Templates . '/wp_properties.css') && $wp_properties[configuration][autoload_css] == 'true') {
			wp_register_style('wp-property-frontend', WPP_URL . '/templates/wp_properties.css',  array(), '0.63' );
		}
		// Find and register MSIE stylesheet
		if ( file_exists( TEMPLATEPATH . '/wp_properties-msie.css') ) {
			wp_register_style('wp-property-frontend-msie', get_bloginfo('template_url') . '/wp_properties-msie.css',   array(),'0.63' );
		} elseif (file_exists( WPP_Templates . '/wp_properties-msie.css') && $wp_properties[configuration][autoload_css] == 'true') {
			wp_register_style('wp-property-frontend-msie', WPP_URL . '/templates/wp_properties-msie.css',  array(), '0.63' );
		}

		// Find front-end JavaScript and register the script
		if ( file_exists( TEMPLATEPATH . '/wp_properties.js') ) {
			wp_register_script('wp-property-frontend', get_bloginfo('template_url') . '/wp_properties.js', array(),'0.63' );
 		} elseif (file_exists( WPP_Templates . '/wp_properties.js')) {
			wp_register_script('wp-property-frontend', WPP_URL . '/templates/wp_properties.js', array(), '0.63' );
 		}



  		// Load premium features
		WPP_F::load_premium();

		// Load UD scripts
		//UD_UI::use_ud_scripts();

		// Add troubleshoot log page
		if($wp_properties[configuration][show_ud_log] == 'true')
			UD_F::add_log_page();

		// Init action hook
		 do_action('wpp_init');

		$labels = array(
			'name' => __('Properties', 'wpp'),
			'singular_name' => __('Property', 'wpp'),
			'add_new' => __('Add New', 'wpp'),
			'add_new_item' => __('Add New Property','wpp'),
			'edit_item' => __('Edit Property','wpp'),
			'new_item' => __('New Property','wpp'),
			'view_item' => __('View Property','wpp'),
			'search_items' => __('Search Properties','wpp'),
			'not_found' =>  __('No properties found','wpp'),
			'not_found_in_trash' => __('No properties found in Trash','wpp'),
			'parent_item_colon' => ''
		);

		// Modify admin body class
		add_filter('admin_body_class', array('WPP_Core', 'admin_body_class'));
		
		//Modify Front-end property body class
		add_filter('body_class', array('WPP_Core', 'properties_body_class'));

		// Register custom post types
		register_post_type('property', array(
			'labels' => $labels,
			'singular_label' => __('Property','wpp'),
			'public' => true,
			'show_ui' => true,
			'_builtin' => false,
			'_edit_link' => 'post.php?post=%d',
			'capability_type' => 'post',
			'hierarchical' => true,
			'rewrite' => array('slug'=>$wp_properties['configuration']['base_slug']),
			'query_var' => $wp_properties['configuration']['base_slug'],
			'supports' => array('title','editor', 'thumbnail'),
			'menu_icon' => WPP_URL . '/images/pp_menu.png'
		));

        register_taxonomy( 'property_feature', 'property', array(
             'hierarchical' => false,
             'label' => __('Features','wpp'),
             'query_var' => 'property_feature',
             'rewrite' => array('slug' => 'feature' )
        ));

        register_taxonomy( 'community_feature', 'property',array(
			 'hierarchical' => false,
			 'label' => __('Community Features','wpp'),
			 'query_var' => 'community_feature',
			 'rewrite' => array('slug' => 'community_feature' )
		));

		// Ajax functions
		add_action('wp_ajax_wpp_ajax_property_query', create_function("",' $class = WPP_F::get_property($_REQUEST["property_id"]); if($class)  print_r($class); else echo __("No property found.","wpp"); die();'));
		add_action('wp_ajax_wpp_ajax_check_plugin_updates', create_function("",'  echo WPP_F::check_plugin_updates(); die();'));

		// Make Property Featured Via AJAX
		 if(wp_verify_nonce($_REQUEST[_wpnonce], "wpp_make_featured_" . $_REQUEST[post_id]))
			add_action('wp_ajax_wpp_make_featured', create_function("",'  $post_id = $_REQUEST[post_id]; echo WPP_F::toggle_featured($post_id); die();'));


		//add_action('wp_ajax_wpp_setup_default_widgets', create_function("",'  echo WPP_F::setup_default_widgets(); die();'));

		// Plug page actions -> Add Settings Link to plugin overview page
		add_filter('plugin_action_links', array('WPP_Core', 'plugin_action_links'), 10, 2 );


		// Register a sidebar for each property type
		foreach($wp_properties['property_types'] as $property_slug => $property_title)
			register_sidebar( array(
				'name'=> sprintf(__('Property: %s', 'wpp'), $property_title),
				'id' => "wpp_sidebar_$property_slug",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
			));

 		add_shortcode('property_overview', array($this, 'shortcode_property_overview'));
 		add_shortcode('property_search', array($this, 'shortcode_property_search'));
 		add_shortcode('featured_properties', array($this, 'shortcode_featured_properties'));

 		//Ajax pagination for property_overview
 		add_action("wp_ajax_wpp_property_overview_pagination", array($this, "ajax_property_overview"));
        add_action("wp_ajax_nopriv_wpp_property_overview_pagination", array($this, "ajax_property_overview"));

		foreach($wp_properties['image_sizes'] as $image_name => $image_sizes)
			add_image_size($image_name, $image_sizes['width'], $image_sizes['height'], true);


		register_taxonomy_for_object_type('property_features', 'property');

		add_filter("manage_edit-property_columns", array(&$this, "edit_columns"));
		add_action("manage_pages_custom_column", array(&$this, "custom_columns"));

		// Called in setup_postdata().  We add property values here to make available in global $post variable on frontend
		add_action('the_post', array('WPP_F','the_post'));

		// Register custom taxonomy
		//register_taxonomy("speaker", array("podcast"), array("hierarchical" => true, "label" => __('Speakers','wpp'), "singular_label" => __('Speaker','wpp'), "rewrite" => true));

		add_action("the_content", array(&$this, "the_content"));

		// Admin interface init
		add_action("admin_init", array(&$this, "admin_init"));
        add_action('admin_print_styles', array('WPP_Core', 'admin_css'));
		add_action("template_redirect", array(&$this, 'template_redirect'));
		add_action("admin_menu", array(&$this, 'admin_menu'));

 		add_action("post_submitbox_misc_actions", array(&$this, "post_submitbox_misc_actions"));
		add_action('save_post', array($this, 'save_property'));
		add_filter('post_updated_messages', array('WPP_Core', 'property_updated_messages'));

		// Fix toggale row actions -> get rid of "Quick Edit" on property rows
		add_filter('page_row_actions', array('WPP_Core', 'property_row_actions'),0,2);

		// Fix 404 errors
		add_filter("parse_query", array($this, "fix_404"));

		// Load admin header scripts
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

		// Check premium feature availability
		add_action('wpp_premium_feature_check', array('WPP_F', 'feature_check'));

		// Has to be called everytime, or else the custom slug will not work
		$wp_rewrite->flush_rules();
	}
	
	
	function after_setup_theme() {
		add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Adds "Settings" link to the plugin overview page
	 *
	 *
 	 * @since 0.60
	 *
	 */
	 function plugin_action_links( $links, $file ){

 		if ( $file == 'wp-property/wp-property.php' ){
			$settings_link =  '<a href="'.admin_url("edit.php?post_type=property&page=property_settings").'">' . __('Settings','wpp') . '</a>';
			array_unshift( $links, $settings_link ); // before other links
		}
		return $links;
	}


	/**
	 * Can enqueue scripts on specific pages, and print content into head
	 *
	 *
	 *	@uses $current_screen global variable
	 * @since 0.53
	 *
	 */

	function admin_enqueue_scripts($hook) {
		global $current_screen, $wp_properties;

		// Include on all pages
		wp_enqueue_script('wp-property-global');

		// Property Overview Page
		if($current_screen->id == 'edit-property') {

			// Get width of overview table thumbnail, and set css
			$thumbnail_attribs = WPP_F::image_sizes($wp_properties[configuration][admin_ui][overview_table_thumbnail_size]);
			$thumbnail_width = (!empty($thumbnail_attribs[width]) ? $thumbnail_attribs[width] : false);


			// Enabldes fancybox js, css and loads overview scripts
			wp_enqueue_script('jquery-fancybox');
			wp_enqueue_script('wp-property-admin-overview');
			wp_enqueue_style('jquery-fancybox-css');



			if($thumbnail_width):
			?>
			<style typ="text/css">
			.wpp_overview .column-thumbnail {width: <?php echo $thumbnail_width + 10; ?>px;}
			.wpp_overview .column-type {width: 90px;}
			.wpp_overview .column-title {width: 230px;}
			.wpp_overview .column-menu_order {width: 50px; }
			.wpp_overview td.column-menu_order {text-align: center; }
			.wpp_overview .column-featured {width: 100px;}
 			</style>
			<?php
			endif;

		}

		// Property Editing Page
		if($current_screen->id == 'property') {


		}	

		if($current_screen->id == 'property_page_property_settings') {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui');
			wp_enqueue_script('jquery-ui-sortable');
			
		}
	}

	/**
	 * Sets up additional pages and loads their scripts
	 *
	 * @since 0.5
	 *
	 */
	function admin_menu() {
		global $wp_properties;

		do_action('wpp_admin_menu');

		// Create property settings page
		$settings_page =  add_submenu_page( 'edit.php?post_type=property', __('Settings','wpp'), __('Settings','wpp'), 'manage_options', 'property_settings', create_function('','global $wp_properties; include "ui/page_settings.php";'));

		// Load jQuery UI Tabs and Cookie into settings page (settings_page_property_settings)
		add_action('admin_print_scripts-' . $settings_page, create_function('', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');"));
		add_action('admin_head-edit.php', array("WPP_Core", "overview_page_scripts"));
	}

	/**
	 * Prints header javascript on admin side
	 *
	 * Called after print_scripts so loaded scripts can be utilized.
	 *
	 * @since 0.54
	 *
	 */
	function overview_page_scripts() {
		global $current_screen, $wp_properties;

		switch ($current_screen->id) {

			// Property Overview Page
			case 'edit-property':

				// If settings not configured
				if(get_option('wpp_settings') == ""):

				$default_url =  UD_F::base_url($wp_properties['configuration']['base_slug']);
				$settings_page =  admin_url('edit.php?post_type=property&page=property_settings');
				$permalink_problem = (get_option('permalink_structure') == '' )
					? sprintf(__('Be advised, since you don\'t have permalinks enabled, you must visit the <a href="%s">Settings Page</a> and set a custom property overview page.', 'wpp'), $settings_page)
					: sprintf(__('By default, your property overview will be displayed on the <a href="%s">%s</a> page. You may change the overview page on the <a href="%s">Settings Page</a>', 'wpp'), $default_url, $default_url, $settings_page);
				?>

				<script type="text/javascript">
					jQuery(document).ready(function() {

						var message = "<div class='updated fade'>" +
							"<p><b><?php _e('Thank you for installing WP-Property!','wpp') ?></b> " +
							"<?php echo $permalink_problem; ?></p>" +
							"<?php _e('<p>You may also visit <a href="http://twincitiestech.com/plugins/wp-property/">TwinCitiesTech.com</a> for more information, and <a href="http://twincitiestech.com/plugins/wp-property/screencasts/">screencasts</a>.</div>', 'wpp') ?>";

 						jQuery(message).insertAfter(".wpp_overview  h2");
					});
				</script>

			<?php endif;

			break;

			case 'property':
			// Property Editing Page
			break;
		}
	}


	/**
	 * Modify admin body class on property pages for CSS
	 *
	 * @return string|$request a modified request to query listings
	 * @since 0.5
	 *
	 */
	 function admin_body_class($content) {
		global $current_screen;


		if($current_screen->id == 'edit-property') {

			return 'wpp_overview';
		}

		if($current_screen->id == 'property') {

			return 'wpp_property_edit';
		}


	 }


	/**
	 * Fixed property pages being seen as 404 pages
	 *
	 * WP handle_404() function decides if current request should be a 404 page
	 * Marking the global variable $wp_query->is_search to true makes the function
	 * assume that the request is a search.
 	 *
	 * @return string|$request a modified request to query listings
	 * @since 0.5
	 *
	 */
	function fix_404($query) {
		global $wp_query, $wp_properties;

		if(empty($wp_properties['configuration']['base_slug']))
			return;


		if($query->query_vars[name] == $wp_properties['configuration']['base_slug']) {
			$query->is_search = true;
		}

 	}

	function the_content($content) {
		global $post, $wp_properties;

		if(empty($wp_properties['configuration']['base_slug']))
			return $content;

		if($wp_properties['configuration']['base_slug'] == $post->post_name && $wp_properties['configuration']['automatically_insert_overview'] == 'true')
			return WPP_Core::shortcode_property_overview();

		return $content;
	}

	function save_property($post_id) {
		global $wp_rewrite, $wp_properties;
		
		//Delete cache files of search values for search widget's form
		$directory = WPP_Path . '/cache/searchwidget';
		if(is_dir($directory)) {
            $dir = opendir($directory);
            while(($cachefile = readdir($dir))){
                if ( is_file ($directory."/".$cachefile)) {
                    unlink ($directory."/".$cachefile);
                }
            }
		}

		if (!wp_verify_nonce( $_POST['_wpnonce'],'update-property_' . $post_id))
			return $post_id;


		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return $post_id;

		/*
		if ( !current_user_can( 'edit_property', $post_id) )
			return $post_id;
		*/

		$update_data = $_REQUEST['wpp_data']['meta'];

		foreach($update_data as $meta_key => $meta_value) {
		
			// Only admins can make features
			if($meta_key == 'featured' && !current_user_can('manage_options'))
				continue;

			//Remove certain characters
			if($meta_key == 'price')
				 $meta_value = str_replace("$" , '', $meta_value);

			if($meta_key == 'deposit')
				 $meta_value = str_replace("$" , '', $meta_value);

			update_post_meta($post_id, $meta_key, $meta_value);
 		}

		// Update Coordinates
		if(!empty($update_data[$wp_properties['configuration']['address_attribute']])) {

			$geo_data = UD_F::geo_locate_address($update_data[$wp_properties['configuration']['address_attribute']], $wp_properties['configuration']['google_maps_localization']);

 
			if($geo_data) {
				update_post_meta($post_id, 'address_is_formatted', true);
				
				if(!empty($wp_properties['configuration']['address_attribute']))
					update_post_meta($post_id, $wp_properties['configuration']['address_attribute'], $geo_data->formatted_address);
				
				update_post_meta($post_id, 'latitude', $geo_data->latitude);
				update_post_meta($post_id, 'longitude', $geo_data->longitude);
				update_post_meta($post_id, 'street_number', $geo_data->street_number);
				update_post_meta($post_id, 'route', $geo_data->route);
				update_post_meta($post_id, 'city', $geo_data->city);
				update_post_meta($post_id, 'county', $geo_data->county);
				update_post_meta($post_id, 'state', $geo_data->state);
				update_post_meta($post_id, 'state_code', $geo_data->state_code);
				update_post_meta($post_id, 'country', $geo_data->country);
				update_post_meta($post_id, 'country_code', $geo_data->country_code);
				update_post_meta($post_id, 'postal_code', $geo_data->postal_code);
			} else {
                update_post_meta($post_id, 'address_is_formatted', false);
            }

		}



		// Check if property has children
		$children = get_children("post_parent=$post_id&post_type=property");

		// Write any data to children properties that are supposed to inherit things
		if(count($children) > 0) {
				//1) Go through all children
				foreach($children as $child_id => $child_data) {

					// Determine child property_type
					$child_property_type = get_post_meta($child_id, 'property_type', true);



					// Check if child's property type has inheritence rules, and if meta_key exists in inheritance array
					if(is_array($wp_properties['property_inheritance'][$child_property_type]))

						foreach($wp_properties['property_inheritance'][$child_property_type] as $i_meta_key) {

							$parent_meta_value = get_post_meta($post_id, $i_meta_key, true);


							// inheritance rule exists for this property_type for this meta_key
							update_post_meta($child_id, $i_meta_key, $parent_meta_value);
							UD_F::log("Updating inherited child meta_data: $i_meta_key - $parent_meta_value for $child_id");

						}
					}
			}
 
		$wp_rewrite->flush_rules();

		return true;
 	}

    /*
     * Inserts content into the "Publish" metabox on property pages
     */
	function post_submitbox_misc_actions() {

		global $post, $action;

		if($post->post_type == 'property') {

			$featured = get_post_meta($post->ID, 'featured', true);
			
			if(class_exists('wpp_slideshow'))
			$disable_slideshow = get_post_meta($post->ID, 'disable_slideshow', true);
			?>
			<div class="misc-pub-section ">

			<ul>
				<li><?php _e('Menu Sort Order:','wpp')?> <?php echo UD_UI::input("name=menu_order&special=size=4",$post->menu_order); ?></li>
				
				<?php if(current_user_can('manage_options')): ?>
				<li><?php $display_property_text = __('Display property in featured listing.','wpp'); echo UD_UI::checkbox("name=wpp_data[meta][featured]&label=$display_property_text", $featured); ?></li>
				<?php endif; ?>
				
 				<?php do_action('wpp_publish_box_options'); ?>
			</ul>

			</div>
			<?php

		}

		return;

	}

	/**
	 * Removes "quick edit" link on property type objects
	 *
	 * Called in via page_row_actions filter
	 *
	 * @since 0.5
	 * @uses $wp_properties WP-Property configuration array
	 * @uses $wp_rewrite WordPress rewrite object
 	 * @access public
	 *
	 */
    function property_row_actions($actions, $post) {
        if($post->post_type != 'property')
            return $actions;

        unset($actions['inline']);

        return $actions;
    }

	function admin_css() {
		global $current_screen;

        if ( file_exists( WPP_Path . '/css/wp_properties_admin.css') ) {
            wp_register_style('myStyleSheets', WPP_URL . '/css/wp_properties_admin.css');
			wp_enqueue_style( 'myStyleSheets');
			
        }

	}
/*
	Custom messages for properties
*/
	function property_updated_messages( $messages ) {

	  $messages['property'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Property updated. <a href="%s">View property</a>','wpp'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.','wpp'),
		3 => __('Custom field deleted.','wpp'),
		4 => __('Property updated.','wpp'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Property restored to revision from %s','wpp'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Property published. <a href="%s">View property</a>','wpp'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Property saved.','wpp'),
		8 => sprintf( __('Property submitted. <a target="_blank" href="%s">Preview property</a>','wpp'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Property scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview property</a>','wpp'),
		  // translators: Publish box date format, see http://php.net/date
		  date_i18n( __( 'M j, Y @ G:i','wpp'), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Property draft updated. <a target="_blank" href="%s">Preview property</a>','wpp'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	  );

	  return $messages;
	}

	/**
	 * Sets up property-type columns
	 *
	 * @since 0.54
	 * @uses $wp_properties WP-Property configuration array
  	 * @access public
	 *
	 */
	function edit_columns($columns) {
		global $wp_properties;

		unset($columns);

		$columns['cb'] = "<input type=\"checkbox\" />";
		$columns['title'] = __('Title','wpp');
		$columns['type'] = __('Type','wpp');

		if(is_array($wp_properties['property_stats'])) {
			foreach($wp_properties['property_stats'] as $slug => $title)
				$columns[$slug] = $title;
		} else {
			$columns = $columns;
		}

		$columns['city'] = __('City','wpp');
	//	$columns['description'] = __('Description','wpp');
	//	$columns['features'] = __('Features','wpp');
		$columns['overview'] = __('Overview','wpp');
		$columns['featured'] = __('Featured','wpp');
		$columns['menu_order'] = __('Order','wpp');
		//$columns['date'] = __('Date','wpp');
		//$columns['author'] = __('Published By','wpp');
		$columns['thumbnail'] = __('Thumbnail','wpp');

		//
		return $columns;
	}

	function custom_columns($column) {
		global $post, $wp_properties;
		$post_id = $post->ID;

		switch ($column)
		{
			case "description":
				the_excerpt();
			break;

			case "type":
				$property_type = $post->property_type;
				echo $wp_properties['property_types'][$property_type];
			break;

			case "price":
 				echo apply_filters("wpp_stat_filter_$column", $post->price);
			break;

			case $wp_properties['configuration']['address_attribute']:

				// Only show this is the property type has an address
				if(in_array($post->property_type, $wp_properties['location_matters'])) {

				echo $post->display_address. "<br />";

				if($post->address_is_formatted)
					echo __('Validated:','wpp') . "<a href='http://maps.google.com/maps?q={$property[latitude]},+{$property[longitude]}+%28" . str_replace(" ", "+",$post->post_title). "%29&iwloc=A&hl=en' target='_blank'>". __('view on map','wpp')."</a>.";
				else
					_e('Address not validated.','wpp');

				}


			break;

			case "overview":

				$overview_stats = $wp_properties['property_stats'];

				unset($overview_stats['phone_number']);

				// Not the best way of doing it, but better than nothing.
				// We basically take all property stats, then dump everything too long and empty
				foreach($overview_stats as $stat => $label) {

					if(empty($post->$stat) || strlen($post->$stat) > 15)
						continue;

					echo "$label: " . apply_filters("wpp_stat_filter_$stat", $post->$stat) . " <br />";

				}


			break;


			case "features":
				$features = get_the_terms($post_id, "property_feature");
 				$features_html = array();

				if($features) {
				foreach ($features as $feature)
					array_push($features_html, '<a href="' . get_term_link($feature->slug, "property_feature") . '">' . $feature->name . '</a>');

				echo implode($features_html, ", ");
				}
			break;

			case "thumbnail":

				$image_thumb_url = $post->images[$wp_properties[configuration][admin_ui][overview_table_thumbnail_size]];

				if(!empty($image_thumb_url)) {
				?>
					<a href="<?php echo $post->images[large]; ?>" class="fancybox" rel="overview_group" title="<?php echo  $post->post_title; ?>">
						<img src="<?php echo $image_thumb_url; ?>" />
					</a>

				<?php
				} else {
					echo " - ";
				}

			break;

			case "featured":

				if(current_user_can('manage_options')) {
					if($post->featured)
						echo "<input type='button' id='wpp_feature_$post_id' class='wpp_featured_toggle wpp_is_featured' nonce='".wp_create_nonce('wpp_make_featured_' . $post_id)."' value='".__('Featured','wpp')."' />";
					else
						echo "<input type='button' id='wpp_feature_$post_id' class='wpp_featured_toggle' ' nonce='".wp_create_nonce('wpp_make_featured_' . $post_id)."'  value='".__('Feature','wpp')."' />";
				} else {
					if($post->featured)
						echo __('Featured','wpp');
					else
						echo "";

				}
			break;

				case "menu_order":
				if($post->menu_order)
					echo $post->menu_order;
			break;

			default:
				echo (!empty($post->$column) ? apply_filters('wpp_stat_filter_' . $column, $post->$column) : "");

			break;


		}
	}


	/**
	 * Performs front-end pre-header functionality
	 *
	 * This function is not called on amdin side
	 *
	 *
	 */
	function template_redirect() {
		global $post, $property, $wp, $wp_query, $wp_properties, $wp_styles;

		// Prepare MSIE css to load on MSIE only
		$wp_styles->add_data( 'wp-property-frontend-msie', 'conditional', 'lte IE 7' );

		// Call on all pages because styles are used in widgets
		wp_enqueue_style('wp-property-frontend');

		// Loaded only on MSIE
		wp_enqueue_style('wp-property-frontend-msie');

		// Include template functions
		include WPP_Templates . "/template-functions.php";

		if($post->post_type == "property")
			$single_page = true;

 		$is_search = (is_array($_REQUEST[wpp_search]) ? true : false);


		if($wp->request == $wp_properties['configuration']['base_slug'] || $wp->query_string == "p=" . $wp_properties['configuration']['base_slug'] || strpos($post->post_content, "property_overview"))
			$overview_page = true;

		// Scripts for both types of views
		if ($single_page || $overview_page)	{

			wp_enqueue_script('jquery-ui-slider', WPP_URL . '/js/jquery.ui.slider.min.js', array('jquery-ui-core'), '1.7.2' );
			wp_enqueue_script('jquery-fancybox');
			wp_enqueue_script('wp-property-frontend');

			wp_enqueue_style('jquery-fancybox-css');
           		wp_enqueue_style('jquery-ui');

		}


		if ($single_page)	{

			// Load Map Scripts
			wp_enqueue_script('google-maps');
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui');
			wp_enqueue_script('jquery-mouse');
  
			// Allow plugins to insert header scripts/styles using wp_head_single_property hook
			do_action('template_redirect_single_property'); 			
			add_action('wp_head', create_function('', "do_action('wp_head_single_property'); "));

			$post = WPP_F::get_property($post->ID, "return_object=true&load_gallery=true");

  			$type = $post->property_type;

			// 1. Try custom template in theme folder
			if(file_exists(TEMPLATEPATH . "/property-$type.php")) {
				load_template(TEMPLATEPATH . "/property-$type.php");
				die();
			}

			// 2. Try general template in theme folder
			if(file_exists(TEMPLATEPATH . "/property.php")) {
				load_template(TEMPLATEPATH . "/property.php");
				die();
			}

			// 3. Try custom template in plugin folder
			if(file_exists(WPP_Templates . "/property-$type.php")) {
				load_template(WPP_Templates . "/property-$type.php");
				die();
			}

			// 4. If all else fails, try the default general template
			if(file_exists(WPP_Templates . "/property.php")) {
				load_template(WPP_Templates . "/property.php");
				die();
			}

		}

 		if($overview_page) {
			// Allow plugins to insert header scripts/styles using wp_head_single_property hook
			do_action('template_redirect_property_overview'); 
			add_action('wp_head', create_function('', "do_action('wp_head_property_overview'); "));

			// If the requested page is the slug, but no post exists, we load our template
			if($is_search || !$post) {
				
				
				// 1. Try custom search-result template in theme folder
				
				if(file_exists(TEMPLATEPATH . "/property-search-result.php")) {
					load_template(TEMPLATEPATH . "/property-search-result.php");
					die();
				}
				
				// 2. Try custom template with property-overview-page.php name in theme folder
				if(file_exists(TEMPLATEPATH . "/property-overview-page.php")) {
					load_template(TEMPLATEPATH . "/property-overview-page.php");
					die();
				}

				// 4. If all else fails, trys the default general template
				if(file_exists(WPP_Templates . "/property-overview-page.php")) {
					load_template(WPP_Templates . "/property-overview-page.php");
					die();
				}

			}

		}

	}

	function admin_init() {
		global $wp_rewrite;
		
		WPP_F::fix_screen_options();


	    add_meta_box( 'property_meta', __('General Information','wpp'), array('WPP_UI','metabox_meta'), 'property', 'normal' );

		// Add metaboxes
		do_action('wpp_metaboxes');

 	}


	/**
	 * Displays featured properties
	 *
	 * Performs searching/filtering functions, provides template with $properties file
	 * Retirms html content to be displayed after location attribute on property edit page
	 *
	 * @since 0.60
 	 * @param string $listing_id Listing ID must be passed
	 */
	function shortcode_featured_properties($atts = "") {
		global $wp_properties;

		$default_property_type = WPP_F::get_most_common_property_type();

		extract(shortcode_atts(array(
			'type' => $default_property_type,
			'class' => 'shortcode_featured_properties',
			'stats' => '',
			'image_type' => 'thumbnail'
            ),$atts));

		// Convert shortcode multi-property-type string to array
		if(strpos($type, ","))
			$type = explode(",", $type);

		// Convert shortcode multi-property-type string to array
		if(!empty($stats)) {
			if(strpos($stats, ",")) 
				$stats = explode(",", $stats);
			if(!is_array($stats))
				$stats = array($stats);
		}

		$properties = WPP_F::get_properties("featured=true&property_type=$type");

		 // Set value to false if nothing returned.
		 if(!is_array($properties))
			return;
		 		 
		ob_start();
		
		// 1. Try custom template in theme folder				
			if(file_exists(TEMPLATEPATH . "/property-featured-shortcode.php")) { 
				include TEMPLATEPATH . "/property-featured-shortcode.php";				

		// 2. Try custom template in defaults folder				
			} elseif(file_exists(WPP_Templates . "/property-featured-shortcode.php")) { 
				include WPP_Templates . "/property-featured-shortcode.php";
			} 
 
		$result .= ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * Shortcode for including search box in page content
	 *
	 */
	function shortcode_property_search($atts = "")  {
		global $post, $wp_properties;
		
		extract(shortcode_atts(array(
 			'searchable_attributes' => '',
			'searchable_property_types' => '',
			'per_page' => '10'
            ),$atts));

		if(empty($searchable_attributes))
			$searchable_attributes = $wp_properties[searchable_attributes];
		else
			$searchable_attributes = explode(",", $searchable_attributes);
				
		if(empty($searchable_property_types))
			$searchable_property_types = $wp_properties[searchable_property_types];
		else
			$searchable_property_types = explode(",", $searchable_property_types);
						
		$widget_id = $post->ID . "_search";			
 
		ob_start();
		echo "<div class='wpp_shortcode_search'>";
		draw_property_search_form($searchable_attributes, $searchable_property_types, $per_page, $widget_id, false);
		echo "</div>";
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
		
		
	}
	
	/**
	 * Displays property overview
	 *
	 * Performs searching/filtering functions, provides template with $properties file
	 * Retirms html content to be displayed after location attribute on property edit page
	 *
	 * @since 0.723
 	 * @param string $listing_id Listing ID must be passed
	 */
	function shortcode_property_overview($atts = "")  {
		global $wp_properties;
		
		$default_property_type = WPP_F::get_most_common_property_type();

        // convert all saved attributes into useful array
        foreach($wp_properties['property_stats'] as $k => $v)
            $to_use[$k] = '';

        if(empty($atts))
            $atts['type'] = 'all';
        $to_use['per_page'] = '10';
        $to_use['starting_row'] = '0';

        // merge with specified by user
        $props_atts = array_merge($to_use, $atts);

        $query = ''; // get the query string from an array with not empty values
        foreach($props_atts as $attr => $value){
            if ($value != '') {
                if ($attr == 'type') $attr = 'property_type'; // need this for better UI and to avoid mistakes
                if ($attr == 'per_page') {$per_page = $value; continue; }
                if ($attr == 'starting_row') {$offset = $value; continue; }
                if ($attr == 'sorter') {$sorter = $value; continue; }
                if ($attr == 'ajax_call') {$ajax_call = $value; continue; }
                if ($attr == 'template') {$template = $value; continue; }
                $query .= $attr.'='.$value;
                if (end($props_atts) !== $value) $query .= '&';
            }
        }

        // default values not used for getting properties
        extract(array(
            'show_children'             => 'true',
            'child_properties_title'    => __('Floor plans at location:','wpp'),
            'fancybox_preview'          => $wp_properties['configuration']['property_overview']['fancybox_preview'],
            'thumbnail_size'            => $wp_properties['configuration']['property_overview']['thumbnail_size'],
            'ajax_call'                 => false,
            'sort_by'                   => ($props_atts['sort_by'])?$props_atts['sort_by']:'menu_order',
            'sort_order'                => ($props_atts['sort_order'] && (strtoupper($props_atts['sort_order']) == 'ASC' || strtoupper($props_atts['sort_order']) == 'DESC'))? strtoupper($props_atts['sort_order']):'ASC'
        ));

 		// Get image sizes for overview/search page
		$thumbnail_sizes = WPP_F::image_sizes($thumbnail_size);
		
		$sortable_attrs = array();
                if (!empty($wp_properties['property_stats']) && $wp_properties['sortable_attributes']) {
                    foreach ($wp_properties['property_stats'] as $slug => $label) {
                        if(in_array($slug, $wp_properties['sortable_attributes'])) {
                            $sortable_attrs[$slug] = $label;
                        }
                    }
                    //If default ORDER_BY attr doesn't exist in sortable attributes we set ORDER_BY by first sortable attribute.
                    if(!empty($sortable_attrs) && !in_array($sort_by, $wp_properties['sortable_attributes'])) {
                        $sort_by = key($sortable_attrs);
                    }
                }

		if(empty($props_atts['ajax_call'])) {
            if(!isset($_REQUEST['wpp_search'])){ // NOT SEARCH - usual use via shortcode
                //$total = count(WPP_F::get_properties($query));
		        $query .= '&pagi='.$offset.'--'.$per_page; // get back limits per page
		        $query .= '&sort_by='.$sort_by;
                $properties = WPP_F::get_properties($query);
                $total = $properties['total'];
                unset($properties['total']); // VERY IMPORTANT!!!
            }else{ // SEARCH RESULTS
                // get rid of empty data that are not inputed by user in search form
                foreach($_REQUEST['wpp_search'] as $k => $v){
                    if(is_array($v) && !empty($v['checked'])) {
                        $searchable_attributes[$k] = 'true';
                    } 
                    elseif (is_array($v) && $k == 'property_type'){
                		$searchable_attributes[$k] = $v;
                	}
                    elseif(($v['min']=='' || $v['min']=='0' || $v['min']=='-1') && $v['max']=='' )
                        continue;
                    else
                        $searchable_attributes[$k] = $v;
                }

                $query = ''; // now we need to form a query string that will get found properties
                if(isset($searchable_attributes)){
                    foreach($searchable_attributes as $attr => $value){
                        if($attr == 'property_type'){
                        $query .= $attr.'=';
                        $count = count($value);
                               for($i=0; $i<$count; $i++){
                                $query .= $value[$i].',';
                               }
                        $query = (substr($query, -1, 1) == ',') ? substr_replace($query,'', -1, 1) : $query;
                        }elseif(is_array($value)){
                            $query .= $attr.'='.$value['min'].'-'.$value['max'];
                        }else{
                            if($value=='-1') continue;
                            if($attr == 'pagi') {
                                $pagi = '&'.$attr.'='.$value;
                                $limits = explode('--', $value);
                                $starting_row = $limits[0];
                                $per_page = $limits[1];
                                continue;
                            }
                            $query .= $attr.'='.$value;
                        }
                        if(end($searchable_attributes) !== $value) $query .= '&';
                    }
                }
                $query = (substr($query, -1, 1) == '&') ? substr_replace($query,'', -1, 1) : $query;
                $query .= $pagi;
                $properties = WPP_F::get_properties($query);
                $total = $properties['total'];
                unset($properties['total']); // VERY IMPORTANT!!!
               
            } // end isset($_REQUEST['wpp_search']

            
            ob_start();
            // 1. Try custom template in theme folder
                if(file_exists(TEMPLATEPATH . "/property-pagination-$type.php")) {
                    include TEMPLATEPATH . "/property-pagination-$type.php";
            // 2. Try custom template in defaults folder
                } elseif(file_exists(WPP_Templates . "/property-pagination-$type.php")) {
                    include WPP_Templates . "/property-pagination-$type.php";
            // 3. Try general template in theme folder
                }elseif(file_exists(TEMPLATEPATH . "/property-pagination.php")) {
                    include TEMPLATEPATH . "/property-pagination.php";
            // 4. If all else fails, try the default general template
                }elseif(file_exists(WPP_Templates . "/property-pagination.php")) {
                    include WPP_Templates . "/property-pagination.php";
                }
            $result .= ob_get_contents();
            ob_end_clean();
		}else{ // !empty($props_atts['ajax_call'])
            $query .= '&pagi='.$offset.'--'.$per_page;
            $properties = WPP_F::get_properties($query);
        }

        // Set value to false if nothing returned.
		if(!is_array($properties))
            $properties = false;

		// Convert variables to booleans
		$ajax_call          = ($ajax_call == 'true' ? true : false);
		$show_children 		= ($show_children == 'true' ? true : false);
		$fancybox_preview   = ($fancybox_preview == 'true' ? true : false);

		ob_start();
		
		// 1. Try custom template in theme folder ("template=" in shortcode)		
			if(file_exists(TEMPLATEPATH . "/property-overview-$template.php")) {
				include TEMPLATEPATH  . "/property-overview-$template.php";
				
		// 2. Try custom template in defaults folder ("template=" in shortcode)		
			} elseif(file_exists(WPP_Templates . "/property-overview-$template.php")) {
				include WPP_Templates  . "/property-overview-$template.php";

		// 3. Try custom template in theme folder
			} elseif(file_exists(TEMPLATEPATH . "/property-overview-$type.php")) {
				include TEMPLATEPATH . "/property-overview-$type.php";
				
		// 4. Try custom template in defaults folder
			} elseif(file_exists(WPP_Templates . "/property-overview-$type.php")) {
				include WPP_Templates . "/property-overview-$type.php";

		// 5. Try general template in theme folder
			} elseif(file_exists(TEMPLATEPATH . "/property-overview.php")) {
				include TEMPLATEPATH . "/property-overview.php";

		// 6. If all else fails, try the default general template
			} elseif(file_exists(WPP_Templates . "/property-overview.php")) {
				include WPP_Templates . "/property-overview.php";
			}

		$result .= ob_get_contents();
		ob_end_clean();

		return $result;
	}



	/**
	 * 
	 * @since 0.723
	 * 
	 */
	 
	function ajax_property_overview()  {
        include_once WPP_Templates . "/template-functions.php";
        
        $params = $_REQUEST;
        if(!empty($params['action']))
        	unset($params['action']);
        	
        if(!empty($params['pagination']))
        	unset($params['pagination']);

       // $params['ajax_call'] = true;
		
		$data = WPP_Core::shortcode_property_overview($params);

		echo $data;
        die();
	}
	
	/**
	 * 
	 * Adds wp-property-listing class in search results and property_overview pages
	 * @since 0.7260
	 *
	 */
    function properties_body_class($classes){
		global  $post, $wp_properties;
		
		if(strpos($post->post_content, "property_overview") || (is_search() && isset($_REQUEST['wpp_search'])) || ($wp_properties['configuration']['base_slug'] == $post->post_name) ) {
    		$classes[] = 'wp-property-listing';
		}
		return $classes;	
    }

}

	

?>
