// Tools that talk to the optional OpenWP Bridge plugin on the site (route /wp-json/openwp/v1).
export const BRIDGE_TOOLS = [
  {
    name: 'bridge_status',
    description: 'Check that the OpenWP Bridge plugin is installed on the site. Returns its version, which builders and SEO plugins it sees, and whether Developer mode is on.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'get_builder_data',
    description: 'Read the layout of a page as the page builder stores it (Elementor: list of elements; Bricks: list of elements; Divi, Gutenberg: the content). Needs the OpenWP Bridge. Read the matching skill first (get_skill).',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Post or page ID' },
        area: { type: 'string', description: 'Bricks only: content, header or footer (default: content)' },
        builder: { type: 'string', description: 'Force a builder: elementor, bricks, divi, gutenberg (default: detected)' },
      },
      required: ['id'],
    },
  },
  {
    name: 'set_builder_data',
    description: 'Write the layout of a page. Send "data" (the full list of elements) for Elementor and Bricks, or "content" (the full text) for Divi and Gutenberg. A copy of the previous layout is saved first and its id is returned. Read the layout with get_builder_data, change only what was asked, send it all back. Needs the OpenWP Bridge.',
    inputSchema: {
      type: 'object',
      properties: {
        id: { type: 'number', description: 'Post or page ID' },
        data: { type: 'array', description: 'Elementor or Bricks: the whole list of elements', items: { type: 'object' } },
        content: { type: 'string', description: 'Divi or Gutenberg: the whole content' },
        builder: { type: 'string', description: 'elementor, bricks, divi or gutenberg (default: detected; needed for a page that has no builder yet)' },
        area: { type: 'string', description: 'Bricks only: content, header or footer' },
        note: { type: 'string', description: 'Short note about the change, kept with the backup' },
      },
      required: ['id'],
    },
  },
  {
    name: 'list_builder_backups',
    description: 'List the saved copies of a page layout (last 10), newest first. Needs the OpenWP Bridge.',
    inputSchema: { type: 'object', properties: { id: { type: 'number', description: 'Post or page ID' } }, required: ['id'] },
  },
  {
    name: 'restore_builder_backup',
    description: 'Put back a saved copy of a page layout. The layout in place now is saved first, so the restore can be undone. Needs the OpenWP Bridge.',
    inputSchema: {
      type: 'object',
      properties: { id: { type: 'number', description: 'Post or page ID' }, backup: { type: 'number', description: 'Backup id from list_builder_backups' } },
      required: ['id', 'backup'],
    },
  },
  {
    name: 'set_page_meta',
    description: 'Write SEO fields of one page: Yoast SEO (_yoast_wpseo_title, _yoast_wpseo_metadesc, _yoast_wpseo_meta-robots-noindex), Rank Math (rank_math_title, rank_math_description), All in One SEO, SEOPress, and the Divi switch _et_pb_use_builder. Only these keys are accepted. Needs the OpenWP Bridge.',
    inputSchema: {
      type: 'object',
      properties: { id: { type: 'number', description: 'Post or page ID' }, meta: { type: 'object', description: 'Key and value pairs, for example {"_yoast_wpseo_title":"..."}' } },
      required: ['id', 'meta'],
    },
  },
  {
    name: 'dev_run_php',
    description: 'DEVELOPER MODE. Run PHP code inside WordPress (without <?php) and get what it printed and returned. Works only if the site owner turned Developer mode on in Tools > OpenWP Bridge. For test and staging sites. Use "return" to send back a value.',
    inputSchema: { type: 'object', properties: { code: { type: 'string', description: 'PHP code, for example: return get_option("blogname");' } }, required: ['code'] },
  },
  {
    name: 'dev_run_sql',
    description: 'DEVELOPER MODE. Run one read-only SQL query (SELECT, SHOW, DESCRIBE, EXPLAIN) on the site database. To change data use dev_run_php.',
    inputSchema: { type: 'object', properties: { sql: { type: 'string' }, limit: { type: 'number', description: 'Max rows returned (default 200)' } }, required: ['sql'] },
  },
  {
    name: 'dev_files',
    description: 'DEVELOPER MODE. List a folder, read a file or write a file inside wp-content. Writing keeps a copy of the old file next to it.',
    inputSchema: {
      type: 'object',
      properties: {
        action: { type: 'string', description: 'list, read or write' },
        path: { type: 'string', description: 'Path inside wp-content, for example plugins/my-plugin/my-plugin.php' },
        content: { type: 'string', description: 'For write: the whole new content of the file' },
      },
      required: ['action', 'path'],
    },
  },
];

const NAMES = new Set(BRIDGE_TOOLS.map(t => t.name));

export function isBridgeTool(name) {
  return NAMES.has(name);
}

export async function callBridgeTool(name, args, bridge) {
  switch (name) {
    case 'bridge_status':
      return bridge('GET', '/status');
    case 'get_builder_data': {
      const q = new URLSearchParams();
      if (args.area) q.set('area', args.area);
      if (args.builder) q.set('builder', args.builder);
      return bridge('GET', `/post/${args.id}/builder${q.toString() ? '?' + q : ''}`);
    }
    case 'set_builder_data': {
      const { id, ...body } = args;
      return bridge('POST', `/post/${id}/builder`, body);
    }
    case 'list_builder_backups':
      return bridge('GET', `/post/${args.id}/backups`);
    case 'restore_builder_backup':
      return bridge('POST', `/post/${args.id}/restore`, { backup: args.backup });
    case 'set_page_meta':
      return bridge('POST', `/post/${args.id}/meta`, { meta: args.meta });
    case 'dev_run_php':
      return bridge('POST', '/dev/php', { code: args.code });
    case 'dev_run_sql':
      return bridge('POST', '/dev/sql', { sql: args.sql, limit: args.limit });
    case 'dev_files':
      return bridge('POST', '/dev/file', { action: args.action, path: args.path, content: args.content });
    default:
      throw new Error(`Unknown tool: ${name}`);
  }
}
