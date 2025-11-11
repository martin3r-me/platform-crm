<?php

namespace Platform\Crm\Services;

use Illuminate\Support\Collection;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyLink;
use Platform\Crm\Contracts\CompanyLinkableInterface;

class CompanyLinkService
{
    private function getRootTeamId(): int
    {
        $user = auth()->user();
        if (!$user) {
            return 0;
        }
        $baseTeam = $user->currentTeamRelation;
        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }
    /**
     * Finde Companies basierend auf Identifikatoren
     */
    public function findCompaniesByIdentifiers(array $identifiers): Collection
    {
        return CrmCompany::where(function ($query) use ($identifiers) {
            foreach ($identifiers as $identifier) {
                $query->orWhere('name', 'like', '%' . $identifier . '%')
                      ->orWhere('legal_name', 'like', '%' . $identifier . '%')
                      ->orWhere('trading_name', 'like', '%' . $identifier . '%')
                      ->orWhere('registration_number', 'like', '%' . $identifier . '%')
                      ->orWhere('tax_number', 'like', '%' . $identifier . '%')
                      ->orWhere('vat_number', 'like', '%' . $identifier . '%');
            }
        })
        ->with('industry', 'legalForm', 'contactStatus')
        ->get()
        ->filter(fn($company) => $company->is_active);
    }

    /**
     * Finde Company anhand ID
     */
    public function findCompanyById(int $id): ?CrmCompany
    {
        $company = CrmCompany::with('industry', 'legalForm', 'contactStatus')->find($id);
        return $company && $company->is_active ? $company : null;
    }

    /**
     * Hole alle sichtbaren Companies
     */
    public function getAllVisibleCompanies(int $limit = 20): Collection
    {
        return CrmCompany::query()
            ->with('industry', 'legalForm', 'contactStatus')
            ->limit($limit)
            ->get()
            ->filter(fn($company) => $company->is_active);
    }

    /**
     * Suche Companies
     */
    public function searchCompanies(string $search, int $limit = 10): Collection
    {
        if (empty($search)) {
            return $this->getAllVisibleCompanies($limit);
        }

        return CrmCompany::query()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('legal_name', 'like', '%' . $search . '%')
                      ->orWhere('trading_name', 'like', '%' . $search . '%')
                      ->orWhere('registration_number', 'like', '%' . $search . '%')
                      ->orWhere('tax_number', 'like', '%' . $search . '%')
                      ->orWhere('vat_number', 'like', '%' . $search . '%');
            })
            ->with('industry', 'legalForm', 'contactStatus')
            ->limit($limit)
            ->get()
            ->filter(fn($company) => $company->is_active);
    }

    /**
     * Erstelle neue Company
     */
    public function createCompany(array $data): CrmCompany
    {
        $company = CrmCompany::create([
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? null,
            'trading_name' => $data['trading_name'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'website' => $data['website'] ?? null,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'industry_id' => $data['industry_id'] ?? null,
            'legal_form_id' => $data['legal_form_id'] ?? null,
            'contact_status_id' => $data['contact_status_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'team_id' => $this->getRootTeamId(),
            'created_by_user_id' => auth()->id(),
            'owned_by_user_id' => $data['team_visible'] ?? true ? null : auth()->id(),
        ]);

        return $company->load('industry', 'legalForm', 'contactStatus');
    }

    /**
     * Verknüpfe Company mit einem Model
     */
    public function linkCompany(CrmCompany $company, CompanyLinkableInterface $linkable): bool
    {
        if (!$company->is_active) {
            return false;
        }

        // Prüfe ob Verknüpfung bereits existiert
        $existingLink = CrmCompanyLink::where([
            'company_id' => $company->id,
            'linkable_type' => $linkable->getCompanyLinkableType(),
            'linkable_id' => $linkable->getCompanyLinkableId(),
        ])->exists();

        if (!$existingLink) {
            CrmCompanyLink::create([
                'company_id' => $company->id,
                'linkable_type' => $linkable->getCompanyLinkableType(),
                'linkable_id' => $linkable->getCompanyLinkableId(),
                'team_id' => $linkable->getTeamId(),
                'created_by_user_id' => auth()->id(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Entferne Company-Verknüpfung
     */
    public function unlinkCompany(CrmCompany $company, CompanyLinkableInterface $linkable): bool
    {
        $link = CrmCompanyLink::where([
            'company_id' => $company->id,
            'linkable_type' => $linkable->getCompanyLinkableType(),
            'linkable_id' => $linkable->getCompanyLinkableId(),
        ])->first();

        if ($link && $link->created_by_user_id === auth()->id()) {
            return $link->delete();
        }

        return false;
    }

    /**
     * Automatisches Verlinken basierend auf Identifikatoren
     */
    public function autoLinkCompanies(CompanyLinkableInterface $linkable): Collection
    {
        $identifiers = $linkable->getCompanyIdentifiers();
        if (empty($identifiers)) {
            return collect();
        }

        $companies = $this->findCompaniesByIdentifiers($identifiers);
        $linkedCompanies = collect();

        foreach ($companies as $company) {
            if ($this->linkCompany($company, $linkable)) {
                $linkedCompanies->push($company);
            }
        }

        return $linkedCompanies;
    }

    /**
     * Hole alle verknüpften Companies für ein Model
     */
    public function getLinkedCompanies(CompanyLinkableInterface $linkable): Collection
    {
        return CrmCompanyLink::where([
            'linkable_type' => $linkable->getCompanyLinkableType(),
            'linkable_id' => $linkable->getCompanyLinkableId(),
        ])
        ->with('company')
        ->get()
        ->pluck('company')
        ->filter(fn($company) => $company && $company->is_active);
    }

    /**
     * Hole alle verknüpften Models für eine Company
     */
    public function getLinkedModels(CrmCompany $company, string $linkableType): Collection
    {
        return CrmCompanyLink::where([
            'company_id' => $company->id,
            'linkable_type' => $linkableType,
        ])
        ->with('linkable')
        ->get()
        ->pluck('linkable')
        ->filter();
    }

    /**
     * Zähle verknüpfte Companies für ein Model
     */
    public function countLinkedCompanies(CompanyLinkableInterface $linkable): int
    {
        return CrmCompanyLink::where([
            'linkable_type' => $linkable->getCompanyLinkableType(),
            'linkable_id' => $linkable->getCompanyLinkableId(),
        ])->count();
    }

    /**
     * Zähle verknüpfte Models für eine Company
     */
    public function countLinkedModels(CrmCompany $company, string $linkableType): int
    {
        return CrmCompanyLink::where([
            'company_id' => $company->id,
            'linkable_type' => $linkableType,
        ])->count();
    }
}
