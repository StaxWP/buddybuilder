# Registering a style

A style is a complete look for the five community pages: member profile, members directory, group page, groups
directory and activity. BuddyBuilder ships Classic; BuddyBuilder Pro registers Creator, Professional, Social, Network, Career and Hub through the same API.
A theme or a kit can add its own style (for example KLEO 7 ships the KLEO style) and have it preselected in the
BuddyBuilder setup when the theme is active.

A style is made of:

| Part | What it is |
|---|---|
| Page layouts | One block markup file per page: `<page>.html` in a folder of yours (`member-profile`, `members-directory`, `group-profile`, `groups-directory`, `sitewide-activity`). A missing file means the style does not cover that page. |
| Tokens | A stylesheet that sets the style tokens (`--bpb-*` custom properties) and any extra rules, scoped to `.bpb-look--<slug>`. It loads after BuddyBuilder's look components, only on pages that use the style. |
| Label | Name, one sentence and who it is for, shown in the setup and the look pickers. |
| Preview | An image for the setup picker (4:3 works best). Without one, BuddyBuilder draws a thumbnail from the layout. |
| Recommended | `true`, or a callback returning `true`, preselects the style in the setup and lists it first, with a "Recommended for your theme" badge. |

## Minimal example

```php
add_action( 'after_setup_theme', function () {
	if ( ! function_exists( 'bpb_register_style' ) ) {
		return; // BuddyBuilder 2.0 is not active.
	}

	bpb_register_style(
		'mytheme',
		[
			'name'        => __( 'My Theme', 'mytheme' ),
			'description' => __( 'The community pages in the My Theme look.', 'mytheme' ),
			'audience'    => __( 'Sites built with My Theme', 'mytheme' ),
			'dir'         => get_template_directory() . '/buddybuilder/style',
			'css'         => get_template_directory_uri() . '/buddybuilder/style.css',
			'version'     => wp_get_theme( get_template() )->get( 'Version' ),
			'preview'     => get_template_directory_uri() . '/buddybuilder/preview.jpg',
			'recommended' => true,
		]
	);
} );
```

`buddybuilder/style/member-profile.html` (block markup; wrap the page in a group with the look classes):

```html
<!-- wp:group {"align":"wide","className":"bpb-look bpb-look--mytheme bpb-page--member-profile","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide bpb-look bpb-look--mytheme bpb-page--member-profile">
<!-- wp:buddybuilder/member-header {"layout":"side"} /-->
<!-- wp:buddybuilder/profile-tabs {"variant":"underline"} /-->
<!-- wp:buddybuilder/member-content /-->
</div>
<!-- /wp:group -->
```

`buddybuilder/style.css`:

```css
.bpb-look--mytheme {
	--bpb-style-accent: #0f766e;
	--bpb-radius: 12px;
	--bpb-radius-lg: 16px;
	--bpb-button-radius: 999px;
	--bpb-font-heading: var(--wp--preset--font-family--heading, inherit);
}
```

The easiest start is to copy a shipped style: `patterns/<style>/` and `assets/css/looks/<style>.css` in the plugin.

## Arguments

| Argument | Type | |
|---|---|---|
| `name` | string | Required. Label in the pickers. |
| `dir` | string | Required. Absolute path of the folder with the page files. |
| `css` | string | URL of the token stylesheet. |
| `css_deps` | string[] | Extra stylesheet handles it depends on (for example the theme's own). |
| `version` | string | Stylesheet version (cache busting). |
| `preview` | string | Preview image URL for the setup picker. |
| `description` | string | One sentence for the pickers. |
| `audience` | string | Who it is for. |
| `recommended` | bool or callable | Preselect in the setup. A callback runs when the picker is built. |
| `elementor` | array | Optional: per page, the id of a built-in Elementor look to use for the Elementor version of the style. |

The slug may use lowercase letters, numbers, `-` and `_`. Register on `after_setup_theme` or `init`.

`bpb_unregister_style( $slug )` removes a style from the pickers, a registered one or a built-in one (for example a
theme that only wants its own style and Classic). Layouts already made from a style keep working: a layout is a copy.

## Tokens

The look components (`assets/css/looks/base.css`) read these custom properties; a style sets the ones it needs.

| Token | Use |
|---|---|
| `--bpb-style-accent`, `--bpb-style-accent-2` | Accent colours (a theme can still override `--bpb-accent`). |
| `--bpb-radius`, `--bpb-radius-lg`, `--bpb-button-radius`, `--bpb-avatar-radius`, `--bpb-badge-radius` | Shapes. |
| `--bpb-font-body`, `--bpb-font-heading`, `--bpb-weight-heading`, `--bpb-tracking-heading`, `--bpb-text-size`, `--bpb-name-size` | Type. |
| `--bpb-card-bg`, `--bpb-card-border`, `--bpb-card-shadow`, `--bpb-panel-bg`, `--bpb-shadow` | Surfaces. |
| `--bpb-button-bg`, `--bpb-button-2-bg`, `--bpb-button-2-color`, `--bpb-button-2-border` | Primary and secondary buttons. |
| `--bpb-badge-bg`, `--bpb-badge-color` | Member type badges. |
| `--bpb-avatar-ring` | Ring around profile photos. |

The `buddy_builder/styles` filter still works for advanced cases (it receives every style, built-in and registered).
