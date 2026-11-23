<?php

use Buddy_Builder\Templates\Dispatcher;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;

/**
 * Render the layout assigned to a template type, with its builder (block layout or Elementor template).
 *
 * @param string $type    Template type (member-profile, members-directory, group-profile, groups-directory,
 *                        sitewide-activity, register-page, or a Pro type).
 * @param array  $context Optional context passed to the elements.
 *
 * @return string
 * @since 2.0.0
 */
function bpb_render_template( $type, $context = [] ) {
	return Dispatcher::render( (string) $type, (array) $context );
}

/**
 * Render one loop item layout (members, groups, activity loops).
 *
 * @param string $type      Item template type.
 * @param string $shortcode Filtered 1.x shortcode string (non-empty keeps the 1.x Elementor path).
 * @param array  $context   Item context.
 *
 * @return string
 * @since 2.0.0
 */
function bpb_render_loop_item( $type, $shortcode, $context = [] ) {
	return Dispatcher::render_item( (string) $type, $shortcode, (array) $context );
}

/**
 * Get shortcode string
 *
 * @param $template
 * @param bool     $preview
 *
 * @return bool|string
 */
function bpb_get_shortcode_str( $template, $preview = false ) {
	if ( ! is_int( $template ) ) {
		$settings    = bpb_get_settings();
		$template_id = isset( $settings['templates'][ $template ] ) ? $settings['templates'][ $template ] : 0;
	} else {
		$template_id = $template;
	}

	if ( ! get_post( $template_id ) ) {
		return '';
	}

	// Block layouts: [bpb-layout] for 1.x callers that do_shortcode() the result ('' for item types).
	if ( PostType::POST_TYPE === get_post_type( $template_id ) ) {
		return is_int( $template ) ? Dispatcher::legacy_shortcode( '', (int) $template_id ) : Dispatcher::legacy_shortcode( (string) $template );
	}

	$shortcode_name = 'elementor-template';

	if ( $preview ) {
		$shortcode_name .= '-preview';
	}

	return (bool) $template_id ? '[' . $shortcode_name . ' id="' . $template_id . '"]' : false;
}

/**
 * Check if template is populated
 *
 * @param $template
 *
 * @return bool
 */
function bpb_is_template_populated( $template = null ) {
	if ( ! $template ) {
		return false;
	}

	if ( ! is_int( $template ) ) {
		/**
		 * Short-circuits the "has a layout" check of a template type (layout previews, Pro layout variations).
		 *
		 * @since 2.0.0
		 *
		 * @param bool|null $pre  Null to run the normal check.
		 * @param string    $type Template type.
		 */
		$pre = apply_filters( 'buddy_builder/is_template_populated/pre', null, (string) $template );

		if ( null !== $pre ) {
			return (bool) $pre;
		}

		// A block layout of a type block layouts do not support here (a BuddyBuilder Pro page type while Pro is off:
		// register, group creation, cards) is not used, so BuddyPress shows its own page or loop item.
		if ( Registry::BLOCKS === Registry::get( (string) $template )['builder'] && ! isset( \Buddy_Builder\Templates\Types::for_blocks()[ $template ] ) ) {
			return false;
		}

		$settings    = bpb_get_settings();
		$template_id = isset( $settings['templates'][ $template ] ) ? $settings['templates'][ $template ] : 0;
	} else {
		$template_id = $template;
	}

	if ( ! get_post( $template_id ) ) {
		return false;
	}

	return bpb_is_template_id_populated( $template_id );
}

/**
 * Check if current template is populated
 *
 * @return bool
 */
function bpb_is_current_template_populated() {
	$settings = bpb_get_settings();
	$settings = $settings['templates'];

	if ( ! function_exists( 'bp_is_active' ) ) {
		return false;
	}

	// Members directory.
	if ( $settings['members-directory'] && bp_is_members_directory() ) {
		return bpb_is_template_id_populated( $settings['members-directory'] );
	}

	// Groups directory.
	if ( $settings['groups-directory'] && ! empty( bp_is_active( 'groups' ) ) && bp_is_groups_directory() ) {
		return bpb_is_template_id_populated( $settings['groups-directory'] );
	}

	// Member profile.
	if ( $settings['member-profile'] && bp_is_user() ) {
		if ( ( ! isset( $settings['sitewide-activity-item'] ) || ! $settings['sitewide-activity-item'] ) &&
			 bp_current_component() === 'activity' &&
			 is_numeric( bp_current_action() ) ) {
			return false;
		}

		return bpb_is_template_id_populated( $settings['member-profile'] );
	}

	// Group profile.
	if ( $settings['group-profile'] && ! empty( bp_is_active( 'groups' ) ) && bp_is_single_item() && bp_is_groups_component() ) {
		return bpb_is_template_id_populated( $settings['group-profile'] );
	}

	// Site-wide activity.
	if ( $settings['sitewide-activity'] && ! empty( bp_is_active( 'activity' ) ) && bp_is_activity_directory() ) {
		return bpb_is_template_id_populated( $settings['sitewide-activity'] );
	}

	// Register page.
	if ( $settings['register-page'] && 'register' === bp_current_component() ) {
		return bpb_is_template_id_populated( $settings['register-page'] );
	}

	return false;
}

/**
 * Check if template ID is populated
 *
 * @param $id
 *
 * @return bool
 */
function bpb_is_template_id_populated( $id ) {
	return Registry::is_post_populated( (int) $id );
}

/**
 * Get available plugin template types
 *
 * @return array
 */
function bpb_get_template_types() {
	$templates_types = apply_filters(
		'buddy_builder/get_template_types',
		[
			'member-profile'    => [
				'name'  => __( 'Member Profile', 'stax-buddy-builder' ),
				'order' => 1,
			],
			'members-directory' => [
				'name'  => __( 'Members Directory', 'stax-buddy-builder' ),
				'order' => 2,
			],
			'group-profile'     => [
				'name'  => __( 'Group Profile', 'stax-buddy-builder' ),
				'order' => 4,
			],
			'groups-directory'  => [
				'name'  => __( 'Groups Directory', 'stax-buddy-builder' ),
				'order' => 5,
			],
			'sitewide-activity' => [
				'name'  => __( 'Site-Wide Activity', 'stax-buddy-builder' ),
				'order' => 8,
			],
		]
	);

	uasort(
		$templates_types,
		static function ( $a, $b ) {
			return $a['order'] - $b['order'];
		}
	);

	return $templates_types;
}

/**
 * Get saved templates settings
 *
 * @return array
 */
function bpb_get_settings() {
	$options = get_option( 'bpb_settings' );

	$defaults = [
		'templates' => [
			'member-profile'    => 0,
			'members-directory' => 0,
			'group-profile'     => 0,
			'groups-directory'  => 0,
			'sitewide-activity' => 0,
			'register-page'     => 0,
		],
	];

	$defaults = apply_filters( 'buddy_builder/default_settings', $defaults );

	if ( empty( $options ) ) {
		return apply_filters( 'buddy_builder/settings', $defaults );
	}

	foreach ( $defaults as $section => $default ) {
		if ( ! isset( $options[ $section ] ) ) {
			continue;
		}

		if ( is_array( $default ) ) {
			foreach ( $default as $key => $item ) {
				if ( is_array( $item ) ) {
					foreach ( $item as $item_key => $item_value ) {
						if ( isset( $options[ $section ][ $key ][ $item_key ] ) ) {
							$defaults[ $section ][ $key ][ $item_key ] = $options[ $section ][ $key ][ $item_key ];
						}
					}
				} elseif ( isset( $options[ $section ][ $key ] ) ) {
					$defaults[ $section ][ $key ] = $options[ $section ][ $key ];
				}
			}
		} else {
			$defaults[ $section ] = $options[ $section ];
		}
	}

	/**
	 * Filters the settings (2.0: the active template id per type, Elementor or block layout).
	 *
	 * @param array $defaults Settings.
	 */
	return apply_filters( 'buddy_builder/settings', $defaults );
}

/**
 * Bulk update assigned templates
 *
 * @param array $data
 */
function bpb_bulk_save_settings( $data ) {
	$existing = bpb_get_settings();

	foreach ( (array) $data as $slug => $template_id ) {
		if ( ! isset( $existing['templates'][ $slug ] ) ) {
			continue;
		}

		$template_id = (int) $template_id;

		Registry::set( $slug, Registry::builder_of( $template_id ), $template_id );
	}
}

/**
 * Nonce action of the "Create new" links that make Elementor assign the new template to a page type (the nonce is sent
 * as _bpb_set_as_active and checked in Library\Documents\BuddyPress::save_template_type()).
 *
 * @param string $template_slug Template type.
 *
 * @return string
 */
function bpb_set_as_active_nonce_action( $template_slug ) {
	return 'bpb_set_as_active_' . $template_slug;
}

/**
 * Set active template ID for a specific template type
 *
 * @param $template_slug
 * @param $id
 *
 * @return bool
 */
function bpb_set_template_id( $template_slug, $id ) {
	// Site-wide assignment: only administrators choose the layout of a BuddyPress page.
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	$settings = bpb_get_settings();

	if ( ! isset( $settings['templates'][ $template_slug ] ) || ! in_array( get_post_type( $id ), [ 'elementor_library', PostType::POST_TYPE ], true ) ) {
		return false;
	}

	return Registry::set( $template_slug, Registry::builder_of( $id ), (int) $id );
}

/**
 * Whether the Elementor editor shows this BuddyBuilder template with real data (a preview member, group, the
 * directory, the sign-up form or the group creation step), so widgets render their live markup (2.0, decision 4);
 * otherwise they print bpb_editor_placeholder().
 *
 * @return bool
 */
function bpb_real_preview() {
	if ( ! class_exists( '\\Buddy_Builder\\Templates\\PreviewContext' ) ) {
		return false;
	}

	$ctx = \Buddy_Builder\Templates\PreviewContext::ctx();

	if ( empty( $ctx['preview'] ) || empty( $ctx['elementor'] ) ) {
		return false;
	}

	if ( 'member-profile' === $ctx['type'] || 'members-directory-item' === $ctx['type'] ) {
		return ! empty( $ctx['user_id'] );
	}

	if ( 'group-profile' === $ctx['type'] || 'groups-directory-item' === $ctx['type'] ) {
		return ! empty( $ctx['group_id'] );
	}

	return 'sitewide-activity-item' !== $ctx['type'] || ! empty( $ctx['activity_id'] );
}

/**
 * Check if it's elementor editor or preview mode
 *
 * @return bool
 */
function bpb_is_elementor_editor() {
	$is_ajax      = ( wp_doing_ajax() && ! isset( $_POST['object'] ) && ! isset( $_POST['target'] ) );
	$is_db_update = isset( $_GET['buddybuilder_db_update'] );

	return defined( 'ELEMENTOR_VERSION' ) &&
		   ( $is_ajax || $is_db_update ||
			 \Elementor\Plugin::$instance->editor->is_edit_mode() ||
			 \Elementor\Plugin::$instance->preview->is_preview_mode() ||
			 bpb_is_preview_mode() || bpb_is_front_library()
		   );
}

/**
 * Check if we are previewing a buddybuilder template in front-end
 *
 * @return bool
 */
function bpb_is_preview_mode() {
	return isset( $_GET['elementor_library'], $_GET['preview'] ) && bpb_is_doc_type( (int) $_GET['preview_id'] );
}

/**
 * Get column class
 *
 * @param $type
 * @param string $viewport
 *
 * @return mixed|string
 */
function bpb_get_column_class( $type, $viewport = '' ) {
	$classes = [
		'1' => 'grid-one',
		'2' => 'grid-two',
		'3' => 'grid-three',
		'4' => 'grid-four',
	];

	if ( ! isset( $classes[ $type ] ) ) {
		return '';
	}

	if ( 'tablet' === $viewport ) {
		return 'md-' . $classes[ $type ];
	}

	if ( 'mobile' === $viewport ) {
		return 'sm-' . $classes[ $type ];
	}

	return $classes[ $type ];
}

/**
 * Listing columns
 *
 * @return mixed|void
 */
function bpb_get_listing_columns() {
	$default = [
		'desktop' => '3',
		'tablet'  => '2',
		'mobile'  => '1',
	];

	$default_data = apply_filters(
		'buddy_builder/loop_columns',
		[
			'members_directory'      => $default,
			'groups_directory'       => $default,
			'group_profile_members'  => $default,
			'member_profile_friends' => $default,
		]
	);

	return get_option( 'bpb_listings', $default_data );
}

/**
 * Update columns
 *
 * @param $data
 */
function bpb_update_listing_columns( $data ) {
	update_option( 'bpb_listings', $data );
}

/**
 * If we are viewing a buddybuilder template in front-end
 *
 * @return bool
 */
function bpb_is_front_library() {
	if ( ! isset( $_GET['elementor_library'] ) ) {
		return false;
	}

	$tpl_id = (int) get_the_ID();

	if ( 0 === $tpl_id ) {
		$slug = sanitize_text_field( $_GET['elementor_library'] );
		$tpl  = get_page_by_path( $slug, OBJECT, 'elementor_library' );
		if ( $tpl ) {
			$tpl_id = $tpl->ID;
		}
	}

	return bpb_is_doc_type( $tpl_id );
}

/**
 * Check if we are on preview iframe in edit mode and a buddybuilder template
 *
 * @return bool
 */
function bpb_is_edit_frame() {
	if ( ! isset( $_GET['elementor-preview'] ) ) {
		return false;
	}

	$post_id = (int) $_GET['elementor-preview'];

	if ( 0 === $post_id ) {
		return false;
	}

	if ( ! bpb_is_doc_type( $post_id ) ) {
		return false;
	}

	return true;
}

/**
 * Check if the post is a buddybuilder document type
 *
 * @param int $id
 *
 * @return bool
 */
function bpb_is_doc_type( $id = 0 ) {
	if ( ! $id ) {
		return false;
	}

	return 'bpb-buddypress' === get_post_meta( $id, '_elementor_template_type', true );
}

/**
 * 1.x static editor previews (templates/preview), removed in 2.0: the Elementor editor shows BuddyBuilder templates
 * with real data (the preview member, group or directory) and a short placeholder elsewhere (bpb_editor_placeholder()).
 *
 * @deprecated 2.0.0
 *
 * @param string $name Template name.
 * @param array  $args Template variables.
 * @param bool   $echo Print instead of returning.
 * @return string Empty.
 */
function bpb_load_preview_template( $name, $args = [], $echo = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- 1.x signature.
	_deprecated_function( __FUNCTION__, '2.0.0', 'bpb_editor_placeholder()' );

	return '';
}

/**
 * In an Elementor editor that cannot show real data (a widget outside a BuddyBuilder template, a template without a
 * preview member or group, or buddy_builder/elementor/real_preview returned false), print a short placeholder in
 * place of the widget. 2.0 replaces the 1.x static previews with this.
 *
 * @since 2.0.0
 *
 * @param \Elementor\Widget_Base|null $widget Widget being rendered (its title starts the text).
 * @return bool Whether the placeholder was printed (the widget then prints nothing else).
 */
function bpb_editor_placeholder( $widget = null ) {
	if ( ! bpb_is_elementor_editor() || bpb_real_preview() ) {
		return false;
	}

	$ctx = class_exists( '\\Buddy_Builder\\Templates\\PreviewContext' ) ? \Buddy_Builder\Templates\PreviewContext::ctx() : [];

	if ( empty( $ctx['preview'] ) ) {
		$text = __( 'Shows real community data. Edit it in a BuddyBuilder layout (BuddyBuilder > Community pages) to preview it with a member or group.', 'stax-buddy-builder' );
	} elseif ( in_array( $ctx['type'], [ 'group-profile', 'groups-directory-item' ], true ) ) {
		$text = __( 'Pick a preview group in the page settings, section BuddyBuilder preview (or create the sample member and groups).', 'stax-buddy-builder' );
	} else {
		$text = __( 'Pick a preview member in the page settings, section BuddyBuilder preview (or create the sample member).', 'stax-buddy-builder' );
	}

	$title = $widget instanceof \Elementor\Widget_Base ? $widget->get_title() . ': ' : '';

	echo '<p class="bpb-el-empty bpb-editor-placeholder" style="margin:0;padding:1em;border:1px dashed currentColor;border-radius:4px;font-size:13px;opacity:.7">' . esc_html( $title . $text ) . '</p>';

	return true;
}

/**
 * @return mixed|void
 */
function bpb_get_appearance() {
	return get_option( 'bp_nouveau_appearance', [] );
}

/**
 * @param $data
 *
 * @return bool
 */
function bpb_update_appearance( $data ) {
	return update_option( 'bp_nouveau_appearance', $data );
}

/**
 * Check if using BuddyBoss
 *
 * @return bool
 */
function bpb_is_buddyboss() {
	return defined( 'BP_PLATFORM_VERSION' );
}

/**
 * HTML tag from a widget setting, limited to an allow-list. Elementor stores any value sent for a select control,
 * so a saved tag is never printed as it is (Patchstack 2026-11-23: a Contributor's forged tag became an <img onerror>).
 *
 * @param mixed  $tag     Tag from the settings.
 * @param string $default Fallback.
 *
 * @return string
 */
function bpb_html_tag( $tag, $default = 'h2' ) {
	return in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ], true ) ? $tag : $default;
}

/**
 * Get dummy avatar url. BuddyBuilder 2.0 has no static previews; the images stay for BuddyBuilder Pro 1.x, whose
 * editor previews use them.
 *
 * @param string $type
 * @param int    $size
 *
 * @return string
 */
function bpb_get_dummy_avatar_url( $type, $size = null ) {
	if ( $size ) {
		return BPB_ASSETS_URL . 'img/' . $type . '-avatar-' . $size . 'x' . $size . '.png';
	}

	return BPB_ASSETS_URL . 'img/' . $type . '-avatar.png';
}

/**
 * Get page slug
 *
 * @return string
 */
function bpb_get_page_slug() {
	if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
		global $wp;

		$url = home_url( $wp ? $wp->request : '' );
	} else {
		if ( empty( $_SERVER['HTTPS'] ) ) {
			$s = '';
		} elseif ( 'on' === $_SERVER['HTTPS'] ) {
			$s = 's';
		} else {
			$s = '';
		}

		$protocol = strtolower( substr( $_SERVER['SERVER_PROTOCOL'], 0, strpos( $_SERVER['SERVER_PROTOCOL'], '/' ) ) ) . $s;
		$uri      = $protocol . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		$segments = explode( '?', $uri, 2 );
		$url      = $segments[0];
	}

	$home_url = home_url( '/' );
	$home_url = str_replace( [ 'www.', 'https://', 'http://' ], '', $home_url );
	$url      = str_replace( [ 'www.', 'https://', 'http://', $home_url ], '', $url );
	$url      = rtrim( $url, '/' );

	return $url;
}

/**
 * Register a community style (2.0): page layouts as block patterns, a token stylesheet, a label, a preview and an
 * optional "recommended" flag (preselected in the setup). Call it on `init` or earlier. See
 * docs/registering-a-style.md.
 *
 * @since 2.0.0
 *
 * @param string $slug Style slug: lowercase letters, numbers, "-" and "_".
 * @param array  $args name, dir (required); css, css_deps, version, preview, description, audience, recommended,
 *                     elementor (optional).
 * @return bool
 */
function bpb_register_style( $slug, $args ) {
	return \Buddy_Builder\Presets\Styles::register( $slug, (array) $args );
}

/**
 * Remove a community style from the pickers (registered or built-in).
 *
 * @since 2.0.0
 *
 * @param string $slug Style slug.
 */
function bpb_unregister_style( $slug ) {
	\Buddy_Builder\Presets\Styles::unregister( $slug );
}

/**
 * Template file of the current activity card style ('' for Classic, which is the item markup of activity/entry.php).
 * BuddyBuilder Pro returns its Post card template for the "post" style through buddy_builder/cards/activity_template.
 *
 * @since 2.0.0
 *
 * @return string Readable file path or ''.
 */
function bpb_activity_card_template() {
	if ( ! class_exists( '\\Buddy_Builder\\Templates\\Cards' ) ) {
		return '';
	}

	$style = \Buddy_Builder\Templates\Cards::current( 'activity' );

	if ( 'classic' === $style ) {
		return '';
	}

	/**
	 * Filters the template of an activity card style.
	 *
	 * @since 2.0.0
	 *
	 * @param string $file  Template path ('' keeps the Classic markup).
	 * @param string $style Card style.
	 */
	$file = (string) apply_filters( 'buddy_builder/cards/activity_template', '', $style );

	return '' !== $file && is_readable( $file ) ? $file : '';
}
