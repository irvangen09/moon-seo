<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Verification implements SectionInterface {

	private const SECTION_KEY = 'verification';

	private const PLATFORMS = array( 'google', 'bing', 'yandex' );

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = array();

		foreach ( self::PLATFORMS as $platform ) {
			$sanitized[ $platform ] = isset( $input[ $platform ] ) ? sanitize_text_field( $input[ $platform ] ) : '';
		}

		return $sanitized;
	}
}