<script id="tmpl-theme" type="text/template">
	<a class="url" href="{{{ data.permalink }}}" rel="bookmark" tabindex="-1">
		<# if ( data.screenshot_url ) { #>
		<div class="theme-screenshot">
			<img src="{{ data.screenshot_url }}?w=572&strip=all" alt="" />
		</div>
		<# } else { #>
		<div class="theme-screenshot blank"></div>
		<# } #>
		<span class="more-details"><?php _ex( 'More Info', 'theme', 'wporg-themes' ); ?></span>
		<# if ( data.author.display_name ) { #>
		<div class="theme-author"><?php printf( _x( 'By %s', 'theme author', 'wporg-themes' ), '<span class="author">{{ data.author.display_name }}</span>' ); ?></div>
		<# } #>
		<h3 class="theme-name entry-title">{{{ data.name }}}</h3>
	</a>
	<div class="theme-actions">
		<a class="button button-primary preview install-theme-preview" href="{{ data.download_link }}"><?php esc_html_e( 'Download', 'wporg-themes' ); ?></a>
	</div>
</script>
