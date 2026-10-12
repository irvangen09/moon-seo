<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Sitewide: applies to every request, including archives, search and 404.
final class WebSiteNode implements NodeInterface {

	private SiteIdentity $site_identity;

	public function __construct( SiteIdentity $site_identity ) {
		$this->site_identity = $site_identity;
	}

	public function get_node(): ?array {
		return array(
			'@type'     => 'WebSite',
			'@id'       => SchemaId::website(),
			'url'       => home_url( '/' ),
			'name'      => SchemaText::plain( $this->site_identity->get_effective_website_name() ),
			'publisher' => array( '@id' => SchemaId::organization() ),
		);
	}
}
