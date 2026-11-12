<?php

namespace Platform\Crm\Livewire\Settings\ContactSource;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Crm\Models\CrmContactSource;

/**
 * Stammdaten-Verwaltung der Kontakt-Quellen (Lookup "contact_sources").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Quellen im UI – kein Hardcode.
 * Spiegelt das Settings-Muster der Unternehmens-Typen.
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
    public function contactSources()
    {
        $query = CrmContactSource::query();

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
        $source = CrmContactSource::findOrFail($id);
        $this->editingId = $source->id;
        $this->form = [
            'name' => $source->name,
            'code' => $source->code,
            'is_active' => (bool) $source->is_active,
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

        $uniqueRule = 'unique:crm_contact_sources,code' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => ['required', 'string', 'max:60', $uniqueRule],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            CrmContactSource::findOrFail($this->editingId)->update($data);
            $message = 'Kontakt-Quelle aktualisiert';
        } else {
            CrmContactSource::create($data);
            $message = 'Kontakt-Quelle erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $source = CrmContactSource::findOrFail($id);
        $source->is_active = !$source->is_active;
        $source->save();
        $this->dispatch('toast', message: $source->is_active ? 'Quelle aktiviert' : 'Quelle deaktiviert');
    }

    public function delete(int $id)
    {
        $source = CrmContactSource::findOrFail($id);
        $source->delete();
        $this->dispatch('toast', message: 'Kontakt-Quelle gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('crm::livewire.settings.contact-source.index')
            ->layout('platform::layouts.app');
    }
}
