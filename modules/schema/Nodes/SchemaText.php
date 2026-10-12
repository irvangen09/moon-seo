<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Structured data carries plain text. WordPress titles and names arrive HTML-encoded (entities, allowed tags), which a JSON-LD consumer would show literally.
final class SchemaText {

	public static function plain( string $text ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	// The title still passes through the "the_title" filters; only the "Protected:" and "Private:" prefixes are dropped.
	public static function post_title( WP_Post $post ): string {
		$keep_title = static function (): string {
			return '%s';
		};

		add_filter( 'protected_title_format', $keep_title, PHP_INT_MAX );
		add_filter( 'private_title_format', $keep_title, PHP_INT_MAX );

		$title = get_the_title( $post );

		remove_filter( 'protected_title_format', $keep_title, PHP_INT_MAX );
		remove_filter( 'private_title_format', $keep_title, PHP_INT_MAX );

		return self::plain( $title );
	}
}
