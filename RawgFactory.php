<?php

namespace Omnigame\Rawg;

use Omnigame\Config;
use Omnigame\Http\JsonClient;
use Omnigame\ProviderFactory;
use Omnigame\ProviderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** "rawg": options api_key (required), user_agent, api ("https://api.rawg.io/api"). */
final class RawgFactory extends ProviderFactory
{
    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnigame.factory_name' => 'rawg',
            'omnigame.factory_title' => 'RAWG',
            'omnigame.required_options' => ['api_key'],
            'user_agent' => 'omnigame/1.0 (+https://github.com/glitchr-studio/omnigame)',
            'api' => 'https://api.rawg.io/api',
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new RawgProvider(new JsonClient($this->http ?? HttpClient::create(['timeout' => 20]), 'RAWG', ['Accept' => 'application/json', 'User-Agent' => (string) $c->string('user_agent')]), (string) $c->string('api_key'), rtrim((string) $c->string('api'), '/'));
    }
}
