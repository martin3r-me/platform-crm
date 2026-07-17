---
title: DAV-Plumbing in den Core (Schritt 1+2)
order: 51
---

# 🏗️ DAV-Infrastruktur in den Core ziehen — Umsetzungsplan (Schritt 1+2)

**Ziel:** Die generische Sabre-/WebDAV-Infrastruktur aus dem CRM-Modul in den
**Core** heben, sodass mehrere Module (CRM → CardDAV/Kontakte, Planner → CalDAV/
Aufgaben) dieselbe Plumbing nutzen und nur ihren **spezifischen** Teil beisteuern.

**Grundprinzip:** **Kein Verhaltensänderung.** Schritt 1+2 ist ein reiner Refactor;
der Live-Endpoint verhält sich danach identisch (gleiche URL, gleiche Auth, gleiche
Responses). Verifikation über dieselbe Live-curl-Suite wie beim CardDAV.

> CalDAV/Aufgaben (Planner) und Write-Back sind **Schritt 3+4** und NICHT Teil
> dieses Plans. Der Contract wird aber so entworfen, dass Planner später nur noch
> ein `DavModule` registriert. Aufgaben liegen im `planner`-Modul (`PlannerTask`:
> `title`, `due_date`, `priority`, `lifecycle_state`, `user_id`).

---

## Zielarchitektur

```
Core (platform-core)  ── generische Plumbing ──────────────────────────────
  Platform\Core\Dav\
    DavServerFactory        Sabre-Server bauen (Tree + Plugins + Auth + ACL)
    CapturingSapi           Output abfangen -> Laravel-Response
    TokenAuthBackend        HTTP-Basic gegen dav_subscriptions.secret
    DavContext              hält das authentifizierte Abo (geteilt)
    PrincipalBackend        1 Principal je User (principals/{userId})
    DavModuleRegistry       Module registrieren ihre DavModule hier
  Platform\Core\Contracts\
    DavModuleInterface      Contract, den Module implementieren
  Platform\Core\Http\Controllers\Dav\
    DavController           Laravel<->Sabre-Bridge (exec + Response)
  Platform\Core\Models\
    DavSubscription         Tabelle dav_subscriptions (module,type,resource_id,secret,...)
  core/routes/dav.php       Route + WebDAV-Methodenliste + .well-known
  core/config/dav.php       enabled, path, secret_ttl_days

CRM (platform-crm)  ── nur der CardDAV-spezifische Teil ───────────────────
  Platform\Crm\Dav\
    CrmCardDavModule        implements DavModuleInterface (type=carddav)
    CrmCardDavBackend       CrmContactList -> Adressbuch, CrmContact -> vCard
  Platform\Crm\Services\CardDav\
    ContactVCardMapper      (bleibt unverändert)
```

Jedes Modul weiß „was ist eine Collection / ein Objekt / wer darf's sehen".
Der Core weiß „Protokoll + Auth + Routing + Team-Kontext".

---

## Der Contract

```php
namespace Platform\Core\Contracts;

use Platform\Core\Dav\DavContext;
use Platform\Core\Dav\PrincipalBackend;
use Sabre\DAV\ICollection;

interface DavModuleInterface
{
    public function key(): string;        // Modul-Diskriminator, z. B. 'crm'
    public function type(): string;       // 'carddav' | 'caldav'

    /** Wurzelknoten (AddressBookRoot / CalendarRoot), an das Modul-Backend verdrahtet. */
    public function rootNode(DavContext $context, PrincipalBackend $principals): ICollection;

    /** Protokoll-Plugins, z. B. [new \Sabre\CardDAV\Plugin()]. */
    public function plugins(): array;
}
```

**Registrierung** (analog `EmbeddingProviderRegistry` im Core): jedes Modul ruft in
seinem ServiceProvider `DavModuleRegistry::register(new CrmCardDavModule(...))` auf.

**Server-Bau (kombiniert, Nextcloud-Stil):** `DavServerFactory` mountet die Roots
**aller** registrierten Module + deren Plugins + Principals + Auth + ACL. Nach der
Auth scopt jedes Backend über `DavContext->subscription()` — ein carddav-Abo sieht
nur Adressbücher, die CalDAV-Collection bleibt für dieses Abo leer. So braucht es
kein Vorab-Auflösen des Secrets, und später zeigt **ein** Account Kontakte *und*
Aufgaben.

---

## Was wandert / was bleibt

| Datei (heute in CRM) | Aktion |
|---|---|
| `src/CardDav/DavServer.php` | → Core `Dav/DavServerFactory` (generalisiert: iteriert Registry) |
| `src/CardDav/CapturingSapi.php` | → Core `Dav/CapturingSapi` (unverändert) |
| `src/CardDav/Auth/TokenAuthBackend.php` | → Core `Dav/TokenAuthBackend` (nutzt `DavSubscription`) |
| `src/CardDav/CardDavContext.php` | → Core `Dav/DavContext` (hält `DavSubscription`) |
| `src/CardDav/PrincipalBackend.php` | → Core `Dav/PrincipalBackend` (unverändert) |
| `src/Http/Controllers/CardDav/DavController.php` | → Core `Http/Controllers/Dav/DavController` |
| `src/Models/CrmCardDavSubscription.php` | → Core `Models/DavSubscription` (+ Felder module/type/resource_id) |
| `routes/carddav.php` | → Core `routes/dav.php` |
| `config/crm.php` (`carddav`-Block) | → Core `config/dav.php` |
| `src/CardDav/CrmCardDavBackend.php` | **bleibt in CRM** (→ `Dav/CrmCardDavBackend`), nutzt `DavContext` + `resource_id` |
| `src/Services/CardDav/ContactVCardMapper.php` | **bleibt in CRM**, unverändert |

---

## Schritt 1 — Core-Extraktion

1. **Namespace/Ordner** `core/src/Dav/` + `core/src/Http/Controllers/Dav/` anlegen.
2. **Klassen verschieben** (siehe Tabelle), Namespaces auf `Platform\Core\Dav\*`
   umstellen. `CapturingSapi`/`PrincipalBackend` bleiben inhaltlich gleich.
3. **`DavSubscription`-Model + Migration `dav_subscriptions`** im Core:
   ```
   id, user_id->users, team_id->teams,
   module (string), type (string), resource_id (unsignedBigInteger nullable),
   secret (string,64,unique), name, last_used_at, expires_at, revoked_at, timestamps
   index [user_id, team_id], [module, type], [resource_id]
   ```
   Logik (`scopeActive`, `isActive`, `markUsed`, `revoke`, Secret-Auto-Gen im
   `creating`-Hook, `secret` in `$hidden`) 1:1 aus `CrmCardDavSubscription`.
4. **`TokenAuthBackend`** auf `DavSubscription::active()->where('secret', …)` umstellen;
   Principal weiterhin `principals/{userId}`; `TeamContext::set()` unverändert.
5. **`DavModuleInterface`** (Core `Contracts/`) + **`DavModuleRegistry`** (Singleton,
   `register()` / `all()`), im `CoreServiceProvider` als Singleton gebunden.
6. **`DavServerFactory::make(string $baseUri): Server`** — baut `DavContext`,
   `TokenAuthBackend`, `PrincipalBackend`; Tree = `[PrincipalCollection]` + für jedes
   registrierte Modul `rootNode(...)`; Plugins = Auth + ACL + `array_merge` aller
   `module->plugins()` (dedupliziert).
7. **`DavController`** (Bridge, unverändert) nach Core; liest `config('dav.path')`.
8. **Route** `core/routes/dav.php`: `Route::match($davMethods, $path.'/{any?}', DavController)`
   (die **WebDAV-Methodenliste** aus dem Route-Fix mitnehmen!) + `.well-known/carddav`
   (später zusätzlich `.well-known/caldav`). Im `CoreServiceProvider::boot()` laden,
   gated durch `config('dav.enabled')`.
9. **`config/dav.php`**: `enabled`, `path` (default `dav`), `secret_ttl_days`.
10. **composer:** `sabre/dav` + `sabre/vobject` in **`core/composer.json`** aufnehmen.

## Schritt 2 — CRM auf den Core-Contract umstellen

1. **`CrmCardDavModule implements DavModuleInterface`** (in `crm/src/Dav/`):
   `key()='crm'`, `type()='carddav'`, `rootNode()` → `new AddressBookRoot($principals,
   new CrmCardDavBackend($context, new ContactVCardMapper()))`, `plugins()` →
   `[new \Sabre\CardDAV\Plugin()]`.
2. **`CrmCardDavBackend`** (aus CardDav-Ordner belassen, Namespace `Platform\Crm\Dav`):
   Konstruktor nimmt Core-`DavContext`; statt `subscription->contact_list_id` jetzt
   `subscription->resource_id`; sonst identisch (Team-/Owner-Scope, CTag, read-only).
   Gibt für `type !== 'carddav'`-Abos nichts zurück (leer).
3. **CRM ServiceProvider:** `DavModuleRegistry::register(new CrmCardDavModule())` in
   `boot()`. Die alten Bindungen/Route/Config-Blöcke entfernen.
4. **Alte CRM-Dateien löschen** (jetzt im Core): `DavServer`, `CapturingSapi`,
   `TokenAuthBackend`, `CardDavContext`, `PrincipalBackend`, `DavController`,
   `routes/carddav.php`, `CrmCardDavSubscription`, `carddav`-Config-Block.
5. **Abo-UI (Phase 5)** anpassen: statt `CrmCardDavSubscription::create([... contact_list_id ...])`
   jetzt `DavSubscription::create(['module'=>'crm','type'=>'carddav','resource_id'=>$list->id, ...])`.
   Computed `cardDavSubscriptions` filtert auf `module='crm', type='carddav', resource_id=$list->id`.
   `cardDavUrl` liest `config('dav.path')`.
6. **composer:** `sabre/dav` aus `crm/composer.json` entfernen (kommt via Core);
   `sabre/vobject` bleibt (direkter Gebrauch im Mapper).

---

## Datenmigration `crm_carddav_subscriptions` → `dav_subscriptions`

In der neuen Core-Migration nach `Schema::create('dav_subscriptions')`:
```php
if (Schema::hasTable('crm_carddav_subscriptions')) {
    DB::table('crm_carddav_subscriptions')->orderBy('id')->each(function ($r) {
        DB::table('dav_subscriptions')->insert([
            'user_id'=>$r->user_id,'team_id'=>$r->team_id,
            'module'=>'crm','type'=>'carddav','resource_id'=>$r->contact_list_id,
            'secret'=>$r->secret,'name'=>$r->name,
            'last_used_at'=>$r->last_used_at,'expires_at'=>$r->expires_at,
            'revoked_at'=>$r->revoked_at,'created_at'=>$r->created_at,'updated_at'=>$r->updated_at,
        ]);
    });
    Schema::drop('crm_carddav_subscriptions');
}
```
Bestehende Abos behalten damit **ihr Secret** — auf Geräten muss nichts neu
eingerichtet werden. (Feature ist frisch; real gibt es kaum Datensätze.)

---

## Verifikation (Verhalten unverändert)

Nach Deploy die **gleiche Live-Suite** wie beim CardDAV, jetzt gegen die Core-Route
`https://<host>/<dav.path>/`:

- `OPTIONS` ohne Auth → **401** + `WWW-Authenticate: Basic realm="CRM CardDAV"`
- `PROPFIND` ohne Auth → **401** + DAV-XML (`Sabre\DAV\Exception\NotAuthenticated`)
- `.well-known/carddav` → **301** auf die Basis-URL
- authentifiziert (bestehendes Secret) → **207** + echte vCards (unverändert)
- iOS-Abo mit vorhandenem Secret synct weiter ohne Neueinrichtung

Zusätzlich: der Sabre-Wiring-Smoke-Test (Stub-Backends) läuft unverändert grün.

---

## Deploy-Reihenfolge & Risiko

- **Cross-Package:** Core **und** CRM ändern sich → Instanzen brauchen
  `composer update` (Core zieht sabre) **und** `php artisan migrate` (Tabelle) +
  ggf. `route:clear`.
- **Rollback:** `config('dav.enabled')=false` schaltet den Endpoint sauber ab.
  Die Datenmigration ist additiv (kopiert, dropt danach) — vor Produktivlauf ein
  DB-Backup, falls doch reale Abos existieren.
- **Reihenfolge:** erst Core deployen/mergen (stellt Infrastruktur), dann CRM
  (registriert Modul, entfernt alte Dateien) — beide liegen im selben Monorepo-Push,
  also atomar mergebar.

---

## Danach (nicht Teil von Schritt 1+2)

- **Schritt 3:** `planner` registriert `PlannerCalDavModule` (type=caldav), Aufgaben
  read-only als `VTODO` → erscheinen in Apple *Erinnerungen*.
- **Schritt 4:** Completion-Write-Back (`updateCalendarObject` → `lifecycle_state`),
  roher iCal-Blob + ETag je Task für verlustfreien Round-Trip.
