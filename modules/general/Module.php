<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\ModuleInterface;
use Moon\SEO\Services\AdminMenu;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Module implements ModuleInterface {

	private const SLUG = 'general';

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	private AdminMenu $admin_menu;

	// The constructor signature is shared by every module; SupportedPostTypes is not used by this module yet.
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;
		$this->admin_menu     = $admin_menu;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		( new Settings\Settings( $this->option_manager, $this->site_identity ) )->init();

		$admin = new Admin( $this->admin_menu );
		$admin->init();

		( new Assets( $admin ) )->init();
	}
}