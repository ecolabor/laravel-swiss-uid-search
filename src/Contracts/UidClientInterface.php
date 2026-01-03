<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Contracts;

use Ecolabor\SwissUid\Data\UidEntity;
use Ecolabor\SwissUid\Data\UidSearchResult;

/**
 * UID Webservice Client Interface
 *
 * Based on the BFS UID Webservice Version 5.0 Public Services.
 *
 * @see https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master
 */
interface UidClientInterface
{
    /**
     * Get entity information by UID number.
     *
     * Corresponds to: GetByUID operation
     */
    public function getByUid(string $uid): ?UidEntity;

    /**
     * Search for entities based on various criteria.
     *
     * Corresponds to: Search operation
     *
     * Available criteria:
     * - organisationName: Company name
     * - town/city: Location
     * - street: Street name
     * - swissZipCode/zipCode: Postal code
     * - cantonAbbreviation/canton: Canton (e.g., "ZH")
     * - legalFormId: Legal form ID
     * - uidregStatusEnterpriseActive: Only active entities (default: true)
     * - maxNumberOfRecords/limit: Max results (default: 100)
     * - searchMode: auto, exact, wild (default: auto)
     */
    public function search(array $criteria): UidSearchResult;

    /**
     * Search by company name.
     */
    public function searchByName(string $name, int $maxResults = 100): UidSearchResult;

    /**
     * Search by location (city/town).
     */
    public function searchByLocation(string $town, ?string $name = null, int $maxResults = 100): UidSearchResult;

    /**
     * Validate if a UID number exists and is valid.
     *
     * Corresponds to: ValidateUID operation
     */
    public function validateUid(string $uid): bool;

    /**
     * Validate a VAT (MWST) number.
     *
     * Corresponds to: ValidateVatNumber operation
     */
    public function validateVatNumber(string $vatNumber): bool;

    /**
     * Format a UID number to the standard format (CHE-XXX.XXX.XXX).
     */
    public function formatUid(string $uid): string;

    /**
     * Format a UID as MWST number (CHE-XXX.XXX.XXX MWST).
     */
    public function formatMwst(string $uid): string;

    /**
     * Normalize a UID number (remove formatting, keep only digits).
     */
    public function normalizeUid(string $uid): string;
}
