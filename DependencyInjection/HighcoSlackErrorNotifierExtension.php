<?php

namespace Highco\SlackErrorNotifierBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * ElaoErrorNotifier Extension
 */
class HighcoSlackErrorNotifierExtension extends Extension
{
    /**
     * load configuration
     *
     * @param array            $configs   configs
     * @param ContainerBuilder $container container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        if (count($configs[0])) {
            $config = $this->processConfiguration($configuration, $configs);

            $container->setParameter('highco.slack_error_notifier.config', $config);

            $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__ . '/../Resources/config/')));
            $loader->load('services.xml');
        }
    }
}
