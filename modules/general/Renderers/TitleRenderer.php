<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Modules\General\PostMetaKeys;
use Moon\SEO\Modules\General\Services\TitleResolver;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleRenderer implements RendererInterface {

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private TitleResolver $title_resolver;

	private SupportedPostTypes $supported_post_types;

	public function __construct( OptionManager $option_manager, TitleResolver $title_resolver, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->title_resolver       = $title_resolver;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		// A filter, not an echo in wp_head, so the theme and WordPress keep control of the <title> tag.
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ), 15 );
	}

	public function filter_title( string $title ): string {
		list( $template, $context_values, $fallback_template ) = $this->resolve_context();

		if ( null === $template ) {
			return $title;
		}

		$resolved = $this->title_resolver->resolve( $template, $context_values, $fallback_template );

		// A non-empty value skips core's own escaping and is printed as is, so it is escaped here.
		return '' !== $resolved ? esc_html( $resolved ) : $title;
	}

	// Returns the template, its context values and a fallback template for contexts that have no {title}.
	private function resolve_context(): array {
		$content_group = is_singular() ? $this->supported_post_types->content_group( (string) get_post_type() ) : null;

		// Checked before the homepage so a static front page honours its own override.
		if ( null !== $content_group ) {
			$override = $this->get_override();

			if ( '' !== $override ) {
				return array( $override, array( 'title' => get_the_title() ), '' );
			}
		}

		if ( is_front_page() && ! is_paged() ) {
			return array(
				$this->get_field( 'content', 'homepage', 'seo_title' ),
				array( 'title' => $this->title_resolver->get_homepage_title() ),
				// The generic fallback would print the site name twice on a blog-index homepage.
				'{site_name} {separator} {tagline}',
			);
		}

		if ( null !== $content_group ) {
			return array( $this->get_field( 'content', $content_group, 'seo_title' ), array( 'title' => get_the_title() ), '' );
		}

		if ( is_search() ) {
			return array(
				$this->get_field( 'content', 'search', 'seo_title' ),
				array( 'query' => get_search_query() ),
				__( 'Search results for {query}', 'moon-seo' ) . ' {separator} {site_name}',
			);
		}

		if ( is_404() ) {
			return array(
				$this->get_field( 'content', 'not_found', 'seo_title' ),
				array(),
				__( 'Page not found', 'moon-seo' ) . ' {separator} {site_name}',
			);
		}

		if ( is_category() || is_tag() ) {
			return array(
				$this->get_field( 'categories_tags', is_category() ? 'categories' : 'tags', 'seo_title' ),
				array( 'term_title' => single_term_title( '', false ) ),
				'{term_title} {separator} {site_name}',
			);
		}

		return array( null, array(), '' );
	}

	private function get_override(): string {
		$value = get_post_meta( get_queried_object_id(), PostMetaKeys::TITLE, true );

		return is_string( $value ) ? trim( $value ) : '';
	}

	private function get_field( string $section, string $type, string $field ): string {
		$data = $this->option_manager->get_section( self::MODULE_SLUG, $section );

		return isset( $data[ $type ][ $field ] ) ? (string) $data[ $type ][ $field ] : '';
	}
}
