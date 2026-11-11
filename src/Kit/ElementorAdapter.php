<?php
/**
 * Elementor adapter trait (element kit, shared with BuddyBuilder).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, no plugin-specific names.
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@677b5ac (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * Use in any Elementor widget class (the generic ElementorWidget or an existing widget
 * with its own controls): implement kit_element(), then call kit_register_controls()
 * from register_controls() (optional) and kit_render() from render().
 */
trait ElementorAdapter {

	/**
	 * The element this widget adapts.
	 *
	 * @return Element|null
	 */
	abstract protected function kit_element();

	/**
	 * Content controls generated from element.json: a "basic" section and a collapsed "advanced" one.
	 * Attribute keys are the control keys, so saved values keep working when the schema grows.
	 */
	protected function kit_register_controls() {
		$element = $this->kit_element();
		if ( ! $element ) {
			return;
		}

		$labels  = $element->kit()->config( 'labels' );
		$schema  = $element->schema();
		$panels  = array(
			'basic'    => array(),
			'advanced' => array(),
		);
		$default = Schema::defaults( $schema );

		foreach ( Schema::attributes( $schema ) as $key => $def ) {
			if ( ( isset( $def['control'] ) && 'none' === $def['control'] ) || ( isset( $def['elementor'] ) && false === $def['elementor'] ) ) {
				continue;
			}
			$panel                    = isset( $def['panel'] ) && 'advanced' === $def['panel'] ? 'advanced' : 'basic';
			$panels[ $panel ][ $key ] = self::kit_control( $element, $def, $default[ $key ] );
		}

		foreach ( $panels as $panel => $controls ) {
			if ( ! $controls ) {
				continue;
			}
			$this->start_controls_section( 'kit_' . $panel, array( 'label' => $labels[ $panel ] ) );
			foreach ( $controls as $key => $control ) {
				$this->add_control( $key, $control );
			}
			$this->end_controls_section();
		}
	}

	/**
	 * One Elementor control from an attribute definition.
	 *
	 * @param Element $element       Element.
	 * @param array   $def           Attribute definition.
	 * @param mixed   $default_value Typed default.
	 * @return array
	 */
	protected static function kit_control( Element $element, array $def, $default_value ) {
		$kit     = $element->kit();
		$type    = isset( $def['control'] ) ? $def['control'] : Element::default_control( $def );
		$options = array();

		foreach ( isset( $def['options'] ) ? $element->translated_options( $def['options'] ) : array() as $option ) {
			$options[ $option['value'] ] = $option['label'];
		}

		$control = array(
			'label'   => $kit->t( isset( $def['label'] ) ? $def['label'] : '' ),
			'default' => $default_value,
		);
		if ( ! empty( $def['help'] ) ) {
			$control['description'] = $kit->t( $def['help'] );
		}

		switch ( $type ) {
			case 'toggle':
				$control['type']         = 'switcher';
				$control['return_value'] = 'yes';
				$control['default']      = $default_value ? 'yes' : '';
				break;
			case 'select':
				$control['type']    = 'select';
				$control['options'] = $options;
				break;
			case 'multiselect':
				$control['type']        = 'select2';
				$control['multiple']    = true;
				$control['options']     = $options;
				$control['label_block'] = true;
				break;
			case 'number':
			case 'range':
				$control['type'] = 'number';
				foreach ( array( 'min', 'max', 'step' ) as $opt ) {
					if ( isset( $def[ $opt ] ) ) {
						$control[ $opt ] = $def[ $opt ];
					}
				}
				break;
			case 'textarea':
				$control['type'] = 'textarea';
				break;
			case 'url':
				$control['type']       = 'text';
				$control['input_type'] = 'url';
				break;
			default:
				$control['type']        = 'text';
				$control['label_block'] = true;
		}

		return $control;
	}

	/**
	 * Widget settings typed by the schema (Elementor "yes"/"" and numeric strings included).
	 *
	 * @return array
	 */
	protected function kit_values() {
		$element = $this->kit_element();

		return $element ? $element->normalize( (array) $this->get_settings_for_display() ) : array();
	}

	/**
	 * Render the element. "inner" wrap mode prints the kit wrapper div; "widget" mode prints
	 * the bare markup (the classes go on the widget wrapper, see kit_add_wrapper_classes()).
	 */
	protected function kit_render() {
		$element = $this->kit_element();
		if ( ! $element ) {
			return;
		}

		$kit     = $element->kit();
		$attrs   = $this->kit_values();
		$preview = class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() );
		$ctx     = $kit->context( $element, $attrs, $this, $preview );
		$html    = $element->render( $attrs, $ctx );

		if ( '' === $html ) {
			if ( ! $ctx->is_preview() ) {
				return;
			}
			$html = $kit->empty_preview( $element, $ctx );
		}

		if ( 'widget' === $kit->config( 'elementor_wrap' ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render.php.
			return;
		}

		echo '<div class="' . esc_attr( $kit->classes( $element ) ) . '">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render.php.
	}

	/**
	 * Add the kit classes to the widget wrapper ("widget" wrap mode). Call from add_render_attributes().
	 */
	protected function kit_add_wrapper_classes() {
		$element = $this->kit_element();

		if ( $element && 'widget' === $element->kit()->config( 'elementor_wrap' ) ) {
			$this->add_render_attribute( '_wrapper', 'class', $element->kit()->classes( $element ) );
		}
	}

	/**
	 * Style handles for get_style_depends().
	 *
	 * @return string[]
	 */
	protected function kit_style_depends() {
		$element = $this->kit_element();

		return $element && $element->style_handle() ? array( $element->style_handle() ) : array();
	}
}
