# omnigame/rawg

[omniscient/omnigame](https://github.com/glitchr-studio/omnigame)'s RAWG, under any PHP host, with an
API key: search (`/api/games?search=`) and a game's record by its RAWG number or slug
(`/api/games/{id}`) with its description, release date, studios, platforms, genres, tags, RAWG and
Metacritic scores, and the stores that sell it (`/api/games/{id}/stores`) - its Steam number,
itch.io page and Epic slug read from their links.

```php
$rawg = (new RawgFactory($httpClient))->create(['api_key' => getenv('RAWG_KEY')]);
$portal = $rawg->game(GameRef::rawg('portal-2'));
```

Options: `api_key` (required), `user_agent`, `api`.

## RAWG's terms, as read on 2026-10-24

From [rawg.io/apidocs](https://rawg.io/apidocs): the free plan allows "up to 20,000 requests per
month"; "add an active hyperlink from every page where the data of RAWG is used"; free for
commercial use for startups and hobby projects "with not more than 100,000 monthly active users",
larger projects by agreement. The key goes in the query (`key=`).

## Verified, and not

- Verified (the core's harness, PHP 8.4): a game with its stores, a search, unknown, a refused key;
  `BareTest`; PHPStan max. The answers are **shaped after RAWG's documentation and written by
  hand**: no key was used.
- Not verified live: no key.

## Responsibility

An independent, unofficial client, affiliated with, endorsed by or sponsored by none of the services
it reaches. The MIT licence covers this code only and grants no right to the service or its data:
whoever runs it complies with the terms above and obtains what they require. The authors publish
it free of charge, make no commercial use of it and accept no liability for its use.

## Licence

MIT.
