<?php

use Ecolabor\SwissUid\Facades\SwissUid;
use Ecolabor\SwissUid\Exceptions\UidApiException;

/**
 * API Integration Tests
 *
 * These tests require:
 * - SOAP extension
 * - Network access to uid-wse.admin.ch
 * - Set SWISS_UID_RUN_INTEGRATION_TESTS=true
 *
 * Run with: SWISS_UID_RUN_INTEGRATION_TESTS=true ./vendor/bin/pest
 */
describe('API Integration Tests', function () {

    beforeEach(function () {
        config()->set('swiss-uid.environment', 'production');
        config()->set('swiss-uid.cache.enabled', false);
    });

    it('can fetch a known company by UID', function () {
        // Migros-Genossenschafts-Bund
        $entity = SwissUid::getByUid('CHE-105.805.649');

        expect($entity)->not->toBeNull();
        expect($entity->uid)->toBe('105805649');
        expect($entity->uidFormatted)->toBe('CHE-105.805.649');
    })->skip(
        fn () => ! extension_loaded('soap') || ! env('SWISS_UID_RUN_INTEGRATION_TESTS'),
        'Integration tests disabled. Set SWISS_UID_RUN_INTEGRATION_TESTS=true to run.'
    );

    it('returns null for non-existent UID', function () {
        $entity = SwissUid::getByUid('CHE-000.000.018'); // Valid checksum, non-existent

        expect($entity)->toBeNull();
    })->skip(
        fn () => ! extension_loaded('soap') || ! env('SWISS_UID_RUN_INTEGRATION_TESTS'),
        'Integration tests disabled'
    );

    it('can validate existing UID', function () {
        $isValid = SwissUid::validateUid('CHE-105.805.649');

        expect($isValid)->toBeTrue();
    })->skip(
        fn () => ! extension_loaded('soap') || ! env('SWISS_UID_RUN_INTEGRATION_TESTS'),
        'Integration tests disabled'
    );

    it('can search companies by name', function () {
        $result = SwissUid::searchByName('Migros', maxResults: 5);

        expect($result->isNotEmpty())->toBeTrue();
    })->skip(
        fn () => ! extension_loaded('soap') || ! env('SWISS_UID_RUN_INTEGRATION_TESTS'),
        'Integration tests disabled'
    );

    it('throws exception for invalid UID format', function () {
        SwissUid::getByUid('invalid');
    })->throws(UidApiException::class);

});

describe('Offline Tests', function () {

    it('can format UID without API call', function () {
        expect(SwissUid::formatUid('123456789'))->toBe('CHE-123.456.789');
    });

    it('can normalize UID without API call', function () {
        expect(SwissUid::normalizeUid('CHE-123.456.789'))->toBe('123456789');
    });

    it('can format MWST without API call', function () {
        expect(SwissUid::formatMwst('123456789'))->toBe('CHE-123.456.789 MWST');
    });

});
