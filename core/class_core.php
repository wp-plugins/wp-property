<?php
/**
 * WP-Property Core Framework
 *
 * @version 0.55
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

/**
 * WP-Property Core Framework Class
 *
 * Contains primary functions for setting up the framework of the plugin.
 *
 * @version 0.55
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
	 * @since 0.55
	 * @uses $wp_properties WP-Property configuration array
	 * @uses $wp_rewrite WordPress rewrite object
 	 * @access public
	 *
	 */
	function WPP_Core(){
		global $wp_properties, $wp_rewrite;



		WPP_F::settings_action();

		// Load early so plugins can use them as well
		wp_register_style('jquery-fancybox-css', WPP_URL. '/third-party/fancybox/jquery.fancybox-1.3.1.css');
		wp_register_script('jquery-fancybox', WPP_URL. '/third-party/fancybox/jquery.fancybox-1.3.1.pack.js', array('jquery'), '1.7.3' );
		wp_register_script('jquery-easing', WPP_URL. '/third-party/fancybox/jquery.easing-1.3.pack.js', array('jquery'), '1.7.3' );
		wp_register_script('jquery-slider', WPP_URL. '/js/jquery.ui.slider.min.js', array('jquery'), '1.7.3' );
		wp_register_script('jquery-cookie', WPP_URL. '/js/jquery.cookie.js', array('jquery'), '1.7.3' );
		wp_register_script('wp-property-admin-overview', WPP_URL. '/js/wp-property-admin-overview.js', array('jquery'), '0.55' );
 		wp_register_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=true');


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
			'name' => _x('Properties', 'post type general name'),
			'singular_name' => _x('Property', 'post type singular name'),
			'add_new' => _x('Add New', 'property'),
			'add_new_item' => __('Add New Property'),
			'edit_item' => __('Edit Property'),
			'new_item' => __('New Property'),
			'view_item' => __('View Property'),
			'search_items' => __('Search Properties'),
			'not_found' =>  __('No properties found'),
			'not_found_in_trash' => __('No properties found in Trash'),
			'parent_item_colon' => ''
		);


		// Modify admin body class
		add_filter('admin_body_class', array('WPP_Core', 'admin_body_class'));


		// Register custom post types
		register_post_type('property', array(
			'labels' => $labels,
			'singular_label' => __('Property'),
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


        register_taxonomy( 'property_feature', 'property',
		array(
			 'hierarchical' => false,
			 'label' => __('Rental Features'),
			 'query_var' => 'property_feature',
			 'rewrite' => array('slug' => 'feature' )
		)
		);
        register_taxonomy( 'community_feature', 'property',
		array(
			 'hierarchical' => false,
			 'label' => __('Community Features'),
			 'query_var' => 'community_feature',
			 'rewrite' => array('slug' => 'community_feature' )
		)
		);

		// Ajax functions
		add_action('wp_ajax_wpp_ajax_property_query', create_function("",' $class = WPP_F::get_property($_REQUEST["property_id"]); if($class)  print_r($class); else echo "No property found."; die();'));
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
				'name'=>"Property: $property_title",
				'id' => "wpp_sidebar_$property_slug",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>',
				) );

 		add_shortcode('property_overview', array($this, 'shortcode_property_overview'));

		foreach($wp_properties['image_sizes'] as $image_name => $image_sizes)
			add_image_size($image_name, $image_sizes['width'], $image_sizes['height'], true);


		register_taxonomy_for_object_type('property_features', 'property');

		add_filter("manage_edit-property_columns", array(&$this, "edit_columns"));
		add_action("manage_pages_custom_column", array(&$this, "custom_columns"));


		// Called in setup_postdata().  We add property values here to make available in global $post variable on frontend
		add_action('the_post', array('WPP_F','the_post'));


		// Register custom taxonomy
		register_taxonomy("speaker", array("podcast"), array("hierarchical" => true, "label" => "Speakers", "singular_label" => "Speaker", "rewrite" => true));

		add_action("the_content", array(&$this, "the_content"));

		// Admin interface init
		add_action("admin_init", array(&$this, "admin_init"));
	    add_action('admin_print_styles', array('WPP_Core', 'admin_css'));
		add_action("template_redirect", array(&$this, 'template_redirect'));
		add_action("admin_menu", array(&$this, 'admin_menu'));

		// Insert post hook
		//add_action("wp_insert_post", array(&$this, "wp_insert_post"), 10, 2);
		add_action("post_submitbox_misc_actions", array(&$this, "post_submitbox_misc_actions"));
		add_action('save_post', array($this, 'save_property'));
		add_filter('post_updated_messages', array('WPP_Core', 'property_updated_messages'));


		add_filter('page_row_actions', array('WPP_Core', 'property_row_actions'),0,2);

		// Fix 404 errors
		add_filter("parse_query", array($this, "fix_404"));

 		add_theme_support( 'post-thumbnails' );

		// Load admin header scripts
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

		// Check premium feature availability
		add_action('wpp_premium_feature_check', array('WPP_F', 'feature_check'));		
		
		// Has to be called everytime, or else the custom slug will not work
		$wp_rewrite->flush_rules();
	}


	/**
	 * Adds "Settings" link to the plugin overview page
	 *
	 *
 	 * @since 0.55
	 *
	 */
	 function plugin_action_links( $links, $file ){

 		if ( $file == 'wp-property/wp-property.php' ){
			$settings_link =  '<a href="'.admin_url("options-general.php?page=property_settings").'">' . __('Settings') . '</a>';
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
		$settings_page =  add_options_page( 'Properties', 'Properties', 'manage_options', 'property_settings', create_function('','global $wp_properties; include "ui/page_settings.php";'));

 		
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
				$settings_page =  admin_url('options-general.php?page=property_settings');
				$permalink_problem = (get_option('permalink_structure') == '' 
					? "Be advised, since you don't have permalinks enabled, you must visit the <a href='$settings_page'>Settings Page</a> and set a custom property overview page." 
					: "By default, your property overview will be displayed on the <a href='$default_url'>$default_url</a> page. You may change the overview page on the <a href='$settings_page'>Settings Page</a>. ");
				?>
				
				<script type="text/javascript">
					jQuery(document).ready(function() {
 						
						var message = "<div class='updated fade'>" +
							"<p><b>Thank you for installing WP-Property!</b> " +
							"<?php echo $permalink_problem; ?></p>" +
							"<p>You may also visit <a href='http://twincitiestech.com/plugins/wp-property/'>TwinCitiesTech.com</a> for more information, and <a href='http://twincitiestech.com/plugins/wp-property/screencasts/'>screencasts</a>.</div>";
						
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

		if(empty($wp_properties[configuration][base_slug]))
			return $content;

		if($wp_properties[configuration][base_slug] == $post->post_name && $wp_properties[configuration][automatically_insert_overview] == 'true')
			return WPP_Core::shortcode_property_overview();

		return $content;
	}

	function save_property($post_id) {
		global $wp_rewrite, $wp_properties;

		if (!wp_verify_nonce( $_POST['_wpnonce'],'update-property_' . $post_id))
			return $post_id;


		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return $post_id;

		/*
		if ( !current_user_can( 'edit_property', $post_id) )
			return $post_id;
		*/

		$update_data = $_REQUEST[wpp_data][meta];

		foreach($update_data as $meta_key => $meta_value) {

			//Remomve certain characters
			if($meta_key == 'price')
				 $meta_value = str_replace("$" , '', $meta_value);

			if($meta_key == 'deposit')
				 $meta_value = str_replace("$" , '', $meta_value);


			update_post_meta($post_id, $meta_key, $meta_value);
 		}

			// Update Coordinates
		if(!empty($update_data['location'])) {

			$geo_data = UD_F::geo_locate_address($update_data['location']);

			if($geo_data) {
				update_post_meta($post_id, 'address_is_formatted', true);
				update_post_meta($post_id, 'location', $geo_data->formatted_address);
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


		// Update Counts
		update_option('wpp_counts', WPP_F::get_search_values());


		$wp_rewrite->flush_rules();

		return true;
 	}

/*
	Inserts content into the "Publish" metabox on property pages
*/
	function post_submitbox_misc_actions() {

		global $post, $action;

		if($post->post_type == 'property') {

			$featured = get_post_meta($post->ID, 'featured', true);
			$disable_slideshow = get_post_meta($post->ID, 'disable_slideshow', true);
			?>
			<div class="misc-pub-section ">

			<ul>
				<li>Menu Sort Order: <?php echo UD_UI::input("name=menu_order&special=size=4&value={$post->menu_order}"); ?></li>
				<li><?php echo UD_UI::checkbox("name=wpp_data[meta][featured]&label=Display property in featured listing.", $featured); ?></li>
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
		1 => sprintf( __('Property updated. <a href="%s">View property</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Property updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Property restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Property published. <a href="%s">View property</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Property saved.'),
		8 => sprintf( __('Property submitted. <a target="_blank" href="%s">Preview property</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Property scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview property</a>'),
		  // translators: Publish box date format, see http://php.net/date
		  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Property draft updated. <a target="_blank" href="%s">Preview property</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
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
		$columns['title'] = "Title";
		$columns['type'] = "Type";

		if(is_array($wp_properties['property_stats'])) {
			foreach($wp_properties['property_stats'] as $slug => $title)
				$columns[$slug] = $title;
		} else {
			$columns = $columns;
		}

		$columns['description'] = "Description";
		$columns['features'] = "Features";
		$columns['overview'] = "Overview";
		$columns['featured'] = "Featured";
		$columns['menu_order'] = "Order";
		$columns['date'] = "Date";
		$columns['author'] = "Published By";
		$columns['thumbnail'] = "Thumbnail";

		return $columns;
	}

	function custom_columns($column) {
		global $post, $wp_properties;
		$post_id = $post->ID;
		$property = WPP_F::get_property($post->ID);


		switch ($column)
		{
			case "description":
				the_excerpt();
			break;

			case "type":
				$property_type = $property[property_type];
				echo $wp_properties['property_types'][$property_type];
			break;

			case "price":
 				echo apply_filters("wpp_stat_filter_$column", $property[price]);
			break;

			case "location":

				// Only show this is the property type has an address
				if(in_array($property['property_type'], $wp_properties['location_matters'])) {

				echo $property['display_address']. "<br />";

				if($property['address_is_formatted'])
					echo "Validated: <a href='http://maps.google.com/maps?q={$property[latitude]},+{$property[longitude]}+%28" . str_replace(" ", "+",$property['post_title']). "%29&iwloc=A&hl=en' target='_blank'>view on map</a>.";
				else
					echo "Address not validated.";

				}


			break;

			case "overview":

				$overview_stats = $wp_properties['property_stats'];

				unset($overview_stats['phone_number']);

				// Not the best way of doing it, but better than nothing.
				// We basically take all property stats, then dump everything too long and empty
				foreach($overview_stats as $stat => $label) {

					if(empty($property[$stat]) || strlen($property[$stat]) > 15)
						continue;

					echo "$label: " . apply_filters("wpp_stat_filter_$stat", $property[$stat]) . " <br />";

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

				$image_thumb_url = $property[images][$wp_properties[configuration][admin_ui][overview_table_thumbnail_size]];

				if(!empty($image_thumb_url)) {
				?>
					<a href="<?php echo $property[images][large]; ?>" class="fancybox" rel="overview_group" title="<?php echo  $property[post_title]; ?>">
						<img src="<?php echo $image_thumb_url; ?>" />
					</a>

				<?php
				} else {
					echo " - ";
				}

			break;

			case "featured":
				if($property['featured'])
					echo "<input type='button' id='wpp_feature_$post_id' class='wpp_featured_toggle wpp_is_featured' nonce='".wp_create_nonce('wpp_make_featured_' . $post_id)."' value='Featured' />";
				else
					echo "<input type='button' id='wpp_feature_$post_id' class='wpp_featured_toggle' ' nonce='".wp_create_nonce('wpp_make_featured_' . $post_id)."'  value='Feature' />";
			break;

				case "menu_order":
				if($property['menu_order'])
					echo $property['menu_order'];
			break;

			default:
				echo (!empty($property[$column]) ? WPP_F::format_attrib($property[$column], $column) : "");

			break;


		}
	}


	/**
	 * Performs front-end pre-header functionality
	 *
	 * This function is not called on amdin side
	 *
	 * @todo Add function to search through post content for shortcode to only include scripts on property pages
	 *
	 */

	// Template selection
	function template_redirect()
	{
		global $post, $property, $wp, $wp_query, $wp_properties;



		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-slider', WPP_URL . '/js/jquery.ui.slider.min.js', array('jquery-ui-core'), '1.7.2' );
 		wp_enqueue_style('jquery-wpp-ui');

		wp_enqueue_script('google-maps');


		wp_enqueue_script('jquery-fancybox');
 		wp_enqueue_style('jquery-fancybox-css');

		// Find stylesheet
		if ( file_exists( TEMPLATEPATH . '/wp_properties.css') ) {
			wp_register_style('wp_properties_frontend', get_bloginfo('template_url') . '/wp_properties.css');
			wp_enqueue_style( 'wp_properties_frontend');
		} elseif (file_exists( WPP_Templates . '/wp_properties.css') && $wp_properties[configuration][autoload_css] == 'true') {
			wp_register_style('wp_properties_frontend', WPP_URL . '/templates/wp_properties.css');
			wp_enqueue_style( 'wp_properties_frontend');
		}

		// Include template functions
		include WPP_Templates . "/template-functions.php";



		if ($post->post_type == "property")	{

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


		if($wp->request == $wp_properties['configuration']['base_slug']) {




			// If the requested page is the slug, but no post exists, we load our template
			if(!$post) {

				// 1. Try custom template in theme folder
				if(file_exists(TEMPLATEPATH . "/property-overview-page.php")) {
					load_template(TEMPLATEPATH . "/property-overview-page.php");
					die();
				}

				// 4. If all else fails, try the default general template
				if(file_exists(WPP_Templates . "/property-overview-page.php")) {
					load_template(WPP_Templates . "/property-overview-page.php");
					die();
				}

			}

		}




	}

	function admin_init() {
		global $wp_rewrite;


	    add_meta_box( 'property_meta', 'General Information', array('WPP_UI','metabox_meta'), 'property', 'normal' );

		// Add metaboxes
		do_action('wpp_metaboxes');

 	}

	// Admin post meta contents
	function meta_options()
	{
		global $post;
		$custom = get_post_custom($post->ID);
		$length = $custom["p30-length"][0];
?>
<label>Length:</label><input name="p30-length" value="<?php echo $length; ?>" />
<?php
	}

	/**
	 * Displays property overview
	 *
	 * Echos html content to be displayed after location attribute on property edit page
	 *
	 * @since 0.5
	 * @uses WPP_F::get_coordinates() Creates an array from string $args.
	 * @param string $listing_id Listing ID must be passed
	 */
	function shortcode_property_overview($atts = "")  {

		extract(shortcode_atts(array(
			'type' => 'all'),
			$atts));

			ob_start();

			// 1. Try template in theme folder
			if(file_exists(TEMPLATEPATH . "/property-overview.php"))
				include TEMPLATEPATH . "/property-overview.php";


			// 3. Try template in plugin folder
			if(file_exists(WPP_Templates . "/property-overview.php"))
				include WPP_Templates . "/property-overview.php";

			$result .= ob_get_contents();
			ob_end_clean();

		return $result;
	}



}

?>