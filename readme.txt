=== WP-Property - WordPress Property and Real Estate Management ===
Contributors: andypotanin
Donate link: http://twincitiestech.com/
Tags: property management, real estate, listings, properties, property
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: trunk


Web-Invoice lets you create and send web invoices and setup recurring billing for your clients.

== Description ==

Developed by the same people who brought you [WP-Invoice](http://wordpress.org/extend/plugins/wp-invoice/), comes WP-Property. As always, integration is seamless, the system is expandable and customizable, functionality is rich, and we are here to support it.

[vimeo http://vimeo.com/14280748]

This is not a "collection" of plugins, but one full suite. You will not have to download and match together a plethora of other plugins, in the hopes of them working well together, to have all the features you need.

The only requirement, you have to use WordPress 3.0.

Listing Demo: http://sites.twincitiestech.com/wp-property/properties/crossing-town-center/

Property Overview Demo: http://sites.twincitiestech.com/wp-property/properties/

Some features:

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

1. Upload 'wp-property' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit Settings -> Properties page for configuration

Please see the [WP Property plugin home page](http://twincitiestech.com/plugins/wp-property/) for details.
 
== Screenshots ==

1. Properties Overview
2. Editing Property 
3. Customize Frontend with Property Widgets
4. Property Listings
5. A Building  Page
6. Image from gallery enlarged

== Frequently Asked Questions ==

= How do I configure the plugin? =

There are two ways.  The easy way is to go to the Settings -> Properties page, many settings can be configured there.  A more advanced way, which does require some basic understanding of PHP and the WordPress API, is to use hooks and filters.  We've setup a page to assist others with customizing, and expanding on, WP-Property.  View the [WP-Property API documentation](http://twincitiestech.com/plugins/wp-property/api-documentation/) here. 

= How do I upload property images? =

You would do it the same way as if you were editing a post or a page.  On the property editing page, click the Image icon above the content area, and upload images into the media library.  If you want the images to show up on the front-end, you may want to visit Appearance -> Widgets and setup the Property Gallery widget to show up on the property page.

= How do I suggest an idea? =

You can send us a message via our website, or, preferably, visit our [UserVoice](http://wpproperty.uservoice.com/) page to submit new, and vote on existing, ideas. 

= I like where this is going, but how do I get customization? =

If you submit a popular idea on UserVoice, we WILL integrate it sooner or later.  If you need something custom, and urgent, [contact us](http://twincitiestech.com/contact-us/)

== Upgrade Notice ==

= 0.5.3 =
We are still in early stages, so updates will be coming out routinely.  Please do not hesitate to send us feedback and suggestions. 


== Changelog ==

= 0.5.4 =
* Hid "Quick Edit" for property types
* Improved default front-end CSS
* Fixed problem with default "large" image not being set for Widget images, not defaults to WP "large" image type

= 0.5.3 =
* Improved Settings Page -> custom image sizes can now be added using UI
* Added new columns to overview page
* Modified get_property() 
* Improved Widgets, adding a search widget

= 0.5.2 =
* Modified to work with default slug of 'property' in the event of a property page not being set

= 0.5.1 =
* Fixed issue with incorrect folder structure

= 0.5 =
* Initial Public Release