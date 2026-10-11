<?php

namespace Moon\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Caches the per-type metadata (entry count, lastmod) and each generated page of entries.
 *
 * Every key embeds a version token. Invalidating a type means changing its token, so the old entries are never
 * read again and simply expire. Unlike deleting rows by prefix, this works with and without an external object
 * cache, and the expiration keeps the transients out of the autoloaded options.
 */
final class SitemapCache {

	private const KEY_PREFIX = 'moon_seo_sitemap_';

	// Options holding the version tokens; uninstall removes them by their shared prefix.
	private const TYPE_TOKEN_PREFIX = 'moon_seo_sitemap_ver_t_';

	private const GLOBAL_TOKEN = 'moon_seo_sitemap_ver_g';

	// Safety net for changes no hook reports; invalidation is normally event-driven.
	private const EXPIRATION = WEEK_IN_SECONDS;

	/**
	 * @return array{count: int, lastmod: string|null}|null Null on a cache miss.
	 */
	public function get_meta( string $type ): ?array {
		return $this->read( $this->key( $type, 'meta' ) );
	}

	public function set_meta( string $type, array $meta ): void {
		set_transient( $this->key( $type, 'meta' ), $meta, self::EXPIRATION );
	}

	/**
	 * @return array<int, array{loc: string, lastmod: string|null}>|null Null on a cache miss.
	 */
	public function get_page( string $type, int $page ): ?array {
		return $this->read( $this->key( $type, 'p' . $page ) );
	}

	public function set_page( string $type, int $page, array $entries ): void {
		set_transient( $this->key( $type, 'p' . $page ), $entries, self::EXPIRATION );
	}

	public function invalidate( string $type ): void {
		update_option( self::TYPE_TOKEN_PREFIX . $type, $this->new_token(), true );
	}

	public function flush_all(): void {
		update_option( self::GLOBAL_TOKEN, $this->new_token(), true );
	}

	public function register_invalidation_hooks(): void {
		add_action( 'save_post', array( $this, 'invalidate_for_post' ) );
		add_action( 'delete_post', array( $this, 'invalidate_for_post' ) );
		add_action( 'trashed_post', array( $this, 'invalidate_for_post' ) );

		add_action( 'created_term', array( $this, 'invalidate_for_term' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'invalidate_for_term' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'invalidate_for_term' ), 10, 3 );
		// Fires whenever a term's post count changes, which decides whether an empty term is listed.
		add_action( 'edited_term_taxonomy', array( $this, 'invalidate_for_term_count' ), 10, 2 );

		add_action( 'profile_update', array( $this, 'invalidate_authors' ) );
		add_action( 'deleted_user', array( $this, 'invalidate_authors' ) );

		add_action( 'update_option_show_on_front', array( $this, 'invalidate_homepage' ) );
		add_action( 'update_option_page_on_front', array( $this, 'invalidate_homepage' ) );

		// A Sitemap setting can change any entry.
		add_action( 'moon_seo_sitemap_settings_updated', array( $this, 'flush_all' ) );

		// Every cached URL may be wrong when new rewrite rules appear (category base, term links) or when the permalink structure
		// (a trailing slash changes the URLs but not the rules) or the site address changes.
		add_action( 'add_option_rewrite_rules', array( $this, 'flush_all' ) );
		add_action( 'update_option_rewrite_rules', array( $this, 'flush_all' ) );
		add_action( 'update_option_permalink_structure', array( $this, 'flush_all' ) );
		add_action( 'update_option_home', array( $this, 'flush_all' ) );
	}

	public function invalidate_for_post( int $post_id ): void {
		// Revisions and autosaves also fire save_post but never change a public URL.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		// Types that are not public never appear in a sitemap, nor in the author list.
		if ( false === $post_type || ! is_post_type_viewable( $post_type ) ) {
			return;
		}

		$this->invalidate( $post_type );
		// Authors are listed from posts of every public type.
		$this->invalidate( 'authors' );

		if ( 'post' === $post_type ) {
			$this->invalidate( 'archives' );
		}

		// The homepage lastmod follows the latest post, or the static front page.
		if ( 'post' === $post_type || (int) get_option( 'page_on_front' ) === $post_id ) {
			$this->invalidate( 'homepage' );
		}
	}

	public function invalidate_for_term( int $term_id, int $tt_id, string $taxonomy ): void {
		$this->invalidate( $taxonomy );
	}

	public function invalidate_for_term_count( int $tt_id, string $taxonomy ): void {
		$this->invalidate( $taxonomy );
	}

	public function invalidate_authors(): void {
		$this->invalidate( 'authors' );
	}

	public function invalidate_homepage(): void {
		$this->invalidate( 'homepage' );
	}

	private function read( string $key ): ?array {
		$value = get_transient( $key );

		return is_array( $value ) ? $value : null;
	}

	private function key( string $type, string $suffix ): string {
		$token = get_option( self::GLOBAL_TOKEN, '0' ) . '-' . get_option( self::TYPE_TOKEN_PREFIX . $type, '0' );

		return self::KEY_PREFIX . $type . '_' . $token . '_' . $suffix;
	}

	private function new_token(): string {
		return bin2hex( random_bytes( 4 ) );
	}
}
