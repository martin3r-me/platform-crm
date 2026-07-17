---
title: CardDAV — Abonnierbares Telefonbuch
order: 50
---

# 📇 CardDAV-Server — Abonnierbares Telefonbuch

Read-only CardDAV-Server im CRM-Package. Jede **Kontaktliste** (`CrmContactList`)
wird als abonnierbares Adressbuch angeboten. Clients (iOS/macOS Contacts, DAVx5
für Android, Thunderbird) syncen die Kontakte einer Liste als **vCards**.

Sichtbarkeit ist strikt **team-** und **owner-gescoped**: Ein abonnierender User
sieht nur Kontakte/Listen, die er auch im CRM sehen darf.

> Status: In Umsetzung. Aufbau auf `sabre/dav` + `sabre/vobject` (Referenz-Impl,
> betreibt u. a. Nextcloud/Baïkal). Wir implementieren nur Backends + vCard-Mapping;
> sabre übernimmt das WebDAV/CardDAV-Protokoll.

---

## Grundsatzentscheidungen

| Thema | Entscheidung |
|---|---|
| Bibliothek | `sabre/dav` + `sabre/vobject` (kein Eigenbau des Protokolls) |
| Adressbuch = | eine `CrmContactList` |
| Schreibrichtung | **read-only** (v1); alle Schreib-Ops → `403 Forbidden` |
| Auth | Dedizierte Subscription-Credential (Variante A) — eigene Tabelle, kurzes Secret als Basic-Auth-Passwort |
| vCard-Version | 3.0 (maximale iOS-Kompatibilität) |
| Sichtbarkeit | `team_id` + Owner-Scope (`owned_by_user_id === user` **oder** `null`) |

---

## Datenfluss

```
CardDAV-Client (iOS/DAVx5/Thunderbird)
   │  HTTP Basic (user = Kennung, pass = Abo-Secret)
   ▼
Route  crm/dav/{path}   (außerhalb Session-Middleware, analog comms-webhooks.php)
   ▼
Sabre\DAV\Server
   ├─ TokenAuthBackend      → Secret gegen crm_carddav_subscriptions prüfen → User + Team setzen
   ├─ PrincipalBackend      → principals/users/{id}
   └─ CrmCardDavBackend     → CrmContactList (sichtbar) = Adressbuch
        └─ ContactVCardMapper → CrmContact → VCard
```

---

## Phasen

### Phase 0 — Dependencies & Grundgerüst
- `modules/crm/composer.json`: `sabre/dav`, `sabre/vobject`.
- `config/crm.php`: Block `carddav` (`enabled`, `path`, `secret_ttl_days`).
- Ordner `src/CardDav/` (Backends + Server), `src/Services/CardDav/` (Mapper).
- Instanzen ziehen die Dep per `composer update martin3r/platform-crm`.

### Phase 1 — vCard-Mapping (pure, isoliert testbar)
`Services/CardDav/ContactVCardMapper.php` — `CrmContact` → `Sabre\VObject\Component\VCard`.

| vCard-Property | Quelle |
|---|---|
| `UID` | `uuid` (UuidV7, stabil & unique) |
| `N` / `FN` | `last_name;first_name;middle_name` + `academicTitle` als Prefix |
| `NICKNAME` | `nickname` |
| `BDAY` | `birth_date` |
| `EMAIL;TYPE=…` | `emailAddresses[*]` → `email_address`, Typ aus `emailType.code`, `is_primary` → `PREF` |
| `TEL;TYPE=…` | `phoneNumbers[*]` → `international` (E.164), Typ aus `phoneType.code`, `is_primary` → `PREF` |
| `ADR;TYPE=…` | `postalAddresses[*]` → `street`+`house_number`/`postal_code`/`city`/`country` |
| `ORG` / `TITLE` | primäre `companyRelations` → `company.name` + `position` |
| `NOTE` | `notes` |
| `REV` | `updated_at` |

- **ETag** = `md5(updated_at . id)` — gemeinsam von Mapper (`etagFor`) und Backend genutzt.
- Typ-Mapping CRM-`code` → vCard-`TYPE` (siehe `ContactVCardMapper::TYPE_MAP`).

### Phase 2 — Authentifizierung
`CardDav/Auth/TokenAuthBackend` extends `Sabre\DAV\Auth\Backend\AbstractBasic`:
- `validateUserPass($user, $pass)` → Secret in `crm_carddav_subscriptions` suchen
  (nicht revoked, nicht expired) → User laden → Team-Kontext setzen → `last_used_at`.
- Neue Tabelle `crm_carddav_subscriptions`
  (`id, user_id, team_id, contact_list_id?, secret, name, last_used_at, expires_at, revoked_at`).
- Model `CommsCardDavSubscription` (bzw. `CrmCardDavSubscription`).

### Phase 3 — CardDAV-Backends
- `CardDav/PrincipalBackend` (read-only): ein Principal `principals/users/{id}`.
- `CardDav/CrmCardDavBackend` implements `CardDAV\Backend\BackendInterface` + `SyncSupport`:
  - `getAddressBooksForUser` → sichtbare `CrmContactList` (Team + Owner-Scope), `getctag` = `max(updated_at)`.
  - `getCards` → Member-URIs `{uuid}.vcf` + etag (ohne Body).
  - `getCard` / `getMultipleCards` → `ContactVCardMapper`, mit Sichtbarkeits-Guard.
  - Schreib-Ops → `Forbidden` (read-only).
  - `getChangesForAddressBook` → `sync-collection` via updated_at-Cursor.

### Phase 4 — Server-Wiring & Routen
- `CardDav/DavServer` bootet `Sabre\DAV\Server` mit Auth/CardDAV/Sync/DAVACL-Plugins.
- `routes/carddav.php` außerhalb `ModuleRouter::group('crm')` geladen (eigene Auth), gated durch `config('crm.carddav.enabled')`:
  - `Route::any('crm/dav/{path?}', DavController::class)->where('path', '.*')`
  - `.well-known/carddav` → Redirect für Client-Autodiscovery.

### Phase 5 — Abo-UI (Livewire)
Im `ContactList`-Detail: Panel „Als Adressbuch abonnieren" → Secret erzeugen,
URL + Kennung + Secret (einmalig) + Client-Anleitung anzeigen, Abos widerrufen.

### Phase 6 — Tests & Client-Matrix
- Unit: `ContactVCardMapper` (alle Feldtypen, Mehrfachwerte, fehlende Lookups, Umlaute/UTF-8).
- Feature: `PROPFIND`, `REPORT addressbook-query`/`addressbook-multiget`, `sync-collection`,
  401 bei falschem Secret, Isolation (kein Cross-Team/keine fremden privaten Kontakte).
- Manuell: iOS + macOS Contacts, DAVx5 (Android), Thunderbird.

---

## Betriebs-/Rechtshinweise
- Feature per `config('crm.carddav.enabled')` abschaltbar.
- DSGVO: echtes Personen-Telefonbuch landet auf Privatgeräten → team-intern halten,
  kein externes Teilen; Widerruf eines Abos muss zuverlässig greifen.
- Nur über HTTPS betreiben (Basic-Auth-Secret).
