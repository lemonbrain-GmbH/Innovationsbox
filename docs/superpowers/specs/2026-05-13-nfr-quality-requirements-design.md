# NFR Quality Requirements Restructure

**Date:** 2026-05-13
**Status:** Approved
**Q42 Model Reference:** quality.arc42.org

## Summary

Restructure arc42 Section 1.2 (Qualitätsziele) and Section 10 (Qualitätsanforderungen) around five Q42/ISO 25010 quality categories. All scenarios are SMART (Specific, Measurable, Achievable, Relevant, Time-bound/Testable). Existing scenarios QS-01 through QS-07 are merged into the new structure and renumbered.

## Scope

- **Section 1.2** (`01_introduction_and_goals.typ`): Replace 5 current quality goals with 5 Q42-aligned goals
- **Section 10** (`10_quality_requirements.typ`): Replace overview table and scenarios with Q42-structured categories and 21 SMART scenarios
- **Approach:** Q42-Pure Restructure (Approach A) -- clean renumbering, no backwards-compatibility shims

## Q42 Categories

| Category | Q42 ID | German | Description |
|---|---|---|---|
| Reliable | #107 | Zuverlässigkeit | Ausführung festgelegter Funktionen ohne Unterbrechungen oder Ausfälle |
| Usable | #103 | Benutzbarkeit | Nutzern ermöglichen, sicher, effektiv und effizient zu arbeiten |
| Maintainable | #31 | Wartbarkeit | Mit vorhersehbarem Aufwand analysierbar, änderbar, testbar und weiterentwickelbar |
| Suitable | #61 | Funktionale Eignung | Erfüllung der expliziten und impliziten Bedürfnisse der Stakeholder |
| Operable | #62 | Betreibbarkeit | Einfach bereitzustellen, zu betreiben, zu überwachen und zu steuern |

## Section 1.2 -- Qualitätsziele

5 goals, reprioritized by stakeholder impact:

| Priorität | Qualitätsziel | Q42 ID | Szenario |
|---|---|---|---|
| 1 | Benutzbarkeit (Usable) | #103 | Eine Besucherin öffnet die Projektübersicht und findet innerhalb weniger Sekunden relevante Projektideen. Navigation und Interaktion sind ohne Schulung verständlich. |
| 2 | Zuverlässigkeit (Reliable) | #107 | Die Plattform ist während der Geschäftszeiten stabil erreichbar. Eingereichte Projektideen gehen nicht verloren, auch bei Fehlern im Moderationsprozess. |
| 3 | Funktionale Eignung (Suitable) | #61 | Die REST-API liefert korrekte, vollständige Daten gemäss OpenAPI-Spezifikation. Die Plattform erfüllt den gesamten Ideenfluss von Einreichung bis Projektraum. |
| 4 | Wartbarkeit (Maintainable) | #31 | Ein neues Domain-Package kann hinzugefügt werden, ohne bestehende Packages zu ändern. Testabdeckung und statische Analyse sichern Änderungen ab. |
| 5 | Betreibbarkeit (Operable) | #62 | Deployments erfolgen automatisiert via CI/CD. Monitoring erkennt Ausfälle. Backups ermöglichen Wiederherstellung innerhalb definierter Zeiten. |

## Section 10.1 -- Übersicht der Qualitätsanforderungen

Overview table with 5 Q42 categories replacing the previous 7 categories.

Mapping of old categories:
- Sicherheit → Reliable (Zugriffskontrolle)
- Performance → Reliable/Usable
- Erweiterbarkeit → Maintainable
- Code-Qualität → Maintainable
- Verfügbarkeit → Reliable

## Section 10.2 -- Qualitätsszenarien (21 scenarios)

All scenarios follow SEI long-form: Quelle, Stimulus, Umgebung, Reaktion, Antwortmass.

### Zuverlässigkeit (Reliable) -- REL-01 to REL-04

**REL-01: Systemverfügbarkeit** *(merged from QS-04)*
- Quelle: Benutzer
- Stimulus: Zugriff auf die Plattform
- Umgebung: Normalbetrieb
- Reaktion: Plattform ist erreichbar und funktionsfähig
- Antwortmass: Systemverfügbarkeit von mindestens 99% pro Kalendermonat (max. 7.3h Downtime/Monat)

**REL-02: Zugriffsschutz für Projekträume** *(merged from QS-02)*
- Quelle: Nicht berechtigter Benutzer
- Stimulus: Versuch, einen geschützten Projektraum oder API-Endpunkt aufzurufen
- Umgebung: Normalbetrieb
- Reaktion: Zugriff wird verweigert, HTTP 401/403 zurückgegeben
- Antwortmass: 100% der geschützten Endpunkte liefern HTTP 401 oder 403 bei fehlendem/ungültigem Token. Verifizierbar durch E2E-Tests.

**REL-03: Datenintegrität bei Ideeneinreichung**
- Quelle: Benutzer
- Stimulus: Benutzer reicht eine Projektidee ein, während ein Serverfehler auftritt
- Umgebung: Fehlersituation (z.B. DB-Timeout)
- Reaktion: Transaktion wird vollständig durchgeführt oder vollständig zurückgerollt
- Antwortmass: Keine partiell gespeicherten Datensätze. Überprüfbar durch Integrationstests mit simuliertem DB-Fehler.

**REL-04: Fehlertoleranz bei externen Abhängigkeiten**
- Quelle: System
- Stimulus: E-Mail-Server ist nicht erreichbar
- Umgebung: Teilausfall externer Dienste
- Reaktion: Kernfunktionen (Ideeneinreichung, Moderation) bleiben verfügbar. Benachrichtigungen werden nachgeliefert oder geloggt.
- Antwortmass: Benutzer kann Idee einreichen und erhält HTTP 201. E-Mail-Fehler wird im Systemlog erfasst. Verifizierbar durch Integrationstest mit deaktiviertem SMTP.

### Benutzbarkeit (Usable) -- USA-01 to USA-04

**USA-01: Schnelle Anzeige von Projektideen** *(merged from QS-01)*
- Quelle: Benutzer
- Stimulus: Benutzer ruft die Projektübersicht auf
- Umgebung: Normalbetrieb, 20-30 gleichzeitige Benutzer
- Reaktion: Projektübersicht wird vollständig gerendert
- Antwortmass: Time-to-Interactive unter 2 Sekunden bei einer Internetverbindung mit mindestens 10 Mbit/s. Messbar via Lighthouse oder Playwright Performance-Messung.

**USA-02: Barrierefreiheit gemäss WCAG 2.1 AA**
- Quelle: Benutzer mit Einschränkungen (Sehbehinderung, motorische Einschränkung)
- Stimulus: Navigation und Interaktion mit der Plattform via Screenreader oder Tastatur
- Umgebung: Normalbetrieb
- Reaktion: Alle interaktiven Elemente sind per Tastatur erreichbar, korrekt gelabelt und mit ausreichendem Farbkontrast versehen
- Antwortmass: Keine WCAG 2.1 AA Violations in automatisierten Accessibility-Audits (axe-core) auf allen öffentlichen Seiten. Verifizierbar durch Playwright + axe-core in CI.

**USA-03: Intuitive Ideeneinreichung**
- Quelle: Erstbenutzer ohne Schulung
- Stimulus: Benutzer möchte eine Projektidee einreichen
- Umgebung: Normalbetrieb
- Reaktion: Benutzer findet das Einreichungsformular und schliesst die Einreichung ab
- Antwortmass: Einreichungsformular ist innerhalb von maximal 3 Klicks ab Startseite erreichbar. Alle Pflichtfelder sind mit Labels und Validierungsmeldungen versehen. Verifizierbar durch E2E-Test.

**USA-04: Responsive Darstellung**
- Quelle: Benutzer mit mobilem Endgerät
- Stimulus: Zugriff auf die Plattform via Smartphone (Viewport 375px)
- Umgebung: Normalbetrieb
- Reaktion: Alle Inhalte und Interaktionen sind ohne horizontales Scrollen nutzbar
- Antwortmass: Kein horizontaler Overflow auf Viewports ab 375px Breite. Alle Touch-Targets mindestens 44x44px. Verifizierbar durch Playwright-Tests mit mobiler Viewport-Konfiguration.

### Wartbarkeit (Maintainable) -- MNT-01 to MNT-05

**MNT-01: Testabdeckung** *(merged from QS-05)*
- Quelle: Entwicklerteam
- Stimulus: Neuer Code wird als Pull Request eingereicht
- Umgebung: CI/CD Pipeline
- Reaktion: Pipeline prüft die Code-Abdeckung der Unit Tests
- Antwortmass: Mindestens 80% Zeilenabdeckung über alle Domain-Packages (`inno_*/src/`). Pipeline schlägt fehl bei Unterschreitung.

**MNT-02: Statische Code-Analyse** *(merged from QS-06)*
- Quelle: Entwicklerteam
- Stimulus: Pull Request wird erstellt
- Umgebung: CI/CD Pipeline
- Reaktion: Pipeline führt statische Analyse und Formatierungsprüfung durch
- Antwortmass: ESLint, Stylelint, PHPStan (Level 6), PHP CS Fixer und TypeScript Type Check bestehen fehlerfrei. Pipeline schlägt fehl bei Violations.

**MNT-03: Sicherheit von Abhängigkeiten** *(merged from QS-07)*
- Quelle: Automatisierte Pipeline
- Stimulus: Pull Request wird erstellt oder wöchentlicher Schedule-Trigger
- Umgebung: CI/CD Pipeline
- Reaktion: Pipeline prüft npm- und Composer-Abhängigkeiten auf bekannte Schwachstellen
- Antwortmass: Keine Schwachstellen mit Schweregrad «high» oder «critical» (npm) bzw. kein Advisory (Composer). Zusätzlich Semgrep SAST-Scan ohne Findings.

**MNT-04: Modulare Erweiterbarkeit** *(merged from QS-03)*
- Quelle: Entwicklerteam
- Stimulus: Neue Funktion soll als zusätzliches Domain-Package integriert werden
- Umgebung: Weiterentwicklung
- Reaktion: Neues Package kann hinzugefügt werden, ohne bestehende Packages zu modifizieren
- Antwortmass: Kein bestehendes Package (`inno_project_idea`, `inno_moderation`, etc.) erfordert Code-Änderungen. Kommunikation erfolgt ausschliesslich über Events (`inno_shared`) oder REST-API. Verifizierbar durch Abhängigkeitsanalyse (keine Cross-Package-Imports).

**MNT-05: Analysierbarkeit durch dokumentierte API**
- Quelle: Entwicklerteam
- Stimulus: Entwickler möchte einen bestehenden API-Endpunkt verstehen oder anpassen
- Umgebung: Weiterentwicklung
- Reaktion: OpenAPI-Spezifikation ist aktuell und beschreibt alle Endpunkte vollständig
- Antwortmass: `composer openapi:generate` produziert eine Spezifikation, die `npm run lint:api` (Spectral) fehlerfrei besteht. Jeder Endpunkt hat Request/Response-Schemas, Statuscodes und Beschreibung. Verifizierbar in CI.

### Funktionale Eignung (Suitable) -- SUI-01 to SUI-04

**SUI-01: API-Konformität zur OpenAPI-Spezifikation**
- Quelle: Frontend oder externer Client
- Stimulus: API-Request an einen dokumentierten Endpunkt
- Umgebung: Normalbetrieb
- Reaktion: Response entspricht exakt dem in der OpenAPI-Spezifikation definierten Schema
- Antwortmass: 100% der API-Responses validieren gegen die OpenAPI-Spezifikation. Verifizierbar durch E2E-Tests, die Responses gegen das generierte Schema prüfen.

**SUI-02: Vollständiger Ideenfluss**
- Quelle: Benutzer und Moderator
- Stimulus: Projektidee wird eingereicht
- Umgebung: Normalbetrieb
- Reaktion: Idee durchläuft den gesamten Lebenszyklus: Einreichung -> Moderationsprüfung -> Freigabe/Ablehnung -> bei Freigabe: Veröffentlichung und Projektraum-Erstellung
- Antwortmass: Jeder Statusübergang erzeugt das korrekte Domain-Event (`ProjectIdeaSubmitted` -> `ModerationDecisionMade` -> `ProjectIdeaApproved` -> `ProjectSpaceCreated`). Verifizierbar durch E2E-Test über den gesamten Flow.

**SUI-03: Korrekte Benachrichtigungen**
- Quelle: System (Event-Listener)
- Stimulus: Domain-Event wird ausgelöst (Idee eingereicht, genehmigt, abgelehnt)
- Umgebung: Normalbetrieb
- Reaktion: Betroffene Benutzer erhalten die entsprechende E-Mail-Benachrichtigung
- Antwortmass: Für jedes der drei Events (`ProjectIdeaSubmitted`, `ProjectIdeaApproved`, `ProjectIdeaRejected`) wird genau eine E-Mail an die definierten Empfänger gesendet. Verifizierbar durch Integrationstests mit Mail-Trap.

**SUI-04: Gleichzeitige Nutzung**
- Quelle: Mehrere Benutzer
- Stimulus: 30 gleichzeitige Benutzer greifen auf die Plattform zu
- Umgebung: Normalbetrieb, Spitzenlast
- Reaktion: Alle Benutzer können Projektideen einsehen, einreichen und in Projekträumen arbeiten
- Antwortmass: Antwortzeiten bleiben unter 3 Sekunden bei 30 gleichzeitigen Sessions. Keine HTTP 5xx Fehler. Verifizierbar durch Lasttest (z.B. k6 oder Artillery).

### Betreibbarkeit (Operable) -- OPR-01 to OPR-04

**OPR-01: Automatisiertes Deployment**
- Quelle: Entwicklerteam
- Stimulus: Tag wird im Git-Repository gepusht
- Umgebung: CI/CD Pipeline
- Reaktion: Pipeline baut Production-Assets, führt alle Checks durch und deployt automatisch auf die Zielumgebung (K8s)
- Antwortmass: Vom Tag-Push bis zum laufenden Deployment vergehen maximal 15 Minuten. Kein manueller Eingriff erforderlich. Verifizierbar durch Pipeline-Laufzeit-Messung.

**OPR-02: Monitoring und Fehlererkennung**
- Quelle: Monitoring-System
- Stimulus: Plattform wird unerreichbar oder liefert HTTP 5xx Fehler
- Umgebung: Normalbetrieb
- Reaktion: Monitoring erkennt den Ausfall und benachrichtigt das Betriebsteam
- Antwortmass: Ausfall wird innerhalb von maximal 5 Minuten erkannt. Benachrichtigung (E-Mail oder Chat) wird automatisch ausgelöst. Verifizierbar durch simulierten Health-Check-Ausfall.

**OPR-03: Backup und Wiederherstellung**
- Quelle: Betriebsteam
- Stimulus: Datenverlust durch Systemfehler oder fehlerhaftes Deployment
- Umgebung: Fehlersituation
- Reaktion: Datenbank und Dateien können aus automatisiertem Backup wiederhergestellt werden
- Antwortmass: RPO (Recovery Point Objective): maximal 24 Stunden Datenverlust. RTO (Recovery Time Objective): Wiederherstellung innerhalb von 2 Stunden. Verifizierbar durch dokumentierten Recovery-Test pro Quartal.

**OPR-04: Staging-Umgebung**
- Quelle: Entwicklerteam
- Stimulus: Push auf den `staging`-Branch
- Umgebung: CI/CD Pipeline
- Reaktion: Production-Build wird automatisch auf die Staging-Umgebung deployt
- Antwortmass: Staging-Umgebung bildet die Produktionsumgebung identisch ab (gleiche K8s-Konfiguration, gleiche Package-Versionen). Verifizierbar durch Vergleich der Deployment-Manifeste.

## Traceability

| Old ID | New ID | Status |
|---|---|---|
| QS-01 | USA-01 | Merged (added concurrent user context) |
| QS-02 | REL-02 | Merged (added API endpoint coverage) |
| QS-03 | MNT-04 | Merged (added event/API communication constraint) |
| QS-04 | REL-01 | Merged (added per-month measurement) |
| QS-05 | MNT-01 | Merged (added pipeline-fail enforcement) |
| QS-06 | MNT-02 | Merged (added pipeline-fail enforcement) |
| QS-07 | MNT-03 | Merged (added Semgrep + weekly schedule) |
| -- | REL-03 | New |
| -- | REL-04 | New |
| -- | USA-02 | New |
| -- | USA-03 | New |
| -- | USA-04 | New |
| -- | MNT-05 | New |
| -- | SUI-01 | New |
| -- | SUI-02 | New |
| -- | SUI-03 | New |
| -- | SUI-04 | New |
| -- | OPR-01 | New |
| -- | OPR-02 | New |
| -- | OPR-03 | New |
| -- | OPR-04 | New |

## Files to Modify

1. `docs/typst/innovationsbox-dokumentation/src/product/arc42/01_introduction_and_goals.typ` -- Section 1.2 quality goals table
2. `docs/typst/innovationsbox-dokumentation/src/product/arc42/10_quality_requirements.typ` -- Full restructure of overview table and scenarios
