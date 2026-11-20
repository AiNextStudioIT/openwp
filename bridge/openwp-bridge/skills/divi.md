---
name: divi
title: Divi (4 and 5)
detects: theme:divi, plugin:divi-builder
status: verified
tested: Divi 5 test site, pages written as Divi 4 style shortcodes (rendered correctly); reading via REST
---
# Divi

The layout is stored in `post_content` as shortcodes, for example:

```
[et_pb_section fb_built="1" _builder_version="4.27.4"][et_pb_row][et_pb_column type="4_4"][et_pb_text _builder_version="4.27.4"]<p>Hello</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]
```

A page opens with the Divi builder only if the post meta `_et_pb_use_builder` is `on`. That meta is NOT writable through the plain REST API. Consequences:

- **Edit an existing Divi page** (it already has `_et_pb_use_builder = on`): read the raw content with `get_content_raw`, change only the text inside the modules, send it back with `update_page` or `update_post`. This works today.
- **Create a new Divi page from scratch**: the page renders the shortcodes as plain text unless `_et_pb_use_builder` is `on`. With the **OpenWP Bridge**, `set_builder_data` with `builder: "divi"` and `content` sets it for you (or use `set_page_meta`).

## With the OpenWP Bridge
`get_builder_data` returns the shortcode `content`; `set_builder_data` with `id` and the whole new `content` saves it (a copy is saved first, `restore_builder_backup` undoes it). Tested on Divi 5.18 with Divi 4 style shortcodes: edit, see it on the page, restore.

## Rules
- Keep every `_builder_version` and attribute exactly as they are; change only the text between the tags.
- Do not remove `fb_built="1"` from a section.
- Never put a literal `[` shortcode in the text of a paragraph.
