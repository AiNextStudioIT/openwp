# OpenWP

Control WordPress with AI. Free. No plugin required. Forever.

OpenWP is an MCP server that connects Claude, Cursor, Windsurf and any AI agent to your WordPress site via REST API — without installing anything on the server.

**Landing page:** [openwp.ainextstudio.it](https://openwp.ainextstudio.it)

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
6. Copy the generated string (e.g. `yCh9 LJkU ZQRb ZWxd N6i8 mj1d`) — you'll only see it once

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

Add this to your `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "openwp": {
      "command": "npx",
      "args": ["-y", "openwp"],
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
"Install the Elementor plugin and activate it"
"What plugins are currently active?"
"Update the site tagline to 'Built with AI'"
"How many users does this site have?"
```

---

## Available Tools (26 total)

| Tool | Description |
|------|-------------|
| `get_posts` | List posts with filters (status, category, tag, search) |
| `get_post` | Get single post by ID |
| `create_post` | Create a post |
| `update_post` | Update a post |
| `delete_post` | Delete a post (trash or permanent) |
| `get_pages` | List pages |
| `create_page` | Create a page |
| `update_page` | Update a page |
| `get_categories` | List categories |
| `create_category` | Create a category |
| `get_tags` | List tags |
| `create_tag` | Create a tag |
| `get_users` | List users |
| `create_user` | Create a user |
| `update_user` | Update a user |
| `get_media` | Browse media library |
| `get_plugins` | List installed plugins |
| `install_plugin` | Install a plugin from WordPress.org |
| `activate_plugin` | Activate an installed plugin |
| `deactivate_plugin` | Deactivate a plugin |
| `delete_plugin` | Delete a plugin |
| `get_themes` | List installed themes |
| `install_theme` | Install a theme from WordPress.org |
| `activate_theme` | Activate an installed theme |
| `get_site_info` | Get site settings (title, URL, timezone, language) |
| `search_content` | Search across all content |

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

## License

MIT — free forever.

## By

[AI Next Studio](https://ainextstudio.it) · [GitHub Org](https://github.com/AiNextStudioIT)
