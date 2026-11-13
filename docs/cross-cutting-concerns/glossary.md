# Glossary

Definitions for roles, permissions, and domain terms used across the Innovationsbox codebase and documentation.

## Roles

Roles map to Concrete CMS user groups. The role constant is used in application code via `AuthInterface::hasRole()`.

| German | English | Role constant | Description |
|---|---|---|---|
| Mitglied | Member | `member` | Authenticated user; can view the public sector and vote on ideas |
| Entwickler | Developer | `developer` | Can join and collaborate in project spaces |
| Initiator | Initiator | `initiator` | Can submit project ideas |
| Moderator | Moderator | `moderator` | Reviews, approves, and rejects submitted ideas in the moderation queue |
| Administrator | Administrator | `administrator` | Full system configuration and user management; extends the Concrete CMS admin role |

Unauthenticated visitors can view the public sector (published ideas, project information) without holding any role.

## Permissions

Permissions are action-based. Roles grant permissions; the mapping is enforced by `ConcreteCmsAuth`. Controllers check permissions via `AuthInterface::hasPermission()`. A failed check throws `AuthException`, which the global exception handler converts to a 403 JSON response.

| Permission | Granted to | Used in |
|---|---|---|
| `idea.submit` | `initiator`, `administrator` | `ProjectIdeaApiController::store()` |
| `idea.view` | all (including unauthenticated) | Public pages |
| `idea.vote` | `member`, `developer`, `initiator`, `moderator`, `administrator` | Voting endpoint |
| `moderation.review` | `moderator`, `administrator` | `ModerationApiController` |
| `space.join` | `developer`, `initiator`, `administrator` | `ProjectSpaceApiController::join()` |
| `space.manage` | `administrator` | Space administration |

## Domain Terms

| Term | Definition |
|---|---|
| Project idea | A climate-relevant idea submitted by an initiator; goes through moderation before being published |
| Project space | A protected collaboration area created automatically when an idea is approved; members with the developer role can join |
| Moderation queue | The list of submitted but not yet reviewed ideas visible to moderators |
| Moderation decision | An approval or rejection recorded by a moderator; triggers `ModerationDecisionMade` event |
| Public sector | The publicly visible area of the platform showing published ideas and project information |

## Architecture Terms

| Term | Definition |
|---|---|
| Port | An interface in `Port/In/` (primary port, driven by controllers) or `Port/Out/` (secondary port, implemented by adapters) |
| Adapter | A concrete implementation of a port; in `Adapter/In/Http/` for controllers, `Adapter/Out/Persistence/` for repositories |
| Cross-cutting concern | A capability needed by all domain packages (configuration, logging, caching, etc.) abstracted behind an interface in `inno_shared` |
| `inno_shared` | The shared package providing event contracts and all cross-cutting infrastructure interfaces and adapters |
| Domain event | A value object with a `NAME` constant representing something that happened; classes live in `inno_shared/src/Domain/Event/` |
