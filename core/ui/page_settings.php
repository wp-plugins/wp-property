

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
		<li><a href="#tab_plugins">Plugins</a></li>
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
			<th>Google Maps Zoom Level</th>
			<td>
				<?php echo UD_UI::input("name=gm_zoom_level&group=wpp_settings[configuration]&style=width: 30px;&value={$wp_properties[configuration][gm_zoom_level]}"); ?>
			</td>
		</tr>
		</table>
	</div>


	<div id="tab_plugins">
 		<table class="form-table">
			<tr>
				<th>&nbsp;</th>
				<td>
				<p>The following premium plugins are available from TwinCitiesTech.com.  Please visit <a href="http://twincitiestech.com/plugins/wp-property/">http://twincitiestech.com/plugins/wp-property/</a> to purchase any premium features you are interested in.  Once purchased, they will automatically become available.</p>
				</td>
			</tr>
			<?php foreach($wp_properties[available_plugins] as $plugin_slug => $plugin_data): ?>
				<tr>
					<th><?php echo $plugin_data[title]; ?></th>
					<td><?php echo $plugin_data[description]; ?></td>
				</tr>
			<?php endforeach; ?>
			</table>
	</div>


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