<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;
use Moon\SEO\Support\CanonicalUrl;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OpenGraphRenderer implements RendererInterface {

	use SocialMetaTrait;

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private DescriptionResolver $description_resolver;

	private SiteIdentity $site_identity;

	private SupportedPostTypes $supported_post_types;

	public function __construct(
		OptionManager $option_manager,
		DescriptionResolver $description_resolver,
		SiteIdentity $site_identity,
		SupportedPostTypes $supported_post_types
	) {
		$this->option_manager       = $option_manager;
		$this->description_resolver = $description_resolver;
		$this->site_identity        = $site_identity;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		add_action( 'wp_head', array( $this, 'output' ), 3 );
	}

	public function output(): void {
		$social = $this->option_manager->get_section( self::MODULE_SLUG, 'social' );
		$og     = isset( $social['open_graph'] ) && is_array( $social['open_graph'] ) ? $social['open_graph'] : array();

		if ( empty( $og['enabled'] ) ) {
			return;
		}

		$this->output_tag( 'og:title', $this->resolve_title() );
		$this->output_tag( 'og:description', $this->description_resolver->resolve() );
		$this->output_tag( 'og:type', $this->resolve_type() );
		$this->output_url_tag( 'og:url', $this->resolve_url() );
		$this->output_tag( 'og:site_name', $this->site_identity->get_effective_website_name() );
		$this->output_url_tag( 'og:image', $this->resolve_image_url( (int) ( $og['image_id'] ?? 0 ) ) );
	}

	protected function meta_attribute(): string {
		return 'property';
	}

	// Follows the Schema type of the post type, so "article" is never claimed for what the Schema module calls a web page.
	private function resolve_type(): string {
		$post = get_queried_object();

		if ( is_singular() && $post instanceof WP_Post && 'article' === $this->supported_post_types->schema_node( $post->post_type ) ) {
			return 'article';
		}

		return 'website';
	}

	// The canonical URL, so the share URL and the canonical tag never disagree.
	private function resolve_url(): string {
		$url = CanonicalUrl::for_request();

		if ( '' === $url && is_front_page() ) {
			return home_url( '/' );
		}

		return $url;
	}
}
