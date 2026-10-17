<?php

namespace Moon\SEO\Modules\Sitemap\Providers;

use Moon\SEO\Services\OptionManager;
use Moon\SEO\Support\PostMetaKeys;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostTypeProvider implements ProviderInterface {

	private const MODULE_SLUG = 'sitemap';

	// Posts hydrated into full objects at a time, so memory is bounded by this rather than by the page size.
	private const BATCH_SIZE = 500;

	private OptionManager $option_manager;

	private string $post_type;

	public function __construct( OptionManager $option_manager, string $post_type ) {
		$this->option_manager = $option_manager;
		$this->post_type      = $post_type;
	}

	public function count(): int {
		$query = new WP_Query(
			array_merge(
				$this->base_args(),
				array(
					'posts_per_page' => 1,
					'no_found_rows'  => false,
				)
			)
		);

		return (int) $query->found_posts;
	}

	public function get_lastmod(): ?string {
		$query = new WP_Query(
			array_merge(
				$this->base_args(),
				array(
					'posts_per_page' => 1,
					'no_found_rows'  => true,
					'orderby'        => array(
						'modified' => 'DESC',
						'ID'       => 'DESC',
					),
				)
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		$time = get_post_modified_time( 'c', true, (int) $query->posts[0] );

		return false !== $time ? $time : null;
	}

	public function get_entries( int $offset, int $limit ): array {
		// Date alone is not a total order: posts imported together share a post_date, and offset paging would then repeat or skip them.
		$query = new WP_Query(
			array_merge(
				$this->base_args(),
				array(
					'posts_per_page' => $limit,
					'offset'         => $offset,
					'no_found_rows'  => true,
					'orderby'        => array(
						'date' => 'DESC',
						'ID'   => 'DESC',
					),
				)
			)
		);

		$entries = array();

		foreach ( array_chunk( $query->posts, self::BATCH_SIZE ) as $batch_ids ) {
			$batch_posts = get_posts(
				array(
					'post__in'               => $batch_ids,
					'post_type'              => $this->post_type,
					'post_status'            => 'publish',
					'orderby'                => 'post__in',
					'posts_per_page'         => count( $batch_ids ),
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					// Permalinks built from the category read the post's terms; one query for the batch instead of one per post.
					'update_post_term_cache' => true,
				)
			);

			foreach ( $batch_posts as $post ) {
				$lastmod = get_post_modified_time( 'c', true, $post );

				$entries[] = array(
					'loc'     => get_permalink( $post ),
					'lastmod' => false !== $lastmod ? $lastmod : null,
				);
			}
		}

		return $entries;
	}

	private function base_args(): array {
		$excluded_items = $this->option_manager->get_section( self::MODULE_SLUG, 'excluded_items' );

		return array(
			'post_type'              => $this->post_type,
			'post_status'            => 'publish',
			// A password-protected post is a public URL with no public content.
			'has_password'           => false,
			'post__not_in'           => $excluded_items['excluded_posts'] ?? array(),
			// The override is a serialized list, so the directive is matched with its quotes; posts without an override pass.
			'meta_query'             => array(
				'relation' => 'OR',
				array(
					'key'     => PostMetaKeys::ROBOTS,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => PostMetaKeys::ROBOTS,
					'value'   => '"noindex"',
					'compare' => 'NOT LIKE',
				),
			),
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
	}
}
