<?php

namespace Moon\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionManager {

	private const OPTION_PREFIX = 'moon_seo_';

	/**
	 * Option data read or written during this request, keyed by module slug.
	 *
	 * @var array<string, array>
	 */
	private array $cache = array();

	public function get_option_name( string $module_slug ): string {
		return self::OPTION_PREFIX . $module_slug . '_settings';
	}

	public function get_all( string $module_slug ): array {
		if ( isset( $this->cache[ $module_slug ] ) ) {
			return $this->cache[ $module_slug ];
		}

		$data = get_option( $this->get_option_name( $module_slug ), array() );

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$this->cache[ $module_slug ] = $data;

		return $data;
	}

	public function get_section( string $module_slug, string $section ): array {
		$all = $this->get_all( $module_slug );

		return isset( $all[ $section ] ) && is_array( $all[ $section ] ) ? $all[ $section ] : array();
	}

	public function update_all( string $module_slug, array $data ): bool {
		// Autoloaded: the option is read on every frontend request.
		$result = update_option( $this->get_option_name( $module_slug ), $data, true );

		// Cached only after a successful write, so the cache never gets ahead of the database.
		if ( $result ) {
			$this->cache[ $module_slug ] = $data;
		}

		return $result;
	}
}