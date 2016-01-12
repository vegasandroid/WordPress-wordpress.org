jQuery( document ).ready( function( $ ) {

	/*
	 * Model for an attached file
	 */
	$.paymentRequests.AttachedFile = Backbone.Model.extend( {
		defaults: {
			'ID':          0,
			'post_parent': 0,
			'filename':    '',
			'url':         ''
		}
	} );

	/*
	 * Collection of attached files
	 */
	$.paymentRequests.AttachedFiles = Backbone.Collection.extend( {
		model: $.paymentRequests.AttachedFile
	} );

	/*
	 * View for a single attached file
	 */
	$.paymentRequests.AttachedFileView = Backbone.View.extend( {
		tagName: 'li',
		template: wp.template( 'wcp-attached-file' ),

		initialize: function() {
			_.bindAll( this, 'render' );
		},

		render: function() {
			$( this.el ).html( this.template( this.model.toJSON() ) );
			return this;
		}
	} );

	/*
	 * View for a collection of attached files
	 */
	$.paymentRequests.AttachedFilesView = Backbone.View.extend( {
		el: $( '#wcp_files' ),

		initialize: function() {
			_.bindAll( this, 'render', 'appendFile' );

			this.collection = new $.paymentRequests.AttachedFiles( wcpAttachedFiles );
			this.collection.bind( 'add', this.appendFile );

			this.render();
		},

		render: function() {
			var self = this;

			_( this.collection.models ).each( function( file ) {
				self.appendFile( file );
			} );
		},

		appendFile: function( file ) {
			var noFilesUploaded  = $( '.wcp_no_files_uploaded' );
			var attachedFileView = new $.paymentRequests.AttachedFileView( { model: file } );

			$( '.wcp_files_list' ).append( attachedFileView.render().el );
			noFilesUploaded.removeClass( 'active' );
			noFilesUploaded.addClass( 'hidden' );

			this.attachExistingFile( file );
		},

		/**
		 * Keep track of existing files that should be attached to the request
		 *
		 * Sometimes users add existing files to the request, rather than uploading new ones. We need to keep track
		 * of those so that they can be attached to the request when the form is submitted.
		 *
		 * Files that are already attached to other posts are ignored.
		 *
		 * @param {paymentRequests.AttachedFile} file
		 */
		attachExistingFile: function( file ) {
			var fileIDsToAttach,
				existingFilesToAttach = $( '#wcp_existing_files_to_attach' );

			try {
				fileIDsToAttach = JSON.parse( existingFilesToAttach.val() );
			} catch ( exception ) {
				fileIDsToAttach = [];
			}

			if ( 0 === file.get( 'post_parent' ) && -1 === $.inArray( file.get( 'ID' ), fileIDsToAttach ) ) {
				fileIDsToAttach.push( file.get( 'ID' ) );
				existingFilesToAttach.val( JSON.stringify( fileIDsToAttach ) );
			}
		}
	} );

	$.paymentRequests.attachedFilesView = new $.paymentRequests.AttachedFilesView();

} );
