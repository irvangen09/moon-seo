<?php

namespace Moon\SEO\Modules\Sitemap\Settings;

use Moon\SEO\Modules\Sitemap\Services\ContentTypeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SitemapContent implements SectionInterface {

	private const SECTION_KEY = 'sitemap_content';

	private const DEFAULT_LINKS_PER_PAGE = 1000;

	// The sitemap protocol allows at most 50,000 URLs per file.
	private const MAX_LINKS_PER_PAGE = 50000;

	// Applied to any toggle that was never saved, so a fresh install already serves a useful sitemap.
	private const DEFAULT_TOGGLES = array(
		'include_homepage'      => true,
		'include_posts'         => true,
		'include_static_pages'  => true,
		'include_categories'    => true,
		'include_tag_pages'     => true,
		'include_archives'      => false,
		'include_author_pages'  => false,
		'include_last_modified' => true,
	);

	private ContentTypeRegistry $content_type_registry;

	public function __construct( ContentTypeRegistry $content_type_registry ) {
		$this->content_type_registry = $content_type_registry;
	}

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	/**
	 * Saved values laid over the defaults; the shape the frontend reads.
	 *
	 * @param array $saved The stored section, possibly empty or partial.
	 */
	public static function resolve( array $saved ): array {
		$resolved = array();

		foreach ( self::DEFAULT_TOGGLES as $key => $default ) {
			$resolved[ $key ] = array_key_exists( $key, $saved ) ? ! empty( $saved[ $key ] ) : $default;
		}

		$resolved['links_per_page']    = self::normalize_links_per_page( $saved['links_per_page'] ?? self::DEFAULT_LINKS_PER_PAGE );
		$resolved['custom_post_types'] = isset( $saved['custom_post_types'] ) && is_array( $saved['custom_post_types'] ) ? $saved['custom_post_types'] : array();
		$resolved['custom_taxonomies'] = isset( $saved['custom_taxonomies'] ) && is_array( $saved['custom_taxonomies'] ) ? $saved['custom_taxonomies'] : array();

		return $resolved;
	}

	public function sanitize( array $input ): array {
		$sanitized = self::resolve( $input );

		$sanitized['custom_post_types'] = $this->sanitize_custom_toggles(
			$sanitized['custom_post_types'],
			array_keys( $this->content_type_registry->get_custom_post_types() )
		);
		$sanitized['custom_taxonomies'] = $this->sanitize_custom_toggles(
			$sanitized['custom_taxonomies'],
			array_keys( $this->content_type_registry->get_custom_taxonomies() )
		);

		return $sanitized;
	}

	private static function normalize_links_per_page( $value ): int {
		$value = absint( $value );

		if ( 0 === $value ) {
			return self::DEFAULT_LINKS_PER_PAGE;
		}

		return min( $value, self::MAX_LINKS_PER_PAGE );
	}

	/**
	 * Only slugs registered right now are kept, so an arbitrary key never reaches the database.
	 *
	 * @param array    $value         Raw slug => bool map.
	 * @param string[] $allowed_slugs Slugs registered on this site.
	 * @return array<string, bool>
	 */
	private function sanitize_custom_toggles( array $value, array $allowed_slugs ): array {
		$result = array();

		foreach ( $allowed_slugs as $slug ) {
			$result[ $slug ] = ! empty( $value[ $slug ] );
		}

		return $result;
	}
}
