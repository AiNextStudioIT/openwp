# OpenWP

Control WordPress with AI. Free. No plugin required. Forever.

OpenWP is an MCP server that connects Claude, Cursor, Windsurf and any AI agent to your WordPress site via REST API — without installing anything on the server.

## Requirements

- Node.js 18+
- WordPress 5.6+ with REST API enabled (default)
- A WordPress Application Password

## Setup

### 1. Generate an Application Password

In WordPress: **Users → Your Profile → Application Passwords**

Add a new password (e.g. "Claude"), copy the generated string.

### 2. Add to your MCP config

**Claude Code (`~/.claude/settings.json`):**
```json
{
  "mcpServers": {
    "openwp": {
      "command": "node",
      "args": ["/path/to/openwp/index.js"],
      "env": {
        "WP_URL": "https://yoursite.com",
        "WP_USERNAME": "admin",
        "WP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

**Claude Desktop (`claude_desktop_config.json`):**
```json
{
  "mcpServers": {
    "openwp": {
      "command": "npx",
      "args": ["-y", "openwp"],
      "env": {
        "WP_URL": "https://yoursite.com",
        "WP_USERNAME": "admin",
        "WP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

### 3. Talk to your site

```
"Publish a post titled 'AI Trends 2026' in the Technology category"
"Show me all draft posts"
"Add a new editor user: mario@example.com"
"Update the site tagline to 'Built with AI'"
```

## Available Tools

| Tool | Description |
|------|-------------|
| `get_posts` | List posts with filters |
| `get_post` | Get single post by ID |
| `create_post` | Create a post |
| `update_post` | Update a post |
| `delete_post` | Delete a post |
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
| `get_site_info` | Get site settings |
| `search_content` | Search across all content |

## License

MIT — free forever.

## By

[AI Next Studio](https://ainextstudio.it) · [GitHub Org](https://github.com/AiNextStudioIT)
