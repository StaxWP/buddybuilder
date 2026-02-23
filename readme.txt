=== BuddyPress Builder for Elementor - BuddyBuilder ===
Contributors: staxwp, seventhqueen, codezz, rtynio, geowrge
Tags: elementor, buddypress, buddyboss, community builder, buddypress builder
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build and customize your BuddyPress community site with Elementor — full drag & drop control.

== Description ==

BuddyBuilder lets you **design every BuddyPress page with Elementor** — member profiles, group pages, activity feeds, directories, and more. No code, no locked-in themes, full creative control.

Instead of being limited to your theme's BuddyPress templates, BuddyBuilder overrides them with Elementor-powered layouts that you build yourself. Works with any theme, including BuddyBoss.

= What you can build =
- **Members Directory** — custom grid/list layout for your community members
- **Member Profile** — fully designed profile page with Elementor widgets
- **Groups Directory** — showcase all BuddyPress groups
- **Group Page** — custom group layout with cover, activity, and members
- **Site Wide Activity** — design the main activity feed

= Use them anywhere on the site =
- **Members Grid** — Elementor widget to display members on any page
- **Groups Grid** — Elementor widget to display groups on any page
- **Activity List** — Elementor widget to show activity entries on any page

= How does it work =
1. Create a new Elementor template from the BuddyPress section
2. Select the BuddyPress component you're building (e.g., Member Profile)
3. Edit with our BuddyPress-specific Elementor widgets
4. Save and see your new design live

= Starter Template Kits =
Get started instantly with our **ready-made template kit** — import with a single click and customize from there.

= BuddyBoss Compatible =
BuddyBuilder works with both BuddyPress and BuddyBoss Platform, giving you full Elementor control over your community regardless of which platform you use.

= Get more with BuddyBuilder Pro =

**Pro Template Kits**
Beautifully designed template kits — build a stunning community with one click.

**Live Notifications Widget**
Real-time notifications icon for your menu or content area — auto-updates with latest community notifications.

**Live Messages Widget**
Real-time messages icon that shows latest private messages from your community.

**Customize Activity Page**
Full control over the activity/news feed — structure, design, and styling.

**Customize Member Profile Tabs**
Style profile tab content for Activity, Profile, Messages, Notifications, and Settings.

**Customize Group Page Tabs**
Design group tabs for Activity, Members, Invite, and Manage sections.

Get [BuddyBuilder Pro](https://staxwp.com/go/buddybuilder-pro) and take your community to the next level.

= More from StaxWP =
- [Visibility Logic for Elementor](https://wordpress.org/plugins/visibility-logic-elementor/) — show or hide Elementor widgets based on conditions: user role, device, browser, ACF fields, and more.
- [Elementor Addons & Widgets](https://staxwp.com/go/addons-for-elementor) — powerful Elementor widgets to help you build stunning pages.
- [Woo Addons for Elementor](https://staxwp.com/go/woo-addons-for-elementor/) — Elementor enhancements for WooCommerce.

= Privacy Policy =
BuddyBuilder uses the Appsero SDK to collect telemetry data upon user confirmation. This helps us troubleshoot problems faster and make product improvements.

= Found a bug? =
Report security bugs through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/stax-buddy-builder). The Patchstack team helps validate, triage, and handle any security vulnerabilities.

== Installation ==

1. Upload the plugin to your `wp-content/plugins` directory or install directly from WP Admin → Plugins → Add New
2. Activate the plugin
3. Go to WP Admin → BuddyBuilder
4. Create a new template for the component you need (e.g., Member Profile)
5. Build the template using our BuddyPress-specific widgets
6. Save and check your BuddyPress pages — done!

== Frequently Asked Questions ==

= Does BuddyBuilder work with any theme? =
Yes. BuddyBuilder overrides BuddyPress templates with Elementor layouts, so it works with any properly coded WordPress theme.

= Is BuddyBoss supported? =
Yes. BuddyBuilder is compatible with both BuddyPress and BuddyBoss Platform.

= Do I need Elementor Pro? =
No. BuddyBuilder works with the free version of Elementor. Elementor Pro is not required.

= Can I use my own design instead of the starter templates? =
Absolutely. The starter templates are optional — you can build everything from scratch using the Elementor editor.

= Can I display members or groups on any page? =
Yes. BuddyBuilder includes Members Grid, Groups Grid, and Activity List widgets that you can place on any Elementor page.

= Does BuddyBuilder affect site performance? =
No. BuddyBuilder only loads assets on pages where BuddyPress templates are active. It does not add overhead to other pages.

= What does BuddyBuilder Pro add? =
Pro adds live notifications and messages widgets, additional template kits, full activity page customization, and the ability to style all member profile and group page tabs.

= Where can I get support? =
Visit the [WordPress.org support forum](https://wordpress.org/support/plugin/stax-buddy-builder/) or our [website](https://staxwp.com/).

== Screenshots ==
1. **Drag & Drop** — build your BuddyPress templates visually with Elementor
2. **Specific Widgets** — dedicated BuddyPress widgets for each page type
3. **Multiple Templates** — create unlimited templates and set the active one

== Changelog ==

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
* Sitewide activity — Post form template preview update
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
* Sitewide activity item — Style update
* Profile member — Message filter style update

= 1.1.0 =
* Added responsive columns for listings
* Rewrite style & settings for members & groups grid listings.
* Member profile — Profile edit settings update.
* Member profile — Settings - Export data form style update.
* Groups & Members directory — Settings update.
* Sitewide activity — Search style update.
* Mobile view optimisations.

= 1.0.5 =
* Template import fixes
* Profile groups — Manage photo style update.
* Members directory — Style update.
* Forms style update.

= 1.0.4 =
* Extra check when checking templates used.
* Group Item — Cover style update.
* Site wide — Meta style update.
* Sitewide activity filters update.
* Load more button style update.

= 1.0.3 =
* Fix profile navigation alignment
* Group profile - Buttons settings update.
* Profile group - Invite filters style update.
* Member profile — Message filters style update.
* Manage group members — Filter style update.
* Group profile — Manage members style update.

= 1.0.2 =
* Member & Group Directory style updates.

= 1.0.0 =
* Initial release

= Be a contributor =
If you want to contribute, go to our [GitHub Repository](https://github.com/staxwp/buddybuilder).

You can also add a new language via [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/stax-buddy-builder).
