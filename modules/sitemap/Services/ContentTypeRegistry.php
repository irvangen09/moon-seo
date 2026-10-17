<?php

namespace Moon\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContentTypeRegistry {

	// Handled by their own toggles in Sitemap Content, so they are not offered as custom types.
	private const BUILT_IN_POST_TYPES = array( 'post', 'page', 'attachment' );

	private const BUILT_IN_TAXONOMIES = array( 'category', 'post_tag', 'post_format' );

	/**
	 * @return \WP_Post_Type[] Keyed by post type slug.
	 */
	public function get_custom_post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// A type without single views has no public URLs to list.
		$post_types = array_filter( $post_types, 'is_post_type_viewable' );

		return array_diff_key( $post_types, array_flip( self::BUILT_IN_POST_TYPES ) );
	}

	/**
	 * @return \WP_Taxonomy[] Keyed by taxonomy slug.
	 */
	public function get_custom_taxonomies(): array {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		// A taxonomy without archive pages has no URL to list.
		$taxonomies = array_filter(
			$taxonomies,
			static function ( $taxonomy ): bool {
				return is_taxonomy_viewable( $taxonomy );
			}
		);

		return array_diff_key( $taxonomies, array_flip( self::BUILT_IN_TAXONOMIES ) );
	}
}
