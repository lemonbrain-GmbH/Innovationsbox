# GUI-Only E2E Tests

**Date:** 2026-07-03
**Status:** Approved

## Problem

The current E2E test suite is mostly API-based: tests create data and perform actions via direct `api.post()`, `api.get()`, etc. calls. This doesn't test the actual user experience — forms, buttons, navigation, error states, CKEditor interactions, file uploads, modals. Screenshots in Playwright UI show blank pages or raw API responses instead of the real application.

## Solution

Replace all API-based E2E tests with GUI-only tests that interact with the application exactly as a user would: fill forms, click buttons, navigate pages, switch users. The only exception is Mailpit API calls for email verification (infrastructure, not application API). Emails are also shown visually by navigating to the Mailpit web UI.

## Design Decisions

- **All setup through GUI**: no `seedIdea()` or API setup. If a test needs an idea, it submits one via the form first.
- **Serial workflow specs**: tests are organized as user journeys. Each spec is `test.describe.serial()` — later tests build on earlier ones.
- **Delete API-only tests**: the GUI tests replace them. No duplication.
- **Merge browser/ subdirectory**: the existing `browser/*.spec.ts` tests are already GUI-based. Their patterns are absorbed into the new workflow specs.
- **Email visual feedback**: after verifying an email via Mailpit API, navigate to the Mailpit web UI to show the email in Playwright UI traces/screenshots.

## Spec File Structure

### Files to Create (7 workflow specs)

| Spec File | Workflow | Pages Used |
|---|---|---|
| `00-smoke.spec.ts` | Environment health, homepage, login smoke for all roles | `/`, `/login` |
| `01-auth.spec.ts` | Register, login, profile completion, forgot password, logout | `/register`, `/login`, `/account/edit_profile`, `/account/avatar` |
| `02-idea-to-space.spec.ts` | Submit idea via form → moderator approves via UI → project space created → verify in list | `/projektidee/einreichen`, `/projektidee/meine`, `/moderation`, `/moderation/detail`, `/projektraeume` |
| `03-moderation-feedback.spec.ts` | Submit idea → moderator requests feedback → user resubmits via edit modal → moderator approves | Same moderation pages, feedback + resubmit flow |
| `04-project-space.spec.ts` | Join request via button, accept/reject in sidebar, write/edit/delete status updates, add/remove collaboard links, leave/remove member, status transitions | `/projektraum/view/{id}`, `/projektraeume` |
| `05-expert-flow.spec.ts` | Request expert from space sidebar, expert accepts/declines via `/expertanfragen`, expert pool visibility | `/projektraum/view/{id}`, `/expertanfragen`, `/members/directory` |
| `06-moderation-admin.spec.ts` | Category CRUD via moderation UI, user management, expert flag toggle, reject idea flow | `/moderation/kategorien`, `/moderation/benutzer`, `/moderation/detail` |

### Files to Keep As-Is

| Spec File | Reason |
|---|---|
| `projektraeume.spec.ts` | Already GUI-only: filters, search, category badges |
| `projektraum.spec.ts` | Already GUI-only: detail page layout verification |
| `expert-pool.spec.ts` | Already GUI-only: member directory visibility |
| `profile-access.spec.ts` | Already GUI-only: access control checks |
| `auth-flow.spec.ts` | Already GUI-only: login/logout/avatar page |
| `storybook/*.spec.ts` | Component tests, not affected |

### Files to Delete

All API-only and mixed specs that are replaced by the new workflow specs:

- `02-ideas.spec.ts` — replaced by `02-idea-to-space.spec.ts`
- `03-moderation.spec.ts` — replaced by `03-moderation-feedback.spec.ts`
- `04-project-spaces.spec.ts` — replaced by `04-project-space.spec.ts`
- `04b-experts.spec.ts` — replaced by `05-expert-flow.spec.ts`
- `submit-idea.spec.ts` — replaced by `02-idea-to-space.spec.ts`
- `category-management.spec.ts` — replaced by `06-moderation-admin.spec.ts`
- `expert-request.spec.ts` — replaced by `05-expert-flow.spec.ts`
- `leave-remove-member.spec.ts` — replaced by `04-project-space.spec.ts`
- `moderation-flow.spec.ts` — replaced by `02-idea-to-space.spec.ts`
- `notification-emails.spec.ts` — email checks distributed across workflow specs
- `status-update.spec.ts` — replaced by `04-project-space.spec.ts`
- `user-management.spec.ts` — replaced by `06-moderation-admin.spec.ts`
- `browser/auth.spec.ts` — merged into `01-auth.spec.ts`
- `browser/expert-flow.spec.ts` — merged into `05-expert-flow.spec.ts`
- `browser/idea-management.spec.ts` — merged into `02-idea-to-space.spec.ts`
- `browser/idea-submission.spec.ts` — merged into `02-idea-to-space.spec.ts`
- `browser/moderation.spec.ts` — merged into `02-idea-to-space.spec.ts`, `03-moderation-feedback.spec.ts`
- `browser/project-spaces.spec.ts` — merged into `04-project-space.spec.ts`

## Email Verification Pattern

Every test that triggers an email follows this pattern:

1. Perform the action via GUI (submit idea, approve, join, etc.)
2. Call `getMailpitMessage(request, to, subjectContains)` — Mailpit API to find the email
3. Navigate to `http://localhost:8025/message/{msg.ID}` — show email in Playwright UI
4. Assert on `msg.Subject` and `msg.HTML` content

New helper in `fixtures/helpers.ts`:

```typescript
async function showEmailInMailpit(
  page: Page,
  request: APIRequestContext,
  to: string,
  subjectContains: string,
  timeoutMs?: number,
): Promise<{ ID: string; Subject: string; HTML: string; Text: string }> {
  const msg = await getMailpitMessage(request, to, subjectContains, timeoutMs);
  await page.goto(`http://localhost:8025/message/${msg.ID}`);
  return msg;
}
```

## Helper Changes

### Keep
- `loginViaUI()` — browser-based login
- `loginAsNewContext()` — multi-user testing (creates new browser context, logs in via UI)
- `fillCKEditor()` — CKEditor interaction
- `registerUser()` — browser-based registration
- `clearMailpit()`, `searchEmails()`, `waitForEmail()`, `getMailpitMessage()` — email infrastructure
- `extractLinkFromEmail()`, `rewriteUrlForDocker()` — email link handling

### Remove
- `AuthedApi` class — no more direct API calls
- `loginAs()` — replaced by `loginViaUI()` (no API session needed)
- `submitIdea()` — replaced by GUI form submission
- `seedIdea()` — replaced by GUI form submission
- `getPendingReviewForIdea()` — replaced by navigating moderation UI
- `approveReview()` — replaced by clicking approve in moderation detail UI
- `clearEmailLogs()`, `getEmailLogs()`, `emailLogsContain()` — legacy local email helpers

### Add
- `showEmailInMailpit()` — navigate to Mailpit web UI to show email visually

## Workflow Details

### 02-idea-to-space.spec.ts (Example)

```
test.describe.serial('Idea to Project Space')

  1. Login as initiator via UI
  2. Navigate to /projektidee/einreichen
  3. Fill title, select category, fill CKEditor description
  4. Click submit → wait for redirect to /projektidee/meine
  5. Verify idea appears in list with "submitted" badge
  6. Show "idea submitted" email to moderator in Mailpit

  7. Login as moderator via UI (new context)
  8. Navigate to /moderation
  9. Find the submitted idea in the table, click it
  10. On /moderation/detail: click "Genehmigen" (approve)
  11. Wait for redirect back to /moderation
  12. Show "idea approved" email to submitter in Mailpit

  13. Login as initiator again
  14. Navigate to /projektidee/meine
  15. Verify idea now shows "approved" badge with "Zum Projektraum" link
  16. Click the link → verify project space page loads
  17. Navigate to /projektraeume → verify space appears in list
```

### 04-project-space.spec.ts (Example)

```
test.describe.serial('Project Space Collaboration')

  // Uses an active space from seed data (e2e_space_active)

  1. Login as user (not yet a member)
  2. Navigate to /projektraeume, find active space, click "Mitmachen"
  3. On space detail: click "Mitmachen" join button
  4. Show "join request received" email to initiator in Mailpit

  5. Login as initiator (new context)
  6. Navigate to /projektraum/view/e2e_space_active
  7. Find join request in sidebar, click accept
  8. Show "join request accepted" email to user in Mailpit

  9. Still as initiator: write a status update
  10. Fill title, CKEditor body, submit
  11. Verify update appears in timeline
  12. Show "status update published" email to members in Mailpit

  13. Edit the status update, verify changes
  14. Delete the status update, verify removal

  15. Add a collaboard link (title + URL), verify it appears
  16. Remove the collaboard link, verify removal

  17. Login as user (now a member)
  18. Navigate to space, click "Verlassen" (leave)
  19. Confirm in dialog
  20. Show "member left" email to initiator in Mailpit
```

## Test Data Requirements

The existing `seed-e2e-data.sh` provides baseline data (categories, ideas in all statuses, spaces in all statuses, members, etc.). The GUI tests will:

- **Create new data via the UI** for workflows that test creation (idea submission, status updates)
- **Use seeded data** only for navigating to existing pages (e.g. project space detail)
- Seeded data ensures pages have content to display; test actions always go through the GUI
