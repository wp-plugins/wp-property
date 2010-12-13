<?php
/*
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



/**
 Child Properties Widget
 */
 class ChildPropertiesWidget extends WP_Widget {
    /** constructor */
    function ChildPropertiesWidget() {
        parent::WP_Widget(false, $name = __('Child Properties','wpp'), array('description' => __('Show child properties (if any) for currently displayed property', 'wpp')));
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global $post, $wp_properties;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $image_type = $instance['image_type'];
        $stats = $instance['stats'];
        $address_format = $instance['address_format'];

        if(!isset($post->ID))
            return;

        $attachments = get_pages("child_of={$post->ID}&post_type=property");
 
            
        // Bail out if no children
        if(count($attachments) < 1)
            return;

        echo $before_widget;
        echo "<div id='wpp_child_properties_widget'>";


        if ( $title )
            echo $before_title . $title . $after_title;


        foreach($attachments as $attached):



            $this_property = WPP_F::get_property($attached->ID, 'return_object=true');
            $image_sizes = WPP_F::image_sizes($image_type);
            ?>
            <div class="apartment_entry clearfix">

                <a class="sidebar_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
                    <?php if(!empty($this_property->images[$image_type])): ?>
                    <img width="<?php echo $image_sizes[width]; ?>" height="<?php echo $image_sizes[height]; ?>" src="<?php echo $this_property->images[$image_type];?>" alt="<?php echo $this_property->post_title; ?> at <?php echo $this_property->location; ?> for <?php echo  $this_property->price; ?>" />
                    <?php endif; ?>
                </a>

                <ul class="sidebar_floorplan_status">
                <?php if(is_array($stats)): ?>
                <?php foreach($stats as $stat):
                    $content =  apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format);

                    if(empty($content))
                        continue;
                ?>
                    <li class="child_results <?php echo $stat; ?>"><span><?php echo $wp_properties['property_stats'][$stat]; ?>:</span><?php echo  $content;  ?></li>
                <?php endforeach; ?>
                <?php endif; ?>
                </ul>
           </div>
            <?php
                unset($this_property);
        endforeach;

        echo "</div>";
        echo $after_widget;

    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        global $wp_properties;
        $title = esc_attr($instance['title']);
        $address_format = esc_attr($instance['address_format']);
        $image_type = esc_attr($instance['image_type']);
        $property_stats = $instance['stats'];
        
        if(empty($address_format))
            $address_format = "[street_number] [street_name],\n[city], [state]";
            
          ?>
            <p><?php _e('The widget will not be displayed if the currently viewed property has no children.','wpp'); ?></p>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>

            </p>

            <p><?php _e('Select the stats you want to display','wpp'); ?></p>
                <?php foreach($wp_properties['property_stats'] as $stat => $label): ?>
                    <label for="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>">
                    <input id="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('stats'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
                    <?php if(is_array($property_stats) && in_array($stat, $property_stats)) echo " checked "; ?> />

                        <?php echo $label;?>
                    </label><br />
                <?php endforeach; ?>
                
                
            <p>
                <label for="<?php echo $this->get_field_id('address_format'); ?>"><?php _e('Address Format:','wpp'); ?>
                <textarea  style="width: 100%"  id="<?php echo $this->get_field_id('address_format'); ?>" name="<?php echo $this->get_field_name('address_format'); ?>"><?php echo $address_format; ?></textarea>
                </label>
            </p>


            
         <?php
    }

}

/**
 Lookup Widget
 */
 class FeaturedPropertiesWidget extends WP_Widget {
    /** constructor */
    function FeaturedPropertiesWidget() {
        parent::WP_Widget(false, $name = __('Featured Properties','wpp'), array('description' => __('List of properties that were marked as Featured', 'wpp')));
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global  $wp_properties;

        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $image_type = $instance['image_type'];
        $stats = $instance['stats'];
        
        $address_format = $instance['address_format'];
        
        
        if(empty($address_format))
            $address_format = "[street_number] [street_name],\n[city], [state]";        

        if(!$image_type)
            $image_type == '';


        $all_featured = WPP_F::get_properties("featured=true&property_type=all");
        
	         if (isset($all_featured['total'])) : unset($all_featured['total']);
	         endif;
	         

        // Bail out if no children
        if(!$all_featured)
            return;

        echo $before_widget;
        echo "<div id='wpp_featured_properties_widget'>";


        if ( $title )
            echo $before_title . $title . $after_title;


        foreach($all_featured as $featured):

            $this_property = WPP_F::get_property($featured, 'return_object=true');

            $image_sizes = WPP_F::image_sizes($image_type);
            ?>
            <div class="apartment_entry clearfix"  style="min-height: <?php echo $image_sizes['height']; ?>px;">

                <a class="sidebar_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
                    <?php if(!empty($this_property->images[$image_type])): ?>
                    <img width="<?php echo $image_sizes['width']; ?>" height="<?php echo $image_sizes['height']; ?>" src="<?php echo $this_property->images[$image_type];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property->post_title, $this_property->location, $this_property->price); ?>" />
                    <?php endif; ?>
                </a>
                <p class="title"><a href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>

                <ul class="sidebar_floorplan_status">
                <?php if(is_array($stats)): ?>
                <?php foreach($stats as $stat):

                    $content = apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format);

                    if(empty($content))
                        continue;
                        
                ?>
                    <li class="<?php echo $stat ?>"><span><?php echo $wp_properties['property_stats'][$stat]; ?>:</span>  <?php echo $content;  ?></li>
                <?php endforeach; ?>
                <?php endif; ?>
                </ul>
                <?php if ($instance['enable_more'] =='on')
                	echo '<p class="more"><a href="'. $this_property->permalink.'">'.__('More','wpp').'</a></p>'; ?>
           </div>
            <?php
                unset($this_property);
        endforeach;
        if ($instance['enable_view_all'] =='on')
            echo '<p class="view-all"><a href="'. site_url() .'/'. $wp_properties['configuration']['base_slug'] .'">'.__('View All','wpp').'</a></p>';
        echo '</div>';
        echo $after_widget;

    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {

        global $wp_properties;
        $title = esc_attr($instance['title']);
        $image_type = esc_attr($instance['image_type']);
        $property_stats = $instance['stats'];
        
        $address_format = esc_attr($instance['address_format']);
        $enable_more = $instance['enable_more'];
        $enable_view_all = $instance['enable_view_all'];
        
        
        if(empty($address_format))
            $address_format = "[street_number] [street_name],\n[city], [state]";
            
            
          ?>
             <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>

            </p>

            <p><?php _e('Select the stats you want to display','wpp') ?></p>
                <?php foreach($wp_properties['property_stats'] as $stat => $label): ?>
                    <label for="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>">
                    <input id="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('stats'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
                    <?php if(is_array($property_stats) && in_array($stat, $property_stats)) echo " checked "; ?>">

                        <?php echo $label;?>
                    </label><br />
                <?php endforeach; ?>
            <p>
                <label for="<?php echo $this->get_field_id('address_format'); ?>"><?php _e('Address Format:','wpp'); ?>
                <textarea style="width: 100%" id="<?php echo $this->get_field_id('address_format'); ?>" name="<?php echo $this->get_field_name('address_format'); ?>"><?php echo $address_format; ?></textarea>
                </label>
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('enable_more'); ?>">
                    <input id="<?php echo $this->get_field_id('enable_more'); ?>" name="<?php echo $this->get_field_name('enable_more'); ?>" type="checkbox" value="on" <?php if($enable_more=='on') echo " checked='checked';"; ?> />
                    <?php _e('Show "More" link?','wpp'); ?>
                </label>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id('enable_view_all'); ?>">
                    <input id="<?php echo $this->get_field_id('enable_view_all'); ?>" name="<?php echo $this->get_field_name('enable_view_all'); ?>" type="checkbox" value="on" <?php if($enable_view_all=='on') echo " checked='checked';"; ?> />
                    <?php _e('Show "View All" link?','wpp'); ?>
                </label>
            </p>
                            
         <?php
    }

}

// Default function to use in template directly
function wpp_featured_properties($args = false, $custom = false){
    if (!$args)
        $args = array(
            'before_title' => '<h3>',
            'after_title' => '</h3>',
            'before_widget' => '',
            'after_widget' => ''
        );

    $default = array(
        'title'         => __('Featured Properies','wpp'),
        'image_type'    => 'thumbnail',
        'stats'         => array('bedrooms','location', 'price'),
        'address_format'=> '[street_number] [street_name], [city], [state]'
    );
    if($custom)
        $default = array_merge($default, $custom);

    FeaturedPropertiesWidget::widget($args, $default);
}



/**
 Latest properties Widget
 */
 
 class LatestPropertiesWidget extends WP_Widget {
    /** constructor */
    function LatestPropertiesWidget() {
        parent::WP_Widget(false, $name = __('Latest Properties','wpp'), array('description' => __('List of the latest properties created on this site', 'wpp')));
    }
    
        /** @see WP_Widget::widget */
    function widget($args, $instance) {
    	global $wp_properties;
    	
    	extract( $args );
    	
    	$title 			= apply_filters('widget_title', $instance['title']);
    	$stats 			= $instance['stats'];
    	$image_type		= $instance['image_type'];
    	$show_image 	= $instance['show_image'];
    	$show_title 	= $instance['show_title'];
    	
    	if(!$image_type)
            $image_type == '';

        $arg = array(
            'post_type'			=> 'property',
            'numberposts'		=> $instance['amount_items'],
            'post_status'		=> 'publish',
            'post_parent'		=> null, // any parent
            'order'				=> 'DESC',
            'orderby'			=> 'post_date'
        );

      	$postslist = get_posts($arg);
     
		echo $before_widget;
			echo "<div id='wpp_latest_properties_widget'>";
     	  
     	   if ( $title )
             	echo $before_title . $title . $after_title;
			
			 
	     foreach ($postslist as $post){
	     	
	     	 echo '<div class="latest_entry clearfix">';
	     	 
	     		$this_property = WPP_F::get_property($post->ID, 'return_object=true');
	     		$image_sizes = WPP_F::image_sizes($image_type);
	     		
	  		if ($show_image =='on'){ ?>
                <a class="latest_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
                    <?php if(!empty($this_property->images[$image_type])){ ?>
                        <img width="<?php echo $image_sizes['width']; ?>" height="<?php echo $image_sizes[height]; ?>" src="<?php echo $this_property->images[$image_type];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property->post_title, $this_property->location, $this_property->price); ?>" />
                    <?php } ?>
                </a>
            <?php
            }
            echo '<ul>';
            
            if(is_array($stats)){ 	
	     	    foreach($stats as $stat){
                    $content = apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format);
                    if(empty($content)) continue;
                    echo '<li class="'. $stat .'"><span>'. $wp_properties['property_stats'][$stat] .':</span> '. $content .'</li>';
                }
            }

            if ($show_title == 'on'){
                echo '<li class="last_titles"><a href="'. $this_property->permalink.'">'. $post->post_title .'</a></li>';
            }
	
            echo '</ul></div>';
	
		}
		
		if ($instance['enable_view_all'] =='on')
	        echo '<p class="view-all"><a href="'. site_url() .'/'. $wp_properties['configuration']['base_slug'] .'">'.__('View All','wpp').'</a></p>';
	    echo '</div>'; 
		echo $after_widget;
    }
    
    
    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        return $new_instance;
    }
    
     /** @see WP_Widget::form */
    function form($instance) {
    
        global $wp_properties;
        
        $title 				= esc_attr($instance['title']);
        $enable_view_all	= esc_attr($instance['enable_view_all']);
        $amount_items 		= esc_attr($instance['amount_items']);
        $property_stats 	= $instance['stats'];
        $image_type 		= esc_attr($instance['image_type']);
        $show_image			= $instance['show_image'];
        $show_title			= $instance['show_title'];
?>
<script type="text/javascript">
//hide and show dropdown whith thumb settings
jQuery(document).ready(function($){
	$('input.check_me').change(function(){
		if($(this).attr('checked') == true){
			$('p#choose_thumb').css('display','block');
		}else{
			$('p#choose_thumb').css('display','none');
		}
	})
});
</script>
             <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo (!empty($title)) ? $title : __('Latest Properties', 'wpp'); ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('show_image'); ?>">
                    <input id="<?php echo $this->get_field_id('show_image'); ?>" class="check_me" name="<?php echo $this->get_field_name('show_image'); ?>" type="checkbox" value="on" <?php if($show_image=='on') echo " checked='checked';"; ?> />
                    <?php _e('Show Image?','wpp'); ?>
                </label>
            </p>
             <p id="choose_thumb" <?php 
             if($show_image == 'on') 
             	echo 'style="display:block;"'; 
             else 
             	echo 'style="display:none;"'; 
             ?>>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>
            
            <p>
            <label for="<?php echo $this->get_field_id('per_page'); ?>"><?php _e('How many to display?', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('amount_items'); ?>" name="<?php echo $this->get_field_name('amount_items'); ?>" type="text" value="<?php echo $amount_items; ?>" />
                </label>
            </p>
            
            
            <p><?php _e('Select stats you want to display','wpp') ?></p>
            
            <p>
                <label for="<?php echo $this->get_field_id('show_title'); ?>">
                    <input id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" type="checkbox" value="on" <?php if($show_title=='on') echo " checked='checked';"; ?> />
                    <?php _e('Title','wpp'); ?>
                </label>
            </p>
                <?php foreach($wp_properties['property_stats'] as $stat => $label): ?>
                    <label for="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>">
                    <input id="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('stats'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
                    <?php if(is_array($property_stats) && in_array($stat, $property_stats)) echo " checked "; ?>">

                        <?php echo $label;?>
                    </label><br />
                <?php endforeach;
    }
 
 }



/**
 Property Search Widget
 */
 class SearchPropertiesWidget extends WP_Widget {
    /** constructor */
    function SearchPropertiesWidget() {
        parent::WP_Widget(false, $name = __('Property Search','wpp'), array('description' => __('Display a highly customizable property search form', 'wpp')));
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global  $wp_properties;

        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);

        $searchable_attributes = $instance['searchable_attributes'];
        $searchable_property_types = $instance['searchable_property_types'];
        if(isset($instance['use_pagi']) && $instance['use_pagi']=='on')
            $per_page = $instance['per_page'];

        if(!is_array($searchable_attributes))
            return;
                
        if(!function_exists('draw_property_search_form'))
            return;

        echo $before_widget;
            echo "<div id='wpp_search_properties_widget'>";

                if ( $title )
                    echo $before_title . $title . $after_title;

                draw_property_search_form($searchable_attributes, $searchable_property_types, $per_page);

            echo "</div>";
        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        //Recache searchable values for search widget form
        $searchable_attributes = $new_instance['searchable_attributes'];
        $searchable_property_types = $new_instance['searchable_property_types'];
        WPP_F::get_search_values($searchable_attributes, $searchable_property_types, false);
        
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        global $wp_properties;
        
        $title = esc_attr($instance['title']);
        $all_searchable_attributes = array_unique($wp_properties['searchable_attributes']);
        $searchable_attributes = $instance['searchable_attributes'];
        $use_pagi = $instance['use_pagi'];
        $per_page = $instance['per_page'];

        $all_searchable_property_types = array_unique($wp_properties['searchable_property_types']);
        $searchable_property_types = $instance['searchable_property_types'];

        if(!is_array($all_searchable_property_types)) { ?>
            <p><?php _e('No searchable property types were found.','wpp'); ?></p>
    <?php }

          ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo (!empty($title)) ? $title : __('Property Search', 'wpp'); ?>" />
            </label>
        </p>

        <p><?php _e('Pagination options for search results','wpp'); echo $use_pagi; ?></p>
        <ul>
            <li>
                <label for="<?php echo $this->get_field_id('use_pagi'); ?>">
                    <input id="<?php echo $this->get_field_id('use_pagi'); ?>" name="<?php echo $this->get_field_name('use_pagi'); ?>" type="checkbox" value="on" <?php if($use_pagi=='on') echo " checked='checked';"; ?> />
                    <?php _e('Do you want to use pagination?','wpp'); ?>
                </label>
            </li>
            <li>
                <label for="<?php echo $this->get_field_id('per_page'); ?>"><?php _e('Items per page', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('per_page'); ?>" name="<?php echo $this->get_field_name('per_page'); ?>" type="text" value="<?php echo $per_page; ?>" />
                </label>
            </li>
        </ul>
    

            <p><?php _e('Property types to search:','wpp'); ?></p>
            <p>
                <ul>
                <?php if(is_array($all_searchable_property_types))
                 foreach($all_searchable_property_types as $property_type): ?>
                <li>
                <label for="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>">
                    <input id="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>" name="<?php echo $this->get_field_name('searchable_property_types'); ?>[]" type="checkbox" <?php if (empty($searchable_property_types)): echo  'checked="checked"'; endif; ?> value="<?php echo $property_type; ?>" <?php if(is_array($searchable_property_types) && in_array($property_type, $searchable_property_types)) { echo " checked "; } ?> />
                    
                    <?php echo (!empty($wp_properties['property_types'][$property_type]) ? $wp_properties['property_types'][$property_type] : ucwords($property_type))  ;?>
                </label>
                </li>
                <?php endforeach; ?>
                </ul>
            </p>
            <br />
            <p><?php _e('Select the attributes you want to search.','wpp'); ?></p>
            <p>
                <ul>
            <?php if(is_array($all_searchable_attributes))
            		foreach($all_searchable_attributes as $attribute): ?>
		                <li>
		                <label for="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>">
		                    <input id="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>" name="<?php echo $this->get_field_name('searchable_attributes'); ?>[]" type="checkbox" <?php if (empty($searchable_attributes)): echo  'checked="checked"'; endif; ?> value="<?php echo $attribute; ?>"
		                    <?php if(is_array($searchable_attributes) && in_array($attribute, $searchable_attributes)) { echo " checked ";  }?> />
		
		                    <?php echo (!empty($wp_properties['property_stats'][$attribute]) ? $wp_properties['property_stats'][$attribute] : ucwords($attribute))  ;?>
		                </label>
		                </li>
                <?php endforeach; ?>

                </ul>
            </p>
            <p><?php _e('City is an automatically created attribute once the address is validated.','wpp'); ?></p>
         <?php

    }

}

// Default function to use in template directly
function wpp_search_widget($args = false, $custom = false){
    if (!$args)
        $args = array(
            'before_title' => '<h3>',
            'after_title' => '</h3>',
            'before_widget' => '',
            'after_widget' => ''
        );

    $default = array(
        'title'                 => __('Search Properies','wpp'),
        'use_pagi'              => 'on',
        'per_page'              => 10,
        'searchable_attributes' => array('bedrooms','bathrooms','city', 'price')
    );
    if($custom)
        $default = array_merge($default, $custom);

    SearchPropertiesWidget::widget($args, $default);
}

/**
 Property Gallery Widget
 */
 class GalleryPropertiesWidget extends WP_Widget {
    /** constructor */
    function GalleryPropertiesWidget() {
        parent::WP_Widget(false, $name = __('Property Gallery','wpp'), array('description' => __('List of all images attached to the current property', 'wpp')));
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {

        global  $wp_properties, $post;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $image_type = esc_attr($instance['image_type']);
        $big_image_type = esc_attr($instance['big_image_type']);
        $gallery_count = esc_attr($instance['gallery_count']);

        if(empty($big_image_type))
            $big_image_type = 'large';

        if(empty($image_type))
            $image_type = 'thumbnail';
			
			

        $thumbnail_dimensions = WPP_F::image_sizes($image_type);

        echo $before_widget;
        echo "<div id='wpp_gallery_widget'>";


        if ( $title )
            echo $before_title . $title . $after_title;

        if($post->gallery) {
            $real_count = 0;
            foreach($post->gallery as $image) {

 
                ?>
                <div class="sidebar_gallery_item">
                <a href="<?php echo $image[$big_image_type]; ?>"  class="fancybox_image" rel="property_gallery">
                    <img src="<?php echo $image[$image_type]; ?>" alt="<?php echo $image[post_title]; ?>" class="size-thumbnail "  width="<?php echo $thumbnail_dimensions[width]; ?>" height="<?php echo $thumbnail_dimensions[height]; ?>" />
                </a>
                </div>
                <?php
                $real_count++;


                if(!empty($gallery_count) && $gallery_count == $real_count)
                    break;

            }
        }

        echo "</div>";
        echo $after_widget;

    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {

        global $wp_properties;
        $title = esc_attr($instance['title']);
        $image_type = $instance['image_type'];
        $big_image_type = $instance['big_image_type'];
        $gallery_count = $instance['gallery_count'];

          ?>
             <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Thumbnail Size:'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>

            </p>
            <p>
                <label for="<?php echo $this->get_field_id('big_image_type'); ?>"><?php _e('Popup Image Size:'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('big_image_type') . "&selected=" . $big_image_type); ?>
            </label>

            </p>
            <p>
            <label for="<?php echo $this->get_field_id('gallery_count') ?>">
               <?php $number_of_images = '<input size="3" type="text" id="'. $this->get_field_id('gallery_count') .'" name="'. $this->get_field_name('gallery_count').'" value="'. $gallery_count.'" />'; ?>
               <?php echo sprintf(__('Show %s Images','wpp'), $number_of_images); ?>
            </label>

            </p>


         <?php

    }

}
