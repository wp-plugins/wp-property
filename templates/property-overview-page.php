<?php
/**
 * The default page for property overview page.
 *
 * Used when no WordPress page is setup to display overview via shortcode.
 *
 * @package WP-Property
 */
 
get_header(); ?>
 
		<div id="container">
			<div id="content" role="main">
 

				<div id="wpp_default_overview_page" >
					
					<h1 class="entry-title">Properties</h1>


					<div class="entry-content">

					<?php			
					if(file_exists(TEMPLATEPATH . "/property-overview.php")) {
						include TEMPLATEPATH . "/property-overview.php";
						
					}
						
					// 4. If all else fails, try the default general template
					if(file_exists(WPP_Templates . "/property-overview.php")) {
						include WPP_Templates . "/property-overview.php";
					}

					?>	
							
					</div><!-- .entry-content -->
				</div><!-- #post-## -->
 

			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
