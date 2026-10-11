<?php

namespace Moon\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExcludedItems implements SectionInterface {

	private const SECTION_KEY = 'excluded_items';

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return array(
			'excluded_categories' => $this->sanitize_id_list( $input['excluded_categories'] ?? array() ),
			'excluded_posts'      => $this->sanitize_id_list( $input['excluded_posts'] ?? array() ),
		);
	}

	/**
	 * Accepts an array of IDs or a comma-separated string such as "110,121".
	 *
	 * @param mixed $value Raw list.
	 * @return int[]
	 */
	private function sanitize_id_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array_filter( array_map( 'absint', $value ) );

		return array_values( array_unique( $ids ) );
	}
}
