<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Modules\General\PostMetaKeys;
use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaRenderer implements RendererInterface {

	private const MODULE_SLUG = 'general';

	// "index" and "follow" are crawler defaults, so only negative directives are ever printed.
	private const NEGATIVE_DIRECTIVES = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );

	private const ROBOTS_PRESET_MAP = array(
		'index_follow'     => array(),
		'noindex_follow'   => array( 'noindex' ),
		'noindex_nofollow' => array( 'noindex', 'nofollow' ),
	);

	private OptionManager $option_manager;

	private DescriptionResolver $description_resolver;

	public function __construct( OptionManager $option_manager, DescriptionResolver $description_resolver ) {
		$this->option_manager       = $option_manager;
		$this->description_resolver = $description_resolver;
	}

	public function init(): void {
		// This Renderer prints the canonical itself; the core tag would be a second, possibly different, one.
		remove_action( 'wp_head', 'rel_canonical' );

		add_action( 'wp_head', array( $this, 'output' ), 2 );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ) );
	}

	public function output(): void {
		$description = $this->description_resolver->resolve();

		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}

		$canonical = $this->resolve_canonical_url();

		if ( '' !== $canonical ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
		}
	}

	// Merges into the core robots tag instead of printing a second one.
	public function filter_robots( array $robots ): array {
		// "Discourage search engines" always wins, so a staging site cannot be made indexable by a per-page setting.
		if ( '0' === get_option( 'blog_public' ) ) {
			return $robots;
		}

		$directives = $this->resolve_robots_directives();

		foreach ( self::NEGATIVE_DIRECTIVES as $directive ) {
			$robots[ $directive ] = in_array( $directive, $directives, true );
		}

		// Core adds "follow" to noindexed pages; it must not sit next to "nofollow".
		if ( $robots['nofollow'] ) {
			unset( $robots['follow'] );
		}

		return $robots;
	}

	private function resolve_robots_directives(): array {
		if ( is_singular() ) {
			$override = get_post_meta( get_queried_object_id(), PostMetaKeys::ROBOTS, true );

			if ( is_array( $override ) && ! empty( $override ) ) {
				return $override;
			}
		}

		$settings     = $this->option_manager->get_section( self::MODULE_SLUG, 'robots_url' );
		$default_meta = isset( $settings['default_robots_meta'] ) && is_array( $settings['default_robots_meta'] ) ? $settings['default_robots_meta'] : array();

		if ( is_404() ) {
			return $this->resolve_preset( $settings['not_found_robots'] ?? 'noindex_follow', $default_meta );
		}

		if ( is_category() || is_tag() ) {
			$directives = $this->resolve_preset( $settings['archives_robots'] ?? 'default', $default_meta );

			// "Show in search results" is more specific than the archives preset, so turning it off forces noindex.
			if ( ! $this->is_shown_in_search_results( is_category() ? 'categories' : 'tags' ) ) {
				$directives[] = 'noindex';
			}

			return array_values( array_unique( $directives ) );
		}

		if ( is_search() ) {
			$preset     = $settings['archives_robots'] ?? 'default';
			$directives = $this->resolve_preset( $preset, $default_meta );

			// WordPress noindexes search results; only an explicit preset overrides that.
			if ( 'default' === $preset ) {
				$directives[] = 'noindex';
			}

			return array_values( array_unique( $directives ) );
		}

		if ( is_archive() ) {
			return $this->resolve_preset( $settings['archives_robots'] ?? 'default', $default_meta );
		}

		return $default_meta;
	}

	// A setting that was never saved counts as shown, so archives are not noindexed by accident.
	private function is_shown_in_search_results( string $taxonomy_type ): bool {
		$section = $this->option_manager->get_section( self::MODULE_SLUG, 'categories_tags' );
		$data    = isset( $section[ $taxonomy_type ] ) && is_array( $section[ $taxonomy_type ] ) ? $section[ $taxonomy_type ] : array();

		return array_key_exists( 'show_in_search_results', $data ) ? (bool) $data['show_in_search_results'] : true;
	}

	private function resolve_preset( string $preset, array $default_meta ): array {
		if ( 'default' === $preset ) {
			return $default_meta;
		}

		return self::ROBOTS_PRESET_MAP[ $preset ] ?? $default_meta;
	}

	// Search and 404 get no canonical.
	private function resolve_canonical_url(): string {
		if ( is_singular() ) {
			$override = get_post_meta( get_queried_object_id(), PostMetaKeys::CANONICAL, true );

			if ( is_string( $override ) && '' !== trim( $override ) ) {
				return trim( $override );
			}
		}

		if ( is_front_page() && ! is_paged() ) {
			return home_url( '/' );
		}

		if ( is_singular() ) {
			// Same function core uses, so a paged post keeps its page number.
			$url = wp_get_canonical_url();

			return false !== $url ? $url : '';
		}

		if ( is_category() || is_tag() ) {
			$term_link = get_term_link( get_queried_object() );

			return is_wp_error( $term_link ) ? '' : $term_link;
		}

		return '';
	}
}
