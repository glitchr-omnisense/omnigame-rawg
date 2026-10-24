<?php

namespace Omnigame\Rawg\Tests;

use Omnigame\Exception\NotSupportedException;
use Omnigame\Exception\ProviderException;
use Omnigame\Model\Game;
use Omnigame\Model\GameRef;
use Omnigame\Model\Platform;
use Omnigame\Model\Query;
use Omnigame\Model\Rating;
use Omnigame\Rawg\RawgFactory;
use Omnigame\Rawg\RawgProvider;
use Omnigame\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * RAWG's answers shaped after its API documentation (api.rawg.io/docs),
 * values written by hand: no key was used, nothing here was captured from
 * RAWG.
 */
final class RawgProviderTest extends TestCase
{
    public function testAGameWithTheStoresThatSellIt(): void
    {
        $http = self::http();
        $game = self::provider($http)->game(GameRef::rawg('portal-2'));

        self::assertNotNull($game);
        self::assertSame(['Portal 2', '2011-04-18', ['Valve Software'], ['Valve', 'Electronic Arts']], [$game->title, $game->released?->format('Y-m-d'), $game->developers, $game->publishers]);
        self::assertSame(['epic' => 'portal-2-example', 'itch' => 'example/portal-2', 'rawg' => '4200', 'steam' => '620'], $game->identifiers->toArray());
        self::assertSame([Platform::Windows, Platform::PlayStation, Platform::Linux, Platform::Mac, Platform::Xbox], $game->platforms);
        self::assertSame([['rawg', 4.6, 5.0], ['metacritic', 95.0, 100.0]], array_map(static fn (Rating $r) => [$r->source, $r->value, $r->scale], $game->ratings));
        self::assertSame('https://rawg.io/games/portal-2', $game->url);
        self::assertSame(['key' => 'not-a-real-rawg-key'], $http->requests[0]['options']['query'] ?? null);
        self::assertSame('https://api.rawg.io/api/games/4200/stores', $http->requests[1]['url']);
    }

    public function testASearch(): void
    {
        $games = self::provider(self::http())->search(new Query('portal', limit: 2));

        self::assertSame([['Portal 2', '4200'], ['Portal', '4286']], array_map(static fn (Game $g) => [$g->title, $g->identifiers->get('rawg')], $games));
    }

    public function testUnknownIsNullAndARefusedKeyAnError(): void
    {
        self::assertNull(self::provider(self::http())->game(GameRef::rawg(1)));

        $http = RecordedHttpClient::routes(['~~' => [401, '{"error": "The key parameter is not provided"}']]);
        $this->expectException(ProviderException::class);
        self::provider($http)->game(GameRef::rawg(4200));
    }

    public function testOnlyRawgNumbersAreKnown(): void
    {
        $this->expectException(NotSupportedException::class);
        self::provider(self::http())->game(GameRef::steam(620));
    }

    private static function http(): RecordedHttpClient
    {
        $api = '~^GET https://api\.rawg\.io/api/';

        return RecordedHttpClient::routes([
            $api.'games/portal-2\?~' => [200, '{"id": 4200, "slug": "portal-2", "name": "Portal 2", "description_raw": "Portal 2 is a first-person puzzle game.", "released": "2011-04-18", "tba": false, "background_image": "https://media.rawg.io/media/games/example-portal-2.jpg", "rating": 4.6, "ratings_count": 5000, "metacritic": 95, "platforms": [{"platform": {"id": 4, "name": "PC", "slug": "pc"}}, {"platform": {"id": 16, "name": "PlayStation 3", "slug": "playstation3"}}, {"platform": {"id": 6, "name": "Linux", "slug": "linux"}}, {"platform": {"id": 5, "name": "macOS", "slug": "macos"}}, {"platform": {"id": 14, "name": "Xbox 360", "slug": "xbox360"}}], "developers": [{"id": 1, "name": "Valve Software"}], "publishers": [{"id": 2, "name": "Valve"}, {"id": 3, "name": "Electronic Arts"}], "genres": [{"id": 1, "name": "Puzzle"}], "tags": [{"id": 1, "name": "Singleplayer"}]}'],
            $api.'games/4200/stores~' => [200, '{"count": 3, "results": [{"id": 1, "game_id": 4200, "store_id": 1, "url": "https://store.steampowered.com/app/620/Portal_2/"}, {"id": 2, "game_id": 4200, "store_id": 9, "url": "https://example.itch.io/portal-2"}, {"id": 3, "game_id": 4200, "store_id": 11, "url": "https://store.epicgames.com/en-US/p/portal-2-example"}]}'],
            $api.'games\?key=[^&]+&search=portal~' => [200, '{"count": 2, "next": null, "results": [{"id": 4200, "slug": "portal-2", "name": "Portal 2", "released": "2011-04-18", "rating": 4.6, "ratings_count": 5000, "parent_platforms": [{"platform": {"id": 1, "name": "PC"}}]}, {"id": 4286, "slug": "portal", "name": "Portal", "released": "2007-10-09"}]}'],
            $api.'~' => [404, '{"detail": "Not found."}'],
        ]);
    }

    /** @param array<string, mixed> $options */
    private static function provider(RecordedHttpClient $http, array $options = []): RawgProvider
    {
        $provider = (new RawgFactory($http))->create($options + ['api_key' => 'not-a-real-rawg-key']);
        self::assertInstanceOf(RawgProvider::class, $provider);

        return $provider;
    }
}
