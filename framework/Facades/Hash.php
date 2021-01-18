<?php

namespace Laras\Facades;

/**
 * @method static array info(string $hashedValue)
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 * @method static string make(string $value, array $options = [])
 *
 * @see \Illuminate\Hashing\HashManager
 */
class Hash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public function getAccessor()
    {
        return 'hash';
    }
}
