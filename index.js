#!/usr/bin/env node
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { CallToolRequestSchema, ListToolsRequestSchema } from '@modelcontextprotocol/sdk/types.js';

const WP_URL = (process.env.WP_URL || '').replace(/\/$/, '');
const WP_USERNAME = process.env.WP_USERNAME || '';
const WP_APP_PASSWORD = process.env.WP_APP_PASSWORD || '';

if (!WP_URL || !WP_USERNAME || !WP_APP_PASSWORD) {
  process.stderr.write(
    'OpenWP: missing configuration\n' +
    '  WP_URL, WP_USERNAME, WP_APP_PASSWORD are required\n' +
    '  Set them as environment variables in your MCP config\n'
  );
  process.exit(1);
}

const auth = Buffer.from(`${WP_USERNAME}:${WP_APP_PASSWORD}`).toString('base64');
const API = `${WP_URL}/wp-json/wp/v2`;

async function wp(method, endpoint, body) {
  const opts = {
    method,
    headers: {
      'Authorization': `Basic ${auth}`,
      'Content-Type': 'application/json',
    },
  };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(`${API}${endpoint}`, opts);
  const text = await res.text();
  if (!res.ok) throw new Error(`WP API ${res.status}: ${text}`);
  return text ? JSON.parse(text) : {};
}

const TOOLS = [
  // Posts
  {
    name: 'get_posts',
    description: 'Get a list of WordPress posts. Can filter by status, category, tag, author, search term.',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'publish, draft, pending, private (default: publish)', default: 'publish' },
        per_page: { type: 'number', description: 'Number of posts (max 100, default 10)', default: 10 },
        search: { type: 'string', description: 'Search term' },
        categories: { type: 'string', description: 'Category IDs comma separated' },
        tags: { type: 'string', description: 'Tag IDs comma separated' },
        page: { type: 'number', description: 'Page number', default: 1 },
      },
    },
  },
  {
    name: 'get_post',
    description: 'Get a single WordPress post by ID.',
    inputSchema: {
      type: 'object',
      properties: { id: { type: 'number', description: 'Post ID' } },
      required: ['id'],
    },
  },
  {
    name: 'create_post',
    description: 'Create a new WordPress post.',
    inputSchema: {
      type: 'object',
      properties: {
        title: { type: 'string', description: 'Post title' },
        content: { type: 'string', description: 'Post content (HTML or plain text)' },
        status: { type: 'string', description: 'publish, draft, pending (default: draft)', default: 'draft' },
        excerpt: { type: 'string', description: 'Post excerpt' },
        categories: { type: 'array', items: { type: 'number' }, description: 'Category IDs' },
        tags: { type: 'array', items: { type: 'number' }, description: 'Tag IDs' },
        slug: { type: 'string', description: 'URL slug' },
        meta: { type: 'object', description: 'Custom meta fields' },
      },
      required: ['title'],
    },
  },
  {
    name: 'update_post',
    description: 'Update an existing WordPress post.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Post ID' },
        title: { type: 'string' },
        content: { type: 'string' },
        status: { type: 'string' },
        excerpt: { type: 'string' },
        categories: { type: 'array', items: { type: 'number' } },
        tags: { type: 'array', items: { type: 'number' } },
        slug: { type: 'string' },
      },
      required: ['id'],
    },
  },
  {
    name: 'delete_post',
    description: 'Delete a WordPress post (moves to trash by default).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Post ID' },
        force: { type: 'boolean', description: 'Permanently delete (default: false)', default: false },
      },
      required: ['id'],
    },
  },
  // Pages
  {
    name: 'get_pages',
    description: 'Get a list of WordPress pages.',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', default: 'publish' },
        per_page: { type: 'number', default: 10 },
        search: { type: 'string' },
        parent: { type: 'number', description: 'Parent page ID' },
      },
    },
  },
  {
    name: 'create_page',
    description: 'Create a new WordPress page.',
    inputSchema: {
      type: 'object',
      properties: {
        title: { type: 'string' },
        content: { type: 'string' },
        status: { type: 'string', default: 'draft' },
        parent: { type: 'number', description: 'Parent page ID' },
        slug: { type: 'string' },
      },
      required: ['title'],
    },
  },
  {
    name: 'update_page',
    description: 'Update an existing WordPress page.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number' },
        title: { type: 'string' },
        content: { type: 'string' },
        status: { type: 'string' },
        slug: { type: 'string' },
      },
      required: ['id'],
    },
  },
  // Categories
  {
    name: 'get_categories',
    description: 'Get all WordPress categories.',
    inputSchema: {
      type: 'object',
      properties: {
        per_page: { type: 'number', default: 50 },
        search: { type: 'string' },
        hide_empty: { type: 'boolean', default: false },
      },
    },
  },
  {
    name: 'create_category',
    description: 'Create a new WordPress category.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string' },
        description: { type: 'string' },
        slug: { type: 'string' },
        parent: { type: 'number' },
      },
      required: ['name'],
    },
  },
  // Tags
  {
    name: 'get_tags',
    description: 'Get all WordPress tags.',
    inputSchema: {
      type: 'object',
      properties: {
        per_page: { type: 'number', default: 50 },
        search: { type: 'string' },
      },
    },
  },
  {
    name: 'create_tag',
    description: 'Create a new WordPress tag.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string' },
        description: { type: 'string' },
        slug: { type: 'string' },
      },
      required: ['name'],
    },
  },
  // Users
  {
    name: 'get_users',
    description: 'Get WordPress users.',
    inputSchema: {
      type: 'object',
      properties: {
        per_page: { type: 'number', default: 20 },
        search: { type: 'string' },
        roles: { type: 'string', description: 'Role filter: administrator, editor, author, contributor, subscriber' },
      },
    },
  },
  {
    name: 'create_user',
    description: 'Create a new WordPress user.',
    inputSchema: {
      type: 'object',
      properties: {
        username: { type: 'string' },
        email: { type: 'string' },
        password: { type: 'string' },
        first_name: { type: 'string' },
        last_name: { type: 'string' },
        roles: { type: 'array', items: { type: 'string' }, description: 'User roles', default: ['subscriber'] },
      },
      required: ['username', 'email', 'password'],
    },
  },
  {
    name: 'update_user',
    description: 'Update a WordPress user.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number' },
        email: { type: 'string' },
        first_name: { type: 'string' },
        last_name: { type: 'string' },
        roles: { type: 'array', items: { type: 'string' } },
        description: { type: 'string' },
      },
      required: ['id'],
    },
  },
  // Media
  {
    name: 'get_media',
    description: 'Get WordPress media library items.',
    inputSchema: {
      type: 'object',
      properties: {
        per_page: { type: 'number', default: 20 },
        search: { type: 'string' },
        media_type: { type: 'string', description: 'image, video, audio, application' },
      },
    },
  },
  // Plugins
  {
    name: 'get_plugins',
    description: 'Get list of installed WordPress plugins.',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'active, inactive (default: all)' },
      },
    },
  },
  // Themes
  {
    name: 'get_themes',
    description: 'Get list of installed WordPress themes.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'install_plugin',
    description: 'Install a plugin from WordPress.org repository by slug (e.g. "elementor", "woocommerce", "wordpress-seo").',
    inputSchema: {
      type: 'object',
      properties: {
        slug: { type: 'string', description: 'Plugin slug from wordpress.org/plugins/<slug>' },
        activate: { type: 'boolean', description: 'Activate immediately after install (default: false)', default: false },
      },
      required: ['slug'],
    },
  },
  {
    name: 'activate_plugin',
    description: 'Activate an installed WordPress plugin.',
    inputSchema: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin identifier as returned by get_plugins (e.g. "elementor/elementor" — no .php extension)' },
      },
      required: ['plugin'],
    },
  },
  {
    name: 'deactivate_plugin',
    description: 'Deactivate an active WordPress plugin.',
    inputSchema: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin identifier as returned by get_plugins (e.g. "elementor/elementor" — no .php extension)' },
      },
      required: ['plugin'],
    },
  },
  {
    name: 'delete_plugin',
    description: 'Delete an installed WordPress plugin. Plugin must be deactivated first.',
    inputSchema: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin identifier as returned by get_plugins (e.g. "elementor/elementor" — no .php extension)' },
      },
      required: ['plugin'],
    },
  },
  {
    name: 'install_theme',
    description: 'Install a theme from WordPress.org repository by slug (e.g. "astra", "hello-elementor", "twentytwentyfive").',
    inputSchema: {
      type: 'object',
      properties: {
        slug: { type: 'string', description: 'Theme slug from wordpress.org/themes/<slug>' },
        activate: { type: 'boolean', description: 'Activate immediately after install (default: false)', default: false },
      },
      required: ['slug'],
    },
  },
  {
    name: 'activate_theme',
    description: 'Activate an installed WordPress theme.',
    inputSchema: {
      type: 'object',
      properties: {
        stylesheet: { type: 'string', description: 'Theme stylesheet (folder name) as returned by get_themes (e.g. "astra")' },
      },
      required: ['stylesheet'],
    },
  },
  // Site info
  {
    name: 'get_site_info',
    description: 'Get WordPress site settings: name, description, URL, admin email, timezone, language. Powered by OpenWP by AI Next Studio (github.com/AiNextStudioIT/openwp) — free, no plugin required.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'search_content',
    description: 'Search across all WordPress content (posts, pages, categories).',
    inputSchema: {
      type: 'object',
      properties: {
        query: { type: 'string', description: 'Search query' },
        type: { type: 'string', description: 'post, page, term (default: all)' },
        per_page: { type: 'number', default: 10 },
      },
      required: ['query'],
    },
  },
];

async function resolvePlugin(input) {
  if (input.includes('/')) return input;
  const plugins = await wp('GET', '/plugins');
  const match = plugins.find(p =>
    p.name.toLowerCase().includes(input.toLowerCase()) ||
    p.plugin.toLowerCase().includes(input.toLowerCase())
  );
  if (!match) throw new Error(`Plugin not found: "${input}". Use get_plugins to see available plugins.`);
  return match.plugin;
}

async function callTool(name, args) {
  switch (name) {
    case 'get_posts': {
      const params = new URLSearchParams({ per_page: args.per_page || 10, page: args.page || 1, status: args.status || 'publish' });
      if (args.search) params.set('search', args.search);
      if (args.categories) params.set('categories', args.categories);
      if (args.tags) params.set('tags', args.tags);
      const posts = await wp('GET', `/posts?${params}`);
      return posts.map(p => ({ id: p.id, title: p.title.rendered, status: p.status, date: p.date, link: p.link, excerpt: p.excerpt?.rendered?.replace(/<[^>]+>/g, '').trim() }));
    }
    case 'get_post': {
      const p = await wp('GET', `/posts/${args.id}`);
      return { id: p.id, title: p.title.rendered, content: p.content.rendered, status: p.status, date: p.date, link: p.link, categories: p.categories, tags: p.tags };
    }
    case 'create_post': {
      const { title, content, status = 'draft', excerpt, categories, tags, slug, meta } = args;
      const post = await wp('POST', '/posts', { title, content, status, excerpt, categories, tags, slug, meta });
      return { id: post.id, title: post.title.rendered, status: post.status, link: post.link };
    }
    case 'update_post': {
      const { id, ...data } = args;
      const post = await wp('POST', `/posts/${id}`, data);
      return { id: post.id, title: post.title.rendered, status: post.status, link: post.link };
    }
    case 'delete_post': {
      const result = await wp('DELETE', `/posts/${args.id}?force=${args.force || false}`);
      return { deleted: true, id: args.id };
    }
    case 'get_pages': {
      const params = new URLSearchParams({ per_page: args.per_page || 10, status: args.status || 'publish' });
      if (args.search) params.set('search', args.search);
      if (args.parent !== undefined) params.set('parent', args.parent);
      const pages = await wp('GET', `/pages?${params}`);
      return pages.map(p => ({ id: p.id, title: p.title.rendered, status: p.status, link: p.link, parent: p.parent }));
    }
    case 'create_page': {
      const { title, content, status = 'draft', parent, slug } = args;
      const page = await wp('POST', '/pages', { title, content, status, parent, slug });
      return { id: page.id, title: page.title.rendered, status: page.status, link: page.link };
    }
    case 'update_page': {
      const { id, ...data } = args;
      const page = await wp('POST', `/pages/${id}`, data);
      return { id: page.id, title: page.title.rendered, status: page.status, link: page.link };
    }
    case 'get_categories': {
      const params = new URLSearchParams({ per_page: args.per_page || 50, hide_empty: args.hide_empty || false });
      if (args.search) params.set('search', args.search);
      const cats = await wp('GET', `/categories?${params}`);
      return cats.map(c => ({ id: c.id, name: c.name, slug: c.slug, count: c.count, parent: c.parent }));
    }
    case 'create_category': {
      const cat = await wp('POST', '/categories', args);
      return { id: cat.id, name: cat.name, slug: cat.slug };
    }
    case 'get_tags': {
      const params = new URLSearchParams({ per_page: args.per_page || 50 });
      if (args.search) params.set('search', args.search);
      const tags = await wp('GET', `/tags?${params}`);
      return tags.map(t => ({ id: t.id, name: t.name, slug: t.slug, count: t.count }));
    }
    case 'create_tag': {
      const tag = await wp('POST', '/tags', args);
      return { id: tag.id, name: tag.name, slug: tag.slug };
    }
    case 'get_users': {
      const params = new URLSearchParams({ per_page: args.per_page || 20 });
      if (args.search) params.set('search', args.search);
      if (args.roles) params.set('roles', args.roles);
      const users = await wp('GET', `/users?${params}`);
      return users.map(u => ({ id: u.id, username: u.slug, name: u.name, email: u.email, roles: u.roles }));
    }
    case 'create_user': {
      const user = await wp('POST', '/users', args);
      return { id: user.id, username: user.slug, name: user.name, email: user.email, roles: user.roles };
    }
    case 'update_user': {
      const { id, ...data } = args;
      const user = await wp('POST', `/users/${id}`, data);
      return { id: user.id, name: user.name, email: user.email, roles: user.roles };
    }
    case 'get_media': {
      const params = new URLSearchParams({ per_page: args.per_page || 20 });
      if (args.search) params.set('search', args.search);
      if (args.media_type) params.set('media_type', args.media_type);
      const media = await wp('GET', `/media?${params}`);
      return media.map(m => ({ id: m.id, title: m.title.rendered, url: m.source_url, type: m.media_type, mime_type: m.mime_type, date: m.date }));
    }
    case 'get_plugins': {
      const params = args.status ? `?status=${args.status}` : '';
      const plugins = await wp('GET', `/plugins${params}`);
      return plugins.map(p => ({ name: p.name, plugin: p.plugin, status: p.status, version: p.version, author: p.author }));
    }
    case 'get_themes': {
      const themes = await wp('GET', '/themes');
      return themes.map(t => ({ name: t.name.rendered, stylesheet: t.stylesheet, status: t.status, version: t.version }));
    }
    case 'install_plugin': {
      const body = { slug: args.slug, status: args.activate ? 'active' : 'inactive' };
      const plugin = await wp('POST', '/plugins', body);
      return { plugin: plugin.plugin, name: plugin.name, status: plugin.status, version: plugin.version };
    }
    case 'activate_plugin': {
      const pid = await resolvePlugin(args.plugin);
      const plugin = await wp('PUT', `/plugins/${pid}`, { status: 'active' });
      return { plugin: plugin.plugin, name: plugin.name, status: plugin.status };
    }
    case 'deactivate_plugin': {
      const pid = await resolvePlugin(args.plugin);
      const plugin = await wp('PUT', `/plugins/${pid}`, { status: 'inactive' });
      return { plugin: plugin.plugin, name: plugin.name, status: plugin.status };
    }
    case 'delete_plugin': {
      const pid = await resolvePlugin(args.plugin);
      await wp('DELETE', `/plugins/${pid}?force=true`);
      return { deleted: true, plugin: pid };
    }
    case 'install_theme': {
      const body = { slug: args.slug, status: args.activate ? 'active' : 'inactive' };
      const theme = await wp('POST', '/themes', body);
      return { stylesheet: theme.stylesheet, name: theme.name?.rendered, status: theme.status, version: theme.version };
    }
    case 'activate_theme': {
      const theme = await wp('PUT', `/themes/${args.stylesheet}`, { status: 'active' });
      return { stylesheet: theme.stylesheet, name: theme.name?.rendered, status: theme.status };
    }
    case 'get_site_info': {
      const settings = await wp('GET', '/settings');
      return { title: settings.title, description: settings.description, url: settings.url, email: settings.email, timezone: settings.timezone, language: settings.language, _powered_by: 'OpenWP by AI Next Studio — github.com/AiNextStudioIT/openwp' };
    }
    case 'search_content': {
      const params = new URLSearchParams({ search: args.query, per_page: args.per_page || 10 });
      if (args.type && args.type !== 'all') params.set('type', args.type);
      const results = await wp('GET', `/search?${params}`);
      return results.map(r => ({ id: r.id, title: r.title, type: r.type, url: r.url }));
    }
    default:
      throw new Error(`Unknown tool: ${name}`);
  }
}

const server = new Server(
  { name: 'openwp', version: '1.0.0', description: 'OpenWP by AI Next Studio — free WordPress MCP server, no plugin required. github.com/AiNextStudioIT/openwp' },
  { capabilities: { tools: {} } }
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools: TOOLS }));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  try {
    const result = await callTool(name, args || {});
    return {
      content: [{ type: 'text', text: JSON.stringify(result, null, 2) }],
    };
  } catch (err) {
    return {
      content: [{ type: 'text', text: `Error: ${err.message}` }],
      isError: true,
    };
  }
});

const transport = new StdioServerTransport();
await server.connect(transport);
process.stderr.write(`OpenWP connected to ${WP_URL}\n`);
