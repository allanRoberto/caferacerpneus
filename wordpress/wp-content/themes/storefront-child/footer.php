<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */
$template_url = get_template_directory_uri();
?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="col-full">

			<?php
			/**
			 * Functions hooked in to storefront_footer action
			 *
			 * @hooked storefront_footer_widgets - 10
			 * @hooked storefront_credit         - 20
			 */
			do_action( 'storefront_footer' ); ?>
				<div class="row-fluid">
					<div class="col-md-12 row-centered" style="padding: 0px !important;">
						<h5 class="title-payments">Formas de pagamento :</h5>	
						<ul class="payments row-centered">
							<li><img src="<?php echo $template_url;?>-child/img/logo-cielo.png" alt="Cielo" /></li>
							<li><img src="<?php echo $template_url;?>-child/img/logo-master.png" alt="Pagamento MasterCard" /></li>
							<li><img src="<?php echo $template_url;?>-child/img/logo-visa.png" alt="Pagamento Visa" /></li>
							<li><img src="<?php echo $template_url;?>-child/img/logo-santander-boletos.png" alt="Pagamento Santander Boletos" /></li>
						</ul>
						<span class="logo-ssl"><img src="<?php echo $template_url;?>-child/img/logo-SSL.png" alt="Ambiente Seguro SSL" /></span>
					</div>
				</div>
			
	 		<div class="row">
				<div class="col-md-12">
					<p class="text-center address-footer"><a href="https://www.google.com.br/maps/dir/''/cafe+racer+pneus/data=!4m5!4m4!1m0!1m2!1m1!1s0x94ce433e337fa999:0x8ee72aeb2c6d4467?sa=X&ved=0ahUKEwjynevEs9zNAhVJgJAKHbQ6CoIQ9RcIuAEwFQ" title="Ir para o Google Maps">Endereço : R. Déa Fongaro, 23 - Rudge Ramos, São Bernardo do Campo - SP, 09623-060</a><br>
					Café Racer Pneus | CNPJ 17.965.226/0001-20</p>
				</div>
			</div>
			<?php 
               $footerNav = wp_nav_menu( array( 'theme_location' => 'footer-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => 'footer-primary', 'menu_id' => 'footer-menu', 'echo' => false ) );

               echo $footerNav;
	 		?>
		</div><!-- .col-full -->
	</footer><!-- #colophon -->

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
