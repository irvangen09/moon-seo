// Must match PostMetaKeys.php exactly.
export const META_KEY_TITLE = '_moon_seo_title';
export const META_KEY_DESCRIPTION = '_moon_seo_description';
export const META_KEY_CANONICAL = '_moon_seo_canonical';
export const META_KEY_ROBOTS = '_moon_seo_robots';

export const TITLE_VARIABLES = [ 'title', 'separator', 'site_name', 'tagline' ];
export const DESCRIPTION_VARIABLES = [ 'title', 'site_name', 'tagline' ];

// Must match Editor.php. "index" and "follow" are the crawler defaults and are never stated.
export const ROBOTS_DIRECTIVES = [
	'noindex',
	'nofollow',
	'noarchive',
	'nosnippet',
	'noimageindex',
];

// Visual guides for the author, not validation.
export const TITLE_MAX_LENGTH = 60;
export const DESCRIPTION_MAX_LENGTH = 160;
