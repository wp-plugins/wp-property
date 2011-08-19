<?php
/*
Name: Admin Tools
Class: class_admin_tools
Version: 2.9.2
Feature ID: 1
Minimum Version: 1.17
Description: Tools for developing themes and extensions for WP-Property.
*/


add_action('wpp_init', array('class_admin_tools', 'init'));


/**
 * class_admin_tools Class
 *
 * Contains administrative functions
 *
 * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
 *
 * @version 1.0
 * @author Andy Potanin <andy.potanin@twincitiestech.com>
 * @package WP-Property
 * @subpackage Admin Functions
 */
class class_admin_tools {

  static function init() {

    // Add Inquiry page to Property Settings page array
    add_filter('wpp_settings_nav', array('class_admin_tools', 'settings_nav'));

    // Add Settings Page
    add_action('wpp_settings_content_admin_tools', array('class_admin_tools', 'settings_page'));
    
    add_action('wpp_contextual_help', array('class_admin_tools', 'wpp_contextual_help'));

  }
 

  /**
   * Adds admin tools manu to settings page navigation
   *
   * @version 1.0
   * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
   */
  function wpp_contextual_help($contextual_help) {
    
    if($contextual_help['page'] != 'property_page_property_settings') {
      return $contextual_help;
    }
      
    $contextual_help['content'][] = '<h3>' . __('Developer Tab Help') .'</h3>';    
    $contextual_help['content'][] = '<p>' . __('The <b>slug</b> is automatically created from the title and is used in the back-end.  It is also used for template selection, example: floorplan will look for a template called property-floorplan.php in your theme folder, or default to property.php if nothing is found.') .'</p>';
    $contextual_help['content'][] = '<p>' . __('If <b>Searchable</b> is checked then the property will be loaded for search, and available on the property search widget.') .'</p>';
    $contextual_help['content'][] = '<p>' . __('If <b>Location Matters</b> is checked, then an address field will be displayed for the property, and validated against Google Maps API.  Additionally, the property will be displayed on the SuperMap, if the feature is installed.') .'</p>';
    $contextual_help['content'][] = '<p>' . __('<b>Hidden Attributes</b> determine which attributes are not applicable to the given property type, and will be grayed out in the back-end.') .'</p>';
    $contextual_help['content'][] = '<p>' . __('<b>Inheritance</b> determines which attributes should be automatically inherited from the parent property') .'</p>';
    $contextual_help['content'][] = '<p>' . __('Property attributes are meant to be short entries that can be searchable, on the back-end attributes will be displayed as single-line input boxes. On the front-end they are displayed using a definitions list.') .'</p>';
    $contextual_help['content'][] = '<p>' . __('Making an attribute as "searchable" will list it as one of the searchable options in the Property Search widget settings.') .'</p>';
    $contextual_help['content'][] = '<p>' . __('Be advised, attributes added via add_filter() function supercede the settings on this page.') .'</p>';
    
    
    
    return $contextual_help;
    

  
  }
  
  
  /**
   * Adds admin tools manu to settings page navigation
   *
   * @version 1.0
   * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
   */
  function settings_nav($tabs) {

     $tabs['admin_tools'] = array(
      'slug' => 'admin_tools',
      'title' => __('Developer','wpp')
    );

    return $tabs;
  }


  /**
   * Displays advanced management page
   *
    *
   * @version 1.0
   * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
   */
  function settings_page() {
  global $wpdb, $wp_properties; ?>
  <script type="text/javascript">
    jQuery(document).ready(function() {

      jQuery("#wpp_inquiry_attribute_fields tbody").sortable();
      jQuery("#wpp_inquiry_meta_fields tbody").sortable();

      jQuery("#wpp_inquiry_attribute_fields tbody tr, #wpp_inquiry_meta_fields tbody tr").live("mouseover", function() {
        jQuery(this).addClass("wpp_draggable_handle_show");
      });;

      jQuery("#wpp_inquiry_attribute_fields tbody tr, #wpp_inquiry_meta_fields tbody tr").live("mouseout", function() {
        jQuery(this).removeClass("wpp_draggable_handle_show");
      });;



    });
  </script>
  <style type="style/text">
  #wpp_inquiry_attribute_fields tbody tr { cursor:move; }
  #wpp_inquiry_meta_fields tbody tr { cursor:move; }
  </style>

  <table class="form-table">
    <tr>      
      <td>        
        <h3><?php _e('Property Types','wpp') ?></h3>
        <table id="wpp_inquiry_property_types" class="ud_ui_dynamic_table widefat">
        <thead>
          <tr>
            <th><?php _e('Property Type','wpp') ?></th>
            <th><?php _e('Slug','wpp') ?></th>
            <th><?php _e('Settings','wpp') ?></th>
            <th><?php _e('Hidden Attributes','wpp') ?></th>
            <th><?php _e('Inheritance','wpp') ?></th>
           </tr>
        </thead>
        <tbody>
        <?php  foreach($wp_properties['property_types'] as $property_slug => $label):  ?>

          <tr class="wpp_dynamic_table_row" slug="<?php echo $property_slug; ?>"  new_row='false'>
          <td >
            <input class="slug_setter" type="text" name="wpp_settings[property_types][<?php echo $property_slug; ?>]" value="<?php echo $label; ?>" /><br />
            <span class="wpp_delete_row wpp_link">Delete</span>
          </td>
          <td>
            <input type="text" class="slug" readonly='readonly' value="<?php echo $property_slug; ?>" />
          </td>

          <td>
            <ul>
              <li>
                <input class="slug" id="<?php echo $property_slug; ?>_searchable_property_types" <?php if(is_array($wp_properties['searchable_property_types']) && in_array($property_slug, $wp_properties['searchable_property_types'])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[searchable_property_types][]" value="<?php echo $property_slug; ?>" />
                <label for="<?php echo $property_slug; ?>_searchable_property_types"><?php _e('Searchable','wpp') ?></label>
              </li>

              <li>
                <input class="slug" id="<?php echo $property_slug; ?>_location_matters"  <?php if(in_array($property_slug, $wp_properties['location_matters'])) echo " CHECKED "; ?> type="checkbox"  name="wpp_settings[location_matters][]" value="<?php echo $property_slug; ?>" />
                <label for="<?php echo $property_slug; ?>_location_matters"><?php _e('Location Matters','wpp') ?></label>
              </li>
            </ul>
          </td>


          <td >
            <ul class="wp-tab-panel wpp_hidden_property_attributes">
            <?php foreach($wp_properties['property_stats'] as $property_stat_slug => $property_stat_label): ?>
            <li>
              <input id="<?php echo $property_slug . "_" .$property_stat_slug;?>_hidden_attributes" <?php if(isset($wp_properties['hidden_attributes'][$property_slug]) && in_array($property_stat_slug, $wp_properties['hidden_attributes'][$property_slug])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug;?>][]" value="<?php echo $property_stat_slug; ?>" />
              <label for="<?php echo $property_slug . "_" .$property_stat_slug;?>_hidden_attributes">
                <?php echo $property_stat_label;?>
              </label>
            </li>
            <?php endforeach; ?>

            <?php foreach($wp_properties['property_meta'] as $property_meta_slug => $property_meta_label): ?>
            <li>
              <input id="<?php echo $property_slug . "_" . $property_meta_slug;?>_hidden_attributes" <?php if(isset($wp_properties['hidden_attributes'][$property_slug]) && in_array($property_meta_slug, $wp_properties['hidden_attributes'][$property_slug])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug;?>][]" value="<?php echo $property_meta_slug; ?>" />
              <label for="<?php echo $property_slug . "_" . $property_meta_slug;?>_hidden_attributes">
                <?php echo $property_meta_label;?>
              </label>
            </li>
            <?php endforeach; ?>

            </ul>
          </td>

           <td >
            <ul class="wp-tab-panel wpp_inherited_property_attributes">
            <?php foreach($wp_properties['property_stats'] as $property_stat_slug => $property_stat_label): ?>
            <li>
              <input id="<?php echo $property_slug . "_" .$property_stat_slug;?>_inheritance" <?php if(isset($wp_properties['property_inheritance'][$property_slug]) && in_array($property_stat_slug, $wp_properties['property_inheritance'][$property_slug])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[property_inheritance][<?php echo $property_slug;?>][]" value="<?php echo $property_stat_slug; ?>" />
              <label for="<?php echo $property_slug . "_" .$property_stat_slug;?>_inheritance">
                <?php echo $property_stat_label;?>
              </label>
            </li>
            <?php endforeach; ?>

            </ul>
          </td>


        </tr>

        <?php endforeach; ?>
        </tbody>

        <tfoot>
          <tr>
            <td colspan='5'>
            <input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row','wpp') ?>" />
            </td>
          </tr>
        </tfoot>

        </table>
      </td>
    </tr>

    <tr>
      <td>
        <h3><?php _e('Property Stats','wpp') ?></h3>

        <table id="wpp_inquiry_attribute_fields" class="ud_ui_dynamic_table widefat">
        <thead>
          <tr>
            <th class='wpp_attribute_name_col'><?php _e('Attribute Name','wpp') ?></th>
             <th class='wpp_settings_input_col'><?php _e('Settings','wpp') ?></th>
             <th class='wpp_search_input_col'><?php _e('Search Settings and Values ','wpp') ?></th>
            <th class='wpp_admin_input_col'><?php _e('Admin Pre-defined Values','wpp') ?></th>
            <th class='wpp_draggable_handle'>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
        <?php  foreach($wp_properties['property_stats'] as $slug => $label):  ?>

          <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>"  new_row='false'>
          

          <td class="wpp_attribute_name_col" >
            <ul>
            <li>
              <input class="slug_setter" type="text" name="wpp_settings[property_stats][<?php echo $slug; ?>]" value="<?php echo $label; ?>" />
            </li>
            <li class="wpp_development_advanced_option">
              <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>" />
            </li>

            <li>
              <span class="wpp_show_advanced"><?php _e('Toggle Advanced Settings'); ?></span>
            </li>

            </ul>
          </td>

          <td class="wpp_settings_input_col">

          <ul>
            <li>
              <input <?php if(in_array($slug, ((!empty($wp_properties['sortable_attributes'])?$wp_properties['sortable_attributes']:array())))) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[sortable_attributes][]" value="<?php echo $slug; ?>" />
              <label><?php _e('Sortable'); ?></label>
            </li>
            
            <li>
              <input <?php if(is_array($wp_properties['searchable_attributes']) && in_array($slug, $wp_properties['searchable_attributes'])) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[searchable_attributes][]" value="<?php echo $slug; ?>" />
              <label><?php _e('Searchable'); ?></label>
            </li>

            <li class="wpp_development_advanced_option">
              <input <?php if(is_array($wp_properties['hidden_frontend_attributes']) && in_array($slug, $wp_properties['hidden_frontend_attributes'])) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[hidden_frontend_attributes][]" value="<?php echo $slug; ?>" />
              <label><?php _e('Admin Only'); ?></label>
            </li>

            <li class="wpp_development_advanced_option">
              <input <?php if(is_array($wp_properties['numeric_attributes']) && in_array($slug, $wp_properties['numeric_attributes'])) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[numeric_attributes][]" value="<?php echo $slug; ?>" />
              <label><?php _e('Format as number'); ?></label>
            </li>            

            <li class="wpp_development_advanced_option">
              <input <?php if(is_array($wp_properties['currency_attributes']) && in_array($slug, $wp_properties['currency_attributes'])) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[currency_attributes][]" value="<?php echo $slug; ?>" />
              <label><?php _e('Format as currency'); ?></label>
            </li>            
            
            
            <li class="wpp_development_advanced_option">
              <span class="wpp_delete_row wpp_link"><?php _e('Delete Attribute','wpp') ?></span>
            </li>
            
          </ul>
            
          </td>
          <td class="wpp_search_input_col">
            <ul>
                        
            <li class="wpp_development_advanced_option">
              <textarea name="wpp_settings[predefined_search_values][<?php echo $slug; ?>]"><?php echo $wp_properties['predefined_search_values'][$slug]; ?></textarea>
            </li>

            <li class="">
              <select name="wpp_settings[searchable_attr_fields][<?php echo $slug; ?>]">
                <option value="input" <?php selected($wp_properties['searchable_attr_fields'][$slug],'input'); ?>><?php _e('Text Input','wpp') ?></option>
                <option value="range_input" <?php selected($wp_properties['searchable_attr_fields'][$slug],'range_input'); ?>><?php _e('Text Input Range','wpp') ?></option>
                <option value="range_dropdown" <?php selected($wp_properties['searchable_attr_fields'][$slug],'range_dropdown'); ?>><?php _e('Range (dropdown)','wpp') ?></option>
                <option value="dropdown" <?php selected($wp_properties['searchable_attr_fields'][$slug],'dropdown'); ?>><?php _e('Dropdown','wpp') ?></option>
                <option value="checkbox" <?php selected($wp_properties['searchable_attr_fields'][$slug], 'checkbox'); ?>><?php _e('Checkbox','wpp') ?></option>
                <option value="multi_checkbox" <?php selected($wp_properties['searchable_attr_fields'][$slug], 'multi_checkbox'); ?>><?php _e('Multi-Checkbox','wpp') ?></option>
              </select>
            </li>


          </ul>
          </td>

          <td class="wpp_admin_input_col">
          <ul>
            <li class="wpp_development_advanced_option">
              <textarea name="wpp_settings[predefined_values][<?php echo $slug; ?>]"><?php echo $wp_properties['predefined_values'][$slug]; ?></textarea>
            </li>


          </ul>

          </td>

           <td class="wpp_draggable_handle">&nbsp;</td>          
        </tr>

        <?php endforeach; ?>
        </tbody>

        <tfoot>
          <tr>
            <td colspan='6'>
            <input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row','wpp') ?>" />
            </td>
          </tr>
        </tfoot>

        </table>
        <br class="cb" />
        <h3><?php _e('Property Meta','wpp') ?></h3>
        <p><?php _e('Meta is used for descriptions,  on the back-end  meta fields will be displayed as textareas.  On the front-end they will be displayed as individual sections.','wpp') ?></p>

        <table id="wpp_inquiry_meta_fields" class="ud_ui_dynamic_table widefat">
        <thead>
          <tr>
            <th class='wpp_draggable_handle'>&nbsp;</th>
            <th class='wpp_attribute_name_col'><?php _e('Attribute Name','wpp') ?></th>
            <th class='wpp_attribute_slug_col'><?php _e('Attribute Slug','wpp') ?></th>
            <th class='wpp_settings_col'><?php _e('Settings','wpp') ?></th>
            <th class='wpp_delete_col'>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
        <?php  foreach($wp_properties['property_meta'] as $slug => $label):  ?>

          <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>" new_row='false'>
          <th class='wpp_draggable_handle'>&nbsp;</th>
          <td >
           <ul>
            <li>
               <input class="slug_setter" type="text" name="wpp_settings[property_meta][<?php echo $slug; ?>]" value="<?php echo $label; ?>" />
            </li>
            </ul>
          <td>
            <ul>
            <li>
               <input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>" />
            </li>
            </ul>
          </td>
          <td>
            <ul>
              </li>
              <input <?php if(is_array($wp_properties['hidden_frontend_attributes']) && in_array($slug, $wp_properties['hidden_frontend_attributes'])) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[hidden_frontend_attributes][]" value="<?php echo $slug; ?>" />
              <label><?php _e('Show in Admin Only'); ?></label>
            </li>
            </ul>
          </td>
  
            <td>
            <span class="wpp_delete_row wpp_link"><?php _e('Delete Meta Attribute','wpp') ?></span>
            </td>
        </tr>

        <?php endforeach; ?>
        </tbody>

        <tfoot>
          <tr>
            <td colspan='4'>
            <input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row','wpp') ?>" />
            </td>
          </tr>
        </tfoot>

        </table>
      </td>
    </tr>


    <tr>
      <td>
        <h3><?php _e('Advanced Options','wpp'); ?></h3>
        <ul>
          <li>
            <?php  echo UD_UI::checkbox("name=wpp_settings[configuration][show_ud_log]&label=" . __('Show Log.','wpp'), $wp_properties['configuration']['show_ud_log']); ?> <br />
            <span class="description"><?php _e('The log is always active, but the UI is hidden.  If enabled, it will be visible in the admin sidebar.','wpp'); ?></span>
          </li>
          <li>
            <?php  echo UD_UI::checkbox("name=wpp_settings[configuration][disable_automatic_feature_update]&label=" . __('Disable automatic feature updates.','wpp'), $wp_properties['configuration']['disable_automatic_feature_update']); ?> <br />
            <span class="description"><?php _e('If disabled, WP-Property will not automatically download updates to premium features.','wpp'); ?></span>
          </li>
          <li>
            <?php  echo UD_UI::checkbox("name=wpp_settings[configuration][disable_wordpress_postmeta_cache]&label=" . __('Disable WordPress update_post_caches() function.','wpp'), $wp_properties['configuration']['disable_wordpress_postmeta_cache']); ?> <br />
            <span class="description"><?php _e('This may solve Out of Memory issues if you have a lot of properties.','wpp'); ?></span>
          </li>
          
        </ul>
      </td>
    </tr>
  </table>

    <?php
  }



}


