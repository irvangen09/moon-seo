<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Sitewide: applies to every request.
final class OrganizationNode implements NodeInterface {

	private SiteIdentity $site_identity;

	private ImageObjectNode $image_object_node;

	public function __construct( SiteIdentity $site_identity, ImageObjectNode $image_object_node ) {
		$this->site_identity     = $site_identity;
		$this->image_object_node = $image_object_node;
	}

	public function get_node(): ?array {
		$node = array(
			'@type' => 'Organization',
			'@id'   => SchemaId::organization(),
			'name'  => SchemaText::plain( $this->site_identity->get_effective_website_name() ),
			'url'   => home_url( '/' ),
		);

		$alternate_name = SchemaText::plain( $this->site_identity->get_alternate_website_name() );

		if ( '' !== $alternate_name ) {
			$node['alternateName'] = $alternate_name;
		}

		// Left out when no site image is set or the attachment was deleted.
		$site_image_id = $this->site_identity->get_site_image_id();
		$logo          = $site_image_id > 0 ? $this->image_object_node->build_for_attachment( $site_image_id ) : null;

		if ( null !== $logo ) {
			$node['logo'] = $logo;
		}

		return $node;
	}
}
