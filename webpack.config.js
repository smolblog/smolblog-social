const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );

module.exports = {
	...defaultConfig,
  entry: {
    main: './js/main.js',
  },
	plugins: defaultConfig.plugins.filter(plugin => {
		return !(plugin instanceof CopyWebpackPlugin);
	}),
};