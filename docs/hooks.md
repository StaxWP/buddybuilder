# Public functions and hooks (2.0)

1.x functions, hooks, shortcodes and theme overrides are unchanged. This page lists what 2.0 adds or documents.

## Functions

| Function | |
|---|---|
| `bpb_register_style( $slug, $args )`, `bpb_unregister_style( $slug )` | Styles from themes and kits. See [registering-a-style.md](registering-a-style.md). |
| `bpb_render_template( $type, $context )` | Render the layout assigned to a template type. |
| `bpb_get_shortcode_str( $template )` | 1.x: the shortcode that prints a layout (block layouts return `[bpb-layout]`). |
| `bpb_activity_card_template()` | Template of the current activity card style ('' for Classic). |
| `bpb_real_preview()` | Whether the Elementor editor previews a real member or group. |
| `bpb_editor_placeholder( $widget )` | In an Elementor editor without real data, prints a short placeholder for a widget and returns true (2.0 replaces the 1.x static previews with it). |

## Filters

| Filter | |
|---|---|
| `buddy_builder/styles` | Every style (built-in and registered). Prefer `bpb_register_style()`. |
| `buddy_builder/card_styles`, `buddy_builder/card_style_labels` | Card styles per list (members, groups, activity) and their labels. |
| `buddy_builder/cards/activity_template` | Template file of an activity card style. |
| `buddy_builder/people_rail/layouts` | Layouts of the people list (Pro adds `bubbles`). |
| `buddy_builder/abilities` | Ability definitions (BuddyBuilder Pro adds the write abilities). |
| `buddy_builder/presets` | Preset layouts (`type`, `title`, `layout` or `content`, optional `theme`). |
| `buddy_builder/template/resolve` | The layout used for a type on this request or loop item (`[ builder, id ]`, `$context` with `user_id` / `group_id` when known). BuddyBuilder Pro uses it for layout variations (member type, role, group type, one group). For loop items, another layout post returned here renders instead of the assigned one, also when the assigned item layout is Elementor (2026-11-17). |
| `buddy_builder/blocks_enabled` | Block layouts on or off. |
| `buddy_builder/split_css` | Per page BuddyPress CSS (default on). |
| `buddy_builder/full_dashicons` | Load the full dashicons font instead of the community icon subset. |
| `buddy_builder/elementor/real_preview` | Real member or group in the Elementor editor preview (false: a placeholder per widget). |
| `buddy_builder/elementor/basic_controls` | Control keys a BuddyBuilder Elementor widget shows first, in its "Settings" section (at most 4; keys and defaults never change). |
| `buddy_builder/member_stats`, `buddy_builder/member_badges` | Numbers and badges in member headers. |
| `buddy_builder/checker/causes` | Reasons the "layout not applied" checker reports. |
| `buddy_builder/setup/checks` | Setup checks. |
| `buddy_builder/upsell/enabled` | Pro badges, Pro links and the Pro notice in the free plugin (default: on while BuddyBuilder Pro is not active). |
| `buddy_builder/upsell/features` | The Pro features shown in the free plugin (`id => [ title, text, elementor? ]`). |
| `buddy_builder/layout/export` | Data of a layout export from Community pages > More > Export (`$data`, `$post`). Elementor templates use Elementor's template format. Block layout exports come back with Community pages > Import layout (2026-11-17). |
| `buddy_builder/notices/dismissible` | Notice ids (user meta keys) the notice dismiss request (`bpb_admin_notice_viewed`) may set; default `bpb_rate_us`. The request also needs the notice's nonce (`Notices::dismiss_url()`, or `data-nonce` for the AJAX request). |

## Element kit

| Key | |
|---|---|
| `textdomain` in `element.json` | Text domain of the element's strings (title, description, setting labels and help, option labels, keywords, variation titles), as in `block.json`. Elements another plugin adds through `buddy_builder_kit_elements` set it (BuddyBuilder Pro sets `stax-buddy-builder-pro`); without it, BuddyBuilder's domain. `php bin/element-strings.php --root <plugin> --domain <domain> --package <name>` writes that plugin's `src/Elements/strings.php` for its POT. The kit change (`Element::t()`, `Kit::t( $text, $domain )`) still has to go upstream to k-elements' `src/Kit` before the next `bin/sync-kit.sh`. |
| `hideWhen` on an attribute in `element.json` | `{ "<other attribute>": <value> }`: the setting hides in both editors while the other attribute has that value (it does nothing then; for example the header parts' Size, cover background and HTML tag with `design` `header`). Kit change, opt-in per attribute (`Schema::errors()`, `Element::editor_definition()`, `editor.js`, `ElementorAdapter::kit_hide_conditions()`), to port to k-elements' `src/Kit`. The 1.x Elementor part widgets apply the same conditions to their own controls (`Widgets\Base`). |
| `lookIgnores`, `splitIgnores` in `element.json` | Block supports that do nothing inside a style (`bpb-look` group: the style's CSS draws the element) or inside a Member header split into parts (`bpb-mh--parts` group: the part's wrapper takes no box): `color.background`, `color.text`, `color.link`, `typography.fontSize`, `typography.lineHeight`, `typography.textAlign`, `border`, `spacing.padding`, `spacing.margin`, `dimensions.minHeight`. The block editor hides those controls there (`src/Elements/look-supports.js`, filter `blockEditor.useSetting.before`); a block with a saved value keeps the control until it is cleared. The supports stay registered, so saved values keep validating and rendering. BuddyBuilder only, not the kit. Measured per element on the page in all seven styles (2026-11-20). |

## Deprecated in 2.0

| Hook or function | |
|---|---|
| `buddy_builder/widget/member-profile/preview`, `buddy_builder/widget/group-profile/preview` | Filtered the 1.x static editor preview of the Content widgets. The static templates (`templates/preview/`) are gone: the editor shows real data. Code still hooked gets the real preview markup, with a deprecation notice. |
| `bpb_load_preview_template()` | Returns '' with a deprecation notice. |

## Actions

| Action | |
|---|---|
| `buddy_builder/cards/item_top` | Markup at the top of a members or groups loop item for the current card style. |
| `buddy_builder/composer/fields` | Extra fields in the post composer. See [composer.md](composer.md). |
| `buddy_builder/preset_applied` | A preset or style page was applied (`$id`, `$post_id`, `$builder`). |
| `buddy_builder/looks/enqueued` | A style's stylesheet was enqueued (`$style`; BuddyBuilder Pro adds the rules of its register, group creation and card presets). |
| `buddy_builder/registry/changed` | Layout assignments changed. |
| `buddy_builder/setup/after_looks` | After the setup's look step. |
| `buddy_builder/community_pages/after_row` | Extra content after a row of Community pages (`$type`, `$row`; BuddyBuilder Pro prints the variations of the page and of its item layouts). |
| `buddy_builder/layout/duplicated` | A layout was duplicated from Community pages (`$copy` draft id, `$id` original). |
| `buddy_builder/layout/imported` | A block layout export was imported from Community pages (`$id` new draft, `$data` export data). |

## Events (JavaScript)

| Event | |
|---|---|
| `bpb:composer:posted`, `bpb:composer:error` | On the composer form. |
