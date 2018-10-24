<?php
namespace Vanio\UserBundle\DependencyInjection;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vanio\UserBundle\Form\ChangePasswordFormType;
use Vanio\UserBundle\Form\ProfileFormType;
use Vanio\UserBundle\Form\RegistrationFormType;
use Vanio\UserBundle\Form\ResettingFormType;

class VanioUserExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param mixed[] $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $config['db_driver'] = $container->getParameter('fos_user.storage');
        $confirmation = &$config['change_email']['confirmation'];
        $confirmation['from_email'] = isset($confirmation['from_email'])
            ? [$confirmation['from_email']['address'] => $confirmation['from_email']['sender_name']]
            : $container->getParameter('fos_user.registration.confirmation.from_email');

        if ($confirmation['enabled'] === null) {
            $confirmation['enabled'] = $container->getParameter('fos_user.registration.confirmation.enabled');
        }

        $loader = new XmlFileLoader($container, new FileLocator(sprintf('%s/../Resources/config', __DIR__)));
        $loader->load('config.xml');
        $container->setParameter('fos_user.storage', 'custom');
        $this->setContainerRecursiveParameter($container, 'vanio_user', $config);

        if ($config['social_authentication']) {
            $loader->load('social_authentication.xml');
        }

        if ($config['registration_target_path']) {
            $container
                ->getDefinition('vanio_user.listener.redirect_on_registration_success')
                ->setAbstract(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($confirmation['enabled']) {
            $container
                ->getDefinition('vanio_user.listener.email_change_confirmation_listener')
                ->setAbstract(false)
                ->addTag('kernel.event_subscriber');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $config = $this->processExtensionConfig($container, 'vanio_user');
        $this->prependSecurityConfig($container, $config);
        $bundles = $container->getParameter('kernel.bundles');
        $config['firewall_name'] = $config['firewall_name'] ?? $this->autodetectFirewallName($container);
        $config['social_authentication'] = $config['social_authentication'] ?? isset($bundles['HWIOAuthBundle']);
        $this->prependFosUserConfig($container, $config);
        $container->prependExtensionConfig('vanio_user', [
            'firewall_name' => $config['firewall_name'],
            'social_authentication' => $config['social_authentication'],
        ]);

        if (!$container->hasExtension('hwi_oauth')) {
            return;
        }

        $container->prependExtensionConfig('hwi_oauth', [
            'firewall_names' => [$config['firewall_name']],
            'failed_auth_path' => 'fos_user_security_login',
        ]);
        $container->setParameter(
            'vanio_user.resource_owner_properties',
            $this->processExtensionConfig($container, 'hwi_oauth')['fosub']['properties'] ?? []
        );
        $container->prependExtensionConfig('twig', [
            'paths' => [
                sprintf('%s/../Resources/views/', __DIR__) => '!VanioUser',
                sprintf('%s/../Resources/views', __DIR__) => 'HWIOAuth',
            ],
        ]);
    }

    /**
     * @param ContainerBuilder $container
     * @param mixed[] $config
     */
    private function prependSecurityConfig(ContainerBuilder $container, array $config): void
    {
        $container->prependExtensionConfig('security', [
            'encoders' => [UserInterface::class => 'bcrypt'],
            'providers' => [
                'fos_userbundle' => [
                    'id' => $config['email_only']
                        ? 'fos_user.user_provider.username_email'
                        : 'fos_user.user_provider.username',
                ],
            ],
        ]);
    }

    /**
     * @param ContainerBuilder $container
     * @param mixed[] $config
     */
    private function prependFosUserConfig(ContainerBuilder $container, array $config): void
    {
        $container->prependExtensionConfig('fos_user', [
            'firewall_name' => $config['firewall_name'],
            'use_listener' => false,
            'use_flash_notifications' => $config['use_flash_notifications'],
            'registration' => [
                'form' => ['type' => RegistrationFormType::class],
                'confirmation' => ['template' => '@VanioUser/Registration/email.html.twig'],
            ],
            'resetting' => [
                'form' => ['type' => ResettingFormType::class],
                'email' => ['template' => '@VanioUser/Resetting/email.html.twig'],
            ],
            'profile' => [
                'form' => ['type' => ProfileFormType::class],
            ],
            'change_password' => [
                'form' => ['type' => ChangePasswordFormType::class],
            ],
            'service' => [
                'mailer' => $container->hasExtension('vanio_mailing')
                    ? 'vanio_user.mailer.easy_mailer'
                    : 'vanio_user.mailer.twig_swift_mailer',
            ],
        ]);
    }

    private function autodetectFirewallName(ContainerBuilder $container): string
    {
        $securityConfig = $this->processExtensionConfig($container, 'security');

        foreach ($securityConfig['firewalls'] as $name => $options) {
            if ($options['security']) {
                return $name;
            }
        }

        throw new \LogicException(
            'Unable to autodetect firewall name. Have you properly configured security extension?'
        );
    }

    /**
     * @return mixed[]
     */
    private function processExtensionConfig(ContainerBuilder $container, string $name): array
    {
        /** @var Extension $extension */
        $extension = $container->getExtension($name);

        return $this->processConfiguration(
            $extension->getConfiguration([], $container),
            $container->getExtensionConfig($name)
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param mixed $value
     */
    private function setContainerRecursiveParameter(ContainerBuilder $container, string $name, $value): void
    {
        $container->setParameter($name, $value);

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->setContainerRecursiveParameter($container, "$name.$k", $v);
            }
        }
    }
}
