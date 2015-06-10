<div class="notice notice-success">
	<h2><?php _e( 'Thank you for submitting your job posting!', 'jobswp' ); ?></h2>

	<p><?php printf( __( 'Your job posting will be moderated in the next 24-48 hours to ensure it meets the criteria listed in our <a href="%s">FAQ</a>. After approval, your posting will stay on the job board for a total of 21 days.',
	'jobswp' ),
	'/faq/' ); ?></p>

	<p><?php printf( __( 'Take note of the following job token. It can be used to remove your job posting from the site before it expires without having to make a request via the feedback form (which could take 24-48 hours to honor). The token can be used on the <a href="%s">job removal page</a>.', 'jobswp' ),
		'/remove-a-job/'
	); ?></p>

	<p style="font-weight:bold;"><?php printf( __( 'Your job token is: %s', 'jobswp' ), $_POST['job_token'] ); ?></p>

	<p><?php printf( __( 'If you would like to modify your posting, or you are having problems removing the job using the job token, please contact us using our <a href="%s">feedback form</a>. Be sure to specify the email address you supplied in your job posting.',
	'jobswp' ),
	'/feedback/' ); ?></p>

	<p><?php _e( "Below you'll find a preview of your job posting.", 'jobswp' ); ?></p>
</div>
