<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteInfo implements SectionInterface {

	private const SECTION_KEY = 'site_info';

	private const DEFAULT_SEPARATOR = '|';

	// Must match SEPARATORS in src/admin/components/title-separator-picker.js.
	private const ALLOWED_SEPARATORS = array( '|', '-', '—', ':', '.', '•', '*', '~', '«', '»', '/', '\\', '>', '<' );

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return array(
			'website_name'           => isset( $input['website_name'] ) ? sanitize_text_field( $input['website_name'] ) : '',
			'alternate_website_name' => isset( $input['alternate_website_name'] ) ? sanitize_text_field( $input['alternate_website_name'] ) : '',
			'title_separator'        => $this->sanitize_separator( $input['title_separator'] ?? '' ),
			'site_image_id'          => isset( $input['site_image_id'] ) ? absint( $input['site_image_id'] ) : 0,
		);
	}

	private function sanitize_separator( $value ): string {
		return is_string( $value ) && in_array( $value, self::ALLOWED_SEPARATORS, true ) ? $value : self::DEFAULT_SEPARATOR;
	}
}