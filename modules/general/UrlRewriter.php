<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\Services\OptionManager;
use WP_Post;
use WP_Rewrite;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UrlRewriter {

	private const MODULE_SLUG = 'general';

	/**
	 * Taxonomies whose base can be removed, with the settings key that enables each one.
	 */
	private const TAXONOMIES = array(
		'category' => array(
			'toggle'       => 'remove_category_base',
			'query_var'    => 'category_name',
			'redirect_var' => 'category_redirect',
			'option'       => 'category_base',
			'default_base' => 'category',
		),
		'post_tag' => array(
			'toggle'       => 'remove_tag_base',
			'query_var'    => 'tag',
			'redirect_var' => 'tag_redirect',
			'option'       => 'tag_base',
			'default_base' => 'tag',
		),
	);

	private OptionManager $option_manager;

	/**
	 * Taxonomies with the base removed on this request.
	 *
	 * @var string[]
	 */
	private array $active_taxonomies = array();

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function init(): void {
		$settings = $this->option_manager->get_section( self::MODULE_SLUG, 'robots_url' );

		foreach ( self::TAXONOMIES as $taxonomy => $config ) {
			if ( ! empty( $settings[ $config['toggle'] ] ) ) {
				$this->active_taxonomies[] = $taxonomy;
			}
		}

		if ( array() !== $this->active_taxonomies ) {
			add_filter( 'term_link', array( $this, 'filter_term_link' ), 10, 3 );
			add_action( 'generate_rewrite_rules', array( $this, 'add_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'template_redirect', array( $this, 'maybe_redirect_old_base' ) );

			// Every term has its own rule, so the rules go stale when terms change.
			add_action( 'created_term', array( $this, 'invalidate_rules' ), 10, 3 );
			add_action( 'edited_term', array( $this, 'invalidate_rules' ), 10, 3 );
			add_action( 'delete_term', array( $this, 'invalidate_rules' ), 10, 3 );
		}

		if ( ! empty( $settings['redirect_attachments_to_parent'] ) ) {
			add_action( 'template_redirect', array( $this, 'redirect_attachment_to_parent' ) );
		}
	}

	public function add_query_vars( array $vars ): array {
		foreach ( $this->active_taxonomies as $taxonomy ) {
			$vars[] = self::TAXONOMIES[ $taxonomy ]['redirect_var'];
		}

		return $vars;
	}

	/**
	 * Drops the cached rewrite rules so they are rebuilt on the next request.
	 *
	 * @param mixed  $unused_term Term ID, or the term object for delete_term.
	 * @param int    $unused_tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function invalidate_rules( $unused_term, $unused_tt_id, $taxonomy ): void {
		if ( in_array( $taxonomy, $this->active_taxonomies, true ) ) {
			delete_option( 'rewrite_rules' );
		}
	}

	public function filter_term_link( string $link, WP_Term $term, string $taxonomy ): string {
		if ( ! in_array( $taxonomy, $this->active_taxonomies, true ) ) {
			return $link;
		}

		$needle = '/' . $this->get_base( $taxonomy ) . '/';
		$offset = 0 === strpos( $link, home_url() ) ? strlen( home_url() ) : 0;
		$pos    = strpos( $link, $needle, $offset );

		if ( false === $pos ) {
			return $link;
		}

		return substr_replace( $link, '/', $pos, strlen( $needle ) );
	}

	/**
	 * Registers one rule set per term, plus a catch-all that sends the old URL shape to the redirect.
	 *
	 * The rules go in front of the core rules so a term URL is not taken for a page.
	 */
	public function add_rewrite_rules( WP_Rewrite $wp_rewrite ): void {
		$rules = array();

		foreach ( $this->active_taxonomies as $taxonomy ) {
			$config = self::TAXONOMIES[ $taxonomy ];
			$prefix = $this->get_front( $taxonomy, $wp_rewrite );

			foreach ( $this->get_term_paths( $taxonomy ) as $path ) {
				$match = $prefix . '(' . preg_quote( $path, '#' ) . ')';

				$rules[ $match . '/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?' . $config['query_var'] . '=$matches[1]&feed=$matches[2]';
				$rules[ $match . '/page/?([0-9]{1,})/?$' ]                  = 'index.php?' . $config['query_var'] . '=$matches[1]&paged=$matches[2]';
				$rules[ $match . '/?$' ]                                    = 'index.php?' . $config['query_var'] . '=$matches[1]';
			}
		}

		// After all term rules, so a term whose path starts with the base still resolves first.
		foreach ( $this->active_taxonomies as $taxonomy ) {
			$prefix = $this->get_front( $taxonomy, $wp_rewrite );

			$rules[ $prefix . preg_quote( $this->get_base( $taxonomy ), '#' ) . '/(.+)$' ] = 'index.php?' . self::TAXONOMIES[ $taxonomy ]['redirect_var'] . '=$matches[1]';
		}

		$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
	}

	/**
	 * Sends the old URL (with the base) to the same path without it.
	 *
	 * A path that is not a term ends up as a normal 404 at the new address.
	 */
	public function maybe_redirect_old_base(): void {
		global $wp_rewrite;

		foreach ( $this->active_taxonomies as $taxonomy ) {
			$path = trim( (string) get_query_var( self::TAXONOMIES[ $taxonomy ]['redirect_var'] ), '/' );

			if ( '' === $path ) {
				continue;
			}

			$prefix = $this->get_front( $taxonomy, $wp_rewrite );

			wp_safe_redirect( home_url( '/' . user_trailingslashit( $prefix . $path ) ), 301 );
			exit;
		}
	}

	/**
	 * Redirects an attachment page to its parent when the parent is publicly viewable.
	 */
	public function redirect_attachment_to_parent(): void {
		if ( ! is_attachment() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post || ! $post->post_parent || ! is_post_publicly_viewable( $post->post_parent ) ) {
			return;
		}

		$parent_url = get_permalink( $post->post_parent );

		if ( false !== $parent_url ) {
			wp_safe_redirect( $parent_url, 301 );
			exit;
		}
	}

	private function get_base( string $taxonomy ): string {
		$config = self::TAXONOMIES[ $taxonomy ];
		$base   = get_option( $config['option'] );

		return $base ? trim( $base, '/' ) : $config['default_base'];
	}

	/**
	 * The permalink front (for example "blog/" or "index.php/") that WordPress puts before the base, or an empty string.
	 *
	 * Used as is in rule keys, like core does: WordPress matches rules that start with
	 * "index.php" against the request path including it, so the front must not be escaped.
	 */
	private function get_front( string $taxonomy, WP_Rewrite $wp_rewrite ): string {
		$object     = get_taxonomy( $taxonomy );
		$with_front = ! is_object( $object ) || ! is_array( $object->rewrite ) || ! empty( $object->rewrite['with_front'] );

		return $with_front ? ltrim( $wp_rewrite->front, '/' ) : '';
	}

	/**
	 * URL paths of every term: the slug, or the slugs of the ancestors and the term for hierarchical taxonomies.
	 *
	 * @return string[]
	 */
	private function get_term_paths( string $taxonomy ): array {
		if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
			$slugs = get_terms(
				array(
					'taxonomy'               => $taxonomy,
					'hide_empty'             => false,
					'fields'                 => 'slugs',
					'update_term_meta_cache' => false,
				)
			);

			return is_wp_error( $slugs ) ? array() : $slugs;
		}

		$terms = get_terms(
			array(
				'taxonomy'               => $taxonomy,
				'hide_empty'             => false,
				'update_term_meta_cache' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$by_id = array();

		foreach ( $terms as $term ) {
			$by_id[ $term->term_id ] = $term;
		}

		$paths = array();

		foreach ( $terms as $term ) {
			$parts = array( $term->slug );
			$seen  = array( $term->term_id => true );
			$next  = $term->parent;

			// The seen map stops a corrupt parent loop.
			while ( $next && isset( $by_id[ $next ] ) && ! isset( $seen[ $next ] ) ) {
				$seen[ $next ] = true;

				array_unshift( $parts, $by_id[ $next ]->slug );

				$next = $by_id[ $next ]->parent;
			}

			$paths[] = implode( '/', $parts );
		}

		return $paths;
	}
}
