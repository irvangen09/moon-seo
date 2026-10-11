<?php

namespace Moon\SEO\Modules\Sitemap\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HomepageProvider implements ProviderInterface {

	public function count(): int {
		return 1;
	}

	public function get_lastmod(): ?string {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );

			if ( $front_page_id > 0 ) {
				$time = get_post_modified_time( 'c', true, $front_page_id );

				if ( false !== $time ) {
					return $time;
				}
			}
		}

		$recent = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => 'publish',
				'orderby'     => 'modified',
				'order'       => 'DESC',
			)
		);

		if ( empty( $recent ) ) {
			return null;
		}

		$time = get_post_modified_time( 'c', true, $recent[0] );

		return false !== $time ? $time : null;
	}

	public function get_entries( int $offset, int $limit ): array {
		if ( $offset > 0 || $limit < 1 ) {
			return array();
		}

		return array(
			array(
				'loc'     => home_url( '/' ),
				'lastmod' => $this->get_lastmod(),
			),
		);
	}
}
