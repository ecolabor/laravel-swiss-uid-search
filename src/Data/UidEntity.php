<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * UID Entity Data Transfer Object
 *
 * Based on eCH-0108 standard and uidEntitySearchResultItem from the BFS documentation.
 *
 * @see https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master
 */
class UidEntity implements Arrayable, JsonSerializable
{
    public function __construct(
        // UID Information
        public readonly string $uid,
        public readonly string $uidFormatted,
        public readonly string $uidCategory,

        // Organisation Information (eCH-0097/eCH-0098)
        public readonly ?string $organisationName,
        public readonly ?string $organisationAdditionalName,
        public readonly ?int $legalFormId,
        public readonly ?string $legalFormText,

        // UID Register Information (eCH-0108)
        public readonly ?int $uidregStatusId,
        public readonly ?string $uidregStatusText,
        public readonly ?bool $uidregStatusEnterpriseActive,
        public readonly ?\DateTimeInterface $uidregPublicStatus,
        public readonly ?\DateTimeInterface $uidregOrganisationTypeFirstDate,
        public readonly ?\DateTimeInterface $uidregLiquidationDate,

        // Address (eCH-0010)
        public readonly ?Address $address,

        // Commercial Register (HR) Information
        public readonly ?string $commercialRegisterStatus,
        public readonly ?\DateTimeInterface $commercialRegisterEntryDate,
        public readonly ?\DateTimeInterface $commercialRegisterLiquidationDate,
        public readonly ?string $commercialRegisterNameTranslation,

        // VAT (MWST) Register Information
        public readonly ?string $vatNumber,
        public readonly ?string $vatNumberFormatted,
        public readonly ?string $vatStatus,
        public readonly ?\DateTimeInterface $vatEntryDate,
        public readonly ?\DateTimeInterface $vatLiquidationDate,

        // Other identifiers
        public readonly ?string $cantonAbbreviation,
        public readonly ?string $municipalityId,
        public readonly ?string $chId, // Commercial Register ID (CH-XXX.X.XXX.XXX-X)

        // Raw API response for advanced usage
        public readonly array $rawData = [],
    ) {}

    /**
     * Create from API response.
     *
     * Supports both structures:
     * - v5.0 Search: uidEntitySearchResultItem.organisation.organisation.organisationIdentification
     *   - organisation.organisation contains: organisationIdentification, address
     *   - organisation contains: uidregInformation, commercialRegisterInformation, vatRegisterInformation
     * - v5.0 GetByUID: organisationType.organisation.organisationIdentification
     * - Legacy: uidEntitySearchResultItem.organisation
     */
    public static function fromApiResponse(object $response): self
    {
        // API v5.0 Search structure: response.organisation.organisation.organisationIdentification
        // parentOrg = response.organisation (contains uidregInformation, etc.)
        // org = response.organisation.organisation (contains organisationIdentification, address)
        $parentOrg = $response->organisation ?? $response;
        $org = $parentOrg;

        // Check for double-nested organisation (Search result structure)
        if (isset($parentOrg->organisation)) {
            $org = $parentOrg->organisation;
        }

        $orgId = $org->organisationIdentification ?? $org;

        // Extract UID
        $uidStruct = $orgId->uid ?? null;
        $uidCategory = $uidStruct->uidOrganisationIdCategorie ?? 'CHE';
        $uidNumber = (string) ($uidStruct->uidOrganisationId ?? '');

        // Address - can be array or object
        $address = null;
        if (isset($org->address)) {
            $addrData = $org->address;
            
            // Handle array of addresses (take first LEGAL address or first one)
            if (is_array($addrData)) {
                $addr = null;
                foreach ($addrData as $a) {
                    if (($a->addressCategory ?? '') === 'LEGAL') {
                        $addr = $a;
                        break;
                    }
                }
                $addr = $addr ?? $addrData[0] ?? null;
            } else {
                $addr = $addrData;
            }
            
            if ($addr) {
                // swissZipCode can be an array in the API response
                $zipCode = $addr->swissZipCode ?? null;
                if (is_array($zipCode)) {
                    $zipCode = $zipCode[0] ?? null;
                }

                $zipCodeAddOn = $addr->swissZipCodeAddOn ?? null;
                if (is_array($zipCodeAddOn)) {
                    $zipCodeAddOn = $zipCodeAddOn[0] ?? null;
                }

                $canton = $addr->cantonAbbreviation ?? null;
                if (is_array($canton)) {
                    $canton = $canton[0] ?? null;
                }

                $address = new Address(
                    street: $addr->street ?? null,
                    houseNumber: $addr->houseNumber ?? null,
                    addressSupplement: $addr->addressSupplement ?? null,
                    postOfficeBoxNumber: isset($addr->postOfficeBoxNumber) ? (string) $addr->postOfficeBoxNumber : null,
                    postOfficeBoxText: $addr->postOfficeBoxText ?? null,
                    locality: $addr->locality ?? null,
                    swissZipCode: $zipCode !== null ? (string) $zipCode : null,
                    swissZipCodeAddOn: $zipCodeAddOn !== null ? (string) $zipCodeAddOn : null,
                    swissZipCodeId: isset($addr->swissZipCodeId) ? (int) $addr->swissZipCodeId : null,
                    town: $addr->town ?? null,
                    countryIdISO2: $addr->country->countryIdISO2 ?? $addr->countryIdISO2 ?? 'CH',
                    countryName: $addr->country->countryNameShort ?? null,
                    cantonAbbreviation: $canton,
                );
            }
        }

        // UID Register Information - on parentOrg level
        $uidregInfo = $parentOrg->uidregInformation ?? null;
        $uidregStatusId = isset($uidregInfo->uidregStatusEnterpriseDetail)
            ? (int) $uidregInfo->uidregStatusEnterpriseDetail
            : (isset($uidregInfo->uidregStatusEnterpriseId) ? (int) $uidregInfo->uidregStatusEnterpriseId : null);

        // Commercial Register Information - on parentOrg level
        $hrInfo = $parentOrg->commercialRegisterInformation ?? null;

        // VAT Register Information - on parentOrg level
        $vatInfo = $parentOrg->vatRegisterInformation ?? null;
        $vatUid = $vatInfo->uidVat ?? null;
        $vatNumber = $vatUid ? (string) ($vatUid->uidOrganisationId ?? '') : null;

        return new self(
            uid: $uidNumber,
            uidFormatted: self::formatUidStatic($uidNumber),
            uidCategory: $uidCategory,

            organisationName: $orgId->organisationName ?? null,
            organisationAdditionalName: $orgId->organisationAdditionalName ?? null,
            legalFormId: isset($orgId->legalForm) ? (int) $orgId->legalForm : null,
            legalFormText: $uidregInfo->uidregLegalForm ?? self::getLegalFormText(isset($orgId->legalForm) ? (int) $orgId->legalForm : null),

            uidregStatusId: $uidregStatusId,
            uidregStatusText: self::getStatusText($uidregStatusId),
            uidregStatusEnterpriseActive: isset($uidregInfo->uidregStatusEnterpriseActive)
                ? (bool) $uidregInfo->uidregStatusEnterpriseActive
                : null,
            uidregPublicStatus: self::parseDate($uidregInfo->uidregPublicStatus ?? null),
            uidregOrganisationTypeFirstDate: self::parseDate($uidregInfo->uidregOrganisationTypeFirstDate ?? null),
            uidregLiquidationDate: self::parseDate($uidregInfo->uidregLiquidationDate ?? null),

            address: $address,

            commercialRegisterStatus: $hrInfo->commercialRegisterEntryStatus ?? null,
            commercialRegisterEntryDate: self::parseDate($hrInfo->commercialRegisterEntryDate ?? null),
            commercialRegisterLiquidationDate: self::parseDate($hrInfo->commercialRegisterLiquidationDate ?? null),
            commercialRegisterNameTranslation: $hrInfo->commercialRegisterNameTranslation ?? null,

            vatNumber: $vatNumber,
            vatNumberFormatted: $vatNumber ? self::formatUidStatic($vatNumber) . ' MWST' : null,
            vatStatus: $vatInfo->vatEntryStatus ?? null,
            vatEntryDate: self::parseDate($vatInfo->vatEntryDate ?? null),
            vatLiquidationDate: self::parseDate($vatInfo->vatLiquidationDate ?? null),

            cantonAbbreviation: $uidregInfo->cantonAbbreviation ?? $address?->extractCanton(),
            municipalityId: isset($uidregInfo->municipalityId) ? (string) $uidregInfo->municipalityId : null,
            chId: self::extractChId($orgId),

            rawData: (array) $response,
        );
    }

    private static function formatUidStatic(string $uid): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $uid) ?? $uid;

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

    private static function parseDate(mixed $value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            return null;
        }
    }

    private static function extractChId(object $orgId): ?string
    {
        // Look for CH-ID in OtherOrganisationId
        if (isset($orgId->OtherOrganisationId)) {
            $others = is_array($orgId->OtherOrganisationId)
                ? $orgId->OtherOrganisationId
                : [$orgId->OtherOrganisationId];

            foreach ($others as $other) {
                if (($other->organisationIdCategory ?? '') === 'CH.HR') {
                    return $other->organisationId ?? null;
                }
            }
        }

        return null;
    }

    private static function getLegalFormText(?int $legalFormId): ?string
    {
        if ($legalFormId === null) {
            return null;
        }

        // Swiss legal forms according to eCH-0097/UID API codes
        // Reference: BFS UID Webservice documentation
        $forms = [
            // Private law legal forms
            101 => 'Einzelunternehmen',
            102 => 'Einfache Gesellschaft',
            103 => 'Kollektivgesellschaft',
            104 => 'Kommanditgesellschaft',
            105 => 'Kommanditaktiengesellschaft',
            106 => 'Aktiengesellschaft',
            107 => 'GmbH',
            108 => 'Genossenschaft',
            109 => 'Verein',
            110 => 'Stiftung',

            // Public law entities
            151 => 'Institut des öffentlichen Rechts',
            152 => 'Körperschaft des öffentlichen Rechts',
            153 => 'Anstalt des öffentlichen Rechts',
            154 => 'Gemeinde',

            // Branches and special forms
            220 => 'Zweigniederlassung CH',
            221 => 'Zweigniederlassung Ausland',

            // Investment entities
            312 => 'Investmentgesellschaft mit variablem Kapital (SICAV)',
            313 => 'Investmentgesellschaft mit festem Kapital (SICAF)',
            314 => 'Kommanditgesellschaft für kollektive Kapitalanlagen',

            // Other
            327 => 'Prokuristen/Handlungsbevollmächtigte',
            441 => 'Europäische Gesellschaft (SE)',
            442 => 'Europäische Genossenschaft (SCE)',
        ];

        return $forms[$legalFormId] ?? null;
    }

    private static function getStatusText(?int $statusId): ?string
    {
        if ($statusId === null) {
            return null;
        }

        $statuses = [
            1 => 'Aktiv',
            2 => 'Gelöscht',
            3 => 'Annulliert',
            4 => 'In Auflösung',
            5 => 'In Konkurs',
        ];

        return $statuses[$statusId] ?? null;
    }

    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'uid_formatted' => $this->uidFormatted,
            'uid_category' => $this->uidCategory,

            'name' => $this->organisationName,
            'additional_name' => $this->organisationAdditionalName,
            'legal_form_id' => $this->legalFormId,
            'legal_form' => $this->legalFormText,

            'status_id' => $this->uidregStatusId,
            'status' => $this->uidregStatusText,
            'is_active' => $this->uidregStatusEnterpriseActive,
            'liquidation_date' => $this->uidregLiquidationDate?->format('Y-m-d'),

            'address' => $this->address?->toArray(),

            'commercial_register_status' => $this->commercialRegisterStatus,
            'commercial_register_entry_date' => $this->commercialRegisterEntryDate?->format('Y-m-d'),

            'vat_number' => $this->vatNumber,
            'vat_number_formatted' => $this->vatNumberFormatted,
            'vat_status' => $this->vatStatus,
            'vat_entry_date' => $this->vatEntryDate?->format('Y-m-d'),

            'canton' => $this->cantonAbbreviation,
            'municipality_id' => $this->municipalityId,
            'ch_id' => $this->chId,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getFullName(): string
    {
        if ($this->organisationAdditionalName) {
            return trim($this->organisationName . ' ' . $this->organisationAdditionalName);
        }

        return $this->organisationName ?? '';
    }

    public function isActive(): bool
    {
        return $this->uidregStatusEnterpriseActive === true || $this->uidregStatusId === 1;
    }

    public function isVatRegistered(): bool
    {
        return $this->vatNumber !== null && $this->vatStatus !== 'liquidated';
    }

    public function isInCommercialRegister(): bool
    {
        return $this->chId !== null || $this->commercialRegisterStatus !== null;
    }
}
