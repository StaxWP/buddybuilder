<?php
/**
 * BuddyBuilder admin app (2.0): one shell for every BuddyBuilder screen, in the same family as SQ Panel and
 * Visibility Logic 3.0. App header (logo, version, Free/Pro status, Docs, Support, Get Pro), tabs with icons
 * (Community pages, Features (Pro), Settings, Help), the screen title with its actions, the screen and an optional
 * sidebar.
 *
 * Settings is one screen of cards: BuddyBuilder's own (builders, sample member, setup) and the ones add-ons give
 * through `buddy_builder/admin/settings_cards` (BuddyBuilder Pro: License, Agents). Screens that used to be separate
 * pages (Pro License, Pro Agents) open their card: their slugs stay registered and redirect, so bookmarks, notices
 * and form handlers keep working.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Upsell\Upsell;

defined( 'ABSPATH' ) || die();

/**
 * Admin app shell.
 */
final class App {

	/** Help and status screen (Compat\StatusPage). */
	const HELP = 'buddy-builder-status';

	/** Docs. */
	const DOCS_URL = 'https://staxwp.com/docs/buddybuilder/';

	/** Free support. */
	const SUPPORT_URL = 'https://wordpress.org/support/plugin/stax-buddy-builder/';

	/** Menu and app logo (white mark, drawn on the accent color in the app). */
	const LOGO = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNTM2IDE1MzYiPjx0aXRsZT5icDwvdGl0bGU+PHBhdGggZD0iTTc1OC43MiwyMjVjMi45MSwxLjA5LDE0LjUzLDQuNzIsMjUuNDIsOC4zNSw4My41MiwyNS43OCwxNjQuODYsOTUuNSwyMDQuODEsMTc1LjM4LDUwLjExLDk5LjEzLDUwLjExLDIyMS4xMywwLDMyMC42Mi02LjksMTMuNDQtMTIuMzUsMjUuNDItMTIuMzUsMjYuMTQsMCwyLjU0LDI1Ljc4LTUuMDgsNDguNjYtMTQuODksODMuNTItMzQuODYsMTQ3LjA3LTExMCwxNjkuNTgtMjAwLjgsNi41NC0yNS43OCw3LjYzLTM4LjEzLDYuMTctNzkuODgtMS40NS00Mi44NS0zLjI3LTUzLTEyLjcxLTgwLjYxLTM0Ljg2LTEwMC4yMi0xMjIuNzQtMTc3LjU2LTIyMC43OS0xOTUtNTIuNjUtOS4wOC05OS41LTYuMTctMTQ1LjI1LDguNzFDNzg2LjY4LDIwNC42OCw3NTEuMSwyMjIuNDgsNzU4LjcyLDIyNVoiIHN0eWxlPSJmaWxsOiNmZmYiLz48cGF0aCBkPSJNMzk0LjUsNjU4LjU2QzQzNi4yNiw3ODQuMiw1NDMuMzgsODYzLDY3Mi4zLDg2M2M0OC4zLDAsODIuMDctNi45LDEyMS42NS0yNS40Miw4MS0zNy43NiwxMzMuNjMtOTcuNjcsMTYwLjg3LTE4My4zNywxMC4xNy0zMi42OCwxMy44LTk2Ljk1LDcuMjYtMTMzLjYyQzk0Mi40Nyw0MDguNzUsODU4LjU4LDMxOC4zMyw3NDYsMjg3LjgzYy0yNS40Mi02LjktOTQuNDItMTAuNTMtMTE4Ljc1LTUuODEtMTE0LjM5LDIwLjctMjAzLjcyLDEwMS4zMS0yMzYsMjEyLjQyLTYuOSwyNC42OS04LjM1LDM3LTgsNzkuODhDMzgzLjI0LDYyMC44LDM4NC4zMyw2MjguMDYsMzk0LjUsNjU4LjU2WiIgc3R5bGU9ImZpbGw6I2ZmZiIvPjxwYXRoIGQ9Ik0xMTk5LjkzLDc4Ni43NGMtMTYuMzQtNS4wOC0zOS4yMi04LjcxLTYzLjU1LTkuOGwtMzguNDktMi4xOC0yNi4xNSwyOS43N2MtMTQuNTMsMTYuMzQtMjYuMTUsMzAuODYtMjYuMTUsMzIuNjgsMCwxLjQ1LDYuOSw1LjA4LDE1LjYyLDcuNjMsOTEuNTEsMjcuMjMsMTY5Ljk1LDExOC43MywyMDUuOSwyNDAsOC4zNSwyOCwyMS43OSw5OC40LDIxLjc5LDExMy42NSwwLDEuMDksMjguNjksMi4xOCw2My41NSwyLjE4LDU1LjIsMCw2My45MS0uNzMsNjYuMDktNS44MSwzLjYzLTkuNDQtNS44MS0xMTIuOTMtMTMuNDQtMTUwLjMyQzEzNzUuNjksOTA0Ljc1LDEzMDkuMjQsODIxLjIzLDExOTkuOTMsNzg2Ljc0WiIgc3R5bGU9ImZpbGw6I2ZmZiIvPjxwYXRoIGQ9Ik0xMTEzLjUxLDk1MS4yMmMtNTEuOTMtNDkuMzgtMTIzLjQ3LTgyLjA2LTE5OC42NC05MS41bC0yNy4yNC0zLjI3TDgxNSw5MjhjLTM5Ljk0LDM5LjIxLTg3Ljg4LDg2LjA1LTEwNi40LDEwMy44NWwtMzMuNDEsMzIuNjgtOTMuNjktOTIuNTlDNDUzLjY5LDg0NS4yLDQ2OC45NCw4NTYuNDUsNDMzLjcxLDg2MC4wOCwyNTEuNDIsODc5LjY5LDE0MC42NiwxMDAxLjMzLDExOS4yNCwxMjA1Yy00LjM2LDM5Ljk0LTIuOTEsMTQwLjE2LDIuMTgsMTQ3Ljc4LDEuNDUsMi45MSwxNjMuNDEsNCw1NTMuNDIsNGg1NTEuNmwyLjE4LTExLjI2YzQuMzYtMjEuNzksMi4xOC0xMjguNTQtMy4yNy0xNjQuODVDMTIxMC40NiwxMDgyLjMsMTE3NS4yNCwxMDA5LjY4LDExMTMuNTEsOTUxLjIyWiIgc3R5bGU9ImZpbGw6I2ZmZiIvPjwvc3ZnPg==';

	/** Stroke icons (24x24, Lucide-style), shared by the app, the settings cards and add-ons. */
	const ICONS = [
		'layout'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
		'puzzle'   => '<path d="M19.4 13.6a2 2 0 1 0 0-3.2V6a1 1 0 0 0-1-1h-4.4a2 2 0 1 0-3.2 0H6.4a1 1 0 0 0-1 1v4.4a2 2 0 1 1 0 3.2V18a1 1 0 0 0 1 1h4.4a2 2 0 1 1 3.2 0h4.4a1 1 0 0 0 1-1z"/>',
		'gear'     => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/>',
		'help'     => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>',
		'key'      => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
		'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
		'blocks'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
		'refresh'  => '<path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15"/>',
		'sparkles' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
		'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'alert'    => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
		'book'     => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
		'chat'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'star'     => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
		'zap'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
		'ext'      => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>',
		'box'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/>',
	];

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		// Before the menu is built and anything prints (admin.php fires admin_init first).
		add_action( 'admin_init', [ __CLASS__, 'legacy_redirect' ] );
	}

	/**
	 * Print an icon.
	 *
	 * @param string $name  Key of self::ICONS.
	 * @param string $class Extra class.
	 */
	public static function icon( $name, $class = '' ) {
		$paths = isset( self::ICONS[ $name ] ) ? self::ICONS[ $name ] : self::ICONS['box'];

		echo '<svg class="bpb-app-icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup from self::ICONS.
	}

	/**
	 * Current admin page slug.
	 *
	 * @return string
	 */
	public static function page() {
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Settings screen URL, optionally opened on one card.
	 *
	 * @param string $card Card id.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	public static function settings_url( $card = '', array $args = [] ) {
		$url = add_query_arg( $args, admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . Options::SLUG ) );

		return $card ? $url . '#bpb-card-' . sanitize_key( $card ) : $url;
	}

	/**
	 * BuddyBuilder Pro status for the header and the sidebar. Pro fills it through `buddy_builder/admin/pro_status`;
	 * `licensed` stays null when the active Pro does not say (Pro 1.x).
	 *
	 * @return array active, licensed (bool|null), expired, label, manage_url
	 */
	public static function pro() {
		$active = defined( 'BPB_PRO_VERSION' ) || function_exists( 'bpb_is_pro' );

		/**
		 * Filters the BuddyBuilder Pro status shown in the app header and sidebar.
		 *
		 * @since 2.0.0
		 *
		 * @param array $status active, licensed (true, false or null when unknown), expired, label (status line), manage_url.
		 */
		$status = (array) apply_filters(
			'buddy_builder/admin/pro_status',
			[
				'active'     => $active,
				'licensed'   => null,
				'expired'    => false,
				'label'      => '',
				'manage_url' => '',
			]
		);

		return wp_parse_args(
			$status,
			[
				'active'     => $active,
				'licensed'   => null,
				'expired'    => false,
				'label'      => '',
				'manage_url' => '',
			]
		);
	}

	/**
	 * Settings cards, in order. BuddyBuilder Pro adds License and Agents.
	 *
	 * Each card: id, title, description, icon (key of self::ICONS), render (callable printing the body),
	 * priority, page (optional: slug of the old screen that now opens this card).
	 *
	 * @return array
	 */
	public static function cards() {
		/**
		 * Filters the cards of BuddyBuilder > Settings.
		 *
		 * @since 2.0.0
		 *
		 * @param array $cards Cards (id, title, description, icon, render, priority, page).
		 */
		$cards = (array) apply_filters( 'buddy_builder/admin/settings_cards', [] );

		$cards = array_filter(
			$cards,
			static function ( $card ) {
				return is_array( $card ) && ! empty( $card['id'] ) && isset( $card['render'] ) && is_callable( $card['render'] );
			}
		);

		uasort(
			$cards,
			static function ( $a, $b ) {
				return ( isset( $a['priority'] ) ? (int) $a['priority'] : 10 ) - ( isset( $b['priority'] ) ? (int) $b['priority'] : 10 );
			}
		);

		return array_values( $cards );
	}

	/**
	 * Old screens that became a settings card open the card (query args are kept).
	 */
	public static function legacy_redirect() {
		$page = self::page();

		if ( '' === $page || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}

		foreach ( self::cards() as $card ) {
			if ( ! empty( $card['page'] ) && $card['page'] === $page ) {
				$args = array_diff_key( wp_unslash( $_GET ), [ 'page' => 1 ] ); // phpcs:ignore WordPress.Security.NonceVerification
				wp_safe_redirect( self::settings_url( $card['id'], array_map( 'sanitize_text_field', array_filter( $args, 'is_scalar' ) ) ) );
				exit;
			}
		}
	}

	/**
	 * App tabs: the screens of the BuddyBuilder menu (folded ones left out) and Help.
	 *
	 * @return array Items: slug, name, link, query, priority, icon, sidebar.
	 */
	public static function tabs() {
		$folded = Menu::folded();
		$tabs   = [];

		foreach ( (array) apply_filters( BPB_HOOK_PREFIX . 'admin_menu', [] ) as $item ) {
			if ( empty( $item['query'] ) || isset( $folded[ $item['query'] ] ) ) {
				continue;
			}
			$tabs[] = $item;
		}

		$tabs[] = [
			'slug'     => 'status',
			'name'     => __( 'Help', 'stax-buddy-builder' ),
			'link'     => admin_url( 'admin.php?page=' . self::HELP ),
			'query'    => self::HELP,
			'priority' => 90,
			'icon'     => 'help',
		];

		usort(
			$tabs,
			static function ( $a, $b ) {
				return ( isset( $a['priority'] ) ? (int) $a['priority'] : 10 ) - ( isset( $b['priority'] ) ? (int) $b['priority'] : 10 );
			}
		);

		return $tabs;
	}

	/**
	 * The tab of the current screen (folded screens belong to the tab their menu entry stays highlighted on).
	 *
	 * @return array|null
	 */
	public static function current_tab() {
		$page   = self::page();
		$folded = Menu::folded();
		$query  = isset( $folded[ $page ] ) && self::HELP !== $page ? $folded[ $page ] : $page;

		foreach ( self::tabs() as $tab ) {
			if ( $tab['query'] === $query ) {
				return $tab;
			}
		}

		return null;
	}

	/**
	 * Screen title: its own menu name (a folded screen such as Starter kits keeps it), else the tab's.
	 *
	 * @param array|null $tab Current tab.
	 * @return string
	 */
	public static function title( $tab ) {
		foreach ( (array) apply_filters( BPB_HOOK_PREFIX . 'admin_menu', [] ) as $item ) {
			if ( isset( $item['query'], $item['name'] ) && $item['query'] === self::page() ) {
				return wp_strip_all_tags( $item['name'] );
			}
		}

		return $tab ? wp_strip_all_tags( $tab['name'] ) : __( 'BuddyBuilder', 'stax-buddy-builder' );
	}

	/**
	 * Icon of a tab (add-on tabs can set `icon` on their menu item).
	 *
	 * @param array $tab Tab.
	 * @return string
	 */
	public static function tab_icon( array $tab ) {
		if ( ! empty( $tab['icon'] ) && isset( self::ICONS[ $tab['icon'] ] ) ) {
			return $tab['icon'];
		}

		$icons = [
			'settings' => 'layout',
			'modules'  => 'puzzle',
			'options'  => 'gear',
			'status'   => 'help',
		];

		return isset( $icons[ $tab['slug'] ] ) ? $icons[ $tab['slug'] ] : 'box';
	}

	/**
	 * Print a whole screen: the app around a content callback.
	 *
	 * @param callable $content Prints the screen.
	 * @param array    $args    title, sidebar.
	 */
	public static function render( callable $content, array $args = [] ) {
		self::open( $args );
		call_user_func( $content );
		self::close( $args );
	}

	/**
	 * App start: header, tabs, screen title and the main column.
	 *
	 * @param array $args title (default: the tab name), sidebar (default: the tab's), wrapper_class.
	 */
	public static function open( array $args = [] ) {
		$tab  = self::current_tab();
		$args = wp_parse_args(
			$args,
			[
				'title'         => self::title( $tab ),
				'sidebar'       => $tab && ! empty( $tab['sidebar'] ),
				'wrapper_class' => [],
			]
		);
		$pro  = self::pro();
		$page = self::page();
		$new  = '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span>';

		if ( ! $pro['active'] ) {
			$pill = [ 'free', '', __( 'Free', 'stax-buddy-builder' ) ];
		} elseif ( $pro['expired'] ) {
			$pill = [ 'warn', 'alert', __( 'Pro, license expired', 'stax-buddy-builder' ) ];
		} elseif ( true === $pro['licensed'] ) {
			$pill = [ 'ok', 'check', __( 'Pro, licensed', 'stax-buddy-builder' ) ];
		} elseif ( false === $pro['licensed'] ) {
			$pill = [ 'warn', 'lock', __( 'Pro, no license', 'stax-buddy-builder' ) ];
		} else {
			$pill = [ 'ok', 'check', __( 'Pro', 'stax-buddy-builder' ) ];
		}

		$classes = array_map( 'sanitize_html_class', array_filter( array_map( 'strval', (array) $args['wrapper_class'] ) ) );
		?>
		<div class="wrap bpb-app-wrap">
			<h1 class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: screen name */ __( 'BuddyBuilder: %s', 'stax-buddy-builder' ), $args['title'] ) ); ?></h1>
			<?php // WordPress moves notices after this line, above the app. ?>
			<hr class="wp-header-end">

			<div class="bpb-app bpb-admin <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<header class="bpb-app-header">
					<div class="bpb-app-header__bar">
						<div class="bpb-app-brand">
							<span class="bpb-app-brand__logo"><img src="<?php echo esc_attr( self::LOGO ); ?>" alt="" width="28" height="28"></span>
							<div>
								<p class="bpb-app-brand__name"><?php esc_html_e( 'BuddyBuilder', 'stax-buddy-builder' ); ?></p>
								<p class="bpb-app-brand__meta">
									<span>
										<?php
										/* translators: %s: version number. */
										echo esc_html( sprintf( __( 'Version %s', 'stax-buddy-builder' ), BPB_VERSION ) );
										if ( defined( 'BPB_PRO_VERSION' ) ) {
											echo ' &middot; ';
											/* translators: %s: version number. */
											echo esc_html( sprintf( __( 'Pro %s', 'stax-buddy-builder' ), BPB_PRO_VERSION ) );
										}
										?>
									</span>
									<?php if ( $pro['manage_url'] ) : ?>
										<a class="bpb-app-pill bpb-app-pill--<?php echo esc_attr( $pill[0] ); ?>" href="<?php echo esc_url( $pro['manage_url'] ); ?>">
									<?php else : ?>
										<span class="bpb-app-pill bpb-app-pill--<?php echo esc_attr( $pill[0] ); ?>">
									<?php endif; ?>
										<?php if ( $pill[1] ) : ?>
											<?php self::icon( $pill[1] ); ?>
										<?php endif; ?>
										<?php echo esc_html( $pill[2] ); ?>
									<?php echo $pro['manage_url'] ? '</a>' : '</span>'; ?>
								</p>
							</div>
						</div>
						<ul class="bpb-app-quick">
							<li>
								<a class="bpb-app-btn bpb-app-btn--ghost bpb-app-btn--sm" href="<?php echo esc_url( self::DOCS_URL ); ?>" target="_blank" rel="noopener">
									<?php self::icon( 'book' ); ?><?php esc_html_e( 'Docs', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
								</a>
							</li>
							<li>
								<a class="bpb-app-btn bpb-app-btn--ghost bpb-app-btn--sm" href="<?php echo esc_url( self::SUPPORT_URL ); ?>" target="_blank" rel="noopener">
									<?php self::icon( 'chat' ); ?><?php esc_html_e( 'Support', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
								</a>
							</li>
							<?php if ( Upsell::enabled() ) : ?>
								<li>
									<a class="bpb-app-btn bpb-app-btn--primary bpb-app-btn--sm" href="<?php echo esc_url( Upsell::url( 'header' ) ); ?>" target="_blank" rel="noopener">
										<?php self::icon( 'zap' ); ?><?php esc_html_e( 'Get Pro', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
									</a>
								</li>
							<?php endif; ?>
						</ul>
					</div>

					<nav class="bpb-app-nav" aria-label="<?php esc_attr_e( 'BuddyBuilder sections', 'stax-buddy-builder' ); ?>">
						<?php foreach ( self::tabs() as $item ) : ?>
							<?php $active = $tab && $item['query'] === $tab['query']; ?>
							<a href="<?php echo esc_url( $item['link'] ); ?>" class="<?php echo $active ? 'is-active' : ''; ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
								<?php self::icon( self::tab_icon( $item ) ); ?>
								<?php echo esc_html( wp_strip_all_tags( $item['name'] ) ); ?>
							</a>
						<?php endforeach; ?>
					</nav>
				</header>

				<div class="bpb-app-columns<?php echo $args['sidebar'] ? '' : ' bpb-app-columns--full'; ?>">
					<div class="bpb-app-main">
						<div class="bpb-app-title">
							<h2><?php echo esc_html( $args['title'] ); ?></h2>
							<div class="bpb-app-title__actions">
								<?php
								/**
								 * Fires next to the screen title (screen actions).
								 *
								 * @param string $page Screen slug.
								 */
								do_action( 'buddy_builder/admin/header_actions', $page );
								?>
							</div>
						</div>
		<?php
	}

	/**
	 * App end: closes the main column, prints the sidebar when the screen has one.
	 *
	 * @param array $args sidebar (default: the tab's).
	 */
	public static function close( array $args = [] ) {
		$tab     = self::current_tab();
		$sidebar = isset( $args['sidebar'] ) ? (bool) $args['sidebar'] : ( $tab && ! empty( $tab['sidebar'] ) );
		?>
					</div>
					<?php if ( $sidebar ) : ?>
						<?php self::sidebar(); ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Sidebar: Pro (offer or status), Get started, Help.
	 */
	public static function sidebar() {
		$pro = self::pro();
		$new = '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span>';
		?>
		<aside class="bpb-app-sidebar" aria-label="<?php esc_attr_e( 'About BuddyBuilder', 'stax-buddy-builder' ); ?>">
			<?php if ( ! $pro['active'] && Upsell::enabled() ) : ?>
				<div class="bpb-app-pro-card">
					<h2><?php esc_html_e( 'BuddyBuilder Pro', 'stax-buddy-builder' ); ?></h2>
					<p><?php esc_html_e( 'More for your community pages, in the same editors.', 'stax-buddy-builder' ); ?></p>
					<ul>
						<li><?php self::icon( 'check' ); ?><?php esc_html_e( 'Notifications, messages and member stats', 'stax-buddy-builder' ); ?></li>
						<li><?php self::icon( 'check' ); ?><?php esc_html_e( 'Member, group and activity cards, register form', 'stax-buddy-builder' ); ?></li>
						<li><?php self::icon( 'check' ); ?><?php esc_html_e( 'A different layout per member type or group', 'stax-buddy-builder' ); ?></li>
						<li><?php self::icon( 'check' ); ?><?php esc_html_e( 'Banners, birthdays and link previews', 'stax-buddy-builder' ); ?></li>
					</ul>
					<a class="bpb-app-btn" href="<?php echo esc_url( Upsell::url( 'sidebar' ) ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'See Pro plans', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					</a>
				</div>
			<?php elseif ( $pro['active'] ) : ?>
				<div class="bpb-app-card bpb-app-side-card">
					<h2><?php esc_html_e( 'BuddyBuilder Pro', 'stax-buddy-builder' ); ?></h2>
					<?php if ( '' !== (string) $pro['label'] ) : ?>
						<p class="bpb-app-state bpb-app-state--<?php echo true === $pro['licensed'] && ! $pro['expired'] ? 'ok' : 'warn'; ?>"><?php echo esc_html( $pro['label'] ); ?></p>
					<?php elseif ( true === $pro['licensed'] ) : ?>
						<p class="bpb-app-state bpb-app-state--ok"><?php esc_html_e( 'Active and licensed. Thank you for supporting the plugin.', 'stax-buddy-builder' ); ?></p>
					<?php elseif ( false === $pro['licensed'] ) : ?>
						<p class="bpb-app-state bpb-app-state--warn"><?php esc_html_e( 'Active, no license yet. Pro elements keep working on your pages; adding or editing them needs a license.', 'stax-buddy-builder' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Active.', 'stax-buddy-builder' ); ?></p>
					<?php endif; ?>
					<ul class="bpb-app-links">
						<?php if ( $pro['manage_url'] ) : ?>
							<li><a href="<?php echo esc_url( $pro['manage_url'] ); ?>"><?php self::icon( 'key' ); ?><?php esc_html_e( 'Manage license', 'stax-buddy-builder' ); ?></a></li>
						<?php endif; ?>
						<li><a href="https://staxwp.com/account/" target="_blank" rel="noopener"><?php self::icon( 'user' ); ?><?php esc_html_e( 'Your staxwp.com account', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></a></li>
					</ul>
				</div>
			<?php endif; ?>

			<div class="bpb-app-card bpb-app-side-card">
				<h2><?php esc_html_e( 'Get started', 'stax-buddy-builder' ); ?></h2>
				<ol class="bpb-app-steps">
					<li><?php esc_html_e( 'Pick a look for each page on Community pages.', 'stax-buddy-builder' ); ?></li>
					<li><?php esc_html_e( 'Edit it in Elementor or the block editor.', 'stax-buddy-builder' ); ?></li>
					<li><?php esc_html_e( 'Check it with "Seen by" in the editor, then press Use.', 'stax-buddy-builder' ); ?></li>
				</ol>
			</div>

			<div class="bpb-app-card bpb-app-side-card">
				<h2><?php esc_html_e( 'Help', 'stax-buddy-builder' ); ?></h2>
				<ul class="bpb-app-links">
					<li><a href="<?php echo esc_url( self::DOCS_URL ); ?>" target="_blank" rel="noopener"><?php self::icon( 'book' ); ?><?php esc_html_e( 'Documentation', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></a></li>
					<li><a href="<?php echo esc_url( self::SUPPORT_URL ); ?>" target="_blank" rel="noopener"><?php self::icon( 'chat' ); ?><?php esc_html_e( 'Support forum', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></a></li>
					<li><a href="<?php echo esc_url( self::SUPPORT_URL . 'reviews/#new-post' ); ?>" target="_blank" rel="noopener"><?php self::icon( 'star' ); ?><?php esc_html_e( 'Rate the plugin', 'stax-buddy-builder' ); ?><?php echo $new; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></a></li>
				</ul>
			</div>
		</aside>
		<?php
	}

	/**
	 * Print one settings card.
	 *
	 * @param array $card Card (see cards()).
	 */
	public static function card( array $card ) {
		$id = 'bpb-card-' . sanitize_key( $card['id'] );
		?>
		<section class="bpb-app-card bpb-app-setting" id="<?php echo esc_attr( $id ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-title">
			<header class="bpb-app-setting__head">
				<span class="bpb-app-setting__icon"><?php self::icon( isset( $card['icon'] ) ? $card['icon'] : 'box' ); ?></span>
				<div>
					<h2 id="<?php echo esc_attr( $id ); ?>-title">
						<?php echo esc_html( $card['title'] ); ?>
						<?php if ( ! empty( $card['badge'] ) ) : ?>
							<span class="bpb-app-badge"><?php echo esc_html( $card['badge'] ); ?></span>
						<?php endif; ?>
					</h2>
					<?php if ( ! empty( $card['description'] ) ) : ?>
						<p><?php echo esc_html( $card['description'] ); ?></p>
					<?php endif; ?>
				</div>
			</header>
			<div class="bpb-app-setting__body">
				<?php call_user_func( $card['render'] ); ?>
			</div>
		</section>
		<?php
	}
}
