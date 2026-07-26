<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MPRO_TC_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'upload_mimes', array( __CLASS__, 'allow_font_mimes' ) );
	}

	public static function defaults() {
		return array(
			'default_text'   => "YOU CAN EDIT THIS TEXT\nWRITE EVERYTHING YOU WANT\nAND MOVE",
			'background_color' => '#ffb700',
			'text_color'       => '#000000',
			'default_font'     => 'Arial, Helvetica, sans-serif',
			'google_fonts'     => array(),
			'uploaded_fonts'   => array(),
		);
	}

	public static function register_menu() {
		if ( ! isset( $GLOBALS['mpro_menu_registered'] ) ) {
			add_menu_page(
				__( 'MPRO Suite', 'mpro-text-canvas' ),
				'MPRO',
				'manage_options',
				'mpro-dashboard',
				array( __CLASS__, 'dashboard_page' ),
				'dashicons-superhero',
				4
			);
			$GLOBALS['mpro_menu_registered'] = true;
		}

		add_submenu_page(
			'mpro-dashboard',
			__( 'Text Canvas Settings', 'mpro-text-canvas' ),
			__( 'Text Canvas', 'mpro-text-canvas' ),
			'manage_options',
			'mpro-text-canvas',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'mpro_tc_options',
			'mpro_tc_settings',
			array(
				'default'           => self::defaults(),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = array(
			'default_text'     => isset( $input['default_text'] ) ? sanitize_textarea_field( $input['default_text'] ) : $defaults['default_text'],
			'background_color' => self::sanitize_color( $input['background_color'] ?? $defaults['background_color'], $defaults['background_color'] ),
			'text_color'       => self::sanitize_color( $input['text_color'] ?? $defaults['text_color'], $defaults['text_color'] ),
			'default_font'     => isset( $input['default_font'] ) ? sanitize_text_field( $input['default_font'] ) : $defaults['default_font'],
			'google_fonts'     => array(),
			'uploaded_fonts'   => array(),
		);

		if ( ! empty( $input['google_fonts'] ) && is_array( $input['google_fonts'] ) ) {
			foreach ( $input['google_fonts'] as $font ) {
				$family = isset( $font['family'] ) ? sanitize_text_field( $font['family'] ) : '';
				if ( '' === $family ) {
					continue;
				}
				$output['google_fonts'][] = array(
					'family'  => $family,
					'weights' => isset( $font['weights'] ) ? preg_replace( '/[^0-9;,]/', '', $font['weights'] ) : '400;700',
				);
			}
		}

		if ( ! empty( $input['uploaded_fonts'] ) && is_array( $input['uploaded_fonts'] ) ) {
			foreach ( $input['uploaded_fonts'] as $font ) {
				$family = isset( $font['family'] ) ? sanitize_text_field( $font['family'] ) : '';
				$url    = isset( $font['url'] ) ? esc_url_raw( $font['url'] ) : '';
				if ( '' === $family || '' === $url ) {
					continue;
				}
				$weight  = isset( $font['weight'] ) ? absint( $font['weight'] ) : 400;
				$formats = array( 'woff2', 'woff', 'truetype', 'opentype' );
				$format  = isset( $font['format'] ) ? sanitize_key( $font['format'] ) : 'woff2';
				if ( ! in_array( $format, $formats, true ) ) {
					$format = 'woff2';
				}
				$output['uploaded_fonts'][] = array(
					'family' => $family,
					'url'    => $url,
					'weight' => min( 900, max( 100, $weight ) ),
					'style'  => ( isset( $font['style'] ) && 'italic' === $font['style'] ) ? 'italic' : 'normal',
					'format' => $format,
				);
			}
		}
		return $output;
	}

	private static function sanitize_color( $value, $fallback ) {
		$value = sanitize_text_field( $value );
		if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) || preg_match( '/^rgba?\([0-9.,\s%]+\)$/', $value ) ) {
			return $value;
		}
		return $fallback;
	}

	public static function allow_font_mimes( $mimes ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $mimes;
		}

		if ( class_exists( 'WP_Font_Utils' ) && is_callable( array( 'WP_Font_Utils', 'get_allowed_font_mime_types' ) ) ) {
			return array_merge( $mimes, \WP_Font_Utils::get_allowed_font_mime_types() );
		}

		$mimes['woff']  = 'font/woff';
		$mimes['woff2'] = 'font/woff2';
		$mimes['ttf']   = 'font/ttf';
		$mimes['otf']   = 'font/otf';
		return $mimes;
	}

	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'mpro-text-canvas' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'mpro-text-canvas-admin', MPRO_TC_URL . 'assets/css/admin.css', array(), MPRO_TC_VERSION );
		wp_enqueue_script( 'mpro-text-canvas-admin', MPRO_TC_URL . 'assets/js/admin.js', array( 'jquery' ), MPRO_TC_VERSION, true );
		wp_localize_script(
			'mpro-text-canvas-admin',
			'mproTCAdmin',
			array(
				'googleFonts' => MPRO_TC_Fonts::google_font_catalog(),
				'mediaTitle'  => __( 'Choose a font file', 'mpro-text-canvas' ),
				'mediaButton' => __( 'Use this font', 'mpro-text-canvas' ),
			)
		);
	}

	public static function dashboard_page() {
		echo '<div class="wrap"><h1>MPRO Suite</h1><p>' . esc_html__( 'Choose an MPRO plugin from the submenu.', 'mpro-text-canvas' ) . '</p></div>';
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = get_option( 'mpro_tc_settings', self::defaults() );
		$options = wp_parse_args( is_array( $options ) ? $options : array(), self::defaults() );
		$catalog = MPRO_TC_Fonts::google_font_catalog();
		?>
		<div class="wrap mpro-tc-admin">
			<h1><?php esc_html_e( 'MPRO Text Canvas', 'mpro-text-canvas' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Set global defaults and manage fonts used by the Elementor Text Canvas widget.', 'mpro-text-canvas' ); ?></p>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'mpro_tc_options' ); ?>
				<datalist id="mpro-tc-google-font-list">
					<?php foreach ( $catalog as $family ) : ?><option value="<?php echo esc_attr( $family ); ?>"></option><?php endforeach; ?>
				</datalist>
				<div class="mpro-tc-admin__card">
					<h2><?php esc_html_e( 'Canvas defaults', 'mpro-text-canvas' ); ?></h2>
					<div class="mpro-tc-admin__grid">
						<label class="mpro-tc-admin__field mpro-tc-admin__field--full">
							<span><?php esc_html_e( 'Default text', 'mpro-text-canvas' ); ?></span>
							<textarea name="mpro_tc_settings[default_text]" rows="5"><?php echo esc_textarea( $options['default_text'] ); ?></textarea>
						</label>
						<label class="mpro-tc-admin__field">
							<span><?php esc_html_e( 'Background color', 'mpro-text-canvas' ); ?></span>
							<input type="color" name="mpro_tc_settings[background_color]" value="<?php echo esc_attr( $options['background_color'] ); ?>">
						</label>
						<label class="mpro-tc-admin__field">
							<span><?php esc_html_e( 'Text color', 'mpro-text-canvas' ); ?></span>
							<input type="color" name="mpro_tc_settings[text_color]" value="<?php echo esc_attr( $options['text_color'] ); ?>">
						</label>
						<label class="mpro-tc-admin__field mpro-tc-admin__field--full">
							<span><?php esc_html_e( 'Default font stack', 'mpro-text-canvas' ); ?></span>
							<input type="text" class="regular-text" name="mpro_tc_settings[default_font]" value="<?php echo esc_attr( $options['default_font'] ); ?>">
							<small><?php esc_html_e( 'Example: Vazirmatn, Tahoma, sans-serif', 'mpro-text-canvas' ); ?></small>
						</label>
					</div>
				</div>

				<div class="mpro-tc-admin__card">
					<div class="mpro-tc-admin__heading-row">
						<div><h2><?php esc_html_e( 'Google Fonts', 'mpro-text-canvas' ); ?></h2><p><?php esc_html_e( 'Add only the families you plan to use. They are loaded with display=swap.', 'mpro-text-canvas' ); ?></p></div>
						<button type="button" class="button" data-mpro-add-google><?php esc_html_e( 'Add Google Font', 'mpro-text-canvas' ); ?></button>
					</div>
					<div data-mpro-google-list>
						<?php foreach ( $options['google_fonts'] as $index => $font ) : ?>
							<?php self::google_font_row( $index, $font, $catalog ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mpro-tc-admin__card">
					<div class="mpro-tc-admin__heading-row">
						<div><h2><?php esc_html_e( 'Uploaded fonts', 'mpro-text-canvas' ); ?></h2><p><?php esc_html_e( 'Upload WOFF2, WOFF, TTF, or OTF files through the WordPress Media Library.', 'mpro-text-canvas' ); ?></p></div>
						<button type="button" class="button" data-mpro-add-upload><?php esc_html_e( 'Add Uploaded Font', 'mpro-text-canvas' ); ?></button>
					</div>
					<div data-mpro-upload-list>
						<?php foreach ( $options['uploaded_fonts'] as $index => $font ) : ?>
							<?php self::uploaded_font_row( $index, $font ); ?>
						<?php endforeach; ?>
					</div>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	private static function google_font_row( $index, $font, $catalog ) {
		?>
		<div class="mpro-tc-font-row" data-font-row>
			<label><span><?php esc_html_e( 'Family', 'mpro-text-canvas' ); ?></span><input type="text" list="mpro-tc-google-font-list" name="mpro_tc_settings[google_fonts][<?php echo esc_attr( $index ); ?>][family]" value="<?php echo esc_attr( $font['family'] ?? '' ); ?>" placeholder="Inter"></label>
			<label><span><?php esc_html_e( 'Weights', 'mpro-text-canvas' ); ?></span><input type="text" name="mpro_tc_settings[google_fonts][<?php echo esc_attr( $index ); ?>][weights]" value="<?php echo esc_attr( $font['weights'] ?? '400;700' ); ?>" placeholder="400;700"></label>
			<button type="button" class="button-link-delete" data-mpro-remove><?php esc_html_e( 'Remove', 'mpro-text-canvas' ); ?></button>
		</div>
		<?php
	}

	private static function uploaded_font_row( $index, $font ) {
		?>
		<div class="mpro-tc-font-row mpro-tc-font-row--upload" data-font-row>
			<label><span><?php esc_html_e( 'Family', 'mpro-text-canvas' ); ?></span><input type="text" name="mpro_tc_settings[uploaded_fonts][<?php echo esc_attr( $index ); ?>][family]" value="<?php echo esc_attr( $font['family'] ?? '' ); ?>"></label>
			<label class="mpro-tc-font-url"><span><?php esc_html_e( 'File URL', 'mpro-text-canvas' ); ?></span><span class="mpro-tc-font-url__control"><input type="url" name="mpro_tc_settings[uploaded_fonts][<?php echo esc_attr( $index ); ?>][url]" value="<?php echo esc_attr( $font['url'] ?? '' ); ?>"><button type="button" class="button" data-mpro-media><?php esc_html_e( 'Choose', 'mpro-text-canvas' ); ?></button></span></label>
			<label><span><?php esc_html_e( 'Weight', 'mpro-text-canvas' ); ?></span><select name="mpro_tc_settings[uploaded_fonts][<?php echo esc_attr( $index ); ?>][weight]"><?php foreach ( range( 100, 900, 100 ) as $weight ) : ?><option value="<?php echo esc_attr( $weight ); ?>" <?php selected( (int) ( $font['weight'] ?? 400 ), $weight ); ?>><?php echo esc_html( $weight ); ?></option><?php endforeach; ?></select></label>
			<label><span><?php esc_html_e( 'Style', 'mpro-text-canvas' ); ?></span><select name="mpro_tc_settings[uploaded_fonts][<?php echo esc_attr( $index ); ?>][style]"><option value="normal" <?php selected( $font['style'] ?? 'normal', 'normal' ); ?>>Normal</option><option value="italic" <?php selected( $font['style'] ?? '', 'italic' ); ?>>Italic</option></select></label>
			<label><span><?php esc_html_e( 'Format', 'mpro-text-canvas' ); ?></span><select name="mpro_tc_settings[uploaded_fonts][<?php echo esc_attr( $index ); ?>][format]"><?php foreach ( array( 'woff2', 'woff', 'truetype', 'opentype' ) as $format ) : ?><option value="<?php echo esc_attr( $format ); ?>" <?php selected( $font['format'] ?? 'woff2', $format ); ?>><?php echo esc_html( $format ); ?></option><?php endforeach; ?></select></label>
			<button type="button" class="button-link-delete" data-mpro-remove><?php esc_html_e( 'Remove', 'mpro-text-canvas' ); ?></button>
		</div>
		<?php
	}
}
