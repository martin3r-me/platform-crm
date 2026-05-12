<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Crm\Models\CrmContactList;

class ContactListIndex extends Component
{
    public string $search = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $perPage = 50;
    public int $page = 1;

    // Create Modal
    public bool $modalShow = false;
    public string $listName = '';
    public string $listDescription = '';

    public function updatedSearch(): void { $this->page = 1; }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return trim($this->search) !== '';
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function contactLists()
    {
        $search = trim($this->search);

        return CrmContactList::forTeam($this->getTeamId())
            ->active()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                );
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->take($this->perPage * $this->page)
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->modalShow = true;
    }

    public function closeCreateModal(): void
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function createList(): void
    {
        $this->validate([
            'listName' => 'required|string|max:255',
            'listDescription' => 'nullable|string|max:1000',
        ]);

        $list = CrmContactList::create([
            'name' => $this->listName,
            'description' => $this->listDescription ?: null,
            'is_active' => true,
            'member_count' => 0,
            'created_by_user_id' => auth()->id(),
            'team_id' => $this->getTeamId(),
        ]);

        $this->resetForm();
        $this->modalShow = false;

        $this->redirect(route('crm.lists.show', ['contactList' => $list->id]), navigate: true);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'name' ? 'asc' : 'desc';
        }
    }

    public function render()
    {
        $this->storeNavigationContext();

        return view('crm::livewire.contact-list-index')
            ->layout('platform::layouts.app');
    }

    private function storeNavigationContext(): void
    {
        $ids = $this->contactLists->pluck('id')->toArray();
        session()->put('crm.list_nav', [
            'ids' => $ids,
            'sort' => $this->sortField,
            'dir' => $this->sortDirection,
        ]);
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }

    private function resetForm(): void
    {
        $this->reset(['listName', 'listDescription']);
    }
}
