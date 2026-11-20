# OpenWP

Control WordPress with AI. Free. No plugin required. Forever.

OpenWP is an MCP server that connects Claude, Cursor, Windsurf and any AI agent to your WordPress site via REST API — without installing anything on the server.

**Landing page:** [openwp.ainextstudio.it](https://openwp.ainextstudio.it)

---

## How it works, in plain words

Think of OpenWP as a **translator** that sits on your computer.

1. You talk to your AI (Claude, Cursor...) as usual: "list my last 5 posts", "create a draft about X", "lower the price of that product".
2. The AI asks OpenWP to do it. OpenWP talks to your WordPress site and gives the answer back.
3. The site only sees a normal user logging in with an **Application Password** (a key you create in your profile, which you can delete at any time).

Nothing runs on a server of ours and nothing is installed on your site. That is why you will not see "OpenWP" inside WordPress, unless you add the optional [OpenWP Bridge](#openwp-bridge-optional-plugin) below, which also shows a green "connected" light.

**To install it you need three things, about 5 minutes:** (1) an Application Password from your WordPress profile, (2) this folder downloaded on your computer, (3) one command or one config entry that tells your AI where OpenWP is. The step-by-step guide is right below.

---

## OpenWP Bridge (optional plugin)

OpenWP works without it. Add the **OpenWP Bridge** plugin to the site when you want more:

- the AI can **read and change the layout** of pages built with **Elementor, Bricks, Divi** or the block editor, and fill the SEO fields of Yoast SEO and Rank Math;
- a **copy is saved before every change**, and one tool puts it back;
- a **green "OpenWP connected" light** in the admin bar and a page in *Tools > OpenWP Bridge* with a log of what the AI did;
- an optional **Developer mode** (off by default, turns itself off after 1, 8 or 24 hours): the AI can run PHP, read the database and read/write files in `wp-content`. For test and staging sites only. You can forbid it for good with `define( 'OPENWP_BRIDGE_DISABLE_DEV', true );` in `wp-config.php`.

**Install:** build the zip with `bridge/build.sh` (or download it from the releases), then in WordPress go to *Plugins > Add New > Upload Plugin*, upload `openwp-bridge.zip` and activate it. It is not on WordPress.org on purpose, because of Developer mode.

---

## Requirements

- Node.js 18+
- WordPress 5.6+ with REST API enabled (default)
- A WordPress Application Password

---

## Setup — Step by Step

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
