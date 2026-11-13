#import "../../../common/styles/help-style.typ": show-help, help

// adoc/05_building_block_view.typ
// ---------------------------------------------
// Section 5: Bausteinsicht (arc42)
// ---------------------------------------------

= Bausteinsicht
#label("section-building-block-view")

// ---------------------------------------------------------
// 5.1 Whitebox Gesamtsystem
// ---------------------------------------------------------

== Whitebox Gesamtsystem

Die Innovationsbox ist als Monorepo organisiert und besteht aus fünf Concrete CMS Packages. Jedes Package entspricht einem Bounded Context aus dem strategischen DDD. Die Packages kommunizieren ausschliesslich über zwei Kanäle:

- *Event-driven:* Symfony EventDispatcher (Facade `Events`). Events werden über benannte String-Konstanten dispatcht (z. B. `ProjectIdeaSubmitted::NAME`).
- *REST API:* Alle Endpunkte liegen unter `/api/*` und geben `JsonResponse` zurück.

Packages greifen nie direkt auf die Infrastruktur eines anderen Packages zu.

#figure(
  image("../../../images/diagram-files/C4L2.jpg", width: 90%),
  caption: [C4 Level 2 – Innovationsbox Container Diagramm]
) <c4-container>

#table(
  columns: (auto, auto, auto, 1fr),
  align: left,
  inset: 7pt,
  stroke: 0.5pt,
  table.header(
    [*Package (Handle)*], [*REST API*], [*Emittiert*], [*Hört auf*]
  ),

  [`inno_frontend`],
  [—],
  [—],
  [—],

  [`inno_shared`],
  [—],
  [— (stellt Event-Klassen + `CategoryReaderPort` bereit)],
  [—],

  [`inno_project_idea`],
  [`/api/project-idea`],
  [`ProjectIdeaSubmitted`\ `ProjectIdeaApproved`\ `ProjectIdeaRejected`\ `ProjectIdeaResubmitted`],
  [`ModerationDecisionMade`\ `ModerationFeedbackRequested`\ `ProjectSpaceCreated`],

  [`inno_project_space`],
  [`/api/project-space`\ `/api/expert-request`],
  [`ProjectSpaceCreated`\ `JoinRequestReceived`\ `JoinRequestAccepted`\ `JoinRequestRejected`\ `MemberInvited`\ `ExpertRequested`\ `ExpertRequestAccepted`\ `ExpertRequestDeclined`\ `StatusUpdatePublished`\ `ProjectSpaceActivated`\ `ProjectSpaceCompleted`],
  [`ProjectIdeaApproved`],

  [`inno_moderation`],
  [`/api/moderation`\ `/api/moderation/category`],
  [`ModerationDecisionMade`\ `ModerationFeedbackRequested`],
  [`ProjectIdeaSubmitted`\ `ProjectIdeaResubmitted`],

  [`inno_notification`],
  [`/api/notification`],
  [—],
  [`ProjectIdeaSubmitted`\ `ProjectIdeaApproved`\ `ProjectIdeaRejected`],
)

*Begründung:*
Die strikte Trennung nach Bounded Contexts verhindert implizite Abhängigkeiten zwischen Domänen. Der Symfony EventDispatcher, der Concrete CMS intern bereits verwendet, ermöglicht eine lose Kopplung ohne zusätzliche Infrastruktur. REST APIs bilden die synchronen Einstiegspunkte für Benutzerinteraktionen.

Alle cross-package Event-Klassen liegen im `inno_shared`-Package. Kein Domain-Package importiert Code eines anderen Domain-Packages — nur Events aus `inno_shared`.


=== Blackbox `inno_frontend`
*Verantwortung:* Theme, SCSS/TypeScript-Komponenten und Atomic Design Component Library. Stellt die Benutzeroberfläche bereit. Enthält keine Domänenlogik.

*Schnittstellen:* Keine API. Assets werden mit Laravel Mix (webpack) gebaut und im Package unter `themes/innovationsbox/` abgelegt. Der Theme-Handle ist `innovationsbox`; der Package-Handle ist `inno_frontend`.

*Component Library (Storybook):* Die UI-Komponenten sind nach dem *Atomic Design*-Muster organisiert und über Storybook (`@storybook/server-webpack5`) dokumentiert und testbar. Jede Komponente ist ein reines PHP-Partial (`$props` → HTML), das sowohl im CMS als auch in Storybook gerendert wird — 1:1 Wiederverwendung.

```
public/packages/inno_frontend/components/
  atoms/         ← 24 Basiskomponenten: avatar, badge, button, caption, checkbox, code, container,
                                        divider, email-link, heading, icon, image, input, label, link,
                                        overlay, paragraph, radio, select, skip-link, spinner, textarea,
                                        toggle, tooltip
  molecules/     ← 26 zusammengesetzte Gruppen: accent-text, alert, auth-card, avatar-upload,
                                                breadcrumb, card, collaboard-item, dropdown,
                                                empty-state, feature-button, form-field,
                                                join-request-card, member-card, modal, nav-item,
                                                page-card, pagination, partner-logo, password-field,
                                                project-card, question-text, search-bar, skeleton,
                                                status-update-entry, tabs, user-menu
  organisms/     ← 9 komplexe Sektionen: collaboard-panel, content-block, footer, header, hero-image,
                                         navigation-drawer, page-list-grid, project-space-sidebar,
                                         status-update-timeline
  templates/     ← 2 Seitenlayouts: default-page, view-page
  pages/         ← 2 Beispielseiten: homepage, inner-page
```

*Kompositionsregel:* Jede Ebene importiert nur von der darunterliegenden Ebene. Molecules nutzen Atoms, Organisms nutzen Molecules, Templates nutzen Organisms.

*Design Tokens:* Alle Designwerte (Farben, Typografie, Abstände, Radien, Schatten) sind in `resources/tokens/tokens.json` definiert. Ein Build-Skript generiert daraus SCSS-Variablen.

*Block-Migration:* Die CMS-Block-Templates (`blocks/*/view.php`) sind dünne Wrapper, die CMS-Daten in ein `$props`-Array extrahieren und das Component-Partial via `DIR_COMPONENTS` einbinden.


=== Blackbox `inno_shared`
*Verantwortung:* Shared Kernel — enthält Event-Klassen, die von mehreren Domain-Packages konsumiert werden, sowie cross-package Port-Interfaces. Keine Domänenlogik, keine Use Cases, keine Adapter.

*Schnittstellen:* Keine API. Stellt PHP-Klassen unter `Concrete\Package\InnoShared\Domain\Event\` bereit.

*Port:* `CategoryReaderPort` — Read-only Interface für paketübergreifenden Zugriff auf die Kategorienliste. Ermöglicht es `inno_project_space` und anderen Packages, Kategorien zu lesen, ohne `inno_moderation` direkt zu importieren.


=== Blackbox `inno_project_idea`
*Verantwortung:* Lifecycle einer Projektidee von der Einreichung bis zur Freigabe oder Ablehnung. Hält den autoritativen Status einer Projektidee.

*Schnittstellen:* REST `GET/POST /api/project-idea`, `GET/PUT /api/project-idea/{id}`. Emittiert Events bei Statusübergängen.


=== Blackbox `inno_project_space`
*Verantwortung:* Geschützter Kollaborationsraum für ein genehmigtes Projekt. Verwaltet Mitgliedschaft und Projektstatus. Unterstützt Filterung nach Kategorie und Volltextsuche.

*Schnittstellen:* REST `GET /api/project-space`, `GET /api/project-space/{id}`, `GET /api/project-space/categories`, `POST /api/project-space/{id}/join-request`, `PUT /api/project-space/{id}/join-request/{requestId}`, `POST /api/project-space/{id}/invite`, `POST /api/project-space/{id}/expert-request`, `PUT /api/project-space/{id}/expert-request/{requestId}`, `GET /api/expert-request/mine`, `POST /api/project-space/{id}/status-update`, `PUT /api/project-space/{id}/status`, `POST /api/project-space/{id}/collaboard`, `DELETE /api/project-space/{id}/collaboard/{linkId}`. Query-Parameter: `?category=slug`, `?search=term`. Wird automatisch angelegt, sobald eine Projektidee genehmigt wird.

*Tabellen:* `ib_project_spaces` (enthält `category_id` FK auf `ib_categories`), `ib_project_space_join_requests`, `ib_expert_requests`, `ib_project_space_collaboards`, `ib_status_updates`.


=== Blackbox `inno_moderation`
*Verantwortung:* Prüft eingereichte Projektideen und trifft eine Freigabe- oder Ablehnungsentscheidung. Hält eine `ModerationReview` pro Idee. Verwaltung von Projektkategorien (CRUD).

*Schnittstellen:* REST `GET /api/moderation` (pending Reviews), `GET /api/moderation/{id}`, `POST /api/moderation/{id}/approve`, `POST /api/moderation/{id}/reject`, `POST /api/moderation/{id}/feedback`. Kategorie-CRUD: `GET/POST /api/moderation/category`, `PUT/DELETE /api/moderation/category/{id}`.

*Tabellen:* `ib_moderation_reviews`, `ib_categories` (id, name, slug, sort_order, created_at, updated_at).

*Use Cases:* `ReviewProjectIdea`, `ManageCategory` (Create, Update, Delete, List).


=== Blackbox `inno_notification`
*Verantwortung:* Versendet E-Mails und Systemnachrichten als Reaktion auf Domänen-Events. Enthält keine eigene Domänenlogik — reine Reaktionsschicht.

*Schnittstellen:* REST `GET /api/notification` (eigene Benachrichtigungen), `POST /api/notification/{id}/read`.

// ---------------------------------------------------------
// 5.2 Ebene 2 — Hexagonale Architektur pro Domain-Package
// ---------------------------------------------------------

== Ebene 2 — Hexagonale Architektur

Jedes der vier Domain-Packages (`inno_project_idea`, `inno_project_space`, `inno_moderation`, `inno_notification`) ist intern nach dem *Hexagonal Architecture*-Muster (Ports & Adapters) aufgebaut. `Application/` ist das Hexagon-Zentrum. Die Packages `inno_frontend` und `inno_shared` folgen diesem Muster nicht.

```
src/
  Domain/               ← Reines Domänenmodell (Entities, Enums), keine Framework-Abhängigkeiten
  Application/          ← Hexagon-Zentrum: orchestriert die Domain
    UseCase/            ← Command + Handler Paare; Handler implementieren Port/In/-Interfaces
    EventHandler/       ← Reagiert auf Events aus inno_shared
  Port/
    In/                 ← Primary Ports: ein Interface pro Use Case (z. B. SubmitProjectIdeaPort)
    Out/                ← Secondary Ports: Repository- und Service-Interfaces
  Adapter/
    In/
      Http/             ← REST-Controller (extend AbstractController, inject Port/In-Interfaces)
    Out/
      Persistence/      ← Repository-Implementierungen via Concrete\Core\Database\Connection
      Email/            ← E-Mail-Adapter (nur inno_notification)
```

*Abhängigkeitsregel:* `Adapter/In → Port/In → Application/ → Domain/` und `Adapter/Out ← Port/Out ← Application/`. Kein Package importiert Code eines anderen Domain-Packages — alle cross-package Events liegen in `inno_shared`.

*Dependency Injection:* Jedes Package bindet seine Interface-Implementierungen in `on_start()` über `$this->app->bind(Interface::class, Implementation::class)` — sowohl `Port/Out/`- als auch `Port/In/`-Interfaces.


=== Whitebox `inno_project_idea`

Das `inno_project_idea`-Package ist das Kernpackage der Plattform. Es besitzt den `ProjectIdea`-Aggregate-Root und ist für alle Statusübergänge verantwortlich.

#table(
  columns: (auto, 1fr),
  align: left,
  inset: 7pt,
  stroke: 0.5pt,
  table.header(
    [*Baustein*], [*Verantwortung*]
  ),

  [`Domain/Model/ProjectIdea`],
  [Aggregate-Root. Zustände: `submitted → approved/rejected`. Methoden: `submit()`, `approve()`, `reject()`.],

  [`Domain/Model/IdeaStatus`],
  [Enum: `Submitted`, `UnderReview`, `Approved`, `Rejected`.],

  [`Port/In/SubmitProjectIdeaPort`],
  [Primary Port: Interface für den Use Case `SubmitProjectIdea`. Implementiert von `SubmitProjectIdeaHandler`.],

  [`Port/In/EditProjectIdeaPort`],
  [Primary Port: Interface für den Use Case `EditProjectIdea`. Implementiert von `EditProjectIdeaHandler`.],

  [`Port/In/DeleteProjectIdeaPort`],
  [Primary Port: Interface für den Use Case `DeleteProjectIdea`. Implementiert von `DeleteProjectIdeaHandler`.],

  [`Port/Out/ProjectIdeaRepositoryInterface`],
  [Secondary Port für Persistenz. Implementiert von `ConcreteProjectIdeaRepository`.],

  [`Application/UseCase/SubmitProjectIdea`],
  [Command + Handler (implementiert `SubmitProjectIdeaPort`). Erstellt `ProjectIdea`, speichert, dispatcht `ProjectIdeaSubmitted` (aus `inno_shared`).],

  [`Application/EventHandler/OnModerationDecisionMade`],
  [Hört auf `ModerationDecisionMade` (aus `inno_shared`). Ruft `approve()` oder `reject()` auf der Idee auf, dispatcht `ProjectIdeaApproved` oder `ProjectIdeaRejected`.],

  [`Application/EventHandler/OnModerationFeedbackRequested`],
  [Hört auf `ModerationFeedbackRequested` (aus `inno_shared`). Setzt Status der Idee auf `FeedbackRequested`, speichert Deadline und Hinweis.],

  [`Application/EventHandler/OnProjectSpaceCreated`],
  [Hört auf `ProjectSpaceCreated` (aus `inno_shared`). Verknüpft die Idee mit der ID des erstellten Projektraums (`project_space_id`).],

  [`Adapter/In/Http/ProjectIdeaApiController`],
  [REST-Controller. Injiziert `SubmitProjectIdeaPort`, `EditProjectIdeaPort`, `DeleteProjectIdeaPort` und `ProjectIdeaRepositoryInterface`.],

  [`Adapter/Out/Persistence/ConcreteProjectIdeaRepository`],
  [Implementiert `ProjectIdeaRepositoryInterface` via `Concrete\Core\Database\Connection`. Tabelle: `ib_project_ideas`.],
)


=== Whitebox `inno_moderation`

#table(
  columns: (auto, 1fr),
  align: left,
  inset: 7pt,
  stroke: 0.5pt,
  table.header(
    [*Baustein*], [*Verantwortung*]
  ),

  [`Domain/Model/ModerationReview`],
  [Entity. Zustände: `Pending → Approved/Rejected`. Methoden: `createPending()`, `approve()`, `reject()`.],

  [`Domain/Model/ModerationDecision`],
  [Enum: `Pending`, `Approved`, `Rejected`.],

  [`Port/In/ReviewProjectIdeaPort`],
  [Primary Port: Interface für den Use Case `ReviewProjectIdea`. Implementiert von `ReviewProjectIdeaHandler`.],

  [`Port/Out/ModerationReviewRepositoryInterface`],
  [Secondary Port für Persistenz. Implementiert von `ConcreteModerationReviewRepository`.],

  [`Application/UseCase/ReviewProjectIdea`],
  [Command + Handler (implementiert `ReviewProjectIdeaPort`). Aktualisiert `ModerationReview`, dispatcht `ModerationDecisionMade` (aus `inno_shared`).],

  [`Application/EventHandler/OnProjectIdeaSubmitted`],
  [Hört auf `ProjectIdeaSubmitted` (aus `inno_shared`). Erstellt eine neue `ModerationReview` mit Status `Pending`.],

  [`Application/EventHandler/OnProjectIdeaResubmitted`],
  [Hört auf `ProjectIdeaResubmitted` (aus `inno_shared`). Setzt die bestehende `ModerationReview` zurück auf Status `Pending`.],

  [`Adapter/In/Http/ModerationApiController`],
  [REST-Controller für Admin-Aktionen. Injiziert `ReviewProjectIdeaPort`. Endpunkte: `approve`, `reject`, `requestFeedback`.],

  [`Adapter/Out/Persistence/ConcreteModerationReviewRepository`],
  [Implementiert `ModerationReviewRepositoryInterface` via `Connection`. Tabelle: `ib_moderation_reviews`.],

  [`Domain/Model/Category`],
  [Entity. Felder: id, name, slug, sort_order, created_at, updated_at.],

  [`Port/In/ManageCategoryPort`],
  [Primary Port: Interface für die Use Cases Create, Update, Delete, List von Kategorien.],

  [`Port/Out/CategoryRepositoryInterface`],
  [Secondary Port für Persistenz. Implementiert von `ConcreteCategoryRepository`.],

  [`Application/UseCase/ManageCategory`],
  [Command + Handler (implementiert `ManageCategoryPort`). CRUD-Operationen auf `Category`.],

  [`Adapter/In/Http/CategoryApiController`],
  [REST-Controller für Kategorie-CRUD. Injiziert `ManageCategoryPort`.],

  [`Adapter/Out/Persistence/ConcreteCategoryRepository`],
  [Implementiert `CategoryRepositoryInterface` via `Connection`. Tabelle: `ib_categories`.],
)


=== Whitebox `inno_project_space`

#table(
  columns: (auto, 1fr),
  align: left,
  inset: 7pt,
  stroke: 0.5pt,
  table.header(
    [*Baustein*], [*Verantwortung*]
  ),

  [`Domain/Model/ProjectSpace`],
  [Entity. Hält Mitglieder-, Join-Request- und Expert-Request-Listen. Methoden: `createFromApprovedIdea()`, `addMember()`, `requestToJoin()`, `handleJoinRequest()`, `requestExpert()`, `handleExpertRequest()`, `invite()`, `changeStatus()`.],

  [`Port/In/RequestToJoinPort`],
  [Primary Port für den Use Case `RequestToJoin`. Dispatcht `JoinRequestReceived`.],

  [`Port/In/HandleJoinRequestPort`],
  [Primary Port für den Use Case `HandleJoinRequest`. Dispatcht `JoinRequestAccepted` oder `JoinRequestRejected`.],

  [`Port/In/InviteMemberPort`],
  [Primary Port für den Use Case `InviteMember`. Dispatcht `MemberInvited`.],

  [`Port/In/RequestExpertPort`],
  [Primary Port für den Use Case `RequestExpert`. Dispatcht `ExpertRequested`.],

  [`Port/In/HandleExpertRequestPort`],
  [Primary Port für den Use Case `HandleExpertRequest`. Dispatcht `ExpertRequestAccepted` oder `ExpertRequestDeclined`.],

  [`Port/In/WriteStatusUpdatePort`],
  [Primary Port für den Use Case `WriteStatusUpdate`. Dispatcht `StatusUpdatePublished`.],

  [`Port/In/ChangeSpaceStatusPort`],
  [Primary Port für den Use Case `ChangeSpaceStatus`. Dispatcht `ProjectSpaceActivated` oder `ProjectSpaceCompleted`.],

  [`Port/In/AddCollaboardLinkPort`],
  [Primary Port für den Use Case `AddCollaboardLink`.],

  [`Port/Out/ProjectSpaceRepositoryInterface`],
  [Secondary Port für Persistenz. Implementiert von `ConcreteProjectSpaceRepository`.],

  [`Application/EventHandler/OnProjectIdeaApproved`],
  [Hört auf `ProjectIdeaApproved` (aus `inno_shared`). Erstellt automatisch einen `ProjectSpace`, dispatcht `ProjectSpaceCreated` (aus `inno_shared`).],

  [`Adapter/In/Http/ProjectSpaceApiController`],
  [REST-Controller: Übersicht, Detailansicht, Join-Request, Einladung, Expert-Request, Status-Updates, Statuswechsel, Collaboard-Links. Injiziert alle `Port/In`-Interfaces.],

  [`Adapter/Out/Persistence/ConcreteProjectSpaceRepository`],
  [Implementiert `ProjectSpaceRepositoryInterface` via `Connection`. Tabellen: `ib_project_spaces`, `ib_project_space_join_requests`, `ib_project_space_expert_requests`, `ib_project_space_collaboards`, `ib_status_updates`.],
)


=== Whitebox `inno_notification`

Das `inno_notification`-Package hat keine eigene Domain-Schicht (keine Entities). Es reagiert ausschliesslich auf Events anderer Packages.

#table(
  columns: (auto, 1fr),
  align: left,
  inset: 7pt,
  stroke: 0.5pt,
  table.header(
    [*Baustein*], [*Verantwortung*]
  ),

  [`Port/Out/EmailServiceInterface`],
  [Secondary Port. Abstrahiert den E-Mail-Versand.],

  [`Application/EventHandler/OnProjectIdeaSubmitted`],
  [Hört auf `ProjectIdeaSubmitted` (aus `inno_shared`). Benachrichtigt Administratorinnen über neue Einreichungen.],

  [`Application/EventHandler/OnProjectIdeaApproved`],
  [Hört auf `ProjectIdeaApproved` (aus `inno_shared`). Benachrichtigt die Initiatorin über die Freigabe.],

  [`Application/EventHandler/OnProjectIdeaRejected`],
  [Hört auf `ProjectIdeaRejected` (aus `inno_shared`). Benachrichtigt die Initiatorin über die Ablehnung mit Begründung.],

  [`Adapter/In/Http/NotificationApiController`],
  [REST-Controller: eigene Benachrichtigungen lesen, als gelesen markieren.],

  [`Adapter/Out/Email/ConcreteEmailService`],
  [Implementiert `EmailServiceInterface` via `Concrete\Core\Mail\Service`.],
)
