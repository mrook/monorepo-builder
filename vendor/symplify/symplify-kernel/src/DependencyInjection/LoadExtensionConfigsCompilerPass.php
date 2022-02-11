<?php

declare (strict_types=1);
namespace MonorepoBuilder20220211\Symplify\SymplifyKernel\DependencyInjection;

use MonorepoBuilder20220211\Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use MonorepoBuilder20220211\Symfony\Component\DependencyInjection\ContainerBuilder;
/**
 * Mimics @see \Symfony\Component\HttpKernel\DependencyInjection\MergeExtensionConfigurationPass without dependency on
 * symfony/http-kernel
 */
final class LoadExtensionConfigsCompilerPass extends \MonorepoBuilder20220211\Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass
{
    public function process(\MonorepoBuilder20220211\Symfony\Component\DependencyInjection\ContainerBuilder $containerBuilder) : void
    {
        $extensionNames = \array_keys($containerBuilder->getExtensions());
        foreach ($extensionNames as $extensionName) {
            $containerBuilder->loadFromExtension($extensionName, []);
        }
        parent::process($containerBuilder);
    }
}
