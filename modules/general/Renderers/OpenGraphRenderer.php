<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Modules\General\Services\PlaceholderResolver;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OpenGraphRenderer implements RendererInterface {

	use SocialMetaTrait;

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private PlaceholderResolver $placeholder_resolver;

	private DescriptionResolver $description_resolver;

	private SiteIdentity $site_identity;

	public function __construct(
		OptionManager $option_manager,
		PlaceholderResolver $placeholder_resolver,
		DescriptionResolver $description_resolver,
		SiteIdentity $site_identity
	) {
		$this->option_manager       = $option_manager;
		$this->placeholder_resolver = $placeholder_resolver;
		$this->description_resolver = $description_resolver;
		$this->site_identity        = $site_identity;
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
		$this->output_tag( 'og:type', is_singular() ? 'article' : 'website' );
		$this->output_url_tag( 'og:url', $this->resolve_url() );
		$this->output_url_tag( 'og:image', $this->resolve_image_url( (int) ( $og['image_id'] ?? 0 ) ) );
	}

	protected function meta_attribute(): string {
		return 'property';
	}

	private function resolve_url(): string {
		if ( is_front_page() ) {
			return home_url( '/' );
		}

		if ( is_singular() ) {
			$permalink = get_permalink();

			return false !== $permalink ? $permalink : '';
		}

		if ( is_category() || is_tag() ) {
			$term_link = get_term_link( get_queried_object() );

			return is_wp_error( $term_link ) ? '' : $term_link;
		}

		return '';
	}
}
