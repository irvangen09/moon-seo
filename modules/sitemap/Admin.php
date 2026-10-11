<?php

namespace Moon\SEO\Modules\Sitemap;

use Moon\SEO\Services\AdminMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	private const MENU_SLUG = 'moon-seo-sitemap';

	private const ROOT_ELEMENT_ID = 'moon-seo-sitemap-settings-root';

	private AdminMenu $admin_menu;

	// The value returned by add_submenu_page(), so Assets can target this exact screen.
	private ?string $hook_suffix = null;

	public function __construct( AdminMenu $admin_menu ) {
		$this->admin_menu = $admin_menu;
	}

	public function init(): void {
		// After the top-level menu, which the General module registers at the default priority.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
	}

	public function register_menu(): void {
		$hook_suffix = add_submenu_page(
			$this->admin_menu->get_top_level_slug(),
			__( 'Moon SEO - Sitemap', 'moon-seo' ),
			__( 'Sitemap', 'moon-seo' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);

		$this->hook_suffix = false === $hook_suffix ? null : $hook_suffix;
	}

	// Only the mount point: the whole UI is rendered by the React app.
	public function render_page(): void {
		printf( '<div id="%s"></div>', esc_attr( self::ROOT_ELEMENT_ID ) );
	}

	// Null until admin_menu has run.
	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}
