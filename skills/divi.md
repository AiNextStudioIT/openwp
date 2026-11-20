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
- **Create a new Divi page from scratch**: the page will render the shortcodes as plain text unless the meta is set. Needs the OpenWP Bridge (planned) or a Divi template imported by the user.

## Rules
- Keep every `_builder_version` and attribute exactly as they are; change only the text between the tags.
- Do not remove `fb_built="1"` from a section.
- Never put a literal `[` shortcode in the text of a paragraph.
