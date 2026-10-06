<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RobotsUrl implements SectionInterface {

	private const SECTION_KEY = 'robots_url';

	// "index" and "follow" are crawler defaults and are never printed, so only the negative directives are valid.
	// Must match ROBOTS_DIRECTIVES in src/admin/components/robots-url-panel.js.
	private const ALLOWED_ROBOTS_DIRECTIVES = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );

	// "default" follows the Default Robots Meta. Must match ROBOTS_PRESET_OPTIONS in robots-url-panel.js.
	private const ALLOWED_ROBOTS_PRESETS = array( 'default', 'index_follow', 'noindex_follow', 'noindex_nofollow' );

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return array(
			'default_robots_meta'            => $this->sanitize_directives( $input['default_robots_meta'] ?? array() ),
			'archives_robots'                => $this->sanitize_preset( $input['archives_robots'] ?? '', 'default' ),
			'not_found_robots'               => $this->sanitize_preset( $input['not_found_robots'] ?? '', 'noindex_follow' ),
			'remove_category_base'           => ! empty( $input['remove_category_base'] ),
			'remove_tag_base'                => ! empty( $input['remove_tag_base'] ),
			'redirect_attachments_to_parent' => ! empty( $input['redirect_attachments_to_parent'] ),
		);
	}

	// An empty array is a valid choice (every directive unchecked), not invalid input.
	private function sanitize_directives( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_intersect( array_unique( array_filter( $value, 'is_string' ) ), self::ALLOWED_ROBOTS_DIRECTIVES ) );
	}

	private function sanitize_preset( $value, string $default_preset ): string {
		return is_string( $value ) && in_array( $value, self::ALLOWED_ROBOTS_PRESETS, true ) ? $value : $default_preset;
	}
}
