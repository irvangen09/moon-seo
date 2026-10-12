<?php

namespace Moon\SEO\Modules\Schema;

use Moon\SEO\Modules\Schema\Services\SchemaGraphBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	private SchemaGraphBuilder $graph_builder;

	public function __construct( SchemaGraphBuilder $graph_builder ) {
		$this->graph_builder = $graph_builder;
	}

	public function init(): void {
		add_action( 'wp_head', array( $this, 'render' ) );
	}

	public function render(): void {
		$graph = $this->graph_builder->build();

		// WebSite and Organization always apply, so this is only a safeguard against printing an empty script.
		if ( empty( $graph['@graph'] ) ) {
			return;
		}

		/*
		 * The JSON sits inside a <script> element, so these flags are a security control. Values such as titles
		 * and names are not HTML-escaped on the way here.
		 * - Never add JSON_UNESCAPED_SLASHES: slash escaping keeps a literal "</script>" out of the output.
		 * - JSON_HEX_TAG escapes "<" and ">", so "<!--<script>" cannot make the parser swallow the rest of the page.
		 * JSON_UNESCAPED_UNICODE is safe: it touches neither character.
		 */
		$json = wp_json_encode( $graph, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

		if ( false === $json ) {
			return;
		}

		printf( '<script type="application/ld+json">%s</script>' . "\n", $json ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with the flags above.
	}
}
