<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Editor {

	// Late enough to see post types that other code registers on init at the default priority.
	private const REGISTER_PRIORITY = 99;

	private const ALLOWED_ROBOTS_DIRECTIVES = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );

	private SupportedPostTypes $supported_post_types;

	public function __construct( SupportedPostTypes $supported_post_types ) {
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		add_action( 'init', array( $this, 'register_meta' ), self::REGISTER_PRIORITY );
	}

	public function register_meta(): void {
		foreach ( array_keys( $this->supported_post_types->all() ) as $post_type ) {
			$this->register_meta_for_post_type( $post_type );
		}
	}

	/**
	 * Whether the block editor UI can read and write the per-post fields for a post type.
	 *
	 * The REST API only exposes post meta for post types that support custom fields,
	 * so the block editor sidebar cannot work for the others. Those post types use
	 * the meta box instead.
	 */
	public function has_sidebar( string $post_type ): bool {
		return $this->supported_post_types->is_supported( $post_type ) && post_type_supports( $post_type, 'custom-fields' );
	}

	private function register_meta_for_post_type( string $post_type ): void {
		register_post_meta(
			$post_type,
			PostMetaKeys::TITLE,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => array( $this, 'can_edit_meta' ),
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			$post_type,
			PostMetaKeys::DESCRIPTION,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => array( $this, 'can_edit_meta' ),
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			$post_type,
			PostMetaKeys::CANONICAL,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => array( $this, 'can_edit_meta' ),
				'show_in_rest'      => true,
			)
		);

		register_post_meta(
			$post_type,
			PostMetaKeys::ROBOTS,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize_robots_override' ),
				'auth_callback'     => array( $this, 'can_edit_meta' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	/**
	 * Keeps only whitelisted directives.
	 *
	 * Public because registered meta calls it back and the meta box reuses it.
	 *
	 * @param mixed $value Raw meta value.
	 */
	public function sanitize_robots_override( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_intersect( array_unique( $value ), self::ALLOWED_ROBOTS_DIRECTIVES ) );
	}

	public function get_allowed_robots_directives(): array {
		return self::ALLOWED_ROBOTS_DIRECTIVES;
	}

	public function can_edit_meta( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}
}
