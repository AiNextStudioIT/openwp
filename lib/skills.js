// Skills: one Markdown file per plugin, theme or builder in /skills.
// Front matter (between --- lines): name, title, detects (comma separated: core | plugin:<slug> | theme:<stylesheet>),
// status (verified | unverified), tested (where it was tested).
import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIR = join(dirname(fileURLToPath(import.meta.url)), '..', 'skills');

function parse(file) {
  const raw = readFileSync(join(DIR, file), 'utf8');
  const m = raw.match(/^---\n([\s\S]*?)\n---\n?([\s\S]*)$/);
  const meta = {};
  if (m) {
    for (const line of m[1].split('\n')) {
      const i = line.indexOf(':');
      if (i > 0) meta[line.slice(0, i).trim()] = line.slice(i + 1).trim();
    }
  }
  return { meta, body: m ? m[2].trim() : raw.trim() };
}

export function listSkills() {
  return readdirSync(DIR).filter(f => f.endsWith('.md')).map(f => {
    const { meta } = parse(f);
    return {
      name: meta.name || f.replace(/\.md$/, ''),
      title: meta.title || '',
      status: meta.status || 'unverified',
      detects: (meta.detects || '').split(',').map(s => s.trim()).filter(Boolean),
      tested: meta.tested || '',
    };
  });
}

export function getSkill(name) {
  const file = readdirSync(DIR).find(f => f === `${name}.md`);
  if (!file) throw new Error(`Skill not found: "${name}". Use get_skill without a name to list them.`);
  const { meta, body } = parse(file);
  return { name, title: meta.title || '', status: meta.status || 'unverified', tested: meta.tested || '', content: body };
}

// plugins: items from /plugins (plugin = "folder/file.php", status); themes: items from /themes (stylesheet, status)
export function detectStack(plugins, themes) {
  const activePlugins = plugins.filter(p => p.status === 'active').map(p => String(p.plugin).split('/')[0].replace(/\.php$/, '').toLowerCase());
  const activeThemes = themes.filter(t => t.status === 'active').map(t => String(t.stylesheet).toLowerCase());
  return listSkills().filter(s => s.detects.some(d => {
    if (d === 'core') return true;
    const [kind, slug] = d.split(':');
    if (kind === 'plugin') return activePlugins.includes(slug.toLowerCase());
    if (kind === 'theme') return activeThemes.includes(slug.toLowerCase());
    return false;
  }));
}
