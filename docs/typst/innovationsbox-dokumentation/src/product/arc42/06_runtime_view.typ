#import "../../../common/styles/help-style.typ": show-help, help

// adoc/06_runtime_view.typ
// ---------------------------------------------
// Section 6: Laufzeitsicht (arc42)
// ---------------------------------------------

= Laufzeitsicht
#label("section-runtime-view")

Die Laufzeitsicht beschreibt, wie die Packages der Innovationsbox zur Laufzeit miteinander interagieren. Die Kommunikation erfolgt entweder synchron via REST API (Benutzerinteraktion) oder asynchron über den Symfony EventDispatcher (Package-zu-Package). Event-Listener werden in `on_start()` des jeweiligen Packages registriert und reagieren innerhalb desselben HTTP-Requests.

// ---------------------------------------------------------
// Szenario 1
// ---------------------------------------------------------

== Szenario: Projektidee einreichen

Eine Initiatorin reicht über die Weboberfläche eine neue Projektidee ein.

```
Initiatorin
  │
  ▼  POST /api/project-idea  (title, description, category)
┌─────────────────────────────────────────────┐
│ project_idea                                │
│  ProjectIdeaApiController::store()          │
│    → SubmitProjectIdeaHandler               │
│        ProjectIdea::submit()                │
│        repository.save(idea)                │
│        dispatch(ProjectIdeaSubmitted)       │
└────────────┬────────────────────────────────┘
             │ Event: innovationsbox.project_idea.submitted
     ┌───────┴──────────────────────────────────┐
     │                                          │
     ▼                                          ▼
┌─────────────────────┐            ┌────────────────────────┐
│ moderation          │            │ notification           │
│  OnProjectIdea-     │            │  OnProjectIdea-        │
│  Submitted          │            │  Submitted             │
│  ModerationReview   │            │  → E-Mail an Admins    │
│  ::createPending()  │            └────────────────────────┘
│  repository.save()  │
└─────────────────────┘
```

*Beteiligte Packages:* `project_idea`, `moderation`, `notification`

*Ergebnis:* Die Idee hat Status `submitted`. Eine pendente `ModerationReview` existiert. Administratorinnen wurden per E-Mail benachrichtigt.

// ---------------------------------------------------------
// Szenario 2
// ---------------------------------------------------------

== Szenario: Projektidee moderieren und freigeben

Eine Administratorin gibt eine eingereichte Projektidee frei.

```
Administratorin
  │
  ▼  POST /api/moderation/{id}/approve  (notes)
┌─────────────────────────────────────────────┐
│ moderation                                  │
│  ModerationApiController::approve()         │
│    → ReviewProjectIdeaHandler               │
│        review.approve(reviewerId, notes)    │
│        repository.save(review)              │
│        dispatch(ModerationDecisionMade)     │
└────────────┬────────────────────────────────┘
             │ Event: innovationsbox.moderation.decision_made
             ▼
┌─────────────────────────────────────────────┐
│ project_idea                                │
│  OnModerationDecisionMade                   │
│    idea.approve()                           │
│    repository.save(idea)                    │
│    dispatch(ProjectIdeaApproved)            │
└────────────┬────────────────────────────────┘
             │ Event: innovationsbox.project_idea.approved
     ┌───────┴──────────────────────────────────┐
     │                                          │
     ▼                                          ▼
┌─────────────────────────────┐   ┌────────────────────────┐
│ project_space               │   │ notification           │
│  OnProjectIdeaApproved      │   │  OnProjectIdeaApproved │
│  ProjectSpace::create...()  │   │  → E-Mail an           │
│  repository.save(space)     │   │    Initiatorin         │
│  dispatch(ProjectSpace-     │   └────────────────────────┘
│           Created)          │
└─────────────────────────────┘
```

*Beteiligte Packages:* `moderation`, `project_idea`, `project_space`, `notification`

*Ergebnis:* Die Idee hat Status `approved`. Ein `ProjectSpace` wurde automatisch erstellt. Die Initiatorin wurde per E-Mail informiert.

*Ablehnungs-Variante:* Bei `reject` folgt derselbe Event-Chain bis `ProjectIdeaRejected`. `project_space` wird nicht involviert. `notification` versendet eine E-Mail mit der Begründung.

// ---------------------------------------------------------
// Szenario 3
// ---------------------------------------------------------

== Szenario: Einem Projektraum beitreten

Ein Mitglied tritt einem freigegebenen Projektraum bei.

```
Mitglied
  │
  ▼  POST /api/project-space/{id}/join
┌─────────────────────────────────────────────┐
│ project_space                               │
│  ProjectSpaceApiController::join()          │
│    space = repository.findById(id)          │
│    space.addMember(userId)                  │
│    repository.save(space)                   │
└─────────────────────────────────────────────┘
```

*Beteiligte Packages:* `project_space`

*Ergebnis:* Das Mitglied ist im `ProjectSpace` registriert und ein `JoinRequestReceived`-Event wird dispatcht.

*Einladungs-Variante:* `POST /api/project-space/{id}/invite` — erstellt eine Einladung, dispatcht `MemberInvited`.

// ---------------------------------------------------------
// Szenario 4
// ---------------------------------------------------------

== Szenario: Projektidee öffentlich abrufen

Eine Besucherin liest die öffentliche Liste der freigegebenen Projektideen.

```
Besucherin
  │
  ▼  GET /api/project-idea
┌─────────────────────────────────────────────┐
│ project_idea                                │
│  ProjectIdeaApiController::index()          │
│    repository.findAll()                     │
│    → JSON Response                          │
└─────────────────────────────────────────────┘
```

*Beteiligte Packages:* `project_idea`

*Hinweis:* Filterung nach Status `approved` für öffentliche Ansicht ist im Controller zu implementieren.

// ---------------------------------------------------------
// Szenario 5
// ---------------------------------------------------------

== Szenario: Projekträume nach Kategorie filtern

Eine Besucherin filtert die öffentliche Projektraum-Übersicht nach einer bestimmten Kategorie.

+ Die Besucherin klickt auf der Übersichtsseite auf einen Kategorie-Pill (z. B. «Bauen»).
+ Das Frontend sendet `GET /api/project-space?category=bauen` an den Server.
+ `ProjectSpaceApiController::index()` nimmt den Query-Parameter `category` entgegen und übergibt den Slug an `findPublic()`.
+ `ConcreteProjectSpaceRepository` führt einen JOIN von `ib_project_spaces` auf `ib_categories` aus (`category_id = c.id`) mit der Bedingung `c.slug = ?`.
+ Die gefilterten Projekträume werden als JSON-Array zurückgegeben.
+ Das Frontend rendert die gefilterte Liste.

```
Besucherin
  │
  ▼  GET /api/project-space?category=bauen
┌─────────────────────────────────────────────┐
│ project_space                               │
│  ProjectSpaceApiController::index()         │
│    slug = request.query('category')         │
│    repository.findPublic(category: slug)    │
│    → JOIN ib_categories ON category_id      │
│      WHERE c.slug = 'bauen'                 │
│    → JSON Response (filtered)               │
└─────────────────────────────────────────────┘
```

*Beteiligte Packages:* `project_space`

*Ergebnis:* Nur Projekträume der Kategorie «Bauen» werden zurückgegeben. Die Kategorienliste stammt aus `ib_categories`, verwaltet durch `inno_moderation`.

// ---------------------------------------------------------
// Szenario 6
// ---------------------------------------------------------

== Szenario: Moderationsfeedback anfordern und Idee erneut einreichen

Eine Administratorin fordert Nachbesserungen an einer Projektidee an. Die Initiatorin überarbeitet die Idee und reicht sie erneut ein.

```
Administratorin
  │
  ▼  POST /api/moderation/{id}/feedback  (notes, deadline)
┌─────────────────────────────────────────────┐
│ moderation                                  │
│  ModerationApiController::requestFeedback() │
│    → ReviewProjectIdeaHandler               │
│        review.requestFeedback(notes, dl)    │
│        repository.save(review)              │
│        dispatch(ModerationFeedbackRequested)│
└────────────┬────────────────────────────────┘
             │ Event: innovationsbox.moderation.feedback_requested
             ▼
┌─────────────────────────────────────────────┐
│ project_idea                                │
│  OnModerationFeedbackRequested              │
│    idea.requestFeedback(notes, deadline)    │
│    repository.save(idea)                    │
└─────────────────────────────────────────────┘

Initiatorin
  │
  ▼  PUT /api/project-idea/{id}  (updated fields)
┌─────────────────────────────────────────────┐
│ project_idea                                │
│  ProjectIdeaApiController::update()         │
│    → EditProjectIdeaHandler                 │
│        idea.resubmit()                      │
│        repository.save(idea)                │
│        dispatch(ProjectIdeaResubmitted)     │
└────────────┬────────────────────────────────┘
             │ Event: innovationsbox.project_idea.resubmitted
             ▼
┌─────────────────────────────────────────────┐
│ moderation                                  │
│  OnProjectIdeaResubmitted                   │
│    review.resetToPending()                  │
│    repository.save(review)                  │
└─────────────────────────────────────────────┘
```

*Beteiligte Packages:* `moderation`, `project_idea`

*Ergebnis:* Die Idee kehrt in die Moderationswarteschlange zurück (`Pending`). Die Administratorin kann die überarbeitete Idee erneut prüfen.

// ---------------------------------------------------------
// Szenario 7
// ---------------------------------------------------------

== Szenario: Expertin in einen Projektraum einladen

Die Initiatorin eines Projektraums lädt eine externe Expertin ein, am Projekt mitzuwirken.

```
Initiatorin
  │
  ▼  POST /api/project-space/{id}/expert-request  (expertUserId, message)
┌─────────────────────────────────────────────┐
│ project_space                               │
│  ProjectSpaceApiController::requestExpert() │
│    → RequestExpertHandler                   │
│        space.requestExpert(userId, msg)     │
│        repository.save(space)               │
│        dispatch(ExpertRequested)            │
└─────────────────────────────────────────────┘

Expertin
  │
  ▼  PUT /api/project-space/{id}/expert-request/{requestId}  (accepted: true)
┌─────────────────────────────────────────────┐
│ project_space                               │
│  ProjectSpaceApiController::                │
│    handleExpertRequest()                    │
│    → HandleExpertRequestHandler             │
│        space.acceptExpertRequest(requestId) │
│        repository.save(space)               │
│        dispatch(ExpertRequestAccepted)      │
└─────────────────────────────────────────────┘
```

*Beteiligte Packages:* `project_space`

*Ergebnis:* Die Expertin ist als Mitglied im `ProjectSpace` registriert. Bei Ablehnung wird `ExpertRequestDeclined` dispatcht und kein Mitglied hinzugefügt.
