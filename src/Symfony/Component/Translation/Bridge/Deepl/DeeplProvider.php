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
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Translation\CatalogueMetadataAwareInterface;
use Symfony\Component\Translation\Exception\ProviderException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Raffaele Carelle <raffaele.carelle@gmail.com>
 */
final class DeeplProvider implements ProviderInterface
{
    public function __construct(
        private HttpClientInterface     $client,
        private LoaderInterface         $loader,
        private LoggerInterface         $logger,
        private string                  $defaultLocale,
        private string                  $endpoint,
        private ?TranslatorBagInterface $translatorBag = null
    )
    {
    }

    public function __toString(): string
    {
        return \sprintf('deepl://%s', $this->endpoint);
    }

    public function write(TranslatorBagInterface $translatorBag): void
    {
        $glossaries = $this->getGlossaries();

        \assert($translatorBag instanceof TranslatorBag);

        foreach ($translatorBag->getCatalogues() as $catalogue) {

            $sourceLocale = $this->defaultLocale;
            $targetLocale = $catalogue->getLocale();

            $glossary = array_filter($glossaries, function ($glossary) use ($sourceLocale, $targetLocale) {
                return $glossary['source_lang'] === $sourceLocale && $glossary['target_lang'] === $targetLocale;
            });

            if (count($glossary) === 1) {
                $response = $this->client->request('DELETE', 'glossaries/' . $glossary[0]['glossary_id']);

                if (201 !== $statusCode = $response->getStatusCode()) {
                    $this->logger->error(\sprintf('Unable to delete glossary for catalog "%s".', $catalogue->getLocale()));

                    $this->throwProviderException($statusCode, $response, \sprintf('Unable to delete glossary for catalog "%s".', $catalogue->getLocale()));
                }
            }

            $glossaryEntries = [];

            foreach ($catalogue->getDomains() as $domain) {
                if (!\count($catalogue->all($domain))) {
                    continue;
                }

                $glossaryEntries[] = $catalogue->all($domain);
            }

            $response = $this->client->request('POST', 'glossaries', [
                'body' => [
                    'ready' => true,
                    'name' =>'My Glossary',
                    'source_lang' => $sourceLocale,
                    'target_lang' => $targetLocale,
                    'creation_time' => (new \DateTime())->format('Y-m-d\TH:i:s.v\Z'),
                    'entry_count' => \count($glossaryEntries),
                ]
            ]);

            if (201 !== $statusCode = $response->getStatusCode()) {
                $this->logger->error(\sprintf('Unable to upload translations for domain "%s" to deepl=>"%s".', $domain, $response->getContent(false)));

                $this->throwProviderException($statusCode, $response, 'Unable to upload translations to deepl.');
            }
        }
    }

    public function read(array $domains, array $locales): TranslatorBag
    {
        foreach ($locales as $locale) {
            $phraseLocale = $this->getLocale($locale);

            foreach ($domains as $domain) {


            }
        }
    }

    public function delete(TranslatorBagInterface $translatorBag): void
    {

    }

    private function getLanguagesPairs(): array
    {
        return $this->client->request('GET', 'glossary-language-pairs')->toArray();
    }

    private function getGlossaries()
    {
        return $this->client->request('GET', 'glossaries')->toArray();
    }

    private function throwProviderException(int $statusCode, ResponseInterface $response, string $message): void
    {
        $headers = $response->getHeaders(false);

        throw match (true) {
            429 === $statusCode => new ProviderException(\sprintf('Rate limit exceeded (%s). please wait %s seconds.',
                $headers['x-rate-limit-limit'][0],
                $headers['x-rate-limit-reset'][0]
            ), $response),
            $statusCode <= 500 => new ProviderException($message, $response),
            default => new ProviderException('Provider server error.', $response),
        };
    }
}
