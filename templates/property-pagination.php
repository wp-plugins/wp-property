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
        <div id="ajax_loader"><?php _e('Loading','wpp') ?></div>

    <?php
        if(substr_count($query, 'pagi') && ($total > $per_page) ){ ?>
            <div id="properties_pagination">
                <a class="nav prev disabled" href="javascript:;"><?php _e('Prev'); ?></a>
                <ul>
                    <?php
                    $page_number = 0;
                    for($i=0; $i<$total; $i++) {
                        if(($i%$per_page) == 0) {
                            ++$page_number;
                            ?>
                            <li><a href="javascript:;" class="page_button <?php echo ($page_number == 1)?"selected":""; ?>" id="<?php echo $i; ?>"><?php echo $page_number; ?></a></li>
                            <?php
                        }
                    } ?>
                </ul>
                <a class="nav next" href="javascript:;"><?php _e('Next'); ?></a>
                <div class="clear"></div>
            </div>
    <?php }

        if(!empty($sorter) && !empty($sortable_attrs)) : ?>
            <form id="properties_sorter" action="">
                <label for="sort_by"><?php _e('Order By:','wpp') ?></label>
                <select name="sort_by" id="sort_by">
                    <?php foreach($sortable_attrs as $slug => $label) : ?>
                    <option value="<?php echo $slug; ?>" <?php echo ($sort_by == $slug)?'selected="selected"':''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="sort_order" class="<?php echo (!empty($sort_order)?$sort_order:'ASC'); ?>"></div>
                <div class="clear"></div>
            </form>
        <?php endif; ?>

    </div>

    <script type="text/javascript">
        var pagebox = jQuery('#properties_pagination');
        var sorterbox = jQuery('#properties_sorter');
        var ajaxloader = jQuery('#ajax_loader');


        var params = {
            action: 'wpp_property_overview_pagination',
            ajax_call:'true',
            <?php
            $data = explode('&',$query);
            foreach($data as $attr => $value ){
                if ($value == '')continue;
                $value_data = explode('=',$value);
                if($value_data[0]=='pagi' || $value_data[0] == 'pagination') continue;
                echo "$value_data[0]: '$value_data[1]'";
                if(end($data) !== $value) echo ',';
            } ?>
        };

        function getProperties() {
            ajaxloader.show();
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: params,
                success: function(data) {
                    ajaxloader.hide();
                    if(data.indexOf('wpp_nothing_found')){
                        jQuery('.wpp_row_view').html(data);
                    }else{
                        var properties = jQuery(data).find('.property_div');
                        jQuery('.wpp_row_view').html('');
                        if(properties.length > 0) {
                            properties.each(function(i, el){
                                jQuery('.wpp_row_view').append(el);

                            });

                        }
                    }
                }


            });

        }

        if(sorterbox.length > 0) {
            sorterbox.find('#sort_by').change(function(){
                params.sort_by = jQuery(this).val();
                getProperties();
            });

            sorterbox.find('#sort_order').click(function(){
                var el = jQuery(this);
                if (el.hasClass('ASC')) {
                    el.removeClass('ASC');
                    el.addClass('DESC');
                    params.sort_order = 'DESC';
                } else {
                    el.removeClass('DESC');
                    el.addClass('ASC');
                    params.sort_order = 'ASC';
                }
                getProperties();
            });
        }

        if(pagebox.length > 0) {

            params.pagination = true;
            params.starting_row = '<?php echo $offset; ?>';
            params.per_page = '<?php echo $per_page; ?>';

            pagebox.find('a.nav').click(function(){
                var nav = jQuery(this);

                if(nav.hasClass('disabled'))
                    return false;

                var selected_page = pagebox.find('li a.page_button.selected');

                if(nav.hasClass('prev')) {
                    var prevEl = selected_page.parent().prev();
                    prevEl.children().click();
                } else {
                    var nextEl = selected_page.parent().next();
                    nextEl.children().click();
                }
                return false;
            });

            pagebox.find('li a.page_button').click(function(){
                var current_page = jQuery(this);

                if(current_page.hasClass('selected'))
                    return false;

                var pages = pagebox.find('li a.page_button');

                pages.each(function(i, el){
                    jQuery(el).removeClass('selected');
                });
                current_page.addClass('selected');

                if(current_page.parent().next().length == 0) {
                    pagebox.find('a.nav.next').removeClass('enabled');
                    pagebox.find('a.nav.next').addClass('disabled');
                } else {
                    pagebox.find('a.nav.next').removeClass('disabled');
                    pagebox.find('a.nav.next').addClass('enabled');
                }

                if(current_page.parent().prev().length == 0) {
                    pagebox.find('a.nav.prev').removeClass('enabled');
                    pagebox.find('a.nav.prev').addClass('disabled');
                } else {
                    pagebox.find('a.nav.prev').removeClass('disabled');
                    pagebox.find('a.nav.prev').addClass('enabled');
                }

                params.starting_row = current_page.attr('id');

                getProperties();

                return false;
            });
        }
    </script>