<?php
/**
 * Card styles of the member, group and activity lists (review decision 3): how one item looks inside a directory or
 * a list, chosen on the list block or widget (attribute and control `card`), without a card layout.
 *
 * - Members and groups: classic (the BuddyPress card, unchanged markup), compact (one row), cover (cover image on
 *   top). Activity: classic, compact, boxed, post (2.0 item: header, text, labelled action bar).
 * - Classic prints exactly the 1.x markup, so saved layouts render as before.
 * - Directory lists load their items over AJAX, so the style of the assigned layout is mirrored into the option
 *   `bpb_cards` (Settings\Mirror, both builders), next to the 1.x columns option. Lists that render in the same
 *   request (general lists, editor previews) push their style for the duration of the loop.
 * - A card layout (BuddyBuilder Pro "Design the card", item template types) wins over the style.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Card styles.
 */
final class Cards {

	/** Option: [ members_directory|groups_directory|activity => style ]. */
	const OPTION = 'bpb_cards';

	/** Styles per object. */
	const STYLES = [
		'members'  => [ 'classic', 'compact', 'cover' ],
		'groups'   => [ 'classic', 'compact', 'cover' ],
		'activity' => [ 'classic', 'compact', 'boxed', 'post' ],
	];

	/** Option key per object (the directory or the activity page). */
	const TARGETS = [
		'members'  => 'members_directory',
		'groups'   => 'groups_directory',
		'activity' => 'activity',
	];

	/** Item template type per object (a card layout wins over the style). */
	const ITEM_TYPES = [
		'members'  => 'members-directory-item',
		'groups'   => 'groups-directory-item',
		'activity' => 'sitewide-activity-item',
	];

	/**
	 * Styles pushed by lists that render in this request.
	 *
	 * @var array[] [ object, style ]
	 */
	private static $stack = [];

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register' ], 1 );
		add_action( 'enqueue_block_assets', [ __CLASS__, 'register' ], 1 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_for_page' ], 20 );
	}

	/**
	 * Register the stylesheet (tokens first).
	 */
	public static function register() {
		if ( ! wp_style_is( 'bpb-cards', 'registered' ) ) {
			wp_register_style( 'bpb-cards', BPB_ASSETS_URL . 'css/cards.css', [ \Buddy_Builder\Assets\Assets::tokens() ], BPB_VERSION );
		}
	}

	/**
	 * Object of a list element or widget name, '' when it has no card style.
	 *
	 * @param string $name Element name or 1.x widget name.
	 * @return string members|groups|activity|''
	 */
	public static function object_of( $name ) {
		$map = [
			'members-listing'            => 'members',
			'bpb-members-directory-list' => 'members',
			'groups-listing'             => 'groups',
			'bpb-groups-directory-list'  => 'groups',
			'activity-stream'            => 'activity',
			'bpb-sitewide-content'       => 'activity',
		];

		return isset( $map[ $name ] ) ? $map[ $name ] : '';
	}

	/**
	 * A valid style for an object ('classic' for anything else).
	 *
	 * @param string $object members|groups|activity.
	 * @param mixed  $style  Style.
	 * @return string
	 */
	public static function sanitize( $object, $style ) {
		$style = is_string( $style ) ? $style : '';

		return isset( self::STYLES[ $object ] ) && in_array( $style, self::STYLES[ $object ], true ) ? $style : 'classic';
	}

	/** Page type whose assigned layout a saved style belongs to. */
	const PAGE_TYPES = [
		'members'  => 'members-directory',
		'groups'   => 'groups-directory',
		'activity' => 'sitewide-activity',
	];

	/**
	 * Saved style of a directory (or the activity page), only while the layout it came from is the one assigned:
	 * switching the page to another layout (an Elementor one, the BuddyPress default) must not keep its cards.
	 *
	 * @param string $object members|groups|activity.
	 * @return string
	 */
	public static function saved( $object ) {
		$saved = get_option( self::OPTION, [] );
		$entry = is_array( $saved ) && isset( self::TARGETS[ $object ], $saved[ self::TARGETS[ $object ] ] ) ? $saved[ self::TARGETS[ $object ] ] : null;

		if ( ! is_array( $entry ) || empty( $entry['layout'] ) || ! isset( $entry['style'] ) ) {
			return 'classic';
		}

		$assigned = Dispatcher::resolve( self::PAGE_TYPES[ $object ] );

		return (int) $assigned['id'] === (int) $entry['layout'] ? self::sanitize( $object, $entry['style'] ) : 'classic';
	}

	/**
	 * Save the style of a directory, with the layout it comes from (Settings\Mirror, from the assigned layout).
	 *
	 * @param string   $object members|groups|activity.
	 * @param mixed    $style  Style.
	 * @param int|null $layout Layout id (null: the layout the mirror is reading).
	 */
	public static function save( $object, $style, $layout = null ) {
		if ( ! isset( self::TARGETS[ $object ] ) ) {
			return;
		}

		$saved = get_option( self::OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];
		$entry = [
			'style'  => self::sanitize( $object, $style ),
			'layout' => (int) ( null === $layout ? \Buddy_Builder\Settings\Mirror::layout() : $layout ),
		];

		if ( isset( $saved[ self::TARGETS[ $object ] ] ) && $saved[ self::TARGETS[ $object ] ] === $entry ) {
			return;
		}

		$saved[ self::TARGETS[ $object ] ] = $entry;
		update_option( self::OPTION, $saved, true );
	}

	/**
	 * Style in effect for a list being rendered: the innermost pushed one, else the saved one.
	 *
	 * @param string $object members|groups|activity.
	 * @return string
	 */
	public static function current( $object ) {
		for ( $i = count( self::$stack ) - 1; $i >= 0; $i-- ) {
			if ( self::$stack[ $i ][0] === $object ) {
				return self::$stack[ $i ][1];
			}
		}

		return self::saved( $object );
	}

	/**
	 * A list starts rendering with this style.
	 *
	 * @param string $object members|groups|activity.
	 * @param mixed  $style  Style.
	 */
	public static function push( $object, $style ) {
		self::$stack[] = [ $object, self::sanitize( $object, $style ) ];
	}

	/**
	 * The list finished.
	 */
	public static function pop() {
		array_pop( self::$stack );
	}

	/**
	 * Whether a card layout replaces the item markup of this object.
	 *
	 * @param string $object members|groups|activity.
	 * @return bool
	 */
	public static function has_card_layout( $object ) {
		return isset( self::ITEM_TYPES[ $object ] ) && function_exists( 'bpb_is_template_populated' ) && bpb_is_template_populated( self::ITEM_TYPES[ $object ] );
	}

	/**
	 * Classes for the list wrapper ('' for classic, or when a card layout is used). Enqueues the styles.
	 *
	 * @param string $object members|groups|activity.
	 * @param mixed  $style  Style.
	 * @return string
	 */
	public static function wrapper_class( $object, $style ) {
		$style = self::sanitize( $object, $style );

		if ( 'classic' === $style || self::has_card_layout( $object ) ) {
			return '';
		}

		self::register();
		wp_enqueue_style( 'bpb-cards' );

		return 'bpb-cards bpb-card-' . $style;
	}

	/**
	 * On a directory or activity page whose saved style is not classic: the styles in the head.
	 */
	public static function enqueue_for_page() {
		if ( ! function_exists( 'bp_is_members_directory' ) ) {
			return;
		}

		$object = '';

		if ( bp_is_members_directory() ) {
			$object = 'members';
		} elseif ( function_exists( 'bp_is_groups_directory' ) && bp_is_groups_directory() ) {
			$object = 'groups';
		} elseif ( function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory() ) {
			$object = 'activity';
		}

		if ( $object && 'classic' !== self::saved( $object ) && ! self::has_card_layout( $object ) ) {
			self::register();
			wp_enqueue_style( 'bpb-cards' );
		}
	}

	/**
	 * Cover image of the current loop item (cover style), printed by the loop templates.
	 *
	 * @param string $object members|groups.
	 */
	public static function print_cover( $object ) {
		if ( 'cover' !== self::current( $object ) || ! function_exists( 'bp_attachments_get_attachment' ) ) {
			return;
		}

		$id  = 'members' === $object ? bp_get_member_user_id() : bp_get_group_id();
		$url = bp_attachments_get_attachment(
			'url',
			[
				'object_dir' => $object,
				'item_id'    => $id,
			]
		);

		echo '<div class="bpb-card__cover"' . ( $url ? ' style="background-image:url(' . esc_url( $url ) . ')"' : '' ) . ' aria-hidden="true"></div>';
	}
}
