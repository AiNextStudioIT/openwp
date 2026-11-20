---
name: fluxtore
title: fluXtore (funnels for WooCommerce)
detects: plugin:fluxtore
status: verified
tested: fluXtore 2.1.0 on test sites, importing a template pack and linking a product with Developer mode on
---
# fluXtore

fluXtore builds sales funnels on WooCommerce: a funnel (flow) made of steps (landing, checkout, upsell, downsell, thank you).

## How the data is stored
- Funnels are the post type `fluxtore_flow` and steps are `fluxtore_step`. **Steps** can be listed and read with `get_custom_posts` (`post_type: fluxtore_step`). **Funnels are not exposed** by the REST API, so you cannot list or create them with the plain tools.
- The page of a step is built with the builder of the template (Gutenberg, Elementor, Bricks or Divi): use `get_builder_data` / `set_builder_data` (OpenWP Bridge) to read or change its texts, after reading the matching builder skill.
- The product of an offer step is the post meta `__fluxtore__product` (the WooCommerce product ID).

## Create a funnel from a template (needs the Bridge and Developer mode ON)
Developer mode is off by default: ask the user to turn it on first, and use it only on a test or staging site. Then with `dev_run_php`:

```php
$flow_id = fluXtore_Template_Packs::import( 'coaching-funnel', 'Gutenberg', 'My funnel' );
return $flow_id;
```

- The builder name is `Gutenberg`, `Elementor`, `Bricks` or `Divi`.
- List the available templates with `return array_map( function ( $p ) { return $p['slug']; }, fluXtore_Template_Packs::all() );`. Templates marked Pro need fluXtore Pro active.
- Link a product to an upsell or downsell step: `update_post_meta( $step_id, '__fluxtore__product', $product_id );`. The accept button of the template shows the price by itself.
- Create WooCommerce products as **drafts** (`create_woo_product` with `status: draft`) and tell the user to publish them: visitors cannot buy a draft.

## "Create with AI" of fluXtore Pro
It runs only inside the fluXtore admin screen (fluXtore > Funnels > Create with AI) with the user's AI connection. It is not available as a tool yet. If the user wants a funnel written by AI, tell them to use that button, or build one from a template as above and rewrite its texts with the builder tools.

## Rules
- Never delete a funnel or a step without the user's explicit request.
- Do not change the address (slug) of steps of a live funnel.
- Changes to pages of a live funnel: read first, change only what was asked, mention the backup id.
