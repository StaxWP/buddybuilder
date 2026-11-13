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
 * - preview-seen-by <viewer>    Majors UX B-M3: member preview seen by self|friend|member|guest|'' (buttons follow,
 *                               the real user is back after the request).
 * - community-pages-read-only   B-M1: rendering Community pages writes nothing; rows show the assignments.
 * - checker-known-issues        B-M4: page template finding; last finding kept and forgotten.
 * - setup-new-install           B-M2: the setup redirect is asked for on new installs only.
 * - checker-readylaunch         B-M4: BuddyBoss ReadyLaunch detection (real function on BuddyBoss 2.9+, else mocked).
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
$bpb_options = [ 'bpb_settings', 'bpb_templates', 'bpb_blocks_enabled' ];
$bpb_saved   = [];
foreach ( $bpb_options as $bpb_name ) {
	$bpb_saved[ $bpb_name ] = get_option( $bpb_name, null );
}
$bpb_created = [];

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
		$bpb_ok( '1' === (string) get_option( \Buddy_Builder\Admin\Setup::REDIRECT ), 'activation asks for the setup once' );
		delete_option( \Buddy_Builder\Admin\Setup::REDIRECT );
		foreach ( $bpb_keep as $bpb_name => $bpb_value ) {
			if ( null !== $bpb_value ) {
				update_option( $bpb_name, $bpb_value );
			}
		}
		\Buddy_Builder\Admin\Setup::on_activation();
		$bpb_ok( false === get_option( \Buddy_Builder\Admin\Setup::REDIRECT, false ), 'activation on an existing site does not' );
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

	default:
		WP_CLI::error( 'Unknown case. Cases: customizer-block-layout, elementor-off, settings-save-no-elementor, inserter-js-only-block, preview-buddyboss, sample-member-hidden, preview-seen-by <viewer>, community-pages-read-only, checker-known-issues, setup-new-install, checker-readylaunch' );
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
