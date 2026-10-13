<?php

namespace Moon\SEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Shared by the modules that read the per-post overrides. uninstall.php repeats these literals because it cannot use the autoloader.
final class PostMetaKeys {

	public const TITLE = '_moon_seo_title';

	public const DESCRIPTION = '_moon_seo_description';

	public const CANONICAL = '_moon_seo_canonical';

	public const ROBOTS = '_moon_seo_robots';
}
