 <?php if($properties): ?>

<style type="text/css">
	.property_image .property_overview_thumb_<?php echo $thumbnail_size; ?> {
		width: <?php echo $thumbnail_sizes[width]; ?>px;
		height: <?php echo $thumbnail_sizes[height]; ?>px;
	}
	.property_div  {
		min-height: <?php echo $thumbnail_sizes[height] + 20;  ?>px;
	}
	
	.wpp_row_view .top_row {
		/* width: <?php echo  860 -  $thumbnail_sizes[width]; ?>px; */
		margin-left: <?php echo  $thumbnail_sizes[width] + 25; ?>px;
	
	}
	.child_properties {
		margin-left: <?php echo $thumbnail_sizes[width] + 40; ?>px;
	}
</style>
 
	
<div class="wpp_row_view">

<?php

 
	foreach($properties as $p):
		$property = get_property($p, "get_children=$show_children");
 
?>

<div class="property_div <?php echo $property[post_type]; ?> clearfix">
 	<div class="property_image">
		<?php
			if($wp_properties[configuration][property_overview][fancybox_preview] == 'true') {
				$thumbnail_link = $property[featured_image_url];
				$link_class = 'fancybox_image';
			} else {
				$thumbnail_link = $property[permalink];			
			}
		?>
		<a href="<?php echo $thumbnail_link; ?>" title="<?php echo $property[post_title] . ($property[parent_title] ? " of " . $property[parent_title] : "");?>"  class="property_overview_thumb property_overview_thumb_<?php echo $thumbnail_size; ?> <?php echo $link_class; ?>" rel="properties" >
			<img src="<?php echo $property[images][$thumbnail_size]; ; ?>" alt="<?php echo $property[post_title];?>" />
		</a>
 	</div>

	<div class="top_row">
		<div class="property_tagline">
            <span class="property_title"><a href="<?php echo $property[permalink]; ?>"><?php echo $property[post_title]; ?></a>
			
			<?php if($property[is_child]): ?>
			<?php echo "of <a href='{$property[parent_link]}'>{$property[parent_title]}</a>"; ?>
			<?php endif; ?>
			
			</span>
            <span class="tagline"><?php echo (!empty($property[custom_attribute_overview]) ? $property[custom_attribute_overview] : $property[tagline]); ?> </span>
         </div>
		
		<?php if(!empty($property[phone_number])): ?>
		<span class="property_phone_number"><?php echo $property[phone_number]; ?></span>
		<?php endif; ?>
		<?php if(!empty($property[display_address])): ?>
		<span class="property_address"><a href="<?php echo $property[permalink]; ?>?show=location"><?php echo $property[display_address]; ?></a></span>
		<?php endif; ?>
		
	</div>
	
	<?php if($property[children]): ?>
	<div class="child_properties">
 		<div class="wpd_floorplans_title">		
		Floor plans at location: 
		</div>
		<table>
			<?php foreach($property[children] as $child): ?>
			<tr class="property_child_row">
				<th class="property_child_title"><a href="<?php echo $child[permalink]; ?>"><?php echo $child[post_title]; ?></a></th>
				<td class="property_child_price"><?php echo apply_filters('wpp_stat_filter_price', $child[price]); ?></td>
 			</tr>
			<?php endforeach; ?>
		</table>	
		
	</div>
	<?php endif; ?>
 
	
	<div class="stats">
	<?php // draw_stats("property={$property[ID]}&include=price,deposit,bedrooms,bathrooms,deposit,area,location"); ?>
	</div>
	
	
</div>

<?php endforeach; ?>
<?php else: ?>
<div class="wpp_nothing_found">
Sorry, no properties found - try expanding your search, or <a href="">view all</a>.
</div>
<?php endif; ?>

</div>