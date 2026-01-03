<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * UID Search Result Data Transfer Object
 *
 * Based on uidEntitySearchResponse from the BFS documentation.
 *
 * @see https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master
 */
class UidSearchResult implements Arrayable, JsonSerializable
{
    /**
     * @param  Collection<int, UidEntity>  $entities
     */
    public function __construct(
        public readonly Collection $entities,
        public readonly int $totalCount,
        public readonly bool $hasMoreResults = false,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
    ) {}

    /**
     * Create from API response (uidEntitySearchResponse).
     */
    public static function fromApiResponse(object $response): self
    {
        $entities = collect();
        $totalCount = 0;
        $hasMoreResults = false;
        $errorMessage = null;
        $errorCode = null;

        // Check for errors first
        if (isset($response->exception)) {
            $errorMessage = $response->exception->message ?? 'Unknown error';
            $errorCode = $response->exception->code ?? null;

            return new self(
                entities: collect(),
                totalCount: 0,
                hasMoreResults: false,
                errorMessage: $errorMessage,
                errorCode: $errorCode,
            );
        }

        // Parse search results
        if (isset($response->uidEntitySearchResultItem)) {
            $results = is_array($response->uidEntitySearchResultItem)
                ? $response->uidEntitySearchResultItem
                : [$response->uidEntitySearchResultItem];

            foreach ($results as $result) {
                try {
                    $entities->push(UidEntity::fromApiResponse($result));
                } catch (\Exception $e) {
                    // Skip malformed entries
                    continue;
                }
            }

            $totalCount = count($results);
        }

        // Check for total result count (when results are truncated)
        if (isset($response->totalResultCount)) {
            $totalCount = (int) $response->totalResultCount;
            $hasMoreResults = $entities->count() < $totalCount;
        }

        return new self(
            entities: $entities,
            totalCount: $totalCount,
            hasMoreResults: $hasMoreResults,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
        );
    }

    /**
     * Create an empty result.
     */
    public static function empty(): self
    {
        return new self(
            entities: collect(),
            totalCount: 0,
            hasMoreResults: false,
        );
    }

    /**
     * Create an error result.
     */
    public static function error(string $message, ?string $code = null): self
    {
        return new self(
            entities: collect(),
            totalCount: 0,
            hasMoreResults: false,
            errorMessage: $message,
            errorCode: $code,
        );
    }

    public function toArray(): array
    {
        return [
            'entities' => $this->entities->toArray(),
            'total_count' => $this->totalCount,
            'has_more_results' => $this->hasMoreResults,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Check if result set is empty.
     */
    public function isEmpty(): bool
    {
        return $this->entities->isEmpty();
    }

    /**
     * Check if result set is not empty.
     */
    public function isNotEmpty(): bool
    {
        return $this->entities->isNotEmpty();
    }

    /**
     * Check if there was an error.
     */
    public function hasError(): bool
    {
        return $this->errorMessage !== null;
    }

    /**
     * Get the first entity or null.
     */
    public function first(): ?UidEntity
    {
        return $this->entities->first();
    }

    /**
     * Get entity count.
     */
    public function count(): int
    {
        return $this->entities->count();
    }

    /**
     * Filter entities by active status.
     */
    public function onlyActive(): self
    {
        return new self(
            entities: $this->entities->filter(fn (UidEntity $e) => $e->isActive()),
            totalCount: $this->totalCount,
            hasMoreResults: $this->hasMoreResults,
            errorMessage: $this->errorMessage,
            errorCode: $this->errorCode,
        );
    }

    /**
     * Filter entities by VAT registration.
     */
    public function onlyVatRegistered(): self
    {
        return new self(
            entities: $this->entities->filter(fn (UidEntity $e) => $e->isVatRegistered()),
            totalCount: $this->totalCount,
            hasMoreResults: $this->hasMoreResults,
            errorMessage: $this->errorMessage,
            errorCode: $this->errorCode,
        );
    }

    /**
     * Filter entities by commercial register entry.
     */
    public function onlyInCommercialRegister(): self
    {
        return new self(
            entities: $this->entities->filter(fn (UidEntity $e) => $e->isInCommercialRegister()),
            totalCount: $this->totalCount,
            hasMoreResults: $this->hasMoreResults,
            errorMessage: $this->errorMessage,
            errorCode: $this->errorCode,
        );
    }

    /**
     * Map entities to simple array for dropdowns.
     */
    public function toSelectOptions(): array
    {
        return $this->entities
            ->mapWithKeys(fn (UidEntity $e) => [$e->uid => $e->getFullName() . ' (' . $e->uidFormatted . ')'])
            ->toArray();
    }
}
