<?php

use Illuminate\Support\Facades\Route;
use Platform\Crm\Http\Controllers\CardDav\DavController;

// CardDAV-Server. MUSS ohne Session-/Modul-Guard laufen — sabre bringt eigene
// HTTP-Basic-Auth mit (siehe TokenAuthBackend). Analog zu comms-webhooks.php.

$path = trim((string) config('crm.carddav.path', 'crm/dav'), '/');

// WICHTIG: Route::any() deckt KEINE WebDAV-Methoden ab (nur GET/HEAD/POST/PUT/
// PATCH/DELETE/OPTIONS). PROPFIND/REPORT/... müssen explizit gelistet werden,
// sonst antwortet Laravel mit 405, bevor der Controller erreicht wird.
// Schreibmethoden sind bewusst dabei, damit sabre sie sauber mit 403 ablehnt
// (statt Laravel-405-HTML).
$davMethods = [
    'GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS',
    'PROPFIND', 'PROPPATCH', 'REPORT', 'MKCOL', 'MOVE', 'COPY', 'LOCK', 'UNLOCK', 'ACL',
];

Route::match($davMethods, $path.'/{any?}', DavController::class)
    ->where('any', '.*')
    ->name('crm.carddav.dav');

// Autodiscovery: /.well-known/carddav -> Basis-URL des DAV-Servers.
Route::get('.well-known/carddav', fn () => redirect('/'.$path, 301))
    ->name('crm.carddav.wellknown');
