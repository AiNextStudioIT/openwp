---
name: rank-math
title: Rank Math
detects: plugin:seo-by-rank-math
status: unverified
tested: not confirmed. On our test site Rank Math printed no SEO tags at all, so writing its fields was not confirmed
---
# Rank Math

Per-page SEO data is expected in post meta: `rank_math_title`, `rank_math_description`, `rank_math_robots` (a list such as `noindex`, `nofollow`).

**Status: unverified.** On our test site the fields were saved but Rank Math did not show them on the page. Tell the user to check the page in Rank Math before relying on it. Like Yoast, these meta fields are not writable through the plain REST API.
