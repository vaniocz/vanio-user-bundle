<?php
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Vanio\UserBundle\Tests\Fixtures\DummyUserManager;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @return Bundle[]
     */
    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle,
            new Symfony\Bundle\SecurityBundle\SecurityBundle,
            new Symfony\Bundle\TwigBundle\TwigBundle,
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle,
            new FOS\UserBundle\FOSUserBundle,
            new HWI\Bundle\OAuthBundle\HWIOAuthBundle,
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
        return sprintf('%s/var/cache/%s', __DIR__, $this->getEnvironment());
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/var/logs';
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {}

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setDefinition('dummy_user_manager', new Definition(DummyUserManager::class));
        $container->loadFromExtension('framework', [
            'secret' => 'secret',
            'form' => null,
            'session' => null,
            'templating' => ['engines' => 'twig'],
        ]);
        $container->loadFromExtension('security', [
            'firewalls' => [
                'main' => [
                    'oauth' => [
                        'login_path' => 'login_path',
                        'oauth_user_provider' => ['oauth' => null],
                        'resource_owners' => null,
                    ],
                ],
            ],
        ]);
        $container->loadFromExtension('fos_user', [
            'from_email' => [
                'address' => 'webmaster@example.com',
                'sender_name' => 'webmaster',
            ],
            'db_driver' => 'custom',
            'user_class' => 'user_class',
            'service' => ['user_manager' => 'dummy_user_manager'],
            'use_flash_notifications' => false,
        ]);
        $container->loadFromExtension('hwi_oauth', ['resource_owners' => null]);
    }
}
