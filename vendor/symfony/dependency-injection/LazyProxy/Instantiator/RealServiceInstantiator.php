<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MonorepoBuilder20220527\Symfony\Component\DependencyInjection\LazyProxy\Instantiator;

use MonorepoBuilder20220527\Symfony\Component\DependencyInjection\ContainerInterface;
use MonorepoBuilder20220527\Symfony\Component\DependencyInjection\Definition;
/**
 * {@inheritdoc}
 *
 * Noop proxy instantiator - produces the real service instead of a proxy instance.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class RealServiceInstantiator implements \MonorepoBuilder20220527\Symfony\Component\DependencyInjection\LazyProxy\Instantiator\InstantiatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function instantiateProxy(\MonorepoBuilder20220527\Symfony\Component\DependencyInjection\ContainerInterface $container, \MonorepoBuilder20220527\Symfony\Component\DependencyInjection\Definition $definition, string $id, callable $realInstantiator) : object
    {
        return $realInstantiator();
    }
}
