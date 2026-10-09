<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\Modules\General\Renderers\OpenGraphRenderer;
use Moon\SEO\Modules\General\Renderers\MetaRenderer;
use Moon\SEO\Modules\General\Renderers\RendererInterface;
use Moon\SEO\Modules\General\Renderers\TitleRenderer;
use Moon\SEO\Modules\General\Renderers\TwitterCardRenderer;
use Moon\SEO\Modules\General\Renderers\VerificationRenderer;
use Moon\SEO\Modules\General\Services\DescriptionGenerator;
use Moon\SEO\Modules\General\Services\DescriptionResolver;
use Moon\SEO\Modules\General\Services\PlaceholderResolver;
use Moon\SEO\Modules\General\Services\TitleResolver;
use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;
use Moon\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	/**
	 * Each Renderer registers its own hook in init(), so the order of output is set by hook priority, not by this list.
	 *
	 * @var RendererInterface[]
	 */
	private array $renderers;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, SupportedPostTypes $supported_post_types ) {
		$placeholder_resolver = new PlaceholderResolver( $option_manager, $site_identity );
		$description_resolver = new DescriptionResolver( $option_manager, $placeholder_resolver, new DescriptionGenerator(), $supported_post_types );

		$this->renderers = array(
			new TitleRenderer( $option_manager, new TitleResolver( $placeholder_resolver ), $supported_post_types ),
			new MetaRenderer( $option_manager, $description_resolver ),
			new OpenGraphRenderer( $option_manager, $description_resolver, $site_identity, $supported_post_types ),
			new TwitterCardRenderer( $option_manager, $description_resolver, $site_identity ),
			new VerificationRenderer( $option_manager ),
		);
	}

	public function init(): void {
		foreach ( $this->renderers as $renderer ) {
			$renderer->init();
		}
	}
}
