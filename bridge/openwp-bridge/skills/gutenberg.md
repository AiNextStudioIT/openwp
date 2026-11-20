---
name: gutenberg
title: Gutenberg (block editor, WordPress core)
detects: core
status: verified
tested: WordPress 7.1 on test sites, create/update via REST
---
# Gutenberg

The page is stored as block markup in `post_content`. Everything works with `create_page`, `update_page`, `create_post` and `update_post`, no extra plugin needed.

## Format
Each block is wrapped by HTML comments. A paragraph, a heading and a button:

```
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Title</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Text of the paragraph.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com">Buy now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
```

Groups, columns and lists follow the same pattern (`wp:group`, `wp:columns` / `wp:column`, `wp:list` / `wp:list-item`). The JSON in the comment holds the block settings and must be valid JSON.

## Rules
- Send the whole `content` string; WordPress does not merge partial content.
- Do not use CDATA wrappers in the content.
- Read the current page first with `get_content_raw` and change only what was asked: rewrite one paragraph, keep the rest identical.
- Keep the comment delimiters balanced, or the editor shows "block contains unexpected content".
