<?php /** @var $regions               array */ ?>
<?php /** @var $sponsorship_levels    array */ ?>
<?php /** @var $regional_sponsorships array */ ?>

<table>
	<thead>
		<tr>
			<th>Region</th>
			<th>Sponsorship Level</th>
		</tr>
	</thead>

	<tbody>
		<?php foreach ( $regions as $region ) : ?>
			<?php
				// Some old regions are deprecated, but must remain in the system for back-compat. This prevents them from cluttering the UI when they're not needed.
				$row_class = false !== stripos( $region->name, 'deprecated' ) && 'null' === $regional_sponsorships[ $region->term_id ] ? 'hidden' : '';
			?>

			<tr class="<?php echo esc_attr( $row_class ); ?>">
				<td>
					<label for="mes_regional_sponsorships-<?php echo esc_attr( $region->term_id ); ?>">
						<?php echo esc_html( $region->name ); ?>
					</label>
				</td>

				<td>
					<select id="mes_regional_sponsorships-<?php echo esc_attr( $region->term_id ); ?>" name="mes_regional_sponsorships[<?php echo esc_attr( $region->term_id ); ?>]">
						<option value="null">None</option>

						<?php foreach ( $sponsorship_levels as $level ) : ?>
							<option value="<?php echo esc_attr( $level->ID ); ?>" <?php selected( $regional_sponsorships[ $region->term_id ], $level->ID ); ?>>
								<?php echo esc_html( $level->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
