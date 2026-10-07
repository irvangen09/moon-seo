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
		$content_group = is_singular() ? $this->supported_post_types->content_group( (string) get_post_type() ) : null;

		// Checked before the homepage so a static front page honours its own override.
		if ( null !== $content_group ) {
			$override = $this->get_override();

			if ( '' !== $override ) {
				return $this->placeholder_resolver->resolve( $override, array( 'title' => get_the_title() ) );
			}
		}

		if ( is_front_page() && ! is_paged() ) {
			return $this->resolve_content( 'homepage', array( 'title' => $this->placeholder_resolver->get_homepage_title() ) );
		}

		if ( null !== $content_group ) {
			return $this->resolve_content( $content_group, array( 'title' => get_the_title() ) );
		}

		if ( is_category() || is_tag() ) {
			return $this->resolve_taxonomy( is_category() ? 'categories' : 'tags', array( 'term_title' => single_term_title( '', false ) ) );
		}

		// Search and 404 have no description field in Settings.
		return '';
	}

	private function resolve_content( string $content_type, array $context_values ): string {
		$content  = $this->option_manager->get_section( self::MODULE_SLUG, 'content' );
		$data     = isset( $content[ $content_type ] ) && is_array( $content[ $content_type ] ) ? $content[ $content_type ] : array();
		$template = (string) ( $data['meta_description'] ?? '' );

		if ( '' !== trim( $template ) ) {
			return $this->placeholder_resolver->resolve( $template, $context_values );
		}

		if ( empty( $data['auto_generate_description'] ) || ! is_singular() ) {
			return '';
		}

		$post = get_queried_object();

		return $post instanceof WP_Post ? $this->description_generator->generate( $post ) : '';
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

	private function get_override(): string {
		$value = get_post_meta( get_queried_object_id(), PostMetaKeys::DESCRIPTION, true );

		return is_string( $value ) ? trim( $value ) : '';
	}
}
