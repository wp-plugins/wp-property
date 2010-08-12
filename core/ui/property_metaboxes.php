<?php

class WPP_UI {

function page_attributes_meta_box($post) {
	$post_type_object = get_post_type_object($post->post_type);
	if ( $post_type_object->hierarchical ) {
		$pages = wp_dropdown_pages(array('post_type' => $post->post_type, 'exclude_tree' => $post->ID, 'selected' => $post->post_parent, 'name' => 'parent_id', 'show_option_none' => __('(no parent)'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0));
		if ( ! empty($pages) ) {
?>



<p><strong><?php _e('Parent') ?></strong></p>
<label class="screen-reader-text" for="parent_id"><?php _e('Parent') ?></label>
<?php echo $pages; ?>
<?php
		} // end empty pages check
	} // end hierarchical check.
	if ( 'page' == $post->post_type && 0 != count( get_page_templates() ) ) {
		$template = !empty($post->page_template) ? $post->page_template : false;
		?>
<p><strong><?php _e('Template') ?></strong></p>
<label class="screen-reader-text" for="page_template"><?php _e('Page Template') ?></label><select name="page_template" id="page_template">
<option value='default'><?php _e('Default Template'); ?></option>
<?php page_template_dropdown($template); ?>
</select>
<?php
	} ?>
<p><strong><?php _e('Order') ?></strong></p>
<p><label class="screen-reader-text" for="menu_order"><?php _e('Order') ?></label><input name="menu_order" type="text" size="4" id="menu_order" value="<?php echo esc_attr($post->menu_order) ?>" /></p>
<p><?php if ( 'page' == $post->post_type ) _e( 'Need help? Use the Help tab in the upper right of your screen.' ); ?></p>
<?php
}



function metabox_meta($object) {
	global $wp_properties, $wpdb;
 
		
	$property_meta = $wp_properties['property_meta'];
	$property_stats = $wp_properties['property_stats'];

	$property = WPP_F::get_property($object->ID);
	
	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			
			// Done with PHP but in case of page reloads
			wpp_toggle_attributes();
		
			/*
				Display prefill values.
				Hide "Show common values" link.
				Display "Cancel" button
			*/
			jQuery(".wpp_show_prefill_values").click(function() {
 				var parent_cell = jQuery(this).parents('.wpp_attribute_cell');
				jQuery(this).hide();
				jQuery(this).parent().children('.wpp_prefill_attribute').show();
				jQuery('.wpp_show_prefill_values_cancel', parent_cell).show();
			});			

			/*
				Cancel displaying prefill values.
				Hide "Cancel" button
				Hide all pre-filled values
				Show "Show common values" link.
			*/			
			jQuery(".wpp_show_prefill_values_cancel").click(function() {	
				jQuery(this).hide();
 				var parent_cell = jQuery(this).parents('.wpp_attribute_cell');
				jQuery('.wpp_prefill_attribute', parent_cell).hide();
				jQuery('.wpp_show_prefill_values', parent_cell).show();
				
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
				
				var property_type = jQuery("#wpp_meta_property_type option:selected").val();
				
				if(property_type == "")
					return;
					
				<?php if(count($wp_properties['hidden_attributes']) < 1) : ?>
					return;
				<?php else: ?>
				
					// Show all fields
					jQuery(".wpp_attribute_row").removeClass('disabled_row');
				
					switch(property_type) {
					<?php foreach($wp_properties['hidden_attributes'] as $property_type => $hidden_values): ?>
							
						case '<?php echo $property_type; ?>':
							<?php foreach($hidden_values as $value): ?>
							jQuery(".wpp_attribute_row_<?php echo $value; ?>").addClass('disabled_row');
							<?php endforeach; ?>
						break;

				
					<?php endforeach; ?>
					}
				<?php endif; ?>
				
			}
		
		});
	</script>
	
	
	<table class="widefat">

	<?php 
	$pages = wp_dropdown_pages(array('post_type' => 'property', 'exclude_tree' => $object->ID, 'selected' => $object->post_parent, 'name' => 'parent_id', 'show_option_none' => __('(no parent)'), 'sort_column'=> 'menu_order, post_title', 'echo' => 0)); 
	if(!empty($pages)): ?>

	<tr class="wpp_attribute_row_parent wpp_attribute_row <?php if(in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) echo 'disabled_row;'; ?>">
	<th>Falls Under</th>
	<td><?php echo $pages; ?></td>
	</tr>
	<?php endif; ?>
	
		<tr class="wpp_attribute_row_type wpp_attribute_row <?php if(in_array('type', $wp_properties['hidden_attributes'][$property['property_type']])) echo 'disabled_row;'; ?>">
			<th>Property Type</th>
			<td> 
			<?php
			// Get property types
				
				
			?>
				<select id="wpp_meta_property_type" name="wpp_data[meta][property_type]" id="property_type">
					<option value=""></option>
					<?php foreach($wp_properties['property_types'] as $slug => $label): ?> 
					<option <?php if(get_post_meta($object->ID, 'property_type', true) == $slug) echo "SELECTED"; ?> value="<?php echo $slug; ?>"><?php echo $label; ?></option>
					<?php endforeach; ?>
					</select> 
				<?php if(!empty($wp_properties['descriptions']['property_type'])): ?>
					<span class="description"><?php echo $wp_properties['descriptions']['property_type']; ?></span>
				<?php endif; ?>
				
				
				
			</td>
		</tr>
		
		<?php foreach($property_stats as $slug => $label): ?>
			<tr class="wpp_attribute_row wpp_attribute_row_<?php echo $slug; ?> <?php if(in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) echo 'disabled_row;'; ?>">
				<th><?php echo $label; ?></th>
				<td class="wpp_attribute_cell">
				
					<span class="disabled_message"><?php echo $label; ?> attribute field is not used for with property type.</span>
					<input type="text" id="wpp_meta_<?php echo $slug; ?>" name="wpp_data[meta][<?php echo $slug; ?>]"  class="text-input" value="<?php echo get_post_meta($object->ID, $slug, true); ?>" />
					
					<?php 
					// Get pre-filed meta data
					$common_values = WPP_F::get_all_attribute_values($slug); 
					
					if($common_values): ?>
						<span class="wpp_prefill_values clearfix">
							<span class="wpp_show_prefill_values wpp_link">Show common values.</span>
							<?php foreach($common_values as $meta): ?>
							<span class="wpp_prefill_attribute"><?php echo($meta); ?></span>
							<?php endforeach; ?>
							<span class="wpp_show_prefill_values_cancel wpp_subtle_link hidden">Cancel.</span>
						</span>
					<?php endif; ?>

					<?php if(!empty($wp_properties['descriptions'][$slug])): ?>
						<span class="description"><?php echo $wp_properties['descriptions'][$slug]; ?></span>
					<?php endif; ?>
					
					<?php do_action('wpp_ui_after_attribute_' . $slug, $object->ID); ?>
					
					
					</td>
			</tr>
		<?php endforeach; ?>
	
		<?php foreach($property_meta as $slug => $label): ?>
			<tr class="wpp_attribute_row wpp_attribute_row_<?php echo $slug; ?> <?php if(in_array('parent', $wp_properties['hidden_attributes'][$property['property_type']])) echo 'disabled_row;'; ?>">
				<th><?php echo $label; ?></th>
				<td>
						<span class="disabled_message"><?php echo $label; ?> attribute field is not used for with property type.</span>

					<textarea name="wpp_data[meta][<?php echo $slug; ?>]"><?php echo get_post_meta($object->ID, $slug, true); ?></textarea>
					<?php if(!empty($wp_properties['descriptions'][$slug])): ?>
						<span class="description"><?php echo $wp_properties['descriptions'][$slug]; ?></span>
					<?php endif; ?>					
				</td>
			</tr>
		<?php endforeach; ?>
		
	</table>
	
	<?php
}
}
?>