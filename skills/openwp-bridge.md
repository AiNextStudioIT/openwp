---
name: openwp-bridge
title: OpenWP Bridge (companion plugin) and Developer mode
detects: plugin:openwp-bridge
status: verified
tested: Elementor 4.4, Bricks 2.4, Divi 5.18, Gutenberg on WordPress 7.1
---
# OpenWP Bridge

A small plugin the site owner installs. It adds what the plain REST API cannot do: read and write builder layouts, write SEO fields, with a backup before each change. Check it with `bridge_status`.

## Tools
`bridge_status`, `get_builder_data`, `set_builder_data`, `list_builder_backups`, `restore_builder_backup`, `set_page_meta`.

## Working rule
Read with `get_builder_data`, change only what was asked, send the whole layout back with `set_builder_data`, tell the user the `backup` id. If something looks wrong, `restore_builder_backup`.

## Developer mode (tools `dev_run_php`, `dev_run_sql`, `dev_files`)
Off by default. The site owner turns it on in *the **OpenWP** menu* for 1, 8 or 24 hours, and it turns itself off. When it is off the tools answer "Developer mode is off": do not try to get around it, ask the user to turn it on. It is for test and staging sites: never use it on a store with real customers without the user's explicit request. SQL is read-only; files are limited to `wp-content`; every call is logged in the same page.
