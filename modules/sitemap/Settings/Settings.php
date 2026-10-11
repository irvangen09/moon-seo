<?php

namespace Moon\SEO\Modules\Sitemap\Settings;

use Moon\SEO\Modules\Sitemap\Services\ContentTypeRegistry;
use Moon\SEO\Services\OptionManager;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private const OPTION_GROUP = 'moon_seo_sitemap';

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	private ContentTypeRegistry $content_type_registry;

	/**
	 * @var SectionInterface[]
	 */
	private array $sections = array();

	public function __construct( OptionManager $option_manager, ContentTypeRegistry $content_type_registry ) {
		$this->option_manager        = $option_manager;
		$this->content_type_registry = $content_type_registry;

		foreach ( array( new SitemapContent( $content_type_registry ), new ExcludedItems() ) as $section ) {
			$this->sections[ $section->get_section_key() ] = $section;
		}
	}

	public function init(): void {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function register_setting(): void {
		register_setting(
			self::OPTION_GROUP,
			$this->option_manager->get_option_name( self::MODULE_SLUG ),
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize' ),
				// The generic /wp/v2/settings endpoint does not persist nested objects, so the module uses its own route.
				'show_in_rest'      => false,
				'default'           => array(),
			)
		);
	}

	public function register_rest_routes(): void {
		register_rest_route(
			'moon-seo/v1',
			'/sitemap-settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_settings' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'rest_update_settings' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
				),
			)
		);

		register_rest_route(
			'moon-seo/v1',
			'/sitemap-content-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_content_types' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);
	}

	public function rest_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Registered custom post types and taxonomies, as metadata for the admin app rather than setting data.
	 */
	public function rest_get_content_types(): WP_REST_Response {
		$post_types = array();

		foreach ( $this->content_type_registry->get_custom_post_types() as $slug => $post_type_object ) {
			$post_types[] = array(
				'slug'  => $slug,
				'label' => $post_type_object->label,
			);
		}

		$taxonomies = array();

		foreach ( $this->content_type_registry->get_custom_taxonomies() as $slug => $taxonomy_object ) {
			$taxonomies[] = array(
				'slug'  => $slug,
				'label' => $taxonomy_object->label,
			);
		}

		return new WP_REST_Response(
			array(
				'post_types' => $post_types,
				'taxonomies' => $taxonomies,
			)
		);
	}

	public function rest_get_settings(): WP_REST_Response {
		// Sanitizing the stored value fills in defaults, so the app shows what the frontend actually uses.
		return new WP_REST_Response( $this->sanitize( $this->option_manager->get_all( self::MODULE_SLUG ) ) );
	}

	public function rest_update_settings( WP_REST_Request $request ) {
		$input = $request->get_json_params();

		// A body that is not a JSON object must not silently reset every setting to its default.
		if ( ! is_array( $input ) ) {
			return new WP_Error(
				'moon_seo_invalid_settings',
				__( 'The settings payload must be a JSON object.', 'moon-seo' ),
				array( 'status' => 400 )
			);
		}

		$sanitized = $this->sanitize( $input );

		$this->option_manager->update_all( self::MODULE_SLUG, $sanitized );

		// Any setting can change the sitemap's structure, so cached output and rewrite rules are rebuilt.
		do_action( 'moon_seo_sitemap_settings_updated' );

		return new WP_REST_Response( $sanitized );
	}

	/**
	 * Sections that are not registered are dropped, so unknown data never reaches the database.
	 *
	 * @param mixed $input Raw settings payload.
	 */
	public function sanitize( $input ): array {
		$input     = is_array( $input ) ? $input : array();
		$sanitized = array();

		foreach ( $this->sections as $section_key => $section ) {
			$raw = isset( $input[ $section_key ] ) && is_array( $input[ $section_key ] ) ? $input[ $section_key ] : array();

			$sanitized[ $section_key ] = $section->sanitize( $raw );
		}

		return $sanitized;
	}
}
