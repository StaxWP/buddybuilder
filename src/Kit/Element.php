<?php
/**
 * One element: schema, assets and the single PHP renderer (element kit, shared with BuddyBuilder).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, no plugin-specific names.
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@bedf241 (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * Element folder: element.json (schema), render.php (markup), optional style.css and view.js.
 */
final class Element {

	/**
	 * Owning kit.
	 *
	 * @var Kit
	 */
	private $kit;

	/**
	 * Folder.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * Schema as read from element.json.
	 *
	 * @var array
	 */
	private $schema;

	/**
	 * Schema with "optionsFrom" resolved (lazy).
	 *
	 * @var array|null
	 */
	private $resolved;

	/**
	 * Constructor.
	 *
	 * @param Kit    $kit    Kit.
	 * @param string $dir    Element folder.
	 * @param array  $schema Schema.
	 */
	public function __construct( Kit $kit, $dir, array $schema ) {
		$this->kit    = $kit;
		$this->dir    = untrailingslashit( $dir );
		$this->schema = $schema;
	}

	/**
	 * Element name (folder and schema name).
	 *
	 * @return string
	 */
	public function name() {
		return (string) $this->schema['name'];
	}

	/**
	 * Block name: <namespace>/<name>.
	 *
	 * @return string
	 */
	public function block_name() {
		return $this->kit->config( 'namespace' ) . '/' . $this->name();
	}

	/**
	 * Owning kit.
	 *
	 * @return Kit
	 */
	public function kit() {
		return $this->kit;
	}

	/**
	 * Folder path.
	 *
	 * @return string
	 */
	public function dir() {
		return $this->dir;
	}

	/**
	 * Schema with "optionsFrom" resolved to "options".
	 *
	 * @return array
	 */
	public function schema() {
		if ( null === $this->resolved ) {
			$schema = $this->schema;
			foreach ( Schema::attributes( $schema ) as $key => $def ) {
				if ( ! empty( $def['optionsFrom'] ) ) {
					$schema['attributes'][ $key ]['options'] = $this->kit->options( $def['optionsFrom'] );
				} elseif ( isset( $def['options'] ) ) {
					$schema['attributes'][ $key ]['options'] = Kit::option_list( (array) $def['options'] );
				}
			}
			$this->resolved = $schema;
		}

		return $this->resolved;
	}

	/**
	 * Typed attribute values (defaults filled, unknown keys dropped).
	 *
	 * @param array $values Raw block attributes or Elementor settings.
	 * @return array
	 */
	public function normalize( array $values ) {
		return Schema::normalize( $this->schema(), $values );
	}

	/**
	 * Translated title.
	 *
	 * @return string
	 */
	public function title() {
		return $this->kit->t( $this->schema['title'] );
	}

	/**
	 * Style handle, or '' when the element has no style.css.
	 *
	 * @return string
	 */
	public function style_handle() {
		return empty( $this->schema['style'] ) ? '' : $this->kit->config( 'namespace' ) . '-' . $this->name();
	}

	/**
	 * Script module id of the front-end view script, or ''.
	 *
	 * @return string
	 */
	public function view_module_id() {
		return empty( $this->schema['viewScriptModule'] ) ? '' : $this->kit->config( 'namespace' ) . '/' . $this->name() . '/view';
	}

	/**
	 * Register style and view module (not enqueued: render() and the block registration do that).
	 */
	public function register_assets() {
		$url = trailingslashit( $this->kit->config( 'url' ) ) . basename( $this->dir ) . '/';
		$ver = $this->kit->config( 'version' );

		if ( $this->style_handle() ) {
			wp_register_style( $this->style_handle(), $url . $this->schema['style'], array(), $ver );
			wp_style_add_data( $this->style_handle(), 'path', $this->dir . '/' . $this->schema['style'] );
		}

		if ( $this->view_module_id() && function_exists( 'wp_register_script_module' ) ) {
			$deps = isset( $this->schema['viewScriptModuleDeps'] ) ? (array) $this->schema['viewScriptModuleDeps'] : array();
			wp_register_script_module( $this->view_module_id(), $url . $this->schema['viewScriptModule'], $deps, $ver );
		}
	}

	/**
	 * The single markup source of the element. render.php echoes; it sees $attrs, $ctx and $element.
	 *
	 * @param array   $attrs Attribute values (normalized here).
	 * @param Context $ctx   Context.
	 * @return string Markup without the builder wrapper ('' = nothing to show).
	 */
	public function render( array $attrs, Context $ctx ) {
		$attrs = $this->normalize( $attrs );

		$html = self::include_render( $this->dir . '/render.php', $attrs, $ctx, $this );
		$html = trim( $html );

		if ( '' === $html ) {
			return '';
		}

		$this->enqueue();

		if ( ! empty( $this->schema['interactive'] ) && function_exists( 'wp_interactivity_process_directives' ) ) {
			// Same server-side directive pass in both builders, so the HTML is identical.
			$html = wp_interactivity_process_directives( $html );
		}

		/**
		 * Markup of one element, before the builder wrapper ("<prefix>_render").
		 *
		 * @param string  $html    Markup.
		 * @param Element $element Element.
		 * @param array   $attrs   Attributes.
		 * @param Context $ctx     Context.
		 */
		return (string) apply_filters( $this->kit->config( 'prefix' ) . '_render', $html, $this, $attrs, $ctx ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefix from the config.
	}

	/**
	 * Enqueue the element assets (late enqueue is fine: styles print in the footer, modules too).
	 */
	public function enqueue() {
		if ( $this->style_handle() ) {
			wp_enqueue_style( $this->style_handle() );
		}
		if ( $this->view_module_id() && function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module( $this->view_module_id() );
		}
	}

	/**
	 * Run render.php in its own scope.
	 *
	 * @param string  $file    render.php.
	 * @param array   $attrs   Attributes.
	 * @param Context $ctx     Context.
	 * @param Element $element Element.
	 * @return string
	 */
	private static function include_render( $file, array $attrs, Context $ctx, Element $element ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- used by render.php.
		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		$returned = include $file;
		$html     = (string) ob_get_clean();

		// render.php may also return its markup.
		return is_string( $returned ) ? $html . $returned : $html;
	}

	/**
	 * Definition for the generic block editor UI.
	 *
	 * @return array
	 */
	public function editor_definition() {
		$schema   = $this->schema();
		$controls = array();

		foreach ( Schema::attributes( $schema ) as $key => $def ) {
			if ( isset( $def['control'] ) && 'none' === $def['control'] ) {
				continue;
			}
			$control = array(
				'key'     => $key,
				'type'    => $def['type'],
				'control' => isset( $def['control'] ) ? $def['control'] : self::default_control( $def ),
				'label'   => $this->kit->t( isset( $def['label'] ) ? $def['label'] : $key ),
				'panel'   => isset( $def['panel'] ) ? $def['panel'] : 'basic',
			);
			foreach ( array( 'min', 'max', 'step' ) as $opt ) {
				if ( isset( $def[ $opt ] ) ) {
					$control[ $opt ] = $def[ $opt ];
				}
			}
			if ( ! empty( $def['help'] ) ) {
				$control['help'] = $this->kit->t( $def['help'] );
			}
			if ( isset( $def['options'] ) ) {
				$control['options'] = $this->translated_options( $def['options'] );
			}
			$controls[] = $control;
		}

		return array(
			'name'     => $this->block_name(),
			'title'    => $this->title(),
			'preview'  => isset( $schema['editorPreview'] ) ? $schema['editorPreview'] : 'server',
			'controls' => $controls,
		);
	}

	/**
	 * Options with translated labels (static options only; sources translate their own).
	 *
	 * @param array $options Options list.
	 * @return array
	 */
	public function translated_options( array $options ) {
		$kit = $this->kit;

		return array_map(
			static function ( $option ) use ( $kit ) {
				return array(
					'value' => $option['value'],
					'label' => $kit->t( $option['label'] ),
				);
			},
			$options
		);
	}

	/**
	 * Control used when element.json does not name one.
	 *
	 * @param array $def Attribute definition.
	 * @return string
	 */
	public static function default_control( array $def ) {
		if ( 'boolean' === $def['type'] ) {
			return 'toggle';
		}
		if ( 'array' === $def['type'] ) {
			return 'multiselect';
		}
		if ( in_array( $def['type'], array( 'integer', 'number' ), true ) ) {
			return 'number';
		}

		return ! empty( $def['options'] ) || ! empty( $def['optionsFrom'] ) ? 'select' : 'text';
	}
}
