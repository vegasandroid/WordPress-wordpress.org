<?php
namespace WordPressdotorg\Make\Breathe;

function styles() {
	wp_dequeue_style('breathe-style');
	wp_enqueue_style( 'p2-breathe', get_template_directory_uri() . '/style.css' );

	// Cacheing hack
	wp_enqueue_style( 'wporg-breathe', get_stylesheet_uri(), array( 'p2-breathe' ), '20160626d' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\styles', 11 );
