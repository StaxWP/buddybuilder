# Deprecated elements and header parts (2.0)

Rule (Gabriel, 2026-11-15): when a new element is better than stretching an old one, build the new element and deprecate the old one.

Exception (UX review 2026-11-17, approved): the member and group **header parts** are not deprecated. Member header and Group header are the one-click designed headers of the styles; the parts stay first-class elements for owners who build their own header (as in 1.x).

## Header parts

- The six member header parts (cover, avatar, name, meta, buttons, latest update) and the seven group header parts (cover, avatar, name, status, description, last activity, buttons) are in the block inserter, category "BuddyBuilder header parts" (member profile and group page layouts), and in the Elementor panel, category "BuddyBuilder Header parts" (member profile and group page templates). No "Deprecated" note in either editor. Rendering is unchanged.
- Marked in `src/Elements/<name>/element.json` with `"category": "buddybuilder-header-parts"` and `"partOf": { "use": "member-header" }` (or `group-header`); read by `Elements::part_of()`. The Elementor widgets take the category from element.json (`Widgets\Base::get_categories()`, `Base::HEADER_PARTS_CATEGORY`).
- "Replace with Member header" (or Group header) stays as a quiet optional action: in the block editor a closed sidebar panel (no toolbar button), in the Elementor editor a closed "Replace with ..." section at the end of the Content tab (a UI button, nothing saved). Same replacement as below (the parts of the same column become one header, Undo brings them back).

## Member header split into parts (BuddyBuilder Pro)

- In the block editor, "Split into parts" (Member header toolbar and sidebar, Pro, licensed) replaces the Member header with core groups that carry the header's classes and one element per part, following the header's settings: `header.bpb-mh.bpb-mh--<layout>[.bpb-mh--has-cover].bpb-mh--parts` > Member cover, `.bpb-mh__body` > Member avatar, `.bpb-mh__main` > (`.bpb-mh__name` > Member name, Member badges), Member name (@username), Member headline, Member last active (details), then Member stats and Member buttons. The groups have List View names (Member header (parts), Header body, Header details, Name and badges).
- The free parts get a stored `design` attribute (kit control `none`, no setting in either editor): `header` prints the Member header's markup for that part (`bpb_mh_*()` in `look-helpers.php`, also used by the Member header itself, plus the `bpb-mh-part` class). Without it they keep their 1.x markup. Member last active also stores `location_field`.
- New Pro parts (`stax-buddy-builder-pro/src/Elements`): Member stats (counts; none picked = the Member header's, else the picked ones in order; Followers and Following when BuddyPress Follow or BuddyBoss follow is active), Member headline (a profile field or the latest update), Member badges (site role, member types, `buddy_builder/member_badges`). They print the header's markup too, so they also work on their own (blocks and Elementor widgets).
- `base.css`: the builders' wrappers around a part take no box (`display: contents`), the groups keep no layout spacing, the name part's h1 takes the name row's style. Checked pixel for pixel against the one-element header in all seven styles, 1440 and 390 px, visitor and member, Twenty Twenty-Five and Twenty Twenty-One.
- A part inside a split header (or the header group) gets "Join into one Member header" instead of the quiet Replace: one Member header whose settings follow the parts that are there.
- Elementor: no split. The style templates are sections and columns: a column holds widgets and one level of inner section, the header needs three nested groups (header, body, details), and Elementor containers cannot sit in a column.

## Deprecated elements

- A deprecated element keeps rendering exactly as before. Its block name, its Elementor widget name, controls and hooks stay registered; saved block and Elementor layouts and the compat gate are unchanged.
- It leaves the block inserter (everywhere) and the Elementor panel. Editors show a short note "Deprecated, use X." where it is already used (block editor preview and Elementor editor only, never on the live page).
- Marked in `src/Elements/<name>/element.json` with `"deprecated": { "use": "<new element>", "since": "2.0.0" }`; read by `Elements::deprecated()` and `Elements::deprecated_note()`.
- No automatic replacement. "Replace with the new element" is optional and reversible: in the block editor a button in the block toolbar and in the sidebar ("Deprecated" panel), in the Elementor editor a button in the deprecated note. It replaces the element, and its siblings in the same column that point to the same new element, with one new element at the first one's place. The new element starts from its defaults; `map` in element.json (`{ "old setting": "new setting" }`) carries settings over. Undo brings the old elements back.

| Element (block / Elementor widget) | Replaced by | Why | Settings mapping |
|---|---|---|---|
| `member-cover` / `bpb-profile-member-cover` | `member-header` (header part, not deprecated) | One designed header with cover fallback, layouts (side, center, compact, card), stats, badges, headline; the six parts could not be arranged into the 2.0 looks without many new options | `cover_url` (custom cover) has no equivalent yet; the member's own cover is used. `overlay_background`: none (the styles control the cover) |
| `member-avatar` / `bpb-profile-member-avatar` | `member-header` (header part, not deprecated) | as above | `avatar_size`: the header always uses the full avatar |
| `member-username` / `bpb-profile-member-username` | `member-header` (header part, not deprecated) | as above | `username_type` full_name: the header name; username: `show_handle`. `username_tag`: the header uses `h1` |
| `member-meta` / `bpb-profile-member-meta` | `member-header` (header part, not deprecated) | as above | `show_meta` (location field, joined, last active) |
| `member-buttons` / `bpb-profile-member-buttons` | `member-header` (header part, not deprecated) | as above | `show_buttons` |
| `member-latest-update` / `bpb-profile-member-last-activity` | `member-header` (header part, not deprecated) | as above | `show_headline` with `headline_field` (a profile field) replaces the latest update in the header; the update stays in the activity tab |
| `group-cover` / `bpb-profile-group-cover` | `group-header` (header part, not deprecated) | One designed group header with privacy, member count, avatar stack, description and buttons | `cover_url`: none yet |
| `group-avatar` / `bpb-profile-group-avatar` | `group-header` (header part, not deprecated) | as above | `avatar_size`: full avatar |
| `group-name` / `bpb-profile-group-name` | `group-header` (header part, not deprecated) | as above | the header uses `h1` |
| `group-status` / `bpb-profile-group-status` | `group-header` (header part, not deprecated) | as above | privacy line in the header meta |
| `group-description` / `bpb-profile-group-description` | `group-header` (header part, not deprecated) | as above | `show_description` |
| `group-last-activity` / `bpb-profile-group-last-activity` | `group-header` (header part, not deprecated) | as above | "Active ..." in the header meta |
| `group-buttons` / `bpb-profile-group-buttons` | `group-header` (header part, not deprecated) | as above | `show_buttons` |
| `member-nav` / `bpb-profile-member-navigation` | `profile-tabs` | Our own tabs: no priority-menu script (it collapsed tabs into "more" in narrow columns), scrolls on small screens, Underline / Pills / Segmented | `show_home_tab` stays a BuddyPress setting (mirrored as before); counts: `show_counts` |
| `group-nav` / `bpb-profile-group-navigation` | `profile-tabs` | as above | as above |
| `activity-nav` / `bpb-sitewide-navigation` | `feed-filters` | Scopes as a segmented control and the type filter as a compact menu, in one element, with the same BuddyPress hooks | none (no content settings) |
| `activity-filters` / `bpb-sitewide-filters` | `feed-filters` | as above | the Show menu: `show_type` |
| `activity-form` / `bpb-sitewide-form` | `composer` | Our own composer (plain form, no jQuery, no script-built Backbone form) that posts through BuddyPress' `post_update` (same nonce and fields; `/activity/post/` without JavaScript) and fires `bp_before_activity_post_form`, `bp_activity_post_form_options`, `bp_after_activity_post_form`; extra fields with `buddy_builder/composer/fields` | No settings before; new: `placeholder`, `show_avatar`, `show_post_in`, `button_label` |

New 2.0 elements without a 1.x widget (Elementor gets the kit's generic adapter, widget name `bpb-el-<element>`): `composer`, `member-header`, `member-about`, `group-header`, `directory-header`, `people-rail`, `profile-tabs`, `feed-filters`. The activity item of the 2.0 looks is a card style of the activity list (`post`), not an element: it changes only the item markup, through `templates/buddypress/activity/entry-post.php`.
