// Persistent memory for the agent, one JSON file per site, in ~/.openwp/memory.
// Notes survive between sessions. Nothing is sent anywhere: it stays on this computer.
import { mkdirSync, readFileSync, writeFileSync, existsSync } from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';

function file(siteUrl) {
  const host = new URL(siteUrl).host.replace(/[^a-z0-9.-]/gi, '_');
  const dir = process.env.OPENWP_MEMORY_DIR || join(homedir(), '.openwp', 'memory');
  mkdirSync(dir, { recursive: true });
  return join(dir, `${host}.json`);
}

function load(siteUrl) {
  const f = file(siteUrl);
  return existsSync(f) ? JSON.parse(readFileSync(f, 'utf8')) : {};
}

function save(siteUrl, data) {
  writeFileSync(file(siteUrl), JSON.stringify(data, null, 2), { mode: 0o600 });
}

export function memoryRead(siteUrl, key) {
  const data = load(siteUrl);
  if (key) {
    if (!(key in data)) throw new Error(`No memory with key "${key}".`);
    return { key, ...data[key] };
  }
  return Object.entries(data).map(([k, v]) => ({ key: k, updated: v.updated, preview: String(v.note).slice(0, 120) }));
}

export function memoryWrite(siteUrl, key, note) {
  if (!key || !note) throw new Error('key and note are required.');
  const data = load(siteUrl);
  data[key] = { note: String(note), updated: new Date().toISOString() };
  save(siteUrl, data);
  return { saved: key, updated: data[key].updated };
}

export function memorySearch(siteUrl, query) {
  const q = String(query || '').toLowerCase();
  return Object.entries(load(siteUrl))
    .filter(([k, v]) => k.toLowerCase().includes(q) || String(v.note).toLowerCase().includes(q))
    .map(([k, v]) => ({ key: k, updated: v.updated, note: v.note }));
}

export function memoryDelete(siteUrl, key) {
  const data = load(siteUrl);
  if (!(key in data)) throw new Error(`No memory with key "${key}".`);
  delete data[key];
  save(siteUrl, data);
  return { deleted: key };
}
