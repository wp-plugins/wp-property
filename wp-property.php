<?php
/*
Plugin Name: WP-Property
Plugin URI: http://twincitiestech.com/plugins/wp-property/
Description: Property and Real Estate Management Plugin for WordPress.  Create a directory of real estate / rental properties and integrate them into you WordPress CMS.
Author: TwinCitiesTech.com, Inc.
Version: 1.00
Author URI: http://twincitiestech.com

Copyright 2010  TwinCitiesTech.com Inc.   (email : andy.potanin@twincitiestech.com)

Created by TwinCitiesTech.com
(website: twincitiestech.com       email : support@twincitiestech.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 3 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/** Path for Includes */
define('WPP_Path', WP_PLUGIN_DIR . '/wp-property');

/** Path for front-end links */
define('WPP_URL', WP_PLUGIN_URL . '/wp-property');

/** Directory path for includes of template files  */
define('WPP_Templates', WP_PLUGIN_DIR . '/wp-property/templates');

/** Directory path for includes of template files  */
define('WPP_Premium', WP_PLUGIN_DIR . '/wp-property/core/premium');

/** Sets prefix for UD_UI and UD_F classes and their functions */
define('UD_UI_PREFIX', 'wpp_');
define('UD_PREFIX', 'wpp_');

function wpp_load_textdomain() {
	$locale = get_locale();
	$mofile = WPP_Path . "/langs/wpp-$locale.mo";

	if ( file_exists( $mofile ) )
		load_textdomain( 'wpp', $mofile );
}
add_action ( 'plugins_loaded', 'wpp_load_textdomain', 2 );

// Global Usability Dynamics / TwinCitiesTech.com, Inc. Functions
include WPP_Path . '/core/class_ud.php';
	
/** Loads built-in plugin metadata and allows for third-party modification to hook into the filters. Has to be included here to run after template functions.php */
include WPP_Path . '/action_hooks.php';
	
/** Defaults filters and hooks */
include WPP_Templates . '/default_api.php';

/** Loads general functions used by WP-Property */
include WPP_Path . '/core/class_functions.php';
 
 /** Loads widgets */
include WPP_Path . '/core/class_widgets.php';

 /** Loads all the metaboxes for the property page */
include WPP_Path . '/core/ui/property_metaboxes.php';
 
/** Loads all the metaboxes for the property page */
include WPP_Path . '/core/class_core.php';

// Register activation hook -> has to be in the main plugin file
register_activation_hook(__FILE__,array('WPP_F', 'activation'));

// Register activation hook -> has to be in the main plugin file
register_deactivation_hook(__FILE__,array('WPP_F', 'deactivation'));

// Setup widgets (they need to be called early)  
if(class_exists('FeaturedPropertiesWidget'))
	add_action('widgets_init', create_function('', 'return register_widget("FeaturedPropertiesWidget");'));

if(class_exists('ChildPropertiesWidget'))
	add_action('widgets_init', create_function('', 'return register_widget("ChildPropertiesWidget");'));

if(class_exists('SearchPropertiesWidget'))
	add_action('widgets_init', create_function('', 'return register_widget("SearchPropertiesWidget");'));

if(class_exists('GalleryPropertiesWidget'))
	add_action('widgets_init', create_function('', 'return register_widget("GalleryPropertiesWidget");'));

if(class_exists('LatestPropertiesWidget'))
	add_action('widgets_init', create_function('', 'return register_widget("LatestPropertiesWidget");'));

if(class_exists('OtherPropertiesWidget'))
add_action('widgets_init', create_function('', 'return register_widget("OtherPropertiesWidget");'));
 				
// Initiate the plugin
add_action("init", create_function('', 'new WPP_Core;'));

?>