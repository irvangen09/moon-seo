# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- General module: SEO title and meta description templates with placeholders, automatic descriptions, canonical URL, global and per-post robots directives, Open Graph, Twitter Card, and site verification tags.
- Block editor sidebar and document panel with a live preview of the resolved title and description, and a meta box fallback for content types without block editor support.
- Optional removal of the category and tag base from URLs, and redirection of attachment pages to their parent.
- Sitemap module: `/sitemap.xml` index and per-type sitemaps with pagination, `lastmod`, event-driven cache invalidation, and exclusion of categories, post IDs, `noindex` posts, and password-protected posts.
- Schema module: a single JSON-LD `@graph` with `WebSite`, `Organization`, `BreadcrumbList`, `Article`, `WebPage`, and `ImageObject`.
- Public filters `moon_seo_supported_post_types` and `moon_seo_module_is_active`.
- Translation template `languages/moon-seo.pot`.
