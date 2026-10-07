<?php

namespace Moon\SEO\Modules\General\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleResolver {

	private const DEFAULT_TEMPLATE = '{title} {separator} {site_name}';

	private PlaceholderResolver $placeholder_resolver;

	public function __construct( PlaceholderResolver $placeholder_resolver ) {
		$this->placeholder_resolver = $placeholder_resolver;
	}

	// Contexts without a {title} value (search, 404, term archives) must pass their own fallback template.
	public function resolve( string $template, array $context_values = array(), string $fallback_template = '' ): string {
		if ( '' === trim( $template ) ) {
			$template = '' !== $fallback_template ? $fallback_template : self::DEFAULT_TEMPLATE;
		}

		return $this->placeholder_resolver->resolve( $template, $context_values );
	}

	public function get_homepage_title(): string {
		return $this->placeholder_resolver->get_homepage_title();
	}
}
