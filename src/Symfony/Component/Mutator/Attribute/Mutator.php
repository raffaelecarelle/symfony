<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mutator\Attribute;

/**
 * @author Raffaele Carelle <raffaele.carelle@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Mutator
{
    public function __construct(
        private string $name,
        private ?\Callable $callable = null,
        private ?string $class = null,
        private ?string $method = null,
        private ?string $factory = null,
    )
    {
    }
}
