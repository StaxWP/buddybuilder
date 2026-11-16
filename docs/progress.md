# BuddyBuilder 2.0: progress

Branch `main` (the 2.0 line since 2026-11-15; 1.x fixes go to `1.x`). Not released.

## 2026-11-16 (night): Pro upsell in the free plugin

Approved by Gabriel (purchase optimizations after the staxwp.com redesign: most Pro buyers never visit staxwp.com). `src/Upsell/Upsell.php` + `assets/js/upsell.js` + `assets/css/upsell.css`; details in `docs/free-and-pro.md`.

- Elementor panel: the Pro widgets and elements as Elementor's locked promotion items with a "Pro" tag (no drag, no locked free code); a click opens a short popover with the link (own popover in every Elementor version: 3.20's dialog offers Elementor Pro and 4.4's sits under the preview).
- Community pages: "More with BuddyBuilder Pro" list, greyed out, same popover (link without JavaScript); replaces the one-line Pro mention.
- Notice: admins, BuddyBuilder screens only, after 3 community page types use a BuddyBuilder layout with content; dismissed for good (nonce, AJAX or admin-post, user meta).
- One link helper with UTM parameters (`Upsell::url()`) for the new items, the 1.x "Go PRO" boxes, the setup "Get Pro" link and Starter kits.
- Nothing shows while BuddyBuilder Pro is active.

Tests: PHP lint 7.4 and 8.5; bbmin.test (Elementor 3.20, PHP 7.4) without and with Pro (nothing shows with Pro; the real Pro elements appear instead); bbvideo.test (Elementor 4.4.1, BuddyPress 14.5.4, 1 page built: no notice); dismiss by AJAX, by link, bad nonce refused; panel search finds the Pro items; Member XProfile Field only on member profile templates; regressions 70/70 with Pro, 69/70 without (`cards-follow-layout` fails on `main` too without Pro: it expects a Pro card style); surface check against 1.9.2 ok. POT regenerated.

## 2026-11-16 (evening): free/Pro split

Decided by Gabriel (see `docs/free-and-pro.md`). Free keeps the five page types in Elementor (as in 1.9) and blocks, every free element (Member header, Member about, Group header, Directory header, People list, Profile tabs, Feed filters, Post composer), the Classic style and Classic cards, read-only abilities (plus get-status). The Pro styles, the platform elements (Community shortcuts, Group rail, Member list, Highlights rail), the Pro card styles (Compact, Cover, Boxed, Post) and the write abilities moved to BuddyBuilder Pro and register through public filters; nothing in free is locked. The Classic style now uses Classic cards. Without Pro: a Pro element renders nothing (editor placeholder), a page with a Pro style renders as Classic with no data change (notice on BuddyBuilder screens), a Pro card style renders as Classic; `tests/compat/pro-missing.js` checks it, and gate.sh runs free only rows (`--base 1ea71dd@none --cand main@none`). The register page stays free in Elementor (it was in 1.9).

## 2026-11-16 (later): style API, Elementor styles, composer, Pro without jQuery, replace action, docs

- **Style registration** (decision: the KLEO style lives in the KLEO 7 theme): `bpb_register_style()` / `bpb_unregister_style()` (page layouts, token stylesheet, label, preview image, `recommended` flag preselected and badged in the setup); the KLEO presets left BuddyBuilder. Doc: `docs/registering-a-style.md`.
- **Elementor versions of every style**: the style's block pattern turned into Elementor sections, columns (classes, widths), inner sections and widgets with the same settings; look classes on the document wrapper (`_bpb_look`), style CSS in the head after Elementor's. Screenshots match the block versions (`bin/capture-styles.js --builder elementor`).
- **Composer**: our own `composer` element (no jQuery, BuddyPress `post_update`, `/activity/post/` without JavaScript, BuddyPress form hooks, `buddy_builder/composer/fields`, events); the styles use it; `activity-form` deprecated. Tested: a post appears at the top of the list.
- **Pro J6 to J8**: post form, ads and link preview without jQuery (see the Pro repo).
- **Replace with the new element**: block toolbar and sidebar, Elementor note button; one new element for the deprecated siblings that point to it; Undo restores. Tested in both editors.
- **Docs**: `docs/README.md`, `styles.md`, `composer.md`, `hooks.md`.
- Fixes on the way: the phone tab bar lost its size to the Network shortcut rules (specificity), post images could overflow their card on phones.

## 2026-11-16: concept review fixes, the style set, Creator concept, accepted differences

**Style set (Gabriel, 2026-11-16)**: Classic (default, backward compatibility), Creator, Professional, Social, Network, Career, Hub. Learning, Local club, Membership and KLEO on hold. Main review page: vault `looks/platform-concepts.md` (7 columns).

**Concept review fixes**: Career (About text full width, one Groups heading, the favorite action labelled like Comment, icon only on phones); Hub (dark ghost Load More, a dark one-line composer pinned at the bottom of the stream, replies as compact indented rows, rows about 40% tighter with actions in a hover toolbar, offline members dimmed less); Network (Intro plus a Friends photo grid in the profile's left column, name and meta closer, the favorite count next to its icon).

**Creator as a platform concept** (creator and photo sharing pattern): shortcuts on the left and a bottom tab bar on phones; a centered profile without a cover, gradient ring photo, stats as a row of numbers, the bio, highlight bubbles (the member's groups), tabs with a rule on top, the Activity tab as a 3-column grid (photo posts as square tiles, text posts as compact cards, counts on hover); the feed with an active members row, full-width media, compact replies. The people list element gained `member-groups` and `friends` sources, `grid` and `bubbles` layouts and `show_title`; the phone tab bar and compact replies are shared rules in `looks/base.css`. The sample community has 7 photo posts (drawn images).

**Twenty Twenty-Five sub nav (701882e) kept** by decision: `tests/compat/accepted.json` lists it as an accepted difference (only the profile sub nav may differ, at most 8% pixels); `compare.js --row <theme>` reports such cases as "pass (accepted: id)" and the gate summary shows the count.

**Gate** (2026-11-16, head 055b8f5, base 1.9.2 + Pro 1.7.0): compare 132/132 on all 8 theme rows for 2.0 + Pro 2.0 and 2.0 + Pro 1.7.0 (Twenty Twenty-Five: 32 of them accepted); WP 6.6 / PHP 7.4 / Elementor 3.20 132/132. Budgets 132/132 with Pro 2.0; 130/132 with Pro 1.7.0 (its own 1.x jQuery scripts, as before), which is why gate.sh still prints FAIL for that pairing. Goldens 88/88 with and without Elementor (re-baselined after review: data only). Regressions 96/96. The env no longer records "last active" on page views (a signed-in run made its member "online" for the next run).

**Second review (2026-11-16)**: Creator's profile grid shows only the member's own posts on the Personal stream (system items left out; Mentions, Favorites and the rest stay a list; "No posts yet" otherwise, a translated string the look CSS prints); Hub media at chat size (at most 480 x 360, natural ratio, rounded); images in Post items link to their full size (`bpb_link_images`). Goldens re-baselined once more: the previous baseline had the viewer "online" (recorded before the env stopped updating last active); now 88/88 twice per row, with and without Elementor. Regressions 96/96.

**Tools**: `capture-styles.js --html` keeps the rendered markup, captures load lazy images; `golden.js` keeps the differing HTML of a failing case under `results/golden-<row>/html/` for review before `--update`.

## 2026-11-15: gate holes, review defects, cards, styles, Elementor preview, menu fold

Brief: review `Projects/staxwp/docs/2026-11-15-buddybuilder-2-review.md` (vault), decisions of 2026-11-15 (1, 3 to 8; decision 2 then approved as option B: hand-designed styles).

**Release gate (review 5.5)**

| Gate | Done | Where |
|---|---|---|
| G1 Pro starter kits | 31 templates from the demo.staxwp.com feed (22 BuddyPress Pro, 9 BuddyBoss) as fixtures; feed export by the coordinator, no key needed | `tests/compat/fixtures/kit-*.json`, `bin/import-kit-feed.js` |
| G2 theme rows | Driver over KLEO 6.2.4, 6.2.7, KLEO 7 (K Elements 7), SweetDate 4.1.7, BuddyApp 2.1.7, Twenty Twenty-Five, Hello Elementor, Astra; base 1.9.2 + Pro 1.7.0, candidates 2.0 + Pro 2.0 and 2.0 + Pro 1.7.0. BuddyBoss theme: not available locally | `tests/compat/bin/gate.sh` |
| G3 WP 6.6 / PHP 7.4 | Fresh site http://bbmin.test (Herd PHP 7.4, WP 6.6.2, Twenty Twenty-One): full gate row and the fresh install check | `tests/compat/fresh.js` |
| G4 block layout goldens | Every look and card style as a block layout, visitor and member, HTML goldens + screenshots | `tests/compat/golden.js` |
| G5 blocks-only classic theme | Fresh install without Elementor on Twenty Twenty-One: setup opens, style applied, five pages render for visitor and member | `tests/compat/fresh.js` |
| G6 Elementor 3.20 | Elementor 3.20.0 on the WP 6.6 / PHP 7.4 row | gate row `twentytwentyone` on bbmin |
| G7 budgets | JS and CSS gzip of BuddyBuilder + Pro per page, no jQuery handles (Pro J6 to J8 listed), no dashicons from us for visitors | `tests/compat/budgets.js`, asset report in the env mu-plugin |
| G8 real updater | 1.9.3 wp.org zip, update through Plugin_Upgrader to a 2.0 test package served by the site, Redis object cache on: PASS, 12/12 identical | `tests/compat/bin/update-real.sh` |
| G9 multisite | http://bbms.test (subdirectory network): network activation, fresh install on the main site ALL PASS, sub-site front end and BuddyBuilder screens render (Herd does not route sub-site wp-admin/*.php paths; checked through WP-CLI) | manual + `fresh.js` |

Test env fixes found on the way: the activity directory and group activity never loaded in the env (heartbeat deregistered while BuddyPress made its scripts depend on it); block layouts assigned by cookie were shadowed by the legacy option; notices compare by first line; a baseline fatal the candidate fixes counts as a pass.

**Review defects**: W2 dashicons replaced by a 36-glyph subset (2.4 KB WOFF2, filter `buddy_builder/full_dashicons`); W4 setup copy; W6 Change look has Preview, a confirm step and Undo (option `bpb_undo`); W1 CSS: screen files for messages, settings, group management (member profile 16.7 to 15.3 KB gzip; the real diet stays 2.1). Bug found by the gate: group creation with its own layout fataled (also in 1.9.2), fixed.

**Cards (decision 3, scope change)**: card styles in the 6 list elements (members and groups: Classic, Compact, Cover; activity: Classic, Compact, Boxed, Post), Elementor control `card`, mirrored to `bpb_cards` with the layout it belongs to. Pro: the 19 member card, group card and activity item widgets are blocks with one renderer each (40/40 item cases identical); card (item) types get block layouts with a real loop in the editor preview.

**Styles (decision 2, option B)**: Classic, Creator, Professional, Social as hand-written patterns (`patterns/<style>/<page>.html`) and token sets (`assets/css/looks/`); new elements member-header, member-about, group-header, directory-header, people-rail, profile-tabs, feed-filters; the Post activity item. 17 old parts deprecated (still render; list in `docs/deprecated-elements.md`). Setup step 2 starts with "Pick a style". Screenshots for review in the vault (`Projects/staxwp/docs/assets/buddybuilder-2.0/looks/`). Learning, Local club, Membership and KLEO styles: not started (waiting for Gabriel's review of the first four).

**Platform concepts** (Gabriel, 2026-11-15, beside the four styles, which stay as they are): Network (social network pattern), Career (professional network pattern), Hub (chat community pattern, dark), each with its own structure and visual language; new elements shortcuts-nav, group-rail, member-list; phone patterns (bottom tab bar, stacked cards, drawers). Review page: vault `looks/platform-concepts.md`.

**Gate, final run of the day** (head 9a81745, base 1.9.2 + Pro 1.7.0): KLEO 6.2.4, KLEO 6.2.7, KLEO 7, Hello, Astra: 132/132 for 2.0 + Pro 2.0 and 2.0 + Pro 1.7.0; SweetDate 4.1.7 132/132 and 130/132 (relative-time pixels, fixed after the run); BuddyApp 131/132 and 132/132 (theme web-font race); WP 6.6 / PHP 7.4 / Elementor 3.20 (Twenty Twenty-One) 132/132; Twenty Twenty-Five 100/132 and 98/132: all 32 are the member profile sub nav markup of 701882e (BuddyPress priority-nav classes added on block themes to fix a 1.9.2 JavaScript error), an intended difference that needs Gabriel's decision. Budgets: 132/132 with Pro 2.0 on every row; 130/132 with Pro 1.7.0 (its own 1.x scripts).

**Elementor (decision 4)**: the editor previews BuddyBuilder templates with a real member or group and "Seen by" (document settings "BuddyBuilder preview"); static previews stay as fallback (`buddy_builder/elementor/real_preview`).

**Admin (decision 5)**: menu Community pages, Features (Pro), Settings; Help and status and License are Settings tabs; starter kits in the look picker. Live page admin bar: page and look name, Edit in <builder>, Change look, View as a guest, View as the sample member (one request, administrators, nonce).

**Pro variations (decision 8)**: see the Pro repo.

Tests (2026-11-15): PHP lint 7.4 and 8.5 on every changed file (123 checks, 0 errors); regressions 95/95 on bbux; e2e 47/47 (KLEO 6); fresh install ALL PASS on WP 6.6 / PHP 7.4 and on multisite; G8 PASS; gate rows: see the vault inventory 15 entry for the final numbers.

Open for 2.0: the four remaining styles, Elementor versions of the styles (generated from the closest 2.0-dev look today), real photos for screenshots (needs Gabriel's OK to download), composer as our own element (BuddyPress' form, restyled), "Replace with the new element" action for deprecated parts, BuddyBoss theme row, docs.


## 2026-11-13: majors UX (Community pages, setup, previews, checker, help)

Done (plan items B-M1 to B-M6; B-M5 is in BuddyBuilder Pro):

| Item | What | Where |
|---|---|---|
| B-M1 | **Community pages** (BuddyBuilder > Community pages, slug `buddy-builder-settings`, the 1.x dashboard slug): one row per page type with thumbnail, layout, builder, live status, Edit, Change look / Pick a look, More (BuddyPress default, a layout you already have, new empty layout, all block layouts). Item layouts (member card, group card, activity item, group creation) are edited from their parent row; Pro page types appear only with Pro; one "More in Pro" line without Pro. Replaces the 1.x selects, the 2.0-dev Layouts screen and the Status templates table. Same data (`Registry`: `bpb_templates`, mirrored in `bpb_settings`), no migration; rendering the screen writes nothing (regression `community-pages-read-only`). The 1.x `admin_post_bpb_settings` handler is kept. Native admin shell (no STAX header, no Tailwind on 2.0 screens); menu: Community pages, Starter kits, Pro modules (Pro, licensed), Settings, Help and status (License only while Pro is unlicensed). | `src/Admin/CommunityPages.php`, `admin/layout.php`, `admin/actions.php`, `admin/Admin.php`, `assets/css/admin-ui.css` |
| B-M2 | **Setup** (`buddy-builder-setup`, hidden page): 1 check your community (platform, components with Turn on, pages, theme, ReadyLaunch, members; builder choice; sample member), 2 pick a look with a live preview on your own profile or the sample member, 3 review the new looks on the real pages, then Use these (or keep them as drafts: Community pages shows "New look ready, not live yet" with Use / Preview / Discard). Opens once after activation on new installs only (`Setup::is_new_install()`); updated sites see a notice while no page has a layout; "Run setup again" on Community pages and Settings. Other plugins' notices are hidden on the setup screen. | `src/Admin/Setup.php`, `assets/js/setup.js` |
| B-M2 | **Settings** screen: block layouts switch, sample member, run setup again. | `src/Admin/Options.php` |
| B-M3 | **Seen by** in the block editor preview: member profile seen by you, the member, a friend, another member, a guest; group page seen by you, a group member, a member not in the group, a guest. BuddyPress' logged-in member becomes the viewer before the profile is booted; a guest preview renders logged out after the REST permission check and the user is restored after the request. Viewers other than a guest need `bp_moderate`, or the sample member / group. | `src/Templates/PreviewContext.php`, `src/Elements/layout-panel.js`, meta `_bpb_preview_viewer` |
| B-M3 | **Live previews of looks** on real pages without saving (`LayoutPreview`: `?bpb_lp[type]=preset:<id>|post:<id>&bpb_lp_nonce=`; administrators only, no cache, noindex, banner). Used by the setup (steps 2 and 3) and the Preview button of pending looks. | `src/Templates/LayoutPreview.php` |
| B-M4 | **Admin bar** on BuddyPress pages: "Edit this community page" / "Design this community page"; **"Layout not applied"** when a layout is assigned but did not render (decided at the end of the page), with causes: page template not default, theme files in `buddybuilder/`, Elementor Pro theme template, BuddyBoss ReadyLaunch, page cache. The last finding per type is kept (`bpb_render_issues`) and shown on Community pages; forgotten when the layout renders or the assignment changes. Layouts printed through their shortcode by 1.x theme overrides count as rendered. | `src/Admin/Checker.php` |
| B-M6 | **Help and status** in plain language: help links, your setup, data update (done / waiting / did not finish, Run now, Undo), theme files; technical details folded. Migration steps have a translated `label()`. | `admin/pages/Status.php`, `src/Migrations` |
| B-M5 (free part) | Starter kits: native cards, Pro kits listed once with a "Pro" label. The review request notice is removed. | `admin/pages/templates/templates.php`, `core/Notices.php` |

Extension points for BuddyBuilder Pro 2.1 layout variations (per member type, group type, role, single group; conditions from `staxwp/conditions`, not started):

- `buddy_builder/template/resolve` (`Dispatcher::resolve()`): return another `[ builder, id ]` for a type on this request, or `content` (block markup). Used by the front end, item loops, `Current::entry()` and the asset loading. The assigned layout is the fallback.
- `buddy_builder/is_template_populated/pre`: short-circuit the "has a layout" check of a type.
- `buddy_builder/community_pages/after_row` (type, row): the variation list under a page row.
- `buddy_builder/setup/after_looks`: slot for the "describe your community" AI helper (2.2).
- `buddy_builder/checker/causes`, `buddy_builder/readylaunch_active`, `buddy_builder/setup/checks`: filters for the checker and the setup.

Tests (2026-11-13, throwaway copy of bb2fix: http://bbux.test, KLEO 6.2.4, Elementor 4.4 + Pro, BuddyPress 14.5.4, WP 7.1.4):

- PHP lint 7.4 and 8.5: all files of both plugins, 0 errors.
- `tests/compat/bin/migrations-test.php`: 21/21.
- `tests/compat/bin/regressions.sh`: 80/80 PASS (the 42 existing checks plus `preview-seen-by` x5, `community-pages-read-only`, `checker-known-issues`, `setup-new-install`). BB-3 now saves the Community pages selects and the 1.x form handler.
- `tests/compat/bin/surface.php check`: free 1.9.2 and Pro 1.7.0 surfaces ok (the 1.x admin filters `buddy_builder/admin/templates_list` and `.../after_restructure` still drive the page list).
- `tests/e2e/ux.js` (Playwright + axe): 37/37 on BuddyPress with Pro licensed, also on BuddyBoss Platform 1.8.6 (Pro modules skipped there: unlicensed). axe: no serious or critical violations on setup steps 1 to 3, Community pages, Settings, Help and status, Starter kits, Pro modules.
- `tests/e2e/profile-nav.js` (Playwright): member profile navigation with an Elementor and a block layout on the site's theme and on Twenty Twenty-Five (BuddyPress priority nav): no BuddyPress or BuddyBuilder JavaScript errors (the "Failed to execute 'appendChild' on 'Node'" regression), one priority sub nav, narrow-screen "more" menu opens. 28/28 + 4 SKIP on KLEO (no priority nav there). Fails without the fix in `src/Elements/member-content/render.php`.
- BuddyBoss: `community-pages-read-only` and the setup checks also run with `--require=tests/compat/env/buddyboss.php`. ReadyLaunch: see the second pass below (tested on BuddyBoss Platform 3.7.2).
- POT files regenerated.

Not done in this run / open:

- Element list ordered per page type with 2 to 4 basic settings (5.4, U3) and the Elementor editor on the preview context with "seen by" (B-L1, 2.1 task 27).
- "More" menu: duplicate, history, export, delete (B-L4, 2.1).
- Docs on staxwp.com: drafts on staxwp-site branch `docs/bb-2.0` (see below); sidebar entries and wp.org screenshots at release.

## 2026-11-13 (second pass): packaging, real look thumbnails, ReadyLaunch, setup redirect

- **Release zip**: `.distignore` + `bin/build-zip.sh [ref] [out dir]` (git archive of the ref, minus the `.distignore` paths; top folder and version from `loader.php`). Excluded: tests, docs, bin, node_modules, vendor, package/composer files, phpcs/phpunit/Playwright configs and results, `*.map`, `.DS_Store`, git files (Pro also tailwind config and `assets/scss`). Verified: `stax-buddy-builder-2.0.0-dev.zip` 2.9 MB, 428 files, 0 excluded paths, the 12 look thumbnails inside; Pro zip 1.8 MB, 525 files, 0 excluded paths. Building is not publishing.
- **Real look thumbnails**: `assets/img/looks/<preset>.webp` (12 looks, 480 px square WebP, about 85 KB in all, shipped with the plugin), each look rendered on the sample member / sample group on Twenty Twenty-Five by `bin/capture-looks.js` (+ `bin/capture-looks.php`, throwaway site only). `Preview::thumb()` uses them on Community pages (row and Change look) and in the setup; theme and add-on presets (KLEO 7 defaults) keep the drawing. Layout previews now show the sample member's data and render the activity / members / groups streams server-side (the AJAX request would not carry the preview). Setup step 2 "Keep current" previews the current layout.
- **ReadyLaunch**: tested on BuddyBoss Platform 3.7.2 (public GitHub release, installed on bbux.test as `buddyboss-platform-372`): `bb_is_readylaunch_enabled()` (option `bb_rl_enabled`) detected; with ReadyLaunch on, the admin bar says Layout not applied with ReadyLaunch first and setup step 1 warns; with it off, the layouts render and the e2e suite passes. Elementor Pro theme templates are not listed as a cause while ReadyLaunch is on. Regression `checker-readylaunch` runs mocked (filter) everywhere and with the real function when `BPB_BUDDYBOSS_PLUGIN=buddyboss-platform-372/bp-loader.php` is set for `env/buddyboss.php`.
- **Setup redirect**: activation sets a one-day transient; the setup opens on the next calm admin screen (Plugins, Dashboard, a BuddyBuilder screen), never on another plugin's setup or onboarding screen, AJAX, cron, bulk activation or the network admin. Covered by `setup-new-install` and the e2e suite.
- **Docs**: staxwp-site branch `docs/bb-2.0` (not merged): draft pages under `/docs/buddybuilder-2/` (What's new, Community pages, Setup, Seen by, Layout not applied, Help and status, Pro modules), screenshots on the Space under `wp-content/uploads/docs/buddybuilder-2/`.

Tests (second pass): PHP lint 7.4 and 8.5 0 errors (both plugins); migrations 21/21; regressions 89/89 (with the real ReadyLaunch run); surface free and Pro ok; e2e + axe 39/39 on BuddyPress (Pro licensed fixture) and 37/37 + 1 skip (Pro unlicensed) on BuddyBoss Platform 3.7.2.

Finding (not BuddyBuilder): on Twenty Twenty-Five, BuddyPress' `buddypress-priority-menu.js` throws "appendChild ... not of type Node" on profiles with a BuddyBuilder layout (it calls `hiddenList.firstChild.nextSibling` without a check). Not seen on KLEO; to look at with a block-theme pass.
