<?php

namespace Moon\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {

	// Held here so every module can read the parent slug without depending on another module's class.
	private const TOP_LEVEL_SLUG = 'moon-seo-general';

	public function get_top_level_slug(): string {
		return self::TOP_LEVEL_SLUG;
	}
}
