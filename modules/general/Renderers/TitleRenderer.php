<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Modules\General\PostMetaKeys;
use Moon\SEO\Modules\General\Services\TitleResolver;
use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleRenderer implements RendererInterface {

	private TitleResolver $title_resolver;

	private SupportedPostTypes $supported_post_types;

	public function __construct( TitleResolver $title_resolver, SupportedPostTypes $supported_post_types ) {
		$this->title_resolver       = $title_resolver;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		// A filter, not an echo in wp_head, so the theme and WordPress keep control of the <title> tag.
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ), 15 );
	}

	public function filter_title( string $title ): string {
		$resolved = $this->resolve();

		// A non-empty value skips core's own escaping and is printed as is, so it is escaped here.
		return '' !== $resolved ? esc_html( $resolved ) : $title;
	}

	// An empty string leaves the title to WordPress.
	private function resolve(): string {
		$post = is_singular() ? get_queried_object() : null;

		// Checked before the homepage so a static front page honours its own override.
		if ( $post instanceof WP_Post && null !== $this->supported_post_types->content_group( $post->post_type ) ) {
			return $this->title_resolver->resolve_for_post( $post, $this->get_override( $post->ID ), get_the_title( $post ) );
		}

		if ( is_front_page() && ! is_paged() ) {
			return $this->title_resolver->resolve_homepage();
		}

		if ( is_search() ) {
			return $this->title_resolver->resolve(
				$this->title_resolver->get_template( 'content', 'search' ),
				array( 'query' => get_search_query() ),
				__( 'Search results for {query}', 'moon-seo' ) . ' {separator} {site_name}'
			);
		}

		if ( is_404() ) {
			return $this->title_resolver->resolve(
				$this->title_resolver->get_template( 'content', 'not_found' ),
				array(),
				__( 'Page not found', 'moon-seo' ) . ' {separator} {site_name}'
			);
		}

		if ( is_category() || is_tag() ) {
			return $this->title_resolver->resolve(
				$this->title_resolver->get_template( 'categories_tags', is_category() ? 'categories' : 'tags' ),
				array( 'term_title' => single_term_title( '', false ) ),
				'{term_title} {separator} {site_name}'
			);
		}

		return '';
	}

	private function get_override( int $post_id ): string {
		$value = get_post_meta( $post_id, PostMetaKeys::TITLE, true );

		return is_string( $value ) ? trim( $value ) : '';
	}
}
