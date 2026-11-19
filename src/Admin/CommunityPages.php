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
use Buddy_Builder\Presets\Restyle;
use Buddy_Builder\Presets\Styles;
use Buddy_Builder\Templates\Cards;
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

	/** Option with the assignment a change replaced, for Undo: [ type, builder, id, user, time ]. */
	const UNDO = 'bpb_undo';

	/** How long Undo stays offered, in seconds. */
	const UNDO_TTL = DAY_IN_SECONDS;

	/**
	 * Id of the copy the last "duplicate" made (for the notice link).
	 *
	 * @var int
	 */
	private static $copy = 0;

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
			wp_enqueue_script( 'bpb-disclosure', BPB_ASSETS_URL . 'js/disclosure.js', [], BPB_VERSION, true );
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

			if ( ! $count ) {
				return self::state( 'live', __( 'Live, no groups yet', 'stax-buddy-builder' ), 'live' );
			}

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

		$type   = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$do     = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$layout = isset( $_POST['layout'] ) ? absint( $_POST['layout'] ) : 0;

		// "Restyle, keep my layout" is one of the answers of a style's confirm step.
		if ( 'look' === $do && ! empty( $_POST['restyle'] ) ) {
			$do = 'restyle';
		}

		if ( 'import' === $do ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- an upload: the file is validated and its JSON sanitized by LayoutActions.
			$id   = isset( $_FILES['bpb_layout_file'] ) && is_array( $_FILES['bpb_layout_file'] ) ? LayoutActions::import_upload( $_FILES['bpb_layout_file'] ) : LayoutActions::import_upload( [] );
			$type = is_wp_error( $id ) ? '' : PostType::type_of( $id );

			wp_safe_redirect(
				add_query_arg(
					array_filter(
						[
							'bpb_done'         => is_wp_error( $id ) ? 'import-error' : 'import',
							'bpb_import_error' => is_wp_error( $id ) ? $id->get_error_code() : '',
							'bpb_type'         => $type,
							'bpb_copy'         => is_wp_error( $id ) ? 0 : $id,
						]
					),
					admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG )
				) . ( $type ? '#bpb-page-' . $type : '#bpb-import' )
			);
			exit;
		}

		if ( 'export' === $do && LayoutActions::can( 'export', $layout ) ) {
			$file = LayoutActions::export( $layout );

			if ( ! is_wp_error( $file ) ) {
				LayoutActions::send( $file );
			}
		}

		$ok = 'export' !== $do && self::apply(
			$type,
			$do,
			[
				'layout'  => $layout,
				'preset'  => isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : '',
				'builder' => isset( $_POST['builder'] ) ? sanitize_key( wp_unslash( $_POST['builder'] ) ) : '',
			]
		);

		wp_safe_redirect(
			add_query_arg(
				array_filter(
					[
						'bpb_done' => $ok ? $do : 'error',
						'bpb_type' => $type,
						'bpb_copy' => $ok && 'duplicate' === $do ? self::$copy : 0,
					]
				),
				admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG )
			) . '#bpb-page-' . $type
		);
		exit;
	}

	/**
	 * Run one action of the screen (testable without the redirect).
	 *
	 * @param string $type Template type.
	 * @param string $do   default|assign|look|restyle|use-pending|discard-pending|undo|duplicate|delete.
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

		// Every change that makes something else live can be undone once (review W6).
		if ( in_array( $do, [ 'default', 'assign', 'look', 'restyle', 'use-pending' ], true ) ) {
			$before = Registry::get( $type );
		}

		$done = self::run( $type, $do, $args );

		if ( $done && isset( $before ) && Registry::get( $type ) !== $before ) {
			self::remember_undo( $type, $before );
		}

		return $done;
	}

	/**
	 * Keep what Undo puts back: the assignment before the change, and the layout a delete moved to the trash.
	 *
	 * @param string     $type    Template type.
	 * @param array      $before  Registry entry before the change.
	 * @param array|null $trashed LayoutActions::trash() record.
	 */
	private static function remember_undo( $type, array $before, $trashed = null ) {
		update_option(
			self::UNDO,
			array_filter(
				[
					'type'    => $type,
					'builder' => $before['builder'],
					'id'      => (int) $before['id'],
					'user'    => get_current_user_id(),
					'time'    => time(),
					'trashed' => $trashed,
				],
				static function ( $value ) {
					return null !== $value;
				}
			),
			false
		);
	}

	/**
	 * The change offered by Undo, or null.
	 *
	 * @return array|null [ type, builder, id, user, time ].
	 */
	public static function undo_entry() {
		$undo = get_option( self::UNDO );

		if ( ! is_array( $undo ) || empty( $undo['type'] ) || ! isset( Types::all()[ $undo['type'] ] ) || time() - (int) $undo['time'] > self::UNDO_TTL ) {
			return null;
		}

		return $undo;
	}

	/**
	 * One action of the screen, without the Undo record.
	 *
	 * @param string $type Template type.
	 * @param string $do   Action.
	 * @param array  $args Arguments.
	 * @return bool
	 */
	private static function run( $type, $do, array $args ) {
		switch ( $do ) {
			case 'undo':
				$undo = self::undo_entry();

				if ( ! $undo || $type !== $undo['type'] ) {
					return false;
				}

				if ( ! empty( $undo['trashed'] ) ) {
					if ( ! current_user_can( 'delete_post', (int) $undo['trashed']['id'] ) || ! LayoutActions::restore( $undo['trashed'] ) ) {
						return false;
					}
				} elseif ( ( $undo['id'] && ! get_post( $undo['id'] ) ) || ! self::assign( $type, $undo['builder'], $undo['id'] ) ) {
					return false;
				}

				delete_option( self::UNDO );

				return true;

			case 'duplicate':
				if ( ! LayoutActions::can( 'duplicate', $args['layout'] ) ) {
					return false;
				}

				$copy = LayoutActions::duplicate( $args['layout'] );

				self::$copy = is_wp_error( $copy ) ? 0 : $copy;

				return (bool) self::$copy;

			case 'delete':
				$before = Registry::get( $type );
				$record = LayoutActions::can( 'delete', $args['layout'] ) ? LayoutActions::trash( $args['layout'] ) : false;

				if ( ! $record ) {
					return false;
				}

				self::remember_undo( $type, $before, $record );

				return true;

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

			case 'restyle':
				$entry = Registry::get( $type );
				$found = Presets::get( $args['preset'] );

				if ( ! $found || $type !== $found['type'] || empty( $entry['id'] ) || is_wp_error( Restyle::apply( (int) $entry['id'], $args['preset'] ) ) ) {
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
	 * @param string $type   Template type.
	 * @param string $do     Action.
	 * @param string $label  Button text.
	 * @param string $class  Button classes.
	 * @param string $aria   Accessible name when the label alone is ambiguous.
	 * @param int    $layout Layout the action is about (0: none).
	 */
	private static function button_form( $type, $do, $label, $class = 'button', $aria = '', $layout = 0 ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-inline-form">';
		self::form_fields( $type, $do );

		if ( $layout ) {
			echo '<input type="hidden" name="layout" value="' . (int) $layout . '">';
		}

		echo '<button type="submit" class="' . esc_attr( $class ) . '"' . ( $aria ? ' aria-label="' . esc_attr( $aria ) . '"' : '' ) . '>' . esc_html( $label ) . '</button></form>';
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
			'look'            => __( 'The page uses the new style now. Edit it to make it yours.', 'stax-buddy-builder' ),
			'restyle'         => __( 'The page uses the new style with your layout and its edits. Your previous layout is kept.', 'stax-buddy-builder' ),
			'use-pending'     => __( 'The new style is live.', 'stax-buddy-builder' ),
			'discard-pending' => __( 'The new style was discarded; the page is unchanged.', 'stax-buddy-builder' ),
			'setup'           => __( 'Setup done: your new community pages are live.', 'stax-buddy-builder' ),
			'undo'            => __( 'Undone: the page uses what it used before.', 'stax-buddy-builder' ),
			'duplicate'       => __( 'A copy was saved as a draft. No page uses it until you pick it.', 'stax-buddy-builder' ),
			'delete'          => __( 'The layout was moved to the trash.', 'stax-buddy-builder' ),
			'import'          => __( 'The layout was imported as a draft. No page uses it until you pick it.', 'stax-buddy-builder' ),
		];

		if ( 'import-error' === $done ) {
			$code   = isset( $_GET['bpb_import_error'] ) ? sanitize_key( wp_unslash( $_GET['bpb_import_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$errors = LayoutActions::import_messages();

			echo '<div class="notice notice-error is-dismissible" role="alert"><p>' . esc_html( isset( $errors[ $code ] ) ? $errors[ $code ] : __( 'That did not work. Nothing was changed.', 'stax-buddy-builder' ) ) . '</p></div>';

			return;
		}

		if ( 'error' === $done || ! isset( $messages[ $done ] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'That did not work. Nothing was changed.', 'stax-buddy-builder' ) . '</p></div>';

			return;
		}

		$undo    = self::undo_entry();
		$type    = $undo && isset( $_GET['bpb_type'] ) && $undo['type'] === sanitize_key( wp_unslash( $_GET['bpb_type'] ) ) ? $undo['type'] : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$trashed = $type && ! empty( $undo['trashed'] ) ? $undo['trashed'] : null;
		$copy    = isset( $_GET['bpb_copy'] ) ? absint( $_GET['bpb_copy'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$message = $messages[ $done ];

		if ( 'delete' === $done && $trashed && ! empty( $trashed['assigned'] ) ) {
			$message .= ' ' . __( 'The page now shows the BuddyPress default.', 'stax-buddy-builder' );
		}

		echo '<div class="notice notice-success is-dismissible bpb-done"><p>' . esc_html( $message );

		if ( in_array( $done, [ 'duplicate', 'import' ], true ) && $copy && current_user_can( 'edit_post', $copy ) ) {
			echo ' <a href="' . esc_url( self::edit_url( $copy ) ) . '">' . esc_html( 'import' === $done ? __( 'Edit the imported layout', 'stax-buddy-builder' ) : __( 'Edit the copy', 'stax-buddy-builder' ) ) . '</a>';
		}

		echo '</p>';

		// Undo follows the change it belongs to: a delete offers it after "delete", a page change after the others.
		if ( $type && ! in_array( $done, [ 'undo', 'duplicate', 'import' ], true ) && ( 'delete' === $done ) === (bool) $trashed ) {
			echo '<p>';
			/* translators: %s: page name */
			self::button_form( $type, 'undo', __( 'Undo', 'stax-buddy-builder' ), 'button', sprintf( __( 'Undo the change on %s', 'stax-buddy-builder' ), self::label( $type ) ) );
			echo ' <span class="description">' . esc_html( $trashed ? __( 'Takes the layout out of the trash and gives it back to the pages that used it.', 'stax-buddy-builder' ) : __( 'Puts back what the page used before. The new layout is kept.', 'stax-buddy-builder' ) ) . '</span></p>';
		}

		echo '</div>';
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

		echo '<p class="bpb-lead">' . esc_html__( 'What each BuddyPress page looks like. Preview a style before it goes live; Undo and the BuddyPress default are always one click away.', 'stax-buddy-builder' ) . '</p>';

		if ( ! self::builders() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Turn on the block editor layouts in BuddyBuilder > Settings, or activate Elementor, to design these pages.', 'stax-buddy-builder' ) . '</p></div>';
		}

		echo '<div class="bpb-cp" role="list">';

		foreach ( self::structure() as $type => $info ) {
			$row = self::row( $type, $info['pro'] );

			$children = [];
			$pages    = [];

			// Cards stay under their page; a page nested there by the list filters (Create a group) gets its own row.
			foreach ( $info['children'] as $child => $child_info ) {
				if ( Types::is_active( $child ) ) {
					if ( self::is_card( $child ) ) {
						$children[] = self::row( $child, $child_info['pro'] );
					} else {
						$pages[] = self::row( $child, $child_info['pro'] );
					}
				}
			}

			self::render_row( $row, $children );

			foreach ( $pages as $page ) {
				self::render_row( $page, [] );
			}
		}

		echo '</div>';

		self::render_import();

		\Buddy_Builder\Upsell\Upsell::render_features();
	}

	/**
	 * Whether a type is a card (an item layout repeated in a list: member card, group card, activity item).
	 *
	 * @param string $type Template type.
	 * @return bool
	 */
	private static function is_card( $type ) {
		return '-item' === substr( $type, -5 );
	}

	/**
	 * Import a block layout export (the JSON of More > Export): a draft layout, used once picked for its page.
	 */
	private static function render_import() {
		if ( ! isset( self::builders()[ Registry::BLOCKS ] ) ) {
			return;
		}

		echo '<details class="bpb-disclosure bpb-cp__import" id="bpb-import"><summary class="button">' . esc_html__( 'Import layout', 'stax-buddy-builder' ) . '</summary><div class="bpb-disclosure__panel">';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
		self::form_fields( '', 'import' );
		echo '<p><label for="bpb-import-file">' . esc_html__( 'Block layout export (.json)', 'stax-buddy-builder' ) . '</label><br><input type="file" id="bpb-import-file" name="bpb_layout_file" accept=".json,application/json" required aria-describedby="bpb-import-help"></p>';
		echo '<p class="description" id="bpb-import-help">' . esc_html__( 'A file made by More > Export of a block layout. It becomes a draft; pick it on its page to use it. Elementor exports go to Elementor > Templates > Import Templates.', 'stax-buddy-builder' ) . '</p>';
		echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Import', 'stax-buddy-builder' ) . '</button></p>';
		echo '</form></div></details>';
	}

	/**
	 * Thumbnail of a row: the preset drawing, a generic drawing for layouts made by hand, or "BuddyPress".
	 *
	 * @param array $row Row.
	 * @return string HTML.
	 */
	private static function thumb( array $row ) {
		if ( $row['preset'] ) {
			return Preview::thumb( $row['preset'] );
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

		echo self::pro_label( $row ) . '</h2><p>' . esc_html( self::subtitle( $row ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- pro_label() escapes.
		$children = self::render_card_line( $row, $children );
		echo '</div>';

		echo '<div class="bpb-cp__builder"><span class="screen-reader-text">' . esc_html__( 'Builder:', 'stax-buddy-builder' ) . ' </span>' . esc_html( $row['builder_label'] ? $row['builder_label'] : __( 'none', 'stax-buddy-builder' ) ) . '</div>';

		echo '<div class="bpb-cp__status"><span class="screen-reader-text">' . esc_html__( 'Status:', 'stax-buddy-builder' ) . ' </span><span class="bpb-status bpb-status--' . esc_attr( $row['status']['tone'] ) . '">' . esc_html( $row['status']['text'] ) . '</span>';

		if ( $row['pending'] ) {
			echo '<span class="bpb-status bpb-status--info">' . esc_html__( 'New style ready, not live yet', 'stax-buddy-builder' ) . '</span>';
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
		 * Fires after a page row on the Community pages screen (BuddyBuilder Pro adds layout variations here).
		 *
		 * @param string $type Template type.
		 * @param array  $row  Row data.
		 */
		do_action( 'buddy_builder/community_pages/after_row', $type, $row );

		echo '</section>';
	}

	/**
	 * The "Pro" label of a Pro page type, only while BuddyBuilder Pro is not active and licensed (review 3.4: no Pro
	 * badges for Pro owners).
	 *
	 * @param array $row Row.
	 * @return string HTML ('' without the label).
	 */
	private static function pro_label( array $row ) {
		$pro = App::pro();

		if ( empty( $row['pro'] ) || ( $pro['active'] && false !== $pro['licensed'] ) ) {
			return '';
		}

		return ' <span class="bpb-label">' . esc_html__( 'Pro', 'stax-buddy-builder' ) . '</span>';
	}

	/**
	 * The one "Card" line under the directory and activity rows: how the page's list shows each member, group or
	 * post, and where it changes. A card style: "Card: Compact, Change" (the page layout's editor with the list
	 * selected) and, with BuddyBuilder Pro, "Design the card" (a card layout from a style). A card layout: its own item
	 * row is the card line (Edit, Change card, More), so no text line repeats it.
	 *
	 * @param array   $row      Row.
	 * @param array[] $children Item rows of the page.
	 * @return array[] The item rows still to list under the page (the card row only when a card layout exists).
	 */
	private static function render_card_line( array $row, array $children ) {
		$object = array_search( $row['type'], Cards::PAGE_TYPES, true );

		if ( false === $object || ! $row['active'] ) {
			return $children;
		}

		$item = Cards::ITEM_TYPES[ $object ];
		$card = null;

		foreach ( $children as $i => $child ) {
			if ( $item === $child['type'] ) {
				$card = $child;

				// No card layout: the card row would only say "BuddyPress default" under a page with a card style.
				if ( ! $child['post'] ) {
					unset( $children[ $i ] );
				}
			}
		}

		if ( $card && $card['post'] ) {
			return $children;
		}

		echo '<div class="bpb-cp__card">';

		if ( Cards::has_card_layout( $object ) ) {
			echo esc_html__( 'Card: your card layout', 'stax-buddy-builder' );
		} else {
			/* translators: %s: card style name, e.g. Compact */
			echo esc_html( sprintf( __( 'Card: %s', 'stax-buddy-builder' ), Cards::label( $object, Cards::saved( $object ) ) ) );

			$labels = Cards::labels();

			// Change only when there is another card style to pick; without BuddyBuilder Pro, Classic is the only one.
			if ( $row['post'] && $row['edit_url'] && ! empty( $labels[ $object ] ) ) {
				/* translators: %s: page name */
				echo ' <a href="' . esc_url( add_query_arg( Cards::SELECT_ARG, $object, $row['edit_url'] ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Change the card style: opens the %s layout with its list selected, Card setting', 'stax-buddy-builder' ), $row['label'] ) ) . '">' . esc_html__( 'Change', 'stax-buddy-builder' ) . '</a>';
			} elseif ( \Buddy_Builder\Upsell\Upsell::enabled() ) {
				echo ' <a href="' . esc_url( \Buddy_Builder\Upsell\Upsell::url( 'card-designer', 'card-line' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'More card styles', 'stax-buddy-builder' ) . ' <span class="bpb-label">' . esc_html__( 'Pro', 'stax-buddy-builder' ) . '</span><span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span></a>';
			}

			if ( $card ) {
				self::render_looks( $card, true );
			}
		}

		echo '</div>';

		return $children;
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
	 * @param array $row   Row.
	 * @param bool  $child An item row (small button under its page row).
	 */
	private static function render_looks( array $row, $child = false ) {
		$presets  = Presets::all( $row['type'] );
		$builders = self::builders();

		if ( ! $presets || ! $builders ) {
			return;
		}

		$primary = ! $row['post'] && ! $row['pending'];
		$card    = self::is_card( $row['type'] );
		$current = $row['post'] ? (int) $row['post']->ID : 0;
		$edited  = $current && Restyle::is_edited( $current );

		// Cards (item layouts, BuddyBuilder Pro): "Design the card" starts one from a style's card (review 3.3).
		if ( $card ) {
			$summary = $row['post'] ? __( 'Change card', 'stax-buddy-builder' ) : __( 'Design the card', 'stax-buddy-builder' );
			$intro   = __( 'Start the card from a style, then edit it like any layout. Using it makes it live in every list of this kind; your current card is kept and Undo puts it back.', 'stax-buddy-builder' );
		} else {
			$summary = $row['post'] ? __( 'Change style', 'stax-buddy-builder' ) : __( 'Pick a style', 'stax-buddy-builder' );
			$intro   = __( 'Preview a style on the real page first. Using it creates a new layout and makes it live; your current layout is kept and Undo puts it back.', 'stax-buddy-builder' );
		}

		if ( $edited && '' !== Styles::of_layout( $current ) ) {
			$intro .= ' ' . __( 'This layout has changes of your own: "Restyle, keep my layout" keeps them and changes only the style.', 'stax-buddy-builder' );
		}

		// Item rows sit under their page row: their summary names the item for screen readers.
		/* translators: 1: action (Design the card, Pick a style, ...), 2: item name (Member card, Create a group, ...) */
		$aria = $child ? ' aria-label="' . esc_attr( sprintf( __( '%1$s: %2$s', 'stax-buddy-builder' ), $summary, $row['label'] ) ) . '"' : '';
		echo '<details class="bpb-disclosure bpb-looks"><summary class="button' . ( $child ? ' button-small' : ( $primary ? ' button-primary' : '' ) ) . '"' . $aria . '>' . esc_html( $summary ) . '</summary>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $aria is escaped above.
		echo '<div class="bpb-disclosure__panel bpb-looks__panel"><p>' . esc_html( $intro ) . '</p><div class="bpb-looks__grid">';

		foreach ( $presets as $id => $preset ) {
			// The register page previews too: LayoutPreview shows it to the signed-in administrator as to a visitor.
			$preview = LayoutPreview::page_url( $row['type'], [ $row['type'] => 'preset:' . $id ] );

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-look">';
			self::form_fields( $row['type'], 'look' );
			echo '<input type="hidden" name="preset" value="' . esc_attr( $id ) . '">';
			echo '<div class="bpb-look__thumb" aria-hidden="true">' . Preview::thumb( $preset ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<strong>' . esc_html( $preset['title'] ) . '</strong>';
			echo '<span class="bpb-look__desc">' . esc_html( isset( $preset['description'] ) ? $preset['description'] : '' ) . '</span>';

			if ( $preview ) {
				/* translators: %s: style name */
				echo '<a class="button" href="' . esc_url( $preview ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( sprintf( __( 'Preview %s on the real page (opens in a new tab)', 'stax-buddy-builder' ), $preset['title'] ) ) . '">' . esc_html__( 'Preview', 'stax-buddy-builder' ) . '</a>';
			}

			// Confirm step without JavaScript: the buttons that change the live page sit behind a second click.
			/* translators: %s: style name */
			echo '<details class="bpb-look__confirm"><summary class="button button-primary" aria-label="' . esc_attr( sprintf( __( 'Use %s', 'stax-buddy-builder' ), $preset['title'] ) ) . '">' . esc_html__( 'Use this style', 'stax-buddy-builder' ) . '</summary>';
			/* translators: %s: page name */
			echo '<p>' . esc_html( sprintf( __( 'Make it live on %s now?', 'stax-buddy-builder' ), $row['label'] ) ) . '</p>';

			// Restyle keeps the current layout and swaps its style (review item 12): the first answer when the owner
			// edited the layout, the second one otherwise.
			$restyle = $current && Restyle::can( $current, $id );

			if ( $restyle && $edited ) {
				self::restyle_button( $preset, true );
			}

			foreach ( $builders as $builder => $label ) {
				/* translators: 1: style name, 2: builder name */
				$aria = sprintf( __( 'Use %1$s with %2$s', 'stax-buddy-builder' ), $preset['title'], $label );

				if ( $restyle ) {
					/* translators: %s: builder name */
					$text = count( $builders ) > 1 ? sprintf( __( 'Replace with the style\'s layout (%s)', 'stax-buddy-builder' ), $label ) : __( 'Replace with the style\'s layout', 'stax-buddy-builder' );
				} else {
					/* translators: %s: builder name */
					$text = count( $builders ) > 1 ? sprintf( __( 'Yes, build it with %s', 'stax-buddy-builder' ), $label ) : __( 'Yes, make it live', 'stax-buddy-builder' );
				}

				echo '<button type="submit" class="button' . ( $restyle && $edited ? '' : ' button-primary' ) . '" name="builder" value="' . esc_attr( $builder ) . '" aria-label="' . esc_attr( $aria ) . '">' . esc_html( $text ) . '</button>';
			}

			if ( $restyle && $edited ) {
				echo '<p class="bpb-look__warn">' . esc_html__( 'Replacing leaves out the changes you made to this page\'s layout. The current layout is kept and Undo puts it back.', 'stax-buddy-builder' ) . '</p>';
			} elseif ( $restyle ) {
				self::restyle_button( $preset, false );
			}

			echo '</details></form>';
		}

		echo '</div>';

		// The 1.x Elementor starter kits are not offered next to the styles (the 2.0 styles replace them; two kinds of
		// "style" confused owners). Their screen stays registered for bookmarks.
		echo '</div></details>';
	}

	/**
	 * "Restyle, keep my layout": the current layout (elements, settings, edits) with the new style.
	 *
	 * @param array $preset  Preset.
	 * @param bool  $primary The first answer (the layout was edited).
	 */
	private static function restyle_button( array $preset, $primary ) {
		/* translators: %s: style name */
		$aria = sprintf( __( 'Restyle with %s and keep my layout', 'stax-buddy-builder' ), $preset['title'] );

		echo '<button type="submit" class="button' . ( $primary ? ' button-primary' : '' ) . '" name="restyle" value="1" aria-label="' . esc_attr( $aria ) . '">' . esc_html__( 'Restyle, keep my layout', 'stax-buddy-builder' ) . '</button>';

		if ( $primary ) {
			echo '<p class="bpb-look__note">' . esc_html__( 'Your elements and edits stay; only the style changes.', 'stax-buddy-builder' ) . '</p>';
		}
	}

	/**
	 * "More" menu: the layout actions (duplicate, history, export, delete), BuddyPress default, another existing layout,
	 * a new empty layout, discard the new look.
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
			self::button_form( $type, 'discard-pending', __( 'Discard the new style', 'stax-buddy-builder' ), 'button-link button-link-delete' );
		}

		self::render_layout_actions( $row );

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
	 * The layout part of the "More" menu: Duplicate, History, Export, Delete (review B-L4). Forms post to
	 * admin-post.php, so every action works without JavaScript; Delete asks once more before it moves the layout to
	 * the trash, and the notice after it offers Undo.
	 *
	 * @param array $row Row.
	 */
	private static function render_layout_actions( array $row ) {
		$id = $row['post'] ? (int) $row['post']->ID : 0;

		if ( ! $id || ! LayoutActions::is_layout( $id ) ) {
			return;
		}

		$type   = $row['type'];
		$layout = $row['title'];
		$aria   = static function ( $action ) use ( $layout ) {
			/* translators: 1: action, 2: layout title */
			return sprintf( __( '%1$s: %2$s', 'stax-buddy-builder' ), $action, $layout );
		};

		echo '<ul class="bpb-more__layout" aria-label="' . esc_attr( sprintf( /* translators: %s: layout title */ __( 'Layout %s', 'stax-buddy-builder' ), $layout ) ) . '">';

		if ( LayoutActions::can( 'duplicate', $id ) ) {
			echo '<li>';
			self::button_form( $type, 'duplicate', __( 'Duplicate', 'stax-buddy-builder' ), 'button-link', $aria( __( 'Duplicate', 'stax-buddy-builder' ) ), $id );
			echo '</li>';
		}

		if ( LayoutActions::can( 'history', $id ) ) {
			$history = LayoutActions::history_url( $id );
			echo '<li>';

			if ( $history ) {
				echo '<a href="' . esc_url( $history ) . '" aria-label="' . esc_attr( $aria( __( 'History', 'stax-buddy-builder' ) ) ) . '">' . esc_html__( 'History', 'stax-buddy-builder' ) . '</a>';
			} else {
				echo '<span class="bpb-more__muted">' . esc_html__( 'History: no saved changes yet', 'stax-buddy-builder' ) . '</span>';
			}

			echo '</li>';
		}

		if ( LayoutActions::can( 'export', $id ) && ( Registry::BLOCKS === $row['builder'] || Elementor::is_available() ) ) {
			echo '<li>';
			self::button_form( $type, 'export', __( 'Export', 'stax-buddy-builder' ), 'button-link', $aria( __( 'Export', 'stax-buddy-builder' ) ), $id );
			echo '</li>';
		}

		if ( LayoutActions::can( 'delete', $id ) ) {
			echo '<li><details class="bpb-more__confirm"><summary class="button-link button-link-delete" aria-label="' . esc_attr( $aria( __( 'Delete', 'stax-buddy-builder' ) ) ) . '">' . esc_html__( 'Delete', 'stax-buddy-builder' ) . '</summary>';
			echo '<p>' . esc_html(
				LayoutActions::assigned_types( $id )
					/* translators: %s: page name */
					? sprintf( __( 'Move it to the trash? %s shows the BuddyPress default until you pick another layout.', 'stax-buddy-builder' ), $row['label'] )
					: __( 'Move it to the trash?', 'stax-buddy-builder' )
			) . '</p>';
			self::button_form( $type, 'delete', __( 'Yes, move to trash', 'stax-buddy-builder' ), 'button button-small', $aria( __( 'Move to trash', 'stax-buddy-builder' ) ), $id );
			echo '</details></li>';
		}

		echo '</ul>';
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
		echo '<span class="bpb-cp__item-name">' . esc_html( $row['label'] ) . self::pro_label( $row ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- pro_label() escapes.
		echo '<span class="bpb-cp__item-layout">' . esc_html( $row['post'] ? $row['title'] . ' (' . $row['builder_label'] . ')' : __( 'BuddyPress default', 'stax-buddy-builder' ) ) . '</span>';
		if ( 'default' !== $row['status']['code'] ) {
			echo '<span class="bpb-status bpb-status--' . esc_attr( $row['status']['tone'] ) . '">' . esc_html( $row['status']['text'] ) . '</span>';
		}

		echo '<span class="bpb-cp__item-actions">';

		if ( $row['post'] ) {
			/* translators: %s: item layout name */
			echo '<a class="button button-small" href="' . esc_url( $row['edit_url'] ) . '" aria-label="' . esc_attr( sprintf( __( 'Edit the %s layout', 'stax-buddy-builder' ), $row['label'] ) ) . '">' . esc_html__( 'Edit', 'stax-buddy-builder' ) . '</a>';
		}

		self::render_looks( $row, true );
		self::render_more( $row );
		echo '</span></li>';
	}
}
