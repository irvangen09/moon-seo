<?php

namespace Moon\SEO\Modules\Schema\Services;

use Moon\SEO\Modules\Schema\Nodes\NodeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A pure aggregator: each node decides for itself whether it applies, so adding a node type means adding one class and registering it in Module.
final class SchemaGraphBuilder {

	/**
	 * @var NodeInterface[]
	 */
	private array $nodes;

	/**
	 * @param NodeInterface[] $nodes Every potentially applicable node.
	 */
	public function __construct( array $nodes ) {
		$this->nodes = $nodes;
	}

	public function build(): array {
		$graph = array();

		foreach ( $this->nodes as $node ) {
			$result = $node->get_node();

			if ( null !== $result ) {
				$graph[] = $result;
			}
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}
}
