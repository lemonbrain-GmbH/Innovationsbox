# Personas & Testbenutzer

## Rollen-Übersicht

| Rolle (EN) | Rolle (DE / CMS-Gruppe) | Beschreibung |
|---|---|---|
| **Administrator** | Administrator | Systemverwaltung, volle Rechte auf alle Bereiche. Kann Projekträume archivieren und alle Initiator-Aktionen ausführen. |
| **Moderator** | Moderator | Prüft eingereichte Projektideen und entscheidet über Genehmigung oder Ablehnung. Zugang zur Moderations-Queue. |
| **Initiator** | Initiator(in) | Reicht Projektideen ein. Wird bei Genehmigung automatisch Leiter(in) des Projektraums. Verwaltet Collaboard-Links, Status-Updates, Mitgliedsanfragen und Einladungen. |
| **Developer** | Entwickler(in) | Tritt genehmigten Projekträumen bei ("Mitmachen"). Sieht alle Raum-Details inkl. Collaboard-Links, Status-Updates und Mitgliederliste. |
| **Member** | Mitglied | Basis-Rolle. Kann Projektideen einreichen und abstimmen. Kann Projekträume auf der öffentlichen Übersicht sehen. |
| *(anonymous)* | Besucher (nicht eingeloggt) | Sieht die öffentliche Projektübersicht (aktive + abgeschlossene Räume). Keine Interaktionen möglich. |

## Berechtigungen pro Rolle

| Berechtigung | Member | Developer | Initiator | Moderator | Admin |
|---|---|---|---|---|---|
| `idea.submit` | x | x | x | x | x |
| `idea.vote` | x | x | x | x | x |
| `moderation.review` | | | | x | x |
| `space.join` | | x | x | | x |
| `space.view` | | x (eigene) | x (eigene) | | x (alle) |
| `space.manage` | | | x (eigene) | | x (alle) |

## Testbenutzer

### Lokale Entwicklung (localhost)

| User ID | Rolle | Verwendung im Seed-Script |
|---|---|---|
| 1 | Initiator | Erstellt alle Projekträume, schreibt Status-Updates |
| 2 | Entwickler | Mitglied in mehreren Räumen |
| 3 | Entwickler | Mitglied in mehreren Räumen |
| 4 | Entwickler | Mitglied in mehreren Räumen |
| 5 | Entwickler | Pendente Beitrittsanfrage (Space 1) |

### Testserver (innovationsbox.lemonbrain.ch)

| User ID | Rolle | Mapping |
|---|---|---|
| 7 | Admin | localhost ID 4 |
| 8 | Initiator | localhost ID 1 |
| 9 | Entwickler | localhost ID 2 + 5 |
| 10 | Moderator | localhost ID 3 |

Seed-Script Testserver: `scripts/seed-testdata-testserver.sh`
Seed-Script Localhost: `scripts/seed-testdata.sh` (oder `make seed`)

## Ablauf: Idee bis Projektraum

```
Mitglied reicht Idee ein
    → Event: ProjectIdeaSubmitted
    → inno_moderation erstellt pendenten Review
    → inno_notification benachrichtigt Moderatoren

Moderator genehmigt Idee
    → Event: ModerationDecisionMade (approved)
    → inno_project_idea: ProjectIdeaApproved
    → inno_project_space: Projektraum wird automatisch erstellt
    → inno_notification benachrichtigt Initiator(in)

Entwickler(in) tritt bei ("Mitmachen")
    → Beitrittsanfrage wird erstellt
    → Initiator(in) genehmigt/lehnt ab
    → Bei Genehmigung: Entwickler(in) sieht Raum-Details + Collaboard
```
