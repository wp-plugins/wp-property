<?php
/**
Name: Cron for WP Property
Description: Allow cron jobs to be executed from Command Line interface.
*/


  /** Need to at least have the do_xml_import argument */
  if (empty($argv[0])) {
    die(); 
  }
  
  ini_set( "display_errors", 0);
  set_time_limit(0);  
  ignore_user_abort(true);
      
  //** Load WP */
  $wp_load_path = str_replace('wp-content/plugins/wp-property/cron.php', 'wp-load.php', __FILE__);
    
  if(!file_exists($wp_load_path)) {
    die('Cannot load WP using: ' . $wp_load_path);
  } else {
    require_once $wp_load_path;
  }
    
  //** Ensure file was loaded and procesed */
  if(ABSPATH && class_exists('class_wpp_property_import')) {
    define('DOING_WPP_CRON', true);
  } else {
    die('Unable to load XML Importer.');
  }  

  $action = $argv[1];
  $schedule_hash = $argv[2];
  
  if(empty($action) || empty($schedule_hash)) {
    die('Missing an argument.');
  }

  /** Begin Loading Import*/
  if($action == 'do_xml_import' && !empty($schedule_hash)) {   
          
      define('WPP_IMPORTER_HASH', $schedule_hash);

      //class_wpp_property_import::init();
      class_wpp_property_import::run_from_cron_hash();
      
      die();
    
  } else {
    die('Nothing done.');
  }
 