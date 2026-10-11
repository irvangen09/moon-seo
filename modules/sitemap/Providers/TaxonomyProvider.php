<?php

namespace Moon\SEO\Modules\Sitemap\Providers;

use Moon\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomyProvider implements ProviderInterface {

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	private string $taxonomy;

	public function __construct( OptionManager $option_manager, string $taxonomy ) {
		$this->option_manager = $option_manager;
		$this->taxonomy       = $taxonomy;
	}

	public function count(): int {
		$count = wp_count_terms( $this->base_args() );

		return is_wp_error( $count ) ? 0 : (int) $count;
	}

	/**
	 * Terms have no modification date of their own, and an inaccurate lastmod is worse than none.
	 */
	public function get_lastmod(): ?string {
		return null;
	}

	public function get_entries( int $offset, int $limit ): array {
		$terms = get_terms(
			array_merge(
				$this->base_args(),
				array(
					'number'  => $limit,
					'offset'  => $offset,
					'orderby' => 'term_id',
					'order'   => 'ASC',
				)
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$entries = array();

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$entries[] = array(
				'loc'     => $link,
				'lastmod' => null,
			);
		}

		return $entries;
	}

	private function base_args(): array {
		$args = array(
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => true,
		);

		// The setting is named "Excluded categories", so it applies to the category taxonomy only.
		if ( 'category' === $this->taxonomy ) {
			$excluded_items  = $this->option_manager->get_section( self::MODULE_SLUG, 'excluded_items' );
			$args['exclude'] = $excluded_items['excluded_categories'] ?? array();
		}

		return $args;
	}
}
