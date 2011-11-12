<?php

class WPP_UI {

  /**
   * Loaded if this is a property page, and child properties exist.
   *
   * @version 1.26.0
   * @author Andy Potanin <andy.potanin@twincitiestech.com>
   * @package WP-Property
   *
   */
  function child_properties($post) {

    $children = get_posts(array(
      'post_parent' => $post->ID,
      'post_type' => 'property'
    ));

    ?>
    <div class="wp-tab-panel">
    <ul>
    <?php  foreach($children as $child) {
      echo '<li><a href="'. get_edit_post_link($child->ID)  .'">' . $child->post_title . '</a></li>';
    } ?>
    </ul>
    </div>


    <?php

  }


  /**
   * Displays the primary metabox on property editing page.
   *
   *
   * @version 1.14.2
   * @author Andy Potanin <andy.potanin@twincitiestech.com>
   * @package WP-Property
   *
   */
  function page_attributes_meta_box($post) {

    $post_type_object = get_post_type_object($post->post_type);
    if ($post_type_object->hierarchical) {
      $pages = wp_dropdown_pages(array('post_type' => $post->post_type, 'exclude_tree' => $post->ID, 'selected' => $post->post_parent, 'name' => 'parent_id', 'show_option_none' => __('(no parent)', 'wpp'), 'sort_column' => 'menu_order, post_title', 'echo' => 0));
      if (!empty($pages)) { ?>

        <p><strong><?php _e('Parent', 'wpp') ?></strong></p>
        <label class="screen-reader-text" for="parent_id"><?php _e('Parent', 'wpp') ?></label>
        <?php echo $pages; ?>
        <?php
      } // end empty pages check
    } // end hierarchical check.
    if ('page' == $post->post_type && 0 != count(get_page_templates())) {
      $template = !empty($post->page_template) ? $post->page_template : false;
      ?>
      <p><strong><?php _e('Template', 'wpp') ?></strong></p>
      <label class="screen-reader-text" for="page_template"><?php _e('Page Template', 'wpp') ?></label><select name="page_template" id="page_template">
        <option value='default'><?php _e('Default Template', 'wpp'); ?></option>
        <?php page_template_dropdown($template); ?>
      </select>
    <?php } ?>
    <p><strong><?php _e('Order', 'wpp') ?></strong></p>
    <p><label class="screen-reader-text" for="menu_order"><?php _e('Order', 'wpp') ?></label><input name="menu_order" type="text" size="4" id="menu_order" value="<?php echo esc_attr($post->menu_order) ?>" /></p>
    <p><?php if ('page' == $post->post_type)
      _e('Need help? Use the Help tab in the upper right of your screen.', 'wpp'); ?></p>
    <?php
  }

  /*
   * Prints Property Atrributes Metabox
   * on Property Edit Page
   *
   * @param object $object. Property
   * @param array $attrs. Metabox attributes
   */
  function metabox_meta($object, $attrs) {
    global $wp_properties, $wpdb;
    static $loaded = false;

    $property = WPP_F::get_property($object->ID);

    $instance = $attrs['id'];
    $stats_group = (!empty($attrs['args']['group']) ? $attrs['args']['group'] : false);

    $disabled_attributes = (array) $wp_properties['geo_type_attributes'];
    $property_stats = $wp_properties['property_stats'];
    $stat_keys = array_keys($property_stats);

    //** If an attribute with 'property_type' slug exists, we tweak UI *'
    if(in_array('property_type', $stat_keys)) {
      $property_type_in_attributes = true;
    } else {
      $property_type_in_attributes = false;
    }

    $this_property_type = $property['property_type'];

    //* Set default property type */
    if (empty($this_property_type) && empty($property['post_name'])) {
      $this_property_type = WPP_F::get_most_common_property_type();
    }

    //** Check for current property type if it is deleted */
    if(is_array($wp_properties['property_types']) && isset($property['property_type']) && !in_array($property['property_type'], array_keys($wp_properties['property_types']))) {
      $wp_properties['property_types'][$property['property_type']] =  WPP_UD_F::de_slug($property['property_type']);
      $wp_properties['descriptions']['property_type'] = '<span class="attention">' . sprintf(__('<strong>Warning!</strong> The %1s property type has been deleted.', 'wpp'), $wp_properties['property_types'][$property['property_type']]) . '</span>';
    }

    ?>

    <?php if (!$loaded) : ?>
    <style type="text/css">
      <?php if ($wp_properties['configuration']['completely_hide_hidden_attributes_in_admin_ui'] == 'true'): ?>
      .disabled_row {display:none;}
      <?php endif; ?>
    </style>

    <script type="text/javascript">
      jQuery(document).ready(function() {

        //* Hack for CSS. View of the current metabox */
        jQuery('.inside table.property_meta').parents('.inside').css({
          'margin':'0',
          'padding':'0'
        });

        // Done with PHP but in case of page reloads
        wpp_toggle_attributes();

        /*
         * Display prefill values.
         * Hide "Show common values" link.
         * Display "Cancel" button
         */
        jQuery(".wpp_show_prefill_values").click(function() {
          var parent_cell = jQuery(this).parents('.wpp_attribute_cell');
          jQuery(this).hide();
          jQuery(this).parent().children('.wpp_prefill_attribute').show();
          jQuery('.wpp_show_prefill_values_cancel', parent_cell).show();
        });

        /*
         * Cancel displaying prefill values.
         * Hide "Cancel" button
         * Hide all pre-filled values
         * Show "Show common values" link.
         */
        jQuery(".wpp_show_prefill_values_cancel").click(function() {
          jQuery(this).hide();
          var parent_cell = jQuery(this).parents('.wpp_attribute_cell');
          jQuery('.wpp_prefill_attribute', parent_cell).hide();
          jQuery('.wpp_show_prefill_values', parent_cell).show();
        });

        jQuery(".wpp_attribute_row input.text-input.wpp_numeric, .wpp_attribute_row input.text-input.wpp_currency").change(function() {
            this_value = jQuery(this).val();
            jQuery(this).val(this_value.replace(/[^\d|\.]/g,''));
        });

        jQuery(".wpp_prefill_attribute").click(function() {
          var value = jQuery(this).text();
          var parent_cell = jQuery(this).parents('.wpp_attribute_cell');
          jQuery('input', parent_cell).val(value);;
          jQuery('.wpp_prefill_attribute', parent_cell).hide();
          jQuery('.wpp_show_prefill_values', parent_cell).show();
        });

        // Setup toggling settings
        jQuery("#wpp_meta_property_type").change(function() {
          wpp_toggle_attributes();
        });

        function wpp_toggle_attributes() {
          var property_type = jQuery("#wpp_meta_property_type").val();

          if(property_type == "") {
            return;
          }

          <?php if (count($wp_properties['hidden_attributes']) < 1) { ?>
          return;
          <?php } else { ?>
          // Show all fields
          jQuery(".wpp_attribute_row").removeClass('disabled_row');
          switch(property_type) {
            <?php if (is_array($wp_properties['hidden_attributes'])) : ?>
            <?php foreach ($wp_properties['hidden_attributes'] as $property_type => $hidden_values): ?>
            case '<?php echo $property_type; ?>':
              <?php if (is_array($hidden_values))  { ?>
                <?php foreach ($hidden_values as $value) { ?>
                jQuery(".wpp_attribute_row_<?php echo $value; ?>").addClass('disabled_row');
                <?php } ?>
              <?php } ?>
              break;
            <?php endforeach; ?>
            <?php endif; ?>
          }

          /* Determine if all attributes of the metabox are hidden
           * and Show/Hide metabox depending on it
           */
          jQuery('table.property_meta').each(function(ti,te){
            var s = false;
            jQuery('tr', te).each(function(ri,re){
              if(!jQuery(re).hasClass('disabled_row')) {
                s = true;
              }
            });
            var b = jQuery(te).parents('.postbox');
            if(!b.length > 0) {
              b = jQuery(te).parents('.postbox_closed');
            }
            if(s) {
              b.show();
            } else {
              b.hide();
            }
          });
          <?php } ?>
        }

      });
    </script>
    <?php $loaded = true; ?>
    <?php endif; ?>

    <table class="widefat property_meta">

      <?php //* 'Falls Under' field should be shown only in 'General Information' metabox */ ?>
      <?php if($instance == 'wpp_property_meta') : ?>
        <?php //** Do not do page dropdown when there are a lot of properties */ ?>
        <?php $property_count = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'property' AND post_status = 'publish' "); ?>
        <?php if($property_count < 200) : ?>
          <?php
          $pages = wp_dropdown_pages(array(
            'post_type' => 'property',
            'exclude_tree' => $object->ID,
            'selected' => $object->post_parent,
            'name' => 'parent_id',
            'show_option_none' => __('(no parent)', 'wpp'),
            'sort_column' => 'menu_order, post_title',
            'echo' => 0
          ));
          ?>
          <?php if (!empty($pages)) : ?>
          <tr class="wpp_attribute_row_parent wpp_attribute_row <?php if (is_array($wp_properties['hidden_attributes'][$property['property_type']]) && in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) {  echo 'disabled_row;'; } ?>">
            <th><?php _e('Falls Under', 'wpp'); ?></th>
            <td><?php echo $pages; ?></td>
          </tr>
          <?php endif; ?>
        <?php else : ?>
          <tr class="wpp_attribute_row_parent wpp_attribute_row <?php if (is_array($wp_properties['hidden_attributes'][$property['property_type']]) && in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) {  echo 'disabled_row;'; } ?>">
            <th><?php _e('Falls Under', 'wpp'); ?></th>
            <td>
              <input name="parent_id" value="<?php echo $property['parent_id']; ?>" />
              <span class="description"><?php _e('ID of parent property', 'wpp'); ?></span>
            </td>
          </tr>
        <?php endif; ?>
        <?php if(!$property_type_in_attributes) { WPP_UI::property_type_selector($property); } ?>
      <?php endif; ?>

      <?php

      //** Detect attributes that were taken from a range of child properties. */
      $upwards_inherited_attributes = is_array($property['system']['upwards_inherited_attributes']) ? $property['system']['upwards_inherited_attributes'] : array();

      foreach ($property_stats as $slug => $label):

        $attribute_data = WPP_F::get_attribute_data($slug);
        
        $attribute_description = array();
        
        $attribute_description[] = ($attribute_data['numeric'] || $attribute_data['currency'] ? __('Numbers only.', 'wpp') : ''); 
        $attribute_description[] = (!empty($wp_properties['descriptions'][$slug]) ? $wp_properties['descriptions'][$slug]: '');            
            
        //* Setup row classes */
        $row_classes = array('wpp_attribute_row');
        $row_classes[] = "wpp_attribute_row_{$slug}";

        if (is_array($wp_properties['hidden_attributes'][$property['property_type']]) && in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) {
          $row_classes[] = 'disabled_row';
        }
        if (in_array($slug, (array) $wp_properties['hidden_frontend_attributes'])) {
          $row_classes[] = 'wpp_hidden_frontend_attribute';
        }

        //** Make note of attributes that consist of ranges upwards inherited from child properties */
        if(in_array($slug, $upwards_inherited_attributes)) {
          $row_classes[] = 'wpp_upwards_inherited_attributes';        
          $disabled_attributes[] = $slug;
          $attribute_description = array(__('Values aggregated from child properties.','wpp'));
        }
  
        //* Determine if attribute is assigned to group */
        $gslug = false;
        $group = false;
        
        if(!empty($wp_properties['property_stats_groups'][$slug])) {
          $gslug = $wp_properties['property_stats_groups'][$slug];
          $group = $wp_properties['property_groups'][$gslug];
        }

        if( $group && $instance == "wpp_property_meta") {
          continue;
        } else if( $instance != "wpp_property_meta" && $instance != $gslug) {
          continue;
        }

        //** Render Property Type selection box here if it exists as an attribute */
        if($property_type_in_attributes && $slug == 'property_type') {
          WPP_UI::property_type_selector($property);
          continue;
        }

        ?>

        <tr class="<?php echo implode(' ', $row_classes); ?>">
          <th><label for="wpp_meta_<?php echo $slug; ?>"><?php echo $label; ?></label></th>
          <td class="wpp_attribute_cell">
            <span class="disabled_message"><?php echo sprintf(__('Editing %s is disabled, it may be inherited.', 'wpp'), $label); ?></span>
            <?php if($attribute_data['currency'] && $wp_properties['configuration']['currency_symbol_placement'] == 'before') { ?>
              <span class="currency"><?php echo $wp_properties['configuration']['currency_symbol']; ?></span>
            <?php } ?>

            <?php
            $value = $property[$slug];
            if ($value === true) {
              $value = 'true';
            }
            ?>

            <?php //* Determine if the current attribute is geo type, it will be disabled */ ?>
            <?php if(in_array($slug, (array) $disabled_attributes))  : ?>
            <?php echo apply_filters("wpp_property_stats_input_$slug", "<input type='text' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]' class='text-input wpp_field_disabled {$attribute_data['ui_class']}' value='{$value}' disabled='disabled' />", $slug, $property); ?>
            <?php else : ?>
              <?php //* Check if attribute has predefine values */ ?>
              <?php if (!empty($wp_properties['predefined_values'][$slug])) : ?>
                <?php $predefined_values = str_replace(array(', ', ' ,'), array(',', ','), trim($wp_properties['predefined_values'][$slug])); ?>
                <?php if ($predefined_values == 'true,false' || $predefined_values == 'false,true') : ?>
                  <?php echo apply_filters("wpp_property_stats_input_$slug", "<input type='hidden' name='wpp_data[meta][{$slug}]' value='false' /><input " . checked($value, 'true', false) . "type='checkbox' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]' value='true' {$disabled} /> <label for='wpp_meta_{$slug}'>" . __('Enable.', 'wpp') . "</label>", $slug, $property); ?>
                <?php else : ?>
                  <?php foreach (explode(',', $predefined_values) as $option) : ?>
                    <?php $predefined_options[$slug][] = "<option " . selected(esc_attr(trim($value)), esc_attr(trim(str_replace('-', '&ndash;', $option))), false) . " value='" . esc_attr($option) . "'>" . trim(esc_attr($option)) . "</option>"; ?>
                  <?php endforeach; ?>
                  <?php echo apply_filters("wpp_property_stats_input_$slug", "<select id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]'><option value=''> - </option>" . implode($predefined_options[$slug]) . "</select>", $slug, $property); ?>
                <?php endif; ?>
              <?php else : ?>
                <?php echo apply_filters("wpp_property_stats_input_$slug", "<input type='text' id='wpp_meta_{$slug}' name='wpp_data[meta][{$slug}]' class='text-input {$attribute_data[ui_class]}' value=\"{$value}\" />", $slug, $property); ?>
              <?php endif; ?>
            <?php endif; ?>

            <?php if($attribute_data['currency'] && $wp_properties['configuration']['currency_symbol_placement'] == 'after') : ?>
              <?php echo $wp_properties['configuration']['currency_symbol']; ?>
            <?php endif; ?>

            <span class="description">
              <?php echo implode('', $attribute_description); ?>
            </span>

            <?php do_action('wpp_ui_after_attribute_' . $slug, $object->ID); ?>

          </td>
        </tr>
      <?php endforeach; ?>

      <?php //* 'Property Meta' fields should be shown only in 'General Information' metabox */ ?>
      <?php if($instance == 'wpp_property_meta') : ?>
        <?php $property_meta = $wp_properties['property_meta']; ?>
        <?php foreach ($property_meta as $slug => $label): ?>
          <tr class="wpp_attribute_row wpp_attribute_row_<?php echo $slug; ?> <?php if (is_array($wp_properties['hidden_attributes'][$property['property_type']]) && in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) echo 'disabled_row;'; ?>">
            <th><label for="wpp_data_meta_<?php echo $slug; ?>"><?php echo $label; ?></label></th>
            <td>
              <span class="disabled_message"><?php echo sprintf(__('Editing %s is disabled, it may be inherited.', 'wpp'), $label); ?></span>
              <textarea id="wpp_data_meta_<?php echo $slug; ?>" name="wpp_data[meta][<?php echo $slug; ?>]"><?php echo preg_replace('%&ndash;|�%i', '-', get_post_meta($object->ID, $slug, true)); ?></textarea>
              <?php if (!empty($wp_properties['descriptions'][$slug])): ?>
                <span class="description"><?php echo $wp_properties['descriptions'][$slug]; ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>

    </table>
    <?php
  }

  function metabox_property_filter($wp_list_table) {

    global $wp_properties;
    ?>
    <div class="misc-pub-section">

      <?php $wp_list_table->search_box('Search', 'property'); ?>

      <?php $filters = WPP_F::get_search_filters(); ?>

      <?php
      if (!empty($filters)) {
        foreach ($filters as $key => $filter) {
          // If there are available values
          if (!empty($filter['values']) && count($filter['values']) > 1 ) {
            ?>

            <ul class="wpp_overview_filters <?php echo $key; ?>">
              <li class="wpp_filter_section_title"><?php echo $filter['label']; ?><a class="wpp_filter_show"><?php echo $key == 'post_status' ? __('Hide', 'wpp') : __('Show', 'wpp') ?></a></li>
              <li class="all wpp_checkbox_filter" <?php echo $key == 'post_status' ? 'style="display:block;"' : '' ?> >

                <?php
                switch ($filter['type']) {

                  default: break;

                  case 'multi_checkbox':
                    ?>

                    <ul class="wpp_multi_checkbox">
                      <?php
                      if (is_array($filter['values'])) {
                        foreach ($filter['values'] as $value => $label) {
                          $unique_id = rand(10000, 99999);
                          ?>
                          <li>
                            <input name="wpp_search[<?php echo $key; ?>][]" id="wpp_attribute_checkbox_<?php echo $unique_id; ?>" type="checkbox" value="<?php echo $value; ?>" />
                            <label for="wpp_attribute_checkbox_<?php echo $unique_id; ?>"><?php echo $label; ?></label>
                          </li>
                        <?php }
                      } ?>

                    </ul>

                    <?php break;

                  case 'dropdown': $unique_id = rand(10000, 99999); ?>

                    <select id="wpp_attribute_dropdown_<?php echo $unique_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $key; ?>" name="wpp_search[<?php echo $key; ?>]" >

                      <?php
                      if (is_array($filter['values'])) {
                        foreach ($filter['values'] as $value => $label) {
                          ?>
                          <option value="<?php echo esc_attr($value); ?>" <?php echo $value == $filter['default'] ? 'selected="selected"' : '' ?> >
                            <?php
                            echo $label;
                            ?>
                          </option>
                <?php }
              } ?>
                    </select>

                    <?php break;

                  case 'radio': ?>
                    <ul>
                      <?php
                      if (is_array($filter['values'])) {

                        foreach ($filter['values'] as $value => $label) {
                          ?>
                          <li>
                            <input id="radio_filter_<?php echo $value; ?>" type="radio" value="<?php echo esc_attr($value); ?>" name="wpp_search[<?php echo $key; ?>]" <?php echo ($value == $filter['default'] ? 'checked="checked"' : ''); ?> />
                            <label for="radio_filter_<?php echo $value; ?>"><?php echo $label; ?></label>
                          </li>

                <?php } break;
              }
          } ?>
                </ul>

              </li></ul>


            <?php
          }
        }
      }

      do_action('wpi_invoice_list_filter');
      ?>

    </div>

    <div class="major-publishing-actions">
      <div class="publishing-action">
    <?php submit_button(__('Filter Results'), 'button', false, false, array('id' => 'search-submit')); ?>
      </div>
      <br class='clear' />
    </div>

    <?php
  }


  function property_type_selector($property) {
    global $wp_properties;

    if(count($wp_properties['property_types']) > 1) { ?>
      <tr class="wpp_attribute_row_type wpp_attribute_row <?php if (is_array($wp_properties['hidden_attributes'][$property['property_type']]) && in_array('type', $wp_properties['hidden_attributes'][$property['property_type']])) echo 'disabled_row;'; ?>">
        <th><?php _e('Property Type', 'wpp'); ?></th>
        <td>
          <?php //* Get property types */ ?>
          <select id="wpp_meta_property_type" name="wpp_data[meta][property_type]" id="property_type">
            <option value=""></option>
            <?php foreach ($wp_properties['property_types'] as $slug => $label) { ?>
            <option <?php selected(strtolower($property['property_type']), strtolower($slug)); ?> value="<?php echo $slug; ?>"><?php echo $label; ?></option>
            <?php } ?>
          </select>
          <?php if (!empty($wp_properties['descriptions']['property_type'])) { ?>
          <span class="description"><?php echo $wp_properties['descriptions']['property_type']; ?></span>
          <?php } ?>
        </td>
      </tr>
    <?php } else { ?>
      <input type="hidden" id="wpp_meta_property_type" name="wpp_data[meta][property_type]" id="property_type" value="<?php echo strtolower($property['property_type']); ?>" />
    <?php }

  }

}
