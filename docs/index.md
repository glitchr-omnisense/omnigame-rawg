# omnigame/rawg

RAWG for [omnisense/omnigame](https://github.com/glitchr-omnisense/omnigame), with an API key: search
and a game's record by its RAWG number or slug, with its description, release date, studios,
platforms, genres, tags, RAWG and Metacritic scores, and the stores that sell it - its Steam
number, itch.io page and Epic slug read from their links. The provider calls:

| Operation | Request |
|---|---|
| search | `GET https://api.rawg.io/api/games?key=...&search=...&page_size=...` |
| a record | `GET /api/games/{id or slug}?key=...` |
| its stores | `GET /api/games/{id}/stores?key=...` |

The key travels in the query string (`key=`), as RAWG takes it.

## Installation

```sh
composer require omnigame/rawg
```

Requires PHP 8.2, `omnisense/omnigame` (1.x) and `symfony/http-client` (6.4, 7 or 8).

## Credentials

An **API key** from [rawg.io/apidocs](https://rawg.io/apidocs). The free plan allows up to 20,000
requests a month.

## Options

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | |
| `user_agent` | string | `omnigame/1.0 (+https://github.com/glitchr-omnisense/omnigame)` | no | |
| `api` | string | `https://api.rawg.io/api` | no | |

## Capabilities

| Contract | |
|---|---|
| `SearchInterface` | yes |
| `CatalogueInterface` | by rawg: a number or a slug (letters, digits, `-`) |
| `LibraryInterface`, `OwnershipInterface` | no |
| prices | no; a key is needed |

`attribution`: `Data from RAWG (rawg.io), with a link on every page that shows it`.

## Behaviour

**A record.** Two calls: the game, then its stores. An answer without an id is `null`.

| Field | Read from |
|---|---|
| `title`, `summary` | `name`, `description_raw` |
| `identifiers` | the RAWG number, and from the stores' `url`: `store.steampowered.com/app/<n>` (steam), `https://<creator>.itch.io/<game>` (itch, lower-cased), `epicgames.com/[store/][xx[-xx]/](p\|product)/<slug>` (epic); no GOG number |
| `url` | `https://rawg.io/games/<slug>` |
| `released`, `comingSoon` | `released`, `tba` |
| `developers`, `publishers`, `genres` | their `name`s |
| `tags` | the first 20 tags' names |
| `platforms` | `platforms[].platform.name` and `parent_platforms[].platform.name`, folded |
| `images` | `background_image` (`Background`), `short_screenshots` but the background (`Screenshot`) |
| `ratings` | `rawg`, `rating` out of 5 with `ratings_count` when rated; `metacritic` out of 100 |

Language and country are not used. **Search**: the same reading without the stores (no cross
identifiers), `page_size` the limit.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnigame\Model\GameRef;
use Omnigame\Model\Scheme;
use Omnigame\Rawg\RawgFactory;
use Symfony\Component\HttpClient\HttpClient;

$rawg = (new RawgFactory(HttpClient::create()))->create(['api_key' => getenv('RAWG_KEY')]);

$portal = $rawg->game(GameRef::rawg('portal-2'));
echo $portal->title, ' - Steam ', $portal->identifiers->get(Scheme::Steam), "\n";   // leads to omnigame/steam
```

The record read from the package's test answers (written after RAWG's documentation; no key was
used), in the core's harness on 2026-10-27 - each field, then the calls:

```
Portal 2 | 2011-04-18 | no price
  ids {"epic":"portal-2-example","itch":"example/portal-2","rawg":"4200","steam":"620"} platforms ["windows","playstation","linux","mac","xbox"]
  dev ["Valve Software"] pub ["Valve","Electronic Arts"] genres ["Puzzle"] tags ["Singleplayer"]
  summary Portal 2 is a first-person puzzle game.
  background https://media.rawg.io/media/games/example-portal-2.jpg
  rating rawg 4.6/5 (5000)
  rating metacritic 95/100
  https://rawg.io/games/portal-2
  > GET https://api.rawg.io/api/games/portal-2 {"key":"example-key"}
  > GET https://api.rawg.io/api/games/4200/stores {"key":"example-key"}
```

In Symfony:

```yaml
services:
    Omnigame\Rawg\RawgFactory:
        arguments: ['@http_client']
    Omnigame\Registry:
        arguments:
            $factories: ['@Omnigame\Rawg\RawgFactory']
            $config:
                rawg: { factory: rawg, options: { api_key: '%env(RAWG_KEY)%' } }
```

## Errors

- unknown (RAWG's 404 `{"detail": "Not found."}`): `null`; a ref of another scheme, or an id with
  other characters: `NotSupportedException`;
- a refused key: `ProviderException` (`RAWG answered 401 for GET /api/games. {"error":"The key parameter is not provided"}`);
  a network error: `UnreachableException`;
- a missing `api_key`: `InvalidConfigException` (`The "rawg" provider needs: api_key.`).

> [!WARNING]
> Known defect: a network error's message quotes the URL with the key (`... for
> "https://api.rawg.io/api/games/portal-2?key=SECRET-KEY".`, seen in a container with no network,
> 2026-10-27), where keys are expected to stay out of messages.

## Verified, and not

Tested offline (`Tests/RawgProviderTest.php`, in the core's harness, PHP 8.4): a game with its
stores, a search, unknown, a refused key. The answers are shaped after RAWG's documentation and
written by hand. `Tests/BareTest.php` loads every class with the package's requirements alone.

Not verified: no RAWG key was used.

## Responsibility

An independent, unofficial client, affiliated with RAWG in no way. From
[rawg.io/apidocs](https://rawg.io/apidocs): an active hyperlink to RAWG on every page that shows
its data; free for commercial use under 100,000 monthly active users, larger projects by agreement.
See the README.
