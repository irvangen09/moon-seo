<?php

namespace Moon\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LastmodFormatter {

	/**
	 * Converts a GMT MySQL datetime into ISO 8601; zero or invalid dates mean "unknown".
	 */
	public static function from_gmt( ?string $mysql_gmt ): ?string {
		if ( null === $mysql_gmt || '' === $mysql_gmt || str_starts_with( $mysql_gmt, '0000-00-00' ) ) {
			return null;
		}

		$timestamp = strtotime( $mysql_gmt . ' UTC' );

		return false === $timestamp ? null : gmdate( 'c', $timestamp );
	}
}
