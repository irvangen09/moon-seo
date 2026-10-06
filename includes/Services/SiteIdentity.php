<?php

namespace Moon\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteIdentity {

	private const OPTION_KEY = 'moon_seo_site_identity';

	public function get_website_name(): string {
		return (string) ( $this->get_data()['website_name'] ?? '' );
	}

	/**
	 * Falls back to the WordPress site title when no website name is saved,
	 * so consumers need no fallback of their own.
	 */
	public function get_effective_website_name(): string {
		$name = $this->get_website_name();

		return '' !== $name ? $name : get_bloginfo( 'name' );
	}

	public function get_alternate_website_name(): string {
		return (string) ( $this->get_data()['alternate_website_name'] ?? '' );
	}

	public function get_site_image_id(): int {
		return (int) ( $this->get_data()['site_image_id'] ?? 0 );
	}

	public function set( array $data ): bool {
		// Autoloaded: the option is read on every frontend request.
		return update_option( self::OPTION_KEY, array_merge( $this->get_data(), $data ), true );
	}

	private function get_data(): array {
		$data = get_option( self::OPTION_KEY, array() );

		return is_array( $data ) ? $data : array();
	}
}
