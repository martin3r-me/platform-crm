<?php

use Illuminate\Support\Facades\Route;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Livewire\Contact\Index as ContactIndex;
use Platform\Crm\Livewire\Contact\Contact as ContactShow;
use Platform\Crm\Livewire\Company\Index as CompanyIndex;
use Platform\Crm\Livewire\Company\Company as CompanyShow;

Route::get('/', Platform\Crm\Livewire\Dashboard::class)->name('crm.dashboard');

Route::get('/contacts', ContactIndex::class)->name('crm.contacts.index');
Route::get('/contacts/{contact}', ContactShow::class)->name('crm.contacts.show');

Route::get('/companies', CompanyIndex::class)->name('crm.companies.index');
Route::get('/companies/{company}', CompanyShow::class)->name('crm.companies.show');

