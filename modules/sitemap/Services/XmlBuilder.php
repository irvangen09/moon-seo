<?php

namespace Moon\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class XmlBuilder {

	/**
	 * @param array<int, array{loc: string, lastmod?: string|null}> $entries Entries of one sitemap page.
	 * @param bool                                                   $include_last_modified Whether <lastmod> is printed.
	 */
	public function build_urlset( array $entries, bool $include_last_modified ): string {
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

		foreach ( $entries as $entry ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( $entry['loc'] ) . "</loc>\n";

			if ( $include_last_modified && ! empty( $entry['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
			}

			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * @param array<int, array{loc: string, lastmod?: string|null}> $sitemaps Child sitemaps listed by the index.
	 */
	public function build_sitemap_index( array $sitemaps ): string {
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">' . "\n";

		foreach ( $sitemaps as $sitemap ) {
			$xml .= "\t<sitemap>\n";
			$xml .= "\t\t<loc>" . esc_url( $sitemap['loc'] ) . "</loc>\n";

			if ( ! empty( $sitemap['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . esc_html( $sitemap['lastmod'] ) . "</lastmod>\n";
			}

			$xml .= "\t</sitemap>\n";
		}

		$xml .= '</sitemapindex>';

		return $xml;
	}
}
