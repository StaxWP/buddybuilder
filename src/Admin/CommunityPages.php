<?php
/**
 * BuddyBuilder > Community pages: one list of the BuddyPress pages BuddyBuilder can design, with the layout each one
 * uses, its builder, a live status and the actions (edit, change look, use a new look, BuddyPress default).
 *
 * Replaces the 1.x "Templates Settings" selects, the 2.0-dev "Layouts" screen and the Status "Templates" table. It
 * reads and writes the same data as before (Registry: `bpb_templates`, mirrored in `bpb_settings`), so opening or
 * saving it never changes what renders.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Features;
use Buddy_Builder\Integrations\Elementor\Integration as Elementor;
use Buddy_Builder\Presets\Presets;
use Buddy_Builder\Presets\Preview;
use Buddy_Builder\Templates\LayoutPreview;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * Community pages screen.
 */
final class CommunityPages {

	/** Admin-post action of every form on the screen. */
	const ACTION = 'bpb_community_page';

	/** Page slug (the 1.x dashboard slug, so old links and bookmarks land here). */
	const SLUG = 'settings';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_filter( 'parent_file', [ __CLASS__, 'parent_file' ] );
	}

	/**
	 * URL of the screen (optionally scrolled to one page type).
	 *
	 * @param string $type Template type.
	 * @return string
	 */
	public static function url( $type = '' ) {
		return admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG ) . ( $type ? '#bpb-page-' . $type : '' );
	}

	/**
	 * Styles of every BuddyBuilder screen (WordPress-native look, no Tailwind).
	 */
	public static function assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( 0 === strpos( $page, BPB_ADMIN_PREFIX ) ) {
			wp_enqueue_style( 'bpb-admin-ui', BPB_ASSETS_URL . 'css/admin-ui.css', [], BPB_VERSION );
		}
	}

	/**
	 * Block layout screens (list, editor) highlight the BuddyBuilder menu.
	 *
	 * @param string $parent_file Parent menu file.
	 * @return string
	 */
	public static function parent_file( $parent_file ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && PostType::POST_TYPE === $screen->post_type ) {
			return \Buddy_Builder\Helpers::get_instance()->get_slug();
		}

		return $parent_file;
	}

	/**
	 * Friendly page names (the template type names stay the technical ones elsewhere).
	 *
	 * @param string $type Template type.
	 * @return string
	 */
	public static function label( $type ) {
		$labels = [
			'member-profile'         => __( 'Member profile', 'stax-buddy-builder' ),
			'members-directory'      => __( 'Members directory', 'stax-buddy-builder' ),
			'members-directory-item' => __( 'Member card', 'stax-buddy-builder' ),
			'group-profile'          => __( 'Group page', 'stax-buddy-builder' ),
			'groups-directory'       => __( 'Groups directory', 'stax-buddy-builder' ),
			'groups-directory-item'  => __( 'Group card', 'stax-buddy-builder' ),
			'groups-create'          => __( 'Create a group', 'stax-buddy-builder' ),
			'sitewide-activity'      => __( 'Activity', 'stax-buddy-builder' ),
			'sitewide-activity-item' => __( 'Activity item', 'stax-buddy-builder' ),
			'register-page'          => __( 'Register', 'stax-buddy-builder' ),
		];

		if ( isset( $labels[ $type ] ) ) {
			return $labels[ $type ];
		}

		$types = Types::all();

		return isset( $types[ $type ]['name'] ) ? $types[ $type ]['name'] : $type;
	}

	/**
	 * Page types and the item types edited from their row, from the 1.x admin list filters (Pro adds its types and
	 * nests the item types there, so the structure stays the one Pro and add-ons already describe).
	 *
	 * @return array parent type => [ pro, children => [ type => [ pro ] ] ], in page order.
	 */
	public static function structure() {
		$base = [
			'member-profile'    => [ 'order' => 2 ],
			'members-directory' => [ 'order' => 1 ],
			'group-profile'     => [ 'order' => 4 ],
			'groups-directory'  => [ 'order' => 3 ],
			'sitewide-activity' => [ 'order' => 5 ],
		];

		foreach ( $base as $type => $item ) {
			$base[ $type ]['title'] = self::label( $type );
		}

		/** This filter is documented in BuddyBuilder 1.x admin/pages/Settings.php. */
		$list = (array) apply_filters( 'buddy_builder/admin/templates_list', $base );

		$grouped = [];

		foreach ( [ 'members' => 'members-directory', 'groups' => 'groups-directory', 'sitewide' => 'sitewide-activity' ] as $key => $type ) {
			if ( isset( $list[ $type ] ) ) {
				$grouped[ $key ] = [
					'inner' => [ $type => $list[ $type ] ],
					'order' => isset( $list[ $type ]['order'] ) ? $list[ $type ]['order'] : 0,
				];
				unset( $list[ $type ] );
			}
		}

		/** This filter is documented in BuddyBuilder 1.x admin/pages/Settings.php. */
		$grouped = (array) apply_filters( 'buddy_builder/admin/templates_list/after_restructure', array_merge( $grouped, $list ) );

		$known = Types::all();
		$out   = [];

		foreach ( $grouped as $key => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$inner  = isset( $item['inner'] ) && is_array( $item['inner'] ) ? $item['inner'] : [ $key => $item ];
			$parent = (string) key( $inner );

			if ( ! isset( $known[ $parent ] ) ) {
				continue;
			}

			$children = [];

			foreach ( array_slice( $inner, 1, null, true ) as $child => $child_item ) {
				if ( isset( $known[ $child ] ) ) {
					$children[ $child ] = [ 'pro' => ! empty( $child_item['pro'] ) ];
				}
			}

			$out[ $parent ] = [
				'pro'      => ! empty( $inner[ $parent ]['pro'] ),
				'children' => $children,
				'order'    => isset( $known[ $parent ]['order'] ) ? (int) $known[ $parent ]['order'] : 99,
			];
		}

		uasort(
			$out,
			static function ( $a, $b ) {
				return $a['order'] - $b['order'];
			}
		);

		return $out;
	}

	/**
	 * Everything the screen shows for one page type.
	 *
	 * @param string $type Template type.
	 * @param bool   $pro  Pro page type (small label).
	 * @return array
	 */
	public static function row( $type, $pro = false ) {
		$entry   = Registry::get( $type );
		$post    = $entry['id'] ? get_post( $entry['id'] ) : null;
		$builder = $post ? Registry::builder_of( $post->ID ) : '';
		$preset  = $post ? self::preset_of( $post->ID ) : null;
		$pending = Setup::pending( $type );

		return [
			'type'          => $type,
			'label'         => self::label( $type ),
			'pro'           => $pro,
			'active'        => Types::is_active( $type ),
			'entry'         => $entry,
			'post'          => $post,
			'builder'       => $builder,
			'builder_label' => self::builder_label( $builder ),
			'title'         => $post ? ( $post->post_title ? $post->post_title : __( '(no title)', 'stax-buddy-builder' ) ) : '',
			'preset'        => $preset,
			'status'        => self::status( $type, $entry, $post ),
			'edit_url'      => $post ? self::edit_url( $post->ID ) : '',
			'pending'       => $pending && $pending !== $entry['id'] && get_post( $pending ) ? $pending : 0,
			'issues'        => Checker::known_issues( $type ),
		];
	}

	/**
	 * Status of a page type: what visitors see now, in plain words.
	 *
	 * @param string        $type  Template type.
	 * @param array         $entry Registry entry.
	 * @param \WP_Post|null $post  Assigned layout.
	 * @return array [ code, text, tone (live|warn|muted) ]
	 */
	public static function status( $type, array $entry, $post ) {
		if ( ! Types::is_active( $type ) ) {
			return self::state( 'off', __( 'BuddyPress component is off', 'stax-buddy-builder' ), 'muted' );
		}

		if ( ! $entry['id'] ) {
			return self::state( 'default', __( 'BuddyPress default', 'stax-buddy-builder' ), 'muted' );
		}

		if ( ! $post ) {
			return self::state( 'missing', __( 'Layout deleted: BuddyPress default shows', 'stax-buddy-builder' ), 'warn' );
		}

		if ( Registry::ELEMENTOR === Registry::builder_of( $post->ID ) && ! Elementor::is_available() ) {
			return self::state( 'inactive', __( 'Elementor is off: BuddyPress default shows', 'stax-buddy-builder' ), 'warn' );
		}

		if ( ! Registry::is_post_populated( $post->ID ) ) {
			return self::state( 'empty', __( 'Layout is empty or not published: BuddyPress default shows', 'stax-buddy-builder' ), 'warn' );
		}

		$issue = Checker::last_issue( $type );

		if ( $issue ) {
			return self::state( 'not-applied', __( 'Not applied on the last visit', 'stax-buddy-builder' ), 'warn' );
		}

		if ( 'member-profile' === $type && function_exists( 'bp_get_total_member_count' ) ) {
			$count = (int) bp_get_total_member_count();
			/* translators: %s: number of member profiles */
			return self::state( 'live', sprintf( _n( 'Live on %s profile', 'Live on %s profiles', $count, 'stax-buddy-builder' ), number_format_i18n( $count ) ), 'live' );
		}

		if ( 'group-profile' === $type && function_exists( 'groups_get_total_group_count' ) ) {
			$count = (int) groups_get_total_group_count();
			/* translators: %s: number of groups */
			return self::state( 'live', sprintf( _n( 'Live on %s group', 'Live on %s groups', $count, 'stax-buddy-builder' ), number_format_i18n( $count ) ), 'live' );
		}

		return self::state( 'live', __( 'Live', 'stax-buddy-builder' ), 'live' );
	}

	/**
	 * Status array.
	 *
	 * @param string $code Code.
	 * @param string $text Text.
	 * @param string $tone Tone.
	 * @return array
	 */
	private static function state( $code, $text, $tone ) {
		return [
			'code' => $code,
			'text' => $text,
			'tone' => $tone,
		];
	}

	/**
	 * Builder name.
	 *
	 * @param string $builder elementor|blocks|''.
	 * @return string
	 */
	public static function builder_label( $builder ) {
		if ( Registry::BLOCKS === $builder ) {
			return __( 'Block editor', 'stax-buddy-builder' );
		}

		return Registry::ELEMENTOR === $builder ? __( 'Elementor', 'stax-buddy-builder' ) : '';
	}

	/**
	 * Builders the site can create layouts with (block editor first when enabled).
	 *
	 * @return array builder => label
	 */
	public static function builders() {
		$builders = [];

		if ( Features::blocks_enabled() ) {
			$builders[ Registry::BLOCKS ] = self::builder_label( Registry::BLOCKS );
		}

		if ( Elementor::is_available() ) {
			$builders[ Registry::ELEMENTOR ] = self::builder_label( Registry::ELEMENTOR );
		}

		return $builders;
	}

	/**
	 * Editor URL of a layout (block editor or Elementor).
	 *
	 * @param int $id Layout id.
	 * @return string
	 */
	public static function edit_url( $id ) {
		if ( Registry::BLOCKS === Registry::builder_of( $id ) ) {
			return (string) get_edit_post_link( $id, 'raw' );
		}

		return add_query_arg(
			[
				'post'   => (int) $id,
				'action' => 'elementor',
			],
			admin_url( 'post.php' )
		);
	}

	/**
	 * Preset a layout was created from (null for layouts made by hand or imported from a kit).
	 *
	 * @param int $id Layout id.
	 * @return array|null
	 */
	public static function preset_of( $id ) {
		$preset_id = (string) get_post_meta( $id, PostType::META_PRESET, true );

		return '' !== $preset_id ? Presets::get( $preset_id ) : null;
	}

	/**
	 * Layouts that can be assigned to a type: Elementor templates (when Elementor is active) and published block
	 * layouts. Without Elementor the assigned Elementor template is still listed, so it can be kept.
	 *
	 * @param string $type Template type.
	 * @return \WP_Post[]
	 */
	public static function layouts( $type ) {
		$posts = [];

		if ( Elementor::is_available() ) {
			$posts = get_posts(
				[
					'post_type'        => 'elementor_library',
					'posts_per_page'   => 100,
					'suppress_filters' => false,
					'meta_query'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery
						[
							'key'   => '_elementor_template_type',
							'value' => 'bpb-buddypress',
						],
						[
							'key'   => '_bpb_page',
							'value' => $type,
						],
					],
				]
			);
		} else {
			$entry = Registry::get( $type );
			$post  = $entry['id'] && Registry::ELEMENTOR === $entry['builder'] ? get_post( $entry['id'] ) : null;

			if ( $post ) {
				$posts[] = $post;
			}
		}

		$blocks = get_posts(
			[
				'post_type'        => PostType::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'suppress_filters' => false,
				'meta_query'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery
					[
						'key'   => PostType::META_TYPE,
						'value' => $type,
					],
				],
			]
		);

		return array_merge( $posts, $blocks );
	}

	/**
	 * Assign a layout to a page type (the one write path of the screen, the Settings form and the setup wizard).
	 *
	 * @param string $type    Template type.
	 * @param string $builder elementor|blocks.
	 * @param int    $id      Layout id (0: BuddyPress default).
	 * @return bool
	 */
	public static function assign( $type, $builder, $id ) {
		$current = Registry::get( $type );

		if ( $current['builder'] === $builder && $current['id'] === (int) $id ) {
			return true;
		}

		if ( ! Registry::set( $type, $builder, $id ) ) {
			return false;
		}

		// Elementor layouts mirror their widget settings into the BuddyPress options (1.x behaviour on save).
		if ( $id && Registry::ELEMENTOR === $builder && class_exists( '\\Buddy_Builder\\ElementorHooks' ) ) {
			\Buddy_Builder\ElementorHooks::get_instance()->save_buddypress_options( (int) $id, null );
		}

		Checker::forget( $type );

		return true;
	}

	/**
	 * Form handler: use the BuddyPress default, assign an existing layout, apply a look (preset), use or discard the
	 * new look made by the setup wizard.
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::ACTION ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stax-buddy-builder' ) );
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$do   = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$ok   = self::apply(
			$type,
			$do,
			[
				'layout'  => isset( $_POST['layout'] ) ? absint( $_POST['layout'] ) : 0,
				'preset'  => isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '',
				'builder' => isset( $_POST['builder'] ) ? sanitize_key( wp_unslash( $_POST['builder'] ) ) : '',
			]
		);

		wp_safe_redirect( add_query_arg( 'bpb_done', $ok ? $do : 'error', admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG ) ) . '#bpb-page-' . $type );
		exit;
	}

	/**
	 * Run one action of the screen (testable without the redirect).
	 *
	 * @param string $type Template type.
	 * @param string $do   default|assign|look|use-pending|discard-pending.
	 * @param array  $args layout (id), preset, builder.
	 * @return bool
	 */
	public static function apply( $type, $do, array $args = [] ) {
		if ( ! isset( Types::all()[ $type ] ) ) {
			return false;
		}

		$args = array_merge(
			[
				'layout'  => 0,
				'preset'  => '',
				'builder' => '',
			],
			$args
		);

		switch ( $do ) {
			case 'default':
				return self::assign( $type, Registry::get( $type )['builder'], 0 );

			case 'assign':
				$id = (int) $args['layout'];

				return self::assign( $type, $id ? Registry::builder_of( $id ) : Registry::get( $type )['builder'], $id );

			case 'look':
				$found = Presets::get( $args['preset'] );

				if ( ! $found || $type !== $found['type'] || is_wp_error( Presets::apply( $args['preset'], $args['builder'] ) ) ) {
					return false;
				}

				Checker::forget( $type );

				return true;

			case 'use-pending':
				$id = Setup::pending( $type );

				if ( ! $id || ! self::assign( $type, Registry::builder_of( $id ), $id ) ) {
					return false;
				}

				Setup::clear_pending( $type, false );

				return true;

			case 'discard-pending':
				Setup::clear_pending( $type, true );

				return true;
		}

		return false;
	}

	/**
	 * Hidden fields of a row form.
	 *
	 * @param string $type Template type.
	 * @param string $do   Action.
	 */
	private static function form_fields( $type, $do ) {
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		echo '<input type="hidden" name="type" value="' . esc_attr( $type ) . '">';
		echo '<input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
		wp_nonce_field( self::ACTION );
	}

	/**
	 * One-button form.
	 *
	 * @param string $type  Template type.
	 * @param string $do    Action.
	 * @param string $label Button text.
	 * @param string $class Button classes.
	 */
	private static function button_form( $type, $do, $label, $class = 'button' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-inline-form">';
		self::form_fields( $type, $do );
		echo '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	}

	/**
	 * Result notice after an action.
	 */
	private static function render_notice() {
		$done = isset( $_GET['bpb_done'] ) ? sanitize_key( wp_unslash( $_GET['bpb_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( '' === $done ) {
			return;
		}

		$messages = [
			'default'         => __( 'The page now uses the BuddyPress default. Your layout is kept: pick it again any time.', 'stax-buddy-builder' ),
			'assign'          => __( 'Layout saved for this page.', 'stax-buddy-builder' ),
			'look'            => __( 'New look created and live. Edit it to make it yours.', 'stax-buddy-builder' ),
			'use-pending'     => __( 'The new look is live.', 'stax-buddy-builder' ),
			'discard-pending' => __( 'The new look was discarded; the page is unchanged.', 'stax-buddy-builder' ),
			'setup'           => __( 'Setup done: your new community pages are live.', 'stax-buddy-builder' ),
		];

		if ( 'error' === $done || ! isset( $messages[ $done ] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'That did not work. Nothing was changed.', 'stax-buddy-builder' ) . '</p></div>';

			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $done ] ) . '</p></div>';
	}

	/**
	 * Header buttons (next to the screen title).
	 */
	public static function header_actions() {
		echo '<a class="page-title-action" href="' . esc_url( Setup::url() ) . '">' . esc_html__( 'Run setup again', 'stax-buddy-builder' ) . '</a>';

		$profile = LayoutPreview::profile_url();

		if ( $profile ) {
			echo '<a class="page-title-action" href="' . esc_url( $profile ) . '" target="_blank" rel="noopener">' . esc_html__( 'Preview my profile', 'stax-buddy-builder' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span></a>';
		}
	}

	/**
	 * The screen.
	 */
	public static function render() {
		self::render_notice();

		echo '<p class="bpb-lead">' . esc_html__( 'What each BuddyPress page looks like. Changes apply when you press Use; the BuddyPress default is always one click away.', 'stax-buddy-builder' ) . '</p>';

		if ( ! self::builders() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Turn on the block editor layouts in BuddyBuilder > Settings, or activate Elementor, to design these pages.', 'stax-buddy-builder' ) . '</p></div>';
		}

		echo '<div class="bpb-cp" role="list">';

		foreach ( self::structure() as $type => $info ) {
			$row = self::row( $type, $info['pro'] );

			$children = [];

			foreach ( $info['children'] as $child => $child_info ) {
				if ( Types::is_active( $child ) ) {
					$children[] = self::row( $child, $child_info['pro'] );
				}
			}

			self::render_row( $row, $children );
		}

		echo '</div>';

		if ( ! defined( 'BPB_PRO_VERSION' ) ) {
			printf(
				'<p class="bpb-pro-line">%1$s <a href="%2$s" target="_blank" rel="noopener">%3$s<span class="screen-reader-text"> %4$s</span></a></p>',
				esc_html__( 'More layouts and modules (register page, member and group cards, notifications, birthdays, ads) in BuddyBuilder Pro.', 'stax-buddy-builder' ),
				esc_url( 'https://staxwp.com/go/buddybuilder-pro' ),
				esc_html__( 'Learn more', 'stax-buddy-builder' ),
				esc_html__( '(opens in a new tab)', 'stax-buddy-builder' )
			);
		}
	}

	/**
	 * Thumbnail of a row: the preset drawing, a generic drawing for layouts made by hand, or "BuddyPress".
	 *
	 * @param array $row Row.
	 * @return string HTML.
	 */
	private static function thumb( array $row ) {
		if ( $row['preset'] ) {
			return Preview::svg( $row['preset']['layout'] );
		}

		if ( $row['post'] ) {
			return Preview::generic( $row['type'] );
		}

		return '<span class="bpb-cp__thumb-default">' . esc_html__( 'BuddyPress', 'stax-buddy-builder' ) . '</span>';
	}

	/**
	 * One page row (with its item layouts).
	 *
	 * @param array   $row      Row.
	 * @param array[] $children Item rows.
	 */
	private static function render_row( array $row, array $children ) {
		$type    = $row['type'];
		$heading = 'bpb-page-' . $type . '-title';

		echo '<section class="bpb-cp__row bpb-cp__row--' . esc_attr( $row['status']['code'] ) . '" id="bpb-page-' . esc_attr( $type ) . '" role="listitem" aria-labelledby="' . esc_attr( $heading ) . '">';
		echo '<div class="bpb-cp__main">';
		echo '<div class="bpb-cp__thumb" aria-hidden="true">' . self::thumb( $row ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<div class="bpb-cp__name"><h2 id="' . esc_attr( $heading ) . '">' . esc_html( $row['label'] );

		if ( $row['pro'] ) {
			echo ' <span class="bpb-label">' . esc_html__( 'Pro', 'stax-buddy-builder' ) . '</span>';
		}

		echo '</h2><p>' . esc_html( self::subtitle( $row ) ) . '</p></div>';

		echo '<div class="bpb-cp__builder"><span class="screen-reader-text">' . esc_html__( 'Builder:', 'stax-buddy-builder' ) . ' </span>' . esc_html( $row['builder_label'] ? $row['builder_label'] : __( 'none', 'stax-buddy-builder' ) ) . '</div>';

		echo '<div class="bpb-cp__status"><span class="screen-reader-text">' . esc_html__( 'Status:', 'stax-buddy-builder' ) . ' </span><span class="bpb-status bpb-status--' . esc_attr( $row['status']['tone'] ) . '">' . esc_html( $row['status']['text'] ) . '</span>';

		if ( $row['pending'] ) {
			echo '<span class="bpb-status bpb-status--info">' . esc_html__( 'New look ready, not live yet', 'stax-buddy-builder' ) . '</span>';
		}

		echo '</div>';

		echo '<div class="bpb-cp__actions">';
		self::render_actions( $row );
		echo '</div></div>';

		self::render_issues( $row );

		if ( $children ) {
			echo '<ul class="bpb-cp__items" aria-label="' . esc_attr( sprintf( /* translators: %s: page name */ __( 'Item layouts of %s', 'stax-buddy-builder' ), $row['label'] ) ) . '">';

			foreach ( $children as $child ) {
				self::render_child( $child );
			}

			echo '</ul>';
		}

		/**
		 * Fires after a page row on the Community pages screen (Pro 2.1 adds layout variations here).
		 *
		 * @param string $type Template type.
		 * @param array  $row  Row data.
		 */
		do_action( 'buddy_builder/community_pages/after_row', $type, $row );

		echo '</section>';
	}

	/**
	 * Second line of a row: layout and preset, or what shows without a layout.
	 *
	 * @param array $row Row.
	 * @return string
	 */
	private static function subtitle( array $row ) {
		if ( ! $row['active'] ) {
			return __( 'Turn the component on in the BuddyPress settings to design this page.', 'stax-buddy-builder' );
		}

		if ( ! $row['post'] ) {
			return __( 'BuddyPress default', 'stax-buddy-builder' );
		}

		$parts = [ $row['title'] ];

		if ( $row['preset'] && false === strpos( $row['title'], $row['preset']['title'] ) ) {
			$parts[] = $row['preset']['title'];
		}

		$modified = get_post_modified_time( 'U', true, $row['post'] );

		if ( $modified ) {
			/* translators: %s: time ago */
			$parts[] = sprintf( __( 'edited %s ago', 'stax-buddy-builder' ), human_time_diff( $modified ) );
		}

		return implode( ' · ', $parts );
	}

	/**
	 * Row actions: Edit, Change look / Pick a look, Use (new look), and the "More" menu.
	 *
	 * @param array $row Row.
	 */
	private static function render_actions( array $row ) {
		$type = $row['type'];

		if ( ! $row['active'] ) {
			$url = Checker::components_url();
			echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Turn on', 'stax-buddy-builder' ) . '</a>';

			return;
		}

		if ( $row['pending'] ) {
			self::button_form( $type, 'use-pending', __( 'Use', 'stax-buddy-builder' ), 'button button-primary' );
			echo '<a class="button" href="' . esc_url( LayoutPreview::page_url( $type, [ $type => 'post:' . $row['pending'] ] ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Preview', 'stax-buddy-builder' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span></a>';
		}

		if ( $row['post'] ) {
			/* translators: %s: page name */
			echo '<a class="button" href="' . esc_url( $row['edit_url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Edit the %s layout', 'stax-buddy-builder' ), $row['label'] ) ) . '">' . esc_html__( 'Edit', 'stax-buddy-builder' ) . '</a>';
		}

		self::render_looks( $row );
		self::render_more( $row );
	}

	/**
	 * "Change look" / "Pick a look": the presets of the type, one click creates and assigns the layout.
	 *
	 * @param array $row Row.
	 */
	private static function render_looks( array $row ) {
		$presets  = Presets::all( $row['type'] );
		$builders = self::builders();

		if ( ! $presets || ! $builders ) {
			return;
		}

		$primary = ! $row['post'] && ! $row['pending'];

		echo '<details class="bpb-disclosure bpb-looks"><summary class="button' . ( $primary ? ' button-primary' : '' ) . '">' . esc_html( $row['post'] ? __( 'Change look', 'stax-buddy-builder' ) : __( 'Pick a look', 'stax-buddy-builder' ) ) . '</summary>';
		echo '<div class="bpb-disclosure__panel bpb-looks__panel"><p>' . esc_html__( 'Creates a new layout from the look and makes it live. Your current layout is kept.', 'stax-buddy-builder' ) . '</p><div class="bpb-looks__grid">';

		foreach ( $presets as $id => $preset ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-look">';
			self::form_fields( $row['type'], 'look' );
			echo '<input type="hidden" name="preset" value="' . esc_attr( $id ) . '">';
			echo '<div class="bpb-look__thumb" aria-hidden="true">' . Preview::svg( $preset['layout'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<strong>' . esc_html( $preset['title'] ) . '</strong>';
			echo '<span class="bpb-look__desc">' . esc_html( isset( $preset['description'] ) ? $preset['description'] : '' ) . '</span>';

			foreach ( $builders as $builder => $label ) {
				/* translators: 1: look name, 2: builder name */
				$aria = sprintf( __( 'Use %1$s with %2$s', 'stax-buddy-builder' ), $preset['title'], $label );
				/* translators: %s: builder name */
				$text = count( $builders ) > 1 ? sprintf( __( 'Use with %s', 'stax-buddy-builder' ), $label ) : __( 'Use this look', 'stax-buddy-builder' );
				echo '<button type="submit" class="button" name="builder" value="' . esc_attr( $builder ) . '" aria-label="' . esc_attr( $aria ) . '">' . esc_html( $text ) . '</button>';
			}

			echo '</form>';
		}

		echo '</div></div></details>';
	}

	/**
	 * "More" menu: BuddyPress default, another existing layout, a new empty layout, discard the new look.
	 *
	 * @param array $row Row.
	 */
	private static function render_more( array $row ) {
		$type    = $row['type'];
		$layouts = self::layouts( $type );

		/* translators: %s: page name */
		echo '<details class="bpb-disclosure bpb-more"><summary class="button" aria-label="' . esc_attr( sprintf( __( 'More actions for %s', 'stax-buddy-builder' ), $row['label'] ) ) . '"><span aria-hidden="true">&middot;&middot;&middot;</span></summary><div class="bpb-disclosure__panel bpb-more__panel">';

		if ( $row['post'] ) {
			self::button_form( $type, 'default', __( 'Use the BuddyPress default', 'stax-buddy-builder' ), 'button-link' );
		}

		if ( $row['pending'] ) {
			self::button_form( $type, 'discard-pending', __( 'Discard the new look', 'stax-buddy-builder' ), 'button-link button-link-delete' );
		}

		if ( $layouts ) {
			$select = 'bpb-layout-' . $type;
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-more__assign">';
			self::form_fields( $type, 'assign' );
			echo '<label for="' . esc_attr( $select ) . '">' . esc_html__( 'Use a layout you already have', 'stax-buddy-builder' ) . '</label>';
			echo '<select id="' . esc_attr( $select ) . '" name="layout"><option value="0">' . esc_html__( 'BuddyPress default', 'stax-buddy-builder' ) . '</option>';

			foreach ( $layouts as $layout ) {
				$builder = self::builder_label( Registry::builder_of( $layout->ID ) );
				$title   = $layout->post_title ? $layout->post_title : __( '(no title)', 'stax-buddy-builder' );
				printf( '<option value="%1$d"%2$s>%3$s (%4$s)</option>', (int) $layout->ID, selected( $row['entry']['id'], $layout->ID, false ), esc_html( $title ), esc_html( $builder ) );
			}

			echo '</select> <button type="submit" class="button">' . esc_html__( 'Use', 'stax-buddy-builder' ) . '</button></form>';
		}

		echo '<ul class="bpb-more__links">';

		if ( Features::blocks_enabled() && isset( Types::for_blocks()[ $type ] ) ) {
			echo '<li><a href="' . esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'bpb_new_layout', 'type' => $type ], admin_url( 'admin-post.php' ) ), 'bpb_new_layout' ) ) . '">' . esc_html__( 'New empty block layout', 'stax-buddy-builder' ) . '</a></li>';
		}

		if ( Elementor::is_available() ) {
			echo '<li><a href="' . esc_url( self::new_elementor_url( $type ) ) . '">' . esc_html__( 'New empty Elementor template', 'stax-buddy-builder' ) . '</a></li>';
		}

		if ( Features::blocks_enabled() ) {
			echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=' . PostType::POST_TYPE ) ) . '">' . esc_html__( 'All block layouts', 'stax-buddy-builder' ) . '</a></li>';
		}

		echo '</ul></div></details>';
	}

	/**
	 * Elementor "new template" URL that assigns the template to the type when created (1.x behaviour).
	 *
	 * @param string $type Template type.
	 * @return string
	 */
	public static function new_elementor_url( $type ) {
		return add_query_arg(
			[
				'action'             => 'elementor_new_post',
				'post_type'          => 'elementor_library',
				'template_type'      => 'bpb-buddypress',
				'_bpb_page'          => $type,
				'_bpb_set_as_active' => 1,
				'_wpnonce'           => wp_create_nonce( 'elementor_action_new_post' ),
			],
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Known reasons a layout may not show (page template, ReadyLaunch, last visit), under the row.
	 *
	 * @param array $row Row.
	 */
	private static function render_issues( array $row ) {
		$issues = $row['issues'];
		$last   = Checker::last_issue( $row['type'] );

		if ( $last ) {
			foreach ( $last['causes'] as $cause ) {
				$issues[] = $cause;
			}
		}

		if ( ! $issues || ! $row['post'] ) {
			return;
		}

		echo '<div class="bpb-cp__issues"><strong>' . esc_html__( 'Why the layout may not show:', 'stax-buddy-builder' ) . '</strong><ul>';

		foreach ( array_unique( $issues ) as $issue ) {
			echo '<li>' . esc_html( $issue ) . '</li>';
		}

		echo '</ul></div>';
	}

	/**
	 * Item layout line (member card, group card, activity item, group creation).
	 *
	 * @param array $row Row.
	 */
	private static function render_child( array $row ) {
		echo '<li class="bpb-cp__item" id="bpb-page-' . esc_attr( $row['type'] ) . '">';
		echo '<span class="bpb-cp__item-name">' . esc_html( $row['label'] );

		if ( $row['pro'] ) {
			echo ' <span class="bpb-label">' . esc_html__( 'Pro', 'stax-buddy-builder' ) . '</span>';
		}

		echo '</span>';
		echo '<span class="bpb-cp__item-layout">' . esc_html( $row['post'] ? $row['title'] . ' (' . $row['builder_label'] . ')' : __( 'BuddyPress default', 'stax-buddy-builder' ) ) . '</span>';
		if ( 'default' !== $row['status']['code'] ) {
			echo '<span class="bpb-status bpb-status--' . esc_attr( $row['status']['tone'] ) . '">' . esc_html( $row['status']['text'] ) . '</span>';
		}

		echo '<span class="bpb-cp__item-actions">';

		if ( $row['post'] ) {
			/* translators: %s: item layout name */
			echo '<a class="button button-small" href="' . esc_url( $row['edit_url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Edit the %s layout', 'stax-buddy-builder' ), $row['label'] ) ) . '">' . esc_html__( 'Edit', 'stax-buddy-builder' ) . '</a>';
		}

		self::render_more( $row );
		echo '</span></li>';
	}
}
