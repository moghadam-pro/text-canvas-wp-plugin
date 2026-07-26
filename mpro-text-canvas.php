<?php
/**
 * Plugin Name:       MPRO Text Canvas
 * Plugin URI:        https://moghadam.pro/mpro-plugins
 * Description:       An interactive, exportable text canvas for Elementor with inline editing, drag, pinch-to-resize, custom fonts, and transparent/background image export.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Sayid Moghadam
 * Author URI:        https://moghadam.pro
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mpro-text-canvas
 * Requires Plugins:  elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MPRO_TC_VERSION', '1.0.0' );
define( 'MPRO_TC_FILE', __FILE__ );
define( 'MPRO_TC_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPRO_TC_URL', plugin_dir_url( __FILE__ ) );

require_once MPRO_TC_PATH . 'includes/class-mpro-tc-fonts.php';
require_once MPRO_TC_PATH . 'includes/class-mpro-tc-admin.php';

register_activation_hook(
	MPRO_TC_FILE,
	static function() {
		if ( false === get_option( 'mpro_tc_settings', false ) ) {
			add_option( 'mpro_tc_settings', MPRO_TC_Admin::defaults() );
		}
	}
);

final class MPRO_Text_Canvas_Plugin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		MPRO_TC_Admin::init();

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
		add_action( 'admin_notices', array( $this, 'elementor_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MPRO_TC_FILE ), array( $this, 'plugin_action_links' ) );
	}

	public function register_assets() {
		$font_dependencies = MPRO_TC_Fonts::register_google_font_assets();

		wp_register_style(
			'mpro-text-canvas',
			MPRO_TC_URL . 'assets/css/frontend.css',
			$font_dependencies,
			MPRO_TC_VERSION
		);

		wp_register_script(
			'mpro-text-canvas',
			MPRO_TC_URL . 'assets/js/frontend.js',
			array(),
			MPRO_TC_VERSION,
			true
		);

		MPRO_TC_Fonts::attach_uploaded_font_assets( 'mpro-text-canvas' );

		wp_localize_script(
			'mpro-text-canvas',
			'mproTextCanvasGlobal',
			array(
				'fonts'   => MPRO_TC_Fonts::get_frontend_fonts(),
				'palette' => MPRO_TC_Fonts::get_site_palette(),
				'i18n'    => array(
					'fontTitle'       => __( 'Choose a font', 'mpro-text-canvas' ),
					'textColorTitle'  => __( 'Text color', 'mpro-text-canvas' ),
					'backgroundTitle' => __( 'Background color', 'mpro-text-canvas' ),
					'apply'           => __( 'Apply', 'mpro-text-canvas' ),
					'cancel'          => __( 'Cancel', 'mpro-text-canvas' ),
					'maxLayers'       => __( 'You can add up to three text layers.', 'mpro-text-canvas' ),
				),
			)
		);
	}

	public function plugin_action_links( $links ) {
		$settings_url = admin_url( 'admin.php?page=mpro-text-canvas' );
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'mpro-text-canvas' )
			)
		);
		return $links;
	}

	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'mpro',
			array(
				'title' => __( 'MPRO', 'mpro-text-canvas' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	public function register_widget( $widgets_manager ) {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		require_once MPRO_TC_PATH . 'includes/class-mpro-tc-elementor-widget.php';
		$widgets_manager->register( new MPRO_TC_Elementor_Widget() );
	}

	public function elementor_notice() {
		if ( did_action( 'elementor/loaded' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'MPRO Text Canvas requires Elementor to be installed and active.', 'mpro-text-canvas' )
		);
	}
}

add_action(
	'plugins_loaded',
	static function() {
		MPRO_Text_Canvas_Plugin::instance();
	}
);
