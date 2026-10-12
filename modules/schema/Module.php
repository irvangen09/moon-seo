<?php

namespace Moon\SEO\Modules\Schema;

use Moon\SEO\ModuleInterface;
use Moon\SEO\Modules\Schema\Nodes\ArticleNode;
use Moon\SEO\Modules\Schema\Nodes\BreadcrumbListNode;
use Moon\SEO\Modules\Schema\Nodes\ImageObjectNode;
use Moon\SEO\Modules\Schema\Nodes\OrganizationNode;
use Moon\SEO\Modules\Schema\Nodes\WebPageNode;
use Moon\SEO\Modules\Schema\Nodes\WebSiteNode;
use Moon\SEO\Modules\Schema\Services\SchemaGraphBuilder;
use Moon\SEO\Services\AdminMenu;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// No settings, admin or editor: every node is derived from site identity and native post data, so there is no decision to hand to the user.
final class Module implements ModuleInterface {

	private const SLUG = 'schema';

	private SiteIdentity $site_identity;

	private SupportedPostTypes $supported_post_types;

	// OptionManager and AdminMenu are not used here; they are accepted so ModuleRegistry can build every module the same way.
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->site_identity        = $site_identity;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		$image_object_node = new ImageObjectNode();

		// The order has no effect on which nodes apply; each one decides for itself.
		$nodes = array(
			new WebSiteNode( $this->site_identity ),
			new OrganizationNode( $this->site_identity, $image_object_node ),
			new BreadcrumbListNode( $this->supported_post_types ),
			new ArticleNode( $image_object_node, $this->supported_post_types ),
			new WebPageNode( $image_object_node, $this->supported_post_types ),
		);

		( new Frontend( new SchemaGraphBuilder( $nodes ) ) )->init();
	}
}
