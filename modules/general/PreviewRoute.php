<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Modules\General\Services\TitleResolver;
use Moon\SEO\Services\SupportedPostTypes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the SEO title and meta description of a post for the editor preview.
 *
 * Uses the same resolvers as the frontend, fed with the values being typed in the editor.
 */
final class PreviewRoute {

	private const REST_NAMESPACE = 'moon-seo/v1';

	private const ROUTE = '/seo-preview';

	private TitleResolver $title_resolver;

	private DescriptionResolver $description_resolver;

	private SupportedPostTypes $supported_post_types;

	public function __construct( TitleResolver $title_resolver, DescriptionResolver $description_resolver, SupportedPostTypes $supported_post_types ) {
		$this->title_resolver       = $title_resolver;
		$this->description_resolver = $description_resolver;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'can_preview' ),
				'args'                => array(
					'post_id'          => array(
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'seo_title'        => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'meta_description' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					// Never stored, and escaped on output, so it is used as typed: a saved title may hold markup too.
					'title'            => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	public function can_preview( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', (int) $request->get_param( 'post_id' ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle( WP_REST_Request $request ) {
		$post = get_post( (int) $request->get_param( 'post_id' ) );

		if ( null === $post ) {
			return new WP_Error( 'moon_seo_invalid_post', __( 'Invalid post ID.', 'moon-seo' ), array( 'status' => 404 ) );
		}

		if ( ! $this->supported_post_types->is_supported( $post->post_type ) ) {
			return new WP_Error( 'moon_seo_unsupported_post_type', __( 'This post type is not supported.', 'moon-seo' ), array( 'status' => 400 ) );
		}

		// The post title being typed replaces the saved one, filtered the way WordPress prints it.
		$typed_post             = clone $post;
		$typed_post->post_title = (string) $request->get_param( 'title' );
		$post_title             = get_the_title( $typed_post );

		$title       = $this->title_resolver->resolve_for_post( $post, (string) $request->get_param( 'seo_title' ), $post_title );
		$description = $this->description_resolver->resolve_for_post( $post, (string) $request->get_param( 'meta_description' ), $post_title );

		// Returned as the browser shows them: escaped the way the tags are printed, then decoded.
		return new WP_REST_Response(
			array(
				'title'       => $this->as_displayed( esc_html( $title ) ),
				'description' => $this->as_displayed( esc_attr( $description ) ),
			)
		);
	}

	private function as_displayed( string $escaped ): string {
		return html_entity_decode( $escaped, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
