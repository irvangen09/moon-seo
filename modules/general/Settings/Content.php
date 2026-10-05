<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Content implements SectionInterface {

	private const SECTION_KEY = 'content';

	private const TYPES_WITH_DESCRIPTION = array( 'homepage', 'post', 'page' );

	// Search and 404 pages have no content to generate a description from.
	// "not_found" stands for 404 because PHP casts a numeric array key to an integer.
	private const TYPES_TITLE_ONLY = array( 'search', 'not_found' );

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = array();

		foreach ( self::TYPES_WITH_DESCRIPTION as $type ) {
			$sanitized[ $type ] = $this->sanitize_with_description( $this->get_type_input( $input, $type ) );
		}

		foreach ( self::TYPES_TITLE_ONLY as $type ) {
			$sanitized[ $type ] = $this->sanitize_title_only( $this->get_type_input( $input, $type ) );
		}

		return $sanitized;
	}

	private function get_type_input( array $input, string $type ): array {
		return isset( $input[ $type ] ) && is_array( $input[ $type ] ) ? $input[ $type ] : array();
	}

	private function sanitize_with_description( array $raw ): array {
		return array(
			'seo_title'                 => isset( $raw['seo_title'] ) ? sanitize_text_field( $raw['seo_title'] ) : '',
			'meta_description'          => isset( $raw['meta_description'] ) ? sanitize_textarea_field( $raw['meta_description'] ) : '',
			'auto_generate_description' => ! empty( $raw['auto_generate_description'] ),
		);
	}

	private function sanitize_title_only( array $raw ): array {
		return array(
			'seo_title' => isset( $raw['seo_title'] ) ? sanitize_text_field( $raw['seo_title'] ) : '',
		);
	}
}