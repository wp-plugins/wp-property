<?php
/**
 * PDF Flyer default template
 *
 */

 ?>
<html>
<head>
<style type="text/css">
	dl.stats dt {
 		padding:0;
		margin:0;
		color: blue;
	}
		
	dl.stats dd {
  
	}
	
	hr, p {
		margin:0;
	 }
	
	ul.no_list, ul.no_list li {
		margin:0;
		padding:0;
	}
	
	ul.no_list,	 ul.side_gallery {
		list-style-type:none;
	}
	
	body {
		width: 800px;
	}
	
	h3 {
		margin: 0; padding: 0;
	}
 </style>
 </head> 
 <body>

 <table cellspacing="0" cellpadding="5" style="font-size: 20px;" border="0">	
	
	<?php if( !empty( $wpp_pdf_flyer['logo_url'] ) ) { ?>
	<tr>
		<td valign="top"><?php echo $wpp_pdf_flyer['logo_url']; ?></td>
	</tr>
	<?php } ?>
 
	<?php if( !empty( $wpp_pdf_flyer['featured_image_url']) ) { ?>
	 <tr>
		<td valign="top" align="center">
		<h1 style="margin-top: 4px; margin-bottom: 1px; margin-left: 10px; text-align: left; padding: 0px;"><?php echo $property['post_title'];?></h1>
		<img src="<?php echo $wpp_pdf_flyer['featured_image_url']; ?>" width="<?php echo $wpp_pdf_flyer['featured_image_width']; ?>" height="<?php echo $wpp_pdf_flyer['featured_image_height']; ?>" valign="middle" />
		</td>
	</tr>
	<?php } ?>
	 
	<tr>
		<td valign="top" width="33%">
		
			<?php do_action( 'wpp_flyer_left_column', $property ); ?>
			
		</td>
		
		<td valign="top"  width="33%" >
		
			<?php do_action( 'wpp_flyer_middle_column', $property ); ?>
			
		</td>
		
		<td valign="top"  width="33%">
			<h3>Photos</h3><hr/>&nbsp;<br>
			<?php if(is_array($property['gallery'])): ?>

				<?php $counter = 0; foreach($property['gallery'] as $image): $counter++; if($counter == 3) break; ?>
				
					<img width="160" height="100" src="<?php echo $image['slideshow']; ?>" /><br /><br />
				
				<?php endforeach; ?>		

			<?php endif; ?>
		</td>
	</tr>
</table>

</body>
</html>