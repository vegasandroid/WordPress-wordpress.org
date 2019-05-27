/**
 * External dependencies.
 */
import React, { PropTypes } from 'react';
//import { localize } from 'i18n-calypso';
import { identity, map } from 'lodash';

/**
 * Internal dependencies.
 */
import ImageGallery from './image-gallery';

export const Screenshots = ( { screenshots } ) => {
	const items = map( screenshots, ( { caption, src } ) => ( {
		original: src,
		originalAlt: '',
		thumbnail: src,
		thumbnailAlt: caption || '',
		description: caption || false,
	} ) );

	if ( items ) {
		return (
			<div id="screenshots" className="plugin-screenshots tabcontent">
				<h2>{ localeData.screenshots }</h2>
				<ImageGallery items={ items } />
			</div>
		);
	}

	return null;
};

Screenshots.propTypes = {
	screenshots: PropTypes.arrayOf( PropTypes.object ),
	translate: PropTypes.func,
};

Screenshots.defaultProps = {
	screenshots: [],
	translate: identity,
};

//export default localize( Screenshots );
export default Screenshots;
