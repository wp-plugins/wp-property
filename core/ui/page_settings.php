<?php
/*
echo "<pre>";
echo "</pre>";
*/
?>

 <script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#wpp_settings_tabs").tabs({ cookie: { expires: 30 } });


	// Delete image type
	jQuery(".wpp_image_table_add_row").click(function() {

		var cloned = jQuery(".wpp_image_row:last").clone();
		var new_name = 'new_image';

		jQuery(cloned).insertAfter('.wpp_image_row:last');

		var added_row = jQuery(".wpp_image_row:last");

		jQuery(added_row).show();
		jQuery("input", added_row).val('');

		jQuery('.wpp_width input', added_row).attr("name", "wpp_settings[image_sizes][" + new_name + "][width]");
		jQuery('.wpp_height input', added_row).attr("name", "wpp_settings[image_sizes][" + new_name + "][height]");



	});

	// Change the names of input fields when slug name is changed (messy)
	jQuery(".wpp_image_row .wpp_slug input").live("change", function() {

		var parent = jQuery(this).parents('tr.wpp_image_row');
		var new_name = jQuery(this).val();

		// Don't allow to blank out image names
		if(new_name == "")
			return;

		// Make sure its a slug
		new_name = new_name.replace(/[^a-zA-Z0-9_\s]/g,"");
		new_name = new_name.toLowerCase();
		new_name = new_name.replace(/\s/g,'_');
		jQuery(this).val(new_name);

		jQuery('.wpp_width input', parent).attr("name", "wpp_settings[image_sizes][" + new_name + "][width]");
		jQuery('.wpp_height input', parent).attr("name", "wpp_settings[image_sizes][" + new_name + "][height]");
	});




	// Delete image type
	jQuery(".wpp_delete_row").live("click", function() {

		var parent = jQuery(this).parents('tr.wpp_image_row');
		var row_count = jQuery(".wpp_delete_row:visible").length;

		jQuery('input', parent).val("");
		jQuery('input', parent).val("");


		// Don't hide last row
		if(row_count > 1)
			jQuery(parent).hide();
	});


	// Show settings array
	jQuery("#wpp_show_settings_array").click(function() {
		jQuery("#wpp_show_settings_array_cancel").show();
		jQuery("#wpp_show_settings_array_result").show();
	});

	// Hide settings array
	jQuery("#wpp_show_settings_array_cancel").click(function() {
		jQuery("#wpp_show_settings_array_result").hide();
		jQuery(this).hide();
	});

	// Hide property query
	jQuery("#wpp_ajax_property_query_cancel").click(function() {
		jQuery("#wpp_ajax_property_result").hide();
		jQuery(this).hide();
	});

	// Show property query
	jQuery("#wpp_ajax_property_query").click(function() {



		var property_id = jQuery("#wpp_property_class_id").val();

		jQuery("#wpp_ajax_property_result").html("");

		jQuery.post(ajaxurl, {
				action: 'wpp_ajax_property_query',
				property_id: property_id,
 			}, function(data) {
				jQuery("#wpp_ajax_property_result").show();
				jQuery("#wpp_ajax_property_result").html(data);
				jQuery("#wpp_ajax_property_query_cancel").show();

			});

	});
	
	// Show property query
	jQuery("#wpp_check_premium_updates").click(function() {

 
		jQuery("#wpp_plugins_ajax_response").hide();

		jQuery.post(ajaxurl, {
				action: 'wpp_ajax_check_plugin_updates',
 			}, function(data) {
				jQuery("#wpp_plugins_ajax_response").show();
				jQuery("#wpp_plugins_ajax_response").html(data);
 
			});

	});


	});
 </script>

<div class="wrap">
<h2>Property Settings</h2>

<form method="post" action="<?php echo admin_url('options-general.php?page=property_settings'); ?>" />
<?php wp_nonce_field('wpp_setting_save'); ?>

<div id="wpp_settings_tabs" class="clearfix">
	<ul class="tabs">
		<li><a href="#tab_main">Main</a></li>
		<li><a href="#tab_display">Display</a></li>
		<li><a href="#tab_admin_ui">Admin UI</a></li>
		
		<?php if(is_array($wp_properties['available_plugins'])): ?>
		<li><a href="#tab_plugins">Plugins</a></li>
		<?php endif; ?>
		
		<li><a href="#tab_troubleshooting">Troubleshooting</a></li>
 		<?php
			if(is_array($wp_properties[plugins])) {

				$wpp_plugin_settings_nav = apply_filters('wpp_settings_nav', array());

				foreach($wp_properties[plugins] as $plugin) {

					if($plugin[status] == 'disabled')
						unset($wpp_plugin_settings_nav[$plugin]);

				}

				if(is_array($wpp_plugin_settings_nav)) {
					foreach($wpp_plugin_settings_nav as $nav) {
						echo "<li><a href='#tab_{$nav[slug]}'>{$nav[title]}</a></li>\n";
					}
				}
			}
		?>
	</ul>

	<div id="tab_main">


		<table class="form-table">
		<tr>
			<th>Property Page</th>
			<td>
				<select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug">
					<option <?php if($wp_properties[configuration][base_slug] == 'property') echo "SELECTED"; ?> value="property">Property (Default)</option>
					<?php foreach(get_pages() as $page): ?>
						<option <?php if($wp_properties[configuration][base_slug] == $page->post_name) echo "SELECTED"; ?> value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>



		<tr>
			<th>&nbsp;</th>
			<td>
				<?php echo UD_UI::checkbox("name=wpp_settings[configuration][automatically_insert_overview]&label=Automatically insert property overview into property page content.", $wp_properties[configuration][automatically_insert_overview]); ?>
				<br />
				<p>If unchecked, you will have to copy and paste one of the shortcodes into the content to display property information on the page.</p>
				<p>Available shortcodes:
					<ul>
					<li>[property_overview] - Property Overview</li>
 					</ul>
				<p>Copy and paste the shortcodes into the page content.
				</p>

			</td>
		</tr>
		<tr>
			<th>Default Phone Number</th>
			<td>
				<?php echo UD_UI::input("name=phone_number&label=Phone number to use when a property-specific phone number is not specified.&group=wpp_settings[configuration]&style=width: 200px;&value={$wp_properties[configuration][phone_number]}"); ?>
			</td>
		</tr>


		</table>
	</div>



	<div id="tab_display">

		<table class="form-table">



		<tr>
			<th>General Settings</th>
			<td>
				<p>These are the general display settings</p>
					<ul>
						<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][autoload_css]&label=Automatically include default CSS.", $wp_properties[configuration][autoload_css]); ?></li>
					</ul>

			</td>
		</tr>


		<tr>
			<th>Image Sizes</th>
			<td>
				<p>
				Image sizes used throughout the plugin. <br />
				<?php if(class_exists("RegenerateThumbnails")): ?>
					  After adding/removing image size, be sure to <a href="<?php echo admin_url("tools.php?page=regenerate-thumbnails"); ?>">regenerate thumbnails</a> using the Regenerate Thumbnails plugin.<br />
				<?php endif; ?>

				<?php if(!class_exists('RegenerateThumbnails')): ?>
				We strongly recommend the <a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">Regenerate Thumbnails</a> plugin by Viper007Bond.
				<?php endif; ?>
				</p>

					<table id="wpp_image_sizes" class="widefat">
						<thead>
							<tr>
								<th>Slug</th>
								<th>Width</th>
								<th>Height</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
					<?php
						$wpp_image_sizes = $wp_properties['image_sizes'];

						foreach(get_intermediate_image_sizes() as $slug):

						// We return all, including images with zero sizes, to avoid default data overriding what we save
						$image_dimensions = WPP_F::image_sizes($slug, "return_all=true");

						// Skip images w/o dimensions
						if(!$image_dimensions)
							continue;

						// Disable if WP not a WPP image size
						if(!is_array($wpp_image_sizes[$slug]))
							$disabled = true;
						else
							$disabled = false;

						// Hide rows with zeroed out image sized
						if($image_dimensions[width] == '0' ||$image_dimensions[width] == '0')
							$hidden = 'hidden';
						else
							$hidden = '';

						if(!$disabled):
					?>
						<tr class="wpp_image_row <?php echo $hidden; ?>">
							<td  class="wpp_slug">
								<input type="text" value="<?php echo $slug; ?>" />
							</td>
							<td class="wpp_width">
								<input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]" value="<?php echo $image_dimensions[width]; ?>" />
							</td>
							<td  class="wpp_height">
								<input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]" value="<?php echo $image_dimensions[height]; ?>" />
							</td>
							<td><span class="wpp_delete_row wpp_link">Delete</span></td>
						</tr>

						<?php else: ?>
						<tr>
							<td>
								<div class="wpp_permanent_image"><?php echo $slug; ?></div>
							</td>
							<td>
								<div class="wpp_permanent_image"><?php echo $image_dimensions[width]; ?></div>
							</td>
							<td>
								<div class="wpp_permanent_image"><?php echo $image_dimensions[height]; ?></div>
							</td>
							<td>&nbsp;</td>
						</tr>

						<?php endif; ?>


					<?php endforeach; ?>

						<tfoot>
							<tr>
								<td colspan='4'>
								<input type="button" class="wpp_image_table_add_row button-secondary" value="Add Row" />
								</td>
							</tr>
						</tfoot>
						</tbody>
					</table>


 			</td>
		</tr>




		<tr>
			<th>Overview Shortcode</th>
			<td>
				<p>
				These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />
				The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property-overview.php</b> file.  To avoid loosing your changes during updates, create a <b>property-overview.php</b> file in your template directory, which will be automatically loaded.
				<ul>

					<li>Thumbnail size: <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties[configuration][property_overview][thumbnail_size]); ?></li>
					<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][show_children]&label=Show children properties.", $wp_properties[configuration][property_overview][show_children]); ?></li>
					<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][fancybox_preview]&label=Show larger image of property when image is clicked using fancybox.", $wp_properties[configuration][property_overview][fancybox_preview]); ?></li>
 				</ul>

			</td>
		</tr>

		<tr>
			<th>Property Page</th>
			<td>
				<p>These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />
				The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property.php</b> file.  To avoid loosing your changes during updates, create a <b>property.php</b> file in your template directory, which will be automatically loaded.
				<ul>
					<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][display_slideshow]&label=Display large image, or slideshow, at the top of the property listing.", $wp_properties[configuration][property_overview][display_slideshow]); ?></li>
 				</ul>

			</td>
		</tr>


		<tr>
			<th>Google Maps</th>
			<td>
			
				<ul>
					<li>Map Thumbnail Size: <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . $wp_properties[configuration][single_property_view][map_image_type]); ?></li>
					<li>Map Zoom Level: <?php echo UD_UI::input("name=gm_zoom_level&group=wpp_settings[configuration][single_property_view]&style=width: 30px;&value={$wp_properties[configuration][single_property_view][gm_zoom_level]}"); ?></li>
				</ul>
			</td>
		</tr>
		
		
		
		
		
		</table>
	</div>


	<div id="tab_admin_ui">
		<table class="form-table">
		<tr>
			<th>
				Overview Page
			</th>
				<td>
				<p>
					These settings are for the main property page on the back-end.
				</p>
				<ul>
					<li>Thumbnail size: <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . $wp_properties[configuration][admin_ui][overview_table_thumbnail_size]); ?></li>
				</ul>
			</td>
		</tr>
		</table>
	</div>

<?php if(is_array($wp_properties['available_plugins'])): ?>
	<div id="tab_plugins">
 
			<table id="wpp_premium_feature_table" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" class="wpp_premium_feature_intro">
							<span class="header">WP-Property Premium Features</span>
							<p>Premium features will become available shortly, we are still waiting on more feedback on the core of the plugin.</p>
							<?php /*<p>If you're recently purchased a premium feature, <span id="wpp_check_premium_updates" class="wpp_link">download updates</a>.</p> */ ?>
							<p id="wpp_plugins_ajax_response" class="hidden"></p>
					</td>
				</tr>
			<?php foreach($wp_properties[available_plugins] as $plugin_slug => $plugin_data):

				$enabled = ($wp_properties[plugins][$plugin_slug][stats] == 'enabled' ? true : false);
			?>
				<tr class="wpp_premium_feature_block">
					
					<td valign="top" class="wpp_premium_feature_image">
						<a href="http://twincitiestech.com/plugins/wp-property/"><img src="<?php echo $plugin_data[image]; ?>" /></a>
					</td>
					
					<td valign="top">
						<div class="wpp_box">
						<div class="wpp_box_header">
							<strong><?php echo $plugin_data[title]; ?></strong>
							<p><?php echo $plugin_data[tagline]; ?> <a href="http://twincitiestech.com/plugins/wp-property/premium/">[learn more]</a>
							</p>
						</div>
						<div class="wpp_box_content">
							<p><?php echo $plugin_data[description]; ?></p>
							
						</div>
						
						<div class="wpp_box_footer">
							<?php if($enabled): ?>
								The feature is installed. Disable.
							<?php else: ?>
								Feature not available. 
							<?php endif; ?>							
						</div>
						</div>
					</td>
				</tr>				
			<?php endforeach; ?>
			</table>
 
	</div>
<?php endif; ?>

	<div id="tab_troubleshooting">
		<div class="wpp_inner_tab">

			<p>Enter in the ID of the property you want to look up, and the class will be displayed below.
				<input id="wpp_property_class_id" />
				<input type="button" value="Lookup" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden">Cancel</span>
			</p>

			<pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>

			<p>
				Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.
				<input type="button" value="Show $wp_properties" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden">Cancel</span>
			</p>

			<pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"><?php  print_r($wp_properties); ?></pre>

		</div>
	</div>


	<?php
	if(is_array($wpp_plugin_settings_nav)) {
		foreach($wpp_plugin_settings_nav as $nav) {
			echo "<div id='tab_{$nav[slug]}'>";
			do_action("wpp_settings_content_{$nav[slug]}");
			echo "</div>";
		}
	}

	?>
</div>


<br class="cb" />
<div class="wpp_save_changes_row">
<input type="submit" value="Save Changes" class="button-primary" name="Submit">
 </div>


</form>
</div>