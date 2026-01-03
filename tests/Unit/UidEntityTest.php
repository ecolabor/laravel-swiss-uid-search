<?php

use Ecolabor\SwissUid\Data\Address;
use Ecolabor\SwissUid\Data\UidEntity;

describe('UidEntity Data Object', function () {
    it('creates entity with basic data', function () {
        $entity = new UidEntity(
            uid: '123456789',
            uidFormatted: 'CHE-123.456.789',
            uidCategory: 'CHE',
            organisationName: 'Test AG',
            organisationAdditionalName: null,
            legalFormId: 6,
            legalFormText: 'Aktiengesellschaft',
            uidregStatusId: 1,
            uidregStatusText: 'Aktiv',
            uidregStatusEnterpriseActive: true,
            uidregPublicStatus: null,
            uidregOrganisationTypeFirstDate: null,
            uidregLiquidationDate: null,
            address: null,
            commercialRegisterStatus: null,
            commercialRegisterEntryDate: null,
            commercialRegisterLiquidationDate: null,
            commercialRegisterNameTranslation: null,
            vatNumber: null,
            vatNumberFormatted: null,
            vatStatus: null,
            vatEntryDate: null,
            vatLiquidationDate: null,
            cantonAbbreviation: 'ZH',
            municipalityId: null,
            chId: null,
        );
        
        expect($entity->uid)->toBe('123456789');
        expect($entity->uidFormatted)->toBe('CHE-123.456.789');
        expect($entity->organisationName)->toBe('Test AG');
        expect($entity->isActive())->toBeTrue();
    });

    it('returns full name with additional name', function () {
        $entity = new UidEntity(
            uid: '123456789',
            uidFormatted: 'CHE-123.456.789',
            uidCategory: 'CHE',
            organisationName: 'Test AG',
            organisationAdditionalName: 'Zweigniederlassung Zürich',
            legalFormId: 6,
            legalFormText: 'Aktiengesellschaft',
            uidregStatusId: 1,
            uidregStatusText: 'Aktiv',
            uidregStatusEnterpriseActive: true,
            uidregPublicStatus: null,
            uidregOrganisationTypeFirstDate: null,
            uidregLiquidationDate: null,
            address: null,
            commercialRegisterStatus: null,
            commercialRegisterEntryDate: null,
            commercialRegisterLiquidationDate: null,
            commercialRegisterNameTranslation: null,
            vatNumber: null,
            vatNumberFormatted: null,
            vatStatus: null,
            vatEntryDate: null,
            vatLiquidationDate: null,
            cantonAbbreviation: null,
            municipalityId: null,
            chId: null,
        );
        
        expect($entity->getFullName())->toBe('Test AG Zweigniederlassung Zürich');
    });

    it('detects inactive entity', function () {
        $entity = new UidEntity(
            uid: '123456789',
            uidFormatted: 'CHE-123.456.789',
            uidCategory: 'CHE',
            organisationName: 'Gelöschte AG',
            organisationAdditionalName: null,
            legalFormId: 6,
            legalFormText: 'Aktiengesellschaft',
            uidregStatusId: 2, // Gelöscht
            uidregStatusText: 'Gelöscht',
            uidregStatusEnterpriseActive: false,
            uidregPublicStatus: null,
            uidregOrganisationTypeFirstDate: null,
            uidregLiquidationDate: new DateTimeImmutable('2023-01-01'),
            address: null,
            commercialRegisterStatus: null,
            commercialRegisterEntryDate: null,
            commercialRegisterLiquidationDate: null,
            commercialRegisterNameTranslation: null,
            vatNumber: null,
            vatNumberFormatted: null,
            vatStatus: null,
            vatEntryDate: null,
            vatLiquidationDate: null,
            cantonAbbreviation: null,
            municipalityId: null,
            chId: null,
        );
        
        expect($entity->isActive())->toBeFalse();
    });

    it('detects VAT registration', function () {
        $entity = new UidEntity(
            uid: '123456789',
            uidFormatted: 'CHE-123.456.789',
            uidCategory: 'CHE',
            organisationName: 'MWST AG',
            organisationAdditionalName: null,
            legalFormId: 6,
            legalFormText: 'Aktiengesellschaft',
            uidregStatusId: 1,
            uidregStatusText: 'Aktiv',
            uidregStatusEnterpriseActive: true,
            uidregPublicStatus: null,
            uidregOrganisationTypeFirstDate: null,
            uidregLiquidationDate: null,
            address: null,
            commercialRegisterStatus: null,
            commercialRegisterEntryDate: null,
            commercialRegisterLiquidationDate: null,
            commercialRegisterNameTranslation: null,
            vatNumber: '123456789',
            vatNumberFormatted: 'CHE-123.456.789 MWST',
            vatStatus: 'active',
            vatEntryDate: new DateTimeImmutable('2020-01-01'),
            vatLiquidationDate: null,
            cantonAbbreviation: null,
            municipalityId: null,
            chId: null,
        );
        
        expect($entity->isVatRegistered())->toBeTrue();
    });

    it('converts to array correctly', function () {
        $entity = new UidEntity(
            uid: '123456789',
            uidFormatted: 'CHE-123.456.789',
            uidCategory: 'CHE',
            organisationName: 'Test AG',
            organisationAdditionalName: null,
            legalFormId: 6,
            legalFormText: 'Aktiengesellschaft',
            uidregStatusId: 1,
            uidregStatusText: 'Aktiv',
            uidregStatusEnterpriseActive: true,
            uidregPublicStatus: null,
            uidregOrganisationTypeFirstDate: null,
            uidregLiquidationDate: null,
            address: null,
            commercialRegisterStatus: null,
            commercialRegisterEntryDate: null,
            commercialRegisterLiquidationDate: null,
            commercialRegisterNameTranslation: null,
            vatNumber: null,
            vatNumberFormatted: null,
            vatStatus: null,
            vatEntryDate: null,
            vatLiquidationDate: null,
            cantonAbbreviation: 'ZH',
            municipalityId: null,
            chId: null,
        );
        
        $array = $entity->toArray();
        
        expect($array)->toHaveKey('uid', '123456789');
        expect($array)->toHaveKey('uid_formatted', 'CHE-123.456.789');
        expect($array)->toHaveKey('name', 'Test AG');
        expect($array)->toHaveKey('legal_form', 'Aktiengesellschaft');
        expect($array)->toHaveKey('canton', 'ZH');
    });
});
