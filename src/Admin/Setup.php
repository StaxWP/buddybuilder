<?php
/**
 * BuddyBuilder setup: three steps for new installs (check your community, pick a look, review on your profile).
 *
 * New installs are sent here once after activation. Updated sites are never redirected: they see a notice while no
 * page has a layout, and every site can open it again from Community pages ("Run setup again"). Nothing goes live
 * before "Use these": the looks are created as layouts that are not assigned yet (pending), previewed on the real
 * pages through LayoutPreview, and assigned with the same write path as the Community pages screen.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Features;
use Buddy_Builder\Helpers;
use Buddy_Builder\Integrations\Elementor\Integration as Elementor;
use Buddy_Builder\Presets\Presets;
use Buddy_Builder\Presets\Preview;
use Buddy_Builder\Templates\LayoutPreview;
use Buddy_Builder\Templates\Registry;
use Buddy_Builder\Templates\SampleMember;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * Setup wizard.
 */
final class Setup {

	const SLUG     = 'setup';
	const ACTION   = 'bpb_setup';
	const STATE    = 'bpb_setup';
	const PENDING  = 'bpb_setup_pending';
	const REDIRECT = 'bpb_setup_redirect';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 20 );
		add_filter( 'parent_file', [ __CLASS__, 'parent_file' ] );
		add_action( 'in_admin_header', [ __CLASS__, 'quiet_notices' ], 1000 );
		add_action( 'admin_init', [ __CLASS__, 'maybe_redirect' ] );
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle' ] );
		add_action( 'admin_notices', [ __CLASS__, 'notice' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_filter( 'admin_title', [ __CLASS__, 'admin_title' ], 10, 2 );
	}

	/**
	 * Screen URL.
	 *
	 * @param int $step Step 1 to 3.
	 * @return string
	 */
	public static function url( $step = 1 ) {
		return admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG . ( $step > 1 ? '&step=' . (int) $step : '' ) );
	}

	/**
	 * Activation: remember to open the setup once, only on a new install (no BuddyBuilder data yet).
	 */
	public static function on_activation() {
		if ( self::is_new_install() ) {
			update_option( self::REDIRECT, 1, false );
		}
	}

	/**
	 * Whether this site never had BuddyBuilder data (1.x or 2.x options, a finished setup).
	 *
	 * @return bool
	 */
	public static function is_new_install() {
		foreach ( [ 'bpb_settings', Registry::OPTION, 'buddybuilder_db_version', 'bpb_db_version', 'bpb_imported_templates', self::STATE ] as $option ) {
			if ( false !== get_option( $option, false ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether no page has a layout yet.
	 *
	 * @return bool
	 */
	public static function is_first_run() {
		foreach ( Registry::all() as $entry ) {
			if ( $entry['id'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Saved state: status (new|done|skipped), builder, sample choice.
	 *
	 * @return array
	 */
	public static function state() {
		$state = get_option( self::STATE, [] );

		return array_merge(
			[
				'status'  => 'new',
				'builder' => '',
			],
			is_array( $state ) ? $state : []
		);
	}

	/**
	 * Save part of the state.
	 *
	 * @param array $changes Changes.
	 */
	private static function save_state( array $changes ) {
		update_option( self::STATE, array_merge( self::state(), $changes ), false );
	}

	/**
	 * Layout made by the setup for a type and not live yet (0: none).
	 *
	 * @param string $type Template type.
	 * @return int
	 */
	public static function pending( $type ) {
		$all = self::all_pending();

		return isset( $all[ $type ] ) ? (int) $all[ $type ] : 0;
	}

	/**
	 * All pending layouts.
	 *
	 * @return array type => id
	 */
	public static function all_pending() {
		$all = get_option( self::PENDING, [] );

		return is_array( $all ) ? array_map( 'intval', $all ) : [];
	}

	/**
	 * Forget a pending layout, deleting it when asked and when nothing uses it.
	 *
	 * @param string $type   Template type.
	 * @param bool   $delete Delete the layout.
	 */
	public static function clear_pending( $type, $delete ) {
		$all = self::all_pending();

		if ( ! isset( $all[ $type ] ) ) {
			return;
		}

		$id = $all[ $type ];
		unset( $all[ $type ] );

		if ( $all ) {
			update_option( self::PENDING, $all, false );
		} else {
			delete_option( self::PENDING );
		}

		if ( $delete && $id && Registry::get( $type )['id'] !== $id ) {
			wp_delete_post( $id, true );
		}
	}

	/**
	 * Hidden screen (opened from the activation redirect, the notice and "Run setup again"): no menu entry.
	 */
	public static function menu() {
		add_submenu_page(
			'',
			__( 'BuddyBuilder setup', 'stax-buddy-builder' ),
			__( 'Setup', 'stax-buddy-builder' ),
			'manage_options',
			BPB_ADMIN_PREFIX . self::SLUG,
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * The setup is a focused task: other plugins' notices are left out of it (they show again on any other screen).
	 */
	public static function quiet_notices() {
		if ( self::is_screen() ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * The setup highlights the BuddyBuilder menu.
	 *
	 * @param string $parent_file Parent menu file.
	 * @return string
	 */
	public static function parent_file( $parent_file ) {
		return self::is_screen() ? Helpers::get_instance()->get_slug() : $parent_file;
	}

	/**
	 * Browser title of the hidden screen.
	 *
	 * @param string $admin_title Title.
	 * @param string $title       Page title.
	 * @return string
	 */
	public static function admin_title( $admin_title, $title ) {
		if ( ! self::is_screen() || '' !== $title ) {
			return $admin_title;
		}

		return __( 'BuddyBuilder setup', 'stax-buddy-builder' ) . $admin_title;
	}

	/**
	 * Whether the setup screen is open.
	 *
	 * @return bool
	 */
	private static function is_screen() {
		return isset( $_GET['page'] ) && BPB_ADMIN_PREFIX . self::SLUG === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * After the first activation of a new install: open the setup once.
	 */
	public static function maybe_redirect() {
		if ( ! get_option( self::REDIRECT ) ) {
			return;
		}

		if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_options' ) || isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		delete_option( self::REDIRECT );

		if ( ! self::is_first_run() || ! function_exists( 'bp_is_active' ) ) {
			return;
		}

		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Notice while no page has a layout and the setup was not finished or skipped (updated sites see this, never a
	 * redirect).
	 */
	public static function notice() {
		if ( ! current_user_can( 'manage_options' ) || self::is_screen() || ! function_exists( 'bp_is_active' ) || ! self::is_first_run() ) {
			return;
		}

		if ( in_array( self::state()['status'], [ 'done', 'skipped' ], true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ( 'dashboard' !== $screen->id && false === strpos( (string) $screen->id, 'buddy-builder' ) ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s <a class="button button-primary" href="%3$s">%4$s</a></p></div>',
			esc_html__( 'BuddyBuilder:', 'stax-buddy-builder' ),
			esc_html__( 'give your community pages a look in three steps. Nothing changes until you press Use these.', 'stax-buddy-builder' ),
			esc_url( self::url() ),
			esc_html__( 'Start setup', 'stax-buddy-builder' )
		);
	}

	/**
	 * Screen assets.
	 */
	public static function assets() {
		if ( ! self::is_screen() ) {
			return;
		}

		wp_enqueue_style( 'bpb-admin-ui', BPB_ASSETS_URL . 'css/admin-ui.css', [], BPB_VERSION );
		wp_enqueue_script( 'bpb-setup', BPB_ASSETS_URL . 'js/setup.js', [], BPB_VERSION, true );
	}

	/**
	 * Builders the setup offers: the block editor always (choosing it turns block layouts on), Elementor when active.
	 *
	 * @return array builder => label
	 */
	public static function builders() {
		$builders = [ Registry::BLOCKS => CommunityPages::builder_label( Registry::BLOCKS ) ];

		if ( Elementor::is_available() ) {
			$builders[ Registry::ELEMENTOR ] = CommunityPages::builder_label( Registry::ELEMENTOR );
		}

		return $builders;
	}

	/**
	 * Builder picked by default: the saved choice, the block editor when enabled or without Elementor, else Elementor.
	 *
	 * @return string
	 */
	public static function builder() {
		$saved    = self::state()['builder'];
		$builders = self::builders();

		if ( isset( $builders[ $saved ] ) ) {
			return $saved;
		}

		return Features::blocks_enabled() || ! Elementor::is_available() ? Registry::BLOCKS : Registry::ELEMENTOR;
	}

	/**
	 * Page types the setup offers looks for (active component, at least one look).
	 *
	 * @return array type => presets
	 */
	public static function types() {
		$out = [];

		foreach ( array_keys( Types::for_blocks() ) as $type ) {
			$presets = Types::is_active( $type ) ? Presets::all( $type ) : [];

			if ( $presets ) {
				$out[ $type ] = $presets;
			}
		}

		return $out;
	}

	/**
	 * Step 1 checks: platform, components, pages, theme, ReadyLaunch, members.
	 *
	 * @return array[] [ tone (ok|warn|info), text, link?, link_text? ]
	 */
	public static function checks() {
		$checks = [];

		if ( ! function_exists( 'bp_is_active' ) ) {
			return [
				[
					'tone' => 'warn',
					'text' => __( 'BuddyPress or BuddyBoss Platform is not active.', 'stax-buddy-builder' ),
				],
			];
		}

		$names = [
			'members'       => __( 'Members', 'stax-buddy-builder' ),
			'xprofile'      => __( 'Profiles', 'stax-buddy-builder' ),
			'activity'      => __( 'Activity', 'stax-buddy-builder' ),
			'groups'        => __( 'Groups', 'stax-buddy-builder' ),
			'friends'       => __( 'Friends', 'stax-buddy-builder' ),
			'messages'      => __( 'Messages', 'stax-buddy-builder' ),
			'notifications' => __( 'Notifications', 'stax-buddy-builder' ),
		];

		$on  = [];
		$off = [];

		foreach ( $names as $component => $name ) {
			if ( 'members' === $component || bp_is_active( $component ) ) {
				$on[] = $name;
			} else {
				$off[] = $name;
			}
		}

		$platform = bpb_is_buddyboss()
			/* translators: %s: version */
			? sprintf( __( 'BuddyBoss Platform %s', 'stax-buddy-builder' ), defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : bp_get_version() )
			/* translators: %s: version */
			: sprintf( __( 'BuddyPress %s', 'stax-buddy-builder' ), bp_get_version() );

		$checks[] = [
			'tone' => 'ok',
			/* translators: 1: platform and version, 2: list of components */
			'text' => sprintf( __( '%1$s with %2$s', 'stax-buddy-builder' ), $platform, implode( ', ', $on ) ),
		];

		foreach ( $off as $name ) {
			$checks[] = [
				'tone'      => 'warn',
				/* translators: %s: component name */
				'text'      => sprintf( __( 'The %s component is off.', 'stax-buddy-builder' ), $name ),
				'link'      => Checker::components_url(),
				'link_text' => __( 'Turn on', 'stax-buddy-builder' ),
			];
		}

		$pages   = function_exists( 'bp_core_get_directory_page_ids' ) ? (array) bp_core_get_directory_page_ids( 'all' ) : [];
		$found   = [];
		$missing = [];

		foreach ( [ 'members' => $names['members'], 'activity' => $names['activity'], 'groups' => $names['groups'] ] as $component => $name ) {
			if ( 'members' !== $component && ! bp_is_active( $component ) ) {
				continue;
			}

			if ( ! empty( $pages[ $component ] ) && get_post( $pages[ $component ] ) ) {
				$found[] = $name;
			} else {
				$missing[] = $name;
			}
		}

		if ( $found ) {
			/* translators: %s: list of pages */
			$checks[] = [
				'tone' => 'ok',
				'text' => sprintf( __( 'Pages: %s', 'stax-buddy-builder' ), implode( ', ', $found ) ),
			];
		}

		if ( $missing ) {
			$checks[] = [
				'tone'      => 'warn',
				/* translators: %s: list of pages */
				'text'      => sprintf( __( 'No WordPress page yet for: %s.', 'stax-buddy-builder' ), implode( ', ', $missing ) ),
				'link'      => self::bp_admin_url( bpb_is_buddyboss() ? 'bp-pages' : 'bp-page-settings' ),
				'link_text' => __( 'Set up pages', 'stax-buddy-builder' ),
			];
		}

		$theme    = wp_get_theme();
		$checks[] = [
			'tone' => 'ok',
			/* translators: %s: theme name */
			'text' => sprintf( __( 'Theme: %s. The looks use its colors and fonts.', 'stax-buddy-builder' ), $theme->get( 'Name' ) ),
		];

		if ( Checker::readylaunch_active() ) {
			$checks[] = [
				'tone' => 'warn',
				'text' => __( 'BuddyBoss ReadyLaunch is on: it styles these pages. BuddyBuilder layouts take over only for the pages you pick a look for.', 'stax-buddy-builder' ),
			];
		}

		$count    = function_exists( 'bp_get_total_member_count' ) ? (int) bp_get_total_member_count() : 0;
		$checks[] = [
			'tone' => 'ok',
			/* translators: %s: number of members */
			'text' => sprintf( _n( '%s member. Previews use your own profile.', '%s members. Previews use your own profile.', $count, 'stax-buddy-builder' ), number_format_i18n( $count ) ),
		];

		/**
		 * Filters the checks of the first setup step.
		 *
		 * @param array[] $checks [ tone, text, link?, link_text? ].
		 */
		return (array) apply_filters( 'buddy_builder/setup/checks', $checks );
	}

	/**
	 * BuddyPress / BuddyBoss settings screen URL.
	 *
	 * @param string $page Page slug.
	 * @return string
	 */
	private static function bp_admin_url( $page ) {
		$path = 'admin.php?page=' . $page;

		return function_exists( 'bp_get_admin_url' ) ? bp_get_admin_url( $path ) : admin_url( $path );
	}

	/**
	 * Form handler (one action, "do" says which step).
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::ACTION ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stax-buddy-builder' ) );
		}

		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';

		switch ( $do ) {
			case 'check':
				$builder = isset( $_POST['builder'] ) ? sanitize_key( wp_unslash( $_POST['builder'] ) ) : '';
				$builder = isset( self::builders()[ $builder ] ) ? $builder : self::builder();

				// Choosing the block editor turns block layouts on (the "Blocks" setting).
				if ( Registry::BLOCKS === $builder && ! Features::blocks_enabled() ) {
					update_option( Features::BLOCKS_OPTION, '1' );
				}

				if ( ! empty( $_POST['sample'] ) && function_exists( 'bp_is_active' ) ) {
					SampleMember::create();
				}

				self::save_state( [ 'builder' => $builder ] );
				$redirect = self::url( 2 );
				break;

			case 'looks':
				$redirect = self::create_looks( isset( $_POST['look'] ) ? (array) wp_unslash( $_POST['look'] ) : [] ) ? self::url( 3 ) : add_query_arg( 'bpb_error', 1, self::url( 2 ) );
				break;

			case 'use':
				foreach ( self::all_pending() as $type => $id ) {
					if ( get_post( $id ) && CommunityPages::assign( $type, Registry::builder_of( $id ), $id ) ) {
						self::clear_pending( $type, false );
					}
				}

				self::save_state( [ 'status' => 'done' ] );
				$redirect = add_query_arg( 'bpb_done', 'setup', CommunityPages::url() );
				break;

			case 'later':
				self::save_state( [ 'status' => 'done' ] );
				$redirect = CommunityPages::url();
				break;

			case 'skip':
				foreach ( array_keys( self::all_pending() ) as $type ) {
					self::clear_pending( $type, true );
				}

				self::save_state( [ 'status' => 'skipped' ] );
				$redirect = CommunityPages::url();
				break;

			default:
				$redirect = self::url();
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Create the picked looks as layouts that are not live yet (replacing the ones a previous run left pending).
	 *
	 * @param array $looks type => preset id ('' keeps the page as it is).
	 * @return bool At least one look was created.
	 */
	private static function create_looks( array $looks ) {
		$builder = self::builder();
		$types   = self::types();
		$created = 0;

		foreach ( array_keys( self::all_pending() ) as $type ) {
			self::clear_pending( $type, true );
		}

		$pending = [];

		foreach ( $looks as $type => $preset_id ) {
			$type      = sanitize_key( $type );
			$preset_id = sanitize_key( $preset_id );

			if ( ! isset( $types[ $type ], $types[ $type ][ $preset_id ] ) ) {
				continue;
			}

			$preset = Presets::get( $preset_id );
			$id     = Registry::BLOCKS === $builder ? Presets::create_block_layout( $preset ) : Presets::create_elementor_layout( $preset );

			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}

			$pending[ $type ] = (int) $id;
			++$created;
		}

		if ( $pending ) {
			update_option( self::PENDING, $pending, false );
		}

		return $created > 0;
	}

	/**
	 * Hidden form fields.
	 *
	 * @param string $do Step action.
	 */
	private static function fields( $do ) {
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
		wp_nonce_field( self::ACTION );
	}

	/**
	 * The screen.
	 */
	public static function render() {
		$step = isset( $_GET['step'] ) ? max( 1, min( 3, absint( $_GET['step'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification

		if ( 3 === $step && ! self::all_pending() ) {
			$step = 2;
		}

		$steps = [
			1 => __( 'Check your community', 'stax-buddy-builder' ),
			2 => __( 'Pick a look', 'stax-buddy-builder' ),
			3 => __( 'Review on your profile', 'stax-buddy-builder' ),
		];

		echo '<div class="wrap bpb-admin bpb-setup">';
		echo '<div class="bpb-setup__head"><h1>' . esc_html__( 'BuddyBuilder setup', 'stax-buddy-builder' ) . '</h1>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::fields( 'skip' );
		echo '<button type="submit" class="button-link">' . esc_html__( 'Skip setup, I will do it myself', 'stax-buddy-builder' ) . '</button></form></div><hr class="wp-header-end">';

		echo '<ol class="bpb-steps">';

		foreach ( $steps as $number => $name ) {
			$class = $number < $step ? 'is-done' : ( $number === $step ? 'is-current' : '' );
			/* translators: 1: step number, 2: step name */
			$text = sprintf( __( '%1$d. %2$s', 'stax-buddy-builder' ), $number, $name );
			echo '<li class="' . esc_attr( $class ) . '"' . ( $number === $step ? ' aria-current="step"' : '' ) . '>';
			echo $number < $step ? '<a href="' . esc_url( self::url( $number ) ) . '">' . esc_html( $text ) . '</a>' : esc_html( $text );

			if ( $number < $step ) {
				echo '<span class="screen-reader-text"> ' . esc_html__( '(done)', 'stax-buddy-builder' ) . '</span>';
			}

			echo '</li>';
		}

		echo '</ol>';

		if ( 1 === $step ) {
			self::render_check();
		} elseif ( 2 === $step ) {
			self::render_looks();
		} else {
			self::render_review();
		}

		echo '</div>';
	}

	/**
	 * Step 1: what BuddyBuilder found, builder and sample member choices.
	 */
	private static function render_check() {
		$builders = self::builders();
		$current  = self::builder();
		$members  = function_exists( 'bp_get_total_member_count' ) ? (int) bp_get_total_member_count() : 0;

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-setup__card">';
		self::fields( 'check' );
		echo '<h2>' . esc_html__( 'Your community', 'stax-buddy-builder' ) . '</h2>';
		self::render_checks();

		echo '<fieldset class="bpb-setup__field"><legend>' . esc_html__( 'Build the pages with', 'stax-buddy-builder' ) . '</legend>';

		foreach ( $builders as $builder => $label ) {
			echo '<label class="bpb-choice"><input type="radio" name="builder" value="' . esc_attr( $builder ) . '"' . checked( $current, $builder, false ) . '> ' . esc_html( $label ) . '</label>';
		}

		echo '<p class="description">' . esc_html__( 'Both make the same looks. You can switch a page to the other builder later.', 'stax-buddy-builder' ) . '</p></fieldset>';

		if ( ! SampleMember::user_id() ) {
			echo '<fieldset class="bpb-setup__field"><legend>' . esc_html__( 'Previews', 'stax-buddy-builder' ) . '</legend>';
			echo '<label class="bpb-choice"><input type="checkbox" name="sample" value="1"' . checked( $members < 3, true, false ) . '> ' . esc_html__( 'Create a sample member with updates, friends and groups, so previews look real. It never shows in directories and you can remove it in Settings.', 'stax-buddy-builder' ) . '</label></fieldset>';
		}

		echo '<p class="bpb-setup__nav"><button type="submit" class="button button-primary button-hero">' . esc_html__( 'Continue', 'stax-buddy-builder' ) . '</button></p>';
		echo '</form>';
	}

	/**
	 * Check list.
	 */
	private static function render_checks() {
		echo '<ul class="bpb-checks">';

		foreach ( self::checks() as $check ) {
			$icon = 'warn' === $check['tone'] ? 'dashicons-warning' : 'dashicons-yes';
			echo '<li class="bpb-checks__item bpb-checks__item--' . esc_attr( $check['tone'] ) . '"><span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
			echo '<span>' . ( 'warn' === $check['tone'] ? '<span class="screen-reader-text">' . esc_html__( 'Warning:', 'stax-buddy-builder' ) . ' </span>' : '' ) . esc_html( $check['text'] );

			if ( ! empty( $check['link'] ) ) {
				echo ' <a href="' . esc_url( $check['link'] ) . '">' . esc_html( $check['link_text'] ) . '</a>';
			}

			echo '</span></li>';
		}

		echo '</ul>';
	}

	/**
	 * Step 2: one look per page, the member profile previewed live on your profile.
	 */
	private static function render_looks() {
		$types = self::types();

		if ( isset( $_GET['bpb_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Pick a look for at least one page, or skip the setup.', 'stax-buddy-builder' ) . '</p></div>';
		}

		if ( ! $types ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'No BuddyPress page can get a look yet: turn on the Members component.', 'stax-buddy-builder' ) . '</p></div>';

			return;
		}

		$main   = isset( $types['member-profile'] ) ? 'member-profile' : (string) key( $types );
		$sample = SampleMember::user_id();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-setup__looks">';
		self::fields( 'looks' );

		echo '<div class="bpb-setup__main">';
		self::render_type_looks( $main, $types[ $main ], true );

		echo '<div class="bpb-setup__also"><h3>' . esc_html__( 'Also', 'stax-buddy-builder' ) . '</h3>';

		foreach ( $types as $type => $presets ) {
			if ( $type !== $main ) {
				self::render_type_looks( $type, $presets, false );
			}
		}

		echo '</div>';

		/**
		 * Fires after the looks of the setup (the "describe your community" AI helper of BuddyBuilder 2.2 hooks here).
		 *
		 * @param array $types type => presets.
		 */
		do_action( 'buddy_builder/setup/after_looks', $types );

		echo '</div>';

		// Live preview of the selected member profile look on a real profile.
		$who = [ 0 => __( 'your profile', 'stax-buddy-builder' ) ];

		if ( $sample ) {
			$who[ $sample ] = __( 'the sample member', 'stax-buddy-builder' );
		}

		$first = (string) key( $types[ $main ] );
		$urls  = [];

		$current = Registry::get( $main );

		foreach ( array_keys( $who ) as $user ) {
			foreach ( array_keys( $types[ $main ] ) as $preset_id ) {
				$urls[ $user ][ $preset_id ] = LayoutPreview::page_url( $main, [ $main => 'preset:' . $preset_id ], true, $user );
			}

			// "Keep current": the layout the page uses now.
			if ( $current['id'] && Registry::is_post_populated( $current['id'] ) ) {
				$urls[ $user ][''] = LayoutPreview::page_url( $main, [ $main => 'post:' . $current['id'] ], true, $user );
			}
		}

		if ( $urls[0][ $first ] ) {
			echo '<aside class="bpb-setup__preview" data-bpb-urls="' . esc_attr( wp_json_encode( $urls ) ) . '" data-bpb-type="' . esc_attr( $main ) . '">';
			echo '<div class="bpb-setup__preview-head"><h3 id="bpb-preview-title">' . esc_html__( 'Live preview', 'stax-buddy-builder' ) . '</h3>';

			if ( count( $who ) > 1 ) {
				echo '<label>' . esc_html__( 'Previewed with', 'stax-buddy-builder' ) . ' <select class="bpb-setup__who">';

				foreach ( $who as $user => $label ) {
					echo '<option value="' . esc_attr( $user ) . '">' . esc_html( $label ) . '</option>';
				}

				echo '</select></label>';
			} else {
				echo '<span>' . esc_html__( 'Previewed with your profile', 'stax-buddy-builder' ) . '</span>';
			}

			echo '</div><div class="bpb-setup__frame"><iframe title="' . esc_attr__( 'Preview of the selected look on a real profile', 'stax-buddy-builder' ) . '" src="' . esc_url( $urls[0][ $first ] ) . '" loading="lazy"></iframe></div></aside>';
		}

		$builder = self::builders()[ self::builder() ];
		echo '<div class="bpb-setup__nav"><a class="button" href="' . esc_url( self::url( 1 ) ) . '">' . esc_html__( 'Back', 'stax-buddy-builder' ) . '</a>';
		/* translators: %s: builder name */
		echo '<p>' . esc_html( sprintf( __( 'Creates the layouts with the %s, ready to edit. Your pages stay as they are until you press Use these.', 'stax-buddy-builder' ), $builder ) ) . '</p>';
		echo '<button type="submit" class="button button-primary button-hero">' . esc_html__( 'Use these and preview', 'stax-buddy-builder' ) . '</button></div>';
		echo '</form>';
	}

	/**
	 * Looks of one page type: cards for the main page, compact choices for the others.
	 *
	 * @param string $type    Template type.
	 * @param array  $presets Presets.
	 * @param bool   $cards   Big cards with drawings.
	 */
	private static function render_type_looks( $type, array $presets, $cards ) {
		$entry   = Registry::get( $type );
		$current = $entry['id'] ? get_post( $entry['id'] ) : null;
		$default = $current ? '' : (string) key( $presets );

		echo '<fieldset class="bpb-looks-set' . ( $cards ? ' bpb-looks-set--cards' : '' ) . '"><legend>' . esc_html( CommunityPages::label( $type ) ) . '</legend>';

		if ( $current ) {
			/* translators: %s: layout title */
			echo '<p class="description">' . esc_html( sprintf( __( 'Now: %s. Picking a look replaces it only when you press Use these.', 'stax-buddy-builder' ), $current->post_title ) ) . '</p>';
		}

		echo '<div class="bpb-looks-set__options">';

		foreach ( $presets as $id => $preset ) {
			echo '<label class="bpb-pick' . ( $cards ? ' bpb-pick--card' : '' ) . '"><input type="radio" name="look[' . esc_attr( $type ) . ']" value="' . esc_attr( $id ) . '"' . checked( $default, $id, false ) . '>';

			if ( $cards ) {
				echo '<span class="bpb-pick__thumb" aria-hidden="true">' . Preview::thumb( $preset ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}

			echo '<span class="bpb-pick__name">' . esc_html( $preset['title'] ) . '</span>';

			if ( $cards && ! empty( $preset['description'] ) ) {
				echo '<span class="bpb-pick__desc">' . esc_html( $preset['description'] ) . '</span>';
			}

			echo '</label>';
		}

		echo '<label class="bpb-pick' . ( $cards ? ' bpb-pick--card bpb-pick--keep' : '' ) . '"><input type="radio" name="look[' . esc_attr( $type ) . ']" value=""' . checked( $default, '', false ) . '><span class="bpb-pick__name">' . esc_html( $current ? __( 'Keep current', 'stax-buddy-builder' ) : __( 'BuddyPress default', 'stax-buddy-builder' ) ) . '</span></label>';
		echo '</div></fieldset>';
	}

	/**
	 * Step 3: the new looks on your real pages, then "Use these".
	 */
	private static function render_review() {
		$pending = self::all_pending();
		$map     = [];

		foreach ( $pending as $type => $id ) {
			$map[ $type ] = 'post:' . $id;
		}

		$links = [];
		$tabs  = [];

		foreach ( array_keys( $pending ) as $type ) {
			$url = LayoutPreview::page_url( $type, $map );

			if ( $url ) {
				$links[ $type ] = $url;
				$tabs[ $type ]  = LayoutPreview::page_url( $type, $map, true );
			}
		}

		echo '<div class="bpb-setup__review">';
		echo '<p class="bpb-lead">' . esc_html__( 'This is your community with the new looks. Only you see them; visitors see the current pages until you press Use these.', 'stax-buddy-builder' ) . '</p>';

		if ( $links ) {
			echo '<nav class="bpb-review-tabs" aria-label="' . esc_attr__( 'Pages to review', 'stax-buddy-builder' ) . '"><ul>';
			$first = true;

			foreach ( $tabs as $type => $url ) {
				echo '<li><a href="' . esc_url( $url ) . '" data-bpb-open="' . esc_url( $links[ $type ] ) . '" target="bpb-review-frame"' . ( $first ? ' aria-current="page"' : '' ) . '>' . esc_html( CommunityPages::label( $type ) ) . '</a></li>';
				$first = false;
			}

			echo '</ul><a href="' . esc_url( reset( $links ) ) . '" target="_blank" rel="noopener" class="bpb-review-tabs__open">' . esc_html__( 'Open in a new tab', 'stax-buddy-builder' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span></a></nav>';
			echo '<div class="bpb-setup__frame bpb-setup__frame--large"><iframe name="bpb-review-frame" title="' . esc_attr__( 'Your community pages with the new looks', 'stax-buddy-builder' ) . '" src="' . esc_url( reset( $tabs ) ) . '"></iframe></div>';
		}

		echo '<div class="bpb-setup__nav">';
		echo '<a class="button" href="' . esc_url( self::url( 2 ) ) . '">' . esc_html__( 'Back', 'stax-buddy-builder' ) . '</a>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::fields( 'later' );
		echo '<button type="submit" class="button">' . esc_html__( 'Not now, keep them as drafts', 'stax-buddy-builder' ) . '</button></form>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		self::fields( 'use' );
		echo '<button type="submit" class="button button-primary button-hero">' . esc_html__( 'Use these', 'stax-buddy-builder' ) . '</button></form>';
		echo '</div></div>';
	}
}
