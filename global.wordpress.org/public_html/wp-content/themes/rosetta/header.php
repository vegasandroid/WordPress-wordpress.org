<?php

$stylesheet = get_stylesheet_uri();
if ( is_rtl() ) {
	$stylesheet = str_replace( '.css', '-rtl.css', $stylesheet );
}

wp_enqueue_style( 'rosetta', $stylesheet, array(), 25 );

if ( is_locale_css() ) {
	wp_enqueue_style( 'rosetta-locale', get_locale_css_url(), array(), 1 );
}

if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
	wp_enqueue_script( 'comment-reply' );
}

echo do_blocks( '<!-- wp:wporg/global-header /-->' );
