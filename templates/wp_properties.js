jQuery(document).ready(function() {

	jQuery("a.fancybox_image").fancybox({
		'transitionIn'	:	'elastic',
		'transitionOut'	:	'elastic',
    'speedIn'    :  600,
    'speedOut'    :  200,
    'overlayShow'  :  false
  });


  /* Scroll to top of pagination */
    jQuery(document).bind('wpp_pagination_change', function(e, data) {
    var overview_id = data.overview_id;

    jQuery.scrollTo(jQuery("#wpp_shortcode_" + overview_id), 1000);
    

  });


});