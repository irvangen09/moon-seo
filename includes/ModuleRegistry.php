<?php

namespace Moon\SEO;

use Moon\SEO\Services\AdminMenu;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModuleRegistry {

	private const MODULES = array(
		\Moon\SEO\Modules\General\Module::class,
		\Moon\SEO\Modules\Sitemap\Module::class,
		\Moon\SEO\Modules\Schema\Module::class,
	);

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	private AdminMenu $admin_menu;

	private SupportedPostTypes $supported_post_types;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->site_identity        = $site_identity;
		$this->admin_menu           = $admin_menu;
		$this->supported_post_types = $supported_post_types;
	}

	public function register_active_modules(): void {
		foreach ( self::MODULES as $module_class ) {
			if ( ! class_exists( $module_class ) ) {
				continue;
			}

			// Every module takes the same constructor arguments, even ones it
			// does not use yet, so instantiation stays uniform.
			$module = new $module_class( $this->option_manager, $this->site_identity, $this->admin_menu, $this->supported_post_types );

			if ( ! $module instanceof ModuleInterface ) {
				continue;
			}

			if ( $this->is_module_active( $module->get_slug() ) ) {
				$module->init();
			}
		}
	}

	private function is_module_active( string $module_slug ): bool {
		/**
		 * Filters whether a Moon SEO module should be initialized on this request.
		 *
		 * @since 0.1.0
		 *
		 * @param bool   $active      Whether the module is active. Default true.
		 * @param string $module_slug Module slug: 'general', 'sitemap', or 'schema'.
		 */
		return (bool) apply_filters( 'moon_seo_module_is_active', true, $module_slug );
	}
}
