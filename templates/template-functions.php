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



if(!function_exists('property_overview_image')) {
  /**
   * Eulated have_posts
   *
   * @since 1.17.3
   */
  function property_overview_image($args = '') {
    global $wpp_query, $property;
    
    $defaults = array(
      'return' => 'false'
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    if($wpp_query['fancybox_preview'] == 'true') {
      $thumbnail_link = $property['featured_image_url'];
      $link_class = "fancybox_image";
    }
    
    $thumbnail_size = $wpp_query['thumbnail_size'];
    
    $image = wpp_get_image_link($property['featured_image'], $thumbnail_size, array('return'=>'array'));
    
    if(!empty($image)) {
      ob_start();
      ?>
      <div class="property_image">
        <a href="<?php echo $thumbnail_link; ?>" title="<?php echo $property['post_title'] . ($property['parent_title'] ? __(' of ', 'wpp') . $property['parent_title'] : "");?>"  class="property_overview_thumb property_overview_thumb_<?php echo $thumbnail_size; ?> <?php echo $link_class; ?>" rel="properties" >
          <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" src="<?php echo $image['link']; ?>" alt="<?php echo $property['post_title'];?>" />
        </a>
      </div>
      <?php
      $html = ob_get_contents();
      ob_end_clean();
    } else {
      $html = '';
    }
    
    if($args['return'] == 'true') {
      return $html;
    } else {
      echo $html;
    }
  }
}


if(!function_exists('returned_properties')) {
  /**
   * Gets returned property loop, and loads the property objects
   *
   * @since 1.17.3
   */
   function returned_properties() {
    global $wpp_query;

    foreach($wpp_query['properties']['results'] as $property_id) {
      $properties[] = prepare_property_for_display($property_id);
    }

     return $properties;
  }
}


if(!function_exists('have_properties')) {
  /**
   * Eulated have_posts
   *
   * @since 1.17.3
   */
   function have_properties() {
    global $wpp_query;

    if($wpp_query['properties']) {
      return true;
    }
    return false;
   }
}


if(!function_exists('wpi_draw_pagination')):

  /**
   * Figures out if current page is the property overview page
   *
   * This function could be called multiple times for the same shortcode, and numerous times on the page.
   * therefore, all JS in here has to take that into account and not perform same function twice.
   *
   * @since 1.10
   *
   */
  function wpi_draw_pagination($settings = '') {
    global $wpp_query, $wp_properties;

    extract($wpp_query);

    //** Do not show pagination on ajax requests */
    if($wpp_query['ajax_call']) {
      return;
    }


    if($properties['total'] > $per_page && $pagination != 'off') {
      $use_pagination = true;
    }

    if ( $properties['total'] == 0 ) {
      $sortable_attrs = false;
    }

    ob_start();
    ?>

    <script type="text/javascript">
      /*
       * The functionality below is used for pagination and sorting the list of properties
       * It can be called twice (for top and bottom pagination blocks)
       * or more times (on multiple shortcodes)
       * So the current javascript functionality should not to be initialized twice.
       */

      /*
       * Init global WPP_QUERY variable which will contain all query objects
       */
      if(typeof wpp_query == 'undefined') {
        var wpp_query = [];
      }

      /*
       *
       */
      if(typeof document_ready == 'undefined') {
          var document_ready = false;
      }

      /*
       * Initialize shortcode's wpp_query object
       */
      if(typeof wpp_query_<?php echo $unique_hash; ?> == 'undefined') {
        var wpp_query_<?php echo $unique_hash; ?> = <?php echo json_encode($wpp_query); ?>;
        /* Default values for ajax query. It's used when we go to base URL using back button */
        wpp_query_<?php echo $unique_hash; ?>['default_query'] = wpp_query_<?php echo $unique_hash; ?>.query;

        /* Push query objects to global wpp_query variable */
        wpp_query.push(wpp_query_<?php echo $unique_hash; ?>);
      }

      /*
       * Init variable only at once
       */
      if(typeof wpp_pagination_history_ran == 'undefined') {
        var wpp_pagination_history_ran = false;
      }

      /* Init variable only at once */
      if(typeof wpp_pagination_<?php echo $unique_hash; ?> == 'undefined') {
        var wpp_pagination_<?php echo $unique_hash; ?> = false;
      }

      if(typeof first_load == 'undefined') {
        var first_load = true;
      }

      /* Watch for address URL for back buttons support */
      if(!wpp_pagination_history_ran) {
        wpp_pagination_history_ran = true;

        /*
         * On change location (address) Event.
         *
         * Also used as Back button functionality.
         *
         * Attention! This event is unique (binds at once) and is used for any (multiple) shortcode
         */
         jQuery(document).ready(function() {
          jQuery.address.change(function(event){
            callPagination(event);
          });
        });

        /*
         * Parse location (address) hash,
         * Setup shortcode params by hash params
         * Calls ajax pagination
         */
        function callPagination(event) {
          /*
           * We have to be sure that DOM is ready
           * if it's not, wait 0.1 sec and call function again
           */
          if(!document_ready) {
            window.setTimeout(function(){
              callPagination(event);
            }, 100);
            return false;
          }

          var history = {};
          /* Parse hash value (params) */
          var hashes = event.value.replace(/^\//, '');
          /* Determine if we have hash params */
          if(hashes) {
            hashes = hashes.split('&');
            for (var i in hashes) {
              if(typeof hashes[i] != 'function') {
                hash = hashes[i].split('=');
                history[hash[0]] = hash[1];
              }
            }
            
            if(history.i) {
              /* get current shortcode's object */
              var index = parseInt(history.i) - 1;
              if(index >= 0) {
                var q = wpp_query[index];
              }

              if(typeof q == 'undefined' || q.length == 0) {
                //ERROR
                return false;
              }

              if(history.sort_by && history.sort_by != '') {
                q.sort_by = history.sort_by;
              }

              if(history.sort_order  && history.sort_order != '') {
                q.sort_order = history.sort_order;
              }

              /* 'Select/Unselect' sortable buttons */
              var sortable_links = jQuery('#wpp_shortcode_' + q.unique_hash + ' .wpp_sortable_link');
              if(sortable_links.length > 0 ) {
                sortable_links.each(function(i,e){
                  jQuery(e).removeClass("wpp_sorted_element");
                  if(jQuery(e).attr('sort_slug') == q.sort_by) {
                    jQuery(e).addClass("wpp_sorted_element");
                  }
                });
              }

              if(history.requested_page && history.requested_page != '') {
                eval('wpp_do_ajax_pagination_' + q.unique_hash + '(' + history.requested_page + ')');
              } else {
                eval('wpp_do_ajax_pagination_' + q.unique_hash + '(1)');
              }
            } else {
              return false;
            }

          } else {
            /* Looks like it's base url
             * Determine if this first load, we do nothing
             * If not, - we use 'back button' functionality.
             */
            if(first_load) {
              first_load = false;
            } else {
              /*
               * Set default pagination values for all shortcodes
               */
              for(var i in wpp_query) {
                wpp_query[i].sort_by = wpp_query[i].default_query.sort_by;
                wpp_query[i].sort_order = wpp_query[i].default_query.sort_order;

                /* 'Select/Unselect' sortable buttons */
                var sortable_links = jQuery('#wpp_shortcode_' + wpp_query[i].unique_hash + ' .wpp_sortable_link');
                if(sortable_links.length > 0 ) {
                  sortable_links.each(function(ie,e){
                    jQuery(e).removeClass("wpp_sorted_element");
                    if(jQuery(e).attr('sort_slug') == wpp_query[i].sort_by) {
                      jQuery(e).addClass("wpp_sorted_element");
                    }
                  });
                }

                eval('wpp_do_ajax_pagination_' + wpp_query[i].unique_hash + '(1, false)');


              }
            }
          }
        }
      }

      /*
       * Changes location (address) hash based on pagination
       *
       * We use this function extend of wpp_do_ajax_pagination()
       * because wpp_do_ajax_pagination() is called on change Address Value's event
       *
       * @param int this_page Page which will be loaded
       * @param object data WPP_QUERY object
       * @return object data Returns updated WPP_QUERY object
       */
      if(typeof changeAddressValue == 'undefined' ) {
        function changeAddressValue (this_page, data) {
          var q = window.wpp_query;
          /* Get the current shortcode's index */
          var index = 0;
          for (var i in q) {
            if(q[i].unique_hash == data.unique_hash) {
              index = (++i);
              break;
            }
          }

          /* Set data query which will be used in history hash below */
          var q = {
            requested_page : this_page,
            sort_order : data.sort_order,
            sort_by : data.sort_by,
            i : index
          };
          /* Update WPP_QUERY query */
          data.query.requested_page = this_page;
          data.query.sort_order = data.sort_order;
          data.query.sort_by = data.sort_by;
          /*
           * Update page URL for back-button support (needs to do sort order and direction)
           * jQuery.address.value() and jQuery.address.path() double binds jQuery.change() event, some way
           * so for now, we use window.location
           */
          var history = jQuery.param(q);
          window.location.hash = '/' + history;

          return data;
        }
      }

      if(typeof wpp_do_ajax_pagination_<?php echo $unique_hash; ?> == 'undefined') {
        function wpp_do_ajax_pagination_<?php echo $unique_hash; ?>(this_page, scroll_to) {
          if(typeof this_page == 'undefined') {
            this_page = 1;
          }
          if(typeof scroll_to == 'undefined') {
            scroll_to = true;
          }

          data = wpp_query_<?php echo $unique_hash; ?>;

          /* Update page counter */
          jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_current_page_count").text(this_page);
          jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider .slider_page_info .val").text(this_page);

          /* Update sliders  */
          jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider").slider("value", this_page );

          jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .ajax_loader').show();

          /* Scroll page to the top of the current shortcode */
          if(scroll_to) {
            jQuery(document).trigger('wpp_pagination_change',{'overview_id' : <?php echo $unique_hash; ?>});
          }

          data.ajax_call = 'true';
          data.requested_page = this_page;

          //console.log(data);

          //jQuery.ajaxSetup({async:false});

          jQuery.post(
            '<?php echo admin_url('admin-ajax.php'); ?>',
            {
              action: 'wpp_property_overview_pagination',
              wpp_ajax_query: data
            },
            function(result_data) {
              jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .ajax_loader').hide();

              var p_list = jQuery('.wpp_property_view_result', result_data.display);
              //* Determine if p_list is empty try previous version's selector */
              if( p_list.length == 0 ) {
                p_list = jQuery('.wpp_row_view', result_data.display);
              }
              var content = ( p_list.length > 0 ) ? p_list.html() : '';

              var p_wrapper = jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_property_view_result');
              //* Determine if p_wrapper is empty try previous version's selector */
              if(p_wrapper.length == 0) {
                p_wrapper = jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_row_view')
              }

              p_wrapper.html(content);

              /* Total properties count may change depending on sorting (if sorted by an attribute that all properties do not have) */
              jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_property_results").text(result_data.wpp_query.properties.total);

              <?php if($use_pagination) { ?>
              /* Update max page in slider and in display */
              jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider").slider("option", "max",  result_data.wpp_query.pages);
              jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_total_page_count").text(result_data.wpp_query.pages);
              <?php } ?>

              jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> a.fancybox_image").fancybox({
                'transitionIn'  : 'elastic',
                'transitionOut' : 'elastic',
                'speedIn' : 600,
                'speedOut'  : 200,
                'overlayShow' : false
              });

              jQuery(document).trigger('wpp_pagination_change_complete',{'overview_id' : <?php echo $unique_hash; ?>});
            },
            "json"
          );
        }
      }

      jQuery(document).ready(function() {

        document_ready = true;

        //** Do not assign click event again */
        if(!jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_back').data('events') ) {
          jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_back').click(function() {
            var current_value =  jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider").slider("value");

            if(current_value == 1) { return; }

            var new_value = current_value - 1;
            jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider").slider("value", new_value);
            wpp_query_<?php echo $unique_hash; ?> = changeAddressValue(new_value, wpp_query_<?php echo $unique_hash; ?>);
          });
        }

        if(!jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_forward').data('events') ) {
          jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_forward').click(function() {
            var current_value =  jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider").slider("value");

            if(current_value == <?php echo ($pages ? $pages : 0); ?>) { return; }

            var new_value = current_value + 1;
            jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider").slider("value",new_value);
            wpp_query_<?php echo $unique_hash; ?> = changeAddressValue(new_value, wpp_query_<?php echo $unique_hash; ?>);
          });
        }

        if(!jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_sortable_link').data('events') ) {
          jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_sortable_link').click(function() {

            var attribute = jQuery(this).attr('sort_slug');
            var sort_order = jQuery(this).attr('sort_order');

            var this_attribute = jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_sortable_link[sort_slug="+attribute+"]");

            if(jQuery(this).is(".wpp_sorted_element")) {
              var currently_sorted = true;
              /* If this attribute is already sorted, we switch sort order */
              if(sort_order == "ASC") {
                sort_order = "DESC";
              } else if(sort_order == "DESC") {
                sort_order = "ASC";
              }
            }

            jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_sortable_link").removeClass("wpp_sorted_element");
            wpp_query_<?php echo $unique_hash; ?>.sort_by = attribute;
            wpp_query_<?php echo $unique_hash; ?>.sort_order = sort_order;

            jQuery(this_attribute).addClass("wpp_sorted_element");
            jQuery(this_attribute).attr("sort_order", sort_order);

            /* Get ajax results and reset to first page */
            wpp_query_<?php echo $unique_hash; ?> = changeAddressValue(1, wpp_query_<?php echo $unique_hash; ?>);
          });
        }
        if(!jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_sortable_dropdown').data('events') ) {
          jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_sortable_dropdown').change(function() {

            var parent = jQuery(this).parents('.wpp_sorter_options');
            var attribute = jQuery(":selected", this).attr('sort_slug');
            var sort_element = jQuery(".sort_order", parent);
            var sort_order = jQuery(sort_element).attr('sort_order');

            wpp_query_<?php echo $unique_hash; ?>.sort_by = attribute;
            wpp_query_<?php echo $unique_hash; ?>.sort_order = sort_order;

            /* Get ajax results and reset to first page */
            wpp_query_<?php echo $unique_hash; ?> = changeAddressValue(1, wpp_query_<?php echo $unique_hash; ?>);
          });
        }

        if(!jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_overview_sorter').data('events') ) {
          jQuery('#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_overview_sorter').click(function() {

            var parent = jQuery(this).parents('.wpp_sorter_options');

            var sort_element = this;
            var dropdown_element = jQuery(".wpp_sortable_dropdown", parent);

            var attribute = jQuery(":selected", dropdown_element).attr('sort_slug');
            var sort_order = jQuery(sort_element).attr('sort_order');

            jQuery(sort_element).removeClass(sort_order);

            /* If this attribute is already sorted, we switch sort order */
            if(sort_order == "ASC") {
              sort_order = "DESC";
            } else if(sort_order == "DESC") {
              sort_order = "ASC";
            }

            wpp_query_<?php echo $unique_hash; ?>.sort_by = attribute;
            wpp_query_<?php echo $unique_hash; ?>.sort_order = sort_order;

            jQuery(sort_element).attr("sort_order", sort_order);
            jQuery(sort_element).addClass(sort_order);

            /* Get ajax results and reset to first page */
            wpp_query_<?php echo $unique_hash; ?> = changeAddressValue(1, wpp_query_<?php echo $unique_hash; ?>);
          });
        }

        <?php if($use_pagination) { ?>
        if(!wpp_pagination_<?php echo $unique_hash; ?>) {
          jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider_wrapper").each(function() {
            var this_parent = this;

            /* Slider */
            jQuery('.wpp_pagination_slider', this).slider({
              value:1,
              min: 1,
              max: <?php echo $pages; ?>,
              step: 1,
              slide: function( event, ui ) {
                /* Update page counter - we do it here because we want it to be instant */
                jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_current_page_count").text(ui.value);
                jQuery("#wpp_shortcode_<?php echo $unique_hash; ?> .wpp_pagination_slider .slider_page_info .val").text(ui.value);
              },
              stop: function(event, ui) {
                wpp_query_<?php echo $unique_hash; ?> = changeAddressValue(ui.value, wpp_query_<?php echo $unique_hash; ?>);
              }
            });

            jQuery('.wpp_pagination_slider .ui-slider-handle', this).append('<div class="slider_page_info"><div class="val">1</div><div class="arrow"></div></div>');
          });
          wpp_pagination_<?php echo $unique_hash; ?> = true;
        }
        <?php } ?>

      });
    </script>

    <div class="properties_pagination <?php echo $settings['class']; ?> wpp_slider_pagination" id="properties_pagination_<?php echo $unique_hash; ?>">
      <div class="wpp_pagination_slider_status">
        <span class="wpp_property_results"><?php echo ($properties['total'] > 0 ? WPP_F::format_numeric($properties['total']) : __('None', 'wpp')); ?></span>
        <?php _e(' found.', 'wpp'); ?>
        <?php if($use_pagination) { ?>
        <?php _e('Viewing page', 'wpp'); ?> <span class="wpp_current_page_count">1</span> <?php _e('of', 'wpp'); ?> <span class="wpp_total_page_count"><?php echo $pages; ?></span>.
        <?php } ?>

        <?php if($sortable_attrs) { ?>
        <span class="wpp_sorter_options"><label  class="wpp_sort_by_text"><?php echo $settings['sort_by_text']; ?></label>
        <?php

        if($settings['sorter_type'] == 'buttons') { ?>
        <?php foreach($sortable_attrs as $slug => $label) { ?>
          <span class="wpp_sortable_link <?php echo ($sort_by == $slug ? 'wpp_sorted_element':''); ?>" sort_order="<?php echo $sort_order ?>" sort_slug="<?php echo $slug; ?>"><?php echo $label; ?></span>
        <?php } ?>
        <?php } elseif($settings['sorter_type'] == 'dropdown') { ?>
        <select class="wpp_sortable_dropdown sort_by" name="sort_by">
        <?php foreach($sortable_attrs as $slug => $label) { ?>
          <option <?php echo ($sort_by == $slug ? 'class="wpp_sorted_element" selected="true"':''); ?> sort_slug="<?php echo $slug; ?>" value="<?php echo $slug; ?>"><?php echo $label; ?></option>
        <?php } ?>
        </select>
        <span class="wpp_overview_sorter sort_order <?php echo $sort_order ?>" sort_order="<?php echo $sort_order ?>"></span>
        <?php } else {
          do_action('wpp_custom_sorter', array('settings' => $settings, 'wpp_query' => $wpp_query, 'sorter_type' => $settings['sorter_type']));
        }
        ?>
        </span>
        <?php } ?>
        <div class="clear"></div>
      </div>

      <?php if($use_pagination) { ?>
      <div class="wpp_pagination_slider_wrapper">
        <div class="wpp_pagination_back"><?php _e('Prev', 'wpp'); ?></div>
        <div class="wpp_pagination_forward"><?php _e('Next', 'wpp'); ?></div>
        <div class="wpp_pagination_slider"></div>
      </div>
      <?php } ?>

    </div>
    <div class="ajax_loader"></div>

    <?php
    $result = ob_get_contents();
    ob_end_clean();
    //die("<pre style='color: white;'> wpp_query:" . print_r($wpp_query, true). "</pre>");
    if($settings['return'] == 'true') {
      return $result;
    }

    echo $result;
  }

endif;

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
    global $wp_properties;

    if(empty($property)) {
      return;
    }

    if(is_numeric($property)) {
      $property_id = $property;
    } elseif(is_object($property)) {
      $property = (array)$property;
      $property_id = $property['ID'];
    } elseif(is_array($property)) {
      $property_id = $property['ID'];
    }

    if($cache_property = wp_cache_get('property_for_display_' . $property_id)) {
      return $cache_property;
    }

    //** Cache not found, load property */
    $property = (array)WPP_F::get_property($property_id);

    foreach($property as $meta_key => $attribute) {
      $property[$meta_key] = apply_filters("wpp_stat_filter_$meta_key",$attribute);
    }

    // Go through children properties
    if(is_array($property['children']))
      foreach($property['children'] as $child => $child_data)
        $property['children'][$child] = prepare_property_for_display($child_data);


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

if(!function_exists('get_features')) {
  function get_features($args = '', $property = false) {
    global $post;

    if(is_array($property)) {
      $property = (object) $property;
    }

    if(!$property) {
      $property = $post;
    }

    $defaults = array('type' => 'property_feature', 'format' => 'comma', 'links' => true);
    $args = wp_parse_args( $args, $defaults );

    $features = get_the_terms($property->ID, $args['type']);

    $features_html = array();

    if($features) {
    foreach ($features as $feature) {

      if($links) {
        array_push($features_html, '<a href="' . get_term_link($feature->slug, $args['type']) . '">' . $feature->name . '</a>');
      } else {
        array_push($features_html, $feature->name);
      }

      if($args['format'] == 'comma') {
        echo implode($features_html, ", ");
      }

      if($args['format'] == 'array') {
        return $features_html;
      }

      if($args['format'] == 'count') {
        return (count($features) > 0 ? count($features) : false);
      }

      if($args['format'] == 'list') {
        echo "<li>" . implode($features_html, "</li><li>") . "</li>";
      }
      
      }

    }

  }
}

if(!function_exists('draw_stats')):
  /**
   * Returns printable array of property stats
   *
   * @since 1.11
   * @args: exclude, return_blank, make_link
   */
  function draw_stats($args = false, $property = false){
    global $wp_properties, $post;

    if(!$property) {
      $property = $post;
    }

    if(is_array($property)) {
      $property = (object)$property;
    }

    $defaults = array(
      'sort_by_groups' => $wp_properties['configuration']['property_overview']['sort_stats_by_groups'],
      'display' => 'dl_list',
      'enable_shortcode' => 'false',
      'show_true_as_image' => 'false',
      'hide_false' => 'false'
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    $property_stats = WPP_F::get_stat_values_and_labels($property, $args);
    $groups = $wp_properties['property_groups'];
      
    if(!$property_stats) {
      return;
    }

    //* Prepare values before display */
    $stats = array();
    $labels_to_keys = array_flip($wp_properties['property_stats']);

    foreach($property_stats as $label => $value){
      if(empty($value)) {
        continue;
      }

      //** Do not show attributes that have value of 'value' if enabled */
      if($hide_false == 'true' && $value == 'false') {
        continue;
      }

      $tag = $labels_to_keys[$label];

      //* Filter Value */
      $value =  trim(apply_filters("wpp_stat_filter_$tag", $value, $property));
      //* Determine if the current attribute is address and display_address is set */
      if($tag == $wp_properties['configuration']['address_attribute'] && !empty($property->display_address)) {
        $value = $property->display_address;
      }

      //* Skip blank values (check after filters have been applied) */
      if($return_blank == 'false' && empty($value)) {
        continue;
      }

      $value = html_entity_decode($value);

      if($enable_shortcode == 'true') {
        $value = do_shortcode($value);
      }

      if($value == 'true') {
        if($show_true_as_image == 'true') {
          $value = '<div class="true-checkbox-image"></div>';
        } else {
          $value = __('Yes', 'wpp');
        }
      } else if ($value == 'false') {
        $value = __('No', 'wpp');
      }

      //* Make URLs into clickable links */
      if($make_link == 'true' && WPP_F::isURL($value)) {
        $value = "<a href='{$value}' title='{$label}'>{$value}</a>";
      }

      $stats[$label] = $value;

    }

    if($display == 'array') {
      return $stats;
    }

    //** Disable regular list if groups are NOT enabled, or if groups is not an array */
    if($sort_by_groups != 'true' || !is_array($groups)) {
      $alt = 'alt';
      foreach ($stats as $label => $value) {
        $tag = $labels_to_keys[$label];
        $alt = $alt == "alt" ? "" : "alt";

        switch($display) {
          case 'dl_list':
            ?>
            <dt class="wpp_stat_dt_<?php echo $tag; ?>"><?php echo $label; ?></dt>
            <dd class="wpp_stat_dd_<?php echo $tag; ?> <?php echo $alt; ?>"><?php echo $value; ?>&nbsp;</dd>
            <?php
            break;

          case 'list':
            ?>
            <li class="wpp_stat_plain_list_<?php echo $tag; ?> <?php echo $alt; ?>">
              <span class="attribute"><?php echo $label; ?>:</span>
              <span class="value"><?php echo $value; ?>&nbsp;</span>
            </li>
            <?php
            break;

          case 'plain_list':
            ?>
            <span class="attribute"><?php echo $label; ?>:</span>
            <span class="value"><?php echo $value; ?>&nbsp;</span>
            <br />
            <?php
            break;
        }
      }
    } else {
      $stats_by_groups = sort_stats_by_groups($stats, array('includes_values' => true));

      $main_stats_group = $wp_properties['configuration']['main_stats_group'];
      
      $labels_to_keys = array_flip($wp_properties['property_stats']);
      

      foreach($stats_by_groups as $gslug => $gstats) {

        if($main_stats_group != $gslug || !@key_exists($gslug, $groups)) {
          $group_name = ( @key_exists($gslug, $groups) ? $groups[$gslug]['name'] : __('Other','wpp') );
          ?>
          <span class="wpp_stats_group"><?php echo $group_name; ?></span>
          <?php
        }

        $alt = 'alt';

        switch($display) {
          case 'dl_list':
            ?>
            <dl id="property_stats" class="overview_stats">
            <?php foreach($gstats as $label => $value) : ?>
              <?php $tag = $labels_to_keys[$label]; ?>
              <?php $alt = $alt == "alt" ? "" : "alt"; ?>
              <dt class="wpp_stat_dt_<?php echo $tag; ?>"><?php echo $label; ?></dt>
              <dd class="wpp_stat_dd_<?php echo $tag; ?> <?php echo $alt; ?>"><?php echo $value; ?>&nbsp;</dd>
            <?php endforeach; ?>
            </dl>
            <?php
            break;

          case 'list':
            ?>
            <ul class="overview_stats wpp_property_stats">
            <?php foreach($gstats as $label => $value) : ?>
              <?php $tag = $labels_to_keys[$label]; ?>
              <?php $alt = $alt == "alt" ? "" : "alt"; ?>
              <li class="wpp_stat_plain_list_<?php echo $tag; ?> <?php echo $alt; ?>">
                <span class="attribute"><?php echo $label; ?>:</span>
                <span class="value"><?php echo $value; ?>&nbsp;</span>
              </li>
            <?php endforeach; ?>
            </ul>
            <?php
            break;

          case 'plain_list':
            foreach($gstats as $label => $value) {
              ?>
              <span class="attribute"><?php echo $label; ?>:</span>
              <span class="value"><?php echo $value; ?>&nbsp;</span>
              <br />
              <?php
            }
            break;
        }
      }

    }

  }
endif;

/**
 *
 * Sorts property stats by groups.
 *
 * Takes a passed array of attributes, and breaks them up into their groups.
 *
 * @param array $stats Property stats
 * @return array $stats Modified array of stats which sorted by groups
 * @author Maxim Peshkov
 */
if(!function_exists('sort_stats_by_groups')):
  function sort_stats_by_groups($stats = false, $args = '') {
    global $wp_properties;

    if(!$stats) {
      return false;
    }
    
    $original_stats = $stats;

    //** Get group deta */
    $groups = $wp_properties['property_groups'];

    /** Get attribute-group association */
    $stats_groups = $wp_properties['property_stats_groups'];
    
    if(!is_array($groups) || !is_array($stats_groups)) {
      return;
    }
    
    $defaults = array(
      'includes_values' => false,
      'fix_stats_array' => false
    );

    $args = wp_parse_args( $args, $defaults );
    
    $wpp_property_stat_labels = apply_filters('wpp_property_stat_labels', $wp_properties['property_stats']);
    $wpp_property_stat_labels_flip = array_flip($wpp_property_stat_labels);
    
    if($args['includes_values'] == true) {
      //** Fix data when an array is passed with actual values, such as from draw_stats() */
      
      foreach($stats as $meta_label => $real_value) {
        $meta_key = $wpp_property_stat_labels_flip[$meta_label];
        //echo "$meta_key - $attribute_label <br />";
        $fixed_stats[$meta_label] = $meta_key;

      }
 
    }

    //** Convert regular stat array to array with values as keys */
    if($args['fix_stats_array'] = true) {

      foreach($stats as $meta_key) {
        $attribute_label = $wpp_property_stat_labels[$meta_key];
        //echo "$meta_key - $attribute_label <br />";
        $fixed_stats[$attribute_label] = $meta_key;

      }      

      $stats = $fixed_stats;
    }


    $labels_to_keys = array_flip($wp_properties['property_stats']);

    
    $group_keys = array_keys($wp_properties['property_groups']);

    //** Get group from settings, or set to first group as default */
    $main_stats_group = (!empty($wp_properties['configuration']['main_stats_group']) ? $wp_properties['configuration']['main_stats_group'] : $group_keys[0]);

    $filtered_stats = array($main_stats_group => array());
    
    $ungrouped_stats = array(); 
    
    foreach($stats as $label => $value) {
      
      $slug = $labels_to_keys[$label];

      $g_slug = !empty($stats_groups[$slug]) ? $stats_groups[$slug] : false;

      //** Handle adding special attributes to groups automatically */
      switch($value) {
        case 'property_type':
          $g_slug = $main_stats_group;
        break;
        case 'city':
          $g_slug = $main_stats_group;
        break;
      }

      if($g_slug && !key_exists($g_slug, $groups)) {
        $g_slug = false;
      }
      
       if($args['includes_values'] == true) {
        $value = $original_stats[$label];
       }
       
       if(empty($value)) {
        continue;
       }

      if($g_slug) {
        //** Build array of attributes in groups */
        $filtered_stats[$g_slug][$label] = $value;
      } else {
        //** Build array of attributes WITHOUT groups */
        $filtered_stats[0][$label] = $value;
      }
    }



    //** Cycle back through to make sure we don't have any empty groups */
    foreach($filtered_stats as $key => $data) {
      if(empty($data)) {
        unset($filtered_stats[$key]);
      }
    }
    
    
    //echo "<pre>";print_r($filtered_stats);echo "</pre>";
 

 

    return $filtered_stats;
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
  function draw_property_search_form($args = false) {
    global $wp_properties;

     $defaults = array(
      'search_attributes' => false,
      'searchable_property_types' => false,
      'use_pagination' => 'on',
      'per_page' => '10',
      'group_attributes' => false,
      'instance_id' => false,
      'sort_order' => false,
      'cache' => true
    );

    //print_r($args);

    WPP_F::force_script_inclusion('jquery-number-format');

    
    $args = wp_parse_args( $args, $defaults );

    
    if(empty($args['search_attributes']) && isset($args['searchable_attributes'])) {
      $args['search_attributes'] = $args['searchable_attributes'];
    }
    

        

    extract( $args, EXTR_SKIP );
        

    $search_values = array();
    $property_type_flag = false;

    //** Bail if no search attributes passed */
    if(!is_array($search_attributes)) {
      return;
    }

    //** Load search values for attributes (from cache, or generate) */
    if (!empty($search_attributes) && !empty($searchable_property_types)) {
      $search_values = WPP_F::get_search_values($search_attributes, $searchable_property_types, $cache, $instance_id);
    }
    
    //** This looks clumsy - potanin@UD */
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
    } ?>

    <form action="<?php echo  UD_F::base_url($wp_properties['configuration']['base_slug']); ?>" method="post">

    <?php if($sort_order){ ?>
    <input type="hidden" name="wpp_search[sort_order]" value ="<?php echo esc_attr($sort_order); ?>" />
    <?php } ?>

    <?php if($sort_by){ ?>
    <input type="hidden" name="wpp_search[sort_by]" value ="<?php echo esc_attr($sort_by); ?>" />
    <?php } ?>

    <?php if($use_pagination) { ?>
      <input type="hidden" name="wpp_search[pagination]" value="<?php echo $use_pagination; ?>" />
    <?php } ?>

    <?php if($per_page) { ?>
      <input type="hidden" name="wpp_search[per_page]" value="<?php echo $per_page; ?>" />
    <?php } ?>

    <?php

    //** If no property_type passed in search_attributes, we get defaults */
    if(is_array($searchable_property_types) && !array_key_exists('property_type', array_fill_keys($search_attributes, 1))) {
      foreach($searchable_property_types as $property) {
        echo '<input type="hidden" name="wpp_search[property_type][]" value="'. $property .'" />';
      }
    }

    ?>
    <ul class="wpp_search_elements">

    <?php



    if($group_attributes) {
      //** Get group data */
      $groups = $wp_properties['property_groups'];
      //** Group  selected searchable attributes */
      $search_groups = sort_stats_by_groups($search_attributes, array('fix_stats_array' => true));
    } else {
      //** Create an ad-hoc group */
      $search_groups['ungrouped'] = $search_attributes;
    }
    
    
    $main_stats_group = $wp_properties['configuration']['main_stats_group'];



    foreach($search_groups as $group_key => $search_attributes) {

      $this_group = $group_key;

      if($this_group == 'ungrouped' || $this_group === 0 || $this_group == $main_stats_group) {
         $is_a_group = false;
        $this_group = 'not_a_group';
      } else {
        $is_a_group = true;
      }

      ?>

      <li class="wpp_search_group wpp_group_<?php echo $this_group; ?>">

      <?php if($is_a_group) { ?>
      <span class="wpp_search_group_title wpp_group_<?php echo $this_group; ?>_title"><?php echo $groups[$this_group]['name']; ?></span>
      <?php } ?>

      <ul class="wpp_search_group wpp_group_<?php echo $this_group; ?>">
      <?php

      //** Begin Group Attributes */
      foreach($search_attributes as $attrib) {

        //** Override search values if they are set in the developer tab */
        if(!empty($wp_properties['predefined_search_values'][$attrib])) {
          $maybe_search_values = explode(',', $wp_properties['predefined_search_values'][$attrib]);

          if(is_array($maybe_search_values)) {
            $using_predefined_values = true;
            $search_values[$attrib] = $maybe_search_values;
          } else {
            $using_predefined_values = true;
          }
        }

        //** Don't display search attributes that have no values */
        if(!isset($search_values[$attrib])) {
          continue;
        }


        $label = (empty($wp_properties['property_stats'][$attrib]) ? UD_F::de_slug($attrib) : $wp_properties['property_stats'][$attrib]);

        
        if(empty($label) && $attrib == 'property_type') {
          $label = __('Type:', 'wpp');
        }


        ?>

        <li class="wpp_search_form_element seach_attribute_<?php echo $attrib; ?>  wpp_search_attribute_type_<?php echo $wp_properties['searchable_attr_fields'][$attrib]; ?> <?php echo ((!empty($wp_properties['searchable_attr_fields'][$attrib]) && $wp_properties['searchable_attr_fields'][$attrib] == 'checkbox') ? 'wpp-checkbox-el' : ''); ?>">
          
          <?php ob_start(); ?>
          
          <?php $random_element_id = 'wpp_search_element_' . rand(1000,9999); ?>
          <label for="<?php echo $random_element_id; ?>" class="wpp_search_label wpp_search_label_<?php echo $attrib; ?>"><?php echo $label; ?>:</label>
          <div class="wpp_search_attribute_wrap">
          <?php
          wpp_render_search_input(array(
            'attrib' => $attrib,
            'random_element_id' => $random_element_id,
            'search_values' => $search_values,
            'value' => $_REQUEST['wpp_search'][$attrib]
          ));
          
          $this_field = ob_get_contents();
          ob_end_clean();
          
          echo apply_filters('wpp_search_form_field_'. $attrib, $this_field, $attrib, $label, $_REQUEST['wpp_search'][$attrib], $wp_properties['searchable_attr_fields'][$attrib], $random_element_id); ?>
          
          </div>
          <div class="clear"></div>
          </li>
        <?php }
      //** End Group Attributes */
      ?>
      </ul>
      <div class="clear"></div>
      </li>
      <?php } ?>

      <li class="wpp_search_form_element submit"><input type="submit" class="wpp_search_button submit" value="<?php _e('Search','wpp') ?>" /></li>
    </ul>

    </form>
  <?php }

endif;



  /**
   * Draws a search form element
   *
   *
   * @return array|$wp_properties
   * @since 1.22.1
   * @version 1.14
   *
    */
if(!function_exists('wpp_render_search_input')):
  function wpp_render_search_input($args = false) {
    global $wp_properties;
    
    $defaults = array(
      'type' => 'input',
      'input_type' => false,
      'search_values' => false,
      'attrib' => false,
      'random_element_id' => 'wpp_search_element_' . rand(1000,9999),
      'value' => false
    );
    
    extract(wp_parse_args( $args, $defaults ));
    
    $attribute_data = WPP_F::get_attribute_data($attrib);
    
    $use_input_type = $wp_properties['searchable_attr_fields'][$attrib];
    
    if(!empty($input_type)) {
      $use_input_type = $input_type;
    }
    
    if(!empty($wp_properties['searchable_attr_fields'][$attrib])) {
    
    switch($use_input_type) {
      case 'input':
        ?>
        <input id="<?php echo $random_element_id; ?>"  class="<?php echo $attribute_data['ui_class']; ?>" name="wpp_search[<?php echo $attrib; ?>]" value="<?php echo $value; ?>" type="text" />
        <?php
        break;
      
      case 'range_input':
        ?>
        <input id="<?php echo $random_element_id; ?>" class="wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>" type="text" name="wpp_search[<?php  echo $attrib; ?>][min]" value="<?php echo $value['min']; ?>" /> <span class="wpp_dash">-</span>
        <input class="wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"  type="text" name="wpp_search[<?php echo $attrib; ?>][max]" value="<?php echo $value['max']; ?>" />
        <?php
        break;
      
      case 'range_dropdown':
        ?>
        <?php $grouped_values = group_search_values($search_values[$attrib]); ?>
        <select id="<?php echo $random_element_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>" name="wpp_search[<?php echo $attrib; ?>][min]" >
        <option value="-1"><?php _e( 'Any' ,'wpp' ) ?></option>
        <?php foreach($grouped_values as $v) : ?>
          <option value='<?php echo (int)$v; ?>' <?php if($value['min'] == $v) echo " selected='true' "; ?>>
          <?php echo apply_filters("wpp_stat_filter_$attrib", $v); ?> +
          </option>
          <?php endforeach; ?>
        </select>
        <?php
        break;
        
      case 'dropdown':
        ?>
        <?php $req_attr = @htmlspecialchars(stripslashes($value), ENT_QUOTES); ?>
        <select id="<?php echo $random_element_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>" name="wpp_search[<?php echo $attrib; ?>]" >
        <option value="-1"><?php _e( 'Any' ,'wpp' ) ?></option>
        <?php foreach( $search_values[$attrib] as $v ) : ?>
          <option value="<?php echo esc_attr($v); ?>" <?php selected($req_attr,$v); ?>>
          <?php echo esc_attr(WPP_F::decode_mysql_output( apply_filters("wpp_stat_filter_$attrib", $v) )); ?>
          </option>
        <?php endforeach; ?>
        </select>
        <?php
        break;
      
      case 'multi_checkbox': 
        ?>
        <ul class="wpp_multi_checkbox <?php echo $attribute_data['ui_class']; ?>">
        <?php foreach($search_values[$attrib] as $value_label) : ?>
          <?php $unique_id = rand(10000,99999);?>
          <li>
            <input name="wpp_search[<?php  echo $attrib; ?>][]" <?php echo (is_array($value) && in_array($value_label, $value) ? 'checked="true"' : '');?> id="wpp_attribute_checkbox_<?php echo $unique_id; ?>" type="checkbox" value="<?php echo $value_label; ?>" />
            <label for="wpp_attribute_checkbox_<?php echo $unique_id; ?>"><?php echo $value_label; ?></label>
          </li>
        <?php endforeach; ?>
        </ul>
        <?php 
        break;
        
      case 'checkbox': 
        ?>
        <input id="<?php echo $random_element_id; ?>" type="checkbox" class="<?php echo $attribute_data['ui_class']; ?>" name="wpp_search[<?php echo $attrib; ?>]" <?php checked($value, 'true'); ?> value="true" />
        <?php
        break;
    }
    
    } else {
      ?>
      <?php //* Determine if attribute is a numeric range */ ?>
      <?php if(WPP_F::is_numeric_range($search_values[$attrib])) : ?>
        <input class="wpp_search_input_field_min wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>" type="text" name="wpp_search[<?php  echo $attrib; ?>][min]" value="<?php echo $value['min']; ?>" /> -
        <input class="wpp_search_input_field_max wpp_search_input_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>"  type="text" name="wpp_search[<?php echo $attrib; ?>][max]" value="<?php echo $value['max']; ?>" />
      <?php else : ?>
        <?php /* Not a numeric range */ ?>
        <select id="<?php echo $random_element_id; ?>" class="wpp_search_select_field wpp_search_select_field_<?php echo $attrib; ?> <?php echo $attribute_data['ui_class']; ?>" name="wpp_search[<?php echo $attrib; ?>]" >
        <option value="<?php echo (($attrib == 'property_type' && is_array($search_values[$attrib])) ? implode(',',(array_flip($search_values[$attrib]))) : '-1' ); ?>"><?php _e('Any','wpp') ?></option>
        <?php foreach($search_values[$attrib] as $key => $v) : ?>
          <option value='<?php echo (($attrib=='property_type')?$key:$v); ?>' <?php if($value == (($attrib=='property_type')?$key:$v)) echo " selected='true' "; ?>>
          <?php echo WPP_F::decode_mysql_output( apply_filters("wpp_stat_filter_$attrib", $v) ); ?>
          </option>
        <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <?php
    }
  }
endif;

if(!function_exists('wpp_get_image_link')):
  /*
   * Returns Image link (url)
   *
   * If image with the current size doesn't exist, we try to generate it.
   * If image cannot be resized, the URL to the main image (original) is returned.
   *
   * @todo Add something to check if requested image size is bigger than the original, in which case cannot be "resized"
   * @todo Add a check to see if the specified image dimensions have changed. Right now only checks if slug exists, not the actualy size.
   *
   * @param string $size. Size name
   * @param string(integer) $thumbnail_link. attachment_id
   * @param string $args. Additional conditions
   * @return string or array. Default is string (image link)
   */
  function wpp_get_image_link($attachment_id, $size, $args = array()) {
    global $wp_properties;

    if(empty($size) || empty($attachment_id)) {
      return false;
    }

    //** Optional arguments */
    $defaults = array(
      'return' => 'string'
    );

    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    if($wp_properties['configuration']['do_not_automatically_regenerate_thumbnails'] == 'true') {
      //* If on-the-fly image generation is specifically disabled, we simply return the default URL */
      $default_return = wp_get_attachment_image_src( $attachment_id, $size , true );

      $i[0] = $default_return[0];
      $i[1] = $default_return[1];
      $i[2] = $default_return[2];

    } else {
      //* Do the default action of attempting to regenerate image if needed. */

      $uploads_dir = wp_upload_dir();

      //** Get image path from meta table (if this doesn't exist, nothing we can do */
      if($_wp_attached_file = get_post_meta($attachment_id, '_wp_attached_file', true)) {
        $attachment_path = $uploads_dir['basedir'] . '/' . $_wp_attached_file;
      } else {
        return false;
      }

      //** Get meta of main image (may not exist if XML import) */
      $image_meta = wp_get_attachment_metadata($attachment_id);

      //** Real URL of full image */
      $img_url = wp_get_attachment_url($attachment_id);

      //** Filenme of image */
      $img_url_basename = wp_basename($img_url);


      if(is_array($image_meta) && $image_meta['sizes'][$size]['file']) {

        //** Image image meta exists, we get the path and URL to the requested image size */
        $requested_size_filepath = str_replace($img_url_basename, $image_meta['sizes'][$size]['file'], $attachment_path);
        $requested_image_url = str_replace($img_url_basename, $image_meta['sizes'][$size]['file'], $img_url);
        $image_path = $requested_size_filepath;

        //** Meta is there, now check if file still exists on disk */

        if (file_exists( $requested_size_filepath ) ) {
          $requested_image_exists = true;
        }
      }

      if($requested_image_exists) {
        $i[0] = $requested_image_url;

      } else {

        //** Image with the current size doesn't exist. Try generate file */
        if ( WPP_F::generate_image($attachment_id, $size) ) {
          //** Get Image data again */
          $image = image_downsize($attachment_id, $size);
          if(is_array($image)) {
            $i = $image;
          }
        } else {

          //** Failure because image could not be resized. Return original URL */
          $i[0] = $img_url;
          $image_path = str_replace($uploads_dir['baseurl'], $uploads_dir['basedir'], $img_url);

        }
      }

    }

    //** Get true image dimensions or returned URL */
    $getimagesize =@getimagesize($image_path);
    $i[1] = $getimagesize[0];
    $i[2] = $getimagesize[1];


    //** Return image data as requested */
    if($i) {
      switch ($return) {
        case 'array':
          if($i[1] == 0 || $i[2] == 0) {

            $s = WPP_F::image_sizes($size);
            $i[1] = $s['width'];
            $i[2] = $s['height'];
          }
          return array (
            'link' => $i[0],
            'src' => $i[0],
            'url' => $i[0],
            'width' => $i[1],
            'height' => $i[2]
          );
          break;

        case 'string':
        default:
          return $i[0];
          break;
      }
    }

    return false;
  }
endif;

if(!function_exists('wpp_inquiry_form')):
  /*
   * Overwtites default Wordpress function comment_form()
   * @param array $args Options for strings, fields etc in the form
   * @param mixed $post_id Post ID to generate the form for, uses the current post if null
   * @return void
   */
  function wpp_inquiry_form( $args = array(), $post_id = null ) {
    global $post, $user_identity, $id;

    $inquiry = true;

    /* Determine if post is property */
    if($post->post_type != 'property') {
      $inquiry = false;
    }

    $inquiry = apply_filters('pre_render_inquiry_form', $inquiry);

    if(!$inquiry) {
      /* If conditions are failed, use default Wordpress function */
      comment_form($args, $post_id);
    } else {
      /* The functionality below based on comment_form() function */
      if ( null === $post_id ) {
        $post_id = $id;
      } else {
        $id = $post_id;
      }

      $commenter = wp_get_current_commenter();

      $req = get_option( 'require_name_email' );
      $aria_req = ( $req ? " aria-required='true'" : '' );
      $fields =  array(
        'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name' ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
                    '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
        'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email' ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
                    '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
        'url'    => '<p class="comment-form-url"><label for="url">' . __( 'Website' ) . '</label>' .
                    '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
      );

      $required_text = sprintf( ' ' . __('Required fields are marked %s'), '<span class="required">*</span>' );
      $defaults = array(
        'fields'               => apply_filters( 'comment_form_default_fields', $fields ),
        'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
        'must_log_in'          => '<p class="must-log-in">' .  sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
        'logged_in_as'         => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>' ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
        'comment_notes_before' => '<p class="comment-notes">' . __( 'Your email address will not be published.' ) . ( $req ? $required_text : '' ) . '</p>',
        'comment_notes_after'  => '<p class="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s' ), ' <code>' . allowed_tags() . '</code>' ) . '</p>',
        'id_form'              => 'commentform',
        'id_submit'            => 'submit',
        'title_reply'          => __( 'Leave a Reply' ),
        'title_reply_to'       => __( 'Leave a Reply to %s' ),
        'cancel_reply_link'    => __( 'Cancel reply' ),
        'label_submit'         => __( 'Post Comment' ),
      );

      $args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );

      ?>
      <?php if ( comments_open() ) : ?>
        <?php do_action( 'comment_form_before' ); ?>
        <div id="respond">
          <h3 id="reply-title"><?php comment_form_title( $args['title_reply'], $args['title_reply_to'] ); ?> <small><?php cancel_comment_reply_link( $args['cancel_reply_link'] ); ?></small></h3>
          <?php if ( get_option( 'comment_registration' ) && !is_user_logged_in() ) : ?>
            <?php echo $args['must_log_in']; ?>
            <?php do_action( 'comment_form_must_log_in_after' ); ?>
          <?php else : ?>
            <form action="<?php echo site_url( '/wp-comments-post.php' ); ?>" method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>">
              <?php do_action( 'comment_form_top' ); ?>

              <?php if ( is_user_logged_in() ) : ?>
                <?php echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity ); ?>
                <?php do_action( 'comment_form_logged_in_after', $commenter, $user_identity ); ?>
              <?php endif; ?>

              <?php echo $args['comment_notes_before']; ?>
              <?php
              do_action( 'comment_form_before_fields' );
              foreach ( (array) $args['fields'] as $name => $field ) {
                echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";
              }
              do_action( 'comment_form_after_fields' );
              ?>

              <?php echo apply_filters( 'comment_form_field_comment', $args['comment_field'] ); ?>
              <?php echo $args['comment_notes_after']; ?>
              <p class="form-submit">
                <input name="submit" type="submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" value="<?php echo esc_attr( $args['label_submit'] ); ?>" />
                <?php comment_id_fields( $post_id ); ?>
              </p>
              <?php do_action( 'comment_form', $post_id ); ?>
            </form>
          <?php endif; ?>
        </div><!-- #respond -->
        <?php do_action( 'comment_form_after' ); ?>
      <?php else : ?>
        <?php do_action( 'comment_form_comments_closed' ); ?>
      <?php endif; ?>
      <?php
    }
  }
endif;