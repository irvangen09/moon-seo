<?php

namespace Moon\SEO\Modules\Sitemap\Providers;

use Moon\SEO\Modules\Sitemap\Services\LastmodFormatter;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthorProvider implements ProviderInterface {

	public function count(): int {
		$query = $this->query(
			array(
				'number'      => 1,
				'count_total' => true,
			)
		);

		return (int) $query->get_total();
	}

	public function get_lastmod(): ?string {
		global $wpdb;

		$post_types   = array_values( get_post_types( array( 'public' => true ) ) );
		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Result is cached by SitemapCache; placeholders are generated.
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders})", $post_types ) );

		return LastmodFormatter::from_gmt( $value );
	}

	public function get_entries( int $offset, int $limit ): array {
		$query      = $this->query(
			array(
				'number' => $limit,
				'offset' => $offset,
			)
		);
		$author_ids = array_map( 'intval', $query->get_results() );
		$lastmods   = $this->get_author_lastmods( $author_ids );

		$entries = array();

		foreach ( $author_ids as $author_id ) {
			$entries[] = array(
				'loc'     => get_author_posts_url( $author_id ),
				'lastmod' => $lastmods[ $author_id ] ?? null,
			);
		}

		return $entries;
	}

	private function query( array $args ): WP_User_Query {
		return new WP_User_Query(
			array_merge(
				array(
					'has_published_posts' => true,
					'fields'              => 'ID',
					'orderby'             => 'ID',
					'order'               => 'ASC',
					'count_total'         => false,
				),
				$args
			)
		);
	}

	/**
	 * @param int[] $author_ids Authors on the requested page.
	 * @return array<int, string|null> Latest modification time per author.
	 */
	private function get_author_lastmods( array $author_ids ): array {
		global $wpdb;

		if ( empty( $author_ids ) ) {
			return array();
		}

		$post_types = array_values( get_post_types( array( 'public' => true ) ) );
		$type_marks = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$id_marks   = implode( ', ', array_fill( 0, count( $author_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Result is cached by SitemapCache; placeholders are generated.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_author, MAX(post_modified_gmt) AS lastmod FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$type_marks}) AND post_author IN ({$id_marks}) GROUP BY post_author", array_merge( $post_types, $author_ids ) ) );

		$lastmods = array();

		foreach ( (array) $rows as $row ) {
			$lastmods[ (int) $row->post_author ] = LastmodFormatter::from_gmt( $row->lastmod );
		}

		return $lastmods;
	}
}
