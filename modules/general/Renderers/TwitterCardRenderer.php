<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TwitterCardRenderer implements RendererInterface {

	use SocialMetaTrait;

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private DescriptionResolver $description_resolver;

	private SiteIdentity $site_identity;

	public function __construct(
		OptionManager $option_manager,
		DescriptionResolver $description_resolver,
		SiteIdentity $site_identity
	) {
		$this->option_manager       = $option_manager;
		$this->description_resolver = $description_resolver;
		$this->site_identity        = $site_identity;
	}

	public function init(): void {
		add_action( 'wp_head', array( $this, 'output' ), 4 );
	}

	public function output(): void {
		$social  = $this->option_manager->get_section( self::MODULE_SLUG, 'social' );
		$twitter = isset( $social['twitter_card'] ) && is_array( $social['twitter_card'] ) ? $social['twitter_card'] : array();

		if ( empty( $twitter['enabled'] ) ) {
			return;
		}

		$image_url = $this->resolve_image_url( (int) ( $twitter['image_id'] ?? 0 ) );

		$this->output_tag( 'twitter:card', '' !== $image_url ? 'summary_large_image' : 'summary' );
		$this->output_tag( 'twitter:title', $this->resolve_title() );
		$this->output_tag( 'twitter:description', $this->description_resolver->resolve() );
		$this->output_url_tag( 'twitter:image', $image_url );
	}

	protected function meta_attribute(): string {
		return 'name';
	}
}
