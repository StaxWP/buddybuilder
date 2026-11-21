# Element kit (`staxwp/element-kit`)

Schema-driven WordPress elements. An element is a folder with an `element.json` (attributes, controls, labels) and one `render.php` (the markup). From that folder the kit generates:

- a dynamic block (server-registered, no `block.json` on disk, no build step: `assets/editor.js` builds the inspector from the schema), and
- an Elementor widget (generic `ElementorWidget`, or the `ElementorAdapter` trait in an existing widget class),

with the same markup in both builders. PHP 7.4+, WordPress 6.6+, Elementor optional.

Used by K Elements 7 (KLEO) and BuddyBuilder 2.0 (BuddyBuilder Pro adds its elements to BuddyBuilder's kit).

## Layout

| Path | |
|---|---|
| `src/Kit.php` | One kit per plugin: config, element discovery, block and Elementor registration, editor data. |
| `src/Element.php` | One element: schema, assets, the single renderer. |
| `src/Schema.php` | `element.json` reader, validator, defaults, value normalizer, translatable strings. |
| `src/Context.php` | Immutable render context (preview flag, post id, plugin values). |
| `src/BlockAdapter.php` | `element.json` to `register_block_type()` arguments and the render callback. |
| `src/ElementorAdapter.php`, `src/ElementorWidget.php` | Elementor controls and render from the schema. |
| `src/Strings.php` | Writes a plugin's `src/Elements/strings.php` (one `__()` per string) for `wp i18n make-pot`. |
| `assets/editor.js` | Generic block editor UI (plain JS on `wp` globals). |
| `tests/` | Unit tests (no WordPress; a few WordPress functions are stubbed in `tests/wp-stubs.php`). |

## How plugins consume it

Each plugin ships its **own namespace-prefixed copy** in `vendor-prefixed/`, made by [Strauss](https://github.com/BrianHenryIE/strauss), and commits it. Customers, wordpress.org and ThemeForest builds need neither Composer nor access to this private repo.

`composer.json` of the plugin (BuddyBuilder shown; K Elements uses `KElements\\Vendor\\` and `KELEMENTS_VENDOR_`):

```json
{
	"repositories": [ { "type": "vcs", "url": "git@github.com:StaxWP/element-kit.git" } ],
	"require": { "staxwp/element-kit": "^1.0" },
	"require-dev": { "brianhenryie/strauss": "^0.31" },
	"extra": {
		"strauss": {
			"target_directory": "vendor-prefixed",
			"namespace_prefix": "Buddy_Builder\\Vendor\\",
			"namespace_replacement_patterns": { "~^StaxWP\\\\ElementKit~": "Buddy_Builder\\Vendor\\ElementKit" },
			"classmap_prefix": "Buddy_Builder_Vendor_",
			"constant_prefix": "BPB_VENDOR_",
			"packages": [ "staxwp/element-kit" ],
			"classmap_output": false,
			"update_call_sites": false,
			"delete_vendor_packages": false,
			"include_modified_date": false,
			"include_author": false
		}
	},
	"scripts": {
		"kit:prefix": "strauss",
		"kit:check": [ "@kit:prefix", "git diff --exit-code -- vendor-prefixed && test -z \"$(git status --porcelain -- vendor-prefixed)\"" ]
	}
}
```

The plugin's own autoloader maps the prefixed namespace (`Buddy_Builder\Vendor\ElementKit\` to `vendor-prefixed/staxwp/element-kit/src/`); no Composer autoloader is loaded at run time. The kit finds `assets/editor.js` from where it is installed (`plugins_url()` relative to its own folder), so the plugin does not pass a `kit_url`.

### Two plugins on one site

Every copy has its own PHP namespace, so different kit versions load side by side. Every name a copy puts on the page comes from the plugin's config and must be unique per plugin:

| Config | Used for |
|---|---|
| `namespace` | Block names `<namespace>/<element>`, editor script handle `<namespace>-kit-editor`, style handles `<namespace>-<element>`, view module ids. |
| `prefix` | Hooks `<prefix>_elements`, `<prefix>_render`. |
| `js_global` | Editor data `window.<js_global>`. The editor script URL carries `?kit=<js_global>`, and each copy of `editor.js` registers only the blocks in its own global. |
| `css_prefix` | Wrapper classes `<css_prefix>-el <css_prefix>-el-<element>`. |
| `elementor_prefix`, `elementor_category` | Elementor widget names and category. |
| `text_domain` | Strings of `element.json` (an element can set its own `textdomain`, as in `block.json`). |

The kit defines no global PHP functions or constants and adds no `wp.hooks` filters.

### Rule

**Never edit `vendor-prefixed/` in a plugin.** Change the kit here, release it, and update each plugin. `composer kit:check` (run by the plugin's tests) fails when `vendor-prefixed/` differs from a fresh Strauss run.

## Develop

```sh
composer install
composer test          # phpcs (WPCS 3, PHP 7.4+, WP 6.6+) + phpunit
npm install && npm run lint:js
```

Try a change in a plugin before releasing: in the plugin, `composer config repositories.kit path ../element-kit` (do not commit that), `composer update staxwp/element-kit`, `composer kit:prefix`, test, then put the VCS repository back.

## Release

1. Change the kit, `composer test`, lint JS.
2. Bump `Kit::VERSION` (semver: a new config key, attribute key or control is minor; a removed or renamed public method, hook or schema key is major) and add the change to the commit message.
3. Commit on `main`, tag `vX.Y.Z`, push the commit and the tag.
4. In each plugin (K Elements `7.x`, BuddyBuilder `main`): `composer update staxwp/element-kit`, `composer kit:prefix`, run the plugin's tests (including `composer kit:check`), commit `composer.lock` and `vendor-prefixed/` together (`kit: element kit X.Y.Z`).
5. BuddyBuilder Pro has no copy: it uses BuddyBuilder's (`Buddy_Builder\Vendor\ElementKit\*`), so it needs a BuddyBuilder release that ships the kit version it relies on.

Source: extracted from K Elements `src/Kit` (k-elements `7.x` at `079a7d5`, which BuddyBuilder `main` at `bd5ffc2` carried as `src/Kit`); the history of those files is kept.
