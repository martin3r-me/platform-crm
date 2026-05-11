<?php

use Illuminate\Support\Facades\Route;
use Platform\Crm\Services\Comms\NewsletterService;

Route::get('/newsletter/unsubscribe/{team}/{email}', function (\Illuminate\Http\Request $request, int $team, string $email) {
    abort_unless($request->hasValidSignature(), 403, 'Ungültiger oder abgelaufener Link.');

    app(NewsletterService::class)->handleUnsubscribe($team, $email);

    return view('crm::newsletter.unsubscribed', ['email' => $email]);
})->name('crm.newsletter.unsubscribe');
