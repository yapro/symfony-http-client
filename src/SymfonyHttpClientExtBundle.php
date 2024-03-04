<?php

declare(strict_types=1);

namespace YaPro\SymfonyHttpClientExt;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use YaPro\SymfonyHttpClientExt\DependencyInjection\CompilerPass;

class SymfonyHttpClientExtBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CompilerPass());
    }
}
