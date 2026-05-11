<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Crm\Models\CommsNewsletter;

class NewsletterIndex extends Component
{
    public string $search = '';
    public $statusFilter = null;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 50;
    public int $page = 1;

    // Create Modal
    public bool $modalShow = false;
    public string $newsletterName = '';
    public string $newsletterSubject = '';

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedStatusFilter(): void { $this->page = 1; }

    public function resetFilters(): void
    {
        $this->reset(['statusFilter', 'search']);
        $this->page = 1;
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return !empty($this->statusFilter)
            || trim($this->search) !== '';
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function newsletters()
    {
        $search = trim($this->search);

        return CommsNewsletter::with(['createdByUser', 'contactList'])
            ->where('team_id', $this->getTeamId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                );
            })
            ->when(!empty($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
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

    public function createNewsletter(): void
    {
        $this->validate([
            'newsletterName' => 'required|string|max:255',
            'newsletterSubject' => 'required|string|max:255',
        ]);

        $newsletter = CommsNewsletter::create([
            'name' => $this->newsletterName,
            'subject' => $this->newsletterSubject,
            'status' => 'draft',
            'created_by_user_id' => auth()->id(),
            'team_id' => $this->getTeamId(),
        ]);

        $this->resetForm();
        $this->modalShow = false;

        $this->redirect(route('crm.newsletters.show', ['newsletter' => $newsletter->id]), navigate: true);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $this->storeNavigationContext();

        return view('crm::livewire.newsletter-index')
            ->layout('platform::layouts.app');
    }

    private function storeNavigationContext(): void
    {
        $ids = $this->newsletters->pluck('id')->toArray();
        session()->put('crm.newsletter_nav', [
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
        $this->reset(['newsletterName', 'newsletterSubject']);
    }
}
