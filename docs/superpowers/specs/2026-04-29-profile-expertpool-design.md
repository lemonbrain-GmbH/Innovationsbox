# Design Spec: Profilbild, Anzeigename & Expertenpool

**Datum:** 2026-04-29
**Status:** Approved
**Scope:** Punkte 1 (Profilbild), 5 (Anzeigename), 4 (Expertenpool) — als zusammenhängender Block

## Kontext

Drei offene Punkte berühren dasselbe Datenmodell und dieselben Templates:

1. Der Avatar-Cropper rendert nicht für Non-Admin-User (CMS-Berechtigungsproblem)
2. Überall wird `getUserName()` als öffentlicher Name angezeigt — der Login-Username, der keine Leerzeichen erlaubt. User brauchen einen frei wählbaren Anzeigenamen.
3. Das Mitgliederverzeichnis (`/members/directory`) soll zum Expertenpool werden: nur Experten sichtbar, Detailgrad abhängig vom Login-Status.

Die drei hängen zusammen: der Expertenpool zeigt Avatare (Punkt 1 muss funktionieren) und Anzeigenamen (Punkt 5 muss existieren). Reihenfolge: 1 → 5 → 4.

## Entscheidungen

| Entscheidung | Gewählt | Alternativen verworfen |
|---|---|---|
| Expertenstatus | CMS-Gruppe «Experte» | Boolean User Attribute (weniger flexibel, keine native Gruppenverwaltung) |
| Anzeigename | Neues User Attribute `display_name` | `getUserName()` mit Leerzeichen erlauben (bricht Login-Logik) |
| Moderator-UI für Expertenstatus | CMS-Dashboard (MVP) | Custom Frontend (Aufwand für MVP zu hoch) |
| Avatar-Fix | CMS File-Permission + Validierung im Controller | Eigener Cropper (unnötige Abweichung vom CMS-Core) |

---

## Teil 1: Profilbild-Upload beheben

### Problem

Der CMS-Core Avatar-Cropper (`AvatarCropperInstanceFactory`) rendert nicht für Non-Admin-User. Der Controller ruft `$this->requireAsset('javascript', 'vue')` auf (`controllers/single_page/account/avatar.php:45`), und die Cropper-Instanz gibt via `$instance->getTag()` eine Vue-Komponente aus. Ohne File-Manager-Permissions für registrierte User rendert das Tag nichts.

### Massnahmen

**1. CMS-Berechtigung setzen (Dashboard, kein Code)**

Im Dashboard: *System & Settings → Files → File Manager → Permissions* — die Berechtigung «Add File» für die Gruppe «Registered Users» aktivieren. Das erlaubt den Cropper-Upload.

Diese Einstellung muss in der Spec und in `docs/auth-pages.md` dokumentiert werden, damit sie auf neuen Umgebungen reproduzierbar ist.

**2. Dateityp- und Grössenvalidierung im Controller (Code)**

Da die globale File-Upload-Berechtigung breiter ist als nötig, wird `save_avatar()` um eine explizite Validierung erweitert:

- Erlaubte MIME-Types: `image/jpeg`, `image/png`, `image/webp`
- Maximale Dateigrösse: 5 MB
- Validierung vor dem `UpdateUserAvatarCommand` (`avatar.php:77-79`)
- Bei ungültigem Typ/Grösse: `JsonResponse` mit Fehlermeldung, HTTP 400

### Betroffene Dateien

- `controllers/single_page/account/avatar.php` — Validierung in `save_avatar()` (nach Zeile 76, vor Zeile 78)
- CMS Dashboard — Permission-Einstellung (dokumentiert)

---

## Teil 2: Anzeigename (`display_name`)

### Problem

Die Plattform zeigt überall `getUserName()` als öffentlichen Namen. Das ist der CMS-Login-Username, der keine Leerzeichen erlaubt und als technischer Identifier dient. User sollen selbst bestimmen können, wie sie öffentlich heissen.

### Neues User Attribute

| Eigenschaft | Wert |
|---|---|
| Handle | `display_name` |
| Name (Label) | «Anzeigename» |
| Typ | Text |
| Editierbar auf Profil | ja |
| In Public Profile List | ja |
| Required on Profile | ja (triggert Forced Profile Completion) |
| Required on Register | nein |
| Unique | nein (Anzeigename, kein Identifier) |
| Validierung | Min. 2 Zeichen, max. 100 Zeichen, HTML-Escaping (bereits via `htmlspecialchars()`) |

### Anlage

Programmatisch in `inno_frontend/controller.php` → `install()` und `upgrade()` via `UserAttributeKey::add()`. Nicht manuell im Dashboard — reproduzierbar auf allen Umgebungen.

### Anzeige-Logik

Fallback-Kette überall wo ein Name öffentlich angezeigt wird:

```
display_name → getUserName() (Fallback wenn display_name leer)
```

### Anpassungen im Code

| Datei | Zeile | Aktuell | Neu |
|---|---|---|---|
| `themes/innovationsbox/elements/header.php` | 21 | `$ui->getUserName()` | `$ui->getAttribute('display_name') ?: $ui->getUserName()` |
| `single_pages/members/profile.php` | 33, 45 | `$profile->getUserName()` | gleiche Fallback-Logik |
| `single_pages/members/directory.php` | 66 | `$user->getUserName()` | gleiche Fallback-Logik |
| `molecules/member-card/member-card.php` | 2 | `$props['userName']` | Datenquelle ändert sich im Aufrufer (directory.php) |

### Profil-Bearbeitung

`display_name` erscheint automatisch im «Additional Information»-Abschnitt der Edit-Profile-Seite, weil es als «editierbar auf Profil» markiert ist. Der CMS Attribute Renderer (`edit_profile.php:128`, `$profileFormRenderer->buildView($ak)->render()`) rendert das Feld ohne Template-Anpassung.

Da `display_name` als «Required on Profile» markiert ist, greift die bestehende Forced-Profile-Completion-Logik (`controller.php:94-96`): User mit leerem `display_name` werden beim nächsten Seitenaufruf zu `/account/edit_profile?complete=1` weitergeleitet.

### Nicht im Scope

- Uniqueness-Constraint auf `display_name`
- Änderung des Login-Username-Feldes (`uName`)
- Umbenennung des Username-Feldes im Edit-Profile-Formular

---

## Teil 3: Expertenpool

### Problem

Das Mitgliederverzeichnis (`/members/directory`) zeigt aktuell alle registrierten User. Es soll zum Expertenpool werden: nur User mit Expertenstatus sichtbar, mit unterschiedlichem Detailgrad je nach Login-Status des Betrachters.

### A. Expertenstatus via CMS-Gruppe

Neue CMS-Benutzergruppe «Experte».

**Anlage:** Programmatisch in `inno_frontend/controller.php` → `install()` via `Group::add()`.

**Zuweisung:** Moderatoren weisen User der Gruppe im CMS-Dashboard zu (*Members → User Search → User → Groups*). Kein Custom-Frontend im MVP.

**Integration ins Berechtigungssystem:**

`ConcreteCmsAuth.php` wird erweitert:

```
GROUP_TO_ROLE (Zeile 22-30):
+ 'experte' => 'expert'

PERMISSION_MAP (Zeile 13-20):
+ 'expert.manage' => ['moderator', 'administrator']
```

### B. Neues User Attribute: Fachgebiet

| Eigenschaft | Wert |
|---|---|
| Handle | `expertise_area` |
| Name (Label) | «Fachgebiet» |
| Typ | Text |
| Editierbar auf Profil | ja |
| In Public Profile List | ja |
| Required on Profile | nein |
| Required on Register | nein |

Wird im gleichen `install()`-Schritt wie `display_name` angelegt. Experten können ihr Fachgebiet auf der Profilseite eintragen; es erscheint im Pool.

### C. Directory-Controller überschreiben

Der aktuelle Controller ist ein leerer Stub:

```php
// controllers/single_page/members/directory.php
class Directory extends CoreDirectory {}
```

Er wird überschrieben, um:

1. **User-Query filtern:** `UserList::filterByGroup('Experte')` — nur Experten werden geladen. Das löst das Pagination-Problem (Filter auf DB-Ebene, nicht im Template).
2. **Auth-State ans Template übergeben:** `$this->set('isLoggedIn', $u->isRegistered())` — das Template entscheidet, was angezeigt wird.

Suche und Pagination bleiben erhalten — sie arbeiten auf der gefilterten Liste.

### D. Sichtbarkeitslogik im Template

`members/directory.php` rendert bedingt basierend auf `$isLoggedIn`:

| Information | Anonym (nicht eingeloggt) | Eingeloggt |
|---|---|---|
| Avatar | ja | ja |
| Anzeigename (`display_name`) | ja | ja |
| Fachgebiet (`expertise_area`) | ja | ja |
| E-Mail | nein | ja |
| Profildetails (weitere Attribute) | nein | ja |
| Link zum Profil | nein (Card ohne Link) | ja (Card verlinkt auf Profil) |

`members/profile.php` bleibt unverändert — die Profilseite ist nur erreichbar über den Link im Pool, der nur eingeloggten Usern angezeigt wird. Direktzugriff via URL ist akzeptabel (kein Geheimnis, nur kein aktiver Link).

### E. member-card Anpassung

`molecules/member-card/member-card.php` bekommt zwei neue Props:

| Prop | Typ | Beschreibung |
|---|---|---|
| `expertise` | string | Fachgebiet, angezeigt unter dem Namen |
| `email` | string | E-Mail, nur wenn `$isLoggedIn` |

Die bestehenden Props (`userName`, `avatarUrl`, `profileUrl`, `memberSince`, `role`) bleiben. `profileUrl` wird auf `#` oder leer gesetzt wenn nicht eingeloggt (Card wird dann ohne `<a>`-Tag gerendert).

### F. Navigation & Seitentitel

- CMS-Seite `/members/directory`: Titel ändern auf «Expertinnen-Pool»
- URL-Slug: `/members/directory` bleibt (CMS-interner Pfad), Navigation zeigt «Expertinnen-Pool»
- Alternativ: Seite auf `/expertinnen-pool` verschieben mit Redirect von `/members/directory` — Entscheidung bei Implementation
- Search-Placeholder anpassen: «Name oder Fachgebiet» statt «Name oder Stichwort»

### G. Storybook-Fixtures

`_fixtures/sample-data.php` hat bereits den Nav-Eintrag «Expertinnen-Pool» (Zeile 31). Keine Änderung nötig. `member-card` Variants-File bekommt neue Varianten mit `expertise` und `email` Props.

---

## Nicht im Scope (bewusst ausgeklammert)

- **Moderator-Frontend** für Expertenstatus (MVP: Dashboard)
- **Selbst-Nominierung** als Experte
- **Audit-Log** für Expertenstatus-Änderungen (separate Spec)
- **Filtermöglichkeiten** nach Fachgebiet im Pool
- **Account-Löschung** und DSGVO (separate Spec)
- **Rate Limiting** auf Login/Register (separate Spec)
- **Tests für die neuen Features** (Punkt 2, separate Spec)
- **Dokumentation** der Auth-Architektur (Punkt 3, separate Spec)

---

## Gesamtübersicht: Betroffene Dateien

| Datei | Änderung |
|---|---|
| `inno_frontend/controller.php` | `install()`/`upgrade()`: User Attributes `display_name` + `expertise_area` anlegen, Gruppe «Experte» anlegen |
| `controllers/single_page/account/avatar.php` | MIME-Type + Grössenvalidierung in `save_avatar()` |
| `controllers/single_page/members/directory.php` | `CoreDirectory` überschreiben: `filterByGroup('Experte')`, Auth-State setzen |
| `single_pages/members/directory.php` | Sichtbarkeitslogik (anonym vs. eingeloggt), Expertise-Feld, Titel/Labels anpassen |
| `single_pages/members/profile.php` | `display_name` statt `getUserName()` |
| `themes/innovationsbox/elements/header.php` | `display_name` statt `getUserName()` |
| `molecules/member-card/member-card.php` | Neue Props `expertise`, `email`; bedingte Verlinkung |
| `molecules/member-card/member-card.variants.php` | Neue Varianten für Storybook |
| `inno_shared/.../ConcreteCmsAuth.php` | `GROUP_TO_ROLE` + `PERMISSION_MAP` erweitern |
| `tests/unit/.../ConcreteCmsAuthTest.php` | Tests für neue Permission `expert.manage` und Rolle `expert` |
| `docs/auth-pages.md` | File-Manager-Permission dokumentieren |

## Abhängigkeiten und Reihenfolge

```
1. Profilbild-Fix (CMS-Permission + Validierung)
   ↓ keine Code-Abhängigkeit, aber logische Voraussetzung
2. display_name Attribut anlegen + 4 Template-Stellen anpassen
   ↓ Pool braucht display_name
3. expertise_area Attribut anlegen
   ↓ Pool zeigt Fachgebiet
4. Gruppe «Experte» anlegen + ConcreteCmsAuth erweitern
   ↓ Directory braucht Gruppe zum Filtern
5. Directory-Controller überschreiben (Query-Filter + Auth-State)
   ↓ Template braucht gefilterte Daten
6. Directory-Template umbauen (Sichtbarkeitslogik)
   ↓ member-card braucht neue Props
7. member-card erweitern (expertise, email, bedingte Verlinkung)
```
