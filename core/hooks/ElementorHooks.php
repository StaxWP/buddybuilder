<?php
/**
 * Add Elementor Hooks
 *
 * @package Buddy_Builder
 * @since 1.0.0
 */

namespace Buddy_Builder;

defined( 'ABSPATH' ) || die();

use Buddy_Builder\Library\Documents\BuddyPress;
use Elementor\Controls_Manager;

/**
 * Class ElementorHooks
 *
 * @package Buddy_Builder
 */
class ElementorHooks extends Singleton {

	/**
	 * ElementorHooks constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'elementor/init', [ $this, 'early_init' ], 0 );

		add_action(
			'elementor/element/column/layout/before_section_end',
			[
				$this,
				'add_column_order_control',
			],
			12,
			2
		);

		add_action( 'elementor/elements/categories_registered', [ $this, 'categories_registered' ] );
		add_action( 'elementor/widgets/register', [ $this, 'widgets_registered' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_css' ] );
		add_action( 'elementor/editor/after_save', [ $this, 'save_buddypress_options' ], 10, 2 );

		add_filter( 'template_include', [ $this, 'change_preview_and_edit_tpl' ] );
		add_filter( 'elementor/document/urls/exit_to_dashboard', [ $this, 'redirect_to_bpb_dashboard' ], 10, 2 );

		add_action( 'wp', [ $this, 'maybe_flag_general_widgets' ] );
	}

	/**
	 * Singular posts built with Elementor that use a general BuddyBuilder widget (members, groups, activity
	 * listings) need the BuddyPress template stack: flag them. F11: only for Elementor-built posts, element names
	 * computed once.
	 */
	public function maybe_flag_general_widgets() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! $post_id || 'builder' !== get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return;
		}

		$meta = get_post_meta( $post_id, '_elementor_controls_usage', true );

		if ( ! is_array( $meta ) || empty( $meta ) ) {
			return;
		}

		$elements = [];

		foreach ( buddy_builder()->get_elements() as $widget ) {
			if ( ! isset( $widget['template'] ) ) {
				$elements[ $widget['name'] ] = true;
			}
		}

		if ( count( array_intersect_key( $elements, $meta ) ) > 0 ) {
			add_filter( 'buddy_builder/has_template/pre', '__return_true' );
		}
	}

	/**
	 * Override exit editor link
	 *
	 * @param string $url
	 * @param object $document
	 * @return string
	 */
	public function redirect_to_bpb_dashboard( $url, $document ) {
		if ( 'bpb-buddypress' === $document->get_template_type() ) {
			return admin_url( 'admin.php?page=buddy-builder-settings' );
		}

		return $url;
	}

	/**
	 * Change the template when editing and previewing to buddybuilder one
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function change_preview_and_edit_tpl( $template ) {
		if ( bpb_is_preview_mode() || bpb_is_front_library() || bpb_is_edit_frame() ) {
			$template = BPB_BASE_PATH . 'templates/buddypress/buddypress.php';
		}

		return $template;
	}

	/**
	 * Adds actions after Elementor init.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function early_init() {
		// Register modules.
		new Library\Module();

		// Template library.
		new Template\Module();
	}

	/**
	 * Add order control to column settings.
	 *
	 * @param object $element Element instance.
	 * @param array  $args Element arguments.
	 */
	public function add_column_order_control( $element, $args ) {
		$existing_control = \Elementor\Plugin::$instance->controls_manager->get_control_from_stack( $element->get_unique_name(), 'column_order' );

		if ( is_wp_error( $existing_control ) ) {
			$args = [
				'position' => [
					'at' => 'before',
					'of' => 'content_position',
				],
			];

			$element->add_responsive_control(
				'column_order',
				[
					'label'     => esc_html__( 'Column Order', 'stax-buddy-builder' ),
					'type'      => \Elementor\Controls_Manager::NUMBER,
					'min'       => 0,
					'max'       => 100,
					'selectors' => [
						'{{WRAPPER}}.elementor-column' => 'order: {{VALUE}}',
					],
				],
				$args
			);
		}
	}

	/**
	 * Register elementor category
	 */
	public function categories_registered() {
		global $post;
		$cat_name = 'BuddyBuilder';

		$templates = bpb_get_template_types();

		if ( $post && $template_type = get_post_meta( $post->ID, BuddyPress::REMOTE_CATEGORY_META_KEY, true ) ) {
			if ( isset( $templates[ $template_type ] ) ) {
				$cat_name .= ' ' . $templates[ $template_type ]['name'];
			}
		}

		\Elementor\Plugin::instance()->elements_manager->add_category(
			'buddy-builder-elements',
			[
				'title' => $cat_name,
				'icon'  => 'fa fa-plug',
			]
		);

		\Elementor\Plugin::instance()->elements_manager->add_category(
			'buddy-builder-general-elements',
			[
				'title' => 'BuddyBuilder General',
				'icon'  => 'fa fa-plug',
			]
		);
	}

	/**
	 * Register elementor widgets
	 */
	public function widgets_registered( $widgets_manager = null ) {
		$elementor = \Elementor\Plugin::instance();

		if ( null === $widgets_manager && isset( $elementor->widgets_manager ) ) {
			$widgets_manager = $elementor->widgets_manager;
		}

		if ( $widgets_manager && method_exists( $widgets_manager, 'register' ) ) {
			include_once BPB_BASE_PATH . 'core/widgets/Base.php';

			foreach ( buddy_builder()->get_elements() as $k => $element ) {
				if ( $template_file = $this->get_element_path( $element['template_base_path'] . $k ) ) {

					// Add widget's settings based on buddypress/buddyboss.
					if ( isset( $element['extra'] ) ) {
						if ( isset( $element['extra']['buddypress'] ) && ! bpb_is_buddyboss() ) {
							$buddypress_settings_template_file = $this->get_element_path( $element['template_base_path'] . $element['extra']['buddypress'] );
							if ( $buddypress_settings_template_file ) {
								require_once $buddypress_settings_template_file;
							}
						}

						if ( isset( $element['extra']['buddyboss'] ) && bpb_is_buddyboss() ) {
							$buddyboss_settings_template_file = $this->get_element_path( $element['template_base_path'] . $element['extra']['buddyboss'] );
							if ( $buddyboss_settings_template_file ) {
								require_once $buddyboss_settings_template_file;
							}
						}
					}

					require_once $template_file;
					$class_name = $element['class_base_namespace'] . $element['class'];

					$widgets_manager->register( new $class_name() );
				}
			}
		}
	}

	/**
	 * Sync elementor widget options with customizer
	 *
	 * @param $post_id
	 * @param $editor_data
	 */
	public function save_buddypress_options( $post_id, $editor_data ) {
		$settings = bpb_get_settings();
		$save     = false;

		foreach ( $settings['templates'] as $template ) {
			if ( (int) $template === (int) $post_id ) {
				$save = true;
			}
		}

		if ( $save ) {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );

			if ( $document ) {
				\Elementor\Plugin::$instance->db->iterate_data(
					$document->get_elements_data(),
					static function ( $element ) {
						if ( empty( $element['widgetType'] ) || $element['elType'] !== 'widget' ) {
							return $element;
						}

						\Buddy_Builder\Settings\Mirror::apply( $element['widgetType'], isset( $element['settings'] ) ? (array) $element['settings'] : [] );

						return $element;
					}
				);
			}
		}
	}

	/**
	 * Enqueue Elementor Editor CSS
	 */
	public function editor_css() {
		wp_enqueue_style(
			'stax-elementor-bb-icons-tyle',
			BPB_ADMIN_ASSETS_URL . 'css/icons.css',
			null,
			BPB_VERSION
		);

		wp_enqueue_style(
			'stax-elementor-bb-label-style',
			BPB_ADMIN_ASSETS_URL . 'css/label.css',
			null,
			BPB_VERSION
		);
	}

	/**
	 * Get widget template path
	 *
	 * @param $file_path
	 *
	 * @return bool|string
	 */
	public function get_element_path( $file_path ) {
		$template_file = $file_path . '.php';
		if ( $template_file && is_readable( $template_file ) ) {
			return $template_file;
		}

		return false;
	}
}
