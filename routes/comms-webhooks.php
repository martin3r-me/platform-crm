<?php

use Illuminate\Support\Facades\Route;
use Platform\Crm\Http\Controllers\Comms\InboundPostmarkController;
use Platform\Crm\Http\Controllers\Comms\WhatsAppWebhookController;

// Webhooks must NOT require auth / module guard.
Route::post('/postmark/inbound', InboundPostmarkController::class)
    ->name('crm.comms.postmark.inbound');

// WhatsApp Meta webhook (GET for verification, POST for messages/status updates)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle'])
    ->name('crm.comms.whatsapp.webhook');

