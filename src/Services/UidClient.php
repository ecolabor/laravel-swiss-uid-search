<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Services;

use Illuminate\Support\Facades\Cache;
use SoapClient;
use SoapFault;
use Ecolabor\SwissUid\Contracts\UidClientInterface;
use Ecolabor\SwissUid\Data\UidEntity;
use Ecolabor\SwissUid\Data\UidSearchResult;
use Ecolabor\SwissUid\Exceptions\UidApiException;

/**
 * UID Webservice Client Version 5.0
 *
 * Based on the official BFS documentation:
 * https://www.bfs.admin.ch/bfs/de/home/register/unternehmensregister/unternehmens-identifikationsnummer.html
 *
 * @see https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master
 */
class UidClient implements UidClientInterface
{
    protected ?SoapClient $publicClient = null;

    protected ?SoapClient $partnerClient = null;

    /**
     * Get entity information by UID number.
     *
     * Uses the Public Services GetByUID operation.
     *
     * @throws UidApiException
     */
    public function getByUid(string $uid): ?UidEntity
    {
        $normalizedUid = $this->normalizeUid($uid);

        if (strlen($normalizedUid) !== 9) {
            throw new UidApiException('Invalid UID format. UID must contain exactly 9 digits.');
        }

        $cacheKey = $this->getCacheKey('uid', $normalizedUid);

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = $this->getPublicClient()->GetByUID([
                'uid' => [
                    'uidOrganisationIdCategorie' => 'CHE',
                    'uidOrganisationId' => (int) $normalizedUid,
                ],
            ]);

            if (! isset($response->GetByUIDResult)) {
                return null;
            }

            $result = $response->GetByUIDResult;

            // API v5.0 structure: GetByUIDResult.organisationType.organisation
            if (isset($result->organisationType->organisation)) {
                $entity = UidEntity::fromApiResponse($result->organisationType);

                if ($this->isCacheEnabled()) {
                    Cache::put($cacheKey, $entity, $this->getCacheTtl());
                }

                return $entity;
            }

            // Fallback: uidEntitySearchResultItem (alternative structure)
            if (isset($result->uidEntitySearchResultItem)) {
                $item = is_array($result->uidEntitySearchResultItem)
                    ? $result->uidEntitySearchResultItem[0]
                    : $result->uidEntitySearchResultItem;

                $entity = UidEntity::fromApiResponse($item);

                if ($this->isCacheEnabled()) {
                    Cache::put($cacheKey, $entity, $this->getCacheTtl());
                }

                return $entity;
            }

            return null;
        } catch (SoapFault $e) {
            throw new UidApiException('SOAP request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search for entities based on various criteria.
     *
     * Uses the Public Services Search operation.
     */
    public function search(array $criteria): UidSearchResult
    {
        $cacheKey = $this->getCacheKey('search', md5(serialize($criteria)));

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $searchParams = $this->buildSearchParameters($criteria);
            $response = $this->getPublicClient()->Search($searchParams);

            if (! isset($response->SearchResult)) {
                return UidSearchResult::empty();
            }

            $result = UidSearchResult::fromApiResponse($response->SearchResult);

            if ($this->isCacheEnabled() && ! $result->hasError()) {
                Cache::put($cacheKey, $result, $this->getCacheTtl());
            }

            return $result;
        } catch (SoapFault $e) {
            return UidSearchResult::error('SOAP request failed: ' . $e->getMessage());
        }
    }

    /**
     * Search by company name.
     */
    public function searchByName(string $name, int $maxResults = 100): UidSearchResult
    {
        return $this->search([
            'organisationName' => $name,
            'maxNumberOfRecords' => $maxResults,
        ]);
    }

    /**
     * Search by location (city/town).
     */
    public function searchByLocation(string $town, ?string $name = null, int $maxResults = 100): UidSearchResult
    {
        $criteria = [
            'town' => $town,
            'maxNumberOfRecords' => $maxResults,
        ];

        if ($name) {
            $criteria['organisationName'] = $name;
        }

        return $this->search($criteria);
    }

    /**
     * Validate if a UID number exists and is valid.
     *
     * Uses the Public Services ValidateUID operation.
     */
    public function validateUid(string $uid): bool
    {
        $normalizedUid = $this->normalizeUid($uid);

        if (strlen($normalizedUid) !== 9) {
            return false;
        }

        try {
            $response = $this->getPublicClient()->ValidateUID([
                'uid' => [
                    'uidOrganisationIdCategorie' => 'CHE',
                    'uidOrganisationId' => (int) $normalizedUid,
                ],
            ]);

            return isset($response->ValidateUIDResult) && $response->ValidateUIDResult === true;
        } catch (SoapFault $e) {
            return false;
        }
    }

    /**
     * Validate a VAT (MWST) number.
     *
     * Uses the Public Services ValidateVatNumber operation.
     */
    public function validateVatNumber(string $vatNumber): bool
    {
        $normalized = $this->normalizeUid($vatNumber);

        if (strlen($normalized) !== 9) {
            return false;
        }

        try {
            $response = $this->getPublicClient()->ValidateVatNumber([
                'vatNumber' => [
                    'uidOrganisationIdCategorie' => 'CHE',
                    'uidOrganisationId' => (int) $normalized,
                ],
            ]);

            return isset($response->ValidateVatNumberResult) && $response->ValidateVatNumberResult === true;
        } catch (SoapFault $e) {
            return false;
        }
    }

    /**
     * Format a UID number to the standard format (CHE-XXX.XXX.XXX).
     */
    public function formatUid(string $uid): string
    {
        $normalized = $this->normalizeUid($uid);

        if (strlen($normalized) !== 9) {
            return $uid;
        }

        return sprintf(
            'CHE-%s.%s.%s',
            substr($normalized, 0, 3),
            substr($normalized, 3, 3),
            substr($normalized, 6, 3)
        );
    }

    /**
     * Format a UID as MWST number (CHE-XXX.XXX.XXX MWST).
     */
    public function formatMwst(string $uid): string
    {
        return $this->formatUid($uid) . ' MWST';
    }

    /**
     * Normalize a UID number (remove formatting, keep only digits).
     */
    public function normalizeUid(string $uid): string
    {
        return preg_replace('/[^0-9]/', '', $uid) ?? $uid;
    }

    /**
     * Get the SOAP client for Public Services.
     */
    protected function getPublicClient(): SoapClient
    {
        if ($this->publicClient === null) {
            $environment = config('swiss-uid.environment', 'production');
            $wsdlUrl = config("swiss-uid.public_wsdl.{$environment}");

            $this->publicClient = new SoapClient($wsdlUrl, $this->getSoapOptions());
        }

        return $this->publicClient;
    }

    /**
     * Get the SOAP client for Partner Services.
     */
    protected function getPartnerClient(): SoapClient
    {
        if ($this->partnerClient === null) {
            $environment = config('swiss-uid.environment', 'production');
            $wsdlUrl = config("swiss-uid.partner_wsdl.{$environment}");

            $options = array_merge($this->getSoapOptions(), [
                'login' => config('swiss-uid.partner_auth.username'),
                'password' => config('swiss-uid.partner_auth.password'),
            ]);

            $this->partnerClient = new SoapClient($wsdlUrl, $options);
        }

        return $this->partnerClient;
    }

    /**
     * Get SOAP client options.
     */
    protected function getSoapOptions(): array
    {
        return array_filter(
            config('swiss-uid.soap_options', []),
            fn ($value) => $value !== null
        );
    }

    /**
     * Build search parameters for the API call.
     *
     * Uses stdClass objects for proper SOAP serialization.
     * Based on uidEntityPublicSearchParameters from the WSDL.
     */
    protected function buildSearchParameters(array $criteria): \stdClass
    {
        $params = new \stdClass();

        // Search parameters wrapper
        $params->searchParameters = new \stdClass();
        $params->searchParameters->uidEntitySearchParameters = new \stdClass();

        // Organisation name
        if (isset($criteria['organisationName']) || isset($criteria['name'])) {
            $params->searchParameters->uidEntitySearchParameters->organisationName =
                $criteria['organisationName'] ?? $criteria['name'];
        }

        // Address parameters - wrapped in address object
        if (isset($criteria['town']) || isset($criteria['city']) ||
            isset($criteria['street']) || isset($criteria['swissZipCode']) ||
            isset($criteria['zipCode']) || isset($criteria['cantonAbbreviation']) ||
            isset($criteria['canton'])) {

            $params->searchParameters->uidEntitySearchParameters->address = new \stdClass();

            if (isset($criteria['town']) || isset($criteria['city'])) {
                $params->searchParameters->uidEntitySearchParameters->address->town =
                    $criteria['town'] ?? $criteria['city'];
            }

            if (isset($criteria['street'])) {
                $params->searchParameters->uidEntitySearchParameters->address->street = $criteria['street'];
            }

            if (isset($criteria['swissZipCode']) || isset($criteria['zipCode'])) {
                $params->searchParameters->uidEntitySearchParameters->address->swissZipCode =
                    $criteria['swissZipCode'] ?? $criteria['zipCode'];
            }

            if (isset($criteria['cantonAbbreviation']) || isset($criteria['canton'])) {
                $params->searchParameters->uidEntitySearchParameters->address->cantonAbbreviation =
                    $criteria['cantonAbbreviation'] ?? $criteria['canton'];
            }
        }

        // Search configuration (required)
        $params->config = new \stdClass();

        // Search mode: Auto, Exact, Wild (must be capitalized)
        $searchMode = $criteria['searchMode'] ?? config('swiss-uid.search.search_mode', 'auto');
        $params->config->searchMode = ucfirst(strtolower($searchMode));

        // Max records
        $params->config->maxNumberOfRecords = $criteria['maxNumberOfRecords']
            ?? $criteria['limit']
            ?? config('swiss-uid.search.max_results', 100);

        // Search history (required)
        $params->config->searchNameAndAddressHistory = $criteria['searchHistory'] ?? false;

        return $params;
    }

    /**
     * Generate a cache key.
     */
    protected function getCacheKey(string $type, string $identifier): string
    {
        $prefix = config('swiss-uid.cache.prefix', 'swiss_uid_');

        return $prefix . $type . '_' . $identifier;
    }

    /**
     * Check if caching is enabled.
     */
    protected function isCacheEnabled(): bool
    {
        return config('swiss-uid.cache.enabled', true);
    }

    /**
     * Get the cache TTL in seconds.
     */
    protected function getCacheTtl(): int
    {
        return config('swiss-uid.cache.ttl', 3600);
    }
}
