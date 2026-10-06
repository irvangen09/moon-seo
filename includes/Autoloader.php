<?php

namespace Moon\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {

	private string $prefix;

	private string $base_dir;

	public function __construct( string $prefix, string $base_dir ) {
		$this->prefix   = trim( $prefix, '\\' ) . '\\';
		$this->base_dir = rtrim( $base_dir, '/\\' ) . '/';
	}

	public function register(): void {
		spl_autoload_register( array( $this, 'load' ) );
	}

	/**
	 * Maps Moon\SEO\Services\OptionManager to {base_dir}/Services/OptionManager.php.
	 *
	 * A class outside the prefix, or without a matching file, is left to the
	 * next autoloader in the chain.
	 */
	public function load( string $class_name ): void {
		if ( ! str_starts_with( $class_name, $this->prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $this->prefix ) );
		$file     = $this->base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
}
