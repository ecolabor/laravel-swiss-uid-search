<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Address Data Transfer Object
 *
 * Based on eCH-0010 Swiss address standard.
 *
 * @see https://www.ech.ch/de/ech/ech-0010
 */
class Address implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly ?string $street,
        public readonly ?string $houseNumber,
        public readonly ?string $addressSupplement,
        public readonly ?string $postOfficeBoxNumber,
        public readonly ?string $postOfficeBoxText,
        public readonly ?string $locality,
        public readonly ?string $swissZipCode,
        public readonly ?string $swissZipCodeAddOn,
        public readonly ?int $swissZipCodeId,
        public readonly ?string $town,
        public readonly string $countryIdISO2 = 'CH',
        public readonly ?string $countryName = null,
        public readonly ?string $cantonAbbreviation = null,
    ) {}

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'house_number' => $this->houseNumber,
            'address_supplement' => $this->addressSupplement,
            'post_office_box_number' => $this->postOfficeBoxNumber,
            'post_office_box_text' => $this->postOfficeBoxText,
            'locality' => $this->locality,
            'zip_code' => $this->swissZipCode,
            'zip_code_add_on' => $this->swissZipCodeAddOn,
            'zip_code_id' => $this->swissZipCodeId,
            'town' => $this->town,
            'canton' => $this->cantonAbbreviation ?? $this->extractCanton(),
            'country' => $this->countryIdISO2,
            'country_name' => $this->countryName,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get street line (street + house number).
     */
    public function getStreetLine(): string
    {
        $parts = array_filter([
            $this->street,
            $this->houseNumber,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get city line (zip code + town).
     */
    public function getCityLine(): string
    {
        $parts = array_filter([
            $this->swissZipCode,
            $this->town,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get full address as multi-line string.
     */
    public function getFullAddress(): string
    {
        $lines = array_filter([
            $this->getStreetLine(),
            $this->addressSupplement,
            $this->locality,
            $this->getPostOfficeBoxLine(),
            $this->getCityLine(),
            $this->countryIdISO2 !== 'CH' ? ($this->countryName ?? $this->countryIdISO2) : null,
        ]);

        return implode("\n", $lines);
    }

    /**
     * Get address as single line.
     */
    public function getOneLiner(): string
    {
        $parts = array_filter([
            $this->getStreetLine(),
            $this->getCityLine(),
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get post office box line.
     */
    public function getPostOfficeBoxLine(): ?string
    {
        if ($this->postOfficeBoxNumber === null && $this->postOfficeBoxText === null) {
            return null;
        }

        if ($this->postOfficeBoxText) {
            return $this->postOfficeBoxText . ' ' . $this->postOfficeBoxNumber;
        }

        return 'Postfach ' . $this->postOfficeBoxNumber;
    }

    /**
     * Check if this is a Swiss address.
     */
    public function isSwiss(): bool
    {
        return $this->countryIdISO2 === 'CH';
    }

    /**
     * Get canton abbreviation.
     *
     * Returns the canton abbreviation if available, otherwise estimates from zip code.
     */
    public function extractCanton(): ?string
    {
        // Return explicit canton if available
        if ($this->cantonAbbreviation !== null) {
            return $this->cantonAbbreviation;
        }

        if ($this->swissZipCode === null || strlen($this->swissZipCode) < 1) {
            return null;
        }

        // Swiss zip code regions (first digit) - fallback estimation
        $regions = [
            '1' => ['GE', 'VD', 'VS', 'FR', 'NE'], // Western Switzerland
            '2' => ['NE', 'JU', 'BE'], // Jura region
            '3' => ['BE'], // Bern
            '4' => ['BS', 'BL', 'SO', 'JU'], // Northwestern Switzerland
            '5' => ['AG', 'SO'], // Aarau region
            '6' => ['LU', 'NW', 'OW', 'SZ', 'UR', 'ZG'], // Central Switzerland
            '7' => ['GR'], // Graubünden
            '8' => ['ZH', 'SH', 'TG'], // Zurich region
            '9' => ['SG', 'AR', 'AI', 'TG'], // Eastern Switzerland
        ];

        $firstDigit = $this->swissZipCode[0];

        // This is a rough estimate - actual canton depends on full zip code
        return $regions[$firstDigit][0] ?? null;
    }

    /**
     * Format for HTML display.
     */
    public function toHtml(): string
    {
        return nl2br(e($this->getFullAddress()));
    }
}
