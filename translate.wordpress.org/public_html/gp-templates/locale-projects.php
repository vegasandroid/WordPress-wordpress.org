<?php
gp_title( sprintf( __( 'Projects translated to %s &lt; GlotPress' ),  esc_html( $locale->english_name ) ) );

$breadcrumb   = array();
$breadcrumb[] = gp_link_get( '/', __( 'Locales' ) );
$breadcrumb[] = gp_link_get( gp_url_join( '/locale', $locale_slug, $set_slug ), esc_html( $locale->english_name ) );
$breadcrumb[] = esc_html( $project->name );
gp_breadcrumb( $breadcrumb );
gp_tmpl_header();
?>

<div class="locale-header">
	<p class="locale-intro">Translate WordPress, core projects, plugins, and themes into your language. Select your project below to get started.</p>

	<div class="locale-box">
		<ul class="name">
			<li class="english"><?php echo $locale->english_name; ?></li>
			<li class="native"><?php echo $locale->native_name; ?></li>
			<li class="code">
				<?php
				echo $locale->wp_locale;

				if ( count( $variants ) > 1 ) {
					?>
					<select id="variant-selector" name="variant">
						<?php
						foreach ( $variants as $variant ) {
							$selected =
							printf(
								'<option name="%s" data-project-url="%s"%s>%s</option>',
								$variant,
								esc_url( gp_url_join( '/locale', $locale_slug, $variant, $project->slug ) ),
								( $set_slug == $variant ) ? ' selected="selected"' : '',
								ucfirst( $variant )
							);
						}
						?>
					</select>
					<?php
				}
				?>
			</li>
		</ul>
		<div class="contributors">
			<?php
			$contributors = sprintf(
				'<span class="dashicons dashicons-admin-users"></span><br />%s',
				isset( $contributors_count[ $locale->slug ] ) ? $contributors_count[ $locale->slug ] : 0
			);
			echo gp_link_get( 'https://make.wordpress.org/polyglots/teams/?locale=' . $locale->wp_locale, $contributors );
			?>
		</div>
	</div>
</div>

<div class="filter-header">
	<ul class="filter-header-links">
		<?php
		foreach ( $top_level_projects as $top_level_project ) {
			printf(
				'<li><a href="%s"%s>%s</a></li>',
				gp_url_join( '/locale', $locale_slug, $set_slug, $top_level_project->slug ),
				( $top_level_project->path == $project_path ) ? ' class="current"' : '',
				$top_level_project->name
			);
		}
		?>
	</ul>
	<div class="search-form">
		<label class="screen-reader-text" for="projects-filter"><?php esc_attr_e( 'Search projects...' ); ?></label>
		<input placeholder="<?php esc_attr_e( 'Search projects...' ); ?>" type="search" id="projects-filter" class="filter-search">
	</div>
</div>

<div id="projects" class="projects">
	<?php
	foreach ( $sub_projects as $sub_project ) {
		$percent_complete = $waiting = $sub_projects_count = 0;
		if ( isset( $project_status[ $sub_project->id ] ) ) {
			$status = $project_status[ $sub_project->id ];
			$percent_complete = $status->percent_complete;
			$waiting = $status->waiting_count;
			$sub_projects_count = $status->sub_projects_count;
		}

		$project_url = gp_url_join( '/locale', $locale_slug, $set_slug, $sub_project->path );

		$project_icon = '';
		if ( isset( $project_icons[ $sub_project->id ] ) ) {
			$project_icon = $project_icons[ $sub_project->id ];
		}

		$classes = 'project-' . sanitize_title_with_dashes( str_replace( '/', '-', $project->path ) );
		$classes .= ' project-' . sanitize_title_with_dashes( str_replace( '/', '-', $sub_project->path ) );
		$classes .= ' percent-' . $percent_complete;
		?>
		<div class="project <?php echo $classes; ?>">
			<div class="project-top">
				<div class="project-icon">
					<?php echo gp_link_get( $project_url, $project_icon ) ?>
				</div>

				<div class="project-name">
					<h4>
						<?php echo gp_link_get( $project_url, $sub_project->name ) ?>
					</h4>
				</div>
				<div class="project-description">
					<p><?php echo $sub_project->description; ?></p>
				</div>
			</div>

			<div class="project-status">
				<div class="project-status-sub-projects">
					<span class="project-status-title">Sub-Projects</span>
					<span class="project-status-value"><?php echo $sub_projects_count; ?></span>
				</div>
				<div class="project-status-waiting">
					<span class="project-status-title">Waiting</span>
					<span class="project-status-value"><?php echo $waiting; ?></span>
				</div>
				<div class="project-status-progress">
					<span class="project-status-title">Progress</span>
					<span class="project-status-value"><?php echo $percent_complete; ?>%</span>
				</div>
			</div>

			<div class="percent">
				<div class="percent-complete" style="width:<?php echo $percent_complete; ?>%;"></div>
			</div>

			<div class="project-bottom">
				<div class="button contribute-button">
					<?php echo gp_link_get( $project_url, 'Translate Project' ) ?>
				</div>
			</div>
		</div>
		<?php
	}
	?>
</div>

<script>
	jQuery( document ).ready( function( $ ) {
		$rows = $( '#projects' ).find( '.project' );
		$( '#projects-filter' ).on( 'input keyup', function() {
			var words = this.value.toLowerCase().split( ' ' );

			if ( '' === this.value.trim() ) {
				$rows.show();
			} else {
				$rows.hide();
				$rows.filter( function( i, v ) {
					var $t = $(this).find( '.project-top' );
					for ( var d = 0; d < words.length; ++d ) {
						if ( $t.text().toLowerCase().indexOf( words[d] ) != -1 ) {
							return true;
						}
					}
					return false;
				}).show();
			}
		});

		$( '#variant-selector' ).on( 'change', function( event ) {
			event.preventDefault();

			var $optionSelected = $( 'option:selected', this ),
				projectUrl = $optionSelected.data( 'projectUrl' );

			if ( projectUrl.length ) {
				window.location = projectUrl;
			}
		});
	});
</script>

<?php gp_tmpl_footer();
