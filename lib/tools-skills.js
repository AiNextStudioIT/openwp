// Tools for skills, stack detection, memory and raw content reading.
// Kept apart from index.js so each part stays small.
import { listSkills, getSkill, detectStack } from './skills.js';
import { memoryRead, memoryWrite, memorySearch, memoryDelete } from './memory.js';

export const EXTRA_TOOLS = [
  {
    name: 'detect_stack',
    description: 'Look at the active plugins and theme of the site and return the skills that match (builders, WooCommerce, SEO plugins...). Call it first on a new site, then read the matching skills with get_skill before changing layouts or SEO data.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'get_skill',
    description: 'Read a skill: how to work with one builder, theme or plugin (where its data lives, what OpenWP can do today, rules). Without a name it lists all the skills with their status (verified or unverified).',
    inputSchema: { type: 'object', properties: { name: { type: 'string', description: 'Skill name, for example "gutenberg" or "elementor"' } } },
  },
  {
    name: 'get_content_raw',
    description: 'Read the RAW content of a post, page or custom post type item (block markup, shortcodes), exactly as stored. Use it before editing a page, so you change only what was asked. get_post returns the rendered HTML instead.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Item ID' },
        type: { type: 'string', description: 'posts, pages, or the REST base of a custom type (default: pages)', default: 'pages' },
      },
      required: ['id'],
    },
  },
  {
    name: 'memory_write',
    description: 'Save a note about this site that survives between sessions (decisions, what was done, preferences, things to remember). Stored on this computer, never sent anywhere. Same key replaces the old note.',
    inputSchema: {
      type: 'object',
      properties: { key: { type: 'string', description: 'Short name of the note' }, note: { type: 'string', description: 'The note' } },
      required: ['key', 'note'],
    },
  },
  {
    name: 'memory_read',
    description: 'Read the memory of this site: without a key it lists all notes, with a key it returns that note.',
    inputSchema: { type: 'object', properties: { key: { type: 'string', description: 'Note key (optional)' } } },
  },
  {
    name: 'memory_search',
    description: 'Search the notes of this site by a word in the key or in the text.',
    inputSchema: { type: 'object', properties: { query: { type: 'string', description: 'Word or phrase' } }, required: ['query'] },
  },
  {
    name: 'memory_delete',
    description: 'Delete one note of this site.',
    inputSchema: { type: 'object', properties: { key: { type: 'string' } }, required: ['key'] },
  },
];

const NAMES = new Set(EXTRA_TOOLS.map(t => t.name));

export function isExtraTool(name) {
  return NAMES.has(name);
}

export async function callExtraTool(name, args, { wp, siteUrl }) {
  switch (name) {
    case 'detect_stack': {
      const [plugins, themes] = await Promise.all([wp('GET', '/plugins'), wp('GET', '/themes')]);
      const skills = detectStack(plugins, themes);
      return {
        active_theme: (themes.find(t => t.status === 'active') || {}).stylesheet || null,
        matching_skills: skills.map(s => ({ name: s.name, title: s.title, status: s.status })),
        next: 'Read the matching skills with get_skill before changing layouts or SEO data.',
      };
    }
    case 'get_skill':
      return args.name ? getSkill(args.name) : listSkills();
    case 'get_content_raw': {
      const type = String(args.type || 'pages').replace(/[^a-z0-9_-]/gi, '');
      const p = await wp('GET', `/${type}/${args.id}?context=edit`);
      return { id: p.id, type: p.type, status: p.status, title: p.title && p.title.raw, content: p.content && p.content.raw };
    }
    case 'memory_write':
      return memoryWrite(siteUrl, args.key, args.note);
    case 'memory_read':
      return memoryRead(siteUrl, args.key);
    case 'memory_search':
      return memorySearch(siteUrl, args.query);
    case 'memory_delete':
      return memoryDelete(siteUrl, args.key);
    default:
      throw new Error(`Unknown tool: ${name}`);
  }
}
