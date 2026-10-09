<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\ModuleInterface;
use Moon\SEO\Modules\General\Services\DescriptionGenerator;
use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Modules\General\Services\PlaceholderResolver;
use Moon\SEO\Modules\General\Services\TitleResolver;
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

	private SupportedPostTypes $supported_post_types;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->site_identity        = $site_identity;
		$this->admin_menu           = $admin_menu;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		( new Settings\Settings( $this->option_manager, $this->site_identity ) )->init();

		$admin = new Admin( $this->admin_menu );
		$admin->init();

		$editor = new Editor( $this->supported_post_types );
		$editor->init();

		( new MetaBox( $editor, $this->supported_post_types ) )->init();

		( new Assets( $admin, $editor ) )->init();

		// The frontend and the editor preview share these, so the preview always matches the output.
		$placeholder_resolver = new PlaceholderResolver( $this->option_manager, $this->site_identity );
		$title_resolver       = new TitleResolver( $placeholder_resolver, $this->option_manager, $this->supported_post_types );
		$description_resolver = new DescriptionResolver( $this->option_manager, $placeholder_resolver, new DescriptionGenerator(), $this->supported_post_types );

		( new Frontend( $this->option_manager, $this->site_identity, $this->supported_post_types, $title_resolver, $description_resolver ) )->init();

		( new PreviewRoute( $title_resolver, $description_resolver, $this->supported_post_types ) )->init();

		( new UrlRewriter( $this->option_manager ) )->init();
	}
}
