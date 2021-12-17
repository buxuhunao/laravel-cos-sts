<?php

namespace Buxuhunao\CosSts;

use Illuminate\Support\Facades\Facade;

/**
 * @method static $this setDurationSeconds(int $seconds)
 * @method static $this setEffect(bool $isAllow)
 * @method static $this setPolicy(string|array $allowActions, string|array $allowPrefixes)
 * @method static array getTempKeys(array $options = null)
 */
class Sts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StsClient::class;
    }
}
