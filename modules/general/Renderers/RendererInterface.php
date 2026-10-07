<?php

namespace Moon\SEO\Modules\General\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RendererInterface {

	// Registers the hook this Renderer needs; each output attaches to WordPress at a different point.
	public function init(): void;
}
