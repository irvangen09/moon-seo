<?php

namespace Moon\SEO\Modules\General\Services;

use Moon\SEO\Services\OptionManager;
use Moon\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaceholderResolver {

	private const MODULE_SLUG = 'general';

	private const DEFAULT_SEPARATOR = '|';

	// Stands in for {separator} while the template is resolved, so separators left dangling by an empty placeholder can be told apart from characters inside values.
	private const SEPARATOR_MARK = "\x1F";

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;
	}

	/**
	 * Replaces {placeholders} in a template. Global placeholders are always available;
	 * contextual ones ({title}, {query}, {term_title}) come from the caller.
	 */
	public function resolve( string $template, array $values = array() ): string {
		$global    = $this->get_global_placeholders();
		$separator = (string) $global['separator'];

		unset( $global['separator'] );

		$replacements = array( '{separator}' => self::SEPARATOR_MARK );

		foreach ( array_merge( $global, $values ) as $key => $value ) {
			$replacements[ '{' . $key . '}' ] = str_replace( self::SEPARATOR_MARK, '', (string) $value );
		}

		// strtr() never re-scans replaced text, so a value that contains "{something}" stays literal.
		$resolved = trim( (string) preg_replace( '/\s+/', ' ', strtr( $template, $replacements ) ) );

		return str_replace( self::SEPARATOR_MARK, $separator, $this->remove_dangling_separators( $resolved ) );
	}

	public function get_site_name(): string {
		return $this->site_identity->get_effective_website_name();
	}

	// With a static front page the page's own title is used, so the default template does not print the site name twice.
	public function get_homepage_title(): string {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );

			if ( $front_page_id > 0 ) {
				$front_page_title = get_the_title( $front_page_id );

				if ( '' !== $front_page_title ) {
					return $front_page_title;
				}
			}
		}

		return $this->get_site_name();
	}

	// A placeholder that resolved to nothing (an empty tagline, for example) must not leave a separator at the edge of the title or two in a row.
	private function remove_dangling_separators( string $text ): string {
		$mark = preg_quote( self::SEPARATOR_MARK, '/' );

		$text = (string) preg_replace( '/(\s*)' . $mark . '(?:\s*' . $mark . ')+/', '$1' . self::SEPARATOR_MARK, $text );
		$text = (string) preg_replace( '/^(?:\s*' . $mark . ')+\s*|(?:\s*' . $mark . ')+\s*$/', '', $text );

		return $text;
	}

	private function get_global_placeholders(): array {
		$site_info = $this->option_manager->get_section( self::MODULE_SLUG, 'site_info' );

		return array(
			'site_name' => $this->get_site_name(),
			// The tagline is read from WordPress itself, not from this module's settings.
			'tagline'   => get_bloginfo( 'description' ),
			'separator' => $site_info['title_separator'] ?? self::DEFAULT_SEPARATOR,
		);
	}
}
