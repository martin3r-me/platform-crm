<?php

namespace Platform\Crm\Contracts;

interface CompanyInterface
{
    /**
     * Eindeutige ID des Unternehmens
     */
    public function getCompanyId(): int;
    
    /**
     * Name des Unternehmens
     */
    public function getName(): string;
    
    /**
     * Rechtlicher Name
     */
    public function getLegalName(): ?string;
    
    /**
     * Handelsname
     */
    public function getTradingName(): ?string;
    
    /**
     * Anzeigename (Trading Name oder Legal Name oder Name)
     */
    public function getDisplayName(): string;
    
    /**
     * Vollständiger Name
     */
    public function getFullName(): string;
    
    /**
     * Handelsregisternummer
     */
    public function getRegistrationNumber(): ?string;
    
    /**
     * Steuernummer
     */
    public function getTaxNumber(): ?string;
    
    /**
     * USt-IdNr.
     */
    public function getVatNumber(): ?string;
    
    /**
     * Website
     */
    public function getWebsite(): ?string;
    
    /**
     * E-Mail-Adressen
     */
    public function getEmailAddresses(): array;
    
    /**
     * Telefonnummern
     */
    public function getPhoneNumbers(): array;
    
    /**
     * Adressen
     */
    public function getPostalAddresses(): array;
    
    /**
     * Team-ID für den Kontext
     */
    public function getTeamId(): int;
    
    /**
     * Ist das Unternehmen aktiv?
     */
    public function isActive(): bool;
    
    /**
     * Anzahl verknüpfter Entitäten eines bestimmten Typs
     */
    public function getLinkedEntitiesCount(string $entityType): int;
    
    /**
     * Prüft ob das Unternehmen mit einer bestimmten Entität verknüpft ist
     */
    public function hasLinkedEntity(string $entityType, int $entityId): bool;
    
    /**
     * Verknüpft das Unternehmen mit einer Entität
     */
    public function linkEntity(string $entityType, int $entityId, array $additionalData = []): void;
    
    /**
     * Entfernt die Verknüpfung zu einer Entität
     */
    public function unlinkEntity(string $entityType, int $entityId): void;
}

