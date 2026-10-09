<?php

namespace Moon\SEO\Modules\General\Services;

use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleResolver {

	private const MODULE_SLUG = 'general';

	private const DEFAULT_TEMPLATE = '{title} {separator} {site_name}';

	private PlaceholderResolver $placeholder_resolver;

	private OptionManager $option_manager;

	private SupportedPostTypes $supported_post_types;

	public function __construct( PlaceholderResolver $placeholder_resolver, OptionManager $option_manager, SupportedPostTypes $supported_post_types ) {
		$this->placeholder_resolver = $placeholder_resolver;
		$this->option_manager       = $option_manager;
		$this->supported_post_types = $supported_post_types;
	}

	// Contexts without a {title} value (search, 404, term archives) must pass their own fallback template.
	public function resolve( string $template, array $context_values = array(), string $fallback_template = '' ): string {
		if ( '' === trim( $template ) ) {
			$template = '' !== $fallback_template ? $fallback_template : self::DEFAULT_TEMPLATE;
		}

		return $this->placeholder_resolver->resolve( $template, $context_values );
	}

	/**
	 * The title of a single post of a supported post type.
	 *
	 * Used for the <title> tag and for the editor preview, so both always agree.
	 *
	 * @param WP_Post $post       The post.
	 * @param string  $override   The per-post SEO Title, empty when not set.
	 * @param string  $post_title The post title as WordPress prints it.
	 */
	public function resolve_for_post( WP_Post $post, string $override, string $post_title ): string {
		$override = trim( $override );

		if ( '' !== $override ) {
			return $this->resolve( $override, array( 'title' => $post_title ) );
		}

		// A static front page uses the homepage template unless its own override is set.
		if ( $this->placeholder_resolver->is_front_page_post( $post ) && ! is_paged() ) {
			return $this->resolve_homepage();
		}

		$content_group = $this->supported_post_types->content_group( $post->post_type );

		if ( null === $content_group ) {
			return '';
		}

		return $this->resolve( $this->get_template( 'content', $content_group ), array( 'title' => $post_title ) );
	}

	public function resolve_homepage(): string {
		return $this->resolve(
			$this->get_template( 'content', 'homepage' ),
			array( 'title' => $this->placeholder_resolver->get_homepage_title() ),
			// The generic fallback would print the site name twice on a blog-index homepage.
			'{site_name} {separator} {tagline}'
		);
	}

	public function get_template( string $section, string $type ): string {
		$data = $this->option_manager->get_section( self::MODULE_SLUG, $section );

		return isset( $data[ $type ]['seo_title'] ) ? (string) $data[ $type ]['seo_title'] : '';
	}
}
