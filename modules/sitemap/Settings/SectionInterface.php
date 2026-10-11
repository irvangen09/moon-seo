<?php

namespace Moon\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Duplicated from the General module on purpose: modules must not depend on each other.
interface SectionInterface {

	/**
	 * Key of this section inside the module option.
	 */
	public function get_section_key(): string;

	/**
	 * Receives only this section's own sub-array.
	 */
	public function sanitize( array $input ): array;
}
