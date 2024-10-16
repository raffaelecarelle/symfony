<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Translation\Bridge\Deepl;

use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Exception\UnsupportedSchemeException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Provider\AbstractProviderFactory;
use Symfony\Component\Translation\Provider\Dsn;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Raffaele Carelle <raffaele.carelle@gmail.com>
 */
final class DeeplProviderFactory extends AbstractProviderFactory
{
    private const HOST = 'api.deepl.com';

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        private string $defaultLocale,
        private LoaderInterface $loader,
        private ?TranslatorBagInterface $translatorBag = null,
    ) {
    }

    public function create(Dsn $dsn): DeeplProvider
    {
        if ('deepl' !== $dsn->getScheme()) {
            throw new UnsupportedSchemeException($dsn, 'deepl', $this->getSupportedSchemes());
        }

        $endpoint = 'default' === $dsn->getHost() ? self::HOST : $dsn->getHost();
        $endpoint .= $dsn->getPort() ? ':'.$dsn->getPort() : '';

        $client = $this->client->withOptions([
            'base_uri' => 'https://'.$endpoint.'/api/',
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key '.$this->getUser($dsn),
            ],
        ]);

        return new DeeplProvider($client, $this->loader, $this->logger, $this->defaultLocale, $endpoint, $this->translatorBag);
    }

    protected function getSupportedSchemes(): array
    {
        return ['deepl'];
    }
}
