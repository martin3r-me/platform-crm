<?php

namespace Platform\Crm\Livewire\Settings\CompanyType;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyType;

/**
 * Stammdaten-Verwaltung der Unternehmens-Typen (Lookup "company_types").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Typen im UI – kein Hardcode,
 * kein MCP/Seeder nötig. Spiegelt das Settings-Muster aus dem organization-Modul.
 */
class Index extends Component
{
    public $search = '';
    public $showInactive = false;
    public $modalShow = false;
    public ?int $editingId = null;

    public $form = [
        'name' => '',
        'code' => '',
        'is_active' => true,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'showInactive' => ['except' => false],
    ];

    #[Computed]
    public function companyTypes()
    {
        $query = CrmCompanyType::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        if (!$this->showInactive) {
            $query->active();
        }

        return $query->orderBy('name')->get();
    }

    public function create()
    {
        $this->reset('form', 'editingId');
        $this->form['is_active'] = true;
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function edit(int $id)
    {
        $type = CrmCompanyType::findOrFail($id);
        $this->editingId = $type->id;
        $this->form = [
            'name' => $type->name,
            'code' => $type->code,
            'is_active' => (bool) $type->is_active,
        ];
        $this->resetValidation();
        $this->modalShow = true;
    }

    public function save()
    {
        $this->form['name'] = trim((string) $this->form['name']);

        // Code aus dem Namen ableiten, falls leer gelassen
        if (trim((string) $this->form['code']) === '') {
            $this->form['code'] = Str::slug($this->form['name'], '_');
        }

        $uniqueRule = 'unique:crm_company_types,code' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => ['required', 'string', 'max:20', $uniqueRule],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            CrmCompanyType::findOrFail($this->editingId)->update($data);
            $message = 'Unternehmens-Typ aktualisiert';
        } else {
            CrmCompanyType::create($data);
            $message = 'Unternehmens-Typ erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $type = CrmCompanyType::findOrFail($id);
        $type->is_active = !$type->is_active;
        $type->save();
        $this->dispatch('toast', message: $type->is_active ? 'Typ aktiviert' : 'Typ deaktiviert');
    }

    public function delete(int $id)
    {
        $type = CrmCompanyType::findOrFail($id);

        $inUse = CrmCompany::where('company_type_id', $type->id)->count();
        if ($inUse > 0) {
            $this->dispatch('toast', type: 'error', message: "Wird noch von {$inUse} Unternehmen genutzt – bitte stattdessen deaktivieren.");
            return;
        }

        $type->delete();
        $this->dispatch('toast', message: 'Unternehmens-Typ gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('crm::livewire.settings.company-type.index')
            ->layout('platform::layouts.app');
    }
}
