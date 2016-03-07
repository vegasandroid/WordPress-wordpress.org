<table class="form-table">
	<?php
		$this->render_textarea_input( $post, 'Description', 'description' );
		$this->render_text_input( $post, 'Invoice Number', 'invoice_number' );
		$this->render_text_input( $post, 'Invoice date', 'invoice_date', '', 'date' );
		$this->render_text_input( $post, 'Requested date for payment/due by', 'due_by', '', 'date' );
		$this->render_text_input( $post, 'Amount', 'payment_amount', 'No commas, thousands separators or currency symbols. Ex. 1234.56' );
		$this->render_select_input( $post, 'Currency', 'currency' );
		$this->render_select_input( $post, 'Category', 'payment_category' );
	?>

	<?php
		$this->render_text_input(
			$post,
			'Other Category',
			'other_category_explanation',
			__( 'Please describe what category this request fits under.', 'wordcamporg' ),
			'text',
			isset( $assigned_category->name ) && 'Other' == $assigned_category->name ? array() : array( 'hidden')    // todo i18n, see notes in insert_default_terms()
		);
	?>

	<?php $this->render_files_input( $post, 'Files', 'files', __( 'Attach supporting documentation including invoices, contracts, or other vendor correspondence. If no supporting documentation is available, please indicate the reason in the notes below.', 'wordcamporg' ) ); ?>
	<?php $this->render_textarea_input( $post, 'Notes', 'general_notes', 'Any other details you want to share.', false ); ?>
</table>

<p class="wcb-form-required">
	<?php _e( '* required', 'wordcamporg' ); ?>
</p>
