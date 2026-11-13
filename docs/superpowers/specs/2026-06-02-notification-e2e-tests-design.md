# Notification E2E Tests — Design Spec

**Date:** 2026-06-02
**Goal:** Verify all 12 email notification types fire correctly via E2E tests, using CMS email logging as the verification mechanism.

---

## Infrastructure

### Email Log Server

A lightweight PHP script at `tests/e2e/email-log-server/index.php` that queries the CMS `Logs` table for `channel = 'sent_emails'` and returns results as JSON. Runs on port 6008.

**Endpoints:**
- `GET /?since={unix_timestamp}` — returns email log entries since timestamp, as `{ data: [{ message, time }] }`
- `DELETE /` — truncates email log entries with `channel = 'sent_emails'` (test isolation)

**Boot:** Requires CMS database access. Reads DB config from `application/config/live.database.php` or `.env`.

### Playwright Config Update

Add the email log server as a second web server in `tests/e2e/playwright.config.ts`, alongside the existing storybook PHP server.

### Test Helper

Add `getEmailLogs(request, since)` and `clearEmailLogs(request)` helpers to `tests/e2e/fixtures/helpers.ts` using the email log server URL.

---

## Test Spec: `tests/e2e/specs/notification-emails.spec.ts`

### Prerequisites
- CMS running with mail logging enabled (`concrete.log.emails` = `metadata` or `metadata_and_body`)
- Seeded test data (`make seed`)
- Email log server running on port 6008

### Test Pattern
1. Clear email logs
2. Trigger action via API
3. Query email logs
4. Assert expected subject substring found in logged messages

### All 12 Tests

| # | Event | API Trigger | Assert Subject Contains |
|---|-------|-------------|------------------------|
| 1 | ProjectIdeaSubmitted | `POST /api/project-idea` | "Neue Projektidee eingereicht" |
| 2 | ProjectIdeaApproved | `POST /api/moderation/{id}/approve` | "freigegeben" |
| 3 | ProjectIdeaRejected | `POST /api/moderation/{id}/reject` | "nicht freigegeben" or "Rückmeldung" |
| 4 | ModerationFeedbackRequested | `POST /api/moderation/{id}/feedback` | "Rückmeldung" |
| 5 | JoinRequestReceived | `POST /api/project-space/{id}/join-request` | "Beitrittsanfrage" |
| 6 | JoinRequestAccepted | `PUT /api/project-space/{id}/join-request/{rid}` (accept) | "angenommen" |
| 7 | JoinRequestRejected | `PUT /api/project-space/{id}/join-request/{rid}` (reject) | "abgelehnt" |
| 8 | MemberInvited | `POST /api/project-space/{id}/invite` | "eingeladen" |
| 9 | ExpertRequested | `POST /api/project-space/{id}/expert-request` | "Expertise" |
| 10 | ExpertRequestAccepted | `PUT /api/project-space/{id}/expert-request/{rid}` (accept) | "angenommen" |
| 11 | ExpertRequestDeclined | `PUT /api/project-space/{id}/expert-request/{rid}` (decline) | "abgelehnt" |
| 12 | StatusUpdatePublished | `POST /api/project-space/{id}/status-update` | "Update" |

### Test Data

Uses seeded data:
- `test_space_1`: active space, initiator=user 1, members=users 1,2,3,4
- `test_space_4`: active space for join request tests (user 5/moderator not a member)
- Ideas submitted dynamically during tests (for approval/rejection/feedback flows)
- Expert user 4 available for expert request tests

### Notes

- Tests 1-4 form a sequence: submit → approve flow, then submit → reject, submit → feedback
- Tests 5-7 form a sequence: join request → accept, then another join → reject
- Test 12 verifies multiple emails sent (one per member minus author)
- Each test group clears logs before starting to avoid cross-contamination
