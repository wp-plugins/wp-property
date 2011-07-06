<?php
/**
 * WP-Property Overview Template
 *
 * To customize this file, copy it into your theme directory, and the plugin will
 * automatically load your version.
 *
 * You can also customize it based on property type.  For example, to create a custom
 * overview page for 'building' property type, create a file called property-overview-building.php
 * into your theme directory.
 *
 *
 * Settings passed via shortcode:
 * $properties: either array of properties or false
 * $show_children: default true
 * $thumbnail_size: slug of thumbnail to use for overview page
 * $thumbnail_sizes: array of image dimensions for the thumbnail_size type
 * $fancybox_preview: default loaded from configuration
 * $child_properties_title: default "Floor plans at location:"
 *
 *
 *
 * @version 1.4
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
*/

if($properties): ?>
	
<div class="wpp_row_view wpp_property_view_result">
<div class="all-properties">
    <?php
    unset($properties['total']); // VERY IMPORTANT!!!
    foreach($properties as $property_id):
        // Get property array/object and run it through prepare_property_for_display(), which runs all filters
        $property = prepare_property_for_display(get_property($property_id, "get_property['children']={$show_property['children']}"));

        // Configure variables
        if($fancybox_preview == 'true') {
            $thumbnail_link = $property['featured_image_url'];
            $link_class = 'fancybox_image';
        } else {
            $thumbnail_link = $property['permalink'];
        }
        
        $image = wpp_get_image_link($property['featured_image'], $thumbnail_size);
        // Check IMG (thumbnail) width and height to set css styles of blocks
        if (!empty($image)) {
            if(empty($img_width) || empty($img_height)) {
                preg_match('/([\d]{2,3})x([\d]{2,3})\.(jpg|gif|png)/' , $image, $matches);
                
                if(empty($img_width) && !empty($matches[1])) {
                    $img_width = $matches[1];
                }
                if(empty($img_height) && !empty($matches[2])) {
                    $img_height = $matches[2];
                }
            }
        }

      ?>

    <div class="property_div <?php echo $property['post_type']; ?> clearfix">

        <div class="wpp_overview_left_column">
            <?php if(!empty($image)): ?>
            <div class="property_image">
                <a href="<?php echo $thumbnail_link; ?>" title="<?php echo $property['post_title'] . ($property['parent_title'] ? __(' of ', 'wpp') . $property['parent_title'] : "");?>"  class="property_overview_thumb property_overview_thumb_<?php echo $thumbnail_size; ?> <?php echo $link_class; ?>" rel="properties" >
                    <img width="<?php echo $img_width; ?>" height="<?php echo $img_height; ?>" src="<?php echo $image; ?>" alt="<?php echo $property['post_title'];?>" />
                </a>
            </div>
            <?php endif; ?>
        </div><?php // .wpp_overview_left_column ?>

        <div class="wpp_overview_right_column">

            <ul class="wpp_overview_data">
                <li class="property_title">
                    <a href="<?php echo $property['permalink']; ?>"><?php echo $property['post_title']; ?></a>
                    <?php if($property['is_child']): ?>
                        of <a href='<?php echo $property['parent_link']; ?>'><?php echo $property['parent_title']; ?></a>
                    <?php endif; ?>
                </li>

            <?php if($property['custom_attribute_overview'] || $property['tagline']): ?>
                <li class="property_tagline">
                    <?php if($property['custom_attribute_overview']): ?>
                        <?php echo $property['custom_attribute_overview']; ?>
                    <?php elseif($property['tagline']): ?>
                        <?php echo $property['tagline']; ?>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if($property[phone_number]): ?>
                <li class="property_phone_number"><?php echo $property['phone_number']; ?></li>
            <?php endif; ?>

            <?php if($property[display_address]): ?>
                <li class="property_address"><a href="<?php echo $property['permalink']; ?>#property_map"><?php echo $property['display_address']; ?></a></li>
            <?php endif; ?>

            <?php if($property[price]): ?>
                <li class="property_price"><?php echo $property['price']; ?></li>
            <?php endif; ?>


            <?php if($show_children && $property['children']): ?>
            <li class="child_properties">
                <div class="wpd_floorplans_title"><?php echo $child_properties_title; ?></div>
                <table class="wpp_overview_child_properties_table">
                    <?php foreach($property['children'] as $child): ?>
                    <tr class="property_child_row">
                        <th class="property_child_title"><a href="<?php echo $child['permalink']; ?>"><?php echo $child['post_title']; ?></a></th>
                        <td class="property_child_price"><?php echo $child['price']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </li>
            <?php endif; ?>
       </ul>

        </div><?php // .wpp_right_column ?>

    </div><?php // .property_div ?>

    <?php endforeach; ?>
    </div><?php // .wpp_row_view ?>
	</div>
<?php else: ?>
<div class="wpp_nothing_found">
   <?php echo sprintf(__('Sorry, no properties found - try expanding your search, or <a href="%s">view all</a>.','wpp'), site_url().'/'.$wp_properties['configuration']['base_slug']); ?>
</div>
<?php endif; ?>