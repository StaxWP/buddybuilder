<?php
/**
 * Add Library Module.
 *
 * @package Buddy_Builder
 * @since 1.0.0
 */

namespace Buddy_Builder\Library;

use Buddy_Builder\Library\Documents\BuddyPress;
use Elementor\Core\Base\Module as BaseModule;
use Elementor\Plugin;

use Buddy_Builder\Library\Documents;

defined( 'ABSPATH' ) || die();

/**
 * Buddy_Builder library module.
 *
 * Buddy_Builder library module handler class is responsible for registering and
 * managing Elementor library modules.
 *
 * @since 1.0.0
 */
class Module extends BaseModule {

	protected $templates;

	/**
	 * Library module constructor.
	 *
	 * Initializing Buddy_Builder library module.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$this->templates = bpb_get_template_types();

		Plugin::$instance->documents
			->register_document_type( 'bpb-buddypress', Documents\BuddyPress::get_class_full_name() );

		if( isset( $_GET['elementor_library_type'] ) && $_GET['elementor_library_type'] === 'bpb-buddypress' ) {
			add_filter( 'manage_elementor_library_posts_columns', [ $this, 'add_column_head' ], 99 );
			add_action( 'manage_elementor_library_posts_custom_column', [ $this, 'add_column_template_type' ], 10, 2 );
		}

		if ( ! class_exists( 'ElementorPro\Plugin' ) ) {
			add_action( 'manage_elementor_library_posts_custom_column', [ $this, 'add_column_content' ], 10, 2 );
		}

		if ( ! shortcode_exists( 'elementor-template' ) ) {
			add_shortcode( 'elementor-template', [ $this, 'register_shortcode' ] );
		}

		if ( ! shortcode_exists( 'elementor-template-preview' ) ) {
			add_shortcode( 'elementor-template-preview', [ $this, 'register_preview_shortcode' ] );
		}

		add_action(
			'elementor/template-library/create_new_dialog_fields',
			[
				$this,
				'buddypress_template_options',
			]
		);

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script' ] );
	}

	/**
	 * Get module name.
	 *
	 * Retrieve the library module name.
	 *
	 * @return string Module name.
	 * @since 1.0.0
	 * @access public
	 */
	public function get_name() {
		return 'library';
	}

	/**
	 * Enqueue a script in the WordPress admin
	 *
	 * @param int $hook Hook suffix for the current admin page.
	 */
	function enqueue_admin_script( $hook ) {
		if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || sanitize_text_field( $_GET['post_type'] ) !== 'elementor_library' ) {
			return;
		}
		wp_enqueue_script( 'bpb-admin-library', BPB_ASSETS_URL . 'js/admin-library.js', [], '1.0' );
	}

	/**
	 * Add column head.
	 *
	 * @param array $defaults The default columns.
	 *
	 * @return array Default columns.
	 * @since 1.0.0
	 * @access public
	 */
	public function add_column_head( $defaults ) {
		if ( isset( $defaults['shortcode'], $defaults['template-type'] ) ) {
			return $defaults;
		}

		$defaults['template-type'] = __( 'BP Template Type', 'stax-buddy-builder' );
		$defaults['shortcode']     = __( 'Shortcode', 'stax-buddy-builder' );

		return $defaults;
	}

	/**
	 * Add column content.
	 *
	 * @param string  $column_name The column name.
	 * @param integer $post_ID The post ID.
	 *
	 * @return void.
	 * @since 1.0.0
	 * @access public
	 */
	public function add_column_content( $column_name, $post_ID ) {
		if ( 'shortcode' !== $column_name ) {
			return;
		}

		echo '<input class="elementor-shortcode-input" style="width: 100%;" type="text" readonly="" onfocus="this.select()" value="[elementor-template id=&quot;' . $post_ID . '&quot;]">';
	}

	/**
	 * Add column BP template type
	 *
	 * @param $column_name
	 * @param $post_ID
	 */
	public function add_column_template_type( $column_name, $post_ID ) {
		if ( 'template-type' !== $column_name ) {
			return;
		}

		$template_type = get_post_meta( $post_ID, BuddyPress::REMOTE_CATEGORY_META_KEY, true );

		if ( isset( $this->templates[ $template_type ] ) ) {
			echo $this->templates[ $template_type ]['name'];
		}
	}

	/**
	 * Checks if template is visible
	 *
	 * @param int $template_id The template ID to check
	 * @return bool
	 */
	protected function is_template_visible($template_id) {
		$post = get_post($template_id);
		
		if (!$post) {
			return false;
		}

		$visible = true;

		// Check post status
		if ('trash' === $post->post_status) {
			$visible = false;
		} elseif ('publish' !== $post->post_status && !current_user_can('edit_post', $template_id)) {
			$visible = false;
		}

		// Verify post type
		if ($post->post_type !== 'elementor_library') {
			$visible = false;
		}

		// Check password protection
		if (!empty($post->post_password)) {
			$visible = false;
		}

		return apply_filters('buddy_builder/template/is_visible', $visible, $template_id);
	}

	/**
	 * Register preview shortcode
	 *
	 * @param $atts
	 * @return string
	 */
	public function register_preview_shortcode($atts) {
		if (empty($atts['id'])) {
			return '';
		}

		if (!$this->is_template_visible($atts['id'])) {
			return '';
		}

		$content = \Elementor\Plugin::instance()->frontend->get_builder_content($atts['id'], true);
		return $content;
	}

	/**
	 * Register shortcode.
	 *
	 * @param array $atts The shortcode attributes.
	 * @return mixed Content.
	 * @since 1.0.0
	 * @access public
	 */
	public function register_shortcode($atts) {
		if (empty($atts['id'])) {
			return '';
		}

		if (!$this->is_template_visible($atts['id'])) {
			return '';
		}

		$content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($atts['id']);
		return $content;
	}


	public function buddypress_template_options() {
		?>
		<div id="elementor-new-template__form__template-bpb__wrapper" class="elementor-form-field">
			<label for="elementor-new-template__form__template-bpb"
				   class="elementor-form-field__label"><?php echo esc_html__( 'Select the type of Buddypress page', 'stax-buddy-builder' ); ?></label>
			<div class="elementor-form-field__select__wrapper">
				<select id="elementor-new-template__form__template-bpb" class="elementor-form-field__select"
						name="<?php echo BuddyPress::REMOTE_CATEGORY_META_KEY; ?>">
					<option value=""><?php echo __( 'Select', 'elementor' ); ?>...</option>
					<?php
					foreach ( $this->templates as $type => $template ) {
						printf( '<option value="%1$s">%2$s</option>', $type, $template['name'] );
					}
					?>
				</select>
			</div>
		</div>
		<?php
	}

}
