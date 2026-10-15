# Moon SEO

A lightweight, modular WordPress plugin for technical SEO. It prints the SEO tags in `<head>`, serves an XML sitemap, and outputs structured data (JSON-LD). It works with any theme and does not depend on any other plugin.

Moon SEO does not render anything visible on the front end and loads no front-end CSS or JavaScript.

## Features

**General**

- SEO title and meta description with templates and placeholders (`{title}`, `{site_name}`, `{tagline}`, `{separator}`, `{query}`, `{term_title}`). An empty description is generated from the excerpt or the first paragraph.
- Canonical URL and robots directives, with global presets per page type and per-post overrides. The directives are merged into the robots tag WordPress already prints, so there is never a second one.
- Open Graph and Twitter Card tags, and site verification tags (Google, Bing, Yandex).
- Block editor sidebar with a live preview of the title and description exactly as the front end will print them. Content types without block editor support get a meta box instead; the two are never shown together.
- Optional removal of the category and tag base from URLs (old URLs redirect with a 301), and redirection of attachment pages to their parent.

**Sitemap**

- `/sitemap.xml` index, plus one sitemap per content type with pagination (`/post-sitemap.xml`, `/post-sitemap2.xml`, and so on).
- Homepage, posts, pages, categories, tags, authors, date archives, and any public custom post type or taxonomy.
- Accurate `lastmod` only. `priority` and `changefreq` are not output because major search engines ignore them.
- Cached, with automatic invalidation when content changes. Excluded categories and post IDs, and posts marked `noindex` or protected by a password are left out.
- The WordPress core sitemap is turned off and a `Sitemap:` line is added to the virtual `robots.txt`.

**Schema**

- One JSON-LD `@graph` per page with `WebSite`, `Organization`, `BreadcrumbList`, `Article` or `WebPage`, and the primary `ImageObject`.
- No settings page: everything is derived from the site identity and the post itself.

## Requirements

- WordPress 6.9 or later
- PHP 8.0 or later
- Pretty permalinks (not "Plain") are recommended

## Installation

1. Download the plugin archive.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the archive, and activate it.
3. Open **SEO** in the admin menu for the General settings and **SEO → Sitemap** for the sitemap settings.

A fresh install already serves a useful sitemap and meta tags without saving anything.

Run only one SEO plugin at a time. Moon SEO does not detect other SEO plugins, so two of them active together may print duplicate tags.

## Extending

Two public filters are the only supported extension points.

`moon_seo_supported_post_types` decides which post types get per-post SEO fields, title and description templates, and Schema nodes. By default these are `post`, `page`, and every other public post type that has an admin UI, except `attachment`.

```php
add_filter( 'moon_seo_supported_post_types', function ( array $post_types ): array {
	// Treat "docs" as an article.
	$post_types['docs'] = array(
		'content_group' => 'post',
		'schema_node'   => 'article',
	);

	// Leave "event" out entirely.
	unset( $post_types['event'] );

	return $post_types;
} );
```

`moon_seo_module_is_active` switches a module off for a request. The module slugs are `general`, `sitemap`, and `schema`.

```php
add_filter( 'moon_seo_module_is_active', function ( bool $active, string $module_slug ): bool {
	return 'schema' === $module_slug ? false : $active;
}, 10, 2 );
```

Option names, post meta keys, REST routes, and internal classes are not part of the public API.

## Known limitations

- Every page of a sitemap type shares the same `lastmod` in the index.
- The static front page is listed in both the homepage sitemap and the page sitemap.
- Categories or tags hidden from search results in the General settings are still listed in their sitemap. Turn off the matching toggle in the sitemap settings to remove them.
- A parent page that is not public still appears in the breadcrumb trail of its children.
- JSON-LD printed by other plugins or themes is not merged into the Moon SEO graph. Use `moon_seo_module_is_active` to switch Schema off if another plugin provides it.

## Uninstall

Deleting the plugin from the Plugins screen removes its options, per-post SEO fields, sitemap cache, and stored rewrite rules (on every site of a multisite network).

## Development

The JavaScript in `build/` is compiled from `src/` with `@wordpress/scripts`. `build/` is not stored in the repository: run `npm ci` and `npm run build` after cloning, or install a release archive. Node 18.12 or later is required.

```bash
npm ci
npm run build          # compile src/ into build/
npm run lint           # lint the JavaScript and the CSS
npm run check:version  # the version must match in every file that states it
npm run plugin-zip     # build the release archive
npm run check:release  # verify what the archive contains
npm run verify         # all of the above, in order
npm run makepot        # regenerate languages/moon-seo.pot (requires WP-CLI)
npm run makejson       # JSON translations for the scripts, from the .po files (requires WP-CLI)
```

PHP is checked with PHP_CodeSniffer and the WordPress Coding Standards (`composer install`, then `vendor/bin/phpcs`). `phpcs-compat.xml` checks compatibility with the minimum PHP version; it needs PHP_CodeSniffer 4 with PHPCompatibility, which the CI workflow (`.github/workflows/ci.yml`) installs. There is no Composer autoloading at runtime; Composer is only used for development tooling.

To release a new version, change it in the plugin header and in `MOON_SEO_VERSION` (both in `moon-seo.php`) and in `package.json`, then run `npm run verify`.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE.md).
