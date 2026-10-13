<?php

namespace Moon\SEO\Support;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The title as WordPress prints it, so the "the_title" filters of other code still apply, minus the "Protected:" and "Private:" prefixes WordPress adds to such posts.
final class PostTitle {

	/**
	 * @param int|WP_Post $post The post or its ID.
	 */
	public static function get( int|WP_Post $post ): string {
		$keep_title = static function (): string {
			return '%s';
		};

		add_filter( 'protected_title_format', $keep_title, PHP_INT_MAX );
		add_filter( 'private_title_format', $keep_title, PHP_INT_MAX );

		$title = get_the_title( $post );

		remove_filter( 'protected_title_format', $keep_title, PHP_INT_MAX );
		remove_filter( 'private_title_format', $keep_title, PHP_INT_MAX );

		return $title;
	}
}
