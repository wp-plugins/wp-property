<?php
/**
 * WP-Property Core Framework
 *
 * @version 1.1
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

/**
 * WP-Directory Core Framework Class
 *
 * Contains primary functions for setting up the framework of the plugin.
 *
 * @version 1.0
 * @package WP-Directory
 * @subpackage Main
 */
class WPP_Core {
	/**
	 * Primary function of WPP_Core, gets called by init.
	 *
	 * Creates and sets up the 'listing' post type.
	 * Creates taxonomies: skills, geo_tag, and industry 
	 *
	 * @since 1.0
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
		wp_register_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=true');
		

  		// Load premium features
		WPP_F::load_premium();

		// Load UD scripts
		//UD_UI::use_ud_scripts();

		// Add troubleshoot log page
		//UD_F::add_log_page();

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
		
		
		// Register a sidebar for each property type
		foreach($wp_properties['property_types'] as $property_slug => $property_title)
			register_sidebar( array(
				'name'=>"Property: $property_title",
				'id' => "wpp_sidebar_$property_slug"
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
		
		// Fix 404 errors
		add_filter("parse_query", array($this, "fix_404"));
 
		// Has to be called everytime, or else the custom slug will not work
		$wp_rewrite->flush_rules();
	}
 
 
	/**
	 * Fixed property pages being seen as 404 pages
	 *
	 * WP handle_404() function decides if current request should be a 404 page
	 * Marking the global variable $wp_query->is_search to true makes the function
	 * assume that the request is a search.
 	 * 
	 * @return string|$request a modified request to query listings
	 * @since 1.0
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
	
    
    function property_row_actions($actions) {
    
        print_r($actions);
        return false;
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


	function edit_columns($columns) {
		global $wp_properties;
		
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Title",
 			"type" => "Type",
			"description" => "Description",
 			"features" => "Features",
   		);
		
		if(is_array($wp_properties['property_stats'])) {
			foreach($wp_properties['property_stats'] as $slug => $title)
				$columns[$slug] = $title;
		} else {
			$columns = $columns;
		}
		
		$columns['thumbnail'] = "Thumbnail";
		$columns['menu_order'] = "Sort Order";
		$columns['featured'] = "Featured Listing";
		return $columns;
	}
	
	function custom_columns($column)
	{
		global $post, $wp_properties;
		$post_id = $post->ID;
		$property = WPP_F::get_property($post->ID, "get_children=false");
		
		switch ($column)
		{
			case "description":
				the_excerpt();
			break;
			
			case "type":
				$property_type = get_post_meta( $post_id, 'property_type', true );
				echo $wp_properties['property_types'][$property_type];
			break;
			
			case "location":
				
				// Only show this is the property type has an address
				if(in_array($property['property_type'], $wp_properties['location_matters'])) {
				
				echo $property['location']. "<br />";				
								
				if($property['coordinates_set'])
					echo "Coordinates found, <a href='http://maps.google.com/maps?q={$property[latitude]},+{$property[longitude]}+%28" . str_replace(" ", "+",$property['post_title']). "%29&iwloc=A&hl=en' target='_blank'>view on map</a>.";
				else
					echo "Coordinates not set.";
				
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
				$width = (int) 75;
				$height = (int) 75;
			
				// thumbnail of WP 2.9
				$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
				// image from gallery
				$attachments = get_children( array('post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image') );
				if ($thumbnail_id)
					$thumb = wp_get_attachment_image( $thumbnail_id, array($width, $height), true );
				elseif ($attachments) {
					foreach ( $attachments as $attachment_id => $attachment ) {
						$thumb = wp_get_attachment_image( $attachment_id, array($width, $height), true );
					}
				}
					if ( isset($thumb) && $thumb ) {
						echo $thumb;
					} else {
						echo __(' ');
					}

			break;
			
			case "featured":
				if($property['featured'])
					echo "Featured.";
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
	
	function admin_menu() {
		global $wp_properties;
		
		do_action('wpp_admin_menu');
		
		// Create property settings page
		$settings_page =  add_options_page( 'Properties', 'Properties', 'manage_options', 'property_settings', create_function('','global $wp_properties; include "ui/page_settings.php";')); 
		
		// Load jQuery UI Tabs and Cookie into settings page
		add_action('admin_print_scripts-' . $settings_page, create_function('', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');"));

	}
	
	
	/**
	 * Performs front-end pre-header functionality
	 *

	 * This function is not called on amdin side
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
		
 
 
			
  			$type = $property[property_type];

			
			
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
 		
		WPP_F::settings_action();
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
	 * @since 1.0
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