# Laravel Swiss UID Search

[![Tests](https://img.shields.io/github/actions/workflow/status/ecolabor/laravel-swiss-uid-search/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ecolabor/laravel-swiss-uid-search/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/ecolabor/laravel-swiss-uid-search.svg?style=flat-square)](https://packagist.org/packages/ecolabor/laravel-swiss-uid-search)
[![Total Downloads](https://img.shields.io/packagist/dt/ecolabor/laravel-swiss-uid-search.svg?style=flat-square)](https://packagist.org/packages/ecolabor/laravel-swiss-uid-search)
[![PHP Version](https://img.shields.io/packagist/php-v/ecolabor/laravel-swiss-uid-search.svg?style=flat-square)](https://packagist.org/packages/ecolabor/laravel-swiss-uid-search)
[![License](https://img.shields.io/packagist/l/ecolabor/laravel-swiss-uid-search.svg?style=flat-square)](https://packagist.org/packages/ecolabor/laravel-swiss-uid-search)

Ein Laravel-Package zur Integration des Schweizer UID-Webservices (Unternehmens-Identifikationsnummer) vom Bundesamt für Statistik (BFS).

**Basiert auf der offiziellen API Version 5.0**

📄 [Offizielle Dokumentation (PDF)](https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master)

## Features

- 🔍 Unternehmen per UID-Nummer abrufen (`GetByUID`)
- 🔎 Unternehmen nach Name, Ort, Kanton suchen (`Search`)
- ✅ UID-Nummern validieren (`ValidateUID`)
- 💰 MWST-Nummern validieren (`ValidateVatNumber`)
- 📦 Automatisches Caching für bessere Performance
- 🏷️ Laravel Validation Rule
- 💡 Einfache Facade für schnellen Zugriff

## Webservice Endpoints

| Umgebung | Public Services |
|----------|-----------------|
| **Produktion** | `https://www.uid-wse.admin.ch/V5.0/PublicServices.svc` |
| **Test** | `https://www.uid-wse-a.admin.ch/V5.0/PublicServices.svc` |

## Installation

```bash
composer require ecolabor/laravel-swiss-uid-search
```

## Konfiguration

Veröffentlichen Sie die Konfigurationsdatei:

```bash
php artisan vendor:publish --tag=swiss-uid-config
```

### Umgebungsvariablen

```env
# Umgebung: production oder test
SWISS_UID_ENVIRONMENT=production

# Sprache: 1=DE, 2=FR, 3=IT, 4=EN
SWISS_UID_LANGUAGE=1

# Cache-Einstellungen
SWISS_UID_CACHE_ENABLED=true
SWISS_UID_CACHE_TTL=3600

# Such-Einstellungen
SWISS_UID_MAX_RESULTS=100
SWISS_UID_SEARCH_MODE=auto

# Partner Services (optional, für erweiterte Funktionen)
SWISS_UID_PARTNER_USERNAME=your_username_sa
SWISS_UID_PARTNER_PASSWORD=your_password
```

## Verwendung

### Unternehmen per UID abrufen

```php
use Ecolabor\SwissUid\Facades\SwissUid;

// Mit formatierter UID
$company = SwissUid::getByUid('CHE-123.456.789');

// Oder nur mit Ziffern
$company = SwissUid::getByUid('123456789');

if ($company) {
    echo $company->organisationName;         // Firmenname
    echo $company->uidFormatted;             // CHE-123.456.789
    echo $company->address->getOneLiner();   // Strasse 1, 8000 Zürich
    echo $company->legalFormText;            // Aktiengesellschaft
    echo $company->vatNumberFormatted;       // CHE-123.456.789 MWST
    echo $company->isActive();               // true/false
    echo $company->isVatRegistered();        // true/false
    echo $company->isInCommercialRegister(); // true/false
}
```

### Nach Unternehmen suchen

```php
use Ecolabor\SwissUid\Facades\SwissUid;

// Nach Name suchen
$result = SwissUid::searchByName('Migros');

// Nach Ort suchen
$result = SwissUid::searchByLocation('Zürich');

// Kombinierte Suche mit allen Parametern
$result = SwissUid::search([
    'organisationName' => 'AG',
    'town' => 'Bern',
    'canton' => 'BE',
    'zipCode' => '3000',
    'legalFormId' => 6,                        // Aktiengesellschaft
    'uidregStatusEnterpriseActive' => true,    // Nur aktive
    'maxNumberOfRecords' => 50,
    'searchMode' => 'auto',                    // auto, exact, wild
]);

foreach ($result->entities as $company) {
    echo "{$company->organisationName} - {$company->uidFormatted}\n";
}

// Ergebnisse filtern
$activeOnly = $result->onlyActive();
$vatRegistered = $result->onlyVatRegistered();
$inHr = $result->onlyInCommercialRegister();

// Für Dropdowns
$options = $result->toSelectOptions();
// ['123456789' => 'Firma AG (CHE-123.456.789)', ...]
```

### UID validieren

```php
use Ecolabor\SwissUid\Facades\SwissUid;

// Prüfen ob UID gültig ist
if (SwissUid::validateUid('CHE-123.456.789')) {
    echo "UID ist gültig!";
}

// MWST-Nummer validieren
if (SwissUid::validateVatNumber('CHE-123.456.789')) {
    echo "MWST-Nummer ist gültig!";
}
```

### Validation Rule

```php
use Ecolabor\SwissUid\Rules\ValidSwissUid;

// Nur Format validieren (schnell, kein API-Call)
$request->validate([
    'uid' => ['required', ValidSwissUid::format()],
]);

// Format + Existenz im Register prüfen (API-Call)
$request->validate([
    'uid' => ['required', ValidSwissUid::exists()],
]);
```

### UID formatieren

```php
use Ecolabor\SwissUid\Facades\SwissUid;

$formatted = SwissUid::formatUid('123456789');
// Ergebnis: CHE-123.456.789

$mwst = SwissUid::formatMwst('123456789');
// Ergebnis: CHE-123.456.789 MWST

$normalized = SwissUid::normalizeUid('CHE-123.456.789');
// Ergebnis: 123456789
```

## UidEntity Eigenschaften

### Basis-Informationen

| Eigenschaft | Typ | Beschreibung |
|-------------|-----|--------------|
| `uid` | string | Normalisierte UID (nur Ziffern) |
| `uidFormatted` | string | Formatierte UID (CHE-XXX.XXX.XXX) |
| `uidCategory` | string | Kategorie (CHE) |
| `organisationName` | ?string | Firmenname |
| `organisationAdditionalName` | ?string | Zusätzlicher Name |
| `legalFormId` | ?int | Rechtsform-ID |
| `legalFormText` | ?string | Rechtsform-Text |

### Status-Informationen (UID-Register)

| Eigenschaft | Typ | Beschreibung |
|-------------|-----|--------------|
| `uidregStatusId` | ?int | Status-ID (1=Aktiv, 2=Gelöscht, etc.) |
| `uidregStatusText` | ?string | Status-Text |
| `uidregStatusEnterpriseActive` | ?bool | Unternehmen aktiv? |
| `uidregLiquidationDate` | ?DateTime | Löschdatum |

### MWST-Informationen

| Eigenschaft | Typ | Beschreibung |
|-------------|-----|--------------|
| `vatNumber` | ?string | MWST-Nummer (nur Ziffern) |
| `vatNumberFormatted` | ?string | Formatiert (CHE-XXX.XXX.XXX MWST) |
| `vatStatus` | ?string | MWST-Status |
| `vatEntryDate` | ?DateTime | MWST-Eintragsdatum |
| `vatLiquidationDate` | ?DateTime | MWST-Löschdatum |

### Handelsregister-Informationen

| Eigenschaft | Typ | Beschreibung |
|-------------|-----|--------------|
| `chId` | ?string | HR-Nummer (CH-XXX.X.XXX.XXX-X) |
| `commercialRegisterStatus` | ?string | HR-Status |
| `commercialRegisterEntryDate` | ?DateTime | HR-Eintragsdatum |

### Adresse

| Eigenschaft | Typ | Beschreibung |
|-------------|-----|--------------|
| `address` | ?Address | Adressobjekt (eCH-0010) |
| `cantonAbbreviation` | ?string | Kanton (z.B. "ZH") |
| `municipalityId` | ?string | Gemeinde-ID |

## Address Eigenschaften (eCH-0010)

```php
$address = $company->address;

$address->street;              // Strasse
$address->houseNumber;         // Hausnummer
$address->swissZipCode;        // PLZ
$address->town;                // Ort
$address->countryIdISO2;       // Land (CH)

// Hilfsmethoden
$address->getStreetLine();     // "Bahnhofstrasse 1"
$address->getCityLine();       // "8000 Zürich"
$address->getFullAddress();    // Mehrzeilige Adresse
$address->getOneLiner();       // "Bahnhofstrasse 1, 8000 Zürich"
$address->isSwiss();           // true/false
```

## Rechtsformen (Legal Forms)

| ID | Bezeichnung |
|----|-------------|
| 1 | Einzelunternehmen |
| 2 | Einfache Gesellschaft |
| 3 | Kollektivgesellschaft |
| 4 | Kommanditgesellschaft |
| 5 | Kommanditaktiengesellschaft |
| 6 | Aktiengesellschaft |
| 7 | GmbH |
| 8 | Genossenschaft |
| 9 | Verein |
| 10 | Stiftung |
| 11 | Zweigniederlassung |

## Fehlerbehandlung

```php
use Ecolabor\SwissUid\Exceptions\UidApiException;

try {
    $company = SwissUid::getByUid('invalid');
} catch (UidApiException $e) {
    // API-Fehler behandeln
    logger()->error('UID API Error: ' . $e->getMessage());
}

// Oder mit Search (gibt UidSearchResult zurück)
$result = SwissUid::search(['name' => 'Test']);

if ($result->hasError()) {
    echo "Fehler: " . $result->errorMessage;
    echo "Code: " . $result->errorCode;
}
```

## Testing

```bash
composer test
```

## Ressourcen

- [UID-Register Webseite](https://www.uid.admin.ch/)
- [BFS Dokumentation](https://www.bfs.admin.ch/bfs/de/home/register/unternehmensregister/unternehmens-identifikationsnummer.html)
- [API Spezifikation (PDF)](https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master)
- [UID Helpdesk](mailto:uid@bfs.admin.ch)

## Lizenz

MIT License. Siehe [LICENSE](LICENSE.md) für weitere Informationen.

## Credits

- [ecolabor GmbH](https://github.com/ecolabor)
- Bundesamt für Statistik (BFS)
