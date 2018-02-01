// Mobile Subnav open/close
jQuery(document).ready(function() {

	var tocContainer = jQuery( 'div[class*="-table-of-contents-container"]').first();

	if ( 0 === tocContainer.length ) {
		return;
	}

	// Add our expandable button
	tocContainer.find( '> ul > .menu-item-has-children > a' )
		.wrap( '<div class="expandable"></div>' )
		.after( '<button class="dashicons dashicons-arrow-down-alt2" aria-expanded="false"></button>' );

	// Invisibly open all of the submenus
	jQuery( '.menu-item-has-children > ul ul' ).addClass( 'default-open' );

	// Open the current menu
	tocContainer.find( '.current-menu-item a' ).first()
		.addClass( 'active' )
		.parents( '.menu-item-has-children' )
			.toggleClass( 'open' )
		.find( '> div > .dashicons' )
			.attr( 'aria-expanded', true );

	// Or if wrapped in a div.expandable
	jQuery( '.menu-item-has-children > div > .dashicons' ).click( function() {
		var menuToggle = jQuery( this ).closest( '.menu-item-has-children' );

		jQuery( this ).parent().siblings( '.sub-menu' ).slideToggle();

		menuToggle.toggleClass( 'open' );
		jQuery( this ).attr( 'aria-expanded', menuToggle.hasClass( 'open' ) );
	} );
} );

