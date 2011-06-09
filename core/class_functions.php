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
   static function revalidate_all_addresses($echo_result = true) {
    global $wp_properties, $wpdb;

    $all_properties = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property' AND post_status = 'publish'");

    $google_map_localizations = WPP_F::draw_localization_dropdown('return_array=true');

     foreach($all_properties as $post_id) {

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

        $updated[] = $post_id;

      } else {
          // Try to figure out what went wrong

          if($geo_data->status != 'OK') {

            // Break if daily geo-lookup limit is exceeded
            if($geo_data->status == 'OVER_QUERY_LIMIT') {
             $return['message'] = __("Address revalidation failed because the Google daily address look-up limit has been exceeded.", 'wpp');
            } else {
             $return['message'] = __("An error occured that prevented geo-location from working.", 'wpp');
            }

          $return['success'] = 'false';

          if($echo_result)
            echo json_encode($return);
          else
            return $return;

          return;
      }

      update_post_meta($post_id, 'address_is_formatted', false);
      }

    }

    $return['success'] = 'true';
    $return['message'] = "Updated " . count($updated) . " properties using the " . $google_map_localizations[$wp_properties['configuration']['google_maps_localization']] .  " localization.";

    if($echo_result)
      echo json_encode($return);
    else
      return $echo_result;

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

    if(!class_exists('W3_Plugin'))
      include_once WPP_Path. '/third-party/jsmin.php';
    elseif(file_exists(WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php'))
      include_once WP_PLUGIN_DIR . '/w3-total-cache/lib/Minify/JSMin.php';
    else
      include_once WPP_Path. '/third-party/jsmin.php';

    if(class_exists('JSMin'))
      $data = JSMin::minify($data);

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

    if(!$type)
      return;

    $dimensions = $wp_properties[image_sizes][$type];

    $return[0] = $dimensions[width];
    $return[1] = $dimensions[height];
    $return['width'] = $dimensions[width];
    $return['height'] = $dimensions[height];

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

    if(empty($current))
      update_user_meta($user_id, 'manageedit-propertycolumnshidden', $default_hidden);


  }


  /**
   * Determines most common property type (used for defaults when needed)
   *
    *
    * @since 0.55
   *
    */
  static function get_most_common_property_type($array = false) {
    global $wpdb;

    $top_property_type = $wpdb->get_row("
    SELECT meta_value as property_type, count(meta_value) as count
    FROM {$wpdb->prefix}postmeta WHERE meta_key = 'property_type'
    GROUP BY meta_value
    ORDER BY count DESC
    LIMIT 0,1");


    return $top_property_type->property_type;
  }


  /**
   * Splits a query string properly, using preg_split to avoid conflicts with dashes and other special chars.
   * @param string $query string to split
   * @return Array
   */
  static function split_query_string($query)
  {
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

    if($meta_key == 'latitude' || $meta_key == 'longitude')
      return $input;


    /* If PHP version is newer than 4.3.0, else apply fix. */
    if ( strnatcmp(phpversion(),'4.3.0' ) >= 0 ) {

      $result = str_replace( html_entity_decode('-', ENT_COMPAT, 'UTF-8'), '&ndash;', $input );

    }
    else {

      $result = str_replace( utf8_encode( html_entity_decode('-') ), '&ndash;', $input );

    }

    /* Uses WPs built in esc_html, works like a charm. */
    $result = esc_html( $result );

    return $result;

  }


  /** NOT IN USE
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
    if(!is_array($array))
      return;

    foreach($array as $value) {
      if(!is_numeric($value))
        return false;
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
      'cs' => 'Czech',
      'de' => 'German',
      'el' => 'Greek',
      'es' => 'Spanish',
      'fr' => 'French',
      'it' => 'Italian',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'nl' => 'Dutch',
      'no' => 'Norwegian',
      'pt' => 'Portuguese',
      'pt-BR' => 'Portuguese (Brazil)',
      'pt-PT' => 'Portuguese (Portugal)',
      'ru' => 'Russian',
      'sv' => 'Swedish',
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
    $blogname = get_bloginfo('url');
    $blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
    $system = 'wpp';
    $wpp_version = get_option( "wpp_version" );

    $check_url = "http://updates.twincitiestech.com/?system=$system&site=$blogname&system_version=$wpp_version";
    $response = @wp_remote_get($check_url);

     if(!$response)
      return;


    // Check for errors
    if(is_object($response) && !empty($response->errors)) {

      foreach($response->errors as $update_errrors) {
        $error_string .= implode(",", $update_errrors);
        UD_F::log("Feature Update Error: " . $error_string);
      }

      if($return)
        return sprintf(__('An error occured during premium feature check: <b> %s </b>.','wpp'), $error_string);

      return;
    }

    // Quit if failture
    if($response[response][code] != '200')
      return;


     $response = @json_decode($response[body]);


    if(is_object($response->available_features)):

      $response->available_features = UD_F::objectToArray($response->available_features);


      // Updata database
      $wpp_settings = get_option('wpp_settings');
      $wpp_settings[available_features] =  UD_F::objectToArray($response->available_features);
       update_option('wpp_settings', $wpp_settings);


    endif;// available_features


    if($response->features == 'eligible') {

      // Try to create directory if it doesn't exist
      if(!is_dir(WPP_Premium)) {
        @mkdir(WPP_Premium, 0755);
      }

      // If didn't work, we quit
      if(!is_dir(WPP_Premium))
        continue;



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

          if(@version_compare($current_file[Version], $version) == '-1') {
            $this_file = WPP_Premium . "/" . $filename;
            $fh = @fopen($this_file, 'w');
            if($fh) {
              fwrite($fh, $php_code);
              fclose($fh);


              if($current_file[Version])
                UD_F::log(sprintf(__('WP-Property Premium Feature: %s updated to version %s from %s.','wpp'), $code->name, $version, $current_file[Version]));
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

    if($return)
      return __('Update ran successfully.','wpp');
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
   * Displays dropdown of available property size images
   *
    *
    * @since 0.54
   *
    */
  static function image_sizes_dropdown($args = "") {
    global $wp_properties;

    $defaults = array('name' => 'wpp_image_sizes',  'selected' => 'none');
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if(empty($id) && !empty($name)) {
      $id = $name;
    }


    $image_array = get_intermediate_image_sizes();


    ?>
      <select id="<?php echo $id ?>" name="<?php echo $name ?>" >
        <option> - </option>
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

  static function image_sizes($type = false, $args = "") {
    global $_wp_additional_image_sizes;

    $defaults = array('return_all' => false);
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


    if(!$type)
      return false;

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
   * Loads property values into global $post variables
   *
   * Attached to do_action_ref_array('the_post', array(&$post)); in setup_postdata()
   *
   * @todo There may be a better place to load property variables
   * @since 0.54
   *
    */
  static function the_post($post) {
    global $post;

    if($post->post_type == 'property') {
      $post = WPP_F::get_property($post->ID, "return_object=true");
    }

   }


  /**
   * Check for premium features and load them
   *
   * @since 0.624
   *
    */
  static function load_premium() {
    global $wp_properties;

    $default_headers = array(
      'Name' => __('Name','wpp'),
      'Version' => __('Version','wpp'),
      'Description' => __('Description','wpp')
    );


    if(!is_dir(WPP_Premium))
      return;

    if ($premium_dir = opendir(WPP_Premium)) {
      if(file_exists(WPP_Premium . "/index.php"))
        @include_once(WPP_Premium . "/index.php");

      while (false !== ($file = readdir($premium_dir))) {

        if($file == 'index.php')
          continue;

        if(end(@explode(".", $file)) == 'php') {

          $plugin_slug = str_replace(array('.php'), '', $file);



          $plugin_data = @get_file_data( WPP_Premium . "/" . $file, $default_headers, 'plugin' );
          $wp_properties['installed_features'][$plugin_slug]['name'] = $plugin_data['Name'];
          $wp_properties['installed_features'][$plugin_slug]['version'] = $plugin_data['Version'];
          $wp_properties['installed_features'][$plugin_slug]['description'] = $plugin_data['Description'];

          // Check if the plugin is disabled
          if($wp_properties['installed_features'][$plugin_slug]['disabled'] != 'true') {
            @include_once(WPP_Premium . "/" . $file);

             // Disable plugin if class does not exists - file is empty
            if(!class_exists($plugin_slug))
              unset($wp_properties['installed_features'][$plugin_slug]);

            $wp_properties['installed_features'][$plugin_slug]['disabled'] = 'false';
          }

        }
      }
    }

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
     * @return array|$wp_properties
     * @since 0.57
     *
     */
    static function get_search_values($search_attributes, $searchable_property_types, $cache = true, $instance_id = false) {
        global $wpdb, $wp_properties;

        if($instance_id) {
            $cachefile = WPP_Path . '/cache/searchwidget/' . $instance_id . '.values.res';
            if($cache && is_file($cachefile) && time() - filemtime($cachefile) < 3600) {
                $result = unserialize(file_get_contents($cachefile));
            }
        }
        if(!$result) {
            $query_attributes = "";
            $query_types = "";
            if(is_array($search_attributes))
                $query_attributes = "'" . implode('\',\'',$search_attributes) . "'";
            if(is_array($searchable_property_types))
               $query_types = "'" . implode('\',\'',$searchable_property_types) . "'";

            $matching_ids = $wpdb->get_col("SELECT post_id
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = 'property_type' AND meta_value IN ({$query_types})");

            if(empty($matching_ids))
                return false;

            $matching_ids = "'" . implode('\',\'',$matching_ids) . "'";
            $results = $wpdb->get_results("SELECT post_id, meta_key, meta_value
                FROM {$wpdb->prefix}postmeta
                WHERE post_id IN ({$matching_ids}) AND meta_key IN ({$query_attributes})", ARRAY_A);

            if(empty($results))
                return false;

            $searchable_properties = array();
            foreach ($results as $value) {
                $searchable_properties[$value['post_id']][$value['meta_key']] = $value['meta_value'];
            }

            //$searchable_properties = WPP_F::get_searchable_properties();
            // Return fail if no searchable properties found
            //if(!$searchable_properties)
            //  return false;

            // Cycle through all searchable properties all searchable data into one array
            foreach($searchable_properties as $property) {
                //$property = WPP_F::get_property($property_id, "get_children=false&load_gallery=false");
                foreach($wp_properties['searchable_attributes'] as $searchable_attribute) {
                    // Clean up values if a conversion exists
                    $search_value = WPP_F::do_search_conversion($searchable_attribute, trim($property[$searchable_attribute]));
                    // Remove dollay signs
                    $search_value = str_replace(array(",", "$"), '', $search_value);

                    // Fix value with special chars
                    $search_value = htmlspecialchars($search_value, ENT_QUOTES);

                    // @TODO: Does it need? Not sure that it is used
                    /*
                    // Fix ranges
                    if(strpos($search_value, '-')) {
                        $split = explode('-', $search_value);
                        foreach($split as $new_search_value)
                        if(!empty($new_search_value))
                            $range[$searchable_attribute][] = trim($new_search_value);
                        continue;
                    }
                    */

                    if(empty($search_value))
                        continue;

                    $range[$searchable_attribute][] = $search_value;
                    $range[$searchable_attribute] = array_unique($range[$searchable_attribute]);
                    sort($range[$searchable_attribute], SORT_REGULAR);
                }
            }
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

    /*
        check if a search converstion exists for a attributes value
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
   *
   * @since 1.08
   *
  */
  static function get_properties($args = "") {
    global $wpdb;
    
    $defaults = array('property_type' => 'all');
    
    /* I haven't seen this doing anything yet, but leaving it to avoid unseen errors.
        if( is_array($maybe_array = unserialize($args)) ) {
            $query = $maybe_array;
        } else {
            $query = wp_parse_args( $args, $defaults );
        }
    */
    
    $query = wp_parse_args( $args, $defaults );
    
    $query = apply_filters('wpp_get_properties_query', $query);
    
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
      $sql_sort_order = ($query['sort_order'])?strtoupper($query['sort_order']):'ASC';
    } else {
      $sql_sort_by = 'post_date';
      $sql_sort_order = 'ASC';
    }
    
    unset( $query['sort_by'] );
    unset( $query['sort_order'] );
    
    // Go down the array list narrowing down matching properties
    foreach ($query as $meta_key => $criteria) {
      
      $criteria = WPP_F::encode_mysql_input( $criteria, $meta_key);
      
      // Stop filtering (loop) because no IDs left
      if (isset($matching_ids) && empty($matching_ids)) {
        break;
      }
      
      /*
      // Allowed property_type array to $comma_and array
      if (is_array($criteria) && $meta_key =='property_type') {
        $comma_and = $criteria;
      }
      */
      
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
      
      if (!$limit_query) $limit_query = '';
      
      switch ($meta_key) {
        
        case 'property_type':
          
          // Get all property types
          if ($specific == 'all') {
            if (isset($matching_ids)) {
              $matching_id_filter = implode("' OR post_id ='", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (post_id ='$matching_id_filter') AND (meta_key = 'property_type')");
            } else {
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = 'property_type')");
            }
            break;
          }
          
          if ( !is_array($criteria) ) {
            $criteria = array($criteria);
          }
          
          if ( $comma_and ) {
            $where_string = implode("' OR meta_value ='", $comma_and);
          }
          
          else {
            $where_string = $specific;
          }
          
          // See if mathinc_ids have already been filtered down
          if ( isset($matching_ids) ) {
            $matching_id_filter = implode("' OR post_id ='", $matching_ids);
            $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (post_id ='$matching_id_filter') AND (meta_key = 'property_type' AND (meta_value ='$where_string'))");
          } else {
            $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = 'property_type' AND (meta_value ='$where_string'))");
            //$wpdb->print_error("Matching not set");
          }
          break;
          
        default:
          
          if (WPP_F::is_numeric_range($criteria)) {
            
            //UD_F::log("Filtering $meta_key which is numeric");
            
            // See if $matching_ids has already been filtered down
            if (isset($matching_ids)) {
              $matching_id_filter = implode("' OR post_id ='", $matching_ids);
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '$meta_key' AND (post_id ='$matching_id_filter') AND (meta_value BETWEEN  $min AND $max) $limit_query");
              //$wpdb->print_error();
            } else {
              $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '$meta_key' AND (meta_value BETWEEN $min AND $max)");
              //$wpdb->print_error();
            }
            // UD_F::log($wpdb->last_query. " " . print_r($matching_ids, true));
            
          } else {
            
            // UD_F::log("Filtering $meta_key which is not numeric");
            // Get all properties for that meta_key
            if ($specific == 'all' && !$comma_and && !$hyphen_between) {
              
              if (isset($matching_ids)) {
                $matching_id_filter = implode("' OR post_id ='", $matching_ids);
                $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (post_id ='$matching_id_filter') AND (meta_key = '$meta_key') AND meta_value != '' $limit_query");
                //$wpdb->print_error();
              } else {
                $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE (meta_key = '$meta_key') AND meta_value != ''");
              }
              break;
              
            } else {
              
              if ( $comma_and ) {
                $where_and = "meta_key = '$meta_key' AND (meta_value ='" . implode("' OR meta_value ='", $comma_and)."')";
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
                    $where_between = "`meta_key` = '$meta_key' AND STR_TO_DATE(`meta_value`, '%c/%e/%Y') BETWEEN " . implode(" AND ", $hyphen_between)."";
                  } else {
                    $where_between = "`meta_key` = '$meta_key' AND `meta_value` BETWEEN " . implode(" AND ", $hyphen_between)."";
                  }
                } else {
                  if($adate) {
                    $where_between = "`meta_key` = '$meta_key' AND STR_TO_DATE(`meta_value`, '%c/%e/%Y') >= STR_TO_DATE('{$hyphen_between[0]}', '%c/%e/%Y')";
                  } else {
                    $where_between = "`meta_key` = '$meta_key' AND `meta_value` >= $hyphen_between[0]";
                  }
                }
                $specific = $where_between;
              }
              
              if ($specific == 'true') {
                // If properties data were imported, meta value can be '1' instead of 'true'
                // So we're trying to find also '1'
                $specific = "meta_value IN ('true', '1')";
              } elseif(!substr_count($specific, 'meta_value')) {
                //$specific = "meta_value LIKE '%".(str_replace(' ', '%', $specific))."%'";
                $specific = "meta_value = '". $wpdb->escape($specific) ."'";
              }
              
              if (isset($matching_ids)) {
                $matching_id_filter = implode(",", $matching_ids);
                $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE post_id IN ($matching_id_filter) AND meta_key = '$meta_key' AND $specific");
                //$wpdb->print_error();
              } else {
                $matching_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE $specific $sql_order");
                //$wpdb->print_error();
              }
              
            }
            
          }
          break;
        
      } // END switch
      
      unset( $comma_and );
      unset( $hyphen_between );
      //unset($specific);
      
    } // END foreach
    
    // Return false, if there are any result using filter conditions
    if (empty($matching_ids)) {
      return false;
    }
    
    // Remove duplicates
    $matching_ids = array_unique( $matching_ids );
    
    // Stores the total Properties returned
    $total = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE ID IN ('" . implode("','", $matching_ids) . "') AND post_status = 'publish'");
    
    // Sorts the returned Properties by the selected sort order
    if ($sql_sort_by &&
        $sql_sort_by != 'menu_order' &&
        $sql_sort_by != 'post_date' &&
        $sql_sort_by != 'post_title' ) 
    {
      $result = $wpdb->get_col("
        SELECT p.ID FROM {$wpdb->prefix}posts AS p, {$wpdb->prefix}postmeta AS pm
          WHERE p.ID IN (" . implode(",", $matching_ids) . ")
            AND p.ID = pm.post_id
            AND p.post_status = 'publish'
            AND pm.meta_key = '$sql_sort_by'
          ORDER BY CAST(pm.meta_value AS SIGNED) $sql_sort_order
          $limit_query
      ");
    } else {
      // If the sorting order is not set, default to menu_order
      if( empty( $sql_sort_by ) ) {
        $sql_sort_by = 'post_date';
      }
      
      $result = $wpdb->get_col("
        SELECT ID FROM {$wpdb->prefix}posts
          WHERE ID IN (" . implode(",", $matching_ids) . ")
            AND post_status = 'publish'
          ORDER BY $sql_sort_by $sql_sort_order
          $limit_query
      ");
    }
    
    if( !empty( $result ) ) {
      $result['total'] = $total;
      //UD_F::log("Search complete, returning: " . implode(" ,", $matching_ids));
      return $result;
    }
    
    return false;
  }


    /**
  * Returns array of all values for a particular attribute/meta_key
  */
  static function get_all_attribute_values($slug) {
    global $wpdb;


    $prefill_meta = $wpdb->get_col("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '$slug'");

    if(empty($prefill_meta[0]))
      unset($prefill_meta);

    $prefill_meta = apply_filters('wpp_prefill_meta', $prefill_meta, $slug);

    if(count($prefill_meta) < 1)
      return false;

    // Clean up values
    foreach($prefill_meta as $meta) {

      if(empty($meta))
        continue;

      $return[] = $meta;

    }

    // Remove duplicates
    $return = array_unique($return);

    sort($return);

    return $return;


  }
/**
   * Load property information into an array or an object
   *
    * @version 1.11 Added support for multiple meta values for a given key
    *
    * @since 1.11
   * @version 1.14 - fixed problem with drafts
   * @todo Fix the long dashes - when in latitude or longitude it breaks it when using static map
   *
    */
  static function get_property($id, $args = false) {
    global $wp_properties, $wpdb;

    if($return = wp_cache_get($id.$args))
      return $return;

     $defaults = array(
      'get_children' => 'true',
      'return_object' => 'false',
      'load_gallery' => 'true',
      'load_thumbnail' => 'true',
      'allow_multiple_values' => 'false',
      'load_parent' => 'true'
     );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    //UD_F::log("Loading property id: $id");
    $post = get_post($id, ARRAY_A);

    if($post['post_type'] != 'property')
      return false;

    $return = array();

    if ( $keys = get_post_custom( $id ) ) {
        foreach ( $keys as $key => $value ) {
        if($allow_multiple_values == 'false') {
          $value = $value[0];
        } 
        
        $keyt = trim($key);

        if ( '_' == $keyt{0} )
          continue;

        // Fix for boolean values
        switch($value) {

          case 'true':
          $real_value = true;
          break;

          case 'false':
          $real_value = false;
          break;

          default:
          $real_value = $value;
          break;

        }
         // if a property_meta value, we do a nl2br since it will most likely have line breaks
        if(array_key_exists($key, $wp_properties['property_meta']))
          $real_value = nl2br($real_value);


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
      Figure out what the thumbnail is, and load all sizes
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
      Load all attached images and their sizes
    */
    if($load_gallery == 'true') {
      // Get gallery images
      if($attachments) {
        foreach ( $attachments as $attachment_id => $attachment ) {
          $return['gallery'][$attachment->post_name]['post_title'] = $attachment->post_title;
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
      Load parent if exists.
      Inherit Parent's Properties
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
      $children = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}posts WHERE  post_type = 'property' AND post_status = 'publish' AND post_parent = '$id' ORDER BY menu_order ASC ");

      //print_r($children);
      if(count($children) > 0) {
          // Cycle through children and get necessary variables
          foreach($children as $child_id) {
            $child_object = WPP_F::get_property($child_id, "load_parent=false");
            $return['children'][$child_id] = $child_object;
            // Exclude variables from searchable attributes (to prevent ranges)
            $excluded_attributes = array(
              $wp_properties['configuration']['address_attribute'],
              'city',
              'country_code',
              'country',
              'state',
              'state_code',
              'state');

            foreach($wp_properties['searchable_attributes'] as $searchable_attribute)
               if(!empty($child_object[$searchable_attribute]) && !in_array($searchable_attribute, $excluded_attributes))
                $range[$searchable_attribute][]  = $child_object[$searchable_attribute];
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
        }
      }
    } /* end get_children */
 
    // Another name for location
    $return['address'] = $return['location'];

    $return['permalink'] = get_permalink($id);

    if(empty($return['phone_number']) && !empty($wp_properties['configuration']['phone_number']))
      $return['phone_number'] = $wp_properties['configuration']['phone_number'];

    if(is_array($return))
      ksort($return);

    $return = apply_filters('wpp_get_property', $return);

    // Get rid of all empty values
    foreach($return as $key => $item) {

      // Don't keys starting w/ post_
      if(strpos($key, 'post_') === 0)
        continue;

      if(empty($item))
        unset($return[$key]);
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
  Gets annex to an attribute
*/
  static function get_attrib_annex($attrib) {
    if($attrib == 'area')
      return __(' sq ft.','wpp');

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

    $defaults = array( );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if($exclude)
      $exclude = explode(',', $exclude);

    if($include)
      $include = explode(',', $include);

    $property_stats = $wp_properties['property_stats'];

    foreach($property_stats as $slug => $label) {
      $value = $property_object->$slug;

      // Exclude passed variables
      if(is_array($exclude) && in_array($slug, $exclude))
        continue;

      // Include only passed variables
      if(is_array($include) && in_array($slug, $include)) {
        if(!empty($value))
        $return[$label] = $value;
        continue;
      }


      if(!is_array($include)) {
      if(!empty($value))
        $return[$label] = $value;
      }

    }

    if(count($return) > 0)
      return $return;

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
  static function google_maps_infobox($post) {
    global $wp_properties;
    $map_image_type = $wp_properties['configuration']['single_property_view']['map_image_type'];
    $infobox_attributes = $wp_properties['configuration']['google_maps']['infobox_attributes'];
    $infobox_settings = $wp_properties['configuration']['google_maps']['infobox_settings'];



    if (!is_object($post) && is_array($post)){    // convert array in object (for supermap)
    $data = $post;
      if (!empty($post)) {
        $post = false;
        foreach ($data as $akey => $aval) {
          $post -> {$akey} = $aval;
        }
      }
    }

    if(empty($wp_properties['configuration']['address_attribute']))
      return;

    if(empty($post))
      return;

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

    //$infobox_attributes = array_reverse($infobox_attributes);

    $image_sizes = WPP_F::get_image_dimensions($map_image_type);
     ob_start();


    ?>

    <div id="infowindow" style="min-width:350px;" >

    <?php if($infobox_settings['show_property_title']  == 'true'): ?>
      <div class="wpp_google_maps_attribute_row_property_title" >
      <a href="<?php echo get_permalink($post->ID); ?>"><?php echo $post->post_title; ?></a>
      </div>
    <?php endif; ?>


    <table cellpadding="0" cellspacing="0" class="wpp_google_maps_infobox_table" style="">
      <tr>
        <td class="wpp_google_maps_left_col" style=" width: <?php echo $image_sizes[width]; ?>px">
          <img style="margin:0;padding:0;" width="<?php echo $image_sizes[width]; ?>" height="<?php echo $image_sizes[height]; ?>" src="<?php echo addslashes($post->images[$map_image_type]);?>" alt="<?php echo addslashes($post->post_title);?>" />

          <?php if($infobox_settings[show_direction_link] == 'true'): ?>
          <div class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_directions_link">
          <a target="_blank" href="http://maps.google.com/maps?gl=us&daddr=<?php echo addslashes(str_replace(' ','+', $post->{$wp_properties['configuration']['address_attribute']})); ?>"><?php _e('Get Directions','wpp') ?></a>
          </div>
          <?php endif; ?>

        </td>
        <td  class="wpp_google_maps_right_col"   valign="top">

          <ul class="wpp_google_maps_infobox">

          <?php foreach($infobox_attributes as $attribute_slug):
          if(empty($post->{$attribute_slug}))
            continue;
          ?>
          <li class="wpp_google_maps_attribute_row wpp_google_maps_attribute_row_<?php echo $attribute_slug; ?>">
          <span class="attribute"><?php echo $wp_properties['property_stats'][$attribute_slug]; ?></span>
          <span class="value"><?php echo apply_filters('wpp_stat_filter_'. $attribute_slug, addslashes($post->{$attribute_slug}), 'google_map_infobox'); ?></span>
          </li>
          <?php endforeach; ?>

          </ul>
        </td>
      </tr>
    </table>
    </div>


    <?php
    $data = ob_get_contents();
    $data = preg_replace(array('/[\r\n]+/'), array(""), $data);

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
     // Get Data (avoid using get_property() for performance reasons)


     $data = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = $id GROUP BY meta_key");
     foreach($data as $row) {
      $return[$row->meta_key] = $row->meta_value;
     }
     $return['post_title'] = htmlspecialchars(addslashes($wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = $id")));

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
   * This static function is not actually used, it's only use to hold some common translations that may be used by our themes.
   *
    * @since 1.14
   *
    */
  static function strings_for_translations() {


    // Denali Theme
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
?>