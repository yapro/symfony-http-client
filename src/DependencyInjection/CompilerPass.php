<?php
declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use YaPro\SymfonyHttpClientExt\Decorator\HttpClientRequestKeeper;
use YaPro\SymfonyHttpClientExt\SymfonyRequestToCurlCommandConverter;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // возвращает имена сервисов так же как в скоупе фреймворка:
        $list = $container->findTaggedServiceIds('http_client.client');
        foreach ($list as $clientName => $config) {
            // if ($clientName==='http_client') {
            //     continue;
            // }
            $decoratorId = HttpClientRequestKeeper::class . '.'.$clientName;
            if ($clientName === $decoratorId) {
                continue;
            }

            // Add the new decorated service
            $container->register($decoratorId, HttpClientRequestKeeper::class)
                ->setDecoratedService($clientName)
                ->setPublic(true)
                ->setAutowired(true);
        }
    }
}
