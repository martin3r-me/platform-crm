<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CommsEmailMailAttachment;
use Platform\Crm\Livewire\Contact\Index as ContactIndex;
use Platform\Crm\Livewire\Contact\Contact as ContactShow;
use Platform\Crm\Livewire\Company\Index as CompanyIndex;
use Platform\Crm\Livewire\Company\Company as CompanyShow;

Route::get('/', Platform\Crm\Livewire\Dashboard::class)->name('crm.dashboard');

// Email attachment serving (signed URL)
Route::get('/comms/email-attachments/{attachment}', function (Request $request, CommsEmailMailAttachment $attachment) {
    abort_unless($request->hasValidSignature(), 403, 'Ungültige oder abgelaufene URL');

    $storage = Storage::disk($attachment->disk);
    abort_unless($storage->exists($attachment->path), 404);

    return response($storage->get($attachment->path), 200, [
        'Content-Type' => $attachment->mime ?: 'application/octet-stream',
        'Cache-Control' => 'public, max-age=3600',
        'Content-Disposition' => 'inline; filename="' . $attachment->filename . '"',
    ]);
})->name('crm.comms.email-attachment.show');

Route::get('/contacts', ContactIndex::class)->name('crm.contacts.index');
Route::get('/contacts/{contact}', ContactShow::class)->name('crm.contacts.show');

Route::get('/companies', CompanyIndex::class)->name('crm.companies.index');
Route::get('/companies/{company}', CompanyShow::class)->name('crm.companies.show');

