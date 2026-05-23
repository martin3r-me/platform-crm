<?php

namespace Platform\Crm\Livewire\NewsletterTemplate;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CommsNewsletterTemplate;

class NewsletterTemplate extends Component
{
    public CommsNewsletterTemplate $newsletterTemplate;

    // Editable fields
    public string $name = '';
    public ?string $description = null;
    public ?string $category = null;
    public ?string $defaultSubject = null;
    public ?string $defaultPreheader = null;
    public ?string $htmlBody = null;
    public ?string $textBody = null;
    public bool $isActive = true;

    // UI state
    public string $activeTab = 'settings';

    public function mount(CommsNewsletterTemplate $newsletterTemplate)
    {
        $this->newsletterTemplate = $newsletterTemplate->load('createdByUser');

        $this->name = $this->newsletterTemplate->name ?? '';
        $this->description = $this->newsletterTemplate->description;
        $this->category = $this->newsletterTemplate->category;
        $this->defaultSubject = $this->newsletterTemplate->default_subject;
        $this->defaultPreheader = $this->newsletterTemplate->default_preheader;
        $this->htmlBody = $this->newsletterTemplate->html_body;
        $this->textBody = $this->newsletterTemplate->text_body;
        $this->isActive = $this->newsletterTemplate->is_active;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'defaultSubject' => 'nullable|string|max:255',
            'defaultPreheader' => 'nullable|string|max:255',
            'htmlBody' => 'nullable|string',
            'textBody' => 'nullable|string',
            'isActive' => 'boolean',
        ]);

        $this->newsletterTemplate->name = $this->name;
        $this->newsletterTemplate->description = $this->description ?: null;
        $this->newsletterTemplate->category = $this->category ?: null;
        $this->newsletterTemplate->default_subject = $this->defaultSubject ?: null;
        $this->newsletterTemplate->default_preheader = $this->defaultPreheader ?: null;
        $this->newsletterTemplate->html_body = $this->htmlBody ?: null;
        $this->newsletterTemplate->text_body = $this->textBody ?: null;
        $this->newsletterTemplate->is_active = $this->isActive;

        $this->newsletterTemplate->save();
        $this->newsletterTemplate->refresh();

        session()->flash('message', 'Vorlage erfolgreich gespeichert.');
    }

    public function delete(): void
    {
        $this->newsletterTemplate->delete();
        session()->flash('message', 'Vorlage erfolgreich gelöscht.');
        $this->redirect(route('crm.newsletter-templates.index'), navigate: true);
    }

    #[Computed]
    public function isDirty(): bool
    {
        return $this->name !== ($this->newsletterTemplate->name ?? '')
            || ($this->description ?: null) !== $this->newsletterTemplate->description
            || ($this->category ?: null) !== $this->newsletterTemplate->category
            || ($this->defaultSubject ?: null) !== $this->newsletterTemplate->default_subject
            || ($this->defaultPreheader ?: null) !== $this->newsletterTemplate->default_preheader
            || ($this->htmlBody ?: null) !== $this->newsletterTemplate->html_body
            || ($this->textBody ?: null) !== $this->newsletterTemplate->text_body
            || $this->isActive !== $this->newsletterTemplate->is_active;
    }

    public function render()
    {
        return view('crm::livewire.newsletter-template.newsletter-template')
            ->layout('platform::layouts.app');
    }
}
