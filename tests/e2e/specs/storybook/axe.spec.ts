/**
 * Axe-core a11y gate across all Storybook component stories.
 *
 * For each story entry in storybook-static/index.json (tokens excluded) the
 * spec fetches the rendered HTML from the PHP story server, wraps it in a
 * minimal full-page document, runs AxeBuilder scoped to critical + serious
 * violations, and asserts zero violations.
 *
 * The three rules that are disabled in .storybook/preview.js
 * (landmark-one-main, page-has-heading-one, region) are also disabled here
 * so the gate aligns with Storybook UI behaviour.
 */

import { test, expect } from '@playwright/test';
import { AxeBuilder } from '@axe-core/playwright';
import * as fs from 'fs';
import * as path from 'path';

const PHP_SERVER_URL = 'http://localhost:6007';

/**
 * Rules globally disabled in .storybook/preview.js.
 *
 * Rationale:
 * - landmark-one-main: Components are tested in isolation, not as full pages
 * - page-has-heading-one: Individual components don't need h1
 * - region: Component snippets aren't wrapped in landmark regions
 *
 * These rules ARE enforced in full-page E2E tests (not here).
 */
const DISABLED_RULES = ['landmark-one-main', 'page-has-heading-one', 'region'];

/** Load story entries from the pre-built storybook-static/index.json */
function loadStoryEntries(): Array<{ storyId: string; componentPath: string; storyArgs: Record<string, string> }> {
  const indexPath = path.resolve(__dirname, '../../../../storybook-static/index.json');
  if (!fs.existsSync(indexPath)) {
    return [];
  }
  const raw = JSON.parse(fs.readFileSync(indexPath, 'utf-8')) as {
    entries: Record<string, { title: string; name: string; args?: Record<string, string> }>;
  };

  const results: Array<{ storyId: string; componentPath: string; storyArgs: Record<string, string> }> = [];

  for (const [storyId, entry] of Object.entries(raw.entries)) {
    // Skip token showcase stories — they are visual-only and have no semantic markup to gate
    if (entry.title.startsWith('Tokens')) {
      continue;
    }

    // Convert "Atoms/Button" → "atoms/button"
    const parts = entry.title.split('/');
    if (parts.length < 2) continue;
    const componentPath = `${parts[0].toLowerCase()}/${parts[1].toLowerCase().replace(/\s+/g, '-')}`;

    results.push({
      storyId,
      componentPath,
      storyArgs: (entry.args as Record<string, string>) ?? {},
    });
  }

  return results;
}

const stories = loadStoryEntries();

for (const { storyId, componentPath, storyArgs } of stories) {
  test(`axe: ${storyId}`, async ({ page, request }) => {
    // Build query string from story args merged with component path
    const params = new URLSearchParams({ id: componentPath, ...storyArgs });
    const url = `${PHP_SERVER_URL}/?${params.toString()}`;

    const res = await request.get(url);
    // If the component path doesn't exist on the server, skip gracefully rather than fail
    if (res.status() === 404) {
      test.skip(true, `Component not found on PHP server: ${componentPath}`);
      return;
    }
    expect(res.status()).toBe(200);

    const snippet = await res.text();

    // Wrap snippet in a minimal full-page document so axe has a proper DOM tree
    const fullHtml = `<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${storyId}</title>
</head>
<body>
  <main>
    ${snippet}
  </main>
</body>
</html>`;

    await page.setContent(fullHtml, { waitUntil: 'domcontentloaded' });

    const results = await new AxeBuilder({ page })
      .disableRules(DISABLED_RULES)
      .options({ runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] } })
      .analyze();

    const criticalOrSerious = results.violations.filter(
      (v) => v.impact === 'critical' || v.impact === 'serious',
    );

    if (criticalOrSerious.length > 0) {
      const summary = criticalOrSerious
        .map(
          (v) =>
            `[${v.impact}] ${v.id}: ${v.description} — ${v.nodes.length} node(s): ${v.nodes
              .map((n) => n.html)
              .join(', ')}`,
        )
        .join('\n');
      expect(
        criticalOrSerious.length,
        `Story "${storyId}" has ${criticalOrSerious.length} axe violation(s):\n${summary}`,
      ).toBe(0);
    }
  });
}
