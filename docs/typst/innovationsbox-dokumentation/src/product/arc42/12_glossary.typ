#import "../../../common/styles/help-style.typ": help, show-help

// adoc/12_glossary.typ
// ---------------------------------------------
// Section 12: Glossar (arc42)
// ---------------------------------------------

= Ubiquitous Language / Glossar
#label("section-glossary")

#if show-help [
  #help[
    *Inhalt:*
    Die wesentlichen fachlichen und technischen Begriffe, die Stakeholder
    im Zusammenhang mit dem System verwenden.

    Das Glossar kann auch als Übersetzungsreferenz dienen, falls
    in mehrsprachigen Teams gearbeitet wird.

    *Motivation:*
    Relevante Begriffe sollten klar definiert sein, damit alle Beteiligten

    - diese Begriffe identisch verstehen und
    - vermeiden, mehrere Begriffe für die gleiche Sache zu verwenden.

    *Form:*
    Zweispaltige Tabelle mit <Begriff> und <Definition>.
    Falls nötig können zusätzliche Spalten für Übersetzungen
    oder Synonyme ergänzt werden.

    *Weiterführende Informationen:*
    Siehe
    #link("https://docs.arc42.org/section-12/")[Glossar]
    in der Online-Dokumentation (englisch).
  ]
]

== Leitprinzip
Die folgenden Begriffe sollen in Workshops, Spezifikationen, Backlog, UX-Texten und später im Code konsequent gleich verwendet werden.

Besonders wichtig ist die klare Trennung zwischen Projektidee, Projektraum, Projektstatus und Einreichungsstatus.

Werden Begriffe übersetzt, sollen die Übersetzungen ebenfalls hier abgelegt werden.

#table(
  columns: (auto, 2fr),
  align: left,
  inset: 6pt,
  stroke: 0.5pt,
  table.header([*Begriff*], [*Definition*]),
  [ Innovationsbox ],
  [ Digitale Plattform für das jeinreichen, Sichtbarmachen und kollaborative Weiterentwickeln klimarelevanter Projektideen. ],

  [ Besucherin ],
  [Nicht angemeldete Person. Sie kann öffentliche Projektideen sehen, filtern und den sichbaren Projektstatus sowie Unterstützungsbedarf erkennen. ],

  [ CategoryReaderPort ],
  [ Read-only Interface in `inno_shared`, das anderen Packages den Zugriff auf die Kategorienliste ermöglicht, ohne das `inno_moderation`-Package direkt zu importieren. ],

  [ Mitglied ], [ Registrierte Person mit Profil. Oberbegriff für Initiatorin, Entwicklerin und Administratorin ],
  [ Entwicklerin ],
  [ Registriertes Mitglied, das Kompetenzen anbietet und an Projektideen mitarbeiten kann. Ist im Expertinnen-Pool auffindbar. ],

  [ Initiatorin ], [ Mitglied, das eine Projektidee einreicht und nach Freigabe den Projektraum führt. ],
  [ Administratorin ],
  [ MItglied mit MOderations- und Freigaberechten. Prüft Projektideen, verwaltet Freigaben und kann Inahalte moderieren. ],

  [ Profil ],
  [ Datensatz eines Mitgliedes mit Identität, Kontaktangaben, Kompetenzen, Kurprofil und optionalem Porträt. ],

  [ Kompetenzen ], [ Fachgebiet, Fähigkeit oder Erfahrung eines Mitglieds ],
  [ Expertinnen-Pool ], [ Durchsuchbares Verzeichnis von Mitgliedern mit Profil und Kompetenzen. ],
  [ Projektidee ],
  [ Eingereichter Vorschlag für eine klimarelevante Innovation mit Titel, Beschrieb, Kategorie, Bildern, Initiatorin und Status. ],

  [ Kategorie ],
  [ Dynamisch verwaltete Projektkategorie (z.B. Bauen, Wohnen, Mobilität). Wird vom Moderator über die REST-API verwaltet und bei der Ideenerfassung zugewiesen. Gespeichert in `ib_categories`. ],

  [ Projektstatus ],
  [ Öffentlich sichbarer Entwicklungsstand einer Projektidee oder eines Projekts: in Entwicklung, in Markteinführung, erfolgreich im Markt. ],

  [ Einreichestatus ],
  [ Interner Prüf- und Freigabestatus einer Projektidee: Entwurf, Eingereicht, In Prüfung, Freigegeben, Abgelehnt. ],

  [ Projektraum ], [ Geschützter digitaler Raum für die Zusammenarbeit an einer freigegebenen Projektidee. ],
  [ Teilnahmeanfrage], [ Anfrage eines Mitglied  einem Projektraum beizutreten. ],
  [ Einladung ], [ Aufforderung durch Initiatorin oder System an ein Mitglied, einem Projektraum beizutreten. ],
  [ Unterstützungsbedarf ],
  [ Öffentlich sichtbare Beschreibung, welche Kompetenzen oder ROllen aktuell gesucht werden. ],

  [ Aktualisierung ], [ Fortschrittsbeitrag aus dem Projektraum, der öffentlich publiziert werden kann. ],
  [ Moderation ],
  [ Prozess zur Prüfung und Freigabe eingereichter Projektideen, bevor diese öffentlich sichtbar werden. ],

  [ Rolle ],
  [ Sammlung von Rechten, die bestimmt, welche Funktionen ein Benutzer innerhalb der Plattform nutzen darf. ],

  [ Benachrichtigung ], [ Systemnachricht, die Benutzer über Erreignisse innerhalb der Plattform informiert ],
)

=== Wichtige sprachliche Trennungen
- *Projektideen ≠ Projektraum:* Die Projektidee ist das öffentlich sichtbare fachliche Objekt. Der Projektraum ist der geschützte Kollaborationsraum dazu.
- *Projektstatus ≠ Einreichungsstatus:* Projektstatus beschreibt den Entwicklungsstand. Einreichungsstatus beschreibt die Prüfung und Freigabe.
- *Mitglied ≠ Rolle:* Ein Mensch ist Mitglied und kann iene oder mehrere Rollen haben.
- *Expertinnen-Pool ≠ Projektteam:* Der Pool ist plattformweit suchbar. Das Team gehört zu einem konkreten Projektraum.

=== Übersetzungen ins Englische
Weil wir in der Programmierung mit Englischenbegriffen arbeiten, hier die dazugehörigen Übersetzungen.

#table(
  columns: (auto, auto, 2fr),
  align: left,
  inset: 6pt,
  stroke: 0.5pt,
  table.header([*Deutsch*], [*Englisch (Code)*], [*Hinweis*]),
  [ Benutzerverwaltung ], [ User Management ], [ CMS-seitig, kein eigenes Package ],
  [ Projektidee ], [ `ProjectIdea` ], [ Package `project_idea`, Klasse `Domain/Model/ProjectIdea` ],
  [ Einreichestatus ], [ `IdeaStatus` ], [ Enum: `Submitted`, `UnderReview`, `Approved`, `Rejected` ],
  [ Projekträume ], [ `ProjectSpace` ], [ Package `project_space`, Klasse `Domain/Model/ProjectSpace` ],
  [ Moderation ], [ `ModerationReview` ], [ Package `moderation`, Klasse `Domain/Model/ModerationReview` ],
  [ Moderationsentscheid ], [ `ModerationDecision` ], [ Enum: `Pending`, `Approved`, `Rejected` ],
  [ Benachrichtigung ], [ `Notification` ], [ Package `notification` ],
  [ Einreichung ], [ `ProjectIdeaSubmitted` ], [ Domain Event, Name: `innovationsbox.project_idea.submitted` ],
  [ Freigabe ], [ `ProjectIdeaApproved` ], [ Domain Event, Name: `innovationsbox.project_idea.approved` ],
  [ Ablehnung ], [ `ProjectIdeaRejected` ], [ Domain Event, Name: `innovationsbox.project_idea.rejected` ],
  [ Moderationsentscheid getroffen ],
  [ `ModerationDecisionMade` ],
  [ Domain Event, Name: `innovationsbox.moderation.decision_made` ],

  [ Projektraum erstellt ], [ `ProjectSpaceCreated` ], [ Domain Event, Name: `innovationsbox.project_space.created` ],
  [ Kategorie ], [ `Category` ], [ Entity in `inno_moderation`, Tabelle `ib_categories` ],
  [ Kategorie-Lesezugriff ], [ `CategoryReaderPort` ], [ Read-only Port in `inno_shared` ],
)
