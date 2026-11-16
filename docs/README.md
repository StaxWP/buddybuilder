# BuddyBuilder 2.0 docs

| Doc | For |
|---|---|
| [free-and-pro.md](free-and-pro.md) | What is free and what is Pro, how the split works, what happens when Pro is missing. |
| [styles.md](styles.md) | The seven styles, how a site picks one, block and Elementor versions, card styles. |
| [registering-a-style.md](registering-a-style.md) | Themes and kits: add your own style (layouts, tokens, label, preview, recommended flag). |
| [composer.md](composer.md) | The post composer element: how it posts, its hooks and events. |
| [deprecated-elements.md](deprecated-elements.md) | Which 1.x elements are deprecated, what replaces them, the "Replace with the new element" action. |
| [hooks.md](hooks.md) | Public functions, filters and actions of 2.0. |
| [progress.md](progress.md) | Development log of the 2.0 line. |

Backward compatibility is the rule of 2.0: Elementor data, the 1.x widget names and control keys, `bpb_settings`, hooks, functions, shortcodes and theme overrides keep working. `tests/compat/` is the suite that checks it (see `tests/compat/bin/gate.sh`).
