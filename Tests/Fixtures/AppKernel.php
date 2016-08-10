<?php
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle,
            new Symfony\Bundle\SecurityBundle\SecurityBundle,
            new Symfony\Bundle\TwigBundle\TwigBundle,
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle,
            new FOS\UserBundle\FOSUserBundle,
            new Vanio\WebBundle\VanioWebBundle,
            new Vanio\UserBundle\VanioUserBundle,
        ];
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sprintf('%s/../../var/cache/%s', __DIR__, $this->getEnvironment());
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../../var/logs';
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {}

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->loadFromExtension('framework', [
            'secret' => 'secret',
            'form' => null,
            'session' => null,
            'templating' => ['engines' => 'twig'],
        ]);
        $container->loadFromExtension('security', [
            'firewalls' => [
                'main' => ['form_login' => null],
            ],
        ]);
        $container->loadFromExtension('fos_user', [
            'db_driver' => 'propel',
            'user_class' => 'user_class',
            'use_flash_notifications' => false,
        ]);
    }
}
