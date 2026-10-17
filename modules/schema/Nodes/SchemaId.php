<?php

namespace Moon\SEO\Modules\Schema\Nodes;

use Moon\SEO\Support\CanonicalUrl;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The only place an "@id" is built, because nodes reference each other by it.
final class SchemaId {

	public static function website(): string {
		return home_url( '/' ) . '#website';
	}

	public static function organization(): string {
		return home_url( '/' ) . '#organization';
	}

	public static function breadcrumb( string $permalink ): string {
		return $permalink . '#breadcrumb';
	}

	public static function article( string $permalink ): string {
		return $permalink . '#article';
	}

	public static function webpage( string $permalink ): string {
		return $permalink . '#webpage';
	}

	public static function primary_image( string $permalink ): string {
		return $permalink . '#primaryimage';
	}

	// A post that names another URL as canonical is described under that URL.
	public static function permalink( WP_Post $post ): string {
		$override = CanonicalUrl::override_for_post( $post->ID );

		if ( '' !== $override ) {
			return $override;
		}

		$permalink = get_permalink( $post );

		return false !== $permalink ? $permalink : home_url( '/' );
	}
}
