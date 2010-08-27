<?php
/**
 * The default page for property overview page.
 *
 * Used when no WordPress page is setup to display overview via shortcode.
 * Will be rendered as a 404 not-found, but still can display properties.
 *
 * @package WP-Property
 */
 
get_header(); ?>
 
		<div id="container">
			<div id="content" role="main">
 

				<div id="wpp_default_overview_page" >
					
					<h1 class="entry-title">Properties</h1>


					<div class="entry-content">
	
					<?php if(is_404()): ?>
					<p>Sorry, we could not find what you were looking for.  Since you are here, take a look at some of our properties.</p>
					<?php endif; ?>
					
					<?php echo WPP_Core::shortcode_property_overview(); ?>	
							
					</div><!-- .entry-content -->
				</div><!-- #post-## -->
 

			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
