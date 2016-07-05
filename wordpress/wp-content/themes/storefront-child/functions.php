<?php

function artezzo_scripts() {

	$template_url = get_template_directory_uri();

	wp_enqueue_style( 'bootstrap-css', $template_url.'-child/css/bootstrap.css', array(), '1.0');
	wp_enqueue_script( 'bootstrap-js', $template_url.'-child/js/bootstrap.min.js', array('jquery'), '1.0', true );

	wp_enqueue_style( 'montserrat-font','https://fonts.googleapis.com/css?family=Montserrat:400,700', array(), '1.0');
}

add_action( 'wp_enqueue_scripts', 'artezzo_scripts');

register_nav_menus( array(
		'primary-left-menu'  => 'Menu principal esquerdo',
		'primary-right-menu' => 'Menu principal direito',
		'secondary-menu'     => 'Menu secundário',
		'footer-menu'        => 'Menu do rodapé',
	) );

remove_action( 'storefront_page', 'storefront_page_content', 20 );