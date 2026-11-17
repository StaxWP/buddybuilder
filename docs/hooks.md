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

## Filters

| Filter | |
|---|---|
| `buddy_builder/styles` | Every style (built-in and registered). Prefer `bpb_register_style()`. |
| `buddy_builder/card_styles`, `buddy_builder/card_style_labels` | Card styles per list (members, groups, activity) and their labels. |
| `buddy_builder/cards/activity_template` | Template file of an activity card style. |
| `buddy_builder/people_rail/layouts` | Layouts of the people list (Pro adds `bubbles`). |
| `buddy_builder/abilities` | Ability definitions (BuddyBuilder Pro adds the write abilities). |
| `buddy_builder/presets` | Preset layouts (`type`, `title`, `layout` or `content`, optional `theme`). |
| `buddy_builder/template/resolve` | The layout used for a type on this request (`[ builder, id ]`), for variations (BuddyBuilder Pro uses it per member type and group type). |
| `buddy_builder/blocks_enabled` | Block layouts on or off. |
| `buddy_builder/split_css` | Per page BuddyPress CSS (default on). |
| `buddy_builder/full_dashicons` | Load the full dashicons font instead of the community icon subset. |
| `buddy_builder/elementor/real_preview` | Real member or group in the Elementor editor preview. |
| `buddy_builder/member_stats`, `buddy_builder/member_badges` | Numbers and badges in member headers. |
| `buddy_builder/checker/causes` | Reasons the "layout not applied" checker reports. |
| `buddy_builder/setup/checks` | Setup checks. |
| `buddy_builder/upsell/enabled` | Pro badges, Pro links and the Pro notice in the free plugin (default: on while BuddyBuilder Pro is not active). |
| `buddy_builder/upsell/features` | The Pro features shown in the free plugin (`id => [ title, text, elementor? ]`). |
| `buddy_builder/layout/export` | Data of a layout export from Community pages > More > Export (`$data`, `$post`). Elementor templates use Elementor's template format. |

## Actions

| Action | |
|---|---|
| `buddy_builder/cards/item_top` | Markup at the top of a members or groups loop item for the current card style. |
| `buddy_builder/composer/fields` | Extra fields in the post composer. See [composer.md](composer.md). |
| `buddy_builder/preset_applied` | A preset or style page was applied (`$id`, `$post_id`, `$builder`). |
| `buddy_builder/registry/changed` | Layout assignments changed. |
| `buddy_builder/setup/after_looks` | After the setup's look step. |
| `buddy_builder/community_pages/after_row` | Extra content after a row of Community pages. |
| `buddy_builder/layout/duplicated` | A layout was duplicated from Community pages (`$copy` draft id, `$id` original). |

## Events (JavaScript)

| Event | |
|---|---|
| `bpb:composer:posted`, `bpb:composer:error` | On the composer form. |
