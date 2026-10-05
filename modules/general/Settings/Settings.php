<?php

namespace Moon\SEO\Modules\General\Settings;

use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private const OPTION_GROUP = 'moon_seo_general';

	private const MODULE_SLUG = 'general';

	// Stored by SiteIdentity instead of the module option. title_separator stays here.
	private const SITE_IDENTITY_FIELDS = array( 'website_name', 'alternate_website_name', 'site_image_id' );

	// Toggles that change the URL structure and therefore need a rewrite flush.
	private const REWRITE_FIELDS = array( 'remove_category_base', 'remove_tag_base' );

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	/**
	 * @var SectionInterface[]
	 */
	private array $sections = array();

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;

		foreach ( array( new SiteInfo(), new Content(), new CategoriesTags(), new Social(), new Verification(), new RobotsUrl() ) as $section ) {
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
			'/general-settings',
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
	}

	public function rest_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	public function rest_get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->overlay_site_identity( $this->option_manager->get_all( self::MODULE_SLUG ) ) );
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

		$before    = $this->option_manager->get_all( self::MODULE_SLUG );
		$sanitized = $this->sanitize( $input );

		$this->site_identity->set( $this->extract_site_identity( $sanitized ) );

		foreach ( self::SITE_IDENTITY_FIELDS as $field ) {
			unset( $sanitized['site_info'][ $field ] );
		}

		$this->option_manager->update_all( self::MODULE_SLUG, $sanitized );

		$this->maybe_flush_rewrite_rules( $before, $sanitized );

		return new WP_REST_Response( $this->overlay_site_identity( $sanitized ) );
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

	private function extract_site_identity( array $sanitized ): array {
		$site_info = $sanitized['site_info'] ?? array();

		return array(
			'website_name'           => $site_info['website_name'] ?? '',
			'alternate_website_name' => $site_info['alternate_website_name'] ?? '',
			'site_image_id'          => $site_info['site_image_id'] ?? 0,
		);
	}

	// Keeps the identity fields under "site_info" in the payload, whatever their storage location.
	private function overlay_site_identity( array $data ): array {
		$site_info = isset( $data['site_info'] ) && is_array( $data['site_info'] ) ? $data['site_info'] : array();

		$site_info['website_name']           = $this->site_identity->get_website_name();
		$site_info['alternate_website_name'] = $this->site_identity->get_alternate_website_name();
		$site_info['site_image_id']          = $this->site_identity->get_site_image_id();

		$data['site_info'] = $site_info;

		return $data;
	}

	private function maybe_flush_rewrite_rules( array $before, array $after ): void {
		$before_url = $before['robots_url'] ?? array();
		$after_url  = $after['robots_url'] ?? array();

		foreach ( self::REWRITE_FIELDS as $field ) {
			if ( ! empty( $before_url[ $field ] ) !== ! empty( $after_url[ $field ] ) ) {
				flush_rewrite_rules();
				return;
			}
		}
	}
}