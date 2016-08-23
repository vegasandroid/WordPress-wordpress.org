import Api from 'modules/api';
import {
	GET_PAGE,
	GET_BROWSE,
	GET_PLUGIN,
	SEARCH_PLUGINS
} from './action-types';

export const getPage = ( slug ) => ( dispatch ) => {
	Api.get( '/wp/v2/pages', { filter: { name: slug } }, ( data, error ) => {
		if ( ! data.length || error ) {
			return;
		}

		dispatch( {
			type: GET_PAGE,
			page: data[0]
		} );
	} );
};

export const getBrowse = ( type ) => ( dispatch ) => {
	Api.get( '/plugins/v1/query-plugins', { browse: type }, ( data, error ) => {
		if ( ! data.plugins.length || error ) {
			return;
		}

		dispatch( {
			type: GET_BROWSE,
			plugins: data.plugins,
			term: type
		} );
	} );
};

export const getPlugin = ( slug ) => ( dispatch ) => {
	Api.get( '/plugins/v1/plugin/' + slug, {}, ( data, error ) => {
		if ( ! data || error ) {
			return;
		}

		dispatch( {
			type: GET_PLUGIN,
			plugin: data
		} );
	} );
};

export const searchPlugins = ( searchTerm ) => ( dispatch ) => {
	Api.get( '/wp/v2/plugin', { search: searchTerm }, ( data, error ) => {
		if ( ! data || error ) {
			return;
		}

		dispatch( {
			type: SEARCH_PLUGINS,
			searchTerm: searchTerm,
			plugins: data
		} );
	} );
};
