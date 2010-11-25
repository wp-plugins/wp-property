=== WP-Property - WordPress Property and Real Estate Management ===
Contributors: andypotanin
Donate link: http://twincitiestech.com/plugins/wp-property/
Tags: property management, real estate, listings, properties, property
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: trunk


== Description ==

Developed by the same people who brought you [WP-Invoice](http://wordpress.org/extend/plugins/wp-invoice/), comes WP-Property. As always, integration is seamless, the system is expandable and customizable, functionality is rich, and we are here to support it.

[vimeo http://vimeo.com/14280748]

This is not a "collection" of plugins, but one full suite. You will not have to download and match together a plethora of other plugins, in the hopes of them working well together, to have all the features you need.

The only requirement, you have to use WordPress 3.0 or newer.

As of version 0.60 we have added a "Developer" tab to the settings page which lets you create new property types, real estate listings for example, alongside rental properties.

http://www.vimeo.com/14473894

Listing Demo: http://sites.twincitiestech.com/wp-property/properties/crossing-town-center/

Property Overview Demo: http://sites.twincitiestech.com/wp-property/properties/

== New features == 
* Property result pagination via AJAX, example: [property_overview pagination=on per_page=5]  (default is 10)
* Property queries by custom attributes. For example, [property_overview house_color='red'] or [property_overview house_color='all'] get all properties that have a value for house_color attribute. 
* Localized Google Maps

== Translations ==
* Italian (IT)
* Portuguese (BR)

== A few features ==

* Customizable templates for different property types.
* Fields such as price, bathrooms, bedrooms, features, address, work out of the box.
* SEO friendly URLs generated for every property, following the WordPress format.
* Customizable widgets:  Featured Properties, Property Search, Property Gallery, and Child Properties.
* Google Maps API to automatically validate physical addresses behind-the-scenes.
* Integrates with Media Library, avoiding the need for additional third-party Gallery plugins..
* Advanced image type configuration using UI.
* Out of the box support for two property types, Building and Floorplan.   More can be added via WP-Property API.
* Property types follow a hierachial format, having the ability of inheriting settings - i.e. buildings (or communities) will automatically calculate the price range of all floor-plans below them.
* Free!

== Installation ==

1. Download and activate the plugin through the 'Plugins' menu in WordPress.
2. Visit Settings -> Properties page and set "Property Page" to one of your pages.
3. Check "Automatically insert property overview into property page content." or copy and paste [property_overview] into the body of your main property page.
4. Visit Appearance -> Widgets and set up the widgets you want to show on your different property type pags.

1. http://vimeo.com/14280748
2. http://vimeo.com/14281223
3. http://vimeo.com/14281599
4. http://vimeo.com/14473894

== Screenshots ==

1. Properties Overview
2. Editing Property 
3. Customize Frontend with Property Widgets
4. Property Listings
5. A Building  Page
6. Image from gallery enlarged

== Frequently Asked Questions ==

= How do stylesheets work? =

The plugin uses your theme's stylesheet, but also has its own. Inside the plugin folder (wp-content/plugins/wp-property/templates) there is a file called "wp_properties.css". Copy that file to your template directory, and the plugin will automatically switch to using the settings in that file, and will not load the default one anymore. That way when you upgrade the plugin, your custom CSS will not be overwritten. Same goes for all the other template files. 

= What if I can't see the developer tab? =

The developer tab should appear within a few seconds after you install the plugin.  If it doesn't, de-activate and re-active the plugin.  If you still cannot see it, contact TwinCitiesTech.com.

= How do I configure the plugin? =

There are two ways.  The easy way is to go to the Settings -> Properties page, many settings can be configured there.  A more advanced way, which does require some basic understanding of PHP and the WordPress API, is to use hooks and filters.  We've setup a page to assist others with customizing, and expanding on, WP-Property.  View the [WP-Property API documentation](http://twincitiestech.com/plugins/wp-property/api-documentation/) here. 

= How do I upload property images? =

You would do it the same way as if you were editing a post or a page.  On the property editing page, click the Image icon above the content area, and upload images into the media library.  If you want the images to show up on the front-end, you may want to visit Appearance -> Widgets and setup the Property Gallery widget to show up on the property page.

= How do I suggest an idea? =

You can send us a message via our website, or, preferably, visit our [UserVoice](http://wpproperty.uservoice.com/) page to submit new, and vote on existing, ideas. 

= I like where this is going, but how do I get customization? =

If you submit a popular idea on UserVoice, we WILL integrate it sooner or later.  If you need something custom, and urgent, [contact us](http://twincitiestech.com/contact-us/)

== Upgrade Notice ==

= 0.6.0 =
We are moving out of beta stages, but you may still experience bugs now and then.  We welcome all feedback.

= 0.5.3 =
We are still in early stages, so updates will be coming out routinely.  Please do not hesitate to send us feedback and suggestions. 


== Changelog ==

= 0.7251 =
* CSS error fixed

= 0.725 =
* Added AJAX pagination
* Fixed bug with searching

= 0.724 =
* Localization added for Google Maps.
* Added fix for properties with empty addresses
* Prevented address attribute from being overwritten by child properties' addresses when set to be searchable

= 0.722 =
* Custom attribute queries. For example, [property_overview house_color='red'] or [property_overview house_color='all'] get all properties that have a value for house_color attribute. 

= 0.722 =
* Improved Developer tab to allow reordering of meta and attribute fields
* Added [county] to list of usable address tags

= 0.721 =
* Improved error handling and reporting of feature update failure.
* Plugin notifies user if premium feature folder permissions are non-writable.
* Some columns are hidden by default on overview page for users with no setting.

= 0.72 =
* Bug fix.

= 0.71 =
* Improved the address generating function.  It now automatically removes commas and line breaks if there is no content around them.

= 0.7 =
* Plugin fully internationalized, and ready for translation.

= 0.6.3.6 =
* Address display setting added to "Featured" and "Child" properties widgets as well.

= 0.6.3.5 =
* Added ability to customize the format of the displayed address. Visit Settings -> Properties -> Display

= 0.6.3.4 =
* Problem with incorrect address being set by Google Maps API fixed. 

= 0.6.3.3 =
* Removed loggin function from get_properties() to save resources
* Removed MSIE FancyBox CSS that was causing "close" button to be hidden

= 0.6.3.2 =
* Updated [property_overview] shortcode to filer by for_sale or by for_rent properties.
* Added two attributes: for rent & for sale.  Properties can be tagged as both, or neither. 
* Improved displayed address -> when no street address present, line break is not printed.
* Improved support for sites not using permalinks.

= 0.6.3.1 =
* Added custom currency support.

= 0.6.3.0 =
* Rewrote property-overview.php and significantly improved CSS
* property-overview.php output now renders property on MSIE
* Improved featured shortcode to work on MSIE
* Added prepare_property_for_display() which runs a given property object array through all appropriate filters

= 0.6.2.6 =
* Added shortcode option to return all properties: [property_overview type=all], or simply [property_overview], which defaults to type=all.

= 0.6.2.5 =
* Updated geo_locate_address() to work with Canadian city names.
* Added "single family home" to default property type.
* Fixed spelling error on settings page. 

= 0.6.2.4 =
* Fixed logging issue in get_property();

= 0.6.2.3 =
* Fixed issue with slashes in property names preventing Google Maps from working.

= 0.6.2.2 =
* Fixed array_merge() warning in class_functions.php on line 532.

= 0.6.2.1 =
* Fixed array_merge() warning in class_functions.php on line 529.
* Added additional installation instructions.

= 0.6.2 =
* Updated meta data inheritance, now phsycally saved to database for all children properties on parent save.

= 0.6.1 =
* Re-upload, action-hooks.php was not uploaded to repository.

= 0.6.0 =
* Added "Developer" feature - a UI for creating new property types, attributes, etc.
* Added a new shortcode for displaying featured properties.
* Improved loading time by approximately 60%.
* Rewrite WPP_F::get_properties(), significantly improving search.
* Improved search widget, made compatible with custom property types.
* Fixed bug get_post_thumbnail_id() bug caused by running WP-Property with themes that don't support featured thumbnails.

= 0.5.5 =
* Added new installation message with instructions.
* Added toggable "Feature" button to the overview page.
* Hid monthly filter on overview page - not applicable to properties.

= 0.5.4 =
* Hid "Quick Edit" for property types.
* Improved default front-end CSS.
* Fixed problem with default "large" image not being set for Widget images, not defaults to WP "large" image type.

= 0.5.3 =
* Improved Settings Page -> custom image sizes can now be added using UI.
* Added new columns to overview page.
* Modified get_property().
* Improved Widgets, adding a search widget.

= 0.5.2 =
* Modified to work with default slug of 'property' in the event of a property page not being set.

= 0.5.1 =
* Fixed issue with incorrect folder structure.

= 0.5 =
* Initial Public Release.