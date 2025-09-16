<?php

namespace Platform\Crm\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyLink;

trait HasCompanyLinksTrait
{
    /**
     * Companies, die diesem Modell zugeordnet sind (nur sichtbare).
     */
    public function companyLinks(): MorphMany
    {
        return $this->morphMany(CrmCompanyLink::class, 'linkable')
            ->forCurrentTeam();
    }

    /**
     * Alle Companies (auch unsichtbare) - für Admin-Zwecke.
     */
    public function allCompanyLinks(): MorphMany
    {
        return $this->morphMany(CrmCompanyLink::class, 'linkable')
            ->forCurrentTeam();
    }

    /**
     * Direkter Zugriff auf die Companies (Collection von CrmCompany).
     */
    public function companies(): Collection
    {
        return $this->companyLinks()
            ->with('company')
            ->get()
            ->pluck('company')
            ->filter(function ($company) {
                return $company && $company->is_active;
            });
    }

    /**
     * Eine Company anhängen (wenn noch nicht vorhanden).
     */
    public function attachCompany(CrmCompany $company): bool
    {
        // Prüfen ob Company aktiv ist
        if (!$company->is_active) {
            return false;
        }

        if (! $this->hasCompany($company)) {
            $this->companyLinks()->create([
                'company_id' => $company->id,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mehrere Companies anhängen.
     */
    public function attachCompanies(Collection|array $companies): void
    {
        $activeCompanies = collect($companies)->filter(function ($company) {
            return $company->is_active;
        });

        $companyIds = $activeCompanies->pluck('id');
        $existingIds = $this->companyLinks()->pluck('company_id');
        $newIds = $companyIds->diff($existingIds);
        
        foreach ($newIds as $companyId) {
            $this->companyLinks()->create([
                'company_id' => $companyId,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Eine Company entfernen (nur wenn man der Ersteller ist).
     */
    public function detachCompany(CrmCompany $company): bool
    {
        $link = $this->companyLinks()
            ->where('company_id', $company->id)
            ->first();

        if ($link && $link->created_by_user_id === auth()->id()) {
            return $link->delete();
        }

        return false;
    }

    /**
     * Alle Companies entfernen (nur eigene).
     */
    public function detachAllCompanies(): int
    {
        return $this->companyLinks()
            ->where('created_by_user_id', auth()->id())
            ->delete();
    }

    /**
     * Companies synchronisieren (alle entfernen und neue hinzufügen).
     */
    public function syncCompanies(Collection|array $companies): void
    {
        $this->detachAllCompanies();
        $this->attachCompanies($companies);
    }

    /**
     * Prüfen, ob eine bestimmte Company verlinkt ist.
     */
    public function hasCompany(CrmCompany $company): bool
    {
        return $this->companyLinks()
            ->where('company_id', $company->id)
            ->exists();
    }

    /**
     * Anzahl der verlinkten Companies.
     */
    public function companiesCount(): int
    {
        return $this->companies()->count();
    }
}
