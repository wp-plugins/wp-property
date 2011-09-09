<ul class="wpp_featured_properties_shortcode clearfix">
<?php
foreach($properties as $featured):

	$this_property = WPP_F::get_property($featured, 'return_object=true&get_children=false&load_gallery=false&load_parent=false');
 	$image_sizes = WPP_F::image_sizes($image_type);
	?>


	<li class="<?php echo $class; ?> wp-caption clearfix " >

		<a class="featured_property_thumbnail"  href="<?php echo $this_property->permalink; ?>">
			<?php if(!empty($this_property->images[$image_type])): ?>
			<img width="<?php echo $image_sizes[width]; ?>" height="<?php echo $image_sizes[height]; ?>" src="<?php echo $this_property->images[$image_type];?>" title="<?php echo sprintf(__('%s at %s for %s'), $this_property->post_title, $this_property->location, $this_property->price)  ?>" />
			<?php endif; ?>
		</a>

		<ul class="wp-caption-text shortcode_featured_properties">
			<?php
			if($inside_test):
			echo $inside_test;
			endif;
			?>

		<?php if(is_array($stats)): ?>
		<?php foreach($stats as $stat):
			$stat = trim($stat);

			$content =  apply_filters('wpp_stat_filter_' . $stat, $this_property->$stat);

			if(empty($content))
				continue;
		?>
			<li class="<?php echo $stat; ?>">
				<dl>
					<dt><?php echo (empty($wp_properties['property_stats'][$stat]) ? ucwords($stat) : $wp_properties['property_stats'][$stat]); ?>:</dt>
					<dd><?php echo  $content;  ?></dd>
				</dl>
			</li>
		<?php endforeach; ?>
		<?php endif; ?>
		</ul>
   </li>
 	<?php unset($this_property); ?>
<?php endforeach; ?>
</ul>