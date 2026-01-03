<?php

declare(strict_types=1);

namespace Ecolabor\SwissUid\Facades;

use Illuminate\Support\Facades\Facade;
use Ecolabor\SwissUid\Contracts\UidClientInterface;
use Ecolabor\SwissUid\Data\UidEntity;
use Ecolabor\SwissUid\Data\UidSearchResult;

/**
 * Swiss UID Webservice Facade
 *
 * Based on the BFS UID Webservice Version 5.0.
 *
 * @method static UidEntity|null getByUid(string $uid)
 * @method static UidSearchResult search(array $criteria)
 * @method static UidSearchResult searchByName(string $name, int $maxResults = 100)
 * @method static UidSearchResult searchByLocation(string $town, ?string $name = null, int $maxResults = 100)
 * @method static bool validateUid(string $uid)
 * @method static bool validateVatNumber(string $vatNumber)
 * @method static string formatUid(string $uid)
 * @method static string formatMwst(string $uid)
 * @method static string normalizeUid(string $uid)
 *
 * @see \Ecolabor\SwissUid\Services\UidClient
 * @see https://dam-api.bfs.admin.ch/hub/api/dam/assets/24605175/master
 */
class SwissUid extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UidClientInterface::class;
    }
}
