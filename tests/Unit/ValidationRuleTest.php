<?php

use Ecolabor\SwissUid\Rules\ValidSwissUid;
use Illuminate\Support\Facades\Validator;

describe('ValidSwissUid Rule - Format Only', function () {
    it('validates correct UID format', function () {
        $validator = Validator::make(
            ['uid' => 'CHE-123.456.788'],
            ['uid' => ValidSwissUid::format()]
        );
        
        expect($validator->passes())->toBeTrue();
    });

    it('validates plain 9-digit UID', function () {
        $validator = Validator::make(
            ['uid' => '123456788'],
            ['uid' => ValidSwissUid::format()]
        );
        
        expect($validator->passes())->toBeTrue();
    });

    it('rejects UID with wrong length', function () {
        $validator = Validator::make(
            ['uid' => '12345'],
            ['uid' => ValidSwissUid::format()]
        );
        
        expect($validator->fails())->toBeTrue();
    });

    it('rejects UID with invalid checksum', function () {
        $validator = Validator::make(
            ['uid' => '123456789'], // Invalid checksum
            ['uid' => ValidSwissUid::format()]
        );
        
        expect($validator->fails())->toBeTrue();
    });

    it('validates known valid UID (Migros)', function () {
        // CHE-105.805.649 is Migros-Genossenschafts-Bund
        $validator = Validator::make(
            ['uid' => 'CHE-105.805.649'],
            ['uid' => ValidSwissUid::format()]
        );
        
        expect($validator->passes())->toBeTrue();
    });
});

describe('UID Checksum Validation', function () {
    it('calculates checksum correctly for known UIDs', function () {
        // Known valid UIDs (checksum verified)
        $validUids = [
            '105805649', // Migros
            '109391747', // Coop
            '116304245', // SBB
        ];

        foreach ($validUids as $uid) {
            $validator = Validator::make(
                ['uid' => $uid],
                ['uid' => ValidSwissUid::format()]
            );
            
            expect($validator->passes())
                ->toBeTrue("UID {$uid} should be valid");
        }
    });
});
