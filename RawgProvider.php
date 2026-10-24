<?php

namespace Omnigame\Rawg;

use Omnigame\Capabilities;
use Omnigame\CatalogueInterface;
use Omnigame\Exception\NotSupportedException;
use Omnigame\Http\Data;
use Omnigame\Http\JsonClient;
use Omnigame\Model\Game;
use Omnigame\Model\GameRef;
use Omnigame\Model\Identifiers;
use Omnigame\Model\Image;
use Omnigame\Model\ImageKind;
use Omnigame\Model\Platform;
use Omnigame\Model\Query;
use Omnigame\Model\Rating;
use Omnigame\Model\Scheme;
use Omnigame\ProviderInterface;
use Omnigame\SearchInterface;

/**
 * RAWG's games database with an API key: search, a game's record by its
 * RAWG number or slug with its studios, platforms, scores, and the stores
 * that sell it (/games/{id}/stores), whose links give its Steam number,
 * its itch.io page and its Epic slug.
 */
final class RawgProvider implements ProviderInterface, SearchInterface, CatalogueInterface
{
    /** A store link's pattern and the identifier it holds. */
    private const STORES = [
        'steam' => '~store\.steampowered\.com/app/(\d+)~',
        'itch' => '~https?://([a-z0-9_-]+)\.itch\.io/([a-z0-9_-]+)~i',
        'epic' => '~epicgames\.com/(?:store/)?(?:[a-z]{2}(?:-[a-z]{2})?/)?(?:p|product)/([a-z0-9-]+)~i',
    ];

    public function __construct(private readonly JsonClient $json, private readonly string $key, private readonly string $api = 'https://api.rawg.io/api')
    {
    }

    public function getName(): string
    {
        return 'rawg';
    }

    public function getTitle(): string
    {
        return 'RAWG';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(
            search: true,
            catalogue: true,
            lookupBy: [Scheme::Rawg],
            attribution: 'Data from RAWG (rawg.io), with a link on every page that shows it',
            terms: ['https://rawg.io/apidocs', 'https://rawg.io/tos_api'],
        );
    }

    public function search(Query $query): array
    {
        $answer = $this->json->get($this->api.'/games', ['key' => $this->key, 'search' => $query->text, 'page_size' => $query->limit]);

        return array_map(fn (Data $game) => $this->game0($game, []), $answer?->list('results') ?? []);
    }

    public function game(GameRef $ref, ?string $language = null, ?string $country = null): ?Game
    {
        if (!$this->capabilities()->resolves($ref) || !preg_match('~^[a-z0-9-]+$~i', $ref->id)) {
            throw NotSupportedException::ref($this->getTitle(), $ref);
        }
        $data = $this->json->get($this->api.'/games/'.$ref->id, ['key' => $this->key]);
        if (null === $data || null === $data->string('id')) {
            return null;
        }
        $stores = $this->json->get($this->api.'/games/'.$data->string('id').'/stores', ['key' => $this->key]);

        return $this->game0($data, $stores?->list('results') ?? []);
    }

    /** @param list<Data> $stores */
    private function game0(Data $data, array $stores): Game
    {
        $ids = ['rawg' => $data->string('id')];
        foreach ($stores as $store) {
            foreach (self::STORES as $scheme => $pattern) {
                if (preg_match($pattern, (string) $store->string('url'), $m)) {
                    $ids[$scheme] ??= isset($m[2]) ? strtolower($m[1].'/'.$m[2]) : $m[1];
                }
            }
        }
        $images = [];
        if (null !== $background = $data->string('background_image')) {
            $images[] = new Image(ImageKind::Background, $background);
        }
        foreach ($data->list('short_screenshots') as $screenshot) {
            if (null !== ($url = $screenshot->string('image')) && $url !== $background) {
                $images[] = new Image(ImageKind::Screenshot, $url);
            }
        }
        $ratings = [];
        $votes = $data->int('ratings_count');
        if (null !== $votes && $votes > 0) {
            $ratings[] = new Rating('rawg', (float) $data->float('rating'), 5.0, $votes);
        }
        if (null !== $metacritic = $data->int('metacritic')) {
            $ratings[] = new Rating('metacritic', (float) $metacritic);
        }
        $slug = $data->string('slug');

        return new Game(
            title: (string) $data->string('name'),
            identifiers: Identifiers::of($ids),
            source: 'rawg',
            url: null === $slug ? null : 'https://rawg.io/games/'.$slug,
            summary: $data->string('description_raw'),
            released: $data->date('released'),
            comingSoon: true === $data->bool('tba'),
            developers: $data->strings('developers', 'name'),
            publishers: $data->strings('publishers', 'name'),
            genres: $data->strings('genres', 'name'),
            tags: \array_slice($data->strings('tags', 'name'), 0, 20),
            platforms: Platform::fromLabels(array_merge(array_filter(array_map(static fn (Data $p) => $p->string('platform.name'), $data->list('platforms'))), $data->strings('parent_platforms', 'platform.name'))),
            images: $images,
            ratings: $ratings,
        );
    }
}
