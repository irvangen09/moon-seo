<?php
/**
 * Removes all Moon SEO data when the plugin is deleted from the Plugins screen.
 *
 * Procedural on purpose: it must still work if the autoloader or any plugin
 * class fails to load.
 *
 * @package Moon\SEO
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function moon_seo_uninstall_cleanup_site() {
	global $wpdb;

	delete_option( 'moon_seo_general_settings' );
	delete_option( 'moon_seo_sitemap_settings' );
	delete_option( 'moon_seo_site_identity' );

	// Removed across every post type at once, without needing the supported list.
	foreach ( array( '_moon_seo_title', '_moon_seo_description', '_moon_seo_canonical', '_moon_seo_robots' ) as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}

	// Sitemap transients never expire and their names vary per content type,
	// so they are removed by prefix.
	foreach ( array( '_transient_moon_seo_sitemap_', '_transient_timeout_moon_seo_sitemap_' ) as $option_prefix ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk delete by prefix has no WordPress API.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $option_prefix ) . '%'
			)
		);
	}
}

if ( is_multisite() ) {
	// 'number' => 0 lifts the default limit of 100 sites.
	$moon_seo_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $moon_seo_site_ids as $moon_seo_site_id ) {
		switch_to_blog( $moon_seo_site_id );
		moon_seo_uninstall_cleanup_site();
		restore_current_blog();
	}
} else {
	moon_seo_uninstall_cleanup_site();
}
