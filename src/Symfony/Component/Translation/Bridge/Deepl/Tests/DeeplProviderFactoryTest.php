<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Translation\Bridge\Deepl\Tests;

use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Translation\Bridge\Deepl\DeeplProviderFactory;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Provider\ProviderFactoryInterface;
use Symfony\Component\Translation\Test\AbstractProviderFactoryTestCase;
use Symfony\Component\Translation\Test\IncompleteDsnTestTrait;
use Symfony\Component\Translation\TranslatorBagInterface;

class DeeplProviderFactoryTest extends AbstractProviderFactoryTestCase
{
    use IncompleteDsnTestTrait;

    public static function supportsProvider(): iterable
    {
        yield [true, 'deepl://API_TOKEN@default'];
        yield [false, 'somethingElse://API_TOKEN@default'];
    }

    public static function createProvider(): iterable
    {
        yield [
            'deepl://api.deepl.com',
            'deepl://API_TOKEN@default',
        ];

        yield [
            'deepl://ORGANIZATION_DOMAIN.api.deepl.com',
            'deepl://API_TOKEN@ORGANIZATION_DOMAIN.default',
        ];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://API_TOKEN@default'];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield ['deepl://default'];
    }

    public function createFactory(): ProviderFactoryInterface
    {
        return new DeeplProviderFactory(new MockHttpClient(), new NullLogger(), 'en', $this->createMock(LoaderInterface::class), $this->createMock(TranslatorBagInterface::class));
    }
}
