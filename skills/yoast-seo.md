---
name: yoast-seo
title: Yoast SEO
detects: plugin:wordpress-seo
status: verified
tested: Yoast SEO 28 test site, title and description shown in the page head
---
# Yoast SEO

Per-page SEO data lives in post meta:

| Meta | What it is |
|---|---|
| `_yoast_wpseo_title` | SEO title |
| `_yoast_wpseo_metadesc` | meta description |
| `_yoast_wpseo_meta-robots-noindex` | `1` to hide the page from search |

These meta fields are not writable through the plain REST API: writing them needs the OpenWP Bridge (planned). Reading is possible: a post exposes the rendered result as `yoast_head_json` in the REST response.

## Rules
- Title up to 60 characters, description up to 155, with the main keyword near the start.
- Do not set `noindex` on a page unless the user asks.
