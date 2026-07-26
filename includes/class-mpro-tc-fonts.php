<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MPRO_TC_Fonts {
	public static function system_fonts() {
		return array(
			'Arial'               => 'Arial, Helvetica, sans-serif',
			'Helvetica'           => 'Helvetica, Arial, sans-serif',
			'Inter'               => 'Inter, sans-serif',
			'Georgia'             => 'Georgia, serif',
			'Times New Roman'     => '"Times New Roman", Times, serif',
			'Trebuchet MS'        => '"Trebuchet MS", sans-serif',
			'Courier New'         => '"Courier New", monospace',
			'System UI'           => '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'Vazirmatn'           => 'Vazirmatn, Tahoma, sans-serif',
			'Tahoma'              => 'Tahoma, sans-serif',
		);
	}

	public static function google_font_catalog() {
		return array(
			'Anton', 'Archivo', 'Barlow', 'Bebas Neue', 'Bitter', 'Bodoni Moda', 'Cabin', 'Cormorant Garamond',
			'DM Sans', 'DM Serif Display', 'Figtree', 'IBM Plex Sans', 'IBM Plex Serif', 'Inter', 'Josefin Sans',
			'Lato', 'Libre Baskerville', 'Manrope', 'Merriweather', 'Montserrat', 'Noto Sans', 'Noto Sans Arabic',
			'Noto Sans Persian', 'Noto Serif', 'Nunito', 'Open Sans', 'Oswald', 'Outfit', 'Poppins', 'Playfair Display',
			'Raleway', 'Roboto', 'Roboto Condensed', 'Roboto Mono', 'Rubik', 'Source Sans 3', 'Space Grotesk',
			'Ubuntu', 'Vazirmatn', 'Work Sans', 'Zilla Slab'
		);
	}

	public static function settings() {
		$defaults = MPRO_TC_Admin::defaults();
		$options  = get_option( 'mpro_tc_settings', $defaults );
		return wp_parse_args( is_array( $options ) ? $options : array(), $defaults );
	}

	public static function get_font_options() {
		$options = array();
		foreach ( self::system_fonts() as $name => $stack ) {
			$options[ $stack ] = $name . ' — System';
		}

		$settings = self::settings();
		foreach ( $settings['google_fonts'] as $font ) {
			if ( empty( $font['family'] ) ) {
				continue;
			}
			$options[ self::font_stack( $font['family'] ) ] = $font['family'] . ' — Google';
		}
		foreach ( $settings['uploaded_fonts'] as $font ) {
			if ( empty( $font['family'] ) || empty( $font['url'] ) ) {
				continue;
			}
			$options[ self::font_stack( $font['family'] ) ] = $font['family'] . ' — Uploaded';
		}

		return $options;
	}

	public static function get_frontend_fonts() {
		$result = array();
		foreach ( self::get_font_options() as $stack => $label ) {
			$result[] = array(
				'family' => $stack,
				'label'  => $label,
			);
		}
		return $result;
	}

	public static function font_stack( $family ) {
		$family = trim( wp_strip_all_tags( $family ) );
		return '"' . str_replace( '"', '', $family ) . '", sans-serif';
	}

	public static function register_google_font_assets() {
		$settings = self::settings();
		$families = array();

		foreach ( $settings['google_fonts'] as $font ) {
			$family = isset( $font['family'] ) ? trim( $font['family'] ) : '';
			if ( '' === $family ) {
				continue;
			}
			$weights        = isset( $font['weights'] ) ? preg_replace( '/[^0-9;,]/', '', $font['weights'] ) : '400;700';
			$weights        = $weights ? str_replace( ',', ';', $weights ) : '400;700';
			$encoded_family = str_replace( '%20', '+', rawurlencode( $family ) );
			$families[]     = 'family=' . $encoded_family . ':wght@' . $weights;
		}

		if ( ! $families ) {
			return array();
		}

		$url = 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap';
		wp_register_style( 'mpro-text-canvas-google-fonts', esc_url_raw( $url ), array(), null );
		return array( 'mpro-text-canvas-google-fonts' );
	}

	public static function attach_uploaded_font_assets( $style_handle ) {
		$settings = self::settings();
		$css      = '';
		foreach ( $settings['uploaded_fonts'] as $font ) {
			if ( empty( $font['family'] ) || empty( $font['url'] ) ) {
				continue;
			}
			$family = str_replace( array( '"', "'" ), '', $font['family'] );
			$weight = isset( $font['weight'] ) ? absint( $font['weight'] ) : 400;
			$style  = ( isset( $font['style'] ) && 'italic' === $font['style'] ) ? 'italic' : 'normal';
			$format = isset( $font['format'] ) ? sanitize_key( $font['format'] ) : self::format_from_url( $font['url'] );
			$css   .= sprintf(
				"@font-face{font-family:'%s';src:url('%s') format('%s');font-weight:%d;font-style:%s;font-display:swap;}\n",
				esc_attr( $family ),
				esc_url_raw( $font['url'] ),
				esc_attr( $format ),
				$weight,
				$style
			);
		}
		if ( $css ) {
			wp_add_inline_style( $style_handle, $css );
		}
	}

	private static function format_from_url( $url ) {
		$ext = strtolower( pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$map = array(
			'woff2' => 'woff2',
			'woff'  => 'woff',
			'ttf'   => 'truetype',
			'otf'   => 'opentype',
		);
		return isset( $map[ $ext ] ) ? $map[ $ext ] : 'woff2';
	}

	public static function get_site_palette() {
		$palette  = array();
		$settings = self::settings();
		$palette[] = array( 'name' => __( 'Default background', 'mpro-text-canvas' ), 'value' => $settings['background_color'] );
		$palette[] = array( 'name' => __( 'Default text', 'mpro-text-canvas' ), 'value' => $settings['text_color'] );
		$palette[] = array( 'name' => __( 'White', 'mpro-text-canvas' ), 'value' => '#ffffff' );
		$palette[] = array( 'name' => __( 'Black', 'mpro-text-canvas' ), 'value' => '#000000' );

		if ( class_exists( '\Elementor\Plugin' ) ) {
			try {
				$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
				if ( $kit && method_exists( $kit, 'get_settings_for_display' ) ) {
					foreach ( array( 'system_colors', 'custom_colors' ) as $setting_key ) {
						$colors = $kit->get_settings_for_display( $setting_key );
						if ( is_array( $colors ) ) {
							foreach ( $colors as $color ) {
								if ( empty( $color['_id'] ) || empty( $color['color'] ) ) {
									continue;
								}
								$palette[] = array(
									'name'  => ! empty( $color['title'] ) ? $color['title'] : $color['_id'],
									'value' => $color['color'],
								);
							}
						}
					}
				}
			} catch ( Throwable $e ) {
				// Elementor internals can differ between versions; defaults remain available.
			}
		}

		$unique = array();
		foreach ( $palette as $item ) {
			$key = strtolower( $item['value'] );
			$unique[ $key ] = $item;
		}
		return array_values( $unique );
	}
}
