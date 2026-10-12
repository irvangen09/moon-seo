<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Applies to single views of post types whose schema node is "article".
final class ArticleNode implements NodeInterface {

	private ImageObjectNode $image_object_node;

	private SupportedPostTypes $supported_post_types;

	public function __construct( ImageObjectNode $image_object_node, SupportedPostTypes $supported_post_types ) {
		$this->image_object_node    = $image_object_node;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_node(): ?array {
		$post = is_singular() ? get_queried_object() : null;

		if ( ! $post instanceof WP_Post || 'article' !== $this->supported_post_types->schema_node( $post->post_type ) ) {
			return null;
		}

		$permalink = SchemaId::permalink( $post );

		$node = array(
			'@type'     => 'Article',
			'@id'       => SchemaId::article( $permalink ),
			'url'       => $permalink,
			// The full title: truncating it risks cutting off the meaning, and the recommended length is not a requirement.
			'headline'  => SchemaText::post_title( $post ),
			'publisher' => array( '@id' => SchemaId::organization() ),
			'isPartOf'  => array( '@id' => SchemaId::website() ),
		);

		$published = $this->format_datetime( $post, 'date' );

		if ( null !== $published ) {
			$node['datePublished'] = $published;
		}

		$modified = $this->format_datetime( $post, 'modified' );

		if ( null !== $modified ) {
			$node['dateModified'] = $modified;
		}

		$author = $this->get_author( $post );

		if ( null !== $author ) {
			$node['author'] = $author;
		}

		$image = $this->image_object_node->build_for_post( $post, $permalink );

		if ( null !== $image ) {
			$node['image'] = $image;
		}

		return $node;
	}

	private function get_author( WP_Post $post ): ?array {
		$author_id = (int) $post->post_author;
		$name      = SchemaText::plain( (string) get_the_author_meta( 'display_name', $author_id ) );

		if ( '' === $name ) {
			return null;
		}

		$author     = array(
			'@type' => 'Person',
			'name'  => $name,
		);
		$author_url = get_author_posts_url( $author_id );

		if ( '' !== $author_url ) {
			$author['url'] = $author_url;
		}

		return $author;
	}

	// Null when WordPress cannot produce a valid date, so the field is left out instead of printing a wrong one.
	private function format_datetime( WP_Post $post, string $field ): ?string {
		$datetime = get_post_datetime( $post, $field );

		return false === $datetime ? null : $datetime->format( DATE_W3C );
	}
}
