/**
 * WP-Property Global Admin Scripts
 *
 * This file is included on all back-end pages, so extra care needs be taken to avoid conflicts
 *
*/


/*
 * Basic e-mail validation
 */
function wpp_validate_email(address) {
   var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

   if(reg.test(address) == false) {
      return false;
   } else {
    return true;
   }
}

/*
 * Bind ColorPicker with input fields '.wpp_input_colorpicker'
 * @param object instance. jQuery object
 */
var bindColorPicker = function(instance){
  if(typeof window.jQuery.prototype.ColorPicker == 'function') {
    if(!instance) {
      instance = jQuery('body');
    }
    jQuery('.wpp_input_colorpicker', instance).ColorPicker({
      onSubmit: function(hsb, hex, rgb, el) {
        jQuery(el).val('#' + hex);
        jQuery(el).ColorPickerHide();
      },
      onBeforeShow: function () {
        jQuery(this).ColorPickerSetColor(this.value);
      }
    })
    .bind('keyup', function(){
      jQuery(this).ColorPickerSetColor(this.value);
    });
  }
}

/*
 * Updates Row field names
 * @param object instance. DOM element
 * @param boolean allowRandomSlug. Determine if Row can contains random slugs.
 */
var updateRowNames = function(instance, allowRandomSlug) {
  if(typeof instance == 'undefined') {
    return false;
  }
  if(typeof allowRandomSlug == 'undefined') {
    var allowRandomSlug = false;
  }

  var this_row = jQuery(instance).parents('tr.wpp_dynamic_table_row');
  // Slug of row in question
  var old_slug = jQuery(this_row).attr('slug');
  // Get data from input.slug_setter
  var new_slug = jQuery(instance).val();
  // Convert into slug
  var new_slug = wpp_create_slug(new_slug);

  // Don't allow to blank out slugs
  if(new_slug == "") {
    if(allowRandomSlug) {
      new_slug = 'random_' + Math.floor(Math.random()*1000);
    } else {
      return;
    }
  }

  // If slug input.slug exists in row, we modify it
  jQuery(".slug" , this_row).val(new_slug);
  // Update row slug
  jQuery(this_row).attr('slug', new_slug);

  // Cycle through all child elements and fix names
  jQuery('input,select,textarea', this_row).each(function(i,e) {
    var old_name = jQuery(e).attr('name');
    if (typeof old_name != 'undefined') {
      var new_name =  old_name.replace(old_slug,new_slug);
      if(jQuery(e).attr('id')) {
        var old_id = jQuery(e).attr('id');
        var new_id =  old_id.replace(old_slug,new_slug);
      }
      // Update to new name
      jQuery(e).attr('name', new_name);
      jQuery(e).attr('id', new_id);
    }
  });

  // Cycle through labels too
  jQuery('label', this_row).each(function(i,e) {

    if(jQuery(e).attr('id')) {
      var old_for = jQuery(e).attr('for');
      var new_for =  old_for.replace(old_slug,new_slug);
      // Update to new name
      jQuery(e).attr('for', new_for);
    }

  });
}

jQuery(document).ready(function() {
  jQuery(".wpp_show_advanced").live("click", function() {    var wrapper = jQuery(this).parents('tr.wpp_dynamic_table_row');    jQuery('li.wpp_development_advanced_option', wrapper).toggle();  });

  // Toggle wpp_wpp_settings_configuration_do_not_override_search_result_page_
  jQuery("#wpp_wpp_settings_configuration_automatically_insert_overview_").change(function() {

    if(jQuery(this).is(":checked")) {
      jQuery("li.wpp_wpp_settings_configuration_do_not_override_search_result_page_row").hide();

    } else {
      jQuery("li.wpp_wpp_settings_configuration_do_not_override_search_result_page_row").show();


    }

  });

  // Bind (Set) ColorPicker
  bindColorPicker();

  // Add row to UD UI Dynamic Table
  jQuery(".wpp_add_row").live("click" , function() {
    wpp_add_row(this);
  });

  // When the .slug_setter input field is modified, we update names of other elements in row
  jQuery(".wpp_dynamic_table_row[new_row=true] input.slug_setter, .wpp_dynamic_table_row[new_row=true] select.slug_setter").live("change", function() {
    //console.log('Name changed.');
    updateRowNames(this);
    /*
    jQuery('.wpp_width input', this_row).attr("name", "wpp_settings[image_sizes][" + new_slug + "][width]");
    jQuery('.wpp_height input', this_row).attr("name", "wpp_settings[image_sizes][" + new_slug + "][height]");
    */
  });


  // Delete dynamic row
  jQuery(".wpp_delete_row").live("click", function() {
    var parent = jQuery(this).parents('tr.wpp_dynamic_table_row');
    var row_count = jQuery(".wpp_delete_row:visible").length;

    if(jQuery(this).attr('verify_action') == 'true') {
      if(!confirm('Are you sure?'))
        return false;
    }


    // Blank out all values
    jQuery("input[type=text]", parent).val('');
    jQuery("input[type=checkbox]", parent).attr('checked', false);

    // Don't hide last row
    if(row_count > 1) {
      jQuery(parent).hide();
      jQuery(parent).remove();
    }
  });

  jQuery('.wpp_attach_to_agent').live('click', function(){
    var agent_image_id = jQuery(this).attr('id');
    if (agent_image_id != '')
      jQuery('#library-form').append('<input name="wpp_agent_post_id" type="text" value="' + agent_image_id + '" />').submit();
  })
});


function wpp_create_slug(slug) {


    slug = slug.replace(/[^a-zA-Z0-9_\s]/g,"");
    slug = slug.toLowerCase();
    slug = slug.replace(/\s/g,'_');

    return slug;
}

function wpp_add_row(element) {
 
    var auto_increment = false;
    var table = jQuery(element).parents('.ud_ui_dynamic_table');
    var table_id = jQuery(table).attr("id");        
    // Determine if table rows are numeric
    if(jQuery(table).attr('auto_increment') == 'true') {
      var auto_increment = true;
    } else if (jQuery(table).attr('use_random_row_id') == 'true') {
      var use_random_row_id = true;
    } else if (jQuery(table).attr('allow_random_slug') == 'true') {
      var allow_random_slug = true;
    }

    // Clone last row
    var cloned = jQuery(".wpp_dynamic_table_row:last", table).clone();

    // Insert new row after last one
    jQuery(cloned).appendTo(table);

    // Get Last row to update names to match slug
    var added_row = jQuery(".wpp_dynamic_table_row:last", table);

    // Bind (Set) ColorPicker with new fields '.wpp_input_colorpicker'
    bindColorPicker(added_row);
    // Display row just in case
    jQuery(added_row).show();

    // Blank out all values
    jQuery("textarea", added_row).val('');
    jQuery("select", added_row).val('');
    jQuery("input[type=text]", added_row).val('');
    jQuery("input[type=checkbox]", added_row).attr('checked', false);

    // Increment name value automatically
    if(auto_increment) {
      // Cycle through all child elements and fix names
      jQuery('input,select,textarea', added_row).each(function(element) {
      var old_name = jQuery(this).attr('name');

      var matches = old_name.match(/\[(\d{1,2})\]/);

      if (matches) {
        old_count = parseInt(matches[1]);
        new_count = (old_count + 1);
      }
      var new_name =  old_name.replace('[' + old_count + ']','[' + new_count + ']');

      // Update to new name
      jQuery(this).attr('name', new_name);

      });
      
    } else if (use_random_row_id){  
      /* Get the current random id of row */
      var random_row_id = jQuery(added_row).attr('random_row_id');
      var new_random_row_id = Math.floor(Math.random()*1000) 
     
      
     // Cycle through all child elements and fix names
      jQuery('input,select,textarea', added_row).each(function(element) {
      var old_name = jQuery(this).attr('name');
      var new_name =  old_name.replace('[' + random_row_id + ']','[' + new_random_row_id + ']');

      // Update to new name
      jQuery(this).attr('name', new_name);

      });
      
      jQuery(added_row).attr('random_row_id', new_random_row_id);
      
      
    } else if (allow_random_slug){
      // Update Row names
      var slug_setter = jQuery("input.slug_setter", added_row);
      if(slug_setter.length > 0) {
        updateRowNames(slug_setter.get(0), true);
      }
    }

    // Unset 'new_row' attribute
    jQuery(added_row).attr('new_row', 'true');

    // Focus on new element
    jQuery('input.slug_setter', added_row).focus();
    if (callback_function = jQuery(element).attr("callback_function")) {      wpp_call_function(callback_function, window, added_row);    }    
    return added_row;

}  function wpp_call_function(functionName, context, args) {    var args = Array.prototype.slice.call(arguments).splice(2);    var namespaces = functionName.split(".");    var func = namespaces.pop();    for(var i = 0; i < namespaces.length; i++) {      context = context[namespaces[i]];    }    return context[func].apply(this, args);  }