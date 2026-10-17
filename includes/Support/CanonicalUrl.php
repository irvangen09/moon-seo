<?php

namespace Moon\SEO\Support;

use WP_Post;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The one answer to "which URL does this page ask search engines to index", shared by the head tags, the social tags and Schema.
final class CanonicalUrl {

	// The per-post override, or an empty string when the post has none.
	public static function override_for_post( int $post_id ): string {
		$override = get_post_meta( $post_id, PostMetaKeys::CANONICAL, true );

		return is_string( $override ) ? trim( $override ) : '';
	}

	// Core's own canonical keeps the page number of a paged post, but only for the queried post.
	public static function for_post( WP_Post $post ): string {
		$override = self::override_for_post( $post->ID );

		if ( '' !== $override ) {
			return $override;
		}

		$url = wp_get_canonical_url( $post );

		return false !== $url ? $url : '';
	}

	// Page two of an archive is its own page, so it points at itself and not at page one.
	public static function for_term( WP_Term $term, int $paged = 1 ): string {
		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			return '';
		}

		if ( $paged < 2 ) {
			return $link;
		}

		global $wp_rewrite;

		if ( $wp_rewrite->using_permalinks() ) {
			return trailingslashit( $link ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $paged, 'paged' );
		}

		return add_query_arg( 'paged', $paged, $link );
	}

	// Search, 404 and the other archives get none.
	public static function for_request(): string {
		$queried = get_queried_object();

		if ( is_singular() && $queried instanceof WP_Post ) {
			$override = self::override_for_post( $queried->ID );

			if ( '' !== $override ) {
				return $override;
			}
		}

		if ( is_front_page() && ! is_paged() ) {
			return home_url( '/' );
		}

		if ( is_singular() && $queried instanceof WP_Post ) {
			return self::for_post( $queried );
		}

		if ( ( is_category() || is_tag() ) && $queried instanceof WP_Term ) {
			return self::for_term( $queried, max( 1, (int) get_query_var( 'paged' ) ) );
		}

		return '';
	}
}
