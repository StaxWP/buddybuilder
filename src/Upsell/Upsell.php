<?php
/**
 * BuddyBuilder Pro in the free plugin: Pro elements in the Elementor panel and Pro features on the Community pages
 * screen, greyed out with a "Pro" badge (never hidden, never locking a free feature), and one dismissible notice
 * after a clear milestone. Nothing shows while BuddyBuilder Pro is active (licensed or not). No tracking: links
 * carry UTM parameters only.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Upsell;

use Buddy_Builder\Templates\Registry;

defined( 'ABSPATH' ) || die();

/**
 * Class Upsell
 */
final class Upsell {

	/** Pricing page. */
	const URL = 'https://staxwp.com/buddybuilder/';

	/** Action of the notice dismiss (admin-post without JavaScript, admin-ajax with it). */
	const DISMISS_ACTION = 'bpb_upsell_dismiss';

	/** User meta set once the notice is dismissed. */
	const DISMISSED_META = 'bpb_upsell_notice_dismissed';

	/** Community page types with a BuddyBuilder layout before the notice shows. */
	const MILESTONE = 3;

	/** Option: when the first BuddyBuilder layout went live (timestamp), the start of the notice delay. */
	const SINCE_OPTION = 'bpb_upsell_since';

	/** Days between the first live layout (or, on sites updated from 1.x, the milestone) and the notice. */
	const DELAY_DAYS = 7;

	/** Prefix of the promotion items in the Elementor panel. */
	const ELEMENTOR_PREFIX = 'bpb-pro-';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_filter( 'elementor/editor/localize_settings', [ __CLASS__, 'elementor_promotions' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'elementor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'block_editor_assets' ] );
		add_action( 'elementor/element/bpb-el-member-header/kit_advanced/after_section_end', [ __CLASS__, 'elementor_split' ] );
		add_action( 'admin_notices', [ __CLASS__, 'notice' ] );
		add_action( 'buddy_builder/registry/changed', [ __CLASS__, 'layout_live' ], 10, 3 );
		add_action( 'admin_post_' . self::DISMISS_ACTION, [ __CLASS__, 'dismiss' ] );
		add_action( 'wp_ajax_' . self::DISMISS_ACTION, [ __CLASS__, 'dismiss' ] );
	}

	/**
	 * Whether Pro features are promoted: only while BuddyBuilder Pro is not active.
	 *
	 * @return bool
	 */
	public static function enabled() {
		$enabled = ! defined( 'BPB_PRO_VERSION' ) && ! function_exists( 'bpb_is_pro' );

		/**
		 * Filters whether BuddyBuilder shows its Pro badges, Pro links and the Pro notice.
		 *
		 * @param bool $enabled True while BuddyBuilder Pro is not active.
		 */
		return (bool) apply_filters( 'buddy_builder/upsell/enabled', $enabled );
	}

	/**
	 * Link to the BuddyBuilder Pro plans.
	 *
	 * @param string $feature Feature id (utm_content).
	 * @param string $medium  panel or notice (utm_medium).
	 * @return string
	 */
	public static function url( $feature, $medium = 'panel' ) {
		return add_query_arg(
			[
				'utm_source'   => 'plugin',
				'utm_medium'   => sanitize_key( $medium ),
				'utm_campaign' => 'bb-free',
				'utm_content'  => sanitize_key( $feature ),
			],
			self::URL
		) . '#pricing';
	}

	/**
	 * BuddyBuilder Pro features, as BuddyBuilder Pro ships them (its widgets, elements and modules).
	 *
	 * Elements carry an "elementor" entry: they show in the Elementor panel. Each feature: title, text, and for
	 * elements icon, category and template types (empty: every page, as in Pro).
	 *
	 * @return array id => feature
	 */
	public static function features() {
		$styles = \Buddy_Builder\Presets\Styles::pro_teasers();

		$features = [
			// Elementor widgets of BuddyBuilder Pro (core/widgets/common).
			'notifications'        => [
				'title'     => __( 'Notifications', 'stax-buddy-builder' ),
				'text'      => __( 'A notifications icon with a dropdown of the member\'s latest notifications, updated live.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'bbl-notifications', 'category' => 'buddy-builder-general-elements' ],
			],
			'messages'             => [
				'title'     => __( 'Messages', 'stax-buddy-builder' ),
				'text'      => __( 'A messages icon with a dropdown of the member\'s latest private messages, updated live.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'bbl-members_messages', 'category' => 'buddy-builder-general-elements' ],
			],
			'members-tab-list'     => [
				'title'     => __( 'Members in tabs', 'stax-buddy-builder' ),
				'text'      => __( 'A grid of members with tabs to switch between lists, for any page.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'bbl-members-tab-list', 'category' => 'buddy-builder-general-elements' ],
			],
			'groups-tab-list'      => [
				'title'     => __( 'Groups in tabs', 'stax-buddy-builder' ),
				'text'      => __( 'A grid of groups with tabs to switch between lists, for any page.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'bbl-groups-tab-list', 'category' => 'buddy-builder-general-elements' ],
			],
			'post-update-form'     => [
				'title'     => __( 'Post form (any page)', 'stax-buddy-builder' ),
				'text'      => __( 'The "What\'s new" form, so members can post activity updates from any page.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'bbl-post-update-form', 'category' => 'buddy-builder-general-elements' ],
			],
			'member-xprofile-field' => [
				'title'     => __( 'Member XProfile Field', 'stax-buddy-builder' ),
				'text'      => __( 'Any profile field of the member, anywhere in the profile layout, with an icon and text.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'bbl-members-xprofile-fields', 'category' => 'buddy-builder-elements', 'templates' => [ 'member-profile' ] ],
			],
			// Elements of BuddyBuilder Pro 2.0 (src/Elements), blocks and Elementor widgets.
			'shortcuts-nav'        => [
				'title'     => __( 'Community shortcuts', 'stax-buddy-builder' ),
				'text'      => __( 'Links to the profile, activity, members, groups, messages and notifications: a side menu on large screens, a bottom tab bar on phones.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'eicon-nav-menu', 'category' => 'buddy-builder-general-elements' ],
			],
			'group-rail'           => [
				'title'     => __( 'Group rail', 'stax-buddy-builder' ),
				'text'      => __( 'A narrow rail of group photos (the member\'s groups or the most active ones), with the current group marked.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'eicon-navigation-vertical', 'category' => 'buddy-builder-general-elements' ],
			],
			'member-list'          => [
				'title'     => __( 'Online members list', 'stax-buddy-builder' ),
				'text'      => __( 'Members with their online status, grouped Online and Offline: the members of the current group, or the most recently active members.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'eicon-person', 'category' => 'buddy-builder-general-elements' ],
			],
			'highlights-rail'      => [
				'title'     => __( 'Highlights rail', 'stax-buddy-builder' ),
				'text'      => __( 'A row of round bubbles: the displayed member\'s groups or friends, or active members.', 'stax-buddy-builder' ),
				'elementor' => [ 'icon' => 'eicon-slider-push', 'category' => 'buddy-builder-general-elements' ],
			],
			// Features of BuddyBuilder Pro (Community pages screen only).
			'card-designer'        => [
				'title' => __( 'Card designer', 'stax-buddy-builder' ),
				'text'  => __( 'Design the member, group and activity cards of your directories and feeds, plus the Compact, Cover, Boxed and Post card styles.', 'stax-buddy-builder' ),
			],
			'split-header'         => [
				'title' => __( 'Split the header into parts', 'stax-buddy-builder' ),
				'text'  => __( 'Turn the Member header into its parts (photo, name, badges, headline, stats, buttons) and arrange them freely, starting from the same look.', 'stax-buddy-builder' ),
			],
			'register-group-pages' => [
				'title' => __( 'Register and Create a group pages', 'stax-buddy-builder' ),
				'text'  => __( 'Build the registration form and the Create a group steps with BuddyBuilder elements.', 'stax-buddy-builder' ),
			],
			'variations'           => [
				'title' => __( 'Layouts per member type', 'stax-buddy-builder' ),
				'text'  => __( 'A different layout per member type, role, group type or group.', 'stax-buddy-builder' ),
			],
			'live'                 => [
				'title' => __( 'Live notifications and messages', 'stax-buddy-builder' ),
				'text'  => __( 'New notifications and messages appear without reloading the page.', 'stax-buddy-builder' ),
			],
			'ads'                  => [
				'title' => __( 'Ads Manager', 'stax-buddy-builder' ),
				'text'  => __( 'Place banners in the activity stream and the members and groups directories.', 'stax-buddy-builder' ),
			],
			'birthday'             => [
				'title' => __( 'Birthdays', 'stax-buddy-builder' ),
				'text'  => __( 'Show the upcoming birthdays of your members, from a date profile field.', 'stax-buddy-builder' ),
			],
			'link-preview'         => [
				'title' => __( 'Link Preview', 'stax-buddy-builder' ),
				'text'  => __( 'Activity updates with a link show a preview of the page, fetched by your own server.', 'stax-buddy-builder' ),
			],
			'menus'                => [
				'title' => __( 'Community links in menus', 'stax-buddy-builder' ),
				'text'  => __( 'Add BuddyPress links (profile, messages, notifications and more) to your WordPress menus.', 'stax-buddy-builder' ),
			],
		];

		if ( $styles ) {
			$features['styles'] = [
				'title' => __( 'More community styles', 'stax-buddy-builder' ),
				/* translators: %s: comma separated style names */
				'text'  => sprintf( __( 'Complete styles for every community page: %s.', 'stax-buddy-builder' ), implode( ', ', $styles ) ),
			];
		}

		/**
		 * Filters the BuddyBuilder Pro features shown in the free plugin.
		 *
		 * @param array $features id => [ title, text, elementor? ].
		 */
		return (array) apply_filters( 'buddy_builder/upsell/features', $features );
	}

	/**
	 * Features for the scripts: id => [ title, text, url ].
	 *
	 * @param string $medium utm_medium.
	 * @return array
	 */
	private static function script_data( $medium = 'panel' ) {
		$data = [];

		foreach ( self::features() as $id => $feature ) {
			$data[ $id ] = [
				'title' => $feature['title'],
				'text'  => $feature['text'],
				'url'   => self::url( $id, $medium ),
			];
		}

		return [
			'features' => $data,
			'labels'   => [
				'pro'   => __( 'Pro', 'stax-buddy-builder' ),
				'cta'   => __( 'See BuddyBuilder Pro plans', 'stax-buddy-builder' ),
				'close' => __( 'Close', 'stax-buddy-builder' ),
				'tab'   => __( '(opens in a new tab)', 'stax-buddy-builder' ),
			],
			'ajax'     => admin_url( 'admin-ajax.php' ),
		];
	}

	/**
	 * Enqueue the shared popover script and styles.
	 *
	 * @param string $context admin or elementor.
	 */
	private static function enqueue( $context ) {
		wp_enqueue_style( 'bpb-upsell', BPB_ASSETS_URL . 'css/upsell.css', [], BPB_VERSION );
		wp_enqueue_script( 'bpb-upsell', BPB_ASSETS_URL . 'js/upsell.js', [], BPB_VERSION, true );
		wp_localize_script( 'bpb-upsell', 'bpbUpsell', array_merge( self::script_data(), [ 'context' => $context ] ) );
	}

	/**
	 * Elementor editor: popover script.
	 */
	public static function elementor_assets() {
		if ( self::enabled() ) {
			self::enqueue( 'elementor' );
		}
	}

	/**
	 * BuddyBuilder screens: popover script and, with the notice, its dismiss script.
	 */
	public static function admin_assets() {
		if ( self::enabled() && self::is_bpb_screen() ) {
			self::enqueue( 'admin' );
		}
	}

	/**
	 * Block editor of a BuddyBuilder layout: "Split into parts (Pro)" on the Member header, locked (the popover script
	 * opens its description; BuddyBuilder Pro has the real action there).
	 */
	public static function block_editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! self::enabled() || ! $screen || \Buddy_Builder\Templates\PostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		self::enqueue( 'admin' );
		wp_enqueue_script( 'bpb-upsell-blocks', BPB_ASSETS_URL . 'js/upsell-blocks.js', [ 'bpb-upsell', 'wp-hooks', 'wp-compose', 'wp-element', 'wp-components', 'wp-block-editor' ], BPB_VERSION, true );
		wp_localize_script( 'bpb-upsell-blocks', 'bpbUpsellBlocks', self::split_data() );
	}

	/**
	 * Elementor, Member header widget: "Split into parts (Pro)", locked: the line, a disabled button and the link to
	 * the Pro plans (BuddyBuilder Pro has the real action there).
	 *
	 * @param \Elementor\Widget_Base $element Member header widget.
	 */
	public static function elementor_split( $element ) {
		$features = self::enabled() ? self::features() : [];
		$split    = self::split_data();

		if ( empty( $features[ $split['feature'] ] ) ) {
			return;
		}

		$pro = __( 'Pro', 'stax-buddy-builder' );

		$element->start_controls_section( 'bpb_split_pro', [ 'label' => $split['label'] . ' (' . $pro . ')' ] );
		$element->add_control(
			'bpb_split_pro_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw'  => '<p class="elementor-descriptor">' . esc_html( $features[ $split['feature'] ]['text'] ) . '</p>'
					. '<p style="margin:10px 0"><button type="button" class="elementor-button elementor-button-default" disabled aria-disabled="true" style="cursor:not-allowed;opacity:.6"><i class="eicon-lock" aria-hidden="true"></i> ' . esc_html( $split['label'] ) . '</button> <span class="bpb-pro-tag">' . esc_html( $pro ) . '</span></p>'
					. '<a href="' . esc_url( self::url( $split['feature'], 'elementor' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'See BuddyBuilder Pro plans', 'stax-buddy-builder' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span></a>',
			]
		);
		$element->end_controls_section();
	}

	/**
	 * The locked "Split into parts" action: the element it sits on, its feature id and its label.
	 *
	 * @return array
	 */
	private static function split_data() {
		return [
			'block'   => 'buddybuilder/member-header',
			'feature' => 'split-header',
			'label'   => __( 'Split into parts', 'stax-buddy-builder' ),
		];
	}

	/**
	 * Elementor: the Pro elements as locked promotion items (Elementor greys them out; nothing can be dropped).
	 *
	 * @param array $settings Editor settings.
	 * @return array
	 */
	public static function elementor_promotions( $settings ) {
		if ( ! self::enabled() || ! is_array( $settings ) ) {
			return $settings;
		}

		$type  = \Buddy_Builder\Integrations\Elementor\EditorPreview::edited_type();
		$items = [];

		foreach ( self::features() as $id => $feature ) {
			if ( empty( $feature['elementor'] ) ) {
				continue;
			}

			$elementor = $feature['elementor'];

			if ( ! empty( $elementor['templates'] ) && ! in_array( $type, (array) $elementor['templates'], true ) ) {
				continue;
			}

			// The panel prints the title as HTML: the badge also tells the script which feature was clicked.
			$items[] = [
				'name'       => self::ELEMENTOR_PREFIX . $id,
				'title'      => esc_html( $feature['title'] ) . ' <span class="bpb-pro-tag" data-bpb-pro="' . esc_attr( $id ) . '">' . esc_html__( 'Pro', 'stax-buddy-builder' ) . '</span>',
				'icon'       => $elementor['icon'],
				'categories' => wp_json_encode( [ $elementor['category'] ] ),
			];
		}

		if ( $items ) {
			$settings['promotionWidgets'] = array_merge( isset( $settings['promotionWidgets'] ) ? (array) $settings['promotionWidgets'] : [], $items );
		}

		return $settings;
	}

	/** Features shown first on the Community pages screen; the others wait behind "See all in Pro". */
	const HIGHLIGHTS = [ 'styles', 'card-designer', 'split-header', 'variations' ];

	/**
	 * Community pages screen: a short teaser of BuddyBuilder Pro (a few highlights, the rest behind "See all in
	 * Pro"), greyed out with a "Pro" badge. Each opens a short description with a link (without JavaScript, the link
	 * itself).
	 */
	public static function render_features() {
		if ( ! self::enabled() ) {
			return;
		}

		$features = self::features();
		$first    = array_filter( array_replace( array_fill_keys( self::HIGHLIGHTS, null ), array_intersect_key( $features, array_flip( self::HIGHLIGHTS ) ) ) );
		$rest     = array_diff_key( $features, $first );

		echo '<section class="bpb-upsell" aria-labelledby="bpb-upsell-title">';
		echo '<h2 id="bpb-upsell-title" class="bpb-upsell__title">' . esc_html__( 'More with BuddyBuilder Pro', 'stax-buddy-builder' ) . '</h2>';
		self::render_feature_list( $first );

		if ( $rest ) {
			echo '<details class="bpb-upsell__more"><summary>' . esc_html__( 'See all in Pro', 'stax-buddy-builder' ) . '</summary>';
			self::render_feature_list( $rest );
			echo '</details>';
		}

		echo '</section>';
	}

	/**
	 * One list of Pro feature chips.
	 *
	 * @param array $features id => feature.
	 */
	private static function render_feature_list( array $features ) {
		echo '<ul class="bpb-upsell__list">';

		foreach ( $features as $id => $feature ) {
			printf(
				'<li><a class="bpb-upsell__item" href="%1$s" target="_blank" rel="noopener" data-bpb-pro="%2$s" aria-haspopup="dialog">%3$s <span class="bpb-pro-tag">%4$s</span><span class="screen-reader-text"> %5$s</span></a></li>',
				esc_url( self::url( $id ) ),
				esc_attr( $id ),
				esc_html( $feature['title'] ),
				esc_html__( 'Pro', 'stax-buddy-builder' ),
				esc_html__( '(opens in a new tab)', 'stax-buddy-builder' )
			);
		}

		echo '</ul>';
	}

	/**
	 * Whether the current admin screen is a BuddyBuilder screen.
	 *
	 * @return bool
	 */
	private static function is_bpb_screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		return '' !== $page && 0 === strpos( $page, BPB_ADMIN_PREFIX );
	}

	/**
	 * Number of community page types that use a BuddyBuilder layout with content.
	 *
	 * @return int
	 */
	public static function pages_built() {
		$count = 0;

		foreach ( array_keys( Registry::all() ) as $type ) {
			if ( Registry::is_populated( $type ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Start the notice delay when the first BuddyBuilder layout goes live (the setup's "Use these", or any
	 * assignment on Community pages). Later assignments keep the first time.
	 *
	 * @param string $type Template type.
	 * @param array  $old  Previous [ builder, id ].
	 * @param array  $new  New [ builder, id ].
	 */
	public static function layout_live( $type, $old, $new ) {
		if ( ! empty( $new['id'] ) && ! get_option( self::SINCE_OPTION ) ) {
			update_option( self::SINCE_OPTION, time(), false );
		}
	}

	/**
	 * Whether the notice delay is over: DELAY_DAYS after the first live layout. Sites updated from 1.x have no
	 * such time: the delay starts the first time the milestone is reached.
	 *
	 * @return bool
	 */
	private static function delay_over() {
		$since = (int) get_option( self::SINCE_OPTION );

		if ( ! $since ) {
			update_option( self::SINCE_OPTION, time(), false );

			return false;
		}

		return time() >= $since + self::DELAY_DAYS * DAY_IN_SECONDS;
	}

	/**
	 * Whether the notice shows to the current user: Pro not active, an admin who has not dismissed it, on a
	 * BuddyBuilder screen, at least MILESTONE community pages built with BuddyBuilder (never on fresh installs), and
	 * DELAY_DAYS after the first layout went live (never right after the setup).
	 *
	 * @return bool
	 */
	public static function notice_due() {
		return self::enabled()
			&& current_user_can( 'manage_options' )
			&& self::is_bpb_screen()
			&& ! get_user_meta( get_current_user_id(), self::DISMISSED_META, true )
			&& self::pages_built() >= self::MILESTONE
			&& self::delay_over();
	}

	/**
	 * The notice (once: dismissing it hides it for good).
	 */
	public static function notice() {
		if ( ! self::notice_due() ) {
			return;
		}

		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION ), self::DISMISS_ACTION );
		$built   = self::pages_built();

		printf(
			'<div class="notice notice-info bpb-upsell-notice" data-bpb-dismiss="%1$s"><p><strong>%2$s</strong> %3$s</p><p><a class="button button-primary" href="%4$s" target="_blank" rel="noopener">%5$s<span class="screen-reader-text"> %6$s</span></a> <a class="button-link bpb-upsell-notice__dismiss" href="%7$s">%8$s</a></p></div>',
			esc_attr( wp_create_nonce( self::DISMISS_ACTION ) ),
			/* translators: %d: number of community pages */
			esc_html( sprintf( _n( 'You have built %d community page with BuddyBuilder.', 'You have built %d community pages with BuddyBuilder.', $built, 'stax-buddy-builder' ), $built ) ),
			esc_html__( 'BuddyBuilder Pro adds more styles, member and group cards, live notifications and messages, ads, birthdays and layouts per member type.', 'stax-buddy-builder' ),
			esc_url( self::url( 'milestone', 'notice' ) ),
			esc_html__( 'See BuddyBuilder Pro', 'stax-buddy-builder' ),
			esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ),
			esc_url( $dismiss ),
			esc_html__( 'Dismiss', 'stax-buddy-builder' )
		);
	}

	/**
	 * Dismiss the notice for the current user (AJAX, or admin-post without JavaScript).
	 */
	public static function dismiss() {
		check_ajax_referer( self::DISMISS_ACTION );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'stax-buddy-builder' ), 403 );
		}

		update_user_meta( get_current_user_id(), self::DISMISSED_META, 1 );

		if ( wp_doing_ajax() ) {
			wp_send_json_success();
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . 'settings' ) );
		exit;
	}
}
