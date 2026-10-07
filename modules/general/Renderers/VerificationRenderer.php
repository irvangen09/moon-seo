<?php

namespace Moon\SEO\Modules\General\Renderers;

use Moon\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VerificationRenderer implements RendererInterface {

	private const MODULE_SLUG = 'general';

	private const META_NAME_MAP = array(
		'google' => 'google-site-verification',
		'bing'   => 'msvalidate.01',
		'yandex' => 'yandex-verification',
	);

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function init(): void {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	// Each platform is skipped on its own when its field is empty.
	public function output(): void {
		$verification = $this->option_manager->get_section( self::MODULE_SLUG, 'verification' );

		foreach ( self::META_NAME_MAP as $platform => $meta_name ) {
			$code = (string) ( $verification[ $platform ] ?? '' );

			if ( '' === $code ) {
				continue;
			}

			printf( '<meta name="%s" content="%s" />' . "\n", esc_attr( $meta_name ), esc_attr( $code ) );
		}
	}
}
