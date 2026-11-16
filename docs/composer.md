# Post composer

The `composer` element (block `buddybuilder/composer`, Elementor widget `bpb-el-composer`) is BuddyBuilder's own "What's new" form. The styles use it on the activity page; it replaces the deprecated `activity-form` element (BuddyPress' script-built form), which keeps working where it is already used.

It shows to signed-in members on the activity page, on their own profile and on groups they belong to.

## How it posts

- With JavaScript (`assets/js/composer.js`, no jQuery, about 1.6 KB gzip): BuddyPress' AJAX action `post_update` with BuddyPress' nonce (`_wpnonce_post_update`) and its fields (`content`, `object`, `item_id`), plus every other field of the form. The new item is added at the top of the activity list on the page.
- Without JavaScript: the form posts to BuddyPress' `/activity/post/` handler (`whats-new`, `whats-new-post-object`, `whats-new-post-in`).

Both go through BuddyPress (`bp_activity_post_update()` or `groups_post_update()`), so `bp_activity_posted_update`, `bp_groups_posted_update` and every plugin that listens to new updates keep working.

## Settings

| Setting | |
|---|---|
| `placeholder` | Empty: "What's new, <first name>?" |
| `show_avatar` | The member's photo. |
| `show_post_in` | On the activity page, a "Post in" menu: your profile or one of your groups. |
| `button_label` | Empty: "Post". |

## Hooks

| Hook | |
|---|---|
| `bp_before_activity_post_form`, `bp_activity_post_form_options`, `bp_after_activity_post_form` | Fired like BuddyPress' own form, so plugins that print there still do. |
| `buddy_builder/composer/fields` (action, args `$object`, `$item_id`) | Print extra fields inside the form; they are sent with the post. |

## Events

On the form element, bubbling:

- `bpb:composer:posted` with `detail.id`, `detail.html` and `detail.response` (BuddyPress' answer).
- `bpb:composer:error` with `detail.error`.

BuddyBuilder Pro's link preview listens to the composer: the preview card and its `link_*` fields go into the form and are sent with the post.
