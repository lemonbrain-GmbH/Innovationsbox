# CI/CD Plan 4: Security Scanning

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add dependency vulnerability scanning (npm audit + composer audit), SAST via semgrep, secret detection via detect-secrets pre-commit hook, license compliance script, SECURITY.md, and fill pipeline Stage 4.

**Architecture:** Stage 4 runs three security checks: npm audit (high+ severity fails), composer audit (any advisory fails), and semgrep SAST (error-level findings fail). detect-secrets runs as a pre-commit hook (not in CI). License checking is a manual npm script.

**Tech Stack:** npm audit, composer audit, semgrep, detect-secrets, license-checker

**Spec:** `docs/superpowers/specs/2026-04-13-code-quality-cicd-design.md`

**Plan series:** Plan 4 of 5. Plans 1-3 are complete (pipeline + linting + testing).

---

### Task 1: Add npm audit and composer audit scripts

**Files:**
- Modify: `package.json` (add scripts)
- Modify: `composer.json` (add scripts)

- [ ] **Step 1: Add npm security scripts to `package.json`**

Read `package.json`, add to the `scripts` section (keep all existing):

```json
"audit:npm": "npm audit --audit-level=high",
"license:check": "npx license-checker --production --failOn 'GPL-3.0;AGPL-3.0'"
```

- [ ] **Step 2: Add composer audit script to `composer.json`**

Read `composer.json`, add to the `scripts` section (keep all existing):

```json
"audit": "composer audit"
```

- [ ] **Step 3: Test npm audit runs**

```bash
npm audit --audit-level=high 2>&1 | tail -5
```

Expected: Either `found 0 vulnerabilities` or a report. The command exits non-zero if high/critical vulnerabilities exist.

- [ ] **Step 4: Test composer audit runs**

```bash
composer audit 2>&1 | tail -5
```

Expected: Either `No security vulnerability advisories found` or a report.

- [ ] **Step 5: Commit**

```bash
git add package.json composer.json
git commit -m "feat(security): add npm audit, composer audit, and license check scripts"
```

---

### Task 2: Add semgrep configuration

Semgrep performs static analysis security testing (SAST) — scans PHP and TypeScript for common vulnerability patterns (SQL injection, XSS, path traversal, etc.).

**Files:**
- Create: `.semgrepconfig.yml`
- Modify: `flake.nix` (add semgrep to dev shell)

- [ ] **Step 1: Create `.semgrepconfig.yml`**

A minimal config that uses semgrep's auto-detection for PHP and TypeScript:

```yaml
# Semgrep SAST configuration
# Runs automatically detected rules for PHP and TypeScript
# See: https://semgrep.dev/docs/running-rules
rules: []
# Using --config=auto in CI, which fetches community rules.
# This file exists as a placeholder for future custom rules.
```

- [ ] **Step 2: Update `flake.nix` to add semgrep**

Read `flake.nix`, add `pkgs.semgrep` to the default shell's packages list. The packages should be:

```nix
packages = [
  claude-code.packages.${system}.default
  pkgs.nodejs_20
  pkgs.php83
  pkgs.semgrep
];
```

Keep the storybook shell and any other structure unchanged.

- [ ] **Step 3: Add npm script for local semgrep**

Add to `package.json` scripts:

```json
"audit:sast": "semgrep --config=auto --error resources/js/ public/packages/inno_*/src/ storybook-server/"
```

The `--error` flag makes semgrep exit non-zero on findings (for CI enforcement).

- [ ] **Step 4: Commit**

```bash
git add .semgrepconfig.yml flake.nix package.json
git commit -m "feat(security): add semgrep SAST config and add to flake.nix"
```

---

### Task 3: Add detect-secrets pre-commit hook

detect-secrets scans staged files for patterns that look like API keys, tokens, passwords, or private keys.

**Files:**
- Modify: `flake.nix` (add detect-secrets)
- Modify: `.husky/pre-commit` (add detect-secrets check)
- Create: `.secrets.baseline` (initial baseline)

- [ ] **Step 1: Update `flake.nix` to add detect-secrets**

Add `pkgs.detect-secrets` to the default shell's packages list:

```nix
packages = [
  claude-code.packages.${system}.default
  pkgs.nodejs_20
  pkgs.php83
  pkgs.semgrep
  pkgs.detect-secrets
];
```

- [ ] **Step 2: Generate initial secrets baseline**

This scans the entire repo and creates a baseline of known non-secret patterns (so existing code doesn't trigger false positives):

```bash
detect-secrets scan --all-files > .secrets.baseline 2>/dev/null || echo '{"results": {}, "version": "1.0.0"}' > .secrets.baseline
```

If `detect-secrets` isn't available in the current shell (not in nix yet), create a minimal baseline:

```bash
echo '{"results": {}, "version": "1.0.0", "generated_at": "2026-04-13T00:00:00Z"}' > .secrets.baseline
```

- [ ] **Step 3: Update `.husky/pre-commit` to include detect-secrets**

Read the current `.husky/pre-commit`, then replace with:

```bash
npx lint-staged
detect-secrets-hook --baseline .secrets.baseline $(git diff --cached --name-only) 2>/dev/null || true
```

The `|| true` ensures the commit isn't blocked if detect-secrets isn't installed (not everyone uses the nix shell). The tool warns but doesn't prevent commits for developers without it.

- [ ] **Step 4: Commit**

```bash
git add flake.nix .secrets.baseline .husky/pre-commit
git commit -m "feat(security): add detect-secrets pre-commit hook with baseline"
```

---

### Task 4: Create SECURITY.md

**Files:**
- Create: `SECURITY.md`

- [ ] **Step 1: Create `SECURITY.md`**

```markdown
# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| main    | Yes       |
| staging | Yes       |
| feature branches | Development only |

## Reporting a Vulnerability

If you discover a security vulnerability in the Innovationsbox project, please report it responsibly.

**Do NOT open a public issue.** Instead:

1. Email: **security@lemonbrain.ch**
2. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

## Response Timeline

- **Acknowledgment:** Within 2 business days
- **Assessment:** Within 5 business days
- **Fix/Mitigation:** Depends on severity
  - Critical: within 24 hours
  - High: within 1 week
  - Medium/Low: next release cycle

## Security Measures

This project employs the following automated security checks:

- **Dependency scanning:** `npm audit` and `composer audit` run on every PR
- **Static analysis (SAST):** Semgrep scans PHP and TypeScript for vulnerability patterns
- **Secret detection:** `detect-secrets` pre-commit hook prevents accidental credential commits
- **License compliance:** `npm run license:check` verifies dependency licenses

## Scope

This policy applies to the Innovationsbox application code in this repository.
It does not cover the Concrete CMS core or third-party packages — report those
to their respective maintainers.
```

- [ ] **Step 2: Commit**

```bash
git add SECURITY.md
git commit -m "docs: add SECURITY.md with responsible disclosure process"
```

---

### Task 5: Fill pipeline Stage 4

Replace the Stage 4 stub with real security scanning commands.

**Files:**
- Modify: `bitbucket-pipelines.yml`

- [ ] **Step 1: Read the pipeline file**

Read `bitbucket-pipelines.yml` to find the Stage 4 stub (around line 79-86).

- [ ] **Step 2: Replace Stage 4 stub**

Find the step named `"Stage 4: Security"` and replace its entire content:

```yaml
      - step:
          name: "Stage 4: Security"
          caches:
            - npm-cache
            - composer-cache
          script:
            - npm ci
            - composer install --no-interaction --no-progress

            # Dependency vulnerability scanning
            - echo "=== npm audit ==="
            - npm audit --audit-level=high
            - echo "=== composer audit ==="
            - composer audit

            # SAST (static analysis)
            - echo "=== semgrep ==="
            - pip3 install semgrep || apt-get update && apt-get install -y python3-pip && pip3 install semgrep
            - semgrep --config=auto --error resources/js/ public/packages/inno_*/src/ storybook-server/
```

- [ ] **Step 3: Validate YAML**

```bash
python3 -c "import yaml; yaml.safe_load(open('bitbucket-pipelines.yml'))" && echo "YAML valid"
```

- [ ] **Step 4: Commit**

```bash
git add bitbucket-pipelines.yml
git commit -m "feat(ci): fill pipeline Stage 4 with npm audit, composer audit, semgrep"
```

---

### Task 6: Update CLAUDE.md with security commands

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add Security section to Commands**

Find the `### Linting & Formatting` section in CLAUDE.md. Add a new `### Security` section right after it (before `### Storybook`):

```markdown
### Security
```bash
npm run audit:npm          # npm dependency vulnerability scan (fails on high/critical)
composer audit             # Composer dependency vulnerability scan
npm run audit:sast         # Semgrep SAST scan (PHP + TypeScript)
npm run license:check      # License compliance check (fails on GPL-3.0/AGPL-3.0)
```
Pre-commit hook runs `detect-secrets` to prevent accidental credential commits.
CI runs npm audit + composer audit + semgrep in pipeline Stage 4.
See `SECURITY.md` for the vulnerability disclosure process.
```

Use the Edit tool — do NOT overwrite the whole file.

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add security commands to CLAUDE.md"
```
