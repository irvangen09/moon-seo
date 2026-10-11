<?php

namespace Moon\SEO\Modules\Sitemap\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ProviderInterface {

	/**
	 * Total number of entries, so the index and the page count never need the entries themselves.
	 */
	public function count(): int;

	/**
	 * Most recent modification time (ISO 8601) across the entries, or null when it is not known.
	 */
	public function get_lastmod(): ?string;

	/**
	 * One slice of the entries in a stable order, so consecutive pages never overlap.
	 *
	 * @return array<int, array{loc: string, lastmod: string|null}>
	 */
	public function get_entries( int $offset, int $limit ): array;
}
