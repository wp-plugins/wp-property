<?php
/**
 * WP-Property General Functions
 *
 * Contains all the general functions used by the plugin.
 *
 * @version 1.00
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package WP-Property
 * @subpackage Functions
 */

class WPP_F {


  /**
   * Checks if script or style have been loaded.
   *
   * @todo Add handler for styles.
   * @since Denali 3.0
   *
   */
  function is_asset_loaded($handle = false) {
    global $wp_properties, $wp_scripts;

    if(empty($handle)) {
      return;
    }

    $footer = (array) $wp_scripts->in_footer;
    $done = (array) $wp_scripts->done;

    $accepted = array_merge($footer, $done);

    if(!in_array($handle, $accepted)) {
      return false;
    }

    return true;

  }


  /**
   * PHP function to echoing a message to JS console
   *
   * @since Denali 3.0
   */
  function console_log($text = false) {
    global $wp_properties;

    if($wp_properties['configuration']['developer_mode'] != 'true') {
      return;
    }

    if(empty($text)) {
      return;
    }

    add_filter('wp_footer', create_function('$nothing,$echo_text = "'. $text .'"', 'echo \'<script type="text/javascript">console.log("\' . $echo_text . \'")</script>\'; '));
  }



  /**
  * Tests if remote script or CSS file can be opened prior to sending it to browser
  *
  *
  * @version 1.26.0
  */
  function can_get_script($url = false, $args = array()) {
    global $wp_properties;

    if(empty($url)) {
      return false;
    }

    $match = false;

    if(empty($args)){
      $args['timeout'] = 10;
    }

    $result = wp_remote_get($url, $args);


    if(is_wp_error($result)) {
      return false;
    }

    $type = $result['headers']['content-type'];

    if(strpos($type, 'javascript') !== false) {
      $match = true;
    }

    if(strpos($type, 'css') !== false) {
      $match = true;
    }

    if(!$match || $result['response']['code'] != 200) {

      if($wp_properties['configuration']['developer_mode'] == 'true') {
        WPP_F::console_log("Remote asset ($url) could not be loaded, content type returned: ". $result['headers']['content-type']);
      }

      return false;
    }

    return true;

  }

  /**
  * Tests if remote image can be loaded, before sending to browser or TCPDF
  *
  * @version 1.26.0
  */
  function can_get_image($url = false) {
    global $wp_properties;

    if(empty($url)) {
      return false;
    }

    $result = wp_remote_get($url, array( 'timeout' => 10));

    //** Image content types should always begin with 'image' (I hope) */
    if(strpos($result['headers']['content-type'], 'image') !== 0) {
      return false;
    }

    return true;

  }



/**
  * Remove non-XML characters
  *
  * @version 1.30.2
  */
  function strip_invalid_xml($value) {

    $ret = "";
    $current;

    $bad_chars = array('\u000b');

    $value = str_replace($bad_chars, ' ', $value);

    if (empty($value)) {
      return $ret;
    }

    $length = strlen($value);

    for ($i=0; $i < $length; $i++) {

      $current = ord($value{$i});

      if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) ||
          (($current >= 0x20) && ($current <= 0xD7FF)) ||
            (($current >= 0xE000) && ($current <= 0xFFFD)) ||
              (($current >= 0x10000) && ($current <= 0x10FFFF))) {

        $ret .= chr($current);

      } else {
        $ret .= " ";
      }
    }

    return $ret;
  }

  /**
  * Convert JSON data to XML if it is in JSON
  *
  * @version 1.26.0
  */
  function json_to_xml($json) {

    if(empty($json)) {
      return false;
    }

    if(!class_exists('XML_Serializer')) {
      set_include_path(get_include_path() . PATH_SEPARATOR . WPP_Path.'/third-party/XML/');
      @require_once 'Serializer.php';
    }

    //** If class still doesn't exist, for whatever reason, we fail */
    if(!class_exists('XML_Serializer')) {
      return false;
    }

    $encoding = mb_detect_encoding($json);

    if($encoding == 'UTF-8') {
      $json = preg_replace('/[^(\x20-\x7F)]*/','', $json);
    }

    $json = WPP_F::strip_invalid_xml($json);

    $data = json_decode($json, true);

    //** If could not decode, return false so we presume with XML format */
    if(!is_array($data)) {
      return false;
    }


  /*
    For troubleshooting, for now we just assume file isn't JSON
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
        break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            echo ' - Unknown error';
        break;
    }
  */

  $data['objects'] = $data;

  // An array of serializer options
  $serializer_options = array (
    'indent' => " ",
    'linebreak' => "\n",
    'addDecl' => true,
    'encoding' => 'ISO-8859-1',
    'rootName' => 'objects',
    'defaultTagName' => 'object',
    'mode' => 'simplexml'
  );

  $Serializer = &new XML_Serializer($serializer_options);

  $status = $Serializer->serialize($data);


  if (PEAR::isError($status)) {
    return false;
  }

  if($Serializer->getSerializedData()) {
    return $Serializer->getSerializedData();
  }

  return false;

  }

  /**
   * Get filesize of a file.
   *
   * Function ported over from List Attachments Shortcode plugin.
   *
   * @version 1.25.0
   */
    function get_filesize( $file ) {
      $bytes = filesize( $file );
      $s = array( 'b', 'Kb', 'Mb', 'Gb' );
      $e = floor( log( $bytes ) / log( 1024 ) );
      return sprintf( '%.2f ' . $s[$e], ( $bytes / pow( 1024, floor( $e ) ) ) );
    }


  /**
   * Set all existing property objects' property type
   *
   * @todo Add regex to check for opening and closing bracket.
   * @version 1.23.1
   */
    function mass_set_property_type($property_type = false) {
      global $wpdb;

      if(!$property_type) {
        return false;
      }

      //** Get all properties */
      $ap = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property'");

      if(!$ap) {
        return false;
      }

      foreach($ap as $id) {

        if(update_post_meta($id, 'property_type', $property_type)) {
          $success[] = true;
        }

      }

      if(!$success) {
        return false;
      }

      return sprintf(__('Set %1s properties to "%2s" property type', 'wpp'), count($success), $property_type);



    }


  /**
   * Attempts to detect if current page has a given shortcode
   *
   * @todo Add regex to check for opening and closing bracket.
   * @version 1.23.1
   */
    function detect_shortcode($shortcode = false){
      global $post;

      if(!$post) {
        return false;
      }

      $shortcode = '[' . $shortcode;

      if(strpos($post->post_content, $shortcode) !== false) {
        return true;
      }

      return false;

    }


  /**
   * Reassemble address from parts
   *
   * @version 1.23.0
   */
    function reassemble_address($property_id = false){

      if(!$property_id) {
        return false;
      }

      $address_part[] = get_post_meta($property_id, 'street_number', true);
      $address_part[] = get_post_meta($property_id, 'route',true);
      $address_part[] = get_post_meta($property_id, 'city', true);
      $address_part[] = get_post_meta($property_id, 'state',true);
      $address_part[] = get_post_meta($property_id, 'state_code', true);
      $address_part[] = get_post_meta($property_id, 'country', true);
      $address_part[] = get_post_meta($property_id, 'postal_code',true);

     $maybe_address = trim(implode(' ', $address_part));

      if(!empty($maybe_address)) {
        return $maybe_address;
      }

      return false;

    }


  /**
   * Creates a nonce, similar to wp_create_nonce() but does not depend on user being logged in
   *
   * @version 1.17.3
   */
    function generate_nonce($action = -1){

      $user = wp_get_current_user();

      $uid = (int) $user->id;

      if(empty($uid)) {
        $uid = $_SERVER['REMOTE_ADDR'];
      }

      $i = wp_nonce_tick();

      return substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10);


   }

   /**
   * Verifies nonce.
   *
   * @version 1.17.3
   */
    function verify_nonce($nonce, $action = false){

      $user = wp_get_current_user();
      $uid = (int) $user->id;

      if(empty($uid)) {
        $uid = $_SERVER['REMOTE_ADDR'];
      }

      $i = wp_nonce_tick();

      // Nonce generated 0-12 hours ago
      if ( substr(wp_hash($i . $action . $uid, 'nonce'), -12, 10) == $nonce )
      return 1;
      // Nonce generated 12-24 hours ago
      if ( substr(wp_hash(($i - 1) . $action . $uid, 'nonce'), -12, 10) == $nonce )
      return 2;
      // Invalid nonce
      return false;

   }


  /**
   * Returns attribute information.
   *
   * Checks $wp_properties and returns a concise array of array-specific settings and attributes
   *
   * @todo Consider putting this into settings action, or somewhere, so it its only ran once, or adding caching
   * @version 1.17.3
   */
    function get_attribute_data($attribute = false) {
      global $wp_properties;

      if(!$attribute) {
        return;
      }

      $post_table_keys = array(
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count');

      $ui_class = array($attribute);

      if(in_array($attribute, $post_table_keys)) {
        $return['storage_type'] = 'post_table';
      }

      $return['slug'] = $attribute;

      if($wp_properties['property_stats'][$attribute]) {
        $return['is_stat'] = 'true';
        $return['storage_type'] = 'meta_key';
        $return['label'] = $wp_properties['property_stats'][$attribute];
      }

      if($wp_properties['property_meta'][$attribute]) {
        $return['is_meta'] = 'true';
        $return['storage_type'] = 'meta_key';
        $return['label'] = $wp_properties['property_meta'][$attribute];
        $return['input_type'] = 'textarea';
      }

      if($wp_properties['searchable_attr_fields'][$attribute]) {
        $return['input_type'] = $wp_properties['searchable_attr_fields'][$attribute];
        $ui_class[] = $return['input_type'];
      }

      if($wp_properties['configuration']['address_attribute'] == $attribute) {
        $return['is_address_attribute'] = 'true';
        $ui_class[] = 'address_attribute';
      }

      if(is_array($wp_properties['property_inheritance'])) {
        foreach($wp_properties['property_inheritance'] as $property_type => $type_data) {
          if(in_array($attribute, $type_data)) {
            $return['inheritance'][] = $property_type;
          }
        }
      }

      if(is_array($wp_properties['predefined_values']) && ($predefined_values = $wp_properties['predefined_values'][$attribute]))  {
        $return['predefined_values'] = $predefined_values;
      }

      if(is_array($wp_properties['predefined_search_values']) && ($predefined_values = $wp_properties['predefined_search_values'][$attribute]))  {
        $return['predefined_search_values'] = $predefined_values;
      }

      if(is_array($wp_properties['sortable_attributes']) && in_array($attribute, $wp_properties['sortable_attributes'])) {
        $return['sortable'] = true;
        $ui_class[] = 'sortable';
      }

      if(is_array($wp_properties['hidden_frontend_attributes']) && in_array($attribute, $wp_properties['hidden_frontend_attributes'])) {
        $return['hidden_frontend_attribute'] = true;
        $ui_class[] = 'fe_hidden';
      }

      if(is_array($wp_properties['currency_attributes']) && in_array($attribute, $wp_properties['currency_attributes'])) {
        $return['currency'] = true;
        $ui_class[] = 'currency';
      }

      if(is_array($wp_properties['numeric_attributes']) && in_array($attribute, $wp_properties['numeric_attributes'])) {
        $return['numeric'] = true;
        $ui_class[] = 'numeric';
      }

      if(is_array($wp_properties['searchable_attributes']) && in_array($attribute, $wp_properties['searchable_attributes'])) {
        $return['searchable'] = true;
        $ui_class[] = 'searchable';
      }

      if(empty($return['title'])) {
        $return['title'] = WPP_UD_F::de_slug($return['slug']);
      }

      $return['ui_class'] = implode(' wpp_', $ui_class);

      return apply_filters('wpp_attribute_data', $return);

    }

  /**
   * Makes sure the script is loaded, otherwise loads it
   *
   * @version 1.17.3
   */
  function force_script_inclusion($handle = false){
    global $wp_scripts;

    //** WP 3.3+ allows inline wp_enqueue_script(). Yay. */
    wp_enqueue_script($handle);

    if(!$handle) {
      return;
    }

    //** Check if already included */
    if(wp_script_is($handle, 'done')) {
      return true;
    }

    //** Check if script has dependancies that have not been loaded */
    if(is_array($wp_scripts->registered[$handle]->deps)) {
      foreach($wp_scripts->registered[$handle]->deps as $dep_handle) {
        if(!wp_script_is($dep_handle, 'done')) {
          $wp_scripts->in_footer[] = $dep_handle;
        }
      }
    }

    //** Force script into footer */
    $wp_scripts->in_footer[] = $handle;

    //  echo "<pre>" . print_r($wp_scripts, true) . "</pre>"; return;
  }

  /**
   * Returns an array of all keys that can be queried using property_overview
   *
   * @version 1.17.3
   */
  function get_queryable_keys(){
    global $wp_properties;

    $keys = array_keys($wp_properties['property_stats']);

    foreach($wp_properties['searchable_attributes'] as $attr){
      if(!in_array($attr, $keys)) {
        $keys[] = $attr;
      }
    }

    $keys[] = 'post_title';
    $keys[] = 'post_date';
    $keys[] = 'post_id';
    $keys[] = 'post_parent';
    $keys[] = 'property_type';
    $keys[] = 'featured';

    //* Adds filter for ability to apply custom queryable keys */
    $keys = apply_filters('get_queryable_keys', $keys);

    return $keys;
  }

    /**
     * Returns array of sortable attributes if set, or default
     *
     * @version 1.17.2
     */
    function get_sortable_keys(){
      global $wp_properties;

      if (!empty($wp_properties['property_stats']) && $wp_properties['sortable_attributes']) {
        foreach ($wp_properties['property_stats'] as $slug => $label) {
          if(in_array($slug, $wp_properties['sortable_attributes'])) {
            $sortable_attrs[$slug] = $label;
          }
        }
      }

      if(!empty($sortable_attrs)) {
        return $sortable_attrs;
      }

      // If not set, menu_order will not be used at all if any of the attributes are marked as searchable
      $sortable_attrs = array(
        'menu_order' => __('Default', 'wpp'),
        'post_title' => __('Title', 'wpp')
      );

      if(!empty($sortable_attrs)) {
        return $sortable_attrs;
      }
    }


  /**
   * Pre post query - for now mostly to disable caching
   *
   * Called in &get_posts() in query.php
   *
   * @todo This function is a hack. Need to use post_type rewrites better. - potanin@UD
   *
   * @version 1.26.0
   */
    function posts_results($posts) {
      global $wpdb, $wp_query;


      //** Look for child properties */
      if(!empty($wp_query->query_vars['attachment'])) {
        $post_name = $wp_query->query_vars['attachment'];

        if($child = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE post_name = '$post_name' AND post_type = 'property' AND post_parent != '' LIMIT 0, 1")) {
          $posts[0] = $child;
          return $posts;
        }
      }


      //** Look for regular pages that are placed under base slug */
      if($wp_query->query_vars['post_type'] == 'property' && count($wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE post_name = '{$wp_query->query_vars['name']}' AND post_type = 'property'  LIMIT 0, 1")) == 0) {
        $posts[] = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE post_name = '{$wp_query->query_vars['name']}' AND post_type = 'page'  LIMIT 0, 1");
      }

      return $posts;


    }

  /**
   * Pre post query - for now mostly to disable caching
   *
   * @version 1.17.2
   */
    function pre_get_posts($query){
      global $wp_properties;

      if($wp_properties['configuration']['disable_wordpress_postmeta_cache'] != 'true') {
        return;
      }

      if($query->query_vars['post_type'] == 'property') {
        $query->query_vars['cache_results'] = false;
      }



    }



  /**
   * Format a number as numeric
   *
   * @version 1.16.3
   */
   function format_numeric($content = '') {
    global $wp_properties;

      $content = trim($content);

      $dec_point  = (!empty($wp_properties['configuration']['dec_point']) ? $wp_properties['configuration']['dec_point'] : ".");
      $thousands_sep  = (!empty($wp_properties['configuration']['thousands_sep']) ? $wp_properties['configuration']['thousands_sep'] : ",");

      if(is_numeric($content)) {
        return number_format($content,0,$dec_point,$thousands_sep);
      } else {
        return $content;
      }

    }


  /**
   * Checks if an file exists in the uploads directory from a URL
   *
   * Only works for files in uploads folder.
   *
   * @todo update to handle images outside the uploads folder
   *
   * @version 1.16.3
   */
   function file_in_uploads_exists_by_url($image_url = '') {

    if(empty($image_url)) {
      return false;
    }

    $upload_dir = wp_upload_dir();
    $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);

    if(file_exists($image_path)) {
      return true;
    }

    return false;

   }


  /**
   * Setup default property page.
   *
   *
   * @version 1.16.3
   */
   function setup_default_property_page() {
      global $wpdb, $wp_properties,  $user_ID;

      $base_slug = $wp_properties['configuration']['base_slug'];

      //** Check if this page actually exists */
      $post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = '{$base_slug}'");


      if($post_id) {
        //** Page already exists */
        return $post_id;
      }

      //** Check if page with this post name already exists */
      if($post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = 'properties'")) {
        return array(
          'post_id' => $post_id,
          'post_name' => 'properties'
        );
      }

      $property_page = array(
        'post_title' => __('Properties', 'wpp'),
        'post_content' => '[property_overview]',
        'post_name' => 'properties',
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' =>  $user_ID
      );

      $post_id = wp_insert_post($property_page);

      if(!is_wp_error($post_id)) {
        //** get post_name of new page */
        $post_name = $wpdb->get_var("SELECT post_name FROM {$wpdb->posts} WHERE ID = '{$post_id}'");

        return array(
          'post_id' => $post_id,
          'post_name' => $post_name
        );

      }

      return false;

  }

   /**
   * Perform WPP related things when a post is being deleted
   *
   * Makes sure all attached files and images get deleted.
   *
   *
   * @version 1.16.1
   */
   function before_delete_post($post_id) {
      global $wpdb, $wp_properties;

      if($wp_properties['configuration']['auto_delete_attachments'] != 'true') {
        return;
      }

      //* Make sure this is a property */
      $is_property = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE ID = {$post_id} AND post_type = 'property'");

      if(!$is_property) {
        return;
      }

      $uploads = wp_upload_dir();

      //* Get Attachments */
      $attachments = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$post_id} AND post_type = 'attachment' ");

      if($attachments) {
        foreach($attachments as $attachment_id) {

          $file_path = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = {$attachment_id} AND meta_key = '_wp_attached_file' ");

          wp_delete_attachment($attachment_id, true);

          if($file_path) {
            $attachment_directories[] = $uploads['basedir'] . '/' . dirname($file_path);
          }

        }
      }

      if(is_array($attachment_directories)) {
        $attachment_directories = array_unique($attachment_directories);
        foreach($attachment_directories as $dir) {
          @rmdir($dir);
        }

      }


  }




  /**
   * Get advanced details about an image (mostly for troubleshooting)
   *
   * @todo add some sort of light validating that the the passed item here is in fact an image
   *
   */
   function get_property_image_data($requested_id) {
      global $wpdb;

      if(empty($requested_id)) {
        return false;
      }

      ob_start();

      if(is_numeric($requested_id)) {

        $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID = '$requested_id'");
      } else {
        //** Try and image search */
        $image_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '%{$requested_id}%' ");


        if($image_id) {
          $post_type = 'image';
          $requested_id = $image_id;
        }
      }

      if($post_type == 'property') {

        //** Get Property Images */
        $property = WPP_F::get_property($requested_id);

        echo 'Requested Property: ' . $property['post_title'];
        $data = get_children( array('post_parent' => $requested_id, 'post_type' => 'attachment', 'post_mime_type' => 'image',  'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );
        echo "\nProperty has: " . count($data) . ' images.';

        foreach($data as $img) {
          $image_data['ID'] = $img->ID;
          $image_data['post_title'] = $img->post_title;

          $img_meta = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = '{$img->ID}'");

          foreach($img_meta as $i_m) {
            $image_data[$i_m->meta_key] = maybe_unserialize($i_m->meta_value);
          }
          print_r($image_data);

        }



      } else {

        $data = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID = '$requested_id'");
        $image_meta = $wpdb->get_results("SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = '$requested_id'");
        foreach($image_meta  as $m_data) {

          print_r($m_data->meta_id);
          echo "<br />";
          print_r($m_data->meta_key);
          echo "<br />";
          print_r(maybe_unserialize($m_data->meta_value));
        }

      }

      $return_data = ob_get_contents();
      ob_end_clean();

      return $return_data;

   }

  /**
   * Resizes (generate) image.
   *
   * @todo add some sort of light validating that the the passed item here is in fact an image
   *
   * If image has no meta data (for instance, if imported via XML Importer), this function
   * what _wp_attachment_metadata the wp_generate_attachment_metadata() function would ideally regenerate.
   *
   * @todo Update so when multiple images are passed the first requested image data is returned
   *
   * @param integer(string) $attachment_id
   * @param array $sizes. Arrays with sizes, or single name, later converted into array
   * @return array. Image data for first image size (if multiple provided). Or FALSE if file could not be generated.
   * @since 1.6
   */
  static function generate_image($attachment_id, $sizes = array()) {
    global $_wp_additional_image_sizes;

    // Determine if params are empty
    if(empty($attachment_id) || empty($sizes)) {
      return false;
    }

    if(!is_array($sizes)) {
      $sizes = array($sizes);
    }


    // Check if image file exists
    $file = get_attached_file( $attachment_id );
    if(empty($file)) {
      return false;
    }

    //** Get attachment metadata */
    $metadata = get_post_meta($attachment_id, '_wp_attachment_metadata', true);

    if(empty($metadata)) {

        include_once  ABSPATH . 'wp-admin/includes/image.php';

      /*
        If image has been imported via XML it may not have meta data
        Here we attempt tp replicate wp_generate_attachment_metadata() but only generate the
        minimum requirements for image meta data and we do not create ALL variations of image, just the requested.
      */

      $metadata = array();
      $imagesize = @getimagesize( $file );
      $metadata['width'] = $imagesize[0];
      $metadata['height'] = $imagesize[1];

      // Make the file path relative to the upload dir
      $metadata['file'] = _wp_relative_upload_path($file);

      if ( $image_meta = wp_read_image_metadata( $file ) ) {
        $metadata['image_meta'] = $image_meta;
      }

    }


    //** Get width, height and crop for new image */
    foreach($sizes as $size) {
      if ( isset( $_wp_additional_image_sizes[$size]['width'] ) ) {
        $width = intval( $_wp_additional_image_sizes[$size]['width'] ); // For theme-added sizes
      } else {
        $width = get_option( "{$size}_size_w" ); // For default sizes set in options
      } if ( isset( $_wp_additional_image_sizes[$size]['height'] ) ) {
        $height = intval( $_wp_additional_image_sizes[$size]['height'] ); // For theme-added sizes
      } else {
        $height = get_option( "{$size}_size_h" ); // For default sizes set in options
      } if ( isset( $_wp_additional_image_sizes[$size]['crop'] ) ) {
        $crop = intval( $_wp_additional_image_sizes[$size]['crop'] ); // For theme-added sizes
      } else {
        $crop = get_option( "{$size}_crop" ); // For default sizes set in options
      }

      //** Try to generate file and update attachment data */
      $resized[$size] = image_make_intermediate_size( $file, $width, $height, $crop );

    }

    if(empty($resized[$size])) {
      return false;
    }


    //** Cycle through resized and remove any blanks (would happen if image already exists)  */
    foreach($resized as $key => $size_info) {
      if(empty($size_info)) {
        unset($resized[$key]);
      }
    }


    if (!empty( $resized )) {

      foreach($resized as $size => $resize) {
        $metadata['sizes'][$size] = $resize;
      }


      update_post_meta($attachment_id, '_wp_attachment_metadata', $metadata);

      //** Return first requested image **/

      return $resized;

    }

    return false;
  }


  /**
   * Check if theme-specific stylesheet exists.
   *
   * get_option('template') seems better choice than get_option('stylesheet'), which returns the current theme's slug
   * which is a problem when a child theme is used. We want the parent theme's slug.
   *
   * @since 1.6
   *
   */
   static function has_theme_specific_stylesheet() {

    $theme_slug = get_option('template');

    if(file_exists( WPP_Templates . "/theme-specific/{$theme_slug}.css")) {
      return true;
    }

    return false;

  }


  /**
   * Check permissions and ownership of premium folder.
   *
   * @since 1.13
   *
   */
   static function check_premium_folder_permissions() {
    global $wp_messages;

    // If folder is writable, it's all good
    if(!is_writable(WPP_Premium . "/"))
      $writable_issue = true;
    else
      return;


    // If not writable, check if this is an ownerhsip issue
    if(function_exists('posix_getuid')) {
      if(fileowner(WPP_Path) != posix_getuid())
        $ownership_issue = true;
    } else {
      if($writable_issue)
        $wp_messages['error'][] = __('If you have problems automatically downloading premium features, it may be due to PHP not having ownership issues over the premium feature folder.','wpp');
    }


    // Attempt to take ownership -> most likely will not work
    if($ownership_issue) {
      if (@chown(WPP_Premium, posix_getuid())) {
        //$wp_messages['error'][] = __('Succesfully took permission over premium folder.','wpp');
        return;
      } else {
        $wp_messages['error'][] = __('There is an ownership issue with the premium folder, which means your site cannot download WP-Property premium features and receive updates.  Please contact your host to fix this - PHP needs ownership over the <b>wp-content/plugins/wp-property/core/premium</b> folder.  Be advised: changing the file permissions will not fix this.','wpp');
      }

    }


    if(!$ownership_issue && $writable_issue)
      $wp_messages['error'][] = __('One of the folders that is necessary for downloading additional features for the WP-Property plugin is not writable.  This means features cannot be downloaded.  To fix this, you need to set the <b>wp-content/plugins/wp-property/core/premium</b> permissions to 0755.','wpp');

    if($wp_messages)
      return $wp_messages;

    return false;


   }


  /**
   * Revalidate all addresses
   *
   * Revalidates addresses of all publishd properties.
   * If Google daily addres lookup is exceeded, breaks the function and notifies the user.
   *
    * @since 1.05
   *
    */
   static function revalidate_all_addresses($args = '') {
    global $wp_properties, $wpdb;

    $defaults = array(
      'property_ids' => false,
      'echo_result' => 'true',
      'skip_existing' => 'false',
      'return_geo_data' => false
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if(is_array($property_ids)) {
      $all_properties = $property_ids;
    } else {
      $all_properties = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property' AND post_status = 'publish'");
    }

    $google_map_localizations = WPP_F::draw_localization_dropdown('return_array=true');

     foreach($all_properties as $post_id) {

      $current_coordinates = get_post_meta($post_id,'latitude', true) . get_post_meta($post_id,'longitude', true);

      if($skip_existing == 'true' && !empty($current_coordinates)) {
        continue;
      }

      $address = get_post_meta($post_id, $wp_properties['configuration']['address_attribute'], true);

      $geo_data = UD_F::geo_locate_address($address, $wp_properties['configuration']['google_maps_localization'], true);

      $coordinates = get_post_meta($post_id,'latitude', true) . get_post_meta($post_id,'longitude', true);

      if(!empty($geo_data->formatted_address)) {
        update_post_meta($post_id, 'address_is_formatted', true);
        update_post_meta($post_id, $wp_properties['configuration']['address_attribute'], $geo_data->formatted_address);
        update_post_meta($post_id, 'street_number', $geo_data->street_number);
        update_post_meta($post_id, 'route', $geo_data->route);
        update_post_meta($post_id, 'city', $geo_data->city);
        update_post_meta($post_id, 'county', $geo_data->county);
        update_post_meta($post_id, 'state', $geo_data->state);
        update_post_meta($post_id, 'state_code', $geo_data->state_code);
        update_post_meta($post_id, 'country', $geo_data->country);
        update_post_meta($post_id, 'country_code', $geo_data->country_code);
        update_post_meta($post_id, 'postal_code', $geo_data->postal_code);

        if (get_post_meta($post_id, 'manual_coordinates', true) != 'true' &&
          get_post_meta($post_id, 'manual_coordinates', true) != '1') {

          update_post_meta($post_id, 'latitude', $geo_data->latitude);
          update_post_meta($post_id, 'longitude', $geo_data->longitude);
        }

        if($return_geo_data) {
          $return['geo_data'][$post_id] = $geo_data;
        }

        $updated[] = $post_id;

      } else {
          // Try to figure out what went wrong

        $failed[] = $post_id;
        update_post_meta($post_id, 'address_is_formatted', false);
      }

    }


    $return['success'] = 'true';
    $return['message'] = "Updated " . count($updated) . " properties using the " . $google_map_localizations[$wp_properties['configuration']['google_maps_localization']] .  " localization.";

    if($failed) {
      $return['message'] .= "<br />" . count($failed) . " properties could not be updated.";
    }

    if($echo_result == 'true') {
      echo json_encode($return);
    } else {
      return $return;
    }

    return;
  }

  /**
   * Minify JavaScript
   *
    * Uses third-party JSMin if class isn't declared.
    * If WP3 is detected, class not loaded to avoid footer warning error.
    * If for some reason W3_Plugin is active, but JSMin is not found,
   * we load ours to avoid breaking property maps.
    *
    * @since 1.06
   *
    */
  static function minify_js($data) {

    if(!class_exists('W3_Plugin')) {
      include_once WPP_Path. '/third-party/jsmin.php';
    } elseif(file_exists(WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php')) {
      include_once WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php';
    } else {
      include_once WPP_Path. '/third-party/jsmin.php';
    }

    if(class_exists('JSMin')) {
      $data = JSMin::minify($data);
    }

    return $data;

  }

  /**
   * Gets image dimensions for WP-Property images
   *
    *
    * @since 1.0
   *
    */
   static function get_image_dimensions($type = false) {
    global $wp_properties;

    if(!$type) {
      return;
    }

    $dimensions = $wp_properties['image_sizes'][$type];

    $return[0] = $dimensions['width'];
    $return[1] = $dimensions['height'];
    $return['width'] = $dimensions['width'];
    $return['height'] = $dimensions['height'];

    return $return;

   }


  /**
   * Prevents all columns on the overview page from being enabled if nothing is configured
   *
    *
    * @since 0.721
   *
    */
  static function fix_screen_options() {
    global $current_user;

    $user_id = $current_user->data->ID;

    $current = get_user_meta($user_id, 'manageedit-propertycolumnshidden', true);

    $default_hidden[] = 'type';
    $default_hidden[] = 'price';
    $default_hidden[] = 'bedrooms';
    $default_hidden[] = 'bathrooms';
    $default_hidden[] = 'deposit';
    $default_hidden[] = 'area';
    $default_hidden[] = 'phone_number';
    $default_hidden[] = 'purchase_price';
    $default_hidden[] = 'for_sale';
    $default_hidden[] = 'for_rent';
    $default_hidden[] = 'city';
    $default_hidden[] = 'featured';
    $default_hidden[] = 'menu_order';

    if(empty($current)) {
      update_user_meta($user_id, 'manageedit-propertycolumnshidden', $default_hidden);
    }


  }


  /**
   * Determines most common property type (used for defaults when needed)
   *
   *
   * @since 0.55
   *
   */
  static function get_most_common_property_type($array = false) {
    global $wpdb, $wp_properties;

    $type_slugs = array_keys($wp_properties['property_types']);

    $top_property_type = $wpdb->get_col("
      SELECT DISTINCT(meta_value)
      FROM {$wpdb->postmeta}
      WHERE meta_key = 'property_type'
      GROUP BY meta_value
      ORDER BY  count(meta_value) DESC
    ");

    if(is_array($top_property_type)) {
      foreach($top_property_type as $slug) {
        if(isset($wp_properties['property_types'][$slug])) {
          return $slug;
        }
      }
    }

    //* No DB entries, return first property type in settings */
    return $type_slugs[0];

  }


  /**
   * Splits a query string properly, using preg_split to avoid conflicts with dashes and other special chars.
   * @param string $query string to split
   * @return Array
   */
  static function split_query_string($query) {
    /**
     * Split the string properly, so no interference with &ndash; which is used in user input.
     */
    //$data = preg_split( "/&(?!&ndash;)/", $query );
    //$data = preg_split( "/(&(?!.*;)|&&)/", $query );
    $data = preg_split( "/&(?!([a-zA-Z]+|#[0-9]+|#x[0-9a-fA-F]+);)/", $query );

    return $data;
  }

  /**
  * Handles user input, so a standard is created for supporting special characters.
  *
  * Added fix for PHP versions earlier than 4.3.0
  *
  * @param  string   $input to be converted
  * @return   string   $result
  */
  static function encode_mysql_input( $input, $meta_key = false) {

    if($meta_key == 'latitude' || $meta_key == 'longitude') {
      return (float)$input;
    }

    /* If PHP version is newer than 4.3.0, else apply fix. */
    if ( strnatcmp(phpversion(),'4.3.0' ) >= 0 ) {
      $result = str_replace( html_entity_decode('-', ENT_COMPAT, 'UTF-8'), '&ndash;', $input );

    } else {
      $result = str_replace( utf8_encode( html_entity_decode('-') ), '&ndash;', $input );
    }

    //** In case &ndash; is already converted and exists in its actual dash form */
    $result = str_replace('–', '&ndash;', $result);

    /* Uses WPs built in esc_html, works like a charm. */
    $result = esc_html( $result );

    return $result;
  }


/**
  * Handles user input, so a standard is created for supporting special characters.
  *
  * @param  string   $string to be converted
  * @return   string   $result
  */
  static function decode_mysql_output( $output ) {

    $result = html_entity_decode( $output );

    return $result;

  }


  /**
   * Determines if all of the arrays values are numeric
   *
   *
   * @since 0.55
   *
   */
  static function is_numeric_range($array = false) {
    if(!is_array($array) || empty($array)) {
      return false;
    }
    foreach($array as $value) {
      if(!is_numeric($value)) {
        return false;
      }
    }
    return true;
  }

  static function draw_property_type_dropdown($args = '') {
    global $wp_properties;

    $defaults = array('id' => 'wpp_property_type',  'name' => 'wpp_property_type',  'selected' => '');
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


    if(!is_array($wp_properties['property_types']))
      return;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    foreach($wp_properties['property_types'] as $slug => $label)
      $return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . "'>$label</option>";
    $return .= "</select>";

    return $return;


  }

  static function draw_property_dropdown($args = '') {
    global $wp_properties, $wpdb;

    $defaults = array('id' => 'wpp_properties',  'name' => 'wpp_properties',  'selected' => '');
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $all_properties = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'property' AND post_status = 'publish'");

    if(!is_array($all_properties))
      return;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    foreach($all_properties as $p_data)
      $return .= "<option value='$p_data->id' " . ($selected == $p_data->id ? " selected='true' " : "") . "'>{$p_data->post_title}</option>";
    $return .= "</select>";

    return $return;


  }

/**
  * Return an array of all available attributes and meta keys
  *
  */
  static function get_total_attribute_array($args = '', $extra_values = false) {
    global $wp_properties, $wpdb;

    $defaults = array('use_optgroups' => 'false');

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


    $property_stats = $wp_properties['property_stats'];
    $property_meta = $wp_properties['property_meta'];

    if(!is_array($extra_values)) {
      $extra_values = array();
    }

    if($use_optgroups == 'true') {
      $attributes['Attributes'] = $property_stats;
      $attributes['Meta'] = $property_meta;
      $attributes['Other'] = $extra_values;
    } else {
      $attributes = $property_stats + $property_meta + $extra_values;
    }

    return apply_filters('wpp_total_attribute_array', $attributes);


  }

/**
  * Render a dropdown of property attributes.
  *
  */
  static function draw_attribute_dropdown($args = '', $extra_values = false) {
    global $wp_properties, $wpdb;

    $defaults = array('id' => 'wpp_attribute',  'name' => 'wpp_attribute',  'selected' => '');
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $attributes = $wp_properties['property_stats'];

    if(is_array($extra_values)) {
     $attributes = array_merge($extra_values, $attributes);
    }

    if(!is_array($attributes))
      return;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
      $return .= "<option value=''> - </option>";

    foreach($attributes as $slug => $label)
      $return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . ">$label ($slug)</option>";
    $return .= "</select>";

    return $return;


  }

  static function draw_localization_dropdown($args = '') {
    global $wp_properties, $wpdb;

    $defaults = array('id' => 'wpp_google_maps_localization',  'name' => 'wpp_google_maps_localization',  'selected' => '', 'return_array' => 'false');
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $attributes = array(
      'en' => 'English',
      'ar' => 'Arabic',
      'bg' => 'Bulgarian',
      'cs' => 'Czech',
      'de' => 'German',
      'el' => 'Greek',
      'es' => 'Spanish',
      'fr' => 'French',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'da' => 'Danish',
      'nl' => 'Dutch',
      'no' => 'Norwegian',
      'pt' => 'Portuguese',
      'pt-BR' => 'Portuguese (Brazil)',
      'pt-PT' => 'Portuguese (Portugal)',
      'ru' => 'Russian',
      'sv' => 'Swedish',
      'th' => 'Thai',
      'uk' => 'Ukranian');

    $attributes = apply_filters("wpp_google_maps_localizations", $attributes);


    if(!is_array($attributes))
      return;

    if($return_array == 'true')
      return $attributes;

    $return = "<select id='$id' " . (!empty($name) ? " name='$name' " : '') . " >";
    foreach($attributes as $slug => $label)
      $return .= "<option value='$slug' " . ($selected == $slug ? " selected='true' " : "") . "'>$label ($slug)</option>";
    $return .= "</select>";

    return $return;


  }



  /**
   * Checks for updates against TwinCitiesTech.com Server
   *
   *
   * @since 0.55
   * @version 1.13.1
   *
    */
  static function feature_check($return = false) {
    global $wp_properties;

    $blogname = get_bloginfo('url');
    $blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
    $system = 'wpp';
    $wpp_version = get_option( "wpp_version" );

    //** Get API key - force API key update just in case */
    $api_key = wpi_property_export::get_api_key(array('force_check' => true, 'return' => true));

    $check_url = "http://updates.usabilitydynamics.com/?system={$system}&site={$blogname}&system_version={$wpp_version}&api_key={$api_key}";

    $response = @wp_remote_get($check_url);

     if(!$response) {
      return;
    }

    // Check for errors
    if(is_object($response) && !empty($response->errors)) {

      foreach($response->errors as $update_errrors) {
        $error_string .= implode(",", $update_errrors);
        UD_F::log("Feature Update Error: " . $error_string);
      }

      if($return) {
        return sprintf(__('An error occured during premium feature check: <b> %s </b>.','wpp'), $error_string);
      }

      return;
    }

    // Quit if failture
    if($response['response']['code'] != '200') {
      return;
    }

   $response = @json_decode($response['body']);

    if(is_object($response->available_features)) {

      $response->available_features = UD_F::objectToArray($response->available_features);

      // Updata database
      $wpp_settings = get_option('wpp_settings');
      $wpp_settings['available_features'] =  UD_F::objectToArray($response->available_features);
      update_option('wpp_settings', $wpp_settings);


    } // available_features



    if(strlen($api_key) != 40) {
      if($return) {
        if(empty($api_key)) {
          $api_key = __("The API key could not be generated.", 'wpp');
        }
        return sprintf(__('An error occured during premium feature check: <b>%s</b>.','wpp'), $api_key);
      } else {
        return;
      }
    }


    if($response->features == 'eligible' && $wp_properties['configuration']['disable_automatic_feature_update'] != 'true') {

      // Try to create directory if it doesn't exist
      if(!is_dir(WPP_Premium)) {
        @mkdir(WPP_Premium, 0755);
      }

      // If didn't work, we quit
      if(!is_dir(WPP_Premium)) {
        continue;
      }

      // Save code
      if(is_object($response->code)) {
        foreach($response->code as $code) {

          $filename = $code->filename;
          $php_code = $code->code;
          $version = $code->version;

          // Check version

          $default_headers = array(
          'Name' => __('Feature Name','wpp'),
          'Version' => __('Version','wpp'),
          'Description' => __('Description','wpp')
          );

          $current_file = @get_file_data( WPP_Premium . "/" . $filename, $default_headers, 'plugin' );
          //echo "$filename - new version: $version , old version:$current_file[Version] |  " .  @version_compare($current_file[Version], $version) . "<br />";

          if(@version_compare($current_file['Version'], $version) == '-1') {
            $this_file = WPP_Premium . "/" . $filename;
            $fh = @fopen($this_file, 'w');
            if($fh) {
              fwrite($fh, $php_code);
              fclose($fh);

              if($current_file[Version])
                UD_F::log(sprintf(__('WP-Property Premium Feature: %s updated to version %s from %s.','wpp'), $code->name, $version, $current_file['Version']));
              else
                UD_F::log(sprintf(__('WP-Property Premium Feature: %s updated to version %s.','wpp'), $code->name, $version));

              $updated_features[] = $code->name;
            }
          } else {

          }


        }
      }
    }

    // Update settings
    WPP_F::settings_action(true);

    if($return && $wp_properties['configuration']['disable_automatic_feature_update'] == 'true') {
      return __('Update ran successfully but no features were downloaded because the setting is disabled. Enable in the "Developer" tab.','wpp');

    } elseif($return) {
      return __('Update ran successfully.','wpp');
    }
  }


  /**
   * Makes a given property featured, usuall called via ajax
   *
    *
    * @since 0.721
   *
    */
   static function toggle_featured($post_id = false) {
    global $current_user;

    if(!current_user_can('manage_options'))
      return;

    if(!$post_id)
      return;

    $featured = get_post_meta($post_id, 'featured', true);

    // Check if already featured
    if($featured == 'true') {
      update_post_meta($post_id, 'featured', 'false');
      $status = 'not_featured';
    } else {
      update_post_meta($post_id, 'featured', 'true');
      $status = 'featured';
    }

    echo json_encode(array('success' => 'true', 'status' => $status, 'post_id' => $post_id));

   }

   /**
   * Add or remove taxonomy columns
   * @since 3.0
   */
  function overview_columns($columns) {
    global $wp_properties;

    $overview_columns = apply_filters('wpp_overview_columns',  array(
      'cb' => '',
      'title' => __('Title', 'wpp'),
      'property_type' => __('Type', 'wpp'),
      'overview' => __('Overview', 'wpp'),
      'features' => __('Features', 'wpp'),
      'featured' => __('Featured', 'wpp')
    ));

    $overview_columns['thumbnail'] = __('Thumbnail', 'wpp');

    foreach($overview_columns as $column => $title) {
      $columns[$column] = $title;
    }

    return $columns;

  }

  function custom_attribute_columns( $columns ) {
    global $wp_properties;

    if ( !empty( $wp_properties['column_attributes'] ) ) {

      foreach( $wp_properties['column_attributes'] as $id => $slug ) {
        $columns[$slug] = __( $wp_properties['property_stats'][$slug], 'wpp' );
      }

    }

    return $columns;

  }

  /**
   * Displays dropdown of available property size images
   *
    *
    * @since 0.54
   *
    */
  static function image_sizes_dropdown($args = "") {
    global $wp_properties;

    $defaults = array(
      'name' => 'wpp_image_sizes',
      'selected' => 'none',
      'blank_selection_label' => ' - '
      );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if(empty($id) && !empty($name)) {
      $id = $name;
    }


    $image_array = get_intermediate_image_sizes();


    ?>
      <select id="<?php echo $id ?>" name="<?php echo $name ?>" >
        <option value=""><?php echo $blank_selection_label; ?></option>
          <?php
            foreach($image_array as $name) {
            $sizes = WPP_F::image_sizes($name);

            if(!$sizes)
              continue;

          ?>
            <option value='<?php echo $name; ?>' <?php if($selected == $name) echo 'SELECTED'; ?>>
               <?php echo $name; ?>: <?php echo $sizes['width']; ?>px by <?php echo $sizes['height']; ?>px
            </option>
          <?php } ?>
      </select>

    <?php
  }


  /**
   * Returns image sizes for a passed image size slug
   *
    *
    * @since 0.54
   *
    */
  static function image_sizes($type = false, $args = "") {
    global $_wp_additional_image_sizes;

    $defaults = array(
      'return_all' => false
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if(!$type) {
      return false;
    }

    if(isset($_wp_additional_image_sizes[$type]) && is_array($_wp_additional_image_sizes[$type])) {
      $return = $_wp_additional_image_sizes[$type];

    } else {

      if($type == 'thumbnail' || $type == 'thumb') {
        $return = array('width' => intval(get_option('thumbnail_size_w')), 'height' => intval(get_option('thumbnail_size_h')));
      }

      if($type == 'medium') {
        $return = array('width' => intval(get_option('medium_size_w')), 'height' => intval(get_option('medium_size_h')));
      }

      if($type == 'large') {
        $return = array('width' => intval(get_option('large_size_w')), 'height' => intval(get_option('large_size_h')));
      }

    }

    if(!is_array($return))
      return;

    if(!$return_all) {

      // Zeroed out dimensions means they are deleted
      if(empty($return['width']) || empty($return['height']))
        return;

      // Zeroed out dimensions means they are deleted
      if($return['width'] == '0' || $return['height'] == '0')
        return;

    }

    // Return dimensions
    return $return;

  }


    /**
   * Saves settings, applies filters, and loads settings into global variable
   *
   * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
   *
   * As of 1.11 prevents removal of premium feature configurations that are not held in the settings page array
   *
   * 1.12 - added taxonomies filter: wpp_taxonomies
   * 1.14 - added backup from text file
   *
   * @return array|$wp_properties
   * @since 1.12
   *
    */
  static function settings_action($force_db = false) {
    global $wp_properties, $wp_rewrite;

    // Process saving settings
    if(isset($_REQUEST['wpp_settings']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wpp_setting_save') ) {

      // Handle backup
      if($backup_file = $_FILES['wpp_settings']['tmp_name']['settings_from_backup']) {
        $backup_contents = file_get_contents($backup_file);

        if(!empty($backup_contents))
          $decoded_settings = json_decode($backup_contents, true);

        if(!empty($decoded_settings))
          $_REQUEST['wpp_settings'] = $decoded_settings;
      }

      // Allow features to preserve their settings that are not configured on the settings page
      $wpp_settings = apply_filters('wpp_settings_save', $_REQUEST['wpp_settings'], $wp_properties);

      // Prevent removal of featured settings configurations if they are not present
      if(!empty($wp_properties['configuration']['feature_settings'])) {
        foreach($wp_properties['configuration']['feature_settings'] as $feature_type => $preserved_settings) {

          if(empty($_REQUEST['wpp_settings']['configuration']['feature_settings'][$feature_type])) {

            $wpp_settings['configuration']['feature_settings'][$feature_type] = $preserved_settings;

          }

        }
      }


      update_option('wpp_settings', $wpp_settings);

      $wp_rewrite->flush_rules();

      // Load settings out of database to overwrite defaults from action_hooks.
      $wp_properties_db = get_option('wpp_settings');

      // Overwrite $wp_properties with database setting
      $wp_properties = array_merge($wp_properties, $wp_properties_db);

      // Reload page to make sure higher-end functions take affect of new settings
      // The filters below will be ran on reload, but the saving functions won't
      if($_REQUEST['page'] == 'property_settings'); {
        unset($_REQUEST);
        wp_redirect(admin_url("edit.php?post_type=property&page=property_settings&message=updated"));
        exit;
      }


    }

    if($force_db) {

      // Load settings out of database to overwrite defaults from action_hooks.
      $wp_properties_db = get_option('wpp_settings');

      // Overwrite $wp_properties with database setting
      $wp_properties = array_merge($wp_properties, $wp_properties_db);

    }

    add_filter('wpp_image_sizes', array('WPP_F','remove_deleted_image_sizes'));

    // Filers are applied
    $wp_properties['configuration']       = apply_filters('wpp_configuration', $wp_properties['configuration']);
    $wp_properties['location_matters']       = apply_filters('wpp_location_matters', $wp_properties['location_matters']);
    $wp_properties['hidden_attributes']     = apply_filters('wpp_hidden_attributes', $wp_properties['hidden_attributes']);
    $wp_properties['descriptions']         = apply_filters('wpp_label_descriptions' , $wp_properties['descriptions']);
    $wp_properties['image_sizes']         = apply_filters('wpp_image_sizes' , $wp_properties['image_sizes']);
    $wp_properties['search_conversions']     = apply_filters('wpp_search_conversions' , $wp_properties['search_conversions']);
    $wp_properties['searchable_attributes']   = apply_filters('wpp_searchable_attributes' , $wp_properties['searchable_attributes']);
    $wp_properties['searchable_property_types'] = apply_filters('wpp_searchable_property_types' , $wp_properties['searchable_property_types']);
    $wp_properties['property_inheritance']     = apply_filters('wpp_property_inheritance' , $wp_properties['property_inheritance']);
    $wp_properties['property_meta']       = apply_filters('wpp_property_meta' , $wp_properties['property_meta']);
    $wp_properties['property_stats']       = apply_filters('wpp_property_stats' , $wp_properties['property_stats']);
    $wp_properties['property_types']       = apply_filters('wpp_property_types' , $wp_properties['property_types']);
    $wp_properties['taxonomies']         = apply_filters('wpp_taxonomies' , $wp_properties['taxonomies']);

    $wp_properties = stripslashes_deep($wp_properties);

    return $wp_properties;

  }

  static function remove_deleted_image_sizes($sizes) {
    global $wp_properties;

    foreach($sizes as $slug => $size) {
      if($size['width'] == '0' || $size['height'] == '0')
        unset($sizes[$slug]);

    }


    return $sizes;

  }


  /**
   * Loads property values into global $post variables.
   *
   * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
   * Ran after template_redirect.
   * $property is loaded in WPP_Core::template_redirect();
   *
   * @since 0.54
   *
    */
  static function the_post($post) {
    global $post, $property;

    if($post->post_type != 'property') {
      return $post;
    }

    //** Update global $post object to include property specific attributes */
    $post = (object) $property;

  }


  /**
   * Check for premium features and load them
   *
   * @updated 1.6
   * @since 0.624
   *
    */
  static function load_premium() {
    global $wp_properties;

    $default_headers = array(
      'Name' => __('Name','wpp'),
      'Version' => __('Version','wpp'),
      'Description' => __('Description','wpp'),
      'Minimum Core Version' => __('Minimum Core Version','wpp')
    );


    if(!is_dir(WPP_Premium))
      return;

    if ($premium_dir = opendir(WPP_Premium)) {

      if(file_exists(WPP_Premium . "/index.php")) {
        @include_once(WPP_Premium . "/index.php");
      }

      while (false !== ($file = readdir($premium_dir))) {

        if($file == 'index.php')
          continue;

        if(end(@explode(".", $file)) == 'php') {

          $plugin_slug = str_replace(array('.php'), '', $file);

          $plugin_data = @get_file_data( WPP_Premium . "/" . $file, $default_headers, 'plugin' );
          $wp_properties['installed_features'][$plugin_slug]['name'] = $plugin_data['Name'];
          $wp_properties['installed_features'][$plugin_slug]['version'] = $plugin_data['Version'];
          $wp_properties['installed_features'][$plugin_slug]['description'] = $plugin_data['Description'];

          if($plugin_data['Minimum Core Version']) {
            $wp_properties['installed_features'][$plugin_slug]['minimum_wpp_version'] = $plugin_data['Minimum Core Version'];
          }

          //** If feature has a Minimum Core Version and it is more than current version - we do not load **/
          $feature_requires_upgrade = (!empty($wp_properties['installed_features'][$plugin_slug]['minimum_wpp_version']) && (version_compare(WPP_Version, $wp_properties['installed_features'][$plugin_slug]['minimum_wpp_version']) < 0) ? true : false);

          if($feature_requires_upgrade) {

            //** Disable feature if it requires a higher WPP version**/

            $wp_properties['installed_features'][$plugin_slug]['disabled'] = 'true';
            $wp_properties['installed_features'][$plugin_slug]['needs_higher_wpp_version'] = 'true';

          } elseif ($wp_properties['installed_features'][$plugin_slug]['disabled'] != 'true') {

            //** Load feature, everything is good**/

            $wp_properties['installed_features'][$plugin_slug]['needs_higher_wpp_version'] = 'false';

            if(WP_DEBUG == true) {
              include_once(WPP_Premium . "/" . $file);
            } else {
              @include_once(WPP_Premium . "/" . $file);
            }

             // Disable plugin if class does not exists - file is empty
            if(!class_exists($plugin_slug)) {
              unset($wp_properties['installed_features'][$plugin_slug]);
            }

            $wp_properties['installed_features'][$plugin_slug]['disabled'] = 'false';
          } else {
            //* This happens when feature cannot be loaded and is disabled */

            //** We unset requires core upgrade in case feature was update while being disabled */
            $wp_properties['installed_features'][$plugin_slug]['needs_higher_wpp_version'] = 'false';

          }

        }

      }
    }


  }

  /**
   * Check if premium feature is installed or not
   * @param string $slug. Slug of premium feature
   * @return boolean.
   */
  static function check_premium($slug) {
    global $wp_properties;

    if(empty($wp_properties['installed_features'][$slug]['version'])) {
      return false;
    }

    $file = WPP_Premium . "/" . $slug . ".php";

    $default_headers = array(
      'Name' => __('Name','wpp'),
      'Version' => __('Version','wpp'),
      'Description' => __('Description','wpp')
    );

    $plugin_data = @get_file_data( $file , $default_headers, 'plugin' );

    if(!is_array($plugin_data) || empty($plugin_data['Version'])) {
      return false;
    }

    return true;
  }

  static function check_plugin_updates() {
    global $wp_properties;

    echo WPP_F::feature_check(true);

  }


  /**
   * Run on plugin activation.
   *
   * As of WP 3.1 this is not ran on automatic update.
   *
   * @since 1.10
   *
    */
  static function activation() {

    // Do nothing because only ran on activation, not updates, as of 3.1
    // Now handled by WPP_F::manual_activation().

  }


  /**
   * Run manually when a version mismatch is detected.
   *
   * Holds official current version designation.
   * Called in admin_init hook.
   *
   * @since 1.10
   * @version 1.13
   *
    */
  static function manual_activation() {

    $installed_ver = get_option( "wpp_version" );
    $wpp_version = WPP_Version;

    if(@version_compare($installed_ver, $wpp_version) == '-1') {
      // We are upgrading.

      // Unschedule event
      $timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
      wp_unschedule_event($timestamp, 'wpp_premium_feature_check' );
      wp_clear_scheduled_hook('wpp_premium_feature_check');

      // Schedule event
      wp_schedule_event(time(), 'daily', 'wpp_premium_feature_check');

      // Update option to latest version so this isn't run on next admin page load
      update_option( "wpp_version", $wpp_version );

      // Get premium features on activation
      @WPP_F::feature_check();

    }

    return;


  }

  static function deactivation() {
    global $wp_rewrite;
    $timestamp = wp_next_scheduled( 'wpp_premium_feature_check' );
    wp_unschedule_event($timestamp, 'wpp_premium_feature_check' );
    wp_clear_scheduled_hook('wpp_premium_feature_check');

    $wp_rewrite->flush_rules();

  }

  /**
   * Returns array of searchable property IDs
   *
   *
   * @return array|$wp_properties
   * @since 0.621
   *
    */
  static function get_searchable_properties() {
    global $wp_properties;

    $searchable_properties = array();

    if(!is_array($wp_properties['searchable_property_types']))
      return;

    // Get IDs of all property types
    foreach($wp_properties['searchable_property_types'] as $property_type) {

      $this_type_properties = WPP_F::get_properties("property_type=$property_type");

      if(is_array($this_type_properties) && is_array($searchable_properties))
        $searchable_properties = array_merge($searchable_properties, $this_type_properties);
    }

    if(is_array($searchable_properties))
      return $searchable_properties;

    return false;

  }

  /**
   * Returns array of searchable attributes and their ranges
   *
   *
   * @return array|$range
   * @since 0.57
   *
   */
  static function get_search_values($search_attributes, $searchable_property_types, $cache = true, $instance_id = false) {
    global $wpdb, $wp_properties;

    if($instance_id) {
      //** Load value array from cache if it exists (search widget creates it on update */
      $cachefile = WPP_Path . '/cache/searchwidget/' . $instance_id . '.values.res';

      if($cache && is_file($cachefile) && time() - filemtime($cachefile) < 3600) {
        $result = unserialize(file_get_contents($cachefile));
      }
    }

    if(!$result) {
      $query_attributes = "";
      $query_types = "";

      //** Use the requested attributes, or all searchable */
      if(!is_array($search_attributes)) {
        $search_attributes = $wp_properties['searchable_attributes'];
      }

      if(!is_array($searchable_property_types)) {
        $searchable_property_types = explode(',', $searchable_property_types);
        foreach($searchable_property_types as $k => $v) {
          $searchable_property_types[$k] = trim($v);
        }
      }
      $searchable_property_types = "AND pm2.meta_value IN ('" . implode("','", $searchable_property_types) . "')";

      //** Cycle through requested attributes */
      foreach($search_attributes as $searchable_attribute) {

        if($searchable_attribute == 'property_type') {
          continue;
        }

        //** Load attribute data */
        $attribute_data = WPP_F::get_attribute_data($searchable_attribute);

        if($attribute_data['numeric'] || $attribute_data['currency']) {
          $is_numeric = true;
        } else {
          $is_numeric = false;
        }

        //** Check to see if this attribute has predefined values or if we have to get them from DB */
        //** If the attributes has predefind values, we use them */
        if($predefined_search_values = $wp_properties['predefined_search_values'][$searchable_attribute]) {
          $predefined_search_values = str_replace(array(', ', ' ,'), array(',', ','), trim($predefined_search_values));
          $predefined_search_values = explode(',', $predefined_search_values);

          if(is_array($predefined_search_values)) {
            foreach($predefined_search_values as $value) {
              $range[$searchable_attribute][] = $value;
            }
          } else {
            $range[$searchable_attribute][] = $predefined_search_values;
          }

        } else {

          //** No predefined value exist */
          $db_values = $wpdb->get_col("
            SELECT DISTINCT(pm1.meta_value)
            FROM {$wpdb->postmeta} pm1
            LEFT JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '{$searchable_attribute}' AND pm2.meta_key = 'property_type'
            $searchable_property_types
            AND pm1.meta_value != ''
            ORDER BY " . ($is_numeric ? 'ABS(' : ''). "pm1.meta_value" . ($is_numeric ? ')' : ''). " ASC
          ");

          //* Get all available values for this attribute for this property_type */
          $range[$searchable_attribute] = $db_values;

        }

        //** Get unique values*/
        if(is_array($range[$searchable_attribute])) {
          $range[$searchable_attribute] = array_unique($range[$searchable_attribute]);
        } else {
          //* This should not happen */
        }

        foreach($range[$searchable_attribute] as $key => $value) {

          $original_value = $value;

          // Clean up values if a conversion exists
          $value = WPP_F::do_search_conversion($searchable_attribute, trim($value));

          // Fix value with special chars. Disabled here, should only be done in final templating stage.
          // $value = htmlspecialchars($value, ENT_QUOTES);

          //* Remove bad characters signs if attribute is numeric or currency */
          if($is_numeric) {
            $value = str_replace(array(",", "$"), '', $value);
          }

          //** Put cleaned up value back into array */
          $range[$searchable_attribute][$key] = $value;

        }

        //** Sort values */
        sort($range[$searchable_attribute], SORT_REGULAR);

      } //** End single attribute data gather */

      $result = $range;

      if($cachefile) {
        $cachedir = dirname($cachefile);
        if (! is_dir($cachedir)) {
          wp_mkdir_p($cachedir);
        }

        @file_put_contents($cachefile, serialize($result));
      }
    }

    return $result;
  }

  /**
   * Check if a search converstion exists for a attributes value
   */
  static function do_search_conversion($attribute, $value, $reverse = false)  {
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


  /**
   * Primary static function for queries properties  based on type and attributes
   *
   * @todo There is a limitation when doing a search such as 4,5+ then mixture of specific and open ended search is not supported.
   * @since 1.08
   *
   * @param string/ $args
   *
  */
  static function get_properties($args = "", $total = false) {
    global $wpdb, $wp_properties, $wpp_query;

    // Non post_meta fields
    $non_post_meta = array(
      'post_title'  => 'like',
      'post_status' => 'equal',
      'post_author' => 'equal',
      'ID' => 'equal',
      'post_parent' => 'equal',
      'post_date'   => 'date'
    );

    //** added to avoid range and "LIKE" searches on single numeric values *
    if(is_array($args)) {
      foreach($args as $thing => $value) {
        // unset empty filter options
        if ( empty( $value ) ) {
          unset($args[$thing]);
          continue;
        }

        if ( is_array( $value ) ) {
          $value = implode(',', $value);
        }
        $value = trim($value);

        $original_value = $value;

        //** If not CSV and last character is a +, we look for open-ended ranges, i.e. bedrooms: 5+
        if(substr($original_value, -1, 1) == '+' && !strpos($original_value, ',')) {
          //** User requesting an open ended range, we leave it off with a dash, i.e. 500- */
          $args[$thing] = str_replace('+', '', $value) .'-';
        } elseif(is_numeric($value)) {
          //** If number is numeric, we do a specific serach, i.e. 500-500 */
          if ( !key_exists($thing, $non_post_meta) ) {
            $args[$thing] = $value .'-'. $value;
          }
        } elseif(is_string($value)) {
          $args[$thing] = $value;
        }
      }
    }

    $defaults = array(
      'property_type' => 'all'
    );

    $query = wp_parse_args( $args, $defaults );
    $query = apply_filters('wpp_get_properties_query', $query);
    $query_keys = array_keys($query);

    // Search by non meta values
    $additional_sql = '';

    // Show 'publish' posts if status is not specified
    if ( !key_exists( 'post_status', $query ) ) {
      $additional_sql .= " AND p.post_status = 'publish' ";
    } else {
      if ( $query['post_status'] != 'all' ) {
        $additional_sql .= " AND p.post_status = '{$query['post_status']}' ";
      }
      unset($query['post_status']);
    }

    foreach( $non_post_meta as $field => $condition ) {
      if ( key_exists( $field, $query ) ) {
        if ( $condition == 'like' ) {
          $additional_sql .= " AND p.$field LIKE '%{$query[ $field ]}%' ";
        }
        if ( $condition == 'equal' ) {
          $additional_sql .= " AND p.$field = '{$query[ $field ]}' ";
        }
        if ( $condition == 'date' ) {
          $additional_sql .= " AND YEAR(p.$field) = ".substr($query[ $field ], 0, 4)." AND MONTH(p.$field) = ".substr($query[ $field ], 4, 2)." ";
        }
        unset( $query[ $field ] );
      }
    }

    if (substr_count($query['pagi'], '--')) {
      $pagi = explode('--', $query['pagi']);
      if(count($pagi) == 2 && is_numeric($pagi[0]) && is_numeric($pagi[1])) {
        $limit_query = "LIMIT $pagi[0], $pagi[1];";
      }
    }

    unset( $query['pagi'] );
    unset( $query['pagination'] );

    /* Handles the sort_by parameter in the Short Code */
    if( $query['sort_by'] ) {
      $sql_sort_by = $query['sort_by'];
      $sql_sort_order = ($query['sort_order']) ? strtoupper($query['sort_order']) : 'ASC';
    } else {
      $sql_sort_by = 'post_date';
      $sql_sort_order = 'ASC';
    }

    unset( $query['sort_by'] );
    unset( $query['sort_order'] );

    // Go down the array list narrowing down matching properties
    foreach ($query as $meta_key => $criteria) {

      $specific = '';
      $criteria = WPP_F::encode_mysql_input( $criteria, $meta_key);

      // Stop filtering (loop) because no IDs left
      if (isset($matching_ids) && empty($matching_ids)) {
        break;
      }

      if (substr_count($criteria, ',') || substr_count($criteria, '&ndash;') || substr_count($criteria, '--')) {
        if (substr_count($criteria, ',') && !substr_count($criteria, '&ndash;')) {
          $comma_and = explode(',', $criteria);
        }
        if (substr_count($criteria, '&ndash;') && !substr_count($criteria, ',')) {
          $cr = explode('&ndash;', $criteria);

          // Check pieces of criteria. Array should contains 2 integer's elements
          // In other way, it's just value of meta_key
          if(count($cr) > 2 || ((int)$cr[0] == 0 && (int)$cr[1] == 0)) {
            $specific = $criteria;
          } else {
            $hyphen_between = $cr;
            // If min value doesn't exist, set 1
            if(empty($hyphen_between[0])) {
              $hyphen_between[0] = 1;
            }
          }
        }
      } else {
        $specific = $criteria;
      }

      if (!$limit_query) {
        $limit_query = '';
      }

      switch ($meta_key) {

        case 'property_type':

          // Get all property types
          if ($specific == 'all') {
            if (isset($matching_ids)) {
              $matching_id_filter = implode("' OR ID ='", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE (ID ='$matching_id_filter') AND post_type = 'property'");
            } else {
              $matching_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'property'");
            }
            break;
          }

          //** If comma_and is set, $criteria is ignored, otherwise $criteria is used */
          $property_type_array = is_array($comma_and) ? $comma_and : array($specific);

          //** Make sure property type is in slug format */
          foreach($property_type_array as $key => $this_property_type) {
            foreach($wp_properties['property_types'] as $pt_key => $pt_value) {
              if(strtolower($pt_value) == strtolower($this_property_type)) {
                $property_type_array[$key] = $pt_key;
              }
            }
          }

          if ( $comma_and ) {
            //** Multiple types passed */
            $where_string = implode("' OR meta_value ='", $property_type_array);
          } else {
            //** Only on type passed */
            $where_string = $property_type_array[0];
          }


          // See if mathinc_ids have already been filtered down
          if ( isset($matching_ids) ) {
            $matching_id_filter = implode("' OR post_id ='", $matching_ids);
            $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (post_id ='$matching_id_filter') AND (meta_key = 'property_type' AND (meta_value ='$where_string'))");
          } else {
            $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = 'property_type' AND (meta_value ='$where_string'))");
          }

        break;

        default:

          // Get all properties for that meta_key
          if ($specific == 'all' && !$comma_and && !$hyphen_between) {

            if (isset($matching_ids)) {
              $matching_id_filter = implode("' OR post_id ='", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (post_id ='$matching_id_filter') AND (meta_key = '$meta_key')");
              //$wpdb->print_error();
            } else {
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = '$meta_key')");
            }
            break;

          } else {

            if ( $comma_and ) {
              $where_and = "(meta_value ='" . implode("' OR meta_value ='", $comma_and)."')";
              $specific = $where_and;
            }

            if ( $hyphen_between ) {
              // We are going to see if we are looking at some sort of date, in which case we have a special MySQL modifier
              $adate = false;
              if(preg_match('%\d{1,2}/\d{1,2}/\d{4}%i', $hyphen_between[0])) $adate = true;

              if(!empty($hyphen_between[1])) {

                if(preg_match('%\d{1,2}/\d{1,2}/\d{4}%i', $hyphen_between[1])){
                  foreach($hyphen_between as $key => $value) {
                    $hyphen_between[$key] = "STR_TO_DATE('{$value}', '%c/%e/%Y')";
                  }
                  $where_between = "STR_TO_DATE(`meta_value`, '%c/%e/%Y') BETWEEN " . implode(" AND ", $hyphen_between)."";
                } else {
                  $where_between = "`meta_value` BETWEEN " . implode(" AND ", $hyphen_between)."";
                }

              } else {

                if($adate) {
                  $where_between = "STR_TO_DATE(`meta_value`, '%c/%e/%Y') >= STR_TO_DATE('{$hyphen_between[0]}', '%c/%e/%Y')";
                } else {
                  $where_between = "`meta_value` >= $hyphen_between[0]";
                }

              }
              $specific = $where_between;
            }

            if ($specific == 'true') {
              // If properties data were imported, meta value can be '1' instead of 'true'
              // So we're trying to find also '1'
              $specific = "meta_value IN ('true', '1')";
            } elseif(!substr_count($specific, 'meta_value')) {
              // Adds conditions for Searching by partial value
              $s = explode(' ', trim($specific));
              $specific = '';
              $count = 0;
              foreach($s as $p) {
                if($count > 0) {
                  $specific .= " AND ";
                }
                $specific .= "meta_value LIKE '%{$p}%'";
                $count++;
              }
            }

            if (isset($matching_ids)) {
              $matching_id_filter = implode(",", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE post_id IN ($matching_id_filter) AND meta_key = '$meta_key' AND $specific");
            } else {
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '$meta_key' AND $specific $sql_order");
            }

          }
          break;

      } // END switch

      unset( $comma_and );
      unset( $hyphen_between );


    } // END foreach


    // Return false, if there are any result using filter conditions
    if (empty($matching_ids)) {
      return false;
    }

    // Remove duplicates
    $matching_ids = array_unique( $matching_ids );

    // Sorts the returned Properties by the selected sort order
    if ($sql_sort_by &&
        $sql_sort_by != 'menu_order' &&
        $sql_sort_by != 'post_date' &&
        $sql_sort_by != 'post_title' ) {

      /*
       * Determine if all values of meta_key are numbers
       * we use CAST in SQL query to avoid sort issues
       */
      if(self::meta_has_number_data_type ($matching_ids, $sql_sort_by)) {
        $meta_value = "CAST(pm.meta_value AS SIGNED)";
      } else {
        $meta_value = "pm.meta_value";
      }

      $result = $wpdb->get_col("
        SELECT p.ID FROM {$wpdb->posts} AS p
          LEFT JOIN {$wpdb->postmeta} AS pm
          ON p.ID = pm.post_id
          WHERE p.ID IN (" . implode(",", $matching_ids) . ")
            AND p.ID = pm.post_id
            AND pm.meta_key = '$sql_sort_by'
            $additional_sql
          ORDER BY $meta_value $sql_sort_order
          $limit_query");

      // Stores the total Properties returned
      if ($total) {
        $total = count($wpdb->get_col("
          SELECT p.ID FROM {$wpdb->posts} AS p
          LEFT JOIN {$wpdb->postmeta} AS pm
          ON p.ID = pm.post_id
          WHERE p.ID IN (" . implode(",", $matching_ids) . ")
          AND p.ID = pm.post_id
          AND pm.meta_key = '$sql_sort_by'
          $additional_sql
          ORDER BY $meta_value"));
      }

    } else {


      $result = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts } AS p
        WHERE ID IN (" . implode(",", $matching_ids) . ")
        $additional_sql
        ORDER BY $sql_sort_by $sql_sort_order
        $limit_query");

      // Stores the total Properties returned
      if($total) {
        $total = count($wpdb->get_col("
          SELECT ID FROM {$wpdb->posts} AS p
          WHERE ID IN (" . implode(",", $matching_ids) . ")
          $additional_sql
          ORDER BY $sql_sort_by"));
      }
    }

    if( !empty( $result ) ) {
      $return = array();
      if(!empty($total)) {
        $return['total'] = $total;
        $return['results'] = $result;
      } else {
        $return = $result;
      }

      return $return;
    }

    return false;
  }

  /**
   * Prepares Request params for get_properties() function
   *
   * @param array $attrs
   * @return array $attrs
   */
  function prepare_search_attributes($attrs) {
    global $wp_properties;

    $prepared = array();

    $non_numeric_chars = apply_filters('wpp_non_numeric_chars', array('-', '$', ','));

    foreach($attrs as $search_key => $search_query) {

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

          //* There is no range if max value is empty and min value is -1 */
          if($search_query['min'] == '-1' && empty($search_query['max'])) {
            $search_query = '-1';
          } else {
          //* Set range */
            //** Ranges are always numeric, so we clear it up */
            foreach($search_query as $range_indicator => $value) {
              $search_query[$range_indicator] = str_replace($non_numeric_chars, '', $value);
            }

            if(empty($search_query['min']) && empty($search_query['max'])) {
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
      }

      if(is_string($search_query)) {
        if($search_query != '-1' && $search_query != '-') {
          $prepared[$search_key] = trim($search_query);
        }
      }

    }

    return $prepared;
  }

  /**
   * Returns array of all values for a particular attribute/meta_key
   */
  static function get_all_attribute_values($slug) {
    global $wpdb;

    // Non post_meta fields
    $non_post_meta = array(
      'post_title',
      'post_status',
      'post_author',
      'post_date'
    );

    if ( !in_array($slug, $non_post_meta) )
      $prefill_meta = $wpdb->get_col("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$slug'");
    else
      $prefill_meta = $wpdb->get_col("SELECT $slug FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status != 'auto-draft'");
    /**
     * @todo check if this condition is required - Anton Korotkov
     */
    /*if(empty($prefill_meta[0]))
      unset($prefill_meta);*/

    $prefill_meta = apply_filters('wpp_prefill_meta', $prefill_meta, $slug);

    if(count($prefill_meta) < 1)
      return false;

    $return = array();
    // Clean up values
    foreach($prefill_meta as $meta) {

      if(empty($meta))
        continue;

      $return[] = $meta;

    }

    if ( !empty( $return ) && !empty( $return ) ) {
      // Remove duplicates
      $return = array_unique($return);

      sort($return);

    }

    return $return;


  }
/**
   * Load property information into an array or an object
   *
    * @version 1.11 Added support for multiple meta values for a given key
    *
    * @since 1.11
   * @version 1.14 - fixed problem with drafts
   * @todo Code pertaining to displaying data should be migrated to prepare_property_for_display() like :$real_value = nl2br($real_value);
   * @todo Fix the long dashes - when in latitude or longitude it breaks it when using static map
   *
    */
  static function get_property($id, $args = false) {
    global $wp_properties, $wpdb;

    $id = trim($id);

    if($return = wp_cache_get($id.$args)) {
      return $return;
    }

     $defaults = array(
      'get_children' => 'true',
      'return_object' => 'false',
      'load_gallery' => 'true',
      'load_thumbnail' => 'true',
      'allow_multiple_values' => 'false',
      'load_parent' => 'true'
     );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $post = get_post($id, ARRAY_A);

    if($post['post_type'] != 'property') {
      return false;
    }

    //** Figure out what all the editable attributes are, and get their keys */
    $wp_properties['property_meta'] = (is_array($wp_properties['property_meta']) ? $wp_properties['property_meta'] : array());
    $wp_properties['property_stats'] = (is_array($wp_properties['property_stats']) ? $wp_properties['property_stats'] : array());
    $editable_keys = array_keys(array_merge($wp_properties['property_meta'], $wp_properties['property_stats']));

    $return = array();

    if ( $keys = get_post_custom( $id ) ) {
        foreach ( $keys as $key => $value ) {

          if($allow_multiple_values == 'false') {
            $value = $value[0];
          }

          $keyt = trim($key);

          //** If has _ prefix it's a built-in WP key */
          if ( '_' == $keyt{0} ) {
            continue;
          }

          // Fix for boolean values
          switch($value) {

            case 'true':
              $real_value = true; //** Converts all "true" to 1 */
            break;

            case 'false':
              $real_value = false;
            break;

            default:
              $real_value = $value;
            break;

          }
           // if a property_meta value, we do a nl2br since it will most likely have line breaks
          if(array_key_exists($key, $wp_properties['property_meta'])) {
            $real_value = nl2br($real_value);
          }

          // Handle keys with multiple values
          if(count($value) > 1) {
            $return[$key] = $value;
          } else {
            $return[$key] = $real_value;
          }

      }
     }


    $return = array_merge($return, $post);

    /*
     * Figure out what the thumbnail is, and load all sizes
     */
    if($load_thumbnail == 'true') {
      $wp_image_sizes = get_intermediate_image_sizes();

      $thumbnail_id = get_post_meta( $id, '_thumbnail_id', true );
      $attachments = get_children( array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image',  'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );

      if ($thumbnail_id) {
        foreach($wp_image_sizes as $image_name) {
          $this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name , true );
          $return['images'][$image_name] = $this_url[0];
        }

        $featured_image_id = $thumbnail_id;

      } elseif ($attachments) {
        foreach ( $attachments as $attachment_id => $attachment ) {

          foreach($wp_image_sizes as $image_name) {
            $this_url =  wp_get_attachment_image_src( $attachment_id, $image_name , true );
            $return['images'][$image_name] = $this_url[0];
          }

          $featured_image_id = $attachment_id;
          break;
        }
      }

      if($featured_image_id) {
        $return['featured_image'] = $featured_image_id;

        $image_title = $wpdb->get_var("SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '$featured_image_id' ");

        $return['featured_image_title'] = $image_title;
        $return['featured_image_url'] = wp_get_attachment_url($featured_image_id);
      }

    } /* end load_thumbnail */

    /*
     * Load all attached images and their sizes
     */
    if($load_gallery == 'true') {
      // Get gallery images
      if($attachments) {
        foreach ( $attachments as $attachment_id => $attachment ) {
          $return['gallery'][$attachment->post_name]['post_title'] = $attachment->post_title;
          $return['gallery'][$attachment->post_name]['post_excerpt'] = $attachment->post_excerpt;
          $return['gallery'][$attachment->post_name]['post_content'] = $attachment->post_content;
          $return['gallery'][$attachment->post_name]['attachment_id'] = $attachment_id;
          foreach($wp_image_sizes as $image_name) {
            $this_url =  wp_get_attachment_image_src( $attachment_id, $image_name , true );
            $return['gallery'][$attachment->post_name][$image_name] = $this_url[0];
          }
        }
      } else {
        $return['gallery'] = false;
      }
    }
    // end load_gallery

    /*
      Load parent if exists and inherit Parent's atttributes.
    */
    if($load_parent == 'true' && $post['post_parent']) {

      $return['is_child'] = true;

      $parent_object = WPP_F::get_property($post['post_parent'], "get_children=false");

      $return['parent_id'] = $post['post_parent'];
      $return['parent_link'] = $parent_object['permalink'];
      $return['parent_title'] = $parent_object['post_title'];

      // Inherit things
      if(is_array($wp_properties['property_inheritance'][$return['property_type']])) {
        foreach($wp_properties['property_inheritance'][$return['property_type']] as $inherit_attrib) {
          if(!empty($parent_object[$inherit_attrib]) && empty($return[$inherit_attrib])) {
            $return[$inherit_attrib] = $parent_object[$inherit_attrib];
          }
        }
      }
    }

    /*
      Load Children and their attributes
    */
    if($get_children == 'true') {

      // Calculate variables if based off children if children exist
      $children = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE  post_type = 'property' AND post_status = 'publish' AND post_parent = '{$id}' ORDER BY menu_order ASC ");

      //print_r($children);
      if(count($children) > 0) {

        //** Cycle through children and get necessary variables */
        foreach($children as $child_id) {

          $child_object = WPP_F::get_property($child_id, "load_parent=false");
          $return['children'][$child_id] = $child_object;

          //** Save child image URLs into one array for quick access */
          if(!empty($child_object['featured_image_url'])) {
            $return['system']['child_images'][$child_id] = $child_object['featured_image_url'];
          }

          // Exclude variables from searchable attributes (to prevent ranges)
          $excluded_attributes = $wp_properties['geo_type_attributes'];
          $excluded_attributes[] = $wp_properties['configuration']['address_attribute'];

          foreach($wp_properties['searchable_attributes'] as $searchable_attribute) {

            $attribute_data = WPP_F::get_attribute_data($searchable_attribute);

            if($attribute_data['numeric'] || $attribute_data['currency']) {

              if(!empty($child_object[$searchable_attribute]) && !in_array($searchable_attribute, $excluded_attributes)) {
                $range[$searchable_attribute][]  = $child_object[$searchable_attribute];
              }

            }
          }
        }

        // Cycle through every type of range (i.e. price, deposit, bathroom, etc) and fix-up the respective data arrays
        foreach((array)$range as $range_attribute => $range_values) {

          // Cycle through all values of this range (attribute), and fix any ranges that use dashes
          foreach($range_values as $key => $single_value) {

            // Remove dollar signs
            $single_value = str_replace("$" , '', $single_value);

            // Fix ranges
            if(strpos($single_value, '&ndash;')) {

              $split = explode('&ndash;', $single_value);

              foreach($split as $new_single_value)

                if(!empty($new_single_value)) {
                  array_push($range_values, trim($new_single_value));
                }

              // Unset original value with dash
              unset($range_values[$key]);

            }
          }

          // Remove duplicate values from this range
          $range[$range_attribute] =  array_unique($range_values);

          // Sort the values in this particular range
           sort($range[$range_attribute]);

           if(count($range[$range_attribute] ) < 2) {
            $return[$range_attribute] = $range[$range_attribute][0];
          }

          if(count($range[$range_attribute]) > 1) {
            $return[$range_attribute] = min($range[$range_attribute]) . " - " .  max($range[$range_attribute]);
          }

          //** If we end up with a range, we make a note of it */
          if(!empty($return[$range_attribute])) {
            $return['system']['upwards_inherited_attributes'][] = $range_attribute;
          }

        }

      }
    } /* end get_children */


    if(!empty($return['location']) && !in_array('address', $editable_keys) && !isset($return['address'])) {
      $return['address'] = $return['location'];
    }

    $return['wpp_gpid'] = WPP_F::maybe_set_gpid($id);

    $return['permalink'] = get_permalink($id);

    //** Make sure property_type stays as slug, or it will break many things:  (widgets, class names, etc)  */
    $return['property_type_label'] = $wp_properties['property_types'][$return['property_type']];

    if(empty($return['property_type_label'])) {
      foreach($wp_properties['property_types'] as $pt_key => $pt_value) {
        if(strtolower($pt_value) == strtolower($return['property_type'])) {
          $return['property_type'] = $pt_key;
          $return['property_type_label'] =  $pt_value;
        }
      }
    }

    if(empty($return['phone_number']) && !empty($wp_properties['configuration']['phone_number'])) {
      $return['phone_number'] = $wp_properties['configuration']['phone_number'];
    }

    if(is_array($return)) {
      ksort($return);
    }

    $return = apply_filters('wpp_get_property', $return);

    // Get rid of all empty values
    foreach($return as $key => $item) {

      // Don't keys starting w/ post_
      if(strpos($key, 'post_') === 0) {
        continue;
      }

      if(empty($item)) {
        unset($return[$key]);
      }
    }

    // Convert to object
    if($return_object == 'true') {
      $return = WPP_F::array_to_object($return);
    }

    wp_cache_add($id.$args, $return);

    return $return;


  }
  /**
  * Gets prefix to an attribute
  */
  static function get_attrib_prefix($attrib) {

    if($attrib == 'price')
      return "$";

    if($attrib == 'deposit')
      return "$";

  }

/*
 * Gets annex to an attribute. (Unused Function)
 *
 * @todo This function does not seem to be used by anything. potanin@UD (11/12/11)
 *
 */
  static function get_attrib_annex($attrib) {

    if($attrib == 'area') {
      return __(' sq ft.','wpp');
    }

  }


/*
  Get coordinates for property out of database
*/
  static function get_coordinates($listing_id = false) {
    global $post;

    if(!$listing_id)
      $listing_id = $post->ID;

    $latitude = get_post_meta($listing_id, 'latitude', true);
    $longitude = get_post_meta($listing_id, 'longitude', true);

    if(empty($latitude) || empty($longitude)) {

      // Try parent
      if($post->parent_id)  {
        $latitude = get_post_meta($post->parent_id, 'latitude', true);
        $longitude = get_post_meta($post->parent_id, 'longitude', true);

      }

      // Still nothing
      if(empty($latitude) || empty($longitude))
        return false;


    }

    return array('latitude' => $latitude, 'longitude' => $longitude);

  }


/*
  Validate if a URL is valid.
*/
  static function isURL($url) {
    return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
  }



  /**
   * Returns an array of a property's stats and their values.
   *
   * Query is array of variables to use load ours to avoid breaking property maps.
    *
    * @since 1.0
   *
    */
  static function get_stat_values_and_labels($property_object, $args = false) {
    global $wp_properties;

    $defaults = array();

    if(is_array($property_object)) {
      $property_object = (object) $property_object;
    }

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if($exclude) {
      $exclude = explode(',', $exclude);
    }

    if($include) {
      $include = explode(',', $include);
    }

    if(!$property_stats) {
      $property_stats = $wp_properties['property_stats'];
    }

    foreach($property_stats as $slug => $label) {

      // Determine if it's frontend and the attribute is hidden for frontend
      if(!is_admin() && in_array($slug, (array)$wp_properties['hidden_frontend_attributes'])) {
        continue;
      }

      // Exclude passed variables
      if(is_array($exclude) && in_array($slug, $exclude)) {
        continue;
      }

      if(!empty($property_object->{$slug})) {
        $value = $property_object->{$slug};
      } else {
        $value = get_post_meta($property_object->ID, $slug, true);
      }

      if ($value === true) {
        $value = 'true';
      }

      //** Override property_type slug with label */
      if($slug == 'property_type') {
        $value = $property_object->property_type_label;
      }

      // Include only passed variables
      if(is_array($include) && in_array($slug, $include)) {
        if(!empty($value)) {
          $return[$label] = $value;
        }
        continue;
      }

      if(!is_array($include)) {
        if(!empty($value)) {
          $return[$label] = $value;
        }
      }

    }

    if(count($return) > 0) {
      return $return;
    }

    return false;

  }



  static function array_to_object($array = array()) {
    if (!empty($array)) {
        $data = false;

        foreach ($array as $akey => $aval) {
            $data -> {$akey} = $aval;
        }

        return $data;
    }

    return false;
  }


  /**
   * Returns a minified Google Maps Infobox
   *
   * Used in property map and supermap
   *
   * @filter wpp_google_maps_infobox
   * @version 1.11 - added return if $post or address attribute are not set to prevent fatal error
   * @since 1.081
   *
   */
  static function google_maps_infobox($post, $args = false) {
    global $wp_properties;

    $map_image_type = $wp_properties['configuration']['single_property_view']['map_image_type'];
    $infobox_attributes = $wp_properties['configuration']['google_maps']['infobox_attributes'];
    $infobox_settings = $wp_properties['configuration']['google_maps']['infobox_settings'];

    if(empty($wp_properties['configuration']['address_attribute'])) {
      return;
    }

    if(empty($post)) {
      return;
    }

    if(is_array($post)) {
      $post = (object) $post;
    }

    $property = (array) prepare_property_for_display($post, array(
      'load_gallery' => 'false',
      'scope' => 'google_map_infobox'
    ));

    //** Check if we have children */
    if(count($property['children']) > 0 && $wp_properties['configuration']['google_maps']['infobox_settings']['do_not_show_child_properties'] != 'true') {
      foreach($property['children'] as $child_property) {
        $child_property = (array) $child_property;
        $html_child_properties[] = '<li class="infobox_child_property"><a href="' . $child_property['permalink'] . '">'. $child_property['post_title'] .'</a></li>';
      }
    }

    if(empty($infobox_attributes)) {
      $infobox_attributes = array(
        'price',
        'bedrooms',
        'bathrooms');
    }

    if(empty($infobox_settings)) {
      $infobox_settings = array(
        'show_direction_link' => true,
        'show_property_title' => true
      );
    }

    if(empty($infobox_settings['minimum_box_width'])) {
      $infobox_settings['minimum_box_width'] = '400';
    }

    foreach($infobox_attributes as $attribute) {
      $property_stats[$attribute] = $wp_properties['property_stats'][$attribute];
    }

    $property_stats = WPP_F::get_stat_values_and_labels($property, array(
      'property_stats' => $property_stats
    ));

    $image = wpp_get_image_link($property['featured_image'], $map_image_type, array('return'=>'array'));

    ob_start(); ?>

    <div id="infowindow" style="min-width:<?php echo $infobox_settings['minimum_box_width']; ?>px;">
    <?php if($infobox_settings['show_property_title']  == 'true') { ?>
      <div class="wpp_google_maps_attribute_row_property_title" >
      <a href="<?php echo get_permalink($property['ID']); ?>"><?php echo $property['post_title']; ?></a>
      </div>
    <?php }  ?>

    <table cellpadding="0" cellspacing="0" class="wpp_google_maps_infobox_table" style="">
      <tr>
        <?php if($image['link']) { ?>
        <td class="wpp_google_maps_left_col" style=" width: <?php echo $image['width']; ?>px">
          <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" src="<?php echo $image['link']; ?>" alt="<?php echo addslashes($post->post_title);?>" />
          <?php if($infobox_settings['show_direction_link'] == 'true'): ?>
          <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
            <a target="_blank" href="http://maps.google.com/maps?gl=us&daddr=<?php echo addslashes(str_replace(' ','+', $property[$wp_properties['configuration']['address_attribute']])); ?>"><?php _e('Get Directions','wpp') ?></a>
          </div>
          <?php endif; ?>
        </td>
        <?php } ?>

        <td class="wpp_google_maps_right_col" vertical-align="top" style="vertical-align: top;">
        <?php if(!$image['link'] && $infobox_settings['show_direction_link'] == 'true') { ?>
          <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
          <a target="_blank" href="http://maps.google.com/maps?gl=us&daddr=<?php echo addslashes(str_replace(' ','+', $property[$wp_properties['configuration']['address_attribute']])); ?>"><?php _e('Get Directions','wpp') ?></a>
          </div>
        <?Php }

          $attributes = array();

          $labels_to_keys = array_flip($wp_properties['property_stats']);

          if(is_array($property_stats)) {
            foreach($property_stats as $attribute_label => $value) {

              $attribute_slug = $labels_to_keys[$attribute_label];
              $attribute_data = WPP_F::get_attribute_data($attribute_slug);

              if(empty($value)) {
                continue;
              }

              if($value == 'true' || (!$attribute_data['numeric'] && $value == 1)) {
                if($wp_properties['configuration']['google_maps']['show_true_as_image'] == 'true') {
                  $value = '<div class="true-checkbox-image"></div>';
                } else {
                  $value = __('Yes', 'wpp');
                }
              } elseif ($value == 'false') {
                continue;
              }

              $attributes[] =  '<li class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_' . $attribute_slug . '">';
              $attributes[] =  '<span class="attribute">' . $attribute_label . '</span>';
              $attributes[] =  '<span class="value">' . $value . '</span>';
              $attributes[] =  '</li>';
            }
          }

          if(count($attributes) > 0) {
            echo '<ul class="wpp_google_maps_infobox">' . implode('', $attributes) . '<li class="wpp_google_maps_attribute_row wpp_fillter_element">&nbsp;</li></ul>';
          }

          if(!empty($html_child_properties)) {
            echo '<ul class="infobox_child_property_list">' . implode('', $html_child_properties) . '<li class="infobox_child_property wpp_fillter_element">&nbsp;</li></ul>';
          }

          ?>

          </td>
      </tr>
    </table>

    </div>


    <?php
    $data = ob_get_contents();
    $data = preg_replace(array('/[\r\n]+/'), array(""), $data);
    $data = addslashes($data);

    ob_end_clean();

    $data = apply_filters('wpp_google_maps_infobox', $data, $post);

    return $data;
  }

  /**
   * Returns property object for displaying on map
   *
   * Used for speeding up property queries, only returns:
   * ID, post_title, atitude, longitude, exclude_from_supermap, location, supermap display_attributes and featured image urls
   *
   * 1.11: addded htmlspecialchars and addslashes to post_title
   * @since 1.11
   *
   */
  static function get_property_map($id, $args = '') {
    global $wp_properties, $wpdb;

    $defaults = array(
      'thumb_type' => (!empty($wp_properties['feature_settings']['supermap']['supermap_thumb']) ? $wp_properties['feature_settings']['supermap']['supermap_thumb'] : 'thumbnail'),
      'return_object' => 'false',
      'map_image_type' => $wp_properties['configuration']['single_property_view']['map_image_type']
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if(class_exists('class_wpp_supermap'))
      $display_attributes = $wp_properties['configuration']['feature_settings']['supermap']['display_attributes'];

     $return['ID'] = $id;

     $data = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = $id GROUP BY meta_key");

     foreach($data as $row) {
      $return[$row->meta_key] = $row->meta_value;
     }

     $return['post_title'] = htmlspecialchars(addslashes($wpdb->get_var("SELECT post_title FROM {$wpdb->posts} WHERE ID = $id")));

     // Get Images
      $wp_image_sizes = get_intermediate_image_sizes();

      $thumbnail_id = get_post_meta( $id, '_thumbnail_id', true );
      $attachments = get_children( array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image',  'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );

      if ($thumbnail_id) {
        foreach($wp_image_sizes as $image_name) {
          $this_url = wp_get_attachment_image_src( $thumbnail_id, $image_name , true );
          $return['images'][$image_name] = $this_url[0];
          }

        $featured_image_id = $thumbnail_id;

      } elseif ($attachments) {
        foreach ( $attachments as $attachment_id => $attachment ) {

          foreach($wp_image_sizes as $image_name) {
            $this_url =  wp_get_attachment_image_src( $attachment_id, $image_name , true );
            $return['images'][$image_name] = $this_url[0];
          }

          $featured_image_id = $attachment_id;
          break;
        }
      }


      if($featured_image_id) {
        $return['featured_image'] = $featured_image_id;

        $image_title = $wpdb->get_var("SELECT post_title  FROM {$wpdb->prefix}posts WHERE ID = '$featured_image_id' ");

        $return['featured_image_title'] = $image_title;
        $return['featured_image_url'] = wp_get_attachment_url($featured_image_id);

      }

    return $return;

  }


  /**
   * Generates Global Property ID for standard reference point during imports.
   *
   * Property ID is currently not used.
   *
   * @return integer. Global ID number
   * @param integer $property_id. Property ID.
   * @todo API call to UD server to verify there is no duplicates
   * @since 1.6
   */
  static function get_gpid($property_id = false, $check_existance = false) {

    if($check_existance && $property_id) {
      $exists = get_post_meta($property_id, 'wpp_gpid', true);

      if($exists) {
        return $exists;
      }
    }
    return 'gpid_' . rand(1000000000,9999999999);

  }


  /**
   * Generates Global Property ID if it does not exist
   *
   * @return string | Returns GPID
   * @since 1.6
   */
  static function maybe_set_gpid($property_id = false) {

    if(!$property_id) {
      return false;
    }

    $exists = get_post_meta($property_id, 'wpp_gpid', true);

    if($exists) {
      return $exists;
    }


    $gpid = WPP_F::get_gpid($property_id, true);

    update_post_meta($property_id, 'wpp_gpid', $gpid);

    return $gpid;

    return false;

  }


  /**
   * Returns post_id fro GPID if it exists
   *
   * @since 1.6
   */
  static function get_property_from_gpid($gpid = false) {
    global $wpdb;

    if(!$gpid) {
      return false;
    }

    $post_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id  WHERE meta_key = 'wpp_gpid' AND meta_value = '{$gpid}' ");

    if(is_numeric($post_id)) {
      return $post_id;
    }

    return false;

  }





  /**
   * This static function is not actually used, it's only use to hold some common translations that may be used by our themes.
   *
   * Translations for Denali theme.
   *
   * @since 1.14
   *
   */
  static function strings_for_translations() {

    __('General Settings', 'wpp');
    __('Find your property', 'wpp');
    __('Edit', 'wpp');
    __('City', 'wpp');
    __('Contact us', 'wpp');
    __('Login', 'wpp');
    __('Explore', 'wpp');
    __('Message', 'wpp');
    __('Phone Number', 'wpp');
    __('Name', 'wpp');
    __('E-mail', 'wpp');
    __('Send Message', 'wpp');
    __('Submit Inquiry', 'wpp');
    __('Inquiry', 'wpp');
    __('Comment About', 'wpp');
    __('Inquire About', 'wpp');
    __('Inquiry About:', 'wpp');
    __('Inquiry message:', 'wpp');
    __('You forgot to enter your e-mail.', 'wpp');
    __('You forgot to enter a message.', 'wpp');
    __('You forgot to enter your  name.', 'wpp');
    __('Error with sending message. Please contact site administrator.', 'wpp');
    __('Thank you for your message.', 'wpp');
  }

  /**
   * Determine if all values of meta key have 'number type'
   * If yes, returns boolean true
   *
   * @param mixed $property_ids
   * @param string $meta_key
   * @return boolean
   * @since 1.16.2
   * @author Maxim Peshkov
   */
  function meta_has_number_data_type ($property_ids, $meta_key) {
    global $wpdb;

    /* There is no sense to continue if no ids */
    if(empty($property_ids)) {
      return false;
    }

    if(is_array($property_ids)) {
      $property_ids = implode(",", $property_ids);
    }

    $values = $wpdb->get_col("
      SELECT pm.meta_value
      FROM {$wpdb->prefix}posts AS p
      JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = p.ID
        WHERE p.ID IN (" . $property_ids . ")
          AND p.post_status = 'publish'
          AND pm.meta_key = '$meta_key'
    ");

    foreach($values as $value) {
      $value = trim($value);

      if(empty($value)) {
        continue;
      }

      preg_match('#^[\d,\.\,]+$#', $value, $matches );
      if(empty($matches)) {
        return false;
      }
    }

    return true;
  }

  /*
   * Determine if permalink has post_name
   *
   * @todo What is the poitn of this function? It seems to break things when permalinks are not enabled - is that intended? - potanin_UD
   * @param object $post
   * @return boolean
   */
  function is_permalink_has_post_name($post){
    $permalink = get_permalink($post->ID);
    preg_match('/'.$post->post_name.'/', $permalink, $m);
    if(empty($m)) {
      return false;
    }
    return true;
  }

  /**
   * Function for displaying WPP Data Table rows
   *
   * Ported from WP-CRM
   *
   * @since 3.0
   *
   */
  function list_table() {
    global $current_screen;

    include WPP_Path . '/core/ui/class_wpp_object_list_table.php';

    //** Get the paramters we care about */
    $sEcho = $_REQUEST['sEcho'];
    $per_page = $_REQUEST['iDisplayLength'];
    $iDisplayStart = $_REQUEST['iDisplayStart'];
    $iColumns = $_REQUEST['iColumns'];
    $sColumns = $_REQUEST['sColumns'];
    $order_by = $_REQUEST['iSortCol_0'];
    $sort_dir = $_REQUEST['sSortDir_0'];
    //$current_screen = $wpi_settings['pages']['main'];

    //** Parse the serialized filters array */
    parse_str($_REQUEST['wpp_filter_vars'], $wpp_filter_vars);
    $wpp_search = $wpp_filter_vars['wpp_search'];

    $sColumns = explode("," , $sColumns);

    //* Init table object */
    $wp_list_table = new WPP_Object_List_Table(array(
      "ajax" => true,
      "per_page" => $per_page,
      "iDisplayStart" => $iDisplayStart,
      "iColumns" => $iColumns,
      "current_screen" => 'property_page_all_properties'
    ));

    if ( in_array( $sColumns[$order_by], $wp_list_table->get_sortable_columns() ) ) {
      $wpp_search['sorting'] = array(
        'order_by' => $sColumns[$order_by],
        'sort_dir' => $sort_dir
      );
    }

    $wp_list_table->prepare_items($wpp_search);

    //print_r( $wp_list_table ); die();

    if ( $wp_list_table->has_items() ) {
      foreach ( $wp_list_table->items as $count => $item ) {
        $data[] = $wp_list_table->single_row( $item );
      }
    } else {
      $data[] = $wp_list_table->no_items();
    }

    //print_r( $data );

    return json_encode(array(
      'sEcho' => $sEcho,
      'iTotalRecords' => count($wp_list_table->all_items),
      // @TODO: Why iTotalDisplayRecords has $wp_list_table->all_items value ? Maxim Peshkov
      'iTotalDisplayRecords' =>count($wp_list_table->all_items),
      'aaData' => $data
    ));
  }

  /*
   * Get Search filter fields
   */
  function get_search_filters() {
    global $wp_properties, $wpdb;

    $filters = array();
    $filter_fields = array(
        'property_type' => array(
            'type'  => 'multi_checkbox',
            'label' => __('Type', 'wpp')
        ),
        'featured'      => array(
            'type'    => 'multi_checkbox',
            'label'   => __('Featured', 'wpp')
        ),
        'post_status'   => array(
            'default' => 'publish',
            'type'    => 'radio',
            'label'   => __('Status', 'wpp')
        ),
        'post_author'   => array(
            'default' => '0',
            'type'    => 'dropdown',
            'label'   => __('Author', 'wpp')
        ),
        'post_date'     => array(
            'default' => '',
            'type'    => 'dropdown',
            'label'   => __('Date', 'wpp')
        )

    );

    foreach( $filter_fields as $slug => $field ) {

      $f = array();

      switch ( $field['type'] ) {

        default: break;

        case 'input': break;

        case 'multi_checkbox':
          $attr_values = self::get_all_attribute_values( $slug );

          break;

        case 'range_dropdown':
          $attr_values = self::get_all_attribute_values( $slug );

          break;

        case 'dropdown':
          $attr_values = self::get_all_attribute_values( $slug );

          break;

        case 'radio':
          $attr_values = self::get_all_attribute_values( $slug );

          break;

      }

      $f  = $field;

      switch ( $slug ) {

        default: break;

        case 'property_type':

          if ( !empty( $wp_properties['property_types'] ) ) {
            $attrs = array();
            if(is_array($attr_values)) {
              foreach( $attr_values as $attr ) {
                if ( !empty( $wp_properties['property_types'][ $attr ] ) ) {
                  $attrs[ $attr ] = $wp_properties['property_types'][ $attr ];
                }
              }
            }
          }
          $attr_values = $attrs;

          break;

        case 'featured':

          $attrs = array();
          if(is_array($attr_values)) {
            foreach( $attr_values as $attr ) {
              $attrs[$attr] = $attr == 'true' ? 'Yes' : 'No';
            }
          }
          $attr_values = $attrs;

          break;

        case 'post_status':
          $all = 0;
          $attrs = array();
          if(is_array($attr_values)) {
            foreach ($attr_values as $attr) {
              $count = self::get_properties_quantity( array( $attr ) );
              $attrs[$attr] = strtoupper( substr($attr, 0, 1) ).substr($attr, 1, strlen($attr)).' ('. WPP_F::format_numeric($count).')';
              $all += $count;
            }
          }

          $attrs['all'] = __('All', 'wpp').' ('.WPP_F::format_numeric($all).')';
          $attr_values = $attrs;

          ksort($attr_values);

          break;

        case 'post_author':

          $attr_values    = self::get_users_of_post_type('property');
          $attr_values[0] = __('Any', 'wpp');

          ksort($attr_values);

          break;

        case 'post_date':

          $attr_values = array();
          $attr_values[''] = __('Show all dates', 'wpp');

          $attrs     = self::get_property_month_periods();

          foreach( $attrs as $value => $attr ) {
            $attr_values[$value] = $attr;
          }

          break;

      }

      if ( !empty( $attr_values ) ) {

        $f['values'] = $attr_values;
        $filters[ $slug ] = $f;

      }

    }

    $filters = apply_filters( "wpp_get_search_filters", $filters );

    return $filters;
  }

  /**
   * Returns users' ids of post type
   * @global object $wpdb
   * @param string $post_type
   * @return array
   */
  function get_users_of_post_type($post_type) {
    global $wpdb;

    switch ($post_type) {

      case 'property':
        $results = $wpdb->get_results($wpdb->prepare("
          SELECT DISTINCT u.ID, u.display_name
          FROM {$wpdb->posts} AS p
          JOIN {$wpdb->users} AS u ON u.ID = p.post_author
          WHERE p.post_type = '%s'
            AND p.post_status != 'auto-draft'
          ", $post_type), ARRAY_N);
        break;

      default: break;
    }

    if (empty($results)) {
      return false;
    }

    $users = array();
    foreach ($results as $result) {
      $users[$result[0]] = $result[1];
    }

    $users = apply_filters('wpp_get_users_of_post_type', $users, $post_type);

    return $users;
  }

  /**
   * Process bulk actions
   */
  function property_page_all_properties_load() {

    if ( !empty( $_REQUEST['action'] ) && !empty( $_REQUEST['post'] ) ) {

      switch ( $_REQUEST['action'] ) {

        default: break;

        case 'trash':
          foreach( $_REQUEST['post'] as $post_id ) {
            $post_id = (int)$post_id;
            wp_trash_post($post_id);
          }
          break;

        case 'untrash':
          foreach( $_REQUEST['post'] as $post_id ) {
            $post_id = (int)$post_id;
            wp_untrash_post($post_id);
          }
          break;

        case 'delete':
          foreach( $_REQUEST['post'] as $post_id ) {
            $post_id = (int)$post_id;
            if ( get_post_status($post_id) == 'trash' ) {
              wp_delete_post($post_id);
            }
          }
          break;

      }

    }

  }

  /**
   * Counts properties by post types
   * @global object $wpdb
   * @param array $post_status
   * @return int
   */
  function get_properties_quantity( $post_status = array('publish') ) {
    global $wpdb;

    $results = $wpdb->get_col("
      SELECT ID
      FROM {$wpdb->posts}
      WHERE post_status IN ('". implode( "','", $post_status ) ."')
        AND post_type = 'property'
    ");

    $results = apply_filters('wpp_get_properties_quantity', $results, $post_status);

    return count( $results );

  }

  /**
   * Returns month periods of properties
   * @global object $wpdb
   * @global object $wp_locale
   * @return array
   */
  function get_property_month_periods() {
    global $wpdb, $wp_locale;

    $months = $wpdb->get_results("
      SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
      FROM $wpdb->posts
      WHERE post_type = 'property'
        AND post_status != 'auto-draft'
      ORDER BY post_date DESC
    ");

    $months = apply_filters('wpp_get_property_month_periods', $months);

    $results = array();

    foreach( $months as $date ) {

      $month = zeroise( $date->month, 2 );
      $year = $date->year;

      $results[ $date->year . $month ] = $wp_locale->get_month( $month ) . " $year";

    }

    return $results;

  }

  /**
   * Deletes directory recursively
   *
   * @param string $dirname
   * @return bool
   * @author korotkov@ud
   */
  function delete_directory( $dirname ) {

    if ( is_dir( $dirname ) )
      $dir_handle = opendir($dirname);

    if ( !$dir_handle )
      return false;

    while( $file = readdir( $dir_handle ) ) {
      if ( $file != "." && $file != ".." ) {

        if ( !is_dir( $dirname."/".$file ) )
          unlink( $dirname."/".$file );
        else
          delete_directory( $dirname.'/'.$file );

      }
    }

    closedir( $dir_handle );
    return rmdir( $dirname );

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

if(!class_exists('XmlToArray')){
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
}



/**
 * Implementing this for old versions of PHP
 *
 * @since 1.15.9
 *
 */
if(!function_exists('array_fill_keys')){

  function array_fill_keys($target, $value = '') {

    if(is_array($target)) {

      foreach($target as $key => $val) {

        $filledArray[$val] = is_array($value) ? $value[$key] : $value;

      }

    }

    return $filledArray;

  }

}
