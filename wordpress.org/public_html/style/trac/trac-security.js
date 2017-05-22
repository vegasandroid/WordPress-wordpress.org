/* global wp */
window.wp = window.wp || {};

(function($) {
	var propertyform = $( '#propertyform' ),
		submit = propertyform.find( 'input[type="submit"]' );

	if ( $( document.body ).hasClass( 'security' ) ) {
		return;
	}

	wp.trac_security = {
		badwords : [
			'sql', 'trojan', 'rce', 'permissions', 'exploit', 'exploits', 'csrf', 'xss', 'sqli',
			'scripting', 'vulnerability', 'vulnerabilities', 'capability', 'capabilities', 'intrusion',
			'intrusions', 'cve', 'disclosure', 'hash', 'security', 'leakage', 'privilege', 'privileges',
			'escape', 'unescape', 'escaped', 'unescaped', 'escapes', 'escaping', 'unescaping', 'esc_',
			'sanitize', 'unsanitize', 'sanitizes', 'unsanitizes', 'sanitized', 'unsanitized', 'sanitization',
			'valid', 'invalid', 'validate', 'validates', 'validation','hack', 'vulnerable', 'attack',
			'compromise', 'escalation', 'injection', 'forgery', 'password', 'passwords', 'cross-site' ,
			'secure'
		],

		intersect : function(a, b) {
			return $.grep(a, function(i) {
				return $.inArray(i, b) > -1;
			});
		},

		has_overlap : function(str, arr){
			var words = str.toLowerCase().replace(/[^a-z|\s]/g, '').split(' '),
				overlap = this.intersect( words, arr);

			return ( overlap.length !== 0 );
		}
	};

	function show_box() {
		// Disable submit only if the box isn't already checked.
		if ( false === $( '#security-question input' ).is( ':checked' ) ) {
			submit.prop( 'disabled', true );
		}

		if ( $( '#security-question' ).length !== 0 ) {
			// We've already created the checkbox
			$( '#security-question' ).show();
		} else {
			// We need to add the checkbox
			$( '.buttons' ).before( '<p id="security-question"><label><input type="checkbox" name="sec_question" />' +
				'&nbsp;I am <strong>not</strong> reporting a security issue</label>' +
				' &mdash; report <a href="http://make.wordpress.org/core/handbook/reporting-security-vulnerabilities/">security issues</a> to the <a href="https://hackerone.com/wordpress">WordPress HackerOne program</a></p>' );
		}

	}

	function hide_box() {
		submit.prop( 'disabled', false );
		$( '#sec_question' ).hide();
	}

	jQuery( '#field-summary, #field-description, #field-keywords' ).on( 'keyup', function() {
		var entry = $(this).val();

		if ( wp.trac_security.has_overlap( entry, wp.trac_security.badwords ) ) {
			show_box();
		} else {
			hide_box();
		}
	});

	propertyform.on( 'change', '#security-question input', function() {
		submit.prop( 'disabled', ! $(this).is( ':checked' ) );
	});
}(jQuery));
