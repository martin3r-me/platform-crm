<?php

use Illuminate\Support\Facades\Route;
use Platform\Crm\Http\Controllers\CardDav\DavController;

// CardDAV-Server. MUSS ohne Session-/Modul-Guard laufen — sabre bringt eigene
// HTTP-Basic-Auth mit (siehe TokenAuthBackend). Analog zu comms-webhooks.php.

$path = trim((string) config('crm.carddav.path', 'crm/dav'), '/');

// Alle DAV-Methoden (PROPFIND, REPORT, GET, OPTIONS, ...) auf beliebige Unterpfade.
Route::any($path.'/{any?}', DavController::class)
    ->where('any', '.*')
    ->name('crm.carddav.dav');

// Autodiscovery: /.well-known/carddav -> Basis-URL des DAV-Servers.
Route::get('.well-known/carddav', fn () => redirect('/'.$path, 301))
    ->name('crm.carddav.wellknown');
