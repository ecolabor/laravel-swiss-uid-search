<?php

use Ecolabor\SwissUid\Facades\SwissUid;

describe('UID Formatting', function () {
    it('formats a 9-digit UID correctly', function () {
        $formatted = SwissUid::formatUid('123456789');
        
        expect($formatted)->toBe('CHE-123.456.789');
    });

    it('formats UID with existing formatting', function () {
        $formatted = SwissUid::formatUid('CHE-123.456.789');
        
        expect($formatted)->toBe('CHE-123.456.789');
    });

    it('formats UID with spaces and dashes', function () {
        $formatted = SwissUid::formatUid('CHE 123 456 789');
        
        expect($formatted)->toBe('CHE-123.456.789');
    });

    it('returns original for invalid UID length', function () {
        $formatted = SwissUid::formatUid('12345');
        
        expect($formatted)->toBe('12345');
    });

    it('formats as MWST number correctly', function () {
        $formatted = SwissUid::formatMwst('123456789');
        
        expect($formatted)->toBe('CHE-123.456.789 MWST');
    });
});

describe('UID Normalization', function () {
    it('normalizes formatted UID to digits only', function () {
        $normalized = SwissUid::normalizeUid('CHE-123.456.789');
        
        expect($normalized)->toBe('123456789');
    });

    it('normalizes UID with MWST suffix', function () {
        $normalized = SwissUid::normalizeUid('CHE-123.456.789 MWST');
        
        expect($normalized)->toBe('123456789');
    });

    it('normalizes plain digits', function () {
        $normalized = SwissUid::normalizeUid('123456789');
        
        expect($normalized)->toBe('123456789');
    });

    it('handles various separators', function () {
        $normalized = SwissUid::normalizeUid('CHE 123-456.789');
        
        expect($normalized)->toBe('123456789');
    });
});
