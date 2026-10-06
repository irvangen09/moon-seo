<?php

namespace Moon\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SectionInterface {

	/**
	 * Key of this section inside the module option.
	 */
	public function get_section_key(): string;

	/**
	 * Receives only this section's own sub-array, so no section needs to know another's structure.
	 */
	public function sanitize( array $input ): array;
}
