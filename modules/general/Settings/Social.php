<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Social implements SectionInterface {

	private const SECTION_KEY = 'social';

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return array(
			'open_graph'   => $this->sanitize_platform( $input['open_graph'] ?? array() ),
			'twitter_card' => $this->sanitize_platform( $input['twitter_card'] ?? array() ),
		);
	}

	// Open Graph and Twitter Card share the same shape.
	private function sanitize_platform( $raw ): array {
		$raw = is_array( $raw ) ? $raw : array();

		return array(
			'enabled'  => ! empty( $raw['enabled'] ),
			'image_id' => isset( $raw['image_id'] ) ? absint( $raw['image_id'] ) : 0,
		);
	}
}
