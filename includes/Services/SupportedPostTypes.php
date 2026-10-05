<?php

namespace Moon\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SupportedPostTypes {

	private ?array $resolved = null;

	public function all(): array {
		if ( null !== $this->resolved ) {
			return $this->resolved;
		}

		/**
		 * Filters the post types that get Moon SEO's per-post SEO features.
		 *
		 * The default is `post`, `page`, and every other post type that is
		 * public, publicly queryable, and has an admin UI, except `attachment`.
		 * Add an entry to support another post type, or unset one to exclude it.
		 *
		 * @since 0.1.0
		 *
		 * @param array $post_types Post type slug => array {
		 *     @type string $content_group Content settings group: 'post' or 'page'.
		 *     @type string $schema_node   Schema node type: 'article' or 'webpage'.
		 * }
		 */
		$post_types = apply_filters( 'moon_seo_supported_post_types', $this->get_defaults() );

		if ( ! is_array( $post_types ) ) {
			$post_types = $this->get_defaults();
		}

		// Post types register during init, so an earlier result could be incomplete and is not kept.
		if ( did_action( 'init' ) && ! doing_action( 'init' ) ) {
			$this->resolved = $post_types;
		}

		return $post_types;
	}

	public function is_supported( string $post_type ): bool {
		return isset( $this->all()[ $post_type ] );
	}

	public function content_group( string $post_type ): ?string {
		return $this->all()[ $post_type ]['content_group'] ?? null;
	}

	public function schema_node( string $post_type ): ?string {
		return $this->all()[ $post_type ]['schema_node'] ?? null;
	}

	private function get_defaults(): array {
		// `page` is not publicly queryable by default, so both built-ins are listed explicitly.
		$defaults = array(
			'post' => array(
				'content_group' => 'post',
				'schema_node'   => 'article',
			),
			'page' => array(
				'content_group' => 'page',
				'schema_node'   => 'webpage',
			),
		);

		$candidates = get_post_types(
			array(
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
			),
			'objects'
		);

		foreach ( $candidates as $slug => $post_type ) {
			if ( 'attachment' === $slug || isset( $defaults[ $slug ] ) ) {
				continue;
			}

			$defaults[ $slug ] = array(
				'content_group' => $post_type->hierarchical ? 'page' : 'post',
				'schema_node'   => 'webpage',
			);
		}

		return $defaults;
	}
}