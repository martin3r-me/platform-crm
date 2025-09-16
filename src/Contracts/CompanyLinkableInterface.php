<?php

namespace Platform\Crm\Contracts;

interface CompanyLinkableInterface
{
    /**
     * Eindeutige ID des Objekts
     */
    public function getCompanyLinkableId(): int;

    /**
     * Typ des Objekts (z.B. 'HcmEmployer')
     */
    public function getCompanyLinkableType(): string;

    /**
     * Company-Identifikatoren, die für automatisches Verlinken verwendet werden sollen
     */
    public function getCompanyIdentifiers(): array;

    /**
     * Team-ID für den Kontext
     */
    public function getTeamId(): int;
}
