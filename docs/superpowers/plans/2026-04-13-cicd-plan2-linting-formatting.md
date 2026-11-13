# CI/CD Plan 2: Linting & Formatting

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Install and configure ESLint, Prettier, Stylelint, PHPStan, and PHP CS Fixer, add pre-commit hooks via husky + lint-staged, and fill in pipeline Stages 1 and 2 with real linting and type-check commands.

**Architecture:** Each tool has its own config file at the project root. Pre-commit hooks auto-format on commit. The pipeline runs all checks in dry-run/check mode (reports errors but doesn't auto-fix). Stage 1 runs lint + format checks in parallel sub-steps. Stage 2 runs `tsc --noEmit`.

**Tech Stack:** ESLint 9 (flat config), Prettier 3, Stylelint 16, PHPStan 2 (level 6), PHP CS Fixer 3, husky 9, lint-staged 15

**Spec:** `docs/superpowers/specs/2026-04-13-code-quality-cicd-design.md`

**Plan series:** Plan 2 of 5. Plan 1 (pipeline skeleton) is complete.

---

### Task 1: Install and configure ESLint + Prettier for TypeScript

**Files:**
- Create: `eslint.config.js`
- Create: `.prettierrc`
- Create: `.prettierignore`
- Modify: `package.json` (add devDependencies and scripts)

- [ ] **Step 1: Install npm packages**

```bash
npm install --save-dev eslint @eslint/js typescript-eslint eslint-config-prettier eslint-plugin-import prettier
```

- [ ] **Step 2: Create `eslint.config.js`**

ESLint 9 flat config with TypeScript strict rules, import ordering, complexity limits, and Prettier integration.

```javascript
import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import prettierConfig from 'eslint-config-prettier';
import importPlugin from 'eslint-plugin-import';

export default tseslint.config(
  eslint.configs.recommended,
  ...tseslint.configs.strictTypeChecked,
  prettierConfig,
  {
    languageOptions: {
      parserOptions: {
        projectService: true,
        tsconfigRootDir: import.meta.dirname,
      },
    },
    plugins: {
      import: importPlugin,
    },
    rules: {
      // Complexity
      'complexity': ['error', 10],
      'max-depth': ['error', 4],

      // Unused variables
      '@typescript-eslint/no-unused-vars': ['error', {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
      }],

      // Import ordering
      'import/order': ['error', {
        'groups': ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
        'newlines-between': 'always',
        'alphabetize': { order: 'asc' },
      }],

      // Naming conventions
      '@typescript-eslint/naming-convention': [
        'error',
        { selector: 'variableLike', format: ['camelCase'] },
        { selector: 'typeLike', format: ['PascalCase'] },
        { selector: 'enumMember', format: ['UPPER_CASE', 'PascalCase'] },
        { selector: 'variable', modifiers: ['const', 'global'], format: ['camelCase', 'UPPER_CASE'] },
      ],

      // Relax some strict rules that are too noisy for this project
      '@typescript-eslint/no-extraneous-class': 'off',
      '@typescript-eslint/no-unsafe-assignment': 'warn',
      '@typescript-eslint/no-unsafe-member-access': 'warn',
      '@typescript-eslint/no-unsafe-call': 'warn',
    },
  },
  {
    ignores: [
      'node_modules/',
      'public/',
      'vendor/',
      'webpack.mix.js',
      '*.config.js',
      'scripts/',
    ],
  },
);
```

- [ ] **Step 3: Create `.prettierrc`**

```json
{
  "semi": true,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "all",
  "printWidth": 120,
  "arrowParens": "always",
  "endOfLine": "lf"
}
```

- [ ] **Step 4: Create `.prettierignore`**

```
node_modules
vendor
public/packages/*/themes/*/css
public/packages/*/themes/*/js
public/packages/*/blocks/*/view.css
public/packages/*/blocks/*/view.js
*.min.js
*.min.css
package-lock.json
composer.lock
```

- [ ] **Step 5: Update `tsconfig.json` for stricter checking and full file coverage**

Replace the current `tsconfig.json`:

```json
{
  "compilerOptions": {
    "module": "commonjs",
    "target": "es6",
    "noImplicitAny": true,
    "sourceMap": false,
    "strict": true,
    "skipLibCheck": true,
    "typeRoots": ["resources/js/types/*"],
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true
  },
  "include": ["resources/js/**/*"],
  "exclude": ["node_modules"]
}
```

Key changes: `noImplicitAny: true` (was false), `noUnusedLocals`, `noUnusedParameters`, `noFallthroughCasesInSwitch` added. `include` broadened from `resources/js/modules/*` to `resources/js/**/*`.

- [ ] **Step 6: Add npm scripts to `package.json`**

Add these to the `scripts` section:

```json
"lint": "eslint resources/js/ --ext .ts",
"lint:fix": "eslint resources/js/ --ext .ts --fix",
"format": "prettier --check 'resources/**/*.{ts,js,json}'",
"format:fix": "prettier --write 'resources/**/*.{ts,js,json}'",
"typecheck": "tsc --noEmit"
```

- [ ] **Step 7: Run ESLint to verify it works (expect some violations)**

```bash
npx eslint resources/js/ --ext .ts 2>&1 | head -20
```

Expected: Either clean output or a list of violations. No config errors.

- [ ] **Step 8: Run Prettier to verify**

```bash
npx prettier --check 'resources/**/*.{ts,js,json}' 2>&1 | head -10
```

Expected: List of files checked, possibly some "not formatted" warnings.

- [ ] **Step 9: Run type check**

```bash
npx tsc --noEmit 2>&1 | head -20
```

Expected: Either clean or type errors. No config errors.

- [ ] **Step 10: Commit**

```bash
git add eslint.config.js .prettierrc .prettierignore tsconfig.json package.json package-lock.json
git commit -m "feat(lint): add ESLint + Prettier + stricter tsconfig for TypeScript"
```

---

### Task 2: Fix existing TypeScript lint violations

After installing ESLint with strict rules, there will likely be violations in existing code. Fix them now so CI starts clean.

**Files:**
- Modify: TypeScript files in `resources/js/` (whatever ESLint reports)

- [ ] **Step 1: Auto-fix what Prettier and ESLint can handle**

```bash
npx prettier --write 'resources/**/*.{ts,js,json}'
npx eslint resources/js/ --ext .ts --fix
```

- [ ] **Step 2: Check remaining violations**

```bash
npx eslint resources/js/ --ext .ts 2>&1
```

If there are remaining violations that can't be auto-fixed, fix them manually. Common issues:
- Unused variables → remove or prefix with `_`
- `noImplicitAny` → add type annotations
- Naming conventions → rename to match camelCase/PascalCase

- [ ] **Step 3: Verify type check passes**

```bash
npx tsc --noEmit
```

If there are type errors from the broadened `include` or `noImplicitAny: true`, fix them.

- [ ] **Step 4: Verify the build still works**

```bash
npm run development
```

Expected: Webpack compiles without errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/ resources/tokens/ resources/css/
git commit -m "fix(lint): fix existing TypeScript lint and format violations"
```

---

### Task 3: Install and configure Stylelint for SCSS

**Files:**
- Create: `.stylelintrc.json`
- Modify: `package.json` (add devDependencies and scripts)

- [ ] **Step 1: Install npm packages**

```bash
npm install --save-dev stylelint stylelint-config-standard-scss
```

- [ ] **Step 2: Create `.stylelintrc.json`**

```json
{
  "extends": "stylelint-config-standard-scss",
  "rules": {
    "no-duplicate-selectors": true,
    "color-named": "never",
    "selector-class-pattern": null,
    "scss/at-rule-no-unknown": [true, {
      "ignoreAtRules": ["use", "forward", "import", "mixin", "include", "function", "return", "if", "else", "for", "each", "while", "error", "warn", "debug", "media"]
    }],
    "scss/dollar-variable-pattern": null,
    "declaration-block-no-redundant-longhand-properties": null,
    "custom-property-pattern": null,
    "value-keyword-case": ["lower", {
      "ignoreKeywords": ["Inter"]
    }]
  },
  "ignoreFiles": [
    "node_modules/**",
    "vendor/**",
    "public/**",
    "resources/css/global/fonts.css",
    "resources/css/global/_generated-variables.scss",
    "resources/css/global/_generated-arrays.scss"
  ]
}
```

- [ ] **Step 3: Add npm scripts**

Add to `package.json` scripts:

```json
"lint:scss": "stylelint 'resources/**/*.scss'",
"lint:scss:fix": "stylelint 'resources/**/*.scss' --fix"
```

- [ ] **Step 4: Run Stylelint**

```bash
npx stylelint 'resources/**/*.scss' 2>&1 | head -20
```

Expected: Either clean or a list of SCSS violations.

- [ ] **Step 5: Auto-fix SCSS violations**

```bash
npx stylelint 'resources/**/*.scss' --fix
```

- [ ] **Step 6: Verify remaining violations and fix manually if needed**

```bash
npx stylelint 'resources/**/*.scss'
```

Fix any remaining issues. Common: property order, color format, naming patterns.

- [ ] **Step 7: Verify the build still works**

```bash
npm run development
```

- [ ] **Step 8: Commit**

```bash
git add .stylelintrc.json package.json package-lock.json resources/css/
git commit -m "feat(lint): add Stylelint for SCSS with auto-fixed violations"
```

---

### Task 4: Install and configure PHPStan + PHP CS Fixer

**Files:**
- Create: `phpstan.neon`
- Create: `.php-cs-fixer.dist.php`
- Modify: `composer.json` (add dev dependencies and scripts)
- Modify: `.gitignore` (add cache files)

- [ ] **Step 1: Install PHP packages**

```bash
composer require --dev phpstan/phpstan friendsofphp/php-cs-fixer
```

- [ ] **Step 2: Create `phpstan.neon`**

```neon
parameters:
    level: 6
    paths:
        - public/packages/inno_project_idea/src
        - public/packages/inno_moderation/src
        - public/packages/inno_notification/src
        - public/packages/inno_project_space/src
        - public/packages/inno_shared/src
    excludePaths:
        - vendor
    scanDirectories:
        - vendor/concrete5/core/src
    bootstrapFiles:
        - vendor/autoload.php
```

Note: No baseline file yet. We'll generate one if there are existing violations.

- [ ] **Step 3: Run PHPStan to check for violations**

```bash
./vendor/bin/phpstan analyse 2>&1 | tail -20
```

If there are violations, generate a baseline:

```bash
./vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon
```

Then add the baseline to `phpstan.neon`:

```neon
includes:
    - phpstan-baseline.neon

parameters:
    level: 6
    paths:
        - public/packages/inno_project_idea/src
        - public/packages/inno_moderation/src
        - public/packages/inno_notification/src
        - public/packages/inno_project_space/src
        - public/packages/inno_shared/src
    excludePaths:
        - vendor
    scanDirectories:
        - vendor/concrete5/core/src
    bootstrapFiles:
        - vendor/autoload.php
```

- [ ] **Step 4: Create `.php-cs-fixer.dist.php`**

```php
<?php
$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/public/packages/inno_project_idea/src',
        __DIR__ . '/public/packages/inno_moderation/src',
        __DIR__ . '/public/packages/inno_notification/src',
        __DIR__ . '/public/packages/inno_project_space/src',
        __DIR__ . '/public/packages/inno_shared/src',
        __DIR__ . '/public/packages/inno_frontend',
        __DIR__ . '/resources/components',
        __DIR__ . '/storybook-server',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
```

- [ ] **Step 5: Run PHP CS Fixer in dry-run to see violations**

```bash
./vendor/bin/php-cs-fixer fix --dry-run --diff 2>&1 | head -30
```

- [ ] **Step 6: Auto-fix PHP formatting**

```bash
./vendor/bin/php-cs-fixer fix
```

- [ ] **Step 7: Add cache files to `.gitignore`**

Add these lines to `.gitignore`:

```
.php-cs-fixer.cache
.phpstan-result-cache/
```

- [ ] **Step 8: Add composer scripts**

Add to `composer.json` scripts:

```json
"lint:phpstan": "phpstan analyse",
"lint:cs": "php-cs-fixer fix --dry-run --diff",
"lint:cs:fix": "php-cs-fixer fix",
"lint:php": ["@lint:phpstan", "@lint:cs"]
```

- [ ] **Step 9: Verify PHPStan passes (with baseline if generated)**

```bash
./vendor/bin/phpstan analyse
```

Expected: `[OK] No errors` (or baseline-ignored errors only)

- [ ] **Step 10: Verify PHP CS Fixer passes**

```bash
./vendor/bin/php-cs-fixer fix --dry-run --diff
```

Expected: No files need fixing.

- [ ] **Step 11: Commit**

```bash
git add phpstan.neon .php-cs-fixer.dist.php composer.json composer.lock .gitignore public/packages/ resources/components/ storybook-server/
# Also add phpstan-baseline.neon if it was generated
git add phpstan-baseline.neon 2>/dev/null || true
git commit -m "feat(lint): add PHPStan level 6 + PHP CS Fixer with PER-CS2.0"
```

---

### Task 5: Set up pre-commit hooks with husky + lint-staged

**Files:**
- Create: `.husky/pre-commit`
- Create: `.lintstagedrc.json`
- Modify: `package.json` (add devDependencies and prepare script)

- [ ] **Step 1: Install husky and lint-staged**

```bash
npm install --save-dev husky lint-staged
```

- [ ] **Step 2: Initialize husky**

```bash
npx husky init
```

This creates `.husky/` directory with a `pre-commit` file.

- [ ] **Step 3: Set the pre-commit hook content**

Replace `.husky/pre-commit` with:

```bash
npx lint-staged
```

- [ ] **Step 4: Create `.lintstagedrc.json`**

```json
{
  "resources/**/*.ts": ["eslint --fix", "prettier --write"],
  "resources/**/*.{js,json}": ["prettier --write"],
  "resources/**/*.scss": ["stylelint --fix"],
  "**/*.php": ["php-cs-fixer fix"]
}
```

- [ ] **Step 5: Add prepare script to package.json**

Add to `package.json` scripts:

```json
"prepare": "husky"
```

- [ ] **Step 6: Test the pre-commit hook**

Create a temporary file to trigger the hook:

```bash
echo "const x  =  1;" > /tmp/test-lint.ts
cp /tmp/test-lint.ts resources/js/test-lint.ts
git add resources/js/test-lint.ts
git commit -m "test: lint hook test" 2>&1 | head -10
# The hook should auto-format the file. If commit succeeds, the file was formatted.
git reset HEAD~1
rm resources/js/test-lint.ts
```

- [ ] **Step 7: Commit**

```bash
git add .husky/ .lintstagedrc.json package.json package-lock.json
git commit -m "feat(lint): add pre-commit hooks with husky + lint-staged"
```

---

### Task 6: Add lint:all convenience script

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Add the combined lint script**

Add to `package.json` scripts:

```json
"lint:all": "npm run lint && npm run format && npm run lint:scss && npm run typecheck"
```

Note: PHP linting is not included here because it requires PHP in the Nix shell. Run `composer lint:php` separately.

- [ ] **Step 2: Verify the full lint suite**

```bash
npm run lint:all
```

Expected: All checks pass (or the command runs through all tools without config errors).

- [ ] **Step 3: Commit**

```bash
git add package.json
git commit -m "feat(lint): add lint:all convenience script"
```

---

### Task 7: Fill in pipeline Stage 1 and Stage 2

Replace the stub stages in `bitbucket-pipelines.yml` with real linting and type-check commands.

**Files:**
- Modify: `bitbucket-pipelines.yml`

- [ ] **Step 1: Read the current pipeline file**

Read `bitbucket-pipelines.yml` to find the Stage 1 and Stage 2 stubs.

- [ ] **Step 2: Replace Stage 1 stub**

Find the step named `"Stage 1: Lint & Format"` and replace its `script` block:

```yaml
      - step:
          name: "Stage 1: Lint & Format"
          caches:
            - npm-cache
            - composer-cache
          script:
            - npm ci
            - composer install --no-interaction --no-progress
            - npm run lint
            - npm run format
            - npm run lint:scss
            - composer lint:php
```

- [ ] **Step 3: Replace Stage 2 stub**

Find the step named `"Stage 2: Type Check"` and replace its `script` block:

```yaml
      - step:
          name: "Stage 2: Type Check"
          caches:
            - npm-cache
          script:
            - npm ci
            - npm run typecheck
```

- [ ] **Step 4: Validate YAML**

```bash
python3 -c "import yaml; yaml.safe_load(open('bitbucket-pipelines.yml'))" && echo "YAML valid"
```

Expected: `YAML valid`

- [ ] **Step 5: Commit**

```bash
git add bitbucket-pipelines.yml
git commit -m "feat(ci): fill pipeline Stage 1 (lint) and Stage 2 (typecheck) with real commands"
```

---

### Task 8: Update CLAUDE.md with lint commands

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add a Linting section to Commands**

Find the `### Testing` section in CLAUDE.md and add a new `### Linting & Formatting` section right before it:

```markdown
### Linting & Formatting
```bash
npm run lint             # ESLint for TypeScript
npm run lint:fix         # ESLint auto-fix
npm run format           # Prettier check
npm run format:fix       # Prettier auto-fix
npm run lint:scss        # Stylelint for SCSS
npm run lint:scss:fix    # Stylelint auto-fix
npm run typecheck        # TypeScript type check (tsc --noEmit)
npm run lint:all         # Run all JS/TS/SCSS checks
composer lint:php        # PHPStan + PHP CS Fixer (dry-run)
composer lint:cs:fix     # PHP CS Fixer auto-fix
```
Pre-commit hooks (husky + lint-staged) auto-format on commit. CI runs all checks in the PR pipeline Stage 1 and 2.
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add linting commands to CLAUDE.md"
```
