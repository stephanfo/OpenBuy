require('./css/app.css');
require('font-awesome/scss/font-awesome.scss');
require('./js/app.js');

// require jQuery normally
const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;