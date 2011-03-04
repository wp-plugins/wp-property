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
?>

    <div class="properties-handling">
        <div class="ajax_loader" id="ajax_loader_<?php echo $unique; ?>"><?php _e('Loading','wpp') ?></div>

    <?php
        if(substr_count($query, 'pagi') && ($total > $per_page) ){ ?>
            <div class="properties_pagination" id="properties_pagination_<?php echo $unique; ?>">
                <a class="nav prev disabled" href="javascript:;"><?php _e('Prev'); ?></a>
                <ul>
                    <?php
                    $page_number = 0;
                    for($i=0; $i<$total; $i++) {
                        if(($i%$per_page) == 0) {
                            ++$page_number;
                            ?>
                            <li><a href="javascript:;" class="page_button <?php echo ($page_number == 1)?"selected":""; ?>" id="page_<?php echo $unique; ?>_<?php echo $i; ?>"><?php echo $page_number; ?></a></li>
                            <?php
                        }
                    } ?>
                </ul>
                <a class="nav next" href="javascript:;"><?php _e('Next'); ?></a>
                <div class="clear"></div>
            </div>
    <?php }

        if($sorter == 'on' && !empty($sortable_attrs)) : ?>
            <form class="properties_sorter" id="properties_sorter_<?php echo $unique; ?>" action="">
                <label for="sort_by_<?php echo $unique; ?>"><?php _e('Order By:','wpp') ?></label>
                <select name="sort_by" class="sort_by" id="sort_by_<?php echo $unique; ?>">
                    <?php foreach($sortable_attrs as $slug => $label) : ?>
                    <option value="<?php echo $slug; ?>" <?php echo ($sort_by == $slug)?'selected="selected"':''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="sort_order_<?php echo $unique; ?>" class="sort_order <?php echo (!empty($sort_order)?$sort_order:'ASC'); ?>"></div>
                <div class="clear"></div>
            </form>
        <?php endif; ?>

    </div>

    <script type="text/javascript">
        var pagebox_<?php echo $unique; ?> = jQuery('#properties_pagination_<?php echo $unique; ?>');
        var sorterbox_<?php echo $unique; ?> = jQuery('#properties_sorter_<?php echo $unique; ?>');
        var ajaxloader_<?php echo $unique; ?> = jQuery('#ajax_loader_<?php echo $unique; ?>');

        var params_<?php echo $unique; ?> = {
            action: 'wpp_property_overview_pagination',
            ajax_call:'true'
			<?php if(!empty($template)) {
			    echo ", template: '$template'";
			}
            $data = explode('&',$query);
            foreach($data as $attr => $value ){
                if ($value == '')continue;
                $value_data = explode('=',$value);
                if($value_data[0]=='pagi' || $value_data[0] == 'pagination') continue;
                echo ", $value_data[0]:'$value_data[1]'";
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
                        jQuery('.wpp_row_view').html(data);
                    }else{
                        var properties = jQuery(data).find('.property_div');
                        var wpp_row_view = jQuery('#wpp_shortcode_<?php echo $unique; ?> .wpp_row_view');
                        wpp_row_view.html('');
                        if(properties.length > 0) {
                            properties.each(function(i, el){
                                wpp_row_view.append(el);
                            });
                            wpp_row_view.find('a.fancybox_image').fancybox({
                                'transitionIn'	:	'elastic',
                                'transitionOut'	:	'elastic',
                                'speedIn'	:	600, 
                                'speedOut'	:	200, 
                                'overlayShow'	:	false
                            });
                        }
                    }
                }


            });

        }

        if(sorterbox_<?php echo $unique; ?>.length > 0) {
            sorterbox_<?php echo $unique; ?>.find('#sort_by_<?php echo $unique; ?>').change(function(){
                params_<?php echo $unique; ?>.sort_by = jQuery(this).val();
                getProperties_<?php echo $unique; ?>();
            });

            sorterbox_<?php echo $unique; ?>.find('#sort_order_<?php echo $unique; ?>').click(function(){
                var el = jQuery(this);
                if (el.hasClass('ASC')) {
                    el.removeClass('ASC');
                    el.addClass('DESC');
                    params_<?php echo $unique; ?>.sort_order = 'DESC';
                } else {
                    el.removeClass('DESC');
                    el.addClass('ASC');
                    params_<?php echo $unique; ?>.sort_order = 'ASC';
                }
                getProperties_<?php echo $unique; ?>();
            });
        }

        if(pagebox_<?php echo $unique; ?>.length > 0) {

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
                params_<?php echo $unique; ?>.starting_row = id.replace(/page_[\d]{4}_/, '');

                getProperties_<?php echo $unique; ?>();

                return false;
            });
        }
    </script>