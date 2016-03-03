<?php if ( ! empty( $box['args']['introduction_message'] ) ) : ?>
	<p>
		<?php echo wp_kses( $box['args']['introduction_message'], array( 'p' => array() ) ); ?>
	</p>
<?php endif; ?>

<fieldset <?php disabled( $box['args']['fields_enabled'], false ); ?> >

	<table class="form-table">
		<?php if ( $box['args']['show_vendor_requested_payment_method'] ) : ?>
			<?php $this->render_textarea_input( $post, 'Did the vendor request a specific type of payment?', 'vendor_requested_payment_method', 'Add any relevant details' ); ?>
		<?php endif;?>

		<?php $this->render_radio_input( $post, 'Payment Method', 'payment_method' ); ?>
	</table>

	<table id="payment_method_direct_deposit_fields" class="form-table payment_method_fields <?php echo 'Direct Deposit' == $selected_payment_method ? 'active' : 'hidden'; ?>">
		<?php $this->render_text_input( $post, 'Bank Name', 'ach_bank_name' ); ?>
		<?php $this->render_radio_input( $post, 'Account Type', 'ach_account_type' ); ?>
		<?php $this->render_text_input( $post, 'Routing Number', 'ach_routing_number' ); ?>
		<?php $this->render_text_input( $post, 'Account Number', 'ach_account_number' ); ?>
		<?php $this->render_text_input( $post, 'Account Holder Name', 'ach_account_holder_name' ); ?>
	</table>

	<div id="payment_method_check_fields" class="form-table payment_method_fields <?php echo 'Check' == $selected_payment_method ? 'active' : 'hidden'; ?>">
		<p>
			<?php _e( 'Please fill out all the below fields to ensure that the check is sent successfully.', 'wordcamporg' ); ?>
		</p>

		<table>
			<?php $this->render_text_input( $post, 'Payable To', 'payable_to' ); ?>
			<?php $this->render_text_input(    $post, 'Street Address',    'check_street_address' ); ?>
			<?php $this->render_text_input(    $post, 'City',              'check_city'           ); ?>
			<?php $this->render_text_input(    $post, 'State / Province',  'check_state'          ); ?>
			<?php $this->render_text_input(    $post, 'ZIP / Postal Code', 'check_zip_code'       ); ?>
			<?php $this->render_country_input( $post, 'Country',           'check_country'        ); ?>
		</table>
	</div>

	<p id="payment_method_credit_card_fields" class="description payment_method_fields <?php echo 'Credit Card' == $selected_payment_method ? 'active' : 'hidden'; ?>">
		<?php _e( 'Please make sure that you upload an authorization form above, if one is required by the vendor.', 'wordcamporg' ); ?>
	</p>

	<div id="payment_method_wire_fields" class="form-table payment_method_fields <?php echo 'Wire' == $selected_payment_method ? 'active' : 'hidden'; ?>">
		<p>
			<?php _e(
				'Please include Bank Name, SWIFT code, Beneficiary Name, and Beneficiary Account Number to ensure that your wire is sent successfully.',
				'wordcamporg'
			); ?>
		</p>

		<h3>
			<?php _e( "Beneficiary's Bank", 'wordcamporg' ); ?>
		</h3>

		<table>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank Name',              'bank_name' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank Street Address',    'bank_street_address' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank City',              'bank_city' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank State / Province',  'bank_state' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank ZIP / Postal Code', 'bank_zip_code' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank Country',           'bank_country' ); ?>
			<?php $this->render_country_input( $post, 'Beneficiary’s Bank Country ISO 3166', 'bank_country_iso3166' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Bank SWIFT BIC',         'bank_bic' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Account Number or IBAN', 'beneficiary_account_number' ); ?>
		</table>

		<hr />
		<h3>
			<?php _e( "Intermediary Bank", 'wordcamporg' ); ?>
		</h3>

		<?php $this->render_checkbox_input(
			$post,
			__( 'Send this payment through an intermediary bank', 'wordcamporg' ),
			'needs_intermediary_bank'
		); ?>

		<table>
			<?php $this->render_text_input( $post, 'Intermediary Bank Name',              'interm_bank_name' ); ?>
			<?php $this->render_text_input( $post, 'Intermediary Bank Street Address',    'interm_bank_street_address' ); ?>
			<?php $this->render_text_input( $post, 'Intermediary Bank City',              'interm_bank_city' ); ?>
			<?php $this->render_text_input( $post, 'Intermediary Bank State / Province',  'interm_bank_state' ); ?>
			<?php $this->render_text_input( $post, 'Intermediary Bank ZIP / Postal Code', 'interm_bank_zip_code' ); ?>
			<?php $this->render_country_input( $post, 'Intermediary Bank Country',        'interm_bank_country_iso3166' ); ?>
			<?php $this->render_text_input( $post, 'Intermediary Bank SWIFT BIC',         'interm_bank_swift' ); ?>
			<?php $this->render_text_input( $post, 'Intermediary Bank Account',           'interm_bank_account' ); ?>
		</table>

		<hr />
		<h3>
			<?php _e( "Beneficiary", 'wordcamporg' ); ?>
		</h3>

		<table>
			<?php $this->render_text_input( $post, 'Beneficiary’s Name',              'beneficiary_name' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Street Address',    'beneficiary_street_address' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s City',              'beneficiary_city' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s State / Province',  'beneficiary_state' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s ZIP / Postal Code', 'beneficiary_zip_code' ); ?>
			<?php $this->render_text_input( $post, 'Beneficiary’s Country',           'beneficiary_country' ); ?>
			<?php $this->render_country_input( $post, 'Beneficiary’s Country ISO 3166', 'beneficiary_country_iso3166' ); ?>
		</table>
	</div>
</fieldset>
