<?php

namespace Moon\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ModuleInterface {

	/**
	 * Unique slug, also passed to the moon_seo_module_is_active filter.
	 */
	public function get_slug(): string;

	/**
	 * Called only when the module is active, so an inactive module registers
	 * no hooks and loads no assets.
	 */
	public function init(): void;
}
