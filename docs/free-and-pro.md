# Free and Pro (2.0)

Decided by Gabriel on 2026-11-16. Hard rule: nothing that is free in 1.9.x becomes locked. A free site keeps everything it has; the compatibility gate (`tests/compat/bin/gate.sh`, rows with and without Pro) proves it.

| | BuddyBuilder (free, wordpress.org) | BuddyBuilder Pro |
|---|---|---|
| Page types | Member profile, group, members directory, groups directory, activity; register page in Elementor (as in 1.9) | Block layouts for register and create a group (2.1) |
| Builders | Elementor (as in 1.9) and the block editor | |
| Elements | All free elements, including Member header, Member about, Group header, Directory header, People list (list and photo grid), Profile tabs, Feed filters, Post composer | Community shortcuts, Group rail, Member list, Highlights rail; the card designer (19 card blocks and widgets) |
| Styles | Classic | Creator, Professional, Social, Network, Career, Hub, and future styles |
| Card styles | Classic | Compact and Cover (members, groups); Compact, Boxed and Post (activity) |
| Variations | | Layout per member type, role, group type or group |
| Abilities (agents, MCP) | Read only: list-template-types, list-elements, list-presets, get-layout, get-status | Write: apply-preset, apply-style, update-layout, assign-template; off by default (Settings > Agents); agents make drafts, a preview link is returned, then the layout is used; Elementor templates stay read only for agents |
| Modules | | Live, ads, birthdays, link preview, menus, the extra widgets and widget add-ons (as in 1.x) |

Any theme or kit can register its own style with `bpb_register_style()` ([registering-a-style.md](registering-a-style.md)), free or Pro; KLEO 7 registers the KLEO style from the theme.

## How the split works

- Pro styles and elements live in the Pro plugin and register through the same public APIs: `bpb_register_style()`, the element kit (`buddy_builder_kit_elements`), `buddy_builder/card_styles`, `buddy_builder/card_style_labels`, `buddy_builder/cards/activity_template`, `buddy_builder/cards/item_top`, `buddy_builder/people_rail/layouts`, `buddy_builder/abilities`. The free plugin has no locked code.
- The free setup shows the Pro styles as preview cards with a "Pro" label and one "Get Pro" link, on BuddyBuilder screens only. Nothing else.
- Licence (unchanged): without a valid licence, Pro elements are locked in the editors; the front end is never locked. A KLEO 7 licence unlocks Pro.

## When Pro is missing

Pro deactivated, removed, or a Pro that does not load:

- **A Pro element in a layout** renders nothing for visitors: no markup, no notice, no empty wrapper; the rest of the layout renders. The block editor shows "This element needs BuddyBuilder Pro." in its place.
- **A page whose layout uses a Pro style** renders with the Classic style page for visitors. No stored data changes: the assignment and the layout stay, and activating Pro brings the page back at once. BuddyBuilder screens show one notice: "Pages using Pro styles show the Classic style until BuddyBuilder Pro is active."
- **A Pro card style** renders as Classic.

`tests/compat/pro-missing.js` checks both page cases for visitors and members (status, no notices, no Pro markup, no empty wrappers, Classic for the Pro style, identical HTML after reactivation).

## Later (roadmap, not now)

- AI style generator: a style from a description or a screenshot (Pro).
- Community AI (Pro), after a demand test.
