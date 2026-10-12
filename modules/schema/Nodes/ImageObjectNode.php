<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Not a top-level node: it builds the nested "image" and "logo" objects. Only the featured image and the site logo are covered, and no license or credit data is claimed.
final class ImageObjectNode {

	// Null when the post has no featured image or its attachment no longer exists, so the caller omits "image" instead of printing an empty object.
	public function build_for_post( WP_Post $post, string $permalink ): ?array {
		$attachment_id = (int) get_post_thumbnail_id( $post );

		if ( $attachment_id <= 0 ) {
			return null;
		}

		return $this->build_for_attachment( $attachment_id, SchemaId::primary_image( $permalink ) );
	}

	public function build_for_attachment( int $attachment_id, string $node_id = '' ): ?array {
		$src = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( false === $src ) {
			return null;
		}

		$image = array( '@type' => 'ImageObject' );

		if ( '' !== $node_id ) {
			$image['@id'] = $node_id;
		}

		$image['url'] = $src[0];

		// An attachment without metadata (an SVG, for example) reports 0; a zero size is wrong, so it is left out.
		if ( (int) $src[1] > 0 && (int) $src[2] > 0 ) {
			$image['width']  = (int) $src[1];
			$image['height'] = (int) $src[2];
		}

		return $image;
	}
}
