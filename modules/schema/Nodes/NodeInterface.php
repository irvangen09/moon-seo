<?php

namespace Moon\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface NodeInterface {

	/**
	 * Returns the node for the "@graph", or null when it does not apply to the current request.
	 */
	public function get_node(): ?array;
}
