<?php
/**
 * Element kit entry point (shared with BuddyBuilder; source of truth: K Elements src/Kit/).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, Elementor optional, no plugin-specific names.
 * Every name (block namespace, hooks, CSS classes, text domain) comes from the config.
 * Copies in other plugins are made by K Elements bin/sync-kit.sh; never edit a copy.
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@677b5ac (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * One kit instance per plugin: finds the elements, registers the blocks and the Elementor widgets.
 */
final class Kit {

	/**
	 * Kit version, bumped on any change of the public API.
	 */
	const VERSION = '1.0.0';

	/**
	 * Configuration (see __construct()).
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Elements by name.
	 *
	 * @var Element[]
	 */
	private $elements = array();

	/**
	 * Named option sources: name => callable returning [ [ value, label ], ... ].
	 *
	 * @var callable[]
	 */
	private $option_sources = array();

	/**
	 * Resolved option sources, per request.
	 *
	 * @var array
	 */
	private $resolved = array();

	/**
	 * Constructor.
	 *
	 * Configuration keys:
	 * - string   namespace          Block namespace, e.g. "kleo".
	 * - string   prefix             Hook prefix, e.g. "k_elements_kit" (hooks <prefix>_elements, <prefix>_render).
	 * - string   css_prefix         Wrapper class prefix, e.g. "kel" (prints "kel-el kel-el-<name>").
	 * - string   text_domain        Text domain of the strings in element.json.
	 * - string   dir                Folder with one sub-folder per element (element.json, render.php, assets).
	 * - string   url                URL of that folder.
	 * - string   kit_url            URL of the kit folder (editor.js).
	 * - string   version            Asset version.
	 * - string   js_global          Editor data global, e.g. "kElementsKit" (also pushed to window.__elementKits).
	 * - string   block_category     Default block category.
	 * - array    elementor_category [ slug, title ] or empty to use block_category as is.
	 * - string   elementor_prefix   Elementor widget name prefix, e.g. "kleo-el-".
	 * - string   elementor_wrap     "inner" (div with the kit classes inside the widget) or "widget" (classes on the widget wrapper).
	 * - bool     elementor          Register Elementor widgets (default true when Elementor loads).
	 * - array    labels             Translated UI strings: basic, advanced, empty.
	 * - array    editor_query_args  Block editor previews: query arg => post meta key, sent from the edited (unsaved) post with post_id.
	 * - callable context            fn( Element element, array attrs, source, Context base ): Context|array. source is a WP_Block, an Elementor widget or null.
	 * - callable empty_preview      fn( Element element, Context ctx ): string, markup shown in editors when render.php prints nothing.
	 *
	 * @param array $config Kit configuration (keys above).
	 */
	public function __construct( array $config ) {
		$this->config = array_merge(
			array(
				'namespace'          => 'kit',
				'prefix'             => 'kit',
				'css_prefix'         => 'kit',
				'text_domain'        => 'default',
				'dir'                => '',
				'url'                => '',
				'kit_url'            => '',
				'version'            => self::VERSION,
				'js_global'          => 'kitElements',
				'block_category'     => 'widgets',
				'elementor_category' => array(),
				'elementor_prefix'   => 'kit-',
				'elementor_wrap'     => 'inner',
				'elementor'          => true,
				'labels'             => array(),
				'editor_query_args'  => array(),
				'context'            => null,
				'empty_preview'      => null,
			),
			$config
		);

		$this->config['labels'] = array_merge(
			array(
				'basic'    => 'Settings',
				'advanced' => 'Advanced',
				'empty'    => 'Nothing to show yet.',
			),
			(array) $this->config['labels']
		);
	}

	/**
	 * Hook the kit into WordPress (and Elementor when it loads).
	 *
	 * @return self
	 */
	public function register() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );

		if ( $this->config['elementor'] ) {
			add_action( 'elementor/elements/categories_registered', array( $this, 'elementor_category' ) );
			add_action( 'elementor/widgets/register', array( $this, 'elementor_widgets' ) );
		}

		return $this;
	}

	/**
	 * Config value.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	public function config( $key ) {
		return isset( $this->config[ $key ] ) ? $this->config[ $key ] : null;
	}

	/**
	 * Find the elements, register assets and blocks. Runs on init.
	 */
	public function init() {
		$this->discover();
		$this->register_editor_script();

		foreach ( $this->elements() as $element ) {
			$element->register_assets();
			BlockAdapter::register( $element );
		}
	}

	/**
	 * Read every <dir>/<name>/element.json. Filter "<prefix>/elements" can add or remove elements.
	 *
	 * @return Element[]
	 */
	public function discover() {
		if ( $this->elements ) {
			return $this->elements;
		}

		$found = array();

		foreach ( (array) glob( trailingslashit( $this->config['dir'] ) . '*/element.json' ) as $file ) {
			$schema = Schema::load( $file );
			if ( $schema ) {
				$found[ $schema['name'] ] = new Element( $this, dirname( $file ), $schema );
			}
		}

		ksort( $found );

		/**
		 * Elements of this kit, by name ("<prefix>_elements").
		 *
		 * @param Element[] $found Elements.
		 * @param Kit       $kit   This kit.
		 */
		$this->elements = (array) apply_filters( $this->config['prefix'] . '_elements', $found, $this ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- prefix from the config.

		return $this->elements;
	}

	/**
	 * All elements.
	 *
	 * @return Element[]
	 */
	public function elements() {
		return $this->elements ? $this->elements : $this->discover();
	}

	/**
	 * One element.
	 *
	 * @param string $name Element name.
	 * @return Element|null
	 */
	public function get( $name ) {
		$elements = $this->elements();

		return isset( $elements[ $name ] ) ? $elements[ $name ] : null;
	}

	/**
	 * Register a named option source used by "optionsFrom" in element.json.
	 *
	 * @param string   $name     Source name.
	 * @param callable $callback Returns [ value => label ] or [ [ 'value' => ..., 'label' => ... ], ... ].
	 * @return self
	 */
	public function add_option_source( $name, callable $callback ) {
		$this->option_sources[ $name ] = $callback;
		unset( $this->resolved[ $name ] );

		return $this;
	}

	/**
	 * Options of a named source, as [ [ 'value' => ..., 'label' => ... ], ... ].
	 *
	 * @param string $name Source name.
	 * @return array
	 */
	public function options( $name ) {
		if ( ! isset( $this->resolved[ $name ] ) ) {
			$raw = isset( $this->option_sources[ $name ] ) ? (array) call_user_func( $this->option_sources[ $name ] ) : array();

			$this->resolved[ $name ] = self::option_list( $raw );
		}

		return $this->resolved[ $name ];
	}

	/**
	 * Options as a list of { value, label } (accepts a value => label map).
	 *
	 * @param array $raw Options.
	 * @return array
	 */
	public static function option_list( array $raw ) {
		$out = array();

		foreach ( $raw as $value => $label ) {
			$out[] = is_array( $label ) ? array(
				'value' => (string) $label['value'],
				'label' => (string) $label['label'],
			) : array(
				'value' => (string) $value,
				'label' => (string) $label,
			);
		}

		return $out;
	}

	/**
	 * Translate a string from element.json with the configured text domain.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public function t( $text ) {
		// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain -- strings come from element.json.
		return '' === (string) $text ? '' : translate( (string) $text, $this->config['text_domain'] );
	}

	/**
	 * Wrapper classes of an element, the same in both builders.
	 *
	 * @param Element $element Element.
	 * @return string
	 */
	public function classes( Element $element ) {
		$prefix = $this->config['css_prefix'];

		return $prefix . '-el ' . $prefix . '-el-' . $element->name();
	}

	/**
	 * Render context for one element.
	 *
	 * @param Element   $element Element.
	 * @param array     $attrs   Normalized attributes.
	 * @param mixed     $source  WP_Block, Elementor widget or null.
	 * @param bool|null $preview Editor preview; null = detect.
	 * @return Context
	 */
	public function context( Element $element, array $attrs, $source = null, $preview = null ) {
		$post_id = 0;

		if ( $source instanceof \WP_Block && isset( $source->context['postId'] ) ) {
			$post_id = (int) $source->context['postId'];
		} elseif ( self::is_block_preview() && isset( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- block renderer route, permission checked by core.
			$post_id = absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above.
		} elseif ( in_the_loop() || is_singular() ) {
			$post_id = (int) get_the_ID();
		}

		$base = new Context(
			array(
				'preview' => null === $preview ? self::is_block_preview() : (bool) $preview,
				'post_id' => $post_id,
			)
		);

		if ( ! is_callable( $this->config['context'] ) ) {
			return $base;
		}

		$ctx = call_user_func( $this->config['context'], $element, $attrs, $source, $base );

		return $ctx instanceof Context ? $ctx : $base->with( (array) $ctx );
	}

	/**
	 * Markup for an editor preview when the element printed nothing.
	 *
	 * @param Element $element Element.
	 * @param Context $ctx     Context.
	 * @return string
	 */
	public function empty_preview( Element $element, Context $ctx ) {
		if ( is_callable( $this->config['empty_preview'] ) ) {
			return (string) call_user_func( $this->config['empty_preview'], $element, $ctx );
		}

		$hint = isset( $element->schema()['emptyHint'] ) ? $this->t( $element->schema()['emptyHint'] ) : $this->config['labels']['empty'];

		return '<p class="' . esc_attr( $this->config['css_prefix'] ) . '-el-empty">' . esc_html( $hint ) . '</p>';
	}

	/**
	 * The block editor asked for a server-side preview (block renderer REST route, edit context).
	 *
	 * @return bool
	 */
	public static function is_block_preview() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag, the REST route checks permissions.
		return defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_GET['context'] ) && 'edit' === $_GET['context'];
	}

	/**
	 * Handle of the generic editor script.
	 *
	 * @return string
	 */
	public function editor_handle() {
		return $this->config['namespace'] . '-kit-editor';
	}

	/**
	 * Register the generic editor script (one for every element, no build step).
	 */
	private function register_editor_script() {
		wp_register_script(
			$this->editor_handle(),
			trailingslashit( $this->config['kit_url'] ) . 'editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-server-side-render', 'wp-i18n' ),
			$this->config['version'],
			true
		);
	}

	/**
	 * Controls of every element for the generic editor (options resolved, strings translated).
	 */
	public function editor_data() {
		$data = array();

		foreach ( $this->elements() as $element ) {
			$data[] = $element->editor_definition();
		}

		$json = wp_json_encode(
			array(
				'labels'    => $this->config['labels'],
				'queryArgs' => (object) $this->config['editor_query_args'],
				'elements'  => $data,
			)
		);
		$name = 'window.' . $this->config['js_global'];

		// One queue for every kit copy (K Elements, BuddyBuilder): the first editor.js registers them all.
		wp_add_inline_script(
			$this->editor_handle(),
			$name . ' = ' . $json . '; ( window.__elementKits = window.__elementKits || [] ).push( ' . $name . ' );',
			'before'
		);
	}

	/**
	 * Elementor: widget category.
	 *
	 * @param object $manager Elementor elements manager.
	 */
	public function elementor_category( $manager ) {
		$category = $this->config['elementor_category'];

		if ( $category && method_exists( $manager, 'add_category' ) ) {
			$manager->add_category(
				$category[0],
				array(
					'title' => $category[1],
					'icon'  => 'fa fa-plug',
				)
			);
		}
	}

	/**
	 * Elementor category slug used by the widgets.
	 *
	 * @return string
	 */
	public function elementor_category_slug() {
		$category = $this->config['elementor_category'];

		return $category ? $category[0] : 'general';
	}

	/**
	 * Elementor: one generic widget instance per element (Elementor default args carry the element name).
	 *
	 * @param object $manager Elementor widgets manager.
	 */
	public function elementor_widgets( $manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		ElementorWidget::$kit = $this;

		foreach ( $this->elements() as $element ) {
			if ( isset( $element->schema()['elementor'] ) && false === $element->schema()['elementor'] ) {
				continue;
			}
			$manager->register( new ElementorWidget( array(), array( 'kit_element' => $element->name() ) ) );
		}
	}

	/**
	 * Elementor widget name of an element.
	 *
	 * @param string $name Element name.
	 * @return string
	 */
	public function elementor_name( $name ) {
		return $this->config['elementor_prefix'] . $name;
	}
}
