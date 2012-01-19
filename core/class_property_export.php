<?php
/**
 * This file holds the functionality that allows us to export our properties to an XML feed
 * @since 1.4.2
 */

/** First we need to add our appropriate actions */
add_action('wpp_settings_help_tab', array('wpi_property_export', 'help_tab'), 10, 4);
add_action('wp_ajax_wpp_export_properties', array('wpi_property_export', 'wpp_export_properties'));
add_action('wp_ajax_nopriv_wpp_export_properties', array('wpi_property_export', 'wpp_export_properties'));

/**
 * This is the actual object which peforms all of the functionality 
 *
 * @todo: wpp_agents data should include agent data not just ID
 * @todo: Featured image is not being imported. Should be able to take from feed.
 *
 */
class wpi_property_export {
  /**
   * This function shows help stuff on the properties settings help tab
   */ 
  function help_tab(){
    $export_url = wpi_property_export::get_property_export_url();
    
    if(!$export_url) {
      return;
    }
    
    $export_url = $export_url . '&limit=10&format=json';
    
  ?>
    <div class="wpp_settings_block">
      <label for="wpp_export_url"><?php _e('Feed URL:', 'wpp'); ?></label>
      <input id="wpp_export_url" type="text" style="width: 70%" readonly="true" value="<?php echo esc_attr($export_url); ?>" />
      <a class="button" href="<?php echo $export_url; ?>"><?php _e('Open', 'wpp'); ?></a>
      <br /><br />    
      <?php _e('You may append the export URL with the following arguments:', 'wpp');?>
      <ul style="margin: 15px 0 0 10px">
        <li><b>limit</b> - number</li>
        <li><b>per_page</b> - number</li>
        <li><b>starting_row</b> - number</li>
        <li><b>sort_order</b> - number</li>
        <li><b>sort_by</b> - number</li>
        <li><b>property_type</b> - string - <?php _e('Slug for the property type.', 'wpp'); ?></li>
        <li><b>format</b> - string - "xml" <?php _e('or', 'wpp'); ?> "json"</li>
        </ul>
      </li>
    </ul>
    </div> <?php
  }
  
  /**
   * This function generates your unique site's export feed
   * @returns string URL to site's export feed
   */
  function get_property_export_url(){
    if($apikey = wpi_property_export::get_api_key()){
      if(empty($apikey)) return __("There has been an error retreiving your API key.", "wpp");
      // We have the API key, we need to build the url
      return admin_url('admin-ajax.php')."?action=wpp_export_properties&api=".$apikey;
    }
    //return __("There has been an error retreiving your API key.", "wpp");
    return false;
  }
  
  /**
   * This function grabs the API key from TCT's servers
   */
  function get_api_key($args = false){
  
    $defaults = array(
      'return_all' => false,
      'force_check' => false
    );
    
    
    $args = wp_parse_args( $args, $defaults );
    
    //** check if API key already exists */
    $ud_api_key = get_option('ud_api_key');
    
    //** if key exists, and we are not focing a check, return what we have */
    if($ud_api_key && !$args['force_check']) {
      return $ud_api_key;
    }
    
    $blogname = get_bloginfo('url');
    $blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
    $system = 'wpp';
    $wpp_version = get_option( "wpp_version" );

    $check_url = "http://updates.usabilitydynamics.com/key_generator.php?system=$system&site=$blogname&system_version=$wpp_version";
    
    $response = @wp_remote_get($check_url);
    
    if(!$response) {
      return false;
    }    
    
    // Check for errors
    if(is_object($response) && !empty($response->errors)) {
      foreach($response->errors as $errors) {
        $error_string .= implode(",", $errors);
        UD_F::log("API Check Error: " . $error_string);
      }
      return false;
    }
    
    // Quit if failture
    if($response['response']['code'] != '200') {
      return false;
    }
    
    $response['body'] = trim($response['body']);
 
    //** If return is not in MD5 format, it is an error */
    if(strlen($response['body']) != 40) {
    
      if($args['return']) {
        return $response['body'];
      } else {      
        UD_F::log("API Check Error: " . sprintf(__('An error occured during premium feature check: <b>%s</b>.','wpp'), $response['body']));
        return false;
      }
    }
    
    //** update wpi_key is DB */
    update_option('ud_api_key', $response['body']);
    
    // Go ahead and return, it should just be the API key
    return $response['body'];
  }
  
  /**
   * This function takes all your properties and exports it as an XML feed
   *
   * @todo Improve efficiency of function, times out quickly for feeds of 500 properties. memory_limit and set_time_limit should be removed once efficiency is improved
   *
   */
  function wpp_export_properties(){
    global $wp_properties;
	
  ini_set('memory_limit', -1);
  set_time_limit(120);

  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $starttime = $mtime; 

  // Set a new path
  set_include_path(get_include_path() . PATH_SEPARATOR . WPP_Path.'third-party/XML/');
  // Include our necessary libaries
  require_once 'Serializer.php';
  require_once 'Unserializer.php';

  $api_key = wpi_property_export::get_api_key();

  $taxonomies = $wp_properties['taxonomies'];

    // If the API key isn't valid, we quit
    if($_REQUEST['api'] != $api_key) {
      die(__('Invalid API key.', 'wpp'));
    }
    
    if(isset($_REQUEST['limit'])) {
      $per_page = $_REQUEST['limit'];
      $starting_row = 0;
    }    
    
    if(isset($_REQUEST['per_page'])) {
      $per_page = $_REQUEST['per_page'];
    }
    
    if(isset($_REQUEST['starting_row'])) {
      $starting_row = $_REQUEST['starting_row'];
    }
    
    if(isset($_REQUEST['property_type'])) {
      $property_type = $_REQUEST['property_type'];
    } else {
      $property_type = 'all';
    }
    
    if(strtolower($_REQUEST['format']) == 'xml') {  
      $xml_format = true;
    } else {
      $xml_format = false;    
    }
    
    $wpp_query['query']['property_type'] = 'listing';
  
    $wpp_query['query']['pagi'] = $starting_row . '--' . $per_page;
    $wpp_query['query']['sort_by'] = ($_REQUEST['sort_by'] ? $_REQUEST['sort_by'] : 'post_date' );
    $wpp_query['query']['sort_order'] = ($_REQUEST['sort_order'] ? $_REQUEST['sort_order'] : 'ASC' );
    $wpp_query['query']['property_type'] = $property_type;
 
    $wpp_query = WPP_F::get_properties($wpp_query['query'], true);
    
    $results = $wpp_query['results'];
 
    if(count($results) == 0) {
      die(__('No published properties.', 'wpp'));
    }

    if($xml_format) {
      header('Content-type: text/xml');
      header('Content-Disposition: inline; filename="wpp_xml_data.xml"');
    } else {
      header('Content-type: application/json');
      header('Content-Disposition: inline; filename="wpp_xml_data.json"');      
    }
    
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");
    
    foreach($results as $count => $id) {

      $property = WPP_F::get_property($id, "return_object=true&load_parent=false");      
      
      if($property->post_parent && !$property->parent_gpid) {
        $property->parent_gpid = WPP_F::maybe_set_gpid($property->post_parent);
      }
       
      // Unset the children, as we'll get to those
      unset(
        $property->wpp_agents,
        $property->comment_count,
        $property->post_modified_gmt,
        $property->comment_status,
        $property->post_password,
        $property->children,
        $property->guid,
        $property->filter,
        $property->post_author,
        $property->permalink,
        $property->ping_status,
        $property->post_modified,
        $property->post_mime_type
      );
      
      // Set unique site ID
      $property->wpp_unique_id = md5($api_key.$property->ID);

      //** Get taxonomies */
      if($taxonomies) {
        foreach($taxonomies as $taxonomy_slug => $taxonomy_data) {        
          if($these_terms = wp_get_object_terms($property->ID, $taxonomy_slug, array('fields' => 'names'))) {            
            $property->taxonomies->{$taxonomy_slug} = $these_terms;
          }
        }
      }
      
      $fixed_property = new stdClass();
      
      foreach($property as $meta_key => $meta_value) {
        
        if(is_array($meta_value) || is_object($meta_value)) {
          $fixed_property->$meta_key = $meta_value;
          continue;
        }
        
        //** Maybe Unserialize */
        $meta_value = maybe_unserialize($meta_value);
       
        //$meta_value = htmlentities($meta_value);
        $fixed_property->$meta_key = $meta_value;
        //$fixed_property->$meta_key = '<![CDATA[' .  ($meta_value) . ']]>';
        
      }
      
      $properties[$id] = $fixed_property;
      
    }

    $result = json_encode($properties);
    
    if($xml_format) {    
      $result = WPP_F::json_to_xml($result);
    } 
    
    
    die($result);
    
  }
}