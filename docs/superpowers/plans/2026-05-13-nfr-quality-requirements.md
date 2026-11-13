# NFR Quality Requirements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restructure arc42 Sections 1.2 and 10 around five Q42 quality categories with 21 SMART scenarios.

**Architecture:** Two Typst files are modified. Section 1.2 gets a new 5-row quality goals table aligned to Q42 IDs. Section 10 gets a restructured overview table (5 categories replacing 7) and 21 scenarios grouped by category (REL, USA, MNT, SUI, OPR), replacing the old QS-01..07.

**Tech Stack:** Typst markup language, arc42 template conventions

**Spec:** `docs/superpowers/specs/2026-05-13-nfr-quality-requirements-design.md`

---

### Task 1: Update Section 1.2 Quality Goals

**Files:**
- Modify: `docs/typst/innovationsbox-dokumentation/src/product/arc42/01_introduction_and_goals.typ:72-126`

- [ ] **Step 1: Replace the quality goals table**

Replace lines 72-126 (from `== Qualitätsziele` through the closing `)` of the table) with the following Typst content:

```typst
== Qualitätsziele

#table(
  columns: (auto, auto, 1fr),
  align: left,
  inset: 8pt,
  stroke: 0.5pt,

  [*Priorität*], [*Qualitätsziel*], [*Szenario*],

  [1],
  [Benutzbarkeit (_Usable_, Q42 \#103)],
  [
  Eine Besucherin öffnet die Projektübersicht und findet innerhalb
  weniger Sekunden relevante Projektideen. Navigation und Interaktion
  sind ohne Schulung verständlich.
  ],

  [2],
  [Zuverlässigkeit (_Reliable_, Q42 \#107)],
  [
  Die Plattform ist während der Geschäftszeiten stabil erreichbar.
  Eingereichte Projektideen gehen nicht verloren, auch bei Fehlern
  im Moderationsprozess.
  ],

  [3],
  [Funktionale Eignung (_Suitable_, Q42 \#61)],
  [
  Die REST-API liefert korrekte, vollständige Daten gemäss
  OpenAPI-Spezifikation. Die Plattform erfüllt den gesamten Ideenfluss
  von Einreichung bis Projektraum.
  ],

  [4],
  [Wartbarkeit (_Maintainable_, Q42 \#31)],
  [
  Ein neues Domain-Package kann hinzugefügt werden, ohne bestehende
  Packages zu ändern. Testabdeckung und statische Analyse sichern
  Änderungen ab.
  ],

  [5],
  [Betreibbarkeit (_Operable_, Q42 \#62)],
  [
  Deployments erfolgen automatisiert via CI/CD. Monitoring erkennt
  Ausfälle. Backups ermöglichen Wiederherstellung innerhalb
  definierter Zeiten.
  ],
)
```

- [ ] **Step 2: Verify the Typst compiles**

Run: `cd docs/typst/innovationsbox-dokumentation && typst compile main.typ --root . 2>&1 | head -20`

Expected: No errors. If `typst` is not installed locally, visually verify the markup is valid Typst by checking bracket/parenthesis matching.

- [ ] **Step 3: Commit**

```bash
git add docs/typst/innovationsbox-dokumentation/src/product/arc42/01_introduction_and_goals.typ
git commit -m "docs: update Section 1.2 quality goals to Q42 categories

Replace 5 old quality goals (Usability, Sicherheit, Moderierbarkeit,
Erweiterbarkeit, Performance) with 5 Q42-aligned goals:
Usable (#103), Reliable (#107), Suitable (#61),
Maintainable (#31), Operable (#62)."
```

---

### Task 2: Restructure Section 10 Overview Table

**Files:**
- Modify: `docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ:1-97`

- [ ] **Step 1: Replace the header and overview table**

Replace lines 1-97 (from the import through the closing `)` of the overview table) with the following Typst content:

```typst
#import "../../../common/styles/help-style.typ": show-help, help

// adoc/10_quality_requirements.typ
// ---------------------------------------------
// Section 10: Qualitätsanforderungen (arc42)
// ---------------------------------------------

= Qualitätsanforderungen
#label("section-quality-scenarios")

Die folgenden Qualitätsanforderungen konkretisieren die
Qualitätsziele aus Abschnitt 1.2. Sie beschreiben messbare,
überprüfbare Anforderungen an die nicht-funktionalen Eigenschaften
der Innovationsbox, strukturiert nach dem
#link("https://quality.arc42.org")[Q42-Qualitätsmodell].

#if show-help [
  #help[
    *Inhalt:*  
    Dieser Abschnitt enthält alle relevanten Qualitätsanforderungen.

    Die wichtigsten davon wurden bereits in Abschnitt 1.2 (Qualitätsziele)
    hervorgehoben – hier kann deshalb auf diese verwiesen werden.
    In diesem Abschnitt 10 sollten Sie auch Qualitätsanforderungen mit
    geringerer Bedeutung erfassen, deren Nichterfüllung keine grossen
    Risiken birgt (aber _nice-to-have_ sein kann).

    *Motivation:*  
    Weil Qualitätsanforderungen Architekturentscheidungen oft massgeblich
    beeinflussen, sollten die für die Stakeholder relevanten
    Qualitätsanforderungen bekannt, möglichst konkret und
    operationalisiert sein.

    *Weiterführende Informationen:*  

    - #link("https://docs.arc42.org/section-10/")[Qualitätsanforderungen]
      in der Online-Dokumentation (englisch)  
    - das ausführliche  
      #link("https://quality.arc42.org")[Q42 Qualitätsmodell]
  ]
]

// ---------------------------------------------------------
// 10.1 Übersicht der Qualitätsanforderungen
// ---------------------------------------------------------

== Übersicht der Qualitätsanforderungen

#table(
  columns: (auto, auto, 1fr),
  align: left,
  table.header(
    [*Qualitätskategorie*], [*Q42 ID*], [*Beschreibung*]
  ),
  [Zuverlässigkeit (_Reliable_)],
  [\#107],
  [
  Ausführung festgelegter Funktionen ohne Unterbrechungen oder
  Ausfälle. Systemverfügbarkeit, Fehlertoleranz und Datenintegrität.
  ],

  [Benutzbarkeit (_Usable_)],
  [\#103],
  [
  Nutzern ermöglichen, sicher, effektiv und effizient zu arbeiten.
  Intuitive Bedienung, Barrierefreiheit (WCAG 2.1 AA),
  erlernbare Interaktionsmuster.
  ],

  [Wartbarkeit (_Maintainable_)],
  [\#31],
  [
  Mit vorhersehbarem Aufwand analysierbar, änderbar, testbar und
  weiterentwickelbar. Modulare Architektur, automatisierte
  Qualitätsprüfungen, dokumentierte Schnittstellen.
  ],

  [Funktionale Eignung (_Suitable_)],
  [\#61],
  [
  Erfüllung der expliziten und impliziten Bedürfnisse der
  Stakeholder. Korrektheit der API, Vollständigkeit des Ideenflusses,
  funktionale Abdeckung.
  ],

  [Betreibbarkeit (_Operable_)],
  [\#62],
  [
  Einfach bereitzustellen, zu betreiben, zu überwachen und zu steuern.
  Automatisiertes Deployment, Monitoring, Backup und Recovery.
  ],
)
```

- [ ] **Step 2: Verify bracket matching**

Count opening and closing brackets/parentheses in the replaced content. The table must end with `)` followed by no trailing content before the next section.

- [ ] **Step 3: Commit**

```bash
git add docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ
git commit -m "docs: restructure Section 10.1 overview table to Q42 categories

Replace 7 categories (Benutzbarkeit, Sicherheit, Performance,
Wartbarkeit, Erweiterbarkeit, Verfügbarkeit, Code-Qualität) with
5 Q42-aligned categories: Reliable (#107), Usable (#103),
Maintainable (#31), Suitable (#61), Operable (#62)."
```

---

### Task 3: Replace Quality Scenarios with REL and USA Categories

**Files:**
- Modify: `docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ:99-181`

- [ ] **Step 1: Replace the old help block and all QS scenarios**

Replace lines 99-235 (from the first `#if show-help` after the overview table through the end of file) with the following Typst content:

```typst
#if show-help [
  #help[
    *Inhalt:*  
    Eine Übersicht oder Zusammenfassung der Qualitätsanforderungen.

    *Motivation:*  
    Oft gibt es Dutzende oder Hunderte detaillierter Qualitätsanforderungen.
    In diesem Abschnitt sollten Sie diese strukturieren und zusammenfassen,
    z.B. nach Kategorien oder Themen (wie von
    #link("https://www.iso.org/obp/ui/#iso:std:iso-iec:25010:ed-2:v1:en")[ISO 25010]
    oder #link("https://quality.arc42.org")[Q42] vorgeschlagen).

    Wenn diese Kurzbeschreibungen bereits präzise, spezifisch und messbar
    sind, kann Abschnitt 10.2 entfallen.

    *Form:*  

    - einfache Tabelle, in der jede Zeile eine Kategorie/ein Thema und eine
      Kurzbeschreibung der Qualitätsanforderung enthält  
    - alternativ eine Mindmap zur Strukturierung  
    - in der Literatur (z.B. [Bass+21]) wird der
      _Quality Attribute Utility Tree_ beschrieben (_Qualitätsbaum_),
      der „Qualität" als Wurzel hat und diese in einem Baum verfeinert.
  ]
]

// ---------------------------------------------------------
// 10.2 Qualitätsszenarien
// ---------------------------------------------------------

== Qualitätsszenarien

Alle Szenarien folgen dem SEI-Langformat (Bass+21) und sind SMART
formuliert (spezifisch, messbar, erreichbar, relevant, überprüfbar).

// ---------------------------------------------------------
// Zuverlässigkeit (Reliable, Q42 #107)
// ---------------------------------------------------------

=== Zuverlässigkeit (_Reliable_, Q42 \#107)

==== REL-01: Systemverfügbarkeit
*Quelle:* Benutzer \
*Stimulus:* Zugriff auf die Plattform \
*Umgebung:* Normalbetrieb \
*Reaktion:* Plattform ist erreichbar und funktionsfähig \
*Antwortmass:* Systemverfügbarkeit von mindestens 99% pro Kalendermonat
(max. 7.3h Downtime/Monat)

==== REL-02: Zugriffsschutz für Projekträume
*Quelle:* Nicht berechtigter Benutzer \
*Stimulus:* Versuch, einen geschützten Projektraum oder API-Endpunkt
aufzurufen \
*Umgebung:* Normalbetrieb \
*Reaktion:* Zugriff wird verweigert, HTTP 401/403 zurückgegeben \
*Antwortmass:* 100% der geschützten Endpunkte liefern HTTP 401 oder 403
bei fehlendem/ungültigem Token. Verifizierbar durch E2E-Tests.

==== REL-03: Datenintegrität bei Ideeneinreichung
*Quelle:* Benutzer \
*Stimulus:* Benutzer reicht eine Projektidee ein, während ein Serverfehler
auftritt \
*Umgebung:* Fehlersituation (z.B. DB-Timeout) \
*Reaktion:* Transaktion wird vollständig durchgeführt oder vollständig
zurückgerollt \
*Antwortmass:* Keine partiell gespeicherten Datensätze. Überprüfbar
durch Integrationstests mit simuliertem DB-Fehler.

==== REL-04: Fehlertoleranz bei externen Abhängigkeiten
*Quelle:* System \
*Stimulus:* E-Mail-Server ist nicht erreichbar \
*Umgebung:* Teilausfall externer Dienste \
*Reaktion:* Kernfunktionen (Ideeneinreichung, Moderation) bleiben
verfügbar. Benachrichtigungen werden nachgeliefert oder geloggt. \
*Antwortmass:* Benutzer kann Idee einreichen und erhält HTTP 201.
E-Mail-Fehler wird im Systemlog erfasst. Verifizierbar durch
Integrationstest mit deaktiviertem SMTP.

// ---------------------------------------------------------
// Benutzbarkeit (Usable, Q42 #103)
// ---------------------------------------------------------

=== Benutzbarkeit (_Usable_, Q42 \#103)

==== USA-01: Schnelle Anzeige von Projektideen
*Quelle:* Benutzer \
*Stimulus:* Benutzer ruft die Projektübersicht auf \
*Umgebung:* Normalbetrieb, 20–30 gleichzeitige Benutzer \
*Reaktion:* Projektübersicht wird vollständig gerendert \
*Antwortmass:* Time-to-Interactive unter 2 Sekunden bei einer
Internetverbindung mit mindestens 10 Mbit/s. Messbar via Lighthouse
oder Playwright Performance-Messung.

==== USA-02: Barrierefreiheit gemäss WCAG 2.1 AA
*Quelle:* Benutzer mit Einschränkungen (Sehbehinderung, motorische
Einschränkung) \
*Stimulus:* Navigation und Interaktion mit der Plattform via Screenreader
oder Tastatur \
*Umgebung:* Normalbetrieb \
*Reaktion:* Alle interaktiven Elemente sind per Tastatur erreichbar,
korrekt gelabelt und mit ausreichendem Farbkontrast versehen \
*Antwortmass:* Keine WCAG 2.1 AA Violations in automatisierten
Accessibility-Audits (axe-core) auf allen öffentlichen Seiten.
Verifizierbar durch Playwright + axe-core in CI.

==== USA-03: Intuitive Ideeneinreichung
*Quelle:* Erstbenutzer ohne Schulung \
*Stimulus:* Benutzer möchte eine Projektidee einreichen \
*Umgebung:* Normalbetrieb \
*Reaktion:* Benutzer findet das Einreichungsformular und schliesst
die Einreichung ab \
*Antwortmass:* Einreichungsformular ist innerhalb von maximal 3 Klicks
ab Startseite erreichbar. Alle Pflichtfelder sind mit Labels und
Validierungsmeldungen versehen. Verifizierbar durch E2E-Test.

==== USA-04: Responsive Darstellung
*Quelle:* Benutzer mit mobilem Endgerät \
*Stimulus:* Zugriff auf die Plattform via Smartphone (Viewport 375px) \
*Umgebung:* Normalbetrieb \
*Reaktion:* Alle Inhalte und Interaktionen sind ohne horizontales
Scrollen nutzbar \
*Antwortmass:* Kein horizontaler Overflow auf Viewports ab 375px Breite.
Alle Touch-Targets mindestens 44×44px. Verifizierbar durch
Playwright-Tests mit mobiler Viewport-Konfiguration.

// ---------------------------------------------------------
// Wartbarkeit (Maintainable, Q42 #31)
// ---------------------------------------------------------

=== Wartbarkeit (_Maintainable_, Q42 \#31)

==== MNT-01: Testabdeckung
*Quelle:* Entwicklerteam \
*Stimulus:* Neuer Code wird als Pull Request eingereicht \
*Umgebung:* CI/CD Pipeline \
*Reaktion:* Pipeline prüft die Code-Abdeckung der Unit Tests \
*Antwortmass:* Mindestens 80% Zeilenabdeckung über alle Domain-Packages
(`inno_*/src/`). Pipeline schlägt fehl bei Unterschreitung.

==== MNT-02: Statische Code-Analyse
*Quelle:* Entwicklerteam \
*Stimulus:* Pull Request wird erstellt \
*Umgebung:* CI/CD Pipeline \
*Reaktion:* Pipeline führt statische Analyse und Formatierungsprüfung
durch \
*Antwortmass:* ESLint, Stylelint, PHPStan (Level 6), PHP CS Fixer und
TypeScript Type Check bestehen fehlerfrei. Pipeline schlägt fehl
bei Violations.

==== MNT-03: Sicherheit von Abhängigkeiten
*Quelle:* Automatisierte Pipeline \
*Stimulus:* Pull Request wird erstellt oder wöchentlicher
Schedule-Trigger \
*Umgebung:* CI/CD Pipeline \
*Reaktion:* Pipeline prüft npm- und Composer-Abhängigkeiten auf bekannte
Schwachstellen \
*Antwortmass:* Keine Schwachstellen mit Schweregrad «high» oder
«critical» (npm) bzw. kein Advisory (Composer). Zusätzlich
Semgrep SAST-Scan ohne Findings.

==== MNT-04: Modulare Erweiterbarkeit
*Quelle:* Entwicklerteam \
*Stimulus:* Neue Funktion soll als zusätzliches Domain-Package integriert
werden \
*Umgebung:* Weiterentwicklung \
*Reaktion:* Neues Package kann hinzugefügt werden, ohne bestehende
Packages zu modifizieren \
*Antwortmass:* Kein bestehendes Package (`inno_project_idea`,
`inno_moderation`, etc.) erfordert Code-Änderungen. Kommunikation
erfolgt ausschliesslich über Events (`inno_shared`) oder REST-API.
Verifizierbar durch Abhängigkeitsanalyse (keine Cross-Package-Imports).

==== MNT-05: Analysierbarkeit durch dokumentierte API
*Quelle:* Entwicklerteam \
*Stimulus:* Entwickler möchte einen bestehenden API-Endpunkt verstehen
oder anpassen \
*Umgebung:* Weiterentwicklung \
*Reaktion:* OpenAPI-Spezifikation ist aktuell und beschreibt alle
Endpunkte vollständig \
*Antwortmass:* `composer openapi:generate` produziert eine Spezifikation,
die `npm run lint:api` (Spectral) fehlerfrei besteht. Jeder Endpunkt
hat Request/Response-Schemas, Statuscodes und Beschreibung. Verifizierbar
in CI.

// ---------------------------------------------------------
// Funktionale Eignung (Suitable, Q42 #61)
// ---------------------------------------------------------

=== Funktionale Eignung (_Suitable_, Q42 \#61)

==== SUI-01: API-Konformität zur OpenAPI-Spezifikation
*Quelle:* Frontend oder externer Client \
*Stimulus:* API-Request an einen dokumentierten Endpunkt \
*Umgebung:* Normalbetrieb \
*Reaktion:* Response entspricht exakt dem in der OpenAPI-Spezifikation
definierten Schema \
*Antwortmass:* 100% der API-Responses validieren gegen die
OpenAPI-Spezifikation. Verifizierbar durch E2E-Tests, die Responses
gegen das generierte Schema prüfen.

==== SUI-02: Vollständiger Ideenfluss
*Quelle:* Benutzer und Moderator \
*Stimulus:* Projektidee wird eingereicht \
*Umgebung:* Normalbetrieb \
*Reaktion:* Idee durchläuft den gesamten Lebenszyklus: Einreichung
→ Moderationsprüfung → Freigabe/Ablehnung → bei Freigabe:
Veröffentlichung und Projektraum-Erstellung \
*Antwortmass:* Jeder Statusübergang erzeugt das korrekte Domain-Event
(`ProjectIdeaSubmitted` → `ModerationDecisionMade`
→ `ProjectIdeaApproved` → `ProjectSpaceCreated`). Verifizierbar
durch E2E-Test über den gesamten Flow.

==== SUI-03: Korrekte Benachrichtigungen
*Quelle:* System (Event-Listener) \
*Stimulus:* Domain-Event wird ausgelöst (Idee eingereicht, genehmigt,
abgelehnt) \
*Umgebung:* Normalbetrieb \
*Reaktion:* Betroffene Benutzer erhalten die entsprechende
E-Mail-Benachrichtigung \
*Antwortmass:* Für jedes der drei Events (`ProjectIdeaSubmitted`,
`ProjectIdeaApproved`, `ProjectIdeaRejected`) wird genau eine E-Mail
an die definierten Empfänger gesendet. Verifizierbar durch
Integrationstests mit Mail-Trap.

==== SUI-04: Gleichzeitige Nutzung
*Quelle:* Mehrere Benutzer \
*Stimulus:* 30 gleichzeitige Benutzer greifen auf die Plattform zu \
*Umgebung:* Normalbetrieb, Spitzenlast \
*Reaktion:* Alle Benutzer können Projektideen einsehen, einreichen und
in Projekträumen arbeiten \
*Antwortmass:* Antwortzeiten bleiben unter 3 Sekunden bei 30
gleichzeitigen Sessions. Keine HTTP 5xx Fehler. Verifizierbar durch
Lasttest (z.B. k6 oder Artillery).

// ---------------------------------------------------------
// Betreibbarkeit (Operable, Q42 #62)
// ---------------------------------------------------------

=== Betreibbarkeit (_Operable_, Q42 \#62)

==== OPR-01: Automatisiertes Deployment
*Quelle:* Entwicklerteam \
*Stimulus:* Tag wird im Git-Repository gepusht \
*Umgebung:* CI/CD Pipeline \
*Reaktion:* Pipeline baut Production-Assets, führt alle Checks durch
und deployt automatisch auf die Zielumgebung (K8s) \
*Antwortmass:* Vom Tag-Push bis zum laufenden Deployment vergehen
maximal 15 Minuten. Kein manueller Eingriff erforderlich. Verifizierbar
durch Pipeline-Laufzeit-Messung.

==== OPR-02: Monitoring und Fehlererkennung
*Quelle:* Monitoring-System \
*Stimulus:* Plattform wird unerreichbar oder liefert HTTP 5xx Fehler \
*Umgebung:* Normalbetrieb \
*Reaktion:* Monitoring erkennt den Ausfall und benachrichtigt das
Betriebsteam \
*Antwortmass:* Ausfall wird innerhalb von maximal 5 Minuten erkannt.
Benachrichtigung (E-Mail oder Chat) wird automatisch ausgelöst.
Verifizierbar durch simulierten Health-Check-Ausfall.

==== OPR-03: Backup und Wiederherstellung
*Quelle:* Betriebsteam \
*Stimulus:* Datenverlust durch Systemfehler oder fehlerhaftes
Deployment \
*Umgebung:* Fehlersituation \
*Reaktion:* Datenbank und Dateien können aus automatisiertem Backup
wiederhergestellt werden \
*Antwortmass:* RPO (Recovery Point Objective): maximal 24 Stunden
Datenverlust. RTO (Recovery Time Objective): Wiederherstellung innerhalb
von 2 Stunden. Verifizierbar durch dokumentierten Recovery-Test
pro Quartal.

==== OPR-04: Staging-Umgebung
*Quelle:* Entwicklerteam \
*Stimulus:* Push auf den `staging`-Branch \
*Umgebung:* CI/CD Pipeline \
*Reaktion:* Production-Build wird automatisch auf die Staging-Umgebung
deployt \
*Antwortmass:* Staging-Umgebung bildet die Produktionsumgebung identisch
ab (gleiche K8s-Konfiguration, gleiche Package-Versionen). Verifizierbar
durch Vergleich der Deployment-Manifeste.

#if show-help [
  #help[
    *Inhalt:*  
    Qualitätsszenarien konkretisieren Qualitätsanforderungen und machen
    prüfbar, ob sie erfüllt sind (Akzeptanzkriterien).  
    Szenarien sollten spezifisch und messbar sein.

    Zwei Arten von Szenarien sind besonders nützlich:

    - *Nutzungsszenarien* (Anwendungs- oder Anwendungsfallszenarien)  
      beschreiben, wie das System zur Laufzeit auf einen Auslöser reagiert.
      Hierunter fallen auch Effizienz-/Performance-Szenarien.  
      Beispiel: „Das System beantwortet eine Benutzeranfrage innerhalb
      einer Sekunde."
    - *_Änderungsszenarien_* beschreiben die gewünschte Wirkung einer
      Änderung oder Erweiterung des Systems oder seiner Umgebung.  
      Beispiel: Zusätzliche Funktionalität wird implementiert, oder eine
      Qualitätsanforderung ändert sich, und Aufwand/Dauer der Änderung
      wird gemessen.

    *Form:*  

    Typische Informationen für detaillierte Szenarien sind:

    *Kurzform* (z.B. im Q42-Modell):

    - *Kontext/Hintergrund*: Art von System/Komponente, Umgebung, Situation  
    - *Quelle/Stimulus*: Wer oder was löst Verhalten/Reaktion aus  
    - *Metrik/Akzeptanzkriterien*: Reaktion inkl. Messgrösse/Metrik

    *Langform* (SEI / [Bass+21]):

    - *Szenario-ID*: eindeutiger Bezeichner  
    - *Szenario-Name*: kurzer, beschreibender Name  
    - *Quelle*: Entität (Benutzer, System, Ereignis), die das Szenario auslöst  
    - *Stimulus*: auslösendes Ereignis oder Bedingung  
    - *Umgebung*: Rahmenbedingungen/Betriebssituation  
    - *Artefakt*: Bausteine oder andere Elemente, die betroffen sind  
    - *Reaktion*: gefordertes Verhalten / Ergebnis  
    - *Antwortmass*: Kriterium/Metrik zur Bewertung der Reaktion

    *Beispiele:*  
    Ausführliche Beispiele für Qualitätsanforderungen finden sich auf  
    #link("https://quality.arc42.org")[quality.arc42.org].

    *Weitere Informationen:*  

    - Len Bass, Paul Clements, Rick Kazman:  
      *Software Architecture in Practice*, 4. Auflage, Addison-Wesley, 2021.
  ]
]
```

- [ ] **Step 2: Verify the complete file**

The file should now contain:
1. Import + header (unchanged)
2. Intro paragraph (updated with Q42 reference)
3. Help block (unchanged)
4. Section 10.1 overview table (5 Q42 categories, 3 columns)
5. Help block for overview (unchanged)
6. Section 10.2 with 5 category subsections, 21 scenarios total
7. Help block for scenarios (unchanged, moved to end)

No `#strong[Liste oder Tabelle von Qualitätsszenarien]` leftover at the end.

- [ ] **Step 3: Commit**

```bash
git add docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ
git commit -m "docs: add 21 SMART quality scenarios grouped by Q42 category

Replace QS-01..07 with 21 scenarios across 5 Q42 categories:
- Reliable (REL-01..04): availability, access control, data integrity, fault tolerance
- Usable (USA-01..04): performance, accessibility, usability, responsive design
- Maintainable (MNT-01..05): test coverage, static analysis, dependency security, modularity, API docs
- Suitable (SUI-01..04): API conformity, idea flow, notifications, concurrency
- Operable (OPR-01..04): deployment, monitoring, backup/recovery, staging"
```

---

### Task 4: Final Verification

**Files:**
- Verify: `docs/typst/innovationsbox-dokumentation/src/product/arc42/01_introduction_and_goals.typ`
- Verify: `docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ`

- [ ] **Step 1: Cross-reference Section 1.2 and Section 10**

Verify that all 5 quality goals in Section 1.2 have a matching category subsection in Section 10.2:

| Section 1.2 Goal | Section 10.2 Category | Scenarios |
|---|---|---|
| Benutzbarkeit (Q42 #103) | Benutzbarkeit (Usable, Q42 #103) | USA-01..04 |
| Zuverlässigkeit (Q42 #107) | Zuverlässigkeit (Reliable, Q42 #107) | REL-01..04 |
| Funktionale Eignung (Q42 #61) | Funktionale Eignung (Suitable, Q42 #61) | SUI-01..04 |
| Wartbarkeit (Q42 #31) | Wartbarkeit (Maintainable, Q42 #31) | MNT-01..05 |
| Betreibbarkeit (Q42 #62) | Betreibbarkeit (Operable, Q42 #62) | OPR-01..04 |

- [ ] **Step 2: Verify scenario count**

Count all scenario headings (`====`) in Section 10. Expected: exactly 21.

Run: `grep -c '====' docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ`
Expected: `21`

- [ ] **Step 3: Check for leftover old content**

Run: `grep -n 'QS-0' docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ`
Expected: No matches (all old QS-01..07 IDs removed).

Run: `grep -n 'Liste oder Tabelle' docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ`
Expected: No matches (placeholder removed).
