<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MonorepoBuilder20210705\Symfony\Component\HttpKernel\DependencyInjection;

use MonorepoBuilder20210705\Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use MonorepoBuilder20210705\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use MonorepoBuilder20210705\Symfony\Component\DependencyInjection\ContainerBuilder;
use MonorepoBuilder20210705\Symfony\Component\DependencyInjection\Reference;
/**
 * Register all services that have the "kernel.locale_aware" tag into the listener.
 *
 * @author Pierre Bobiet <pierrebobiet@gmail.com>
 */
class RegisterLocaleAwareServicesPass implements \MonorepoBuilder20210705\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{
    private $listenerServiceId;
    private $localeAwareTag;
    public function __construct(string $listenerServiceId = 'locale_aware_listener', string $localeAwareTag = 'kernel.locale_aware')
    {
        if (0 < \func_num_args()) {
            trigger_deprecation('symfony/http-kernel', '5.3', 'Configuring "%s" is deprecated.', __CLASS__);
        }
        $this->listenerServiceId = $listenerServiceId;
        $this->localeAwareTag = $localeAwareTag;
    }
    public function process(\MonorepoBuilder20210705\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->listenerServiceId)) {
            return;
        }
        $services = [];
        foreach ($container->findTaggedServiceIds($this->localeAwareTag) as $id => $tags) {
            $services[] = new \MonorepoBuilder20210705\Symfony\Component\DependencyInjection\Reference($id);
        }
        if (!$services) {
            $container->removeDefinition($this->listenerServiceId);
            return;
        }
        $container->getDefinition($this->listenerServiceId)->setArgument(0, new \MonorepoBuilder20210705\Symfony\Component\DependencyInjection\Argument\IteratorArgument($services));
    }
}