<?php

namespace Platform\Crm\Contracts;

interface ContactInterface
{
    /**
     * Eindeutige ID des Kontakts
     */
    public function getContactId(): int;
    
    /**
     * Vorname
     */
    public function getFirstName(): string;
    
    /**
     * Nachname
     */
    public function getLastName(): string;
    
    /**
     * Vollständiger Name
     */
    public function getFullName(): string;
    
    /**
     * Anzeigename (Nickname oder Vor-/Nachname)
     */
    public function getDisplayName(): string;
    
    /**
     * Geburtsdatum
     */
    public function getBirthDate(): ?\Carbon\Carbon;
    
    /**
     * Alter
     */
    public function getAge(): ?int;
    
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
     * Ist der Kontakt aktiv?
     */
    public function isActive(): bool;
}
