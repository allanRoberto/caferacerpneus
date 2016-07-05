<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package storefront
 */

$template_url = get_template_directory_uri();

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.6&appId=600741946619736";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<div id="page" class="hfeed site">
	<?php
	do_action( 'storefront_before_header' ); ?>

	<header id="masthead" class="site-header" role="banner">
	   <div class="top-header">
		   	<div class="container">
		   		<div class="row">
		   			<div class="col-md-12">
					   	<span class="info-header info-call">
					   		<p>Atendimento : Seg. à Sex. 9h às 18h | Aberto aos sábados : 9h às 16h</p>
					   	</span>
					   	<span class="info-header info-tel">
					   	  <a href="tel:+551143966666">Telefone : (11) 4396-6666</a>
						</span>
						<span class="info-header info-mail">
							<a href="mailto:atendimento@caferacerpneus.com.br">atendimento@caferacerpneus.com.br</a>
						</span>
					</div>
				</div>
			</div>
	   </div>
		<div class="container">
			<div class="row row-centered">
 				<div class="col-md-4">
	 				<?php 
	                   $primaryLeftNav = wp_nav_menu( array( 'theme_location' => 'primary-left-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => 'menu-primary-left menu-primary', 'menu_id' => 'left-menu', 'echo' => false ) );

	                   echo $primaryLeftNav;
	 				?>
 				</div>
 				<div class="col-md-4">
 					<a href="<?php echo site_url();?>" title="Café Racer Pneus">
						<img src="<?php echo $template_url.'-child/img/logo-header.png';?>" alt="Café Racer Pneus" class="align-center"/>
 					</a>
 				</div>
 				<div class="col-md-4">
 					<?php 
                   $primaryRightNav = wp_nav_menu( array( 'theme_location' => 'primary-right-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => 'menu-primary-right menu-primary', 'menu_id' => 'right-menu', 'echo' => false ) );

                   echo $primaryRightNav;
 				 ?>
 				</div>
			</div>

			<?php
			/**
			 * Functions hooked into storefront_header action
			 *
			 * @hooked storefront_skip_links                       - 0
			 * @hooked storefront_social_icons                     - 10
			 * @hooked storefront_site_branding                    - 20
			 * @hooked storefront_secondary_navigation             - 30
			 * @hooked storefront_product_search                   - 40
			 * @hooked storefront_primary_navigation_wrapper       - 42
			 * @hooked storefront_primary_navigation               - 50
			 * @hooked storefront_header_cart                      - 60
			 * @hooked storefront_primary_navigation_wrapper_close - 68
			 */
			//do_action( 'storefront_header' ); ?>

		</div>
	</header><!-- #masthead -->
		
	<?php
	if (!is_home() or is_front_page()) : 
	/**
	 * Functions hooked in to storefront_before_content
	 *
	 * @hooked storefront_header_widget_region - 10
	 */
	do_action( 'storefront_before_content' ); ?>

	<div id="content" class="site-content" tabindex="-1">
		<div class="container">

		<?php
		/**
		 * Functions hooked in to storefront_content_top
		 *
		 * @hooked woocommerce_breadcrumb - 10
		 */
		do_action( 'storefront_content_top' );
	endif;	
