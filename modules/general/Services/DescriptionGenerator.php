<?php

namespace Moon\SEO\Modules\General\Services;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DescriptionGenerator {

	private const MAX_LENGTH = 160;

	/**
	 * Descriptions already built during this request, keyed by post ID.
	 * The instance is shared by every output that needs a description.
	 *
	 * @var array<int, string>
	 */
	private array $cache = array();

	public function generate( WP_Post $post ): string {
		if ( ! isset( $this->cache[ $post->ID ] ) ) {
			$this->cache[ $post->ID ] = $this->build( $post );
		}

		return $this->cache[ $post->ID ];
	}

	private function build( WP_Post $post ): string {
		// Visitors who have not entered the password must not see any of the content.
		if ( post_password_required( $post ) ) {
			return '';
		}

		$description = $this->get_manual_excerpt( $post );

		if ( '' === $description ) {
			$description = $this->get_first_paragraph( $post );
		}

		return $this->trim_to_length( $description, self::MAX_LENGTH );
	}

	private function get_manual_excerpt( WP_Post $post ): string {
		if ( ! has_excerpt( $post ) ) {
			return '';
		}

		return trim( wp_strip_all_tags( $post->post_excerpt ) );
	}

	// Headings and figures (images with captions) are not body text, so they are removed before the first line is taken.
	private function get_first_paragraph( WP_Post $post ): string {
		$content = strip_shortcodes( $post->post_content );
		$content = (string) preg_replace( '#<(h[1-6]|figure)\b[^>]*>.*?</\1>#is', '', $content );
		$content = (string) preg_replace( '#<br\s*/?>#i', ' ', $content );
		$content = trim( wp_strip_all_tags( $content ) );

		foreach ( preg_split( '/\r\n|\r|\n/', $content ) as $line ) {
			$line = trim( (string) preg_replace( '/\s+/', ' ', $line ) );

			if ( '' !== $line ) {
				return $line;
			}
		}

		return '';
	}

	// Cuts at the last space so a word is never split in half.
	private function trim_to_length( string $text, int $max_length ): string {
		if ( mb_strlen( $text, 'UTF-8' ) <= $max_length ) {
			return $text;
		}

		$trimmed    = mb_substr( $text, 0, $max_length, 'UTF-8' );
		$last_space = strrpos( $trimmed, ' ' );

		if ( false !== $last_space ) {
			$trimmed = substr( $trimmed, 0, $last_space );
		}

		return rtrim( $trimmed ) . '…';
	}
}
