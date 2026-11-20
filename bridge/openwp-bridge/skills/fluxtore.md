---
name: fluxtore
title: fluXtore (funnels for WooCommerce)
detects: plugin:fluxtore
status: verified
tested: fluXtore 2.1.0 on test sites, fluxtore_* tools of the Bridge 0.7.0 (create a funnel, link a product), no Developer mode
---
# fluXtore

fluXtore builds sales funnels on WooCommerce: a funnel (flow) made of steps (landing, checkout, upsell, downsell, thank you).

## FIRST: use the fluXtore tools (OpenWP Bridge 0.7+), NOT Developer mode
When fluXtore is active the Bridge offers these tools. **Developer mode is not needed.** Use them before anything else:
- `fluxtore_templates`: the templates (slug, builders, needs_pro).
- `fluxtore_funnels`: every funnel with its steps (step_id, title, type, url, linked product).
- `fluxtore_create_funnel` with `template`, `builder` (Gutenberg, Elementor, Bricks or Divi) and `name`: creates the funnel and its pages.
- `fluxtore_set_product` with `step_id` and `product_id` (and an optional `price`): links a WooCommerce product to a checkout, order bump, upsell or downsell step.
- `fluxtore_stats`: the numbers of the month.
If these tools are not in your list, the chat was opened before the plugin was updated: ask the user to open a **new chat** (or reconnect the MCP) and update the OpenWP Bridge plugin to 0.7.0 or later. Only then, as a last resort on a test site, use Developer mode.

### A funnel for a course or product, step by step
1. `fluxtore_templates`, pick a template that fits and works with the user's builder.
2. `create_woo_product` as a **draft** with the price the user gave.
3. `fluxtore_create_funnel`.
4. `fluxtore_set_product` on the checkout step (and on offer steps if asked).
5. Rewrite the texts of the pages with the builder tools (`get_builder_data` / `set_builder_data`), after reading the builder skill.
6. Tell the user what was created and that nothing is live until the product is published and the checkout address is shared.

## How the data is stored
- Funnels are the post type `fluxtore_flow` and steps are `fluxtore_step`. The funnel post type is not in the REST API: use the tools above.
- The page of a step is built with the builder of the template: use `get_builder_data` / `set_builder_data` to read or change its texts.
- The product of a step is the post meta `__fluxtore__product` (the WooCommerce product ID).
- Templates marked Pro need fluXtore Pro active. Create WooCommerce products as **drafts** and tell the user to publish them: visitors cannot buy a draft.

## "Create with AI" of fluXtore Pro
It runs only inside the fluXtore admin screen (fluXtore > Funnels > Create with AI) with the user's AI connection. It is not available as a tool yet. If the user wants a funnel written by AI, tell them to use that button, or build one from a template with the tools above and rewrite its texts with the builder tools.

## Rules
- Never delete a funnel or a step without the user's explicit request.
- Do not change the address (slug) of steps of a live funnel.
- Changes to pages of a live funnel: read first, change only what was asked, mention the backup id.
