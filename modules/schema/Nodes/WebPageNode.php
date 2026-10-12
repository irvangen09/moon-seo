<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Applies to single views of post types whose schema node is "webpage". Kept minimal on purpose: a page can be anything, so headline, author and date framing would misrepresent it.
final class WebPageNode implements NodeInterface {

	private ImageObjectNode $image_object_node;

	private SupportedPostTypes $supported_post_types;

	public function __construct( ImageObjectNode $image_object_node, SupportedPostTypes $supported_post_types ) {
		$this->image_object_node    = $image_object_node;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_node(): ?array {
		$post = is_singular() ? get_queried_object() : null;

		if ( ! $post instanceof WP_Post || 'webpage' !== $this->supported_post_types->schema_node( $post->post_type ) ) {
			return null;
		}

		$permalink = SchemaId::permalink( $post );

		$node = array(
			'@type'      => 'WebPage',
			'@id'        => SchemaId::webpage( $permalink ),
			'url'        => $permalink,
			'name'       => SchemaText::post_title( $post ),
			'isPartOf'   => array( '@id' => SchemaId::website() ),
			// The breadcrumb applies to every supported post type, so this reference always resolves.
			'breadcrumb' => array( '@id' => SchemaId::breadcrumb( $permalink ) ),
		);

		$image = $this->image_object_node->build_for_post( $post, $permalink );

		if ( null !== $image ) {
			$node['image'] = $image;
		}

		return $node;
	}
}
