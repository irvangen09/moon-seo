<?php

namespace Moon\SEO\Modules\General\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Requires $placeholder_resolver, $description_resolver and $site_identity on the consuming class.
trait SocialMetaTrait {

	// Open Graph tags use "property", Twitter Card tags use "name".
	abstract protected function meta_attribute(): string;

	private function output_tag( string $key, string $value ): void {
		if ( '' === $value ) {
			return;
		}

		printf(
			'<meta %s="%s" content="%s" />' . "\n",
			esc_attr( $this->meta_attribute() ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}

	private function output_url_tag( string $key, string $url ): void {
		if ( '' === $url ) {
			return;
		}

		printf(
			'<meta %s="%s" content="%s" />' . "\n",
			esc_attr( $this->meta_attribute() ),
			esc_attr( $key ),
			esc_url( $url )
		);
	}

	private function resolve_title(): string {
		if ( is_front_page() ) {
			return $this->placeholder_resolver->get_homepage_title();
		}

		if ( is_singular() ) {
			return get_the_title();
		}

		if ( is_category() || is_tag() ) {
			return single_term_title( '', false );
		}

		return '';
	}

	// Order: the post's featured image, this platform's default image, then the site image.
	private function resolve_image_url( int $default_image_id ): string {
		if ( is_singular() && has_post_thumbnail() ) {
			$url = get_the_post_thumbnail_url( null, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		foreach ( array( $default_image_id, $this->site_identity->get_site_image_id() ) as $image_id ) {
			if ( $image_id > 0 ) {
				$url = wp_get_attachment_image_url( $image_id, 'full' );

				if ( false !== $url ) {
					return $url;
				}
			}
		}

		return '';
	}
}
