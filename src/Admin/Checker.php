<?php
/**
 * "Layout not applied" checker and the admin bar entry on community pages.
 *
 * On the front end, for administrators, the admin bar shows "Edit this community page" on every page BuddyBuilder
 * can design. When a layout is assigned but BuddyBuilder did not render it on that request, the entry turns into
 * "Layout not applied" and lists the likely causes (page template, theme files, Elementor Pro theme templates,
 * BuddyBoss ReadyLaunch, page cache). The last finding per page type is kept, so the Community pages screen shows it.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Compat\Status;
use Buddy_Builder\Templates\Current;
use Buddy_Builder\Templates\Dispatcher;
use Buddy_Builder\Templates\LayoutPreview;
use Buddy_Builder\Templates\Registry;

defined( 'ABSPATH' ) || die();

/**
 * Checker.
 */
final class Checker {

	/** Last "not applied" finding per type: [ type => [ time, causes[] ] ]. */
	const OPTION = 'bpb_render_issues';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar' ], 90 );
		add_action( 'wp_footer', [ __CLASS__, 'footer_check' ], 1001 );
		add_filter( 'do_shortcode_tag', [ __CLASS__, 'shortcode_seen' ], 10, 3 );
	}

	/**
	 * Layout ids printed through their shortcode on this request (1.x theme overrides print layouts that way).
	 *
	 * @var int[]
	 */
	private static $seen = [];

	/**
	 * Filter do_shortcode_tag: remember layouts printed by [elementor-template] / [bpb-layout].
	 *
	 * @param string $output Output.
	 * @param string $tag    Shortcode.
	 * @param array  $attrs  Attributes.
	 * @return string
	 */
	public static function shortcode_seen( $output, $tag, $attrs ) {
		if ( ( 'elementor-template' === $tag || Dispatcher::SHORTCODE === $tag ) && is_array( $attrs ) ) {
			if ( ! empty( $attrs['id'] ) ) {
				self::$seen[] = (int) $attrs['id'];
			} elseif ( ! empty( $attrs['type'] ) ) {
				self::$seen[] = (int) Registry::get( sanitize_key( $attrs['type'] ) )['id'];
			}
		}

		return $output;
	}

	/**
	 * Page type of this request when the checker applies (front end, administrators), '' otherwise.
	 *
	 * @return string
	 */
	private static function checked_type() {
		if ( is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! function_exists( 'bp_is_active' ) ) {
			return '';
		}

		// A single activity permalink lives under the profile URL but never shows the profile layout.
		if ( function_exists( 'bp_is_single_activity' ) && bp_is_single_activity() ) {
			return '';
		}

		$type = Current::type();

		return '' !== $type && isset( \Buddy_Builder\Templates\Types::all()[ $type ] ) ? $type : '';
	}

	/**
	 * Admin bar entry. The admin bar prints before the page content (wp_body_open), so whether the layout rendered
	 * is known only at the end of the page: footer_check() updates the entry then.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar.
	 */
	public static function admin_bar( $bar ) {
		$type = self::checked_type();

		if ( '' === $type ) {
			return;
		}

		$entry = Current::entry();
		$label = CommunityPages::label( $type );
		$edit  = $entry && $entry['id'] ? CommunityPages::edit_url( $entry['id'] ) : CommunityPages::url( $type );

		$bar->add_node(
			[
				'id'    => 'bpb-community',
				'title' => '<span class="ab-icon dashicons dashicons-groups" aria-hidden="true" style="top:2px"></span><span class="bpb-ab-title">' . esc_html( $entry ? __( 'Edit this community page', 'stax-buddy-builder' ) : __( 'Design this community page', 'stax-buddy-builder' ) ) . '</span>',
				'href'  => $edit,
			]
		);

		if ( $entry && $entry['id'] ) {
			$post = get_post( $entry['id'] );
			$bar->add_node(
				[
					'parent' => 'bpb-community',
					'id'     => 'bpb-community-layout',
					/* translators: 1: page name, 2: layout title, 3: builder name */
					'title'  => esc_html( sprintf( __( '%1$s: edit "%2$s" (%3$s)', 'stax-buddy-builder' ), $label, $post ? $post->post_title : '#' . $entry['id'], CommunityPages::builder_label( $entry['builder'] ) ) ),
					'href'   => $edit,
				]
			);
		}

		$bar->add_node(
			[
				'parent' => 'bpb-community',
				'id'     => 'bpb-community-pages',
				'title'  => esc_html__( 'All community pages', 'stax-buddy-builder' ),
				'href'   => CommunityPages::url( $type ),
			]
		);
	}

	/**
	 * End of the page: was the assigned layout rendered? Keeps the finding for the Community pages screen and turns
	 * the admin bar entry into "Layout not applied" with the likely causes.
	 */
	public static function footer_check() {
		$type  = self::checked_type();
		$entry = '' !== $type ? Current::entry() : null;

		if ( ! $entry ) {
			return;
		}

		$rendered = in_array( $type, Dispatcher::rendered(), true ) || in_array( (int) $entry['id'], self::$seen, true );
		$preview  = LayoutPreview::is_active();

		if ( $rendered ) {
			if ( ! $preview ) {
				self::forget( $type );
			}

			return;
		}

		$causes = self::causes( $type );

		if ( ! $preview ) {
			self::record( $type, $causes );
		}

		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$data = [
			'title'  => __( 'Layout not applied', 'stax-buddy-builder' ),
			'causes' => $causes,
			'url'    => CommunityPages::url( $type ),
		];
		?>
		<script id="bpb-checker">
		( function ( data ) {
			var node = document.getElementById( 'wp-admin-bar-bpb-community' );
			if ( ! node ) {
				return;
			}
			var icon = node.querySelector( '.ab-icon' );
			var title = node.querySelector( '.bpb-ab-title' );
			var list = node.querySelector( 'ul' );
			if ( icon ) {
				icon.className = 'ab-icon dashicons dashicons-warning';
			}
			if ( title ) {
				title.textContent = data.title;
			}
			if ( list ) {
				data.causes.forEach( function ( cause ) {
					var li = document.createElement( 'li' );
					var a = document.createElement( 'a' );
					a.className = 'ab-item';
					a.href = data.url;
					a.textContent = cause;
					li.appendChild( a );
					list.insertBefore( li, list.firstChild );
				} );
			}
		}( <?php echo wp_json_encode( $data ); ?> ) );
		</script>
		<?php
	}

	/**
	 * Likely causes on this request (front end), most likely first.
	 *
	 * @param string $type Template type.
	 * @return string[]
	 */
	public static function causes( $type ) {
		$causes = self::known_issues( $type );

		foreach ( [ 'single', 'archive' ] as $location ) {
			$title = self::elementor_pro_location( $location );

			if ( $title ) {
				/* translators: %s: Elementor template title */
				$causes[] = sprintf( __( 'The Elementor Pro theme template "%s" replaces this page: exclude BuddyPress pages from its display conditions.', 'stax-buddy-builder' ), $title );
			}
		}

		if ( class_exists( Status::class ) && Status::overrides() ) {
			$causes[] = __( 'Your theme has BuddyBuilder template files (in its buddybuilder folder) that replace the plugin ones.', 'stax-buddy-builder' );
		}

		if ( ! $causes ) {
			$causes[] = __( 'The theme or another plugin printed this page without the BuddyPress templates.', 'stax-buddy-builder' );
		}

		if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
			$causes[] = __( 'A page cache is on: visitors may see an older copy until you clear it.', 'stax-buddy-builder' );
		}

		/**
		 * Filters the "layout not applied" causes shown to administrators.
		 *
		 * @param string[] $causes Causes.
		 * @param string   $type   Template type.
		 */
		return (array) apply_filters( 'buddy_builder/checker/causes', $causes, $type );
	}

	/**
	 * Causes that can be read anywhere (admin screens too): page template of the BuddyPress page, ReadyLaunch.
	 *
	 * @param string $type Template type.
	 * @return string[]
	 */
	public static function known_issues( $type ) {
		$issues = [];

		if ( self::readylaunch_active() ) {
			$issues[] = __( 'BuddyBoss ReadyLaunch is on: it styles BuddyBoss pages with its own templates. Turn it off for the pages you design with BuddyBuilder.', 'stax-buddy-builder' );
		}

		$page = self::directory_page( $type );

		if ( $page ) {
			$template = get_page_template_slug( $page );

			if ( $template && 'default' !== $template ) {
				/* translators: 1: page title, 2: page template file */
				$issues[] = sprintf( __( 'The page "%1$s" uses the page template "%2$s": set it to the default template if the layout does not show.', 'stax-buddy-builder' ), get_the_title( $page ), $template );
			}
		}

		return $issues;
	}

	/**
	 * WordPress page BuddyPress uses for a type (members, groups, activity, register).
	 *
	 * @param string $type Template type.
	 * @return int
	 */
	private static function directory_page( $type ) {
		if ( ! function_exists( 'bp_core_get_directory_page_ids' ) ) {
			return 0;
		}

		$map = [
			'member-profile'    => 'members',
			'members-directory' => 'members',
			'group-profile'     => 'groups',
			'groups-directory'  => 'groups',
			'groups-create'     => 'groups',
			'sitewide-activity' => 'activity',
			'register-page'     => 'register',
		];

		$ids = (array) bp_core_get_directory_page_ids( 'all' );

		return isset( $map[ $type ], $ids[ $map[ $type ] ] ) ? (int) $ids[ $map[ $type ] ] : 0;
	}

	/**
	 * Title of the Elementor Pro theme template shown in a location on this request ('' when none).
	 *
	 * @param string $location single|archive.
	 * @return string
	 */
	private static function elementor_pro_location( $location ) {
		if ( ! class_exists( '\\ElementorPro\\Modules\\ThemeBuilder\\Module' ) ) {
			return '';
		}

		try {
			$documents = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_documents_for_location( $location );
		} catch ( \Throwable $e ) {
			return '';
		}

		if ( empty( $documents ) ) {
			return '';
		}

		$document = reset( $documents );

		return is_object( $document ) && method_exists( $document, 'get_main_id' ) ? get_the_title( $document->get_main_id() ) : __( '(untitled)', 'stax-buddy-builder' );
	}

	/**
	 * Whether BuddyBoss ReadyLaunch is on.
	 *
	 * @return bool
	 */
	public static function readylaunch_active() {
		$active = function_exists( 'bpb_is_buddyboss' ) && bpb_is_buddyboss() && function_exists( 'bb_is_readylaunch_enabled' ) && bb_is_readylaunch_enabled();

		/**
		 * Filters whether BuddyBoss ReadyLaunch is considered on.
		 *
		 * @param bool $active Active.
		 */
		return (bool) apply_filters( 'buddy_builder/readylaunch_active', $active );
	}

	/**
	 * BuddyPress components screen (BuddyPress: Settings > BuddyPress; BuddyBoss: BuddyBoss > Components).
	 *
	 * @return string
	 */
	public static function components_url() {
		$path = 'admin.php?page=bp-components';

		return function_exists( 'bp_get_admin_url' ) ? bp_get_admin_url( $path ) : admin_url( $path );
	}

	/**
	 * Last "not applied" finding of a type.
	 *
	 * @param string $type Template type.
	 * @return array|null [ time, causes ]
	 */
	public static function last_issue( $type ) {
		$all = get_option( self::OPTION, [] );

		return is_array( $all ) && isset( $all[ $type ]['causes'] ) ? $all[ $type ] : null;
	}

	/**
	 * Keep a finding (written only when it changed).
	 *
	 * @param string   $type   Template type.
	 * @param string[] $causes Causes.
	 */
	public static function record( $type, array $causes ) {
		$all = get_option( self::OPTION, [] );
		$all = is_array( $all ) ? $all : [];

		if ( isset( $all[ $type ]['causes'] ) && $all[ $type ]['causes'] === $causes ) {
			return;
		}

		$all[ $type ] = [
			'time'   => time(),
			'causes' => array_values( $causes ),
		];

		update_option( self::OPTION, $all, false );
	}

	/**
	 * Forget the finding of a type (it rendered, or the assignment changed).
	 *
	 * @param string $type Template type.
	 */
	public static function forget( $type ) {
		$all = get_option( self::OPTION, [] );

		if ( ! is_array( $all ) || ! isset( $all[ $type ] ) ) {
			return;
		}

		unset( $all[ $type ] );

		if ( $all ) {
			update_option( self::OPTION, $all, false );
		} else {
			delete_option( self::OPTION );
		}
	}
}
