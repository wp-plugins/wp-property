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

        parent::WP_Widget(false, $name = 'Child Properties');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global $post, $wp_properties;
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $image_type = $instance['image_type'];
        $stats = $instance['stats'];

		if(!isset($post->ID))
			return;

		$attachments = get_pages("child_of={$post->ID}&post_type=property");

 		
		// Bail out if no children
		if(count($attachments) < 1)
			return;

		echo $before_widget;


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
					$content =  apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat);

					if(empty($content))
						continue;
				?>
					<li><span><?php echo $wp_properties['property_stats'][$stat]; ?>:</span> <p><?php echo  $content;  ?></p></li>
				<?php endforeach; ?>
				<?php endif; ?>
				</ul>
		   </div>
			<?php
				unset($this_property);
		endforeach;

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
          ?>
			<p>The widget will not be displayed if the currently viewed property has no children.</p>
            <p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>

            <p>
				<label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:'); ?>				
				<?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
			</label>

			</p>

			<p>Select the stats you want to display</p>
				<?php foreach($wp_properties['property_stats'] as $stat => $label): ?>
					<label for="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>">
					<input id="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('stats'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
					<?php if(is_array($property_stats) && in_array($stat, $property_stats)) echo " checked "; ?>">

						<?php echo $label;?>
					</label><br />
				<?php endforeach; ?>
         <?php
    }

}

/**
 Lookup Widget
 */
 class FeaturedPropertiesWidget extends WP_Widget {
    /** constructor */
    function FeaturedPropertiesWidget() {
        parent::WP_Widget(false, $name = 'Featured Properties');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {

        global  $wp_properties;
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $image_type = $instance['image_type'];
        $stats = $instance['stats'];

		if(!$image_type)
			$image_type == '';


		$all_featured = WPP_F::get_properties("featured=true&property_type=all");

		// Bail out if no children
		if(!$all_featured)
			return;

		echo $before_widget;


		if ( $title )
			echo $before_title . $title . $after_title;


		foreach($all_featured as $featured):

			$this_property = WPP_F::get_property($featured, 'return_object=true');

  			$image_sizes = WPP_F::image_sizes($image_type);
 			?>
			<div class="apartment_entry clearfix"  style="min-height: <?php echo $image_sizes[height]; ?>px;">

				<a class="sidebar_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
					<?php if(!empty($this_property->images[$image_type])): ?>
					<img width="<?php echo $image_sizes[width]; ?>" height="<?php echo $image_sizes[height]; ?>" src="<?php echo $this_property->images[$image_type];?>" alt="<?php echo $this_property->post_title; ?> at <?php echo $this_property->location; ?> for <?php echo  $this_property->price; ?>" />
					<?php endif; ?>
				</a>

				<ul class="sidebar_floorplan_status">
				<?php if(is_array($stats)): ?>
				<?php foreach($stats as $stat):

					$content =  apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat);

					if(empty($content))
						continue;
				?>
					<li><span><?php echo $wp_properties['property_stats'][$stat]; ?>:</span>  <p><?php echo $content;  ?></p></li>
				<?php endforeach; ?>
				<?php endif; ?>
				</ul>
		   </div>
			<?php
				unset($this_property);
		endforeach;

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
          ?>
             <p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>

            <p>
				<label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:'); ?>
				<?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
			</label>

			</p>

			<p>Select the stats you want to display</p>
				<?php foreach($wp_properties['property_stats'] as $stat => $label): ?>
					<label for="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>">
					<input id="<?php echo $this->get_field_id('stats'); ?>_<?php echo $stat; ?>" name="<?php echo $this->get_field_name('stats'); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
					<?php if(is_array($property_stats) && in_array($stat, $property_stats)) echo " checked "; ?>">

						<?php echo $label;?>
					</label><br />
				<?php endforeach; ?>
         <?php
    }

}


/**
 Property Search Widget
 */
 class SearchPropertiesWidget extends WP_Widget {
    /** constructor */
    function SearchPropertiesWidget() {
        parent::WP_Widget(false, $name = 'Property Search');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {

        global  $wp_properties;
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
 		$searchable_attributes = $instance['searchable_attributes'];
 		$searchable_property_types = $instance['searchable_property_types'];
 
		if(!is_array($searchable_attributes))
			return;
 
		if(!function_exists('draw_property_search_form'))
			return;

		echo $before_widget;


		if ( $title )
			echo $before_title . $title . $after_title;

		draw_property_search_form($searchable_attributes, $searchable_property_types);

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
		$all_searchable_attributes = array_unique($wp_properties['searchable_attributes']);
 		$searchable_attributes = $instance['searchable_attributes'];
 		
		$all_searchable_property_types = array_unique($wp_properties['searchable_property_types']);
		$searchable_property_types = $instance['searchable_property_types'];
 
  
          ?>
             <p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
				</label>
			</p>

			<p>Property types to search:</p>
			<p>
				<ul>
				<?php if(is_array($all_searchable_property_types)) foreach($all_searchable_property_types as $property_type): ?>
				<li>
				<label for="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>">
					<input id="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>" name="<?php echo $this->get_field_name('searchable_property_types'); ?>[]" type="checkbox" value="<?php echo $property_type; ?>"
					<?php
					if(is_array($searchable_property_types) && in_array($property_type, $searchable_property_types))
						echo " checked "; ?>
					">

					<?php echo (!empty($wp_properties['property_types'][$property_type]) ? $wp_properties['property_types'][$property_type] : ucwords($property_type))  ;?>
				</label>
				</li>
				<?php endforeach; ?>	
				</ul>
			</p>
			<br />
			<p>Select the attributes you want to search. </p>
			<p>
				<ul>
			<?php  if(is_array($all_searchable_attributes))  foreach($all_searchable_attributes as $attribute): ?>
				<li>
				<label for="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>">
					<input id="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>" name="<?php echo $this->get_field_name('searchable_attributes'); ?>[]" type="checkbox" value="<?php echo $attribute; ?>"
					<?php
					if(is_array($searchable_attributes) && in_array($attribute, $searchable_attributes))
						echo " checked "; ?>
					">

					<?php echo (!empty($wp_properties['property_stats'][$attribute]) ? $wp_properties['property_stats'][$attribute] : ucwords($attribute))  ;?>
				</label>
				</li>
				<?php endforeach; ?>
				
				</ul>
			</p>
			<p>City is an automatically created attribute once the address is validated.</p>
         <?php

    }

}

/**
 Property Gallery Widget
 */
 class GalleryPropertiesWidget extends WP_Widget {
    /** constructor */
    function GalleryPropertiesWidget() {
        parent::WP_Widget(false, $name = 'Property Gallery');
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
 		 
		$thumbnail_dimensions = WPP_F::image_sizes($image_type);
		
		
		echo $before_widget;


		if ( $title )
			echo $before_title . $title . $after_title;
  
		if($post->gallery) {
			$real_count = 0;
			foreach($post->gallery as $image) {

 		
					
				?>
				<div class="sidebar_gallery_item">
				<a href="<?php echo $image[$big_image_type]; ?>"  class="fancybox_image" rel="property_gallery">
					<img src="<?php echo $image[$image_type]; ?>" alt="<?php echo $image[post_title]; ?>"  width="<?php echo $thumbnail_dimensions[width]; ?>" height="<?php echo $thumbnail_dimensions[height]; ?>" />
				</a>
				</div>				
				<?php
				$real_count++;
				
					
				if(!empty($gallery_count) && $gallery_count == $real_count) 
					break;
					
			}
		}
 		
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
				Show 
				<input size="3" type="text" id="<?php echo $this->get_field_id('gallery_count'); ?>" name="<?php echo $this->get_field_name('gallery_count'); ?>" value="<?php echo $gallery_count; ?>" />
				Images.
			</label>

			</p>

 
         <?php

    }

}


?>