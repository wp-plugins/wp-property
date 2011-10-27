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
 * Tag cloud widget class
 *
 * @todo Need to fix the result page
 * @since 1.01
 */ /*
class WP_Property_Tag_Cloud extends WP_Widget {

  function WP_Property_Tag_Cloud() {
    $widget_ops = array( 'description' => __( "Your most used property tags in cloud format") );
    $this->WP_Widget('tag_cloud', __('Property Tag Cloud'), $widget_ops);
  }

  function widget( $args, $instance ) {
    extract($args);
    $current_taxonomy = $this->_get_current_taxonomy($instance);
    if ( !empty($instance['title']) ) {
      $title = $instance['title'];
    } else {
      if ( 'post_tag' == $current_taxonomy ) {
        $title = __('Tags');
      } else {
        $tax = get_taxonomy($current_taxonomy);
        $title = $tax->labels->name;
      }
    }
    $title = apply_filters('widget_title', $title, $instance, $this->id_base);

    echo $before_widget;
    if ( $title )
      echo $before_title . $title . $after_title;
    echo '<div>';
    wp_tag_cloud( apply_filters('widget_tag_cloud_args', array('taxonomy' => $current_taxonomy) ) );
    echo "</div>\n";
    echo $after_widget;
  }

  function update( $new_instance, $old_instance ) {
    $instance['title'] = strip_tags(stripslashes($new_instance['title']));
    $instance['taxonomy'] = stripslashes($new_instance['taxonomy']);
    return $instance;
  }

  function form( $instance ) {
    $current_taxonomy = $this->_get_current_taxonomy($instance);
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
  <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
  <p><label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:') ?></label>
  <select class="widefat" id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
  <?php foreach ( get_object_taxonomies('property') as $taxonomy ) :
        $tax = get_taxonomy($taxonomy);
        if ( !$tax->show_tagcloud || empty($tax->labels->name) )
          continue;
  ?>
    <option value="<?php echo esc_attr($taxonomy) ?>" <?php selected($taxonomy, $current_taxonomy) ?>><?php echo $tax->labels->name; ?></option>
  <?php endforeach; ?>
  </select></p><?php
  }

  function _get_current_taxonomy($instance) {
    if ( !empty($instance['taxonomy']) && taxonomy_exists($instance['taxonomy']) )
      return $instance['taxonomy'];

    return 'post_tag';
  }
}
*/

/**
Other Properties Widget
 */
class OtherPropertiesWidget extends WP_Widget {
  /** constructor */
  function OtherPropertiesWidget() {
    parent::WP_Widget(false, $name = __('Other Properties','wpp'), array('description' => __('Show other properties (if any) with the same Parent', 'wpp')));
  }

  /** @see WP_Widget::widget */
  function widget($args, $instance) {
    global $post, $wp_properties;
    extract( $args );
    $title       = apply_filters('widget_title', $instance['title']);
    $instance = apply_filters('OtherPropertiesWidget', $instance);
    $show_title    = $instance['show_title'];
    $image_type   = $instance['image_type'];
    $hide_image    = $instance['hide_image'];
    $stats       = $instance['stats'];
    $address_format = $instance['address_format'];
    $amount_items  = $instance['amount_items'];

    if(!isset($post->ID))
      return;

    $bill = $post->post_parent;

    $argus = array(
      'post_type'      => 'property',
      'numberposts'    => $amount_items +1,
      'post_status'    => 'publish',
      'post_parent'    => $bill
    );

    $jams = get_posts($argus);

    // Bail out if no children
    if(count($jams) < 2)
        return;

    //The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique
    $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);

    echo $before_widget;
    echo "<div class='wpp_other_properties_widget'>";


    if ( $title )
        echo $before_title . $title . $after_title;


    foreach($jams as $jam):
    if ($jam->ID == $post->ID){
        continue;
       }

        $this_property  = WPP_F::get_property($jam->ID, 'return_object=true');
        $image = wpp_get_image_link($this_property->featured_image, $image_type, array('return'=>'array'));

        ?>
        <div class="property_widget_block apartment_entry clearfix" style="<?php echo ($image['width'] ? 'width: ' . ($image['width']+5) . 'px;' : ''); ?>" >

      <?php if ($hide_image !=='on'){ ?>
          <a class="sidebar_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
        <?php if ($show_title == 'on'): ?>
          <p class="title"><a href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php endif; ?>
            <?php if(!empty($image)){ ?>
              <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" src="<?php echo $image['link'];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property->post_title, $this_property->location, $this_property->price); ?>" />
            <?php } ?>
          </a>
      <?php } ?>

        <ul class="wpp_widget_attribute_list">
        <?php if(is_array($stats)): ?>
        <?php foreach($stats as $stat):
        $content = nl2br(apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format));

        if(empty($content)) continue; ?>
        <li class="<?php echo $stat ?>">
          <span class='attribute'><?php echo $wp_properties['property_stats'][$stat]; ?>:</span>
          <span class='value'><?php echo $content;  ?></span></li>
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
        $title         = esc_attr($instance['title']);
        $show_title      = $instance['show_title'];
        $amount_items     = esc_attr($instance['amount_items']);
        $address_format   = esc_attr($instance['address_format']);
        $image_type     = esc_attr($instance['image_type']);
        $property_stats   = $instance['stats'];
        $hide_image     = $instance['hide_image'];
        $enable_more     = $instance['enable_more'];
        $enable_view_all   = $instance['enable_view_all'];

        if(empty($address_format))
            $address_format = "[street_number] [street_name], [city], [state]";

          ?>

<script type="text/javascript">
//hide and show dropdown whith thumb settings
jQuery(document).ready(function($){
  jQuery('input.check_me_other').change(function(){
    if(jQuery(this).attr('checked') !== true){
      jQuery('p#choose_thumb_other').css('display','block');
    }else{
      jQuery('p#choose_thumb_other').css('display','none');
    }
  })
});
</script>
            <p><?php _e('The widget will not be displayed if the currently viewed property has no children.','wpp'); ?></p>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('hide_image'); ?>">
            <input id="<?php echo $this->get_field_id('hide_image'); ?>" class="check_me_other" name="<?php echo $this->get_field_name('hide_image'); ?>" type="checkbox" value="on" <?php if($hide_image=='on') echo "checked='checked'"; ?> />
                    <?php _e('Hide Images?','wpp'); ?>
                </label>
            </p>
             <p id="choose_thumb_other" <?php
             if($hide_image !== 'on')
               echo 'style="display:block;"';
             else
               echo 'style="display:none;"';
             ?>>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>
             <p>
            <label for="<?php echo $this->get_field_id('amount_items'); ?>"><?php _e('Listings to display?', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('amount_items'); ?>" name="<?php echo $this->get_field_name('amount_items'); ?>" type="text" value="<?php echo (empty($amount_items)) ? 5 : $amount_items; ?>" />
                </label>
            </p>

             <p><?php _e('Select the stats you want to display','wpp'); ?></p>
             <p>
                <label for="<?php echo $this->get_field_id('show_title'); ?>">
                    <input id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" type="checkbox" value="on" <?php if($show_title=='on') echo " checked='checked';"; ?> />
                    <?php _e('Title','wpp'); ?>
                </label>
            </p>
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
        $title       = apply_filters('widget_title', $instance['title']);
    $instance = apply_filters('ChildPropertiesWidget', $instance);
        $show_title    = $instance['show_title'];
        $image_type   = $instance['image_type'];
        $hide_image   = $instance['hide_image'];
        $stats       = $instance['stats'];
        $address_format = $instance['address_format'];
        $amount_items  = $instance['amount_items'];

        if(!isset($post->ID))
            return;

       // $attachments = get_pages("child_of={$post->ID}&post_type=property");

        $argus = array(
            'post_type'      => 'property',
            'numberposts'    => $amount_items ,
            'post_status'    => 'publish',
            'post_parent'    => $post->ID,

           );


        $attachments = get_posts($argus);


        // Bail out if no children
        if(count($attachments) < 1)
            return;


        $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);

        echo $before_widget;
        echo "<div class='wpp_child_properties_widget'>";


        if ( $title )
            echo $before_title . $title . $after_title;


        foreach($attachments as $attached):



            $this_property = WPP_F::get_property($attached->ID, 'return_object=true');
            $image = wpp_get_image_link($this_property->featured_image, $image_type, array('return'=>'array'));
            ?>
            <div class="property_widget_block apartment_entry clearfix" style="<?php echo ($image['width'] ? 'width: ' . ($image['width']+5) . 'px;' : ''); ?>">

             <?php if ($hide_image !== 'on'){ ?>
                <a class="sidebar_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
                      <?php if(!empty($image)){ ?>
                        <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" src="<?php echo $image['link'];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property->post_title, $this_property->location, $this_property->price); ?>" />
                    <?php } ?>
                </a>
            <?php
            }  if ($show_title == 'on'): ?>
                <p class="title"><a href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php endif; ?>

               <ul class="wpp_widget_attribute_list">
                <?php if(is_array($stats)): ?>
                <?php foreach($stats as $stat):
                    $content = nl2br(apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format));

                    if(empty($content)) continue; ?>

          <li class="<?php echo $stat ?>"><span class='attribute'><?php echo $wp_properties['property_stats'][$stat]; ?>:</span>  <span class='value'><?php echo $content;  ?></span></li>
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
        $title         = esc_attr($instance['title']);
        $show_title      = $instance['show_title'];
        $address_format    = esc_attr($instance['address_format']);
        $image_type     = esc_attr($instance['image_type']);
        $amount_items     = esc_attr($instance['amount_items']);
        $property_stats   = $instance['stats'];
        $hide_image      = $instance['hide_image'];
        $enable_more     = $instance['enable_more'];
        $enable_view_all   = $instance['enable_view_all'];


        if(empty($address_format))
            $address_format = "[street_number] [street_name], [city], [state]";

          ?>
<script type="text/javascript">
//hide and show dropdown whith thumb settings
jQuery(document).ready(function($){
  $('input.check_me_child').change(function(){
    if($(this).attr('checked') !== true){
      $('p#choose_thumb_child').css('display','block');
    }else{
      $('p#choose_thumb_child').css('display','none');
    }
  })
});
</script>
                 <p><?php _e('The widget will not be displayed if the currently viewed property has no children.','wpp'); ?></p>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>


            <p>
                <label for="<?php echo $this->get_field_id('hide_image'); ?>">
                    <input id="<?php echo $this->get_field_id('hide_image'); ?>" class="check_me_child" name="<?php echo $this->get_field_name('hide_image'); ?>" type="checkbox" value="on" <?php if($hide_image=='on') echo " checked='checked';"; ?> />
                    <?php _e('Hide Images?','wpp'); ?>
                </label>
            </p>
             <p id="choose_thumb_child" <?php
             if($hide_image !== 'on')
               echo 'style="display:block;"';
             else
               echo 'style="display:none;"';
             ?>>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>

             <p>
            <label for="<?php echo $this->get_field_id('amount_items'); ?>"><?php _e('Listings to display?', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('amount_items'); ?>" name="<?php echo $this->get_field_name('amount_items'); ?>" type="text" value="<?php echo (empty($amount_items)) ? 5 : $amount_items; ?>" />
                </label>
            </p>




            <p><?php _e('Select the stats you want to display','wpp'); ?></p>
        <p>
          <label for="<?php echo $this->get_field_id('show_title'); ?>">
            <input id="<?php echo $this->get_field_id('show_title'); ?>" name="<?php echo $this->get_field_name('show_title'); ?>" type="checkbox" value="on" <?php if($show_title=='on') echo " checked='checked';"; ?> />
            <?php _e('Title','wpp'); ?>
          </label>
        </p>
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
      $title       = apply_filters('widget_title', $instance['title']);
      $instance = apply_filters('FeaturedPropertiesWidget', $instance);
      $show_title   = $instance['show_title'];
      $image_type   = $instance['image_type'];
      $amount_items  = $instance['amount_items'];
      $stats       = $instance['stats'];
      $address_format = $instance['address_format'];
      $hide_image   = $instance['hide_image'];
      $amount_items  = $instance['amount_items'];
      if(empty($address_format))
          $address_format = "[street_number] [street_name], [city], [state]";

      if(!$image_type) {
          $image_type == '';
      }

      $all_featured = WPP_F::get_properties("featured=true&property_type=all&pagi=0--$amount_items");

      // Bail out if no children
      if(!$all_featured) {
      return;
      }

    $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
    echo $before_widget;
    echo "<div class='wpp_featured_properties_widget'>";


      if ( $title )
          echo $before_title . $title . $after_title;


    $count = 0;

      foreach($all_featured as $featured):



      if($amount_items == $count)
        continue;

      $count++;

            $this_property = WPP_F::get_property($featured, 'return_object=true');
            $image = wpp_get_image_link($this_property->featured_image, $image_type, array('return'=>'array'));
            ?>
            <div class="property_widget_block  clearfix"  style="<?php echo ($image['width'] ? 'width: ' . ($image['width']+5) . 'px;' : ''); ?> min-height: <?php echo $image['height']; ?>px;">

        <?php if ($hide_image !=='on'){ ?>
                <a class="sidebar_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
                    <?php if(!empty($image)){ ?>
                        <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" src="<?php echo $image['link'];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property->post_title, $this_property->location, $this_property->price); ?>" />
                    <?php } ?>
                </a>

        <?php } ?>


        <?php if ($show_title == 'on'): ?>
                <p class="title"><a href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
        <?php endif; ?>

                <ul class="wpp_widget_attribute_list">
                <?php if(is_array($stats)): ?>
                <?php foreach($stats as $stat):
                    $content = nl2br(apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format));

                    if(empty($content)) continue; ?>

                    <li class="<?php echo $stat ?>"><span class='attribute'><?php echo $wp_properties['property_stats'][$stat]; ?>:</span>  <span class='value'><?php echo $content;  ?></span></li>
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
        $title         = esc_attr($instance['title']);
        $image_type     = esc_attr($instance['image_type']);
        $amount_items     = esc_attr($instance['amount_items']);
        $property_stats   = $instance['stats'];
        $show_title      = $instance['show_title'];
        $hide_image      = $instance['hide_image'];
        $address_format   = esc_attr($instance['address_format']);
        $enable_more     = $instance['enable_more'];
        $enable_view_all   = $instance['enable_view_all'];



        if(empty($address_format))
            $address_format = "[street_number] [street_name],[city], [state]";

          ?>
<script type="text/javascript">
//hide and show dropdown whith thumb settings
jQuery(document).ready(function($){
  $('input.check_me_featured').change(function(){
    if($(this).attr('checked') !== true){
      $('p#choose_thumb_featured').css('display','block');
    }else{
      $('p#choose_thumb_featured').css('display','none');
    }
  })
});
</script>
             <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>


            <p>
                <label for="<?php echo $this->get_field_id('hide_image'); ?>">
                    <input id="<?php echo $this->get_field_id('hide_image'); ?>" class="check_me_featured" name="<?php echo $this->get_field_name('hide_image'); ?>" type="checkbox" value="on" <?php if($hide_image=='on') echo " checked='checked';"; ?> />
                    <?php _e('Hide Images?','wpp'); ?>
                </label>
            </p>
             <p id="choose_thumb_featured" <?php
             if($hide_image !== 'on')
               echo 'style="display:block;"';
             else
               echo 'style="display:none;"';
             ?>>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>

            <p>
            <label for="<?php echo $this->get_field_id('amount_items'); ?>"><?php _e('Listings to display?', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('amount_items'); ?>" name="<?php echo $this->get_field_name('amount_items'); ?>" type="text" value="<?php echo (empty($amount_items)) ? 5 : $amount_items; ?>" />
                </label>
            </p>

            <p><?php _e('Select the stats you want to display','wpp') ?></p>

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

      $title       = apply_filters('widget_title', $instance['title']);
      $instance = apply_filters('LatestPropertiesWidget', $instance);
      $stats       = $instance['stats'];
      $image_type    = $instance['image_type'];
      $hide_image   = $instance['hide_image'];
      $show_title   = $instance['show_title'];
      $address_format = $instance['address_format'];

      if(!$image_type) {
        $image_type == '';
      }

      $arg = array(
        'post_type'      => 'property',
        'numberposts'    => $instance['amount_items'],
        'post_status'    => 'publish',
        'post_parent'    => null, // any parent
        'order'        => 'DESC',
        'orderby'      => 'post_date'
      );

    $postslist = get_posts($arg);
    $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);
    echo $before_widget;
    echo "<div class='wpp_latest_properties_widget'>";

    if ( $title ) {
      echo $before_title . $title . $after_title;
    }


     foreach ($postslist as $post) {
       $this_property = WPP_F::get_property($post->ID, 'return_object=true');
       $image = wpp_get_image_link($this_property->featured_image, $image_type, array('return'=>'array'));

          ?>

       <div class="property_widget_block latest_entry clearfix" style="<?php echo ($image['width'] ? 'width: ' . ($image['width']+5) . 'px;' : ''); ?>">

       <?php if ($hide_image !=='on'){ ?>
          <a class="sidebar_property_thumbnail latest_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
            <?php if(!empty($image)){ ?>
            <img width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" src="<?php echo $image['url'];?>" alt="<?php echo sprintf(__('%s at %s for %s','wpp'), $this_property->post_title, $this_property->location, $this_property->price); ?>" />
            <?php } ?>
          </a>
        <?php
        }

      if ($show_title == 'on'){
        echo '<p class="title"><a href="'. $this_property->permalink.'">'. $post->post_title .'</a></p>';
      }

      echo '<ul class="wpp_widget_attribute_list">';
      if(is_array($stats)){
       foreach($stats as $stat){
        $content = apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat, $this_property, $address_format);
        if(empty($content)) continue;
        echo '<li class="'. $stat .'"><span class="attribute">'. $wp_properties['property_stats'][$stat] .':</span> <span class="value">'. $content .'</span></li>';
      }
      }
     echo '</ul>';

     if ($instance['enable_more'] =='on') {
      echo '<p class="more"><a href="'. $this_property->permalink.'">'.__('More','wpp').'</a></p>';
      }
      ?>
           </div>
      <?php
                unset($this_property);

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

        $title         = esc_attr($instance['title']);
        $amount_items     = esc_attr($instance['amount_items']);
        $address_format   = esc_attr($instance['address_format']);
        $property_stats   = $instance['stats'];
        $image_type     = esc_attr($instance['image_type']);
        $hide_image      = $instance['hide_image'];
        $show_title      = $instance['show_title'];
        $enable_more     = $instance['enable_more'];
        $enable_view_all   = $instance['enable_view_all'];

        if(empty($address_format)) {
          $address_format = "[street_number] [street_name],[city], [state]";
        }
?>
<script type="text/javascript">
//hide and show dropdown whith thumb settings
jQuery(document).ready(function($){
  $('input.check_me').change(function(){
    if($(this).attr('checked') !== true){
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
                <label for="<?php echo $this->get_field_id('hide_image'); ?>">
                    <input id="<?php echo $this->get_field_id('hide_image'); ?>" class="check_me" name="<?php echo $this->get_field_name('hide_image'); ?>" type="checkbox" value="on" <?php if($hide_image=='on') echo " checked='checked';"; ?> />
                    <?php _e('Hide Images?','wpp'); ?>
                </label>
            </p>
             <p id="choose_thumb" <?php
             if($hide_image !== 'on')
               echo 'style="display:block;"';
             else
               echo 'style="display:none;"';
             ?>>
                <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Image Size:','wpp'); ?>
                <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
            </label>

            <p>
            <label for="<?php echo $this->get_field_id('per_page'); ?>"><?php _e('Listings to display?', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('amount_items'); ?>" name="<?php echo $this->get_field_name('amount_items'); ?>" type="text" value="<?php echo (empty($amount_items)) ? 5 : $amount_items; ?>" />
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
                <?php endforeach; ?>

         <p>
                <label for="<?php echo $this->get_field_id('address_format'); ?>"><?php _e('Address Format:','wpp'); ?>
                <textarea  style="width: 100%"  id="<?php echo $this->get_field_id('address_format'); ?>" name="<?php echo $this->get_field_name('address_format'); ?>"><?php echo $address_format; ?></textarea>
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

  <?php  }

 }



/**
 * Property Search Widget
 */
 class SearchPropertiesWidget extends WP_Widget {
    var $id = false;

    /** constructor */
    function SearchPropertiesWidget() {
        parent::WP_Widget(false, $name = __('Property Search','wpp'), array('description' => __('Display a highly customizable property search form', 'wpp')));
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {

        global  $wp_properties;

        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);

        $instance = apply_filters('SearchPropertiesWidget', $instance);
        $search_attributes = $instance['searchable_attributes'];
        $sort_by = $instance['sort_by'];
        $sort_order = $instance['sort_order'];
        $searchable_property_types = $instance['searchable_property_types'];
        $grouped_searchable_attributes = $instance['grouped_searchable_attributes'];


        if(!is_array($search_attributes)) {
          return;
        }

        if(!function_exists('draw_property_search_form')) {
          return;
        }

        //** The current widget can be used on the page twice. So ID of the current DOM element (widget) has to be unique */
        $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);

        echo $before_widget;

        echo '<div class="wpp_search_properties_widget">';

        if ( $title ) {
          echo $before_title . $title . $after_title;
        } else  {
          echo '<span class="wpp_widget_no_title"></span>';
        }
        
        //** Load different attribute list depending on group selection */
        if($instance['group_attributes'] == 'true') {
          $search_args['group_attributes'] = true;
          $search_args['search_attributes'] = $instance['grouped_searchable_attributes'];
        } else {
          $search_args['search_attributes'] = $search_attributes;
        }
        
        //* Clean searchable attributes: remove unavailable ones */
        $all_searchable_attributes = array_unique($wp_properties['searchable_attributes']);
        foreach($search_args['search_attributes'] as $k => $v) {
          if(!in_array($v, $all_searchable_attributes)) {
            //* Don't remove hardcoded attributes (property_type,city) */
            if ($v != 'property_type' && $v != 'city') {
              unset($search_args['search_attributes'][$k]);
            }
          }
        }
        
        $search_args['searchable_property_types'] = $searchable_property_types;
        
        if(isset($instance['use_pagi']) && $instance['use_pagi']=='on') {

          if(empty($instance['per_page'])) {
            $instance['per_page'] = 10;
          }

          $search_args['per_page'] = $instance['per_page'];
          $search_args['use_pagination'] = 'on';
        } else {
          $search_args['use_pagination'] = 'off';
          $search_args['per_page'] = $instance['per_page'];
        }

        $search_args['instance_id'] = $widget_id;
        $search_args['sort_by'] = $sort_by;
        $search_args['sort_order'] = $sort_order;

        draw_property_search_form($search_args);

        echo "<div class='cboth'></div></div>";

        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        //Recache searchable values for search widget form
        $searchable_attributes = $new_instance['searchable_attributes'];
        $grouped_searchable_attributes = $new_instance['grouped_searchable_attributes'];
        $searchable_property_types = $new_instance['searchable_property_types'];
        $group_attributes = $new_instance['group_attributes'];
        
        
        if($group_attributes == 'true') {        
        
          WPP_F::get_search_values($grouped_searchable_attributes, $searchable_property_types, false, $this->id);
        } else {
          WPP_F::get_search_values($searchable_attributes, $searchable_property_types, false, $this->id);        
        }

        return $new_instance;
    }
    /**
     * 
     * Renders back-end property search widget tools.
     * 
     * @complexity 8     
     * @author potanin@UD
     * 
     */    
     function form($instance) {
      global $wp_properties;

      //** Get widget-specific data */
      $title = ($instance['title']);
      $searchable_attributes = $instance['searchable_attributes'];
      $grouped_searchable_attributes = $instance['grouped_searchable_attributes'];
      $use_pagi = $instance['use_pagi'];
      $per_page = $instance['per_page'];
      $sort_by = $instance['sort_by'];
      $sort_order = $instance['sort_order'];
      $group_attributes = $instance['group_attributes'];
      $searchable_property_types = $instance['searchable_property_types'];

      //** Get WPP data */
      $all_searchable_property_types = array_unique($wp_properties['searchable_property_types']);
      $all_searchable_attributes = array_unique($wp_properties['searchable_attributes']);
      $groups = $wp_properties['property_groups'];
      $main_stats_group = $wp_properties['configuration']['main_stats_group'];


      if(!is_array($all_searchable_property_types)) {
        $error['no_searchable_types'] = true;
      }

      if(!is_array($all_searchable_property_types)) {
        $error['no_searchable_attributes'] = true;
      }

      //** Set label for list below only */
      if(!isset($wp_properties['property_stats']['property_type'])) {
        $wp_properties['property_stats']['property_type'] = __('Property Type', 'wpp');
      }
        
       if(is_array($all_searchable_property_types) && count($all_searchable_property_types) > 1) {

        //** Add property type to the beginning of the attribute list, even though it's not a typical attribute */
        array_unshift($all_searchable_attributes, 'property_type');
       }
      
      //** Find the difference between selected attributes and all attributes, i.e. unselected attributes */
      if(is_array($searchable_attributes) && is_array($all_searchable_attributes)) {
        $unselected_attributes = array_diff($all_searchable_attributes, $searchable_attributes);
        
        //* Clean searchable attributes: remove unavailable ones */
        foreach($searchable_attributes as $k => $v) {
          if(!in_array($v, $all_searchable_attributes)) {
            //* Don't remove hardcoded attributes (property_type,city) */
            if ($v != 'property_type' && $v != 'city') {
              unset($searchable_attributes[$k]);
            }
          }
        }
        
        // Build new array beginning with selected attributes, in order, follow by all other attributes
        $ungrouped_searchable_attributes = array_merge($searchable_attributes, $unselected_attributes);
      
      } else {
        $ungrouped_searchable_attributes = $all_searchable_attributes;
      }
      //$ungrouped_searchable_attributes = $all_searchable_attributes;
      
      //* Perpare $all_searchable_attributes for using by sort function */
      $temp_attrs = array();
      
      foreach($all_searchable_attributes as $slug) {
        $attribute_label = $wp_properties['property_stats'][$slug];
        
        if(empty($attribute_label)) {
          $attribute_label = UD_F::de_slug($slug);
        }
        
        $temp_attrs[$attribute_label] = $slug;
      }

      //* Sort stats by groups */
      $stats_by_groups = sort_stats_by_groups($temp_attrs);

      //** If the search widget cannot be created without some data, we bail */
      if($error) {
        echo '<p>' . _e('No searchable property types were found.','wpp') . '</p>';
        return;
      }
      
      /*
        echo "<pre>";
        //print_r($searchable_attributes);
        //print_r($stats_by_groups);        
        echo "</pre>";
      */
        
      ?>
     <script type="text/javascript">

      jQuery(document).ready(function(){      
      
        var this_search_box = jQuery("#wpp_property_search_wrapper_<?php echo $this->number; ?>");
        
        /* Run on load to hide property type attribut if there is less than 2 property types */
        wpp_adjust_property_type_option();
        
        /* Select the correc tab */
        wpp_set_group_or_ungroup();
      
        jQuery("#all_atributes_<?php echo $this->id; ?> .wpp_sortable_attributes").sortable(); 

        /* Setup tab the grouping/ungrouping tabs, and trigger checking the select box when tabs are switched */
        jQuery(".wpp_subtle_tabs").tabs({
          select: function(event, ui) {            
            if(ui.index == 0) {
              jQuery("#<?php echo $this->get_field_id('group_attributes'); ?>").attr("checked", false);
            } else {
              jQuery("#<?php echo $this->get_field_id('group_attributes'); ?>").attr("checked", true);
            }            
          }
        });

        /* Not sure if this is important */
        if(typeof wpp_search_widget_dragstop == "undefined") {
          jQuery('#widget-list').bind('dragstop', function(e){
            jQuery('.wpp_search_widget_tab').tabs();
          });
          wpp_search_widget_dragstop = true;
        }
        
        /* Select grouped tab if grouping is enabled here */
        <?php if($stats_by_groups && $group_attributes == 'true') { ?>
        jQuery(".wpp_subtle_tabs").tabs('select',1);
        <?php } ?>
        
        jQuery("#<?php echo $this->get_field_id('group_attributes'); ?>").change(function() {

        
        });
        
        jQuery(".wpp_prperty_types_<?php echo $this->number;?>").change(function() {
          wpp_adjust_property_type_option();
        });
        
        function wpp_set_group_or_ungroup() {
                
          if(jQuery("#<?php echo $this->get_field_id('group_attributes'); ?>").is(":checked")) {
            jQuery(".wpp_subtle_tabs",this_search_box).tabs('select',1);          
          } else {
            jQuery(".wpp_subtle_tabs",this_search_box).tabs('select',0);                    
          }
        
        }
        
        function wpp_adjust_property_type_option() {
        
          var count = jQuery(".wpp_prperty_types_<?php echo $this->number;?>:checked").length;
 
          if(count < 2) {
            jQuery(".wpp_attribute_wrapper.property_type", this_search_box).hide();
            jQuery(".wpp_attribute_wrapper.property_type input", this_search_box).attr("checked", false);
          } else {
            jQuery(".wpp_attribute_wrapper.property_type", this_search_box).show();          
          }
          
        }

        
      });

    </script>

    <ul id="wpp_property_search_wrapper_<?php echo $this->number; ?>" class="wpp_property_search_wrapper">

      <li class="<?php echo $this->get_field_id('title'); ?>">
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','wpp'); ?>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </label>
      </li>

      <li class="wpp_property_types">
        <p><?php _e('Property types to search:','wpp'); ?></p>
        <ul>
        <?php foreach($all_searchable_property_types as $property_type) { ?>
          <li>
            <label for="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>">
            <input class="wpp_prperty_types_<?php echo $this->number;?>" id="<?php echo $this->get_field_id('searchable_property_types'); ?>_<?php echo $property_type; ?>" name="<?php echo $this->get_field_name('searchable_property_types'); ?>[]" type="checkbox" <?php if (empty($searchable_property_types)) { echo  'checked="checked"'; } ?> value="<?php echo $property_type; ?>" <?php if(is_array($searchable_property_types) && in_array($property_type, $searchable_property_types)) { echo " checked "; } ?> />
            <?php echo (!empty($wp_properties['property_types'][$property_type]) ? $wp_properties['property_types'][$property_type] : ucwords($property_type))  ;?>
          </label>
          </li>
        <?php }  ?>
        </ul>
      </li>

      <li class="wpp_attribute_selection">
        <p><?php _e('Select the attributes you want to search.','wpp'); ?></p>
        <div class="wpp_search_widget_tab wpp_subtle_tabs ">

        <ul class="wpp_section_tabs  tabs">
          <li><a href="#all_atributes_<?php echo $this->id; ?>"><?php _e('All Attributes','wpp'); ?></a></li>
          
          <?php if($stats_by_groups) { ?>
          <li><a href="#grouped_attributes_<?php echo $this->id; ?>"><?php _e('Grouped Attributes','wpp'); ?></a></li>
          <?php } ?>
        </ul>

        <div id="all_atributes_<?php echo $this->id; ?>" class="wp-tab-panel wpp_all_attributes">
          <ul class="wpp_sortable_attributes">
          <?php foreach($ungrouped_searchable_attributes as $attribute) { ?>
          
            <li class="wpp_attribute_wrapper <?php echo $attribute; ?>">
              <input id="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>" name="<?php echo $this->get_field_name('searchable_attributes'); ?>[]" type="checkbox" <?php if (empty($searchable_attributes)) { echo  'checked="checked"'; } ?> value="<?php echo $attribute; ?>" <?php echo ((is_array($searchable_attributes) && in_array($attribute, $searchable_attributes)) ? " checked " : ""); ?> />
              <label for="<?php echo $this->get_field_id('searchable_attributes'); ?>_<?php echo $attribute; ?>"><?php echo (!empty($wp_properties['property_stats'][$attribute]) ? $wp_properties['property_stats'][$attribute] : ucwords($attribute))  ;?></label>
            </li>
          <?php } ?>
          </ul>
        </div><?php /* end all (ungrouped) attribute selection */ ?>

        <?php if($stats_by_groups) { ?>
        <div id="grouped_attributes_<?php echo $this->id; ?>" class="wpp_grouped_attributes_container wp-tab-panel">

          <?php foreach($stats_by_groups as $gslug => $gstats) { ?>
            <?php if($main_stats_group != $gslug || !key_exists($gslug, $groups)) { ?>
              <?php $group_name = ( key_exists($gslug, $groups) ? $groups[$gslug]['name'] : "<span style=\"color:#8C8989\">" . __('Ungrouped','wpp') . "</span>" ); ?>
              <h2 class="wpp_stats_group"><?php echo $group_name; ?></h2>
            <?php } ?>
            <ul>
            <?php foreach ($gstats as $attribute) { ?>
            <li>
                <input id="<?php echo $this->get_field_id('grouped_searchable_attributes'); ?>_<?php echo $attribute; ?>" name="<?php echo $this->get_field_name('grouped_searchable_attributes'); ?>[]" type="checkbox" <?php if (empty($grouped_searchable_attributes)) { echo  'checked="checked"'; } ?> value="<?php echo $attribute; ?>" <?php echo ((is_array($grouped_searchable_attributes) && in_array($attribute, $grouped_searchable_attributes)) ? " checked " : ""); ?> />
                <label for="<?php echo $this->get_field_id('grouped_searchable_attributes'); ?>_<?php echo $attribute; ?>"><?php echo (!empty($wp_properties['property_stats'][$attribute]) ? $wp_properties['property_stats'][$attribute] : ucwords($attribute))  ;?></label>
            </li>
            <?php } ?>
            </ul>
          <?php } /* End cycle through $stats_by_groups */ ?>
        </div>
        <?php } ?>

        </div>

        </li>

        <li>

        <?php if($stats_by_groups) { ?>
        <div>
          <input  id="<?php echo $this->get_field_id('group_attributes'); ?>"  class="wpp_toggle_attribute_grouping" type="checkbox" value="true" name="<?php echo $this->get_field_name('group_attributes'); ?>" <?php checked($group_attributes, 'true'); ?> />
          <label for="<?php echo $this->get_field_id('group_attributes'); ?>"><?php _e('Group attributes together.'); ?></label>
        </div>
        </li>
        <?php } ?>

        <li>

        <div class="wpp_something_advanced_wrapper" style="margin-top: 10px;">
          <ul>

          <?php if(is_array($wp_properties['sortable_attributes'])) { ?>
            <li class="wpp_development_advanced_option">
              <div><label for="<?php echo $this->get_field_id('sort_by'); ?>"><?php _e('Default Sort Order','wpp'); ?></label></div>
              <select id="<?php echo $this->get_field_id('sort_by'); ?>" name="<?php echo $this->get_field_name('sort_by'); ?>">
                <option></option>
                <?php foreach($wp_properties['sortable_attributes'] as $attribute) { ?>
                  <option value="<?php echo esc_attr($attribute); ?>"  <?php selected($sort_by, $attribute); ?> ><?php echo $wp_properties['property_stats'][$attribute]; ?></option>
                <?php } ?>
              </select>

              <select id="<?php echo $this->get_field_id('sort_order'); ?>" name="<?php echo $this->get_field_name('sort_order'); ?>">
                <option></option>
                <option value="DESC"  <?php selected($sort_order, 'DESC'); ?> ><?php _e('Descending'); ?></option>
                <option value="ASC"  <?php selected($sort_order, 'ASC'); ?> ><?php _e('Acending'); ?></option>
              </select>

            </li>
          <?php } ?>
              <li class="wpp_development_advanced_option">
                <label for="<?php echo $this->get_field_id('use_pagi'); ?>">
                    <input id="<?php echo $this->get_field_id('use_pagi'); ?>" name="<?php echo $this->get_field_name('use_pagi'); ?>" type="checkbox" value="on" <?php if($use_pagi=='on') echo " checked='checked';"; ?> />
                    <?php _e('Use pagination','wpp'); ?>
                </label>
            </li>

            <li class="wpp_development_advanced_option">
                <label for="<?php echo $this->get_field_id('per_page'); ?>"><?php _e('Items per page', 'wpp'); ?>
                    <input style="width:30px" id="<?php echo $this->get_field_id('per_page'); ?>" name="<?php echo $this->get_field_name('per_page'); ?>" type="text" value="<?php echo $per_page; ?>" />
                </label>
            </li>

            <li>
              <span class="wpp_show_advanced"><?php _e('Toggle Advanced Search Options', 'wpp'); ?></span>
            </li>
          </ul>
        </div>
        </li>
      </ul>


    <?php

    }

}

// Default function to use in template directly
function wpp_search_widget($args = false, $custom = false){
  global $wp_properties;



    if (!$args)
        $args = array(
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
            'before_widget' => '',
            'after_widget'  => ''
        );

   // $searchable_attributes = array('bedrooms','bathrooms','area','city', 'price');
   $searchable_attributes = $custom;
   $searchable_property_types = $wp_properties['searchable_property_types'];


    $default = array(
        'title'                     => __('Search Properies','wpp'),
        'use_pagi'                  => 'on',
        'per_page'                  => 10,
        'searchable_attributes'     => $searchable_attributes,
        'searchable_property_types' => $searchable_property_types
    );
    if($custom)
        $default = array_merge($default, $custom);


    $count = strlen(implode('-', $default['searchable_attributes'])) . strlen(implode('-', $default['searchable_property_types']));

    WPP_F::get_search_values($searchable_attributes, $searchable_property_types, false, 'searchpropertieswidget-'.$count);

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
      $show_caption = esc_attr($instance['show_caption']);
      $show_description = esc_attr($instance['show_description']);

      if(empty($big_image_type)) {
        $big_image_type = 'large';
      }

      if(empty($image_type)) {
        $image_type = 'thumbnail';
      }

      if(empty($post->gallery)) {
        return;
      }

      $thumbnail_dimensions = WPP_F::image_sizes($image_type);

      $before_widget = preg_replace('/id="([^\s]*)"/', 'id="$1_'.rand().'"', $before_widget);

      $html[] = $before_widget;
      $html[] = "<div class='wpp_gallery_widget'>";

      if ( $title ) {
        $html[] = $before_title . $title . $after_title;
      }

      ob_start();

      if($post->gallery) {

        $real_count = 0;

        foreach($post->gallery as $image) {

          $big_image = wpp_get_image_link($image['attachment_id'], $big_image_type);
          $thumb_image = wpp_get_image_link($image['attachment_id'], $image_type);

          ?>
          <div class="sidebar_gallery_item">
            <a href="<?php echo $big_image; ?>" class="fancybox_image" rel="property_gallery">
                <img src="<?php echo $thumb_image; ?>" title="<?php echo esc_attr($image['post_excerpt'] ? $image['post_excerpt'] : $image['post_title'] . ' - ' . $post->post_title); ?>" alt="<?php echo esc_attr($image['post_excerpt'] ? $image['post_excerpt'] : $image['post_title']); ?>" class="wpp_gallery_widget_image size-thumbnail "  width="<?php echo $thumbnail_dimensions['width']; ?>" height="<?php echo $thumbnail_dimensions['height']; ?>" />
            </a>
            <?php if($show_caption == 'on' && !empty($image['post_excerpt'])) { ?>
              <div class="wpp_image_widget_caption"><?php echo $image['post_excerpt']; ?></div>
            <?php } ?>

            <?php if($show_description == 'on') { ?>
              <div class="wpp_image_widget_description"><?php echo $image['post_content']; ?></div>
            <?php } ?>

          </div>
          <?php
          $real_count++;

          if(!empty($gallery_count) && $gallery_count == $real_count) {
            break;
          }

        }
      }

      $html['images'] = ob_get_contents();
      ob_end_clean();

      $html[] = "</div>";
      $html[] = $after_widget;

      $html = apply_filters('wpp_widget_property_gallery', $html, array('args' => $args, 'instance' => $instance, 'post' => $post));

      if(is_array($html)) {
        echo implode('', $html);
      }

      return;

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
        $show_caption = $instance['show_caption'];
        $show_description = $instance['show_description'];
        $gallery_count = $instance['gallery_count']; ?>

       <p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('image_type'); ?>"><?php _e('Regular Size:'); ?></label>
        <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('image_type') . "&selected=" . $image_type); ?>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('big_image_type'); ?>"><?php _e('Large Image Size:'); ?></label>
        <?php WPP_F::image_sizes_dropdown("name=" . $this->get_field_name('big_image_type') . "&selected=" . $big_image_type); ?>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('gallery_count') ?>"></label>
        <?php $number_of_images = '<input size="3" type="text" id="'. $this->get_field_id('gallery_count') .'" name="'. $this->get_field_name('gallery_count').'" value="'. $gallery_count.'" />'; ?>
        <?php echo sprintf(__('Show %s Images','wpp'), $number_of_images); ?>
      </p>

      <p>
        <input name="<?php echo $this->get_field_name('show_caption'); ?>"  id="<?php echo $this->get_field_id('show_caption') ?>" type="checkbox" <?php checked('on', $show_caption); ?> value="on" />
        <label for="<?php echo $this->get_field_id('show_caption') ?>"><?php _e('Show Image Captions', 'wpp'); ?></label>
      </p>

      <p>
        <input name="<?php echo $this->get_field_name('show_description'); ?>"  id="<?php echo $this->get_field_id('show_description') ?>" type="checkbox" <?php checked('on', $show_description); ?> value="on" />
        <label for="<?php echo $this->get_field_id('show_description') ?>"><?php _e('Show Image Descriptions.', 'wpp'); ?></label>
      </p>

      <?php do_action('wpp_widget_slideshow_bottom',array('this_object' => $this, 'instance' => $instance)); ?>


   <?php

    }
}
