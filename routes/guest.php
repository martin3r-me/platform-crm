<?php

use Illuminate\Support\Facades\Route;
use Platform\Crm\Services\Comms\NewsletterService;
use Platform\Crm\Services\Comms\SubscriptionService;
use Platform\Crm\Livewire\Newsletter\PreferenceCenter;

// Unsubscribe GET — redirect to Preference Center (backwards compatible with old links)
Route::get('/newsletter/unsubscribe/{team}/{email}', function (\Illuminate\Http\Request $request, int $team, string $email) {
    abort_unless($request->hasValidSignature(), 403, 'Ungültiger oder abgelaufener Link.');

    $preferenceCenterUrl = app(SubscriptionService::class)->generatePreferenceCenterUrl($team, $email);

    return redirect($preferenceCenterUrl);
})->name('crm.newsletter.unsubscribe');

// Unsubscribe POST — RFC 8058 One-Click Unsubscribe
Route::post('/newsletter/unsubscribe/{team}/{email}', function (\Illuminate\Http\Request $request, int $team, string $email) {
    abort_unless($request->hasValidSignature(), 403, 'Ungültiger oder abgelaufener Link.');

    app(SubscriptionService::class)->globalUnsubscribe($team, $email, 'one_click_unsubscribe');

    return response('', 200);
})->name('crm.newsletter.unsubscribe.post');

// DOI Confirmation
Route::get('/newsletter/confirm/{token}', function (string $token) {
    $member = app(SubscriptionService::class)->confirmDoi($token);

    if (!$member) {
        return view('crm::newsletter.doi-error');
    }

    $listName = $member->contactList?->name ?? 'Newsletter';
    return view('crm::newsletter.doi-confirmed', ['listName' => $listName]);
})->name('crm.newsletter.doi-confirm');

// Preference Center
Route::get('/newsletter/preferences/{team}/{email}', PreferenceCenter::class)
    ->middleware('signed')
    ->name('crm.newsletter.preferences');
