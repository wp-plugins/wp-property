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
  ?>
    <div class="wpp_settings_block">
      <?php echo __('This URL lets you access your property export feed, it is custom to your site:'); ?><br />
      <a href='<?php echo $export_url; ?>'><?php echo $export_url; ?></a>
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
    return __("There has been an error retreiving your API key.", "wpp");
  }
  
  /**
   * This function grabs the API key from TCT's servers
   */
  function get_api_key(){
    $blogname = get_bloginfo('url');
    $blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
    $system = 'wpp';
    $wpp_version = get_option( "wpp_version" );

    $check_url = "http://updates.twincitiestech.com/key_generator.php?system=$system&site=$blogname&system_version=$wpp_version";
    
    $response = @wp_remote_get($check_url);
    if(!$response) return false;
    // Check for errors
    if(is_object($response) && !empty($response->errors)) {
      foreach($response->errors as $errors) {
        $error_string .= implode(",", $errors);
        UD_F::log("API Check Error: " . $error_string);
      }
      return false;
    }
    // Quit if failture
    if($response[response][code] != '200') return false;
    
    // Go ahead and return, it should just be the API key
    return $response['body'];
  }
  
  /**
   * This function takes all your properties and exports it as an XML feed
   */
  function wpp_export_properties(){
    // Set a new path
    set_include_path(get_include_path() . PATH_SEPARATOR . WPP_Path.'/third-party/XML/');
    // Include our necessary libaries
    require_once 'Serializer.php';
    require_once 'Unserializer.php';
    
    $api_key = wpi_property_export::get_api_key();
    
    // If the API key isn't valid, we quit
    if($_REQUEST['api'] != $api_key) die(__('Invalid API key.', 'wpp'));
    // Start building our wp query object
    $args = array(
      'post_type' => 'property',
      'post_status' => 'publish',
      'posts_per_page' => -1
    );
    $wpq = new wp_query($args);
    if($wpq->post_count == 0) die(__('No published properties.', 'wpp'));
    //Start the XML
    header('Content-type: text/xml');
    print '<?xml version="1.0"?><properties>';
    $count = 0;
    foreach($wpq->posts as $post){
      if(isset($_REQUEST['limit']) && $count == $_REQUEST['limit']) break;
      $count++;
      $property = WPP_F::get_property($post->ID, "return_object=true&load_parent=false");      
      
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
      
      $xml = new XML_Serializer();
      $xml->serialize($property);
      $data = preg_replace('/stdClass/i', 'property', $xml->getSerializedData());
      print $data;
    }
    die("</properties>");
  }
}