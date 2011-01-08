/**
 * WP-Property Global Admin Scripts
 *
 * This file is included on all back-end pages, so extra care needs be taken to avoid conflicts
 *
*/

jQuery(document).ready(function() {

	// Add row to UD UI Dynamic Table
	jQuery(".wpp_add_row").live("click" , function() {

		var table = jQuery(this).parents('.ud_ui_dynamic_table');
		var table_id = jQuery(table).attr("id");

		// Clone last row
		var cloned = jQuery(".wpp_dynamic_table_row:last", table).clone();
		
		// Insert new row after last one
		jQuery(cloned).appendTo(table);

		// Get Last row to update names to match slug
		var added_row = jQuery(".wpp_dynamic_table_row:last", table);

		// Display row ust in case
		jQuery(added_row).show();

		// Blank out all values
		jQuery("input[type=text]", added_row).val('');
		jQuery("input[type=checkbox]", added_row).attr('checked', false);
		
		// Unset 'new_row' attribute
		jQuery(added_row).attr('new_row', 'true');
 

	});

	// When the .slug_setter input field is modified, we update names of other elements in row
	jQuery(".wpp_dynamic_table_row[new_row=true] input.slug_setter").live("change", function() {

		//console.log('Name changed.');
	
		var this_row = jQuery(this).parents('tr.wpp_dynamic_table_row');

		// Slug of row in question
		var old_slug = jQuery(this_row).attr('slug');
		
		// Get data from input.slug_setter
		var new_slug = jQuery(this).val();

		// Conver into slug
		var new_slug = wpp_create_slug(new_slug);
		//console.log("New slug: "  + new_slug);

		// Don't allow to blank out slugs
		if(new_slug == "")
			return;

		// If slug input.slug exists in row, we modify it
		jQuery(".slug" , this_row).val(new_slug);

		// Update row slug
		jQuery(this_row).attr('slug', new_slug);
		
		// Cycle through all child elements and fix names
		jQuery('input', this_row).each(function(element) {
			var old_name = jQuery(this).attr('name');
			var new_name =  old_name.replace(old_slug,new_slug);

			var old_id = jQuery(this).attr('id');
			var new_id =  old_id.replace(old_slug,new_slug);
			
			// Update to new name
			jQuery(this).attr('name', new_name);
			jQuery(this).attr('id', new_id);
			

		});
		
		// Cycle through labels too
			jQuery('label', this_row).each(function(element) {
			var old_for = jQuery(this).attr('for');
			var new_for =  old_for.replace(old_slug,new_slug);
			
			// Update to new name
			jQuery(this).attr('for', new_for);
			

		});
				
		/*
		jQuery('.wpp_width input', this_row).attr("name", "wpp_settings[image_sizes][" + new_slug + "][width]");
		jQuery('.wpp_height input', this_row).attr("name", "wpp_settings[image_sizes][" + new_slug + "][height]");
		*/
	});




	// Delete image type
	jQuery(".wpp_delete_row").live("click", function() {

		
		var parent = jQuery(this).parents('tr.wpp_dynamic_table_row');
		var row_count = jQuery(".wpp_delete_row:visible").length;
 
	
		
		// Blank out all values
		jQuery("input[type=text]", parent).val('');
		jQuery("input[type=checkbox]", parent).attr('checked', false);
		
		// Don't hide last row
		if(row_count > 1) {
			jQuery(parent).hide();
			jQuery(parent).remove();	
		}
	});

	
});


function wpp_create_slug(slug) {


		slug = slug.replace(/[^a-zA-Z0-9_\s]/g,"");
		slug = slug.toLowerCase();
		slug = slug.replace(/\s/g,'_');

		return slug;
}