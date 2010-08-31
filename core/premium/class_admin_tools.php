<?php
/*
Name: Admin Tools
Class: class_admin_tools
Version: 1.0
Description: Tools for developing themes and extensions for WP-Property.
*/


add_action('wpp_init', array('class_admin_tools', 'init'));


/**
 * class_admin_tools Class
 * 
 * Contains administrative functions
 *
 * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
 *
 * @version 1.0
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
 * @subpackage Admin Functions
 */	
class class_admin_tools {

	function init() {	

		// Add Inquiry page to Property Settings page array
		add_filter('wpp_settings_nav', array('class_admin_tools', 'settings_nav'));

		// Add Settings Page
		add_action('wpp_settings_content_admin_tools', array('class_admin_tools', 'settings_page'));

	}

	/**
	 * Adds admin tools manu to settings page navigation
	 *
	 * @version 1.0
	 * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
	 */		
	function settings_nav($tabs) {
 
 		$tabs['admin_tools'] = array(
			'slug' => 'admin_tools',
			'title' => 'Developer'
		);

		return $tabs;
	}
	
	
	/**
	 * Displays advanced management page
	 *
 	 *
	 * @version 1.0
	 * Copyright 2010 Andy Potanin, TwinCitiesTech.com, Inc.  <andy.potanin@twincitiestech.com>
	 */		 
	function settings_page() {
	global $wpdb, $wp_properties; ?>	 
	<table class="form-table">	
		<tr>
			<th>Property Types</th>
			<td>
				<p>Create new property types using this menu. </p>
				<p>The <b>slug</b> is automatically created from the title and is used in the back-end.  It is also used for template selection, example: floorplan will look for a template called property-floorplan.php in your theme folder, or default to property.php if nothing is found.</p>
				<p>If <b>Searchable</b> is checked then the property will be loaded for search, and available on the property search widget.</p>
				<p>If <b>Location Matters</b> is checked, then an address field will be displayed for the property, and validated against Google Maps API.  Additionally, the property will be displayed on the SuperMap, if the feature is installed.</p>
				<p><b>Hidden Attributes</b> determine which attributes are not applicable to the given property type, and will be grayed out in the back-end.</p>
				<p><b>Inheritance</b> determines which attributes should be automatically inherited from the parent property.</p>
				
				<table id="wpp_inquiry_property_types" class="ud_ui_dynamic_table widefat">
				<thead>
					<tr>
						<th>Property Type</th>
						<th>Slug</th>
						<th>Settings</th>
						<th>Hidden Attributes</th>						
						<th>Inheritance</th>						
 					</tr>
				</thead>
				<tbody>
				<?php  foreach($wp_properties['property_types'] as $property_slug => $label):  ?>
				
					<tr class="wpp_dynamic_table_row <?php echo $hidden; ?>" slug="<?php echo $property_slug; ?>">
					<td >
						<input class="slug_setter" type="text" name="wpp_settings[property_types][<?php echo $property_slug; ?>]" value="<?php echo $label; ?>" /><br />
						<span class="wpp_delete_row wpp_link">Delete</span>
					</td>
					<td>
						<input type="text" class="slug" readonly='readonly' value="<?php echo $property_slug; ?>" />
					</td>
					
					<td>
						<ul>
							<li>
								<input class="slug" id="<?php echo $property_slug; ?>_searchable_property_types" <?php if(in_array($property_slug, $wp_properties['searchable_property_types'])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[searchable_property_types][]" value="<?php echo $property_slug; ?>" /> 
								<label for="<?php echo $property_slug; ?>_searchable_property_types">Searchable</label>
							</li>
							
							<li>
								<input class="slug" id="<?php echo $property_slug; ?>_location_matters"  <?php if(in_array($property_slug, $wp_properties['location_matters'])) echo " CHECKED "; ?> type="checkbox"  name="wpp_settings[location_matters][]" value="<?php echo $property_slug; ?>" /> 
								<label for="<?php echo $property_slug; ?>_location_matters">Location Matters</label>
							</li>
						</ul>
					</td>
					
					<td >
						<ul class="wpp_hidden_property_attributes">
						<?php foreach($wp_properties['property_stats'] as $property_stat_slug => $property_stat_label): ?>
						<li>
							<input id="<?php echo $property_slug . "_" .$property_stat_slug;?>_hidden_attributes" <?php if(is_array($wp_properties['hidden_attributes'][$property_slug]) && in_array($property_stat_slug, $wp_properties['hidden_attributes'][$property_slug])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug;?>][]" value="<?php echo $property_stat_slug; ?>" /> 
							<label for="<?php echo $property_slug . "_" .$property_stat_slug;?>_hidden_attributes">
								<?php echo $property_stat_label;?>
							</label>
						</li>
						<?php endforeach; ?>

						<?php foreach($wp_properties['property_meta'] as $property_meta_slug => $property_meta_label): ?>
						<li>
							<input id="<?php echo $property_slug . "_" . $property_meta_slug;?>_hidden_attributes" <?php if(is_array($wp_properties['hidden_attributes'][$property_slug]) && in_array($property_meta_slug, $wp_properties['hidden_attributes'][$property_slug])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[hidden_attributes][<?php echo $property_slug;?>][]" value="<?php echo $property_meta_slug; ?>" /> 
							<label for="<?php echo $property_slug . "_" . $property_meta_slug;?>_hidden_attributes">
								<?php echo $property_meta_label;?>
							</label>
						</li>
						<?php endforeach; ?>
						
						</ul>
					</td>
					
 					<td >
						<ul class="wpp_inherited_property_attributes">
						<?php foreach($wp_properties['property_stats'] as $property_stat_slug => $property_stat_label): ?>
						<li>
							<input id="<?php echo $property_slug . "_" .$property_stat_slug;?>_inheritance" <?php if(is_array($wp_properties['property_inheritance'][$property_slug]) && in_array($property_stat_slug, $wp_properties['property_inheritance'][$property_slug])) echo " CHECKED "; ?> type="checkbox" name="wpp_settings[property_inheritance][<?php echo $property_slug;?>][]" value="<?php echo $property_stat_slug; ?>" /> 
							<label for="<?php echo $property_slug . "_" .$property_stat_slug;?>_inheritance">
								<?php echo $property_stat_label;?>
							</label>
						</li>
						<?php endforeach; ?>
 
						</ul>
					</td>
					
 					
				</tr>

				<?php endforeach; ?>
				</tbody>
				
				<tfoot>
					<tr>
						<td colspan='4'>
						<input type="button" class="wpp_add_row button-secondary" value="Add Row" />
						</td>
					</tr>
				</tfoot>
				
				</table>
			</td>
		</tr>
			

		<tr>
			<th>Property Fields</th>
			<td>
				<h3>Property Stats</h3>
				<p>Property attributes are meant to be short entries that can be searchable, on the back-end attributes will be displayed as single-line input boxes. On the front-end they are displayed using a definitions list.</p>
				<p>Making an attribute as "searchable" will list it as one of the searchable options in the Property Search widget settings.</p>
				<p>Be advised, attributes added via add_filter() function supercede the settings on this page. </p>

				<table id="wpp_inquiry_fields" class="ud_ui_dynamic_table widefat">
				<thead>
					<tr>
						<th>Property Type</th>
						<th>Slug</th>
						<th>Searchable</th>

 						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<?php  foreach($wp_properties['property_stats'] as $slug => $label):  ?>
				
					<tr class="wpp_dynamic_table_row <?php echo $hidden; ?>" slug="<?php echo $slug; ?>">
					<td >
						<input class="slug_setter" type="text" name="wpp_settings[property_stats][<?php echo $slug; ?>]" value="<?php echo $label; ?>" />
					</td>
					<td>
						<input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>" />
					</td>

					<td>
						<input <?php if(in_array($slug, $wp_properties['searchable_attributes'])) echo " CHECKED "; ?> type="checkbox" class="slug" name="wpp_settings[searchable_attributes][]" value="<?php echo $slug; ?>" /> 
					</td>

					
 					<td><span class="wpp_delete_row wpp_link">Delete</span></td>
				</tr>

				<?php endforeach; ?>
				</tbody>
				
				<tfoot>
					<tr>
						<td colspan='4'>
						<input type="button" class="wpp_add_row button-secondary" value="Add Row" />
						</td>
					</tr>
				</tfoot>
				
				</table>	
				<h3>Property Meta</h3>
				<p>Meta is used for descriptions,  on the back-end  meta fields will be displayed as textareas.  On the front-end they will be displayed as individual sections. </p>

				<table id="wpp_inquiry_fields" class="ud_ui_dynamic_table widefat">
				<thead>
					<tr>
						<th>Property Type</th>
						<th>Slug</th>
 						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<?php  foreach($wp_properties['property_meta'] as $slug => $label):  ?>
				
					<tr class="wpp_dynamic_table_row <?php echo $hidden; ?>" slug="<?php echo $slug; ?>">
					<td >
						<input class="slug_setter" type="text" name="wpp_settings[property_meta][<?php echo $slug; ?>]" value="<?php echo $label; ?>" />
					</td>
					<td>
						<input type="text" class="slug" readonly='readonly' value="<?php echo $slug; ?>" />
					</td>
 					<td><span class="wpp_delete_row wpp_link">Delete</span></td>
				</tr>

				<?php endforeach; ?>
				</tbody>
				
				<tfoot>
					<tr>
						<td colspan='4'>
						<input type="button" class="wpp_add_row button-secondary" value="Add Row" />
						</td>
					</tr>
				</tfoot>
				
				</table>
			</td>
		</tr>
		
	 
		<tr>
			<th>Log</th>
			<td>
				<?php echo UD_UI::checkbox("name=wpp_settings[configuration][show_ud_log]&label=Show Log.", $wp_properties[configuration][show_ud_log]); ?> <br />
				<span class="description">The log is always active, but the UI is hidden.  If enabled, it will be visible in the admin sidebar.</span>
			</td>
		</tr>
	</table>
 
		<?php
	} 
	

 
}


?>