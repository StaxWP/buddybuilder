<?php
/**
 * Regression cases for the 2.0 review (2026-11-12, BB-1 to BB-5): the behaviours the 60 render cases cannot see.
 * One case per call, so each one runs with the plugins it needs (bin/regressions.sh starts them all):
 *
 *   wp eval-file tests/compat/bin/regressions-test.php <case>
 *
 * Cases:
 * - customizer-block-layout     BB-1: a Customizer save keeps the block layouts of member-profile / group-profile.
 * - elementor-off               BB-2: Elementor inactive (--skip-plugins=elementor,elementor-pro): Elementor
 *                               assignments are inert (BuddyPress default), block layouts still render.
 * - settings-save-no-elementor  BB-3: Elementor inactive: saving the Community pages selects as rendered (and
 *                               the 1.x settings form handler) keeps the Elementor assignments.
 * - inserter-js-only-block      BB-4: `allowed_block_types_all` stays `true` in the post editor (blocks registered
 *                               only in JavaScript stay in the inserter); page blocks are hidden there by the
 *                               editor filter, and allowed in a block layout.
 * - preview-buddyboss           BB-5: block preview of a member profile renders without BuddyPress 12+ helpers
 *                               (run with --require=tests/compat/env/buddyboss.php on a site with BuddyBoss
 *                               Platform installed; also passes on BuddyPress).
 * - sample-member-hidden        BB-7: the sample member and its friends stay out of member queries, counts and
 *                               activity; remove() deletes them.
 * - preview-seen-by <viewer>    Majors UX B-M3: member preview seen by self|friend|member|guest|'' (buttons and
 *                               owner-only profile tabs follow, the real user is back after the request).
 * - sample-member-preview       Previews show and count the sample member's own (sitewide-hidden) activity.
 * - sample-member-preview-ajax <valid|forged|subscriber>  ... and in the lists a preview page reloads over AJAX.
 * - upsell-delay                The Pro notice waits 7 days after the first live layout (or the 1.x milestone).
 * - community-pages-read-only   B-M1: rendering Community pages writes nothing; rows show the assignments.
 * - checker-known-issues        B-M4: page template finding; last finding kept and forgotten.
 * - setup-new-install           B-M2: the setup redirect is asked for on new installs only.
 * - checker-readylaunch         B-M4: BuddyBoss ReadyLaunch detection (real function on BuddyBoss 2.9+, else mocked).
 * - cards-follow-layout         Card styles apply only with the layout they were saved from.
 * - change-look-undo            Review W6: each look has a Preview of the real page and a confirm step; using a look
 *                               can be undone once (the previous layout is live again, the new one is kept).
 * - layout-actions              B-L4: More menu Duplicate, History, Export, Delete (trash, default shows, Undo
 *                               restores the layout and the assignment), block layouts and Elementor templates.
 * - element-settings            U3: every element shows 1 to 4 basic settings, the rest under More settings; the
 *                               block editor gets the page types of every element (inserter order per page).
 * - elementor-basic-controls    U3 (task 26): every BuddyBuilder Elementor widget keeps the same control keys and
 *                               defaults when its basic settings move to the "Settings" section (first in the
 *                               Content tab, 1 to 4 settings); nothing moves on the front end.
 * - layout-import               Community pages > Import layout: a block layout export comes back as a draft of the
 *                               same type, look and preset; Elementor exports, other JSON and unknown types are refused.
 * - notice-dismiss              Dismissing a notice: only known notice ids, only with their nonce.
 * - card-line                   One "Card" line per directory and activity row (style and Change, or the card layout row),
 *                                 no Pro labels with Pro licensed.
 * - elementor-panel             In a BuddyBuilder template: BuddyBuilder categories first, the page's elements only.
 * - blocks-stay-on              Block layouts stay on when Elementor is activated after a block editor setup.
 * - community-pages-rows        Create a group as its own row, cards under their page, the intro, the collapsed
 *                               "Variations" link (Pro) starting from a copy of the main layout.
 * - split-header                Review item 7: the header parts with design "header" (Split into parts, Pro) print
 *                               the Member header's markup; 1.x parts keep theirs; Pro stats and badges parts.
 * - fresh-site-points           Fresh-site test: undo to 1.9 only after a 1.x update, Turn on in place, part widget
 *                               names, no repeated About label, short Pro teaser.
 * - split-header-elementor      Item 7 in Elementor: the split header's containers render without Elementor's layout
 *                               classes, its parts print the header's markup; Split (Pro) or its locked version
 *                               (free) on the Member header widget; Join on the parts; Pro parts in Header parts.
 * - pro-locks                   Without Pro: Split into parts locked on the Member header, the Community style's
 *                               photo shape, spacing and font locked (saved values kept, not used); Pro unlocks them.
 * - pro-unlicensed              BuddyBuilder Pro without a license (state forced to "not licensed" for this run): Split
 *                               into parts shows locked in both editors with a link to the license, not to the plans;
 *                               an expired license keeps the real action.
 *
 * THROWAWAY site only (needs the compat env mu-plugin). Restores the options it touches and deletes the posts it
 * creates. Prints PASS / FAIL / SKIP lines; exits 1 on any FAIL.
 *
 * @package Buddy_Builder\Tests\Compat
 */

use Buddy_Builder\Templates\Dispatcher;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;

if ( ! defined( 'BPB_COMPAT_ENV' ) ) {
	WP_CLI::error( 'Compat env mu-plugin missing: refusing to run on this site.' );
}

if ( ! class_exists( Registry::class ) ) {
	WP_CLI::error( 'BuddyBuilder 2.x is not active.' );
}

$bpb_case  = isset( $args[0] ) ? $args[0] : '';
$bpb_fails = 0;
$bpb_ok    = static function ( $cond, $msg ) use ( &$bpb_fails, $bpb_case ) {
	echo ( $cond ? 'PASS ' : 'FAIL ' ) . $bpb_case . ': ' . $msg . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$bpb_fails += $cond ? 0 : 1;
};
$bpb_skip  = static function ( $msg ) use ( $bpb_case ) {
	echo 'SKIP ' . $bpb_case . ': ' . $msg . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
};

// State to restore.
$bpb_options = [ 'bpb_settings', 'bpb_templates', 'bpb_blocks_enabled', 'bpb_undo' ];
$bpb_saved   = [];
foreach ( $bpb_options as $bpb_name ) {
	$bpb_saved[ $bpb_name ] = get_option( $bpb_name, null );
}
$bpb_created = [];
global $wpdb;

$bpb_admin = get_users(
	[
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	]
);
wp_set_current_user( $bpb_admin ? (int) $bpb_admin[0] : 0 );

/**
 * A published block layout for a type (content from the first preset of that type, or a single block).
 *
 * @param string $type Template type.
 * @return int
 */
$bpb_block_layout = static function ( $type ) use ( &$bpb_created ) {
	$content = '';
	foreach ( \Buddy_Builder\Presets\Presets::all( $type ) as $preset ) {
		$content = \Buddy_Builder\Presets\Builder::to_blocks( $preset['layout'] );
		break;
	}
	if ( '' === $content ) {
		$content = '<!-- wp:buddybuilder/member-username /-->';
	}

	$id = wp_insert_post(
		[
			'post_type'    => PostType::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => 'Regression ' . $type,
			'post_content' => $content,
			'meta_input'   => [ PostType::META_TYPE => $type ],
		]
	);

	$bpb_created[] = (int) $id;
	Registry::flush();

	return (int) $id;
};

switch ( $bpb_case ) {
	case 'customizer-block-layout':
		if ( ! did_action( 'elementor/loaded' ) ) {
			$bpb_skip( 'needs Elementor active' );
			break;
		}

		$bpb_before = [];
		foreach ( [ 'member-profile', 'group-profile' ] as $bpb_type ) {
			$bpb_id = $bpb_block_layout( $bpb_type );
			Registry::set( $bpb_type, Registry::BLOCKS, $bpb_id );
			$bpb_before[ $bpb_id ] = get_post( $bpb_id )->post_content;
		}

		$bpb_ok( has_action( 'customize_save_after' ) !== false, 'customize_save_after has callbacks' );

		// What WP_Customize_Manager::save() fires after any Publish (even the site title).
		do_action( 'customize_save_after', null );

		foreach ( $bpb_before as $bpb_id => $bpb_content ) {
			clean_post_cache( $bpb_id );
			$bpb_after = get_post( $bpb_id )->post_content;
			// Same blocks (Settings\Mirror may only flip show_home_tab to match the Customizer).
			$bpb_names = static function ( $content ) {
				return wp_json_encode( array_column( parse_blocks( $content ), 'blockName' ) );
			};
			$bpb_ok( '' !== $bpb_content && $bpb_names( $bpb_after ) === $bpb_names( $bpb_content ), 'block layout ' . $bpb_id . ' blocks kept (' . strlen( $bpb_after ) . '/' . strlen( $bpb_content ) . ' chars)' );
			$bpb_ok( '' === get_post_meta( $bpb_id, '_elementor_data', true ), 'block layout ' . $bpb_id . ' got no _elementor_data' );
		}
		break;

	case 'elementor-off':
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$bpb_skip( 'run with --skip-plugins=elementor,elementor-pro' );
			break;
		}

		$bpb_elementor = Registry::elementor_ids();
		$bpb_populated = array_filter(
			$bpb_elementor,
			static function ( $id ) {
				$data = json_decode( (string) get_post_meta( $id, '_elementor_data', true ), true );
				return ! empty( $data );
			}
		);

		if ( ! $bpb_populated ) {
			$bpb_skip( 'no saved Elementor template assigned on this site' );
			break;
		}

		foreach ( $bpb_populated as $bpb_type => $bpb_id ) {
			$bpb_ok( ! Registry::is_populated( $bpb_type ), $bpb_type . ' (Elementor ' . $bpb_id . ') inert: BuddyPress default' );
			$bpb_ok( ! bpb_is_template_populated( $bpb_type ), $bpb_type . ' 1.x bpb_is_template_populated() false' );
			$bpb_ok( '' === Dispatcher::render( $bpb_type ), $bpb_type . ' renders nothing from BuddyBuilder' );
		}

		$bpb_id = $bpb_block_layout( 'member-profile' );
		Registry::set( 'member-profile', Registry::BLOCKS, $bpb_id );
		$bpb_ok( Registry::is_populated( 'member-profile' ), 'block layout still active without Elementor' );
		break;

	case 'settings-save-no-elementor':
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$bpb_skip( 'run with --skip-plugins=elementor,elementor-pro' );
			break;
		}

		$bpb_elementor = Registry::elementor_ids();
		if ( ! $bpb_elementor ) {
			$bpb_skip( 'no Elementor template assigned on this site' );
			break;
		}

		// The Community pages screen exactly as rendered: each row's "Use a layout you already have" select, posted
		// with its selected value, must keep the assignment (the Elementor template is listed though Elementor is off).
		ob_start();
		\Buddy_Builder\Admin\CommunityPages::render();
		$bpb_html = ob_get_clean();

		$bpb_post = [];
		if ( preg_match_all( '#<select id="bpb-layout-([a-z-]+)" name="layout">(.*?)</select>#s', $bpb_html, $bpb_m, PREG_SET_ORDER ) ) {
			foreach ( $bpb_m as $bpb_select ) {
				$bpb_post[ $bpb_select[1] ] = preg_match( '#<option value="([^"]*)" selected#', $bpb_select[2], $bpb_sel ) ? $bpb_sel[1] : '0';
			}
		}

		$bpb_ok( count( $bpb_post ) >= count( $bpb_elementor ), 'community pages lists ' . count( $bpb_post ) . ' layout selects' );

		foreach ( $bpb_post as $bpb_type => $bpb_value ) {
			\Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'assign', [ 'layout' => (int) $bpb_value ] );
		}

		// The 1.x settings form handler (kept for scripts that post it), with the same values.
		if ( ! class_exists( '\Buddy_Builder\Settings' ) ) {
			require_once BPB_BASE_PATH . 'admin/pages/Base.php';
			require_once BPB_BASE_PATH . 'admin/pages/Settings.php';
		}

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array_merge(
			$bpb_post,
			[
				'action'   => 'bpb_settings',
				'_wpnonce' => wp_create_nonce( 'bpb_settings' ),
			]
		);
		$_REQUEST                  = $_POST;

		// The handler redirects; keep the CLI process alive.
		remove_all_filters( 'wp_redirect' );
		add_filter( 'wp_redirect', '__return_false' );
		\Buddy_Builder\Settings::get_instance()->handle_settings_request();
		Registry::flush();

		$bpb_after = Registry::elementor_ids();
		foreach ( $bpb_elementor as $bpb_type => $bpb_id ) {
			$bpb_ok( isset( $bpb_after[ $bpb_type ] ) && $bpb_after[ $bpb_type ] === $bpb_id, $bpb_type . ' keeps Elementor template ' . $bpb_id );
		}
		break;

	case 'inserter-js-only-block':
		$bpb_page = get_posts(
			[
				'post_type'      => 'post',
				'posts_per_page' => 1,
				'post_status'    => 'any',
			]
		);
		$bpb_ctx  = new WP_Block_Editor_Context( [ 'post' => $bpb_page ? $bpb_page[0] : null ] );
		$bpb_ok( true === apply_filters( 'allowed_block_types_all', true, $bpb_ctx ), 'post editor: allowed_block_types_all stays true' );

		$bpb_list   = [ 'core/paragraph', 'buddybuilder/member-cover', 'buddybuilder/members-listing' ];
		$bpb_result = apply_filters( 'allowed_block_types_all', $bpb_list, $bpb_ctx );
		$bpb_ok( is_array( $bpb_result ) && ! in_array( 'buddybuilder/member-cover', $bpb_result, true ) && in_array( 'core/paragraph', $bpb_result, true ), 'post editor: an explicit list keeps its own blocks and loses the page blocks' );

		$bpb_id    = $bpb_block_layout( 'member-profile' );
		$bpb_lctx  = new WP_Block_Editor_Context( [ 'post' => get_post( $bpb_id ) ] );
		$bpb_ok( true === apply_filters( 'allowed_block_types_all', true, $bpb_lctx ), 'block layout editor: allowed_block_types_all stays true' );

		// The page blocks are hidden in other editors by the inline filter on the kit editor script.
		$bpb_hidden = \Buddy_Builder\Elements\Elements::inserter_hidden_blocks();
		$bpb_ok( in_array( 'buddybuilder/member-cover', $bpb_hidden, true ), 'member-cover is hidden from the post inserter (' . count( $bpb_hidden ) . ' blocks)' );
		break;

	case 'preview-buddyboss':
		if ( ! function_exists( 'buddypress' ) ) {
			$bpb_skip( 'needs BuddyPress or BuddyBoss Platform' );
			break;
		}

		$bpb_is_bb = function_exists( 'bp_enable_private_network' );
		$bpb_member = get_users(
			[
				'number'  => 1,
				'fields'  => 'ID',
				'exclude' => [ get_current_user_id() ],
			]
		);
		$bpb_id    = $bpb_block_layout( 'member-profile' );
		update_post_meta( $bpb_id, PostType::META_USER, $bpb_member ? (int) $bpb_member[0] : get_current_user_id() );

		// A block-renderer request from the layout editor, as ServerSideRender sends it.
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}
		$_GET['post_id'] = $bpb_id;

		$bpb_request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/buddybuilder/member-username' );
		$bpb_request->set_query_params(
			[
				'context' => 'edit',
				'post_id' => $bpb_id,
			]
		);

		$bpb_response = rest_do_request( $bpb_request );
		$bpb_data     = $bpb_response->get_data();
		$bpb_ok( 200 === $bpb_response->get_status(), ( $bpb_is_bb ? 'BuddyBoss Platform' : 'BuddyPress' ) . ' preview status ' . $bpb_response->get_status() );
		$bpb_ok( isset( $bpb_data['rendered'] ) && false !== strpos( $bpb_data['rendered'], 'bpb-el' ), 'member preview rendered: ' . ( isset( $bpb_data['rendered'] ) ? substr( wp_strip_all_tags( $bpb_data['rendered'] ), 0, 60 ) : '' ) );
		$bpb_ok( (int) bp_displayed_user_id() === (int) get_post_meta( $bpb_id, PostType::META_USER, true ), 'displayed user set by the preview context' );
		break;

	case 'sample-member-hidden':
		$bpb_sample = \Buddy_Builder\Templates\SampleMember::class;
		if ( $bpb_sample::user_id() ) {
			$bpb_skip( 'a sample member already exists on this site' );
			break;
		}

		$bpb_count = (int) bp_core_get_active_member_count();
		$bpb_user  = $bpb_sample::create();
		$bpb_data  = $bpb_sample::data();
		$bpb_ids   = array_map( 'intval', array_merge( [ $bpb_data['user'] ], $bpb_data['friends'] ) );
		$bpb_ok( $bpb_user && ! is_wp_error( $bpb_user ), 'sample member created (' . count( $bpb_ids ) . ' users)' );

		$bpb_query = new BP_User_Query( [ 'per_page' => 0 ] );
		$bpb_ok( ! array_intersect( $bpb_ids, array_map( 'intval', array_keys( $bpb_query->results ) ) ), 'BP_User_Query (directories, REST, mentions) leaves them out' );
		$bpb_ok( (int) bp_core_get_active_member_count() === $bpb_count, 'member count unchanged (' . $bpb_count . ')' );

		if ( bp_is_active( 'activity' ) ) {
			$bpb_stream = bp_activity_get( [ 'per_page' => 50, 'filter' => [ 'user_id' => $bpb_ids ] ] );
			$bpb_ok( empty( $bpb_stream['activities'] ), 'their activity is not in activity queries' );
		}

		$bpb_sample::remove();
		$bpb_left = 0;
		foreach ( $bpb_ids as $bpb_uid ) {
			$bpb_left += get_userdata( $bpb_uid ) ? 1 : 0;
		}
		$bpb_ok( 0 === $bpb_left && ! $bpb_sample::user_id(), 'remove() deletes every sample user' );
		break;

	case 'preview-seen-by':
		// Majors UX B-M3: the block preview of a member profile, seen by the member, a friend, another member, a
		// guest. Each viewer runs in its own process (the preview context is built once per request).
		$bpb_viewer = isset( $args[1] ) ? $args[1] : '';
		$bpb_member = get_user_by( 'login', 'ana' );
		$bpb_friends = $bpb_member && bp_is_active( 'friends' ) ? friends_get_friend_user_ids( $bpb_member->ID ) : [];
		if ( ! $bpb_member || ! $bpb_friends ) {
			$bpb_skip( 'needs the member "ana" with a friend (compat site data)' );
			break;
		}

		$bpb_id = $bpb_block_layout( 'member-profile' );
		update_post_meta( $bpb_id, PostType::META_USER, $bpb_member->ID );

		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}
		$_GET['post_id']            = $bpb_id;
		$_GET['bpb_preview_viewer'] = $bpb_viewer;
		$bpb_me                     = get_current_user_id();

		$bpb_request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/buddybuilder/member-buttons' );
		$bpb_request->set_query_params(
			[
				'context' => 'edit',
				'post_id' => $bpb_id,
			]
		);
		$bpb_response = rest_do_request( $bpb_request );
		$bpb_html     = isset( $bpb_response->get_data()['rendered'] ) ? $bpb_response->get_data()['rendered'] : '';
		$bpb_ctx      = \Buddy_Builder\Templates\PreviewContext::ctx();

		$bpb_ok( 200 === $bpb_response->get_status(), $bpb_viewer . ': status ' . $bpb_response->get_status() );
		$bpb_ok( $bpb_viewer === $bpb_ctx['viewer'], $bpb_viewer . ': viewer applied (' . $bpb_ctx['viewer'] . ')' );
		$bpb_ok( get_current_user_id() === $bpb_me, $bpb_viewer . ': the real user is back after the request' );

		$bpb_expect = [
			'guest'  => [ 'none' => [ 'friendship-button', 'send-message' ] ],
			'self'   => [ 'none' => [ 'friendship-button' ] ],
			'friend' => [ 'has' => [ 'is_friend' ] ],
			'member' => [ 'has' => [ 'not_friends' ] ],
			''       => [ 'has' => [ 'send-message' ] ],
		];
		foreach ( isset( $bpb_expect[ $bpb_viewer ]['has'] ) ? $bpb_expect[ $bpb_viewer ]['has'] : [] as $bpb_needle ) {
			$bpb_ok( false !== strpos( $bpb_html, $bpb_needle ), $bpb_viewer . ': buttons show ' . $bpb_needle );
		}
		foreach ( isset( $bpb_expect[ $bpb_viewer ]['none'] ) ? $bpb_expect[ $bpb_viewer ]['none'] : [] as $bpb_needle ) {
			$bpb_ok( false === strpos( $bpb_html, $bpb_needle ), $bpb_viewer . ': no ' . $bpb_needle );
		}

		// The profile tabs follow the viewer as on the front end: owner-only tabs for you (an administrator) and the
		// member only.
		$bpb_request = new WP_REST_Request( 'GET', '/wp/v2/block-renderer/buddybuilder/profile-tabs' );
		$bpb_request->set_query_params(
			[
				'context' => 'edit',
				'post_id' => $bpb_id,
			]
		);
		$bpb_response = rest_do_request( $bpb_request );
		$bpb_html     = isset( $bpb_response->get_data()['rendered'] ) ? $bpb_response->get_data()['rendered'] : '';
		$bpb_owner    = in_array( $bpb_viewer, [ '', 'self' ], true );
		$bpb_ok( false !== strpos( $bpb_html, 'xprofile-personal-li' ), $bpb_viewer . ': profile tabs rendered' );
		foreach ( [ 'notifications', 'messages', 'settings' ] as $bpb_tab ) {
			if ( bp_is_active( $bpb_tab ) ) {
				$bpb_ok( $bpb_owner === ( false !== strpos( $bpb_html, $bpb_tab . '-personal-li' ) ), $bpb_viewer . ': ' . $bpb_tab . ' tab ' . ( $bpb_owner ? 'shown' : 'hidden' ) );
			}
		}
		break;

	case 'sample-member-preview':
		// Previews show the sample member's own activity and count it (create() hides it sitewide); other hidden
		// items stay hidden. The real site is covered by sample-member-hidden.
		if ( ! bp_is_active( 'activity' ) ) {
			$bpb_skip( 'needs the Activity component' );
			break;
		}
		$bpb_sample = \Buddy_Builder\Templates\SampleMember::class;
		$bpb_made   = ! $bpb_sample::user_id();
		$bpb_user   = $bpb_sample::create();
		$bpb_id     = $bpb_block_layout( 'member-profile' );

		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}
		$_GET['post_id'] = $bpb_id;
		$bpb_ok( \Buddy_Builder\Templates\PreviewContext::is_preview(), 'preview context' );

		$bpb_updates = [
			'filter'      => [
				'user_id' => $bpb_user,
				'action'  => 'activity_update',
			],
			'count_total' => true,
		];
		$bpb_stream  = bp_activity_get( $bpb_updates );
		$bpb_ok( 5 <= (int) $bpb_stream['total'], 'their updates are counted (' . (int) $bpb_stream['total'] . ')' );
		$bpb_stream = bp_activity_get( array_merge( $bpb_updates, [ 'scope' => 'just-me' ] ) );
		$bpb_ok( 5 <= (int) $bpb_stream['total'], 'their profile stream (just-me scope) shows them (' . (int) $bpb_stream['total'] . ')' );

		$bpb_hidden = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_activity WHERE hide_sitewide = 1 AND user_id NOT IN (" . implode( ',', array_map( 'intval', array_merge( [ $bpb_user ], $bpb_sample::data()['friends'] ) ) ) . ')' ); // phpcs:ignore WordPress.DB
		$bpb_all    = bp_activity_get( [ 'per_page' => 0, 'count_total' => true ] );
		$bpb_ok( ! array_filter( (array) $bpb_all['activities'], static function ( $a ) use ( $bpb_sample, $bpb_user ) {
			return (int) $a->hide_sitewide && ! in_array( (int) $a->user_id, array_map( 'intval', array_merge( [ $bpb_user ], $bpb_sample::data()['friends'] ) ), true );
		} ), 'other hidden items stay hidden (' . $bpb_hidden . ' on the site)' );

		if ( $bpb_made ) {
			$bpb_sample::remove();
		}
		break;

	case 'sample-member-preview-ajax':
		// BuddyPress reloads the lists of a layout preview page over AJAX: the sample member's activity shows there
		// only for a valid preview (administrator, nonce), never for a forged referer or another user.
		$bpb_how = isset( $args[1] ) ? $args[1] : 'valid';
		if ( ! bp_is_active( 'activity' ) ) {
			$bpb_skip( 'needs the Activity component' );
			break;
		}
		$bpb_sample = \Buddy_Builder\Templates\SampleMember::class;
		$bpb_made   = ! $bpb_sample::user_id();
		$bpb_user   = $bpb_sample::create();
		$bpb_url    = \Buddy_Builder\Templates\LayoutPreview::url( home_url( '/members/' ), [ 'member-profile' => 'preset:classic-member-profile' ], true );
		if ( 'forged' === $bpb_how ) {
			$bpb_url = add_query_arg( 'bpb_lp_nonce', 'abc123', $bpb_url );
		} elseif ( 'subscriber' === $bpb_how ) {
			$bpb_sub = get_users( [ 'role' => 'subscriber', 'number' => 1, 'fields' => 'ID' ] );
			wp_set_current_user( $bpb_sub ? (int) $bpb_sub[0] : 0 );
		}
		add_filter( 'wp_doing_ajax', '__return_true' );
		$_SERVER['HTTP_REFERER'] = $bpb_url;
		$bpb_stream = bp_activity_get(
			[
				'filter'      => [ 'user_id' => $bpb_user ],
				'count_total' => true,
			]
		);
		$bpb_shown  = 0 < (int) $bpb_stream['total'];
		$bpb_ok( ( 'valid' === $bpb_how ) === $bpb_shown, $bpb_how . ': sample activity ' . ( $bpb_shown ? 'shown' : 'hidden' ) . ' in the AJAX list (' . (int) $bpb_stream['total'] . ')' );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		wp_set_current_user( $bpb_admin ? (int) $bpb_admin[0] : 0 );
		if ( $bpb_made ) {
			$bpb_sample::remove();
		}
		break;

	case 'upsell-delay':
		// The Pro notice waits DELAY_DAYS after the first live layout (sites from 1.x: after the milestone is first
		// reached), admins on BuddyBuilder screens only, dismissible for good, never with Pro active.
		$bpb_upsell = \Buddy_Builder\Upsell\Upsell::class;
		if ( $bpb_upsell::pages_built() < $bpb_upsell::MILESTONE ) {
			$bpb_skip( 'needs ' . $bpb_upsell::MILESTONE . ' community pages built with BuddyBuilder' );
			break;
		}
		$bpb_since_saved = get_option( $bpb_upsell::SINCE_OPTION, null );
		$bpb_dismissed   = get_user_meta( get_current_user_id(), $bpb_upsell::DISMISSED_META, true );
		delete_user_meta( get_current_user_id(), $bpb_upsell::DISMISSED_META );
		add_filter( 'buddy_builder/upsell/enabled', '__return_true' );
		$_GET['page'] = BPB_ADMIN_PREFIX . 'community-pages';

		delete_option( $bpb_upsell::SINCE_OPTION );
		$bpb_ok( ! $bpb_upsell::notice_due(), 'updated from 1.x (no time stored): not shown when the milestone is first reached' );
		$bpb_ok( abs( (int) get_option( $bpb_upsell::SINCE_OPTION ) - time() ) < 60, 'the delay starts then' );

		delete_option( $bpb_upsell::SINCE_OPTION );
		do_action( 'buddy_builder/registry/changed', 'member-profile', [], [ 'builder' => 'blocks', 'id' => 1 ] );
		$bpb_first = (int) get_option( $bpb_upsell::SINCE_OPTION );
		$bpb_ok( abs( $bpb_first - time() ) < 60, 'the first live layout stores the time' );
		update_option( $bpb_upsell::SINCE_OPTION, $bpb_first - 100, false );
		do_action( 'buddy_builder/registry/changed', 'member-profile', [], [ 'builder' => 'blocks', 'id' => 1 ] );
		$bpb_ok( $bpb_first - 100 === (int) get_option( $bpb_upsell::SINCE_OPTION ), 'later layouts keep the first time' );
		$bpb_ok( ! $bpb_upsell::notice_due(), 'not shown right after the setup' );

		update_option( $bpb_upsell::SINCE_OPTION, time() - ( $bpb_upsell::DELAY_DAYS - 1 ) * DAY_IN_SECONDS, false );
		$bpb_ok( ! $bpb_upsell::notice_due(), 'not shown after ' . ( $bpb_upsell::DELAY_DAYS - 1 ) . ' days' );
		update_option( $bpb_upsell::SINCE_OPTION, time() - $bpb_upsell::DELAY_DAYS * DAY_IN_SECONDS - 60, false );
		$bpb_ok( $bpb_upsell::notice_due(), 'shown after ' . $bpb_upsell::DELAY_DAYS . ' days' );

		$_GET['page'] = 'plugins';
		$bpb_ok( ! $bpb_upsell::notice_due(), 'BuddyBuilder screens only' );
		$_GET['page'] = BPB_ADMIN_PREFIX . 'community-pages';
		update_user_meta( get_current_user_id(), $bpb_upsell::DISMISSED_META, 1 );
		$bpb_ok( ! $bpb_upsell::notice_due(), 'dismissed for good' );
		delete_user_meta( get_current_user_id(), $bpb_upsell::DISMISSED_META );
		remove_filter( 'buddy_builder/upsell/enabled', '__return_true' );
		add_filter( 'buddy_builder/upsell/enabled', '__return_false' );
		$bpb_ok( ! $bpb_upsell::notice_due(), 'never with Pro active' );
		remove_filter( 'buddy_builder/upsell/enabled', '__return_false' );
		$bpb_sub = get_users( [ 'role' => 'subscriber', 'number' => 1, 'fields' => 'ID' ] );
		if ( $bpb_sub ) {
			$bpb_admin_id = get_current_user_id();
			wp_set_current_user( (int) $bpb_sub[0] );
			add_filter( 'buddy_builder/upsell/enabled', '__return_true' );
			$bpb_ok( ! $bpb_upsell::notice_due(), 'administrators only' );
			wp_set_current_user( $bpb_admin_id );
		}

		if ( null === $bpb_since_saved ) {
			delete_option( $bpb_upsell::SINCE_OPTION );
		} else {
			update_option( $bpb_upsell::SINCE_OPTION, $bpb_since_saved, false );
		}
		if ( $bpb_dismissed ) {
			update_user_meta( get_current_user_id(), $bpb_upsell::DISMISSED_META, $bpb_dismissed );
		}
		break;

	case 'community-pages-read-only':
		// Majors UX B-M1: opening Community pages (and the Help and status data) never writes the assignments.
		$bpb_before = [ get_option( 'bpb_settings', null ), get_option( 'bpb_templates', null ) ];
		$bpb_rows   = [];
		foreach ( \Buddy_Builder\Admin\CommunityPages::structure() as $bpb_type => $bpb_info ) {
			$bpb_rows[ $bpb_type ] = \Buddy_Builder\Admin\CommunityPages::row( $bpb_type, $bpb_info['pro'] );
		}
		ob_start();
		\Buddy_Builder\Admin\CommunityPages::render();
		$bpb_html = ob_get_clean();
		$bpb_after = [ get_option( 'bpb_settings', null ), get_option( 'bpb_templates', null ) ];
		$bpb_ok( $bpb_before === $bpb_after, 'assignment options unchanged after rendering' );
		foreach ( Registry::all() as $bpb_type => $bpb_entry ) {
			if ( isset( $bpb_rows[ $bpb_type ] ) ) {
				$bpb_ok( $bpb_rows[ $bpb_type ]['entry'] === $bpb_entry, $bpb_type . ': row shows the assigned ' . $bpb_entry['builder'] . ' #' . $bpb_entry['id'] );
			}
		}
		$bpb_ok( false !== strpos( $bpb_html, 'bpb-page-member-profile' ), 'member profile row rendered' );
		$bpb_ok( false === strpos( $bpb_html, "\u{2014}" ), 'no em dash in the screen' );
		break;

	case 'checker-known-issues':
		// Majors UX B-M4: a non-default page template on the members page is reported; findings are kept and forgotten.
		$bpb_pages = bp_core_get_directory_page_ids( 'all' );
		$bpb_page  = isset( $bpb_pages['members'] ) ? (int) $bpb_pages['members'] : 0;
		if ( ! $bpb_page ) {
			$bpb_skip( 'no members page' );
			break;
		}
		$bpb_tpl = get_post_meta( $bpb_page, '_wp_page_template', true );
		update_post_meta( $bpb_page, '_wp_page_template', 'page-full-width.php' );
		$bpb_issues = \Buddy_Builder\Admin\Checker::known_issues( 'member-profile' );
		$bpb_ok( (bool) preg_grep( '/page-full-width\.php/', $bpb_issues ), 'page template reported' );
		update_post_meta( $bpb_page, '_wp_page_template', $bpb_tpl ? $bpb_tpl : 'default' );
		$bpb_ok( ! preg_grep( '/page template/', \Buddy_Builder\Admin\Checker::known_issues( 'member-profile' ) ), 'default template: nothing reported' );

		\Buddy_Builder\Admin\Checker::record( 'member-profile', [ 'test cause' ] );
		$bpb_ok( 'not-applied' === \Buddy_Builder\Admin\CommunityPages::row( 'member-profile' )['status']['code'] || ! Registry::get( 'member-profile' )['id'], 'Community pages shows the last finding' );
		\Buddy_Builder\Admin\Checker::forget( 'member-profile' );
		$bpb_ok( null === \Buddy_Builder\Admin\Checker::last_issue( 'member-profile' ), 'finding forgotten' );
		break;

	case 'setup-new-install':
		// Majors UX B-M2: the setup opens only on new installs.
		$bpb_ok( ! \Buddy_Builder\Admin\Setup::is_new_install(), 'this site (with BuddyBuilder data) is not a new install' );
		$bpb_keep = [];
		foreach ( [ 'bpb_settings', 'bpb_templates', 'buddybuilder_db_version', 'bpb_db_version', 'bpb_imported_templates', 'bpb_setup' ] as $bpb_name ) {
			$bpb_keep[ $bpb_name ] = get_option( $bpb_name, null );
			delete_option( $bpb_name );
		}
		$bpb_ok( \Buddy_Builder\Admin\Setup::is_new_install(), 'without any BuddyBuilder option it is a new install' );
		\Buddy_Builder\Admin\Setup::on_activation();
		$bpb_ok( '1' === (string) get_transient( \Buddy_Builder\Admin\Setup::REDIRECT ), 'activation asks for the setup once (pending for a day)' );

		// Where the pending setup may open: Plugins, Dashboard, BuddyBuilder screens; never another plugin's wizard.
		global $pagenow;
		$bpb_where = [];
		foreach ( [ [ 'plugins.php', '' ], [ 'index.php', '' ], [ 'admin.php', 'buddy-builder-settings' ], [ 'admin.php', 'other-plugin-setup' ], [ 'admin.php', 'buddy-builder-setup' ], [ 'edit.php', '' ] ] as $bpb_req ) {
			$pagenow = $bpb_req[0]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$_GET    = $bpb_req[1] ? [ 'page' => $bpb_req[1] ] : [];
			$bpb_where[] = ( $bpb_req[1] ? $bpb_req[1] : $bpb_req[0] ) . '=' . ( \Buddy_Builder\Admin\Setup::can_redirect_here() ? 'yes' : 'no' );
		}
		$_GET = [];
		$bpb_ok( 'plugins.php=yes index.php=yes buddy-builder-settings=yes other-plugin-setup=no buddy-builder-setup=no edit.php=no' === implode( ' ', $bpb_where ), 'opens only on calm screens: ' . implode( ' ', $bpb_where ) );
		// Another plugin's activation redirect can take each try (BuddyPress): a few tries, then the request is given up.
		$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		set_transient( \Buddy_Builder\Admin\Setup::REDIRECT, \Buddy_Builder\Admin\Setup::REDIRECT_TRIES + 1, DAY_IN_SECONDS );
		\Buddy_Builder\Admin\Setup::maybe_redirect();
		$bpb_ok( false === get_transient( \Buddy_Builder\Admin\Setup::REDIRECT ), 'after ' . \Buddy_Builder\Admin\Setup::REDIRECT_TRIES . ' redirects the pending setup is given up' );
		delete_transient( \Buddy_Builder\Admin\Setup::REDIRECT );
		foreach ( $bpb_keep as $bpb_name => $bpb_value ) {
			if ( null !== $bpb_value ) {
				update_option( $bpb_name, $bpb_value );
			}
		}
		\Buddy_Builder\Admin\Setup::on_activation();
		$bpb_ok( false === get_transient( \Buddy_Builder\Admin\Setup::REDIRECT ), 'activation on an existing site does not' );
		break;

	case 'checker-readylaunch':
		// BuddyBoss ReadyLaunch (BuddyBoss Platform 2.9+): detected through bb_is_readylaunch_enabled() (the real
		// function when that BuddyBoss runs, see env/buddyboss.php and BPB_BUDDYBOSS_PLUGIN), else through the filter.
		$bpb_real = function_exists( 'bb_is_readylaunch_enabled' );
		if ( $bpb_real ) {
			$bpb_rl = get_option( 'bb_rl_enabled', null );
			update_option( 'bb_rl_enabled', 1 );
		} else {
			add_filter( 'buddy_builder/readylaunch_active', '__return_true' );
		}
		$bpb_ok( \Buddy_Builder\Admin\Checker::readylaunch_active(), ( $bpb_real ? 'real bb_is_readylaunch_enabled()' : 'mocked through the filter' ) . ': ReadyLaunch detected' );
		$bpb_ok( (bool) preg_grep( '/ReadyLaunch/', \Buddy_Builder\Admin\Checker::known_issues( 'member-profile' ) ), 'reported as a cause on the profile' );
		$bpb_ok( (bool) preg_grep( '/ReadyLaunch/', wp_list_pluck( \Buddy_Builder\Admin\Setup::checks(), 'text' ) ), 'shown in the setup checks' );
		if ( $bpb_real ) {
			update_option( 'bb_rl_enabled', 0 );
			$bpb_ok( ! \Buddy_Builder\Admin\Checker::readylaunch_active(), 'ReadyLaunch off: not reported' );
			null === $bpb_rl ? delete_option( 'bb_rl_enabled' ) : update_option( 'bb_rl_enabled', $bpb_rl );
		} else {
			remove_filter( 'buddy_builder/readylaunch_active', '__return_true' );
			$bpb_ok( ! \Buddy_Builder\Admin\Checker::readylaunch_active(), 'filter removed: not reported' );
		}
		break;

	case 'change-look-undo':
		// Review W6: Preview + confirm on every look, Undo after Use.
		update_option( 'bpb_blocks_enabled', '1' );
		$bpb_type   = 'member-profile';
		$bpb_before = Registry::get( $bpb_type );
		ob_start();
		\Buddy_Builder\Admin\CommunityPages::render();
		$bpb_html = ob_get_clean();
		$bpb_looks = count( \Buddy_Builder\Presets\Presets::all( $bpb_type ) );
		$bpb_ok( substr_count( $bpb_html, 'bpb_lp' ) >= $bpb_looks, 'every look links to a live preview (' . $bpb_looks . ' looks)' );
		$bpb_ok( false !== strpos( $bpb_html, 'class="bpb-look__confirm"' ), 'Use this style asks for a confirmation first' );
		$bpb_preset = array_keys( \Buddy_Builder\Presets\Presets::all( $bpb_type ) )[0];
		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'look', [ 'preset' => $bpb_preset, 'builder' => Registry::BLOCKS ] ), 'look applied' );
		$bpb_new = Registry::get( $bpb_type );
		$bpb_created[] = (int) $bpb_new['id'];
		$bpb_undo = \Buddy_Builder\Admin\CommunityPages::undo_entry();
		$bpb_ok( $bpb_undo && $bpb_undo['id'] === (int) $bpb_before['id'] && $bpb_undo['builder'] === $bpb_before['builder'], 'Undo remembers what was live before' );
		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'undo' ), 'Undo runs' );
		$bpb_ok( Registry::get( $bpb_type ) == $bpb_before, 'the previous layout is live again' ); // phpcs:ignore Universal.Operators.StrictComparisons
		$bpb_ok( 'publish' === get_post_status( $bpb_new['id'] ), 'the new layout is kept' );
		$bpb_ok( null === \Buddy_Builder\Admin\CommunityPages::undo_entry(), 'Undo is offered once' );
		$bpb_ok( ! \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'undo' ), 'a second Undo does nothing' );
		break;

	case 'cards-follow-layout':
		// A card style belongs to the layout it was saved from: another layout on the page (Elementor, the
		// BuddyPress default) renders the classic markup again (found by the gate on 2026-11-15).
		// The case saves the Pro card style Cover: without BuddyBuilder Pro there is no style to follow the layout.
		if ( ! in_array( 'cover', \Buddy_Builder\Templates\Cards::styles( 'groups' ), true ) ) {
			$bpb_skip( 'needs the Pro card style Cover (BuddyBuilder Pro is not active)' );
			break;
		}
		update_option( 'bpb_blocks_enabled', '1' );
		$bpb_saved_cards = get_option( \Buddy_Builder\Templates\Cards::OPTION, null );
		$bpb_layout      = wp_insert_post( [ 'post_type' => PostType::POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Cards', 'post_content' => '<!-- wp:buddybuilder/groups-listing {"card":"cover"} /-->', 'meta_input' => [ PostType::META_TYPE => 'groups-directory' ] ] );
		$bpb_created[]   = (int) $bpb_layout;
		Registry::set( 'groups-directory', Registry::BLOCKS, $bpb_layout );
		Registry::flush();
		$bpb_ok( 'cover' === \Buddy_Builder\Templates\Cards::saved( 'groups' ), 'assigning a block layout mirrors its card style' );
		Registry::set( 'groups-directory', Registry::BLOCKS, 0 );
		Registry::flush();
		$bpb_ok( 'classic' === \Buddy_Builder\Templates\Cards::saved( 'groups' ), 'another layout on the page gets the classic markup' );
		null === $bpb_saved_cards ? delete_option( \Buddy_Builder\Templates\Cards::OPTION ) : update_option( \Buddy_Builder\Templates\Cards::OPTION, $bpb_saved_cards );
		break;

	case 'layout-actions':
		// B-L4: the layout part of the More menu, for both builders.
		update_option( 'bpb_blocks_enabled', '1' );
		$bpb_type   = 'member-profile';
		$bpb_layout = $bpb_block_layout( $bpb_type );
		update_post_meta( $bpb_layout, '_bpb_look', 'bpb-look bpb-look--classic' );
		Registry::set( $bpb_type, Registry::BLOCKS, $bpb_layout );
		Registry::flush();

		ob_start();
		\Buddy_Builder\Admin\CommunityPages::render();
		$bpb_html = ob_get_clean();
		foreach ( [ 'duplicate', 'export', 'delete' ] as $bpb_do ) {
			$bpb_ok( false !== strpos( $bpb_html, 'name="do" value="' . $bpb_do . '"' ), 'More menu has ' . $bpb_do . ' (admin-post form)' );
		}
		$bpb_ok( false !== strpos( $bpb_html, 'class="bpb-more__confirm"' ), 'Delete asks for a confirmation first' );

		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'duplicate', [ 'layout' => $bpb_layout ] ), 'duplicate runs' );
		$bpb_copy = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title = %s ORDER BY ID DESC LIMIT 1", PostType::POST_TYPE, get_the_title( $bpb_layout ) . ' (copy)' ) ); // phpcs:ignore WordPress.DB
		$bpb_created[] = $bpb_copy;
		$bpb_ok( $bpb_copy && 'draft' === get_post_status( $bpb_copy ), 'the copy is a draft' );
		$bpb_ok( $bpb_copy && get_post( $bpb_copy )->post_content === get_post( $bpb_layout )->post_content, 'the copy has the same blocks' );
		$bpb_ok( $bpb_copy && $bpb_type === get_post_meta( $bpb_copy, PostType::META_TYPE, true ) && 'bpb-look bpb-look--classic' === get_post_meta( $bpb_copy, '_bpb_look', true ), 'the copy keeps the page type and the look' );
		$bpb_ok( Registry::get( $bpb_type )['id'] === $bpb_layout, 'the copy is not assigned' );

		$bpb_ok( '' === \Buddy_Builder\Admin\LayoutActions::history_url( $bpb_layout ), 'History: no revision yet, no link' );
		wp_update_post( [ 'ID' => $bpb_layout, 'post_content' => get_post( $bpb_layout )->post_content . "\n<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->" ] );
		$bpb_ok( false !== strpos( \Buddy_Builder\Admin\LayoutActions::history_url( $bpb_layout ), 'revision.php?revision=' ), 'History links to the revisions screen after an edit' );

		$bpb_file = \Buddy_Builder\Admin\LayoutActions::export( $bpb_layout );
		$bpb_ok( ! is_wp_error( $bpb_file ) && 'buddybuilder-layout' === $bpb_file['data']['format'] && $bpb_type === $bpb_file['data']['type'] && get_post( $bpb_layout )->post_content === $bpb_file['data']['content'], 'Export: format, page type and content' );
		$bpb_ok( ! is_wp_error( $bpb_file ) && ! isset( $bpb_file['data']['meta'][ PostType::META_USER ] ) && isset( $bpb_file['data']['meta']['_bpb_look'] ), 'Export: look meta, no preview member' );

		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'delete', [ 'layout' => $bpb_layout ] ), 'delete runs' );
		$bpb_ok( 'trash' === get_post_status( $bpb_layout ), 'the layout is in the trash' );
		$bpb_ok( 0 === Registry::get( $bpb_type )['id'], 'the page shows the BuddyPress default' );
		$bpb_ok( ! \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'delete', [ 'layout' => $bpb_layout ] ), 'a trashed layout is not deleted twice' );
		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'undo' ), 'Undo runs' );
		$bpb_ok( 'publish' === get_post_status( $bpb_layout ), 'Undo restores the layout as published' );
		$bpb_ok( Registry::get( $bpb_type )['id'] === $bpb_layout, 'Undo gives the layout back to the page' );
		$bpb_ok( ! \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'delete', [ 'layout' => (int) get_option( 'page_on_front' ) ?: 1 ] ), 'a post that is not a layout is refused' );

		wp_set_current_user( 0 );
		$bpb_ok( ! \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'duplicate', [ 'layout' => $bpb_layout ] ) && ! \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'delete', [ 'layout' => $bpb_layout ] ), 'without the capability nothing runs' );
		wp_set_current_user( $bpb_admin ? (int) $bpb_admin[0] : 0 );

		if ( ! did_action( 'elementor/loaded' ) ) {
			$bpb_skip( 'Elementor part: needs Elementor active' );
			break;
		}

		$bpb_el = wp_insert_post( [ 'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'Regression Elementor', 'meta_input' => [ '_elementor_template_type' => 'bpb-buddypress', '_bpb_page' => $bpb_type, '_elementor_edit_mode' => 'builder', '_bpb_look' => 'bpb-look bpb-look--classic', '_elementor_data' => wp_slash( wp_json_encode( [ [ 'id' => 'a1b2c3d', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => [ 'title' => 'Quote " and \\ slash' ], 'elements' => [] ] ] ) ) ] ] );
		$bpb_created[] = (int) $bpb_el;
		wp_set_object_terms( $bpb_el, 'bpb-buddypress', 'elementor_library_type' );
		Registry::set( $bpb_type, Registry::ELEMENTOR, $bpb_el );
		Registry::flush();
		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'duplicate', [ 'layout' => $bpb_el ] ), 'Elementor: duplicate runs' );
		$bpb_ecopy = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'elementor_library' AND post_title = %s ORDER BY ID DESC LIMIT 1", 'Regression Elementor (copy)' ) ); // phpcs:ignore WordPress.DB
		$bpb_created[] = $bpb_ecopy;
		$bpb_ok( $bpb_ecopy && get_post_meta( $bpb_ecopy, '_elementor_data', true ) === get_post_meta( $bpb_el, '_elementor_data', true ), 'Elementor: the copy has the same data (slashes kept)' );
		$bpb_ok( $bpb_ecopy && 'bpb-look bpb-look--classic' === get_post_meta( $bpb_ecopy, '_bpb_look', true ) && $bpb_type === get_post_meta( $bpb_ecopy, '_bpb_page', true ), 'Elementor: the copy keeps the look and the page' );
		$bpb_ok( $bpb_ecopy && has_term( 'bpb-buddypress', 'elementor_library_type', $bpb_ecopy ), 'Elementor: the copy keeps the template type term' );
		$bpb_ok( false !== strpos( \Buddy_Builder\Admin\LayoutActions::history_url( $bpb_el ), 'action=elementor&bpb_history=1' ), 'Elementor: History opens Elementor revisions' );
		$bpb_file = \Buddy_Builder\Admin\LayoutActions::export( $bpb_el );
		$bpb_ok( ! is_wp_error( $bpb_file ) && isset( $bpb_file['data']['content'], $bpb_file['data']['page_settings'], $bpb_file['data']['version'] ) && 'bpb-buddypress' === $bpb_file['data']['type'], 'Elementor: export in Elementor\'s format' );
		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'delete', [ 'layout' => $bpb_el ] ) && 0 === Registry::get( $bpb_type )['id'], 'Elementor: delete, the page shows the default' );
		$bpb_ok( \Buddy_Builder\Admin\CommunityPages::apply( $bpb_type, 'undo' ) && 'publish' === get_post_status( $bpb_el ) && Registry::get( $bpb_type )['id'] === $bpb_el, 'Elementor: Undo restores it' );
		break;

	case 'element-settings':
		// U3: 1 to 4 basic settings per element (deprecated parts are not offered, they keep what they had).
		foreach ( \Buddy_Builder\Elements\Elements::kit()->elements() as $bpb_element ) {
			$bpb_schema = $bpb_element->schema();
			if ( ! empty( $bpb_schema['deprecated'] ) ) {
				continue;
			}
			$bpb_basic = 0;
			foreach ( $bpb_element->editor_definition()['controls'] as $bpb_control ) {
				$bpb_basic += 'basic' === $bpb_control['panel'] ? 1 : 0;
			}
			if ( 0 !== strpos( wp_normalize_path( $bpb_element->dir() ), wp_normalize_path( BPB_BASE_PATH ) ) ) {
				// Elements of other plugins (BuddyBuilder Pro): reported, checked in their own repo.
				$bpb_basic > 4 && $bpb_skip( $bpb_element->name() . ' (not in this plugin): ' . $bpb_basic . ' basic settings' );
				continue;
			}
			$bpb_ok( $bpb_basic <= 4, $bpb_element->name() . ': ' . $bpb_basic . ' basic settings' );
		}
		$bpb_types = \Buddy_Builder\Elements\Elements::page_types();
		$bpb_ok( isset( $bpb_types['buddybuilder/member-header'] ) && [ 'member-profile' ] === $bpb_types['buddybuilder/member-header'], 'page types of the elements for the inserter' );
		$bpb_ok( isset( $bpb_types['buddybuilder/people-rail'] ) && [] === $bpb_types['buddybuilder/people-rail'], 'elements for any page have no page type' );
		break;

	case 'elementor-basic-controls':
		if ( ! did_action( 'elementor/loaded' ) ) {
			$bpb_skip( 'Elementor is not active' );
			break;
		}
		$bpb_manager = \Elementor\Plugin::$instance->widgets_manager;
		$bpb_shape   = static function ( $widget ) {
			$out = [];
			foreach ( $widget->get_controls() as $id => $control ) {
				if ( 'section' !== $control['type'] ) {
					$out[ $id ] = isset( $control['default'] ) ? wp_json_encode( $control['default'] ) : '';
				}
			}
			ksort( $out );
			return $out;
		};
		$bpb_front = [];
		foreach ( $bpb_manager->get_widget_types() as $bpb_name => $bpb_widget ) {
			if ( 0 === strpos( $bpb_name, 'bpb-' ) ) {
				$bpb_front[ $bpb_name ] = [ $bpb_shape( $bpb_widget ), isset( $bpb_widget->get_controls()[ \Buddy_Builder\Integrations\Elementor\BasicControls::SECTION ] ) ];
				\Elementor\Plugin::$instance->controls_manager->delete_stack( $bpb_widget );
			}
		}
		set_current_screen( 'dashboard' ); // is_admin(): the editor and its AJAX requests.
		$bpb_moved = 0;
		foreach ( $bpb_front as $bpb_name => $bpb_before ) {
			$bpb_widget = $bpb_manager->get_widget_types( $bpb_name );
			$bpb_ok( ! $bpb_before[1], $bpb_name . ': no regrouping on the front end' );
			$bpb_ok( $bpb_before[0] === $bpb_shape( $bpb_widget ), $bpb_name . ': same control keys and defaults' );
			$bpb_controls = $bpb_widget->get_controls();
			if ( ! isset( $bpb_controls[ \Buddy_Builder\Integrations\Elementor\BasicControls::SECTION ] ) ) {
				continue;
			}
			++$bpb_moved;
			$bpb_first = '';
			$bpb_in    = 0;
			foreach ( $bpb_controls as $bpb_id => $bpb_control ) {
				if ( '' === $bpb_first && 'section' === $bpb_control['type'] && 'content' === $bpb_control['tab'] ) {
					$bpb_first = $bpb_id;
				}
				if ( isset( $bpb_control['section'] ) && \Buddy_Builder\Integrations\Elementor\BasicControls::SECTION === $bpb_control['section'] && empty( $bpb_control['responsive'] ) ) {
					++$bpb_in;
				}
			}
			$bpb_ok( \Buddy_Builder\Integrations\Elementor\BasicControls::SECTION === $bpb_first && $bpb_in >= 1 && $bpb_in <= 4, $bpb_name . ': Settings first in the Content tab with ' . $bpb_in . ' settings' );
		}
		$bpb_ok( $bpb_moved >= ( defined( 'BPB_PRO_VERSION' ) ? 30 : 15 ), $bpb_moved . ' widgets show basic settings first' );
		set_current_screen( 'front' );
		break;

	case 'layout-import':
		update_option( 'bpb_blocks_enabled', '1' );
		$bpb_layout = $bpb_block_layout( 'member-profile' );
		update_post_meta( $bpb_layout, '_bpb_look', 'bpb-look bpb-look--classic' );
		update_post_meta( $bpb_layout, '_bpb_preset', 'classic-member-profile' );
		$bpb_file = \Buddy_Builder\Admin\LayoutActions::export( $bpb_layout );
		$bpb_json = wp_json_encode( $bpb_file['data'] );
		$bpb_new  = \Buddy_Builder\Admin\LayoutActions::import( $bpb_json );
		$bpb_ok( is_int( $bpb_new ) && $bpb_new > 0, 'the export imports' );
		if ( is_int( $bpb_new ) ) {
			$bpb_created[] = $bpb_new;
			$bpb_ok( 'draft' === get_post_status( $bpb_new ) && PostType::POST_TYPE === get_post_type( $bpb_new ), 'as a draft block layout' );
			$bpb_ok( 'member-profile' === PostType::type_of( $bpb_new ) && 'bpb-look bpb-look--classic' === get_post_meta( $bpb_new, '_bpb_look', true ) && 'classic-member-profile' === get_post_meta( $bpb_new, '_bpb_preset', true ), 'with its type, look and preset' );
			$bpb_ok( get_post_field( 'post_content', $bpb_new ) === get_post_field( 'post_content', $bpb_layout ), 'same content' );
			$bpb_ok( (int) Registry::get( 'member-profile' )['id'] !== $bpb_new, 'not assigned' );
		}
		$bpb_bad = [
			'bpb_import_elementor' => wp_json_encode( [ 'content' => [], 'version' => '0.4', 'title' => 'E', 'type' => 'page' ] ),
			'bpb_import_json'      => 'not json',
			'bpb_import_format'    => wp_json_encode( [ 'format' => 'other', 'version' => 1, 'content' => '' ] ),
			'bpb_import_type'      => wp_json_encode( array_merge( $bpb_file['data'], [ 'type' => 'no-such-type' ] ) ),
			'bpb_import_content'   => wp_json_encode( array_merge( $bpb_file['data'], [ 'content' => '<p>No blocks</p>' ] ) ),
		];
		foreach ( $bpb_bad as $bpb_code => $bpb_raw ) {
			$bpb_res = \Buddy_Builder\Admin\LayoutActions::import( $bpb_raw );
			$bpb_ok( is_wp_error( $bpb_res ) && $bpb_code === $bpb_res->get_error_code(), 'refused: ' . $bpb_code );
		}
		$bpb_user = wp_get_current_user();
		wp_set_current_user( 0 );
		$bpb_res = \Buddy_Builder\Admin\LayoutActions::import( $bpb_json );
		$bpb_ok( is_wp_error( $bpb_res ) && 'bpb_import_cap' === $bpb_res->get_error_code(), 'refused without the capability' );
		wp_set_current_user( $bpb_user->ID );
		break;

	case 'notice-dismiss':
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function ( $message, $title, $args ) {
					throw new RuntimeException( (string) ( is_array( $args ) && isset( $args['response'] ) ? $args['response'] : 200 ) );
				};
			}
		);
		$bpb_try = static function ( array $request ) {
			$_REQUEST = $request;
			try {
				\Buddy_Builder\Notices::get_instance()->bpb_admin_notice_viewed();
			} catch ( RuntimeException $e ) {
				return $e->getMessage();
			}
			return 'none';
		};
		$bpb_uid  = get_current_user_id();
		$bpb_prev = get_user_meta( $bpb_uid, 'bpb_rate_us', true );
		delete_user_meta( $bpb_uid, 'bpb_rate_us' );
		$bpb_ok( '400' === $bpb_try( [ 'notice_id' => 'wp_capabilities' ] ) && '' === (string) get_user_meta( $bpb_uid, 'wp_capabilities_x', true ), 'an unknown key is refused' );
		$bpb_ok( '403' === $bpb_try( [ 'notice_id' => 'bpb_rate_us' ] ) && '' === get_user_meta( $bpb_uid, 'bpb_rate_us', true ), 'no nonce: refused, nothing saved' );
		$bpb_ok( '403' === $bpb_try( [ 'notice_id' => 'bpb_rate_us', '_wpnonce' => wp_create_nonce( 'other' ) ] ), 'another nonce: refused' );
		$bpb_try( [ 'notice_id' => 'bpb_rate_us', '_wpnonce' => wp_create_nonce( \Buddy_Builder\Notices::DISMISS_NONCE . 'bpb_rate_us' ) ] );
		$bpb_ok( '1' === (string) get_user_meta( $bpb_uid, 'bpb_rate_us', true ), 'with its nonce: dismissed' );
		$bpb_ok( false !== strpos( \Buddy_Builder\Notices::dismiss_url( 'bpb_rate_us' ), '_wpnonce=' ), 'the dismiss link carries the nonce' );
		'' === $bpb_prev ? delete_user_meta( $bpb_uid, 'bpb_rate_us' ) : update_user_meta( $bpb_uid, 'bpb_rate_us', $bpb_prev );
		$_REQUEST = [];
		break;

	case 'card-line':
		// One "Card" line per directory and activity row (UX review 2026-11-17, item 5): the card style with Change (the
		// editor with the list selected), or the card layout's own item row; no card row without a card layout; no Pro
		// labels while BuddyBuilder Pro is active and licensed.
		ob_start();
		\Buddy_Builder\Admin\CommunityPages::render();
		$bpb_html = ob_get_clean();
		foreach ( \Buddy_Builder\Templates\Cards::PAGE_TYPES as $bpb_object => $bpb_type ) {
			if ( ! \Buddy_Builder\Templates\Types::is_active( $bpb_type ) ) {
				continue;
			}
			$bpb_item   = \Buddy_Builder\Templates\Cards::ITEM_TYPES[ $bpb_object ];
			$bpb_layout = (int) Registry::get( $bpb_item )['id'] && get_post( (int) Registry::get( $bpb_item )['id'] );
			$bpb_row    = preg_match( '/id="bpb-page-' . preg_quote( $bpb_type, '/' ) . '".*?<\/section>/s', $bpb_html, $bpb_m ) ? $bpb_m[0] : '';
			$bpb_lines  = substr_count( $bpb_row, 'class="bpb-cp__card"' ) + substr_count( $bpb_row, '<li class="bpb-cp__item" id="bpb-page-' . $bpb_item . '"' );
			$bpb_ok( 1 === $bpb_lines, $bpb_type . ' row has one card line (' . $bpb_lines . ', card layout: ' . ( $bpb_layout ? 'yes' : 'no' ) . ')' );
			if ( ! $bpb_layout ) {
				$bpb_ok( (bool) preg_match( '/<div class="bpb-cp__card">Card: [^<]+/', $bpb_row ) && false === strpos( $bpb_row, '<li class="bpb-cp__item" id="bpb-page-' . $bpb_item . '"' ), $bpb_type . ': the card style line, no card row without a card layout' );
				$bpb_styles = \Buddy_Builder\Templates\Cards::labels();
				if ( Registry::get( $bpb_type )['id'] && ! empty( $bpb_styles[ $bpb_object ] ) ) {
					$bpb_ok( false !== strpos( $bpb_row, 'bpb_select=' . $bpb_object ), $bpb_type . ': Change opens the editor with the list selected' );
				} elseif ( empty( $bpb_styles[ $bpb_object ] ) ) {
					$bpb_ok( false === strpos( $bpb_row, 'bpb_select=' ) && ( ! \Buddy_Builder\Upsell\Upsell::enabled() || false !== strpos( $bpb_row, 'utm_content=card-designer' ) ), $bpb_type . ': only Classic: no Change (nothing to pick), More card styles links to Pro' );
				}
			}
		}
		$bpb_ok( 3 >= substr_count( $bpb_html, 'class="bpb-cp__card"' ), 'only the directory and activity rows' );
		$bpb_pro = \Buddy_Builder\Admin\App::pro();
		if ( $bpb_pro['active'] && false !== $bpb_pro['licensed'] ) {
			$bpb_ok( false === strpos( $bpb_html, '<span class="bpb-label">' ), 'no Pro labels with BuddyBuilder Pro active and licensed' );
		}
		$bpb_ok( [ 'members-listing', 'bpb-members-directory-list' ] === \Buddy_Builder\Templates\Cards::list_names( 'members' ), 'the members lists to select in the editor' );
		$_GET[ \Buddy_Builder\Templates\Cards::SELECT_ARG ] = 'groups';
		$bpb_ok( [ 'groups-listing', 'bpb-groups-directory-list' ] === \Buddy_Builder\Templates\Cards::editor_selection(), 'editor selection from the link' );
		$_GET[ \Buddy_Builder\Templates\Cards::SELECT_ARG ] = 'nope';
		$bpb_ok( [] === \Buddy_Builder\Templates\Cards::editor_selection(), 'unknown selection ignored' );
		unset( $_GET[ \Buddy_Builder\Templates\Cards::SELECT_ARG ] );
		break;

	case 'blocks-stay-on':
		// Fresh-site test 2026-11-18: a site set up with the block editor before Elementor was active keeps the block
		// editor when Elementor is activated (the setup saves the choice; without the option, a page with a block layout
		// keeps block layouts on). 1.x sites (Elementor assignments only) keep the 1.x default.
		$bpb_opt = get_option( \Buddy_Builder\Features::BLOCKS_OPTION, null );
		$bpb_reg = get_option( Registry::OPTION, null );
		delete_option( \Buddy_Builder\Features::BLOCKS_OPTION );
		update_option( Registry::OPTION, [ 'member-profile' => [ 'builder' => 'blocks', 'id' => 123 ] ] );
		$bpb_ok( \Buddy_Builder\Features::blocks_enabled(), 'no option, a block layout assigned: block layouts stay on' );
		update_option( Registry::OPTION, [ 'member-profile' => [ 'builder' => 'elementor', 'id' => 123 ] ] );
		$bpb_ok( \Buddy_Builder\Features::blocks_enabled() === ( \Buddy_Builder\Features::is_kleo7() || ! defined( 'ELEMENTOR_VERSION' ) ), 'no option, Elementor layouts only: the 1.x default' );
		update_option( \Buddy_Builder\Features::BLOCKS_OPTION, '0' );
		update_option( Registry::OPTION, [ 'member-profile' => [ 'builder' => 'blocks', 'id' => 123 ] ] );
		$bpb_ok( ! \Buddy_Builder\Features::blocks_enabled(), 'turned off in Settings: off' );
		null === $bpb_opt ? delete_option( \Buddy_Builder\Features::BLOCKS_OPTION ) : update_option( \Buddy_Builder\Features::BLOCKS_OPTION, $bpb_opt );
		null === $bpb_reg ? delete_option( Registry::OPTION ) : update_option( Registry::OPTION, $bpb_reg );
		Registry::flush();
		break;

	case 'community-pages-rows':
		// UX review 2026-11-17, item 8: pages nested under a row by the list filters (Create a group) get their own row,
		// cards stay under their page; the intro does not mention a "Use" button; with BuddyBuilder Pro and no
		// variations, one quiet "Variations" link per row instead of an "Add a variation" button per page and card.
		$bpb_vars = get_option( 'bpb_template_variations', null );
		delete_option( 'bpb_template_variations' );
		ob_start();
		\Buddy_Builder\Admin\CommunityPages::render();
		$bpb_html = ob_get_clean();
		null === $bpb_vars ? delete_option( 'bpb_template_variations' ) : update_option( 'bpb_template_variations', $bpb_vars );
		$bpb_ok( false === strpos( $bpb_html, 'press Use' ) && false !== strpos( $bpb_html, 'class="bpb-lead"' ), 'the intro does not ask to press a Use button' );
		$bpb_rows = preg_match_all( '/<section class="bpb-cp__row[^"]*" id="bpb-page-([a-z-]+)"/', $bpb_html, $bpb_m ) ? $bpb_m[1] : [];
		$bpb_ok( (bool) $bpb_rows && ! array_filter( $bpb_rows, static function ( $t ) { return '-item' === substr( $t, -5 ); } ), 'rows are pages; cards are not rows (' . implode( ', ', $bpb_rows ) . ')' );
		if ( isset( \Buddy_Builder\Templates\Types::all()['groups-create'] ) && \Buddy_Builder\Templates\Types::is_active( 'groups-create' ) ) {
			$bpb_ok( in_array( 'groups-create', $bpb_rows, true ) && false === strpos( $bpb_html, '<li class="bpb-cp__item" id="bpb-page-groups-create"' ), 'Create a group is its own row, not an item of the groups directory' );
			$bpb_ok( array_search( 'groups-create', $bpb_rows, true ) === array_search( 'groups-directory', $bpb_rows, true ) + 1, 'Create a group follows the groups directory' );
		} else {
			echo "SKIP community-pages-rows: no Create a group page type here (BuddyBuilder Pro or the Groups component off)\n";
		}
		if ( class_exists( '\\Buddy_Builder_Pro\\Variations' ) && \Buddy_Builder_Pro\Variations::can_edit() ) {
			$bpb_ok( false === strpos( $bpb_html, 'Add a variation' ) && substr_count( $bpb_html, 'class="bpb-variations bpb-variations--empty"' ) <= count( $bpb_rows ), 'no variations: no Add a variation buttons, at most one Variations link per row (' . substr_count( $bpb_html, 'bpb-variations--empty' ) . ')' );
			$bpb_ok( false !== strpos( $bpb_html, '<option value="copy" selected' ), 'a new variation starts from a copy of the main layout' );
		} else {
			$bpb_ok( false === strpos( $bpb_html, 'bpb-variations' ), 'no variations without BuddyBuilder Pro' );
		}
		break;

	case 'elementor-panel':
		// UX review 2026-11-17, item 11: in a BuddyBuilder template the panel opens with BuddyBuilder's categories, the page
		// category holds only that page's elements, elements for any page sit in BuddyBuilder General, other pages'
		// elements leave the panel; other documents keep the usual order and every element.
		if ( ! did_action( 'elementor/loaded' ) ) {
			$bpb_skip( 'Elementor is not active' );
			break;
		}
		$bpb_tpl = (int) wp_insert_post( [ 'post_type' => 'elementor_library', 'post_status' => 'draft', 'post_title' => 'Regression panel', 'meta_input' => [ '_elementor_template_type' => 'bpb-buddypress', '_bpb_page' => 'member-profile' ] ] );
		$bpb_widgets = \Elementor\Plugin::$instance->widgets_manager->get_widget_types();
		$bpb_panel   = static function () use ( $bpb_widgets ) {
			$out = [];
			foreach ( [ 'bpb-el-member-header', 'bpb-el-directory-header', 'bpb-el-group-header', 'bpb-el-people-rail' ] as $bpb_name ) {
				if ( isset( $bpb_widgets[ $bpb_name ] ) ) {
					$out[ $bpb_name ] = [ $bpb_widgets[ $bpb_name ]->show_in_panel(), $bpb_widgets[ $bpb_name ]->get_categories()[0] ];
				}
			}
			return $out;
		};
		$bpb_order = static function () {
			// The categories are built once per request: forget them so the next read registers them again.
			$bpb_manager = \Elementor\Plugin::$instance->elements_manager;
			$bpb_reset   = function () {
				$this->categories = null;
			};
			$bpb_reset->call( $bpb_manager );
			return array_keys( $bpb_manager->get_categories() );
		};
		$_REQUEST['post'] = $bpb_tpl;
		$bpb_in  = $bpb_panel();
		$bpb_cat = $bpb_order();
		$bpb_ok( 'member-profile' === \Buddy_Builder\Integrations\Elementor\EditorPreview::edited_type(), 'the editor knows the template\'s page' );
		$bpb_ok( [ true, 'buddy-builder-elements' ] === $bpb_in['bpb-el-member-header'], 'Member header: in the page category' );
		$bpb_ok( false === $bpb_in['bpb-el-directory-header'][0] && false === $bpb_in['bpb-el-group-header'][0], 'Directory header and Group header leave the member profile panel' );
		$bpb_ok( [ true, 'buddy-builder-general-elements' ] === $bpb_in['bpb-el-people-rail'], 'an element for any page sits in BuddyBuilder General' );
		$bpb_first = array_values( array_diff( array_slice( $bpb_cat, 0, 4 ), [ 'favorites' ] ) );
		$bpb_ok( [ 'buddy-builder-elements', 'buddy-builder-header-parts', 'buddy-builder-general-elements' ] === array_slice( $bpb_first, 0, 3 ), 'BuddyBuilder categories first (' . implode( ', ', array_slice( $bpb_cat, 0, 5 ) ) . ')' );
		unset( $_REQUEST['post'] );
		$bpb_out = $bpb_panel();
		$bpb_ok( $bpb_out['bpb-el-directory-header'][0] && $bpb_out['bpb-el-group-header'][0], 'outside a BuddyBuilder template every element is offered' );
		$bpb_ok( array_search( 'buddy-builder-elements', $bpb_order(), true ) > array_search( 'basic', $bpb_order(), true ), 'outside a BuddyBuilder template the categories keep their place' );
		wp_delete_post( $bpb_tpl, true );
		break;

	case 'header-parts':
		// UX review 2026-11-17, item 1: the header parts are first-class (not deprecated), the navs and forms stay deprecated.
		$bpb_parts      = [ 'member-cover', 'member-avatar', 'member-username', 'member-meta', 'member-buttons', 'member-latest-update', 'group-cover', 'group-avatar', 'group-name', 'group-status', 'group-description', 'group-last-activity', 'group-buttons' ];
		$bpb_deprecated = \Buddy_Builder\Elements\Elements::deprecated();
		$bpb_replace    = \Buddy_Builder\Elements\Elements::replacements();
		foreach ( $bpb_parts as $bpb_part ) {
			$bpb_element = \Buddy_Builder\Elements\Elements::get( $bpb_part );
			$bpb_ok( $bpb_element && ! isset( $bpb_deprecated[ $bpb_element->block_name() ] ) && '' === \Buddy_Builder\Elements\Elements::deprecated_note( $bpb_part ), $bpb_part . ': not deprecated, no note' );
			$bpb_ok( $bpb_element && 'buddybuilder-header-parts' === $bpb_element->schema()['category'], $bpb_part . ': in the header parts category' );
			$bpb_ok( isset( $bpb_replace[ $bpb_part ] ) && $bpb_replace[ $bpb_part ]['quiet'] && 0 === strpos( $bpb_part, strtok( $bpb_replace[ $bpb_part ]['use'], '-' ) ), $bpb_part . ': quiet Replace with ' . ( isset( $bpb_replace[ $bpb_part ] ) ? $bpb_replace[ $bpb_part ]['use'] : '?' ) );
		}
		foreach ( [ 'member-nav', 'group-nav', 'activity-nav', 'activity-filters', 'activity-form' ] as $bpb_old ) {
			$bpb_ok( '' !== \Buddy_Builder\Elements\Elements::deprecated_note( $bpb_old ) && empty( $bpb_replace[ $bpb_old ]['quiet'] ), $bpb_old . ': still deprecated' );
		}
		$bpb_ok( in_array( 'buddybuilder-header-parts', wp_list_pluck( \Buddy_Builder\Templates\PostType::category( [] ), 'slug' ), true ), 'block category registered' );
		if ( did_action( 'elementor/loaded' ) ) {
			$bpb_widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'bpb-profile-member-avatar' );
			$bpb_ok( $bpb_widget && [ \Buddy_Builder\Widgets\Base::HEADER_PARTS_CATEGORY ] === $bpb_widget->get_categories(), 'Elementor: avatar widget in the header parts category' );
			$bpb_ok( $bpb_widget && isset( $bpb_widget->get_controls()['bpb_part_of_replace'] ), 'Elementor: quiet Replace button on a part' );
			$bpb_nav = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'bpb-profile-member-navigation' );
			$bpb_ok( $bpb_nav && ! isset( $bpb_nav->get_controls()['bpb_part_of_replace'] ) && false === $bpb_nav->show_in_panel(), 'Elementor: the member nav stays out of the panel' );
		}
		break;

	case 'theme-title-space':
		// UX review 2026-11-17, item 4: the group around a hidden Post Title loses its top padding (Twenty Twenty-Five
		// left about 140 px empty above the layout); nothing changes while the title shows or in other groups.
		$bpb_hide  = new ReflectionProperty( \Buddy_Builder\Templates\PageTitle::class, 'hide' );
		$bpb_hide->setAccessible( true );
		$bpb_html  = '<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><h1>x</h1></div>';
		$bpb_block = [ 'blockName' => 'core/group', 'innerBlocks' => [ [ 'blockName' => 'core/post-title' ], [ 'blockName' => 'core/post-content' ] ] ];
		$bpb_other = [ 'blockName' => 'core/group', 'innerBlocks' => [ [ 'blockName' => 'core/paragraph' ] ] ];
		$bpb_hide->setValue( null, true );
		$bpb_out = \Buddy_Builder\Templates\PageTitle::title_group( $bpb_html, $bpb_block );
		$bpb_ok( false === strpos( $bpb_out, 'padding-top' ) && false !== strpos( $bpb_out, 'padding-bottom:var(--wp--preset--spacing--60)' ), 'title hidden: the group loses its top padding only' );
		$bpb_ok( $bpb_html === \Buddy_Builder\Templates\PageTitle::title_group( $bpb_html, $bpb_other ), 'title hidden: other groups unchanged' );
		$bpb_ok( '<div class="x" style="padding-top:2rem"></div>' !== \Buddy_Builder\Templates\PageTitle::title_group( '<div class="x" style="padding-top:2rem"></div>', $bpb_block ) && false === strpos( \Buddy_Builder\Templates\PageTitle::title_group( '<div class="x" style="padding-top:2rem"></div>', $bpb_block ), 'style=' ), 'title hidden: an empty style attribute is removed' );
		$bpb_hide->setValue( null, false );
		$bpb_ok( $bpb_html === \Buddy_Builder\Templates\PageTitle::title_group( $bpb_html, $bpb_block ), 'title shown: unchanged' );
		$bpb_hide->setValue( null, null );
		break;

	case 'profile-fields':
		// UX review 2026-11-17, item 6: field pickers list the site's profile fields, saved values stay strings (any
		// value is kept, no migration), the setup creates only the missing standard fields a style shows.
		$bpb_header = \Buddy_Builder\Elements\Elements::get( 'member-header' );
		$bpb_about  = \Buddy_Builder\Elements\Elements::get( 'member-about' );
		$bpb_ok( 'Job title' === $bpb_header->normalize( [ 'headline_field' => 'Job title' ] )['headline_field'] && 'Headline' === $bpb_header->normalize( [] )['headline_field'], 'Member header: any saved field name is kept, the default stays Headline' );
		$bpb_ok( 'Bio, About' === $bpb_about->normalize( [ 'fields' => 'Bio, About' ] )['fields'], 'Member about: the saved field list is kept as written' );
		$bpb_ctl = [];
		foreach ( array_merge( $bpb_header->editor_definition()['controls'], $bpb_about->editor_definition()['controls'] ) as $bpb_control ) {
			$bpb_ctl[ $bpb_control['key'] ] = $bpb_control;
		}
		$bpb_names = \Buddy_Builder\Templates\ProfileFields::names();
		$bpb_ok( 'select' === $bpb_ctl['headline_field']['control'] && ! empty( $bpb_ctl['headline_field']['anyValue'] ) && in_array( 'Headline', wp_list_pluck( $bpb_ctl['headline_field']['options'], 'value' ), true ) && in_array( '', wp_list_pluck( $bpb_ctl['location_field']['options'], 'value' ), true ), 'headline and location: a select of the profile fields (with None, Headline and Location always offered)' );
		$bpb_ok( 'checklist' === $bpb_ctl['fields']['control'] && wp_list_pluck( $bpb_ctl['fields']['options'], 'value' ) === $bpb_names, 'about fields: a checklist of the ' . count( $bpb_names ) . ' profile fields' );
		$bpb_ok( [ 'Headline', 'Location', 'About', 'Website' ] === \Buddy_Builder\Templates\ProfileFields::used_by( \Buddy_Builder\Presets\Presets::get( 'classic-member-profile' )['content'] ), 'the Classic profile shows Headline, Location, About, Website' );
		$bpb_ok( [] === \Buddy_Builder\Templates\ProfileFields::used_by( '<!-- wp:buddybuilder/member-header {"show_headline":false,"show_meta":false} /-->' ), 'hidden parts need no field' );
		$bpb_missing = \Buddy_Builder\Templates\ProfileFields::missing( [ 'Headline', 'Location', 'About', 'Website', 'Favourite colour' ] );
		$bpb_ok( ! in_array( 'Favourite colour', $bpb_missing, true ), 'only standard fields can be created' );
		if ( $bpb_missing && function_exists( 'xprofile_insert_field' ) ) {
			$bpb_created_fields = \Buddy_Builder\Templates\ProfileFields::create( $bpb_missing );
			$bpb_ok( $bpb_created_fields === $bpb_missing && [] === \Buddy_Builder\Templates\ProfileFields::missing( $bpb_missing ), 'creates the missing fields: ' . implode( ', ', $bpb_created_fields ) );
			$bpb_group = 0;
			foreach ( (array) bp_xprofile_get_groups( [ 'fetch_fields' => true ] ) as $bpb_g ) {
				if ( \Buddy_Builder\Templates\ProfileFields::GROUP === $bpb_g->name ) {
					$bpb_group = (int) $bpb_g->id;
					foreach ( (array) $bpb_g->fields as $bpb_f ) {
						if ( in_array( $bpb_f->name, $bpb_created_fields, true ) ) {
							$bpb_ok( ! $bpb_f->is_required && 'public' === bp_xprofile_get_meta( $bpb_f->id, 'field', 'default_visibility' ), $bpb_f->name . ': optional, public, type ' . $bpb_f->type );
						}
					}
				}
			}
			$bpb_ok( $bpb_group > 1, 'in their own group, not on the sign-up form' );
			// Put the site back.
			foreach ( $bpb_created_fields as $bpb_name ) {
				xprofile_delete_field( xprofile_get_field_id_from_name( $bpb_name ) );
			}
			if ( $bpb_group ) {
				xprofile_delete_field_group( $bpb_group );
			}
			\Buddy_Builder\Templates\ProfileFields::flush();
		} else {
			$bpb_skip( 'the site already has every standard field (creation not run)' );
		}
		break;

	case 'split-header':
		// UX review 2026-11-17, item 7: a Member header split into parts (BuddyBuilder Pro) prints the header's own
		// markup, part by part, so it looks the same; without design "header" the 1.x parts keep their markup.
		$bpb_member = get_users( [ 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID' ] );
		$bpb_was    = buddypress()->displayed_user->id ?? 0;
		buddypress()->displayed_user->id = (int) $bpb_member[0];
		$bpb_render = static function ( $name, $attrs ) {
			$element = \Buddy_Builder\Elements\Elements::get( $name );
			if ( ! $element ) {
				return null;
			}
			$attrs = $element->normalize( $attrs );
			return trim( $element->render( $attrs, $element->kit()->context( $element, $attrs, null, false ) ) );
		};
		$bpb_part  = static function ( $name, $attrs = [] ) use ( $bpb_render ) {
			$html = $bpb_render( $name, $attrs );
			return null === $html ? null : str_replace( ' bpb-mh-part', '', $html );
		};
		$bpb_whole = $bpb_render( 'member-header', [] );
		foreach ( [ 'member-cover' => [], 'member-avatar' => [], 'member-meta' => [], 'member-buttons' => [], 'member-username' => [ 'username_type' => 'username' ] ] as $bpb_name => $bpb_attrs ) {
			$bpb_html = $bpb_part( $bpb_name, $bpb_attrs + [ 'design' => 'header' ] );
			$bpb_ok( '' !== $bpb_html && false !== strpos( $bpb_whole, $bpb_html ), $bpb_name . ': as a header part it prints the Member header\'s markup' );
		}
		$bpb_name_html = $bpb_render( 'member-username', [ 'design' => 'header', 'username_type' => 'full_name' ] );
		$bpb_ok( false !== strpos( $bpb_name_html, '<h1 class="bpb-mh__title bpb-mh-part">' . esc_html( bp_core_get_user_displayname( $bpb_member[0] ) ) . '</h1>' ), 'member-username: as a header part the name is the h1 of the name row' );
		$bpb_ok( false !== strpos( (string) $bpb_render( 'member-avatar', [] ), 'elementor-image-bpb-avatar' ) && false !== strpos( (string) $bpb_render( 'member-username', [] ), 'user-nicename' ), '1.x parts (no design) keep their markup' );
		$bpb_ok( ! in_array( 'design', wp_list_pluck( \Buddy_Builder\Elements\Elements::get( 'member-avatar' )->editor_definition()['controls'], 'key' ), true ), 'design has no control in the editors (kit control "none")' );
		if ( \Buddy_Builder\Elements\Elements::get( 'member-stats' ) ) {
			$bpb_ok( false !== strpos( $bpb_whole, $bpb_part( 'member-stats' ) ), 'Pro member-stats: by default the Member header\'s counts' );
			$bpb_order = wp_list_pluck( \Buddy_Builder_Pro\HeaderParts::stats( $bpb_member[0], 'posts, friends' ), 0 );
			$bpb_ok( array_values( array_intersect( [ 'posts', 'friends' ], $bpb_order ) ) === $bpb_order, 'Pro member-stats: picked counts in the picked order (' . implode( ', ', $bpb_order ) . ')' );
			$bpb_badges = bpb_mh_badges( $bpb_member[0] );
			$bpb_ok( '' === $bpb_badges ? '' === $bpb_part( 'member-badges' ) : false !== strpos( $bpb_part( 'member-badges' ), $bpb_badges ), 'Pro member-badges: the Member header\'s badges' );
			$bpb_ok( '' === $bpb_part( 'member-badges', [ 'show_role' => false, 'show_types' => false ] ) || has_filter( 'buddy_builder/member_badges' ), 'Pro member-badges: role and types can be left out' );
		} else {
			$bpb_skip( 'BuddyBuilder Pro not active: no Pro header parts' );
		}
		buddypress()->displayed_user->id = $bpb_was;
		break;

	case 'fresh-site-points':
		// Fresh-site test (2026-11-18): Help offers the undo to 1.9 only on sites updated from 1.x; a component's
		// "Turn on" turns it on in place (nonce'd admin-post action); header part widgets are named like their blocks;
		// the About card does not repeat its title as a field label; free users see a short Pro teaser.
		$bpb_from = get_option( \Buddy_Builder\Migrations\Runner::FROM_1X, null );
		$bpb_help = static function () {
			ob_start();
			\Buddy_Builder\Compat\StatusPage::render();
			return ob_get_clean();
		};
		if ( ! class_exists( '\\Buddy_Builder\\Compat\\StatusPage' ) ) {
			require_once BPB_BASE_PATH . 'admin/pages/Status.php';
		}
		wp_set_current_user( (int) get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] )[0] );
		if ( class_exists( '\\Buddy_Builder\\Compat\\StatusPage' ) ) {
			delete_option( \Buddy_Builder\Migrations\Runner::FROM_1X );
			$bpb_ok( false === strpos( $bpb_help(), 'value="rollback"' ), 'Help: no undo to 1.9 on a site that never had 1.x data' );
			update_option( \Buddy_Builder\Migrations\Runner::FROM_1X, 1, false );
			$bpb_ok( false !== strpos( $bpb_help(), 'value="rollback"' ), 'Help: the undo to 1.9 on a site updated from 1.x' );
			null === $bpb_from ? delete_option( \Buddy_Builder\Migrations\Runner::FROM_1X ) : update_option( \Buddy_Builder\Migrations\Runner::FROM_1X, $bpb_from, false );
		} else {
			$bpb_skip( 'Help screen class not loaded in this context' );
		}
		$bpb_url = \Buddy_Builder\Admin\Checker::turn_on_url( 'blogs' );
		$bpb_ok( false !== strpos( $bpb_url, 'admin-post.php' ) && false !== strpos( $bpb_url, 'component=blogs' ) && false !== strpos( $bpb_url, '_wpnonce=' ), 'Turn on: an admin-post link with the component and a nonce' );
		$bpb_active = bp_get_option( 'bp-active-components' );
		if ( ! bp_is_active( 'blogs' ) && ! is_multisite() ) {
			global $wpdb;
			$bpb_tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->base_prefix . 'bp_user_blogs' ) . '%' ) );
			$bpb_on     = new ReflectionMethod( \Buddy_Builder\Admin\Checker::class, 'activate_component' );
			$bpb_on->setAccessible( true );
			$bpb_ok( $bpb_on->invoke( null, 'blogs' ) && ! empty( bp_get_option( 'bp-active-components' )['blogs'] ), 'Turn on: the component is on, the others stay as they were' );
			$bpb_ok( ! $bpb_on->invoke( null, 'not-a-component' ), 'Turn on: an unknown component is refused (the components screen opens instead)' );
			bp_update_option( 'bp-active-components', $bpb_active );
			foreach ( array_diff( $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->base_prefix . 'bp_user_blogs' ) . '%' ) ), $bpb_tables ) as $bpb_table ) {
				$wpdb->query( 'DROP TABLE `' . esc_sql( $bpb_table ) . '`' ); // phpcs:ignore WordPress.DB
			}
		} else {
			$bpb_skip( 'Site Tracking is on (or multisite): turning a component on not run' );
		}
		if ( did_action( 'elementor/loaded' ) ) {
			foreach ( [ 'bpb-profile-member-avatar' => 'member-avatar', 'bpb-profile-member-meta' => 'member-meta', 'bpb-profile-group-status' => 'group-status' ] as $bpb_widget => $bpb_name ) {
				$bpb_w = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $bpb_widget );
				$bpb_ok( $bpb_w && \Buddy_Builder\Elements\Elements::get( $bpb_name )->title() === $bpb_w->get_title() && $bpb_widget === $bpb_w->get_name(), 'Elementor: ' . $bpb_widget . ' is named like its block (' . ( $bpb_w ? $bpb_w->get_title() : '?' ) . '), same widget name' );
			}
		}
		$bpb_member = get_users( [ 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID' ] );
		$bpb_was    = buddypress()->displayed_user->id ?? 0;
		buddypress()->displayed_user->id = (int) $bpb_member[0];
		$bpb_about  = \Buddy_Builder\Elements\Elements::get( 'member-about' );
		add_filter( 'xprofile_get_field_data', $bpb_value = static function () { return 'Some words.'; } );
		$bpb_attrs = $bpb_about->normalize( [ 'fields' => 'About, Location', 'show_groups' => false ] );
		$bpb_html  = $bpb_about->render( $bpb_attrs, $bpb_about->kit()->context( $bpb_about, $bpb_attrs, null, false ) );
		remove_filter( 'xprofile_get_field_data', $bpb_value );
		buddypress()->displayed_user->id = $bpb_was;
		if ( xprofile_get_field_id_from_name( 'About' ) && xprofile_get_field_id_from_name( 'Location' ) ) {
			$bpb_ok( false === strpos( $bpb_html, '<dt>About</dt>' ) && false !== strpos( $bpb_html, '<dt>Location</dt>' ), 'About card: the About field has no "About" label under the "About" title, other fields keep theirs' );
		} else {
			$bpb_skip( 'no About and Location profile fields on this site' );
		}
		if ( \Buddy_Builder\Upsell\Upsell::enabled() ) {
			ob_start();
			\Buddy_Builder\Upsell\Upsell::render_features();
			$bpb_html  = ob_get_clean();
			$bpb_first = substr( $bpb_html, 0, strpos( $bpb_html, '<details' ) );
			$bpb_ok( substr_count( $bpb_first, 'data-bpb-pro=' ) <= 4 && false !== strpos( $bpb_html, 'See all in Pro' ) && substr_count( $bpb_html, 'data-bpb-pro=' ) === count( \Buddy_Builder\Upsell\Upsell::features() ), 'Pro teaser: ' . substr_count( $bpb_first, 'data-bpb-pro=' ) . ' highlights, the rest behind See all in Pro' );
		} else {
			$bpb_skip( 'BuddyBuilder Pro active: no Pro teaser' );
		}
		break;

	case 'pro-locks':
		// UX review items 7 and 13 (free and Pro): without BuddyBuilder Pro, "Split into parts" shows locked on the
		// Member header (block editor) and the Community style's photo shape, spacing and font are locked (their saved
		// values are kept, not used); BuddyBuilder Pro unlocks both.
		$bpb_pro    = defined( 'BPB_PRO_VERSION' );
		$bpb_cs     = \Buddy_Builder\Presets\CommunityStyle::class;
		$bpb_cs_was = get_option( $bpb_cs::OPTION, null );
		update_option( $bpb_cs::OPTION, [ 'accent' => '#facc15', 'radius' => 'square', 'avatar' => 'square', 'density' => 'compact', 'font' => 'theme' ] );
		$bpb_ok( ( $bpb_pro ? [] : [ 'avatar', 'density', 'font' ] ) === $bpb_cs::locked(), 'Community style: locked choices (' . implode( ', ', $bpb_cs::locked() ) . ')' );
		$bpb_tokens = $bpb_cs::tokens();
		$bpb_ok( '#facc15' === $bpb_tokens['--bpb-accent'] && '0' === $bpb_tokens['--bpb-radius'], 'Community style: accent and corners apply' );
		$bpb_ok( $bpb_pro === isset( $bpb_tokens['--bpb-avatar-radius'], $bpb_tokens['--bpb-density'], $bpb_tokens['--bpb-font-body'] ) && ( $bpb_pro || ( ! isset( $bpb_tokens['--bpb-avatar-radius'] ) && ! isset( $bpb_tokens['--bpb-density'] ) && ! isset( $bpb_tokens['--bpb-font-body'] ) ) ), $bpb_pro ? 'Community style: photo shape, spacing and font apply with Pro' : 'Community style: the saved photo shape, spacing and font are not used without Pro' );
		ob_start();
		\Buddy_Builder\Admin\Options::render_community_style();
		$bpb_html = ob_get_clean();
		$bpb_ok( ( $bpb_pro ? 0 : 3 ) === substr_count( $bpb_html, ' disabled aria-describedby="bpb-cs-pro"' ) && $bpb_pro !== ( false !== strpos( $bpb_html, 'utm_content=community-style' ) ) && 4 === substr_count( $bpb_html, '<select ' ), $bpb_pro ? 'Community style card: every choice open' : 'Community style card: photo shape, spacing and font shown locked with Pro and a link to the plans' );
		$bpb_cs::save( [ 'radius' => 'small' ] );
		$bpb_saved_cs = (array) get_option( $bpb_cs::OPTION, [] );
		$bpb_ok( 'small' === $bpb_saved_cs['radius'] && ( $bpb_pro ? ! isset( $bpb_saved_cs['avatar'] ) : ( 'square' === $bpb_saved_cs['avatar'] && 'compact' === $bpb_saved_cs['density'] && 'theme' === $bpb_saved_cs['font'] ) ), $bpb_pro ? 'Community style: a save sets every choice' : 'Community style: a save keeps the saved Pro choices for when Pro is back' );
		$bpb_cs::save( [], true );
		$bpb_ok( null === get_option( $bpb_cs::OPTION, null ), 'Community style: Reset removes every choice' );
		null === $bpb_cs_was ? delete_option( $bpb_cs::OPTION ) : update_option( $bpb_cs::OPTION, $bpb_cs_was );

		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		set_current_screen( PostType::POST_TYPE );
		\Buddy_Builder\Upsell\Upsell::block_editor_assets();
		$bpb_data = wp_scripts()->get_data( 'bpb-upsell-blocks', 'data' );
		// A Pro without a license shows the same lock with a link to the license (case pro-unlicensed).
		$bpb_open = $bpb_pro && ! \Buddy_Builder_Pro\Blocks::locked();
		$bpb_ok( $bpb_open !== wp_script_is( 'bpb-upsell-blocks', 'enqueued' ) && ( $bpb_pro || ( false !== strpos( (string) $bpb_data, '"feature":"split-header"' ) && false !== strpos( (string) $bpb_data, 'utm_content=split-header' ) && isset( \Buddy_Builder\Upsell\Upsell::features()['split-header'] ) ) ), $bpb_open ? 'block editor: no locked Split into parts with a licensed Pro' : ( $bpb_pro ? 'block editor: Split into parts locked while Pro is not licensed' : 'block editor: Split into parts (Pro) locked on the Member header, with the Pro description and link' ) );
		set_current_screen( 'front' );
		break;

	case 'pro-unlicensed':
		// BuddyBuilder Pro installed but not licensed: the same locked Split into parts as without Pro, in both editors,
		// with "Activate your license" to the license card instead of the plans. The state is forced before any widget
		// builds its controls (Elementor caches them per widget type), so this case runs in its own process.
		if ( ! defined( 'BPB_PRO_VERSION' ) || ! class_exists( '\\Buddy_Builder_Pro\\License' ) ) {
			$bpb_skip( 'BuddyBuilder Pro is not active' );
			break;
		}
		$bpb_state = 'locked';
		add_filter(
			'bpb_pro_license_state',
			static function ( $state ) use ( &$bpb_state ) {
				return array_merge( $state, [ 'source' => 'locked' === $bpb_state ? '' : 'expired', 'licensed' => 'locked' !== $bpb_state, 'locked' => 'locked' === $bpb_state, 'expired' => 'expired' === $bpb_state ] );
			},
			99
		);
		$bpb_license = \Buddy_Builder_Pro\License::manage_url();
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		set_current_screen( PostType::POST_TYPE );
		\Buddy_Builder\Upsell\Upsell::block_editor_assets();
		// The localized objects (WordPress 6.6 escapes their slashes).
		$bpb_json   = static function ( $handle ) {
			$data = (string) wp_scripts()->get_data( $handle, 'data' );
			return (array) json_decode( substr( $data, (int) strpos( $data, '{' ), (int) strrpos( $data, '}' ) - (int) strpos( $data, '{' ) + 1 ), true );
		};
		$bpb_data   = $bpb_json( 'bpb-upsell-blocks' );
		$bpb_upsell = $bpb_json( 'bpb-upsell' );
		$bpb_ok( wp_script_is( 'bpb-upsell-blocks', 'enqueued' ) && $bpb_license === ( $bpb_data['url'] ?? '' ) && 'Activate your license' === ( $bpb_data['cta'] ?? '' ), 'block editor: Split into parts (Pro) locked, with Activate your license to the license (' . $bpb_license . ')' );
		$bpb_ok( $bpb_license === ( $bpb_upsell['features']['split-header']['url'] ?? '' ) && 'Activate your license' === ( $bpb_upsell['features']['split-header']['cta'] ?? '' ), 'block editor: the toolbar item\'s description links to the license too' );
		$bpb_ok( ! wp_script_is( 'bpb-pro-split-header', 'enqueued' ), 'block editor: no real Split into parts while not licensed' );
		set_current_screen( 'front' );
		if ( did_action( 'elementor/loaded' ) ) {
			$bpb_header = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'bpb-el-member-header' );
			$bpb_note   = $bpb_header ? $bpb_header->get_controls( 'bpb_split_pro_note' ) : null;
			// Outside the editor Elementor keeps no control text: the link is the filter's, which elementor_split() prints.
			$bpb_lock = apply_filters( 'buddy_builder/upsell/split_lock', null );
			$bpb_ok( $bpb_note && null === $bpb_header->get_controls( 'bpb_split_note' ) && isset( $bpb_lock['url'], $bpb_lock['cta'] ) && $bpb_license === $bpb_lock['url'] && 'Activate your license' === $bpb_lock['cta'], 'Elementor: Split into parts (Pro) locked on the Member header widget, with Activate your license' );
		} else {
			$bpb_skip( 'Elementor is not active' );
		}
		$bpb_state = 'expired';
		$bpb_ok( null === apply_filters( 'buddy_builder/upsell/split_lock', null ), 'expired license: no lock (the editor stays unlocked, the real action shows)' );
		break;

	case 'split-header-elementor':
		// UX review item 7 in Elementor: a Member header split into parts is Flexbox Containers with the header's classes
		// (in a column of a section too); they render without Elementor's container layout classes, so the header's CSS
		// lays them out; the parts print the header's markup; the Split action (Pro) or its locked version (free) sits
		// on the Member header widget; the Pro parts sit in the Header parts category.
		if ( ! did_action( 'elementor/loaded' ) ) {
			$bpb_skip( 'Elementor is not active' );
			break;
		}
		$bpb_pro     = defined( 'BPB_PRO_VERSION' );
		$bpb_open    = $bpb_pro && class_exists( '\\Buddy_Builder_Pro\\Blocks' ) && ! \Buddy_Builder_Pro\Blocks::locked();
		$bpb_widgets = \Elementor\Plugin::$instance->widgets_manager->get_widget_types();
		$bpb_header  = isset( $bpb_widgets['bpb-el-member-header'] ) ? $bpb_widgets['bpb-el-member-header'] : null;
		$bpb_has     = static function ( $widget, $control ) {
			return $widget && null !== $widget->get_controls( $control );
		};
		if ( ! $bpb_pro ) {
			$bpb_ok( $bpb_has( $bpb_header, 'bpb_split_pro_note' ) && ! $bpb_has( $bpb_header, 'bpb_split_note' ), 'Member header widget: Split into parts shown locked, with the link to the Pro plans' );
		} elseif ( ! $bpb_open ) {
			$bpb_ok( $bpb_has( $bpb_header, 'bpb_split_pro_note' ) && ! $bpb_has( $bpb_header, 'bpb_split_note' ), 'Member header widget: Split into parts shown locked while BuddyBuilder Pro has no license (link to the license, case pro-unlicensed)' );
		} else {
			$bpb_ok( $bpb_has( $bpb_header, 'bpb_split_note' ) && ! $bpb_has( $bpb_header, 'bpb_split_pro_note' ), 'Member header widget: Split into parts (Pro)' );
			$bpb_ok( \Buddy_Builder_Pro\HeaderParts::elementor_containers() === $bpb_has( $bpb_header, 'bpb_split_run' ), 'Split button only with Flexbox Containers on (a note otherwise)' );
			$bpb_ok( $bpb_has( $bpb_widgets['bpb-profile-member-avatar'], 'bpb_join_note' ) && $bpb_has( $bpb_widgets['bpb-el-member-stats'], 'bpb_join_note' ), 'parts offer Join into one Member header (inside a split header)' );
		}
		if ( $bpb_pro ) {
			$bpb_parts = [];
			foreach ( [ 'bpb-el-member-stats', 'bpb-el-member-headline', 'bpb-el-member-badges' ] as $bpb_name ) {
				$bpb_parts[] = isset( $bpb_widgets[ $bpb_name ] ) ? $bpb_widgets[ $bpb_name ]->get_categories()[0] : '-';
			}
			$bpb_ok( [ 'buddy-builder-header-parts' ] === array_unique( $bpb_parts ), 'Pro parts Stats, Headline, Badges in the Header parts category (' . implode( ', ', $bpb_parts ) . ')' );
		}
		$bpb_avatar = $bpb_widgets['bpb-profile-member-avatar'];
		$bpb_ok( 'header' === ( $bpb_avatar->get_controls( 'bpb_part_of' )['condition']['design!'] ?? '' ), 'a part of a split header does not offer Replace with Member header' );

		// A split header in a column, rendered on the page (Elementor skips container elements while Flexbox Containers
		// are off: the split header then does not render until they are on again).
		if ( ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' ) ) {
			$bpb_skip( 'Flexbox Containers are off on this site: a split header does not render' );
			break;
		}
		$bpb_member = get_users( [ 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID' ] );
		$bpb_was    = buddypress()->displayed_user->id ?? 0;
		buddypress()->displayed_user->id = (int) $bpb_member[0];
		$bpb_n      = 0;
		$bpb_el     = static function ( $type, $settings, $children = [] ) use ( &$bpb_n ) {
			$bpb_n++;
			$el = [ 'id' => 'bpbx' . $bpb_n, 'elType' => $type, 'settings' => $settings, 'elements' => $children ];
			if ( 'container' !== $type && 'section' !== $type && 'column' !== $type ) {
				$el = [ 'id' => 'bpbx' . $bpb_n, 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings + [ 'design' => 'header' ], 'elements' => [] ];
			}
			return $el;
		};
		$bpb_box    = static function ( $classes, $children, $extra = [] ) use ( $bpb_el ) {
			return $bpb_el( 'container', [ 'content_width' => 'full', 'css_classes' => $classes, 'design' => 'header' ] + $extra, $children );
		};
		$bpb_data   = [
			$bpb_el(
				'section',
				[],
				[
					$bpb_el(
						'column',
						[ '_column_size' => 100 ],
						[
							$bpb_box(
								'bpb-mh bpb-mh--side bpb-mh--has-cover bpb-mh--parts',
								[
									$bpb_el( 'bpb-profile-member-cover', [] ),
									$bpb_box(
										'bpb-mh__body',
										[
											$bpb_el( 'bpb-profile-member-avatar', [] ),
											$bpb_box( 'bpb-mh__main', [ $bpb_box( 'bpb-mh__name', [ $bpb_el( 'bpb-profile-member-username', [ 'username_type' => 'full_name' ] ) ] ), $bpb_el( 'bpb-profile-member-meta', [] ) ] ),
											$bpb_el( 'bpb-profile-member-buttons', [] ),
										]
									),
								],
								[ 'html_tag' => 'header' ]
							),
						]
					),
				]
			),
		];
		$bpb_doc = (int) wp_insert_post( [ 'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'Regression split header', 'meta_input' => [ '_elementor_template_type' => 'bpb-buddypress', '_bpb_page' => 'member-profile', '_elementor_edit_mode' => 'builder', '_elementor_data' => wp_slash( wp_json_encode( $bpb_data ) ) ] ] );
		$bpb_created[] = $bpb_doc;
		$bpb_html  = (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $bpb_doc );
		$bpb_boxes = preg_match_all( '/<(?:div|header)[^>]*class="([^"]*\b(?:bpb-mh--parts|bpb-mh__body|bpb-mh__main|bpb-mh__name)\b[^"]*)"/', $bpb_html, $bpb_m ) ? $bpb_m[1] : [];
		$bpb_ok( 4 === count( $bpb_boxes ) && ! preg_grep( '/(^|\s)e-(con|flex|grid|child|parent)(\s|$|-)/', $bpb_boxes ), 'the split header\'s containers render without Elementor\'s container layout classes (' . count( $bpb_boxes ) . ' boxes)' );
		$bpb_ok( false !== strpos( $bpb_html, '<header class="elementor-element' ) && false !== strpos( $bpb_html, 'bpb-mh__avatar bpb-mh-part' ) && false !== strpos( $bpb_html, 'bpb-mh__title bpb-mh-part' ), 'the frame is a header and the parts print the header\'s markup' );
		buddypress()->displayed_user->id = $bpb_was;
		break;

	default:
		WP_CLI::error( 'Unknown case. Cases: customizer-block-layout, elementor-off, settings-save-no-elementor, inserter-js-only-block, preview-buddyboss, sample-member-hidden, preview-seen-by <viewer>, community-pages-read-only, checker-known-issues, setup-new-install, checker-readylaunch, change-look-undo, cards-follow-layout, layout-actions, element-settings, elementor-basic-controls, layout-import, notice-dismiss, card-line, blocks-stay-on, community-pages-rows, elementor-panel, header-parts, theme-title-space, profile-fields, split-header, fresh-site-points, pro-locks, pro-unlicensed, split-header-elementor' );
}

// Cleanup.
foreach ( $bpb_created as $bpb_id ) {
	wp_delete_post( $bpb_id, true );
}
foreach ( $bpb_saved as $bpb_name => $bpb_value ) {
	if ( null === $bpb_value ) {
		delete_option( $bpb_name );
	} else {
		update_option( $bpb_name, $bpb_value );
	}
}
Registry::flush();

if ( $bpb_fails ) {
	WP_CLI::halt( 1 );
}
