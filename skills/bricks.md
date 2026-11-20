---
name: bricks
title: Bricks Builder
detects: theme:bricks
status: verified
tested: Bricks test site, reading the stored structure
---
# Bricks

The layout is NOT in `post_content` (it stays empty). It is stored in post meta:

| Meta | What it is |
|---|---|
| `_bricks_page_content_2` | the whole layout, a flat list of elements (id, name, parent, children, settings) saved as a serialized PHP array |
| `_bricks_editor_mode` | `bricks` when the page is built with Bricks |

## What OpenWP can do today
- Create and edit the page shell (title, status, slug) with `create_page` / `update_page`.
- Activate the Bricks theme or its child theme.
- Read and write the layout: NOT possible with the plain REST API, because `_bricks_page_content_2` is not exposed. Needs the OpenWP Bridge (planned).

## Rules for when the layout can be written
- Elements are a flat list linked by `parent` and `children` ids. Keep ids and the parent/children links consistent when adding or removing an element.
- The value is a serialized array: write it as a real array through the bridge, never as a hand-built string.
- To rewrite only text, change the `text` / `content` fields of the `settings` and leave the rest.
