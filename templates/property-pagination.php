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
	<div id="ajax_loader">Loading</div>

<?php if(!empty($props_atts['pagination']) && ($total > $per_page) ): ?>
    <div id="properties_pagination">
    <a class="nav prev disabled" href="javascript:;"><?php echo _e('Prev'); ?></a>
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
    } 
    ?>
    </ul>
    <a class="nav next" href="javascript:;"><?php echo _e('Next'); ?></a>
    <div class="clear"></div>
    </div>
<?php endif; ?>

<?php if(!empty($sorter)) : ?>
	<form id="properties_sorter" action="">
		<label for="sort_by">Order By:</label>
		<select name="sort_by" id="sort_by">menu_order
			<option value="menu_order" <?php echo ($order_by == 'menu_order')?'selected="selected"':''; ?>>Menu Order</option>
			<option value="price" <?php echo ($order_by == 'price')?'selected="selected"':''; ?>>Price</option>
			<option value="bedrooms" <?php echo ($order_by == 'bedrooms')?'selected="selected"':''; ?>>Bedrooms</option>
			<option value="bathrooms" <?php echo ($order_by == 'bathrooms')?'selected="selected"':''; ?>>Bathrooms</option>
			<option value="neighborhood" <?php echo ($order_by == 'neighborhood')?'selected="selected"':''; ?>>Neighborhood</option>
		</select>
		<div id="sort_dir" class="<?php echo (!empty($order_dir)?strtoupper($order_dir):'ASC'); ?>"></div>
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
		<? foreach($props_atts as $index => $value ){ 
		if ($value == ''){continue;}?>
		<?=$index?> : '<?=$value?>',
	<?}?>
	};
	
    function getProperties() {
    	ajaxloader.show();
    	jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            data: params,
            success: function(data) {
            	ajaxloader.hide();
            	var properties = jQuery(data).find('.property_div');
            	jQuery('.wpp_row_view').html('');
    			if(properties.length > 0) {
    				properties.each(function(i, el){
    					jQuery('.wpp_row_view').append(el);
	
    				});
    				
    			}
            }
           
 
        });
        
    }
    
    if(sorterbox.length > 0) {
    	sorterbox.find('#sort_by').change(function(){
    		params.order_by = jQuery(this).val();
    		getProperties();
        });

    	sorterbox.find('#sort_dir').click(function(){
    		var el = jQuery(this);
        	if (el.hasClass('ASC')) {
				el.removeClass('ASC');
				el.addClass('DESC');
				params.order_dir = 'DESC';
        	} else {
        		el.removeClass('DESC');
				el.addClass('ASC');
				params.order_dir = 'ASC';
        	}
        	getProperties();
        });
    }

	if(pagebox.length > 0) {

		params.pagination = true;
		params.starting_row = '<?php echo $starting_row; ?>';
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