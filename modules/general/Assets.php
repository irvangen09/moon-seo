<?php

namespace Moon\SEO\Modules\General;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	private Admin $admin;

	private Editor $editor;

	public function __construct( Admin $admin, Editor $editor ) {
		$this->admin  = $admin;
		$this->editor = $editor;
	}

	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	public function enqueue_admin( string $hook_suffix ): void {
		$page_hook = $this->admin->get_hook_suffix();

		if ( null === $page_hook || $hook_suffix !== $page_hook ) {
			return;
		}

		$asset_file = MOON_SEO_PATH . 'build/admin.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// The media modal backs the image fields in Site Info and Social.
		wp_enqueue_media();

		wp_enqueue_script( 'moon-seo-admin', MOON_SEO_URL . 'build/admin.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'moon-seo-admin', 'moon-seo', MOON_SEO_PATH . 'languages' );

		if ( file_exists( MOON_SEO_PATH . 'build/style-admin.css' ) ) {
			wp_enqueue_style( 'moon-seo-admin', MOON_SEO_URL . 'build/style-admin.css', array(), $asset['version'] );
			wp_style_add_data( 'moon-seo-admin', 'rtl', 'replace' );
		}
	}

	public function enqueue_editor(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// The block editor also runs for other screens (site editor, other post types); the bundle is not needed there.
		if ( null === $screen || ! $this->editor->has_sidebar( (string) $screen->post_type ) ) {
			return;
		}

		$asset_file = MOON_SEO_PATH . 'build/editor.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script( 'moon-seo-editor', MOON_SEO_URL . 'build/editor.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'moon-seo-editor', 'moon-seo', MOON_SEO_PATH . 'languages' );

		if ( file_exists( MOON_SEO_PATH . 'build/style-editor.css' ) ) {
			wp_enqueue_style( 'moon-seo-editor', MOON_SEO_URL . 'build/style-editor.css', array(), $asset['version'] );
			wp_style_add_data( 'moon-seo-editor', 'rtl', 'replace' );
		}
	}
}
