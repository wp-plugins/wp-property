<?php
/**
Name: Cron for WP Property
Description: Allow cron jobs to be executed from Command Line interface.
*/
if (empty($argv[0])) return; 

######### init WP #####################
set_time_limit(0);
ignore_user_abort(true);
define('DOING_CRON', true);
require_once('../../../wp-load.php');

/** Switch based on task needed */
if($argv[1] == 'do_xml_import' && class_exists('class_wpp_property_import')){
  if(isset($argv[2])) {
    global $wpp_property_import;
    /** We are doing the XML import */
    $wpp_property_import = $wp_properties['configuration']['feature_settings']['property_import'];
    $_REQUEST['wpp_schedule_import'] = $argv[2];
    $_GET['wpp_schedule_import'] = $argv[2];
    $_GET['echo_log'] = 'true';
    $_REQUEST['echo_log'] = 'true';
    if(isset($argv[3]) && $argv[3] == 'preview'){
      $_REQUEST['stepping_element'] = 0;
    }
    class_wpp_property_import::init();
    class_wpp_property_import::run_from_cron_hash(true);
    die();
  }
  else echo "\r\nMissing argument!\r\n";
}
elseif($argv[1] == 'do_fix_duplicate_imports' && class_exists('class_wpp_property_import')){
  global $wpdb;
  $wpdb->show_errors();
  /** Get all posts with duplicate titles */
  $result = $wpdb->get_results("SELECT ID, post_title, COUNT( * ) AS `count` FROM {$wpdb->prefix}posts WHERE post_type =  'property' GROUP BY post_title HAVING COUNT( * ) >1");
  /** Loop through them, loading the ID, and the one with the duplicate ID */
  foreach($result as $row){
    /** Only do this if we have 2 items */
    if($row->count == 2){
      $duplicates = $wpdb->get_results("SELECT ID, post_modified FROM {$wpdb->prefix}posts WHERE post_title = '{$wpdb->escape($row->post_title)}' AND post_type = 'property' ORDER BY post_modified ASC");
      /** Load both items */
      $duplicate = WPP_F::get_property($duplicates[1]->ID);
      /** Update the import unique ID for the original */
      update_post_meta($duplicates[0]->ID, 'wpp_old_id', $duplicate['wpp_old_id']);
      update_post_meta($duplicates[0]->ID, 'wpp_unique_id', $duplicate['wpp_unique_id']);
      /** Delete the new one */
      wp_delete_post($duplicate['ID']);
    }
  }
  die('Successfully deleted all detected duplicates!');
}
elseif($argv[1] == 'erase_all_properties'){
  global $wpdb;
  $wpdb->show_errors();
  /** First, delete attachments */
  $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'attachment' AND post_parent IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property') OR post_excerpt = 'qr_code'";
  foreach($wpdb->get_results($sql) as $row) {
    wp_delete_attachment($row->ID, true);
    print "Deleted attachment: ".$row->ID."\r\n";
  }
  /** Now, delete posts */
  $sql = "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'property'";
  foreach($wpdb->get_results($sql) as $row){
    wp_delete_post($row->ID, true);
    class_wpp_property_import::delete_post($row->ID);
    print "Deleted post: ".$row->ID."\r\n";
  }
  die("Done deleting posts\r\n");
}

/** If we made it here, we're at the end of the script */
die ("
SCRIPT USAGE: php ./" . $argv[0] . " <action> <variables>
  Actions Possible==
  do_xml_import: 
    Premium feature to pull in properties from an external source
    php ./". $argv[0] ." do_xml_import <hash>
    where <hash> is hash ID of the sheduled import task of XML Property Importer.
  erase_all_properties:
    !WARNING! This command clears out all properties from your database !WARNING!
");
/** Depreciated:
  do_fix_duplicate_imports: 
    Finds posts with the same name, but different Import IDs and combines them
*/