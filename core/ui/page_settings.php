<?php
/**
 * Page handles all the settings configuration for WP-Property. Premium features can hook into this page.
 *
 * Actions:
 * - wpp_settings_page_property_page
 * - wpp_settings_help_tab
 * - wpp_settings_content_$slug
 *
 * Filters:
 *  - wpp_settings_nav
 *
 * @version 1.12
 * @package   WP-Property
 * @author     TwinCitiesTech.com
 * @copyright  2011 TwinCitiesTech.com, Inc.
*/
 

// Check if premium folder is writable
$wp_messages = WPP_F::check_premium_folder_permissions();

if(isset($_REQUEST['message'])) {

  switch($_REQUEST['message']) {
  
    case 'updated':    
    $wp_messages['notice'][] = "Settings updated.";
    break;
  }



}
?>

 <script type="text/javascript">
  jQuery(document).ready(function() {
    jQuery("#wpp_settings_tabs").tabs({ cookie: { expires: 30 } });

  // Show settings array
  jQuery("#wpp_show_settings_array").click(function() {
    jQuery("#wpp_show_settings_array_cancel").show();
    jQuery("#wpp_show_settings_array_result").show();
  });

  // Hide settings array
  jQuery("#wpp_show_settings_array_cancel").click(function() {
    jQuery("#wpp_show_settings_array_result").hide();
    jQuery(this).hide();
  });

  // Hide property query
  jQuery("#wpp_ajax_property_query_cancel").click(function() {
    jQuery("#wpp_ajax_property_result").hide();
    jQuery(this).hide();
  });

  // Check plugin updates
  jQuery("#wpp_ajax_check_plugin_updates").click(function() {

    jQuery('.plugin_status').remove();

    jQuery.post(ajaxurl, {
        action: 'wpp_ajax_check_plugin_updates'
        }, function(data) {

        message = "<div class='plugin_status updated fade'><p>" + data + "</p></div>";
        jQuery(message).insertAfter("h2");
      });
  });  
  
  // Revalidate all addresses
  jQuery("#wpp_ajax_revalidate_all_addresses").click(function() {

    jQuery(this).val('Processing...');
    jQuery(this).attr('disabled', true);
    jQuery('.address_revalidation_status').remove();

    jQuery.post(ajaxurl, {
        action: 'wpp_ajax_revalidate_all_addresses'
        }, function(data) {

        jQuery("#wpp_ajax_revalidate_all_addresses").val('Revalidate again');
        jQuery("#wpp_ajax_revalidate_all_addresses").attr('disabled', false);
        
        if(data.success == 'true')
          message = "<div class='address_revalidation_status updated fade'><p>" + data.message + "</p></div>";
        else
          message = "<div class='address_revalidation_status error fade'><p>" + data.message + "</p></div>";
        
        jQuery(message).insertAfter("h2");
      }, 'json');
  });  
  
  // Show property query
  jQuery("#wpp_ajax_property_query").click(function() {

    var property_id = jQuery("#wpp_property_class_id").val();

    jQuery("#wpp_ajax_property_result").html("");

    jQuery.post(ajaxurl, {
        action: 'wpp_ajax_property_query',
				property_id: property_id
       }, function(data) {
        jQuery("#wpp_ajax_property_result").show();
        jQuery("#wpp_ajax_property_result").html(data);
        jQuery("#wpp_ajax_property_query_cancel").show();

      });

  });

  // Show property query
  jQuery("#wpp_check_premium_updates").click(function() {


    jQuery("#wpp_plugins_ajax_response").hide();

    jQuery.post(ajaxurl, {
				action: 'wpp_ajax_check_plugin_updates'
       }, function(data) {
        jQuery("#wpp_plugins_ajax_response").show();
        jQuery("#wpp_plugins_ajax_response").html(data);

      });

  });


  });
 </script>

<div class="wrap">
<h2><?php _e('Property Settings','wpp'); ?></h2>

<?php if(isset($wp_messages['error']) && $wp_messages['error']): ?>
<div class="error">
  <?php foreach($wp_messages['error'] as $error_message): ?>
    <p><?php echo $error_message; ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if(isset($wp_messages['notice']) && $wp_messages['notice']): ?>
<div class="updated fade">
  <?php foreach($wp_messages['notice'] as $notice_message): ?>
    <p><?php echo $notice_message; ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>



<form method="post" action="<?php echo admin_url('edit.php?post_type=property&page=property_settings'); ?>"  enctype="multipart/form-data" />
<?php wp_nonce_field('wpp_setting_save'); ?>

<div id="wpp_settings_tabs" class="clearfix">
  <ul class="tabs">
    <li><a href="#tab_main"><?php _e('Main','wpp'); ?></a></li>
    <li><a href="#tab_display"><?php _e('Display','wpp'); ?></a></li>
      <?php
      if(is_array($wp_properties['available_features'])) {

        $wpp_plugin_settings_nav = apply_filters('wpp_settings_nav', array());

        foreach($wp_properties['available_features'] as $plugin) {

          if(@$plugin['status'] == 'disabled')
            unset($wpp_plugin_settings_nav[$plugin]);

        }
        if(is_array($wpp_plugin_settings_nav)) {
          foreach($wpp_plugin_settings_nav as $nav) {
            echo "<li><a href='#tab_{$nav['slug']}'>{$nav['title']}</a></li>\n";
          }
        }
      }
    ?>

    <?php if(count($wp_properties['available_features']) > 0): ?>
    <li><a href="#tab_plugins"><?php _e('Plugins','wpp'); ?></a></li>
    <?php endif; ?>
    <li><a href="#tab_troubleshooting"><?php _e('Help','wpp'); ?></a></li>


  </ul>

  <div id="tab_main">

     <?php do_action('wpp_settings_main_top', $wp_properties); ?>

    <table class="form-table">
    <tr>
      <th><?php _e('Property Page','wpp'); ?></th>
      <td>
        <select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug">
          <option <?php if($wp_properties['configuration']['base_slug'] == 'property') echo "SELECTED"; ?> value="property"><?php _e('Property (Default)','wpp'); ?></option>
          <?php foreach(get_pages() as $page): ?>
            <option <?php if($wp_properties['configuration']['base_slug'] == $page->post_name) echo "SELECTED"; ?> value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>



    <tr>
      <th>&nbsp;</th>
      <td>
        <ul>


          <li>
            <?php
              $insert_property_text = __('Always overwrite this page\'s content with [property_overview].','wpp');
              echo UD_UI::checkbox("name=wpp_settings[configuration][automatically_insert_overview]&label=$insert_property_text", $wp_properties['configuration']['automatically_insert_overview']);
            ?>
          </li>
        <li class="wpp_wpp_settings_configuration_do_not_override_search_result_page_row <?php if($wp_properties['configuration']['automatically_insert_overview'] == 'true') echo " hidden ";?>">
            <?php echo UD_UI::checkbox("name=wpp_settings[configuration][do_not_override_search_result_page]&label=When showing search results, don't override the page content with [property_overview].", $wp_properties['configuration']['do_not_override_search_result_page']); ?>
            <br />
            <span class="description"><?php _e('If checked, be sure to include [property_overview] somewhere in the content, or no properties will be displayed.','wpp'); ?></span>
          </li>
          <li>
            <a href="http://twincitiestech.com/wp-property/wpp-shortcode-cheatsheet/"><?php _e('View list of available shortcodes.','wpp'); ?></a>
          </li>
				</ul>
        <span class="description">
        <?php _e('The <b>property page</b> will be used to display property search results, as well as the base for property URLs.  For example, if the URL of your property page is ' . get_bloginfo('url') . '<b>/real_estate/</b>, then you properties will have the URLs of ' . get_bloginfo('url') . '/real_estate/<b>property_name</b>/','wpp'); ?>
        </span>
      </td>
    </tr>
    <tr>
      <th><?php _e('Default Phone Number','wpp'); ?></th>
      <td>
        <?php
            $phone_number_text = __('Phone number to use when a property-specific phone number is not specified.','wpp');
            echo UD_UI::input("name=phone_number&label=$phone_number_text&group=wpp_settings[configuration]&style=width: 200px;", $wp_properties['configuration']['phone_number']); ?>
      </td>
    </tr>
    <tr>
      <th><?php _e('Address Attribute','wpp'); ?></th>
      <td>
        <?php _e('Attribute to use for address:','wpp'); ?>
        <?php echo WPP_F::draw_attribute_dropdown("name=wpp_settings[configuration][address_attribute]&selected={$wp_properties['configuration']['address_attribute']}"); ?>
        <?php _e('and localize for:','wpp'); ?> <?php echo WPP_F::draw_localization_dropdown("name=wpp_settings[configuration][google_maps_localization]&selected={$wp_properties['configuration']['google_maps_localization']}"); ?>
      </td>
    </tr>

    <tr>
      <th><?php _e('Currency','wpp'); ?></th>
      <td>
        <?php
            $currency_symbol_test = __('Currency symbol.','wpp');
            echo UD_UI::input("name=currency_symbol&label=$currency_symbol_test&group=wpp_settings[configuration]&style=width: 50px;",$wp_properties['configuration']['currency_symbol']); ?>
      </td>
    </tr>


    </table>
  </div>

  <div id="tab_display">

    <table class="form-table">
    <tr>
      <th><?php _e('General Settings','wpp'); ?></th>
      <td>
        <p><?php _e('These are the general display settings','wpp'); ?></p>
          <ul>
            <li><?php
                  $default_css_text = __('Automatically include default CSS.','wpp');
                  echo UD_UI::checkbox("name=wpp_settings[configuration][autoload_css]&label=$default_css_text", $wp_properties['configuration']['autoload_css']); ?></li>
          </ul>

      </td>
    </tr>


    <tr>
      <th><?php _e('Image Sizes','wpp'); ?></th>
      <td>
        <p>
        <?php _e('Image sizes used throughout the plugin.','wpp'); ?> <br />
        <?php if(class_exists("RegenerateThumbnails")): ?>
            <?php echo sprintf(__('After adding/removing image size, be sure to <a href="%s">regenerate thumbnails</a> using the Regenerate Thumbnails plugin.','wpp'), admin_url("tools.php?page=regenerate-thumbnails")); ?><br />
        <?php endif; ?>

        <?php if(!class_exists('RegenerateThumbnails')): ?>
        <?php _e('We strongly recommend to <a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">Regenerate Thumbnails</a> plugin by Viper007Bond.','wpp'); ?>
        <?php endif; ?>
        </p>

          <table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
            <thead>
              <tr>
                <th><?php _e('Slug','wpp'); ?></th>
                <th><?php _e('Width','wpp'); ?></th>
                <th><?php _e('Height','wpp'); ?></th>
                <th>&nbsp;</th>
              </tr>
            </thead>
            <tbody>
          <?php
            $wpp_image_sizes = $wp_properties['image_sizes'];

            foreach(get_intermediate_image_sizes() as $slug):

            $slug = trim($slug);

            // We return all, including images with zero sizes, to avoid default data overriding what we save
            $image_dimensions = WPP_F::image_sizes($slug, "return_all=true");

            // Skip images w/o dimensions
            if(!$image_dimensions)
              continue;

            // Disable if WP not a WPP image size
            if(@!is_array($wpp_image_sizes[$slug]))
              $disabled = true;
            else
              $disabled = false;


            if(!$disabled):
          ?>
            <tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>">
              <td  class="wpp_slug">
                <input class="slug_setter slug"  type="text" value="<?php echo $slug; ?>" />
              </td>
              <td class="wpp_width">
                <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]" value="<?php echo $image_dimensions['width']; ?>" />
              </td>
              <td  class="wpp_height">
                <input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]" value="<?php echo $image_dimensions['height']; ?>" />
              </td>
              <td><span class="wpp_delete_row wpp_link"><?php _e('Delete','wpp') ?></span></td>
            </tr>

            <?php else: ?>
            <tr>
              <td>
                <div class="wpp_permanent_image"><?php echo $slug; ?></div>
              </td>
              <td>
                <div class="wpp_permanent_image"><?php echo $image_dimensions['width']; ?></div>
              </td>
              <td>
                <div class="wpp_permanent_image"><?php echo $image_dimensions['height']; ?></div>
              </td>
              <td>&nbsp;</td>
            </tr>

            <?php endif; ?>


          <?php endforeach; ?>

            <tfoot>
              <tr>
                <td colspan='4'><input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row','wpp') ?>" /></td>
              </tr>
            </tfoot>
            </tbody>
          </table>


       </td>
    </tr>




    <tr>
      <th><?php _e('Overview Shortcode','wpp') ?></th>
      <td>
        <p>
        <?php _e('These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />
        The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property-overview.php</b> file.  To avoid losing your changes during updates, create a <b>property-overview.php</b> file in your template directory, which will be automatically loaded.','wpp') ?>
        <ul>

          <li><?php _e('Thumbnail size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties['configuration']['property_overview']['thumbnail_size']); ?></li>
          <li><?php
                  $show_children_text = __('Show children properties.','wpp');
                  echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][show_children]&label=$show_children_text", $wp_properties['configuration']['property_overview']['show_children']); ?></li>
          <li><?php
                  $show_larger_img_text = __('Show larger image of property when image is clicked using fancybox.','wpp');
                  echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][fancybox_preview]&label=$show_larger_img_text", $wp_properties['configuration']['property_overview']['fancybox_preview']); ?>
           </li>
           <li>
            <?php
              
              echo UD_UI::checkbox("name=wpp_settings[configuration][bottom_insert_pagenation]&label=" . __('Show pagenation on bottom of results.','wpp'), $wp_properties['configuration']['bottom_insert_pagenation']);
            ?>          
          </li>
         </ul>

      </td>
    </tr>

    <tr>
      <th><?php _e('Property Page','wpp') ?></th>
      <td>
        <p><?php _e('These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />
        The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property.php</b> file.  To avoid losing your changes during updates, create a <b>property.php</b> file in your template directory, which will be automatically loaded.','wpp') ?>
        <ul>
          <li><?php
                  $display_larger_img_text = __('Display larger image, or slideshow, at the top of the property listing.','wpp');
                  echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][display_slideshow]&label=$display_larger_img_text", $wp_properties['configuration']['property_overview']['display_slideshow']); ?></li>
          <li><?php
          do_action('wpp_settings_page_property_page');?></li>
         </ul>

      </td>
    </tr>


    <tr>
      <th><?php _e('Google Maps','wpp') ?></th>
      <td>

        <ul>
          <li><?php _e('Map Thumbnail Size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . $wp_properties['configuration']['single_property_view']['map_image_type']); ?></li>
          <li><?php _e('Map Zoom Level:','wpp') ?> <?php echo UD_UI::input("name=wpp_settings[configuration][gm_zoom_level]&style=width: 30px;",$wp_properties['configuration']['gm_zoom_level']); ?></li>
        </ul>

        <p>Attributes to display in popup after a property on a map is clicked.</p>
        <ul>

          <li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][show_property_title]&label=Show Property Title", $wp_properties['configuration']['google_maps']['infobox_settings']['show_property_title']); ?></li>

          <?php foreach($wp_properties['property_stats'] as $attrib_slug => $attrib_title): ?>
          <li><?php
          $checked = (in_array($attrib_slug, $wp_properties['configuration']['google_maps']['infobox_attributes']) ? true : false);
          echo UD_UI::checkbox("id=google_maps_attributes_{$attrib_title}&name=wpp_settings[configuration][google_maps][infobox_attributes][]&label=$attrib_title&value={$attrib_slug}", $checked);
          ?></li>
          <?php endforeach; ?>

          <li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][google_maps][infobox_settings][show_direction_link]&label=Show Directions Link", $wp_properties['configuration']['google_maps']['infobox_settings']['show_direction_link']); ?></li>

        </ul>
      </td>
    </tr>

    <tr>
      <th><?php _e('Address Display','wpp') ?></th>
      <td>


        <textarea name="wpp_settings[configuration][display_address_format]" style="width: 70%;"><?php echo $wp_properties['configuration']['display_address_format']; ?></textarea>
        <br />
        <span class="description">
               <?php _e('Available tags:','wpp') ?> [street_number] [street_name], [city], [state], [state_code], [county],  [country], [zip_code].
        </span>
      </td>
    </tr>


    <tr>
      <th>
        <?php _e('Admin Overview Page','wpp') ?>
      </th>
        <td>
        <p>
          <?php _e('These settings are for the main property page on the back-end.','wpp') ?>
        </p>
        <ul>
          <li><?php _e('Thumbnail size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . $wp_properties['configuration']['admin_ui']['overview_table_thumbnail_size']); ?></li>
        </ul>
      </td>
    </tr>



    </table>
  </div>
 


  <?php
  if(is_array($wpp_plugin_settings_nav)) {
    foreach($wpp_plugin_settings_nav as $nav) {
      echo "<div id='tab_{$nav['slug']}'>";
      do_action("wpp_settings_content_{$nav['slug']}");
      echo "</div>";
    }
  }

  ?>



<?php if(count($wp_properties['available_features']) > 0): ?>
  <div id="tab_plugins">

      <table id="wpp_premium_feature_table" cellpadding="0" cellspacing="0">
        <thead>
        <tr>
          <td colspan="2" class="wpp_premium_feature_intro">
              <span class="header"><?php _e('WP-Property Premium Features','wpp') ?></span>
              <p><?php _e('Premium features will become available shortly, we are still waiting on more feedback on the core of the plugin.','wpp') ?></p>
              <?php /*<p><?php _e('If you're recently purchased a premium feature, <span id="wpp_check_premium_updates" class="wpp_link">download updates</a>.','wpp') ?></p> */ ?>
              <p id="wpp_plugins_ajax_response" class="hidden"></p>
          </td>
        </tr>
        </thead>
      <?php foreach($wp_properties['available_features'] as $plugin_slug => $plugin_data): ?>

        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][title]" value="<?php echo $plugin_data['title']; ?>" />
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][tagline]" value="<?php echo $plugin_data['tagline']; ?>" />
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][image]" value="<?php echo $plugin_data['image']; ?>" />
        <input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $plugin_data['description']; ?>" />
        
        <?php /* Do this to preserve settings after page save. */ ?>
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][disabled]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['disabled']; ?>" />
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][name]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['name']; ?>" />
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][version]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['version']; ?>" />
        <input type="hidden" name="wpp_settings[installed_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $wp_properties['installed_features'][$plugin_slug]['description']; ?>" />

        <?php $installed = (!empty($wp_properties['installed_features'][$plugin_slug]['version']) ? true : false); ?>
        <?php $active = (@$wp_properties['installed_features'][$plugin_slug]['disabled'] != 'false' ? true : false); ?>
        <tr class="wpp_premium_feature_block">

          <td valign="top" class="wpp_premium_feature_image">
            <a href="http://twincitiestech.com/plugins/wp-property/"><img src="<?php echo $plugin_data['image']; ?>" /></a>
          </td>

          <td valign="top">
            <div class="wpp_box">
            <div class="wpp_box_header">
              <strong><?php echo $plugin_data['title']; ?></strong>
              <p><?php echo $plugin_data['tagline']; ?> <a href="http://twincitiestech.com/plugins/wp-property/premium/"><?php _e('[learn more]','wpp') ?></a>
              </p>
            </div>
            <div class="wpp_box_content">
              <p><?php echo $plugin_data['description']; ?></p>

            </div>

            <div class="wpp_box_footer clearfix">
              <?php if($installed): ?>

                <div class="alignleft">
                <?php
                   $disable_text = __('Disable plugin.','wpp');
                   echo UD_UI::checkbox("name=wpp_settings[installed_features][$plugin_slug][disabled]&label=$disable_text", $wp_properties['installed_features'][$plugin_slug]['disabled']); ?>
                </div>

                <div class="alignright"><?php _e('Feature installed, using version','wpp') ?> <?php echo $wp_properties['installed_features'][$plugin_slug]['version']; ?>.</div>
              <?php else: ?>
                <?php $pr_link = 'http://twincitiestech.com/plugins/wp-property/premium/'; echo sprintf(__('Please visit <a href="%s">TwinCitiesTech.com</a> to purchase this feature.','wpp'),$pr_link); ?>
              <?php endif; ?>
            </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </table>

  </div>
<?php endif; ?>

  <div id="tab_troubleshooting">
    <div class="wpp_inner_tab">

      <div class="wpp_settings_block"><?php _e('Enter in the ID of the property you want to look up, and the class will be displayed below.','wpp') ?>
        <input type="text" id="wpp_property_class_id" />
        <input type="button" value="<?php _e('Lookup','wpp') ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
        <pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>
      </div>



      <div class="wpp_settings_block">
        <?php _e('Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.','wpp') ?>
        <input type="button" value="<?php _e('Show $wp_properties','wpp') ?>" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
        <pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"><?php print_r($wp_properties); ?></pre>        
      </div>


      <div class="wpp_settings_block">
      
       <?php _e("Restore Backup of WP-Property Configuration", 'wpp'); ?>: <input name="wpp_settings[settings_from_backup]" type="file" />
      <a href="<?php echo wp_nonce_url( "edit.php?post_type=property&page=property_settings&wpp_action=download-wpp-backup", 'download-wpp-backup'); ?>"><?php _e("Download Backup of Current WP-Property Configuration.");?></a>
      
      </div>

      <div class="wpp_settings_block">
        <?php _e('Force check of allowed premium features.','wpp');?>
         <input type="button" value="<?php _e('Check Updates','wpp');?>" id="wpp_ajax_check_plugin_updates">
      </div>
      
      <div class="wpp_settings_block">
        <?php $google_map_localizations = WPP_F::draw_localization_dropdown('return_array=true'); ?>
        Revalidate all addresses using <b><?php echo $google_map_localizations[$wp_properties['configuration']['google_maps_localization']]; ?></b> localization.
         <input type="button" value="<?php _e('Revalidate','wpp');?>" id="wpp_ajax_revalidate_all_addresses">
      </div>

      <?php do_action('wpp_settings_help_tab'); ?>
    </div>
  </div>

</div>


<br class="cb" />

<p class="wpp_save_changes_row">
<input type="submit" value="<?php _e('Save Changes','wpp');?>" class="button-primary" name="Submit">
 </p>


</form>
</div>