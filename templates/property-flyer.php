<?php
/**
 * PDF Flyer default template
 *
 *
 */
 
 ?>
<html>
<head>
<style type="text/css">
  dl.stats dt {
     padding:0;
    margin:0;

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
  
  ul.no_list,   ul.side_gallery {
    list-style-type:none;
  }
 

  body {

    width: 800px;
  }
  
  .heading_text {
    font-size: 1.7em; 
    padding-bottom: 15px;
    border-bottom:0.5px solid #DADADA;
    
  }
  
  h1 {
    font-size: 2.5em;
  }
 
 .right_column {
  text-align: left;
 }
 .google_map{
  border: 3px solid #EDEDED;

 }
 </style>
 </head> 
 <body>

 <table cellspacing="0" cellpadding="5" style="font-size: 20px;" border="0">  
  
  <?php if( !empty( $wpp_pdf_flyer['logo_url'] ) ) { ?>
  <tr>
    <td colspan="2" valign="top">
        <br />
        <br />
        <br />
        <img class='header_logo_image' src="<?php echo $wpp_pdf_flyer['logo_url']; ?>" />
    </td>
  </tr>
  <?php } ?>
 
   <tr>
    <td colspan="2" valign="top">
    <div style="font-size: 2.3em; text-align: left; padding: 0px;"><?php echo $property['post_title'];?></div>
    </td>
  </tr>
  
  <tr>
    <td valign="top" align="center" width="66%">

    <?php if( !empty( $wpp_pdf_flyer['featured_image_url']) ) { ?>
    <img src="<?php echo $wpp_pdf_flyer['featured_image_url']; ?>" width="<?php echo $wpp_pdf_flyer['featured_image_width']; ?>" valign="middle" />
    <?php } ?>
    </td>
    
    <td valign="top"  rowspan="3" width="33%" align="right">
        <?php if(is_array($property['gallery'])): ?>

        <?php $counter = 0; 
        if(!empty($property['gallery'])) 
          foreach($property['gallery'] as $image):
   
            if($counter == $wpp_pdf_flyer['num_pictures']) break;  
            
            if(empty($image[$wpp_pdf_flyer['secondary_photos']])) continue; 
            
            $counter++; 
            ?>
        
          <img width="160" src="<?php echo $image[$wpp_pdf_flyer['secondary_photos']]; ?>" /><br /><br />
        
        <?php endforeach; ?>    

      <?php endif; ?>
      <div class="right_column">
      <?php do_action( 'wpp_flyer_right_column', $property, $wpp_pdf_flyer ); ?>
      </div>
    </td>
  </tr>
 
   
  <tr>
    <td valign="top" width="33%">
    
      <div class="left_column">
      <?php do_action( 'wpp_flyer_left_column', $property, $wpp_pdf_flyer ); ?>
      </div>
      
    </td>
    
    <td valign="top"  width="33%" >
    
      <div class="middle_column">
      <?php do_action( 'wpp_flyer_middle_column', $property, $wpp_pdf_flyer ); ?>
      </div>
      
    </td>
  </tr>
</table>

</body>
</html>