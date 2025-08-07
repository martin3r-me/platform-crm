<?php

namespace Platform\Crm\Contracts;

interface ContactLinkableInterface
{
    /**
     * Eindeutige ID des Objekts
     */
    public function getContactLinkableId(): int;

    /**
     * Typ des Objekts (z.B. 'CommsChannelEmailThread')
     */
    public function getContactLinkableType(): string;

    /**
     * E-Mail-Adressen, die für automatisches Verlinken verwendet werden sollen
     */
    public function getEmailAddresses(): array;

    /**
     * Team-ID für den Kontext
     */
    public function getTeamId(): int;
} 