# OpenWP

**Control WordPress with AI. Free.** Open source, MIT license.

OpenWP connects Claude, Cursor, Windsurf and any AI that speaks MCP to your WordPress site. You say "publish a post", "lower the price of that product" or "rewrite the top of this page", and it happens. Your AI talks straight to your site: nothing goes through our servers.

**Website:** [openwpmcp.com](https://openwpmcp.com)

---

## Quick start: the easy way (5 minutes, nothing to install on your computer)

1. **Install the plugin.** Download `openwp-bridge.zip` from the [latest release](https://github.com/AiNextStudioIT/openwp/releases/latest). In WordPress go to *Plugins > Add New > Upload Plugin*, upload the zip and activate it.
2. **Connect your AI.** Open **OpenWP** in the admin menu, then *Connect your AI*. Three steps: create an Application Password, type your user name and that password, copy the command it builds.
3. **Paste the command** in Claude Code (Cursor and other clients: the same page gives the address and the header). Then talk to your site.

The plugin shows a green "OpenWP connected" light in the admin bar, keeps a log of what your AI did, and saves a copy before every layout change.

## What your AI can do

- **Content:** posts, pages, custom post types, media, comments, users, plugins and themes, site settings, search. 56 tools in total (list below).
- **Page layouts:** read and change pages built with **Elementor, Bricks, Divi** or the block editor, with a **safety copy before every change** and a tool to put it back.
- **SEO fields:** Yoast SEO, Rank Math, All in One SEO, SEOPress.
- **WooCommerce:** products, orders, customers.
- **The tools of your other plugins:** plugins that publish tools for AI with the WordPress Abilities API (WooCommerce, Elementor, Bricks, SEO plugins, forms...) are offered through the same address. For Elementor, Bricks and WooCommerce you choose who does the job: **OpenWP, the plugin's own tools, or both**.
- **Skills and memory:** short guides your AI reads before working with a builder or plugin, and notes it keeps about your site between sessions.
- **Developer mode** (off by default, turns itself off after 1, 8 or 24 hours): run PHP, read the database, read and write files in `wp-content`. For test and staging sites only. You can forbid it for good with `define( 'OPENWP_BRIDGE_DISABLE_DEV', true );` in `wp-config.php`.

The plugin is not on WordPress.org on purpose, because of Developer mode.

---

## How it works, in plain words

Think of OpenWP as a **translator** between your AI and your site. You talk to your AI as usual. The AI asks OpenWP to do it, and OpenWP does it on the site. The site only sees a normal user with an **Application Password**: a key you create in your profile and can delete at any time to cut the access.

## Prefer no plugin?

OpenWP also works **without any plugin**: a small program that runs on your computer (Node 18+) and talks to your site through the official REST API. You get the content tools, the skills and the memory; layouts, SEO fields, safety copies and Developer mode need the plugin. Step by step below.

---

## No plugin: the OpenWP program, step by step

- Node.js 18+
- WordPress 5.6+ with REST API enabled (default)
- A WordPress Application Password

### Setup

### Step 1 — Generate a WordPress Application Password

1. Log in to your WordPress admin panel
2. Go to **Users → Your Profile**
3. Scroll down to the **Application Passwords** section
4. Type a name (e.g. `OpenWP`) in the field
5. Click **Add New Application Password**
6. Copy the generated string (it looks like `xxxx xxxx xxxx xxxx xxxx xxxx`) — you'll only see it once

> **Note:** If you use Wordfence, it may disable Application Passwords by default.
> Go to Wordfence → Login Security → disable "Block Application Passwords" to enable them.

---

### Step 2 — Download OpenWP

```bash
git clone https://github.com/AiNextStudioIT/openwp.git
cd openwp
npm install
```

---

### Step 3 — Connect to your AI agent

#### Claude Code (recommended)

Run this command once in your terminal:

```bash
claude mcp add openwp node "/path/to/openwp/index.js" \
  -e WP_URL=https://yoursite.com \
  -e WP_USERNAME=your-username \
  -e "WP_APP_PASSWORD=xxxx xxxx xxxx xxxx xxxx xxxx" \
  --scope user
```

Replace `/path/to/openwp/index.js` with the actual path, then open a new Claude Code session. Done.

#### Claude Desktop

Replace `/path/to/openwp/index.js` with the real path of the folder you downloaded in Step 2, and add this to your `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "openwp": {
      "command": "node",
      "args": ["/path/to/openwp/index.js"],
      "env": {
        "WP_URL": "https://yoursite.com",
        "WP_USERNAME": "your-username",
        "WP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

Restart Claude Desktop.

#### Cursor / Windsurf / VS Code

Add this to your MCP config file:

```json
{
  "mcpServers": {
    "openwp": {
      "command": "node",
      "args": ["/path/to/openwp/index.js"],
      "env": {
        "WP_URL": "https://yoursite.com",
        "WP_USERNAME": "your-username",
        "WP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

---

### Step 4 — Talk to your site

Once connected, just use natural language:

```
"Show me all draft posts"
"Publish a post titled 'AI Trends 2026' in the Technology category"
"Add a new editor user: mario@example.com"
"Install Elementor and activate it"
"What plugins are currently active?"
"Update the site tagline to 'Built with AI'"
"How many users does this site have?"
"Show me all pending comments"
"Approve comment #42"
"Upload this image to the media library: https://example.com/photo.jpg"
"Show me all WooCommerce orders from this week"
"Mark order #123 as completed"
"Create a product called 'AI Course' priced at 97.00"
"Publish all draft posts at once"
"What custom post types does this site have?"
```

---

## Available Tools (56 total)

### Content

| Tool | Description |
|------|-------------|
| `get_posts` | List posts with filters (status, category, tag, search) |
| `get_post` | Get single post by ID |
| `create_post` | Create a post |
| `update_post` | Update a post |
| `delete_post` | Delete a post (trash or permanent) |
| `bulk_update_posts` | Change status of multiple posts at once |
| `get_pages` | List pages |
| `create_page` | Create a page |
| `update_page` | Update a page |
| `get_custom_posts` | Get posts from any custom post type (CPT) |
| `search_content` | Search across all content |

### Taxonomy

| Tool | Description |
|------|-------------|
| `get_categories` | List categories |
| `create_category` | Create a category |
| `get_tags` | List tags |
| `create_tag` | Create a tag |

### Media

| Tool | Description |
|------|-------------|
| `get_media` | Browse media library |
| `upload_media_from_url` | Download a file from URL and upload to media library |

### Comments

| Tool | Description |
|------|-------------|
| `get_comments` | List comments (filter by post, status) |
| `moderate_comment` | Approve, hold, spam or trash a comment |
| `delete_comment` | Permanently delete a comment |

### Users

| Tool | Description |
|------|-------------|
| `get_users` | List users |
| `create_user` | Create a user |
| `update_user` | Update a user |

### Plugins & Themes

| Tool | Description |
|------|-------------|
| `get_plugins` | List installed plugins |
| `install_plugin` | Install a plugin from WordPress.org by name or slug |
| `activate_plugin` | Activate an installed plugin |
| `deactivate_plugin` | Deactivate a plugin |
| `delete_plugin` | Delete a plugin |
| `get_themes` | List installed themes |
| `install_theme` | Install a theme from WordPress.org |
| `activate_theme` | Activate an installed theme |

### Site Settings

| Tool | Description |
|------|-------------|
| `get_site_info` | Get site settings (title, URL, timezone, language) |
| `update_site_settings` | Update site title, tagline, email, timezone, etc. |
| `get_post_types` | List all available post types including CPT |

### WooCommerce *(requires WooCommerce active)*

| Tool | Description |
|------|-------------|
| `get_woo_products` | List products |
| `create_woo_product` | Create a product |
| `update_woo_product` | Update a product |
| `get_woo_orders` | List orders (filter by status, customer, date) |
| `update_woo_order` | Update order status or add customer note |
| `get_woo_customers` | List customers |

### Skills, stack detection and memory

| Tool | What it does |
|---|---|
| `detect_stack` | Looks at the active plugins and theme and returns the matching skills |
| `get_skill` | Reads a skill: how to work with one builder, theme or plugin (data location, what works today, rules). Without a name it lists all skills |
| `get_content_raw` | Reads the raw content of a post or page (block markup, shortcodes) before editing it |
| `memory_write` / `memory_read` / `memory_search` / `memory_delete` | Notes about the site that survive between sessions. Stored on your computer in `~/.openwp/memory` |

### OpenWP Bridge tools (need the Bridge plugin)

| Tool | What it does |
|---|---|
| `bridge_status` | Checks the Bridge is active, and which builders and SEO plugins it sees |
| `get_builder_data` / `set_builder_data` | Read and write the layout of a page (Elementor, Bricks, Divi, Gutenberg) |
| `list_builder_backups` / `restore_builder_backup` | List the saved copies and put one back |
| `set_page_meta` | Write SEO fields (Yoast, Rank Math, AIOSEO, SEOPress) |
| `dev_run_php` / `dev_run_sql` / `dev_files` | Developer mode only: run PHP, read the database, read/write files in `wp-content` |

Skills are Markdown files in the `skills/` folder, one per product, marked `verified` or `unverified`. Adding one is a pull request. Today: Gutenberg, Divi, Elementor, Bricks, WooCommerce, Yoast SEO, Rank Math, OpenWP Bridge.

Sites with plain permalinks (no `/wp-json/`) work too: OpenWP falls back to `?rest_route=` by itself.

---

## Troubleshooting

**Application Passwords section is missing or disabled**
→ Wordfence disables them by default. Go to Wordfence → Login Security → uncheck "Disable WordPress Application Passwords".

**401 Unauthorized error**
→ Make sure you're using an Application Password (not your regular login password). The format is `xxxx xxxx xxxx xxxx xxxx xxxx` with spaces.

**Plugin install fails**
→ Your WordPress hosting must allow filesystem writes to the plugins directory. Most managed hosting supports this. Some hardened VPS configurations may not.

**REST API returns 404**
→ Make sure Permalinks are not set to "Plain" in WordPress Settings → Permalinks.

---

## Learn more

- [What is MCP (Model Context Protocol) and how it works](https://raffaelemele.it/mcp-model-context-protocol-cose-come-funziona/) (in Italian)
- [AI agents: what they are and how they work](https://raffaelemele.it/agenti-ai-cosa-sono-come-funzionano/) (in Italian)
- [Elementor MCP: what it is and how it works](https://servizi-wp.it/elementor-mcp) (in Italian)

## License

MIT — free forever.

## By

[AI Next Studio](https://ainextstudio.it) · [GitHub Org](https://github.com/AiNextStudioIT)
