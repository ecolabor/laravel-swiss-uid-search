# Changelog

All notable changes to `laravel-swiss-uid-search` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-01-02

### Added
- Initial release
- `SwissUid` Facade for easy access to the UID service
- `getByUid()` - Fetch company details by UID number
- `validateUid()` - Validate UID numbers against the BFS API
- `validateVatNumber()` - Validate Swiss VAT numbers
- `searchByName()` - Search companies by name
- `searchByLocation()` - Search companies by location/city
- `ValidSwissUid` validation rule with checksum verification
- `UidEntity` DTO with full company information
- `Address` DTO following eCH-0010 standard
- `UidSearchResult` collection with filtering methods
- Caching support for API responses
- Support for both production and test API environments
- Full compatibility with Laravel 11 and 12
