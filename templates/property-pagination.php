<?php 
/**
 * WP-Property Overview Pagination (Sorter) Block Template
 *
 * To customize this file, copy it into your theme directory, and the plugin will
 * automatically load your version.
 *
 * You can also customize it based on property type.
 *
*/
if ($bottom_pagenation_flag)
  $tmp_unique = $unique == $top_page_unique ? $bottom_page_unique : $top_page_unique;
else
  $tmp_unique = $unique;

 ?>
    <div class="properties-handling">
        <div class="ajax_loader" id="ajax_loader_<?php echo $unique; ?>"><span><?php _e('Loading','wpp') ?></span></div>

       <?php if(substr_count($query, 'pagi') && ($total > $per_page) && $pagination != 'off') : ?>
            <div class="properties_pagination" id="properties_pagination_<?php echo $unique; ?>">
                <a class="nav prev disabled" href="javascript:;"><?php _e('Prev'); ?></a>
                <ul>
                    <?php
                    $page_number = 0;
                    for($i=0; $i<$total; $i++) {
                  if(($i % $per_page) == 0) {
                    ++$page_number;
            ?>
              <li>
                <a href="#s<?php echo $unique; ?>p<?php echo $page_number; ?>" class="page_button <?php echo ($page_number == 1)?"selected":""; ?>" id="page_<?php echo $unique; ?>_<?php echo $i; ?>">
                  <?php echo $page_number; ?>
                </a>
              </li>
            <?php
                  }
                } ?>
          </ul>
          <a class="nav next" href="javascript:;"><?php _e('Next'); ?></a>
                <div class="clear"></div>
            </div>
        <?php endif; ?>

        <?php if($sorter == 'on' && !empty($sortable_attrs)) : ?>
            <form class="properties_sorter" id="properties_sorter_<?php echo $unique; ?>" action="">
                <label for="sort_by_<?php echo $unique; ?>"><?php _e('Order By:','wpp') ?></label>
                <select name="sort_by" class="sort_by" id="sort_by_<?php echo $unique; ?>">
                    <?php foreach($sortable_attrs as $slug => $label) : ?>
                    <option value="<?php echo $slug; ?>" <?php echo ($sort_by == $slug)?'selected="selected"':''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="sort_order_<?php echo $unique; ?>" class="sort_order <?php echo (!empty($sort_order)?$sort_order:'ASC'); ?>"></div>
                <input type="hidden" name="tmp_sort_order_<?php echo $unique;?>" id="tmp_sort_order_<?php echo $unique;?>" value="<?php echo (!empty($sort_order)?$sort_order:'ASC'); ?>" />
                <div class="clear"></div>
            </form>
        <?php endif; ?>

    </div>

    <script type="text/javascript">
        var pagebox_<?php echo $unique; ?> = jQuery('#properties_pagination_<?php echo $unique; ?>');
        var sorterbox_<?php echo $unique; ?> = jQuery('#properties_sorter_<?php echo $unique; ?>');
        var ajaxloader_<?php echo $unique; ?> = jQuery('#ajax_loader_<?php echo $unique; ?>');
        <?php if ($bottom_pagenation_flag) : ?>
          var tmp_pagebox_<?php echo $tmp_unique; ?> = jQuery('#properties_pagination_<?php echo $tmp_unique; ?>');
          var tmp_sorterbox_<?php echo $tmp_unique; ?> = jQuery('#properties_sorter_<?php echo $tmp_unique; ?>');
          var tmp_ajaxloader_<?php echo $tmp_unique; ?> = jQuery('#ajax_loader_<?php echo $tmp_unique; ?>');
        <?php endif; ?>

        var params_<?php echo $unique; ?> = {
            action: 'wpp_property_overview_pagination',
            ajax_call:'true',
            url_encoded:'true'
            <?php if(!empty($template)) {
                echo ", template: '$template'";
            }
            //$data = explode('&',$query);
            $data = WPP_F::split_query_string( $query );
            foreach($data as $attr => $value ){
                if ($value == '')continue;
                $value_data = explode('=',$value);
                if($value_data[0]=='pagi' || $value_data[0] == 'pagination') continue;
                echo ", $value_data[0]:'".urlencode($value_data[1])."'";
            } ?>
        };

        function getProperties_<?php echo $unique; ?>() {
            ajaxloader_<?php echo $unique; ?>.show();
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: params_<?php echo $unique; ?>,
                success: function(data) {
                    ajaxloader_<?php echo $unique; ?>.hide();
                   
                    
                    if(data.indexOf('wpp_nothing_found') > 0){
                        jQuery('.wpp_property_view_result, .wpp_row_view').html(data);
                    }else{
                        var properties = jQuery(data).find('.property_div');
                        var wpp_property_view_result = jQuery('#wpp_shortcode_<?php echo $top_page_unique; ?> .wpp_property_view_result, #wpp_shortcode_<?php echo $top_page_unique; ?> .wpp_row_view');
                        var do_loop = true;
                        wpp_property_view_result.html('');
                        if(properties.length > 0) {
                            properties.each(function(i, el){
                                wpp_property_view_result.append(el);
                                if(properties.length - 1 == i) do_loop = false;
                            });
                            while(true){
                                if(!do_loop){
                                    wpp_property_view_result.find('a.fancybox_image').fancybox({
                                        'transitionIn'	:	'elastic',
                                        'transitionOut'	:	'elastic',
                                        'speedIn'	:	600, 
                                        'speedOut'	:	200, 
                                        'overlayShow'	:	false
                                    });
                                    <?php do_action('wpp_js_on_property_overview_display', $template, 'ajax_result'); ?>
                                    break;
                                }
                            }
                        }
                    }
                }
            });
        }

        if(sorterbox_<?php echo $unique; ?>.length > 0) {
          params_<?php echo $unique; ?>.sort_by = sorterbox_<?php echo $unique; ?>.find('#sort_by_<?php echo $unique; ?>').val();
          var current_sort_state = sorterbox_<?php echo $unique; ?>.find('#tmp_sort_order_<?php echo $unique; ?>').val();
          var sort_state_element = sorterbox_<?php echo $unique; ?>.find('#sort_order_<?php echo $unique; ?>');
          params_<?php echo $unique; ?>.sort_order = current_sort_state;
          if (sort_state_element.hasClass(current_sort_state) == false){
            if (current_sort_state == 'ASC') {
              sort_state_element.removeClass('DESC');
              sort_state_element.addClass('ASC');
            } else {
              sort_state_element.removeClass('ASC');
              sort_state_element.addClass('DESC');
            }
          }

          sorterbox_<?php echo $unique; ?>.find('#sort_by_<?php echo $unique; ?>').change(function(){
              <?php if ($bottom_pagenation_flag) : ?>
                sorterbox_<?php echo $tmp_unique; ?>.find('#sort_by_<?php echo $tmp_unique; ?>').val(jQuery(this).val());
              <?php endif; ?>
              params_<?php echo $unique; ?>.sort_by = jQuery(this).val();

              getProperties_<?php echo $unique; ?>();
          });

          sorterbox_<?php echo $unique; ?>.find('#sort_order_<?php echo $unique; ?>').click(function(){
                var el = jQuery(this);
                if (el.hasClass('ASC')) {
                    el.removeClass('ASC');
                    el.addClass('DESC');
                    params_<?php echo $unique; ?>.sort_order = 'DESC';
                    sorterbox_<?php echo $unique; ?>.find('#tmp_sort_order_<?php echo $unique; ?>').val('DESC');
              } else {
                  el.removeClass('DESC');
                  el.addClass('ASC');
                  params_<?php echo $unique; ?>.sort_order = 'ASC';
                  sorterbox_<?php echo $unique; ?>.find('#tmp_sort_order_<?php echo $unique; ?>').val('ASC');
              }
              <?php if ($bottom_pagenation_flag) : ?>
                var tmp_el = sorterbox_<?php echo $tmp_unique; ?>.find('#sort_order_<?php echo $tmp_unique; ?>');
                 if (tmp_el.hasClass('ASC')) {
                    tmp_el.removeClass('ASC');
                    tmp_el.addClass('DESC');
                } else {
                    tmp_el.removeClass('DESC');
                    tmp_el.addClass('ASC');
                }
              <?php endif; ?>
              getProperties_<?php echo $unique; ?>();
          });
        }

        if(pagebox_<?php echo $unique; ?>.length > 0) {
            
            // Address handler
            if(typeof jQuery.address != 'undefined') {
                var unique = <?php echo $unique; ?>;
                var startPage = true;
                jQuery.address.init(function(event) {
                    jQuery('#properties_pagination_<?php echo $unique; ?> ul a').address();
                }).change(function(event) {
                    var hash = event.value;
                    if(hash == '/') {
                        if(startPage == true) {
                            startPage = false;
                            return false;
                        }
                        hash += 's100p1';
                    }
                    jQuery('a[href='+ hash.replace('/', '#') +']').click();
                }).history(true);
            }
            
            params_<?php echo $unique; ?>.pagination = true;
            params_<?php echo $unique; ?>.starting_row = '<?php echo $offset; ?>';
            params_<?php echo $unique; ?>.per_page = '<?php echo $per_page; ?>';

            pagebox_<?php echo $unique; ?>.find('a.nav').click(function(){
                var nav = jQuery(this);

                if(nav.hasClass('disabled'))
                    return false;

                var selected_page = pagebox_<?php echo $unique; ?>.find('li a.page_button.selected');

                if(nav.hasClass('prev')) {
                    var prevEl = selected_page.parent().prev();
                    prevEl.children().click();
                } else {
                    var nextEl = selected_page.parent().next();
                    nextEl.children().click();
                }
                return false;
            });

            pagebox_<?php echo $unique; ?>.find('li a.page_button').click(function(){
                var current_page = jQuery(this);

                if(current_page.hasClass('selected'))
                    return false;

                var pages = pagebox_<?php echo $unique; ?>.find('li a.page_button');

                pages.each(function(i, el){
                    jQuery(el).removeClass('selected');
                });
                current_page.addClass('selected');

                if(current_page.parent().next().length == 0) {
                    pagebox_<?php echo $unique; ?>.find('a.nav.next').removeClass('enabled');
                    pagebox_<?php echo $unique; ?>.find('a.nav.next').addClass('disabled');
                } else {
                    pagebox_<?php echo $unique; ?>.find('a.nav.next').removeClass('disabled');
                    pagebox_<?php echo $unique; ?>.find('a.nav.next').addClass('enabled');
                }

                if(current_page.parent().prev().length == 0) {
                    pagebox_<?php echo $unique; ?>.find('a.nav.prev').removeClass('enabled');
                    pagebox_<?php echo $unique; ?>.find('a.nav.prev').addClass('disabled');
                } else {
                    pagebox_<?php echo $unique; ?>.find('a.nav.prev').removeClass('disabled');
                    pagebox_<?php echo $unique; ?>.find('a.nav.prev').addClass('enabled');
                }

                var id = current_page.attr('id');
                params_<?php echo $unique; ?>.starting_row = id.replace(/page_[\d]{3,4}_/, '');
                <?php if ($bottom_pagenation_flag) : ?>
                  var tmp_pages = pagebox_<?php echo $tmp_unique; ?>.find('li a.page_button');
                  tmp_pages.each(function(i, el){
                    jQuery(el).removeClass('selected');
                    if (jQuery(el).html() == current_page.html()){
                      jQuery(el).addClass('selected');
                    }
                  });
                <?php endif; ?>
                getProperties_<?php echo $unique; ?>();

                return false;
            });
        }
    </script>