<?php

declare(strict_types=1);

namespace Struct\Reflection;

class MemoryCache
{
    /**
     * @var array<string, mixed>
     */
    protected static array $transitMemoryCache = [];

    public static function write(string $identifier, mixed $value, ?int $timeToLive = null): void
    {
        $internalCacheIdentifier = self::buildInternalCacheIdentifier($identifier);
        self::writeTransitMemoryCache($internalCacheIdentifier, $value);
        if (self::hasAPCU() === true) {
            if ($timeToLive === null) {
                $timeToLive = 0;
            }
            apcu_store($internalCacheIdentifier, $value, $timeToLive);
        }
    }

    public static function clear(): void
    {
        self::$transitMemoryCache = [];
        if (self::hasAPCU() === true) {
            apcu_clear_cache();
        }
    }

    public static function delete(string $identifier): void
    {
        $internalCacheIdentifier = self::buildInternalCacheIdentifier($identifier);
        self::deleteTransitMemoryCache($internalCacheIdentifier);
        if (self::hasAPCU() === true) {
            apcu_delete($internalCacheIdentifier);
        }
    }

    public static function has(string $identifier): bool
    {
        $internalCacheIdentifier = self::buildInternalCacheIdentifier($identifier);
        $has = self::hasTransitMemoryCache($internalCacheIdentifier);
        if ($has === true) {
            return true;
        }
        if (self::hasAPCU() === false) {
            return false;
        }
        return apcu_exists($internalCacheIdentifier) === true;
    }

    public static function read(string $identifier): mixed
    {
        $internalCacheIdentifier = self::buildInternalCacheIdentifier($identifier);
        $value = self::readTransitMemoryCache($internalCacheIdentifier);
        if ($value !== null) {
            return $value;
        }

        if (self::hasAPCU() === false) {
            return null;
        }

        $success = false;
        $value = apcu_fetch($internalCacheIdentifier, $success);
        if ($success === false) {
            return null;
        }
        return $value;
    }

    public static function buildCacheIdentifier(string $key, string $namespace): string
    {
        $cacheIdentifier = hash_hmac('sha1', $key, $namespace);
        return $cacheIdentifier;
    }

    protected static function readTransitMemoryCache(string $cacheIdentifier): mixed
    {
        if (array_key_exists($cacheIdentifier, self::$transitMemoryCache) === false) {
            return null;
        }
        return self::$transitMemoryCache[$cacheIdentifier];
    }

    protected static function hasTransitMemoryCache(string $cacheIdentifier): bool
    {
        if (array_key_exists($cacheIdentifier, self::$transitMemoryCache) === false) {
            return false;
        }
        return true;
    }

    protected static function writeTransitMemoryCache(string $cacheIdentifier, mixed $value): void
    {
        self::$transitMemoryCache[$cacheIdentifier] = $value;
    }

    protected static function deleteTransitMemoryCache(string $cacheIdentifier): void
    {
        if (array_key_exists($cacheIdentifier, self::$transitMemoryCache) === false) {
            return;
        }

        unset(self::$transitMemoryCache[$cacheIdentifier]);
    }

    protected static function hasAPCU(): bool
    {
        if (function_exists('apcu_enabled') === false) {
            return false;
        }

        if (apcu_enabled() === false) {
            return false;
        }

        return true;
    }

    protected static function buildInternalCacheIdentifier(string $key): string
    {
        $internalCacheIdentifier = self::buildCacheIdentifier($key, '93cfdaf9-e722-4307-83b6-a214647fb2b6');
        return $internalCacheIdentifier;
    }
}
