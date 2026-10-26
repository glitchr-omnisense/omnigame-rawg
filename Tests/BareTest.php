<?php

namespace Omnigame\Rawg\Tests;

use Omnigame\Rawg\RawgProvider;
use Omnigame\Testing\BareTestCase;

/** omnigame/rawg's classes load with omnisense/omnigame and Symfony's HTTP client contracts alone. */
final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return RawgProvider::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }
}
