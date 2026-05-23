<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Crm\Models\CommsNewsletterTemplate;

class NewsletterTemplateIndex extends Component
{
    public string $search = '';
    public ?string $categoryFilter = null;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 50;
    public int $page = 1;

    // Create Modal
    public bool $modalShow = false;
    public string $templateName = '';
    public ?string $templateCategory = null;

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedCategoryFilter(): void { $this->page = 1; }

    public function resetFilters(): void
    {
        $this->reset(['categoryFilter', 'search']);
        $this->page = 1;
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return !empty($this->categoryFilter)
            || trim($this->search) !== '';
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function templates()
    {
        $search = trim($this->search);

        return CommsNewsletterTemplate::with('createdByUser')
            ->forTeam($this->getTeamId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                );
            })
            ->when(!empty($this->categoryFilter), fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->take($this->perPage * $this->page)
            ->get();
    }

    #[Computed]
    public function categories(): array
    {
        return CommsNewsletterTemplate::forTeam($this->getTeamId())
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values()
            ->toArray();
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

    public function createTemplate(): void
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
            'templateCategory' => 'nullable|string|max:255',
        ]);

        $template = CommsNewsletterTemplate::create([
            'name' => $this->templateName,
            'category' => $this->templateCategory ?: null,
            'team_id' => $this->getTeamId(),
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;

        $this->redirect(route('crm.newsletter-templates.show', ['newsletterTemplate' => $template->id]), navigate: true);
    }

    public function deleteTemplate(int $id): void
    {
        $template = CommsNewsletterTemplate::forTeam($this->getTeamId())->find($id);
        $template?->delete();
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
        return view('crm::livewire.newsletter-template-index')
            ->layout('platform::layouts.app');
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }

    private function resetForm(): void
    {
        $this->reset(['templateName', 'templateCategory']);
    }
}
