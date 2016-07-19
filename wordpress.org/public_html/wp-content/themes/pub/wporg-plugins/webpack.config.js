/* global module:false */
const path = require( 'path' );
const webpack = require( 'webpack' );

module.exports = {
	cache: true,
	entry: [ './js/client/index.jsx' ],
	output: {
		path: __dirname + '/js',
		filename: 'theme.js'
	},
	module: {
		loaders: [
			{
				test: /\.jsx?$/,
				exclude: /node_modules/,
				loader: 'babel-loader'
			}
		]
	},
	plugins: [],

	resolve: {
		extensions: ['', '.js', '.jsx'],
		root: [
			path.resolve('./js/client')
		]
	},
	// stats: false disables the stats output

	progress: false, // Don't show progress
	// Defaults to true

	failOnError: false, // don't report error to grunt if webpack find errors
	// Use this if webpack errors are tolerable and grunt should continue

	watch: false, // use webpacks watcher
	// You need to keep the grunt process alive

	keepalive: false // don't finish the grunt task
	// Use this in combination with the watch option
};
