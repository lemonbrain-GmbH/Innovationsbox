#!/usr/bin/env node

/**
 * Pre-renders all Storybook stories by fetching HTML from the PHP story server.
 * Outputs a JSON map keyed by "componentId|argsJSON" so the static Storybook
 * build can serve components without a live PHP backend.
 *
 * Usage: node scripts/prerender-stories.mjs
 * Requires: PHP story server running on port 6007
 */

import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join, resolve } from 'path';

const COMPONENTS_DIR = resolve('public/packages/inno_frontend/components');
const OUTPUT_FILE = resolve('.storybook/prerendered.json');
const SERVER_URL = 'http://localhost:6007';

function findStoryFiles(dir) {
  const results = [];
  for (const entry of readdirSync(dir)) {
    const full = join(dir, entry);
    if (statSync(full).isDirectory()) {
      results.push(...findStoryFiles(full));
    } else if (entry.endsWith('.stories.json')) {
      results.push(full);
    }
  }
  return results;
}

async function fetchWithRetry(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.text();
    } catch (err) {
      if (i === retries - 1) throw err;
      await new Promise((r) => setTimeout(r, 500));
    }
  }
}

async function main() {
  const storyFiles = findStoryFiles(COMPONENTS_DIR);
  const rendered = {};
  let total = 0;
  let failed = 0;

  for (const file of storyFiles) {
    const config = JSON.parse(readFileSync(file, 'utf-8'));
    if (!config.stories) continue;

    for (const story of config.stories) {
      const id = story.parameters?.server?.id;
      if (!id) continue;

      const args = story.args || {};
      const key = `${id}|${JSON.stringify(args)}`;
      const params = new URLSearchParams(args);
      const url = `${SERVER_URL}/${id}${params.toString() ? '?' + params : ''}`;

      try {
        rendered[key] = await fetchWithRetry(url);
        total++;
      } catch (err) {
        console.error(`FAIL: ${url} — ${err.message}`);
        failed++;
      }
    }
  }

  writeFileSync(OUTPUT_FILE, JSON.stringify(rendered, null, 2));
  console.log(
    `Pre-rendered ${total} stories to ${OUTPUT_FILE}` +
      (failed ? ` (${failed} failed)` : ''),
  );
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
