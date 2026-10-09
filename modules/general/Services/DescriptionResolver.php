<?php

namespace Moon\SEO\Modules\General\Services;

use Moon\SEO\Modules\General\PostMetaKeys;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Resolves the description of the current request once, so the meta, Open Graph and Twitter tags always agree.
final class DescriptionResolver {

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private PlaceholderResolver $placeholder_resolver;

	private DescriptionGenerator $description_generator;

	private SupportedPostTypes $supported_post_types;

	public function __construct(
		OptionManager $option_manager,
		PlaceholderResolver $placeholder_resolver,
		DescriptionGenerator $description_generator,
		SupportedPostTypes $supported_post_types
	) {
		$this->option_manager        = $option_manager;
		$this->placeholder_resolver  = $placeholder_resolver;
		$this->description_generator = $description_generator;
		$this->supported_post_types  = $supported_post_types;
	}

	// Order: per-post override, then the template and auto-generation of the content type in Settings.
	public function resolve(): string {
		$post = is_singular() ? get_queried_object() : null;
		$post = $post instanceof WP_Post ? $post : null;

		// Checked before the homepage so a static front page honours its own override.
		if ( null !== $post && null !== $this->supported_post_types->content_group( $post->post_type ) ) {
			return $this->resolve_for_post( $post, $this->get_override( $post->ID ), get_the_title( $post ) );
		}

		if ( is_front_page() && ! is_paged() ) {
			return $this->resolve_homepage( $post );
		}

		if ( is_category() || is_tag() ) {
			return $this->resolve_taxonomy( is_category() ? 'categories' : 'tags', array( 'term_title' => single_term_title( '', false ) ) );
		}

		// Search and 404 have no description field in Settings.
		return '';
	}

	/**
	 * The description of a single post of a supported post type.
	 *
	 * Used for the meta tags and for the editor preview, so both always agree.
	 *
	 * @param WP_Post $post       The post.
	 * @param string  $override   The per-post Meta Description, empty when not set.
	 * @param string  $post_title The post title as WordPress prints it.
	 */
	public function resolve_for_post( WP_Post $post, string $override, string $post_title ): string {
		$override = trim( $override );

		if ( '' !== $override ) {
			return $this->placeholder_resolver->resolve( $override, array( 'title' => $post_title ) );
		}

		if ( $this->placeholder_resolver->is_front_page_post( $post ) && ! is_paged() ) {
			return $this->resolve_homepage( $post );
		}

		$content_group = $this->supported_post_types->content_group( $post->post_type );

		return null !== $content_group ? $this->resolve_content( $content_group, array( 'title' => $post_title ), $post ) : '';
	}

	private function resolve_homepage( ?WP_Post $post ): string {
		return $this->resolve_content( 'homepage', array( 'title' => $this->placeholder_resolver->get_homepage_title() ), $post );
	}

	// The post is the source of an auto-generated description; a blog-index homepage has none.
	private function resolve_content( string $content_type, array $context_values, ?WP_Post $post ): string {
		$content  = $this->option_manager->get_section( self::MODULE_SLUG, 'content' );
		$data     = isset( $content[ $content_type ] ) && is_array( $content[ $content_type ] ) ? $content[ $content_type ] : array();
		$template = (string) ( $data['meta_description'] ?? '' );

		if ( '' !== trim( $template ) ) {
			return $this->placeholder_resolver->resolve( $template, $context_values );
		}

		if ( empty( $data['auto_generate_description'] ) || null === $post ) {
			return '';
		}

		return $this->description_generator->generate( $post );
	}

	// A term archive has no content to generate from, so only the template applies.
	private function resolve_taxonomy( string $taxonomy_type, array $context_values ): string {
		$section  = $this->option_manager->get_section( self::MODULE_SLUG, 'categories_tags' );
		$data     = isset( $section[ $taxonomy_type ] ) && is_array( $section[ $taxonomy_type ] ) ? $section[ $taxonomy_type ] : array();
		$template = (string) ( $data['meta_description'] ?? '' );

		if ( '' === trim( $template ) ) {
			return '';
		}

		return $this->placeholder_resolver->resolve( $template, $context_values );
	}

	private function get_override( int $post_id ): string {
		$value = get_post_meta( $post_id, PostMetaKeys::DESCRIPTION, true );

		return is_string( $value ) ? trim( $value ) : '';
	}
}
