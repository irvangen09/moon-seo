<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Applies to a single view of a supported post type, so excluding a post type also removes its breadcrumb.
final class BreadcrumbListNode implements NodeInterface {

	private SupportedPostTypes $supported_post_types;

	public function __construct( SupportedPostTypes $supported_post_types ) {
		$this->supported_post_types = $supported_post_types;
	}

	public function get_node(): ?array {
		$post = is_singular() ? get_queried_object() : null;

		// The home page is the top of every trail, so a trail on it would only list it twice.
		if ( is_front_page() || ! $post instanceof WP_Post || ! $this->supported_post_types->is_supported( $post->post_type ) ) {
			return null;
		}

		$permalink = SchemaId::permalink( $post );

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => SchemaId::breadcrumb( $permalink ),
			'itemListElement' => $this->to_list_items( $this->build_trail( $post, $permalink ) ),
		);
	}

	// Home, then the parent pages (hierarchical post types) or the category chain (all others), then the current post.
	private function build_trail( WP_Post $post, string $permalink ): array {
		$trail = array(
			array(
				'name' => __( 'Home', 'moon-seo' ),
				'url'  => home_url( '/' ),
			),
		);

		$trail = array_merge(
			$trail,
			is_post_type_hierarchical( $post->post_type ) ? $this->build_ancestor_trail( $post ) : $this->build_category_trail( $post )
		);

		$trail[] = array(
			'name' => SchemaText::post_title( $post ),
			'url'  => $permalink,
		);

		return $trail;
	}

	// get_post_ancestors() runs from the closest parent to the furthest, so it is reversed to read general to specific.
	private function build_ancestor_trail( WP_Post $post ): array {
		$trail = array();

		foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );

			if ( ! $ancestor instanceof WP_Post ) {
				continue;
			}

			$trail[] = array(
				'name' => SchemaText::post_title( $ancestor ),
				'url'  => SchemaId::permalink( $ancestor ),
			);
		}

		return $trail;
	}

	private function build_category_trail( WP_Post $post ): array {
		$primary = $this->get_primary_category( $post );

		if ( null === $primary ) {
			return array();
		}

		$terms = array();

		foreach ( array_reverse( get_ancestors( $primary->term_id, 'category', 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'category' );

			if ( $ancestor instanceof WP_Term ) {
				$terms[] = $ancestor;
			}
		}

		$terms[] = $primary;

		$trail = array();

		foreach ( $terms as $term ) {
			$term_link = get_term_link( $term );

			// An entry without a URL cannot be a valid list item.
			if ( is_wp_error( $term_link ) ) {
				continue;
			}

			$trail[] = array(
				'name' => SchemaText::plain( $term->name ),
				'url'  => $term_link,
			);
		}

		return $trail;
	}

	// The deepest category gives the most specific trail; the lowest ID breaks a tie so the choice never depends on name order.
	private function get_primary_category( WP_Post $post ): ?WP_Term {
		$primary       = null;
		$primary_depth = -1;

		foreach ( get_the_category( $post->ID ) as $category ) {
			$depth = count( get_ancestors( $category->term_id, 'category', 'taxonomy' ) );

			if ( $depth > $primary_depth || ( $depth === $primary_depth && $category->term_id < $primary->term_id ) ) {
				$primary       = $category;
				$primary_depth = $depth;
			}
		}

		return $primary;
	}

	private function to_list_items( array $trail ): array {
		$items = array();

		foreach ( array_values( $trail ) as $index => $entry ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $entry['name'],
				'item'     => $entry['url'],
			);
		}

		return $items;
	}
}
