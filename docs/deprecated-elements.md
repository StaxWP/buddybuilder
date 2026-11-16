# Deprecated elements (2.0)

Rule (Gabriel, 2026-11-15): when a new element is better than stretching an old one, build the new element and deprecate the old one.

- A deprecated element keeps rendering exactly as before. Its block name, its Elementor widget name, controls and hooks stay registered; saved block and Elementor layouts and the compat gate are unchanged.
- It leaves the block inserter (everywhere) and the Elementor panel. Editors show a short note "Deprecated, use X." where it is already used (block editor preview and Elementor editor only, never on the live page).
- Marked in `src/Elements/<name>/element.json` with `"deprecated": { "use": "<new element>", "since": "2.0.0" }`; read by `Elements::deprecated()` and `Elements::deprecated_note()`.
- No automatic replacement. "Replace with the new element" is optional and reversible: in the block editor a button in the block toolbar and in the sidebar ("Deprecated" panel), in the Elementor editor a button in the deprecated note. It replaces the element, and its deprecated siblings in the same column that point to the same new element (for example the six member header parts), with one new element at the first one's place. The new element starts from its defaults; `deprecated.map` in element.json (`{ "old setting": "new setting" }`) carries settings over. Undo brings the old elements back.

| Old element (block / Elementor widget) | New element | Why | Settings mapping |
|---|---|---|---|
| `member-cover` / `bpb-profile-member-cover` | `member-header` | One designed header with cover fallback, layouts (side, center, compact, card), stats, badges, headline; the six parts could not be arranged into the 2.0 looks without many new options | `cover_url` (custom cover) has no equivalent yet; the member's own cover is used. `overlay_background`: none (the styles control the cover) |
| `member-avatar` / `bpb-profile-member-avatar` | `member-header` | as above | `avatar_size`: the header always uses the full avatar |
| `member-username` / `bpb-profile-member-username` | `member-header` | as above | `username_type` full_name: the header name; username: `show_handle`. `username_tag`: the header uses `h1` |
| `member-meta` / `bpb-profile-member-meta` | `member-header` | as above | `show_meta` (location field, joined, last active) |
| `member-buttons` / `bpb-profile-member-buttons` | `member-header` | as above | `show_buttons` |
| `member-latest-update` / `bpb-profile-member-last-activity` | `member-header` | as above | `show_headline` with `headline_field` (a profile field) replaces the latest update in the header; the update stays in the activity tab |
| `group-cover` / `bpb-profile-group-cover` | `group-header` | One designed group header with privacy, member count, avatar stack, description and buttons | `cover_url`: none yet |
| `group-avatar` / `bpb-profile-group-avatar` | `group-header` | as above | `avatar_size`: full avatar |
| `group-name` / `bpb-profile-group-name` | `group-header` | as above | the header uses `h1` |
| `group-status` / `bpb-profile-group-status` | `group-header` | as above | privacy line in the header meta |
| `group-description` / `bpb-profile-group-description` | `group-header` | as above | `show_description` |
| `group-last-activity` / `bpb-profile-group-last-activity` | `group-header` | as above | "Active ..." in the header meta |
| `group-buttons` / `bpb-profile-group-buttons` | `group-header` | as above | `show_buttons` |
| `member-nav` / `bpb-profile-member-navigation` | `profile-tabs` | Our own tabs: no priority-menu script (it collapsed tabs into "more" in narrow columns), scrolls on small screens, Underline / Pills / Segmented | `show_home_tab` stays a BuddyPress setting (mirrored as before); counts: `show_counts` |
| `group-nav` / `bpb-profile-group-navigation` | `profile-tabs` | as above | as above |
| `activity-nav` / `bpb-sitewide-navigation` | `feed-filters` | Scopes as a segmented control and the type filter as a compact menu, in one element, with the same BuddyPress hooks | none (no content settings) |
| `activity-filters` / `bpb-sitewide-filters` | `feed-filters` | as above | the Show menu: `show_type` |
| `activity-form` / `bpb-sitewide-form` | `composer` | Our own composer (plain form, no jQuery, no script-built Backbone form) that posts through BuddyPress' `post_update` (same nonce and fields; `/activity/post/` without JavaScript) and fires `bp_before_activity_post_form`, `bp_activity_post_form_options`, `bp_after_activity_post_form`; extra fields with `buddy_builder/composer/fields` | No settings before; new: `placeholder`, `show_avatar`, `show_post_in`, `button_label` |

New 2.0 elements without a 1.x widget (Elementor gets the kit's generic adapter, widget name `bpb-el-<element>`): `composer`, `member-header`, `member-about`, `group-header`, `directory-header`, `people-rail`, `profile-tabs`, `feed-filters`. The activity item of the 2.0 looks is a card style of the activity list (`post`), not an element: it changes only the item markup, through `templates/buddypress/activity/entry-post.php`.
