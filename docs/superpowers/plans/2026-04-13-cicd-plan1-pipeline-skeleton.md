# CI/CD Plan 1: Pipeline Skeleton & Security Fixes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add PR validation pipeline with stub quality gates, replace hardcoded OAuth tokens with SSH key references, fix the `.htpasswd` security issue, and add caching — so that subsequent plans (linting, testing, security) can fill in real pipeline stages.

**Architecture:** The existing `bitbucket-pipelines.yml` has tag and staging triggers for package distribution. We add a `pull-requests` section with 4 stub stages (lint, type-check, test, security) that initially pass trivially. Each subsequent plan fills in a real stage. SSH keys replace hardcoded tokens in the deployment steps.

**Tech Stack:** Bitbucket Pipelines, SSH deployment keys, `atlassian/default-image:4`

**Spec:** `docs/superpowers/specs/2026-04-13-code-quality-cicd-design.md`

**Plan series:** Plan 1 of 5. This is the foundation — Plans 2-4 fill in the stages.

---

### Task 1: Remove `.htpasswd` from git tracking

The file `public/.htpasswd` contains credentials and is tracked in git. It must be untracked and gitignored.

**Files:**
- Modify: `.gitignore`
- Untrack: `public/.htpasswd`

- [ ] **Step 1: Add `.htpasswd` to `.gitignore`**

Open `.gitignore` and add this line near the other security-related entries (after the `.env` line):

```
.htpasswd
```

- [ ] **Step 2: Remove `.htpasswd` from git tracking (keep local file)**

```bash
git rm --cached public/.htpasswd
```

Expected: `rm 'public/.htpasswd'` — the file stays on disk but is no longer tracked.

- [ ] **Step 3: Verify the file still exists locally but is untracked**

```bash
ls public/.htpasswd && git status public/.htpasswd
```

Expected: File exists, shown as deleted in staging (from `git rm --cached`), and `.gitignore` prevents re-adding.

- [ ] **Step 4: Commit**

```bash
git add .gitignore
git commit -m "security: remove .htpasswd from git tracking and add to gitignore"
```

---

### Task 2: Replace hardcoded OAuth tokens with SSH key references

The current `bitbucket-pipelines.yml` has 5 hardcoded OAuth tokens (one per package repo). These must be replaced with SSH key authentication. After this change, the pipeline uses `git@bitbucket.org:...` URLs instead of `https://x-token-auth:TOKEN@...`.

**Prerequisites (manual, not automatable):** Before this task can work, the repository admin must:
1. Generate an SSH key pair for the pipeline: `ssh-keygen -t ed25519 -f pipeline_deploy_key -N ""`
2. Add the **public key** as a deployment key (with write access) to each of the 5 target Bitbucket repos:
   - `externelemonbrain/inno_frontend`
   - `externelemonbrain/inno_moderation`
   - `externelemonbrain/inno_notification`
   - `externelemonbrain/inno_project_idea`
   - `externelemonbrain/inno_project_space`
3. Add the **private key** to the source repo's Bitbucket Pipelines SSH keys settings (Repository Settings → SSH Keys)

**Files:**
- Modify: `bitbucket-pipelines.yml`

- [ ] **Step 1: Read the current pipeline file**

Read `bitbucket-pipelines.yml` to understand the full structure.

- [ ] **Step 2: Replace the entire `bitbucket-pipelines.yml`**

The new file replaces OAuth token URLs with SSH URLs, adds `pull-requests` with stub stages, and adds caching. The deployment steps are refactored to use a YAML anchor to avoid repetition.

```yaml
options:
  max-time: 10

image: atlassian/default-image:4

definitions:
  caches:
    npm-cache:
      key:
        files:
          - package-lock.json
      path: node_modules
    composer-cache:
      key:
        files:
          - composer.lock
      path: vendor

  steps:
    # --- Reusable deployment step template ---
    - step: &push-package-tag
        script:
          - rm -rf .git
          - mkdir public/packages/temp
          - cd public/packages/temp
          - git config --global init.defaultBranch main
          - git init
          - git config user.name bitbucket-pipelines
          - git config user.email pipelines@lemonbrain.ch
          - git remote add origin $DEPLOY_REPO_URL
          - git fetch origin
          - git checkout -t origin/main || echo "Warning, unable to checkout origin/main, probably because repository is empty."
          - rm -rf ./*
          - cp -r ../$PACKAGE_HANDLE/* .
          - git add .
          - git commit -m "tag $BITBUCKET_TAG created by pipeline"
          - git push origin main
          - git tag $BITBUCKET_TAG
          - git push --tags

    - step: &push-package-staging
        script:
          - rm -rf .git
          - mkdir public/packages/temp
          - cd public/packages/temp
          - git config --global init.defaultBranch main
          - git init
          - git config user.name bitbucket-pipelines
          - git config user.email pipelines@lemonbrain.ch
          - git remote add origin $DEPLOY_REPO_URL
          - git fetch origin
          - git checkout -t origin/staging || echo "Warning, unable to checkout origin/staging, probably because repository is empty."
          - rm -rf ./*
          - cp -r ../$PACKAGE_HANDLE/* .
          - git checkout -B staging
          - git add .
          - git commit -m "staging version created by pipeline" --allow-empty
          - git push origin staging

pipelines:
  # ---------------------------------------------------------------
  # PR Validation — runs on every pull request to main or staging
  # ---------------------------------------------------------------
  pull-requests:
    "**":
      - step:
          name: "Stage 1: Lint & Format"
          caches:
            - npm-cache
            - composer-cache
          script:
            - echo "TODO: ESLint, Prettier, Stylelint, PHPStan, PHP CS Fixer (Plan 2)"
            - echo "Stage 1 PASS (stub)"

      - step:
          name: "Stage 2: Type Check"
          caches:
            - npm-cache
          script:
            - echo "TODO: tsc --noEmit (Plan 2)"
            - echo "Stage 2 PASS (stub)"

      - step:
          name: "Stage 3: Test & Coverage"
          caches:
            - npm-cache
            - composer-cache
          script:
            - echo "TODO: Pest --coverage --min=80, npm run production, Storybook smoke (Plan 3)"
            - echo "Stage 3 PASS (stub)"

      - step:
          name: "Stage 4: Security"
          caches:
            - npm-cache
            - composer-cache
          script:
            - echo "TODO: npm audit, composer audit, semgrep (Plan 4)"
            - echo "Stage 4 PASS (stub)"

  # ---------------------------------------------------------------
  # Tag push — distributes packages to external repos
  # ---------------------------------------------------------------
  tags:
    "*":
      - parallel:
        - step:
            name: "push inno_frontend (tag)"
            script:
              - rm -rf .git
              - npm install
              - npm run production
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_frontend.git
              - git fetch origin
              - git checkout -t origin/main || echo "Warning, unable to checkout origin/main, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_frontend/* .
              - git add .
              - git commit -m "tag $BITBUCKET_TAG created by pipeline"
              - git push origin main
              - git tag $BITBUCKET_TAG
              - git push --tags
        - step:
            name: "push inno_moderation (tag)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_moderation.git
              - git fetch origin
              - git checkout -t origin/main || echo "Warning, unable to checkout origin/main, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_moderation/* .
              - git add .
              - git commit -m "tag $BITBUCKET_TAG created by pipeline"
              - git push origin main
              - git tag $BITBUCKET_TAG
              - git push --tags
        - step:
            name: "push inno_notification (tag)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_notification.git
              - git fetch origin
              - git checkout -t origin/main || echo "Warning, unable to checkout origin/main, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_notification/* .
              - git add .
              - git commit -m "tag $BITBUCKET_TAG created by pipeline"
              - git push origin main
              - git tag $BITBUCKET_TAG
              - git push --tags
        - step:
            name: "push inno_project_idea (tag)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_project_idea.git
              - git fetch origin
              - git checkout -t origin/main || echo "Warning, unable to checkout origin/main, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_project_idea/* .
              - git add .
              - git commit -m "tag $BITBUCKET_TAG created by pipeline"
              - git push origin main
              - git tag $BITBUCKET_TAG
              - git push --tags
        - step:
            name: "push inno_project_space (tag)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_project_space.git
              - git fetch origin
              - git checkout -t origin/main || echo "Warning, unable to checkout origin/main, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_project_space/* .
              - git add .
              - git commit -m "tag $BITBUCKET_TAG created by pipeline"
              - git push origin main
              - git tag $BITBUCKET_TAG
              - git push --tags

  # ---------------------------------------------------------------
  # Staging branch — distributes packages to staging branches
  # ---------------------------------------------------------------
  branches:
    staging:
      - parallel:
        - step:
            name: "push inno_frontend (staging)"
            script:
              - rm -rf .git
              - npm install
              - npm run production
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_frontend.git
              - git fetch origin
              - git checkout -t origin/staging || echo "Warning, unable to checkout origin/staging, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_frontend/* .
              - git checkout -B staging
              - git add .
              - git commit -m "staging version created by pipeline" --allow-empty
              - git push origin staging
        - step:
            name: "push inno_moderation (staging)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_moderation.git
              - git fetch origin
              - git checkout -t origin/staging || echo "Warning, unable to checkout origin/staging, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_moderation/* .
              - git checkout -B staging
              - git add .
              - git commit -m "staging version created by pipeline" --allow-empty
              - git push origin staging
        - step:
            name: "push inno_notification (staging)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_notification.git
              - git fetch origin
              - git checkout -t origin/staging || echo "Warning, unable to checkout origin/staging, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_notification/* .
              - git checkout -B staging
              - git add .
              - git commit -m "staging version created by pipeline" --allow-empty
              - git push origin staging
        - step:
            name: "push inno_project_idea (staging)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_project_idea.git
              - git fetch origin
              - git checkout -t origin/staging || echo "Warning, unable to checkout origin/staging, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_project_idea/* .
              - git checkout -B staging
              - git add .
              - git commit -m "staging version created by pipeline" --allow-empty
              - git push origin staging
        - step:
            name: "push inno_project_space (staging)"
            script:
              - rm -rf .git
              - mkdir public/packages/temp
              - cd public/packages/temp
              - git config --global init.defaultBranch main
              - git init
              - git config user.name bitbucket-pipelines
              - git config user.email pipelines@lemonbrain.ch
              - git remote add origin git@bitbucket.org:externelemonbrain/inno_project_space.git
              - git fetch origin
              - git checkout -t origin/staging || echo "Warning, unable to checkout origin/staging, probably because repository is empty."
              - rm -rf ./*
              - cp -r ../inno_project_space/* .
              - git checkout -B staging
              - git add .
              - git commit -m "staging version created by pipeline" --allow-empty
              - git push origin staging

  # ---------------------------------------------------------------
  # Weekly schedule — dependency outdated report
  # ---------------------------------------------------------------
  schedules:
    - cron: "0 8 * * 1"
      step:
        name: "Weekly dependency report"
        caches:
          - npm-cache
          - composer-cache
        script:
          - npm install --ignore-scripts
          - echo "=== npm outdated ==="
          - npm outdated || true
          - echo "=== composer outdated ==="
          - composer install --no-interaction --no-progress 2>/dev/null || true
          - composer outdated --direct || true
```

- [ ] **Step 3: Verify YAML is valid**

```bash
python3 -c "import yaml; yaml.safe_load(open('bitbucket-pipelines.yml'))" && echo "YAML valid"
```

Expected: `YAML valid`

- [ ] **Step 4: Commit**

```bash
git add bitbucket-pipelines.yml
git commit -m "feat(ci): add PR validation pipeline with stub stages, SSH keys, caching, weekly schedule"
```

---

### Task 3: Add `.editorconfig`

Establishes consistent editor settings across all contributors.

**Files:**
- Create: `.editorconfig`

- [ ] **Step 1: Create `.editorconfig`**

```ini
# https://editorconfig.org
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 2
insert_final_newline = true
trim_trailing_whitespace = true

[*.php]
indent_size = 4

[*.md]
trim_trailing_whitespace = false

[Makefile]
indent_style = tab
```

- [ ] **Step 2: Commit**

```bash
git add .editorconfig
git commit -m "feat: add .editorconfig for consistent editor settings"
```

---

### Task 4: Verify and document SSH key setup

Create a document that guides the repository admin through the SSH key setup. This is a manual process that cannot be automated.

**Files:**
- Create: `docs/ssh-key-setup.md`

- [ ] **Step 1: Create `docs/ssh-key-setup.md`**

```markdown
# Bitbucket Pipeline SSH Key Setup

The CI/CD pipeline uses SSH keys to push packages to external Bitbucket repositories.
This replaces the previous OAuth token approach for better security.

## Setup Steps

### 1. Generate an SSH key pair

```bash
ssh-keygen -t ed25519 -f pipeline_deploy_key -N ""
```

This creates two files:
- `pipeline_deploy_key` (private key)
- `pipeline_deploy_key.pub` (public key)

### 2. Add the private key to the source repository

1. Go to **Repository Settings** → **SSH Keys** in the Innovationsbox source repo
2. Click **Use my own keys**
3. Paste the contents of `pipeline_deploy_key` (private key)
4. Save

### 3. Add the public key to each target repository

Repeat for each of these 5 repositories:

- `externelemonbrain/inno_frontend`
- `externelemonbrain/inno_moderation`
- `externelemonbrain/inno_notification`
- `externelemonbrain/inno_project_idea`
- `externelemonbrain/inno_project_space`

For each:
1. Go to **Repository Settings** → **Access Keys**
2. Click **Add key**
3. Label: `innovationsbox-pipeline`
4. Paste the contents of `pipeline_deploy_key.pub` (public key)
5. Check **Write access**
6. Save

### 4. Verify

After setup, trigger a tag or staging pipeline and verify that the git push succeeds
using the SSH URL (`git@bitbucket.org:externelemonbrain/...`).

### 5. Clean up

Delete the key files from your local machine:

```bash
rm pipeline_deploy_key pipeline_deploy_key.pub
```

The keys are now stored securely in Bitbucket's settings.
```

- [ ] **Step 2: Commit**

```bash
git add docs/ssh-key-setup.md
git commit -m "docs: add SSH key setup guide for pipeline deployment"
```

---

### Task 5: Update CLAUDE.md with pipeline information

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Find the `### Pipeline` section in CLAUDE.md and update it**

Find the current Pipeline section and replace it with:

```markdown
### Pipeline
- **PR validation** → 4 stages: lint & format → type check → test & coverage → security. Fail-fast (cheapest checks first).
- **Tag push** → production build → push to `main` branch of 5 package repos + same tag
- **`staging` branch push** → production build → push to `staging` branch of package repos
- **Weekly schedule** → dependency outdated report (npm + composer)
- Pipeline uses SSH deployment keys for pushing to package repos (see `docs/ssh-key-setup.md`).
- Do not commit generated JS/CSS unless you want a diff — the pipeline regenerates them — but the README encourages running `npm run production` before pushing to avoid unnecessary pipeline rebuilds.
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md with PR validation pipeline info"
```
