<?php

namespace Platform\Crm\Livewire\Settings\Segment;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Platform\Crm\Models\CrmSegment;

/**
 * Stammdaten-Verwaltung der Segmente (Lookup "segments").
 *
 * Generisch: jede Instanz pflegt ihre eigenen Segmente im UI – kein Hardcode.
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
    public function segments()
    {
        $query = CrmSegment::query();

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
        $segment = CrmSegment::findOrFail($id);
        $this->editingId = $segment->id;
        $this->form = [
            'name' => $segment->name,
            'code' => $segment->code,
            'is_active' => (bool) $segment->is_active,
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

        $uniqueRule = 'unique:crm_segments,code' . ($this->editingId ? ',' . $this->editingId : '');

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.code' => ['required', 'string', 'max:60', $uniqueRule],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($this->editingId) {
            CrmSegment::findOrFail($this->editingId)->update($data);
            $message = 'Segment aktualisiert';
        } else {
            CrmSegment::create($data);
            $message = 'Segment erstellt';
        }

        $this->modalShow = false;
        $this->reset('form', 'editingId');
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id)
    {
        $segment = CrmSegment::findOrFail($id);
        $segment->is_active = !$segment->is_active;
        $segment->save();
        $this->dispatch('toast', message: $segment->is_active ? 'Segment aktiviert' : 'Segment deaktiviert');
    }

    public function delete(int $id)
    {
        $segment = CrmSegment::findOrFail($id);
        $segment->delete();
        $this->dispatch('toast', message: 'Segment gelöscht');
    }

    public function toggleInactive()
    {
        $this->showInactive = !$this->showInactive;
    }

    public function render()
    {
        return view('crm::livewire.settings.segment.index')
            ->layout('platform::layouts.app');
    }
}
