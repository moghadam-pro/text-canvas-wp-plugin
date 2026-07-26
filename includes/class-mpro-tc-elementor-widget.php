<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPRO_TC_Elementor_Widget extends \Elementor\Widget_Base {
	public function get_name() {
		return 'mpro-text-canvas';
	}

	public function get_title() {
		return __( 'MPRO Text Canvas', 'mpro-text-canvas' );
	}

	public function get_icon() {
		return 'eicon-edit';
	}

	public function get_categories() {
		return array( 'mpro' );
	}

	public function get_keywords() {
		return array( 'text', 'canvas', 'story', 'export', 'instagram', 'mpro' );
	}

	public function get_script_depends() {
		return array( 'mpro-text-canvas' );
	}

	public function get_style_depends() {
		return array( 'mpro-text-canvas' );
	}

	protected function register_controls() {
		$defaults     = MPRO_TC_Admin::defaults();
		$admin        = get_option( 'mpro_tc_settings', $defaults );
		$admin        = wp_parse_args( is_array( $admin ) ? $admin : array(), $defaults );
		$font_options = MPRO_TC_Fonts::get_font_options();

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'mpro-text-canvas' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'default_text',
			array(
				'label'       => __( 'Initial text 1', 'mpro-text-canvas' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 5,
				'default'     => 'Curiosity',
				'placeholder' => __( 'Write something…', 'mpro-text-canvas' ),
			)
		);

		$this->add_control(
			'default_text_2',
			array(
				'label'   => __( 'Initial text 2', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => 'NEW Products.',
			)
		);

		$this->add_control(
			'default_text_3',
			array(
				'label'   => __( 'Initial text 3', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => 'Exploration',
			)
		);

		$this->add_control(
			'added_text',
			array(
				'label'       => __( 'New layer text', 'mpro-text-canvas' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => $admin['default_text'],
				'description' => __( 'Used after a layer is deleted and Add Text becomes available.', 'mpro-text-canvas' ),
			)
		);

		$this->add_control(
			'button_labels_heading',
			array(
				'label'     => __( 'Button labels', 'mpro-text-canvas' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$labels = array(
			'reset_label'              => array( __( 'Reset', 'mpro-text-canvas' ), 'RESET' ),
			'add_label'                => array( __( 'Add text', 'mpro-text-canvas' ), 'ADD TEXT' ),
			'export_transparent_label' => array( __( 'Transparent export — desktop', 'mpro-text-canvas' ), 'EXPORT TRANSPARENT' ),
			'export_transparent_mobile'=> array( __( 'Transparent export — mobile', 'mpro-text-canvas' ), 'EXPORT PNG' ),
			'export_bg_label'          => array( __( 'Background export — desktop', 'mpro-text-canvas' ), 'EXPORT WITH BG' ),
			'export_bg_mobile'         => array( __( 'Background export — mobile', 'mpro-text-canvas' ), 'EXPORT JPG' ),
			'full_page_label'          => array( __( 'Full-page tool', 'mpro-text-canvas' ), 'OPEN FULL TOOL' ),
		);
		foreach ( $labels as $key => $label ) {
			$this->add_control(
				$key,
				array(
					'label'   => $label[0],
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => $label[1],
				)
			);
		}
		$this->end_controls_section();

		$this->start_controls_section(
			'frame_style',
			array(
				'label' => __( 'Canvas', 'mpro-text-canvas' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'background_color',
			array(
				'label'   => __( 'Background color', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $admin['background_color'],
				'global'  => array(),
				'selectors' => array(
					'{{WRAPPER}} .mpro-tc__frame' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_height',
			array(
				'label'      => __( 'Height', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 260, 'max' => 1000 ),
					'vh' => array( 'min' => 30, 'max' => 100 ),
				),
				'default'        => array( 'unit' => 'px', 'size' => 500 ),
				'tablet_default' => array( 'unit' => 'px', 'size' => 500 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 746 ),
				'selectors'      => array(
					'{{WRAPPER}} .mpro-tc__frame' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_radius',
			array(
				'label'      => __( 'Corner radius', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'        => array( 'unit' => 'px', 'size' => 44 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'      => array(
					'{{WRAPPER}} .mpro-tc__frame' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'frame_padding',
			array(
				'label'      => __( 'Inner padding', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array( 'top' => 32, 'right' => 32, 'bottom' => 32, 'left' => 32, 'unit' => 'px', 'isLinked' => true ),
				'mobile_default' => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20, 'unit' => 'px', 'isLinked' => true ),
				'selectors'  => array(
					'{{WRAPPER}} .mpro-tc__frame' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'text_style',
			array(
				'label' => __( 'Default text style', 'mpro-text-canvas' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'   => __( 'Text color', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => $admin['text_color'],
				'global'  => array(),
				'selectors' => array(
					'{{WRAPPER}} .mpro-tc__text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'font_family',
			array(
				'label'   => __( 'Font family', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $font_options,
				'default' => isset( $font_options[ $admin['default_font'] ] ) ? $admin['default_font'] : array_key_first( $font_options ),
			)
		);

		$this->add_control(
			'font_weight',
			array(
				'label'   => __( 'Weight', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '700',
				'options' => array(
					'100' => '100', '200' => '200', '300' => '300', '400' => '400', '500' => '500',
					'600' => '600', '700' => '700', '800' => '800', '900' => '900',
				),
			)
		);

		$this->add_responsive_control(
			'font_size',
			array(
				'label'      => __( 'Font size', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 10, 'max' => 160 ) ),
				'default'        => array( 'unit' => 'px', 'size' => 24 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 24 ),
			)
		);

		$this->add_responsive_control(
			'line_height',
			array(
				'label'      => __( 'Line height', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'em' ),
				'range'      => array( 'em' => array( 'min' => 0.8, 'max' => 2, 'step' => 0.05 ) ),
				'default'    => array( 'unit' => 'em', 'size' => 1.45 ),
			)
		);

		$this->add_control(
			'text_align',
			array(
				'label'   => __( 'Alignment', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'default' => 'right',
				'options' => array(
					'left'   => array( 'title' => __( 'Left', 'mpro-text-canvas' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'mpro-text-canvas' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'mpro-text-canvas' ), 'icon' => 'eicon-text-align-right' ),
				),
			)
		);

		$this->add_responsive_control(
			'initial_x',
			array(
				'label'      => __( 'Initial horizontal position', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'        => array( 'unit' => '%', 'size' => 68 ),
				'mobile_default' => array( 'unit' => '%', 'size' => 52 ),
			)
		);

		$this->add_responsive_control(
			'initial_y',
			array(
				'label'      => __( 'Initial vertical position', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
				'default'        => array( 'unit' => '%', 'size' => 84 ),
				'mobile_default' => array( 'unit' => '%', 'size' => 80 ),
			)
		);

		$this->add_responsive_control(
			'initial_width',
			array(
				'label'      => __( 'Initial text width', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 15, 'max' => 100 ) ),
				'default'        => array( 'unit' => '%', 'size' => 59 ),
				'mobile_default' => array( 'unit' => '%', 'size' => 78 ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'layout_style',
			array(
				'label' => __( 'Toolbar and layout', 'mpro-text-canvas' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'panel_width',
			array(
				'label'      => __( 'Desktop toolbar width', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 80, 'max' => 450 ), '%' => array( 'min' => 10, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 120 ),
				'selectors'  => array( '{{WRAPPER}} .mpro-tc' => '--mpro-tc-panel-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'layout_gap',
			array(
				'label'      => __( 'Gap', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'        => array( 'unit' => 'px', 'size' => 24 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 16 ),
				'selectors'      => array( '{{WRAPPER}} .mpro-tc' => '--mpro-tc-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'button_background',
			array(
				'label'   => __( 'Button background', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#f5f4ef',
				'global'  => array(),
				'selectors' => array( '{{WRAPPER}} .mpro-tc__action' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_color',
			array(
				'label'   => __( 'Button text', 'mpro-text-canvas' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '#000000',
				'global'  => array(),
				'selectors' => array( '{{WRAPPER}} .mpro-tc__action' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'button_radius',
			array(
				'label'      => __( 'Button radius', 'mpro-text-canvas' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'  => array( '{{WRAPPER}} .mpro-tc__action' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}

	private function slider_value( $settings, $key, $device, $fallback ) {
		$field = $key;
		if ( 'desktop' !== $device ) {
			$field = $key . '_' . $device;
		}
		if ( isset( $settings[ $field ]['size'] ) && '' !== $settings[ $field ]['size'] ) {
			return (float) $settings[ $field ]['size'];
		}
		if ( 'desktop' !== $device && isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
			return (float) $settings[ $key ]['size'];
		}
		return $fallback;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$fonts    = MPRO_TC_Fonts::get_frontend_fonts();
		$config = array(
			'defaultText' => $settings['default_text'],
			'addedText'   => $settings['added_text'],
			'maxLayers'   => 3,
			'initialTexts' => array( $settings['default_text'], $settings['default_text_2'], $settings['default_text_3'] ),
			'background'  => $settings['background_color'] ?: '#ffb700',
			'textColor'   => $settings['text_color'] ?: '#000000',
			'fontFamily'  => $settings['font_family'],
			'fontWeight'  => $settings['font_weight'],
			'lineHeight'  => $this->slider_value( $settings, 'line_height', 'desktop', 1.45 ),
			'textAlign'   => $settings['text_align'],
			'desktop'     => array(
				'x'        => $this->slider_value( $settings, 'initial_x', 'desktop', 68 ) / 100,
				'y'        => $this->slider_value( $settings, 'initial_y', 'desktop', 84 ) / 100,
				'width'    => $this->slider_value( $settings, 'initial_width', 'desktop', 59 ) / 100,
				'fontSize' => $this->slider_value( $settings, 'font_size', 'desktop', 24 ),
				'layers'   => array(
					array( 'x' => 0.806, 'y' => 0.508, 'width' => 0.191, 'fontSize' => 32, 'rotation' => 5 ),
					array( 'x' => 0.51, 'y' => 0.718, 'width' => 0.781, 'fontSize' => 62, 'rotation' => 5 ),
					array( 'x' => 0.732, 'y' => 0.599, 'width' => 0.344, 'fontSize' => 50, 'rotation' => 5 ),
				),
			),
			'tablet'      => array(
				'x'        => $this->slider_value( $settings, 'initial_x', 'tablet', 68 ) / 100,
				'y'        => $this->slider_value( $settings, 'initial_y', 'tablet', 84 ) / 100,
				'width'    => $this->slider_value( $settings, 'initial_width', 'tablet', 59 ) / 100,
				'fontSize' => $this->slider_value( $settings, 'font_size', 'tablet', 24 ),
			),
			'mobile'      => array(
				'x'        => $this->slider_value( $settings, 'initial_x', 'mobile', 52 ) / 100,
				'y'        => $this->slider_value( $settings, 'initial_y', 'mobile', 80 ) / 100,
				'width'    => $this->slider_value( $settings, 'initial_width', 'mobile', 78 ) / 100,
				'fontSize' => $this->slider_value( $settings, 'font_size', 'mobile', 24 ),
				'layers'   => array(
					array( 'x' => 0.70, 'y' => 0.79, 'width' => 0.50, 'fontSize' => 32, 'rotation' => 5 ),
					array( 'x' => 0.24, 'y' => 0.42, 'width' => 0.95, 'fontSize' => 40, 'rotation' => -90 ),
					array( 'x' => 0.55, 'y' => 0.87, 'width' => 0.84, 'fontSize' => 48, 'rotation' => 5 ),
				),
			),
			'fonts'        => $fonts,
			'labels'       => array(
				'reset'              => $settings['reset_label'],
				'add'                => $settings['add_label'],
				'transparentDesktop' => $settings['export_transparent_label'],
				'transparentMobile'  => $settings['export_transparent_mobile'],
				'backgroundDesktop'  => $settings['export_bg_label'],
				'backgroundMobile'   => $settings['export_bg_mobile'],
				'fullPage'           => $settings['full_page_label'],
			),
		);

		$this->add_render_attribute( 'wrapper', 'class', 'mpro-tc' );
		$this->add_render_attribute( 'wrapper', 'data-mpro-config', wp_json_encode( $config ) );
		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<div class="mpro-tc__layout">
				<div class="mpro-tc__toolbar" aria-label="<?php esc_attr_e( 'Text canvas controls', 'mpro-text-canvas' ); ?>">
					<div class="mpro-tc__tools">
						<button class="mpro-tc__tool mpro-tc__tool--background" type="button" data-mpro-tool="background" aria-label="<?php esc_attr_e( 'Choose background color', 'mpro-text-canvas' ); ?>"><span class="mpro-tc__swatch" aria-hidden="true"></span></button>
						<button class="mpro-tc__tool mpro-tc__tool--text" type="button" data-mpro-tool="text-color" aria-label="<?php esc_attr_e( 'Choose text color', 'mpro-text-canvas' ); ?>"><span class="mpro-tc__swatch" aria-hidden="true"></span></button>
						<button class="mpro-tc__tool mpro-tc__tool--font" type="button" data-mpro-tool="font" aria-label="<?php esc_attr_e( 'Choose font', 'mpro-text-canvas' ); ?>">Aa</button>
						<button class="mpro-tc__tool mpro-tc__tool--menu" type="button" data-mpro-menu-toggle aria-label="<?php esc_attr_e( 'Open actions', 'mpro-text-canvas' ); ?>" aria-haspopup="true" aria-expanded="false"><span aria-hidden="true">•••</span></button>
						<div class="mpro-tc__actions" data-mpro-menu hidden>
							<button type="button" class="mpro-tc__action" data-mpro-action="reset"><?php echo esc_html( $settings['reset_label'] ); ?></button>
							<button type="button" class="mpro-tc__action" data-mpro-action="add"><?php echo esc_html( $settings['add_label'] ); ?></button>
							<button type="button" class="mpro-tc__action" data-mpro-action="export-transparent"><span class="mpro-tc__label--desktop"><?php echo esc_html( $settings['export_transparent_label'] ); ?></span><span class="mpro-tc__label--mobile"><?php echo esc_html( $settings['export_transparent_mobile'] ); ?></span></button>
							<button type="button" class="mpro-tc__action" data-mpro-action="export-background"><span class="mpro-tc__label--desktop"><?php echo esc_html( $settings['export_bg_label'] ); ?></span><span class="mpro-tc__label--mobile"><?php echo esc_html( $settings['export_bg_mobile'] ); ?></span></button>
							<a class="mpro-tc__action mpro-tc__action--link" href="https://moghadam.pro/online-story-font"><?php echo esc_html( $settings['full_page_label'] ); ?></a>
						</div>
					</div>

					<div class="mpro-tc__arrow" aria-hidden="true">
						<img src="<?php echo esc_url( MPRO_TC_URL . 'assets/images/arrow.svg' ); ?>" alt="" width="120" height="120">
					</div>

				</div>

				<div class="mpro-tc__frame" data-mpro-frame role="application" aria-label="<?php esc_attr_e( 'Editable text canvas', 'mpro-text-canvas' ); ?>">
					<div class="mpro-tc__stage" data-mpro-stage></div>
				</div>
			</div>

			<div class="mpro-tc__modal" data-mpro-modal hidden aria-hidden="true">
				<div class="mpro-tc__modal-backdrop" data-mpro-modal-close></div>
				<div class="mpro-tc__dialog" role="dialog" aria-modal="true" aria-labelledby="mpro-tc-dialog-title-<?php echo esc_attr( $this->get_id() ); ?>">
					<div class="mpro-tc__dialog-header">
						<h3 id="mpro-tc-dialog-title-<?php echo esc_attr( $this->get_id() ); ?>" data-mpro-modal-title></h3>
						<button type="button" class="mpro-tc__close" data-mpro-modal-close aria-label="<?php esc_attr_e( 'Close', 'mpro-text-canvas' ); ?>">×</button>
					</div>
					<div class="mpro-tc__dialog-body" data-mpro-modal-body></div>
				</div>
			</div>
			<div class="mpro-tc__status" data-mpro-status role="status" aria-live="polite"></div>
		</div>
		<?php
	}
}
