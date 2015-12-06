<?php
/**
 * WordPress-specific WPORG SSO: redirects all WP login and registration screens to our SSO ones.
 * 
 * @uses WPOrg_SSO (class-wporg-sso.php)
 * @author stephdau
 */
if ( ! class_exists( 'WPOrg_SSO' ) ) {
	require_once __DIR__ . '/class-wporg-sso.php';
}

if ( class_exists( 'WPOrg_SSO' ) && ! class_exists( 'WP_WPOrg_SSO' ) ) {
	class WP_WPOrg_SSO extends WPOrg_SSO {
		/**
		 * Constructor: add our action(s)/filter(s)
		 */
		public function __construct() {
			parent::__construct();

			if ( $this->has_host() ) {
				add_action( 'init', array( &$this, 'redirect_all_login_or_signup_to_sso' ) );
				// De-hooking the password change notification, too high volume on wp.org, for no admin value.
				remove_action( 'after_password_reset', 'wp_password_change_notification' );
			}
		}
	
		/**
		 * Redirect all attempts to get to a WP login or signup to the SSO ones, or to a safe redirect location.
		 
		 * @example add_action( 'init', array( &$wporg_sso, 'redirect_all_wp_login_or_signup_to_sso' ) );
		 * 
		 * @note Also handles accesses to lost password forms, since wp-login too.
		 */
		public function redirect_all_login_or_signup_to_sso() {
			if ( ! $this->_is_valid_targeted_domain( $this->host ) ) {
				// Not in list of targeted domains, not interested, bail out
				return;
			}
			
			$redirect_req = $this->_get_safer_redirect_to();
			
			// Add our host to the list of allowed ones.
			add_filter( 'allowed_redirect_hosts', array( &$this, 'add_allowed_redirect_host' ) );
			
			if ( preg_match( '/\/wp-signup\.php$/', $this->script ) ) {
				// If we're on any WP signup screen, redirect to the SSO host one,respecting the user's redirect_to request
				$this->_safe_redirect( add_query_arg( 'redirect_to', urlencode( $redirect_req ), $this->sso_signup_url ) );
			
			} else if ( self::SSO_HOST !== $this->host ) {
				// If we're not on the SSO host
				if ( preg_match( '/\/wp-login\.php$/', $this->script ) ) {
					// If on a WP login screen...
					$redirect_to_sso_login = $this->sso_login_url;
					
					// Pass thru the requested action, loggedout, if any
					if ( ! empty( $_GET ) ) {
						$redirect_to_sso_login = add_query_arg( $_GET, $redirect_to_sso_login );
					}
					
					// Pay extra attention to the post-process redirect_to
					$redirect_to_sso_login = add_query_arg( 'redirect_to', urlencode( $redirect_req ), $redirect_to_sso_login );
					
					// And actually redirect to the SSO login
					$this->_safe_redirect( $redirect_to_sso_login );
				} else {
					// Otherwise, filter the login_url to point to the SSO
					add_action( 'login_url', array( &$this, 'login_url' ), 10, 2 );
				}
			} else if ( self::SSO_HOST === $this->host ) {
				// If on the SSO host
				if ( ! preg_match( '/\/wp-login\.php$/', $this->script ) ) {
					// ... but not on its login or signup screen.
					// TODO: Relax rules when we want more  out of our theme then bypassing it altogether with redirects.
					if ( is_user_logged_in() ) {
						// Mimic what happens after a login without a specified redirect.
						$this->_safe_redirect( 'https://wordpress.org/support/profile/' . get_currentuserinfo()->user_login );
					} else {
						// Otherwise, redirect to the login screen.
						$this->_safe_redirect( $this->sso_login_url );
					}
				} else {
					// if on login screen, filter network_site_url to make sure our forms go to the SSO host, not wordpress.org
					add_action( 'network_site_url', array( &$this, 'login_network_site_url' ), 10, 3 );
				}
			}
		}
		
		/**
		 * Modifies the network_site_url on login.wordpress.org's login screen to make sure all forms and links
		 * go to the SSO host, not wordpress.org
		 * 
		 * @param string $url
		 * @param string $path
		 * @param string $scheme
		 * @return string 
		 * 
		 * @example add_action( 'network_site_url', array( &$this, 'login_network_site_url' ), 10, 3 );
		 */
		public function login_network_site_url( $url, $path, $scheme ) {
			if ( self::SSO_HOST === $this->host && preg_match( '/\/wp-login\.php$/', $this->script ) ) {
				$url = preg_replace( '/^(https?:\/\/)[^\/]+(\/.+)$/' , '\1'.self::SSO_HOST.'\2', $url );
			}
			
			return $url;
		}
	}
	
	new WP_WPOrg_SSO();
}
