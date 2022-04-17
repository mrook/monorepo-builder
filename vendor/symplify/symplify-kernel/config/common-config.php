<?php

declare (strict_types=1);
namespace MonorepoBuilder20220417;

use MonorepoBuilder20220417\Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use MonorepoBuilder20220417\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory;
use MonorepoBuilder20220417\Symplify\PackageBuilder\Parameter\ParameterProvider;
use MonorepoBuilder20220417\Symplify\PackageBuilder\Reflection\PrivatesAccessor;
use MonorepoBuilder20220417\Symplify\SmartFileSystem\FileSystemFilter;
use MonorepoBuilder20220417\Symplify\SmartFileSystem\FileSystemGuard;
use MonorepoBuilder20220417\Symplify\SmartFileSystem\Finder\FinderSanitizer;
use MonorepoBuilder20220417\Symplify\SmartFileSystem\Finder\SmartFinder;
use MonorepoBuilder20220417\Symplify\SmartFileSystem\SmartFileSystem;
use function MonorepoBuilder20220417\Symfony\Component\DependencyInjection\Loader\Configurator\service;
return static function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $services = $containerConfigurator->services();
    $services->defaults()->public()->autowire()->autoconfigure();
    // symfony style
    $services->set(\MonorepoBuilder20220417\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory::class);
    $services->set(\MonorepoBuilder20220417\Symfony\Component\Console\Style\SymfonyStyle::class)->factory([\MonorepoBuilder20220417\Symfony\Component\DependencyInjection\Loader\Configurator\service(\MonorepoBuilder20220417\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory::class), 'create']);
    // filesystem
    $services->set(\MonorepoBuilder20220417\Symplify\SmartFileSystem\Finder\FinderSanitizer::class);
    $services->set(\MonorepoBuilder20220417\Symplify\SmartFileSystem\SmartFileSystem::class);
    $services->set(\MonorepoBuilder20220417\Symplify\SmartFileSystem\Finder\SmartFinder::class);
    $services->set(\MonorepoBuilder20220417\Symplify\SmartFileSystem\FileSystemGuard::class);
    $services->set(\MonorepoBuilder20220417\Symplify\SmartFileSystem\FileSystemFilter::class);
    $services->set(\MonorepoBuilder20220417\Symplify\PackageBuilder\Parameter\ParameterProvider::class)->args([\MonorepoBuilder20220417\Symfony\Component\DependencyInjection\Loader\Configurator\service('service_container')]);
    $services->set(\MonorepoBuilder20220417\Symplify\PackageBuilder\Reflection\PrivatesAccessor::class);
};
