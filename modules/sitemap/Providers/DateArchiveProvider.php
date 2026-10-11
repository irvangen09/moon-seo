<?php

namespace Moon\SEO\Modules\Sitemap\Providers;

use Moon\SEO\Modules\Sitemap\Services\LastmodFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One entry per month that has a published post, matching WordPress's /YYYY/MM/ archives of the post type "post".
 */
final class DateArchiveProvider implements ProviderInterface {

	public function count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- No WordPress API lists distinct months; the result is cached by SitemapCache.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM ( SELECT 1 FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'post' GROUP BY YEAR(post_date), MONTH(post_date) ) AS months" );
	}

	public function get_lastmod(): ?string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The result is cached by SitemapCache.
		return LastmodFormatter::from_gmt( $wpdb->get_var( "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'post'" ) );
	}

	public function get_entries( int $offset, int $limit ): array {
		global $wpdb;

		// GROUP BY (not DISTINCT) keeps the query valid under ONLY_FULL_GROUP_BY and yields the real lastmod of each month.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The result is cached by SitemapCache.
		$months = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT YEAR(post_date) AS year, MONTH(post_date) AS month, MAX(post_modified_gmt) AS lastmod
				FROM {$wpdb->posts}
				WHERE post_status = 'publish' AND post_type = 'post'
				GROUP BY YEAR(post_date), MONTH(post_date)
				ORDER BY year DESC, month DESC
				LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		$entries = array();

		foreach ( (array) $months as $month ) {
			$entries[] = array(
				'loc'     => get_month_link( (int) $month->year, (int) $month->month ),
				'lastmod' => LastmodFormatter::from_gmt( $month->lastmod ),
			);
		}

		return $entries;
	}
}
