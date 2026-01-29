<?php

namespace Dherlou\ContaoKKMSitePackage;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ContaoKKMSitePackage extends AbstractBundle {
    
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $builder): void {
        $configurator->import('../config/services.yaml');
    }

}
