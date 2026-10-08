<?php

namespace Moon\SEO\Modules\General;

use Moon\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaBox {

	private const BOX_ID = 'moon-seo-meta-box';

	private const NONCE_ACTION = 'moon_seo_meta_box';

	private const NONCE_FIELD = 'moon_seo_meta_box_nonce';

	private Editor $editor;

	private SupportedPostTypes $supported_post_types;

	public function __construct( Editor $editor, SupportedPostTypes $supported_post_types ) {
		$this->editor               = $editor;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'register' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	public function register( string $post_type, WP_Post $post ): void {
		if ( ! $this->should_show_on( $post ) ) {
			return;
		}

		add_meta_box(
			self::BOX_ID,
			__( 'Moon SEO', 'moon-seo' ),
			array( $this, 'render' ),
			$post_type,
			'normal',
			'high'
		);
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$title       = get_post_meta( $post->ID, PostMetaKeys::TITLE, true );
		$description = get_post_meta( $post->ID, PostMetaKeys::DESCRIPTION, true );
		$canonical   = get_post_meta( $post->ID, PostMetaKeys::CANONICAL, true );
		$robots      = get_post_meta( $post->ID, PostMetaKeys::ROBOTS, true );
		$robots      = is_array( $robots ) ? $robots : array();
		?>
		<p>
			<label for="moon-seo-title"><?php esc_html_e( 'SEO Title', 'moon-seo' ); ?></label><br />
			<input
				type="text"
				id="moon-seo-title"
				name="moon_seo_title"
				class="widefat"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<p>
			<label for="moon-seo-description"><?php esc_html_e( 'Meta Description', 'moon-seo' ); ?></label><br />
			<textarea
				id="moon-seo-description"
				name="moon_seo_description"
				class="widefat"
				rows="3"
			><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<p>
			<label for="moon-seo-canonical"><?php esc_html_e( 'Canonical URL', 'moon-seo' ); ?></label><br />
			<input
				type="url"
				id="moon-seo-canonical"
				name="moon_seo_canonical"
				class="widefat"
				value="<?php echo esc_attr( $canonical ); ?>"
				placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>"
			/>
		</p>
		<fieldset>
			<legend><?php esc_html_e( 'Robots', 'moon-seo' ); ?></legend>
			<?php foreach ( $this->editor->get_allowed_robots_directives() as $directive ) : ?>
				<label>
					<input
						type="checkbox"
						name="moon_seo_robots[]"
						value="<?php echo esc_attr( $directive ); ?>"
						<?php checked( in_array( $directive, $robots, true ) ); ?>
					/>
					<?php echo esc_html( $directive ); ?>
				</label><br />
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! $this->should_show_on( $post ) ) {
			return;
		}

		if ( isset( $_POST['moon_seo_title'] ) ) {
			$this->store( $post_id, PostMetaKeys::TITLE, sanitize_text_field( wp_unslash( $_POST['moon_seo_title'] ) ) );
		}

		if ( isset( $_POST['moon_seo_description'] ) ) {
			$this->store( $post_id, PostMetaKeys::DESCRIPTION, sanitize_textarea_field( wp_unslash( $_POST['moon_seo_description'] ) ) );
		}

		if ( isset( $_POST['moon_seo_canonical'] ) ) {
			$this->store( $post_id, PostMetaKeys::CANONICAL, esc_url_raw( wp_unslash( $_POST['moon_seo_canonical'] ) ) );
		}

		// An unchecked group is not submitted at all, so a missing field means "no directives".
		$robots = isset( $_POST['moon_seo_robots'] ) && is_array( $_POST['moon_seo_robots'] )
			? wp_unslash( $_POST['moon_seo_robots'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Whitelisted below.
			: array();

		$this->store( $post_id, PostMetaKeys::ROBOTS, $this->editor->sanitize_robots_override( $robots ) );
	}

	/**
	 * Shown only for supported post types, and only where the block editor sidebar is not available.
	 */
	private function should_show_on( WP_Post $post ): bool {
		if ( ! $this->supported_post_types->is_supported( $post->post_type ) ) {
			return false;
		}

		return ! use_block_editor_for_post( $post ) || ! $this->editor->has_sidebar( $post->post_type );
	}

	/**
	 * Empty values remove the meta row instead of storing an empty one.
	 *
	 * @param mixed $value Sanitized value.
	 */
	private function store( int $post_id, string $key, $value ): void {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );

			return;
		}

		update_post_meta( $post_id, $key, $value );
	}
}
