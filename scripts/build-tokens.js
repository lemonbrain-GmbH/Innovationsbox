#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

const TOKENS_PATH = path.resolve(__dirname, '../resources/tokens/tokens.json');
const OUT_DIR = path.resolve(__dirname, '../resources/css/global');

const tokens = JSON.parse(fs.readFileSync(TOKENS_PATH, 'utf-8'));

function emitScaleVars(lines, prefix, scale) {
  for (const [step, value] of Object.entries(scale)) {
    lines.push(`  --${prefix}-${step}: ${value};`);
  }
}

function buildCssVariables() {
  const lines = [
    '// AUTO-GENERATED from tokens.json — do not edit manually.',
    '// Run: npm run tokens',
    '',
    '@use "../helper/functions" as f;',
    '',
    ':root {',
  ];

  lines.push('  // screen sizes');
  lines.push('  --upper-bound: #{f.$upperBound-factor}px;');
  lines.push('  --lower-bound: #{f.$lowerBound-factor}px;');
  lines.push('  --upper-bound-mobile: #{f.$upperBoundMobile-factor}px;');
  lines.push('');

  lines.push('  // motion');
  lines.push(`  --duration-fast: ${tokens.motion.duration.fast};`);
  lines.push(`  --duration-normal: ${tokens.motion.duration.normal};`);
  lines.push(`  --duration-slow: ${tokens.motion.duration.slow};`);
  lines.push(`  --ease-standard: ${tokens.motion.easing.standard};`);
  lines.push(`  --ease-emphasized: ${tokens.motion.easing.emphasized};`);
  lines.push('');

  lines.push('  // legacy aliases');
  lines.push(`  --transition-time: var(--duration-normal);`);
  lines.push('  --lighten-amount: 20%;');
  lines.push('  --darken-amount: 20%;');
  lines.push(`  --block-space-max-factor: ${tokens.spacing.blockSpaceMax};`);
  lines.push(`  --block-space-min-factor: ${tokens.spacing.blockSpaceMin};`);
  lines.push('');

  lines.push('  // radius');
  for (const step of ['none', 'sm', 'md', 'lg', 'xl', 'full']) {
    const val = tokens.radius[step];
    lines.push(`  --radius-${step}: ${val === 9999 ? '9999px' : val + 'px'};`);
  }
  lines.push('  --border-radius: var(--radius-lg);');
  lines.push('  --border-radius-small: var(--radius-md);');
  lines.push('');
  lines.push('  @media screen and (max-width: #{f.$lowerBound-factor}px) {');
  lines.push('    --border-radius: var(--border-radius-small);');
  lines.push('  }');
  lines.push('');

  lines.push('  // shadows');
  for (const step of ['sm', 'md', 'lg', 'xl']) {
    lines.push(`  --shadow-${step}: ${tokens.shadow[step]};`);
  }
  lines.push('  --box-shadow: var(--shadow-md);');
  lines.push('');

  lines.push('  // z-index');
  for (const [name, value] of Object.entries(tokens.z)) {
    lines.push(`  --z-${name}: ${value};`);
  }
  lines.push('');

  lines.push('  // spacing scale (4px base)');
  for (const [step, value] of Object.entries(tokens.space)) {
    lines.push(`  --space-${step}: ${value}px;`);
  }
  lines.push('');

  lines.push('  // brand color scales');
  emitScaleVars(lines, 'color-brand-primary', tokens.color.brand.primary);
  lines.push('');
  emitScaleVars(lines, 'color-brand-secondary', tokens.color.brand.secondary);
  lines.push('');
  emitScaleVars(lines, 'color-brand-accent', tokens.color.brand.accent);
  lines.push('');

  lines.push('  // neutral scale');
  emitScaleVars(lines, 'color-neutral', tokens.color.neutral);
  lines.push('');

  lines.push('  // status color scales');
  for (const status of ['success', 'warning', 'error', 'info']) {
    emitScaleVars(lines, `color-status-${status}`, tokens.color.status[status]);
    lines.push('');
  }

  lines.push('  // base colors');
  lines.push(`  --color-white: ${tokens.color.white};`);
  lines.push(`  --color-overlay: ${tokens.color.overlay};`);
  lines.push('');

  lines.push('  // semantic aliases (dark-mode-swap-ready)');
  for (const group of ['text', 'bg', 'border', 'action']) {
    for (const [name, value] of Object.entries(tokens.semantic[group])) {
      lines.push(`  --color-${group}-${name}: ${value};`);
    }
  }
  // header, footer, card, fab tokens
  for (const group of ['header', 'footer', 'card', 'fab']) {
    if (tokens.semantic[group]) {
      for (const [name, value] of Object.entries(tokens.semantic[group])) {
        lines.push(`  --${group}-${name}: ${value};`);
      }
    }
  }
  // page layout tokens
  if (tokens.semantic.page) {
    for (const [name, value] of Object.entries(tokens.semantic.page)) {
      lines.push(`  --page-${name}: ${value};`);
    }
  }
  // ui-text scale tokens
  if (tokens.semantic['ui-text']) {
    for (const [name, value] of Object.entries(tokens.semantic['ui-text'])) {
      lines.push(`  --text-${name}: ${value};`);
    }
  }
  // filter interaction tokens
  if (tokens.semantic.filter) {
    for (const [name, value] of Object.entries(tokens.semantic.filter)) {
      lines.push(`  --filter-${name}: ${value};`);
    }
  }
  lines.push('');

  lines.push('  // status semantic aliases');
  for (const status of ['success', 'warning', 'error', 'info']) {
    lines.push(`  --color-status-${status}-bg: var(--color-status-${status}-50);`);
    lines.push(`  --color-status-${status}-border: var(--color-status-${status}-200);`);
    lines.push(`  --color-status-${status}-icon: var(--color-status-${status}-600);`);
  }
  lines.push('');

  lines.push('  // extended semantic aliases');
  lines.push('  --color-text-tertiary: var(--color-text-muted);');
  lines.push('  --color-bg-muted: var(--color-bg-subtle);');
  lines.push('  --color-text-on-primary: var(--color-action-primary-text);');
  lines.push('');

  lines.push('  // legacy color aliases');
  lines.push('  --color-primary: var(--color-brand-primary-500);');
  lines.push('  --color-secondary: var(--color-brand-secondary-500);');
  lines.push('  --color-green: var(--color-brand-accent-500);');
  lines.push('  --color-brown: var(--color-brand-secondary-500);');
  lines.push('  --color-lightgreen: var(--color-brand-primary-500);');
  lines.push('  --color-black: var(--color-neutral-900);');
  lines.push('  --color-light-overlay: rgba(255, 255, 255, 0.5);');
  lines.push('');

  lines.push('  // font');
  lines.push(`  --heading-font-family: ${tokens.font.family.heading};`);
  lines.push(`  --text-font-family: ${tokens.font.family.text};`);
  for (const [name, value] of Object.entries(tokens.font.weight)) {
    lines.push(`  --font-weight-${name}: ${value};`);
  }
  for (const [name, value] of Object.entries(tokens.font.tracking)) {
    lines.push(`  --tracking-${name}: ${value};`);
  }
  lines.push('');

  lines.push('  --font-weight-normal: var(--font-weight-regular);');
  lines.push('');

  lines.push('  // typography (legacy per-element vars)');
  const typoMap = {
    h1: 'h1', h2: 'h2', h3: 'h3', h4: 'h4', h5: 'h5', h6: 'h6',
    body: 'p', lead: 'lead', small: 'small', link: 'a', list: 'list',
  };

  for (const [tokenKey, cssPrefix] of Object.entries(typoMap)) {
    const t = tokens.typography[tokenKey];
    if (!t) continue;
    const familyVar = t.family === 'heading' ? '--heading-font-family' : '--text-font-family';

    lines.push(`  --${cssPrefix}-font-family: var(${familyVar});`);
    lines.push(`  --${cssPrefix}-font-weight: ${t.weight};`);

    if (tokenKey === 'link') {
      lines.push(`  --${cssPrefix}-font-color: var(--color-${t.color});`);
      lines.push(`  --${cssPrefix}-font-color-hover: var(--color-${t.hoverColor});`);
      lines.push(`  --${cssPrefix}-text-decoration: none;`);
      lines.push(`  --${cssPrefix}-text-decoration-hover: none;`);
    } else {
      lines.push(`  --${cssPrefix}-font-color: var(--color-${t.color});`);
    }
    lines.push('');
  }

  lines.push('  --list-style-type: none;');

  lines.push('}');

  return lines.join('\n') + '\n';
}

function buildScssArrays() {
  const lines = [
    '// AUTO-GENERATED from tokens.json — do not edit manually.',
    '// Run: npm run tokens',
    '',
  ];

  lines.push(`$base-font-sizes: (${tokens.typography.body.sizes.join(', ')});`);
  lines.push(`$base-line-heights: (${tokens.typography.body.lineHeights.join(', ')});`);
  lines.push('');

  const arrayMap = {
    p: 'body', lead: 'lead', small: 'small',
    h1: 'h1', h2: 'h2', h3: 'h3', h4: 'h4', h5: 'h5', h6: 'h6',
    a: 'link', list: 'list', button: 'button',
  };

  for (const [scssPrefix, tokenKey] of Object.entries(arrayMap)) {
    const t = tokens.typography[tokenKey];
    if (tokenKey === 'body') {
      lines.push(`$${scssPrefix}-font-sizes: $base-font-sizes;`);
      lines.push(`$${scssPrefix}-line-heights: $base-line-heights;`);
    } else {
      lines.push(`$${scssPrefix}-font-sizes: (${t.sizes.join(', ')});`);
      lines.push(`$${scssPrefix}-line-heights: (${t.lineHeights.join(', ')});`);
    }
    lines.push('');
  }

  lines.push('// numeric text scale');
  for (const [name, t] of Object.entries(tokens.textScale)) {
    lines.push(`$text-${name}-font-sizes: (${t.sizes.join(', ')});`);
    lines.push(`$text-${name}-line-heights: (${t.lineHeights.join(', ')});`);
    lines.push('');
  }

  return lines.join('\n');
}

fs.mkdirSync(OUT_DIR, { recursive: true });

const varsContent = buildCssVariables();
const arraysContent = buildScssArrays();

fs.writeFileSync(path.join(OUT_DIR, '_generated-variables.scss'), varsContent);
fs.writeFileSync(path.join(OUT_DIR, '_generated-arrays.scss'), arraysContent);

console.log('Tokens built:');
console.log('  → resources/css/global/_generated-variables.scss');
console.log('  → resources/css/global/_generated-arrays.scss');
