=== BuddyPress Builder for Elementor and the Block Editor - BuddyBuilder ===
Contributors: staxwp, seventhqueen, codezz, rtynio, geowrge
Tags: buddypress, elementor, buddyboss, community builder, buddypress builder
Requires at least: 6.6
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.9.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Design BuddyPress member profiles, groups, directories and activity with the block editor or Elementor. Ready-made styles and live previews.

== Description ==

**BuddyBuilder** lets you design every BuddyPress page yourself: member profiles, group pages, the members and groups directories and the activity stream. Build them in the **block editor** or in **Elementor**, start from a ready-made style, and see your real community while you edit.

BuddyBuilder replaces the BuddyPress templates of your theme with layouts you design. It works with any WordPress theme, with BuddyPress and with BuddyBoss Platform. Elementor is optional.

= What you can do =

* **Community pages**: one screen lists every BuddyPress page with the layout it uses, its builder and whether it is live. Edit, change the look, duplicate, export, import or go back to the BuddyPress default from there.
* **Setup in three steps**: check your community, pick a style with a live preview, then review the new pages before anyone else sees them. Nothing changes until you press Use these.
* **Classic style**: a finished look for profiles, groups, directories and activity: cover, photo and name, cards with a soft lift. It takes your theme's colors and fonts.
* **Real data in the editor**: the block editor and Elementor show a real member or group while you edit. "Seen by" shows the page as the member, a friend, another member or a guest would see it.
* **Elements for both builders**: Member header, Member about, Group header, Directory header, People list (list or photo grid), Profile tabs, Feed filters, Post composer, plus the BuddyBuilder 1.x widgets.
* **Card styles** for member, group and activity lists.
* **Admin bar help**: edit the page you are on, change its look, view it as a guest, and "Layout not applied" with the likely cause when a layout does not show (page template, theme files, page cache and more).
* **Help and status** in plain language, including the data update and its Undo.
* **Starter kits** for Elementor, imported in one click.

= BuddyPress pages you can design =

* Member profile
* Members directory
* Group page
* Groups directory
* Activity (site-wide stream)

= Updating from BuddyBuilder 1.x =

Your Elementor templates keep working as they are. BuddyBuilder 2.0 copies which layout each page uses into a new format on the first admin page after the update; your pages look the same before and after, and Help and status has Undo. Older widgets that have a newer element keep working on your pages and only leave the widget list; one click replaces them when you want.

= Free and Pro =

BuddyBuilder is free and complete: all five BuddyPress pages, both builders, the Classic style and every element above. Nothing that was free in 1.x is locked.

[BuddyBuilder Pro](https://staxwp.com/buddybuilder/) is a separate, paid plugin. It adds six more styles (Creator, Professional, Social, Network, Career, Hub), the register and group creation pages, card layouts you design, layouts per member type, role or group, live notifications and messages, ads, birthdays, link previews and more elements. BuddyBuilder shows these Pro features with a "Pro" label on its own screens and in the Elementor panel; nothing shows while Pro is active.

= Developers =

* Register your own style with `bpb_register_style()`, so your theme or kit appears in the setup and in Change look.
* Abilities for AI agents and MCP (WordPress 6.9 and newer, read only): page types, elements, styles, the layout a page uses and the status.
* Actions and filters for layouts, previews and the setup.
* Code on [GitHub](https://github.com/staxwp/buddybuilder). Translations on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/stax-buddy-builder).

= More from StaxWP =

* [Visibility Logic for Elementor and the Block Editor](https://wordpress.org/plugins/visibility-logic-elementor/): show or hide content by user role, device, date and more.

= Privacy =

BuddyBuilder collects no usage data and sends nothing to us on its own. The usage tracker (Appsero) bundled with 1.x was removed in 2.0, and what it stored is deleted on update and on uninstall.

When you open the starter kits in BuddyBuilder or the Elementor template library and import one, the plugin fetches the kit list and the chosen kit from demo.staxwp.com (StaxWP). These requests happen only on that admin action and send the standard WordPress request data (your site URL in the user agent); no personal data is included. See the [StaxWP privacy policy](https://staxwp.com/privacy-policy/).

= Found a security issue? =

Report it through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/stax-buddy-builder). The Patchstack team helps validate, triage and handle security reports.

== Installation ==

1. Install BuddyBuilder from Plugins > Add New, or upload the plugin to `wp-content/plugins`.
2. Activate it. BuddyPress (or BuddyBoss Platform) must be active.
3. The setup opens on the next admin screen: check your community, pick a style, review it on your pages, then press Use these.
4. Later, go to BuddyBuilder > Community pages to edit a page, change its look or run the setup again.

== Frequently Asked Questions ==

= Do I need Elementor? =

No. BuddyBuilder 2.0 works with the block editor alone. If you use Elementor, every page can also be built with Elementor (version 3.20 or newer). Elementor Pro is not required.

= Will updating from 1.x change my pages? =

No. Your Elementor templates and assignments are kept and render as before. The data update can be undone from BuddyBuilder > Settings > Help and status.

= What happens to the old widgets? =

They keep working on the pages that use them. Widgets that have a newer element leave the widget list and show a "Deprecated" note in the editor, with a button to replace them with the new element (Undo brings them back).

= Does BuddyBuilder work with my theme? =

It works with any theme that follows WordPress standards, block themes and classic themes. If a layout does not show, the admin bar on that page says why ("Layout not applied") and what to change.

= Is BuddyBoss supported? =

BuddyBuilder works with BuddyBoss Platform. When BuddyBoss ReadyLaunch is on, ReadyLaunch draws the community pages instead; the admin bar and the setup tell you.

= What is in BuddyBuilder Pro? =

More styles, the register and group creation pages, card layouts, layouts per member type, role or group, live notifications and messages, ads, birthdays, link previews and more elements. See [BuddyBuilder Pro](https://staxwp.com/buddybuilder/).

= I use BuddyBuilder Pro 1.x. Can I update BuddyBuilder first? =

Yes. BuddyBuilder Pro 1.x keeps working next to BuddyBuilder 2.0: its Elementor widgets and modules work as before, and a notice suggests updating Pro to 2.x for blocks and the new features.

= Does BuddyBuilder slow down my site? =

No. It loads its styles only on community pages, and only the CSS for the page type shown. It adds no jQuery to the front end.

= Where can I get help? =

In the [support forum](https://wordpress.org/support/plugin/stax-buddy-builder/) and the [documentation](https://staxwp.com/docs/buddybuilder/).

== Screenshots ==

1. Community pages: every BuddyPress page, the layout it uses, its builder and its live status.
2. Setup: pick a style with a live preview before anything changes. Classic is free; the other styles come with BuddyBuilder Pro.
3. A member profile in the Classic style.
4. The activity stream in the Classic style, with the post composer and the sidebar lists.
5. A group page in the Classic style.
6. The block editor shows a real member while you edit; "Seen by" shows the page as another visitor would see it.
7. The same Classic layout in Elementor, with the BuddyBuilder widgets.
8. Help and status: your setup and the data update, with Undo.

== Changelog ==

= 2.0.0 =
New:

* Build community pages with the block editor. Elementor is no longer required; your Elementor templates keep working.
* BuddyBuilder > Community pages: every BuddyPress page with its layout, builder and live status, and Edit, Change look, Duplicate, History, Export, Import and Delete (with Undo).
* Setup in three steps with a live preview: nothing changes until you press Use these.
* Classic style for profiles, groups, directories and activity, in both builders.
* New elements: Member header, Member about, Group header, Directory header, People list, Profile tabs, Feed filters and Post composer.
* Card styles for member, group and activity lists.
* Real community data in the block editor and in Elementor, with "Seen by" (the member, a friend, another member, a guest).
* Admin bar on community pages: edit the page, change its look, view it as a guest, and "Layout not applied" with the likely cause.
* Help and status in plain language, with Undo for the data update.
* "Replace with the new element" for older widgets that have a newer element (Undo brings them back).
* Themes and kits can register their own style (`bpb_register_style()`).
* Read-only abilities for AI agents and MCP (WordPress 6.9 and newer).

Improvements:

* Elementor widgets show their basic settings first.
* The block inserter lists the elements made for the page you edit first.
* WordPress-native admin screens: Community pages, Settings, Help and status.
* Lighter: CSS per page type, a small icon font, no jQuery added to the front end, a much smaller download.
* Neutral `.bpb-el-*` CSS classes next to the Elementor ones.

Fixes:

* Fatal error on a new site before BuddyPress had its pages.
* A page whose address contained "members" or "groups" was treated as a BuddyPress page.
* A group creation page with its own layout caused a fatal error.
* JavaScript error in the member profile menu on block themes such as Twenty Twenty-Five.
* Dismissing an admin notice now checks a security token (also in 1.9.4).

Removed:

* The usage tracker (Appsero); what it stored is deleted on update.
* The review request notice, Youzer support and old third-party theme workarounds.
* The static preview images of the editor (replaced by real data).

Requires WordPress 6.6 and PHP 7.4; Elementor 3.20 or newer for the Elementor integration.

= 1.9.4 =
* Security: dismissing an admin notice now checks a security token and accepts only BuddyBuilder's own notices.
* Fix: help and documentation links point to the current documentation at staxwp.com/docs/buddybuilder/.

= 1.9.3 =
* Fix: Fatal error on BuddyPress pages when the BuddyPress page settings (bp-pages option) are missing; deleted directory pages are skipped and nested directory pages (for example community/members) are detected.
* Fix: PHP warning in the Members Directory and Groups Directory Filters widgets when the content display setting is missing.
* Fix: PHP 8.1+ and BuddyPress 12+ deprecation notices (group links, rtMedia request check).
* Fix: Possible "cannot redeclare class" error when loading the Elementor widgets on case-insensitive file systems.

= 1.9.2 =
* Compatibility: WordPress 7.1
* Compatibility: Elementor 4.2.4
* Compatibility: Elementor Pro 4.2.4
* Fix: Updated Elementor widget registration to use the current widgets manager API while retaining the older registration fallback.

= 1.9.1 =
* Fix: BuddyPress CSS not loading on frontend when template is active
* Fix: PHP 8.x compatibility: null check on $wp->request
* Update: BuddyPress Nouveau CSS updated to match BuddyPress 14.4.0
* Compatibility: BuddyPress 14.4.0

= 1.9.0 =
* Compatibility: WordPress 6.9
* Compatibility: Elementor 3.35
* Compatibility: PHP 7.4 minimum required
* Fix: Translation loading moved to init hook for WordPress 6.7+ compatibility
* Fix: Appsero early initialization causing translation notice
* Fix: Readme tags reduced to 5 (WordPress.org limit)
* Fix: License field corrected to GPLv2

= 1.7.4 =
* Add security nonce check to settings page.

= 1.7.3 =
* Text color fix for input forms.
* Remove empty section conditions for Elementor compatibility

= 1.7.2 =
* Upgrade Appsero SDK

= 1.7.1 =
* NOTICE: Version 1.7.0 removes the Notices code from templates and now it is available as a Widget for more customization freedom. After the update please add the Notices widget to Activity Directory, Members Directory, Groups Directory, Member Profile and Group profile templates.
* New icons added
* Added the notices to show when not using BuddyBuilder templates.

= 1.7.0 =
* NOTICE: Version 1.7.0 removes the Notices code from templates and now it is available as a Widget for more customization freedom. After the update please add the Notices widget to Activity Directory, Members Directory, Groups Directory, Member Profile and Group profile templates.
* Added new Notifications widget
* Cover widget settings and style update.
* New option added to Create Group button.
* Fix group invites for BuddyPress v10+
* Alignment fix for members and groups directory navigation.
* Member & Groups directory navigation - Fix empty space between navs items.
* Fix for directory column toggles on small resolutions
* Fix enqueue buddyboss css for editor
* Added support for group create template

= 1.6.8 =
* Fix: Customizer error on save
* Fix: Error in Customizer when switching from BuddyBoss to BuddyPress
* Improved admin panel UI

= 1.6.7 =
* Fix: BuddyBoss activity entry menu for Edit and Delete actions
* Fix: Update blocks template attributes to match newer Elementor versions
* Fix: More action button position fix in activity streams.
* Fix: Rate us admin notification
* Other code improvements

= 1.6.6 =
* Changed register widget method to support older Elementor versions too

= 1.6.5 =
* Fix error on import when not using the Pro version

= 1.6.4 =
* BuddyBoss platform compatibility
* Font library update
* New Username widget option to show also the Name of the user on profile page
* Fix groups columns on member profile
* Fix for cover images

= 1.6.3 =
* CSS improvement for members/groups grid list.
* Icons library updated
* Bug fixes

= 1.6.2 =
* BuddyPress 8.0 compatibility

= 1.6.1 =
* BuddyBoss Theme compatibility - Sitewide activity, Members directory, Groups directory & Member profile.

= 1.6.0 =
* BuddyBoss Platform compatibility.
* NEW Template Kit.

= 1.5.0 =
* Prepare for Register template in Pro version
* Icon library update
* PRO: Added Dynamic tags to generate Members data on any existing Elementor widget. Avatar, Cover Image and Xprofile field dynamic tags.

= 1.4.0 =
* PHP 8 Compatibility
* rtMedia style update.
* Fix rtMedia image popup preview
* Fix button classes for ajax
* Refactoring to meet WordPress Coding standards
* Template updates for latest BuddyPress 7.x

= 1.3.1 =
* Extra checks in BuddyBuilder logic so it loads assets and templates just when needed.

= 1.3.0 =
* New Elementor Widget: Members list - List your members anywhere on the site
* New Elementor Widget: Groups list - List your groups anywhere on the site
* New Elementor Widget: Activity list - Show activity entries anywhere on the site
* Added Site-Wide Activity Navigation widget
* Listing type(List/Grid) toggle improvements
* rtMedia compatibility style update.
* Some style fixes for activity update form.

= 1.2.4 =
* WPML Integration
* Cover position settings in Style tab && Fix template preview full width
* Fix active Member Profile Navigation tab in preview.
* Fix admin bar Edit with Elementor missing

= 1.2.3 =
* Added Group page action buttons fix.

= 1.2.2 =
* Enable global fonts and colors for all BuddyBuilder templates
* Added rtMedia compatibility
* Added more theme compatibility code. Custom integration with Sportix theme added

= 1.2.1 =
* Fix template full alignment on some themes
* Allow template override in child theme under "buddybuilder" folder
* Sitewide activity: Post form template preview update
* Added more theme compatibility code
* Added some compatibility with PHP 7.0

= 1.2.0 =
* FEATURE: Added List/Grid switcher for Members and Groups directory listing.
Enable/disable the feature by editing the members/groups navigation widget
* FEATURE: Allowing Pro Template Kit to be loaded when using Pro version

= 1.1.3 =
* Fixed admin error on some plugin activations
* Fallback to BuddyPress default template if Elementor template exists but is empty

= 1.1.2 =
* Enhanced theme compatibility logic

= 1.1.1 =
* Wider plugins compatibility with plugins like rtMedia and others
* Fix pagination links padding
* Sitewide activity item: Style update
* Profile member: Message filter style update

= 1.1.0 =
* Added responsive columns for listings
* Rewrite style & settings for members & groups grid listings.
* Member profile: Profile edit settings update.
* Member profile: Settings - Export data form style update.
* Groups & Members directory: Settings update.
* Sitewide activity: Search style update.
* Mobile view optimisations.

= 1.0.5 =
* Template import fixes
* Profile groups: Manage photo style update.
* Members directory: Style update.
* Forms style update.

= 1.0.4 =
* Extra check when checking templates used.
* Group Item: Cover style update.
* Site wide: Meta style update.
* Sitewide activity filters update.
* Load more button style update.

= 1.0.3 =
* Fix profile navigation alignment
* Group profile - Buttons settings update.
* Profile group - Invite filters style update.
* Member profile: Message filters style update.
* Manage group members: Filter style update.
* Group profile: Manage members style update.

= 1.0.2 =
* Member & Group Directory style updates.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Big update: block editor support, Community pages, a guided setup and real previews. Your Elementor templates keep working and pages look the same; the data update can be undone. Older widgets keep working. Requires WordPress 6.6 and PHP 7.4.

= 1.9.4 =
Security fix for the admin notice dismiss request. Recommended for all users.
