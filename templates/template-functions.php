<?php
/**
 * Functions to be used in templates.  Overrided by anything in template functions.php
 *
 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 *
 * @version 1.4
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/



if(!function_exists('is_property_overview_page')):

  /**
   * Figures out if current page is the property overview page
   *
   * @since 1.10
   *
    */
   function is_property_overview_page() {
    global $wp_query;


      if ( ! isset( $wp_query ) ) {
        _doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1' );
        return false;
      }


    return $wp_query->is_property_overview;
  }

endif;



if(!function_exists('prepare_property_for_display')):
  /**
   * Runs all filters through property variables
   *
   * @since 1.4
   *
    */
   function prepare_property_for_display($property) {

    if(empty($property))
      return;

    $property_id = $property[ID];

    if($cache_property = wp_cache_get('property_for_display_' . $property_id))
      return $cache_property;


    foreach($property as $meta_key => $attribute)
       $property[$meta_key] = apply_filters("wpp_stat_filter_$meta_key",$attribute);


    // Go through children properties
    if(is_array($property[children]))
      foreach($property[children] as $child => $child_data)
        $property[children][$child] = prepare_property_for_display($child_data);


    wp_cache_add('property_for_display_' . $property_id, $property);

    return $property;
  }

endif;

if(!function_exists('property_slideshow')):
  /**
   * Returns property slideshow images, or single image if plugin not installed
   *
   * @since 1.0
   *
    */
   function property_slideshow($args = "") {
    global $wp_properties, $post;


    $defaults = array('force_single' => false, 'return' => false);
    $args = wp_parse_args( $args, $defaults );

    if($wp_properties[configuration][property_overview][display_slideshow] == 'false')
      return;


    ob_start();

      // Display slideshow if premium plugin exists and the property isn't set to hide slideshow
      if($wp_properties[plugins][slideshow][status] == 'enabled' && !$post->disable_slideshow) {
        wpp_slideshow::display_property_slideshow(wpp_slideshow::get_property_slideshow_images($post->ID));
      } else {
        // Get slideshow image type for featured image

        if(!empty($post->slideshow)) {
          echo "<a href='{$post->featured_image_url}' class='fancybox_image'>";
          echo "<img src='{$post->slideshow}' alt='{$post->featured_image_title}' />";
          echo "</a>";
        }
      }




    $content = ob_get_contents();
    ob_end_clean();

    if(empty($content))
      return false;

    if($return)
      return $content;

    echo $content;


  }
endif; // property_slideshow


/*
  Extends get_post by dumping all metadata into array
*/
if(!function_exists('get_property')):
  function get_property($id, $args = "") {
    if($id && is_numeric($id))
      return WPP_F::get_property($id, $args);
  }
endif;

if(!function_exists('the_tagline')):
   function the_tagline($before = '', $after = '', $echo = true) {
    global $post;

    $content = $post->tagline;


    if ( strlen($content) == 0 )
      return;

    $content = $before . $content . $after;

    if ( $echo )
      echo $content;
    else
      return $content;

  }
endif;

if(!function_exists('get_features')):
  function get_features($args = '', $property = false) {
    global $post;

    if(is_array($property))
      $post = (object) $property;

    if(!$property)
      $property = $post;

    $defaults = array('type' => 'property_feature', 'format' => 'comma', 'links' => true);
    $args = wp_parse_args( $args, $defaults );

    $features = get_the_terms($property->ID, $args['type']);

    $features_html = array();

    if($features) {
    foreach ($features as $feature)

      if($links)
        array_push($features_html, '<a href="' . get_term_link($feature->slug, $args['type']) . '">' . $feature->name . '</a>');
      else
        array_push($features_html, $feature->name);

      if($args[format] == 'comma')
        echo implode($features_html, ", ");

      if($args[format] == 'array')
        return $features_html;

      if($args[format] == 'count')
        return (count($features) > 0 ? count($features) : false);

      if($args[format] == 'list')
        echo "<li>" . implode($features_html, "</li><li>") . "</li>";

    }


  }
endif;

if(!function_exists('draw_stats')):

  /**
   * Returns printable array of property stats
    *
    * @since 1.11
   *  @args: exclude, return_blank, make_link
    */
  function draw_stats($args = false, $property = false){
    global $wp_properties, $post;

    $defaults = array( );
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );


    if(is_array($property))
      $property = (object) $property;

    if(!$property)
      $property = $post;


    $defaults = array(
      'display' => 'dl_list'
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $stats = WPP_F::get_stat_values_and_labels($property, $args);

    if(!$stats)
      return;

        $alt = 'alt';

        foreach($stats as $label => $value){
            $labels_to_keys = array_flip($wp_properties['property_stats']);
            if(empty($value))
               return;

            $tag = $labels_to_keys[$label];

      $value =  trim(apply_filters("wpp_stat_filter_$tag", $value, $property));

      // Skip blank values
      if($return_blank == 'false' && empty($value))
        continue;

      // Make URLs into clickable links
      if($make_link == 'true' && WPP_F::isURL($value))
        $value = "<a href='{$value}' title='{$label}'>{$value}</a>";

            if ($alt == '' )
                $alt = "alt";
            else
                $alt = '';

      switch($display) {

        case 'dl_list':
      ?>
      <dt class="wpp_stat_dt_<?php echo $tag; ?>"><?php echo $label; ?></dt>
      <dd class="wpp_stat_dd_<?php echo $tag; ?> <?php echo $alt ?>"><?php echo $value; ?>&nbsp;</dd>
          <?php
        break;

        case 'list':
          ?>
          <li class="wpp_stat_plain_list_<?php echo $tag; ?> <?php echo $alt ?>">
            <span class="attribute"><?php echo $label; ?>:</span>
            <span class="value"><?php echo $value; ?>&nbsp;</span>
          </li>
        <?php

        case 'plain_list':
          ?>
          <span class="attribute"><?php echo $label; ?>:</span>
          <span class="value"><?php echo $value; ?>&nbsp;</span>
          <br />
        <?php

        break;

        case 'array':
          $return_array[$label] = $value;

        break;


      }

    }

    if($display == 'array')
      return $return_array;

  }
endif;


if(!function_exists('draw_featured_properties')):
  function draw_featured_properties() {
  ?>


      <?php
    $featured_array = WPP_F::get_properties("featured=true&property_type=all");

      if(is_array($featured_array)):
      foreach($featured_array as $featured):

        unset($this_property);
        $this_property = WPP_F::get_property($featured->ID);

        ?>
        <div class="apartment_entry clearfix" style="clear:both;margin-bottom:15px;">

          <a href="<?php echo $this_property[permalink]; ?>">
            <img src="<?php echo $this_property[sidebar_gallery_thumb];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property[post_title], $this_property[location], $this_property[price]); ?>" />
          </a>

          <ul class="sidebar_properties">
            <li><span><?php _e('Price:','wpp'); ?></span> $ <?php echo  $this_property[price]; ?></li>
            <li><span><?php _e('Bed(s):','wpp'); ?></span> <?php echo  $this_property[bedrooms]; ?></li>
            <li><span><?php _e('Bath(s):','wpp'); ?></span> <?php echo  $this_property[bathrooms]; ?></li>
            <li><span><?php _e('Square Ft:','wpp'); ?></span> <?php echo  $this_property[area]; ?></li>
          </ul>
         </div>
        <?php
      endforeach;
    endif;


  }
endif;

  /**
   * Draws search form
   *
   *
   * @return array|$wp_properties
   * @since 0.57
   * @version 1.14
   *
    */
if(!function_exists('draw_property_search_form')):
  function draw_property_search_form($search_attributes = false, $searchable_property_types = false, $per_page = false, $instance_id = false, $cache = true) {
        global $wp_properties;

        $search_values = array();
        $property_type_flag = false;

        if(!$search_attributes)
            return;

        if (!empty($search_attributes) && !empty($searchable_property_types))
            $search_values = WPP_F::get_search_values($search_attributes, $searchable_property_types, $cache, $instance_id);

            if($search_values['property_type'])
                unset ($search_values['property_type']);

            if(array_key_exists('property_type', array_fill_keys($search_attributes, 1)) && is_array($searchable_property_types) && count($searchable_property_types) > 1 ) {
                $spt = array_fill_keys($searchable_property_types, 1);
                if(!empty($wp_properties['property_types'])) {
                    foreach ($wp_properties['property_types'] as $key => $value) {
                        if(array_key_exists($key, $spt)) {
                            $search_values['property_type'][$key] = $value;
                        }
                    }
                    if(count($search_values['property_type']) <= 1) {
                        unset ($search_values['property_type']);
                    }
                }
            }

        ?>

        <form action="<?php echo  UD_F::base_url($wp_properties['configuration']['base_slug']); ?>" method="post">

        <?php
        if(is_array($searchable_property_types) && !array_key_exists('property_type', array_fill_keys($search_attributes, 1))) {
            foreach($searchable_property_types as $this_property) {
                echo '<input type="hidden" name="wpp_search[property_type][]" value="'. $this_property .'" />';
            }
        }
    //echo "<pre>";print_r($search_values);    print_r($_REQUEST['wpp_search']);echo "</pre>";
        ?>
            <ul class="wpp_search_elements">
                <?php if(is_array($search_attributes)) foreach($search_attributes as $attrib) {
                    // Don't display search attributes that have no values
                    if(!isset($search_values[$attrib]))
                        continue;
            
          $random_element_id = 'wpp_search_element_' . rand(1000,9999);
          $label = (empty($wp_properties['property_stats'][$attrib]) ? ucwords($attrib) : $wp_properties['property_stats'][$attrib])
          
                    ?>
                    <li class="seach_attribute_<?php echo $attrib; ?> <?php echo ((!empty($wp_properties['searchable_attr_fields'][$attrib]) && $wp_properties['searchable_attr_fields'][$attrib] == 'checkbox') ? 'wpp-checkbox-el' : ''); ?>">

            <?php ob_start(); ?>

                        <?php if($attrib == 'property_type') : ?>
                        <label for="<?php echo $random_element_id; ?>" class="wpp_search_label wpp_search_label_<?php echo $attrib; ?>"><?php _e('Type:', 'wpp'); ?></label>
                        <?php else : ?>
                        <label for="<?php echo $random_element_id; ?>" class="wpp_search_label wpp_search_label_<?php echo $attrib; ?>"><?php echo $label; ?>:</label>
                        <?php endif; ?>
                        <?php if(!empty($wp_properties['searchable_attr_fields'][$attrib])) : ?>
                            <?php switch($wp_properties['searchable_attr_fields'][$attrib]) {
                                case 'input' :
                                    ?>
                                    <input id="<?php echo $random_element_id; ?>" name="wpp_search[<?php echo $attrib; ?>]" value="<?php echo $_REQUEST['wpp_search'][$attrib]; ?>" type="text" />
                                   <?php
                                    break;
                                case 'range_input':
                                    ?>
                                    <input id="<?php echo $random_element_id; ?>" class="wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?>" type="text" name="wpp_search[<?php  echo $attrib; ?>][min]" value="<?php echo $_REQUEST['wpp_search'][$attrib]['min']; ?>" /> -
                                    <input class="wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?>"  type="text" name="wpp_search[<?php echo $attrib; ?>][max]" value="<?php echo $_REQUEST['wpp_search'][$attrib]['max']; ?>" />
                                    <?php
                                    break;
                                case 'range_dropdown':
                                    ?>
                                    <?php $grouped_values = group_search_values($search_values[$attrib]); ?>
                                    <select id="<?php echo $random_element_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?>" name="wpp_search[<?php echo $attrib; ?>][min]" >

                  <option value="-1"><?php _e( 'Any' ,'wpp' ) ?></option>

                  <?php foreach($grouped_values as $value) { ?>
                                        <option value='<?php echo (int)$value; ?>' <?php if($_REQUEST['wpp_search'][$attrib]['min'] == $value) echo " selected='true' "; ?>>
                                            <?php echo apply_filters("wpp_stat_filter_$attrib", $value); ?> +
                                        </option>
                                    <?php } ?>
                                    </select>
                                    <?php
                  break;
                
                                case 'dropdown':
                                    //$req_attr = htmlspecialchars((stripslashes($_REQUEST['wpp_search'][$attrib])), ENT_QUOTES);
                  $req_attr = htmlspecialchars(stripslashes($_REQUEST['wpp_search'][$attrib]), ENT_QUOTES);

                                    ?>
                                    <select id="<?php echo $random_element_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?>" name="wpp_search[<?php echo $attrib; ?>]" >

                  <?php if( !isset( $_POST['wpp_search'][$attrib] ) || $_POST['wpp_search'][$attrib] == "-1" ) { ?>

                  <option value="-1"><?php _e( 'Any' ,'wpp' ) ?></option>

                  <?php  } else { ?>

                  <?php
                  // What is the point of this??
                  /* <option value="<?php _e( $_POST['wpp_search'][$attrib] ,'wpp' ) ?>"><?php _e( $_POST['wpp_search'][$attrib], 'wpp' ) ?></option> */
                  ?>
                  <option value="-1"><?php _e( 'Any' ,'wpp' ) ?></option>

                  <?php } ?>


                                    <?php foreach( $search_values[$attrib] as $value ) { ?>
                  <?php // echo 'value: ' . $value . '|req_attr: '  . $req_attr; ?>
                                        <option value='<?php echo $value; ?>' <?php selected($req_attr,$value); ?>>
                                            <?php echo WPP_F::decode_mysql_output( apply_filters("wpp_stat_filter_$attrib", $value) ); ?>
                                        </option>
                                    <?php } ?>
                                    </select>
                                    <?php
                                    break;
                                case 'checkbox':
                                    ?>
                                    <input id="<?php echo $random_element_id; ?>" type="checkbox" name="wpp_search[<?php echo $attrib; ?>][checked]" <?php checked($_REQUEST['wpp_search'][$attrib]['checked'], 'on'); ?> />
                                    <?php
                                    break;
                            } ?>

                        <?php else: ?>

                            <?php
                            // Determine if attribute is a numeric range
                            if(WPP_F::is_numeric_range($search_values[$attrib])) {
                            ?>
                                <input class="wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?>" type="text" name="wpp_search[<?php  echo $attrib; ?>][min]" value="<?php echo $_REQUEST['wpp_search'][$attrib]['min']; ?>" /> -
                                <input class="wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?>"  type="text" name="wpp_search[<?php echo $attrib; ?>][max]" value="<?php echo $_REQUEST['wpp_search'][$attrib]['max']; ?>" />
                            <?php
                            }  else { /* Not a numeric range */ ?>
                                <select id="<?php echo $random_element_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?>" name="wpp_search[<?php echo $attrib; ?>]" >
                                    <option value="<?php echo (($attrib == 'property_type' && is_array($search_values[$attrib])) ? implode(',',(array_flip($search_values[$attrib]))) : '-1' ); ?>"><?php _e('Any','wpp') ?></option>
                                    <?php foreach($search_values[$attrib] as $key => $value) { ?>
                                        <option value='<?php echo (($attrib=='property_type')?$key:$value); ?>' <?php if($_REQUEST['wpp_search'][$attrib] == (($attrib=='property_type')?$key:$value)) echo " selected='true' "; ?>>
                                            <?php echo WPP_F::decode_mysql_output( apply_filters("wpp_stat_filter_$attrib", $value) ); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            <?php } ?>

                        <?php endif; ?>
            <?php $this_field = ob_get_contents(); ?>

            <?php ob_end_clean(); ?>
            <?php echo apply_filters('wpp_search_form_field_'. $attrib, $this_field, $attrib, $label, $_REQUEST['wpp_search'][$attrib], $wp_properties['searchable_attr_fields'][$attrib], $random_element_id); ?>
                    </li>
                <?php } ?>
                <li class="submit"><input type="submit" class="wpp_search_button submit" value="<?php _e('Search','wpp') ?>" /></li>
            </ul>
            <?php if($per_page) echo '<input type="hidden" name="wpp_search[pagi]" value="0--'. $per_page .'" />'; ?>
        </form>
    <?php }

endif;



?>
