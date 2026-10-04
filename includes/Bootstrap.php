<?php

namespace Moon\SEO;

use Moon\SEO\Services\AdminMenu;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	private const MIN_PHP_VERSION = '8.0';

	private const MIN_WP_VERSION = '6.9';

	public function run(): void {
		// Translations may only load on init (WordPress 6.7+). Hooking first
		// keeps this callback ahead of the modules' own init callbacks.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( ! $this->is_php_supported() || ! $this->is_wp_supported() ) {
			add_action( 'admin_notices', array( $this, 'render_environment_notice' ) );
			return;
		}

		$this->register_modules();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'moon-seo', false, dirname( MOON_SEO_BASENAME ) . '/languages' );
	}

	public function render_environment_notice(): void {
		if ( ! $this->is_php_supported() ) {
			$message = sprintf(
				/* translators: %s: minimum required PHP version. */
				__( 'Moon SEO requires PHP %s or higher. Please contact your hosting provider to upgrade PHP.', 'moon-seo' ),
				self::MIN_PHP_VERSION
			);
		} else {
			$message = sprintf(
				/* translators: %s: minimum required WordPress version. */
				__( 'Moon SEO requires WordPress %s or higher. Please update WordPress.', 'moon-seo' ),
				self::MIN_WP_VERSION
			);
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	private function is_php_supported(): bool {
		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}

	// WordPress core already enforces the plugin headers; this covers load
	// paths that bypass that check.
	private function is_wp_supported(): bool {
		global $wp_version;

		return ! isset( $wp_version ) || version_compare( $wp_version, self::MIN_WP_VERSION, '>=' );
	}

	private function register_modules(): void {
		$option_manager       = new OptionManager();
		$site_identity        = new SiteIdentity( $option_manager );
		$admin_menu           = new AdminMenu();
		$supported_post_types = new SupportedPostTypes();

		$registry = new ModuleRegistry( $option_manager, $site_identity, $admin_menu, $supported_post_types );
		$registry->register_active_modules();
	}
}