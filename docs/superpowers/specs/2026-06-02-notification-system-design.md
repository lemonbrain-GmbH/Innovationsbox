# Notification System — Design Spec

**Date:** 2026-06-02
**Goal:** Complete the notification package — 12 email notification types covering the full idea-to-project lifecycle, with branded HTML templates, proper email resolution via hexagonal ports, and unit tests.

---

## Architecture

Event-driven notification using the existing hexagonal pattern in `inno_notification`. Each domain event gets its own handler class in `Application/EventHandler/`. All handlers use two new shared ports for looking up user emails/names and space names — keeping handlers free of CMS coupling and testable with mocks.

Email bodies use branded HTML templates with the klimaglarus/Arento corporate design (logo, brand primary `#B7CD57`, consistent footer).

---

## New Ports (in `inno_shared`)

### UserResolverPort

```
Port/Out/UserResolverPort
  resolveEmail(string $userId): ?string
  resolveName(string $userId): string
```

Implementation: `ConcreteCmsUserResolver` using `UserInfo::getByID()` → `getUserEmail()` / `getAttribute('display_name')`.

### ProjectSpaceReaderPort

```
Port/Out/ProjectSpaceReaderPort
  getSpaceName(string $spaceId): string
  getSpaceMemberIds(string $spaceId): array<string>
```

Implementation: reads from the `inno_project_space` repository tables. Lives in `inno_shared` so the notification package can use it without importing `inno_project_space`.

---

## HTML Email Template

A single shared PHP function that wraps email body content in a branded HTML shell:

- Header: klimaglarus green bar (`#B7CD57`) with "Innovationsbox" text
- Body: white background, system font stack, 600px max-width
- Footer: "Diese E-Mail wurde automatisch von der Innovationsbox gesendet." + unsubscribe note
- Inline CSS only (email client compatibility)

The `EmailServiceInterface.send()` already accepts a body string. The template function produces that string.

---

## Complete Email Matrix (12 types)

### 1. ProjectIdeaSubmitted → Admin

**Existing handler — fix email resolution only.**

| Field | Value |
|---|---|
| To | Admin email from config (`notification.admin_email`) |
| Subject | Neue Projektidee eingereicht: {title} |
| Body | Eine neue Projektidee wurde eingereicht und wartet auf Moderation. Titel: {title}, Eingereicht von: {submitterName} |

### 2. ProjectIdeaApproved → Submitter

**Existing handler — fix email resolution, upgrade to HTML.**

| Field | Value |
|---|---|
| To | Submitter's email (via UserResolverPort) |
| Subject | Deine Projektidee wurde freigegeben! |
| Body | Gratulation! Deine Projektidee "{title}" wurde freigegeben. Ein Projektraum wurde für dich erstellt. Du kannst ihn jetzt einrichten und Mitglieder einladen. |

### 3. ProjectIdeaRejected → Submitter

**Existing handler — fix email resolution, upgrade to HTML.**

| Field | Value |
|---|---|
| To | Submitter's email (via UserResolverPort) |
| Subject | Rückmeldung zu deiner Projektidee |
| Body | Deine Projektidee "{title}" konnte leider nicht freigegeben werden. Begründung: {reason} |

### 4. ModerationFeedbackRequested → Submitter (NEW)

| Field | Value |
|---|---|
| To | Idea submitter's email (need ideaId → submitterId lookup via repository) |
| Subject | Rückmeldung zu deiner Projektidee |
| Body | Das Moderationsteam hat Rückmeldungen zu deiner Projektidee. Bitte überarbeite sie und reiche sie erneut ein. Hinweise: {notes}. Frist: {deadline or "keine Frist"} |

Note: `ModerationFeedbackRequested` event only carries `ideaId`, not `submitterId`. The handler needs to look up the idea's submitter. Add a method to a port or use `ProjectIdeaReaderPort` (new, minimal — just `getSubmitterId(string $ideaId): string`).

### 5. JoinRequestReceived → Space Initiator (NEW)

| Field | Value |
|---|---|
| To | Space initiator's email |
| Subject | Neue Beitrittsanfrage für «{spaceName}» |
| Body | {userName} möchte deinem Projektraum «{spaceName}» beitreten. Bitte bearbeite die Anfrage im Projektraum. |

Lookup: `spaceId` → initiatorId (via ProjectSpaceReaderPort) → email (via UserResolverPort)

### 6. JoinRequestAccepted → Requesting User (NEW)

| Field | Value |
|---|---|
| To | Requesting user's email |
| Subject | Deine Beitrittsanfrage wurde angenommen |
| Body | Du wurdest als Mitglied im Projektraum «{spaceName}» aufgenommen. Du kannst jetzt aktiv mitarbeiten. |

### 7. JoinRequestRejected → Requesting User (NEW)

| Field | Value |
|---|---|
| To | Requesting user's email |
| Subject | Deine Beitrittsanfrage wurde abgelehnt |
| Body | Deine Beitrittsanfrage für den Projektraum «{spaceName}» wurde leider abgelehnt. |

### 8. MemberInvited → Invited User (NEW)

| Field | Value |
|---|---|
| To | Invited user's email |
| Subject | Du wurdest in einen Projektraum eingeladen |
| Body | {initiatorName} hat dich in den Projektraum «{spaceName}» eingeladen. Schau dir den Projektraum an und beginne mitzuarbeiten. |

### 9. ExpertRequested → Expert (NEW)

| Field | Value |
|---|---|
| To | Expert's email |
| Subject | Deine Expertise wird angefragt |
| Body | {requesterName} bittet um deine Unterstützung im Projektraum «{spaceName}». {message if present}. Bitte nimm die Anfrage an oder lehne sie ab. |

### 10. ExpertRequestAccepted → Space Initiator (NEW)

| Field | Value |
|---|---|
| To | Space initiator's email (via spaceId lookup) |
| Subject | Expert·in hat die Anfrage angenommen |
| Body | {expertName} hat die Expertanfrage für den Projektraum «{spaceName}» angenommen und ist jetzt Mitglied. |

### 11. ExpertRequestDeclined → Space Initiator (NEW)

| Field | Value |
|---|---|
| To | Space initiator's email (via spaceId lookup) |
| Subject | Expert·in hat die Anfrage abgelehnt |
| Body | {expertName} hat die Expertanfrage für den Projektraum «{spaceName}» leider abgelehnt. |

### 12. StatusUpdatePublished → All Space Members (NEW)

| Field | Value |
|---|---|
| To | Each member of the space (except the author) |
| Subject | Neues Update in «{spaceName}» |
| Body | {authorName} hat ein neues Update im Projektraum «{spaceName}» veröffentlicht. Schau dir das Update an. |

Lookup: `spaceId` → all member IDs (via ProjectSpaceReaderPort) → filter out authorId → resolve each email → send one email per member.

---

## Files to Create/Modify

### New in `inno_shared`
- `Port/Out/UserResolverPort.php` — interface
- `Infrastructure/Adapter/User/ConcreteCmsUserResolver.php` — implementation
- `Port/Out/ProjectSpaceReaderPort.php` — interface (getSpaceName, getSpaceMemberIds, getInitiatorId)
- `Infrastructure/Adapter/Space/ConcreteProjectSpaceReader.php` — implementation (reads ib_project_spaces directly)
- `Port/Out/ProjectIdeaReaderPort.php` — interface (getSubmitterId)
- `Infrastructure/Adapter/Idea/ConcreteProjectIdeaReader.php` — implementation

### New in `inno_notification`
- `Infrastructure/Email/EmailTemplate.php` — branded HTML template wrapper
- `Application/EventHandler/OnModerationFeedbackRequested.php`
- `Application/EventHandler/OnJoinRequestReceived.php`
- `Application/EventHandler/OnJoinRequestAccepted.php`
- `Application/EventHandler/OnJoinRequestRejected.php`
- `Application/EventHandler/OnMemberInvited.php`
- `Application/EventHandler/OnExpertRequested.php`
- `Application/EventHandler/OnExpertRequestAccepted.php`
- `Application/EventHandler/OnExpertRequestDeclined.php`
- `Application/EventHandler/OnStatusUpdatePublished.php`

### Modify
- `inno_notification/controller.php` — register 9 new event listeners + bind new ports
- `inno_shared/controller.php` — bind UserResolverPort, ProjectSpaceReaderPort, ProjectIdeaReaderPort
- Existing 3 handlers — fix email resolution, upgrade to HTML template

### Tests
- Unit test per handler (12 total)
- Unit test for UserResolverPort, ProjectSpaceReaderPort, ProjectIdeaReaderPort implementations
- Unit test for EmailTemplate
