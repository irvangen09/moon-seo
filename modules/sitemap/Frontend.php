<?php

namespace Moon\SEO\Modules\Sitemap;

use Moon\SEO\Modules\Sitemap\Providers\AuthorProvider;
use Moon\SEO\Modules\Sitemap\Providers\DateArchiveProvider;
use Moon\SEO\Modules\Sitemap\Providers\HomepageProvider;
use Moon\SEO\Modules\Sitemap\Providers\PostTypeProvider;
use Moon\SEO\Modules\Sitemap\Providers\ProviderInterface;
use Moon\SEO\Modules\Sitemap\Providers\TaxonomyProvider;
use Moon\SEO\Modules\Sitemap\Services\SitemapCache;
use Moon\SEO\Modules\Sitemap\Services\XmlBuilder;
use Moon\SEO\Modules\Sitemap\Settings\SitemapContent;
use Moon\SEO\Services\OptionManager;
use WP_Rewrite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	private const MODULE_SLUG = 'sitemap';

	// Built-in types in index order. "post_tag" is deliberately served as "tags-sitemap.xml".
	private const CORE_TYPES = array(
		'homepage' => array(
			'prefix' => 'homepage',
			'kind'   => 'homepage',
			'toggle' => 'include_homepage',
		),
		'post'     => array(
			'prefix' => 'post',
			'kind'   => 'post_type',
			'toggle' => 'include_posts',
		),
		'page'     => array(
			'prefix' => 'page',
			'kind'   => 'post_type',
			'toggle' => 'include_static_pages',
		),
		'category' => array(
			'prefix' => 'category',
			'kind'   => 'taxonomy',
			'toggle' => 'include_categories',
		),
		'post_tag' => array(
			'prefix' => 'tags',
			'kind'   => 'taxonomy',
			'toggle' => 'include_tag_pages',
		),
		'authors'  => array(
			'prefix' => 'authors',
			'kind'   => 'authors',
			'toggle' => 'include_author_pages',
		),
		'archives' => array(
			'prefix' => 'archives',
			'kind'   => 'archives',
			'toggle' => 'include_archives',
		),
	);

	private OptionManager $option_manager;

	private SitemapCache $cache;

	private XmlBuilder $xml_builder;

	public function __construct( OptionManager $option_manager, SitemapCache $cache, XmlBuilder $xml_builder ) {
		$this->option_manager = $option_manager;
		$this->cache          = $cache;
		$this->xml_builder    = $xml_builder;
	}

	public function init(): void {
		$this->cache->register_invalidation_hooks();

		add_filter( 'generate_rewrite_rules', array( $this, 'add_sitemap_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'maybe_output_sitemap' ) );
		add_filter( 'redirect_canonical', array( $this, 'skip_canonical_redirect' ) );

		// Dropping the stored rules makes WordPress rebuild them on the next request, through the filter above.
		add_action( 'moon_seo_sitemap_settings_updated', array( $this, 'reset_rewrite_rules' ) );

		// The built-in sitemap would be a second, competing one.
		add_filter( 'wp_sitemaps_enabled', '__return_false' );

		// With the core sitemap off, WordPress no longer adds its own Sitemap: line, so the plugin adds one.
		add_filter( 'robots_txt', array( $this, 'add_robots_sitemap_line' ), 10, 2 );
	}

	public function add_robots_sitemap_line( string $output, bool $is_public ): string {
		if ( ! $is_public ) {
			return $output;
		}

		return $output . "\nSitemap: " . esc_url( home_url( '/sitemap.xml' ) ) . "\n";
	}

	public function add_query_vars( array $vars ): array {
		$vars[] = 'moon_seo_sitemap';
		$vars[] = 'moon_seo_sitemap_page';

		return $vars;
	}

	/**
	 * With a trailing-slash permalink structure, WordPress would redirect /sitemap.xml to /sitemap.xml/.
	 *
	 * @param string|false $redirect_url Proposed redirect target.
	 * @return string|false
	 */
	public function skip_canonical_redirect( $redirect_url ) {
		return '' !== (string) get_query_var( 'moon_seo_sitemap' ) ? false : $redirect_url;
	}

	public function reset_rewrite_rules(): void {
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Runs whenever the ruleset is regenerated, so it always reads the settings as they are now.
	 */
	public function add_sitemap_rewrite_rules( WP_Rewrite $wp_rewrite ): WP_Rewrite {
		$rules = array(
			'sitemap\.xml$' => 'index.php?moon_seo_sitemap=index',
		);

		foreach ( $this->get_active_types() as $type => $definition ) {
			$prefix = preg_quote( $definition['prefix'], '#' );

			$rules[ $prefix . '-sitemap\.xml$' ] = 'index.php?moon_seo_sitemap=' . $type . '&moon_seo_sitemap_page=1';
			// Page 1 has no number, so "-sitemap1.xml" and "-sitemap0.xml" are not valid addresses.
			$rules[ $prefix . '-sitemap([2-9]|[1-9][0-9]+)\.xml$' ] = 'index.php?moon_seo_sitemap=' . $type . '&moon_seo_sitemap_page=$matches[1]';
		}

		$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );

		return $wp_rewrite;
	}

	public function maybe_output_sitemap(): void {
		$type = (string) get_query_var( 'moon_seo_sitemap' );

		if ( '' === $type ) {
			return;
		}

		if ( 'index' === $type ) {
			$this->output_index();

			return;
		}

		$active = $this->get_active_types();

		// Only types switched on in Settings are served, whatever the query string asks for.
		if ( ! isset( $active[ $type ] ) ) {
			$this->send_not_found();

			return;
		}

		$this->output_type( $type, $active[ $type ]['kind'] );
	}

	private function output_index(): void {
		$content        = $this->get_content_settings();
		$links_per_page = $content['links_per_page'];

		$sitemaps = array();

		foreach ( $this->get_active_types() as $type => $definition ) {
			$meta        = $this->get_meta( $type, $definition['kind'] );
			$total_pages = max( 1, (int) ceil( $meta['count'] / $links_per_page ) );
			$lastmod     = $content['include_last_modified'] ? $meta['lastmod'] : null;

			for ( $page = 1; $page <= $total_pages; $page++ ) {
				$sitemaps[] = array(
					'loc'     => home_url( '/' . $definition['prefix'] . '-sitemap' . ( $page > 1 ? (string) $page : '' ) . '.xml' ),
					'lastmod' => $lastmod,
				);
			}
		}

		$this->send_xml( $this->xml_builder->build_sitemap_index( $sitemaps ) );
	}

	private function output_type( string $type, string $kind ): void {
		$content        = $this->get_content_settings();
		$links_per_page = $content['links_per_page'];

		$raw_page = get_query_var( 'moon_seo_sitemap_page' );
		$page     = '' === $raw_page ? 1 : absint( $raw_page );

		$meta        = $this->get_meta( $type, $kind );
		$total_pages = max( 1, (int) ceil( $meta['count'] / $links_per_page ) );

		if ( $page < 1 || $page > $total_pages ) {
			$this->send_not_found();

			return;
		}

		$entries = $this->cache->get_page( $type, $page );

		if ( null === $entries ) {
			$provider = $this->create_provider( $type, $kind );
			$entries  = null === $provider ? array() : $provider->get_entries( ( $page - 1 ) * $links_per_page, $links_per_page );

			$this->cache->set_page( $type, $page, $entries );
		}

		$this->send_xml( $this->xml_builder->build_urlset( $entries, $content['include_last_modified'] ) );
	}

	/**
	 * Entry count and lastmod of one type, from cache or from its Provider.
	 *
	 * @return array{count: int, lastmod: string|null}
	 */
	private function get_meta( string $type, string $kind ): array {
		$meta = $this->cache->get_meta( $type );

		if ( null !== $meta ) {
			return $meta;
		}

		$provider = $this->create_provider( $type, $kind );
		$count    = null === $provider ? 0 : $provider->count();

		$meta = array(
			'count'   => $count,
			'lastmod' => $count > 0 ? $provider->get_lastmod() : null,
		);

		$this->cache->set_meta( $type, $meta );

		return $meta;
	}

	private function create_provider( string $type, string $kind ): ?ProviderInterface {
		switch ( $kind ) {
			case 'homepage':
				return new HomepageProvider();
			case 'authors':
				return new AuthorProvider();
			case 'archives':
				return new DateArchiveProvider();
			case 'taxonomy':
				return taxonomy_exists( $type ) ? new TaxonomyProvider( $this->option_manager, $type ) : null;
			case 'post_type':
				return post_type_exists( $type ) ? new PostTypeProvider( $this->option_manager, $type ) : null;
		}

		return null;
	}

	private function get_content_settings(): array {
		return SitemapContent::resolve( $this->option_manager->get_section( self::MODULE_SLUG, 'sitemap_content' ) );
	}

	/**
	 * Types switched on in Settings, keyed by internal type, with their file prefix and kind.
	 *
	 * @return array<string, array{prefix: string, kind: string}>
	 */
	private function get_active_types(): array {
		$content = $this->get_content_settings();
		$active  = array();
		$used    = array();

		foreach ( self::CORE_TYPES as $type => $definition ) {
			if ( $content[ $definition['toggle'] ] ) {
				$active[ $type ] = array(
					'prefix' => $definition['prefix'],
					'kind'   => $definition['kind'],
				);
			}
		}

		// A custom slug that equals a built-in key or file prefix would share its cache and its file name.
		$reserved = array_merge( array_keys( self::CORE_TYPES ), array_column( self::CORE_TYPES, 'prefix' ) );

		foreach ( $content['custom_post_types'] as $slug => $enabled ) {
			if ( $enabled && post_type_exists( $slug ) && ! in_array( $slug, $reserved, true ) && ! isset( $used[ $slug ] ) ) {
				$active[ $slug ] = array(
					'prefix' => $slug,
					'kind'   => 'post_type',
				);
				$used[ $slug ]   = true;
			}
		}

		foreach ( $content['custom_taxonomies'] as $slug => $enabled ) {
			if ( $enabled && taxonomy_exists( $slug ) && ! in_array( $slug, $reserved, true ) && ! isset( $used[ $slug ] ) ) {
				$active[ $slug ] = array(
					'prefix' => $slug,
					'kind'   => 'taxonomy',
				);
				$used[ $slug ]   = true;
			}
		}

		return $active;
	}

	private function send_xml( string $xml ): void {
		header( 'Content-Type: application/xml; charset=UTF-8' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Every value is escaped while the XML is built.
		echo $xml;
		exit;
	}

	private function send_not_found(): void {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
