<?php

use Ecolabor\SwissUid\Data\Address;

describe('Address Data Object', function () {
    it('creates address with all fields', function () {
        $address = new Address(
            street: 'Bahnhofstrasse',
            houseNumber: '1',
            addressSupplement: 'c/o Test AG',
            postOfficeBoxNumber: '123',
            postOfficeBoxText: 'Postfach',
            locality: null,
            swissZipCode: '8001',
            swissZipCodeAddOn: '00',
            swissZipCodeId: 1234,
            town: 'Zürich',
            countryIdISO2: 'CH',
            countryName: 'Schweiz',
        );
        
        expect($address->street)->toBe('Bahnhofstrasse');
        expect($address->houseNumber)->toBe('1');
        expect($address->swissZipCode)->toBe('8001');
        expect($address->town)->toBe('Zürich');
        expect($address->isSwiss())->toBeTrue();
    });

    it('generates street line correctly', function () {
        $address = new Address(
            street: 'Bahnhofstrasse',
            houseNumber: '1',
            addressSupplement: null,
            postOfficeBoxNumber: null,
            postOfficeBoxText: null,
            locality: null,
            swissZipCode: '8001',
            swissZipCodeAddOn: null,
            swissZipCodeId: null,
            town: 'Zürich',
        );
        
        expect($address->getStreetLine())->toBe('Bahnhofstrasse 1');
    });

    it('generates city line correctly', function () {
        $address = new Address(
            street: 'Bahnhofstrasse',
            houseNumber: '1',
            addressSupplement: null,
            postOfficeBoxNumber: null,
            postOfficeBoxText: null,
            locality: null,
            swissZipCode: '8001',
            swissZipCodeAddOn: null,
            swissZipCodeId: null,
            town: 'Zürich',
        );
        
        expect($address->getCityLine())->toBe('8001 Zürich');
    });

    it('generates one-liner correctly', function () {
        $address = new Address(
            street: 'Bahnhofstrasse',
            houseNumber: '1',
            addressSupplement: null,
            postOfficeBoxNumber: null,
            postOfficeBoxText: null,
            locality: null,
            swissZipCode: '8001',
            swissZipCodeAddOn: null,
            swissZipCodeId: null,
            town: 'Zürich',
        );
        
        expect($address->getOneLiner())->toBe('Bahnhofstrasse 1, 8001 Zürich');
    });

    it('converts to array correctly', function () {
        $address = new Address(
            street: 'Bahnhofstrasse',
            houseNumber: '1',
            addressSupplement: null,
            postOfficeBoxNumber: null,
            postOfficeBoxText: null,
            locality: null,
            swissZipCode: '8001',
            swissZipCodeAddOn: null,
            swissZipCodeId: null,
            town: 'Zürich',
        );
        
        $array = $address->toArray();
        
        expect($array)->toHaveKey('street', 'Bahnhofstrasse');
        expect($array)->toHaveKey('house_number', '1');
        expect($array)->toHaveKey('zip_code', '8001');
        expect($array)->toHaveKey('town', 'Zürich');
    });

    it('identifies non-Swiss address', function () {
        $address = new Address(
            street: 'Hauptstrasse',
            houseNumber: '1',
            addressSupplement: null,
            postOfficeBoxNumber: null,
            postOfficeBoxText: null,
            locality: null,
            swissZipCode: null,
            swissZipCodeAddOn: null,
            swissZipCodeId: null,
            town: 'Berlin',
            countryIdISO2: 'DE',
        );
        
        expect($address->isSwiss())->toBeFalse();
    });
});
