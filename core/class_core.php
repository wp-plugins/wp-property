<?php
/**
 * WP-Property Core Framework
 *
 * @version 1.08
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package WP-Property
*/


/**
 * WP-Property Core Framework Class
 *
 * Contains primary functions for setting up the framework of the plugin.
 *
 * @version 1.08
 * @package WP-Property
 * @subpackage Main
 */
class WPP_Core {

  /**
   * Highest-level function initialized on plugin load
   *
   * @since 1.11
   *
   */
  function WPP_Core() {
    global $wp_properties;
    
    // Load premium features
    WPP_F::load_premium();
    
    // Hook in init
    add_action('init', array($this, 'init'));

    // Setup template_redirect
    add_action("template_redirect", array($this, 'template_redirect'));

    // Pre-init action hook
    do_action('wpp_pre_init');
    
    /* set WPP capabilities */
    $this->set_capabilities();
  }

  /**
   * Called on init
   *
   * Creates and sets up the 'listing' post type.
   *
   * @todo  Find a way of not having to call $wp_rewrite->flush_rules(); on every load.
   * @since 1.11
   * @uses $wp_properties WP-Property configuration array
   * @uses $wp_rewrite WordPress rewrite object
   * @access public
   *
   */
  function init() {

    global $wp_properties, $wp_rewrite;

    load_plugin_textdomain('wpp', WPP_Path . false, '/wp-property/langs');

    /** Making template-functions global but load after the premium features, giving the premium features priority. */
    include_once WPP_Templates . '/template-functions.php';
    
    // Load settings into $wp_properties and save settings if nonce exists
    WPP_F::settings_action();
    
    // Load early so plugins can use them as well
    wp_register_style('jquery-fancybox-css', WPP_URL. '/third-party/fancybox/jquery.fancybox-1.3.4.css');
    wp_register_style('jquery-colorpicker-css', WPP_URL. '/third-party/colorpicker/colorpicker.css');
    wp_register_style('jquery-ui', WPP_URL. '/css/jquery-ui.css');
    wp_register_script('jquery-fancybox', WPP_URL. '/third-party/fancybox/jquery.fancybox-1.3.4.pack.js', array('jquery'), '1.7.3' );
    wp_register_script('jquery-colorpicker', WPP_URL. '/third-party/colorpicker/colorpicker.js', array('jquery'));
    wp_register_script('jquery-easing', WPP_URL. '/third-party/fancybox/jquery.easing-1.3.pack.js', array('jquery'), '1.7.3' );
    wp_register_script('jquery-cookie', WPP_URL. '/js/jquery.smookie.js', array('jquery'), '1.7.3' );
    wp_register_script('jquery-ajaxupload', WPP_URL. '/js/fileuploader.js', array('jquery'));
    wp_register_script('wp-property-admin-overview', WPP_URL. '/js/wp-property-admin-overview.js', array('jquery'),WPP_Version);
    wp_register_script('wp-property-global', WPP_URL. '/js/wp-property-global.js', array('jquery'),WPP_Version);
    wp_register_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=true');
    wp_register_script('jquery-quicksand', WPP_URL. '/third-party/jquery.quicksand.js', array('jquery'));
    wp_register_script('jquery-nivo-slider', WPP_URL. '/third-party/jquery.nivo.slider.pack.js', array('jquery'));
    wp_register_script('jquery-address', WPP_URL. '/js/jquery.address-1.3.2.js', array('jquery'));
    wp_register_script('jquery-scrollTo', WPP_URL. '/js/jquery.scrollTo-min.js', array('jquery'));
    wp_register_script('jquery-validate', WPP_URL. '/js/jquery.validate.min.js', array('jquery'));
    
    wp_register_script('jquery-ui-widget', WPP_URL. '/js/jquery.ui.widget.min.js', array('jquery-ui-core'));
    wp_register_script('jquery-ui-mouse', WPP_URL. '/js/jquery.ui.mouse.min.js', array('jquery-ui-core'));
    wp_register_script('jquery-ui-slider', WPP_URL. '/js/jquery.ui.slider.min.js', array('jquery-ui-widget', 'jquery-ui-mouse'));
    
    wp_register_script('jquery-data-tables', WPP_URL . "/third-party/dataTables/jquery.dataTables.min.js", array('jquery'));
    wp_register_style('jquery-data-tables', WPP_URL . "/third-party/dataTables/wpp-data-tables.css");
    
    // Find and register stylesheet
    if ( file_exists( STYLESHEETPATH . '/wp_properties.css') ) {
      wp_register_style('wp-property-frontend', get_bloginfo('stylesheet_directory') . '/wp_properties.css',   array(),'1.13' );
    } elseif (file_exists( TEMPLATEPATH . '/wp_properties.css')) {
      wp_register_style('wp-property-frontend', get_bloginfo('template_url') . '/wp_properties.css',  array(),WPP_Version);
    } elseif (file_exists( WPP_Templates . '/wp_properties.css') && $wp_properties['configuration']['autoload_css'] == 'true') {
      wp_register_style('wp-property-frontend', WPP_URL . '/templates/wp_properties.css',  array(),WPP_Version);
      
      // Find and register theme-specific style if a custom wp_properties.css does not exist in theme
      if($wp_properties['configuration']['do_not_load_theme_specific_css'] != 'true' && WPP_F::has_theme_specific_stylesheet()) {
        wp_register_style('wp-property-theme-specific', WPP_URL . "/templates/theme-specific/".get_option('template').".css",  array('wp-property-frontend'),WPP_Version);
      }
    }
    
    // Find front-end JavaScript and register the script
    if ( file_exists( STYLESHEETPATH . '/wp_properties.js') ) {
      wp_register_script('wp-property-frontend', get_bloginfo('stylesheet_directory') . '/wp_properties.js', array('jquery-ui-core'),WPP_Version, true);
    } elseif( file_exists( TEMPLATEPATH . '/wp_properties.js') ) {
      wp_register_script('wp-property-frontend', get_bloginfo('template_url') . '/wp_properties.js', array('jquery-ui-core'), WPP_Version, true);
    } elseif (file_exists( WPP_Templates . '/wp_properties.js')) {
      wp_register_script('wp-property-frontend', WPP_URL . '/templates/wp_properties.js', array('jquery-ui-core'),WPP_Version, true);
    }


    // Check settings data on accord with existing wp_properties data before option updates
    add_filter('wpp_settings_save', array('WPP_Core', 'check_wp_settings_data'), 0, 2);


    // Init action hook
     do_action('wpp_init');


    // Load UD scripts
    //UD_UI::use_ud_scripts();

    // Add troubleshoot log page
    if(isset($wp_properties['configuration']['show_ud_log']) && $wp_properties['configuration']['show_ud_log'] == 'true') {
      UD_F::add_log_page();
    }

    // Setup taxonomies
    $wp_properties['taxonomies'] = apply_filters('wpp_taxonomies', $wp_properties['taxonomies']);


    $labels = array(
      'name' => __('Properties', 'wpp'),
      'all_items' =>  __( 'All Properties', 'wpp'),
      'singular_name' => __('Property', 'wpp'),
      'add_new' => __('Add Property', 'wpp'),
      'add_new_item' => __('Add New Property','wpp'),
      'edit_item' => __('Edit Property','wpp'),
      'new_item' => __('New Property','wpp'),
      'view_item' => __('View Property','wpp'),
      'search_items' => __('Search Properties','wpp'),
      'not_found' =>  __('No properties found','wpp'),
      'not_found_in_trash' => __('No properties found in Trash','wpp'),
      'parent_item_colon' => ''
    );
    
    $labels = apply_filters('wpp_object_labels', $labels);

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
      'capability_type' => array('wpp_property','wpp_properties'),
      'hierarchical' => true,
      'rewrite' => array('slug'=>$wp_properties['configuration']['base_slug']),
      'query_var' => $wp_properties['configuration']['base_slug'],
      'supports' => array('title','editor', 'thumbnail'),
      'menu_icon' => WPP_URL . '/images/pp_menu-1.6.png'
    ));
    
    if($wp_properties['taxonomies']) {
      foreach($wp_properties['taxonomies'] as $taxonomy => $taxonomy_data) {
        
        /* 
        // Commented out for now. Because Taxonomy was modified (Custom Capability was added). 
        // And now it should be overwrited to implement changes. Maxim Peshkov
        if(taxonomy_exists($taxonomy))
          continue;
        //*/
        
        register_taxonomy( $taxonomy, 'property', array(
           'hierarchical' => $taxonomy_data['hierarchical'],
           'label' => $taxonomy_data['label'],
           'labels' => $taxonomy_data['labels'],
           'query_var' => $taxonomy,
           'rewrite' => array('slug' => $taxonomy ),
           'capabilities' => array('manage_terms' => 'manage_wpp_categories')
        ));
      }
    }
    
    // Ajax functions
    add_action('wp_ajax_wpp_ajax_property_query', create_function("",' $class = WPP_F::get_property($_REQUEST["property_id"]); if($class)  print_r($class); else echo __("No property found.","wpp"); die();'));
    add_action('wp_ajax_wpp_ajax_image_query', create_function("",' $class = WPP_F::get_property_image_data($_REQUEST["image_id"]); if($class)  print_r($class); else echo __("No image found.","wpp"); die();'));
    add_action('wp_ajax_wpp_ajax_check_plugin_updates', create_function("",'  echo WPP_F::check_plugin_updates(); die();'));
    add_action('wp_ajax_wpp_ajax_revalidate_all_addresses', create_function("",'  echo WPP_F::revalidate_all_addresses(); die();'));
    add_action('wp_ajax_wpp_ajax_list_table', create_function("", ' die(WPP_F::list_table());'));
    
    // Make Property Featured Via AJAX
    if(isset($_REQUEST['_wpnonce'])) {
      if(wp_verify_nonce($_REQUEST['_wpnonce'], "wpp_make_featured_" . $_REQUEST['post_id'])) {
        add_action('wp_ajax_wpp_make_featured', create_function("",'  $post_id = $_REQUEST[post_id]; echo WPP_F::toggle_featured($post_id); die();'));
      }
    }
    
    //add_action('wp_ajax_wpp_setup_default_widgets', create_function("",'  echo WPP_F::setup_default_widgets(); die();'));
    
    // Plug page actions -> Add Settings Link to plugin overview page
    add_filter('plugin_action_links', array('WPP_Core', 'plugin_action_links'), 10, 2 );
    
    // Register a sidebar for each property type
    foreach($wp_properties['property_types'] as $property_slug => $property_title) {
      register_sidebar( array(
        'name'=> sprintf(__('Property: %s', 'wpp'), $property_title),
        'id' => "wpp_sidebar_$property_slug",
        'description' =>  sprintf(__('Sidebar located on the %s page.', 'wpp'), $property_title),
        'before_widget' => '<li id="%1$s"  class="wpp_widget %2$s">',
        'after_widget' => '</li>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
      ));
    }
    
    add_shortcode('property_overview', array($this, 'shortcode_property_overview'));
    add_shortcode('property_search', array($this, 'shortcode_property_search'));
    add_shortcode('featured_properties', array($this, 'shortcode_featured_properties'));
    
    //Ajax pagination for property_overview
    add_action("wp_ajax_wpp_property_overview_pagination", array($this, "ajax_property_overview"));
    add_action("wp_ajax_nopriv_wpp_property_overview_pagination", array($this, "ajax_property_overview"));
    
    foreach($wp_properties['image_sizes'] as $image_name => $image_sizes) {
      add_image_size($image_name, $image_sizes['width'], $image_sizes['height'], true);
    }
    
    register_taxonomy_for_object_type('property_features', 'property');
    
    add_filter("manage_edit-property_sortable_columns", array(&$this, "sortable_columns"));
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
    
    add_action("admin_menu", array(&$this, 'admin_menu'));
    
    add_action("post_submitbox_misc_actions", array(&$this, "post_submitbox_misc_actions"));
    add_action('save_post', array($this, 'save_property'));
    add_action('before_delete_post', array('WPP_F', 'before_delete_post'));
    add_filter('post_updated_messages', array('WPP_Core', 'property_updated_messages'), 5);
    
    // Fix toggale row actions -> get rid of "Quick Edit" on property rows
    add_filter('page_row_actions', array('WPP_Core', 'property_row_actions'),0,2);
    
    add_action('pre_get_posts', array('WPP_F', 'pre_get_posts'));
    
    // Fix 404 errors
    add_filter("parse_query", array($this, "fix_404"));
    
    //* Hack. Used to avoid issues of some WPP capabilities */
    add_filter('current_screen', array($this, 'current_screen'));
    
    // Load admin header scripts
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    
    // Check premium feature availability
    add_action('wpp_premium_feature_check', array('WPP_F', 'feature_check'));
    
    // process bulk actions
    add_action('load-property_page_all_properties', array( 'WPP_F', 'property_page_all_properties_load' ));
    
    add_filter("manage_property_page_all_properties_columns", array( 'WPP_F', 'overview_columns' ));
    add_filter("wpp_overview_columns", array('WPP_F', 'custom_attribute_columns'));
    
    add_filter("wpp_attribute_filter", array('WPP_F', 'attribute_filter'), 10, 2);
    
    // Has to be called everytime, or else the custom slug will not work
    // Post-init action hook
    do_action('wpp_post_init');
    $wp_rewrite->flush_rules();
  }

  /**
   * Adds thumbnail feature to WP-Property pages
   *
   *
   * @todo Make sure only ran on property pages
    * @since 0.60
   *
   */
  static function after_setup_theme() {
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
   *  @uses $current_screen global variable
   * @since 0.53
   *
   */

  function admin_enqueue_scripts($hook) {
    global $current_screen, $wp_properties;

    // Include on all pages
    wp_enqueue_script('wp-property-global');

    // Property Overview Page
    if($current_screen->id == 'property_page_all_properties' || $current_screen->id == 'edit-property') {

      // Get width of overview table thumbnail, and set css
      $thumbnail_attribs = WPP_F::image_sizes($wp_properties['configuration']['admin_ui']['overview_table_thumbnail_size']);
      $thumbnail_width = (!empty($thumbnail_attribs['width']) ? $thumbnail_attribs['width'] : false);


      // Enabldes fancybox js, css and loads overview scripts
      wp_enqueue_script('jquery-fancybox');
      wp_enqueue_script('wp-property-admin-overview');
      wp_enqueue_style('jquery-fancybox-css');
      wp_enqueue_script('jquery-data-tables');
      wp_enqueue_style('jquery-data-tables');



      if($thumbnail_width):
      ?>
      <style typ="text/css">
      #wp-list-table.wp-list-table .column-thumbnail {width: <?php echo $thumbnail_width + 10; ?>px;}
      #wp-list-table.wp-list-table .column-type {width: 90px;}
       #wp-list-table.wp-list-table .column-menu_order {width: 50px; }
      #wp-list-table.wp-list-table td.column-menu_order {text-align: center; }
      #wp-list-table.wp-list-table .column-featured {width: 100px;}
      #wp-list-table.wp-list-table .check-column  {width: 26px;}
       </style>
      <?php
      endif;

    }

    // Property Editing Page
    if($current_screen->id == 'property') {


    }

    if($current_screen->id == 'property_page_property_settings') {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-sortable');
      wp_enqueue_script('jquery-colorpicker');
      wp_enqueue_style('jquery-colorpicker-css');

      $contextual_help['content'][] = '<h3>' . __('WP-Property Help') .'</h3>';
      $contextual_help['content'][] = '<p>' . __('The <b>property page</b> will be used to display property search results, as well as the base for property URLs.  For example, if the URL of your property page is ' . get_bloginfo('url') . '<b>/real_estate/</b>, then you properties will have the URLs of ' . get_bloginfo('url') . '/real_estate/<b>property_name</b>/','wpp') .'</p>';
      $contextual_help['content'][] = '<p>' . __('On-the-fly image generation means that image sizes, such as different sized thumbnails, are generated automatically when a visitor requests it online.  Alternatively, you could manually regenerate thumbnails by using a third-party plugin.','wpp') .'</p>';

      $contextual_help = apply_filters('wpp_contextual_help', array('page' => $current_screen->id, 'content' => $contextual_help['content']));
      add_contextual_help($current_screen->id, implode("\n", $contextual_help['content']));
    }
  }

  /**
   * Sets up additional pages and loads their scripts
   *
   * @since 0.5
   *
   */
  function admin_menu() {
    global $wp_properties, $submenu;

    do_action('wpp_admin_menu');

    // Create property settings page
    $settings_page  = add_submenu_page( 'edit.php?post_type=property', __('Settings','wpp'), __('Settings','wpp'), 'manage_wpp_settings', 'property_settings', create_function('','global $wp_properties; include "ui/page_settings.php";'));
    $all_properties = add_submenu_page( 'edit.php?post_type=property', __('All Properties','wpp'), __('All Properties','wpp'), 'edit_wpp_properties', 'all_properties', create_function('','global $wp_properties; include "ui/page_all_properties.php";'));
    /**
     * Next used to add custom submenu page 'All Properties' with Javascript dataTable
     * @author Anton K
     */
    //*
    if(!empty($submenu['edit.php?post_type=property'])) {
      
      // Comment next line if you want to get back old Property list page.
      array_shift($submenu['edit.php?post_type=property']);
      
      foreach ($submenu['edit.php?post_type=property'] as $key => $page) {
        if ( $page[2] == 'all_properties' ) {
          unset( $submenu['edit.php?post_type=property'][ $key ] );
          array_unshift( $submenu['edit.php?post_type=property'], $page );
        }
      }
    }
    /**  */
    
    // Load jQuery UI Tabs and Cookie into settings page (settings_page_property_settings)
    add_action('admin_print_scripts-' . $settings_page, create_function('', "wp_enqueue_script('jquery-ui-tabs');wp_enqueue_script('jquery-cookie');"));
    add_action('admin_head-edit.php', array("WPP_Core", "overview_page_scripts"));
    add_action('admin_head-'.$all_properties, array("WPP_Core", "overview_page_scripts"));

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

      // Property overview page
      case 'property_page_all_properties':

        $contextual_help = '
        <h3>Theme Problems</h3>
        We do theme customizations, and if you need help customizing your theme, please <a href="http://twincitiestech.com/contact-us/">contact us</a>.<br />
        <h3>Premium Theme</h3>
        If you would like to purchase the official WP-Property theme, <a href="http://twincitiestech.com/plugins/wp-property/wp-property-premium-theme-the-denali/">please view it here</a>. <br />
        <h3>Shortcodes</h3>
        For examples of how to use shortcodes, visit the <a href="http://sites.twincitiestech.com/the-denali/help/wp-property-shortcode-overview/">shortcode cheatsheet page<a>.
        ';
        
        $contextual_help = apply_filters('wpp_contextual_help_overview', $contextual_help);
                
        add_contextual_help('property_page_all_properties', $contextual_help);

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
              "<p><?php _e('You may also visit <a href="http://twincitiestech.com/plugins/wp-property/">TwinCitiesTech.com</a> for more information, and <a href="http://twincitiestech.com/plugins/wp-property/screencasts/">screencasts</a>.', 'wpp') ?></p></div>";

             jQuery(message).insertAfter(".wp-list-table   h2");
          });
        </script>

      <?php endif; ?>
        <script type="text/javascript">
          jQuery(document).ready(function() {
            jQuery("<p class='wpp_notification'>Help improve WP-Property by <a href='http://feedback.twincitiestech.com/forums/95259-wp-property'>suggesting ideas</a>. Support WP-Property by using the <a href='http://sites.twincitiestech.com/the-denali/'>official premium theme</a>.</p>").insertAfter("#posts-filter");
          });
        </script>

      <?php
        
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

      return 'wp-list-table ';
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

    if(
      $query->query_vars['name'] == $wp_properties['configuration']['base_slug']  ||
      $query->query_vars['category_name'] == $wp_properties['configuration']['base_slug']
    ) {
      $query->is_search = true;
    }

   }

  /**
   * Modifies post content
   *
   * @since 1.04
   *
   */
  function the_content($content) {
    global $post, $wp_properties;

    if(empty($wp_properties['configuration']['base_slug'])) {
      return $content;
    }

    if($wp_properties['configuration']['base_slug'] == $post->post_name && $wp_properties['configuration']['automatically_insert_overview'] == 'true') {
      return WPP_Core::shortcode_property_overview();
    }

    return $content;
  }

  /**
   * Hooks into save_post function and saves additional property data
   *
   *
   * @todo Add some sort of custom capability so not only admins can make properties as featured. i.e. Agents can make their own properties featured.
   * @since 1.04
   *
   */
  function save_property( $post_id ) {
    global $wp_rewrite, $wp_properties;

    if (!wp_verify_nonce( $_POST['_wpnonce'],'update-property_' . $post_id)) {
      return $post_id;
    }

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

    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
      return $post_id;
    }

    /*
    if ( !current_user_can( 'edit_property', $post_id) )
      return $post_id;
    */

    $update_data = $_REQUEST['wpp_data']['meta'];

    $old_location = get_post_meta($post_id, $wp_properties['configuration']['address_attribute'], true);
    $coordinates = get_post_meta($post_id,'latitude', true) . get_post_meta($post_id,'longitude', true);
    $new_location = $update_data[$wp_properties['configuration']['address_attribute']];


    if($update_data['manual_coordinates'] != get_post_meta($post_id, 'manual_coordinates', true)) {
      $manual_coordinates_updated = true;
    }

    // Update Coordinates (skip if old address matches new address), but always do if no coordinates set
    if(empty($coordinates) || ($old_location != $new_location && !empty($new_location)) || $manual_coordinates_updated) {

      $geo_data = UD_F::geo_locate_address($update_data[$wp_properties['configuration']['address_attribute']], $wp_properties['configuration']['google_maps_localization'], true);

      if(!empty($geo_data->formatted_address)) {
        update_post_meta($post_id, 'address_is_formatted', true);

        if(!empty($wp_properties['configuration']['address_attribute'])) {
          update_post_meta($post_id, $wp_properties['configuration']['address_attribute'], WPP_F::encode_mysql_input( $geo_data->formatted_address, $wp_properties['configuration']['address_attribute']));
        }

        foreach($geo_data as $geo_type => $this_data) {
          update_post_meta($post_id, $geo_type, WPP_F::encode_mysql_input( $this_data, $geo_type));
        }

      } else {
        // Try to figure out why it failed
        update_post_meta($post_id, 'address_is_formatted', false);
      }

    } else {

    }

    if($geo_data->status == 'OVER_QUERY_LIMIT') {
      //** Could add some sort of user notification that over limit */
    }

    foreach($update_data as $meta_key => $meta_value) {

      $attribute_data = WPP_F::get_attribute_data($meta_key);
      
      /* Cleans the user input */
      $meta_value = WPP_F::encode_mysql_input( $meta_value, $meta_key);

      // Only admins can mark properties as featured.
      if( $meta_key == 'featured' && !current_user_can('manage_options') ) {
        continue;
      }

      //Remove certain characters
      
      if($attribute_data['currency'] || $attribute_data['numeric']) {
        $meta_value = str_replace(array("$", ","), '', $meta_value);      
      }

       //** Overwrite old post meta allowing only one value */
      delete_post_meta($post_id, $meta_key);
      add_post_meta($post_id, $meta_key, $meta_value);
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
          }
        }
    }

    WPP_F::maybe_set_gpid($post_id);

    if($_REQUEST['parent_id']) {
      update_post_meta($post_id, 'parent_gpid', WPP_F::maybe_set_gpid($_REQUEST['parent_id']));
    }

    do_action('save_property',$post_id, $_REQUEST, $geo_data);

    $wp_rewrite->flush_rules();

    return true;
   }

  /**
   * Inserts content into the "Publish" metabox on property pages
   *
   * @since 1.04
   *
   */
  function post_submitbox_misc_actions() {
    global $post, $action;

    if($post->post_type == 'property') {

      $featured = get_post_meta($post->ID, 'featured', true);

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
   *
   */
    function property_row_actions($actions, $post) {
        if($post->post_type != 'property')
            return $actions;

        unset($actions['inline']);

        return $actions;
    }


  /**
   * Includes admin CSS link if file exists
   *
   *
   * @since 0.5
   *
   */
  function admin_css() {
    global $current_screen;

    if ( file_exists( WPP_Path . '/css/wp_properties_admin.css') ) {
      wp_register_style('myStyleSheets', WPP_URL . '/css/wp_properties_admin.css');
      wp_enqueue_style( 'myStyleSheets');
    }

  }

  /**
   * Adds property-relevant messages to the property post type object
   *
   *
   * @since 0.5
   *
   */
  function property_updated_messages( $messages ) {
    global $post_id, $post;

    $messages['property'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Property updated. <a href="%s">view property</a>','wpp'), esc_url( get_permalink($post_id) ) ),
    2 => __('Custom field updated.','wpp'),
    3 => __('Custom field deleted.','wpp'),
    4 => __('Property updated.','wpp'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Property restored to revision from %s','wpp'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Property published. <a href="%s">View property</a>','wpp'), esc_url( get_permalink($post_id) ) ),
    7 => __('Property saved.','wpp'),
    8 => sprintf( __('Property submitted. <a target="_blank" href="%s">Preview property</a>','wpp'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_id) ) ) ),
    9 => sprintf( __('Property scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview property</a>','wpp'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i','wpp'), strtotime( $post->post_date ) ), esc_url( get_permalink($post_id) ) ),
    10 => sprintf( __('Property draft updated. <a target="_blank" href="%s">Preview property</a>','wpp'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_id) ) ) ),
    );

    $messages = apply_filters('wpp_updated_messages', $messages);

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
    $columns['property_type'] = __('Type','wpp');

    if(is_array($wp_properties['property_stats'])) {
      foreach($wp_properties['property_stats'] as $slug => $title)
        $columns[$slug] = $title;
    } else {
      $columns = $columns;
    }

    $columns['city'] = __('City','wpp');
    $columns['overview'] = __('Overview','wpp');
    $columns['featured'] = __('Featured','wpp');
    $columns['menu_order'] = __('Order','wpp');
    $columns['thumbnail'] = __('Thumbnail','wpp');

    $columns = apply_filters('wpp_admin_overview_columns', $columns);
    //
    return $columns;
  }

  /**
   * Sets up sortable columns columns
   *
   * @since 1.08
   *
   */
  function sortable_columns($columns) {
    global $wp_properties;


    $columns['type'] = 'type';
    $columns['featured'] = 'featured';

    if(is_array($wp_properties['property_stats'])) {
      foreach($wp_properties['property_stats'] as $slug => $title)
        $columns[$slug] = $slug;
    }

    $columns = apply_filters('wpp_admin_sortable_columns', $columns);


    return $columns;
  }


  /**
   * Displays custom property columns on the overview page
   *
   *
   * @since 0.5
   *
   */
  function custom_columns($column) {
    global $post, $wp_properties;
    $post_id = $post->ID;

    switch ($column)    {
      case "description":
        the_excerpt();
      break;

      case "property_type":
        $property_type = $post->property_type;
        echo $wp_properties['property_types'][$property_type];
      break;

      case "price":
         echo apply_filters("wpp_stat_filter_$column", $post->price);
      break;

      case $wp_properties['configuration']['address_attribute']:

        // Only show this is the property type has an address
        if(in_array($post->property_type, $wp_properties['location_matters'])) {

        $localization = ($wp_properties['configuration']['google_maps_localization'] ? $wp_properties['configuration']['google_maps_localization'] : 'en');

        if($post->latitude && $post->longitude)
          echo "<a href='http://maps.google.com/maps?q={$post->latitude},+{$post->longitude}&hl=$localization' target='_blank'>".$post->display_address ."</a>.";
        else
          _e(' - ','wpp');

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

        if($post->featured_image) {

          $overview_thumb_type = $wp_properties['configuration']['admin_ui']['overview_table_thumbnail_size'];

          if(empty($overview_thumb_type)) {
            $overview_thumb_type = 'thumbnail';
          }

           $image_thumb_obj = wpp_get_image_link($post->featured_image, $overview_thumb_type, array('return'=>'array'));


        }

        if(!empty($image_thumb_obj)) { ?>
          <a href="<?php echo $post->images['large']; ?>" class="fancybox" rel="overview_group" title="<?php echo  $post->post_title; ?>">
            <img src="<?php echo $image_thumb_obj['url']; ?>" width="<?php echo $image_thumb_obj['width']; ?>" height="<?php echo $image_thumb_obj['height']; ?>" />
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
   * Loads conditional CSS styles
   *
   * @since 1.11
   */
  function template_redirect() {
    global $post, $property, $wp, $wp_query, $wp_properties, $wp_styles;

     do_action('wpp_template_redirect');

    // Call on all pages because styles are used in widgets
    wp_enqueue_style('wp-property-frontend');
    wp_enqueue_style('wp-property-theme-specific');

    if($wp_properties['configuration']['load_scripts_everywhere'] == 'true') {
      wp_enqueue_style('jquery-fancybox-css');
      wp_enqueue_style('jquery-ui');
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-slider');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-mouse');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-widget');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-fancybox');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('wp-property-frontend');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-address');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-scrollTo');"));
    }

    //** Must include these everywhere since do_action() property_overview calls cannot be detected */

    if($post->post_type == "property") {
      $single_page = true;
    }

    $is_search = (is_array($_REQUEST['wpp_search']) ? true : false);

    // Determine if current request is for the overview page
    if($wp->request == $wp_properties['configuration']['base_slug'] ||
        $wp->query_string == "p=" . $wp_properties['configuration']['base_slug'] ||
          strpos($post->post_content, "property_overview")) {

      // Update global $wp_query var
      $wp_query->is_property_overview = true;

    }


    // Scripts for both types of views
    if ($single_page || $wp_query->is_property_overview) {

      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-fancybox');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('wp-property-frontend');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-address');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-scrollTo');"));

      wp_enqueue_style('jquery-fancybox-css');
      wp_enqueue_style('jquery-ui');
      // Check for and load conditional browser styles
      $conditional_styles = apply_filters('wpp_conditional_style_slugs', array('IE','IE 7','msie'));

      foreach($conditional_styles as $type) {

        // Fix slug for URL
        $url_slug = str_replace(" ", "_", $type);

        if ( file_exists( STYLESHEETPATH . "/wp_properties-{$url_slug}.css") ) {
          wp_register_style('wp-property-frontend-'. $url_slug, get_bloginfo('stylesheet_directory') . "/wp_properties-{$url_slug}.css",   array('wp-property-frontend'),'1.13' );
        } elseif ( file_exists( TEMPLATEPATH . "/wp_properties-{$url_slug}.css") ) {
          wp_register_style('wp-property-frontend-'. $url_slug, get_bloginfo('template_url') . "/wp_properties-{$url_slug}.css",   array('wp-property-frontend'),'1.13' );
        } elseif (file_exists( WPP_Templates . "/wp_properties-{$url_slug}.css") && $wp_properties['configuration']['autoload_css'] == 'true') {
          wp_register_style('wp-property-frontend-'. $url_slug, WPP_URL . "/templates/wp_properties-{$url_slug}.css",  array('wp-property-frontend'),WPP_Version);
        }
        // Mark every style as conditional
        $wp_styles->add_data('wp-property-frontend-'. $url_slug, 'conditional', $type );
        wp_enqueue_style('wp-property-frontend-'. $url_slug);

      }

    }

    if ($single_page && empty($post->post_password))  {

      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('google-maps');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-mouse');"));

      // Allow plugins to insert header scripts/styles using wp_head_single_property hook
      do_action('template_redirect_single_property');
      add_action('wp_head', create_function('', "do_action('wp_head_single_property'); "));

      $post = WPP_F::get_property($post->ID, "return_object=true&load_gallery=true");

      $type = $post->property_type;

       if(file_exists(STYLESHEETPATH . "/property-$type.php")) {
        load_template(STYLESHEETPATH . "/property-$type.php");
        die();
      }

       if(file_exists(STYLESHEETPATH . "/property.php")) {
        load_template(STYLESHEETPATH . "/property.php");
        die();
      }

       if(file_exists(TEMPLATEPATH . "/property-$type.php")) {
        load_template(TEMPLATEPATH . "/property-$type.php");
        die();
      }

       if(file_exists(TEMPLATEPATH . "/property.php")) {
        load_template(TEMPLATEPATH . "/property.php");
        die();
      }

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

     if($wp_query->is_property_overview) {

      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-slider');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-mouse');"));
      add_action('wp_enqueue_scripts', create_function('', "wp_enqueue_script('jquery-ui-widget');"));

      // Allow plugins to insert header scripts/styles using wp_head_single_property hook
      do_action('template_redirect_property_overview');
      add_action('wp_head', create_function('', "do_action('wp_head_property_overview'); "));

      /*
        Show custom property overview template if this is a search result and not a post
        If do_not_override_search_result_page is set to true, then we do not use custom template, but depend
        on user to include [property_overview] in their post content
      */
      if(($is_search || !$post) && $wp_properties['configuration']['do_not_override_search_result_page'] != 'true') {


        if(file_exists(STYLESHEETPATH . "/property-search-result.php")) {
          load_template(STYLESHEETPATH . "/property-search-result.php");
          die();
        }

         if(file_exists(STYLESHEETPATH . "/property-overview-page.php")) {
           load_template(STYLESHEETPATH . "/property-overview-page.php");
          die();
        }

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


  /**
   * Runs pre-header functions on admin-side only
   *
   * Checks if plugin has been updated.
   *
   * @since 1.10
   *
   */
  function admin_init() {
    global $wp_rewrite;

    WPP_F::fix_screen_options();

    add_meta_box( 'property_meta', __('General Information','wpp'), array('WPP_UI','metabox_meta'), 'property', 'normal' );
    add_meta_box( 'propetry_filter', __('Property Search','wpp'), array('WPP_UI','metabox_property_filter'), 'property_page_all_properties', 'normal' );
    
    // Add metaboxes
    do_action('wpp_metaboxes');
    WPP_F::manual_activation();

    // Download backup of configuration
    if($_REQUEST['page'] == 'property_settings'
      && $_REQUEST['wpp_action'] == 'download-wpp-backup'
      && wp_verify_nonce($_REQUEST['_wpnonce'], 'download-wpp-backup')) {
        global $wp_properties;

        $sitename = sanitize_key( get_bloginfo( 'name' ) );
        $filename = $sitename . '-wp-property.' . date( 'Y-m-d' ) . '.txt';

        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Transfer-Encoding: binary");
        header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );

        echo json_encode($wp_properties);

      die();
    }
  }


  /**
   * Displays featured properties
   *
   * Performs searching/filtering functions, provides template with $properties file
   * Retirms html content to be displayed after location attribute on property edit page
   *
   * @todo Consider making this function depend on shortcode_property_overview() more so pagination and sorting functions work.
   *
   * @since 0.60
   * @param string $listing_id Listing ID must be passed
   *
   * @uses WPP_F::get_properties()
   *
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
    if(strpos($type, ",")) {
      $type = explode(",", $type);
    }

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
      if(file_exists(STYLESHEETPATH . "/property-featured-shortcode.php")) {
        include STYLESHEETPATH . "/property-featured-shortcode.php";

    // 1. Try custom template in theme folder
      } elseif(file_exists(TEMPLATEPATH . "/property-featured-shortcode.php")) {
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
   * Returns the property search widget
   *
   *
    * @since 1.04
   *
   */
  function shortcode_property_search($atts = "")  {
    global $post, $wp_properties;

    extract(shortcode_atts(array(
      'searchable_attributes' => '',
      'searchable_property_types' => '',
      'pagination' => 'on',
      'per_page' => '10'
    ),$atts));

    if(empty($searchable_attributes)) {
      
      //** get first 3 attributes to prevent people from accidentally loading them all (long query) */      
      $searchable_attributes = array_slice($wp_properties['searchable_attributes'], 0, 5);
      
    } else {
      $searchable_attributes = explode(",", $searchable_attributes);
    }

    $searchable_attributes = array_unique($searchable_attributes);

    if(empty($searchable_property_types)) {
      $searchable_property_types = $wp_properties['searchable_property_types'];
    } else {
      $searchable_property_types = explode(",", $searchable_property_types);
    }

    $widget_id = $post->ID . "_search";

    ob_start();
    echo "<div class='wpp_shortcode_search'>";

    $search_args['searchable_attributes'] = $searchable_attributes;
    $search_args['searchable_property_types'] = $searchable_property_types;
    $search_args['per_page'] = $per_page;
    $search_args['pagination'] = $pagination;
    $search_args['instance_id'] = $widget_id;

    draw_property_search_form($search_args);
    
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
     * @since 1.081
     * @param string $listing_id Listing ID must be passed
     * @return string $result
     *
     * @uses WPP_F::get_properties()
     *
     */
    function shortcode_property_overview($atts = "")  {
      global $wp_properties, $wpp_query, $property;
      
      WPP_F::force_script_inclusion('jquery-ui-widget');
      WPP_F::force_script_inclusion('jquery-ui-mouse');
      WPP_F::force_script_inclusion('jquery-ui-slider');
      WPP_F::force_script_inclusion('jquery-address');
      WPP_F::force_script_inclusion('jquery-scrollTo');
      WPP_F::force_script_inclusion('jquery-fancybox');
      WPP_F::force_script_inclusion('wp-property-frontend');
      
      //** Load all queriable attributes **/
      foreach(WPP_F::get_queryable_keys() as $key) {
        //** This needs to be done because a key has to exist in the $deafult array for shortcode_atts() to load passed value */
        $queryable_keys[$key] = false;
      }
      
      //** Allow the shorthand of "type" as long as there is not a custom attribute of "type". If "type" does exist as an attribute, then users need to use the full "property_type" query tag. **/
      if ( !array_key_exists( 'type', $queryable_keys ) && (is_array($atts) && array_key_exists( 'type', $atts ) ) ) {
        $atts['property_type'] = $atts['type'];
        unset( $atts['type'] );
      }

      //** Get ALL allowed attributes that may be passed via shortcode (to include property attributes) */
      $defaults['show_children'] = (isset($wp_properties['configuration']['property_overview']['show_children']) ? $wp_properties['configuration']['property_overview']['show_children'] : 'true');
      $defaults['child_properties_title'] = __('Floor plans at location:','wpp');
      $defaults['fancybox_preview'] = $wp_properties['configuration']['property_overview']['fancybox_preview'];
      $defaults['bottom_pagination_flag'] = ($wp_properties['configuration']['bottom_insert_pagenation'] == 'true' ? true : false);
      $defaults['thumbnail_size'] = $wp_properties['configuration']['property_overview']['thumbnail_size'];
      $defaults['sort_by'] = 'menu_order';
      $defaults['sort_order'] = 'ASC';
      $defaults['template'] = false;
      $defaults['ajax_call'] = false;
      $defaults['pagination'] = 'on';
      $defaults['per_page'] = 10;
      $defaults['starting_row'] = 0;
      $defaults['unique_hash'] = rand(10000,99900);
      $defaults['detail_button'] = false;
      
      if($atts['ajax_call']) {
        //** If AJAX call then the passed args have all the data we need */
        $wpp_query = $atts;
        
        //* Fix ajax data. Boolean value false is returned as string 'false'. */
        foreach($wpp_query as $key => $value) {
          if($value == 'false') {
            $wpp_query[$key] = false;
          }
        }
        
        $wpp_query['ajax_call'] =  true;
        
        //** Everything stays the same except for sort order and page */
        $wpp_query['starting_row']  =   (($wpp_query['requested_page'] - 1) * $wpp_query['per_page']);
        
        //** Figure out current page */
        $wpp_query['current_page'] =  $wpp_query['requested_page'];
        
      } else {
        //** Merge defaults with passed arguments */
        $wpp_query = shortcode_atts($defaults, $atts);  
        $wpp_query['query'] = shortcode_atts($queryable_keys, $atts);
        
        //** Handle search */
        if($wpp_search = $_REQUEST['wpp_search']) {
        
          $non_numeric_chars = apply_filters('wpp_non_numeric_chars', array('-', '$', ','));
          
          if($wp_properties['configuration']['developer_mode'] == 'true' && !$wpp_query['ajax_call']) {
            //  echo '<script type="text/javascript">console.log('.json_encode($wpp_search) . ' ); </script>'; 
          }
          
          foreach($wpp_search as $search_key => $search_query) {
            //** Fix search form passed paramters to be usable by get_properties();
            
            if(is_array($search_query)) {
              //** Array variables are either option lists or minimum and maxim variables
              if(is_numeric(array_shift(array_keys($search_query)))) {
                //** get regular arrays (non associative) */
                $search_query = implode(',', $search_query);
              } elseif(is_array($search_query['options'])) {
                //** Get queries with options */
                $search_query = implode(',', $search_query['options']);
              } elseif(in_array('min', array_keys($search_query))) {
                //** Get arrays with minimum and maxim ranges */
                
                //** Ranges are always numeric, so we clear it up */
                foreach($search_query as $range_indicator => $value) {
                  $search_query[$range_indicator] = str_replace($non_numeric_chars, '', $value);
                }
                
                if(empty($search_query['min']) && empty($search_query['max'])) {
                  unset($wpp_query['query'][$search_key]);
                  continue;
                }
                
                if(empty($search_query['min'])) {
                  $search_query['min'] = '0';
                }
                
                if(empty($search_query['max'])) {
                  $search_query = $search_query['min'] . '+';
                } else {
                  $search_query = str_replace($non_numeric_chars, '', $search_query['min']) . '-' .  str_replace($non_numeric_chars, '', $search_query['max']);
                }
              }
            }
            
            if(is_string($search_query)) {
              $wpp_query['query'][$search_key] = trim($search_query);
            }
            
            if($search_query == '-') {
              unset($wpp_query['query'][$search_key]);
            }
            
            if($search_query == '-1') {
              unset($wpp_query['query'][$search_key]);
            }
          
          }
          
          if(isset($_REQUEST['wpp_search']['sort_by'])) {
            $wpp_query['sort_by'] = $_REQUEST['wpp_search']['sort_by'];
          }
          
          if(isset($_REQUEST['wpp_search']['sort_order'])) {
            $wpp_query['sort_order'] = $_REQUEST['wpp_search']['sort_order'];
          }       
          
          if(isset($_REQUEST['wpp_search']['pagination'])) {
            $wpp_query['pagination'] = $_REQUEST['wpp_search']['pagination'];
          }
          
          if(isset($_REQUEST['wpp_search']['per_page'])) {
            $wpp_query['per_page'] = $_REQUEST['wpp_search']['per_page'];
          }
        
        }
      
      }
      
      //** Load certain settings into query for get_properties() to use */
      $wpp_query['query']['sort_by'] = $wpp_query['sort_by'];
      $wpp_query['query']['sort_order'] = $wpp_query['sort_order'];
      
      $wpp_query['query']['pagi'] = $wpp_query['starting_row'] . '--' . $wpp_query['per_page'];
      
      if(!isset($wpp_query['current_page'])) {
        $wpp_query['current_page'] =  ($wpp_query['starting_row'] / $wpp_query['per_page']) + 1;
      }
      
      //** Load settings that are not passed via shortcode atts */
      $wpp_query['sortable_attrs'] = WPP_F::get_sortable_keys();
      
      //** Remove all blank values */
      $wpp_query['query'] = array_filter($wpp_query['query']);
      
      //** Unset this because it gets passed with query (for back-button support) but not used by get_properties() */
      unset($wpp_query['query']['per_page']);
      unset($wpp_query['query']['pagination']);
      unset($wpp_query['query']['requested_page']);
      
      //** Load the results */
      $wpp_query['properties'] = WPP_F::get_properties($wpp_query['query'], true);
      
      //** Calculate number of pages */
      if($wpp_query['pagination'] == 'on') {
        $wpp_query['pages'] = round($wpp_query['properties']['total'] / $wpp_query['per_page']);
      }
      
      //** Set for quick access (for templates */
      $property_type = $wpp_query['query']['property_type'];
      
      //** Legacy Support - include variables so old templates still work */
      $properties = $wpp_query['properties']['results'];
      //$properties['total'] = $wpp_query['properties']['total'];
      $unique = $wpp_query['unique_hash'];
      $thumbnail_size = $wpp_query['thumbnail_size'];
      
      //* Debugger */
      if($wp_properties['configuration']['developer_mode'] == 'true' && !$wpp_query['ajax_call']) {
       echo '<script type="text/javascript">console.log( ' .json_encode($wpp_query) . ' ); </script>'; 
      }
      
      ob_start();
      
      $template = $wpp_query['template'];
      
      // 1. Try custom template in theme folder ("template=" in shortcode)
      if(file_exists(STYLESHEETPATH . "/property-overview-$template.php")) {
        include STYLESHEETPATH  . "/property-overview-$template.php";
      // 1. Try custom template in theme folder ("template=" in shortcode)
      } elseif (file_exists(TEMPLATEPATH . "/property-overview-$template.php")) {
        include TEMPLATEPATH  . "/property-overview-$template.php";
      // 2. Try custom template in defaults folder ("template=" in shortcode)
      } elseif (file_exists(WPP_Templates . "/property-overview-$template.php")) {
        include WPP_Templates  . "/property-overview-$template.php";
      // 3. Try custom template in theme folder
      } elseif(file_exists(STYLESHEETPATH . "/property-overview-$property_type.php")) {
        include STYLESHEETPATH . "/property-overview-$property_type.php";
      } elseif(file_exists(TEMPLATEPATH . "/property-overview-$property_type.php")) {
        include TEMPLATEPATH . "/property-overview-$property_type.php";
      // 4. Try custom template in defaults folder
      } elseif(file_exists(WPP_Templates . "/property-overview-$property_type.php")) {
        include WPP_Templates . "/property-overview-$property_type.php";
      // 5. Try general template in theme folder
      } elseif(file_exists(STYLESHEETPATH . "/property-overview.php")) {
        include STYLESHEETPATH . "/property-overview.php";
      } elseif(file_exists(TEMPLATEPATH . "/property-overview.php")) {
        include TEMPLATEPATH . "/property-overview.php";
      // 6. If all else fails, try the default general template
      } elseif(file_exists(WPP_Templates . "/property-overview.php")) {
        include WPP_Templates . "/property-overview.php";
      }
      
      $ob_get_contents = ob_get_contents();
      ob_end_clean();
      
      // Initialize result (content which will be shown) and open wrap (div) with unique id
      $result['top'] = '<div id="wpp_shortcode_'. $defaults['unique_hash'] .'" class="wpp_ui wpp_property_overview_shortcode">';
      $result['top_pagination'] = wpi_draw_pagination(array('return' => true, 'class' => 'wpp_top_pagination'));
      $result['result'] = $ob_get_contents;
      
      if($wpp_query['bottom_pagination_flag'] == 'true') {
        $result['bottom_pagination'] = wpi_draw_pagination(array('return' => true, 'class' => 'wpp_bottom_pagination'));
      }
      
      $result['bottom'] = '</div>';
      
      $result = apply_filters('wpp_property_overview_render', $result);
      
      if($wpp_query['ajax_call']) {
        return json_encode(array('wpp_query' => $wpp_query, 'display' => implode('', $result)));
      } else {
        return implode('', $result);
      }
    }

/**
   *
   * @since 0.723
   *
   * @uses WPP_Core::shortcode_property_overview()
   *
   */
    function ajax_property_overview()  {

    $params = $_REQUEST['wpp_ajax_query'];

    if(!empty($params['action'])) {
      unset($params['action']);
    }

    $params['ajax_call'] = true;

    $data = WPP_Core::shortcode_property_overview( $params );
    
    die($data);
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

    /**
     * Checks settings data on accord with existing wp_properties data ( before option updates )
     * @param array $wpp_settings New wpp settings data
     * @param array $wp_properties Old wpp settings data
     * @return array $wpp_settings
     */
    function check_wp_settings_data ($wpp_settings, $wp_properties) {
        if(is_array($wpp_settings) && is_array($wp_properties)) {
            foreach($wp_properties as $key => $value) {
                if(!isset($wpp_settings[$key])) {
                    switch($key) {
                        case 'hidden_attributes':
                        case 'property_inheritance':
                            $wpp_settings[$key] = array();
                            break;
                    }
                }
            }
        }

        return $wpp_settings;
    }
  
  /*
   * Hack to avoid issues with capabilities and views.
   * 
   */
  function current_screen($screen){
    
    //print_r($screen);
    switch($screen->id){
      case "edit-property":
        wp_redirect('edit.php?post_type=property&page=all_properties');
        exit();
        break;
    }
    
    return $screen;
  }
  
  /*
   * Adds all WPP custom capabilities to administrator role.
   * Premium feature capabilities are added by filter in this function, see below.
   * 
   * @author Maxim Peshkov
   */
  function set_capabilities() {
    global $wpp_capabilities;
    
    //* Get Administrator role for adding custom capabilities */
    $role =& get_role('administrator');
    
    //* General WPP capabilities */
    $wpp_capabilities = array(
      //* Manage WPP Properties Capabilities */
      'edit_wpp_property' => __('Edit Propery','wpp'),
      'read_wpp_property' => __('Read Propery','wpp'),
      'delete_wpp_property' => __('Delete Propery','wpp'),
      'edit_wpp_properties' => __('Edit Properties','wpp'),
      'edit_others_wpp_properties' => __('Edit Other Properties','wpp'),
      'publish_wpp_properties' => __('Publish Properties','wpp'),
      'read_private_wpp_properties' => __('Read Private Properties','wpp'),
      //* WPP Settings capability */
      'manage_wpp_settings' => __('Manage Settings','wpp'),
      //* WPP Taxonomies capability */
      'manage_wpp_categories' => __('Manage Categories','wpp')
    );
    
    //* Adds Premium Feature Capabilities */
    $wpp_capabilities = apply_filters('wpp_capabilities', $wpp_capabilities); 
    
    /* FOR DEBUG: PRINT ALL WPP PROPERTY CAPABILITIES
    echo "<pre>";print_r($capabilities);echo "</pre>";die();
    //*/
    
    foreach($wpp_capabilities as $cap => $value){
      if (empty($role->capabilities[$cap])) {
        $role->add_cap($cap);
      }
    }
  }
  
}


