<?php

namespace Moon\SEO\Modules\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	private Admin $admin;

	public function __construct( Admin $admin ) {
		$this->admin = $admin;
	}

	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	public function enqueue_admin( string $hook_suffix ): void {
		$page_hook = $this->admin->get_hook_suffix();

		if ( null === $page_hook || $hook_suffix !== $page_hook ) {
			return;
		}

		$asset_file = MOON_SEO_PATH . 'build/sitemap-admin.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script( 'moon-seo-sitemap-admin', MOON_SEO_URL . 'build/sitemap-admin.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'moon-seo-sitemap-admin', 'moon-seo', MOON_SEO_PATH . 'languages' );

		if ( file_exists( MOON_SEO_PATH . 'build/style-sitemap-admin.css' ) ) {
			wp_enqueue_style( 'moon-seo-sitemap-admin', MOON_SEO_URL . 'build/style-sitemap-admin.css', array(), $asset['version'] );
			wp_style_add_data( 'moon-seo-sitemap-admin', 'rtl', 'replace' );
		}
	}
}
