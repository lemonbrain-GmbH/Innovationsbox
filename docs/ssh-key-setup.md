# Bitbucket Pipeline SSH Key Setup

The CI/CD pipeline uses SSH keys to push packages to external Bitbucket repositories.
This replaces the previous OAuth token approach for better security.

## Setup Steps

### 1. Generate an SSH key pair

    ssh-keygen -t ed25519 -f pipeline_deploy_key -N ""

This creates two files:
- `pipeline_deploy_key` (private key)
- `pipeline_deploy_key.pub` (public key)

### 2. Add the private key to the source repository

1. Go to **Repository Settings** → **SSH Keys** in the Innovationsbox source repo
2. Click **Use my own keys**
3. Paste the contents of `pipeline_deploy_key` (private key)
4. Save

### 3. Add the public key to each target repository (write access)

The pipeline pushes packages to these 5 repositories on tag/staging builds:

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

### 4. Add the public key to each dependency repository (read access)

The pipeline pulls these private packages via `composer install` (resolved through `packagist.lemonbrain.ch`):

- `externelemonbrain/block_developer`
- `externelemonbrain/lemonbrain_services`
- `externelemonbrain/lemonbrain_webp`

For each:
1. Go to **Repository Settings** → **Access Keys**
2. Click **Add key**
3. Label: `innovationsbox-pipeline`
4. Paste the contents of `pipeline_deploy_key.pub` (public key)
5. **Read access** is sufficient
6. Save

### 5. Verify

After setup, trigger a tag or staging pipeline and verify that the git push succeeds
using the SSH URL (`git@bitbucket.org:externelemonbrain/...`).

### 6. Clean up

Delete the key files from your local machine:

    rm pipeline_deploy_key pipeline_deploy_key.pub

The keys are now stored securely in Bitbucket's settings.
