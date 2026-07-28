<?php

declare(strict_types=1);

namespace Survos\ExhibitBundle;

use Survos\ExhibitBundle\Service\SiteFinder;
use Survos\ExhibitBundle\Service\StoryFinder;
use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\SurvosKitBundle;
use Survos\Kit\Traits\HasConfigurableRoutes;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

#[RequiredBundle(SurvosKitBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosExhibitBundle extends AbstractSurvosBundle
{
    use HasConfigurableRoutes;

    public function configure(DefinitionConfigurator $definition): void
    {
        $children = $definition->rootNode()->children();
        $this->addRouteOptions($children, '/stories');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->addRouteLoaderCompilerPass($container);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Auto-registers src/Controller (StoryController) — see AbstractSurvosBundle.
        parent::loadExtension($config, $container, $builder);
        $this->captureRouteConfig($config);

        $container->services()
            ->set(StoryFinder::class)
            ->autowire()
            ->autoconfigure()
            ->public();
        $container->services()
            ->set(SiteFinder::class)
            ->autowire()
            ->autoconfigure()
            ->public();

        $this->registerRouteLoader($builder);
    }
}
