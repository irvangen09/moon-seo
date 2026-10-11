<?php

namespace Moon\SEO\Modules\Sitemap;

use Moon\SEO\ModuleInterface;
use Moon\SEO\Modules\Sitemap\Services\ContentTypeRegistry;
use Moon\SEO\Modules\Sitemap\Services\SitemapCache;
use Moon\SEO\Modules\Sitemap\Services\XmlBuilder;
use Moon\SEO\Services\AdminMenu;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Module implements ModuleInterface {

	private const SLUG = 'sitemap';

	private OptionManager $option_manager;

	private AdminMenu $admin_menu;

	// SiteIdentity and SupportedPostTypes are not used here (the sitemap covers every public post type and taxonomy); they are accepted so ModuleRegistry can build every module the same way.
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager = $option_manager;
		$this->admin_menu     = $admin_menu;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		( new Settings\Settings( $this->option_manager, new ContentTypeRegistry() ) )->init();

		$admin = new Admin( $this->admin_menu );
		$admin->init();

		( new Frontend( $this->option_manager, new SitemapCache(), new XmlBuilder() ) )->init();
		( new Assets( $admin ) )->init();
	}
}
