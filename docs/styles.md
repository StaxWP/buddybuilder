# Styles

A style is a complete look for the five community pages (member profile, members directory, group page, groups directory, activity), plus the card style of its lists. BuddyBuilder ships Classic; BuddyBuilder Pro adds six more ([free-and-pro.md](free-and-pro.md)):

| Style | Plugin | Pattern | For |
|---|---|---|---|
| Classic (default) | Free | The familiar community page: cover, photo and name on the side, soft cards | Any community |
| Creator | Pro | Creator and photo sharing: centered profile with a gradient ring, numbers row, highlight bubbles, posts as a 3-column grid, phone tab bar | Creators, artists, photographers, fan communities |
| Professional | Pro | Compact header, facts in a side card, list directories | Associations, alumni |
| Social | Pro | Feed first, slim header, people and groups rail | Friend networks, hobby communities |
| Network | Pro | Social network: shortcuts, feed, people and groups rail, phone tab bar | Large friend networks, alumni, member communities |
| Career | Pro | Professional network: sticky profile card, profile as stacked section cards | Professional networks, job communities |
| Hub | Pro | Chat community, dark: group rail, sections, message stream, who is online | Gaming, creator and fan communities |

Themes add their own with [`bpb_register_style()`](registering-a-style.md) (KLEO 7 ships the KLEO style that way).

## Picking a style

- **Setup** (BuddyBuilder > Community pages, first run): "Pick a style for your community" sets all five pages at once; "Choose per page" keeps per page choices. A style the active theme marks as recommended is preselected.
- **Community pages > Change look**: per page, with Preview, a confirm step and Undo.
- **Block editor**: every style page is also a block pattern (category BuddyBuilder) in the layout editor.

Applying a style page creates a layout (a copy): editing it never changes the style, and later style updates never change it.

## Thumbnails

The pickers (setup, Change look) and the Community pages rows show a screenshot of each style page: a 480 px square WebP of the top of the page, rendered on the sample community only (members `demo-*` and `bpb-sample-*` with drawn media, their groups and activity; compat env cookie `bpb_compat_sample_only`), never site or test users. Classic: `assets/img/looks/classic-<page>.webp` (found by `Preview::thumb()`); BuddyBuilder Pro styles: Pro `assets/img/presets/<style>-<page>.webp` (the preset's `thumb`, set by Pro). Made by `bin/capture-styles.js --widths 1200 --thumbs <dir>` (Pro: add `--patterns <Pro>/styles`); a style without a screenshot keeps the drawing.

## Block and Elementor versions

The builder of the site decides which version is created:

- **Blocks**: the style's pattern (`patterns/<style>/<page>.html` in the plugin that registers the style) as a `bpb_template` layout. The page wrapper carries `bpb-look bpb-look--<style> bpb-page--<page>`; the style's stylesheet loads in the head.
- **Elementor**: the same pattern turned into an Elementor template: sections and columns with the same classes and widths, a card group as an inner section, every element as its widget (1.x widget names where the element has one, `bpb-el-<element>` for 2.0 elements) with the same settings. The look classes sit on the Elementor document wrapper (meta `_bpb_look`), and the style's stylesheet loads in the head after Elementor's.

## Register, group creation and card presets (BuddyBuilder Pro)

BuddyBuilder Pro adds presets for its page types to every style (`<style>-register-page`, `<style>-groups-create`, `<style>-members-directory-item`, `<style>-groups-directory-item`, `<style>-sitewide-activity-item`; Pro `src/Presets.php`), 35 in all. A style picks one arrangement per page; its tokens give the look, so one arrangement looks like Career in Career and like Hub in Hub. The arrangements' rules are in Pro `assets/css/looks/items.css`, loaded with any style stylesheet (action `buddy_builder/looks/enqueued`).

| Style | Register | Create a group | Member card | Group card | Activity item |
|---|---|---|---|---|---|
| Classic | Centered card | Centered card, steps as pills | Classic (centered photo) | Classic | Classic (photo column) |
| Creator | Centered card | Centered card, steps as pills | Cover band | Cover band | Post card |
| Professional | Welcome on the side, form card | Steps listed on the side | Compact (photo next to text) | Compact | Post card |
| Social | Form, welcome and "Already here" photo grid | Centered card, steps as pills | Compact | Compact | Post card |
| Network | Form, welcome and "Already here" photo grid | Steps listed on the side | Cover band | Cover band | Post card |
| Career | Sticky welcome card, form as section cards | Steps listed on the side | Compact | Compact | Post card |
| Hub (dark) | Centered card | Centered card, steps as pills | Compact | Compact | Message row (buttons on hover) |

Where they show:

- **Community pages**: Change look (Pick a look) on the Register and Create a group rows; on the Member card, Group card and Activity item rows, "Design the card" (Change card once a card layout is assigned): the card layout is its own page (review decision 3) and starts from the style's card instead of a blank layout. Preview opens the real page (the directory for cards); the register page has no preview for signed-in administrators (BuddyPress sends them away from it).
- **Setup**: Register and Create a group are set with the style ("Customize per page" lists them). Cards stay out of the setup: a card layout replaces the card style of every list.
- **Block editor**: block patterns (category BuddyBuilder) in layouts of those page types; **Variations**: "Add a variation from a look" offers them.
- **Elementor**: the same patterns turned into Elementor templates (look classes on the document wrapper, every element its 1.x Pro widget).
- **Thumbnails**: Pro `assets/img/presets/<preset>.webp`, made by `bin/capture-looks.js --pro` (sample community media only, all drawn by `bin/sample-media.js`). A preset's `thumb` (image URL) is used by `Preview::thumb()`.

## Card styles

Member, group and activity lists have a card style: Classic in BuddyBuilder; BuddyBuilder Pro adds Compact and Cover (members and groups) and Compact, Boxed and Post (activity). Without Pro, a Pro card style renders as Classic. A style sets them in its pattern (`"card"` on the list block, the `card` control on the widget). The choice is stored with the layout it belongs to (option `bpb_cards`) and applies only while that layout is assigned, so AJAX loads (filters, load more) keep the same cards.

## Tokens

Each style is a token set (`assets/css/looks/<style>.css` of its plugin) over the shared look components (`assets/css/looks/base.css`). A theme can override `--bpb-accent`, `--bpb-font-heading` and `--bpb-font-body` on `.bpb-look`. The full token list is in [registering-a-style.md](registering-a-style.md#tokens).
