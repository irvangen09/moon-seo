<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CategoriesTags implements SectionInterface {

	private const SECTION_KEY = 'categories_tags';

	private const TAXONOMY_TYPES = array( 'categories', 'tags' );

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = array();

		foreach ( self::TAXONOMY_TYPES as $type ) {
			$raw                = isset( $input[ $type ] ) && is_array( $input[ $type ] ) ? $input[ $type ] : array();
			$sanitized[ $type ] = $this->sanitize_taxonomy( $raw );
		}

		return $sanitized;
	}

	private function sanitize_taxonomy( array $raw ): array {
		return array(
			// Archives are shown unless explicitly switched off, so a payload that omits the key must not turn them off.
			'show_in_search_results'    => array_key_exists( 'show_in_search_results', $raw ) ? ! empty( $raw['show_in_search_results'] ) : true,
			'seo_title'                 => isset( $raw['seo_title'] ) ? sanitize_text_field( $raw['seo_title'] ) : '',
			'meta_description'          => isset( $raw['meta_description'] ) ? sanitize_textarea_field( $raw['meta_description'] ) : '',
			'auto_generate_description' => ! empty( $raw['auto_generate_description'] ),
		);
	}
}