<?php

namespace Platform\Crm\Livewire\Company;

use Livewire\Component;
use Platform\Crm\Models\CrmCompany;

class Company extends Component
{
    public CrmCompany $company;
    public $mode = 'show'; // 'show' oder 'edit'

    public function mount($id = null, $mode = 'show')
    {
        if ($id) {
            $this->company = CrmCompany::findOrFail($id);
        }
        $this->mode = $mode;
    }

    public function render()
    {
        return view('crm::livewire.company.company')
            ->layout('platform::layouts.app');
    }
} 