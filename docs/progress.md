# BuddyBuilder 2.0: progress

Branch `2.0` (development, not released). Released lines stay on `master` (1.9.x).

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
