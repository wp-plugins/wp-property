<?php
/*
echo "<pre>";
print_r($wp_properties[configuration]);
echo "</pre>";
*/

?>

 <script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#wpp_settings_tabs").tabs({ cookie: { expires: 30 } });



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

	// Check plugin updates
	jQuery("#wpp_ajax_check_plugin_updates").click(function() {

		jQuery('.plugin_status').remove();

 
 
		jQuery.post(ajaxurl, {
				action: 'wpp_ajax_check_plugin_updates'
				}, function(data) {
				
				message = "<div class='plugin_status updated fade'><p>" + data + "</p></div>";
				jQuery(message).insertAfter("h2");
				

			});

	});	// Show property query
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
<h2><?php _e('Property Settings','wpp'); ?></h2>

<form method="post" action="<?php echo admin_url('options-general.php?page=property_settings'); ?>" />
<?php wp_nonce_field('wpp_setting_save'); ?>

<div id="wpp_settings_tabs" class="clearfix">
	<ul class="tabs">
		<li><a href="#tab_main"><?php _e('Main','wpp'); ?></a></li>
		<li><a href="#tab_display"><?php _e('Display','wpp'); ?></a></li>
		<li><a href="#tab_admin_ui"><?php _e('Admin UI','wpp'); ?></a></li>



 		<?php
			if(is_array($wp_properties[available_features])) {

				$wpp_plugin_settings_nav = apply_filters('wpp_settings_nav', array());
 
				foreach($wp_properties[available_features] as $plugin) {

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

		<?php if(count($wp_properties['available_features']) > 0): ?>
		<li><a href="#tab_plugins"><?php _e('Plugins','wpp'); ?></a></li>
		<?php endif; ?>
		<li><a href="#tab_troubleshooting"><?php _e('Troubleshooting','wpp'); ?></a></li>


	</ul>

	<div id="tab_main">


		<table class="form-table">
		<tr>
			<th><?php _e('Property Page','wpp'); ?></th>
			<td>
				<select name="wpp_settings[configuration][base_slug]" id="wpp_settings_base_slug">
					<option <?php if($wp_properties[configuration][base_slug] == 'property') echo "SELECTED"; ?> value="property"><?php _e('Property (Default)','wpp'); ?></option>
					<?php foreach(get_pages() as $page): ?>
						<option <?php if($wp_properties[configuration][base_slug] == $page->post_name) echo "SELECTED"; ?> value="<?php echo $page->post_name; ?>"><?php echo $page->post_title; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>



		<tr>
			<th>&nbsp;</th>
			<td>
				<?php echo UD_UI::checkbox("name=wpp_settings[configuration][automatically_insert_overview]&label=" .__('Automatically insert property overview into property page content.','wpp'), $wp_properties[configuration][automatically_insert_overview]); ?>
				<br />
				<p><?php _e('If unchecked, you will have to copy and paste one of the shortcodes into the content to display property information on the page.','wpp'); ?></p>
				<p><?php _e('Available shortcodes:','wpp'); ?>
					<ul>
					<li><?php _e('[property_overview] - Property Overview','wpp'); ?></li>
 					</ul>
				<p><?php _e('Copy and paste the shortcodes into the page content.','wpp'); ?>
				</p>

			</td>
		</tr>
		<tr>
			<th><?php _e('Default Phone Number','wpp'); ?></th>
			<td>
				<?php echo UD_UI::input("name=phone_number&label=" . __('Phone number to use when a property-specific phone number is not specified.','wpp') . "&group=wpp_settings[configuration]&style=width: 200px;&value={$wp_properties[configuration][phone_number]}"); ?>
			</td>
		</tr>
		<tr>
			<th><?php _e('Address Attribute','wpp'); ?></th>
			<td>
				<?php _e('Attribute to use for address:','wpp'); ?> <?php echo WPP_F::draw_attribute_dropdown("name=wpp_settings[configuration][address_attribute]&selected={$wp_properties[configuration][address_attribute]}"); ?>
			</td>
		</tr>

		<tr>
			<th><?php _e('Currency','wpp'); ?></th>
			<td>
				<?php echo UD_UI::input("name=currency_symbol&label=" . __('Currency symbol.','wpp') . "&group=wpp_settings[configuration]&style=width: 50px;",$wp_properties[configuration][currency_symbol]); ?>
			</td>
		</tr>


		</table>
	</div>



	<div id="tab_display">

		<table class="form-table">



		<tr>
			<th><?php _e('General Settings','wpp'); ?></th>
			<td>
				<p><?php _e('These are the general display settings','wpp'); ?></p>
					<ul>
						<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][autoload_css]&label=" . __('Automatically include default CSS.','wpp'), $wp_properties[configuration][autoload_css]); ?></li>
					</ul>

			</td>
		</tr>


		<tr>
			<th><?php _e('Image Sizes','wpp'); ?></th>
			<td>
				<p>
				<?php _e('Image sizes used throughout the plugin.','wpp'); ?> <br />
				<?php if(class_exists("RegenerateThumbnails")): ?>
					  <?php echo sprintf(__('After adding/removing image size, be sure to <a href="%s">regenerate thumbnails</a> using the Regenerate Thumbnails plugin.','wpp'), admin_url("tools.php?page=regenerate-thumbnails")); ?><br />
				<?php endif; ?>

				<?php if(!class_exists('RegenerateThumbnails')): ?>
				<?php _e('We strongly recommend to <a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">Regenerate Thumbnails</a> plugin by Viper007Bond.','wpp'); ?>
				<?php endif; ?>
				</p>

					<table id="wpp_image_sizes" class="ud_ui_dynamic_table widefat">
						<thead>
							<tr>
								<th><?php _e('Slug','wpp'); ?></th>
								<th><?php _e('Width','wpp'); ?></th>
								<th><?php _e('Height','wpp'); ?></th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
					<?php
						$wpp_image_sizes = $wp_properties['image_sizes'];

						foreach(get_intermediate_image_sizes() as $slug):

						$slug = trim($slug);

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


						if(!$disabled):
					?>
						<tr class="wpp_dynamic_table_row" slug="<?php echo $slug; ?>">
							<td  class="wpp_slug">
								<input class="slug_setter slug"  type="text" value="<?php echo $slug; ?>" />
							</td>
							<td class="wpp_width">
								<input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][width]" value="<?php echo $image_dimensions[width]; ?>" />
							</td>
							<td  class="wpp_height">
								<input type="text" name="wpp_settings[image_sizes][<?php echo $slug; ?>][height]" value="<?php echo $image_dimensions[height]; ?>" />
							</td>
							<td><span class="wpp_delete_row wpp_link"><?php _e('Delete','wpp') ?></span></td>
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
								<td colspan='4'><input type="button" class="wpp_add_row button-secondary" value="<?php _e('Add Row','wpp') ?>" /></td>
							</tr>
						</tfoot>
						</tbody>
					</table>


 			</td>
		</tr>




		<tr>
			<th><?php _e('Overview Shortcode','wpp') ?></th>
			<td>
				<p>
				<?php _e('These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />
				The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property-overview.php</b> file.  To avoid losing your changes during updates, create a <b>property-overview.php</b> file in your template directory, which will be automatically loaded.','wpp') ?>
				<ul>

					<li>Thumbnail size: <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][property_overview][thumbnail_size]&selected=" . $wp_properties[configuration][property_overview][thumbnail_size]); ?></li>
					<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][show_children]&label=Show children properties.", $wp_properties[configuration][property_overview][show_children]); ?></li>
					<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][fancybox_preview]&label=" . __('Show larger image of property when image is clicked using fancybox.','wpp'), $wp_properties[configuration][property_overview][fancybox_preview]); ?></li>
 				</ul>

			</td>
		</tr>

		<tr>
			<th><?php _e('Property Page','wpp') ?></th>
			<td>
				<p><?php _e('These are the settings for the [property_overview] shortcode.  The shortcode displays a list of all building / root properties.<br />
				The display settings may be edited further by customizing the <b>wp-content/plugins/wp-properties/templates/property.php</b> file.  To avoid losing your changes during updates, create a <b>property.php</b> file in your template directory, which will be automatically loaded.','wpp') ?>
				<ul>
					<li><?php echo UD_UI::checkbox("name=wpp_settings[configuration][property_overview][display_slideshow]&label=".__('Display large image, or slideshow, at the top of the property listing.','wpp'), $wp_properties[configuration][property_overview][display_slideshow]); ?></li>
 				</ul>

			</td>
		</tr>


		<tr>
			<th><?php _e('Google Maps','wpp') ?></th>
			<td>

				<ul>
					<li><?php _e('Map Thumbnail Size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][single_property_view][map_image_type]&selected=" . $wp_properties[configuration][single_property_view][map_image_type]); ?></li>
					<li><?php _e('Map Zoom Level:','wpp') ?> <?php echo UD_UI::input("name=gm_zoom_level&group=wpp_settings[configuration][single_property_view]&style=width: 30px;&value={$wp_properties[configuration][single_property_view][gm_zoom_level]}"); ?></li>
				</ul>
			</td>
		</tr>

		<tr>
			<th><?php _e('Address Display','wpp') ?></th>
			<td>
 
					
				<textarea name="wpp_settings[configuration][display_address_format]" style="width: 70%;"><?php echo $wp_properties[configuration][display_address_format]; ?></textarea>
				<br />
				<span class="description">
               <?php _e('Available tags:','wpp') ?> [street_number] [street_name], [city], [state], [state_code], [country], [zip_code].
				</span>
			</td>
		</tr>





		</table>
	</div>


	<div id="tab_admin_ui">
		<table class="form-table">
		<tr>
			<th>
				<?php _e('Overview Page','wpp') ?>
			</th>
				<td>
				<p>
					<?php _e('These settings are for the main property page on the back-end.','wpp') ?>
				</p>
				<ul>
					<li><?php _e('Thumbnail size:','wpp') ?> <?php WPP_F::image_sizes_dropdown("name=wpp_settings[configuration][admin_ui][overview_table_thumbnail_size]&selected=" . $wp_properties[configuration][admin_ui][overview_table_thumbnail_size]); ?></li>
				</ul>
			</td>
		</tr>
		</table>
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



<?php if(count($wp_properties['available_features']) > 0): ?>
	<div id="tab_plugins">

			<table id="wpp_premium_feature_table" cellpadding="0" cellspacing="0">
				<thead>
				<tr>
					<td colspan="2" class="wpp_premium_feature_intro">
							<span class="header"><?php _e('WP-Property Premium Features','wpp') ?></span>
							<p><?php _e('Premium features will become available shortly, we are still waiting on more feedback on the core of the plugin.','wpp') ?></p>
							<?php /*<p><?php _e('If you're recently purchased a premium feature, <span id="wpp_check_premium_updates" class="wpp_link">download updates</a>.','wpp') ?></p> */ ?>
							<p id="wpp_plugins_ajax_response" class="hidden"></p>
					</td>
				</tr>
				</thead>
			<?php foreach($wp_properties[available_features] as $plugin_slug => $plugin_data): ?>

				<input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][title]" value="<?php echo $plugin_data[title]; ?>" />
				<input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][tagline]" value="<?php echo $plugin_data[tagline]; ?>" />
				<input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][image]" value="<?php echo $plugin_data[image]; ?>" />
				<input type="hidden" name="wpp_settings[available_features][<?php echo $plugin_slug; ?>][description]" value="<?php echo $plugin_data[description]; ?>" />

				<?php $installed = (!empty($wp_properties[installed_features][$plugin_slug][version]) ? true : false); ?>
				<?php $active = ($wp_properties[installed_features][$plugin_slug][disabled] != 'false' ? true : false); ?>
				<tr class="wpp_premium_feature_block">

					<td valign="top" class="wpp_premium_feature_image">
						<a href="http://twincitiestech.com/plugins/wp-property/"><img src="<?php echo $plugin_data[image]; ?>" /></a>
					</td>

					<td valign="top">
						<div class="wpp_box">
						<div class="wpp_box_header">
							<strong><?php echo $plugin_data[title]; ?></strong>
							<p><?php echo $plugin_data[tagline]; ?> <a href="http://twincitiestech.com/plugins/wp-property/premium/"><?php _e('[learn more]','wpp') ?></a>
							</p>
						</div>
						<div class="wpp_box_content">
							<p><?php echo $plugin_data[description]; ?></p>

						</div>

						<div class="wpp_box_footer clearfix">
							<?php if($installed): ?>

								<div class="alignleft">
								<?php echo UD_UI::checkbox("name=wpp_settings[installed_features][$plugin_slug][disabled]&label=" . __('Disable plugin.','wpp'), $wp_properties[installed_features][$plugin_slug][disabled]); ?>
								</div>

								<div class="alignright"><?php _e('Feature installed, using version','wpp') ?> <?php echo $wp_properties[installed_features][$plugin_slug][version]; ?>.</div>
							<?php else: ?>
								<?php $pr_link = 'http://twincitiestech.com/plugins/wp-property/premium/'; echo sprintf(__('Please visit <a href="%s">TwinCitiesTech.com</a> to purchase this feature.','wpp'),$pr_link); ?>
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

			<p><?php _e('Enter in the ID of the property you want to look up, and the class will be displayed below.','wpp') ?>
				<input type="text" id="wpp_property_class_id" />
				<input type="button" value="<?php _e('Lookup','wpp') ?>" id="wpp_ajax_property_query"> <span id="wpp_ajax_property_query_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
			</p>


			<pre id="wpp_ajax_property_result" class="wpp_class_pre hidden"></pre>

			<p>
				<?php _e('Look up the <b>$wp_properties</b> global settings array.  This array stores all the default settings, which are overwritten by database settings, and custom filters.','wpp') ?>
				<input type="button" value="Show $wp_properties" id="wpp_show_settings_array"> <span id="wpp_show_settings_array_cancel" class="wpp_link hidden"><?php _e('Cancel','wpp') ?></span>
			</p>

			<pre id="wpp_show_settings_array_result" class="wpp_class_pre hidden"><?php print_r($wp_properties); ?></pre>

			
			<p><?php _e('Force check of allowed premium features.','wpp');?>
 				<input type="button" value="<?php _e('Check Updates','wpp');?>" id="wpp_ajax_check_plugin_updates">
			</p>
			
		</div>
	</div>

</div>


<br class="cb" />

<p class="wpp_save_changes_row">
<input type="submit" value="<?php _e('Save Changes','wpp');?>" class="button-primary" name="<?php _e('Submit','wpp');?>">
 </p>


</form>
</div>