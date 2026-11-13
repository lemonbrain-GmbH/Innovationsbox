# Design Spec: Auth Tests & Documentation

**Datum:** 2026-04-29
**Status:** Approved
**Scope:** Punkte 2 (Tests) und 3 (Dokumentation) aus der Feature-Liste

## Kontext

Die Authentifizierungs-Infrastruktur wurde um Expertenpool, Anzeigename und Avatar-Fix erweitert. Es fehlen Tests für die neuen Features und die bestehende Profile-Completion-Logik, sowie eine aktualisierte Dokumentation.

## Entscheidungen

| Entscheidung | Gewählt | Begründung |
|---|---|---|
| Controller-Tests | E2E statt Unit | Frontend-Controller sind zu eng an CMS gekoppelt für sinnvolle Unit-Tests |
| Profile-Completion | Logik extrahieren + Unit-Test | Die Closure in controller.php ist nicht testbar |
| Dokumentation | `docs/auth-pages.md` erweitern | Ein Dokument statt zwei, bereits vorhanden |

## Teil 1: Profile-Completion-Logik extrahieren

Die anonyme Closure in `controller.php:73-106` wird in eine eigene Klasse `ProfileCompletionChecker` extrahiert. Diese Klasse hat eine Methode `needsCompletion(UserInfo $user, string $pagePath): bool` mit 5 testbaren Guard-Bedingungen:

1. User nicht registriert → false
2. Seite ist Error oder Admin → false
3. Pfad beginnt mit erlaubtem Prefix → false
4. UserInfo nicht gefunden → false
5. Required Attribute leer → true

Unit-Tests decken alle 5 Guards + den Positivfall ab.

## Teil 2: E2E-Tests (Playwright)

Zwei Spec-Dateien mit Browser-Level-Tests (nicht API-Level wie die bestehenden):

**`auth-flow.spec.ts`** — Login → Profil bearbeiten → Logout
- Login mit Test-Credentials
- Anzeigename setzen und speichern
- Verifizieren, dass der Name im Header erscheint
- Logout und Login-Buttons sichtbar

**`expert-pool.spec.ts`** — Sichtbarkeits-Tiers
- Pool anonym besuchen → Titel "Expertinnen-Pool", keine E-Mails, keine Profil-Links
- Einloggen → Pool besuchen → E-Mails und Profil-Links sichtbar

Voraussetzung: laufende CMS-Instanz mit Testdaten (Test-User als Experte).

## Teil 3: Dokumentation

`docs/auth-pages.md` erweitern um:
- User Attributes Tabelle (handle, label, required, public)
- Rollen und Permissions (PERMISSION_MAP + GROUP_TO_ROLE)
- Expertenpool-Mechanik (Gruppe, Filter, Sichtbarkeit)
- Forced Profile Completion (Mechanismus, erlaubte Pfade)

## Betroffene Dateien

| Datei | Änderung |
|---|---|
| `public/packages/inno_frontend/src/Service/ProfileCompletionChecker.php` | Neue Klasse (extrahiert aus controller.php) |
| `public/packages/inno_frontend/controller.php` | Closure durch ProfileCompletionChecker ersetzen |
| `tests/unit/inno_frontend/Service/ProfileCompletionCheckerTest.php` | Unit-Tests |
| `tests/e2e/specs/auth-flow.spec.ts` | E2E: Login, Profil, Logout |
| `tests/e2e/specs/expert-pool.spec.ts` | E2E: Sichtbarkeits-Tiers |
| `docs/auth-pages.md` | Erweiterte Dokumentation |
