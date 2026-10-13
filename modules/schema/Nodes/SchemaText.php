<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Support\PostTitle;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Structured data carries plain text. WordPress titles and names arrive HTML-encoded (entities, allowed tags), which a JSON-LD consumer would show literally.
final class SchemaText {

	public static function plain( string $text ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	public static function post_title( WP_Post $post ): string {
		return self::plain( PostTitle::get( $post ) );
	}
}
