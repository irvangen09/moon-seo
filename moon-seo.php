<?php
/**
 * Plugin Name:       Moon SEO
 * Description:       A lightweight, modular WordPress plugin for technical SEO: meta tags, XML sitemaps, and structured data.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Irvan Noerfazri
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       moon-seo
 * Domain Path:       /languages
 *
 * @package Moon\SEO
 */

namespace Moon\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOON_SEO_VERSION', '0.1.0' );
define( 'MOON_SEO_FILE', __FILE__ );
define( 'MOON_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'MOON_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'MOON_SEO_BASENAME', plugin_basename( __FILE__ ) );

require_once MOON_SEO_PATH . 'includes/Autoloader.php';

// Module prefixes first, so a module class is not looked up under includes/.
( new Autoloader( 'Moon\SEO\Modules\General', MOON_SEO_PATH . 'modules/general' ) )->register();
( new Autoloader( 'Moon\SEO\Modules\Sitemap', MOON_SEO_PATH . 'modules/sitemap' ) )->register();
( new Autoloader( 'Moon\SEO\Modules\Schema', MOON_SEO_PATH . 'modules/schema' ) )->register();
( new Autoloader( 'Moon\SEO', MOON_SEO_PATH . 'includes' ) )->register();

add_action(
	'plugins_loaded',
	static function () {
		( new Bootstrap() )->run();
	}
);